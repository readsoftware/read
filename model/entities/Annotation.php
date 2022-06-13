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
  * Classes to deal with Annotation entities
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
  require_once (dirname(__FILE__) . '/EntityFactory.php');

  //*******************************************************************
  //****************   ANNOTATION CLASS  *************************
  //*******************************************************************
  /**
  * Annotation represents annotation entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Annotation.php';
  *
  * $annotation = new Annotation( $resultRow );
  * echo "annotation labeled with ".$annotation->getText();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Annotation extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_text,
              $_linkfrom_ids,
              $_linkedfrom_entities,
              $_linkto_ids,
              $_linkedto_entities,
              $_type_id,
              $_url;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an annotation instance from a annotation table row or a token table row
    * @param array $row associated with columns of the annotation table, a valid ano_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null) {
      $this->_table_name = 'annotation';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM annotation WHERE ano_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= ano_owner_id or ".getUserID()." = ANY (\"ano_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('ano_id',$row)) {
          error_log("unable to query for annotation ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['ano_id'] ? $arg['ano_id']:NULL;
        $this->_text=@$arg['ano_text'] ? $arg['ano_text']:NULL;
        $this->_linkfrom_ids=@$arg['ano_linkfrom_ids'] ? $arg['ano_linkfrom_ids']:NULL;
        $this->_linkto_ids=@$arg['ano_linkto_ids'] ? $arg['ano_linkto_ids']:NULL;
        $this->_type_id=@$arg['ano_type_id'] ? $arg['ano_type_id']:NULL;
        $this->_url=@$arg['ano_url'] ? $arg['ano_url']:NULL;
        if (!array_key_exists('ano_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('ano_linkfrom_ids',$arg))$arg['ano_linkfrom_ids'] = $this->arraysOfIdsToString($arg['ano_linkfrom_ids']);
          if (array_key_exists('ano_linkto_ids',$arg))$arg['ano_linkto_ids'] = $this->arraysOfIdsToString($arg['ano_linkto_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new annotation to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "ano";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_text)) {
        $this->_data['ano_text'] = $this->_text;
      }
      if (count($this->_linkfrom_ids)) {
        $this->_data['ano_linkfrom_ids'] = $this->arraysOfIdsToString($this->_linkfrom_ids);
      }
      if (count($this->_linkto_ids)) {
        $this->_data['ano_linkto_ids'] = $this->arraysOfIdsToString($this->_linkto_ids);
      }
      if ($this->_type_id) {
        $this->_data['ano_type_id'] = $this->_type_id;
      }
      if (count($this->_url)) {
        $this->_data['ano_url'] = $this->_url;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Annotation's text
    *
    * @return string text of this annotation
    */
    public function getText() {
      return $this->_text;
    }

    /**
    * Get Annotation's value
    *
    * @return string text of this annotation
    */
    public function getValue() {
      return $this->_text;
    }

    /**
    * Get Annotation's linked from entity unique global IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string uniquely identifying entites typPrefix:ID linked with this annotation
    */
    public function getLinkFromIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_linkfrom_ids);
      }else{
        return $this->stringOfStringsToArray($this->_linkfrom_ids);
      }
    }

    /**
    * Get Annotation's linked from entity objects
    *
    * @return OrderedSet iterator for the entities of this annotation or NULL
    */
    public function getLinkedFromEntities($autoExpand = false) {
      if (!$this->_linkedfrom_entities && $autoExpand && count($this->getLinkFromIDs())>0) {
        $this->_linkedfrom_entities = new OrderedSet();
        $this->_linkedfrom_entities->loadEntities($this->getLinkFromIDs());
      }
      return $this->_linkedfrom_entities;
    }

    /**
    * Get Annotation's linked to entity unique global IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string uniquely identifying entites typPrefix:ID linked with this annotation
    */
    public function getLinkToIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_linkto_ids);
      }else{
        return $this->stringOfStringsToArray($this->_linkto_ids);
      }
    }

    /**
    * Get Annotation's linked to entity objects
    *
    * @return OrderedSet iterator for the entities of this annotation or NULL
    */
    public function getLinkedToEntities($autoExpand = false) {
      if (!$this->_linkedfrom_entities && $autoExpand && count($this->getLinkToIDs())>0) {
        $this->_linkedto_entities = new OrderedSet();
        $this->_linkedto_entities->loadEntities($this->getLinkToIDs());
      }
      return $this->_linkedto_entities;
    }

    /**
    * Get TypeID of the annotation
    *
    * @return int indentifying the term from typology of terms for types for annotations
    * @todo write lookup code to get label for term id
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the annotation
    *
    * @param string $lang identifying which language to return
    * @return string from terms identifying the type for this annotation
    * @todo write lookup code to get label for term id
    */
    public function getType($lang = "default") {
      if ($this->_type_id) {
        return Entity::getTermFromID($this->_type_id);
      }
      return null;
    }

    /**
    * Gets the URL used in this annotation
    * @return string URL of this annotation
    */
    public function getURL() {
      return $this->_url;
    }

    //********SETTERS*********

    /**
    * Set Annotation's text
    *
    * @param string $text of this annotation
    */
    public function setText($text) {
      if($this->_text != $text) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ano_text",$text);
      }
      $this->_text = $text;
    }

    /**
    * Set Annotation's linked from entity unique globalIDs
    *
    * @param string array $gids that contains entity globalIDs linked with this annotation
    */
    public function setLinkFromIDs($gids) {
      if($this->_linkfrom_ids != $gids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ano_linkfrom_ids",$this->stringsToString($gids));
      }
      $this->_linkfrom_ids = $gids;
    }

    /**
    * Set Annotation's linked to entity unique globalIDs
    *
    * @param string array $gids that contains entity globalIDs linked by this annotation
    */
    public function setLinkToIDs($gids) {
      if($this->_linkto_ids != $gids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ano_linkto_ids",$this->stringsToString($gids));
      }
      $this->_linkto_ids = $gids;
    }

    /**
    * Set TypeID of the annotation
    *
    * @param string  $typeID from a typology of terms for Type for annotations
    * @todo add code to lookup id for term also check if type is numeric
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ano_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Set the URL of this Annotation
    *
    * @param string $url of this Annotation
    */
    public function setURL($url) {
      if($this->_url != $url) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("ano_url",$url);
      }
      $this->_url = $url;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
