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
* editors transV object
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
* @param transVCfg is a JSON object with the following possible properties
*  "edition" an entity data element which defines the edition and all it's structures.
*
* @returns {transV}

*/

EDITORS.TranslationV =  function(transVCfg) {
  var that = this;
  //read configuration and set defaults
  this.config = transVCfg;
  this.type = "TranslationV";
  this.transType = transVCfg['transType'] ? transVCfg['transType']:null;
  this.dataMgr = transVCfg['dataMgr'] ? transVCfg['dataMgr']:null;
  this.layoutMgr = transVCfg['layoutMgr'] ? transVCfg['layoutMgr']:null;
  this.eventMgr = transVCfg['eventMgr'] ? transVCfg['eventMgr']:null;
  this.edition = transVCfg['edition'] ? transVCfg['edition']:null;
  this.editDiv = transVCfg['editDiv'] ? transVCfg['editDiv']:null;
  this.id = transVCfg['id'] ? transVCfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
EDITORS.TranslationV.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    var transV = this;
    if (this.dataMgr && this.dataMgr.entities) {//warninig!!!!! patch until change code to use dataMgr api
      window.entities = this.dataMgr.entities;
      window.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
    }
    if (this.transType) {
      this.annoTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel[this.transType + '-translation'];//term dependency
    }
    if (!this.annoTypeID) {
      this.annoTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel['translation-annotationtype'];//term dependency
      this.transType = 'generic';
    }
    this.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
    this.transTypeName = this.trmIDtoLabel[this.annoTypeID];
    this.splitterDiv = $('<div id="'+this.id+'splitter"/>');
    this.contentDiv = $('<div id="'+this.id+'textContent" class = "transVContentDiv" contenteditable="true" spellcheck="false" ondragstart="return false;" />');
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
                                                 editor: transV,
                                                 ednID: this.edition.id,
                                                 propVEType: "entPropVE",
                                                 dataMgr: this.dataMgr,
                                                 splitterDiv: this.splitterDiv });
    this.displayProperties = this.propMgr.displayProperties;
    this.createStaticToolbar();
    this.renderEditionTranslation();
  },


/**
* put your comment there...
*
*/

  setFocus: function () {
    $(this.editDiv.firstChild.firstChild).find('.grpGra:first').focus();
  },


/**
* put your comment there...
*
*/

  getEdnID: function () {
    return this.edition.id;
  },


/**
* put your comment there...
*
*/

  refreshEditionHeader: function () {
    var transV = this,
        edition = this.dataMgr.entities.edn[this.edition.id],
        headerDiv = $('h3.ednLabel',this.contentDiv);
    headerDiv.html(edition.value);
    this.layoutMgr.refreshCursor();
  },


/**
* put your comment there...
*
* @param seqID
*/

  refreshPhysLineHeader: function (seqID) {
    var transV = this,
        sequence = this.dataMgr.entities.seq[seqID],
        seqType = this.dataMgr.getTermFromID(sequence.typeID).toLowerCase(),
        lineHeaderSpan;
    if ('linephysical' == seqType) {
      lineHeaderSpan = $('span.textDivHeader.lineseq'+seqID,this.contentDiv);
      lineHeaderSpan.html(sequence.value);
    }
  },


/**
* put your comment there...
*
*/

  createStaticToolbar: function () {
    var transV = this;
    var btnShowPropsName = this.id+'showprops',
        btnDownloadRTFName = this.id+'downloadrtf';
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
                              '" title="Show/Hide property panel">&#x25E8;</button>'+
                            '<div class="toolbuttonlabel">Properties</div>'+
                           '</div>');
    this.propertyBtn = $('#'+btnShowPropsName,this.propertyBtnDiv);
    this.propertyBtn.unbind('click').bind('click',function(e) {
                                           var paneID, editor;
                                           transV.showProperties(!$(this).hasClass("showUI"));
                                         });

    this.downloadRTFBtnDiv = $('<div class="toolbuttondiv">' +
                            '<a href="" >'+
                            '<button class="toolbutton iconbutton" id="'+btnDownloadRTFName+
                              '" title="Download '+this.transType+' translation to RTF">Download</button></a>'+
                            '<div class="toolbuttonlabel">RTF</div>'+
                           '</div>');
    this.downloadRTFBtn = $('#'+btnDownloadRTFName,this.downloadRTFBtnDiv);
    this.downloadRTFLink = this.downloadRTFBtn.parent();
    this.downloadRTFLink.attr('href',basepath+"/services/exportRTFTranslation.php?db="+dbName+"&ednID="+this.edition.id+"&typeID="+this.annoTypeID+"&download=1");

    this.viewToolbar.append(this.propertyBtnDiv);
    this.viewToolbar.append(this.downloadRTFBtnDiv);
    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
  },


