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
* savePart
*
* saves Part entity data
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
  $part = null;
  $prtID = null;
  $prtGID = null;
  if ( isset($data['prtID'])) {//get existing part
    $prtID = $data['prtID'];
    $part = new Part($prtID);
    if ($part->hasError()) {
      array_push($errors,"error loading part id $prtID - ".join(",",$part->getErrors()));
    } else if ($part->isReadonly()) {
      array_push($errors,"error part id $prtID - is readonly");
    } else {
      $prtGID = $part->getGlobalID();
    }
  } else {
    $part = new Part();
    $part->setOwnerID($defOwnerID);
    $part->setVisibilityIDs($defVisIDs);
    $part->save();
    if ($part->hasError()) {
      array_push($errors,"error creating new part - ".join(",",$part->getErrors()));
    } else {
      $prtGID = $part->getGlobalID();
    }
  }
  $description = null;
  if ( isset($data['description'])) {//get description for part
    $description = $data['description'];
  }
  $label = null;
  if ( isset($data['label'])) {//get label for part
    $label = $data['label'];
  }
  $measure = null;
  if ( isset($data['measure'])) {//get measure for part
    $measure = $data['measure'];
	}
	$sequenceNum = null;
  if ( isset($data['sequence'])) {//get sequence number for part
    $sequenceNum = $data['sequence'];
  }
  $mediums = null;
  if ( isset($data['mediums'])) {//get mediums for part
    $mediums = $data['mediums'];
  }
  $imageIDs = null;
  if ( isset($data['imageIDs'])) {//get imageIDs for text
    $imageIDs = $data['imageIDs'];
  }
  $addImageID = null;
  if ( isset($data['addImageID'])) {//get addImageID for text
    $addImageID = $data['addImageID'];
  }
  $newPartItmID = null;
  if ( isset($data['newPartItmID'])) {//add new part to part of newPartItmID
    $newPartItmID = $data['newPartItmID'];
  }
}

if (count($errors) == 0) {
  if (!$part || ($description === null && $label === null && $measure === null && $mediums === null &&
                    $imageIDs === null && $addImageID === null && $sequenceNum === null && $newPartItmID === null)) {
    array_push($errors,"insufficient data to save part");
  } else {//use data to update part and save
    if ($description !== null) {
      $part->setDescription($description);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'description', $part->getDescription());
      }
    }
    if ($label !== null) {
      $part->setLabel($label);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'value', $part->getLabel());
        addUpdateEntityReturnData("prt",$prtID,'label', $part->getLabel());
      }
    }
    if ($measure !== null) {
      $part->setMeasure($measure);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'measure', $part->getMeasure());
      }
    }
    if ($sequenceNum !== null) {
      $part->setSequence($sequenceNum);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'sequence', $part->getSequence());
      }
    }
    if ($mediums !== null) {
      $part->setMediums($mediums);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'mediums', $part->getMediums());
      }
    }
    if ($newPartItmID) {
      $part->setItemID($newPartItmID);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'itemID', $part->getItemID());
      }
    }
    if ($imageIDs) {
      $part->setImageIDs($imageIDs);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'imageIDs', $part->getImageIDs());
      }
    }
    if ($addImageID) {
      $imageIDs = $part->getImageIDs();
      if ($imageIDs && count($imageIDs)) {
        array_push($imageIDs,$addImageID);
        array_unique($imageIDs);
      } else {
        $imageIDs = array($addImageID);
      }
      $part->setImageIDs($imageIDs);
      if ($prtID) {
        addUpdateEntityReturnData("prt",$prtID,'imageIDs', $part->getImageIDs());
      }
    }
    $part->save();
    if ($part->hasError()) {
      array_push($errors,"error updating part '".$part->getLabel()."' - ".$part->getErrors(true));
    }else {
      if (!$prtID){
        addNewEntityReturnData('prt',$part);
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
