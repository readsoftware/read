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
  * Classes to deal with Link entities
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
//****************   LINK CLASS  *********************************
//*******************************************************************
  /**
  * Link represents link entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Link.php';
  *
  * $link = new Link( $resultRow );
  * echo "link probable begin is".$link->getProbBeginLink();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Link extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_to_entity_ids,
              $_from_entity_ids,
              $_type_id;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an link instance from an link table row
    * @param array $row associated with columns of the link table, a valid lnk_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'link';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM link WHERE lnk_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= lnk_owner_id or ".getUserID()." = ANY (\"lnk_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('lnk_id',$row)) {
          error_log("unable to query for link ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['lnk_id'] ? $arg['lnk_id']:NULL;
        $this->_to_entity_ids=@$arg['lnk_to_entity_ids'] ? $arg['lnk_to_entity_ids']:NULL;
        $this->_from_entity_ids=@$arg['lnk_from_entity_ids'] ? $arg['lnk_from_entity_ids']:NULL;
        $this->_type_id=@$arg['lnk_type_id'] ? $arg['lnk_type_id']:NULL;
        if (!array_key_exists('lnk_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('lnk_to_entity_ids',$arg))$arg['lnk_to_entity_ids'] = $this->stringsToString($arg['lnk_to_entity_ids']);
          if (array_key_exists('lnk_from_entity_ids',$arg))$arg['lnk_from_entity_ids'] = $this->stringsToString($arg['lnk_from_entity_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new link to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "lnk";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_to_entity_ids)) {
        $this->_data['lnk_to_entity_ids'] = $this->stringsToString($this->_to_entity_ids);
      }
      if (count($this->_from_entity_ids)) {
        $this->_data['lnk_from_entity_ids'] = $this->stringsToString($this->_from_entity_ids);
      }
      if ($this->_type_id) {
        $this->_data['lnk_type_id'] = $this->_type_id;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Link's linked to entities global ID strings
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return array of global ID strings for "linked to" entities
    */
    public function getLinkedToIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_to_entity_ids);
      }else{
        return $this->stringOfStringsToArray($this->_to_entity_ids);
      }
    }

    /**
    * Get Link's linked from entities global ID strings
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return array of global ID strings for "linked from" entities
    */
    public function getLinkedFromIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_from_entity_ids);
      }else{
        return $this->stringOfStringsToArray($this->_from_entity_ids);
      }
    }

    /**
    * Get Link's type id
    *
    * @return int term id from a link typology for this Link
    */
    public function getTypeID() {
      return $this->_type_id;
    }


    //********SETTERS*********


    /**
    * Set Link's linked to entities global ID strings
    *
    * @param array of global ID strings for "linked to" entities
    */
    public function setLinkedToIDs($gIDs) {
      if($this->_to_entity_ids!= $gIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lnk_to_entity_ids",$this->stringsToString($gIDs));
      }
      $this->_to_entity_ids = $gIDs;
    }

    /**
    * Set Link's linked from entities global ID strings
    *
    * @param array of global ID strings for "linked from" entities
    */
    public function setLinkedFromIDs($gIDs) {
      if($this->_from_entity_ids!= $gIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lnk_from_entity_ids",$this->stringsToString($gIDs));
      }
      $this->_from_entity_ids = $gIDs;
    }

    /**
    * Set Link's type term ID
    *
    * @param int term $typeID of this Link
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lnk_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
