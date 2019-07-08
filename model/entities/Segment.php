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
  * Classes to deal with Segment entities
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
  require_once (dirname(__FILE__) . '/Spans.php');
  require_once (dirname(__FILE__) . '/Baselines.php');
  require_once (dirname(__FILE__) . '/Baseline.php');
  require_once (dirname(__FILE__) . '/Segments.php');
  require_once (dirname(__FILE__) . '/SyllableClusters.php');

//*******************************************************************
//****************   SEGMENT CLASS  *********************************
//*******************************************************************
  /**
  * Segment represents segment entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Segment.php';
  *
  * $segment = new Segment( $resultRow );
  * echo "segment has layer # ".$segment->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Segment extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_baseline_ids = array(),
              $_baselines,
              $_spans,
              $_syllable_ids,
              $_syllables,
              $_image_pos,
              $_center_pos,
              $_polygons,
              $_string_pos = array(),
              $_layer,
              $_rotation,
              $_urls,
              $_clarity_id,
              $_obscurations = array(),
              $_mapped_seg_ids = array(),
              $_mapped_segs;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an segment instance from an segment table row
    * @param array $row associated with columns of the segment table, a valid seg_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'segment';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('seg_id',$row)) {
          error_log("unable to query for segment ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['seg_id'] ? $arg['seg_id']:NULL;
        $this->_baseline_ids=@$arg['seg_baseline_ids'] ? $arg['seg_baseline_ids']:NULL;
        $this->_image_pos=@$arg['seg_image_pos'] ? $arg['seg_image_pos']:NULL;
        $this->_string_pos=@$arg['seg_string_pos'] ? $arg['seg_string_pos']:NULL;
        $this->_layer=@$arg['seg_layer'] ? $arg['seg_layer']:NULL;
        $this->_rotation=@$arg['seg_rotation'] ? $arg['seg_rotation']:NULL;
        $this->_urls=@$arg['seg_url'] ? $arg['seg_url']:NULL;
        $this->_clarity_id=@$arg['seg_clarity_id'] ? $arg['seg_clarity_id']:NULL;
        $this->_obscurations=@$arg['seg_obscurations'] ? $arg['seg_obscurations']:NULL;
        $this->_mapped_seg_ids=@$arg['seg_mapped_seg_ids'] ? $arg['seg_mapped_seg_ids']:NULL;
        if (!array_key_exists('seg_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('seg_obscurations',$arg))$arg['seg_obscurations'] = $this->enumsToString($arg['seg_obscurations']);
          if (array_key_exists('seg_image_pos',$arg))$arg['seg_image_pos'] = $this->polygonsToString($arg['seg_image_pos']);
          if (array_key_exists('seg_string_pos',$arg))$arg['seg_string_pos'] = $this->arraysOfIdsToString($arg['seg_string_pos']);
          if (array_key_exists('seg_baseline_ids',$arg))$arg['seg_baseline_ids'] = $this->idsToString($arg['seg_baseline_ids']);
          if (array_key_exists('seg_mapped_seg_ids',$arg))$arg['seg_mapped_seg_ids'] = $this->idsToString($arg['seg_mapped_seg_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new segment to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "seg";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_baseline_ids)) {
        $this->_data['seg_baseline_ids'] = $this->idsToString($this->_baseline_ids);
      }
      if (count($this->_image_pos)) {
        $this->_data['seg_image_pos'] = $this->polygonsToString($this->_image_pos);
      }
      if (count($this->_center_pos)) {
        $this->_data['seg_center_pos'] = $this->idsToString($this->_center_pos);
      }
      if (count($this->_string_pos)) {
        $this->_data['seg_string_pos'] = $this->arraysOfIdsToString($this->_string_pos);
      }
      if ($this->_layer) {
        $this->_data['seg_layer'] = $this->_layer;
      }
      if ($this->_rotation) {
        $this->_data['seg_rotation'] = $this->_rotation;
      }
      if (count($this->_urls)) {
        $this->_data['seg_url'] = $this->_urls;
      }
      if ($this->_clarity) {
        $this->_data['seg_clarity_id'] = $this->_clarity;
      }
      if (count($this->_obscurations)) {
        $this->_data['seg_obscurations'] = $this->enumsToString($this->_obscurations);
      }
      if (count($this->_mapped_seg_ids)) {
        $this->_data['seg_mapped_seg_ids'] = $this->idsToString($this->_mapped_seg_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Calculate centroid for this segment
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateCenter(){
      $this->_center_pos = null;
      $boundary = null;
      if ($this->_image_pos) {
        $boundary = $this->polyStringToArray($this->_image_pos);
      }
      $cnt = 0;
      if ($boundary) {
        $cnt = count($boundary);
      }
      if ( $cnt > 0) {//proper polygon calc center
        $center_x = $center_y = 0;
        foreach ($boundary as $polygon){
          $center = $polygon->getCenter();
          $center_x += $center[0];
          $center_y += $center[1];
        }
        $this->_center_pos = array(round($center_x/$cnt),round($center_y/$cnt));
      }
    }

    /**
    * Saves the segments' image(s) to the IMAGE_SEGMENT_CACHE dir
    */
    public function cacheSegmentImages($urls = null){
      if (!$urls) {
        $urls = $this->getURLs(true);
      }
      if ($urls) {
        $segFilenameBase = SEGMENT_CACHE_BASE_PATH.DBNAME."seg".$this->getID();
        $urlCnt = 0;
        foreach ($urls as $url) {
          $urlCnt++;
          $bytesSaved = null;
          $segFilename = $segFilenameBase.($urlCnt>1?"_part$urlCnt":"").".png";
          $urlContent = loadURLContent($url,true);
          if ($urlContent) {
            $bytesSaved = file_put_contents($segFilename,$urlContent);
          }
          if (!$bytesSaved) {
            array_push($this->_errors,"error caching segment image for $segFilename");
          }
        }
      }
    }

    //********GETTERS*********

    /**
    * Get Segment's baseline unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array that contains baseline IDs of this segme
    */
    public function getBaselineIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_baseline_ids);
      }else{
        return $this->idsStringToArray($this->_baseline_ids);
      }
    }

    /**
    * Get Segment's baseline objects
    *
    * @return baseline iterator for the baselines of this segment or NULL
    */
    public function getBaselines($autoExpand = false) {
      if (!$this->_baselines && $autoExpand && count($this->getBaselineIDs())>0) {
        $this->_baselines = new Baselines("bln_id in (".join(",",$this->getBaselineIDs()).")",null,null,null);
        $this->_baselines->setAutoAdvance(false);
      }
      return $this->_baselines;
    }

     /**
    * Get Spans object which contains all spans attached to this segment
    *
    * @return Spans iterator with all spans linked to this segment
    */
    public function getSpans() {
      if (!$this->_spans && $this->_id) {
        $condition = $this->_id." = ANY (\"spn_segment_ids\") ";
        $this->_spans = new Spans($condition,null,null,null);
        $this->_spans->setAutoAdvance(false);
      }
      return $this->_spans;
    }

     /**
    * Get SyllableIDs returns the IDs of all syllable attached to this segment
    *
    * @return int array of Syllable IDs of all syllables linked to this segment
    */
    public function getSyllableIDs($reFetch = false) {
      if ((!$this->_syllable_ids || count($this->_syllable_ids) == 0 || $reFetch) && $this->_id) {
        $condition = $this->_id." = scl_segment_id ";
        $dbMgr = new DBManager();
        $dbMgr->query("select scl_id from syllablecluster where scl_segment_id = ".$this->_id);
        if ($dbMgr->getRowCount()) {
          $this->_syllable_ids = array();
          while ($row = $dbMgr->fetchResultRow()) {
            array_push($this->_syllable_ids, $row[0]);
          }
        } else {
          $this->_syllable_ids = null;
        }
      }
      return $this->_syllable_ids;
    }

     /**
    * Get Syllables object which contains all syllables attached to this segment
    *
    * @return Syllables iterator with all syllables linked to this segment
    */
    public function getSyllables() {
      if (!$this->_syllables && $this->_id) {
        $condition = $this->_id." = scl_segment_id ";
        $this->_syllables = new SyllableClusters($condition,null,null,null);
        $this->_syllables->setAutoAdvance(false);
      }
      return $this->_syllables;
    }

    /**
    * Get boundary polygon on baseline for this segment
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return Polygons array|string|null which describes the boundary of the segment within the image
    */
    public function getImageBoundary($asString = false) {
      if ($asString){
        return $this->polygonsToString($this->_image_pos);
      }else{
        return $this->polyStringToArray($this->_image_pos);
      }
    }

    /**
    * Get Segment's center point
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of x,y coordinate for the center of this segment
    */
    public function getCenter($asString = false) {
      if ( !$this->_center_pos) {
        $this->calculateCenter();
      }
      if (!$this->_center_pos) {
        return null;
      }
      if ($asString){
        return $this->idsToString($this->_center_pos);
      }else{
        return $this->idsStringToArray($this->_center_pos);
      }
    }

    /**
    * Get character offsets for transcription baseline for this segment
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return array of int array|string of character positions which describe the range of characters for this segment
    */
    public function getStringPos($asString = false) {
      if ($asString){
        return $this->arraysOfIdsToString($this->_string_pos);
      }else{
        return $this->arraysOfIdsStringToArray($this->_string_pos);
      }
    }

    /**
    * Gets the layer number for this Segment
    * @return int layer number
    */
    public function getLayer() {
      return $this->_layer;
    }

    /**
    * Gets the rotation for this Segment
    * @return int rotation in degrees
    */
    public function getRotation() {
      return $this->_rotation;
    }

    /**
    * Gets the URLs for this Segment
    * @return string array of urls
    */
    public function getURLs($getCached = true) {//todo handle split segment case where there are 2 baselines and 2 boundaries.
      if(!$this->_urls
            && !$this->_string_pos // not a transcription segment
            && $this->getBaselines(true) //has baseline(s)
            && $this->_baselines->valid() // that are valid
            && $this->_baselines->current()->getType() == $this->getIDofTermParentLabel("image-baselinetype")) {//term dependency //'Image' ){  FIX THIS! lookup Image Type ID
        //get the boundary for this segment
        $boundary = $this->getImageBoundary();
        $urls = array();
        $this->_baselines->rewind();
        if ($boundary) {
          $polyCnt = 0;
          $segFilenameBase = SEGMENT_CACHE_BASE_PATH.DBNAME."seg".$this->getID();
          $segCacheBaseURL = SEGMENT_CACHE_BASE_URL."/".DBNAME."seg".$this->getID();
          $urlCnt = 0;
          $reCalcCache = false;
          foreach ($boundary as $polygon){// this code assumes baseline IDs is a parallel array with polygons
            $urlCnt++;
            if ((!defined("USESEGMENTCACHING") || USESEGMENTCACHING) && $getCached) { //try cached first
              $segFilename = $segFilenameBase.($urlCnt>1?"_part$urlCnt":"").".png";
              if (file_exists($segFilename)) {
                array_push($urls,$segCacheBaseURL.($urlCnt>1?"_part$urlCnt":"").".png");
                $this->_baselines->next();
                continue;
              }
            }
            if (count($urls) == 0){
              $baseline = $this->_baselines->current();
              //get the baseline's image object
              $image = $baseline->getImage(true);
              // if baseline has a boundary then translate segment points to the origin of the baseline bounding box
              if($baseline->getImageBoundary()){
                $bBox = new BoundingBox($baseline->getImageBoundary());
                $polygon->translate($bBox->getXOffset(),$bBox->getYOffset());
              }
              // if the image has a boundary then translate segment points to the origin of the image bounding box
              if($image->getBoundary()){
                $bBox = new BoundingBox($image->getBoundary());
                $polygon->translate($bBox->getXOffset(),$bBox->getYOffset());
              }
              array_push($urls,constructCroppedImageURL($image->getURL(),$polygon));
              $this->_baselines->next();
              $reCalcCache = true;
            }
          }// end for boundary
        }
        if (count($urls) > 0) {
          $this->_urls = $urls;
          if ((!defined("USESEGMENTCACHING") || USESEGMENTCACHING) && $reCalcCache) {
            $this->cacheSegmentImages($urls);
          }
        }
        $this->_baselines->rewind();
      }
      return $this->_urls;
    }

    /**
    * Get Clarity of the segment
    *
    * @return string from a typology of terms for clarity of a segment
    */
    public function getClarity() {
      return $this->_clarity_id;
    }

    /**
    * Get Clarity id of the segment
    *
    * @return int id from a typology of terms for clarity of a segment
    */
    public function getClarityID() {
      return $this->_clarity_id;
    }

    /**
    * Get Obscurations of the segment
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string from a typology of terms for obscurations for segments
    */
    public function getObscurations($asString = false) {
      if ($asString){
        return $this->enumsToString($this->_obscurations);
      }else{
        return $this->enumStringToArray($this->_obscurations);
      }
    }

    /**
    * Get Segment's MappedSegment unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of the primary Key for the segments mapped to this segment
    */
    public function getMappedSegmentIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_mapped_seg_ids);
      }else{
        return $this->idsStringToArray($this->_mapped_seg_ids);
      }
    }

    /**
    * Get Segment's Mapped Segemnt objects
    *
    * @return Segemnts interator for all mapped segments or NULL
    */
    public function getMappedSegment($autoExpand = false) {
      if (!$this->_mapped_segs && $autoExpand && count($this->getMappedSegmentIDs())>0) {
        $this->_mapped_segs = new Segments("seg_id in (".join(",",$this->getMappedSegmentIDs()).")",null,null,null);
        $this->_mapped_segs->setAutoAdvance(false);
      }
      return $this->_mapped_segs;
    }

    //********SETTERS*********
    
    /**
    * Set Segment's baseline unique IDs
    *
    * @param int array that contains baseline IDs of this segment
    */
    public function setBaselineIDs($ids) {
      if($this->_baseline_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_baseline_ids",$this->idsToString($ids));
      }
      $this->_baseline_ids = $ids;
    }

    /**
    * Set Segment's image bounding box
    *
    * @param string $boundary of the form ((x1,y1),(x2,y2),....,(xn,yn)),(( , )...( , ))
    */
    public function setImageBoundary($boundary) {
      if($this->polygonsToString($this->_image_pos) != $this->polygonsToString($boundary)) {
        $this->_dirty = true;
        $this->_image_pos = $boundary;
        $this->calculateCenter();
        $this->setDataKeyValuePair("seg_image_pos",$this->polygonsToString($boundary));
      }
    }

    /**
    * Set Segment's character offsets for transcription string
    *
    * @param int array $offsets character offsets for transcription string for this segment
    */
    public function setStringPos($offsets) {
      if($this->_string_pos != $offsets) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_string_pos",$this->arraysOfIdsToString($offsets));
      }
      $this->_string_pos = $offsets;
    }

    /**
    * Sets the layer number for this Segment
    * @param int $layer layer number
    */
    public function setLayer($layer) {
      if($this->_layer != $layer) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_layer",$layer);
      }
      $this->_layer = $layer;
    }

    /**
    * Sets the rotation for this Segment
    * @param int $rot rotation in degrees
    */
    public function setRotation($rot) {
      if($this->_rotation != $rot) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_rotation",$rot);
      }
      $this->_rotation = $rot;
    }

    /**
    * Sets the URL for this Segment
    * @return string $url
    */
    public function setURL($url) {
      if($this->_url != $url) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_url",$url);
      }
      $this->_url = $url;
    }

    /**
    * Set Clarity ID of the segment
    *
    * @param string $clarityID from a typology of terms for clarity of a segment
    */
    public function setClarityID($clarityID) {
      if($this->_clarity_id!= $clarityID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_clarity_id",$clarityID);
      }
      $this->_clarity_id = $clarityID;
    }

    /**
    * Set Obscurations of the segment
    *
    * @param string array $obstructs from a typology of terms for obscurations for segments
    */
    public function setObscurations($obstructs) {
      if($this->_obscurations != $obstructs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_obscurations",$this->enumsToString($obstructs));
      }
      $this->_obscurations = $obstructs;
    }

    /**
    * Set Segment's MappedSegment unique IDs
    *
    * @param int array of the primary Key for the segments mapped to this segment
    */
    public function setMappedSegmentIDs($ids) {
      if($this->_mapped_seg_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("seg_mapped_seg_ids",$this->idsToString($ids));
      }
      $this->_mapped_seg_ids = $ids;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
