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
* Utility functions
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Utility Classes
*/


require_once (dirname(__FILE__) . '/userAccess.php');
require_once (dirname(__FILE__) . '/../../config.php');//get defines
require_once (dirname(__FILE__) . '/DBManager.php');//get database interface
require_once dirname(__FILE__) . '/../../model/entities/Terms.php';
require_once dirname(__FILE__) . '/../../model/entities/Entity.php';
// add required for switchInfo
require_once dirname(__FILE__) . '/../../model/entities/SyllableClusters.php';
require_once dirname(__FILE__) . '/../../model/entities/Tokens.php';
require_once dirname(__FILE__) . '/../../model/entities/Compounds.php';
require_once dirname(__FILE__) . '/../../model/entities/Inflections.php';
require_once dirname(__FILE__) . '/../../model/entities/Lemmas.php';
require_once dirname(__FILE__) . '/../../model/entities/Editions.php';
require_once dirname(__FILE__) . '/../../model/entities/Texts.php';
require_once dirname(__FILE__) . '/../../model/entities/Sequences.php';
require_once dirname(__FILE__) . '/../../model/entities/JsonCache.php';
require_once dirname(__FILE__) . '/../../model/entities/UserGroups.php';
require_once dirname(__FILE__) . '/../../model/entities/AttributionGroup.php';
require_once dirname(__FILE__) . '/../../model/entities/Attribution.php';
include_once dirname(__FILE__) . '/../../model/entities/Annotation.php';
include_once dirname(__FILE__) . '/../../model/entities/Bibliography.php';
include_once dirname(__FILE__) . '/../../model/entities/Baselines.php';
include_once dirname(__FILE__) . '/../../model/entities/Fragments.php';
include_once dirname(__FILE__) . '/../../model/entities/Items.php';
include_once dirname(__FILE__) . '/../../model/entities/Images.php';
include_once dirname(__FILE__) . '/../../model/entities/MaterialContexts.php';
include_once dirname(__FILE__) . '/../../model/entities/Runs.php';
include_once dirname(__FILE__) . '/../../model/entities/Parts.php';
include_once dirname(__FILE__) . '/../../model/entities/Surfaces.php';
include_once dirname(__FILE__) . '/../../model/entities/Texts.php';
include_once dirname(__FILE__) . '/../../model/utility/parser.php';

/**
* Polygon encapsulates a set of vertices defining a closed path
*
* <code>
* require_once 'utils.php';
*
* $polygon = new Polygon("((255,78),(304,115),(275,90))");
* $polygon->getBoundingBox();
* echo $bBox->getXOffset();
* </code>
*
* @author Stephen White  <stephenawhite57@gmail.com>
*/

class Polygon implements JsonSerializable{

  //*******************************PRIVATE MEMBERS************************************

  /**
  * private member variables
  * @access private
  */
  private   $_points,
  $_center;


  //****************************CONSTRUCTOR FUNCTION***************************************

  /**
  * Create a polygon instance from a string of points with each point in the form of (x,y)
  * @param string|null with each point in the form of (x,y) or null
  * @access public
  */
  public function __construct( $points = null ) {
    if (is_string($points)) {// this
      preg_match_all("/\((\d+),(\d+)\)/",$points,$match);
      $cnt = count($match[0]);
      if ($cnt > 2) {
        $this->_points = array();
        $center_x = $center_y = 0;
        for($i=0;$i<$cnt;$i++){ // go through all points
          $x = intval($match[1][$i]);
          $y =  intval($match[2][$i]);
          $center_x += $x;
          $center_y += $y;
          $v = array($x,$y);
          array_push($this->_points,$v);
        }
        $this->_center = array(round($center_x/$cnt),round($center_y/$cnt));
      }
    }
  }

  //*******************************PUBLIC FUNCTIONS************************************

  /**
  * Serialize Polygon to json
  *
  * @return array of members for serialization
  */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {

    return $this->_points;
  }

  /**
  * Translate the polygon
  *
  * @param int $x shift in x direction
  * @param int $y shift in y direction
  */
  public function translate( $x = 0, $y = 0 ) {
    $this->_points = getTranslatedPoly($this->_points,$x,$y);
  }

  /**
  * Check that the polygon is valid.
  *
  * @return true|false  returns true if offsets are set and width and height are non zero has an Entity or false if not
  */
  public function valid() {
    return isset($this->_points) && count($this->_points) > 2;
  }

  //********GETTERS*********

  /**
  * Get bounding rectangle
  * @return int array  of point for the bounding rectangle of this polygon
  */
  public function getBoundingRect() {
    return getBoundingRect($this->_points);
  }

  /**
  * Get point
  * @return int array of points for this polygon
  */
  public function getPoints() {
    return $this->_points;
  }

  /**
  * Get center point
  * @return int array of x,y center point for this polygon
  */
  public function getCenter() {
    return $this->_center;
  }

  /**
  * Get points as a string ((x1,y1),(x2,y2),....,(xn,yn))
  * @return string representing the vertices of this polygon
  */
  public function getPolygonString() {
    $str = "(";
    $cnt = count($this->_points);
    for($i=0;$i<$cnt;$i++){ // go through all points and output (x,y) for each
      if ($i) {
        $str .= ",";
      }
      $str .= "(".$this->_points[$i][0].",".$this->_points[$i][1].")";
    }
    return $str.")";
  }

  /**
  * Get points as a Json string [[x1,y1],[x2,y2],....,[xn,yn]]
  * @return string representing the vertices of this polygon
  */
  public function getPolygonJson() {
    $str = "[";
    $cnt = count($this->_points);
    for($i=0;$i<$cnt;$i++){ // go through all points and output [x,y] for each
      if($i){
        $str .= ",";//separate the points
      }
      $str .= "[".$this->_points[$i][0].",".$this->_points[$i][1]."]";
    }
    return $str."]";
  }

  //********SETTERS*********

  /**
  * Sets the points for this polygon
  * @param int  array $points
  */
  public function setPoints($points) {
    $this->_points = $points;
  }


  //*******************************PRIVATE FUNCTIONS************************************

}


/**
* Bounding Box encapsulates boundaries for images
*
* <code>
* require_once 'utils.php';
*
* $bBox = new BoundingBox("(255,78),(304,115)");
* $bBox->translate(20,50);
* echo $bBox->getXOffset();
* </code>
*
* @author Stephen White  <stephenawhite57@gmail.com>
*/

class BoundingBox{

  //*******************************PRIVATE MEMBERS************************************

  /**
  * private member variables
  * @access private
  */
  private   $_offsetx,
  $_offsety,
  $_width,
  $_height;


  //****************************CONSTRUCTOR FUNCTION***************************************

  /**
  * Create a bounding box instance from a string of points with each point in the form of (x,y)
  * @param string|null with each point in the form of (x,y) or null
  * @access public
  * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
  */
  public function __construct( $points = null ) {
    if (is_string($points)) {// this is an ID so need to query the db
      preg_match_all("/\((\d+),(\d+)\)/",$points,$match);
      $cnt = count($match[0]);
      if ($cnt) {
        $x1 = $y1 = 1000000;
        $x2 = $y2 = 0;
        for($i=0;$i<$cnt;$i++){ // go through all points and find min and max
          $x = intval($match[1][$i]);
          $y = intval($match[2][$i]);
          $x1 = min($x1, $x);
          $x2 = max($x2, $x);
          $y1 = min($y1, $y);
          $y2 = max($y2, $y);
        }
      }
      if ($cnt == 1){//case where single point so assume this point is lowerLeft and use 0,0 as upperRight
        $x1 = $y1 = 0;
      }
      $this->_offsetx = $x1;
      $this->_offsety = $y1;
      $this->_width = $x2 - $x1;
      $this->_height = $y2 - $y1;
    }
  }

  //*******************************PUBLIC FUNCTIONS************************************

  /**
  * Translate the bounding box
  *
  * @param int $x shift in x direction
  * @param int $y shift in y direction
  */
  public function translate( $x = 0, $y = 0 ) {
    $this->_offsetx += $x;
    $this->_offsety += $y;
  }

  /**
  * Check that the bounding box is a box.
  *
  * @return true|false  returns true if offsets are set and width and height are non zero has an Entity or false if not
  */
  public function valid() {
    return isset($this->_offsetx) &&
    isset($this->_offsety) &&
    isset($this->_width) && is_int($this->_width) && $this->_width > 0 &&
    isset($this->_height) && is_int($this->_height) && $this->_height > 0;
  }

  //********GETTERS*********

  /**
  * Get the X offset for this bounding box
  * @return int x offset
  */
  public function getXOffset() {
    return $this->_offsetx;
  }

  /**
  * Get the Y offset for this bounding box
  * @return int y offset
  */
  public function getYOffset() {
    return $this->_offsety;
  }

  /**
  * Get the width for this bounding box
  * @return int width
  */
  public function getWidth() {
    return $this->_width;
  }

  /**
  * Get the Height for this bounding box
  * @return int height
  */
  public function getHeight() {
    return $this->_height;
  }

  /**
  * Get points for this bounding box
  * @return string points
  */
  public function getPoints() {
    return "{'(".$this->_offsetx.",".$this->_offsety.")','(".($this->_offsetx + $this->_width).",".($this->_offsety + $this->_height).")'}";
  }

  /**
  * Get points as a string ((x1,y1),(x2,y2),....,(xn,yn))
  * @return string representing the vertices of this polygon
  */
  public function getPolygonString() {
    return "((".$this->_offsetx.",".$this->_offsety."),(".
               ($this->_offsetx + $this->_width).",".$this->_offsety."),(".
               ($this->_offsetx + $this->_width).",".($this->_offsety + $this->_height)."),(".
               $this->_offsetx.",".($this->_offsety + $this->_height)."))";
  }

  //********SETTERS*********

  /**
  * Sets the X offset for this bounding box
  * @param int $x offset
  */
  public function setXOffset($x) {
    $this->_offsetx = $x;
  }

  /**
  * Sets the Y offset for this bounding box
  * @param int $y offset
  */
  public function setYOffset($y) {
    $this->_offsety = $y;
  }

  /**
  * Sets the width for this bounding box
  * @param int $w width
  */
  public function setWidth($w) {
    $this->_width = $w;
  }

  /**
  * Sets the Height for this bounding box
  * @param int $h height
  */
  public function setHeight($h) {
    $this->_height = $h;
  }


  //*******************************PRIVATE FUNCTIONS************************************

}

/**
* Construct a URL to crop image given a point array postgres string of points
*
* @param string $imageURL a URL for the image to be cropped
* @param BoundingBox|Polygon $boundary
* @return string returns cropping URL service call or the $imageURL
*/
function constructCroppedImageURL($imageURL, $boundary) {
  if ($boundary){
    $url = CROP_IMAGE_SERVICE_PATH."?db=".DBNAME."&url=$imageURL";
    if (is_a($boundary,'BoundingBox') && $boundary->valid()){
      $url .= "&x=".$boundary->getXOffset().
      "&y=".$boundary->getYOffset().
      "&w=".$boundary->getWidth().
      "&h=".$boundary->getHeight();
      return $url;
    }else if (is_a($boundary,'Polygon') && $boundary->valid()){
      $url .= "&polygons=[".$boundary->getPolygonJson()."]";
      return $url;
    }else if (is_array($boundary) && is_a($boundary[0],'Polygon')){
      $url .= "&polygons=".json_encode($boundary);
      return $url;
    }
  }
  return $imageURL;
}

/**
* calculate translated polygon given the new origin x,y
*
* @param int array $points of the form [x1,y1,x2,y2,...,xn,yn] or [[x1,y1],[x2,y2],...,[xn,yn]]
* @param int $newOrigX
* @param int $newOrigX
* @return int array of points for the translated polygon
*/
function getTranslatedPoly($points,$newOrigX, $newOrigY, $forceSerial = false) {
  $cnt = count($points); // find number of points
  if(!$cnt || (!$newOrigX && !$newOrigY)) return $points;
  if ( is_array($points[0]) && count($points[0]) === 2) {
    $format = 'tuples';
  }else{
    $format = 'serial';
  }
  $poly = array();
  for($i=0;$i<$cnt;$i++){
    if($format == 'serial') {
      array_push($poly,($points[$i]+$newOrigX), ($points[$i+1]+$newOrigY));
      $i++;
    }else if($format == 'tuples' && $forceSerial) {//return seral format
      array_push($poly,($points[$i][0]+$newOrigX), ($points[$i][1]+$newOrigY));
    }else{
      array_push($poly,array(($points[$i][0]+$newOrigX), ($points[$i][1]+$newOrigY)));
    }
  }
  return $poly;
}

/**
* calculate center of a polygon set of points and return array of int x y
*
* @param int array $points of the form [x1,y1,x2,y2,...,xn,yn] or [[x1,y1],[x2,y2],...,[xn,yn]]
* @return NULL|array of int x y
*/
function getPolygonCenter($points) {
  $cnt = count($points); // find number of points
  if(!$cnt || $cnt < 3 || !is_array($points)) return null;
  $center_x = $center_y = 0;
  if ( is_array($points[0]) && count($points[0]) === 2) {//tuples
    for($i=0;$i<$cnt;$i++){
      $center_x += $points[$i][0];
      $center_y += $points[$i][1];
    }
  }else{
    for($i=0;$i<$cnt;){
      $center_x += $points[$i];
      $center_y += $points[$i+1];
      $i +=2;
    }
  }
  return array(round($center_x/$cnt),round($center_y/$cnt));
}

/**
* calculate bound rect for array of points
*
* @param int array $points of the form [x1,y1,x2,y2,...,xn,yn] or [[x1,y1],[x2,y2],...,[xn,yn]]
* @return NULL|array of int points starting from upper left to right to lower right to left
*/
function getBoundingRect($points) {
  $cnt = count($points); // find number of points
  if(!$cnt) return null;
  $x1 = $y1 = 10000000;//upper left
  $x2 = $y2 = 0; // lower
  if ( is_array($points[0]) && count($points[0]) === 2) {//tuples
    for($i=0;$i<$cnt;$i++){
      $x1 = min($x1,$points[$i][0]);
      $x2 = max($x2,$points[$i][0]);
      $y1 = min($y1,$points[$i][1]);
      $y2 = max($y2,$points[$i][1]);
    }
  }else{
    for($i=0;$i<$cnt;){
      $x1 = min($x1,$points[$i]);
      $x2 = max($x2,$points[$i]);
      $y1 = min($y1,$points[$i+1]);
      $y2 = max($y2,$points[$i+1]);
      $i +=2;
    }
  }
  return array($x1,$y1,$x2,$y1,$x2,$y2,$x1,$y2);
}

/**
* converts an array of $points of the form [x1,y1,x2,y2,...,xn,yn] to [[x1,y1],[x2,y2],...,[xn,yn]]
*
* @param int array $points of the form [x1,y1,x2,y2,...,xn,yn]
* @return NULL|array of int tuples (points) of the form [[x1,y1],[x2,y2],...,[xn,yn]]
*/
function pointsArray2ArrayOfTuples($points) {
  $cnt = count($points); // find number of points
  if(!$cnt) return null;
  if ( is_array($points) ) {
    if ( is_array($points[0]) && count($points[0]) === 2) {//tuples
      return $points;
    }else if ((count($points)%2)===0){//even number
      $tuples = array();
      for($i=0;$i<$cnt;){
        array_push($tuples,array($points[$i],$points[$i+1]));
        $i +=2;
      }
      return $tuples;
    }
  }
  return null;
}


/**
* download image from given url
*
* @param mixed $url
* @return resource|null
*/
function loadURLContent($url,$raw = false) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //return the output as a string from curl_exec
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  curl_setopt($ch, CURLOPT_NOBODY, 0);
  curl_setopt($ch, CURLOPT_HEADER, 0);  //don't include header in output
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  // follow server header redirects
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // don't verify peer cert
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );  // http authenticate
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // timeout after ten seconds
  curl_setopt($ch, CURLOPT_MAXREDIRS, 5);  // no more than 5 redirections

  if (preg_match("/http/",$url)) {
    curl_setopt($ch, CURLOPT_URL, $url);
  } else if (preg_match("/https/",$url)) {
    curl_setopt($ch, CURLOPT_URL, $url);
  } else {
    curl_setopt($ch, CURLOPT_URL, SITE_ROOT.$url);
  }
  $data = curl_exec($ch);

  $error = curl_error($ch);
  if ($error) {
    $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    error_log("$error ($code)" . " url = ". $url);
    curl_close($ch);
    return false;
  } else if (!$data || preg_match("/401 Unauthorized/",$data)) {
    curl_close($ch);
    if (preg_match("/130\.223\.29\.184/",$data) || !$data) {
      $path = preg_replace("/^.*images/",DOCUMENT_ROOT."/images",$url);
      $data = file_get_contents($path);
      if($data){
        if ($raw) {
          return $data;
        } else {
          $img = imagecreatefromstring($data);
          return $img;
        }
      }
    }
  } else {
    curl_close($ch);
    if($data){
      if ($raw) {
        return $data;
      } else {
        $img = imagecreatefromstring($data);
        return $img;
      }
    }
    return null;
  }
}

/**
* create term lookup tables for system.
*
* lookups include:
* lookup term label by term id
* lookup term entity by term id
* lookup term code by term id
* lookup term parent term id by term id
* lookup foriegn key type by term id
* lookup automation type by term id
* lookup UI type by term id
*
* @param mixed $langCode
*/

function getTermInfoForLangCode($langCode = 'en'){
  static $termInfos = array();
  if (array_key_exists($langCode,$termInfos)) {
    return $termInfos[$langCode];
  }
  $enumLables = array('SystemList','ContentList','EntityList','List-Single','List-Multiple','List-MultipleOrdered');
  $termInfo = array();
  $dbMgr = new DBManager();
  if ($dbMgr->getError()) {
    return null;
  }
  $dbMgr->query("SELECT trm_id, trm_labels::hstore->'$langCode' as trm_label, trm_labels, trm_list_ids, trm_code, trm_parent_id FROM term;");
  $rows = $dbMgr->fetchAllResultRows();
  if ($rows && count($rows) > 0) {
    $termInfo = array('idByTerm_ParentLabel' => array(),
                      'labelByID' => array(),
                      'termByID' => array(),
                      'codeByID' => array(),
                      'parentIDByID' => array(),
                      'enumTypeIDs' => array(),
                      'fkTypeIDs' => array(),
                      'automationTypeIDs' => array(),
                      'uiAssistTypeIDs' => array());
      foreach($rows as $row) { 
//    while($row = $dbMgr->fetchResultRow()){
      $termInfo['labelByID'][$row['trm_id']] = $row['trm_label'];
      $termInfo['termByID'][$row['trm_id']] = $row;
      if ($row['trm_code']) {
        $termInfo['codeByID'][$row['trm_id']] = $row['trm_code'];
      }
      $termInfo['parentIDByID'][$row['trm_id']] = $row['trm_parent_id']?$row['trm_parent_id']:"";
      if (in_array($row['trm_label'], $enumLables)) {
        $termInfo['enumTypeIDs'][$row['trm_id']] = 1;
      }
      if (strpos($row['trm_label'],'FK-') === 0) {
        $subtype = array();
        if(strpos($row['trm_label'],'Hom')) {
          $subtype['ho'] = 1;
        }else  if(strpos($row['trm_label'],'Het')) {
          $subtype['he'] = 1;
        }else{
          $subtype['pr'] = 1;
        }
        if(strpos($row['trm_label'],'Multi')) {
          $subtype['mu'] = 1;
        }else{
          $subtype['si'] = 1;
        }
        if(strpos($row['trm_label'],'Ord')) {
          $subtype['ord'] = 1;
        }
        $termInfo['fkTypeIDs'][$row['trm_id']] = $subtype;
      }
      if (strpos($row['trm_label'],'Automation') === 0) {
        $termInfo['automationTypeIDs'][$row['trm_id']] = 1;
      }
      if (strpos($row['trm_label'],'(UI)') === 0) {
        $termInfo['uiAssistTypeIDs'][$row['trm_id']] = 1;
      }
    }
    foreach ($termInfo['parentIDByID'] as $trmID => $pTrmID) {
      $termInfo['idByTerm_ParentLabel'][mb_strtolower($termInfo['labelByID'][$trmID].($pTrmID?'-'.$termInfo['labelByID'][$pTrmID]:''),'utf-8')] = $trmID;
    }
    $termInfos[$langCode] = $termInfo;
  }
  return $termInfo;
}

/**
* get UserGroup id for the "Marked for Delete" group
*/

function getMarkedForDeleteUgrpID() {
  static $userIDMarkedForDelete;
  if (!$userIDMarkedForDelete){
    $dbMgr = new DBManager();
    $dbMgr->query("SELECT ugr_id FROM usergroup where ugr_family_name = 'Marked for Delete' limit 1");
    $row = $dbMgr->fetchResultRow();
    $userIDMarkedForDelete = $row['ugr_id'];
  }
  return $userIDMarkedForDelete;
}

/**
* get the statis UserGroup lookup table mapping UserGroup id to givenName,familyName and fullName
* @static
*/

function getUserLookup() {
  static $userIDToInfoLookup;
  if (!$userIDToInfoLookup){
    $userIDToInfoLookup = array();
    $dbMgr = new DBManager();
    $dbMgr->query("SELECT ugr_id, ugr_given_name, ugr_family_name, concat(ugr_given_name,' ', ugr_family_name) as ugr_fullname".
      " FROM usergroup where ugr_given_name is not null or ugr_family_name is not null");
    while($row = $dbMgr->fetchResultRow()){
      $key = $row['ugr_id'];
      if (!array_key_exists($key,$userIDToInfoLookup)) {
        $userIDToInfoLookup[$key] = array( "givenName"=>$row['ugr_given_name'],
          "familyName"=>$row['ugr_family_name'],
          "fullName"=>$row['ugr_fullname']);
      }
    }
  }
  return $userIDToInfoLookup;
}


/**
* @global mixed[] $entTagToLabel A lookup table mapping tagType's term tag ('trm' + ID) to the term label
*/
$entTagToLabel = array();

/**
* @global mixed[] $entTagToPath A lookup table mapping tagType's term tag ('trm' + ID) to
*         a hierachical path string
*/
$entTagToPath = array();

/**
* @global mixed[] $tagIDToAnoID A lookup table mapping tagType's term tag ('trm' + ID) to
*         it's annotation instance id
*/
$tagIDToAnoID = array();

/**
* get the tag type hierarchical info structure for the current database term taxonomy
*
* @global mixed[] $entTagToLabel
* @global mixed[] $entTagToPath
* @global mixed[] $tagIDToAnoID
*
* @return mixed[] $tagsInfo of tagInfoStructures with label, path and annotation instance mappings
*/

function getTagsInfo() {
  global $entTagToLabel,$tagIDToAnoID,$entTagToPath;
  $tagsInfo = null;
  $annoTypeTerms = new Terms("trm_labels::hstore->'en' = 'AnnotationType'",null,null);
  if ($annoTypeTerms && $annoTypeTerms->getCount() > 0 ){
    $annoTagTerms = new Terms("trm_labels::hstore->'en' = 'TagType' AND trm_parent_id = ". $annoTypeTerms->current()->getID());
    if ($annoTagTerms && $annoTagTerms->getCount() > 0 ){
      $tagTrmID = $annoTagTerms->current()->getID();
      $tagsStruct = getSubTagsStructure($tagTrmID);
      if (count($tagsStruct) > 0) {
        $tagsInfo = array ("tags"=>$tagsStruct);
        if (count($entTagToLabel) > 0) {
          $tagsInfo["entTagToLabel"]=$entTagToLabel;
        }
        if (count($entTagToPath) > 0) {
          $tagsInfo["entTagToPath"]=$entTagToPath;
        }
        if (count($tagIDToAnoID) > 0) {
          $tagsInfo["tagIDToAnoID"]=$tagIDToAnoID;
        }
      }
    }
  }
  return $tagsInfo;
}

/**
* @global mixed[] $parentTermsWithNumericSubterms A lookup table mapping labels of Terms with numeric
*   sub terms to tag ('trm' + ID) to a prefix
*/
$parentTermsWithNumericSubterms = array("BaseType"=>"BT-","FootMarkType"=>"FMT-","VowelType"=>"VT-");

/**
* get the tag type hierarchical info structure for the current database term taxonomy
*
* @global mixed[] $entTagToLabel
* @global mixed[] $entTagToPath
* @global mixed[] $tagIDToAnoID
* @global mixed[] $parentTermsWithNumericSubterms
*
* @param int $trmID Term id
* @param string $ctxPos A list of index values used to navigate the tagtree
* @param string $preLabel A prefix for to apply to a set of terms
* @param boolean $numericSort Identifies whether term labels should be treated a numbers for sort (false)
*
* @return mixed[] $subTagsInfo of label,id,value,items for a given type
*/

