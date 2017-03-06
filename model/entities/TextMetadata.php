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
  * Classes to deal with TextMetadata entities
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
  require_once (dirname(__FILE__) . '/Text.php');
  require_once (dirname(__FILE__) . '/Editions.php');
  require_once (dirname(__FILE__) . '/Attributions.php');

//*******************************************************************
//****************   TEXTMETADATA CLASS    **********************************
//*******************************************************************
  /**
  * TextMetadata represents textMetadata entity which is metadata about an artefact
  *
  * <code>
  * require_once 'TextMetadata.php';
  *
  * $textMetadata = new TextMetadata( $resultRow );
  * echo $textMetadata->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class TextMetadata extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_text_id,
              $_text,
              $_type_ids,
              $_reference_ids,
              $_references,
              $_editions;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create a textMetadata instance from an textMetadata table row
    * @param array $row associated with columns of the textMetadata table, a valid tmd_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'textmetadata';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM textmetadata WHERE tmd_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= tmd_owner_id or ".getUserID()." = ANY (\"tmd_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('tmd_id',$row)) {
          error_log("unable to query for textMetadata ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['tmd_id'] ? $arg['tmd_id']:NULL;
        $this->_text_id=@$arg['tmd_text_id'] ? $arg['tmd_text_id']:NULL;
        $this->_type_ids=@$arg['tmd_type_ids'] ? $arg['tmd_type_ids']:NULL;
        $this->_reference_ids=@$arg['tmd_reference_ids'] ? $arg['tmd_reference_ids']:NULL;
        if (!array_key_exists('tmd_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('tmd_type_ids',$arg))$arg['tmd_type_ids'] = $this->idsToString($arg['tmd_type_ids']);
          if (array_key_exists('tmd_edition_ref_ids',$arg))$arg['tmd_edition_ref_ids'] = $this->stringsToString($arg['tmd_edition_ref_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new textMetadata to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "tmd";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_text_id) {
        $this->_data['tmd_text_id'] = $this->_text_id;
      }
      if (count($this->_type_ids)) {
        $this->_data['tmd_type_ids'] = $this->idsToString($this->_type_ids);
      }
      if (count($this->_reference_ids)) {
        $this->_data['tmd_reference_ids'] = $this->idsToString($this->_reference_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********
    /**
    * Get TextMetadata's Text's unique ID
    *
    * @return int returns the primary Key for the text of this textMetadata
    */
    public function getTextID() {
      return $this->_text_id;
    }

    /**
    * Get TextMetadata's text object
    *
    * @return Text of this TextMetadata or NULL
    */
    public function getText($autoExpand = false) {
      if (!$this->_text && $autoExpand && is_numeric($this->_text_id)) {
        $this->_text = new Text(intval($this->_text_id));
      }
      return $this->_text;
    }

    /**
    * Get TextMetadata's Type term unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for term object IDs identifying the type for this textMetadata
    */
    public function getTypeIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_type_ids);
      }else{
        return $this->idsStringToArray($this->_type_ids);
      }
    }

    /**
    * Get TextMetadata's Reference attribution link IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of Reference attribution link IDs
    */
    public function getReferenceIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_reference_ids);
      }else{
        return $this->stringOfStringsToArray($this->_reference_ids);
      }
    }

    /**
    * Get TextMetadata's Reference attribution objects
    *
    * @return Attribution iterator for the reference attribution of this TextMetadata or NULL
    */
    public function getReferences($autoExpand = false) {
      if (!$this->_references && $autoExpand && count($this->getReferenceIDs())>0) {
        $this->_references = new Attributions("atb_id in (".join(",",$this->getReferenceIDs()).")",null,null,null);
        $this->_references->setAutoAdvance(false);
      }
      return $this->_references;
    }

    //********SETTERS*********

    /**
    * Sets the text id for this TextMetadata
    * @param int $textID
    * @todo check part ID is valid by query
    */
    public function setTextID($textID) {
      if($this->_text_id != $textID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tmd_text_id",$textID);
      }
      $this->_text_id = $textID;
    }

    /**
    * Set TextMetadata's Type term unique IDs
    *
    * @param int array $typeIDs of type term object IDs for this textMetadata
    */
    public function setTypeIDs($typeIDs) {
      if($this->_type_ids != $typeIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tmd_type_ids",$this->idsToString($typeIDs));
      }
      $this->_type_ids = $typeIDs;
    }

    /**
    * Set TextMetadata's Reference object IDs
    *
    * @param int $refIDs of the primary Key for the Reference of this textMetadata
    */
    public function setReferenceIDs($refIDs) {
      if($this->_reference_ids != $refIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tmd_reference_ids",$this->stringsToString($refIDs));
      }
      $this->_reference_ids = $refIDs;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
