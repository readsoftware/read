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
* @subpackage  Editors
*/
var EDITORS = EDITORS || {};

/**
* Constructor for Paleography Viewer/Editor Object
*
* @type Object
*
* @param paleoVECfg is a JSON object with the following possible properties
*  "edition" an entity data element which defines the edition and all it's structures.
*
* @returns {PaleoVE}

*/

EDITORS.PaleoVE = function(paleoCfg) {
  var paleoVE = this;
  //read configuration and set defaults
  this.config = paleoCfg;
  this.type = "PaleoVE";
  this.editDiv = paleoCfg['editDiv'] ? paleoCfg['editDiv']:null;
  this.dataMgr = paleoCfg['dataMgr'] ? paleoCfg['dataMgr']:null;
  this.catID = (paleoCfg['catID'] && this.dataMgr &&
    this.dataMgr.entities.cat[paleoCfg['catID']])?paleoCfg['catID']:null;
  this.ednID = (paleoCfg['ednID'] && this.dataMgr &&
    this.dataMgr.entities.edn[paleoCfg['ednID']]) ? paleoCfg['ednID']:null;
  this.layoutMgr = paleoCfg['layoutMgr'] ? paleoCfg['layoutMgr']:null;
  this.eventMgr = paleoCfg['eventMgr'] ? paleoCfg['eventMgr']:null;
  this.id = paleoCfg['id'] ? paleoCfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.init();
  return this;
};

/**
* Paleography viewer/editor
*
* @type Object
*/
EDITORS.PaleoVE.prototype = {

/**
* initialization funciton to create the html container frame with an expandable side panel
* for property editors and contextual/workflow editors.
* @author Stephen White  <stephenawhite57@gmail.com>
*/

  init: function() {
    var paleoVE = this, propMgrCfg;
    this.splitterDiv = $('<div id="'+this.id+'splitter"/>');
    this.contentDiv = $('<div id="'+this.id+'textContent" class="paleoContentDiv" spellcheck="false" ondragstart="return false;"/>');
    this.propertyMgrDiv = $('<div id="'+this.id+'propManager" class="propertyManager"/>');
    this.splitterDiv.append(this.contentDiv);
    this.splitterDiv.append(this.propertyMgrDiv);
    $(this.editDiv).append(this.splitterDiv);
    this.splitterDiv.jqxSplitter({ width: '100%',
      height: '100%',
      orientation: 'vertical',
      splitBarSize: 1,
      showSplitBar:false,
      panels: [{ size: '60%', min: '250', collapsible: false},
        { size: '40%', min: '150', collapsible: true,collapsed : true}] });
    propMgrCfg ={id: this.id,
      propertyMgrDiv: this.propertyMgrDiv,
      editor: paleoVE,
      propVEType: "entPropVE",
      dataMgr: this.dataMgr,
      splitterDiv: this.splitterDiv };
    if (this.catID) {
      propMgrCfg['catID'] = this.catID;
    } else {
      propMgrCfg['ednID'] = this.ednID;
    }
    this.propMgr = new MANAGERS.PropertyManager(propMgrCfg);
    this.displayProperties = this.propMgr.displayProperties;
    this.splitterDiv.unbind('focusin').bind('focusin',this.layoutMgr.focusHandler);
    this.createStaticToolbar();
    this.displayPaleographicChart();
  },

/**
* put your comment there...
*
*/

  createStaticToolbar: function () {
    var paleoVE = this;
    var btnEditModeName = this.id+'editmode',
        btnShowPropsName = this.id+'showprops',
        btnDownloadSegName = this.id+'downloadseg',
        ddbtnCurTagName = this.id+'curtagbutton',
        treeCurTagName = this.id+'curtagtree';
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    this.editModeBtnDiv = $('<div class="toolbuttondiv">' +
      '<button class="toolbutton" id="'+btnEditModeName +
      '" title="Change Paleography edit mode">View</button>'+
      '<div class="toolbuttonlabel">Edit mode</div>'+
      '</div>');
    this.editModeBtn = $('#'+btnEditModeName,this.editModeBtnDiv);
    this.editModeBtn.unbind('click').bind('click',function(e) {
      if (paleoVE.changeEditMode) {
        paleoVE.changeEditMode($(this));
      }
    });

    if (this.dataMgr && this.dataMgr.getPaleographyTags()) {
      this.curTagBtnDiv = $('<div class="toolbuttondiv">' +
        '<div id="'+ ddbtnCurTagName+'"><div id="'+ treeCurTagName+'"></div></div>'+
        '<div class="toolbuttonlabel">Current Tag</div>'+
        '</div>');
      this.tagTree = $('#'+treeCurTagName,this.curTagBtnDiv);
      this.tagDdBtn = $('#'+ddbtnCurTagName,this.curTagBtnDiv);
      this.tagTree.jqxTree({
        source: paleoVE.dataMgr.getPaleographyTags(),
        //       hasThreeStates: false, checkboxes: true,
        width: '250px',
        theme:'energyblue'
      });
      this.tagTree.on('select', function (event) {
        var args = event.args, dropDownContent = '',
        item =  paleoVE.tagTree.jqxTree('getItem', args.element);
        if (item.value) {
          //save selected tag to edition VE
          paleoVE.curTagID = item.value.replace(":","");
          paleoVE.curTagLabel = item.label;
          //display current tag
          dropDownContent = '<div class="listDropdownButton">' + item.label + '</div>';
          paleoVE.tagDdBtn.jqxDropDownButton('setContent', dropDownContent);
          paleoVE.tagDdBtn.jqxDropDownButton('close');
        }
      });
      this.tagDdBtn.jqxDropDownButton({width:95, height:28,closeDelay: 50});
    }

    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
      '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
      '" title="Show/Hide property panel">&#x25E8;</button>'+
      '<div class="toolbuttonlabel">Properties</div>'+
      '</div>');
    this.propertyBtn = $('#'+btnShowPropsName,this.propertyBtnDiv);
    this.propertyBtn.unbind('click').bind('click',function(e) {
      paleoVE.showProperties(!$(this).hasClass("showUI"));
    });
    this.viewToolbar.append(this.propertyBtnDiv);

    this.downloadSegmenturl = basepath+"/services/exportPaleography.php?db="+dbName+"&ednID="+paleoVE.ednID+"&download=1";
    this.downloadSegBtnDiv = $('<div class="toolbuttondiv">' +
                            '<a href="'+this.downloadSegmenturl+'" >'+
                            '<button class="toolbutton iconbutton" id="'+btnDownloadSegName+
                              '" title="Download Segment Images">Download</button></a>'+
                            '<div class="toolbuttonlabel">Segments</div>'+
                           '</div>');
    this.viewToolbar.append(this.downloadSegBtnDiv);

    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    this.editToolbar.append(this.editModeBtnDiv);
    if (this.curTagBtnDiv) {
      this.editToolbar.append(this.curTagBtnDiv);
      this.curTagBtnDiv.hide();
    }
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
  },


