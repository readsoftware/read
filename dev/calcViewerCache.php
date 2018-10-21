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
  if ( isset($data['txtIDs'])) {
    $txtIDs = $data['txtIDs'];
    $txtIDs = explode(",",$txtIDs);
    $txtID = intval($txtIDs[0]); //first id is primary
    if (!is_int($txtID)) {
      $txtIDs = $txtID = null;
    }
  }
  if ($txtIDs && count($txtIDs) > 0) {
    $texts = new Texts("txt_id in (".join(",",$txtIDs).") and not txt_owner_id = 1","txt_id",null,null);
    $groupLabel = "Selected texts from ".DBNAME." database";
  } else {
    $texts = new Texts("not txt_owner_id = 1","txt_id",null,null);
    $groupLabel = "All texts from ".DBNAME." database";
  }
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
      "checked" => true
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
          $resultsDiv.append(docHtmlFragment);
        }
        
        function updateStatus(docHtmlFragment) {
          if (!docHtmlFragment) {
            docHtmlFragment = "<div>Calculating READ Viewer Cache</div>";
          }
          $statusDiv.html(docHtmlFragment);
        }
        
        function calcCaching() {
          var txtID = null;
          if (cachingState == "Stopping") {
            cachingState = "Stopped";
            return;
          } else if (cachingState == "Paused") {
            return;
          } else if (cachingState == "Calculating") {
            return;
          } else if(!txtIDs || txtIDs.length == 0) {
            cachingState = "Stopped";
            updateStatus("No txtIDs left, nothing left to cache");
            return;
          }
          txtID = txtIDs.shift();
          if (txtID) {
            $btnCalcViewerCache.html('Calculating');
            updateStatus("<div>Calculating READ Viewer Cache for text ("+txtID+")</div>");
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: calcViewerBaseURL+txtID,//caution dependency on context having basepath and dbName
                async: true,
                success: function (data, status, xhr) {
                  $btnCalcViewerCache.html('Calculate');
                  message = '<div class="success">Successfully cache viewer content for text txt:'+txtID+"</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    cachingState = "Paused";
                  } else if (cachingState == "Stopping") {
                    cachingState = "Stopped";
                    return;
                  } else {
                    cachingState = "Stopped";
                    setTimeout(calcCaching,50);
                  }
                },
                error: function (xhr,status,error) {
                    // add record failed.
                  var message = '<div class="error">An error occurred while trying to calculate cache for txt: '+txtID +". Error: " + error+"</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  
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
            calcCaching();
          }
        });

        //Handle Pause button click
        $btnPause.unbind('click').bind('click', function (e) {
          //Check button state
          if ($(this).html() == "Pause") {
            //In call to server so indicate pause wanted
            if (cachingState == "calculating") {
              cachingState = "PausePending"
              $(this).html("PausePending");
            } else { // mark paused and change button state
              cachingState = "Paused";
              $(this).html("Restart");
            }
          } else {
            //Change button to Pauseable state
            $(this).html("Pause");
            if (cachingState == "calculating") {
              return;
            } else if (cachingState == "PausePending" || cachingState == "Stopping") {
              cachingState = "calculating";
              return;
            } else {
              cachingState = "Stopped";
              calcCaching();
            }
          }
        });

        //Handle Cancel button click
        $btnCancel.unbind('click').bind('click', function (e) {
          if (cachingState == "calculating") {
            cachingState = "PausePending"
            $(this).html("PausePending");
          } else { // mark paused and change button state
            cachingState = "Paused";
            $(this).html("Restart");
          }
        });
      });

    </script>
  </head>
  <body>
    <div class="headline">
      <div class="titleDiv"></div>
    </div>
    <div id="textListDiv" >
      <div id="textListHdr"> Text Caching List</div>
      <div id="textListTree"></div>
    </div>
    <div>
      <div style="padding: 15px">
          <input type="button" class="dlgButton" value="Calculate" style="margin-bottom: 5px;" id="btnCalcViewerCache" />
          <input type="button" class="dlgButton" value="Pause" id="btnPause" />
          <input type="button" class="dlgButton" value="Cancel" id="btnCancel" />
      </div>
    </div>
    <h3>Status:</h3>
    <div id="statusDiv">
    </div>
    <h3>Results:</h3>
    <div id="resultsDiv">
    </div>
  </body>
</html>