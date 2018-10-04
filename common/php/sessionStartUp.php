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

//check if session is active else start browser (user-agent) session.
if (!isset($_SESSION)) {
  session_start(); //will open existing session if exist
}

$sessid = session_id();
if (!$sessid) { // new session so may need to include default database from config.php
  require_once (dirname(__FILE__) . '/../../config.php');
}

//identify the DB name
global $dbn;
$dbn = null;
if (defined('DBNAME')) {
  $dbn = DBNAME;
} else if (isset($_REQUEST['db'])) {
  $dbn = $_REQUEST['db'];
} else if (isset($SERVER['QUERY_STRING'])) {
//parse the dbname from QUERY_STRING
  $params = explode('&',$SERVER['QUERY_STRING']);
  foreach($params as $param) {
    list($prop,$val) = explode('=',$param);
    if ($prop == 'db'){
      $dbn = $val;
    }
  }
}

//check session for user data for selected DB or from default
if (isset($_SESSION['readSessions']) && $dbn && isset($_SESSION['readSessions'][$dbn])) {
  $logged = $_SESSION['readSessions'][$dbn]['ka_status'];
  $username = $_SESSION['readSessions'][$dbn]['ka_username'];
  $userID = $_SESSION['readSessions'][$dbn]['ka_userid'];
  $membershipIDs = array_keys($_SESSION['readSessions'][$dbn]['ka_groups']);
} else if (isset($_SESSION['ka_status'])) {//single db with single session format
  $logged = $_SESSION['ka_status'];
  $username = $_SESSION['ka_username'];
  $userID = $_SESSION['ka_userid'];
  $membershipIDs = array_keys($_SESSION['ka_groups']);
} else if ($_COOKIE && isset($_COOKIE['ka_username_'.$dbn])) {
  //check cookie validity
  require_once (dirname(__FILE__) . '/DBManager.php');
  $dbMgr = new DBManager();
  $ugrID = isValidLoginCookie($dbMgr);
  if ($ugrID) {
    setSessionUserLogin($dbMgr,$ugrID);
  }
}
//falling through set access functions to use guest account values as a pseudo login

function set_session($id, $userID, $user, $name, $email, $edit, $groupIDs, $userPrefs = null) {
  global $dbn;
  if (!isset($_SESSION)) {
    return false;
  }
  if (!isset($_SESSION['readSessions'])) {
    $_SESSION['readSessions'] = array();
  }
  if (!isset($_SESSION['readSessions'][$dbn])) {
    $_SESSION['readSessions'][$dbn] = array();
  }
  $_SESSION['readSessions'][$dbn]['ka_id'] = $id;
  $_SESSION['readSessions'][$dbn]['ka_userid'] = $userID;
  $_SESSION['readSessions'][$dbn]['ka_username'] = $user;
  $_SESSION['readSessions'][$dbn]['ka_name'] = $name;
  $_SESSION['readSessions'][$dbn]['ka_email'] = $email;
  $_SESSION['readSessions'][$dbn]['ka_status'] = "in";
  $_SESSION['readSessions'][$dbn]['ka_edit'] = $edit;
  $_SESSION['readSessions'][$dbn]['ka_groups'] = $groupIDs;
  if ($userPrefs) {
    $_SESSION['readSessions'][$dbn]['userPrefs'] = $userPrefs;
  } else if (isset($_SESSION['readSessions'][$dbn]['userPrefs'])) {
    unset($_SESSION['readSessions'][$dbn]['userPrefs']);
  }
}

function setSessionUserLogin($dbMgr,$userID) {
  $dbname = null;
  if ($dbMgr && $dbMgr->isConnected()) {
    $dbname = $dbMgr->getDBName();
  }
  if (!isset($_SESSION) || !$dbname) {
    return false;
  }
  unsetSessionUserLogin($dbname);
  $dbMgr->query("SELECT * FROM usergroup WHERE ".$userID." = ANY(\"ugr_member_ids\") OR ugr_id in (2,3,6)");
  $groups = array();
  if ($dbMgr->getRowCount() > 0) {
    while ($row = $dbMgr->fetchResultRow(null,false,PGSQL_ASSOC)) {
      $groups[$row['ugr_id']] = array(
        "ugr_id"=>$row['ugr_id'],
        "ugr_name"=>$row['ugr_name'],
        "ugr_type_id"=>$row['ugr_type_id'],
        "ugr_given_name"=>$row['ugr_given_name'],
        "ugr_family_name"=>$row['ugr_family_name'],
        "ugr_description"=>$row['ugr_description']
      );
    }
  }
  $dbMgr->query("SELECT * FROM usergroup WHERE ugr_id = $userID");
  $user = $dbMgr->fetchResultRow();
  if ($user) {
    set_session(session_id (), $user['ugr_id'], $user['ugr_name'], $user['ugr_given_name']." ".$user['ugr_family_name'], null, null, (isset($groups)?$groups:null));
    return array($user, $groups);
  }
  return false;
}

