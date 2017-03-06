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
  * Classes to deal with Catalog entities
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
  require_once (dirname(__FILE__) . '/Editions.php');

//*******************************************************************
//****************   CATALOG CLASS    **********************************
//*******************************************************************
  /**
  * Catalog represents catalog entity which is collection of text editions
  *
  * <code>
  * require_once 'Catalog.php';
  *
  * $catalog = new Catalog( $resultRow );
  * echo $catalog->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Catalog extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_title,
              $_type_id,
              $_description,
              $_edition_ids=array(),
              $_editions;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an catalog instance from an catalog table row
    * @param array $row associated with columns of the catalog table, a valid cat_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'catalog';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM catalog WHERE cat_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= cat_owner_id or ".getUserID()." = ANY (\"cat_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('cat_id',$row)) {
          error_log("unable to query for catalog ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['cat_id'] ? $arg['cat_id']:NULL;
        $this->_title=@$arg['cat_title'] ? $arg['cat_title']:NULL;
        $this->_type_id=@$arg['cat_type_id'] ? $arg['cat_type_id']:NULL;
        $this->_description=@$arg['cat_description'] ? $arg['cat_description']:NULL;
        $this->_edition_ids=@$arg['cat_edition_ids'] ? $arg['cat_edition_ids']:NULL;
        if (!array_key_exists('cat_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('cat_edition_ids',$arg))$arg['cat_edition_ids'] = $this->idsToString($arg['cat_edition_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new catalog to be initialized through setters
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
      return "cat";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->cat_title)) {
        $this->_data['cat_title'] = $this->_title;
      }
      if ($this->_type_id) {
        $this->_data['cat_type_id'] = $this->_type_id;
      }
      if (count($this->_description)) {
        $this->_data['cat_description'] = $this->_description;
      }
      if (count($this->_edition_ids)) {
        $this->_data['cat_edition_ids'] = $this->idsToString($this->_edition_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********
    /**
    * Get Title of the catalog
    *
    * @return string represents a human readable label for this catalog
    */
    public function getTitle() {
      return $this->_title;
    }

    /**
    * Get TypeID of the catalog
    *
    * @return int indentifying the term from typology of terms for types of catalogs
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the catalog
    *
    * @param string $lang identifying which language to return
    * @return string from terms identifying the type for this catalog
    */
    public function getType($lang = "en") {
      return Entity::getTermFromID($this->_type_id);
    }

    /**
    * Gets the Description for this Catalog
    * @return string for the Description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get Unique IDs of edition entities which make up this catalog
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of edition entity IDs
    */
    public function getEditionIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_edition_ids);
      }else{
        return $this->idsStringToArray($this->_edition_ids);
      }
    }

    /**
    * Get Catalog's Edition objects
    *
    * @return Editions for this catalog or NULL
    */
    public function getEditions($autoExpand = false) {
      if (!$this->_editions && $autoExpand && count($this->getEditionIDs())>0) {
        $this->_editions = new Editions("edn_id in (".join(",",$this->getEditionIDs()).")","edn_id",null,null);
        $this->_editions->setOrderMap($this->getEditionIDs());//ensure the iterator will server objects as in order list of ids.
      }
      return $this->_editions;
    }


    //********SETTERS*********

   /**
    * Set Title of the catalog
    *
    * @param string $title represents a more human readable label for this catalog
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cat_title",$title);
      }
      $this->_title = $title;
    }

    /**
    * Set Type of the Catalog
    *
    * @param int $typeID from a typology of terms for types of Catalogs
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cat_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Sets the Description for this Catalog
    * @param string $desc describing the Catalog
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cat_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set Unique IDs of edition entities which make up this catalog
    *
    * @param int array $edition_ids of catalog entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setEditionIDs($edition_ids) {
      if($this->_edition_ids != $edition_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cat_edition_ids",$this->idsToString($edition_ids));
      }
       $this->_edition_ids = $edition_ids;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
