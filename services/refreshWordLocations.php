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
  * refreshWordLocations.php
  *
  *  A service that recalculates the location of words and compound words.
  *
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

  header("Content-type: text/plain");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Token.php';
  require_once dirname(__FILE__) . '/../model/entities/Lemmas.php';
  require_once dirname(__FILE__) . '/../model/entities/Inflections.php';
  require_once dirname(__FILE__) . '/../model/entities/Edition.php';
  require_once dirname(__FILE__) . '/../model/entities/Text.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Compound.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
  require_once dirname(__FILE__) . '/../model/entities/Baselines.php';
  require_once (dirname(__FILE__) . '/../viewer/php/viewutils.php');
  //get utilities for viewing
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;

  $dbMgr = new DBManager();
  $retVal = array();
  $tokIDs = (array_key_exists('tokIDs',$_REQUEST)? $_REQUEST['tokIDs']:null);
  $tokIDs = explode(',',$tokIDs);
  $cmpIDs = (array_key_exists('cmpIDs',$_REQUEST)? $_REQUEST['cmpIDs']:null);
  $cmpIDs = explode(',',$cmpIDs);
  $errors = array();
  $warnings = array();
  //find catalogs
//  if ($ednID) {
//    $edition = new Edition($ednID);
//  }else{
//    $edition = null;
//  }
  if (!$tokIDs && !$cmpIDs) {//no word ids so warn
    echo "Usage: refreshWordLocations.php?db=yourDBName&tokIDs=###,###,...&cmpIDs=###";
  } else {
    if ($tokIDs && count($tokIDs) > 0) {
      foreach ($tokIDs as $tokID) {
        if (strlen($tokID) == 0) continue;
        $token = new Token($tokID);
        if ($token->hasError()) {
          echo "Error accessing word tok:$tokID - ".$token->geterror();
        } else if($token->isMarkedDelete()){
          echo "Error word tok:$tokID is marked for delete and should be removed from words list"."\n";
        } else if($token->isReadonly()){
          echo "Error word tok:$tokID is readonly and cannot be updated, owner id is ".$token->getOwnerID()."\n";
        } else {
          $token->updateLocationLabel();
          $token->save();
          echo "Word tok:$tokID ".$token->getValue()." has location of ".$token->getLocation()."\n";
        }
      }
    }
    if ($cmpIDs && (count($cmpIDs) > 0)) {
      foreach ($cmpIDs as $cmpID) {
        if (strlen($cmpID) == 0) continue;
        $compound = new Compound($cmpID);
        if ($compound->hasError()) {
          echo "Error accessing word cmp:$cmpID - ".$compound->geterror();
        } else if($compound->isMarkedDelete()){
          echo "Error word cmp:$cmpID is marked for delete and should be removed from words list"."\n";
        } else if($compound->isReadonly()){
          echo "Error word cmp:$cmpID is readonly and cannot be updated, owner id is ".$compound->getOwnerID()."\n";
        } else {
          $compound->updateLocationLabel();
          $compound->save();
          echo "Word cmp:$cmpID ".$compound->getValue()." has location of ".$compound->getLocation()."\n";
        }
      }
    }
  }

?>
