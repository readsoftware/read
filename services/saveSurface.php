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
* saveSurface
*
* saves Surface entity data
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
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
require_once (dirname(__FILE__) . '/../model/entities/Surface.php');
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
  $surface = null;
  $srfID = null;
  $srfGID = null;
  if ( isset($data['srfID'])) {//get existing surface
    $srfID = $data['srfID'];
    $surface = new Surface($srfID);
    if ($surface->hasError()) {
      array_push($errors,"error loading surface id $srfID - ".join(",",$surface->getErrors()));
    } else if ($surface->isReadonly()) {
      array_push($errors,"error surface id $srfID - is readonly");
    } else {
      $srfGID = $surface->getGlobalID();
    }
  } else {
    $surface = new Surface();
    $surface->setOwnerID($defOwnerID);
    $surface->setVisibilityIDs($defVisIDs);
    $surface->save();
    if ($surface->hasError()) {
      array_push($errors,"error creating new surface - ".join(",",$surface->getErrors()));
    } else {
      $srfGID = $surface->getGlobalID();
    }
  }
  $description = null;
  if ( isset($data['description'])) {//get description for surface
    $description = $data['description'];
  }
  $label = null;
  if ( isset($data['label'])) {//get label for surface
    $label = $data['label'];
  }
  $number = null;
  if ( isset($data['number'])) {//get number for surface
    $number = $data['number'];
  }
  $layer = null;
  if ( isset($data['layer'])) {//get layer for surface
    $layer = $data['layer'];
  }
  $imageIDs = null;
  if ( isset($data['imageIDs'])) {//get imageIDs for text
    $imageIDs = $data['imageIDs'];
  }
  $addImageID = null;
  if ( isset($data['addImageID'])) {//get addImageID for text
    $addImageID = $data['addImageID'];
  }
  $addTxtID = null;
  if ( isset($data['addTxtID'])) {//get addTxtID to be linked to surface
    $addTxtID = $data['addTxtID'];
  }
  $removeTxtID = null;
  if ( isset($data['removeTxtID'])) {//get removeTxtID to be unlinked from surface
    $removeTxtID = $data['removeTxtID'];
  }
  $newSurfaceFrgID = null;
  if ( isset($data['newSurfaceFrgID'])) {//add new surface to fragment of newSurfaceFrgID
    $newSurfaceFrgID = $data['newSurfaceFrgID'];
  }
}

if (count($errors) == 0) {
  if (!$surface || ($description === null && $label === null && $number === null && $layer === null &&
                    $addTxtID === null && $removeTxtID === null && $newSurfaceFrgID === null)) {
    array_push($errors,"insufficient data to save surface");
  } else {//use data to update surface and save
    if ($description !== null) {
      $surface->setDescription($description);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'description', $surface->getDescription());
      }
    }
    if ($label !== null) {
      $surface->setLabel($label);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'value', $surface->getLabel());
        addUpdateEntityReturnData("srf",$srfID,'label', $surface->getLabel());
      }
    }
    if ($number !== null) {
      $surface->setNumber($number);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'number', $surface->getNumber());
      }
    }
    if ($layer !== null) {
      $surface->setLayerNumber($layer);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'layer', $surface->getLayerNumber());
      }
    }
    if ($newSurfaceFrgID) {
      $surface->setFragmentID($newSurfaceFrgID);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'fragmentID', $surface->getFragmentID());
      }
    }
    if ($imageIDs) {
      $surface->setImageIDs($imageIDs);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'imageIDs', $surface->getImageIDs());
      }
    }
    if ($addTxtID) {
      $textIDs = $surface->getTextIDs();
      if ($textIDs && count($textIDs)) {
        array_push($textIDs,$addTxtID);
        $textIDs = array_unique($textIDs);
      } else {
        $textIDs = array($addTxtID);
      }
      $surface->setTextIDs($textIDs);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'textIDs', $surface->getTextIDs());
      }
    }
    if ($removeTxtID) {
      $textIDs = $surface->getTextIDs();
      if ($textIDs && count($textIDs)) {
        $textIDs = array_unique($textIDs);
        $index = array_search($removeTxtID,$textIDs);
        if ($index !== false) {
          array_splice($textIDs,$index,1);
        }
        $surface->setTextIDs($textIDs);
        if ($srfID) {
          addUpdateEntityReturnData("srf",$srfID,'textIDs', $surface->getTextIDs());
        }
      }
    }
    if ($addImageID) {
      $imageIDs = $surface->getImageIDs();
      if ($imageIDs && count($imageIDs)) {
        array_push($imageIDs,$addImageID);
        array_unique($imageIDs);
      } else {
        $imageIDs = array($addImageID);
      }
      $surface->setImageIDs($imageIDs);
      if ($srfID) {
        addUpdateEntityReturnData("srf",$srfID,'imageIDs', $surface->getImageIDs());
      }
    }
    $surface->save();
    if ($surface->hasError()) {
      array_push($errors,"error updating surface '".$surface->getLabel()."' - ".$surface->getErrors(true));
    }else {
      if (!$srfID){
        addNewEntityReturnData('srf',$surface);
        invalidateCache('SearchAllResults');
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
