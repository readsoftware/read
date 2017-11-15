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
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read> <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/clientDataUtils.php');//get utilies
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once dirname(__FILE__) . '/../model/entities/Term.php';
require_once dirname(__FILE__) . '/../model/entities/Terms.php';

header('Content-type: text/javascript');
header('Cache-Control: no-transform,private,max-age=300,s-maxage=900');

$jsonRetVal = null;
// check for cache
$dbMgr = new DBManager();
if ($dbMgr->getError()) {
  exit("Error: ".$dbMgr->getError());
}
$dbMgr->query("SELECT * FROM jsoncache WHERE jsc_label = 'Anotations'");
$jsonCache = null;
if ($dbMgr->getRowCount() > 0 && USECACHE) {
  $row = $dbMgr->fetchResultRow();
  $jsonCache = new JsonCache($row);
  if (!$jsonCache->hasError() && !$jsonCache->isDirty()) {
    $jsonRetVal = $jsonCache->getJsonString();
  }
}

if (!$jsonRetVal) {
  $entities = array('update'=>array(),'insert'=>array());
  calcAnnotationsByEntityByType();
  calcTagsByEntityType();
  calcRelatedEntityByLinkType();
  $jsonRetVal = json_encode(array("entities" => $entities));

  if (USECACHE) {
    if (!$jsonCache) {
      $jsonCache = new JsonCache();
      $jsonCache->setLabel('Anotations');
      $jsonCache->setJsonString($jsonRetVal);
      $jsonCache->setVisibilityIDs(array(6));
    } else {
      $jsonCache->clearDirty();
      $jsonCache->setJsonString($jsonRetVal);
    }
    $jsonCache->save();
  }
}

if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".$jsonRetVal.");";
  }
} else {
  print $jsonRetVal;
}


  function calcAnnotationsByEntityByType() {
    global $entities;

    $annotations = new Annotations("ano_linkfrom_ids is not null and ano_linkto_ids is null and not ano_owner_id = 1","ano_type_id,modified");
    if ($annotations->getCount()>0){
      foreach ($annotations as $annotation){
        $curType = $annotation->getTypeID();
        $anoID = $annotation->getID();
        $linkFromGIDs = $annotation->getLinkFromIDs();
        addNewEntityReturnData('ano',$annotation);
        if (count($linkFromGIDs) > 0) {
          foreach ($linkFromGIDs as $gid) {
            list($prefix,$id) = explode(":",$gid);
            if (!isset($entities['update'][$prefix])) {
              $entities['update'][$prefix] = array();
            }
            if (!isset($entities['update'][$prefix][$id])) {
              $entities['update'][$prefix][$id] = array();
            }
            if (!isset($entities['update'][$prefix][$id]['linkedAnoIDsByType'])) {
              $entities['update'][$prefix][$id]['linkedAnoIDsByType'] = array();
            }
            if (!isset($entities['update'][$prefix][$id]['linkedAnoIDsByType'][$curType])) {
              $entities['update'][$prefix][$id]['linkedAnoIDsByType'][$curType] = array($anoID);
            }else{
              array_push($entities['update'][$prefix][$id]['linkedAnoIDsByType'][$curType],$anoID);
            }
          }
        }
      }
    }
  }

  function calcRelatedEntityByLinkType() {
    global $entities;

    $annotations = new Annotations("ano_linkfrom_ids is not null and ano_linkto_ids is not null and not ano_owner_id = 1","ano_type_id,modified");
    if ($annotations->getCount()>0){
      foreach ($annotations as $annotation){
        $curType = $annotation->getTypeID();
        $anoID = $annotation->getID();
        $linkFromGIDs = $annotation->getLinkFromIDs();
        $linkToGIDs = $annotation->getLinkToIDs();
        addNewEntityReturnData('ano',$annotation);
        if (count($linkFromGIDs) > 0) {
          foreach ($linkFromGIDs as $gid) {
            list($prefix,$id) = explode(":",$gid);
            if (!isset($entities['update'][$prefix])) {
              $entities['update'][$prefix] = array();
            }
            if (!isset($entities['update'][$prefix][$id])) {
              $entities['update'][$prefix][$id] = array();
            }
            if (!isset($entities['update'][$prefix][$id]['relatedEntGIDsByType'])) {
              $entities['update'][$prefix][$id]['relatedEntGIDsByType'] = array();
            }
            if (!isset($entities['update'][$prefix][$id]['relatedEntGIDsByType'][$curType])) {
              $entities['update'][$prefix][$id]['relatedEntGIDsByType'][$curType] = $linkToGIDs;
            }else{
              $entities['update'][$prefix][$id]['relatedEntGIDsByType'][$curType] = array_merge($entities['update'][$prefix][$id]['relatedEntGIDsByType'][$curType],$linkToGIDs);
            }
          }
        }
      }
    }
  }

  function calcTagsByEntityType() {
    global $entities;
    $annotations = new Annotations("ano_linkto_ids is not null and ano_linkfrom_ids is null and not ano_owner_id = 1","ano_type_id,modified");
    if ($annotations->getCount()>0){
      foreach ($annotations as $annotation){
        $curType = $annotation->getTypeID();
        $anoID = $annotation->getID();
        $linkToGIDs = $annotation->getLinkToIDs();
        addNewEntityReturnData('ano',$annotation);
        if (count($linkToGIDs) > 0) {
          foreach ($linkToGIDs as $gid) {
            list($prefix,$id) = explode(":",$gid);
            if (!isset($entities['update'][$prefix])) {
              $entities[$prefix] = array();
            }
            if (!isset($entities['update'][$prefix][$id])) {
              $entities['update'][$prefix][$id] = array();
            }
            if (!isset($entities['update'][$prefix][$id]['linkedByAnoIDsByType'])) {
              $entities['update'][$prefix][$id]['linkedByAnoIDsByType'] = array();
            }
            if (!isset($entities['update'][$prefix][$id]['linkedByAnoIDsByType'][$curType])) {
              $entities['update'][$prefix][$id]['linkedByAnoIDsByType'][$curType] = array($anoID);
            }else{
              array_push($entities['update'][$prefix][$id]['linkedByAnoIDsByType'][$curType],$anoID);
            }
          }
        }
      }
    }
  }


?>
