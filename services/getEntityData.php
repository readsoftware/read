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
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
//  $userID = 12;
  $dbMgr = new DBManager();
  $labelsToColNames = array();
  $columnNames = array();
  $retVal = array();
  $qparam = (array_key_exists('q',$_REQUEST)? json_decode($_REQUEST['q'],true):null);
  foreach (@$qparam as $prefix => $qstruct) {
    if (array_key_exists('ordered',$qstruct)) {
      $isOrdered = true;
      unset($qstruct['ordered']);
    }
    if (array_key_exists('blended',$qstruct)) {
      $isBlended = true;
      unset($qstruct['blended']);
    }
    if (array_key_exists('showGID',$qstruct) or @$isBlended) {
      $showGID = true;
      unset($qstruct['showGID']);
    }
    if (array_key_exists('aggregate',$qstruct)) {
      $aggrColIDs = true;
      $aggPrefix = $qstruct['aggprefix'];
      $aggrColName = $qstruct['aggcol'];
      unset($qstruct['aggregate']);
      unset($qstruct['aggprefix']);
      unset($qstruct['aggcol']);
    }
    if (@$isBlended && array_key_exists('ids', $qstruct)) {
      // create a loop to query for each gid's record in order
      $ids = $qstruct['ids'];
      if (is_string($ids)) {
        preg_match_all("/([a-z]{3}\:\d+)/",$ids,$matches);
        $ids = $matches[0];
      }
      $columnNames = array('bld_id','bld_properties');
      if (@$isOrdered) {
        array_push($columnNames,'order');
      }
      $records = array();
      $order = 1;
      foreach ($ids as $gid) {
        list($recPrefix,$recID) = explode(':',$gid);
        $table = $prefixToTableName[$recPrefix];
        $dbMgr->query("select * from $table where $recPrefix"."_id = $recID limit 1");
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
        if (@$isOrdered) {
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
      if (@$isOrdered) {
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
      if (@$aggrColIDs) {//find parent record fkey set for aggregate column
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
            if (array_key_exists(@$recPrefix,$prefixToTableName)) {
              $table = $prefixToTableName[$recPrefix];
              $dbMgr->query("select * from $table where $recPrefix"."_id = $recID limit 1");
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
          $ids = join(',', array_keys($fKeys));
          //query for records
          $dbMgr->query("select * from $table where $aggPrefix"."_id in ($ids);");
          while($row = $dbMgr->fetchResultRow(null,null,PGSQL_NUM)){
            array_push($records,$row);
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
          if (@$isOrdered) {
            if ($noRanges) {
              array_push($row,$idsOrderLookup[$row[$pkIndex]]);
            } else {
              array_push($row,$order++);
            }
          }
          if (@$showGID) {// change ID into global ID
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