/**
* put your comment there...
*
* @param bShow bool indicating whether to show or hide the properties panel
*/

  showProperties: function (bShow) {
    var paleoVE = this;
    if (paleoVE.propMgr &&
      typeof paleoVE.propMgr.displayProperties == 'function'){
      paleoVE.propMgr.displayProperties(bShow);
      if (this.propertyBtn.hasClass("showUI") && !bShow) {
        this.propertyBtn.removeClass("showUI");
      } else if (!this.propertyBtn.hasClass("showUI") && bShow) {
        this.propertyBtn.addClass("showUI");
      }
    }
  },


/**
* put your comment there...
*
* @param editModeBtn
* @param mode
*/

  changeEditMode: function (editModeBtn,mode) {
    var paleoVE = this, sclSort;
    if (!editModeBtn) {
      editModeBtn = paleoVE.editModeBtn;
    }
    if (!this.editMode && (!mode || mode == "cellview")) {
      paleoVE.editMode = "cellview";
      editModeBtn.html("CellView");
      $(paleoVE.contentDiv).addClass('cellview');
    } else if (this.editMode == "cellview" && (!mode || mode == "tag")) {
      paleoVE.editMode = "tag";
      editModeBtn.html("Tagging");
      this.curTagBtnDiv.show();
    } else if (this.editMode == "tag" && !mode || mode == "view") {
      delete paleoVE.editMode;
      editModeBtn.html("View");
      delete paleoVE.curTagSclCell;
      $(paleoVE.contentDiv).removeClass('cellview');
      this.curTagBtnDiv.hide();
      if (this.pTaggingHeaderSpan.gSort) {
        this.refreshSclCellDiv(this.pTaggingHeaderSpan.gSort);
      }
    }
  },


/**
* put your comment there...
*
* @param btnSortMode
*/

  changeSortMode: function (btnSortMode) {
    var paleoVE = this;
    if (!btnSortMode) {
      btnSortMode = this.btnSortMode;
    }
    if (!btnSortMode) {
      return;
    }
    if (!this.sortMode ||this.sortMode == "base") {
      this.sortMode = "footmark";
      btnSortMode.html("Sort: FootMark");
    } else if (this.sortMode == "footmark" ) {
      this.sortMode = "vowel";
      btnSortMode.html("Sort: Vowel");
    } else {
      this.sortMode = "base";
      btnSortMode.html("Sort: Base");
    }
    this.displaySclCell();
  },


/**
* put your comment there...
*
* @param gSort
*/

  refreshSclCellDiv: function(gSort) {
    var sclCell = this.sclCellsBySortCode[gSort],
    chartCell = $('.paleoChartCell.srt'+gSort.substring(2),this.pChart);
    chartCell.replaceWith(this.getSclCellDiv(sclCell,gSort));
    this.addEventHandlers();
  },


/**
* put your comment there...
*
* @param int catID Catalog entity id
*/

  refreshDisplay: function (catID) {
    if (!this.catID && catID) {
      this.catID = catID;
      if (this.layoutMgr) {
        this.layoutMgr.editors[this.id].config.entGID = "pl-cat:" + catID;
        this.layoutMgr.pushState();
        if (this.layoutMgr.editors['searchVE']) {
          this.layoutMgr.editors['searchVE'].updateCursorInfoBar();
        }
      }
    }
    this.displayPaleographicChart();
  },


/**
* put your comment there...
*
*/

  prevCell: function () {
    /* TBD
    var curEntry = $('.selected',this.contentDiv).parent();
    if (curEntry.length && curEntry.prev().hasClass('sclCell')) {
    curEntry.prev().children().first().trigger('dblclick');
    } else {
    UTILITY.beep();
    }
    */
  },


/**
* put your comment there...
*
*/

  nextCell: function () {
    /* TBD
    var curEntry = $('.selected',this.contentDiv).parent();
    if (curEntry.length && curEntry.next().length && curEntry.next().hasClass('sclCell')) {
    curEntry.next().children().first().trigger('dblclick');
    } else {
    UTILITY.beep();
    }
    */
  },


/**
* put your comment there...
*
* @returns {Object}
*/

  getSclIDToTypeLookups: function () {
    var usedTrmIDs = Object.keys(this.dataMgr.tagIDToAnoID),
    sclIDbyTypes = {}, sclID2Type = {}, defaultSclIDs = {}, tagLabel,
    tags = this.dataMgr.getPaleographyTags(),baseTypes,footMarkTypes,vowelTypes,
    defaultTag, i,j,annotation,gid;
    //getbasetypes
    for (i in tags) {
      tagLabel = tags[i].label.toLowerCase();
      if (tagLabel == "basetype"){
        baseTypes = tags[i].items;
      }
      if (tagLabel == "footmarktype"){
        footMarkTypes = tags[i].items;
      }
      if (tagLabel == "voweltype"){
        vowelTypes = tags[i].items;
      }
      if (tagLabel == "default"){
        defaultTag = tags[i];
      }
    }
    if (baseTypes && baseTypes.length) {
      for (i in baseTypes) {
        if (baseTypes[i].value.substring(0,3) == 'ano') {//base type used for tagging
          annotation = this.dataMgr.getEntityFromGID(baseTypes[i].value);
          if (annotation && annotation.linkedToIDs && annotation.linkedToIDs.length) {
            for (j in annotation.linkedToIDs) {
              gid = annotation.linkedToIDs[j].replace(':','');
              if (gid.substring(0,3) == 'scl') {
                sclID2Type[gid.substring(3)] = baseTypes[i];
              }
            }
          }
        }
      }
      sclIDbyTypes['base'] = sclID2Type;
    }
    if (footMarkTypes && footMarkTypes.length) {
      sclID2Type = {};
      for (i in footMarkTypes) {
        if (footMarkTypes[i].value.substring(0,3) == 'ano') {//footMark type used for tagging
          annotation = this.dataMgr.getEntityFromGID(footMarkTypes[i].value);
          if (annotation && annotation.linkedToIDs && annotation.linkedToIDs.length) {
            for (j in annotation.linkedToIDs) {
              gid = annotation.linkedToIDs[j].replace(':','');
              if (gid.substring(0,3) == 'scl') {
                sclID2Type[gid.substring(3)] = footMarkTypes[i];
              }
            }
          }
        }
      }
      sclIDbyTypes['footmark'] = sclID2Type;
    }
    if (vowelTypes && vowelTypes.length) {
      sclID2Type = {};
      for (i in vowelTypes) {
        if (vowelTypes[i].value.substring(0,3) == 'ano') {//vowel type used for tagging
          annotation = this.dataMgr.getEntityFromGID(vowelTypes[i].value);
          if (annotation && annotation.linkedToIDs && annotation.linkedToIDs.length) {
            for (j in annotation.linkedToIDs) {
              gid = annotation.linkedToIDs[j].replace(':','');
              if (gid.substring(0,3) == 'scl') {
                sclID2Type[gid.substring(3)] = vowelTypes[i];
              }
            }
          }
        }
      }
      sclIDbyTypes['vowel'] = sclID2Type;
    }
    if (defaultTag) {
      sclID2Type = {};
      if (defaultTag.value.substring(0,3) == 'ano') {//default type used for tagging
        annotation = this.dataMgr.getEntityFromGID(defaultTag.value);
        if (annotation && annotation.linkedToIDs && annotation.linkedToIDs.length) {
          for (j in annotation.linkedToIDs) {
            gid = annotation.linkedToIDs[j].replace(':','');
            if (gid.substring(0,3) == 'scl') {
              sclID2Type[gid.substring(3)] = defaultTag;
            }
          }
        }
      }
      sclIDbyTypes['default'] = sclID2Type;
    }
    return sclIDbyTypes;
  },


