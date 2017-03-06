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
  * Classes to deal with ProperNoun entities
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
  require_once (dirname(__FILE__) . '/Term.php');

  //*******************************************************************
  //****************   PROPERNOUN CLASS  *************************
  //*******************************************************************
  /**
  * ProperNoun represents a Proper value (set) for a given concept
  *
  * <code>
  * require_once 'ProperNoun.php';
  *
  * $propernoun = new ProperNoun( $resultRow );
  * echo "propernoun labeled with ".$propernoun->getLabels(true);
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class ProperNoun extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_labels,
              $_evidences,
              $_type_id,
              $_type,
              $_description,
              $_url;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an propernoun instance from a propernoun table row or a token table row
    * @param array $row associated with columns of the propernoun table, a valid prn_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null) {
      $this->_table_name = 'propernoun';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM propernoun WHERE prn_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= prn_owner_id or ".getUserID()." = ANY (\"prn_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('prn_id',$row)) {
          error_log("unable to query for propernoun ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['prn_id'] ? $arg['prn_id']:NULL;
        $this->_labels=@$arg['prn_labels'] ? $arg['prn_labels']:NULL;
        $this->_evidences=@$arg['prn_evidences'] ? $arg['prn_evidences']:NULL;
        $this->_type_id=@$arg['prn_type_id'] ? $arg['prn_type_id']:NULL;
        $this->_description=@$arg['prn_description'] ? $arg['prn_description']:NULL;
        $this->_url=@$arg['prn_url'] ? $arg['prn_url']:NULL;
        if (!array_key_exists('prn_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('prn_labels',$arg))$arg['prn_labels'] = $this->kvPairsToString($arg['prn_labels']);
          if (array_key_exists('prn_list_ids',$arg))$arg['prn_list_ids'] = $this->idsToString($arg['prn_list_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new propernoun to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "prn";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_labels)) {
        $this->_data['prn_labels'] = $this->_labels;
      }
      if ($this->_evidences) {
        $this->_data['prn__evidences'] = $this->_evidences;
      }
      if ($this->_type_id) {
        $this->_data['prn_type_id'] = $this->_type_id;
      }
      if (count($this->_description)) {
        $this->_data['prn_description'] = $this->_description;
      }
      if (count($this->_url)) {
        $this->_data['prn_url'] = $this->_url;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get ProperNoun's label
    *
    * @param string $lang determines which language label is returned (default = "en")
    * @return string label in the language indicated by $lang for this propernoun
    * @todo create global define loaded with prefernce of language for default
    */
    public function getLabel($lang = "en") {
      if (is_string($this->_labels)) {
        $labels = $this->kvPairsStringToArray($this->_labels);
      }else{
        $labels = $this->_labels;
      }
      if (array_key_exists($lang,$labels)){
        return $labels[$lang];
      }else{
        return $labels["en"];
      }
    }

    /**
    * Get ProperNoun's labels
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string that contains a list of language code : label keyvalue pairs for this propernoun
    */
    public function getLabels($asString = false) {
      if ($asString){
        return $this->kvPairsToString($this->_labels);
      }else{
        return $this->kvPairsStringToArray($this->_labels);
      }
    }

    /**
    * Get evidence of the propernoun
    *
    * @return string representing the key value pairs of evidence for this propernoun
    */
    public function getEvidence() {
      return $this->_evidences;
    }

    /**
    * Get TypeID of the propernoun
    *
    * @return int indentifying the propernoun from typology of propernouns for type for propernoun
    * @todo write lookup code to get label for propernoun id
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the propernoun
    *
    * @param string $lang identifying which language to return
    * @return string from terms identifying the type for this propernoun
    */
    public function getType($lang = "default") {
      if ($this->_type_id) {
        return Entity::getTermFromID($this->_type_id);
      } else {
        return null;
      }
    }

    /**
    * Gets the Description for this propernoun
    * @return string for the Description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Gets the url of this propernoun
    * @return int url of propernoun
    */
    public function getURL() {
      return $this->_url;
    }


    //********SETTERS*********

    /**
    * Set ProperNoun's labels
    *
    * @param string $labels of this propernoun
    */
    public function setLabels($labels) {
      if($this->_labels != $labels) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prn_labels",$this->kvPairsToString($labels));
      }
      $this->_labels = $labels;
    }

    /**
    * Set TypeID of the propernoun
    *
    * @param string  $typeID from a typology of propernouns for Type for propernoun
    * @todo add code to lookup id for propernoun also check if type is numeric
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prn_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Sets the Evidences for this propernoun
    * @param string $evids describing the evidence for the propernoun
    */
    public function setEvidence($evids) {
      if($this->_evidences != $evids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prn_evidences",$evids);
      }
      $this->_evidences = $evids;
    }

    /**
    * Sets the Description for this propernoun
    * @param string $desc describing the propernoun
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prn_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set ProperNoun's url
    *
    * @param string $url of this propernoun
    */
    public function setURL($url) {
      if($this->_url != $url) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prn_url",$url);
      }
      $this->_url = $url;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
