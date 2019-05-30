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
* managers layoutManager object
*
* handles handle initialisation of the landing page, view create and switching,
*  linking components, and messaging
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Managers
*/

var MANAGERS = MANAGERS || {};


/**
* Constructor for Layout Manager Object
*
* @type Object
*
* @param layoutCfg is a JSON object with the following possible properties
*  "navPanel" reference to the div element for display of navigation panel
*  "dataMgr" reference to the data manager/model manager
*  "projTitle" string for projTitle installation
*  "contentDiv" reference to the div element for layout elements
*  "username" current user name
*
* @returns {LayoutManager}
*/

MANAGERS.LayoutManager =  function(layoutCfg) {
  this.config = layoutCfg;
  this.navPanel = layoutCfg['navPanel'] ? layoutCfg['navPanel']:null;
  this.dataMgr = layoutCfg['dataMgr'] ? layoutCfg['dataMgr']:null;
  if (!this.dataMgr) {
    this.dataMgr = new MANAGERS.DataManager({ dbname: dbName?dbName:'',
                                              username: this.username?this.username:"Guest"});
  }
  this.projTitle = layoutCfg['projTitle'] ? layoutCfg['projTitle']:"No Title sent to LayoutMgr";
  this.contentDiv = layoutCfg['contentDiv'] ? layoutCfg['contentDiv']:null;
  this.username = layoutCfg['username'] && layoutCfg['username'] != "unknown" ? layoutCfg['username']:null;
  this.lkViewTB = {};
  this.lkEditTB = {};
  this.editors = {};
  this.editionObjLevel = 'syllable';
  this.maxAttemps = 5; //max. number of times to try loading a resource
  this.init();
  return this;
};

