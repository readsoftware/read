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
* editors editionVE object
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Utility Classes
*/
var EDITORS = EDITORS || {};

/**
* Constructor for Edition Viewer/Editor Object
*
* @type Object
*
* @param editionVECfg is a JSON object with the following possible properties
*  "edition" an entity data element which defines the edition and all it's structures.
*
* @returns {EditionVE}

*/


/**
* put your comment there...
*
* @type Object
*/

EDITORS.SearchVE = function(searchVECfg) {
  var srchVE = this;
  //read configuration and set defaults
  this.config = searchVECfg;
  this.type = "SearchVE";
  this.search = searchVECfg['search'] ? searchVECfg['search']:null;
  this.editDiv = searchVECfg['editDiv'] ? searchVECfg['editDiv']:null;
  this.dataMgr = searchVECfg['dataMgr'] ? searchVECfg['dataMgr']:null;
  this.layoutMgr = searchVECfg['layoutMgr'] ? searchVECfg['layoutMgr']:null;
  this.searchNavBar = searchVECfg['searchNavBar'] ? searchVECfg['searchNavBar']:null;
  this.searchNavBar.addClass('searchNavBar');
  this.id ="searchVE";
  this.pageSize = 3;
  this.isLoading = false;
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
EDITORS.SearchVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    var srchVE = this, propMgrCfg;
    this.splitterDiv = $('<div id="'+this.id+'splitter"/>');
    this.gridDivCntr = $('<div id="'+this.id+'GridContainer" class="gridContainer"/>');
    this.gridDiv = $('<div id="'+this.id+'Grid" />');
    this.gridDivCntr.append(this.gridDiv);
    this.propertyMgrDiv = $('<div id="'+this.id+'propManager" class="propertyManager"/>');
    this.splitterDiv.append(this.gridDivCntr);
    this.splitterDiv.append(this.propertyMgrDiv);
    $(this.editDiv).append(this.splitterDiv);
    this.splitterDiv.jqxSplitter({ width: '100%',
                                      height: '100%',
                                      orientation: 'horizontal',
                                      splitBarSize: 1,
                                      showSplitBar:true,
                                      panels: [{ size: '60%', min: '200', collapsible: false},
                                               { size: '40%', min: '200', collapsible: true,collapsed : true}] });
    propMgrCfg ={id: this.id,
      propertyMgrDiv: this.propertyMgrDiv,
      editor: srchVE,
      propVEType: "entPropVE",
      dataMgr: this.dataMgr,
      splitterDiv: this.splitterDiv };
    this.propMgr = new MANAGERS.PropertyManager(propMgrCfg);
    this.displayProperties = this.propMgr.displayProperties;
    this.splitterDiv.unbind('focusin').bind('focusin',this.layoutMgr.focusHandler);
    this.createSearchBar();
    if (this.search) {
      this.searchInput.val(this.search);
    }
    this.loadSearch();
  },


/**
* put your comment there...
*
*/

  createStaticToolbar: function () {
    var srchVE = this;
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    this.newTextBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="newTextBtn"' +
                              ' title="Add a new text to the database">+ &#x1F4DC;</button>'+
                            '<div class="toolbuttonlabel">New text</div>'+
                           '</div>');
    this.editToolbar.append(this.newTextBtnDiv);
    this.newTextBtn = $('#newTextBtn',this.newTextBtnDiv);
    this.newTextBtn.unbind('click')
                                  .bind('click',function(e) {
                                    srchVE.addText();
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
//    this.newTextBtn.attr('disabled','disabled');
/*    this.newEditionBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="newEditionBtn"' +
                              ' title="Add a new edition for the current text">+ &#x1F5CE;</button>'+
                            '<div class="toolbuttonlabel">New edition</div>'+
                           '</div>');
    this.editToolbar.append(this.newEditionBtnDiv);
    $('#newEditionBtn',this.newEditionBtnDiv).unbind('click')
                                  .bind('click',function(e) {
                                    srchVE.addEditionWizard();
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
    this.cloneEditionBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="cloneEditionBtn"' +
                              ' title="Clone the selected edition of the current text">&#x1F5D0;</button>'+
                            '<div class="toolbuttonlabel">Clone edition</div>'+
                           '</div>');
    this.editToolbar.append(this.cloneEditionBtnDiv);
    $('#cloneEditionBtn',this.cloneEditionBtnDiv).unbind('click')
                                  .bind('click',function(e) {
                                    srchVE.addEditionWizard();
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
    this.addImageBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="addImageBtn"' +
                              ' title="Add image url to database and/or text">+ &#x1F4F7;</button>'+
                            '<div class="toolbuttonlabel">Add image</div>'+
                           '</div>');
    this.editToolbar.append(this.addImageBtnDiv);
    $('#addImageBtn',this.addImageBtnDiv).unbind('click')
                                  .bind('click',function(e) {
                                    srchVE.addImageWizard();
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
    this.newBaselineBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="newBaselineBtn"' +
                              ' title="create a new baseline for a text">+ &#x26CB;</button>'+
                            '<div class="toolbuttonlabel">New baseline</div>'+
                           '</div>');
    this.editToolbar.append(this.newBaselineBtnDiv);
    $('#newBaselineBtn',this.newBaselineBtnDiv).unbind('click')
                                  .bind('click',function(e) {
                                    srchVE.addBaselineWizard();
                                    e.stopImmediatePropagation();
                                    return false;
                                  });
*/
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);

    btnShowPropsName = this.id+'showprops';
    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
                              '" title="Show/Hide property panel">&#x25E8;</button>'+
                            '<div class="toolbuttonlabel">Properties</div>'+
                           '</div>');
    this.propertyBtn = $('#'+btnShowPropsName,this.propertyBtnDiv);
    this.propertyBtn.unbind('click').bind('click',function(e) {
                                           var paneID, editor;
                                           srchVE.showProperties(!$(this).hasClass("showUI"));
                                         });

    this.viewToolbar.append(this.propertyBtnDiv);
    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);

  },


