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
  * Classes to deal with Span entities
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
  require_once (dirname(__FILE__) . '/Segments.php');
  require_once (dirname(__FILE__) . '/Lines.php');

//*******************************************************************
//****************   SPAN CLASS  *********************************
//*******************************************************************
  /**
  * Span represents span entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Span.php';
  *
  * $span = new Span( $resultRow );
  * echo "span contains segment with layer ".$span->getSegments(true)->current()->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Span extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_segment_ids = array(),
              $_segments,
              $_lines;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an span instance from an span table row
    * @param array $row associated with columns of the span table, a valid spn_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'span';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM span WHERE spn_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= spn_owner_id or ".getUserID()." = ANY (\"spn_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('spn_id',$row)) {
          error_log("unable to query for span ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['spn_id'] ? $arg['spn_id']:NULL;
        $this->_segment_ids=@$arg['spn_segment_ids'] ? $arg['spn_segment_ids']:NULL;
        if (!array_key_exists('spn_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('spn_segment_ids',$arg))$arg['spn_segment_ids'] = $this->idsToString($arg['spn_segment_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new span to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "spn";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_segment_ids)) {
        $this->_data['spn_segment_ids'] = $this->idsToString($this->_segment_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Span's segment unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string that contains segment IDs of this Span
    */
    public function getSegmentIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_segment_ids);
      }else{
        return $this->idsStringToArray($this->_segment_ids);
      }
    }

    /**
    * Get Span's segment objects
    *
    * @return segment iterator for the segments of this Span or NULL
    */
    public function getSegments($autoExpand = false) {
      if (!$this->_segments && $autoExpand && count($this->getSegmentIDs())>0) {
        $this->_segments = new Segments("seg_id in (".join(",",$this->getSegmentIDs()).")","seg_id",null,null);
        $this->_segments->setOrderMap($this->getSegmentIDs());//ensure the iterator will server objects as in order list of ids.
      }
      return $this->_segments;
    }

     /**
    * Get Lines object which contains all lines attached to this span
    *
    * @return Lines iterator with all lines linked to this span
    */
    public function getLines() {
      if (!$this->_lines) {
        $condition = $this->_id." = ANY (\"lin_span_ids\") ";
        $this->_lines = new Lines($condition,null,null,null);
        $this->_lines->setAutoAdvance(false);
      }
      return $this->_lines;
    }

    //********SETTERS*********

    /**
    * Set Span's segment unique IDs
    *
    * @param int array $segID that contains segment IDs of this Span
    */
    public function setSegmentIDs($segID) {
      if($this->_segment_ids != $segID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("spn_segment_ids",$this->idsToString($segID));
      }
      $this->_segment_ids = $segID;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
