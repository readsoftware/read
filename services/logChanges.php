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
* logChanges
*
* service that store log entries for editions and/or catalogs
*
* when called with no parameters the service will query the system to compile a list of entities that
* have been changed by the current user and return with a form for entering the log text for the first
* entity. When an entry is saved the service will select the next entity and return a form for entering
* a log entry for it until all entities are logged or the user cancels.
*
*This is targetted at full filling the TEI revisionDesc Header node
*
*     <revisionDesc>
      <change who="#SB" when="2017-06-13">Creation of the file through READ export.</change>
      <change who="#SB" when="2017-06-19">Initial adaptation of file.</change>
      <change who="#SB" when="2017-06-20">Fixed apparatus.</change>
      <change who="#AG" when="2017-06-21">Changed title and added comments/responses</change>
      <change who="#AG" when="2017-06-22">Various fixes.</change>
      <change who="#AG" when="2017-06-23">Fixed bibliographic entries.</change>
      <change who="#AG" when="2017-06-23">Removed some comments and responded to others.</change>
      <change who="#SB" when="2017-06-26">Changed biblScope to citedRange, g to g type=1, wit > resp, PR biblio entry.</change>
      <change who="#SB" when="2017-06-27">Added xml:lang="pyx" to edition; tagged ASB, ASI; expanded pyx section of language ident.</change>
      <change who="#SB" when="2017-07-08">bibl formatting fixes.</change>
      <change who="#SB" when="2017-07-12">Restored PPPB to the bibliography; it was accidentally deleted at some point.</change>
      <change who="#SB" when="2017-07-15">Implemented idno, origDate changes from xxxx's emails.</change>
      <change who="#SB" when="2017-07-15">Added xml:lang="en" to msItem.</change>
      <change who="#AG" when="2017-07-17">Removed full stops from textLang and language.</change>
    </revisionDesc>

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