/**
* Prototype for Layout Manager Object
*/
MANAGERS.LayoutManager.prototype = {

/**
* Initialiser for Layout Manager Object
*/

  init: function() {
    DEBUG.traceEntry("layoutMgr.init","");
    var layoutMgr = this, startPage = "landing", startFocus = null, focus, entTags, entTag, txtID, searchVE;
    this.params = UTILITY.parseParams();//get URI parameters
    this.createUserNavBar();
    this.loadingPage = $('<div id="loadingPage"><h1>Loading ....</h1></div>');
    this.contentDiv.html("");
    this.contentDiv.append(this.loadingPage);
    this.currentPage = "loadingPage";
    $(document.body).addClass(this.currentPage);
    this.createLandingPage();
    this.createSearchCtrlPanel();
    this.createTextEditPanels();
    this.createEditorToolPanels();
    this.createLayoutTools();
    //parse params for start up
    if (this.params['f']) { //focus parameter
      focus = this.params['f'].split(",");
      startPage = focus[0];
      if (focus.length == 2) {
        startFocus = focus[1];
      }
    } else if (this.params['l']) {//there is a search so switch to search page
      startPage = "textEdit";
    } else if (this.params['q']) {//there is a search so switch to search page
      startPage = "search";
    }
    this.switchContentPage(startPage,startFocus);
    if (this.params['l']) { //layout parameter
      entTags = this.params['l'].split(',');
      if (entTags[0][0] == 'H' || entTags[0][0] == 'V') {//layout tag
        entTags.shift();
      }
      if (entTags.length > 0) {
        for (var i=0; i < entTags.length; i++) {
          entTag = entTags[i];
          this.loadPaneContent(entTag,"pane" + (i+1));
        }
        this.focusHandler(null,startFocus);
      } else if (this.editors['searchVE']) {
        searchVE = this.editors['searchVE'];
        if (searchVE.isLoaded()){
          //attempt to load current cursor text default resources
          this.loadTextDefaults(searchVE.getCursorTextID(),startFocus);
        } else {
          setTimeout(function(){
                        layoutMgr.loadTextDefaults(searchVE.getCursorTextID(),startFocus);
                      },1500);
        }
      }
    }
    this.createNewEditionWizard();// placed here due to unknown interaction bug with toolbar, leave after load
    DEBUG.traceExit("layoutMgr.init","");
  },

  getEditionObjectLevel: function(){
    return this.editionObjLevel;
  },

  setEditionObjectLevel: function(level){
    this.editionObjLevel = level;
  },

  refreshCursor: function(){
    if (this.editors.searchVE) {
      this.editors.searchVE.updateCursorInfoBar();
    }
  },


  refreshCatalogResources: function(){
    this.editors.searchVE.updateCatalogInfoBar();
  },


/**
* refresh search page
*
* @param pageID
*/

  refresh: function(pageID){
    this.dataMgr.flushLocalCache();
    this.editors.searchVE.reloadSearch(pageID);
    this.pushState();
  },


/**
* update browser history with calculated url
*
*/

  pushState: function(){
    DEBUG.traceEntry("layoutMgr.pushState","");
    var URL = this.dataMgr.basepath + "/?db=" + this.dataMgr.dbName, state={},
        searchVE = this.editors['searchVE'], layoutID, edID, edTags = {},maxIndex=0, i,
        focus = this.focusPaneID != 'searchVE'?this.focusPaneID:null;
        pageID = this.currentPage.replace("Page","");
    if ( this.curLayout.children('.pane1').get(0)) {
      layoutID = this.curLayout.children('.pane1').get(0).id.replace("pane1-","");
    } else {
      layoutID = this.curLayout.children('.pane3').get(0).id.replace("pane3-","");
    }
    if (searchVE && searchVE.searchInput.val()) {
      URL += "&q=" + searchVE.searchInput.val().trim();
      state['search'] = searchVE.searchInput.val().trim();
    }
    if (pageID) {
      URL += "&f=" + pageID;
      state['pageID'] = pageID;
      if (pageID != "search" && focus) {
        URL += "," + focus;
        state['focus'] = focus;
      }
    }
    for (edID in this.editors) {
      if (edID == 'searchVE') {
        continue;
      }
      if (this.editors[edID].config && this.editors[edID].config.entGID){
        i = edID.replace("pane","");
        edTags[i] = this.editors[edID].config.entGID.replace(":","");
        maxIndex = Math.max(maxIndex,i);
      }
    }
    if (maxIndex) {
      URL += "&l=";
      if (layoutID) {
        URL += layoutID;
        state['layout']=layoutID;
      }
      for (i=1; i <= maxIndex; i++) {
        if (edTags[i]) {
          if (URL.substr(-1) != ","){
            URL += ",";
          }
          URL += edTags[i];
          state['pane'+i] = edTags[i];
        } else {
          URL += ",";
        }
      }
    }
    DEBUG.log("gen",window.location.href.substr(0,URL.length));
    if (window.location.href.substr(0,URL.length) != URL ||
        maxIndex && window.location.href.length > URL.length) {
      window.history.pushState(state,'',URL);
      DEBUG.log("gen","pushHistory - "+URL);
    } else {
      DEBUG.log("gen","URL State change - "+URL);
    }
    DEBUG.traceExit("layoutMgr.pushState","");
  },


/**
* create user navigation bar
*/

  createUserNavBar: function(){
    DEBUG.traceEntry("layoutMgr.createUserNavBar","");
    var layoutMgr = this,
        userHdrDiv = $('<div id="userHeader"/>'),
        userNavBar = $('<div id="navBar"/>'),
        userNavHdr = $('<div id="navHdr"/>'),
        userUIPanel = $('<div id="userUIPanel"/>'),
        navBarExpHdr;
    userNavHdr.append(userHdrDiv);
    userNavBar.append(userNavHdr).append(userUIPanel);
    this.userNavBar = userNavBar.jqxExpander({expanded:false,
                                              expandAnimationDuration:50,
                                              collapseAnimationDuration:50});
    this.userNavBar.css('height','auto');
    this.userNavBar.unbind('expanding').bind('expanding', function(e) {
                            layoutMgr.userNavBar.css('height','100%');
                            $('#userUIPanel',layoutMgr.userNavBar).css('height','100%');
                      });
    this.userNavBar.unbind('collapsing').bind('collapsing', function(e) {
                            layoutMgr.userNavBar.css('height','auto');
                            $('#userUIPanel',layoutMgr.userNavBar).css('height','auto');
                      });
    navBarExpHdr=this.userNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
    this.navPanel.append(this.userNavBar);
    this.userVE = new EDITORS.UserVE({ userVEDiv: userUIPanel,
                                       userHdrDiv: userHdrDiv,
                                       layoutMgr: layoutMgr,
                                       dataMgr: this.dataMgr,
                                       username: this.username });
    this.hideCatBtnUI()
    DEBUG.traceExit("layoutMgr.createUserNavBar","");
  },

  closeUserPanel: function(){
    this.userNavBar.jqxExpander("expanded",false);
  },


/**
* create search control panel
*/

  createSearchCtrlPanel: function(){
    var layoutMgr = this;
    this.searchNavBar = $('<div id="searchNavBar"/>');
    this.searchPage = $('<div id="searchPage"/>');
    this.searchNavBar.insertAfter(this.userNavBar);
    this.contentDiv.append(this.searchPage);
    this.editors['searchVE'] = new EDITORS.SearchVE({ searchNavBar: this.searchNavBar,
                                           editDiv: this.searchPage,
                                           layoutMgr: layoutMgr,
                                           dataMgr: this.dataMgr,
                                           search: (this.params && this.params['q'])? this.params['q']:"" });
  },


/**
* create Editor Tool Panel
*/

  createEditorToolPanels: function(){
    DEBUG.traceEntry("layoutMgr.createEditorToolPanels","");
    var layoutMgr = this,
        viewToolNavBar = $('<div id="viewToolNavBar" class="toolNavBar"/>'),
        viewToolNavHdr = $('<div id="viewToolNavHdr"><div class="toolPanelLabel">View</div></div>'),
        editToolNavBar = $('<div id="editToolNavBar" class="toolNavBar"/>'),
        editToolNavHdr = $('<div id="editToolNavHdr"><div class="toolPanelLabel">Edit</div></div>'),
        layoutToolNavBar = $('<div id="layoutToolNavBar" class="toolNavBar"/>'),
        layoutToolNavHdr = $('<div id="layoutToolNavHdr"><div class="toolPanelLabel">Layout</div></div>');
    this.viewToolBarPanel = $('<div id="viewToolBarPanel" class="toolPanel"/>');
    this.editToolBarPanel = $('<div id="editToolBarPanel" class="toolPanel"/>');
    this.layoutToolBarPanel = $('<div id="layoutToolBarPanel" class="toolPanel"/>');
    //attach header and panel to navbar
    viewToolNavBar.append(viewToolNavHdr).append( this.viewToolBarPanel);
    this.viewToolNavBar = viewToolNavBar.jqxExpander({expanded:true,
                                              expandAnimationDuration:50,
                                              collapseAnimationDuration:50});
    navBarExpHdr=this.viewToolNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
    //attach editheader and editpanel to editNavBar
    editToolNavBar.append(editToolNavHdr).append( this.editToolBarPanel);
    this.editToolNavBar = editToolNavBar.jqxExpander({expanded:true,
                                              expandAnimationDuration:50,
                                              collapseAnimationDuration:50});
    this.editToolBarHdr = this.editToolNavBar.children().first();
    this.editToolBarHdr.addClass('navExpandableHeader');
    layoutToolNavBar.append(layoutToolNavHdr).append( this.layoutToolBarPanel);
    this.layoutToolNavBar = layoutToolNavBar.jqxExpander({expanded:true,
                                              expandAnimationDuration:50,
                                              collapseAnimationDuration:50});
    navBarExpHdr=this.layoutToolNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
//    navBarExpHdr.css('background-color','white');
//    navBarExpHdr.css('border','none');
    this.editToolNavBar.insertAfter(this.searchNavBar);
    this.viewToolNavBar.insertAfter(this.editToolNavBar);
    this.layoutToolNavBar.insertAfter(this.viewToolNavBar);
//    this.createLayoutTools();
    this.editors.searchVE.createStaticToolbar();
    DEBUG.traceExit("layoutMgr.createEditorToolPanels","");
  },


/**
* create Layout Tools
*
*/

  createLayoutTools: function(){
    DEBUG.traceEntry("layoutMgr.createLayoutTools","");
    var layoutMgr = this;
    this.layoutBtnDiv = $('<div id="layoutBtnDiv" class="toolbuttondiv">' +
                            '<button class="toolbutton" id="layoutBtn'+
                              '" title="Select panel layout">Change</button>'+
                            '<div class="toolbuttonlabel">Panel Layout</div>'+
                           '</div>');
    this.layoutBtnBar = this.createLayoutButtonBar();
    this.layoutToolBarPanel.append(this.layoutBtnDiv);
    $('#layoutBtn',this.layoutBtnDiv).unbind('click')
                                  .bind('click',function(e) {
                                    layoutMgr.layoutBtnBar.toggle("showLayoutUI");
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
/*    this.columnBtnDiv = $('<div id="columnBtnDiv" class="toolbuttondiv">' +
                            '<button class="toolbutton" id="columnsBtn'+
                              '" title="Select columns to display on grid">Select</button>'+
                            '<div class="toolbuttonlabel">Columns</div>'+
                           '</div>');
//    this.columnDlgBar = this.createColumnDialogBar();
    this.layoutToolBarPanel.append(this.columnBtnDiv);
    $('#columnsBtn',this.columnBtnDiv).unbind('click')
                                  .bind('click',function(e) {
                                    if (!$(this).hasClass('showUI')) {
                                      alert("column chooser is underconstruction");
                                    }
//                                    layoutMgr.columnDlgBar.toggle("showLayoutUI");
                                    $(this).toggleClass("showUI");
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
*/

    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="propertiesBtn'+
                              '" title="Show/Hide property panels">&#x25E8;</button>'+
                            '<div class="toolbuttonlabel">Properties</div>'+
                           '</div>');
    $('#propertiesBtn',this.propertyBtnDiv).unbind('click')
                                        .bind('click',function(e) {
                                           var paneID, editor;
                                           if (!layoutMgr.propVisible) {
//                                             $(this).html("Hide");
                                             $(this).addClass("showUI");
                                             for (paneID in layoutMgr.editors) {
                                               editor = layoutMgr.editors[paneID];
                                               if (editor.displayProperties &&
                                                   typeof editor.displayProperties == 'function') {
                                                 editor.displayProperties(true);
                                               }
                                             }
                                             layoutMgr.propVisible = true;
                                           } else {
//                                             $(this).html("Show");
                                             $(this).removeClass("showUI");
                                             for (paneID in layoutMgr.editors) {
                                               editor = layoutMgr.editors[paneID];
                                               if (editor.displayProperties &&
                                                   typeof editor.displayProperties == 'function') {
                                                 editor.displayProperties(false);
                                               }
                                             }
                                             layoutMgr.propVisible = false;
                                           }
                                         });
//    this.layoutToolBarPanel.append(this.propertyBtnDiv);
    this.synchScrollBtnDiv = $('<div id="synchScrollBtnDiv" class="toolbuttondiv">' +
                            '<button class="toolbutton" id="synchScrollBtn'+
                              '" title="Turn on/off synchronize scrolling">Off</button>'+
                            '<div class="toolbuttonlabel">Sync Scroll</div>'+
                           '</div>');
    this.layoutToolBarPanel.append(this.synchScrollBtnDiv);
    this.synchScrollBtn = $('#synchScrollBtn',this.synchScrollBtnDiv);
    this.synchScrollBtn.unbind('click').bind('click',function(e) {
                                    $('body').toggleClass("synchScroll");
                                    $(this).html($('body').hasClass("synchScroll")?"On":"Off");
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
//    this.layoutToolBarPanel.append(this.layoutBtnBar);
    DEBUG.traceExit("layoutMgr.createLayoutTools","");
  },


/**
* register view tool bar
*
* @param string paneID Identifying the pane of the editor registering
* @param node tbView View tool bar element for the given editor of a pane
*/

  registerViewToolbar: function(paneID, tbView){
    DEBUG.traceEntry("layoutMgr.registerViewToolbar","");
    //if toolbar lookup is occupied remove toolbar from panel
    if (this.lkViewTB[paneID]) {
      $('.'+paneID+'ViewTB',this.viewToolBarPanel).remove();
      delete this.lkViewTB[paneID];
    }
    //add paneIDViewTB class to tbView
    tbView.addClass(paneID+'ViewTB');
    //add to panel and store in lookup
    this.viewToolBarPanel.append(tbView);
    this.lkViewTB[paneID] = tbView;
    DEBUG.traceExit("layoutMgr.registerViewToolbar","");
  },


/**
* register edit tool bar
*
* @param string paneID Identifying the pane of the editor registering
* @param node tbEdit Edit tool bar element for the given editor of a pane
*/

  registerEditToolbar: function(paneID, tbEdit){
    DEBUG.traceEntry("layoutMgr.registerEditToolbar","");
    //if toolbar lookup is occupied remove toolbar from panel
    if (this.lkEditTB[paneID]) {
      $('.'+paneID+'EditTB',this.editToolBarPanel).remove();
      delete this.lkEditTB[paneID];
    }
    //add paneIDEditTB class to tbEdit
    tbEdit.addClass(paneID+'EditTB');
    //add to panel and store in lookup
    this.editToolBarPanel.append(tbEdit);
    this.lkEditTB[paneID] = tbEdit;
    DEBUG.traceExit("layoutMgr.registerEditToolbar","");
  },


/**
* create text edit layout panels
*
*/

  createTextEditPanels: function(){
    DEBUG.traceEntry("layoutMgr.createTextEditPanels","");
    var layoutMgr = this;
    this.textEditPage = $('<div id="textEditPage"/>');
    this.contentDiv.append(this.textEditPage);
    this.editPlaceholderHTML = '<div class="editPlaceholder">Drag text resource to load editor.</div>';
    this.layoutV12 = $('<div id="v1_2Splitter">' +
                         '<div id="pane1-V12" class="editContainer pane1">' +
                           this.editPlaceholderHTML +
                         '</div>' +
                         '<div id="pane2-V12" class="editContainer pane2">' +
                           this.editPlaceholderHTML +
                         '</div>' +
                       '</div>');
    this.layoutH12 = $('<div id="h1_2Splitter">' +
                         '<div id="pane1-H12" class="editContainer pane1">' +
                           this.editPlaceholderHTML +
                         '</div>' +
                         '<div id="pane2-H12" class="editContainer pane2">' +
                           this.editPlaceholderHTML +
                         '</div>' +
                       '</div>');
    this.layoutH123 = $('<div id="h1_23Splitter">' +
                          '<div id="pane1-H123" class="editContainer pane1">' +
                            this.editPlaceholderHTML +
                          '</div>' +
                          '<div>' +
                            '<div id="h2_3Splitter">' +
                              '<div id="pane2-H123" class="editContainer pane2">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                              '<div id="pane3-H123" class="editContainer pane3">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                            '</div>' +
                          '</div>' +
                        '</div>');
    this.layoutV12H3 = $('<div id="v12_h3Splitter">' +
                          '<div>' +
                            '<div id="v1_v2Splitter">' +
                              '<div id="pane1-V12H3" class="editContainer pane1">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                              '<div id="pane2-V12H3" class="editContainer pane2">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                            '</div>' +
                          '</div>' +
                          '<div id="pane3-V12H3" class="editContainer pane3">' +
                            this.editPlaceholderHTML +
                          '</div>' +
                        '</div>');
    this.layoutH1V23 = $('<div id="h1_v23Splitter">' +
                          '<div id="pane1-H1V23" class="editContainer pane1">' +
                            this.editPlaceholderHTML +
                          '</div>' +
                          '<div>' +
                            '<div id="v2_v3Splitter">' +
                              '<div id="pane2-H1V23" class="editContainer pane2">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                              '<div id="pane3-H1V23" class="editContainer pane3">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                            '</div>' +
                          '</div>' +
                        '</div>');
    this.layoutH12V3 = $('<div id="h12_v3Splitter">' +
                          '<div>' +
                            '<div id="h1_h2Splitter">' +
                              '<div id="pane1-H12V3" class="editContainer pane1">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                              '<div id="pane2-H12V3" class="editContainer pane2">' +
                                this.editPlaceholderHTML +
                              '</div>' +
                            '</div>' +
                          '</div>' +
                          '<div id="pane3-H12V3" class="editContainer pane3">' +
                            this.editPlaceholderHTML +
                          '</div>' +
                        '</div>');
    this.textEditPage.append(this.layoutV12);
    this.textEditPage.append(this.layoutH12);
    this.textEditPage.append(this.layoutH123);
    this.textEditPage.append(this.layoutV12H3);
    this.textEditPage.append(this.layoutH1V23);
    this.textEditPage.append(this.layoutH12V3);
    if (this.params['l'] && (this.params['l'][0] == 'H' || this.params['l'][0] == 'V')) {
      switch (this.params['l'].split(",")[0]) {
        case "V12":
          this.curLayout = this.layoutV12;
          break;
        case "H123":
          this.curLayout = this.layoutH123;
          break;
        case "V12H3":
          this.curLayout = this.layoutV12H3;
          break;
        case "H1V23":
          this.curLayout = this.layoutH1V23;
          break;
        case "H12V3":
          this.curLayout = this.layoutH12V3;
          break;
        case "H12":
        default:
          this.curLayout = this.layoutH12;
      }
    } else {
      this.curLayout = this.layoutH12;
    }
    this.curLayout.addClass('showUI');
    this.layoutH12.jqxSplitter({ orientation: 'horizontal',width: '100%',height: '100%',
                                 panels: [{ size: '50%', collapsible: false },//horizontal pane 1
                                          { size: '50%', collapsible: true}] });//horizontal pane 2
    this.layoutV12.jqxSplitter({ orientation: 'vertical',width: '100%',height: '100%',
                                 panels: [{ size: '50%', collapsible: false },//left vertical pane 1
                                          { size: '50%', collapsible: true}] });//right vertical pane 2
    this.layoutH123.jqxSplitter({ orientation: 'horizontal',width: '100%',height: '100%',
                                  panels: [{ size: '35%', collapsible: false },//upper horizontal pane 1
                                           { size: '65%',  collapsible: true}] });//lower horizontal panes 2 & 3
    $('#h2_3Splitter',this.layoutH123)
                   .jqxSplitter({ orientation: 'horizontal',
                                  panels: [{ size: '50%', collapsible: false },//middle horizontal pane 2
                                           { size: '50%',  collapsible: true}] });//lower horizontal pane 3
    this.layoutV12H3.jqxSplitter({ orientation: 'horizontal',width: '100%',height: '100%',
                                  panels: [{ size: '65%', collapsible: false },//upper vertical panes 1 & 2
                                           { size: '35%',  collapsible: true}] });//lower horizontal pane 3
    $('#v1_v2Splitter',this.layoutV12H3)
                   .jqxSplitter({ orientation: 'vertical',
                                  panels: [{ size: '50%', collapsible: false },//left vertical pane 1
                                           { size: '50%',  collapsible: true}] });//right vertical pane 2
    this.layoutH1V23.jqxSplitter({ orientation: 'horizontal',width: '100%',height: '100%',
                                  panels: [{ size: '35%', collapsible: false },//upper vertical panes 1 & 2
                                           { size: '65%',  collapsible: true}] });//lower horizontal pane 3
    $('#v2_v3Splitter',this.layoutH1V23)
                   .jqxSplitter({ orientation: 'vertical',
                                  panels: [{ size: '50%', collapsible: false },//left vertical pane 1
                                           { size: '50%',  collapsible: true}] });//right vertical pane 2
    this.layoutH12V3.jqxSplitter({ orientation: 'vertical',width: '100%',height: '100%',
                                  panels: [{ size: '65%', collapsible: false },//upper vertical panes 1 & 2
                                           { size: '35%',  collapsible: true}] });//lower horizontal pane 3
    $('#h1_h2Splitter',this.layoutH12V3)
                   .jqxSplitter({ orientation: 'horizontal',
                                  panels: [{ size: '50%', collapsible: false },//left vertical pane 1
                                           { size: '50%',  collapsible: true}] });//right vertical pane 2
    this.layoutH12.unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    this.layoutV12.unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    this.layoutH123.unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    $('#h2_3Splitter',this.layoutH123).unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    this.layoutV12H3.unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    $('#v1_v2Splitter',this.layoutV12H3).unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    this.layoutH1V23.unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    $('#v2_v3Splitter',this.layoutH1V23).unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    this.layoutH12V3.unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    $('#h1_h2Splitter',this.layoutH12V3).unbind('resize').bind('resize',function(e) {
                                                      layoutMgr.resizeHandler(e);
                                                    });
    this.layoutH12.unbind('focusin').bind('focusin',function(e,args) {
                                                      layoutMgr.focusHandler(e,args);
                                                    });
    this.layoutV12.unbind('focusin').bind('focusin',function(e,args) {
                                                      layoutMgr.focusHandler(e,args);
                                                    });
    this.layoutV12H3.unbind('focusin').bind('focusin',function(e,args) {
                                                      layoutMgr.focusHandler(e,args);
                                                    });
    this.layoutH1V23.unbind('focusin').bind('focusin',function(e,args) {
                                                      layoutMgr.focusHandler(e,args);
                                                    });
    this.layoutH12V3.unbind('focusin').bind('focusin',function(e,args) {
                                                      layoutMgr.focusHandler(e,args);
                                                    });
    this.layoutH123.unbind('focusin').bind('focusin',function(e,args) {
                                                      layoutMgr.focusHandler(e,args);
                                                    });
    DEBUG.traceExit("layoutMgr.createTextEditPanels","");
  },


/**
* handle 'resize' events
*
* @param object e System event object
*/

  resizeHandler: function(e){
    var layoutMgr = this;
      //for each pane editor clean up old layout
      for (paneID in layoutMgr.editors) {
        //if text Page Editor
        if (paneID.indexOf('pane') ==0) {
          //check for resize api
          if (typeof this.editors[paneID].resize == "function") {
            this.editors[paneID].resize();
          }
        }
      }
  },


/**
* handle 'focusin' event
*
* @param object e System event object
* @param mixed args Possible passed paneID
*/

  focusHandler: function(e,args){
    var layoutMgr = this,
        paneID = (e && e.target.id && e.target.id.match(/pane\d+/))?
                    e.target.id.match(/pane\d+/)[0]:null, $targetPane;
    if ( !paneID && e && e.target.id && e.target.id.match(/searchVE/)) {
      paneID = "searchVE";
    }
    if ( !paneID && args && args.length >1) {
      if(args.match(/pane\d+/)) {
        paneID = args.match(/pane\d+/)[0];
      } else if(args.match(/searchVE/)) {
        paneID = "searchVE";
      } else if(args[1].match(/pane\d+/)) {
        paneID = args[1].match(/pane\d+/)[1];
      }
    }
    if (paneID) {
      $targetPane = $('.editContainer.'+paneID+':not(:has(div.editPlaceholder))');
      if (this.focusPaneID && this.focusPaneID != paneID) {
        $('#viewToolBarPanel').removeClass('show'+this.focusPaneID+'TB');
        $('#editToolBarPanel').removeClass('show'+this.focusPaneID+'TB');
        if ($('.editContainer.'+this.focusPaneID).hasClass('hasFocus')) {
          $('.editContainer.'+this.focusPaneID).removeClass('hasFocus');
        }
        if (paneID == "searchVE" ) {
          this.lastTextFocusPaneID = this.focusPaneID;
        }
      }
      if (!$targetPane.hasClass('hasFocus')) {
        $('.editContainer.'+paneID).addClass('hasFocus');
        $('#viewToolBarPanel').addClass('show'+paneID+'TB');
        $('#editToolBarPanel').addClass('show'+paneID+'TB');
        this.focusPaneID = paneID;
        //TODO call editors setFocus api if available
        if (this.editors && this.editors[paneID] && this.editors[paneID].setFocus) {
          this.editors[paneID].setFocus();
        }
      }
    }
  },


/**
* switch layout
*
* @param string layout Identifies layout
*/

  switchEditLayout: function(layout){
    DEBUG.traceEntry("layoutMgr.switchEditLayout","");
    var layoutMgr = this,
        newLayout,paneID,txtIDs={},entGIDs={},entTags={};
    switch (layout) {
      case "V12":
        newLayout = this.layoutV12;
        break;
      case "H12":
        newLayout = this.layoutH12;
        break;
      case "H123":
        newLayout = this.layoutH123;
        break;
      case "V12H3":
        newLayout = this.layoutV12H3;
        break;
      case "H1V23":
        newLayout = this.layoutH1V23;
        break;
      case "H12V3":
        newLayout = this.layoutH12V3;
        break;
    }
    if (newLayout) {
      //hide current layout
      this.curLayout.removeClass('showUI');
      //for each pane editor clean up old layout
      for (paneID in this.editors) {
        //if text Page Editor
        if (paneID.indexOf('pane') == 0) {
          //read entID,txtID from editor config
          txtIDs[paneID] = this.editors[paneID].config.txtID;
          entGIDs[paneID] = this.editors[paneID].config.entGID;
          entTags[paneID] = this.editors[paneID].config.entGID.replace(":","");
          this.clearPane(paneID);
        }
      }
      this.curLayout = newLayout;
     //show newlayout
      this.curLayout.addClass('showUI');
      for (paneID in entGIDs) {
        //load entID into newlayout pane
        if (this.curLayout.find('.'+paneID).length) {
          this.loadPaneContent(entTags[paneID],paneID);
        }
      for (paneID in this.editors) {
        //if text Page Editor
        if (paneID.indexOf('pane') ==0) {
          editor = this.editors[paneID];
          if (editor && editor.propMgr &&
              typeof editor.propMgr.displayProperties == 'function'){
            editor.propMgr.displayProperties(false);//turn off properties
          }
        }
      }
      }
    }
    layoutMgr.layoutBtnBar.toggle("showLayoutUI");
    layoutMgr.pushState();
    DEBUG.traceExit("layoutMgr.switchEditLayout","");
  },


/**
* clear pane
*
* @param string paneID Identifying the pane to be cleared
*/

  clearPane: function(paneID){
    if (this.editors && this.editors[paneID]) {
      //close editor
      if (typeof this.editors[paneID].close == "function") {
        this.editors[paneID].close();
      }
      delete this.editors[paneID];
    }
    if ($("."+paneID,this.curLayout).length) {//remove children
      $("."+paneID,this.curLayout).children().remove();
      $("."+paneID,this.curLayout).html(this.editPlaceholderHTML);
    }
  },


/**
* resetLayoutManager
*
* clear all editors and resets all layouts to placeholder
*/

  resetLayoutManager: function(){
    for (paneID in this.editors) {
      if (paneID.indexOf('pane') == 0) {
        this.clearPane(paneID);
      }
    }
  },


/**
* get default edition entity id for a text
*
* @param int txtID Text entity id
*/

  getTextDefaultEdnID: function(txtID){
    var layoutMgr = this,
        tmdEntity, textEntity = this.dataMgr.getEntity('txt',txtID);
    if (textEntity && textEntity.ednIDs && textEntity.ednIDs.length) {
        return textEntity.ednIDs[0];
    }
    return null;
  },


/**
* notify editors of event
*
* @param string eventName Name of event
* @param mixed[] params Array of paramenter values
*/

  notifyEditors: function(eventName, params){
    var editorIndex, editor;eventName
    for (editorIndex in this.editors) {
      editor = this.editors[editorIndex];
      if (editor['editDiv']){
        $(editor['editDiv']).trigger(eventName,params);
      }
    }
  },


/**
* get default baseline entity id for a text
*
* @param int txtID Text entity id
*/

  getTextDefaultBlnID: function(txtID){
    var layoutMgr = this,i,blnID = null,
        textEntity = this.dataMgr.getEntity('txt',txtID);
    if (textEntity && textEntity.blnIDs && textEntity.blnIDs.length > 0) {
      for (i=0; i < textEntity.blnIDs.length; i++) {
        blnID = textEntity.blnIDs[i];
        if (this.dataMgr.checkEntityType(this.dataMgr.getEntity('bln',blnID),'Image')) {
          return blnID;
        }
      }
    }
    return null;
 },


/**
* open text default resources in editors
*
* @param int txtID Text entity id
* @param string focusPane Identifying the pane to receive focus
*/

  loadTextDefaults: function(txtID,focusPane){
    DEBUG.traceEntry("layoutMgr.loadTextDefaults"," txtID "+ txtID);
    var layoutMgr = this,blnID,ednID;
    focusPane = (focusPane && focusPane.match(/pane[1|2|3]$/i))?focusPane.toLowerCase() :
                                    ( this.lastTextFocusPaneID ? this.lastTextFocusPaneID : "pane1");
    if (txtID && this.dataMgr.textResourcesLoaded) {
      blnID = this.getTextDefaultBlnID(txtID),
      ednID = this.getTextDefaultEdnID(txtID);
      if (blnID && ednID) {
        this.loadPaneContent('bln'+blnID,'pane1');
        this.loadPaneContent('edn'+ednID,'pane2');
      } else if (blnID) {
        this.loadPaneContent('bln'+blnID,'pane1');
      } else if (ednID) {
        this.loadPaneContent('edn'+ednID,'pane1');
      }
      this.focusHandler(null,focusPane);
    } else {
      this.dataMgr.loadTextResources(function() {
          if (layoutMgr.dataMgr.isTextLoaded(txtID)) {
            layoutMgr.loadTextDefaults(txtID,focusPane);
          }
      });
    }
    DEBUG.traceExit("layoutMgr.loadTextDefaults"," txtID "+ txtID);
  },


/**
* get Editor object
*
* @param string paneID Identifying which pane oif the editor to return
* @returns editor object
*/

  getEditorFromID: function(paneID){
    return this.editors[paneID];
  },


/**
* load pane content
*
* @param string tag Identifying the resource to load
* @param string paneID Identifying which pane to load
*/

  loadPaneContent: function(tag,paneID){
  DEBUG.traceEntry("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
    var layoutMgr = this,entID,entTag,contentType,rptID,rptLabel,prefix,entity,catalog,i,
        edition,text,ednID,ednIDs,blnID,blnIDs,entGID,config;
    if (tag && tag.indexOf('-')>-1) {//check for report type tag
      entTag = tag.split('-');
      rptID = entTag[0];
      entTag = entTag[1];
    } else { // normal entity Tag
      entTag = tag;
    }
    if (entTag && entTag.match(/^cat/)) {
      if (!this.dataMgr.isCatalogLoaded(entTag.substr(3))){
        this.dataMgr.loadCatalog(entTag.substr(3),function() {
                        layoutMgr.loadPaneContent(tag,paneID);
                      });
        DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
        return;
      } else {
        catalog = this.dataMgr.getEntityFromGID(entTag);
        if (catalog.ednIDs && catalog.ednIDs.length) {
          for (i in catalog.ednIDs) {
            ednID = catalog.ednIDs[i];
            if (!this.dataMgr.editionForIDUnavailable(ednID) && !this.dataMgr.isEditionLoaded(ednID)) {
              this.dataMgr.loadEdition(ednID,function() {
                              layoutMgr.loadPaneContent(tag,paneID);
                            });
              DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
              return;
            }
          }
        } else if (catalog.blnIDs && catalog.blnIDs.length) {
          blnIDs = catalog.blnIDs;
          for (i=0; i < blnIDs.length; i++) {
            blnID = blnIDs[i];
            entity = this.dataMgr.getEntity('bln',blnID);
            if (!this.dataMgr.checkEntityType(entity,'Image') &&
                !this.dataMgr.baselineForIDUnavailable(blnID) &&
                !this.dataMgr.isBaselineLoaded(blnID)) {
              this.dataMgr.loadBaseline(blnID,function() {
                              //trigger message that baseline loaded
                              layoutMgr.notifyEditors("baselineLoaded",[paneID,blnID]);
                              layoutMgr.loadPaneContent(tag,paneID);
                            });
            }
          }
          DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
          return;
        }
      }
    } else if (entTag && entTag.match(/^edn/)){
      if (this.dataMgr.editionForIDUnavailable(entTag.substr(3))) {
            DEBUG.traceExit("layoutMgr.loadPaneContent","unavailable entTag "+ tag + " not loaded in "+paneID);
            return;
      } else if (!this.dataMgr.isEditionLoaded(entTag.substr(3))) {
          this.dataMgr.loadEdition(entTag.substr(3),function() {
                          layoutMgr.loadPaneContent(tag,paneID);
                        });
          DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
          return;
      } else if (rptID == 'pa') { //paleographic report need to load all baselines of the this edition
        // find this edition's text's image baseline ids
        edition = this.dataMgr.getEntityFromGID(entTag);
        text = this.dataMgr.getEntity('txt',edition.txtID);
        //load each baseline
        if (text.blnIDs && text.blnIDs.length) {
          blnIDs = text.blnIDs;
          for (i=0; i < blnIDs.length; i++) {
            blnID = blnIDs[i];
            entity = this.dataMgr.getEntity('bln',blnID);
            if (this.dataMgr.checkEntityType(entity,'Image') &&
                !this.dataMgr.baselineForIDUnavailable(blnID) &&
                !this.dataMgr.isBaselineLoaded(blnID)) {
              this.dataMgr.loadBaseline(blnID,function() {
                              //trigger message that baseline loaded
                              layoutMgr.notifyEditors("baselineLoaded",[paneID,blnID]);
                              layoutMgr.loadPaneContent(tag,paneID);
                            });
              DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
              return;
            }
          }
        }
      }
    } else if (entTag && entTag.match(/^bln/) && !this.dataMgr.isBaselineLoaded(entTag.substr(3))){
      if (!this.dataMgr.baselineForIDUnavailable(entTag.substr(3))) {
        this.dataMgr.loadBaseline(entTag.substr(3),function() {
                      //trigger message that baseline loaded
                      layoutMgr.notifyEditors("baselineLoaded",[paneID,entTag.substr(3)]);
                      layoutMgr.loadPaneContent(tag,paneID);
                      });
      }
      DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
      return;
    } else if (entTag && (entTag.match(/^txt/) || entTag.match(/^srf/) ||
                entTag.match(/^tmd/)) && !this.dataMgr.isTextLoaded(entTag)){
      if (!this.dataMgr.textForTagUnavailable(entTag)) {
        if (ednID = this.getTextDefaultEdnID(txtID)) {
          if (!this.dataMgr.isEditionLoaded(ednID)) {
            this.dataMgr.loadEdition(ednID,function() {
                  if (!layoutMgr.dataMgr.editionForIDUnavailable(ednID)) {
                    layoutMgr.loadPaneContent(ednID,paneID);
                  }
            });
          }
        } else {//depricated call to load everything for a text.
          this.dataMgr.loadText(entTag,function() {
                  layoutMgr.loadPaneContent(tag,paneID);
          });
        }
      }
      DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
      return;
    }
    if (entTag && entTag.length > 3 && paneID && paneID.length) {
      entID = entTag.substr(3);
      prefix = entTag.substr(0,3);
      entGID = prefix+":"+entID;
      txtID = this.dataMgr.getTextIDFromEntityTag(entTag);
      entity = this.dataMgr.getEntity(prefix,entID);
      if (!entity) {
        DEBUG.log("error","Unable to load resource, no entity found for tag "+entTag+" try reloading");
        return;
      }
      entity.id = entID;
      //clear Pane
      if ($("."+paneID,this.curLayout).length) {//remove children
        $("."+paneID,this.curLayout).children().remove();
      }
      //load appropriate editor
      if (rptID) {
        contentType = 'rpt'; // reports are subdivided by type.
      } else {
        contentType = prefix;
      }
      switch (contentType) {
        case 'bln':
          if (this.dataMgr.checkEntityType(entity,'Image')) {
            this.editors[paneID] = new EDITORS.ImageVE(
                                                   { initViewPercent:80,
                                                     entGID: entGID,
                                                     txtID: txtID,
                                                     eventMgr: layoutMgr,
                                                     layoutMgr: layoutMgr,
                                                     dataMgr: this.dataMgr,
                                                     id:paneID,
                                                     imageEditDiv: $("."+paneID,this.curLayout)[0],
                                                     baseline: entity,
                                                     navSizePercent:10
                                                   });
          }else{//todo add new transcription implement
            DEBUG.log("warn","VE for "+this.dataMgr.getTermFromID(entity.type)+" baselines not implemented yet.");
          }
          break;
        case 'img':
          this.editors[paneID] = new EDITORS.ImageVE(
                                                 { initViewPercent:100,
                                                   entGID: entGID,
                                                   eventMgr: layoutMgr,
                                                   layoutMgr: layoutMgr,
                                                   dataMgr: this.dataMgr,
                                                   id:paneID,
                                                   imageEditDiv: $("."+paneID,this.curLayout)[0],
                                                   imgEntity: entity,
                                                   navSizePercent:10
                                                 });
          break;
        case 'edn':
          //todo adjust this so that all editors have interface for setting entity and reinitialising
          this.editors[paneID] = new EDITORS.EditionVE(
                                              { edition: entity,
                                                eventMgr: layoutMgr,
                                                entGID: entGID,
                                                txtID: txtID,
                                                layoutMgr: layoutMgr,
                                                dataMgr: this.dataMgr,
                                                id:paneID,
                                                editionEditDiv:$("."+paneID,this.curLayout)[0]
                                              });
          break;
        case 'cat'://dictionary or glossary
          var catCode, catalog = this.dataMgr.getEntity('cat',entID);
          config = { eventMgr: layoutMgr,
                     layoutMgr: layoutMgr,
                     entGID:'wl-'+entGID,
                     catID: entID,
                     dataMgr: this.dataMgr,
                     id:paneID,
                     editDiv:$("."+paneID,this.curLayout)[0]
                   }
          if (catalog.value == "GD" || catalog.value == "MW") {
            catCode = catalog.value.toLowerCase();
            config['dictionary'] = catCode;
            config['url'] = basepath+'/plugins/dictionary/index.php?dictionary='+catCode+'&searchstring=a&searchtype=F&strJSON={"dictionary":"'+catCode+'","mode":"getdictionarystats"}';
//            config['url'] = basepath+'/plugins/dictionary/m_dictionary.php?dictionary='+catCode+'&searchstring=a&searchtype=F&strJSON={"dictionary":"'+catCode+'","mode":"getdictionarystats"}';
//            config['url'] = 'https://gandhari.org/beta/plugins/dictionary/m_dictionary.php?dictionary='+catCode+'&searchstring=a&searchtype=F&strJSON={"dictionary":"'+catCode+'","mode":"getdictionarystats"}';
//            config['url'] = 'http://gandhari.org/~glass/testing/m_dictionary.php?dictionary='+catCode+'&searchstring=a&searchtype=F&strJSON={"dictionary":"'+catCode+'","mode":"getdictionarystats"}';
            config['entGID'] = entGID;
            this.editors[paneID] = new EDITORS.FrameV(config);
          } else if (catalog.value == "BG") {
            catCode = catalog.value.toLowerCase();
            config['bibliography'] = catCode;
            config['url'] = basepath+'/plugins/bibliography/a_bibliography.php?initial=a';
//            config['url'] = basepath+'/plugins/bibliography/a_bibliography.php?initial=a';
//            config['url'] = 'https://gandhari.org/beta/plugins/bibliography/a_bibliography.php?initial=a';
//            config['url'] = 'http://gandhari.org/~glass/testing/a_bibliography.php?initial=a';
            config['entGID'] = entGID;
            this.editors[paneID] = new EDITORS.FrameV(config);
          } else {
            config['entGID'] = 'wl-'+entGID;
            this.editors[paneID] = new EDITORS.WordlistVE(config);
          }
          break;
        case 'rpt': //launch report
          switch (rptID) {
            case 'wl'://word list
              config = { eventMgr: layoutMgr,
                         layoutMgr: layoutMgr,
                         entGID:'wl-'+entGID,
                         dataMgr: this.dataMgr,
                         id:paneID,
                         editDiv:$("."+paneID,this.curLayout)[0]
                       }
              if (prefix == "edn" || prefix == "cat") {
                config[prefix+'ID'] = entID;
                this.editors[paneID] = new EDITORS.WordlistVE(config);
              } else {
                $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">unknown report entity.</div>');
              }
              //instantiate wordlist VE with GID catID will launch Lemma Glossary while ednID with generate word list.
              break;
            case 'ph'://Phonology
              rptLabel = 'Phonology';
              $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">'+rptLabel + ' report is under construction.</div>');
              break;
            case 'pa'://Palaeography
              config = { eventMgr: layoutMgr,
                         layoutMgr: layoutMgr,
                         entGID:'pa-'+entGID,
                         dataMgr: this.dataMgr,
                         id:paneID,
                         editDiv:$("."+paneID,this.curLayout)[0]
                       }
              if (prefix == "edn" || prefix == "cat") {
                config[prefix+'ID'] = entID;
                this.editors[paneID] = new EDITORS.PaleoVE(config);
              } else {
                $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">Palaeography Viewer/Tagger currently starts with an edition entity.</div>');
              }
              break;
            case 'tr'://Translation
              config = { eventMgr: layoutMgr,
                         edition: entity,
                         layoutMgr: layoutMgr,
                         entGID:'tr-'+entGID,
                         dataMgr: this.dataMgr,
                         id:paneID,
                         editDiv:$("."+paneID,this.curLayout)[0]
                       }
              if (prefix == "edn") {
                config[prefix+'ID'] = entID;
                this.editors[paneID] = new EDITORS.TranslationV(config);
              } else {
                $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">Translation Viewer currently starts with an edition entity.</div>');
              }
              break;
            case 'cy'://chāyā
              config = { eventMgr: layoutMgr,
                         edition: entity,
                         transType:'chaya',
                         layoutMgr: layoutMgr,
                         entGID:'cy-'+entGID,
                         dataMgr: this.dataMgr,
                         id:paneID,
                         editDiv:$("."+paneID,this.curLayout)[0]
                       }
              if (prefix == "edn") {
                config[prefix+'ID'] = entID;
                this.editors[paneID] = new EDITORS.TranslationV(config);
              } else {
                $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">Chaya Viewer currently starts with an edition entity.</div>');
              }
              break;
            case 'sq'://sequence editor/viewer
              config = { eventMgr: layoutMgr,
                         edition: entity,
                         layoutMgr: layoutMgr,
                         entGID:'sq-'+entGID,
                         dataMgr: this.dataMgr,
                         id:paneID,
                         editDiv:$("."+paneID,this.curLayout)[0]
                       }
              if (prefix == "edn") {
                this.editors[paneID] = new EDITORS.SequenceVE(config);
              } else {
                $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">Sequence Viewer/Editor currently starts with an edition entity.</div>');
              }
              break;
            case 'mo'://Morphology
              rptLabel = 'Morphology';
              $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">'+rptLabel + ' report is under construction.</div>');
              break;
            case 'pr'://Parallels
              rptLabel = 'Parallels';
              $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">'+rptLabel + ' report is under construction.</div>');
              break;
            case 'bi'://Bibliography
              rptLabel = 'Bibliography';
              $("."+paneID,this.curLayout).html('<div class="panelMsgDiv">'+rptLabel + ' report is under construction.</div>');
              break;
            default:
              DEBUG.log("warn","No report generator for rptID "+rpt+" and entity " + entGID);
          }
          break;
        default:
          DEBUG.log("warn","No VE for content type "+contentType+" and entity " + entGID);
      }//end switch
      if (this.propVisible) {
         var editor = layoutMgr.editors[paneID];
         if (editor.displayProperties &&
             typeof editor.displayProperties == 'function') {
           editor.displayProperties(true);
         }
      }
      if (this.editors.searchVE) {// sync the play button
        this.editors.searchVE.syncPlayButton(paneID);
      }
      layoutMgr.pushState();
    }//end if
    DEBUG.traceExit("layoutMgr.loadPaneContent"," entTag "+ tag + " for "+paneID);
  },


/**
* Create
*
* @param btnIdPrefix
*/

  createButtonBar: function(btnIdPrefix){
    DEBUG.traceEntry("layoutMgr.createButtonBar","");
    var layoutMgr = this;
        btnBar = $('<div id="'+btnIdPrefix+'BtnBar">' +
                    '<div id="'+btnIdPrefix+'BtnText" class="fontIconBtn">' +
                      '<div class="btnFontIcon">&#x1F4DC;</div>' +
                      '<div class="btnTitle">Documents</div>' +
                    '</div>' +
                    // '<div id="'+btnIdPrefix+'BtnReference" class="fontIconBtn">' +
                    //   '<div class="btnFontIcon">&#x1F4DA;</div>' +
                    //   '<div class="btnTitle">Bibliographies</div>' +
                    // '</div>' +
                    // '<div id="'+btnIdPrefix+'BtnLexicon" class="fontIconBtn">' +
                    //   '<div class="btnFontIcon">&#x1F4D5;</div>' +
                    //   '<div class="btnTitle">Lexica</div>' +
                    // '</div>' +
//                    '<div id="'+btnIdPrefix+'BtnLanding" class="fontIconBtn">' +
//                      '<div class="btnFontIcon">&#x26B1;</div>' +
//                      '<div class="btnTitle">Landing</div>' +
//                    '</div>' +
                  '</div>');
        $('#'+btnIdPrefix+'BtnText',btnBar).unbind('click')
                                           .bind('click', function () {
                                             layoutMgr.switchContentPage('search');
                                           });
        $('#'+btnIdPrefix+'BtnLexicon',btnBar).unbind('click')
                                           .bind('click', function () {
//                                             layoutMgr.switchContentPage('lexica');
                                             window.location.href = 'plugins/dictionary/m_dictionary.php';
                                           });
        $('#'+btnIdPrefix+'BtnReference',btnBar).unbind('click')
                                           .bind('click', function () {
//                                             layoutMgr.switchContentPage('biblio');
                                             window.location.href = 'plugins/bibliography/a_bibliography.php';
                                           });
        $('#'+btnIdPrefix+'BtnLanding',btnBar).unbind('click')
                                           .bind('click', function () {
                                             layoutMgr.switchContentPage('landing');
                                           });
    DEBUG.traceExit("layoutMgr.createButtonBar","");
    return btnBar;
  },


/**
* create logo footer
*/

  createMainFooter: function(){
    var footer = $('<div id="mainFooter">' +
                   '<div>READ software development supported by: </div>' +
                   '<div id="logoBar">' +
                        '<a href="http://www.lmu.de/" target="_blank" title="University of Munich" class="logoLink">' +
                          '<img src="./common/images/logo_LMU.png" alt="LMU logo" title="University of Munich">' +
                        '</a>' +
                        '<a href="http://www.unil.ch/" target="_blank" title="University of Lausanne" class="logoLink">' +
                          '<img src="./common/images/logo_UNIL.png" alt="UNIL logo" title="University of Lausanne">' +
                        '</a>' +
                        '<a href="http://www.washington.edu/" target="_blank" title="University of Washington" class="logoLink">' +
                          '<img src="./common/images/logo_UW.png" alt="UW logo" title="University of Washington">' +
                        '</a>' +
                        '<a href="http://www.prakas.org/" target="_blank" title="Prakaś Foundation" class="logoLink">' +
                          '<img src="./common/images/logo_Prakas.png" alt="Prakaś logo" title="Prakaś Foundation">' +
                        '</a>' +
                        '<a href="http://www.sydney.edu.au/" target="_blank" title="University of Sydney" class="logoLink">' +
                          '<img src="./common/images/logo_USyd.png" alt="USyd logo" title="University of Sydney">' +
                        '</a>' +
                      '</div>' +
                    '</div>');
    return footer;
  },

  getContentPage: function(){
    return this.currentPage;
  },


/**
* switch content page
*
* @param string pageID Identify which page to switch to
* @param string focusPane identifying which pane receives focus
*/

  switchContentPage: function(pageID,focusPane){
    DEBUG.traceEntry("layoutMgr.switchContentPage","");
    var layoutMgr = this,newPage, txtID, textEntity;
    switch (pageID) {
      case 'loading':
        if (this.loadingPage) {
          newPage = "loadingPage";
        }
        break;
      case 'textEdit':
        if (this.textEditPage) {
          newPage = "textEditPage";
          if (!this.editors.pane1 && this.editors.searchVE && this.editors.searchVE.getCursorTextID()) {//nothing loaded
            //check search for cursor text
            txtID = this.editors.searchVE.getCursorTextID();
            this.loadTextDefaults(txtID);
          } else {
            this.focusHandler(null,(focusPane && focusPane.match(/pane[1|2|3]$/i))?focusPane.toLowerCase() :
                                    ( this.lastTextFocusPaneID ? this.lastTextFocusPaneID : "pane1"));
          }
          this.synchScrollBtn.removeAttr("disabled");
        }
        break;
      case 'search':
        if (this.searchPage) {
          newPage = "searchPage";
          this.focusHandler(null,"searchVE");
          this.synchScrollBtn.attr("disabled","disabled");
        }
        break;
      case 'landing':
      default:
        if (this.landingPage) {
          newPage = "landingPage";
        }
    }
    if (this.editors.searchVE) {// sync the play button
      this.editors.searchVE.syncPlayButton(pageID)
    }
    if (newPage) {
      $(document.body).removeClass(this.currentPage);
      this.currentPage = newPage;
      $(document.body).addClass(this.currentPage);
      layoutMgr.pushState();
    }else{
      alert("Unable to launch page for "+pageID+" likely not implemented yet!");
    }
    DEBUG.traceExit("layoutMgr.switchContentPage","");
  },

  showCatBtnUI: function(){
    return this.catBtnUI && this.catBtnUI.addClass("showCatBtn");
  },

  hideCatBtnUI: function(){
    return this.catBtnUI && this.catBtnUI.removeClass("showCatBtn");
  },


/**
* create Layout button bar
*/

  createLayoutButtonBar: function(){
    DEBUG.traceEntry("layoutMgr.createLayoutButtonBar","");
    var layoutMgr = this,
        btnBar = $('#layoutBtnBar'),
        barBtns = $('<div id="H12" class="layoutBtn">' +
                      '<div class="btnLayoutDiv">1</div>' +
                      '<div class="btnLayoutDiv">2</div>' +
                    '</div>' +
                    '<div id="V12" class="layoutBtn">' +
                      '<div class="btnLayoutDiv">1</div>' +
                      '<div class="btnLayoutDiv">2</div>' +
                    '</div>' +
                    '<div id="V12H3" class="layoutBtn">' +
                      '<div class="btnLayoutDiv inline">1</div>' +
                      '<div class="btnLayoutDiv inline">2</div>' +
                      '<div class="btnLayoutDiv">3</div>' +
                    '</div>' +
                    '<div id="H1V23" class="layoutBtn">' +
                      '<div class="btnLayoutDiv">1</div>' +
                      '<div class="btnLayoutDiv inline">2</div>' +
                      '<div class="btnLayoutDiv inline">3</div>' +
                    '</div>' +
                    '<div id="H12V3" class="layoutBtn">' +
                      '<div class="btnLayoutCtnr">' +
                        '<div class="btnLayoutDiv">1</div>' +
                        '<div class="btnLayoutDiv">2</div>' +
                      '</div>' +
                      '<div class="btnLayoutDiv fright">3</div>' +
                    '</div>' +
                    '<div id="H123" class="layoutBtn">' +
                      '<div class="btnLayoutDiv">1</div>' +
                      '<div class="btnLayoutDiv">2</div>' +
                      '<div class="btnLayoutDiv">3</div>' +
                    '</div>');
        btnBar.append(barBtns);
        $('#H12',btnBar).unbind('click')
                         .bind('click', function () {
                           layoutMgr.switchEditLayout('H12');
                         });
        $('#V12',btnBar).unbind('click')
                         .bind('click', function () {
                           layoutMgr.switchEditLayout('V12');
                         });
        $('#V12H3',btnBar).unbind('click')
                         .bind('click', function () {
                           layoutMgr.switchEditLayout('V12H3');
                         });
        $('#H1V23',btnBar).unbind('click')
                         .bind('click', function () {
                           layoutMgr.switchEditLayout('H1V23');
                         });
        $('#H12V3',btnBar).unbind('click')
                         .bind('click', function () {
                           layoutMgr.switchEditLayout('H12V3');
                         });
        $('#H123',btnBar).unbind('click')
                         .bind('click', function () {
                           layoutMgr.switchEditLayout('H123');
                         });
    DEBUG.traceExit("layoutMgr.createLayoutButtonBar","");
    return btnBar;
  },


  /**
  * create Landing page
  */

  createLandingPage: function(){
    DEBUG.traceEntry("layoutMgr.createLandingPage","");
    var layoutMgr = this;
    //create sign in UI
    if (this.landingPage) {
      delete this.landingPage;
    }
    this.landingPage = $('<div id="landingPage">' +
                            '<h1>'+this.projTitle+'</h1>' +
                            '<h2>Research Environment for Ancient Documents</h2>' +
                         '</div>');
    this.landingPage.append(this.createButtonBar('main'));
    this.landingPage.append(this.createMainFooter());
    this.landingPage.append($('<div id="rightPanel"></div>'));
    //display UI
    this.contentDiv.append(this.landingPage);
    DEBUG.traceExit("layoutMgr.createLandingPage","");
  },

  getUsername: function(){
    return this.userVE.getUsername();
  },

  isLoggedIn: function(){
    return this.userVE.isLoggedIn();
  },



  /**
  * create new edition wizard
  */

  initEditionWizard: function(ckn = null){
    var layoutMgr = this;
    DEBUG.traceEntry("layoutMgr.initEditionWizard","");
    $('#btnEditionValidate').jqxButton({ width: '80px', disabled: true });
    $('#btnEditionValidate').unbind('click').bind('click',function(e) {layoutMgr.importNewEdition(false);});
    $('#btnEditionSave').jqxButton({ width: '80px', disabled: false });
    $('#btnEditionSave').unbind('click').bind('click', function(e) {layoutMgr.importNewEdition(false);});
    $('#btnEditionWizardCancel').jqxButton({ width: '80px', disabled: false });
    $('#txtInv').val((ckn?ckn:null));
    $('#ednTitle').val();
    $('#freetextCheckBox').jqxCheckBox({ width: '150px', checked:true, disabled:true});
    //    $('#verboseCheckBox').jqxCheckBox({ width: '150px', checked:false});
    DEBUG.traceExit("layoutMgr.initEditionWizard","");
  },



  /**
  * create new edition wizard
  */

  importNewEdition: function(saveAfterValidation = false){
    DEBUG.traceEntry("layoutMgr.importNewEdition","");
    var layoutMgr = this, savedata = {};
    savedata['txtInv'] = $('#txtInv').val()
    savedata['ednTitle'] = $('#ednTitle').val(),
    savedata['transcription'] = $('#transcript').val(),
    savedata['freetextImport'] = true,
    savedata['verbose'] = false;
    savedata['save'] = saveAfterValidation;
    if (!$('#freetextCheckBox').jqxCheckBox('checked')) {
      savedata.freetextImport = false;
    }
    if ($('#verboseCheckBox').jqxCheckBox('checked')) {
      savedata.verbose =  true;
    }
  //make ajax call to create plain Text
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: basepath+'/services/createPlainTextEdition.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
            var srchVE = layoutMgr.editors.searchVE;
            if (data && data.resultMsg) {
              $('#importResultsContent').html(data.resultMsg);
            }
            if (typeof data == 'object' && data.success && data.entities) {
              layoutMgr.dataMgr.updateLocalCache(data,srchVE.getCursorTextID());
              if (data && data.entities && data.entities.insert && data.entities.insert.edn) {
                var ednID, text = srchVE.dataMgr.getEntity('txt',srchVE.getCursorTextID());
                if (text) {
                  if (!text.ednIDs) {
                    text.ednIDs = [];
                  }
                  for (ednID in data.entities.insert.edn) {
                    if (text.ednIDs.indexOf(ednID) == -1){
                      text.ednIDs.push(ednID);
                    }
                  }
                  srchVE.dataMgr.updateTextResourcesCache(text.id);
                }
              }
              srchVE.updateCursorInfoBar();
              $('#btnEditionSave').jqxButton({disabled: false });
              $('#transcript').val('')
            }
        },
        error: function (xhr,status,error) {
            // parsing text input failed.
            errStr = "<div class=\"errmsg\">An error occurred while trying to parser text input. Error: " + error+"</div>";
            $('#importResultsContent').val(errStr);
        }
    });// end ajax
    DEBUG.traceExit("layoutMgr.importNewEdition","");
  },



  /**
  * create new edition wizard
  */

  createNewEditionWizard: function(ckn = null){
    DEBUG.traceEntry("layoutMgr.createNewEditionWizard","");
    var layoutMgr = this, mainContainer = $('body'),
        offset = mainContainer.offset(),
        wzWidth, wzHeight;

    offset.xcenter = mainContainer.innerWidth()/4;
    offset.ycenter = mainContainer.innerHeight()/4;
    wzWidth = 3 * offset.xcenter + 300;
    wzHeight = 3 * offset.ycenter + 400;
    layoutMgr.ednWizard = $('#editionWizard').jqxWindow({  width: wzWidth,
                              height: wzHeight, resizable: true, isModal: true,
                              cancelButton: $('#btnEditionWizardCancel'),
                              position: { x: offset.left + 100, 
                                          y: offset.top + 100},
                              initContent: function () { layoutMgr.initEditionWizard(ckn);}
                          });
    layoutMgr.ednWizard.jqxWindow('close');
    DEBUG.traceExit("layoutMgr.createNewEditionWizard","");
  }
}
