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
* editors LemmaVE object
*
* viewer for tokens and compounds (creates lemma when required)
* viewer editor for lemmas
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
* put your comment there...
*
* @type Object
*/

EDITORS.LemmaVE =  function(lemmaVECfg,prefix,id) {
  this.config = lemmaVECfg;
  this.catID = lemmaVECfg['catID'] ? lemmaVECfg['catID']:null;
  this.ednID = lemmaVECfg['ednID'] ? lemmaVECfg['ednID']:null;
  this.prefix = lemmaVECfg['prefix'] ? lemmaVECfg['prefix']:null;
  this.id = lemmaVECfg['id'] ? "lemVE"+lemmaVECfg['id']:null;
  this.entID = lemmaVECfg['entID'] ? lemmaVECfg['entID']:null;//lemma, token or compound
  this.wordlistVE = lemmaVECfg['editor'] ? lemmaVECfg['editor']:null;
  this.dataMgr = lemmaVECfg['dataMgr'] ? lemmaVECfg['dataMgr']:null;
  this.propMgr = lemmaVECfg['propMgr'] ? lemmaVECfg['propMgr']:null;
  this.editDiv = lemmaVECfg['editDiv'] ? $(lemmaVECfg['editDiv']):null;
  this.tag = this.prefix + this.entID;
  if (this.wordlistVE) { // install API link
    this.wordlistVE.lemmaVE = this;
  }
  this.init();
  this.data = {};
  this.dirty = false;
  return this;
};

/**
* put your comment there...
*
* @type Object
*/

EDITORS.LemmaVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("init","init lemma editor");
    if (this.prefix && this.entID && this.dataMgr.getEntity(this.prefix,this.entID)) {
      this.showLemma();
    } else {
      this.editDiv.html('Lemma Editor');
    }
    DEBUG.traceExit("init","init lemma editor");
  },

/*****************  property manager interface  *******************************/

