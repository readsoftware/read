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
  $forceLemmaDelete = false;
  if ( isset($data['force'])) {//get force param
    $forceLemmaDelete = true;
  }
  if ( isset($data['catID'])) {//get lemma
    $catID = $data['catID'];
    $catalog = new Catalog($catID);
    if ($catalog->hasError()) {
      array_push($errors,"loading catalog/glossary - ".join(",",$catalog->getErrors()));
    }
  }
  if (!$catID) {
    array_push($errors,"insufficient information for deleting Catalog/Glossary");
  }
  if ($catalog && $catalog->isReadonly()) {
    array_push($errors,"insufficient previledges for deleting Catalog/Glossary");
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}

if (count($errors) == 0) {
  //check for lemmas
  $lemmas = new Lemmas("lem_catalog_id = $catID and not lem_owner_id = 1","lem_id",null,null);
  if (!@$lemmas || $lemmas->getError()) {
    array_push($errors,"error loading lemma for catalog ID $catID".$lemmas->getError());
  } else if ($lemmas->getCount()>0){
    if (!$forceLemmaDelete){
      array_push($errors,"error catalog '".$catalog->getTitle()."' (ID: $catID) has linked Lemma aborting catalog delete");
    } else {
      $lemmas->setAutoAdvance(false); // make sure the iterator doesn't prefetch
      $cntROLemma = 0;
      $catLemIDs = array();
      foreach ($lemmas as $lemma) {
        if ($lemma->isReadonly()) {
          $cntROLemma++;
        }
        array_push($catLemIDs,$lemma->getID());
      }
      if ($cntROLemma == 0){//all lemma deletable so process them first
        $catalog->storeScratchProperty("restoreLemIDs",join(",",$catLemIDs));//save lemIDs for undo when we implement it
        $lemmas->rewind();
        foreach ($lemmas as $lemma) {
          $lemmaAnnoIDs = $lemma->getAnnotationIDs();
          $lemmaAnnotations = $lemma->getAnnotations(true);
          if ($lemmaAnnotations && $lemmaAnnotations->getCount()) {
            //TODO add code to store anoIDs in lemma scratch
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
            array_push($warnings,"readonly annotations found for lemma id $lemID : ".join(',',$lemmaAnnoIDs));
          }
          addUpdateEntityReturnData('lem',$lemID,'annotationIDs',$lemmaAnnoIDs);
          $lemGID = $lemma->getGlobalID();
          //remove lemGID from any tags (linkeToGIDs of annotation entities)
          $tagAnnotations = new Annotations("'$lemGID' = ANY(ano_linkto_ids) and ano_linkfrom_ids is null and not ano_owner_id = 1","ano_type_id,modified");
          if ($tagAnnotations->getCount()) {
            //TODO consider if there is a method for undo, what info to save and where
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
                array_push($warnings,"readonly tag annotation".$annotation->getID()." found for lemma id $lemID ");
                error_log("warning: readonly tag annotation".$annotation->getID()." found for lemma id $lemID ");
              }
            }
          }
          //delete any inflections
          $components = $lemma->getComponents(true);
          if ($components && $components->getCount()>0) {
            $components->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            $cntROInfl = 0;
            $lemInfIDs = array();
            foreach ($components as $component) {
              if ($component->getEntityType() == 'inflection') {
                if ($component->isReadonly()) {
                  $cntROInfl++;
                }
                array_push($lemInfIDs,$component->getID());
              }
            }
            if ($cntROLemma == 0){//all inflection deletable so process them
              $lemma->storeScratchProperty("restoreInfIDs",join(",",$lemInfIDs));//save infIDs for undo when we implement it
              $components->rewind();
              foreach ($components as $component) {
                if ($component->getEntityType() == 'inflection') {
                  $component->markForDelete();
                  // and remove from local cache
                  addRemoveEntityReturnData('inf',$component->getID());
                }
              }
            } else {
              array_push($warnings,"readonly inflections found for lemma id $lemID not deleting or or unlinking");
              error_log("warning: readonly inflections found for lemma id $lemID not deleting or or unlinking");
            }
          }
          //delete any linked from annotation (tags)
          $noteAnnotations = new Annotations("'$lemGID' = ANY(ano_linkfrom_ids) and ano_linkto_ids is null and not ano_owner_id = 1","ano_type_id,modified");
          if ($noteAnnotations->getCount()) {
            foreach ($noteAnnotations as $annotation) {
              if (!$annotation->isReadonly()) {
                $annotation->markForDelete();
                // and remove from local cache
                addRemoveEntityReturnData('ano',$annotation->getID());
              } else {
                array_push($warnings,"readonly tag annotation".$annotation->getID()." found for lemma id $lemID ");
                error_log("warning: readonly tag annotation".$annotation->getID()." found for lemma id $lemID ");
              }
            }
          }
          //delete any linking annotation
          $links = new Annotations("('$lemGID' = ANY(ano_linkfrom_ids) or '$lemGID' = ANY(ano_linkto_ids)) ".
                                              "and ano_linkto_ids is not null and ano_linkfrom_ids is not null and ".
                                              "not ano_owner_id = 1","ano_type_id,modified");
          if ($links && $links->getError()) {
            array_push($warnings,"warning loading links annotations for $lemGID unable to unlink any linked entities - ".$links->getError());
            error_log("warning: error loading links annotations for $lemGID unable to unlink any linked entities - ".$links->getError());
          } else if ($links->getCount() > 0) {
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
                if (count($linkToGIDs) && count($linkFromGIDs)) {
                  $annotation->setLinkToIDs($linkToGIDs);
                  $annotation->setLinkFromIDs($linkFromGIDs);
                  $annotation->save();
                  addUpdateEntityReturnData('ano',$annotation->getID(),'linkedToIDs',$linkToGIDs);
                  addUpdateEntityReturnData('ano',$annotation->getID(),'linkedFromIDs',$linkFromGIDs);
                }else{
                  $annotation->markForDelete();
                  addRemoveEntityReturnData('ano',$annotation->getID());
                }
                if ($updateLinkFromRelatedLinks && count($linkFromGIDs)) {//unlinked sub lemma so need to update the linkRelated
                  foreach ($linkFromGIDs as $linkFromGID) {
                    $subLemma = new Lemma(substr($linkFromGID,4));
                    if (!$subLemma->hasError() && !$subLemma->isReadonly()) {
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
                array_push($warnings,"readonly link annotation".$annotation->getID()." found for lemma id $lemID ");
                error_log("warning: readonly link annotation".$annotation->getID()." found for lemma id $lemID ");
              }
            }
          }
          //delete lemma
          $lemma->markForDelete();
          // and remove from local cache
          addRemoveEntityReturnData('lem',$lemma->getID());
        }
      }
    }
  }
}