/**
* put your comment there...
*
* @param bShow
*/

  showProperties: function (bShow) {
    var srchVE = this;
    if (srchVE.propMgr &&
        typeof srchVE.propMgr.displayProperties == 'function'){
      srchVE.propMgr.displayProperties(bShow);
      if (this.propertyBtn.hasClass("showUI") && !bShow) {
        this.propertyBtn.removeClass("showUI");
        this.gridDiv.jqxGrid('pagesize',22);
      } else if (!this.propertyBtn.hasClass("showUI") && bShow) {
        this.propertyBtn.addClass("showUI");
        this.gridDiv.jqxGrid('pagesize',13);
      }
    }
  },


/**
* put your comment there...
*
*/

  addTextWizard: function() {
    alert("add text wizard underconstruction");
  },


/**
* put your comment there...
*
*/

  addImageWizard: function() {
    alert("add image wizard underconstruction");
  },


/**

  addBaselineWizard: function() {
    alert("add baseline wizard underconstruction");
  },


/**
* put your comment there...
*
*/

  addEditionWizard: function() {
    alert("add edition wizard underconstruction");
  },


/**
* put your comment there...
*
* @param {String} pageID
*/

  reloadSearch: function(pageID) {
    var srchVE = this,
        playButton = $('#play',this.searchMediaBar);
    if (!pageID) {
      pageID = "search";
    }
    this.searchInput.val("");//clear the search string
    this.loadSearch();
    this.layoutMgr.switchContentPage(pageID);
    playButton.removeClass("med-newsearchpage").removeClass("med-searchpage").addClass("med-textpage");
  },


/**
* put your comment there...
*
*/

  loadSearch: function() {
    var srchVE = this,
        search = this.searchInput.val()?this.searchInput.val():"";
    this.isLoading = true;
    this.dataMgr.loadTextSearch(search,function(results) {
                                        srchVE.updateResultGrid(results);
                                        srchVE.updateCatalogInfoBar();
                                        srchVE.search = search;
                                        srchVE.isLoading = false;
                                        srchVE.dataMgr.loadTextResources(function(){srchVE.gridChange();});
                                      });
  },


/**
* put your comment there...
*
*/

  doLocalSearch: function() {
    var srchVE = this,
        search = this.searchInput.val()?this.searchInput.val():"";
    //parse search string to determine filter action
    if (search === "") {
      //remove all filters from grid
      this.gridDiv.jqxGrid('removefilter', 'ckn', true);
      this.gridDiv.jqxGrid('removefilter', 'title', true);
    } else if (search.toUpperCase().substring(0,2) == "CK") {//filter ckn column
      var filtergroup = new $.jqx.filter(),
          filter_or_operator = 1;
      var filter1 = filtergroup.createfilter('stringfilter', search, 'starts_with');
      filtergroup.addfilter(filter_or_operator, filter1);
      // add the filters.
      this.gridDiv.jqxGrid('addfilter', 'ckn', filtergroup);
      // apply the filters.
      this.gridDiv.jqxGrid('applyfilters');
    } else {// filter title column
      var filtergroup = new $.jqx.filter(),
          filter_or_operator = 1;
      var filter1 = filtergroup.createfilter('stringfilter', search, 'contains');
      filtergroup.addfilter(filter_or_operator, filter1);
      // add the filters.
      this.gridDiv.jqxGrid('addfilter', 'title', filtergroup);
      // apply the filters.
      this.gridDiv.jqxGrid('applyfilters');
    }
  },


/**
* put your comment there...
*
* @param textResults
*/

  updateResultGrid: function (textResults) {
    var srchVE = this, txtData, newRow, dataAdapter,
        selectedTxtIDs = {},selectedNewRowIndexes = [],
        i,cnt=0,txtID,dataArray = [];
    for (txtID in textResults) {
      txtData = textResults[txtID];
      if (txtData && txtData.title && txtData.CKN) {
        newRow = [txtID];
        newRow.push(txtData['CKN']);
        newRow.push(txtData['title']);
        dataArray.push(newRow);
        if (this.selectedTxtIDs && this.selectedTxtIDs[txtID]) {
          selectedNewRowIndexes.push(cnt);
        }
        cnt++;
      }
    }
    dataAdapter = new $.jqx.dataAdapter({
                localdata: dataArray,
                datatype: "array",
                pagesize: 22,
                datafields:
                [
                    { name: 'txt_id', type: 'number',map: '0' },
                    { name: 'ckn', type: 'string',map: '1' },
                    { name: 'title', type: 'string',map: '2' }
                ],
                addrow: function(rowid,rowdate,pos,commit) {
                          srchVE.newRowID = rowid;
                          commit(true);
                        }
            });
    if (this.gridDiv.children().length) {
      try {
        this.gridDiv.remove();
        delete this.gridDiv;
      } catch (e) {
        alert( "exception from removal of grid");
      }
    }
    if (!this.gridDiv){
      this.gridDiv = $('<div id="'+this.id+'Grid" />');
      this.gridDivCntr.append(this.gridDiv);
    }
    this.gridDiv.jqxGrid({
      source: dataAdapter,
      theme: 'energyblue',
      width: '100%',
//      height:'100%',
      altrows: true,
      columnsresize: false,
      selectionmode: 'checkbox',
      sortable: true,
      pageable: true,
      pagermode: 'simple',
      pagesizeoptions: ['15', '20', '25'],
      autoheight:true,
      autorowheight:true,
      editable: false,
      filterable: true,
      columns: [
                  { text: 'FK', datafield: 'txt_id', hidden: true },
                  { text: 'ID', datafield: 'ckn', minwidth: 100 },
                  { text: 'Title', datafield: 'title',minwidth: 400 , cellsalign: 'left' },
                ]
    });
    for (i=0; i < selectedNewRowIndexes.length; i++) {
      this.gridDiv.jqxGrid('selectrow',selectedNewRowIndexes[i]);
    }
    this.gridDiv.unbind('rowselect').bind('rowselect', function(e) {
                                                           var gid;
                                                           if (e.args && e.args.row &&  e.args.row.txt_id) {
                                                             gid = "txt"+e.args.row.txt_id;
                                                             srchVE.propMgr.showVE(null,gid);
                                                             //create a refresh API for the select row in case user edits properties.
                                                             srchVE.refreshEntityDisplay = function(id) {
                                                               var rowID = e.args.row.uid, txtID = e.args.row.txt_id, text;
                                                               if (id == txtID) {//create rowdata from text entity in dataMgr
                                                                 text = srchVE.dataMgr.getEntity('txt',id);
                                                                 rowData = {
                                                                   "ckn": text.CKN,
                                                                   "txt_id": id,
                                                                   "title":text.title
                                                                 };
                                                                 srchVE.gridDiv.jqxGrid('updaterow',rowID,rowData)
                                                               }
                                                            };
                                                           } else {// remove refresh API so it's not called without edits.
                                                             delete srchVE.refreshEntityDisplay;
                                                           }
                                                           srchVE.gridChange(e)
                                                         });
    this.gridDiv.unbind('rowunselect').bind('rowunselect', function(e) {
                                                           srchVE.gridChange(e)
                                                         });
    this.gridDiv.unbind('sort').bind('sort', function(e) {
                                                           srchVE.gridChange(e)
                                                         });
    this.gridDiv.unbind('filter').bind('filter', function(e) {
                                                           srchVE.gridChange(e)
                                                         });
 },


