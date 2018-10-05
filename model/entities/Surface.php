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
  * Classes to deal with surface entities
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
  require_once (dirname(__FILE__) . '/Fragment.php');
  require_once (dirname(__FILE__) . '/Text.php');
  require_once (dirname(__FILE__) . '/Images.php');
  require_once (dirname(__FILE__) . '/Baselines.php');
//  require_once (dirname(__FILE__) . '/ReconstructedSurface.php');

//*******************************************************************
//****************   SURFACE CLASS    **********************************
//*******************************************************************
  /**
  * Surface represents surface entity which is metadata about a surface of a fragment
  *
  * <code>
  * require_once 'Surface.php';
  *
  * $surface = new Surface( $resultRow );
  * echo $surface->getDescription();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Surface  extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_fragment_id,
              $_fragment,
              $_number,
              $_label,
              $_description,
              $_layer_number,
              $_scripts=array(),
              $_text_ids,
              $_text,
              $_image_ids=array(),
              $_images,
              $_baselines,
              $_reconst_surface_id,
              $_reconst_surface;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create a Surface instance from a surface table row or null
    * @param array $row associated with columns of the surface table, a valid srf_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
     $this->_table_name = 'surface';
       if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT s.* FROM surface s JOIN fragment ON srf_fragment_id = frg_id WHERE srf_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= frg_owner_id or ".getUserID()." = ANY (\"frg_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('srf_id',$row)) {
          error_log("unable to query for fragment ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['srf_id'] ? $arg['srf_id']:NULL;
        $this->_fragment_id=@$arg['srf_fragment_id'] ? $arg['srf_fragment_id']:NULL;
        $this->_number=@$arg['srf_number'] ? $arg['srf_number']:NULL;
        $this->_label=@$arg['srf_label'] ? $arg['srf_label']:NULL;
        $this->_description=@$arg['srf_description'] ? $arg['srf_description']:NULL;
        $this->_layer_number=@$arg['srf_layer_number'] ? $arg['srf_layer_number']:NULL;
        $this->_scripts=@$arg['srf_scripts'] ? $arg['srf_scripts']:NULL;
        $this->_text_ids=@$arg['srf_text_ids'] ? $arg['srf_text_ids']:NULL;
        $this->_image_ids=@$arg['srf_image_ids'] ? $arg['srf_image_ids']:NULL;
        $this->_reconst_surface_id=@$arg['srf_reconst_surface_id'] ? $arg['srf_reconst_surface_id']:NULL;
       if (!array_key_exists('srf_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('srf_image_ids',$arg))$arg['srf_image_ids'] = $this->idsToString($arg['srf_image_ids']);
          if (array_key_exists('srf_scripts',$arg))$arg['srf_scripts'] = $this->enumsToString($arg['srf_scripts']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
       }
      //otherwise treat as new surface to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "srf";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_fragment_id) {
        $this->_data['srf_fragment_id'] = $this->_fragment_id;
      }
      if ($this->_number) {
        $this->_data['srf_number'] = $this->_number;
      }
      if (count($this->_label)) {
        $this->_data['srf_label'] = $this->_label;
      }
      if (count($this->_description)) {
        $this->_data['srf_description'] = $this->_description;
      }
      if ($this->_layer_number) {
        $this->_data['srf_layer_number'] = $this->_layer_number;
      }
      if (count($this->_scripts)) {
        $this->_data['srf_scripts'] = $this->enumsToString($this->_scripts);
      }
      if ($this->_text_ids) {
        $this->_data['srf_text_ids'] = $this->_text_ids;
      }
      if (count($this->_image_ids)) {
        $this->_data['srf_image_ids'] = $this->idsToString($this->_image_ids);
      }
      if ($this->_reconst_surface_id) {
        $this->_data['srf_reconst_surface_id'] = $this->_reconst_surface_id;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Expand all links into objects
    *
    * @param int $level determines how many level out where NULL or <1  is treated the same as 1
    * @todo implement expand for Surface
    */
    public function expandLinks( $level = 1 ) {
      //check each object array expansion slot before expanding as Getters can expand individually
      //for multilevel need to call expand on each linked object.
    }

    //********GETTERS*********

    /**
    * Get Surface's Fragment's unique ID
    *
    * @return int returns the primary Key for the fragment of this surface
    */
    public function getFragmentID() {
      return $this->_fragment_id;
    }

    /**
    * Get Surface's fragment object
    *
    * @return fragment that this surface belongs to or NULL
    * @todo create fragment object using ID
    */
    public function getFragment($autoExpand = false) {
      if (!$this->_fragment && $autoExpand && is_numeric($this->_fragment_id)) {
        $this->_fragment = new Fragment(intval($this->_fragment_id));
      }
      return $this->_fragment;
    }

    /**
    * Gets the number for this Surface
    * @return int number of surface
    */
    public function getNumber() {
      return $this->_number;
    }

    /**
    * Gets the label of the fragment's surface
    * @return string label of surface
    */
    public function getLabel() {
      return $this->_label;
    }

    /**
    * Get description of the fragment's surface
    *
    * @return string describing the surface
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Get layer number of this surface of the fragment
    *
    * @return int specifiying the layer number of the surface
    */
    public function getLayerNumber() {
      return $this->_layer_number;
    }

    /**
    * Get scripts existing on this surface of the fragment
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string of enum strings specifiying the location's reference number of the surface
    */
    public function getScripts($asString = false) {
      if ($asString){
        return $this->enumsToString($this->_scripts);
      }else{
        return $this->enumStringToArray($this->_scripts);
      }
    }

    /**
    * Gets the unigue IDs of the Text entities for this surface
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int identifying the Text of this surface
    */
    public function getTextIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_text_ids);
      }else{
        return $this->idsStringToArray($this->_text_ids);
      }
    }

    /**
    * Get Surface's text
    *
    * @return Text object that contains this surface or NULL
    */
    public function getText($autoExpand = false) {
      if (!$this->_text && $autoExpand && is_integer($this->_text_ids)) {
        $this->_text = new Text($this->_text_ids);
      }
      return $this->_text;
    }

    /**
    * Get Surface's Images unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array for image object IDs for this surface
    */
    public function getImageIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_image_ids);
      }else{
        return $this->idsStringToArray($this->_image_ids);
      }
    }

    /**
    * Get Surface's images
    *
    * @return Images iterator that contains image objects of this surface or NULL
    * @todo create images iterator object using IDs array
    */
    public function getImages($autoExpand = false) {
      if (!$this->_images && $autoExpand && count($this->getImageIDs())>0) {
        $this->_images = new Images("img_id in (".join(",",$this->getImageIDs()).")",null,null,null);
        $this->_images->setAutoAdvance(false);
      }
      return $this->_images;
    }

     /**
    * Get baselines object which contains all baselines attached to this surface
    *
    * @return Baselines iterator with all baselines linked to this surface
    */
    public function getBaselines() {
      if (!$this->_baselines) {
        $condition = "bln_surface_id = ".$this->_id;
        $this->_baselines = new Baselines($condition,null,null,null);
        $this->_baselines->setAutoAdvance(false);
      }
      return $this->_baselines;
    }

    /**
    * Get Surface's ReconstructedSurface unique ID
    *
    * @return int ReconstructedSurface object ID for this surface
    */
    public function getReconstSurfaceID() {
      return $this->_reconst_surface_id;
    }

    /**
    * Get Surface's ReconstructedSurface
    *
    * @return ReconstructedSurface object for this surface or NULL
    * @todo implement auto expansion
    */
    public function getReconstSurface($autoExpand = false) {
      if (!$this->_reconst_surface && $autoExpand) {
        // todo SAW implement auto expansion
      }
      return $this->_reconst_surface;
    }

    /**
    * Get Entity's Attributions unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return override entity always return null
    */
    public function getAttributionIDs($asString = false) {
      return null;
    }
    /**
    * Get Fragment's owner ID
    *
    * @return int returns the primary Key for owner of the Fragment of this Surface
    */
    public function getOwnerID() {
      $this->getFragment(true);
      if ($this->_fragment) {
        return $this->_fragment->getOwnerID();
      } else {
        $this->getText(true);
        if ($this->_text) {
          return $this->_text->getOwnerID();
        } else {
          return 3;
        }
      }
    }

    //********SETTERS*********

    /**
    * Sets the fragment id for this Surface
    * @param int $fragmentID
    * @todo check fragment ID is valid by query
    */
    public function setFragmentID($fragmentID) {
      //todo check fragment ID is valid by query
      if($this->_fragment_id != $fragmentID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_fragment_id",$fragmentID);
      }
      $this->_fragment_id = $fragmentID;
    }

    /**
    * Set the number of this Surface of the fragment
    *
    * @param int $number to identify a surface
    * @todo add code to check duplicates
    */
    public function setNumber($number) {
      if($this->_number != $number) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_number",$number);
      }
      $this->_number = $number;
    }

    /**
    * Set Label of the surface
    *
    * @param string $label uniquely identifying the surface relative to the fragment
    */
    public function setLabel($label) {
      if($this->_label != $label) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_label",$label);
      }
      $this->_label = $label;
    }

    /**
    * Set Description of the surface
    *
    * @param string $desc  describing the surface
    */
    public function setDescription($desc) {
      if($this->_description != $desc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_description",$desc);
      }
      $this->_description = $desc;
    }

    /**
    * Set the layer number of this Surface of the fragment
    *
    * @param int $layerNum to identify a surface layer
    * @todo add code to check duplicates
    */
    public function setLayerNumber($layerNum) {
      if($this->_layer_number != $layerNum) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_layer_number",$layerNum);
      }
      $this->_layer_number = $layerNum;
    }

    /**
    * Set the scripts of this Surface
    *
    * @param scripts array $scripts of enumeration terms to identify scripts used on a surface
    */
    public function setScripts($scripts) {
      if($this->_scripts != $scripts) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_scripts",$this->enumsToString($scripts));
      }
      $this->_scripts = $scripts;
    }

    /**
    * Sets the unigue IDs of the Text entities for this surface
    * @param int array $textIDs identifying the Texts of this surface
    */
    public function setTextIDs($textIDs) {
      if($this->_text_ids != $textIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_text_ids",$this->idsToString($textIDs));
      }
      $this->_text_ids = $textIDs;
    }

    /**
    * Set Surface's Images unique IDs
    *
    * @param int array $imageIDs of image object IDs for this surface
    */
    public function setImageIDs($imageIDs) {
      if($this->_image_ids != $imageIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_image_ids",$this->idsToString($imageIDs));
      }
      $this->_image_ids = $imageIDs;
    }

    /**
    * Set Surface's Reconstructed Surface unique ID
    *
    * @param int $reconSurfaceID of a Reconstructed Surface entity for this surface
    */
    public function setReconstSurfaceID($reconSurfaceID) {
      if($this->_reconst_surface_id != $reconSurfaceID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("srf_reconst_surface_id",$reconSurfaceID);
      }
      $this->_reconst_surface_id = $reconSurfaceID;
    }

   /**
    * Set Surface's Owner ID
    * no op for now there is no owner of a surface
    * @param int $ownerID of userGroup object
    */
    public function setOwnerID($ownerID) {
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