/**
* put your comment there...
*
* @param gid
*/

  setEntity: function(gid) {
    DEBUG.traceEntry("setEntity");
    if (gid) {
      var tag = gid.replace(":","");
          prefix = tag.substring(0,3),
          id = tag.substring(3);
      this.showLemma(prefix,id);
    } else {
      this.showLemma();
    }
    DEBUG.traceExit("setEntity");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getType: function() {
    return "lemmaVE";
  },


/**
* put your comment there...
*
*/

  show: function() {
    DEBUG.traceEntry("show");
    this.editDiv.show();
    DEBUG.traceExit("show");
  },


/**
* put your comment there...
*
*/

  hide: function() {
    DEBUG.traceEntry("hide");
    this.editDiv.hide();
    DEBUG.traceExit("hide");
  },

/************* rebuild Lemma interface ****************/

/**
* put your comment there...
*
* @param prefix
* @param id
*/

  showLemma: function(prefix,id) {
    DEBUG.traceEntry("showLemma");
    if (prefix && id && this.dataMgr.getEntity(prefix,id)) {
      this.prefix = prefix;
      this.entID = id;
      this.tag = this.prefix + this.entID;
      this.entity = this.dataMgr.getEntity(prefix,id);
    }
    if (this.prefix && this.entID && this.entity) {
      this.isLemma = (this.prefix == "lem");
      this.editDiv.html('');
      this.createValueUI();
      this.createDescriptionUI();
      this.createPOSUI();
      this.createTransUI();
      if (this.isLemma) {
        this.createCompoundAnalysisUI();
        this.createLinkTypeUI()
        this.createRelatedUI()
        this.createAttestedUI();
        this.createTaggingUI();
        this.createAnnotationUI();
      }
    } else {
      this.editDiv.html('Lemma Editor - no lemma information found.');
    }
    DEBUG.traceExit("showLemma");
  },

/*************  Value Interface ****************/

/**
* put your comment there...
*
*/

  createValueUI: function() {
    var lemmaVE = this,
        value = this.isLemma ? this.entity.value.replace(/ʔ/g,''):this.entity.transcr.replace(/ʔ/g,'');
    DEBUG.traceEntry("createValueUI");
    //create UI container
    this.valueUI = $('<div class="valueUI"></div>');
    this.editDiv.append(this.valueUI);
    //create label with navigation
    this.valueUI.append($('<div class="propDisplayUI">'+
                    '<div class="propFlipNavDiv propDisplayElement"><div class="med-flip lemmaNavButton"><span/></div></div>'+
                    '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                    '<div class="lemmaNavDiv propDisplayElement"><div class="med-prevword lemmaNavButton"><span/></div><div class="med-nextword lemmaNavButton"><span/></div></div>'+
                    '</div>'));
    //create input with save button
    this.valueUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.valueUI).unbind("click").bind("click",function(e) {
        lemmaVE.valueUI.addClass("edit");
        $('div.valueInputDiv input',this.valueUI).focus();
//        $('div.valueInputDiv input',this.valueUI).select();
      });
      //blur to cancel
      $('div.valueInputDiv input',this.valueUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
          lemmaVE.valueUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.valueUI).unbind("input").bind("input",function(e) {
        var curInput = $(this).val(), btnText = $('.saveDiv',lemmaVE.valueUI).html();
        if ($('div.valueLabelDiv',lemmaVE.valueUI).html() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
        if (!curInput && btnText == "Save") {
          $('.saveDiv',lemmaVE.valueUI).html("Delete").css('color','red');
        } else if (curInput && btnText != "Save") {
          $('.saveDiv',lemmaVE.valueUI).html("Save").css('color','white');
        }
      });
      //flip prop display
      $('.med-flip',this.valueUI).unbind("click").bind("click",function(e) {
        if (lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
          lemmaVE.propMgr.showVE("tabPropVE",lemmaVE.propMgr.currentVE.tag);
        }
      });
      //previous lemma
      $('.med-prevword',this.valueUI).unbind("click").bind("click",function(e) {
        if (lemmaVE.wordlistVE && lemmaVE.wordlistVE.prevWord) {
          lemmaVE.wordlistVE.prevWord();
        }
      });
      //next lemma
      $('.med-nextword',this.valueUI).unbind("click").bind("click",function(e) {
        if (lemmaVE.wordlistVE && lemmaVE.wordlistVE.nextWord) {
          lemmaVE.wordlistVE.nextWord();
        }
      });
      //save data
      $('.saveDiv',this.valueUI).unbind("click").bind("click",function(e) {
        var lemProp = {}, isSave = ($(this).html()== 'Save'),
            origText = $('div.valueLabelDiv',lemmaVE.valueUI).html();
        if (isSave) {
          if ($('.propEditUI',lemmaVE.valueUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',lemmaVE.valueUI).val();
            lemProp["value"] = val;
            $('div.valueLabelDiv',lemmaVE.valueUI).html(val);
            $('.propEditUI',lemmaVE.valueUI).removeClass('dirty');
            lemmaVE.saveLemma(lemProp);
          }
          lemmaVE.valueUI.removeClass("edit");
        } else if (confirm('Are you sure you want to delete lemma "' + origText + '"?')) { // is delete
          lemmaVE.deleteLemma();
        }
      });
    DEBUG.traceExit("createValueUI");
  },

  /*************  Compound Analysis Interface ****************/

/**
*
*/

  createCompoundAnalysisUI: function() {
    var lemmaVE = this,
        value = (this.entity.compAnalysis?this.entity.compAnalysis:"Compound Analysis"),
        editValue = (this.entity.compAnalysis?this.entity.compAnalysis:this.entity.value.replace(/ʔ/g,''));
    DEBUG.traceEntry("createCompoundAnalysisUI");
    //create UI container
    this.compUI = $('<div class="compUI"></div>');
    this.editDiv.append(this.compUI);
    //create label with navigation
    this.compUI.append($('<div class="propDisplayUI">'+
                     '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                    '</div>'));
    //create input with save button
    this.compUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+editValue+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.compUI).unbind("click").bind("click",function(e) {
        lemmaVE.compUI.addClass("edit");
        $('div.valueInputDiv input',this.compUI).focus();
//        $('div.valueInputDiv input',this.valueUI).select();
      });
      //blur to cancel
      $('div.valueInputDiv input',this.compUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
          lemmaVE.compUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.compUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.compUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.compUI).unbind("click").bind("click",function(e) {
        var lemProp = {}, compAnalysis, nodeKey, rootKey = null, rootNode, compKeys;


/**
* put your comment there...
*
* @param key
*/

        function resolveSubLemma(key) {
          var node = compAnalysis[key], j, lemma,
              subKeys = null, matches = [], possMatches = [];
          if (node.lemmaIDs) {
            for (j in node.lemmaIDs) {
              lemma = lemmaVE.dataMgr.getEntity('lem',node.lemmaIDs[j]);
              if (node.subKeys && lemma && lemma.compAnalysis &&
                  node.markup == lemma.compAnalysis) {
                matches.push(lemma);
              } else {
                possMatches.push(lemma);
              }
            }
          }
          if (matches.length == 1) {
            compAnalysis[key]['lemID'] = matches[0].id;
            if (node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (matches.length > 1) { // user selection
            for (j in matches) {
              lemma = matches[j];
              if (confirm("Would you like to link constituent lemma '"+node.value+
                          "' with POS of "+lemmaVE.dataMgr.getTermFromID(lemma.pos))) {
                compAnalysis[key]['lemID'] = lemma.id;
                break;
              }
            }
            if (!compAnalysis[key]['lemID'] && node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (possMatches.length == 1) {
            compAnalysis[key]['lemID'] = possMatches[0].id;
            if (node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (possMatches.length > 1) { // user selection
            for (j in possMatches) {
              lemma = possMatches[j];
              if (confirm("Would you like to link constituent lemma '"+node.value+
                          "' with POS of "+lemmaVE.dataMgr.getTermFromID(lemma.pos))) {
                compAnalysis[key]['lemID'] = lemma.id;
                break;
              }
            }
            if (node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (node.subKeys) {
            subKeys = node.subKeys;
          }
          if (subKeys) {
            for (j in subKeys) {
              resolveSubLemma(subKeys[j]);
            }
          }
        }

        if ($('.propEditUI',lemmaVE.compUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.compUI).val();
          compAnalysis = lemmaVE.validateCompAnalysis(val);
          if (compAnalysis) {
            compKeys = Object.keys(compAnalysis);
            for (i in compKeys) {
              nodeKey = compKeys[i];
              if (compAnalysis[nodeKey].root) {
                rootKey = nodeKey;
                resolveSubLemma(rootKey);
                break;
              }
            }
            $('div.valueLabelDiv',lemmaVE.compUI).html(val);
          } else {
            $('div.valueLabelDiv',lemmaVE.compUI).html("Compound Analysis");
          }
          $('.propEditUI',lemmaVE.compUI).removeClass('dirty');
          lemmaVE.saveCompoundAnalysis(compAnalysis, rootKey);
          lemmaVE.compUI.removeClass("edit");
        }
      });
    DEBUG.traceExit("createCompoundAnalysisUI");
  },


/**
* put your comment there...
*
* @param compAnal
*
* @returns {Object}
*/

  validateCompAnalysis: function(compAnal) {
    if (!compAnal) {
      return compAnal;
    }
    var lemmaVE = this, strAnalysis = compAnal, value, markup, hasHead, lemma, lemID,
        subNodes, compNodes, i, j, k, key, subKey, l=0, nodeLookup = {}, lemmaIDs,
        reSqBrkOpen = /\[/g,
        reSqBrkClose = /\]/g,
        reCompMarkup = /[\[\]!]/g,//find all compound analysis markup characters
        reBrkNodes = /\[[^\[\]]+\]/g;//text surrounded by square brackets
    if (strAnalysis.match(reSqBrkOpen).length != strAnalysis.match(reSqBrkClose).length) {
      alert("Compound Analysis has mismatched brackets, please correct before saving.");
      return false;
    }
    if (!strAnalysis.match(/!/)) {
      alert("Compound Analysis must use '!' to mark at least one constituent as HEAD WORD.");
      return false;
    }
    subNodes = strAnalysis.match(reBrkNodes);
    if (subNodes.length > 1) {
      for (i in subNodes) {
        l++;
        key = "n"+l;
        markup = subNodes[i];
        value = markup.replace(reCompMarkup,"");
        nodeLookup[key] = { 'markup':markup,
                            'value':value};
        lemmaIDs = this.wordlistVE.lookupLemma(value);
        if (lemmaIDs && lemmaIDs.length) {
          for (k=0; k<lemmaIDs.length; k++) {
            lemID = lemmaIDs[k];
            lemma = this.dataMgr.getEntity('lem',lemID);
            if (lemma && !lemma.compAnalysis) {
              nodeLookup[key]['lemID'] = lemID;
              break;
            }
          }
        }
        if (markup.match(/^\[!/)) {
          nodeLookup[key]['head'] = 1;
        }
        strAnalysis = strAnalysis.replace(markup,key);
      }
      while (compNodes = strAnalysis.match(reBrkNodes)) {//process compound constituents
        if (strAnalysis.match(reSqBrkOpen).length != strAnalysis.match(reSqBrkClose).length) {
          alert("Compound Analysis has mismatched brackets, please correct before saving.");
          return false;
        }
        for (i in compNodes) {
          l++;
          key = "n"+l;
          markup = compNodes[i];
          value = markup.replace(reCompMarkup,"");
          subNodes = value.match(/(n\d+)/g);//find all node keys
          hasHead = false;
          for (j in subNodes) {
            subKey = subNodes[j];
            if (nodeLookup[subKey].head) {
              hasHead = true;
            }
            markup = markup.replace(subKey,nodeLookup[subKey].markup);
            value = value.replace(subKey,nodeLookup[subKey].value);
          }
          nodeLookup[key] = { 'markup':markup,
                              'subKeys': subNodes,
                              'value':value};
          lemmaIDs = this.wordlistVE.lookupLemma(value);
          if (lemmaIDs && lemmaIDs.length) {
            for (k=0; k<lemmaIDs.length; k++) {// search the lemma for a match to the markup
              lemID = lemmaIDs[k];
              lemma = this.dataMgr.getEntity('lem',lemID);
              if (lemma && ((lemma.compAnalysis &&
                  (markup == lemma.compAnalysis)) || markup == compAnal)) {
                nodeLookup[key]['lemID'] = lemID;
                if (lemID == this.entID) {
                  nodeLookup[key]['root'] = 1;
                }
                break;
              }
            }
          }
          if (!hasHead) {
            alert("Compound Analysis has missing HEAD WORD for constituent '"+markup+"', please correct before saving.");
            return false;
          }
          if (markup.match(/^\[!/)) {
            nodeLookup[key]['head'] = 1;
          }
          strAnalysis = strAnalysis.replace(compNodes[i],key);
        }
      }
    } else {
      alert("Compound Analysis must have more than 1 subnode, please correct before saving.");
      return false;
    }
    if (strAnalysis.match(/(\[|\])/)) {
      alert("Compound Analysis has misaligned brackets, please correct before saving.");
      return false;
    }
    if (nodeLookup[key].value  != lemmaVE.entity.value.replace(/ʔ/g,'')) {
      alert("Compound Analysis compound does not match constituents, please correct before saving.");
      return false;
    }
    return nodeLookup;
  },

/************* Etymology Interface ****************/

/**
* put your comment there...
*
*/

  createDescriptionUI: function() {
    var lemmaVE = this,
        value = this.isLemma ? this.entity.gloss:"";
    DEBUG.traceEntry("createDescriptionUI");
    //create UI container
    this.descrUI = $('<div class="descrUI"></div>');
    this.editDiv.append(this.descrUI);
    //create label
    this.descrUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"Etymology")+'</div>'+
                          '</div>'));
    //create input with save button
    this.descrUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+(value?value:"")+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.descrUI).unbind("click").bind("click",function(e) {
        lemmaVE.descrUI.addClass("edit");
        $('div.valueInputDiv input',this.descrUI).focus();
        //$('div.valueInputDiv input',this.descrUI).select();
      });
      //blur to cancel
      $('div.valueInputDiv input',this.descrUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
          lemmaVE.descrUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.descrUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.descrUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.descrUI).unbind("click").bind("click",function(e) {
        var lemProp = {};
        if ($('.propEditUI',lemmaVE.descrUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.descrUI).val();
          lemProp["gloss"] = val;
          $('div.valueLabelDiv',lemmaVE.descrUI).html(val);
          $('.propEditUI',lemmaVE.descrUI).removeClass('dirty');
          lemmaVE.saveLemma(lemProp);
        }
        lemmaVE.descrUI.removeClass("edit");
      });
    DEBUG.traceExit("createDescriptionUI");
  },

/*************  radio button helper ****************/

/**
* put your comment there...
*
* @param grpName
* @param uiClass
* @param list
* @param initTermID
* @param defTermID
* @param isUncertain
* @param clearsAll
*/

  createRadioGroupUI: function(grpName, uiClass, list, initTermID, defTermID, isUncertain, clearsAll) {
    var lemmaVE = this,
        radioGroup = $('<div class="radioGroupUI'+(uiClass?' '+uiClass:'')+'"></div>'),
        i, listDef, buttonDiv;
    if (grpName) {
      radioGroup.prop('grpName',grpName);
    }
    if (initTermID) {
      radioGroup.prop('origID',initTermID);
    }
    if (isUncertain) {
      radioGroup.prop('origCF',2);
    }
    if (clearsAll) {
      radioGroup.prop('clearsAll',clearsAll);
    }
    for (i in list) {
      listDef = list[i];
      buttonDiv = $('<button class="'+
                    (listDef.type?listDef.type:"buttonDiv")+
                    '" type="button"><span>'+listDef.label+'</span></button>');
      buttonDiv.prop('trmID',listDef.trmID);
      if (listDef.showSub || listDef.showSub == "") {
        buttonDiv.prop('showSub',listDef.showSub);
      }
      if (listDef.showInfl) {
        buttonDiv.prop('showInfl',listDef.showInfl);
      }
      if (initTermID && listDef.trmID == initTermID) {// initialize single selection, assumes list has unique trmIDs
        buttonDiv.addClass('selected');
        if (isUncertain) {
          buttonDiv.addClass('uncertain');
        }
      }
      if (defTermID && listDef.trmID == defTermID) {// initialize single selection, assumes list has unique trmIDs
        buttonDiv.addClass('default');
      }
      radioGroup.append(buttonDiv);
    }
    if (defTermID && $('.buttonDiv.selected',radioGroup).length == 0 && $('.buttonDiv.default',radioGroup).length == 1) {
//      $('.buttonDiv.default',radioGroup).addClass('selected');
      //since there is not initial value selected it must be null or out of range
      //so default represents a change in value, need to mark dirty
//      radioGroup.addClass("dirty");
    }
    //click to select
    $('.buttonDiv',radioGroup).unbind("click").bind("click",function(e) {
      var ctxDiv = $(this).parent().parent(),showSub = $(this).prop('showSub'),
          classes = ctxDiv.attr('class').replace(/show[^\s]+/g,"").trim();//get all non show classes
      if (showSub || showSub == "") {//showSub can be blank to remove submenus
        classes += showSub?" "+showSub :"";
        ctxDiv.attr('class',classes);
      }
      //if button selected and uncertainty remove both
      if ($(this).hasClass('uncertain')) {
        $(this).removeClass('uncertain');
        $(this).removeClass('selected');
      } else if ($(this).hasClass('selected')) {//if button selected add uncertainty
        $(this).addClass('uncertain');
      } else {
        if (radioGroup.prop('clearsAll')) {//reset all buttons and update dirty flags
          ctxDiv.find('.selected').removeClass('selected');
          ctxDiv.find('.uncertain').removeClass('uncertain');
          $('.radioGroupUI',ctxDiv).each(function(index, elem) {
            var radioGroup2 = $(elem);
            if (radioGroup2 != radioGroup) { // not current group
              if (radioGroup2.prop('origCF') ||  radioGroup2.prop('origID')) { // is dirty
                if (!radioGroup2.hasClass("dirty")) {
                  radioGroup2.addClass("dirty");
                }
              } else { // not dirty so remove flags
                if (radioGroup2.hasClass("dirty")) {
                  radioGroup2.removeClass("dirty");
                }
              }
            }
          });
        } else {//remove selected and uncertainty from all group buttons
          $(this).parent().find('.selected').removeClass('selected');
          $(this).parent().find('.uncertain').removeClass('uncertain');
        }
        //add selected to this button and force refresh
        $(this).addClass('selected');
      }
      //check value against original and update dirty flag for group
      if ((radioGroup.prop('origCF') && !$(this).hasClass('uncertain')) ||
          (!radioGroup.prop('origCF') && $(this).hasClass('uncertain')) ||
          (!$(this).hasClass('selected') && radioGroup.prop('origID'))||
          ($(this).hasClass('selected') && radioGroup.prop('origID') != $(this).prop('trmID'))) { // is dirty
        if (!radioGroup.hasClass("dirty")) {
          radioGroup.addClass("dirty");
        }
      } else { // not dirty so remove flags
        if (radioGroup.hasClass("dirty")) {
          radioGroup.removeClass("dirty");
        }
      }
      //check dirty flag of all groups and update UI dirty flag for save button.
      if ( ctxDiv.find('.dirty').length) { //properties have changed
        if (!ctxDiv.hasClass("dirty")) {
          ctxDiv.addClass("dirty");
        }
      } else {
        if (ctxDiv.hasClass("dirty")) {
          ctxDiv.removeClass("dirty");
        }
      }
    });
    return radioGroup;
  },

/************* Part Of Speech Interface ****************/

/**
* put your comment there...
*
*/

  createPOSEditUI: function() {
    var lemmaVE = this, showSub,
        nounID = 664,
        pronID = 667,
        verbID = 685,
        numID = 662,
        adjID = 674,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:null,
        spos = this.isLemma && this.entity.spos ? this.entity.spos:null,
        ggen = this.isLemma && this.entity.gender ? this.entity.gender:null,
        vclass = this.isLemma && this.entity['class'] ? this.entity['class']:null,
        cf = this.isLemma && this.entity.certainty ? this.entity.certainty:[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        posEdit = $('<div class="posEditUI radioUI"></div>'),
        listPOS = [{label: "adj.",trmID:674,showSub:"showSubAdj",showInfl:"showCase showAdjGender showNumber showAdjConj"},
                   {label: "adp.",trmID:661,showSub:""},
                   {label: "adv.",trmID:655,showSub:""},
                   {label: "ind.",trmID:656,showSub:""},
                   {label: "noun",trmID:664,showSub:"showSubNoun showGramGender",showInfl:"showGender showCase showNumber"},
                   {label: "num.",trmID:662,showSub:"showSubNum"},
                   {label: "pron.",trmID:667,showSub:"showSubPron"},
                   {label: "v.",trmID:685,showSub:"",showInfl:"showSubVerb"}],
        listSubAdj = [{label: "common",trmID:675,showInfl:"showCase showAdjGender showNumber showAdjConj"},
                      {label: "gdv.",trmID:676,showInfl:"showCase showAdjGender showNumber showAdjConj"},
                       {label: "pp.",trmID:677,showInfl:"showCase showAdjGender showNumber showAdjConj"},
                       {label: "pres. part.",trmID:678}],
        listSubNum = [{label: "ord.",trmID:683,showInfl:"showCase showGender showNumber"},
                       {label: "card.",trmID:682,showInfl:"showCase showGender showNumber"}],
        listSubNoun = [{label: "common",trmID:665,showInfl:"showCase showGender showNumber"},
                       {label: "proper",trmID:666,showInfl:"showCase showGender showNumber"}],
        listSubPron = [{label: "dem.",trmID:669,showInfl:"showCase showGender showNumber"},
                       {label: "indef.",trmID:670,showInfl:"showCase showGender showNumber"},
                       {label: "interr.",trmID:671,showInfl:"showCase showGender showNumber"},
                       {label: "pers.",trmID:668,showInfl:"showCase showNumber"},
                       {label: "rel.",trmID:672,showInfl:"showCase showGender showNumber"},
                       {label: "refl.",trmID:673,showInfl:"showCase showNumber"}],
        listGramGen = [{label: "m.",trmID:491},
                       {label: "mn.",trmID:494,showInfl:"showGender"},
                       {label: "n.",trmID:492},
                       {label: "mfn.",trmID:1383,showInfl:"showGender"},
                       {label: "nf.",trmID:1384,showInfl:"showGender"},
                       {label: "f.",trmID:493},
                       {label: "mf.",trmID:495,showInfl:"showGender"}],
        listClass = [{label: "1",trmID:831},
                     {label: "2",trmID:832},
                     {label: "3",trmID:833},
                     {label: "4",trmID:834},
                     {label: "5",trmID:835}],
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Clear",type:"clearBtnDiv"}];
    posEdit.append(this.createRadioGroupUI("pos", "PosUI", listPOS,pos,null,cf[0]==2,1));
    posEdit.append(this.createRadioGroupUI("spos", "SubNounUI", listSubNoun,spos,665, cf[1]==2));
    posEdit.append(this.createRadioGroupUI("gender", "GramGenderUI", listGramGen, ggen, 491, cf[2]==2));
    posEdit.append(this.createRadioGroupUI("spos", "SubNumeralUI", listSubNum,spos, 683, cf[1]==2));
    posEdit.append(this.createRadioGroupUI("spos", "SubAdjectiveUI", listSubAdj,spos,675, cf[1]==2));
    posEdit.append(this.createRadioGroupUI("class", "ClassUI", listClass,vclass,null, cf[3]==2));
    posEdit.append(this.createRadioGroupUI("spos", "SubPronUI", listSubPron,spos,null, cf[1]==2));
    posEdit.append(this.createRadioGroupUI(null, null, listCtrl));
    showSub = $('.PosUI button.buttonDiv.selected',posEdit).prop('showSub');
    if (showSub && showSub.length) {// ensure sub groups are shown
      posEdit.addClass(showSub);
    }
    //click to clear
    $('.clearBtnDiv',posEdit).unbind("click").bind("click",function(e) {
      var ctxDiv = posEdit;
      //remove selected and uncertainty from all groups buttons
      ctxDiv.find('.selected').removeClass('selected');
      ctxDiv.find('.uncertain').removeClass('uncertain');
      //check each radioGroup against it's original values and update dirty flag for group
      $('.radioGroupUI',ctxDiv).each(function(index, elem) {
        var radioGroup = $(elem);
        if (radioGroup.prop('origCF') ||  radioGroup.prop('origID')) { // is dirty
          if (!radioGroup.hasClass("dirty")) {
            radioGroup.addClass("dirty");
          }
        } else { // not dirty so remove flags
          if (radioGroup.hasClass("dirty")) {
            radioGroup.removeClass("dirty");
          }
        }
      });
      //check dirty flag of all groups and update UI dirty flag for save button.
      if ( ctxDiv.find('.dirty').length) { //properties have changed
        if (!ctxDiv.hasClass("dirty")) {
          ctxDiv.addClass("dirty");
        }
      } else {
        if (ctxDiv.hasClass("dirty")) {
          ctxDiv.removeClass("dirty");
        }
      }
    });
    //save data
    $('.saveBtnDiv',posEdit).unbind("click").bind("click",function(e) {
      var value = 'POS', posVal, posID, posCF = 3, lemProps = {},
          sposVal, sposID, sposCF = 3, genVal, genID, genCF = 3,
          classCF = 3,declCF = 3,// WARNING!! hardcode to unused until Gandhari declination research is finished
          posButtonDiv, subPosButtonDiv, genButtonDiv;
      if ($('.posEditUI',lemmaVE.posUI).hasClass('dirty')) {
        //calculate lemma display for POS - SPOS - etc
        posButtonDiv = $('.PosUI button.buttonDiv.selected',posEdit);
        if (posButtonDiv.length) {// pos selected so calc all the rest
          //read selected values and uncertain values
          posVal = posButtonDiv.text();
          posID = posButtonDiv.prop('trmID');
          posCF = posButtonDiv.hasClass('uncertain')?2:1;
          value = posVal;
          //if noun then use gender if set else use mnf. for display
          if (posID == nounID) {
            subPosButtonDiv = $('.SubNounUI button.buttonDiv.selected',posEdit);
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
            genButtonDiv = $('.GramGenderUI button.buttonDiv.selected',posEdit);
            if (genButtonDiv.length) {// gender selected so calc all the rest
              //read selected values and uncertain values
              genVal = genButtonDiv.text();
              genID = genButtonDiv.prop('trmID');
              genCF = genButtonDiv.hasClass('uncertain')?2:1;
              value = genVal + (genCF==2?'(?)':'');
            }
          } else if (posID == pronID) {// if pronoun check for spos
            subPosButtonDiv = $('.SubPronUI button.buttonDiv.selected',posEdit);
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
          } else if (posID == numID) {// if numeral check for spos
            subPosButtonDiv = $('.SubNumeralUI button.buttonDiv.selected',posEdit);
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
          } else if (posID == adjID) {// if numeral check for spos
            subPosButtonDiv = $('.SubAdjectiveUI button.buttonDiv.selected',posEdit);
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
          }
          lemProps["pos"] = posID?posID:null;
          lemProps["spos"] = sposID?sposID:null;
          lemProps["gender"] = genID?genID:null;
          lemProps["certainty"] = [posCF,sposCF,genCF,classCF,declCF];
          lemmaVE.saveLemma(lemProps);
        }
        $('div.valueLabelDiv',lemmaVE.posUI).html(value);
        $('.posEditUI',lemmaVE.posUI).removeClass('dirty');
      }
      lemmaVE.posUI.removeClass("edit");
    });
    posEdit.append($('<input style="visibility:hidden;" />'));
    return posEdit;
  },


/**
* put your comment there...
*
*/

  createPOSUI: function() {
    var lemmaVE = this, value,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:null,
        cf = this.isLemma && this.entity.certainty ? this.entity.certainty:[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        spos = this.isLemma && this.entity.spos ? this.entity.spos:null;
    if (pos && this.dataMgr.getTermFromID(pos)) {
      value = this.dataMgr.getTermFromID(pos) + (cf[0]==2?'(?)':'');
      if (value == 'noun' && this.entity.gender && this.dataMgr.getTermFromID(this.entity.gender)) {
        value = this.dataMgr.getTermFromID(this.entity.gender) + (cf[2]==2?'(?)':'');
      }
    }
    DEBUG.traceEntry("createPOSUI");
    //create UI container
    this.posUI = $('<div class="posUI"></div>');
    this.editDiv.append(this.posUI);
    //create label
    this.posUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"POS")+'</div>'+
                          '</div>'));
    //create input with save button
    this.posUI.append(this.createPOSEditUI());
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.posUI).unbind("click").bind("click",function(e) {
        lemmaVE.posUI.addClass("edit");
        $('div.posEditUI input',this.posUI).focus();
      });
      //click to cancel
      $('div.posEditUI input',this.posUI).unbind("blur").bind("blur",function(e) {
        lemmaVE.posUI.removeClass("edit");
      });
      //mark dirty on input
      $('div.posEditUI',this.posUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.posUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
    DEBUG.traceExit("createPOSUI");
  },

/************* Gloss Interface ****************/

/**
* put your comment there...
*
*/

  createTransUI: function() {
    var lemmaVE = this,
        value = this.isLemma ? this.entity.trans:"";
    DEBUG.traceEntry("createTransUI");
    //create UI container
    this.transUI = $('<div class="transUI"></div>');
    this.editDiv.append(this.transUI);
    //create label
    this.transUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"Gloss")+'</div>'+
                          '</div>'));
    //create input with save button
    this.transUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+(value?value:"")+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.transUI).unbind("click").bind("click",function(e) {
        lemmaVE.transUI.addClass("edit");
        $('div.valueInputDiv input',lemmaVE.transUI).focus();
        //$('div.valueInputDiv input',lemmaVE.transUI).select();
      });
      //blur to cancel
      $('div.valueInputDiv input',this.transUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
          lemmaVE.transUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.transUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.transUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.transUI).unbind("click").bind("click",function(e) {
        var lemProp = {};
        if ($('.propEditUI',lemmaVE.transUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.transUI).val();
          lemProp["trans"] = val;
          $('div.valueLabelDiv',lemmaVE.transUI).html(val);
          $('.propEditUI',lemmaVE.transUI).removeClass('dirty');
          lemmaVE.saveLemma(lemProp);
        }
        lemmaVE.transUI.removeClass("edit");
      });
    DEBUG.traceExit("createTransUI");
  },


/**
* put your comment there...
*
* @param attestedGID
*/

  linkAttestedForm: function(attestedGID) {
    var lemmaVE = this;
    this.saveLemma(null, attestedGID, null, null, true);
  },


/**
* put your comment there...
*
* @param infID
* @param attestedGID
*/

  unlinkAttested: function(infID,attestedGID) {
    var lemmaVE = this;
    this.saveLemma(null, attestedGID, infID, null, false);
  },


/**
* put your comment there...
*
* @param infID
* @param attestedGID
* @param buttonDiv
*/

  initShowInflectionEditUI: function(infID,attestedGID,buttonDiv) {
    var lemmaVE = this;
    if (!this.inflectionEditUI.children().length) {
      this.inflectionEditUI.children().remove();
    }
    this.createInflectionEditUI(infID,attestedGID,buttonDiv);
  },


/**
* put your comment there...
*
* @param infID
* @param attestedGID
* @param btnDiv
*/

  createInflectionEditUI: function(infID, attestedGID, btnDiv) {
    var lemmaVE = this, showSub, nounID = 664, pronID = 667, verbID = 685,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:null,
        spos = this.isLemma && this.entity.spos ? this.entity.spos:null,
        vclass = this.isLemma && this.entity['class'] ? this.entity['class']:null,
        inflection, sverb = '686', gen, num, infcase, per, tense , mood, conj2nd, icf = [3,3,3,3,3,3,3,3],
        infEdit = this.inflectionEditUI, lemmaShowInf = [], posSelectedBtns,
        listSubVerb = [{label: "Finite",trmID:'686',showSub:"showSubVerb showNumber showPerson showTense showMood"},
                       {label: "Derivative",trmID:'687',showSub:"showSubVerb showV2ndConj"}],
        listGen = [{label: "m.",trmID:485},
                   {label: "n.",trmID:486},
                   {label: "f.",trmID:487}],
        listAdjGen = [{label: "m.",trmID:491},
                       {label: "mn.",trmID:494},
                       {label: "n.",trmID:492},
                       {label: "mfn.",trmID:1383},
                       {label: "nf.",trmID:1384},
                       {label: "f.",trmID:493},
                       {label: "mf.",trmID:495}],
        listNum = [{label: "sg.",trmID:499},
                   {label: "du.",trmID:500},
                   {label: "pl.",trmID:501}],
        listCase = [//{label: "dir.",trmID:361},
                   {label: "nom.",trmID:362},
                   {label: "acc.",trmID:363},
                   {label: "instr.",trmID:364},
                   {label: "dat.",trmID:365},
                   {label: "dat/gen.",trmID:366},
                   {label: "abl.",trmID:367},
                   {label: "gen.",trmID:368},
                   {label: "loc.",trmID:369},
                   {label: "voc.",trmID:370}/*,
                   {label: "stem.",trmID:1069}*/],
        listPerson = [{label: "1st",trmID:850},
                     {label: "2nd",trmID:851},
                     {label: "3rd",trmID:852}],
        listTense = [{label: "pres.",trmID:865},
                     {label: "fut.",trmID:868},
                     {label: "perf.",trmID:867},
                     {label: "pret.",trmID:872}],
        listMood = [{label:  "ind.",trmID:846},
                     {label: "opt.",trmID:845},
                     {label: "impv.",trmID:844}],
        listV2ndConj = [{label: "abs.",trmID:860},
                       {label:  "inf.",trmID:861}],
        list2ndConj = [{label: "desid.",trmID:857},
                       {label: "intens.",trmID:859}],
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Clear",type:"clearBtnDiv"}];
    //find the show inflection UI indicators
    posSelectedBtns = $('.posEditUI button.buttonDiv.selected',lemmaVE.posUI);
    if (posSelectedBtns.length == 0) {
      alert("Error - trying to set inflection without lemma POS selected.");
      return;
    }
    posSelectedBtns.each(function(index,elem){
      var i,showInf = $(elem).prop('showInfl');
      if (showInf) {
        //handle multiple space separation
        showInf = showInf.replace(/\s+/g,',').split(',');
        for (i=0; i<showInf.length; i++) {
          if (lemmaShowInf.indexOf(showInf[i]) == -1) {
            lemmaShowInf.push(showInf[i]);
          }
        }
      }
    });
    if (infID) {
      inflection = this.dataMgr.getEntity('inf',infID);
      icf =  inflection.certainty;
      gen = inflection.gender;
      num = inflection.num;
      infcase = inflection['case'];
      per = inflection.person;
      tense = inflection.tense;
//      voice = inflection.voice;
      mood = inflection.mood;
      conj2nd = inflection.conj2nd;
      if (conj2nd) {
        sverb = '687';
      }
    }
    infEdit.html('');
            //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
    if (lemmaShowInf.indexOf('showSubVerb') > -1) {
      infEdit.append(this.createRadioGroupUI("spos", "SubVerbUI", listSubVerb,sverb,null,null,1));
      infEdit.append(this.createRadioGroupUI("tense", "TenseUI", listTense,tense,null,icf[0] == 2));
//      infEdit.append(this.createRadioGroupUI("voice", "VoiceUI", listVoice,voice,null,icf[1] == 2));
      infEdit.append(this.createRadioGroupUI("mood", "MoodUI", listMood,mood,null,icf[2] == 2));
      infEdit.append(this.createRadioGroupUI("num", "NumberUI", listNum,num,null,icf[4] == 2));
      infEdit.append(this.createRadioGroupUI("person", "PersonUI", listPerson,per,null,icf[6] == 2));
      infEdit.append(this.createRadioGroupUI("conj2nd", "V2ndConjUI", listV2ndConj,null,conj2nd,icf[7] == 2));
    } else {// other inflectables use a subset of the following
      if (lemmaShowInf.indexOf('showGender') > -1) {
        infEdit.append(this.createRadioGroupUI("gender", "GenderUI", listGen, gen,null,icf[3] == 2));
      }
      if (lemmaShowInf.indexOf('showAdjGender') > -1) {
        infEdit.append(this.createRadioGroupUI("gender", "AGenderUI", listAdjGen, gen,null,icf[3] == 2));
      }
      if (lemmaShowInf.indexOf('showNumber') > -1) {
        infEdit.append(this.createRadioGroupUI("num", "NumberUI", listNum,num,null,icf[4] == 2));
      }
      if (lemmaShowInf.indexOf('showCase') > -1) {
        infEdit.append(this.createRadioGroupUI("case", "CaseUI", listCase,infcase,null,icf[5] == 2));
      }
      if (lemmaShowInf.indexOf('showAdjConj') > -1) {
        infEdit.append(this.createRadioGroupUI("conj2nd", "A2ndConjUI", list2ndConj,conj2nd,null,icf[7] == 2));
      }
    }
    infEdit.append(this.createRadioGroupUI(null, null, listCtrl));
    infEdit.addClass(lemmaShowInf.join(" "));
    if (pos == verbID) {
      showSub = $('.SubVerbUI button.buttonDiv.selected',infEdit).prop('showSub');
      if (showSub.length) {// ensure sub groups are shown
        infEdit.addClass(showSub);
      }
    }
    //click to clear
    $('.clearBtnDiv',infEdit).unbind("click").bind("click",function(e) {
      var ctxDiv = infEdit;
      //remove selected and uncertainty from all groups buttons
      ctxDiv.find('.selected').removeClass('selected');
      ctxDiv.find('.uncertain').removeClass('uncertain');
      //check each radioGroup against it's original values and update dirty flag for group
      $('.radioGroupUI',ctxDiv).each(function(index, elem) {
        var radioGroup = $(elem);
        if (radioGroup.prop('origCF') ||  radioGroup.prop('origID')) { // is dirty
          if (!radioGroup.hasClass("dirty")) {
            radioGroup.addClass("dirty");
          }
        } else { // not dirty so remove flags
          if (radioGroup.hasClass("dirty")) {
            radioGroup.removeClass("dirty");
          }
        }
      });
      //check dirty flag of all groups and update UI dirty flag for save button.
      if ( ctxDiv.find('.dirty').length) { //properties have changed
        if (!ctxDiv.hasClass("dirty")) {
          ctxDiv.addClass("dirty");
        }
      } else {
        if (ctxDiv.hasClass("dirty")) {
          ctxDiv.removeClass("dirty");
        }
      }
    });
    //save inflection data
    $('.saveBtnDiv',infEdit).unbind("click").bind("click",function(e) {
      var infProps = {}, value = '', pname,
          certainty = {'tense':3,'voice':3,'mood':3,'gender':3,'num':3,'case':3,'person':3,'conj2nd':3},
          infPropNames = Object.keys(certainty);
      if (infEdit.hasClass('dirty')) {
        //for each radio group find name and value of selected
        $('.buttonDiv.selected',infEdit).each(function(index,elem) {
            var propname = $(elem).parent().prop('grpName'),
                propval = $(elem).prop('trmID');
            if (infPropNames.indexOf(propname) > -1) {
              infProps[propname] = propval;
              certainty[propname] = $(elem).hasClass('uncertain')?2:1;
              value += (value != ''?' ':'') + $(elem).text();
            }
        });
        infProps['certainty'] = [];
        for (pname in certainty) {
          infProps['certainty'].push(certainty[pname]);
        }
        lemmaVE.saveLemma(null,attestedGID,infID,infProps);
        if (btnDiv) {
          btnDiv.html(value?value:"inflection");
        }
        infEdit.removeClass('dirty');
      }
      lemmaVE.attestedUI.removeClass("edit");
    });
  },

/************* Attested Form Interface ****************/

/**
* put your comment there...
*
*/

  createAttestedUI: function() {
    var lemmaVE = this,i,j, prefix, id, infID, infEntIDs, attestedEntry, nounID = 664, adjID = 674, pronID = 667, verbID = 685,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:"",
        gen = this.isLemma && this.entity.gender ? this.entity.gender:null,
        inflectable = (pos && (pos == this.dataMgr.termInfo.idByTerm_ParentLabel['v.-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['adj.-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['noun-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['pron.-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['num.-partofspeech']))? true:false,//term dependency
        entities = [], infMap = {},entity, displayUI, infVal, inflection,
        attested, entIDs = this.isLemma ? this.entity.entityIDs:"";
    DEBUG.traceEntry("createAttestedUI");
    //create UI container
    this.attestedUI = $('<div class="attestedUI"></div>');
    this.editDiv.append(this.attestedUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="attestedUIHeader"><span>Attestations:</span>'+
                       '<span class="addButton"><u>'+(this.wordlistVE.attestedLinkMode?'Cancel link mode':'Add new')+'</u></span></div>'));
    //create a list of tokens and map them to inflections
    if (entIDs && entIDs.length) {
      for (i in entIDs) {
        prefix = entIDs[i].substring(0,3);
        id = entIDs[i].substring(4);
        if ( prefix == "inf") { //inflection so get entities
          infID = id;
          entity = this.dataMgr.getEntity(prefix,id);
          infEntIDs = entity.entityIDs;
          for (j in infEntIDs) {
            prefix = infEntIDs[j].substring(0,3);
            id = infEntIDs[j].substring(4);
            entity = this.dataMgr.getEntity(prefix,id);
            entity.tag = infEntIDs[j].replace(':','');
            entity.gid = infEntIDs[j];
            entities.push(entity);
            infMap[entity.tag] = infID;
          }
        } else {// add to set with no inflection mapping
          entity = this.dataMgr.getEntity(prefix,id);
          entity.tag = entIDs[i].replace(':','');
          entity.gid = entIDs[i];
          entities.push(entity);
          infMap[entity.tag] = 0;
        }
      }
      entities.sort(UTILITY.compareEntities);
      for (i=0; i<entities.length; i++) {
        attested = entities[i];
        infVal = "";
        if (inflectable) {// setup inflection
          infID = infMap[attested.tag];
          if (infID) { //calc inflection string
            inflection = this.dataMgr.getEntity('inf',infID);
            //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
            if (inflection.tense && this.dataMgr.getTermFromID(inflection.tense)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.tense);
              if (inflection.certainty && inflection.certainty[0] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.voice && this.dataMgr.getTermFromID(inflection.voice)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.voice);
              if (inflection.certainty && inflection.certainty[1] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.mood && this.dataMgr.getTermFromID(inflection.mood)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.mood);
              if (inflection.certainty && inflection.certainty[2] == 2) {
                infVal += '(?)';
              }
            }
            if ( pos != verbID && inflection.gender && this.dataMgr.getTermFromID(inflection.gender)) {
              //TODO add code to check multi gender noun, only show inf gender of noun if subset of lemma gender
              infVal +=  this.dataMgr.getTermFromID(inflection.gender);
              if (inflection.certainty && inflection.certainty[3] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.num && this.dataMgr.getTermFromID(inflection.num)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.num);
              if (inflection.certainty && inflection.certainty[4] == 2) {
                infVal += '(?)';
              }
            }
            if (pos != verbID && inflection["case"] && this.dataMgr.getTermFromID(inflection["case"])) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection["case"]);
              if (inflection.certainty && inflection.certainty[5] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.person && this.dataMgr.getTermFromID(inflection.person)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.person);
              if (inflection.certainty && inflection.certainty[6] == 2) {
                infVal += '(?)';
              }
            }
            if ((pos == verbID || pos == adjID) && inflection.conj2nd && this.dataMgr.getTermFromID(inflection.conj2nd)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.conj2nd);
              if (inflection.certainty && inflection.certainty[7] == 2) {
                infVal += '(?)';
              }
            }
            infVal = (infVal?infVal:"inflection");
          } else { //use placeholder
            infVal = "inflection";
          }
        }
        attestedEntry = $('<div class="attestedentry">' +
                            '<span class="attestedformloc '+attested.tag+'">' + (attested.locLabel?attested.locLabel:"$nbsp;") + '</span>'+
                            '<span class="attestedform '+attested.tag+'">' + attested.transcr.replace(/ʔ/g,'') + '</span>'+
                            (infVal?'<span class="inflection '+attested.tag+'">' + infVal + '</span>':'') +
                            '<span class="unlink '+attested.tag+'"><u>unlink</u></span>'+
                          '</div>');
        $('.inflection',attestedEntry).prop('gid',attested.gid);
        $('.unlink',attestedEntry).prop('gid',attested.gid);//attach entGID of attested to be unlinked
        if (infID) {//attach link to inflection if inflected entity
          $('.inflection',attestedEntry).prop('infID',infID);
          $('.unlink',attestedEntry).prop('infID',infID);
        }
        displayUI.append(attestedEntry);
      }
    }
    this.attestedUI.append(displayUI);
    $('span.attestedform',this.attestedUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'), entTag;
      entTag = classes.match(/(?:tok|cmp)\d+/)[0];
      //trigger selection change
      $('.editContainer').trigger('updateselection',[lemmaVE.id,[entTag],entTag]);
      e.stopImmediatePropagation();
      return false;
    });
    $('span.inflection',this.attestedUI).unbind("click").bind("click",function(e) {
      lemmaVE.attestedUI.addClass("edit");
      lemmaVE.initShowInflectionEditUI( $(this).prop('infID'), $(this).prop('gid'),$(this));
    });
    $('span.unlink',this.attestedUI).unbind("click").bind("click",function(e) {
      lemmaVE.unlinkAttested( $(this).prop('infID'), $(this).prop('gid'));
    });
    //create inflectionUI container
    this.inflectionEditUI = $('<div class="infEditUI"/>');
    this.attestedUI.append(this.inflectionEditUI);
    //attach event handlers
    $('span.addButton',this.attestedUI).unbind("click").bind("click",function(e) {
      if ($(this).text() == 'Add new') {//switch to linking mode
        $(this).html('<u>Cancel link mode</u>');
        lemmaVE.wordlistVE.setLinkMode(true);
      } else {//cancel linking mode
        $(this).html('<u>Add new</u>');
        lemmaVE.wordlistVE.setLinkMode(false);
      }
    });
    DEBUG.traceExit("createAttestedUI");
  },