/**
* put your comment there...
*
* @param txtID
* @param ckn
* @param title
*/

  addTextRow: function (txtID,ckn,title) {
    var srchVE = this, rowID, rowData = {
                                 "ckn": ckn,
                                 "txt_id": txtID,
                                 "title":title
                               };
    srchVE.newRowID = null;
    rowID = srchVE.gridDiv.jqxGrid('addrow',null,rowData);
    if (srchVE.newRowID !== null) {
      srchVE.gridDiv.jqxGrid('selectrow',srchVE.newRowID);
    }
  },


/**
* put your comment there...
*
* @param event
*/

  gridChange: function (event) {
    var srchVE = this, i, j, txtID,
        curEntID, cursorMap = [], cursorPos = 0,
        selectedIndexes = this.gridDiv.jqxGrid('getselectedrowindexes'),
        gridDisplayRows = this.gridDiv.jqxGrid('getdisplayrows');
    this.selectedTxtIDs = {};
    //create lookupByTextID  !!Warning Text Entity Specific - Search for other entities???
    if (selectedIndexes && selectedIndexes.length && selectedIndexes[0] != -1) { //use selection to create mapping
      for (i in selectedIndexes) {
        j = selectedIndexes[i];
        txtID = this.gridDiv.jqxGrid('getrowdata',j).txt_id;
        cursorMap.push([txtID,j]);
        this.selectedTxtIDs[txtID] = 1;
      }
    } else if (gridDisplayRows.length) {//use entire grid displayRows
      for (i in gridDisplayRows) {
        cursorMap.push([gridDisplayRows[i].txt_id,i]);
      }
    }
    if (this.cursorMap && cursorMap && cursorMap.length) {//existing map so find current entity ID
      curEntID = this.cursorMap[this.cursorPos][0];
      for(i=0; i<cursorMap.length; i++) {
        if (cursorMap[i][0] == curEntID) {
          cursorPos = i;
          break;
        }
      }
    }
    if (cursorMap.length) {
      this.cursorMap = cursorMap;
      this.cursorPos = cursorPos;
    } else {
      delete this.cursorMap;
      delete this.cursorPos;
    }
    if (this.dataMgr.textResourcesLoaded) {
      this.updateCursorInfoBar();
    } else {
      this.dataMgr.loadTextResources(this.updateCursorInfoBar());
    }
  },


/**
* put your comment there...
*
*/

  isLoaded: function () {
    return (!this.isLoading && this.getCursorTextID());
  },


/**
* put your comment there...
*
*/

  getCurCatID: function () {
    var srchVE = this, curCatalog;
    if (this.curCatID) {
      return this.curCatID;
    }
    return null;
  },


/**
* put your comment there...
*
*/

  getCursorTextID: function () {
    var srchVE = this, curText;
    if (this.cursorMap && this.cursorMap.length &&
        this.cursorPos >=0 && this.cursorPos < this.cursorMap.length) {
      return this.cursorMap[this.cursorPos][0];
    }
    return null;
  },


/**
* put your comment there...
*
*/

  updateCatalogInfoBar: function () {
    var srchVE = this, curText, textResources,
        catResBtnBar = $('#catResBtnBar',this.searchCatInfoBar),
        catID = this.getCurCatID();
    if (catResBtnBar.length == 0) { // no existing catalog button bar so create it
      catResBtnBar = $('<div id="catResBtnBar">');
      this.searchCatInfoBar.append(catResBtnBar);
    //add resource type button
      catResBtnBar.append($('<button class="res-dictionary resFontIconBtn" '+
                         ' title="">&#x1F4DA;</button>'));
    }
    //refresh for the search info
    if (this.searchCatResourcePanel) {// remove any previous resources
      this.searchCatResourcePanel.remove();
    }
    //rebuild resource panel
    this.searchCatResourcePanel = $('<div id="searchCatResourcePanel"></div>');
    this.searchCatResourcePanel.insertAfter(this.searchCatInfoBar);
    //create drop down panel of divs for each resource
    //  with a drag target for each with entID as drag data
    this.searchCatResourcePanel.append(this.createResourcePanel('dict','cat',{1:this.dataMgr.getEntity('cat',1),
                                                                              2:this.dataMgr.getEntity('cat',2),
                                                                              3:this.dataMgr.getEntity('cat',3)}));
    if ($('button',catResBtnBar).length) {
      $('button',catResBtnBar).unbind('click')
                          .bind('click', function (e) {
                            var btn = this;
                            srchVE.showCatalogResources(e,btn);
                          });
    }
    if ($('.draghandle',this.searchCatResourcePanel).length) {
      $('.draghandle',this.searchCatResourcePanel).jqxDragDrop({dropTarget: $('.editContainer'),
                                                                  dropAction: 'none',
                                                                  revert:false});
      $('.draghandle',this.searchCatResourcePanel).unbind('dragStart')
                                                  .bind('dragStart', function (e) {
                                                        if ("textEditPage" != srchVE.layoutMgr.getContentPage()) {
                                                          e.preventDefault();
                                                          return false;
                                                        }
                                                        $(this).jqxDragDrop('data', {
                                                                                  entID: this.id,
                                                                                  catID: catID});
                                                        });
      $('.draghandle',this.searchCatResourcePanel).jqxDragDrop({onTargetDrop: function (target) {
                                                        var data = this.data,
                                                            prefix, id,paneID;
                                                            if (data &&
                                                                data.entID) {
                                                              prefix = data.entID.split(":");
                                                              id = prefix[1];
                                                              prefix = prefix[0];
                                                              paneID = target.attr('class').match(/pane(\d+)/)[0];
                                                              if (paneID) {
                                                                srchVE.layoutMgr.loadPaneContent(prefix+id,paneID);
                                                              }
                                                            } else {
                                                              return false;
                                                            }
                                                         }});
    }
  },


