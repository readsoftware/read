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

// SECURITY: Anti-bot and programmatic access prevention
function validateHumanAccess() {
    // 1. Referrer validation - must come from our own site
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (empty($referrer) || strpos($referrer, SITE_ROOT) !== 0) {
        error_log("downloadTextfile.php: Access denied - Invalid referrer: " . $referrer . " from IP: " . $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        die("Access denied: Invalid referrer");
    }
    
    // 2. User-Agent validation - block obvious bots and scripts
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $botPatterns = [
        '/bot/i', '/crawler/i', '/spider/i', '/scraper/i', '/curl/i', '/wget/i', 
        '/python/i', '/php/i', '/java/i', '/perl/i', '/ruby/i', '/go-http/i',
        '/postman/i', '/insomnia/i', '/httpie/i', '/apache-httpclient/i'
    ];
    
    foreach ($botPatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            error_log("downloadTextfile.php: Access denied - Bot detected: " . $userAgent . " from IP: " . $_SERVER['REMOTE_ADDR']);
            http_response_code(403);
            die("Access denied: Automated access not permitted");
        }
    }
    
    if (empty($userAgent) || strlen($userAgent) < 10) {
        error_log("downloadTextfile.php: Access denied - Invalid/missing User-Agent from IP: " . $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        die("Access denied: Invalid browser");
    }
    
    // 3. Rate limiting by IP
    $clientIP = $_SERVER['REMOTE_ADDR'];
    $rateLimitFile = sys_get_temp_dir() . '/download_rate_' . md5($clientIP);
    $maxRequests = 10; // Max 10 downloads per hour
    $timeWindow = 3600; // 1 hour
    
    if (file_exists($rateLimitFile)) {
        $requestData = json_decode(file_get_contents($rateLimitFile), true);
        if ($requestData && 
            $requestData['count'] >= $maxRequests && 
            (time() - $requestData['first_request']) < $timeWindow) {
            error_log("downloadTextfile.php: Rate limit exceeded for IP: " . $clientIP);
            http_response_code(429);
            die("Rate limit exceeded. Please try again later.");
        }
        
        // Reset if time window has passed
        if ((time() - $requestData['first_request']) >= $timeWindow) {
            unlink($rateLimitFile);
            $requestData = null;
        }
    }
    
    // Update rate limiting counter
    if (!isset($requestData)) {
        $requestData = ['count' => 1, 'first_request' => time()];
    } else {
        $requestData['count']++;
    }
    file_put_contents($rateLimitFile, json_encode($requestData));
    
    return true;
}

// SECURITY: Validate human access before proceeding
validateHumanAccess();


$textURL = (array_key_exists('url',$_REQUEST)? $_REQUEST['url']:null);
startLog();

// SECURITY: Enhanced URL validation to prevent SSRF
if (!$textURL) {
    logAddMsgExit("service requires a valid url parameter.");
}

// Only allow HTTP/HTTPS URLs
if (!preg_match('/^https?:\/\//', $textURL)) {
    logAddMsgExit("service requires a valid HTTP or HTTPS URL.");
}

// Parse and validate the URL
$parsedURL = parse_url($textURL);
if (!$parsedURL || !isset($parsedURL['host'])) {
    logAddMsgExit("service requires a valid URL format.");
}

// SECURITY: Only allow our own domain to prevent SSRF
$allowedHost = parse_url(SITE_ROOT, PHP_URL_HOST);
if ($parsedURL['host'] !== $allowedHost) {
    error_log("downloadTextfile.php: SSRF attempt blocked - Host: " . $parsedURL['host'] . " from IP: " . $_SERVER['REMOTE_ADDR']);
    logAddMsgExit("service only allows downloads from this server domain.");
}

// Convert URL to local file path
if (strpos($textURL, SITE_ROOT) === 0) {
    $filepathname = str_replace(SITE_ROOT, DOCUMENT_ROOT, $textURL);
} else {
    logAddMsgExit("service requires a URL from this server.");
}

// SECURITY: Validate file path to prevent directory traversal
$realpath = realpath(dirname($filepathname));
$allowedBasePath = realpath(DOCUMENT_ROOT);

if ($realpath === false || strpos($realpath, $allowedBasePath) !== 0) {
    error_log("downloadTextfile.php: Path traversal attempt blocked - Path: " . $filepathname . " from IP: " . $_SERVER['REMOTE_ADDR']);
    logAddMsgExit("Invalid file path detected.");
}

$textFileInfo = new SplFileInfo($filepathname);
$filename = $textFileInfo->getFilename();
if (!$filename || !$textFileInfo->isFile() || !$textFileInfo->isReadable()) {
  logAddMsgExit("Unable to read file '".$textFileInfo->getFilename()."' aborting download.");
} else {
  // SECURITY: Secure cURL configuration
  $ch = curl_init($textURL);
  curl_setopt($ch, CURLOPT_COOKIEFILE, '/dev/null');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //return the output as a string from curl_exec
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  curl_setopt($ch, CURLOPT_NOBODY, 0);
  curl_setopt($ch, CURLOPT_HEADER, 0);  //don't include header in output
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);  // SECURITY: Disable redirects to prevent SSRF
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);  // SECURITY: Enable SSL verification
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // SECURITY: Verify SSL hostname
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // timeout after 30 seconds
  curl_setopt($ch, CURLOPT_MAXREDIRS, 0);  // SECURITY: No redirections allowed
  curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);  // Only allow HTTP/HTTPS
  curl_setopt($ch, CURLOPT_USERAGENT, 'READ-DownloadService/1.0');  // Identify ourselves

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
