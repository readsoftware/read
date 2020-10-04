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
* saveSegment
*
* saves Segment entity data
*
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   Stephen White  <stephenawhite57@gmail.com>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Services
*/
define('ISSERVICE',1);
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');

header("Content-type: segment/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Segment.php');
require_once (dirname(__FILE__) . '/../model/entities/Baseline.php');
require_once (dirname(__FILE__) . '/../model/entities/Image.php');
require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $segment = null;
  $segID = null;
  $segs = null;
  $segGID = null;
  if ( isset($data['segs'])) {//get existing segment
    $segs = $data['segs'];
  }
  if (!$segs || count($segs) == 0) {
    array_push($warnings,"warning updateSegments called with no segments");
  } else {
    foreach ($segs as $segID => $segProps) {
      if (!$segProps || count($segProps) == 0) {
        array_push($warnings,"warning updateSegments for segID $segID called with no properties");
        continue;
      }
      $segment = new Segment($segID);
      if ($segment->hasError()) {
        array_push($warnings,"error loading segment id $segID - ".join(",",$segment->getErrors()));
      } else if ($segment->isReadonly()) {
        array_push($warnings,"error segment id $segID - is readonly");
      } else {
        $segGID = $segment->getGlobalID();
        if ( isset($segProps['baselineIDs'])) {//set baseline for segment
          $segment->setBaselineIDs($segProps['baselineIDs']);
          addUpdateEntityReturnData("seg",$segID,'baselineIDs', $segment->getBaselineIDs());
        }
        if ( isset($segProps['boundary'])) {//set boundary for segment
          $segment->setImageBoundary($segProps['boundary']);
          addUpdateEntityReturnData("seg",$segID,'boundary', $segment->getImageBoundary());
        }
        if ( isset($segProps['layer'])) {//set layer for segment
          $segment->setLayer($segProps['layer']);
          addUpdateEntityReturnData("seg",$segID,'layer', $segment->getLayer());
        }
        if ( isset($segProps['urls'])) {//set url for segment
          $segment->setURL($segProps['urls']);
          addUpdateEntityReturnData("seg",$segID,'urls', $segment->getURLs());
        }
        if ( isset($segProps['ordinal'])) {//set order for segment
          $segment->storeScratchProperty("blnOrdinal",$segProps['ordinal']);
          addUpdateEntityReturnData("seg",$segID,'ordinal', $segment->getScratchProperty("blnOrdinal"));
        }
        if ( isset($segProps['code'])) {//set code for segment
          $segment->storeScratchProperty("code",$segProps['code']);
          addUpdateEntityReturnData("seg",$segID,'code', $segment->getScratchProperty("code"));
        }
        if ( isset($segProps['loc'])) {//set location for segment
          $segment->storeScratchProperty("sgnLoc",$segProps['loc']);
          addUpdateEntityReturnData("seg",$segID,'loc', $segment->getScratchProperty("sgnLoc"));
        }
        if ($segment->isDirty()) {
          $segment->save();
          if ($segment->hasError()) {
            array_push($warning,"error updating segment $segID during save - ".$segment->getErrors(true));
          }
        } else {
          array_push($warnings,"warning updateSegments for segID $segID no properties updated");
        }
      }
    }
  }
}

$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
//  invalidateCache('AllTextResources'.getUserDefEditorID());
//  invalidateCache('SearchAllResults'.getUserDefEditorID());
//  invalidateCache('AllTextResources');
//  invalidateCache('SearchAllResults');
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
}
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