/**
* put your comment there...
*
*/

  updateCursorInfoBar: function () {
    var srchVE = this, curText, textResources,
        resBtnBar = $('<div id="resBtnBar">'),
        txtID = this.getCursorTextID();
    if (this.cursorMap && this.cursorMap.length &&
      this.cursorPos >=0 && this.cursorPos < this.cursorMap.length) {
      curText = this.dataMgr.getEntity('txt',txtID);
      textResources = this.getTextResources(txtID);
      if (!this.searchCurInfoBar) {
        this.searchCurInfoBar = $('<div id="searchCursorInfoBar">Loading....</div>');
        this.searchCurInfoBar.insertAfter(this.searchMediaBar);
      }
      this.searchCurInfoBar.html('<div class="cursorTitle">'+ (curText.CKN?curText.CKN+" - ":"") + curText.value+ '</div>');
      this.searchCurInfoBar.append(resBtnBar);
      if (this.searchCurResourcePanel) {
        this.searchCurResourcePanel.remove();
      }
      this.searchCurResourcePanel = $('<div id="searchCurResourcePanel"></div>');
      this.searchCurResourcePanel.insertAfter(this.searchCurInfoBar);
      //create summary div  title, ckn  icons for reserach edition, published
      // editions, image baselines, images, references with counts as titles
      if (textResources.cnt['edn'] && textResources.cnt['edn']['published'] ) {//published editions
        //add resource type button
        resBtnBar.append($('<button class="res-published resFontIconBtn" '+
                           ' title="'+ textResources.cnt['edn']['published'] +
                           ' edition(s)">&#x1F5CE;</button>'));
        //create drop down panel of divs for each resource
        //  with a drag target for each with entID as drag data
        this.searchCurResourcePanel.append(this.createEdnResourcePanel('published',textResources['edn']['published']));
      }
      if (textResources.cnt['edn'] && textResources.cnt['edn']['research']) {//research editions
        //add resource type button
        resBtnBar.append($('<button class="res-research resFontIconBtn" '+
                           ' title="'+textResources.cnt['edn']['research']+
                           ' research edition(s)">&#x1F5CB;</button>'));
        //create drop down panel of divs for each resource
        //  with a drag target for each with entID as drag data
        this.searchCurResourcePanel.append(this.createEdnResourcePanel('research',textResources['edn']['research']));
      } else if (this.dataMgr.username) {
        //add resource type button
        resBtnBar.append($('<button class="res-research resFontIconBtn" '+
                           ' title="No research edition(s)">&#x1F5CB;</button>'));
        //create drop down panel of divs for each resource
        //  with a drag target for each with entID as drag data
        this.searchCurResourcePanel.append(this.createEdnResourcePanel('research',null));
      }
      if (textResources.cnt['bln']) {//baseline
        //add resource type button
        resBtnBar.append($('<button class="res-baseline resFontIconBtn" '+
                           ' title="'+textResources.cnt['bln']+
                           ' baseline(s)">&#x26CB;</button>'));
        //create drop down panel of divs for each resource
        //  with a drag target for each with entID as drag data
        this.searchCurResourcePanel.append(this.createBlnResourcePanel(textResources['bln']));
      }
      if (textResources.cnt['img']) {//images
        //add resource type button
        resBtnBar.append($('<button class="res-image resFontIconBtn" '+
                           ' title="'+textResources.cnt['img']+
                           ' image(s)">&#x1F4F7;</button>'));
        //create drop down panel of divs for each resource
        //  with a drag target for each with entID as drag data
        this.searchCurResourcePanel.append(this.createResourcePanel('image','img',textResources['img']));
      }
      if (textResources.cnt['atb']) {//references
        //add resource type button
        resBtnBar.append($('<button id=refList-txt:'+txtID+' class="res-reference resFontIconBtn" '+
                           ' title="'+textResources.cnt['atb']+
                           ' reference(s)">&#x1F4DA;</button>'));
        //create drop down panel of divs for each resource
        //  with a drag target for each with entID as drag data
        this.searchCurResourcePanel.append(this.createResourcePanel('reference','atb',textResources['atb']));
      }
      if ($('button',resBtnBar).length) {
        $('button',resBtnBar).unbind('click')
                            .bind('click', function (e) {
                              var btn = this;
                              srchVE.showCursorResources(e,btn);
                            });
      }
      if ($('.draghandle',this.searchCurResourcePanel).length) {
        $('.draghandle',this.searchCurResourcePanel).jqxDragDrop({dropTarget: $('.editContainer'),
                                                                    dropAction: 'none',
                                                                    revert:false});
        $('.draghandle',this.searchCurResourcePanel).unbind('dragStart')
                                                    .bind('dragStart', function (e) {
                                                          if ("textEditPage" != srchVE.layoutMgr.getContentPage()) {
                                                            e.preventDefault();
                                                            return false;
                                                          }
                                                          $(this).jqxDragDrop('data', {
                                                                                    entID: this.id,
                                                                                    txtID: txtID});
                                                          });
        $('.draghandle',this.searchCurResourcePanel).jqxDragDrop({onTargetDrop: function (target) {
                                                          var data = this.data,
                                                              prefix, id,paneID;
                                                              if (data &&
                                                                  data.entID) {
                                                                prefix = data.entID.split(":");
                                                                id = prefix[1];
                                                                prefix = prefix[0];
                                                                paneID = target.attr('class').match(/pane(\d+)/)[0];
                                                                if (paneID) {
                                                                  srchVE.layoutMgr.loadPaneContent(prefix+id,paneID);
                                                                }
                                                                if (srchVE.curCmd) {
                                                                  srchVE.searchCurResourcePanel.removeClass(srchVE.curCmd);
                                                                  $('.reportpanel',srchVE.searchCurResourcePanel).remove();
                                                                  delete srchVE.curCmd;
                                                                }
                                                              } else {
                                                                return false;
                                                              }
                                                           }});
      }
    }
  },


