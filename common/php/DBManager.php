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
  * Database Access and Helper Classes
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */

  require_once (dirname(__FILE__) . '/../../config.php');//get database defines

  /**
  * Database Manager encapsulates database access
  *
  * <code>
  * require_once 'databaseManager.php';
  *
  * $dbMgr = new DBManager();
  * $dbMgr->query($queryString);
  * $obj->loadFromResults($dbMgr);
  * </code>
  *
  * @author Stephen White  <stephenawhite57@gmail.com>
  * @todo   Add code to preload common (parameterised or not) queries and perhaps encapsulate all oboject creation
  * @todo   ??Add triggers for lastmodified to tables for caching?? research more against user stories
  */

  class DBManager {

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private $_dbHandle,
            $_psqlInfo,
            $_ver,
            $_conn,
            $_dbName,
            $_query,
            $_results,
            $_rowCount = 0,
            $_affectedRowCount = 0,
            $_queryResultSynchNeeded = true,
            $_error;

    /**
     * Store the DB name globally to use when initiate the DBManager object.
     *
     * @var string|null
     */
    private static $_dbSharedName = null;
    private static $_dbSharedHandle = null;
    private static $_dbSharedPsqlInfo = null;
    private static $_dbSharedVer = null;
    private static $_dbSharedConn = null;


    //****************************CONSTRUCTOR/DESTRUCTOR FUNCTION***************************************

    /**
    * Create an DBManager instance, attemps to connect to the database using passed params, configured defines or defautls
    * @access public
    */
    public function __construct($dbname = "",$user = "", $password = "" ) {// todo SAW expand this to take parameters for connection values as overrides
      $this->_error = null;
      if (self::$_dbSharedHandle) { //code has set shared handle so use it
        $this->_conn = self::$_dbSharedConn;
        $this->_dbName = self::$_dbSharedName;
        $this->_dbHandle = self::$_dbSharedHandle;
        $this->_psqlInfo = self::$_dbSharedPsqlInfo;
        $this->_ver = self::$_dbSharedVer;
        
        return;
      }
      // Resolve DB name.
      if (empty($dbname)) {
        if (empty(self::$_dbSharedName)) {
          $dbname = defined('DBNAME') ? DBNAME : "default";
        } else {
          $dbname = self::$_dbSharedName;
        }
      }
      if ($dbname) {
        $this->_dbName = $dbname;
      }
      $this->_conn = "dbname='". $dbname . "'" .
                    " user='".($user?$user."'":(defined('USERNAME')?USERNAME."'":"white'")).
                    " password='".($password?$password."'":(defined('PASSWORD')?PASSWORD."'":"'")).
                    " host='".(defined('DBSERVERNAME')?DBSERVERNAME."'":"localhost'").
                    " port='".(defined('PORT')?PORT."'":"5432'");
      $this->_connect();
   }

    function __destruct(){
      if($this->_dbHandle && (pg_connection_status($this->_dbHandle) == PGSQL_CONNECTION_OK)){
        $this->_dbHandle = null;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Query the database using the supplied query string or previously stored if nothing supplied
    *
    * @param string $query
    */
    public function query( $query = null ) {
      if (!$this->_dbHandle) {
        $this->_connect();
      }
      if (!$this->_dbHandle) {
        return;
      }
      if(!$query) {
        $query = $this->_query;
      }
//list($usec, $sec) = explode(' ', microtime());
//$stime = $sec + $usec;//start time
      if ($query && pg_send_query($this->_dbHandle, $query)) {
        $this->_results = pg_get_result($this->_dbHandle);
      }
//list($usec, $sec) = explode(' ', microtime());
//$ttime = $sec + $usec - $stime;//total time
//error_log("time=$ttime for q= ".$query);
      if($this->_results) {
        $this->_rowCount = pg_num_rows($this->_results);
        $this->_affectedRowCount = pg_affected_rows($this->_results);
        $this->_query = $query;
        $this->_queryResultSynchNeeded = false;
      }else{
        $this->_rowCount = 0;
        $this->_affectedRowCount = 0;
        $this->_query = $query;
      }
      $this->_error = pg_last_error($this->_dbHandle);
    }

    /**
    * ClearResults
    *
    * clears the results of a connection
    */
    public function clearResults() {
      while (pg_get_result($this->_dbHandle)) {
      }
    }

    /**
    * Start transaction
    *
    * @return boolean true is successful, false otherwise
    */
    public function startTransaction() {
      if(!$this->_dbHandle) {
        $this->_connect();
      }
      if (pg_send_query($this->_dbHandle, "BEGIN TRANSACTION")) {
        pg_get_result($this->_dbHandle);
        return true;
      }
      $this->_error = pg_last_error($this->_dbHandle);
      return false;
    }

    /**
    * Rollback changes
    *
    * @return boolean true is successful, false otherwise
    */
    public function rollback() {
      if(!$this->_dbHandle) {
        $this->_connect();
      }
      if (pg_send_query($this->_dbHandle, "ROLLBACK")) {
        pg_get_result($this->_dbHandle);
        return true;
      }
      $this->_error = pg_last_error($this->_dbHandle);
      return false;
    }

    /**
    * Commit changes
    *
    * @return boolean true is successful, false otherwise
    */
    public function commit() {
      if(!$this->_dbHandle) {
        $this->_connect();
      }
      if (pg_send_query($this->_dbHandle, "COMMIT")) {
        pg_get_result($this->_dbHandle);
        return true;
      }
      $this->_error = pg_last_error($this->_dbHandle);
      return false;
    }

    /**
    * Fetches a row of data from the result set
    *
    * @param int $index of the row to return where null gets next row
    * @param boolean $autoSynch the result set with the stored query if out of synch
    * @param int $type indicates the type of PSQL array fetch default is PGSQL_BOTH
    * @return array returns a zero based indexed and associated array for one row of data for a given query or FALSE on no more results or error
    * @link http://php.net/manual/en/function.pg-fetch-array.php‎
    */
    public function fetchResultRow( $index = null, $autoSynch = true, $type = PGSQL_BOTH) {
      if($autoSynch && $this->_queryResultSynchNeeded) {
        $this->query();
      }
      if (!$this->_results || $this->_rowCount== 0) {
        return false;//no result is treated the same as no more results
      }else{
        return pg_fetch_array($this->_results, $index, $type);
      }
    }

    /**
    * Fetches an array of all result rows of data from the result set
    *
    * @param boolean $autoSynch the result set with the stored query if out of synch
    * @param int $type indicates the type of PSQL array fetch default is PGSQL_BOTH
    * @return array returns a zero based indexed array all result rows for a given query or FALSE on no more results or error
    * @link http://php.net/manual/en/function.pg-fetch-all.php‎
    */
    public function fetchAllResultRows( $autoSynch = true) {
      if($autoSynch && $this->_queryResultSynchNeeded) {
        $this->query();
      }
      if (!$this->_results || $this->_rowCount== 0) {
        return false;//no result is treated the same as no more results
      }else{
        return pg_fetch_all($this->_results);
      }
    }

    /**
    * Update table data
    *
    * @return boolean indicating success or failure
    */
    public function update($table,$data,$condition) {
      $keys = join(",",array_keys($data));
      $values = array_values($data);
      $paramList = '$1';
      for($i=2; $i<=count($values);$i++) {
        $paramList .= ',$'.$i;
      }
      if ($this->_ver >= 10) {
        $query = "UPDATE $table SET ($keys) = ROW($paramList) WHERE $condition ;";
      } else {
        $query = "UPDATE $table SET ($keys) = ($paramList) WHERE $condition ;";
      }
      $res = pg_query_params($this->_dbHandle,$query,$values);
      if (!$res) {// failed, store last error in _error
        $this->_error = pg_last_error($this->_dbHandle);
        return false;
      }
      return $res;
    }

    /**
    * Insert table data
    *
    * @return int|false int id indicating success and serial id of inserted record or false for failure
    */
    public function insert($table,$data,$idColumnName) {
      $keys = join(",",array_keys($data));
      $values = array_values($data);
      $paramList = '$1';
      for($i=2; $i<=count($values);$i++) {
        $paramList .= ',$'.$i;
      }
      $query = "INSERT INTO $table ($keys) VALUES ($paramList) ;";
      $res = pg_query_params($this->_dbHandle,$query,$values);
      if ($res) {//success get the id of row inserted and return it
        $query = "SELECT CURRVAL(pg_get_serial_sequence('$table','$idColumnName'))";
        $res = pg_query($this->_dbHandle,$query);
        if ($res) {
          $row = pg_fetch_row($res);
          return $row[0];
        }
      }
      if (!$res) {// failed, store last error in _error
        $this->_error = pg_last_error($this->_dbHandle);
        return false;
      }
    }

    /**
    * Test connected
    *
    * @return boolean for connected status
    */
    public function isConnected() {
      return ($this->_dbHandle && pg_connection_status($this->_dbHandle)=== PGSQL_CONNECTION_OK);
    }


    //********GETTERS*********
    /**
    * Get connection string
    *
    * @return string returns the connection string
    */
    public function getConnection() {
      return $this->_conn;
    }

        /**
    * Get dbName
    *
    * @return string returns the db name
    */
    public function getDBName() {
      return $this->_dbName;
    }

/**
    * Get affected row count
    *
    * @return int returns the number of affected rows
    */
    public function getAffectedRowCount() {
      return $this->_affectedRowCount;
    }

    /**
    * Get row count
    *
    * @return int returns the number of result rows
    */
    public function getRowCount() {
      return $this->_rowCount;
    }

    /**
    * Get query string
    *
    * @return string returns the query string
    */
    public function getQuery() {
      return $this->_query;
    }

    /**
    * Get error string
    *
    * @return string of the lasterror
    */
    public function getError() {
      return $this->_error;
    }

    //********SETTERS*********
    /**
    * Sets the query string
    *
    * @return string returns the query string
    */
    public function setQuery($query) {
      $this->_query = $query;
      $this->_queryResultSynchNeeded = true;
    }

    /**
    * Sets connection string and ensures that
    */
    public function setConnection($dbname = "",$user = "", $password = "") {
      $conn = "dbname='".($dbname?$dbname."'":(defined('DBNAME')?DBNAME."'":"kanishka'")).
              " user='".($user?$user."'":(defined('USERNAME')?USERNAME."'":"postgres'")).
              " password='".($password?$password."'":(defined('PASSWORD')?PASSWORD."'":"'")).
              " host='".(defined('DBSERVERNAME')?DBSERVERNAME."'":"localhost'").
              " port=".(defined('PORT')?PORT."'":"5432");
      if($this->_conn !== $conn && $this->_dbHandle) {//previous connection is changing so close current
          pg_close($this->_dbHandle);
          $this->_dbHandle = null;
      }
      if (!$this->_dbHandle) {
        $this->_conn = $conn;
        $this->_connect();//set dbHandle if successful
      }
   }

    /**
     * Set dbManager factory common $handle.
     *
     * After the database Manager is created, calling this function will ensure
     * all future initialization of the DBManager use the same connection
     *
     * @param string $this The database manager with connection to share.
     */
    public function useCommonConnect() {
      self::$_dbSharedConn = $this->_conn;
      self::$_dbSharedHandle = $this->_dbHandle;
      self::$_dbSharedPsqlInfo = $this->_psqlInfo;
      self::$_dbSharedVer = $this->_ver;
    }

    /**
     * Remove dbManager factory common $handle.
     *
     * After the database Manager is created, calling this function will ensure
     * all future initialization of the DBManager will try to create a connection.
     */
    public static function removeCommonConnect() {
      self::$_dbSharedConn = null;
      self::$_dbSharedHandle = null;
      self::$_dbSharedPsqlInfo = null;
      self::$_dbSharedVer = null;
    }

    /**
     * Use the database by providing the name.
     *
     * After set the database to use, all future initialization of the DBManager
     * object will use this database unless the database name is specified in the
     * constructor.
     *
     * @param string $dbSharedName The database name.
     */
    public static function setDefaultDBName($dbSharedName) {
      self::$_dbSharedName = $dbSharedName;
    }

    //*******************************PRIVATE FUNCTIONS************************************

    /**
    * Connects to the database using configured constants or passed parameters
    *
    * @param type desc
    */
    private function _connect( ) {
      $this->_dbHandle = @pg_pconnect($this->_conn);
      if (!$this->_dbHandle){
        $this->_error = "Unable to connect to database using ".$this->_conn;
        error_log($this->_error);
        $this->_conn = null;
      } else {
        $res = pg_query($this->_dbHandle,"select version();");
        if ($res) {
          $row = pg_fetch_row($res);
          $this->_psqlInfo = $row[0];
          list($sqlType,$ver) = explode(" ",substr($row[0], 0, strpos($row[0],','))); //warning depends on "select version()" format
          if (strtolower($sqlType) != "postgresql" ) {
            $this->_error = "This system require PostgreSql";
          }
          $this->_ver = $ver;
        }
      }
    }
  }
?>
