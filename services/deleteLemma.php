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
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Inflection.php');
require_once (dirname(__FILE__) . '/../model/entities/Lemmas.php');//include Lemma.php
require_once (dirname(__FILE__) . '/../model/entities/Catalogs.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $catID = null;
  $catalog = null;
  $lemID = null;
  $lemma = null;
  if ( isset($data['lemID'])) {//get lemma
    $lemma = new Lemma($data['lemID']);
    if ($lemma->hasError()) {
      array_push($errors,"creating lemma - ".join(",",$lemma->getErrors()));
    } else {
      $lemID = $lemma->getID();
      if (!$catID) {
        $catalog = new Catalog($lemma->getCatalogID());
        if ($catalog->hasError()) {
          array_push($errors,"creating catalog - ".join(",",$catalog->getErrors()));
        } else {
          $catID = $catalog->getID();
        }
      } else if ($lemma->getCatalogID() != $catID) {
        array_push($errors,"lemma doesn't belong to glossary");
      }
    }
  }
  if ($catalog && $catalog->isReadonly()) {
    array_push($errors,"insufficient previledges for Glossary Change");
  }
  if ($lemma && $lemma->isReadonly()) {
    array_push($errors,"insufficient previledges to delete Lemma");
  }
  if (!$lemma ) {
    array_push($errors,"valid lemma id required for delete Lemma");
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}
if (count($errors) == 0) {
  $lemmaAnnoIDs = $lemma->getAnnotationIDs();
  $lemmaAnnotations = $lemma->getAnnotations(true);
  if ($lemmaAnnotations && $lemmaAnnotations->getCount()) {
    foreach ($lemmaAnnotations as $annotation) {
      if (!$annotation->isReadonly()) {
        $annotation->markForDelete();
        $index = array_search($annotation->getID(),$lemmaAnnoIDs);
        array_splice($lemmaAnnoIDs,$index,1);
        addRemoveEntityReturnData('ano',$annotation->getID());
      }
    }
  }
  if (count($lemmaAnnoIDs)) {
    array_push($warnings,"readonly annotaions found for lemma id $lemID : ".join(',',$lemmaAnnoIDs));
  }
  addUpdateEntityReturnData('lem',$lemID,'annotationIDs',$lemmaAnnoIDs);
  $lemGID = $lemma->getGlobalID();
  //remove lemGID from any tags (linkeToGIDs of annotation entities)
  $tagAnnotations = new Annotations("'$lemGID' = ANY(ano_linkto_ids) and ano_linkfrom_ids is null and not ano_owner_id = 1","ano_type_id,modified");
  if ($tagAnnotations->getCount()) {
    foreach ($tagAnnotations as $annotation) {
      if (!$annotation->isReadonly()) {
        $tagLinkToIDs = $annotation->getLinkToIDs();
        $index = array_search($lemGID,$tagLinkToIDs);
        array_splice($tagLinkToIDs,$index,1);
        if (count($tagLinkToIDs)) {
          $annotation->setLinkToIDs($tagLinkToIDs);
          $annotation->save();
          addUpdateEntityReturnData('ano',$annotation->getID(),'linkedToIDs',$tagLinkToIDs);
        }else{
          $annotation->markForDelete();
          addRemoveEntityReturnData('ano',$annotation->getID());
        }
      } else {
        array_push($warnings,"readonly tag annotaion".$annotation->getID()." found for lemma id $lemID ");
        error_log("warning: readonly tag annotaion".$annotation->getID()." found for lemma id $lemID ");
      }
    }
  }
  //delete any inflections
  $components = $lemma->getComponents(true);
  if ($components) {
    foreach ($components as $component) {
      if ($component->getEntityType() == 'inflection') {
        $component->markForDelete();
        // and remove from local cache
        addRemoveEntityReturnData('inf',$component->getID());
      }
    }
  }
  //delete any linked from annotation
  $noteAnnotations = new Annotations("'$lemGID' = ANY(ano_linkfrom_ids) and ano_linkto_ids is null and not ano_owner_id = 1","ano_type_id,modified");
  if ($noteAnnotations->getCount()) {
    foreach ($noteAnnotations as $annotation) {
      if (!$annotation->isReadonly()) {
        $annotation->markForDelete();
        // and remove from local cache
        addRemoveEntityReturnData('ano',$annotation->getID());
      } else {
        array_push($warnings,"readonly tag annotaion".$annotation->getID()." found for lemma id $lemID ");
        error_log("warning: readonly tag annotaion".$annotation->getID()." found for lemma id $lemID ");
      }
    }
  }
  //delete any linked from annotation
  $links = new Annotations("('$lemGID' = ANY(ano_linkfrom_ids) or '$lemGID' = ANY(ano_linkto_ids)) ".
                                      "and ano_linkto_ids is not null and ano_linkfrom_ids is not null and ".
                                      "not ano_owner_id = 1","ano_type_id,modified");
  if ($links->getCount()) {
    foreach ($links as $annotation) {
      if (!$annotation->isReadonly()) {
        $anoID = $annotation->getID();
        $linkToGIDs = $annotation->getLinkToIDs();
        $index = array_search($lemGID,$linkToGIDs);
        if ($index !== false) {
          array_splice($linkToGIDs,$index,1);
          $updateLinkFromRelatedLinks = true;
        } else {
          $updateLinkFromRelatedLinks = false;
        }
        $linkFromGIDs = $annotation->getLinkFromIDs();
        $index = array_search($lemGID,$linkFromGIDs);
        if ($index !== false) {
          array_splice($linkFromGIDs,$index,1);
        }
        if (count($linkToGIDs) and count($linkFromGIDs)) {
          $annotation->setLinkToIDs($linkToGIDs);
          $annotation->setLinkFromIDs($linkFromGIDs);
          $annotation->save();
          addUpdateEntityReturnData('ano',$annotation->getID(),'linkedToIDs',$linkToGIDs);
          addUpdateEntityReturnData('ano',$annotation->getID(),'linkedFromIDs',$linkFromGIDs);
        }else{
          $annotation->markForDelete();
          addRemoveEntityReturnData('ano',$annotation->getID());
        }
        if ($updateLinkFromRelatedLinks and count($linkFromGIDs)) {//unlinked sub lemma so need to update the linkRelated
          foreach ($linkFromGIDs as $linkFromGID) {
            $subLemma = new Lemma(substr($linkFromGID,4));
            if (!$subLemma->hasError()) {
              $lemAnnoIDs = $subLemma->getAnnotationIDs();
              $index = array_search($anoID,$lemAnnoIDs);
              if ($index !== false) {
                array_splice($lemAnnoIDs,$index,1);
                $subLemma->setAnnotationIDs($lemAnnoIDs);
                addUpdateEntityReturnData('lem',$subLemma->getID(),'annotationIDs',$subLemma->getAnnotationIDs());
                $subLemma->save();
              }
              addUpdateEntityReturnData('lem',$subLemma->getID(),'relatedEntGIDsByType',$subLemma->getRelatedEntitiesByLinkType());
            }
          }
        }

      } else {
        array_push($warnings,"readonly link annotaion".$annotation->getID()." found for lemma id $lemID ");
        error_log("warning: readonly link annotaion".$annotation->getID()." found for lemma id $lemID ");
      }
    }
  }
  $lemCatID = $lemma->getCatalogID();
  //delete lemma
  $lemma->markForDelete();
  // and remove from local cache
  addRemoveEntityReturnData('lem',$lemma->getID());
  invalidateCachedViewerLemmaHtmlLookup($lemCatID,null);

  //update catalog info
  $lemmas = new Lemmas("lem_catalog_id = $lemCatID and not lem_owner_id = 1","lem_id",null,null);
  if ($lemmas && !$lemmas->getError()){
    $catLemIDs = array();
    if ($lemmas->getCount()>0) {
      foreach($lemmas as $catLemma){
        array_push($catLemIDs, $catLemma->getID());
      }
    }
    addUpdateEntityReturnData('cat',$lemCatID,"lemIDs",$catLemIDs);
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
if ($healthLogging && $catalog) {
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
