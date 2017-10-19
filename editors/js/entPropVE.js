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
* editors entPropVE object
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
* Constructor for Generic Entity Property Viewer/Editor Object
*
* @type Object
*
* @param entPropVECfg is a JSON object with the following possible properties
*  "id" the id of the open pane used to create context ids for elements
*  "entGID" the global ID of the entity for viewing/editing
*  "editor" reference to the context editor/"controlling" editor
*  "ednID" the edition ID of the context
*  "dataMgr" reference to the data manager/model manager
*  "propMgr" reference to the property manager that switches property editors
*  "contentDiv" reference to the div element for display of the EntityPropVE
*
* @returns {EntityPropVE}
*/

EDITORS.EntityPropVE = function(entPropVECfg) {
  this.config = entPropVECfg;
  this.id = entPropVECfg['id'] ? "propVE"+entPropVECfg['id']:null;
  this.entGID = entPropVECfg['entGID'] ? entPropVECfg['entGID']:null;
  this.controlVE = entPropVECfg['editor'] ? entPropVECfg['editor']:null;
  this.ednID = entPropVECfg['ednID'] ? entPropVECfg['ednID']:null;
  this.dataMgr = entPropVECfg['dataMgr'] ? entPropVECfg['dataMgr']:null;
  this.propMgr = entPropVECfg['propMgr'] ? entPropVECfg['propMgr']:null;
  this.contentDiv = entPropVECfg['contentDiv'] ? $(entPropVECfg['contentDiv']):null;
  this.contentDiv.addClass("autoScrollY");
  if (this.entGID) {
    this.tag = this.entGID.replace(":","");
    this.prefix = this.entGID.substring(0,3);
    this.entID = this.entGID.substring(4);
  } else if (this.ednID) {
    this.tag = "edn"+this.ednID;
    this.prefix = "edn";
    this.entID = this.ednID;
    this.entity = this.dataMgr.getEntity(this.prefix,this.entID);
  }
  if (this.controlVE) { // install API link
    this.controlVE.entPropVE = this;
  }
  if (this.ednID){
    this.txtID = this.dataMgr.entities.edn[this.ednID].txtID;
  }
  this.init();
  return this;
};

