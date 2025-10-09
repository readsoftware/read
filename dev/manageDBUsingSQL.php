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
* manageDBUsingSQL.php
*
* creates, restores or downloads a database from an SQL file
*
* SECURITY WARNING: This file provides direct database management capabilities
* and should be:
* 1. Moved outside the web root in production environments  
* 2. Accessed only via CLI or secure admin interfaces
* 3. Protected with strong authentication and authorization
* 4. Monitored for unauthorized access attempts
*
* Assumes there is a READ_FILE_STORE subdirectory where the dbname.sql is located otherwise
* looks for file in same directory as this service.
* Assumes that '.$psqlUser.' tools like psql.exe are located in a configured path or directory is part of env path.
* For create and restore, if the dbname exist this service will overwrite it. To accomplish this is closes
* db connections and revokes access which can cause warnings to be generated upon connnecting to the dbname
* database afterwards. This can be avoided by checking PHP.ini for pgsql.auto_reset_persistent and set it to On.
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.1 - Security Enhanced
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Dev and support tools
*/
define('ISSERVICE', 1);
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');


require_once dirname(__FILE__) . '/../config.php';//get system config info
require_once dirname(__FILE__) . '/../common/php/userAccess.php';//get user access control

// SECURITY: Check authentication and authorization
if (!isLoggedIn()) {
    http_response_code(401);
    error_log("Unauthorized database management access attempt from IP: " . $_SERVER['REMOTE_ADDR']);
    die('Authentication required');
}

if (!isSysAdmin()) {
    http_response_code(403);
    error_log("Non-admin user attempted database management: " . getUserID() . " from IP: " . $_SERVER['REMOTE_ADDR']);
    die('Administrative privileges required');
}

// SECURITY: Input validation functions
function validateCommand($cmd) {
    $allowedCommands = ['create', 'restore', 'snapshot'];
    return in_array($cmd, $allowedCommands, true);
}

function validateDatabaseName($dbname) {
    // Only allow alphanumeric characters and underscores, 1-63 characters
    return preg_match('/^[a-zA-Z0-9_]{1,63}$/', $dbname);
}

function validateSQLFilename($filename) {
    // Only allow safe filename characters and .sql extension
    return preg_match('/^[a-zA-Z0-9_.-]{1,255}\.sql$/', $filename) && 
           !preg_match('/\.\./', $filename); // Prevent path traversal
}

function validateAndSanitizePath($path) {
    if (empty($path)) {
        return null;
    }
    
    // Define allowed base directories
    $allowedBasePaths = [
        dirname(__FILE__) . '/../data/',
        defined("READ_FILE_STORE") ? READ_FILE_STORE . "/" : ""
    ];
    
    // Resolve the real path
    $realPath = realpath($path);
    if ($realPath === false) {
        return null; // Path doesn't exist or is invalid
    }
    
    // Check if the path is within allowed directories
    foreach ($allowedBasePaths as $basePath) {
        if (!empty($basePath) && strpos($realPath, realpath($basePath)) === 0) {
            return $realPath . '/';
        }
    }
    
    return null; // Path not allowed
}

$cmd = (array_key_exists('cmd', $_REQUEST)? $_REQUEST['cmd']:null);
$dbname = (array_key_exists('dbname', $_REQUEST)? $_REQUEST['dbname']:null);
$sqlfilename = (array_key_exists('sqlfilename', $_REQUEST)? $_REQUEST['sqlfilename']:null);
$sqlfilepath = (array_key_exists('sqlfilepath', $_REQUEST)? $_REQUEST['sqlfilepath']:null);

