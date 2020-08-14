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
* saveFragment
*
* saves Fragment entity data
*
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
require_once (dirname(__FILE__) . '/../model/entities/Item.php');
require_once (dirname(__FILE__) . '/../model/entities/Part.php');
require_once (dirname(__FILE__) . '/../model/entities/Fragment.php');
require_once (dirname(__FILE__) . '/../model/entities/Image.php');
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
  $fragment = null;
  $frgID = null;
  $frgGID = null;
  if ( isset($data['frgID'])) {//get existing fragment
    $frgID = $data['frgID'];
    $fragment = new Fragment($frgID);
    if ($fragment->hasError()) {
      array_push($errors,"error loading fragment id $frgID - ".join(",",$fragment->getErrors()));
    } else if ($fragment->isReadonly()) {
      array_push($errors,"error fragment id $frgID - is readonly");
    } else {
      $frgGID = $fragment->getGlobalID();
    }
  } else {
    $fragment = new Fragment();
    $fragment->setOwnerID($defOwnerID);
    $fragment->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $fragment->setAttributionIDs($defAttrIDs);
    }
    $fragment->save();
    if ($fragment->hasError()) {
      array_push($errors,"error creating new fragment - ".join(",",$fragment->getErrors()));
    } else {
      $frgGID = $fragment->getGlobalID();
    }
  }
  $description = null;
  if ( isset($data['description'])) {//get description for fragment
    $description = $data['description'];
  }
  $label = null;
  if ( isset($data['label'])) {//get label for fragment
    $label = $data['label'];
  }
  $measure = null;
  if ( isset($data['measure'])) {//get measure for fragment
    $measure = $data['measure'];
  }
  $locations = null;
  if ( isset($data['locations'])) {//get locations for fragment
    $locations = $data['locations'];
  }
  $imageIDs = null;
  if ( isset($data['imageIDs'])) {//get imageIDs for text
    $imageIDs = $data['imageIDs'];
  }
  $addImageID = null;
  if ( isset($data['addImageID'])) {//get addImageID for text
    $addImageID = $data['addImageID'];
  }
  $addMcxID = null;
  if ( isset($data['addMcxID'])) {//add mcx ID to fragment
    $addMcxID = $data['addMcxID'];
  }
  $createMcx = ($addMcxID == null); // set default createMcx to true if no mcxID else false
  if ( isset($data['createMcx'])) {//create mcx entiry and add mcx ID to fragment
    $createMcx = $data['createMcx'];
  }
  $newFragmentPrtID = null;
  if ( isset($data['newFragmentPrtID'])) {//add new fragment to part of newFragmentPrtID
    $newFragmentPrtID = $data['newFragmentPrtID'];
  }
}

if (count($errors) == 0) {
  if (!$fragment || ($description === null && $label === null && $measure === null && $locations === null && !$createMcx &&
                    $imageIDs === null && $addImageID === null  && $addMcxID === null && $newFragmentPrtID === null)) {
    array_push($errors,"insufficient data to save fragment");
  } else {//use data to update fragment and save
    if ($description !== null) {
      $fragment->setDescription($description);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'description', $fragment->getDescription());
      }
    }
    if ($label !== null) {
      $fragment->setLabel($label);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'value', $fragment->getLabel());
        addUpdateEntityReturnData("frg",$frgID,'label', $fragment->getLabel());
      }
    }
    if ($measure !== null) {
      $fragment->setMeasure($measure);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'measure', $fragment->getMeasure());
      }
    }
    if ($locations !== null) {
      $fragment->setLocationRefs($locations);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'locations', $fragment->getLocationRefs());
      }
    }
    if ($newFragmentPrtID) {
      $fragment->setPartID($newFragmentPrtID);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'partID', $fragment->getPartID());
      }
    }
    if ($imageIDs) {
      $fragment->setImageIDs($imageIDs);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'imageIDs', $fragment->getImageIDs());
      }
    }
    $mcxID = null;
    if ($createMcx || !$addMcxID && !$frgID){
      $materialCtx = new MaterialContext();
      $materialCtx->setOwnerID($defOwnerID);
      $materialCtx->setVisibilityIDs($defVisIDs);
      if ($defAttrIDs){
        $materialCtx->setAttributionIDs($defAttrIDs);
      }
      $materialCtx->save();
      if ($materialCtx->hasError()) {
        array_push($errors,"error creating new Material Context - ".join(",",$materialCtx->getErrors()));
      } else {
        $mcxID = $materialCtx->getID();
        addNewEntityReturnData('mcx',$materialCtx);
      }
    }
    if ($addMcxID || $mcxID) {
      $mcxIDs = $fragment->getMaterialContextIDs();
      if (!$mcxIDs) {
        $mcxIDs = array();
      }
      if($addMcxID) {
        array_push($mcxIDs,$addMcxID);
      }
      if($mcxID) {
        array_push($mcxIDs,$mcxID);
      }
      array_unique($mcxIDs);
      $fragment->setMaterialContextIDs($mcxIDs);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'mcxIDs', $fragment->getMaterialContextIDs());
      }
    }
    if ($addImageID) {
      $imageIDs = $fragment->getImageIDs();
      if ($imageIDs && count($imageIDs)) {
        array_push($imageIDs,$addImageID);
        array_unique($imageIDs);
      } else {
        $imageIDs = array($addImageID);
      }
      $fragment->setImageIDs($imageIDs);
      if ($frgID) {
        addUpdateEntityReturnData("frg",$frgID,'imageIDs', $fragment->getImageIDs());
      }
    }
    $fragment->save();
    if ($fragment->hasError()) {
      array_push($errors,"error updating fragment '".$fragment->getLabel()."' - ".$fragment->getErrors(true));
    }else {
      if (!$frgID){
        addNewEntityReturnData('frg',$fragment);
//        invalidateCache('SearchAllResults');
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}

?>