/************* Tagging Interface ****************/

/**
* put your comment there...
*
*/

  createTaggingUI: function() {
    var lemmaVE = this,i,j, anoTag, tag, annoID, annoIDs, displayUI, annotation,
        customTagID = this.dataMgr.termInfo.idByTerm_ParentLabel['customtype-tagtype'],//term dependency
        annoTagIDsByType = (this.entity && this.entity.linkedByAnoIDsByType)? this.entity.linkedByAnoIDsByType:[];
    DEBUG.traceEntry("createTaggingUI");
    //create UI container
    this.tagUI = $('<div class="tagUI"></div>');
    this.editDiv.append(this.tagUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create a list of Tags
    if (Object.keys(annoTagIDsByType).length) {
      //create Header
      displayUI.append($('<div class="tagUIHeader"><span>Tags:</span><span class="addButton"><u>Edit tags</u></span></div>'));
      for (i in annoTagIDsByType) {
        if (this.dataMgr.entTagToLabel['trm'+i]) {
          annoIDs = annoTagIDsByType[i];
          tag = this.dataMgr.entTagToLabel['trm'+i];
          for (j in annoIDs) {
            annoID =  annoIDs[j];
            annotation = this.dataMgr.getEntity("ano",annoID);
            if (annotation && tag == "CustomType") {
              text = annotation.text;
            } else {
              text = tag;
            }
            anoTag = "ano" + annoID;
            //TODO add code to display the different types linked vs URL vs text
            displayUI.append(this.createTagEntry(text,anoTag));
          }
        }
      }
    } else {
      //create Header
      displayUI.append($('<div class="tagUIHeader"><span>Tags:</span><span class="addButton"><u>Add tags</u></span></div>'));
    }
    //add or edit tags for this entity
    this.tagUI.append(displayUI);
    $('span.addButton',this.tagUI).unbind("click").bind("click",function(e) {
      if (lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
        lemmaVE.propMgr.showVE("tagVE", lemmaVE.tag);
      }
    });
    //remove tag
    $('span.removetag',this.tagUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          anoTag = classes.match(/ano\d+/)[0];
      entPropVE.removeTag(anoTag);
    });
    //create input with save button
    DEBUG.traceExit("createTaggingUI");
  },


