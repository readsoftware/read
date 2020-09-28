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
* refreshSortCodes
*
* recalculates all sort codes from bottom up
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

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid aksara
require_once (dirname(__FILE__) . '/../model/entities/SyllableClusters.php');
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Lemmas.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$data = (array_key_exists('data', $_REQUEST)? json_decode($_REQUEST['data'], true):$_REQUEST);
$specificRecalc = ( isset($data['graIDs']) ||
                    isset($data['sclIDs']) ||
                    isset($data['tokIDs']) ||
                    isset($data['cmpIDs']) ||
                    isset($data['lemIDs'])
                  );
$gcalc = $scalc = $tcalc = $ccalc = $lcalc = !$specificRecalc;
if ( !$specificRecalc && isset($data['entTypes'])) {//get command
  $entTypes = $data['entTypes'];
  if (strpos($entTypes, "g")===false) {
    $gcalc = false;
  }
  if (strpos($entTypes, "s")===false) {
    $scalc = false;
  }
  if (strpos($entTypes, "t")===false) {
    $tcalc = false;
  }
  if (strpos($entTypes, "l")===false) {
    $lcalc = false;
  }
  if (strpos($entTypes, "c")===false) {
    $ccalc = false;
  }
}
$graIDs = $sclIDs = $tokIDs = $cmpIDs = $lemIDs = null;
if ( isset($data['graIDs'])) {//specifying graphemes
  $graIDs = $data['graIDs'];
  $gcalc = true;
}
if ( isset($data['sclIDs'])) {//specifying syllables
  $sclIDs = $data['sclIDs'];
  $scalc = true;
}
if ( isset($data['tokIDs'])) {//specifying tokens
  $tokIDs = $data['tokIDs'];
  $tcalc = true;
}
if ( isset($data['cmpIDs'])) {//specifying compounds
  $cmpIDs = $data['cmpIDs'];
  $ccalc = true;
}
if ( isset($data['lemIDs'])) {//specifying lemmas
  $lemIDs = $data['lemIDs'];
  $lcalc = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title>Refresh READ Entity Sort Codes</title>
</head>
<body>
<?php
if ($gcalc) {
  $condition = null;
  if ($graIDs) {
    $condition = "gra_id in ($graIDs)";
  }
  $graphemes = new Graphemes($condition,null,null,500);
  $graphemes->setAutoAdvance(true);
  echo "recalculating grapheme sort codes <br/>";
  foreach ($graphemes as $grapheme) {
    $grapheme->calculateSort();
    $grapheme->save();
    if ($grapheme->hasError()) {
      echo "Error: ".$grapheme->getErrors(true)."<br/>";
    }
  }
  unset($graphemes);
//  ob_flush();
}
if ($scalc) {
  $condition = null;
  if ($sclIDs) {
    $condition = "scl_id in ($sclIDs)";
  }
  $syllables = new SyllableClusters($condition,null,null,500);
  $syllables->setAutoAdvance(true);
  echo "recalculating syllable sort codes <br/>";
  foreach ($syllables as $syllable) {
    $syllable->calculateSortCodes();
    $syllable->save();
    if ($syllable->hasError()) {
      echo "Error: ".$syllable->getErrors(true)."<br/>";
    }
  }
  unset($syllables);
}
if ($tcalc) {
  $condition = null;
  if ($tokIDs) {
    $condition = "tok_id in ($tokIDs)";
  }
  $tokens = new Tokens($condition,null,null,500);
  $tokens->setAutoAdvance(true);
  echo "recalculating token sort codes <br/>";
  foreach ($tokens as $token) {
    $token->calculateValues();
    $token->save();
    if ($token->hasError()) {
      echo "Error: ".$token->getErrors(true)."<br/>";
    }
  }
  unset($tokens);
  //ob_flush();
}
if ($lcalc) {
  $condition = null;
  if ($lemIDs) {
    $condition = "lem_id in ($lemIDs)";
  }
  $lemmas = new Lemmas($condition,null,null,500);
  $lemmas->setAutoAdvance(true);
  echo "recalculating lemma sort codes <br/>";
  foreach ($lemmas as $lemma) {
    $lemma->calculateSortCodes();
    $lemma->save();
    if ($lemma->hasError()) {
      echo "Error: ".$lemma->getErrors(true)."<br/>";
    }
  }
  unset($lemmas);
//  ob_flush();
}
if ($ccalc) {
  $condition = "not cmp_id=0";
  if ($cmpIDs) {
    $condition = "cmp_id in ($cmpIDs)";
  }
  $compounds = new Compounds($condition,null,null,500);
  $compounds->setAutoAdvance(true);
  echo "recalculating compound sort codes <br/>";
  foreach ($compounds as $compound) {
    $compound->calculateValues();
    $compound->save();
    if ($compound->hasError()) {
      echo "Error: ".$compound->getErrors(true)."<br/>";
    }
  }
  unset($compounds);
//  ob_flush();
}
?>
</body>
</html>
