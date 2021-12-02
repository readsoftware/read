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
* editors sequenceVE object
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
* @param sequenceVECfg is a JSON object with the following possible properties
*  "edition" an entity data element which defines the edition and all it's structures.
*
* @returns {SequenceVE}

*/

EDITORS.SequenceVE =  function(sequenceVECfg) {
  var that = this;
  //read configuration and set defaults
  this.config = sequenceVECfg;
  this.type = "SequenceVE";
  this.dataMgr = sequenceVECfg['dataMgr'] ? sequenceVECfg['dataMgr']:null;
  this.layoutMgr = sequenceVECfg['layoutMgr'] ? sequenceVECfg['layoutMgr']:null;
  this.eventMgr = sequenceVECfg['eventMgr'] ? sequenceVECfg['eventMgr']:null;
  this.edition = sequenceVECfg['edition'] ? sequenceVECfg['edition']:null;
  this.editDiv = sequenceVECfg['editDiv'] ? sequenceVECfg['editDiv']:null;
  this.id = sequenceVECfg['id'] ? sequenceVECfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/

EDITORS.SequenceVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    var seqVE = this;
    this.curTagID = null;
    this.anchorTagID = null;
    this.parentTagID = null;
    this.autoLink = false;
    this.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
    this.$splitterDiv = $('<div id="'+this.id+'splitter"/>');
    this.$contentDiv = $('<div id="'+this.id+'textContent" class = "seqveContentDiv"  spellcheck="false" />');
    this.$structTree = $('<div class="structTree"/>');
    this.$contentDiv.append(this.$structTree);
    this.$propertyMgrDiv = $('<div id="'+this.id+'propManager" class="propertyManager" />');
    this.$splitterDiv.append(this.$contentDiv);
    this.$splitterDiv.append(this.$propertyMgrDiv);
    $(this.editDiv).append(this.$splitterDiv);
    this.$splitterDiv.jqxSplitter({ width: '100%',
                                      height: '100%',
                                      orientation: 'vertical',
                                      splitBarSize: 1,
                                      showSplitBar:false,
                                      panels: [{ size: '60%', min: '250', collapsible: false},
                                               { size: '40%', min: '150', collapsed: true, collapsible: true}] });
    this.propMgr = new MANAGERS.PropertyManager({id: this.id,
                                                 propertyMgrDiv: this.$propertyMgrDiv,
                                                 editor: seqVE,
                                                 ednID: this.edition.id,
                                                 hideSubType: true,
                                                 hideComponents: true,
                                                 propVEType: "entPropVE",
                                                 dataMgr: this.dataMgr,
                                                 splitterDiv: this.$splitterDiv });
    this.displayProperties = this.propMgr.displayProperties;
    this.calcSeqList();
    this.setDefaultSeqType();
    this.$splitterDiv.unbind('focusin').bind('focusin',this.layoutMgr.focusHandler);
    this.createStaticToolbar();
    this.setTagView();
    this.cmpTypeID = this.dataMgr.getIDFromTermParentTerm("compound","systementity");// warning!!! term dependency
    this.tokTypeID = this.dataMgr.getIDFromTermParentTerm("token","systementity");// warning!!! term dependency
    this.sclTypeID = this.dataMgr.getIDFromTermParentTerm("syllablecluster","systementity");// warning!!! term dependency
    this.showStructureTree();
    this.componentLinkMode = false;
    this.addEventHandlers();
  },

/**
* put your comment there...
*
*/

  setFocus: function () {
    this.$structTree.focus();
  },

/**
* put your comment there...
*
* @returns {Array}
*/

  getUsedTagsList: function () {
    var usedTagIDs = Object.keys(this.dataMgr.tagIDToAnoID),//get the cur tags list from dataMgr
        reservedColors = {Blue:1,Green:1,Red:1},
        colorChoices = ['cyan','yellowgreen','coral','yellow','cadetblue','aqua',
                        'gold','khaki','lavender','lightcyan','lightgrey','lightsteelblue','palegreen',
                        'plum','pink','skyblue','sandbrown','silver','springgreen','violet','tan'],
        items = [], i, item, tagID, anoID, label, color, cssSelectors,scope = "#"+this.$contentDiv.attr('id');

    //if empty nothing tagged create single item "off" using off for all item values
    if (usedTagIDs.length == 0) {
      items.push({id:'off',label:'Off',value:'off'});
    } else {
      //foreach tag create css rule using editors contentDiv for scope and entTags for class selection
      for (i in usedTagIDs) {
        tagID = usedTagIDs[i];
        label = this.dataMgr.entTagToLabel[tagID];
        anoID = this.dataMgr.tagIDToAnoID[tagID];
        //set background color from a list reserving primary color tag color to match tag name.
        color = (reservedColors[label]?label:colorChoices[i]);
        if (anoID && this.dataMgr.entities.ano && this.dataMgr.entities.ano[anoID] &&
            this.dataMgr.entities.ano[anoID].linkedToIDs && this.dataMgr.entities.ano[anoID].linkedToIDs.length) {
          //create css rule using editors contentDiv for scope and entTags for class selection
          cssSelectors = scope+" ."+this.dataMgr.entities.ano[anoID].linkedToIDs.join(","+scope+" .").replace(/\:/g,"");
          //create item  with tagID as id, lookup Label as label and css rule string as value.
          item = {id:tagID,
                  label:label,
                  value:cssSelectors+'{background-color:'+color+'!important;}'
                 };
          items.push(item);
        }
      }
    }
    if (items.length == 0) {
      items.push({id:'off',label:'Off',value:'off'});
    }
    return items;
  },


/**
* put your comment there...
*
*/

  setTagView: function () {
    var endVE = this, tagID2Path = this.dataMgr.entTagToPath,
        anchorPath, parentIndex, parentElem, parentItem, $parent, tagItemTree = this.dataMgr.tags,
        curTag, curTagItem, curTagParentItem, $anchor, anchorIndex, anchorItem, anchorElem;
    // if top level show all
    if (!this.anchorTagID || !tagID2Path[this.anchorTagID].match(/;/)) {
      $('li.jqx-tree-item-li',this.tagTree).show();
    } else { //else hide all and show parent, anchor, anchor siblings and anchor children
      $('li.jqx-tree-item-li',this.tagTree).hide();
    }
    if (this.anchorTagID) {//handle anchor - show it, scroll to view, and expand it
      //check parentage
      anchorPath = tagID2Path[this.anchorTagID].split(";");
      if (anchorPath.length) {
        anchorIndex = anchorPath.pop();
        if (anchorPath.length) {//show parentage
          parentIndex = anchorPath.shift();
          parentItem = tagItemTree[parentIndex];
          $parent = $('li#'+parentItem.id,this.tagTree);
          $parent.show();
          while (anchorPath.length) {
            parentIndex = anchorPath.shift();
            if (!parentItem.items || parentIndex >= parentItem.items.length) { //invalid parent info break out
              break;
            }
            parentItem = parentItem.items[parentIndex];
            $parent = $('li#'+parentItem.id,this.tagTree);
            $parent.show();
          }
          this.parentTagID = parentItem.id;
        } else {
          this.parentTagID = null;
        }
      }
      $anchor = $('li#'+this.anchorTagID,this.tagTree);
      if ($anchor.length) {
        anchorElem = $anchor[0];
        $anchor.show();
        anchorItem = this.tagTree.jqxTree("getItem",anchorElem);
        if (anchorItem) {
          this.parentTagID = anchorItem.parentId;
          parentElem = anchorItem.parentElement;
          if (parentElem) {
            $parent = $(parentElem);
            $parent.find('li.jqx-tree-item-li').show();
          } else {//top level so show branch
            $anchor.find('li.jqx-tree-item-li').show();
          }
          this.tagTree.jqxTree("ensureVisible",anchorElem);
        }
      }
      this.tagTree.jqxTree('refresh')
      //check selection is curTagID and ensure this is visible.
      if (false || this.curTagID) {
        curTag = $('li#'+this.curTagID,this.tagTree);
        if (curTag.length) {
          curTagItem = this.tagTree.jqxTree("getItem",curTag[0]);
          if (curTagItem) {
            if (!curTagItem.selected) {
              this.tagTree.jqxTree("selectItem",curTag[0]); //todo consider if case of needs to be selected.
            }
            if (curTagItem.parentElement) {
              curTagParentItem = this.tagTree.jqxTree("getItem",curTagItem.parentElement);
              if (curTagParentItem.isExpanded) {
                this.tagTree.jqxTree("ensureVisible",curTag[0]);
              }
            }
          }
        }
      }
    } else {
      this.tagTree.jqxTree('refresh')
    }
  },


