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
  * Classes to deal with Part entities
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
  require_once (dirname(__FILE__) . '/Item.php');
  require_once (dirname(__FILE__) . '/Images.php');
  require_once (dirname(__FILE__) . '/Fragments.php');

//*******************************************************************
//****************   PART CLASS    **********************************
//*******************************************************************
  /**
  * Part represents part entity which is metadata about a part of an artefact
  *
  * <code>
  * require_once 'Part.php';
  *
  * $part = new Part( $resultRow );
  * echo $Part->getType();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Part  extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_type_id,
              $_label,
              $_description,
              $_sequence,
              $_shape_id,
              $_mediums=array(),
              $_manufacture_id,
              $_measure,
              $_item_id,
              $_item,
              $_fragments,
              $_image_ids=array(),
              $_images;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create a Part instance from an part table row
    * @param array $row associated with columns of the part table, a valid prt_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'part';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM part WHERE prt_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= prt_owner_id or ".getUserID()." = ANY (\"prt_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('prt_id',$row)) {
          error_log("unable to query for part ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['prt_id'] ? $arg['prt_id']:NULL;
        $this->_type_id=@$arg['prt_type_id'] ? $arg['prt_type_id']:NULL;
        $this->_label=@$arg['prt_label'] ? $arg['prt_label']:NULL;
        $this->_title=@$arg['prt_description'] ? $arg['prt_description']:NULL;
        $this->_sequence=@$arg['prt_sequence'] ? $arg['prt_sequence']:NULL;
        $this->_shape_id=@$arg['prt_shape_id'] ? $arg['prt_shape_id']:NULL;
        $this->_mediums=@$arg['prt_mediums'] ? $arg['prt_mediums']:NULL;
        $this->_manufacture_id=@$arg['prt_manufacture_id'] ? $arg['prt_manufacture_id']:NULL;
        $this->_measure=@$arg['prt_measure'] ? $arg['prt_measure']:NULL;
        $this->_item_id=@$arg['prt_item_id'] ? $arg['prt_item_id']:NULL;
        $this->_image_ids=@$arg['prt_image_ids'] ? $arg['prt_image_ids']:NULL;
        if (!array_key_exists('prt_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('prt_mediums',$arg))$arg['prt_mediums'] = $this->enumsToString($arg['prt_mediums']);
          if (array_key_exists('prt_image_ids',$arg))$arg['prt_image_ids'] = $this->idsToString($arg['prt_image_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new part to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "prt";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_type_id) {
        $this->_data['prt_type_id'] = $this->_type_id;
      }
      if (count($this->_label)) {
        $this->_data['prt_label'] = $this->_label;
      }
      if (count($this->_description)) {
        $this->_data['prt_description'] = $this->_description;
      }
      if ($this->_sequence) {
        $this->_data['prt_sequence'] = $this->_sequence;
      }
      if ($this->_shape_id) {
        $this->_data['prt_shape_id'] = $this->_shape_id;
      }
      if (count($this->_mediums)) {
        $this->_data['prt_mediums'] = $this->enumsToString($this->_mediums);
      }
      if ($this->_manufacture_id) {
        $this->_data['prt_manufacture_id'] = $this->_manufacture_id;
      }
      if (count($this->_measure)) {
        $this->_data['prt_measure'] = $this->_measure;
      }
      if ($this->_item_id) {
        $this->_data['prt_item_id'] = $this->_item_id;
      }
      if (count($this->_image_ids)) {
        $this->_data['prt_image_ids'] = $this->idsToString($this->_image_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Expand all links into objects
    *
    * @param int $level determines how many level out where NULL or <1  is treated the same as 1
    * @todo implement expand for Part
    */
    public function expandLinks( $level = 1 ) {
      //check each object array expansion slot before expanding as Getters can expand individually
      //for multilevel need to call expand on each linked object.
    }

    //********GETTERS*********

    /**
    * Get Type of the artefact part
    *
    * @return int term id from a typology of terms for artefacts
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
    * Gets the label of this Part of the item
    * @return string label of part
    */
    public function getLabel() {
      return $this->_label;
    }

    /**
    * Gets the description for this Part
    * @return string $description
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get Sequence Number for this part of the artefact
    *
    * @return int specifiying the order in sequence of the part
    */
    public function getSequence() {
      return $this->_sequence;
    }

    /**
    * Get Shape Number for this part of the artefact
    *
    * @return string specifiying the shape of the part
    */
    public function getShape() {
      $shape = "unknown";
      if ($this->_shape_id && $this->getTermFromID($this->_shape_id)) {
        $shape = $this->_shape_id;
      } else if ($this->getScratchProperty('shape')) {
        $shape = $this->getScratchProperty('shape');
      }
      return $shape;
    }

    /**
    * Get Mediums or materials the part in made of
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string of enumerated terms describing material
    */
    public function getMediums($asString = false) {
      if ($asString){
        return $this->enumsToString($this->_mediums);
      }else{
        return $this->enumStringToArray($this->_mediums);
      }
    }

    /**
    * Get Part's Manufacture term unique ID
    *
    * @return int manufacture term id for this part
    */
    public function getManufactureID() {
      return $this->_manufacture_id;
    }

    /**
    * Gets the measure for this Part
    * @return string identifying the measure of this part
    */
    public function getMeasure() {
      return $this->_measure;
    }

    /**
    * Get Part's Item's unique ID
    *
    * @return int returns the primary Key for the item of this part
    */
    public function getItemID() {
      return $this->_item_id;
    }

    /**
    * Get Part's item object
    *
    * @return item that contains this part or NULL
    */
    public function getItem($autoExpand = false) {
      if (!$this->_item && $autoExpand && is_numeric($this->_item_id)) {
        $this->_item = new Item(intval($this->_item_id));
      }
      return $this->_item;
    }

     /**
    * Get Fragments object which contains all fragments attached to this part
    *
    * @return Fragments iterator with all fragments linked to this part
    */
    public function getFragments() {
      if (!$this->_fragments) {
        $this->_fragments = new Fragments(" frg_part_id = ".$this->_id." ",null,null,null);
        $this->_fragments->setAutoAdvance(false);
      }
      return $this->_fragments;
    }

    /**
    * Get Part's Images unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for image object IDs for this part
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
    * @return iterator that contains image objects of this part or NULL
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
    * Sets the type for this Part
    * @param string $type
    */
    public function setType($type) {
      //todo check type is valid  by query with value of enum table for type rownum = 0 is invalid
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set the label of this Part of the item
    *
    * @param string $label to identify a part
    * @todo add code to check duplicates
    */
    public function setLabel($label) {
      if($this->_label != $label) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_label",$label);
      }
      $this->_label = $label;
    }

   /**
    * Sets the description for this Part
    * @param string $description
    */
    public function setDescription($description) {
      if($this->_description != $description) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_description",$description);
      }
      $this->_description = $description;
    }

    /**
    * Set Sequence Number of the part
    *
    * @param int $seq number for orderring parts of an artifact
    */
    public function setSequence($seq) {
      //todo move other part sequence ??
      if($this->_sequence != $seq) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_sequence",$seq);
      }
      $this->_sequence = $seq;
    }

    /**
    * Set the shape of this part
    *
    * @param string $shape for this part
    */
    public function setShape($shape) {
      if($this->_shape_id != $shape) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_shape_id",$shape);
      }
      $this->_shape_id = $shape;
    }

    /**
    * Set the shape of this part
    *
    * @param string $shape for this part
    */
    public function setShapeID($shapeID) {
      if($this->_shape_id != $shapeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_shape_id",$shapeID);
      }
      $this->_shape_id = $shapeID;
    }

    /**
    * Set Mediums or materials the part in made of
    *
    * @param array $mediums - enumerated terms describing material
    * @todo add code to check all enum values are valid
    */
    public function setMediums($mediums) {
      if($this->_mediums != $mediums) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_mediums",$this->enumsToString($mediums));
      }
       $this->_mediums = $mediums;
    }

     /**
    * Set Part's Manufacture term unique ID
    *
    * @param int $manufactureID term id for this part
    */
    public function setManufactureID($manufactureID) {
      if($this->_manufacture_id != $manufactureID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_manufacture_id",$manufactureID);
      }
      $this->_manufacture_id = $manufactureID;
    }

    /**
    * Set the measure of this part
    *
    * @param string $measure for this part
    */
    public function setMeasure($measure) {
      if($this->_measure != $measure) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_measure",$measure);
      }
      $this->_measure = $measure;
    }

    /**
    * Set Part's link to its Item
    *
    * @param int $itmID of the primary Key for the item of this part
    */
    public function setItemID($itmID) {
      //todo check is valid item
      if($this->_item_id != $itmID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_item_id",$itmID);
      }
      $this->_item_id = $itmID;
    }

    /**
    * Set Part's Images unique IDs
    *
    * @param int array $imageIDs of image object IDs for this part
    */
    public function setImageIDs($imageIDs) {
      if($this->_image_ids != $imageIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("prt_image_ids",$this->idsToString($imageIDs));
      }
      $this->_image_ids = $imageIDs;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
