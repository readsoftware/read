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
* saveGlossary
*
* saves entity data for glossary entities lem and inf given data of the form
*
*   data =
*   {
*     cmd: "createLem",
*     ednID: 9,
*     tokID: 5,
*     lemProps: {"value": "dhadu"}
*   }
*
*
* return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
*
* {
*   "success": true,
*   "entities": { "insert":
*                 { "cat":
*                   { 25:
*                     {'value': 'Richard CKM xxx Glossary',
*                      'readonly': 'false',
*                      'typeID': 535,
*                      'ednIDs': [9]
*                     }
*                   }
*                 }
*                 { "lem":
*                   { 155:
*                     {'value': 'dhadu',
*                      'readonly': 'false',
*                      'catID': 25,
*                      'entityIDs': ['tok:5']
*                     }
*                   }
*                 }
*               }
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
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Baselines.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $txtID = null;
  $text = null;
  $ednIDs = null;
  $sequence = null;
  $forceTextDelete = false;
  if ( isset($data['force'])) {//get force param
    $forceTextDelete = true;
  }
  if ( isset($data['txtID'])) {//get text
    $txtID = $data['txtID'];
    $text = new Text($txtID);
    if ($text && $text->hasError()) {
      array_push($errors,"loading text - ".join(",",$text->getErrors()));
    }
  }
  if (!$txtID) {
    array_push($errors,"insufficient information for deleting Text");
  }
  if ($text && $text->isReadonly()) {
    array_push($errors,"insufficient previledges for deleting Text");
  }
  if ($text) {
    $editions = $text->getEditions();
    if (!$editions || $editions->getError() ||
         $editions->getCount()>0 && !$forceTextDelete){
      array_push($errors,"status of text editions is preventing text deletion");
    }
    $textMetadatas = $text->getTextMetadatas();
    if (!$textMetadatas || $textMetadatas->getError() ||
         $textMetadatas->getCount()>0 && !$forceTextDelete){
      array_push($errors,"status of text textMetadatas is preventing text deletion");
    }
    $imgIDs = $text->getImageIDs();
    if ($imgIDs && count($imgIDs)>0 && !$forceTextDelete) {
      array_push($errors,"linked images is preventing text deletion");
    }
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}

if (count($errors) == 0 && $text ) {
  $surfaces = $text->getSurfaces();
  if ($surfaces && !$surfaces->getError() && $surfaces->getCount()>0){
    foreach($surfaces as $surface) {
      $textIDs = $surface->getTextIDs();
      if (($index = array_search($txtID,$textIDs)) !== false) {
        array_splice($textIDs,$index,1);
      }
      $surface->setTextIDs($textIDs);
      if (count($textIDs) == 0) {
        $surface->markForDelete();
      } else {
        $surface->save();
      }
    }
  }
  //delete text
  $text->markForDelete();
  // and remove from local cache
  addRemoveEntityReturnData('txt',$text->getID());
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
//  $retVal["editionHealth"] = checkEditionHealth($catalog->getID(),false);
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
