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
* @subpackage  Editor Classes
*/
var EDITORS = EDITORS || {};

/**
* Constructor for Edition Viewer/Editor Object
*
* @type Object
*
* @param editionVECfg is a JSON object with the following possible properties
*  "dataMgr" reference to the data manager/model manager
*  "layoutMgr" reference to the layout manager for displaying multiple editors
*  "edition" an entity data element which defines the edition and all it's structures.
*  "editionEditDiv" reference to the div element for display of the Edition
*  "id" the id of the open pane used to create context ids for elements
*
* @returns {EditionVE}
*/

EDITORS.EditionVE =  function(editionVECfg) {
  //read configuration and set defaults
  this.config = editionVECfg;
  this.type = "EditionVE";
  this.dataMgr = editionVECfg['dataMgr'] ? editionVECfg['dataMgr']:null;
  this.layoutMgr = editionVECfg['layoutMgr'] ? editionVECfg['layoutMgr']:null;
  this.edition = editionVECfg['edition'] ? editionVECfg['edition']:null;
  this.editDiv = editionVECfg['editionEditDiv'] ? editionVECfg['editionEditDiv']:null;
  this.id = editionVECfg['id'] ? editionVECfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.init();
  return this;
};

/**
* Prototype for Edition Viewer/Editor Object
*/
EDITORS.EditionVE.prototype = {

/**
* Initialiser for Edition Viewer/Editor Object
*/

  init: function() {
    var ednVE = this;
    if (this.dataMgr && this.dataMgr.entities) {//warninig!!!!! patch until change code to use dataMgr api
      window.entities = this.dataMgr.entities;
      window.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
    }
    this.footnoteTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["footnote-footnotetype"];//term dependency
    this.transcrTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["transcription-footnote"];//term dependency
    this.paraphraseTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["paraphrase-textreflinkage"];//term dependency
    this.quoteTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["quote-textreflinkage"];//term dependency
    this.altEditionTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["altedition-textreflinkage"];//term dependency
    this.reconstrTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["reconstruction-footnote"];//term dependency
    this.typeIDs = [this.footnoteTypeID,this.transcrTypeID,this.reconstrTypeID,
                    this.paraphraseTypeID,this.quoteTypeID,this.altEditionTypeID];
    this.cntFootnote = 0;
    this.curTagID = null;
    this.anchorTagID = null;
    this.parentTagID = null;
    this.checkEditCollision = false;
    this.autoInsert = true;
    this.autoLink = false;
    this.selOrderGrpGraClasses = [];
    this.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
    this.splitterDiv = $('<div id="'+this.id+'splitter"/>');
    this.contentDiv = $('<div id="'+this.id+'textContent" class = "ednveContentDiv" contenteditable="true" spellcheck="false" ondragstart="return false;" />');
    this.propertyMgrDiv = $('<div id="'+this.id+'propManager" class="propertyManager" />');
    this.splitterDiv.append(this.contentDiv);
    this.splitterDiv.append(this.propertyMgrDiv);
    $(this.editDiv).append(this.splitterDiv);
    this.splitterDiv.jqxSplitter({ width: '100%',
                                      height: '100%',
                                      orientation: 'vertical',
                                      splitBarSize: 1,
                                      showSplitBar:false,
                                      panels: [{ size: '60%', min: '250', collapsible: false},
                                               { size: '40%', min: '150', collapsed: true, collapsible: true}] });
    this.propMgr = new MANAGERS.PropertyManager({id: this.id,
                                                 propertyMgrDiv: this.propertyMgrDiv,
                                                 editor: ednVE,
                                                 ednID: this.edition.id,
                                                 propVEType: "entPropVE",
                                                 dataMgr: this.dataMgr,
                                                 splitterDiv: this.splitterDiv });
    this.displayProperties = this.propMgr.displayProperties;
    this.calcSeqList();
    this.setDefaultSeqType();
    this.createStaticToolbar();
    this.setTagView();
    this.calcEditionRenderMappings();
    this.renderEdition();
    this.calcLineScrollDataBySegLookups();
    this.componentLinkMode = false;
  },


/**
* set focus to the first grapheme group of the display
*
*/

  setFocus: function () {
    $(this.editDiv).removeClass('autoOrdLinkTarget');
    if (this.pendingAutoLinkOrdBln) {//pending autoLink request so verify we can enter mode
      //check already in link mode
      if (this.linkMode) {
        alert("Currently in direct single select link mode" + (this.autoLink?" with autoAdvance":"") +
               ". You must exit before trying to Auto Link by Number. Aborting Auto Number Link Request.");
        $('.editContainer').trigger('autoLinkOrdAbort',[this.id,this.pendingAutoLinkOrdBln]);
        delete this.pendingAutoLinkOrdBln;
        delete this.pendingAutoLinkOrdMode;
        return;
      }
      //check bln is associated with this editions text
      text = this.dataMgr.getEntity('txt',this.edition.txtID);
      if (text.blnIDs.indexOf(this.pendingAutoLinkOrdBln) == -1) {//invalid link request but user may be doing something in the interem
        delete this.pendingAutoLinkOrdBln;
        delete this.pendingAutoLinkOrdDefault;
      } else {//valid editon for baseline so tell the user to select the first syllable
        this.autoLinkOrdMode = this.pendingAutoLinkOrdMode;
        this.autoLinkOrdBln = this.pendingAutoLinkOrdBln;
        $(this.editDiv).addClass('autoOrdLinkingMode');
        delete this.pendingAutoLinkOrdBln;
        delete this.pendingAutoLinkOrdMode;
        if (this.autoLinkOrdMode == "default") {//send the edition info back to the request
          $('.editContainer').trigger('autoLinkOrdReturn',[this.id,this.autoLinkOrdBln,this.edition.id],null,null);
        } else {
          //show link ordinal button
          this.linkOrdBtnDiv.show();
          alert("Auto linking for a range of segments requires you to select the start-point syllable in the edition and then click the 'Finish Linking' button");
        }
      }
    }
    $(this.editDiv.firstChild.firstChild).find('.grpGra:first').focus();
  },


/**
* calculate a list of used tag terms for entities of this edition
*
* @returns object array Item structures for jqWidget listbox
*/

  getUsedTagsList: function () {
    var usedTagIDs = Object.keys(this.dataMgr.tagIDToAnoID),//get the cur tags list from dataMgr
        reservedColors = {Blue:1,Green:1,Red:1},
        colorChoices = ['cyan','yellowgreen','coral','yellow','cadetblue','aqua',
                        'gold','khaki','lavender','lightcyan','lightgrey','lightsteelblue','palegreen',
                        'plum','pink','skyblue','sandbrown','silver','springgreen','violet','tan'],
        items = [], i, item, tagID, anoID, label, color, cssSelectors,scope = "#"+this.contentDiv.attr('id');

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
      if (items.length == 0) {
        items.push({id:'off',label:'Off',value:'off'});
      } else {
        items = [{ label:"All", expanded:true, items:items}];
      }
    }
    return items;
  },


/**
* set view of tag tree to show a reduced scope tree
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
* set default sequence entity type
*
* @param int seqTypeID Sequence entity type Term entity id
*/

  setDefaultSeqType: function (seqTypeID) {
    if (!seqTypeID || !this.dataMgr.seqTypeTagToLabel['trm'+seqTypeID]) {
      this.defaultSeqTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel['paragraph-section'];//term dependency
    } else {
      this.defaultSeqTypeID = seqTypeID;
    }
  },


/**
* add a default type sequence entity to this edition
*/

  addSequence: function () {
    DEBUG.traceEntry("addSequence");
    var ednVE = this;
    if (!this.defaultSeqTypeID) {
      this.setDefaultSeqType(null);
    }
    if (this.entPropVE && this.entPropVE.createNewSequence) {
      this.entPropVE.createNewSequence(this.defaultSeqTypeID,
                                       this.getSelectionEntityGIDs(),
                                       function(entTag) {
                                         $('.editContainer').trigger('structureChange',[ednVE.id,ednVE.edition.id,entTag]); 
                                       });
      this.showProperties(true);
    }
    DEBUG.traceExit("addSequence");
  },


/**
* add a text reference sequence entity to this edition
*/

addTextReference: function () {
  DEBUG.traceEntry("addTextReference");
  var ednVE = this,
      externalRefID = this.dataMgr.termInfo.idByTerm_ParentLabel['externalreference-textreferences'];

  if (externalRefID && this.entPropVE && this.entPropVE.createNewSequence) {
    this.entPropVE.createNewSequence(externalRefID,
                                     this.getSelectionEntityGIDs(),
                                     function(entTag) {
                                       $('.editContainer').trigger('structureChange',[ednVE.id,ednVE.edition.id,entTag]); 
                                     });
    this.showProperties(true);
  }
  DEBUG.traceExit("addTextReference");
},


/**
* calculate a list of used Sequence type terms for this edition
*
* @returns object array Item structures for jqWidget listbox
*/

  getUsedSeqsList: function () {
    var usedSeqIDs = Object.keys(this.seqGIDsByType),//get the cur seqIDs
        colorChoices = {"Analysis":"#C2CBCE",//warning!!! term dependency
                        "Chapter":"#61A3D9",//warning!!! term dependency
                        "Section":"#9BC2E6",//warning!!! term dependency
                        "Paragraph":"#BDD7EE",//warning!!! term dependency
                        "Sentence":"#70ad47",//warning!!! term dependency
                        "Clause":"#a9d08e",//warning!!! term dependency
                        "Phrase":"#c4dfb2",//warning!!! term dependency
                        "List":"#FE642E",//warning!!! term dependency
                        "Item":"#fe9d7a",//warning!!! term dependency
                        "Stanza":"#FFC03C",//warning!!! term dependency
                        "Pāda":"#FFE699",//warning!!! term dependency
                        "other":['cyan','yellowgreen','coral','yellow','cadetblue','aqua',
                        'gold','khaki','lavender','lightcyan','lightgrey','lightsteelblue','palegreen',
                        'plum','pink','skyblue','sandbrown','silver','springgreen','violet','tan']},
        items = [], i, item, tagID, label, color;

    //if empty nothing tagged create single item "off" using off for all item values
    if (!usedSeqIDs || usedSeqIDs.length == 0) {
      items.push({id:'off',label:'Off',value:'off'});
    } else {
      //foreach seg create item
      for (i in usedSeqIDs) {
        tagID = usedSeqIDs[i];
        label = this.dataMgr.getTermFromID(tagID.substring(3));
        //set background color from a list reserving primary color tag color to match tag name.
        if (colorChoices[label]){
          color = colorChoices[label];
        } else {
          color = colorChoices["other"][i%colorChoices["other"].length];
        }
        item = {id:tagID,
                label:label,
                value:color
               };
        items.push(item);
      }
      items = [{ label:"All", expanded:true, items:items}];
    }
    return items;
  },


/**
* calculate lookup tables for sequences of this edition
*/

  calcSeqList: function () {
    var ednVE = this, seqIDs = this.edition.seqIDs, i, k, l, entTag, childSeqTag, seqID, seqGID, ednSeqTag,
        seqType, prefix, typeID, sequence, entity, seqIDsByType = {}, parentTagBySeqTag = {},
        entTagsBySeqTag = {};


    /**
    * recursively add child components to the sequence lookup tables
    *
    * @param string seqTag Parent sequence entity tag
    * @param string array entGIDs Entity global identifiers
    */

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
          sequence = ednVE.dataMgr.getEntityFromGID(gid);
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
          entity = ednVE.dataMgr.getEntityFromGID(gid);
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
      sequence = ednVE.dataMgr.getEntity('seq',seqID);
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
* inject superscript markers into edition display
*
* @param string seqTypeTrmTag Sequence Type term tag
* @param string color CSS color for given sequence type
*/

  injectSeqMarkers: function (seqTypeTag,color) {
    var ednVE = this,
        seqGIDs = this.seqGIDsByType[seqTypeTag], i, seqGID, label, seqTag,
        component, sequence, firstEntGID, insertElem,
        seqLabel = this.dataMgr.getTermFromID(seqTypeTag.substring(3));
    if (seqGIDs && seqGIDs.length > 0) {
      for (i=0; i<seqGIDs.length; i++) {
        seqGID = seqGIDs[i];
        seqTag = seqGID.replace(':','');
        sequence = this.dataMgr.getEntityFromGID(seqGID);
        label = sequence.sup? sequence.sup : ( sequence.value?sequence.value:seqTag);
        if (sequence.entityIDs && sequence.entityIDs.length){
          firstEntGID = sequence.entityIDs[0];//TODO this prevents injection if non first seq has word component.
          while (firstEntGID && firstEntGID.match(/^seq/)) {//walk the first childs till first non sequence
            component = this.dataMgr.getEntityFromGID(firstEntGID);
            firstEntGID = (component && component.entityIDs)?component.entityIDs[0]:null;
          }
          if (firstEntGID){// found no sequence child component
            firstEntGID = firstEntGID.replace(':','');
            // the grpGRA for this entity tag
            $(':not(.'+firstEntGID+') + .'+firstEntGID, this.contentDiv).first().before('<sup class="'+seqTypeTag+' '+seqTag+
                                                               '" title="'+seqLabel+' '+label+
                                                               '" style="background-color:'+(color?color:'inherit')+';">'+
                                                               label+'</sup>');
          }
        }
      }
      //add handlers
      $('sup',this.contentDiv).unbind('click').bind('click', function(e){
        var classes = $(this).attr('class'),
            entTag = classes.match(/seq\d+/);
        if (entTag.length) {
          entTag = entTag[0];
        }
        if (ednVE.componentLinkMode && ednVE.entPropVE && ednVE.entPropVE.addSequenceEntityGID && entTag ){
          ednVE.entPropVE.addSequenceEntityGID(entTag.substring(0,3)+':'+entTag.substring(3));
        } else {
          ednVE.propMgr.showVE(null,entTag);
        }
        if (ednVE.entTagsBySeqTag[entTag] && ednVE.entTagsBySeqTag[entTag].length){
          $.each(ednVE.entTagsBySeqTag[entTag], function(i,val) {
            $('.'+val,ednVE.contentDiv).addClass("selected");
          });
          $('.editContainer').trigger('updateselection',[ednVE.id,[entTag],entTag]);
        }
      });
    }
  },

  getEdnID: function () {
    return this.edition.id;
  },

  refreshEditionHeader: function () {
    var ednVE = this,
        edition = this.dataMgr.entities.edn[this.edition.id],
        headerDiv = $('h3.ednLabel',this.contentDiv);
    headerDiv.html(edition.value);
    this.layoutMgr.refreshCursor();
  },

  refreshPhysLineHeader: function (seqID) {
    var ednVE = this,
        sequence = this.dataMgr.entities.seq[seqID],
        seqType = this.dataMgr.getTermFromID(sequence.typeID).toLowerCase(),
        lineHeaderSpan;
    if ('linephysical' == seqType || 'freetext' == seqType) { // warning!!! term dependency
      lineHeaderSpan = $('span.textDivHeader.lineseq'+seqID,this.contentDiv);
      if (lineHeaderSpan.length) {
        lineHeaderSpan.html(sequence.value);
      }
      lineMarkerSpan = $('span.linelabel.seq'+seqID,this.contentDiv);
      if (lineMarkerSpan.length) {
        lineMarkerSpan.html("["+sequence.value+"]");
      }
    }
  },


/**
* refresh Tag markers in edition display
*/

  refreshTagMarkers: function () {
    var ednVE = this, element,
        checkedItems, cntNewCheckedItems = 0, i, item, dropDownContent = '', styles = '',
        newUsedList = this.getUsedTagsList();
    checkedItems = this.showTagTree.jqxTree('getCheckedItems');
    this.showTagTree.jqxTree('clear');
    this.showTagTree.jqxTree('addTo', newUsedList);
    if (newUsedList.length == 1 && newUsedList[0]['id'] == "off") {
      this.showTagDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');
    } else {
      for (i in checkedItems) {
        item = checkedItems[i];
        element = $('#'+item.id,this.showTagTree);
        if (element.length) {
          element = element.get(0);
          ednVE.injectSeqMarkers(item.id,null);
          cntNewCheckedItems++;
          this.showTagTree.jqxTree('checkItem', element, true);
        }
      }
    }
    if (cntNewCheckedItems == 0) {
      //nothing selected so show tags is "off"
      ednVE.showTagDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');
      //if stylesheet exist clear it to remove tagging
      if (ednVE.tagStyleSheet) {
        ednVE.tagStyleSheet.remove();
        delete ednVE.tagStyleSheet;
      }
    }
  },


/**
* refresh sequence type markers in edition display
*/

  refreshSeqMarkers: function (seqIDs) {
    var ednVE = this, element,segTypeTag, color,label,
        checkedItems, i, item, seqID, sequence, $marker;
    checkedItems = this.showSeqTree.jqxTree('getCheckedItems');
    this.calcSeqList();
    this.removeAllSeqMarkers();
    this.showSeqTree.jqxTree('clear');
    this.showSeqTree.jqxTree('addTo', this.getUsedSeqsList());
    for (i in checkedItems) {
      item = checkedItems[i];
      element = $('#'+item.id,this.showSeqTree);
      segTypeTag = item.id;
      color = item.value;
      if (element.length) {
        element = element.get(0);
        if ($('sup.'+segTypeTag,ednVE.editDiv).length == 0) {
          ednVE.injectSeqMarkers(segTypeTag,color);
        } else if (seqIDs && seqIDs.length > 0){
          for (j in seqIDs) {
            seqID = seqIDs[j];
            sequence = ednVE.dataMgr.getEntity('seq',seqID);
            if (sequence) {
              label = sequence.sup? sequence.sup : ( sequence.value?sequence.value:'seq'+seqID);
              $marker = $('sup.seq'+seqID,ednVE.editDiv);
              if ($marker) {
                $marker.html(label);
              }
            }
          }
        }
        this.showSeqTree.jqxTree('checkItem', element, true);
      } else {
          ednVE.removeSeqMarkers(segTypeTag);
      }
    }
  },

  removeSeqMarkers: function (seqTypeTag) {
    $('sup.'+seqTypeTag,this.editDiv).remove();
  },

  removeAllSeqMarkers: function () {
    $('sup:not(.footnote)',this.editDiv).remove();
  },

 afterUpdate: function(entTag) {
    var ednVE = this, prefix, entID, entity, $elems,classes, match, txtDivSeqID, lineOrd, $header, physLineID;
    entTag = entTag.replace(":","");// ensure GID is converted incase
    prefix = entTag.substring(0,3);
    entID = entTag.substring(3);
    //find scope of change
    if (prefix == "seq") {
// todo define cases where this makes sense like structure sequence changes.
    } else {
      $elems = $(".grpGra."+entTag,ednVE.contentDiv);
      if ($elems.length > 0) {
        classes = $elems.get($elems.length - 1).className; // use last incase anchor to end
        match = classes.match(/seq(\d+)/);
        if (match && match.length > 1) {
          txtDivSeqID = match[1];
          ednVE.calcTextDivGraphemeLookups(txtDivSeqID);
        }
        match = classes.match(/ordL\d+/);
        if (match) {
          lineOrd = match[0];
          $header = $(".textDivHeader."+lineOrd);
          if ($header.length == 1) {
            classes = $header.get(0).className;
            match = classes.match(/lineseq(\d+)/);
            if (match && match.length > 1) {
              physLineID = match[1];
              ednVE.calcLineGraphemeLookups(physLineID);
              ednVE.reRenderPhysLine(lineOrd,physLineID);
            }
          }
        }
      }
    }
    //update presentation
  },


/**
* create toolbars for this edition editor
*/

  createStaticToolbar: function () {
    var ednVE = this,
        btnObjLevelName = this.id+'objlevel',
        btnEdStyleName = this.id+'edstyle',
        btnFormatName = this.id+'edformat',
        btnLinkName = this.id+'link',
        btnEditModeName = this.id+'editmode',
        btnAddSyllableName = this.id+'addsyllable',
        btnShowSepName = this.id+'showtoksep',
        btnShowPropsName = this.id+'showprops',
        btnAddSequenceName = this.id+'addsequence',
        btnAddTextRefName = this.id+'addtextref',
        btnInsertLineName = this.id+'insertline',
        btnDeleteLineName = this.id+'deleteline',
        ddbtnShowTagName = this.id+'showtagbutton',
        treeShowTagName = this.id+'showtagtree',
        ddbtnShowSeqName = this.id+'showseqbutton',
        treeShowSeqName = this.id+'showseqtree',
        ddbtnCurTagName = this.id+'curtagbutton',
        treeCurTagName = this.id+'curtagtree',
//        btnDownloadHTMLName = this.id+'downloadhtml',
        btnDownloadRTFName = this.id+'downloadrtf',
        btnLaunchViewerName = this.id+'launchviewer';
    this.catIDs = this.dataMgr.getCatIDsFromEdnID(this.edition.id);
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    this.linkSclBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnLinkName +
                              '" title="Link selected element">&#x1F517;</button>'+
                            '<div class="toolbuttonlabel">Link</div>'+
                           '</div>');
    this.linkSclBtn = $('#'+btnLinkName,this.linkSclBtnDiv);
    this.linkSclBtn.unbind('click').bind('click',function(e) {
      var selectedScl = $('.grpGra.selected',ednVE.contentDiv),
          sclID = null, selectedSclLabel;
      ednVE.autoLink = false;
      if (selectedScl.length) {
        selectedSclLabel = selectedScl.get(0).className.match(/scl\d+/);
        if (selectedSclLabel.length) {
          selectedSclLabel = selectedScl.get(0).className.match(/scl\d+/)[0];
          sclID = selectedSclLabel.substring(3);
        }
        if (ednVE.dataMgr.entities.scl && ednVE.dataMgr.entities.scl[sclID]) {
          if (ednVE.dataMgr.entities.scl[sclID].readonly) {
            alert("Insufficient permissions to link syllable '"+ednVE.dataMgr.entities.scl[sclID].value+"', aborting link");
          } else {
            if (confirm("Would you like to automatically advance syllables while linking?")) {
              ednVE.autoLink = true;
            }
            ednVE.linkSource = selectedSclLabel;
            alert("Please select a segmented akṣara or create one to link to syllable '"+selectedScl.text()+"' #" + selectedSclLabel);
            $('.editContainer').trigger('linkRequest',[ednVE.id,selectedSclLabel,ednVE.autoLink]);
          }
        }
      } else {
        DEBUG.log("warn","link pressed in pane "+ednVE.id+" with no scl selected");
        alert("Please select a syllable before pressing link.");
      }
    });

    this.editModeBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnEditModeName +
                              '" title="Change Edition edit mode">View</button>'+
                            '<div class="toolbuttonlabel">Edit mode</div>'+
                           '</div>');
    this.editModeBtn = $('#'+btnEditModeName,this.editModeBtnDiv);
    this.editModeBtn.unbind('click').bind('click',function(e) {
                                            if (ednVE.changeEditMode) {
                                              ednVE.changeEditMode(e,$(this));
                                            }
                                          });

    this.addSyllableBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnAddSyllableName+
                              '" title="Insert new syllable at cursor">+ Syllable</button>'+
                            '<div class="toolbuttonlabel">Add syllable</div>'+
                           '</div>');
    this.addSyllableBtn = $('#'+btnAddSyllableName,this.addSyllableBtnDiv);
    this.addSyllableBtn.unbind('click').bind('click',function(e) {
                                            if (ednVE.sclEd && ednVE.sclEd.insertNew) {
                                              ednVE.sclEd.insertNew();
                                            }
                                          });

    this.showSepBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnShowSepName +
                              '" title="Show/hide token separators">Show</button>'+
                            '<div class="toolbuttonlabel">Token Splits</div>'+
                           '</div>');
    $('#'+btnShowSepName,this.showSepBtnDiv).unbind('click')
                               .bind('click',function(e) {
                                  if (!ednVE.toksepVisible) {
                                    $(this).html("Hide");
                                    $(ednVE.editDiv).addClass("showTokenSep");
                                    ednVE.toksepVisible = true;
                                  } else {
                                    $(this).html("Show");
                                    $(ednVE.editDiv).removeClass("showTokenSep");
                                    ednVE.toksepVisible = false;
                                  }
                                });
    var level;
    switch (ednVE.layoutMgr.getEditionObjectLevel()) {
      case 'syllable':
        level = "Syllable";
        break;
      case 'token':
        level = "Word";
        break;
      case 'compound':
      default:
        level = "Compound";
        break;
    }
    this.objLevelBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnObjLevelName +
                              '" title="Set object selection level">'+ level +'</button>'+
                            '<div class="toolbuttonlabel">Object Level</div>'+
                           '</div>');
    this.objLevelBtn = $('#'+btnObjLevelName,this.objLevelBtnDiv);
    this.objLevelBtn.unbind('click')
                     .bind('click',function(e) {
                        switch (ednVE.layoutMgr.getEditionObjectLevel()) {
                          case 'syllable':
                            ednVE.layoutMgr.setEditionObjectLevel('token');
                            $(this).html("Word");
                            break;
                          case 'token':
                            ednVE.layoutMgr.setEditionObjectLevel('compound');
                            $(this).html("Compound");
                            break;
                          case 'compound':
                          default:
                            ednVE.layoutMgr.setEditionObjectLevel('syllable');
                            $(this).html("Syllable");
                            break;
                        }
                        $('.editContainer').trigger('objectLevelChanged');
                      });
    this.edStyleBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnEdStyleName +
                              '" title="Change edition display format">Hybrid</button>'+
                            '<div class="toolbuttonlabel">Edition Style</div>'+
                           '</div>');
    this.edStyleBtn = $('#'+btnEdStyleName,this.edStyleBtnDiv);
    this.edStyleBtn.unbind('click')
                   .bind('click',function(e) {
                      switch (ednVE.repType) {
                        case 'hybrid':
                          ednVE.repType = 'diplomatic';
                          $(this).html("Diplomatic");
                          ednVE.typeIDs = [ednVE.transcrTypeID];
                          break;
                        case 'diplomatic':
                          ednVE.repType = 'reconstructed';
                          $(this).html("Reconstructed");
                          ednVE.typeIDs = [ednVE.footnoteTypeID,ednVE.reconstrTypeID];
                          break;
                        case 'reconstructed':
                        default:
                          ednVE.repType = 'hybrid';
                          $(this).html("Hybrid");
                          ednVE.typeIDs = [ednVE.footnoteTypeID,ednVE.transcrTypeID,ednVE.reconstrTypeID];
                          break;
                      }
                      ednVE.downloadRTFLink.attr('href',basepath+"/services/exportRTFEdition.php?db="+dbName+"&ednID="+ednVE.edition.id+"&style="+ednVE.repType+"&download=1");
                      ednVE.downloadRTFBtn.attr('title',"Download Physical '"+ednVE.repType+"' View to RTF");
                      ednVE.renderEdition();
                      ednVE.refreshSeqMarkers();
                    });

    this.formatBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnFormatName +
                              '" title="Change Display Format">Physical</button>'+
                            '<div class="toolbuttonlabel">Format</div>'+
                           '</div>');
    this.formatBtn = $('#'+btnFormatName,this.formatBtnDiv);
    this.formatBtn.unbind('click')
                  .bind('click',function(e) {
                    switch (ednVE.format) {
                      case 'physical':
                        ednVE.format = 'structure';
                        $(this).html("Structure");
                        this.typeIDs = [this.footnoteTypeID,this.reconstrTypeID];
                        ednVE.renderEditionStructure();
                        ednVE.addSequenceBtn.attr('disabled','disabled');
                        ednVE.addSyllableBtn.attr('disabled','disabled');
                        ednVE.edStyleBtn.attr('disabled','disabled');
                        ednVE.linkSclBtn.attr('disabled','disabled');
                        ednVE.editModeBtn.attr('disabled','disabled');
                        if (ednVE.downloadRTFBtn.attr('disabled') == 'disabled') {
                          ednVE.downloadRTFBtn.removeAttr('disabled');
                        }
                        ednVE.downloadRTFBtn.attr('title',"Download Structured Reconstructed View to RTF");
                        ednVE.downloadRTFLink.attr('href',basepath+"/services/exportRTFStructural.php?db="+dbName+"&ednID="+ednVE.edition.id+"&download=1");
                        ednVE.showSeqBtnDiv.hide();
                        break;
                      case 'structure':
                      default:
                        ednVE.format = 'physical';
                        $(this).html("Physical");
                        switch (ednVE.repType) {
                          case 'diplomatic':
                            ednVE.typeIDs = [ednVE.transcrTypeID];
                            break;
                          case 'reconstructed':
                            ednVE.typeIDs = [ednVE.footnoteTypeID,ednVE.reconstrTypeID];
                            break;
                          case 'hybrid':
                          default:
                             ednVE.typeIDs = [ednVE.footnoteTypeID,ednVE.transcrTypeID,ednVE.reconstrTypeID];
                            break;
                        }
                        ednVE.renderEdition();
                        ednVE.addSequenceBtn.removeAttr('disabled');
                        ednVE.addSyllableBtn.removeAttr('disabled');
                        ednVE.edStyleBtn.removeAttr('disabled');
                        ednVE.linkSclBtn.removeAttr('disabled');
                        ednVE.editModeBtn.removeAttr('disabled');
                        ednVE.downloadRTFLink.attr('href',basepath+"/services/exportRTFEdition.php?db="+dbName+"&ednID="+ednVE.edition.id+"&style="+ednVE.repType+"&download=1");
                        ednVE.downloadRTFBtn.attr('title',"Download Physical '"+ednVE.repType+"' View to RTF");
                        ednVE.showSeqBtnDiv.show();
                        ednVE.refreshSeqMarkers();
                        break;
                    }
                  });
    this.format = 'physical';

    // ********  Show Tags **********
    this.showTagBtnDiv = $('<div class="toolbuttondiv">' +
                           '<div id="'+ ddbtnShowTagName+'"><div id="'+ treeShowTagName+'"></div></div>'+
                           '<div class="toolbuttonlabel">Tags</div>'+
                           '</div>');
    this.showTagTree = $('#'+treeShowTagName,this.showTagBtnDiv);
    this.showTagDdBtn = $('#'+ddbtnShowTagName,this.showTagBtnDiv);
    this.showTagTree.jqxTree({
           source: this.getUsedTagsList(),
           hasThreeStates: true, checkboxes: true,
           width: '250px',
           theme:'energyblue'
    });
    this.showTagTree.on('checkChange', function (event) {
        var args = event.args, dropDownContent = '', styles = '', i, item,
            checkedItems =  ednVE.showTagTree.jqxTree('getCheckedItems');
        if (checkedItems.length) {
          dropDownContent = '<div class="listDropdownButton">' + checkedItems[0].label + (checkedItems.length>1?"...":"")+ '</div>';
          ednVE.showTagDdBtn.jqxDropDownButton('setContent', dropDownContent);
          //aggregate the checked values to create css tagging rules for injection
          for (i in checkedItems) {
            styles += checkedItems[i].value;
          }
          //else create it and inject new css rules
          if (ednVE.tagStyleSheet) {
            ednVE.tagStyleSheet.remove();
          }
          ednVE.tagStyleSheet = $('<style type="text/css">'+ styles +'</style>').appendTo(document.head);
        } else {
          //nothing selected so show tags is "off"
          ednVE.showTagDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');
          //if stylesheet exist clear it to remove tagging
          if (ednVE.tagStyleSheet) {
            ednVE.tagStyleSheet.remove();
            delete ednVE.tagStyleSheet;
          }
        }
    });
    this.showTagDdBtn.jqxDropDownButton({width:95, height:25 });
    this.showTagDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');

    // ********  Show Sequence Markers **********
    this.showSeqBtnDiv = $('<div class="toolbuttondiv">' +
                           '<div id="'+ ddbtnShowSeqName+'"><div id="'+ treeShowSeqName+'"></div></div>'+
                           '<div class="toolbuttonlabel">Sequences</div>'+
                           '</div>');
    this.showSeqTree = $('#'+treeShowSeqName,this.showSeqBtnDiv);
    this.showSeqDdBtn = $('#'+ddbtnShowSeqName,this.showSeqBtnDiv);
    this.showSeqTree.jqxTree({
           source: this.getUsedSeqsList(),
           hasThreeStates: true, checkboxes: true,
           width: '250px',
           theme:'energyblue'
    });
    this.showSeqTree.on('checkChange', function (event) {
        var args = event.args, element = args.element, checked = args.checked,
            dropDownContent = '', i, item = ednVE.showSeqTree.jqxTree('getItem',element),
            segTypeTag = item.id, color = item.value,
            checkedItems =  ednVE.showSeqTree.jqxTree('getCheckedItems');

        if (checked) {
          if ($('sup.'+segTypeTag,ednVE.editDiv).length == 0) {
            ednVE.injectSeqMarkers(segTypeTag,color);
          }
        } else {
            ednVE.removeSeqMarkers(segTypeTag);
        }

        if (checkedItems.length) {
          dropDownContent = '<div class="listDropdownButton">' + checkedItems[0].label + (checkedItems.length>1?"...":"")+ '</div>';
          ednVE.showSeqDdBtn.jqxDropDownButton('setContent', dropDownContent);
          //aggregate the checked values to create css tagging rules for injection
        } else {
          //nothing selected so show tags is "off"
          ednVE.showSeqDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');
          //if stylesheet exist clear it to remove tagging
        }
    });
    this.showSeqDdBtn.jqxDropDownButton({width:95, height:25 });
    this.showSeqDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');

    this.addSequenceBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnAddSequenceName +
                              '" title="Create a new sequence entity">+ Sequence</button>'+
                            '<div class="toolbuttonlabel">Add sequence</div>'+
                           '</div>');
    this.addSequenceBtn = $('#'+btnAddSequenceName,this.addSequenceBtnDiv);
    this.addSequenceBtn.unbind('click').bind('click',function(e) {
      if (ednVE.addSequence ) {
        ednVE.addSequence();
      }
    });

    this.addTextRefBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnAddTextRefName +
                              '" title="Create a new text reference set">+ Reference</button>'+
                            '<div class="toolbuttonlabel">Add Text Ref</div>'+
                           '</div>');
    this.addTextRefBtn = $('#'+btnAddTextRefName,this.addTextRefBtnDiv);
    this.addTextRefBtn.unbind('click').bind('click',function(e) {
      if (ednVE.addTextReference ) {
        ednVE.addTextReference();
      }
    });

      var btnLinkOrdName = this.id+'LinkOrd';
      this.linkOrdBtnDiv = $('<div class="toolbuttondiv">' +
                              '<button class="toolbutton" id="'+btnLinkOrdName +
                                '" title="Finish auto linking by segment number">Finish Linking</button>'+
                              '<div class="toolbuttonlabel">Link by number</div>'+
                             '</div>');
      $('#'+btnLinkOrdName,this.linkOrdBtnDiv).unbind('click')
                                 .bind('click',function(e) {
        var ednID = ednVE.edition.id,sclIDs=[],
            text = ednVE.dataMgr.getEntity('txt',ednVE.edition.txtID),
            blnIDs = text.blnIDs,
            sclGIDs = ednVE.getSelectionOrdEntityGIDs('scl'),index,pos;
        if (ednVE.autoLinkOrdMode) {
          if (!sclGIDs) {
            sclGIDs = ednVE.getSelectionEntityGIDs();
          }
          if (sclGIDs) {
            for (index in sclGIDs) {
              pos = (sclGIDs[index].match(/\:/)?4:3);
              sclIDs.push((sclGIDs[index]).substr(pos));
            }
          }
          $('.editContainer').trigger('autoLinkOrdReturn',[ednVE.id,ednVE.autoLinkOrdBln,ednID,sclIDs,blnIDs]);
        }
      });

    this.insertLineBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnInsertLineName +
                              '" title="Insert a new physical line">+</button>'+
                            '<div class="toolbuttonlabel">Insert line</div>'+
                           '</div>');
    $('#'+btnInsertLineName,this.insertLineBtnDiv).unbind('click').bind('click',function(e) {
      if (ednVE.insertFreeTextLine) {
        ednVE.insertFreeTextLine();
      }
    });

    this.deleteLineBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnDeleteLineName +
                              '" title="Delete current physical line and associate components">-</button>'+
                            '<div class="toolbuttonlabel">Delete line</div>'+
                           '</div>');
    $('#'+btnDeleteLineName,this.deleteLineBtnDiv).unbind('click').bind('click',function(e) {
      if (ednVE.deleteLine) {
        ednVE.deleteLine();
      }
    });

    this.curTagBtnDiv = $('<div class="toolbuttondiv">' +
                           '<div id="'+ ddbtnCurTagName+'"><div id="'+ treeCurTagName+'"></div></div>'+
                           '<div class="toolbuttonlabel">Current Tag</div>'+
                           '</div>');
    this.tagTree = $('#'+treeCurTagName,this.curTagBtnDiv);
    this.tagDdBtn = $('#'+ddbtnCurTagName,this.curTagBtnDiv);
    this.tagTree.jqxTree({
           source: ednVE.dataMgr.tags,
           width: '250px',
           height: '300px',
           theme:'energyblue'
    });
    this.tagTree.on('expand', function (event) {
      var item =  ednVE.tagTree.jqxTree('getItem', event.args.element),
          isOldAnchorParentOfItem = false, item, itemElement, parentItem;
      if (ednVE.anchorTagID != item.id && ednVE.parentTagID != item.id ) {
        if (ednVE.anchorTagID) {
          if (item.id) {
            itemElement = $('li#'+item.id,ednVE.tagTree);
            if (itemElement.length) {
              item = ednVE.tagTree.jqxTree("getItem",itemElement[0]);
              if (item && item.parentElement) {
                parentItem = ednVE.tagTree.jqxTree("getItem",item.parentElement);
                if (ednVE.anchorTagID == parentItem.id) {
                  isOldAnchorParentOfItem = true;
                }
              }
            }
          }
          anchor = $('li#'+ednVE.anchorTagID,ednVE.tagTree);
          anchorItem = ednVE.tagTree.jqxTree("getItem",anchor[0]);
          if (anchorItem && anchorItem.isExpanded && !isOldAnchorParentOfItem) {
            ednVE.tagTree.jqxTree("collapseItem",anchor[0]);
          }
        }
        ednVE.anchorTagID = item.id;
        ednVE.setTagView();
      }
    });
    this.tagTree.on('collapse', function (event) {
      // if collapse is anchor then make parent anchor unless node is top level
      var item =  ednVE.tagTree.jqxTree('getItem', event.args.element),
          anchor,anchorItem;
      if (ednVE.anchorTagID == item.id) {
        if (ednVE.parentTagID) {
          ednVE.anchorTagID = ednVE.parentTagID;
          ednVE.setTagView();
        }
      } else if (ednVE.parentTagID && ednVE.parentTagID == item.id ) {
        if (ednVE.anchorTagID) {
          anchor = $('li#'+ednVE.anchorTagID,ednVE.tagTree);
          anchorItem = ednVE.tagTree.jqxTree("getItem",anchor[0]);
          if (anchorItem && anchorItem.isExpanded) {
            ednVE.tagTree.jqxTree("collapseItem",anchor[0]);
          }
        }
        ednVE.anchorTagID = ednVE.parentTagID;
        ednVE.parentTagID = null;
        ednVE.setTagView();
      }
    });
    this.tagTree.on('select', function (event) {
        var args = event.args, dropDownContent = '',
            item =  ednVE.tagTree.jqxTree('getItem', args.element);
        if (item.value) {
          //save selected tag to edition VE
          ednVE.curTagID = item.value.replace(":","");
          //display current tag
          dropDownContent = '<div class="listDropdownButton">' + item.label + '</div>';
          ednVE.tagDdBtn.jqxDropDownButton('setContent', dropDownContent);
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
                                           ednVE.showProperties(!$(this).hasClass("showUI"));
                                         });
    this.downloadRTFBtnDiv = $('<div class="toolbuttondiv">' +
                            '<a href="" >'+
                            '<button class="toolbutton iconbutton" id="'+btnDownloadRTFName+
                              '" title="Download Edition \'hybrid\' View to RTF">Download</button></a>'+
                            '<div class="toolbuttonlabel">RTF</div>'+
                           '</div>');
    this.downloadRTFBtn = $('#'+btnDownloadRTFName,this.downloadRTFBtnDiv);
    this.downloadRTFLink = this.downloadRTFBtn.parent();

    this.launchViewerDiv = $('<div class="toolbuttondiv">' +
                              '<a href="" target="_blank">'+
                              '<button class="toolbutton iconbutton" id="'+btnLaunchViewerName+
                                '" title="Launch READ Viewer">Launch</button></a>'+
                              '<div class="toolbuttonlabel">Viewer</div>'+
                            '</div>');
    this.launchViewerBtn = $('#'+btnLaunchViewerName,this.launchViewerDiv);
    this.launchViewerLink = this.launchViewerBtn.parent();


    this.viewToolbar.append(this.edStyleBtnDiv);
    this.viewToolbar.append(this.objLevelBtnDiv);
    this.viewToolbar.append(this.formatBtnDiv);
    this.viewToolbar.append(this.propertyBtnDiv);
    this.viewToolbar.append(this.showTagBtnDiv);
    this.viewToolbar.append(this.showSeqBtnDiv);
    this.viewToolbar.append(this.showSepBtnDiv);
    this.viewToolbar.append(this.downloadRTFBtnDiv);
    this.viewToolbar.append(this.launchViewerDiv);
    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    this.editToolbar.append(this.linkSclBtnDiv);
    this.editToolbar.append(this.editModeBtnDiv);
    this.editToolbar.append(this.insertLineBtnDiv);
    this.editToolbar.append(this.deleteLineBtnDiv);
    this.editToolbar.append(this.curTagBtnDiv);
    this.editToolbar.append(this.addSequenceBtnDiv);
    this.editToolbar.append(this.addTextRefBtnDiv);
    this.editToolbar.append(this.linkOrdBtnDiv);
    this.editToolbar.append(this.addSyllableBtnDiv);
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
    this.insertLineBtnDiv.hide();
    this.deleteLineBtnDiv.hide();
    this.addSyllableBtnDiv.hide();
    this.linkOrdBtnDiv.hide();
    this.curTagBtnDiv.hide();
    this.repType = "hybrid";//default
    this.downloadRTFLink.attr('href',basepath+"/services/exportRTFEdition.php?db="+dbName+
                                "&ednID="+this.edition.id+"&style="+this.repType+"&download=1");
    this.launchViewerLink.attr('href',basepath+"/viewer/getTextViewer.php?db="+dbName+
                                "&txtID="+this.edition.txtID+"&multiEd=1"+
                                ((this.catIDs && this.catIDs.length)?"&catID="+this.catIDs[0]:""));
  },


