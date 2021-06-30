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
* reclipSegmentImageToCache
*
* clips an image of each segment and stores in the system configured segment cache
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Services
*/
define('ISSERVICE',1);
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Segments.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
//  print json_encode($_REQUEST);
  $blnIDs = null;
  if (isset($data['force'])) {
    $forceReclip = true;
  } else {
    $forceReclip = false;
  }
  if (isset($data['blnIDs'])) {
    if (is_array($data['blnIDs'])) {
      $blnIDs = $data['blnIDs'];
    } else if (is_numeric($data['blnIDs'])) {
      $blnIDs = array($data['blnIDs']);
    } else if (strpos(',',$data['blnIDs'])!==false) {
      $blnIDs = explode(',',$data['blnIDs']);
    }
  } else if (isset($data['blnID']) && is_numeric($data['blnID'])) {
    $blnIDs = array($data['blnID']);
  } else {
    $blnIDs = null;
  }
  $retStruct = synchSegmentImageCache($blnIDs,$forceReclip);
}

if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retStruct).");";
  }
} else {
  print json_encode($retStruct);
}
?>
