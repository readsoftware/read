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
 * User Access Control
 *
 * @author      Stephen White  <stephenawhite57@gmail.com>
 * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
 * @link        https://github.com/readsoftware
 * @version     1.0
 * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
 * @package     READ Research Environment for Ancient Documents
 * @subpackage  Common
 */
  require_once (dirname(__FILE__) . '/../../common/php/sessionStartUp.php');//initialize the session

  /**
  * isLoggedIn returns true is the current session's user is not guest/general public.
  *
  */
  function isLoggedIn() {
    return getUserID() !=2; //WARNING!! this depends on the UserGroup table having a special setup
  }

  /**
  * getUserID returns the current sessions userID or id for guest/general public.
  *
  */
  function getUserID() {
    global $userID;
    return (isset($_SESSION['ka_userid']) && count($_SESSION['ka_userid'])) ? intval($_SESSION['ka_userid']):(isset($userID)?$userID:2);
  }

  /**
  * impersonate temporarily sets the session variables saving current values for restore.
  *
  */
  function impersonateUser($userID,$userName,$groups) {
    if (isset($_SESSION['ka_userRestore'])) {
      return false;
    }
    $restore = array( isset($_SESSION['ka_userid'])?$_SESSION['ka_userid']:"",
                      isset($_SESSION['ka_username'])?$_SESSION['ka_username']:"",
                      isset($_SESSION['ka_groups'])?$_SESSION['ka_groups']:"");
    $_SESSION['ka_userRestore'] = $restore;
    if ($userID) {
      $_SESSION['ka_userid']= $userID;
    }
    if ($userName) {
      $_SESSION['ka_username'] = "impersonating(".$userName.")";
    }
    if ($groups) {
      $_SESSION['ka_groups'] = $groups;
    }
    return true;
  }

  /**
  * restore user from temporary impersonation.
  *
  */
  function restoreUser() {
    if (!isset($_SESSION['ka_userRestore'])) {
      return false;
    }
    list($userID,$userName,$groups) = $_SESSION['ka_userRestore'];
    unset($_SESSION['ka_userRestore']);
    if ( $userID == "" ) {
      unset($_SESSION['ka_userid']);
    }else{
      $_SESSION['ka_userid']= $userID;
    }
    if ( $userName == "" ) {
      unset($_SESSION['ka_username']);
    }else{
      $_SESSION['ka_username']= $userName;
    }
    if ( $groups == "" ) {
      unset($_SESSION['ka_groups']);
    }else{
      $_SESSION['ka_groups']= $groups;
    }
    return true;
  }

  /**
  * getUserMembership returns the current session user's membershipIDs or id for guest/general public.
  *
  */
  function getUserMembership() {
    global $userID;
    return (isset($_SESSION['ka_groups']) && count($_SESSION['ka_groups'])) ? array_keys($_SESSION['ka_groups']): (isLoggedIn()? ($userID?array($userID,2,3):array(2,3)): array(2));
  }

  /**
  * getUserName returns the current session user's name or guest.
  *
  */
  function getUserName() {
    return (isset($_SESSION['ka_username']) && count($_SESSION['ka_username'])) ? $_SESSION['ka_username']:"Guest";
  }

  /**
  * isSysAdmin returns true is the current sessions user is a system admin, false otherwise
  *
  */
  function isSysAdmin() {
    return in_array(1,getUserMembership());
  }

  function session_defaults() {
    $_SESSION['ka_id'] = NULL;
    $_SESSION['ka_userid'] = NULL;
    $_SESSION['ka_username'] = '';
    $_SESSION['ka_name'] = '';
    $_SESSION['ka_email'] = '';
    $_SESSION['ka_last_url'] = '';
    $_SESSION['ka_status'] = "out";
    $_SESSION['ka_group'] = '';
  }

  function set_session($id, $userID, $user, $name, $email, $edit, $groupIDs) {
    $_SESSION['ka_id'] = $id;
    $_SESSION['ka_userid'] = $userID;
    $_SESSION['ka_username'] = $user;
    $_SESSION['ka_name'] = $name;
    $_SESSION['ka_email'] = $email;
    $_SESSION['ka_status'] = "in";
    $_SESSION['ka_edit'] = $edit;
    $_SESSION['ka_groups'] = $groupIDs;
  }

  ?>