/**
* put your comment there...
*
* @param sclCell
*/

  calcSclCellGroups: function (sclCell) {
    //sort the syllables into untyped, and the various category types.
    //getbasetypes
    var sclTag2Types = this.getSclIDToTypeLookups(),
    i, syllable, sylGrpByType = {}, untypedSyls = {},
    baseType, vowelType, footmarkType;
    if (sclCell) {
      this.curTagSclCell = sclCell;
    } else {
      sclCell = this.curTagSclCell;
    }
    //for each syllable in the group check for each type tag and place in subgroup
    for (i in sclCell.syllables) {
      syllable = sclCell.syllables[i];
      baseType = sclTag2Types.base[syllable.id];
      if(baseType) {
        syllable.bt = baseType;
        if (!sylGrpByType['base']) {
          sylGrpByType['base']={};
        }
        // has a base type then add to group
        if (!sylGrpByType.base[baseType.label]) {
          sylGrpByType.base[baseType.label] = [syllable];
        } else {
          sylGrpByType.base[baseType.label].push(syllable);
        }
      } else { // no base type tag so put in the untyped group
        if (!untypedSyls['base']) {
          untypedSyls['base']=[syllable];
        } else {
          untypedSyls['base'].push(syllable);
        }
      }
      footmarkType = sclTag2Types.footmark[syllable.id];
      if(footmarkType) {
        syllable.fmt = footmarkType;
        if (!sylGrpByType['footmark']) {
          sylGrpByType['footmark']={};
        }
        // has a base type then add to group
        if (!sylGrpByType.footmark[footmarkType.label]) {
          sylGrpByType.footmark[footmarkType.label] = [syllable];
        } else {
          sylGrpByType.footmark[footmarkType.label].push(syllable);
        }
      } else { // no base type tag so put in the untyped group
        if (!untypedSyls['footmark']) {
          untypedSyls['footmark']=[syllable];
        } else {
          untypedSyls['footmark'].push(syllable);
        }
      }
      vowelType = sclTag2Types.vowel[syllable.id];
      if(vowelType) {
        syllable.vt = vowelType;
        if (!sylGrpByType['vowel']) {
          sylGrpByType['vowel']={};
        }
        // has a vowel type then add to group
        if (!sylGrpByType.vowel[vowelType.label]) {
          sylGrpByType.vowel[vowelType.label] = [syllable];
        } else {
          sylGrpByType.vowel[vowelType.label].push(syllable);
        }
      } else { // no base type tag so put in the untyped group
        if (!untypedSyls['vowel']) {
          untypedSyls['vowel']=[syllable];
        } else {
          untypedSyls['vowel'].push(syllable);
        }
      }
      if(sclTag2Types['default'][syllable.id]) {
        syllable.def = sclTag2Types['default'][syllable.id];
      }
    }
    sclCell['typeGrps'] = sylGrpByType;
    sclCell['untyped'] = untypedSyls;
  },


/**
* put your comment there...
*
* @param sclCell
* @param gSort
*/

  displaySclCell: function (sclCell, gSort) {
    //sort the syllables into typeGrpsuntyped, and the Base types.
    //getbasetypes
    var curRow, curGrpCell, sortCat, index, catType, keys,
    i, syllable, sylGrpByType, untypedSyls,
    baseType, vowelType, footmarkType, untypedLinkedSyls = [];
    if (sclCell) {
      this.curTagSclCell = sclCell;
    } else {
      sclCell = this.curTagSclCell;
    }
    this.calcSclCellGroups(sclCell);
    sylGrpByType = sclCell.typeGrps;
    untypedSyls = sclCell.untyped;
    //change modes
    this.changeEditMode(null,"cellview");
    //update title
    this.pTaggingHeaderSpan.html(sclCell.rLabel + sclCell.cLabel);
    //create table
    this.pTaggingDiv.html("");//remove any existing groups
    if (!gSort && sclCell.gSort) {
      gSort = sclCell.gSort;
    }
    if (gSort) {
      this.pTaggingHeaderSpan.gSort = gSort;
    } else {
      this.pTaggingHeaderSpan.gSort = null;
    }
    //for each bt group create a row with header and a large cell for segments
    sortCat = this.sortMode;
    if (sylGrpByType[sortCat] && Object.keys(sylGrpByType[sortCat]).length) {
      keys = Object.keys(sylGrpByType[sortCat]).sort();
      for (index in keys) {
        catType = keys[index];
        //create row
        //add header cell
        curRow = $('<div class="taggingGroupRow">' +
          '<div class="taggingRowHeader">'+catType+'</div>' +
          '</div>' );
        this.pTaggingDiv.append(curRow);
        curGrpCell = $('<div class="taggingGrpCell"/>')
        curRow.append(curGrpCell);
        curRow.append($('<div class="padCell"/>'));
        // for each syllable add a image
        for (i in sylGrpByType[sortCat][catType]) {
          syllable = sylGrpByType[sortCat][catType][i];
          if (syllable && syllable.segID) {
            segDiv = this.createTaggingSclCell(syllable);
            if (segDiv) {
              curGrpCell.append(segDiv);
            }
          }
        }
      }
    }
    //if any untyped create last row for untyped
    if (untypedSyls[sortCat] && Object.keys(untypedSyls[sortCat]).length) {
      //find linked syllables
      for (i in untypedSyls[sortCat]) {
        syllable = untypedSyls[sortCat][i];
        if (syllable && syllable.segID) {
          segment = this.dataMgr.getEntity('seg',syllable.segID);
          if (segment && segment.urls && segment.urls.length) {
            untypedLinkedSyls.push(syllable);
          }
        }
      }
    }
    if (untypedLinkedSyls.length) {
      //create row
      //add header cell
      curRow = $('<div class="taggingGroupRow">' +
        '<div class="taggingRowHeader">unknown</div>' +
        '</div>' );
      this.pTaggingDiv.append(curRow);
      curGrpCell = $('<div class="taggingGrpCell"/>')
      curRow.append(curGrpCell);
      curRow.append($('<div class="padCell"/>'));
      // for each syllable add a image
      for (i in untypedLinkedSyls) {
        syllable = untypedLinkedSyls[i];
        if (syllable && syllable.segID) {
          segDiv = this.createTaggingSclCell(syllable);
          if (segDiv) {
            curGrpCell.append(segDiv);
          }
        }
      }
    }
    this.addEventHandlers();
  },


