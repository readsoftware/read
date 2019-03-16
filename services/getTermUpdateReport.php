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
* getTermMergeReport
*
* for a specified database, this service creates a temporary table using the term.sql file
* to populate the table. It then analyses the content of the two tables to indentify the differences
* mark these differences such that they can be filtered by used/not used/all, system/content/all,
* removed/added,
* changed label, changed parent, changed type, changed comment, changed list or changed code
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

$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if ($data) {
  if ( isset($data['html'])) {//get html
    header("Content-type: text/html");
    $format = 'html';
  } else {
    header("Content-type: text/javascript");
    $format = 'json';
  }
}
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
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
//load used term IDs
$usedTermIDs = getUsedTermIDs();
$codeTermDependencies = array();
setCodeDependencies();
$currentTerms = array();
$curCodeTermIDs = array();
$curLookupByTermParent = array();
//load current terms lookup by id
$curTermQuery = "SELECT c.trm_id AS id,c.trm_labels::hstore->'en' AS label,".
                    "concat(c.trm_labels::hstore->'en','-',p.trm_labels::hstore->'en') AS term_parentlabel,".
                    "c.trm_type_id as type,c.trm_code AS code, c.trm_list_ids as list ".
                  "FROM term c LEFT JOIN term p ON c.trm_parent_id = p.trm_id ".
                  "ORDER BY c.trm_id";
$dbMgr->query($curTermQuery);
if ($dbMgr->getRowCount()>0){
  while($row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC)){
    $currentTerms[$row['id']] = $row;
    $curLookupByTermParent[strtolower($row['term_parentlabel'])] = $row['id'];
    if (in_array($row['term_parentlabel'],$codeTermDependencies)) {// code depends on this term label
      array_push($curCodeTermIDs,$row['id']);
    }
  }
} else if ($dbMgr->getError()) {
  array_push($errors,"error getting current terms :".$dbMgr->getError());
}
$curTermCnt = count($currentTerms);
if (!$curTermCnt) {
  array_push($errors,"error no current terms retrieved");
}
if (!loadTempNewTermsFromFile()) {
  exit("unable to load new terms into temp table");
}
//load new terms lookup by id
$newTerms = array();
$newLookupByTermParent = array();
$newTermQuery = "SELECT c.trm_id AS id,c.trm_labels::hstore->'en' AS label,".
                    "concat(c.trm_labels::hstore->'en','-',p.trm_labels::hstore->'en') AS term_parentlabel,".
                    "c.trm_type_id as type,c.trm_code AS code, c.trm_list_ids as list ".
                  "FROM newterm c LEFT JOIN newterm p ON c.trm_parent_id = p.trm_id ".
                  "ORDER BY c.trm_id";
$dbMgr->query($newTermQuery);
if ($dbMgr->getRowCount()>0){
  while($row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC)){
    $newTerms[$row['id']] = $row;
    $newLookupByTermParent[strtolower($row['term_parentlabel'])] = $row['id'];
  }
} else if ($dbMgr->getError()) {
  array_push($errors,"error getting new terms :".$dbMgr->getError());
}
$newTermCnt = count($newTerms);
if (!$newTermCnt) {
  array_push($errors,"error no new terms retrieved");
}
//run through term sets to find remove, add and change (label, term_parentlabel, code, list, type, description) and used
$removedIDs = array();
$changedIDs = array();
$changedTo = array();

foreach ($currentTerms as $trmID => $curTerm) {
  if (!array_key_exists($trmID,$newTerms)) {//find removed or moved terms
    array_push($removedIDs,$trmID);
    if (array_key_exists(strtolower($curTerm['term_parentlabel']),$newLookupByTermParent)) {
      $movedToNewTerm = $newLookupByTermParent[strtolower($curTerm['term_parentlabel'])];
      if ($movedToNewTerm) {
        $changedTo[$trmID] = $newTerms[$movedToNewTerm]['id'];//todo check if this is the same term
      }
    }
  } else {
    $newTerm = $newTerms[$trmID];
    $changes = array();
    foreach ($curTerm as $key => $value) {
      if ($value != $newTerm[$key]) {
        $changes[$key] = array('cur'=>$value,'new'=>$newTerm[$key]);
      }
    }
    if (count($changes)) {
      $changedIDs[$trmID] = $changes;
    }
  }
}

