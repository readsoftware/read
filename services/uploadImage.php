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
* uploadImage
*
* uploads an image into configured location and creates thumbnail for image.
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

// SECURITY: Require authentication for image upload operations
if (!isLoggedIn()) {
  echo "Error: Authentication required for image upload operations.";
  error_log("uploadImage.php: Unauthorized access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
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
  
  // Check for PHP upload errors first
  if (isset($fileInfo['error']) && $fileInfo['error'] !== UPLOAD_ERR_OK) {
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    
    switch ($fileInfo['error']) {
      case UPLOAD_ERR_INI_SIZE:
        echo "Error: File exceeds the maximum size allowed by server configuration. Maximum allowed: {$uploadMaxFilesize}";
        break;
      case UPLOAD_ERR_FORM_TOO_LARGE:
        echo "Error: File exceeds the maximum upload size. Maximum allowed: {$uploadMaxFilesize}";
        break;
      case UPLOAD_ERR_PARTIAL:
        echo "Error: File was only partially uploaded. Please try again.";
        break;
      case UPLOAD_ERR_NO_FILE:
        echo "Error: No file was uploaded.";
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        echo "Error: Missing temporary upload directory on server.";
        break;
      case UPLOAD_ERR_CANT_WRITE:
        echo "Error: Failed to write file to disk.";
        break;
      case UPLOAD_ERR_EXTENSION:
        echo "Error: Upload stopped by PHP extension.";
        break;
      default:
        echo "Error: Unknown upload error occurred (code: {$fileInfo['error']}).";
        break;
    }
    error_log("uploadImage.php: Upload error " . $fileInfo['error'] . " for file: " . $fileInfo['name'] . " (size: " . (isset($fileInfo['size']) ? $fileInfo['size'] : 'unknown') . " bytes)");
    exit;
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
  
  // SECURITY: Sanitize input parameters to prevent path traversal attacks
  if ($path) {
    // Remove any directory traversal sequences and normalize path
    $path = str_replace(array('..', '\\'), '', $path);
    $path = trim($path, '/');
    // Only allow alphanumeric characters, hyphens, underscores, and forward slashes
    if (!preg_match('/^[a-zA-Z0-9\/_-]*$/', $path)) {
      echo "Error: Invalid characters in path parameter.";
      error_log("uploadImage.php: Invalid path parameter rejected: " . $_REQUEST['subpath']);
      exit;
    }
  }
  
  if ($entTag) {
    // Sanitize entTag to prevent path traversal
    $entTag = str_replace(array('..', '\\', '/'), '', $entTag);
    // Only allow alphanumeric characters, hyphens, and underscores
    if (!preg_match('/^[a-zA-Z0-9_-]*$/', $entTag)) {
      echo "Error: Invalid characters in entTag parameter.";
      error_log("uploadImage.php: Invalid entTag parameter rejected: " . $_REQUEST['entTag']);
      exit;
    }
  }
  
  if (!$path && !$entTag) {
    array_push($warnings,"Neither path or entTag data supplied, file(s) will be upload to '".DBNAME."' image root");
  } else if ($path) {
    $path = IMAGE_ROOT."/".DBNAME."/".$path;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME."/".$path;
  } else {
    $path = IMAGE_ROOT."/".DBNAME."/".$entTag;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME."/".$entTag;
  }
  
  // SECURITY: Verify the final path is within the allowed directory
  $allowedBasePath = realpath(IMAGE_ROOT."/".DBNAME);
  $resolvedPath = realpath(dirname($path));
  
  // If realpath returns false, the path doesn't exist yet, so check if parent path is safe
  if ($resolvedPath === false) {
    // Check if we can create this path safely by checking each parent directory
    $checkPath = $path;
    while ($resolvedPath === false && dirname($checkPath) !== $checkPath) {
      $checkPath = dirname($checkPath);
      $resolvedPath = realpath($checkPath);
    }
  }
  
  if ($resolvedPath === false || strpos($resolvedPath, $allowedBasePath) !== 0) {
    echo "Error: Invalid upload path.";
    error_log("uploadImage.php: Path traversal attempt blocked. Attempted path: " . $path);
    exit;
  }
  //check path exist if not try to create it
  $info = new SplFileInfo($path);
  if (!$info->isDir()) {
    $isDir = mkdir($path, 0775, true);
    if (!$isDir) {//point at the temp dir which will only can temporarily
      echo "Error: unable to open destination. Nothing uploaded.";
      error_log("Error: unable to open destination $path. Nothing uploaded.");
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

?>