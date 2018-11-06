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
* logout
*
* destroys session and associated cookies
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Authorization
*/
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
// Initialize the session.
// If you are using session_name("something"), don't forget it now!
if (!isset($_SESSION)) {
  session_start();
}
if (!isset($dbn)) {
  $dbn = DBNAME;
}
//clean up persistant login
if ($_COOKIE && isset($_COOKIE['ka_username_'.$dbn])) {
  //separate selector and validator
  list($selector,$validator) = explode(':', $_COOKIE['ka_username_'.$dbn]);
  $dbMgr = new DBManager();
  //remove authtoken
  if ($selector) {
    $dbMgr->query("delete from authtoken where aut_selector='$selector'");
  }
  //remove cookie
  unsetLoginCookie($dbMgr);
}
//cleanup db specific session login info
unsetSessionUserLogin($dbn);

//last person out shut off the lights
if (!isset($_SESSION["readSessions"]) || (count($_SESSION["readSessions"]) == 0)) {
  // If it's desired to kill the session, also delete the session cookie.
  // Note: This will destroy the session, and not just the session data!
  if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 60*60*24*100,
          $params["path"], $params["domain"],
          $params["secure"], $params["httponly"]
      );
  }
/*
  try {
    session_gc();
    session_destroy();
  }
  catch (Exception $e) {
    error_log(print_r($e,true));
  }
  */
}

$retVal = array("success" => 1);

if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
