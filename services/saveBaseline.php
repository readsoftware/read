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
* saves Baseline entity data
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
require_once (dirname(__FILE__) . '/../model/entities/Surface.php');
require_once (dirname(__FILE__) . '/../model/entities/Baseline.php');
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
  $baseline = null;
  $blnID = null;
  $blnGID = null;
  $blnLinked = false;
  if ( isset($data['blnID'])) {//get existing sequence
    $blnID = $data['blnID'];
    $baseline = new Baseline($blnID);
    if ($baseline->hasError()) {
      array_push($errors,"error loading baseline id $blnID - ".join(",",$baseline->getErrors()));
    } else if ($text->isReadonly()) {
      array_push($errors,"error baseline id $blnID - is readonly");
    } else {
      $blnGID = $baseline->getGlobalID();
      $blnLinked = ($baseline->getSegments()->getCount() > 0);
    }
  } else {
    $baseline = new Baseline();
    $baseline->setOwnerID($defOwnerID);
    $baseline->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $baseline->setAttributionIDs($defAttrIDs);
    }
    $baseline->setType(Entity::getIDofTermParentLabel("image-baselinetype"));//term dependency
    $baseline->save();
    if ($baseline->hasError()) {
      array_push($errors,"error creating new baseline - ".join(",",$baseline->getErrors()));
    } else {
      $blnGID = $baseline->getGlobalID();
    }
  }
  $txtID = null;
  if ( isset($data['txtID'])) {//get txtID for surface
    $txtID = $data['txtID'];
  }
  $srfID = null;
  if ( isset($data['srfID'])) {//get srfID for baseline
    $srfID = $data['srfID'];
  }
  $imgpos = null;
  if ( isset($data['imgpos'])) {//get clipping polygon for baseline
    $imgpos = $data['imgpos'];
  }
  $imgID = null;
  if ( isset($data['imgID'])) {//get imgID for baseline
    $imgID = $data['imgID'];
  }
}

if (count($errors) == 0) {
  if (!$baseline || ( $srfID === null && $imgpos === null && $imgID === null)) {
    array_push($errors,"insufficient data to save baseline");
  } else {//use data to update baseline and save
    if ($srfID !== null) {
      $surface = new Surface($srfID);
    } else if (!$baseline->getSurfaceID() && $txtID) {//create new surface
      $surface = new Surface();
      $surface->setTextIDs(array($txtID));
      if ($imgID) {
        $surface->setImageIDs(array($imgID));
      }
      $surface->setOwnerID($defOwnerID);
      $surface->setVisibilityIDs($defVisIDs);
      if ($defAttrIDs){
//        $surface->setAttributionIDs($defAttrIDs);
      }
      $surface->save();
      if ($surface->hasError()) {
        array_push($errors,"error creating new surface - ".join(",",$surface->getErrors()));
      } else {
        addNewEntityReturnData('srf',$surface);
        $srfID = $surface->getID();
      }
    }
    if ($srfID !== null) {
      $baseline->setSurfaceID($srfID);
      if ($blnID) {
        addUpdateEntityReturnData("bln",$blnID,'surfaceID', $baseline->getSurfaceID());
      }
    }
    if ($imgID && !$blnLinked) {
      $baseline->setImageID($imgID);
      if ($blnID) {
        addUpdateEntityReturnData("bln",$blnID,'imageID', $baseline->getImageID());
      }
    }
    if ($imgpos && !$blnLinked) {
      $baseline->setImageBoundary($imgpos);
      if ($blnID) {
        addUpdateEntityReturnData("bln",$blnID,'boundary', $baseline->getImageBoundary());
      }
    }
    $baseline->save();
    if ($baseline->hasError()) {
      array_push($errors,"error updating baseline '".$baseline->getTitle()."' - ".$baseline->getErrors(true));
    }else {
      if (!$blnID){
        addNewEntityReturnData('bln',$baseline);
      }
      $blnID = $baseline->getID();
//      addLinkEntityReturnData('srf',$srfID,'blnIDs',$blnID);
      $txtIDs = $surface->getTextIDs();
      foreach ($txtIDs as $txtID) {
        addLinkEntityReturnData('txt',$txtID,'blnIDs',$blnID);
      }
    }
  }
}
$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
//  invalidateCache('AllTextResources'.getUserDefEditorID());
  invalidateCache('AllTextResources');
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
