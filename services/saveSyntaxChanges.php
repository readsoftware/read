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
require_once (dirname(__FILE__) . '/../model/entities/Annotation.php');
require_once (dirname(__FILE__) . '/../model/entities/Annotations.php');
require_once (dirname(__FILE__) . '/../model/entities/Compound.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$rootNode = null;
$syntaxListTerm = (defined('SYNTAXFUNCTIONLIST')?SYNTAXFUNCTIONLIST:'SyntacticFunction');
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
  $unique = 1;

  $edition = null;
  if (isset($data['ednID'])) {
    $ednID = $data['ednID'];
    $edition = new Edition($ednID);
    if ($edition->hasError()) {
      array_push($errors,"error loading editon - ".join(",",$edition->getErrors()));
    } else if ($edition->isReadonly()) {
      array_push($errors,"error editon ($ednID) is not editable");
    } else if ($edition->isMarkedDelete()) {
      array_push($errors,"error editon ($ednID) is not editable");
    }
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
  $processedGIDs = array();
  if (count($errors)==0) {
    $changes = null;
    if (isset($data['changes'])) {
      $changes = $data['changes'];
      if ($changes and count($changes) > 0) {
        // constrain syntax function term to a declared list under a term that derives from LinkageType
        $linkageTypeID = ENTITY::getIDofTermParentLabel("linkagetype-annotationtype");//warning!!!! term dependency
        $syntaxFuncListTermID = Entity::getIDofTermParentLabel($syntaxListTerm."-LinkageType"); //
        if (!$syntaxFuncListTermID || !$linkageTypeID) { // check that this is a valid linktype
          array_push($errors,"syntax function list term must be a child of LinkageType");
        } else {
          // parse and validate the syntax command of the form "entTag": "cmd:[oldtype|]newtype:[oldtarget|]newtarget"
          foreach($changes as $entTag => $chngStr) {
            $entGID = substr($entTag,0,3).":".substr($entTag,3);
            $chngStr = $chngStr[0];
            list($cmd,$types,$targets) = explode(':',$chngStr);
            $types = explode('|',$types);
            $newType = array_pop($types);
            $oldType = array_pop($types);
            $targets = explode('|',$targets);
            $newTarget = array_pop($targets);
            if ($newTarget) {
              $newTarget = substr($newTarget,0,3).":".substr($newTarget,3);
            } else {
              array_push($warnings,"malformed syntax change $chngStr, skipping");
            }
            $oldTarget = array_pop($targets);
            if ($oldTarget) {
              $oldTarget = substr($oldTarget,0,3).":".substr($oldTarget,3);
            }
            $entGID = substr($entTag,0,3).":".substr($entTag,3);
            $newlinkTypeID = Entity::getIDofTermParentLabel(strtolower($newType."-$syntaxListTerm"));
            $oldlinkTypeID = Entity::getIDofTermParentLabel(strtolower($oldType."-$syntaxListTerm"));
            $link = null;
            $isReuse = false;
            if (!$newlinkTypeID) { // check that this is a valid linktype
              array_push($warnings,"syntax link $newType must be a child term of the syntax function list $syntaxListTerm (a child of LinkageType) skipping $entGID");
            } else {
              //check for linkType links containing link from GID
              $links = new Annotations(" '$entGID' = ANY(ano_linkfrom_ids) and ano_owner_id != 1");
              if ($links->getCount() > 0) {
                foreach ( $links as $sLink ) {
                  if ($sLink->isReadonly()) {
                    continue;
                  } else if (isSubTerm($syntaxFuncListTermID,$sLink->getTypeID()) &&
                             $sLink->getLinkToIDs() &&
                             count($sLink->getLinkToIDs()) < 2 &&
                             count($sLink->getLinkFromIDs()) == 1) {
                    $link = $sLink;
                    $isReuse = true;
                    break;
                  }
                }
              }
              if (!$link) {
                $link = new Annotation();
                $link->setOwnerID($defOwnerID);
                $link->setVisibilityIDs($defVisIDs);
                if ($defAttrIDs){
                  $link->setAttributionIDs($defAttrIDs);
                }
                $isReuse = false;
              }
            }
            if ($newlinkTypeID && $link) {
              if ($cmd == 'del') {
                $link->markForDelete();
              } else {
                $toGIDs = $link->getLinkToIDs();
                $fromGIDs = $link->getLinkFromIDs();
                $typ = $link->getType();
                //check consistency of request with
                if ($isReuse){
                  if ($cmd == 'add') {
                    array_push($warnings,"add link $newType with target $newTarget and reusing existing link ".$link->getGLobalID());
                  }
                  if ($oldType != $typ) {
                    array_push($warnings,"existing link has type $typ and request shows oldtype of $oldType");
                  }
                  if ($oldTarget && array_search($oldTarget, $toGIDs) !== 0) {
                    array_push($warnings,"requested linkto $oldTarget doesn't match existing link with ID(s) ".join(',',$toGIDs));
                  }
                }
                $link->setLinkFromIDs(array($entGID));
                if ($newType != $typ) {
                  $link->setTypeID($newlinkTypeID);
                }
                $link->setLinkToIDs(array($newTarget));
              }
              $link->save();
              if ($link->hasError()) {
                array_push($warnings,"error saving syntax $newType link from $entGID to $newTarget - ".$link->getErrors(true));
              } else {
                if ($cmd == 'del') {
                  addRemoveEntityReturnData('ano',$link->getID());
                } else if (!$isReuse) {
                  addNewEntityReturnData('ano',$link);
                } else {
                  $linkID = $link->getID();
                  addUpdateEntityReturnData('ano',$linkID,'linkedToIDs', $link->getLinkToIDs());
                  addUpdateEntityReturnData('ano',$linkID,'linkedFromIDs', $link->getLinkFromIDs());
                  addUpdateEntityReturnData('ano',$linkID,'typeID', $link->getTypeID());
                }
              }
            } else {
              array_push($warnings,"unable to get link for $cmd with type $newType and with target $newTarget");
            }
            if ($newlinkTypeID) {
              //for link from GID update relatedEntGIDsByType field
              $linkFromEntity = EntityFactory::createEntityFromGlobalID($entGID);//assumed to be a tok or cmp  GID
              addUpdateEntityReturnData($linkFromEntity->getEntityTypeCode(),$linkFromEntity->getID(),'relatedEntGIDsByType', $linkFromEntity->getRelatedEntitiesByLinkType());
              $linkToEntity = null;
              //check for syntactic function and able to store scratch information
              //if not this could show up as a mis-match in UI and data.
              if (!$linkFromEntity->isReadonly()) {
                if ($cmd == 'del') {
                  $linkFromEntity->storeScratchProperty('syntacticRelation',null);
                  $linkFromEntity->storeScratchProperty('syntaxData',null);
                } else {
                  $dependencyWord = "";
                  if ($newTarget) {
                    $linkToEntity = EntityFactory::createEntityFromGlobalID($newTarget);
                    $prefix = $linkToEntity->getEntityTypeCode();
                    if ($prefix == 'tok' || $prefix == 'cmp') {
                      $tempWord = $linkToEntity->getValue();
                      $dependencyWord = preg_replace("/ʔ/","",$tempWord);
                    }
                  }
                  $targetTag = ($linkToEntity?$linkToEntity->getEntityTag():null);
                  $linkFromEntity->storeScratchProperty('syntacticRelation',$newType.' → '.$dependencyWord);
                  $linkFromEntity->storeScratchProperty('syntaxData',"{\"type\":\"$newType\",\"target\":\"$targetTag\"}");
                }
                $linkFromEntity->save();
                array_push($processedGIDs,$entGID);
              }
            }
          }
        }
      }
    }
  }
}
$cacheUpdateList = array();
if (count($errors) == 0) {
  $txtDivTypeID = Entity::getIDofTermParentLabel("textdivision-text"); //warning!! term dependency
  $txtTypeID = Entity::getIDofTermParentLabel("text-sequencetype"); //warning!! term dependency
  foreach ($processedGIDs as $entGID) {
    // TODO mark cache dirty so updates text div seq and edition as a whole
    $txtDivSeqs = new Sequences("'$entGID' = ANY(seq_entity_ids) and seq_type_id = $txtDivTypeID",null,null,null);
    if ($txtDivSeqs->getCount() > 0) {//
      foreach ( $txtDivSeqs as $txtDivSeq ) {
        $txtDivID =  $txtDivSeq->getID();
        $txtSeqs = new Sequences("'".$txtDivSeq->getGlobalID()."' = ANY(seq_entity_ids) and seq_type_id = $txtTypeID",null,null,null);
        if ($txtSeqs->getCount() > 0) {//
          foreach ( $txtSeqs as $txtSeq ) {
            $editions = new Editions("'".$txtSeq->getID()."' = ANY(edn_sequence_ids)",null,null,null);
            if ($editions->getCount() > 0) {//
              foreach ($editions as $edition) {
                $ednID = $edition->getID();
                $cacheUpdateList[$txtDivID.'-'.$ednID] = 1;
              }
            }
          }
        }
      }
    }
  }
  foreach($cacheUpdateList as $txtdiv_ednIDs => $int) {
    list($txtDivID, $ednID) = explode('-',$txtdiv_ednIDs);
    if (is_numeric($txtDivID) && is_numeric($ednID)) {
      invalidateCachedEditionEntities($ednID);
      invalidateCachedSeqEntities($txtDivID,$ednID);
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