/**
* put your comment there...
*
* @param seqTypeID
*/

  setDefaultSeqType: function (seqTypeID) {
    if (!seqTypeID || !this.dataMgr.seqTypeTagToLabel['trm'+seqTypeID]) {
      this.defaultSeqTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel['paragraph-section'];// warning!!! term dependency
    } else {
      this.defaultSeqTypeID = seqTypeID;
    }
  },

/**
* put your comment there...
*
*/

  addSequence: function () {
    DEBUG.traceEntry("addSequence");
    var seqVE = this;
    if (this.entPropVE && this.defaultSeqTypeID && this.entPropVE.createNewSequence) {
      this.entPropVE.createNewSequence(this.defaultSeqTypeID,null,function (newSeqID) {
        // callback need to add item to tree and attach MenuHandle
                  var newItem = seqVE.getSubItems([newSeqID],0);
                  seqVE.$structTree.jqxTree('addTo',newItem[0]);
                  seqVE.attachMenuEventHandler(seqVE.$structTree);
      });
      this.showProperties(true);
    }
    DEBUG.traceExit("addSequence");
  },

/**
* put your comment there...
*
* @returns {Array}
*/

  getUsedSeqsList: function () {
    var usedSeqIDs = Object.keys(this.seqGIDsByType),//get the cur seqIDs
        colorChoices = ['cyan','yellowgreen','coral','yellow','cadetblue','aqua',
                        'gold','khaki','lavender','lightcyan','lightgrey','lightsteelblue','palegreen',
                        'plum','pink','skyblue','sandbrown','silver','springgreen','violet','tan'],
        items = [], i, item, tagID, label, color, cssSelectors,scope = "#"+this.$contentDiv.attr('id');

    //if empty nothing tagged create single item "off" using off for all item values
    if (!usedSeqIDs || usedSeqIDs.length == 0) {
      items.push({id:'off',label:'Off',value:'off'});
    } else {
      //foreach seg create item
      for (i in usedSeqIDs) {
        tagID = usedSeqIDs[i];
        label = this.dataMgr.getTermFromID(tagID.substring(3));
        //set background color from a list reserving primary color tag color to match tag name.
        color = colorChoices[i];
        item = {id:tagID,
                label:label,
                value:color
               };
        items.push(item);
      }
    }
    return items;
  },


/**
* put your comment there...
*
*/

  calcSeqList: function () {
    var seqVE = this, seqIDs = this.edition.seqIDs, i, k, l, entTag, childSeqTag, seqID, seqGID, ednSeqTag,
        seqType, prefix, typeID, sequence, entity, seqIDsByType = {}, parentTagBySeqTag = {},
        entTagsBySeqTag = {};

    function addChildSeqences(seqTag, entGIDs) {
      var j, k, l, gid, tag, tokTag, entTag, prefix, sequence, entity, childEntTag, childSeqTag;
      if (entGIDs.length && !entTagsBySeqTag[seqTag]){
        entTagsBySeqTag[seqTag] = [];
      }
      for (j=0; j<entGIDs.length; j++) {
        gid = entGIDs[j];
        tag = gid.replace(":","");
        prefix = gid.substring(0,3);
        if (prefix == 'seq') {
          sequence = seqVE.dataMgr.getEntityFromGID(gid);
          if (!sequence || !sequence.entityIDs || sequence.entityIDs.length == 0){//skip empty sequences
            continue;
          }
          typeID = 'trm' + sequence.typeID;
          if (!seqIDsByType[typeID]) {
            seqIDsByType[typeID] = [];
          }
          if (seqIDsByType[typeID].indexOf(gid)== -1) { // only store it once
            seqIDsByType[typeID].push(gid);
          }
          if (sequence.entityIDs && sequence.entityIDs.length) {
            addChildSeqences(tag, sequence.entityIDs);
            //add child sequence entity ids to parent lookup entry
            if (entTagsBySeqTag[tag] && entTagsBySeqTag[tag].length) {
              for (l in entTagsBySeqTag[tag]) {
                childEntTag = entTagsBySeqTag[tag][l];
                if ( entTagsBySeqTag[seqTag].indexOf(childEntTag)== -1) {
                  entTagsBySeqTag[seqTag].push(childEntTag);
                }
              }
            }
          }
        } else if (prefix == 'cmp'){
          entity = seqVE.dataMgr.getEntityFromGID(gid);
          if (entity && entity.tokenIDs && entity.tokenIDs.length){
            for (k in entity.tokenIDs) {
              tokTag = 'tok'+ entity.tokenIDs[k];
              if (entTagsBySeqTag[seqTag].indexOf(tokTag)== -1) {
                entTagsBySeqTag[seqTag].push(tokTag);
              }
            }
          }
        } else {
          if (entTagsBySeqTag[seqTag].indexOf(tag)== -1) {
            entTagsBySeqTag[seqTag].push(tag);
          }
        }
      }
    }; // end addChildSeqences

//start running through the sequences
    for (i=0; i<seqIDs.length; i++) {
      seqID = seqIDs[i];
      ednSeqTag = 'seq' + seqID;
      seqGID = 'seq:' + seqID;
      sequence = seqVE.dataMgr.getEntity('seq',seqID);
      if (!sequence){//skip sequence not loaded
        continue;
      }
      seqType = this.dataMgr.getTermFromID(sequence.typeID).toLowerCase();
      if (seqType == 'text' || seqType == 'textphysical'){//skip system managed sequences
        continue;
      }
      if (!sequence.entityIDs || sequence.entityIDs.length == 0){//skip empty sequences
        continue;
      }
      typeID = 'trm' + sequence.typeID;
      if (!seqIDsByType[typeID]) {
        seqIDsByType[typeID] = [];
      }
      if (seqIDsByType[typeID].indexOf(seqGID)==-1) { // only store it once
        seqIDsByType[typeID].push(seqGID);
      }
      if (sequence.entityIDs && sequence.entityIDs.length) {
        addChildSeqences(ednSeqTag, sequence.entityIDs);// only getting children of edition OK??
        //compose list the entTags from list of child sequence list
        if (!entTagsBySeqTag[ednSeqTag]){
          entTagsBySeqTag[ednSeqTag] = [];
        }
        for (k in sequence.entityIDs) {
          ednSeqChildTag = sequence.entityIDs[k].replace(":","");
          if (entTagsBySeqTag[ednSeqChildTag] && entTagsBySeqTag[ednSeqChildTag].length) {
            for (l in entTagsBySeqTag[ednSeqChildTag]) {
              entTag = entTagsBySeqTag[ednSeqChildTag][l];
              if ( entTagsBySeqTag[ednSeqTag].indexOf(entTag)== -1) {
                entTagsBySeqTag[ednSeqTag].push(entTag);
              }
            }
          }
        }
      }
    }
    this.seqGIDsByType = seqIDsByType;
    this.entTagsBySeqTag = entTagsBySeqTag;
  },

