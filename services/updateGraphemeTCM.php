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
* updateGraphemeTCM
*
* saves TCM data for a grapheme or set of graphemes
* {"tcmState": array of records
*                   where each record is an array of [graphemeID,context]
* }
*
**[["S",
*   [["298","seq94,cmp36,cmp15,tok50,scl147"],
*   ["299","seq94,cmp36,cmp15,tok50,scl147"],
*   ["300","seq94,cmp36,cmp15,tok50,scl148"],
*   ["301","seq94,cmp36,cmp15,tok50,scl148"],
*   ["302","seq94,cmp36,cmp15,tok50,scl149"]]],
* ["D",
*   [["303","seq94,cmp36,cmp15,tok50,scl149"],
*   ["304","seq94,cmp36,cmp15,tok50,scl150"],
*   ["305","seq94,cmp36,cmp15,tok50,scl150"],
*   ["306","seq94,cmp36,cmp15,tok50,scl151"],
*   ["307","seq94,cmp36,cmp15,tok50,scl151"]]],
* ["S",
*   [["597","seq94,cmp36,tok148,scl295"],
*   ["598","seq94,cmp36,tok148,scl295"],
*   ["310","seq92,tok146,scl153"],
*   ["311","seq92,tok146,scl153"],
*   ["601","seq92,tok146,scl296"],
*   ["602","seq92,tok146,scl296"],
*   ["312","seq92,tok147,scl154"],
*   ["313","seq92,tok147,scl154"],
*   ["316","seq92,tok147,scl156"],
*   ["317","seq92,tok147,scl156"],
*   ["318","seq92,tok54,scl157"],
*   ["319","seq92,tok54,scl157"],
*   ["320","seq92,tok54,scl158"],
*   ["321","seq92,tok54,scl158"],
*   ["322","seq92,tok54,scl159"],
*   ["323","seq92,tok54,scl159"],
*   ["324","seq92,tok54,scl160"]]],
* ["U",
*   [["325","seq92,tok54,scl160"]]]]"