/**
* turn on or off the property pane
*
* @param boolean bShow Show property pane
*/

  showProperties: function (bShow) {
    var ednVE = this;
    if (ednVE.propMgr &&
        typeof ednVE.propMgr.displayProperties == 'function'){
      ednVE.propMgr.displayProperties(bShow);
      if (this.propertyBtn.hasClass("showUI") && !bShow) {
        this.propertyBtn.removeClass("showUI");
      } else if (!this.propertyBtn.hasClass("showUI") && bShow) {
        this.propertyBtn.addClass("showUI");
      }
    }
  },


/**
* check edition status entity with current tag id
*
* @param string entGID Entity global id (prefix:id)
*/

  checkEditionStatus: function (cb) {
    var ednVE = this, msgWarning = '', savedata = {};
    //setup data
    savedata={
      ednID: ednVE.edition.id,
    };
    if (DEBUG.healthLogOn) {
      savedata['hlthLog'] = 1;
    }
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: basepath+'/services/getEditionStatus.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' ) {
            if (data.collisiondetection == 'off') {//if set off set editor to skip service call
              ednVE.checkEditCollision = false;
              if (cb && typeof cb == 'function') {
                cb();
              }
            } else if (data.success && data.editUserName != ednVE.layoutMgr.userVE.username) {
              msgWarning ="Warning user "+data.editUserName+" ("+data.editUserID+
                          ") is editting edition ("+ednVE.edition.id+") with status: "+data.status +
                          ". Are you sure that you would like to continue?";
              if (confirm(msgWarning) && cb && typeof cb == 'function') {
                cb();
              }
            } else if (cb && typeof cb == 'function') {
              cb();
            }
          }
          if (data.editionHealth) {
            DEBUG.log("health","***Tag Entity***");
            DEBUG.log("health","Params: "+JSON.stringify(savedata));
            DEBUG.log("health",data.editionHealth);
          }
          if (data.errors) {
            alert("An error occurred while trying to edition status. Error: " + data.errors.join());
          }
        },
        error: function (xhr,status,error) {
          // add record failed.
          errStr = "Error while trying to checking Edition status. Error: " + error;
          DEBUG.log("err",errStr);
          alert(errStr);
        }
    });// end ajax
  },


/**
* change edit mode to modify mode
*
* @param object e System event object
* @param button editModeBtn Button element for mode change
*/

  changeToModify: function (e,editModeBtn) {
    var ednVE = this;
    this.editMode = "modify";
    editModeBtn.html("Modify");
    if (!this.sclEd) {
      this.sclEd = new EDITORS.sclEditor(ednVE);//todo guard against syllableEditor.js not included
    } else {
      this.sclEd.reInit();
    }
    this.insertLineBtnDiv.show();
    this.deleteLineBtnDiv.show();
    this.addSyllableBtnDiv.show();
    this.addSequenceBtnDiv.hide();
    this.addTextRefBtnDiv.hide();
    this.linkOrdBtnDiv.hide();
    this.showSeqBtnDiv.hide();
    this.showTagBtnDiv.hide();
    this.objLevelBtn.attr('disabled','disabled');
    this.edStyleBtn.attr('disabled','disabled');
    this.linkSclBtn.attr('disabled','disabled');
    this.formatBtn.attr('disabled','disabled');
    $(ednVE.contentDiv).addClass("modify");
  },

/**
* change edit mode of this edition editor
*
* @param object e System event object
* @param button editModeBtn Button element for mode change
*/

  changeEditMode: function (e,editModeBtn) {
    var ednVE = this;
    //modify
    if (!this.editMode && 
         ednVE.layoutMgr &&
         ednVE.layoutMgr.userVE &&
         ednVE.edition &&
         ednVE.edition.editibility &&
         ednVE.layoutMgr.userVE.isEditAsEditibilityMatch(ednVE.edition.editibility)) {
      if (this.checkEditCollision) {
        this.checkEditionStatus(function(){
          ednVE.changeToModify(e,editModeBtn);
        });
      } else {
        this.changeToModify(e,editModeBtn);
      }
     //TCM mode
    } else if (ednVE.editMode == "modify") {
      ednVE.editMode = "tcm";
      editModeBtn.html("Text Critical");
      if (!ednVE.tcmEd) {
        ednVE.tcmEd = new EDITORS.tcmEditor(ednVE);//todo guard against syllableEditor.js not included
      }
      if (ednVE.sclEd) {
        $('.selected',ednVE.contentDiv).removeClass('selected');
        ednVE.removeFreeTextLineUI();
        delete(ednVE.sclEd);
      }
      $(ednVE.contentDiv).removeClass("modify").addClass('tcmodify');
      this.propertyBtn.attr('disabled','disabled');
      this.insertLineBtnDiv.hide();
      this.deleteLineBtnDiv.hide();
      this.addSyllableBtnDiv.hide();
    //tagging mode
    } else if (ednVE.editMode == "tcm" ||
               (!ednVE.editMode && 
                 ednVE.layoutMgr &&
                 ednVE.layoutMgr.userVE &&
                 ednVE.edition &&
                 ednVE.edition.editibility &&
                !ednVE.layoutMgr.userVE.isEditAsEditibilityMatch(ednVE.edition.editibility))) {
      if (!ednVE.editMode && 
           ednVE.layoutMgr &&
           ednVE.layoutMgr.userVE &&
           ednVE.edition &&
           ednVE.edition.editibility &&
          !ednVE.layoutMgr.userVE.isEditAsEditibilityMatch(ednVE.edition.editibility)) {
        this.addSequenceBtnDiv.hide();
        this.addTextRefBtnDiv.hide();
        this.linkOrdBtnDiv.hide();
        this.objLevelBtn.attr('disabled','disabled');
        this.edStyleBtn.attr('disabled','disabled');
        this.linkSclBtn.attr('disabled','disabled');
      }
      if (ednVE.tcmEd) {
        if (ednVE.tcmEd.exit(function() {
                                delete(ednVE.tcmEd);
                              })) {
          ednVE.editMode = "tag";
          editModeBtn.html("Tagging");
          $(ednVE.contentDiv).removeClass('tcmodify').addClass('tagging');
          this.curTagBtnDiv.show();
          this.showTagBtnDiv.show();
          this.propertyBtn.removeAttr('disabled');
          this.objLevelBtn.removeAttr('disabled');
        }
      } else {
        ednVE.editMode = "tag";
        editModeBtn.html("Tagging");
        $(ednVE.contentDiv).removeClass('tcmodify').addClass('tagging');
        this.curTagBtnDiv.show();
        this.showTagBtnDiv.show();
        this.propertyBtn.removeAttr('disabled');
        this.objLevelBtn.removeAttr('disabled');
      }
    //view mode
    } else if (ednVE.editMode == "tag") {
        delete ednVE.editMode;
        editModeBtn.html("View");
        ednVE.refreshSeqMarkers();
        $(ednVE.contentDiv).removeClass('tagging');
        this.edStyleBtn.removeAttr('disabled');
        this.linkSclBtn.removeAttr('disabled');
        this.formatBtn.removeAttr('disabled');
        this.curTagBtnDiv.hide();
        this.addSequenceBtnDiv.show();
        this.addTextRefBtnDiv.show();
        if (this.autoLinkOrdMode) {
          this.linkOrdBtnDiv.show();
        }
        this.showSeqBtnDiv.show();
    }
  },


/**
* set syntatic dependency link mode
*
* @param bLinkMode boolean to set syntatic mode flag
* @returns boolean indicating success
*/

  setLinkSfDependencyFlag: function(bLinkMode) {
    if (this.LinkMode || this.editMode) {
      return false;
    } else {
      this.setLinkSfDependencyMode = bLinkMode;
      return true;
    }
  },

/**
* split token at current location
*
* @param function cbError Error callback function\n*/

  splitToken: function (cbError) {
    var ednVE = this, sclEd = this.sclEd, crosslineWord = false, curBOL = false, curEOL = false,
        context,refDivSeqID, sclIDs, splitPos, refSclID, splittokendata,
        newTokGID, physLineSeqID, lineOrdTag, adjPhysLineSeqID = null, adjLineOrdTag = null;
    if (sclEd) {
      lineOrdTag = sclEd.syllable[0].className.match(/ordL\d+/)[0];
      physLineSeqID = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv).attr('class').match(/lineseq(\d+)/)[1];
      splitPos = sclEd.getTokenCurPos();
      curBOL =sclEd.caretAtBOL();
      curEOL =sclEd.caretAtEOL();
      //special case syllables at the beginning or end of line
      if ( curBOL && !sclEd.syllable.hasClass('firstLine') ||
          curEOL && !sclEd.syllable.hasClass('lastLine')) {
        // set ordOffset variable BOL => -1 EOL + 1
        ordOffset = (curBOL?-1:1);
        adjLineOrdTag = "ordL" + (parseInt(lineOrdTag.substr(4))+ordOffset);
        adjPhysLineSeqID = $('.textDivHeader.'+adjLineOrdTag,ednVE.contentDiv).attr('class').match(/lineseq(\d+)/)[1];
        //check for crossline words to ensure that both lines are updated 
        // class of 'toksplit' indicates a crossline token and 'cmpsplit' indicates a crossline compound
        if (curBOL) {
          lbNode = $('.linebreak.'+adjLineOrdTag);
        } else {
          lbNode = $('.linebreak.'+lineOrdTag);
        }
        crosslineWord = (lbNode.hasClass('toksplit') || lbNode.hasClass('cmpsplit'));
      }
      if (!crosslineWord && splitPos == -1) {
        DEBUG.beep();
        return;
      }
      context = sclEd.syllable[0].className.replace(/grpGra/,"")
                                      .replace(/ord\d+/,"")
                                      .replace(/ordL\d+/,"")
                                      .replace(/seg\d+/,"")
                                      .replace(/prepadding/,"")
                                      .replace(/firstLine/,"")
                                      .replace(/lastLine/,"")
                                      .replace(/selected/,"")
                                      .trim()
                                      .replace(/\s+/g,",")
                                      .split(",");
      refDivSeqID = context[0].substring(3);//!!!!Caution!!!!!  class order dependency
      //setup data
      splittokendata={
        ednID: this.edition.id,
        context: context,
        insPos: splitPos
      };
      if (DEBUG.healthLogOn) {
        splittokendata['hlthLog'] = 1;
      }

      DEBUG.log("gen","call to splitToken for tokID "+ sclEd.getTokenID() +
                  " at position " + splittokendata.insPos +
                  " in text division sequence id " + splittokendata.refDivSeqID +
                  " with context of '"+splittokendata.context +"'");
      DEBUG.log("data","before splitToken sequence dump\n" + DEBUG.dumpSeqData(refDivSeqID,0,1,ednVE.lookup.gra));
//      return;
      //call service with ednID, ord position and line label
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/splitToken.php?db='+dbName,
//          data: 'data='+JSON.stringify(splittokendata),
          data: splittokendata,
          asynch: true,
          success: function (data, status, xhr) {
              var oldSeqIDTag, newSegIDTag;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data);
                //update grapheme lookups for token changes
                for (entGID in data.alteredTextDivComponentGIDs) {
                  //calc render mapping for new token with walkTokenContext
                  ednVE.walkTokenContext(data.alteredTextDivComponentGIDs[entGID], 'seq'+refDivSeqID, true, null);
                }
                //if text div has a new id update all nodes with the new id
                if (data.alteredTextDivSeqID) {
                  oldSeqIDTag = 'seq' + refDivSeqID;
                  newSegIDTag ='seq' + data.alteredTextDivSeqID;
                  $('.seq'+refDivSeqID,ednVE.contentDiv).each(function(hindex,elem){
                              //update seqID
                              elem.className = elem.className.replace(oldSeqIDTag,newSegIDTag);
                  });
                  ednVE.calcTextDivGraphemeLookups(data.alteredTextDivSeqID);
                  DEBUG.log("data","after splitToken sequence dump \n" + DEBUG.dumpSeqData(data.alteredTextDivSeqID,0,1,ednVE.lookup.gra));
                } else {
                  ednVE.calcTextDivGraphemeLookups(refDivSeqID);
                  DEBUG.log("data","after splitToken sequence dump \n" + DEBUG.dumpSeqData(refDivSeqID,0,1,ednVE.lookup.gra));
                }

                // calcLineGraphemeLookups for new line ? needed?
                ednVE.calcLineGraphemeLookups(physLineSeqID);
                //redraw line
                ednVE.reRenderPhysLine(lineOrdTag,physLineSeqID);
                if (crosslineWord) {
                  // calcLineGraphemeLookups for new line ? needed?
                  ednVE.calcLineGraphemeLookups(adjPhysLineSeqID);
                  //redraw line
                  ednVE.reRenderPhysLine(adjLineOrdTag,adjPhysLineSeqID);
                }
                //position sclED on new syllable
                if (typeof sclEd != "undefined") {
                  newTokGID = data.alteredTextDivComponentGIDs[-1+data.alteredTextDivComponentGIDs.length];//caution!!!! service return data order dependency
                  sclIDs = entities.tok[newTokGID.substring(4)].syllableClusterIDs;
                  refSclID = sclIDs[0];
                  sclEd.moveToSyllable(refSclID,'caretAtStart');
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Split Token***");
                  DEBUG.log("health","Params: "+JSON.stringify(splittokendata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['errors']) {
                alert("An error occurred while trying to save a syllable record. Error: " + data['errors'].join(" : "));
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save Syllable. Error: " + error;
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
          }
      });// end ajax
    }
  },