/**
* put your comment there...
*
*/

  getEdnID: function () {
    return this.edition.id;
  },

   createStaticToolbar: function () {
    var seqVE = this;
    var btnShowPropsName = this.id+'showprops',
        btnAddSequenceName = this.id+'addsequence',
        ddbtnCurTagName = this.id+'curtagbutton',
        treeCurTagName = this.id+'curtagtree';
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    this.addSequenceBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnAddSequenceName +
                              '" title="Create a new sequence entity">+ Sequence</button>'+
                            '<div class="toolbuttonlabel">Add sequence</div>'+
                           '</div>');
    this.addSequenceBtn = $('#'+btnAddSequenceName,this.addSequenceBtnDiv);
    this.addSequenceBtn.unbind('click').bind('click',function(e) {
      if (seqVE.addSequence ) {
        seqVE.addSequence();
      }
    });

    this.curTagBtnDiv = $('<div class="toolbuttondiv">' +
                           '<div id="'+ ddbtnCurTagName+'"><div id="'+ treeCurTagName+'"></div></div>'+
                           '<div class="toolbuttonlabel">Current Tag</div>'+
                           '</div>');
    this.tagTree = $('#'+treeCurTagName,this.curTagBtnDiv);
    this.tagDdBtn = $('#'+ddbtnCurTagName,this.curTagBtnDiv);
    this.tagTree.jqxTree({
           source: seqVE.dataMgr.tags,
    //       hasThreeStates: false, checkboxes: true,
           width: '250px',
           height: '300px',
           theme:'energyblue'
    });
    this.tagTree.on('expand', function (event) {
      var item =  seqVE.tagTree.jqxTree('getItem', event.args.element),
          isOldAnchorParentOfItem = false, item, itemElement, parentItem;
      if (seqVE.anchorTagID != item.id && seqVE.parentTagID != item.id ) {
        if (seqVE.anchorTagID) {
          if (item.id) {
            itemElement = $('li#'+item.id,seqVE.tagTree);
            if (itemElement.length) {
              item = seqVE.tagTree.jqxTree("getItem",itemElement[0]);
              if (item && item.parentElement) {
                parentItem = seqVE.tagTree.jqxTree("getItem",item.parentElement);
                if (seqVE.anchorTagID == parentItem.id) {
                  isOldAnchorParentOfItem = true;
                }
              }
            }
          }
          anchor = $('li#'+seqVE.anchorTagID,seqVE.tagTree);
          anchorItem = seqVE.tagTree.jqxTree("getItem",anchor[0]);
          if (anchorItem && anchorItem.isExpanded && !isOldAnchorParentOfItem) {
            seqVE.tagTree.jqxTree("collapseItem",anchor[0]);
          }
        }
        seqVE.anchorTagID = item.id;
        seqVE.setTagView();
      }
    });
    this.tagTree.on('collapse', function (event) {
      // if collapse is anchor then make parent anchor unless node is top level
      var item =  seqVE.tagTree.jqxTree('getItem', event.args.element),
          anchor,anchorItem;
      if (seqVE.anchorTagID == item.id) {
        if (seqVE.parentTagID) {
          seqVE.anchorTagID = seqVE.parentTagID;
          //seqVE.parentTagID = null;
          seqVE.setTagView();
        }
      } else if (seqVE.parentTagID && seqVE.parentTagID == item.id ) {
        if (seqVE.anchorTagID) {
          anchor = $('li#'+seqVE.anchorTagID,seqVE.tagTree);
          anchorItem = seqVE.tagTree.jqxTree("getItem",anchor[0]);
          if (anchorItem && anchorItem.isExpanded) {
            seqVE.tagTree.jqxTree("collapseItem",anchor[0]);
          }
        }
        seqVE.anchorTagID = seqVE.parentTagID;
        seqVE.parentTagID = null;
        seqVE.setTagView();
      }
    });
    this.tagTree.on('select', function (event) {
        var args = event.args, dropDownContent = '',
            item =  seqVE.tagTree.jqxTree('getItem', args.element);
        if (item.value) {
          //save selected tag to edition VE
          seqVE.curTagID = item.value.replace(":","");
          //display current tag
          dropDownContent = '<div class="listDropdownButton">' + item.label + '</div>';
          seqVE.tagDdBtn.jqxDropDownButton('setContent', dropDownContent);
        }
    });
    this.tagDdBtn.jqxDropDownButton({width:95, height:25 });

    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
                              '" title="Show/Hide property panel">&#x25E8;</button>'+
                            '<div class="toolbuttonlabel">Properties</div>'+
                           '</div>');
    this.propertyBtn = $('#'+btnShowPropsName,this.propertyBtnDiv);
    this.propertyBtn.unbind('click').bind('click',function(e) {
                                           var paneID, editor;
                                           seqVE.showProperties(!$(this).hasClass("showUI"));
                                         });
    this.viewToolbar.append(this.propertyBtnDiv);
    this.viewToolbar.append(this.showTagBtnDiv);
    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    this.editToolbar.append(this.curTagBtnDiv);
    this.editToolbar.append(this.addSequenceBtnDiv);
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
  },


/**
* put your comment there...
*
* @param bShow
*/

  showProperties: function (bShow) {
    var seqVE = this;
    if (seqVE.propMgr &&
        typeof seqVE.propMgr.displayProperties == 'function'){
      seqVE.propMgr.displayProperties(bShow);
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
* @param string entGID Entity global id (prefix:id)
* @param cb
*/

  tagEntity: function (entGID,cb) {
    var seqVE = this, savedata = {};
    //setup data
    savedata={
      entGID: entGID,
      tagAddToGIDs: [seqVE.curTagID],
    };
    if (DEBUG.healthLogOn) {
      savedata['hlthLog'] = 1;
    }
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: basepath+'/services/saveTags.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.entities) {
            //update data
            seqVE.dataMgr.updateLocalCache(data);
            seqVE.propMgr.showVE();
          }
          if (data.editionHealth) {
            DEBUG.log("health","***Tag Entity***");
            DEBUG.log("health","Params: "+JSON.stringify(savedata));
            DEBUG.log("health",data.editionHealth);
          }
          if (cb && typeof cb == "function") {
            cb(seqVE.curTagID);
          }
          if (data.errors) {
            alert("An error occurred while trying to retrieve a record. Error: " + data.errors.join());
          }
      },
      error: function (xhr,status,error) {
          // add record failed.
          errStr = "Error while trying to splitLine. Error: " + error;
          DEBUG.log("err",errStr);
            alert(errStr);
      }
    });// end ajax
  },