function getSubTagsStructure($trmID, $ctxPos = "",$preLabel="",$numericSort = false) {
  global $entTagToLabel,$entTagToPath,$tagIDToAnoID,$parentTermsWithNumericSubterms;
  $subTagsInfo = null;
  //get all subTagTerms ordered alphabetically
  $annoSubTagTerms = new Terms("trm_parent_id = $trmID",($numericSort?"cast(trm_labels::hstore->'en' as integer)":"trm_labels::hstore->'en'"),null,null);
  if ($annoSubTagTerms && $annoSubTagTerms->getCount() > 0 ){
    $subTagsInfo = array();
    $index = 0;
    foreach($annoSubTagTerms as $tagTerm) {
      $tagID = $tagTerm->getID();
      $tagLabel = $tagTerm->getLabel();
      $entTagToLabel["trm".$tagID] = $preLabel.$tagLabel;
      $trmPos = $ctxPos?$ctxPos.";$index":"$index"; // string of indices that locate this tag in the tagtree
      $entTagToPath["trm".$tagID] = $trmPos;
      $tagInfo = array( "label" => $preLabel.$tagLabel,
        "id" => 'trm'.$tagID);
      if ($tagLabel == "CustomType") {
        //get custom anno records for sub
        $items = getCustomTags($tagID);
      } else {
        //get value GID
        $tagInfo['value'] = getTagGIDValue($tagID);
        if (strpos($tagInfo['value'],"ano")!== false){
          $tagIDToAnoID[$tagInfo['id']] = substr($tagInfo['value'],4);
        }
        //get subTags
        if (array_key_exists($tagLabel,$parentTermsWithNumericSubterms)) {
          $items = getSubTagsStructure($tagID,$trmPos,$parentTermsWithNumericSubterms[$tagLabel], true);
        } else {
          $items = getSubTagsStructure($tagID,$trmPos);
        }
      }
      if ($items) {
        $tagInfo['items'] = $items;
      }
      array_push($subTagsInfo,$tagInfo);
      $index++;
    }
  }
  return $subTagsInfo;
}

$linkTypeTagToLabel = array();
$linkTypeTagToList = array();

/**
* get the linkage type hierarchical info structure for the current database term taxonomy
*
* @global mixed[] $linkTypeTagToLabel
* @global mixed[] $linkTypeTagToList
*
* @return mixed[] $linkTypeInfo of label,id,value,items,expanded for a given type
*/

function getLinkTypeInfo() {
  global $linkTypeTagToLabel,$linkTypeTagToList;
  $linkTypeInfo = null;
  $linkTypeTerms = new Terms("trm_labels::hstore->'en' = 'LemmaLinkage'",null,null,null);
  if ($linkTypeTerms && $linkTypeTerms->getCount() > 0 ){
    $linkTypeID = $linkTypeTerms->current()->getID();
    $linkTypesStruct = getLinkSubTypeStructure($linkTypeID);
    if (count($linkTypesStruct) > 0) {
      $linkTypeInfo = array ("types"=>$linkTypesStruct);
      if (count($linkTypeTagToLabel) > 0) {
        $linkTypeInfo["linkTypeTagToLabel"]=$linkTypeTagToLabel;
      }
      if (count($linkTypeTagToList) > 0) {
        $linkTypeInfo["linkTypeTagToList"]=$linkTypeTagToList;
      }
    }
  }
  $sfLinkTypeTerms = new Terms("trm_labels::hstore->'en' = 'SyntacticFunction'",'trm_id',null,null);
  if ($sfLinkTypeTerms && $sfLinkTypeTerms->getCount() > 0 ){
    $sfLinkTypeID = $sfLinkTypeTerms->current()->getID();
    $sfLinkTypesStruct = getLinkSubTypeStructure($sfLinkTypeID);
    if (count($sfLinkTypesStruct) > 0) {
      if (!$linkTypeInfo) {
        $linkTypeInfo = array ("sftypes"=>$sfLinkTypesStruct);
      } else {
        $linkTypeInfo["sftypes"] = $sfLinkTypesStruct;
      }
      if (count($linkTypeTagToLabel) > 0) {
        $linkTypeInfo["linkTypeTagToLabel"]=$linkTypeTagToLabel;
      }
      if (count($linkTypeTagToList) > 0) {
        $linkTypeInfo["linkTypeTagToList"]=$linkTypeTagToList;
      }
    }
  }
  return $linkTypeInfo;
}


/**
* validate child term is containable by parent term
*
* @param int $pTypeID Term id of container
* @param int $cTypeID Term id of item
* @return boolean
*/

function isValidContainment($pTypeID, $cTypeID) {
  if ($pTypeID && $cTypeID && $pTypeID != $cTypeID){
    $pTermList = Entity::getTermList($pTypeID);
    if (isset($pTermList) &&
        is_array($pTermList) &&
        in_array($cTypeID,$pTermList) ) {
      return true;
    }
  }
  return false;
}


/**
* validate child term is decendant of parent term
*
* @param int $pTypeID Term id
* @param int $cTypeID Term id
* @param int $maxDepth indicates a limit for the search (default 8 levels)
* @return boolean
*/

function isSubTerm($pTypeID, $cTypeID, $maxDepth = 8) {
  if ($pTypeID && $cTypeID && $pTypeID != $cTypeID){
    $pTermID = $cTypeID;
    while ($maxDepth--) {
      $pTermID = Entity::getParentIDFromID($pTermID);
      if ($pTermID == $pTypeID) {
        return true;
      }
    }
  }
  return false;
}


/**
* get child term Term Iterator given a Term id
*
* @param int $trmID Term id
* @param boolean $sortByLabel Indicates sorting by label or by default (term_id)
* @return Terms
*/

function getChildTerms($trmID,$sortByLabel = false) {
  $childTerms = null;
  $childTerms = new Terms("trm_parent_id = $trmID",($sortByLabel?"trm_labels::hstore->'en'":null),null,null);
  if ($childTerms && $childTerms->getCount() > 0 ){
    $childTerms->setAutoAdvance(false);
    return $childTerms;
  }
  return null;
}

/**
* @global mixed[] $linkTypeTagToLabel A lookup table mapping linkType term tag ('trm' + ID) to the term label
*/
$linkTypeTagToLabel = array();

/**
* @global mixed[] $linkTypeTagToList A lookup table mapping linkType term tag ('trm' + ID) to the term list
*/
$linkTypeTagToList = array();

/**
* get the linkage type hierarchical info structure for the current database term taxonomy
*
* @param int $trmID Term id
*
* @global mixed[] $linkTypeTagToLabel
* @global mixed[] $linkTypeTagToList
*
* @return mixed[] $subLinkTypeInfo of label,id,value,items,expanded for a given type
*/

function getLinkSubTypeStructure($trmID) {
  global $linkTypeTagToLabel,$linkTypeTagToList;
  $subLinkTypeInfo = null;
  //get all linkTypeSubTerms ordered alphabetically
  $seqSubTypeTerms = new Terms("trm_parent_id = $trmID","trm_labels::hstore->'en'",null,null);
  if ($seqSubTypeTerms && $seqSubTypeTerms->getCount() > 0 ){
    $subLinkTypeInfo = array();
    foreach($seqSubTypeTerms as $linkTypeTerm) {
      $typeID = $linkTypeTerm->getID();
      $typeLabel = $linkTypeTerm->getLabel();
      $linkTypeTagToLabel["trm".$typeID] = $typeLabel;
      $termList = $linkTypeTerm->getListIDs();
      if ($termList) {
        $linkTypeTagToList["trm".$typeID] = $termList;
      }
      $typeInfo = array( "label" => $typeLabel,
        "value" => $typeID,
        "id" => 'trm'.$typeID);
      //get subTags
      $items = getSeqSubTypeStructure($typeID);
      if ($items) {
        $typeInfo['items'] = $items;
        $typeInfo['expanded'] = false;
      }
      array_push($subLinkTypeInfo,$typeInfo);
    }
  }
  return $subLinkTypeInfo;
}
/**
* @global mixed[] $seqTypeTagToLabel A lookup table mapping seqType term tag ('trm' + ID) to the term label
*/
$seqTypeTagToLabel = array();

/**
* @global mixed[] $seqTypeTagToList A lookup table mapping seqType term tag ('trm' + ID) to the term list
*/
$seqTypeTagToList = array();

/**
* get the sequence type hierarchical info structure for the current database term taxonomy
*
* @global mixed[] $seqTypeTagToLabel
* @global mixed[] $seqTypeTagToList
*
* @return mixed[] $seqTypeInfo of label,id,value,items,expanded for a given type
*/

function getSeqTypeInfo() {
  global $seqTypeTagToLabel,$seqTypeTagToList;
  $seqTypeInfo = null;
  $seqTypeTerms = new Terms("trm_labels::hstore->'en' = 'SequenceType'",null,null);
  if ($seqTypeTerms && $seqTypeTerms->getCount() > 0 ){
    $seqTypeID = $seqTypeTerms->current()->getID();
    $seqTypesStruct = getSeqSubTypeStructure($seqTypeID);
    if (count($seqTypesStruct) > 0) {
      $seqTypeInfo = array ("types"=>$seqTypesStruct);
      if (count($seqTypeTagToLabel) > 0) {
        $seqTypeInfo["seqTypeTagToLabel"]=$seqTypeTagToLabel;
      }
      if (count($seqTypeTagToList) > 0) {
        $seqTypeInfo["seqTypeTagToList"]=$seqTypeTagToList;
      }
    }
  }
  return $seqTypeInfo;
}

/**
* get hierachical structure type info for a given sequence type term id
*
* @global mixed[] $seqTypeTagToLabel
* @global mixed[] $seqTypeTagToList
*
* @param int $trmID sequence type Term id
*
* @return object array $seqSubTypeStructInfo of label,id,value,items,expanded for a given type
*/

function getSeqSubTypeStructure($trmID) {
  global $seqTypeTagToLabel,$seqTypeTagToList;
  $subSeqTypeInfo = null;
  //get all seqTypeSubTerms ordered alphabetically
  $seqSubTypeTerms = new Terms("trm_parent_id = $trmID","trm_labels::hstore->'en'",null,null);
  if ($seqSubTypeTerms && $seqSubTypeTerms->getCount() > 0 ){
    $subSeqTypeInfo = array();
    foreach($seqSubTypeTerms as $seqTypeTerm) {
      $typeLabel = $seqTypeTerm->getLabel();
      if ($typeLabel == "Text" || $typeLabel == "TextPhysical") {// warning!!! term dependecny
        continue;
      }
      $typeID = $seqTypeTerm->getID();
      $seqTypeTagToLabel["trm".$typeID] = $typeLabel;
      $termList = $seqTypeTerm->getListIDs();
      if ($termList) {
        $seqTypeTagToList["trm".$typeID] = $termList;
      }
      $typeInfo = array( "label" => $typeLabel,
        "value" => $typeID,
        "id" => 'trm'.$typeID);
      //get subTags
      $items = getSeqSubTypeStructure($typeID);
      if ($items) {
        $typeInfo['items'] = $items;
        $typeInfo['expanded'] = true;
      }
      array_push($subSeqTypeInfo,$typeInfo);
    }
  }
  return $subSeqTypeInfo;
}

/**
* item structures for custom tags created by the current user
*
* @param int $trmID of custom trm
* @param string $ctxPos identifying the position of tag in tree ( @deprecated )
*
* @global $entTagToLabel,$entTagToPath,$tagIDToAnoID
*
* @return object array $subTagsInfo of label,id,value structures for each custom tag
*/

function getCustomTags($trmID,$ctxPos = "") {
  global $entTagToLabel,$entTagToPath,$tagIDToAnoID;
  $annoRepresentations = new Annotations("ano_type_id = $trmID and ano_owner_id = ".getUserDefEditorID(),"ano_text",null,null);
  if ($annoRepresentations && $annoRepresentations->getCount() > 0 ){
    $subTagsInfo = array();
    $index=0;
    foreach($annoRepresentations as $tagAnno) {
      $anoGID = $tagAnno->getGlobalID();
      $trmPos = $ctxPos."$index"; // string of indices that locate this tag in the tagtree
      $anoTag = str_replace(":","",$anoGID);
      $entTagToPath[$anoTag] = $trmPos;
      $tagLabel= $tagAnno->getText();
      $entTagToLabel["ano".$tagAnno->getID()] = $tagLabel;
      $tagInfo = array( "label" => $tagLabel,
        "id" => $anoTag,
        "value" => $anoGID);
      array_push($subTagsInfo,$tagInfo);
      $tagIDToAnoID[$tagInfo['id']] = substr($tagInfo['value'],4);
    }
    return $subTagsInfo;
  } else {
    return null;
  }
}

/**
* get GID representation for a given tag termID for the current user
*
* @param int $trmID tag term id
*
* @todo add code to handle multiple tag representations from different group memberships
*/

function getTagGIDValue($trmID) {
  $annoRepresentations = new Annotations("ano_type_id = $trmID and not ano_owner_id in (2,3,6)".
                                          " and not 5 = ANY(ano_visibility_ids)".
                                          " and ano_owner_id in (".join(",",getUserMembership()).")",
                                          "ano_text",null,null);
  if ($annoRepresentations && $annoRepresentations->getCount() > 0 ) {
    return "ano:".$annoRepresentations->current()->getID();
  } else {
    return "trm:".$trmID;
  }
}

/**
* depth first search of compound containment tree for a given GID
*
* @param string $pGID start/containing Global entity id
* @param string $cGID child/search for Global entity id
*
* @return int array $ctx of Compound ids representing the from-to containment
*/

function getCmpContext($pGID,$cGID) {
  if ($pGID == $cGID) {
    return true;//positive terminate recursion for found compound or token to return containment context
  }
  list($pPrefix,$pID) = explode(":",$pGID);
  if ($pPrefix == "cmp") {
    $compound = new Compound($pID);
    foreach($compound->getComponentIDs() as $gid) {
      if ($ctx = getCmpContext($gid,$cGID)) {
        if (is_array($ctx)) {
          array_push($ctx, $pID);
          return $ctx;
        } else {
          return array($pID);
        }
      }
    }
  }
  return false;//negative terminate recursion for tok
}

/**
* get syllable ids for a given entity
*
* @param string(3) $prefix identifying the entity type 'scl', 'tok' or 'cmp'
* @param int $id entity identifier
* @param bool $refresh by requery for syllableClusters
*
* @return int array $sclIDs of SyllableCluster entity ids for a given entity
*/

function getEntitySclIDs($prefix,$id,$refresh = true) { //todo: make SclIDs refresh default configurable
  $sclIDs = null;
  if ($prefix == 'scl') {
    $sclIDs = array($id);
  } else if ($prefix == 'tok') {
    $token = new Token($id);
    $sclIDs = $token->getSyllableClusterIDs(false,$refresh);
  } else if ($prefix == 'cmp') {
    $compound = new Compound($id);
    $cmpTokens = $compound->getTokens();
    $sclIDs = array();
    foreach ($cmpTokens as $token) {
      $sclIDs = array_merge($sclIDs,$token->getSyllableClusterIDs(false,$refresh));
    }
  }
  return $sclIDs;
}

/**
* get text, textphysical and analysis sequences for a given edition entity
*
* @param object $edition entity
*
* @return Sequence array $sequences ($seqText, $seqPhysicalText, $seqAnalysis) the given edition entity
*/

function getOrderedEditionSequences($edition) {
  $seqPhysicalText = null;
  $seqText = null;
  $seqAnalysis = null;
  $edSeqs = $edition->getSequences(true);
  foreach ($edSeqs as $edSequence) {
    $seqType = $edSequence->getType();
    if (!$seqPhysicalText && $seqType == "TextPhysical"){ //warning!!!! term dependency
      $seqPhysicalText = $edSequence;
    }
    if (!$seqText && $seqType == "Text"){ //warning!!!! term dependency
      $seqText = $edSequence;
    }
    if (!$seqAnalysis && $seqType == "Analysis"){ //warning!!!! term dependency
      $seqAnalysis = $edSequence;
    }
  }
  return array($seqText,$seqPhysicalText,$seqAnalysis);
}

/**
* get morphology for word entity sequences for a given edition entity
*
* @param object $word entity is either a compound or token
*
* @return structure of morphological information POS:???, MORPH: ? ? ?
*/

function getWordMorphology($word, $catID = null) {
  // find inf and lem from catID
  $prefix = $word->getEntityTypeCode();
  $table = $word->getEntityType();
  $id = $word->getID();

  $inflQuery = "
      select concat('$prefix',$prefix"."_id) as id, $prefix"."_value as value, inf_certainty as icert,
        inf_case_id as caseid, inf_nominal_gender_id as ngenid, inf_gram_number_id as gnumid,
        inf_verb_person_id as personid, inf_verb_voice_id as voiceid,inf_verb_tense_id as tenseid,
        inf_verb_mood_id as moodid, inf_verb_second_conj_id as conjid,lem_id,lem_part_of_speech_id as posid,
        lem_subpart_of_speech_id as sposid, lem_nominal_gender_id as genid, lem_certainty as lcert
      from $table 
        left join inflection on concat('$prefix:',$prefix"."_id) = ANY(inf_component_ids)
        left join lemma on concat('inf:',inf_id) = ANY(lem_component_ids)
      where $prefix"."_id = $id and inf_id is not null".($catID?" and lem_catalog_id = ".$catID:"").";";

  $unInflQuery = "
      select concat('$prefix',$prefix"."_id) as id, $prefix"."_value as value,
        lem_id,lem_part_of_speech_id as posid,
        lem_subpart_of_speech_id as sposid, lem_nominal_gender_id as genid, lem_certainty as lcert
      from $table 
        left join lemma on concat('$prefix:',$prefix"."_id) = ANY(lem_component_ids)
      where $prefix"."_id = $id".($catID?" and lem_catalog_id = ".$catID:"").";";

  $pos = "???";
  $morph = "? ? ?";
  $isVerb = false;
  $processInfl = false;
  $dbMgr = new DBManager();
  $dbMgr->query($inflQuery);
  if ($dbMgr->getError()) {
    error_log("error querying morphology word GID ".$word->getGlobalID()." error: ".$dbMgr->getError());
    $row = null;
  } else if ($dbMgr->getRowCount() >= 1) { //found an inflection
    $row = $dbMgr->fetchResultRow();
    $processInfl = true;
  } else { //no inflection found so try uninflected case
    $dbMgr->query($unInflQuery);
    if ($dbMgr->getError()) {
      error_log("error querying morphology word GID ".$word->getGlobalID()." error: ".$dbMgr->getError());
      $row = null;
    } else if ($dbMgr->getRowCount() >= 1) { //found a lemma
      $row = $dbMgr->fetchResultRow();
    }
  }
  if ($row && $row['posid']) {
    //build lemma pos
    $posID = $row['posid'];
    $sposID = $row['sposid'];
    $genid = $row['genid'];
    $lCert = $row['lcert'];//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
    $pos = $posID && is_numeric($posID) && Entity::getTermFromID($posID) ? Entity::getTermFromID($posID) : '';
    $isVerb = null;
    if ($pos) {
      $isVerb = ($pos == 'v.');
    }
    $sPos = $sposID && is_numeric($sposID) && Entity::getTermFromID($sposID) ? Entity::getTermFromID($sposID) : '';
    $lGen = $sposID && is_numeric($sposID) && Entity::getTermFromID($sposID) ? Entity::getTermFromID($sposID) : '';
    if ($lGen ) {//warning Order Dependency for display code lemma gender (like for nouns) hides subPOS hides POS
      $pos =  $lGen.($lCert[2]==2?'(?)':'');
    } else if ($sPos ) {
      $pos =  $sPos.($lCert[1]==2?'(?)':'');
    }else if ($pos) {
      $pos .=  ($lCert[0]==2?'(?)':'');
    }

    if ($processInfl) {
      $infCF = $row['icert'];
      $case = $row['caseid'];
      $gen = $row['ngenid'];
      $num = $row['gnumid'];
      $vper = $row['personid'];
      $vvoice = $row['voiceid'];
      $vtense = $row['tenseid'];
      $vmood = $row['moodid'];
      $conj2 = $row['conjid'];
      $morph = '';
      if ($isVerb) { //term dependency
        if ($vmood && is_numeric($vmood)) {
          $morph = Entity::getTermFromID($vmood).($infCF[2]==2?'(?)':'');
        } else if ($vtense && is_numeric($vtense)) {
          $morph = Entity::getTermFromID($vtense).($infCF[0]==2?'(?)':'');
        } else {
          $morph = '?';
        }
        if ($num && is_numeric($num)) {
          $num = Entity::getTermFromID($num).($infCF[4]==2?'(?)':'');
        } else {
          $num = '?';
        }
        if ($vper && is_numeric($vper)) {
          $vper = Entity::getTermFromID($vper).($infCF[6]==2?'(?)':'');
        } else {
          $vper = '?';
        }
        $morph .= " ".$vper;
        if ($num && $num != '?') {
          $morph .= " ".$num;
        }
        if ($conj2 && is_numeric($conj2)) {
          $conj2 = Entity::getTermFromID($conj2).($infCF[7]==2?'(?)':'');
        } else {
          $conj2 = '?';
        }
        $morph .= " ".$conj2;
      } else {
        if ($gen && is_numeric($gen)) {
          $gen = Entity::getTermFromID($gen).($infCF[3]==2?'(?)':'');
        } else {
          $gen = '?';
        }
        $morph = $gen;
        if ($num && is_numeric($num)) {
          $num = Entity::getTermFromID($num).($infCF[4]==2?'(?)':'');
        } else {
          $num = '?';
        }
        $morph .= " ".$num;
        if ($case && is_numeric($case)) {
          $case = Entity::getTermFromID($case).($infCF[5]==2?'(?)':'');
        } else {
          $case = '?';
        }
        $morph .= " ".$case;
        if ($conj2 && is_numeric($conj2)) {
          $conj2 = Entity::getTermFromID($conj2).($infCF[7]==2?'(?)':'');
        } else {
          $conj2 = '?';
        }
        $morph .= " ".$conj2;
      }
    }
  }
  return array("pos"=>"$pos","morph"=>"$morph");
}

/**
* calculate switch hash for an entity
*
* @param string(3) $prefix identifying the entity type 'scl', 'tok' or 'cmp'
* @param int $id entity identifier
*
* @return string $hash made from $prefix + start segID + end segID
*/

function getEntitySwitchHash($prefix,$id) {
  $tag = $prefix.$id;
//  if ($prefix == 'scl') {
//    $syllable = new SyllableCluster($id);
//    $startID = $endID = $syllable->getSegmentID();
//  } else 
  if ($prefix == 'tok') {
    $token = new Token($id);
    if (!$token) {
      return null;
    }
    $sclIDs = $token->getSyllableClusterIDs(false,true);
    if (!$sclIDs || !$sclIDs[0] || !$sclIDs[count($sclIDs)-1]) {
      return null;
    }
    $startSclID = $sclIDs[0];
    $endSclID = $sclIDs[count($sclIDs)-1];
    $syllable = new SyllableCluster($startSclID);
    $startID = $syllable->getSegmentID();
    $split = checkForSplit($token,$syllable,false);
    if ($split){
      $startID = "scl".$startSclID."S".$split;
//      $startID = "scl".$startSclID."S".$startID."-".$split;
    }

    $syllable = new SyllableCluster($endSclID);
    $endID = $syllable->getSegmentID();
    $split = checkForSplit($token,$syllable,true);
    if ($split){
      $endID = "scl".$endSclID."S".$split;
//      $endID = "scl".$endSclID."S".$endID."-".$split;
    }
} else if ($prefix == 'cmp') {
    $compound = new Compound($id);
    if (!$compound) {
      return null;
    }
    $cmpTokens = $compound->getTokens();
    if (!$cmpTokens || $cmpTokens->getCount()==0) {
      return null;
    }
    $tokens = $cmpTokens->getEntities();
    if (!$tokens || !$tokens[0] || !$tokens[count($tokens)-1]) {
      return null;
    }
    $token = $tokens[0];
    $sclIDs = $token->getSyllableClusterIDs();
    if (!$sclIDs || !$sclIDs[0]) {
      return null;
    }
    $startSclID = $sclIDs[0];
    $syllable = new SyllableCluster($startSclID);
    $startID = $syllable->getSegmentID();
    $split = checkForSplit($token,$syllable,false);
    if ($split){
      $startID = "scl".$startSclID."S".$split;
//      $startID = "scl".$startSclID."S".$startID."-".$split;
    }

    $token = $tokens[count($tokens)-1];
    $sclIDs = $token->getSyllableClusterIDs();
    if (!$sclIDs || !$sclIDs[count($sclIDs)-1]) {
      return null;
    }
    $endSclID = $sclIDs[count($sclIDs)-1];
    $syllable = new SyllableCluster($endSclID);
    $endID = $syllable->getSegmentID();
    $split = checkForSplit($token,$syllable,true);
    if ($split){
      $endID = "scl".$endSclID."S".$split;
//      $endID = "scl".$endSclID."S".$endID."-".$split;
    }
  } else {
    return null;
  }
  // add entity tag to switch lookup using hash
  return $prefix."seg$startID"."seg$endID";
}