/**
* put your comment there...
*
* @param tag
* @param anoTag
*/

  createTagEntry: function(tag,anoTag) {
    var lemmaVE = this;
    DEBUG.trace("createTagEntry");
    //create Annotation Entry
    return($('<div class="tagentry '+anoTag+'">' +
         '<span class="tag">' + tag + '</span>'+
         '<span class="removetag '+anoTag+'" title="remove tag '+tag+'">X</span>'+
         '</div>'));
    //create input with save button
  },


/**
* put your comment there...
*
* @param anoTag
*/

  removeTag: function(anoTag) {
    var lemmaVE = this, savedata = {};
    DEBUG.traceEntry("removeTag");
    savedata["entGID"] = this.prefix+":"+this.entID;
    savedata["tagRemoveFromGIDs"] = [anoTag];
    if (DEBUG.healthLogOn) {
      savedata['hlthLog'] = 1;
    }
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveTags.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              lemmaVE.dataMgr.updateLocalCache(data,null);
              lemmaVE.propMgr.showVE();
            }
            if (data.editionHealth) {
              DEBUG.log("health","***Remove Tag***");
              DEBUG.log("health","Params: "+JSON.stringify(savedata));
              DEBUG.log("health",data.editionHealth);
            }
            if (data.errors) {
              alert("An error occurred while trying to retrieve a record. Error: " + data.errors.join());
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve a record. Error: " + error);
        }
    });
    DEBUG.traceExit("removeTag");
  },

