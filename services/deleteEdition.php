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
  $ednID = null;
  $edition = null;
  $seqIDs = null;
  $sequence = null;
  $forceSequenceDelete = false;
  if ( isset($data['force'])) {//get force param
    $forceSequenceDelete = true;
  }
  if ( isset($data['ednID'])) {//get edition
    $ednID = $data['ednID'];
    $edition = new Edition($ednID);
    if ($edition->hasError()) {
      array_push($errors,"loading edition - ".join(",",$edition->getErrors()));
    } else {
      $seqIDs = $edition->getSequenceIDs();
    }
  }
  if (!$ednID) {
    array_push($errors,"insufficient information for deleting Edition");
  }
  if ($edition && $edition->isReadonly()) {
    array_push($errors,"insufficient previledges for deleting Edition");
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}

if (count($errors) == 0 && $seqIDs && count($seqIDs) > 0 ) {
  //check for lemmas
  $sequences = new Sequences("seq_id in (".join(",",$seqIDs).")","seq_id",null,null);
  if (!@$sequences || $sequences->getError()) {
    array_push($errors,"error loading sequences for edition ID $ednID".$sequences->getError());
  } else if ($sequences->getCount()>0){
    $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $deleteSequence = array();
    foreach ($sequences as $sequence) {
      if ($sequence->isReadonly()) {// don't care if not owned
        continue;
      }
      $seqEntityIDs = $sequence->getEntityIDs();
      if (!$seqEntityIDs || count($seqEntityIDs) == 0) {
        array_push($deleteSequence,$sequence);
      } else if ($sequence->getType() == "Text" || $sequence->getType() == "TextPhysical") {
        foreach ($sequence->getEntities(true) as $subSequence) {
          if ($subSequence->isReadonly()) {// don't care if not owned
            continue;
          }
          $subseqEntityIDs = $subSequence->getEntityIDs();
          if (!$subseqEntityIDs || count($subseqEntityIDs) == 0) {
            array_push($deleteSequence,$subSequence);
          } else {
            array_push($errors,"error found non empty ".$subSequence->getType()." sequence ID ".$subSequence->getID()." in edition ID $ednID");
            break;
          }
        }
        if (count($errors) == 0) {
          array_push($deleteSequence,$sequence);
        }
      } else {
        array_push($errors,"error found non empty ".$sequence->getType()." sequence ID ".$sequence->getID()." in edition ID $ednID");
      }
    }
    if (count($errors) == 0) {
      foreach ($deleteSequence as $sequence) {
        $seqID = $sequence->getID();
        $seqAnnoIDs = $sequence->getAnnotationIDs();
        $seqAnnotations = $sequence->getAnnotations(true);
        if ($seqAnnotations && $seqAnnotations->getCount()) {
          //TODO add code to store anoIDs in sequence scratch
          foreach ($seqAnnotations as $annotation) {
            if (!$annotation->isReadonly()) {
              $annotation->markForDelete();
              $index = array_search($annotation->getID(),$seqAnnoIDs);
              array_splice($seqAnnoIDs,$index,1);
              addRemoveEntityReturnData('ano',$annotation->getID());
            }
          }
        }
        if (count($seqAnnoIDs)) {
          array_push($warnings,"readonly annotations found for sequence id $seqID : ".join(',',$seqAnnoIDs));
        }
        addUpdateEntityReturnData('seq',$seqID,'annotationIDs',$seqAnnoIDs);
        $seqGID = $sequence->getGlobalID();
        //remove lemGID from any tags (linkeToGIDs of annotation entities)
        $tagAnnotations = new Annotations("'$seqGID' = ANY(ano_linkto_ids) and ano_linkfrom_ids is null and not ano_owner_id = 1","ano_type_id,modified");
        if ($tagAnnotations->getCount()) {
          //TODO consider if there is a method for undo, what info to save and where
          foreach ($tagAnnotations as $annotation) {
            if (!$annotation->isReadonly()) {
              $tagLinkToIDs = $annotation->getLinkToIDs();
              $index = array_search($seqGID,$tagLinkToIDs);
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
              array_push($warnings,"readonly tag annotation".$annotation->getID()." found for sequence id $seqID ");
              error_log("warning: readonly tag annotation".$annotation->getID()." found for sequence id $seqID ");
            }
          }
        }
        //delete any linked from annotation (tags)
        $noteAnnotations = new Annotations("'$seqGID' = ANY(ano_linkfrom_ids) and ano_linkto_ids is null and not ano_owner_id = 1","ano_type_id,modified");
        if ($noteAnnotations->getCount()) {
          foreach ($noteAnnotations as $annotation) {
            if (!$annotation->isReadonly()) {
              $annotation->markForDelete();
              // and remove from local cache
              addRemoveEntityReturnData('ano',$annotation->getID());
            } else {
              array_push($warnings,"readonly tag annotation".$annotation->getID()." found for sequence id $seqID ");
              error_log("warning: readonly tag annotation".$annotation->getID()." found for sequence id $seqID ");
            }
          }
        }
        //delete sequence
        $sequence->markForDelete();
        // and remove from local cache
        addRemoveEntityReturnData('seq',$sequence->getID());
      }
    }
  }
}

if (count($errors) == 0) {
  //get text
  $ednTxtID = $edition->getTextID();
  //delete edition
  $edition->markForDelete();
  // and remove from local cache
  addRemoveEntityReturnData('edn',$edition->getID());
  //get text update information
  $text = new Text($ednTxtID);
  if ($text && !$text->hasError()){
    $txtEdnIDs = array();
    foreach($text->getEditions() as $txtEdition){
      if (!$txtEdition->isMarkedDelete()) {
        array_push($txtEdnIDs, $txtEdition->getID());
      }
    }
    addUpdateEntityReturnData('txt',$ednTxtID,"ednIDs",$txtEdnIDs);
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
if ($healthLogging && $edition) {
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
