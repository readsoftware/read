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
  * cloneEdition
  *
    * clones an edition assigning it all defaults
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Services
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
  require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
  require_once (dirname(__FILE__) . '/../model/entities/UserGroup.php');
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $entities = array();
  $warnings = array();
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    array_push($errors,"invalid json data - decode failed");
  } else {
    $defAttrIDs = getUserDefAttrIDs();
    $defVisIDs = getUserDefVisibilityIDs();
    $defOwnerID = getUserDefEditorID();
    if ( isset($data['ednID'])) {//get edition
      $ednID = $data['ednID'];
      $edition = new Edition($ednID);
      if ($edition->hasError()) {
        array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
      } else if (!$edition->isPublic() && $edition->isReadonly()) {
        array_push($errors,"non public edition readonly edition cannot be cloned");
      } else {
        $ednOwnerID = $edition->getOwnerID();
        $userName = getUserName();
        $userID = getUserID();
        $userGroupTypeID = Entity::getIDofTermParentLabel('group-grouptype');
        if ($defOwnerID == $userID or $ednOwnerID == $defOwnerID) { // user has "Edit As" set to self and editing will not cause cloing of individual entities unless we use a different owner id
          // find or create user namesake group not used by an edition in this text. Assume group names are formatted as username + number (starting at 1)
          // so username of Steve  would generate/use usergroups of Steve1, Steve2, ... SteveN where there are N + 1 editions
          $query = "select ugr_id, ugr_name, $userID = ANY(ugr_admin_ids) as is_selfgroup, ".
                      "(replace(ugr_name,'$userName','') ~ '^([0-9]+)$') as is_num, ".
                      "replace(ugr_name,'$userName','') as lastnum, ".
                      "ugr_type_id = $userGroupTypeID as is_group, ".
                      "ugr_id = ANY(select distinct ugr_id from edition ce ".
                                      "left join edition te on ce.edn_text_id = te.edn_text_id ".
                                      "left join usergroup on ugr_id = te.edn_owner_id ".
                                    "where ce.edn_id = $ednID and ".
                                          "ugr_name ~ '^$userName([0-9]+)$' and ".
                                          "not te.edn_owner_id = 1) as is_used ".
                   "from usergroup ".
                   "where ugr_name like '$userName%' and ".
                   "not ugr_id = $userID ".
                   "order by ugr_name";
          $defOwnerID = null;
          $lastUgrNum = "0";
          $dbMgr = new DBManager();
          $dbMgr->query($query);
          $cntUnusedGroups = $dbMgr->getRowCount();
          if ($cntUnusedGroups>0) {
            for($i=0; $i < $cntUnusedGroups; $i++) {
              $row = $dbMgr->fetchResultRow();
              if ($row['is_num'] == 't') {
                $lastUgrNum = $row['lastnum'];
              } else {//name doesn't match usernameN pattern exact so skip
                continue;
              }
              if ($row['is_selfgroup'] == 'f' || $row['is_group'] == 'f' || $row['is_used'] == 't') { // not this users group or already used so skip
                continue;
              }
              $defOwnerID = $row['ugr_id']; // found unused username# group so use as owner of clone
              break;
            }
          }
          if (!$defOwnerID) {//no username# group found so create one without name collision
            $user = new UserGroup($userID);
            //find max N for usernameN
            $query = "select ugr_type_id, max(replace(ugr_name,'$userName','')::int) as maxnum ".
                     "from usergroup ".
                     "where ugr_name ~ '^$userName([0-9]+)$' ".
                     "group by ugr_type_id order by maxnum desc";
            $dbMgr->query($query);
            if ($dbMgr->getRowCount()) {
              $row = $dbMgr->fetchResultRow();
              $newGrpNum = 1 + intval($row['maxnum']);
            } else {
              $newGrpNum = 1;
            }
            $newGroupName = $userName.$newGrpNum;
            $ugroup = new UserGroup();
            $ugroup->setName($newGroupName);
            $ugroup->setFamilyName($user->getRealname()." UserGroup $newGrpNum");
            $ugroup->setType($userGroupTypeID); // warning!! term dependency
            $ugroup->setMemberIDs(array($userID));
            $ugroup->setAdminIDs(array($userID));
            $ugroup->save();
            if ($ugroup->hasError()) {
              array_push($errors,"Error while creating personal usergroup for cloning: ".join(",",$ugroup->getErrors()));
            } else {
              $defOwnerID = $ugroup->getID();
            }
          }
          //set defOwnerID to group ID
        }
        if ($defOwnerID) {
          $ugroup = new UserGroup($defOwnerID);
          $newEdition = $edition->cloneEntity($defAttrIDs,$defVisIDs,$defOwnerID);
          //change title
          $newEdition->setDescription("Copy of ".$edition->getDescription()." for ".$ugroup->getName());
          $newEdition->setTypeID($newEdition->getIDofTermParentLabel('research-editiontype'));//term dependency
          $newEdition->save();
          addNewEntityReturnData('edn',$newEdition);
        } else {
          array_push($errors,"unable to find usergroup as owner for edition");
        }
      }
    } else {
      array_push($errors,"unaccessable edition");
    }
  }

  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  if (count($entities)) {
    $retVal["entities"] = $entities;
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