/************* Annotation Interface ****************/

/**
* put your comment there...
*
*/

  createAnnotationUI: function() {
    var lemmaVE = this,i,j, prefix, id, infID, infEntIDs,
        displayUI,annotation,
        annoIDsByType = (this.entity && this.entity.linkedAnoIDsByType)? this.entity.linkedAnoIDsByType:[];
    DEBUG.traceEntry("createAnnotationUI");
    //create UI container
    this.annoUI = $('<div class="annoUI"></div>');
    this.editDiv.append(this.annoUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="annoUIHeader"><span>Annotations:</span><span class="addButton"><u>Add new</u></span></div>'));
    //create a list of Annotations
    if (Object.keys(annoIDsByType).length) {
      for (i in annoIDsByType) {
        annoType = this.dataMgr.getTermFromID(i);
        annoIDs = annoIDsByType[i];
        for (j in annoIDs) {
          annoID =  annoIDs[j];
          annotation = this.dataMgr.getEntity("ano",annoID);
          if (annotation) {
            annotation.tag = "ano" + annoID;
            //TODO add code to display the different types linked vs URL vs text
            displayUI.append(this.createAnnotationEntry(annotation));
          }
        }
      }
    }
    this.annoUI.append(displayUI);
    $('div.annotationentry',this.annoUI).unbind("dblclick").bind("dblclick",function(e) {
      var classes = $(this).attr("class"),anoTag,annotation;
      if (classes.match(/ano\d+/)) {
        anoTag = classes.match(/ano\d+/)[0];
      }
      annotation = lemmaVE.dataMgr.getEntityFromGID(anoTag);
      if (annotation && !annotation.readonly && lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
        lemmaVE.propMgr.showVE("annoVE",anoTag);
      } else {
        UTILITY.beep();
      }
    });
    $('span.addButton',this.annoUI).unbind("click").bind("click",function(e) {
      if (lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
        lemmaVE.propMgr.showVE("annoVE");
      }
    });
    //remove anno
    $('span.removeanno',this.annoUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          anoTag = classes.match(/ano\d+/)[0];
      lemmaVE.removeAnno(anoTag);
    });
    //create input with save button
    DEBUG.traceExit("createAnnotationUI");
  },