function unsetSessionUserLogin($dbname) {
  if (!$dbname) {
    return false;
  }
  if (isset($_SESSION['readSessions'][$dbname])) {
    unset($_SESSION['readSessions'][$dbname]);
  }
  if (isset($_SESSION['userPrefs'][$dbname])) {
    unset($_SESSION['userPrefs'][$dbname]);
  }
  return true;
}

function isValidLoginCookie($dbMgr) {
  $userID = 0;
  $selector = $validator = "";
  if ($_COOKIE && isset($_COOKIE['ka_username_'.$dbMgr->getDBName()])) {
    //separate selector and validator
    list($selector,$validator) = explode(':', $_COOKIE['ka_username_'.$dbMgr->getDBName()]);
  }
  //validate token
  //check for selector row in DB, ignore on not present
  if ($selector && $validator) {
    $curTime = time();
    $dbMgr->query("select * from authtoken where aut_selector='$selector' and aut_expire > $curTime;");
    if ($dbMgr->getRowCount() == 1) {
      $autTokRow = $dbMgr->fetchResultRow(null,false,PGSQL_ASSOC);
      //compare hashed in db with hash of validator
      $hashedValidator = $autTokRow['aut_hashed_validator'];
      //valid then return userID and regen validator, update auth table and 
      if (base64_encode(hash('sha384', $validator, true)) === $hashedValidator) {
        setLoginCookie($dbMgr, $selector);
        return $autTokRow['aut_user_id'];
      }
    }
  }
  //invalid remove from authtoken and cookie
  if ($selector) {
    $dbMgr->query("delete from authtoken where aut_selector=$selector");
  }
  unsetLoginCookie($dbMgr);
  return $userID;
}

function setLoginCookie($dbMgr, $selector = "", $ugrID = null) {
  $autTokRow = null;
  if (!$dbMgr->isConnected() || !($selector || $ugrID)) {
    return false;
  }
  unsetLoginCookie($dbMgr);
  if (!$selector){
    $selector = getRandomString(16);
  }
  $validator = getRandomString(64);
  $hashedValidator = base64_encode(hash('sha384', $validator, true));
  $expiry = time() + (defined("NONUSECOOKIELIFETIME")?NONUSECOOKIELIFETIME:60*60*24*30);
  if ($selector) {
    $dbMgr->query("select * from authtoken where aut_selector='$selector'");
    if ($dbMgr->getRowCount() > 0) {//update existing
      $data = array("aut_hashed_validator"=>$hashedValidator,"aut_expire"=>$expiry);
      if ($ugrID) {
        $data["aut_user_id"] = $ugrID;
      }
      $dbMgr->update("authtoken",$data,"aut_selector='$selector'");
    } else if ($ugrID) {//new selector
      $data = array(
        "aut_selector"=>$selector,
        "aut_hashed_validator"=>$hashedValidator,
        "aut_expire"=>$expiry,
        "aut_user_id"=>$ugrID
      );
      $dbMgr->insert("authtoken",$data,"aut_id");
    } else {
      //can't set authtoken for abort
      return false;
    }
  }
  setcookie('ka_username_'.$dbMgr->getDBName(),$selector.":".$validator,$expiry,"/");
  return true;
}

function unsetLoginCookie($dbMgr) {
  if (isset($_COOKIE) && isset($_COOKIE['ka_username_'.$dbMgr->getDBName()])) {
    setcookie('ka_username_'.$dbMgr->getDBName(),"",time()-360,"/");
    unset($_COOKIE['ka_username_'.$dbMgr->getDBName()]);
  }
}

function getRandomString($length) {
  return bin2hex(openssl_random_pseudo_bytes($length));
}
