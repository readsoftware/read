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

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

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

$graphemes = new Graphemes();

foreach ($graphemes as $grapheme) {
  $grapheme->calculateSort();
  $grapheme->save();
  if ($grapheme->hasError()) {
    echo $grapheme->getErrors(true)."<br>";
  }
}

$syllables = new SyllableClusters();
foreach ($syllables as $syllable) {
  $syllable->calculateSortCodes();
  $syllable->save();
  if ($syllable->hasError()) {
    echo $syllable->getErrors(true)."<br>";
  }
}

$tokens = new Tokens();
foreach ($tokens as $token) {
  $token->calculateValues();
  $token->save();
  if ($token->hasError()) {
    echo $token->getErrors(true)."<br>";
  }
}

$lemmas = new Lemmas();
foreach ($lemmas as $lemma) {
  $lemma->calculateSortCodes();
  $lemma->save();
  if ($lemma->hasError()) {
    echo $lemma->getErrors(true)."<br>";
  }
}

$compounds = new Compounds("not cmp_id=0");
$compounds->setAutoAdvance(true);
foreach ($compounds as $compound) {
  $compound->calculateValues();
  $compound->save();
  if ($compound->hasError()) {
    echo $compound->getErrors(true)."<br>";
  }
}

?>