if (count($errors) == 0) {
  $catEdnIDs = $catalog->getEditionIDs();
  //delete catalog
  $catalog->markForDelete();
  // and remove from local cache
  addRemoveEntityReturnData('cat',$catalog->getID());
  //update edition information
  if (count($catEdnIDs)){
    foreach ($catEdnIDs as $catEdnID) {
      $catalogs = new Catalogs("$catEdnID = ANY(cat_edition_ids) and not cat_owner_id = 1","cat_id",null,null);
      if ($catalogs && !$catalogs->getError()){
        $ednCatIDs = array();
        if ($catalogs->getCount()>0) {
          foreach($catalogs as $ednCatalog){
            array_push($ednCatIDs, $ednCatalog->getID());
          }
        }
        addUpdateEntityReturnData('edn',$catEdnID,"catIDs",$ednCatIDs);
      }
    }
  }
}

/*
addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
} else {
addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs());
addUpdateEntityReturnData('tok',$token->getID(),'sort', $token->getSortCode());
addUpdateEntityReturnData('tok',$token->getID(),'sort2', $token->getSortCode2());
}
addNewEntityReturnData('tok',$newSplitToken);
if (isset($newSplitToken2)) {
addNewEntityReturnData('tok',$newSplitToken2);
}

//update compound heirarchy
if (count($errors) == 0 && isset($compounds) && count($compounds) > 0) {//update compounds
while (count($compounds)) {
$compound = array_shift($compounds);
$componentGIDs = $compound->getComponentIDs();
$oldTokCmpIndex = array_search($oldTokCmpGID,$componentGIDs);
array_splice($componentGIDs,$oldTokCmpIndex,1,$tokCmpReplaceGIDs);
$tokCmpReplaceGIDs = $componentGIDs;
$oldTokCmpGID = $compound->getGlobalID();
if (!$compound->isReadonly()) {
$compound->markForDelete();
addRemoveEntityReturnData('cmp',$compound->getID());
}
if ($compound->hasError()) {
array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
break;
}
}
}
$newTokCmpGID = $tokCmpReplaceGIDs;//ensure this gets altered in the text div
}

}else { // only updated
addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
}
//update edition seqIDs
$edition->setSequenceIDs($edSeqIds);
$edition->save();
if ($edition->hasError()) {
array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
}else{
addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
}
*/

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
