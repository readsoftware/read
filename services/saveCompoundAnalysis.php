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
require_once (dirname(__FILE__) . '/../model/entities/Lemmas.php');//include Lemma.php
require_once (dirname(__FILE__) . '/../model/entities/Catalogs.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$rootNode = null;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  if ( isset($data['compAnalysis'])) {//get command
    $analysis = $data['compAnalysis'];
    if ($analysis) {
      if ( isset($data['rootkey'])) {//get rootkey
        $rootkey = $data['rootkey'];
        if (array_key_exists($rootkey,$analysis)) {
          $rootNode = $analysis[$rootkey];
        }
      }
      if (!$rootNode) {
        foreach($analysis as $key => $node) {
          if (array_key_exists('root',$node)) {
            $rootkey = $key;
            $rootNode = $node;
            break;
          }
        }
      }
      if (!$rootNode) {
        array_push($errors,"saveCompoundAnalysis requires the root node to be marked - aborting save");
      }
    }
  } else {
    array_push($errors,"saveCompoundAnalysis requires an analysis structure - aborting save");
  }
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $lemID = null;
  if ( isset($data['lemID'])) {//get lemma id
    $lemID = $data['lemID'];
  } else if ($rootNode && $rootNode['lemID']){//todo warn about multiple
    //get from root lemma
    $lemID = $rootNode['lemID'];
  }
  $rootLemma = null;
  $rootLemID = null;
  if ($lemID) {
    $rootLemma = new Lemma($lemID);
    if ($rootLemma->hasError()) {
      array_push($errors,"creating lemma with id $lemID - ".join(",",$rootLemma->getErrors()));
      $rootLemma = null;
    }
    $rootLemID = $lemID;
  }
  $catID = null;
  $catalog = null;
  if ( isset($data['catID'])) {
    $catID = $data['catID'];
  } else if ($rootLemma) { //get from lemma
      $catID = $rootLemma->getCatalogID();
  }
  if ($catID) {//get catalog
    $catalog = new Catalog($catID);
    if ($catalog->hasError()) {
      array_push($errors,"creating catalog with id $catID - ".join(",",$catalog->getErrors()));
      $catalog = null;
    }
  }
  if (!$catalog || $catalog && $catalog->isReadonly()) {
    array_push($errors,"unable to access Glossary");
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}
if (count($errors) == 0) {
  if (!$analysis) {
    if ($rootLemma) {
      $rootLemma->setCompoundAnalysis(null);
      $rootLemma->save();
      addUpdateEntityReturnData('lem',$lemID,'compAnalysis',null);
    }
  } else {
    //iterate through nodes to load or create lemma,
    //update compound analysis if needed,
    //create lemma lookup by nodeID
    $lemmaByNodeID = array();
    foreach ($analysis as $nodeID => $node) {
      if (array_key_exists('lemID',$node)) {//user supplied match for lemma so open it
        $lemma = new Lemma($node['lemID']);
        if ($lemma->hasError()) {
          array_push($errors,"error creating lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
          $lemma = null;
        }
      } else { //create new
        $lemma = new Lemma();
        $lemma->setCatalogID($catID);
        $lemma->setOwnerID($defOwnerID);
        $lemma->setVisibilityIDs($defVisIDs);
        if ($defAttrIDs){
          $lemma->setAttributionIDs($defAttrIDs);
        }
        $lemma->setValue($node['value']);
      }
      if (array_key_exists('subKeys',$node) && array_key_exists('markup',$node)){
        $lemma->setCompoundAnalysis($node['markup']);
      }
      $lemmaByNodeID[$nodeID] = $lemma;
    }
    if (count($errors) == 0) {
      //iterate through nodes
      //save lemma
      foreach($lemmaByNodeID as $key => $lemma) {
        if ($lemma->isDirty()) {
          $lemID = $lemma->getID();
          $lemma->save();
          if ($lemma->hasError()) {
            array_push($errors,"error creating lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
          }else{
            if ($lemID != $lemma->getID()) {
              $lemma->storeTempProperty('isNew',1);
              if (array_key_exists('lemID',$analysis[$key])) {
                array_push($errors,"error node $key has lemID and detected as new lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
              } else {
                $analysis[$key]['lemID'] = $lemma->getID();
              }
            }
          }
        }
      }
      //iterate through nodes
      //if subkeys then create links checking subnodes isHead
      $headConstituentLinkTypeID = Entity::getIDofTermParentLabel('CompoundConstituentHead-LinkageType');
      $constituentLinkTypeID = Entity::getIDofTermParentLabel('CompoundConstituent-LinkageType');
      $seeLinkTypeID = Entity::getIDofTermParentLabel('See-LinkageType');
      foreach( $analysis as $key => $node) {
        $lemma = $lemmaByNodeID[$key];
        //create see links from node to rootnode
        if ($rootLemID && !array_key_exists('root',$node)) {
          $toLemGID = 'lem:'.$rootLemID;
          $fromLemGID = 'lem:'.$lemma->getID();
          $annoLink = createRelationshipLink($fromLemGID,$toLemGID,$seeLinkTypeID);
          if ($annoLink->hasError()) {
            array_push($errors,"error creating relation link between $fromLemGID and $toLemGID '".$annoLink->getValue()."' - ".$annoLink->getErrors(true));
            $annoLink = null;
          } else {
            addNewEntityReturnData('ano',$annoLink);
            if (!$lemma->isReadOnly()) {
              $annoIDs = $lemma->getAnnotationIDs();
              if (count($annoIDs)) {
                if (!in_array($annoLink->getID(),$annoIDs)) {
                  array_push($annoIDs,$annoLink->getID());
                }
              } else {
                $annoIDs = array($annoLink->getID());
              }
              $lemma->setAnnotationIDs($annoIDs);
              $lemma->save();
              if ($lemma->hasError()) {
                array_push($errors,"error adding relation link to $fromLemGID  '".$lemma->getValue()."' - ".$lemma->getErrors(true));
              } else {
                $lemma->storeTempProperty('linked',1);
              }
            }
          }
        }
        if (array_key_exists('subKeys',$node)) {//compound so create links for each constituent/sub lemma
          //create compound analysis links
          $toLemGID = 'lem:'.$lemma->getID();
          foreach($node['subKeys'] as $subKey) {
            $subLemma = $lemmaByNodeID[$subKey];
            $fromLemGID = 'lem:'.$subLemma->getID();
            $linkType = array_key_exists('head',$analysis[$subKey])?$headConstituentLinkTypeID:$constituentLinkTypeID;
            $annoLink = createRelationshipLink($fromLemGID,$toLemGID,$linkType);
            if ($annoLink->hasError()) {
              array_push($errors,"error creating relation link between $fromLemGID and $toLemGID '".$annoLink->getValue()."' - ".$annoLink->getErrors(true));
              $annoLink = null;
            } else {
              addNewEntityReturnData('ano',$annoLink);
              if (!$subLemma->isReadOnly()) {
                $annoIDs = $subLemma->getAnnotationIDs();
                if (count($annoIDs)) {
                  if (!in_array($annoLink->getID(),$annoIDs)) {
                    array_push($annoIDs,$annoLink->getID());
                  }
                } else {
                  $annoIDs = array($annoLink->getID());
                }
                $subLemma->setAnnotationIDs($annoIDs);
                $subLemma->save();
                if ($subLemma->hasError()) {
                  array_push($errors,"error adding relation link to $fromLemGID  '".$subLemma->getValue()."' - ".$subLemma->getErrors(true));
                } else {
                  $subLemma->storeTempProperty('linked',1);
                }
              }
            }
          }
        }
      }
      foreach($lemmaByNodeID as $key => $lemma) {
        if ($lemma->getTempProperty('isNew')) {
          addNewEntityReturnData('lem',$lemma);
        } else {
          if ($lemma->getCompoundAnalysis()) {
            addUpdateEntityReturnData('lem',$lemma->getID(),'compAnalysis',$lemma->getCompoundAnalysis());
          }
          if ($lemma->getTempProperty('linked')) {
            addUpdateEntityReturnData('lem',$lemma->getID(),'annotationIDs',$lemma->getAnnotationIDs());
            addUpdateEntityReturnData('lem',$lemma->getID(),'relatedEntGIDsByType',$lemma->getRelatedEntitiesByLinkType());
          }
        }
      }

      //update client catalog with lemIDs
      $lemmas = new Lemmas("lem_catalog_id = $catID");
      if ($lemmas->getCount() > 0) {
        $lemIDs = array();
        foreach($lemmas as $lemma) {
          $lemID = $lemma->getID();
          array_push($lemIDs,$lemID);
        }
        addUpdateEntityReturnData('cat',$catID,'lemIDs',$lemIDs);
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
