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
* calc text edition caching
*
* creates a framework for selecting the text edition to refresh/calculate cached information
* it support opening with a parameter set that how the calculation will proceed.
*
* The caching app will bring up a check box tree of selected edition or all editions in the database 
* depending on how it is called
* 
* localhost:81/readDev/dev/calcTextEditionCache.php?db=gandhari&ednIDs=1,2,3,4,5
* or
* localhost:81/readDev/dev/calcTextEditionCache.php?db=gandhari&ednIDs=1-5
* or
* localhost:81/readDev/dev/calcTextEditionCache.php?db=gandhari
* 
* &refresh=n where n is 0,1 or 2 can be added to the url (default is refresh=0)
* the rest of the parameters for the site configuration of the READ Viewer will be used
* 
* the app has a pause and cancel button which are asynch and take effect on the next edition cycle
* cancel - will stop the cache calculation and empty the edition id list as if the app just started
* pause - will pause the cache calculation at the next edition and can be restarted at teh paused edition
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
  $ednIDs = null;
  $condition = "not edn_owner_id = 1";
  $groupLabel = "All editions from ".DBNAME." database";
  if ( isset($data['ednIDs'])) {
    $ednIDs = $data['ednIDs'];
    if (strpos($ednIDs,'-') !== false) {
      list($startID,$endID) = explode("-",$ednIDs);
      if ( $startID && is_numeric($startID) && $endID && is_numeric($endID) && $startID <= $endID) {
        $condition = "edn_id >= $startID and edn_id <= $endID and not edn_owner_id = 1";
        $groupLabel = "Selected editions from ".DBNAME." database";
      }
    } else if (strpos($ednIDs,',') !== false) {
      $ednIDs = explode(",",$ednIDs);
      if (is_numeric($ednIDs[0])) {
        $condition = "edn_id in (".join(",",$ednIDs).") and not edn_owner_id = 1";
        $groupLabel = "Selected editions from ".DBNAME." database";
      }
    } else if (is_numeric($ednIDs)) { //single number case
      $condition = "edn_id = $ednIDs and not edn_owner_id = 1";
      $groupLabel = "Selected edition from ".DBNAME." database";
    }
  }
  $editions = new Editions($condition,"edn_id",null,null);
  if (!$editions || $editions->getCount() == 0 ) {
    //exit with error
  } else {
    $editionList = array();
    foreach($editions as $edition) {
      $ednGID = $edition->getGlobalID();
      $ednTag = str_replace(":","",$ednGID);
      $ednDesc= $edition->getDescription();
      $editionInfo = array( "label" => $ednDesc." ($ednGID)",
        "id" => $ednTag,
        "value" => $edition->getID());
      array_push($editionList,$editionInfo);
    }
    $editionselect = array(
      "label" => $groupLabel,
      "value" => "all",
      "id" => "alledn",
      "items" => $editionList,
      "checked" => true,
      "expanded" => true
    );
  }

  $loadTextEditionBaseURL = SITE_BASE_PATH."/services/loadTextEdition.php?db=".DBNAME."&refresh=$refresh"."&ednID=";

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="edition/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title>Calculate edition Edition Caching</title>
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
          editionsource = [<?=json_encode($editionselect)?>],
          loadTextEditionBaseURL ="<?=$loadTextEditionBaseURL?>";
			DEBUG.log("gen","initializing");

      $(document).ready( function () {
        var ednIDs = [],
            ednID = null,
            lastCachedID = null,
            $editionListDiv = $('#editionListDiv'),
            $editionListHdr = $('#editionListHdr'),
            $editionListTree = $('#editionListTree'),
            $resultsDiv = $('#resultsDiv'),
            $statusDiv = $('#statusDiv'),
            $btnloadTextEditionCache = $('#btnloadTextEditionCache'),
            $btnPause = $('#btnPause'),
            $btnCancel = $('#btnCancel');
        $editionListTree.jqxTree({
             source: editionsource,
             hasThreeStates: true, 
             checkboxes: true,
             width: '500px',
             theme:'energyblue'
        });
				DEBUG.log("gen","initializing");
        
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
          ednID = null;
          if (cachingState == "Stopping") {
            cachingState = "Stopped";
            $btnCancel.val("Cancel");
            updateStatus("<div>Stopped READ Viewer Caching, last cached edition ("+ednID+")</div>");
            return;
          } else if (cachingState == "Paused" || cachingState == "PausePending") {
            cachingState = "Paused";
            $btnPause.val("Restart");
            updateStatus("<div>Paused READ Viewer Caching, next edition ("+ednIDs[0]+")</div>");
            return;
          } else if (cachingState == "Stopped") {
            return;
          } else if(!ednIDs || ednIDs.length == 0) {
            cachingState = "Stopped";
            updateStatus("<div>No ednIDs left, nothing left to cache</div>");
            return;
          }
          ednID = ednIDs.shift();
          if (ednID) {
            updateStatus("<div>Calculating Cache for edition ("+ednID+")</div>");
            d = new Date();
            stime = d.getTime();
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: loadTextEditionBaseURL+ednID,//caution dependency on context having basepath and dbName
                async: true,
                success: function (data, status, xhr) {
                  lastCachedID = ednID
                  d = new Date();
                  etime = d.getTime();
                  sec = Math.round((etime-stime)/1000);
                  message = '<div class="success">Successfully cached Edition content for edition edn:'+lastCachedID+" in "+sec+" seconds</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    cachingState = "Paused";
                    $btnPause.val("Restart");
                    $btnloadTextEditionCache.val('Paused');
                    updateStatus("<div>Paused READ Caching, next edition ("+ednIDs[0]+")</div>");
                  } else if (cachingState == "Stopping") {
                    cachingState = "Stopped";
                    $btnloadTextEditionCache.val('Calculate');
                    $btnCancel.val("Cancel");
                    updateStatus("<div>Stopped READ Caching, last cached edition ("+lastCachedID+")</div>");
                    return;
                  } else {
                    setTimeout(calcCaching,50);
                  }
                },
                error: function (xhr,status,error) {
                    // add record failed.
                  var message = '<div class="error">An error occurred while trying to calculate cache for edn: '+ednID +". Error: " + error+"</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    $btnPause.val("Restart");
                  }
                  cachingState = "Stopped";
                  $btnloadTextEditionCache.val('Calculate');
                  $btnCancel.val("Cancel");
                  updateStatus("<div>Stopped READ Viewer Caching, last cached edition ("+lastCachedID+")</div>");
                }
            });
          }  
        }

        //Handle Cache button click
        $btnloadTextEditionCache.unbind('click').bind('click', function (e) {
          var checkedItems = $editionListTree.jqxTree('getCheckedItems'), ednID;
          ednIDs = [];
          if (checkedItems.length == 0) {
            alert("No selected edition found so nothing to cache");
          } else if (cachingState !== "Stopped") {
            alert("Caching must be stopped before starting cache. Try pressing cancel.");
            return;
          } else {
            for (i in checkedItems) {
              ednID = checkedItems[i].value;
              if (!isNaN(ednID)) {
                ednIDs.push(ednID);
              }
            }
            $btnloadTextEditionCache.val('Calculating');
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
              $btnloadTextEditionCache.val('Paused');
              updateStatus("<div>Paused READ Viewer Caching, next edition ("+ednIDs[0]+")</div>");
              $(this).val("Restart");
            }
          } else {
            //Change button to Pauseable state
            $(this).val("Pause");
            if (cachingState == "Calculating") {
              return;
            } else {
              cachingState = "Calculating";
              $btnloadTextEditionCache.val('Calculating');
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
            updateStatus("<div>Stopped READ Viewer Caching, last cached edition ("+lastCachedID+")</div>");
            $btnloadTextEditionCache.val('Calculate');
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
            <input type="button" class="dlgButton" value="Calculate" style="margin-bottom: 5px;" id="btnloadTextEditionCache" />
            <input type="button" class="dlgButton" value="Pause" id="btnPause" />
            <input type="button" class="dlgButton" value="Cancel" id="btnCancel" />
        </div>
        <div id="editionListHdr"> edition Caching List</div>
      </div>
      <div style="display:table-row">
        <div id="editionListDiv" style="display:table-cell">
          <div id="editionListTree"></div>
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