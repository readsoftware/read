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
  * Classes to deal with Bibliography entities
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
//****************   BIBLIOGRPHY CLASS    **********************************
//*******************************************************************
  /**
  * Bibliography represents bibliography entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Bibliography.php';
  *
  * $bibliography = new Bibliography( $resultRow );
  * echo $bibliography->getName();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Bibliography extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_name;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an bibliography instance from an bibliography table row
    * @param array $row associated with columns of the bibliography table, a valid bib_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'bibliography';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM bibliography WHERE bib_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= bib_owner_id or ".getUserID()." = ANY (\"bib_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('bib_id',$row)) {
          error_log("unable to query for bibliography ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['bib_id'] ? $arg['bib_id']:NULL;
        $this->_name=@$arg['bib_name'] ? $arg['bib_name']:NULL;
        if (!array_key_exists('bib_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new bibliography to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "bib";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_name)) {
        $this->_data['bib_name'] = $this->_name;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Gets the name for this Bibliography
    * @return string name
    */
    public function getName() {
      return $this->_name;
    }


    //********SETTERS*********
    /**
    * Sets the name for this Bibliography
    * @param string $name
    */
    public function setName($name) {
      if($this->_name != $name) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("bib_name",$name);
      }
      $this->_name = $name;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
