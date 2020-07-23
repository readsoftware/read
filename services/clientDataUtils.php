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
* clientDataUtils
*
* format utilities for returning entity data to client code
*

* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Utility Classes
* @todo Change this to class clientDataMgr that manages all return data and error recovery.
*/
require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
require_once (dirname(__FILE__) . '/../model/entities/Grapheme.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compound.php');
require_once (dirname(__FILE__) . '/../model/entities/Image.php');
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
require_once (dirname(__FILE__) . '/../model/entities/Surface.php');
require_once (dirname(__FILE__) . '/../model/entities/Segment.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequence.php');
require_once (dirname(__FILE__) . '/../model/entities/Inflection.php');
require_once (dirname(__FILE__) . '/../model/entities/Lemma.php');
require_once (dirname(__FILE__) . '/../model/entities/Catalog.php');
$entities = array();
$tagsInfo =null;


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entity
*/

function addNewEntityReturnData($prefix,$entity) {
  global $entities;
  if (!$entity) {
    return;
  }
  if (!$prefix) {
    $prefix = substr($entity->getGlobalID(),0,3);
  }
  if (!array_key_exists('insert',$entities)) {
    $entities['insert'] = array();
  }
  if (!array_key_exists($prefix,$entities['insert'])) {
    $entities['insert'][$prefix] = array();
  }
  $entID = $entity->getID();
  switch ($prefix) {
    case 'gra':
      $grapheme = $entity;
      $entities['insert']['gra'][$entID] = array( 'value'=> $grapheme->getValue(),
        'id' => $entID,
        'txtcrit' => $grapheme->getTextCriticalMark(),
        'readonly' => $grapheme->isReadonly(),
        'alt' => $grapheme->getAlternative(),
        'emmend' => $grapheme->getEmmendation(),
        'type' => $grapheme->getType(),
        'sort' => $grapheme->getSortCode());
        if ($grapheme->getDecomposition()) {
           $entities['insert']['gra'][$entID]['decomp'] = preg_replace("/\:/",'',$grapheme->getDecomposition());
        }
        $attrIDs = $grapheme->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['gra'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
    case 'scl':
      $syllable = $entity;
      $entities['insert']['scl'][$entID] = array( 'value'=> $syllable->getValue(),
        'txtcrit' => $syllable->getTextCriticalMark(),
        'id' => $entID,
        'modStamp' => $syllable->getModificationStamp(),
        'graphemeIDs' => $syllable->getGraphemeIDs(),
        'segID' => $syllable->getSegmentID(),
        'readonly' => $syllable->isReadonly(),
        'attributionIDs' => $syllable->getAttributionIDs(),
        'sort' => $syllable->getSortCode(),
        'sort2' => $syllable->getSortCode2());
      break;
    case 'tok':
      $token = $entity;
      $entities['insert']['tok'][$entID] = array( 'value'=> $token->getValue(),
        'id' => $entID,
        'modStamp' => $token->getModificationStamp(),
        'transcr' => $token->getTranscription(),
        'affix' => $token->getNominalAffix(),
        'readonly' => $token->isReadonly(),
        'graphemeIDs' => $token->getGraphemeIDs(),
        'sort' => $token->getSortCode(),
        'sort2' => $token->getSortCode2(),
        'attributionIDs' => $token->getAttributionIDs(),
        'syllableClusterIDs' => $token->getSyllableClusterIDs(false,true));
      break;
    case 'cmp':
      $compound = $entity;
      $entities['insert']['cmp'][$entID] = array( 'value'=> $compound->getValue(),
        'id' => $entID,
        'modStamp' => $compound->getModificationStamp(),
        'transcr' => $compound->getTranscription(),
        'entityIDs' => $compound->getComponentIDs(),
        'readonly' => $compound->isReadonly(),
        'case' => $compound->getCase(),
        'class' => $compound->getClass(),
        'type' => $compound->getType(),
        'tokenIDs' => $compound->getTokenIDs(),
        'attributionIDs' => $compound->getAttributionIDs(),
        'sort' => $compound->getSortCode(),
        'sort2' => $compound->getSortCode2());
      break;
    case 'seq':
      $sequence = $entity;
      $entities['insert']['seq'][$entID] = array( 'label'=> $sequence->getLabel(),
        'id' => $entID,
        'value'=> $sequence->getLabel(),
        'entityIDs' => $sequence->getEntityIDs(),
        'readonly' => $sequence->isReadonly(),
        'editibility' => $sequence->getOwnerID(),
        'superscript' => $sequence->getSuperScript(),
        'attributionIDs' => $sequence->getAttributionIDs(),
        'typeID' => $sequence->getTypeID());
        if ($freetext = $sequence->getScratchProperty('freetext')) {
           $entities['insert']['seq'][$entID]['freetext'] = $freetext;
        }
        if ($validationMsg = $sequence->getScratchProperty('validationMsg')) {
           $entities['insert']['seq'][$entID]['validationMsg'] = $validationMsg;
        }
      break;
    case 'srf':
      $surface = $entity;
      $entities['insert']['srf'][$entID] = array(
        'fragmentID'=>$surface->getFragmentID(),
        'id' => $entID,
        'number' => $surface->getNumber(),
        'value' => $surface->getLabel(),
        'label' => $surface->getLabel(),
        'description' => $surface->getDescription(),
        'layer' => $surface->getLayerNumber(),
        'textIDs' => $surface->getTextIDs(),
        'scripts' => $surface->getScripts(),
        'imageIDs' => $surface->getImageIDs(),
        'readonly' => $surface->isReadonly(),
        'attributionIDs' => $surface->getAttributionIDs());
      break;
    case 'frg':
      $fragment = $entity;
      $entities['insert']['frg'][$entID] = array(
        'partID'=>$fragment->getPartID(),
        'id' => $entID,
        'label' => $fragment->getLabel(),
        'value' => $fragment->getLabel(),
        'description' => $fragment->getDescription(),
        'measure' => $fragment->getMeasure(),
        'status' => $fragment->getRestoreState(),
        'locRefs' => $fragment->getLocationRefs(),
        'mcxIDs' => $fragment->getMaterialContextIDs(),
        'imageIDs' => $fragment->getImageIDs(),
        'readonly' => $fragment->isReadonly(),
        'attributionIDs' => $fragment->getAttributionIDs());
      break;
    case 'mcx':
      $materialCtx = $entity;
      $entities['insert']['mcx'][$entID] = array(
        'id' => $entID,
        'archContext' => $materialCtx->getArchContext(),
        'findStatus' => $materialCtx->getFindStatus(),
        'readonly' => $materialCtx->isReadonly(),
        'attributionIDs' => $materialCtx->getAttributionIDs());
      break;
    case 'prt':
      $part = $entity;
      $entities['insert']['prt'][$entID] = array(
        'itemID'=>$part->getItemID(),
        'id' => $entID,
        'type' => $part->getType(),
        'label' => $part->getLabel(),
        'value' => $part->getLabel(),
        'description' => $part->getDescription(),
        'sequence' => $part->getSequence(),
        'shape' => $part->getShape(),
        'mediums' => $part->getMediums(),
        'measure' => $part->getMeasure(),
        'imageIDs' => $part->getImageIDs(),
        'readonly' => $part->isReadonly(),
        'attributionIDs' => $part->getAttributionIDs());
      break;
    case 'itm':
      $item = $entity;
      $entities['insert']['itm'][$entID] = array(
        'id' => $entID,
        'type' => $item->getType(),
        'title' => $item->getTitle(),
        'value' => $item->getTitle(),
        'idno' => $item->getIdNo(),
        'description' => $item->getDescription(),
        'shape' => $item->getShape(),
        'measure' => $item->getMeasure(),
        'imageIDs' => $item->getImageIDs(),
        'editibility' => $item->getOwnerID(),
        'readonly' => $item->isReadonly(),
        'attributionIDs' => $item->getAttributionIDs());
      break;
    case 'bln':
      $baseline = $entity;
      $segIDs = $baseline->getSegIDs();
      $entities['insert']['bln'][$entID] = array(
        'id' => $entID,
        'surfaceID' => $baseline->getSurfaceID(),
        'segCount' => count($segIDs),
        'segIDs' => $segIDs,
        'type' => $baseline->getType(),
        'value' => ($baseline->getURL()?$baseline->getURL():$baseline->getTranscription()),
        'editibility' => $baseline->getOwnerID(),
        'readonly' => $baseline->isReadonly(),
        'transcription' => $baseline->getTranscription());
      $boundary = $baseline->getImageBoundary();
      if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
        $boundary = $boundary[0];
        $entities["insert"]['bln'][$entID]['boundary']= array($boundary->getPoints());
        $entities["insert"]['bln'][$entID]['url']= $segment->getURLs();
      }
      if ($baseline->getImageID()) {
        $entities["insert"]['bln'][$entID]['imageID']= $baseline->getImageID();
        $entities["insert"]['bln'][$entID]['url']= $baseline->getURL();
      }
      break;
    case 'seg':
      $segment = $entity;
      $entities['insert']['seg'][$entID] = array(
        'id' => $entID,
        'baselineIDs' => $segment->getBaselineIDs(),
        'layer' => $segment->getLayer(),
        'editibility' => $segment->getOwnerID(),
        'readonly' => $segment->isReadonly(),
        'center' => $segment->getCenter(),
        'value' => 'seg'.$entID);
      $sclIDs = $segment->getSyllableIDs();
      if ($sclIDs && count($sclIDs) > 0) {
        $entities["insert"]['seg'][$entID]['sclIDs']= $sclIDs;
      }
      $boundary = $segment->getImageBoundary();
      if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
        $entities["insert"]['seg'][$entID]['boundary']= array();
        foreach($boundary as $polygon) {
          array_push($entities["insert"]['seg'][$entID]['boundary'], $polygon->getPoints());
        }
        $entities["insert"]['seg'][$entID]['urls']= $segment->getURLs();
      }
      $stringpos = $segment->getStringPos();
      if ($stringpos && count($stringpos) > 0) {
        $entities["insert"]['seg'][$entID]['stringpos']= $stringpos;
      }
      $mappedSegIDs = $segment->getMappedSegmentIDs();
      if ($mappedSegIDs && count($mappedSegIDs) > 0) {
        $entities["insert"]['seg'][$entID]['mappedSegIDs'] = $mappedSegIDs;
      }
      $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
      if ($segBlnOrder) {
        $entities["insert"]['seg'][$entID]['ordinal'] = $segBlnOrder;
      }
      $segCode = $segment->getScratchProperty("code");
      if ($segCode) {
        $entities["insert"]['seg'][$entID]['code'] = $segCode;
        $entities["insert"]['seg'][$entID]['value'] = $segCode;
      }
      $segLoc = $segment->getScratchProperty("sgnLoc");
      if ($segLoc) {
        $entities["insert"]['seg'][$entID]['loc'] = $segLoc;
      }
      break;
    case 'lem':
      $lemma = $entity;
      $entities['insert']['lem'][$entID] = array( 'value'=> $lemma->getValue(),
        'id' => $entID,
        'readonly' => $lemma->isReadonly(),
        'trans' => $lemma->getTranslation(),
        'search' => $lemma->getSearchValue(),
        'gloss' => $lemma->getDescription(),
        'order' => $lemma->getHomographicOrder(),
        'typeID' => $lemma->getTypeID(),
        'certainty' => $lemma->getCertainty(),
        'catID' => $lemma->getCatalogID(),
        'entityIDs' => $lemma->getComponentIDs(),
        'class' => $lemma->getVerbalClass(),
        'pos' => $lemma->getPartOfSpeech(),
        'spos' => $lemma->getSubpartOfSpeech(),
        'gender' => $lemma->getGender(),
        'decl' => $lemma->getDeclension(),
        'sort' => $lemma->getSortCode(),
        'sort2' => $lemma->getSortCode2());
        if ($lemma->getCompoundAnalysis()) {
          $entities['insert']['lem'][$entID]['compAnalysis'] = $lemma->getCompoundAnalysis();
        }
        $attrIDs = $lemma->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['lem'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
    case 'inf':
      $inflection = $entity;
      $entities['insert']['inf'][$entID] = array( 'id' => $entID,
        'readonly' => $inflection->isReadonly(),
        'chaya' => $inflection->getChaya(),
        'entityIDs' => $inflection->getComponentIDs(),
        'certainty' => $inflection->getCertainty(),
        'case' => $inflection->getCase(),
        'gender' => $inflection->getGender(),
        'num' => $inflection->getGramaticalNumber(),
        'person' => $inflection->getVerbalPerson(),
        'voice' => $inflection->getVerbalVoice(),
        'tense' => $inflection->getVerbalTense(),
        'mood' => $inflection->getVerbalMood(),
        'conj2nd' => $inflection->getSecondConjugation());
      break;
    case 'cat':
      $catalog = $entity;
      $entities['insert']['cat'][$entID] = array( 'description'=> $catalog->getDescription(),
        'id' => $entID,
        'value'=> $catalog->getTitle(),
        'readonly' => $catalog->isReadonly(),
        'editibility' => $catalog->getOwnerID(),
        'ednIDs' => $catalog->getEditionIDs(),
        'typeID' => $catalog->getTypeID());
        $attrIDs = $catalog->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['cat'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
    case 'edn':
      $edition = $entity;
      $entities['insert']['edn'][$entID] = array( 'description'=> $edition->getDescription(),
         'id' => $entID,
         'value'=> $edition->getDescription(),
         'readonly' => $edition->isReadonly(),
         'editibility' => $edition->getOwnerID(),
         'typeID' => $edition->getTypeID(),
         'txtID' => $edition->getTextID(),
         'seqIDs' => $edition->getSequenceIDs());
      //todo create service to push and remove from FK list
        $attrIDs = $edition->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['edn'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
    case 'txt':
      $text = $entity;
      $entities['insert']['txt'][$entID] = array(
         'CKN'=> $text->getCKN(),
         'id' => $entID,
         'value'=> $text->getTitle(),
         'title'=> $text->getTitle(),
         'readonly' => $text->isReadonly(),
         'editibility' => $text->getOwnerID(),
         'typeIDs' => $text->getTypeIDs(),
         'ref' => $text->getRef(),
         'imageIDs' => $text->getImageIDs());
      //todo create service to push and remove from FK list
        $attrIDs = $text->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['txt'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
    case 'tmd':
      $textMetadata = $entity;
      $entities['insert']['tmd'][$entID] = array(
         'id' => $entID,
         'txtID'=> $textMetadata->getTextID(),
         'typeIDs'=> $textMetadata->getTypeIDs(),
         'readonly' => $textMetadata->isReadonly(),
         'editibility' => $textMetadata->getOwnerID(),
         'refIDs' => $textMetadata->getReferenceIDs());
      //todo create service to push and remove from FK list
        $attrIDs = $textMetadata->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['tmd'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
    case 'img':
      $image = $entity;
      $title = $image->getTitle();
      $url = $image->getURL();
      $entities['insert']['img'][$entID] = array(
         'title'=> $title,
         'id' => $entID,
         'value'=> ($title?$title:substr($url,strrpos($url,'/')+1)),
         'readonly' => $image->isReadonly(),
         'editibility' => $image->getOwnerID(),
         'url' => $url,
         'type' => $image->getType(),
         'boundary' => $image->getBoundary());
      //todo create service to push and remove from FK list
      break;
    case 'atb':
      $attribution = $entity;
      $entities['insert']['atb'][$entID] = array( 'title'=> $attribution->getTitle(),
        'id' => $entID,
        'value'=> $attribution->getTitle().($attribution->getDetail()?$attribution->getDetail():''),
        'readonly' => $attribution->isReadonly(),
        'grpID' => $attribution->getGroupID(),
        'bibID' => $attribution->getBibliographyID(),
        'description' => $attribution->getDescription(),
        'detail' => $attribution->getDetail(),
        'types' => $attribution->getTypes());
      break;
    case 'ano':
      $annotation = $entity;
      $entities['insert']['ano'][$entID] = array( 'text'=> $annotation->getText(),
        'id' => $entID,
        'modStamp' => $annotation->getModificationStamp(),
        'linkedFromIDs' => $annotation->getLinkFromIDs(),
        'linkedToIDs' => $annotation->getLinkToIDs(),
        'value' => ($annotation->getText()? $annotation->getText():$annotation->getURL()),//todo reformat this to have semantic term with entity value
        'readonly' => $annotation->isReadonly(),
        'url' => $annotation->getURL(),
        'typeID' => $annotation->getTypeID());
        $vis = $annotation->getVisibilityIDs();
        if (in_array(2,$vis)) {
          $vis = "Public";
        } else if (in_array(3,$vis)) {
          $vis = "User";
        } else {
          $vis = "Private";
        }
        $entities['insert']['ano'][$entID]['vis'] = $vis;
        $attrIDs = $annotation->getAttributionIDs();
        if ($attrIDs && count($attrIDs)) {
          $entities['insert']['ano'][$entID]['attributionIDs'] = $attrIDs;
        }
      break;
  }
  $linkedAnoIDsByType = $entity->getLinkedAnnotationsByType();
  if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
    $entities['insert'][$prefix][$entID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
  }
  $relatedEntGIDsByLinkType = $entity->getRelatedEntitiesByLinkType();
  if ($relatedEntGIDsByLinkType && count($relatedEntGIDsByLinkType) > 0){
    $entities['insert'][$prefix][$entID]['relatedEntGIDsByType'] = $relatedEntGIDsByLinkType;
  }
  addUpdateSwitchHashReturnData($prefix,$entID);
}


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entID
* @param mixed $key
* @param mixed $value
*/

function addUpdateEntityReturnData($prefix,$entID,$key,$value) {
  global $entities;
  if (!array_key_exists('update',$entities)) {
    $entities['update'] = array();
  }
  if (!array_key_exists($prefix,$entities['update'])) {
    $entities['update'][$prefix] = array();
  }
  if (!array_key_exists($entID,$entities['update'][$prefix])) {
    $entities['update'][$prefix][$entID] = array();
  }
  $entities['update'][$prefix][$entID][$key] = $value;
  if ( in_array($key, array('entityIDs','graphemeIDs'))) {
    addUpdateSwitchHashReturnData($prefix,$entID);
  }
}


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entID
* @param mixed $key
* @param mixed $value
*/

function addRemovePropertyReturnData($prefix,$entID,$key) {
  global $entities;
  if (!array_key_exists('removeprop',$entities)) {
    $entities['removeprop'] = array();
  }
  if (!array_key_exists($prefix,$entities['removeprop'])) {
    $entities['removeprop'][$prefix] = array();
  }
  if (!array_key_exists($entID,$entities['removeprop'][$prefix])) {
    $entities['removeprop'][$prefix][$entID] = array();
  }
  array_push($entities['removeprop'][$prefix][$entID],$key);
  if ( in_array($key, array('entityIDs','graphemeIDs'))) {
    addUpdateSwitchHashReturnData($prefix,$entID);
  }
}


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entID
* @param mixed $key
* @param mixed $value
*/

function addLinkEntityReturnData($prefix,$entID,$key,$value) {
  global $entities;
  if (!array_key_exists('link',$entities)) {
    $entities['link'] = array();
  }
  if (!array_key_exists($prefix,$entities['link'])) {
    $entities['link'][$prefix] = array();
  }
  if (!array_key_exists($entID,$entities['link'][$prefix])) {
    $entities['link'][$prefix][$entID] = array();
  }
  $entities['link'][$prefix][$entID][$key] = $value;
}


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entID
* @param mixed $key
* @param mixed $value
*/

function addUnlinkEntityReturnData($prefix,$entID,$key,$value) {
  global $entities;
  if (!array_key_exists('unlink',$entities)) {
    $entities['unlink'] = array();
  }
  if (!array_key_exists($prefix,$entities['unlink'])) {
    $entities['unlink'][$prefix] = array();
  }
  if (!array_key_exists($entID,$entities['unlink'][$prefix])) {
    $entities['unlink'][$prefix][$entID] = array();
  }
  $entities['unlink'][$prefix][$entID][$key] = $value;
}


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entID
*/

function addUpdateSwitchHashReturnData($prefix,$entID) {
  global $entities;
  if (!in_array($prefix,array('cmp','tok'))) {
    return;
  }
  //calc hash
  $hash = getEntitySwitchHash($prefix,$entID);
  if ($hash) {
    if (!array_key_exists('switchhashes',$entities)) {
      $entities['switchhashes'] = array();
    }
    $entities['switchhashes'][$prefix.$entID] = $hash;
  }
}

/**
* put your comment there...
*
* @param mixed $entTag
*/

function addRemoveEntitySwitchHashReturnData($entTag) {
  global $entities;
  if (!in_array(substr($entTag,0,3),array('cmp','tok'))) {
    return;
  }
  if (!array_key_exists('removeswitchhashes',$entities)) {
    $entities['removeswitchhashes'] = array();
  }
  array_push($entities['removeswitchhashes'],$entTag);
}


/**
* put your comment there...
*
* @param mixed $prefix
* @param mixed $entID
*/

function addRemoveEntityReturnData($prefix,$entID) {
  global $entities;
  if (!array_key_exists('remove',$entities)) {
    $entities['remove'] = array();
  }
  if (!array_key_exists($prefix,$entities['remove'])) {
    $entities['remove'][$prefix] = array();
  }
  if (!in_array($entID,$entities['remove'][$prefix])) {
    array_push($entities['remove'][$prefix],$entID);
  }
}


/**
* put your comment there...
*
*/

function addUpdatedTagsInfo() {
  global $tagsInfo;
  $tempTagsInfo = getTagsInfo();
  if ($tempTagsInfo) {
    $tagsInfo = $tempTagsInfo;
  }
}


/**
* put your comment there...
*
* @param mixed $error
*/

function returnWithError($error){//todo expand code to give sub counts for update remove and insert
  global $entities;
  return $entities;
}

/**
* put your comment there...
*
*/

function getEntities(){//todo expand code to give sub counts for update remove and insert
  global $entities;
  return $entities;
}


/**
* put your comment there...
*
*/

function getEntityCount(){//todo expand code to give sub counts for update remove and insert
  global $entities;
  return count($entities);
}
?>