/**
* put your comment there...
*
* @param syllable
*/

  createTaggingSclCell: function (syllable) {
    var segment = this.dataMgr.getEntity('seg',syllable.segID),
    url,segDiv,themeDiv;
    if (segment.urls && segment.urls.length) {
      url = segment.urls[0];//tod add code to handle separate urls
      title = syllable.value.replace('ʔ','')+ " (Line: "+syllable.line+" Akṣara:"+syllable.pos+")";
      segDiv = $('<div class="tagSegDiv scl'+syllable.id+' seg'+syllable.segID+'" title="'+title+'"/>');
      themeDiv = $('<div class="tagThematicDiv">' +
        '<div class="btTheme '+ (syllable.bt?syllable.bt.label:'BT-Un') +'"><span/></div>' +
        '<div class="fmtTheme '+ (syllable.fmt?syllable.fmt.label:'FMT-Un') +'"><span/></div>' +
        '<div class="vtTheme '+ (syllable.vt?syllable.vt.label:'VT-Un') +'"><span/></div>' +
        '</div>');
      segDiv.append(themeDiv);
      if (syllable.def) {
        segDiv.addClass("defaultSeg");
      }
      segDiv.append('<img class="taggingSegImg" alt="'+syllable.value.replace('ʔ','')+'" src="'+url+'"/>');
    }
    return segDiv;
  },


