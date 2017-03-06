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
  * Classes to deal with Compound entities
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
  require_once (dirname(__FILE__) . '/Compound.php');
  require_once (dirname(__FILE__) . '/Token.php');

//*******************************************************************
//*********************   COMPOUNDS CLASS   *****************************
//*******************************************************************
/**
  * Compounds class which is an iterating container of compounds
  *
  * <code>
  * require_once 'Compounds.php';
  *
  * $compounds = new Compounds();
  * $compounds->loadEntities();
  * $compound = $compounds->current();
  * $key = $compounds->key();
  * echo " compound $key is ".$compound->getCompound();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */

  class Compounds extends EntityIterator {

    //*******************************PRIVATE MEMBERS************************************

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an Compounds iterator, optionally setting the offset and pagesize
    * @param int $pageSize sets the max size for query results (default 20)
    * @param int $offset sets the start point for query results (default 0)
    * @todo write a store procedure to test for intersection of 2 integer arrays for security checking access IDs with VisibilityIDs
    * @todo add code to load all ??? what about 10k or 100k case (could call to get rownum)
    */
    public function __construct( $condition = "", $sort = "cmp_id", $offset = 0, $pageSize = 20) {
      parent::__construct("compound","cmp_id",false);
      $this->_pageSize = $pageSize;
      $this->_offset = $offset;
      if ($condition) $this->_condition = $condition;
//      $this->_security = isSysAdmin()?null:" (".getUserID()."= cmp_owner_id or ".getUserID()." = ANY (\"cmp_visibility_ids\"))";
      $this->_security = parent::getEntityAccessCondition("cmp");
      $this->_sort = $sort;
      $this->_dbMgr = new DBManager();
      if ($condition != '' || $sort != "cmp_id" || $offset != 0 || $pageSize != 20) {
        $this->loadEntities();
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************


    /**
    * Compounds - array of compounds from the current query
    *
    * @return array returns a Compound array for the current page size (default is 20)
    */
    public function getCompounds() {
      return $this->_entities;
    }

    public function createObject($arg){
      return new Compound($arg);
    }

    /**
    * Load compound components from global ids list
    *
    * @param $globalIDs is a postgresql array of strings string of the form {"tok:1","cmp:3"}
    * @return true|false  returns true if the current position has a loaded compound or token, false otherwise
    */
    public function loadComponents($globalIDs) {
      if (is_string($globalIDs)) {
        $globalIDs = explode(",", preg_replace("/\{|\}/","",$globalIDs));
      }
      foreach ( $globalIDs as $globalID ){
        $prefix = substr($globalID,0,3);
        $id = intval(substr($globalID,4));
        if (!array_key_exists($globalID,$this->_keyIndexMap)) {
          if ($prefix == "tok"){
            array_push($this->_entities,new Token($id));//create token object
          }else if($prefix == "cmp"){
            array_push($this->_entities,new Compound($id));//create compound object
          }else{
            return false;
          }
          $this->_keyIndexMap[$globalID] = count($this->_entities)-1;
          array_push($this->_keys,$globalID);
        }
      }
      // don't change position for progressive retrieval getNextPage when next fails
      return isset($this->_entities[$this->_position]);
    }
   }
?>