$codeTermIDs = array();
$missingCodeDependencies = array();
foreach ($codeTermDependencies as $term_parentLabel) {
  if (array_key_exists(strtolower($term_parentLabel),$curLookupByTermParent)) {
    $curID = $curLookupByTermParent[strtolower($term_parentLabel)];
  } else {
    $curID = 'missing';
    $missingCodeDependencies[$term_parentLabel] = "cur";
  }
  if (array_key_exists(strtolower($term_parentLabel),$newLookupByTermParent)) {
    $newID = $newLookupByTermParent[strtolower($term_parentLabel)];
  } else {
    $newID = 'missing';
    $missingCodeDependencies[$term_parentLabel] = ($curID == 'missing'?"both":"new");
  }
  $codeTermIDs[$term_parentLabel] = array('cur'=>$curID,'new'=>$newID);
}

$addedIDs = array();
foreach ($newTerms as $trmID => $newTerm) {
  if (!array_key_exists($trmID,$currentTerms)) {//find added terms
    $addedIDs[$trmID] = $newTerm;
  }
}

//find used changed ids
$usedChangedIDs = array_intersect($usedTermIDs, array_keys($changedIDs));

//find used removed ids
$usedRemovedIDs = array_intersect($usedTermIDs, $removedIDs);

if ($format == 'html') {
  //header
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Expires" content="Fri, Jan 01 2017 00:00:00 GMT"/>
      <meta http-equiv="Pragma" content="no-cache"/>
      <meta http-equiv="Cache-Control" content="no-cache"/>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
      <meta http-equiv="Lang" content="en"/>
      <meta name="author" content="READ System"/>
      <meta http-equiv="Reply-to" content="@.com"/>
      <meta name="generator" content="PhpED 8.0"/>
      <meta name="description" content="'.($catalog->getDescription()?$catalog->getDescription():'').'"/>
      <meta name="revisit-after" content="15 days"/>
      <title>Term Update Report for <?=DBNAME?></title>
      <!--link rel="stylesheet" href="'.SITE_BASE_PATH.'/common/css/exGlossary.css" type="text/css"/-->
      <style type="text/css">
        td, th, tr {
          border: 1px solid;
        }
      </style>
    </head>
    <body>
      <h2>Term Update Report for "<?=DBNAME?>" database</h2>
<?php
  //errors section
  if (count($errors)) {
    $html = "<h3>Errors</h3>";
    foreach ($errors as $error) {
      $html .= "<div style=\"color:red;\"> $error </div>";
    }
    echo $html;
  }
  //used changed section
  if (count($usedChangedIDs)) {
    $html = '<table style="text-align:left"><tr><h3>Used terms that have changed</h3></tr>'.
            '<tr><th>Term ID</th><th>Label</th><th>New Label</th><th>Label-Parent Label</th>'.
            '<th>New Label-Parent Label</th><th>Type ID</th><th>New Type ID</th><th>Code</th>'.
            '<th>New Code</th><th>List</th><th>New List</th></tr>';
    foreach ($usedChangedIDs as $trmID) {
      $term = $currentTerms[$trmID];
      $html .= "<tr><td>$trmID</td><td>".$term['label']."</td>";
      $changes = $changedIDs[$trmID];
      if (array_key_exists('label',$changes)) {
        $html .= "<td>".$changes['label']['new']."</td>";
      } else {
        $html .= "<td></td>";
      }
      if (array_key_exists('term_parentlabel',$changes)) {
        $html .= "<td>".$changes['term_parentlabel']['cur']."</td><td>".$changes['term_parentlabel']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
      if (array_key_exists('type',$changes)) {
        $html .= "<td>".$changes['type']['cur']."</td><td>".$changes['type']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
      if (array_key_exists('code',$changes)) {
        $html .= "<td>".$changes['code']['cur']."</td><td>".$changes['code']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
      if (array_key_exists('list',$changes)) {
        $html .= "<td>".$changes['list']['cur']."</td><td>".$changes['list']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
       $html .= "</tr>";
    }
    $html .= "</table>";
    echo $html;
  }
  //used removed section
  if (count($usedRemovedIDs)) {
    $html = '<table style="text-align:left"><tr><h3>Used terms that are removed</h3></tr>'.
            '<tr><th>Term ID</th><th>Label</th><th>Label-Parent Label</th><th>Moved To Term ID</th></tr>';
    foreach ($usedRemovedIDs as $trmID) {
      $term = $currentTerms[$trmID];
      $html .= "<tr><td>$trmID</td><td>".$term['label']."</td><td>".$term['term_parentlabel']."</td>";

      if (array_key_exists($trmID,$changedTo)) {
        $html .= "<td>".$changedTo[$trmID]."</td></tr>";
      } else {
        $html .= "<td></td></tr>";
      }
    }
    $html .= "</table>";
    echo $html;
  }
  //missing code dependencies
  if (count($missingCodeDependencies)) {
    $html = '<table style="text-align:left"><tr><h3>Code terms that are missing</h3></tr>'.
            '<tr><th>Label-Parent Label</th><th>Current Term ID</th><th>New Term ID</th></tr>';
    foreach ($missingCodeDependencies as $term_parentLabel => $scope) {
      $termIDs = $codeTermIDs[$term_parentLabel];
      $html .= "<tr><td><strong>".$term_parentLabel."</strong></td><td>".$termIDs['cur']."</td><td>".$termIDs['new']."</td></tr>";
    }
    $html .= "</table>";
    echo $html;
  }
  //added section
  if (count($addedIDs)) {
    $html = '<table style="text-align:left"><tr><h3>Added terms</h3></tr>'.
            '<tr><th>Term ID</th><th>Label</th><th>Label-Parent Label</th>'.
            '<th>Type ID</th><th>Code</th><th>List</th></tr>';
    foreach ($addedIDs as $trmID => $term) {
      $html .= "<tr><td>$trmID</td><td>".$term['label']."</td><td>".$term['term_parentlabel']."</td>";
      $html .= "<td>".$term['type']."</td><td>".$term['code']."</td><td>".$term['list']."</td></tr>";
    }
    $html .= "</table>";
    echo $html;
  }
  //removed section
  if (count($removedIDs)) {
    $html = '<table style="text-align:left"><tr><h3>Removed Terms</h3></tr>'.
            '<tr><th>Term ID</th><th>Label</th><th>Label-Parent Label</th><th>Moved To Term ID</th></tr>';
    foreach ($removedIDs as $trmID) {
      $term = $currentTerms[$trmID];
      $html .= "<tr><td>$trmID</td><td>".$term['label']."</td><td>".$term['term_parentlabel']."</td>";

      if (array_key_exists($trmID,$changedTo)) {
        $html .= "<td>".$changedTo[$trmID]."</td></tr>";
      } else {
        $html .= "<td></td></tr>";
      }
    }
    $html .= "</table>";
    echo $html;
  }
  //changed section
  if (count($changedIDs)) {
    $html = '<table style="text-align:left"><tr><h3>All terms that have changed</h3></tr>'.
            '<tr><th>Term ID</th><th>Label</th><th>New Label</th><th>Label-Parent Label</th>'.
            '<th>New Label-Parent Label</th><th>Type ID</th><th>New Type ID</th><th>Code</th>'.
            '<th>New Code</th><th>List</th><th>New List</th></tr>';
    foreach ($changedIDs as $trmID => $changes) {
      $term = $currentTerms[$trmID];
      $html .= "<tr><td>$trmID</td><td>".$term['label']."</td>";
      if (array_key_exists('label',$changes)) {
        $html .= "<td>".$changes['label']['new']."</td>";
      } else {
        $html .= "<td></td>";
      }
      if (array_key_exists('term_parentlabel',$changes)) {
        $html .= "<td>".$changes['term_parentlabel']['cur']."</td><td>".$changes['term_parentlabel']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
      if (array_key_exists('type',$changes)) {
        $html .= "<td>".$changes['type']['cur']."</td><td>".$changes['type']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
      if (array_key_exists('code',$changes)) {
        $html .= "<td>".$changes['code']['cur']."</td><td>".$changes['code']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
      if (array_key_exists('list',$changes)) {
        $html .= "<td>".$changes['list']['cur']."</td><td>".$changes['list']['new']."</td>";
      } else {
        $html .= "<td></td><td></td>";
      }
       $html .= "</tr>";
    }
    $html .= "</table>";
    echo $html;
  }
  //footer
?>
    </body>
    </html>
<?php
} else {
  if (count($usedTermIDs)) {
    $retVal['usedTermIDs'] = $usedTermIDs;
  }
  if (count($usedChangedIDs)) {
    $retVal['usedChangedIDs'] = $usedChangedIDs;
  }
  if (count($changedIDs)) {
    $retVal['changedIDs'] = $changedIDs;
  }
  if (count($usedRemovedIDs)) {
    $retVal['usedRemovedIDs'] = $usedRemovedIDs;
  }
  if (count($removedIDs)) {
    $retVal['removedIDs'] = $removedIDs;
  }
  if (count($changedTo)) {
    $retVal['changedTo'] = $changedTo;
  }
  if (count($missingCodeDependencies)) {
    $retVal['missingCodeDependencies'] = $missingCodeDependencies;
  }
  if (count($codeTermIDs)) {
    $retVal['codeTermIDs'] = $codeTermIDs;
  }
  if (count($addedIDs)) {
    $retVal['addedIDs'] = $addedIDs;
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
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }
}

function loadTempNewTermsFromFile() {
  global $dbMgr,$errors;
  $filename = "../model/term.sql";
  $createTempNewTermQuery = "DROP TABLE IF EXISTS newterm; ".
                            "CREATE TABLE newterm (LIKE term INCLUDING ALL)";
  $dbMgr->query($createTempNewTermQuery);
  if ($dbMgr->getError()) {
    array_push($errors,"error creating temp newterm table:".$dbMgr->getError());
  } else {
    $termInsertQuery = file_get_contents($filename);
    if ($termInsertQuery !== false) {
      $termInsertQuery = str_replace("\r\n","",$termInsertQuery, $count);
      $termInsertQuery = str_replace("INSERT INTO term","INSERT INTO newterm",$termInsertQuery, $count);
      $dbMgr->clearResults();
      $dbMgr->query($termInsertQuery);
      $dbMgr->clearResults();
      if ($dbMgr->getError()) {
        array_push($errors,"error loading temp newterm table from term.sql:".$dbMgr->getError());
      } else {
        return true;
      }
    } else {
      array_push($errors,"error loading term.sql");
    }
  }
  return false;
}

function getUsedTermsInfo() {
  global $dbMgr,$errors;
  $usedTermsInfo = array();
  $termUsageQuery = "SELECT c.trm_id AS id,concat(c.trm_labels::hstore->'en','_',p.trm_labels::hstore->'en') AS label_parentlabel,".
                           "c.trm_type_id as type,c.trm_code AS code, c.trm_list_ids as list ".
                    "FROM term c LEFT JOIN term p ON c.trm_parent_id = p.trm_id ".
                    "WHERE c.trm_id IN ".getUsedTermIDs(true)." ".
                    "ORDER BY c.trm_id";
  $dbMgr->query($termUsageQuery);
  if ($dbMgr->getRowCount()>0){
    while($row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC)){
      $usedTermsInfo[$row['id']] = $row;
    }
  } else if ($dbMgr->getError()) {
    array_push($errors,"error getting used terms :".$dbMgr->getError());
  }
  return $usedTermsInfo;
}

function getCurrentTerms() {
  global $dbMgr,$errors;
  return $currentTerms;
}

function getNewTerms() {
  global $dbMgr,$errors;
  return $newTerms;
}

function getUsedTermIDs( $asString = false) {
  global $dbMgr,$errors;
  $usedTermIDs = $asString? "":array();
  $usedTermIDsQuery = ($asString?"SELECT string_agg(c.trmID::text,',') FROM ":"").
                      "(SELECT trmID FROM ".
                         "(SELECT DISTINCT(ano_type_id) AS trmID FROM annotation ".
                         "UNION SELECT DISTINCT(atg_type_id) FROM attributiongroup ".
                         "UNION SELECT DISTINCT(jsc_type_id) FROM jsoncache ".
                         "UNION SELECT DISTINCT(img_type_id) FROM image ".
                         "UNION SELECT DISTINCT(prn_type_id) FROM propernoun ".
                         "UNION SELECT DISTINCT(ugr_type_id) FROM usergroup ".
                         "UNION SELECT DISTINCT(itm_type_id) FROM item ".
                         "UNION SELECT DISTINCT(prt_type_id) FROM part ".
                         "UNION SELECT DISTINCT(prt_shape_id) FROM part ".
                         "UNION SELECT DISTINCT(prt_manufacture_id) FROM part ".
                         "UNION SELECT DISTINCT(bln_type_id) FROM baseline ".
                         "UNION SELECT DISTINCT(gra_type_id) FROM grapheme ".
                         "UNION SELECT DISTINCT(lem_type_id) FROM lemma ".
                         "UNION SELECT DISTINCT(lem_part_of_speech_id) FROM lemma ".
                         "UNION SELECT DISTINCT(lem_subpart_of_speech_id) FROM lemma ".
                         "UNION SELECT DISTINCT(lem_nominal_gender_id) FROM lemma ".
                         "UNION SELECT DISTINCT(lem_verb_class_id) FROM lemma ".
                         "UNION SELECT DISTINCT(lem_declension_id) FROM lemma ".
                         "UNION SELECT DISTINCT(inf_case_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_nominal_gender_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_gram_number_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_verb_person_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_verb_voice_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_verb_tense_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_verb_mood_id) FROM inflection ".
                         "UNION SELECT DISTINCT(inf_verb_second_conj_id) FROM inflection ".
                         "UNION SELECT DISTINCT(cat_type_id) FROM catalog ".
                         "UNION SELECT DISTINCT(cat_lang_id) FROM catalog ".
                         "UNION SELECT DISTINCT(edn_type_id) FROM edition ".
                         "UNION SELECT DISTINCT(seq_type_id) FROM sequence) AS temp ".
                       "WHERE trmID IS NOT NULL ".
                       "ORDER BY trmID)".($asString?" AS c":"");
  $dbMgr->query($usedTermIDsQuery);
  if ($dbMgr->getRowCount()>0){
    if ($asString) {
      $row = $dbMgr->fetchResultRow();
      $usedTermIDs = "(".$row[0].")";
    } else {
      while($row = $dbMgr->fetchResultRow()){
        array_push($usedTermIDs,$row[0]);
      }
    }
  } else if ($dbMgr->getError()) {
    array_push($errors,"error getting used terms :".$dbMgr->getError());
  }
  return $usedTermIDs;
}
function setCodeDependencies() {
  global $codeTermDependencies;
  $codeTermDependencies= array(
    '(ui)assisteddate-termtype',
    'adj.-partofspeech',
    'analysis-sequencetype',
    'automationdate-termtype',
    'automationnumber-termtype',
    'chapter-analysis',
    'chaya-translation',
    'comment-commentarytype',
    'consonant-graphemetype',
    'customtype-tagtype',
    'dictionary-catalogtype',
    'done-workflowtype',
    'eyecopy-imagetype',
    'footnotetype-annotationtype',
    'glossary-catalogtype',
    'image-baselinetype',
    'individual-attributiongrouptype',
    'inscriptioneyecopy-imagetype',
    'inscriptionphotograph-imagetype',
    'inscriptionphotographinfrared-imagetype',
    'inscriptionrubbing-imagetype',
    'intrasyllablepunctuation-graphemetype',
    'issue-commentarytype',
    'key-termtype',
    'lexicon-attributiontype',
    'linephysical-textphysical',
    'list-multipleordered-termtype',
    'list-multiple-termtype',
    'list-single-termtype',
    'manuscriptconserved-imagetype',
    'manuscriptreconstruction-imagetype',
    'noun-partofspeech',
    'num.-partofspeech',
    'numbersign-graphemetype',
    'obsolete-workflowtype',
    'paragraph-section',
    'pron.-partofspeech',
    'published-editiontype',
    'punctuation-graphemetype',
    'question-commentarytype',
    'reconstructedsurface-imagetype',
    'reliquaryphotograph-imagetype',
    'research-editiontype',
    'research-editiontype',
    'rootref-textreferences',
    'textdivision-text',
    'textphysical-sequencetype',
    'textreferences-sequencetype',
    'text-sequencetype',
    'todo-workflowtype',
    'transcription-baselinetype',
    'translation-annotationtype',
    'unknown-graphemetype',
    'unknown-partofspeech',
    'v.-partofspeech',
    'vowel-graphemetype',
    'vowelmodifier-graphemetype',
    //'adj.',//adj.-partofspeech:674
    'case-systemontology',//case-systemontology:358
    'commentarytype-systemontology',//commentarytype-systemontology:389
    //'dictionary',//dictionary-catalogtype:372
    //'fragmentstate',//notfound
    'grammaticalgender-systemontology',//grammaticalgender-systemontology:488
    'grammaticalnumber-systemontology',//grammaticalnumber-systemontology:496
    'imagetype-systemontology',//imagetype-systemontology:514
    //'itemshape',//not found
    //'itemtype',//not found
    'languagescript-tagtype',//languagescript-tagtype:720
    //'linephysical',
    //'noun',//noun-partofspeech:664
    'partofspeech-systemontology',//partofspeech-systemontology:642
    //'partshape',//not found
    //'parttype',//not found
    //'text',
    //'textdivision',
    //'textphysical',
    //'translation',//translation-annotationtype:761
    //'v.',//'v.-partofspeech'
    'verbalmood-systemontology',//verbalmood-systemontology:841
    'verbalperson-systemontology',//verbalperson-systemontology:847
    'verbalsecondaryconjugation-systemontology',//verbalsecondaryconjugation-systemontology:853
    'verbaltense-systemontology',//verbaltense-systemontology:862
    'verbalvoice-systemontology'//verbalvoice-systemontology:873
  );
}

?>