/**
* calculate switch information for all texts in $entities
*
* @param object reference $entities
* @param object reference $gra2SclMap mapping Grapheme ids to SyllableCluster id
* @param object reference $errors to log any errors encountered during calculation
* @param object reference $warnings to log any warnings encountered during calculation
*
* @return object $switchInfoByTextID lookups for mapping entGID to hash and hash to entity GIDs
*/
function getSwitchInfoByTextFromEntities(&$entities,&$gra2SclMap,&$errors,&$warnings) {
  $retVal = array();
  $switchInfoByTextID = array();

  if (!array_key_exists('txt',$entities) || count($entities['txt'])>0) {
    //iterate the texts
    foreach ($entities['txt'] as $txtID => $txtObj) {
      $switchInfoByTextID[$txtID] = array('entSetBySegHash'=>array(),'hashByEntTag'=> array());
      $switchInfo = &$switchInfoByTextID[$txtID];
      //text must have edition info to calculate
      if (!array_key_exists('ednIDs',$txtObj) || count($txtObj['ednIDs'])<1) {
        //zero or 1 edition so no relavent switch info skip
        array_push($warnings,"warning no multiple editions found for text txt$txtID skipping switch calculation");
        continue;
      }
      //iterate each edition
      foreach ($txtObj['ednIDs'] as $ednID) {
        if (!array_key_exists($ednID,$entities['edn'])) {
          //error so mark and move on
          array_push($warnings,"warning edition edn$ednID not found for text txt$txtID skipping switch calculation");
          continue;
        }
        $ednObj = $entities['edn'][$ednID];
        if (!array_key_exists('seqIDs',$ednObj)) {//no top sequence containers
          array_push($warnings,"warning sequence containers for edition edn$ednID not found for text txt$txtID skipping switch calculation");
          continue;
        }
        //iterate each sequence container and create entHash Lookup and hash set
        foreach ($ednObj['seqIDs'] as $seqID) {
          if (!array_key_exists($seqID,$entities['seq'])) {
            //error so mark and move on
            array_push($warnings,"warning sequence seq$seqID not found for edition edn$ednID of text txt$txtID skipping switch calculation");
            continue;
          }
          $seqObj = $entities['seq'][$seqID];
          if (!array_key_exists('entityIDs',$seqObj)) {//no contained entities
            array_push($warnings,"warning sequence seq$seqID is empty (edition edn$ednID of text txt$txtID) skipping switch calculation");
            continue;
          }
          foreach ($seqObj['entityIDs'] as $entGID) {
            addSwitchInfo($entGID,$entities,$gra2SclMap,$switchInfo,$errors,$warnings);
          }
        }//end foreach top level sequence
      }//end foreach edition
    }//end foreach text
    return $switchInfoByTextID;
  }//end if text
  return null;
}

/**
* checkForSplit - determine is syllable is split
*
* @param Token $token to be checked
* @param Syllable $syllable located at beginning or end of token to be checked
* @param boolean $end determine whether to check end or start(default) of token
*
* @returns int position of split or zero if not split
*/

function checkForSplit($token, $syllable, $end) {
  $strTok = $token->getValue();
  $strScl = $syllable->getValue();
  $cntScl = strlen($strScl);
  $split = 0;
  if (!$end) { //check start of token
    $strTokCompare = substr($strTok,0,$cntScl);
    $split = 0;
    while ( $strScl != $strTokCompare) {
      $split++;
      $strScl = substr($strScl,1);//remove lead char
      $strTokCompare = substr($strTokCompare,0, (strlen($strTokCompare) - 1));
    }
  } else {
    $strTokCompare = substr($strTok,strlen($strTok) - $cntScl);
    $split = $cntScl;
    while ( $strScl != $strTokCompare) {
      $split--;
      $strTokCompare = substr($strTokCompare,1);//remove lead char
      $strScl = substr($strScl,0, (strlen($strScl) - 1));
    }
  }
  return $split;
}

/**
* update existing switch information given a new entities global identifier
*
* @param int $entGID
* @param object reference $entities
* @param object reference $gra2SclMap mapping Grapheme ids to SyllableCluster id
* @param object reference $switchInfo lookups for mapping entGID to hash and hash to entity GIDs
* @param object reference $errors to log any errors encountered during calculation
* @param object reference $warnings to log any warnings encountered during calculation
*
* @return array $startID,$endID Segment ids marking the range of the given entity
*/

function addSwitchInfo($entGID,&$entities,&$gra2SclMap,&$switchInfo,&$errors,&$warnings) {
  $prefix = substr($entGID,0,3);
  $startID = $endID = null;
  $id = substr($entGID,4);
  if (array_key_exists($prefix,$entities) && array_key_exists($id, $entities[$prefix])) {
    $entObj = $entities[$prefix][$id];
    switch ($prefix) {
      case "xxxscl"://xxx deprecate switch for syllables
        if (array_key_exists('segID',$entObj)) {
          $startID = $endID = $entObj['segID'];
        }
        break;
      case "tok":
        $graIDs = $entObj['graphemeIDs'];
        $startSclID = @$gra2SclMap[$graIDs[0]];
        $endSclID = @$gra2SclMap[$graIDs[count($graIDs)-1]];
        if ($startSclID && $endSclID) {
          if ($entities['scl'][$startSclID]) {
            if (array_key_exists('segID',$entities['scl'][$startSclID]) && $entities['scl'][$startSclID]['segID']) {
              $startID = $entities['scl'][$startSclID]['segID'];
              $syllable = new SyllableCluster($endSclID);
              $split = checkForSplit($entObj,$syllable,false);
              if ($split){
                $startID = "scl".$startSclID."S".$split;
//                $startID = "scl".$startSclID."S".$startID."-".$split;
              }
            } else {
              array_push($warnings,"warning no segID for syllable ID $startSclID of $entGID skipping switch calculation");
            }
          } else {
            array_push($warnings,"warning syllable $startSclID  of $entGID not found - skipping switch calculation");
          }
          if ($entities['scl'][$endSclID]) {
            if (array_key_exists('segID',$entities['scl'][$endSclID]) && $entities['scl'][$endSclID]['segID']) {
              $endID = $entities['scl'][$endSclID]['segID'];
              $syllable = new SyllableCluster($endSclID);
              $split = checkForSplit($entObj,$syllable,true);
              if ($split){
                $endID = "scl".$endSclID."S".$split;
//                $endID = "scl".$endSclID."S".$endID."-".$split;
              }
            } else {
              array_push($warnings,"warning no segID for syllable ID $endSclID of $entGID skipping switch calculation");
            }
          } else {
            array_push($warnings,"warning syllable $endSclID  of $entGID not found - skipping switch calculation");
          }
        } else {
          if (!array_key_exists($graIDs[0],$gra2SclMap)) {
            array_push($warnings,"warning no syllable map info for grapheme ".$graIDs[0]." of $entGID skipping switch calculation");
          }
          if (!array_key_exists($graIDs[count($graIDs)-1],$gra2SclMap)) {
            array_push($warnings,"warning no syllable map info for grapheme ".$graIDs[count($graIDs)-1]." of $entGID skipping switch calculation");
          }
        }
        break;
      case "cmp":
      case "seq":
        if (!array_key_exists('entityIDs',$entObj) || count($entObj['entityIDs'])<1) {
          array_push($warnings,"warning entity $entGID is empty skipping switch calculation");
        } else {
          $entIDs = $entObj['entityIDs'];
          $i=0;
          $subStartID = $subEndID = null;
          foreach ($entIDs as $entGID) {
            list($subStartID,$subEndID) = addSwitchInfo($entGID,$entities,$gra2SclMap,$switchInfo,$errors,$warnings);
            if ($i == 0) {
              $startID = $subStartID;
              $i=1;
            }
          }
          $endID = $subEndID;
        }
        break;
    }
    $tag = $prefix.$id;
    if ($startID && $endID) {
      // add entity tag to switch lookup using hash
      $hash = $prefix."seg$startID"."seg$endID";
      $switchInfo['hashByEntTag'][$tag] = $hash;
      if (!array_key_exists($hash,$switchInfo['entSetBySegHash'])) {
        $switchInfo['entSetBySegHash'][$hash] = array($tag);
      } else if (!in_array($tag,$switchInfo['entSetBySegHash'][$hash])){
        array_push($switchInfo['entSetBySegHash'][$hash],$tag);
      }
    } else if (!$startID) {
      array_push($warnings,"warning start segID for entity $tag was not found not adding switch info");
    } else if (!$endID) {
      array_push($warnings,"warning end segID for entity $tag was not found not adding switch info");
    }
  }
  return array($startID,$endID);
}

/**
* find the editions containing teh given gid
*
* @param string $gid global id of target entity
*/

function getEdnIDsFromGID($gid = null) {
  if (!$gid){
    return array();
  }
  $query =
   "select array_agg(distinct edn_id) 
    from edition left join 
     (WITH RECURSIVE seqs AS (
       SELECT
           seq_id,
           seq_entity_ids,
           seq_label
       FROM
           sequence
       WHERE
           '$gid' = any(seq_entity_ids) and not seq_owner_id = 1
       UNION
           SELECT
             c.seq_id,
             c.seq_entity_ids,
             c.seq_label
           FROM
             sequence c
           INNER JOIN seqs s ON concat('seq:',s.seq_id) = any(c.seq_entity_ids)
       WHERE NOT c.seq_owner_id = 1
     ) SELECT * FROM seqs) a 
    on a.seq_id = any(edn_sequence_ids) 
    where a.seq_id is not null;";
  
  $dbMgr = new DBManager();
  if (!$dbMgr || $dbMgr->getError()) {
    error_log("error loading dataManager");
    return array();
  }
  $dbMgr->query($query);
  if ($dbMgr->getError()) {
    error_log("error querying for edn for entity ".$gid.' '.$dbMgr->getError());
    return array();
  } else if ($dbMgr->getRowCount() < 1) {
    error_log("error no editions found for entity $gid");
  } else {
    $row = $dbMgr->fetchResultRow();
  }
  return explode(",",trim($row[0],"{}"));
}

function getSequenceIDsForGID($gid){
  $linePhysicalTypeID = Entity::getIDofTermParentLabel('linephysical-textphysical');// warning!!! term dependency
  $textDivisionTypeID = Entity::getIDofTermParentLabel('textdivision-text');// warning!!! term dependency

  $query = 
    "SELECT array_agg(DISTINCT seq_id)
     FROM sequence
     WHERE NOT seq_owner_id = 1
           AND seq_type_id IN ($linePhysicalTypeID,$textDivisionTypeID)
           AND '$gid' = ANY(seq_entity_ids);";
  
  $dbMgr = new DBManager();
  if (!$dbMgr || $dbMgr->getError()) {
    error_log("error loading dataManager");
    return array();
  }
  $dbMgr->query($query);
  if ($dbMgr->getError()) {
    error_log("error querying for seqID for entity ".$gid.' '.$dbMgr->getError());
    return array();
  } else if ($dbMgr->getRowCount() < 1) {
    error_log("error no seqIDs found for entity $gid");
  } else {
    $row = $dbMgr->fetchResultRow();
  }
  return explode(",",trim($row[0],"{}"));
}


/**
* invalidate lemma for this word
*
* @param string $wordGID global id of word
*/

function invalidateWordLemma($wordGID = null) { // setDirty flag
  // find lemma of this word and invalidate
  $query = "select lem_id from lemma ".
           "where '$wordGID' = ANY(lem_component_ids) and lem_owner_id != 1 union all ".
           "select lem_id ".
           "from lemma left join inflection on concat('inf:',inf_id) = ANY(lem_component_ids) ".
           "where '$wordGID' = ANY(inf_component_ids) and lem_owner_id != 1;";
  $dbMgr = new DBManager();
  $dbMgr->query($query);
  if ($dbMgr->getRowCount()>0){
    while ($row = $dbMgr->fetchResultRow()) {
      $lemma = new Lemma($row['lem_id']);
      if (!$lemma->hasError() && $lemma->getID()) {
        if ($lemma->getScratchProperty("entry")) { //clear cached entry html
          $lemma->storeScratchProperty("entry",null);
          $lemma->save();
        }
      }
    }
  }
}

/**
* invalidate lemma
*
* @param Lemma $lemma entity
*/

function invalidateLemma($lemma = null) { // setDirty flag
  if ($lemma && !$lemma->hasError() && $lemma->getID()) {
    if ($lemma->getScratchProperty("entry")) { //clear cached entry html
      $lemma->storeScratchProperty("entry",null);
      $lemma->save();
      //  error_log("lemma scratch 'entry'");
    }
    $catID = $lemma->getCatalogID();
    if ($catID) {
      invalidateCachedViewerLemmaHtmlLookup($catID,null); //todo use lemma attested forms to find editions scope
    }
  }
}

/**
* invalidate cache catalog entities
*
* @param int $catID Catalog id
*/

function invalidateCachedCatalogEntities($catID = null) { // setDirty flag
  if (!$catID) {
    return;
  }
  $cacheKey = "cat$catID"."cachedEntities";
  invalidateCache($cacheKey);
  //  error_log("invalidateCache($cacheKey)");
}

/**
* invalidate cache edition entities
*
* @param int $ednID Sequence id
*/

function invalidateCachedEditionEntities($ednID = null) { // setDirty flag
  if (!$ednID) {
    return;
  }
  $cacheKey = "edn$ednID"."cachedEntities";
  invalidateCache($cacheKey);
  //  error_log("invalidateCache($cacheKey)");
  }

/**
* invalidate all caches containing entity info 
*
* @param Entity object $entity
*/

function invalidateCachedEntityInfo($entity = null) {
  if (!$entity) {
    return;
  } else {
    $entType = $entity->getEntityTypeCode();
    $entGID = $entity->getGlobalID();
    $ednIDs = getEdnIDsFromGID($entGID);
    $seqIDs = null;
    switch ($entType){
      case 'tok':
        $seqIDs = getSequenceIDsForGID($entGID);
        $sclIDs = $entity->getSyllableClusterIDs();
        if ($sclIDs) {
          $firstSclGID = 'scl:'.$sclIDs[0];
          $seqIDs = array_merge($seqIDs,getSequenceIDsForGID($firstSclGID));
          if (count($sclIDs) > 1) {
            $lastSclGID = $sclIDs[count($sclIDs)-1];
            $seqIDs = array_merge($seqIDs,getSequenceIDsForGID($lastSclGID));
          }
        }
        break;
      case 'cmp':
        $seqIDs = getSequenceIDsForGID($entGID);
        $tokIDs = $entity->getTokenIDs();
        $firstToken = new Token($tokIDs[0]);
        if ($firstToken && !$firstTokon->hasError()) {
          $sclIDs = $firstToken->getSyllableClusterIDs();
          $seqIDs = array_merge($seqIDs,getSequenceIDsForGID($sclIDs[0]));
        }
        $lastToken = new Token($tokIDs[count($tokIDs)-1]);
        if ($lastToken && !$lastToken->hasError()) {
          $sclIDs = $lastToken->getSyllableClusterIDs();
          $seqIDs = array_merge($seqIDs,getSequenceIDsForGID($sclIDs[count($sclIDs)-1]));
        }
        break;
      case 'scl':
        // find text division and physical line sequences to invalidate
        $seqIDs = getSequenceIDsForGID($entGID);
        break;
      case 'seq':
        $seqIDs = array($entGID);
    }
    if ($ednIDs && count($ednIDs) > 0 && //ensure we have an edition
        $seqIDs && count($seqIDs) > 0) { //ensure we have a sequence
      $seqIDs = array_unique($seqIDs);
      foreach ($ednIDs as $ednID) {
        foreach ($seqIDs as $seqID) {
          $sequence = new Sequence($seqID);
          if (!$sequence->hasError()) {
            invalidateSequenceCache($sequence,$ednID);
          } else {
            invalidateCachedSeqEntities($seqID,$ednID);
          }
        }
        invalidateCachedEditionEntities($ednID);
        invalidateCachedEditionViewerHtml($ednID);
      }
    }
  }
}

/**
* invalidate cache edition lookup
*
* @param Edition object $edition
*/

function invalidateCachedEditionViewerInfo($edition = null) { // setDirty flag
  if (!$edition) {
    return;
  } else {
    $edition->storeScratchProperty('lookupInfo',null);
    $edition->save();
    //  error_log("edn scratch 'lookupInfo'");
  }
}

/**
* invalidate cached all text resources
*
*/

function invalidateAllTextResources() { // setDirty flag
  $cacheKey = 'AllTextResources';
  invalidateCache($cacheKey);
  //  error_log("invalidateCache($cacheKey)");
}

/**
* invalidate cached all text search results
*
*/

function invalidateSearchAllResults() { // setDirty flag
  $cacheKey = 'SearchAllResults';
  invalidateCache($cacheKey);
  //  error_log("invalidateCache($cacheKey)");
}

/**
* invalidate cache edition viewer html
*
* @param int $ednID edition id
*/

function invalidateCachedEditionViewerHtml($ednID = null) { // setDirty flag
  if ($ednID ) {
    $cacheKey = "edn$ednID".'structviewHTML';
    invalidateCache($cacheKey);
    //  error_log("invalidateCache($cacheKey)");
  }
}

/**
* invalidate cache by catalog id and edition id
*
* @param int $catID Catalog id
* @param int $ednID Edition id
*/

function invalidateCachedViewerLemmaHtmlLookup($catID = null,$ednID = null) { // setDirty flag
  $cacheKey = "glosscat".($catID?$catID:'%')."edn".($ednID?$ednID:'%');
  invalidateCache($cacheKey);
  //  error_log("invalidateCache($cacheKey)");
}

/**
* invalidate cache by user id and sequence id
*
* @param int $seqID Sequence id
* @param int $usrID UserGroup id
*/

function invalidateCachedSeqEntities($seqID = null,$ednID = null) { // setDirty flag
  $cacheKey = "seq".($seqID?$seqID:'%')."edn".($ednID?$ednID:'%');
  invalidateCache($cacheKey);
  //  error_log("invalidateCache($cacheKey)");
}


/**
* invalidate all parent sequence caching to top of heirarchy
*
* @param mixed $seqGID
* @param mixed $ednSeqIDs
* @param mixed $ednID
*/
function invalidateParentCache($seqGID,$ednSeqIDs,$ednID) {
  if ($ednSeqIDs && count($ednSeqIDs) > 0 && in_array(substr($seqGID,4),$ednSeqIDs)) {
    return;//top level sequence nothing to do
  }
  $containers = new Sequences("'$seqGID' = ANY(seq_entity_ids)",null,null,null);
  if ($containers && $containers->getCount() > 0){
    foreach($containers as $seqContainer){
      invalidateParentCache($seqContainer->getGlobalID(),$ednSeqIDs,$ednID);
      invalidateSequenceCache($seqContainer,$ednID);
    }
  }
}

/**
* invalidate all sequence caching
*
* @param Sequence entity $sequence
* @param int $ednID Edition id
*/

function invalidateSequenceCache($sequence,$ednID) { // setDirty flag
  $seqID = $sequence->getID();
  $scratchKey = "edn".$ednID."physLineHtml";
  if ($sequence->getScratchProperty($scratchKey)) { //clear cached viewer html
    $sequence->storeScratchProperty($scratchKey,null);
    //  error_log("edn scratch '$scratchKey'");
  }
  $scratchKey = "edn".$ednID."structHtml";
  if ($sequence->getScratchProperty($scratchKey)) { //clear cached viewer html
    $sequence->storeScratchProperty($scratchKey,null);
    //  error_log("edn scratch '$scratchKey'");
  }
  $sequence->save();
  if ($seqID){
    $cacheKey = "seq".$seqID."edn".($ednID?$ednID:'%');
    invalidateCache($cacheKey);
    //  error_log("invalidateCache($cacheKey)");
  }
}

/**
* invalidate matching entries or entire cache
*
* @param string $cacheKey string to match cache entries
*/

function invalidateCache($cacheKey = null) { // setDirty flag of matching entries or entire cache
  $dbMgr = new DBManager();
  $dbMgr->query("SELECT * FROM jsoncache".($cacheKey?" WHERE jsc_label like '$cacheKey' and jsc_owner_id != 1":""));
//  error_log("invalidate entire cache entry");
  while ($row = $dbMgr->fetchResultRow()) {
    $jsonCache = new JsonCache($row);
    if (!$jsonCache->hasError() && $jsonCache->getID()) {
      $jsonCache->setDirtyBit();
      $jsonCache->save();
    }
  }
}

/**
* calculate switch information
*
* for each text identified calculate a start stop seg range marking hash
* used to mark alternative interpretations
*
* @param mixed $txtIDs list of Text ids for calculation
*/

function getSwitchInfo($txtIDs) {
  $retVal = array();
  $switchInfoByTextID = array();
  $errors = array();
  $warnings = array();

  if (!is_array($txtIDs)) {
    if (is_string($txtIDs)) {//convert to array
      if(strpos($txtIDs,",")) {
        $txtIDs = explode(",",$txtIDs);
      } else {
        $txtIDs = array($txtIDs);
      }
    } else {
      $txtIDs = array($txtIDs);
    }
  }

  //get all switchInfo foreach text
  foreach ($txtIDs as $txtID) {
    $switchInfo = array();
    //get all editions for text
    $editions = new Editions("edn_text_id = ".$txtID);
    if ($editions->getError()) {
      $switchInfoByTextID[$txtID] = array('error' => "error loading editions for text - ".$editions->getError());
    } else if ($editions->getCount() > 0) {
      //get unique sequences for all editions
      $seqIDs = array();
      foreach ($editions as $edition) {
        $seqIDs = array_merge($seqIDs,$edition->getSequenceIDs());
      }
      $seqIDs = array_unique($seqIDs);
      //reduce sequences to switchable entities (compounds, tokens and syllableclusters)
      $entSeqIDs = array();
      $sequences = new Sequences("seq_id in (".join(',',$seqIDs).")",null,null,null);
      foreach ($sequences as $sequence) {
        $seqType = strtolower($sequence->getType('en'));
        if ($seqType == "textphysical" || $seqType == "text") {
          $entSeqIDs = array_merge($entSeqIDs,$sequence->getEntityIDs());
        }
      }
      $entSeqIDs = array_unique($entSeqIDs);
      $entSeqIDsList = join(',',$entSeqIDs);
      $entSeqIDsList = preg_replace("/seq\:/",'',$entSeqIDsList);
      $sequences = new Sequences("seq_id in (".$entSeqIDsList.")",null,null,null);
      $entGIDs = array();
      foreach ($sequences as $sequence) {
        $entGIDs = array_merge($entGIDs,$sequence->getEntityIDs());
      }
      $entGIDs = array_unique($entGIDs);
      preg_match_all("/cmp:\d+/",join(" ",$entGIDs),$cmpIDs);//find all the compounds gids
      $cmpIDs = $cmpIDs[0];
      while (count($cmpIDs)) {
        $cmpID = array_shift($cmpIDs);
        $compound = new Compound(substr($cmpID,4));
        if (!$compound->hasError()) {
          $compGIDS = $compound->getComponentIDs();
          if (count($compGIDS)) {
            foreach ($compGIDS as $compGID) {
              if (!in_array($compGID,$entGIDs)) { // new GID so add to list
                array_push($entGIDs,$compGID);
                if (substr($compGID,0,3) == "cmp") {// compound so add to processing list
                  array_push($cmpIDs,$compGID);
                }
              }
            }
          }
        }
      }
      $entGIDs = array_unique($entGIDs);
      $entities = new OrderedSet();
      $entities->loadEntities($entGIDs);
      //foreach entity
      $switchInfo = array('entSetBySegHash'=>array(),'hashByEntTag'=> array());
      foreach ($entities as $entity){
        // calc hash from start and stop segment
        $gid = $entity->getGlobalID();
        $tag = preg_replace("/\:/","",$gid);
        $prefix = substr($gid,0,3);
        if ($prefix == 'scl') {
          $startID = $endID = $entity->getSegmentID();
        } else if ($prefix == 'tok') {
          $sclIDs = $entity->getSyllableClusterIDs();
          if (!count($sclIDs)) {
            array_push($warnings,"warning found token entity GID ".$entity->getGlobalID()." with no sclIDs and entGIDs - ".join(',',$entGIDs));
            continue;
          }
          $syllable = new SyllableCluster($sclIDs[0]);
          $startID = $syllable->getSegmentID();
          $syllable = new SyllableCluster($sclIDs[count($sclIDs)-1]);
          $endID = $syllable->getSegmentID();
        } else if ($prefix == 'cmp') {
          $cmpTokens = $entity->getTokens();
          $tokens = $cmpTokens->getEntities();
          $token = $tokens[0];
          $sclIDs = $token->getSyllableClusterIDs();
          $syllable = new SyllableCluster($sclIDs[0]);
          $startID = $syllable->getSegmentID();
          $token = $tokens[count($tokens)-1];
          $sclIDs = $token->getSyllableClusterIDs();
          $syllable = new SyllableCluster($sclIDs[count($sclIDs)-1]);
          $endID = $syllable->getSegmentID();
        } else {
          //warn
          array_push($warnings,"warning found entity GID $gid not valid txtID $txtID switch info");
        }
        // add entity tag to switch lookup using hash
        $hash = $prefix."seg$startID"."seg$endID";
        $switchInfo['hashByEntTag'][$tag] = $hash;
        if (!array_key_exists($hash,$switchInfo['entSetBySegHash'])) {
          $switchInfo['entSetBySegHash'][$hash] = array($tag);
        } else {
          array_push($switchInfo['entSetBySegHash'][$hash],$tag);
        }
      }
    } else {
      array_push($warnings,"warning text $txtID has no editions ");
    }
    if ($switchInfo && count($switchInfo) > 0 ){
      $switchInfoByTextID[$txtID] = $switchInfo;
    }
  }
  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  if ($switchInfoByTextID && count($switchInfoByTextID) > 0 ){
    $retVal["switchInfoByTextID"] = $switchInfoByTextID;
  }
  return $retVal;
}

function getUserPersistedPreferences(){
  //check user scratch for preferences array
  $user = new UserGroup(getUserID());
  if (!$user || !$user->getID() || $user->hasError()) {
    return null;
  }
  return $user->getPreferences();
}

function getUserDefEditorID(){
  $prefs = getUserPreferences();
  return $prefs['defaultEditUserID'];
}

function getUserDefVisibilityIDs(){
  $prefs = getUserPreferences();
  return $prefs['defaultVisibilityIDs'];
}

function getUserDefAttrIDs(){
  $prefs = getUserPreferences();
  return $prefs['defaultAttributionIDs'];
}

/**
* store user default owner id
*
* @param int $ugrID UserGroup entity id used as owner id for newly created entities
*/

