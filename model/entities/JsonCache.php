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
  * Class to deal with JSONCACHE entries
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
//****************   JSONCACHE CLASS    **********************************
//*******************************************************************
  /**
  * Catalog represents jsonCache entity which represents a row in the jsoncache table of text editions
  *
  * <code>
  * require_once 'JsonCache.php';
  *
  * $cache = new JsonCache( $resultRow );
  * echo $cache->getLabel();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class JsonCache extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_label,
              $_type_id,
              $_json_string;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an catalog instance from an catalog table row
    * @param array $row associated with columns of the catalog table, a valid jsc_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'jsoncache';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM catalog WHERE jsc_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= jsc_owner_id or ".getUserID()." = ANY (\"jsc_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('jsc_id',$row)) {
          error_log("unable to query for jsoncache ID = $arg ");
        }else{
          $arg = $row;
        }
      } else if (is_string($arg) && strlen($arg) > 6) {// try the label
        $dbMgr = new DBManager();
        $dbMgr->query("SELECT * FROM jsoncache WHERE jsc_label = '$arg'".
                      (isSysAdmin()?"":
                        " AND (".getUserID()."= jsc_owner_id or ".getUserID()." = ANY (\"jsc_visibility_ids\"))")." LIMIT 1");
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('jsc_id',$row)) {
          error_log("unable to query for jsoncache label = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['jsc_id'] ? $arg['jsc_id']:NULL;
        $this->_label=@$arg['jsc_label'] ? $arg['jsc_label']:NULL;
        $this->_type_id=@$arg['jsc_type_id'] ? $arg['jsc_type_id']:NULL;
        $this->_json_string=@$arg['jsc_json_string'] ? $arg['jsc_json_string']:NULL;
        if (!array_key_exists('jsc_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
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
      return "jsc";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->jsc_label)) {
        $this->_data['jsc_label'] = $this->_label;
      }
      if ($this->_type_id) {
        $this->_data['jsc_type_id'] = $this->_type_id;
      }
      if (count($this->_json_string)) {
        $this->_data['jsc_json_string'] = $this->_json_string;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Save
    *
    * save JsonCache to database using id as idicator for insert or update
    *
    * @return boolean indicating success or failure
    */
    public function save($dbMgr = null) {
      if ($this->_dirty && count($this->_data)) {
        if (!@$dbMgr ) {
          $dbMgr = new DBManager();
        }
        if ($this->_id){ // update
          if (isset($this->_owner_id) && $this->_owner_id != getUserID() && !in_array($this->_owner_id, getUserMembership())//owner/admin not equal user current user id
              && !isSysAdmin()
              && !(count(array_keys($this->_data)) == 1 && array_key_exists('jsc_scratch',$this->_data))) {// allow scratch only updates by anyone
            array_push($this->_errors,"User with ID= ".getUserID()." attemping to edit unowned record ".$this->getGlobalPrefix()."_id=".$this->_id);
            return false;
          }
          $res = $dbMgr->update($this->_table_name,$this->_data,$this->getGlobalPrefix()."_id=".$this->_id);
          if (!$res){
            array_push($this->_errors,$dbMgr->getError());
            return false;
          }
        }else{ //insert
          if (!$this->_owner_id) {//owner not set so user current user id
            $this->setOwnerID(getUserID());
          } else if (!array_key_exists($this->getGlobalPrefix().'_owner_id',$this->_data)) {//handle clone to create new
            $this->_data[$this->getGlobalPrefix().'_owner_id'] = $this->_owner_id;
          }
          if (!$this->_visibility_ids) {//visibility not set so user current user id
            $this->setVisibilityIDs(array(getUserID()));
          }
          $res = $dbMgr->insert($this->_table_name,$this->_data,$this->getGlobalPrefix()."_id");
          if (!$res){
            array_push($this->_errors,$dbMgr->getError());
            return false;
          }
          $this->_id = $res;
        }
        $this->_dirty = false;
        $this->_data = array();
      }
      return true;
    }

    /**
    * Append date to Label to version this entry JsonCache entry
    *
    */
    public function versionEntry() {
      $this->_dirty = true;
      $label = $this->_label.time();
      $this->setDataKeyValuePair("jsc_label",$label);
      $this->_label = $label;
      $this->save();
    }

    /**
    * Check scratch for dirty flag
    *
    */
    public function isDirty() {
      $dBit = $this->getScratchProperty('dirtyBit');
      if ($dBit == 1) {
        return true;
      }
      return false;
    }

    /**
    * Set scratch dirty flag
    *
    */
    public function setDirtyBit() {
      $this->storeScratchProperty('dirtyBit',1);
    }

    /**
    * Clear scratch dirty flag
    *
    */
    public function clearDirtyBit() {
      $this->storeScratchProperty('dirtyBit',0);
    }

    //********GETTERS*********
    /**
    * Get Label of the JsonCache entry
    *
    * @return string represents a human readable label for this JsonCache entry
    */
    public function getLabel() {
      return $this->_label;
    }

    /**
    * Get TypeID of the JsonCache entry
    *
    * @return int indentifying the term from typology of terms for types of JsonCache entries
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the JsonCache entry
    *
    * @param string $lang identifying which language to return
    * @return string from terms identifying the type for this JsonCache entry
    */
    public function getType($lang = "en") {
      return Entity::getTermFromID($this->_type_id);
    }

    /**
    * Gets the JsonString for this JsonCache entry
    * @return string representing the cached value for this JsonCache entry
    */
    public function getJsonString() {
      return $this->_json_string;
    }

    /**
    * Get Entity's Annotations unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return null | 'empty string
    */
    public function getAnnotationIDs($asString = false) {
      if ($asString) {
        return '';
      } else {
        return null;
      }
    }

    /**
    * Get Entity's Attributions unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return null | 'empty string
    */
    public function getAttributionIDs($asString = false) {
      if ($asString) {
        return '';
      } else {
        return null;
      }
    }

    //********SETTERS*********

   /**
    * Set Label of the JsonCache entry
    *
    * @param string $label represents a more human readable label for this JsonCache entry
    */
    public function setLabel($label) {
      if($this->_label != $label) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("jsc_label",$label);
      }
      $this->_label = $label;
    }

    /**
    * Set Type ID of the JsonCache entry
    *
    * @param int $typeID from a typology of terms for types of JsonCache entries
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("jsc_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Sets the JsonString for this JsonCache entry
    * @param string $json
    */
    public function setJsonString($json) {
      if($this->_json_string != $json) {
        $this->_dirty = true;
        $this->clearDirtyBit();
        $this->setDataKeyValuePair("jsc_json_string",$json);
      }
      $this->_json_string = $json;
    }

    /**
    * Set Entity's Annotations unique IDs
    *
    * @param int array $annotationIDs of attribution object IDs for this Entity
    */
    public function setAnnotationIDs($annotationIDs) {
      return;
    }

    /**
    * Set Entity's Attributions unique IDs
    *
    * @param int array $attributionIDs of attribution object IDs for this Entity
    */
    public function setAttributionIDs($attributionIDs) {
      return;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
