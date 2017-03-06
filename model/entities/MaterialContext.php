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
  * Classes to deal with MaterialContext entities
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
//****************   MATERIALCONTEXT CLASS    **********************************
//*******************************************************************
  /**
  * MaterialContext represents materialcontext entity which is metadata about an artefact
  *
  * <code>
  * require_once 'MaterialContext.php';
  *
  * $materialcontext = new MaterialContext( $resultRow );
  * echo $materialcontext->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class MaterialContext extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_arch_context_id,
              $_find_status;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an materialcontext instance from an materialcontext table row
    * @param array $row associated with columns of the materialcontext table, a valid mcx_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'materialcontext';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
        $dbMgr->query("SELECT * FROM materialcontext WHERE mcx_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= mcx_owner_id or ".getUserID()." = ANY (\"mcx_visibility_ids\"))")." LIMIT 1");
        $row = $dbMgr->fetchResultRow(0);
        if(!array_key_exists('mcx_id',$row)) {
          error_log("unable to query for materialcontext ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['mcx_id'] ? $arg['mcx_id']:NULL;
        $this->_arch_context_id=@$arg['mcx_arch_context_id'] ? $arg['mcx_arch_context_id']:NULL;
        $this->_find_status=@$arg['mcx_find_status'] ? $arg['mcx_find_status']:NULL;
        if (!array_key_exists('mcx_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new materialcontext to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "mcx";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_arch_context_id) {
        $this->_data['mcx_arch_context_id'] = $this->_arch_context_id;
      }
      if (count($this->_find_status)) {
        $this->_data['mcx_find_status'] = $this->_find_status;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Gets the archaeological context term ID for this MaterialContext
    * @return int for the term ID of the archaeological context
    */
    public function getArchContextID() {
      return $this->_arch_context_id;
    }

    /**
    * Gets the find status term ID : attribution ID pair for this MaterialContext
    * @return string for the find status term ID : attribution ID pair for this MaterialContext
    */
    public function getFindStatus() {
      return $this->_find_status;
    }


    //********SETTERS*********

    /**
    * Sets the archaeological context term ID for this MaterialContext
    * @param int for the term ID of the archaeological context
    */
    public function setArchContextID($trmID) {
      if($this->_arch_context_id != $trmID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("mcx_arch_context_id",$trmID);
      }
      $this->_arch_context_id = $trmID;
    }

    /**
    * Sets the find status term ID : attribution ID pair for this MaterialContext
    * @param string $status is the find status term ID : attribution ID pair for this MaterialContext
    */
    public function setFindStatus($status) {
      if($this->_find_status != $status) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("mcx_find_status",$status);
      }
      $this->_find_status = $status;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
