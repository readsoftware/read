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
require_once (dirname(__FILE__) . '/../common/php/utils.php');
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
$tokenSplitRequired = false;
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
      $ednOwnerID = $edition->getOwnerID();
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
}

$healthLogging = false;
if ( isset($data['hlthLog'])) {//check for health logging
  $healthLogging = true;
}

//getEntity IDs for switchFrom and switchTo
if (count($errors) == 0) {
  $switchFromEntTag = null;
  $switchToEntTag = null;
  $switchFromSclIDs = null;
  $switchToSclIDs = null;
  if (isset($data['entTag'])) {//get switchFrom entity's tag
    $switchFromEntTag = $data['entTag'];
    $prefix = substr($switchFromEntTag,0,3);
    $entID = substr($switchFromEntTag,3);
    $switchFromSclIDs = getEntitySclIDs($prefix,$entID);
  } else {
    array_push($errors,"missing parameter entity Tag");
  }
  if (isset($data['newEntTag'])) {//get switchTo entity's tag
    $switchToEntTag = $data['newEntTag'];
    $prefix = substr($switchToEntTag,0,3);
    $entID = substr($switchToEntTag,3);
    $switchToSclIDs = getEntitySclIDs($prefix,$entID);
    $switchToSclIDs = explode(',','scl:'.join(',scl:',$switchToSclIDs));
  } else {
    array_push($errors,"missing parameter new entity Tag");
  }
  if (count($switchFromSclIDs) != count($switchToSclIDs)) {
    array_push($errors,"cannot switch entites of different lengths");
  }
}

