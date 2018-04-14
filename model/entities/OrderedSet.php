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
  * Classes to deal with Collection entities
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Entity Classes
  */
  require_once (dirname(__FILE__) . '/EntityFactory.php');

//*******************************************************************
//*********************   ORDEREDSET CLASS   *****************************
//*******************************************************************
/**
  * OrderedSet class which is an ordered iterating container of entities
  *
  * <code>
  * require_once 'OrderedSet.php';
  * $globalIDs = array( "tok:5","cmp:15","seq:47","frg:25");
  *
  * $entitySet = new OrderedSet();
  * $entitySet->loadEntities($globalIDsArray);
  * $entity = $entitySet->current();
  * $key = $entitySet->key();
  * echo " set member type ".$object->getEntityType()." has key $key";
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */

  class OrderedSet implements Iterator {

    //*******************************PROTECTED MEMBERS************************************

    /**
    * protected member variables
    * @access protected
    */
    protected $_validPrefixes,
              $_entities = array(),        //array of entity objects
              $_keys = array(),         //parallel array entities keys
              $_keyIndexMap = array(),  //array map of entity keys to index
              $_position = 0,           //index into array
              $_allowDups = false,          //allow duplicate
              $_ignoreDups = true,         //allow duplicate
              $_error;                  //error
    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an OrderedSet iterator, optionally setting the sets entity types
    * @param string array $entityPrefixes sets the list of valid prefixes to limit the types that can be in the set
    */
    public function __construct( $entityPrefixes = null) {
      if (@$entityPrefixes && is_array($entityPrefixes) && count($entityPrefixes)){
        $this->_validPrefixes = $entityPrefixes;
      }else{
        $this->_validPrefixes = Entity::$validPrefixes;
      }
    }

    //*******************************ITERATOR FUNCTIONS************************************

    /**
    * Reset to first element of set.
    */
    public function rewind() {
      $this->_position = 0;
    }

    /**
    * Get the current entity's position/key.
    *
    * @return int|string returns id of the Entity at the current position
    */
    public function key() {
      return array_key_exists($this->_position,$this->_keys) ? $this->_keys[$this->_position]:null;
    }

    /**
    * Get the current Entity in this container which may be the first in the results set.
    *
    * @return Entity|NULL  returns the current Entity in the results or NULL if invalid
    */
    public function current() {
      if ($this->valid()){
        return $this->_entities[$this->_position];
      }else{
        return NULL;
      }
    }

    /**
    * Move position to next index in array of entities in the results set.
    *
    */
    public function next() {
      ++$this->_position;
    }

    /**
    * Check that the current entity is available in the results set.
    *
    * @return true|false  returns true if the position has an Entity or false if not
    * @todo extend this to validate entity
    */
    public function valid() {
      return isset($this->_entities[$this->_position]);
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * getSet - array of entities for the current set
    *
    * @return Entity array for the current set
    */
    public function getSet() {
      return $this->_entities;
    }

    /**
    * Entities - array of Entities from the current set
    *
    * @return array returns an Entity array for the set
    */
    public function getEntities() {
      return $this->_entities;
    }

    /**
    * Entities' keys - array of keys for the Entities from the current set
    *
    * @return array returns an Entity keys array for the set
    */
    public function getKeys() {
      return $this->_keys;
    }

    /**
    * getCount - gets the number of entities contained
    *
    * @return int returns the number of entities in currently in the iterator
    */
    public function getCount() {
      return count($this->_entities);
    }

    /**
    * Set Allow Duplicate Switch
    *
    * @param boolean allowing (true) or disallowing (false - default) duplicate entries in the set
    */
    public function setAllowDups($value = false) {
      $this->_allowDups = $value;
    }

    /**
    * Set Ignore Duplicate Switch
    *
    * @param boolean determining behavior for disallowed duplicates when found - ignored (true - default) or error (false)
    */
    public function setIgnoreDups($value = true) {
      $this->_ignoreDups = $value;
    }

    /**
    * findEntity - find entity by it's globalID from the current set
    *
    * @param string $gid global ID string identifying the unique entity
    * @return Entity returns an Entity from the set for the given $gid if exist, null otherwise
    */
    public function findEntity($gid) {
      if (array_key_exists($gid,$this->_keyIndexMap)) {
        return $this->_entities[$this->_keyIndexMap[$gid]];
      }
      return null;
    }

    /**
    * Error - string identifying any error during load.
    *
    * @return string|NULL returns an error string or NULL
    */
    public function getError() {
      return ($this->_error ? $this->_error : NULL);
    }

    /**
    * Load collection objects from global ids array
    *
    * @param string array $globalIDsArray is an array of GlobalID strings of the form array( "tok:5","cmp:15","seq:47","frg:25")
    * @return true|false  returns true if the current position has a loaded object, false otherwise
    */
    public function loadEntities($globalIDsArray) {
      foreach ( $globalIDsArray as $globalID ){
        if (array_key_exists($globalID,$this->_keyIndexMap) && !$this->_allowDups) {//duplicate
          $errMsg = "duplicate global id found $globalID";
          if (!$this->_ignoreDups) {
            $this->_error = "duplicate global id found $globalID";
            return false;
          } else {
            continue;
          }
        }else{
          $entity = EntityFactory::createEntityFromGlobalID($globalID);
          if (!$entity || !$entity->getID()){
            $this->_error = "Error creating GID $globalID - ".EntityFactory::$error;
            return false;
          }
          if (count($entity->getErrors()) > 0){
            $this->_error = join(":", $entity->getErrors());
            return false;
          }
          array_push($this->_entities,$entity);
          $this->_keyIndexMap[$globalID] = count($this->_entities)-1;
          array_push($this->_keys,$globalID);
        }
      }
      // don't change position for progressive loading
      return $this->valid();
    }
   }
?>