* return json format:  //whole record data returned due to multi-user editing
*
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
        $oldPhysSeqID = null;//for cloning readonly if needed
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
    $physLineSeqID = null;
    $oldPhysLineSeqID = null;
    $lineSclIDs = null;
    if ( isset($data['lineSeqID'])) {//get line sequence
      $physLineSeqID = $data['lineSeqID'];
      $physLineSeq = new Sequence($physLineSeqID);
      if ($physLineSeq->hasError()) {
        array_push($errors,"creating sequence id = $physLineSeqID - ".join(",",$physLineSeq->getErrors()));
      } else {
        $lineSclIDs = $physLineSeq->getEntityIDs();
        $newLineSclIDs = null;
      }
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  $alteredTagIDs = array();
  $unChangedGraID2context = array();
  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else {
    if (isset($data['stateMap'])) {
      $stateGraMap = $data['stateMap'];
      $sylGraIDUpdates = array();
      $tokGraIDUpdates = array();
      $tokRecalcUpdates = array();
      foreach ($stateGraMap as $index => $tcmGraMap ) {
        $tcmState = $tcmGraMap[0];
        foreach ($tcmGraMap[1] as $index2 => $graInfo) {
          list($graID , $context) = $graInfo;
          //load grapheme
          $grapheme = new Grapheme($graID);
          //if TCM is different
          $graTCM = $grapheme->getTextCriticalMark() ? $grapheme->getTextCriticalMark():"S";
          if ( $graTCM != $tcmState) {
            //then update grapheme (cloning as need) and
            if ($grapheme->isReadonly()) {
              $grapheme = $grapheme->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $grapheme->setTextCriticalMark($tcmState);
            $grapheme->save();
            $newID = $grapheme->getID();
            if ($grapheme->hasError()) {
              array_push($errors,"error saving grapheme '".$grapheme->getValue()."' - ".$grapheme->getErrors(true));
            } else {
              //changed grapheme so need update lookups for graphemes
              preg_match("/seq\d+/",$context,$matches);
              if (count($matches)) {
                $changedSeqTag = $matches[0];
                $alteredTagIDs[$changedSeqTag] = $changedSeqTag;// signal recalc
              }
              $context = explode(",",$context);
              if ($graID != $grapheme->getID()) { //cloned
                $sclTag = array_pop($context);//WARNING context order dependency
                $sclID = substr($sclTag,3);
                //add tuple oldID, newID to update list for $sylGraIDUpdates[sclID]
                if (!array_key_exists($sclID, $sylGraIDUpdates)) {
                  $sylGraIDUpdates[$sclID] = array();
                }
                $sylGraIDUpdates[$sclID][$graID] = $newID;
                // and also to updTok[tokID] with context
                $tokTag = array_pop($context);
                $tokID = substr($tokTag,3);
                //add tuple oldID, newID to update list for $tokGraIDUpdates[sclID]
                if (!array_key_exists($tokID, $tokGraIDUpdates)) {
                  $tokGraIDUpdates[$tokID] = array();
                }
                $tokGraIDUpdates[$tokID][$graID] = array($newID,$context);
                //add ui data for new grapheme
                addNewEntityReturnData('gra',$grapheme);
              } else {//owned grapheme so just update client TCM and ensure that token is recalculated
                $sclTag = array_pop($context);
                $tokTag = array_pop($context);
                $tokID = substr($tokTag,3);
                //add to $tokRecalcUpdates update list for $tokGraIDUpdates[sclID]
                if (!array_key_exists($tokID, $tokRecalcUpdates)) {
                  $tokRecalcUpdates[$tokID] = $context;
                }
                //add ui data for grapheme update
                addUpdateEntityReturnData('gra',$graID,'txtcrit',$grapheme->getTextCriticalMark());
              }
            }
          } else {//store context in case partial syllable update
            $unChangedGraID2context[$graID] = $context;
          }
        }
      }
    }
  }
  $sylGraReplacements = array();
  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && $sylGraIDUpdates && count($sylGraIDUpdates)) {
    //for each $sylGraIDUpdates
    foreach ( $sylGraIDUpdates as $sclID => $graTuples ) {
      $syllable = new SyllableCluster($sclID);
      // clone if needed
      if ($syllable->isReadonly()) {
        $syllable = $syllable->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $graIDs = $syllable->getGraphemeIDs();
      $newGraIDs = array();
      foreach ($graIDs as $graID) {
        //replace all update grapheme ids
        if (array_key_exists($graID,$graTuples)) {
          array_push($newGraIDs,$graTuples[$graID]);
        } else if ($sclID != $syllable->getID()) {
          //clone those not already replaced clone if readonly.
          $grapheme = new Grapheme($graID);
          $grapheme = $grapheme->cloneEntity($defAttrIDs,$defVisIDs);
          $grapheme->save();
          addNewEntityReturnData('gra',$grapheme);
          $newID = $grapheme->getID();
          array_push($newGraIDs,$newID);
          //capture token update - could be split token
          if (array_key_exists($graID,$unChangedGraID2context)) {
            $context = explode(',',$unChangedGraID2context[$graID]);
            $sclTag = array_pop($context);
            $tokTag = array_pop($context);
            $tokID = substr($tokTag,3);
            if (!array_key_exists($tokID, $tokGraIDUpdates)) {
              $tokGraIDUpdates[$tokID] = array();
            }
            $tokGraIDUpdates[$tokID][$graID] = array($newID,$context);
          } else {
            $sylGraReplacements[$graID] = $newID;
          }
        } else {
          array_push($newGraIDs,$graID);
        }
      }
      $syllable->setGraphemeIDs($newGraIDs);
      $syllable->save();
      $newSclID = $syllable->getID();
      foreach ($newGraIDs as $newGraID) {
        addUpdateEntityReturnData('gra',$newGraID,'sclID',$newSclID);
      }
      if ($syllable->hasError()) {
        array_push($errors,"error saving syllable '".$syllable->getValue()."' - ".$syllable->getErrors(true));
      } else if ($sclID != $syllable->getID()) { //cloned
        //sclID in $lineSclIDs of the physLineSeq
        $sclIndex = array_search("scl:".$sclID,$lineSclIDs);
        array_splice($lineSclIDs,$sclIndex,1,$syllable->getGlobalID());
        $newLineSclIDs = $lineSclIDs;
        //add ui data for new syllable and remove old syllable
        addNewEntityReturnData('scl',$syllable);
        //addRemoveEntityReturnData('scl',$sclID);
      } else {
        addUpdateEntityReturnData('scl',$sclID,'graphemeIDs',$syllable->getGraphemeIDs());
      }
    }
    //set ids in physLineSeq and save
    if ($newLineSclIDs) {
      if ($physLineSeq->isReadonly()) {
        $oldPhysLineSeqID = $physLineSeq->getID();
        $oldPhysLineSeqGID = $physLineSeq->getGlobalID();
        $physLineSeq = $physLineSeq->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $physLineSeq->setEntityIDs($newLineSclIDs);
      $physLineSeq->save();
      if ($physLineSeq->hasError()) {
        array_push($errors,"error saving physical line sequence '".$physLineSeq->getValue()."' - ".$physLineSeq->getErrors(true));
      } else if ($oldPhysLineSeqID) { //cloned
        addNewEntityReturnData('seq',$physLineSeq);
        //signal a change for client side update
        $retVal['alteredPhysLineSeqID'] = $newPhysLineSeqID = $physLineSeq->getID();
        //update physical sequence
        if ($seqPhys->isReadonly()) {
          $oldPhysSeqID = $seqPhys->getID();
          $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
        }
        $physSeqEntityIDs = $seqPhys->getEntityIDs();
        $seqIndex = array_search($oldPhysLineSeqGID,$physSeqEntityIDs);
        array_splice($physSeqEntityIDs,$seqIndex,1,"seq:$newPhysLineSeqID");
        $seqPhys->setEntityIDs($physSeqEntityIDs);
        $seqPhys->save();
        if ($seqPhys->hasError()) {
          array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
        }else if ($oldPhysSeqID){// return insert data
          addNewEntityReturnData('seq',$seqPhys);
          //addRemoveEntityReturnData('seq',$oldPhysSeqID);
        }else {
          addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
        }
      } else { // just an update
        //changed components on a cached sequence so invalidate cache to recalc on next refresh
        addUpdateEntityReturnData('seq',$physLineSeq->getID(),'entityIDs',$physLineSeq->getEntityIDs());
        $retVal['alteredPhysLineSeqID'] = $physLineSeq->getID();
      }
    }
  }
  if (count($errors) == 0 && count($alteredTagIDs) > 0) {//review why altereTags signals Cache refresh of physical line
    invalidateCachedSeqEntities($physLineSeq->getID(),$edition->getID());
  }

  //update tokens as needed and propagate changes up containment hierarchy
  $cmpEntIDUpdates = array();
  $cmpRecalcUpdates = array();
  $seqEntIDUpdates = array();
  $textSeqRecalcUpdates = array();
  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && $tokGraIDUpdates && count($tokGraIDUpdates)) {
    //for each $tokGraIDUpdates
    foreach ( $tokGraIDUpdates as $tokID => $graInfos) {
      $token = new Token($tokID);
      if ( $token->isReadonly()) {
        $token = $token->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $graIDs = $token->getGraphemeIDs();
      $newGraIDs = array();
      foreach ($graIDs as $graID) {
        //replace all update grapheme ids
        if (array_key_exists($graID,$graInfos)) {
          array_push($newGraIDs,$graInfos[$graID][0]);
          $context = $graInfos[$graID][1];//WARNING possible issue for token with split syllable on each side
        } else if (array_key_exists($graID,$sylGraReplacements)) {
          array_push($newGraIDs,$sylGraReplacements[$graID]);
        } else {
          //keep any not replaced
          array_push($newGraIDs,$graID);
        }
      }
      $token->setGraphemeIDs($newGraIDs);
      $token->getValue(true);//cause recalc
      $token->save();
      if ($token->hasError()) {
        array_push($errors,"error saving token '".$token->getValue()."' - ".$token->getErrors(true));
      } else if ($tokID != $token->getID()) { //cloned
        //need to save tuple for replacement in containers entities
        $oldTokGid = "tok:$tokID";
        $newTokGid = $token->getGlobalID();
        $alteredTagIDs["tok$tokID"] = "tok".$token->getID();
        $entTag = array_pop($context);
        $prefix = substr($entTag,0,3);
        $entID = substr($entTag,3);
        if ($prefix == "cmp") {
          if (!array_key_exists($entID, $cmpEntIDUpdates)) {
            $cmpEntIDUpdates[$entID] = array();
          }
          $cmpEntIDUpdates[$entID][$oldTokGid] = array($newTokGid,$context);
        } else if ($prefix == "seq") {
          if (!array_key_exists($entID, $seqEntIDUpdates)) {
            $seqEntIDUpdates[$entID] = array();
          }
          $seqEntIDUpdates[$entID][$oldTokGid] = $newTokGid;
        } else { //error
          array_push($errors,"error processing token ($tokID) context found invalid prefix '$prefix' ");
        }
        //add ui data for new syllable and remove old syllable
        addNewEntityReturnData('tok',$token);
        //addRemoveEntityReturnData('tok',$tokID);
      } else {
        while ($entTag = array_pop($context)) {
          $prefix = substr($entTag,0,3);
          $entID = substr($entTag,3);
          if ($prefix == "cmp") {
            if (!array_key_exists($entID, $cmpRecalcUpdates)) {
              $cmpRecalcUpdates[$entID] = 1;
            }
          } else if ($prefix == "seq") {
            if (!array_key_exists($entID, $textSeqRecalcUpdates)) {
              $textSeqRecalcUpdates[$entID] = 1;
            }
          } else { //error
            array_push($errors,"error processing token ($tokID) context found invalid prefix '$prefix' ");
          }
        }
        addUpdateEntityReturnData('tok',$tokID,'graphemeIDs',$token->getGraphemeIDs());
        addUpdateEntityReturnData('tok',$tokID,'value',$token->getValue());
        addUpdateEntityReturnData('tok',$tokID,'transcr',$token->getTranscription());
        addUpdateEntityReturnData('tok',$tokID,'syllableClusterIDs',$token->getSyllableClusterIDs());
        $alteredTagIDs["tok$tokID"] = "tok".$token->getID();
      }
    }
  }

  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && $tokRecalcUpdates && count($tokRecalcUpdates)) {
    //for each $tokGraIDUpdates
    foreach ( $tokRecalcUpdates as $tokID => $context) {
      $token = new Token($tokID);
      $token->getValue(true);//cause recalc
      $token->save();
      if ($token->hasError()) {
        array_push($errors,"error saving token '".$token->getValue()."' - ".$token->getErrors(true));
      } else {
        while ($entTag = array_pop($context)) {
          $prefix = substr($entTag,0,3);
          $entID = substr($entTag,3);
          if ($prefix == "cmp") {
            if (!array_key_exists($entID, $cmpRecalcUpdates)) {
              $cmpRecalcUpdates[$entID] = 1;
            }
          } else if ($prefix == "seq") {
            if (!array_key_exists($entID, $textSeqRecalcUpdates)) {
              $textSeqRecalcUpdates[$entID] = 1;
            }
            invalidateWordLemma($token->getGlobalID());//top level token can be an attested form
          } else { //error
            array_push($errors,"error processing token ($tokID) context found invalid prefix '$prefix' ");
          }
        }
        addUpdateEntityReturnData('tok',$tokID,'value',$token->getValue());
        addUpdateEntityReturnData('tok',$tokID,'transcr',$token->getTranscription());
        $alteredTagIDs["tok$tokID"] = "tok".$token->getID();
      }
    }
  }

  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && count($cmpEntIDUpdates) > 0) {//compound updates
    foreach ( $cmpEntIDUpdates as $cmpID =>$tokInfo) {
      foreach ($tokInfo as $oldTokCmpGID => $newTokInfo) {
        $newTokCmpGID = $newTokInfo[0];
        $context = $newTokInfo[1];
        while (count($context) && $oldTokCmpGID != $newTokCmpGID) {
          $compound = new Compound($cmpID);
          $componentIDs = $compound->getComponentIDs();
          $tokCmpIndex = array_search($oldTokCmpGID,$componentIDs);
          array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID);
          $oldTokCmpGID = $compound->getGlobalID();
          $oldTokCmpID = $compound->getID();
          if ($compound->isReadonly()) {
            $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
          }
          // update compound container
          $compound->setComponentIDs($componentIDs);
          $compound->getValue(true);//cause recalc
          $compound->save();
          $newTokCmpGID = $compound->getGlobalID();
          if ($compound->hasError()) {
            array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
          }else if ($oldTokCmpGID != $newTokCmpGID){//cloned
            $alteredTagIDs["cmp$oldTokCmpID"] = "cmp".$compound->getID();
            $entTag = array_pop($context);
            $prefix = substr($entTag,0,3);
            $entID = substr($entTag,3);
            if ($prefix == "cmp") {//prep for container compound update
              $cmpID = $entID;
            } else if ($prefix == "seq") {
              if (!array_key_exists($entID, $seqEntIDUpdates)) {
                $seqEntIDUpdates[$entID] = array();
              }
              $seqEntIDUpdates[$entID][$oldTokCmpGID] = $newTokCmpGID;
            } else { //error
              array_push($errors,"error processing token ($tokID) context found invalid prefix '$prefix' ");
            }
            addNewEntityReturnData('cmp',$compound);
            //addRemoveEntityReturnData(substr($oldTokCmpGID,0,3),substr($oldTokCmpGID,4));
          } else { //done propagating replacements but not recalculations
            $entTag = array_pop($context);
            $prefix = substr($entTag,0,3);
            $entID = substr($entTag,3);
            if ($prefix == "cmp") {
              if (!array_key_exists($entID, $cmpRecalcUpdates)) {
//                $cmpEntIDUpdates[$entID] = $context;
                $cmpEntIDUpdates[$entID] = 1;
              }
            } else if ($prefix == "seq") {
              if (!array_key_exists($entID, $textSeqRecalcUpdates)) {
                $textSeqRecalcUpdates[$entID] = 1;
              }
            } else { //error
              array_push($errors,"error processing token ($tokID) context found invalid prefix '$prefix' ");
            }
            addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
            addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
            addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
            addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
          }
        }
        invalidateWordLemma($compound->getGlobalID());//top level compound can be an attested form
      }
    }
  }

  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && $cmpEntIDUpdates && count($cmpEntIDUpdates)) {
    //for each $tokGraIDUpdates
    foreach ( array_keys($cmpEntIDUpdates) as $cmpID) {
      $compound = new Compound($cmpID);
      $compound->getValue(true);//cause recalc
      $compound->save();
      if ($compound->hasError()) {
        array_push($errors,"error saving compound '".$compound->getValue()."' - ".$compound->getErrors(true));
      } else {
        addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
        addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
      }
    }
  }

  //text division updates
  $textSeqUpdates = array();
  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && count($seqEntIDUpdates) > 0) {//compound updates
    foreach ( $seqEntIDUpdates as $seqID =>$updateTuple) {
      $textDivSeq = new Sequence($seqID);
      $oldTxtDivSeqID = $textDivSeq->getID();
      $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
      $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
      foreach ($updateTuple as $oldTokCmpGID => $newTokCmpGID) {
        $tokCmpIndex = array_search($oldTokCmpGID,$textDivSeqEntityIDs);
        array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmpGID);
      }
      if ($textDivSeq->isReadonly()) {
        $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
      invalidateSequenceCache($textDivSeq,$edition->getID());
      //save text division sequence
      $textDivSeq->save();
      $newTxtDivSeqGID = $textDivSeq->getGlobalID();
      $newTxtDivSeqID = $textDivSeq->getID();
      if ($textDivSeq->hasError()) {
        array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
      }else if ($oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so it's new
        addNewEntityReturnData('seq',$textDivSeq);
        //addRemoveEntityReturnData('seq',$oldTxtDivSeqID);
        $textSeqUpdates[$oldTxtDivSeqGID] = $newTxtDivSeqGID;
        $alteredTagIDs["seq$oldTxtDivSeqID"] ="seq$newTxtDivSeqID";
      }else { // only updated
        if (!array_key_exists($textDivSeq->getID(), $textSeqRecalcUpdates)) {
          $textSeqRecalcUpdates[$textDivSeq->getID()] = 1;
        }
        addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
        $alteredTagIDs["seq$seqID"] ="seq$seqID";
      }
    }
  }

  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && count($textSeqRecalcUpdates) > 0) {//cache updates
    foreach ( array_keys($textSeqRecalcUpdates) as $seqID) {
      invalidateCachedSeqEntities($seqID,$edition->getID());
    }
  }

  // something changed need to update
  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 && count($textSeqUpdates) > 0) {//compound updates
    $textSeqEntityIDs = $seqText->getEntityIDs();
    foreach ( $textSeqUpdates as $oldTxtDivSeqGID =>$newTxtDivSeqGID) {
      $txtDivSeqIndex = array_search($oldTxtDivSeqGID,$textSeqEntityIDs);
      array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID);
    }
    //clone text sequence if not owned
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
      addNewEntityReturnData('seq',$seqText);
      //addRemoveEntityReturnData('seq',$oldTextSeqID);
    }else { // only updated
      addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
    }
  }
  // update edition if sequences cloned
  if (count($errors) > 0) {
    error_log("updTCM - errors : ".print_r($errors,true));
    return;
  } else if (count($errors) == 0 ) {
    //touch edition for synch code
    $edition->storeScratchProperty("lastModified",$edition->getModified());
    //get segIDs
    $edSeqIds = $edition->getSequenceIDs();
    //if phys changed update id
    if ($oldPhysSeqID) {
      $seqIDIndex = array_search($oldPhysSeqID,$edSeqIds);
      array_splice($edSeqIds,$seqIDIndex,1,$seqPhys->getID());
    }
    if ($oldTextSeqID) {
      $seqIDIndex = array_search($oldTextSeqID,$edSeqIds);
      array_splice($edSeqIds,$seqIDIndex,1,$seqText->getID());
    }
    //update edition seqIDs
    $edition->setSequenceIDs($edSeqIds);
    $edition->save();
    invalidateCachedEditionEntities($edition->getID());
    invalidateCachedEditionViewerHtml($edition->getID());
    invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
    if ($edition->hasError()) {
      array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
    }else{
      addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
    }
  }

  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($alteredTagIDs)) {
    $retVal['alteredTagIDs'] = $alteredTagIDs;
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
