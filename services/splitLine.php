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
  * splitLine
  *
  * splits a physical line before or after the syllableClusters leaving syllables before in the current sequence
  * and placing the syllables after in a new physical line sequence added to the owned edition
  * data =
  * {
      ednID: 9,
      context: "seq66 tok5 scl35 seg24",
      sclID: 35,
      seqID: 75,
      splitAfter: false
  * }
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
    $lineSeqID = null;
    if ( isset($data['seqID'])) {//get reference physical Line sequence ID
      $lineSeqID = $data['seqID'];
    }
    $refSclGID = null;
    if ( isset($data['sclID'])) {//get id of Syllable for split
      $refSclGID = 'scl:'.$data['sclID'];
    }
    $lnLabel = null;
    if ( isset($data['label'])) {//get label for new physical line sequence
      $lnLabel = $data['label'];
    }
    $splitAfter = false;
    if ( isset($data['splitAfter'])) {//split side infor
      $splitAfter = $data['splitAfter'] == "true";
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  if (count($errors) == 0 && isset($seqText) && isset($seqPhys) && $lineSeqID) {
    $physLine = new Sequence($lineSeqID);
    if ($physLine->hasError()) {
      array_push($errors,"error loading physical line sequence '".$physLine->getValue()."' - ".$physLine->getErrors(true));
    } else if ($refSclGID) {//split physycal sequence
      $physLineSclGIDs = $physLine->getEntityIDs();
      if (!$lnLabel) {
        $physLineSeqGIDs = $seqPhys->getEntityIDs();
        $refSeqIndex = array_search('seq:'.$lineSeqID,$physLineSeqGIDs);
        $lnLabel = "NL".(1+$refSeqIndex);
      }
      //find index of refSclGID in physical line sequence
      $refSclIndex = array_search($refSclGID,$physLineSclGIDs);
      if ($splitAfter) {// need to move to next scl GID for insert
        $refSclIndex++;
      }
      //split syllables
      $newLineSclGIDs = array_splice($physLineSclGIDs,$refSclIndex);
      if (count($newLineSclGIDs) == 0 || count($physLineSclGIDs) == 0) {
        array_push($errors,"error split has empty line");
      } else {
        //if no label create a default
        $oldPhysLineSeqGID = $physLine->getGlobalID();
        if ( $physLine->isReadonly()) {
          $physLine = $physLine->cloneEntity($defAttrIDs,$defVisIDs);
        }
        $physLine->setEntityIDs($physLineSclGIDs);
        $physLine->save();
        $physLineSeqGID = $physLine->getGlobalID();
        if ($physLine->hasError()) {
          array_push($errors,"error updating physical line sequence '".$physLine->getValue()."' - ".$physLine->getErrors(true));
        } else {//add new physycal sequence
          $retVal['physLineSeqID'] = $physLine->getID();
          // create new physical line seq
          $newPhysLine = new Sequence();
          $newPhysLine->setEntityIDs($newLineSclGIDs);
          $newPhysLine->setLabel($lnLabel);
          $newPhysLine->setTypeID(Entity::getIDofTermParentLabel("linephysical-textphysical"));//term dependency
          $newPhysLine->setOwnerID($defOwnerID);
          $newPhysLine->setVisibilityIDs($defVisIDs);
          if ($defAttrIDs){
            $newPhysLine->setAttributionIDs($defAttrIDs);
          }
          $newPhysLine->save();
          if ($newPhysLine->hasError()) {
            array_push($errors,"error creating new physical line sequence '".$newPhysLine->getValue()."' - ".$newPhysLine->getErrors(true));
          } else {//update physycal sequence with
            $newPhysLineSeqGID = $newPhysLine->getGlobalID();
            addNewEntityReturnData('seq',$newPhysLine);
            $retVal['newPhysLineSeqID'] = $newPhysLine->getID();
            $physSeqEntityIDs = $seqPhys->getEntityIDs();
            $oldPhysIndex = array_search($oldPhysLineSeqGID,$physSeqEntityIDs);
            if ($oldPhysLineSeqGID == $physLineSeqGID) {//we owned this so update
              addUpdateEntityReturnData('seq',$physLine->getID(),'entityIDs',$physLine->getEntityIDs());
              array_splice($physSeqEntityIDs,$oldPhysIndex+1,0,$newPhysLineSeqGID);
            } else { //cloned physical so add both GIDs
              addNewEntityReturnData('seq',$physLine);
              array_splice($physSeqEntityIDs,$oldPhysIndex,1,array($physLineSeqGID,$newPhysLineSeqGID));
            }
            //update physical sequence
            $oldPhysSeqID = $seqPhys->getID();
            if ($seqPhys->isReadonly()) {
              $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $seqPhys->setEntityIDs($physSeqEntityIDs);
            $seqPhys->save();
            $newPhysSeqID = $seqPhys->getID();
            if ($seqPhys->hasError()) {
              array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
            }else if ($oldPhysSeqID != $newPhysSeqID){ //cloned so update client with new entity
              addNewEntityReturnData('seq',$seqPhys);
              $retVal['newPhysSeqID'] = $seqPhys->getID();
            }else {
              addUpdateEntityReturnData('seq',$newPhysSeqID,'entityIDs',$seqPhys->getEntityIDs());
            }
          }
        }
      }
    }
    // update edition if sequences cloned
    if (count($errors) == 0 && @$oldPhysSeqID && $oldPhysSeqID != $newPhysSeqID) {
      //get segIDs
      $edSeqIds = $edition->getSequenceIDs();
      $seqIDIndex = array_search($oldPhysSeqID,$edSeqIds);
      array_splice($edSeqIds,$seqIDIndex,1,$newPhysSeqID);
     //update edition seqIDs
      $edition->setSequenceIDs($edSeqIds);
      $edition->save();
      if ($edition->hasError()) {
        array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
      }else{
        addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
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
