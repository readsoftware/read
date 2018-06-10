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
* orderSegment
*
* saves or clear segment ordinals
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
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Segments.php');
require_once (dirname(__FILE__) . '/../model/entities/Baseline.php');
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
  $baseline = null;
  $blnID = null;
  if ( isset($data['blnID'])) {//get ednID for sequence attach or detach
    $blnID = $data['blnID'];
    $baseline = new Baseline($blnID);
    if ($baseline->hasError()) {
      array_push($errors,"error loading baseline id $seqID - ".join(",",$edition->getErrors()));
    }
  }
  $cmd = null;
  if ( isset($data['cmd'])) {//get command
    $cmd = $data['cmd'];
  }
  $segID = null;
  if ( isset($data['segID'])) {//get segment ID
    $segID = $data['segID'];
  }
  $ord = null;
  $ordIsSet = false;
  if ( isset($data['ord'])) {//get ordinal
    $ord = $data['ord'];
    $ordIsSet = true;
  }
  if (count($errors) == 0 && ($segID && $ordIsSet || $blnID)) {
    if ($cmd == "clearOrdinals") {
      if (!$baseline) {
        array_push($errors,"Insufficient parameters to clear Segment Ordinals");
      } else {
        foreach ($baseline->getSegments() as $segment) {
          $ord = $segment->getScratchProperty("blnOrdinal");
          if ($ord) {
            $segment->storeScratchProperty("blnOrdinal",null);
            $segment->save();
            if ($segment->hasError()) {
              array_push($errors,"error resetting ordinal for segment '".$segment->getGlobalID()."' - ".$segment->getErrors(true));
            } else {
              addRemovePropertyReturnData("seg",$segment->getID(),'ordinal');
            }
          }
        }
      }
    } else if ($cmd == "setOrdinal") {
      if (!($segID && $ordIsSet)) {
        array_push($errors,"Insufficient parameters to set Segment Ordinals");
      } else {
        $segment = new Segment($segID);
        if ($segment->hasError()) {
          array_push($errors,"error loading edition id $seqID - ".join(",",$segment->getErrors()));
        } else {
          $segment->storeScratchProperty("blnOrdinal",($ordIsSet?$ord:null));
          $segment->save();
          if ($segment->hasError()) {
            array_push($errors,"error setting ordinal for segment '".$segment->getGlobalID()."' - ".$segment->getErrors(true));
          } else if ($ord){
            addUpdateEntityReturnData("seg",$segID,'ordinal', $segment->getScratchProperty("blnOrdinal"));
          } else {
            addRemovePropertyReturnData("seg",$segment->getID(),'ordinal');
          }
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}

?>
