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
  * Classes to deal with Edition entities
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
  require_once (dirname(__FILE__) . '/Sequences.php');
  require_once (dirname(__FILE__) . '/Catalogs.php');
  require_once (dirname(__FILE__) . '/Text.php');

//*******************************************************************
//****************   EDITION CLASS    **********************************
//*******************************************************************
  /**
  * Edition represents edition entity which is collection of sequences, textmetadata and documentation
  *
  * <code>
  * require_once 'Edition.php';
  *
  * $edition = new Edition( $resultRow );
  * echo $edition->getDescription();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Edition extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_description,
              $_sequence_ids=array(),
              $_sequences,
              $_type_id,
              $_text_id,
              $_text;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an edition instance from an edition table row
    * @param array $row associated with columns of the edition table, a valid edn_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'edition';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM edition WHERE edn_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= edn_owner_id or ".getUserID()." = ANY (\"edn_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('edn_id',$row)) {
          error_log("unable to query for edition ID = $arg ".$dbMgr->getQuery());
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['edn_id'] ? $arg['edn_id']:NULL;
        $this->_description=@$arg['edn_description'] ? $arg['edn_description']:NULL;
        $this->_sequence_ids=@$arg['edn_sequence_ids'] ? $arg['edn_sequence_ids']:NULL;
        $this->_type_id=@$arg['edn_type_id'] ? $arg['edn_type_id']:NULL;
        $this->_text_id=@$arg['edn_text_id'] ? $arg['edn_text_id']:NULL;
        if (!array_key_exists('edn_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('edn_sequence_ids',$arg))$arg['edn_sequence_ids'] = $this->idsToString($arg['edn_sequence_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new edition to be initialized through setters
    }

    /**
    * Save Edition
    *
    * override entity save to set status to changed before saving
    *
    * @return boolean indicating success or failure
    */
    public function save($dbMgr = null) {
      if ($this->_dirty && count($this->_data)) {
        $status = $this->getStatus();
        if (!$status) {
          $this->setStatus("changed");
        }
      }
      return parent::save($dbMgr);
    }

    /**
    * Set status
    *
    * set the status of this object where 'editing' and 'changed'
    * have a reserved semantic
    *
    * @param string $status indicating current status
    */
    public function setStatus($status = null) {
      $this->storeScratchProperty("status",$status);
    }

    /**
    * Get status
    *
    * get the status of this object
    *
    * @return string $status indicating current status
    */
    public function getStatus() {
      return $this->getScratchProperty("status");
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
      return "edn";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_description)) {
        $this->_data['edn_description'] = $this->_description;
      }
      if (count($this->_sequence_ids)) {
        $this->_data['edn_sequence_ids'] = $this->idsToString($this->_sequence_ids);
      }
      if ($this->_type_id) {
        $this->_data['edn_type_id'] = $this->_type_id;
      }
      if ($this->_text_id) {
        $this->_data['edn_text_id'] = $this->_text_id;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Check type is Research
    *
    * @return boolean indentifying the term from typology of terms is research
    */
    public function isResearchEdition() {
      $type = Entity::getTermFromID($this->_type_id);
      return (($type && strtolower($type) == "research") || !$type);
    }

    //********GETTERS*********
    /**
    * Gets the Description for this Edition
    * @return string for the Description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get Unique IDs of sequence entities which define this edition and its views
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of sequence entity IDs
    */
    public function getSequenceIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_sequence_ids);
      }else{
        return $this->idsStringToArray($this->_sequence_ids);
      }
    }

    /**
    * Get Edition's Sequence objects
    *
    * @return Sequences for this edition or NULL
    */
    public function getSequences($autoExpand = false) {
      if (!$this->_sequences && $autoExpand && count($this->getSequenceIDs())>0) {
        $this->_sequences = new Sequences("seq_id in (".join(",",$this->getSequenceIDs()).")",null,null,null);
      }
      return $this->_sequences;
    }

    /**
    * Get TypeID of the edition
    *
    * @return int indentifying the term from typology of terms for types of editions
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Edition's Text's unique ID
    *
    * @return int returns the primary Key for the text of this edition
    */
    public function getTextID() {
      return $this->_text_id;
    }

    /**
    * Get Edition's TextMetadata object
    *
    * @return TextMetadata or NULL
    */
    public function getText($autoExpand = false) {
      if (!$this->_text && $autoExpand && is_numeric($this->_text_id)) {
        $this->_text = new Text(intval($this->_text_id));
      }
      return $this->_text;
    }

    /**
    * Gets the Catalog ID for this Edition
    * @return int ID of the default catalog or null
    */
    public function getCatalogID() {
      $catID = $this->getScratchProperty("defCatID");
      if (!$catID){//search for the first catalog for this edition
        $condition = "".$this->getID()." = ANY(cat_edition_ids)";
        $catalogs = new Catalogs($condition,"cat_id",null,1);
        if (!$catalogs->getError() && $catalogs->getCount() == 1) {
          $catID = $catalogs->current()->getID();
        }
      }
      return ($catID?$catID:null);
    }


    //********SETTERS*********

    /**
    * Sets the Default Catalog ID for this Edition
    * @param int $catID for the Edition
    */
    public function setDefaultCatalogID($catID) {
      $this->storeScratchProperty("defCatID",$catID);
    }

    /**
    * Sets the Description for this Edition
    * @param string $desc describing the Edition
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("edn_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set Unique IDs of sequence entities which define this edition and its views
    *
    * @param int array $sequence_ids of sequence entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setSequenceIDs($sequence_ids) {
      if($this->_sequence_ids != $sequence_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("edn_sequence_ids",$this->idsToString($sequence_ids));
      }
       $this->_sequence_ids = $sequence_ids;
    }

    /**
    * Set Type of the edition
    *
    * @param int $typeID from a typology of terms for types of Catalogs
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("edn_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Set Edition's Text's unique ID
    *
    * @param int $id the primary Key for the text of this edition
    */
    public function setTextID($id) {
      if($this->_text_id != $id) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("edn_text_id",$id);
      }
      $this->_text_id = $id;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
