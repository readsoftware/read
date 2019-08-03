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
* createImageThumbnails
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
  array_push($warnings,"no data supplied, unable to create thumbnails");
} else {
  $thumbExt = array('jpeg', 'jpg', 'png', 'gif'); // extensions for making thumbs
  $thresholdSize = 30000 * 1024; // max file size in bytes

 //check data entTag dbname
  $path = (array_key_exists('path',$data)? $data['path']:null);
  $subpath = (array_key_exists('subpath',$data)? $data['subpath']:null);
  $entTag = (array_key_exists('entTag',$data)? $data['entTag']:null);
  if (!$path && !$subpath && !$entTag) {
		array_push($warnings,"Neither path or entTag data supplied, file(s) will be upload to '".DBNAME."' image root");
    $path = IMAGE_ROOT."/".DBNAME;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME;
  } else if (!$path && $subpath) {
		if (!preg_match("/^\//",$subpath)) {
			$subpath = "/".$subpath;
		}
    $path = IMAGE_ROOT."/".DBNAME.$subpath;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME.$subpath;
  } else if (!$path && $entTag){
    //todo add code to check entity access
    $path = IMAGE_ROOT."/".DBNAME."/".$entTag;
    $url = IMAGE_SITE_BASE_URL."/".DBNAME."/".$entTag;
  }
  //check path exist if not try to create it
  $info = new SplFileInfo($path);
  if (!$info->isDir()) {
		echo "Error: unable to open destination $path. Nothing created.";
		error_log("Error: unable to open destination $path. Nothing created.");
		exit;
  } else if (!$info->isWritable()) {
    echo "Error: not able to save to $path. Nothing created.";
    exit;
	}
	$iterator = new DirectoryIterator($path);

  if (!preg_match("/\/$/",$path)) {
    $path .= "/";
    $url .= "/";
	}
	$retVal['thumbUrls'] = array();
	foreach ($iterator as $fileinfo) {
		if ($fileinfo->isFile()) {
			$filename = $fileinfo->getFilename();
			$ext = $fileinfo->getExtension();
			if (in_array(strtolower($ext), $thumbExt)) {
				//try to create thumbnail
				$urlThumb = createThumb($path, $filename, $ext, $path, $url);
				if ($urlThumb) {
					array_push($retVal['thumbUrls'], $urlThumb);
				}
			}
		}
  }
}

$retVal['status'] = "Failed to create thumbnails";
if (count($errors)) {
  $retVal["errors"] = $errors;
} else if (count($retVal['thumbUrls'])) {
  $retVal["status"] = "thumbnails created successfully!";
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