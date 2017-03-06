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
  * Classes to deal with Run entities
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
  require_once (dirname(__FILE__) . '/Text.php');
  require_once (dirname(__FILE__) . '/Baseline.php');

//*******************************************************************
//****************   RUN CLASS  *********************************
//*******************************************************************
  /**
  * Run represents run entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Run.php';
  *
  * $run = new Run( $resultRow );
  * echo "run contains segment with layer ".$run->getSegments(true)->current()->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Run extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_scribe_id,
              $_script_id,
              $_writing_id,
              $_text_id,
              $_text,
              $_baseline_id,
              $_baseline,
              $_image_pos;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an run instance from an run table row
    * @param array $row associated with columns of the run table, a valid run_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'run';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM run WHERE run_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= run_owner_id or ".getUserID()." = ANY (\"run_visibility_ids\"))")." LIMIT 1");
        $row = $dbMgr->fetchResultRow(0);
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        if(!$row || !array_key_exists('run_id',$row)) {
          error_log("unable to query for run ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['run_id'] ? $arg['run_id']:NULL;
        $this->_scribe_id=@$arg['run_scribe_id'] ? $arg['run_scribe_id']:NULL;
        $this->_script_id=@$arg['run_script_id'] ? $arg['run_script_id']:NULL;
        $this->_writing_id=@$arg['run_writing_id'] ? $arg['run_writing_id']:NULL;
        $this->_text_id=@$arg['run_text_id'] ? $arg['run_text_id']:NULL;
        $this->_baseline_id=@$arg['run_baseline_id'] ? $arg['run_baseline_id']:NULL;
        $this->_image_pos=@$arg['run_image_pos'] ? $arg['run_image_pos']:NULL;
        if (!array_key_exists('run_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array__image_poskey_exists('run_image_pos',$arg))$arg['run_image_pos'] = $this->polygonsToString($arg['run_image_pos']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new run to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "run";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_scribe_id) {
        $this->_data['run_scribe_id'] = $this->_scribe_id;
      }
      if ($this->_script_id) {
        $this->_data['run_script_id'] = $this->_script_id;
      }
      if ($this->_writing_id) {
        $this->_data['run_writing_id'] = $this->_writing_id;
      }
      if ($this->_text_id) {
        $this->_data['run_text_id'] = $this->_text_id;
      }
      if ($this->_baseline_id) {
        $this->_data['run_baseline_id'] = $this->_baseline_id;
      }
      if (count($this->_image_pos)) {
        $this->_data['run_image_pos'] = $this->polygonsToString($this->_image_pos);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Run's scribe
    *
    * @return string scribe of this Run
    */
    public function getScribe() {
      return $this->_scribe_id;
    }

    /**
    * Get Run's Script term unique ID
    *
    * @return int script term id for this run
    */
    public function getScriptID() {
      return $this->_script_id;
    }

    /**
    * Get Run's Writing Technique term unique ID
    *
    * @return int writing technique term id for this run
    */
    public function getWritingID() {
      return $this->_writing_id;
    }

    /**
    * Gets the unigue ID of the Text entity for this Run
    * @return int|null identifying the Text of this run
    */
    public function getTextID() {
      return $this->_text_id;
    }

    /**
    * Get Run's text
    *
    * @return text object linked with this run or NULL
    */
    public function getText($autoExpand = false) {
      if (!$this->_text && $autoExpand && is_integer($this->_text_id)) {
        $this->_text = new Text($this->_text_id);
      }
      return $this->_text;
    }

    /**
    * Gets the unigue ID of the Baseline entity for this Run
    * @return int|null identifying the Baseline of this run
    */
    public function getBaselineID() {
      return $this->_baseline_id;
    }

    /**
    * Get Run's baseline
    *
    * @return baseline object linked with this run or NULL
    */
    public function getBaseline($autoExpand = false) {
      if (!$this->_baseline && $autoExpand && is_integer($this->_baseline_id)) {
        $this->_baseline = new Baseline($this->_baseline_id);
      }
      return $this->_baseline;
    }

    /**
    * Get boundary polygon on baseline for this run
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return Polygon array|string|null which describes the boundary of the run within the baseline
    */
    public function getBaselineBoundary($asString = false) {
      if ($asString){
        return $this->polygonsToString($this->_image_pos);
      }else{
        return $this->polyStringToArray($this->_image_pos);
      }
    }


    //********SETTERS*********

    /**
    * Set Run's scribe
    *
    * @param string $scribe of this Run
    */
    public function setScribe($scribe) {
      if($this->_scribe_id != $scribe) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("run_scribe_id",$scribe);
      }
      $this->_scribe_id = $scribe;
    }

     /**
    * Set Run's Script term unique ID
    *
    * @param int $scriptID term id for this run
    */
    public function setScriptID($scriptID) {
      if($this->_script_id != $scriptID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("run_script_id",$scriptID);
      }
      $this->_script_id = $scriptID;
    }

     /**
    * Set Run's Writing Technique term unique ID
    *
    * @param int $writingID term id for this run
    */
    public function setWritingTechniqueID($writingID) {
      if($this->_writing_id != $writingID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("run_writing_id",$writingID);
      }
      $this->_writing_id = $writingID;
    }

    /**
    * Sets the unigue ID of the Text entity for this run
    * @param int $textID identifying the Text of this run
    */
    public function setTextID($textID) {
      if($this->_text_id != $textID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("run_text_id",$textID);
      }
      $this->_text_id = $textID;
    }

    /**
    * Sets the unigue ID of the Baseline entity for this run
    * @param int $textID identifying the Baseline of this run
    */
    public function setBaselineID($baselineID) {
      if($this->_baseline_id != $baselineID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("run_baseline_id",$baselineID);
      }
      $this->_baseline_id = $baselineID;
    }

    /**
    * Set Run's baseline image bounding polygon
    *
    * @param string $boundary of the form ((x1,y1),(x2,y2),....,(xn,yn)),(( , )...( , ))
    */
    public function setBaselineBoundary($boundary) {
      if($this->_image_pos != $boundary) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("run_image_pos",$this->polygonsToString($boundary));
      }
      $this->_image_pos = $boundary;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
