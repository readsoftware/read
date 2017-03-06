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
  * Classes to deal with AttributionGroup entities
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
  require_once (dirname(__FILE__) . '/AttributionGroups.php');

//*******************************************************************
//****************   ATTRIBUTION CLASS    **********************************
//*******************************************************************
  /**
  * AttributionGroup represents AttributionGroup entity member group for attribution
  *
  * <code>
  * require_once 'AttributionGroup.php';
  *
  * $AttributionGroup = new AttributionGroup( $resultRow );
  * echo $AttributionGroup->getName();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class AttributionGroup extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_name,
              $_type_id,
              $_realname,
              $_description,
              $_date_created,
              $_member_ids=array(),
              $_members,
              $_admin_ids=array(),
              $_admins;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an AttributionGroup instance from an AttributionGroup table row
    * @param array $row associated with columns of the AttributionGroup table, a valid atg_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'attributiongroup';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM attributiongroup WHERE atg_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= atg_owner_id or ".getUserID()." = ANY (\"atg_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('atg_id',$row)) {
          error_log("unable to query for AttributionGroup ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['atg_id'] ? $arg['atg_id']:NULL;
        $this->_name=@$arg['atg_name'] ? $arg['atg_name']:NULL;
        $this->_type_id=@$arg['atg_type_id'] ? $arg['atg_type_id']:NULL;
        $this->_realname=@$arg['atg_realname'] ? $arg['atg_realname']:NULL;
        $this->_description=@$arg['atg_description'] ? $arg['atg_description']:NULL;
        $this->_date_created=@$arg['atg_date_created'] ? $arg['atg_date_created']:NULL;
        $this->_member_ids=@$arg['atg_member_ids'] ? $arg['atg_member_ids']:NULL;
        $this->_admin_ids=@$arg['atg_admin_ids'] ? $arg['atg_admin_ids']:NULL;
        if (!array_key_exists('atg_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('atg_member_ids',$arg))$arg['atg_member_ids'] = $this->idsToString($arg['atg_member_ids']);
          if (array_key_exists('atg_admin_ids',$arg))$arg['atg_admin_ids'] = $this->idsToString($arg['atg_admin_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new AttributionGroup to be initialized through setters
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
      return "atg";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_name)) {
        $this->_data['atg_name'] = $this->_name;
      }
      if ($this->_type) {
        $this->_data['atg_type_id'] = $this->_type;
      }
      if (count($this->_realname)) {
        $this->_data['atg_realname'] = $this->_realname;
      }
      if (count($this->_description)) {
        $this->_data['atg_description'] = $this->_description;
      }
      if ($this->_date_created) {
        $this->_data['atg_date_created'] = $this->_date_created;
      }
      if (count($this->_member_ids)) {
        $this->_data['atg_member_ids'] = $this->idsToString($this->_member_ids);
      }
      if (count($this->_admin_ids)) {
        $this->_data['atg_admin_ids'] = $this->idsToString($this->_admin_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********
    /**
    * Get Name of the AttributionGroup
    *
    * @return string represents a short human readable label for this AttributionGroup
    */
    public function getName() {
      return $this->_name;
    }

    /**
    * Get Type of the AttributionGroup
    *
    * @return string from a typology of terms for types of AttributionGroup
    */
    public function getTypeID() {
      return $this->_type_id;
    }

     /**
    * Get Realname of the AttributionGroup
    *
    * @return string represents a long human readable label for this AttributionGroup
    */
    public function getRealname() {
      return $this->_realname;
    }

   /**
    * Gets the Description for this AttributionGroup
    * @return string for the Description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Gets the Creation Date for this AttributionGroup
    * @return string for the Creation Date
    */
    public function getCreationDate() {
      return $this->_date_created;
    }

    /**
    * Get Unique IDs of attribution group entities which make up this AttributionGroup
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
    * Get AttributionGroup's Member Attribution Group objects
    *
    * @return Editions for this AttributionGroup or NULL
    */
    public function getMembers($autoExpand = false) {
      if (!$this->_members && $autoExpand && count($this->getMemberIDs())>0) {
        $this->_members = new AttributionGroups("atg_id in (".join(",",$this->getMemberIDs()).")");
        $this->_members->setAutoAdvance(false);
      }
      return $this->_members;
    }

    /**
    * Get Unique IDs of attribution group entities which make up the admins for this AttributionGroup
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
    * Get AttributionGroup's Admin Attribution Group objects
    *
    * @return Admins for this AttributionGroup or NULL
    */
    public function getAdmins($autoExpand = false) {
      if (!$this->_admins && $autoExpand && count($this->getAdminIDs())>0) {
        $this->_admins = new AttributionGroups("atg_id in (".join(",",$this->getAdminIDs()).")");
        $this->_admins->setAutoAdvance(false);
      }
      return $this->_admins;
    }


    //********SETTERS*********

    /**
    * Set Name of the AttributionGroup
    *
    * @param string $name represents a short human readable label for this AttributionGroup
    */
    public function setName($name) {
      if($this->_name != $name) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_name",$name);
      }
      $this->_name = $name;
    }

    /**
    * Set Type of the AttributionGroup
    *
    * @param string $type from a typology of terms for types of AttributionGroup
    * @todo add code to validate against enum
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set Realname of the AttributionGroup
    *
    * @param string $name represents a long human readable label for this AttributionGroup
    */
    public function setRealname($name) {
      if($this->_realname != $name) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_realname",$name);
      }
      $this->_realname = $name;
    }

    /**
    * Sets the Description for this AttributionGroup
    * @param string $desc describing the AttributionGroup
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Sets the date create for this AttributionGroup
    * @param string $date describing the AttributionGroup
    */
    public function setCreationDate($date) {
      if($this->_date_created != $date) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_date_created",$date);
      }
      $this->_date_created = $date;
    }

    /**
    * Set Unique IDs of AttributionGroup entities which make up the members of this AttributionGroup
    *
    * @param int array $member_ids of AttributionGroup entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setMemberIDs($member_ids) {
      if($this->_member_ids != $member_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_member_ids",$this->idsToString($member_ids));
      }
       $this->_member_ids = $member_ids;
    }

    /**
    * Set Unique IDs of AttributionGroup entities which make up the admin of this AttributionGroup
    *
    * @param int array $admin_ids of AttributionGroup entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setAdminIDs($admin_ids) {
      if($this->_admin_ids != $admin_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atg_admin_ids",$this->idsToString($admin_ids));
      }
       $this->_admin_ids = $admin_ids;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