/**
* tag entity with current tag id
*
* @param string entGID Entity global id (prefix:id)
*/

  tagEntity: function (entGID) {
    var ednVE = this, savedata = {};
    //setup data
    savedata={
      entGID: entGID,
      tagAddToGIDs: [ednVE.curTagID],
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
            ednVE.dataMgr.updateLocalCache(data);
            ednVE.propMgr.showVE();
            ednVE.refreshTagMarkers();
          }
          if (data.editionHealth) {
            DEBUG.log("health","***Tag Entity***");
            DEBUG.log("health","Params: "+JSON.stringify(savedata));
            DEBUG.log("health",data.editionHealth);
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
* split line at the current location
*
* @param function cbError Error callback function\n*/

  splitLine: function (cbError) {
    var ednVE = this, sclEd = this.sclEd, startNode, prevNode, headerNode, grdCnt = 500,
        context,refDivSeqID, grpClass = "",hdrClass = "", sclIDs,
        refSclID, newTokGID, physLineSeqID, lineOrdTag, isLastLine, splittokendata;
    if (sclEd) {
      context = sclEd.syllable[0].className.replace(/grpGra/,"")
                                      .replace(/ord\d+/,"")
                                      .replace(/ordL\d+/,"")
                                      .replace(/seg\d+/,"")
                                      .replace(/prepadding/,"")
                                      .replace(/firstLine/,"")
                                      .replace(/lastLine/,"")
                                      .replace(/selected/,"")
                                      .trim()
                                      .replace(/\s+/g,",")
                                      .split(",");
      lineOrdTag = sclEd.syllable[0].className.match(/ordL\d+/)[0];
      headerNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv);
      physLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
      refDivSeqID = context[0].substring(3);//!!!!Caution!!!!!  class order dependency
      //setup data
      splitlinedata={
        ednID: this.edition.id,
        context: context,
        sclID: sclEd.sclID,
        seqID: physLineSeqID,
        splitAfter: sclEd.caretAtBoundary('right')
      };
      if (DEBUG.healthLogOn) {
        splitlinedata['hlthLog'] = 1;
      }
      DEBUG.log("gen","call to splitLine for seqID "+ physLineSeqID +
                  " at syllable " + sclEd.sclID +
                  " with split " + (sclEd.caretAtBoundary('right')?'after':'before') +
                  " and with a context of '"+splitlinedata.context +"'");
      DEBUG.log("data","before splitLine sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before splitLine sequence dump\n" + DEBUG.dumpSeqData(physLineSeqID,0,1,ednVE.lookup.gra));
      //call service with ednID, ord position and line label
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/splitLine.php?db='+dbName,
          data: splitlinedata,
          asynch: true,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                if (data['newPhysSeqID']) {
                  ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
                }
                DEBUG.log("data","after splitLine sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
                if (data['physLineSeqID']) {
                  DEBUG.log("data","after splitLine sequence dump\n" + DEBUG.dumpSeqData(data['physLineSeqID'],0,1,ednVE.lookup.gra));
                }
                if (data['newPhysLineSeqID']) {
                  DEBUG.log("data","after splitLine sequence dump\n" + DEBUG.dumpSeqData(data['newPhysLineSeqID'],0,1,ednVE.lookup.gra));
                }
                //temporary redraw all
                ednVE.calcEditionRenderMappings();
                ednVE.renderEdition();
                //position sylED on original syllable
                sclEd.moveToSyllable(splitlinedata.sclID,splitlinedata.splitAfter ? 'caretAtEnd':'caretAtStart');
                if (data.editionHealth) {
                  DEBUG.log("health","***Split Line***");
                  DEBUG.log("health","Params: "+JSON.stringify(splitlinedata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['errors']) {
                errStr = "Error while trying to splitLine. Error: " + data['errors'].join();
                alert(errStr);
                DEBUG.log("err",errStr);
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "Error while trying to splitLine. Error: " + error;
              DEBUG.log("err",errStr);
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
          }
      });// end ajax
    }
  },


/**
* merge line with next or previous line
*
* @param string direction Indicates direction of merge prev or next
* @param function cbError Error callback function\n*/

mergeLine: function (direction,cbError) {
    var ednVE = this, sclEd = this.sclEd, startNode, prevNode, headerNode,
        context,refDivSeqID, grpClass = "",hdrClass = "", sclIDs,
        refSclID, newTokGID, physLineSeqID, physLineSeqID2, lineOrd1, lineOrd2, isLastLine, mergelinedata;
    if (sclEd) {
      if (!confirm("Preparing to merge 2 lines, hit cancel to abort.")){
        return;
      }
      lineOrd1 = sclEd.syllable[0].className.match(/ordL(\d+)/)[1];
      if (direction == 'prev') {
        lineOrd2 = parseInt(lineOrd1);
        lineOrd1 = -1 + lineOrd2;
      } else {
        lineOrd1 = parseInt(lineOrd1);
        lineOrd2 = 1 + lineOrd1;
      }
      headerNode = $('.textDivHeader.ordL'+lineOrd1,ednVE.contentDiv);
      physLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
      headerNode = $('.textDivHeader.ordL'+lineOrd2,ednVE.contentDiv);
      physLineSeqID2 = headerNode.attr('class').match(/lineseq(\d+)/)[1];
      //setup data
      mergelinedata = {
        ednID: this.edition.id,
        line1: physLineSeqID,
        line2: physLineSeqID2
      };
      if (DEBUG.healthLogOn) {
        mergelinedata['hlthLog'] = 1;
      }
      DEBUG.log("gen","call to mergeLine for seqID "+ physLineSeqID +
                  " at syllable " + sclEd.sclID +
                  " with merge " + (sclEd.caretAtBoundary('right')?'next':'previous') +
                  " and with a context of '"+mergelinedata.context +"'");
      DEBUG.log("data","before mergeLine sequence dump\n" + DEBUG.dumpSeqData(physLineSeqID,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before mergeLine sequence dump\n" + DEBUG.dumpSeqData(physLineSeqID2,0,1,ednVE.lookup.gra));
//      return;
      //call service with ednID, ord position and line label
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/mergeLine.php?db='+dbName,
          data: mergelinedata,
          asynch: true,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                if (data['newPhysSeqID']) {
                  ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
                }
                DEBUG.log("data","after mergeLine sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
                if (data['physLineSeqID']) {
                  DEBUG.log("data","after mergeLine sequence dump\n" + DEBUG.dumpSeqData(data['physLineSeqID'],0,1,ednVE.lookup.gra));
                }
                //temporary redraw all
                ednVE.calcEditionRenderMappings();
                ednVE.renderEdition();
                //position sylED on original syllable
                sclEd.moveToSyllable(sclEd.sclID,direction == 'next' ? 'caretAtEnd':'caretAtStart');
                if (data.editionHealth) {
                  DEBUG.log("health","***Merge Line***");
                  DEBUG.log("health","Params: "+JSON.stringify(mergelinedata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['error']) {
                alert("An error occurred while trying to save a syllable record. Error: " + data['error']);
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save Syllable. Error: " + error;
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
          }
      });// end ajax
    }
  },


/**
* create compound with the current token splitting at the current location
*
* @param function cbError Error callback function\n*/

  createCompound: function (cbError) {
    var ednVE = this, sclEd = this.sclEd, startNode, prevNode, headerNode, grdCnt = 500, refreshCmpTag,
        context,refDivSeqID, grpClass = "",hdrClass = "", sclIDs, bndryVal = sclEd.getCurTokenBoundary(),
        refSclID, newTokID, physLineSeqID, adjPhysLineSeqID, adjPhysLineSeqIDs = [], trgTokTag, $tokNodes,
        curLineOrdTag, lineOrdTag, adjLineOrdTag, isLastLine, createcompounddata;
    if (sclEd && sclEd.syllable && sclEd.syllable.length && bndryVal == 0) {
      context = sclEd.syllable[0].className.replace(/grpGra/,"")
                                      .replace(/ord\d+/,"")
                                      .replace(/ordL\d+/,"")
                                      .replace(/seg\d+/,"")
                                      .replace(/prepadding/,"")
                                      .replace(/firstLine/,"")
                                      .replace(/lastLine/,"")
                                      .replace(/selected/,"")
                                      .trim()
                                      .replace(/\s+/g,",")
                                      .split(",");
      if (sclEd.syllable[0].className.match(/cmp\d+/)) {
        refreshCmpTag = sclEd.syllable[0].className.match(/cmp\d+/)[0];
      }
      refSclID = sclEd.sclID;
      lineOrdTag = sclEd.syllable[0].className.match(/ordL\d+/)[0];
      headerNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv);
      physLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
      refDivSeqID = context[0].substring(3);//!!!!Caution!!!!!  class order dependency
      //setup data
      createcompounddata={
        ednID: this.edition.id,
        context: context,
        insPos: sclEd.getTokenCurPos()
      };
      if (sclEd.syllable[0].className.match(/tok\d+/)) {
        trgTokTag = sclEd.syllable[0].className.match(/tok\d+/)[0];
        $tokNodes = $('.grpGra.'+trgTokTag,ednVE.contentDiv);
        adjPhysLineSeqIDs = $tokNodes.map(function(index,elem)  {
            tempOrdTag = elem.className.match(/ordL\d+/)[0];
            if (tempOrdTag == curLineOrdTag || tempOrdTag == lineOrdTag) {
              return null;
            }
            curLineOrdTag = tempOrdTag;
            headerNode = $('.textDivHeader.'+curLineOrdTag,ednVE.contentDiv);
            if (headerNode && headerNode.length) {
              adjPhysLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
            }
            return [[curLineOrdTag,adjPhysLineSeqID]];
          });
      }
/*      if (sclEd.caretAtBOL()) {//find previous line seq ID
        adjLineOrdTag = 'ordL'+ (parseInt(lineOrdTag.substring(4))-1);
        headerNode = $('.textDivHeader.'+adjLineOrdTag,ednVE.contentDiv);
        if (headerNode && headerNode.length) {
          adjPhysLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
        }
      } else if (sclEd.caretAtEOL()){//find following line seq ID
        adjLineOrdTag = 'ordL'+ (parseInt(lineOrdTag.substring(4))+1);
        headerNode = $('.textDivHeader.'+adjLineOrdTag,ednVE.contentDiv);
        if (headerNode && headerNode.length) {
          adjPhysLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
        }
      }
      DEBUG.log("err","physLine id = "+physLineSeqID+", adjPhysLine id = "+adjPhysLineSeqID);
      */
      if (DEBUG.healthLogOn) {
        createcompounddata['hlthLog'] = 1;
      }
      DEBUG.log("gen","call to create Compound for tokID "+ sclEd.getTokenID() +
                  " at position " + createcompounddata.insPos +
                  " in text division sequence id " + createcompounddata.refDivSeqID +
                  " with context of '"+createcompounddata.context.join(' ') +"'");
      DEBUG.log("data","before createCompound dump sequence \n" + DEBUG.dumpSeqData(refDivSeqID,0,1,ednVE.lookup.gra));
      //call service with ednID, ord position and line label
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/createCompound.php?db='+dbName,
          data: createcompounddata,
          asynch: true,
          success: function (data, status, xhr) {
              var oldSeqIDTag, newSeqIDTag,i;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                //update grapheme lookups for token changes
                //if text div has a new id update all nodes with the new id
                if (data.alteredTextDivSeqID) {
                  oldSeqIDTag = 'seq' + refDivSeqID;
                  newSeqIDTag ='seq' + data.alteredTextDivSeqID;
                  $('.seq'+refDivSeqID,ednVE.contentDiv).each(function(hindex,elem){
                              //update seqID
                              elem.className = elem.className.replace(oldSeqIDTag,newSeqIDTag);
                  });
                  ednVE.calcTextDivGraphemeLookups(data.alteredTextDivSeqID);
                  DEBUG.log("data","after createCompound dump sequence \n" + DEBUG.dumpSeqData(data.alteredTextDivSeqID,0,1,ednVE.lookup.gra));
                } else {
                  ednVE.calcTextDivGraphemeLookups(refDivSeqID);
                  DEBUG.log("data","after createCompound dump sequence \n" + DEBUG.dumpSeqData(refDivSeqID,0,1,ednVE.lookup.gra));
                }
                // calcLineGraphemeLookups
                ednVE.calcLineGraphemeLookups(physLineSeqID);
                //redraw line
                ednVE.reRenderPhysLine(lineOrdTag,physLineSeqID);
                if (adjPhysLineSeqIDs && adjPhysLineSeqIDs.length > 0) {
                  for (i=0; i < adjPhysLineSeqIDs.length; i++) {
                    adjLineOrdTag = adjPhysLineSeqIDs[i][0];
                    adjPhysLineSeqID = adjPhysLineSeqIDs[i][1];
                    if (adjLineOrdTag && adjPhysLineSeqID) {
                      // calcLineGraphemeLookups
                      ednVE.calcLineGraphemeLookups(adjPhysLineSeqID);
                      //redraw line
                      ednVE.reRenderPhysLine(adjLineOrdTag,adjPhysLineSeqID);
                    }
                  }
                }
                ednVE.refreshSeqMarkers();
                //position sylED on new syllable
                if (typeof sclEd != "undefined") {
                  sclIDs = [];
                  if (data.entities.update && data.entities.update.tok) {//split an owned token
                    for (tokID in data.entities.update.tok) {
                      if (data.entities.update.tok[tokID].syllableClusterIDs) {
                        sclIDs = sclIDs.concat(data.entities.update.tok[tokID].syllableClusterIDs);
                      }
                    }
                  }
                  if (data.entities.insert && data.entities.insert.tok) {//split an owned token
                    for (tokID in data.entities.insert.tok) {
                      if (data.entities.insert.tok[tokID].syllableClusterIDs) {
                        sclIDs = sclIDs.concat(data.entities.insert.tok[tokID].syllableClusterIDs);
                      }
                    }
                  }
                  if (sclIDs && sclIDs.length && (!refSclID || sclIDs.indexOf(refSclID) == -1)) {
                    sclEd.moveToSyllable(sclIDs[0],'caretAtStart');
                  } else {
                    sclEd.init(sclEd.syllable);
                  }
                }
                if (ednVE.propMgr && ednVE.propMgr.setEntity) {
                  if (!refreshCmpTag && data.entities.insert && data.entities.insert.cmp) {
                    for (cmpID in data.entities.insert.cmp) {
                      refreshCmpTag = "cmp"+cmpID;
                      break;
                    }
                  }
                  if (refreshCmpTag) {
                    ednVE.propMgr.setEntity(refreshCmpTag);
                  }
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Create Compound***");
                  DEBUG.log("health","Params: "+JSON.stringify(createcompounddata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['error']) {
                alert("An error occurred while trying to save a syllable record. Error: " + data['error']);
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save Syllable. Error: " + error;
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
          }
      });// end ajax
    } else {
      //invalid call to create compound
      UTILITY.beep();
      if (bndryVal < 0) {//at beginning of token get prev compound or token to combine with
        DEBUG.log("err"," createCompound at token boundary with previous token underconstruction");
        //if compound and token is not first or if token at beginning of edition then beep
        //else find prev entity and add to data
      } else if (bndryVal > 0) {//at end of token get next compound or token to combine with
        DEBUG.log("err","createCompound at token boundary with following token underconstruction");
        //if compound and token is not last or if token at end of edition then beep
        //else find prev entity and add to data
      } else {
        DEBUG.log("err"," invalid call to createCompound, either no syllable editor found, cursor not at syllable boundary or editor on invalid syllable");
      }
    }
  },


/**
* combine tokens around current boundary
*
* @param string direction Indicates direction of merge prev or next
* @param function cbError Error callback function\n*/

  combineTokens: function (direction,cbError) {
    var ednVE = this, sclEd = this.sclEd, startNode, prevNode, headerNode, grdCnt = 500,
        sylIsSplit = sclEd.isSplitSyllable(), i, context, className,
        grpClass = "",hdrClass = "", sclIDs, sylOrd, sylNode2, context2,txtDivSeqID,
        refSclID, newTokID, physLineSeqID, physLineSeqID2, lineOrdTag, lineOrdTag2,
        isLastLine, createcompounddata;
    if (sclEd) {
      //test if valid call for non split syllable
      //     direction prev has cursor index = first and pos zero
      //     direction next has cursor index = last (note single is same as first) and pos length
      // if split then syllable elements > 1
      //     direction prev has cursor index != first and pos zero
      //     direction next has cursor index != last and pos equal element length
      if ((!sylIsSplit &&
            ((direction == 'prev' && sclEd.cursorIndex == 0 && sclEd.cursorNodePos == 0)||
             (direction == 'next' && sclEd.cursorIndex == sclEd.syllable.length -1
                  && sclEd.cursorNodePos == $(sclEd.syllable[sclEd.cursorIndex]).text().length))) ||
          (sylIsSplit &&
            ((direction == 'prev' && sclEd.cursorIndex != 0 && sclEd.cursorNodePos == 0)||
             (direction == 'next' && sclEd.cursorIndex != sclEd.syllable.length -1
                  && sclEd.cursorNodePos == $(sclEd.syllable[sclEd.cursorIndex]).text().length)))) {
        className = sclEd.syllable[sclEd.cursorIndex].className;
        context = className.replace(/grpGra/,"")
                           .replace(/ord\d+/,"")
                           .replace(/ordL\d+/,"")
                           .replace(/seg\d+/,"")
                           .replace(/scl\d+/,"")
                           .replace(/prepadding/,"")
                           .replace(/firstLine/,"")
                           .replace(/lastLine/,"")
                           .replace(/selected/,"")
                           .trim()
                           .replace(/\s+/g,",")
                           .split(",");
        lineOrdTag = sclEd.syllable[0].className.match(/ordL\d+/)[0];
        headerNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv);
        physLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
        sylOrd = className.match(/ord(\d+)/)[1];
        sylNode2 = $('.grpGra.ord' + (direction == 'prev'? (parseInt(sylOrd)-1) : (parseInt(sylOrd)+1)),ednVE.contentDiv).get(0);
        context2 = sylNode2.className.replace(/grpGra/,"")
                                        .replace(/ord\d+/,"")
                                        .replace(/ordL\d+/,"")
                                        .replace(/seg\d+/,"")
                                        .replace(/scl\d+/,"")
                                        .replace(/prepadding/,"")
                                        .replace(/firstLine/,"")
                                        .replace(/lastLine/,"")
                                        .replace(/selected/,"")
                                        .trim()
                                        .replace(/\s+/g,",")
                                        .split(",");
        lineOrdTag2 = sylNode2.className.match(/ordL\d+/)[0];
        headerNode = $('.textDivHeader.'+lineOrdTag2,ednVE.contentDiv);
        physLineSeqID2 = headerNode.attr('class').match(/lineseq(\d+)/)[1];
        refSclID = sclEd.sclID;
        context = (direction == 'prev'? [context2,context] : [context,context2]);
        txtDivSeqID = context[0][0].substring(3);
        //setup data
        combinetokendata={
          ednID: this.edition.id,
          context: context
        };
        if (DEBUG.healthLogOn) {
          combinetokendata['hlthLog'] = 1;
        }
        DEBUG.log("gen","call to combineTokens cursor on token "+ sclEd.getTokenID() +
                    " with context1 of '"+combinetokendata.context[0].join(' ') +
                    "' and context2 of '"+combinetokendata.context[1].join(' ') +"'");
        DEBUG.log("data","before combineTokens dump sequence \n" + DEBUG.dumpSeqData(txtDivSeqID,0,1,ednVE.lookup.gra));
        //return;
        //call service with ednID, ord position and line label
        $.ajax({
            type:"POST",
            dataType: 'json',
            url: basepath+'/services/combineTokens.php?db='+dbName,
            data: 'data='+JSON.stringify(combinetokendata),
            asynch: true,
            success: function (data, status, xhr) {
                var oldSeqIDTag, newSeqIDTag, seqID, i, updatedSeq;
                if (typeof data == 'object' && data.success && data.entities) {
                  //update data
                  ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                  //update grapheme lookups for token changes
                  //if text div has a new id update all nodes with the new id
                  if (data.alteredTextDivSeqIDs) {
                    for (i in data.alteredTextDivSeqIDs) {
                      oldSeqIDTag = 'seq' + i;
                      newSeqIDTag ='seq' + data.alteredTextDivSeqIDs[i];
                      $('.'+oldSeqIDTag,ednVE.contentDiv).each(function(hindex,elem){
                                  //update seqID
                                  elem.className = elem.className.replace(oldSeqIDTag,newSeqIDTag);
                      });
                      ednVE.calcTextDivGraphemeLookups(data.alteredTextDivSeqIDs[i]);
                      DEBUG.log("data","after combineTokens dump sequence \n" + DEBUG.dumpSeqData(data.alteredTextDivSeqIDs[i],0,1,ednVE.lookup.gra));
                    }
                  }
                  //recalc any updated text div sequences
                  if (data.entities.update) {
                    if (data.entities.update.seq) {
                      for (seqID in data.entities.update.seq) {
                        updatedSeq = entities.seq[seqID];
                        if ( ednVE.trmIDtoLabel[updatedSeq.typeID] == "TextDivision") {
                          ednVE.calcTextDivGraphemeLookups(seqID);
                          DEBUG.log("data","after combineTokens dump sequence \n" + DEBUG.dumpSeqData(seqID,0,1,ednVE.lookup.gra));
                        }
                      }
                    }
                  }
                  if (txtDivSeqID) {
                    ednVE.calcTextDivGraphemeLookups(txtDivSeqID);
                    DEBUG.log("data","after combineTokens dump sequence \n" + DEBUG.dumpSeqData(txtDivSeqID,0,1,ednVE.lookup.gra));
                  }
                  // calcLineGraphemeLookups
                  ednVE.calcLineGraphemeLookups(physLineSeqID);
                  //redraw line
                  ednVE.reRenderPhysLine(lineOrdTag,physLineSeqID);
                  if(physLineSeqID2 && physLineSeqID2 != physLineSeqID) {
                    // calcLineGraphemeLookups
                    ednVE.calcLineGraphemeLookups(physLineSeqID2);
                    //redraw line
                    ednVE.reRenderPhysLine(lineOrdTag2,physLineSeqID2);
                  }
                  //position sylED on new syllable
                  if (typeof sclEd != "undefined") {
                    sclEd.moveToSyllable(refSclID,(direction == 'prev'?'caretAtStart':'caretAtEnd'));
                  }
                  if (data.displayEntGID) { //display entity has change to a new entity
                    ednVE.propMgr.showVE("entPropVE",data.displayEntGID);
                  }
                }else if (data['errors']) {
                  alert("An error occurred while trying to save a syllable record. Error: " + data['errors'].join(" : "));
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Combine Token***");
                  DEBUG.log("health","Params: "+JSON.stringify(combinetokendata));
                  DEBUG.log("health",data.editionHealth);
                }
            },// end success cb
            error: function (xhr,status,error) {
                // add record failed.
                errStr = "An error occurred while trying to call save Syllable. Error: " + error;
                if (cbError) {
                  cbError(errStr);
                } else {
                  alert(errStr);
                }
            }
        });// end ajax
      }
    } else { // invalid call to combine tokens

    }
  },


/**
* insert freetext line before or after the current line dependent on position
*
* @param function cbError Error callback function\n*/

  insertFreeTextLine: function (cbError) {
    var ednVE = this,sel,range, startNode, prevNode, headerNode, grdCnt = 500,
        context,refDivSeqID,refEntGID,grpClass = "",hdrClass = "", newLineHTML = "",
        newSclID, newTokGID, newTextDivSeqGID,newPhysLineSeqID,newLineOrd,refLineSeqGID,
        insertdata, refTokID, posInsert = 'after', beforeFirst = false;
    if (window.getSelection) {
      sel = window.getSelection();
      if (sel.getRangeAt && sel.rangeCount) {
        range = eRange = sel.getRangeAt(0);
      }
    } else if (document.selection && document.selection.createRange) {
      range = document.selection.createRange();
    }
    //find cursor position and line
    if (range.startContainer.nodeName == "#text") {// text click case (usual case)
      startNode = range.startContainer.parentNode;
    }else{
      startNode = range.startContainer;
    }
    //find header node
    switch (startNode.nodeName) {
      case 'DIV':
        if (startNode.className.match(/freetext/)) {
          headerNode = $(startNode).prevUntil('br','.textDivHeader').first();
          break;
        }
      case 'H3':
        // select first header and direction before (default)
        headerNode = $(ednVE.contentDiv).find(".textDivHeader:first");
        break;
      case 'SPAN':
        if (startNode.className.match(/textDivHeader/)) {
          //at header set dir before
          headerNode = $(startNode);
        } else if (startNode.className.match(/grpGra|boundary|linebreak|TCM/)){ //somewhere on line so move left to find header
          prevNode = $(startNode).prev();// !!assume caret at start means prev node is line header
          if (prevNode.hasClass('TCM')) {//skip TCMs
            prevNode = prevNode.prev();
          }
          if (prevNode.hasClass('textDivHeader')) {//on first syllable of line
            posInsert = 'before';
          }
          while (prevNode.length && !prevNode.hasClass('textDivHeader') && grdCnt--) {
            if (prevNode.hasClass('grpGra')) {// found not at first syllable start group
              posInsert = 'after';
            }
            prevNode = prevNode.prev();
          }
          if (grdCnt) {
            headerNode = prevNode;
          }
        }
        break;
      default:
        DEBUG.log("gen","insertFreeTextLine not able to detect position for "+this.edition.value);
        return;
    }
    if (!headerNode) {
        DEBUG.log("gen","insertFreeTextLine not able to detect position for "+this.edition.value);
        return;
    } else {
      if (posInsert == "before") {
        //if not startHeader then check for split token on previous linebreak
        if (!headerNode.hasClass('startHeader') && headerNode.prevUntil('.grpGra','.linebreak').attr('class').match(/split/)) {
          alert("Inserting line between lines sharing a token is not allowed. Please split the last token of previous line first.");
          return;
        }
      } else {
        //if not endHeader then check for cross line token on this lines linebreak
        if (!headerNode.hasClass('endHeader') && headerNode.nextUntil('br','.linebreak').attr('class').match(/split/)) {
          alert("Inserting line between lines sharing a token is not allowed. Please split the last token of this line first.");
          return;
        }
      }
      refLineSeqGID = ((headerNode.attr('class')).match(/line(seq\d+)/)[1]).replace(/(.{3})(.*)/,"$1:$2");
    }
    //setup data
    insertdata={
      ednID: this.edition.id,
      //label:"newline 1",
      initText:"+ + + + +///",
      insPos: posInsert,
      refLineSeqGID: refLineSeqGID
    };
    if (DEBUG.healthLogOn) {
      insertdata['hlthLog'] = 1;
    }
    DEBUG.log("gen","call to insertFreeTextLine "+ insertdata.insPos +
                " line sequence id "+insertdata.refLineSeqID);
    if (this.textSeq && this.lookup && this.lookup.gra) {
      DEBUG.log("data","before insertLine sequence dump\n" + DEBUG.dumpSeqData(this.textSeq.id,0,1,ednVE.lookup.gra));
    }
    //call service with ednID, ord position and line label
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: basepath+'/services/insertFreeTextLine.php?db='+dbName,
        data: insertdata,
        asynch: true,
        success: function (data, status, xhr) {
          var freetextLine;
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
              //find new syllable id
              newLineOrd = data['newLineOrd'];
              freeTextLineSeqID = (data['freeTextLineSeqGID']).substring(4);
              if (data['newPhysSeqID']) {
                ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
              }
              DEBUG.log("data","after insertLine sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
              DEBUG.log("data","after insertLine sequence dump\n" + DEBUG.dumpSeqData(freeTextLineSeqID,0,1,ednVE.lookup.gra));
              //calc html for newline
              newLineHTML = ednVE.renderPhysicalLine(freeTextLineSeqID,null,null,1,newLineOrd);
              //insert line  and renumber
              ednVE.renderNewLine(newLineHTML[0],newLineOrd,data['lastReplaced']);
              //attach event handlers
              ednVE.addEventHandlers();
              //trigger click for editting new freetext.
              freetextLine = $('div.freetext.lineseq'+freeTextLineSeqID,ednVE.editDiv);
              ednVE.createEditFreeTextLineUI(freetextLine);
              if (data.editionHealth) {
                DEBUG.log("health","***Insert Free Text Line***");
                DEBUG.log("health","Params: "+JSON.stringify(insertdata));
                DEBUG.log("health",data.editionHealth);
              }
            }else if (data['error']) {
              alert("An error occurred while trying to insert a new line. Error: " + data['error']);
            }
        },// end success cb
        error: function (xhr,status,error) {
            // add record failed.
            errStr = "An error occurred while trying to insert a new line. Error: " + error;
            if (cbError) {
              cbError(errStr);
            } else {
              alert(errStr);
            }
        }
    });// end ajax
  },


/**
* delete current line
*
* @param function cbError Error callback function\n
*/

  deleteLine: function (cbError) {
    var ednVE = this,sel,range, startNode, prevNode, headerNode, grdCnt = 500,prevCtx,
        context,grpClass = "",hdrClass = "", endNode,
        delLineOrd,delLineSeqGID, classes, trgHeaderNode, trgFreetextNode, trgSclID,
        deletedata, refTokID, posInsert = 'before', beforeFirst = false;
    if (window.getSelection) {
      sel = window.getSelection();
      if (sel.getRangeAt && sel.rangeCount) {
        range = eRange = sel.getRangeAt(0);
      }
    } else if (document.selection && document.selection.createRange) {
      range = document.selection.createRange();
    }
    //find cursor position and line
    if (range.startContainer.nodeName == "#text") {// text click case (usual case)
      startNode = range.startContainer.parentNode;
    }else{
      startNode = range.startContainer;
    }
    //find cursor position and line
    if (range.endContainer.nodeName == "#text") {// text click case (usual case)
      endNode = range.endContainer.parentNode;
    }else{
      endNode = range.endContainer;
    }
    //find header node
    switch (startNode.nodeName) {
      case 'DIV':
        if (startNode.className.match(/freetext/)) {
          headerNode = $(startNode).prevUntil('br','.textDivHeader').first();
          break;
        } else if (range.startOffset) {
          headerNode = $(startNode.childNodes[range.startOffset]);
          if (!headerNode.hasClass('textDivHeader') && headerNode.hasClass('grpGra')) {
            headerNode = headerNode.prevUntil('br','.textDivHeader').first();
          }
          break;
        }
      case 'H3':
        // select first header and direction before (default)
        headerNode = $(ednVE.contentDiv).find(".textDivHeader:first");
        break;
      case 'SPAN':
        if (startNode.className.match(/textDivHeader/)) {
          //at header set dir before
          headerNode = $(startNode);
        } else if (startNode.className.match(/grpGra|boundary|linebreak|TCM/)){ //somewhere on line so move left to find header
          prevNode = $(startNode).prev();// !!assume caret at start means prev node is line header
          while (prevNode.length && !prevNode.hasClass('textDivHeader') && grdCnt--) {
            if (prevNode.hasClass('grpGra')) {// found not at first syllable start group
              posInsert = 'after';
            }
            prevNode = prevNode.prev();
          }
          if (grdCnt) {
            headerNode = prevNode;
          }
        }
        break;
      default:
        DEBUG.log("warn","deleteLine not able to detect header for "+this.edition.value);
        return;
    }
    if (!headerNode) {
        DEBUG.log("warn","deleteLine not able to detect header for "+this.edition.value);
        return;
    } else {
      //check for cross line tokens
      //if not startHeader then check for split token on previous linebreak
      if (!headerNode.hasClass('startHeader') &&
          headerNode.prevUntil('.grpGra','.linebreak').attr('class').match(/split/)) {
        alert("Deleting line that sharing a token with another line is not allowed. Please split the last token of previous line before deleting.");
        return;
      }
      //check for split token on next linebreak
      if (!headerNode.hasClass('endHeader') &&
          headerNode.nextUntil('br','.linebreak').attr('class').match(/split/)) {
        alert("Deleting line that sharing a token with another line is not allowed. Please split the last token of this line before deleting.");
        return;
      }
      classes = headerNode.attr('class');
      delLineOrd = classes.match(/ordL(\d+)/)[1];
      //find target to set focus on after delete line
      if (headerNode.hasClass('endHeader')) { //find previous line Header
        trgHeaderNode = $(".textDivHeader.ordL"+(-1 + parseInt(delLineOrd)),ednVE.contentDiv);
      } else {
        trgHeaderNode = $(".textDivHeader.ordL"+(1 + parseInt(delLineOrd)),ednVE.contentDiv);
      }
      if (trgHeaderNode.length) {
        if (trgHeaderNode.hasClass('freetext')) {
          trgFreetextNode = trgHeaderNode.nextUntil('.linebreak','.freetext');
        } else {
          trgSclID = (trgHeaderNode.nextUntil('.linebreak','.grpGra').first().attr('class')).match(/scl(\d+)/)[1];
        }
      } else {
        delete trgHeaderNode;
      }

      context = {};
      delLineSeqGID = (classes.match(/line(seq\d+)/)[1]).replace(/(.{3})(.*)/,"$1:$2");
      $('span.ordL'+delLineOrd+':not(.textDivHeader,.boundary,.linebreak,.TCM)',ednVE.contentDiv).
                        each(function(index,elem){
                              cntx = elem.className.replace(/grpGra/,"")
                                                  .replace(/ord\d+/,"")
                                                  .replace(/seg\d+/,"")
                                                  .replace(/ordL\d+/,"")
                                                  .replace(/scl\d+/,"")
                                                  .replace(/prepadding/,"")
                                                  .replace(/firstLine/,"")
                                                  .replace(/lastLine/,"")
                                                  .replace(/selected/,"")
                                                  .trim()
                                                  .replace(/\s/g,",");
                              if (prevCtx != cntx) {
                                prevCtx = cntx;
                                tagIDs = cntx.split(",");
                                seqGID = tagIDs[0].substring(0,3)+":"+tagIDs[0].substring(3);
                                entGID = tagIDs[1].substring(0,3)+":"+tagIDs[1].substring(3);
                                if (context[seqGID]) {
                                  if (context[seqGID].indexOf(entGID) == -1) {// doesn't exist so push it
                                    context[seqGID].push(entGID);
                                  }
                                } else {
                                  context[seqGID] = [entGID];
                                }
                              }
                        });
    }
    //setup data
    deletedata={
      ednID: this.edition.id,
      context:context,
      delLineSeqGID: delLineSeqGID
    };
    if (DEBUG.healthLogOn) {
      deletedata['hlthLog'] = 1;
    }
    DEBUG.log("gen","call to deleteLine with line sequence id "+deletedata.delLineSeqGID +
                " and context "+ JSON.stringify(deletedata));
    DEBUG.log("data","before deleteLine sequence dump\n" + DEBUG.dumpSeqData(this.physSeq.id,0,1,ednVE.lookup.gra));
    DEBUG.log("data","before deleteLine sequence dump\n" + DEBUG.dumpSeqData(delLineSeqGID.substring(4),0,1,ednVE.lookup.gra));
    if ( this.textSeq) {// freetext line exclude
      DEBUG.log("data","before deleteLine sequence dump\n" + DEBUG.dumpSeqData(this.textSeq.id,0,1,ednVE.lookup.gra));
    }
    //call service with ednID, ord position and line label
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: basepath+'/services/deleteLine.php?db='+dbName,
        data: deletedata,
        asynch: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
              //remove line
              ednVE.removeLine("ordL"+delLineOrd);
              //position sylED on new syllable
              if (data['newPhysSeqID']) {
                ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
              }
              if (data['newTextSeqID']) {
                ednVE.textSeq = ednVE.dataMgr.getEntity('seq',data['newTextSeqID']);
              }
              DEBUG.log("data","after deleteLine sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
              if ( this.textSeq) { // freetext line exclude
                DEBUG.log("data","after deleteLine sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
              }
              if (typeof ednVE.sclEd != "undefined" && trgSclID) {
                ednVE.sclEd.moveToSyllable(trgSclID,'caretAtStart');
              } else if (trgFreetextNode) {
                ednVE.createEditFreeTextLineUI(trgFreetextNode);
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Delete Line***");
                DEBUG.log("health","Params: "+JSON.stringify(deletedata));
                DEBUG.log("health",data.editionHealth);
              }
            }else if (data['errors']) {
              alert("An error occurred while trying to save a syllable record. Error: " + data['errors'].join());
            }
        },// end success cb
        error: function (xhr,status,error) {
            // add record failed.
            errStr = "An error occurred while trying to call save Syllable. Error: " + error;
            if (cbError) {
              cbError(errStr);
            } else {
              alert(errStr);
            }
        }
    });// end ajax
  },

  level2Prefix : {
    "syllable":"scl",
    "token":"tok",
    "compound":"cmp"
  },


/**
* get the current browser selection as entity global ids at the current selected level
*
* @returns string array | null Array of Entity global ids
*/

  getSelectionEntityGIDs: function () {
    var ednVE = this,startNode, endNode, nextNode, prevNode, selectNode, startOrd, endOrd, ord,
         grdCnt=500, curGID, lastGID, prefix = this.level2Prefix[this.layoutMgr.getEditionObjectLevel()];
    if (this.autoLinkOrdMode) {
      prefix = 'scl';
    }
    if (window.getSelection) {
      sel = window.getSelection();
      if (sel.isCollapsed) {
        return null;
      }
      if (sel.getRangeAt && sel.rangeCount) {
        range = sel.getRangeAt(0);
        if (range.startContainer.nodeName == "#text") {
          startNode = range.startContainer.parentNode;
        } else {
          startNode = range.startContainer;
        }
        range2 = sel.getRangeAt(sel.rangeCount -1);
        if (range2.endContainer.nodeName == "#text") {
          endNode = range2.endContainer.parentNode;
        } else {
          endNode = range2.endContainer;
        }
      }
    } else if (document.selection && document.selection.createRange) {
      range = document.selection.createRange();
      if (range.collapsed) {
        return null;
      }
      startNode = range.startContainer;
      endNode = range.endContainer;
    }

    if (startNode.nodeName == 'DIV' || endNode.nodeName == 'DIV') {
      return null;
    }
    if (startNode.nodeName == 'SPAN') {
      if (startNode.className.match(/textDivHeader|linebreak/)) {
        //choose first syllable on line next('.grpGra')
        nextNode = $(startNode);
        while (nextNode.length && !nextNode.hasClass('grpGra') &&
                (nextNode.prop('nodeName') == "SPAN" || nextNode.prop('nodeName') == "BR")) {
          nextNode = nextNode.next();
        }
        if (! nextNode || nextNode.length == 0) {
          return null;
        } else {
          startNode = nextNode.get(0);
        }
      } else if (!startNode.className.match(/grpGra/)) {
        if (startNode.className.match(/TCM/)) {
          selectNode = $('.grpGra.'+startNode.className.match(/scl\d+/)[0],ednVE.contentDiv);
          if (! selectNode || selectNode.length == 0) {
            return null;
          } else {
            startNode = selectNode.get(0);
          }
        } else if (startNode.className.match(/boundary/)) {
          nextNode = $(startNode);
          while (nextNode.length && !nextNode.hasClass('grpGra') &&
                (nextNode.prop('nodeName') == "SPAN" || nextNode.prop('nodeName') == "BR")) {
            nextNode = nextNode.next();
          }
          if (! nextNode || nextNode.length == 0) {
            return null;
          } else {
            startNode = nextNode.get(0);
          }
        }
      }
    }//end if SPAN startNode
    if (endNode.nodeName == 'SPAN') {
      if (endNode.className.match(/textDivHeader|linebreak/)) {
        //choose last syllable on line prev('.grpGra')
        prevNode = $(endNode);
        while (prevNode.length && !prevNode.hasClass('grpGra') && grdCnt-- &&
                (prevNode.prop('nodeName') == "SPAN" || prevNode.prop('nodeName') == "BR")) {
          prevNode = prevNode.prev();
        }
        if (! prevNode || prevNode.length == 0) {
          return null;
        } else {
          endNode = prevNode.get(0);
        }
      } else if (!endNode.className.match(/grpGra/)) {
        if (endNode.className.match(/TCM/)) {
          selectNode = $('.grpGra.'+endNode.className.match(/scl\d+/)[0],ednVE.contentDiv);
          if (! selectNode || selectNode.length == 0) {
            return null;
          } else {
            endNode = selectNode.get(0);
          }
        } else if (endNode.className.match(/boundary/)) {
          prevNode = $(endNode);
          while (prevNode.length && !prevNode.hasClass('grpGra') && grdCnt-- &&
                (prevNode.prop('nodeName') == "SPAN" || prevNode.prop('nodeName') == "BR")) {
            prevNode = prevNode.prev();
          }
          if (! prevNode || prevNode.length == 0) {
            return null;
          } else {
            endNode = prevNode.get(0);
          }
        }
      }
    }//end if SPAN endNode

    //find all unique prefix type entity GIDs between startNode and endNode
    match = startNode.className.match(/ord(\d+)/);
    if (match && match.length > 1) {
      startOrd = parseInt(match[1]);
    } else {
      return null;
    }
    match = endNode.className.match(/ord(\d+)/);
    if (match && match.length > 1) {
      endOrd = parseInt(match[1]);
    } else {
      return null;
    }
    foundGIDsLookup = {};
    selectGIDs = [];
    if (endOrd < startOrd) {//reverse selection
      ord = endOrd;
      endOrd = startOrd;
    } else {
      ord = startOrd;
    }
    selectNode = $(startNode);
    while ( ord <= endOrd) {
//      selectNode = $('.grpGra.ord'+ord,ednVE.contenDIV);
      if (selectNode && selectNode.length > 0) {
        curGID = selectNode.get(0).className.match(new RegExp(prefix+"\\d+"));
        tempPrefix = prefix;
        if ((!curGID || curGID.length == 0) && prefix == "cmp") {
          curGID = selectNode.get(0).className.match(/tok\d+/);
          tempPrefix = "tok";
        }
        if (curGID && curGID.length > 0) {
          curGID = curGID[0];
          if (!foundGIDsLookup[curGID] && curGID != lastGID) {
            foundGIDsLookup[curGID] = 1;
            curGID = curGID.replace(tempPrefix,tempPrefix+":");
            selectGIDs.push(curGID);
          }
          lastGID = curGID;
        }
      }
      if (ord == endOrd) {
        break;
      }
      selectNode = selectNode.next();
      cntTry = 5;
      while (!selectNode.hasClass('grpGra') && cntTry--) {
        selectNode = selectNode.next();
      }
      match = null;
      if (selectNode.length) {
        match = selectNode.prop('className').match(/ord(\d+)/);
      }
      if (match && match.length > 1) {
        ord = parseInt(match[1]);
      } else {
        break;
      }
    }
    if (selectGIDs.length > 0) {
      return selectGIDs;
    } else {
      return null;
    }
  },


/**
* get the current selected entity global ids at the given entity level
*
* @returns string array | null Array of Entity global ids
*/

  getSelectionOrdEntityGIDs: function (prefix) {
    var entGID = null, selectedGIDs = null, regexEntGID = new RegExp(prefix+"\\d+");
    if (this.selOrderGrpGraClasses && this.selOrderGrpGraClasses.length) {
      selectedGIDs = [];
      $(this.selOrderGrpGraClasses).each(function(index,className) {
        entGID = className.match(regexEntGID);
        if (entGID.length) {
          selectedGIDs.push(entGID[0]);
        }
      });
    }
    return selectedGIDs;
  },


/**
* calculate the full context for a grapheme for this edition
*
* @param int graID Grapheme entity id
*/

  getFullContextFromGrapheme: function (graID) {
    var grapheme = this.dataMgr.getEntity('gra',graID),
        sclID, context = null, chr, elemTxt;
    if (grapheme && grapheme.sclID){
      sclID = grapheme.sclID;
      chr = grapheme.value;
      $(".grpGra.scl"+sclID,this.editDiv).each(function(index,elem){
        elemTxt = elem.innerHTML.replace(/ï/,'i').replace(/ü/,'u');
        if (elemTxt.indexOf(chr)>-1){
          context = elem.className.replace(/grpGra/,"")
                                  .replace(/ord\d+/,"")
                                  .replace(/ordL\d+/,"")
                                  .replace(/seg\d+/,"")
                                  .replace(/prepadding/,"")
                                  .replace(/firstLine/,"")
                                  .replace(/lastLine/,"")
                                  .replace(/selected/,"")
                                  .trim()
                                  .replace(/\s+/g,",")
                                  .split(",");
        }
      });
    }
    return context;
  },


/**
* refresh property editor to show entity for the current level extracting the id from the select node classes
*
* @param  string selectNodeClasses Classes for the current selection
*/

  refreshProperty: function (selectNodeClasses) {
    var ednVE = this, entTag, objLevel = ednVE.layoutMgr.getEditionObjectLevel();
    if (objLevel == 'token') {
      entTag = selectNodeClasses.match(/tok\d+/)[0];
    } else if (objLevel == 'compound') {
      entTag = selectNodeClasses.match(/cmp\d+/) ? selectNodeClasses.match(/cmp\d+/)[0] : selectNodeClasses.match(/tok\d+/)[0];
    } else {
      entTag = selectNodeClasses.match(/scl\d+/)[0];
    }
    ednVE.propMgr.showVE(null,entTag);
  },


/**
* change level handler
*
* @param object e System event object
*/

  handleLevelCommand: function (e) {
    alert(" user set select to " + e.args.button[0].id);
    e.preventDefault();//stop dblclick
  },


/**
* supress wheel event's
*
* @param object e System event object
*/

  handleWheel: function (e) {
    e.preventDefault();//stop window scroll, for dual purpose could use CTRL key to disallow default
  },


