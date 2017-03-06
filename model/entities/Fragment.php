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
  * Classes to deal with fragment entities
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
  require_once (dirname(__FILE__) . '/Surfaces.php');
  require_once (dirname(__FILE__) . '/Images.php');
  require_once (dirname(__FILE__) . '/MaterialContexts.php');

//*******************************************************************
//****************   FRAGMENT CLASS    **********************************
//*******************************************************************
  /**
  * Fragment represents fragment entity which is metadata about a piece of an artefact part
  *
  * <code>
  * require_once 'Fragment.php';
  *
  * $fragment = new Fragment( $resultRow );
  * echo $Fragment->getType();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Fragment extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_part_id,
              $_part,
              $_surfaces,
              $_description,
              $_location_refs,
              $_label,
              $_restore_state_id,
              $_measure,
              $_image_ids=array(),
              $_images,
              $_material_context_ids,
              $_material_contexts;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create a Fragment instance from a fragment table row or null
    * @param array $row associated with columns of the fragment table, a valid itm_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'fragment';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM fragment WHERE frg_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= frg_owner_id or ".getUserID()." = ANY (\"frg_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('frg_id',$row)) {
          error_log("unable to query for fragment ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['frg_id'] ? $arg['frg_id']:NULL;
        $this->_part_id=@$arg['frg_part_id'] ? $arg['frg_part_id']:NULL;
        $this->_description=@$arg['frg_description'] ? $arg['frg_description']:NULL;
        $this->_location_refs=@$arg['frg_location_refs'] ? $arg['frg_location_refs']:NULL;
        $this->_label=@$arg['frg_label'] ? $arg['frg_label']:NULL;
        $this->_restore_state_id=@$arg['frg_restore_state_id'] ? $arg['frg_restore_state_id']:NULL;
        $this->_measure=@$arg['frg_measure'] ? $arg['frg_measure']:NULL;
        $this->_image_ids=@$arg['frg_image_ids'] ? $arg['frg_image_ids']:NULL;
        $this->_material_context_ids=@$arg['frg_material_context_ids'] ? $arg['frg_material_context_ids']:NULL;
        if (!array_key_exists('frg_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('frg_location_refs',$arg))$arg['frg_location_refs'] = $this->stringsToString($arg['frg_location_refs']);
          if (array_key_exists('frg_material_context_ids',$arg))$arg['frg_material_context_ids'] = $this->idsToString($arg['frg_material_context_ids']);
          if (array_key_exists('frg_image_ids',$arg))$arg['frg_image_ids'] = $this->idsToString($arg['frg_image_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new fragment to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "frg";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_part_id) {
        $this->_data['frg_part_id'] = $this->_part_id;
      }
      if (count($this->_description)) {
        $this->_data['frg_description'] = $this->_description;
      }
      if (count($this->_location_refs)) {
        $this->_data['frg_location_refs'] = $this->stringsToString($this->_location_refs);
      }
      if (count($this->_label)) {
        $this->_data['frg_label'] = $this->_label;
      }
      if ($this->_restore_state_id) {
        $this->_data['frg_restore_state_id'] = $this->_restore_state_id;
      }
      if (count($this->_measure)) {
        $this->_data['frg_measure'] = $this->_measure;
      }
      if (count($this->_image_ids)) {
        $this->_data['frg_image_ids'] = $this->idsToString($this->_image_ids);
      }
      if (count($this->_material_context_ids)) {
        $this->_data['frg_material_context_ids'] = $this->idsToString($this->_material_context_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Expand all links into objects
    *
    * @param int $level determines how many level out where NULL or <1  is treated the same as 1
    * @todo implement expand for Fragment
    */
    public function expandLinks( $level = 1 ) {
      //check each object array expansion slot before expanding as Getters can expand individually
      //for multilevel need to call expand on each linked object.
    }

    //********GETTERS*********

    /**
    * Get Fragment's Part's unique ID
    *
    * @return int returns the primary Key for the part of this fragment
    */
    public function getPartID() {
      return $this->_part_id;
    }

    /**
    * Get Fragment's part object
    *
    * @return part that contains this fragment or NULL
    */
    public function getPart($autoExpand = false) {
      if (!$this->_part && $autoExpand && is_numeric($this->_part_id)) {
        $this->_part = new Part(intval($this->_part_id));
      }
      return $this->_part;
    }

     /**
    * Get Surfaces object which contains all surfaces attached to this fragment
    *
    * @return Surfaces iterator with all surfaces linked to this fragment
    */
    public function getSurfaces() {
      if (!$this->_surfaces) {
        $this->_surfaces = new Surfaces(" srf_fragment_id = ".$this->_id." ",null,null,null);
        $this->_surfaces->setAutoAdvance(false);
      }
      return $this->_surfaces;
    }

    /**
    * Get description of the artefact fragment
    *
    * @return string describing of the fragment
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get location reference identifiers of this fragment of the artefact
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array or string specifiying the location's reference numbers of the fragment
    */
    public function getLocationRefs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_location_refs);
      }else{
        return $this->stringOfStringsToArray($this->_location_refs);
      }
    }

    /**
    * Gets the label for this Fragment of the part
    * @return int label of fragment
    */
    public function getLabel() {
      return $this->_label;
    }

    /**
    * Get Fragment's Restoration State term unique ID
    *
    * @return int restoration state term id for this fragment
    */
    public function getRestoreStateID() {
      return $this->_restore_state_id;
    }

    /**
    * Gets the measure for this Fragment
    * @return string identifying the measure of this fragment
    */
    public function getMeasure() {
      return $this->_measure;
    }

    /**
    * Get Fragment's Images unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for image object IDs for this fragment
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
    * @return iterator that contains image objects of this fragment or NULL
    * @todo create images iterator object using IDs array
    */
    public function getImages($autoExpand = false) {//todo check that there isn't a case for expanded IDs not in existing iterator
      if (!$this->_images && $autoExpand && count($this->getImageIDs())>0) {
        $this->_images = new Images("img_id in (".join(",",$this->getImageIDs()).")",null,null,null);
        $this->_images->setAutoAdvance(false);
      }
      return $this->_images;
    }

    /**
    * Get Fragment's Material Context unique IDs
    *
    * @return int contextIDs of a material context entities for this fragment
    */
    public function getMaterialContextIDs() {
      return $this->_material_context_ids;
    }

    /**
    * Get Fragment's Material Contexts
    *
    * @return MaterialContexts for this fragment
    */
    public function getMaterialContexts($autoExpand = false) {
      if (!$this->_material_contexts && $autoExpand && count($this->getMaterialContextIDs())>0) {
        $this->_material_contexts = new MaterialContexts("mcx_id in (".join(",",$this->getMaterialContextIDs()).")",null,null,null);
        $this->_material_contexts->setAutoAdvance(false);
      }
      return $this->_material_contexts;
    }

    //********SETTERS*********
    /**
    * Sets the part id for this Fragment
    * @param int $partID
    * @todo check part ID is valid by query
    */
    public function setPartID($partID) {
      //todo check part ID is valid by query
      if($this->_part_id != $partID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_part_id",$partID);
      }
      $this->_part_id = $partID;
    }

    /**
    * Set Description of the fragment
    *
    * @param string $desc  describing the fragment
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set location reference of the fragment
    *
    * @param string array or string $locRefs with the place ids and the location's reference strings for the fragment
    */
    public function setLocationRefs($locRefs) {
      if($this->_location_refs != $locRefs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_location_refs",$this->stringsToString($locRefs));
      }
      $this->_location_refs = $locRefs;
    }

    /**
    * Set the label of this Fragment of the part
    *
    * @param int $label to identify a fragment
    * @todo add code to check duplicates
    */
    public function setLabel($label) {
      if($this->_label != $label) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_label",$label);
      }
      $this->_label = $label;
    }

    /**
    * Set Fragment's Restoration State term unique ID
    *
    * @param int restoration $stateID term id for this fragment
    */
    public function setRestoreStateID($stateID) {
      if($this->_restore_state_id != $stateID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_restore_state_id",$stateID);
      }
      $this->_restore_state_id = $stateID;
    }

    /**
    * Set the measure of this Fragment
    *
    * @param string $measure to identify a fragment
    */
    public function setMeasure($measure) {
      if($this->_measure != $measure) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_measure",$measure);
      }
      $this->_measure = $measure;
    }

    /**
    * Set Fragment's Images unique IDs
    *
    * @param int array $imageIDs of image object IDs for this fragment
    */
    public function setImageIDs($imageIDs) {
      if($this->_image_ids != $imageIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_image_ids",$this->idsToString($imageIDs));
      }
      $this->_image_ids = $imageIDs;
    }

    /**
    * Set Fragment's Material Context unique IDs
    *
    * @param int $contextIDs of a material context entity for this fragment
    */
    public function setMaterialContextIDs($contextIDs) {
      if($this->_material_context_ids != $contextIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("frg_material_context_ids",$this->idsToString($contextIDs));
      }
      $this->_material_context_ids = $contextIDs;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
