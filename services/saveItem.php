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
* saveItem
*
* saves Item entity data
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
  $item = null;
  $itmID = null;
  $itmGID = null;
  if ( isset($data['itmID'])) {//get existing item
    $itmID = $data['itmID'];
    $item = new Item($itmID);
    if ($item->hasError()) {
      array_push($errors,"error loading item id $itmID - ".join(",",$item->getErrors()));
    } else if ($item->isReadonly()) {
      array_push($errors,"error item id $itmID - is readonly");
    } else {
      $itmGID = $item->getGlobalID();
    }
  } else {
    $item = new Item();
    $item->setOwnerID($defOwnerID);
    $item->setVisibilityIDs($defVisIDs);
    $item->save();
    if ($item->hasError()) {
      array_push($errors,"error creating new item - ".join(",",$item->getErrors()));
    } else {
      $itmGID = $item->getGlobalID();
    }
  }
  $description = null;
  if ( isset($data['description'])) {//get description for item
    $description = $data['description'];
  }
  $title = null;
  if ( isset($data['title'])) {//get title for item
    $title = $data['title'];
  }
  $idno = null;
  if ( isset($data['idno'])) {//get idno for item
    $idno = $data['idno'];
	}
  $createBlank = null;
  if ( isset($data['createBlank'])) {//get createBlank boolean for creating a blank item
    $createBlank = $data['createBlank'];
	}
  $imageIDs = null;
  if ( isset($data['imageIDs'])) {//get imageIDs for text
    $imageIDs = $data['imageIDs'];
  }
  $addImageID = null;
  if ( isset($data['addImageID'])) {//get addImageID for text
    $addImageID = $data['addImageID'];
  }
}

if (count($errors) == 0) {
  if (!$item || (!$createBlank && $description === null && $title === null && $idno === null &&
                    $imageIDs === null && $addImageID === null)) {
    array_push($errors,"insufficient data to save item");
  } else {//use data to update item and save
    if ($description !== null) {
      $item->setDescription($description);
      if ($itmID) {
        addUpdateEntityReturnData("itm",$itmID,'description', $item->getDescription());
      }
    }
    if ($title !== null) {
      $item->setTitle($title);
      if ($itmID) {
        addUpdateEntityReturnData("itm",$itmID,'value', $item->getTitle());
        addUpdateEntityReturnData("itm",$itmID,'title', $item->getTitle());
      }
    }
    if ($idno !== null) {
      $item->setIdNo($idno);
      if ($itmID) {
        addUpdateEntityReturnData("itm",$itmID,'idno', $item->getIdNo());
      }
    }
    if ($imageIDs) {
      $item->setImageIDs($imageIDs);
      if ($itmID) {
        addUpdateEntityReturnData("itm",$itmID,'imageIDs', $item->getImageIDs());
      }
    }
    if ($addImageID) {
      $imageIDs = $item->getImageIDs();
      if ($imageIDs && count($imageIDs)) {
        array_push($imageIDs,$addImageID);
        array_unique($imageIDs);
      } else {
        $imageIDs = array($addImageID);
      }
      $item->setImageIDs($imageIDs);
      if ($itmID) {
        addUpdateEntityReturnData("itm",$itmID,'imageIDs', $item->getImageIDs());
      }
    }
    $item->save();
    if ($item->hasError()) {
      array_push($errors,"error updating item '".$item->getLabel()."' - ".$item->getErrors(true));
    }else {
      if (!$itmID){
        addNewEntityReturnData('itm',$item);
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
