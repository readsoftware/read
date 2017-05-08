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
* reorderSegments
*
* take image segments that have been ordered and injects the baseline and polygons into segments link with syllables.
* assumes transcription baselines are linked to all syllables and that ordered image segments count matches syllable count.
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
require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid aksara
require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/JsonCache.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$ednOwnerID = null;
$tokenSplitRequired = false;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $log = "Start reorder morphying process.\n";
  // get an ordered list of segment IDs for the base lines supplied or for the entire database.
  $query = "select seg_id, seg_baseline_ids[1] as blnID, substring(seg_scratch from '(?:\"blnOrdinal\":\")(\\d+)\"')::int as ord from segment where seg_scratch like '%blnOrdinal%' and seg_image_pos is not null";
  if (isset($data['blnIDs'])) {
    $query .= " and seg_baseline_ids[1] = ANY(".$data['blnIDs'].") order by blnID,ord";
  } else {
    $query .= " order by blnID,ord";
  }
  $log .= "query = '$query'\n";
  $dbMgr->query($query);
  $ordSegIDs = array();
  $ordCnt = $dbMgr->getRowCount();
  if ($ordCnt == 0) {
    array_push($errors,"no ordinals found in scratch of any segments");
  } else {
    while ($row = $dbMgr->fetchResultRow()) {
      array_push($ordSegIDs, $row['seg_id']);
    }
  }

  if (count($errors) == 0) {
    $segments = new Segments(null,'seg_id',null,null);
    if ($segments->getError()) {
      array_push($errors,"Error loading segments error: ".$segments->getError());
    } else if ($segments->getCount() < ($ordCnt * 2)) {
      array_push($errors,"Error segment count mismatch database segment count (".$segments->getCount().") should be at least twice the count ($ordCnt) of ordered segments");
    } else {
      foreach ($ordSegIDs as $segID) {
        $srcSegment = $segments->searchKey($segID);
        $trgSegment = $segments->current();
        if (!$srcSegment) {
          array_push($errors,"unable to find source segment for seg:$segID");
        } else if ($srcSegment->isReadonly()) {
//          array_push($errors,"source segment for seg:$segID is read only");
        }
        if (!$trgSegment) {
          array_push($errors,"no target segment for seg:$segID");
        } else if ($trgSegment->isReadonly()) {
//          array_push($errors,"target segment for seg:$segID is read only");
        }
        if (count($errors)) {
          break;
        }
        $trgSegID = $trgSegment->getID();
        $log .= "Attempting to merge data from source seg$segID into target seg$trgSegID.\n";
        $trgSegment->setBaselineIDs($srcSegment->getBaselineIDs());
        $trgSegment->setImageBoundary($srcSegment->getImageBoundary());
        if ($trgSegment->getStringPos()) {
          $trgSegment->setStringPos(null);
        }
        $trgSegment->save();
        if ($trgSegment->hasError()) {
          array_push($errors,"Error saving target segment id = ".$trgSegment->getID()." errors - ".join(",",$trgSegment->getErrors()));
          break;
        }
        $srcSegment->markForDelete();
        if ($srcSegment->hasError()) {
          array_push($errors,"Error deleting source segment id = ".$srcSegment->getID()." errors - ".join(",",$srcSegment->getErrors()));
          break;
        }
        $log .= "Successfully merged data from source seg$segID into target seg$trgSegID.\n";
        $segments->next();
      }
      if (count($errors) == 0) {//can remove ordinal segments
        $query = "delete from segment where seg_id in (".join(",",$ordSegIDs).")";
        $dbMgr->query($query);
      }
    }
  }
}

$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
}
if (count($entities)) {
  $retVal["entities"] = $entities;
}
$retVal["log"] = $log;
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
