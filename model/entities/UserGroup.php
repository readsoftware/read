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
  * Classes to deal with UserGroup entities
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Entity Classes
  */
  require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/Entity.php');

//*******************************************************************
//****************   USERGROUP CLASS    **********************************
//*******************************************************************
  /**
  * UserGroup represents UserGroup entity member group for attribution
  *
  * <code>
  * require_once 'UserGroup.php';
  *
  * $UserGroup = new UserGroup( $resultRow );
  * echo $UserGroup->getName();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class UserGroup extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_name,
              $_type_id,
              $_given_name,
              $_family_name,
              $_description,
              $_member_ids=array(),
              $_members,
              $_admin_ids=array(),
              $_admins;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an UserGroup instance from an UserGroup table row
    * @param array $row associated with columns of the UserGroup table, a valid ugr_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'usergroup';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
        $dbMgr->query("SELECT * FROM usergroup WHERE ugr_id = $arg".
                      (isSysAdmin()?"":
                        " AND (".getUserID()." = ANY (\"ugr_member_ids\") or ".
                                 getUserID()." = ANY (\"ugr_admin_ids\"))")." LIMIT 1");
//        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('ugr_id',$row)) {
          error_log("unable to query for UserGroup ID = $arg ");
        }else{
          $arg = $row;
        }
      } else if (is_string($arg) && strlen($arg) > 0) {// try the name
        $dbMgr = new DBManager();
        $dbMgr->query("SELECT * FROM usergroup WHERE ugr_name = '$arg'".
                      (isSysAdmin()?"":
                        " AND (".getUserID()." = ANY (\"ugr_member_ids\") or ".
                                 getUserID()." = ANY (\"ugr_admin_ids\"))")." LIMIT 1");
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('ugr_id',$row)) {
          error_log("unable to query for usergroup name = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['ugr_id'] ? $arg['ugr_id']:NULL;
        $this->_name=@$arg['ugr_name'] ? $arg['ugr_name']:NULL;
        $this->_type_id=@$arg['ugr_type_id'] ? $arg['ugr_type_id']:NULL;
        $this->_given_name=@$arg['ugr_given_name'] ? $arg['ugr_given_name']:NULL;
        $this->_family_name=@$arg['ugr_family_name'] ? $arg['ugr_family_name']:NULL;
        $this->_description=@$arg['ugr_description'] ? $arg['ugr_description']:NULL;
        $this->_member_ids=@$arg['ugr_member_ids'] ? $arg['ugr_member_ids']:NULL;
        $this->_admin_ids=@$arg['ugr_admin_ids'] ? $arg['ugr_admin_ids']:NULL;
        if (!array_key_exists('ugr_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('ugr_member_ids',$arg))$arg['ugr_member_ids'] = $this->idsToString($arg['ugr_member_ids']);
          if (array_key_exists('ugr_admin_ids',$arg))$arg['ugr_admin_ids'] = $this->idsToString($arg['ugr_admin_ids']);
//          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new UserGroup to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    *
    * @see Entity::getGlobalID
    */
    protected function getGlobalPrefix(){
      return "ugr";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_name)) {
        $this->_data['ugr_name'] = $this->_name;
      }
      if ($this->_type) {
        $this->_data['ugr_type_id'] = $this->_type;
      }
      if (count($this->_given_name)) {
        $this->_data['ugr_given_name'] = $this->_given_name;
      }
      if (count($this->_family_name)) {
        $this->_data['ugr_family_name'] = $this->_family_name;
      }
      if (count($this->_description)) {
        $this->_data['ugr_description'] = $this->_description;
      }
      if (count($this->_member_ids)) {
        $this->_data['ugr_member_ids'] = $this->idsToString($this->_member_ids);
      }
      if (count($this->_admin_ids)) {
        $this->_data['ugr_admin_ids'] = $this->idsToString($this->_admin_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********
    /**
    * Get Name of the UserGroup
    *
    * @return string represents a short human readable label for this UserGroup
    */
    public function getName() {
      return $this->_name;
    }

    /**
    * Get Type of the UserGroup
    *
    * @return string from a typology of terms for types of UserGroup
    */
    public function getTypeID() {
      return $this->_type_id;
    }

     /**
    * Get Firstname of the UserGroup
    *
    * @return string representing part 1 of long human readable label for this UserGroup
    */
    public function getGivenName() {
      return $this->_given_name;
    }

     /**
    * Get Lastname of the UserGroup
    *
    * @return string represents part 2 of long human readable label for this UserGroup
    */
    public function getFamilyName() {
      return $this->_family_name;
    }

     /**
    * Get Firstname of the UserGroup
    *
    * @return string representing part 1 of long human readable label for this UserGroup
    */
    public function getFirstname() {
      return $this->getGivenName();
    }

     /**
    * Get Lastname of the UserGroup
    *
    * @return string represents part 2 of long human readable label for this UserGroup
    */
    public function getLastname() {
      return $this->getFamilyName();
    }

     /**
    * Get Realname of the UserGroup
    *
    * @return string represents a long human readable label for this UserGroup
    */
    public function getRealname() {
      return $this->_given_name.' '.$this->_family_name;
    }

   /**
    * Gets the Description for this UserGroup
    * @return string for the Description
    */
    public function getDescription() {
      return $this->_description;
    }

   /**
    * Sets the preferences for this UserGroup
    * @return array of key id(s) pairs for this user
    */
    public function getPreferences() {
      return array (
                    'defaultEditUserID' => $this->getDefaultEditUserID(),
                    'defaultVisibilityIDs' => $this->getDefaultVisibilityIDs(),
                    'defaultAttributionIDs' => $this->getDefaultAttributionIDs());
    }

   /**
    * Gets the default Editor ID for this UserGroup
    * @return int foreign key to usergroup that represents owner for new entities
    */
    public function getDefaultEditUserID() {
      return $this->getScratchProperty('defaultEditUserID');
    }

   /**
    * Gets the default Visibility IDs for this UserGroup
    * @return int array foreign key to usergroups that can view an entity
    */
    public function getDefaultVisibilityIDs() {
      return $this->getScratchProperty('defaultVisibilityIDs');
    }

   /**
    * Gets the default Attribution IDs for this UserGroup
    * @return int array foreign key to attributions that represents those completing this work
    */
    public function getDefaultAttributionIDs() {
      $attrs = $this->getScratchProperty('defaultAttributionIDs');
      if (!$attrs) {
        $attrs = array( $this->getDefaultAttributionID());
      }
      return $attrs;
    }

   /**
    * Gets the default Attribution ID for this UserGroup
    * @return int foreign key to attribution that represents work inprogress by this user
    */
    public function getDefaultAttributionID() {
      return $this->getScratchProperty('defAttID');
    }

    /**
    * Get Unique IDs of attribution group entities which make up this UserGroup
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of attribution group entity IDs
    */
    public function getMemberIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_member_ids);
      }else{
        return $this->idsStringToArray($this->_member_ids);
      }
    }

    /**
    * Get UserGroup's Member Attribution Group objects
    *
    * @return Editions for this UserGroup or NULL
    */
    public function getMembers($autoExpand = false) {
      if (!$this->_members && $autoExpand && count($this->getMemberIDs())>0) {
        $this->_members = new UserGroups("ugr_id in (".join(",",$this->getMemberIDs()).")");
        $this->_members->setAutoAdvance(false);
      }
      return $this->_members;
    }

    /**
    * Get Unique IDs of attribution group entities which make up the admins for this UserGroup
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of attribution group entity IDs
    */
    public function getAdminIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_admin_ids);
      }else{
        return $this->idsStringToArray($this->_admin_ids);
      }
    }

    /**
    * Get UserGroup's Admin Attribution Group objects
    *
    * @return Admins for this UserGroup or NULL
    */
    public function getAdmins($autoExpand = false) {
      if (!$this->_admins && $autoExpand && count($this->getAdminIDs())>0) {
        $this->_admins = new UserGroups("ugr_id in (".join(",",$this->getAdminIDs()).")");
        $this->_admins->setAutoAdvance(false);
      }
      return $this->_admins;
    }


    //********SETTERS*********

    /**
    * Set Name of the UserGroup
    *
    * @param string $name represents a short human readable label for this UserGroup
    */
    public function setName($name) {
      if($this->_name != $name) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_name",$name);
      }
      $this->_name = $name;
    }

    /**
    * Set Type of the UserGroup
    *
    * @param string $type from a typology of terms for types of UserGroup
    * @todo add code to validate against enum
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set Given name of the UserGroup
    *
    * @param string $name represents a long human readable label for this UserGroup
    */
    public function setGivenName($name) {
      if($this->_given_name != $name) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_given_name",$name);
      }
      $this->_given_name = $name;
    }

    /**
    * Set Family name of the UserGroup
    *
    * @param string $name represents a long human readable label for this UserGroup
    */
    public function setFamilyName($name) {
      if($this->_family_name != $name) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_family_name",$name);
      }
      $this->_family_name = $name;
    }

    /**
    * Sets the Description for this UserGroup
    * @param string $desc describing the UserGroup
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_description",$desc);
      }
      $this->_description = $desc;
    }

   /**
    * Sets the preferences for this UserGroup
    * @param array of key id(s) pairs $prefs for this user
    */
    public function setPreferences($prefs) {
      foreach ($prefs as $prop => $val) {
        $this->storeScratchProperty($prop,$val);
      }
    }

   /**
    * Sets the default Attribution ID for this UserGroup
    * @param int foreign key to attribution that represents work inprogress by this user
    */
    public function setDefaultAttributionID($attID) {
      $this->storeScratchProperty('defAttID',$attID);
    }

    /**
    * Set Unique IDs of UserGroup entities which make up the members of this UserGroup
    *
    * @param int array $member_ids of UserGroup entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setMemberIDs($member_ids) {
      if($this->_member_ids != $member_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_member_ids",$this->idsToString($member_ids));
      }
       $this->_member_ids = $member_ids;
    }

    /**
    * Set Unique IDs of UserGroup entities which make up the admin of this UserGroup
    *
    * @param int array $admin_ids of UserGroup entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setAdminIDs($admin_ids) {
      if($this->_admin_ids != $admin_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ugr_admin_ids",$this->idsToString($admin_ids));
      }
       $this->_admin_ids = $admin_ids;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