/**
* Prototype for Generic Entity Property Viewer/Editor Object
*/
EDITORS.EntityPropVE.prototype = {

/**
* Initialiser for Entity Property editor
*/

  init: function() {
    DEBUG.traceEntry("init","init entity property viewer");
   if (this.prefix && this.entID && this.dataMgr.getEntity(this.prefix,this.entID)) {
      this.showEntity();
    } else {
      this.contentDiv.html('Entity Property Viewer');
    }
    DEBUG.traceExit("init","init entity property viewer");
  },


/**
* set the editor's entity and refresh display
*
* @param gid Global Entity ID
*/

  setEntity: function(gid) {
    DEBUG.traceEntry("setEntity");
    if (gid) {
      var tag = gid.replace(":","");
          prefix = tag.substring(0,3),
          id = tag.substring(3);
      this.showEntity(prefix,id);
    } else {
      this.showEntity();
    }
    DEBUG.traceExit("setEntity");
  },


/**
* clears the editor's entity and refresh display
*
*/

  clear: function() {
    DEBUG.traceEntry("clearEntity");
    this.prefix = null;
    this.entID = null;
    this.tag = null;
    this.entity = null;
    this.showEntity();
    DEBUG.traceExit("clearEntity");
  },


/**
* get editor's type string
*
* @returns string
*/

  getType: function() {
    return "entPropVE";
  },


/**
* show editor'd content div
*/

  show: function() {
    DEBUG.traceEntry("show");
    this.contentDiv.show();
    DEBUG.traceExit("show");
  },


/**
* hide editor's content div
*/

  hide: function() {
    DEBUG.traceEntry("hide");
    this.contentDiv.hide();
    DEBUG.traceExit("hide");
  },


  afterUpdate: function() {
    if (this.controlVE && this.controlVE.afterUpdate) {
      this.controlVE.afterUpdate(this.tag);
    }
  },


/**
* show entity's properties by creating UI for each property
*
* @param prefix A 3 letter entity type designator @see EntityPrefixLnk
* @param id Unique identifier of the entity
*/

  showEntity: function(prefix,id) {
    DEBUG.traceEntry("showEntity");
    if (prefix && id && this.dataMgr.getEntity(prefix,id)) {
      this.prefix = prefix;
      this.entID = id;
      this.tag = this.prefix + this.entID;
    }
    this.entity = this.dataMgr.getEntity(this.prefix,this.entID);//reread this as could be updated
    if (this.prefix && this.entID && this.entity) {
      this.entGID = this.prefix + ":" + this.entID;
      this.contentDiv.html('');
      this.createValueDisplay();
      if (this.prefix == "cat" && this.entity ) {
       if (this.dataMgr && !this.catTypeIDs){//default to list of dict or gloss
        this.catTypeIDs = [this.dataMgr.termInfo.idByTerm_ParentLabel["dictionary-catalogtype"],//term dependency
                        this.dataMgr.termInfo.idByTerm_ParentLabel["glossary-catalogtype"]];//term dependency
       }
//        this.createDescriptionUI();
        this.createTypeUI(this.catTypeIDs);
      }
      if (this.prefix == "seq" && this.entity ) {
        this.createSuperScriptDisplay();
        this.createSeqTypeUI();
      }
      if (this.prefix == "txt" && this.entity ) {
        this.createTextInvDisplay();
        this.createTextRefDisplay();
        this.createImageUI();
      }
      if (this.tag && this.controlVE.getEdnID &&
          this.dataMgr.getMatchEntities(this.tag,this.controlVE.getEdnID()).length) {
        this.createAltTranscriptionUI();
      }
      this.createTaggingUI();
      this.createAnnotationUI();
      this.createAttributionUI();

      if (this.prefix == "seq" && this.entity ) {
        this.createSubTypeUI();
      }
      if ((this.prefix == "seq" || this.prefix == "edn" || this.prefix == "cmp" ||
           this.prefix == "tok" || this.prefix == "scl" || this.prefix == "cat" ) && this.entity ) {
        this.createComponentsUI();
      }
      this.contentDiv.append('<hr class="viewEndRule">');
    } else {
      this.contentDiv.html('Entity Property Viewer - no entity information found.');
    }
    DEBUG.traceExit("showEntity");
  },


/**
* create UI for alternative transcriptions that map to this edition's entity
*
*/

  createAltTranscriptionUI: function() {
    var entPropVE = this, matchEntityTags,i, matchEntity, matchValue, matchAttr;
        switchable = !(this.dataMgr.getEntity('edn',this.controlVE.getEdnID())).readonly;

    //check for sandhi and ignore UI
    if (this.dataMgr.checkForSandhi(this.tag)) {
      return;
    }
    //create UI container
    this.altTransUI = $('<div class="altTransUI '+(switchable?'':'readonly')+'"></div>');
    this.contentDiv.append(this.altTransUI);
    matchEntityTags = this.dataMgr.getMatchEntities(this.tag,this.controlVE.getEdnID());
    if (matchEntityTags.length) {
      this.altTransUI.append($('<div class="altTransUIHeader"><span>Alternative Transcriptions:</span></div>'));
      for (i in matchEntityTags) {
        if (matchEntityTags[i] == this.tag) {
          continue;
        }
        if ((matchEntityTags[i].indexOf('scl') == 0  && this.prefix != 'scl') ||
            (matchEntityTags[i].indexOf('scl') == -1  && this.prefix == 'scl')) {//tok and cmp are not interchangable with scl
          continue;
        }
        matchEntity = this.dataMgr.getEntityFromGID(matchEntityTags[i]);
        if (matchEntity) {
          if (matchEntity.transcr) {
            matchValue = matchEntity.transcr;
          } else {
            matchValue = matchEntity.value;
          }
          if (matchEntity.attributionIDs && matchEntity.attributionIDs.length) {//attributions for now use first
            matchAttr = this.dataMgr.entities.atb[matchEntity.attributionIDs[0]];
          } else if (matchEntity.modStamp){
            matchAttr = matchEntity.modStamp;
          }
          this.altTransUI.append($('<div class="matchEntity '+matchEntityTags[i]+'">'+matchValue+
                                   (matchAttr?' <div class ="matchAttr">'+matchAttr.title+'</div>':'')+'</div>'));
        }
      }
      if (switchable) {
        $('div.matchEntity',this.altTransUI).unbind("click").bind("click",function(e) {
          var classes = $(this).attr('class'), entTag;
          entTag = classes.match(/(?:seq|scl|ano|tok|cmp)\d+/)[0];
          entPropVE.switchEntity(entPropVE.tag,entTag);
        });
      }
    }
  },


/**
* change to alternative transcription
*
* @param string entTag current entity tag
* @param string newEntTag alternative entity tag
*/

  switchEntity: function(entTag, newEntTag) {
    DEBUG.traceEntry("switchEntity");
    //todo  Add code to get ednVE selected item context to allow for switch within a compound
    var entPropVE = this,
        savedata = {
          ednID: this.ednID,
          entTag: entTag,
          newEntTag: newEntTag
        };
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/switchEntity.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              entPropVE.dataMgr.updateLocalCache(data,entPropVE.txtID);
              entPropVE.showEntity();
              if (entPropVE.controlVE.type == 'EditionVE') {
                entPropVE.controlVE.calcEditionRenderMappings();
                entPropVE.controlVE.renderEdition();
                newSelectionElem = $(".grpGra."+newEntTag,entPropVE.controlVE.editDiv);
                if (newSelectionElem.length) {
                  newSelectionElem.first().dblclick();
                }
              }
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
    DEBUG.traceExit("switchEntity");
  },


/**
* create Entity value property UI
*/

  createValueDisplay: function() {
    var entPropVE = this, valueEditable = ((this.prefix == "cat" || this.prefix == "txt" || this.prefix == "seq" || this.prefix == "edn") && this.entity && !this.entity.readonly),
        attrValue = null, value = this.entity.transcr ? this.entity.transcr : (this.entity.value ? this.entity.value : (this.entity.title ? this.entity.title : ""));
    DEBUG.traceEntry("createValueUI");
    value = value.replace("ʔ","");
    if (this.entity.attributionIDs && this.entity.attributionIDs.length &&
          this.dataMgr.entities.atb &&
          this.dataMgr.entities.atb[this.entity.attributionIDs[0]]) {//attributions for now use first
      attrValue = this.dataMgr.entities.atb[this.entity.attributionIDs[0]].value;
    } else if (this.entity.modStamp){
      attrValue = this.entity.modStamp;
    }

    //create UI container
    this.valueUI = $('<div class="valueUI"></div>');
    this.contentDiv.append(this.valueUI);
    //create label with navigation
    this.valueUI.append($('<div class="propDisplayUI">'+
                    '<div class="propFlipNavDiv propDisplayElement"><div class="med-flip editionNavButton"><span/></div></div>'+
                    '<div class="valueLabelDiv propDisplayElement'+(!valueEditable?' readonly':'')+
                      (attrValue?'" title="'+attrValue+'">':'">')+(value?value:'entity has no value')+'</div>'+
//                    '<div class="editionNavDiv propDisplayElement"><div class="med-prevword editionNavButton"><span/></div><div class="med-nextword editionNavButton"><span/></div></div>'+
                    '</div>'));
    //create input with save button
    this.valueUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="entity has no value" value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      if (valueEditable) {
        $('div.valueLabelDiv',this.valueUI).unbind("click").bind("click",function(e) {
          $('div.edit',entPropVE.contentDiv).removeClass("edit");
          entPropVE.valueUI.addClass("edit");
          $('div.valueInputDiv input',this.valueUI).focus();
          //$('div.valueInputDiv input',this.valueUI).select();
        });
        //blur to cancel
        $('div.valueInputDiv input',this.valueUI).unbind("blur").bind("blur",function(e) {
          if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
            entPropVE.valueUI.removeClass("edit");
          }
        });
        //mark dirty on input
        $('div.valueInputDiv input',this.valueUI).unbind("input").bind("input",function(e) {
          var curInput = $(this).val(), btnText = $('.saveDiv',entPropVE.valueUI).html();
          if ($('div.valueLabelDiv',this.valueUI).text() != $(this).val()) {
            if (!$(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().addClass("dirty");
            }
          } else if ($(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().removeClass("dirty");
          }
          if (!curInput && btnText == "Save" &&
                entPropVE.dataMgr.checkEntityDeletable(entPropVE.tag) &&
               (entPropVE.prefix == "edn" ||
                entPropVE.prefix == "txt" ||
                entPropVE.prefix == "cat")) {
            $('.saveDiv',entPropVE.valueUI).html("Delete").css('color','red');
          } else if (curInput && btnText != "Save") {
            $('.saveDiv',entPropVE.valueUI).html("Save").css('color','white');
          }
        });
        //save data
        $('.saveDiv',this.valueUI).unbind("click").bind("click",function(e) {
          var isSave = ($(this).html()== 'Save'),val,
              origText = $('div.valueLabelDiv',entPropVE.valueUI).html();
          if ($('.propEditUI',entPropVE.valueUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',entPropVE.valueUI).val();
            $('div.valueLabelDiv',entPropVE.valueUI).html(val);
            $('.propEditUI',entPropVE.valueUI).removeClass('dirty');
            if (isSave) {
              if (entPropVE.prefix == "seq") {
                 entPropVE.changeSequenceLabel(val);
              } else if (entPropVE.prefix == "edn") {
                 entPropVE.saveEditionLabel(val);
              } else if (entPropVE.prefix == "txt") {
                 entPropVE.changeTextTitle(val);
              } else if (entPropVE.prefix == "cat") {
                 entPropVE.changeCatalogTitle(val);
              }
            } else { //delete case
              switch (entPropVE.prefix) {
                case "edn":
                  if (confirm('Are you sure you want to delete edition "' + origText + '"?')) { // is delete
                    entPropVE.deleteEdition();
                  }
                  break;
                case "txt":
                  if (confirm('Are you sure you want to delete text "' + origText + '"?')) { // is delete
                    entPropVE.deleteText();
                  }
                  break;
                case "cat":
                  if (confirm('Are you sure you want to delete Catalog "' + origText + '"?')) { // is delete
                    entPropVE.deleteGlossary();
                  }
                  break;
              }
            }
            entPropVE.valueUI.removeClass("edit");
          }
        });
      }
      //click to flip editors
      $('.med-flip',this.valueUI).unbind("click").bind("click",function(e) {
        if (entPropVE.propMgr && entPropVE.propMgr.showVE) {
          entPropVE.propMgr.showVE("tabPropVE",entPropVE.propMgr.currentVE.tag);
        }
      });
/*      //previous entity
      $('.med-prevword',this.valueUI).unbind("click").bind("click",function(e) {
        if (entPropVE.controlVE && entPropVE.controlVE.prevEnt) {
          entPropVE.controlVE.prevEnt(entPropVE.prefix);
        }
      });
      //next entity
      $('.med-nextword',this.valueUI).unbind("click").bind("click",function(e) {
        if (entPropVE.controlVE && entPropVE.controlVE.nextEnt) {
          entPropVE.controlVE.nextEnt(entPropVE.prefix);
        }
      });
      */
    DEBUG.traceExit("createValueUI");
  },


/**
* create entity superscript property UI
*
*/

  createSuperScriptDisplay: function() {
    var entPropVE = this, value = this.entity.sup ? this.entity.sup : "";
    DEBUG.traceEntry("createSuperScriptDisplay");

    //create UI container
    this.supUI = $('<div class="supUI"></div>');
    this.contentDiv.append(this.supUI);
    //create label with navigation
    this.supUI.append($('<div class="propDisplayUI">'+
                    '<div class="valueLabelDiv propDisplayElement'+(this.entity.readonly?' readonly':'')+
                      '" title="short label for superscript label">'+(value?value:'Enter Superscript Label')+'</div>'+
                    '</div>'));
    //create input with save button
    this.supUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Enter Superscript Label" value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      if (!this.entity.readonly) {
        $('div.valueLabelDiv',this.supUI).unbind("click").bind("click",function(e) {
          $('div.edit',entPropVE.contentDiv).removeClass("edit");
          entPropVE.supUI.addClass("edit");
          $('div.valueInputDiv input',this.supUI).focus();
          //$('div.valueInputDiv input',this.supUI).select();
        });
        //blur to cancel
        $('div.valueInputDiv input',this.supUI).unbind("blur").bind("blur",function(e) {
          if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
            entPropVE.supUI.removeClass("edit");
          }
        });
        //mark dirty on input
        $('div.valueInputDiv input',this.supUI).unbind("input").bind("input",function(e) {
          if ($('div.valueLabelDiv',this.supUI).text() != $(this).val()) {
            if (!$(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().addClass("dirty");
            }
          } else if ($(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().removeClass("dirty");
          }
        });
        //save data
        $('.saveDiv',this.supUI).unbind("click").bind("click",function(e) {
          var val;
          if ($('.propEditUI',entPropVE.supUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',entPropVE.supUI).val();
            $('div.valueLabelDiv',entPropVE.supUI).html(val);
            $('.propEditUI',entPropVE.supUI).removeClass('dirty');
            if (entPropVE.prefix == "seq") {
               entPropVE.changeSequenceSup(val);
            }
          }
          entPropVE.supUI.removeClass("edit");
        });
      }
    DEBUG.traceExit("createSuperScriptDisplay");
  },


/**
* create entity text ref property UI
*
*/

  createTextRefDisplay: function() {
    var entPropVE = this, value = this.entity.ref ? this.entity.ref : "";
    DEBUG.traceEntry("createTextRefDisplay");

    //create UI container
    this.refUI = $('<div class="refUI"></div>');
    this.contentDiv.append(this.refUI);
    //create label with navigation
    this.refUI.append($('<div class="propDisplayUI">'+
                    '<div class="valueLabelDiv propDisplayElement'+(this.entity.readonly?' readonly':'')+
                      '" title="short label for Text">Ref: '+(value?value:'Enter Text RefLabel')+'</div>'+
                    '</div>'));
    //create input with save button
    this.refUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Enter Text RefLabel" value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      if (!this.entity.readonly) {
        $('div.valueLabelDiv',this.refUI).unbind("click").bind("click",function(e) {
          $('div.edit',entPropVE.contentDiv).removeClass("edit");
          entPropVE.refUI.addClass("edit");
          $('div.valueInputDiv input',this.refUI).focus();
          //$('div.valueInputDiv input',this.refUI).select();
        });
        //blur to cancel
        $('div.valueInputDiv input',this.refUI).unbind("blur").bind("blur",function(e) {
          if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
            entPropVE.refUI.removeClass("edit");
          }
        });
        //mark dirty on input
        $('div.valueInputDiv input',this.refUI).unbind("input").bind("input",function(e) {
          if ($('div.valueLabelDiv',this.refUI).text() != $(this).val()) {
            if (!$(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().addClass("dirty");
            }
          } else if ($(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().removeClass("dirty");
          }
        });
        //save data
        $('.saveDiv',this.refUI).unbind("click").bind("click",function(e) {
          var val;
          if ($('.propEditUI',entPropVE.refUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',entPropVE.refUI).val();
            $('div.valueLabelDiv',entPropVE.refUI).html('Ref: '+val);
            $('.propEditUI',entPropVE.refUI).removeClass('dirty');
            if (entPropVE.prefix == "txt") {
               entPropVE.changeTextRef(val);
            }
          }
          entPropVE.refUI.removeClass("edit");
        });
      }
    DEBUG.traceExit("createTextRefDisplay");
  },


/**
* create text entity inventory property UI
*/

  createTextInvDisplay: function() {
    var entPropVE = this, value = this.entity.CKN ? this.entity.CKN : "";
    DEBUG.traceEntry("createTextInvDisplay");

    //create UI container
    this.invUI = $('<div class="invUI"></div>');
    this.contentDiv.append(this.invUI);
    //create label with navigation
    this.invUI.append($('<div class="propDisplayUI">'+
                    '<div class="valueLabelDiv propDisplayElement'+(this.entity.readonly?' readonly':'')+
                      '" title="Inventory Number for Text">Inv: '+(value?value:'Enter Inv. No.')+'</div>'+
                    '</div>'));
    //create input with save button
    this.invUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Enter Inv. No." value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      if (!this.entity.readonly) {
        $('div.valueLabelDiv',this.invUI).unbind("click").bind("click",function(e) {
          $('div.edit',entPropVE.contentDiv).removeClass("edit");
          entPropVE.invUI.addClass("edit");
          $('div.valueInputDiv input',this.invUI).focus();
          //$('div.valueInputDiv input',this.invUI).select();
        });
        //blur to cancel
        $('div.valueInputDiv input',this.invUI).unbind("blur").bind("blur",function(e) {
          if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
            entPropVE.invUI.removeClass("edit");
          }
        });
        //mark dirty on input
        $('div.valueInputDiv input',this.invUI).unbind("input").bind("input",function(e) {
          if ($('div.valueLabelDiv',this.invUI).text() != $(this).val()) {
            if (!$(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().addClass("dirty");
            }
          } else if ($(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().removeClass("dirty");
          }
        });
        //save data
        $('.saveDiv',this.invUI).unbind("click").bind("click",function(e) {
          var val;
          if ($('.propEditUI',entPropVE.invUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',entPropVE.invUI).val();
            $('div.valueLabelDiv',entPropVE.invUI).html('Inv: '+val);
            $('.propEditUI',entPropVE.invUI).removeClass('dirty');
            if (entPropVE.prefix == "txt") {
               entPropVE.changeTextCKN(val);
            }
          }
          entPropVE.invUI.removeClass("edit");
        });
      }
    DEBUG.traceExit("createTextInvDisplay");
  },


/**
* create Radio button group UI
*
* @param string grpName
* @param string uiClass
* @param object array list
* @param int defTermID
*/

  createRadioGroupUI: function(grpName, uiClass, list, defTermID) {
    var entPropVE = this,
        radioGroup = $('<div class="radioGroupUI'+(uiClass?' '+uiClass:'')+'"></div>'),
        i, listDef, buttonDiv, isDef;
    if (grpName) {
      radioGroup.prop('grpName',grpName);
    }
    if (defTermID) {
      radioGroup.prop('origID',defTermID);
    }
    for (i in list) {
      listDef = list[i];
      buttonDiv = $('<button class="'+
                    (listDef.type?listDef.type:"buttonDiv")+
                    '" type="button"><span>'+listDef.label+'</span></button>');
      buttonDiv.prop('trmID',listDef.trmID);
      if (listDef.trmID == defTermID) {// initialize single selection, assumes list has unique trmIDs
        buttonDiv.addClass('selected');
      }
      radioGroup.append(buttonDiv);
    }
    //click to select
    $('.buttonDiv',radioGroup).unbind("click").bind("click",function(e) {
      var ctxDiv = $(this).parent().parent();
      if (!$(this).hasClass('selected')) {//if button not selected
        ctxDiv.find('.selected').removeClass('selected');
        $(this).addClass('selected');
      }
      //check value against original and update dirty flag for group
      if (radioGroup.prop('origID') != $(this).prop('trmID')) { // is dirty
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
      $('div.valueLabelDiv',entPropVE.typeUI).html(entPropVE.dataMgr.getTermFromID( $(this).prop('trmID')));
      entPropVE.typeUI.removeClass("edit");
      if (entPropVE.prefix == 'cat') {
        entPropVE.changeCatalogType($(this).prop('trmID'));
      }
    });
    return radioGroup;
  },

/**
* create Type property UI editor
*
* @param int[] typeIDs A set of term ids
* @param typeID Current or default term id
*/
  createTypeEditUI: function(typeIDs,typeID) {
    var entPropVE = this,
        typeEdit = $('<div class="propEditUI radioUI"></div>'),
        index, listType = [], term, trmID;
    for (index in typeIDs){
      trmID = typeIDs[index];
      term = this.dataMgr.getTermFromID(trmID);
      if (term) {
        listType.push({label:term,trmID:trmID});
      }
    }
    typeEdit.append(this.createRadioGroupUI("type", "TypeUI", listType,typeID));
    return typeEdit
  },


/**
* create Type property UI
*
* @param typeIDs int[] typeIDs A set of term ids
*/

  createTypeUI: function(typeIDs) {
    var entPropVE = this, typeID = (this.entity && this.entity.typeID)?this.entity.typeID:typeIDs[0],
        value = this.dataMgr.getTermFromID(typeID);
    DEBUG.traceEntry("createTypeUI");
    //create UI container
    this.typeUI = $('<div class="typeUI"></div>');
    this.contentDiv.append(this.typeUI);
    //create label
    this.typeUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                          '</div>'));
    //create input with save button
    this.typeUI.append(this.createTypeEditUI(typeIDs,typeID));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.typeUI).unbind("click").bind("click",function(e) {
        $('div.edit',entPropVE.contentDiv).removeClass("edit");
        entPropVE.typeUI.addClass("edit");
      });
    DEBUG.traceExit("createTypeUI");
  },


/**
* create sequence type property UI
*/

  createSeqTypeUI: function() {//type ui for sequence entities
    var entPropVE = this,
        valueEditable = (this.prefix == "seq" && this.entity && !this.entity.readonly),
        value = this.dataMgr.getTermFromID(this.entity.typeID),
        treeSeqTypeName = this.id+'seqtypetree';
    DEBUG.traceEntry("createSeqTypeUI");
    //create UI container
    this.typeUI = $('<div class="typeUI"></div>');
    this.contentDiv.append(this.typeUI);
    //create label with navigation
    this.typeUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement'+(valueEditable?"":" readonly")+'">'+value+'</div>'+
                          '</div>'));
    //create input with save button
    this.typeUI.append($('<div class="propEditUI">'+
                    '<div class="propEditElement"><div id="'+ treeSeqTypeName+'"></div></div>'+
                    '</div>'));
    this.seqTypeTree = $('#'+treeSeqTypeName,this.typeUI);
    this.seqTypeTree.jqxTree({
           source: this.dataMgr.seqTypes,
           hasThreeStates: false, checkboxes: false,
           theme:'energyblue'
    });
    $('.propEditElement',this.typeUI).addClass('seqTypeUI');
    //attach event handlers
      if (valueEditable) {
      //click to edit
        $('div.valueLabelDiv',this.typeUI).unbind("click").bind("click",function(e) {
          entPropVE.typeUI.addClass("edit");
          //init tree to current type
          var curItem = $('#trm'+entPropVE.entity.typeID, entPropVE.seqTypeTree),
              offset = 0;
          if (curItem && curItem.length) {
            curItem = curItem.get(0);
            entPropVE.suppressSelect = true;
            entPropVE.seqTypeTree.jqxTree('selectItem',curItem);
            //expand selected item sub tree if needed
            curItem = entPropVE.seqTypeTree.jqxTree('getSelectedItem');
            while (curItem && curItem.parentElement) {
              entPropVE.seqTypeTree.jqxTree('expandItem',curItem.parentElement);
              curItem = entPropVE.seqTypeTree.jqxTree('getItem',curItem.parentElement);
            }
            curItem = entPropVE.seqTypeTree.jqxTree('getItem',$('li:first',entPropVE.seqTypeTree).get(0));
            while (curItem && !curItem.selected) {
              offset += 25;
              if (curItem.isExpanded) {
                offset += 2;
              }
              curItem = entPropVE.seqTypeTree.jqxTree('getNextItem',curItem.element);
            }
            delete entPropVE.suppressSelect;
          }
          setTimeout(function(){
              $('.seqTypeUI',entPropVE.typeUI).scrollTop(offset);
            },50);
        });
        //blur to cancel
        this.seqTypeTree.unbind("blur").bind("blur",function(e) {
          $('div.valueLabelDiv',entPropVE.typeUI).html(entPropVE.dataMgr.getTermFromID(entPropVE.entity.typeID));
          entPropVE.typeUI.removeClass("edit");
        });
        //change sequence type
        this.seqTypeTree.on('select', function (event) {
            if (entPropVE.suppressSelect) {
              return;
            }
            var args = event.args, dropDownContent = '',
                item =  entPropVE.seqTypeTree.jqxTree('getItem', args.element);
            if (item.value && item.value != entPropVE.entity.typeID) {//user selected to change sequence type
              //save new type to entity
              entPropVE.changeSequenceType(item.value);
              $('div.valueLabelDiv',entPropVE.typeUI).html(entPropVE.dataMgr.getTermFromID(item.value));
            }
            entPropVE.typeUI.removeClass("edit");
        });
      }
    DEBUG.traceExit("createSeqTypeUI");
  },


/**
* create subtype selection UI
*/

  createSubTypeUI: function() {//subtype ui for sequence entities
    var entPropVE = this,
        valueEditable = (this.prefix == "seq" && this.entity && !this.entity.readonly),
        value, subTypeID = (this.entity.subtypeID?this.entity.subtypeID:this.entity.typeID),
        treeSubSeqTypeName = this.id+'subseqtypetree';
    DEBUG.traceEntry("createSubTypeUI");
    value = this.dataMgr.getTermFromID(subTypeID);
    //create UI container
    this.subTypeUI = $('<div class="subTypeUI"></div>');
    this.contentDiv.append(this.subTypeUI);
    //create label with Add new button
    this.subTypeUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement'+(valueEditable?"":" readonly")+'">Current Subtype '+value+'</div>'+
                          ((this.entity.readonly)?'':('<span class="addButton"><u>Add New '+value+'</u></span></div>'))+
                          '</div>'));
    //create input with selection tree
    this.subTypeUI.append($('<div class="propEditUI">'+
                    '<div class="propEditElement"><div id="'+ treeSubSeqTypeName+'"></div></div>'+
                    '</div>'));
    this.subSeqTypeTree = $('#'+treeSubSeqTypeName,this.subTypeUI);
    this.subSeqTypeTree.jqxTree({
           source: this.dataMgr.seqTypes,
           hasThreeStates: false, checkboxes: false,
           theme:'energyblue'
    });
    $('.propEditElement',this.subTypeUI).addClass('seqSubTypeUI');
    //attach event handlers
      if (valueEditable) {
      //click to edit
        $('div.valueLabelDiv',this.subTypeUI).unbind("click").bind("click",function(e) {
          $('div.edit',entPropVE.contentDiv).removeClass("edit");
          entPropVE.subTypeUI.addClass("edit");
          //init tree to current type
          var curItem = $('#trm'+(entPropVE.entity.subtypeID?entPropVE.entity.subtypeID:entPropVE.entity.typeID), entPropVE.subSeqTypeTree),
              offset = 0;
          if (curItem && curItem.length) {
            curItem = curItem.get(0);
            entPropVE.suppressSubSelect = true;
            entPropVE.subSeqTypeTree.jqxTree('selectItem',curItem);
            //expand selected item sub tree if needed
            curItem = entPropVE.subSeqTypeTree.jqxTree('getSelectedItem');
            while (curItem && curItem.parentElement) {
              entPropVE.subSeqTypeTree.jqxTree('expandItem',curItem.parentElement);
              curItem = entPropVE.subSeqTypeTree.jqxTree('getItem',curItem.parentElement);
            }
            curItem = entPropVE.subSeqTypeTree.jqxTree('getItem',$('li:first',entPropVE.subSeqTypeTree).get(0));
            while (curItem && !curItem.selected) {
              offset += 25;
              if (curItem.isExpanded) {
                offset += 2;
              }
              curItem = entPropVE.subSeqTypeTree.jqxTree('getNextItem',curItem.element);
            }
            delete entPropVE.suppressSubSelect;
          }
          setTimeout(function(){
              $('.seqSubTypeUI',entPropVE.subTypeUI).scrollTop(offset);
            },50);
        });
        //blur to cancel
        this.subSeqTypeTree.unbind("blur").bind("blur",function(e) {
          $('div.valueLabelDiv',entPropVE.subTypeUI).html("Current Subtype "+entPropVE.dataMgr.getTermFromID(entPropVE.entity.subtypeID));
          $('span.addButton',entPropVE.subTypeUI).html("<u>Add New "+entPropVE.dataMgr.getTermFromID(entPropVE.entity.subtypeID)+"</u>");
          entPropVE.subTypeUI.removeClass("edit");
        });
        //change sequence type
        this.subSeqTypeTree.on('select', function (event) {
            if (entPropVE.suppressSubSelect) {
              return;
            }
            var args = event.args, dropDownContent = '',
                item =  entPropVE.subSeqTypeTree.jqxTree('getItem', args.element);
            if (item.value && item.value != entPropVE.entity.subtypeID) {//user selected to change sequence type
              //save new subtype to entity
              entPropVE.entity.subtypeID = item.value;
              $('div.valueLabelDiv',entPropVE.subTypeUI).html("Current Subtype "+entPropVE.dataMgr.getTermFromID(entPropVE.entity.subtypeID));
              $('span.addButton',entPropVE.subTypeUI).html("<u>Add New "+entPropVE.dataMgr.getTermFromID(entPropVE.entity.subtypeID)+"</u>");
            }
            entPropVE.subTypeUI.removeClass("edit");
        });
        //attach event handlers
        $('span.addButton',entPropVE.subTypeUI).unbind("click").bind("click",function(e) {
            var subTypeID = entPropVE.entity.subtypeID?entPropVE.entity.subtypeID:entPropVE.entity.typeID;
            entPropVE.addNewSubSequenceType(subTypeID);
        });
      }
    DEBUG.traceExit("createSubTypeUI");
  },

  /************* Components ****************/

