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
* saveAnno
*
* saves entity data for annotation entities
*
*   data =
*   {
*     cmd: "createAno",
*     linkfromGID: "tok:9",
*     typeID: 1195,
*     text: "This is a sample todo",
*     vis:"Private"
*   }
*
*
* return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
*
* {
*   "success": true,
*   "entities": { "insert":
*                 { "ano":
*                   { 25:
*                     {'value': 'This is a sample todo',
*                      'readonly': 'false',
*                      'linkFromIDs': ['tok:9'],
*                      'typeID': 1195,
*                      'vis': 'Private'
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
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Inflection.php');
require_once (dirname(__FILE__) . '/../model/entities/Lemmas.php');//include Lemma.php
require_once (dirname(__FILE__) . '/../model/entities/Catalog.php');
require_once (dirname(__FILE__) . '/../model/entities/Annotation.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $cmd = null;
  if ( isset($data['cmd'])) {//get command
    $cmd = $data['cmd'];
  } else {
    array_push($errors,"saveAnno requires a command - aborting save");
  }
  $anoID = null;
  if ( isset($data['anoID'])) {//get annotation
    $annotation = new Annotation($data['anoID']);
    if ($annotation->hasError()) {
      array_push($errors,"creating annotation - ".join(",",$annotation->getErrors()));
    } else {
      $anoID = $annotation->getID();
    }
  }
  $text = null;
  if ( isset($data['text'])) {//get annotation text/commentary
    $text = $data['text'];
  }
  $url = null;
  if ( isset($data['url'])) {//get url external link to information
    $url = $data['url'];
  }
  $anoTag = null;
  if ( isset($data['anoTag'])) {//get anoTag
    $anoTag = $data['anoTag'];
  }
  $typeID = null;
  if ( isset($data['typeID'])) {//get termID for type of annotation 
    $typeID = $data['typeID'];
  }
  $vis = null;
  if ( isset($data['vis'])) {//get vis
    if ( $data['vis'] == "User"){
      $vis = array(3);  //logged in users
    }
    if ( $data['vis'] == "Public"){
      $vis = array(6); //no loggin required to view annotation
    }
    if ( $data['vis'] == "Private"){
      $vis =getUserDefVisibilityIDs();
    }
  }
  $linkedFromEntity = null;
  $linkedFromAnchorEntity = null;
  if ( isset($data['linkFromGID']) && $data['linkFromGID']) {//get entity being annotated
    $linkedFromEntity = EntityFactory::createEntityFromGlobalID($data['linkFromGID']);
    if ($linkedFromEntity->hasError()) {
      array_push($errors,"error creating linked entity - ".join(",",$linkedFromEntity->getErrors()));
    } else if (isset($data['linkFromGIDAnchor']) && $data['linkFromGIDAnchor']) {
      $linkedFromAnchorEntity = EntityFactory::createEntityFromGlobalID($data['linkFromGIDAnchor']);
      if ($linkedFromAnchorEntity->hasError()) {
        array_push($errors,"error creating linked anchor entity- ".join(",",$linkedFromAnchorEntity->getErrors()));
      }      
    }
  }
  $linkedToEntity = null;
  if ( isset($data['linkToGID'])) {//get entity
    $linkedToEntity = EntityFactory::createEntityFromGlobalID($data['linkToGID']);
    if ($linkedToEntity->hasError()) {
      array_push($errors,"creating linked to entity - ".join(",",$linkedToEntity->getErrors()));
    }
  }
}
if (count($errors) == 0) {
  switch ($cmd) {
    case "createCustomTag":
      if (!$text) {
        array_push($errors,"insufficient data to create annotation");
      } else {//create annotation
        $annotation = new Annotation();
        if ($linkedToEntity) {
          $annotation->setLinkToIDs(array($linkedToEntity->getGlobalID()));
        }
        $typeID = Entity::getIDofTermParentLabel("customtype-tagtype");//term dependency
        $annotation->setTypeID($typeID);
        $annotation->setOwnerID(getUserDefEditorID());
        $annotation->setVisibilityIDs(getUserDefVisibilityIDs());
        $annotation->setText($text);
        if ($url){
          $annotation->setURL($url);
        }
        $annotation->save();
        if ($annotation->hasError()) {
          array_push($errors,"error creating annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
        }else{
          $annotation = new Annotation($annotation->getID());//reread anotation from DB
          addNewEntityReturnData('ano',$annotation);
          addUpdatedTagsInfo();
        }
      }
      invalidateCache('Annotations');
      break;
    case "createAno":
      if (!$linkedFromEntity && !$url && !$text) {
        array_push($errors,"insufficient data to create annotation");
      } else {//create annotation
        $annotation = new Annotation();
        $linkedFromGIDs = array($linkedFromEntity->getGlobalID());
        if ($linkedFromAnchorEntity) {
          array_push($linkedFromGIDs,$linkedFromAnchorEntity->getGlobalID());
          if (!$typeID){ // use paraphrase as default for sequence - todo consider making configurable
            $typeID = Entity::getIDofTermParentLabel("paraphrase-textreflinkage");//term dependency
           }
        }
        $annotation->setLinkFromIDs($linkedFromGIDs); //save annotation's linked context entities
        if (!$typeID){ // use footnote as default type - todo consider making configurable
         $typeID = Entity::getIDofTermParentLabel("footnote-footnotetype");//term dependency
        }
        $annotation->setTypeID($typeID);
        $annotation->setOwnerID(getUserDefEditorID());
        $annotation->setVisibilityIDs($vis);
        $defAttrIDs = getUserDefAttrIDs();
        if ($defAttrIDs){
          $annotation->setAttributionIDs($defAttrIDs);
        }
        if ($text){
          $annotation->setText($text);
        }
        if ($url){
          $annotation->setURL($url);
        }
        $annotation->save();
        if ($annotation->hasError()) {
          array_push($errors,"error creating annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
        }else{
          $annotation = new Annotation($annotation->getID());//reread anotation from DB
          addNewEntityReturnData('ano',$annotation);
          //update $linked from entity
        }
      }
      invalidateCache('Annotations');
      break;
    case "removeAno":
      if (!$linkedFromEntity && !$anoTag) {
        array_push($errors,"insufficient data to remove annotation $anoTag");
      } else {//remove annotation
        $annotation = new Annotation(substr($anoTag,3));
        if ($annotation->hasError()) {
          array_push($errors,"error creating annotation $anoTag - ".$annotation->getErrors(true));
        } else if ($annotation->isReadonly()) {
          array_push($errors,"error insufficient permissions for $anoTag ");
        } else {
          $linkFromIDs = $annotation->getLinkFromIDs();
          $indexLinked = array_search($linkedFromEntity->getGlobalID(),$linkFromIDs);
          if ($indexLinked !== false) {
            array_splice($linkFromIDs,$indexLinked,1);
            $annotation->setLinkFromIDs($linkFromIDs);
            $annotation->save();
            if ($annotation->hasError()) {
              array_push($errors,"error unlinking annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
            }else if (count($linkFromIDs) > 0) {
              addUpdateEntityReturnData("ano",$annotation->getID(),'linkedFromIDs', $annotation->getLinkFromIDs());
            } else if (count($linkFromIDs) == 0) {
              $annotation->markForDelete();
              if ($annotation->hasError()) {
                array_push($errors,"error deleting annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
              }else{
                addRemoveEntityReturnData('ano',$annotation->getID());
              }
            }
          } else {
            array_push($errors,"error deleting annotation unable to find linked entity (".$linkedFromEntity->getGlobalID().") in linkFromIDs of ".$annotation->getGlobalID());
          }
          //todo add code for linkedFromAnchor case
        }
        if (count($errors) == 0 && !$linkedFromEntity->isReadonly()) {
          if (Entity::getIDofTermParentLabel("footnote-footnotetype") == $annotation->getTypeID($typeID)) {//term dependency
            $annoIDs = $linkedFromEntity->getAnnotationIDs();
            $anoIndex = array_search($annotation->getID(),$annoIDs);
            array_splice($annoIDs,$anoIndex,1);
            $linkedFromEntity->setAnnotationIDs($annoIDs);
            $linkedFromEntity->save();
            if ($linkedFromEntity->hasError()) {
              array_push($errors,"error unlinking entity '".$linkedFromEntity->getGlobalID()."' - ".$linkedFromEntity->getErrors(true));
            }else{
              addUpdateEntityReturnData($linkedFromEntity->getEntityTypeCode(),$linkedFromEntity->getID(),'annotationIDs', $linkedFromEntity->getAnnotationIDs());
            }
          }
          addUpdateEntityReturnData($linkedFromEntity->getEntityTypeCode(),$linkedFromEntity->getID(),'linkedAnoIDsByType', $linkedFromEntity->getLinkedAnnotationsByType());
        }
      }
      invalidateCache('Annotations');
      break;
    case "updateAno":
      if ($vis){
        $annotation->setVisibilityIDs($vis);
        if (in_array(6,$vis)) {
          $vis = "Public";
        } else if (in_array(3,$vis)) {
          $vis = "User";
        } else {
          $vis = "Private";
        }
        addUpdateEntityReturnData('ano',$annotation->getID(),'vis',$vis);
      }
      if ($text){
        $annotation->setText($text);
        addUpdateEntityReturnData('ano',$annotation->getID(),'text',$annotation->getText());
      }
      if ($linkedFromEntity){
        $annotation->setLinkFromIDs(array($linkedFromEntity->getGlobalID()));
        addUpdateEntityReturnData('ano',$annotation->getID(),'linkedFromIDs',$annotation->getLinkFromIDs());
      }
      if ($url){
        $annotation->setURL($url);
        addUpdateEntityReturnData('ano',$annotation->getID(),'url',$annotation->getURL());
      }
      if ($typeID){
        $annotation->setTypeID($typeID);
        addUpdateEntityReturnData('ano',$annotation->getID(),'typeID',$annotation->getTypeID());
      }
      $annotation->save();
      if ($annotation->hasError()) {
        array_push($errors,"error creating annotation '".$annotation->getValue()."' - ".$annotation->getErrors(true));
      } else {
        $annotation = new Annotation($annotation->getID());//reread anotation from DB
        addUpdateEntityReturnData('ano',$annotation->getID(),'modStamp',$annotation->getModificationStamp());
      }
      invalidateCache('Annotations');
      break;
    default:
      array_push($errors,"unknown command");
  }
}
//if footnote then if possible append anoID into the list of annotations in the linked from entity
if ($typeID == Entity::getIDofTermParentLabel("footnote-footnotetype") &&//term dependency
  $linkedFromEntity && !$linkedFromEntity->isReadonly()){//entity is owned and footnote is publishable
  $entityAnoIDs = $linkedFromEntity->getAnnotationIDs();
  if (!$entityAnoIDs) {
    $entityAnoIDs = array();
  }
  if (!in_array($annotation->getID(),$entityAnoIDs)){
    array_push($entityAnoIDs, $annotation->getID());
    $linkedFromEntity->setAnnotationIDs($entityAnoIDs);
    $linkedFromEntity->save();
    if ($linkedFromEntity->hasError()) {
      array_push($errors,"error adding annotation to '".$linkedFromEntity->getValue()."' - ".$linkedFromEntity->getErrors(true));
    } else {
      addUpdateEntityReturnData(substr($linkedFromEntity->getGlobalID(),0,3),$linkedFromEntity->getID(),'annotationIDs',$entityAnoIDs);
    }
  }
}
if ((count($errors) == 0) && $linkedFromEntity) {//update all annotations for the linked entity
  $linkedAnoIDsByType = $linkedFromEntity->getLinkedAnnotationsByType();
  if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
    addUpdateEntityReturnData(substr($linkedFromEntity->getGlobalID(),0,3),$linkedFromEntity->getID(),'linkedAnoIDsByType',$linkedAnoIDsByType);
  }
  clearEntityAnnoCache($linkedFromEntity);
  invalidateCachedEntityInfo($linkedFromEntity);
}
if ((count($errors) == 0) && $linkedFromAnchorEntity) {//update all annotations for the linked anchor entity
  $linkedAnoIDsByType = $linkedFromAnchorEntity->getLinkedAnnotationsByType();
  if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
    addUpdateEntityReturnData(substr($linkedFromAnchorEntity->getGlobalID(),0,3),$linkedFromAnchorEntity->getID(),'linkedAnoIDsByType',$linkedAnoIDsByType);
  }
  clearEntityAnnoCache($linkedFromAnchorEntity);
  invalidateCachedEntityInfo($linkedFromAnchorEntity);
}
if ((count($errors) == 0) && $linkedToEntity) {//update all annotations for the linked to entity
  $linkedByAnoIDsByType = $linkedToEntity->getLinkedByAnnotationsByType();
  if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
    addUpdateEntityReturnData(substr($linkedToEntity->getGlobalID(),0,3),$linkedToEntity->getID(),'linkedByAnoIDsByType',$linkedByAnoIDsByType);
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

function clearEntityAnnoCache ($entity) {
  if ($entity->getScratchProperty('fnTextByAnoTag') ||
      $entity->getScratchProperty('fnHtml')) {
    $entity->storeScratchProperty('fnTextByAnoTag',null);
    $entity->storeScratchProperty('fnHtml',null);
    $entity->save();
  }
}
?>
