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
  * Classes to deal with AttributionGroup entities
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
  require_once (dirname(__FILE__) . '/AttributionGroup.php');

//*******************************************************************
//*********************   ATTRIBUTIONGROUPS CLASS   *****************************
//*******************************************************************
/**
  * AttributionGroups class which is an iterating container of attributionGroups
  *
  * <code>
  * require_once 'AttributionGroups.php';
  *
  * $attributiongroups = new AttributionGroups("",,10,5);
  * $attributiongroup = $attributionGroups->current();
  * $key = $attributiongroups->key();
  * echo " attributiongroup $key is ".$attributiongroup->getName();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */

  class AttributionGroups extends EntityIterator {

    //*******************************PRIVATE MEMBERS************************************
    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an AttributionGroups iterator, optionally setting the offset and pagesize
    * @param int $pageSize sets the max size for query results (default 20)
    * @param int $offset sets the start point for query results (default 0)
    * @todo write a store procedure to test for intersection of 2 integer arrays for security checking access IDs with VisibilityIDs
    */
    public function __construct( $condition = "", $sort = "atg_id", $offset = 0, $pageSize = 20) {
      parent::__construct("attributiongroup","atg_id");
      $this->_autoAdvancePage = true;
      $this->_pageSize = $pageSize;
      $this->_offset = $offset;
      if ($condition) $this->_condition = $condition;
//      $this->_security = isSysAdmin()?null:" (".getUserID()."= atg_owner_id or ".getUserID()." = ANY (\"atg_visibility_ids\"))";
      $this->_security = parent::getEntityAccessCondition("atg");
      $this->_sort = $sort;
      $this->_dbMgr = new DBManager();
      $this->loadEntities();
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * AttributionGroups - array of attributionGroups from the current query
    *
    * @return array returns an AttributionGroup array for the current page size (default is 20)
    */
    public function getAttributionGroups() {
      return $this->_entities;
    }

    public function createObject($arg){
      return new AttributionGroup($arg);
    }

  }
?>