/**
* put your comment there...
*
* @param entities
*/

  createBlnResourcePanel: function(entities){
    var srchVE = this, id, i, resDiv, resLabel, blnGID,
        baseline, image, resDiv,
        resPanel = $('<div id="baseline" class="resourcepanel"/>');
    for (id in entities) {
      blnGID = "bln:" + id;
      baseline = entities[id];
      image = this.dataMgr.getEntity('img',baseline.imageID);
      resLabel = (image && image.value)? image.value:baseline.value;
      thumbUrl = (baseline.thumbUrl?baseline.thumbUrl:((image && image.thumbUrl)? image.thumbUrl:(baseline.url?baseline.url:image.uploadUI)));
      resLabel = resLabel.substr(resLabel.lastIndexOf("/")+1);
      resDiv =$('<div class="blnresource dragresource"><img src="'+thumbUrl+'" class="resImageIconBtn"/>' + resLabel +'</div>');
      resDiv.append($('<div id="'+blnGID+'" class="draghandle"/>'));
      resPanel.append(resDiv);
    }
    return resPanel;
  },


/**
* put your comment there...
*
* @param resType
* @param entities
*/

  createEdnResourcePanel: function(resType,entities){
    var srchVE = this, id, resDiv, resLabel, ednGID, rptButton,rptPanel, ckn, text,
        edition, resDiv, resPanel = $('<div id="'+resType+'" class="resourcepanel"/>');
    text = this.dataMgr.getEntity('txt',this.getCursorTextID());
    ckn = text.CKN;
    for (id in entities) {
      ednGID = "edn:" + id;
      edition = entities[id];
      resLabel = edition.value;
      resDiv =$('<div class="ednresource dragresource"><span class="reportmenuitemlabellvl1">' + resLabel +'</span></div>');
      resDiv.append($('<button class="res-report resFontIconBtn" '+
                         ' title="Edition reports">&#x1F4DA;</button>'));
      if ((resType == 'published' || resType == 'research' && !edition.readonly) && this.dataMgr.username) {
        resDiv.append($('<button class="res-clone resFontIconBtn"' +
                           ' title="Clone Edition">&nbsp;+&#x1F5CB;</button>'));
      }
      resDiv.append($('<div id="'+ednGID+'" class="draghandle"/>'));
      resPanel.append(resDiv);
      $('button.res-report',resDiv).unbind('click')
                          .bind('click', function (e) {
                            var btn = this;
                            srchVE.showEdnReportMenu(e,btn);
                          })
                          .prop('GID',ednGID);
      $('button.res-clone',resDiv).unbind('click')
                          .bind('click', function (e) {
                            var btn = this;
                            srchVE.cloneEdition(e,btn);
                          })
                          .prop('GID',ednGID);
    }
    if (resType == 'research'  && this.dataMgr.username) {// add new edition button
      resDiv =$('<div class="dragresource ednresource"> New Edition </div>');
      resPanel.append(resDiv);
      resDiv.unbind('click').bind('click', function (e) {
                                var btn = this;
                                srchVE.newEdition(e,btn);
                              })
                            .prop('GID', "txt:"+ srchVE.getCursorTextID());

    }
    return resPanel;
  },


/**
* put your comment there...
*
*/

  addText: function () {
    DEBUG.traceEntry("addText");
    var srchVE = this;
    if (this.entPropVE && this.entPropVE.createNewText) {
      this.entPropVE.createNewText();
      this.showProperties(true);
    }
    DEBUG.traceExit("addText");
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  newBaseline: function(event,button){
    if (!this.dataMgr.username ) {
      alert("BEEP!!!");
      return;
    }
    var srchVE = this, rptPanel,
        imgID = $(button).prop("imgID"),
        txtID = $(button).prop("txtID");
    DEBUG.traceEntry("newBaseline"," search editor");
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/saveBaseline.php?db='+dbName,
        data:{imgID:imgID,txtID:txtID},
        asynch: true,
        success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                srchVE.dataMgr.updateLocalCache(data,srchVE.getCursorTextID());
                if (data && data.entities && data.entities.insert && data.entities.insert.edn) {
                  var ednID, text = srchVE.dataMgr.getEntity('txt',srchVE.getCursorTextID());
                  if (text && !text.ednIDs) {
                    text.ednIDs = [];
                  }
                  for (ednID in data.entities.insert.edn) {
                    if (text.ednIDs.indexOf(ednID) == -1){
                      text.ednIDs.push(ednID);
                    }
                  }
                }
                srchVE.updateCursorInfoBar();
              }
        },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 $('#errorMsg',userVE.signInUI).html("Invalid Username or Password!");
        }
    });// end ajax
    DEBUG.traceExit("newBaseline"," user editor");
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  cloneEdition: function(event,button){
    if (!this.dataMgr.username ) {
      alert("BEEP!!!");
      return;
    }
    var srchVE = this, rptPanel,
        gid = $(button).prop("GID");
    DEBUG.traceEntry("cloneEdition"," search editor");
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/cloneEdition.php?db='+dbName,
        data:{ednID:gid.substring(4)},
        asynch: true,
        success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                srchVE.dataMgr.updateLocalCache(data,srchVE.getCursorTextID());
                if (data && data.entities && data.entities.insert && data.entities.insert.edn) {
                  var ednID, text = srchVE.dataMgr.getEntity('txt',srchVE.getCursorTextID());
                  if (text && !text.ednIDs) {
                    text.ednIDs = [];
                  }
                  for (ednID in data.entities.insert.edn) {
                    if (text.ednIDs.indexOf(ednID) == -1){
                      text.ednIDs.push(ednID);
                    }
                  }
                }
                srchVE.updateCursorInfoBar();
              }
        },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 $('#errorMsg',userVE.signInUI).html("Invalid Username or Password!");
        }
    });// end ajax
    DEBUG.traceExit("cloneEdition"," user editor");
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  newEdition: function(event,button){
    if (!this.dataMgr.username) {
      alert("BEEP!!!");
      return;
    }
    var srchVE = this, rptPanel,
        txtGID = $(button).prop("GID");
    DEBUG.traceEntry("newEdition"," search editor");
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/createEdition.php?db='+dbName,
        data:{txtID:txtGID.substring(4)},
        asynch: true,
        success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                srchVE.dataMgr.updateLocalCache(data,srchVE.getCursorTextID());
                if (data && data.entities && data.entities.insert && data.entities.insert.edn) {
                  var ednID, text = srchVE.dataMgr.getEntity('txt',srchVE.getCursorTextID());
                  if (text && !text.ednIDs) {
                    text.ednIDs = [];
                  }
                  for (ednID in data.entities.insert.edn) {
                    if (text.ednIDs.indexOf(ednID) == -1){
                      text.ednIDs.push(ednID);
                    }
                  }
                }
                srchVE.updateCursorInfoBar();
              }
        },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 $('#errorMsg',userVE.signInUI).html("Invalid Username or Password!");
        }
    });// end ajax
    DEBUG.traceExit("newEdition"," user editor");
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  showEdnReportMenu: function(event,button){
    var srchVE = this, rptPanel,
        gid = $(button).prop("GID"),
        parentResource = $(button).parent();
    if (parentResource.next().hasClass('reportpanel')) {
      parentResource.next().remove();
    } else {
      $('.reportpanel',this.searchCurResourcePanel).remove();
      rptPanel = this.createReportPanel(gid);
      rptPanel.insertAfter(parentResource);
    }
  },