//find physical line sequences to adjust
if (count($errors) == 0 && count($switchFromSclIDs)>0 && count($switchToSclIDs)>0 ) {
  $startSclGID = 'scl:'.$switchFromSclIDs[0];
  $endSclGID = 'scl:'.$switchFromSclIDs[count($switchFromSclIDs)-1];
  $physLine1Seq = null;
  $physLine2Seq = null;
  $physLine1SclGIDs = null;
  $physLine2SclGIDs = null;
  $oldPhysLine1GID = null;
  $oldPhysLine2GID = null;
  foreach($seqPhys->getEntities(true) as $edPhysLineSeq) {
    if ($edPhysLineSeq->getType() != "LinePhysical") {
      continue;
    }
    $physLineSclGIDs = $edPhysLineSeq->getEntityIDs();
    if (!$physLine1Seq && in_array($startSclGID,$physLineSclGIDs)) {
      $physLine1Seq = $edPhysLineSeq;
      $physLine1SclGIDs = $physLineSclGIDs;
    }
    if ($physLine1Seq && in_array($endSclGID,$physLineSclGIDs)) {
      if ($physLine1Seq != $edPhysLineSeq) {
        $physLine2Seq = $edPhysLineSeq;
        $physLine2SclGIDs = $physLineSclGIDs;
      }
      break;
    }
  }
  if ($physLine1Seq) {
    //find overlap of entity sclIDs with physical line ids,
    //cross line token split will require updating 2 physical lines
    $startIndex = array_search($startSclGID,$physLine1SclGIDs);
    $replaceLength = count($physLine1SclGIDs) - $startIndex;
    $replaceLength = min($replaceLength,count($switchToSclIDs));
    $replaceSclIDs = array_splice($switchToSclIDs,0,$replaceLength);
    array_splice($physLine1SclGIDs,$startIndex,$replaceLength,$replaceSclIDs);
    //todo add code to check replacement is not the same as ids in phsyline, if so skip replace for this line
    //use owned or clone
    if ($physLine1Seq->isReadonly()) {
      $oldPhysLine1GID = $physLine1Seq->getGlobalID();
      $physLine1Seq = $physLine1Seq->cloneEntity($defAttrIDs,$defVisIDs);
    }
    $physLine1Seq->setEntityIDs($physLine1SclGIDs);
    $physLine1Seq->save();
    if ($physLine1Seq->hasError()) {
      array_push($errors,"error updating physical line sequence '".$physLine1Seq->getLabel()."' - ".$physLine1Seq->getErrors(true));
    }else if ($oldPhysLine1GID){//cloned so it's new
      addNewEntityReturnData('seq',$physLine1Seq);
      $retVal['newPhysLine1SeqID'] = $physLine1Seq->getID();
    }else { // only updated
      addUpdateEntityReturnData('seq',$physLine1Seq->getID(),'entityIDs',$physLine1Seq->getEntityIDs());
    }
    if (count($switchToSclIDs)) {//cross line case
      //find overlap of entity sclIDs with physical line ids, cross line token split will require updating 2 physical lines
      $endIndex = array_search($endSclGID,$physLine2SclGIDs);
      $replaceLength = count($switchToSclIDs);//
      //todo add code to check replacement is not the same as ids in phsyline, if so skip replace for this line
      array_splice($physLine2SclGIDs,0,$replaceLength,$switchToSclIDs);
      //use owned or clone
      if ($physLine2Seq->isReadonly()) {
        $oldPhysLine2GID = $physLine2Seq->getGlobalID();
        $physLine2Seq = $physLine2Seq->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $physLine2Seq->setEntityIDs($physLine2SclGIDs);
      $physLine2Seq->save();
      if ($physLine2Seq->hasError()) {
        array_push($errors,"error updating physical line sequence '".$physLine2Seq->getLabel()."' - ".$physLine2Seq->getErrors(true));
      }else if ($oldPhysLine2GID){//cloned so it's new
        addNewEntityReturnData('seq',$physLine2Seq);
        $retVal['newPhysLine1SeqID'] = $physLine2Seq->getID();
      }else { // only updated
        addUpdateEntityReturnData('seq',$physLine2Seq->getID(),'entityIDs',$physLine2Seq->getEntityIDs());
      }
    }
  } else {
    array_push($errors,"unable to find physical line sequence for entity");
  }
} else {
  array_push($errors,"unable to get syllable foreign keys to switch Entity");
}

//update Physical Line container sequence
if (count($errors) == 0 &&
($oldPhysLine1GID || $oldPhysLine2GID)) {
  if ($seqPhys->isReadonly()) {//clone physicalText sequence if not owned
    $oldPhysSeqID = $seqPhys->getID();
    $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
  }
  // update container if cloned
  $physSeqEntityIDs = $seqPhys->getEntityIDs();
  if ($oldPhysLine1GID) {
    $seqIndex = array_search($oldPhysLine1GID,$physSeqEntityIDs);
    array_splice($physSeqEntityIDs,$seqIndex,1,$physLine1Seq->getGlobalID());
  }
  if ($oldPhysLine2GID) {
    $seqIndex = array_search($oldPhysLine2GID,$physSeqEntityIDs);
    array_splice($physSeqEntityIDs,$seqIndex,1,$physLine2Seq->getGlobalID());
  }
  $seqPhys->setEntityIDs($physSeqEntityIDs);
  $seqPhys->save();
  if ($seqPhys->hasError()) {
    array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
  }else if ($oldPhysSeqID){
    addNewEntityReturnData('seq',$seqPhys);
  }else {
    addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
  }
}

//find text division the switch from entity is contained in
$oldTxtDivSeqGID = null;
$ctxCmpIDs = null;
$textDivSeq = null;
$oldTokCmpGID = null;
$newTokCmpGID = null;
if (count($errors) == 0 &&
  ($switchFromEntTag || $switchToEntTag)) {
  $oldTokCmpGID = substr($switchFromEntTag,0,3).':'.substr($switchFromEntTag,3);
  $newTokCmpGID = substr($switchToEntTag,0,3).':'.substr($switchToEntTag,3);
  //search this editions set of text divisions the old EntGID
  foreach($seqText->getEntities(true) as $edTextDivSeq) {
    $textDivSeqEntityIDs = $edTextDivSeq->getEntityIDs();
    foreach($textDivSeqEntityIDs as $gid) {
      if ($ctxCmpIDs = getCmpContext($gid,$oldTokCmpGID)) { // added for bug #254 wasn't checking within compounds
        if ($ctxCmpIDs === true) {// direct match
          $ctxCmpIDs = null; //signal no compound heirarch to update
        }
        $textDivSeq = $edTextDivSeq;
        break;
      }
    }
    if ($textDivSeq) {//found so break out of the outer loop
      break;
    }
  }
}
// if switch entity in compounds then update compound context heirarchy as needed
if (count($errors) == 0 && $ctxCmpIDs && count($ctxCmpIDs) && $oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID) {
  while (count($ctxCmpIDs) && $oldTokCmpGID != $newTokCmpGID) {
    $cmpID = array_shift($ctxCmpIDs);
    $compound = new Compound($cmpID);
    if ($compound->hasError()) {
      array_push($errors,"error loading compound id = $cmpID during switch entity");
    } else {
      $componentIDs = $compound->getComponentIDs();
      $tokCmpIndex = array_search($oldTokCmpGID,$componentIDs);
      array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID);
      $oldTokCmpGID = $compound->getGlobalID();
      if ($compound->isReadonly()) {
        $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
      }
      // update compound container
      $compound->setComponentIDs($componentIDs);
      $compound->getValue(true);//cause recalc
      $compound->save();
      $newTokCmpGID = $compound->getGlobalID();
      if ($compound->hasError()) {
        array_push($errors,"error updating compound '".$compound->getValue()."' - ".$compound->getErrors(true));
      }else if ($oldTokCmpGID != $newTokCmpGID){
        addNewEntityReturnData('cmp',$compound);
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

// update text division sequence
if (count($errors) == 0 && $textDivSeq && $oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID) {
  if ($textDivSeq->isReadonly()) {
    $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
    $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
  }
  // update text dividion components ids by replacing $oldTokCmpGID with $newTokCmpGID
  $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
  $tokCmpIndex = array_search($oldTokCmpGID,$textDivSeqEntityIDs);
  array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmpGID);
  $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
  //save text division sequence
  $textDivSeq->save();
  $newTxtDivSeqGID = $textDivSeq->getGlobalID();
  if ($textDivSeq->hasError()) {
    array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
  }else if ($oldTxtDivSeqGID){//cloned so it's new
    addNewEntityReturnData('seq',$textDivSeq);
    $retVal['newTextDivSeqGID'] = $newTxtDivSeqGID;
  }else { // only updated
    invalidateCachedSeqEntities($textDivSeq->getID(),$edition->getID());
    addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
  }
}
if (count($errors) == 0) {
  $oldEntity = EntityFactory::createEntityFromGlobalID($switchFromEntTag);
  if ($oldEntity && !$oldEntity->isReadonly()) {
    $oldEntity->markForDelete();
    addRemoveEntitySwitchHashReturnData($switchFromEntTag);
  }
}

if (count($errors) == 0 && $oldTxtDivSeqGID && $oldTxtDivSeqGID != $newTxtDivSeqGID) {
  //clone text sequence if not owned
  if ($seqText->isReadonly()) {
    $oldTextSeqID = $seqText->getID();
    $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
  }
  $textSeqEntityIDs = $seqText->getEntityIDs();
  $txtDivSeqIndex = array_search($oldTxtDivSeqGID,$textSeqEntityIDs);
  array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID);
  $seqText->setEntityIDs($textSeqEntityIDs);
  //save text sequence
  $seqText->save();
  if ($seqText->hasError()) {
    array_push($errors,"error updating text sequence '".$seqText->getLabel()."' - ".$seqText->getErrors(true));
  }else if ($oldTextSeqID){//cloned so it's new
    addNewEntityReturnData('seq',$seqText);
  }else { // only updated
    addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
  }
}

// update edition if sequences cloned
if (count($errors) == 0 ) {
  //touch edition for synch code
  $edition->storeScratchProperty("lastModified",$edition->getModified());
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
