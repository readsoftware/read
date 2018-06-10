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
  * deleteEntity
  *
    * marks entities for delete
  *
  * {
  * "tableprefix or tablename": array of record IDs to mark for delete,
  * }
  *
  * {"tok":[594,595,596,597,598],
  *  "cmp":[595,596,599,600,601]
  * }
  *
  * return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
  *
  * { "total" : #entityRecords marked for delete,
  *   "success": true or false,   //if errors encountered false is returned
  *   "errors": array of error messages if any, blank otherwise
  *   "failed" : array of GIDs for entities unable to delete
  * }
  *
  * {"total" : 8,
  *  "success": false,
  *  "errors": ["cmp 600 access denied","cmp 601 access denied"],
  *  "failed" : ["cmp:600","cmp:601"]
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
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//get Entity Factory
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
//  $userID = 12;

  $retVal = array();
  $errors = array();
  $deletedTags = array();
  $failedEntGIDs = array();
  $cntEntitiesMarked = 0;
  if (array_key_exists('data',$_REQUEST)) {
    if (is_string($_REQUEST['data'])) {
      $data =  json_decode($_REQUEST['data'],true);
    } else if (is_array($_REQUEST['data'])) {
      $data = $_REQUEST['data'];
    }
  }
  if (!$data) {
    print '["error":"invalid json data - decode failed"]';
    return;
  }
  $healthLogging = false;
  if ( isset($_REQUEST['hlthLog'])) {//check for health logging
    $healthLogging = true;
    if ( isset($_REQUEST['ednID'])) {//get ednID for sequence attach or detach
      $edition = new Edition($_REQUEST['ednID']);
    }
  }
  foreach ($data as $prefixortable => $entityIDs) {
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
    //get each Entity and mark for delete if possible
    foreach($entityIDs as $entID) {
      $gid = $prefix.":".$entID;
      $tag = $prefix.$entID;
      //create Entity from fractory
      $entity = EntityFactory::createEntityFromPrefix($prefix,$entID);
      if (EntityFactory::$error) { //error creating entity
        array_push($failedEntGIDs,$gid);
        continue;
      } else {
        if ($entity->hasError() || $entity->getID() != $entID) {
          array_push($failedEntGIDs,$gid);
          continue;
        }
        $entity->markForDelete();
        if ($entity->hasError()) {
          array_push($errors,"deleting entity id $gid - ".join(",",$entity->getErrors()));
          array_push($failedEntGIDs,$gid);
        } else {
          array_push($deletedTags,$gid);
          addRemoveEntityReturnData($prefix,$entID);
        }
      }
    }//foreach record
  }//foreach entity
  $retVal = array("total" => count($deletedTags),
                  "success" => true);
  if ( count($errors) > 0) {
     $retVal['errors'] = $errors;
     $retVal['success'] = false;
  }
  if (count($failedEntGIDs) > 0){
    $retVal['failed'] = $failedEntGIDs;
  }
  if (count($deletedTags) > 0){
    $retVal['deleted'] = $deletedTags;
  }
  if (count($entities)) {
    $retVal["entities"] = $entities;
  }
  if ($healthLogging && $edition) {
    $retVal["editionHealth"] = checkEditionHealth($edition->getID(),false);
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
