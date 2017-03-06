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
  * Classes to deal with collection entities
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
  require_once (dirname(__FILE__) . '/Part.php');
  require_once (dirname(__FILE__) . '/Fragment.php');
  require_once (dirname(__FILE__) . '/Item.php');

//*******************************************************************
//****************   COLLECTION CLASS    **********************************
//*******************************************************************
  /**
  * Collection represents collection entity which is metadata about a piece of an artefact part
  *
  * <code>
  * require_once 'Collection.php';
  *
  * $collection = new Collection( $resultRow );
  * echo $Collection->getType();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Collection extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_title,
              $_description,
              $_location_refs,
              $_item_part_fragment_ids,
              $_objects,
              $_excl_part_fragment_ids,
              $_excluded_objects;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create a Collection instance from a collection table row or null
    * @param array $row associated with columns of the collection table, a valid itm_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'collection';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM collection WHERE col_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= col_owner_id or ".getUserID()." = ANY (\"col_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('col_id',$row)) {
          error_log("unable to query for collection ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['col_id'] ? $arg['col_id']:NULL;
        $this->_title=@$arg['col_title'] ? $arg['col_title']:NULL;
        $this->_description=@$arg['col_description'] ? $arg['col_description']:NULL;
        $this->_location_refs=@$arg['col_location_refs'] ? $arg['col_location_refs']:NULL;
        $this->_item_part_fragment_ids=@$arg['col_item_part_fragment_ids'] ? $arg['col_item_part_fragment_ids']:NULL;
        $this->_excl_part_fragment_ids=@$arg['col_exclude_part_fragment_ids'] ? $arg['col_exclude_part_fragment_ids']:NULL;
        if (!array_key_exists('col_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('col_location_refs',$arg))$arg['col_location_refs'] = $this->stringsToString($arg['col_location_refs']);
          if (array_key_exists('col_item_part_fragment_ids',$arg))$arg['col_item_part_fragment_ids'] = $this->arraysOfIdsToString($arg['col_item_part_fragment_ids']);
          if (array_key_exists('col_exclude_part_fragment_ids',$arg))$arg['col_exclude_part_fragment_ids'] = $this->arraysOfIdsToString($arg['col_exclude_part_fragment_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new collection to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "col";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_title)) {
        $this->_data['col_title'] = $this->_title;
      }
      if (count($this->_description)) {
        $this->_data['col_description'] = $this->_description;
      }
      if (count($this->_location_refs)) {
        $this->_data['col_location_refs'] = $this->stringsToString($this->_location_refs);
      }
      if (count($this->_item_part_fragment_ids)) {
        $this->_data['col_item_part_fragment_ids'] = $this->arraysOfIdsToString($this->_item_part_fragment_ids);
      }
      if (count($this->_excl_part_fragment_ids)) {
        $this->_data['col_exclude_part_fragment_ids'] = $this->arraysOfIdsToString($this->_excl_part_fragment_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********


    /**
    * Get title of the artefact collection
    *
    * @return string title of the collection
    */
    public function getTitle() {
      return $this->_title;
    }

    /**
    * Get description of the artefact collection
    *
    * @return string describing of the collection
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get location reference identifiers of this collection of the artefact
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array or string specifiying the location's reference numbers of the collection
    */
    public function getLocationRefs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_location_refs);
      }else{
        return $this->stringOfStringsToArray($this->_location_refs);
      }
    }

    /**
    * Get Collection's Object unique gIDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for object gIDs in this collection
    */
    public function getObjectGlobalIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_item_part_fragment_ids);
      }else{
        return $this->stringOfStringsToArray($this->_item_part_fragment_ids);
      }
    }

    /**
    * Get Collection's objects
    *
    * @return CollectionObjects iterator for the objects of this Collection or NULL
    */
    public function getObjects($autoExpand = false) {
      if (!$this->_objects && $autoExpand && count($this->getObjectGlobalIDs())>0) {
        $this->_objects = new CollectionObjects();
        $this->_objects->loadObjects($this->getObjectGlobalIDs());
      }
      return $this->_objects;
    }

    /**
    * Get Collection's Excluded Object unique gIDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for excluded object gIDs for this collection
    */
    public function getExcludedObjectGlobalIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_excl_part_fragment_ids);
      }else{
        return $this->stringOfStringsToArray($this->_excl_part_fragment_ids);
      }
    }

    /**
    * Get Collection's excluded objects
    *
    * @return CollectionObjects iterator for the excluded objects of this Collection or NULL
    */
    public function getExcludedObjects($autoExpand = false) {
      if (!$this->_excluded_objects && $autoExpand && count($this->getExcludedObjectGlobalIDs())>0) {
        $this->_excluded_objects = new CollectionObjects();
        $this->_excluded_objects->loadObjects($this->getExcludedObjectGlobalIDs());
      }
      return $this->_excluded_objects;
    }


    //********SETTERS*********
    /**
    * Set Title of the collection
    *
    * @param string $title represents a more human readable label for this collection
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("col_title",$title);
      }
      $this->_title = $title;
    }

    /**
    * Set Description of the collection
    *
    * @param string $desc  describing the collection
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("col_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set location references of the collection
    *
    * @param string array or string $locRefs with the place ids and the location's reference strings for the collection
    */
    public function setLocationRefs($locRefs) {
      if($this->_location_refs != $locRefs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("col_location_refs",$this->stringsToString($locRefs));
      }
      $this->_location_refs = $locRefs;
    }

    /**
    * Set the set of object global ids for the whole item, part and or fragments of this Collection
    *
    * @param string array of $gids to identify include item, part or fragments of this collection
    * @todo add code to check duplicates
    */
    public function setItemPartFragmentIDs($gids) {
      if($this->_item_part_fragment_ids != $gids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("col_item_part_fragment_ids",$this->stringsToString($gids));
      }
      $this->_item_part_fragment_ids = $gids;
    }

    /**
    * Set the set of object global ids for excluded part and or fragments
    *  which are contained in item or part of this Collection
    *
    * @param string array of $gids to identify excluded parts or fragments
    * @todo add code to check duplicates and containment
    */
    public function setExcludedPartFragmentIDs($gids) {
      if($this->_excl_part_fragment_ids != $gids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("col_exclude_part_fragment_ids",$this->stringsToString($gids));
      }
      $this->_excl_part_fragment_ids = $gids;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
