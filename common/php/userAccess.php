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
    return getUserID() !=6; //WARNING!! this depends on the UserGroup table having a special setup
  }

  /**
  * getUserID returns the current sessions userID or id for guest/general public.
  *
  */
  function getUserID() {
    global $userID, $dbn;
    return (isset($_SESSION['readSessions']) && isset($_SESSION['readSessions'][$dbn]) &&
            isset($_SESSION['readSessions'][$dbn]['ka_userid']) && 
            strlen($_SESSION['readSessions'][$dbn]['ka_userid']) > 0) ? intval($_SESSION['readSessions'][$dbn]['ka_userid']):(isset($userID)?$userID:6);
  }

  /**
  * impersonate temporarily sets the session variables saving current values for restore.
  *
  */
  function impersonateUser($userID,$userName,$groups) {
    global $dbn;
    if (isset($_SESSION['readSessions']) && isset($_SESSION['readSessions'][$dbn])) {
      if (isset($_SESSION['readSessions'][$dbn]['ka_userRestore'])) {
        return false;
      }
      $restore = array( isset($_SESSION['readSessions'][$dbn]['ka_userid'])?$_SESSION['readSessions'][$dbn]['ka_userid']:"",
                        isset($_SESSION['readSessions'][$dbn]['ka_username'])?$_SESSION['readSessions'][$dbn]['ka_username']:"",
                        isset($_SESSION['readSessions'][$dbn]['ka_groups'])?$_SESSION['readSessions'][$dbn]['ka_groups']:"");
      $_SESSION['readSessions'][$dbn]['ka_userRestore'] = $restore;
      if ($userID) {
        $_SESSION['readSessions'][$dbn]['ka_userid']= $userID;
      }
      if ($userName) {
        $_SESSION['readSessions'][$dbn]['ka_username'] = "impersonating(".$userName.")";
      }
      if ($groups) {
        $_SESSION['readSessions'][$dbn]['ka_groups'] = $groups;
      }
      return true;
    }
  }

  /**
  * restore user from temporary impersonation.
  *
  */
  function restoreUser() {
    if (isset($_SESSION['readSessions']) && isset($_SESSION['readSessions'][$dbn])) {
      if (!isset($_SESSION['readSessions'][$dbn]['ka_userRestore'])) {
        return false;
      }
      list($userID,$userName,$groups) = $_SESSION['readSessions'][$dbn]['ka_userRestore'];
      unset($_SESSION['readSessions'][$dbn]['ka_userRestore']);
      if ( $userID == "" ) {
        unset($_SESSION['readSessions'][$dbn]['ka_userid']);
      }else{
        $_SESSION['readSessions'][$dbn]['ka_userid']= $userID;
      }
      if ( $userName == "" ) {
        unset($_SESSION['readSessions'][$dbn]['ka_username']);
      }else{
        $_SESSION['readSessions'][$dbn]['ka_username']= $userName;
      }
      if ( $groups == "" ) {
        unset($_SESSION['readSessions'][$dbn]['ka_groups']);
      }else{
        $_SESSION['readSessions'][$dbn]['ka_groups']= $groups;
      }
      return true;
    }
  }

  /**
  * getUserMembership returns the current session user's membershipIDs or id for guest/general public.
  *
  */
  function getUserMembership() {
    global $userID,$dbn;
    return (isset($_SESSION['readSessions']) && isset($_SESSION['readSessions'][$dbn]) &&
            isset($_SESSION['readSessions'][$dbn]['ka_groups']) && 
            count($_SESSION['readSessions'][$dbn]['ka_groups'])) ? array_keys($_SESSION['readSessions'][$dbn]['ka_groups']): 
                                                                  (isLoggedIn()? ($userID?array($userID,2,3,6):array(2,3,6)): array(2,6));
  }

  /**
  * getUserName returns the current session user's name or guest.
  *
  */
  function getUserName() {
    global $dbn;
    return (isset($_SESSION['readSessions']) && isset($_SESSION['readSessions'][$dbn]) &&
            isset($_SESSION['readSessions'][$dbn]['ka_username']) && 
            strlen($_SESSION['readSessions'][$dbn]['ka_username'])) ? $_SESSION['readSessions'][$dbn]['ka_username']:"Guest";
  }

  /**
  * isSysAdmin returns true is the current sessions user is a system admin, false otherwise
  *
  */
  function isSysAdmin() {
    return in_array(1,getUserMembership());
  }

  function session_defaults() {
    global $dbn;
    $_SESSION['readSessions'][$dbn]['ka_id'] = NULL;
    $_SESSION['readSessions'][$dbn]['ka_userid'] = NULL;
    $_SESSION['readSessions'][$dbn]['ka_username'] = '';
    $_SESSION['readSessions'][$dbn]['ka_name'] = '';
    $_SESSION['readSessions'][$dbn]['ka_email'] = '';
    $_SESSION['readSessions'][$dbn]['ka_last_url'] = '';
    $_SESSION['readSessions'][$dbn]['ka_status'] = "out";
    $_SESSION['readSessions'][$dbn]['ka_group'] = '';
  }