/**
* put your comment there...
*
* @param bShow
*/

  showProperties: function (bShow) {
    var transV = this;
    if (transV.propMgr &&
        typeof transV.propMgr.displayProperties == 'function'){
      transV.propMgr.displayProperties(bShow);
      if (this.propertyBtn.hasClass("showUI") && !bShow) {
        this.propertyBtn.removeClass("showUI");
      } else if (!this.propertyBtn.hasClass("showUI") && bShow) {
        this.propertyBtn.addClass("showUI");
      }
    }
  },

  level2Prefix : {
    "syllable":"scl",
    "token":"tok",
    "compound":"cmp"
  },


/**
* put your comment there...
*
* @param selectNodeClasses
*/

  refreshProperty: function (selectNodeClasses) {
    transV.propMgr.showVE();
  },


/**
*
*/

  addEventHandlers: function() {
    var transV = this, entities = this.dataMgr.entities;


/**
* put your comment there...
*
* @param object e System event object
*/

    function annotationsLoadedHandler(e) {
      DEBUG.log("event","Annotations loaded recieved by transV in "+transV.id);
      transV.refreshTagMarkers();
    };

    $(this.editDiv).unbind('annotationsLoaded').bind('annotationsLoaded', annotationsLoadedHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
* @param string entTag Entity tag
*/

    function updateSelectionHandler(e,senderID, selectionIDs, entTag) {
      if (senderID == transV.id) {
        return;
      }
      var i, id;
      DEBUG.log("event","selection changed recieved by transV in "+transV.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      $(".selected", transV.contentDiv).removeClass("selected");
      if (selectionIDs && selectionIDs.length) {
        $.each(selectionIDs, function(i,val) {
          if (val && val.length) {
            $('.'+val,transV.contentDiv).addClass("selected");
            var entity = $('.'+val,transV.contentDiv),j;
            if (entity && entity.length > 0) {
              entity.addClass("selected");
            } else {
              entity = $('.'+entTag,transV.contentDiv);
              if (entity && entity.length > 0) {
                entity.addClass("selected");
              }
            }
          }
        });
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

    function ednDblClickHandler(e) {
      var classes = $(this).attr('class'),
      entTag = classes.match(/edn\d+/)[0];
      transV.propMgr.showVE(null,entTag);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    $(".ednLabel", this.editDiv).unbind('dblclick').bind('dblclick',ednDblClickHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function spanDblClickHandler(e) {
      var classes = $(this).attr('class'),
      entTag = classes.match(/(?:cmp|tok)\d+/);
      if (entTag && entTag.length == 1) {
        entTag = entTag[0];
        transV.propMgr.showVE(null,entTag);
        DEBUG.log("event","span dblclick in "+transV.id+" on word translation for "+ entTag);
        $(".selected", transV.contentDiv).removeClass("selected");
        $('.'+entTag,transV.contentDiv).addClass("selected");
        $('.editContainer').trigger('updateselection',[transV.id,[entTag],entTag]);
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all section elements
    $(".wordTranslation", this.editDiv).unbind('dblclick').bind('dblclick',spanDblClickHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function secDblClickHandler(e) {
      var classes = $(this).attr('class'),
      entTag = classes.match(/seq\d+/)[0];
      transV.propMgr.showVE(null,entTag);
      DEBUG.log("event","section dblclick in "+transV.id+" selected ids "+ entTag);
      $(".selected", transV.contentDiv).removeClass("selected");
      $('.'+entTag,transV.contentDiv).addClass("selected");
      $('.editContainer').trigger('updateselection',[transV.id,[entTag],entTag]);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all section elements
    $(".section,.secHeader", this.editDiv).unbind('dblclick').bind('dblclick',secDblClickHandler);


/**
* put your comment there...
*
* @param object e System event object
*
* @returns true|false
*/

    function veScrollHandler(e) {
      var top = this.scrollTop, i=1, maxLine = 500, grpGra, segID, lineFraction;
      if (!transV.supressSynchOnce) {
        DEBUG.log("data","scroll");
        if ($('body').hasClass('synchScroll')) {
          for (i; i<maxLine; i++) {
            grpGra = $(this).find('.grpGra.ordL'+i+':first');
            if (grpGra && grpGra.length ==1) {
              grpGra = grpGra.get(0);
              if (grpGra.offsetTop <= top && grpGra.offsetTop + grpGra.offsetHeight > top) {
                segID = grpGra.className.match(/seg\d+/)[0];
                lineFraction = (top - grpGra.offsetTop)/grpGra.offsetHeight;
                $('.editContainer').trigger('synchronize',[transV.id,segID,lineFraction]);
                break;
              }
            }
          }
        }
      } else {
        delete transV.supressSynchOnce;
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all syllable elements
    this.contentDiv.unbind("scroll").bind("scroll", veScrollHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param anchorSegID
* @param visFraction
*/

    function synchronizeHandler(e,senderID, anchorSegID, visFraction) {
      var top, grpGra;
      if (senderID == transV.id) {
        return;
      }
      DEBUG.log("event","synch request recieved by "+transV.id+" from "+senderID+" with segID "+ anchorSegID + (visFraction?" with fraction" + visFraction:""));
      grpGra = $('.'+anchorSegID+':first',this);
      if (grpGra && grpGra.length ==1) {
        grpGra = grpGra.get(0);
        top = Math.round(grpGra.offsetHeight * visFraction) + grpGra.offsetTop;
        transV.supressSynchOnce = true;
        transV.contentDiv.scrollTop(top);
      }
    };

    $(this.editDiv).unbind('synchronize').bind('synchronize', synchronizeHandler);
  },



/**
* render this edition in structural layout with tokenization
*
*/

  renderEditionTranslation: function () {
    DEBUG.traceEntry("transV.renderEditionTranslation","ednID = " + this.edition.id);
    //create html of the edition's translation for edition textAnalysis structures depth first search
    //for translation text wrapping each text in a div or span acording to the structure and nesting level.
    var transV = this, entities = this.dataMgr.entities,
        textSeq, textDivSeq, physicalSeq, lineSeq,
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
    if (!textAnalysisSeq || !textAnalysisSeq.entityIDs || textAnalysisSeq.entityIDs.length == 0) { // create a physical seq of clusters?
      alert("No Text Analysis found for edition "+this.edition.value + " id="+this.id);
      //todo add code to return UI to physical presentation
      return;
    }

    var physicalLineSeqIDs = physicalSeq?physicalSeq.entityIDs:null,
        textAnalysisIDs = (textAnalysisSeq.entityIDs && textAnalysisSeq.entityIDs.length)?textAnalysisSeq.entityIDs:null;
    // output Header
    html = '<div class="editionTitleDiv" contenteditable="false"><h3 class="ednLabel edn'+this.edition.id+'"> '+this.transTypeName+' for '+this.edition.value+ '</h3></div>';
    $(this.contentDiv).html(html);
    this.grpOrd = 1;



/**
* put your comment there...
*
* @param string entGID Entity global id (prefix:id)
*/

    function findEntityTranslation(entGID) {
      var prefix = entGID.substring(0,3),
          id = entGID.replace(":","").substring(3),
          entTag = prefix + id, translation = null,
          annoIDsByType = transV.dataMgr.entTag2LinkedAnoIDsByType[entTag];
      if (annoIDsByType && annoIDsByType[transV.annoTypeID] && annoIDsByType[transV.annoTypeID].length > 0) {
        translation = transV.dataMgr.getEntity('ano',annoIDsByType[transV.annoTypeID][0]).text;
        translation = translation.trim();
      }
      return translation;
    }


/**
* put your comment there...
*
* @param seqID
* @param level
* @param parentDiv
*
* @returns {String}
*/

    function renderStructTranslationHTML(seqID, level, parentDiv) {
      var lvl = level +1, seqHtml, proseDiv, i, entGID, prefix, entTag, entID, translation,
          sequence = entities['seq'][seqID], sectionDiv, seqIDs, seqType;
      if (sequence) {
          seqIDs = sequence.entityIDs;
          seqType = transV.trmIDtoLabel[sequence.typeID];
      }
      if (!seqIDs || seqIDs.length == 0) {
        DEBUG.log("warn","Found empty structural sequence element seq"+ seqID +" for edition "+transV.edition.value + " id="+transV.id);
        return "";
      }
      if (sequence.label && sequence.sup) {
        parentDiv.append('<div class="secTitleHeader level'+level+' seq'+seqID+'">'+sequence.sup + " " +sequence.label+'</div>')
      } else if (sequence.label) {//output section title
        parentDiv.append('<div class="secTitle level'+level+' seq'+seqID+'">'+sequence.label+'</div>')
      } else if (sequence.sup) {//output section hdr
        parentDiv.append('<div class="secHeader level'+level+' seq'+seqID+'">'+sequence.sup+'</div>')
      }
      //start section div
      sectionDiv = $('<div class="section level'+level+' '+seqType+' seq'+seqID+'"/>');
      parentDiv.append(sectionDiv);
      translation = findEntityTranslation('seq'+seqID);
      if (translation && translation.length > 0) {
        sectionDiv.html(translation);
      } else {
        for (i=0; i<seqIDs.length; i++) {
          entGID = seqIDs[i];
          prefix = entGID.substring(0,3);
          entID = entGID.substr(4),
          entTag = prefix+entID;
          if (prefix == 'seq') {
            if (proseDiv) { // close prose div
              proseDiv = null;//TODO possible enhancement check if phrase and nest span and recurse to expand tokens else set to null
            }
            renderStructTranslationHTML(entID, lvl, sectionDiv);
          } else if (prefix.match(/cmp|tok/)) {
            if (!proseDiv) { // open prose div
              proseDiv = $('<div class="prose level'+lvl+' seq'+seqID+'"/>');
              sectionDiv.append(proseDiv);
            }
            translation = findEntityTranslation(entGID);
            if (translation && translation.length > 0) {
              proseDiv.html( proseDiv.html() + '<span class="wordTranslation '+entTag+'">' + translation + '</span>');
            }
          }else{
            DEBUG.log("warn","Found unknown structural element "+ entGID +" for edition "+transV.edition.value + " id="+transV.id);
            continue;
          }
        }
      }
//      parentDiv.append($("<br/>"));
    }

    this.grpNodeSeq = 0;//reset count so that system can mark order of leaf nodes in selection
    var entGID, entID, prefix, proseDiv, translation,
        analysisGID = 'seq'+textAnalysisSeq.id;
    for (i=0; i<textAnalysisIDs.length; i++) {
      entGID = textAnalysisIDs[i];
      prefix = entGID.substring(0,3);
      entID = entGID.substr(4);
      if (prefix == 'seq') {
        if (proseDiv) { // close prose div
          proseDiv = null;
        }
        renderStructTranslationHTML(entID, 1,$(this.contentDiv));
      } else if (prefix.match(/cmp|tok/)) {
        if (!proseDiv) { // open prose div
          proseDiv = $('<div class="prose level1 '+analysisGID+'"/>');
          $(this.contentDiv).append(proseDiv);
        }
        translation = findEntityTranslation(entGID);
        if (translation && translation.length > 0) {
          proseDiv.html( proseDiv.html() + ' ' + translation);
        }
      }else{
        DEBUG.log("warn","Found unknown structural element "+ entGID +" for edition "+this.edition.value + " id="+this.id);
        continue;
      }
    }
    DEBUG.trace("transV.renderEditionTranslation","before add Handlers to DOM for edn " + this.edition.id);
    this.addEventHandlers(); // needs to be done after content created
    DEBUG.trace("transV.renderEditionTranslation","after add Handlers to DOM for edn " + this.edition.id);
    DEBUG.traceExit("transV.renderEditionTranslation","ednID = " + this.edition.id);
  }
}


