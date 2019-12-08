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
  * Classes to deal with Item entities
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
  require_once (dirname(__FILE__) . '/Parts.php');
//  require_once (dirname(__FILE__) . '/Collections.php');
  require_once (dirname(__FILE__) . '/Images.php');

//*******************************************************************
//****************   ITEM CLASS    **********************************
//*******************************************************************
  /**
  * Item represents item entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Item.php';
  *
  * $item = new Item( $resultRow );
  * echo $item->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Item extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_title,
              $_type_id,
              $_description,
              $_idno,
              $_measure,
              $_shape_id,
              $_collections,
              $_parts,
              $_image_ids=array(),
              $_images;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an item instance from an item table row
    * @param array $row associated with columns of the item table, a valid itm_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'item';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM item WHERE itm_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= itm_owner_id or ".getUserID()." = ANY (\"itm_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('itm_id',$row)) {
          error_log("unable to query for item ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['itm_id'] ? $arg['itm_id']:NULL;
        $this->_title=@$arg['itm_title'] ? $arg['itm_title']:NULL;
        $this->_title=@$arg['itm_description'] ? $arg['itm_description']:NULL;
        $this->_title=@$arg['itm_idno'] ? $arg['itm_idno']:NULL;
        $this->_type_id=@$arg['itm_type_id'] ? $arg['itm_type_id']:NULL;
        $this->_measure=@$arg['itm_measure'] ? $arg['itm_measure']:NULL;
        $this->_shape_id=@$arg['itm_shape_id'] ? $arg['itm_shape_id']:NULL;
        $this->_image_ids=@$arg['itm_image_ids'] ? $arg['itm_image_ids']:NULL;
        if (!array_key_exists('itm_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('itm_image_ids',$arg))$arg['itm_image_ids'] = $this->idsToString($arg['itm_image_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new item to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "itm";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_title)) {
        $this->_data['itm_title'] = $this->_title;
      }
      if (count($this->_description)) {
        $this->_data['itm_description'] = $this->_description;
      }
      if (count($this->_idno)) {
        $this->_data['itm_idno'] = $this->_idno;
      }
      if ($this->_type_id) {
        $this->_data['itm_type_id'] = $this->_type_id;
      }
      if (count($this->_measure)) {
        $this->_data['itm_measure'] = $this->_measure;
      }
      if ($this->_shape_id) {
        $this->_data['itm_shape_id'] = $this->_shape_id;
      }
      if (count($this->_image_ids)) {
        $this->_data['itm_image_ids'] = $this->idsToString($this->_image_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Gets the title for this Item
    * @return string $title
    */
    public function getTitle() {
      return $this->_title;
    }

    /**
    * Gets the description for this Item
    * @return string $description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Gets the id number for this Item
    * @return string $idno
    */
    public function getIdNo() {
      return $this->_idno;
    }

    /**
    * Get Type of the artefact
    *
    * @return string from a typology of terms for artefacts
    */
    public function getType() {
      $type = "unknown";
      if ($this->_type_id && $this->getTermFromID($this->_type_id)) {
        $type = $this->_type_id;
      } else if ($this->getScratchProperty('type')) {
        $state = $this->getScratchProperty('type');
      }
      return $type;
    }

    /**
    * Gets the measure for this Item
    * @return string identifying the measure of this item
    */
    public function getMeasure() {
      return $this->_measure;
    }

    /**
    * Get Item's Shape term unique ID
    *
    * @return int shape term id for this item
    */
    public function getShapeID() {
      return $this->_shape_id;
    }

    /**
    * Get Shape for this artefact
    *
    * @return string specifiying the shape of the item
    */
    public function getShape() {
      $shape = "unknown";
      if ($this->_shape_id && $this->getTermFromID($this->_shape_id)) {
        $shape =  $this->getTermFromID($this->_shape_id);
      } else if ($this->getScratchProperty('shape')) {
        $shape = $this->getScratchProperty('shape');
      }
      return $shape;
    }

    /**
    * Get Item's collection object
    *
    * @return collection that contains this item
    */
    public function getCollections($synch=false) {
      if (!$this->_collections || $synch) {
        $condition = "itm:".$this->_id." = ANY (\"col_item_part_fragment_ids\") ";
//        $this->_collections = new Collections($condition,null,null,null);
//        $this->_collections->setAutoAdvance(false);
      }
      return $this->_collections;
    }

     /**
    * Get Parts object which contains all parts attached to this item
    *
    * @return Parts iterator with all parts linked to this item
    */
    public function getParts() {
      if (!$this->_parts) {
        $this->_parts = new Parts(" prt_item_id = ".$this->_id." ",null,null,null);
        $this->_parts->setAutoAdvance(false);
      }
      return $this->_parts;
    }

    /**
    * Get Item's Images unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for image object IDs for this item
    */
    public function getImageIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_image_ids);
      }else{
        return $this->idsStringToArray($this->_image_ids);
      }
    }

    /**
    * Get Item's images
    *
    * @return iterator that contains image objects of this item or NULL
    */
    public function getImages($autoExpand = false) {
      if (!$this->_images && $autoExpand && count($this->getImageIDs())>0) {
        $this->_images = new Images("img_id in (".join(",",$this->getImageIDs()).")",null,null,null);
        $this->_images->setAutoAdvance(false);
      }
      return $this->_images;
    }

    //********SETTERS*********
   /**
    * Sets the tilte for this Item
    * @param string $title
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_title",$title);
      }
      $this->_title = $title;
    }

   /**
    * Sets the description for this Item
    * @param string $description
    */
    public function setDescription($description) {
      if($this->_description != $description) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_description",$description);
      }
      $this->_description = $description;
    }

   /**
    * Sets the id reference for this Item
    * @param string $idno
    */
    public function setIdNo($idno) {
      if($this->_idno != $idno) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_idno",$idno);
      }
      $this->_idno = $idno;
    }

    /**
    * Set Type of the artefact
    *
    * @param string $type enumerate term from a typology of terms for artefacts
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set the measure of this Item
    *
    * @param string $measure for this item
    */
    public function setMeasure($measure) {
      if($this->_measure != $measure) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_measure",$measure);
      }
      $this->_measure = $measure;
    }

     /**
    * Set Item's Restoration State term unique ID
    *
    * @param int $shapeID term id for this item
    */
    public function setShapeID($shapeID) {
      if($this->_shape_id != $shapeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_shape_id",$shapeID);
      }
      $this->_shape_id = $shapeID;
    }

   /**
    * Set Item's Images unique IDs
    *
    * @param int array $imageIDs of image object IDs for this item
    */
    public function setImageIDs($imageIDs) {
      if($this->_image_ids != $imageIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("itm_image_ids",$this->idsToString($imageIDs));
      }
      $this->_image_ids = $imageIDs;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