/**
* put your comment there...
*
*/

  displayPaleographicChart: function () {
    var paleoVE = this,i,j,k,cnt,sequence, html = "", physSeqGIDs, multiEd = false,sclTag2Types = this.getSclIDToTypeLookups(),
    catalog = this.catID?this.dataMgr.entities.cat[this.catID]:null, edition, ednID, fSclID, lSclID,
    ednIDs = catalog?catalog.ednIDs:[this.ednID], edition, sclIDs, seqIDs, seqGIDs, label, defLabel, colNum,
    syllable, sclSort, sclGrpSort, prevSclSort, sclCellsBySortCode = {}, seqTags = {}, prefix, id, tag, tempLabel, pos,
    entities = [], sclTagToLabel = {}, rowColumnInfo, curRow, curRowLabel, sclCell, curCellNum;
    //create sorted wordlist for all catalog lemata and edition tokens not linked as attested to a lemma
    //note that attested forms are listed in edition/line/line position order
    //find lemmata and add linked words to listsequences for edition
    if (ednIDs && ednIDs.length) {
      if (ednIDs.length > 1) {
        multiEd = true;
      }
      //for each edition find token sequences and find physical sequences and create sclID to label lookup
      for (i=0; i<ednIDs.length; i++) {
        ednID = ednIDs[i];
        edition = this.dataMgr.getEntity('edn',ednIDs[i]);
        physSeqGIDs = [];
        if ( edition && edition.seqIDs && edition.seqIDs.length) {
          seqIDs = edition.seqIDs;
          for (j=0; j<seqIDs.length; j++) {
            sequence =  this.dataMgr.getEntity('seq',seqIDs[j]);;
            //            if (sequence.typeID == this.dataMgr.termInfo.idByTerm_ParentLabel['text-sequencetype']) {
            //              txtSeqGIDs = txtSeqGIDs.concat(sequence.entityIDs);
            //            }
            if (sequence && sequence.typeID == this.dataMgr.termInfo.idByTerm_ParentLabel['textphysical-sequencetype']) {//term dependency
              physSeqGIDs = physSeqGIDs.concat(sequence.entityIDs);
            }
          }
        }
        /*        if (txtSeqGIDs && txtSeqGIDs.length) {// capture each sequence once
        for (j=0; j<txtSeqGIDs.length; j++) {
        tag = txtSeqGIDs[j].replace(":","");
        txtSeqTags[tag] = ednID;//todo: this overwrites the edition, ?? do we need to associate a line sequence with a primary edition for the reuse case??
        }
        }*/
        if (physSeqGIDs && physSeqGIDs.length) {// capture each sequence once
          for (j=0; j<physSeqGIDs.length; j++) {
            sequence =  this.dataMgr.getEntityFromGID(physSeqGIDs[j]);
            if (!sequence) {
              DEBUG.log('err',"physical line sequence not found in paleoVE for tag "+physSeqGIDs[j]);
              continue;
            }
            label = sequence.sup?sequence.sup:(sequence.label ? sequence.label:"no label index "+j);
            sclGIDs = sequence.entityIDs;
            if (label && sclGIDs.length) {//create lookup for location of word span B11-B12
              for (k=0; k<sclGIDs.length; k++) {
                tag = sclGIDs[k].replace(":","");
                //                sclTagToLabel[tag] = label;
                syllable = this.dataMgr.getEntityFromGID(tag);
                if (syllable) {
                  if (sclTag2Types.base[tag]) {
                    syllable.bt = sclTag2Types.base[tag];
                  }
                  if (sclTag2Types.footmark[tag]) {
                    syllable.fmt = sclTag2Types.footmark[tag];
                  }
                  if (sclTag2Types.vowel[tag]) {
                    syllable.vt = sclTag2Types.vowel[tag];
                  }
                  syllable.line = label;
                  syllable.pos = k+1;
                  entities.push(syllable);
                }
              }
            }
          }
        }
      }
    }
    //sort entities array using UTILITY.compareEntities
    entities.sort(UTILITY.compareEntities);
    sclSort = sclGrpSort = null;
    for (i=0; i<entities.length; i++) {
      syllable = entities[i];
      rowColumnInfo = getRowColumnInfo(syllable);
      if (rowColumnInfo === false) {//error or skip
        continue;
      }
      sclGrpSort = rowColumnInfo[0]; //gSort, columnLabel, columnOffset, rowLabel
      if (!sclCellsBySortCode[sclGrpSort]) {//start a new group
        sclCellsBySortCode[sclGrpSort] = {syllables:[syllable],gSort:sclGrpSort};
        if (rowColumnInfo[1]) {// columnLabel
          sclCellsBySortCode[sclGrpSort]['cLabel'] = rowColumnInfo[1];
        } else {// columnLabel
          sclCellsBySortCode[sclGrpSort]['cLabel'] = "";
        }
        if (rowColumnInfo[2]) {// columnNumber
          sclCellsBySortCode[sclGrpSort]['cNum'] = rowColumnInfo[2];
        }
        if (rowColumnInfo[3]) {// rowLabel
          sclCellsBySortCode[sclGrpSort]['rLabel'] = rowColumnInfo[3];
        }
      } else {
        sclCellsBySortCode[sclGrpSort]['syllables'].push(syllable);
      }
    }
    this.sclCellsBySortCode = sclCellsBySortCode;
    // create Report Title
    html = '<h3 class="paleographicReportHeader '+(this.catID?'cat'+this.catID:'edn'+this.ednID)+'">'+
            (catalog?catalog.value:'Paleography for '+edition.value)+'</h3>';
    //create chart table div
    this.contentDiv.html(html);
    this.pChartFrame = $('<div class="paleoFrameDiv"/>');
    this.pChartTable = $('<table class="paleoReportTable" />');
    this.pChartFrame.append(this.pChartTable);
    this.contentDiv.append(this.pChartFrame);
    //create column header row and append to table
    if (!sktSort) {
      this.pChartTable.append($('<thead class="paleoReportHeaderRow"><tr>' +
        '<td><div class="columnHeader"/></td>' +
        '<td><div class="columnHeader">&nbsp;</div></td>' +
        '<td><div class="lastColumnHeader"/></td>' +
        '</tr></thead>'
      ));
      colNum = 1;
    } else {
      this.pChartTable.append($('<thead class="paleoReportHeaderRow"><tr><td>' +
        '<div class="col0 columnHeader"/>' +
        '<div class="noVowel columnHeader">&nbsp;</div>' +
        '<div class="columnHeader">a</div>' +
        '<div class="columnHeader">ā</div>' +
        '<div class="columnHeader">i</div>' +
        '<div class="columnHeader">ī</div>' +
        '<div class="columnHeader">u</div>' +
        '<div class="columnHeader">ū</div>' +
        '<div class="columnHeader">ṛ</div>' +
        '<div class="columnHeader">e</div>' +
        '<div class="columnHeader">ai</div>' +
        '<div class="columnHeader">o</div>' +
        '<div class="columnHeader">au</div>' +
        '<div class="lastColumnHeader"/>' +
        '</td></tr></thead>'
      ));
      colNum = 12;
    }
    this.pChartTable.append($('<tbody><tr><td colspan="'+(colNum+2)+'"><div class="paleoChart" /></td></tr></tbody>'));
    this.pChart = $('.paleoChart',this.pChartTable);
    this.pTaggingFrame = $('<div class="paleoSclGrpTaggingDiv" />');
    this.pTaggingTable = $('<table class="paleoTaggingTable" />');
    this.pTaggingFrame.append(this.pTaggingTable);
    this.contentDiv.append(this.pTaggingFrame);
    //create column header row and append to table
    this.pTaggingTable.append($('<thead class="paleoTaggingHeaderRow"><tr>' +
      '<td class="tagNameColumnHeader"><div>Sort: base</div></td>' +
      '<td><div class="tagColumnHeader">Tagging Syllable Group <span class="taggingSyllableSpan"/></div></td>' +
      '<td class="scrollColumnHeader"><div/></td>' +
      '</tr></thead>'
    ));
    this.pTaggingTable.append($('<tbody><tr><td colspan="3"><div class="taggingDiv" /></td></tr></tbody>'));
    this.pTaggingDiv = $('.taggingDiv',this.pTaggingTable);
    this.pTaggingHeaderSpan = $('.taggingSyllableSpan',this.pTaggingTable);
    this.btnSortMode = $('.tagNameColumnHeader',this.pTaggingTable);
    this.sortMode = "base";
    //init position tracking variables
    curCellNum = 0;
    curRowLabel = '';
    //iterate through all syllable groups and calculate sclCells
    for (gSort in this.sclCellsBySortCode) {
      sclCell = this.sclCellsBySortCode[gSort];
      if (sclCell) { //output syllable cell
        //if new row header create new row and append to table
        if (sclCell.rLabel != curRowLabel) {
          if (curRow && curCellNum < colNum) {//fill out empty cells
            for(curCellNum; curCellNum<colNum; curCellNum++) {
              curRow.append($('<div class="paleoChartCell"/>'));//mark it so dblclick has context
            }
            curRow.append($('<div class="padCell"/>'));//scrollbar space
          }
          curCellNum = 0;
          if (sclCell.rLabel) {
            curRowLabel = sclCell.rLabel.toUpperCase();
          } else {
            curRowLabel = "unk";
          }
          curRow = $('<div class="paleoChartRow">' +
            '<div class="paleoChartRowHeader">'+curRowLabel+'</div>' +
            '</div>' );
          this.pChart.append(curRow);
        }
        //before creating sclCell div prefill blanks as needed
        if (sclCell.cNum > 1+curCellNum) { // prefill
          for(curCellNum; curCellNum<sclCell.cNum-1; curCellNum++) {
            curRow.append($('<div class="paleoChartCell">'));
          }
        }
        //add syllable Group cell
        curRow.append(this.getSclCellDiv(sclCell,gSort));
        curCellNum = sclCell.cNum;
      }
    }
    if (curRow && curCellNum < colNum) {
      for(curCellNum; curCellNum<colNum; curCellNum++) {
        curRow.append($('<div class="paleoChartCell">'));
      }
      curRow.append($('<div class="padCell">'));//scrollbar space
    }
    this.addEventHandlers();
  },


