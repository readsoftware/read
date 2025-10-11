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
*
* SECURITY ENHANCEMENTS:
* - Input validation to prevent SQL injection
* - Rate limiting to prevent brute force attacks  
* - Enhanced logging for security monitoring
* - Proper string escaping for database queries
*
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

  // SECURITY: Rate limiting to prevent brute force attacks
  $client_ip = $_SERVER['REMOTE_ADDR'];
  $max_attempts = 5;
  $lockout_time = 300; // 5 minutes
  $cache_key = 'changepwd_attempts_' . md5($client_ip);

  // Simple rate limiting using file-based cache
  $attempts_file = sys_get_temp_dir() . '/' . $cache_key;
  $current_time = time();

  if (file_exists($attempts_file)) {
      $attempts_data = json_decode(file_get_contents($attempts_file), true);
      if ($attempts_data && $attempts_data['count'] >= $max_attempts && 
          ($current_time - $attempts_data['first_attempt']) < $lockout_time) {
          $retVal = array("error" => "Too many failed attempts. Please try again later.");
          error_log("Change password rate limit exceeded for IP: " . $client_ip);
          print json_encode($retVal);
          exit;
      }
      // Reset if lockout period has passed
      if (($current_time - $attempts_data['first_attempt']) >= $lockout_time) {
          unlink($attempts_file);
      }
  }

  // CHECK USERS NAME AND PASSWORD
  $dbMgr = new DBManager();

  // SECURITY: Input validation to prevent SQL injection
  // Validate username: only allow alphanumeric, underscore, hyphen, and dot
  if (!preg_match('/^[a-zA-Z0-9._-]{1,50}$/', $username)) {
      $retVal = array("error" => "Invalid username format.");
      error_log("Change password attempt with invalid username format: " . var_export($username, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
  } else if (strlen($userpwd) > 255 || strlen($newpassword) > 255) {
      // Prevent extremely long passwords that could cause DoS
      $retVal = array("error" => "Invalid password format.");
      error_log("Change password attempt with excessive password length from IP: " . $_SERVER['REMOTE_ADDR']);
  } else {
      // Use secure string escaping - escape quotes and backslashes
      $safe_username = str_replace(array("'", "\\"), array("''", "\\\\"), $username);
      $safe_userpwd = str_replace(array("'", "\\"), array("''", "\\\\"), $userpwd);
      
      $query = "SELECT * FROM usergroup WHERE ugr_name = '" . $safe_username . "' AND ugr_password = '" . $safe_userpwd . "'";
      $dbMgr->query($query);

      if ($dbMgr->getRowCount() == 0) {
          //return error invalid login
          $retVal = array("error" => "Invalid request.");
          
          // SECURITY: Track failed password change attempts
          $attempts_data = array('count' => 1, 'first_attempt' => $current_time);
          if (file_exists($attempts_file)) {
              $existing_data = json_decode(file_get_contents($attempts_file), true);
              if ($existing_data) {
                  $attempts_data['count'] = $existing_data['count'] + 1;
                  $attempts_data['first_attempt'] = $existing_data['first_attempt'];
              }
          }
          file_put_contents($attempts_file, json_encode($attempts_data));
          error_log("Failed password change attempt for username: $username from IP: " . $client_ip);
      } else {
          // SECURITY: Clear failed attempts on successful validation
          if (file_exists($attempts_file)) {
              unlink($attempts_file);
          }
          
          $user = $dbMgr->fetchResultRow();
          $ugrID = $user["ugr_id"];
          $data = array("ugr_password"=>$hashednewpassword);
          $dbMgr->update("usergroup",$data,"ugr_id=$ugrID");
          if ($dbMgr->getError()) {
              $retVal = array("error" => "Error during processing id $ugrID: ". $dbMgr->getError());
              error_log("Database error during password change for user ID $ugrID: " . $dbMgr->getError());
          } else {
              $retVal = array("success" => 1);
              error_log("Successful password change for username: $username (ID: $ugrID) from IP: " . $client_ip);
          }
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
