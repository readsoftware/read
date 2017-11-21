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
* saveEdition
*
* saves Edition entity data
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
require_once (dirname(__FILE__) . '/../model/entities/Sequence.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
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
  $edition = null;
  if ( isset($data['ednID'])) {//get ednID for sequence attach or detach
    $edition = new Edition($data['ednID']);
    if ($edition->hasError()) {
      array_push($errors,"error loading edition id $seqID - ".join(",",$edition->getErrors()));
    } else if ($edition->isReadonly()) {
      array_push($errors,"error edition '".$edition->getDescription()."' - is readonly");
    } else {
      //get default attribution
      if (!$defAttrIDs || count($defAttrIDs) == 0) {
        $attrIDs = $edition->getAttributionIDs();
        if ($attrIDs && count($attrIDs) > 0 ) {
          $defAttrIDs = array($attrIDs[0]);
        }
      }
      //get default visibility
      if (!$defVisIDs || count($defVisIDs) == 0) {
        $visIDs = $edition->getVisibilityIDs();
        if ($visIDs && count($visIDs) > 0 ) {
          $defVisIDs = array($visIDs[0]);
        }
      }
      if ( isset($data['description'])) {//get description
        $txtID = $edition->getTextID();
        $description = $data['description'];
        if (!$description) {
          if ($txtID) {
            $text = new Text($txtID);
            if ($text->hasError()) {
              array_push($errors,"error loading text id $txtID - ".join(",",$text->getErrors()));
            } else {
              $description = "Edition for ".($text->getRef()? $text->getRef():
                                              ($text->getTitle()?$text->getTitle():
                                                ($text->getCKN()?$text->getCKN():"unlabelled text")));
            }
          } else {
            $description = "Edition for unlabelled text";
          }
        }
        $edition->setDescription($description);
        $edition->save();
        if ($edition->hasError()) {
          array_push($errors,"error updating edition '".$edition->getDescription()."' - ".$edition->getErrors(true));
        }else {
          addUpdateEntityReturnData("edn",$data['ednID'],'description', $edition->getDescription());
          addUpdateEntityReturnData("edn",$data['ednID'],'value', $edition->getDescription());
        }
      }
      if (isset($data['seqIDs'])) {
        $seqIDs = $data['seqIDs'];
        $edition->setSequenceIDs($seqIDs);
        $edition->save();
        if ($edition->hasError()) {
          array_push($errors,"error updating edition '".$edition->getDescription()."' - ".$edition->getErrors(true));
        }else {
          addUpdateEntityReturnData("edn",$data['ednID'],'seqIDs', $edition->getSequenceIDs());
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
  invalidateCache('AllTextResources'.getUserDefEditorID());
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