/**
* put your comment there...
*
* @param sclCell
* @param gSort
*/

  getSclCellDiv: function(sclCell,gSort) {
    var segment,syllable,i,segDiv = null,segImg = null, title,
    sclCellDiv = $('<div class="paleoChartCell srt'+gSort.substring(2)+'"/>'),
    sclStr, url, defaultUrl,
    serviceBaseUrl = basepath+'/services/getSegmentImage.php';
    if (!sclCell && gSort) {
      sclCell = this.sclCellsBySortCode[gSort];
    }
    if (sclCell) {
      sclStr = (sclCell.rLabel == "vowel"?"":sclCell.rLabel)+sclCell.cLabel;
      defaultUrl = imgbasepath+'/karoshti_default/'+sclStr+'.jpg'
      this.calcSclCellGroups(sclCell);
      //find the segment for each syllable
      for(i in sclCell.syllables) {
        syllable = sclCell.syllables[i];
        if (syllable) {
          if(syllable.segID) {
            segment = this.dataMgr.getEntity('seg',syllable.segID);
            if (segment && segment.urls && segment.urls.length) {//tod add code to handle separate urls
              if (!url || syllable.def) {
                url = segment.urls[0];
              }
            }
          }
          if (!segDiv) {
            title = syllable.value.replace('ʔ','')+ " (Line: "+syllable.line+" Letter:"+syllable.pos+")";
            segDiv = $('<div class="segDiv scl'+syllable.id+'" title="'+title+'"/>');
            segImg = $('<img class="cellSegImg" alt="'+sclStr+'"/>');
            segDiv.append(segImg);
            sclCellDiv.append(segDiv);
          } else {
            title += "\n"+syllable.value.replace('ʔ','')+ " (Line: "+syllable.line+" Letter:"+syllable.pos+")";
            segDiv.attr('title',title);
          }
          if (syllable.segID) {
            segDiv.addClass('seg'+syllable.segID);
          }
        }
      }
      if (segImg) {
        segImg.attr('src',(url?url:defaultUrl));
      }
      return sclCellDiv;
    } else {
      return $('<div/>');
    }
  },


