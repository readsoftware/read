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
* login
*
* validates the username/password combination returning the users realname and initialising the session
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Authorization
*/
//session_start ();
//session_id ();
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface

if (defined("WORKBENCH_BASE_URL")) {
  header("Access-Control-Allow-Origin: " . WORKBENCH_BASE_URL);
  header('Access-Control-Allow-Credentials: true');
}

$username = isset($_POST['username'])?$_POST['username'] : (isset($_REQUEST['username'])?$_REQUEST['username']:null);
$password = isset($_POST['password'])?$_POST['password'] : (isset($_REQUEST['password'])?$_REQUEST['password']:null);
$hashed = (isset($_REQUEST['hashed']) && $_REQUEST['hashed']) ? TRUE : FALSE;
if (!$hashed) {
  $password = md5($password);
}
//$expiry = (array_key_exists('persist',$_POST) || array_key_exists('persist',$_REQUEST))? (time ()+60*60*24*365) : 0;
$persist = (array_key_exists('persist',$_POST) || array_key_exists('persist',$_REQUEST))? true: false;

// CHECK USERS NAME AND PASSWORD
$dbMgr = new DBManager();
$query = "SELECT * FROM usergroup WHERE ugr_name = '$username' AND ugr_password ='" . $password . "'";
//error_log("query login: $query");
$dbMgr->query($query);

if ($dbMgr->getRowCount() == 0) {
//return error invalid login
  $retVal = array("error" => "Invalid username or password.");
//  return false;
} else {
  $user = $dbMgr->fetchResultRow();
  if ($user['ugr_type_id'] != 511) { 
    $retVal = array("error" => "Invalid username or password.");
  } else {
    $ret = setSessionUserLogin($dbMgr,$user["ugr_id"]);
    $groups = array();
    if (is_array($ret) && count($ret) == 2) {
      $groups = $ret[1];
    }
    if ($persist){
      setLoginCookie($dbMgr,"",$user["ugr_id"]);
    }
    $retVal = array("success" => 1,
                    "familyName" => $user['ugr_family_name'],
                    "givenName" => $user['ugr_given_name'],
                    "fullname" => $user['ugr_given_name']." ".$user['ugr_family_name'],
                    "description" => $user['ugr_description'],
                    "groups" => $groups
                    );
  }
// cleanup any cache that might be user specific
  if (isset($_SESSION['ka_searchAllResults_'.DBNAME])) {
    unset($_SESSION['ka_searchAllResults_'.DBNAME]);
  }
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