/**
* create entity components UI
*/

  createComponentsUI: function() {
    var entPropVE = this, i, j, prefix, id, tag, addBtnLabel = "",
        treeUnlinkEditionID = this.id+'unlinkededitions', cnt,
        value, type, entity, entIDs, graIDs;
    if (!this.entity) {
      return;
    }
    DEBUG.traceEntry("createComponentsUI");
    if (this.prefix == "edn" && this.entity.seqIDs) {
      entIDs = ("seq:" + this.entity.seqIDs.join(",seq:")).split(",");
    } else if (this.prefix == "cat" && this.entity.ednIDs) {
      entIDs = ("edn:" + this.entity.ednIDs.join(",edn:")).split(",");
    } else if (this.entity.entityIDs) {
      entIDs = this.entity.entityIDs;
    } else if (this.entity.graphemeIDs) {
      graIDs = this.entity.graphemeIDs;
    }
    //create UI container
    this.componentsUI = $('<div class="componentsUI"></div>');
    this.contentDiv.append(this.componentsUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    if (this.prefix == "cat") {
      addBtnLabel = 'Add edition';
    } else {
      addBtnLabel = (this.controlVE.componentLinkMode?'Leave link mode':'Add component');
    }
    displayUI.append($('<div class="componentsUIHeader"><span>Components:</span>'+
                       ((this.entity.readonly || graIDs)?'':('<span class="addButton"><u>'+addBtnLabel+'</u></span></div>'))));
    this.componentsUI.append(displayUI);
    if (graIDs && graIDs.length) {
      //create a list of components
      for (i in graIDs) {
        prefix = 'gra';
        id = graIDs[i];
        entity = this.dataMgr.getEntity(prefix,id);
        if (!entity) {
          DEBUG.log('warn',entIDs[i]+" entID not in datamanager");
          continue;
        }
        tag = prefix+id;
        value = entity.value ? entity.value + (entity.decomp?' ('+entity.decomp+')':'') : tag;
        type = entity.typeID ? " (" + this.dataMgr.getTermFromID(entity.typeID) + ")" : "";
        componentEntry = $('<div class="componententry">' +
                            '<span class="component locked '+tag+'">' + value + type + '</span>'+
                           '</div>');
        if (!this.entity.readonly && this.entity.value != "ʔ") {
          sandhiUI = $('<div class="sandhiUI"></div>');
          componentEntry.append(sandhiUI);
          sandhiUI.append($('<div class="sandhibtn '+tag+'"><u>sandhi</u></div>'));
          $('.sandhibtn',sandhiUI).prop('tag',tag);//attach entTag to be edited
          //create input with save button
          sandhiUI.append($('<div class="sandhiEditUI propEditUI">'+
                              '<div class="valueInputDiv propEditElement">'+
                                '<input class="valueInput" placeholder="Enter Decomposition"/>'+
                              '</div>'+
                              '<button class="saveDiv propEditElement">Save</button>'+
                            '</div>'));
          //click to edit
          $('div.sandhibtn',sandhiUI).unbind("click").bind("click",function(e) {
            var entTag = $(this).prop('tag'), value = '', graSandhiUI = $(this).parent(),
                inputElem = $('div.valueInputDiv input',graSandhiUI),
                entity = entPropVE.dataMgr.getEntityFromGID(entTag);
            if (entity && entity.decomp) {
              inputElem.val(entity.decomp);
              $('.saveDiv',sandhiUI).html('Save');
            }
            //show edit UI
            $('div.edit',entPropVE.contentDiv).removeClass("edit");
            graSandhiUI.addClass("edit");//mark component's sandhiUI div
            inputElem.focus().select();
          });
          //blur to cancel
          $('div.valueInputDiv input',sandhiUI).unbind("blur").bind("blur",function(e) {
            if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
              $(this).parent().parent().parent().removeClass("edit");
            }
          });
          //mark dirty on input
          $('div.valueInputDiv input',sandhiUI).unbind("input").bind("input",function(e) {
            var graSandhiUI = $(this).parent().parent().parent(),
                entTag = $('div.sandhibtn',graSandhiUI).prop('tag'),
                entity = entPropVE.dataMgr.getEntityFromGID(entTag),
                val = $(this).val();
            if (entity &&
                  ((entity.decomp && !val) ||
                   (val &&
                    (!entity.decomp || entity.decomp != val) &&
                    val.toLowerCase().match(/^([aāiīïüuūeēoō’l̥̄rṛṝ]+|[aāiīïüuūeēoō’l̥̄rṛṝ]+[\s-‐][aāiīïüuūeēoō’l̥̄rṛṝ]+)$/)))) {//todo extract to config for lang-script
              if (!$(this).parent().parent().hasClass("dirty")) {
                $(this).parent().parent().addClass("dirty");
              }
            } else if ($(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().removeClass("dirty");
            }
            if (entity.decomp && (!val || val.length == 0)){
              $('.saveDiv',sandhiUI).html('Delete');
            } else {
              $('.saveDiv',sandhiUI).html('Save');
            }
          });
          //save data
          $('.saveDiv',sandhiUI).unbind("click").bind("click",function(e) {
            var val = '',graSandhiUI = $(this).parent().parent(),
                entTag = $('div.sandhibtn',graSandhiUI).prop('tag');
            if ($('.sandhiEditUI',graSandhiUI).hasClass('dirty')) {
              val = $('div.valueInputDiv input',graSandhiUI).val();
              $('.sandhiEditUI',graSandhiUI).removeClass('dirty');
              entPropVE.saveSandhi(entTag,val);
            }
            graSandhiUI.removeClass("edit");
          });
        }
        displayUI.append(componentEntry);
      }
    } else if (entIDs && entIDs.length) {
      if (this.prefix == "cat" ) {  //if glossary then add edit UI for editions
        //create edition selection tree
        this.componentsUI.append($('<div class="propEditUI">'+
                        '<div class="propEditElement"><div id="'+ treeUnlinkEditionID+'"></div></div>'+
                        '</div>'));
        this.ednUnusedTree = $('#'+treeUnlinkEditionID,this.componentsUI);
        this.ednUnusedTree.jqxTree({
               source: this.dataMgr.calcUnusedEditions(this.entID),
               hasThreeStates: false, checkboxes: false,
               theme:'energyblue'
        });
        $('.propEditElement',this.componentsUI).addClass('editionSelectUI');
        //blur to cancel
        this.ednUnusedTree.unbind("blur").bind("blur",function(e) {
          entPropVE.componentsUI.removeClass("edit");
        });
        //change sequence type
        this.ednUnusedTree.on('select', function (event) {
            if (entPropVE.suppressAddEdition) {
              return;
            }
            var args = event.args,
                item =  entPropVE.ednUnusedTree.jqxTree('getItem', args.element);
            if (item.value) {//user selected to change sequence type
              //save edition to catalog entity
              entPropVE.addCatalogEditionID(item.value.substring(3));//value is tag (edn1)
            }
            entPropVE.componentsUI.removeClass("edit");
        });
      }

      //create a list of components
      for (i in entIDs) {
        prefix = entIDs[i].substring(0,3);
        id = entIDs[i].substring(4);
        entity = this.dataMgr.getEntity(prefix,id);
        if (!entity) {
          DEBUG.log('warn',entIDs[i]+" entID not in dataManager");
          continue;
        }
        tag = entIDs[i].replace(':','');
        value = entity.transcr ? entity.transcr : (entity.sup ? entity.sup : (entity.value ? entity.value : entIDs[i]));
        type = entity.typeID ? " (" + this.dataMgr.getTermFromID(entity.typeID) + ")" : "";
        componentEntry = $('<div class="componententry">' +
                            '<span class="component '+tag+'">' + value + type + '</span>'+
                            (this.entity.readonly?'':('<span class="unlink '+tag+'"><u>unlink</u></span>'))+
                          '</div>');
        $('.unlink',componentEntry).prop('gid',entIDs[i]);//attach entGID to be unlinked
        displayUI.append(componentEntry);
      }
      $('span.component',this.componentsUI).unbind("click").bind("click",function(e) {
        var classes = $(this).attr('class'), entTags, entTag;
        entTag = classes.match(/(?:seq|scl|ano|tok|cmp)\d+/)[0];
        //trigger selection change
        if (entTag.match(/seq/) && entPropVE.controlVE &&
            entPropVE.controlVE.entTagsBySeqTag && entPropVE.controlVE.entTagsBySeqTag[entTag]) {
          entTags = entPropVE.controlVE.entTagsBySeqTag[entTag];
        } else {
          entTags = [entTag];
        }
        $('.editContainer').trigger('updateselection',[entPropVE.id,entTags,entTag]);
        e.stopImmediatePropagation();
        return false;
      });
      $('span.component',this.componentsUI).unbind("dblclick").bind("dblclick",function(e) {
        var classes = $(this).attr('class'), entTag;
        entTag = classes.match(/(?:seq|scl|ano|tok|cmp)\d+/)[0];
        //drill down to component
        entPropVE.setEntity(entTag);
        e.stopImmediatePropagation();
        return false;
      });
      $('span.unlink',this.componentsUI).unbind("click").bind("click",function(e) {
        if (entPropVE.prefix == 'seq') {
          entPropVE.removeSequenceEntityGID($(this).prop('gid'));
        } else if (entPropVE.prefix == 'cat') {
          cnt = $('.linkedword.'+$(this).prop('gid').replace(':','')).length;
          if (cnt == 0) {
            entPropVE.removeCatalogEditionID($(this).prop('gid').substring(4));
          } else {
            alert("edition "+$(this).prop('gid')+" has "+cnt+" words attesting lemma and cannot be unlinked from glossary");
          }
        } else {
          alert("unlinking of "+entPropVE.prefix+" type entity components is under construction");
        }
      });
    }
    //attach event handlers
    $('span.addButton',this.componentsUI).unbind("click").bind("click",function(e) {
      if ($(this).text() == 'Add edition') {
        $('div.edit',entPropVE.contentDiv).removeClass("edit");
        entPropVE.componentsUI.addClass("edit");
      } else if ($(this).text() == 'Add component') {//switch to linking mode
        if (entPropVE.prefix == 'seq' && entPropVE.controlVE && entPropVE.controlVE.type == 'EditionVE') {
          selectedGIDs = entPropVE.controlVE.lastSelection;
          if (selectedGIDs && selectedGIDs.length > 0 &&
              confirm("Would you like to replace components with current selection?")) {
              entPropVE.changeSequenceEntityIDs(selectedGIDs);
          } else {
            $(this).html('<u>Leave link mode</u>');
            entPropVE.controlVE.setComponentLinkMode(true,entPropVE.entity.typeID);
          }
        } else {
          $(this).html('<u>Leave link mode</u>');
          entPropVE.controlVE.setComponentLinkMode(true,entPropVE.entity.typeID);
        }
      } else {//cancel linking mode
        $(this).html('<u>Add component</u>');
        entPropVE.controlVE.setComponentLinkMode(false);
      }
    });
    DEBUG.traceExit("createComponentsUI");
  },


/**
* save sandhi decomposition to grapheme
*
* @param string graTag grapheme entity tag
* @param string sandhiDecomp Sandhi decomposition
*/

  saveSandhi: function(graTag, sandhiDecomp) {
    DEBUG.traceEntry("saveSandhi");
    var context,
        graID = graTag.substring(3),
        grapheme = this.dataMgr.getEntityFromGID(graTag),
        entPropVE = this,
        savedata = {
          ednID: this.ednID,
          entTag: this.entGID.replace(':',''),
          graTag: graTag,
          decomp: sandhiDecomp
        };
    if (grapheme && grapheme.tokIDs && grapheme.tokIDs.length) {
      savedata['tokIDs'] = grapheme.tokIDs;
    }
    if (this.controlVE && this.controlVE.getFullContextFromGrapheme) {
      context = this.controlVE.getFullContextFromGrapheme(graID);
      if (context) {
        savedata['context'] = context;
        txtDivSeqID = context[0].substring(3);//caution dependency on class add order, assumes txtDiv seqTag is first
      }
    }
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveSandhi.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              entPropVE.dataMgr.updateLocalCache(data,entPropVE.txtID);
              if (entPropVE.controlVE && entPropVE.controlVE.type && entPropVE.controlVE.type == "EditionVE" ) {
                var ednVE = entPropVE.controlVE,
                    lineOrdTag =  $('.grpGra.'+entPropVE.tag,ednVE.contentDiv).get(0).className.match(/ordL\d+/)[0],
                    headerNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv),
                    physLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
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
                    DEBUG.log("data","after sandhi dump sequence \n" + DEBUG.dumpSeqData(data.alteredTextDivSeqIDs[i],0,1,ednVE.lookup.gra));
                  }
                }
                //recalc any updated text div sequences
                if (data.entities.update) {
                  if (data.entities.update.seq) {
                    for (seqID in data.entities.update.seq) {
                      updatedSeq = entities.seq[seqID];
                      if ( ednVE.trmIDtoLabel[updatedSeq.typeID] == "TextDivision") {
                        ednVE.calcTextDivGraphemeLookups(seqID);
                        DEBUG.log("data","after sandhi dump sequence \n" + DEBUG.dumpSeqData(seqID,0,1,ednVE.lookup.gra));
                      }
                    }
                  } else {
                    ednVE.calcTextDivGraphemeLookups(txtDivSeqID);
                    DEBUG.log("data","after sandhi dump sequence \n" + DEBUG.dumpSeqData(txtDivSeqID,0,1,ednVE.lookup.gra));
                  }
                }
                // calcLineGraphemeLookups
                ednVE.calcLineGraphemeLookups(physLineSeqID);
                //redraw line
                ednVE.reRenderPhysLine(lineOrdTag,physLineSeqID);
                //position sylED on new syllable
              }
              if (data.displayEntGID) { //display entity has change to a new entity
                entPropVE.showEntity(data.displayEntGID.substring(0,3),data.displayEntGID.substring(4));
              } else {
                entPropVE.showEntity();
              }
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
    DEBUG.traceExit("saveSandhi");
  },