function setUserDefEditorID($ugrID, $dbname = ""){
  if ($dbname && $_SESSION) {
    if (!isset($_SESSION['userPrefs'])) {
      $_SESSION['userPrefs'] = array();
    }
    if (!isset($_SESSION['userPrefs'][$dbname])) {
      $_SESSION['userPrefs'][$dbname] = array();
    }
    $_SESSION['userPrefs'][$dbname]['defaultEditUserID'] = $ugrID;
  } else {
    $user = new UserGroup(getUserID());
    if (!$user || !$user->getID() || $user->hasError()) {
      return;
    } else {
      $prefs = getUserPreferences();
      $prefs['defaultEditUserID'] = $ugrID;
      //store in user
      $user->setPreferences($prefs);
      $user->save();
    }
  }
}

/**
* store user default visibility ids
*
* @param int array $visIDs of UserGroup entity ids
*/

function setUserDefVisibilityIDs($visIDs, $dbname = ""){
  if ($dbname && $_SESSION) {
    if (!isset($_SESSION['userPrefs'])) {
      $_SESSION['userPrefs'] = array();
    }
    if (!isset($_SESSION['userPrefs'][$dbname])) {
      $_SESSION['userPrefs'][$dbname] = array();
    }
    $_SESSION['userPrefs'][$dbname]['defaultVisibilityIDs'] = $visIDs;
  } else {
    $user = new UserGroup(getUserID());
    if (!$user || $user->hasError()) {
      return;
    } else {
      $prefs = getUserPreferences();
      $prefs['defaultVisibilityIDs'] = $visIDs;
      //store in user
      $user->setPreferences($prefs);
      $user->save();
    }
  }
}

/**
* store user default attribution ids
*
* @param int array $attrIDs of Attribution entity ids
*/

function setUserDefAttrIDs($attrIDs, $dbname = ""){
  if ($dbname && $_SESSION) {
    if (!isset($_SESSION['userPrefs'])) {
      $_SESSION['userPrefs'] = array();
    }
    if (!isset($_SESSION['userPrefs'][$dbname])) {
      $_SESSION['userPrefs'][$dbname] = array();
    }
    $_SESSION['userPrefs'][$dbname]['defaultAttributionIDs'] = $attrIDs;
  } else {
    $user = new UserGroup(getUserID());
    if (!$user || $user->hasError()) {
      return;
    } else {
      $prefs = getUserPreferences();
      $prefs['defaultAttributionIDs'] = $attrIDs;
      //store in user
      $user->setPreferences($prefs);
      $user->save();
    }
  }
}

/**
* get user preferences in order from seaaion, from user entity or create and save to session
*
*/

function getUserPreferences($dbname = ""){
  // check session - prefer session over persisted
  if (!$dbname && defined('DBNAME')) {
    $dbname = DBNAME;
  }
  if ($dbname && $_SESSION && isset($_SESSION['userPrefs']) && isset($_SESSION['userPrefs'][$dbname])) {
    $userPreferences = $_SESSION['userPrefs'][$dbname];
  } else {
    $userPreferences = getUserPersistedPreferences();
  }

  if (!$userPreferences){
    //initialise to system default values
    $userPreferences = array(
      'defaultVisibilityIDs'=>array(getUserID()),
      'defaultEditUserID'=>getUserID(),
      'defaultAttributionIDs'=>array(getUserDefaultAttributionID())
    );
  } else { // check for missing values
    if (!isset($userPreferences['defaultVisibilityIDs'])) {
      $userPreferences['defaultVisibilityIDs']=array(getUserID());
    }
    if (!isset($userPreferences['defaultEditUserID'])) {
      $userPreferences['defaultEditUserID']=getUserID();
    }
    if (!isset($userPreferences['defaultAttributionIDs']) ||
        count($userPreferences['defaultAttributionIDs'])>0 && !$userPreferences['defaultAttributionIDs'][0]) {
      $userPreferences['defaultAttributionIDs']=array(getUserDefaultAttributionID());
    }
  }
  //save to session if needed
  if (defined('DBNAME') && $_SESSION){
    if (!isset($_SESSION['userPrefs'])) {
      $_SESSION['userPrefs'] = array();
    }
    $_SESSION['userPrefs'][DBNAME] = $userPreferences;
  }
  return $userPreferences;
}

/**
* find or create a default attribution for the logged in user
*/

function getUserDefaultAttributionID(){
  //check scratch
  $atbID = null;
  $user = new UserGroup(getUserID());
  if (!$user || $user->hasError()) {
    return null;
  } else if (! $user->getDefaultAttributionID() && getUserID() != 2 && getUserID() != 6) {
    $indivAtgTypeID = $user->getIDofTermParentLabel('individual-attributiongrouptype');
    //check database for attribution with group id and 
    $query = "select atb_id ".
             "from attribution  ".
               "left join attributiongroup on atb_group_id = atg_id ".
             "where atg_id is not null and ".
                   "atb_owner_id = ".$user->getID()." and not atg_owner_id = 1 and atg_type_id = $indivAtgTypeID;";
    $dbMgr = new DBManager();
    $dbMgr->query($query);
    if ($dbMgr->getRowCount()>0){
      $row = $dbMgr->fetchResultRow();
      $atbID = $row[0];
    } else {
      //create attribution group
      $atg = new AttributionGroup();//todo  lookup ATG for user ??
      $atg->setRealname($user->getRealname());
      $atg->setName($user->getName());
      $atg->setDescription(($user->getDescription()?$user->getDescription():$user->getFamilyName())."(Work in progress)");
      $atg->setType($indivAtgTypeID);//term dependency
      $atg->setMemberIDs(array($user->getID()));
      $atg->setAdminIDs(array($user->getID()));
      $atg->setVisibilityIDs(array($user->getID()));
      $atg->setOwnerID($user->getID());
      $atg->save();
      //create atg attribute
      $att = new Attribution();
      $att->setTitle($user->getRealname());
      $att->setDetail("Work in progress");
      $att->setDescription($atg->getDescription());
      $att->setVisibilityIDs(array($user->getID()));
      $att->setOwnerID($user->getID());
      $att->setGroupID($atg->getID());
      $att->save();
      $atbID = $att->getID();
    }
    //store in user
    $user->setDefaultAttributionID($atbID);
    $user->save();
  }
  return $atbID;
}

/**
* retrieve a list of user/usergroup: name , description and id
*/

function getUserUGrpList(){
  //get user's usergroups (member or admin)
  $uGroups = new UserGroups("",'ugr_name',null,null);
  $uGrpUIList = array();
  if ($uGroups && !$uGroups->getError()) {
    foreach($uGroups as $userGroup) {
      $ugrpName = $userGroup->getName();
      $ugrpDesc = $userGroup->getDescription();
      if ($ugrpName) {
        array_push($uGrpUIList, array('name'=>$ugrpName,'description'=>$ugrpDesc, 'id'=>$userGroup->getID()));
      }
    }
  }
  return $uGrpUIList;
}

/**
* retrieve any existing relationship matching the input data
*
* @param string $fromEntGID global id of primary entity or subject being related
* @param string $toEntGID global id of secondary entity or related entity
* @param int $linkTypeID term id of term which defines the sematic/relationship
* @return Entity link Annotation matching the parameters || null
*/

function getRelationshipLink($fromEntGID,$toEntGID,$linkTypeID) {
  //find all annotations with same from to
  $existingLinks = new Annotations("'$fromEntGID' = ANY(ano_linkfrom_ids) and '$toEntGID' = ANY(ano_linkto_ids) and ano_type_id = $linkTypeID");
  if ($existingLinks->getCount() > 0) {
    return $existingLinks->current();
  }
  return null;
}

/**
*  return existing semantic link or create a semantic (trmID) link (annotation entity) between entities.
*
* @param string $fromEntGID global id of primary entity or subject being related
* @param string $toEntGID global id of secondary entity or related entity
* @param int $linkTypeID term id of term which defines the sematic/relationship
* @param int array $muxLinkTypeIDs of term IDs of can be used as terms (poor mans multiple inheritance)
* @return link Annotation
*/

function createRelationshipLink($fromEntGID,$toEntGID,$linkTypeID,$muxLinkTypeIDs = null) {
  //todo validate $linkType
  if (!$muxLinkTypeIDs) {
    $muxLinkTypeIDs = array($linkTypeID);
  }
  //find all annotations with same from to
  $existingLinks = new Annotations("'$fromEntGID' = ANY(ano_linkfrom_ids) and '$toEntGID' = ANY(ano_linkto_ids) and not ano_owner_id = 1");
  if ($existingLinks->getCount() > 0) {//check existing links for type or if MUX set check forlink in set of types
    foreach($existingLinks as $link) {
      if (in_array($link->getTypeID(),$muxLinkTypeIDs)) {//found existing link
        $link->setTypeID($linkTypeID); //in memory alter the type for use in calling routine
        return $link;
      }
    }
  }
  $link = new Annotation();
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $link->setOwnerID($defOwnerID);
  $link->setVisibilityIDs($defVisIDs);
  if ($defAttrIDs){
    $link->setAttributionIDs($defAttrIDs);
  }
  $link->setLinkFromIDs(array($fromEntGID));
  $link->setTypeID($linkTypeID);
  $link->setLinkToIDs(array($toEntGID));
  $link->save();
  return $link;
}

/**
* health check globals
*/
$hltherrors = array();
$hlthwarnings = array();
$hlthtokGraphemeIDs = array();
$hlthgra2TokGID = array();
$hlthtokGID2CtxLabel = array();

/**
* check the health of the edition
*
* walk through all entities of this edition and validate linked entities
*
* @param int $ednID edition ID
* @param boolean $verbose indicate the level of output information.
*/

function checkEditionHealth($ednID, $verbose = true, $errorsOnly = false, $continuousOut = false) {
  global $hltherrors, $hlthwarnings, $hlthgra2TokGID, $hlthtokGID2CtxLabel, $hlthtokGraphemeIDs;

  $retStr = "";
  $hltherrors = array();
  $hlthwarnings = array();
  $gid2SeqMap = array();
  $hlthgra2TokGID = array();
  $hlthtokGID2CtxLabel = array();
  $gra2SclGID = array();
  $seqGID2Label = array();
  $blnID2SclIDsMap = array();
  $srfID2BlnIDsMap = array();
  $blnIDs = array();
  $srfIDs = array();
  $sclIDs = array();
  $sclGraphemeIDs = array();
  $tokCmpGIDs = array();
  $hlthtokGraphemeIDs = array();
  $processedTokIDs = array();
  $edition = null;
  if ($ednID) {
    $edition = new Edition($ednID);
  }
  if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
    array_push($hlthwarnings,"Usage = testEditionLinks.php?db=dbnameGoesHere&ednID=idOfEditionGoesHere.");
 } else {
    $termInfo = getTermInfoForLangCode('en');
    $dictionaryCatalogTypeID = $termInfo['idByTerm_ParentLabel']['dictionary-catalogtype'];//term dependency
    $textSeqTypeID = $termInfo['idByTerm_ParentLabel']['text-sequencetype'];//term dependency
    $textDivSeqTypeID = $termInfo['idByTerm_ParentLabel']['textdivision-text'];//term dependency
    $textPhysSeqTypeID = $termInfo['idByTerm_ParentLabel']['textphysical-sequencetype'];//term dependency
    $linePhysSeqTypeID = $termInfo['idByTerm_ParentLabel']['linephysical-textphysical'];//term dependency
    $imageBaselineTypeID = $termInfo['idByTerm_ParentLabel']['image-baselinetype'];//term dependency
    $transBaselineTypeID = $termInfo['idByTerm_ParentLabel']['transcription-baselinetype'];//term dependency
    $ednID = $edition->getID();
    $seqIDs = $edition->getSequenceIDs();
    if ($seqIDs && count($seqIDs) > 0) {
      $condition = "seq_id in (".join(",",$seqIDs).")";
      $sequences = new Sequences($condition,null,null,null);
      $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
      if ($sequences && $sequences->getCount()>0) {
        $txtDivSeqIDs = array();
        $linePhysSeqIDs = array();
        $structuralSeqIDs = array();
        $seqRetString = '';
        //get physical and textdiv so that they are processed physical before token so that gra to scl map is constructed
        foreach ($sequences as $sequence) {
          $seqID = $sequence->getID();
          $seqTypeID = $sequence->getTypeID();
          $seqLabel = $sequence->getLabel()?$sequence->getLabel():$sequence->getSuperScript();
          $seqGID2Label["seq:$seqID"] = $seqLabel;
          if ($sequence->isMarkedDelete()) {
            array_push($hltherrors,"Error edition (edn:$ednID) has sequence ($seqLabel/seq:$seqID) that is marked for delete.");
            //ToDo  add code to add <a> for a service to correct the issue.
            continue;
          }
          if ($seqTypeID == $textSeqTypeID){
            // get all the child IDs
            $txtSeqGID = $sequence->getGlobalID();
            $txtDivSeqGIDs = $sequence->getEntityIDs();
            if (!$txtDivSeqGIDs) {
              array_push($hltherrors,"Error Text Sequence ($seqLabel/seq:$seqID) has no entity GIDs.");             
            } else {
              $txtDivSeqIDs = preg_replace("/seq\:/","",$txtDivSeqGIDs);
              if (strpos(join(' ',$txtDivSeqIDs),":") !== false) {
                array_push($hltherrors,"Error Text Sequence ($seqLabel/seq:$seqID) not all entity GIDs are sequence type. (".join(',',$txtDivSeqGIDs).").");
              }
            }
          } else if ($seqTypeID == $textPhysSeqTypeID){
            // get all the child IDs
            $physSeqGID = $sequence->getGlobalID();
            $linePhysSeqGIDs = $sequence->getEntityIDs();
            if (!$linePhysSeqGIDs) {
              array_push($hltherrors,"Error Physical Sequence ($seqLabel/seq:$seqID) has no entity GIDs.");             
            } else {
              $linePhysSeqIDs = preg_replace("/seq\:/","",$linePhysSeqGIDs);
              if (strpos(join(' ',$linePhysSeqIDs),":") !== false) {
                array_push($hltherrors,"Error Physical Sequence ($seqLabel/seq:$seqID) not all entity GIDs are sequence type. (".join(',',$linePhysSeqGIDs).").");
              }
            }
          } else {//other structural definitions
            array_push($structuralSeqIDs,$seqID);
          }
        }
        //process line sequences
        if ($verbose) {
          array_push($hltherrors,"**************** Processing Line Physical Sequences ***************************");
        }
        if ($continuousOut) {
          echo $hltherrors;
          $hltherrors = array();
        }
        if ($linePhysSeqIDs && count($linePhysSeqIDs) > 0) {
          $condition = "seq_id in (".join(",",$linePhysSeqIDs).")";
          $sequences = new Sequences($condition,null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          if ($sequences && $sequences->getCount()>0) {
            foreach ($sequences as $sequence) {
              $seqID = $sequence->getID();
              $seqLabel = $sequence->getLabel()?$sequence->getLabel():$sequence->getSuperScript();
              $seqGID2Label["seq:$seqID"] = $seqLabel;
              if ($sequence->isMarkedDelete()) {
                array_push($hltherrors,"Error Physical Sequence ($physSeqGID) has Line Sequence link ($seqLabel/seq:$seqID) that is marked for delete.");
                //ToDo  add code to add <a> for a service to correct the issue.
              }
              //check for line physical and not free text
              if ($sequence->getTermFromID($sequence->getTypeID()) == "LinePhysical") {
                $lineSclGIDs = $sequence->getEntityIDs();
                $lineSclIDs = preg_replace("/scl\:/","",$lineSclGIDs);
                if (strpos(join(' ',$lineSclIDs),":") !== false) {
                  array_push($hltherrors,"Error Physical Line Sequence ($seqLabel/seq:$seqID) not all entity GIDs are syllablecluster type. (".join(',',$lineSclGIDs).").");
                } else {
                  if (count($dups = array_intersect($sclIDs,$lineSclIDs))) {
                      array_push($hltherrors,"Error Physical Line Sequence ($seqLabel/seq:$seqID) has duplicate syllablecluster id (".join(',',$dups).").");
                  }
                  $sclIDs = array_unique(array_merge($sclIDs,$lineSclIDs));
                  foreach ($lineSclGIDs as $lineSclGID) {
                    $gid2SeqMap[$lineSclGID] = "seq:$seqID";
                  }
                }
              }
            }
          }
        }
        if ($verbose) {
          array_push($hltherrors,"**************** Processing SyllableClusters ***************************");
        }
        if ($continuousOut) {
          echo $hltherrors;
          $hltherrors = array();
        }
        //process syllables
        if ($sclIDs && count($sclIDs) > 0) {
          $condition = "scl_id in (".join(",",$sclIDs).")";
          $syllables = new SyllableClusters($condition,null,null,null);
          $syllables->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          if ($syllables && $syllables->getCount()>0) {
            $sclLinePos = 0;
            $lineSeqGID = null;
            $curLineSeqGID = null;
            $aksaraPos = "syl#?";
            foreach ($syllables as $syllable) {
              $sclID = $syllable->getID();
              $lineSeqGID = $gid2SeqMap[$syllable->getGlobalID()];
              if ($lineSeqGID != $curLineSeqGID) {//line transition so reset line variables
                $seqLabel =  $seqGID2Label[$lineSeqGID];
                $curLineSeqGID = $lineSeqGID;
                $sclLinePos = 1;
              } else {
                $sclLinePos++;
              }
              $aksaraPos = "syl#$sclLinePos";
              $ctxMessage = "Physical Line Sequence ($seqLabel/$lineSeqGID) with syllable link ($aksaraPos/scl:$sclID)";
              if (($index = array_search($sclID,$sclIDs)) !== false) {
                array_splice($sclIDs,$index,1);
              }
              if ($syllable->isMarkedDelete()) {
                array_push($hltherrors,"Error $ctxMessage that is marked for delete.");
                //ToDo:  add code to add <a> for a service to correct the issue.
              } else {
                $sclGraIDs = $syllable->getGraphemeIDs();
//               if (count($dups = array_intersect($sclGraphemeIDs,$sclGraIDs))) {
//                  array_push($hltherrors,"Error $ctxMessage with duplicate graIDs (".join(',',$dups).").");
//                }
                $sclGraphemeIDs = array_unique(array_merge($sclGraphemeIDs,$sclGraIDs));
                foreach ($sclGraIDs as $sclGraID) {
                  $gra2SclGID[$sclGraID] = "scl:$sclID";
                  $grapheme = new Grapheme($sclGraID);
                  if (!$grapheme || $grapheme->hasError()) {
                    array_push($hltherrors,"Error Unable to create graphene (".$grapheme->getGrapheme()."/gra:$sclGraID) located in $ctxMessage.".
                                ($grapheme->hasError()?"Errors: ".$grapheme->getErrors(true):""));
                  } else if ($grapheme->isMarkedDelete()) {
                      array_push($hltherrors,"Error $ctxMessage has grapheme (".$grapheme->getGrapheme()."/gra:$sclGraID) that is marked for delete.");
                      //ToDo:  add code to add <a> for a service to correct the issue.
                  }
                }
                //TODO: check valid syllable CCCHVMMMM
                $segment = $syllable->getSegment(true);
                if ($segment) {
                  $segGID = $segment->getGlobalID();
                  if ($segment->isMarkedDelete()) {
                    array_push($hltherrors,"Error Syllable ($aksaraPos/scl:$sclID) of Physical Line Sequence ($seqLabel/$lineSeqGID) is linked to segment ($segGID) that is marked for delete.");
                    //ToDo:  add code to add <a> for a service to correct the issue.
                  } else {
                    $segBlnIDs = $segment->getBaselineIDs();
                    if (!count($segBlnIDs)) {
                      array_push($hltherrors,"Error Syllable ($aksaraPos/scl:$sclID) of Physical Line Sequence ($seqLabel/$lineSeqGID) is linked to Segment ($segGID) which is missing baseline link.");
                    } else {
                      $blnIDs = array_unique(array_merge($blnIDs,$segBlnIDs));
                      foreach ($segBlnIDs as $blnID) {
                        if ( array_key_exists($blnID,$blnID2SclIDsMap)) {
                          array_push($blnID2SclIDsMap[$blnID],$sclID);
                        } else {
                          $blnID2SclIDsMap[$blnID] = array($sclID);
                        }
                      }
                    }
                  }
                } else {
                  array_push($hlthwarnings,"Warning Syllable ($aksaraPos/scl:$sclID) of Physical Line Sequence ($seqLabel/$lineSeqGID) has no segment.");
                }
              }
            }
            if ($sclIDs && count($sclIDs) > 0) {
              array_push($hltherrors,"Error Unread syllablecluster ids (".join(',',$sclIDs).").");
            }
            if ($blnIDs && count($blnIDs) > 0) {
              $condition = "bln_id in (".join(",",$blnIDs).")";
              $baselines = new Baselines($condition,null,null,null);
              $baselines->setAutoAdvance(false); // make sure the iterator doesn't prefetch
              if ($baselines && $baselines->getCount()>0) {
                foreach ($baselines as $baseline) {
                  $blnGID = $baseline->getGlobalID();
                  if ($baseline->isMarkedDelete()) {
                    array_push($hltherrors,"Error Baseline ($blnGID) that is marked for delete is linked to syllables (".join(",",$blnID2SclIDsMap[$baseline->getID()]).").");
                    //ToDo  add code to add <a> for a service to correct the issue.
                  } else {
                    if ($baseline->getType()==$imageBaselineTypeID) {//image baseline so check image
                      //TODO: segment bounds lay within image boundaries
                      //check image is valid
                    } else if ($baseline->getType()==$transBaselineTypeID) {//$transciption baseline
                      //TODO: segment should have valid string position and should have valid number relationship length = or <
                      //syllable value should exist in transcription
                      //baseline should have transcription
                    }
                    $blnSrfID = $baseline->getSurfaceID();
                    if (!$blnSrfID) {
                      if ( "Transcription"!= $baseline->getTermFromID($baseline->getType())) {
                        array_push($hlthwarnings,"Warning Baseline ($blnGID) is not linked to any surfaces.");
                      }
                      } else {
                      array_push($srfIDs,$blnSrfID);
                      $srfIDs = array_unique($srfIDs);
                      if ( array_key_exists($blnSrfID,$srfID2BlnIDsMap)) {
                        array_push($srfID2BlnIDsMap[$blnSrfID],$baseline->getID());
                      } else {
                        $srfID2BlnIDsMap[$blnSrfID] = array($baseline->getID());
                      }
                    }
                  }
                }
                if ($srfIDs && count($srfIDs) > 0) {
                  $condition = "srf_id in (".join(",",$srfIDs).")";
                  $surfaces = new Surfaces($condition,null,null,null);
                  $surfaces->setAutoAdvance(false); // make sure the iterator doesn't prefetch
                  if ($surfaces && $surfaces->getCount()>0) {
                    foreach ($surfaces as $surface) {
                      $srfID = $surface->getID();
                      $srfGID = $surface->getGlobalID();
                      if ($surface->isMarkedDelete()) {
                        array_push($hltherrors,"Error Baseline ($blnGID) linked to surface ($segGID) that is marked for delete.");
                        //ToDo:  add code to add <a> for a service to correct the issue.
                      } else {
                        $textIDs = $surface->getTextIDs();
                        if (!@$textIDs || !count($textIDs) || array_search($edition->getTextID(),$textIDs) === false) {
                          array_push($hltherrors,"Error baselines (".(@$srfID2BlnIDsMap[$srfID]?join(",",$srfID2BlnIDsMap[$srfID]):"").") linked to surface ($srfGID) with txtIDs (".(@$textIDs?join(",",$textIDs):"").") that is not linked to edition's text (txt:".$edition->getTextID().").");
                        }
                      }
                    }
                  }
                }
              }
            }
          } else {
            array_push($hltherrors,"Error Loading syllablecluster ids (".join(',',$sclIDs).") for Physical Line Sequences .".$sequences->getError());
          }
        }
        //process text division sequences
        if ($verbose) {
          array_push($hltherrors,"**************** Processing Text Division Sequences ***************************");
        }
        if ($continuousOut) {
          echo $hltherrors;
          $hltherrors = array();
        }
        if ($txtDivSeqIDs && count($txtDivSeqIDs) > 0) {
          $condition = "seq_id in (".join(",",$txtDivSeqIDs).")";
          $sequences = new Sequences($condition,null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          if ($sequences && $sequences->getCount()>0) {
            foreach ($sequences as $sequence) {
              $seqID = $sequence->getID();
              $seqLabel = $sequence->getLabel()?$sequence->getLabel():$sequence->getSuperScript();
              $seqGID2Label["seq:$seqID"] = $seqLabel;
              if ($sequence->isMarkedDelete()) {
                array_push($hltherrors,"Error Text Sequence ($txtSeqGID) has TextDivision Sequence link ($seqLabel/seq:$seqID) that is marked for delete.");
                //ToDo:  add code to add <a> for a service to correct the issue.
              }
              $txtDivGIDs = $sequence->getEntityIDs();
              if (!$txtDivGIDs) {
                array_push($hlthwarnings,"warning TextDivision Sequence ($seqLabel/seq:$seqID) is Empty");
              } else {
                $stripTokCmpGIDs = preg_replace("/(tok|cmp)\:/","",$txtDivGIDs);
                if (strpos(join(' ',$stripTokCmpGIDs),":") !== false) {
                  array_push($hltherrors,"Error TextDivision Sequence ($seqLabel/seq:$seqID) not all entity GIDs are token or compound type. (".join(',',$txtDivGIDs).").");
                } else {
                  if (count($dups = array_intersect($tokCmpGIDs,$txtDivGIDs))) {
                      array_push($hltherrors,"Error TextDivision Sequence ($seqLabel/seq:$seqID) has duplicate Tok/Cmp GIDs (".join(',',$dups).").");
                  }
                  $tokCmpGIDs = array_unique(array_merge($tokCmpGIDs,$txtDivGIDs));
                  foreach ($txtDivGIDs as $txtDivGID) {
                    $gid2SeqMap[$txtDivGID] = "seq:$seqID";
                  }
                }
              }
            }
          }
        }
        //process tokens and compounds
        if ($verbose) {
          array_push($hltherrors,"**************** Processing Tokens and Compounds ***************************");
        }
        if ($continuousOut) {
          echo $hltherrors;
          $hltherrors = array();
        }
        if ($tokCmpGIDs && count($tokCmpGIDs) > 0) {
          foreach ($tokCmpGIDs as $tokCmpGID) {
            $txtDivSeqGID = $gid2SeqMap[$tokCmpGID];
//            if (($index = array_search($tokCmpGID,$txtDivGIDs)) !== false) {
//              array_splice($txtDivGIDs,$index,1);
//            }
            $seqLabel =  $seqGID2Label[$txtDivSeqGID];
            $ctxMessage = "Text Division Sequence ($seqLabel/$txtDivSeqGID)";
            validateTokCmp($tokCmpGID,$ctxMessage,$tokCmpGID);
          }
        }
        //check syllable vs token graIDs
        if ($verbose) {
          array_push($hltherrors,"**************** Checking graIDs match for syllable and tokens ***************************");
        }
        if ($continuousOut) {
          echo $hltherrors;
          $hltherrors = array();
        }
        while ($graID = array_shift($sclGraphemeIDs)) {
          $index = array_search($graID,$hlthtokGraphemeIDs);
          if ($index === false) {//syllable grapheme not found in any token
            //find sclGID
            $sclGID = $gra2SclGID[$graID];
            //find seqGID and label
            $seqGID = $gid2SeqMap[$sclGID];
            $seqLabel = $seqGID2Label[$seqGID];
            //write out error message
            array_push($hltherrors,"Error Physical Line Sequence ($seqLabel/$seqGID) has syllable ($sclGID) with grapheme (gra:$graID) that is not contained in a token.");
          } else {
            array_splice($hlthtokGraphemeIDs,$index,1);
          }
        }
        if (count($hlthtokGraphemeIDs)) {//we have token graphemes that are not in a syllable
          foreach ($hlthtokGraphemeIDs as $graID) {
            //find the token GID
            $tokGID = $hlthgra2TokGID[$graID];
            //find the token context
            $ctxLabel = $hlthtokGID2CtxLabel[$tokGID];
            //write out error message
            array_push($hltherrors,"Error $ctxLabel has token ($tokGID) that has a grapheme (gra:$graID) that is not in a syllable.");
          }
        }
        //process structure sequences
        if ($verbose) {
          array_push($hltherrors,"**************** Processing Structural Analysis Sequences ***************************");
        }
        if ($continuousOut) {
          echo $hltherrors;
          $hltherrors = array();
        }
        if ($structuralSeqIDs && count($structuralSeqIDs) > 0) {
          $processedSeqIDs = array();
          while ($seqID = array_shift($structuralSeqIDs)) {
            $sequence = new Sequence($seqID);
            if ($sequence->hasError()) {
              array_push($hltherrors,"Error Structural Sequence (seq:$seqID) cannot be loaded. Error:".$sequence->getErrors(true));
              break;
            }
            $seqGID = $sequence->getGlobalID();
            $seqLabel = $sequence->getLabel()?$sequence->getLabel():$sequence->getSuperScript();
            $seqGID2Label[$seqGID] = $seqLabel;
            if ($sequence->isMarkedDelete()) {
              array_push($hltherrors,"Error Structural Sequence ($seqLabel/seq:$seqID) is marked for delete.");
              //ToDo:  add code to add <a> for a service to correct the issue.
            }
            $seqEntityGIDs = $sequence->getEntityIDs();
            if (!$seqEntityGIDs || count($seqEntityGIDs) == 0) {
              array_push($hlthwarnings,"Warnings Structural Sequence (seq:$seqID) has no entity ids.");
              break;
            }
            foreach ($seqEntityGIDs as $seqEntityGID) {
              $prefix =substr($seqEntityGID,0,3);
              switch ($prefix) {
                case 'seq':
                  $subSeqID = substr($seqEntityGID,4);
                  $subsequence = new Sequence($subSeqID);
                  if ($subsequence->hasError()) {
                    array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has subsequence $seqEntityGID with loading error. Error:".$subsequence->getErrors(true));
                    break;
                  }
                  $subSeqLabel = $subsequence->getLabel()?$subsequence->getLabel():$subsequence->getSuperScript();
                  $seqGID2Label[$seqEntityGID] = $subSeqLabel;
                  if ($sequence->isMarkedDelete()) {
                    array_push($hltherrors,"Error Structural Sequence ($subSeqLabel/$seqEntityGID) is marked for delete.");
                    break;
                    //ToDo:  add code to add <a> for a service to correct the issue.
                  }
                  if ( $seqGID == $seqEntityGID ) {//recursion
                    array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has $seqEntityGID as contained entity (recursion)");
                  } else {
                    if (array_key_exists($seqEntityGID,$processedSeqIDs)) {
                      array_push($hlthwarnings,"Warning Structural Sequence ($seqLabel/$seqGID) has child $subSeqLabel/$seqEntityGID already processed as child of ".join(",",$processedSeqIDs[$seqEntityGID]));
                      array_push($processedSeqIDs[$seqEntityGID],$seqGID);
                    } else {
                      if (!in_array($subSeqID,$structuralSeqIDs)) {
                        array_push($structuralSeqIDs,$subSeqID); //walk tree
                      }
                      $processedSeqIDs[$seqEntityGID] = array($seqGID);
                    }
                  }
                  break;
                case 'tok':
                  if (!in_array($seqEntityGID,$tokCmpGIDs)) {// structure with non edition tok/cmp
                    array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) contains token $seqEntityGID which is not part of the edition.");
                    $token = new Token(substr($seqEntityGID,4));
                    if ($token->hasError()) {
                      array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has token $seqEntityGID with loading error. Error:".$token->getErrors(true));
                      break;
                    }
                    if ($token->isMarkedDelete()) {
                      array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has token $seqEntityGID which is marked for delete.");
                      break;
                      //ToDo:  add code to add <a> for a service to correct the issue.
                    }
                    if (count($token->getGraphemeIDs())==0) {
                      array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has token $seqEntityGID which has no graphemes.");
                    }
                  }
                  break;
                case 'cmp':
                  if (!in_array($seqEntityGID,$tokCmpGIDs)) {// structure with non edition tok/cmp
                    array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) contains compound $seqEntityGID which is not part of the edition.");
                    $compound = new Compound(substr($seqEntityGID,4));
                    if ($compound->hasError()) {
                      array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has compound $seqEntityGID with loading error. Error:".$compound->getErrors(true));
                      break;
                    }
                    if ($compound->isMarkedDelete()) {
                      array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has compound $seqEntityGID which is marked for delete.");
                      break;
                      //ToDo:  add code to add <a> for a service to correct the issue.
                    }
                    if (count($compound->getComponentIDs())==0) {
                      array_push($hltherrors,"Error Structural Sequence ($seqLabel/$seqGID) has compound $seqEntityGID which has no components.");
                    }
                  }
                  break;
              }//end switch
            }//end for each seqGIDs
          }//end while seqID
        }
      }
    }
  }
  if ($verbose || ($errorsOnly && (count($hltherrors) > 0))) {
    $retStr .= "\t\t\t Health Report for (edn$ednID) - ".$edition->getDescription()."\n";
  }

  if (!$errorsOnly && count($hlthwarnings) > 0) {
    if ($verbose) {
      $retStr .= "WARNING:\n";
    }
    foreach ($hlthwarnings as $warning) {
      $retStr .= $warning."\n";
    }
  }
  if (count($hltherrors) > 0) {
    if ($verbose) {
      $retStr .= "ERRORS:\n";
    }
    foreach ($hltherrors as $error) {
      $retStr .= $error."\n";
    }
  }
  if ($verbose && count($hlthwarnings) == 0 && count($hltherrors) == 0) {
    $retStr .= " Edition links check ok for edition (edn$ednID).";
  }
  if ($verbose || $errorsOnly && count($hltherrors) > 0) {
    $retStr .= "\t\t\t End of Health Report for (edn$ednID) - ".$edition->getDescription()."\n\n";
  }

  return $retStr;
}

