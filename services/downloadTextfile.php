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
* downloadTextfile
*
* downloads a static text given it's URL.
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

require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies


$textURL = (array_key_exists('url',$_REQUEST)? $_REQUEST['url']:null);
startLog();
if( $textURL && strpos($textURL,"http") ===0) {
  if (strpos($textURL,SITE_ROOT) === 0) {
    $filepathname = str_replace(SITE_ROOT,DOCUMENT_ROOT,$textURL);
  } else {
    logAddMsgExit("service requires a valid url for a file located on this server.");
  }
} else {
  logAddMsgExit("service requires a valid url for a file located on this server.");
}

$textFileInfo = new SplFileInfo($filepathname);
$filename = $textFileInfo->getFilename();
if (!$filename || !$textFileInfo->isFile() || !$textFileInfo->isReadable()) {
  logAddMsgExit("Unable to read file '".$textFileInfo->getFilename()."' aborting download.");
} else {
  $ch = curl_init($textURL);
  curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //return the output as a string from curl_exec
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  curl_setopt($ch, CURLOPT_NOBODY, 0);
  curl_setopt($ch, CURLOPT_HEADER, 0);  //don't include header in output
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  // follow server header redirects
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // don't verify peer cert
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // timeout after ten seconds
  curl_setopt($ch, CURLOPT_MAXREDIRS, 5);  // no more than 5 redirections

  $content = curl_exec($ch);
  //error_log(" data = ". $data);

  $error = curl_error($ch);
  if ($error) {
    $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    error_log("get file content error: $error ($code) url = $url");
    curl_close($ch);
    logAddMsgExit("get file $filename content error: $error ($code) url = $url");
  } else {
    $size = 0;
    $info = curl_getinfo($ch);
    if($content){
      $size = $info['download_content_length'];
      $content_type = $info['content_type'];
      $content_type = explode(";",$content_type);
      $content_type = $content_type[0];
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
    echo $content;
  }
}

?>
