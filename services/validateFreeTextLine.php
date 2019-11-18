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
  * validateFreeTextLine
  *
  * given an freetext line sequence ID and 'FreeText' the service saves and validates  the freetext returning
  * the information as an update to the client data.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read> <https://github.com/readsoftware/read>
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

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilities
  require_once (dirname(__FILE__) . '/../model/utility/parser.php');//get utilities
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $entities = array();
  $warnings = array();
  if (!array_key_exists('freetext',$_POST)) {
    array_push($errors,"invalid json data");
  } else {
    $freetext = $_POST['freetext'];
    $parserConfigs = array(
      createParserConfig(6,"{6}",'{1}',"TEST","guest",null,null,"1",1,null,$freetext)
    );
    $parser = new Parser($parserConfigs);
    $parser->setBreakOnError(true);
    $parser->parse();
    $errStr1 = null;
    if (count($parser->getErrors())) {
      foreach ($parser->getErrors() as $error) {
        if (preg_match('/(?:at|for)?\s?character (\d+)/', $error, $matches)) {
          $errIndex = $matches[1];
          $errStr1 = "&nbsp;&nbsp;".mb_substr($freetext,0,$errIndex).
                "<span class=\"errhilite\">".mb_substr($freetext,$errIndex,1)." </span>".
                "<span class=\"errmsg\">  error: ".
                mb_substr($error,0,mb_strpos($error,$matches[0])) ."</span>";
          $errStr1 = preg_replace("/%20/"," ",$errStr1);
          break;
        }
      }
    }
    if (array_key_exists('seqID',$_POST)) {
      $freeTextLine = new Sequence($_POST['seqID']);
      if ($freeTextLine->hasError()) {
        array_push($errors,"unable to open sequence - ".$freeTextLine->getErrors(true));
      }else {
        $freeTextLine->storeScratchProperty('freetext',$freetext);
        $freeTextLine->storeScratchProperty('validationMsg',$errStr1);// if err will store, else errStr1 is null will remove old err
        $freeTextLine->save();
        addUpdateEntityReturnData('seq',$freeTextLine->getID(),'freetext',$freeTextLine->getScratchProperty('freetext'));
        addUpdateEntityReturnData('seq',$freeTextLine->getID(),'validationMsg',$freeTextLine->getScratchProperty('validationMsg'));
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
  if ($errStr1) {
    $retVal["errString"] = $errStr1;
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
/*

  $graCnt = count($parser->getGraphemes());
  echo "<h2> Entities </h2>";
  echo '<table style="width:100%">';
  echo '<tr>';
  echo '<th>Graphemes IDs</th>';
  $graLookup = array();
  foreach ($parser->getGraphemes() as $grapheme) {
    $graID = mb_substr(mb_strstr($grapheme->getScratchProperty("nonce"),"#",true),5);
    $graLookup[$graID] = $grapheme;
    echo "<td style=\"border: 1px solid\">$graID</td>";
  }
  echo '</tr>';
  echo '<tr>';
  echo '<th>Graphemes</th>';
  foreach ($parser->getGraphemes() as $grapheme) {
    $decomp = $grapheme->getDecomposition();
    if (mb_strlen($decomp)){
      $value = preg_replace("/\:/","",$decomp);
    } else {
      $value = $grapheme->getGrapheme();
    }
    echo "<td style=\"border: 1px solid; text-align: center\">".$value."</td>";
  }
  echo '</tr>';
  echo '<tr>';
  echo '<th>SyllableClusters</th>';
  foreach ($parser->getSyllableClusters() as $syllable) {
    $sclGraIDs = $syllable->getGraphemeIDs();
    $sclGraCnt = count($sclGraIDs);
    $value = '';
    foreach ($sclGraIDs as $graID) {
      $value .= $graLookup[substr($graID,1)]->getGrapheme();
    }
//    $value = $syllable->getSegmentID();
    echo "<td style=\"border: 1px solid; text-align: center\" colspan=\"$sclGraCnt\">$value</td>";
  }
  echo '</tr>';
  echo '<tr>';
  echo '<th>Tokens</th>';
  foreach ($parser->getTokens() as $token) {
    $tokGraIDs = $token->getGraphemeIDs();
    $tokGraCnt = count($tokGraIDs);
    $decomp = $graLookup[substr($tokGraIDs[0],1)]->getDecomposition();
    if (mb_strlen($decomp)){
      $tokGraCnt--;
    }
    $value = $token->getValue();
    echo "<td style=\"border: 1px solid; text-align: center\" colspan=\"$tokGraCnt\">$value</td>";
  }
  echo '</tr>';
  echo '<th>Token IDs</th>';
  foreach ($parser->getTokens() as $token) {
    $tokID = mb_substr(mb_strstr($token->getScratchProperty("nonce"),"#",true),5);
    $tokGraIDs = $token->getGraphemeIDs();
    $tokGraCnt = count($tokGraIDs);
    $decomp = $graLookup[substr($tokGraIDs[0],1)]->getDecomposition();
    if (mb_strlen($decomp)){
      $tokGraCnt--;
    }
    echo "<td style=\"border: 1px solid; text-align: center\" colspan=\"$tokGraCnt\">$tokID</td>";
  }
  echo '</tr>';
  echo '</table>';
  echo "<h2> Compounds </h2>";
  foreach ($parser->getCompounds() as $compound) {
    echo (($ckn = $compound->getScratchProperty("cknLine")) ? "$ckn ":"")."\"".$compound->getCompound()."\"".
          " - ".$compound->getTranscription()." SC -  ".$compound->getSortCode()."     ".
         "componentIDs - ".$compound->getComponentIDs(true)."     ".mb_strstr($compound->getScratchProperty("nonce"),"#",true)."<br>";
  }
}
</body>
</html>
*/
?>