/**
* check the health of the catalog
*
* walk through all entities of this catalog and validate linked entities
*
* @param int $catID catalog ID
* @param boolean $verbose indicate the level of output information.
*/


function checkGlossaryHealth($catID, $verbose = true) {
  global $hltherrors, $hlthwarnings, $hlthgra2TokGID, $hlthtokGID2CtxLabel, $hlthtokGraphemeIDs;

  $retStr = "";
  $hltherrors = array();
  $hlthwarnings = array();
  $lemTag2Value = array();
  $hlthgra2TokGID = array();
  $hlthtokGID2CtxLabel = array();
  $tokCmp2LemGID = array();
  $tokCmp2InfGID = array();
  $lemIDs = array();
  $infIDs = array();
  $tokCmpGIDs = array();
  $processedTokIDs = array();
  $catalog = null;
  if ($catID) {
    $catalog = new Catalog($catID);
  }
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    array_push($hlthwarnings,"Usage = testGlossaryLinks.php?db=dbnameGoesHere&catID=idOfCatalogGlossaryGoesHere.");
 } else {
    $termInfo = getTermInfoForLangCode('en');
    $glossaryCatalogTypeID = $termInfo['idByTerm_ParentLabel']['glossary-catalogtype'];//term dependency
    $textSeqTypeID = $termInfo['idByTerm_ParentLabel']['text-sequencetype'];//term dependency
    $textDivSeqTypeID = $termInfo['idByTerm_ParentLabel']['textdivision-text'];//term dependency
    $textPhysSeqTypeID = $termInfo['idByTerm_ParentLabel']['textphysical-sequencetype'];//term dependency
    $linePhysSeqTypeID = $termInfo['idByTerm_ParentLabel']['linephysical-textphysical'];//term dependency
    $imageBaselineTypeID = $termInfo['idByTerm_ParentLabel']['image-baselinetype'];//term dependency
    $transBaselineTypeID = $termInfo['idByTerm_ParentLabel']['transcription-baselinetype'];//term dependency
    $catID = $catalog->getID();
    $lemmas = new Lemmas("lem_catalog_id = $catID",'lem_id',null,null);
    if ($lemmas && $lemmas->getCount()>0) {
      if ($verbose) {
        array_push($hltherrors,"**************** Processing Glossary Lemmas ***************************");
      }
      foreach ($lemmas as $lemma) {
        $lemID = $lemma->getID();
        $lemValue = $lemma->getValue();
        $lemTag2Value["lem$lemID"] = $catID;
        if ($lemma->isMarkedDelete()) {
          array_push($hltherrors,"Error glossary (cat:$catID) has lemma ($lemValue/lem$lemID) that is marked for delete.");
          //ToDo  add code to add <a> for a service to correct the issue.
          continue;
        } else {//save to check components
          array_push($lemIDs,$lemID);
        }
        $relatedGIDsByLinkType = $lemma->getRelatedEntitiesByLinkType();
        $seeLinkTypeID = Entity::getIDofTermParentLabel('See-LemmaLinkage');
        $cfLinkTypeID = Entity::getIDofTermParentLabel('Compare-LemmaLinkage');
        $relatedNode = null;
        if ($relatedGIDsByLinkType && array_key_exists($seeLinkTypeID,$relatedGIDsByLinkType)) {
          foreach ($relatedGIDsByLinkType[$seeLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            if (!$entity || $entity->hasError() || $entity->isMarkedDelete()) {
              array_push($hltherrors,"Lemma ($lemValue/lem$lemID) has link to invalid entity $linkGID.");
            }
          }
        }
        if ($relatedGIDsByLinkType && array_key_exists($cfLinkTypeID,$relatedGIDsByLinkType)) {
          foreach ($relatedGIDsByLinkType[$cfLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            if (!$entity || $entity->hasError() || $entity->isMarkedDelete()) {
              array_push($hltherrors,"Lemma ($lemValue/lem$lemID) has link to invalid entity $linkGID.");
            }
          }
        }
      }
      //process lemma components
      if ($verbose) {
        array_push($hltherrors,"**************** Processing Lemma Components ***************************");
      }
      if ($lemIDs && count($lemIDs) > 0) {
        $lemmas->rewind();
        foreach ($lemmas as $lemma) {
          $entGIDs = $lemma->getComponentIDs();
          $lemValue = $lemma->getValue();
          $lemTag = $lemma->getEntityTag();
          if (!$entGIDs || count($entGIDs) == 0) {
            array_push($hlthwarnings,"Warning lemma ($lemValue/$lemTag) that is has no attested forms.");
            continue;
          }
          foreach ($entGIDs as $entGID) {
            $entTypeID = substr($entGID,0,3);
            if ($entTypeID == "inf") {
              if (!@$infIDs[$entGID]) {
                $infIDs[$entGID] = $lemTag;
              } else {
                array_push($hltherrors,"Inflection $entGID already a component of ".$infIDs[$entGID]);
              }
            } else {
              if (!@$tokCmpGIDs[$entGID]) {
                $tokCmpGIDs[$entGID] = $lemTag;
              } else {
                array_push($hltherrors,"Processing Token/Compound $entGID for $lemTag already a component of ".$tokCmpGIDs[$entGID]);
              }
            }
          }
        }
        if ($verbose) {
          array_push($hltherrors,"**************** Processing Inflections ***************************");
        }
        if ($infIDs && count($infIDs) > 0) {
          foreach ($infIDs as $infGID => $lemTag) {
            $inflection = new Inflection(substr($infGID,4));
            $infTag = $inflection->getEntityTag();
            if (!$inflection || $inflection->hasError()) {
              array_push($hltherrors,"Error Unable to create inflection ($infGID) for lemma $lemTag.".
                          (($inflection && $inflection->hasError())?"Errors: ".$inflection->getErrors(true):""));
              continue;
            }
            if ($inflection->isMarkedDelete()) {
              array_push($hlthwarnings,"Warning glossary (cat:$catID) Lemma ($lemValue/$lemTag) has inflection $infTag that is marked for delete.");
              continue;
            }
            $entGIDs = $inflection->getComponentIDs();
            if (!$entGIDs || count($entGIDs) == 0) {
              array_push($hltherrors,"Error glossary (cat:$catID) Lemma ($lemTag) has inflection $infTag that has no attested forms.");
              continue;
            }
            foreach ($entGIDs as $entGID) {
              if (!@$tokCmpGIDs[$entGID]) {
                $tokCmpGIDs[$entGID] = $infTag;
              } else {
                array_push($hltherrors,"Error glossary (cat:$catID) Token/Compound $entGID of $infTag (of $lemTag) is already a component of ".$tokCmpGIDs[$entGID]);
              }
            }
          }
        }
        if ($verbose) {
          array_push($hltherrors,"**************** Processing Tokens and Compounds ***************************");
        }
        if ($tokCmpGIDs && count($tokCmpGIDs) > 0) {
          foreach ($tokCmpGIDs as $tokCmpGID => $cntTag) {
            if (strlen($tokCmpGID)< 4) {
              array_push($hltherrors,"TokGIDs has invalid tag for Container $cntTag.");
              continue;
            }
            $ctxMessage = "Containing Entity ($cntTag)";
            validateTokCmp($tokCmpGID,$ctxMessage,$tokCmpGID);
          }
        }
      }
    }
  }
  if ($verbose) {
    $retStr .= "\t\t\t Health Report for (cat$catID) - ".$catalog->getTitle()."\n\n";
  }

  if (count($hlthwarnings) > 0) {
    if ($verbose) {
      $retStr .= "WARNING:\n";
    }
    foreach ($hlthwarnings as $warning) {
      $retStr .= $warning."\n";
    }
  }
  if (count($hltherrors) > 0) {
    if ($verbose) {
      $retStr .= "ERRORS:\n";
    }
    foreach ($hltherrors as $error) {
      $retStr .= $error."\n";
    }
  }
  if (count($hlthwarnings) == 0 && count($hltherrors) == 0) {
    $retStr .= " Catalog links check ok for catalog (cat$catID).";
  }
  if ($verbose) {
    $retStr .= "\t\t\t End of Health Report for (cat$catID) - ".$catalog->getTitle()."\n\n";
  }

  return $retStr;
}


/**
* synchronise clip images for each segment of each baseline.
*
* @param int array $blnIDs of baseline ids to scope the set of segments to synch
* @param boolean $reclipSegments indicating whether to resynch even existing cached images
*
* @return mixed array of $success, $errors strings, array warnings strings and $log string.
*/

function synchSegmentImageCache($blnIDs = null, $reclipSegments = false) {
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $warnings = array();
  if (count($blnIDs) == 0) {
    $log = "Segment Image Cache Synch process requires at least one baseline id  'blnID=123'.\n";
  } else {
    $imgBlnTypeID = Entity::getIDofTermParentLabel("image-baselinetype");
    $log = "Start Segment Image Cache Synch process.\n";
    foreach ($blnIDs as $blnID) {
      $baseline = new Baseline($blnID);
      if (!$baseline || $baseline->hasError() || $baseline->getType() != $imgBlnTypeID) {//no baseline or unavailable so warn
        $log .= "Warning no valid baseline available for id $blnID .\n";
      } else {
        $image = $baseline->getImage(true);
        // if baseline has a boundary then get bounding box to translate segment points to the origin of the bounding box
        if($baseline->getImageBoundary()){
          $blnBBox = new BoundingBox($baseline->getImageBoundary());
        } else {
          $blnBBox = null;
        }
        // if the image has a boundary then get bounding box to translate segment points to the origin of the bounding box
        if($image && $image->getBoundary()){
          $imgBBox = new BoundingBox($image->getBoundary());
        } else {
          $imgBBox = null;
        }
        foreach ($baseline->getSegments() as $segment) {
          $boundary = $segment->getImageBoundary();
          if ($boundary) {
            $polyCnt = 0;
            $segFilenameBase = SEGMENT_CACHE_BASE_PATH.DBNAME."seg".$segment->getID();
            $urlCnt = 0;
            foreach ($boundary as $polygon){// this code assumes baseline IDs is a parallel array with polygons
              $urlCnt++;
              if (!$reclipSegments) { //check cached first
                $segFilename = $segFilenameBase.($urlCnt>1?"_part$urlCnt":"").".png";
                if (file_exists($segFilename)) {
                  $log .= "$segFilename exist skipping \n";
                  continue;
                }
              }
              if($blnBBox){
                $polygon->translate($blnBBox->getXOffset(),$blnBBox->getYOffset());
              }
              if($imgBBox){
                $polygon->translate($imgBBox->getXOffset(),$imgBBox->getYOffset());
              }
              $log .= "clipping image for ".$segment->getGlobalID()." \n";
              $segment->cacheSegmentImages(array(constructCroppedImageURL($image->getURL(),$polygon)));
            }
          }// end for boundary
        }
      }
    }
  }
  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  $retVal["log"] = $log;
  return $retVal;
}

/**
* validate the structure of tokens or compounds
*
* @param mixed $tokCmpGID global ID of token or compound
* @param mixed $ctxMessage context information to be used in error messages in recursive calls
* @param mixed $topTokCmpGID global ID of the top level compound or token
*/


/**
* merge data from ordinal tagged image segments of one or more baselines (order by baseline id then ordinal)
* into the segments of the db starting in segment id order.
*
* @param int array $blnIDs of baseline ids to scope the set of segments to merge from
* @param boolean $deleteAfterMerge indicating whethter to delete the segment from the db after merging it's data
*
* @return mixed array of $success, $errors strings, array warnings strings and $log string.
*/

function reorderSegments($blnIDs = null, $deleteAfterMerge = true) {
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $warnings = array();
  $log = "Start reorder morphying process.\n";
  // get an ordered list of segment IDs for the base lines supplied or for the entire database.
  $query = "select seg_id, seg_baseline_ids[1] as blnID, ".
                  "substring(seg_scratch from '(?:\"blnOrdinal\":\")(\\d+)\"')::int as ord ".
           "from segment where seg_scratch like '%blnOrdinal%' and seg_image_pos is not null";
  if (isset($data['blnIDs'])) {
    $query .= " and seg_baseline_ids[1] = ANY(".$data['blnIDs'].") order by blnID,ord";//todo needs to be changed for cross baseline segmentation
  } else {
    $query .= " order by blnID,ord";
  }
  $log .= "query = '$query'\n";
  $dbMgr->query($query);
  $ordSegIDs = array();
  $usedOrdSegIDs = array();
  $ordCnt = $dbMgr->getRowCount();
  if ($ordCnt == 0) {
    array_push($errors,"no ordinals found in scratch of any segments");
  } else {
    $lastOrd = null;
    while ($row = $dbMgr->fetchResultRow()) {
      if ($lastOrd == $row['ord']) {
        $msg = " found duplicate ordinal ".$row['ord']." on segment ".$row['seg_id'];
        $log .= $msg."\n";
        array_push($warnings,$msg);
      }
      $lastOrd = $row['ord'];
      array_push($ordSegIDs, $row['seg_id']);
    }
  }

  if (count($errors) == 0) {
    $segments = new Segments(null,'seg_id',null,null);
    if ($segments->getError()) {
      array_push($errors,"Error loading segments error: ".$segments->getError());
    } else if ($segments->getCount() < ($ordCnt * 2)) {
      array_push($errors,"Error segment count mismatch database segment count (".$segments->getCount().") should be at least twice the count ($ordCnt) of ordered segments");
    } else {
      foreach ($ordSegIDs as $segID) {
        $srcSegment = $segments->searchKey($segID);
        $trgSegment = $segments->current();
        if (!$srcSegment) {
          array_push($errors,"unable to find source segment for seg:$segID");
        } else if ($srcSegment->isReadonly()) {
//          array_push($errors,"source segment for seg:$segID is read only");
        }
        if (!$trgSegment) {
          array_push($errors,"no target segment for seg:$segID");
        } else if ($trgSegment->isReadonly()) {
//          array_push($errors,"target segment for seg:$segID is read only");
        }
        if (count($errors)) {
          break;
        }
        $trgSegID = $trgSegment->getID();
        $log .= "Attempting to merge data from source seg$segID into target seg$trgSegID.\n";
        $trgSegment->setBaselineIDs($srcSegment->getBaselineIDs());
        $trgSegment->setImageBoundary($srcSegment->getImageBoundary());
        if ($trgSegment->getStringPos()) {
          $trgSegment->setStringPos(null);
        }
        $trgSegment->save();
        if ($trgSegment->hasError()) {
          array_push($errors,"Error saving target segment id = ".$trgSegment->getID()." errors - ".join(",",$trgSegment->getErrors()));
          break;
        }
        $srcSegment->markForDelete();
        if ($srcSegment->hasError()) {
          array_push($errors,"Error deleting source segment id = ".$srcSegment->getID()." errors - ".join(",",$srcSegment->getErrors()));
          break;
        }
        array_push($usedOrdSegIDs, $srcSegment->getID());
        $log .= "Successfully merged data from source seg$segID into target seg$trgSegID.\n";
        $segments->next();
      }
      if ($deleteAfterMerge && count($errors) == 0) {//can remove ordinal segments, todo check if there is overlap in source and target segments
        $query = "delete from segment where seg_id in (".join(",",$usedOrdSegIDs).")";
        $dbMgr->query($query);
      }
    }
  }
  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  $retVal["log"] = $log;
  return $retVal;
}

/**
* validate the structure of tokens or compounds
*
* @param mixed $tokCmpGID global ID of token or compound
* @param mixed $ctxMessage context information to be used in error messages in recursive calls
* @param mixed $topTokCmpGID global ID of the top level compound or token
*/

function validateTokCmp ($tokCmpGID, $ctxMessage, $topTokCmpGID) {
  global $hltherrors, $hlthwarnings, $hlthtokGraphemeIDs, $hlthgra2TokGID, $hlthtokGID2CtxLabel;
  $entity = EntityFactory::createEntityFromGlobalID($tokCmpGID);
  if (!$entity || $entity->hasError()) {
    array_push($hltherrors,"Error Unable to create tok/cmp ($tokCmpGID) located in $ctxMessage.".
                (($entity && $entity->hasError())?"Errors: ".$entity->getErrors(true):EntityFactory::$error));
  } else {
     if (!$entity->getID()) { //failed to create entity
      $getIDRet = $entity->getID();
      array_push($hltherrors,"Error Unable to create tok/cmp ($tokCmpGID) id is ($getIDRet)-> invalid attestation located in $ctxMessage.");
      return;
    }
    if ($entity->isMarkedDelete()) {
      array_push($hltherrors,"Error $ctxMessage has token/compound link ($tokCmpGID) that is marked for delete.");
      //ToDo:  add code to add <a> for a service to correct the issue.
    } else {// process each token or compound depth first
      $label = $entity->getValue();
      if(is_null($label) || $label === '' || strlen($label) == 0) {
        array_push($hltherrors,"Error tok/cmp ($tokCmpGID) located in $ctxMessage has no value.");
      }
      $newCtxMessage = "$ctxMessage, token/compound ($label/$tokCmpGID)";
      $prefix = $entity->getEntityTypeCode();
      $id = $entity->getID();
      if ($prefix == "cmp") {//process components
        $componentGIDs = $entity->getComponentIDs();
        if (count($componentGIDs)) {
          foreach ($componentGIDs as $componentGID) {
            if ($componentGID == "cmp:$id") {
               continue;
            }
            validateTokCmp($componentGID,$newCtxMessage,$topTokCmpGID);//**********RECURSION*********
          }
        } else {
          array_push($hltherrors,"Error $ctxMessage has compound link ($tokCmpGID) missing components.");
        }
      } else if ($prefix == "tok") {//token so process graphemes
        $tokGraIDs = $entity->getGraphemeIDs();
        if ($tokGraIDs && count($tokGraIDs) > 0) {
          $dups = array_intersect($hlthtokGraphemeIDs,$tokGraIDs);//check for repeated graID could be sandhi
          foreach ($tokGraIDs as $graID) {
            $grapheme = new Grapheme($graID);
            $hlthgra2TokGID[$graID] = $tokCmpGID;
            $hlthtokGID2CtxLabel[$tokCmpGID] = $newCtxMessage;
            if (!$grapheme || $grapheme->hasError()) {
              array_push($hltherrors,"Error Unable to create graphene (".$grapheme->getGrapheme()."/gra:$graID) located in $newCtxMessage.".
                          ($grapheme->hasError()?"Errors: ".$grapheme->getErrors(true):""));
            } else {
              if ($grapheme->isMarkedDelete()) {
                array_push($hltherrors,"Error $newCtxMessage has grapheme (".$grapheme->getGrapheme()."/gra:$graID) that is marked for delete.");
                //ToDo:  add code to add <a> link for a service to correct the issue.
              }
              if ($dups && in_array($graID,$dups)) {
                if (!$grapheme->getDecomposition()) {
                  array_push($hltherrors,"Error $newCtxMessage with duplicate grapheme (".$grapheme->getGrapheme()."/gra:$graID) without sandhi decomposition.");
                }
              } else {
                array_push($hlthtokGraphemeIDs,$graID);
              }
            }
          }
        } else {
          array_push($hltherrors,"Error $newCtxMessage has no graphemes.");
          //todo remove this from seqeunce and mark for delete
        }
      } else {//unknown
        array_push($hltherrors,"Error $ctxMessage has non tok/cmp link type ($tokCmpGID).");
      }
    }
  }
}

/*
Adapted from Jeroen van den Broek contribution on
May 3, 2012  post on https://spin.atomicobject.com/2010/08/25/rendering-utf8-characters-in-rich-text-format-with-php/
*/
function utf8ToRtf($utf8_text) {
  if (mb_check_encoding($utf8_text)) {
    return preg_replace_callback("/([\\xC2-\\xF4][\\x80-\\xBF]+)/", 'FixUnicodeForRtf', $utf8_text);
  } else {
    error_log("utf8ToRtf passed invalid mb string $utf8_text");
    return $utf8_text;
  }
}

function FixUnicodeForRtf($matches) {
  return "\\u".hexdec(bin2hex(iconv('UTF-8', 'UTF-16BE', $matches[1]))).'?';
}

mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
/**
* multibyte safe string replace function
*
* @param mixed $srchStrings search for string that will be replaced
* @param mixed $rplcStrings replacement string
* @param mixed $mbStr subject string
*/
function mbPregReplace($srchStrings,$rplcStrings,$mbStr) {
  $cnt = count($srchStrings);
  $mbStrReplaced = $mbStr;
  for ($i=0; $i<$cnt; $i++) {
    $mbStrReplaced = mb_ereg_replace($srchStrings[$i],$rplcStrings[$i],$mbStrReplaced);
  }
  return $mbStrReplaced;
}


function findSclGIDsFromPattern($pattern,$sclGIDsByLinePostion) {
  $sclGIDs = array();
  $lineOrds = array();
  $sclOrds = array();
  list($lpattern,$spattern) = explode(":",$pattern);
  if (!$lpattern || $lpattern == "L*") {//all lines pattern
    $lineOrds = array_keys($sclGIDsByLinePostion);
  } else if (preg_match("/L(\d+)(\+|\-)(\d+)/",$lpattern,$matches)) {//every N lines or range pattern
      $lineOrd = $matches[1]-1;
      if ($matches[2] == "+") {//every N
        $ordInc = $matches[3];
        $limitOrd = count($sclGIDsByLinePostion);
      } else {// range
        $limitOrd = $matches[3];
        $ordInc = 1;
      }
      while ($lineOrd < $limitOrd) {
        array_push($lineOrds,$lineOrd);
        $lineOrd += $ordInc;
      }
  } else {//simple line ordinal case
    $lineOrds = array(substr($lpattern,1)-1);
  }
  $maxLine = 0;
  foreach ($sclGIDsByLinePostion as $lineOfSclIDs) {
    $maxLine = max($maxLine,count($lineOfSclIDs));
  }
  if (!$spattern || $spattern == "S*") {//all syllables
//    $lineOrds = array_keys($sclGIDsByLinePostion);
    for ($i=0;$i < $maxLine; $i++) {
      array_push($sclOrds,$i);//load ordinals to the max as overrun is ignored
    }
  } else if (preg_match("/S(\d+)(\+|\-)(\d+)/",$spattern,$matches)) {//every N lines or range pattern
      $sclOrd = $matches[1]-1;
      if ($matches[2] == "+") {//every N
        $ordInc = $matches[3];
        $limitOrd = $maxLine;
      } else {// range
        $limitOrd = $matches[3];
        $ordInc = 1;
      }
      while ($sclOrd < $limitOrd) {
        array_push($sclOrds,$sclOrd);
        $sclOrd += $ordInc;
      }
  } else {//simple line ordinal case
    $sclOrds = array(substr($spattern,1)-1);
  }
  foreach ($lineOrds as $lOrd) {
    $line = $sclGIDsByLinePostion[$lOrd];
    if ($line) {
      $maxOrd = count($line);
      foreach ($sclOrds as $sOrd) {
        if ($sOrd < $maxOrd) {//skip overrun
          $sclGID = $line[$sOrd];
          if ($sclGID) {
            array_push($sclGIDs,$sclGID);
          }
        }
      }
    }
  }
  return $sclGIDs;
}

function getUserGroupIDforName($name) {
  $dbMgr = new DBManager();
  $dbMgr->query("select ugr_id from usergroup where ugr_name = '$name';");
  $row = $dbMgr->fetchResultRow();
  return $row?$row['ugr_id']:null;
}

function clearSessionCatCache() {
  foreach(array_keys($_SESSION) as $key) {
    if (preg_match('/^cache-cat\d+'.DBNAME.'/',$key)) {
      unset($_SESSION[$key]);
    }
  }
}

function getToksBoundaryQueryString($tokIDs){
  if (is_array($tokIDs)) {
    $subQueries = array();
    foreach ($tokIDs as $tokID){
      array_push($subQueries,
        "(select scl_id,seg_baseline_ids,array_position(p.seq_entity_ids::text[],concat('seq:',c.seq_id)::text) as lineOrd, ".
                "array_position(c.seq_entity_ids::text[],concat('scl:',scl_id)::text) as sylOrd, ".
                "c.seq_id, seg_scratch::json->>'ordinal' as ord, seg_image_pos ".
         "from token left join syllablecluster on scl_grapheme_ids && tok_grapheme_ids ".
                    "left join segment on seg_id = scl_segment_id ".
                    "left join sequence c on concat('scl:',scl_id) = ANY(c.seq_entity_ids) ".
                    "left join sequence p on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids) ".
         "where tok_id = $tokID and scl_segment_id is not null and ".
                "not scl_owner_id = 1)");
    }
    //lineOrd is position in edition while ord is multiple derived edition ordering of the same line
    return join(" union all ",$subQueries)." order by lineOrd,sylOrd,ord;";
  } else {
    $tokID = $tokIDs;
    return "select scl_id,seg_baseline_ids,array_position(p.seq_entity_ids::text[],concat('seq:',c.seq_id)::text) as lineOrd, ".
                  "array_position(c.seq_entity_ids::text[],concat('scl:',scl_id)::text) as sylOrd, ".
                  "c.seq_id, seg_scratch::json->>'ordinal' as ord, seg_image_pos ".
           "from token left join syllablecluster on scl_grapheme_ids && tok_grapheme_ids ".
                      "left join segment on seg_id = scl_segment_id ".
                      "left join sequence c on concat('scl:',scl_id) = ANY(c.seq_entity_ids) ".
                      "left join sequence p on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids) ".
           "where tok_id = $tokID and scl_segment_id is not null and ".
                  "not scl_owner_id = 1 ".
    //lineOrd is position in edition while ord is multiple derived edition ordering of the same line
           "order by lineOrd,sylOrd,ord;";
  }
}

function getWordsBaselineInfo($tokIDs) {
  if (!$tokIDs) {
    return null;
  }
  $dbMgr = new DBManager();
  if (!$dbMgr || $dbMgr->getError()) {
    error_log("error loading dataManager");
    return null;
  }
  $dbMgr->query(getToksBoundaryQueryString($tokIDs));
  if ($dbMgr->getError()) {
    error_log("error from querying ".print_r($tokIDs)." ".$dbMgr->getError());
    return null;
  } else if ($dbMgr->getRowCount() < 1) {
    error_log("error from querying ".print_r($tokIDs)." row count is 0");
    return null;
  } else {
    $wrdBlnPolygons = array();
    $accPoints = array();//accumulated points
    $wrdBlnScrollTop = array();
    $curBlnTag = null;
    $curSclID = null;
    $prevLineOrd = null;
    $lineOrd = null;
    while ($row = $dbMgr->fetchResultRow()) {
      if ($row["scl_id"] == $curSclID) {//skip multiple uses of syllable for multi-edition case
        continue;
      } else {
        $curSclID = $row["scl_id"];
      }
      $segPoints = array();
      $blnTag = "bln".(Entity::idsStringToArray($row['seg_baseline_ids'])[0]);
      $polygons = Entity::polyStringToArray($row["seg_image_pos"]);
      $lineOrd = $row["lineord"];
      if (!$prevLineOrd){//first
        $prevLineOrd = $lineOrd;
      }
      if ($polygons && count($polygons) > 0) {
        $polygon = $polygons[0];
        if (is_a($polygon,'Polygon')) {
          $segPoints = $polygon->getBoundingRect();
        } else {
          $segPoints = getBoundingRect($polygon);
        }
      } else {
        continue;
      }
      if ($curBlnTag && $curBlnTag != $blnTag || $prevLineOrd != $lineOrd) {
        if (count($accPoints) > 0) {//existing point accumulation so save bounding bbox
          if (!array_key_exists($curBlnTag,$wrdBlnPolygons)) {
            $bBox = pointsArray2ArrayOfTuples(getBoundingRect($accPoints));
            $wrdBlnPolygons[$curBlnTag] = array($bBox);
            $wrdBlnScrollTop = array('blnTag'=>$curBlnTag,'x'=>$bBox[0][0],'y'=>$bBox[0][1],'h'=>($bBox[2][1]-$bBox[0][1]));
          } else {
            array_push($wrdBlnPolygons[$curBlnTag],pointsArray2ArrayOfTuples(getBoundingRect($accPoints)));
          }
          $accPoints = array();
        }
        $curBlnTag = $blnTag;
        $prevLineOrd = $lineOrd;
      } else if (!$curBlnTag) {
        $curBlnTag = $blnTag;
      }
      // aggregate segment points
      $accPoints = array_merge($accPoints,$segPoints);
    }
    if (count($accPoints) > 0) {//existing point accumulation so save bounding bbox no bln/line change case
      $bBox = pointsArray2ArrayOfTuples(getBoundingRect($accPoints));
      if (!array_key_exists($curBlnTag,$wrdBlnPolygons)) {
        $wrdBlnPolygons[$curBlnTag] = array($bBox);
        if (count($wrdBlnScrollTop) == 0) {
          $wrdBlnScrollTop = array('blnTag'=>$curBlnTag,'x'=>$bBox[0][0],'y'=>$bBox[0][1],'h'=>($bBox[2][1]-$bBox[0][1]));
        }
      } else {
        array_push($wrdBlnPolygons[$curBlnTag],pointsArray2ArrayOfTuples(getBoundingRect($accPoints)));
      }
      $accPoints = array();
    }
    return array($wrdBlnPolygons,$wrdBlnScrollTop);
  }
}

function getEntityFootnoteInfo($entity, $fnTypeIDs, $refresh = false) {
  $fnInfo = null;
  $fnHtml = $entity->getScratchProperty("fnHtml");
  $fnTextByAnoTag = $entity->getScratchProperty("fnTextByAnoTag");
  if (!$fnHtml || !$fnTextByAnoTag || $refresh) {
    $fnTextByAnoTag = null;
    if ($linkedAnoIDsByType = $entity->getLinkedAnnotationsByType()) {
      foreach ($fnTypeIDs as $typeID) {
        if (array_key_exists($typeID,$linkedAnoIDsByType)) {
          $type = strtolower( Entity::getTermFromID($typeID));
          $typeTag = "trm".$typeID;
          if (!$fnTextByAnoTag) {
            $fnTextByAnoTag = array();
            $fnHtml = "";
            $entTag = $entity->getEntityTag();
          }
          foreach ($linkedAnoIDsByType[$typeID] as $anoID) {
            $annotation = new Annotation($anoID);
            $anoText = $annotation->getText();
            if ($anoText) {
              $anoTag = "ano".$anoID;
              $fnHtml .= "<sup id=\"$anoTag\" class=\"$type $entTag $typeTag\" >".(defined("FOOTNOTEMARKER")?FOOTNOTEMARKER:"n")."</sup>";
              $fnTextByAnoTag[$anoTag] = $anoText;
            }
          }
        }
      }
    }
    if ($fnTextByAnoTag && count($fnTextByAnoTag) > 0){
      $fnInfo = array('fnHtml' => $fnHtml, 'fnTextByAnoTag' => $fnTextByAnoTag);
      $entity->storeScratchProperty('fnHtml',$fnHtml);
      $entity->storeScratchProperty('fnTextByAnoTag',$fnTextByAnoTag);
      $entity->save();
    }
  } else {
    $fnInfo = array('fnHtml' => $fnHtml, 'fnTextByAnoTag' => $fnTextByAnoTag);
  }
  return $fnInfo;
}

function getEdnLpInfoQueryString($ednID){
  $textPhysicalTypeID = Entity::getIDofTermParentLabel('textphysical-sequencetype');// warning!!! term dependency
  return "select lp.seq_id, seg_baseline_ids[1] as bln_id, ".
               "fscl.scl_id as fscl_id, syl.scl_id as scl_id, ".
               "array_position(lp.seq_entity_ids::text[],concat('scl:',syl.scl_id)::text) as sclord,lp.seq_label, ".
               "case when gra_sort_code::text = '195' then syl.scl_grapheme_ids[2] else syl.scl_grapheme_ids[1] end as scl_gra_id, ".
               "seg_image_pos, array_position(c.tpentids::text[],concat('seq:',lp.seq_id)::text) as lineOrd, img_title as ilabel, ".
               "prt_label as plabel, frg_label as flabel, srf_label as slabel ".
         "from (select tp.seq_id as tpid, tp.seq_entity_ids as tpentids ".
                "from sequence tp left join edition on tp.seq_id = ANY(edn_sequence_ids) ".
                "where tp.seq_type_id = $textPhysicalTypeID and edn_id = $ednID) c ".
              "left join sequence lp on concat('seq:',seq_id)::text = ANY(c.tpentids) ".
              "left join syllablecluster fscl on scl_id = substring(lp.seq_entity_ids[1]::text from 5)::int ".
              "left join syllablecluster syl on concat('scl:',syl.scl_id)::text = ANY(lp.seq_entity_ids) ".
              "left join segment on syl.scl_segment_id = seg_id ".
              "left join baseline on bln_id = seg_baseline_ids[1] ".
              "left join surface on srf_id = bln_surface_id ".
              "left join fragment on frg_id = srf_fragment_id ".
              "left join part on prt_id = frg_part_id ".
              "left join image on img_id = bln_image_id ".
              "left join grapheme on gra_id = syl.scl_grapheme_ids[1] ".
//        "where seg_image_pos is not null ".
        "order by lineOrd, sclord";
}

function getEdnLpNoSegInfoQueryString($ednID){
  $textPhysicalTypeID = Entity::getIDofTermParentLabel('textphysical-sequencetype');// warning!!! term dependency
  return "select lp.seq_id, ".
               "substring(lp.seq_entity_ids[1]::text from 5)::int as fscl_id, lp.seq_label, scl_id".
               "case when gra_sort_code::text = '195' then scl_grapheme_ids[2] else scl_grapheme_ids[1] end as scl_gra_id, ".
               "array_position(c.tpentids::text[],concat('seq:',lp.seq_id)::text) as lineOrd ".
        "from (select tp.seq_id as tpid, tp.seq_entity_ids as tpentids ".
                "from sequence tp left join edition on tp.seq_id = ANY(edn_sequence_ids) ".
                "where tp.seq_type_id = $textPhysicalTypeID and edn_id = $ednID) c ".
              "left join sequence lp on concat('seq:',seq_id)::text = ANY(c.tpentids) ".
              "left join syllablecluster on scl_id = substring(lp.seq_entity_ids[1]::text from 5)::int ".
              "left join grapheme on gra_id = scl_grapheme_ids[1] ".
        "order by lineOrd";
}
function addSequenceLabelToLookup($seqID) {
  global $graID2StructureInlineLabels;
  $structSequence = new Sequence($seqID);
  if ($structSequence->hasError()) {
    error_log("error retrieving structural analysis sequence ".$structSequence->getError());
  } else {
    $structGIDs = $structSequence->getEntityIDs();
    $seqLabel = $structSequence->getLabel();
    $seqType = $structSequence->getType();
    $firstGraID = null;
    $graIDs = null;
    if ($structGIDs && count($structGIDs) > 0) {
      $cnt = 0;
      foreach ($structGIDs as $structGID) {
        list($prefix,$entID) = explode(':',$structGID);
        $entTag = $prefix.$entID;
        if ($cnt == 0) {
          switch ($prefix) {
            case 'seq':
              $firstGraID = addSequenceLabelToLookup($entID);
              break;
            case 'cmp':
              if ($firstGraID) {
                break;
              }
              $compound = new Compound($entID);
              $tokenIDs = $compound->getTokenIDs();
              if (count($tokenIDs) == 0) {
                error_log("warn, Warning irregular compound $entTag in sequence - $structGID skipped.");
                break;
              }
              $entID = $tokenIDs[0]; //first token of compound
            case 'tok':
              if ($firstGraID) {
                break;
              }
              $token = new Token($entID);
              $graIDs = $token->getGraphemeIDs();
              if ($graIDs && count($graIDs) == 0) {
                error_log("warn, Warning irregular token $entTag with no graphemes - $structGID skipped.");
                break;
              }
              break;
            case 'scl':
              if ($firstGraID) {
                break;
              }
              $syllable = new SyllableCluster($entID);
              $graIDs = $syllable->getGraphemeIDs();
              if ($graIDs && count($graIDs) == 0) {
                error_log("warn, Warning irregular syllable $entTag with no graphemes - $structGID skipped.");
                break;
              }
              break;
          }

          if (!$firstGraID && $graIDs && count($graIDs)) {
            $grapheme = new Grapheme($graIDs[0]);
            if ($grapheme && !$grapheme->hasError()) {
              if ($grapheme->getSortCode() != "195") {
                $firstGraID = $graIDs[0];
              } else if ($graIDs && count($graIDs) > 1) {
                $firstGraID = $graIDs[1];
              }
            }
          }
          if ($firstGraID && $seqLabel) {
            if (!is_array($graID2StructureInlineLabels)) {
              $graID2StructureInlineLabels = array();
            }
            if (!array_key_exists($firstGraID,$graID2StructureInlineLabels)) {
              $graID2StructureInlineLabels[$firstGraID] = array();
            }
            $inlineHtmlMarker = "<span class=\"structMarker $seqType seq$seqID\">[$seqLabel]</span>";
            array_push($graID2StructureInlineLabels[$firstGraID], $inlineHtmlMarker);
          }
        } else if ($prefix == "seq") {
          addSequenceLabelToLookup($entID);
        }
        $cnt++;
      }
    }
  }
  return $firstGraID;
}

function getEdnLookupInfo($edition, $fnTypeIDs = null, $useInlineLabel = true, $refresh = false, $isMultiText = false) {
  global $graID2StructureInlineLabels;
  if (!$edition || $edition->hasError()) {
    return null;
  }
  $ednLookupInfo = null;
  if (defined("USEVIEWERCACHING") && USEVIEWERCACHING) {
    $ednLookupInfo = $edition->getScratchProperty('lookupInfo');
  }
  if (!$ednLookupInfo || $refresh) {
    $ednID = $edition->getID();
    if (!$fnTypeIDs){
      $fnTypeIDs = array(Entity::getIDofTermParentLabel('FootNote-FootNoteType'));//warning!!!! term dependency
    }
    $dbMgr = new DBManager();
    $isFullQuery = true;
    if (!$dbMgr || $dbMgr->getError()) {
      error_log("error loading dataManager");
      return null;
    }
    $dbMgr->query(getEdnLpInfoQueryString($edition->getID()));
    if ($dbMgr->getError()) {
      error_log("error querying lp info for edn".$edition->getID().' '.$dbMgr->getError());
      return null;
    } else if ($dbMgr->getRowCount() < 1) {
      error_log("error for querying seg lp info for edn".$edition->getID()." row count is 0");
      $dbMgr->query(getEdnLpNoSegInfoQueryString($edition->getID()));
      if ($dbMgr->getError()) {
        error_log("error querying lp non seg info for edn".$edition->getID().' '.$dbMgr->getError());
        return null;
      } else if ($dbMgr->getRowCount() < 1) {
        error_log("error for querying non seg lp info for edn".$edition->getID()." row count is 0");
        return null;
      }
      $isFullQuery = false;
    }
    $ednLookupInfo = array();
    $blnInfoBySort = array();
    $blnIDs = array();
    $blnID = null;
    $lpSeqID = null;
    $curSideLbl = null;
    $sideLbl = null;
    $curFrgLbl = null;
    $frgLbl = null;
    $bRectPts = null;
    $fnTextByAnoIDs = array();
    $lineBlnScrollTops = array();
    $lineBlnScrollTop = null;
    $lineHtmlMarkerMap = array();
    $PFSHtmlMarkerMap = array();
    $lineOrd = null;
    //calc info per physical line
    while ($row = $dbMgr->fetchResultRow()) {//lp seq_id : bln_id : firstscl_id : seq_label : nl_gra_id : seg_image_pos : lineOrd
      $seqID = $row['seq_id'];
      $physicalLineSeq = new Sequence($seqID);
      if (!$physicalLineSeq || $physicalLineSeq->hasError()) {//no $physicalLineSeqIDsequence or unavailable so warn
        error_log("Warning unable to load edition $ednID's physicalline sequence seq:$seqID. Skipping.".
                  ($physicalLineSeq->hasError()?" Error: ".join(",",$physicalLineSeq->getErrors()):""));
        continue;
      } else {
        //check for lp change
        if ($lpSeqID != $seqID) { // new physical line so init
          $lineBlnScrollTop = null;
          $seqTag = 'seq'.$seqID;
          $seqLabel = $row['seq_label'];
          $lineGraID = $row['scl_gra_id'];
          $lineOrd = $row['lineord'];
          $lpSeqID = $seqID;
        }
        $sclGraID = $row['scl_gra_id'];
        $sclTag = 'scl'.$row["scl_id"];
        // collect unique blnIDs for url lookup
        if (array_key_exists("bln_id",$row)&& $row["bln_id"] != $blnID && 
            array_key_exists("seg_image_pos",$row) && $row["seg_image_pos"]) {// found another baseline need to capture the id
          $blnID = $row["bln_id"];
          $blnTag = 'bln'.$blnID;
          array_push($blnIDs,$blnID);
        }
        if ($isFullQuery && array_key_exists("seg_image_pos",$row) && $row["seg_image_pos"]) { //   **variable** need to find min y of all line syllables
          $bRectPts = (new Polygon($row['seg_image_pos']))->getBoundingRect();
          // calculate baseline position by physical line seqID minimum value
          if (!$lineBlnScrollTop || $bRectPts[1] < $lineBlnScrollTop['y']) {
            $lineBlnScrollTop = array('blnTag'=>$blnTag,'x'=>$bRectPts[0],'y'=>$bRectPts[1],'h'=>($bRectPts[5]-$bRectPts[1]));
            $lineBlnScrollTops[$seqTag] = $lineBlnScrollTop;
          }
        }
        if (!$isFullQuery || array_key_exists("sclord",$row) && $row["sclord"] == 1) {
          // create grapheme to line marker lookup for this physical line
          if (!$seqLabel && !$seqLabel == 0) {
            $seqLabel = $lineOrd?$lineOrd:$seqTag;
          }
          // first syllable infomation
          $fnInfo = getEntityFootnoteInfo($physicalLineSeq,$fnTypeIDs, $refresh);
          $fnHtml = $fnInfo?$fnInfo['fnHtml']:"";
          $fnTextByAnoIDs = $fnInfo?array_merge($fnTextByAnoIDs,$fnInfo['fnTextByAnoTag']):$fnTextByAnoIDs;
          if ($useInlineLabel) {
            $lineHtml = "<span class=\"linelabel $seqTag\">[$seqLabel]";
            $lineHtml .= $fnHtml;//add any line footnotes to end of label
            $lineHtml .= "</span>";
          } else { //use header format
            $lineHtml = "<span class=\"lineHeader $seqTag\">$seqLabel";
            $lineHtml .= $fnHtml;//add any line footnotes to end of label
            $lineHtml .= "</span>";
          }
          $lineHtmlMarkerMap[$lineGraID] = $lineHtml;
        }
        if ($isFullQuery){
          $imgLabel = $row["ilabel"];
          $prtLabel = $row["plabel"];
          $frgLbl = $row["flabel"];
          $srfLabel = $row["slabel"];
          $sideLbl = ($prtLabel || $frgLbl || $srfLabel)?($prtLabel?$prtLabel:"").
                        ((INCLUDEFRAGINPARTSIDELABEL && $frgLbl)? ($prtLabel && PARTFRAGSEPARATOR?PARTFRAGSEPARATOR:'').$frgLbl:"").
                        ($srfLabel?(($prtLabel || $frgLbl) && FRAGSIDESEPARATOR?FRAGSIDESEPARATOR:'').$srfLabel:""):
                      ((SUBIMGTITLEFORPARTSIDELABEL && $imgLabel)? $imgLabel:"");
          //on label changes save html for graphemeID to mark change logical location
          if ($sideLbl && $curSideLbl != $sideLbl) {
            $PFSHtmlMarkerMap[$sclGraID] = array(
              "sideMarker"=>($useInlineLabel?"<span class=\"sideMarker $seqTag\">[$sideLbl]</span>":
                                             "<div class=\"sideHeader $seqTag\">$sideLbl</div>")
            );
            $curSideLbl = $sideLbl;
          }
          if (!INCLUDEFRAGINPARTSIDELABEL && $frgLbl && $curFrgLbl != $frgLbl) {
            if (!array_key_exists($sclGraID,$PFSHtmlMarkerMap)) {
              $PFSHtmlMarkerMap[$sclGraID] = array();
            }
            $PFSHtmlMarkerMap[$sclGraID]["fragLabel"] = "<span class=\"fraglabel $sclTag\">[$frgLbl]</span>";
            $curFrgLbl = $frgLbl;
          }
        }
      }
    }
    $ednLookupInfo['htmlLineMarkerByGraID'] = $lineHtmlMarkerMap;
    if (count($PFSHtmlMarkerMap) > 0) {
      $ednLookupInfo['PFSHtmlMarkerMap'] = $PFSHtmlMarkerMap;
    }
    if ($lineBlnScrollTops) {
      $ednLookupInfo['lineScrollTops'] = $lineBlnScrollTops;
    }
    // calculate baseline info for each blnID
    if (count($blnIDs)>0){
      $segBaselines = new Baselines('bln_id in ('.join(',',$blnIDs).')');
      if ($segBaselines && !$segBaselines->getError() && $segBaselines->getCount() > 0) {
        foreach ($segBaselines as $segBaseline){
          $sourceLookup = array();
          $attributions = $segBaseline->getAttributions(true);
          if ($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
            foreach ($attributions as $attribution) {
              $atbID = $attribution->getID();
              $title = $attribution->getTitle();
              if (!array_key_exists($atbID,$sourceLookup)) {
                $sourceLookup[$atbID] = $title;
              }
            }
          }
          $blnTag = $segBaseline->getEntityTag();
          $url = $segBaseline->getURL(); //todo handle case where segment is defined across 2 or more baselines
          $ord = $segBaseline->getScratchProperty('ordinal');
          $blnImgTag = 'img'.$segBaseline->getImageID();
          if ($ord) {
            $sort = $ord;
          } else {
            $sort = 1000 * intval($segBaseline->getID());
          }
          $blnInfoBySort[$sort] = array('tag'=>$blnTag, 'url'=>$url, 'imgTag'=>$blnImgTag, 'source'=> $sourceLookup);
          //$title = substr($url,strrpos($url,'/')+1);//filename
          $image = $segBaseline->getImage(true);
          //if ($image && $image->getTitle()) {
          $title = $image?$image->getTitle():'';
          //}
          $blnInfoBySort[$sort]['title'] = $title?$title:"";
          if ($url) {
            $info = pathinfo($url);
            $dirname = $info['dirname'];
            if (strpos($dirname,'full/full') > -1) { //assume iiif
              $fullpath = str_replace('full/full','full/pct:5',$dirname).'/'.$info['basename'];
            } else if (strpos($dirname,'iiif') > -1 && strpos($dirname,'/full/0') > -1 ) { //assume iiif
              $fullpath = str_replace('/full/0','/pct:5/0',$dirname).'/'.$info['basename'];
            } else {
              $fullpath =  $dirname."/th".$info['basename'];
            }
            $blnInfoBySort[$sort]['thumbUrl'] = $fullpath;
          }
        }
        $ednLookupInfo['blnInfoBySort'] = $blnInfoBySort;
      }
    }
    $textSeqTypeID = Entity::getIDofTermParentLabel('text-sequencetype');// warning!!! term dependency
/*    $allTokCmpGIDsQuery = "select array_agg(c.comp) ".
                          "from (select unnest(td.seq_entity_ids::text[]) as comp ".
                                "from sequence td ".
                                     "left join sequence txt on concat('seq:',td.seq_id) = ANY(txt.seq_entity_ids) ".
                                     "left join edition on txt.seq_id = ANY(edn_sequence_ids) ".
                                "where txt.seq_type_id = $textSeqTypeID and edn_id = $ednID) c;";
*/    $allTokCmpGIDsQuery = "select td.seq_id as txtdiv_seqid, td.seq_entity_ids::text[] as ent_ids ".
                                "from sequence td ".
                                     "left join sequence txt on concat('seq:',td.seq_id) = ANY(txt.seq_entity_ids) ".
                                     "left join edition on txt.seq_id = ANY(edn_sequence_ids) ".
                                "where txt.seq_type_id = $textSeqTypeID and edn_id = $ednID;";
    $dbMgr->query($allTokCmpGIDsQuery);
    if ($dbMgr->getError()) {
      error_log("error for querying words GIDs ".$dbMgr->getError());
    } else if ($dbMgr->getRowCount() < 1) {
      error_log("error for querying for wordGIDs row count is 0");
    } else {
      $graID2WordGID = array();
      $prevWrdLastGraID = 0;
      while ($row = $dbMgr->fetchResultRow()) {
        $txtDivSeqID = trim($row['txtdiv_seqid']);
        $tdSeqTokCmpGIDs = explode(",",trim($row['ent_ids'],"{}"));
        foreach ($tdSeqTokCmpGIDs as $wordGID) {
          list($prefix,$entID) = explode(':',$wordGID);
          $wordTag = $prefix.$entID;
          if ($prefix == 'cmp') {
            $compound = new Compound($entID);
            $tokenIDs = $compound->getTokenIDs();
            if (count($tokenIDs) == 0) {
              error_log("warn, Warning irregular word $wordGID in sequence $txtDivSeqID skipped.");
              continue;
            }
            $entID = $tokenIDs[0]; //first token of compound
          }
          $token = new Token($entID);
          $graIDs = $token->getGraphemeIDs();
          if ($graIDs && count($graIDs) == 0) {
            error_log("warn, Warning irregular token tok$entID with no graphemes in $txtDivSeqID skipped.");
            continue;
          }
          if ($graIDs[0] !== $prevWrdLastGraID) {
            $graID2WordGID[$graIDs[0]] = array('seq'.$txtDivSeqID, $wordTag, 0);
          } else { //shared grapheme currently vowel sandhi
            //set word info to show second word in sandhi combination
            $graID2WordGID[$graIDs[0]] = array('seq'.$txtDivSeqID, $wordTag, 2);
            if (array_key_exists($prevWrdFirstGraID,$graID2WordGID )) {
              //adjust to show first word in sandhi combination
              $graID2WordGID[$prevWrdFirstGraID][2] = 1;
            }
          }
          $prevWrdFirstGraID = $graIDs[0];
          $prevWrdLastGraID = $graIDs[count($graIDs)-1];
        }
        if (count($graID2WordGID) > 0){
          $ednLookupInfo['gra2WordGID'] = $graID2WordGID;
        }
      }
    }

    if (!$isMultiText) {
      $graID2StructureInlineLabels = array();
    }

    $analysisSeqTypeID = Entity::getIDofTermParentLabel('analysis-sequencetype');// warning!!! term dependency
    $analSeqIDQuery = "select seq_id from sequence ".
                                  "left join edition on seq_id = ANY(edn_sequence_ids) ".
                                "where seq_type_id = $analysisSeqTypeID and edn_id = $ednID;";
    $dbMgr->query($analSeqIDQuery);
    if ($dbMgr->getError()) {
      error_log("error for querying words GIDs ".$dbMgr->getError());
    } else if ($dbMgr->getRowCount() < 1) {
      error_log("error for querying for wordGIDs row count is 0");
    } else {
      $row = $dbMgr->fetchResultRow();
      $analSequence = new Sequence($row["seq_id"]);
      if ($analSequence->hasError()) {
        error_log("error retrieving analysis sequence ".$analSequence->getError());
      } else {
        $analysisGIDs = $analSequence->getEntityIDs();
        if ($analysisGIDs && count($analysisGIDs) > 0) {
          foreach ($analysisGIDs as $subSeqGID) {
            list($prefix,$entID) = explode(':',$subSeqGID);
            $entTag = $prefix.$entID;
            if ($prefix != 'seq') {
              error_log("Warning analysis contains non sequence $entTag  skipped.");
              continue;
            }
            addSequenceLabelToLookup($entID);
          }
        }
        if ($graID2StructureInlineLabels && count($graID2StructureInlineLabels) > 0){
          $ednLookupInfo['graID2StructureInlineLabels'] = $graID2StructureInlineLabels;
        }
      }
    }
    $edition->storeScratchProperty('lookupInfo',$ednLookupInfo);
    $edition->save();
  }
  return $ednLookupInfo;
}

function getWordIdsForLineQueryString($seqID){
  $linePhysicalTypeID = Entity::getIDofTermParentLabel('linephysical-textphysical');// warning!!! term dependency

  return "select distinct(tok_id), cmp_id from token left join syllablecluster on scl_grapheme_ids && tok_grapheme_ids ".
                                          "left join compound on concat('tok:',tok_id) = ANY(cmp_component_ids) ".
                                          "left join sequence c on concat('scl:',scl_id) = ANY(c.seq_entity_ids) ".
          "where c.seq_id = $seqID and c.seq_type_id = $linePhysicalTypeID and ".
                 "tok_id is not null and scl_id is not null and not scl_owner_id = 1 and not tok_owner_id = 1 and ".
                 "(cmp_owner_id is null or not cmp_owner_id = 1) ".
          "order by tok_id;";
}

function updateWordLocationForLine($seqID) {
  $dbMgr = new DBManager();
  if (!$dbMgr || $dbMgr->getError()) {
    error_log("error loading dataManager");
    return null;
  }
  $dbMgr->query(getWordIdsForLineQueryString($seqID));
  if ($dbMgr->getError()) {
    error_log("error querying line seq$seqID ".$dbMgr->getError());
    return null;
  } else if ($dbMgr->getRowCount() < 1) {
    error_log("error querying line seq$seqID row count is 0");
    return null;
  } else {
    $updateWords = array();
    while ($row = $dbMgr->fetchResultRow()) {
      if ($row[0]) {
        $token = new Token($row[0]);
        $token->updateLocationLabel();
        array_push($updateWords, $token);
      }
      if ($row[1]) {
        $compound = new Compound($row[1]);
        $compound->updateLocationLabel();
        array_push($updateWords, $compound);
      }
    }
    return $updateWords;
  }
}


function getTokLocQueryString($tokID){
  $strMatch = defined('CKNMATCHREGEXP')?CKNMATCHREGEXP:"'([a-z]+)0*(\d+)'";
  $strReplace = defined('CKNREPLACEMENTEXP')?CKNREPLACEMENTEXP:"'\\1 \\2'";
  $strFlags = defined('CKNREPLACEFLAGS')?CKNREPLACEFLAGS:"'i'";
  $linePhysicalTypeID = Entity::getIDofTermParentLabel('linephysical-textphysical');// warning!!! term dependency
  $padaTypeID = Entity::getIDofTermParentLabel('pāda-stanza');// warning!!! term dependency

  return "select distinct  array_position( b.seq_entity_ids::text[],concat('seq:',c.cid)) as lineord, c.label as linelabel, ".
         "case when txt_ref is not null and txt_ref != '' then txt_ref else regexp_replace(txt_ckn,$strMatch,$strReplace".($strFlags?",$strFlags":"").") end as txtlabel, ".
         (defined('DEFAULTTOVERSELABEL') && DEFAULTTOVERSELABEL?"vp.vloc ":"null ")."as vloc ".
         "from sequence b left join (select a.seq_id::text as cid, a.seq_label as label, a.seq_scratch::json->>'edOrd' as edord ".
                                    "from sequence a ".
                                    "where a.seq_type_id = $linePhysicalTypeID and ".
                                          "a.seq_entity_ids::text[] && (select array_agg(concat('scl:',scl_id)) ".
                                                                       "from token,syllablecluster ".
                                                                       "where scl_grapheme_ids && tok_grapheme_ids and tok_id = $tokID) ".
                                                                       "order by edOrd,seq_label,seq_id) c ".
                              "on concat('seq:',c.cid) = ANY(b.seq_entity_ids) ".
                        "left join edition on b.seq_id = ANY(edn_sequence_ids) ".
                        "left join text on txt_id = edn_text_id ".
                        "full join (select concat(v.seq_label,p.seq_label) as vloc ".
                                    "from sequence p left join sequence v on concat('seq:',p.seq_id::text) = ANY(v.seq_entity_ids) ".
                                    "where p.seq_type_id = $padaTypeID and ".
                                          "concat('tok:','$tokID') = ANY(p.seq_entity_ids) and ".
                                          "v.seq_owner_id != 1 and p.seq_owner_id != 1 ".
                                    "order by v.seq_label,p.seq_label) vp on true ".
         "where c.cid is not null and txt_id is not null ".
         "order by lineord;";
}

function getWordLocation($wordTag) {
  if (!$wordTag || strlen($wordTag) < 4) {
    return "zzz";
  }
  $prefix = substr($wordTag,0,3);
  $wrdID = substr($wordTag,3);
  $useVLabel = (defined('DEFAULTTOVERSELABEL') && DEFAULTTOVERSELABEL);
  $txtLabel = null;
  $lineord = null;
  $labels = array();
  if ($prefix == 'cmp') {
    $compound = new Compound($wrdID);
    if ($compound->hasError()){
      error_log("error loading word cmp$compound : ".$compound->getErrors(true));
      return "zzz";
    } else {
      $tokIDs = $compound->getTokenIDs();
      $fTokID = $tokIDs[0];
      $lTokID = $tokIDs[count($tokIDs)-1];
      $dbMgr = new DBManager();
      if (!$dbMgr || $dbMgr->getError()) {
        error_log("error loading dataManager");
        return "zzz";
      }
      if (!$useVLabel) {//if using verse label skip first token      
        $dbMgr->query(getTokLocQueryString($fTokID));
        if ($dbMgr->getError()) {
          error_log("error for querying first tok$fTokID of cmp$wrdID ".$dbMgr->getError());
          return "zzz";
        } else if ($dbMgr->getRowCount() < 1) {
          error_log("error for querying first tok$fTokID of cmp$wrdID row count is 0");
          return "zzz";
        } else {
          $row = $dbMgr->fetchResultRow();
          $txtLabel = $row['txtlabel'];
          $lineord = $row['lineord'];
          array_push($labels, $row['linelabel']);
        }
      }
      $dbMgr->query(getTokLocQueryString($lTokID));
      if ($dbMgr->getError()) {
        error_log("error for querying last tok$lTokID of cmp$wrdID ".$dbMgr->getError());
        return "zzz";
      } else if ($dbMgr->getRowCount() < 1) {
        error_log("error for querying last tok$lTokID of cmp$wrdID row count is 0");
        return "zzz";
      } else {
        $row = $dbMgr->fetchResultRow($dbMgr->getRowCount()-1);
        array_push($labels,(($row['vloc'] && $useVLabel)?$row['vloc']:$row['linelabel']));
      }
    }
  } else if ($prefix == 'tok') {
    $dbMgr = new DBManager();
    if (!$dbMgr || $dbMgr->getError()) {
      error_log("error loading dataManager");
      return "zzz";
    }
    $dbMgr->query(getTokLocQueryString($wrdID));
    if ($dbMgr->getError()) {
      error_log("error for querying tok$wrdID ".$dbMgr->getError());
      return "zzz";
    } else if ($dbMgr->getRowCount() < 1) {
      error_log("error for querying first tok$wrdID row count is 0");
      return "zzz";
    } else {
      $row = $dbMgr->fetchResultRow();
      array_push($labels,(($row['vloc'] && $useVLabel)?$row['vloc'] : $row['linelabel']));
      $txtLabel = $row['txtlabel'];
      $lineord = $row['lineord'];
      if ($dbMgr->getRowCount() > 1) {
        $row = $dbMgr->fetchResultRow($dbMgr->getRowCount()-1);
        array_push($labels,(($row['vloc'] && $useVLabel)?$row['vloc'] : $row['linelabel']));
      }
    }
  }
  $cntLabels = count($labels);
  if ($cntLabels == 1){
    return ($txtLabel?$txtLabel.':':"").$lineord.':'.$labels[0];
  } else {
    sort($labels);
    $label1 = $labels[0];
    $label2 = $labels[$cntLabels - 1];
    if ($label1 != $label2) {
      $label2 = explode(':',$label2);
      $label1 .= '–'.(count($label2)>1?$label2[1]:$label2[0]);
    }
    return ($txtLabel?$txtLabel.':':"").$lineord.':'.$label1;
  }
}

function compareWordLocations($locW1,$locW2) {
  if ($locW2 == 'zzz') {
    if ($locW1 == 'zzz') {
      return 0;
    } else {
      return 1;
    }
  } else if ($locW1 == 'zzz') {
    return -1;
  }
  if (strpos($locW1,':') == strrpos($locW1,':')) { //single colon so no tref
    list($ord1,$label1) = explode(':',$locW1);
    $tref1 = 0;
  } else {
    list($tref1,$ord1,$label1) = explode(':',$locW1);
  }
  if (strpos($locW2,':') == strrpos($locW2,':')) { //single colon so no tref
    list($ord2,$label2) = explode(':',$locW2);
    $tref2 = 0;
  } else {
    list($tref2,$ord2,$label2) = explode(':',$locW2);
  }
  if (preg_match("/^sort\d+/",$tref1) && preg_match("/^sort\d+/",$tref2)) {
    $tref1 = intval(substr($tref1,4));
    $tref2 = intval(substr($tref2,4));
  } else if (preg_match("/^CK[DIMC]\d+/i",$tref1) && preg_match("/^CK[DIMC]\d+/",$tref2)) {
    if ($tref1[2] == $tref2[2]) {//ensure the CKD CKC CKM CKI also sort
      $tref1 = intval(substr($tref1,3));//use integer part
      $tref2 = intval(substr($tref2,3));//use integer part
    }
  } else if (is_numeric($tref1)&& is_numeric($tref2)) {
    $tref1 = intval($tref1);
    $tref2 = intval($tref2);
  }
  if ($tref1 > $tref2) {
    return 1;
  } else if ($tref1 < $tref2) {
    return -1;
  } else {
    $ord1 = intval($ord1);
    $ord2 = intval($ord2);
    if ($ord1 > $ord2) {
      return 1;
    } else if ($ord1 < $ord2) {
      return -1;
    } else {
      return 0;
    }
  }
}

/**
 * compareSortKeys
 * compare 2 numeric strings starting with "0." to order
 * by numeric value
 * @param string $key1 sort code of the form "0.#####*"
 * @param string $key2 sort code of the form "0.#####*"
 */
function compareSortKeys($key1,$key2) {
  if (!$key1 && $key2) {
    return -1;
  } else if ($key1 && !$key2) {
    return 1;
  } else if (!$key1 && !$key2) {
    return 0;
  }
  $cnt1 = strlen($key1);
  $cnt2 = strlen($key2);
  $i = 0;
  while ($i < $cnt1 && $i < $cnt2) {
    if ($key1[$i] != $key2[$i]) {
      break;
    }
    $i++;
  }
  //fall through case
  if ($i == $cnt1 || $i == $cnt2) {
    if ($cnt1 == $cnt2) {
      return 0;
    } else if ($cnt1 > $cnt2 ) {
      return 1;
    } else {
      return -1;
    }
  }
  //break out case
  if ($key1[$i] > $key2[$i]) {
    return 1;
  } else if ($key1[$i] < $key2[$i]) {
    return -1;
  } else {
    return 0;
  }
}

function changeVisibility($prefix,$table,$ids,$vis,$owner) {
  $dbMgr = new DBManager();
  if ($table == 'usergroup') {
    return null;
  }
  $query = "update $table set $prefix"."_visibility_ids='$vis'";
  if($ids && count($ids) >0 || $owner) {
    if ($ids && $owner) {
      $query .= " where $prefix"."_id in (".join(",",$ids).") and $prefix"."_owner_id=$owner";
    }else if ($ids) {
      $query .= " where $prefix"."_id in (".join(",",$ids).") and $prefix"."_owner_id != 1";
    } else if ($owner) {
      $query .= " where $prefix"."_owner_id=$owner";
    }
  } else {
    $query .= " where $prefix"."_owner_id != 1";
  }
  $query .= ";";
  $dbMgr->query($query);
  $cnt = $dbMgr->getAffectedRowCount();
  return $cnt?$cnt:null;
}


function changeOwner($prefix,$table,$ids,$newOwner,$oldOwner) {
  $dbMgr = new DBManager();
  if ($table == 'usergroup') {
    return null;
  }
  $query = "update $table set $prefix"."_owner_id='$newOwner'";
  if($oldOwner) {
    $query .= " where $prefix"."_owner_id=$oldOwner";
  }
  $query .= ";";
  $dbMgr->query($query);
  $cnt = $dbMgr->getAffectedRowCount();
  return $cnt?$cnt:null;
}

$prefixToTableName = array(
          "col" => "collection",
          "itm" => "item",
          "prt" => "part",
          "frg" => "fragment",
          "img" => "image",
          "spn" => "span",
          "srf" => "surface",
          "txt" => "text",
          "tmd" => "textmetadata",
          "mcx" => "materialcontext",
          "bln" => "baseline",
          "seg" => "segment",
          "run" => "run",
          "lin" => "line",
          "scl" => "syllablecluster",
          "gra" => "grapheme",
          "tok" => "token",
          "cmp" => "compound",
          "lem" => "lemma",
          "inf" => "inflection",
          "trm" => "term",
          "prn" => "propernoun",
          "cat" => "catalog",
          "seq" => "sequence",
          "lnk" => "link",
          "edn" => "edition",
          "bib" => "bibliography",
          "ano" => "annotation",
          "atb" => "attribution",
          "atg" => "attributiongroup",
          "ugr" => "usergroup",
          "dat" => "date",
          "era" => "era"
);

function getThumbFromFilename($filename) {
  return "th".$filename;
}

function createThumb($srcPath, $srcFilename, $ext, $targetPath, $thumbBaseURL, $maxSizeX = 150, $maxSizeY = 150) {
  $sourcefile = $srcPath.$srcFilename;
  if (strpos($sourcefile,'full/full') > -1) { //assume iiif
    return str_replace('full/full','full/pct:5',$sourcefile);
  } else if (strpos($sourcefile,'iiif') > -1 && strpos($sourcefile,'/full/0') > -1 ) { //assume iiif
    return str_replace('/full/0','/pct:5/0',$sourcefile);
  }

  $thumbfile = $targetPath.getThumbFromFilename($srcFilename);
  list($imageW,$imageH) = getimagesize($sourcefile);
  if ($imageW <= $maxSizeX && $imageH <= $maxSizeY && preg_match("/^th/",$srcFilename)) { // small image or likely a thumbnail
    return null;
  }
  //shrink and preserve aspect
  $percent = $maxSizeX/$imageW;
  if ($percent>1) {
    $percent = 1;
  }
  if ($percent*$imageH > $maxSizeY) {
    $percent = $maxSizeY/$imageH;
  }
  $thumbW = round($percent*$imageW);
  $thumbH = round($percent*$imageH);

  $thumbImage = imagecreatetruecolor($thumbW,$thumbH);

  switch(strtolower($ext)) {
    case 'png':
      $sourceImage = imagecreatefrompng($sourcefile);
      break;
    case 'gif':
      $sourceImage = imagecreatefromgif($sourcefile);
      break;
    case 'jpg':
    case 'jpeg':
    default:
      $sourceImage = imagecreatefromjpeg($sourcefile);
  }

  if ($sourceImage) {
    imagecopyresampled($thumbImage,$sourceImage,0,0,0,0,$thumbW,$thumbH,$imageW,$imageH);
    switch($ext){
      case 'png':
        $ret = imagepng($thumbImage,$thumbfile,9);
        break;
      case 'gif':
        $ret = imagegif($thumbImage,$thumbfile,100);
        break;
      case 'jpg':
      case 'jpeg':
      default:
        $ret = imagejpeg($thumbImage,$thumbfile,100);
    }
    imagedestroy($thumbImage);
    imagedestroy($sourceImage);
    if ($ret && $thumbBaseURL && $srcFilename) {
      return $thumbBaseURL.getThumbFromFilename($srcFilename);
    } else if ($ret && !$thumbBaseURL) {
      return "no base url supplied!";
    }
  }
  return false;
}



function formatEtym($lemmaEtymString) {
  $formattedEtyms = "";
  $etyms = explode(",",$lemmaEtymString);
  $isFirst = true;
  foreach ($etyms as $etym) {
    preg_match("/\s*(Skt|Pkt|Vedic|P|BHS|G|S)\.?\:?\s*(.+)/",$etym,$matches);
    if (!$isFirst) {
      $formattedEtyms .= ", ";
    } else {
      $isFirst = false;
    }
    if (count($matches) == 3) {
      $formattedEtyms .= "<span class=\"etymlangcode\">".$matches[1]."</span><span class=\"etymvalue\">".$matches[2]."</span>";
    }
  }
  return $formattedEtyms;
}

function getServiceContent($url){
  $content = null;
  if ($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //return the output as a string from curl_exec
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);  //don't include header in output
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  // follow server header redirects
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // don't verify peer cert
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // timeout after 30 seconds
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);  // no more than 5 redirections

    $content = curl_exec($ch);
    //error_log(" data = ". $data);

    $error = curl_error($ch);
    if ($error) {
      $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
      error_log("get Service content error: $error ($code) url = $url");
      curl_close($ch);
      return null;
    }
    curl_close($ch);
  }
  return $content;
}

