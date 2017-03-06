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
* createEdition
*
* creates a new edition with a single line,textdiv,token,syllable and grapheme.
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
ob_start('ob_gzhandler');

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid aksara
require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
require_once (dirname(__FILE__) . '/../model/entities/JsonCache.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$ednOwnerID = null;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  if ( isset($data['txtID'])) {//get txt
    $text = new Text($data['txtID']);
    if ($text->hasError()) {
      array_push($errors,"loading text for new edition - ".join(",",$text->getErrors()));
    } else {
      //get default attribution
      if (!$defAttrIDs || count($defAttrIDs) == 0) {
        $attrIDs = $text->getAttributionIDs();
        if ($attrIDs && count($attrIDs) > 0 ) {
          $defAttrIDs = array($attrIDs[0]);
        }
      }
      //get default visibility
      if (!$defVisIDs || count($defVisIDs) == 0) {
        $visIDs = $text->getVisibilityIDs();
        if ($visIDs && count($visIDs) > 0 ) {
          $defVisIDs = array($visIDs[0]);
        }
      }
      $initText = '+ + + + + ///';
      if ( isset($data['initText'])) {//supllied init text so use it to set initial text for line
        $initText = $data['initText'];
      }
    }
  } else {//we require txtID to create an edition.
    array_push($errors,"insufficient data to create new edition");
  }
}
/*
if (count($errors) == 0) {
  //create grapheme using ? to denote an aksara is visible and not transcribe
  $graData = array('gra_grapheme'=>"?",
                   'gra_type_id'=>$graphemeTypeTermIDMap[$graphemeCharacterMap["?"]['typ']],
                   'gra_sort_code'=>$graphemeCharacterMap["?"]['srt']);
  $grapheme = new Grapheme($graData);
  $grapheme->setOwnerID($defOwnerID);
  $grapheme->setVisibilityIDs($defVisIDs);
  $grapheme->save();
  if ($grapheme->hasError()) {
    array_push($error,"error creating grapheme");
  } else {
    addNewEntityReturnData('gra',$grapheme);
  }
}

if (count($errors) == 0) {
  //create syllable for this grapheme
  $syllable = new SyllableCluster();
  $syllable->setGraphemeIDs(array($grapheme->getID()));
  $syllable->setOwnerID($defOwnerID);
  $syllable->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $syllable->setAttributionIDs($defAttrIDs);
  }
  $syllable->getValue();//cause recalc
  $syllable->save();
  if ($syllable->hasError()) {
    array_push($error,"error creating syllable");
  } else {
    addNewEntityReturnData('scl',$syllable);
  }
}

if (count($errors) == 0) {
  //create token for this grapheme
  $token = new Token();
  $token->setGraphemeIDs(array($grapheme->getID()));
  $token->setOwnerID($defOwnerID);
  $token->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $token->setAttributionIDs($defAttrIDs);
  }
  $token->getValue(true);//cause recalc
  $token->save();
  if ($token->hasError()) {
    array_push($error,"error creating token");
  } else {
    addNewEntityReturnData('tok',$token);
  }
}
*/