/**
* put your comment there...
*
* @param bLinkMode
* @param typeID
*/

  setComponentLinkMode: function(bLinkMode, typeID) {
    var subTypeID = this.dataMgr.seqTypeTagToList['trm'+typeID] ? this.dataMgr.seqTypeTagToList['trm'+typeID][0]:null,
        element, subType = subTypeID ? this.dataMgr.getTermFromID(subTypeID):'';
    this.componentLinkMode = bLinkMode;
    if (typeID && this.dataMgr.seqTypeTagToLabel['trm'+typeID]) {
      element = $('#trm'+typeID,this.showSeqTree);
      if (element.length) {
        element = element.get(0);
        this.removeAllSeqMarkers();
        this.showSeqTree.jqxTree('checkItem', element, true);
      }
    }
    if (subTypeID && this.dataMgr.seqTypeTagToLabel['trm'+subTypeID]) {
      element = $('#trm'+subTypeID,this.showSeqTree);
      if (element.length) {
        element = element.get(0);
        this.showSeqTree.jqxTree('checkItem', element, true);
      }
    }
    if (subType){
      var button = $('button',this.objLevelBtnDiv);
      switch (subType) {
        case 'syllable':
          this.layoutMgr.setEditionObjectLevel('syllable');
          button.html("Syllable");
          break;
        case 'token':
          this.layoutMgr.setEditionObjectLevel('token');
          button.html("Word");
          break;
        case 'compound':
        default:
          this.layoutMgr.setEditionObjectLevel('compound');
          button.html("Compound");
          break;
      }
    }
  },


  /**
  * put your comment there...
  *
  */
  
    addEventHandlers: function() {
      var seqVE = this, entities = this.dataMgr.entities;
  
  
/**
* put your comment there...
*
* @param object e System event object
*/

    function annotationsLoadedHandler(e) {
      DEBUG.log("event","Annotations loaded recieved by sequenceVE in "+seqVE.id);
      seqVE.refreshTagMarkers();
    };

    $(this.editDiv).unbind('annotationsLoaded').bind('annotationsLoaded', annotationsLoadedHandler);


/**
* put your comment there...
*
* @param object e System event object
*/

    function objLevelChangedHandler(e) {//????????????
      var level;
      DEBUG.log("event","Object level changed recieved by sequenceVE in "+seqVE.id);
    };

    $(this.editDiv).unbind('objectLevelChanged').bind('objectLevelChanged', objLevelChangedHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
* @param string entTag Entity tag
*/

    function updateSelectionHandler(e,senderID, selectionIDs, entTag) {
      if (senderID == seqVE.id || !seqVE.linkTargetTag || !entTag) {
        return;
      }
      DEBUG.log("event","selection changed received by sequenceVE in "+seqVE.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      var parentEntTag = seqVE.linkTargetTag,
          linkEntGID = entTag.substring(0,3)+":"+entTag.substring(3),
          $linkElement = $('.linktarget',seqVE.$structTree);
      if (seqVE.entPropVE && seqVE.entPropVE.saveSequence &&
        linkEntGID.replace(":","") != parentEntTag) {
        seqVE.entPropVE.saveSequence(parentEntTag.substring(3), null, null, null, null, null, linkEntGID, null, function(newSeqID) {
          var elem = $linkElement[0],
              newItem = seqVE.getSubItems([linkEntGID],0);
          seqVE.$structTree.jqxTree('addTo',newItem[0],elem);
          seqVE.attachMenuEventHandler($(elem));
          seqVE.$structTree.jqxTree('expandItem', elem);
        },null);
      }
    };

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);

/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
* @param string entTag Entity tag
*/

    function structureLinkResponseHandler(e,senderID, senderEdnID, linkTargetTag, selectionIDs) {
      if (senderID == seqVE.id || senderEdnID != seqVE.edition.id || !seqVE.linkTargetTag || !selectionIDs) {
        return;
      }
      DEBUG.log("event","selection changed recieved by sequenceVE in "+seqVE.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      var parentEntTag = seqVE.linkTargetTag,
          $linkElement = $('.linktarget',seqVE.$structTree);
          $linkElement.removeClass('linktarget');
      if (parentEntTag && selectionIDs.length && seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
        seqVE.entPropVE.saveSequence(parentEntTag.substring(3), null, null, null, selectionIDs, null, null, null, 
            function(newSeqID){
              var elem = $linkElement[0], newItem, i,
                  newItems = seqVE.getSubItems(selectionIDs,0);
              if (newItems && newItems.length){
                for (i in newItems) {
                  newItem = newItems[i];
                  seqVE.$structTree.jqxTree('addTo',newItem,elem);
                  seqVE.attachMenuEventHandler($(elem));
                  seqVE.$structTree.jqxTree('expandItem', elem);
                }
              }
              $('.editContainer').trigger('structureChange',[seqVE.id,seqVE.edition.id, newSeqID]);
            },
            null);
      }
    };

    $(this.editDiv).unbind('structurelinkresponse').bind('structurelinkresponse', structureLinkResponseHandler);


    /**
    * handle 'structureChange' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string ednID Identifies the edition with the structural change
    * @param string entTag Identifies the entity that changed the structural change
    */

    function structureChangeHandler(e,senderID, ednID, entTag) {
      if (senderID == seqVE.id || seqVE.edition.id != ednID) {
        return;
      }
      var newSeqID = entTag.substring(3);
      DEBUG.log("event","struct change recieved by sequenceVE in "+seqVE.id+" from "+senderID);
      seqVE.showStructureTree();
      seqVE.entPropVE.showEntity("seq",newSeqID);
      seqVE.showProperties(true);
    };

    $(this.editDiv).unbind('structureChange').bind('structureChange', structureChangeHandler);


    /**
    * put your comment there...
    *
    * @param object e System event object
    *
    * @returns true|false
    */

    function seqVEClickHandler(e) {
      seqVE.$contentDiv.focus();
      seqVE.layoutMgr.curLayout.trigger('focusin',seqVE.id);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    this.$contentDiv.unbind('click').bind('click',seqVEClickHandler);

  },


  /*********************************** Edition display code ************************************************/

/**
* put your comment there...
*
* @param trmTag
*
* @returns {Array}
*/

  getTermListMenuItems: function(trmTag) {
    var trmTypeIDs, trmTypeID,i, items = [], trmID = trmTag.substring(3),
        itemLabel, itemValue, item, itemID, tagItem, linkItem, canLinkComponenets = false;
    if (!this.dataMgr || !this.dataMgr.getTermListFromID) {
      return null;
    }
    trmTypeIDs = this.dataMgr.getTermListFromID(trmID);
    if (trmTypeIDs && trmTypeIDs.length) {
      for (i=0; i<trmTypeIDs.length; i++) {
        trmTypeID = trmTypeIDs[i];
        itemLabel = this.dataMgr.getTermFromID(trmTypeID);
        itemID = "trm" + trmTypeID;
        if (!itemLabel) {
          continue;
        }
        if (trmTypeID == this.cmpTypeID || trmTypeID == this.tokTypeID || trmTypeID == this.sclTypeID) {
          canLinkComponenets = true;
          continue;
        }
        item = { label: itemLabel,
                    id: itemID,
                 value: trmTypeID };
        // Add menu group description
        if (items.length === 0) {
          items.push({
            label: "------Add------",
            id: "SeparatorAdd",
            value: 'SeparatorAdd',
            disabled: true
          });
        }
        items.push(item);
      }
    }
    if (items.length > 0) {
      items.push({
        label: "---------------",
        id: "Separator",
        value: 'Separator',
        disabled: true
      });
    }

    if (canLinkComponenets) {
      linkItem = { label: "Link",
                      id: "Link",
                   value: "Link" };
      items.push(linkItem);
    }
    tagItem = { label: "Tag",
                   id: "Tag",
                value: "Tag" };
    items.push(tagItem);
    return items;
  },

/**
.* getSubItems
 *   create nested array hierarchy of items of the edition's structural elements
 * sample item list create is shown here. This code give a minimal form of this list for version 1
 * var source = [
 *    { label: "Item 1",
 *       html: '<div class="myItem1">Item 1</div>',
 *         id: 'seq55',
 *      value: 'trm746',
 *   expanded: true,
 *   selected: false,
 *      items: [
 *              { label: "Item 1.1" },
 *              { label: "Item 1.2", selected: true }
 *             ],
 *       icon: "../images/trm1395.png",
 *   iconsize: "16px"
 *    },
 *    { label: "Item 2" }
 * ];
 *    value - sets the item's value.
 *    html - item's html. The html to be displayed in the item.
 *    id - sets the item's id.
 *    disabled - sets whether the item is enabled/disabled.
 *    checked - sets whether the item is checked/unchecked(when tree/list checkboxes are enabled).
 *    expanded - sets whether the item is expanded or collapsed.
 *    selected - sets whether the item is selected.
 *    items - sets an array of sub items.
 *    icon - sets the item's icon(url is expected).
 *    iconsize - sets the size of the item's icon.
 *
 *
*/

/**
* put your comment there...
*
* @param string array entGIDs Entity global identifiers
* @param cntSubLevels
* @param {boolean} isRoot Whether the current level is at the root level.
*
* @returns {Array}
*/

  getSubItems: function(entGIDs, cntSubLevels, isRoot) {
    var seqGID, i, j, trmTypeTag, entType, prefix,
        typeID, sequence, item, id, entity,
        subItems, itemHTML, items = [],
        gid, tag, entTag;
  var subEntityGIDs;
  var subEntityID;
  var subEntity;
  var k;
  var foundSystemSeq = false;
  var includeSubEntity;
  var sectionTypeTermID = parseInt(this.dataMgr.getIDFromTermParentTerm('Section', 'Chapter'));

    if (entGIDs.length){
      for (i=0; i<entGIDs.length; i++) {
        gid = entGIDs[i];
        tag = gid.replace(":","");
        prefix = gid.substring(0,3);
        id = tag.substring(3);
        itemHTML = "";
        entType = "";
        subItems = null;
        trmTypeTag = "";
        item = {};
        entity = this.dataMgr.getEntity(prefix,id);
        if (!entity){//skip entity not loaded
//               DEBUG.log("warn","Found entity not loaded"+tag);
          continue;
        }
        item['id'] = tag;
        switch (prefix) {
          case "seq":
            entType = this.dataMgr.getTermFromID(entity.typeID).toLowerCase();
            trmTypeTag = 'trm' + entity.typeID;
            break;
          case "cmp":
            entType = this.dataMgr.getTermFromID(this.cmpTypeID).toLowerCase();
            trmTypeTag = 'trm' + this.cmpTypeID;
            break;
          case "tok":
            entType = this.dataMgr.getTermFromID(this.tokTypeID).toLowerCase();
            trmTypeTag = 'trm' + this.tokTypeID;
            break;
          case "scl":
            entType = this.dataMgr.getTermFromID(this.sclTypeID).toLowerCase();
            trmTypeTag = 'trm' + this.sclTypeID;
            break;
          default:
            if (entity.typeID) {
              entType = this.dataMgr.getTermFromID(entity.typeID).toLowerCase();
              trmTypeTag = 'trm' + entity.typeID;
            }
        }
        item['value'] = trmTypeTag;
        itemHTML = '<div class="'+(entType?entType+' ':'')+tag+(trmTypeTag?' '+trmTypeTag:'')+'"'+
                       ' title="'+(entType?entType+' ':'')+tag+'" >'+
                       (entity.sup?entity.sup + (entity.label?" " +entity.label:""):
                                        (entity.label?entity.label:
                                          (entity.value?entity.value:
                                            (entType?entType+'('+tag+')':tag))))+'</div>';
        if (prefix == "seq" && entity.entityIDs && entity.entityIDs.length) {
          subEntityGIDs = [];
          if (isRoot) {
            // Check whether the top level entities should be included in the tree.
            // This will exclude any read only entities from the top level.
            for (k = 0; k < entity.entityIDs.length; k++) {
              includeSubEntity = false;
              subEntityID = this.parseEntityGID(entity.entityIDs[k]);
              if (subEntityID) {
                subEntity = this.dataMgr.getEntity(subEntityID.prefix, subEntityID.id);
                if (subEntity) {
                  if (subEntityID.prefix === 'seq') {
                    // Exclude the system generated section sequence. It will treat
                    // the first sub sequence in 'Section' type as the system generated
                    // sequence and exclude it from displaying.
                    if (i === 0 && !foundSystemSeq && parseInt(subEntity.typeID) === sectionTypeTermID) {
                      foundSystemSeq = true;
                    } else if (!subEntity.readonly) {
                      includeSubEntity = true;
                    }
                  } else if (!subEntity.readonly) {
                    includeSubEntity = true;
                  }
                }
              }
              if (includeSubEntity) {
                subEntityGIDs.push(entity.entityIDs[k]);
              }
            }
          } else {
            subEntityGIDs = entity.entityIDs;
          }
          if (cntSubLevels) {
            subItems = this.getSubItems(subEntityGIDs, cntSubLevels-1);
          } else {
            subItems = [{label:"Loading...",id:tag+"children"}];
          }
        }
        if (subItems && subItems.length) {
          item['items'] = subItems;
        }
        if (itemHTML && itemHTML.length) {
          item['html'] = itemHTML;
        }
        items.push(item);
      } // end for entGIDs
    }
    if (items && items.length) {
      return items;
    }
    return null;
  },

  /**
    * Parse an entity GID and return the parts of GID.
    *
    * @param {string} entGID
    * @return {Object} The prefix and id parts of the GID in an object. Returns
    *   false if the GID is not valid.
    */
  parseEntityGID: function (entGID) {
    var parts = entGID.split(':');
    if (parts.length === 2 && parts[0].length === 3) {
      return {
        prefix: parts[0],
        id: parseInt(parts[1])
      }
    }
    return false;
  },


/**
* put your comment there...
*
* @param object e System event object
* @param item
*/

  createContextMenu: function(e,item) {
    var seqVE = this,i,subItem,subType,
        scrollTop = $(window).scrollTop(),
        scrollLeft = $(window).scrollLeft(),
        $ctxMenu, menuItems, tag = item.id,
        entity = seqVE.dataMgr.getEntityFromGID(tag);
    $('.linktarget',seqVE.$structTree).removeClass('linktarget');
    seqVE.linkTargetTag = null;
    $ctxMenu = $('.structPopupMenu');
    if ($ctxMenu.length) {
      $ctxMenu.jqxMenu('destroy');
    }
    if (!entity) {
      return null;
    }
    if (!entity.readonly || !tag.match(/seq/)) {
      menuItems = this.getTermListMenuItems(item.value);
      if (!item.hasItems) {//item has no children so can be removed
        if (!menuItems) {
          menuItems = [];
        }
        menuItems.push({ label: "Remove",
                            id: "Remove",
                         value: "Remove" });
      } else {
        wordsOnly = true;
        for (i = 0; i< item.subtreeElement.children.length; i++) {
          subItem = seqVE.$structTree.jqxTree('getItem', item.subtreeElement.children[i]);
          if (subItem) {
            if (subItem.hasItems) {
              wordsOnly = false;
              break;
            } else {
              subType = subItem.id.substring(0,3);
              if (subType !== "scl" && subType !== "tok" && subType !== "cmp") {
                wordsOnly = false;
                break;
              }
            }
          }
        }
        if (wordsOnly){
          if (!menuItems) {
            menuItems = [];
          }
          menuItems.push({ label: "RemoveAll",
                              id: "RemoveAll",
                           value: "RemoveAll" });
        }
      }
    }
    if (menuItems && menuItems.length) {
      $ctxMenu = $('<div id="ctxMenu" class="structPopupMenu"/>');
      $ctxMenu.jqxMenu({ width: '120px',
                         source: menuItems,
                         autoOpenPopup: false,
                         mode: 'popup' });
      $ctxMenu.unbind('itemclick').bind('itemclick', function (e) {
          var itemMenuElement = e.args,
              menuLabel = $.trim($(itemMenuElement).text()),
              itemTypeTag, newStructTag,itemType, seqIDs,
              selectedItem = seqVE.$structTree.jqxTree('selectedItem'),
              parentItem, entID, entGID, entTag, parentEntTag,seqTypeID;
          switch ($.trim($(itemMenuElement).text())) {
            case "Remove":
              if (selectedItem != null) {
                entTag = selectedItem.id;
                entID = entTag.substring(3);
                entGID = entTag.substring(0,3)+":"+entID;
                parentEntTag = selectedItem.parentId;
                if (!parentEntTag) {//edition level sequence
                  seqIDs = seqVE.edition.seqIDs;
                  if (seqIDs && entTag.match(/seq/)) {//edition is parent
                    index = seqIDs.indexOf(entID);
                    //remove from array
                    seqIDs.splice(index,1);
                    //save seqIDs to edition
                    if (seqVE.entPropVE && seqVE.entPropVE.saveEditionSequenceIDs) {
                      seqVE.entPropVE.saveEditionSequenceIDs(seqVE.edition.id, seqIDs, function() {
                        if (entTag.match(/seq/) && seqVE.entPropVE && seqVE.entPropVE.deleteSeq) {
                          seqVE.entPropVE.deleteSeq(entTag, function(delSeqTag) {
                            seqVE.$structTree.jqxTree('removeItem', selectedItem.element);
                            //refresh property display
                            seqVE.entPropVE.showEntity();
                          });
                        } else {
                          seqVE.$structTree.jqxTree('removeItem', selectedItem.element);
                        }
                      });
                    }
                  }
                } else if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
                  seqVE.entPropVE.saveSequence(parentEntTag.substring(3), null, null, null, null,entGID, null, null, function(newSeqID) {
                    if (entTag.match(/seq/) && seqVE.entPropVE && seqVE.entPropVE.deleteSeq) {
                      seqVE.entPropVE.deleteSeq(entTag, function(delSeqTag) {
                        seqVE.$structTree.jqxTree('removeItem', selectedItem.element);
                      });
                    } else {
                      seqVE.$structTree.jqxTree('removeItem', selectedItem.element);
                    }
                  },null);
                }
              }
              break;
            case "RemoveAll":
              if (selectedItem != null) {
                entTag = selectedItem.id;
                if (entTag.match(/seq/)) {
                  entID = entTag.substring(3);
                  entGID = entTag.substring(0,3)+":"+entID;
                  if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
                    seqVE.entPropVE.saveSequence(entID, null, null, null, "",null, null, null,function(seqID) {
                        seqVE.refreshNode(entGID);
                        $('.editContainer').trigger('structureChange',[seqVE.id,seqVE.edition.id]);
                      },null);
                  }
                }
              }
              break;
            case "Tag":
              if (selectedItem != null && seqVE.curTagID) {
                 entTag = selectedItem.id;
                  //Apply current tag to selected item.
                  seqVE.tagEntity(entTag.substring(0,3)+":"+entTag.substring(3), function(tagTrmID) {
                    //TODO check if needed????
                  });
              } else if (!seqVE.curTagID) {
                alert("Please select a 'Current Tag' to tag elements");
              }
              break;
            case "Link":
              if (selectedItem != null) {
                 entTag = selectedItem.id;
                //set linktarget to selected Item id
                if (entTag.match(/seq/)) {//is sequence
                  //place in link mode so selection change event will add GID.
                  //callback needs to add item to parent, attach MenuEventHandler and expanded parent node
                  seqVE.linkTargetTag = selectedItem.id;
                  $(selectedItem.element).addClass('linktarget');
                  if (seqVE.edition) {
                    ednTag = "edn"+ seqVE.edition.id;
                    $('.editContainer').trigger('linkStructRequest',[seqVE.id,ednTag,seqVE.linkTargetTag]);
                  }
                }
              }
              break;
            default://add child structure
              selectedItem = seqVE.$structTree.jqxTree('selectedItem');
              parentEntTag = selectedItem.id;
              itemTypeTag = itemMenuElement.id;
              seqTypeID = itemTypeTag.substring(3);
              if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
                seqVE.entPropVE.saveSequence(parentEntTag.substring(3), null, null, null, null, null, null, seqTypeID, function(newSeqID) {
                  var elem = selectedItem.element,
                      newItem = seqVE.getSubItems([newSeqID],0);
                  seqVE.$structTree.jqxTree('addTo',newItem[0],elem);
                  seqVE.attachMenuEventHandler($(elem));
                  seqVE.$structTree.jqxTree('expandItem', elem);
                },null);
              }
          }
      });
      $ctxMenu.on('closed', function (e) {
        $(this).jqxMenu('destroy');
      });
      $ctxMenu.jqxMenu('open', parseInt(e.clientX) + 5 + scrollLeft, parseInt(e.clientY) + 5 + scrollTop);
    }
  },

