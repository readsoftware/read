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
* userUI
*
* creates a framework for the user interface including a layout manager and data manager
* it support opening with a parameter set that defines the layout and data needed to reproduce
* the exact view currently shown (perhaps minus any selection).
* when no parameters are passed it creates the default layout with no data and a single empty view.
*/

  require_once (dirname(__FILE__) . '/common/php/sessionStartUp.php');//initialize the session
  require_once (dirname(__FILE__) . '/common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/model/entities/EntityFactory.php');//get user access control
  $dbMgr = new DBManager();
  $username = getUserName();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title><?=defined("PROJECT_TITLE")?PROJECT_TITLE:"Set PROJECT_TITLE in config.php"?></title>
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.energyblue.css" type="text/css" />
    <link rel="stylesheet" href="./common/css/kanishka.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/imageViewer.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/editionVE.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/wordlistVE.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/paleoVE.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/syntaxVE.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/lemmaVE.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/searchVE.css" type="text/css" />
    <link rel="stylesheet" href="./editors/css/propertyVE.css" type="text/css" />
    <script type="text/javascript" src="https://static.sketchfab.com/api/sketchfab-viewer-1.8.2.js"></script>
    <script src="/jquery/jquery-1.11.1.min.js"></script>
    <script src="/d3/d3.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcore.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtouch.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdata.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtabs.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownbutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttons.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttongroup.js"></script>
    <script src="/jqwidget/jqwidgets/jqxradiobutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxscrollbar.js"></script>
    <script src="/jqwidget/jqwidgets/jqxexpander.js"></script>
    <script src="/jqwidget/jqwidgets/jqxnavigationbar.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdragdrop.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.pager.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.selection.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.filter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.sort.js"></script>
    <script src="/jqwidget/jqwidgets/jqxmenu.js"></script>
    <script src="/jqwidget/jqwidgets/jqxwindow.js"></script>
    <script src="/jqwidget/jqwidgets/jqxsplitter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownlist.js"></script>
    <script src="/jqwidget/jqwidgets/jqxlistbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcheckbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcombobox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxvalidator.js"></script>
    <script src="/jqwidget/jqwidgets/jqxpanel.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtree.js"></script>
    <script type="text/javascript" src="./common/php/getEntityInfo.php?db=<?=DBNAME?>"></script>
    <script type="text/javascript" src="./services/getTagInfo.php?db=<?=DBNAME?>"></script>
    <script type="text/javascript">

      var sktSort = ('<?=defined("USESKTSORT")?USESKTSORT:"0"?>' == "0" || !'<?=defined("USESKTSORT")?USESKTSORT:"0"?>')?false:true,
          enableCatalogResources = ('<?=defined("ENABLECATALOGRESOURCE")?ENABLECATALOGRESOURCE:"0"?>' == "0" || !'<?=ENABLECATALOGRESOURCE?>')?false:true,
          maxUploadSize = parseInt(<?=defined("MAX_UPLOAD_SIZE")?MAX_UPLOAD_SIZE:7000000?>),
          linkToSyllablePattern = '<?=defined("LINKSYLPATTERN")?LINKSYLPATTERN:""?>',
          progressInputName='<?php echo ini_get("session.upload_progress.name"); ?>',
          dbName = '<?=DBNAME?>', basepath="<?=SITE_BASE_PATH?>",
          imgbasepath="<?=IMAGE_SITE_BASE_URL?>",
          EDITORS = EDITORS || {};
        if (!EDITORS.config){
          EDITORS.config = {};
        }
        EDITORS.config.showLemmaVEPhoneticUI = ('<?=defined("SHOWLEMMAPHONETIC")?SHOWLEMMAPHONETIC:"0"?>' == "0" || !'<?=defined("SHOWLEMMAPHONETIC")?SHOWLEMMAPHONETIC:"0"?>')?false:true;
        EDITORS.config.showLemmaVEPhonologicalUI = ('<?=defined("SHOWLEMMAPHONOLOGY")?SHOWLEMMAPHONOLOGY:""?>' == "" || !'<?=defined("SHOWLEMMAPHONOLOGY")?SHOWLEMMAPHONOLOGY:""?>')?false:'<?=SHOWLEMMAPHONOLOGY?>';
        EDITORS.config.showLemmaDeclensionUI = ('<?=defined("SHOWLEMMADECLENSION")?SHOWLEMMADECLENSION:"0"?>' == "0" || !'<?=defined("SHOWLEMMADECLENSION")?SHOWLEMMADECLENSION:"0"?>')?false:true;
        EDITORS.config.declensionListName = '<?=defined("DECLENSIONLIST")?DECLENSIONLIST:"OIADeclension"?>';
        EDITORS.config.attestedLocSep = '<?=defined("CKNLINENUMSEPARATOR")?CKNLINENUMSEPARATOR:""?>';
        EDITORS.config.editToolsOpenOnStart = ('<?=defined("EDITTOOLSOPENONSTART")?EDITTOOLSOPENONSTART:1?>' == "1") ;
        EDITORS.config.viewToolsOpenOnStart = ('<?=defined("VIEWTOOLSOPENONSTART")?VIEWTOOLSOPENONSTART:1?>' == "1") ;
        EDITORS.config.layoutToolsOpenOnStart = ('<?=defined("LAYOUTTOOLSOPENONSTART")?LAYOUTTOOLSOPENONSTART:1?>' == "1") ;
        EDITORS.config.toolSidebarOpenOnStart = ('<?=defined("TOOLSIDEBAROPENONSTART")?TOOLSIDEBAROPENONSTART:1?>' == "1") ;
    </script>
    <script src="./editors/js/utility.js"></script>
    <script src="./editors/js/debug.js"></script>
    <script src="./editors/js/dataManager.js"></script>
    <script src="./editors/js/layoutManager.js"></script>
    <script src="./editors/js/propertyManager.js"></script>
    <script src="./editors/js/userEditor.js"></script>
    <script src="./editors/js/searchVE.js"></script>
    <script src="./editors/js/imageVE.js"></script>
    <script src="./editors/js/syllableEditor.js"></script>
    <script src="./editors/js/tcmEditor.js"></script>
    <script src="./editors/js/propertyEditor.js"></script>
    <script src="./editors/js/tabbedPropVE.js"></script>
    <script src="./editors/js/editionVE.js"></script>
    <script src="./editors/js/sequenceVE.js"></script>
    <script src="./editors/js/translationV.js"></script>
    <script src="./editors/js/wordlistVE.js"></script>
    <script src="./editors/js/paleoVE.js"></script>
    <script src="./editors/js/syntaxVE.js"></script>
    <script src="./editors/js/frameV.js"></script>
    <script src="./editors/js/lemmaVE.js"></script>
    <script src="./editors/js/annoVE.js"></script>
    <script src="./editors/js/tagVE.js"></script>
    <script src="./editors/js/imgVE.js"></script>
    <script src="./editors/js/attrVE.js"></script>
    <script src="./editors/js/entPropVE.js"></script>
    <script src="./editors/js/threeDVE.js"></script>
    <script type="text/javascript">
      var navPanelDiv, contDiv, layoutManager,dataManager;
      $(document).ready( function () {
        if (typeof seqTypeInfo == "undefined") {
          seqTypeInfo = null;
        }
        if (typeof linkTypeInfo == "undefined") {
          linkTypeInfo = null;
        }
        if (typeof termInfo == "undefined") {
          termInfo = null;
        }
        if (typeof basepath == "undefined") {
          basepath = null;
        }
        if (typeof entityInfo == "undefined") {
          entityInfo = null;
        }
        navPanelDiv = $('#frameNavPanel');
        contDiv = $('#frameContentPanel');
        dataManager = new MANAGERS.DataManager({ dbname: dbName,
                                                 seqTypes: ((seqTypeInfo && seqTypeInfo['types']) ?seqTypeInfo['types']:""),
                                                 seqTypeTagToLabel: ((seqTypeInfo && seqTypeInfo['seqTypeTagToLabel'])?seqTypeInfo['seqTypeTagToLabel']:""),
                                                 seqTypeTagToList: ((seqTypeInfo && seqTypeInfo['seqTypeTagToList'])?seqTypeInfo['seqTypeTagToList']:""),
                                                 linkTypes: ((linkTypeInfo && linkTypeInfo['types']) ?linkTypeInfo['types']:""),
                                                 sfLinkTypes: ((linkTypeInfo && linkTypeInfo['sftypes']) ?linkTypeInfo['sftypes']:""),
                                                 linkTypeTagToLabel: ((linkTypeInfo && linkTypeInfo['linkTypeTagToLabel'])?linkTypeInfo['linkTypeTagToLabel']:""),
                                                 linkTypeTagToList: ((linkTypeInfo && linkTypeInfo['linkTypeTagToList'])?linkTypeInfo['linkTypeTagToList']:""),
                                                 tags: ((typeof tagInfo == "undefined")?"":tagInfo),
                                                 entTagToLabel: ((typeof entTagToLabel == "undefined")?"":entTagToLabel),
                                                 entTagToPath: ((typeof entTagToPath == "undefined")?"":entTagToPath),
                                                 tagIDToAnoID: ((typeof tagIDToAnoID == "undefined")?"":tagIDToAnoID),
                                                 basepath: basepath,
                                                 entityInfo: entityInfo,
                                                 termInfo: termInfo,
                                                 username: "<?= @$username?$username:"unknown"?>" });
        layoutManager = new MANAGERS.LayoutManager({ navPanel: navPanelDiv,
                                                     contentDiv: contDiv,
                                                     dataMgr: dataManager,
                                                     catalogIdURL: "<?=defined("NEW_CATALOG_ID_URL")?NEW_CATALOG_ID_URL:''?>",
                                                     projTitle: "<?=defined("PROJECT_TITLE")?PROJECT_TITLE:"Set PROJECT_TITLE in config.php"?>",
                                                     username: "<?= @$username?$username:"unknown"?>" });
        dataManager.layoutMgr = layoutManager;
      });
    </script>
  </head>
