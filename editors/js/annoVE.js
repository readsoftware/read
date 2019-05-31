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
* editors annoVE object
*
* Editor for Annotations of type footnotes, comments and todos
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

EDITORS.AnnoVE =  function(annoVECfg) {
  this.config = annoVECfg;
  this.anoID = annoVECfg['anoID'] ? annoVECfg['anoID']:null;// if null then new
  this.id = annoVECfg['id'] ? "anoVE"+annoVECfg['id']:null;
  this.entTag = annoVECfg['entTag'] ? annoVECfg['entTag']:null;//lemma, token or compound etc.
  this.cbVEType = annoVECfg['cbVEType'] ? annoVECfg['cbVEType']:null;//callback VE type
  this.controlVE = annoVECfg['editor'] ? annoVECfg['editor']:null;
  this.typeIDs = annoVECfg['typeIDs'] ? annoVECfg['typeIDs']:null;
  this.dataMgr = annoVECfg['dataMgr'] ? annoVECfg['dataMgr']:null;
  this.propMgr = annoVECfg['propMgr'] ? annoVECfg['propMgr']:null;
  this.editDiv = annoVECfg['editDiv'] ? $(annoVECfg['editDiv']):null;
  this.editDiv.addClass("autoScrollY");
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
EDITORS.AnnoVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("init","init anno editor");
    if (! this.typeIDs && this.dataMgr){
      this.typeIDs = [this.dataMgr.termInfo.idByTerm_ParentLabel["footnote-footnotetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["transcription-footnote"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["reconstruction-footnote"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["translation-annotationtype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["chaya-translation"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["question-commentarytype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["comment-commentarytype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["glossary-commentarytype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["issue-commentarytype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["todo-workflowtype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["done-workflowtype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["obsolete-workflowtype"]];//term dependency
      this.config["typeIDs"] = this.typeIDs;
    }
    this.showAnno();
    DEBUG.traceExit("init","init anno editor");
  },


/**
* put your comment there...
*
* @param gid
*/

  setEntity: function(gid) {
    DEBUG.traceEntry("setEntity");
    var tag, prefix, id;
    if (gid) {
      tag = gid.replace(":","");
      prefix = tag.substring(0,3),
      id = tag.substring(3);
      if (prefix != "ano"){
        DEBUG.log("warn","setting annotation editor to non anno GID "+gid);
        delete this.tag;
        delete this.anoID;
        this.entity = null;
        this.linkTag = tag;
        this.showAnno();
      } else {
        this.tag = tag;
        this.showAnno(id);
      }
    }else{//new anno case
      delete this.tag;
      delete this.anoID;
      this.entity = null;
      this.showAnno();
    }
    DEBUG.traceExit("setEntity");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getType: function() {
    return "annoVE";
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


/**
* put your comment there...
*
* @param id
*/

  showAnno: function(id) {
    DEBUG.traceEntry("showAnno");
    var value = "unknown";
    if (id && this.dataMgr.getEntity("ano",id)) {
      this.anoID = id;
      this.tag = "ano" + id;
      this.entity = this.dataMgr.getEntity("ano",id);
    } else if (this.anoID && this.dataMgr.getEntity("ano",this.anoID)){
      this.tag = "ano" + this.anoID;
      this.entity = this.dataMgr.getEntity("ano",this.anoID);
    }
    if (this.linkTag) {
      if (this.dataMgr.getEntityFromGID(this.linkTag)) {
        value = this.dataMgr.getEntityFromGID(this.linkTag).value;
      }
      if (!value) {
        value = this.linkTag;
      }
    } else if (this.entTag) {
      if (this.dataMgr.getEntityFromGID(this.entTag)) {
        value = this.dataMgr.getEntityFromGID(this.entTag).value;
      }
      if (!value) {
        value = this.entTag;
      }
    }
    this.editDiv.html('<div class="annoHeader">Annotation'+(value?' for '+value:'')+'</div>');
    this.createTypeUI();
    this.createTextUI();
    this.createVisUI();
    this.createUrlUI();
//    this.createAttrUI();
    this.createSaveUI();
    DEBUG.traceExit("showAnno");
  },


/**
* put your comment there...
*
*/

  createTextUI: function() {
    var annoVE = this,
        typeID = (this.entity && this.entity.typeID ? this.entity.typeID:this.typeIDs[0]),
        value = (this.entity && this.entity.text ? this.entity.text:"");
    DEBUG.traceEntry("createTextUI");
    //create UI container
    this.valueUI = $('<div class="valueUI"></div>');
    this.editDiv.append(this.valueUI);
    //create label with navigation
    this.annoTextUI = $('<textarea class="textInput annoText">'+value+'</textarea>');
    this.valueUI.append(this.annoTextUI);
    this.annoTextUI.attr("placeholder","Enter "+this.dataMgr.getTermFromID(typeID)+" text here");
      //focus handler
//    this.annoTextUI.unbind("focus").bind("focus",function(e) {
//      $(this).select();
//    });
    this.annoTextUI.unbind("blur").bind("blur",function(e) {
      //if value diff from entity value then mark input as dirty.
      if ((annoVE.entity && annoVE.entity.value != this.value) ||
          this.value.length) {//case where new annotation then if text anno needs saving (dirty)
        if (!annoVE.valueUI.hasClass("dirty")) {
          annoVE.valueUI.addClass("dirty");
        }
      } else if (annoVE.valueUI.hasClass("dirty")) {
        annoVE.valueUI.removeClass("dirty");
      }
      annoVE.updateDirtyMarker();
    });
    DEBUG.traceExit("createTextUI");
  },


/**
* put your comment there...
*
*/

  updateDirtyMarker: function() {
    if ( this.editDiv.find('.dirty').length) { //properties have changed
      if (!this.editDiv.hasClass("dirty")) {
        this.editDiv.addClass("dirty");
      }
    } else {
      if (this.editDiv.hasClass("dirty")) {
        this.editDiv.removeClass("dirty");
      }
    }
  },


/**
* put your comment there...
*
*/

  removeDirtyMarkers: function() {
    this.editDiv.find('.dirty').removeClass("dirty");
  },


/**
* put your comment there...
*
*/

  createUrlUI: function() {
    var annoVE = this,
        value = (this.entity && this.entity.url) ? this.entity.url:"";
    DEBUG.traceEntry("createUrlUI");
    //create UI container
    this.urlUI = $('<div class="descrUI"></div>');
    this.editDiv.append(this.urlUI);
    //create label
    this.urlUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"URL")+'</div>'+
                          '</div>'));
    //create input with save button
    this.urlUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Type URL here" value="'+(value?value:"")+'"/></div>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.urlUI).unbind("click").bind("click",function(e) {
        annoVE.urlUI.addClass("edit");
        $('div.valueInputDiv input',annoVE.urlUI).focus();
        //$('div.valueInputDiv input',annoVE.urlUI).select();
      });
      //blur to cancel
      $('div.valueInputDiv input',this.urlUI).unbind("blur").bind("blur",function(e) {
        var val = $('div.valueInputDiv input',annoVE.urlUI).val();
        $('div.valueLabelDiv',annoVE.urlUI).html((val?val:"URL"));
        annoVE.urlUI.removeClass("edit");
        //if value diff from entity value tehn mark input as dirty.
        if ((annoVE.entity && annoVE.entity.value != val) ||
            val.length) {//case where new annotation then if text anno needs saving (dirty)
          if (!annoVE.urlUI.hasClass("dirty")) {
            annoVE.urlUI.addClass("dirty");
          }
        } else if (annoVE.urlUI.hasClass("dirty")) {
          annoVE.urlUI.removeClass("dirty");
        }
        annoVE.updateDirtyMarker();
      });
      //save data
    DEBUG.traceExit("createUrlUI");
  },


/**
* put your comment there...
*
* @param grpName
* @param uiClass
* @param list
* @param defTermID
*/

  createRadioGroupUI: function(grpName, uiClass, list, defTermID) {
    var annoVE = this,
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
      if (radioGroup.hasClass("TypeUI")) {
        $('div.valueLabelDiv',annoVE.typeUI).html(annoVE.dataMgr.getTermFromID( $(this).prop('trmID')));
        annoVE.typeUI.removeClass("edit");
        if (annoVE.annoTextUI){
          annoVE.annoTextUI.attr("placeholder","Enter "+annoVE.dataMgr.getTermFromID( $(this).prop('trmID'))+" text here");
        }
      } else if (radioGroup.hasClass("VisUI")) {
        $('div.valueLabelDiv',annoVE.visUI).html( $(this).prop('trmID'));
        annoVE.visUI.removeClass("edit");
      }
    });
    return radioGroup;
  },