/**
* put your comment there...
*
* @param $elem
*/

  attachMenuEventHandler: function($elem) {
    var seqVE = this;
    // open the context menu when the user presses the mouse right button.
    function mousedownHandler(e) {
        var target = $(e.target).parents('li:first')[0];
        seqVE.$structTree.curTop = seqVE.$structTree.scrollTop();
        if ((e.which == 3 || e.button == 2) && target != null) {
          seqVE.$structTree.jqxTree('selectItem', target);
          seqVE.createContextMenu(e, seqVE.$structTree.jqxTree('getItem', target));
          return false;
        } else if (target != null && e.args) {
          console.log("show structure class = " + $(e.args).attr('class'));
        }
    }
    $elem.unbind('mousedown').bind('mousedown', mousedownHandler);
    $elem.find("li").unbind('mousedown').bind('mousedown', mousedownHandler);

/*    $("#structTree li").unbind('mouseenter').bind('mouseenter', function (e) {
        var $linkedText;
        $linkedText = $('.linkedtextgroup:first',this);
        if ($linkedText && $linkedText.length) {
          $linkedText.addClass('selected');
        }
    });
    $("#structTree li").unbind('mouseleave').bind('mouseleave', function (e) {
        var $linkedText;
        $linkedText = $('.linkedtextgroup',this);
        if ($linkedText && $linkedText.length) {
          $linkedText.removeClass('selected');
        }
    });*/
  },