// SECURITY: Validate all inputs
if (!$cmd || !validateCommand($cmd)) {
    http_response_code(400);
    error_log("Invalid command attempted: " . var_export($cmd, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
    die('Invalid or missing command. Allowed: create, restore, snapshot');
}

if (!$dbname || !validateDatabaseName($dbname)) {
    http_response_code(400);
    error_log("Invalid database name attempted: " . var_export($dbname, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
    die('Invalid database name. Only alphanumeric characters and underscores allowed (1-63 chars)');
}

if (!$sqlfilename || !validateSQLFilename($sqlfilename)) {
    http_response_code(400);
    error_log("Invalid SQL filename attempted: " . var_export($sqlfilename, true) . " from IP: " . $_SERVER['REMOTE_ADDR']);
    die('Invalid SQL filename. Only safe characters and .sql extension allowed');
}

// SECURITY: Validate and sanitize file path
$sqlFilePath = validateAndSanitizePath($sqlfilepath);
if ($sqlFilePath === null) {
    // Use default safe path if no custom path provided or invalid path
    $defaultPath = defined("READ_FILE_STORE") ? READ_FILE_STORE . "/" : dirname(__FILE__) . "/../data/";
    $sqlFilePath = realpath($defaultPath) . '/';
    if (!$sqlFilePath || !is_dir($sqlFilePath)) {
        http_response_code(500);
        error_log("Invalid file storage configuration from IP: " . $_SERVER['REMOTE_ADDR']);
        die('Invalid file storage configuration');
    }
}

if (!$cmd && !$dbname && !$sqlfilename) {
  error_log("Database management attempt with missing parameters from IP: " . $_SERVER['REMOTE_ADDR']);
  echo "A command, database name and SQL filename are required.";
  ob_end_flush();
  return;
} else {
  // Log the operation attempt for security auditing
  error_log("Database operation: $cmd on database: $dbname by user: " . getUserID() . " from IP: " . $_SERVER['REMOTE_ADDR']);
  
  //get password from config.php (securely handled in environment)
  $psqlPWD = defined("PASSWORD") ? PASSWORD : "gandhari";
  //get postgres server name from config.php (securely escaped)
  $pgServerNameSwitch = defined("DBSERVERNAME") ? " -h " . escapeshellarg(DBSERVERNAME) : "";
  //set default postgresql database
  $psqlDB = defined("PSQLDEFAULTDB") ? PSQLDEFAULTDB : 'postgres';
  //get environment set command
  $setCmd = defined("SETENVCMD") ? SETENVCMD : 'export'; //'export' for ubuntu, 'set' for mac bitnami 
  //get shell command separator
  $cmdsep = defined("CMDSEPARATOR") ? CMDSEPARATOR : ';'; // ';' for ubuntu, '&' for mac bitnami
  //need to set environment 'PGPASSWORD' and 'PGDATABASE' before running script
  $psqlPath = ("$setCmd PGDATABASE=" . escapeshellarg($psqlDB) . "$cmdsep ") .
              ($psqlPWD ? "$setCmd PGPASSWORD=" . escapeshellarg($psqlPWD) . "$cmdsep " : '') .
              (defined("PSQL_PATH") && PSQL_PATH ? escapeshellarg(PSQL_PATH) . "/" : "");//configured tool dir or assume in PATH
  // Debug output removed for security - check logs instead
  //get db username (securely escaped)
  $psqlUser = defined("PGUSERNAME") ? PGUSERNAME : 'postgres';
  //get path to db SQL files
  $sqlFilePath = $sqlfilepath?$sqlfilepath:(defined("READ_FILE_STORE")?READ_FILE_STORE."/":"");//set dir for sql file windows
//  $psqlPath = defined("PSQL_PATH")? PSQL_PATH."/" :"";//configured tool dir or assume in PATH
//  $sqlFilePath = defined("READ_FILE_STORE")?READ_FILE_STORE."/":"";//set dir for sql file
  switch ($cmd) {
    case "restore": 
    //WARNING this set of commands may shut of a connection causing a warning first connection to the new/restored db
    //This can be avoided by checking PHP.ini for pgsql.auto_reset_persistent and set it to On.
      $command = $psqlPath.'psql -w -U '.escapeshellarg($psqlUser).$pgServerNameSwitch.' -c '.escapeshellarg("REVOKE CONNECT ON DATABASE $dbname FROM PUBLIC;");
      if (runShellCommand($command, "REVOKED connection on $dbname database", "Aborting - failed to revoke connections to database $dbname")) {
        $command = $psqlPath.'psql -w -U '.escapeshellarg($psqlUser).$pgServerNameSwitch.
                    ' -c '.escapeshellarg("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$dbname';");
        if (runShellCommand($command, "DROPPED connections to $dbname database", "Aborting - failed to drop connections to database $dbname")) {
          $command = $psqlPath.'psql -w -U '.escapeshellarg($psqlUser).$pgServerNameSwitch.' -c '.escapeshellarg("DROP DATABASE IF EXISTS $dbname;");
          if (!runShellCommand($command, "DROPPED $dbname database", "Aborting - failed to drop database $dbname")) {
            echo "unable to restore $dbname from $sqlFilePath$sqlfilename. Please read error <br>";
            break;
          }
        } else {//unable to drop connections
          echo "unable to restore $dbname from $sqlFilePath$sqlfilename. Please read error <br>";
          break;
        }
      } else {//unable to drop connections
        echo "unable to restore $dbname from $sqlFilePath$sqlfilename. Please read error <br>";
        break;
      }
    case "create":
      $createSQL = "CREATE DATABASE $dbname WITH OWNER = ".USERNAME." ENCODING = 'UTF8' TABLESPACE = pg_default LC_COLLATE = 'C' LC_CTYPE = 'C' CONNECTION LIMIT = -1 TEMPLATE template0;";
      $command = $psqlPath.'psql -w -U '.escapeshellarg($psqlUser).$pgServerNameSwitch.' -c '.escapeshellarg($createSQL);
      if (runShellCommand($command, "CREATED $dbname database", "Aborting - failed to create database $dbname")) {
        $command = $psqlPath.'psql -w -U '.escapeshellarg($psqlUser).$pgServerNameSwitch.' -d '.escapeshellarg($dbname).' -f '.escapeshellarg($sqlFilePath.$sqlfilename);
        if (runShellCommand($command, "Loaded $dbname database from $sqlFilePath$sqlfilename", "Aborting - failed to load database $dbname from $sqlFilePath$sqlfilename")) {
          $command = $psqlPath.'psql -w -U '.escapeshellarg($psqlUser).$pgServerNameSwitch.' -c '.escapeshellarg("GRANT CONNECT ON DATABASE $dbname TO PUBLIC;");
           if (runShellCommand($command, "GRANTED connection on $dbname database", "Aborting - failed to GRANTED connections to database $dbname")) {
             echo ('<span id="dbready">'.$dbname.$pgServerNameSwitch.' database ready</span>');// span id="dbready" is for front end test harness used to trigger test after db restore
             ob_flush();
           }
        }
      }
      break;

    case "snapshot":
      $outputFile = $sqlFilePath."snapshot$sqlfilename";
      $command = $psqlPath."pg_dump -U ".escapeshellarg($psqlUser).$pgServerNameSwitch." --no-privileges --no-owner -d ".escapeshellarg($dbname)." > ".escapeshellarg($outputFile);
      if (runShellCommand($command, "Dump $dbname database to $outputFile", "Aborting - failed to dump database $dbname to $outputFile")) {
        $info = new SplFileInfo($outputFile);
        if ($info && $info->isFile()) {
          $size = $info->getSize();
          error_log("Database snapshot created: $outputFile (size: $size bytes) by user: " . getUserID());
          header("Pragma: public");
          header("Expires: 0");
          header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-type: application/sql");
          header("Content-Disposition: attachment; filename=\"snapshot$sqlfilename\"");
          header("Content-Transfer-Encoding: binary");
          header("Content-Length: $size");
          // ob_end_clean();
          // ob_end_flush();
          //echo file_get_contents($outputFile);
          $chunkSize = 50 * 1024 * 1024;
          $handle = fopen($outputFile, 'rb');
          while (!feof($handle))
          {
            $buffer = fread($handle, $chunkSize);
            echo $buffer;
            ob_flush();
            flush();
          }
          fclose($handle);
        }
      }
      break;

    default:
      echo "Unknown command $cmd aborting service call";
  }
}

function runShellCommand ($cmdLine,$outputMsgSuccess = "command successful",$outputMsgError = "command error",$verbose = true) {
  exec($cmdLine . ' 2>&1', $output, $res);
  if (!$verbose) {
    return ($res == 0);
  } else {
    if ($res != 0) {
      echo ($outputMsgError."<br>");
      echo ("Error $res executing cmdLine<br>");
      echo(join(',<br>', $output).'<br>');
      ob_flush();
      return false;
    } else {
      echo ($outputMsgSuccess."<br>");
      echo(join(',<br>', $output).'<br>');
      ob_flush();
      return true;
    }
  }
}
?>
