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
* saveSyllable
*
* saves the syllable passed making new or updating existing graphemes as needed, propagates up the containment hierarchy as needed.
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

header("Content-type: application/json");
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
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
require_once (dirname(__FILE__) . '/../model/entities/JsonCache.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();

if (mb_strtolower(getUserName(), 'UTF-8') == "guest") {
  echo "Error: Insufficient permissions for image upload.";
  exit;
}
if (!defined("DBNAME")) {
  echo "Error: must specify a dbname to associate the image with";
  exit;
}
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($warnings,"no data supplied, file(s) will be upload to '".DBNAME."' image root");
} else {
  // check upload file type image files .gif .jpeg and .png
  if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    echo "Error: Upload using ".$_SERVER['REQUEST_METHOD']." request is not supported at this time. Please use 'POST'. Nothing uploaded.";
    exit;
  }
  if (!array_key_exists('file',$_FILES)) {
    echo "Error: No image files found for upload. Nothing uploaded.";
    exit;
  }
  if (is_array($_FILES['file']['name'])) {
    if (count($_FILES['file']['name']) > 1) {
      array_push($warnings,"There are ".count($_FILES['file']['name'])." files in request for upload, current limit is 1, some files not uploaded.");
    }
    $fileInfo = array();
    foreach($_FILES['file'] as $key => $values) {
      $fileInfo[$key] = $values[0];
    }
  } else {
    $fileInfo = $_FILES['file'];
  }
  if(!is_uploaded_file($fileInfo['tmp_name']) ) {
    echo "Error: File info seems to be fake, aborting upload. Nothing uploaded.";
    exit;
  }
  $thumbExt = array('jpeg', 'jpg', 'png', 'gif'); // extensions for making thumbs
  $thresholdSize = 30000 * 1024; // max file size in bytes
  // get uploaded file extension
  $filename = basename($fileInfo['name']);
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  // looking for format and size validity
  if (!in_array($ext, $thumbExt) ) {
    echo "Error: Unsupported file format! Nothing uploaded.";
    exit;
  }
  if ($fileInfo['size'] > $thresholdSize){
    array_push($warnings,$filename." File size exceeds the efficient threshold.");
  }

 //check data entTag dbname
  $path = (array_key_exists('subpath',$_REQUEST)? $_REQUEST['subpath']:null);
//  $thumbDir = (array_key_exists('thumbDir',$_REQUEST)? $_REQUEST['thumbDir']:THUMBNAIL_SUB_PATH);
  $entTag = (array_key_exists('entTag',$_REQUEST)? $_REQUEST['entTag']:null);
  if (!$path && !$entTag) {
    array_push($warnings,"Neither path or entTag data supplied, file(s) will be upload to '".DBNAME."' image root");
  } else if ($path) {
    //todo add code to clean $path
    $path = IMAGE_ROOT."/".DBNAME.$path;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME.$path;
  } else {
    // add code to check entity access
    $path = IMAGE_ROOT."/".DBNAME."/".$entTag;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME."/".$entTag;
  }
  //check path exist if not try to create it
  $info = new SplFileInfo($path);
  if (!$info->isDir()) {
    $isDir = mkdir($info, 0775, true);
    if (!$isDir) {//point at the temp dir which will only can temporarily
      echo "Error: unable to open destination. Nothing uploaded.";
      exit;
    }
  } else if (!$info->isWritable()) {
    echo "Error: not able to save to $path. Nothing uploaded.";
    exit;
  }
  if (!preg_match("/\/$/",$path)) {
    $path .= "/";
    $url .= "/";
  }

  // move uploaded file from temp to uploads directory
  if (move_uploaded_file($fileInfo['tmp_name'], $path.$filename)) {
    $retVal['imageUrl'] = $url.$filename;
    //try to create thumbnail
    $urlThumb = createThumb($path, $filename, $ext, $path, $url);
    if ($urlThumb) {
      $retVal['thumbUrl'] = $urlThumb;
    }
  }
}

$retVal['status'] = "Failed to Upload $filename";
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["status"] = "$filename uploaded successfully!";
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
}
$jsonRetVal = json_encode($retVal);
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".$jsonRetVal.");";
  }
} else {
  print $jsonRetVal;
}

function createThumb($srcPath, $srcFilename, $ext, $targetPath, $thumbBaseURL, $maxSizeX = 150, $maxSizeY = 150) {
  $sourcefile = $srcPath.$srcFilename;
  $thumbfile = $targetPath.getThumbFromFilename($srcFilename);
  list($imageW,$imageH) = getimagesize($sourcefile);
  //shrink and preserve aspect
  $percent = $maxSizeX/$imageW;
  if ($percent>1) {
    $percent = 1;
  }
  if ($percent*$imageH > $maxSizeY) {
    $percent = $maxSizeY/$imageH;
  }
  $thumbW = round($percent*$imageW);
  $thumbH = round($percent*$imageH);

  $thumbImage = imagecreatetruecolor($thumbW,$thumbH);

  switch($ext){
    case 'png':
      $sourceImage = imagecreatefrompng($sourcefile);
      break;
    case 'gif':
      $sourceImage = imagecreatefromgif($sourcefile);
      break;
    case 'jpg':
    case 'jpeg':
    default:
      $sourceImage = imagecreatefromjpeg($sourcefile);
  }

  if ($sourceImage) {
    imagecopyresampled($thumbImage,$sourceImage,0,0,0,0,$thumbW,$thumbH,$imageW,$imageH);
    switch($ext){
      case 'png':
        $ret = imagepng($thumbImage,$thumbfile,9);
        break;
      case 'gif':
        $ret = imagegif($thumbImage,$thumbfile,100);
        break;
      case 'jpg':
      case 'jpeg':
      default:
        $ret = imagejpeg($thumbImage,$thumbfile,100);
    }
    imagedestroy($thumbImage);
    imagedestroy($sourceImage);
    if ($ret) {
      return $thumbBaseURL.getThumbFromFilename($srcFilename);
    }
  }
  return false;
}

?>