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
ob_start();

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
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $tagAddToGIDs = null;
  if ( isset($data['tagAddToGIDs'])) {//get add to tag annotation/term entity GIDs
    $tagAddToGIDs = $data['tagAddToGIDs'];
  }
  $tagRemoveFromGIDs = null;
  if ( isset($data['tagRemoveFromGIDs'])) {//get remove from tag annotation entity GIDs
    $tagRemoveFromGIDs = $data['tagRemoveFromGIDs'];
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
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}
if (count($errors) == 0) {
  if (!$entity || ((!$tagAddToGIDs || count($tagAddToGIDs) == 0) &&
                   (!$tagRemoveFromGIDs || count($tagRemoveFromGIDs) == 0))) {
    array_push($errors,"insufficient data to save tags");
  } else {//save entity's GID to or remove from tag annotation entities
    $tagsInfoChanged = false;
    if (($tagRemoveFromGIDs && count($tagRemoveFromGIDs) > 0)) {
      $anoIDs = join(",",$tagRemoveFromGIDs);
      $anoIDs = str_replace(":","",$anoIDs);
      $anoIDs = str_replace("ano","",$anoIDs);
      $annotations = new Annotations("ano_id in ($anoIDs)",null,null,null);
      if (!$annotations->getCount()) {
        array_push($errors,"creating annotation set - ");
      } else {
        foreach ($annotations as $annotation) {
          $linkedToIDs = array();
          if ($annotation && !$annotation->hasError()) {
            $linkedToIDs = $annotation->getLinkToIDs();
          }
          if ($linkedToIDs && count($linkedToIDs) > 0 && 
              in_array($entGID,$linkedToIDs) && !$annotation->isReadonly()) {
            $tagEntGIDIndex = array_search($entGID,$linkedToIDs);
            array_splice($linkedToIDs,$tagEntGIDIndex,1);
            $annotation->setLinkToIDs($linkedToIDs);
            $annotation->save();
            if ($annotation->hasError()) {
              array_push($errors,"error creating annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
            }else{
              addNewEntityReturnData('ano',$annotation);
//              addUpdateEntityReturnData("ano",$annotation->getID(),'linkedToIDs', $annotation->getLinkToIDs());
              $tagsInfoChanged = true;
            }
          }
        }
      }
    }
    if (($tagAddToGIDs && count($tagAddToGIDs) > 0)) {
      foreach ($tagAddToGIDs as $tagGID) {
        $tagGID = str_replace(":","",$tagGID);
        $prefix = substr($tagGID,0,3);
        $id = substr($tagGID,3);
        if ($prefix == "trm") {// first time tag or out of synch client
          $annos = new Annotations("ano_type_id = $id and ano_owner_id = $defOwnerID",null,null,null);
//          $annos = new Annotations("ano_type_id = $id and ano_owner_id = ".getUserID(),null,null,null);
          if ($annos->getCount() > 0) {//out of synch so translate to ano id
            $annoTag = $annos->current();
            $prefix = "ano";
            $id = $annoTag->getID();
          }
        }
        if ($prefix == "ano") {//existing anno representation of tag so update
          $annotation = new Annotation($id);
          if ($annotation->hasError()) {
            array_push($errors,"error loading annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
          }else{
            $linkedToIDs = $annotation->getLinkToIDs();
            //if empty create it
            if (!$linkedToIDs) {// handle case where previously created and then all entities removed.
              $linkedToIDs = array();
            }
            if (!in_array($entGID,$linkedToIDs)&& !$annotation->isReadonly()) {
              array_push($linkedToIDs,$entGID);
              $annotation->setLinkToIDs($linkedToIDs);
              $annotation->save();
              if ($annotation->hasError()) {
                array_push($errors,"error updating tag representation annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
              }else{
                addNewEntityReturnData("ano",$annotation);
                $tagsInfoChanged = true;
              }
            }
          }
        } else if ($prefix == "trm") {//first use of tag so create new anno
          $annotation = new Annotation();
          $annotation->setLinkToIDs(array($entGID));
          $annotation->setTypeID($id);
          $annotation->setOwnerID($defOwnerID);
          $annotation->setVisibilityIDs($defVisIDs);
          if ($defAttrIDs){
            $annotation->setAttributionIDs($defAttrIDs);
          }
          $annotation->save();
          if ($annotation->hasError()) {
            array_push($errors,"error updating tag representation annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
          }else{
            addNewEntityReturnData('ano',$annotation);
            $tagsInfoChanged = true;
          }
        } else {
          array_push($errors,"tagID $tagGID is invalid ");
        }
      }
    }
    if ($tagsInfoChanged) {
      addUpdatedTagsInfo();
    }
  }
}
if ((count($errors) == 0) && $entity) {//update tags for the linked entity
  $linkedByAnoIDsByType = $entity->getLinkedByAnnotationsByType();
  if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
    addUpdateEntityReturnData(substr($entGID,0,3),$entity->getID(),'linkedByAnoIDsByType',$linkedByAnoIDsByType);
  } else if (!$linkedByAnoIDsByType){
    addUpdateEntityReturnData(substr($entGID,0,3),$entity->getID(),'linkedByAnoIDsByType',array());
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
if ($healthLogging ) {
//  $retVal["editionHealth"] = checkEditionHealth($edition->getID(),false);
}
ob_clean();
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
