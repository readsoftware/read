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
  * Classes to deal with Date entities
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
//****************   DATE CLASS  *********************************
//*******************************************************************
  /**
  * Date represents date entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Date.php';
  *
  * $date = new Date( $resultRow );
  * echo "date probable begin is".$date->getProbBeginDate();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Date extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_prob_begin_date,
              $_prob_end_date,
              $_evidences,
              $_era_ids,
              $_preferred_era_id,
              $_entity_id;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an date instance from an date table row
    * @param array $row associated with columns of the date table, a valid dat_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'date';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM date WHERE dat_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= dat_owner_id or ".getUserID()." = ANY (\"dat_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('dat_id',$row)) {
          error_log("unable to query for date ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['dat_id'] ? $arg['dat_id']:NULL;
        $this->_prob_begin_date=@$arg['dat_prob_begin_date'] ? $arg['dat_prob_begin_date']:NULL;
        $this->_prob_end_date=@$arg['dat_prob_end_date'] ? $arg['dat_prob_end_date']:NULL;
        $this->_evidences=@$arg['dat_evidences'] ? $arg['dat_evidences']:NULL;
        $this->_era_ids=@$arg['dat_era_ids'] ? $arg['dat_era_ids']:NULL;
        $this->_preferred_era_id=@$arg['dat_preferred_era_id'] ? $arg['dat_preferred_era_id']:NULL;
        $this->_entity_id=@$arg['dat_entity_id'] ? $arg['dat_entity_id']:NULL;
        if (!array_key_exists('dat_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('dat_evidences',$arg))$arg['dat_evidences'] = $this->stringsToString($arg['dat_evidences']);
          if (array_key_exists('dat_era_ids',$arg))$arg['dat_era_ids'] = $this->idsToString($arg['dat_era_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new date to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "dat";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_prob_begin_date) {
        $this->_data['dat_prob_begin_date'] = $this->_prob_begin_date;
      }
      if ($this->_prob_end_date) {
        $this->_data['dat_prob_end_date'] = $this->_prob_end_date;
      }
      if (count($this->_evidences)) {
        $this->_data['dat_evidences'] = $this->stringsToString($this->_evidences);
      }
      if (count($this->_era_ids)) {
        $this->_data['dat_era_ids'] = $this->idsToString($this->_era_ids);
      }
      if ($this->_preferred_era_id) {
        $this->_data['dat_preferred_era_id'] = $this->_preferred_era_id;
      }
      if ($this->_entity_id) {
        $this->_data['dat_entity_id'] = $this->_entity_id;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Date's probable begin date
    *
    * @return int date for the probable beginning of this Date
    */
    public function getProbBeginDate() {
      return $this->_prob_begin_date;
    }

    /**
    * Get Date's probable ending date
    *
    * @return int date for the probable ending of this Date
    */
    public function getProbEndDate() {
      return $this->_prob_end_date;
    }

    /**
    * Get Date's evidence strings
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return array of evidence strings of this Date termID:value string pairs
    */
    public function getEvidences($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_evidences);
      }else{
        return $this->stringOfStringsToArray($this->_evidences);
      }
    }

    /**
    * Get Date's era ids
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array era ids of this Date
    */
    public function getEraIds($asString = false) {
      if ($asString){
        return $this->idsToString($this->_era_ids);
      }else{
        return $this->idsStringToArray($this->_era_ids);
      }
    }

    /**
    * Get Date's preferred era id
    *
    * @return int preffered era id of this Date
    */
    public function getPreferredEraID() {
      return $this->_preferred_era_id;
    }

    /**
    * Get Date's entity global id
    *
    * @return string global id for entity  of this Date
    */
    public function getEntityID() {
      return $this->_entity_id;
    }


    //********SETTERS*********

    /**
    * Set Date's probable begin date
    *
    * @param int $date for the probable beginning of this Date
    */
    public function setProbBeginDate($date) {
      if($this->_prob_begin_date != $date) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("dat_prob_begin_date",$date);
      }
      $this->_prob_begin_date = $date;
    }

    /**
    * Set Date's probable ending date
    *
    * @param int $date for the probable ending of this Date
    */
    public function setProbEndDate($date) {
      if($this->_prob_end_date != $date) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("dat_prob_end_date",$date);
      }
      $this->_prob_end_date = $date;
    }

    /**
    * Set Date's evidence strings
    *
    * @param array of $evidences strings of this Date termID:value string pairs
    */
    public function setEvidences($evidences) {
      if($this->_evidences!= $evidences) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("dat_evidences",$this->stringsToString($evidences));
      }
      $this->_evidences = $evidences;
    }

    /**
    * Set Unique IDs of eras of this date
    *
    * @param int array $eraIDs of term IDs for the methods of determination
    */
    public function setEraIDs($eraIDs) {
      if($this->_era_ids != $eraIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("dat_era_ids",$this->idsToString($eraIDs));
      }
       $this->_era_ids = $eraIDs;
    }

    /**
    * Set Date's preferred era ID
    *
    * @param int preferred $eraID of this Date
    */
    public function setPreferredEraID($eraID) {
      if($this->_preferred_era_id != $eraID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("dat_preferred_era_id",$eraID);
      }
      $this->_preferred_era_id = $eraID;
    }

    /**
    * Set Date's entity Global ID
    *
    * @param string $entityGID of this Date
    */
    public function setEntityGID($entityGID) {
      if($this->_entity_id != $entityGID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("dat_entity_id",$entityGID);
      }
      $this->_entity_id = $entityGID;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
