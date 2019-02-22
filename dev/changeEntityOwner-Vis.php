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
  * changeEntityOwner-Vis.php
  *
  *  A script that changes ownership or visibility or both for a scope of entities.
  * it can be called from the browse like this:
  * http://localhost/kanishka/dev/changeEntityOwner-Vis.php?db=bctest&newVis=andrea,ingoandrew&scope=entity&tags=edn1,edn2
  * to change visbility tousergroup name "andrea" and usergroup name "ingoandrea" for edition 1 and edition 2
  * http://localhost/kanishka/dev/changeEntityOwner-Vis.php?db=bctest&ownerOld=andrea&ownerNew=ingoandrew&scope=entity&tags=edn1,edn2
  * to change ownership from usergroup name "andrea" to usergroup name "ingoandrea" for edition 1 and edition 2
  * http://localhost/kanishka/dev/changeEntityOwner-Vis.php?db=bctest&ownerNew=ingoandrew&scope=entity&tags=tok153,cmp52
  * to change ownership to usergroup name "ingoandrea" on token 153 and compound 52
  * http://localhost/kanishka/dev/testEditionLinks.php?db=bctest&ownerNew=stefan&scope=table&tags=edn
  * to change ownership to usergroup name "stefan" on all editions
  * http://localhost/kanishka/dev/testEditionLinks.php?db=bctest&ownerNew=stefan&scope=table&tags=all
  * to change ownership to usergroup name "stefan" on all tables (all entities)
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
  ob_start('ob_gzhandler');

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
/*  if (@$argv) {
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
    if(@$cmdParams['-d']) $_REQUEST["db"] = $cmdParams['-d'];
    if (@$cmdParams['-e']) $_REQUEST['ednID'] = $cmdParams['-e'];
    //commandline access for setting userid  TODO review after migration
    if (@$cmdParams['-u'] && !isset($_SESSION['ka_userid'])) $_SESSION['ka_userid'] = $cmdParams['-u'];
    echo 'db = '.$_REQUEST['db'].' ednID = '.$_REQUEST['ednID'].' uID = '.$_SESSION['ka_userid']."\n";
  }
  */
if (!isSysAdmin()) {
  echo "you must be signed is as sys admin to use this service";
  exit();
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
  $strVis = null;
  $newOwnerID = null;
  $oldOwnerID = null;
  $newVisibility = (array_key_exists('newVis',$_REQUEST)? explode(",",$_REQUEST['newVis']):null);//Or required
  $newOwnerName = (array_key_exists('ownerNew',$_REQUEST)? $_REQUEST['ownerNew']:null);//Or required
  $oldOwnerName = (array_key_exists('ownerOld',$_REQUEST)? $_REQUEST['ownerOld']:null);//optional
  $scope = (array_key_exists('scope',$_REQUEST)? $_REQUEST['scope']:'entity');//default
  $tags = (array_key_exists('tags',$_REQUEST)?  explode(",",$_REQUEST['tags']):null);//required
  if ((!$newOwnerName && (!$newVisibility || count($newVisibility) == 0)) || !$tags || count($tags) == 0 ) {//invalid call
    echo "you must supply one of 'newVis'or 'ownerNew' usergroup name and 'tags' appropriate for the given scope (default scope=entity)";
    exit();
  }
  if ($newVisibility) {
    $strVis = "{";
    $isFirst = true;
    foreach ($newVisibility as $usrname) {
      $usrID = getUserGroupIDforName($usrname);
      if (!$usrID) {
        echo "visibility usergroup $usrname is not valid";
        exit();
      }
      if (isFirst) {
        $strVis .= $usrID;
        $isFirst = false;
      } else {
        $strVis .= ",".$usrID;
      }
    }
    $strVis .= "}";
  }
  if ($newOwnerName) {
    $newOwnerID = getUserGroupIDforName($newOwnerName);
    if (!$newOwnerID) {
      echo "'ownerNew' usergroup $newOwnerName is not valid";
      exit();
    }
  }
  if ($oldOwnerName) {
    $oldOwnerID = getUserGroupIDforName($oldOwnerName);
    if (!$oldOwnerID) {
      echo "'ownerOld' usergroup $oldOwnerName is not valid";
      exit();
    }
  }
  if (strtolower($scope) == 'table') {//all entities of a table
    if ( count($tags) == 1 && strtolower($tags[0]) == 'all') {
      $tags = array_keys($prefixToTableName);
    }
    foreach ($tags as $prefix) {
      if (!array_key_exists($prefix,$prefixToTableName)) {
        echo "skipping invalid tag $prefix, not found in lookup for table prefixes \n";
        continue;
      }
      $tableName = $prefixToTableName[$prefix];
      if ($strVis) {
        $cnt = changeVisibility($prefix,$tableName,null,$strVis,$oldOwnerID);
        if ($cnt) {
          echo "visibility for $cnt rows in $tableName".($oldOwnerID?" with ownerID = $oldOwnerName":"")." where changed to $strVis \n";
        } else {
          echo "failed to change visibility for $tableName to $strVis \n";
        }
      }
      if ($newOwnerID) {
        $cnt = changeOwner($prefix,$tableName,null,$newOwnerID,$oldOwnerID);
        if ($cnt) {
          echo "owner for $cnt rows in $tableName".($oldOwnerID?" with ownerID = $oldOwnerName":"")." where changed to $newOwnerName \n";
        } else if ($oldOwnerID) {
          echo "didn't find entity with owner $oldOwnerName to change owner for $tableName to $newOwnerName \n";
        } else {
          echo "failed to change owner for $tableName to $newOwnerName \n";
        }
      }
    }
  } else if (strtolower($scope) == 'entity') {// specific entities
    foreach ($tags as $entTag) {
      $prefix = substr($entTag,0,3);
      $id = substr($entTag,3);
      if (!array_key_exists($prefix,$prefixToTableName)) {
        echo "skipping invalid tag $entTag with prefix $prefix not found in lookup for table prefixes \n";
        continue;
      }
      if (!is_numeric($id)) {
        echo "skipping invalid tag $entTag with non-numeric id $id \n";
        continue;
      }
      $tableName = $prefixToTableName[$prefix];
      if ($strVis) {
        $cnt = changeVisibility($prefix,$tableName,array($id),$strVis,$oldOwnerID);
        if ($cnt) {
          echo "visibility for $cnt rows in $tableName with id $id".($oldOwnerID?" and ownerID = $oldOwnerID":"")." was changed to $strVis \n";
        } else {
          echo "failed to change visibility for entity $entTag to $strVis \n";
        }
      }
      if ($newOwnerID) {
        $cnt = changeOwner($prefix,$tableName,array($id),$newOwnerID,$oldOwnerID);
        if ($cnt) {
          echo "owner for $cnt rows in $tableName with id $id".($oldOwnerID?" and ownerID = $oldOwnerID":"")." was changed to $newOwnerID \n";
        } else {
          echo "failed to change owner for entity $entTag to $newOwnerID \n";
        }
      }
    }
  }
//  "ugr_member_ids"  vis
//  "ugr_admin_ids"  owner

?>
