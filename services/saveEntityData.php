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
  * saveEntityData
  *
    * saves entity data for passed entity data structure of the form
  *
  * {"tableprefix or tablename": array of records
  *                   where each record is an array of columnName:value pairs
  *                             where id column is required even new records of the form prefix:newID,
  * }
  *
  * {"tok":[{"tok_id":355,"tok_grapheme_ids":"{594,595,596,597,598}"},
  *         {"tok_id":"new14","tok_grapheme_ids":"{595,596,599,600,601}"}]
  * }
  *
  * return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
  *
  * { "entityName" : {
  *           "total" : #entityRecords,
  *           "success": true or false,   //if errors encountered false is returned
  *           "columns": array of columnNames for the records returned
  *           "records" : array of records where each record is an array of column values in "columns" order
  *    }
  * }
  *
  * { "token" : {
  *           "total" : 2,
  *           "success": true,
  *           "columns": ["tok_id","tok_value"," ...,"tok_scratch"],
  *           "records" : [[355,"putra",.....,"\"CKN\":\"CKI02661\""],
  *                        [1209,"putre",.....,"\"CKN\":\"CKI02661\""]]
  *    }
  * }
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
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utility functions
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//get Entity Factory
  require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');//get Entity Factory
//  $userID = 12;
  $prefixToTableName = array(
            "col" => "collection",
            "itm" => "item",
            "prt" => "part",
            "frg" => "fragment",
            "img" => "image",
            "spn" => "span",
            "srf" => "surface",
            "txt" => "text",
            "tmd" => "textmetadata",
            "mcx" => "materialcontext",
            "bln" => "baseline",
            "seg" => "segment",
            "run" => "run",
            "lin" => "line",
            "scl" => "syllablecluster",
            "gra" => "grapheme",
            "tok" => "token",
            "cmp" => "compound",
            "lem" => "lemma",
            "per" => "person",
            "trm" => "term",
            "prn" => "propernoun",
            "cat" => "catalog",
            "seq" => "sequence",
            "lnk" => "link",
            "edn" => "edition",
            "bib" => "bibliography",
            "ano" => "annotation",
            "atb" => "attribution",
            "atg" => "attributiongroup",
            "ugr" => "usergroup",
            "dat" => "date",
            "era" => "era");

  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):null);
  if (!$data) {
    print '["error":"invalid json data - decode failed"]';
    return;
  }
  foreach ($data as $prefixortable => $recordData) {
    // check for valid entity identifier
    if (array_key_exists($prefixortable, $prefixToTableName)){
      $table = $prefixToTableName[$prefixortable];
      $prefix = $prefixortable;
    }else if(in_array($prefixortable,array_values($prefixToTableName))) {
      $table = $prefixortable;
      $prefix = array_search($prefixortable,$prefixToTableName);
    }else{
      array_push($errors,"$prefixortable is not a known prefix or table name, data ignored");
      continue;
    }
    //get columnNames
    $columnNames = array();
    $records = array();
    $dbMgr->query("select column_name from INFORMATION_SCHEMA.COLUMNS where table_name = '$table' order by ordinal_position");
    while($row = $dbMgr->fetchResultRow()){
      array_push($columnNames,$row[0]);
    }
    $tempIDMap = array();
    //save each record
    foreach($recordData as $record){
      $isInsert = false;
      $recColDataPairs = array();
      $tempID = null;
      $entity = null;
      $condition = null;
      foreach ($record as $column => $value) {
        if (!in_array($column,$columnNames)) {
          if (!array_key_exists($table,$errors)) {
            $errors[$table]=array();
          }
          array_push($errors[$table],"$column is not a valid column in $table, data '$value' ignored");
          continue;
        }
        if ($column == $prefix.'_id') {
          if (strpos($value,'new') === 0) { //new record
            $isInsert = true;
            $tempID = $value;
            $condition = '';
          }else{
            $condition = ' '.$column.' = '.$value.' ';
            $recID = $value;
          }
        }else{
          $recColDataPairs[$column] = $value;
        }
      }//foreach column
      if ($isInsert) {
        if (!array_key_exists($prefix.'_owner_id',$recColDataPairs) && isLoggedIn()) {//check for owner if not use logged in user
          $recColDataPairs[$prefix.'_owner_id'] = getUserDefEditorID();
        }
        if (!array_key_exists($prefix.'_visibility_ids',$recColDataPairs) && isLoggedIn()) {//check for visibility if not use logged in user
          $recColDataPairs[$prefix.'_visibility_ids'] = getUserDefVisibilityIDs();
        }
        $recID = $dbMgr->insert($table,$recColDataPairs,$prefix.'_id');
        if (!$recID){
          if (!array_key_exists($table,$errors)) {
            $errors[$table]=array();
          }
          array_push($errors[$table],"error inserting tempID $tempID, dbManager returned ".$dbMgr->getError());
        }else{
          $tempIDMap[$tempID] = $recID;
          $entity = EntityFactory::createEntityFromPrefix($prefix,$recID);
        }
      }else{ //update record if exist and is accessable
        $entity = EntityFactory::createEntityFromPrefix($prefix,$recID);
        if ( isSysAdmin() || ($entity && ! $entity->hasError() && !$entity->isReadonly())) {
          if (!$dbMgr->update($table,$recColDataPairs,$condition)) {
            if (!array_key_exists($table,$errors)) {
              $errors[$table]=array();
            }
            array_push($errors[$table],"error updating $table recID $recID, dbManager returned ".$dbMgr->getError());
          } else {//check to see if entity is part of any seq and if so invalidate cache for that sequence.
            $entity = EntityFactory::createEntityFromPrefix($prefix,$recID);
            $gid = $entity->getGlobalID();
            $sequences = new Sequences("'$gid' = Any(seq_entity_ids)",'seq_id',null,null);
            if ($sequences && $sequences->getCount()>0) {
              foreach ($sequences as $sequence) {
                invalidateCachedSeq($sequence->getID());
              }
            }
          }
        } else if ($entity && $entity->hasError()) {
          if (!array_key_exists($table,$errors)) {
            $errors[$table]=array();
          }
          array_push($errors[$table],"error updating $table recID $recID, dbManager returned ".$dbMgr->getError());
        } else {
          if (!array_key_exists($table,$errors)) {
            $errors[$table]=array();
          }
          array_push($errors[$table],"error updating $table recID $recID, access denied!");
        }
      }
      $dbMgr->query("select * from $table where $prefix"."_id = $recID limit 1");
      while($row = $dbMgr->fetchResultRow(null,null,PGSQL_NUM)){
        array_push($records,$row);
      }
      addNewEntityReturnData($prefix,$entity);
    }//foreach record
    $retVal[$table] = array("total" => count($records),
                            "success" => true,
                            "columns" => $columnNames,
                            "records" => $records);
    if (array_key_exists($table,$errors) && count($errors[$table]) > 0) {
       $retVal[$table]['errors'] = $errors[$table];
       $retVal[$table]['success'] = false;
    }
    if (count($tempIDMap) > 0){
      $retVal[$table]['tempIDMap'] = $tempIDMap;
    }
  }//foreach entity
  if (count($entities)) {
    $retVal["entities"] = $entities;
  }
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }
  ?>
