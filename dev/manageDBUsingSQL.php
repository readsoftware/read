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
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Dev and support tools
*/
define('ISSERVICE', 1);
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');


require_once dirname(__FILE__) . '/../config.php';//get system config info
require_once dirname(__FILE__) . '/../common/php/userAccess.php';//get user access control

$cmd = (array_key_exists('cmd', $_REQUEST)? $_REQUEST['cmd']:null);
$dbname = (array_key_exists('dbname', $_REQUEST)? $_REQUEST['dbname']:null);
$sqlfilename = (array_key_exists('sqlfilename', $_REQUEST)? $_REQUEST['sqlfilename']:null);
$sqlfilepath = (array_key_exists('sqlfilepath', $_REQUEST)? $_REQUEST['sqlfilepath']:null);

if (!$cmd && !$dbname && !$sqlfilename) {
  echo "A command, database name and SQl filename are required.";
  ob_end_flush();
  return;
} else {
  //get password from config.php
  $psqlPWD = defined("PASSWORD")? PASSWORD :null;
  //need to set environment 'PGPASSWORD' before running script
  $psqlPath = ($psqlPWD?"set PGPASSWORD=$psqlPWD& ":'').(defined("PSQL_PATH")? PSQL_PATH."\\" :"");//configured tool dir or assume in PATH windows
  //get db username
  $psqlUser = defined("USERNAME")? USERNAME :'postgres';
  //get path to db SQL files
  $sqlFilePath = $sqlfilepath?$sqlfilepath:(defined("READ_FILE_STORE")?READ_FILE_STORE."\\":"");//set dir for sql file windows
//  $psqlPath = defined("PSQL_PATH")? PSQL_PATH."/" :"";//configured tool dir or assume in PATH
//  $sqlFilePath = defined("READ_FILE_STORE")?READ_FILE_STORE."/":"";//set dir for sql file
  switch ($cmd) {
    case "restore":
    //WARNING this set of commands may shut of a connection causing a warning first connection to the new/restored db
    //This can be avoided by checking PHP.ini for pgsql.auto_reset_persistent and set it to On.
      $command = $psqlPath.'psql -U '.$psqlUser.' -c "REVOKE CONNECT ON DATABASE '.$dbname.' FROM PUBLIC;"';
      if (runShellCommand($command, "REVOKED connection on $dbname database", "Aborting - failed to revoke connections to database $dbname")) {
        $command = $psqlPath.'psql -U '.$psqlUser.
                    ' -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '."'$dbname';\"";
        if (runShellCommand($command, "DROPPED connections to $dbname database", "Aborting - failed to drop connections to database $dbname")) {
          $command = $psqlPath.'psql -U '.$psqlUser.' -c "DROP DATABASE IF EXISTS '.$dbname.';"';
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
      $command = $psqlPath.'psql -U '.$psqlUser.' -c "CREATE DATABASE '."$dbname WITH OWNER = ".USERNAME." ENCODING = 'UTF8' TABLESPACE = pg_default LC_COLLATE = 'C' LC_CTYPE = 'C'".' CONNECTION LIMIT = -1 TEMPLATE template0;"';
      if (runShellCommand($command, "CREATED $dbname database", "Aborting - failed to create database $dbname")) {
        $command = $psqlPath.'psql -U '.$psqlUser." -d $dbname -f ".$sqlFilePath.$sqlfilename;
        if (runShellCommand($command, "Loaded $dbname database from $sqlFilePath$sqlfilename", "Aborting - failed to load database $dbname from $sqlFilePath$sqlfilename")) {
          $command = $psqlPath.'psql -U '.$psqlUser.' -c "GRANT CONNECT ON DATABASE '.$dbname.' TO PUBLIC;"';
           runShellCommand($command, "GRANTED connection on $dbname database", "Aborting - failed to GRANTED connections to database $dbname");
        }
      }
      break;

    case "snapshot":
      $command = $psqlPath."pg_dump -U '.$psqlUser.' --no-privileges --no-owner $dbname > $sqlFilePath"."snapshot$sqlfilename";
      if (runShellCommand($command, "Dump $dbname database to $sqlFilePath"."snapshot$sqlfilename", "Aborting - failed to dump database $dbname to $sqlFilePath"."snapshot$sqlfilename")) {
        $info = new SplFileInfo("$sqlFilePath"."snapshot$sqlfilename");
        if ($info && $info->isFile()) {
          $size = $info->getSize();
          header("Pragma: public");
          header("Expires: 0");
          header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-type: application/sql");
          header("Content-Disposition: attachment; filename=\"snapshot$sqlfilename\"");
          header("Content-Transfer-Encoding: binary");
          header("Content-Length: $size");
          ob_end_clean();
          ob_end_flush();
          echo file_get_contents("$sqlFilePath"."snapshot$sqlfilename");
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
