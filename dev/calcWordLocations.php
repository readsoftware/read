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
* calc word locations
*
* creates a framework for selecting the edition to refresh/calculate cached information
* it support opening with a parameter set that how the calculation will proceed.
*
* The caching app will bring up a check box tree of selected edition or all edition in the database 
* depending on how it is called
* 
* localhost:81/readDev/dev/calcWordLocations.php?db=gandhari&catID=1&ednIDs=1,2,3,4,5
* or
* localhost:81/readDev/dev/calcWordLocations.php?db=gandhari&catID=1
* 
* the glossary catID needs to be supplied in the current version
* &refresh=n where n is 0,1 or 2 can be added to the url (default is refresh=0)
* the rest of the parameters for the site configuration of the READ Viewer will be used
* when the app calls geteditionViewer.php
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
  $ednIDs = null;
  if ( isset($data['ednIDs'])) {
    $ednIDs = $data['ednIDs'];
    $ednIDs = explode(",",$ednIDs);
    $ednID = intval($ednIDs[0]); //first id is primary
    if (!is_int($ednID)) {
      $ednIDs = $ednID = null;
    }
  }
  if ($ednIDs && count($ednIDs) > 0) {
    $editions = new Editions("edn_id in (".join(",",$ednIDs).") and not edn_owner_id = 1","edn_id",null,null);
    $groupLabel = "Selected editions from ".DBNAME." database";
  } else {
    $editions = new editions("not edn_owner_id = 1","edn_id",null,null);
    $groupLabel = "All editions from ".DBNAME." database";
  }
  if (!$editions || $editions->getCount() == 0 ) {
    //exit with error
  } else {
    $editionList = array();
    foreach($editions as $edition) {
      $ednGID = $edition->getGlobalID();
      $ednTag = str_replace(":","",$ednGID);
      $ednTitle= $edition->getDescription();
      $editionInfo = array( "label" => $ednTitle." ($ednGID)",
        "id" => $ednTag,
        "value" => $edition->getID());
      array_push($editionList,$editionInfo);
    }
    $editionSelect = array(
      "label" => $groupLabel,
      "value" => "all",
      "id" => "alltxt",
      "items" => $editionList,
      "checked" => true,
      "expanded" => true
    );
  }

  $calcEdnWordLocationsURL = SITE_BASE_PATH."/services/refreshEditionWordLocations.php?db=".DBNAME."&ednID=";

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title>Calculate Edition Word Locations</title>
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
          calcState = "Stopped",
          srvbasepath="<?=SITE_ROOT?>",
          basepath="<?=SITE_BASE_PATH?>",
          editionSource = [<?=json_encode($editionSelect)?>],
          calcEdnWordLocationsURL ="<?=$calcEdnWordLocationsURL?>";

      $(document).ready( function () {
        var ednIDs = [],
            ednID = null,
            lastCachedID = null,
            $editionListDiv = $('#editionListDiv'),
            $editionListHdr = $('#editionListHdr'),
            $editionListTree = $('#editionListTree'),
            $resultsDiv = $('#resultsDiv'),
            $statusDiv = $('#statusDiv'),
            $btnWordLocations = $('#btnWordLocations'),
            $btnPause = $('#btnPause'),
            $btnCancel = $('#btnCancel');
        $editionListTree.jqxTree({
             source: editionSource,
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
        
        function calcLocation() {
          ednID = null;
          if (calcState == "Stopping") {
            calcState = "Stopped";
            $btnCancel.val("Cancel");
            updateStatus("<div>Stopped READ location calculation, last calculated edition ("+ednID+")</div>");
            return;
          } else if (calcState == "Paused" || calcState == "PausePending") {
            calcState = "Paused";
            $btnPause.val("Restart");
            updateStatus("<div>Paused READ Location Calculation, next edition ("+ednIDs[0]+")</div>");
            return;
          } else if (calcState == "Stopped") {
            return;
          } else if(!ednIDs || ednIDs.length == 0) {
            calcState = "Stopped";
            updateStatus("<div>No ednIDs left, nothing left to cache</div>");
            return;
          }
          ednID = ednIDs.shift();
          if (ednID) {
            updateStatus("<div>Calculating READ Word Locations for edition ("+ednID+")</div>");
            d = new Date();
            stime = d.getTime();
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: calcEdnWordLocationsURL+ednID,//caution dependency on conedition having basepath and dbName
                async: true,
                success: function (data, status, xhr) {
                  lastCachedID = ednID;
                  d = new Date();
                  etime = d.getTime();
                  sec = Math.round((etime-stime)/1000);
                  message = '<div class="success">Successfully calculated locations for edition edn:'+lastCachedID+" in "+sec+" seconds</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (calcState == "PausePending" || calcState == "Paused") {
                    calcState = "Paused";
                    $btnPause.val("Restart");
                    $btnWordLocations.val('Paused');
                    updateStatus("<div>Paused READ Location Calculation, next edition ("+ednIDs[0]+")</div>");
                  } else if (calcState == "Stopping") {
                    calcState = "Stopped";
                    $btnWordLocations.val('Calculate');
                    $btnCancel.val("Cancel");
                    updateStatus("<div>Stopped READ Location Calculation, last calculated edition ("+lastCachedID+")</div>");
                    return;
                  } else {
                    setTimeout(calcLocation,50);
                  }
                },
                error: function (xhr,status,error) {
                    // add record failed.
                  var message = '<div class="error">An error occurred while trying to calculate locations for edn: '+ednID +". Error: " + error+"</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (calcState == "PausePending" || calcState == "Paused") {
                    $btnPause.val("Restart");
                  }
                  calcState = "Stopped";
                  $btnWordLocations.val('Calculate');
                  $btnCancel.val("Cancel");
                  updateStatus("<div>Stopped READ Location Calculation, last calculated edition ("+lastCachedID+")</div>");
                }
            });
          }  
        }

        //Handle Calculation button click
        $btnWordLocations.unbind('click').bind('click', function (e) {
          var checkedItems = $editionListTree.jqxTree('getCheckedItems'), ednID;
          ednIDs = [];
          if (checkedItems.length == 0) {
            alert("No selected edition found so nothing to calc");
          } else if (calcState !== "Stopped") {
            alert("Calculation must be stopped before starting new calculation. Try pressing cancel.");
            return;
          } else {
            for (i in checkedItems) {
              ednID = checkedItems[i].value;
              if (!isNaN(ednID)) {
                ednIDs.push(ednID);
              }
            }
            $btnWordLocations.val('Calculating');
            calcState = "Calculating";
            calcLocation();
          }
        });

        //Handle Pause button click
        $btnPause.unbind('click').bind('click', function (e) {
          //Check button state
          if ($(this).val() == "Pause") {
            //In call to server so indicate pause wanted
            if (calcState == "Calculating") {
              calcState = "PausePending"
              $(this).val("PausePending");
            } else { // mark paused and change button state
              calcState = "Paused";
              $btnWordLocations.val('Paused');
              updateStatus("<div>Paused READ Location Calculation, next edition ("+ednIDs[0]+")</div>");
              $(this).val("Restart");
            }
          } else {
            //Change button to Pauseable state
            $(this).val("Pause");
            if (calcState == "Calculating") {
              return;
            } else {
              calcState = "Calculating";
              $btnWordLocations.val('Calculating');
              calcLocation();
            }
          }
        });

        //Handle Cancel button click
        $btnCancel.unbind('click').bind('click', function (e) {
          if (calcState == "Calculating" || calcState == "PausePending") {
            calcState = "Stopping"
            $(this).val("Stopping");
          } else { // mark paused and change button state
            calcState = "Stopped";
            updateStatus("<div>Stopped READ Location Calculation, last calculated edition ("+lastCachedID+")</div>");
            $btnWordLocations.val('Calculate');
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
            <input type="button" class="dlgButton" value="Calculate" style="margin-bottom: 5px;" id="btnWordLocations" />
            <input type="button" class="dlgButton" value="Pause" id="btnPause" />
            <input type="button" class="dlgButton" value="Cancel" id="btnCancel" />
        </div>
        <div id="editionListHdr"> Edition Calculation List</div>
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