if (count($errors) == 0) {
 /* //create physical line sequence for the syllable
  $physLineSeq = new Sequence();
  $physLineSeq->setEntityIDs(array($syllable->getGlobalID()));
  $physLineSeq->setOwnerID($defOwnerID);
  $physLineSeq->setVisibilityIDs($defVisIDs);
  $physLineSeq->setLabel('NL1');
  $physLineSeq->setSuperScript('L1');
  if ($defAttrIDs){
    $physLineSeq->setAttributionIDs($defAttrIDs);
  }
  $physLineSeq->setTypeID($physLineSeq->getIDofTermParentLabel('LinePhysical-TextPhysical'));//term dependency
  $physLineSeq->save();
  if ($physLineSeq->hasError()) {
    array_push($error,"error creating physical line sequence");
  } else {
    addNewEntityReturnData('seq',$physLineSeq);
  }
  */
  // create new free text line seq
  $newFreeTextLine = new Sequence();
  $newFreeTextLine->setLabel('NL1');
  $newFreeTextLine->setSuperScript('L1');
  $newFreeTextLine->setTypeID(Entity::getIDofTermParentLabel('freetext-textphysical'));//term dependency
  $newFreeTextLine->setOwnerID($defOwnerID);
  $newFreeTextLine->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $newFreeTextLine->setAttributionIDs($defAttrIDs);
  }
  if ($initText){
    $newFreeTextLine->storeScratchProperty('freetext',$initText);
  } else {
    $newFreeTextLine->storeScratchProperty('freetext','+ + + + + ///');//default place ellipses in new freetext line
  }
  $newFreeTextLine->save();
  if ($newFreeTextLine->hasError()) {
    array_push($errors,"error creating physical line sequence '".$newFreeTextLine->getValue()."' - ".$newFreeTextLine->getErrors(true));
  } else {
    addNewEntityReturnData('seq',$newFreeTextLine);
  }
}

if (count($errors) == 0) {
  //create text physical sequence for the physical line sequence
  $physSeq = new Sequence();
  $physSeq->setEntityIDs(array($newFreeTextLine->getGlobalID()));
  $physSeq->setOwnerID($defOwnerID);
  $physSeq->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $physSeq->setAttributionIDs($defAttrIDs);
  }
  $physSeq->setTypeID($physSeq->getIDofTermParentLabel('TextPhysical-SequenceType'));//term dependency
  $physSeq->save();
  if ($physSeq->hasError()) {
    array_push($error,"error creating text physical sequence");
  } else {
    addNewEntityReturnData('seq',$physSeq);
  }
}
/*
if (count($errors) == 0) {
  //create text division sequence for the token
  $textDivSeq = new Sequence();
  $textDivSeq->setEntityIDs(array($token->getGlobalID()));
  $textDivSeq->setOwnerID($defOwnerID);
  $textDivSeq->setVisibilityIDs($defVisIDs);
  $textDivSeq->setLabel('TG1');
  $textDivSeq->setSuperScript('G1');
  if ($defAttrIDs){
    $textDivSeq->setAttributionIDs($defAttrIDs);
  }
  $textDivSeq->setTypeID($textDivSeq->getIDofTermParentLabel('TextDivision-Text'));//term dependency
  $textDivSeq->save();
  if ($textDivSeq->hasError()) {
    array_push($error,"error creating text division  sequence");
  } else {
    addNewEntityReturnData('seq',$textDivSeq);
  }
}

if (count($errors) == 0) {
  //create text sequence for the text division  sequence
  $textSeq = new Sequence();
  $textSeq->setEntityIDs(array($textDivSeq->getGlobalID()));
  $textSeq->setOwnerID($defOwnerID);
  $textSeq->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $textSeq->setAttributionIDs($defAttrIDs);
  }
  $textSeq->setTypeID($textSeq->getIDofTermParentLabel('Text-SequenceType'));//term dependency
  $textSeq->save();
  if ($textSeq->hasError()) {
    array_push($error,"error creating text sequence");
  } else {
    addNewEntityReturnData('seq',$textSeq);
  }
}
*/
if (count($errors) == 0) {
  //create edition
  $edition = new Edition();
  $edition->setSequenceIDs(array($physSeq->getID())); //,$textSeq->getID()));
  $edition->setOwnerID($defOwnerID);
  $edition->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $edition->setAttributionIDs($defAttrIDs);
  }
  $description = $text->getRef();
  if (!$description) {
    $description = $text->getTitle();
  }
  if (!$description) {
    $description = $text->getCKN();
  }
  if (!$description) {
    $description = "New text";
  }
  $edition->setDescription("Edition for ".$description);
  $edition->setTextID($text->getID());
  $edition->setTypeID($edition->getIDofTermParentLabel('Research-EditionType'));//term dependency
  $edition->save();
  if ($edition->hasError()) {
    array_push($error,"error creating text sequence");
  } else {
    addNewEntityReturnData('edn',$edition);
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
