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
* deleteBaseline
*
* marks Baseline for delete if empty (no segments)
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
$baseline = null;
$blnLinked = null;
$blnGID = null;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else if ( !isset($data['blnID'])){
  array_push($errors,"invalid data supplied for delete baseline - aborting");
} else {
  $blnID = $data['blnID'];
  $baseline = new Baseline($blnID);
  if ($baseline->hasError()) {
    array_push($errors,"error loading baseline id $blnID - ".join(",",$baseline->getErrors()));
  } else if ($baseline->isReadonly()) {
    array_push($errors,"error baseline id $blnID - is readonly");
  } else {
    $blnGID = $baseline->getGlobalID();
    $blnLinked = ($baseline->getSegmentCount() > 0);
  }
}
if (count($errors) == 0) {
  if (!$baseline) {
    array_push($errors,"insufficient data to delete baseline");
  } else if ($blnLinked) {
    array_push($errors,"deleting baseline requires removing all segments first");
  } else {//use data to delete baseline and surface is solely linked to baseline
    $txtIDs = null;
    if ($baseline->getSurfaceID()) {
      //check if surface solely linked to this baseline
      $surface = $baseline->getSurface(true);
      $txtIDs = $surface->getTextIDs();
      if (!$surface->isReadonly() && count($surface->getBaselineIDs()) == 1) {
        addRemoveEntityReturnData("srf",$surface->getID());
        $surface->markForDelete();
      }
    }
    addRemoveEntityReturnData("bln",$baseline->getID());
    $baseline->markForDelete();

    if ($txtIDs) {
      foreach ($txtIDs as $txtID) {
        $text = new Text($txtID);
        if ($text->hasError()) {
          array_push($errors,"error loading text id $txtID - ".join(",",$text->getErrors()));
        } else if ($text->isReadonly()) {
          array_push($errors,"error text id $txtID - is readonly");
        } else {
          addLinkEntityReturnData('txt',$txtID,'blnIDs',$text->getBaselineIDs());
        }
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
