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
  * Classes to deal with Attribution entities
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
//****************   ATTRIBUTION CLASS    **********************************
//*******************************************************************
  /**
  * Attribution represents attribution entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Attribution.php';
  *
  * $attribution = new Attribution( $resultRow );
  * echo $attribution->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Attribution extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_title,
              $_description,
              $_bibliography_id,
              $_group_id,
              $_types,
              $_detail;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an attribution instance from an attribution table row
    * @param array $row associated with columns of the attribution table, a valid atb_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'attribution';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM attribution WHERE atb_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= atb_owner_id or ".getUserID()." = ANY (\"atb_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('atb_id',$row)) {
          error_log("unable to query for attribution ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['atb_id'] ? $arg['atb_id']:NULL;
        $this->_title=@$arg['atb_title'] ? $arg['atb_title']:NULL;
        $this->_description=@$arg['atb_description'] ? $arg['atb_description']:NULL;
        $this->_bibliography_id=@$arg['atb_bib_id'] ? $arg['atb_bib_id']:NULL;
        $this->_group_id=@$arg['atb_group_id'] ? $arg['atb_group_id']:NULL;
        $this->_types=@$arg['atb_types'] ? $arg['atb_types']:NULL;
        $this->_detail=@$arg['atb_detail'] ? $arg['atb_detail']:NULL;
        if (!array_key_exists('atb_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('atb_types',$arg))$arg['atb_types'] = $this->enumsToString($arg['atb_types']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new attribution to be initialized through setters
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "atb";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_title)) {
        $this->_data['atb_title'] = $this->_title;
      }
      if (count($this->_description)) {
        $this->_data['atb_description'] = $this->_description;
      }
      if ($this->_bibliography_id) {
        $this->_data['atb_bib_id'] = $this->_bibliography_id;
      }
      if ($this->_group_id) {
        $this->_data['atb_group_id'] = $this->_group_id;
      }
      if (count($this->_types)) {
        $this->_data['atb_types'] = $this->idsToString($this->_types);
      }
      if (count($this->_detail)) {
        $this->_data['atb_detail'] = $this->_detail;
      }
    }

    //********GETTERS*********
    /**
    * Gets the title for this Attribution
    * @return string $title
    */
    public function getTitle() {
      return $this->_title;
    }

    /**
    * Get Description for this attibution
    *
    * @return string Description for this attibution
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get Bibliography ID for this attibution
    *
    * @return int ID of the Bibliography entity for this attibution
    */
    public function getBibliographyID() {
      return $this->_bibliography_id;
    }

    /**
    * Get Group ID for this attibution
    *
    * @return int ID of the AttributionGroup entity for this attibution
    */
    public function getGroupID() {
      return $this->_group_id;
    }

    /**
    * Get Types of the attribution
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string from a typology of terms for types of attribution
    */
    public function getTypes($asString = false) {
      if ($asString){
        return $this->enumsToString($this->_types);
      }else{
        return $this->enumStringToArray($this->_types);
      }
    }

    /**
    * Get Detail for this attibution
    *
    * @return string Detail for this attibution
    */
    public function getDetail() {
      return $this->_detail;
    }

    //********SETTERS*********

    /**
    * Sets the title for this Attribution
    * @param string $title
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atb_title",$title);
      }
      $this->_title = $title;
    }

    /**
    * Set Description for this attibution
    *
    * @param string $desc Description for this attibution
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atb_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set Bibliography ID for this attibution
    *
    * @param int $bibID ID of the Bibliography entity for this attibution
    */
    public function setBibliographyID($bibID) {
      if($this->_bibliography_id != $bibID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atb_bib_id",$bibID);
      }
      $this->_bibliography_id = $bibID;
    }

    /**
    * Set Group ID for this attibution
    *
    * @param int ID $grpID of the AttributionGroup entity for this attibution
    */
    public function setGroupID($grpID) {
      if($this->_group_id != $grpID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atb_group_id",$grpID);
      }
      $this->_group_id = $grpID;
    }

    /**
    * Set Types of the attribution
    *
    * @param string array|string $types from a typology of terms for types of attribution
    */
    public function setTypes($types) {
      if($this->_types != $types) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atb_types",$this->enumsToString($types));
      }
      $this->_types = $types;
    }

    /**
    * Set Detail for this attibution
    *
    * @param string $detail for this attibution
    */
    public function setDetail($detail) {
      if($this->_detail != $detail) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("atb_detail",$detail);
      }
      $this->_detail = $detail;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
