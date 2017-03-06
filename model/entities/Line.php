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
  * Classes to deal with Line entities
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
  require_once (dirname(__FILE__) . '/Spans.php');

//*******************************************************************
//****************   LINE CLASS  *********************************
//*******************************************************************
  /**
  * Line represents line entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Line.php';
  *
  * $line = new Line( $resultRow );
  * echo "line contains segment with layer ".$line->getSegments(true)->current()->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Line extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_order,
              $_mask,
              $_span_ids = array(),
              $_spans;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an line instance from an line table row
    * @param array $row associated with columns of the line table, a valid lin_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'line';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM line WHERE lin_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= lin_owner_id or ".getUserID()." = ANY (\"lin_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('lin_id',$row)) {
          error_log("unable to query for line ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['lin_id'] ? $arg['lin_id']:NULL;
        $this->_order=@$arg['lin_order'] ? $arg['lin_order']:NULL;
        $this->_mask=@$arg['lin_mask'] ? $arg['lin_mask']:NULL;
        $this->_span_ids=@$arg['lin_span_ids'] ? $arg['lin_span_ids']:NULL;
        if (!array_key_exists('lin_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('lin_span_ids',$arg))$arg['lin_span_ids'] = $this->idsToString($arg['lin_span_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as ne_orderw line to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "lin";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_order) {
        $this->_data['lin_order'] = $this->_order;
      }
      if (count($this->_mask)) {
        $this->_data['lin_mask'] = $this->_mask;
      }
      if (count($this->_span_ids)) {
        $this->_data['lin_span_ids'] = $this->idsToString($this->_span_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Line's order
    *
    * @return int order of this Line
    */
    public function getOrder() {
      return $this->_order;
    }

    /**
    * Get Line's mask
    *
    * @return int mask of this Line
    */
    public function getMask() {
      return $this->_mask;
    }

    /**
    * Get Line's span unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string that contains span IDs of this Line
    */
    public function getSpanIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_span_ids);
      }else{
        return $this->idsStringToArray($this->_span_ids);
      }
    }

    /**
    * Get Line's span objects
    *
    * @return span iterator for the spans of this Line or NULL
    */
    public function getSpans($autoExpand = false) {
      if (!$this->_spans && $autoExpand && count($this->getSpanIDs())>0) {
        $this->_spans = new Spans("spn_id in (".join(",",$this->getSpanIDs()).")",null,null,null);
        $this->_spans->setAutoAdvance(false);
        $this->_spans->setOrderMap($this->getSpanIDs());//ensure the iterator will server objects as in order list of ids.
      }
      return $this->_spans;
    }


    //********SETTERS*********

    /**
    * Set Line's order
    *
    * @param string $order of this Line
    */
    public function setOrder($order) {
      if($this->_order != $order) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lin_order",$order);
      }
      $this->_order = $order;
    }

    /**
    * Set Line's mask
    *
    * @param string $mask of this Line
    */
    public function setMask($mask) {
      if($this->_mask != $mask) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lin_mask",$mask);
      }
      $this->_mask = $mask;
    }

    /**
    * Set Line's span unique IDs
    *
    * @param int array $spanID that contains span IDs of this Line
    */
    public function setSpanIDs($spanID) {
      if($this->_span_ids != $spanID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lin_span_ids",$this->idsToString($spanID));
      }
      $this->_span_ids = $spanID;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
