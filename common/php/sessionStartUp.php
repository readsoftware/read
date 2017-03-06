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

//	session_save_path ("../protect/sessions");
session_start ();
//	session_cache_limiter ('private_no_expire');
session_id ();

if (isset($_SESSION['ka_status'])) {
  $logged = $_SESSION['ka_status'];
  $username = $_SESSION['ka_username'];
  $userID = $_SESSION['ka_userid'];
  $membershipIDs = array_keys($_SESSION['ka_groups']);
}
?>
