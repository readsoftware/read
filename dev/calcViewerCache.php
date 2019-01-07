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
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/

/**
* calc viewer caching
*
* creates a framework for selecting the text to refresh/calculate cached information
* it support opening with a parameter set that how the calculation will proceed.
*
* The caching app will bring up a check box tree of selected text or all text in the database 
* depending on how it is called
* 
* localhost:81/readDev/dev/calcViewerCache.php?db=gandhari&catID=1&txtIDs=1,2,3,4,5
* or
* localhost:81/readDev/dev/calcViewerCache.php?db=gandhari&catID=1
* 
* the glossary catID needs to be supplied in the current version
* &refresh=n where n is 0,1 or 2 can be added to the url (default is refresh=0)
* the rest of the parameters for the site configuration of the READ Viewer will be used
* when the app calls getTextViewer.php
* 
* the app has a pause and cancel button which are asynch and take effect on the next text cycle
* cancel - will stop the cache calculation and empty the text id list as if the app just started
* pause - will pause the cache calculation at the next text and can be restarted at teh paused text
* 
*/

  require_once (dirname(__FILE__) . '/../common/php/sessionStartUp.php');//initialize the session
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//get user access control
  $dbMgr = new DBManager();
  
  //check and validate parameters
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);

  $refresh = (isset($data['refresh']) && $data['refresh'])? $data['refresh']: 0;
  $multiEd = (!isset($data['multiEd']) || !$data['multiEd'])? 0: 1;
  $catID = (isset($data['catID']) && $data['catID'])? $data['catID']: null;
  $txtIDs = null;
  $condition = "not txt_owner_id = 1";
  $groupLabel = "All texts from ".DBNAME." database";
  if ( isset($data['txtIDs'])) {
    $txtIDs = $data['txtIDs'];
    if (strpos($txtIDs,'-') !== false) {
      list($startID,$endID) = explode("-",$txtIDs);
      if ( $startID && is_numeric($startID) && $endID && is_numeric($endID) && $startID <= $endID) {
        $condition = "txt_id >= $startID and txt_id <= $endID and not txt_owner_id = 1";
        $groupLabel = "Selected texts from ".DBNAME." database";
      }
    } else if (strpos($txtIDs,',') !== false) {
      $txtIDs = explode(",",$txtIDs);
      if (is_int($txtIDs[0])) {
        $condition = "txt_id in (".join(",",$txtIDs).") and not txt_owner_id = 1";
        $groupLabel = "Selected texts from ".DBNAME." database";
      }
    }
  }
  $texts = new Texts($condition,"txt_id",null,null);
  if (!$texts || $texts->getCount() == 0 ) {
    //exit with error
  } else {
    $textList = array();
    foreach($texts as $text) {
      $txtGID = $text->getGlobalID();
      $txtTag = str_replace(":","",$txtGID);
      $txtTitle= $text->getTitle();
      $textInfo = array( "label" => $txtTitle." ($txtGID)",
        "id" => $txtTag,
        "value" => $text->getID());
      array_push($textList,$textInfo);
    }
    $textSelect = array(
      "label" => $groupLabel,
      "value" => "all",
      "id" => "alltxt",
      "items" => $textList,
      "checked" => true,
      "expanded" => true
    );
  }

  $calcViewerBaseURL = SITE_BASE_PATH."/viewer/getTextViewer.php?db=".DBNAME."&refresh=$refresh".
                        ($catID?"&catID=$catID" : "").
                        ($multiEd?"&multiEd=$multiEd" : "")."&txtID=";

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title>Calculate Text Edition Caching</title>
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.energyblue.css" type="text/css" />
    <link rel="stylesheet" href="./css/readviewer.css" type="text/css" />
    <script src="/jquery/jquery-1.11.1.min.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcore.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtouch.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdata.js"></script>
    <script src="/jqwidget/jqwidgets/jqxexpander.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtooltip.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcheckbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttons.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtree.js"></script>
    <script src="/jqwidget/jqwidgets/jqxwindow.js"></script>
    <script src="../editors/js/utility.js"></script>
    <script src="../editors/js/debug.js"></script>
    <script type="text/javascript">
      var dbName = '<?=DBNAME?>', imgViewer,
          cachingState = "Stopped",
          srvbasepath="<?=SITE_ROOT?>",
          basepath="<?=SITE_BASE_PATH?>",
          textSource = [<?=json_encode($textSelect)?>],
          calcViewerBaseURL ="<?=$calcViewerBaseURL?>";

      $(document).ready( function () {
        var txtIDs = [],
            txtID = null,
            lastCachedID = null,
            $textListDiv = $('#textListDiv'),
            $textListHdr = $('#textListHdr'),
            $textListTree = $('#textListTree'),
            $resultsDiv = $('#resultsDiv'),
            $statusDiv = $('#statusDiv'),
            $btnCalcViewerCache = $('#btnCalcViewerCache'),
            $btnPause = $('#btnPause'),
            $btnCancel = $('#btnCancel');
        $textListTree.jqxTree({
             source: textSource,
             hasThreeStates: true, 
             checkboxes: true,
             width: '500px',
             theme:'energyblue'
        });
        
        function addResultsMessage(docHtmlFragment) {
          $resultsDiv.prepend(docHtmlFragment);
        }
        
        function updateStatus(docHtmlFragment) {
          if (!docHtmlFragment) {
            docHtmlFragment = "<div>Calculating READ Viewer Cache</div>";
          }
          $statusDiv.html(docHtmlFragment);
        }
        
        function calcCaching() {
          txtID = null;
          if (cachingState == "Stopping") {
            cachingState = "Stopped";
            $btnCancel.val("Cancel");
            updateStatus("<div>Stopped READ Viewer Caching, last cached text ("+txtID+")</div>");
            return;
          } else if (cachingState == "Paused" || cachingState == "PausePending") {
            cachingState = "Paused";
            $btnPause.val("Restart");
            updateStatus("<div>Paused READ Viewer Caching, next text ("+txtIDs[0]+")</div>");
            return;
          } else if (cachingState == "Stopped") {
            return;
          } else if(!txtIDs || txtIDs.length == 0) {
            cachingState = "Stopped";
            updateStatus("<div>No txtIDs left, nothing left to cache</div>");
            return;
          }
          txtID = txtIDs.shift();
          if (txtID) {
            updateStatus("<div>Calculating READ Viewer Cache for text ("+txtID+")</div>");
            d = new Date();
            stime = d.getTime();
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: calcViewerBaseURL+txtID,//caution dependency on context having basepath and dbName
                async: true,
                success: function (data, status, xhr) {
                  lastCachedID = txtID
                  d = new Date();
                  etime = d.getTime();
                  sec = Math.round((etime-stime)/1000);
                  message = '<div class="success">Successfully cache viewer content for text txt:'+lastCachedID+" in "+sec+" seconds</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    cachingState = "Paused";
                    $btnPause.val("Restart");
                    $btnCalcViewerCache.val('Paused');
                    updateStatus("<div>Paused READ Viewer Caching, next text ("+txtIDs[0]+")</div>");
                  } else if (cachingState == "Stopping") {
                    cachingState = "Stopped";
                    $btnCalcViewerCache.val('Calculate');
                    $btnCancel.val("Cancel");
                    updateStatus("<div>Stopped READ Viewer Caching, last cached text ("+lastCachedID+")</div>");
                    return;
                  } else {
                    setTimeout(calcCaching,50);
                  }
                },
                error: function (xhr,status,error) {
                    // add record failed.
                  var message = '<div class="error">An error occurred while trying to calculate cache for txt: '+txtID +". Error: " + error+"</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    $btnPause.val("Restart");
                  }
                  cachingState = "Stopped";
                  $btnCalcViewerCache.val('Calculate');
                  $btnCancel.val("Cancel");
                  updateStatus("<div>Stopped READ Viewer Caching, last cached text ("+lastCachedID+")</div>");
                }
            });
          }  
        }

        //Handle Cache button click
        $btnCalcViewerCache.unbind('click').bind('click', function (e) {
          var checkedItems = $textListTree.jqxTree('getCheckedItems'), txtID;
          txtIDs = [];
          if (checkedItems.length == 0) {
            alert("No selected text found so nothing to cache");
          } else if (cachingState !== "Stopped") {
            alert("Caching must be stopped before starting cache. Try pressing cancel.");
            return;
          } else {
            for (i in checkedItems) {
              txtID = checkedItems[i].value;
              if (!isNaN(txtID)) {
                txtIDs.push(txtID);
              }
            }
            $btnCalcViewerCache.val('Calculating');
            cachingState = "Calculating";
            calcCaching();
          }
        });

        //Handle Pause button click
        $btnPause.unbind('click').bind('click', function (e) {
          //Check button state
          if ($(this).val() == "Pause") {
            //In call to server so indicate pause wanted
            if (cachingState == "Calculating") {
              cachingState = "PausePending"
              $(this).val("PausePending");
            } else { // mark paused and change button state
              cachingState = "Paused";
              $btnCalcViewerCache.val('Paused');
              updateStatus("<div>Paused READ Viewer Caching, next text ("+txtIDs[0]+")</div>");
              $(this).val("Restart");
            }
          } else {
            //Change button to Pauseable state
            $(this).val("Pause");
            if (cachingState == "Calculating") {
              return;
            } else {
              cachingState = "Calculating";
              $btnCalcViewerCache.val('Calculating');
              calcCaching();
            }
          }
        });

        //Handle Cancel button click
        $btnCancel.unbind('click').bind('click', function (e) {
          if (cachingState == "Calculating" || cachingState == "PausePending") {
            cachingState = "Stopping"
            $(this).val("Stopping");
          } else { // mark paused and change button state
            cachingState = "Stopped";
            updateStatus("<div>Stopped READ Viewer Caching, last cached text ("+lastCachedID+")</div>");
            $btnCalcViewerCache.val('Calculate');
            $(this).val("Cancel");
          }
          $btnPause.val("Pause");
        });
      });

    </script>
  </head>
  <body>
    <div class="headline">
      <div class="titleDiv"></div>
    </div>
    <div style="display:table">
      <div style="display:table-row">
        <div style="padding: 15px">
            <input type="button" class="dlgButton" value="Calculate" style="margin-bottom: 5px;" id="btnCalcViewerCache" />
            <input type="button" class="dlgButton" value="Pause" id="btnPause" />
            <input type="button" class="dlgButton" value="Cancel" id="btnCancel" />
        </div>
        <div id="textListHdr"> Text Caching List</div>
      </div>
      <div style="display:table-row">
        <div id="textListDiv" style="display:table-cell">
          <div id="textListTree"></div>
        </div>
        <div style="display:table-cell; padding-left: 15px">
          <h3>Status:</h3>
          <div id="statusDiv">
          </div>
          <h3>Results:</h3>
          <div id="resultsDiv">
          </div>
        </div>
      </div>
    </div>
  </body>
</html>