function startLog($withHeader = true) {
  global $logHTML;
  $logHTML = "<div class=\"logmessages\">";
}

function logAddMsg($msg) {
  global $logHTML;
  $logHTML .= "<div class=\"logmessage\">$msg</div>\n";
}

function logAddMsgExit($msg, $wrapHeader = true, $jsonEncode = false) {
  global $logHTML;
  $logHTML .= "<div class=\"logmessage\">$msg</div>\n";
  flushLog($wrapHeader, $jsonEncode);
  exit();
}

function logAddLink($msg,$url) {
  global $logHTML;
  $logHTML .= "<div class=\"loglink\"><a href=\"$url\" target=\"_blank\">$msg</a></div>\n";
}

function getLogFragment() {
  global $logHTML;
  return $logHTML."</div>\n";
}

function flushLog($wrapHeader = true, $jsonEncode = false) {
  global $logHTML;
  $logHTML .= "</div>\n";
  if ($wrapHeader) {
   $logHTML = "<html>\n<body>\n".$logHTML."</body>\n</html>\n";
  }
  if ($jsonEncode) {
    $logHTML = json_encode($logHTML);
  }
  echo $logHTML;
  $logHTML = "";
}

function createAnnotation($visibilityIDs, $ownerID, $defAttrIDs, $annoTypeTerm = "comment", $annoText = "", $fromGID = null, $toGID = null) {
  
  $annotation = new Annotation();
  $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-commentarytype');//term dependency
  if (!$typeID) {
    $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-workflowtype');//term dependency
  }
  if (!$typeID) {
    $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-footnotetype');//term dependency
  }
  if (!$typeID) {
    $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-linkagetype');//term dependency
  }
  if ($typeID) {
    $typeID = $annotation->getIDofTermParentLabel('comment-commentarytype');//term dependency
  }
  if ($fromGID) {
    $annotation->setLinkFromIDs(array($fromGID));
  }
  if ($toGID) {
    $annotation->setLinkToIDs(array($toGID));
  }
  if ($annoText) {
    $annotation->setText($annoText);
  }
  if ($typeID) {
    $annotation->setTypeID($typeID);
  }
  $annotation->setVisibilityIDs($visibilityIDs);
  $annotation->setAttributionIDs($defAttrIDs);
  $annotation->setOwnerID($ownerID);
  $annotation->save();
  if ($annotation->hasError()) {
    return null;
  } else {
    return $annotation->getID();
  }
}