/**
* put your comment there...
*
* @param string entGID Entity global id (prefix:id)
*/

  refreshNode: function(entGID) {
    var entTag = entGID.replace(':',''),
        oldItem, newItem, prevItem, nextItem;
    //find element
    oldItem = this.$structTree.jqxTree('getItem',$('#'+entTag,this.$structTree)[0]);
    //get newitem structure using GID
    newItem = this.getSubItems([entTag],1);
    newItem = newItem[0];
    this.$structTree.jqxTree('addBefore',newItem,oldItem.element);
    this.$structTree.jqxTree('removeItem',oldItem.element);
    //attach MenuHandler to item.
    this.attachMenuEventHandler($('#'+entTag,this.$structTree));
  },

/**
* put your comment there...
*
* @param parentEntTag
* @param childEntTag
* @param pos
* @param refEntTag
*/

  reOrderChildGID: function(parentEntTag, childEntTag, pos, refEntTag) {
    var seqVE = this, ednID = this.edition.id,
        childEntID = childEntTag.substring(3),childEntGID = childEntTag.substring(0,3)+":"+childEntID,
        refEntID = refEntTag.substring(3),refEntGID = refEntTag.substring(0,3)+":"+refEntID,
        parent = seqVE.dataMgr.getEntityFromGID(parentEntTag), index,
        seqIDs, entityGIDs;
    if (parent) {
      seqIDs = parent.seqIDs;
      entityGIDs = parent.entityIDs;
      if (seqIDs && childEntTag.match(/seq/)) {//edition is parent
        index = seqIDs.indexOf(childEntID);
        //remove from array
        seqIDs.splice(index,1);
        index = seqIDs.indexOf(refEntID);
        if (pos == 'after') {
          index++;
        }
        //insert at new position
        seqIDs.splice(index,0,childEntID);
        //get entityIDs of parent and move GID to new position
        //save seqIDs to edition
        if (seqVE.entPropVE && seqVE.entPropVE.saveEditionSequenceIDs) {
          seqVE.entPropVE.saveEditionSequenceIDs(ednID, seqIDs, function() {
            //refresh property display
            seqVE.entPropVE.showEntity();
            //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
          });
        }
      } else if (parentEntTag.match(/seq/) && entityGIDs && entityGIDs.length) {
        index = entityGIDs.indexOf(childEntGID);
        //remove from array
        entityGIDs.splice(index,1);
        index = entityGIDs.indexOf(refEntGID);
        if (pos == 'after') {
          index++;
        }
        //insert at new position
        entityGIDs.splice(index,0,childEntGID);
        //save childEntGID to parentSequence
        if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
          seqVE.entPropVE.saveSequence(parentEntTag.substring(3), null, null, null, entityGIDs, null, null, null, function() {
            //refresh property display
            seqVE.entPropVE.showEntity();
            //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
          },null);
        }
      }
      //send notification that structure has changed.
    }
  },

