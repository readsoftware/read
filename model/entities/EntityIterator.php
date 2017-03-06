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
  * Abstract Class to capture common code for iterating entities
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Entity Classes
  */
  require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database defines

//*******************************************************************
//*********************   ENTITYITERATOR CLASS   *****************************
//*******************************************************************
/**
  * EntityIterator class which is an iterating container of entities
  *
  * <code>
  * <code>
  * require_once 'EntityIterator.php';
  *
  * class baselines extends EntityIterator {
  *
  *   public function __construct($arg) {
  *         parent::__construct("baseline","bln_id"];
  *         ...}
  * $baselines = new Baselines(10,5);
  * $baseline = $baselines->current();
  * $key = $baselines->key();
  * echo " baseline $key has image - ".$baseline->getImageURL();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @todo   add member for advance to next page.
  */

  abstract class EntityIterator implements Iterator {

    //*******************************PRIVATE MEMBERS************************************

    /**
    * protected member variables
    * @access protected
    */
    protected $_entities = array(),        //array of entity objects
              $_tableName,
              $_pkColumnName,
              $_keys = array(),         //parallel array entities keys
              $_keyMap ,                //array of entity keys to set interation order
              $_keyIndexMap = array(),  //array map of entity keys to index
              $_position = 0,           //index into array
              $_dbMgr,                  //dbManager
              $_autoAdvancePage,        //auto query next page in next()
              $_query,                  //query
              $_condition,              //condition
              $_security,               //security
              $_sort,                   //sort
              $_error,                  //error
              $_pageSize,               //limit for the query result
              $_offset;                 //offset for the query result


    /**
    * Build access condition for entities query with visibility checks
    *
    * @param string $prefix of entity
    * @return string that represents a postgreSQL condition that checks access for user groups
    */
    protected function getEntityAccessCondition($prefix){
      //select *,case when edn_owner_id = ANY(ARRAY[2,14,15,20]) then 1 else 0 end as editable from edition
      //where edn_owner_id = ANY(ARRAY[2,14,15,20]) or ARRAY[2,14,15,20] && edn_visibility_ids;
      $c =  (isSysAdmin()?"":" (".$prefix."_owner_id = ANY(ARRAY[".join(",",getUserMembership()).",".getUserID()."])  OR ".
                              "ARRAY[".join(",",getUserMembership()).",".getUserID()."] && ".$prefix."_visibility_ids)");
      return $c;
    }

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an Entity iterator, setting table name and pKey column name
    * @param string $tableName sets table name
    * @param sting $pkeyColumnName sets the primary key column name
    * @todo write check and exception code for invalid tableName of pkColumnName
    */
    public function __construct($tableName, $pkColumnName, $autoAdvancePage = true) {
      $this->_tableName = $tableName;
      $this->_pkColumnName = $pkColumnName;
      $this->_autoAdvancePage = $autoAdvancePage;
    }

    //*******************************ITERATOR FUNCTIONS************************************

    /**
    * Reset to beginning of result set.
    */
    public function rewind( ) {
      $this->_position = 0;
    }

    /**
    * Get the current position in the result set.
    *
    * @return int returns id of the Entity at the current position
    */
    public function key( ) {
      if ($this->_keyMap) {
       return array_key_exists($this->_position,$this->_keyMap) ? $this->_keyMap[$this->_position]:null;
      }
      return array_key_exists($this->_position,$this->_keys) ? $this->_keys[$this->_position]:null;
    }

    /**
    * Get the current Entity in this container which may be the first in the results set.
    *
    * @return Entity|NULL  returns the current Entity in the results or NULL if invalid
    */
    public function current( ) {
      if ($this->valid()){
        if ($this->_keyMap) {
          return $this->_entities[ $this->_keyIndexMap[$this->_keyMap[$this->_position]]];
        }
        return $this->_entities[$this->_position];
      }else{
        return NULL;
      }
    }

    /**
    * Move position to next index in array of entities in the results set.
    *
    * @return int|NULL  returns the position or NULL if at the end
    */
    public function next( ) {
      if (!$this->_keyMap && $this->_autoAdvancePage && ($this->_position + 1) >= count($this->_entities)){
          $this->_offset += $this->_pageSize;
          $this->loadEntities();
      }
      return ++$this->_position;
    }

    /**
    * Check that the current entity is available in the results set.
    *
    * @return true|false  returns true if the position has an Entity or false if not
    * @todo extend this to validate entity
    */
    public function valid() {
      if ($this->_keyMap) {
       return array_key_exists($this->_position,$this->_keyMap) &&
                array_key_exists($this->_keyMap[$this->_position],$this->_keyIndexMap);
      }
      return isset($this->_entities[$this->_position]);//
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Create object
    *
    */
    abstract public function createObject($arg);

    /**
    * Get results set for reconstructed query
    *
    * @return true|false  returns true if the query was successful, false otherwise
    */
    public function loadEntities($args = null) {//args null but allows params in subclasses
      //rebuild query
      $this->_buildQuery();
      $this->_error = NULL;
//      error_log($this->_query);
      $this->_dbMgr->query($this->_query);// query always selects *
      while($row = $this->_dbMgr->fetchResultRow()){
        $key = $row[$this->_pkColumnName];
        if (!array_key_exists($key,$this->_keyIndexMap)) {
          array_push($this->_entities,$this->createObject($row));//call derived class to create object
          $this->_keyIndexMap[$key] = count($this->_entities)-1;
          array_push($this->_keys,$key);
        }
      }
      // don't change position for progressive retrieval getNextPage when next fails
      return isset($this->_entities[$this->_position]);
    }

    /**
    * Entities - array of Entities from the current query
    *
    * @return array returns an Entity array for the current page size (default is 20)
    */
    public function getEntities() {
      return $this->_entities;
    }

    /**
    * searchKey - gets entity for a given key or returns NULL
    *
    * @param int $key uniquely identifies an entity
    * @return Entity returns an Entity or NULL
    */
    public function searchKey($key) {
      return (array_key_exists($key,$this->_keyIndexMap)? $this->_entities[$this->_keyIndexMap[$key]]:NULL);
    }

    //********GETTERS*********

    /**
    * Page Size - limits the number of entities contained
    *
    * @return int returns the current page size (default is 20)
    */
    public function getPageSize() {
      return $this->_pageSize;
    }

    /**
    * getKeys - gets array of int as primary keys for each of the contained entities
    *
    * @return array int returns the unique IDs of the contained entities
    */
    public function getKeys() {
      return $this->_keys;
    }

    /**
    * getCurIndex - gets the index of the current entity
    *
    * @return int returns the number index of current entity
    */
    public function getCurIndex() {
      return $this->_position;
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
    * Offset - the start position in the result set for the current query
    *
    * @return int returns the current page size (default is 20)
    */
    public function getOffset() {
      return $this->_offset;
    }

    /**
    * Error - string identifying any error during query.
    *
    * @return string|NULL returns an error string or NULL
    */
    public function getError() {
      return ($this->_error ? $this->_error : NULL);
    }

    /**
    * Query - string identifying the query.
    *
    * @return string used to query results
    */
    public function getQuery() {
      return $this->_query;
    }

    //********SETTERS*********

    /**
    * Set Order Map sets an array of keys that is used for iteration of the entities
    * this shuts off auto advance
    *
    * @param array of keys $keys used with keymap to find entity limit the number of query results
    */
    public function setOrderMap($keyMap) {
      $this->_keyMap = $keyMap;
    }

    /**
    * Set Page Size sets the limit of entities per page
    *
    * @param int $size used to limit the number of query results
    */
    public function setPageSize($size) {
      $this->_pageSize = $size;
    }

    /**
    * Set Offset - sets the start position in the result set for the current query
    *
    * @param int $offset used to set start of query results
    */
    public function setOffset($offset) {
      $this->_offset = $offset;
    }

    /**
    * AutoAdvancePage - sets the start position in the result set for the current query
    *
    * @param boolean $auto used to turn on and off auto advance of iterator
    */
    public function setAutoAdvance($auto) {
      $this->_autoAdvancePage = $auto;
    }


    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Builds the query from the current private members
    * @todo consider using expanded projection and include array_to_string("bln_AnnotationIDs"::int[],\',\') as "bln_AnnotationIDs"
    */
    protected function _buildQuery() {
      $this->_query = "SELECT * FROM ".$this->_tableName." ".//we get it all which is assumed in loadEntities
      ($this->_condition? " WHERE ".$this->_condition.($this->_security ? " AND ".$this->_security:""):($this->_security ? " WHERE ".$this->_security:"")).
      ($this->_sort? " ORDER BY ".$this->_sort:"").
      ($this->_offset ? " OFFSET ".$this->_offset:"").
      ($this->_pageSize? " LIMIT ".$this->_pageSize:"");
    }
  }
?>
