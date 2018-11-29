<?php
  /**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this distribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can redistribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/
  /**
  * saveEntityData
  *
    * saves entity data for passed entity data structure of the form
  *
  * {"tableprefix or tablename": array of records
  *                   where each record is an array of columnName:value pairs
  *                             where id column is required even new records of the form prefix:newID,
  * }
  *
  * {"tok":[{"tok_id":355,"tok_grapheme_ids":"{594,595,596,597,598}"},
  *         {"tok_id":"new14","tok_grapheme_ids":"{595,596,599,600,601}"}]
  * }
  *
  * return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
  *
  * { "entityName" : {
  *           "total" : #entityRecords,
  *           "success": true or false,   //if errors encountered false is returned
  *           "columns": array of columnNames for the records returned
  *           "records" : array of records where each record is an array of column values in "columns" order
  *    }
  * }
  *
  * { "token" : {
  *           "total" : 2,
  *           "success": true,
  *           "columns": ["tok_id","tok_value"," ...,"tok_scratch"],
  *           "records" : [[355,"putra",.....,"\"CKN\":\"CKI02661\""],
  *                        [1209,"putre",.....,"\"CKN\":\"CKI02661\""]]
  *    }
  * }
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid aksara
  require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
  require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
  require_once (dirname(__FILE__) . '/../model/entities/Token.php');
  require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
  require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
  require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
  require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $warnings = array();
  $ednOwnerID = null;
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    array_push($errors,"invalid json data - decode failed");
  } else {
    $defAttrIDs = getUserDefAttrIDs();
    $defVisIDs = getUserDefVisibilityIDs();
    $defOwnerID = getUserDefEditorID();
    if ( isset($data['ednID'])) {//get edition
      $edition = new Edition($data['ednID']);
      if ($edition->hasError()) {
        array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
      } else if ($edition->isReadonly()) {
        array_push($errors,"edition readonly");
      } else {
        $ednOwnerID = $edition->getOwnerID();
        //get default attribution
        if (!$defAttrIDs || count($defAttrIDs) == 0) {
          $attrIDs = $edition->getAttributionIDs();
          if ($attrIDs && count($attrIDs) > 0 ) {
            $defAttrIDs = array($attrIDs[0]);
          }
        }
        //get default visibility
        if (!$defVisIDs || count($defVisIDs) == 0) {
          $visIDs = $edition->getVisibilityIDs();
          if ($visIDs && count($visIDs) > 0 ) {
            $defVisIDs = array($visIDs[0]);
          }
        }
        //find edition's Physical and Text sequences
        $seqPhys = null;
        $seqText = null;
        $oldPhysSeqID = null;
        $oldTextSeqID = null;
        $edSeqs = $edition->getSequences(true);
        foreach ($edSeqs as $edSequence) {
          $seqType = $edSequence->getType();
          if (!$seqPhys && $seqType == "TextPhysical"){
            $seqPhys = $edSequence;
          }
          if (!$seqText && $seqType == "Text"){
            $seqText = $edSequence;
          }
        }
      }
    } else {
      array_push($errors,"unaccessable edition");
    }
    $delLineSeqGID = null;
    if ( isset($data['delLineSeqGID'])) {//get reference physical Line sequence GID
      $delLineSeqGID = $data['delLineSeqGID'];
    } else {
      array_push($errors,"no delLine GID parameter");
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  //*********************** process physical line **********************************
  if (count($errors) == 0) {
    //remove delLineGID from physical sequence
    if ($seqPhys->isReadonly()) {
      $oldPhysSeqID = $seqPhys->getID();
      $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
    }
    $physSeqEntityIDs = $seqPhys->getEntityIDs();
    if (!$physSeqEntityIDs || count($physSeqEntityIDs) == 0) {
      array_push($errors,"error deleting line from empty text physical sequence seq".$seqPhys->getID());
    } else {
      //find index of $delLineSeqGID in text physical sequence
      $delSeqIndex = array_search($delLineSeqGID,$physSeqEntityIDs);
      if ($delSeqIndex === false) {
        array_push($errors,"error deleting line $delLineSeqGID not found in text physical sequence seq".$seqPhys->getID());
      } else {
        array_splice($physSeqEntityIDs,$delSeqIndex,1);//remove physLine seq from text physical seq
        $seqPhys->setEntityIDs($physSeqEntityIDs);
        $seqPhys->save();
        if ($seqPhys->hasError()) {
          array_push($errors,"error updating text physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
        }else if ($oldPhysSeqID){// return insert data
          addNewEntityReturnData('seq',$seqPhys);
          //addRemoveEntityReturnData('seq',$oldPhysSeqID);
        }else {
          addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
        }
        if ($delLineSeqGID) {
          //get physical Line sequence
          $physLineSeq = new Sequence(substr($delLineSeqGID,4));
          $delGraIDs = array();
          if (!$physLineSeq || $physLineSeq->hasError()) {
            array_push($errors,"error deleting physical Line sequence '".$physLineSeq->getLabel()."' - ".$physLineSeq->getErrors(true));
          } else if ($physLineSeq->isReadonly()) { //if delLine Sequence is not owned
            array_push($errors,"error permission denied for deleting physical Line sequence '".$physLineSeq->getLabel()."' - ".$physLineSeq->getErrors(true));
          } else {
            //delete delLine sequence
            $physLineSeq->markForDelete();
            addRemoveEntityReturnData('seq',substr($delLineSeqGID,4));
            //delete all owned syllables
            if (count($physLineSeq->getEntityIDs())) {//freetext will not have entities
              foreach ($physLineSeq->getEntities(true) as $syllable) {
                if (!$syllable->isReadonly()) {// owned so delete it
                  $syllable->markForDelete();
                  addRemoveEntityReturnData('scl',$syllable->getID());
                  //delete all syllable graphemes tracking graIDs
                  foreach ($syllable->getGraphemes(true) as $grapheme) {
                    if (!$grapheme->isReadonly()) {// owned so delete it
                      $grapheme->markForDelete();
                      addRemoveEntityReturnData('gra',$grapheme->getID());
                      array_push($delGraIDs,$grapheme->getID());
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  //*********************** process text Div components and any empty owned Div **********************************
  if (count($errors) == 0) {
    if (isset($data['context'])) {
      $splitToken = (count($data['context'])>1);
      $extraneousGraIDs = array();
      foreach ($data['context'] as $seqGID => $componentIDs) {
        //get textDiv Sequence
        $textDivSeq = new Sequence(substr($seqGID,4));
        if ($textDivSeq->hasError()) {
          array_push($errors,"error getting text div sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
        } else {
          //get txtDivEntGIDs array for tracking removed ids
          $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
          while ($entGID = array_shift($componentIDs)) {
            //remove each components GID from text Div Sequence
            $indexEntGID = array_search($entGID,$textDivSeqEntityIDs);
            if ($indexEntGID === false) {//component GID is not in this textDiv
              array_push($errors,"error removing component from text div sequence GID '".
                          $entGID."' was not in seq entIDs for $seqGID");
              continue;
            } else {
              array_splice($textDivSeqEntityIDs,$indexEntGID,1);//remove component from textDiv seq
              delTokCmpHierarchy($entGID);
            }
          }
          $newTxtDivSeqGID = $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
          $oldTxtDivSeqID = $textDivSeq->getID();
          $textSeqEntityIDs = $seqText->getEntityIDs();
          $txtDivSeqIndex = array_search($oldTxtDivSeqGID,$textSeqEntityIDs);
          //if leftover component ids
          if (count($textDivSeqEntityIDs) > 0) {
            //if text Div not owned then clone
            if ($textDivSeq->isReadonly()) {
              $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
            }
            //need to save the modified component list
            $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
            //save text division sequence
            $textDivSeq->save();
            $newTxtDivSeqGID = $textDivSeq->getGlobalID();
            if ($textDivSeq->hasError()) {
              array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
            }else if ($oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so it's new
              addNewEntityReturnData('seq',$textDivSeq);
              //addRemoveEntityReturnData('seq',$oldTxtDivSeqID);
              array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID);
            }else { // only updated
              //changed components on a cached sequence so invalidate cache to recalc on next refresh
              invalidateCachedSeqEntities($textDivSeq->getID(),$edition->getID());
              addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
            }
          } else {//else empty textDiv need to remove old ID
            array_splice($textSeqEntityIDs,$txtDivSeqIndex,1);
          }
          if ($oldTxtDivSeqGID != $newTxtDivSeqGID || count($textDivSeqEntityIDs) == 0){
            //update text sequence
            if ($seqText->isReadonly()) {
              $oldTextSeqID = $seqText->getID();
              $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $seqText->setEntityIDs($textSeqEntityIDs);
            //save text sequence
            $seqText->save();
            if ($seqText->hasError()) {
              array_push($errors,"error updating text sequence '".$seqText->getLabel()."' - ".$seqText->getErrors(true));
            }else if ($oldTextSeqID){//cloned so it's new
              addNewEntityReturnData('seq',$seqText);//**********new seq
              //addRemoveEntityReturnData('seq',$oldTextSeqID);
            }else { // only updated
              addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
            }
          }
          //******************** edition *********************
          if (count($errors) == 0 && $edition) {
            //touch edition for synch code
            $edition->storeScratchProperty("lastModified",$edition->getModified());
            $edition->setStatus('changed');
            if ($oldPhysSeqID || $oldTextSeqID) {
              //get segIDs
              $edSeqIds = $edition->getSequenceIDs();
              //if phys changed update id
              if ($oldPhysSeqID) {
                $seqIDIndex = array_search($oldPhysSeqID,$edSeqIds);
                array_splice($edSeqIds,$seqIDIndex,1,$seqPhys->getID());
                $retVal['newPhysSeqID'] = $seqPhys->getID();
              }
              if ($oldTextSeqID) {
                $seqIDIndex = array_search($oldTextSeqID,$edSeqIds);
                array_splice($edSeqIds,$seqIDIndex,1,$seqText->getID());
                $retVal['newTextSeqID'] = $seqText->getID();
              }
              //update edition seqIDs
              $edition->setSequenceIDs($edSeqIds);
            }
            $edition->save();
            invalidateCachedEditionEntities($edition->getID());
            invalidateCachedEditionViewerHtml($edition->getID());
            invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
            if ($edition->hasError()) {
              array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
            }else{
              //array_push($updated,$edition);//********** updated edn
              addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
            }
          }
        }//else
      }//foreach data context
      if (count($extraneousGraIDs) > 0) {
        array_push($warnings,"warning while deleting line found extra graphemes - ".json_encode($extraneousGraIDs));
      }
    }//if data context
  }

  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  if (getEntityCount() > 0) {
    $retVal["entities"] = getEntities();
  }
  if ($healthLogging && $edition) {
    $retVal["editionHealth"] = checkEditionHealth($edition->getID(),false);
  }
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }

  function delTokCmpHierarchy($entGID) {
    global $errors;
    //delete each owned component
    $id = substr($entGID,4);
    switch (substr($entGID,0,3)) {
      case "tok":
        $token = new Token($id);
        if ($token->hasError()) {
          array_push($errors,"error deleting token '".$token->getValue()."' - ".$token->getErrors(true));
        } else if (!$token->isReadonly()) { // mark for delete
          $token->markForDelete();
          addRemoveEntityReturnData('tok',$id);
        }
        break;
      case "cmp":
        $compound = new Compound($id);
        if ($compound->hasError()) {
          array_push($errors,"error deleting compound '".$compound->getValue()."' - ".$compound->getErrors(true));
        } else if(!$compound->isReadonly()){ // mark for delete
          $compound->markForDelete();
          addRemoveEntityReturnData('cmp',$id);
          foreach ($compound->getComponentIDs() as $tokCmpGID) {
            delTokCmpHierarchy($tokCmpGID);
          }
        }
        break;
      default:
    }
  }

  ?>
