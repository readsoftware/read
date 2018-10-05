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
  * Classes to deal with Sequence entities
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
  require_once (dirname(__FILE__) . '/OrderedSet.php');
//  require_once (dirname(__FILE__) . '/theme.php');

  //*******************************************************************
  //****************   SEQUENCE CLASS  *************************
  //*******************************************************************
  /**
  * Sequence represents sequence entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Sequence.php';
  *
  * $sequence = new Sequence( $resultRow );
  * echo "sequence labeled with ".$sequence->getLabel();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Sequence extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_label,
              $_ord,
              $_entity_ids=array(),
              $_entities,
              $_type_id,
              $_superscript,
              $_theme_id;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an sequence instance from a sequence table row or a token table row
    * @param array $row associated with columns of the sequence table, a valid seq_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null) {
      $this->_table_name = 'sequence';
      if (is_numeric($arg) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM sequence WHERE seq_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= seq_owner_id or ".getUserID()." = ANY (\"seq_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('seq_id',$row)) {
          error_log("unable to query for sequence ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['seq_id'] ? $arg['seq_id']:NULL;
        $this->_label=@$arg['seq_label']|| @$arg['seq_label'] == 0 ? $arg['seq_label']:NULL;
        $this->_type_id=@$arg['seq_ord'] ? $arg['seq_ord']:NULL;
        $this->_entity_ids=@$arg['seq_entity_ids'] ? $arg['seq_entity_ids']:NULL;
        $this->_type_id=@$arg['seq_type_id'] ? $arg['seq_type_id']:NULL;
        $this->_superscript=@$arg['seq_superscript'] ? $arg['seq_superscript']:NULL;
        $this->_theme_id=@$arg['seq_theme_id'] ? $arg['seq_theme_id']:NULL;
        if (!array_key_exists('seq_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('seq_entity_ids',$arg))$arg['seq_entity_ids'] = $this->arraysOfIdsToString($arg['seq_entity_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new sequence to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "seq";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_label)) {
        $this->_data['seq_label'] = $this->_label;
      }
      if ($this->_ord) {
        $this->_data['seq_ord'] = $this->_ord;
      }
      if (count($this->_entity_ids)) {
        $this->_data['seq_entity_ids'] = $this->arraysOfIdsToString($this->_entity_ids);
      }
      if ($this->_type_id) {
        $this->_data['seq_type_id'] = $this->_type_id;
      }
      if ($this->_superscript) {
        $this->_data['seq_superscript'] = $this->_superscript;
      }
      if ($this->_theme_id) {
        $this->_data['seq_theme_id'] = $this->_theme_id;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Sequence's label
    *
    * @return string label of this sequence
    */
    public function getLabel() {
      return $this->_label;
    }

    /**
    * Get ordinal position of the sequence
    *
    * @return int indentifying the ordinal position of the sequence within containing context
    */
    public function getOrdinal() {
      return $this->_ord;
    }

    /**
    * Get Sequence's entity unique global IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string that contains a list of entity typPrefix:IDs for this sequence
    */
    public function getEntityIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_entity_ids);
      }else{
        return $this->stringOfStringsToArray($this->_entity_ids);
      }
    }

    /**
    * Get Sequence's entity objects
    *
    * @return OrderedSet iterator for the entities of this sequence or NULL
    */
    public function getEntities($autoExpand = false) {
      if (!$this->_entities && $autoExpand && count($this->getEntityIDs())>0) {
        $this->_entities = new OrderedSet();
        $this->_entities->loadEntities($this->getEntityIDs());
      }
      return $this->_entities;
    }

    /**
    * Get TypeID of the sequence
    *
    * @return int indentifying the term from typology of terms for types for sequences
    * @todo write lookup code to get label for term id
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the sequence
    *
    * @param string $lang identifying which language to return
    * @return string from terms identifying the type for this sequence
    * @todo expand lookup code to get label for term id with lang
    */
    public function getType($lang = "default") {
      return Entity::getTermFromID($this->_type_id);
    }

    /**
    * Gets the SuperScript of this Sequence
    * @return text SuperScript of Sequence
    */
    public function getSuperScript() {
      return $this->_superscript;
    }

    /**
    * Get Sequence's Theme's unique ID
    *
    * @return int returns the primary Key for the theme of this sequence
    */
    public function getThemeID() {
      return $this->_theme_id;
    }

    //********SETTERS*********

    /**
    * Set Sequence's label
    *
    * @param string $label of this sequence
    */
    public function setLabel($label) {
      if($this->_label != $label) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seq_label",$label);
      }
      $this->_label = $label;
    }

    /**
    * Get ordinal position of the sequence
    *
    * @param int $ord indentifying the ordinal position of the sequence within containing context
    */
    public function setOrdinal($ord) {
      if($this->_ord != $ord) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seq_ord",$ord);
      }
      $this->_ord = $ord;
    }

    /**
    * Set Sequence's entity unique globalIDs
    *
    * @param string array that contains entity globalIDs in this sequence
    */
    public function setEntityIDs($ids) {
      if($this->_entity_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seq_entity_ids",$this->stringsToString($ids));
      }
      $this->_entity_ids = $ids;
    }

    /**
    * Set TypeID of the sequence
    *
    * @param int  $typeID from a typology of terms for Type for sequences
    * @todo add code to lookup id for term also check if type is numeric
    */
    public function setTypeID($typeID) {
      //todo validate type is valid Sequence type
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seq_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Set the SuperScript of this Sequence
    *
    * @param string $ssText to identify Sequence
    */
    public function setSuperScript($ssText) {
      if($this->_superscript != $ssText) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seq_superscript",$ssText);
      }
      $this->_superscript = $ssText;
    }

    /**
    * Set Sequence's Theme unique ID
    *
    * @param int $id the primary Key for the theme of this sequence
    */
    public function setThemeID($id) {
      if($this->_theme_id != $id) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seq_theme_id",$id);
      }
      $this->_theme_id = $id;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
