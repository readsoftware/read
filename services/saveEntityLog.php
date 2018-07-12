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
* logChanges
*
* service that store log entries for editions and/or catalogs
*
* when called with no parameters the service will query the system to compile a list of entities that
* have been changed by the current user and return with a form for entering the log text for the first
* entity. When an entry is saved the service will select the next entity and return a form for entering
* a log entry for it until all entities are logged or the user cancels.
*
*This is targetted at full filling the TEI revisionDesc Header node
*
*     <revisionDesc>
      <change who="#SB" when="2017-06-13">Creation of the file through READ export.</change>
      <change who="#SB" when="2017-06-19">Initial adaptation of file.</change>
      <change who="#SB" when="2017-06-20">Fixed apparatus.</change>
      <change who="#AG" when="2017-06-21">Changed title and added comments/responses</change>
      <change who="#AG" when="2017-06-22">Various fixes.</change>
      <change who="#AG" when="2017-06-23">Fixed bibliographic entries.</change>
      <change who="#AG" when="2017-06-23">Removed some comments and responded to others.</change>
      <change who="#SB" when="2017-06-26">Changed biblScope to citedRange, g to g type=1, wit > resp, PR biblio entry.</change>
      <change who="#SB" when="2017-06-27">Added xml:lang="pyx" to edition; tagged ASB, ASI; expanded pyx section of language ident.</change>
      <change who="#SB" when="2017-07-08">bibl formatting fixes.</change>
      <change who="#SB" when="2017-07-12">Restored PPPB to the bibliography; it was accidentally deleted at some point.</change>
      <change who="#SB" when="2017-07-15">Implemented idno, origDate changes from xxxx's emails.</change>
      <change who="#SB" when="2017-07-15">Added xml:lang="en" to msItem.</change>
      <change who="#AG" when="2017-07-17">Removed full stops from textLang and language.</change>
    </revisionDesc>

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


