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
* linkOrderedSegments
*
* take image segments that have been ordered and links them, based on common ordinal value, to a set of defined syllables for a given edition.
* requires target syllables be owned and stops when segments or syllables run out.
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
$log = "";
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  //process parameters
  //editon
  $edition = null;
  if ( isset($data['ednID'])) {//get edition
    $edition = new Edition($data['ednID']);
    if ($edition->hasError()) {
      array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
    } else if ($edition->isReadonly()) {
      array_push($errors,"edition readonly - insufficient priviledges to link syllbles from this edition");
    }
  } else {
    array_push($errors,"unknown edition");
  }

  $endSclGID = $startSclGID = $sclIDs = null;
  if (count($errors) == 0) {
    //start syllable (- end syllable) - sclIDs if 1 then start if 2 then start stop if more set of selected syllables
    if (isset($data['sclIDs'])) {//get  SyllableIDs
      $sclIDs = $data['sclIDs'];
      if (!is_array($sclIDs)) {
        if (strpos($sclIDs,",")) {//multiple
          $sclIDs = explode(",",$sclIDs);
        } else {
          $sclIDs = array($sclIDs);
        }
      }
      switch(count($sclIDs)) {
        case 2:
          $endSclGID = "scl:".$sclIDs[1];
        case 1:
          $startSclGID = "scl:".$sclIDs[0];
          break;
        case 0:
          $sclIDs = null;
          break;
      }
    }

    /**selection patterns:
     *     * is reserved to mean 'all'
     * L*:S1 = first syllable of every physical line of selected edition
     * LN = LN:S* = all syllables in the Nth physical line of selected edition
     * LN,LM = all syllables in the Nth and Mth physical line of selected edition
     * LN+5 = all syllables in the Nth and every 5th line after LN physical lines of selected edition
     * LN-M = all syllables in the Nth through Mth physical lines of selected edition
     * LN:SN = the Nth syllable in the Nth physical line of selected edition
     * LN:SN-M = the Nth through the Mth syllable in the Nth physical line of selected edition
     * L1:S1,L5+5:S1 will select the first syllable of the 1st, 5th, 10th, 15th ... physical line of selected edition
     */
    $patterns = null;
    if (isset($data['pattern'])) {//get selection pattern
      $patterns = explode(",",$data['pattern']);
      if (count($patterns) == 1 && $patterns[0] == ""){//catch pattern empty string
        $patterns = null;
      }
    }

    if (!$sclIDs || $startSclGID || $patterns) {
      // Create linkSclIDs array
      //find edition's Physical and Text sequences
      $linesOfSclGIDs = array();
      $orderedSclGIDs = array();
      $sclGIDs = array();
      $seqPhys = null;
      $edSeqs = $edition->getSequences(true);
      foreach ($edSeqs as $edSequence) {
        $seqType = $edSequence->getType();
        if (!$seqPhys && $seqType == "TextPhysical"){
          $seqPhys = $edSequence;
          foreach ($seqPhys->getEntities(true) as $lineSequence) {
            $lineSclGIDs = $lineSequence->getEntityIDs();
            if ($lineSclGIDs && is_array($lineSclGIDs)) {
              array_push($linesOfSclGIDs,$lineSclGIDs);
              $orderedSclGIDs = array_merge($orderedSclGIDs,$lineSclGIDs);
            }
          }
        }
      }
      if ($startSclGID) {//range specified so trim $orderedSclGIDsusing the start and end gids
        $sclGIDs = $orderedSclGIDs;
        $startIndex = array_search($startSclGID,$sclGIDs);
        if ($startIndex) {
          array_splice($sclGIDs,0,$startIndex);
        }
        if ($endSclGID) {
          $endIndex = array_search($endSclGID,$sclGIDs);
          if ($endIndex) {
             array_splice($sclGIDs,$endIndex+1);
          }
        }
      } else if ($patterns) {// use patterns to compose an ordered list of syllables
        // Decode pattern  --   L1:S1,L5+5:S1
        foreach ($patterns as $pattern) {
          $sclGIDs = array_merge($sclGIDs,findSclGIDsFromPattern($pattern,$linesOfSclGIDs));
        }
        // Create linkSclIDs array
      } else if (count($orderedSclGIDs) > 0) {
        $sclGIDs = $orderedSclGIDs;
      }
      $sclIDs = preg_replace("/scl\:/","",$sclGIDs);
    }
  }

  //baseline(s in order)
  $blnIDs = null;
  if (isset($data['blnIDs'])) {//get selection pattern
    $blnIDs = $data['blnIDs'];
    if (!is_array($blnIDs)) {
      if (strpos($blnIDs,",")) {//multiple
        $blnIDs = explode(",",$blnIDs);
      } else {
        $blnIDs = array($blnIDs);
      }
    }
  }

  if (count($blnIDs) == 0) {
    array_push($errors,"insufficient infomation - must specify at least one baseline");
  }

  if (count($errors) == 0) {//check if user specified a start and stop segmentID
    //start baseline-segment order id - (end baseline-segment order id)- segIDs if 1 then start if 2 then start stop if more set of selected segments
    $endSegOrd = $startSegOrd = $segIDs = null;
    if (isset($data['segIDs']) && $data['segIDs'] != "") {//get  SyllableIDs
      $segIDs = $data['segIDs'];
      if (!is_array($segIDs)) {
        if (strpos($segIDs,",")) {//multiple
          $segIDs = explode(",",$segIDs);
        } else {
          $segIDs = array($segIDs);
        }
      }
      switch(count($segIDs)) {
        case 2:
          $segment = new Segment($segIDs[1]);
          if ($segment && !$segment->hasError()) {
            $endSegOrd = $segment->getScratchProperty("blnOrdinal");
          }
        case 1:
          $segment = new Segment($segIDs[0]);
          if ($segment && !$segment->hasError()) {
            $startSegOrd = $segment->getScratchProperty("blnOrdinal");
          }
          break;
        case 0:
          $segIDs = null;
          break;
      }
    }

    //currently only deal with one baseline at a time.
    //create query for ordered set of segments
    // get an ordered list of segment IDs for the base lines supplied or for the entire database.
    $query = "select seg_id, seg_baseline_ids[1] as blnID, substring(seg_scratch from '".'"blnOrdinal":"(\d+)"'."')::int as ord".
             " from segment".
             " where substring(seg_scratch from '".'"blnOrdinal":"(\d+)"'."')::int is not null and seg_image_pos is not null".
             " and seg_baseline_ids[1] in (".join(',',$blnIDs).") ";
    if ($startSegOrd || $endSegOrd) {
      if ($startSegOrd && is_numeric($startSegOrd)) {
        $startSegOrd = intval($startSegOrd);
        $query .= " and substring(seg_scratch from '".'"blnOrdinal":"(\d+)"'."')::int >= $startSegOrd";
      }
      if ($endSegOrd && is_numeric($endSegOrd)) {
        $endSegOrd = intval($endSegOrd);
        $query .= " and substring(seg_scratch from '".'"blnOrdinal":"(\d+)"'."')::int <= $endSegOrd";
      }
    } else if (count($segIDs)) {
      $query .= " and seg_id in (".join(",",$segIDs).")";
    }
    $query .= " and not seg_owner_id = 1 order by blnID,ord";
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
  }
  $segCnt = count($ordSegIDs);
  $log .= "segCnt = '$segCnt'\n";
  // verify syllable ownership
  $orderedSyllables = array();

  //order syllables
  foreach ($sclIDs as $sclID) {
    $syllable = new SyllableCluster($sclID);
    if ($syllable->isReadonly()) {
      array_push($errors,"link to readonly syllables is not allowed");
      break;
    }
    $graphemes = $syllable->getGraphemes(true);
    $cntGra = $graphemes->getCount();
    if (!$graphemes || $cntGra == 0){
      array_push($errors,"attempt to link to syllable $sclID and unable to access graphemes, aborting");
      break;
    }
    $cntTcmA = 0;
    //check for scribal addition of entire syllable
    foreach ($graphemes as $grapheme){
      if ($grapheme->getTextCriticalMark() == "A") {
        $cntTcmA++;
      }
    }
    if ($cntTcmA && $cntTcmA == $cntGra) {// scribal addition of aksara
      array_push($warnings,"syllable $sclID is scribal insertion, skipping");
      continue;
    }
    array_push($orderedSyllables,$syllable);
    if (--$segCnt == 0) {//only check and accumulate those to be matched with a segment
      break;
    }
  }
  if (count($errors) == 0 && count($orderedSyllables)> 0) {
    $cachePhysLineSeqIDs = array();
    while (count($orderedSyllables) && count($ordSegIDs)) {
      $segID = array_shift($ordSegIDs);
      $segment = new Segment($segID);
      if ($segment && !$segment->hasError()) {
        $syllable = array_shift($orderedSyllables);
        $sclID = $syllable->getID();
        $segSclIDs = $segment->getSyllableIDs();
        if (count($segSclIDs) > 0) {
          foreach ($segSclIDs as $segSclID) {
            if ($segSclID != $sclID && in_array("scl:$segSclID",$orderedSclGIDs)) {//found a syllable already link to this segment so unlink it if possible
              $otherLinkedSyllable = new SyllableCluster($segSclID);
              if ($otherLinkedSyllable && !$otherLinkedSyllable->hasError() && !$otherLinkedSyllable->isReadonly()) {
                $otherLinkedSyllable->setSegmentID(null);
                $otherLinkedSyllable->save();
              }
            }
          }
        }
        if($syllable) {
          $syllable->setSegmentID($segID);
          $syllable->save();
          if ($syllable->hasError()) {
            array_push($errors,"Error saving syllable id = ".$syllable->getID()." errors - ".join(",",$syllable->getErrors()));
            break;
          } else {
            addUpdateEntityReturnData("seg",$segment->getID(),"sclIDs",$segment->getSyllableIDs(true));
            addUpdateEntityReturnData("scl",$syllable->getID(),"segID",$syllable->getSegmentID());
            //if using cache then invalidate cache for syllable's physical line seq and for edition
            if (defined('USECACHE') && USECACHE) {
              $sclPLSeqs = $syllable->getPhysLineSequences();
              if ($sclPLSeqs) {
                foreach ($sclPLSeqs as $sequence) {
                  array_push($cachePhysLineSeqIDs,$sequence->getID());
                }
              }
            }
          }
        }
      }
    }
    if (count($cachePhysLineSeqIDs)>0) {
      $cachePhysLineSeqIDs = array_unique($cachePhysLineSeqIDs);
      $cacheEdnIDs = array();
      foreach ($cachePhysLineSeqIDs as $physLineSeqID){
        $cacheSelector = "seq$physLineSeqID"."edn";
        $query = "select array_agg(replace(jsc_label,'$cacheSelector','')) as ednIDs from jsonCache where jsc_label like '$cacheSelector%'";
        $dbMgr->query($query);
        if ($dbMgr->getRowCount() > 0) {
          $row = $dbMgr->fetchResultRow();
          $ednIDs = explode(',',trim($row[0],"\"{}"));
          if ($ednIDs[0] != "" ) {
            $cacheEdnIDs = array_merge($cacheEdnIDs,$ednIDs);
          } 
        }
        invalidateCachedSeqEntities($physLineSeqID);
      }
      if (count($cacheEdnIDs)>0) {
        $cacheEdnIDs = array_unique($cacheEdnIDs);
        foreach ($cacheEdnIDs as $ednID) {
          invalidateCachedEditionEntities($ednID);
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
