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
* saveImage
*
* saves Image entity data
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
ob_start();

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
  $image = null;
  $imgID = null;
  $imgGID = null;
  $cmd = null;
  if ( isset($data['cmd'])) {//get command
    $cmd = $data['cmd'];
    if ( isset($data['imgID'])) {//get existing image
      $imgID = $data['imgID'];
    }
    $title = null;
    if ( isset($data['title'])) {//get title for image
      $title = $data['title'];
    }
    $url = null;
    if ( isset($data['url'])) {//get url for image
      $url = $data['url'];
    }
    $typeID = null;
    if ( isset($data['typeID'])) {//get typeID for image
      $typeID = $data['typeID'];
    }
    $vis = null;
    if ( isset($data['vis'])) {//get vis
      if ( $data['vis'] == "User"){
        $vis = array(3);
      }
      if ( $data['vis'] == "Public"){
        $vis = array(6);
      }
      if ( $data['vis'] == "Private"){
        $vis =$defVisIDs;
      }
    }
    $containerEntity = null;
    if ( isset($data['containerEntTag'])) {//get entity
      $containerEntity = EntityFactory::createEntityFromGlobalID($data['containerEntTag']);
      if ($containerEntity->hasError()) {
        array_push($errors,"creating linked entity - ".join(",",$containerEntity->getErrors()));
      }
    }
  } else {
    array_push($errors,"saveImage requires a command - aborting save");
  }
}

if (count($errors) == 0) {
  switch ($cmd) {
    case "createImg":
      if (!$containerEntity || !$url ) {
        array_push($errors,"insufficient data to create image");
      } else if ($containerEntity->isReadonly()) {
        array_push($errors,"insufficient permissions to update $contType container with new Images");
      } else if (!method_exists($containerEntity,'getImageIDs')) {
        $contType = $containerEntity->getEntityType();
        array_push($errors,"$contType container doesn't have Images");
      } else {//create image
        $image = new Image();
        if (!$typeID){
         $typeID = Entity::getIDofTermParentLabel("reconstructedsurface-imagetype");//term dependency
        }
        $image->setTypeID($typeID);
        $image->setOwnerID($defOwnerID);
        $image->setVisibilityIDs($vis);
        if ($defAttrIDs){
          $image->setAttributionIDs($defAttrIDs);
        }
        if ($title){
          $image->setTitle($title);
        }
        if ($url){
          $image->setURL($url);
        }
        $image->save();
        if ($image->hasError()) {
          array_push($errors,"error creating image '".$image->getTitle()."' - ".$image->getErrors(true));
        }else{
          $image = new Image($image->getID());//reread image from DB
          addNewEntityReturnData('img',$image);
          $imageIDs = $containerEntity->getImageIDs();
          if (!($imageIDs && count($imageIDs) > 0)) {
            $imageIDs = array();
          }
          array_push($imageIDs,$image->getID());
          $containerEntity->setImageIDs($imageIDs);
          $containerEntity->save();
          if ($containerEntity->hasError()) {
            array_push($errors,"error updating $contType image IDs - ".$containerEntity->getErrors(true));
          }else{
            addUpdateEntityReturnData($containerEntity->getEntityTypeCode(),$containerEntity->getID(),'imageIDs', $containerEntity->getImageIDs());
          }
        }
      }
      break;
    case "removeImg":
      if (!$containerEntity || !$imgID ) {
        array_push($errors,"insufficient data to remove image $imgID");
      } else if ($containerEntity->isReadonly()) {
        $contType = $containerEntity->getEntityType();
        array_push($errors,"insufficient permissions to Images from $contType container");
      } else if (!method_exists($containerEntity,'getImageIDs')) {
        $contType = $containerEntity->getEntityType();
        array_push($errors,"$contType container doesn't have Images");
      } else {//remove image
        $imageIDs = $containerEntity->getImageIDs();
        $indexImage = array_search($imgID,$imageIDs);
        if ($indexImage !== false) {
          array_splice($imageIDs,$indexImage,1);
          $containerEntity->setImageIDs($imageIDs);
          $containerEntity->save();
          if ($containerEntity->hasError()) {
            array_push($errors,"error unlinking image $imgID from '".$containerEntity->getValue()."' - ".$containerEntity->getErrors(true));
          }else{
            addUpdateEntityReturnData($containerEntity->getEntityTypeCode(),$containerEntity->getID(),'imageIDs', $containerEntity->getImageIDs());
          }
        } else {
          array_push($errors,"error removing image unable to find id $imgID in $contType ");
        }
      }
      break;
    case "updateImg":
      if (!$imgID ) {
        array_push($errors,"insufficient data to update image");
      } else {//create image
        $image = new Image($imgID);
        if ($image->hasError()) {
          array_push($errors,"error creating image $imgID - ".$image->getErrors(true));
        } else if ($image->isReadonly()) {
          array_push($errors,"error insufficient permissions for img:$imgID ");
        } else {
          if ($vis){
            $image->setVisibilityIDs($vis);
            $vis = $image->getVisibilityIDs();
            if (in_array(6,$vis)) {
              $vis = "Public";
            } else if (in_array(3,$vis)) {
              $vis = "User";
            } else {
              $vis = "Private";
            }
            addUpdateEntityReturnData('img',$image->getID(),'vis',$vis);
          }
          if ($title){
            $image->setTitle($title);
            addUpdateEntityReturnData('img',$image->getID(),'title',$image->getTitle());
            addUpdateEntityReturnData('img',$image->getID(),'value',$image->getTitle());
          }
          if ($url){
            $image->setURL($url);
            addUpdateEntityReturnData('img',$image->getID(),'url',$image->getURL());
          }
          if ($typeID){
            $image->setTypeID($typeID);
            addUpdateEntityReturnData('img',$image->getID(),'typeID',$image->getTypeID());
          }
          $image->save();
          if ($image->hasError()) {
            array_push($errors,"error creating annotation '".$image->getTitle()."' - ".$image->getErrors(true));
          } else {
            $image = new Annotation($image->getID());//reread anotation from DB
            addUpdateEntityReturnData('img',$image->getID(),'modStamp',$image->getModificationStamp());
          }
        }
      }
      break;
    default:
      array_push($errors,"unknown command for saveImage");
  }
}
$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
//  invalidateCache('AllTextResources'.getUserDefEditorID());
//  invalidateCache('SearchAllResults'.getUserDefEditorID());
//  invalidateCache('AllTextResources');
//  invalidateCache('SearchAllResults');
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
}
if (count($entities)) {
  $retVal["entities"] = $entities;
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