/**
* create image UI
*/

  createImageUI: function() {
    var entPropVE = this,i,j, imgID, displayUI, image;
    DEBUG.traceEntry("createImageUI");
    //create UI container
    this.imgUI = $('<div class="imgUI"></div>');
    this.contentDiv.append(this.imgUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="imgUIHeader"><span>Images:</span><span class="addButton"><u>Add new</u></span></div>'));
    //create a list of Images for entity
    if (this.entity && this.entity.imageIDs && this.entity.imageIDs.length) {
      for (i in this.entity.imageIDs) {
        imgID = this.entity.imageIDs[i];
        image = this.dataMgr.getEntity("img",imgID);
        if (image) {
          image.tag = "img" + imgID;
          //TODO add code to display the different types linked vs URL vs text
          displayUI.append(this.createImageEntry(image));
        }
      }
    }
    this.imgUI.append(displayUI);
    $('div.imageentry',this.imgUI).unbind("dblclick").bind("dblclick",function(e) {
      var classes = $(this).attr("class"),imgTag,image;
      if (classes.match(/img\d+/)) {
        imgTag = classes.match(/img\d+/)[0];
      }
      image = entPropVE.dataMgr.getEntityFromGID(imgTag);
      if (image && !image.readonly && entPropVE.propMgr && entPropVE.propMgr.showVE) {
        entPropVE.propMgr.showVE("imgVE",imgTag);
      } else {
        UTILITY.beep();
      }
    });
    $('span.addButton',this.imgUI).unbind("click").bind("click",function(e) {
      if (entPropVE.propMgr && entPropVE.propMgr.showVE) {
        entPropVE.propMgr.showVE("imgVE");
      }
    });
    //remove anno
    $('span.removeimg',this.imgUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          imgTag = classes.match(/img\d+/)[0];
      entPropVE.removeImg(imgTag);
    });
    //create input with save button
    DEBUG.traceExit("createImageUI");
  },