/**
* put your comment there...
*
* @param typeID
*/

  createTypeEditUI: function(typeID) {
    var annoVE = this,
        typeEdit = $('<div class="propEditUI radioUI"></div>'),
        index, listType = [], term, trmID;
    for (index in this.typeIDs){
      trmID = this.typeIDs[index];
      term = this.dataMgr.getTermFromID(trmID);
      if (term) {
        listType.push({label:term,trmID:trmID});
      }
    }
    typeEdit.append(this.createRadioGroupUI("type", "TypeUI", listType,typeID));
    return typeEdit
  },


/**
* put your comment there...
*
*/

  createTypeUI: function() {
    var annoVE = this, typeID = (this.entity && this.entity.typeID)?this.entity.typeID:this.typeIDs[0],
        value = this.dataMgr.getTermFromID(typeID);
    DEBUG.traceEntry("createTypeUI");
    //create UI container
    this.typeUI = $('<div class="typeUI"></div>');
    this.editDiv.append(this.typeUI);
    //create label
    this.typeUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                          '</div>'));
    //create input with save button
    this.typeUI.append(this.createTypeEditUI(typeID));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.typeUI).unbind("click").bind("click",function(e) {
        annoVE.typeUI.addClass("edit");
      });
    DEBUG.traceExit("createTypeUI");
  },