/**
* put your comment there...
*
* @param ednGID
*/

  createReportPanel: function(ednGID){
    var srchVE = this, rptDiv, i, id, catalog, catTypeLabel = "Glossary",
        edition = this.dataMgr.getEntityFromGID(ednGID),
        txtID = this.getCursorTextID(),
        rptPanel = $('<div id="rpts-'+ednGID+'" class="reportpanel"/>');
    rptDiv = $('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">Word List</div></div>');
    rptDiv.append($('<div id="wl-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    if (edition.catIDs && edition.catIDs.length) {
      for (i=0; i<edition.catIDs.length; i++) {
        id = edition.catIDs[i];
        catalog = this.dataMgr.getEntity("cat",id);
        if (catalog.typeID && this.dataMgr.getTermFromID(catalog.typeID)) {
          catTypeLabel = this.dataMgr.getTermFromID(catalog.typeID);
        }
        if (catalog && catalog.value) {
          rptDiv =$('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">'+catalog.value+'</div></div>');
        } else {
          rptDiv =$('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">'+catTypeLabel+'</div></div>');
        }
        rptDiv.append($('<div id="wl-cat:'+id+'" class="draghandle"/>'));
        rptPanel.append(rptDiv);
      }
    }
    rptDiv =$('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">Structures</div></div>');
    rptDiv.append($('<div id="sq-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =$('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">Paleography</div></div>');
    rptDiv.append($('<div id="pa-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =$('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">Translation</div></div>');
    rptDiv.append($('<div id="tr-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =$('<div class="reportmenuitem"><div class="reportmenuitemlabellvl2">Chāyā</div></div>');
    rptDiv.append($('<div id="cy-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
/*    rptDiv =($('<div class="reportmenuitem">Phonology</div>'));
    rptDiv.append($('<div id="ph-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =($('<div class="reportmenuitem">Palaeography</div>'));
    rptDiv.append($('<div id="pa-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =($('<div class="reportmenuitem">Morphology</div>'));
    rptDiv.append($('<div id="mo-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =($('<div class="reportmenuitem">Parallels</div>'));
    rptDiv.append($('<div id="pr-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    rptDiv =($('<div class="reportmenuitem">Bibliography</div>'));
    rptDiv.append($('<div id="bi-'+ednGID+'" class="draghandle"/>'));
    rptPanel.append(rptDiv);
    */
      $('.draghandle',rptPanel).jqxDragDrop({dropTarget: $('.editContainer'),
                                              dropAction: 'none',
                                              revert:false});
      $('.draghandle',rptPanel).unbind('dragStart')
                                .bind('dragStart', function (e) {
                                      if ("textEditPage" != srchVE.layoutMgr.getContentPage()) {
                                        e.preventDefault();
                                        return false;
                                      }
                                      $(this).jqxDragDrop('data', {
                                                                entID: this.id,
                                                                txtID: txtID});
                                      });
      $('.draghandle',rptPanel).jqxDragDrop({onTargetDrop: function (target) {
                                                var data = this.data,
                                                    prefix, id,paneID;
                                                    if (data &&
                                                        data.entID) {
                                                      prefix = data.entID.split(":");
                                                      id = prefix[1];
                                                      prefix = prefix[0];
                                                      paneID = target.attr('class').match(/pane(\d+)/)[0];
                                                      if (paneID) {
                                                        srchVE.layoutMgr.loadPaneContent(prefix+id,paneID);
                                                      }
                                                      if (srchVE.curCmd) {
                                                        srchVE.searchCurResourcePanel.removeClass(srchVE.curCmd);
                                                        $('.reportpanel',srchVE.searchCurResourcePanel).remove();
                                                        delete srchVE.curCmd;
                                                      }
                                                    } else {
                                                      return false;
                                                    }
                                                 }});

    return rptPanel;
  },


/**
* put your comment there...
*
* @param resType
* @param prefix
* @param entities
*/

  createResourcePanel: function(resType,prefix,entities){
    var srchVE = this, id, resDiv, resLabel, entGID, txtID = this.getCursorTextID(),
        curText = this.dataMgr.getEntity('txt',txtID),
        curEntity, thumbUrl,
        resPanel = $('<div id="'+resType+'" class="resourcepanel"/>');
    for (id in entities) {
      if (!entities[id]) {
        continue;
      }
      entGID = prefix +":" + id;
      curEntity = this.dataMgr.getEntityFromGID(entGID);
      if (prefix == "img" && curEntity && (curEntity.thumbUrl || curEntity.url)) {
        thumbUrl = curEntity.thumbUrl?curEntity.thumbUrl:curEntity.url;
      }
      resLabel = entities[id].value;
      resDiv = $('<div class="'+prefix+'resource dragresource"><span class="reportmenuitemlabellvl1">' +
                 (thumbUrl? '<img class="resImageIconBtn img'+id+'" src="'+thumbUrl+'" alt="Thumbnail not available"/>':'')+
                 resLabel +'</span></div>');
      if (resType == 'image' && !curText.readonly && this.dataMgr.username) {
        resDiv.append($('<button class="res-newbaseline resFontIconBtn"' +
                           ' title="Create baseline">&nbsp;+&#x26CB;</button>'));
      }
      resDiv.append($('<div id="'+entGID+'" class="draghandle"/>'));
      resPanel.append(resDiv);
      $('button.res-newbaseline',resDiv).unbind('click')
                          .bind('click', function (e) {
                            var btn = this;
                            srchVE.newBaseline(e,btn);
                          })
                          .prop('txtID',txtID)
                          .prop('imgID',id);
    }
    return resPanel;
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  showCatalogResources: function(event,button){
    var srchVE = this,
        targetExist = "textEditPage" != srchVE.layoutMgr.getContentPage(),
        cmd = 'show' + button.className.match(/res-([^\s]+)/)[1];
    if (this.catCmd) {
      this.searchCatResourcePanel.removeClass(this.catCmd);
      if (this.catCmd == cmd) {
        delete this.catCmd;
        return
      }
    }
//    $('.draghandle',this.searchCurResourcePanel).jqxDragDrop({disabled:targetExist});
    this.catCmd = cmd;
    this.searchCatResourcePanel.toggleClass(this.catCmd);
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  showCursorResources: function(event,button){
    var srchVE = this,
        targetExist = "textEditPage" != srchVE.layoutMgr.getContentPage(),
        cmd = 'show' + button.className.match(/res-([^\s]+)/)[1];
    if (this.curCmd) {
      this.searchCurResourcePanel.removeClass(this.curCmd);
      if (this.curCmd == cmd) {
        delete this.curCmd;
        return
      }
    }
//    $('.draghandle',this.searchCurResourcePanel).jqxDragDrop({disabled:targetExist});
    this.curCmd = cmd;
    this.searchCurResourcePanel.toggleClass(this.curCmd);
  },


/**
* put your comment there...
*
* @param txtID
*
* @returns {Object}
*/

  getTextResources: function(txtID) {
    var srchVE = this, textEntity = this.dataMgr.getEntity('txt',txtID),
        resources = {'cnt':{}}, i, cnt,
        blnID, baseline, imgID, image,tmdID, textmeta, ednID, edition, atbID, attrib,
        refIDs = {}, resrchEdnIDs = [], pubEdnIDs = [],
        ednPublishTypeID = "" + this.dataMgr.termInfo.idByTerm_ParentLabel['published-editiontype'],//term dependency
        ednResearchTypeID = "" + this.dataMgr.termInfo.idByTerm_ParentLabel['research-editiontype'],//term dependency
        imgBlnTypeID = "" + this.dataMgr.termInfo.idByTerm_ParentLabel['image-baselinetype'];//term dependency

    if (textEntity) {
      //store text's reference IDs
      if (textEntity.refIDs && textEntity.refIDs.length) {
        refIDs = textEntity.refIDs;
      }
      //count image baselines
      if (textEntity.blnIDs && textEntity.blnIDs.length) {
        cnt=0;
        for (i=0; i<textEntity.blnIDs.length; i++) {
          blnID = textEntity.blnIDs[i];
          baseline = this.dataMgr.getEntity('bln',blnID);
          if (baseline && baseline.type == imgBlnTypeID) {
            if (!resources['bln']) {
              resources['bln'] = {};
            }
            resources['bln'][blnID] = baseline;
            cnt++;
          }
        }
        if (cnt) {
          resources['cnt']['bln'] = cnt;
        }
      }
      //images
      if (textEntity.imageIDs && textEntity.imageIDs.length) {
        cnt=0;
        for (i=0; i<textEntity.imageIDs.length; i++) {
          imgID = textEntity.imageIDs[i];
          image = this.dataMgr.getEntity('img',imgID);
          if (image) {
            if (!resources['img']) {
              resources['img'] = {};
            }
            resources['img'][imgID] = image;
            cnt++;
          }
        }
        if (cnt) {
          resources['cnt']['img'] = cnt;
        }
      }
      //count txtmetadata (types?)
      if (textEntity.tmdIDs && textEntity.tmdIDs.length) {
        cnt=0;
        for (i=0; i<textEntity.tmdIDs.length; i++) {
          tmdID = textEntity.tmdIDs[i];
          textmeta = this.dataMgr.getEntity('tmd',tmdID);
          if (textmeta) {
            if (!resources['tmd']) {
              resources['tmd'] = {};
            }
            if (!resources['tmd'][tmdID]) {
              resources['tmd'][tmdID] = textmeta;
              cnt++;
            }
//            if (textmeta.ednIDs && textmeta.ednIDs.length) {
//              if (textmeta.typeIDs && textmeta.typeIDs.length && textmeta.typeIDs.indexOf(tmdPublishTypeID) > -1) {
//                pubEdnIDs = pubEdnIDs.concat(textmeta.ednIDs);
//              } else {
//                resrchEdnIDs = resrchEdnIDs.concat(textmeta.ednIDs);
//              }
//            }
//            if (textmeta.refIDs && textmeta.refIDs.length) {
//              refIDs = refIDs.concat(textmeta.refIDs);
//            }
          }
        }
        if (cnt) {
          resources['cnt']['tmd'] = cnt;
        }
      }
      //count editions both published and research (other types)
      if (textEntity.ednIDs && textEntity.ednIDs.length) {
        pcnt=0;
        rcnt=0;
        for (i=0; i<textEntity.ednIDs.length; i++) {
          ednID = textEntity.ednIDs[i];
          edition = this.dataMgr.getEntity('edn',ednID);
          if (edition) {
            if (!resources['edn']) {
              resources['edn'] = {};
              resources['cnt']['edn'] = {};
            }
            if (edition.typeID == ednPublishTypeID){
              if (!resources['edn']['published']) {
                resources['edn']['published'] = {};
              }
              if (!resources['edn']['published'][ednID]) {
                resources['edn']['published'][ednID] = edition;
                pcnt++;
              }
            } else if (edition.typeID == ednResearchTypeID){
              if (!resources['edn']['research']) {
                resources['edn']['research'] = {};
              }
              if (!resources['edn']['research'][ednID]) {
                resources['edn']['research'][ednID] = edition;
                rcnt++;
              }
            }
          }
        }
        if (pcnt) {
          resources['cnt']['edn']['published'] = pcnt;
        }
        if (rcnt) {
          resources['cnt']['edn']['research'] = rcnt;
        }
      }
      //references  (types?)
      if (refIDs && refIDs.length) {
        cnt=0;
        for (i=0; i<refIDs.length; i++) {
          atbID = refIDs[i];
          attrib = this.dataMgr.getEntity('atb',atbID);
          if (attrib) {
            if (!resources['atb']) {
              resources['atb'] = {};
            }
            resources['atb'][atbID] = attrib;
            cnt++;
          }
        }
        if (cnt) {
          resources['cnt']['atb'] = cnt;
        }
      }
    }
    return resources;
  },


/**
* put your comment there...
*
* @param object e System event object
*/

  searchChangeHandler: function (e) {
    var search = this.searchInput.val(),
        playButton = $('#play',this.searchMediaBar),
        cmd = playButton.attr('class').match(/med-([^\s]+)/)[1];
    if (this.search != search) {
      if (cmd != 'newsearchpage') {
        playButton.removeClass("med-"+cmd).addClass("med-newsearchpage");
      }
    }
  },


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

 searchKeypressHandler: function (e) {
    var search = this.searchInput.val(),
        playButton = $('#play',this.searchMediaBar),
        cmd = playButton.attr('class').match(/med-([^\s]+)/)[1];
    if (this.search != search) {
      if (e.keyCode == 13 || e.which == 13 || e.key == 'Enter') {
        this.doLocalSearch();
        this.search = search;
        this.layoutMgr.switchContentPage("search");
        playButton.removeClass("med-newsearchpage").addClass("med-textpage");
        e.stopImmediatePropagation();
        return false;
      } else {
        if (cmd != 'newsearchpage') {
          playButton.removeClass("med-"+cmd).addClass("med-newsearchpage");
        }
      }
    }
    return true;
  },


/**
* put your comment there...
*
*/

  createSearchBar: function () {
    if (this.searchInput) { //already created just reposition
      return;
    }
    var srchVE = this,
        searchNavHdr = $('<div id="searchNavHdr"><div class="toolPanelLabel">Catalog</div></div>');
        searchNavPanel = $('<div id="searchNavPanel" class="searchPanel"/>');
    this.searchCatInfoBar = $('<div id="searchCatalogInfoBar"><div class="catalogTitle">Resources</div></div>');
    searchNavPanel.append(this.searchCatInfoBar);
    this.searchInput = $('<input type="text" id="searchInput" />'),
    this.searchInput.jqxInput({placeHolder: "Enter Search Here"});
    this.searchInput.unbind('change').bind('change', function(e) {
//                                                      srchVE.searchChangeHandler(e);
                                                    });
    this.searchInput.unbind('keypress').bind('keypress', function(e) {
                                                      srchVE.searchKeypressHandler(e);
                                                    });
    searchNavPanel.append(this.searchInput);
    this.searchMediaBar = this.createMediaBtnBar();
    searchNavPanel.append(this.searchMediaBar);
    this.searchCurInfoBar = $('<div id="searchCursorInfoBar">Loading ...</div>');
    searchNavPanel.append(this.searchCurInfoBar);
    this.searchNavBar.append(searchNavHdr).append(searchNavPanel);
    this.searchNavBar.jqxExpander({expanded:true,
                                              expandAnimationDuration:50,
                                              collapseAnimationDuration:50});
    navBarExpHdr=this.searchNavBar.children().first();
    navBarExpHdr.addClass('navExpandableHeader');
//    navBarExpHdr.css('background-color','white');
//    navBarExpHdr.css('border','none');
  },


/**
* put your comment there...
*
* @param event
* @param button
*/

  moveResultsCursor: function(event, button){
    var srchVE = this,
        cmd = button.className.match(/med-([^\s]+)/)[1];
    switch (cmd) {
      case "textpage":
        this.layoutMgr.switchContentPage("textEdit");
        $(button).removeClass("med-textpage").addClass("med-searchpage");
        break;
      case "searchpage":
        this.layoutMgr.switchContentPage("search");
        $(button).removeClass("med-searchpage").addClass("med-textpage");
        break;
      case "newsearchpage":
        this.loadSearch();
        this.layoutMgr.switchContentPage("search");
        $(button).removeClass("med-newsearchpage").addClass("med-textpage");
        break;
      case "begin":
        this.cursorPos = 0;
        this.updateCursorInfoBar();
        break;
      case "end":
        this.cursorPos = this.cursorMap.length-1;
        this.updateCursorInfoBar();
        break;
      case "next":
        if (this.cursorPos < (this.cursorMap.length-1)) {
          this.cursorPos += 1;
          this.updateCursorInfoBar();
        }
        break;
      case "prev":
        if (this.cursorPos > 0) {
          this.cursorPos -= 1;
          this.updateCursorInfoBar();
        }
        break;
      case "nextpage":
        if (this.cursorPos < (this.cursorMap.length-1)) {
          this.cursorPos += this.pageSize;
          if (this.cursorPos > (this.cursorMap.length-1)) {
            this.cursorPos = this.cursorMap.length-1
          }
          this.updateCursorInfoBar();
        }
        break;
      case "prevpage":
        if (this.cursorPos > 0) {
          this.cursorPos -= this.pageSize;
          if (this.cursorPos < 0) {
            this.cursorPos = 0
          }
          this.updateCursorInfoBar();
        }
        break;
      default:
        alert("media button command = "+cmd+" not implemented");
    }
  },


/**
* put your comment there...
*
*/

  createMediaBtnBar: function(){
    var srchVE = this;
        mediaBtnBar = $('<div id="mediaBtnBar">' +
                          '<button class="med-begin medFontIconBtn"><span/></button>' +
                          '<button class="med-prevpage medFontIconBtn"><span/></button>' +
                          '<button class="med-prev medFontIconBtn"><span/></button>' +
                          '<button id= "play" class="med-textpage medFontIconBtn"><span/></button>' +
                          '<button class="med-next medFontIconBtn"><span/></button>' +
                          '<button class="med-nextpage medFontIconBtn"><span/></button>' +
                          '<button class="med-end medFontIconBtn"><span/></button>' +
                        '</div>');
        $('button',mediaBtnBar).unbind('click')
                          .bind('click', function (e) {
                            var btn = this;
                            srchVE.moveResultsCursor(e,btn);
                          });
    return mediaBtnBar;
  }
}