/**
* create image entry
*
* @param {image} img Image entity
*/

  createImageEntry: function(img) {
    var entPropVE = this,
        thumbUrl = (img && (img.thumbUrl || img.url))? (img.thumbUrl?img.thumbUrl:img.url):null,
        imgType = this.dataMgr.getTermFromID(img.typeID);
    DEBUG.trace("createImageEntry");
    //create Annotation Entry
    return($('<div class="imageentry '+imgType+' '+img.tag+'">' +
         '<span class="image">' +
         (img.thumbUrl? '<img class="resImageIconBtn img'+img.id+'" src="'+img.thumbUrl+'" alt="Thumbnail not available"/>':'')+
         img.title + '</span>'+
         (img.readonly?'':'<span class="removeimg '+img.tag+'" title="remove tag '+img.tag+'">X</span>')+
         '</div>'));
    //create input with save button
  },


/**
* remove image
*
* @param string imgTag Image entity tag
*/

  removeImg: function(imgTag) {
    var entPropVE = this, savedata = {},imgID = imgTag.substring(3);
    DEBUG.traceEntry("removeImg");
    savedata["cmd"] = "removeImg";
    savedata["containerEntTag"] = this.tag;
    savedata["imgID"] = imgID;
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveImage.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              entPropVE.dataMgr.updateLocalCache(data,null);
              entPropVE.showEntity();
            }
            if (entPropVE.controlVE &&
                entPropVE.controlVE.type == "SearchVE"  && entPropVE.controlVE.updateCursorInfoBar) {
              entPropVE.controlVE.updateCursorInfoBar();
            }
            if (data.errors) {
              alert("An error occurred while trying to remove image record. Error: " + data.errors.join());
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to remove image record. Error: " + error);
        }
    });
    DEBUG.traceExit("removeImg");
  },


