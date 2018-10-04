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
  * loadAttributions
  *
  *  A service that returns a json structure of the attributions of this datastore.
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

  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once dirname(__FILE__) . '/../model/entities/Attributions.php';


  $dbMgr = new DBManager();
  $condition = "";
  $maxRows = null;
  $jsonRetVal = null;
  $attrs = array();
  // check for cache
  if ( array_key_exists('titleContains',$_REQUEST) && $_REQUEST['titleContains']) {
    $condition = "atb_title ilike '%".$_REQUEST['titleContains']."%'";
  }
  if ( array_key_exists('titleBeginsWith',$_REQUEST) && $_REQUEST['titleBeginsWith']) {
    $condition = "atb_title ilike '".$_REQUEST['titleBeginsWith']."%'";
  }
  if ( array_key_exists('maxRows',$_REQUEST) && $_REQUEST['maxRows']) {
    $maxRows = $_REQUEST['maxRows'];
  }

  $jsonCache = null;
  if ($condition == "" && USECACHE) {// check cache for all
    $dbMgr->query("SELECT * FROM jsoncache WHERE jsc_label = 'SearchAllAttributions'");
    if ($dbMgr->getRowCount() > 0) {
      $row = $dbMgr->fetchResultRow();
      $jsonCache = new JsonCache($row);
      if (!$jsonCache->hasError() && !$jsonCache->isDirty()) {
        $jsonRetVal = $jsonCache->getJsonString();
      }
    }
  }
//error_log(print_r($_REQUEST,true));
  //get all visible attributions
  if (!$jsonRetVal) {
    $attributions = new Attributions($condition,'atb_title',null,$maxRows);
    $attributions->setAutoAdvance(false);
    foreach ($attributions as $attribution){
      $atbID = $attribution->getID();
      array_push($attrs,array(
        'label' => $attribution->getTitle().($attribution->getDetail()?' ('.$attribution->getDetail().')':''),
        'value' => $atbID
      ));
    } //for attributions
    $jsonRetVal = "(".json_encode($attrs).")";

    if ($condition == "" && USECACHE) {
      if (!$jsonCache) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel('SearchAllAttributions');
        $jsonCache->setJsonString($jsonRetVal);
        $jsonCache->setVisibilityIDs(DEFAULTCACHEVISID?array(DEFAULTCACHEVISID):array(6));
        $jsonCache->setOwnerID(DEFAULTCACHEOWNERID?DEFAULTCACHEOWNERID:6);
      } else {
        $jsonCache->clearDirtyBit();
        $jsonCache->setJsonString($jsonRetVal);
      }
      $jsonCache->save();
    }
  }

  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    echo $cb.$jsonRetVal;
  } else {
    echo $jsonRetVal;
  }
?>
