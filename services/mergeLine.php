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
  * mergeLine
  *
    * merges a physical line's syllableClusters with the next or previous physical line of an owned edition
  *
  * {
      ednID: 9,
      context: "seq66 tok5 scl35 seg24",
      seqID: 75,
      mergePrevious: false
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
        $oldPhysSeqID = null;
        $edSeqs = $edition->getSequences(true);
        foreach ($edSeqs as $edSequence) {
          $seqType = $edSequence->getType();
          if (!$seqPhys && $seqType == "TextPhysical"){
            $seqPhys = $edSequence;
          }
        }
      }
    } else {
      array_push($errors,"unaccessable edition");
    }
    $line1SeqID = null;
    if ( isset($data['line1'])) {//get reference physical Line sequence ID
      $line1SeqID = $data['line1'];
    }
    $line2SeqID = null;
    if ( isset($data['line2'])) {//get reference physical Line sequence ID
      $line2SeqID = $data['line2'];
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
    //todo validate the line1 seqID is followed by line2 seqID, check if this is a requirement.
  }
  if (count($errors) == 0 && isset($seqPhys) && $line1SeqID && $line2SeqID) {
    $physLine = new Sequence($line1SeqID);
    $mergeLine = new Sequence($line2SeqID);
    if ($physLine->hasError()) {
      array_push($errors,"error loading physical line sequence '".$physLine->getValue()."' - ".$physLine->getErrors(true));
    } else  if ($mergeLine->hasError()) {
      array_push($errors,"error loading merge line sequence '".$mergeLine->getValue()."' - ".$mergeLine->getErrors(true));
    } else {//add merge physycal line sequence syllables to physical line sequence
      $physLineSclGIDs = $physLine->getEntityIDs();
      $mergeLineSclGIDs = $mergeLine->getEntityIDs();
      if (count($physLineSclGIDs) == 0 || count($mergeLineSclGIDs) == 0) {
        array_push($errors,"error merge of empty line is not supported");
      } else { //merge syllable GIDs
        $oldPhysLineSeqGID = $physLine->getGlobalID();
        if ( $physLine->isReadonly()) {
          $physLine = $physLine->cloneEntity($defAttrIDs,$defVisIDs);
        }
        //merge syllable GIDs
        $physLine->setEntityIDs(array_merge($physLineSclGIDs,$mergeLineSclGIDs));
        $physLine->save();
        $physLineSeqGID = $physLine->getGlobalID();
        $retVal['physLineSeqID'] = $physLine->getID();
        if ($physLine->hasError()) {
          array_push($errors,"error merging physical line sequence '".$physLine->getValue()."' - ".$physLine->getErrors(true));
        } else  if ($oldPhysLineSeqGID == $physLineSeqGID) {//we owned this so update
          //changed components on a cached sequence so invalidate cache to recalc on next refresh
          invalidateCachedSeq($physLine->getID(),$ednOwnerID);
          addUpdateEntityReturnData('seq',$physLine->getID(),'entityIDs',$physLine->getEntityIDs());
        } else { //cloned physical so add it
          addNewEntityReturnData('seq',$physLine);
        }
        if ( !$mergeLine->isReadonly()) {//if we own this mark for delete
          $mergeLine->markForDelete();
        }
        //calculate new physical sequence GIDs
        $physSeqEntityIDs = $seqPhys->getEntityIDs();
        //remove merged physical line GID from TextPhysical sequence
        $mergeIndex = array_search($mergeLine->getGlobalID(),$physSeqEntityIDs);
        if ($mergeIndex) {
          array_splice($physSeqEntityIDs,$mergeIndex,1);
        } else {
          array_push($errors,"error updating physical sequence merge GID'".$mergeLine->getGlobalID()."' not found");
        }
        //if physical line (extended one) was cloned then replace old GID with new
        if ($oldPhysLineSeqGID != $physLineSeqGID) {
          $physSeqIDIndex = array_search($oldPhysLineSeqGID,$physSeqEntityIDs);
          if ($physSeqIDIndex) {
            array_splice($physSeqEntityIDs,$physSeqIDIndex,1,$physLineSeqGID);
          } else {
            array_push($errors,"error updating physical sequence old line GID'".$oldPhysLineSeqGID."' not found");
          }
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
        invalidateCachedEdn($edition->getID(),$edition->getCatalogID());
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
