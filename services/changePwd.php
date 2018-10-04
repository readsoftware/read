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
* changePwd
*
* validates the username/password combination then updates password 
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

if (defined("WORKBENCH_BASE_URL")) {
  header("Access-Control-Allow-Origin: " . WORKBENCH_BASE_URL);
  header('Access-Control-Allow-Credentials: true');
}
if (!isLoggedIn()) {
  $retVal = array("error" => "Must be logged for request.");
} else {
  $username = getUserName();
  $userpwd = isset($_POST['password1'])?$_POST['password1'] : (isset($_REQUEST['password1'])?$_REQUEST['password1']:null);
  $newpassword = isset($_POST['password2'])?$_POST['password2'] : (isset($_REQUEST['password2'])?$_REQUEST['password2']:null);
  $hashed = (isset($_REQUEST['hashed']) && $_REQUEST['hashed']) ? TRUE : FALSE;
  if (!$hashed) {
    $userpwd = md5($userpwd);
  }
  $hashednewpassword = md5($newpassword);

  // CHECK USERS NAME AND PASSWORD
  $dbMgr = new DBManager();
  $dbMgr->query("SELECT * FROM usergroup WHERE ugr_name = '$username' AND ugr_password ='" . $userpwd . "'");

  if ($dbMgr->getRowCount() == 0) {
  //return error invalid login
    $retVal = array("error" => "Invalid request.");
  } else {
    $user = $dbMgr->fetchResultRow();
    $ugrID = $user["ugr_id"];
    $data = array("ugr_password"=>$hashednewpassword);
    $dbMgr->update("usergroup",$data,"ugr_id=$ugrID");
    if ($dbMgr->getError()) {
      $retVal = array("error" => "Error during processing id $ugrID: ". $dbMgr->getError());
    } else {
      $retVal = array("success" => 1);
    }
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
