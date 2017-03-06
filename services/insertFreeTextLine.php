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
  * insertFreeTextLine
  *
  * given an edition and insert position this service will create a 'FreeText' sequence and
  * add it to the TextPhysical at the position indicated. The service also has the posibility to
  * add freetext on creation.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Services
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
  $entities = array();
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
            $defAttrIDs = array($attrIDs[0]);//only select the first one assume it is primary
          }
        }
        //get default visibility
        if (!$defVisIDs || count($defVisIDs) == 0) {
          $visIDs = $edition->getVisibilityIDs();
          if ($visIDs && count($visIDs) > 0 ) {
            $defVisIDs = array($visIDs[0]);//only select the first one assume it is primary todo: check spec
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
          if (!$seqPhys && $seqType == "TextPhysical"){//term dependency
            $seqPhys = $edSequence;
          }
        }
      }
    } else {
      array_push($errors,"unaccessable edition");
    }
    $refLineSeqGID = null;
    if ( isset($data['refLineSeqGID'])) {//get reference physical Line sequence GID
      $refLineSeqGID = $data['refLineSeqGID'];
    }
    $initText = null;
    if ( isset($data['initText'])) {//get initial text for line
      $initText = $data['initText'];
    }
    $lnLabel = null;
    if ( isset($data['label'])) {//get label for Free Text sequence
      $lnLabel = $data['label'];
    }
    $insPos = null;
    if ( isset($data['insPos'])) {//get alignment info relative to reference GID or absolute
      $insPos = $data['insPos'];
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  if (count($errors) == 0 && isset($seqPhys)) {
    //find insert index to calc line label if needed
    $physSeqEntityGIDs = $seqPhys->getEntityIDs();
    //find index of refLineSeqGID in physical sequence
    if ($insPos == 'first') {
      $refSeqIndex = 0;
    } else if ($insPos == 'last') {
      $refSeqIndex = count($physSeqEntityGIDs);
    } else if (isset($refLineSeqGID)) {
      $refSeqIndex = array_search($refLineSeqGID,$physSeqEntityGIDs);
      if ($insPos == 'after') {// need to move to next GID for insert
        $refSeqIndex++;
      }
    }
    if (!isset($refSeqIndex)) {
      array_push($errors,"error unable to identify insert location for free text line sequence '");
    }
  }
  if (count($errors) == 0 && isset($refSeqIndex)) {
    //if no label create a default
    if (!$lnLabel) {
      $lnLabel = "NL".($refSeqIndex + 1);
    }
    // create new free text line seq
    $newFreeTextLine = new Sequence();
    $newFreeTextLine->setLabel($lnLabel);
    $newFreeTextLine->setTypeID(Entity::getIDofTermParentLabel('freetext-textphysical'));//term dependency
    $newFreeTextLine->setOwnerID($defOwnerID);
    $newFreeTextLine->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $newFreeTextLine->setAttributionIDs($defAttrIDs);
    }
    if ($initText){
      $newFreeTextLine->storeScratchProperty('freetext',$initText);
    } else {
      $newFreeTextLine->storeScratchProperty('freetext','…');//default place ellipses in new freetext line
    }
    $newFreeTextLine->save();
    if ($newFreeTextLine->hasError()) {
      array_push($errors,"error creating physical line sequence '".$newFreeTextLine->getValue()."' - ".$newFreeTextLine->getErrors(true));
    }
  }
  if (count($errors) == 0 && isset($newFreeTextLine) && isset($physSeqEntityGIDs)) {//insert into physycal sequence
    $retVal['freeTextLineSeqGID'] = $newFreeTextLineSeqGID = $newFreeTextLine->getGlobalID();
    addNewEntityReturnData('seq',$newFreeTextLine);
    //update text physical sequence
    if ($refSeqIndex == count($physSeqEntityGIDs)) { //append
      array_push($physSeqEntityGIDs,$newFreeTextLineSeqGID);
      $retVal['lastReplaced'] = true;// signal last line changed
    } else { //splice
      array_splice($physSeqEntityGIDs,$refSeqIndex,0,$newFreeTextLineSeqGID);
      if ($refSeqIndex == 0) {
        $retVal['firstReplaced'] = true;// signal first line changed
      }
    }
    $retVal['newLineOrd'] = $refSeqIndex + 1;
    if ($seqPhys->isReadonly()) {
      $oldPhysSeqID = $seqPhys->getID();
      $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
    }
    $seqPhys->setEntityIDs($physSeqEntityGIDs);
    $seqPhys->save();
    if ($seqPhys->hasError()) {
      array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
    }else if ($oldPhysSeqID){// return insert data
      addNewEntityReturnData('seq',$seqPhys);
      //addRemoveEntityReturnData('seq',$oldPhysSeqID);
    }else {
      addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
    }
  }
  // update edition if text physical sequence cloned
  if (count($errors) == 0 && isset($oldPhysSeqID)) {
    //get segIDs
    $edSeqIds = $edition->getSequenceIDs();
    $seqIDIndex = array_search($oldPhysSeqID,$edSeqIds);
    array_splice($edSeqIds,$seqIDIndex,1,$seqPhys->getID());
    $retVal['newPhysSeqID'] = $seqPhys->getID();
    //update edition seqIDs
    $edition->setSequenceIDs($edSeqIds);
    $edition->save();
    if ($edition->hasError()) {
      array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
    }else{
      //array_push($updated,$edition);//********** updated edn
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