/**
* delete sequence
*
* @param string seqTag Sequence entity tag
* @param function cb Callback function
*/

  deleteSeq: function(seqTag,cb) {
    var entPropVE = this, deletedata = {},seqID = seqTag.substring(3),
        sequence = entPropVE.dataMgr.getEntityFromGID(seqTag);
    DEBUG.traceEntry("deleteSeq");
    deletedata["data"] = {"seq":[seqID]};
    if (seqTag.indexOf(':') != -1 || seqTag.indexOf('seq') != 0 ||
        !sequence || sequence.readonly || sequence.entityIDs && sequence.entityIDs.length) { //cannot delete
      DEBUG.log("err","Invalid seqTag '"+seqTag+"' passed to deleteSeq");
      DEBUG.traceExit("deleteSeq");
      return;
    }
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/deleteEntity.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: deletedata,
        async: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.entities) {
            //update data
            entPropVE.dataMgr.updateLocalCache(data,null);
            entPropVE.showEntity();
          }
          if (cb && typeof cb == "function") {
            cb((data['deleted'] && data['deleted'].length)?data['deleted'][0]:'seq'+seqID);
          }
          if (data.errors) {
            alert("An error occurred while trying to remove image record. Error: " + data.errors.join());
          }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to remove image record. Error: " + error);
        }
    });
    DEBUG.traceExit("removeImg");
  },


/**
* create Tagging UI
*/

  createTaggingUI: function() {
    var entPropVE = this,i,j, anoTag, tag, annoID, annoIDs, displayUI, annotation,
        customTagID = this.dataMgr.termInfo.idByTerm_ParentLabel['custom-tag'],//term dependency
        annoTagIDsByType = this.tag ? this.dataMgr.getEntityAnoTagIDsByType(this.tag):[];
    DEBUG.traceEntry("createTaggingUI");
    //create UI container
    this.tagUI = $('<div class="tagUI"></div>');
    this.contentDiv.append(this.tagUI);
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
      if (entPropVE.propMgr && entPropVE.propMgr.showVE) {
        entPropVE.propMgr.showVE("tagVE", entPropVE.tag);
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
* create tag entry
*
* @param string tag Label for tag
* @param string anoTag Annotation entity tag
*/

  createTagEntry: function(tag,anoTag) {
    var entPropVE = this;
    DEBUG.trace("createTagEntry");
    //create Annotation Entry
    return($('<div class="tagentry '+anoTag+'">' +
         '<span class="tag">' + tag + '</span>'+
         '<span class="removetag '+anoTag+'" title="remove tag '+tag+'">X</span>'+
         '</div>'));
  },


/**
* remove Tag from entity
*
* @param string anoTag Annotation entity tag
*/

  removeTag: function(anoTag) {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("removeTag");
    savedata["entGID"] = this.prefix+":"+this.entID;
    savedata["tagRemoveFromGIDs"] = [anoTag];
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
              entPropVE.dataMgr.updateLocalCache(data,null);
              entPropVE.propMgr.showVE();
              if (entPropVE.controlVE &&
                  entPropVE.controlVE.type == "EditionVE"  && entPropVE.controlVE.showTagTree) {
                entPropVE.controlVE.refreshTagMarkers();
              }
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


/**
* create attribution UI
*/

  createAttributionUI: function() {
    var entPropVE = this,i,j, atbTag, label, attrID, attrIDs, displayUI, attribution;
    DEBUG.traceEntry("createAttrbutionUI");
    //create UI container
    this.attrUI = $('<div class="attrUI"></div>');
    this.contentDiv.append(this.attrUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create a list of attributes
    if (this.entity && this.entity.attributionIDs && this.entity.attributionIDs.length &&
        this.dataMgr.getEntity("atb",this.entity.attributionIDs[0])) {
      attrIDs = this.entity.attributionIDs;
      //create Header
      if (this.entity.readonly) {
        displayUI.append($('<div class="attrUIHeader"><span>Attributions:</span></div>'));
      } else {
        displayUI.append($('<div class="attrUIHeader"><span>Attributions:</span><span class="addButton"><u>Edit</u></span></div>'));
      }
      for (i in attrIDs) {
        attrID = attrIDs[i];
        attribution = this.dataMgr.getEntity("atb",attrID);
        text = attribution.value;
        atbTag = "atb" + attrID;
        displayUI.append(this.createAttrEntry(text,atbTag));
      }
    } else {
      //create Header
      displayUI.append($('<div class="attrUIHeader"><span>Attributions:</span><span class="addButton"><u>Add</u></span></div>'));
    }
    //add or edit tags for this entity
    this.attrUI.append(displayUI);
    $('span.addButton',this.attrUI).unbind("click").bind("click",function(e) {
      if (entPropVE.propMgr && entPropVE.propMgr.showVE) {
        entPropVE.propMgr.showVE("attrVE", entPropVE.tag);
      }
    });
    //remove tag
    $('span.removeattr',this.attrUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          atbTag = classes.match(/atb\d+/)[0];
      entPropVE.removeAttr(atbTag);
    });
    //create input with save button
    DEBUG.traceExit("createAttrbutionUI");
  },


/**
* create attribution UI entry
*
* @param string attribution Attribution label
* @param string atbTag Attribution entity tag id
*/

  createAttrEntry: function(attribution,atbTag) {
    var entPropVE = this;
    DEBUG.trace("createAttrEntry");
    //create Attribution Entry
    return($('<div class="attrentry '+atbTag+'">' +
         '<span class="attribution">' + attribution + '</span>'+
         (this.entity.readonly?'':'<span class="removeattr '+atbTag+'" title="remove attribution '+attribution+'">X</span>')+
         '</div>'));
  },

  /**
  * remove attribution
  *
  * @param string atbTag Attribution entity tag id
  */
  removeAttr: function(atbTag) {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("removeAttr");
    savedata["entGID"] = this.prefix+":"+this.entID;
    savedata["attrRemoveFromGIDs"] = [atbTag];
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveAttrs.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              entPropVE.dataMgr.updateLocalCache(data,null);
              entPropVE.propMgr.showVE();
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
    DEBUG.traceExit("removeAttr");
  },


/**
* create annotation UI
*/

  createAnnotationUI: function() {
    var entPropVE = this,i,j, anoID, displayUI, annotation,
        annoIDsByType = this.tag ? this.dataMgr.getEntityAnoIDsByType(this.tag):[];
    DEBUG.traceEntry("createAnnotationUI");
    //create UI container
    this.annoUI = $('<div class="annoUI"></div>');
    this.contentDiv.append(this.annoUI);
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
      annotation = entPropVE.dataMgr.getEntityFromGID(anoTag);
      if (annotation && !annotation.readonly && entPropVE.propMgr && entPropVE.propMgr.showVE) {
        entPropVE.propMgr.showVE("annoVE",anoTag);
      } else {
        UTILITY.beep();
      }
    });
    $('span.addButton',this.annoUI).unbind("click").bind("click",function(e) {
      if (entPropVE.propMgr && entPropVE.propMgr.showVE) {
        entPropVE.propMgr.showVE("annoVE");
      }
    });
    //remove anno
    $('span.removeanno',this.annoUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          anoTag = classes.match(/ano\d+/)[0];
      entPropVE.removeAnno(anoTag);
    });
    //create input with save button
    DEBUG.traceExit("createAnnotationUI");
  },


/**
* create annotation UI entry
*
* @param {annotation} anno Annotation entity
*/

  createAnnotationEntry: function(anno) {
    var entPropVE = this, attribution, attrText = null,
        annoType = this.dataMgr.getTermFromID(anno.typeID);
    if (anno.attributionIDs && anno.attributionIDs.length > 0) {
      attribution = this.dataMgr.getEntity('atb',anno.attributionIDs[0]);
      if (attribution && attribution.value) {
        attrText = attribution.value;
      }
    }
    DEBUG.trace("createAnnotationEntry");
    //create Annotation Entry
    return($('<div class="annotationentry '+annoType+' '+anno.tag+'">' +
         '<span class="annotation">(' + annoType + ') ' + anno.text + '</span>'+
         '<span class="modstamp">' + (attrText?attrText:anno.modStamp) + '</span>'+
         (anno.readonly?'':'<span class="removeanno '+anno.tag+'" title="remove annotation '+anno.tag+'">X</span>')+
         '</div>'));
  },