<body>
  <div id="frameContentPanel"></div>
  <div id="frameNavPanel"></div>
  <div id="layoutBtnBar"></div>
  <div id="editionWizard">
    <div id="wizardHeader">
        <span id="headerSpan" style="float: left">New Edition Wizard</span>
    </div>
    <div id="wizardContent">
      <div class="wzInputBox">
        <span id="lblTxtInv" class="wzInputLabel">Text Inv. :</span>
        <input type="text" class="wzInput" id="txtInv" placeholder="Enter text inv. no."/>
      </div>
      <div class="wzInputBox">
        <span id="lblTitle" class="wzInputLabel">Edition Title :</span>
        <input type="text" class="wzInput" id="ednTitle"  placeholder="Enter edition title"/>
      </div>
      <div class="wzImportOptionsBox">
        <span class="wzOptionsGroupLabel">import option :</span>
        <div id="importOptions" >
          <div id="freetextCheckBox" class="wzCheckBox"> import as individual freetext lines</div>
        </div>
      </div>
      <div class="wzTextareaBox">
        <div id="lblTrans" class="wzInputLabel">Transcription :</div>
        <textarea class="wzInputArea" id="transcript" placeholder="Enter transcription"></textarea>
      </div>
      <div  class="wzButtonBox">
        <div style="float: right">
          <input type="button" class="wzButton" value="Import Lines" id="btnImportFreeTextLines" />
          <input type="button" class="wzButton" value="Validate Multiline" id="btnEditionValidate" />
          <input type="button" class="wzButton" value="Commit Multiline" id="btnEditionCommit" />
          <input type="button" class="wzButton" value="Close" id="btnEditionWizardCancel" />
        </div>
      </div>
      <div class="wzTextareaBox">
        <div id="importResultsHeader" class="wzInputLabel">Results :</div>
        <div id="importResultsContent"  class="wzResultArea" ></div>
      </div>
    </div>
  </div>
</body>
</html>