/**
* set link entity component mode for this editor
*
* @param boolean bLinkMode Indicates linking enabled
* @param int typeID Sequence entity term type id
*/

  setComponentLinkMode: function(bLinkMode, typeID) {
    var subTypeID = this.dataMgr.seqTypeTagToList['trm'+typeID] ? this.dataMgr.seqTypeTagToList['trm'+typeID][0]:null,
        element, subType = subTypeID ? this.dataMgr.getTermFromID(subTypeID):'';
    //set link mode mode
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
* invalidate the current lines cache to ensure refresh
*
* @param node syllable Grapheme group used to reference current line
*/

  refreshLineCache: function(syllable) {
    var headerNode, seqID, refreshData;
    headerNode = $(syllable).prev();
    if (headerNode.hasClass('.textDivHeader')) {
      headerNode = $(syllable).prevUntil('.textDivHeader').last().prev();
    }
    if (headerNode && headerNode.hasClass('textDivHeader') &&
        this.edition && this.edition.id) {
      seqID = headerNode.attr('class').match(/lineseq(\d+)/);
      if (seqID && seqID.length > 1) {
        seqID = seqID[1];
        refreshData = {ednID:this.edition.id,lineSeqID:seqID};
        $.ajax({
            type:"POST",
            dataType: 'json',
            url: basepath+'/services/invalidateSeqCache.php?db='+dbName,
            data: refreshData,
            asynch: true,
            error: function (xhr,status,error) {
              // add record failed.
              errStr = "Error while trying to refresh line cache. Error: " + error;
              DEBUG.log("err",errStr);
            }
        });// end ajax
      }
    }
  },


/**
* add event handlers
*
*/

  addEventHandlers: function() {
    var ednVE = this, entities = this.dataMgr.entities;


    /**
    * handle 'enterSyllable' events
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string array selectionGIDs Global entity ids if entities hovered
    */

    function enterSyllableHandler(e,senderID, selectionGIDs) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.trace("enterSyllableHandler","recieved by "+ednVE.id+" for "+ selectionGIDs[0]);
      var i, id;
      $(".highlighted",ednVE.contentDiv).removeClass("highlighted");
      $.each(selectionGIDs, function(i,val) {
        $('.'+val,ednVE.contentDiv).addClass('highlighted');
      });
    };

    $(this.editDiv).unbind('enterSyllable').bind('enterSyllable', enterSyllableHandler);


    /**
    * handle 'leaveSyllableHandler' events
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string array selectionGIDs Global entity ids if entities hovered
    */

    function leaveSyllableHandler(e,senderID, selectionIDs) {
      if (senderID == ednVE.id) {
        return;
      }
      var i, id;
      $.each(selectionIDs, function(i,val) {
        $('.'+val,ednVE.contentDiv).removeClass('highlighted');
      });
    };

    $(this.editDiv).unbind('leaveSyllable').bind('leaveSyllable', leaveSyllableHandler);


    /**
    * handle 'annotationsLoaded' event
    *
    * @param object e System event object
    */

    function annotationsLoadedHandler(e) {
      DEBUG.log("event","Annotations loaded recieved by editionVE in "+ednVE.id);
      ednVE.refreshTagMarkers();
    };

    $(this.editDiv).unbind('annotationsLoaded').bind('annotationsLoaded', annotationsLoadedHandler);


    /**
    * change object level
    *
    * @param object e System event object
    */

    function objLevelChangedHandler(e) {
      var level;
      DEBUG.log("event","Object level changed recieved by editionVE in "+ednVE.id);
      switch (ednVE.layoutMgr.getEditionObjectLevel()) {
        case 'syllable':
          level = "Syllable";
          break;
        case 'token':
          level = "Word";
          break;
        case 'compound':
        default:
          level = "Compound";
          break;
      }
      ednVE.objLevelBtn.html(level);
    };

    $(this.editDiv).unbind('objectLevelChanged').bind('objectLevelChanged', objLevelChangedHandler);


    /**
    * handle 'linkRequest' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source element to link
    * @param boolean autoAdvance Indicates set mode to fire autoadvance linking code
    */

    function linkRequestHandler(e,senderID, linkSource, autoAdvance) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","link request recieved by editionVE in "+ednVE.id+" from "+senderID+" with source "+ linkSource + (autoAdvance?" with autoAdvance on":""));
      ednVE.linkMode = true;
      ednVE.autoLink = autoAdvance? true : false;
    };

    $(this.editDiv).unbind('linkRequest').bind('linkRequest', linkRequestHandler);


    /**
    * handle 'linkRequest' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source element to link
    * @param boolean autoAdvance Indicates set mode to fire autoadvance linking code
    */

    function linkStructRequestHandler(e,senderID, structEdnTag, linkTargetTag) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","struct link request recieved by editionVE in "+ednVE.id+" from "+senderID+" with edntion tag "+ structEdnTag +
                "linking to " + linkTargetTag);
      ednVE.structLinkMode = linkTargetTag;
      ednVE.layoutMgr.curLayout.trigger('focusin',ednVE.id);
    };

    $(this.editDiv).unbind('linkStructRequest').bind('linkStructRequest', linkStructRequestHandler);


    /**
    * handle 'structureChange' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string ednID Identifies the edition with the structural change
    */

   function structureChangeHandler(e,senderID, ednID, seqTag) {
    if (senderID == ednVE.id || ednVE.edition.id != ednID) {
      return;
    }
    DEBUG.log("event","struct change recieved by editionVE in "+ednVE.id+" from "+senderID+" for "+seqTag);
    ednVE.refreshSeqMarkers();
  };

  $(this.editDiv).unbind('structureChange').bind('structureChange', structureChangeHandler);


  /**
    * handle 'autoLinkOrdRequest' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source baseline used for segments to link
    * @param string mode Identifies the number of segments used in link mode command
    */

    function autoLinkOrdRequestHandler(e,senderID, linkSource, mode) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","autolinkOrd request recieved by editionVE in "+ednVE.id+" from "+senderID+" with source "+ linkSource);
      if (ednVE.linkMode && !ednVE.autoLink) {//left in link mode, ok to change to autolinkOrd
        ednVE.linkMode = false;
      } else if ((ednVE.linkMode && ednVE.autoLink)) {
        DEBUG.log("err","edition editor in autolink scl to seg mode aborting autolinkord request from source "+ linkSource);
        $('.editContainer').trigger('autoLinkOrdAbort',[this.id,this.autoLinkOrdBln]);
      }
      ednVE.pendingAutoLinkOrdBln = linkSource;
      ednVE.pendingAutoLinkOrdMode = (mode == 0?"default":(mode == 1?"start":(mode == 2?"startstop":"multi")));
      //if bln is linked to this text then show highlight
      $(ednVE.editDiv).addClass('autoOrdLinkTarget');
    };

    $(this.editDiv).unbind('autoLinkOrdRequest').bind('autoLinkOrdRequest', autoLinkOrdRequestHandler);


    /**
    * handle 'autoLinkOrdComplete' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source baseline used for segments to link
    * @param string linkEditionID identifies the edition being linked to
    */

    function autoLinkOrdCompleteHandler(e,senderID, linkSource, linkEditionID, linkedSclsSegID) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","auto link complete recieved by editionVE in "+ednVE.id+" from "+senderID+" with source "+ linkSource+" linkEditionID of "+linkEditionID);
      if (ednVE.autoLinkOrdMode && linkEditionID == ednVE.edition.id) {
        //in link mode and linking has been completed so cleanup and update display
        delete ednVE.autoLinkOrdMode;
        delete ednVE.autoLinkOrdBln;
        $(ednVE.editDiv).removeClass('autoOrdLinkingMode');
        ednVE.refreshSegIDLinks();
        ednVE.linkOrdBtnDiv.hide();
      } else if (ednVE.pendingAutoLinkOrdBln) {//non target editionVE, let's not keep it waiting.
        delete ednVE.pendingAutoLinkOrdBln;
        delete ednVE.pendingAutoLinkOrdMode;
      }
    };

    $(this.editDiv).unbind('autoLinkOrdComplete').bind('autoLinkOrdComplete', autoLinkOrdCompleteHandler);


    /**
    * handle 'linkAbort' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source element to link
    * @param string linkTarget Identifies the target element to link
    */

    function linkAbortHandler(e,senderID, linkSource, linkTarget) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","link abort recieved by editionVE in "+ednVE.id+" from "+senderID+" with source "+ linkSource+" and target "+linkTarget);
      ednVE.linkMode = false;
    };

    $(this.editDiv).unbind('linkAbort').bind('linkAbort', linkAbortHandler);


    /**
    * handle 'linkComplete' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source element to link
    * @param string linkTarget Identifies the target element to link
    */

    function linkCompleteHandler(e,senderID, linkSource, linkTarget) {
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","link complete recieved by editionVE in "+ednVE.id+" from "+senderID+" with source "+ linkSource+" and target "+linkTarget);
      //update syllables class for linking
      var syllable = $(".grpGra."+linkTarget,ednVE.contentDiv),
          segTag;
      if (syllable && syllable.length > 0 && // check that target is part of this editors edition
          linkSource.match(/seg\d+/)) { // make sure that we are linking from a baseline
        segTag = syllable.get(0).className.match(/seg\d+/)[0];
        syllable.removeClass(segTag);
        syllable.addClass(linkSource);
        ednVE.refreshLineCache(syllable);
      }
      ednVE.linkMode = false;
    };

    $(this.editDiv).unbind('linkComplete').bind('linkComplete', linkCompleteHandler);


    /**
    * handle 'autoLinkAdvance' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkSource Identifies the source element to link
    * @param string linkTarget Identifies the target element to link
    */

    function autoLinkAdvanceHandler(e,senderID, linkSource, linkTarget) {
      if (senderID == ednVE.id || !ednVE.autoLink) {
        return;
      }
      DEBUG.log("event","autolink advance received by editionVE in "+ednVE.id+" from "+senderID+" with source "+ linkSource+" and target "+linkTarget);
      var nodes,node,ord,nextSyllable;

      nodes = $(".grpGra."+linkSource,ednVE.contentDiv);
      if (!nodes || nodes.length == 0 ) {
        ednVE.autoLink = false;
        return;
      }
      node = nodes.get(nodes.length-1);// get last grp for this syllable
      ord = parseInt(node.className.match(/ord(\d+)/)[1]);//check ordinal of grp
      nextSyllable = $(".grpGra.ord"+(1+ord),ednVE.contentDiv).get(0);//look for grp with next ordinal

      while (!nextSyllable) {
        ord++;
        nextSyllable = $(".grpGra.ord"+ord,ednVE.contentDiv).get(0);
        node = $(".ord"+ord,ednVE.contentDiv).get(0);
        if (!node) {
          break;
        }
      }
      if (nextSyllable){
        var selectedSclLabel = nextSyllable.className.match(/scl\d+/)[0],
            nextSclText = $(".grpGra."+selectedSclLabel,ednVE.editDiv).text();
        nextSyllable = ednVE.dataMgr.getEntityFromGID(selectedSclLabel);
        if (nextSyllable && !nextSyllable.readonly &&
            confirm(" Continue linking '"+(nextSclText?nextSclText:nextSyllable.textContent)+"' to the next segment?")) {
          $(".selected",ednVE.editDiv).removeClass("selected");
          $(".grpGra."+selectedSclLabel,ednVE.editDiv).addClass("selected");
          ednVE.linkSource = selectedSclLabel;
          $('.editContainer').trigger('linkRequest',[ednVE.id,selectedSclLabel,ednVE.autoLink]);
        }else{
          if (nextSyllable.readonly) {
            alert("Insufficient permissions to link syllable '"+ nextSyllable.value+"', aborting autolink!");
          }
          ednVE.autoLink == false;
        }
      }
    };

    $(this.editDiv).unbind('autoLinkAdvance').bind('autoLinkAdvance', autoLinkAdvanceHandler);




    /**
    * handle 'linkResponse' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string linkTarget Identifies the target element to link
    */

    function linkResponseHandler(e,senderID, linkTarget) {
      if (senderID == ednVE.id || !ednVE.linkSource) {
        return;
      }

      DEBUG.log("event","link response recieved by editionVE in "+ednVE.id+" from "+senderID+" with source "+ednVE.linkSource+" target "+ linkTarget);

      var savedata={},
      srcPrefix = ednVE.linkSource.substring(0,3),
      trgPrefix = linkTarget.substring(0,3),
      sclID = ednVE.linkSource.substring(3),
      segID = linkTarget.substring(3),
      segClass, sclOldSegID;
      sclOldSegID = entities['scl'][sclID]['segID'];
      oldIsTransSeg = (sclOldSegID && entities['seg'][sclOldSegID] &&
                        entities['seg'][sclOldSegID]['stringpos'] &&
                        entities['seg'][sclOldSegID]['stringpos'].length);
      savedata['scl'] = [{scl_id:sclID,scl_segment_id:segID}];
      if (oldIsTransSeg) {
        scratch = (entities['scl'][sclID]['scratch']?JSON.parse(entities['scl'][sclID]['scratch']):{});
        scratch['tranSeg'] = 'seg'+sclOldSegID;//todo modify this to have transblnID and char pos
//        savedata['scl'][0]['scl_scratch'] = JSON.stringify(scratch);
      }
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //save link
      $.ajax({
        dataType: 'json',
        url: basepath+'/services/saveEntityData.php?db='+dbName,
        data: 'data='+JSON.stringify(savedata),
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.syllablecluster && data.syllablecluster.success) {
            if (data['syllablecluster'].columns &&
              data['syllablecluster'].records[0] &&
              data['syllablecluster'].columns.indexOf('scl_id') > -1) {
              var record, segID, sclID, cnt, sclLabel, segLabel, segSylIDs,syllable,
              pkeyIndex = data['syllablecluster'].columns.indexOf('scl_id'),
              segIdIndex = data['syllablecluster'].columns.indexOf('scl_segment_id');
              cnt = data['syllablecluster'].records.length;
              // for each record
              for(i=0; i<cnt; i++) {
                record = data['syllablecluster'].records[i];
                sclID = record[pkeyIndex];
                segID = record[segIdIndex];
                sclLabel = 'scl'+sclID;
                segLabel = 'seg'+segID;
                // update cached data
                segSylIDs = entities['seg'][segID]['sclIDs'] ? entities['seg'][segID]['sclIDs']:[];
                segSylIDs.push(sclID);
                entities['seg'][segID]['sclIDs'] = segSylIDs;
                entities['scl'][sclID]['segID'] = segID;
                syllable = $(".grpGra."+sclLabel,ednVE.contentDiv);
                segClass = syllable.get(0).className.match(/seg\d+/);
                if (segClass) {// new edition added syllables don't have segments.
                  segClass = syllable.get(0).className.match(/seg\d+/)[0];
                  syllable.removeClass(segClass);
                }
                syllable.addClass(segLabel);
                ednVE.refreshLineCache(syllable);
                DEBUG.log("gen","change class for "+sclLabel+" from "+segClass+" to "+ segLabel);
              }
              ednVE.linkMode = false;
              $('.editContainer').trigger('linkComplete',[ednVE.id,sclLabel,segLabel,oldIsTransSeg?null:segClass]);
            }
            if (data.editionHealth) {
              DEBUG.log("health","***Save Syllable Link***");
              DEBUG.log("health","Params: "+JSON.stringify(savedata));
              DEBUG.log("health",data.editionHealth);
            }
             if (data['syllablecluster'] && data['syllablecluster'].errors && data['syllablecluster'].errors.length) {
              alert("An error occurred while trying to save to a segment record. Error: " + data['syllablecluster'].errors.join());
            }
            if (data['error']) {
              alert("An error occurred while trying to save to a segment record. Error: " + data['error']);
            }
          }
        },// end success cb
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to link. Error: " + error);
        }
      });// end ajax
    };

    $(this.editDiv).unbind('linkResponse').bind('linkResponse', linkResponseHandler);


    /**
    * handle 'updatelabel' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string array entTags parallel array of entity tags who's labels have changed
    * @param string array entLabels parallel array of labels for entity tags
    * @param string entTag Entity tag of primary change
    */

    function updateLabelHandler(e,senderID, entTags, entLabels, primaryTag) {
      if (senderID == ednVE.id) {
        return;
      }
      if (primaryTag && primaryTag.match(/^seq/)) {
        var seqID = primaryTag.substr(3);
        ednVE.calcLineGraphemeLookups(seqID);
        ednVE.refreshPhysLineHeader(seqID);
      }
    };

    $(this.editDiv).unbind('updatelabel').bind('updatelabel', updateLabelHandler);


    /**
    * handle 'updateselection' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string array selectionGIDs Global entity ids if entities hovered
    * @param string entTag Entity tag of selection
    */

    function updateSelectionHandler(e,senderID, selectionGIDs, entTag) {
      if (senderID == ednVE.id) {
        return;
      }
      var i, id;
      DEBUG.log("event","selection changed recieved by editionVE in "+ednVE.id+" from "+senderID+" selected ids "+ selectionGIDs.join());
      $(".selected", ednVE.contentDiv).removeClass("selected");
      if (selectionGIDs && selectionGIDs.length) {
        $.each(selectionGIDs, function(i,val) {
          var entity ,j,entTag;
          if (val && val.length) {
            entity = $('.'+val,ednVE.contentDiv);
            if (entity && entity.length > 0) {
              entity.addClass("selected");
            } else if (val.match(/^seq/) && ednVE.entTagsBySeqTag[val] && ednVE.entTagsBySeqTag[val].length) {
              for (j in ednVE.entTagsBySeqTag[val]) {
                entTag = ednVE.entTagsBySeqTag[val][j];
                $('.'+entTag,ednVE.contentDiv).addClass("selected");
              }
            }
          }
        });
      }
    };

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);


/**
* handle 'mouseleave' event
*
* @param object e System event object
*/

    function contentDivMouseLeaveHandler(e) {
      DEBUG.log("event","leave contentDiv of editionVE in "+ednVE.id);
      if (ednVE.editDiv.classList.contains('hasFocus')) {
        ednVE.lastSelection = ednVE.getSelectionEntityGIDs();
      }
    }
    $(this.contentDiv).unbind('mouseleave').bind('mouseleave', contentDivMouseLeaveHandler);


/**
* handle 'selectstart' event
*
* @param object e System event object
*/

    function selectStartHandler(e) {
 //     alert("selectionstart");
    }
    $(this.editDiv).unbind('selectstart').bind('selectstart', selectStartHandler);


