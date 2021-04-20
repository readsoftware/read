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
* validateNewEditionString
*
* validates input string as a new edition using the parser to create valid lines,textdivs,tokens,syllables and graphemes.
*
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
$saveAfterParse = false;
$txtInv = null;
$text = null;
$txtInv = null;
$texts = null;
$description = null;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  if (isset($data['saveAfterValidation']) && $data['saveAfterValidation'] == 1) {
    $saveAfterParse = true;
  }
  if ( isset($data['txtInv']) || isset($data['txtID'])) {//get txt

    if ($data['txtInv']) {
      $texts = new Texts("txt_ckn = '".$data['txtInv']."'");
    } else if (is_numeric($data['txtID'])) {
      $texts = new Texts("txt_id = ".$data['txtID']);
    }
    if ($texts->getError()) {
      array_push($errors," error loading text for new edition - ".$texts->getError());
    } else if ($texts->getCount() == 0) {
      array_push($errors," no text found for Text ".$data['txtInv']?$data['txtInv']:$data['txtID']);
    } else if ($texts->getCount() > 1) {
      array_push($errors," multiple text found for TextInv ".$data['txtInv']." - aborting");
    } else {
      $text = $texts->getTextAt(0);
      $txtInv = $text->getInv();
      if (isset($data['ednTitle']) && is_String($data['ednTitle']) && strlen($data['ednTitle'])) {
        $description = $data['ednTitle'];
      }
      if (!$description && $text->getRef()) {
        $description = "Edition for ".$text->getRef();
      }
      if (!$description && $text->getTitle()) {
        $description = "Edition for ".$text->getTitle();
      }
      if (!$description && $text->getInv()) {
        $description = "Edition for ".$text->getInv();
      }
      if (!$description) {
        $description = "New text";
      }
    }
  } else {//we require txtID to create an edition.
    array_push($errors,"insufficient data to create new edition");
  }
}


$parserConfigs = array();
if (count($errors) == 0) {
  if (isset($data['transcription']) && is_string($data['transcription']) && strlen($data['transcription'])) {
    //separate the lines
    $physlines = explode("\n",$data['transcription']);
    $lineTrans = null;
    $lineMask = null;
    $lineord = 0;
    foreach ($physlines as $line) {
      $lineord++;
      if (preg_match("/^([a-z0-9\.]+)\)(.+)/i",$line,$matches)) {
        $lineMask = $matches[1];
        $lineTrans = $matches[2];
      } else {
        $lineMask = "NL$lineord";
        $lineTrans = $line;
      }
      array_push($parserConfigs,
                 createParserConfig($defOwnerID,
                                    "{".join(',',$defVisIDs)."}",
                                    $defAttrIDs?"{".join(',',$defAttrIDs)."}":"{3}",
                                    $txtInv,
                                    null,
                                    $text->getID(),
                                    null,
                                    $lineMask,
                                    $lineord,
                                    null,
                                    $lineTrans,
                                    null,
                                    null,
                                    null,
                                    $description
                                   ));
    }
  }
}
$ednIDs = array();
if (count($errors) == 0 && count($parserConfigs) > 0) {
  $parser = new Parser($parserConfigs);
  $parser->parse();

  if ($parser->getErrors()) {
    foreach ($parser->getErrors() as $error) {
      if (!$error) {
        continue;
      }
      if (count($errors) == 0) {
        array_push($errors,"<h2> Errors </h2>");
      }
      array_push($errors,"<span style=\"color:red;\">error -   $error </span><br>");
    }
  }
  if (count($errors) == 0 && $saveAfterParse) {
    $parser->saveParseResults();

    foreach($parser->getEditions() as $edition) {
      array_push($ednIDs, $edition->getID());
      addNewEntityReturnData('edn',$edition);
    }
    if ($parser->getErrors()) {
      array_push($errors,"<h2> Saving Errors </h2>");
      foreach ($parser->getErrors() as $error) {
        array_push($errors,"<span style=\"color:red;\">error -   $error </span><br>");
      }
    }
  }
}


$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
  $retVal['txtID'] = $text->getID();
  $retVal['ednIDs'] = $ednIDs;
  if ($saveAfterParse) {
    $retVal['commitMsg'] = "<div class=\"successMsg\">Text edition '$description' successfully commited.</div>";
  } else  {
    $retVal['validateMsg'] = "<div class=\"successMsg\">Text edition '$description' successfully validated.</div>";
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