/**
*
*/

  addEventHandlers: function() {
    var paleoVE = this, entities = this.dataMgr.entities;


/**
* put your comment there...
*
* @param object e System event object
*/

    function sortChangedHandler(e) {
      paleoVE.changeSortMode($(this));
    }

    this.btnSortMode.unbind('click').bind('click',sortChangedHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
* @param entID
*/

    function updateSelectionHandler(e,senderID, selectionIDs, entID) {
      if (senderID == paleoVE.id) {
        return;
      }
      var i, id, prefix;
      DEBUG.log("event","selection changed recieved by "+paleoVE.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      $(".selected", paleoVE.contentDiv).removeClass("selected");
      if (selectionIDs && selectionIDs.length && selectionIDs[0].substr(0,3) == 'seg') {
        $.each(selectionIDs, function(i,val) {
          var elem = $('.'+val,paleoVE.contentDiv)
          if (!elem.hasClass("selected")) {
            elem.addClass("selected");
          }
        });
      } else if (entID && entID.length && entID.length > 3) {
        prefix = entID.substr(0,3);
        if (prefix == 'seg') {
          $('.'+entID,paleoVE.contentDiv).addClass("selected");
        }
      }
    };

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function segClickHandler(e) {
      var classes = $(this).attr('class'), entTags = [], entGID, segTag;
      if ( paleoVE.id != paleoVE.layoutMgr.focusPaneID) {
        paleoVE.layoutMgr.focusHandler(e,paleoVE.id);
      }
      if (classes.match(/selected/)) {//user is unselecting
        $(this).removeClass("selected");
      }
      if(!e.ctrlKey){//if not multiselect
        $(".selected", paleoVE.contentDiv).removeClass("selected");
      } else {//find segment ids for selected
        $(".selected",paleoVE.contentDiv).each(function(index,el){
          entTags = entTags.concat(el.className.match(/seg\d+/g));
        });
      }
      if (!classes.match(/selected/)) {//user is unselecting
        entTags = entTags.concat(classes.match(/seg\d+/g));
        $(this).addClass("selected");
      }
      entGID = classes.match(/scl\d+/g)[0];
      if (paleoVE.editMode == "tag" && paleoVE.curTagID && entGID) {
        paleoVE.tagEntity(entGID);
      } else if (paleoVE.editMode == "tag" && !paleoVE.curTagID) {
        alert("No entity was tagged. Please select current tag for modal tagging to work.");
      }
      $('.editContainer').trigger('updateselection',[paleoVE.id,entTags]);
      paleoVE.propMgr.showVE(null,entGID);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all word elements
    $(".segDiv,.tagSegDiv", this.editDiv).unbind('click').bind('click',segClickHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function tagHdrClickHandler(e) {
      var classes = $(this).attr('class'), entTags = [], entGID, exemplarSclTag;
      if ( paleoVE.id != paleoVE.layoutMgr.focusPaneID) {
        paleoVE.layoutMgr.focusHandler(e,paleoVE.id);
      }
      if (classes.match(/selected/)) {//user is unselecting
        $(".selected", paleoVE.contentDiv).removeClass("selected");
      } else {// selefind segment ids for selected
        $(".selected", paleoVE.contentDiv).removeClass("selected");
        //find all segDiv select them and add segIDs to entTags
        $(this).parent().find('.segDiv').each(function(index,el){
          if (!el.className.match(/selected/)) {
            $(el).addClass('selected');
          }
          if (!exemplarSclTag) {
            exemplarSclTag = el.className.match(/scl\d+/);
            if (exemplarSclTag && exemplarSclTag.length) {
              exemplarSclTag = exemplarSclTag[0];
            }
          }
          entTags = entTags.concat(el.className.match(/seg\d+/g));
        });
        $(this).addClass("selected");
      }
      $('.editContainer').trigger('updateselection',[paleoVE.id,entTags]);
      if (exemplarSclTag) {
        paleoVE.propMgr.showVE(null,exemplarSclTag);
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all word elements
    $(".taggingRowHeader", this.editDiv).unbind('click').bind('click',tagHdrClickHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function cellDblClickHandler(e) {
      var classes = $(this).attr('class'), gSort, sclCell, syllable;
      gSort = classes.match(/(?:srt)(\d+)/);
      if (gSort && gSort[1]) {
        gSort = '0.' + gSort[1]
        sclCell = paleoVE.sclCellsBySortCode[gSort];
        if (sclCell) {
          paleoVE.displaySclCell(sclCell,gSort);
        }
      }//trigger selection change
      $('.editContainer').trigger('updateselection',[paleoVE.id,[],null]);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all word elements
    $(".paleoChartCell", this.editDiv).unbind('dblclick').bind('dblclick',cellDblClickHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function paleoVEClickHandler(e) {
      paleoVE.contentDiv.focus();
      paleoVE.layoutMgr.curLayout.trigger('focusin',paleoVE.id);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    this.contentDiv.unbind('click').bind('click',paleoVEClickHandler);



/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function titleDblClickHandler(e) {
      var classes = $(this).attr('class'),entTag;
      entTag = classes.match(/cat\d+/);
      if (entTag) {
        entTag = entTag[0];
      } else {
        entTag = classes.match(/edn\d+/);
        if (entTag) {
          entTag = entTag[0];
        }
      }
      if (entTag) {
        paleoVE.propMgr.showVE(null,entTag);
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    $(".paleographicReportHeader", this.editDiv).unbind('dblclick').bind('dblclick',titleDblClickHandler);
  },

/**
* tag the entity identiied with the surrent selected tag term.
*
* @param entGID string that represents the Global ID of the entity
*/

  tagEntity: function(entGID) {
    var paleoVE = this, savedata = {}, curLabel = this.curTagLabel,
    syllable = this.dataMgr.getEntityFromGID(entGID);
    //setup data
    //check if syllable has existing tag in cur category
    if (syllable) {
      if (this.curTagLabel.match(/^BT/) && syllable.bt) {
        if (this.curTagLabel != syllable.bt.label) {
          savedata['tagAddToGIDs'] = [paleoVE.curTagID];
        }
        savedata['tagRemoveFromGIDs'] = [syllable.bt.value.replace(":","")];
      } else if (this.curTagLabel.match(/^FMT/) && syllable.fmt) {
        if (this.curTagLabel != syllable.fmt.label) {
          savedata['tagAddToGIDs'] = [paleoVE.curTagID];
        }
        savedata['tagRemoveFromGIDs'] = [syllable.fmt.value.replace(":","")];
      } else if (this.curTagLabel.match(/^VT/) && syllable.vt) {
        if (this.curTagLabel != syllable.vt.label) {
          savedata['tagAddToGIDs'] = [paleoVE.curTagID];
        }
        savedata['tagRemoveFromGIDs'] = [syllable.vt.value.replace(":","")];
      } else if (this.curTagLabel.match(/^default/)) {
        if (!syllable.def) {
          savedata['tagAddToGIDs'] = [paleoVE.curTagID];
        } else {
          savedata['tagRemoveFromGIDs'] = [syllable.def.value.replace(":","")];
          delete syllable.def;
        }
      }
    }
    savedata['entGID'] = entGID;
    savedata['tagAddToGIDs'] = [paleoVE.curTagID];
    $.ajax({
      type:"POST",
      dataType: 'json',
      url: basepath+'/services/saveTags.php?db='+dbName,
      data: savedata,
      asynch: true,
      success: function (data, status, xhr) {
        if (typeof data == 'object' && data.success && data.entities) {
          //update data
          paleoVE.dataMgr.updateLocalCache(data);
          paleoVE.propMgr.showVE();
          //            paleoVE.refreshTagMarkers();
          if (paleoVE.curTagSclCell) {
            paleoVE.displaySclCell(paleoVE.curTagSclCell);
          }
        }
        if (data.errors) {
          alert("An error occurred while trying to retrieve a record. Error: " + data.errors.join());
        }
      },
      error: function (xhr,status,error) {
        // add record failed.
        errStr = "Error while trying to save tag for segment. Error: " + error;
        DEBUG.log("err",errStr);
        alert(errStr);
      }
    });// end ajax
  },
}

/**
* calculate the information for constructing the rows and columns for a given syllable
* for the paleography table
*
* @param syllable entity to calculate information for
*
* @returns {Array}
*/

function getRowColumnInfo(syllable) {
  var columnLabel = '', columnOffset = -1, rowLabel = '',
      sclSort, sort2, lkIndex, vSort, cSort, gSort = "0.", cOffsetMap;
  if (syllable && syllable.sort &&
      syllable.sort.length &&
      (syllable.sort >= 0 && syllable.sort < 0.8 ||  
      syllable.sort >= 0.92)  && // skip TCMs
      !syllable.value.match(/\./) &&
      !syllable.value.match(/_/)) {
    //check for non paleographic cases
    // space 885, _ 559, . 189, ʔ 195,
    sclSort = syllable.sort;
    sort2 = syllable.sort2.substring(2);
      vSort = "";
      cSort = (sclSort.indexOf('0.') == 0 ? sclSort.substring(2): sclSort);
      columnLabel = " ";
      columnOffset = 1;
    }
    while(cSort && cSort.length) {
      lkIndex = cSort.substring(0,2);
      // punctuation cases require sort2 code
      if (lkIndex >= 70 || lkIndex == "09") {//don't group numbers, symbols or *
        lkIndex += sort2[0];
      } else if ( lkIndex == "18" && sort2[0] == "7") {//separate I with macron
        lkIndex += "187";
      } else {//group in primary sort
        lkIndex += "0";
      }
      if (sortCodeToCharLookup[lkIndex]) {
      rowLabel += sortCodeToCharLookup[lkIndex][0];
      gSort += lkIndex;
      cSort = cSort.substring(2);
      sort2 = sort2.substring(2);
    }
    gSort += vSort;
    return [gSort, columnLabel, columnOffset, rowLabel];
  }
  return false;
}

// sort code mappings to grapheme multibyte sequences
sortCodeToCharLookup = {
  "000": [ "·" ],
    "100": [
        "A"
    ],
    "105": [
        "a"
    ],
    "106": [
        "ạ",
        "ạ"
    ],
    "107": [
        "a᷃"
    ],
    "108": [
        "â",
        "â"
    ],
    "109": [
        "á"
    ],
    "110": [
        "B"
    ],
    "115": [
        "b"
    ],
    "116": [
        "ḅ",
        "ḅ"
    ],
    "120": [
        "C"
    ],
    "125": [
        "c"
    ],
    "126": [
        "c̣"
    ],
    "127": [
        "ĉ"
    ],
    "130": [
        "D"
    ],
    "135": [
        "d"
    ],
    "136": [
        "ḍ",
        "ḍ"
    ],
    "140": [
        "E"
    ],
    "145": [
        "e"
    ],
    "146": [
        "ẹ",
        "ẹ"
    ],
    "147": [
        "e᷃"
    ],
    "148": [
        "ê",
        "ê"
    ],
    "149": [
        "é"
    ],
    "150": [
        "F"
    ],
    "155": [
        "f"
    ],
    "156": [
        "f̣"
    ],
    "160": [
        "G"
    ],
    "165": [
        "g"
    ],
    "166": [
        "g̣"
    ],
    "170": [
        "H"
    ],
    "175": [
        "h"
    ],
    "176": [
        "ḥ",
        "ḥ"
    ],
    "178": [
        "ĥ"
    ],
    "180": [
        "I"
    ],
    "185": [
        "i"
    ],
    "186": [
        "ị",
        "ị"
    ],
    "187": [
      "Ī",
      "i͞",
        "ī"
    ],
    "188": [
        "î"
    ],
    "189": [
        "í",
        "ì"
    ],
    "190": [
        "J"
    ],
    "195": [
        "j",
        "Λ"
    ],
    "196": [
        "j̣",
        "j̣"
    ],
    "198": [
        "ĵ"
    ],
    "200": [
        "K",
        "Κ"
    ],
    "205": [
        "k"
    ],
    "206": [
        "ḳ",
        "ḳ"
    ],
    "210": [
        "L"
    ],
    "215": [
        "l"
    ],
    "216": [
        "ḷ",
        "ḷ"
    ],
    "220": [
        "M",
        "Μ"
    ],
    "225": [
        "m",
        "Ξ"
    ],
    "226": [
        "ṃ",
        "ṃ"
    ],
    "227": [
        "m ̄"
    ],
    "228": [
        "m̂"
    ],
    "230": [
        "N",
        "Ν"
    ],
    "235": [
        "n"
    ],
    "236": [
        "ṇ",
        "ṇ"
    ],
    "238": [
        "n̂"
    ],
    "240": [
        "O",
        "Ο"
    ],
    "245": [
        "o",
        "Π"
    ],
    "246": [
        "ọ",
        "ọ"
    ],
    "248": [
        "ô",
        "ô"
    ],
    "249": [
        "ó"
    ],
    "250": [
        "P",
        "Ρ"
    ],
    "255": [
        "p"
    ],
    "256": [
        "p̣"
    ],
    "260": [
        "Q"
    ],
    "265": [
        "q"
    ],
    "266": [
        "q̣",
        "q̣"
    ],
    "270": [
        "R"
    ],
    "275": [
        "r",
        "Σ"
    ],
    "276": [
        "ṛ",
        "ṛ"
    ],
    "280": [
        "S"
    ],
    "285": [
        "s",
        "Τ"
    ],
    "286": [
        "ṣ",
        "ṣ"
    ],
    "288": [
        "ŝ",
        "ŝ"
    ],
    "290": [
        "T"
    ],
    "295": [
        "t"
    ],
    "296": [
        "ṭ",
        "ṭ"
    ],
    "298": [
        "t̂"
    ],
    "300": [
        "U"
    ],
    "305": [
        "u",
        "Φ"
    ],
    "306": [
        "ụ",
        "ụ",
        "ⅎ",
        "Ⅎ"
    ],
    "307": [
        "ↄ",
        "Ↄ"
    ],
    "308": [
        "û",
        "ⱶ",
        "Ⱶ"
    ],
    "309": [
        "ú",
        "û"
    ],
    "310": [
        "V"
    ],
    "315": [
        "v"
    ],
    "316": [
        "ṿ",
        "ṿ"
    ],
    "318": [
        "v̂"
    ],
    "320": [
        "W"
    ],
    "325": [
        "w",
        "Ψ"
    ],
    "326": [
        "ẉ",
        "ẉ"
    ],
    "329": [
        "w"
    ],
    "330": [
        "X",
        "Χ"
    ],
    "335": [
        "x",
        "Ω"
    ],
    "336": [
        "x̣"
    ],
    "340": [
        "Y",
        "Υ"
    ],
    "345": [
        "y"
    ],
    "346": [
        "ỵ",
        "ỵ"
    ],
    "348": [
        "ŷ"
    ],
    "350": [
        "Z"
    ],
    "355": [
        "z"
    ],
    "356": [
        "ẓ",
        "ẓ"
    ],
    "365": [
        "א",
        "ב",
        "ג",
        "ד",
        "ה",
        "ו",
        "ז",
        "ח",
        "ט",
        "י",
        "ך",
        "כ",
        "ל",
        "ם",
        "מ",
        "ן",
        "נ",
        "ס",
        "ע",
        "ף",
        "פ",
        "ץ",
        "צ",
        "ק",
        "ר",
        "ש",
        "ת"
    ],
    "400": [
        "Ἀ",
        "Α"
    ],
    "405": [
        "ἀ",
        "α"
    ],
    "410": [
        "Β"
    ],
    "415": [
        "β"
    ],
    "420": [
        "Γ"
    ],
    "425": [
        "γ"
    ],
    "430": [
        "Δ"
    ],
    "435": [
        "δ"
    ],
    "440": [
        "Ε"
    ],
    "445": [
        "ε",
        "ἔ"
    ],
    "450": [
        "Ζ"
    ],
    "455": [
        "ζ"
    ],
    "460": [
        "Η"
    ],
    "465": [
        "η"
    ],
    "470": [
        "Θ"
    ],
    "475": [
        "θ"
    ],
    "480": [
        "Ι"
    ],
    "485": [
        "ι",
        "ί"
    ],
    "495": [
        "κ"
    ],
    "505": [
        "λ"
    ],
    "515": [
        "μ"
    ],
    "525": [
        "ν"
    ],
    "535": [
        "ξ"
    ],
    "545": [
        "ο"
    ],
    "555": [
        "π"
    ],
    "565": [
        "ρ"
    ],
    "575": [
        "ς",
        "ϛ"
    ],
    "585": [
        "σ"
    ],
    "595": [
        "τ"
    ],
    "605": [
        "υ",
        "ῦ"
    ],
    "615": [
        "φ",
        "ό"
    ],
    "625": [
        "χ"
    ],
    "635": [
        "ψ"
    ],
    "645": [
        "ω"
    ],
    "700": [
        0
    ],
    "705": [
        "½"
    ],
    "710": [
        1
    ],
    "720": [
        2
    ],
    "730": [
        3
    ],
    "740": [
        4
    ],
    "750": [
        5
    ],
    "760": [
        6
    ],
    "770": [
        7
    ],
    "780": [
        8
    ],
    "790": [
        9
    ],
    "800": [
        ","
    ],
    "810": [
        "."
    ],
    "820": [
        ";"
    ],
    "830": [
        ":"
    ],
    "840": [
        "!"
    ],
    "850": [
        "?"
    ],
    "860": [
        "[",
        "{",
        "("
    ],
    "870": [
        "]",
        "}",
        ")"
    ],
    "880": [
        "«"
    ],
    "890": [
        "»"
    ],
    "900": [
        "+"
    ],
    "910": [
        "-"
    ],
    "920": [
        "ϴ"
    ],
    "921": [
        "☧",
        "⳩"
    ],
    "922": [
        "⸱",
        "·"
    ]
}