/**
* handle 'dblclick' event for grapheme group elements
*
* @param object e System event object
*
* @returns true|false
*/

    function grpGraDblClickHandler(e) {
      var classes = $(this).attr('class'), objLevel = ednVE.layoutMgr.getEditionObjectLevel(),
          firstGroup, lastGroup, ord, minOrd = 10000000, maxOrd = 0,
          entTag, sandhiSelector = "", headernode, segIDs = [],i, sclIDs = [],sclID, lineord;
      if(!e.ctrlKey){//if not multiselect
        $(".selected", ednVE.contentDiv).removeClass("selected");
        ednVE.selOrderGrpGraClasses = [];
      }
      ednVE.selOrderGrpGraClasses.push(classes);
      if (objLevel == 'token' && ednVE.editMode != "modify") {
        entTag = classes.match(/tok\d+/)[0];
        sandhiSelector = ",.grpGra.s"+entTag;
      } else if (objLevel == 'compound' && ednVE.editMode != "modify") {
        if (classes.match(/cmp\d+/)) {
          entTag = classes.match(/cmp\d+/)[0];
        } else {
          entTag = classes.match(/tok\d+/)[0];
          sandhiSelector = ",.grpGra.s"+entTag;
        }
      } else if (objLevel == 'line' && ednVE.editMode != "modify") {
        if ($(this).prev().hasClass('textDivHeader')) {
          headernode = $(this).prev();
        } else {
          headernode = $(this).prevUntil('.textDivHeader').last().prev();
        }
        entTag = headernode.attr('class').match(/line(seq\d+)/)[1];
        lineord = headernode.attr('class').match(/ordL\d+/)[0];
      } else {
        entTag = classes.match(/scl\d+/)[0];
      }
      if (ednVE.editMode == "tag" && ednVE.curTagID && entTag) {
        ednVE.tagEntity(entTag);
      } else if (ednVE.editMode == "tag" && !ednVE.curTagID) {
        alert("No entity was tagged. Please select current tag for modal tagging to work.");
      }
      if (objLevel == 'line' ) {
        $("."+lineord+":not(.textDivHeader)",ednVE.contentDiv).addClass("selected");
      } else {
        $(".grpGra."+entTag+sandhiSelector,ednVE.contentDiv).addClass("selected");
      }
      if (ednVE.componentLinkMode && ednVE.entPropVE && ednVE.entPropVE.addSequenceEntityGID ){
        ednVE.entPropVE.addSequenceEntityGID(entTag.substring(0,3)+':'+entTag.substring(3));
      } else {
//        ednVE.propMgr.showVE(null,entTag);
        ednVE.refreshProperty(classes);
      }
      //find segment ids for selected
      $(".grpGra.selected",ednVE.contentDiv).each(function(index,el){
           sclID = el.className.match(/scl(\d+)/)[1];
           if (sclIDs.indexOf(sclID) == -1) {//skip duplicate from split syllables
            segIDs.push('seg'+entities['scl'][sclID].segID);
            sclIDs.push(sclID);
           }
      });
      //trigger selection change
      if (ednVE.linkMode) {
        $('.editContainer').trigger('linkResponse',[ednVE.id,classes.match(/scl\d+/)[0]]);
      } else if (!ednVE.autoLinkOrdMode){
        $('.editContainer').trigger('updateselection',[ednVE.id,segIDs,entTag]);
      }
      //find first and last syllable
      $(".grpGra."+entTag,ednVE.contentDiv).each(function(index,el) {
          if (el && el.className) {
            ord = el.className.match(/ord(\d+)/);
            if (ord) {
              ord = ord[1];
              if (ord < minOrd) {
                firstGroup = el;
                minOrd = ord;
              }
              if (ord > maxOrd) {
                lastGroup = el;
                maxOrd = ord;
              }
            }
          }
      });
      if (firstGroup && lastGroup && firstGroup != lastGroup) {
        sclSetSelection(firstGroup,"start",lastGroup,"end");
      }
      if (ednVE.editMode == "modify" && ednVE.sclEd) {
        if (ednVE.sclEd.sclID != sclID) {
          ednVE.sclEd.init($(this));
        } else {
          ednVE.sclEd.synchSelection();
        }
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    $(".grpGra", this.editDiv).unbind('dblclick').bind('dblclick',grpGraDblClickHandler);


/**
* handle 'click' event for grapheme group elements
*
* @param object e System event object
*
* @returns true|false
*/

    function grpGraClickHandler(e) {
      var classes = $(this).attr('class'), entTag, entGID,
          objLevel = ednVE.layoutMgr.getEditionObjectLevel();

      if (ednVE.setLinkSfDependencyMode) {
        if (objLevel == 'token' && ednVE.editMode != "modify") {
          entTag = classes.match(/tok\d+/)[0];
        } else if (objLevel == 'compound' && ednVE.editMode != "modify") {
          if (classes.match(/cmp\d+/)) {
            entTag = classes.match(/cmp\d+/)[0];
          } else {
            entTag = classes.match(/tok\d+/)[0];
          }
        }
        if (entTag &&  ednVE.propMgr.propVEType == 'entPropVE' &&
            ednVE.propMgr.currentVE.linkDependencyEntity) {
          entGID = entTag.substring(0,3)+":"+entTag.substring(3);
          ednVE.propMgr.currentVE.linkDependencyEntity(entGID);
        } else {
          UTILITY.beep();
        }
        e.stopImmediatePropagation();
        return false;
      }
    };

    //assign handler for all grapheme group elements
    $(".grpGra", this.editDiv).unbind('click').bind('click',grpGraClickHandler);


/**
* handle 'dblclick' event for grapheme group elements
*
* @param object e System event object
*
* @returns true|false
*/

    function freeTextDblClickHandler(e) {
//      alert("underconstruction");
      if (ednVE.editmode == "Modify") {
        //this should also happen for click
      }else{
        $(this).addClass('selected');
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
//    $(".freetext", this.editDiv).unbind('dblclick').bind('dblclick',freeTextDblClickHandler);


/**
* handle 'dblclick' event for edition header element
*
* @param object e System event object
*
* @returns true|false
*/

    function ednDblClickHandler(e) {
      var classes = $(this).attr('class'),
      entTag = classes.match(/edn\d+/)[0];
      ednVE.propMgr.showVE(null,entTag);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for edition header
    $(".ednLabel", this.editDiv).unbind('dblclick').bind('dblclick',ednDblClickHandler);


/**
* handle 'dblclick' event for sequence label elements
*
* @param object e System event object
*
* @returns true|false
*/

    function seqDblClickHandler(e) {
      var classes = $(this).attr('class'),
      entTag = classes.match(/seq\d+/)[0];
      ednVE.propMgr.showVE(null,entTag);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all line headers
    $(".seqLabel", this.editDiv).unbind('dblclick').bind('dblclick',seqDblClickHandler);


/**
* handle 'mouseenter' event for grapheme group elements
*
* @param object e System event object
*/

    function sclMouseEnterHandler(e) {
      if (!e.buttons) {//skip if user is selecting text (button down)
        var classes = $(this).attr('class');
        if (classes && classes.match(/scl(\d+)/)) {
          sclID = classes.match(/scl(\d+)/)[1];
          if (entities['scl'] && entities['scl'][sclID]) {
            segIDs = [('seg' + entities['scl'][sclID].segID)];
            $('.editContainer').trigger('enterSyllable',[ednVE.id,segIDs]);
          }
        }
      }
    };


/**
* handle 'mouseleave' event for grapheme group elements
*
* @param object e System event object
*/

    function sclMouseLeaveHandler(e) {
      if (!e.buttons) {//skip if user is selecting text (button down)
        var classes = $(this).attr('class');
        if (classes && classes.match(/scl(\d+)/)) {
          sclID = classes.match(/scl(\d+)/)[1];
          if (entities['scl'] && entities['scl'][sclID]) {
            segIDs = [('seg' + entities['scl'][sclID].segID)];
            $('.editContainer').trigger('leaveSyllable',[ednVE.id,segIDs]);
          }
        }
      }
    };

    //assign handler for all syllable elements
    $(".grpGra", this.editDiv).off('hover');
    $(".grpGra", this.editDiv).hover(sclMouseEnterHandler, sclMouseLeaveHandler);


/**
* helper function for pageup and pagedown scrolling
*
* @param boolean isPgUp identifying the direction
*/

    function pageScrollHelper(isPgUp) {
      var scrollDiv = ednVE.contentDiv.get(0), maxScrollTop,
          curTop, divHeight, pageHeight, contentHeight;

      divHeight = scrollDiv.clientHeight;
      pageHeight = divHeight - 5;
      contentHeight = scrollDiv.scrollHeight;
      curTop = scrollDiv.scrollTop;
      maxScrollTop = Math.max((contentHeight - divHeight -1),0);

      if (isPgUp && curTop > 0) { // page up and not at top
        scrollDiv.scrollTop = Math.max((curTop - pageHeight),0);
      } else if (curTop < maxScrollTop) { // page down and not scrolled to max
        scrollDiv.scrollTop = Math.min((curTop + pageHeight),maxScrollTop);
      }
    };


/**
* handle 'keydown' event for content div element
*
* @param object e System event object
*
* @returns true|false
*/

    function keyDownHandler(e) {
      var sclEditor = ednVE.sclEd,tcmEditor = ednVE.tcmEd, direction,$adjNode,$prevNode, loopLimit =5,
          prevSyllable, nextSyllable, key, ordL;
      if (ednVE.editMode == 'modify' && sclEditor) {
        switch (e.keyCode || e.which) {
          case 38://'Up':
            if (!sclEditor.syllable || sclEditor.syllable.hasClass("firstLine")) {
              e.stopImmediatePropagation();
              return false;
            }
            sclEditor.save();
            break;
          case 40://'Down':
            if (!sclEditor.syllable || sclEditor.syllable.hasClass("lastLine")) {
              e.stopImmediatePropagation();
              return false;
            }
            sclEditor.save();
            break;
          case 37://"Left":
            DEBUG.log("nav","Call to left arrow keydown for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
            if (sclEditor.moveCursor("left",e.shiftKey)) {
              e.stopImmediatePropagation();
              return false;
            }
            break;
          case 39://"Right":
            DEBUG.log("nav","Call to right arrow keydown for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
            if (sclEditor.moveCursor("right",e.shiftKey)) {
              e.stopImmediatePropagation();
              return false;
            }
            break;
          case 18://"Alt":
          case 16://"Shift":
          case 17://"Control":
//            e.stopImmediatePropagation();
//            return false;//eat all other keys
            break;
          case 13://"Enter":
            if (sclEditor.hasCaret()) {
              if (sclEditor.caretAtBOL() ||
                  sclEditor.caretAtEOL()) {
                ednVE.insertFreeTextLine();
              } else if (sclEditor.caretAtBoundary()) {
                ednVE.splitLine();
              } else {
                UTILITY.beep();
              }
              e.stopImmediatePropagation();
              return false;//eat all other keys
            }
            break;
          case 8://"Backspace":
            if (e.ctrlKey || e.metaKey) {//ctrl + backspace
              if (sclEditor.isSelectAll()) {//entire syllable selected
                sclEditor.removeSelected();
                e.stopImmediatePropagation();
                return false;//eat all other keys
              } else if (sclEditor.hasCaret() &&
                        sclEditor.caretAtBoundary("left")) { // caret at left boundary of syllable
                $prevNode = sclEditor.prevAdjacent();
                //move over TCM so we check beyond TCM
                if ($prevNode.hasClass('TCM')){
                  $prevNode = $prevNode.prev();
                }
                //check for sclEditor state next to left side auxilary element boundary, TCM or linebreak
                if ($prevNode.hasClass('TCM')){
                  DEBUG.log("nav","Call to ctrl Backspace for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to TCM node");
                } else if ($prevNode.hasClass('boundary')) {
                  //process removing boundary by combining this token with the previous
                  DEBUG.log("nav","Call to ctrl Backspace for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to boundary node");
                  ednVE.combineTokens('prev');
                } else if ($prevNode.hasClass('textDivHeader')) {
                  if ($prevNode.hasClass('startHeader') || $prevNode.hasClass('freetext')) {
                    UTILITY.beep();
                    DEBUG.log("warn","Ctrl Backspace at beginning of edition or on freetext line, ignoring keystroke");
                  } else {
                    headerClasses = $prevNode.attr('class');
                    ordL = headerClasses.match(/ordL(\d+)/);
                    if (ordL && ordL.length >1) {
                      ordL = ordL[1];
                      ordL = parseInt(ordL);
                      ordL--;
                      $prevHeader = $('.textDivHeader.ordL'+ordL,ednVE.contentDiv);
                      if ($prevHeader && $prevHeader.length && !$prevHeader.hasClass('freetext')) {
                        //process removing linebreak
                        ednVE.combineTokens('prev');
                        e.stopImmediatePropagation();
                        return false;//eat all other keys
                      }
                    }
                    UTILITY.beep();
                    DEBUG.log("warn","Ctrl Backspace next to freetext line, ignoring keystroke");
                    alert("warning combining word with freetext line not allowed, ignoring keystroke");
                  }
                } else { //unknown so do nothing but beep  or should we send this to VSE like Del case
                  UTILITY.beep();
                  DEBUG.log("warn","BEEP! Ctrl Backspace at unknown location, ignoring keystroke");
                }
                e.stopImmediatePropagation();
                return false;//eat all other keys
              } else { // caret not at left boundary so sent to syllable editor for processing
                var ret = sclEditor.preprocessKey("Backspace",(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e);
                if (ret === false) {
                  sclEditor.synchSelection();
                }
                e.stopImmediatePropagation();
                return false;//eat all other keys
              }
            } else if (sclEditor.caretAtBOL()){ //simple backspace at beginning of line so try to merge with previous line
              $prevNode = sclEditor.prevAdjacent();
              //move over TCM so we check beyond TCM
              if ($prevNode.hasClass('TCM')){
                $prevNode = $prevNode.prev();
              }
              //check for sclEditor state next to left side auxilary element boundary, TCM or linebreak
              if ($prevNode.hasClass('TCM')){
                DEBUG.log("nav","Call to ctrl Backspace for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to TCM node");
              } else if ($prevNode.hasClass('textDivHeader')) {
                if ($prevNode.hasClass('startHeader') || $prevNode.hasClass('freetext')) {
                  UTILITY.beep();
                  DEBUG.log("warn","Ctrl Backspace at beginning of edition or on freetext line, ignoring keystroke");
                  e.stopImmediatePropagation();
                  return false;//eat all other keys
                } else {
                  headerClasses = $prevNode.attr('class');
                  ordL = headerClasses.match(/ordL(\d+)/);
                  if (ordL && ordL.length >1) {
                    ordL = ordL[1];
                    ordL = parseInt(ordL);
                    ordL--;
                    $prevHeader = $('.textDivHeader.ordL'+ordL,ednVE.contentDiv);
                    if ($prevHeader && $prevHeader.length && !$prevHeader.hasClass('freetext')) {
                      //process removing linebreak
                      ednVE.mergeLine('prev');
                      e.stopImmediatePropagation();
                      return false;//eat all other keys
                    }
                  }
                  UTILITY.beep();
                  DEBUG.log("warn","Ctrl Backspace next to freetext line, ignoring keystroke");
                  alert("warning merging with freetext line not allowed, ignoring keystroke");
                }
                DEBUG.log("nav","Call to ctrl Backspace for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to linebreak node");
              }
            } else { //simple backspace so pass to syllable editor for processing
              var ret = sclEditor.preprocessKey("Backspace",(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e);
              if (ret === false) {
                sclEditor.synchSelection();
              }
              e.stopImmediatePropagation();
              return false;//eat all other keys
            }
            break;
          case 32:// space " ":
            if (sclEditor.hasCaret()) {
              //if caret at syllable boundary
              if (sclEditor.caretAtBoundary()) {
                //check if left side
                if (sclEditor.caretAtBoundary("left")) {
                  $adjNode = sclEditor.prevAdjacent();
                  if ($adjNode.hasClass("TCM")){
                    $adjNode = sclEditor.prevAdjacent();
                  }
                } else {
                  $adjNode = sclEditor.nextAdjacent();
                  if ($adjNode.hasClass("TCM")){
                    $adjNode = sclEditor.nextAdjacent();
                  }
                }
                //if next to cmp toksep change to simple boundary - separate compound
                if ($adjNode.hasClass("toksep")) {
                  //split crossline token
                  ednVE.splitToken();
                  UTILITY.beep();
                  DEBUG.log("warn","BEEP! Call to token break next to compound separator for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                //if cursor next to a boundry (not cmp toksep) then error can't break token at token start or end
                } else if ($adjNode.hasClass("boundary")) {
                  UTILITY.beep();
                  DEBUG.log("warn","BEEP! Ignoring call to token break next to boundary for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                //if cursor next to a linebreak then error
                } else if ($adjNode.hasClass("linebreak")) {
                  //check for crossline token or compound
                  if ($adjNode.hasClass("toksplit") || $adjNode.hasClass("cmpsplit")) {
                    //split crossline token
                    ednVE.splitToken();
                  } else {
                    UTILITY.beep();
                    DEBUG.log("warn","BEEP! Call to token break next to linebreak for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                  }
                //else intra token
                } else {
                  DEBUG.log("gen","Call to token break intra token for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                  ednVE.splitToken();
                }
              } else {
                //else intra syllable so separate scl, token, etc.
                DEBUG.log("gen","Call to token break intra syllable for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                ednVE.splitToken();
              }
            } else if (e.key != ' ') {
              break;
            } else {
              UTILITY.beep();
              DEBUG.log("warn","BEEP! Call to token break not supported for selection for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
            }
            e.stopImmediatePropagation();
            return false;
            break;
          case 45://"Insert":
            if ((e.ctrlKey || e.metaKey) && (sclEditor.caretAtBoundary('left') || sclEditor.caretAtBoundary('right')) ) {
              sclEditor.insertNew();
            } else {
              DEBUG.log("nav","Call to ctrl Insert for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" cursor not at boundary");
            }
            e.stopImmediatePropagation();
            return false;
            break;
          case 46://"Del":
            if (e.ctrlKey || e.metaKey) {//ctrl + delete
              if (sclEditor.isSelectAll()) {//entire syllable selected
                sclEditor.removeSelected();
                e.stopImmediatePropagation();
                return false;//eat all other keys
              } else if (sclEditor.hasCaret() &&
                        sclEditor.caretAtBoundary("right")) { // caret at right boundary of syllable
                $adjNode = sclEditor.nextAdjacent();
                //move over TCM so we check beyond TCM
                if ($adjNode && $adjNode.hasClass('TCM')){
                  $adjNode = $adjNode.next();
                }
                if ($adjNode && $adjNode.hasClass('TCM')){
                  //todo: process removing TCM ?? ignore right now
                  DEBUG.log("nav","Call to ctrl Del for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to TCM node");
                } else if ($adjNode && $adjNode.hasClass('boundary')) {
                  //process removing boundary
                  DEBUG.log("nav","Call to ctrl Del for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to boundary node");
                  ednVE.combineTokens('next');
                } else if ($adjNode && $adjNode.hasClass('linebreak')) {
                  //process removing compound split before linebreak
                  headerClasses = $adjNode.attr('class');
                  ordL = headerClasses.match(/ordL(\d+)/);
                  if (ordL && ordL.length >1) {
                    ordL = ordL[1];
                    ordL = parseInt(ordL);
                    ordL++;
                    $nextHeader = $('.textDivHeader.ordL'+ordL,ednVE.contentDiv);
                    if ($nextHeader && $nextHeader.length && !$nextHeader.hasClass('freetext')) {
                      //process cobining cross line words
                      ednVE.combineTokens('next');
                      e.stopImmediatePropagation();
                      return false;//eat all other keys
                    }
                  }
                  UTILITY.beep();
                  DEBUG.log("warn","Ctrl Del with adjacent freetext line, ignoring keystroke");
                  alert("warning combining word with freetext line not allowed, ignoring keystroke");
                } else {//send ctrl + del to VSE on unknown boundary ????
                  UTILITY.beep();
                  DEBUG.log("warn","BEEP! Ctrl Del at unknown adjacent node, ignoring keystroke");
                }
                e.stopImmediatePropagation();
                return false;//eat all other keys
              } else { // caret not at right boundary so sent to syllable editor for processing
                var ret = sclEditor.preprocessKey("Del",(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e);
                if (ret === false) {
                  sclEditor.synchSelection();
                }
                e.stopImmediatePropagation();
                return false;//eat all other keys
              }
            } else if (sclEditor.caretAtEOL()) { //simple del at end of line so try to merge with next line
                $adjNode = sclEditor.nextAdjacent();
                //move over TCM so we check beyond TCM
                if ($adjNode.hasClass('TCM')){
                  $adjNode = $adjNode.next();
                }
                if ($adjNode.hasClass('TCM')){
                  //todo: process removing TCM ?? ignore right now
                  DEBUG.log("nav","Call to ctrl Del for '"+sclEditor.curSyl+"' with state "+sclEditor.state+" next to TCM node");
                  e.stopImmediatePropagation();
                  return false;//eat all other keys
                } else if ($adjNode.hasClass('linebreak')) {
                  headerClasses = $adjNode.attr('class');
                  ordL = headerClasses.match(/ordL(\d+)/);
                  if (ordL && ordL.length >1) {
                    ordL = ordL[1];
                    ordL = parseInt(ordL);
                    ordL++;
                    $nextHeader = $('.textDivHeader.ordL'+ordL,ednVE.contentDiv);
                    if ($nextHeader && $nextHeader.length && !$nextHeader.hasClass('freetext')) {
                      //process removing linebreak
                      ednVE.mergeLine('next');
                      e.stopImmediatePropagation();
                      return false;//eat all other keys
                    }
                  }
                  UTILITY.beep();
                  DEBUG.log("warn","Ctrl Del with adjacent freetext line, ignoring keystroke");
                  alert("warning merging with freetext line not allowed, ignoring keystroke");
                }
            } else {
              var ret = sclEditor.preprocessKey("Del",(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e);
              if (ret === false) {
                sclEditor.synchSelection();
              }
              e.stopImmediatePropagation();
              return false;//eat all other keys
            }
            break;
        }
      } else if (ednVE.editMode == "tcm"  && tcmEditor ) {
        switch (e.keyCode || e.which) {
          case 33://"Page Up":
            DEBUG.log("nav","Call to page up keydown ");
            pageScrollHelper(true);
            break;
          case 34://"Page Down":
            DEBUG.log("nav","Call to page down keydown ");
            pageScrollHelper(false);
            break;
          case 38://'Up':
            DEBUG.log("nav","Call to up arrow keydown ");
            tcmEditor.moveLine('up');
            break;
          case 40://'Down':
            DEBUG.log("nav","Call to down arrow keydown ");
            tcmEditor.moveLine('down');
            break;
          case 37://"Left":
            DEBUG.log("nav","Call to left arrow keydown ");
            if (tcmEditor.moveCursor('left')) {
              e.stopImmediatePropagation();
              return false;//eat all other keys
            }
            break;
          case 39://"Right":
            DEBUG.log("nav","Call to right arrow keydown ");
            if (tcmEditor.moveCursor('right')) {
              e.stopImmediatePropagation();
              return false;//eat all other keys
            }
            break;
          case 18://"Alt":
          case 16://"Shift":
          case 17://"Control":
          default:
            if (e.ctrlKey || e.metaKey) {// copy or paste so let it pass
              DEBUG.log("event","Call to keydown in tcm with ctrl key ");
              return;
            }
            e.stopImmediatePropagation();
            return false;//eat all other keys
            break;
        }
//         e.stopImmediatePropagation();
//        return false;//eat all other keys
      } else {
        switch (e.keyCode || e.which) {
          case 33://"Page Up":
            DEBUG.log("nav","Call to page up keydown ");
            pageScrollHelper(true);
            break;
          case 34://"Page Down":
            DEBUG.log("nav","Call to page down keydown ");
            pageScrollHelper(false);
            break;
          case 38://'Up':
            DEBUG.log("nav","Call to up arrow keydown ");
            break;
          case 40://'Down':
            DEBUG.log("nav","Call to down arrow keydown ");
            break;
          case 37://"Left":
            DEBUG.log("nav","Call to left arrow keydown ");
            break;
          case 39://"Right":
            DEBUG.log("nav","Call to right arrow keydown ");
            break;
          default:
            if (e.ctrlKey || e.metaKey) {
              key = e.key?e.key :
                    (e.which == null?String.fromCharCode(e.keyCode):
                    ((e.which != 0 )?String.fromCharCode(e.which):null));
              if (key && key.toLowerCase() == "t") {// toggle sidebar
                ednVE.layoutMgr.toggleSideBar();
                e.stopImmediatePropagation();
                return false;//eat all other keys
              }
              // ctrl so meta command like copy or paste so let it pass to keypress
              DEBUG.log("event","Call to keydown with ctrl key");
              return;
            }
            e.stopImmediatePropagation();
            return false;//eat all other keys
            break;
        }
      }
    }

    //assign handler for all syllable elements
    $(this.contentDiv).unbind('keydown').bind('keydown', keyDownHandler);



/**
* handle 'key press' event for content div
*
* @param object e System event object
*
* @returns true|false
*/

    function keyPressHandler(e) {
      var sclEditor = ednVE.sclEd, tcmEditor = ednVE.tcmEd, direction,
          key = e.which == null?String.fromCharCode(e.keyCode):
                (e.which != 0 )?String.fromCharCode(e.which):null;
      if (key && ednVE.editMode == "modify" && sclEditor) {
//        DEBUG.log("gen""Call to keypress with key '"+key+"' for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
        if ((e.ctrlKey || e.metaKey) && (key.toLowerCase() == "c" || key.toLowerCase() == "v")) {// copy or paste so let it pass
          DEBUG.log("event","Call to keypress with copy paste key '"+key+"' for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
          return;
        }
        if (key == '-' || key == '‐') {//compound separator
          if (sclEditor) {
            if (sclEditor.hasCaret()) {
              //if caret at syllable boundary
              if (sclEditor.caretAtBoundary()) {
                var adjNode;
                //check if left side
                if (sclEditor.caretAtBoundary("left")) {
                  adjNode = sclEditor.prevAdjacent();
                  direction = "prev";
                  if (adjNode.hasClass("TCM")){
                    adjNode = adjNode.prev();
                  }
                } else {
                  adjNode = sclEditor.nextAdjacent();
                  direction = "next";
                  if (adjNode.hasClass("TCM")){
                    adjNode = adjNode.next();
                  }
                }
                //if next to cmp toksep change to simple boundary - separate compound
                if (adjNode.hasClass("toksep")) {
                  UTILITY.beep();
                  DEBUG.log("warn","BEEP! Call to create compound next to compound separator not allowed for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                //cursor next to a boundry (not cmp toksep)
                } else if (adjNode.hasClass("boundary")) {
                  DEBUG.log("gen","Call to create compound next to boundary for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                  ednVE.createCompound();
                //cursor next to a linebreak
                } else if (adjNode.hasClass("linebreak")){
                  DEBUG.log("gen","Call to token break at end of line break for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                  ednVE.createCompound();
                //cursor next to a line header
                } else if (adjNode.hasClass("textDivHeader")){
                  DEBUG.log("gen","Call to token break at beginning of line break for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                  ednVE.createCompound();
                //else intra token
                } else {
                  DEBUG.log("gen","Call to token break intra token for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                  ednVE.createCompound();
                }
              } else {
                //else intra syllable so separate scl, token, etc.
                DEBUG.log("gen","Call to create compound intra syllable for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
                ednVE.createCompound();
              }
            } else {
              UTILITY.beep();
              DEBUG.log("warn","BEEP! Call to create compound not supported for selection for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
            }
//              DEBUG.log("gen""BEEP! Call to space with token '"+sclEditor.getTokenID()+"' at position "+sclEditor.getTokenCurPos());
            e.stopImmediatePropagation();
            return false;
          }
        }//end compound separator
        if (((e.keyCode || e.which) == 32 && e.key == ' ') || //since some special key combinations pass keycode == 32 which are not a space
            !sclEditor.preprocessKey(key,(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e)) {
          e.stopImmediatePropagation();
          return false;//eat all other keys
        }
      } else if (key && ednVE.editMode == "tcm" && tcmEditor) {
        //tcmEditor.processKey(key,e.ctrlKey,e.shiftKey,e.altKey,e);
//        e.stopImmediatePropagation();
//        return false;//eat all other keys
      } else {
        if ((e.ctrlKey || e.metaKey) && key) {
          if (key.toLowerCase() == "v") {// paste so eat it
            DEBUG.log("event","Call to keypress with paste");
            e.stopImmediatePropagation();
            return false;//eat all other keys
          } else if (key.toLowerCase() == "t") {// toggle sidebar
            ednVE.layoutMgr.toggleSideBar();
            e.stopImmediatePropagation();
            return false;//eat all other keys
          }
        }
      }
    };

    //assign handler for all syllable elements
    $(this.contentDiv).unbind('keypress').bind('keypress', keyPressHandler);


/**
* handle 'keyup' event for content div
*
* @param object e System event object
*
* @returns true|false
*/

    function keyUpHandler(e) {
      var sclEditor = ednVE.sclEd, tcmEditor = ednVE.tcmEd, startNode, range, sel, selectNode, hint, grdCnt=500,
          prevSyllable, nextSyllable;
      if (sclEditor && ednVE.editMode == "modify") {
        switch (e.keyCode || e.which) {
          case 37: //'Left':
  //          DEBUG.log("gen","Call to left arrow keyup for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
            break;
          case 39://'Right':
  //          DEBUG.log("gen","Call to right arrow keyup for '"+sclEditor.curSyl+"' with state "+sclEditor.state);
            break;
          case 38://'Up':
          case 40://'Down':
            if (window.getSelection) {
              sel = window.getSelection();
              if (sel.getRangeAt && sel.rangeCount) {
                range = sel.getRangeAt(0);
              }
            } else if (document.selection && document.selection.createRange) {
              range = document.selection.createRange();
            }

            if (range) {
              //DIV case where long line next to short sets target
              //linebreak
              //boundary offset 0
              // all need to find previous grpGra
              if (range.startContainer.nodeName == 'SPAN' && range.startContainer.className.match(/textDivHeader/)) {
                //choose first syllable on line next('.grpGra')
                nextNode = $(range.startContainer);
                while (nextNode.length && grdCnt-- && !nextNode.hasClass('grpGra')) {
                  nextNode = nextNode.next();
                }
                startNode = nextNode.get(0);
                hint = "first";
              } else if (range.startContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                         && range.startContainer.id.match(/textContent/) != null) {
                // check range offset which should indicate the index of the closest childnode
                if (range.startOffset > 0 && range.startOffset <= range.startContainer.childNodes.length) {
                  prevNode = $(range.startContainer.childNodes[range.startOffset]);
                  while (prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
                    prevNode = prevNode.prev();
                  }
                  startNode = prevNode.get(0);
                  hint = "last";
                }else{//strange so just set it to first syllable
                  nextNode = $(range.startContainer.firstChild);
                  while (nextNode.length && grdCnt-- && !nextNode.hasClass('grpGra')) {
                    nextNode = nextNode.next();
                  }
                  startNode = nextNode.get(0);
                }
              } else if (range.startContainer.nodeName == "#text") {
                startNode = range.startContainer.parentNode;
              } else {
                startNode = range.startContainer;
              }
              grdCnt = 500;
              if (startNode.nodeName == 'SPAN') {
                if (!startNode.className.match(/grpGra/)) {
                  if (startNode.className.match(/TCM/)) {
                    if (startNode.className.match(/ord(\d+)/) &&
                        startNode.className.match(/ord(\d+)/)[1] != 0) {
                      hint = "last";
                    }
                    selectNode = $('.grpGra.'+startNode.className.match(/scl\d+/)[0],ednVE.contentDiv);
                  } else if (startNode.className.match(/boundary/)) {
                    if (range.startOffset == 0) {
                      prevNode = $(startNode);
                      while (prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
                        prevNode = prevNode.prev();
                      }
                      selectNode = prevNode;
                      hint = "last";
                    }else{
                      nextNode = $(startNode);
                      while (nextNode.length && grdCnt-- && !nextNode.hasClass('grpGra')) {
                        nextNode = nextNode.next();
                      }
                      selectNode = nextNode;
                      hint = "first";
                    }
                  } else if (startNode.className.match(/linebreak/)) {
                    prevNode = $(startNode);
                    while (prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
                      prevNode = prevNode.prev();
                    }
                    selectNode = prevNode;
                    hint = "last";
                  }
                } else {
                  selectNode = $(startNode);
                }
                if (!hint) {
                  hint = "first";
                }else{
                  range.setStart(selectNode.get(0).firstChild,
                                  hint=="last"?selectNode.text().length:0);
                  range.setEnd(selectNode.get(0).firstChild,
                                  hint=="last"?selectNode.text().length:0);
                }
                sclEditor.init(selectNode,hint);
              }//end if SPAN
            }//end if range
            break;
          case 8://"Backspace":
          case 46://"Del":
//            sclEditor.processKey(e.keyCode == 46?"Del":"Backspace",e.ctrlKey,e.shiftKey,e.altKey);
            //sclEditor.synchSelection();
            break;
          case 32:// space " ":
          case 18://"Alt":
          case 16://"Shift":
          case 17://"Control":
            break;
          default:
            sclEditor.synchSelection();
        }
      } else if (ednVE.editMode == "tcm" && tcmEditor) {
        switch (e.keyCode || e.which) {
          case 37: //'Left':
          case 39://'Right':
          case 38://'Up':
          case 40://'Down':
            break;
          default:
            tcmEditor.processKey(e.key,e.ctrlKey,e.shiftKey,e.altKey,e);
            e.stopImmediatePropagation();
            return false;
        }
      }
    };

    //assign handler keyup to container div
    $(this.contentDiv).unbind('keyup').bind('keyup', keyUpHandler);

    //assign handler for paste to container div
    $(this.contentDiv).unbind('paste').bind('paste', function(e) {
      var sclEditor = ednVE.sclEd, tcmEditor = ednVE.tcmEd, i=0, cnt, ret = false,
          txt = e.originalEvent.clipboardData.getData("text/plain");
      if (txt && txt.length > 0) {
        cnt = txt.length;
        if (ednVE.editMode == "modify" && sclEditor) {
          while (i<cnt && !ret) {
           ret = sclEditor.preprocessKey(txt.charAt(i),(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e);
           i++;
          }
          if (!ret) {
            e.stopImmediatePropagation();
            sclEditor.synchSelection();
            return false;
          }
        } else  if (ednVE.editMode == "tcm" && tcmEditor) {
            tcmEditor.processKey(txt,(e.ctrlKey || e.metaKey),e.shiftKey,e.altKey,e);
            e.stopImmediatePropagation();
            return false;
        }
      }
    });

    var mdTarget = null,
        mdPos= [0,0],
        mdTime = null,
        dblClickThreshold = 60;


/**
* handle 'mousedown' event for display elements
*
* @param object e System event object
*/

    function sclMouseDownHandler(e) {
      var eTime = (new Date()).getTime();
      mdTarget = e.target;
      mdPos =[e.clientX,e.clientY];
      if ((eTime - mdTime) > dblClickThreshold) {
        mdTime = eTime;
      }
    };

    //assign handler for all span elements
    $("span,div.freetext,#"+this.id+"textContent", this.editDiv).unbind("mousedown").bind("mousedown", sclMouseDownHandler);

    var mmTarget = null;//


/**
* handle 'mousemove' event for display span elements
*
* @param object e System event object
*/

    function sclMouseMoveHandler(e) {
      mmTarget = e.target;
    }
    //assign handler for all syllable elements
//    $("span", this.editDiv).unbind("mousemove").bind("mousemove", sclMouseMoveHandler);


/**
* find next element grapheme group or freetext div
*
* @param node elem Reference html element
*
* @returns node | null Html node
*/

    function findNextGraGroupOrFreetext(elem) {
      var nextNode = $(elem), grdCnt = 500;
      if (!nextNode.hasClass('grpGra') && !nextNode.hasClass('freetext') ||
          nextNode.hasClass('textDivHeader') && !nextNode.hasClass('endHeader')) {// move atleast one node
        nextNode = nextNode.next();
      }
      while (nextNode.length && !nextNode.hasClass('grpGra') && !nextNode.hasClass('freetext')  && grdCnt--) {
        nextNode = nextNode.next();
      }
      if (grdCnt) {
        return nextNode.get(0);
      } else {
        return null;
      }
    }


/**
* find prev element grapheme group or freetext div
*
* @param node elem Reference html element
*
* @returns node | null Html node
*/

    function findPrevGraGroupOrFreetext(elem) {
      var prevNode = $(elem), grdCnt = 500;
      if (!prevNode.hasClass('grpGra') && !prevNode.hasClass('freetext') ||
          prevNode.hasClass('textDivHeader') && !prevNode.hasClass('startHeader')) {// move atleast one node
        prevNode = prevNode.prev();
      }
      while (prevNode.length && !prevNode.hasClass('grpGra') && !prevNode.hasClass('freetext') && grdCnt--) {
        prevNode = prevNode.prev();
      }
      if (grdCnt) {
        return prevNode.get(0);
      } else {
        return null;
      }
    }


/**
* handle 'mouseup' event for display elements
*
* @param object e System event object
*
* @returns true|false
*/

    function sclMouseUpHandler(e) {
      var sel,range,sRange,eRange,isCaretOnly,isReverseSelection,classes,
      muTime = (new Date()).getTime(),
      startNode,endNode,tokNode,sclNode,nextNode,prevNode, useTarget = false, eTarget,
      selectNode,tcmPos,childNodes,i,sclID,endOrdPos,selectOrdPos,
      startContainer,endContainer,startOffset,endOffset, otherContainer,
      newStartContainer,newStartOffset,newEndContainer,newEndOffset;
//      DEBUG.log("gen","muPos "+e.clientX+","+e.clientY);
      if (window.getSelection) {
        sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
          if (sel.rangeCount == 1) {
            sRange = eRange = sel.getRangeAt(0);
          } else {
            sRange = sel.getRangeAt(0);
            eRange = sel.getRangeAt(sel.rangeCount -1);
          }
        }
      } else if (document.selection && document.selection.createRange) {
        sRange = eRange = document.selection.createRange();
      }
      eTarget = (e.target.nodeName == '#text'? e.target.parentElement:e.target);
      startContainer = sRange.startContainer;
      startOffset = sRange.startOffset;
      endContainer = eRange.endContainer;
      endOffset = eRange.endOffset;
      if (ednVE.editMode == "modify" && ednVE.sclEd && startContainer && endContainer &&
             ((muTime - mdTime) > dblClickThreshold) ) {//edit mode restricts to syllable so fix up selection to constrain to a syllable
        isCaretOnly = ((mdPos[0] == e.clientX && mdPos[1] == e.clientY) || startContainer == endContainer &&
                        startOffset == endOffset && mdTarget == this);
        isReverseSelection = (!isCaretOnly &&((startContainer != endContainer &&
                                                (sel && startContainer == sel.focusNode &&
                                                        startOffset == sel.focusOffset||( mdTarget &&
                                                 (endContainer.parentElement == mdTarget ||
                                                 endContainer.parentElement == mdTarget.parentElement)))) ||
                                              (startContainer == endContainer &&
                                                sel && sel.anchorOffset != null &&
                                                sel.focusOffset == startOffset
                                                && startOffset != endOffset) || mdTarget &&
                                                mdTarget != this && mdTarget.id.match(/textContent/) != null));
/********************************* Caret Reposition cases ***************************************/
        if (isCaretOnly){
          DEBUG.trace("ednVE mousup","1 "+muTime);
          //handle caret reposition by snapping to nearest syllable- nearest text position in that syllable
          //range still on selected node case
          if ((startContainer.nodeName == 'SPAN' && startContainer.className.match(/selected/) && eTarget !== startContainer ||
              startContainer.nodeName == '#text' && startContainer.parentElement.className.match(/selected/) && eTarget !== startContainer.parentElement) ||
              startContainer == endContainer && startOffset != endOffset) {
            useTarget = true;
          }
          // line label case
          if (e.target.nodeName == 'SPAN' && e.target.className.match(/textDivHeader/)) {
          DEBUG.trace("ednVE mousup","2 "+muTime);
            //choose first syllable on line next('.grpGra')
            nextNode = $(e.target).next();
            while (nextNode.length && !nextNode.hasClass('grpGra') && !nextNode.hasClass('freetext')) {
              nextNode = nextNode.next();
            }
            startNode = nextNode.get(0);
            newStartContainer = newEndContainer = startNode.firstChild;
            newStartOffset = newEndOffset = 0;
          } else if (e.target.nodeName == 'DIV' // user end of line click or range issue in browser
                     && e.target.id.match(/textContent/) != null) {
          DEBUG.trace("ednVE mousup","3 "+muTime);
            // check range offset which should indicate the index of the closest childnode
            if (startOffset > 0 && startOffset <= e.target.childNodes.length) {
              if (startNode = findPrevGraGroupOrFreetext( startContainer != e.target ?
                                                  (startContainer.nodeName == "#text" ?
                                                    startContainer.parentNode :
                                                    startContainer):
                                                  e.target.childNodes[startOffset])) {
                newStartContainer = newEndContainer = startNode.firstChild;
                newStartOffset = newEndOffset = newStartContainer.textContent.length;
              }
            }else{//strange so just set it to first syllable
              if (startNode = findNextGraGroupOrFreetext(e.target.firstChild)) {
                newStartContainer = newEndContainer = startNode.firstChild;
                newStartOffset = newEndOffset = 0;
              }
            }
          } else if (e.target.nodeName == 'DIV' // user end of line click or range issue in browser
                     && e.target.className.match(/freetext/) != null) {
            //alert(" clicked on freetext field under constrution");
           // $(".selected",ednVE.contentDiv).removeClass("selected");
          DEBUG.trace("ednVE mousup","4 "+muTime);
            ednVE.createEditFreeTextLineUI($(e.target));
            e.stopImmediatePropagation();
            mdTarget = mmTarget = null;
            return false;
          } else if (useTarget) {// startContainer is selected different from target
            startNode = eTarget;
          } else if (startContainer.nodeName == "#text") {// text click case (usual case)
            startNode = startContainer.parentNode;
          } else { // element click case
            startNode = startContainer;
          }
          if (startNode && startNode.nodeName == "SPAN") {
            classes = startNode.className;
            if (classes.indexOf("grpGra") == -1) {//not at syllable need to adjust
              if (classes.indexOf("boundary") > -1) {//Boundary case
                //use offset to determine syllable.
                if (startOffset == 1 &&
                    (selectNode = findNextGraGroupOrFreetext(startNode))) { // find next syllables first text position
                  newStartContainer = newEndContainer = selectNode.firstChild;
                  newStartOffset = newEndOffset = 0;
                } else if (selectNode = findPrevGraGroupOrFreetext(startNode)){ // find previous grapheme groups last text position
                  newStartContainer = newEndContainer = selectNode.firstChild;
                  newStartOffset = newEndOffset = newStartContainer.textContent.length;
                }
              } else if (classes.indexOf("TCM") > -1) {//TCM case
                //use parent.childNode position to find nearest text node
                //with TCM offset directing intra syllable cases
                if ((classes.indexOf("ord0") > -1) &&
                    (selectNode = findNextGraGroupOrFreetext(startNode))) {//TCM at start of syllable find next grapheme group
          DEBUG.trace("ednVE mousup","5 "+muTime);
                  newStartContainer = newEndContainer = selectNode.firstChild;
                  newStartOffset = newEndOffset = 0;
                } else if (selectNode = findPrevGraGroupOrFreetext(startNode)){ // find previous grapheme groups last text position
                  newStartContainer = newEndContainer = selectNode.firstChild;
                  newStartOffset = newEndOffset = newStartContainer.textContent.length;
                }
              } else if (classes.indexOf("linebreak") > -1) {//linebreak case
                if (selectNode = findPrevGraGroupOrFreetext(startNode)){
                  newStartContainer = newEndContainer = selectNode.firstChild;
                  newStartOffset = newEndOffset = newStartContainer.textContent.length;
                }
              } else {
                //error case unsupport selection node
                DEBUG.log("warn","mouse up on element " + classes + " and is not a supported node type");
                return;
              }
              selectNode = $(selectNode);
            } else {// syllable
              selectNode = $(startNode);
            }
            sclID = selectNode.get(0).className.match(/scl(\d+)/)[1];
          } else if (startNode && startNode.nodeName == "DIV" && startNode.className.match(/freetext/)) {
          DEBUG.trace("ednVE mousup","6 "+muTime);
            sclID = null;  // todo invalidate VSE ??
            selectNode = $(startNode);
          } else {
            //error case unknown node type
            DEBUG.log("warn","mouse up on element " + classes + " and is not a supported node type");
            return;
          }
          range = sRange.cloneRange();
        } else {
          /*****************************selection cases***********************************/
          //from start node (end node is start if reverse selection) find first text position
          // skipping boundaries and TCMs and adjust range accordingly
          //test end node is within syllable adjusting as needed to position to last text position
          // in this syllable that is less than or equal to end node position.
          if (!isReverseSelection){ // forward selection case

            //move start to next nearest grpGra
            if (startContainer.nodeName == 'SPAN' && startContainer.className.match(/textDivHeader/)) {
              //choose first syllable on line next('.grpGra')
          DEBUG.trace("ednVE mousup","7");
              if (startNode = findNextGraGroupOrFreetext(startContainer)) {
                newStartContainer = startNode.firstChild;
                newStartOffset = 0;
              }
            } else if (startContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                       && startContainer.id.match(/textContent/) != null) {
              // check range offset which should indicate the index of the closest childnode
          DEBUG.trace("ednVE mousup","8");
              if (startOffset > 0 && startOffset <= startContainer.childNodes.length &&
                (startNode = findPrevGraGroupOrFreetext(startContainer.childNodes[startOffset]))) {
                  newStartContainer = startNode.firstChild;
                  newStartOffset = newStartContainer.textContent.length;
              }else if (startNode = findNextGraGroupOrFreetext(startContainer.firstChild)) {//strange so just set it to first syllable
                newStartContainer = startNode.firstChild;
                newStartOffset = 0;
              }
            } else if (startContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                     && startContainer.className.match(/freetext/) != null) {
          DEBUG.trace("ednVE mousup","9");
              ednVE.createEditFreeTextLineUI($(startContainer));
//              alert("selection started on freetext field - under constrution");
              e.stopImmediatePropagation();
              return false;
            } else if (startContainer.nodeName == "#text") {// text click case (usual case)
              if (startOffset == startContainer.textContent.length) { //at the end of previous syllable
                startNode = findNextGraGroupOrFreetext(startContainer.parentNode);
                newStartContainer = startNode.firstChild;
                newStartOffset = 0;
              } else {
                startNode = startContainer.parentNode;
              }
            } else { // element click case
              startNode = startContainer;
            }

            //move end to prev nearest grpGra
            if (endContainer.nodeName == 'SPAN' && endContainer.className.match(/textDivHeader/)) {
              //choose first syllable on line next('.grpGra')
          DEBUG.trace("ednVE mousup","10");
              if (endNode = findNextGraGroupOrFreetext(endContainer)) {
                newEndContainer = endNode.firstChild;
                newEndOffset = 0;
              }
            } else if (endContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                       && endContainer.id.match(/textContent/) != null) {
          DEBUG.trace("ednVE mousup","11");
              // check range offset which should indicate the index of the closest childnode
              if (endOffset > 0 && endOffset <= endContainer.childNodes.length &&
                    (endNode = findPrevGraGroupOrFreetext(endContainer.childNodes[endOffset]))) {
                newEndContainer = endNode.firstChild;
                newEndOffset = endNode.textContent.length;
              }else if (endNode = findPrevGraGroupOrFreetext(endContainer.lastChild)) {//strange so just set it to last syllable
                newEndContainer = endNode.firstChild;
                newEndOffset = endNode.textContent.length;
              }
            } else if (endContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                     && endContainer.className.match(/freetext/) != null) {
          DEBUG.trace("ednVE mousup","12");
              ednVE.createEditFreeTextLineUI($(endContainer));
//              alert("selection ended on freetext field - under constrution");
              e.stopImmediatePropagation();
              return false;
            } else if (endContainer.nodeName == "#text") {
              endNode = endContainer.parentNode;
            } else {
              endNode = endContainer;
            }
            if (startNode) {
              classes = startNode.className;
              if (classes.indexOf("grpGra") == -1) {//not at syllable need to adjust
                if (classes.indexOf("boundary") > -1 //Boundary case
                    || classes.indexOf("TCM") > -1 //TCM case
                    || classes.indexOf("textDivHeader") > -1 //Header case
                    || classes.indexOf("freetext") > -1 //freetext case
                    || classes.indexOf("linebreak") > -1) {//linebreak case
                  //forward selection say move to first text position of syllable to right.
                  if (startNode = findNextGraGroupOrFreetext(startNode)) {
                    selectNode = $(startNode);
                    newStartContainer = startNode.childNodes[0];
                    newStartOffset = 0;
                  }
                } else {
                  //error case unsupport selection node
                  DEBUG.log("gen","processing mouse up for selection startnode on element " + classes + " and is not a supported node type");
                  //select first grpGra
                  startNode = $(ednVE.contentDiv).find(".grpGra:first").get(0);
                  selectNode = $(startNode);
                  newStartContainer = startNode.firstChild;
                  newStartOffset = 0;
                }
              } else {// syllable
                selectNode = $(startNode);
                if (startNode == startContainer) { //need to set container to text and first
                  newStartContainer = startNode.firstChild;
                  newStartOffset = 0;
                }
              }
            }
            sclID = selectNode.get(0).className.match(/scl(\d+)/);
            if (sclID) {
              sclID = sclID[1];
            } else {
              sclID = null;
            }
            selectOrdPos = selectNode.get(0).className.match(/ord(\d+)/);
            if (selectOrdPos) {
              selectOrdPos = parseInt(selectOrdPos[1]);
            } else {
              selectOrdPos = null;
            }
            //fixup end of selection
            classes = endNode.className;
            if (classes.indexOf("grpGra") == -1) {//not at syllable need to adjust
              if (classes.indexOf("boundary") > -1 //Boundary case
                  || (classes.indexOf("textDivHeader") > -1 && classes.indexOf("startHeader") == -1 ) //TCM case
                  || classes.indexOf("freetext") > -1 //freetext case
                  || classes.indexOf("TCM") > -1 //TCM case
                  || classes.indexOf("linebreak") > -1) {//linebreak case
                //forward selection so move to first text position of syllable to right.
                endNode = findPrevGraGroupOrFreetext(endNode);
              } else {
          DEBUG.trace("ednVE mousup","13");
                //error case unsupport selection node
                DEBUG.log("gen","processing mouse up for selection startnode on element " + classes + " and is not a supported node type");
                //select first grpGra
                endNode = $(ednVE.contentDiv).find(".grpGra:first").get(0);
              }
            }
            if (endNode) {
              //if not same syllable need to contract to end of selectNode
              endOrdPos = endNode.className.match(/ord(\d+)/);
              if (endOrdPos) {
                endOrdPos = parseInt(endOrdPos[1]);
              } else {
                endOrdPos = null;
              }
              if (endOrdPos > selectOrdPos &&
                  sclID != endNode.className.match(/scl(\d+)/)[1]) { //syllable to right of select syllable so end is last text of syllable
                  //get the last group for this sclID and set offset to end of text
                  grps = $('.grpGra.scl'+sclID,ednVE.contentDiv);
                  newEndContainer = grps.get(grps.length-1).firstChild;
                  newEndOffset = newEndContainer.textContent.length;
              } else if (endOrdPos < selectOrdPos) {
                DEBUG.log("err","ERROR:processing mouse up for forward selection endnode on element " + classes + " with end node before start");
              } else {// syllable is the same syllable so leave as is
                if (endNode == endContainer) { //need to set container to text and last
                  newEndContainer = endNode.lastChild;
                  newEndOffset = newEndContainer.textContent.length;
                }
              }
            }
            range = sRange.cloneRange();
          } else { // *****************  reverse selection case ******************

            if (endContainer.nodeName == 'SPAN' && endContainer.className.match(/textDivHeader/)) {
              if (endContainer.className.match(/firstHeader/)) {
                startNode = $(ednVE.contentDiv).find(".grpGra:first").get(0);
              } else {
                startNode = findPrevGraGroupOrFreetext(endContainer);
              }
              if (startNode) {
                newStartContainer = startNode.firstChild;
                newStartOffset = 0;
              }
            } else if (endContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                       && endContainer.id.match(/textContent/) != null) {
              // check range offset which should indicate the index of the closest childnode
              if (endOffset > 0 && endOffset <= endContainer.childNodes.length &&
                (startNode = findPrevGraGroupOrFreetext(endContainer.childNodes[endOffset]))) {
                  newStartContainer = startNode.firstChild;
                  newStartOffset = newStartContainer.textContent.length;
              }else if (startNode = findNextGraGroupOrFreetext(endContainer.firstChild)) {//strange so just set it to first syllable
                newStartContainer = startNode.firstChild;
                newStartOffset = newStartContainer.textContent.length;
              }
            } else if (endContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                     && endContainer.className.match(/freetext/) != null) {
//              alert(" ended reverse selection on freetext field - under constrution");
//              return;
          DEBUG.trace("ednVE mousup","14");
              ednVE.createEditFreeTextLineUI($(endContainer));
//              alert("selection ended on freetext field - under constrution");
              e.stopImmediatePropagation();
              return false;
            } else if (endContainer.nodeName == "#text") {// text click case (usual case)
              startNode = endContainer.parentNode;
            } else { // element click case
              startNode = endContainer;
            }

            //move end (range start) to next nearest grpGra
            if (startContainer.nodeName == 'SPAN' && startContainer.className.match(/textDivHeader/)) {
              //choose first syllable on line next('.grpGra')
              if (endNode = findNextGraGroupOrFreetext(startContainer)) {
                newEndContainer = endNode.firstChild;
                newEndOffset = 0;
              }
            } else if (startContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                       && startContainer.id.match(/textContent/) != null) {
              // check range offset which should indicate the index of the closest childnode
              if (startOffset > 0 && startOffset <= startContainer.childNodes.length &&
                    (endNode = findPrevGraGroupOrFreetext(startContainer.childNodes[startOffset]))) {
                newEndContainer = endNode.firstChild;
                newEndOffset = 0;
              }else if (endNode = findPrevGraGroupOrFreetext(startContainer.lastChild)) {//strange so just set it to last syllable
                newEndContainer = endNode.firstChild;
                newEndOffset = endNode.textContent.length;
              }
            } else if (startContainer.nodeName == 'DIV' // user end of line click or range issue in browser
                     && startContainer.className.match(/freetext/) != null) {
//              alert(" started reverse selection on freetext field - under constrution");
//              return;
          DEBUG.trace("ednVE mousup","15");
              ednVE.createEditFreeTextLineUI($(startContainer));
//              alert("selection ended on freetext field - under constrution");
              e.stopImmediatePropagation();
              return false;
            } else if (startContainer.nodeName == "#text") {
              endNode = startContainer.parentNode;
            } else {
              endNode = startContainer;
            }

            if (startNode) {
              classes = startNode.className;
              if (classes.indexOf("grpGra") == -1) {//not at syllable need to adjust
                if (classes.indexOf("boundary") > -1 //Boundary case
                    || classes.indexOf("freetext") > -1 //freetext case
                    || classes.indexOf("TCM") > -1 //TCM case
                    ||(classes.indexOf("textDivHeader") > -1 && classes.indexOf("startHeader") == -1 ) //Header case
                    || classes.indexOf("linebreak") > -1) {//linebreak case
                  //forward selection say move to first text position of syllable to right.
                  if (startNode = findPrevGraGroupOrFreetext(startNode)) {
                    selectNode = $(startNode);
                    newStartContainer = startNode.firstChild;
                    newStartOffset = 0;
                  }
                } else {
                  //error case unsupport selection node
                  DEBUG.log("gen","processing mouse up for selection startnode on element " + classes + " and is not a supported node type");
                  //select first grpGra
                  startNode = $(ednVE.contentDiv).find(".grpGra:first").get(0);
                  selectNode = $(startNode);
                  newStartContainer = startNode.firstChild;
                  newStartOffset = 0;
                }
              } else {// syllable
                selectNode = $(startNode);
                if (startNode == startContainer) { //need to set container to text and first
                  newStartContainer = startNode.firstChild;
                  newStartOffset = 0;
                }
              }
            }

            sclID = selectNode.get(0).className.match(/scl(\d+)/);
            if (sclID) {
              sclID = parseInt(sclID[1]);
            } else {
              sclID = null;
            }
            selectOrdPos = selectNode.get(0).className.match(/ord(\d+)/);
            if (selectOrdPos) {
              selectOrdPos = parseInt(selectOrdPos[1]);
            } else {
              selectOrdPos = null;
            }
            //fixup end of selection
            classes = endNode.className;
            if (classes.indexOf("grpGra") == -1) {//not at syllable need to adjust
              if (classes.indexOf("boundary") > -1 //Boundary case
                  || classes.indexOf("textDivHeader") > -1 //TCM case
                  || classes.indexOf("freetext") > -1 //freetext case
                  || classes.indexOf("TCM") > -1 //TCM case
                  || classes.indexOf("linebreak") > -1) {//linebreak case
                //forward selection say move to first text position of syllable to right.
                endNode = findPrevGraGroupOrFreetext(endNode);
              } else {
                //error case unsupport selection node
                DEBUG.log("warn","processing mouse up for selection endnode on element " + classes + " and is not a supported node type");
                //select first grpGra
                endNode = $(ednVE.contentDiv).find(".grpGra:first").get(0);
              }
              newEndOffset = 0;
            }
            if (endNode) {
              //if not same syllable need to contract to end of selectNode
              ord =endNode.className.match(/ord(\d+)/);
              endOrdPos = (ord?parseInt(ord[1]):null);
              if (endOrdPos < selectOrdPos &&
                  sclID != endNode.className.match(/scl(\d+)/)[1]) { //syllable to right of select syllable so end is last text of syllable
                  //get the last group for this sclID and set offset to end of text
                  grps = $('.grpGra.scl'+sclID,ednVE.contentDiv);
                  newEndContainer = grps.get(0).firstChild;
                  newEndOffset = 0;
              }else if (endOrdPos > selectOrdPos) {
                DEBUG.log("err","ERROR:processing mouse up for reverse selection endnode on element " + classes);
              }else {// syllable is the same syllable so leave as is
                if (endNode == endContainer) { //need to set container to text and first
                  newEndContainer = endNode.firstChild;
                  newEndOffset = 0;
                }
              }
            }
            range = eRange.cloneRange();
          } // end if reverse selection
        } // end selection case
        //apply any new selection
        if (sel && (newStartContainer || newEndContainer)) {
          if ( newStartContainer ) {
            DEBUG.log("nav","newStartContainer in sclmouseup "+newStartContainer.textContent);
            if( isReverseSelection ) {
              range.setEnd(newStartContainer,newStartOffset);
              otherContainer = range.startContainer;
            } else {
              range.setStart(newStartContainer,newStartOffset);
              otherContainer = range.endContainer;
            }
            if (!newEndContainer && //check to see if range (clone) need its other node adjusted
                endContainer != otherContainer ) {
              if( isReverseSelection ) {
                range.setStart(endContainer,endOffset);
              } else {
                range.setEnd(endContainer,endOffset);
              }
            }
          }
          if ( newEndContainer ) {
            DEBUG.log("nav","newEndContainer in sclmouseup "+newEndContainer.textContent);
            if( isReverseSelection ) {
              range.setStart(newEndContainer,newEndOffset);
              otherContainer = range.endContainer;
            } else {
              range.setEnd(newEndContainer,newEndOffset);
              otherContainer = range.startContainer;
            }
            if (!newStartContainer && //check to see if range (clone) need its other node adjusted
                  startContainer != otherContainer ) {
              if( isReverseSelection ) {
                range.setEnd(startContainer,startOffset);
              } else {
                range.setStart(startContainer,startOffset);
              }
            }
          }
        }
        if (sel && (newStartContainer || newEndContainer)) {
          sel.removeAllRanges();
          sel.addRange(range);
        }
        if(selectNode && (!selectNode.hasClass("selected") || $(".selected", ednVE.contentDiv).length > 1)) {
          $(".selected", ednVE.contentDiv).removeClass("selected");
          $(".grpGra.scl"+sclID,ednVE.contentDiv).addClass("selected");
          //if link mode trigger link response
          if (ednVE.linkMode) {
            $('.editContainer').trigger('linkResponse',[ednVE.id,"scl"+sclID]);
          } else { // trigger selection change
            $('.editContainer').trigger('updateselection',[ednVE.id,["scl"+sclID]]);
          }
        }
        if (selectNode.hasClass("freetext") && !selectNode.hasClass("selected")) {
          ednVE.createEditFreeTextLineUI(selectNode);
        } else if (ednVE.sclEd) {
          if (ednVE.sclEd.dirty) {
            ednVE.sclEd.saving = true;
            ednVE.sclEd.save(function(sclIDSaved){
                        var sclIDSelect = sclID?sclID:sclIDSaved,
                            selectNode = $(".grpGra.scl"+sclIDSelect+":first",ednVE.contentDiv);
                        ednVE.sclEd.saving = false;
                        ednVE.sclEd.dirty = false;
                        ednVE.removeFreeTextLineUI();
                        ednVE.sclEd.init(selectNode);
                      },
                      function(errStr) {
                        ednVE.sclEd.saving = false;
                        alert("Error from navigation while trying to save current syllable - "+errStr);
                      });
          } else {
            ednVE.removeFreeTextLineUI();
            if (sclID != ednVE.sclEd.sclID) {
              ednVE.sclEd.init(selectNode);
            } else {
              ednVE.sclEd.synchSelection();
//              ednVE.sclEd.calculateState();
            }
          }
        }
        e.stopImmediatePropagation();
        mdTarget = null;
        mmTarget = null;
        return false;
      } else if (ednVE.editMode == "tcm"){
        $(".selected",ednVE.contentDiv).removeClass("selected");
        if (!ednVE.tcmEd) {
          edVE.tcmEd = new EDITOR.tcmEditor(ednVE);
        } else {
          ednVE.tcmEd.moveToSelection();
        }
        e.stopImmediatePropagation();
        mdTarget = null;
        mmTarget = null;
        return false;
      } else if (ednVE.structLinkMode) {
        selectIDs = ednVE.getSelectionEntityGIDs();
        if (selectIDs && selectIDs.length){
          $('.editContainer').trigger('structurelinkresponse',[ednVE.id,ednVE.edition.id,ednVE.structLinkMode,selectIDs]);
        }
        ednVE.structLinkMode = null;
      } else if (!e.ctrlKey && !(eTarget.id && eTarget.id.match(/textContent/) && sRange.commonAncestorContainer == sRange.startContainer)) {
        $(".selected",ednVE.contentDiv).removeClass("selected");
        $('.editContainer').trigger('updateselection',[ednVE.id,[""]]);
      }
    };

    //assign handler for all syllable elements
    $("span,div.freetext,#"+this.id+"textContent", this.editDiv).unbind("mouseup").bind("mouseup", sclMouseUpHandler);


/**
* handle 'scroll' event for content div
*
* @param object e System event object
*
* @returns true|false
*/

    function veScrollHandler(e) {
      var top = this.scrollTop, i=1, maxLine = 800, grpGra, segTag, lineFraction, imgScrollData;
      if (!ednVE.supressSynchOnce) {
        DEBUG.log("event","scroll editionVE");
        if ($('body').hasClass('synchScroll')) {
          for (i; i<maxLine; i++) {
            grpGra = $(this).find('.grpGra.ordL'+i+':first');
            if (grpGra && grpGra.length ==1) {
              grpGra = grpGra.get(0);
              if (grpGra.offsetTop <= top && grpGra.offsetTop + grpGra.offsetHeight > top) {
                segTag = grpGra.className.match(/seg\d+/)[0];
                if (segTag) {//sync only if segID
                  lineFraction = (top - grpGra.offsetTop)/grpGra.offsetHeight;
                  imgScrollData = ednVE.getImageScrollData(segTag,lineFraction);
                  $('.editContainer').trigger('synchronize',[ednVE.id,segTag,lineFraction, imgScrollData]);
                }
                break;
              }
            }
          }
        }
      } else {
        delete ednVE.supressSynchOnce;
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all syllable elements
    this.contentDiv.unbind("scroll").bind("scroll", veScrollHandler);


    /**
    * handle 'synchronize' event for edit div
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param int anchorSegID Segment entity id of anchor segemnt
    * @param number visFraction Fraction of display viewed
    */

    function synchronizeHandler(e,senderID, anchorSegID, visFraction) {
      var top, grpGra;
      if (senderID == ednVE.id) {
        return;
      }
      DEBUG.log("event","synch request recieved by "+ednVE.id+" from "+senderID+" with segID "+ anchorSegID + (visFraction?" with fraction" + visFraction:""));
      grpGra = $('.'+anchorSegID+':first',this);
      if (grpGra && grpGra.length ==1) {
        grpGra = grpGra.get(0);
        top = Math.round(grpGra.offsetHeight * visFraction) + grpGra.offsetTop;
        ednVE.supressSynchOnce = true;
        ednVE.contentDiv.scrollTop(top);
      }
    };

    $(this.editDiv).unbind('synchronize').bind('synchronize', synchronizeHandler);


    /**
    * handle 'baselineLoaded' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string blnID Identifies the baseline loaded
    */

    function baselineLoadedHandler(e,senderID, blnID) {
      if (senderID == ednVE.id) {
        return;
      }
      var baseline = ednVE.dataMgr.getEntity('bln',blnID);
      DEBUG.log("event","baselineLoaded received by editionVE in "+ednVE.id+" from "+senderID+" with blnID "+ blnID);
      if (baseline && baseline.textIDs &&
          baseline.textIDs.length &&
          baseline.textIDs.indexOf(ednVE.edition.txtID) > -1) {
        ednVE.calcLineScrollDataBySegLookups();
      }
    };

    $(this.editDiv).unbind('baselineLoaded').bind('baselineLoaded', baselineLoadedHandler);


/**
* set syllable selection
*
* @param node sylStart Start syllable grapheme group element
* @param int charSOffset Character offset within the start grapheme group
* @param node sylEnd syllable grapheme group element
* @param int charEOffset Character offset within the end grapheme group
*/

    function sclSetSelection(sylStart,charSOffset,sylEnd,charEOffset) {
      var sOffset,eOffset,range, sel, childNodes, i;
      if (sylStart && sylStart.className.match(/grpGra/) ||
          sylEnd && sylEnd.className.match(/grpGra/)) {
        if (window.getSelection) {
          sel = window.getSelection();
          if (sel.getRangeAt && sel.rangeCount) {
            range = sel.getRangeAt(0);
          }
        } else if (document.selection && document.selection.createRange) {
          range = document.selection.createRange();
        }
        if (sylStart) {
          childNodes = sylStart.childNodes;
          sOffset == parseInt(charSOffset);
          for(i=0; i < childNodes.length; i++){
            if (childNodes[i].nodeName != "#text"){//skip TCM nodes
              continue;
            } else if (charSOffset == "start") {//found first text child, use position of 0
              range.setStart(childNodes[i],0);
              DEBUG.log("gen","changing range start to '"+childNodes[i].textContent+"' at pos "+0);
              break;
            } else if (charSOffset == "end" && i == (childNodes.length - 1)) {//found last text child, use position of length
              range.setStart(childNodes[i],childNodes[i].textContent.length);
              DEBUG.log("gen","changing range start to '"+childNodes[i].textContent+"' at pos "+childNodes[i].textContent.length);
              break;
            } else if (childNodes[i].textContent.length < sOffset) {//pos is not in this childnode
              sOffset -= childNodes[i].textContent.length;
            } else {//found child and pos is offset
              range.setStart(childNodes[i],sOffset);
              DEBUG.log("gen","changing range start to '"+childNodes[i].textContent+"' at pos "+sOffset);
              break;
            }
          }
        }
        if (sylEnd) {
          childNodes = sylEnd.childNodes;
          eOffset == parseInt(charEOffset);
          for(i=0; i < childNodes.length; i++){
            if (childNodes[i].nodeName != "#text"){//skip TCM nodes
              continue;
            } else if (charEOffset == "start") {//found first text child, use position of 0
              range.setEnd(childNodes[i],0);
              DEBUG.log("gen","changing range start to '"+childNodes[i].textContent+"' at pos "+0);
              break;
            } else if (charEOffset == "end" && i == (childNodes.length - 1)) {//found last text child, use position of length
              range.setEnd(childNodes[i],childNodes[i].textContent.length);
              DEBUG.log("gen","changing range start to '"+childNodes[i].textContent+"' at pos "+childNodes[i].textContent.length);
              break;
            } else if (childNodes[i].textContent.length < eOffset) {//pos is not in this childnode
              eOffset -= childNodes[i].textContent.length;
            } else {//found child and pos is offset
              range.setEnd(childNodes[i],eOffset);
              DEBUG.log("gen","changing range start to '"+childNodes[i].textContent+"' at pos "+eOffset);
              break;
            }
          }
        }
        if (sel) {
          sel.removeAllRanges();
          sel.addRange(range);
        }
      }
    }
    this.attachFootnoteClickHandlers();
  },


/**
* remove freetext line view/edit UI
*
*/

  removeFreeTextLineUI: function() {
    var ednVE = this;
    $('.freetextInput',ednVE.contentDiv).each(function(index,elem) {
      var $freeTextInput = $(elem), $freeTextDiv = $freeTextInput.parent(),
         classes = $freeTextDiv.attr('class'),
         freeTextLineSeqID = classes.match(/lineseq(\d+)/)[1],
         strFreetext=$freeTextInput.val();
      if (freeTextLineSeqID) {
        freeTextLineSequence = ednVE.dataMgr.getEntity('seq',freeTextLineSeqID);
        if (freeTextLineSequence) {
          if (freeTextLineSequence.freetext == strFreetext){//input is same as saved on server
            if ($freeTextDiv.hasClass('dirty')) {
              $freeTextDiv.removeClass('dirty');
            }
          } else if (strFreetext.length) {
            if (!$freeTextDiv.hasClass('dirty')) {
              $freeTextDiv.addClass('dirty');
            }
            if ($freeTextDiv.hasClass('error')) {
              $freeTextDiv.removeClass('error');
            }
            if ($freeTextDiv.hasClass('valid')) {
              $freeTextDiv.removeClass('valid');
            }
            if (freeTextLineSequence.errorMsg) {
              delete freeTextLineSequence.errorMsg;
            }
          } else {// blank line or spaces
            if (!$freeTextDiv.hasClass('error')) {
              $freeTextDiv.addClass('error');
            }
            if ($freeTextDiv.hasClass('dirty')) {
              $freeTextDiv.removeClass('dirty');
            }
            if ($freeTextDiv.hasClass('valid')) {
              $freeTextDiv.removeClass('valid');
            }
            freeTextLineSequence.errorMsg = "blank or space only line text not supported, please enter …";
          }
          freeTextLineSequence.localfreetext = strFreetext;
        }
      }
      //set FreeText line display
      if (!strFreetext || strFreetext.trim() == "") {
        $freeTextDiv.html("blank or space only line text not supported, please enter …");
      } else {
        $freeTextDiv.html(strFreetext);
      }
    });
  },


/**
* create freetext line view/edit UI
*
* @param node freeTextDiv Html div used to contain the freetext UI
*/

  createEditFreeTextLineUI: function($freeTextDiv) {
    var ednVE = this, classes = $freeTextDiv.attr('class'), validateData, sel, range, selElem,
        $freeTextInput, validateBtn, errMsgDiv, resultsTable, lostFocusToValidate = false,
        ord = classes.match(/ordL(\d+)/)[1],html, freeTextLineSequence,tranlit,//errXoffset = 10, errYoffset=10,
        freeTextLineSeqID = classes.match(/lineseq(\d+)/)[1];
    ednVE.removeFreeTextLineUI();
    if (freeTextLineSeqID) {
      freeTextLineSequence = ednVE.dataMgr.getEntity('seq',freeTextLineSeqID);
      if ( typeof freeTextLineSequence.localfreetext == 'undefined') {//first time
        freeTextLineSequence.localfreetext = freeTextLineSequence.freetext;
        if (freeTextLineSequence.validationMsg) {
          freeTextLineSequence.errorMsg = decodeURIComponent(freeTextLineSequence.validationMsg);
          $freeTextDiv.addClass('error');
        }
      } else {
        if (freeTextLineSequence.localfreetext != freeTextLineSequence.freetext) {
          if (freeTextLineSequence.localfreetext &&
              freeTextLineSequence.localfreetext.trim().length &&
              freeTextLineSequence.errorMsg) {
            delete freeTextLineSequence.errorMsg;
            $freeTextDiv.addClass('dirty');
            $freeTextDiv.removeClass('error');
            $freeTextDiv.removeClass('valid');
          }
        }
        if (freeTextLineSequence.errorMsg) {
          $freeTextDiv.addClass('error');
          $freeTextDiv.removeClass('dirty');
          $freeTextDiv.removeClass('valid');
        }
      }
      errMsg = (freeTextLineSequence.errorMsg?freeTextLineSequence.errorMsg:"");
      html = '<input class="freetextInput" contenteditable="true" value="'+freeTextLineSequence.localfreetext+'" />'+
             '<button class="validateButton" >Validate</button>'+
             '<button class="commitButton" >Commit</button>'+
             '<div class="validationError">'+errMsg+'</div>';
      $freeTextDiv.html(html);
      $freeTextInput = $('.freetextInput',$freeTextDiv);
      validateBtn = $('.validateButton',$freeTextDiv);
      commitBtn = $('.commitButton',$freeTextDiv);
      errMsgDiv = $('.validationError',$freeTextDiv);
      $freeTextInput.unbind('keydown').bind('keydown', function(e) {
        e.stopImmediatePropagation();
      });
      $freeTextInput.unbind('keypress').bind('keypress', function(e) {
        $freeTextDiv.removeClass('valid');
        e.stopImmediatePropagation();
      });
      $freeTextInput.unbind('keyup').bind('keyup', function(e) {
        //if up arrow then navigate to the first syllable of the prev line, down arrow the next line
        // or if line is freetext then invoke edit UI
        //retrack this line to just display string.
        e.stopImmediatePropagation();
      });
      $freeTextInput.unbind('paste').bind('paste', function(e) {
        e.stopImmediatePropagation();
      });
      $freeTextInput.unbind('mousedown').bind('mousedown', function(e) {
        e.stopImmediatePropagation();
      });
      $freeTextInput.unbind('mouseup').bind('mouseup', function(e) {
        $freeTextInput.focus();
        e.stopImmediatePropagation();
      });
      $freeTextInput.unbind('change').bind('change', function(e) {
        $freeTextInput.focus();
        e.stopImmediatePropagation();
      });
      $freeTextInput.surpressBlurOnce = true;
      $freeTextInput.unbind('blur').bind('blur', function(e) {
        var strFreetext = $freeTextInput.val();
        if ($freeTextInput.surpressBlurOnce){
          delete $freeTextInput.surpressBlurOnce;
          e.stopImmediatePropagation();
          return false;
        }
        freeTextLineSequence.localfreetext = $freeTextInput.val(); //temp solution TODO call save freetext service.
        if (!lostFocusToValidate) {
          if (!strFreetext || strFreetext.trim() == "") {
            $freeTextDiv.html("blank or space only line text not supported, please enter …");
          } else {
            $freeTextDiv.html(strFreetext);
          }
          if (freeTextLineSequence.freetext == strFreetext){//input is same as saved on server
            if ($freeTextDiv.hasClass('dirty')) {
              $freeTextDiv.removeClass('dirty');
            }
          } else if (strFreetext.length) {
            if (!$freeTextDiv.hasClass('dirty')) {
              $freeTextDiv.addClass('dirty');
            }
            if ($freeTextDiv.hasClass('error')) {
              $freeTextDiv.removeClass('error');
            }
            if ($freeTextDiv.hasClass('valid')) {
              $freeTextDiv.removeClass('valid');
            }
            if (freeTextLineSequence.errorMsg) {
              delete freeTextLineSequence.errorMsg;
            }
          } else {
            if (!$freeTextDiv.hasClass('error')) {
              $freeTextDiv.addClass('error');
            }
            if ($freeTextDiv.hasClass('dirty')) {
              $freeTextDiv.removeClass('dirty');
            }
            if ($freeTextDiv.hasClass('valid')) {
              $freeTextDiv.removeClass('valid');
            }
            freeTextLineSequence.errorMsg = "blank or space only line text not supported, please enter …";
          }
          freeTextLineSequence.localfreetext = strFreetext;
        } else {
          lostFocusToValidate = false;
        }
        e.stopImmediatePropagation();
      });
      errMsgDiv.unbind('mousedown').bind('mousedown', function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      errMsgDiv.unbind('mouseup').bind('mouseup', function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      validateBtn.unbind('mousedown').bind('mousedown', function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      validateBtn.unbind('mouseup').bind('mouseup', function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      validateBtn.unbind('click').bind('click', function(e) {
        var strFreetext = $freeTextInput.val();
        lostFocusToValidate = true;
        if (!strFreetext || strFreetext.trim() == "") {
          //remove any valid and/or dirty indicators
          if ($freeTextDiv.hasClass('valid')) {
            $freeTextDiv.removeClass('valid');
          }
          if ($freeTextDiv.hasClass('dirty')) {
            $freeTextDiv.removeClass('dirty');
          }
          //mark validationError
          if (!$freeTextDiv.hasClass('error')) {
            $freeTextDiv.addClass('error');
          }
          //and update error UI
          freeTextLineSequence.localfreetext = strFreetext;
          freeTextLineSequence.validationMsg = "blank or space only line text not supported, please enter …";
          errMsgDiv.html(freeTextLineSequence.validationMsg);
        } else {
          $freeTextInput.focus();
          validateData = { seqID: freeTextLineSeqID,
                           freetext: $freeTextInput.val()};
          $.ajax({
              type:"POST",
              dataType: 'json',
              url: basepath+'/services/validateFreeTextLine.php?db='+dbName,
              data: validateData,
              asynch: true,
              success: function (data, status, xhr) {
                var seqID = freeTextLineSeqID;
                if (typeof data == 'object') {
                  if (data.entities) {
                    //update data
                    ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                  }
                  if (data.success) {
                    if (!data.errString) {
                      //remove any error and/or dirty indicators
                      if ($freeTextDiv.hasClass('error')) {
                        $freeTextDiv.removeClass('error');
                      }
                      //mark as valid so commit btn will show
                      if (!$freeTextDiv.hasClass('valid')) {
                        $freeTextDiv.addClass('valid');
                      }
                      //ensure that seq.validationMsg was removed
                      errMsgDiv.html();
                    } else {
                      //remove any valid and/or dirty indicators
                      if ($freeTextDiv.hasClass('valid')) {
                        $freeTextDiv.removeClass('valid');
                      }
                      //mark validationError
                      if (!$freeTextDiv.hasClass('error')) {
                        $freeTextDiv.addClass('error');
                      }
                      //and update error UI
                      errMsgDiv.html(decodeURIComponent(data.errString));
                    }
                  }else if (data['errors']) {
                    alert("Error(s) occurred while trying to validate a freetext line. Error: " + data['errors'].join(" : "));
                  }
                  if (data.warnings) {
                    DEBUG.log("warn", "warnings during  validate freetext - " + data.warnings.join(" : "));
                  }
                }
              },// end success cb
              error: function (xhr,status,error) {
                  // add record failed.
                  errStr = "An error occurred while trying to call  validate freetext. Error: " + error;
              }
          });// end ajax
        }
        e.stopImmediatePropagation();
        return false;
      });
      commitBtn.unbind('mousedown').bind('mousedown', function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      commitBtn.unbind('mouseup').bind('mouseup', function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      commitBtn.unbind('click').bind('click', function(e) {
        $freeTextInput.focus();
        //unmark as valid so commit btn will hide
        if ($freeTextDiv.hasClass('valid')) {
          $freeTextDiv.removeClass('valid');
        }
        if (!$freeTextDiv.hasClass('saving')) {
          $freeTextDiv.addClass('saving');
        }

        commitData = { seqID: freeTextLineSeqID,
                       ednID: ednVE.edition.id};
        if (DEBUG.healthLogOn) {
          commitData['hlthLog'] = 1;
        }
        $.ajax({
            type:"POST",
            dataType: 'json',
            url: basepath+'/services/commitFreeTextLine.php?db='+dbName,
            data: commitData,
            asynch: true,
            success: function (data, status, xhr) {
              var seqID = freeTextLineSeqID, newTextDivSeqID,
                  firstSyllable, newSeqTag, index, tokCmpTag, tokCmpGID,
                  oldSeqTag;
              if (typeof data == 'object') {
                if ($freeTextDiv && $freeTextDiv.hasClass('saving')) {
                  $freeTextDiv.removeClass('saving');
                }        
                if (data.entities) {
                  //update local cache
                  ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                }
                if (data.success) {
                  //remove freetext and replace with New line physical
                  ednVE.calcTextDivGraphemeLookups(data.newTextDivSeqID);
                  ednVE.calcLineGraphemeLookups(freeTextLineSeqID);
                  ednVE.reRenderPhysLine('ordL'+ord,freeTextLineSeqID);
                  //refresh property display
                  ednVE.propMgr.showVE("","seq:"+freeTextLineSeqID);
                  //if split textdiv update previous and post lines as needed
                  if (data.replaceLbls) {
                    for (newSeqTag in data.replaceLbls) {
                      oldSeqTag = data.replaceLbls[newSeqTag]['oldSeqTag'];
                      for (index in data.replaceLbls[newSeqTag]['tokCmpGIDs']) {
                        tokCmpGID = data.replaceLbls[newSeqTag]['tokCmpGIDs'][index];
                        tokCmpTag = tokCmpGID.replace(':','');
                        $('.'+oldSeqTag+'.'+tokCmpTag).removeClass(oldSeqTag).addClass(newSeqTag);
                      }
                    }
                  }
                  //place VSE on first syllable of new line.
                  firstSyllable = $('.textDivHeader.lineseq'+freeTextLineSeqID,ednVE.editDiv).next();
                  while (!firstSyllable.hasClass('grpGra')) {
                    firstSyllable = firstSyllable.next();
                  }
                  ednVE.sclEd.init(firstSyllable);
                  if (data.editionHealth) {
                    DEBUG.log("health","***Commit Free Text Line***");
                    DEBUG.log("health","Params: "+JSON.stringify(commitData));
                    DEBUG.log("health",data.editionHealth);
                  }
                }else if (data['errors']) {
                  alert("Error(s) occurred while trying to validate a freetext line. Error: " + data['errors'].join(" : "));
                }
                if (data.warnings) {
                  DEBUG.log("warn", "warnings during  validate freetext - " + data.warnings.join(" : "));
                }
              }
            },// end success cb
            error: function (xhr,status,error) {
                // add record failed.
                errStr = "An error occurred while trying to call  validate freetext. Error: " + error;
            }
        });// end ajax
        e.stopImmediatePropagation();
        return false;
      });
      if (window.getSelection) {
        sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
          range = sel.getRangeAt(0);
        }
      } else if (document.selection && document.selection.createRange) {
        range = document.selection.createRange();
      }
      if (range) {
        selElem = $freeTextDiv.get(0);
        range.setStart(selElem,0);
        range.setEnd(selElem,0);
        if (sel) {
          sel.removeAllRanges();
          sel.addRange(range);
        }
      }
      $freeTextInput.focus();
    }
  },

  /*********************************** Edition display code ************************************************/
/**
* calculate all lookups for various render functions
*
*/
  calcEditionRenderMappings: function () {
    // for current edition determine sequences types
    var textSeq, textDivSeq, physicalSeq, lineSeq, tempSeq,
        textAnalysisSeq, textSubStructSeq, tcms = 'S',
        seqID, ednSeqIDs = this.edition.seqIDs,
        seqLK = this.dataMgr.entities['seq'],
        i,cnt;
    this.lookup = { 'gra':{}, 'scl':{}, 'tok':{}, 'cmp':{}, 'seq':{}};
    if (seqLK && Object.keys(seqLK).length && ednSeqIDs && ednSeqIDs.length) {
      cnt = ednSeqIDs.length;
      for (i=0; i<cnt; i++) {
        seqID = ednSeqIDs[i];
        tempSeq = seqLK[seqID];
        if (!tempSeq) {
          continue;
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'Text'){//warning!!!! id dependency edition text seq type
          if (!textSeq) {
           this.textSeq = textSeq = tempSeq;
          } else {//warn of having more than one text per edition
            DEBUG.log("warn","Found multiple Text sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'TextPhysical'){//warning!!!! id dependency edition physical seq type
          if (!physicalSeq) {
           this.physSeq = physicalSeq = tempSeq;
          } else {//warn against having more than one physical per edition
            DEBUG.log("warn","Found multiple physical sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'Analysis'){//warning!!!! id dependency edition structural seq type
          if (!textAnalysisSeq) {
           this.structSeq = textAnalysisSeq = tempSeq;
          } else {//warn against having more than one structure per edition
            DEBUG.log("warn","Found multiple structure sequences for edition "
                        +this.edition.value + " id="+this.id);
          }
        }
      }
    }
    // if text sequence
    if ( this.textSeq ) {
      var textDivSeqIDs = this.textSeq.entityIDs,
          j,textDiv,textDivSeqID, textDivGIDs;
      cnt = textDivSeqIDs.length;
      //for each text division
      for (var j=0; j<cnt; j++) {
        if (textDivSeqIDs[j] && textDivSeqIDs[j].indexOf('seq:') == 0) {//validate that this is a sequence
          textDivSeqID = (textDivSeqIDs[j]).substr(4);
          this.calcTextDivGraphemeLookups(textDivSeqID);
        }
      }
    }
    // if physical text sequence
    if ( this.physSeq ) {
      var physLineSeqIDs = this.physSeq.entityIDs;
      cnt = physLineSeqIDs.length;
      //for each physical line
      for (var j=0; j<cnt; j++) {
        if (physLineSeqIDs[j] && physLineSeqIDs[j].indexOf('seq:') == 0) {//validate that this is a sequence
          this.calcLineGraphemeLookups((physLineSeqIDs[j]).substr(4));
        }
      }
    }
  },

  /**
  *
  */

  /**
  * create data for positioning the image relative to the edition position
  *
  * @param segTag string indicating the segment tag of the first syllable on the top visible line
  * @param lineFraction float indicating the fraction of the top line showing
  */
  getImageScrollData : function(segTag,lineFraction) {
    var segID=segTag.substring(3), lineSegData = this.scrollDataBySegID[segID], imgData = {};
    if (lineSegData) {
      if (lineSegData.imgBlnID) { //on line with link to image baseline
        imgData['segID'] = segID;
        imgData['segHeightFactor'] = lineFraction;
      } else {//on line with transcription segment link translate to image segment
        if (lineSegData.prevImgSegID) {//anchor to previous image segment
          imgData['segID'] = lineSegData.prevImgSegID;
          if (lineSegData.nextImgSegID) {// calc percent delta between img Segment top position
            imgData['deltaSegID'] = lineSegData.nextImgSegID;
            //calc percent
            imgData['deltaFactor'] = (lineSegData.cntPrevLinesToImgSeg + lineFraction)/this.scrollDataBySegID[lineSegData.prevImgSegID].cntNextLinesToImgSeg;
            imgData['segHeightFactor'] = lineSegData.cntPrevLinesToImgSeg + lineFraction;
          } else {//segment after last image seg, so estimate a segment height delta to postion
            //calc add delta segHeight multiplier
            imgData['segHeightFactor'] = lineSegData.cntPrevLinesToImgSeg + lineFraction;
          }
        } else if (lineSegData.nextImgSegID) {//anchor to next image segment
          imgData['segID'] = lineSegData.nextImgSegID;
          //calc subtract delta segHeight multiplier
          imgData['segHeightFactor'] = lineSegData.cntToFirstLineSeg - this.scrollDataBySegID[lineSegData.nextImgSegID].cntToFirstLineSeg + lineFraction;
        } else {// no ref image segment so do not sync image
          return null;
        }
      }
      return imgData;
    }
    return null;
  },

  /**
  * calculate scroll data by segID lookup to maintain the data need to calculate the next position
  *
  */
  calcLineScrollDataBySegLookups : function() {
    if ( this.physSeq && this.physSeq.entityIDs && this.physSeq.entityIDs.length) {
      var physLineSeqIDs = this.physSeq.entityIDs,
          cnt = physLineSeqIDs.length, i, j, segID, lastSegID, prevImgSegID,
          lineSeq, segment, scrollDataIndex,
          blnID, syllable, lineCnt = 0, imgSegLineCnt = 0;
          this.scrollDataBySegID = {};
      //for each physical line find the first syllable's segment
      for (var j=0; j<cnt; j++) {
        if (physLineSeqIDs[j] && physLineSeqIDs[j].indexOf('seq:') == 0) {//validate that this is a sequence
          lineSeq = this.dataMgr.getEntityFromGID(physLineSeqIDs[j]);
          if (lineSeq && lineSeq.entityIDs && lineSeq.entityIDs.length) {
            syllable = this.dataMgr.getEntityFromGID(lineSeq.entityIDs[0]);
            if (syllable.segID) {
              segID = syllable.segID;
              this.scrollDataBySegID[segID] = {
                index:j+1,
                cntPrevLinesToSeg:lineCnt
              };
              if (prevImgSegID) {
                this.scrollDataBySegID[segID]['prevImgSegID'] = prevImgSegID;
                this.scrollDataBySegID[prevImgSegID]['nextImgSegID'] = segID;
                this.scrollDataBySegID[segID]['cntPrevLinesToImgSeg'] = imgSegLineCnt;
              } else {//no image segs before this, could be transcription segs
                this.scrollDataBySegID[segID]['cntToFirstLineSeg'] = imgSegLineCnt;
              }
              segment =  this.dataMgr.getEntity('seg',segID);
              if (segment && segment.boundary) {//img segment link
                if (prevImgSegID) {
                  this.scrollDataBySegID[prevImgSegID]['cntNextLinesToImgSeg'] = imgSegLineCnt;
                } else {//patch the previous segs to point to this imgSeg
                  for (scrollDataIndex in this.scrollDataBySegID) {
                    if ( segID != scrollDataIndex) {
                      this.scrollDataBySegID[scrollDataIndex]["nextImgSegID"] = segID;
                    }
                  }
                }
                this.scrollDataBySegID[segID]['imgBlnID'] = segment.baselineIDs[0]; //warning doesn't handle split segments
                imgSegLineCnt = 0;
                prevImgSegID = segID;
              }
              imgSegLineCnt++;
              lineCnt = 0;
            } else { //no seg so count line and skip
              lineCnt++;
              imgSegLineCnt++;
            }
          } else { //no syllables so count line and skip
            lineCnt++;
            imgSegLineCnt++;
          }
        }
      }
    }
  },

  /**
  * calculate grapheme lookups for tokens, context and boundary nodes for each token/compound in a text division
  *
  * @param textDivSeqID int ID identifying the sequence for this text division
  */
  calcTextDivGraphemeLookups : function(textDivSeqID) {
    var textDiv = this.dataMgr.entities['seq'][textDivSeqID],
        i, textDivTag, textDivGID, textDivGIDs;
    if (textDiv && textDiv.entityIDs) {
      textDivGIDs = textDiv.entityIDs;
      for (i=0; i<textDivGIDs.length; i++) {//iterate through the entities of this sequence
        textDivGID = textDivGIDs[i];
        this.walkTokenContext(textDivGID,'seq'+textDivSeqID,true,null);
      }
    } else {
      DEBUG.log("warn", "warnings Calculating Text Division lookup entity not available - skipping seq:" + textDivSeqID);
    }
  },

  /**
  * updates the render mappings of the grapheme lookups with newly calculated TCM nodes for the graID grapheme
  *
  * @param int prevGraID indicates the previous grapheme's ID
  * @param int graID indicates the current grapheme's ID
  * @param int nextGraID indicates the next grapheme's ID
  */
  calcGraLookupTCMNodes : function (prevGraID, graID, nextGraID, sclID, ord) {
    var  entities = this.dataMgr.entities, prevState, curState, nextState, prevCommon, nextCommon,
        i, cnt, brackets, prevDiff, postDiff;
    prevState = curState = nextState = "";
    //calc state trasition variables
    if (prevGraID ) {
      if (entities.gra && entities.gra[prevGraID] && entities.gra[prevGraID].txtcrit){
        prevState = entities.gra[prevGraID].txtcrit;
      }
    }
    if (nextGraID ) {
      if (entities.gra && entities.gra[nextGraID] && entities.gra[nextGraID].txtcrit){
        nextState = entities.gra[nextGraID].txtcrit;
      }
    }
    if (graID ) {
      if (entities.gra && entities.gra[graID] && entities.gra[graID].txtcrit){
        curState = entities.gra[graID].txtcrit;
      }
    }
    //validate call parameters
    if (!graID || !sclID){
      DEBUG.log("warn","invalid call to calculate TCM nodes sclID "+sclID+" graID "+graID+" for edition "
                  +this.edition.value + " id="+this.id);
      return;
    }
    //shortcircuit for no trasition case
    if (prevState == curState && curState == nextState) {//no transitions so no nodes
      delete this.lookup.gra[graID].preTCM;
      delete this.lookup.gra[graID].postTCM;
      return ;
    }

    //find previous common state which could be none (= S), this equates to a diff of the state label characters
    if (prevState == "S"){
      prevState = "";
    }
    if (curState == "S"){
      curState = "";
    }
    if (nextState == "S"){
      nextState = "";
    }
    cnt = Math.min(prevState.length, curState.length),
    prevCommon = "";
    for (i=0; i<cnt; i++) {
      if (prevState[i] != curState[i]) {
        break;
      }
      prevCommon += curState[i];
    }
    prevDiff = curState.substring(prevCommon.length);
    if (prevCommon == ""){
      prevCommon = "S";
    }
    //find next common state which could be none (= S), again a diff of state characters
    cnt = Math.min(curState.length, nextState.length),
    nextCommon = "";
    for (i=0; i<cnt; i++) {
      if (nextState[i] != curState[i]) {
        break;
      }
      nextCommon += curState[i];
    }
    postDiff = curState.substring(nextCommon.length);
    if (nextCommon == ""){
      nextCommon = "S";
    }
    if (curState == ""){
      curState = "S";
    }
    //recalc grapheme's preTCM
    delete this.lookup.gra[graID].preTCM;
    brackets = tcmBracketsLookup[prevCommon][curState];
    if (brackets.length) {
      this.lookup.gra[graID].preTCM = '<span class="TCM scl'+sclID+' ord'+ord+'"' +
                                        ' state="'+prevDiff+'">'+brackets+'</span>' ;
    }
    //recalc grapheme's postTCM
    delete this.lookup.gra[graID].postTCM;
    brackets = tcmBracketsLookup[curState][nextCommon];
    if (brackets.length) {
      this.lookup.gra[graID].postTCM = '<span class="TCM scl'+sclID+'"' +
                                        ' state="-'+postDiff+'">'+brackets+'</span>' ;
    }
  },

  /**
  * calculate grapheme lookups for groups, boundaries and TCM nodes for each grapheme in a physical line
  *
  * @param physLineSeqID int ID identifying the sequence for this physical line
  */
  calcLineGraphemeLookups : function(physLineSeqID) {
    DEBUG.traceEntry("editionVE.calcLineGraphemeLookups","physLseqID = " + physLineSeqID);
    var  entities = this.dataMgr.entities, physLineSeq = entities.seq[physLineSeqID],
        isNumber, sclID,sclIDs,graID = null,graIDs,i,j, prevGraID = null, nextGraID,
        nextSclID, entFootnote, physLineTag, physLineLabel;
    if (physLineSeq && physLineSeq.entityIDs && physLineSeq.entityIDs.length && entities.scl) {
      sclIDs = physLineSeq.entityIDs;
      physLineTag = 'seq' + physLineSeq.id;
      if (physLineSeq.label) {
        physLineLabel = physLineSeq.label;
      } else {
        physLineLabel = physLineTag;
      }
      // for each syllable
      for(i=0; i<sclIDs.length; i++) {
        sclID = sclIDs[i].substr(4);
        graIDs = entities['scl'][sclID]?entities['scl'][sclID].graphemeIDs:null;
        if (!graIDs) {
          DEBUG.log("err","calculating graLookups and syllable sclID "+sclID+" is not available ");
          continue;
        }
        nextSclID = (i+1 == sclIDs.length)? null : sclIDs[i+1].substr(4);
        // for each grapheme
        for(j=0; j<graIDs.length; j++) {
          graID = graIDs[j];
          if (!entities.gra || !entities.gra[graID]) {
            DEBUG.log("err","calculating graLookups and grapheme not available for graID "+graID);
            continue;
          }
          if (entities.gra && entities.gra[graID].value == 'ʔ') {//skip vowel carrier
            continue;
          }
          nextGraID = (j+1 < graIDs.length)? graIDs[j+1] :
                        ((nextSclID && entities['scl'][nextSclID]&&
                          entities['scl'][nextSclID].graphemeIDs) ? entities['scl'][nextSclID].graphemeIDs[0]:null);
          if (!this.lookup.gra[graID]) {
            this.lookup.gra[graID] = {};
          }
          // calc preTCM node and postTCM node
          this.calcGraLookupTCMNodes(prevGraID,graID,nextGraID,sclID,j);
          //update context with scl and seg
          this.lookup.gra[graID].sylctx = ' scl' + sclID
                           + (entities.scl[sclID].segID ? ' seg' + entities.scl[sclID].segID : '');
          if (physLineLabel) {
            this.lookup.gra[graID].linelabel = '<span class="linelabel ' + physLineTag + '">['+physLineLabel+']</span>';
            physLineLabel = null; // only label first grapheme of physical line
          }
          prevGraID = graID;
        }
        entFootnote = this.getEntFootnoteHtml('scl'+sclID);
        if (entFootnote) {
          if (!this.lookup.gra[graID].fnMarker) {
            this.lookup.gra[graID].fnMarker = {};
          }
          this.lookup.gra[graID].fnMarker['scl'+sclID] = entFootnote;
        } else if (this.lookup.gra[graID].fnMarker && this.lookup.gra[graID].fnMarker['scl'+sclID]) {
          delete this.lookup.gra[graID].fnMarker['scl'+sclID];
        }
      }

      if (graID){
        // update last grapheme boundary to appropriate linebreak
        // compoundtoken split linebreak, linebreak or split token linebreak
        boundary = this.lookup.gra[graID].boundary;
        if (!entities.gra || !entities.gra[graID] || !entities.gra[graID].type) {
          isNumber = false;
          DEBUG.log("warn","grapheme id "+graID +" or type not found for sclID "
                +sclID+" physLine "+ physLineTag);
        } else {
          isNumber = (this.dataMgr.getTermFromID(entities.gra[graID].type) == "NumberSign");
        }
        if (!boundary) {//must be in the middle of token so split token linebreak
          this.lookup.gra[graID].boundary = '<span class="linebreak toksplit">-</span><br/>';
        } else if (boundary.search(/toksep/) > -1) {// at compound token boundary so split compound linebreak
          this.lookup.gra[graID].boundary = '<span class="linebreak cmpsplit">'+(isNumber?"":"-")+'</span><br/>';
        } else if (boundary.search(/linebreak/) == -1){// all the rest goes to regular linebreak
          this.lookup.gra[graID].boundary = '<span class="linebreak"></span><br/>';
        }
      }
      entFootnote = this.getEntFootnoteHtml('seq'+physLineSeqID);
      if (entFootnote) {
        if (!this.lookup.seq[physLineSeqID]) {
          this.lookup.seq[physLineSeqID] = {};
        }
        if (this.lookup.seq[physLineSeqID].fnMarker) {
          this.lookup.seq[physLineSeqID].fnMarker += entFootnote;
        } else {
          this.lookup.seq[physLineSeqID].fnMarker = entFootnote;
        }
      }
    } else if (!physLineSeq){
      DEBUG.log("warn","invalid physical line sequence with seqID "+physLineSeqID+" for edition "
                  +this.edition.value + " id="+this.id);
    }
    DEBUG.traceExit("editionVE.calcLineGraphemeLookups","physLseqID = " + physLineSeqID);
  },


/**
* calculate footnote html for a given entity
*
* @param string entTag Entity tag
*
* @returns {String}
*/

  getEntFootnoteHtml: function(entTag) {
    var annotations,
        i,j, typeID, footnotes, footnote, html = "";
    if (this.dataMgr.entTag2LinkedAnoIDsByType) {
      annotations = this.dataMgr.entTag2LinkedAnoIDsByType[entTag];
    }
    if (annotations && Object.keys(annotations).length) {
      for (i in this.typeIDs) {
        typeID = this.typeIDs[i];
        footnotes = annotations[typeID];
        if (footnotes && footnotes.length) {
          for (j in footnotes) {
            footnote = this.dataMgr.getEntity('ano',footnotes[j]);
            if (footnote) {
              this.cntFootnote ++;
              title = footnote.text + (footnote.url? " "+footnote.url:"");
              html += '<sup class="footnote '+entTag+' trm'+typeID+'" '+
                        'title="'+title+'">'+this.cntFootnote+'</sup>';
            }
          }
        }
      }
      return html;
    } else {
      return null;
    }
  },


/**
* apply click handle for superscript footnote elements
*/

  attachFootnoteClickHandlers: function() {
    var ednVE = this;
    $('sup.footnote',this.editDiv).unbind('click').bind('click',function(e) {
      var classes = $(this).attr('class'), entTag;
      if (classes && classes.length) {
        entTag = classes.match(/(tok|cmp|scl|seq)\d+/);
        if (entTag) {
          entTag = entTag[0];
          ednVE.propMgr.showVE('entPropVE',entTag);
        }
      }
    });
  },


/**
* renumber footnote superscript elements
*/

  renumberFootnotes: function() {
    var ednVE = this, fNum = 0;
    $('sup.footnote',this.editDiv).each(function(index,elem) {
      var classes  = $(this).attr('class'), trmTag, typeID;
      if (classes && classes.length) {
        trmTag = classes.match(/trm\d+/);
        if (trmTag && trmTag.length) {
          typeID = trmTag[0].substring(3);
          if (ednVE.typeIDs.indexOf(parseInt(typeID)) > -1) {//need to show this footnote
            $(this).show();
            fNum++;
            $(this).html(fNum);
          } else {
            $(this).hide();
            $(this).html("?fn?");
          }
        }
      }
    });
  },

/**
*
* walk the token hierarchy and update the render mapping
*
* @param entGID string in the form of a GID (prefix:ID) for the entity to be walked
* @param {String} context string representing the containing context
* @param isLastEnt boolean identifying whether the entity ends at a token boundary
* @param footnoteHtml string encoding footnote for this token's location
*/

  walkTokenContext : function (entGID, context, isLastEnt, footnoteHtml) {
    var  entities = this.dataMgr.entities, entID = entGID.split(":",2), entType, isCmpTokenSeparator,
        k, ctxt, entIDs, graID, graIDs, isNumber, compound = null,
        entFootnote = null, isProperNoun, pos, cmpntGID;

    entType = entID[0];
    entID = entID[1];
    //guard for undefined context
    if (!context) {
      DEBUG.log("error","invalid context for entitiy "+entGID);
      context = "errCtx"; //debug code to show errant entities
    }
    switch (entType) {
      case 'cmp':
        compound = entities['cmp'][entID];
        if (compound && compound.entityIDs && compound.entityIDs.length) {
          entIDs = compound.entityIDs;
          ctxt = context+' cmp'+entID;
          entFootnote = this.getEntFootnoteHtml('cmp'+entID);
          entFootnote = (entFootnote?entFootnote:"")+(footnoteHtml?footnoteHtml:"");
          for (k=0; k<entIDs.length; k++) {
            cmpntGID = entIDs[k];
            this.walkTokenContext(cmpntGID,ctxt,(k+1 == entIDs.length)? isLastEnt:false, (k+1 == entIDs.length && entFootnote)?entFootnote:null);
          }
        }
        break;
      case 'tok':
        if (entities['tok'][entID] && entities['tok'][entID].graphemeIDs) {
          graIDs = entities['tok'][entID].graphemeIDs;
          entFootnote = this.getEntFootnoteHtml('tok'+entID);
          entFootnote = (entFootnote?entFootnote:"")+(footnoteHtml?footnoteHtml:"");
          //for each token's grapheme
          for (k=0; k<graIDs.length; k++) {
            graID = graIDs[k];
            // add/update lookup with containing tokID, context string, and Boundary
            if (!this.lookup.gra[graID]) {
              this.lookup.gra[graID] = {};
            }
            if (k==0) {
              if (entities.gra && entities.gra[graID] && entities.gra[graID].decomp &&
                  entities.gra[graID].decomp.length){//sandhi case
                if (entities.gra[graID].tokIDs) {
                  entities.gra[graID].tokIDs.push(entID);
                }
                if (this.lookup.gra[graID].tokID &&
                    this.lookup.gra[graID].tokctx) {
                  this.lookup.gra[graID].stokID = entID;
                  this.lookup.gra[graID].tokctx += ' stok'+entID;
                }
              } else {
                this.lookup.gra[graID].tokID = entID;
                this.lookup.gra[graID].tokctx = context+' tok'+entID;
              }
            } else {
              this.lookup.gra[graID].tokID = entID;
              this.lookup.gra[graID].tokctx = context+' tok'+entID;
            }
            if ((k+1) == graIDs.length) { // last grapheme of this token create the HTML boundary marker
              if (entFootnote) {
                if (!this.lookup.gra[graID].fnMarker) {
                  this.lookup.gra[graID].fnMarker = {};
                }
                this.lookup.gra[graID].fnMarker[context+' tok'+entID] = entFootnote;
              } else if (this.lookup.gra[graID].fnMarker && this.lookup.gra[graID].fnMarker[context+' tok'+entID]) {
                delete this.lookup.gra[graID].fnMarker[context+' tok'+entID];
              }
              isCmpTokenSeparator = (!isLastEnt && context.search(/cmp/)>-1);
              if (!entities.gra || !entities.gra[graID] || !entities.gra[graID].type) {
                isNumber = false;
                DEBUG.log("warn","grapheme id "+graID +" or type not found for entGID "
                      +entGID+" with context "+context);
              } else {
                isNumber = (this.dataMgr.getTermFromID(entities.gra[graID].type) == "NumberSign");//term dependency
              }
              //store in the lookup for this grapheme
              this.lookup.gra[graID].boundary = '<span class="boundary'+
                                                ((isCmpTokenSeparator && !isNumber)?' toksep':'')+
                                                '">'+((isCmpTokenSeparator && !isNumber)?'-':'&nbsp;')+'</span>';
              if (entities.gra && entities.gra[graID] && entities.gra[graID].decomp) {//sandhi grapheme at end of token
                entities.gra[graID].tokIDs =[entID];
              }
            }else{//in the case where the boundary changes it's necessary to erase the old boundary HTML
              delete this.lookup.gra[graID].boundary;
            }
          }//end for each grapheme
        }
        break;
      default:
        DEBUG.log("warn","walkContext of calcRenderMappings found invalid GID "
                      +entGID+" with context "+context);
    }
  },

/**
*
* renders a physical line using render mappings (grapheme lookups)
* post process line ordinal and edition presentation style change
*
* @param int physLineSeqID Physical line sequence id
* @param string headerClass Classes to add to line header element
* @param string groupClass Classes to add to each group element element
* @param int grpOrdStart Starting ordinal for groups
* @param int lineord Ordinal position for this line
*
* @returns mixed [] Array of line html and ordinal line position
*/

  renderPhysicalLine: function (physLineSeqID, headerClass, groupClass,grpOrdStart, lineord) {
    DEBUG.traceEntry("editionVE.renderPhysicalLine","physLseqID = " + physLineSeqID);
    var  entities = this.dataMgr.entities, physLineSeq = entities.seq[physLineSeqID], fnCtx,
        grpOrd = grpOrdStart?grpOrdStart:1, sclID, sclIDs, i, j, hasNonReconConsnt, hasNonAddedConsnt, isConsnt, typ,
        graLU, grapheme, previousA, previousGraTCMS, graID, graIDs, lineHTML, grpHTML = "", inRestore = false,
        prevGraIsVowelCarrier = false, plusSign = (this.repType == "diplomatic" ? "+&nbsp":"+");
    if (physLineSeq && physLineSeq.entityIDs && physLineSeq.entityIDs.length) {
      //calculate line header
      lineHTML = '<span class="textDivHeader seqLabel'+(headerClass?' '+ headerClass:'')+
                      (lineord?' ordL'+ lineord:'')+
                      (physLineSeqID?' lineseq'+ physLineSeqID:'')+'">'+
                       physLineSeq.label;
      if ( this.lookup.seq && this.lookup.seq[physLineSeqID] && this.lookup.seq[physLineSeqID].fnMarker ) {
        lineHTML += this.lookup.seq[physLineSeqID].fnMarker;
      }
      lineHTML += '</span>';
      sclIDs = physLineSeq.entityIDs;
      //for each syllable in physical line sequence
      for(i=0; i<sclIDs.length; i++) {
        sclID = sclIDs[i].substr(4);
        graIDs = entities['scl'][sclID].graphemeIDs;
        hasNonReconConsnt = false;//track non reconstructed consonants for not changing _. to +
        hasNonAddedConsnt = false;//track non added consonants for changing vowel to 'a'
        //for each grapheme in syllable
        for(j=0; j<graIDs.length; j++) {
          graID = graIDs[j];
          grapheme = entities.gra[graID];
          if (!grapheme) {
            DEBUG.log("err","calculating graLookups and grapheme not available for graID "+graID);
            continue;
          }
          if (this.repType == "reconstructed" && (grapheme.value == "◈" || grapheme.value == "◯")) {//knot symbol removed from reconstructed
            hasNonReconConsnt = false;
            hasNonAddedConsnt = false;
            prevGraIsVowelCarrier = false;
            previousA = false;
            continue;
          }
          typ = this.trmIDtoLabel[grapheme.type];
          isConsnt = (typ == "Consonant");//warning!!! term dependency
          if (isConsnt && (!grapheme.txtcrit || grapheme.txtcrit.indexOf("R") == -1)) {
            hasNonReconConsnt = true;
          }
          if (isConsnt && (!grapheme.txtcrit || grapheme.txtcrit.indexOf("A") == -1)) {
            hasNonAddedConsnt = true;
          }
          entities.gra[graID].sclID = sclID;
          if (grapheme.value == "ʔ") {
            prevGraIsVowelCarrier = true;
            continue;
          }
          graLU = this.lookup.gra[graID];
          if (graLU) {
            //check for TCM pre transition
            if (graLU.preTCM) {
              if (this.repType != "diplomatic" || !grapheme.txtcrit ||
                  grapheme.txtcrit.indexOf("A") == -1) {//vowel start editorial addition output "a"
                //if group close grp node and add to html
                if (grpHTML) {
                  lineHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                //add preTCM html node
                lineHTML += graLU.preTCM;
              } else if (this.repType != "diplomatic" &&
                          grapheme.txtcrit.indexOf("A") == -1 &&
                          grapheme.txtcrit.length > 1) {//multiple TCM transition case
                //if group close grp node and add to html
                if (grpHTML) {
                  lineHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                //add preTCM html node
                lineHTML += graLU.preTCM.replace(/⟨\*/g,"").replace(/[⟨⟩]/g,"");
              }
              previousA = false;
            }
            if (grapheme.decomp && grapheme.decomp.length) { //sandhi
              if (grpHTML) {
                lineHTML += grpHTML+ '</span>';
                grpHTML = '';
              }
            }
            //if no group find context and start grapheme group
            if (!grpHTML) {
              grpHTML = '<span class="grpGra '+ (groupClass? groupClass +' ':'')+
                          (graLU.tokctx?graLU.tokctx:"ctxerror") + graLU.sylctx +
                          ' ord' + grpOrd++ + (lineord?' ordL'+ lineord:'')+'">'
            }
            //add grapheme
            if (this.repType == "diplomatic" && grapheme.txtcrit &&
                grapheme.txtcrit.indexOf("R") > -1) {
                if (typ == "Consonant"){//term dependency
                  grpHTML += (j==0?" _":"_");
                }else if (typ == "Vowel") {//term dependency
                  if (prevGraIsVowelCarrier) {

                    grpHTML += plusSign;
                  } else {
                    grpHTML += (graLU.boundary?". ":". ");
                    grpHTML = grpHTML.replace(/_+/g,"_").replace(/_\./g,(hasNonReconConsnt?"..":(j || i?" ":"")+plusSign));
                  }
                }else if (typ != "VowelModifier"){
                  grpHTML += plusSign;
                }
            } else if (this.repType == "diplomatic" && grapheme.txtcrit &&
                 grapheme.txtcrit.indexOf("A") > -1) {
              if (typ == "Vowel" && //warning!!! term dependency
                  !prevGraIsVowelCarrier && hasNonAddedConsnt) {
                   //vowel start editorial addition output "a"
                grpHTML += "a";
              }
            } else if (grapheme.value == '_') {
              if (this.repType == "hybrid") {
                if (j==0) {
                  indx = grpHTML.indexOf('class="');
                  grpHTML = grpHTML.substr(0,indx+7) + "prepadding " + grpHTML.substr(indx+7);
                }
                grpHTML += "_";
              } else {
                grpHTML += (j==0?"&nbsp;.":".");
              }
            } else if (graLU.uppercase) {//depricated //capitalize grapheme assumes dicritics always follow base character
              graTemp = grapheme.value;
              graTemp = graTemp[0].toUpperCase() + (graTemp.length > 1? graTemp.substring(1):"");
              grpHTML += graTemp;
            } else if (j==1 && prevGraIsVowelCarrier && previousA) {
              if (grapheme.value == 'i') {
                grpHTML += "ï";
              }else if (grapheme.value == 'u') {
                grpHTML += "ü";
              }else{
                grpHTML += grapheme.value;
              }
            } else {
              grpHTML += grapheme.value;
            }
            //check for TCM post transition
            if (graLU.postTCM) {
              if (this.repType != "diplomatic" || !grapheme.txtcrit ||
                  grapheme.txtcrit.indexOf("A") == -1) {//vowel start editorial addition output "a"
                //if group close grp node and add to html
                if (grpHTML) {
                  lineHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                //add preTCM html node
                lineHTML += graLU.postTCM;
              } else if (this.repType != "diplomatic" &&
                          grapheme.txtcrit.indexOf("A") == -1 &&
                          grapheme.txtcrit.length > 1) {//multiple TCM transition case
                //if group close grp node and add to html
                if (grpHTML) {
                  lineHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                //add preTCM html node
                lineHTML += graLU.postTCM.replace(/⟨\*/g,"").replace(/[⟨⟩]/g,"");
              }
            }
            if (graLU.fnMarker) {//check for footnote markers
              //if group close grp node and add to html
              if (grpHTML) {
                lineHTML += grpHTML+ '</span>';
                grpHTML = '';
              }
              for (fnCtx in graLU.fnMarker) {
                lineHTML += graLU.fnMarker[fnCtx];
              }
            }
            //check for split syllable token break or compound token break
            if (graLU.boundary) {
              if (!(this.repType == "diplomatic" && grapheme.txtcrit && grapheme.txtcrit.indexOf("R") > -1) ||
                  graLU.boundary.indexOf('linebreak') != -1) {
                //if group close grp node and add to html
                if (grpHTML) {
                  lineHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                //add break node to html
                lineHTML += graLU.boundary;
              }
            }
          }
          if (grapheme.value.toLowerCase() == "a" && !(graLU && (graLU.boundary || graLU.postTCM))) {
            previousA = true;
            previousGraTCMS = grapheme.txtcrit;
          } else {
            previousA = false;
          }
          prevGraIsVowelCarrier = false;
        }//end for graphIDs
        if (grpHTML) {
          lineHTML += grpHTML+ '</span>';
          grpHTML = '';
        }
      }//end for syllable IDs
      //insert line ordinal
      lineHTML = lineHTML.replace(/TCM/g,"TCM ordL"+lineord).
                         replace(/boundary/g,"boundary ordL"+lineord).
                         replace(/linebreak/g,"linebreak ordL"+lineord);
      if (this.repType == "diplomatic"){//remove any disallowed TCMs
        lineHTML = lineHTML.replace(/_+/g,"_").replace(/_\./g," "+plusSign); //compress consonant placeholders and convert _. to +
        lineHTML = lineHTML.replace(/\(\*/g,"").replace(/\)/g,""); //remove editorial restoration brackets
        lineHTML = lineHTML.replace(/-/g,""); //remove hyphens
        lineHTML = lineHTML.replace(/(_)([^\.])/g,".$2"); //replace _ with . for missing consonant
        //remove editorial deletion brackets
        lineHTML = lineHTML.replace(/([^\{])(\{)([^\{])/g,"$1$3").replace(/([^\}])(\})([^\}])/g,"$1$3").replace(/{{{/g,"{{").replace(/}}}/g,"}}")
      }
      if (this.repType == "reconstructed"){//remove any disallowed TCMs
        lineHTML = lineHTML.replace(/\[/g,"").replace(/\]/g,""); // remove uncertainty brackets
        lineHTML = lineHTML.replace(/⟪/g,"").replace(/⟫/g,""); // remove scribal insertion brackets
//        lineHTML = lineHTML.replace(/\s◈/g,"").replace(/◈/g,""); // remove knot symbol
        lineHTML = lineHTML.replace(/\{\{[^}]*\}\}/g,""); // remove scribal deletion brackets and text
        lineHTML = lineHTML.replace(/\/\/\//g,""); // remove edge indicator
        lineHTML = lineHTML.replace(/_+/g,"_").replace(/(_)([^\.])/g,".$2");//.replace(/\.\./g,".");
      }
      DEBUG.traceExit("editionVE.renderPhysicalLine","physLseqID = " + physLineSeqID);
      return [lineHTML,grpOrd];
    } else if (this.dataMgr.getTermFromID(physLineSeq.typeID) == 'FreeText') {// term dependancy
      lineHTML = '<span class="textDivHeader freetext seqLabel'+(headerClass?' '+ headerClass:'')+
                      (lineord?' ordL'+ lineord:'')+
                      (physLineSeqID?' lineseq'+ physLineSeqID:'')+'">'+
                       physLineSeq.label+'</span>';
      if (physLineSeq.freetext) {
        lineHTML += '<div class="freetext'+
                          (lineord?' ordL'+ lineord:'')+
                          (physLineSeqID?' lineseq'+ physLineSeqID:'')+'">'+
                          physLineSeq.freetext+'</div><span class="linebreak"></span><br/>';
      }
      return [lineHTML,grpOrd];
    } else {
      return ['no physical line seq. contact sys admin<br/>',grpOrd];
    }
  },

/**
* render this edition in physical layout with tokenization
*/

  renderEdition: function () {
    DEBUG.traceEntry("editionVE.renderEdition","ednID = " + this.edition.id);
    //create html of the edition.
    //for edition text and text division
    //create flat set of grpGra spans with class labels for sequence compounds tokens and syllableClusters
    //ma ha ra ja becomes
    //    <span class="grpGra seq84 cmp12 tok91 scl214">ma</span>
    //    <span class="grpGra seq84 cmp12 tok91 scl215">ha</span>
    //    <span class="tokSep">-</span>
    //    <span class="TCM">[</span>
    //    <span class="grpGra seq84 cmp12 tok92 scl216">ra</span>
    //    <span class="grpGra seq84 cmp12 tok92  scl219">ja</span>
    //    <span class="TCM">]</span>
    //track end of physical lines to insert <br/>
    var  entities = this.dataMgr.entities, textSeq, textDivSeq, physicalSeq, lineSeq,
        textAnalysisSeq, textSubStructSeq, tcms = 'S',
        seqID, ednSeqIDs = this.edition.seqIDs,
        i,cnt;
    if (ednSeqIDs && ednSeqIDs.length) {
      cnt = ednSeqIDs.length;
      for (i=0; i<cnt; i++) {
        seqID = ednSeqIDs[i];
        if (!entities['seq']) {
          DEBUG.log("warn","Text sequences for edition "+this.edition.value + " id="+this.id + " not loaded");
          alert("Nothing to display for this edition");
          $(this.editDiv).html(this.layoutMgr.editPlaceholderHTML);
          return;
        }
        tempSeq = entities['seq'][seqID];
        if (!tempSeq) {
          continue;
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'Text'){//warning!!!! id dependency edition text seq type
          if (!textSeq) {
           textSeq = tempSeq;
          } else {//warn of having more than one text per edition
            DEBUG.log("warn","Found multiple Text sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'TextPhysical'){//warning!!!! id dependency edition physical seq type
          if (!physicalSeq) {
           physicalSeq = tempSeq;
          } else {//warn against having more than one physical per edition
            DEBUG.log("warn","Found multiple physical sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'Analysis'){//warning!!!! id dependency edition physical seq type
          if (!textAnalysisSeq) {
           textAnalysisSeq = tempSeq;
          } else {//warn against having more than one structure per edition
            DEBUG.log("warn","Found multiple structure sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
      }
    }
    if (textSeq && !physicalSeq) { // create a physical seq of clusters?
      this.noPhysical = true;
    }
    if (!textSeq && physicalSeq) { // create a physical seq of clusters?
      this.noTokenised = true;
      textSeq = physicalSeq;
    }
    if (!physicalSeq) { // create a physical seq of clusters?
      alert("Editting a new edition without text physical Seq is underconstruction");
      return;
    }

    var textDivSeqIDs = textSeq ?textSeq.entityIDs:physicalSeq.entityIDs,
        textLineSeqIDs = physicalSeq?physicalSeq.entityIDs:(textSeq ?textSeq.entityIDs:null);
    var j,cnt = textDivSeqIDs.length,nextLineLabel, tcms = 'S', ret,
        nextSeqID,lineLastEntityID,textLineSeqID, text = this.dataMgr.getEntity('txt',this.edition.txtID),
        html = '<div class="editionTitleDiv" contenteditable="false"><h3 class="ednLabel edn'+this.edition.id+'">'+this.edition.value+ '</h3>' +
              (text && text.CKN?' <a target="_blank" title="export edition to EpiDoc" href="'+basepath+'/services/exportEditionToEpiDoc.php?db='+dbName+'&ednID='+ this.edition.id +'">[epidoc]</a>':'') +
              '</div>';
    $(this.contentDiv).html(html);

    this.grpNodeSeq = 0;//reset count so that system can mark order of leaf nodes in selection
    var grpOrd = 1, hdrClass, grpClass;
    for (j=0; j<textLineSeqIDs.length; j++) {
      seqID = (textLineSeqIDs[j]).substr(4);
      hdrClass = !j?' startHeader': ((j+1) == textLineSeqIDs.length ? ' endHeader':'');
      grpClass = !j?' firstLine': ((j+1) == textLineSeqIDs.length ? ' lastLine':'');
      ret = this.renderPhysicalLine(seqID,hdrClass,grpClass,grpOrd,j+1);
      html = ret[0];
      $(this.contentDiv).append(html);
      grpOrd = ret[1];
    }
    $(this.contentDiv).append('<hr class="viewEndRule">');
    DEBUG.trace("editionVE.renderEdition","before add Handlers to DOM for edn " + this.edition.id);
    this.renumberFootnotes();
    this.addEventHandlers(); // needs to be done after content created
    DEBUG.trace("editionVE.renderEdition","after add Handlers to DOM for edn " + this.edition.id);
    DEBUG.traceExit("editionVE.renderEdition","ednID = " + this.edition.id);
  },


/**
* render this edition in structural layout with tokenization
*/

  renderEditionStructure: function () {
    DEBUG.traceEntry("editionVE.renderEditionStructure","ednID = " + this.edition.id);
    //create html of the edition.
    //for edition textAnalysis structures nesting where needed
    //create flat set of grpGra spans with class labels for sequence compounds tokens and syllableClusters
    //ma ha ra ja becomes
    //    <span class="grpGra seq84 cmp12 tok91 scl214">ma</span>
    //    <span class="grpGra seq84 cmp12 tok91 scl215">ha</span>
    //    <span class="tokSep">-</span>
    //    <span class="TCM">[</span>
    //    <span class="grpGra seq84 cmp12 tok92 scl216">ra</span>
    //    <span class="grpGra seq84 cmp12 tok92  scl219">ja</span>
    //    <span class="TCM">]</span>
    //track end of physical lines to insert <br/>
    var ednVE = this, entities = this.dataMgr.entities,
        textSeq, textDivSeq, physicalSeq, lineSeq, prevWordLastGraID = null,
        textAnalysisSeq, textSubStructSeq, tcms = 'S',
        seqID, ednSeqIDs = this.edition.seqIDs, text = this.dataMgr.getEntity('txt',this.edition.txtID),
        i,cnt;
    if (ednSeqIDs && ednSeqIDs.length) {
      cnt = ednSeqIDs.length;
      if (!entities['seq']) {
        DEBUG.log("warn","Text sequences for edition "+this.edition.value + " id="+this.id + " not loaded");
        alert("Nothing to display for this edition");
        $(this.editDiv).html(this.layoutMgr.editPlaceholderHTML);
        return;
      }
      for (i=0; i<cnt; i++) {
        seqID = ednSeqIDs[i];
        tempSeq = entities['seq'][seqID];
        if (!tempSeq) {
          continue;
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'Text'){//warning!!!! term dependency edition text seq type
          if (!textSeq) {
           textSeq = tempSeq;
          } else {//warn of having more than one text per edition
            DEBUG.log("warn","Found multiple Text sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'TextPhysical'){//warning!!!! term dependency edition physical seq type
          if (!physicalSeq) {
           physicalSeq = tempSeq;
          } else {//warn against having more than one physical per edition
            DEBUG.log("warn","Found multiple physical sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
        if (this.trmIDtoLabel[tempSeq.typeID] == 'Analysis'){//warning!!!! term dependency edition physical seq type
          if (!textAnalysisSeq) {
           textAnalysisSeq = tempSeq;
          } else {//warn against having more than one structure per edition
            DEBUG.log("warn","Found multiple structure sequences for edition "+this.edition.value + " id="+this.id);
          }
        }
      }
    }
    if (!textAnalysisSeq || !textAnalysisSeq.entityIDs || textAnalysisSeq.entityIDs.length == 0) { // create a physical seq of clusters?
      alert("No Text Analysis found for edition "+this.edition.value + " id="+this.id);
      //todo add code to return UI to physical presentation
      return;
    }

    var physicalLineSeqIDs = physicalSeq?physicalSeq.entityIDs:null,
        textAnalysisIDs = (textAnalysisSeq.entityIDs && textAnalysisSeq.entityIDs.length)?textAnalysisSeq.entityIDs:null;
    // output Header
    html = '<div class="editionTitleDiv" contenteditable="false"><h3 class="ednLabel edn'+this.edition.id+'">'+this.edition.value+ '</h3>' +
          (text && text.CKN?' <a target="_blank" title="export edition to EpiDoc" href="'+basepath+'/services/exportEditionToEpiDoc.php?db='+dbName+'&ckn='+ text.CKN +'">[epidoc]</a>':'') +
          '</div>';
    $(this.contentDiv).html(html);
    this.grpOrd = 1;

    /**
    * put your comment there...
    *
    * @param string entGID Entity global id (prefix:id)
    * @param parentDiv
    */

    function renderWordHTML(entGID,parentDiv) {
      var entities = ednVE.dataMgr.entities,tokID,tokIDs,i,j, entID = entGID.substring(4),
          firstT, firstG, lastT, lastG, preTCM, postTCM, isScribalDelete,
          prefix = entGID.substring(0,3), entity = entities[prefix][entID],
          graLU, grapheme,previousA, previousGraTCMS, graID,graIDs,i,j, lastSylCtx = null,
          wordHTML = "",grpHTML = "", prevGraIsVowelCarrier = false;

      if (entity && entity.tokenIDs && entity.tokenIDs.length) {
        tokIDs = entity.tokenIDs;
      } else if (entity && prefix == 'tok'){
        tokIDs = [entID];
      } else {
        DEBUG.log("err","rendering words in structure view invalid GID "+entGID);
      }

      if (tokIDs && tokIDs.length > 0) {
        //for each syllable in physical line sequence
        for(i=0; i<tokIDs.length; i++) {
          tokID = tokIDs[i];
          graIDs = entities['tok'][tokID].graphemeIDs;
          if (graIDs.length == 0) {
              DEBUG.log("err","rendering words in structure view with token ("+tokID+") that has no graphemes");
              continue;
          }
          firstT = (i==0);
          lastT = (1+i == tokIDs.length);
          //for each grapheme in token
          for(j=0; j<graIDs.length; j++) {
            graID = graIDs[j];
            if (j === 0 && graID == prevWordLastGraID){ // first grapheme same as previous word - sandhi
              continue;
            }
            if (j+1 == graIDs.length) { // last grapheme of this token save the graID
              prevWordLastGraID = graID;
            }
            grapheme = entities.gra[graID];
            if (!grapheme) {
              DEBUG.log("err","calculating graLookups and grapheme not available for graID "+graID);
              continue;
            }
            if (grapheme.value == "◈" || grapheme.value == "◯") {//knot or ◯ symbol removed from reconstructed
              hasNonReconConsnt = false;
              hasNonAddedConsnt = false;
              prevGraIsVowelCarrier = false;
              previousA = false;
              continue;
            }
            if (grapheme.value == "ʔ") {
              prevGraIsVowelCarrier = true;
              continue;
            }
            isScribalDelete = (grapheme.txtcrit && grapheme.txtcrit.match(/Sd/));
            firstG = (j==0 || j==1 && prevGraIsVowelCarrier);
            lastG = (1+j == graIDs.length);
            graLU = ednVE.lookup.gra[graID];
            if (graLU) {
              if (graLU.linelabel){
                //if group close grp node and add to html
                if (grpHTML) {
                  wordHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                if (firstT && firstG || lastT && lastG) {
                  wordHTML += '&nbsp;'+graLU.linelabel+'&nbsp;';
                } else {
                  wordHTML += graLU.linelabel;
                }
              }
              //check for TCM pre transition
              if (graLU.preTCM) {
                //if group close grp node and add to html
                if (grpHTML) {
                  wordHTML += grpHTML+ '</span>';
                  grpHTML = '';
                }
                //add preTCM html node
                preTCM = graLU.preTCM.replace(/\[/g,"") // remove uncertainty
                                     .replace(/\]/g,"")
                                     .replace(/⟪/g,"") // remove scribal insertion
                                     .replace(/⟫/g,"")
                                     .replace(/\{\{/g,"") // remove scribal deletion
                                     .replace(/\}\}/g,"");
                if (!preTCM.match(/></)) {
                  wordHTML += preTCM;
                }
              }
              //if syllable changed the close previous group
              if (grpHTML && lastSylCtx != graLU.sylctx || isScribalDelete){
                wordHTML += grpHTML+ '</span>';
                grpHTML = '';
              }
              if (!isScribalDelete) {
                //if no group find context and start grapheme group
                if (!grpHTML) {
                  grpHTML = '<span class="grpGra '+ graLU.tokctx + graLU.sylctx + ' ord'+ ednVE.grpOrd++ +'">';
                  lastSylCtx = graLU.sylctx;
                }
                //add grapheme
                if (graLU.uppercase) {//depricated //capitalize grapheme assumes dicritics always follow base character
                  graTemp = grapheme.value;
                  graTemp = graTemp[0].toUpperCase() + (graTemp.length > 1? graTemp.substring(1):"");
                  grpHTML += graTemp;
                } else if (prevGraIsVowelCarrier && previousA &&
                          (previousGraTCMS == grapheme.txtcrit ||
                           (!previousGraTCMS|| previousGraTCMS == "S") &&
                           (!grapheme.txtcrit|| grapheme.txtcrit == "S"))) {
                  if (grapheme.value == 'i') {
                    grpHTML += "ï";
                  }else if (grapheme.value == 'u') {
                    grpHTML += "ü";
                  }else{
                    grpHTML += grapheme.value;
                  }
                } else {
                  grpHTML += grapheme.value;
                }
                //check for TCM post transition
                if (graLU.postTCM) {
                  //if group close grp node and add to html
                  if (grpHTML) {
                    wordHTML += grpHTML+ '</span>';
                    grpHTML = '';
                  }
                  //add preTCM html node
                  postTCM = graLU.postTCM.replace(/\[/g,"") // remove uncertainty
                                         .replace(/\]/g,"")
                                         .replace(/⟪/g,"") // remove scribal insertion
                                         .replace(/⟫/g,"")
                                         .replace(/\{\{/g,"") // remove scribal deletion
                                         .replace(/\}\}/g,"");
                  if (!postTCM.match(/></)) {
                    wordHTML += postTCM;
                  }
                }
                //check for split syllable token break or compound token break
                if (graLU.boundary &&
                    graLU.boundary.indexOf('linebreak') == -1) {
                  //if group close grp node and add to html
                  if (grpHTML) {
                    wordHTML += grpHTML+ '</span>';
                    grpHTML = '';
                  }
                  if (graLU.fnMarker) {
                    for (fnCtx in graLU.fnMarker) {
                      wordHTML += graLU.fnMarker[fnCtx];
                    }
                  }
                  //add break node to html
  //                if (graLU.boundary.indexOf('toksep') == -1) {
                    wordHTML += graLU.boundary;
  //                }
                }
              }
            }
            if (grapheme.value == "a") {
              previousA = true;
              previousGraTCMS = grapheme.txtcrit;
            } else {
              previousA = false;
            }
            prevGraIsVowelCarrier = false;
          }//end for graphIDs
          if (grpHTML) {
            wordHTML += grpHTML+ '</span>';
            grpHTML = '';
          }
          wordHTML = wordHTML.replace(/\/\/\//g,""); // remove edge indicator
          wordHTML = wordHTML.replace(/_+/g,"_").replace(/(_)([^\.])/g,".$2");//.replace(/\.\./g,".");
          parentDiv.append(wordHTML);
          wordHTML = "";
        }//end for token IDs
      }
    }


    /**
    * recursively calculate Structural HTML
    *
    * @param int seqID Sequence entity id of structure
    * @param int level Indicates the structural nesting level
    * @param node parentDiv HTML div node
    *
    * @returns string Html fragment for structure
    */

    function renderStructHTML(seqID, level, parentDiv) {
      var lvl = level +1, seqHtml, proseDiv, i, entGID, prefix, entID,
          sequence = entities['seq'][seqID], sectionDiv, wrapperDiv, seqIDs, seqType;
      if (sequence) {
          seqIDs = sequence.entityIDs;
          seqType = ednVE.trmIDtoLabel[sequence.typeID];
      }
      if (!seqIDs || seqIDs.length == 0) {
        DEBUG.log("warn","Found empty structural sequence element seq"+ seqID +" for edition "+ednVE.edition.value + " id="+ednVE.id);
        return "";
      }
      if (seqType != "Pāda" && seqType != "Item") {//warning!!!! term dependency
        if (sequence.label && sequence.sup) {
          parentDiv.append('<div class="secTitleHeader level'+level+' seq'+seqID+'">'+sequence.sup + " " +sequence.label+'</div>')
        } else if (sequence.label) {//output section title
          parentDiv.append('<div class="secTitle level'+level+' seq'+seqID+'">'+sequence.label+'</div>')
        } else if (sequence.sup) {//output section hdr
          parentDiv.append('<div class="secHeader level'+level+' seq'+seqID+'">'+sequence.sup+'</div>')
        }
      }
      //start section div
      sectionDiv = $('<div class="section level'+level+' '+seqType+' seq'+seqID+'"/>');
      if (seqType == "Pāda") {//warning!!!! term dependency
        if (sequence.label || sequence.sup) {
          sectionDiv.append('<div class="secMarker level'+lvl+' seq'+seqID+'">'+
                             (sequence.sup?sequence.sup + (sequence.label?" " +sequence.label:""):
                                          (sequence.label?sequence.label:"")+'</div>'));
        }
      } else if (seqType == "Item") {//warning!!!! term dependency
        if (sequence.label || sequence.sup) {
          sectionDiv.append('<div class="itemBullet level'+lvl+' seq'+seqID+'">'+
                             (sequence.sup?sequence.sup + (sequence.label?" " +sequence.label:""):
                                          (sequence.label?sequence.label:"")+'</div>'));
        }
      }
      parentDiv.append(sectionDiv);
      if (seqType == "Item") {//warning!!!! term dependency
        wrapperDiv = $('<div class="itemWrapper"/>');
        sectionDiv.append(wrapperDiv);
        sectionDiv = wrapperDiv;
      }
      for (i=0; i<seqIDs.length; i++) {
        entGID = seqIDs[i];
        prefix = entGID.substring(0,3);
        entID = entGID.substr(4);
        if (prefix == 'seq') {
          if (proseDiv) { // close prose div
            proseDiv = null;//TODO possible enhancement check if phrase and nest span and recurse to expand tokens else set to null
          }
          renderStructHTML(entID, lvl, sectionDiv);
        } else if (prefix.match(/cmp|tok|scl/)) {
          if (!proseDiv) { // open prose div
            proseDiv = $('<div class="prose level'+lvl+' seq'+seqID+'"/>');
            sectionDiv.append(proseDiv);
          }
          renderWordHTML(entGID,proseDiv);
        }else{
          DEBUG.log("warn","Found unknown structural element "+ entGID +" for edition "+ednVE.edition.value + " id="+ednVE.id);
          continue;
        }
      }
    }

    this.grpNodeSeq = 0;//reset count so that system can mark order of leaf nodes in selection
    var entGID, entID, prefix, proseDiv,
        analysisGID = 'seq'+textAnalysisSeq.id;
    for (i=0; i<textAnalysisIDs.length; i++) {
      entGID = textAnalysisIDs[i];
      prefix = entGID.substring(0,3);
      entID = entGID.substr(4);
      if (prefix == 'seq') {
        if (proseDiv) { // close prose div
          proseDiv = null;
        }
        renderStructHTML(entID, 1,$(this.contentDiv));
      } else if (prefix.match(/cmp|tok|scl/)) {
        if (!proseDiv) { // open prose div
          proseDiv = $('<div class="prose level1 '+analysisGID+'"/>');
          $(this.contentDiv).append(proseDiv);
        }
        renderWordHTML(entGID,proseDiv);
      }else{
        DEBUG.log("warn","Found unknown structural element "+ entGID +" for edition "+this.edition.value + " id="+this.id);
        continue;
      }
    }
    $('.prose',this.contentDiv).last().addClass('lastStructure');
    $(this.contentDiv).append('<hr class="viewEndRule">');
    DEBUG.trace("editionVE.renderEditionStructure","before add Handlers to DOM for edn " + this.edition.id);
    this.renumberFootnotes();
    this.addEventHandlers(); // needs to be done after content created
    DEBUG.trace("editionVE.renderEditionStructure","after add Handlers to DOM for edn " + this.edition.id);
    DEBUG.traceExit("editionVE.renderEditionStructure","ednID = " + this.edition.id);
  },



/**
* insert new line html into edition display
*
* @param string htmlNewLine Html fragment for new line
* @param int lineOrd ordinal position of line
* @param boolean isLastLine Identifies line is last line
* @param boolean autoRenumber Indicates whether to run renumber code
*/

  renderNewLine: function (htmlNewLine, lineOrd, isLastLine, autoRenumber) {
    var ednVE = this;
    if (isLastLine) {
      brNode = $('.textDivHeader.ordL'+(-1+lineOrd),ednVE.contentDiv).nextUntil('br','.linebreak').next();
      if (brNode) {
        $(htmlNewLine).insertAfter(brNode);
      }
    } else {
      hdrNode = $('.textDivHeader.ordL'+lineOrd,ednVE.contentDiv);
      if (hdrNode) {
        $(htmlNewLine).insertBefore(hdrNode);
      }
    }
    if (typeof autoRenumber == "undefined" || autoRenumber) {
      this.renumberLines();
    }
  },


/**
* re render physical line
*
* @param string lineOrdTag Identifies the ordinal of the line to re render
* @param int physLineSeqID Identifies the physical line sequence id to re render
*/

  reRenderPhysLine: function (lineOrdTag, physLineSeqID) {
    var ednVE = this, headerNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv), physLineID, refNode,
        newHTML, isFirstLine, isLastLine, lineOrd = lineOrdTag.substring(4);
    if (headerNode.length == 0) {//invalid line ordinal
      alert("Trying to rerender line and unable to find header for given line ordinal, please refresh screen");
    } else {
      refNode = headerNode.prev();
      isFirstLine = headerNode.hasClass('startHeader');
      isLastLine = headerNode.hasClass('endHeader');
      hdrClass = isFirstLine?' startHeader': (isLastLine ? ' endHeader':'');
      grpClass = isFirstLine?' firstLine': (isLastLine ? ' lastLine':'');
      physLineID = physLineSeqID?physLineSeqID : headerNode.get(0).className.match(/lineseq(\d+)/)[1];
      newHTML = this.renderPhysicalLine(physLineID,hdrClass,grpClass,1,lineOrd);
      this.removeLine(lineOrdTag,false);
      $(newHTML[0]).insertAfter(refNode);
      this.renumberGraGroups();
      this.renumberFootnotes();
      this.addEventHandlers(); // needs to be done after content created
    }
  },


/**
* remove line from display
*
* @param string lineOrdTag Identifies the ordinal of the line to remove
* @param boolean autoRenumber Indicates whether to run renumber code
*/

  removeLine: function (lineOrdTag,autoRenumber) {
    var ednVE = this;
    brNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv).nextUntil('br','.linebreak').next();
    $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv).nextUntil('br').remove();
    $(".textDivHeader."+lineOrdTag,ednVE.contentDiv).remove();
    brNode.remove();
    if (typeof autoRenumber == "undefined" || autoRenumber) {
      this.renumberLines();
    }
  },


/**
* re number line ordinals
*/

  renumberLines: function () {
    var ednVE = this,
        hdrNodes = $(".textDivHeader",ednVE.contentDiv),
        endHeaderIndex = hdrNodes.length - 1, grpOrd = 1;
    hdrNodes.each(function(hindex,elem){
            //renumber line ordinals
            var newLineOrd = " ordL"+(1+hindex);
            elem.className = elem.className.replace(/ordL\d+/,'')
                                            .replace(/startHeader/,"")
                                            .replace(/endHeader/,"") +
                                            newLineOrd +
                                            (hindex==0?' startHeader':
                                             (hindex == endHeaderIndex?" endHeader":""));
            $(elem).nextUntil("br").
                each(function(index,elem){
                  elem.className = elem.className.replace(/ordL\d+/,'')
                                                  .replace(/ord\d+/,"ord"+(grpOrd++))
                                                  .replace(/prepadding/,"")
                                                  .replace(/firstLine/,"")
                                                  .replace(/lastLine/,"") +
                                                  newLineOrd +
                                                  (hindex==0?' firstLine':
                                                   (hindex == endHeaderIndex?" lastLine":""));
                });
        });
  },

/**
* refreshes segID for all grapheme groups in an edition
*/

  refreshSegIDLinks: function () {
    var ednVE = this,sclID,segID;
    $(".grpGra",ednVE.contentDiv).each(function(hindex,elem){
            //refresh the segID links for each scl
            if (sclID = elem.className.match(/scl(\d+)/)) {
              segID = ednVE.dataMgr.entities.scl[sclID[1]].segID;
              if (segID) {
                elem.className = elem.className.replace(/seg\d+/,"seg"+ segID);
                elem.className = elem.className.replace(/noseg/,"");
              } else {
                elem.className = elem.className.replace(/seg\d+/,"noseg");
              }
            }
        });
  },

/**
* renumbers all grapheme groups in an edition starting from 1
*/

  renumberGraGroups: function () {
    var ednVE = this,
        ord = 1;
    $(".grpGra",ednVE.contentDiv).each(function(hindex,elem){
            //renumber ordinals
            elem.className = elem.className.replace(/ord\d+/,"ord"+ ord);
            ord++;
        });
  },

/**
* replaces all element class tags with a new tag
*
* @param string oldTag the current existing tag
* @param string newTag the replacement/updated tag
*/

  replaceTag: function (oldTag,newTag) {
    var ednVE = this;
    $("."+oldTag,ednVE.contentDiv).each(function(hindex,elem){
            //replace Tag
            elem.className = elem.className.replace(oldTag,newTag);
        });
  }

};

/**
* getTCMTransitionBrackets - lookup function
*
* @param string curState indicates the current state
* @param string nextState denoting next state
* @param string tcmPart denoting which part of transition TCM to return Pre, Post or Both
*.@param string sclID is the primary key of the current syllable that this TCM is attached
* @param int defining the ordinal position of this TCM in the current physical line
* @return string of brackets for transition
*/
function getTCMTransitionBrackets(curState, nextState, tcmPart, sclID, ordPos) {
  if (!curState || !tcmBracketsLookup[curState]){
    curState = "S";
  }
  if (!nextState || !tcmBracketsLookup[nextState]){
    nextState = "S";
  }
  if (curState == nextState) {
    return "";
  }

  //find common state which could be none (= S)
  var i,ret,cnt = Math.min(curState.length, nextState.length),
  commonState = "";
  for (i=0; i<cnt; i++) {
    if (curState[i] != nextState[i]) {
      break;
    }
    commonState += curState[i];
  }
  if (commonState == ""){
    commonState = "S";
  }
  if (tcmPart == "Pre"){
    ret = tcmBracketsLookup[curState][commonState];
  } else if (tcmPart == "Post") {
    ret = tcmBracketsLookup[commonState][nextState];
  } else {// post + pre
    ret = tcmBracketsLookup[curState][commonState]+tcmBracketsLookup[commonState][nextState];
  }
  return ret?'<span class="TCM scl'+sclID+' ord'+ordPos+'">'+ret+'</span>':ret;
}

/**
 * tcmBracketsLookup table takes curState[transToState] and returns the string of brackets representing
 * the transition from curState to transToState.
 */
var tcmBracketsLookup = {
    //start
    "S" : {
      "S" :"",
      "A" :"⟨*",
      "D" : "{",
      "DA" : "{⟨*",
      "DR" : "{(*",
      "DU" : "{[",
      "DSd" : "{{{",
      "DI" :  "{⟪",
      "DIU" : "{⟪[",
      "DIR" : "{⟪(*",
      "DISd" : "{⟪{{",
      "DIA" : "{⟪⟨*",
      "I" : "⟪",
      "IR"  : "⟪(*",
      "IU"  : "⟪[",
      "ID"  : "⟪{",
      "ISd" : "⟪{{",
      "IA" : "⟪⟨*",
      "R" :"(*",
      "Sd" :"{{",
      "SdR" : "{{(*",
      "SdU" : "{{[",
      "SdD" : "{{{",
      "SdA" : "{{⟨*",
      "SdI" : "{{⟪",
      "SdIR" : "{{⟪(*",
      "SdIU" : "{{⟪[",
      "SdID" : "{{⟪{",
      "SdIA" :"{{⟪⟨*",
      "U" : "["},
    //Singular TCM States
    "A" : {
      "A" : "",
      "S" : "⟩"},
    "D" : {
      "D" : "",
      "DR" : "(*",
      "DU" : "[",
      "DSd" : "{{",
      "DA" : "⟨*",
      "DI" : "⟪",
      "DIU" : "⟪[",
      "DIR" : "⟪(*",
      "DISd" : "⟪{{",
      "DIA" : "⟪⟨*",
      "S" : "}"},
    "I" : {
      "I"  : "",
      "IR"  : "(*",
      "IU"  : "[",
      "ID"  : "{" ,
      "ISd" : "{{",
      "IA" : "⟨*",
      "S" : "⟫"},
    "R" : {
      "R" : "",
      "S" : ")"},
    "Sd" : {
      "Sd" : "",
      "SdR" : "(*",
      "SdU" : "[",
      "SdD" : "{",
      "SdA" : "⟨*",
      "SdI" : "⟪",
      "SdIR" : "⟪(*",
      "SdIU" : "⟪[",
      "SdID" : "⟪{",
      "SdIA" :"⟪⟨*",
      "S"   : "}}"},
    "U" : {
      "U" : "",
      "S" : "]"},
    //Double TCM States
    "DA" : {
      "DA" : "",
      "D" : "⟩",
      "S" : "⟩}"},
    "DI" : {
      "DI" : "",
      "DIU" : "[",
      "DIR" : "(*",
      "DISd" : "{{",
      "DIA" : "⟨*",
      "D" : "⟫",
      "S" : "⟫}"},
    "DR" : {
      "DR" : "",
      "D" : ")",
      "S" : ")}"},
    "DSd" : {
      "DSd" : "",
      "D" : "}}",
      "S" : "}}}"},
    "DU" : {
      "DU" : "",
      "D" : "]",
      "S" : "]}"},
    "IA" : {
      "IA" : "",
      "I" : "⟩",
      "S" : "⟩⟫"},
    "ID" : {
      "ID" : "",
      "I" : "}",
      "S" : "}⟫"},
    "IR" : {
      "IR" : "",
      "I" : ")",
      "S" : ")⟫"},
    "ISd" : {
      "ISd" : "",
      "I" : "}}",
      "S" : "}}⟫"},
    "IU" : {
      "IU" : "",
      "I" : "]",
      "S" : "]⟫"},
    "SdA" : {
      "SdA" : "",
      "Sd" : "⟩",
      "S" : "⟩}}"},
    "SdD" : {
      "SdD" : "",
      "Sd" : "}",
      "S" : "}}}"},
    "SdI" : {
      "SdI" : "",
      "SdIR" : "(*",
      "SdIU" : "[",
      "SdID" : "{",
      "SdIA" :"⟨*",
      "Sd" : "⟫",
      "S" : "⟫}}"},
    "SdR" : {
      "SdR" : "",
      "Sd" : ")",
      "S" : ")}}"},
    "SdU" : {
      "SdU" : "",
      "Sd" : "]",
      "S" : "]}}"},
    //Triple TCM States
    "DIA" : {
      "DIA" : "",
      "DI" : "⟩",
      "D" : "⟩⟫",
      "S" : "⟩⟫}"},
    "DIR" : {
      "DIR" : "",
      "DI" : ")",
      "D" : ")⟫",
      "S" : ")⟫}"},
    "DISd" : {
      "DISd" : "",
      "DI" : "}}",
      "D" : "}}⟫",
      "S" : "}}⟫}"},
    "DIU" : {
      "DIU" : "",
      "DI" : "]",
      "D" : "]⟫",
      "S" : "]⟫}"},
    "SdIA" : {
      "SdIA" : "",
      "SdI" : "⟩",
      "Sd" : "⟩⟫",
      "S" : "⟩⟫}}"},
    "SdID" : {
      "SdID" : "",
      "SdI" : "}",
      "Sd" : "}⟫",
      "S" : "}⟫}}"},
    "SdIR" : {
      "SdIR" : "",
      "SdI" : ")",
      "Sd" : ")⟫",
      "S" : ")⟫}}"},
    "SdIU" : {
      "SdIU" : "",
      "SdI" : "]",
      "Sd" : "]⟫",
      "S" : "]⟫}}"}
};
