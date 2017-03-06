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
  require_once (dirname(__FILE__) . '/EntityIterator.php');
  require_once (dirname(__FILE__) . '/Collection.php');
  require_once (dirname(__FILE__) . '/Item.php');
  require_once (dirname(__FILE__) . '/Part.php');
  require_once (dirname(__FILE__) . '/Fragment.php');

//*******************************************************************
//*********************   COLLECTIONS CLASS   *****************************
//*******************************************************************
/**
  * Collections class which is an iterating container of collections
  *
  * <code>
  * require_once 'CollectionObjs.php';
  * $globalIDsArray = array( "itm" => array(1,2,3),
  *                          "prt" => array(5),
  *                          "frg" => array(55,7,89));
  *
  * $collectionObjs = new CollectionObjects();
  * $collectionObjs->loadObjects($globalIDsArray);
  * $object = $collectionObjs->current();
  * $key = $collectionObjs->key();
  * echo " collection ".$object->getEntityType()."key is $key";
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */

  class CollectionObjects extends EntityIterator {

    //*******************************PRIVATE MEMBERS************************************

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an CollectionObjects iterator, optionally setting the offset and pagesize
    * @param int $pageSize sets the max size for query results (default 20)
    * @param int $offset sets the start point for query results (default 0)
    * @todo write a store procedure to test for intersection of 2 integer arrays for security checking access IDs with VisibilityIDs
    * @todo add code to load all ??? what about 10k or 100k case (could call to get rownum)
    */
    public function __construct( $condition = "", $sort = "col_id", $offset = 0, $pageSize = 20) {
      parent::__construct("collection","col_id",false);
      $this->_pageSize = $pageSize;
      $this->_offset = $offset;
      if ($condition) $this->_condition = $condition;
//      $this->_security = isSysAdmin()?null:" (".getUserID()."= col_owner_id or ".getUserID()." = ANY (\"col_visibility_ids\"))";
      $this->_security = parent::getEntityAccessCondition("col");
      $this->_sort = $sort;
      $this->_dbMgr = new DBManager();
    }

    //*******************************PUBLIC FUNCTIONS************************************


    /**
    * Collections - array of collections from the current query
    *
    * @return array returns a Collection array for the current page size (default is 20)
    */
    public function getCollectionObjects() {
      return $this->_entities;
    }

    public function createObject($arg){
      return new Collection($arg);
    }

    /**
    * Move position to next index in array of entities in the results set.
    *
    * @return int  returns the position or NULL if at the end
    */
    public function next( ) {
      return ++$this->_position;
    }

    /**
    * Load collection objects from global ids array
    *
    * @param $globalIDsArray is a postgresql array of strings of the form array("itm"=>array(1,3),"frg"=>array(3))
    * @return true|false  returns true if the current position has a loaded object, false otherwise
    */
    public function loadObjects($globalIDsArray) {
      foreach ( $globalIDsArray as $prefix => $idsArray ){
        foreach ($idsArray as $id) {
          $globalID = $prefix.":".$id;
          if (!array_key_exists($globalID,$this->_keyIndexMap)) {
            if ($prefix == "itm"){
              array_push($this->_entities,new Item($id));//create item object
            }else if($prefix == "prt"){
              array_push($this->_entities,new Part($id));//create part object
            }else if($prefix == "frg"){
              array_push($this->_entities,new Fragment($id));//create part object
            }else{
              $this->_error .= "- nonexistant globalID $globalID -";
              continue;
            }
            $this->_keyIndexMap[$globalID] = count($this->_entities)-1;
            array_push($this->_keys,$globalID);
          }
        }
      }
      // don't change position for progressive retrieval getNextPage when next fails
      return isset($this->_entities[$this->_position]);
    }
   }
?>
