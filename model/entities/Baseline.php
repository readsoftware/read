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
  * Classes to deal with Baseline entities
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
  require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get database interface
  require_once (dirname(__FILE__) . '/Entity.php');
  require_once (dirname(__FILE__) . '/Image.php');
  require_once (dirname(__FILE__) . '/Surface.php');
  require_once (dirname(__FILE__) . '/Segments.php');

//*******************************************************************
//****************   BASELINE CLASS    **********************************
//*******************************************************************
  /**
  * Baseline represents baseline entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Baseline.php';
  *
  * $baseline = new Baseline( $resultRow );
  * echo $baseline->getType();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Baseline extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_image_id,
              $_image,
              $_image_pos,
              $_polygons,
              $_transcription,
              $_type_id,
              $_url,
              $_surface_id,
              $_surface,
              $_segments;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an baseline instance from an baseline table row
    * @param array $row associated with columns of the baseline table, a valid bln_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'baseline';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM baseline WHERE bln_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= bln_owner_id or ".getUserID()." = ANY (\"bln_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('bln_id',$row)) {
          error_log("unable to query for baseline ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['bln_id'] ? $arg['bln_id']:NULL;
        $this->_image_id=@$arg['bln_image_id'] ? $arg['bln_image_id']:NULL;
        $this->_image_pos=@$arg['bln_image_position'] ? $arg['bln_image_position']:NULL;
        $this->_transcription=@$arg['bln_transcription'] ? $arg['bln_transcription']:NULL;
        $this->_type_id=@$arg['bln_type_id'] ? $arg['bln_type_id']:NULL;
        $this->_surface_id=@$arg['bln_surface_id'] ? $arg['bln_surface_id']:NULL;
        if (!array_key_exists('bln_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('bln_image_position',$arg))$arg['bln_image_position'] = $this->polygonsToString($arg['bln_image_position']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new baseline to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "bln";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_image_id) {
        $this->_data['bln_image_id'] = $this->_image_id;
      }
      if (count($this->_image_pos)) {
        $this->_data['bln_image_position'] = $this->polygonsToString($this->_image_pos);
      }
      if (count($this->_transcription)) {
        $this->_data['bln_transcription'] = $this->_transcription;
      }
      if ($this->bln_type_id) {
        $this->_data['bln_type_id'] = $this->_type_id;
      }
      if ($this->_surface_id) {
        $this->_data['bln_surface_id'] = $this->_surface_id;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Expand all links into objects
    *
    * @param int $level determines how many level out where NULL or <1  is treated the same as 1
    */
    public function expandLinks( $level = 1 ) {
      //check each object array expansion slot before expanding as Getters can expand individually
      //for multilevel need to call expand on each linked object.
    }

    //********GETTERS*********

    /**
    * Get Baseline's Images unique IDs
    *
    * @return int array for image object IDs for this baseline
    */
    public function getImageID() {
      return $this->_image_id;
    }

    /**
    * Get Baseline's images
    *
    * @return iterator that contains image objects of this baseline or NULL
    */
    public function getImage($autoExpand = false) {
      if (!$this->_image && $autoExpand && is_numeric($this->_image_id)) {
        $this->_image = new Image(intval($this->_image_id));
      }
      return $this->_image;
    }

    /**
    * Get bounding box on image for this baseline
    *
    * @param boolean $cropped tells whether to generate a cropped image url or return the raw URL (default = false)
    * @return Polygon array|string|null which holds the boundary of the baseline within the image
    */
    public function getImageBoundary($asString = false) {
      if ($asString){
        return $this->polygonsToString($this->_image_pos);
      }else{
        return $this->polyStringToArray($this->_image_pos);
      }
    }

    /**
    * Gets the transcription for this Baseline
    * @return string $transcription
    */
    public function getTranscription() {
      return $this->_transcription;
    }

    /**
    * Get Type of the baseline
    *
    * @return string from a typology of terms for baselines
    */
    public function getType() {
      return $this->_type_id;
    }

    /**
    * Gets the cropped imageURL for this Baseline
    * @return string url
    */
    public function getURL() {
      if(!$this->_url
          && $this->getImage(true)){
        $image = $this->getImage(true);
        $boundary = is_Array($this->getImageBoundary())? $this->getImageBoundary():null;
        if($image->getBoundary()){
          $bBox = new BoundingBox($image->getBoundary(true));
          if($boundary){//adjust polygons to clipped image if image is bounded
            $shiftedBoundary = array();
            foreach($boundary as $polygon){
              $polygon->translate($bBox->getXOffset(),$bBox->getYOffset());
              array_push($shiftedBoundary,$polygon);
            }
            $boundary = $shiftedBoundary;
          }else{
            $boundary = $bBox;
          }
        }
        $this->_url = constructCroppedImageURL($image->getURL(),$boundary);
      }
      return $this->_url;
    }

    /**
    * Get Baseline's Surface unique ID
    *
    * @return int returns the primary Key for the surface of this baseline
    */
    public function getSurfaceID() {
      return $this->_surface_id;
    }

    /**
    * Get Baseline's Surface object
    *
    * @return Surface associated with this baseline or NULL
    */
    public function getSurface($autoExpand = false) {
      if (!$this->_surface && $autoExpand && is_numeric($this->_surface_id)) {
        $this->_surface = new Surface(intval($this->_surface_id));
      }
      return $this->_surface;
    }
    
    /**
    * Get number of segment objects attached to this baseline
    *
    * @return int count all segments linked to this baseline
    */
    public function getSegmentCount() {
      $dbMgr = new DBManager();
      $dbMgr->query("select count(seg_id) from segment where ".$this->_id." = ANY (\"seg_baseline_ids\") and not seg_owner_id = 1");
      if ($dbMgr->getRowCount()) {
        $row = $dbMgr->fetchResultRow();
        return $row[0];
      }
      return 0;
    }

    /**
    * Get number of segment objects attached to this baseline
    *
    * @return int[] array of segment ids for all segments linked to this baseline
    */
    public function getSegIDs() {
      $dbMgr = new DBManager();
      $dbMgr->query("select array_agg(seg_id) from segment where ".$this->_id." = ANY (\"seg_baseline_ids\") and not seg_owner_id = 1");
      if ($dbMgr->getRowCount()) {
        $row = $dbMgr->fetchResultRow();
        $segIDs = explode(',',trim($row[0],"\"{}"));
        if ($segIDs[0] == "" ) {
          return array();
        } 
        return $segIDs;
      }
      return array();
    }

    /**
    * Get segments object which contains all segments attached to this baseline
    *
    * @return Segments iterator with all segments linked to this baseline
    */
    public function getSegments() {
      if (!$this->_segments) {
        $condition = $this->_id." = ANY (\"seg_baseline_ids\") ";
        $this->_segments = new Segments($condition,null,null,null);
        $this->_segments->setAutoAdvance(false);
      }
      return $this->_segments;
    }

    //********SETTERS*********

    /**
    * Sets the transcription for this Baseline
    * @param string $transcription
    */
    public function setTranscription($transcription) {
      if($this->_transcription != $transcription) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("bln_transcription",$transcription);
      }
      $this->_transcription = $transcription;
    }

    /**
    * Set Baseline's image unique ID
    *
    * @param int $imgID the primary Key for the image of this baseline
    * @todo validate this image id
    */
    public function setImageID($imgID) {
      if($this->_image_id != $imgID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("bln_image_id",$imgID);
      }
      $this->_image_id = $imgID;
    }

    /**
    * Set Baseline's image boundary
    *
    * @param string $boundary of the form ((x1,y1),(x2,y2),....,(xn,yn)),(( , )...( , ))
    */
    public function setImageBoundary($boundary) {
      if($this->_image_pos != $boundary) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("bln_image_position",$this->polygonsToString($boundary));
      }
      $this->_image_pos = $boundary;
    }

    /**
    * Set Type of the baseline
    *
    * @param string $type enumerate term from a typology of terms for baseline
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("bln_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set Baseline's Surface's unique ID
    *
    * @param int $colID of the primary Key for the Surface of this baseline
    * @todo validate this Surface id
    */
    public function setSurfaceID($srfID) {
      if($this->_surface_id != $srfID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("bln_surface_id",$srfID);
      }
      $this->_surface_id = $srfID;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
