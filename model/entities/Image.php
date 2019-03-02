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
  * Classes to deal with Image entities
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

//*******************************************************************
//****************   IMAGE CLASS  *********************************
//*******************************************************************
  /**
  * Image represents image entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Image.php';
  *
  * $image = new Image( $resultRow );
  * echo "image has layer # ".$image->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Image extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_title,
              $_type_id,
              $_url,
              $_boundary;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an image instance from an image table row
    * @param array $row associated with columns of the image table, a valid img_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'image';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM image WHERE img_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= img_owner_id or ".getUserID()." = ANY (\"img_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('img_id',$row)) {
          $msg = "unable to query for image ID = $arg ";
          array_push($this->_errors,$msg);
          error_log($msg);
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from array
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['img_id'] ? $arg['img_id']:NULL;
        $this->_title=@$arg['img_title'] ? $arg['img_title']:NULL;
        $this->_type_id = @$arg['img_type_id'] ? $arg['img_type_id']:NULL;
        $this->_url=@$arg['img_url'] ? $arg['img_url']:NULL;
        $this->_boundary =@$arg['img_image_pos'] ? $arg['img_image_pos']:NULL;
        if (!array_key_exists('img_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('img_image_pos',$arg))$arg['img_image_pos'] = $this->polygonsToString($arg['img_image_pos']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new image to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "img";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_title)) {
        $this->_data['img_title'] = $this->_title;
      }
      if ($this->_type_id) {
        $this->_data['img_type_id'] = $this->_type_id;
      }
      if (count($this->_url)) {
        $this->_data['img_url'] = $this->_url;
      }
      if (count($this->_boundary)) {
        $this->_data['img_image_pos'] = $this->polygonsToString($this->_boundary);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Gets the title for this Image
    * @return string title
    */
    public function getTitle() {
      return $this->_title;
    }

    /**
    * Gets the value/title for this Image
    * @return string title
    */
    public function getValue() {
      return $this->_title;
    }

    /**
    * Get Type of the image
    *
    * @return string from a typology of terms for types of images
    */
    public function getType() {
      return $this->_type_id;
    }

    /**
    * Get Type of the image
    *
    * @return int term ID from a typology of terms for types of images
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Image's url
    *
    * @param boolean $cropped tells whether to generate a cropped image url or return the raw URL (default = false)
    * @return string url for resource of this image or a cropping of the resource is boundary is set.
    */
    public function getURL($cropped = false) {
      if ($cropped && $this->_boundary){
        return constructCroppedImageURL($this->_url,$this->_boundary);
      }else{
        return $this->_url;
      }
    }

    /**
    * Get Image's Boundary
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return Polygon array|string|null for this image
    */
    public function getBoundary($asString = false) {
      if ($asString){
        return $this->polygonsToString($this->_boundary);
      }else{
        return $this->polyStringToArray($this->_boundary);
      }
    }

    //********SETTERS*********

    /**
    * Sets the tilte for this Image
    * @param string $title
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("img_title",$title);
      }
      $this->_title = $title;
    }

    /**
    * Set Type of the image
    *
    * @param string $type from a typology of terms for types of images
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("img_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set Type ID of the image
    *
    * @param int $typeID term ID from a typology of terms for types of images
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("img_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Set Image's url
    *
    * @param string $URL of this image
    */
    public function setURL($url) {
      if($this->_url != $url) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("img_url",$url);
      }
      $this->_url = $url;
    }

    /**
    * Set Image's boundary
    *
    * @param string $boundary of the form ((x1,y1),(x2,y2),....,(xn,yn)),(( , )...( , ))
    */
    public function setBoundary($boundary) {
      if($this->_boundary != $boundary) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("img_image_pos",$this->polygonsToString($boundary));
      }
      $this->_boundary = $boundary;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