/**
* remove annotation
*
* @param string anoTag Annotation entity tag id
*/

  removeAnno: function(anoTag) {
    var entPropVE = this, savedata = {};
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
              entPropVE.dataMgr.updateLocalCache(data,null);
              entPropVE.propMgr.showVE();
              if (entPropVE.propMgr && entPropVE.propMgr.entityUpdated) {
                entPropVE.propMgr.entityUpdated();
              }
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
* delete glossary
*
*/

  deleteGlossary: function() {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("deleteGlossary");
      savedata = {catID:entPropVE.entID};
      //save data
      $.ajax({
        dataType: 'json',
        url: basepath+'/services/deleteCatalog.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.entities) {
            entPropVE.dataMgr.updateLocalCache(data,null);
            if (entPropVE.controlVE && entPropVE.controlVE.layoutMgr) {
              entPropVE.controlVE.layoutMgr.clearPane(entPropVE.config.id);
              entPropVE.controlVE.layoutMgr.refreshCursor();
              entPropVE.controlVE.layoutMgr.refreshCatalogResources();
            }
            if (data['errors']) {
              alert("Error(s) occurred while trying to delete Glossary . Error(s): " +
                    data['errors'].join());
            }
          }
        },// end success cb
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to delete Glossary. Error: " + error);
        }
      });// end ajax
    DEBUG.traceExit("deleteGlossary");
  },


/**
* delete text
*
*/

  deleteText: function() {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("deleteText");
      savedata = {txtID:entPropVE.entID};
      //save data
      $.ajax({
        dataType: 'json',
        url: basepath+'/services/deleteText.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          var controlVE =entPropVE.controlVE, txtID = entPropVE.entID;
          if (typeof data == 'object' && data.success && data.entities) {
            entPropVE.dataMgr.updateLocalCache(data,null);
            entPropVE.clear();
            if (controlVE && controlVE.id == "searchVE" &&
                controlVE.removeTextRow) {
              controlVE.removeTextRow(txtID);
            }
            if (data['errors']) {
              alert("Error(s) occurred while trying to delete Text . Error(s): " +
                    data['errors'].join());
            }
          }
        },// end success cb
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to delete Text. Error: " + error);
        }
      });// end ajax
    DEBUG.traceExit("deleteText");
  },


/**
* delete edition
*
*/

  deleteEdition: function() {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("deleteEdition");
      savedata = {ednID:entPropVE.entID};
      //save data
      $.ajax({
        dataType: 'json',
        url: basepath+'/services/deleteEdition.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.entities) {
            entPropVE.dataMgr.updateLocalCache(data,null);
            if (entPropVE.controlVE && entPropVE.controlVE.layoutMgr) {
              entPropVE.controlVE.layoutMgr.clearPane(entPropVE.config.id);
              entPropVE.controlVE.layoutMgr.refreshCursor();
            }
            if (data['errors']) {
              alert("Error(s) occurred while trying to delete Edition . Error(s): " +
                    data['errors'].join());
            }
          }
        },// end success cb
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to delete Edition. Error: " + error);
        }
      });// end ajax
    DEBUG.traceExit("deleteEdition");
  },


/**
* save edition label
*
* @param string description Text to save to edition's description field
*/

  saveEditionLabel: function(description) {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("saveEditionLabel");
      savedata = {ednID:entPropVE.entID, description:description};
      //save data
      $.ajax({
        dataType: 'json',
        url: basepath+'/services/saveEdition.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.entities) {
            entPropVE.dataMgr.updateLocalCache(data,null);
            if (entPropVE.controlVE && entPropVE.controlVE.refreshEditionHeader) {
              entPropVE.controlVE.refreshEditionHeader();
            }
            if (data['errors']) {
              alert("Error(s) occurred while trying to save to a Edition record. Error(s): " +
                    data['errors'].join());
            }
          }
        },// end success cb
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to link. Error: " + error);
        }
      });// end ajax
    DEBUG.traceExit("saveEditionLabel");
  },


/**
* save edition sequence ids
*
* @param int ednID Edition entity id
* @param int[] seqIDs Array of sequence entity ids
* @param function cb Callback function
*/

  saveEditionSequenceIDs: function(ednID,seqIDs,cb) {
    var entPropVE = this, savedata = {};
    DEBUG.traceEntry("saveEditionLabel");
      savedata = {ednID:ednID, seqIDs:seqIDs};
      //save data
      $.ajax({
        dataType: 'json',
        url: basepath+'/services/saveEdition.php?db='+dbName,
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.entities) {
            entPropVE.dataMgr.updateLocalCache(data,null);
            if (entPropVE.controlVE && entPropVE.controlVE.refreshEditionHeader) {
              entPropVE.controlVE.refreshEditionHeader();
            }
          }
          if (cb && typeof cb == "function") {
            cb();
          }
          if (data['errors']) {
            alert("Error(s) occurred while trying to save to a Edition record. Error(s): " +
                  data['errors'].join());
          }
        },// end success cb
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to link. Error: " + error);
        }
      });// end ajax
    DEBUG.traceExit("saveEditionLabel");
  },


/**
* create new sequence
*
* @param int typeID sequence type id
* @param string[] entityGIDs Array of entity global ids
* @param function cb Callback function
*/

  createNewSequence: function(typeID,entityGIDs,cb) {
    this.saveSequence(null,typeID,null,null,entityGIDs,null,null,null,cb,null);
  },

/**
* change sequence type id
*
* @param int typeID sequence type id
* @param function cb Callback function
*/
  changeSequenceType: function(typeID,cb) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,typeID,null,null,null,null,null,null,cb,null);
    }
  },


/**
* change sequence label
*
* @param string label
*/

  changeSequenceLabel: function(label) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,null,label,null,null,null,null,null,null,null);
    }
  },


/**
* change sequence superscript
*
* @param string superscript Short label to use as a superscript
* @param function cb Callback function
*/

  changeSequenceSup: function(superscript,cb) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,null,null,superscript,null,null,null,null,cb,null);
    }
  },


/**
* save sequence entity ids
*
* @param string[] entityGIDs Array of entity global ids
* @param function cb Callback function
*/

  changeSequenceEntityIDs: function(entityIDs,cb) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,null,null,null,entityIDs,null,null,null,cb,null);
    }
  },


/**
* remove entity global identifier from sequence entityIDs
*
* @param string removeEntityGID Global entity id to be removed.
* @param function cb Callback function
*/

  removeSequenceEntityGID: function(removeEntityGID,cb) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,null,null,null,null,removeEntityGID,null,null,cb,null);
    }
  },


/**
* add entity global identifier from sequence entityIDs
*
* @param string addEntityGID Global entity id to be added.
* @param function cb Callback function
*/

  addSequenceEntityGID: function(addEntityGID,cb) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,null,null,null,null,null,addEntityGID,null,cb,null);
    }
  },


/**
* create a new subsequence and add it's global id to the sequence entityIDs
*
* @param int addSubSeqTypeID Type id of subsequence
* @param function cb Callback function
*/

  addNewSubSequenceType: function(addSubSeqTypeID,cb) {
    if (this.prefix == 'seq') {
      this.saveSequence(this.entID,null,null,null,null,null,null,addSubSeqTypeID,cb,null);
    }
  },


