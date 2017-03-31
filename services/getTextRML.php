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
  * getTextRML
  *
  *  A service that returns a ReadML for the data of a text edition.
  *
  * by default the service outputs all entities relavant to a text edition (edition,
  * text, textmetadata, sequence, grapheme, token, compound, lemma,
  * attributiongroup, link, bibliography, annotation, attribution, date, era
  * This can be altered by supplying the entityList value as a single quoted comma delimited list of entities
  * entityList='edition','text','textmetadata','sequence','token','compound','lemma'
  *
  * by default the Schema returns entityType properties. Properties can be suppressed by supplying the noProps value.
  * noProps=1
  *
  * http://localhost/kanishka/services/getSchemaRML.php
  *   returns a full schema rml output.
  *
  * http://localhost/kanishka/services/getSchemaRML.php?noProps=1
  *   returns a full schema rml output without properties.
  *
  * http://localhost/kanishka/services/getSchemaRML.php?entityList='item','fragment','part','text','surface'
  *   returns schema rml output for 'item','fragment','part','text' and 'surface' entities.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Service
  */
  if (@$argv) {
    // handle command-line queries
    $ARGV = array();
    for ($i = 0;$i < count($argv);++$i) {
        if ($argv[$i][0] === '-') {
            if (@$argv[$i + 1] && $argv[$i + 1][0] != '-') {
                $ARGV[$argv[$i]] = $argv[$i + 1];
                ++$i;
            } else {
                $ARGV[$argv[$i]] = true;
            }
        } else {
            array_push($ARGV, $argv[$i]);
        }
    }
    if (@$ARGV['-db']) $_REQUEST["db"] = $ARGV['-db'];
    if (@$ARGV['-ckn']) $_REQUEST['ckn'] = $ARGV['-ckn'];
    if (@$ARGV['-ednID']) $_REQUEST['ednID'] = $ARGV['-ednID'];
    if (@$ARGV['-userID']) $userID = $ARGV['-userID'];
  }

  if (!defined('ISSERVICE')) {
    define('ISSERVICE',1);
    ini_set("zlib.output_compression_level", 5);
    ob_start('ob_gzhandler');
  }
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/utilRML.php');//RML utility functions

  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $rml = null;
  $textCKN = (array_key_exists('ckn',$_REQUEST)? $_REQUEST['ckn']:null);
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $rml = calcEditionRML($ednID, $textCKN);

  if ($rml) {
    if ($isDownload) {
      header("Content-type: text/xml;  charset=utf-8");
      header("Content-Disposition: attachment; filename=rml_".$_REQUEST['ckn'].".xml");
      header("Expires: 0");
    } else {
      header('Content-type: text/xml; charset=utf-8');
      header('Cache-Control: no-cache');
    }
    echo $rml;
  } else if ($errorMsg) {
    echo $errorMsg;
  }
  ?>
