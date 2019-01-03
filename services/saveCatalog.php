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
* saves Catalog entity data
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
require_once (dirname(__FILE__) . '/../model/entities/Catalog.php');
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
  $catalog = null;
  $catID = null;
  $catGID = null;
  if ( isset($data['catID'])) {//get existing text
    $catID = $data['catID'];
    $catalog = new Catalog($catID);
    if ($catalog->hasError()) {
      array_push($errors,"error loading catalog id $catID - ".join(",",$catalog->getErrors()));
    } else if ($catalog->isReadonly()) {
      array_push($errors,"error catalog id $catID - is readonly");
    } else {
      $txtGID = $catalog->getGlobalID();
    }
  } else {
      array_push($errors,"error creating new catalog not support by saveCatalog, must provide a valid catalog ID");
  }
  $title = null;
  if ( isset($data['title'])) {//get title for catalog
    $title = $data['title'];
  }
  $descrpt = null;
  if ( isset($data['description'])) {//get description for catalog
    $descrpt = $data['description'];
  }
  $typeID = null;
  if ( isset($data['typeID'])) {//get typeID for catalog
    $typeID = $data['typeID'];
  }
  $editionIDs = null;
  if ( isset($data['editionIDs'])) {//get editionIDs for catalog
    $editionIDs = $data['editionIDs'];
  }
  $removeEditionID = null;
  if ( isset($data['removeEditionID'])) {//get removeEditionID for catalog
    $removeEditionID = $data['removeEditionID'];
  }
  $addEditionID = null;
  if ( isset($data['addEditionID'])) {//get addEditionID for catalog
    $addEditionID = $data['addEditionID'];
  }
}

if (count($errors) == 0) {
  if (!$catalog || ( $title === null && $descrpt === null && $typeID === null &&
                   $editionIDs == null && $removeEditionID === null && $addEditionID === null)) {
    array_push($errors,"insufficient data to save catalog");
  } else {//use data to update catalog and save
    if ($title !== null) {
      $catalog->setTitle($title);
      if ($catID) {
        addUpdateEntityReturnData("cat",$catID,'value', $catalog->getTitle());
        addUpdateEntityReturnData("cat",$catID,'title', $catalog->getTitle());
      }
    }
    if ($descrpt !== null) {
      $catalog->setDescription($descrpt);
      if ($txtID) {
        addUpdateEntityReturnData("cat",$catID,'description', $catalog->getDescription());
      }
    }
    if ($typeID) {
      $catalog->setTypeID($typeID);
      if ($catID) {
        addUpdateEntityReturnData("cat",$catID,'typeID', $catalog->getTypeID());
      }
    }
    if ($editionIDs) {
      $catalog->setEditionIDs($editionIDs);
      if ($catID) {
        addUpdateEntityReturnData("cat",$catID,'ednIDs', $catalog->getEditionIDs());
      }
    }
    if ($removeEditionID) {
      $ednIDs = $catalog->getEditionIDs();
      $ednIDIndex = array_search($removeEditionID,$ednIDs);
      array_splice($ednIDs,$ednIDIndex,1);
      $catalog->setEditionIDs($ednIDs);
      if ($catID) {
        addUpdateEntityReturnData("cat",$catID,'ednIDs', $catalog->getEditionIDs());
      }
    }
    if ($addEditionID) {
      $ednIDs = $catalog->getEditionIDs();
      if ($ednIDs && count($ednIDs)) {
        array_push($ednIDs,$addEditionID);
      } else {
        $ednIDs = array($addEditionID);
      }
      $catalog->setEditionIDs($ednIDs);
      if ($catID) {
        addUpdateEntityReturnData("cat",$catID,'ednIDs', $catalog->getEditionIDs());
      }
    }
    $catalog->save();
    if ($catalog->hasError()) {
      array_push($errors,"error updating catalog '".$catalog->getTitle()."' - ".$catalog->getErrors(true));
    }else {
      if (!$catID){
        addNewEntityReturnData('cat',$catalog);
      }
    }
    //invalidateAllTextResources();  
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
