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
* saveLink
*
* saves annotation link type between gids
*
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
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $fromGID = null;
  $toGID = null;
  $linkType = null;
  $linkTypeID = null;
  $oldLinkTypeID = null;
  $unique = false;
  if ( isset($data['fromGID'])) {//get lemma id
    $fromGID = $data['fromGID'];
  }
  if ( isset($data['toGID']) && $data['toGID']) {
    $toGID = $data['toGID'];
  }
  if ( isset($data['linkTypeID'])) {
    $linkTypeID = $data['linkTypeID'];
  }
  if ( isset($data['oldLinkTypeID'])) {
    $oldLinkTypeID = $data['oldLinkTypeID'];
  }
  if ( isset($data['unique'])) {
    $unique = $data['unique'];
  }
  if ( isset($data['linkType'])) {//todo: this assumes direct decendent of linkagetype need a way to specify terms under linkage
    $linkType = $data['linkType'];
    if ($linkType || count($linkType)) {
      $linkTypeID = Entity::getIDofTermParentLabel(strtolower($linktype)."-linkagetype");
    }
  }
  if (!$fromGID || count($fromGID) == 0) {
    array_push($errors,"saveLink must have a 'from' entity GID");
  }
  if (!$toGID || count($toGID) == 0) {
//    array_push($errors,"saveLink must have a 'to' entity GID");
  }
  if (!$linkTypeID) { // todo add check that this is a valid linktype
    array_push($errors,"saveLink must have a link type");
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}
if (count($errors) == 0) {
  $link = null;
  $originalLinkID = null;
  //check for linkType links containing link from GID
  $links = new Annotations("ano_type_id = $linkTypeID and '$fromGID' = ANY(ano_linkfrom_ids) and ano_owner_id != 1");
  if ($links->getCount() > 0) {//for now take first writable one later either combine or check for existing to GID
    foreach ( $links as $sLink ) {
      if ($sLink->isReadonly()) {
        continue;
      } else {
        if (!$link) {
          $originalLinkID = $sLink->getID();
          $link = $sLink;
          if (!$unique) {
            break;
          }
        } else if (count($sLink->getLinkFromIDs()) == 1){ //only linked with this entity and there are multiple for a linktype that needs to be unique
          $sLink->markForDelete();
          addRemoveEntityReturnData($sLink->getEntityTypeCode(),$sLink->getID());
          //TODO determine if toGIDs need update
          //TODO test if this erases other owner (group) current user is member of
        }
      }
    }
  }
  if ($oldLinkTypeID) {//existing link
    $links = new Annotations("ano_type_id = $oldLinkTypeID and '$fromGID' = ANY(ano_linkfrom_ids) and ano_owner_id != 1");
    if ($links->getCount() > 0) {//for now take first writable one later either combine or check for existing to GID
      if (!$link) {// try and find existing
        foreach ( $links as $sLink ) {
          if ($sLink->isReadonly()) {
            continue;
          } else {
            if (!$link) {
              $originalLinkID = $sLink->getID();
              $link = $sLink;
              if (!$unique) {
                break;
              }
            } else if (count($sLink->getLinkFromIDs()) == 1){ //only linked with this entity and there are multiple for a linktype that needs to be unique
              $sLink->markForDelete();
              addRemoveEntityReturnData($sLink->getEntityTypeCode(),$sLink->getID());
              //TODO determine if toGIDs need update
              //TODO test if this erases other owner (group) current user is member of
            }
          }
        }
      } else {//new link exist so remove old link
        foreach ( $links as $sLink ) {
          if ($sLink->isReadonly()) {
            continue;
          } else {
            $sLink->markForDelete();
            addRemoveEntityReturnData($sLink->getEntityTypeCode(),$sLink->getID());
            //TODO determine if toGIDs need update
            //TODO test if this erases other owner (group) current user is member of
          }
        }
      }
    }
  }
  if (!$link) {// no existing create a link annotation with linktype
    $link = new Annotation();
    $link->setOwnerID($defOwnerID);
    $link->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $link->setAttributionIDs($defAttrIDs);
    }
  }
  $toLinkGIDs = $link->getLinkToIDs();
  if (count($toLinkGIDs) > 0 && !$unique) {
    if ($toGID && !in_array($toGID,$toLinkGIDs)) {// no duplicates
      array_push($toLinkGIDs,$toGID);
    }
  } else {
    $toLinkGIDs = array($toGID);
  }
  $link->setTypeID($linkTypeID);
  $link->setLinkToIDs($toLinkGIDs);
  $fromLinkGIDs = $link->getLinkFromIDs();
  if (count($fromLinkGIDs) > 0 && !$unique) {
    if (!in_array($fromGID,$fromLinkGIDs)) {
      array_push($fromLinkGIDs,$fromGID);
    }
  } else {
    if (count($fromLinkGIDs) > 0) {
    array_push($warnings,"warning replacing(".join(',',$fromLinkGIDs).") $linkType links with $fromGID in".$link->getEntityTag());
    }
    $fromLinkGIDs = array($fromGID);
  }
  $link->setLinkFromIDs($fromLinkGIDs);
  $link->save();
  if ($link->hasError()) {
    array_push($errors,"error adding $linkType link from $fromGID to $toGID - ".$link->getErrors(true));
  } else {
    $linkID = $link->getID();
    if ($originalLinkID != $linkID) {//insert new link
      addNewEntityReturnData('ano',$link);
    } else {
      addUpdateEntityReturnData('ano',$linkID,'linkedToIDs',$toLinkGIDs);
      addUpdateEntityReturnData('ano',$linkID,'linkedFromIDs',$fromLinkGIDs);
    }
    //for link from GID update relatedEntGIDsByType field
    $linkFromEntity = EntityFactory::createEntityFromGlobalID($fromGID);//assumed to be a tok or cmp  GID
    addUpdateEntityReturnData($linkFromEntity->getEntityTypeCode(),$linkFromEntity->getID(),'relatedEntGIDsByType', $linkFromEntity->getRelatedEntitiesByLinkType());
    $txtDivTypeID = Entity::getIDofTermParentLabel("textdivision-text"); //warning!! term dependency
    $txtTypeID = Entity::getIDofTermParentLabel("text-sequencetype"); //warning!! term dependency
    // TODO mark cache dirty so updates text div seq and edition as a whole
    $txtDivSeqs = new Sequences("'$fromGID' = ANY(seq_entity_ids) and seq_type_id = $txtDivTypeID",null,null,null);
    if ($txtDivSeqs->getCount() > 0) {//
      foreach ( $txtDivSeqs as $txtDivSeq ) {
        $txtSeqs = new Sequences("'".$txtDivSeq->getGlobalID()."' = ANY(seq_entity_ids) and seq_type_id = $txtTypeID",null,null,null);
        if ($txtSeqs->getCount() > 0) {//
          foreach ( $txtSeqs as $txtSeq ) {
            $editions = new Editions("'".$txtSeq->getID()."' = ANY(edn_sequence_ids)",null,null,null);
            if ($editions->getCount() > 0) {//
              foreach ($editions as $edition) {
                $ednID = $edition->getID();
                $ednSeqIDs = $edition->getSequenceIDs();
                invalidateCachedEditionEntities($ednID);
                invalidateCachedSeqEntities($txtDivSeq->getID(),$ednID);
              }
            }
          }
        }
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
