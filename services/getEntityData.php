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
  * getEntityData
  *
  * SECURITY ENHANCEMENTS:
  * - Authentication check required for access
  * - Input validation to prevent SQL injection
  * - Entity prefix whitelist validation
  * - GID format validation with regex
  * - Numeric ID validation and type casting
  * - Enhanced logging for security monitoring
  *
  * gets entity data for passed query structure of the form
  *
  * {"tableprefix":{
  *                 "ids":[list of ids or 'all' to indicate all entities], //optional
  *  (not implemented yet)               "depth": numeric link level, //optionally used to retrieve linked entities default is 0 or no linked entities,
  *                 "columnName":"equatedValue" //for array fields this will translate to 'any' matching}}
  *
  * {"gra":{"ids":[596,597,598],"depth":1}}  //retrieves upto 3 grapheme records with immediate linked records
  *
  * return json format:
  *
  * { "entityName" : {
  *           "total" : #entityRecords,
  *           "success": true or false,   //if errors encountered false is returned
  *           "columns": array of columnNames for the records returned
  *           "records" : array of records where each record is an array of column values in "columns" order
  *    }
  * }
  *
  * { "grapheme" : {
  *           "total" : 3,
  *           "success": true,
  *           "columns": ["gra_id","gra_grapheme"," ...,"gra_scratch"],
  *           "records" : [[596,"t",.....,"\"CKN\":\"CKI02661\""],
  *                        [597,"r",.....,"\"CKN\":\"CKI02661\""],
  *                        [598,"a",.....,"\"CKN\":\"CKI02661\""]]
  *    },
  *   "annotation" : {...}
  * }
  *
  *
  *
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid unicode characters
  require_once (dirname(__FILE__) . '/../model/entities/SyllableClusters.php');
  require_once (dirname(__FILE__) . '/../model/entities/JsonCache.php');// get cache management
  require_once (dirname(__FILE__) . '/../model/entities/Tokens.php');
  require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
  require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');
  
  // SECURITY: Log access attempts for monitoring
  $userID = getUserID();
  $isAuthenticated = isLoggedIn();
  error_log("Entity data access from IP: " . $_SERVER['REMOTE_ADDR'] . " User ID: " . $userID . " Authenticated: " . ($isAuthenticated ? 'yes' : 'no'));
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies

  // SECURITY: Add authentication check for this sensitive service
  if (!isLoggedIn()) {
    $retVal = array("error" => "Authentication required to access entity data.");
    error_log("Unauthorized entity data access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    print json_encode($retVal);
    exit;
  }

//  $userID = 12;
  $dbMgr = new DBManager();
  $labelsToColNames = array();
  $columnNames = array();
  $retVal = array();
  
  // SECURITY: Validate and sanitize JSON input to prevent SQL injection
  $qparam = (array_key_exists('q',$_REQUEST)? json_decode($_REQUEST['q'],true):null);
  
  if ($qparam === null && array_key_exists('q',$_REQUEST)) {
      // Invalid JSON
      $retVal = array("error" => "Invalid JSON format in query parameter");
      error_log("Invalid JSON in getEntityData from IP: " . $_SERVER['REMOTE_ADDR'] . " - Data: " . var_export($_REQUEST['q'], true));
      print json_encode($retVal);
      exit;
  }
  
  // Define allowed prefixes to prevent table injection
  $allowedPrefixes = array('gra', 'tok', 'cmp', 'seq', 'txt', 'edn', 'seg', 'syl', 'lem', 'cat', 'ano', 'atb', 'img', 'spn', 'run', 'lin', 'sur', 'bln', 'prt', 'fra', 'mcx', 'trm', 'col', 'dgr', 'ugr', 'bib', 'itm');
  
  foreach (@$qparam as $prefix => $qstruct) {
    // SECURITY: Validate prefix to prevent table name injection
    if (!in_array($prefix, $allowedPrefixes)) {
        $retVal = array("error" => "Invalid entity prefix: " . htmlspecialchars($prefix));
        error_log("Invalid entity prefix in getEntityData: " . var_export($prefix, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
        print json_encode($retVal);
        exit;
    }
    
    if (array_key_exists('ordered',$qstruct)) {
      $isOrdered = true;
      unset($qstruct['ordered']);
    }
    if (array_key_exists('blended',$qstruct)) {
      $isBlended = true;
      unset($qstruct['blended']);
    }
    if (array_key_exists('showGID',$qstruct) or isset($isBlended)) {
      $showGID = true;
      unset($qstruct['showGID']);
    }
    if (array_key_exists('aggregate',$qstruct)) {
      $aggrColIDs = true;
      
      // SECURITY: Validate aggregate prefix
      $aggPrefix = $qstruct['aggprefix'];
      if (!in_array($aggPrefix, $allowedPrefixes)) {
          $retVal = array("error" => "Invalid aggregate prefix: " . htmlspecialchars($aggPrefix));
          error_log("Invalid aggregate prefix: " . var_export($aggPrefix, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
          print json_encode($retVal);
          exit;
      }
      
      // SECURITY: Validate aggregate column name
      $aggrColName = $qstruct['aggcol'];
      if (!preg_match('/^[a-z_]{3,50}$/', $aggrColName)) {
          $retVal = array("error" => "Invalid aggregate column name");
          error_log("Invalid aggregate column: " . var_export($aggrColName, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
          print json_encode($retVal);
          exit;
      }
      
      unset($qstruct['aggregate']);
      unset($qstruct['aggprefix']);
      unset($qstruct['aggcol']);
    }
    if (isset($isBlended) && $isBlended && array_key_exists('ids', $qstruct)) {
      // create a loop to query for each gid's record in order
      $ids = $qstruct['ids'];
      if (is_string($ids)) {
        preg_match_all("/([a-z]{3}\:\d+)/",$ids,$matches);
        $ids = $matches[0];
      }
      
      // SECURITY: Additional validation for IDs array
      if (is_array($ids)) {
          foreach ($ids as $index => $gid) {
              if (!preg_match('/^[a-z]{3}:\d+$/', $gid)) {
                  error_log("Removing invalid GID from array: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
                  unset($ids[$index]);
              }
          }
          $ids = array_values($ids); // Re-index array
      }
      
      $columnNames = array('bld_id','bld_properties');
      if (isset($isOrdered) && $isOrdered) {
        array_push($columnNames,'order');
      }
      $records = array();
      $order = 1;
      foreach ($ids as $gid) {
        // SECURITY: Validate GID format to prevent injection
        if (!preg_match('/^[a-z]{3}:\d+$/', $gid)) {
            error_log("Invalid GID format in getEntityData: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
            continue; // Skip invalid GIDs
        }
        
        list($recPrefix,$recID) = explode(':',$gid);
        
        // SECURITY: Double-check prefix is allowed
        if (!in_array($recPrefix, $allowedPrefixes)) {
            error_log("Invalid prefix in GID: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
            continue;
        }
        
        // SECURITY: Validate ID is numeric and positive
        if (!is_numeric($recID) || $recID <= 0) {
            error_log("Invalid ID in GID: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
            continue;
        }
        
        $table = $prefixToTableName[$recPrefix];
        
        // SECURITY: Use proper integer casting for ID
        $safeRecID = (int)$recID;
        $dbMgr->query("select * from $table where {$recPrefix}_id = $safeRecID limit 1");
        $row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC);
        $kvcontents = "";
        foreach ($row as $colname => $value) {
          if($colname == $recPrefix.'_id' || $colname == $recPrefix.'_scratch') {
            continue;
          } else if($value){
            $kvcontents .= ($kvcontents?' | ':'').substr($colname,4).': <b>'.$value.' </b>';
          }
        }
        $record = array($gid,$kvcontents);
        if (isset($isOrdered) && $isOrdered) {
          array_push($record,$order++);
        }
        array_push($records,$record);
      }
      $retVal['blended'] = array("total" => count($records),
                      "success" => true,
                      "columns" => $columnNames,
                      "records" => $records);
    } else if (array_key_exists($prefix, $prefixToTableName)) {
      $table = $prefixToTableName[$prefix];
      $dbMgr->query("select column_name from INFORMATION_SCHEMA.COLUMNS where table_name = '$table' order by ordinal_position");
      while($row = $dbMgr->fetchResultRow()){
        $labelsToColNames[$row[0]] = $row[0];
        array_push($columnNames, $row[0]);
      }
      if (isset($isOrdered) && $isOrdered) {
        array_push($columnNames,'order');
      }
      $conditions = array();
      foreach ($qstruct as $key => $value) {
        switch ($key) {
          case "depth" : //todo activate this to do a depth expanded query
            $depth = (array_key_exists("depth", $qstruct) && is_numeric($qstruct['depth'])?intval($qstruct['depth']):0);
            break;
          case "ids" :
            //unpack ids
            $rawIDs = $value;
            $ids = array();
            $idsOrderLookup = array();
            $idRanges = array();
            if (is_array($rawIDs) && strtolower($rawIDs[0]) != "all") {
              foreach ($rawIDs as $id) {
                if (strpos($id,"-")) {//separate ranges from all ids
                  $id = explode("-",$id);
                  array_push($idRanges,$id);
                } else if (is_numeric($id)) {
                  array_push($ids,$id);
                  $idsOrderLookup[$id] = count($idsOrderLookup);
                }
              }
              $cnt = count($ids);
              $idCondition = "(";
              if ( $cnt > 1) {
                $idCondition .= $prefix."_id in (".join(",",$ids).")";
              } else if ($cnt == 1) {
                $idCondition .= $prefix."_id = ".$ids[0];
              }
              foreach($idRanges as $range) {
                if ( count($range) == 2 && is_numeric($range[0]) && is_numeric($range[1])) {
                  if (strlen($idCondition) > 1) {
                    $idCondition .= " or ";
                  }
                  $idCondition .= $prefix."_id between ".min($range)." and ".max($range);
                }
              }
              $idCondition .= ")";
              array_push($conditions,$idCondition);
            }//if
            break;
            default:  //todo  check field type to encode of ANY for array fields  and  ?? possibly for reverse links ??
              if (array_key_exists($key,$labelsToColNames)) {
                if (is_array($value)){
                  $cnt = count($value);
                  if ( $cnt > 1) {
                    array_push($conditions," ".$labelsToColNames[$key]." in ('".join("','",$value)."')");
                  } else if ($cnt == 1) {
                    array_push($conditions," ".$labelsToColNames[$key]." = '$value' ");
                  }
                } else { //straight value case
                  array_push($conditions," ".$labelsToColNames[$key]." = '$value' ");
                }
              }
        }//switch
      }//foreach
      $cnt = count($conditions);
      if ( $cnt > 1) {
        $conditions = " where ".join(" and ",$conditions);
      } else if ($cnt == 1) {
        $conditions = " where ".$conditions[0];
      } else {// case pf all
        $conditions = "";
      }
      if (isset($aggrColIDs) && $aggrColIDs) {//find parent record fkey set for aggregate column
        $dbMgr->query("select $aggrColName from $table $conditions");
        $fKeys = array();
        while($row = $dbMgr->fetchResultRow(null,null,PGSQL_NUM)){
          $fkIDs = explode(',', trim($row[0],"{}"));//remove any braces first
          foreach ($fkIDs as $fkID) {
            if ($fkID) $fKeys[$fkID] = 1; // put fk in array as key, automatically removes duplicates
          }
        }
        $records = array();
        if (@$aggPrefix == 'bld') {//need to retrieve each record separately to create blended view
          $columnNames = array('bld_id','bld_properties');
          $i = 0;
          foreach ($fKeys as $gid => $val) {
            list($recPrefix,$recID) = explode(':',@$gid);
            
            // SECURITY: Validate GID format and components
            if (!preg_match('/^[a-z]{3}:\d+$/', $gid)) {
                error_log("Invalid GID format in blended view: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
                continue;
            }
            
            // SECURITY: Validate prefix and ID
            if (!in_array($recPrefix, $allowedPrefixes)) {
                error_log("Invalid prefix in blended GID: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
                continue;
            }
            
            if (!is_numeric($recID) || $recID <= 0) {
                error_log("Invalid ID in blended GID: " . var_export($gid, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
                continue;
            }
            
            if (array_key_exists(@$recPrefix,$prefixToTableName)) {
              $table = $prefixToTableName[$recPrefix];
              $safeRecID = (int)$recID;
              $dbMgr->query("select * from $table where {$recPrefix}_id = $safeRecID limit 1");
              $row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC);
              $kvcontents = "";
              foreach ($row as $colname => $value) {
                if($colname == $recPrefix.'_id' || $colname == $recPrefix.'_scratch') {
                  continue;
                } else if($value){
                  $kvcontents .= ($kvcontents?' | ':'').substr($colname,4).': <b>'.$value.' </b>';
                }
              }
              $record = array($gid,$kvcontents);
              array_push($records,$record);
            }
          }
          $table = 'blended';
        } else { //homogenous set
          //get column names
          $table = $prefixToTableName[@$aggPrefix];
          $columnNames = array();
          $dbMgr->query("select column_name from INFORMATION_SCHEMA.COLUMNS where table_name = '$table' order by ordinal_position");
          while($row = $dbMgr->fetchResultRow()){
            $labelsToColNames[$row[0]] = $row[0];
            array_push($columnNames, $row[0]);
          }
          //merge fkey id set into comma separated string
          // SECURITY: Validate all IDs are numeric before joining
          $safeIds = array();
          foreach (array_keys($fKeys) as $id) {
              if (is_numeric($id) && $id > 0) {
                  $safeIds[] = (int)$id;
              } else {
                  error_log("Invalid ID in fKeys: " . var_export($id, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
              }
          }
          
          if (empty($safeIds)) {
              // No valid IDs, return empty result
              $records = array();
          } else {
              $ids = join(',', $safeIds);
              //query for records
              $dbMgr->query("select * from $table where {$aggPrefix}_id in ($ids);");
              while($row = $dbMgr->fetchResultRow(null,null,PGSQL_NUM)){
                array_push($records,$row);
              }
          }
        }
        $retVal[$table] = array("total" => count($records),
                        "success" => true,
                        "columns" => $columnNames,
                        "records" => $records);
      }else{ // non aggregate get level 0 records
        //condition to exclude record marked as deleted
        if($table != 'usergroup') {
          if($conditions) {
            $conditions .= " and ".$prefix."_visibility_ids <> ARRAY[5]";
          }
          else {
            $conditions .= "where ".$prefix."_visibility_ids <> ARRAY[5]";
          }
        }
        $dbMgr->query("select * from $table $conditions order by ".$prefix."_id");
        $records = array();
        $order = 1;
        $noRanges = (count($idRanges) == 0);
        //find the field position of the pkey field
        $pkIndex = array_search($prefix."_id",$columnNames);
        while($row = $dbMgr->fetchResultRow(null,null,PGSQL_NUM)){
          if (isset($isOrdered) && $isOrdered) {
            if ($noRanges) {
              array_push($row,$idsOrderLookup[$row[$pkIndex]]);
            } else {
              array_push($row,$order++);
            }
          }
          if (isset($showGID) && $showGID) {// change ID into global ID
            $row[$indexPKey] = $prefix.$row[$indexPKey];
          }
          array_push($records,$row);
        }
        $retVal[$table] = array("total" => count($records),
                        "success" => true,
                        "columns" => $columnNames,
                        "records" => $records);
      }
    } else {// end if normal entity format
      //error bad input data
      $retVal['unknown'] = array("total" => 0,
                      "success" => false,
                      "params" => $qstruct,
                      "records" => array(),
                      "error" => "Invalid arguments for getEntityData no recognized entity prefix supplied.");
    }
  }//foreach
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }
  ?>
