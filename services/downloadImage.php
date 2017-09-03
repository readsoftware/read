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
* downloadImage
*
* downloads an image using the url of an image entiry or baseline entity.
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


require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Baseline.php');
require_once (dirname(__FILE__) . '/../model/entities/Image.php');

$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$url = null;
$urlsAllowed = ini_get('allow_url_fopen');

$imgID = (array_key_exists('imgID',$_REQUEST)? $_REQUEST['imgID']:null);
$blnID = (array_key_exists('blnID',$_REQUEST)? $_REQUEST['blnID']:null);

if (!$imgID && !$blnID) {
  array_push($errors,"Must indicate a valid image or baseline id.");
} else {
  if ($imgID) {
    $image = new Image($imgID);
    if (!$image || $image->hasError()) {//no image or unavailable so warn
      array_push($errors,"Error need valid image id $imgID. Errors: ".join(".",$image->getErrors()));
    } else {
      $url = $image->getURL();
    }
  } else if ($blnID) {
    $baseline = new Baseline($blnID);
    if (!$baseline || $baseline->hasError()) {//no baseline or unavailable so warn
      array_push($warnings,"Error need valid baseline id $blnID. Errors: ".join(".",$baseline->getErrors()));
    } else {
      $url = $baseline->getURL();
      $image = $baseline->getImage(true);
    }
  }
  if ($url) {
    if (strpos($url,"/") == 0) {
      $url = SITE_ROOT.$url;
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //return the output as a string from curl_exec
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);  //don't include header in output
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  // follow server header redirects
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // don't verify peer cert
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // timeout after ten seconds
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);  // no more than 5 redirections

    $data = curl_exec($ch);
    //error_log(" data = ". $data);

    $error = curl_error($ch);
    if ($error) {
      $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
      error_log("$error ($code) url = $url");
      curl_close($ch);
      die("$error ($code) url = $url");
    } else {
      $size = 0;
      $info = curl_getinfo($ch);
      $urlInfo = parse_url($url);
      $filename = substr($urlInfo['path'],strrpos($urlInfo['path'],'/')+1);
      $fileext = substr($filename,strrpos($filename,'.')+1);
      if($data){
        $size = $info['download_content_length'];
        $content_type = $info['content_type'];
        $content_type = explode(";",$content_type);
        $content_type = $content_type[0];
        $ext = explode("/",$content_type);
        $ext = $ext[1];
        $extPos = strrpos($filename, $ext);
        if ( $extPos === false || $extPos != (strlren($filename)-1-strlen($ext))) {
          $title = $image->getTitle();
          if ($title) {
            $filename = str_replace('[\^\[\]/?*:;{}\\]+]','_',$title).".$ext";
          } else {
            $filename = DBNAME."_imgID".$image->getID().".$ext";
          }
        }
      }
      curl_close($ch);
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: public");
      header("Content-Description: File Transfer");
      header("Content-type: $content_type");
      header("Content-Disposition: attachment; filename=\"".$filename."\"");
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: $size");
      ob_end_clean();
      ob_end_flush();
      echo $data;
    }
    exit;
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