/**
* save sequence
*
* @param int seqID sequence entity id
* @param int typeID sequence type id
* @param string label
* @param string superscript Short label to use as a superscript
* @param string[] entityGIDs Array of entity global ids
* @param string removeEntityGID Global entity id to be removed.
* @param string addEntityGID Global entity id to be added.
* @param int addSubSeqTypeID Type id of subsequence
* @param function cb Callback function
*/

  saveSequence: function(seqID, typeID, label, superscript, entityIDs, removeEntityGID, addEntityGID, addSubSeqTypeID, cb, isSeqMove) {
    var savedata ={},url, text, typeID, vis,
        entPropVE = this;
    DEBUG.traceEntry("saveSequence");
    if (this.controlVE && this.controlVE.edition && this.controlVE.edition.id) {
      savedata["ednID"] = this.controlVE.edition.id;
    }
    if (seqID) {
      savedata["seqID"] = seqID;
    }
    if (label != null) {
      savedata["label"] = label;
    }
    if (isSeqMove != null) {
      savedata["isSeqMove"] = isSeqMove;
    }
    if (superscript != null) {
      savedata["superscript"] = superscript;
    }
    if (typeID) {
      savedata["typeID"] = typeID;
    }
    if (entityIDs) {
      savedata["entityIDs"] = entityIDs;
    }
    if (removeEntityGID) {
      savedata["removeEntityGID"] = removeEntityGID;
    }
    if (addEntityGID) {
      savedata["addEntityGID"] = addEntityGID;
    }
    if (addSubSeqTypeID) {
      savedata["addSubSeqTypeID"] = addSubSeqTypeID;
    }
    if (Object.keys(savedata).length > 0) {
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveSequence.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var seqID, newSeqID, propName, sequence, needsRefresh = false;
              if (typeof data == 'object' && data.success && data.entities &&
                  entPropVE && entPropVE.dataMgr ) {
                //update data
                entPropVE.dataMgr.updateLocalCache(data,null);
                if (entPropVE.showEntity && ((data.entities.update && data.entities.update.seq) ||
                                            (data.entities.insert && data.entities.insert.seq))) {
                  if (data.entities.insert && addSubSeqTypeID) {
                    newSeqID = Object.keys(data.entities.insert.seq)[0];
                  }
                  if (data.entities.insert && !addSubSeqTypeID) {
                    seqID = Object.keys(data.entities.insert.seq)[0];
                  } else {
                    seqID = Object.keys(data.entities.update.seq)[0];
                    if (data.entities.update.seq[seqID]) {
                      needsRefresh = true;
                    }
                  }
                  entPropVE.showEntity('seq',seqID);
                  sequence = entPropVE.dataMgr.getEntity('seq',seqID);
                  if (entPropVE.controlVE && entPropVE.controlVE.setDefaultSeqType){
                    entPropVE.controlVE.setDefaultSeqType(sequence.typeID);
                  }
                  if (entPropVE.controlVE && entPropVE.controlVE.refreshSeqMarkers) {
                    entPropVE.controlVE.refreshSeqMarkers([seqID]);
                  }
                  if (entPropVE.controlVE && entPropVE.controlVE.refreshPhysLineHeader) {
                    entPropVE.controlVE.refreshPhysLineHeader(seqID);
                  }
                  if (!cb && needsRefresh && entPropVE.controlVE && entPropVE.controlVE.refreshNode) {
                    entPropVE.controlVE.refreshNode('seq:'+seqID);
                  }
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Tag Entity***");
                  DEBUG.log("health","Params: "+JSON.stringify(savedata));
                  DEBUG.log("health",data.editionHealth);
                }
                if (cb && typeof cb == "function") {
                  cb('seq'+(addSubSeqTypeID?newSeqID:seqID));
                }
              }
              if (data.errors) {
                alert("An error occurred while trying to save sequence record. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save sequence record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveSequence");
  },


/**
* change catalog entity type id
*
* @param int typeID Catalog type id
*/

  changeCatalogType: function(typeID) {
    if (this.prefix == 'cat') {
      this.saveCatalog(this.entID,typeID,null,null,null,null,null);
    }
  },


/**
* change catalog entity title
*
* @param string title
*/

  changeCatalogTitle: function(title) {
    if (this.prefix == 'cat') {
      this.saveCatalog(this.entID,null,title,null,null,null,null);
    }
  },


/**
* change catalog entity description
*
* @param string description
*/

  changeCatalogDescription: function(description) {
    if (this.prefix == 'cat') {
      this.saveCatalog(this.entID,null,null,description,null,null,null);
    }
  },

/**
* change catalog entity edition ids
*
* @param int[] editionIDs Array of edition ids
*/
  changeCatalogEditionIDs: function(editionIDs) {
    if (this.prefix == 'cat') {
      this.saveCatalog(this.entID,null,null,null,editionIDs,null,null);
    }
  },


/**
* remove edition entity id from catalog entity
*
* @param int removeEditionID Edition entity id to be removed to catalog editionIDs
*/

  removeCatalogEditionID: function(removeEditionGID) {
    if (this.prefix == 'cat') {
      this.saveCatalog(this.entID,null,null,null,null,removeEditionGID,null);
    }
  },


/**
* add edition entity id from catalog entity
*
* @param int addEditionID Edition entity id to be added to catalog editionIDs
*/

  addCatalogEditionID: function(addEditionID) {
    if (this.prefix == 'cat') {
      this.saveCatalog(this.entID,null,null,null,null,null,addEditionID);
    }
  },


/**
* save catalog entity
*
* @param int catID Catalog entity id
* @param int typeID Catalog type id
* @param string title
* @param string description
* @param int[] editionIDs Array of edition ids
* @param int removeEditionID Edition entity id to be removed to catalog editionIDs
* @param int addEditionID Edition entity id to be added to catalog editionIDs
*/

  saveCatalog: function(catID, typeID, title, description, editionIDs, removeEditionID, addEditionID) {
    var savedata ={}, entPropVE = this;
    DEBUG.traceEntry("saveCatalog");
    if (catID) {
      savedata["catID"] = catID;
    }
    if (title != null) {
      savedata["title"] = title;
    }
    if (description != null) {
      savedata["description"] = description;
    }
    if (typeID) {
      savedata["typeID"] = typeID;
    }
    if (editionIDs) {
      savedata["editionIDs"] = editionIDs;
    }
    if (removeEditionID) {
      savedata["removeEditionID"] = removeEditionID;
    }
    if (addEditionID) {
      savedata["addEditionID"] = addEditionID;
    }
    if (Object.keys(savedata).length > 0) {
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveCatalog.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var catID, propName, sequence, needsRefresh = false;
              if (typeof data == 'object' && data.success && data.entities &&
                  entPropVE && entPropVE.dataMgr ) {
                //update data
                entPropVE.dataMgr.updateLocalCache(data,null);
                if (entPropVE.showEntity && ((data.entities.update && data.entities.update.cat))) {
                  catID = Object.keys(data.entities.update.cat)[0];
                  if (data.entities.update.cat[catID]) {
                    entPropVE.showEntity('cat',catID);
                  }
                  if (data.entities.update.cat[catID].title && entPropVE.controlVE && entPropVE.controlVE.refreshGlossaryHeader) {
                    entPropVE.controlVE.refreshGlossaryHeader();
                  }
                  if (removeEditionID && entPropVE.controlVE && entPropVE.controlVE.refreshGlossary) {
                    entPropVE.controlVE.refreshGlossary();
                  }
                  if (addEditionID && entPropVE.controlVE && entPropVE.controlVE.refreshGlossary) {
                    entPropVE.dataMgr.loadEdition(addEditionID, function() {entPropVE.controlVE.refreshGlossary();});
                  }
                }
              }
              if (data.errors) {
                alert("An error occurred while trying to save catalog record. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save catalog record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveCatalog");
  },


/**
* change text entity inventory id
*
* @param string ckn Inventory id for this text
*/

  changeTextCKN: function(ckn) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,ckn,null,null,null,null,null,null,null);
    }
  },


/**
* change text entity title
*
* @param string title
*/

  changeTextTitle: function(title) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,null,title,null,null,null,null,null,null);
    }
  },


/**
* change text entity reference label
*
* @param string refLabel Short label for identifying text
*/

  changeTextRef: function(refLabel) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,null,null,refLabel,null,null,null,null,null);
    }
  },


/**
* change text entity types
*
* @param int[] typeIDs Set of term ids identifying the typology of the text
*/

  changeTextTypeIDs: function(typeIDs) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,null,null,null,typeIDs,null,null,null,null);
    }
  },


/**
* set text entity imageIDs
*
* @param int[] imageIDs Set of image ids
*/

  changeTextImageIDs: function(imageIDs) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,null,null,null,null,imageIDs,null,null,null);
    }
  },


/**
* add type id to text entity
*
* @param int addTypeID text type id to add to typeIDs
*/

  addTextTypeID: function(addTypeID) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,null,null,null,null,null,addTypeID,null,null);
    }
  },


/**
* add image id to text
*
* @param int addImageID Image id to add to imageIDs
*/

  addTextImageID: function(addImageID) {
    if (this.prefix == 'txt') {
      this.saveText(this.entID,null,null,null,null,null,null,addImageID,null);
    }
  },


/**
* create new text
*/

  createNewText: function() {
    this.saveText(null,null,null,null,null,null,null,null,true);
  },


/**
* save text entity
*
* @param int txtID
* @param string ckn Inventory id for this text
* @param string title
* @param string refLabel Short label for identifying text
* @param int[] typeIDs Set of term ids identifying the typology of the text
* @param int[] imageIDs Set of image ids
* @param int addTypeID text type id to add to typeIDs
* @param int addImageID Image id to add to imageIDs
* @param boolean newText Flag to signal next text creation
*/

  saveText: function(txtID, ckn, title, refLabel, typeIDs, imageIDs, addTypeID, addImageID, newText) {
    var savedata ={}, text,
        entPropVE = this;
    DEBUG.traceEntry("saveText");
    if (txtID) {
      savedata["txtID"] = txtID;
    }
    if (ckn != null) {
      savedata["ckn"] = ckn;
    }
    if (title != null) {
      savedata["title"] = title;
    }
    if (refLabel != null) {
      savedata["ref"] = refLabel;
    }
    if (typeIDs && is_array(typeIDs)) {
      savedata["typeIDs"] = typeIDs;
    }
    if (imageIDs && is_array(imageIDs)) {
      savedata["imageIDs"] = imageIDs;
    }
    if (addTypeID) {
      savedata["addTypeID"] = addTypeID;
    }
    if (addImageID) {
      savedata["addImageID"] = addImageID;
    }
    if (newText) {
      savedata["addNewText"] = 1;
    }
    if (Object.keys(savedata).length > 0) {
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveText.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var txtID, propName, text, needsRefresh = false;
              if (typeof data == 'object' && data.success && data.entities &&
                  entPropVE && entPropVE.dataMgr ) {
                //update data
                entPropVE.dataMgr.updateLocalCache(data,null);
                if (entPropVE.showEntity && ((data.entities.update && data.entities.update.txt) ||
                                            (data.entities.insert && data.entities.insert.txt))) {
                  if (data.entities.insert) {
                    txtID = Object.keys(data.entities.insert.txt)[0];
                    text = entPropVE.dataMgr.getEntity('txt',txtID);
                    if (text && entPropVE.controlVE && entPropVE.controlVE.addTextRow &&
                        typeof entPropVE.controlVE.addTextRow == "function") {
                      entPropVE.controlVE.addTextRow(txtID, text.CKN, text.title);
                    }
                  } else {
                    txtID = Object.keys(data.entities.update.txt)[0];
                    if (data.entities.update.txt[txtID]) {
                      needsRefresh = true;
                    }
                  }
                  entPropVE.showEntity('txt',txtID);
                  if (entPropVE.controlVE && entPropVE.controlVE.refreshEntityDisplay &&
                      typeof entPropVE.controlVE.refreshEntityDisplay == "function") {
                    entPropVE.controlVE.refreshEntityDisplay(txtID);
                  }
                }
              }
              if (data.errors) {
                alert("An error occurred while trying to save sequence record. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save sequence record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveText");
  }
}