/**
* put your comment there...
*
*/

  createVisUI: function() {
    var annoVE = this, visEdit,
        listVis =[{label:"Private",trmID:"Private"},
                  {label:"User",trmID:"User"},
                  {label:"Public",trmID:"Public"}],
        vis = (this.entity && this.entity.vis)?this.entity.vis:"Private";
    DEBUG.traceEntry("createVisUI");
    //create UI container
    this.visUI = $('<div class="visUI"></div>');
    this.editDiv.append(this.visUI);
    //create label
    this.visUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+vis+'</div>'+
                          '</div>'));
    //create edit
    visEdit = $('<div class="propEditUI radioUI"></div>');
    visEdit.append(this.createRadioGroupUI("vis", "VisUI", listVis,vis));
    this.visUI.append(visEdit);
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.visUI).unbind("click").bind("click",function(e) {
        annoVE.visUI.addClass("edit");
      });
    DEBUG.traceExit("createPOSUI");
  },


/**
* put your comment there...
*
*/

  createSaveUI: function() {
    var annoVE = this, annotation,
    radioGroup = $('<div class="radioGroupUI annoSaveUI"></div>');
    radioGroup.append($('<button class="saveBtnDiv" type="button"><span>Save</span></button>'));
    if (this.anoID) {
      annotation = this.dataMgr.getEntity('ano',this.anoID);
      if (!annotation.readOnly) {
        radioGroup.append($('<button class="deleteBtnDiv" type="button"><span>Delete</span></button>'));
      }
    }
    radioGroup.append($('<button class="cancelBtnDiv" type="button"><span>Cancel</span></button>'));
    annoVE.editDiv.append(radioGroup);
    //click to cancel
    $('.cancelBtnDiv',annoVE.editDiv).unbind("click").bind("click",function(e) {
      annoVE.hide();
      if (annoVE.cbVEType && annoVE.dataMgr) {
        annoVE.propMgr.showVE(annoVE.cbVEType);
      }
    });
    //delete annotation
    $('.deleteBtnDiv',annoVE.editDiv).unbind("click").bind("click",function(e) {
      annoVE.removeAnno();
    });
    //save annotation data
    $('.saveBtnDiv',annoVE.editDiv).unbind("click").bind("click",function(e) {
      annoVE.saveAnno();
    });
  },


