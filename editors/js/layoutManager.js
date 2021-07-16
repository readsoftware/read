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

MANAGERS.LayoutManager = function (layoutCfg) {
  this.config = layoutCfg;
  this.navPanel = layoutCfg['navPanel'] ? layoutCfg['navPanel'] : null;
  this.dataMgr = layoutCfg['dataMgr'] ? layoutCfg['dataMgr'] : null;
  if (!this.dataMgr) {
    this.dataMgr = new MANAGERS.DataManager({
      dbname: dbName ? dbName : '',
      username: this.username ? this.username : "Guest"
    });
  }
  if (layoutCfg['catalogIdURL']) {
    this.newCatalogIDService = layoutCfg['catalogIdURL'];
  }
  this.projTitle = layoutCfg['projTitle'] ? layoutCfg['projTitle'] : "No Title sent to LayoutMgr";
  this.contentDiv = layoutCfg['contentDiv'] ? layoutCfg['contentDiv'] : null;
  this.username = layoutCfg['username'] && layoutCfg['username'] != "unknown" ? layoutCfg['username'] : null;
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

  init: function () {
    DEBUG.traceEntry("layoutMgr.init", "");
    var layoutMgr = this, startPage = "landing", startFocus = null, toolsState = null, focus,
      entTags, entTag, txtID, searchVE;
    this.params = UTILITY.parseParams();//get URI parameters
    this.createUserNavBar();
    this.loadingPage = $('<div id="loadingPage"><h1>Loading ....</h1></div>');
    this.contentDiv.html("");
    this.contentDiv.append(this.loadingPage);
    this.currentPage = "loadingPage";
    $(document.body).addClass(this.currentPage);
    this.createLandingPage();
    this.createSearchCtrlPanel();
    //parse params for start up
    if (this.params['f']) { //focus parameter
      focus = this.params['f'].split(",");
      startPage = focus[0];
      if (focus.length > 1) {
        startFocus = focus[1];
      }
      if (focus.length > 2) {
        toolsState = focus[2];
        //override system defaults in config.php
        EDITORS.config.editToolsOpenOnStart = false;
        EDITORS.config.viewToolsOpenOnStart = false;
        EDITORS.config.layoutToolsOpenOnStart = false;
        EDITORS.config.toolSidebarOpenOnStart = true;
        for (char in toolsState) {
          switch (toolsState[char]) {
            case "E": //edit open
              EDITORS.config.editToolsOpenOnStart = true;
              break;
            case "V": //view open
              EDITORS.config.viewToolsOpenOnStart = true;
              break;
            case "L": //layout open
              EDITORS.config.layoutToolsOpenOnStart = true;
              break;
            case "C": //layout open
              EDITORS.config.toolSidebarOpenOnStart = false;
              break;
          }
        }
      }
    } else if (this.params['l']) {//there is an editor layout so switch to textEdit page
      startPage = "textEdit";
    } else if (this.params['q']) {//there is a search so switch to search page
      startPage = "search";
    }
    this.createTextEditPanels();
    this.createEditorToolPanels();
    this.createLayoutTools();
    this.switchContentPage(startPage, startFocus);
    if (this.params['l']) { //layout parameter
      entTags = this.params['l'].split(',');
      if (entTags[0][0] == 'H' || entTags[0][0] == 'V') {//layout tag
        entTags.shift();
      }
      if (entTags.length > 0) {
        for (var i = 0; i < entTags.length; i++) {
          entTag = entTags[i];
          this.loadPaneContent(entTag, "pane" + (i + 1));
        }
        this.focusHandler(null, startFocus);
      } else if (this.editors['searchVE']) {
        searchVE = this.editors['searchVE'];
        if (searchVE.isLoaded()) {
          //attempt to load current cursor text default resources
          this.loadTextDefaults(searchVE.getCursorTextID(), startFocus);
        } else {
          setTimeout(function () {
            layoutMgr.loadTextDefaults(searchVE.getCursorTextID(), startFocus);
          }, 1500);
        }
      }
    }
    if (!EDITORS.config.toolSidebarOpenOnStart) {
      this.hideSideBar();
    }
    this.navPanel.unbind('keydown').bind('keydown', this.panelKeydownHandler);
    this.contentDiv.unbind('keydown').bind('keydown', this.panelKeydownHandler);
    this.createNewEditionWizard();// placed here due to unknown interaction bug with toolbar, leave after load
    DEBUG.traceExit("layoutMgr.init", "");
  },

  getEditionObjectLevel: function () {
    return this.editionObjLevel;
  },

  setEditionObjectLevel: function (level) {
    this.editionObjLevel = level;
  },

  refreshCursor: function () {
    if (this.editors.searchVE) {
      this.editors.searchVE.updateCursorInfoBar();
    }
  },


  refreshCatalogResources: function () {
    this.editors.searchVE.updateCatalogInfoBar();
  },


  /**
  * refresh search page
  *
  * @param pageID
  */

  refresh: function (pageID) {
    this.dataMgr.flushLocalCache();
    this.editors.searchVE.reloadSearch(pageID);
    this.pushState();
  },


  /**
  * update browser history with calculated url
  *
  */

  pushState: function () {
    DEBUG.traceEntry("layoutMgr.pushState", "");
    var URL = this.dataMgr.basepath + "/?db=" + this.dataMgr.dbName, state = {},
      searchVE = this.editors['searchVE'], layoutID, edID, edTags = {}, maxIndex = 0, i,
      focus = this.focusPaneID != 'searchVE' ? this.focusPaneID : null;
    pageID = this.currentPage.replace("Page", "");
    if (this.curLayout.children('.pane1').get(0)) {
      layoutID = this.curLayout.children('.pane1').get(0).id.replace("pane1-", "");
    } else {
      layoutID = this.curLayout.children('.pane3').get(0).id.replace("pane3-", "");
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
      if (this.editors[edID].config && this.editors[edID].config.entGID) {
        i = edID.replace("pane", "");
        edTags[i] = this.editors[edID].config.entGID.replace(":", "");
        maxIndex = Math.max(maxIndex, i);
      }
    }
    if (maxIndex) {
      URL += "&l=";
      if (layoutID) {
        URL += layoutID;
        state['layout'] = layoutID;
      }
      for (i = 1; i <= maxIndex; i++) {
        if (edTags[i]) {
          if (URL.substr(-1) != ",") {
            URL += ",";
          }
          URL += edTags[i];
          state['pane' + i] = edTags[i];
        } else {
          URL += ",";
        }
      }
    }
    DEBUG.log("gen", window.location.href.substr(0, URL.length));
    if (window.location.href.substr(0, URL.length) != URL ||
      maxIndex && window.location.href.length > URL.length) {
      window.history.pushState(state, '', URL);
      DEBUG.log("gen", "pushHistory - " + URL);
    } else {
      DEBUG.log("gen", "URL State change - " + URL);
    }
    DEBUG.traceExit("layoutMgr.pushState", "");
  },


  /**
  * create user navigation bar
  */

  createUserNavBar: function () {
    DEBUG.traceEntry("layoutMgr.createUserNavBar", "");
    var layoutMgr = this,
      userHdrDiv = $('<div id="userHeader"/>'),
      userNavBar = $('<div id="navBar"/>'),
      userNavHdr = $('<div id="navHdr"/>'),
      userUIPanel = $('<div id="userUIPanel"/>'),
      navBarExpHdr;
    userNavHdr.append(userHdrDiv);
    userNavBar.append(userNavHdr).append(userUIPanel);
    this.userNavBar = userNavBar.jqxExpander({
      expanded: false,
      expandAnimationDuration: 50,
      collapseAnimationDuration: 50
    });
    this.userNavBar.css('height', 'auto');
    this.userNavBar.unbind('expanding').bind('expanding', function (e) {
      layoutMgr.userNavBar.css('height', '100%');
      $('#userUIPanel', layoutMgr.userNavBar).css('height', '100%');
    });
    this.userNavBar.unbind('collapsing').bind('collapsing', function (e) {
      layoutMgr.userNavBar.css('height', 'auto');
      $('#userUIPanel', layoutMgr.userNavBar).css('height', 'auto');
    });
    navBarExpHdr = this.userNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
    this.navPanel.append(this.userNavBar);
    this.userVE = new EDITORS.UserVE({
      userVEDiv: userUIPanel,
      userHdrDiv: userHdrDiv,
      layoutMgr: layoutMgr,
      dataMgr: this.dataMgr,
      username: this.username
    });
    this.hideCatBtnUI()
    DEBUG.traceExit("layoutMgr.createUserNavBar", "");
  },

  closeUserPanel: function () {
    this.userNavBar.jqxExpander("expanded", false);
  },



  /**
  * put your comment there...
  *
  * @param object e System event object
  *
  * @returns true|false
  */

  panelKeydownHandler: function (e) {
    var key = e.key ? e.key :
      (e.which == null ? String.fromCharCode(e.keyCode) :
        ((e.which != 0) ? String.fromCharCode(e.which) : null));
    if (key && key == "T") {// toggle sidebar
      this.toggleSideBar();
      e.stopImmediatePropagation();
      return false;//eat all other keys
    }
  },


  /**
  * toggle tool panel opens or closes the side toolbar panel
  */

  toggleSideBar: function () {
    $('#textEditPage').toggleClass('hideSidebar');
    $('#frameContentPanel').toggleClass('hideSidebar');
    $('#frameNavPanel').toggleClass('hideSidebar');
    this.resizeHandler();
  },


  /**
  * hide side toolbar panel
  */

  hideSideBar: function () {
    $('#textEditPage').addClass('hideSidebar');
    $('#frameContentPanel').addClass('hideSidebar');
    $('#frameNavPanel').addClass('hideSidebar');
  },


  /**
  * show side toolbar panel
  */

  showSideBar: function () {
    $('#textEditPage').removeClass('hideSidebar');
    $('#frameContentPanel').removeClass('hideSidebar');
    $('#frameNavPanel').removeClass('hideSidebar');
  },


  /**
  * create search control panel
  */

  createSearchCtrlPanel: function () {
    var layoutMgr = this;
    this.searchNavBar = $('<div id="searchNavBar"/>');
    this.searchPage = $('<div id="searchPage"/>');
    this.searchNavBar.insertAfter(this.userNavBar);
    this.contentDiv.append(this.searchPage);
    this.editors['searchVE'] = new EDITORS.SearchVE({
      searchNavBar: this.searchNavBar,
      editDiv: this.searchPage,
      layoutMgr: layoutMgr,
      dataMgr: this.dataMgr,
      search: (this.params && this.params['q']) ? this.params['q'] : ""
    });
  },


  /**
  * create Editor Tool Panel
  */

  createEditorToolPanels: function () {
    DEBUG.traceEntry("layoutMgr.createEditorToolPanels", "");
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
    viewToolNavBar.append(viewToolNavHdr).append(this.viewToolBarPanel);
    this.viewToolNavBar = viewToolNavBar.jqxExpander({
      expanded: EDITORS.config.viewToolsOpenOnStart,
      expandAnimationDuration: 50,
      collapseAnimationDuration: 50
    });
    navBarExpHdr = this.viewToolNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
    //attach editheader and editpanel to editNavBar
    editToolNavBar.append(editToolNavHdr).append(this.editToolBarPanel);
    this.editToolNavBar = editToolNavBar.jqxExpander({
      expanded: EDITORS.config.editToolsOpenOnStart,
      expandAnimationDuration: 50,
      collapseAnimationDuration: 50
    });
    this.editToolBarHdr = this.editToolNavBar.children().first();
    this.editToolBarHdr.addClass('navExpandableHeader');
    layoutToolNavBar.append(layoutToolNavHdr).append(this.layoutToolBarPanel);
    this.layoutToolNavBar = layoutToolNavBar.jqxExpander({
      expanded: EDITORS.config.layoutToolsOpenOnStart,
      expandAnimationDuration: 50,
      collapseAnimationDuration: 50
    });
    navBarExpHdr = this.layoutToolNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
    //    navBarExpHdr.css('background-color','white');
    //    navBarExpHdr.css('border','none');
    this.editToolNavBar.insertAfter(this.searchNavBar);
    this.viewToolNavBar.insertAfter(this.editToolNavBar);
    this.layoutToolNavBar.insertAfter(this.viewToolNavBar);
    //    this.createLayoutTools();
    this.editors.searchVE.createStaticToolbar();
    DEBUG.traceExit("layoutMgr.createEditorToolPanels", "");
  },


  /**
  * create Layout Tools
  *
  */

  createLayoutTools: function () {
    DEBUG.traceEntry("layoutMgr.createLayoutTools", "");
    var layoutMgr = this;
    this.layoutBtnDiv = $('<div id="layoutBtnDiv" class="toolbuttondiv">' +
      '<button class="toolbutton" id="layoutBtn' +
      '" title="Select panel layout">Change</button>' +
      '<div class="toolbuttonlabel">Panel Layout</div>' +
      '</div>');
    this.layoutBtnBar = this.createLayoutButtonBar();
    this.layoutToolBarPanel.append(this.layoutBtnDiv);
    $('#layoutBtn', this.layoutBtnDiv).unbind('click')
      .bind('click', function (e) {
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
      '<button class="toolbutton iconbutton" id="propertiesBtn' +
      '" title="Show/Hide property panels">&#x25E8;</button>' +
      '<div class="toolbuttonlabel">Properties</div>' +
      '</div>');
    $('#propertiesBtn', this.propertyBtnDiv).unbind('click')
      .bind('click', function (e) {
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
      '<button class="toolbutton" id="synchScrollBtn' +
      '" title="Turn on/off synchronize scrolling">Off</button>' +
      '<div class="toolbuttonlabel">Sync Scroll</div>' +
      '</div>');
    this.layoutToolBarPanel.append(this.synchScrollBtnDiv);
    this.synchScrollBtn = $('#synchScrollBtn', this.synchScrollBtnDiv);
    this.synchScrollBtn.unbind('click').bind('click', function (e) {
      $('body').toggleClass("synchScroll");
      $(this).html($('body').hasClass("synchScroll") ? "On" : "Off");
      e.stopImmediatePropagation();
      return false;
    });
    //    this.layoutToolBarPanel.append(this.layoutBtnBar);
    DEBUG.traceExit("layoutMgr.createLayoutTools", "");
  },


  /**
  * register view tool bar
  *
  * @param string paneID Identifying the pane of the editor registering
  * @param node tbView View tool bar element for the given editor of a pane
  */

  registerViewToolbar: function (paneID, tbView) {
    DEBUG.traceEntry("layoutMgr.registerViewToolbar", "");
    //if toolbar lookup is occupied remove toolbar from panel
    if (this.lkViewTB[paneID]) {
      $('.' + paneID + 'ViewTB', this.viewToolBarPanel).remove();
      delete this.lkViewTB[paneID];
    }
    //add paneIDViewTB class to tbView
    tbView.addClass(paneID + 'ViewTB');
    //add to panel and store in lookup
    this.viewToolBarPanel.append(tbView);
    this.lkViewTB[paneID] = tbView;
    DEBUG.traceExit("layoutMgr.registerViewToolbar", "");
  },


  /**
  * register edit tool bar
  *
  * @param string paneID Identifying the pane of the editor registering
  * @param node tbEdit Edit tool bar element for the given editor of a pane
  */

  registerEditToolbar: function (paneID, tbEdit) {
    DEBUG.traceEntry("layoutMgr.registerEditToolbar", "");
    //if toolbar lookup is occupied remove toolbar from panel
    if (this.lkEditTB[paneID]) {
      $('.' + paneID + 'EditTB', this.editToolBarPanel).remove();
      delete this.lkEditTB[paneID];
    }
    //add paneIDEditTB class to tbEdit
    tbEdit.addClass(paneID + 'EditTB');
    //add to panel and store in lookup
    this.editToolBarPanel.append(tbEdit);
    this.lkEditTB[paneID] = tbEdit;
    DEBUG.traceExit("layoutMgr.registerEditToolbar", "");
  },


  /**
  * create text edit layout panels
  *
  */

  createTextEditPanels: function () {
    DEBUG.traceEntry("layoutMgr.createTextEditPanels", "");
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
    this.layoutH12.jqxSplitter({
      orientation: 'horizontal', width: '100%', height: '100%',
      panels: [{ size: '50%', collapsible: false },//horizontal pane 1
      { size: '50%', collapsible: true }]
    });//horizontal pane 2
    this.layoutV12.jqxSplitter({
      orientation: 'vertical', width: '100%', height: '100%',
      panels: [{ size: '50%', collapsible: false },//left vertical pane 1
      { size: '50%', collapsible: true }]
    });//right vertical pane 2
    this.layoutH123.jqxSplitter({
      orientation: 'horizontal', width: '100%', height: '100%',
      panels: [{ size: '35%', collapsible: false },//upper horizontal pane 1
      { size: '65%', collapsible: true }]
    });//lower horizontal panes 2 & 3
    $('#h2_3Splitter', this.layoutH123)
      .jqxSplitter({
        orientation: 'horizontal',
        panels: [{ size: '50%', collapsible: false },//middle horizontal pane 2
        { size: '50%', collapsible: true }]
      });//lower horizontal pane 3
    this.layoutV12H3.jqxSplitter({
      orientation: 'horizontal', width: '100%', height: '100%',
      panels: [{ size: '65%', collapsible: false },//upper vertical panes 1 & 2
      { size: '35%', collapsible: true }]
    });//lower horizontal pane 3
    $('#v1_v2Splitter', this.layoutV12H3)
      .jqxSplitter({
        orientation: 'vertical',
        panels: [{ size: '50%', collapsible: false },//left vertical pane 1
        { size: '50%', collapsible: true }]
      });//right vertical pane 2
    this.layoutH1V23.jqxSplitter({
      orientation: 'horizontal', width: '100%', height: '100%',
      panels: [{ size: '35%', collapsible: false },//upper vertical panes 1 & 2
      { size: '65%', collapsible: true }]
    });//lower horizontal pane 3
    $('#v2_v3Splitter', this.layoutH1V23)
      .jqxSplitter({
        orientation: 'vertical',
        panels: [{ size: '50%', collapsible: false },//left vertical pane 1
        { size: '50%', collapsible: true }]
      });//right vertical pane 2
    this.layoutH12V3.jqxSplitter({
      orientation: 'vertical', width: '100%', height: '100%',
      panels: [{ size: '65%', collapsible: false },//upper vertical panes 1 & 2
      { size: '35%', collapsible: true }]
    });//lower horizontal pane 3
    $('#h1_h2Splitter', this.layoutH12V3)
      .jqxSplitter({
        orientation: 'horizontal',
        panels: [{ size: '50%', collapsible: false },//left vertical pane 1
        { size: '50%', collapsible: true }]
      });//right vertical pane 2
    this.layoutH12.unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    this.layoutV12.unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    this.layoutH123.unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    $('#h2_3Splitter', this.layoutH123).unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    this.layoutV12H3.unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    $('#v1_v2Splitter', this.layoutV12H3).unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    this.layoutH1V23.unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    $('#v2_v3Splitter', this.layoutH1V23).unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    this.layoutH12V3.unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    $('#h1_h2Splitter', this.layoutH12V3).unbind('resize').bind('resize', function (e) {
      layoutMgr.resizeHandler(e);
    });
    this.layoutH12.unbind('focusin').bind('focusin', function (e, args) {
      layoutMgr.focusHandler(e, args);
    });
    this.layoutV12.unbind('focusin').bind('focusin', function (e, args) {
      layoutMgr.focusHandler(e, args);
    });
    this.layoutV12H3.unbind('focusin').bind('focusin', function (e, args) {
      layoutMgr.focusHandler(e, args);
    });
    this.layoutH1V23.unbind('focusin').bind('focusin', function (e, args) {
      layoutMgr.focusHandler(e, args);
    });
    this.layoutH12V3.unbind('focusin').bind('focusin', function (e, args) {
      layoutMgr.focusHandler(e, args);
    });
    this.layoutH123.unbind('focusin').bind('focusin', function (e, args) {
      layoutMgr.focusHandler(e, args);
    });
    DEBUG.traceExit("layoutMgr.createTextEditPanels", "");
  },


  /**
  * handle 'resize' events
  *
  * @param object e System event object
  */

  resizeHandler: function (e) {
    var layoutMgr = this;
    //for each pane editor clean up old layout
    for (paneID in layoutMgr.editors) {
      //if text Page Editor
      if (paneID.indexOf('pane') == 0) {
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

  focusHandler: function (e, args) {
    var layoutMgr = this,
      paneID = (e && e.target.id && e.target.id.match(/pane\d+/)) ?
        e.target.id.match(/pane\d+/)[0] : null, $targetPane;
    if (!paneID && e && e.target.id && e.target.id.match(/searchVE/)) {
      paneID = "searchVE";
    }
    if (!paneID && args && args.length > 1) {
      if (args.match(/pane\d+/)) {
        paneID = args.match(/pane\d+/)[0];
      } else if (args.match(/searchVE/)) {
        paneID = "searchVE";
      } else if (args[1].match(/pane\d+/)) {
        paneID = args[1].match(/pane\d+/)[1];
      }
    }
    if (paneID) {
      $targetPane = $('.editContainer.' + paneID + ':not(:has(div.editPlaceholder))');
      if (this.focusPaneID && this.focusPaneID != paneID) {
        $('#viewToolBarPanel').removeClass('show' + this.focusPaneID + 'TB');
        $('#editToolBarPanel').removeClass('show' + this.focusPaneID + 'TB');
        if ($('.editContainer.' + this.focusPaneID).hasClass('hasFocus')) {
          $('.editContainer.' + this.focusPaneID).removeClass('hasFocus');
        }
        if (paneID == "searchVE") {
          this.lastTextFocusPaneID = this.focusPaneID;
        }
      }
      if (!$targetPane.hasClass('hasFocus')) {
        $('.editContainer.' + paneID).addClass('hasFocus');
        $('#viewToolBarPanel').addClass('show' + paneID + 'TB');
        $('#editToolBarPanel').addClass('show' + paneID + 'TB');
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

  switchEditLayout: function (layout) {
    DEBUG.traceEntry("layoutMgr.switchEditLayout", "");
    var layoutMgr = this,
      newLayout, paneID, txtIDs = {}, entGIDs = {}, entTags = {};
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
          entTags[paneID] = this.editors[paneID].config.entGID.replace(":", "");
          this.clearPane(paneID);
        }
      }
      this.curLayout = newLayout;
      //show newlayout
      this.curLayout.addClass('showUI');
      for (paneID in entGIDs) {
        //load entID into newlayout pane
        if (this.curLayout.find('.' + paneID).length) {
          this.loadPaneContent(entTags[paneID], paneID);
        }
        for (paneID in this.editors) {
          //if text Page Editor
          if (paneID.indexOf('pane') == 0) {
            editor = this.editors[paneID];
            if (editor && editor.propMgr &&
              typeof editor.propMgr.displayProperties == 'function') {
              editor.propMgr.displayProperties(false);//turn off properties
            }
          }
        }
      }
    }
    layoutMgr.layoutBtnBar.toggle("showLayoutUI");
    layoutMgr.pushState();
    DEBUG.traceExit("layoutMgr.switchEditLayout", "");
  },


  /**
  * clear pane
  *
  * @param string paneID Identifying the pane to be cleared
  */

  clearPane: function (paneID) {
    if (this.editors && this.editors[paneID]) {
      //close editor
      if (typeof this.editors[paneID].close == "function") {
        this.editors[paneID].close();
      }
      delete this.editors[paneID];
    }
    if ($("." + paneID, this.curLayout).length) {//remove children
      $("." + paneID, this.curLayout).children().remove();
      $("." + paneID, this.curLayout).html(this.editPlaceholderHTML);
    }
  },


  /**
  * resetLayoutManager
  *
  * clear all editors and resets all layouts to placeholder
  */

  resetLayoutManager: function () {
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

  getTextDefaultEdnID: function (txtID) {
    var layoutMgr = this,
      tmdEntity, textEntity = this.dataMgr.getEntity('txt', txtID);
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

  notifyEditors: function (eventName, params) {
    var editorIndex, editor; eventName
    for (editorIndex in this.editors) {
      editor = this.editors[editorIndex];
      if (editor['editDiv']) {
        $(editor['editDiv']).trigger(eventName, params);
      }
    }
  },


  /**
  * get default baseline entity id for a text
  *
  * @param int txtID Text entity id
  */

  getTextDefaultBlnID: function (txtID) {
    var layoutMgr = this, i, blnID = null,
      textEntity = this.dataMgr.getEntity('txt', txtID);
    if (textEntity && textEntity.blnIDs && textEntity.blnIDs.length > 0) {
      for (i = 0; i < textEntity.blnIDs.length; i++) {
        blnID = textEntity.blnIDs[i];
        if (this.dataMgr.checkEntityType(this.dataMgr.getEntity('bln', blnID), 'Image')) {
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

  loadTextDefaults: function (txtID, focusPane) {
    DEBUG.traceEntry("layoutMgr.loadTextDefaults", " txtID " + txtID);
    var layoutMgr = this, blnID, ednID;
    focusPane = (focusPane && focusPane.match(/pane[1|2|3]$/i)) ? focusPane.toLowerCase() :
      (this.lastTextFocusPaneID ? this.lastTextFocusPaneID : "pane1");
    if (txtID && this.dataMgr.textResourcesLoaded) {
      blnID = this.getTextDefaultBlnID(txtID),
        ednID = this.getTextDefaultEdnID(txtID);
      if (blnID && ednID) {
        this.loadPaneContent('bln' + blnID, 'pane1');
        this.loadPaneContent('edn' + ednID, 'pane2');
      } else if (blnID) {
        this.loadPaneContent('bln' + blnID, 'pane1');
      } else if (ednID) {
        this.loadPaneContent('edn' + ednID, 'pane1');
      }
      this.focusHandler(null, focusPane);
    } else {
      this.dataMgr.loadTextResources(function () {
        if (layoutMgr.dataMgr.isTextLoaded(txtID)) {
          layoutMgr.loadTextDefaults(txtID, focusPane);
        }
      });
    }
    DEBUG.traceExit("layoutMgr.loadTextDefaults", " txtID " + txtID);
  },


  /**
  * get Editor object
  *
  * @param string paneID Identifying which pane oif the editor to return
  * @returns editor object
  */

  getEditorFromID: function (paneID) {
    return this.editors[paneID];
  },


  /**
  * load pane content
  *
  * @param string tag Identifying the resource to load
  * @param string paneID Identifying which pane to load
  */

  loadPaneContent: function (tag, paneID) {
    DEBUG.traceEntry("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
    var layoutMgr = this, entID, entTag, contentType, rptID, rptLabel, prefix, entity, catalog, i,
      edition, text, ednID, ednIDs, blnID, blnIDs, entGID, config;
    if (tag && tag.indexOf('-') > -1) {//check for report type tag
      entTag = tag.split('-');
      rptID = entTag[0];
      entTag = entTag[1];
    } else { // normal entity Tag
      entTag = tag;
    }
    if (entTag && entTag.match(/^cat/)) {
      if (!this.dataMgr.isCatalogLoaded(entTag.substr(3))) {
        this.dataMgr.loadCatalog(entTag.substr(3), function () {
          layoutMgr.loadPaneContent(tag, paneID);
        });
        DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
        return;
      } else {
        catalog = this.dataMgr.getEntityFromGID(entTag);
        if (catalog.ednIDs && catalog.ednIDs.length) {
          for (i in catalog.ednIDs) {
            ednID = catalog.ednIDs[i];
            if (!this.dataMgr.editionForIDUnavailable(ednID) && !this.dataMgr.isEditionLoaded(ednID)) {
              if (this.dataMgr.loadingEdition && ednID == this.dataMgr.loadingEdition) {
                this.dataMgr.stackLoadEdition(ednID, function () {
                  layoutMgr.loadPaneContent(tag, paneID);
                });
              } else {
                this.dataMgr.loadEdition(ednID, function () {
                  layoutMgr.loadPaneContent(tag, paneID);
                });
              }
              DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
              return;
            }
          }
        } else if (catalog.blnIDs && catalog.blnIDs.length) {
          blnIDs = catalog.blnIDs;
          for (i = 0; i < blnIDs.length; i++) {
            blnID = blnIDs[i];
            entity = this.dataMgr.getEntity('bln', blnID);
            if (!this.dataMgr.checkEntityType(entity, 'Image') &&
              !this.dataMgr.baselineForIDUnavailable(blnID) &&
              !this.dataMgr.isBaselineLoaded(blnID)) {
              this.dataMgr.loadBaseline(blnID, function () {
                //trigger message that baseline loaded
                layoutMgr.notifyEditors("baselineLoaded", [paneID, blnID]);
                layoutMgr.loadPaneContent(tag, paneID);
              });
            }
          }
          DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
          return;
        }
      }
    } else if (entTag && entTag.match(/^edn/)) {
      if (this.dataMgr.editionForIDUnavailable(entTag.substr(3))) {
        DEBUG.traceExit("layoutMgr.loadPaneContent", "unavailable entTag " + tag + " not loaded in " + paneID);
        return;
      } else if (!this.dataMgr.isEditionLoaded(entTag.substr(3))) {
        this.dataMgr.loadEdition(entTag.substr(3), function () {
          layoutMgr.loadPaneContent(tag, paneID);
        });
        DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
        return;
      } else if (rptID == 'pa') { //paleographic report need to load all baselines of the this edition
        // find this edition's text's image baseline ids
        edition = this.dataMgr.getEntityFromGID(entTag);
        text = this.dataMgr.getEntity('txt', edition.txtID);
        //load each baseline
        if (text.blnIDs && text.blnIDs.length) {
          blnIDs = text.blnIDs;
          for (i = 0; i < blnIDs.length; i++) {
            blnID = blnIDs[i];
            entity = this.dataMgr.getEntity('bln', blnID);
            if (this.dataMgr.checkEntityType(entity, 'Image') &&
              !this.dataMgr.baselineForIDUnavailable(blnID) &&
              !this.dataMgr.isBaselineLoaded(blnID)) {
              this.dataMgr.loadBaseline(blnID, function () {
                //trigger message that baseline loaded
                layoutMgr.notifyEditors("baselineLoaded", [paneID, blnID]);
                layoutMgr.loadPaneContent(tag, paneID);
              });
              DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
              return;
            }
          }
        }
      }
    } else if (entTag && entTag.match(/^bln/) && !this.dataMgr.isBaselineLoaded(entTag.substr(3))) {
      if (!this.dataMgr.baselineForIDUnavailable(entTag.substr(3))) {
        this.dataMgr.loadBaseline(entTag.substr(3), function () {
          //trigger message that baseline loaded
          layoutMgr.notifyEditors("baselineLoaded", [paneID, entTag.substr(3)]);
          layoutMgr.loadPaneContent(tag, paneID);
        });
      }
      DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
      return;
    } else if (entTag && (entTag.match(/^txt/) || entTag.match(/^srf/) ||
      entTag.match(/^tmd/)) && !this.dataMgr.isTextLoaded(entTag)) {
      if (!this.dataMgr.textForTagUnavailable(entTag)) {
        if (ednID = this.getTextDefaultEdnID(txtID)) {
          if (!this.dataMgr.isEditionLoaded(ednID)) {
            this.dataMgr.loadEdition(ednID, function () {
              if (!layoutMgr.dataMgr.editionForIDUnavailable(ednID)) {
                layoutMgr.loadPaneContent(ednID, paneID);
              }
            });
          }
        } else {//depricated call to load everything for a text.
          this.dataMgr.loadText(entTag, function () {
            layoutMgr.loadPaneContent(tag, paneID);
          });
        }
      }
      DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
      return;
    }
    if (entTag && entTag.length > 3 && paneID && paneID.length) {
      entID = entTag.substr(3);
      prefix = entTag.substr(0, 3);
      entGID = prefix + ":" + entID;
      txtID = this.dataMgr.getTextIDFromEntityTag(entTag);
      entity = this.dataMgr.getEntity(prefix, entID);
      if (!entity) {
        DEBUG.log("error", "Unable to load resource, no entity found for tag " + entTag + " try reloading");
        return;
      }
      entity.id = entID;
      //clear Pane
      if ($("." + paneID, this.curLayout).length) {//remove children
        $("." + paneID, this.curLayout).children().remove();
      }
      //load appropriate editor
      if (rptID) {
        contentType = 'rpt'; // reports are subdivided by type.
      } else {
        contentType = prefix;
      }
      switch (contentType) {
        case 'bln':
          if (this.dataMgr.checkEntityType(entity, 'Image')) {
            this.editors[paneID] = new EDITORS.ImageVE(
              {
                initViewPercent: 80,
                entGID: entGID,
                txtID: txtID,
                eventMgr: layoutMgr,
                layoutMgr: layoutMgr,
                dataMgr: this.dataMgr,
                id: paneID,
                imageEditDiv: $("." + paneID, this.curLayout)[0],
                baseline: entity,
                navSizePercent: 10
              });
          } else {//todo add new transcription implement
            DEBUG.log("warn", "VE for " + this.dataMgr.getTermFromID(entity.type) + " baselines not implemented yet.");
          }
          break;
        case 'img':
          this.editors[paneID] = new EDITORS.ImageVE(
            {
              initViewPercent: 100,
              entGID: entGID,
              eventMgr: layoutMgr,
              layoutMgr: layoutMgr,
              dataMgr: this.dataMgr,
              id: paneID,
              imageEditDiv: $("." + paneID, this.curLayout)[0],
              imgEntity: entity,
              navSizePercent: 10
            });
          break;
        case 'edn':
          //todo adjust this so that all editors have interface for setting entity and reinitialising
          this.editors[paneID] = new EDITORS.EditionVE(
            {
              edition: entity,
              eventMgr: layoutMgr,
              entGID: entGID,
              txtID: txtID,
              layoutMgr: layoutMgr,
              dataMgr: this.dataMgr,
              id: paneID,
              editionEditDiv: $("." + paneID, this.curLayout)[0]
            });
          break;
        case 'cat'://dictionary or glossary
          var catCode, catalog = this.dataMgr.getEntity('cat', entID);
          config = {
            eventMgr: layoutMgr,
            layoutMgr: layoutMgr,
            entGID: 'wl-' + entGID,
            catID: entID,
            dataMgr: this.dataMgr,
            id: paneID,
            editDiv: $("." + paneID, this.curLayout)[0]
          }
          if (catalog.value == "GD" || catalog.value == "MW" || catalog.value == "MG") {
            catCode = catalog.value.toLowerCase();
            config['dictionary'] = catCode;
            if (catalog.value == "GD") {
              config['selectURL'] = basepath + '/plugins/dictionary/index.php?db=' + dbName +'&dictionary=' + catCode + '&searchstring={{value}}&searchtype=F';
            } else if (catalog.value == "MG") {
              config['selectURL'] = basepath + '/plugins/dictionary/index.php?db=' + dbName +'&dictionary=' + catCode + '&searchstring={{value}}&searchtype={{type}}';
            }
            config['url'] = basepath + '/plugins/dictionary/index.php?dictionary=' + catCode + '&searchstring=a&searchtype=F&strJSON={"dictionary":"' + catCode + '","mode":"getdictionarystats"}';
            config['entGID'] = entGID;
            this.editors[paneID] = new EDITORS.FrameV(config);
          } else if (catalog.value == "BG") {
            catCode = catalog.value.toLowerCase();
            config['bibliography'] = catCode;
            config['url'] = basepath + '/plugins/bibliography/a_bibliography.php?initial=a';
            config['entGID'] = entGID;
            this.editors[paneID] = new EDITORS.FrameV(config);
          } else {
            config['entGID'] = 'wl-' + entGID;
            this.editors[paneID] = new EDITORS.WordlistVE(config);
          }
          break;
        case 'rpt': //launch report
          switch (rptID) {
            case 'wl'://word list
              config = {
                eventMgr: layoutMgr,
                layoutMgr: layoutMgr,
                entGID: 'wl-' + entGID,
                dataMgr: this.dataMgr,
                id: paneID,
                editDiv: $("." + paneID, this.curLayout)[0]
              }
              if (prefix == "edn" || prefix == "cat") {
                config[prefix + 'ID'] = entID;
                this.editors[paneID] = new EDITORS.WordlistVE(config);
              } else {
                $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">unknown report entity.</div>');
              }
              //instantiate wordlist VE with GID catID will launch Lemma Glossary while ednID with generate word list.
              break;
            case 'sx'://syntax
              config = {
                eventMgr: layoutMgr,
                layoutMgr: layoutMgr,
                entGID: 'sx-' + entGID,
                dataMgr: this.dataMgr,
                id: paneID,
                editDiv: $("." + paneID, this.curLayout)[0]
              }
              if (prefix == "edn") {
                config['ednID'] = entID;
                this.editors[paneID] = new EDITORS.SyntaxVE(config);
              } else {
                $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">unknown report entity.</div>');
              }
              break;
            case 'ph'://Phonology
              rptLabel = 'Phonology';
              $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">' + rptLabel + ' report is under construction.</div>');
              break;
            case 'pa'://Palaeography
              config = {
                eventMgr: layoutMgr,
                layoutMgr: layoutMgr,
                entGID: 'pa-' + entGID,
                dataMgr: this.dataMgr,
                id: paneID,
                editDiv: $("." + paneID, this.curLayout)[0]
              }
              if (prefix == "edn" || prefix == "cat") {
                config[prefix + 'ID'] = entID;
                this.editors[paneID] = new EDITORS.PaleoVE(config);
              } else {
                $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">Palaeography Viewer/Tagger currently starts with an edition entity.</div>');
              }
              break;
            case 'tr'://Translation
              config = {
                eventMgr: layoutMgr,
                edition: entity,
                layoutMgr: layoutMgr,
                entGID: 'tr-' + entGID,
                dataMgr: this.dataMgr,
                id: paneID,
                editDiv: $("." + paneID, this.curLayout)[0]
              }
              if (prefix == "edn") {
                config[prefix + 'ID'] = entID;
                this.editors[paneID] = new EDITORS.TranslationV(config);
              } else {
                $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">Translation Viewer currently starts with an edition entity.</div>');
              }
              break;
            case 'cy'://chāyā
              config = {
                eventMgr: layoutMgr,
                edition: entity,
                transType: 'chaya',
                layoutMgr: layoutMgr,
                entGID: 'cy-' + entGID,
                dataMgr: this.dataMgr,
                id: paneID,
                editDiv: $("." + paneID, this.curLayout)[0]
              }
              if (prefix == "edn") {
                config[prefix + 'ID'] = entID;
                this.editors[paneID] = new EDITORS.TranslationV(config);
              } else {
                $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">Chaya Viewer currently starts with an edition entity.</div>');
              }
              break;
            case 'sq'://sequence editor/viewer
              config = {
                eventMgr: layoutMgr,
                edition: entity,
                layoutMgr: layoutMgr,
                entGID: 'sq-' + entGID,
                dataMgr: this.dataMgr,
                id: paneID,
                editDiv: $("." + paneID, this.curLayout)[0]
              }
              if (prefix == "edn") {
                this.editors[paneID] = new EDITORS.SequenceVE(config);
              } else {
                $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">Sequence Viewer/Editor currently starts with an edition entity.</div>');
              }
              break;
            case 'mo'://Morphology
              rptLabel = 'Morphology';
              $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">' + rptLabel + ' report is under construction.</div>');
              break;
            case 'pr'://Parallels
              rptLabel = 'Parallels';
              $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">' + rptLabel + ' report is under construction.</div>');
              break;
            case 'bi'://Bibliography
              rptLabel = 'Bibliography';
              $("." + paneID, this.curLayout).html('<div class="panelMsgDiv">' + rptLabel + ' report is under construction.</div>');
              break;
            default:
              DEBUG.log("warn", "No report generator for rptID " + rpt + " and entity " + entGID);
          }
          break;
        default:
          DEBUG.log("warn", "No VE for content type " + contentType + " and entity " + entGID);
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
    DEBUG.traceExit("layoutMgr.loadPaneContent", " entTag " + tag + " for " + paneID);
  },


  /**
  * Create
  *
  * @param btnIdPrefix
  */

  createButtonBar: function (btnIdPrefix) {
    DEBUG.traceEntry("layoutMgr.createButtonBar", "");
    var layoutMgr = this;
    btnBar = $('<div id="' + btnIdPrefix + 'BtnBar">' +
      '<div id="' + btnIdPrefix + 'BtnText" class="fontIconBtn">' +
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
    $('#' + btnIdPrefix + 'BtnText', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchContentPage('search');
      });
    $('#' + btnIdPrefix + 'BtnLexicon', btnBar).unbind('click')
      .bind('click', function () {
        //                                             layoutMgr.switchContentPage('lexica');
        window.location.href = 'plugins/dictionary/m_dictionary.php';
      });
    $('#' + btnIdPrefix + 'BtnReference', btnBar).unbind('click')
      .bind('click', function () {
        //                                             layoutMgr.switchContentPage('biblio');
        window.location.href = 'plugins/bibliography/a_bibliography.php';
      });
    $('#' + btnIdPrefix + 'BtnLanding', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchContentPage('landing');
      });
    DEBUG.traceExit("layoutMgr.createButtonBar", "");
    return btnBar;
  },


  /**
  * create logo footer
  */

  createMainFooter: function () {
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

  getContentPage: function () {
    return this.currentPage;
  },


  /**
  * switch content page
  *
  * @param string pageID Identify which page to switch to
  * @param string focusPane identifying which pane receives focus
  */

  switchContentPage: function (pageID, focusPane) {
    DEBUG.traceEntry("layoutMgr.switchContentPage", "");
    var layoutMgr = this, newPage, txtID, textEntity;
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
            this.focusHandler(null, (focusPane && focusPane.match(/pane[1|2|3]$/i)) ? focusPane.toLowerCase() :
              (this.lastTextFocusPaneID ? this.lastTextFocusPaneID : "pane1"));
          }
          this.synchScrollBtn.removeAttr("disabled");
        }
        break;
      case 'search':
        if (this.searchPage) {
          newPage = "searchPage";
          this.focusHandler(null, "searchVE");
          this.synchScrollBtn.attr("disabled", "disabled");
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
    } else {
      alert("Unable to launch page for " + pageID + " likely not implemented yet!");
    }
    DEBUG.traceExit("layoutMgr.switchContentPage", "");
  },

  showCatBtnUI: function () {
    return this.catBtnUI && this.catBtnUI.addClass("showCatBtn");
  },

  hideCatBtnUI: function () {
    return this.catBtnUI && this.catBtnUI.removeClass("showCatBtn");
  },


  /**
  * create Layout button bar
  */

  createLayoutButtonBar: function () {
    DEBUG.traceEntry("layoutMgr.createLayoutButtonBar", "");
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
    $('#H12', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchEditLayout('H12');
      });
    $('#V12', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchEditLayout('V12');
      });
    $('#V12H3', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchEditLayout('V12H3');
      });
    $('#H1V23', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchEditLayout('H1V23');
      });
    $('#H12V3', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchEditLayout('H12V3');
      });
    $('#H123', btnBar).unbind('click')
      .bind('click', function () {
        layoutMgr.switchEditLayout('H123');
      });
    DEBUG.traceExit("layoutMgr.createLayoutButtonBar", "");
    return btnBar;
  },


  /**
  * create Landing page
  */

  createLandingPage: function () {
    DEBUG.traceEntry("layoutMgr.createLandingPage", "");
    var layoutMgr = this;
    //create sign in UI
    if (this.landingPage) {
      delete this.landingPage;
    }
    this.landingPage = $('<div id="landingPage">' +
      '<h1>' + this.projTitle + '</h1>' +
      '<h2>Research Environment for Ancient Documents</h2>' +
      '</div>');
    this.landingPage.append(this.createButtonBar('main'));
    this.landingPage.append(this.createMainFooter());
    this.landingPage.append($('<div id="rightPanel"></div>'));
    //display UI
    this.contentDiv.append(this.landingPage);
    DEBUG.traceExit("layoutMgr.createLandingPage", "");
  },

  getUsername: function () {
    return this.userVE.getUsername();
  },

  isLoggedIn: function () {
    return this.userVE.isLoggedIn();
  },



  /**
  * create new edition wizard
  */

  initEditionWizard: function (ckn = null) {
    var layoutMgr = this;
    DEBUG.traceEntry("layoutMgr.initEditionWizard", "");
    $('#btnImportFreeTextLines').jqxButton({ width: '120px', disabled: true });
    $('#btnImportFreeTextLines').unbind('click').bind('click', function (e) { layoutMgr.importPlainTextEdition(); });
    $('#btnEditionValidate').jqxButton({ width: '140px', disabled: false });
    $('#btnEditionValidate').unbind('click').bind('click', function (e) { layoutMgr.validateNewEdition(false); });
    $('#btnEditionCommit').jqxButton({ width: '140px', disabled: true });
    $('#btnEditionCommit').unbind('click').bind('click', function (e) { layoutMgr.validateNewEdition(true); });
    $('#btnEditionWizardCancel').jqxButton({ width: '80px', disabled: false });
    $('#txtInv').val((ckn ? ckn : null));
    $('#ednTitle').val();
    $('#freetextCheckBox').jqxCheckBox({ width: '250px', checked: false});
    $('#freetextCheckBox').unbind('change').bind('change', function (e) {
      var checked = e.args.checked;
      $('#btnEditionCommit').jqxButton({disabled:true});
      $('#importResultsContent').html("");
      if (checked) {
        $('#btnImportFreeTextLines').jqxButton({disabled:false});
        $('#btnEditionValidate').jqxButton({disabled:true});
      } else {
        $('#btnImportFreeTextLines').jqxButton({disabled:true});
        $('#btnEditionValidate').jqxButton({disabled:false});
      }
    });
    DEBUG.traceExit("layoutMgr.initEditionWizard", "");
  },



  /**
  * validate new edition
  */

 validateNewEdition: function (saveAfterValidation = false) {
  DEBUG.traceEntry("layoutMgr.validateNewEdition", "");
  var layoutMgr = this, savedata = {},
      transcr = $('#transcript').val();
  $('#btnEditionCommit').jqxButton({ disabled: !saveAfterValidation });
  $('#importResultsContent').val('')
  if (!transcr) return;
  savedata['txtInv'] = $('#txtInv').val();
  savedata['ednTitle'] = $('#ednTitle').val();
  savedata['transcription'] = transcr;
  savedata['saveAfterValidation'] = saveAfterValidation?1:0;
  //make ajax call to create plain Text
  $.ajax({
    type: "POST",
    dataType: 'json',
    url: basepath + '/services/validateNewEditionString.php?db=' + dbName,
    data: savedata,
    asynch: true,
    success: function (data, status, xhr) {
      var srchVE = layoutMgr.editors.searchVE;
      if (data && typeof data == 'object' && data.success) {
        if (data.validateMsg) {
          $('#importResultsContent').html(data.validateMsg);
          $('#btnEditionCommit').jqxButton({ disabled: false });
        } else if (data.commitMsg) {
          layoutMgr.dataMgr.updateLocalCache(data, srchVE.getCursorTextID());
          if (data && data.entities && data.entities.insert && data.entities.insert.edn) {
            var ednID, text = srchVE.dataMgr.getEntity('txt', srchVE.getCursorTextID());
            if (text) {
              if (!text.ednIDs) {
                text.ednIDs = [];
              }
              for (ednID in data.entities.insert.edn) {
                if (text.ednIDs.indexOf(ednID) == -1) {
                  text.ednIDs.push(ednID);
                }
              }
              srchVE.dataMgr.updateTextResourcesCache(text.id);
            }
          }
          srchVE.updateCursorInfoBar();
          $('#btnEditionCommit').jqxButton({ disabled: true });
          $('#importResultsContent').html(data.commitMsg);
          $('#transcript').val('')
        }
      } else {
        $('#importResultsContent').html(data.errors.join(' '));
        $('#btnEditionCommit').jqxButton({ disabled: true });
      }
    },
    error: function (xhr, status, error) {
      // parsing text input failed.
      errStr = "<div class=\"errmsg\">An error occurred while trying to parser multiline text input. Error: " + error + "</div>";
      $('#importResultsContent').val(errStr);
    }
  });// end ajax
  DEBUG.traceExit("layoutMgr.validateNewEdition", "");
},



  /**
  * import new edition as freetext lines
  */

 importPlainTextEdition: function () {
  DEBUG.traceEntry("layoutMgr.importPlainTextEdition", "");
  var layoutMgr = this, savedata = {},
      transcr = $('#transcript').val();
  savedata['txtInv'] = $('#txtInv').val();
  savedata['ednTitle'] = $('#ednTitle').val();
  savedata['transcription'] = transcr;
  if (!$('#freetextCheckBox').jqxCheckBox('checked')) {
    savedata.freetextImport = false;
  }
  //make ajax call to create plain Text
  $.ajax({
    type: "POST",
    dataType: 'json',
    url: basepath + '/services/createPlainTextEdition.php?db=' + dbName,
    data: savedata,
    asynch: true,
    success: function (data, status, xhr) {
      var srchVE = layoutMgr.editors.searchVE;
      if (data && data.resultMsg) {
        $('#importResultsContent').html(data.resultMsg);
      }
      if (typeof data == 'object' && data.success && data.entities) {
        layoutMgr.dataMgr.updateLocalCache(data, srchVE.getCursorTextID());
        if (data && data.entities && data.entities.insert && data.entities.insert.edn) {
          var ednID, text = srchVE.dataMgr.getEntity('txt', srchVE.getCursorTextID());
          if (text) {
            if (!text.ednIDs) {
              text.ednIDs = [];
            }
            for (ednID in data.entities.insert.edn) {
              if (text.ednIDs.indexOf(ednID) == -1) {
                text.ednIDs.push(ednID);
              }
            }
            srchVE.dataMgr.updateTextResourcesCache(text.id);
          }
        }
        srchVE.updateCursorInfoBar();
        $('#btnEditionCommit').jqxButton({ disabled: true });
        $('#btnEditionValidate').jqxButton({ disabled: true });
        $('#transcript').val('')
      }
    },
    error: function (xhr, status, error) {
      // parsing text input failed.
      errStr = "<div class=\"errmsg\">An error occurred while trying create plain freetext edition. Error: " + error + "</div>";
      $('#importResultsContent').val(errStr);
    }
  });// end ajax
  DEBUG.traceExit("layoutMgr.importPlainTextEdition", "");
},



/**
  * create new edition wizard
  */

  createNewEditionWizard: function (ckn = null) {
    DEBUG.traceEntry("layoutMgr.createNewEditionWizard", "");
    var layoutMgr = this, mainContainer = $('body'),
      offset = mainContainer.offset(),
      wzWidth, wzHeight;

    offset.xcenter = mainContainer.innerWidth() / 4;
    offset.ycenter = mainContainer.innerHeight() / 4;
    wzWidth = 3 * offset.xcenter + 300;
    wzHeight = 3 * offset.ycenter + 400;
    layoutMgr.editionWizard = $('#editionWizard').jqxWindow({
      width: wzWidth,
      height: wzHeight, resizable: true, isModal: true,
      cancelButton: $('#btnEditionWizardCancel'),
      position: {
        x: offset.left + 100,
        y: offset.top + 100
      },
      initContent: function () { layoutMgr.initEditionWizard(ckn); }
    });
    layoutMgr.editionWizard.jqxWindow('close');
    DEBUG.traceExit("layoutMgr.createNewEditionWizard", "");
  },


  /**
    * remove Text wizard
    */

   removeTextWizard: function () {
    var $textWizContentDiv = $('#txtWizardContent'),
        $textSelector = $('#srfTextSelect'),
        $textSep = $('#textSep'),
        $textInfoDiv = $('#textInfoDiv');
    if ($textInfoDiv.length) {
      $textInfoDiv.remove();
    }
    if ($textSep.length) {
      $textSep.remove();
    }
    if ($textSelector.length) {
      $textSelector.jqxComboBox('destroy');
    }
    if ($textWizContentDiv.length) {
        $textWizContentDiv.remove();
    }
  },


  /**
  * init text wizard
  */

  initTextWizard: function (itmID,srfdisplay,srfTxtIDs,srfID) {
    DEBUG.traceEntry("layoutMgr.initTextWizard", "");
    var layoutMgr = this,
        $textWizContentDiv,
        txtDataUrl = basepath + "/services/getItemTextsList.php?db=" + dbName + "&itmID=" + itmID,
        txtDatasource = {
          datatype: "jsonp",
          datafields: [
            { name: 'txtid' },
            { name: 'ckn' },
            { name: 'txtdisplay' },
            { name: 'txttitle' }
          ],
          url: txtDataUrl,
        },
        txtDataAdapter = new $.jqx.dataAdapter(txtDatasource),
        $textSelector = $('#srfTextSelect');
    if ($textSelector && $textSelector.length) {
      //remove text UI for rebuild
      layoutMgr.removeTextWizard();
    }
    layoutMgr.modelWizContentDiv.append('<div id="textSep" class="wzModelSep"></div>');
    layoutMgr.modelWizContentDiv.append(
      '<div id="txtWizardContent">' +
        '<div class="wzLabel">' +
          '<div style:"float :right; padding-right:5px">Text :</div>' +
          '<button class="wzBtnAddEntity" title="Add new text to surface '+srfdisplay+'" >Add text</button>' +
        '</div>' +
        '<div class="wzCombo" id="srfTextSelect"/>' +
        '<div class="wzInfoDiv" id="textInfoDiv"></div>' +
      '</div>');
    $textWizContentDiv = $('#txtWizardContent');
    $btnNewText = $('button.wzBtnAddEntity', $textWizContentDiv);
    $btnNewText.unbind('click').bind('click', function(e) {
      //if service for auto inv. no. call to get next inventory
      if (layoutMgr.newCatalogIDService) {
        //jqAjax asynch save
        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: layoutMgr.newCatalogIDService + '?itmID='+itmID, //caution dependency on context having basepath and dbName
          async: true,
          success: function (data, status, xhr) {
            var ckn = data[0], blankText = false;
            if (!ckn) {
              blankText = true;
            }
            //call service to create new text with inventory number
            layoutMgr.modelWizardSaveText(null,ckn,null,null,blankText,itmID, function (success){
              if (!success) {
                alert("Unable to save text entity");
              }
              layoutMgr.initTextWizard(itmID,srfdisplay,srfTxtIDs,srfID);
            });
          }
        });
      } else {
        layoutMgr.modelWizardSaveText(null,null,null,null,true,itmID, function (success){
          if (!success) {
            alert("Unable to save text entity");
          }
          layoutMgr.initTextWizard(itmID,srfdisplay,srfTxtIDs,srfID);
        });
      }
    });
    $textSelector = $('#srfTextSelect', $textWizContentDiv);
    $textSelector.prop('srfTxtIDs', srfTxtIDs);
    function refreshTextInfo() {
      var textInfo, txtTag,
      checkedItems = $textSelector.jqxComboBox('getCheckedItems'),
      $textInfoDiv = $('#textInfoDiv');
      $textInfoDiv.children().remove();
      if (!checkedItems || checkedItems.length == 0) {
        $textInfoDiv.append('<div class="wzInfoHdr"><strong>No linked texts</strong></div>');
      } else {
        $textInfoDiv.append('<div class="wzInfoHdr"><strong>'+checkedItems.length+' linked text(s) :</strong></div>');
        for (i=0; i<checkedItems.length; i++) {
          textInfo = checkedItems[i].originalItem;
          txtTag = "txt"+textInfo.txtid;
          $textInfoDiv.append('<div class="wzInfoRow Top">' +
            '<div class="wzEntInfoLbl">ID:</div>' +
            '<div class="wzEntInfo readonly" id="'+txtTag+'_id">' + textInfo.txtid + '</div>' +
            '</div>');
          $textInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Inv.No.:</div>' +
            '<div class="wzEntInfo" id="'+txtTag+'_ckn">' + textInfo.ckn + '</div>' +
            '<input class="wzEntInfoInput" value="' + textInfo.ckn + '"/>' +
            '</div>');
          $textInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Title:</div>' +
            '<div class="wzEntInfo" id="'+txtTag+'_title">' + textInfo.txttitle + '</div>' +
            '<input class="wzEntInfoInput" value="' + textInfo.txttitle + '"/>' +
            '</div>');
        }
        $('.wzEntInfo:not(readonly)',$textInfoDiv).unbind('click').bind('click',function(event){
            var element = this, id = element.id,
                $fieldDisplayDiv = $(element),
                $fieldInput = $(element.nextSibling);
            $fieldDisplayDiv.addClass('edit');
            $fieldInput.addClass('edit');
            $fieldInput.focus().select();
            $fieldInput.unbind('keydown').bind('keydown', function(event){
              var val = $(this).val(),
                  codes = id.split('_'),
                  key = codes[1],
                  txtTag = codes[0],
                  txtID = txtTag.substr(3);
              if(event.keyCode == 13) {
                $fieldDisplayDiv.text(val);
                $fieldDisplayDiv.removeClass('edit');
                $fieldInput.removeClass('edit');
                if (key == 'ckn') {
                  layoutMgr.modelWizardSaveText(txtID,val,null,null,false,null, function (success){
                    if (!success) {
                      alert("Unable to update text inv. no.");
                    }
                    layoutMgr.initTextWizard(itmID,srfdisplay,srfTxtIDs,srfID);
                  });
                } else if (key == 'title') {
                  layoutMgr.modelWizardSaveText(txtID,null,val,null,false,null, function (success){
                    if (!success) {
                      alert("Unable to update text title");
                    }
                    layoutMgr.initTextWizard(itmID,srfdisplay,srfTxtIDs,srfID);
                  });
                }
              }
            });
            //alert("Edit "+id+" with value: "+val);
        });
      }
    }
    $textSelector.jqxComboBox(
      {
        width: 300,
        height: 25,
        autoComplete: true,
        checkboxes:true,
        multiSelect: true,
        searchMode: 'containsignorecase',
        placeHolder: "Link text ...",
        source: txtDataAdapter,
        popupZIndex: 18010,
        displayMember: "txtdisplay",
        valueMember: "txtid",
      });
    $textSelector.unbind('bindingComplete').bind('bindingComplete', function (event) {
      var saveSrfTxtIDs = $textSelector.prop('srfTxtIDs');
      $textSelector.find('input').css('width','100%');//force combo input to full width
      $textSelector.unbind('checkChange');
      if (saveSrfTxtIDs.length > 0) {
        $textSelector.jqxComboBox('getItems').forEach(textItem => {
          if (saveSrfTxtIDs.indexOf(textItem.value) > -1) {
            $textSelector.jqxComboBox('checkItem',textItem);
          }
        });
      }

      $textSelector.bind('checkChange', function (event) {
        if (event.args) {
          var selText = event.args.item;
          if (selText) {
            var text = selText.originalItem,
                txtID = text.txtid,
                bAddTextID = selText.checked;
            if (bAddTextID) {   
              //link text to surface
              layoutMgr.modelWizardSaveSurface(srfID,null,null,null,null,false,txtID,null,function (success){
                if (!success) {
                  alert("Unable to link text to surface entity");
                }
                setTimeout(function() {
                            var surfTextIds = layoutMgr.dataMgr.entities.srf[srfID].textIDs;
                            if (!surfTextIds) {
                              surfTextIds = [];
                            }
                            layoutMgr.initTextWizard(itmID,srfdisplay,surfTextIds,srfID);
                          }, 50);
              });
            } else  {   
              //unlink text from surface
              layoutMgr.modelWizardSaveSurface(srfID,null,null,null,null,false,null,txtID,function (success){
                if (!success) {
                  alert("Unable to link text to surface entity");
                }
                setTimeout(function() {
                            var surfTextIds = layoutMgr.dataMgr.entities.srf[srfID].textIDs;
                            if (!surfTextIds) {
                              surfTextIds = [];
                            }
                            layoutMgr.initTextWizard(itmID,srfdisplay,surfTextIds,srfID);
                          }, 50);
              });
            }
          }
        }
      });
      refreshTextInfo();
    });

    DEBUG.traceExit("layoutMgr.initTextWizard", "");
  },


  /**
  * model wizard save text entity
  *
  * @param int txtID
  * @param string ckn Inventory id for this text
  * @param string title
  * @param string refLabel Short label for identifying text
  * @param boolean newText Flag to signal next text creation
  * @param int itmID item id to link text to
  * @param function cb is a callback function with a flag to signal success
  */

  modelWizardSaveText: function(txtID, ckn, title, refLabel, newText, itmID, cb) {
    var savedata ={}, layoutMgr = this;
    DEBUG.traceEntry("layoutMgr.modelWizardSaveText");
    if (txtID) {
      savedata["txtID"] = txtID;
    }
    if (ckn != null) {
      savedata["ckn"] = ckn;
    }
    if (itmID != null) {
      savedata["itmID"] = itmID;
    }
    if (title != null) {
      savedata["title"] = title;
    }
    if (refLabel != null) {
      savedata["ref"] = refLabel;
    }
    if (newText) {
      savedata["addNewText"] = 1;
    }
    if (Object.keys(savedata).length > 0) {
      //jqAjax asynch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveText.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var txtID, text;
              if (typeof data == 'object' && data.success && data.entities &&
                  layoutMgr.dataMgr ) {
                //update data
                layoutMgr.dataMgr.updateLocalCache(data,null);
                if ((data.entities.update && data.entities.update.txt) ||
                                            (data.entities.insert && data.entities.insert.txt)) {
                  if (data.entities.insert) {
                    txtID = Object.keys(data.entities.insert.txt)[0];
                    text = layoutMgr.dataMgr.getEntity('txt',txtID);
                    if (text && layoutMgr.editors && layoutMgr.editors.searchVE && layoutMgr.editors.searchVE.addTextRow &&
                        typeof layoutMgr.editors.searchVE.addTextRow  == "function") {
                      layoutMgr.editors.searchVE.addTextRow(txtID, text.CKN, text.title);
                      if (layoutMgr.editors.searchVE.showProperties &&
                          typeof layoutMgr.editors.searchVE.showProperties  == "function") {
                        layoutMgr.editors.searchVE.showProperties(true);
                      }
                    }
                    if (text) {
                      layoutMgr.dataMgr.updateTextResourcesCache(txtID);
                    }
                  } else {
                    txtID = Object.keys(data.entities.update.txt)[0];
                    layoutMgr.dataMgr.updateTextResourcesCache(txtID);
                  }
                }
//                layoutMgr.initTextWizard();
              }
              if (data.errors) {
                alert("An error occurred while trying to save text data. Error: " + data.errors.join());
                if (cb) {
                  cb(false);
                }
              } else if (cb){
                cb(true);
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save text data. Error: " + error);
              if (cb) {
                cb(false);
              }
        }
      });
    }
    DEBUG.traceExit("layoutMgr.modelWizardSaveText");
  },


  /**
    * remove surface wizard
    */

   removeSurfaceWizard: function () {
    this.removeTextWizard();
    var $surfWizContentDiv = $('#srfWizardContent'),
        $surfSelector = $('#srfSelect'),
        $surfSep = $('#surfSep'),
        $surfInfoDiv = $('#surfaceInfoDiv');
    if ($surfInfoDiv.length) {    
      $surfInfoDiv.remove();
    }
    if ($surfSep.length) {    
      $surfSep.remove();
    }
    if ($surfSelector.length) {    
      $surfSelector.jqxComboBox('destroy');
    }
    if ($surfWizContentDiv.length) {    
      $surfWizContentDiv.remove();
    }
  },


  /**
  * init surface wizard
  */

  initSurfaceWizard: function (frgID,frgdisplay,itmID) {
    DEBUG.traceEntry("layoutMgr.initSurfaceWizard", "");
    var layoutMgr = this,
        $surfWizContentDiv ,
        srfDataUrl = basepath + "/services/getFragmentSurfacesList.php?db=" + dbName + "&frgID=" + frgID,
        srfDatasource = {
          datatype: "jsonp",
          datafields: [
            { name: 'srf_id' },
            { name: 'srftxtids' },
            { name: 'srfdisplay' },
            { name: 'srf_label' },
            { name: 'srf_number' }
          ],
          url: srfDataUrl,
        },
        srfDataAdapter = new $.jqx.dataAdapter(srfDatasource),
        $surfSelector = $('#srfSelect');
    if ($surfSelector && $surfSelector.length) {
      //remove fragment UI for rebuild
      layoutMgr.removeSurfaceWizard();
    }
    layoutMgr.modelWizContentDiv.append('<div id="surfSep" class="wzModelSep"></div>');
    layoutMgr.modelWizContentDiv.append(
      '<div id="srfWizardContent">' +
        '<div class="wzLabel">' +
          '<div style:"float :right; padding-right:5px">Surface :</div>' +
          '<button class="wzBtnAddEntity" title="Add new surface to fragment '+frgdisplay+'" >Add surface</button>' +
        '</div>' +
        '<div class="wzCombo" id="srfSelect"/>' +
        '<div class="wzInfoDiv" id="surfaceInfoDiv"></div>' +
      '</div>');

    $surfWizContentDiv = $('#srfWizardContent');
    $btnNewSurface = $('button.wzBtnAddEntity', $surfWizContentDiv);
    $btnNewSurface.unbind('click').bind('click', function(e) {
      layoutMgr.modelWizardSaveSurface(null,null,null,null,null,frgID,null,null,function (success){
        if (!success) {
          alert("Unable to create new surface entity");
        }
        setTimeout(function() {
                    layoutMgr.initSurfaceWizard(frgID,frgdisplay,itmID);
                  }, 50);
      });
    });
    $surfSelector = $('#srfSelect',$surfWizContentDiv);
    $surfSelector.prop('itemID', itmID);
    $surfSelector.jqxComboBox(
      {
        width: 300,
        height: 25,
        autoComplete: true,
        searchMode: 'containsignorecase',
        placeHolder: "Select surface ...",
        source: srfDataAdapter,
        selectedIndex: 0,
        popupZIndex: 18010,
        displayMember: "srfdisplay",
        valueMember: "srf_id",
      });
    $surfSelector.unbind('select').bind('select', function (event) {
      if (event.args) {
        var surf = event.args.item,
            itemID = $surfSelector.prop('itemID');
        if (surf) {
          var surfInfo = surf.originalItem,srfTag = 'srf'+surfInfo.srf_id,
            surfTextIds = [],
            $surfInfoDiv = $('#surfaceInfoDiv', $surfWizContentDiv);
          if (surfInfo.srftxtids && surfInfo.srftxtids.length) {
            surfTextIds = surfInfo.srftxtids.replace("{",'').replace('}','').split(',');
          }
          $surfInfoDiv.children().remove();
          $surfInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">ID:</div>' +
            '<div class="wzEntInfo readonly" id="'+srfTag+'_id">' + surfInfo.srf_id + '</div>' +
            '</div>');
          $surfInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Label:</div>' +
            '<div class="wzEntInfo" id="'+srfTag+'_label">' + surfInfo.srf_label + '</div>' +
            '<input class="wzEntInfoInput" value="' + surfInfo.srf_label + '"/>' +
            '</div>');
          $surfInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Number:</div>' +
            '<div class="wzEntInfo" id="'+srfTag+'_number">' + surfInfo.srf_number + '</div>' +
            '<input class="wzEntInfoInput" value="' + surfInfo.srf_number + '"/>' +
            '</div>');
          $('.wzEntInfo:not(readonly)',$surfInfoDiv).unbind('click').bind('click',function(event){
            var element = this, id = element.id,
                $fieldDisplayDiv = $(element),
                $fieldInput = $(element.nextSibling);
            $fieldDisplayDiv.addClass('edit');
            $fieldInput.addClass('edit');
            $fieldInput.focus().select();
            $fieldInput.unbind('keydown').bind('keydown', function(event){
              var val = $(this).val(),
                  codes = id.split('_'),
                  key = codes[1],
                  srfTag = codes[0],
                  srfID = srfTag.substr(3);
              if(event.keyCode == 13) {
                $fieldDisplayDiv.text(val);
                $fieldDisplayDiv.removeClass('edit');
                $fieldInput.removeClass('edit');
                if (key == 'label') {
                  layoutMgr.modelWizardSaveSurface(srfID,null,val,null,null,null,null,null,function (success){
                    if (!success) {
                      alert("Unable to update surface entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initSurfaceWizard(frgID,frgdisplay,itmID);
                              }, 50);
                  });
                } else if (key == 'number') {
                  layoutMgr.modelWizardSaveSurface(srfID,null,null,val,null,null,null,null,function (success){
                    if (!success) {
                      alert("Unable to update surface entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initSurfaceWizard(frgID,frgdisplay,itmID);
                              }, 50);
                  });
                }
              }
            });
            //alert("Edit "+id+" with value: "+val);
          });
          layoutMgr.initTextWizard(itemID,surfInfo.srfdisplay,surfTextIds,surfInfo.srf_id);
        }
      }
    });
  DEBUG.traceExit("layoutMgr.initSurfaceWizard", "");
},


  /**
  * model wizard save surface entity
  *
  * @param int srfID
  * @param string description of this surface
  * @param string label commonly used for this surface
  * @param int number surface ordinal
  * @param int layer indicator for this surface
  * @param int newSurfaceFrgID Fragment ID to link new surface to
  * @param int addTxtID text id to link to surface
  * @param int removeTxtID text id to unlink from surface
  * @param function cb is a callback function with a flag to signal success
  */

 modelWizardSaveSurface: function(srfID, description, label, number, layer, newSurfaceFrgID, addTxtID, removeTxtID, cb) {
  var savedata ={}, layoutMgr = this;
  DEBUG.traceEntry("layoutMgr.modelWizardSaveSurface");
  if (srfID) {
    savedata["srfID"] = srfID;
  }
  if (description != null) {
    savedata["description"] = description;
  }
  if (label != null) {
    savedata["label"] = label;
  }
  if (number != null) {
    savedata["number"] = number;
  }
  if (layer != null) {
    savedata["layer"] = layer;
  }
  if (addTxtID != null) {
    savedata["addTxtID"] = addTxtID;
  }
  if (removeTxtID != null) {
    savedata["removeTxtID"] = removeTxtID;
  }
  if (newSurfaceFrgID) {
    savedata["newSurfaceFrgID"] = newSurfaceFrgID;
  }
  if (Object.keys(savedata).length > 0) {
    //jqAjax asynch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveSurface.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            var txtID, text;
            if (typeof data == 'object' && data.success && data.entities &&
                layoutMgr.dataMgr ) {
              //update data
              layoutMgr.dataMgr.updateLocalCache(data,null);
              if ((data.entities.update && data.entities.update.txt) ||
                                          (data.entities.insert && data.entities.insert.txt)) {
                if (data.entities.insert) {
                  txtID = Object.keys(data.entities.insert.txt)[0];
                  text = layoutMgr.dataMgr.getEntity('txt',txtID);
                  if (text && layoutMgr.editors && layoutMgr.editors.searchVE && layoutMgr.editors.searchVE.addTextRow &&
                      typeof layoutMgr.editors.searchVE.addTextRow  == "function") {
                    layoutMgr.editors.searchVE.addTextRow(txtID, text.CKN, text.title);
                    if (layoutMgr.editors.searchVE.showProperties &&
                        typeof layoutMgr.editors.searchVE.showProperties  == "function") {
                      layoutMgr.editors.searchVE.showProperties(true);
                    }
                  }
                  if (text) {
                    layoutMgr.dataMgr.updateTextResourcesCache(txtID);
                  }
                } else {
                  txtID = Object.keys(data.entities.update.txt)[0];
                  layoutMgr.dataMgr.updateTextResourcesCache(txtID);
                }
              }
//              layoutMgr.initTextWizard();
            }
            if (data.errors) {
              alert("An error occurred while trying to save text data. Error: " + data.errors.join());
              if (cb) {
                cb(false);
              }
            } else if (cb){
              cb(true);
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to save text data. Error: " + error);
            if (cb) {
              cb(false);
            }
      }
    });
  }
  DEBUG.traceExit("layoutMgr.modelWizardSaveSurface");
},


  /**
    * remove fragment wizard
    */

   removeFragmentWizard: function () {
    this.removeSurfaceWizard();
    var $fragWizContentDiv = $('#frgWizardContent'),
        $fragSelector = $('#frgSelect'),
        $fragSep = $('#fragSep'),
        $fragInfoDiv = $('#fragmwzEntInfoDiv');
    if ($fragInfoDiv.length) {    
      $fragInfoDiv.remove();
    }
    if ($fragSep.length) {    
      $fragSep.remove();
    }
    if ($fragSelector.length) {    
      $fragSelector.jqxComboBox('destroy');
    }
    if ($fragWizContentDiv.length) {    
      $fragWizContentDiv.remove();
    }
  },


  /**
  * init fragment wizard
  */

 initFragmentWizard: function (prtID, prtdisplay, itmID) {
  DEBUG.traceEntry("layoutMgr.initFragmentWizard", "");
  var layoutMgr = this, savedItmID = itmID,
      $fragWizContentDiv,
      frgDataUrl = basepath + "/services/getPartFragmentsList.php?db=" + dbName + "&prtID=" + prtID,
      frgDatasource = {
        datatype: "jsonp",
        datafields: [
          { name: 'frg_id' },
          { name: 'frg_description' },
          { name: 'frgdisplay' },
          { name: 'frg_label' }
        ],
        url: frgDataUrl,
      },
      frgDataAdapter = new $.jqx.dataAdapter(frgDatasource),
      $fragSelector = $('#frgSelect');
  if ($fragSelector && $fragSelector.length) {
    //remove fragment UI for rebuild
    layoutMgr.removeFragmentWizard();
  }
  layoutMgr.modelWizContentDiv.append('<div id="fragSep" class="wzModelSep"></div>');
  layoutMgr.modelWizContentDiv.append(
      '<div id="frgWizardContent">' +
        '<div class="wzLabel">' +
          '<div style:"padding-right:5px">Fragment :</div>' +
          '<button class="wzBtnAddEntity" title="Add new fragment to part '+prtdisplay+'" >Add fragment</button>' +
        '</div>' +
        '<div class="wzCombo" id="frgSelect"/>' +
        '<div class="wzInfoDiv" id="fragmwzEntInfoDiv"></div>' +
      '</div>');
  $fragWizContentDiv = $('#frgWizardContent'),
  $btnNewFragment = $('button.wzBtnAddEntity', $fragWizContentDiv);
  $btnNewFragment.unbind('click').bind('click', function(e) {
    layoutMgr.modelWizardSaveFragment(null,null,null,null,null,prtID,function (success){
      if (!success) {
        alert("Unable to create new fragment entity");
      }
      setTimeout(function() {
                  layoutMgr.initFragmentWizard(prtID, prtdisplay, itmID);
                }, 50);
    });
  });
  $fragSelector = $('#frgSelect', $fragWizContentDiv),
  $fragSelector.prop('itemID', itmID);
  $fragSelector.jqxComboBox(
    {
      width: 300,
      height: 25,
      autoComplete: true,
      searchMode: 'containsignorecase',
      placeHolder: "Select fragment ...",
      source: frgDataAdapter,
      selectedIndex: 0,
      popupZIndex: 18010,
      displayMember: "frgdisplay",
      valueMember: "frg_id",
    });
  $fragSelector.unbind('select').bind('select', function (event) {
    if (event.args) {
      var frag = event.args.item,
          itemID = $fragSelector.prop('itemID');
      if (frag) {
        var fragInfo = frag.originalItem, frgTag = 'frg'+fragInfo.frg_id,
          $fragInfoDiv = $('#fragmwzEntInfoDiv', $fragWizContentDiv);
        $fragInfoDiv.children().remove();
        $fragInfoDiv.append('<div class="wzInfoRow">' +
          '<div class="wzEntInfoLbl">ID:</div>' +
          '<div class="wzEntInfo readonly" id="'+frgTag+'_id">' + fragInfo.frg_id + '</div>' +
          '</div>');
        $fragInfoDiv.append('<div class="wzInfoRow">' +
          '<div class="wzEntInfoLbl">Label:</div>' +
          '<div class="wzEntInfo" id="'+frgTag+'_label">' + fragInfo.frg_label + '</div>' +
          '<input class="wzEntInfoInput" value="' + fragInfo.frg_label + '"/>' +
          '</div>');
        /*$fragInfoDiv.append('<div class="wzInfoRow">' +
          '<div class="wzEntInfoLbl">Description:</div>' +
          '<div class="wzEntInfo" id="'+frgTag+'_description>' + fragInfo.frg_description + '</div>' +
          '<input class="wzEntInfoInput" value="' + fragInfo.frg_description + '"/>' +
          '</div>');*/
          $('.wzEntInfo:not(readonly)',$fragInfoDiv).unbind('click').bind('click',function(event){
            var element = this, id = element.id,
                $fieldDisplayDiv = $(element),
                $fieldInput = $(element.nextSibling);
            $fieldDisplayDiv.addClass('edit');
            $fieldInput.addClass('edit');
            $fieldInput.focus().select();
            $fieldInput.unbind('keydown').bind('keydown', function(event){
              var val = $(this).val(),
                  codes = id.split('_'),
                  key = codes[1],
                  frgTag = codes[0],
                  frgID = frgTag.substr(3);
              if(event.keyCode == 13) {
                $fieldDisplayDiv.text(val);
                $fieldDisplayDiv.removeClass('edit');
                $fieldInput.removeClass('edit');
                if (key == 'label') {
                  layoutMgr.modelWizardSaveFragment(frgID,null,val,null,null,null,function (success){
                    if (!success) {
                      alert("Unable to update fragment entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initFragmentWizard(prtID, prtdisplay, itmID);
                              }, 50);
                  });
                } else if (key == 'description') {
                  layoutMgr.modelWizardSaveFragment(frgID,val,null,null,null,null,function (success){
                    if (!success) {
                      alert("Unable to update fragment entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initFragmentWizard(prtID, prtdisplay, itmID);
                              }, 50);
                  });
                }
              }
            });
            //alert("Edit "+id+" with value: "+val);
          });
        layoutMgr.initSurfaceWizard(fragInfo.frg_id,fragInfo.frgdisplay,itemID);
      }
    }
  });
  DEBUG.traceExit("layoutMgr.initFragmentWizard", "");
},


  /**
  * model wizard save fragment entity
  *
  * @param int frgID
  * @param string description of this fragment
  * @param string label commonly used for this fragment
  * @param string measure describing the measurements of this fragment
  * @param string locations is comma separate list of location references for this fragment
  * @param int newFragmentPrtID Part ID to link new fragment to
  * @param function cb is a callback function with a flag to signal success
  */

 modelWizardSaveFragment: function(frgID, description, label, measure, locations, newFragmentPrtID, cb) {
  var savedata ={}, layoutMgr = this;
  DEBUG.traceEntry("layoutMgr.modelWizardSaveFragment");
  if (frgID) {
    savedata["frgID"] = frgID;
  }
  if (description != null) {
    savedata["description"] = description;
  }
  if (label != null) {
    savedata["label"] = label;
  }
  if (measure != null) {
    savedata["measure"] = measure;
  }
  if (locations != null) {
    savedata["locations"] = locations;
  }
  if (newFragmentPrtID) {
    savedata["newFragmentPrtID"] = newFragmentPrtID;
  }
  if (Object.keys(savedata).length > 0) {
    //jqAjax asynch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveFragment.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            var txtID, text;
            if (typeof data == 'object' && data.success && data.entities &&
                layoutMgr.dataMgr ) {
              //update data
              layoutMgr.dataMgr.updateLocalCache(data,null);
            }
            if (data.errors) {
              alert("An error occurred while trying to save fragment data. Error: " + data.errors.join());
              if (cb) {
                cb(false);
              }
            } else if (cb){
              cb(true);
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to save fragment data. Error: " + error);
            if (cb) {
              cb(false);
            }
      }
    });
  }
  DEBUG.traceExit("layoutMgr.modelWizardSaveFragment");
},


  /**
    * remove part wizard
    */

   removePartWizard: function () {
    this.removeFragmentWizard();
    var $partWizContentDiv = $('#prtWizardContent'),
        $partSep = $('#partSep'),
        $partSelector = $('#prtSelect'),
        $partInfoDiv = $('#partInfoDiv');
    if ($partInfoDiv.length) {    
      $partInfoDiv.remove();
    }
    if ($partSep.length) {    
      $partSep.remove();
    }
    if ($partSelector.length) {    
      $partSelector.jqxComboBox('destroy');
    }
    if ($partWizContentDiv.length) {    
      $partWizContentDiv.remove();
    }
  },


  /**
    * init part wizard
    */

  initPartWizard: function (itmID, itmdisplay) {
    DEBUG.traceEntry("layoutMgr.initPartWizard", "");
    var layoutMgr = this,
        $partWizContentDiv,
        prtDataUrl = basepath + "/services/getItemPartsList.php?db=" + dbName + "&itmID=" + itmID,
        prtDatasource = {
          datatype: "jsonp",
          datafields: [
            { name: 'prt_id' },
            { name: 'prt_label' },
            { name: 'prt_description' },
            { name: 'prt_sequence' },
            { name: 'prtdisplay' }
          ],
          url: prtDataUrl,
        },
        prtDataAdapter = new $.jqx.dataAdapter(prtDatasource),
        $partSelector = $('#prtSelect');
    if ($partSelector && $partSelector.length) {
      //remove part UI for rebuild
      layoutMgr.removePartWizard();
    }
    layoutMgr.modelWizContentDiv.append('<div id="partSep" class="wzModelSep"></div>');
    layoutMgr.modelWizContentDiv.append(
        '<div id="prtWizardContent">' +
          '<div class="wzLabel">' +
            '<div style:"padding-right:5px">Part :</div>' +
            '<button class="wzBtnAddEntity" title="Add new part to item '+itmdisplay+'" >Add part</button>' +
          '</div>' +
          '<div class="wzCombo" id="prtSelect"/>' +
          '<div class="wzInfoDiv" id="partInfoDiv"></div>' +
        '</div>');
    $partWizContentDiv = $('#prtWizardContent');
    $btnNewPart = $('button.wzBtnAddEntity', $partWizContentDiv);
    $btnNewPart.unbind('click').bind('click', function(e) {
      layoutMgr.modelWizardSavePart(null,null,null,null,null,null,itmID,function (success){
        if (!success) {
          alert("Unable to create new part entity");
        }
        setTimeout(function() {
                    layoutMgr.initPartWizard(itmID, itmdisplay);
                  }, 50);
      });
    });
    $partSelector = $('#prtSelect');
    $partSelector.prop('itemID', itmID);
    $partSelector.jqxComboBox(
      {
        width: 300,
        height: 25,
        autoComplete: true,
        searchMode: 'containsignorecase',
        placeHolder: "Select part ...",
        source: prtDataAdapter,
        selectedIndex: 0,
        popupZIndex: 18010,
        displayMember: "prtdisplay",
        valueMember: "prt_id",
      });
    $partSelector.unbind('select').bind('select', function (event) {
      if (event.args) {
        var part = event.args.item,
            itemID = $partSelector.prop('itemID');
        if (part) {
          var partInfo = part.originalItem, prtTag = 'prt'+partInfo.prt_id,
            $partInfoDiv = $('#partInfoDiv', $partWizContentDiv);
          $partInfoDiv.children().remove();
          $partInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">ID:</div>' +
            '<div class="wzEntInfo readonly" id="'+prtTag+'_id">' + partInfo.prt_id + '</div>' +
            '</div>');
          $partInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Label:</div>' +
            '<div class="wzEntInfo" id="'+prtTag+'_label">' + partInfo.prt_label + '</div>' +
            '<input class="wzEntInfoInput" value="' + partInfo.prt_label + '"/>' +
            '</div>');
          $partInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Sequence:</div>' +
            '<div class="wzEntInfo" id="'+prtTag+'_sequence">' + partInfo.prt_sequence + '</div>' +
            '<input class="wzEntInfoInput" value="' + partInfo.prt_sequence + '"/>' +
            '</div>');
        /*$partInfoDiv.append('<div class="wzInfoRow">' +
          '<div class="wzEntInfoLbl">Description:</div>' +
          '<div class="wzEntInfo" id="'+prtTag+'_description">' + partInfo.prt_description + '</div>' +
          '<input class="wzEntInfoInput" value="' + partInfo.prt_description + '"/>' +
          '</div>');*/
          $('.wzEntInfo:not(readonly)',$partInfoDiv).unbind('click').bind('click',function(event){
            var element = this, id = element.id,
                $fieldDisplayDiv = $(element),
                $fieldInput = $(element.nextSibling);
            $fieldDisplayDiv.addClass('edit');
            $fieldInput.addClass('edit');
            $fieldInput.focus().select();
            $fieldInput.unbind('keydown').bind('keydown', function(event){
              var val = $(this).val(),
                  codes = id.split('_'),
                  key = codes[1],
                  prtTag = codes[0],
                  prtID = prtTag.substr(3);
              if(event.keyCode == 13) {
                $fieldDisplayDiv.text(val);
                $fieldDisplayDiv.removeClass('edit');
                $fieldInput.removeClass('edit');
                if (key == 'label') {
                  layoutMgr.modelWizardSavePart(prtID,null,val,null,null,null,null,function (success){
                    if (!success) {
                      alert("Unable to update part entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initPartWizard(itmID, itmdisplay);
                              }, 50);
                  });
                } else if (key == 'sequence') {
                  layoutMgr.modelWizardSavePart(prtID,null,null,null,val,null,null,function (success){
                    if (!success) {
                      alert("Unable to update part entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initPartWizard(itmID, itmdisplay);
                              }, 50);
                  });
                } else if (key == 'description') {
                  layoutMgr.modelWizardSavePart(prtID,val,null,null,null,null,null,function (success){
                    if (!success) {
                      alert("Unable to update part entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initPartWizard(itmID, itmdisplay);
                              }, 50);
                  });
                }
              }
            });
            //alert("Edit "+id+" with value: "+val);
          });
          layoutMgr.initFragmentWizard(partInfo.prt_id,partInfo.prtdisplay,itemID);
        }
      }
    });
    DEBUG.traceExit("layoutMgr.initPartWizard", "");
  },


  /**
  * model wizard save part entity
  *
  * @param int prtID
  * @param string description of this part
  * @param string label commonly used for this part
  * @param string measure describing the measurements of this part
  * @param int sequence number of this part
  * @param string mediums is comma separate list of mediums used for this part
  * @param int newPartItmID Item ID to link new part to
  * @param function cb is a callback function with a flag to signal success
  */

 modelWizardSavePart: function(prtID, description, label, measure, sequence, mediums, newPartItmID, cb) {
  var savedata ={}, layoutMgr = this;
  DEBUG.traceEntry("layoutMgr.modelWizardSavePart");
  if (prtID) {
    savedata["prtID"] = prtID;
  }
  if (description != null) {
    savedata["description"] = description;
  }
  if (label != null) {
    savedata["label"] = label;
  }
  if (measure != null) {
    savedata["measure"] = measure;
  }
  if (sequence != null) {
    savedata["sequence"] = sequence;
  }
  if (mediums != null) {
    savedata["mediums"] = mediums;
  }
  if (newPartItmID) {
    savedata["newPartItmID"] = newPartItmID;
  }
  if (Object.keys(savedata).length > 0) {
    //jqAjax asynch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/savePart.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities &&
                layoutMgr.dataMgr ) {
              //update data
              layoutMgr.dataMgr.updateLocalCache(data,null);
            }
            if (data.errors) {
              alert("An error occurred while trying to save part data. Error: " + data.errors.join());
              if (cb) {
                cb(false);
              }
            } else if (cb){
              cb(true);
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to save part data. Error: " + error);
            if (cb) {
              cb(false);
            }
      }
    });
  }
  DEBUG.traceExit("layoutMgr.modelWizardSavePart");
},


  /**
  * init item wizard
  */

  initItemWizard: function (selectItmID = null) {
    DEBUG.traceEntry("layoutMgr.initItemWizard", "");
    var layoutMgr = this,
      $itemWizContentDiv = $('#itmWizardContent'),
      $itemSelector = $('#itmSelect', $itemWizContentDiv),
      itmDataUrl = basepath + "/services/getItemsList.php?db=" + dbName,
      itmDatasource = {
        datatype: "jsonp",
        datafields: [
          { name: 'itm_id' },
          { name: 'itm_idno' },
          { name: 'itm_title' },
          { name: 'itm_description' },
          { name: 'display' }
        ],
        url: itmDataUrl,
      },
      itmDataAdapter = new $.jqx.dataAdapter(itmDatasource);
    $itemSelector.jqxComboBox(
      {
        width: 300,
        height: 25,
        autoComplete: true,
        searchMode: 'containsignorecase',
        placeHolder: "Select item ...",
        source: itmDataAdapter,
        popupZIndex: 18010,
        displayMember: "display",
        valueMember: "itm_id",
      });
      $itemSelector.unbind('bindingComplete').bind('bindingComplete', function (event) {
        if (selectItmID){
//          alert("Finding item id " + selectItmID);
          var item = $itemSelector.jqxComboBox('getItemByValue',selectItmID);
          if (item) {
            $itemSelector.jqxComboBox('selectItem',item);
          }
        } else {
//          alert("normal start");
        }
      });
      $itemSelector.unbind('select').bind('select', function (event) {
      if (event.args) {
        var item = event.args.item;
        if (item) { // show select item info and setup insitu edit UX
          var itemInfo = item.originalItem, itmTag = 'itm'+itemInfo.itm_id,
            $itemInfoDiv = $('#itemInfoDiv', $itemWizContentDiv);
          $itemInfoDiv.children().remove();
          $itemInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">ID:</div>' +
            '<div class="wzEntInfo" readonly" id="'+itmTag+'_id">' + itemInfo.itm_id + '</div>' +
            '</div>');
          $itemInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Inv.No.:</div>' +
            '<div class="wzEntInfo" id="'+itmTag+'_idno">' + itemInfo.itm_idno + '</div>' +
            '<input class="wzEntInfoInput" value="' + itemInfo.itm_idno + '"/>' +
            '</div>');
          $itemInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl">Title:</div>' +
            '<div class="wzEntInfo" id="'+itmTag+'_title">' + itemInfo.itm_title + '</div>' +
            '<input class="wzEntInfoInput" value="' + itemInfo.itm_title + '"/>' +
            '</div>');
          /*$itemInfoDiv.append('<div class="wzInfoRow">' +
            '<div class="wzEntInfoLbl" id="'+itmTag+'_id">Description:</div>' +
            '<div class="wzEntInfo">' + itemInfo.itm_description + '</div>' +
            '<input class="wzEntInfoInput" value="' + itemInfo.itm_description + '"/>' +
            '</div>');*/

          // check if there is 
          //add click handler for each editable item property
          $('.wzEntInfo:not(readonly)',$itemInfoDiv).unbind('click').bind('click',function(event){
            var element = this, id = element.id,
                $fieldDisplayDiv = $(element),
                $fieldInput = $(element.nextSibling), $fieldSelect,
                codes = id.split('_'),
                key = codes[1],
                itmTag = codes[0],
                itmID = itmTag.substr(3);
            $fieldDisplayDiv.addClass('edit');
            $fieldInput.addClass('edit');
            $fieldInput.focus().select();
            $fieldInput.unbind('keydown').bind('keydown', function(event) {
              var val = $(this).val();
              if(event.keyCode == 13) {
                $fieldDisplayDiv.text(val);
                $fieldDisplayDiv.removeClass('edit');
                $fieldInput.removeClass('edit');
                if (key == 'idno') {
                  layoutMgr.modelWizardSaveItem(itmID, null, null, null, val,null,function (success){
                    if (!success) {
                      alert("Unable to update item entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initItemWizard(itmID);
                              }, 50);
                  });
                } else if (key == 'title') {
                  layoutMgr.modelWizardSaveItem(itmID, null, val, null, null, null, function (success){
                    if (!success) {
                      alert("Unable to update item entity");
                    }
                    setTimeout(function() {
                                layoutMgr.initItemWizard(itmID);
                              }, 50);
                  });
                }
              }
            });
          });
          layoutMgr.initPartWizard(itemInfo.itm_id,itemInfo.display);
        }
      }
    });

    DEBUG.traceExit("layoutMgr.initItemWizard", "");
  },


  /**
  * model wizard save item entity
  *
  * @param int itmID
  * @param string description of this item
  * @param string title commonly used for this item
  * @param string measure describing the measurements of this item
  * @param int idno identifying number of this item
  * @param function cb is a callback function with a flag to signal success
  */

 modelWizardSaveItem: function(itmID, description, title, measure, idno, createBlank, cb) {
  var savedata ={}, layoutMgr = this;
  DEBUG.traceEntry("layoutMgr.modelWizardSaveItem");
  if (itmID) {
    savedata["itmID"] = itmID;
  }
  if (description != null) {
    savedata["description"] = description;
  }
  if (title != null) {
    savedata["title"] = title;
  }
  if (measure != null) {
    savedata["measure"] = measure;
  }
  if (idno != null) {
    savedata["idno"] = idno;
  }
  if (createBlank != null) {
    savedata["createBlank"] = createBlank;
  }
  if (Object.keys(savedata).length > 0) {
    //jqAjax asynch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveItem.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            var selectItmID = itmID;
            if (typeof data == 'object' && data.success && data.entities &&
                layoutMgr.dataMgr ) {
              //update data
              layoutMgr.dataMgr.updateLocalCache(data,null);
              if (!selectItmID && data.entities.insert && data.entities.insert.itm) {
                selectItmID = Object.keys(data.entities.insert.itm)[0];
              }
            }
            if (data.errors) {
              alert("An error occurred while trying to save item data. Error: " + data.errors.join());
              if (cb) {
                cb(false);
              }
            } else if (cb){
              cb(selectItmID);
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to save item data. Error: " + error);
            if (cb) {
              cb(false);
            }
      }
    });
  }
  DEBUG.traceExit("layoutMgr.modelWizardSaveItem");
},


  /**
    * create new item wizard
    */

  createItemWizard: function (initalItmID = null) {
    DEBUG.traceEntry("layoutMgr.createNewItemWizard", "");
    var layoutMgr = this, mainContainer = $('body'),
      offset = mainContainer.offset(),
      wzWidth, wzHeight,
      $modelWizDiv,
      $itemCategorySelect;
    if (layoutMgr.modelWizard) {
      layoutMgr.modelWizard.jqxWindow('close');
      delete layoutMgr.modelWizard;
      $('#modelWizard').remove();
      delete $('#modelWizard');
    }
    mainContainer.append(
      '<div id="modelWizard">' +
        // add wizard header
        '<div id="wizardHeader">' +
          '<span id="itmHeaderSpan" style="float: left">Item Wizard</span>' +
        '</div>' +
        '<div id="wizardContent" style="overflow-y: auto;">' +
          '<div id="itmWizardContent">' +
            '<div class="wzLabel">' +
              '<div style:"padding-right:5px">Item :</div>' +
              '<button class="wzBtnAddEntity" title="Add new item to corpus" >Add item</button>' +
              '<div class="wzInvCombo" id="invSelect"/>' +
            '</div>' +
            '<div class="wzCombo" id="itmSelect"/>' +
            '<div class="wzInfoDiv" id="itemInfoDiv"></div>' +
          '</div>' +
        '</div>' +
      '</div>');
    $modelWizDiv = $('#modelWizard');
    // get wizard content div in modelWizard
    layoutMgr.modelWizContentDiv = $('#wizardContent',$modelWizDiv);
    $btnNewItem = $('#itmWizardContent button.wzBtnAddEntity', $modelWizDiv);
    $btnNewItem.unbind('click').bind('click', function(e) {
      if (layoutMgr.newCatalogIDService) {
        //jqAjax asynch save
        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: layoutMgr.newCatalogIDService, //caution dependency on context having basepath and dbName
          async: true,
          success: function (data, status, xhr) {
            var invNo = null;
            $itemCategorySelect = $('#invSelect', layoutMgr.modelWizContentDiv);
            if (data && Object.keys(data).length && $itemCategorySelect.length) {
              if (!layoutMgr.previousInvCat || !data[layoutMgr.previousInvCat]) {
                if (Object.keys(data).length > 1 && !$itemCategorySelect.hasClass('show')) { // init select and have user select
                  $itemCategorySelect.addClass('show');
                  $itemCategorySelect.jqxComboBox(
                    {
                      width: 100,
                      height: 20,
                      placeHolder: "Select type ...",
                      source: Object.keys(data),
                      popupZIndex: 18010
                    });
                    $itemCategorySelect.unbind('select').bind('select', function(event){
                      var key = $(this).val();
                      layoutMgr.previousInvCat = key;
                      $btnNewItem.attr('title', "Add "+key+" item");
                    });
                }
                alert("Please select a category of item to create.");
                return;
              } else {
                invNo = data[layoutMgr.previousInvCat];
              }
            }
            //call service to create new text with inventory number
            layoutMgr.modelWizardSaveItem(null,null,null,null,invNo,true, function (selectItmID){
              if (!selectItmID) {
                alert("Unable to save text entity");
              }
              layoutMgr.initItemWizard(selectItmID);
            });
          }
        });
      } else {
        layoutMgr.modelWizardSaveItem(null,null,null,null,null,true,function (selectItmID){
          if (!selectItmID) {
            alert("Unable to create new item entity");
          }
          setTimeout(function() {
                      layoutMgr.initItemWizard(selectItmID);
                    }, 50);
        });
      }
    });
    offset.xcenter = mainContainer.innerWidth() / 4;
    offset.ycenter = mainContainer.innerHeight() / 4;
    wzWidth = 3 * offset.xcenter + 300;
    wzHeight = 3 * offset.ycenter + 400;
    layoutMgr.modelWizard = $modelWizDiv.jqxWindow({
      width: wzWidth,
      height: wzHeight, resizable: true, isModal: true,
      position: {
        x: offset.left + 100,
        y: offset.top + 100
      },
      initContent: function () { layoutMgr.initItemWizard(initalItmID); }
    });
    DEBUG.traceExit("layoutMgr.createNewItemWizard", "");
  }
}
