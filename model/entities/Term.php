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
  * Classes to deal with Term entities
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
  require_once (dirname(__FILE__) . '/Terms.php');

  //*******************************************************************
  //****************   TERM CLASS  *************************
  //*******************************************************************
  /**
  * Term represents a concept or constrained list of concepts
  *
  * <code>
  * require_once 'Term.php';
  *
  * $term = new Term( $resultRow );
  * echo "term labeled with ".$term->getLabels(true);
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Term extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_labels,
              $_code,
              $_parent_id,
              $_parent,
              $_type_id,
              $_type,
              $_list_ids=array(),
              $_list,
              $_description,
              $_url;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an term instance from a term table row or a token table row
    * @param array $row associated with columns of the term table, a valid trm_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null) {
      $this->_table_name = 'term';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM term WHERE trm_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= trm_owner_id or ".getUserID()." = ANY (\"trm_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('trm_id',$row)) {
          error_log("unable to query for term ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['trm_id'] ? $arg['trm_id']:NULL;
        $this->_labels=@$arg['trm_labels'] ? $arg['trm_labels']:NULL;
        $this->_code=@$arg['trm_code'] ? $arg['trm_code']:NULL;
        $this->_parent_id=@$arg['trm_parent_id'] ? $arg['trm_parent_id']:NULL;
        $this->_type_id=@$arg['trm_type_id'] ? $arg['trm_type_id']:NULL;
        $this->_list_ids=@$arg['trm_list_ids'] ? $arg['trm_list_ids']:NULL;
        $this->_description=@$arg['trm_description'] ? $arg['trm_description']:NULL;
        $this->_url=@$arg['trm_url'] ? $arg['trm_url']:NULL;
        if (!array_key_exists('trm_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('trm_labels',$arg))$arg['trm_labels'] = $this->kvPairsToString($arg['trm_labels']);
          if (array_key_exists('trm_list_ids',$arg))$arg['trm_list_ids'] = $this->idsToString($arg['trm_list_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new term to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "trm";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_labels)) {
        $this->_data['trm_labels'] = $this->_labels;
      }
      if ($this->_code) {
        $this->_data['trm_code'] = $this->_code;
      }
      if ($this->_parent_id) {
        $this->_data['trm_parent_id'] = $this->_parent_id;
      }
      if ($this->_type_id) {
        $this->_data['trm_type_id'] = $this->_type_id;
      }
      if (count($this->_list_ids)) {
        $this->_data['trm_list_ids'] = $this->idsToString($this->_list_ids);
      }
      if (count($this->_description)) {
        $this->_data['trm_description'] = $this->_description;
      }
      if (count($this->_url)) {
        $this->_data['trm_url'] = $this->_url;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Term's label
    *
    * @param string $lang determines which language label is returned (default = "en")
    * @return string label in the language indicated by $lang for this term
    * @todo create global define loaded with prefernce of language for default
    */
    public function getLabel($lang = "en") {
      if (is_string($this->_labels)) {
        $labels = $this->kvPairsStringToArray($this->_labels);
      }else{
        $labels = $this->_labels;
      }
      if (is_array($labels)) {
        if (array_key_exists($lang,$labels)){
          return $labels[$lang];
        }else{
          return $labels["en"];
        }
      } else {
        return $labels["en"];
      }
    }

    /**
    * Get Term's labels
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string that contains a list of language code : label keyvalue pairs for this term
    */
    public function getLabels($asString = false) {
      if ($asString){
        return $this->kvPairsToString($this->_labels);
      }else{
        return $this->kvPairsStringToArray($this->_labels);
      }
    }

    /**
    * Get Term's list of term IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string that contains a list of term ids for this term
    */
    public function getListIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_list_ids);
      }else{
        return $this->idsStringToArray($this->_list_ids);
      }
    }

    /**
    * Get Term's list
    *
    * @return Term iterator for the list of terms for this term or NULL
    */
    public function getList($autoExpand = false) {
      if (!$this->_list && $autoExpand && count($this->getListIDs())>0) {
//        $this->_list = new Terms();
//        $this->_list->loadEntities($this->getListIDs());
        if (strpos($this->idsToString($this->_list_ids),"-") !== false){ //found temp id
          $this->_list = null;
        }else{
          $this->_list = new Terms("trm_id in (".join(",",$this->getListIDs()).")",null,null,null);
          $this->_list->setAutoAdvance(false);
        }
      }
      return $this->_list;
    }

    /**
    * Get Code of the term
    *
    * @return string representing the code for this term
    */
    public function getCode() {
      return $this->_code;
    }

    /**
    * Get Parent Term ID of the term
    *
    * @return int indentifying the parent term for this term
    * @todo write lookup code to get label for term id
    */
    public function getParentID() {
      return $this->_parent_id;
    }

    /**
    * Get Parent Term of the term
    *
    * @return Term which is parent to this term
    */
    public function getParent($autoExpand = false) {
      if (!$this->_parent && $autoExpand && is_numeric($this->_parent_id)) {
        $this->_parent = new Term(intval($this->_parent_id));
      }
      return $this->_parent;
    }

    /**
    * Get TypeID of the term
    *
    * @return int indentifying the term from typology of terms for type for term
    * @todo write lookup code to get label for term id
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the term
    *
    * @param string $lang identifying which language to return
    * @return string from terms identifying the type for this term
    * @todo write lookup code to get label for term id
    */
    public function getType($lang = "default") {
      return null;
    }

    /**
    * Gets the Description for this term
    * @return string for the Description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Gets the url of this term
    * @return int url of term
    */
    public function getURL() {
      return $this->_url;
    }


    //********SETTERS*********

    /**
    * Set Term's labels
    *
    * @param string $labels of this term
    */
    public function setLabels($labels) {
      if($this->_labels != $labels) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("trm_labels",$this->kvPairsToString($labels));
      }
      $this->_labels = $labels;
    }

    /**
    * Set Parent Term ID of the term
    *
    * @param int $parentID identifying the parent term of this terms
    * @todo add code to lookup id for term also check if type is numeric
    */
    public function setParentID($parentID) {
      if($this->_parent_id != $parentID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("trm_parent_id",$parentID);
      }
      $this->_parent_id = $parentID;
    }

    /**
    * Set TypeID of the term
    *
    * @param string  $typeID from a typology of terms for Type for term
    * @todo add code to lookup id for term also check if type is numeric
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("trm_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Set Unique IDs of term entities which define this list of this term
    *
    * @param int array $listIDs of term entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setListIDs($listIDs) {
      if($this->_list_ids != $listIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("trm_list_ids",$this->idsToString($listIDs));
      }
       $this->_list_ids = $listIDs;
    }

    /**
    * Sets the Description for this term
    * @param string $desc describing the term
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("trm_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set Term's url
    *
    * @param string $url of this term
    */
    public function setURL($url) {
      if($this->_url != $url) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("trm_url",$url);
      }
      $this->_url = $url;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
