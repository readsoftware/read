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
* getEditionStatus
*
* get edition status and set as requested if unset
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
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
require_once (dirname(__FILE__) . '/../model/entities/Compound.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequence.php');
require_once (dirname(__FILE__) . '/../model/entities/Inflection.php');
require_once (dirname(__FILE__) . '/../model/entities/Lemma.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Catalog.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');

$retVal = array();
$errors = array();
$warnings = array();
$ednOwnerID = null;
$status = null;
$syntaxListTerm = (defined('SYNTAXFUNCTIONLIST')?SYNTAXFUNCTIONLIST:'SyntacticFunction');
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else if (!isset($data['ednID'])) {
  array_push($errors,"must specify an edition using 'ednID=##'");
} else {
  $ednID = $data['ednID'];
  $edition = new Edition($ednID);
  if ($edition->hasError()) {
    array_push($errors,"error loading editon - ".join(",",$edition->getErrors()));
  } else if ($edition->isReadonly()) {
    array_push($errors,"error editon ($ednID) is not editable");
  } else if ($edition->isMarkedDelete()) {
    array_push($errors,"error editon ($ednID) is not editable");
  } else {
    $text = $edition->getText(true);
    $ref = "unknown";
    if ($text && !$text->hasError()) {
      $ref = $text->getInv();
      if (!$ref) {
        $ref = $text->getRef();
      }
      if (!$ref) {
        $ref = $text->getTitle();
      }
    }
    $syntaxData = array("title" => $edition->getDescription(),
                        "id" => $edition->getEntityTag(),
                        "ref" => $ref
                      );

    $seqPhys = null;
    $seqText = null;
    $seqAnalysis = null;
    list($seqText,$seqPhys,$seqAnalysis) = getOrderedEditionSequences($edition);
    $physLineInfosBySclGID = array();
    $plStartSclGIDs = array();
    // get information to calculate the physical marker positions.
    foreach($seqPhys->getEntities(true) as $edPhysLineSeq) {
      $startSclGID = ($edPhysLineSeq->getEntityIDs())[0];
      $physLineInfosBySclGID[$startSclGID] = array(
                                                    "id" => $edPhysLineSeq->getEntityTag(),
                                                    "text" => $edPhysLineSeq->getLabel()
                                                  );
      array_push($plStartSclGIDs,$startSclGID);
    }
    // all edition words on a display line
    $allWordsNodeGrp = array("id" => $seqText->getEntityTag());
    $nodes = array();
    $plIndexMax = count($plStartSclGIDs);
    $plIndex = 0;
    $linemarker = null;
    $plStartSclGID =  $plStartSclGIDs[$plIndex];
    $plStartSclID = substr_replace($plStartSclGID,'',0,4);
    foreach($seqText->getEntities(true) as $edTextDivSeq) {
      foreach($edTextDivSeq->getEntities(true) as $word) {
        $offset = 0;
        $wrdSclIDs = getEntitySclIDs($word->getEntityTypeCode(),$word->getID(),false);
        $value = $word->getTranscription();
        if ($wrdSclIDs && count($wrdSclIDs) > 0 && $value) {
          $sclIndex = array_search($plStartSclID,$wrdSclIDs);
          if ($sclIndex !== false) { // word contains new physical line syllable create marker
            $charAverage = mb_strlen($word->getValue())/count($wrdSclIDs);
            $offset = round($charAverage * $sclIndex);
            $linemarker = $physLineInfosBySclGID[$plStartSclGID];
            $linemarker['offset'] = $offset;  // approximation of physical line break
          }
        }

        $morphology = $word->getMorphology(null,true);
        $node = array(
                      "id" => $word->getEntityTag(),
                      "text" => $value,
                      "sub1" => $morphology['pos'],
                      "sub2" => $morphology['morph']
                     );
        $rel = $word->getScratchProperty('syntaxData');
        if ($rel) {
          $node['rel'] = json_decode($rel);
        }
        if ($linemarker) {
          $node['linemarker'] = $linemarker;
          $linemarker = null;
          $plIndex += 1;
          if ($plIndex < $plIndexMax) {
            $plStartSclGID =  $plStartSclGIDs[$plIndex];
            $plStartSclID = substr_replace($plStartSclGID,'',0,4);
          }
        }
        array_push($nodes,$node);
      }
    }
    $allWordsNodeGrp['nodes'] = $nodes;
    $syntaxData['nodegrps'] = array($allWordsNodeGrp);
    $ednSynFuncListTerm = $edition->getScratchProperty('synFuncList');
    if ($ednSynFuncListTerm) {
      $syntaxListTerm = $ednSynFuncListTerm;
    }
  }
}
// get syntactic function term information id, label, and code.
$syntaxFuncInfo = array();
$syntaxFuncListTermID = Entity::getIDofTermParentLabel($syntaxListTerm."-LinkageType"); //
if ($syntaxFuncListTermID) {
  $syntaxFunctionTerms = new Terms("trm_parent_id = $syntaxFuncListTermID","trm_labels",0,50);
  if ($syntaxFunctionTerms->getError()) {
    array_push($errors,"error loading syntax function terms - ".$syntaxFunctionTerms->getError());
  } else {
    foreach($syntaxFunctionTerms as $term) {
      array_push($syntaxFuncInfo,
                  array(
                    "id" => $term->getID(),
                    "type" => $term->getLabel(),
                    "code" => $term->getCode()
                  )
                );
    }
    array_push($syntaxFuncInfo,
      array(
        "id" => "new",
        "type" => "new",
        "code" => "new"
      )
    );
    array_push($syntaxFuncInfo,
      array(
        "id" => "?",
        "type" => "?",
        "code" => "unknown"
      )
    );
  }
}

$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
  $retVal["syntaxInfo"] = $syntaxFuncInfo;
  $retVal["data"] = $syntaxData;
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
}
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal,JSON_PRETTY_PRINT).");";
  }
} else {
  print json_encode($retVal,JSON_PRETTY_PRINT);
}
?>
