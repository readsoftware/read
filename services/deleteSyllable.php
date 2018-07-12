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
  * deleteSyllable
  *
    * deletes a syllable from an edition's entities
  *
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
  require_once (dirname(__FILE__) . '/../model/entities/JsonCache.php');
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $entities = array();
  $warnings = array();
  $ednOwnerID = null;
  $compound = null;
  $token = null;
  $syllable = null;
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
    $sclID = null;
    if ( isset($data['sclID'])) {//get SyllableClusterID
      $sclID = $data['sclID'];
      $syllable = new SyllableCluster($sclID);
      if ($syllable->hasError()) {
        array_push($errors,"creating syllable id = $sclID - ".join(",",$syllable->getErrors()));
      }
    }
    $physLineSeqID = null;
    if ( isset($data['lineSeqID'])) {//get line sequence
      $physLineSeqID = $data['lineSeqID'];
    } else if ($sclID){ //search for physical Line using sclID
      foreach($seqPhys->getEntities(true) as $edPhysLineSeq) {
        if (count($edPhysLineSeq->getEntityIDs()) && in_array("scl:".$sclID,$edPhysLineSeq->getEntityIDs())) {
          $physLineSeqID = $edPhysLineSeq->getID();
          break;
        }
      }
    }
    $lineSclIDs = null;
    if ($physLineSeqID) {
      $physLineSeq = new Sequence($physLineSeqID);
      if ($physLineSeq->hasError()) {
        array_push($errors,"creating sequence id = $physLineSeqID - ".join(",",$physLineSeq->getErrors()));
      }
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  if (count($errors) == 0) {
    if (isset($data['context'])) {
      $context = explode(",",$data['context']);
      while ($gid = array_pop($context)) {
        $id = substr($gid,3);
        switch (substr($gid,0,3)) {
          case "scl":
              if (!$sclID) {
                $sclID = $id;
                $syllable = new SyllableCluster($sclID);
                if ($syllable->hasError()) {
                  array_push($errors,"creating syllable id = $sclID - ".join(",",$syllable->getErrors()));
                }
              } else if ($sclID != $id) {
                array_push($warnings,"syllable id = $sclID does not match context syllable id = $id");
              }
              break;
          case "tok":
            $token = new Token($id);
            if ($token->hasError()) {
              array_push($errors,"creating context token id = $id - ".join(",",$token->getErrors()));
            }
            break;
          case "cmp":
            $compound = new Compound($id);
            if ($compound->hasError()) {
              array_push($errors,"creating context compound id = $id - ".join(",",$compound->getErrors()));
              break;
            }
            if (!@$compounds) {
              $compounds = array($compound);
            } else {
                array_push($compounds, $compound);
            }
            break;
          case "seq":
            $textDivSeq = new Sequence($id);
            if ($textDivSeq->hasError()) {
              array_push($errors,"creating context text division sequnce id = $id - ".join(",",$textDivSeq->getErrors()));
              break;
            }
            break;
        }
      }
    } else {
      array_push($errors,"missing context");
    }
    // remove syllable and graphemes
    if ($sclID && count($errors) == 0) {
      //if syllable is owned then delete graphemes and syllable
      if (!$syllable->isReadonly()) {//owned
        foreach($syllable->getGraphemes(true) as $grapheme) {
          $grapheme->markForDelete();
          $grapheme->save();
          if ($grapheme->hasError()) {
            array_push($errors,"deleting grpaheme id = ".$grapheme->getID()." - ".join(",",$grapheme->getErrors()));
          }else{
            addRemoveEntityReturnData('gra',$grapheme->getID());
          }
        }
        $syllable->markForDelete();
        $syllable->save();
        if ($syllable->hasError()) {
          array_push($errors,"deleting syllable id = $sclID - ".join(",",$syllable->getErrors()));
        }else{
          addRemoveEntityReturnData('scl',$sclID);
        }
      }
      $sclGID = "scl:$sclID";
      //******************* physical line sequence **********************
      if ($physLineSeq) {
        $oldPhysLineSeqID = null;
        $oldPhysLineSeqGID = null;
        $physLineRemoved = false;
        $physLineSclGIDs = $physLineSeq->getEntityIDs();
        //if last syllable of the physical line remove line
        if (count($physLineSclGIDs) == 1 && $physLineSclGIDs[0] == $sclGID) {
          $oldPhysLineSeqGID = $physLineSeq->getGlobalID();
          if (!$physLineSeq->isReadonly()) {
            $physLineSeq->markForDelete();
            addRemoveEntityReturnData('seq',$physLineSeq->getID());
          }
          $physLineRemoved = true;
        } else { // adjust line by removing sclGID, cloning if not owned
          $sclIndex = array_search($sclGID,$physLineSclGIDs);
          if ($physLineSeq->isReadonly()) {//clone physicalLine sequence if not owned
            $oldPhysLineSeqID = $physLineSeq->getID();
            $oldPhysLineSeqGID = $physLineSeq->getGlobalID();
            $physLineSeq = $physLineSeq->cloneEntity($defAttrIDs,$defVisIDs);
          } else {
              invalidateCachedSeqEntities($physLineSeq->getID(),$edition->getID());
            }
          //find index of refScl in physical line sequence
          array_splice($physLineSclGIDs,$sclIndex,1);//remove syllable from physical line
          $physLineSeq->setEntityIDs($physLineSclGIDs);
          $physLineSeq->save();
          if ($physLineSeq->hasError()) {
            array_push($errors,"error updating physical line sequence '".$physLineSeq->getLabel()."' - ".$physLineSeq->getErrors(true));
          } else if ($oldPhysLineSeqID){//insert return data
            addNewEntityReturnData('seq',$physLineSeq);
            $retVal['newPhysLineSeqID'] = $physLineSeq->getID();
            //addRemoveEntityReturnData('seq',$oldPhysLineSeqID);
          } else {
            //changed components on a cached sequence so invalidate cache to recalc on next refresh
            invalidateCachedSeqEntities($physLineSeq->getID(),$edition->getID());
            addUpdateEntityReturnData('seq',$physLineSeq->getID(),'entityIDs',$physLineSeq->getEntityIDs());
          }
        }
      }
      $sclGraIDs = $syllable->getGraphemeIDs();
      //******************* physical text sequence **********************
      if ($oldPhysLineSeqGID){//update physical text sequence
        if ($seqPhys->isReadonly()) {
          $oldPhysSeqID = $seqPhys->getID();
          $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
        }
        $physSeqEntityIDs = $seqPhys->getEntityIDs();
        $seqIndex = array_search($oldPhysLineSeqGID,$physSeqEntityIDs);
        if ($physLineRemoved) {//only remove
          array_splice($physSeqEntityIDs,$seqIndex,1);
        } else {//replace with cloned ID
          array_splice($physSeqEntityIDs,$seqIndex,1,$physLineSeq->getGlobalID());
        }
        $seqPhys->setEntityIDs($physSeqEntityIDs);
        $seqPhys->save();
        if ($seqPhys->hasError()) {
          array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
        }else if ($oldPhysSeqID){// return insert data
          addNewEntityReturnData('seq',$seqPhys);
          //addRemoveEntityReturnData('seq',$oldPhysSeqID);
        }else {
          //array_push($updated,$seqPhys);//**********update seq
          addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
        }
      }
      //********************* token *****************************
      if ($token && count($errors) == 0) { // update token
        $tokCloned = false;
        //calc newTokGraIDs
        $tokGraIDs = $token->getGraphemeIDs();
        $startIndex = array_search($sclGraIDs[0],$tokGraIDs);
        $endIndex = array_search($sclGraIDs[count($sclGraIDs)-1],$tokGraIDs);
        $removeEntity = false;
        if ( $startIndex === false || $endIndex === false || $startIndex > $endIndex) {
          array_push($errors,"token '".$token->getValue()."' is out of synch with original db syllable '".$strSylDB);
        } else {
          $startTokIDs = array();
          if ($startIndex > 0) {
            $startTokIDs = array_slice($tokGraIDs,0,$startIndex);
          }
          $endTokIDs = array();
          if ($endIndex < (count($tokGraIDs) -1)) {
            $endTokIDs = array_slice($tokGraIDs,$endIndex+1);
          }
          $newTokGraIDs = array_merge($startTokIDs,$endTokIDs);
          $oldTokCmpGID = null;
          $newTokCmpGID = null;
          if (count($newTokGraIDs) == 0 ) {//token is empty so remove the token
            $oldTokCmpGID = $token->getGlobalID();
            if (!$token->isReadonly()){//owned
              $token->markForDelete();
              addRemoveEntityReturnData('tok',$token->getID());
            }
            $removeEntity = true;
          } else { // update token graIDs and clone as needed
            if ($token->isReadonly()){// clone token
              $oldTokCmpGID = $token->getGlobalID();
              $token = $token->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $token->setGraphemeIDs($newTokGraIDs);
            $token->getValue(true);//cause recalc
            $token->updateLocationLabel();
            $token->updateBaselineInfo();
            $token->save();
            $newTokCmpGID = $token->getGlobalID();
            if ($token->hasError()) {
              array_push($errors,"error cloning token '".$token->getValue()."' - ".$token->getErrors(true));
            } else if ($oldTokCmpGID) {//cloned
              $tokCloned = true;
              addNewEntityReturnData('tok',$token);
              //addRemoveEntityReturnData('tok',$oldTokCmpGID);
            } else {
              addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
              addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
              addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
              addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs());
              addUpdateEntityReturnData('tok',$token->getID(),'sort', $token->getSortCode());
              addUpdateEntityReturnData('tok',$token->getID(),'sort2', $token->getSortCode2());
            }
          }
        }
        //************ compounds *******************
        if (count($errors) == 0 && $oldTokCmpGID
            && isset($compounds) && count($compounds) > 0) {//update compounds
          while (count($compounds) && $oldTokCmpGID != $newTokCmpGID) {
            $compound = array_shift($compounds);
            $componentIDs = $compound->getComponentIDs();
            $tokCmpIndex = array_search($oldTokCmpGID,$componentIDs);
            if ($removeEntity) {//only remove entity GID from container
              array_splice($componentIDs,$tokCmpIndex,1);
              $removeEntity = false;
            } else {//replace with cloned ID
              array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID);
            }
            if (count($componentIDs) == 1 ) {//compound has one component so swap to promote component and remove compound
              $oldTokCmpGID = $compound->getGlobalID();
              $newTokCmpGID = $componentIDs[0];
              if (!$compound->isReadonly()) {
                $compound->markForDelete();
                addRemoveEntityReturnData('cmp',$compound->getID());
              }
            } else { // update compound entityIDs and clone as needed
              $oldTokCmpGID = $compound->getGlobalID();
              if ($compound->isReadonly()) {
                $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
              }
              // update compound container
              $compound->setComponentIDs($componentIDs);
              $compound->getValue(true);//cause recalc
              $compound->updateLocationLabel();
              $compound->updateBaselineInfo();
              $compound->save();
              $newTokCmpGID = $compound->getGlobalID();
              if ($compound->hasError()) {
                array_push($errors,"error updating compound '".$compound->getValue()."' - ".$compound->getErrors(true));
              }else if ($oldTokCmpGID != $newTokCmpGID){
                addNewEntityReturnData('cmp',$compound);
                //addRemoveEntityReturnData('cmp',substr($oldTokCmpGID,4));
              }else {
                addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
                addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
              }
            }
          }
        }
        //******************** text division sequence ****************
        if (count($errors) == 0 && $oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID) {//token or compound changed update text division sequence
          // update text dividion components ids by replacing $oldTokCmpGID with $newTokCmpGID
          $newTxtDivSeqGID = null;
          $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
          $tokCmpIndex = array_search($oldTokCmpGID,$textDivSeqEntityIDs);
          if ($removeEntity) {//only remove entity GID from container
            array_splice($textDivSeqEntityIDs,$tokCmpIndex,1);
            $removeEntity = false;
          } else {//replace with cloned ID
            array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmpGID);
          }
          if (count($textDivSeqEntityIDs) == 0 ) {//text division is empty so remove it
            $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
            if (!$textDivSeq->isReadonly()) {
              $textDivSeq->markForDelete();
              addRemoveEntityReturnData('seq',$textDivSeq->getID());
            }
            $removeEntity = true;//mark so that GID is removed from container
          } else { // update text dividion sequence entityIDs and clone as needed
            $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
            $oldTxtDivSeqID = $textDivSeq->getID();
            if ($textDivSeq->isReadonly()) {
              $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
            } else {
              invalidateCachedSeqEntities($textDivSeq->getID(),$edition->getID());
            }
            $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
            //save text division sequence
            $textDivSeq->save();
            $newTxtDivSeqGID = $textDivSeq->getGlobalID();
            if ($textDivSeq->hasError()) {
              array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
            }else if ($oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so it's new
              addNewEntityReturnData('seq',$textDivSeq);
              $retVal['newTextDivSeqGID'] = $newTxtDivSeqGID;
              //addRemoveEntityReturnData('seq',$oldTxtDivSeqID);
            }else { // only updated
              //changed components on a cached sequence so invalidate cache to recalc on next refresh
              addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
            }
          }
        //******************** text sequence ****************
          if (count($errors) == 0 && $oldTxtDivSeqGID != $newTxtDivSeqGID){//removed or cloned so update
            //clone text sequence if not owned
            if ($seqText->isReadonly()) {
              $oldTextSeqID = $seqText->getID();
              $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $textSeqEntityIDs = $seqText->getEntityIDs();
            $txtDivSeqIndex = array_search($oldTxtDivSeqGID,$textSeqEntityIDs);
            if ($removeEntity) {//only remove entity GID from container
              array_splice($textSeqEntityIDs,$txtDivSeqIndex,1);
              $removeEntity = false;
            } else {//replace with cloned ID
              array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID);
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
        }
      }
      //******************** edition *********************
      if (count($errors) == 0 && $edition) {
        //touch edition for synch code
        $edition->storeScratchProperty("lastModified",$edition->getModified());
        $edition->setStatus('changed');
        // update edition if sequences cloned
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
    }
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
  if (count($entities)) {
    $retVal["entities"] = $entities;
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
  ?>
