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
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/
    require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/utility/parser.php');//get utilities
// header('content-type: text/html; charset: utf-8');
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<?php
  echo "<form action=\"".READ_DIR."/dev/validateLine.php\" method=\"post\">";
?>

<?php
if (!array_key_exists('line',$_POST)) {
?>

Line: <input id="line" style="width:70%" type="text" name="line"><br>
<input type="submit" value="Validate">
</form>

<?php
} else {
  $line = $_POST['line'];
  $parserConfigs = array(
    createParserConfig(2,"{2}",'{1}',"TEST","guest",null,null,"1",1,null,$line)
  );
?>

Line: <input id="line" style="width:70%" type="text" name="line" value="<?=$line?>">
<input type="submit" value="Validate">
</form>

<?php
  $parser = new Parser($parserConfigs);
  $parser->setBreakOnError(true);
  $parser->parse();
  $graCnt = count($parser->getGraphemes());

  if (count($parser->getErrors())) {
    foreach ($parser->getErrors() as $error) {
      if (preg_match('/character (\d+)/', $error, $matches)) {
        $errIndex = $matches[1];
        echo "error: ".mb_substr($line,0,$errIndex).
            "<span style=\"color:red; background-color:pink;\">".mb_substr($line,$errIndex,1)."</span>"
            .mb_substr($line,$errIndex+1)."<br/>";
        echo "<span style=\"color:red;\">error -   $error </span><br/>";
      }
    }
//    echo "<h2> Errors </h2>";
    foreach ($parser->getErrors() as $error) {
    }
  }

  echo "<h2> Entities </h2>";
  echo '<table style="text-align:left">';
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

  if (count($parser->getCompounds())) {
    echo "<h2> Compounds </h2>";
    foreach ($parser->getCompounds() as $compound) {
      echo (($ckn = $compound->getScratchProperty("cknLine")) ? "$ckn ":"")."\"".$compound->getCompound()."\"".
            " - ".$compound->getTranscription()." SC -  ".$compound->getSortCode()."     ".
           "componentIDs - ".$compound->getComponentIDs(true)."     ".mb_strstr($compound->getScratchProperty("nonce"),"#",true)."<br>";
    }
  }
}
?>
</body>
</html>