/**
* put your comment there...
*
*/

  removeAnno: function() {
    var annoVE = this, savedata = {};
    DEBUG.traceEntry("removeAnno");
    savedata["cmd"] = "removeAno";
    savedata["linkFromGID"] = this.entTag;
    savedata["anoTag"] = this.tag;
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveAnno.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities && annoVE.hide ) {
              //update data
              annoVE.dataMgr.updateLocalCache(data);
              annoVE.hide();
              annoVE.removeDirtyMarkers();
              if (annoVE.propMgr && annoVE.propMgr.showVE) {
                if (annoVE.cbVEType) {
                  annoVE.propMgr.showVE();
                } else {
                  annoVE.propMgr.showVE();
                }
              }
              if (annoVE.propMgr && annoVE.propMgr.entityUpdated) {
                annoVE.propMgr.entityUpdated();
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
* put your comment there...
*
*/

  getAnchorEntityTag: function(entTag) {
    var annoVE = this, anchTag = null;
    DEBUG.traceEntry("getAnchorEntityTag");
    if (entTag && entTag.substring(0,3) == "seq") {
      sequence = annoVE.dataMgr.getEntityFromGID(entTag);
      extRefTypeID = annoVE.dataMgr.termInfo.idByTerm_ParentLabel["externalreference-textreferences"];//term dependency
      if (sequence.typeID == extRefTypeID) {
        entGIDs = sequence.entityIDs;
        anchTag = entGIDs[entGIDs.length - 1];
      }
    }
    return anchTag;
    DEBUG.traceExit("getAnchorEntityTag");
  },


/**
  * put your comment there...
  *
  */
    
  saveAnno: function() {
    var savedata ={},url, text, typeID, vis,
        annoVE = this;
    DEBUG.traceEntry("saveAnno");
    if (this.editDiv.hasClass("dirty") && (this.entTag || this.linkTag)) {
      url = $('div.valueInputDiv input',this.urlUI).val();
      text = this.annoTextUI.val();
      typeID = $('.buttonDiv.selected',this.typeUI).prop('trmID');
      vis = $('.buttonDiv.selected',this.visUI).prop('trmID');
      if (!this.anoID && (text.length || url.length)) {//create new annotation case
        savedata["cmd"] = "createAno";
        savedata["linkFromGID"] = (this.linkTag?this.linkTag:this.entTag);
        savedata["linkFromGIDAnchor"] = annoVE.getAnchorEntityTag(savedata["linkFromGID"]);
        savedata["typeID"] = typeID;
        savedata["vis"] = vis;
        if (text.length) {
          savedata["text"] = text;
        }
        if (url.length) {
          savedata["url"] = url;
        }
      } else {// update annotation case
        savedata["cmd"] = "updateAno";
        savedata["anoID"] = this.anoID;
        if (this.entity.text != text){
          savedata["text"] = text;
        }
        if (this.entity.typeID != typeID){
          savedata["typeID"] = typeID;
        }
        if (this.entity.url != url &&
            (this.entity.url || url)){
          savedata["url"] = url;
        }
        if (this.entity.vis != vis){
          savedata["vis"] = vis;
        }
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveAnno.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var cmd = savedata["cmd"],
                  anchTag = annoVE.getAnchorEntityTag(annoVE.linkTag?annoVE.linkTag:annoVE.entTag);
              if (typeof data == 'object' && data.success && data.entities &&
                annoVE && annoVE.dataMgr && annoVE.hide ) {
                //update data
                annoVE.dataMgr.updateLocalCache(data);
                annoVE.hide();
                annoVE.removeDirtyMarkers();
                if (annoVE.propMgr && annoVE.propMgr.showVE) {
                  if (annoVE.cbVEType) {
                    annoVE.propMgr.showVE();
                  } else {
                    annoVE.propMgr.showVE();
                  }
                }
                if (annoVE.propMgr && annoVE.propMgr.entityUpdated) {
                  if (anchTag) { // the annotation anchors to a contained entity
                    // ensure to update the line display for the anchor.
                    annoVE.propMgr.entityUpdated(anchTag);
                  } else {
                    annoVE.propMgr.entityUpdated();
                  }
                }
              }
              if (data.errors) {
                alert("An error occurred while trying to save annotation record. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save annotation record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveAnno");
  },

}

