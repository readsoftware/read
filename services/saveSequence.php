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
* saveSequence
*
* saves Sequency entity data
*
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
require_once (dirname(__FILE__) . '/../model/entities/Sequence.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $edition = null;
  $ednID = null;
  if ( isset($data['ednID'])) {//get ednID for sequence attach or detach
    $ednID = $data['ednID'];
    $edition = new Edition($ednID);
    if ($edition->hasError()) {
      array_push($errors,"error loading edition id $seqID - ".join(",",$edition->getErrors()));
//    } else if ($edition->isReadonly()) {
//      array_push($errors,"error edition '".$edition->getDescription()."' - is readonly");
    } else {
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
    }
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
  $isSeqMove = false;
  if ( isset($data['isSeqMove'])) {//check if sequence structural move to determine if the sequence is injected into the edition for temp storeage
    $isSeqMove = true;
  }
  if (count($errors) == 0) {
    $seqID = null;
    $seqGID = null;
    if ( isset($data['seqID'])) {//get existing sequence
      $seqID = $data['seqID'];
      $sequence = new Sequence($seqID);
      if ($sequence->hasError()) {
        array_push($errors,"error loading sequence id $seqID - ".join(",",$sequence->getErrors()));
      } else if ($sequence->isReadonly()) {
        array_push($errors,"error sequence id $seqID - is readonly");
      } else if ($sequence->isMarkedDelete()) {
        array_push($errors,"error sequence id $seqID - is marked for delete");
      } else {
        $seqGID = $sequence->getGlobalID();
      }
    } else if (!$edition){
        array_push($errors,"error need to specify edition when creating a new sequence");
    } else {
      $sequence = new Sequence();
      $sequence->setOwnerID($defOwnerID);
      $sequence->setVisibilityIDs($defVisIDs);
      if ($defAttrIDs){
        $sequence->setAttributionIDs($defAttrIDs);
      }
      $sequence->save();
      if ($sequence->hasError()) {
        array_push($errors,"error creating new sequence - ".join(",",$sequence->getErrors()));
      } else {
        $seqGID = $sequence->getGlobalID();
      }
    }
    $label = null;
    if ( isset($data['label'])) {//get label for sequence
      $label = $data['label'];
    }
    $superscript = null;
    if ( isset($data['superscript'])) {//get superscript for sequence
      $superscript = $data['superscript'];
    }
    $typeID = null;
    if ( isset($data['typeID'])) {//get typeID for sequence
      $typeID = $data['typeID'];
    }
    $entityIDs = null;
    if ( isset($data['entityIDs'])) {//get entityIDs for sequence
      if ($data['entityIDs'] && $data['entityIDs'] != '') {
        $entityIDs = $data['entityIDs'];
      } else {
        $entityIDs = array();
      }
    }
    $removeEntityGID = null;
    if ( isset($data['removeEntityGID'])) {//get removeEntityGID for sequence
      $removeEntityGID = $data['removeEntityGID'];
    }
    $addEntityGID = null;
    if ( isset($data['addEntityGID'])) {//get addEntityGID for sequence
      $addEntityGID = $data['addEntityGID'];
    }
    $addSubSeqTypeID = null;
    if ( isset($data['addSubSeqTypeID'])) {//get addSubSeqTypeID for sub sequence
      $addSubSeqTypeID = $data['addSubSeqTypeID'];
    }
  }
}
$isSequenceUpdate = ($seqID && $sequence);
if (count($errors) == 0) {
  if (!$sequence || ($label === null && $superscript === null && !$typeID &&
                   !isset($entityIDs) && !$removeEntityGID && !$addEntityGID && !$addSubSeqTypeID)) {
    array_push($errors,"insufficient data to save sequence");
  } else {//use data to update sequence and save
    if ($label !== null) {
      $sequence->setLabel($label);
      if ($isSequenceUpdate) {
        addUpdateEntityReturnData("seq",$seqID,'value', $sequence->getLabel());
        addUpdateEntityReturnData("seq",$seqID,'label', $sequence->getLabel());
      }
    }
    if ($superscript !== null) {
      $sequence->setSuperScript($superscript);
      if ($isSequenceUpdate) {
        addUpdateEntityReturnData("seq",$seqID,'sup', $sequence->getSuperScript());
      }
      //todo check if superscript can impact cache
    }
    if ($typeID) {
      $sequence->setTypeID($typeID);
      if ($isSequenceUpdate) {
        addUpdateEntityReturnData("seq",$seqID,'typeID', $sequence->getTypeID());
      }
    }
    if (isset($entityIDs)) {
      $sequence->setEntityIDs($entityIDs);
      if ($isSequenceUpdate) {
        addUpdateEntityReturnData("seq",$seqID,'entityIDs', $sequence->getEntityIDs());
      }
    }
    if ($removeEntityGID) {
      $entityIDs = $sequence->getEntityIDs();
      $entGIDIndex = array_search($removeEntityGID,$entityIDs);
      array_splice($entityIDs,$entGIDIndex,1);
      $sequence->setEntityIDs($entityIDs);
      if ($isSequenceUpdate) {
        addUpdateEntityReturnData("seq",$seqID,'entityIDs', $sequence->getEntityIDs());
      }
      if ($edition && strpos($removeEntityGID,'seq')!== false && !$isSeqMove){//removing a sequence so if substructure add back to edition sequenceIDs
        $removedSequence = new Sequence(substr($removeEntityGID,4));
        if (count($removedSequence->getEntityIDs())) {
          $seqIDs = array_unique($edition->getSequenceIDs());
          $removeSeqID = substr($removeEntityGID,4);
          array_push($seqIDs,$removeSeqID);
          $edition->setSequenceIDs($seqIDs);
          $edition->save();
          if ($edition->hasError()) {
            array_push($errors,"error updating edition '".$edition->getDescription()."' - ".$edition->getErrors(true));
          }else {
            addUpdateEntityReturnData("edn",$edition->getID(),'seqIDs', $edition->getSequenceIDs());
          }
        }
      }
    } else if ($addSubSeqTypeID && $isSequenceUpdate) { // use $seqID to indicate existing parent sequence
      //todo check that type is a sequence type and a sub type of parent sequence
      if (!isValidContainment($sequence->getTypeID(),$addSubSeqTypeID)) {
        array_push($errors,"error creating new subsequence - types are not compatible");
      } else {
        //create new subsequence
        $subSequence = new Sequence();
        $subSequence->setOwnerID($defOwnerID);
        $subSequence->setVisibilityIDs($defVisIDs);
        if ($defAttrIDs){
          $subSequence->setAttributionIDs($defAttrIDs);
        }
        $subSequence->setTypeID($addSubSeqTypeID);
        $subSequence->save();
        if ($subSequence->hasError()) {
          array_push($errors,"error creating new subsequence - ".join(",",$subSequence->getErrors()));
        } else {
          addNewEntityReturnData('seq',$subSequence);
          $subSequenceGID = $subSequence->getGlobalID();
          $entityIDs = $sequence->getEntityIDs();
          if ($entityIDs && count($entityIDs)>0) {
            array_push($entityIDs,$subSequenceGID);
          } else {
            $entityIDs = array($subSequenceGID);
          }
          $sequence->setEntityIDs($entityIDs);
          addUpdateEntityReturnData("seq",$seqID,'entityIDs', $sequence->getEntityIDs());
        }
      }
    } else if ($addEntityGID && $edition) {
      $skip = false;
      if (strpos($addEntityGID,'seq')!== false){ //adding sequence to sequence check nesting level and loops
        $skip = checkInContainment($addEntityGID,$seqGID,$edition->getSequenceIDs(),8);
        array_push($warnings,"warning reference loop detected or level constraint (8) exceeded, cancel component add of $addEntityGID to sequence '".$sequence->getLabel());
      }
      if (!$skip) {
        $entityIDs = $sequence->getEntityIDs();
        if ($entityIDs && count($entityIDs)>0) {
          //check if already in entityIDs
          if (!in_array($addEntityGID,$entityIDs)){
            array_push($entityIDs,$addEntityGID);
          }
        } else {
          $entityIDs = array($addEntityGID);
        }
        $sequence->setEntityIDs($entityIDs);
        if ($isSequenceUpdate) {
          addUpdateEntityReturnData("seq",$seqID,'entityIDs', $sequence->getEntityIDs());
        }
        if ($edition && strpos($addEntityGID,'seq')!== false){//adding a sequence so check for removal from edition sequenceIDs
          $seqIDs = array_unique($edition->getSequenceIDs());
          $addSeqID = substr($addEntityGID,4);
          $segIDIndex = array_search($addSeqID,$seqIDs);
          if ($segIDIndex !== false) {
            array_splice($seqIDs,$segIDIndex,1);
            $edition->setSequenceIDs($seqIDs);
            $edition->save();
            if ($edition->hasError()) {
              array_push($errors,"error updating edition '".$edition->getDescription()."' - ".$edition->getErrors(true));
            }else {
              addUpdateEntityReturnData("edn",$edition->getID(),'seqIDs', $edition->getSequenceIDs());
            }
          }
        }
      }
    }
    $sequence->save();
    if ($sequence->hasError()) {
      array_push($errors,"error updating sequence '".$sequence->getLabel()."' - ".$sequence->getErrors(true));
    }else {
      if (!$isSequenceUpdate){ //adding a new sequence
        addNewEntityReturnData('seq',$sequence);
        if ($edition){//new sequence so attach it to edition sequenceIDs
          //first check if this is a text reference type sequence
          $textRefTypeID = ENTITY::getIDofTermParentLabel("textreferences-sequencetype");//warning!!!! term dependency
          if (isSubTerm($textRefTypeID,$sequence->getTypeID())) {//text reference seq created need to add it to container
            //check if container exist
            $edSeqs = $edition->getSequences(true);
            $textReferenceSeq = null;
            foreach ($edSeqs as $edSequence) {
              $seqType = $edSequence->getType();
              if (!$textReferenceSeq && $seqType == "TextReferences"){//warning!!!! term dependency
                $textReferenceSeq = $edSequence;
                break;
              }
            }
            if (!$textReferenceSeq) {
              $textReferenceSeq = new Sequence();
              $textReferenceSeq->setOwnerID($defOwnerID);
              $textReferenceSeq->setVisibilityIDs($defVisIDs);
              if ($defAttrIDs){
                $textReferenceSeq->setAttributionIDs($defAttrIDs);
              }
              $textReferenceSeq->setTypeID($textRefTypeID);
              $textReferenceSeq->setEntityIDs(array($sequence->getGlobalID()));
              $textReferenceSeq->save();
              if ($textReferenceSeq->hasError()) {
                array_push($errors,"error creating new textReference container - ".join(",",$textReferenceSeq->getErrors()));
              } else {
                addNewEntityReturnData('seq',$textReferenceSeq);
                $seqID = $textReferenceSeq->getID(); //need to link container into edition
              }
            } else {
              $seqGID = $sequence->getGlobalID();
              $entityIDs = $textReferenceSeq->getEntityIDs();
              if ($entityIDs && count($entityIDs)>0) {
                //check if already in entityIDs
                if (!in_array($seqGID,$entityIDs)){
                  array_push($entityIDs,$seqGID);
                }
              } else {
                $entityIDs = array($seqGID);
              }
              $textReferenceSeq->setEntityIDs($entityIDs);
              $textReferenceSeq->save();
              if ($textReferenceSeq->hasError()) {
                array_push($errors,"error updating textReference container - ".join(",",$textReferenceSeq->getErrors()));
              } else {
                addUpdateEntityReturnData("seq",$textReferenceSeq->getID(),'entityIDs', $textReferenceSeq->getEntityIDs());
              }
            }
          } else { // not text reference and new so link it into the edition for now
            $seqID = $sequence->getID();
          }
          if ($seqID) {
            $seqIDs = $edition->getSequenceIDs();
            array_push($seqIDs,$seqID);
            $edition->setSequenceIDs($seqIDs);
            $edition->save();
            if ($edition->hasError()) {
              array_push($errors,"error updating edition '".$edition->getDescription()."' - ".$edition->getErrors(true));
            }else {
              addUpdateEntityReturnData("edn",$edition->getID(),'seqIDs', $edition->getSequenceIDs());
            }
          }
        }
      } else if ($sequence->getType()=='LinePhysical') { // warning!!! Term Dependency
        $updatesWords = updateWordLocationForLine($sequence->getID());
        if ($updatesWords && count($updatesWords) > 0) {
          foreach ($updatesWords as $word) {
            addUpdateEntityReturnData($word->getEntityTypeCode(), $word->getID(),'locLabel', $word->getLocation());
          }
        }
      }
    }
    invalidateCachedSeqEntities($sequence->getID(),$edition->getID());
    //if linephysical sequence type then find the corresponding textDivision that cache
    invalidateSequenceCache($sequence,$edition->getID());
    invalidateParentCache($sequence->getGlobalID(),$edition->getSequenceIDs(),$edition->getID());
  }
}
if (count($errors) == 0 && $edition) {
  //touch edition for synch code
  $edition->storeScratchProperty("lastModified",$edition->getModified());
  $edition->setStatus('changed');
  $edition->save();
  invalidateCachedEditionEntities($edition->getID());
  invalidateCachedEditionViewerInfo($edition);
  invalidateCachedEditionViewerHtml($edition->getID());
  invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
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

function checkInContainment($searchGID,$containGID,$ednSeqIDs,$level=4) {
  $containers = new Sequences("'$containGID' = ANY(seq_entity_ids)",null,null,null);
  if ($containers && $containers->getCount()){
    foreach($containers as $seqContainer){
      if ( $searchGID == $seqContainer->getGlobalID()) {
        return true;
      }
      if (!in_array($seqContainer->getID(),$ednSeqIDs) && //stop check at edition
          ($level - 1 > 0) && //reached iteration limit
          checkInContainment($searchGID,$seqContainer->getGlobalID(),$ednSeqIDs,$level-1)) {
        return true;
      }
    }
  }
  return false;
}
?>