require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Editions.php');
require_once (dirname(__FILE__) . '/../model/entities/Catalogs.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$retVal = array();
$errors = array();
$warnings = array();
$log = "";
$title = null;
$entity = null;
$nextentity = null;
$logCount = 0;
$_SESSION['processlog'] = array();
$userID = getUserID();
$condition = "cat_scratch like '%editUserID".'":'."$userID%' and cat_scratch like '%status".'":"'."changed%'";
$catalogs = new Catalogs($condition,'cat_id',null,null);
if ($catalogs && $catalogs->getCount() > 0) {
  $logCount += $catalogs->getCount();
  $_SESSION['processlog']['catIDs'] = $catalogs->getKeys();
  $log .= "Found catalogs with changes: ids ".join(',',$catalogs->getKeys())." <br/>";
}
$condition = "edn_scratch like '%editUserID".'":'."$userID%' and edn_scratch like '%status".'":"'."changed%'";
$editions = new Editions($condition,'edn_id',null,null);
if ($editions && $editions->getCount() > 0) {
  $logCount += $editions->getCount();
  $_SESSION['processlog']['ednIDs'] = $editions->getKeys();
  $log .= "Found edition with changes: ids ".join(',',$editions->getKeys())." <br/>";
}
// find first logable entity to process
if ($logCount == 0) {
  $log .= "No changed entities found for user ".getUserName()." <br/>";
} else {
  $catID = null;
  $ednID = null;
  $nextentity = null;
  if (array_key_exists('catIDs',$_SESSION['processlog'])) {
    if (count($_SESSION['processlog']['catIDs']) > 0) {
      while (!$catID) {
        $catID = array_shift($_SESSION['processlog']['catIDs']);
        $nextentity = new Catalog($catID);
        if (!$nextentity || !$nextentity->getID() || $nextentity->hasError()) {
          $log .= "Unable to access/use entity cat$catID <br/>";
          $catID = null;
          $nextentity = null;
        } else {
          break;
        }
      }
    }
    if (count($_SESSION['processlog']['catIDs']) == 0) {
      unset($_SESSION['processlog']['catIDs']);
      $log .= "Completed processing catalog entities ids <br/>";
    }
  }
  if (!$nextentity && array_key_exists('ednIDs',$_SESSION['processlog'])) {
    if (count($_SESSION['processlog']['ednIDs']) > 0) {
      while (!$ednID) {
        $ednID = array_shift($_SESSION['processlog']['ednIDs']);
        $nextentity = new Edition($ednID);
        if (!$nextentity || !$nextentity->getID() || $nextentity->hasError()) {
          $log .= "Unable to access/use entity edn$ednID <br/>";
          $ednID = null;
          $nextentity = null;
        } else {
          break;
        }
      }
    }
    if (count($_SESSION['processlog']['ednIDs']) == 0) {
      unset($_SESSION['processlog']['ednIDs']);
      $log .= "Completed processing edition entity ids <br/>";
    }
  }
  if (!$nextentity) {
    unset($_SESSION['processlog']);
    $log .= "Finished processing change entity ids. Removing process log list from session <br/>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title>READ Change Log Utility</title>
    <link rel="stylesheet" href="./editors/css/imageViewer.css" type="text/css" />
    <script src="/jquery/jquery-1.11.1.min.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcore.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtouch.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownbutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttons.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttongroup.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxwindow.js"></script>
    <script src="/jqwidget/jqwidgets/jqxsplitter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxvalidator.js"></script>
    <script src="/jqwidget/jqwidgets/jqxpanel.js"></script>
    <script src="../editors/js/utility.js"></script>
    <script src="../editors/js/debug.js"></script>
<?php
if ($nextentity) {
  $nextentityType = $nextentity->getEntityTypeCode();
  $nextentityID = $nextentity->getID();
  if ($nextentityType == 'cat') {
      $title = $nextentity->getTitle();
  } else {
    $title = $nextentity->getDescription();
  }

  $userID = $nextentity->getScratchProperty('editUserID');
  if (!$userID) {
    $userID = getUserID();
  }
  $user = new UserGroup($userID);
  if ($user && $user->getID() == $userID){
    $username = $user->getFirstname()." ".$user->getFamilyName();
  } else {
    $username = getUserName();
  }
  $date = date("Y-m-d");

?>
    <script type="text/javascript">
      var $btnSave,$btnSkip,$btnCancel,$username,$logdate,$logentry
          dbName = '<?=DBNAME?>',
          basepath="<?=SITE_BASE_PATH?>",
          logUser = '<?=$username?$username:"" ?>',
          entityTitle = '<?=$title?$title:"No entity title or description found" ?>',
          entityType = '<?=$nextentityType?$nextentityType:"" ?>',
          entityID = '<?=$nextentityID?$nextentityID:"" ?>',
          logDate = '<?=$date?$date:"" ?>';
      $(document).ready( function () {
        $username = $('#logusername');
        $logdate = $('#logdate');
        $loggingUI = $('#loggingUI');
        $responseText = $('#responseText');
        $logentry = $('#logentry');
        $entrytitle = $('#enttitle');
        $btnSave = $('#btnSave');
        $btnSkip = $('#btnSkip');
        $btnCancel = $('#btnCancel');
        $btnSave.unbind('click').bind('click', function(e) {
          var savedata = {}, entTag = entityType+entityID;
          //setup data
          if (entityType == "cat") {
            savedata['catID'] = parseInt(entityID);
          } else {
            savedata['ednID'] = parseInt(entityID);
          }
          savedata['logusername'] = $username.val();
          savedata['logdate'] = $logdate.val();
          savedata['logentry'] = $logentry.val();
          if (DEBUG.healthLogOn) {
            savedata['hlthLog'] = 1;
          }
          $.ajax({
              type:"POST",
              dataType: 'json',
              url: basepath+'/services/saveEntityLog.php?db='+dbName,
              data: savedata,
              asynch: true,
              success: function (data, status, xhr) {
                if (typeof data == 'object') {
                  if (data.success && data.nextEntityID) {//re-init UI
                    logUser = data.editUserName;
                    $username.html(logUser);
                    entityTitle = data.nextEntityTitle;
                    $entrytitle.html(entityTitle);
                    entityType = data.nextEntityType;
                    entityID = data.nextEntityID;
                    $logentry.html();
                    logDate = data.date;
                    $logdate.html(logDate);
                  } else {
                    $loggingUI.html("<h3> No more entities to log </h3>");
                  }
                  if (data.log) {
                    var logText = data.log;
                    if (data.entlog) {
                      logText += " <h4>Entity Log </h4> <br/>" +  JSON.stringify(data.entlog,true);
                    }
                    $responseText.html(logText);
                  } else {
                    $responseText.html("no response log returned");
                  }
                  if (data.editionHealth) {
                    DEBUG.log("health","***Tag Entity***");
                    DEBUG.log("health","Params: "+JSON.stringify(savedata));
                    DEBUG.log("health",data.editionHealth);
                  }
                  if (data.errors) {
                    alert("An error occurred while trying to save log entry for "+ entTag + ". Error: " + data.errors.join());
                  }
                }
              },
              error: function (xhr,status,error) {
                // add record failed.
                errStr = "Error while trying to save log entry for "+ entTag + ". Error: " + error;
                DEBUG.log("err",errStr);
                alert(errStr);
              }
          });// end ajax
        });
        $btnSkip.unbind('click').bind('click', function(e) {
          var savedata = {}, entTag = entityType+entityID;
          savedata['skip'] = 1;
          if (DEBUG.healthLogOn) {
            savedata['hlthLog'] = 1;
          }
          $.ajax({
              type:"POST",
              dataType: 'json',
              url: basepath+'/services/saveEntityLog.php?db='+dbName,
              data: savedata,
              asynch: true,
              success: function (data, status, xhr) {
                if (typeof data == 'object') {
                  if (data.success && data.nextEntityID) {//re-init UI
                    logUser = data.editUserName;
                    $username.html(logUser);
                    entityTitle = data.nextEntityTitle;
                    $entrytitle.html(entityTitle);
                    entityType = data.nextEntityType;
                    entityID = data.nextEntityID;
                    $logentry.html();
                    logDate = data.date;
                    $logdate.html(logDate);
                  } else {
                    $loggingUI.html("<h3> No more entities to log </h3>");
                  }
                  if (data.log) {
                    var logText = data.log;
                    if (data.entlog) {
                      logText += " <h4>Entity Log </h4> <br/>" + JSON.stringify(data.entlog,true);
                    }
                    $responseText.html(logText);
                  } else {
                    $responseText.html("no response log returned");
                  }
                  if (data.editionHealth) {
                    DEBUG.log("health","***Tag Entity***");
                    DEBUG.log("health","Params: "+JSON.stringify(savedata));
                    DEBUG.log("health",data.editionHealth);
                  }
                  if (data.errors) {
                    alert("An error occurred while trying to skip log entry for "+ entTag + ". Error: " + data.errors.join());
                  }
                }
              },
              error: function (xhr,status,error) {
                // add record failed.
                errStr = "Error while trying to skip log entry for "+ entTag + ". Error: " + error;
                DEBUG.log("err",errStr);
                alert(errStr);
              }
          });// end ajax
        });
        $btnCancel.unbind('click').bind('click', function(e) {
          var savedata = {}, entTag = entityType+entityID;
          savedata['cancel'] = 1;
          if (DEBUG.healthLogOn) {
            savedata['hlthLog'] = 1;
          }
          $.ajax({
              type:"POST",
              dataType: 'json',
              url: basepath+'/services/saveEntityLog.php?db='+dbName,
              data: savedata,
              asynch: true,
              success: function (data, status, xhr) {
                if (typeof data == 'object') {
                  $loggingUI.html();
                  if (data.log) {
                    $responseText.html(data.log);
                  } else {
                    $responseText.html("no response log returned");
                  }
                  if (data.editionHealth) {
                    DEBUG.log("health","***Tag Entity***");
                    DEBUG.log("health","Params: "+JSON.stringify(savedata));
                    DEBUG.log("health",data.editionHealth);
                  }
                  if (data.errors) {
                    alert("An error occurred while trying to cancel logging. Error: " + data.errors.join());
                  }
                }
              },
              error: function (xhr,status,error) {
                // add record failed.
                errStr = "Error while trying to cancel logging. Error: " + error;
                DEBUG.log("err",errStr);
                alert(errStr);
              }
          });// end ajax
        });
      });
    </script>
<?php
}
?>
  </head>
<body>
  <h3 id="entityLogHeader" >Entity Change Log</h3>
<?php
if ($nextentity) {
?>
  <div style="margin: 10px"><span style="margin-right: 10px">Entity Title:</span><span id="enttitle"><?=$title?$title:""?></span></div>
  <div id="loggingUI" style="margin: 10px">
    <div class="logInputBox">
      <span id="lblFName" class="logInputLabel" style="margin-right: 10px">Username :</span>
      <input type="text" class="logInput" id="logusername" style="margin-top: 10px" value="<?=$username ?>"/>
    </div>
    <div class="logInputBox">
      <span id="lblTitle" class="logInputLabel" style="margin-right: 44px">Date :</span>
      <input type="text" class="logInput" id="logdate" style="margin-top: 10px" value="<?= $date ?>"/>
    </div>
    <div class="logInputBox" style="padding-top: 10px" >
      <span class="logInputLabel" style="margin-right: 48px; vertical-align: top;">Log :</span>
      <textarea rows="5" cols="60" id="logentry" placeholder="Enter log here"></textarea>
    </div>
    <div style="margin: 10px" >
        <input type="button" class="logButton" value="Save" style="margin-bottom: 5px;" id="btnSave" />
        <input type="button" class="logButton" value="Skip" style="margin-bottom: 5px;" id="btnSkip" />
        <input type="button" class="logButton" value="Cancel" id="btnCancel" />
    </div>
  </div>
<?php
} else {
?>
  <div id="loggingUI" style="margin: 10px">No changes to log</div>
<?php
}
if ($log){
?>
  <h3 id="responseHeader" >Response information</h3>
  <div id="responseText" ><?=$log?></div>
<?php
}
?>
</body>
</html>
