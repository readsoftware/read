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
* saveTags
*
* saves entity data for tag type annotation entities
*
*   data =
*   {
*     entGID: "tok:9",
*     tagAddToGIDs: ["ano5","ano:7","trm:1156"]
*     tagRemoveFromGIDs: ["ano4","ano:8"]
*   }
*
*
* return json format:  //record updated field data returned
*
* {
*   "success": true,
*   "entities": { "update":
*                 { "tok":
*                   { 9:
*                     {
*                      'linkedByAnoIDsByType': {1143:[9],1134:[5],1136:[7]}
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
require_once (dirname(__FILE__) . '/../model/entities/Annotation.php');
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
  $attrAddToGIDs = null;
  if ( isset($data['attrAddToGIDs'])) {//get add to attributes IDs
    $attrAddToGIDs = $data['attrAddToGIDs'];
  }
  $attrRemoveFromGIDs = null;
  if ( isset($data['attrRemoveFromGIDs'])) {//get remove from attribution IDs
    $attrRemoveFromGIDs = $data['attrRemoveFromGIDs'];
  }
  $entity = null;
  if ( isset($data['entGID'])) {//get entity GID
    $entGID = $data['entGID'];
    $entity = EntityFactory::createEntityFromGlobalID($entGID);
    if ($entity->hasError()) {
      array_push($errors,"error loading entity id $entGID - ".join(",",$entity->getErrors()));
    } else {
      $entGID = $entity->getGlobalID();
    }
  }
}
if (count($errors) == 0) {
  if (!$entity || ((!$attrAddToGIDs || count($attrAddToGIDs) == 0) &&
                   (!$attrRemoveFromGIDs || count($attrRemoveFromGIDs) == 0))) {
    array_push($errors,"insufficient data to save attribution");
  } else {//save to or remove from attribution ids for entity attributionIDs
    $entAttrIDs = $entity->getAttributionIDs();
    if (!$entAttrIDs) {
      $entAttrIDs = array();
    }
    if (($attrRemoveFromGIDs && count($attrRemoveFromGIDs) > 0 && count($entAttrIDs) > 0)) {
      foreach ($attrRemoveFromGIDs as $attrGID) {
        $attrGID = str_replace(":","",$attrGID);
        $prefix = substr($attrGID,0,3);
        $id = substr($attrGID,3);
        $atbIndex = array_search($id,$entAttrIDs);
        if ($prefix == 'atb' && $atbIndex !== false) {
          array_splice($entAttrIDs,$atbIndex,1);
        }
      }
    }
    if (($attrAddToGIDs && count($attrAddToGIDs) > 0)) {
      $attrAddToGIDs = join(",",$attrAddToGIDs);
      $attrAddToGIDs = str_replace(":","",$attrAddToGIDs);
      $attrAddToGIDs = str_replace("atb","",$attrAddToGIDs);
      $attrAddToGIDs = explode(",",$attrAddToGIDs);
      $entAttrIDs = array_unique(array_merge($entAttrIDs,$attrAddToGIDs));
    }
    $entity->setAttributionIDs($entAttrIDs);
    $entity->save();
    if ($entity->hasError()) {
      array_push($errors,"error updating attributions for '".$entity->getGlobalID()."' - ".$entity->getErrors(true));
    }else{
      addUpdateEntityReturnData(substr($entGID,0,3),$entity->getID(),'attributionIDs', $entity->getAttributionIDs());
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
if ($tagsInfo) {
  $retVal["tagsInfo"] = $tagsInfo;
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
