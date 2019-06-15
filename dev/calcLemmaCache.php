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
* calc lemma caching
*
* creates a framework for selecting the lemma to refresh/calculate cached information
* it support opening with a parameter set that how the calculation will proceed.
*
* The caching app will bring up a check box tree of selected lemma or all lemma in the database 
* depending on how it is called
* 
* localhost:81/readDev/dev/calcViewerCache.php?db=gandhari&catID=1&lemIDs=1,2,3,4,5
* or
* localhost:81/readDev/dev/calcViewerCache.php?db=gandhari&catID=1
* 
* the glossary catID needs to be supplied in the current version
* the rest of the parameters for the site configuration of the READ will be used
* when the app calls lemmaLoader.php
* 
* the app has a pause and cancel button which are asynch and take effect on the next lemma cycle
* cancel - will stop the cache calculation and empty the lemma id list as if the app just started
* pause - will pause the cache calculation at the next lemma and can be restarted at teh paused lemma
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

  $catID = (isset($data['catID']) && $data['catID'])? $data['catID']: null;
  $catName = (isset($data['catName']) && $data['catName'])? $data['catName']: null;
  $lemIDs = null;
  $condition = "not lem_owner_id = 1";
  $groupLabel = "All lemmas from ".DBNAME." database";
  if ( isset($data['lemIDs'])) {
    $lemIDs = $data['lemIDs'];
    if (strpos($lemIDs,'-') !== false) {
      list($startID,$endID) = explode("-",$lemIDs);
      if ( $startID && is_numeric($startID) && $endID && is_numeric($endID) && $startID <= $endID) {
        $condition = "lem_id >= $startID and lem_id <= $endID and not lem_owner_id = 1";
        $groupLabel = "Selected lemmas from ".DBNAME." database";
      }
    } else if (strpos($lemIDs,',') !== false) {
      $lemIDs = explode(",",$lemIDs);
      if (is_numeric($lemIDs[0])) {
        $condition = "lem_id in (".join(",",$lemIDs).") and not lem_owner_id = 1";
        $groupLabel = "Selected lemmas from ".DBNAME." database";
      }
    } else if (is_numeric($lemIDs)) { //single number case
      $condition = "lem_id = $lemIDs and not lem_owner_id = 1";
      $groupLabel = "Selected lemma from ".DBNAME." database";
    }
  }
  $lemmas = new Lemmas($condition,"lem_id",null,null);
  if (!$lemmas || $lemmas->getCount() == 0 ) {
    //exit with error
  } else {
    $lemmaList = array();
    foreach($lemmas as $lemma) {
      $lemGID = $lemma->getGlobalID();
      $lemTag = str_replace(":","",$lemGID);
      $lemValue= $lemma->getValue();
      $lemmaInfo = array( "label" => $lemValue." ($lemGID)",
        "id" => $lemTag,
        "value" => $lemma->getID());
      array_push($lemmaList,$lemmaInfo);
    }
    $lemmaSelect = array(
      "label" => $groupLabel,
      "value" => "all",
      "id" => "alllem",
      "items" => $lemmaList,
      "checked" => true,
      "expanded" => true
    );
  }

  $calcLemmaBaseURL = SITE_BASE_PATH."/plugins/dev/php/lemmaLoader.php?db=".DBNAME.'&strJSON={"dictionary":"gd","phase":"cache","gd3id":"';
	$calcLemmaBaseURLEnding = '"}';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title>Calculate lemma Edition Caching</title>
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.energyblue.css" type="text/css" />
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
          lemmaSource = [<?=json_encode($lemmaSelect)?>],
          calcLemmaBaseURL ='<?=$calcLemmaBaseURL?>',
          calcLemmaBaseURLEnding ='<?=$calcLemmaBaseURLEnding?>';

      $(document).ready( function () {
        var lemIDs = [],
            lemID = null,
            lastCachedID = null,
            $lemmaListDiv = $('#lemmaListDiv'),
            $lemmaListHdr = $('#lemmaListHdr'),
            $lemmaListTree = $('#lemmaListTree'),
            $resultsDiv = $('#resultsDiv'),
            $statusDiv = $('#statusDiv'),
            $btnCalcLemmaCache = $('#btnCalcLemmaCache'),
            $btnPause = $('#btnPause'),
						$btnCancel = $('#btnCancel');
				DEBUG.log("gen","loading");
        $lemmaListTree.jqxTree({
             source: lemmaSource,
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
            docHtmlFragment = "<div>Calculating READ Lemma Cache</div>";
          }
          $statusDiv.html(docHtmlFragment);
        }
        
        function calcCaching() {
          lemID = null;
          if (cachingState == "Stopping") {
            cachingState = "Stopped";
            $btnCancel.val("Cancel");
            updateStatus("<div>Stopped READ Lemma Caching, last cached lemma ("+lemID+")</div>");
            return;
          } else if (cachingState == "Paused" || cachingState == "PausePending") {
            cachingState = "Paused";
            $btnPause.val("Restart");
            updateStatus("<div>Paused READ Lemma Caching, next lemma ("+lemIDs[0]+")</div>");
            return;
          } else if (cachingState == "Stopped") {
            return;
          } else if(!lemIDs || lemIDs.length == 0) {
            cachingState = "Stopped";
            updateStatus("<div>No lemIDs left, nothing left to cache</div>");
            return;
          }
          lemID = lemIDs.shift();
          if (lemID) {
            updateStatus("<div>Calculating Dictionary Cache for lemma ("+lemID+")</div>");
            d = new Date();
            stime = d.getTime();
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: calcLemmaBaseURL+lemID+calcLemmaBaseURLEnding,
                async: true,
                success: function (data, status, xhr) {
                  lastCachedID = lemID
                  d = new Date();
                  etime = d.getTime();
                  sec = Math.round((etime-stime)/1000);
                  message = '<div class="success">Successfully cache Lemma content for lemma lem:'+lastCachedID+" in "+sec+" seconds</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    cachingState = "Paused";
                    $btnPause.val("Restart");
                    $btnCalcLemmaCache.val('Paused');
                    updateStatus("<div>Paused READ Lemma Caching, next lemma ("+lemIDs[0]+")</div>");
                  } else if (cachingState == "Stopping") {
                    cachingState = "Stopped";
                    $btnCalcLemmaCache.val('Calculate');
                    $btnCancel.val("Cancel");
                    updateStatus("<div>Stopped READ Lemma Caching, last cached lemma ("+lastCachedID+")</div>");
                    return;
                  } else {
                    setTimeout(calcCaching,50);
                  }
                },
                error: function (xhr,status,error) {
                    // add record failed.
                  var message = '<div class="error">An error occurred while trying to calculate cache for lem: '+lemID +". Error: " + error+"</div>";
                  updateStatus(message);
                  addResultsMessage(message);
                  if (cachingState == "PausePending" || cachingState == "Paused") {
                    $btnPause.val("Restart");
                  }
                  cachingState = "Stopped";
                  $btnCalcLemmaCache.val('Calculate');
                  $btnCancel.val("Cancel");
                  updateStatus("<div>Stopped READ Lemma Caching, last cached lemma ("+lastCachedID+")</div>");
                }
            });
          }  
        }

        //Handle Cache button click
        $btnCalcLemmaCache.unbind('click').bind('click', function (e) {
          var checkedItems = $lemmaListTree.jqxTree('getCheckedItems'), lemID;
          lemIDs = [];
          if (checkedItems.length == 0) {
            alert("No selected lemma found so nothing to cache");
          } else if (cachingState !== "Stopped") {
            alert("Caching must be stopped before starting cache. Try pressing cancel.");
            return;
          } else {
            for (i in checkedItems) {
              lemID = checkedItems[i].value;
              if (!isNaN(lemID)) {
                lemIDs.push(lemID);
              }
            }
            $btnCalcLemmaCache.val('Calculating');
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
              $btnCalcLemmaCache.val('Paused');
              updateStatus("<div>Paused READ Lemma Caching, next lemma ("+lemIDs[0]+")</div>");
              $(this).val("Restart");
            }
          } else {
            //Change button to Pauseable state
            $(this).val("Pause");
            if (cachingState == "Calculating") {
              return;
            } else {
              cachingState = "Calculating";
              $btnCalcLemmaCache.val('Calculating');
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
            updateStatus("<div>Stopped READ Lemma Caching, last cached lemma ("+lastCachedID+")</div>");
            $btnCalcLemmaCache.val('Calculate');
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
            <input type="button" class="dlgButton" value="Calculate" style="margin-bottom: 5px;" id="btnCalcLemmaCache" />
            <input type="button" class="dlgButton" value="Pause" id="btnPause" />
            <input type="button" class="dlgButton" value="Cancel" id="btnCancel" />
        </div>
        <div id="lemmaListHdr"> Lemma Caching List</div>
      </div>
      <div style="display:table-row">
        <div id="lemmaListDiv" style="display:table-cell">
          <div id="lemmaListTree"></div>
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