require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Editions.php');
require_once (dirname(__FILE__) . '/../model/entities/Catalogs.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$retVal = array();
$errors = array();
$warnings = array();
$entLog = null;
$log = "";
$title = null;
$entity = null;
$nextentity = null;
$date = date("Y-m-d");
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
//check for logging data
if ($data && (array_key_exists('ednID',$data) || array_key_exists('catID',$data)) &&
    array_key_exists('logentry',$data)) {
  if (array_key_exists('ednID',$data)) {
    $jsonCacheKey = 'edn'.$data['ednID'].'changelog';
    $entity = new Edition($data['ednID']);
  } else {
    $jsonCacheKey = 'cat'.$data['catID'].'changelog';
    $entity = new Catalog($data['catID']);
  }
  //update cached log for entity
  if ($entity && !$entity->isMarkedDelete() && !$entity->isReadonly() &&
      !$entity->hasError()) {
    $jsonCache = new JsonCache($jsonCacheKey);
    if ($jsonCache && $jsonCache->getID() && !$jsonCache->hasError()) {
      $entLog = $jsonCache->getJsonString();
      if (!$entLog) {
        $entLog = array();
      } else {
        $entLog = json_decode($entLog,true);
      }
    } else {
      $jsonCache->setLabel($jsonCacheKey);
      $entLog = array();
    }
    $logEntry = $data['logentry'];
    if (array_key_exists('logusername',$data)) {
      $username = $data['logusername'];
    } else {
      $userID = $entity->getScratchProperty('editUserID');
      if (!$userID) {
        $userID = getUserID();
      }
      $user = new UserGroup($userID);
      if ($user && $user->getID() == $userID){
        $username = $user->getFirstname()." ".$user->getFamilyName();
      } else {
        $username = getUserName();
      }
    }
    if (array_key_exists('logdate',$data)) {
      $date = $data['logdate'];
    }
    if (!array_key_exists($date,$entLog)) {
      $entLog[$date] = array(array("loguser"=>$username, "logentry"=>$logEntry));
    } else {
      array_push($entLog[$date], array("loguser"=>$username, "logentry"=>$logEntry));
    }
    $jsonCache->setJsonString(json_encode($entLog));
    $jsonCache->save();
    $log .= "Added $username's entry to log for ".$entity->getEntityTag()." <br/> [$date] $logEntry <br/>";
    //if configured to maintain logfiles then pretty print to log file.
    //if signalled continue editing else release
    if (array_key_exists('retainEdit',$data)) {
      $entity->storeScratchProperty('status','editing');
      $log .= "Set status for ".$entity->getEntityTag()." to editing <br/>";
    } else {//release entity from edit
      $entity->storeScratchProperty('editUserName',null);
      $entity->storeScratchProperty('editUserID',null);
      $entity->storeScratchProperty('status',null);
      $log .= "Released ".$entity->getEntityTag()." from editing <br/>";
    }
    $entity->save();
  } else {
//    array_push($errors," unable to access/use entity ". $entity->getEntityTag());
    $log .= "Error trying to access log for ".$entity->getEntityTag().", log not updated <br/>";
  }
} else if ($data && array_key_exists('skip',$data)) {
  $log .= "Skipping entity logging, searching for next entity <br/>";
} else if ($data && array_key_exists('cancel',$data)) {
  $log .= "Canceling entity logging, clearing process log <br/>";
  unset($_SESSION['processlog']);
}
// find next entity to process
if (array_key_exists('processlog',$_SESSION)) {
  $catID = null;
  $ednID = null;
  $nextentity = null;
  if (array_key_exists('catIDs',$_SESSION['processlog'])) {
    if (count($_SESSION['processlog']['catIDs']) > 0) {
      while (!$catID) {
        $catID = array_shift($_SESSION['processlog']['catIDs']);
        $nextentity = new Catalog($catID);
        if (!$nextentity || !$nextentity->getID() || $nextentity->hasError()) {
//          array_push($errors," unable to access/use entity cat$catID");
          $log .= "Unable to access/use entity cat$catID <br/>";
          $catID = null;
          $nextentity = null;
        } else {
          break;
        }
      }
    }
    if (count($_SESSION['processlog']['catIDs']) == 0) {
      unset($_SESSION['processlog']['catIDs']);
      $log .= "Completed processing catalog entities <br/>";
    }
  }
  if (!$nextentity && array_key_exists('ednIDs',$_SESSION['processlog'])) {
    if (count($_SESSION['processlog']['ednIDs']) > 0) {
      while (!$ednID) {
        $ednID = array_shift($_SESSION['processlog']['ednIDs']);
        $nextentity = new Edition($ednID);
        if (!$nextentity || !$nextentity->getID() || $nextentity->hasError()) {
//          array_push($errors," unable to access/use entity edn$ednID");
          $log .= "Unable to access/use entity edn$ednID <br/>";
          $ednID = null;
          $nextentity = null;
        } else {
          break;
        }
      }
    }
    if (count($_SESSION['processlog']['ednIDs']) == 0) {
      unset($_SESSION['processlog']['ednIDs']);
      $log .= "Completed processing edition entities <br/>";
    }
  }
  if (!$nextentity) {
    unset($_SESSION['processlog']);
    $log .= "Finished processing. Removing process log list from session <br/>";
  }
} else {
  $log .= "Nothing to process <br/>";
}

if ($nextentity) {
  $nextentityType = $nextentity->getEntityTypeCode();
  $nextentityID = $nextentity->getID();
  if ($nextentity->getEntityTypeCode()== 'cat') {
      $title = $nextentity->getTitle();
  } else {
    $title = $nextentity->getDescription();
  }

  $userID = $nextentity->getScratchProperty('editUserID');
  if (!$userID) {
    $userID = getUserID();
  }
  $user = new UserGroup($userID);
  if ($user && $user->getID() == $userID){
    $username = $user->getFirstname()." ".$user->getFamilyName();
  } else {
    $username = getUserName();
  }
  $status = $nextentity->getScratchProperty('status');

  $retVal["nextEntityType"] = $nextentityType;
  $retVal["nextEntityID"] = $nextentityID;
  $retVal["nextEntityTitle"] = $title;
  $retVal["editUserName"] = $username;
  $retVal["editUserID"] = $userID;
  $retVal["status"] = $status;
  $retVal["date"] = $date;
}
$retVal["success"] = false;
if ($entLog){
  $retVal["entlog"] = $entLog;
}
if ($log){
  $retVal["log"] = $log;
}
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
?>