/**
* put your comment there...
*
* @param anno
*/

  createAnnotationEntry: function(anno) {
    var lemmaVE = this,
        annoType = this.dataMgr.getTermFromID(anno.typeID);
    DEBUG.trace("createAnnotationEntry");
    //create Annotation Entry
    return($('<div class="annotationentry '+annoType+' '+anno.tag+'">' +
         '<span class="annotation">' + anno.text + '</span>'+
         '<span class="modstamp">' + anno.modStamp + '</span>'+
         (anno.readonly?'':'<span class="removeanno '+anno.tag+'" title="remove tag '+anno.tag+'">X</span>')+
         '</div>'));
  },


/**
* put your comment there...
*
* @param anoTag
*/

  removeAnno: function(anoTag) {
    var lemmaVE = this, savedata = {};
    DEBUG.traceEntry("removeAnno");
    savedata["cmd"] = "removeAno";
    savedata["linkFromGID"] = this.prefix+":"+this.entID;
    savedata["anoTag"] = anoTag;
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveAnno.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              lemmaVE.dataMgr.updateLocalCache(data,null);
              lemmaVE.propMgr.showVE();
            }
            if (data.errors) {
              alert("An error occurred while trying to remove annotation record. Error: " + data.errors.join());
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve a record. Error: " + error);
        }
    });
    DEBUG.traceExit("removeAnno");
  },