function createAttribution($title, $description, $visibilityIDs, $ckn_Key, $bibliographyID=null, $type='reference', $detail=null, $aEdId=null, $usergroupID=null) {
  // global $ckn_Key, $visibilityIDs;
  $attribution = new Attribution();
  $nonce = "";
  if (isset($title)) {
    $attribution->setTitle($title);
    $nonce .= $title;
  }
  $attribution->setDescription($description);
  if (isset($bibliographyID)) {
    $attribution->setBibliographyID($bibliographyID);
    $nonce .= $bibliographyID;   
  }
  if (isset($usergroupID)) {
    $attribution->setGroupID($usergroupID);
  }
  $attribution->setVisibilityIDs($visibilityIDs);
  $typeID = Entity::getIDofTermParentLabel($type.'-attributiontype');
  if ($typeID){
    $attribution->setTypes(array($typeID));
    $nonce .= $typeID;   
  }  else if ($ckn_Key) {
    $attribution->storeScratchProperty($ckn_Key."_editions:ed_cmty",$type);
  }
  if (isset($detail)) {
    $attribution->setDetail($detail);
    $nonce .= $detail;   
  }
  if ($nonce) {
    $attributions = new Attributions("atb_scratch like '%$nonce%'","atb_id",null,null);
    if($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
      $attribution = $attributions->current();
      return $attribution->getID();
    } else {
      $attribution->storeScratchProperty("nonce",$nonce);
    }
  }
  if ($aEdId && $ckn_Key) {
    $attribution->storeScratchProperty($ckn_Key."_editions:id",$aEdId);
  }
  $attribution->Save();
  if ($attribution->hasError()) {
    return null;
  } else {
    return $attribution->getID();
  }
}  

