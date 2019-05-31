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
require_once (dirname(__FILE__) . '/../model/utility/parser.php');//get map for valid aksara
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
$freeTextImport = false;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
	$defOwnerID = getUserDefEditorID();
	if (isset($data['freetextImport']) && $data['freetextImport']) {
		$freeTextImport = true;
	}
	if ( isset($data['txtInv'])) {//get txt
		if (!is_numeric($data['txtInv']) || strpos($data['txtInv'],".") !== false) {
			$texts = new Texts("txt_ckn = '".$data['txtInv']."'");
		} else if (is_numeric($data['txtInv'])) {
			$texts = new Texts("txt_id = ".$data['txtInv']);
		}
    if ($texts->getError()) {
      array_push($errors," error loading text for new edition - ".$texts->getError());
    } else if ($texts->getCount() == 0) {
      array_push($errors," no text found for TextInv ".$data['txtInv']);
    } else if ($texts->getCount() > 1) {
      array_push($errors," multiple text found for TextInv ".$data['txtInv']." - aborting");
    } else {
			$text = $texts->getTextAt(0);
    }
  } else {//we require txtID to create an edition.
    array_push($errors,"insufficient data to create new edition");
  }
}

if (count($errors) == 0) {
	if (isset($data['transcription']) && is_string($data['transcription']) && strlen($data['transcription'])) {
		//separate the
		$physlines = explode("\n",$data['transcription']);
	} else if ($freeTextImport) {
		$physlines = array("+ + + + + ///");
	}
	$lineTrans = array();
	$lineMask = array();
	$lineord = 1;
  foreach ($physlines as $line) {
		if (preg_match("/^([a-z0-9\.]+)\)(.+)/i",$line,$matches)) {
			array_push($lineMask,$matches[1]);
			array_push($lineTrans,$matches[2]);
		} else {
			array_push($lineMask,"NL$lineord");
			array_push($lineTrans,$line);
		}
		$lineord++;
	}
}

if (count($errors) == 0 && $freeTextImport && count($lineTrans) > 0) {
	$cnt = count($lineTrans);
	$physLineGIDs = array();
	for ($i=0; $i<$cnt; $i++) {
		// create new free text line seq
		$newFreeTextLine = new Sequence();
		$newFreeTextLine->setLabel($lineMask[$i]);
		$newFreeTextLine->setTypeID(Entity::getIDofTermParentLabel('freetext-textphysical'));//term dependency
		$newFreeTextLine->setOwnerID($defOwnerID);
		$newFreeTextLine->setVisibilityIDs($defVisIDs);
		if ($defAttrIDs){
			$newFreeTextLine->setAttributionIDs($defAttrIDs);
		}
		$newFreeTextLine->storeScratchProperty('freetext',$lineTrans[$i]);
		$newFreeTextLine->save();
		if ($newFreeTextLine->hasError()) {
			array_push($errors,"error creating physical line sequence '".$newFreeTextLine->getValue()."' - ".$newFreeTextLine->getErrors(true));
		} else {
			addNewEntityReturnData('seq',$newFreeTextLine);
			array_push($physLineGIDs,$newFreeTextLine->getGlobalID());
		}
	}
	if (count($errors) == 0) {
		//create text physical sequence for the physical line sequence
		$physSeq = new Sequence();
		$physSeq->setEntityIDs($physLineGIDs);
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
}

if (count($errors) == 0) {
  //create edition
  $edition = new Edition();
  $edition->setSequenceIDs(array($physSeq->getID())); //,$textSeq->getID()));
  $edition->setOwnerID($defOwnerID);
  $edition->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $edition->setAttributionIDs($defAttrIDs);
	}
	if (isset($data['ednTitle']) && count($data['ednTitle'])) {
		$description = $data['ednTitle'];
	}
  if (!$description) {
		$description = $text->getRef();
	}
  if (!$description) {
    $description = $text->getTitle();
  }
  if (!$description) {
    $description = $text->getInv();
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
	if ($freeTextImport) {
		$retVal['resultMsg'] = "<div class=\"successMsg\">Text edition '$description' successfully created.</div>";
		$retVal['txtID'] = $text->getID();
	}
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
