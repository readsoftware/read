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
* saveText
*
* saves Text entity data
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
  $text = null;
  $txtID = null;
  $txtGID = null;
  if ( isset($data['txtID'])) {//get existing text
    $txtID = $data['txtID'];
    $text = new Text($txtID);
    if ($text->hasError()) {
      array_push($errors,"error loading text id $txtID - ".join(",",$text->getErrors()));
    } else if ($text->isReadonly()) {
      array_push($errors,"error text id $txtID - is readonly");
    } else {
      $txtGID = $text->getGlobalID();
    }
  } else {
    $text = new Text();
    $text->setOwnerID($defOwnerID);
    $text->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $text->setAttributionIDs($defAttrIDs);
    }
    $text->setTitle("enter text title");
    $text->setCKN("enter text inv. no.");
    $text->save();
    if ($text->hasError()) {
      array_push($errors,"error creating new text - ".join(",",$text->getErrors()));
    } else {
      $txtGID = $text->getGlobalID();
    }
  }
  $ckn = null;
  if ( isset($data['ckn'])) {//get ckn for text
    $ckn = $data['ckn'];
  }
  $title = null;
  if ( isset($data['title'])) {//get title for text
    $title = $data['title'];
  }
  $ref = null;
  if ( isset($data['ref'])) {//get superscript for text
    $ref = $data['ref'];
  }
  $typeIDs = null;
  if ( isset($data['typeIDs'])) {//get typeIDs for text
    $typeIDs = $data['typeIDs'];
  }
  $imageIDs = null;
  if ( isset($data['imageIDs'])) {//get imageIDs for text
    $imageIDs = $data['imageIDs'];
  }
  $addTypeID = null;
  if ( isset($data['addTypeID'])) {//get addTypeID for text
    $addTypeID = $data['addEntityGID'];
  }
  $addImageID = null;
  if ( isset($data['addImageID'])) {//get addImageID for text
    $addImageID = $data['addImageID'];
  }
  $addNewText = null;
  if ( isset($data['addNewText'])) {//add new Image to text
    $addNewText = true;
  }
}

if (count($errors) == 0) {
  if (!$text || ($ckn === null && $title === null && $ref === null && !$typeIDs && !$addNewText &&
                   !$imageIDs && $addTypeID === null && $addImageID === null)) {
    array_push($errors,"insufficient data to save text");
  } else {//use data to update text and save
    if ($ckn !== null) {
      $text->setCKN($ckn);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'CKN', $text->getCKN());
      }
    }
    if ($title !== null) {
      $text->setTitle($title);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'value', $text->getTitle());
        addUpdateEntityReturnData("txt",$txtID,'title', $text->getTitle());
      }
    }
    if ($ref !== null) {
      $text->setRef($ref);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'ref', $text->getRef());
      }
    }
    if ($typeIDs) {
      $text->setTypeIDs($typeIDs);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'typeIDs', $text->getTypeIDs());
      }
    }
    if ($imageIDs) {
      $text->setImageIDs($imageIDs);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'imageIDs', $text->getImageIDs());
      }
    }
    if ($addTypeID) {
      $typeIDs = $text->getTypeIDs();
      if ($typeIDs && count($typeIDs)) {
        array_push($typeIDs,$addTypeID);
      } else {
        $typeIDs = array($addTypeID);
      }
      $text->setTypeIDs($typeIDs);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'typeIDs', $text->getTypeIDs());
      }
    }
    if ($addImageID) {
      $imageIDs = $text->getImageIDs();
      if ($imageIDs && count($imageIDs)) {
        array_push($imageIDs,$addImageID);
      } else {
        $imageIDs = array($addImageID);
      }
      $text->setImageIDs($imageIDs);
      if ($txtID) {
        addUpdateEntityReturnData("txt",$txtID,'imageIDs', $text->getImageIDs());
      }
    }
    $text->save();
    if ($text->hasError()) {
      array_push($errors,"error updating text '".$text->getTitle()."' - ".$text->getErrors(true));
    }else {
      if (!$txtID){
        addNewEntityReturnData('txt',$text);
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