//setup for full text import or update
function updateTextEntites($textCKN, $entitiesConfig) {

  if (!isset($textCKN)) {
    error_log("invalid inventory number $textCKN supplied");
    return;
  } else {
    $texts = new Texts("txt_ckn = '$textCKN'","txt_id");
    if (!$texts || $texts->getError() || $texts->getCount() == 0) {
      error_log("invalid text inventory number $textCKN supplied");
      return;
    }
    $text = $texts->current();
    $txtID = $text->getID();
    if (!$txtID || $text->hasError()) {
      error_log("invalid text for inventory number $textCKN supplied");
      return;
    }
  }

  //PAGE VARIABLES
  $ckn_Key = $textCKN[2];
  $userPrefs = getUserPreferences();
  $visibilityIDs = $userPrefs['defaultVisibilityIDs'];  // DEFAULT VISIBILITY SET TO PUBLIC
  $ownerID = $userPrefs['defaultEditUserID'];
  $defAttrIDs = $userPrefs['defaultAttributionIDs'];
  if ($entitiesConfig) {
    if (isset($entitiesConfig['imageDefs'])) {
      $imageDefs = $entitiesConfig['imageDefs'];
    }
    if (isset($entitiesConfig['textItem'])) {
      $textItem = $entitiesConfig['textItem'];
    }
    if (isset($entitiesConfig['textParts'])) {
      $textParts = $entitiesConfig['textParts'];
    }
    if (isset($entitiesConfig['materialContexts'])) {
      $materialContexts = $entitiesConfig['materialContexts'];
    }
    if (isset($entitiesConfig['textFragments'])) {
      $textFragments = $entitiesConfig['textFragments'];
    }
    if (isset($entitiesConfig['textSurfaces'])) {
      $textSurfaces = $entitiesConfig['textSurfaces'];
    }
    if (isset($entitiesConfig['textImageBaselines'])) {
      $textImageBaselines = $entitiesConfig['textImageBaselines'];
    }
  }

  //Create Images
  $imagesNonce2image = array();
  $images = new Images("img_scratch like '%$textCKN%'","img_id",null,null);
  if ( $images->getError()) {
    error_log("Error updating $textCKN - ".$images->getError());
  } else if ($images->getCount() > 0) {
    foreach ($images as $image) {
      $nonce = $image->getScratchProperty('nonce');
      if ($nonce) {
        $imagesNonce2image[$nonce] = $image;
      }
    }
  }
  if (isset($imageDefs) && count($imageDefs) >0) {
    $imageIDs = array();
    foreach ($imageDefs as $imgNonce => $imgMetadata) {
      if (array_key_exists($imgNonce,$imagesNonce2image)) {
        $image = $imagesNonce2image[$imgNonce];
      } else {
        if (intval($imgNonce)) {
          $image = new Image(intval($imgNonce));
          $imgNonce = 'img:'.$imgNonce;
          if ($image && !$image->hasErrors()) {
            $image->storeScratchProperty('nonce',$imgNonce);
            $image->storeScratchProperty('ckn',$textCKN);
          } else {
            $image = null;
          }
        } else {
          $image = null;
        }
        if (!isset($image)) {
          $image = new Image();
          $image->storeScratchProperty('nonce',$imgNonce);
          $image->storeScratchProperty('ckn',$textCKN);
          $image->setOwnerID($ownerID);
          $image->setVisibilityIDs($visibilityIDs);
        }
      }
      if (isset($image) && !$image->getURL() && !array_key_exists('url',$imgMetadata)) {
        continue;
      } else if (array_key_exists('url',$imgMetadata)) {
        $image->setURL($imgMetadata['url']);
      }
      if (array_key_exists('polygon',$imgMetadata)) {
        $image->setBoundary($imgMetadata['polygon']);
      }
      if (array_key_exists('title',$imgMetadata)) {
        $image->setTitle($imgMetadata['title']);
      }
      if (array_key_exists('type',$imgMetadata)) {
        $typeID = $image->getIDofTermParentLabel(strtolower($imgMetadata['type']).'-imagetype');//term dependency
        if ($typeID) {
          $image->setType($typeID);
        } else {
          $image->storeScratchProperty('type',$imgMetadata['type']);
        }
      }
      if (array_key_exists('attribution',$imgMetadata)) {
        $attrInfo = $imgMetadata['attribution'];
        if (isset($attrInfo['atbid'])) {
          $attrID = $attrInfo['atbid'];
        } else {
          $attrID = createAttribution(
                                      isset($attrInfo["title"])?$attrInfo["title"]:null,
                                      isset($attrInfo["description"])?$attrInfo["description"]:null,
                                      $visibilityIDs, $ckn_Key,
                                      isset($attrInfo["bibliographyid"])?$attrInfo["bibliographyid"]:null,
                                      isset($attrInfo["type"])?$attrInfo["type"]:null,
                                      isset($attrInfo["detail"])?$attrInfo["detail"]:null,
                                      isset($attrInfo["aedid"])?$attrInfo["aedid"]:null,
                                      isset($attrInfo["usergroupid"])?$attrInfo["usergroupid"]:null
                                    );
        }
        if (!$image->getAttributionIDs() || $attrID) {
          if (!$attrID) {
            $attrIDS = $defAttrIDs;
          } else {
            $attrIDS = array_unique(array_merge(array($attrID), $image->getAttributionIDs()));
          }
          $image->setAttributionIDs($attrIDS);
        }
      }
      $image->save();
      if (!$image->hasError()) {
        $imagesNonce2image[$imgNonce] = $image;
        array_push($imageIDs, $image->getID());
      }else{
        error_log($image->getErrors(true));
      }
    }
    if ($text && count($imageIDs) > 0) {
      $txtImgIDs = array_unique(array_merge($imageIDs,($text->getImageIDs()?$text->getImageIDs():array())));
      $text->setImageIDs($txtImgIDs);
      $text->save();
    }
  }

  //ITEM
  $itmID = null;
  if(isset($textItem) && count($textItem) > 0) {
    $items = new Items("itm_scratch like '%$textCKN%'","itm_id",null,null);
    if ($items->getCount() == 0 || $items->getError()) {
      error_log("Error updating $textCKN - ".$items->getError());
    } else {
      $item = $items->current();  
      $itmID = $item->getID();
      if (array_key_exists('title',$textItem)) {
        $item->setTitle($textItem['title']);
      }
      if (array_key_exists('type',$textItem)) {
        $typeID = $item->getIDofTermParentLabel(strtolower($textItem['type']).'-itemtype');//term dependency
        if ($typeID) {
          $item->setType($typeID);
        } else {
          $item->storeScratchProperty('type',$textItem['type']);
        }
      }
      if (array_key_exists('shape',$textItem)) {
        $shapeID = $item->getIDofTermParentLabel(strtolower($textItem['shape']).'-itemshape');//term dependency
        if ($shapeID) {
          $item->setShapeID($shapeID);
        } else {
          $item->storeScratchProperty('shape',$textItem['shape']);
        }
      }
      if (array_key_exists('measure',$textItem)) {
        $item->setMeasure($textItem['measure']);
      }
      if (array_key_exists('image_ids',$textItem)) {
        $itemImgIDs = array();
        foreach ($textItem['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($itemImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($itemImgIDs)) {
          $item->setImageIDs($itemImgIDs);
        }
      }
      $item->save();
      if ($item->hasError()) {
        error_log($item->getErrors(true));
      }
    }
  }

  //PARTS
  $partsNonce2part = array();
  $parts = new Parts("prt_scratch like '%$textCKN%'","prt_id",null,null);
  if ($parts->getCount() == 0 || $parts->getError()) {
    error_log("Error updating $textCKN - ".$parts->getError());
  } else {
    foreach ($parts as $part) {
      $nonce = $part->getScratchProperty('nonce');
      if ($nonce) {
        $partsNonce2part[$nonce] = $part;
      }
    }
  }
  if(isset($textParts) && count($textParts) > 0) {
    foreach ($textParts as $prtNonce => $prtMetadata) {
      if (array_key_exists($prtNonce,$partsNonce2part)) {
        $part = $partsNonce2part[$prtNonce];
      } else {
        $part = new Part();
        $part->setOwnerID($ownerID);
        $part->setVisibilityIDs($visibilityIDs);
        $part->storeScratchProperty('nonce',$prtNonce);
        $part->storeScratchProperty('ckn',$textCKN);
      }
      if (array_key_exists('label',$prtMetadata)) {
        $part->setLabel($prtMetadata['label']);
      }
      if (array_key_exists('type',$prtMetadata)) {
        $typeID = $part->getIDofTermParentLabel(strtolower($prtMetadata['type']).'-parttype');//term dependency
        if ($typeID) {
          $part->setType($typeID);
        } else {
          $part->storeScratchProperty('type',$prtMetadata['type']);
        }
      }
      if (array_key_exists('shape',$prtMetadata)) {
        $shapeID = $part->getIDofTermParentLabel(strtolower($prtMetadata['shape']).'-partshape');//term dependency
        if ($shapeID) {
          $part->setShapeID($shapeID);
        } else {
          $part->storeScratchProperty('shape',$prtMetadata['shape']);
        }
      }
      if (array_key_exists('mediums',$prtMetadata)) {
        $part->setMediums($prtMetadata['mediums']);
      }
      if (array_key_exists('measure',$prtMetadata)) {
        $part->setMeasure($prtMetadata['measure']);
      }
      if (array_key_exists('sequence',$prtMetadata)) {
        $part->setSequence($prtMetadata['sequence']);
      }
      if ($itmID) {
        $part->setItemID($itmID);
      }
      if (array_key_exists('image_ids',$prtMetadata)) {
        $partImgIDs = array();
        foreach ($prtMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($partImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($partImgIDs)) {
          $part->setImageIDs($partImgIDs);
        }
      }
      $part->save();
      if (!$part->hasError()) {
        $partsNonce2part[$prtNonce] = $part;
      }else{
        error_log($part->getErrors(true));
      }
    }
  }

  //MATERIALCONTEXT
  $mcxsNonce2mcx = array();
  $mcxs = new MaterialContexts("mcx_scratch like '%$textCKN%'","mcx_id",null,null);
  if ($mcxs->getCount() == 0 || $mcxs->getError()) {
    error_log("Error updating $textCKN - ".$mcxs->getError());
  } else {
    foreach ($mcxs as $materialContext) {
      $nonce = $materialContext->getScratchProperty('nonce');
      if ($nonce) {
        $mcxsNonce2mcx[$nonce] = $materialContext;
      }
    }
  }
  $mcxs = array();
  if(isset($materialContexts) && count($materialContexts) > 0) {
    foreach ($materialContexts as $mcxNonce => $mcxMetadata) {
      if (array_key_exists($mcxNonce,$mcxsNonce2mcx)) {
        $materialContext = $mcxsNonce2mcx[$mcxNonce];
      } else {
        $materialContext = new MaterialContext();
        $materialContext->setOwnerID($ownerID);
        $materialContext->setVisibilityIDs($visibilityIDs);
        $materialContext->setAttributionIDs($defAttrIDs);
        $materialContext->storeScratchProperty('nonce',$mcxNonce);
        $materialContext->storeScratchProperty('ckn',$textCKN);
      }
      if (array_key_exists('arch_context',$mcxMetadata)) {
        $archCtxID = null; //TODO add code to lookup string
        if ($archCtxID) {
          $materialContext->setArchContextID($archCtxID);
        } else {
          $materialContext->storeScratchProperty('arch_context',$mcxMetadata['arch_context']);
        }
      }
      if (array_key_exists('find_status',$mcxMetadata)) {
        $materialContext->setFindStatus($mcxMetadata['find_status']);
      }
      $materialContext->save();
      if (!$materialContext->hasError()) {
        $mcxsNonce2mcx[$mcxNonce] = $materialContext;
      }else{
        error_log($materialContext->getErrors(true));
      }
    }
  }


  //FRAGMENT
  $fragmentsNonce2fragment = array();
  $fragments = new Fragments("frg_scratch like '%$textCKN%'","frg_id",null,null);
  if ($fragments->getCount() == 0 || $fragments->getError()) {
    error_log("Error updating $textCKN - ".$fragments->getError());
  } else {
    foreach ($fragments as $fragment) {
      $nonce = $fragment->getScratchProperty('nonce');
      if ($nonce) {
        $fragmentsNonce2fragment[$nonce] = $fragment;
      }
    }
  }
  if(isset($textFragments) && count($textFragments) > 0) {
    foreach ($textFragments as $fraNonce => $fraMetadata) {
      if (array_key_exists($fraNonce,$fragmentsNonce2fragment)) {
        $fragment = $fragmentsNonce2fragment[$fraNonce];
      } else {
        $fragment = new Fragment();
        $fragment->setOwnerID($ownerID);
        $fragment->setVisibilityIDs($visibilityIDs);
        $fragment->setAttributionIDs($defAttrIDs);
        $fragment->storeScratchProperty('nonce',$fraNonce);
        $fragment->storeScratchProperty('ckn',$textCKN);
      }
      if (array_key_exists('label',$fraMetadata)) {
        $fragment->setLabel($fraMetadata['label']);
      }
      if (array_key_exists('description',$fraMetadata)) {
        $fragment->setDescription($fraMetadata['description']);
      }
      if (array_key_exists('measure',$fraMetadata)) {
        $fragment->setMeasure($fraMetadata['measure']);
      }
      if (array_key_exists('restore_state',$fraMetadata)) {
        $restoreID = $fragment->getIDofTermParentLabel(strtolower($fraMetadata['restore_state']).'-fragmentstate');//term dependency
        if ($restoreID) {
          $fragment->setShapeID($restoreID);
        } else {
          $fragment->storeScratchProperty('restore_state',$fraMetadata['restore_state']);
        }
      }
      if (array_key_exists('location_refs',$fraMetadata)) {
        $fragment->setLocationRefs($fraMetadata['location_refs']);
      }
      if (array_key_exists('part_id',$fraMetadata) && array_key_exists($fraMetadata['part_id'],$partsNonce2part)) {
        $fragment->setPartID($partsNonce2part[$fraMetadata['part_id']]->getID());
      }
      if (array_key_exists('material_context_ids',$fraMetadata)) {
        $fragmentMcxIDs = array();
        foreach ($fraMetadata['material_context_ids'] as $mcxNonce) {
          if (array_key_exists($mcxNonce,$mcxsNonce2mcx)) {
            array_push($fragmentMcxIDs,$mcxsNonce2mcx[$mcxNonce]->getID());
          }
        }
        if (count($fragmentMcxIDs)) {
          $fragment->setMaterialContextIDs($fragmentMcxIDs);
        }
      }
      if (array_key_exists('image_ids',$fraMetadata)) {
        $fragmentImgIDs = array();
        foreach ($fraMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($fragmentImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($fragmentImgIDs)) {
          $fragment->setImageIDs($fragmentImgIDs);
        }
      }
      $fragment->save();
      if (!$fragment->hasError()) {
        $fragmentsNonce2fragment[$fraNonce] = $fragment;
      }else{
        error_log($fragment->getErrors(true));
      }
    }
  }

  //SURFACE
  $surfacesNonce2surface = array();
  $surfaces = new Surfaces("srf_scratch like '%$textCKN%'","srf_id",null,null);
  if ($surfaces->getCount() == 0 || $surfaces->getError()) {
    error_log("Error updating $textCKN - ".$surfaces->getError());
  } else {
    foreach ($surfaces as $surface) {
      $nonce = $surface->getScratchProperty('nonce');
      if ($nonce) {
        $surfacesNonce2surface[$nonce] = $surface;
      }
    }
  }
  if(isset($textSurfaces) && count($textSurfaces) > 0) {
    foreach ($textSurfaces as $srfNonce => $srfMetadata) {
      if (array_key_exists($srfNonce,$surfacesNonce2surface)) {
        $surface = $surfacesNonce2surface[$srfNonce];
      } else {
        $surface = new Surface();
        $surface->setOwnerID($ownerID);
        $surface->setVisibilityIDs($visibilityIDs);
        $surface->storeScratchProperty('nonce',$srfNonce);
        $surface->storeScratchProperty('ckn',$textCKN);
      }
      $surface->setTextIDs(array($txtID));
      if (array_key_exists('description',$srfMetadata)) {
        $surface->setDescription($srfMetadata['description']);
      }
      if (array_key_exists('number',$srfMetadata)) {
        $surface->setNumber($srfMetadata['number']);
      }
      if (array_key_exists('layer_number',$srfMetadata)) {
        $surface->setLayerNumber($srfMetadata['layer_number']);
      }
      if (array_key_exists('scripts',$srfMetadata)) {
        $srfScriptIDs = array();
        $errScripts = array();
        foreach($srfMetadata['scripts'] as $strScript) {
          $scrID = $surface->getIDofTermParentLabel(strtolower($strScript).'-languagescript');//term dependency
          if ($scrID) {
            array_push($srfScriptIDs,$scrID);
          } else {
            array_push($errScripts,$strScript);
          }
        }
        if (count($srfScriptIDs)) {
          $surface->setScripts($srfScriptIDs);
        }
        if (count($errScripts)) {
          $surface->storeScratchProperty('scripts',$errScripts);
        }
      }
      if (array_key_exists('fragment_id',$srfMetadata) && array_key_exists($srfMetadata['fragment_id'],$fragmentsNonce2fragment)) {
        $surface->setFragmentID($fragmentsNonce2fragment[$srfMetadata['fragment_id']]->getID());
      }
      if (array_key_exists('image_ids',$srfMetadata)) {
        $surfaceImgIDs = array();
        foreach ($srfMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($surfaceImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($surfaceImgIDs)) {
          $surface->setImageIDs($surfaceImgIDs);
        }
      }
      $surface->save();
      if (!$surface->hasError()) {
        $surfacesNonce2surface[$srfNonce] = $surface;
      }else{
        error_log($surface->getErrors(true));
      }
    }
  }

  //BASELINE
  $typeID = $text->getIDofTermParentLabel('image-baselinetype');//term dependency
  $baselineNonce2baseline = array();
  $baselines = new Baselines("bln_type_id = $typeID and bln_scratch like '%$textCKN%'","bln_id",null,null);
  if ($baselines->getCount() == 0 || $baselines->getError()) {
    error_log("Error updating $textCKN - ".$baselines->getError());
  } else {
    foreach ($baselines as $baseline) {
      $nonce = $baseline->getScratchProperty('nonce');
      if ($nonce) {
        $baselineNonce2baseline[$nonce] = $baseline;
      }
    }
  }
  $baselines = array();
  if(isset($textImageBaselines) && count($textImageBaselines) > 0) {
    foreach ($textImageBaselines as $blnNonce => $blnMetadata) {
      if (array_key_exists($blnNonce,$baselineNonce2baseline)) {
        $baseline = $baselineNonce2baseline[$blnNonce];
      } else {
        $baseline = new Baseline();
        $baseline->setOwnerID($ownerID);
        $baseline->setVisibilityIDs($visibilityIDs);
        $baseline->setAttributionIDs($defAttrIDs);
        $baseline->storeScratchProperty('nonce',$blnNonce);
        $baseline->storeScratchProperty('ckn',$textCKN);
      }
      if ($typeID) {
        $baseline->setType($typeID);
      }
      if (array_key_exists('imgID',$blnMetadata) && array_key_exists($blnMetadata['imgID'],$imagesNonce2image)) {
        $baseline->setImageID($imagesNonce2image[$blnMetadata['imgID']]->getID());
      }
      if (array_key_exists('polygon',$blnMetadata)) {
        $baseline->setImageBoundary($blnMetadata['polygon']);
      }
      if (array_key_exists('scriptLanguage',$blnMetadata)) {
        $baseline->storeScratchProperty('scriptLanguage',$blnMetadata['scriptLanguage']);
      }
      if (array_key_exists('surface_id',$blnMetadata) && array_key_exists($blnMetadata['surface_id'],$surfacesNonce2surface)) {
        $baseline->setSurfaceID($surfacesNonce2surface[$blnMetadata['surface_id']]->getID());
      }
      $baseline->save();
      if (!$baseline->hasError()) {
        $baselineNonce2baseline[$blnNonce] = $baseline;
      }
    }
  }
  error_log("$textCKN updated");
}
?>