/**
* put your comment there...
*
* @param fromParentEntTag
* @param toParentEntTag
* @param childEntTag
* @param pos
* @param refEntTag
*/

  moveChildGID: function(fromParentEntTag, toParentEntTag, childEntTag, pos, refEntTag) {
    var seqVE = this, ednID = this.edition.id,
        childEntID = childEntTag.substring(3),childEntGID = childEntTag.substring(0,3)+":"+childEntID,
        refEntID ,refEntGID, index,
        fromParent = seqVE.dataMgr.getEntityFromGID(fromParentEntTag),
        toParent = seqVE.dataMgr.getEntityFromGID(toParentEntTag),
        seqIDs, entityGIDs;

    if (fromParent && toParent) {
      if (toParentEntTag == childEntTag) {//cannot link to self so return
        DEBUG.log("warn","BEEP! Call to move entity to itself - recursion not allowed toParentEntTag ='"+toParentEntTag+"' and childEntTag = "+childEntTag);
        return;
      }
      seqIDs = fromParent.seqIDs;
      entityGIDs = fromParent.entityIDs;
      //remove entity GID from entityIDs of fromParent
      if (seqIDs && childEntTag.match(/seq/)) {//edition is parent
        index = seqIDs.indexOf(childEntID);
        //remove from array
        seqIDs.splice(index,1);
        //save seqIDs to edition
        if (seqVE.entPropVE && seqVE.entPropVE.saveEditionSequenceIDs) {
          seqVE.entPropVE.saveEditionSequenceIDs(ednID, seqIDs, function() {
            //refresh property display
            seqVE.entPropVE.showEntity();
            //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
          });
        }
      } else if (fromParentEntTag.match(/seq/) && entityGIDs && entityGIDs.length) {
        index = entityGIDs.indexOf(childEntGID);
        //remove from array
        entityGIDs.splice(index,1);
        //remove childEntGID from fromParent
        if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
          seqVE.entPropVE.saveSequence(fromParent.id, null, null, null, null, childEntGID, null, null, function() {
            //refresh property display
            seqVE.entPropVE.showEntity();
            //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
          },true);
        }
      } else {
        //error unable to remove gid from Parent
        return;
      }
      // process toParent
      // if pos = inside then dropitem is newParent
      // else dropItem.id is reference for position, dropItem.parentId is newParentTag
      // get parent entityIDs find index and insert itemGID
      seqIDs = toParent.seqIDs;
      entityGIDs = toParent.entityIDs;
      if (!refEntTag) {// add as child case
        if (seqIDs && childEntTag.match(/seq/) && seqIDs.indexOf(childEntID) == -1) {//edition is parent
          seqIDs.push(childEntID);
          if (seqVE.entPropVE && seqVE.entPropVE.saveEditionSequenceIDs) {
            seqVE.entPropVE.saveEditionSequenceIDs(ednID, seqIDs, function() {
              //refresh property display
              seqVE.entPropVE.showEntity();
              //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
            });
          }
        } else if (toParentEntTag.match(/seq/)) { // to Parent can be childless
          if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
            seqVE.entPropVE.saveSequence(toParent.id, null, null, null, null, null, childEntGID, null, function() {
              //refresh property display
              seqVE.entPropVE.showEntity();
              //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
            },true);
          }
        }
      } else {// reference postion insert
        refEntID = refEntTag.substring(3);
        refEntGID = refEntTag.substring(0,3)+":"+refEntID;
        if (seqIDs && childEntTag.match(/seq/)) {//edition is parent
          index = seqIDs.indexOf(refEntID);
          if (pos == 'after') {
            index++;
          }
          //insert at new position
          seqIDs.splice(index,0,childEntID);
          if (seqVE.entPropVE && seqVE.entPropVE.saveEditionSequenceIDs) {
            seqVE.entPropVE.saveEditionSequenceIDs(ednID, seqIDs, function() {
              //refresh property display
              seqVE.entPropVE.showEntity();
              //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
            });
          }
        } else if (toParentEntTag.match(/seq/) && entityGIDs && entityGIDs.length) {
          index = entityGIDs.indexOf(refEntGID);
          if (pos == 'after') {
            index++;
          }
          //insert at new position
          entityGIDs.splice(index,0,childEntGID);
          if (seqVE.entPropVE && seqVE.entPropVE.saveSequence) {
            seqVE.entPropVE.saveSequence(toParent.id, null, null, null, entityGIDs, null, null, null, function() {
              //refresh property display
              seqVE.entPropVE.showEntity();
              //treeStrucutre is updated during the drop so don't have to update unless this proves to get out of synch
            },true);
          }
        }
      }
    }
  },

