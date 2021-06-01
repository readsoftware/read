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
  * testEditionLinks
  *
  *  A script that checks the edition molecule is linked properly and returns a report on the results.
  * it can be called from the browse like this:
  * http://localhost/kanishka/dev/testEditionLinks.php?db=vp2sk&ednIDs=1
  * to check an individual edition
  * http://localhost/kanishka/dev/testEditionLinks.php?db=vp2sk&ednIDs=1,2,3,4
  * to check multiple edition
  * http://localhost/kanishka/dev/testEditionLinks.php?db=vp2sk&ednIDs=all
  * to check all editions
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
//  ob_start('ob_gzhandler');

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  if (isset($argv)) {
    // handle command-line queries
    $cmdParams = array();
    for ($i=0; $i < count($argv); ++$i) {
      if ($argv[$i][0] === '-') {
        if (@$argv[$i+1] && $argv[$i+1][0] != '-') {
        $cmdParams[$argv[$i]] = $argv[$i+1];
        ++$i;
        }else{ // map non-value dash arguments into state variables
          $cmdParams[$argv[$i]] = true;
        }
      } else {//capture others args
        array_push($cmdParams, $argv[$i]);
      }
    }
    if(@$cmdParams['-d']) {
      $_REQUEST["db"] = $cmdParams['-d'];
      $dbn = $_REQUEST["db"];
      $cmdStr .= ' db = '.$_REQUEST['db'];
    }
    if (@$cmdParams['-e']) {
      $_REQUEST['ednID'] = $cmdParams['-e'];
      $cmdStr .= ' ednID = '.$_REQUEST['ednID'];
    }
    //commandline access for setting userid  TODO review after migration
    if (@$cmdParams['-u'] ) {
      if ($dbn) {
        if (!isset($_SESSION['readSessions'])) {
          $_SESSION['readSessions'] = array();
        }
        if (!isset($_SESSION['readSessions'][$dbn])) {
          $_SESSION['readSessions'][$dbn] = array();
        }
        $_SESSION['readSessions'][$dbn]['ka_userid'] = $cmdParams['-u'];
        $cmdStr .= ' uID = '.$_SESSION['readSessions'][$dbn]['ka_userid'];
      }
    }
    echo $cmdStr."\n";
  }
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once dirname(__FILE__) . '/../model/entities/Editions.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Segments.php';
  require_once dirname(__FILE__) . '/../model/entities/Graphemes.php';
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/SyllableClusters.php';
  require_once dirname(__FILE__) . '/../model/entities/Annotations.php';
  require_once dirname(__FILE__) . '/../model/entities/Attributions.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';


  $dbMgr = new DBManager();
  $retVal = array();

  $ednIDs = (array_key_exists('ednIDs',$_REQUEST)? $_REQUEST['ednIDs']:null);
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $compact = (array_key_exists('compact',$_REQUEST)? true:false);
  $errorsOnly = (array_key_exists('errorsOnly',$_REQUEST)? true:false);
  if ($ednID && !$ednIDs) {
     echo checkEditionHealth($ednID,!$compact);
  } else if ($ednIDs) {// edns
    if (is_string($ednIDs)) {
      $hasRange = false;
      if (strpos($ednIDs,'-')) {// range
        $hasRange = true;
      }
      if (strpos($ednIDs,',')) {// list of numbers
        $ednIDs = explode(",",$ednIDs);
      } else if ($hasRange){ // handle case for single range making it an array
        $ednIDs = array($ednIDs);
      }
      if ($hasRange) {
        $expandedEdnIDs = array();
        foreach ($ednIDs as $ednID) {
          if (strpos($ednID,'-')) {
            list($startID,$stopID) = explode("-",$ednID);
            for( $i = intval($startID); $i<= intval($stopID); $i++) {
              array_push($expandedEdnIDs,$i);
            }
          } else {
            array_push($expandedEdnIDs,intval($ednID));
          }
        }
        $ednIDs = $expandedEdnIDs;
      }
    }
    if (is_array($ednIDs)) {
      foreach ($ednIDs as $ednID) {
        echo checkEditionHealth($ednID,!$compact,$errorsOnly);
      }
    } else if (is_numeric($ednIDs)) {// single edition id case
     echo checkEditionHealth($ednIDs);
    } else if ($ednIDs == "all") {
      $editions = new Editions(null,"edn_id",null,null);
      foreach ($editions as $edition) {
        echo checkEditionHealth($edition->getID(),!$compact,$errorsOnly);
      }
    }
  }

?>
