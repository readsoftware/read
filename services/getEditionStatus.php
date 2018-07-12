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
* getEditionStatus
*
* get edition status and set as requested if unset
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
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$editCollisionDetection = "off";
if (!defined("EDITCOLLISIONDETECTION") || EDITCOLLISIONDETECTION) {
  $editCollisionDetection = "on";
}

$retVal = array();
$errors = array();
$warnings = array();
$ednOwnerID = null;
$status = null;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else if (!isset($data['ednID'])) {
  array_push($errors,"must specify an edition using 'ednID=##'");
} else {
  $ednID = $data['ednID'];
  $edition = new Edition($ednID);
  if ($edition->hasError()) {
    array_push($errors,"error loading editon - ".join(",",$edition->getErrors()));
  } else if ($edition->isReadonly()) {
    array_push($errors,"error editon ($ednID) is not editable");
  } else if ($edition->isMarkedDelete()) {
    array_push($errors,"error editon ($ednID) is not editable");
  } else if ($editCollisionDetection == "on"){
    $userName = $edition->getScratchProperty('editUserName');
    if (!$userName) {//open so set property in the form of username:userID:statusMessgae
      $userName = getUserName();
      $userID = getUserID();
      $status = "editing";
      $edition->storeScratchProperty('editUserName',$userName);//reserving user (editing can be done under an editAs account)
      $edition->storeScratchProperty('editUserID',$userID);
      $edition->storeScratchProperty('status',"editing");
      $edition->save();
    } else {
      $userID = $edition->getScratchProperty('editUserID');
      $status = $edition->getScratchProperty('status');
    }
  } else {
    $userName = getUserName();
    $userID = getUserID();
    $status = "editing";
  }
}

$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
  $retVal["editUserName"] = $userName;
  $retVal["editUserID"] = $userID;
  $retVal["status"] = $status;
  $retVal["collisiondetection"] = $editCollisionDetection;
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
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
