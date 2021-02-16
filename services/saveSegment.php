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
* saveSegment
*
* saves Segment entity data
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
ob_start();

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Segment.php');
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
  $segment = null;
  $segID = null;
  $segGID = null;
  $tempIDMap = null;
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
  if ( isset($data['segID']) && !preg_match("/new/",$data['segID'])) {//get existing sequence
    $segID = $data['segID'];
    $segment = new Segment($segID);
    if ($segment->hasError()) {
      array_push($errors,"error loading segment id $segID - ".join(",",$segment->getErrors()));
    } else if ($segment->isReadonly()) {
      array_push($errors,"error segment id $segID - is readonly");
    } else if ($segment->isMarkedDelete()) {
      array_push($errors,"error segment id $segID - is marked for delete");
    } else {
      $segGID = $segment->getGlobalID();
    }
  } else {
    $segment = new Segment();
    $segment->setOwnerID($defOwnerID);
    $segment->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $segment->setAttributionIDs($defAttrIDs);
    }
    $segment->save();
    if ($segment->hasError()) {
      array_push($errors,"error creating new segment - ".join(",",$segment->getErrors()));
    } else {
      $segGID = $segment->getGlobalID();
      if (preg_match("/new/",$data['segID'])) {
        $tempIDMap = array();
        $tempIDMap[$segment->getID()] = $data['segID'];
      }
    }
  }
  $code = null;
  if ( isset($data['code'])) {//get code for segment
    $code = $data['code'];
  }
  $loc = null;
  if ( isset($data['loc'])) {//get loc for segment
    $loc = $data['loc'];
  }
  $ord = null;
  if ( isset($data['ordinal'])) {//get ordinal for segment
    $ord = $data['ordinal'];
  }
  $rotation = null;
  if ( isset($data['rotation'])) {//get rotation for segment
    $rotation = $data['rotation'];
  }
  $layer = null;
  if ( isset($data['layer'])) {//get layer for segment
    $layer = $data['layer'];
  }
  $clarityID = null;
  if ( isset($data['clarityID'])) {//get clarityID for segment
    $clarityID = $data['clarityID'];
  }
  $typeID = null;
  if ( isset($data['typeID'])) {//get typeID for segment
    $typeID = $data['typeID'];
  }
  $obscurations = null;
  if ( isset($data['obscurations'])) {//get obscurations for segment
    if ($data['obscurations'] && $data['obscurations'] != '') {
      $obscurations = $data['obscurations'];
    } else { //handle delete case
      $obscurations = array();
    }
  }
  $boundary = null;
  if ( isset($data['boundary'])) {//get boundary for segment
    if ($data['boundary'] && $data['boundary'] != '') {
      $boundary = $data['boundary'];
    } else { //handle delete case
      $boundary = array();
    }
  }
  $baselineIDs = null;
  if ( isset($data['baselineIDs'])) {//get baselineIDs for segment
    if ($data['baselineIDs'] && $data['baselineIDs'] != '') {
      $baselineIDs = $data['baselineIDs'];
    } else { //handle delete case
      $baselineIDs = array();
    }
  }
  $visibilityIDs = null;
  if ( isset($data['visibilityIDs'])) {//get visibilityIDs for segment
    if ($data['visibilityIDs'] && $data['visibilityIDs'] != '') {
      $visibilityIDs = $data['visibilityIDs'];
    }
  }
  $annotationIDs = null;
  if ( isset($data['annotationIDs'])) {//get annotationIDs for segment
    if ($data['annotationIDs'] && $data['annotationIDs'] != '') {
      $annotationIDs = $data['annotationIDs'];
    } else { //handle delete case
      $annotationIDs = array();
    }
  }
  $attributionIDs = null;
  if ( isset($data['attributionIDs'])) {//get attributionIDs for segment
    if ($data['attributionIDs'] && $data['attributionIDs'] != '') {
      $attributionIDs = $data['attributionIDs'];
    } else { //handle delete case
      $attributionIDs = array();
    }
  }
}
$isSegmentUpdate = ($segID && $segment);
if (count($errors) == 0) {
  if (!$segment || ($code === null && $loc === null && $ord === null && $obscurations === null && !$typeID &&
      !$clarityID && !$layer && !$boundary  && !$baselineIDs && !$rotation)) {
    array_push($errors,"insufficient data to save segment");
  } else {//use data to set segment properties and save
    if ($code !== null) {
      $segment->storeScratchProperty('code', $code);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seg",$segID,'code', $segment->getScratchProperty('code'));
        addUpdateEntityReturnData("seg",$segID,'value', $segment->getScratchProperty('code'));
      }
    }
    if ($loc !== null) {
      $segment->storeScratchProperty('sgnLoc', $loc);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seg",$segID,'loc', $segment->getScratchProperty('sgnLoc'));
      }
    }
    if ($ord !== null) {
      $segment->storeScratchProperty('blnOrdinal', $ord);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seg",$segID,'ordinal', $segment->getScratchProperty('blnOrdinal'));
      }
    }
    if ($rotation !== null) {
      $segment->setRotation($rotation);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seq",$segID,'rotation', $segment->getRotation());
      }
    }
    if ($typeID) {
      $segment->storeScratchProperty('typeID', $typeID);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seg",$segID,'typeID', $segment->getScratchProperty('typeID'));
      }
//      $segment->setTypeID($typeID);
//      if ($isSegmentUpdate) {
//        addUpdateEntityReturnData("seq",$segID,'typeID', $segment->getTypeID());
//      }
    }
    if ($clarityID) {
      $segment->setClarityID($clarityID);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seq",$segID,'clarityID', $segment->getClarityID());
      }
    }
    if ( isset($boundary)) {
      $segment->setImageBoundary($boundary);
      addUpdateEntityReturnData("seg",$segID,'boundary', $segment->getImageBoundary());
      addUpdateEntityReturnData("seg",$segID,'center', $segment->getCenter());
    }
    if ($layer) {
      $segment->setLayer($layer);
      addUpdateEntityReturnData("seg",$segID,'layer', $segment->getLayer());
    }
    if (isset($baselineIDs)) {
      $segment->setBaselineIDs($baselineIDs);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seq",$segID,'baselineIDs', $segment->getBaselineIDs());
      }
    }
    if (isset($visibilityIDs)) {
      $segment->setVisibilityIDs($visibilityIDs);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seq",$segID,'visibilityIDs', $segment->getVisibilityIDs());
      }
    }
    if (isset($annotationIDs)) {
      $segment->setAnnotationIDs($annotationIDs);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seq",$segID,'annotationIDs', $segment->getAnnotationIDs());
      }
    }
    if (isset($attributionIDs)) {
      $segment->setAttributionIDs($attributionIDs);
      if ($isSegmentUpdate) {
        addUpdateEntityReturnData("seq",$segID,'attributionIDs', $segment->getAttributionIDs());
      }
    }
    $segment->save();
    if ($segment->hasError()) {
      array_push($errors,"error updating segment '".$segment->getLabel()."' - ".$segment->getErrors(true));
    } else if (!$isSegmentUpdate){ //adding a new segment
      addNewEntityReturnData('seg',$segment);
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
if ($tempIDMap) {
  $retVal["tempIDMap"] = $tempIDMap;
}
if (count($entities)) {
  $retVal["entities"] = $entities;
}
if ($healthLogging && $edition) {
  $retVal["editionHealth"] = checkEditionHealth($edition->getID(),false);
}
ob_clean();
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}

?>