/**
* put your comment there...
*
*/

  createLinkTypeUI: function() {//link type ui for lemma
    var lemmaVE = this,
        value, linkTypeID = (this.linkTypeID),
        valueEditable = (this.prefix == "lem" && this.entity && !this.entity.readonly),
        seeAlsoLinkTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["seealso-linkagetype"],
        treeLinkTypeName = this.id+'linktypetree';
    DEBUG.traceEntry("createLinkTypeUI");
    value = this.dataMgr.getTermFromID(linkTypeID);
    //create UI container
    this.linkTypeUI = $('<div class="linkTypeUI"></div>');
    this.editDiv.append(this.linkTypeUI);
    //create label with Add new button
    this.linkTypeUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement'+(valueEditable?"":" readonly")+'">Current Linktype '+value+'</div>'+
                          ((this.entity.readonly)?'':('<span class="addButton"><u>Add New '+value+' link</u></span></div>'))+
                          '</div>'));
    //create input with selection tree
    this.linkTypeUI.append($('<div class="propEditUI">'+
                    '<div class="propEditElement"><div id="'+ treeLinkTypeName+'"></div></div>'+
                    '</div>'));
    this.linkTypeTree = $('#'+treeLinkTypeName,this.linkTypeUI);
    this.linkTypeTree.jqxTree({
           source: this.dataMgr.linkTypes,
           hasThreeStates: false, checkboxes: false,
           theme:'energyblue'
    });
    $('.propEditElement',this.linkTypeUI).addClass('linkTypeUI');
    //attach event handlers
      if (valueEditable) {
      //click to edit
        $('div.valueLabelDiv',this.linkTypeUI).unbind("click").bind("click",function(e) {
          lemmaVE.linkTypeUI.addClass("edit");
          //init tree to current type
          var curItem = $('#trm'+(lemmaVE.linkTypeID?lemmaVE.linkTypeID:seeAlsoLinkTypeID), lemmaVE.linkTypeTree),
              offset = 0;
          if (curItem && curItem.length) {
            curItem = curItem.get(0);
            lemmaVE.suppressSubSelect = true;
            lemmaVE.linkTypeTree.jqxTree('selectItem',curItem);
            //expand selected item sub tree if needed
            curItem = lemmaVE.linkTypeTree.jqxTree('getSelectedItem');
            while (curItem && curItem.parentElement) {
              lemmaVE.linkTypeTree.jqxTree('expandItem',curItem.parentElement);
              curItem = lemmaVE.linkTypeTree.jqxTree('getItem',curItem.parentElement);
            }
            curItem = lemmaVE.linkTypeTree.jqxTree('getItem',$('li:first',lemmaVE.linkTypeTree).get(0));
            while (curItem && !curItem.selected) {
              offset += 25;
              if (curItem.isExpanded) {
                offset += 2;
              }
              curItem = lemmaVE.linkTypeTree.jqxTree('getNextItem',curItem.element);
            }
            delete lemmaVE.suppressSubSelect;
          }
          setTimeout(function(){
              $('.linkTypeUI',lemmaVE.linkTypeUI).scrollTop(offset);
            },50);
        });
        //blur to cancel
        this.linkTypeTree.unbind("blur").bind("blur",function(e) {
          $('div.valueLabelDiv',lemmaVE.linkTypeUI).html("Current Linktype "+lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID));
          $('span.addButton',lemmaVE.linkTypeUI).html("<u>Add New "+lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID)+" link</u>");
          lemmaVE.linkTypeUI.removeClass("edit");
        });
        //change sequence type
        this.linkTypeTree.on('select', function (event) {
            if (lemmaVE.suppressSubSelect) {
              return;
            }
            var args = event.args, dropDownContent = '',
                item =  lemmaVE.linkTypeTree.jqxTree('getItem', args.element);
            if (item.value && item.value != lemmaVE.linkTypeID) {//user selected to change sequence type
              //save new subtype to entity
              lemmaVE.linkTypeID = item.value;
              $('div.valueLabelDiv',lemmaVE.linkTypeUI).html("Current Linktype "+lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID));
              $('span.addButton',lemmaVE.linkTypeUI).html("<u>Add New "+lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID)+" link</u>");
            }
            lemmaVE.linkTypeUI.removeClass("edit");
        });
        //attach event handlers
        $('span.addButton',lemmaVE.linkTypeUI).unbind("click").bind("click",function(e) {
            var linkTypeID = lemmaVE.linkTypeID?lemmaVE.linkTypeID:seeAlsoLinkTypeID;
            if (lemmaVE.wordlistVE && lemmaVE.wordlistVE.setLinkRelatedMode) {
              lemmaVE.wordlistVE.setLinkRelatedMode(true);
            }
        });
      }
    DEBUG.traceExit("createLinkTypeUI");
  },

/************* Related Interface ****************/