/**
* put your comment there...
*
*/

  showStructureTree: function() {
    var seqVE = this, ednID = this.edition.id, seqGIDs, analySeqID,// txtRefSeqID,
        analysTrmID = this.dataMgr.getIDFromTermParentTerm("Analysis","SequenceType"),
//        textRefTrmID = this.dataMgr.getIDFromTermParentTerm("TextReferences","SequenceType"),
        textTrmID = this.dataMgr.getIDFromTermParentTerm("Text","SequenceType"),
        textDivTrmID = this.dataMgr.getIDFromTermParentTerm("TextDivision","Text"),
        textPhysTrmID = this.dataMgr.getIDFromTermParentTerm("TextPhysical","SequenceType"),
        linePhysTrmID = this.dataMgr.getIDFromTermParentTerm("LinePhysical","TextPhysical");
    seqGIDs = $(this.edition.seqIDs).map(function(index,str) {
      var sequence = seqVE.dataMgr.getEntity("seq",str);
      if (sequence && sequence.typeID != textTrmID &&
          sequence.typeID != textDivTrmID &&
          sequence.typeID != textPhysTrmID &&
          sequence.typeID != linePhysTrmID) {
          if (sequence.typeID == analysTrmID) {
            analySeqID = str;
          }
//          if (sequence.typeID == textRefTrmID) {
//            txtRefSeqID = str;
//          }
          return "seq:"+str;
      }
    });
    if (!analySeqID) {
      //no analysis so create one.
      if (this.entPropVE && this.entPropVE.createNewSequence) {
        this.entPropVE.createNewSequence(analysTrmID,null,function (newSeqID) {
          seqVE.showStructureTree();
          seqVE.entPropVE.showEntity("seq",newSeqID);
          seqVE.showProperties(true);
        });
      } else {
        alert("no analysis structure for this edition. Please add an Analysis Sequence.");
      }
      return false;
    }
    /*
    if (!txtRefSeqID) {
      //no text so create one.
      if (this.entPropVE && this.entPropVE.createNewSequence) {
        this.entPropVE.createNewSequence(textRefTrmID,null,function (newSeqID) {
          seqVE.showStructureTree();
          seqVE.entPropVE.showEntity("seq",newSeqID);
          seqVE.showProperties(true);
        });
      } else {
        alert("no analysis structure for this edition. Please add an Analysis Sequence.");
      }
      return false;
    }
    */
    seqVE.$structTree.jqxTree({
          source: seqVE.getSubItems(seqGIDs, 1, true),
          enableHover: false,
          allowDrag: true,
          allowDrop: true,
          theme:'energyblue',
          dragStart: function (item) {
            // Check whether the dragged item parent is editable. Prevent draging
            // if it's read only.
            var parentEntity;
            if (item.parentId) {
              parentEntity = seqVE.dataMgr.getEntityFromGID(item.parentId);
              if (parentEntity && !parentEntity.readonly) {
                return true;
              }
            }
            return false;
          },
          dragEnd: function (item, dropItem, args, dropPosition, tree) {
            var fromParentTag = item.parentId, toParentTag = dropItem.parentId,
                refEntTag = dropItem.id;
            if (!seqVE.isDroppedLocationWritable(dropItem)) {
              alert("The dropped location is not writable");
              return false;
            }
            if (!seqVE.isItemDroppable(item, dropItem, dropPosition)) {
              alert("This item can't be dropped at this location");
              return false;
            }
              if (!fromParentTag){
                fromParentTag = "edn"+ednID;
              }
              if (!toParentTag){
                if (!item.id.match(/seq/)) { //word being dropped into edition is not allowed
                  alert("dropping a word to top level is not allowed");
                  return false;
                }
                toParentTag = "edn"+ednID;
              }
              if (fromParentTag == toParentTag && dropPosition != "inside") {
                DEBUG.log("gen","moving " + item.id + " to position " + dropPosition + " " +dropItem.id +" of "+toParentTag);
                seqVE.reOrderChildGID(fromParentTag, item.id, dropPosition, dropItem.id);
              } else {
                if (dropPosition == 'inside') {
                  toParentTag = dropItem.id;
                  refEntTag = null;
                }
                DEBUG.log("gen","removing " + item.id + " from "+fromParentTag+" to position " + dropPosition + " " +dropItem.id +" of "+toParentTag);
                seqVE.moveChildGID(fromParentTag, toParentTag, item.id, dropPosition, refEntTag);
              }
              return true;
          }
    });

    seqVE.$structTree.css('visibility', 'visible');
    seqVE.$structTree.css('overflow-y', 'scroll');
    seqVE.$structTree.css('height', '100%');
    seqVE.$structTree.css('max-height', '100%');
    seqVE.$structTree.css('border', 'none');
    seqVE.attachMenuEventHandler(seqVE.$structTree);
    seqVE.$structTree.on('select',function (e) {
      var item = $(this).jqxTree('getItem', args.element),
          entTag;
      if (item) {
        entTag = item.id;
        seqVE.propMgr.showVE('entPropVE',entTag);
      }
      $('.linktarget',seqVE.$structTree).removeClass('linktarget');
      seqVE.linkTargetTag = null;
    });
    seqVE.$structTree.unbind("expand").bind("expand", function (e) {
      var elem = e.args.element, 
          item = seqVE.$structTree.jqxTree('getItem', elem),
          tag, entity, subItems, loadElement;
      if (item) {
        seqVE.$structTree.curTop = seqVE.$structTree.scrollTop();
        tag = item.id;
        entity = seqVE.dataMgr.getEntityFromGID(tag);
        loadElement = null;
        if (item && item.hasItems && item.nextItem.id == (item.id + "children")) {//dynamic load case
          loadElement = item.nextItem.element;
        }
        if (loadElement) {
          if (entity && entity.entityIDs && entity.entityIDs.length) {
            subItems = seqVE.getSubItems(entity.entityIDs,0);
            if (subItems) {
              seqVE.$structTree.jqxTree('addTo', subItems, elem);
              seqVE.$structTree.jqxTree('removeItem', loadElement);
              seqVE.attachMenuEventHandler($(elem));
            }
          }
        }
        setTimeout(function(){
            seqVE.$structTree.get(0).scrollTo(0,seqVE.$structTree.curTop);
          },1);
      }
    });
    seqVE.$structTree.unbind("collapse").bind("collapse", function (e) {
      setTimeout(function(){
          seqVE.$structTree.get(0).scrollTo(0,seqVE.$structTree.curTop);
        },1);
    });
    // disable the default browser's context menu.
    $(document).on('contextmenu', function (e) {
        if ($(e.target).parents('.jqx-tree').length > 0) {
            return false;
        }
        return true;
    });
    return true;
  },

  /**
   * Parse an entity tag and return the parts of the tag.
   *
   * @param {string} tag The entity tag (eg. seg123).
   * @return {Object} The entity prefix and id in an object. Returns false if
   *   the tag is invalid.
   */
  parseEntityTag: function (tag) {
    var expression = new RegExp('^([a-z]{3})(\\d+)$');
    var matches = expression.exec(tag);
    if (matches) {
      return {
        prefix: matches[1],
        id: matches[2]
      };
    }
    return false;
  },

  /**
   * Test whether the dropped location is writable.
   *
   * @param {Object} dropItem The target item where the item is dropped.
   * @param {string} dropPosition The drop position related to the target item.
   *   Eg. 'before', 'after', 'inside'.
   * @return {boolean}
   */
  isDroppedLocationWritable: function (dropItem, dropPosition) {
    var parentEntity;
    if (dropPosition === 'inside') {
      parentEntity = this.dataMgr.getEntityFromGID(dropItem.id);
    } else if (dropItem.parentId) {
      parentEntity = this.dataMgr.getEntityFromGID(dropItem.parentId);
    }
    if (parentEntity && !parentEntity.readonly) {
      return true;
    }
    return false;
  },

  isItemDroppable: function (item, dropItem, dropPosition) {
    var parentEntity;
    var parentIDObj;
    var entity;
    var entityIDObj;
    var allowedChildItems;
    var i;
    if (dropPosition === 'inside') {
      parentEntity = this.dataMgr.getEntityFromGID(dropItem.id);
      parentIDObj = this.parseEntityTag(dropItem.id);
    } else if (dropItem.parentId) {
      parentEntity = this.dataMgr.getEntityFromGID(dropItem.parentId);
      parentIDObj = this.parseEntityTag(dropItem.parentId);
    }
    entity = this.dataMgr.getEntityFromGID(item.id);
    entityIDObj = this.parseEntityTag(item.id);
    if (parentEntity && parentIDObj && entity && entityIDObj) {
      if (parentIDObj.prefix === 'seq' && parentEntity.typeID) {
        allowedChildItems = this.getTermListMenuItems('trm' + parentEntity.typeID);
        for (i = 0; i < allowedChildItems.length; i++) {
          if (entityIDObj.prefix === 'seq') {
            if (allowedChildItems[i].id === item.value) {
              return true;
            }
          } else {
            // Token, Compound, Syllable.
            if (allowedChildItems[i].id === 'Link') {
              return true;
            }
          }
        }
      }
    }
    return false;
  }

};
