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
  * Classes to deal with Era entities
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

//*******************************************************************
//****************   ERA CLASS  *********************************
//*******************************************************************
  /**
  * Era represents era entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Era.php';
  *
  * $era = new Era( $resultRow );
  * echo "era title is".$era->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Era extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_title,
              $_order,
              $_preferred,
              $_begin_date,
              $_end_date;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an era instance from an era table row
    * @param array $row associated with columns of the era table, a valid era_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'era';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM era WHERE era_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= era_owner_id or ".getUserID()." = ANY (\"era_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('era_id',$row)) {
          error_log("unable to query for era ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['era_id'] ? $arg['era_id']:NULL;
        $this->_title=@$arg['era_title'] ? $arg['era_title']:NULL;
        $this->_order=@$arg['era_order'] ? $arg['era_order']:NULL;
        $this->_preferred=@$arg['era_preferred'] ? $arg['era_preferred']:NULL;
        $this->_begin_date=@$arg['era_begin_date'] ? $arg['era_begin_date']:NULL;
        $this->_end_date=@$arg['era_end_date'] ? $arg['era_end_date']:NULL;
        if (!array_key_exists('era_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new era to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "era";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_title)) {
        $this->_data['era_title'] = $this->_title;
      }
      if ($this->_order) {
        $this->_data['era_order'] = $this->_order;
      }
      if ($this->_preferred) {
        $this->_data['era_preferred'] = $this->_preferred;
      }
      if ($this->_begin_date) {
        $this->_data['era_begin_date'] = $this->_begin_date;
      }
      if ($this->_end_date) {
        $this->_data['era_end_date'] = $this->_end_date;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Era's title
    *
    * @return string title of this Era
    */
    public function getTitle() {
      return $this->_title;
    }

    /**
    * Get Era's order
    *
    * @return int order of this Era
    */
    public function getOrder() {
      return $this->_order;
    }

    /**
    * Get Era's preferred status
    *
    * @return boolean preffered status of this Era
    */
    public function getPreferred() {
      return $this->_preferred;
    }

    /**
    * Get Era's begin date
    *
    * @return int date for the beginning of this Era
    */
    public function getBeginDate() {
      return $this->_begin_date;
    }

    /**
    * Get Era's ending date
    *
    * @return int date for the ending of this Era
    */
    public function getEndDate() {
      return $this->_end_date;
    }


    //********SETTERS*********

    /**
    * Set Era's title
    *
    * @param string $title of this Era
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("era_title",$title);
      }
      $this->_title = $title;
    }

    /**
    * Set Era's order
    *
    * @param string $order of this Era
    */
    public function setOrder($order) {
      if($this->_order != $order) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("era_order",$order);
      }
      $this->_order = $order;
    }

    /**
    * Set Era's preferred status
    *
    * @param boolean preffered $status of this Era
    */
    public function setPreferred($status) {
      if($this->_preferred != $status) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("era_preferred",$status);
      }
      $this->_preferred = $status;
    }

    /**
    * Set Era's begin date
    *
    * @param int $date for the beginning of this Era
    */
    public function setBeginDate($date) {
      if($this->_begin_date != $date) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("era_begin_date",$date);
      }
      $this->_begin_date = $date;
    }

    /**
    * Set Era's ending date
    *
    * @param int $date for the ending of this Era
    */
    public function setEndDate($date) {
      if($this->_end_date != $date) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("era_end_date",$date);
      }
      $this->_end_date = $date;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