/**
* put your comment there...
*
*/

  createRelatedUI: function() {
    var lemmaVE = this,i,j, prefix, id, entGID, relEntIDs,
        displayUI,entity, relEntry,
        relEntGIDsByType = (this.entity && this.entity.relatedEntGIDsByType)? this.entity.relatedEntGIDsByType:[];
    DEBUG.traceEntry("createRelatedUI");
    //create UI container
    this.relUI = $('<div class="relUI"></div>');
    this.editDiv.append(this.relUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="relUIHeader"><span>Related:</span></div>'));
    //create a list of Related entities by relationship
    if (Object.keys(relEntGIDsByType).length) {
      for (i in relEntGIDsByType) {
        linkType = this.dataMgr.getTermFromID(i);
        displayUI.append($('<div class="relUISection"><span>'+linkType+' for :'+'</span></div>'));
        relEntIDs = relEntGIDsByType[i];
        for (j in relEntIDs) {
          entGID =  relEntIDs[j];
          entity = this.dataMgr.getEntityFromGID(entGID);
          if (entity) {
            entity.tag = entGID.replace(':','');
            //TODO add code to display the different types linked vs URL vs text
            relEntry = this.createRelatedEntry(entity);
            relEntry.prop('tag',entity.tag);
            $('span.unlink',relEntry).prop('entGID',entGID);
            $('span.unlink',relEntry).prop('typeID',i);
            displayUI.append(relEntry);
          }
        }
      }
    }
    this.relUI.append(displayUI);
    $('div.relationentry',this.relUI).unbind("dblclick").bind("dblclick",function(e) {
      var entTag = $(this).prop('tag'),entity;
      entity = lemmaVE.dataMgr.getEntityFromGID(entTag);
      if (entity) {
        lemmaVE.showLemma("lem",entity.id);
      } else {
        UTILITY.beep();
      }
    });
    $('span.unlink',this.relUI).unbind("click").bind("click",function(e) {
      lemmaVE.unlinkRelated( $(this).prop('typeID'), $(this).prop('entGID'));
    });
    DEBUG.traceExit("createRelatedUI");
  },

  /**
  * put your comment there...
  *
  * @param entity
  */

  createRelatedEntry: function(entity) {
    var lemmaVE = this;
    DEBUG.trace("createRelatedEntry");
    //create Related Entry
    return($('<div class="relationentry '+entity.tag+'">' +
         '<span class="related">' + entity.value + '</span>'+
         '<span class="unlink '+entity.tag+'"><u>unlink</u></span>'+
         '</div>'));
  },


/**
* put your comment there...
*
* @param lemProps
* @param tokGID
* @param infID
* @param infProps
* @param isLink
*/

  saveLemma: function(lemProps, tokGID, infID, infProps, isLink) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("saveLemma");
    if (this.prefix != "lem") {//create lem cases
      savedata["cmd"] = "createLem";
      savedata["tokGID"] = this.prefix+":"+this.entID;
      savedata["lemProps"] = lemProps;
      if (this.catID) {
        savedata["catID"] = this.catID;
      } else if (this.ednID) {
        savedata["ednID"] = this.ednID;
      }
    } else if(!tokGID) {// update lem case
      savedata["cmd"] = "updateLem";
      savedata["lemID"] = this.entID;
      savedata["lemProps"] = lemProps;
    } else if (!infProps) {//lem with tokGID and no inflection properties so link or unlink
      savedata["cmd"] = (isLink?"linkTok":"unlinkTok");
      savedata["lemID"] = this.entID;
      savedata["tokGID"] = tokGID;
      if (infID) {
        savedata["infID"] = infID;
      }
    } else {//inflection
      savedata["cmd"] = "inflectTok";
      savedata["lemID"] = this.entID;
      savedata["tokGID"] = tokGID;
      if (infID) {
        savedata["infID"] = infID;
      }
      savedata["infProps"] = infProps;
    }
    if (DEBUG.healthLogOn) {
      savedata['hlthLog'] = 1;
    }
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveLemma.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            var newLemID, newCatID, oldLemID = savedata["lemID"],
                tokGID =savedata["tokGID"], cmd = savedata["cmd"];
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              lemmaVE.dataMgr.updateLocalCache(data,null);
              switch (cmd) {
                case "createLem":
                  if (data.entities.insert) {
                    if (data.entities.insert.lem) {
                      //find new lemma id
                      for (entID in data.entities.insert.lem) {
                        newLemID = entID;
                        break;
                      }
                    }
                    if (data.entities.insert.cat) {
                      //find new cat id
                      for (entID in data.entities.insert.cat) {
                        newCatID = entID;
                        lemmaVE.catID = newCatID;
                        break;
                      }
                    }
                    if (lemmaVE.wordlistVE) {
                      //REPLACE WITH insertLemmaEntry and give tokGID as hint (we don't know if user changed spelling)
                      //lemmaVE.wordlistVE.refreshDisplay((newCatID?newCatID:null), newLemID);
                      lemmaVE.wordlistVE.insertLemmaEntry(newLemID,tokGID);
                      lemmaVE.wordlistVE.removeWordEntry(tokGID);
                    }
                    lemmaVE.showLemma('lem',newLemID);
                  }
                  break;
                case "updateLem":
                  //REPLACE WITH updateLemmaEntry
                  //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
                  lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                  lemmaVE.showLemma('lem',oldLemID);
                  break;
                case "linkTok":
                  //REPLACE WITH updateLemmaEntry
                  //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
                  lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                  lemmaVE.showLemma('lem',oldLemID);
                  lemmaVE.wordlistVE.removeWordEntry(tokGID);
                  break;
                case "unlinkTok":
                  //REPLACE WITH updateLemmaEntry
                  //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
                  lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                  lemmaVE.showLemma('lem',oldLemID);
                  lemmaVE.wordlistVE.insertWordEntry(tokGID,'lem'+oldLemID);
                  break;
                case "inflectTok":
                  //REPLACE WITH updateLemmaEntry
                  //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
                  lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                  lemmaVE.showLemma('lem',oldLemID);
                  break;
              }
            }
            if (data.editionHealth) {
              DEBUG.log("health","***Save Lemma cmd="+cmd+"***");
              DEBUG.log("health","Params: "+JSON.stringify(savedata));
              DEBUG.log("health",data.editionHealth);
            }
            if (data.errors) {
              alert("An error occurred while trying to retrieve a record. Error: " + data.errors.join());
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve a record. Error: " + error);
        }
    });
    DEBUG.traceExit("saveLemma");
  },


/**
* put your comment there...
*
* @param toGID
*/

  linkRelatedEntity: function(toGID) {
    var fromGID = 'lem:' + this.entID,
        linkTypeID = this.linkTypeID;
    this.linkRelated(fromGID, linkTypeID, toGID);
  },


/**
* put your comment there...
*
* @param fromGID
* @param linkTypeID
* @param toGID
*/

  linkRelated: function(fromGID, linkTypeID, toGID) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("linkRelated");
    if (linkTypeID && fromGID && toGID) {//requires all params
      savedata['fromGID'] = fromGID;
      savedata['linkTypeID'] = linkTypeID;
      savedata['toGID'] = toGID;
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax async save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveLink.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var oldLemID = lemmaVE.entID;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                lemmaVE.showLemma();
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Unlink Related***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to remove link. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to remove link. Error: " + error);
          }
      });
    } else {
      DEBUG.log("gen","invalid call to linkRelated (" + fromGID + " - " + linkTypeID + " - " + toGID + ")");
    }
    DEBUG.traceExit("linkRelated");
  },


/**
* put your comment there...
*
* @param linkTypeID
* @param string entGID Entity global id (prefix:id)
*/

  unlinkRelated: function(linkTypeID, entGID) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("unlinkRelated");
    if (linkTypeID && entGID) {//requires both params
      savedata["cmd"] = "removeLink";
      savedata['lemID'] = this.entID;
      savedata['linkTypeID'] = linkTypeID;
      savedata['entGID'] = entGID;
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax async save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveLemma.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var oldLemID = lemmaVE.entID, newCatID;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                lemmaVE.showLemma();
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Unlink Related***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to remove link. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to remove link. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("unlinkRelated");
  },


/**
* put your comment there...
*
*/

  deleteLemma: function() {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("deleteLemma");
    if (this.prefix == 'lem' && this.entID) {//requires lemID
      savedata['lemID'] = this.entID;
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax async save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/deleteLemma.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var oldLemID;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                oldLemID = lemmaVE.entID;
                if (lemmaVE.wordlistVE){
                  if (!lemmaVE.wordlistVE.nextWord()) {
                    lemmaVE.wordlistVE.prevWord();
                  }
                  lemmaVE.wordlistVE.removeWordlistEntry('lem'+oldLemID);
                  lemmaVE.entID = null;
                  lemmaVE.showLemma();
                }
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Delete Lemma***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to remove link. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to remove link. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("deleteLemma");
  },


/**
* put your comment there...
*
* @param compAnalysis
* @param rootkey
*/

  saveCompoundAnalysis: function(compAnalysis, rootkey) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("saveCompoundAnalysis");
    if (compAnalysis && Object.keys(compAnalysis).length>2 || compAnalysis == "") {//minimally valid compound analysis
      savedata["compAnalysis"] = compAnalysis;
      if (rootkey && compAnalysis && compAnalysis[rootkey] && compAnalysis[rootkey].lemID) {
        savedata['lemID'] = compAnalysis[rootkey].lemID;
        savedata['rootkey'] = rootkey;
      } else {
        savedata['lemID'] = this.entID;
      }
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveCompoundAnalysis.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var newLemID, newCatID,updLemID;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                if (compAnalysis) {
                  //TODO find out new lemmas and insert them
                  //change lemma to Update
                  if (data.entities && data.entities.insert && data.entities.insert.lem &&
                      Object.keys(data.entities.insert.lem).length) {
                    for (newLemID in data.entities.insert.lem) {
                      lemmaVE.wordlistVE.insertLemmaEntry(newLemID);
                    }
                  }
                  if (data.entities && data.entities.update && data.entities.update.lem &&
                      Object.keys(data.entities.update.lem).length) {
                    for (updLemID in data.entities.update.lem) {
                      lemmaVE.wordlistVE.updateLemmaEntry(updLemID);
                    }
                  }
                }
                lemmaVE.showLemma();
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Save Compound Analysis***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to save Compound Analysis. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to retrieve a record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveCompoundAnalysis");
  }

}

