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
* editors imgVE object
*
* Editor for Creating and Editing Image Entities*
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
* Constructor for Imgage Entity Viewer/Editor Object
*
* @type Object
*
* @param imgVECfg is a JSON object with the following possible properties
*  "imgID" the image entity id to view/edit
*  "id" the id of the open pane used to create context ids for elements
*  "entTag" the entity tag of the cantaining entity
*  "cbVEType" identitfies the type of the callback editor
*  "editor" reference to the context editor/"controlling" editor
*  "ednID" the edition ID of the context
*  "dataMgr" reference to the data manager/model manager
*  "propMgr" reference to the property manager that switches property editors
*  "editDiv" reference to the div element for display of the ImgVE
*
* @returns {ImgVE}
*/

EDITORS.ImgVE =  function(imgVECfg) {
  this.config = imgVECfg;
  this.imgID = imgVECfg['imgID'] ? imgVECfg['imgID']:null;// if null then new
  this.id = imgVECfg['id'] ? "imgVE"+imgVECfg['id']:null;
  this.entTag = imgVECfg['entTag'] ? imgVECfg['entTag']:null;
  this.cbVEType = imgVECfg['cbVEType'] ? imgVECfg['cbVEType']:null;//callback VE type
  this.controlVE = imgVECfg['editor'] ? imgVECfg['editor']:null;
  this.dataMgr = imgVECfg['dataMgr'] ? imgVECfg['dataMgr']:null;
  this.propMgr = imgVECfg['propMgr'] ? imgVECfg['propMgr']:null;
  this.editDiv = imgVECfg['editDiv'] ? $(imgVECfg['editDiv']):null;
  this.editDiv.addClass("autoScrollY");
  this.init();
  return this;
};

/**
* Prototype for Imgage Entity Viewer/Editor Object
*/

EDITORS.ImgVE.prototype = {

/**
* initialiser for Image Entity Viewer/Editor
*/

  init: function() {
    DEBUG.traceEntry("init","init img editor");
    if (this.dataMgr){
      this.typeIDs = [this.dataMgr.termInfo.idByTerm_ParentLabel["eyecopy-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["inscriptioneyecopy-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["inscriptionphotograph-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["inscriptionphotographinfrared-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["inscriptionrubbing-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["manuscriptconserved-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["manuscriptreconstruction-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["reconstructedsurface-imagetype"],//term dependency
                      this.dataMgr.termInfo.idByTerm_ParentLabel["reliquaryphotograph-imagetype"]];//term dependency
    }
    this.showImg();
    DEBUG.traceExit("init","init img editor");
  },


/**
* set the editor's entity and refresh display
*
* @param gid Global Entity ID
*/

  setEntity: function(gid) {
    DEBUG.traceEntry("setEntity");
    var tag, prefix, id;
    if (gid) {
      tag = gid.replace(":","");
      prefix = tag.substring(0,3),
      id = tag.substring(3);
      if (prefix != "img"){
        alert(" invalid img ID reverting to new");
        this.showImg();
      } else {
        this.tag = tag;
        this.showImg(id);
      }
    }else{//new anno case
      delete this.tag;
      delete this.imgID;
      this.entity = null;
      this.showImg();
    }
    DEBUG.traceExit("setEntity");
  },


/**
* get editor's type string
*
* @returns string
*/

  getType: function() {
    return "imgVE";
  },


/**
* show editor'd content div
*/

  show: function() {
    DEBUG.traceEntry("show");
    this.editDiv.show();
    DEBUG.traceExit("show");
  },


/**
* hide editor'd content div
*/

  hide: function() {
    DEBUG.traceEntry("hide");
    this.editDiv.hide();
    DEBUG.traceExit("hide");
  },


/**
* show image entity's properties by creating UI for each property
*
* @param id Unique identifier of the image entity
*/

  showImg: function(id) {
    DEBUG.traceEntry("showImg");
    var value;
    if (id && this.dataMgr.getEntity("img",id)) {
      this.imgID = id;
      this.tag = "img" + id;
      this.entity = this.dataMgr.getEntity("img",id);
    } else if (this.imgID && this.dataMgr.getEntity("img",this.imgID)){
      this.tag = "img" + this.imgID;
      this.entity = this.dataMgr.getEntity("img",this.imgID);
    }
    if (this.entTag) {
      if (this.dataMgr.getEntityFromGID(this.entTag)) {
        value = this.dataMgr.getEntityFromGID(this.entTag).value;
      }
      if (!value) {
        value = this.entTag;
      }
    }
    this.editDiv.html('<div class="imgHeader">Image'+(value?' for '+value:'')+'</div>');
    this.createValueUI();
    this.createTypeUI();
    this.createVisUI();
    this.createUrlUI();
//    this.createAttrUI();
    this.createSaveUI();
    this.updateDirtyMarker();
    DEBUG.traceExit("showImg");
  },

/*************  Value Interface ****************/

/**
* create Entity value property UI
*/

  createValueUI: function() {
    var imgVE = this, valueEditable = (!this.imgID || this.entity && !this.entity.readonly),
        value = ((this.entity && this.entity.value) ? this.entity.value : "");
    DEBUG.traceEntry("createValueUI");

    //create UI container
    this.valueUI = $('<div class="valueUI"></div>');
    this.editDiv.append(this.valueUI);
    //create label with navigation
    this.valueUI.append($('<div class="propDisplayUI">'+
                    '<div class="valueLabelDiv propDisplayElement'+(!valueEditable?' readonly':'')+
                      '" title="Title for image.">'+(value?value:(!valueEditable?'':'Enter Title for Image'))+'</div>'+
                    '</div>'));
    //create input with save button
    this.valueUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Enter Title for Image" value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      if (valueEditable) {
        $('div.valueLabelDiv',this.valueUI).unbind("click").bind("click",function(e) {
          imgVE.valueUI.addClass("edit");
          $('div.valueInputDiv input',this.valueUI).focus();
        });
        //blur to cancel
        $('div.valueInputDiv input',this.valueUI).unbind("blur").bind("blur",function(e) {
          if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv') &&
              !$(e.originalEvent.explicitOriginalTarget).parent().hasClass('saveDiv')) {//all but save button
            imgVE.valueUI.removeClass("edit");
            if ($('div.valueLabelDiv',imgVE.valueUI).val() != $(this).val()) {
              if (!$(this).parent().parent().hasClass("dirty")) {
                $(this).parent().parent().addClass("dirty");
              }
            } else if ($(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().removeClass("dirty");
            }
            imgVE.updateDirtyMarker();
          }
        });
        //mark dirty on input
        $('div.valueInputDiv input',this.valueUI).unbind("input").bind("input",function(e) {
          if ($('div.valueLabelDiv',imgVE.valueUI).val() != $(this).val()) {
            if (!$(this).parent().parent().hasClass("dirty")) {
              $(this).parent().parent().addClass("dirty");
            }
          } else if ($(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().removeClass("dirty");
          }
          imgVE.updateDirtyMarker();
        });
        //save data
        $('.saveDiv',this.valueUI).unbind("click").bind("click",function(e) {
          var val;
          if ($('.propEditUI',imgVE.valueUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',imgVE.valueUI).val();
            $('div.valueLabelDiv',imgVE.valueUI).html(val);
          }
          imgVE.valueUI.removeClass("edit");
          imgVE.updateDirtyMarker();
        });
      }
    DEBUG.traceExit("createValueUI");
  },


/**
* update editor's dirty markers
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
* create image url UI
*/

  createUrlUI: function() {
    var imgVE = this,
        value = (this.entity && this.entity.url) ? this.entity.url:"";
        thumbnail = (this.entity && this.entity.thumbUrl) ? this.entity.thumbUrl:null;
    DEBUG.traceEntry("createUrlUI");
    //create UI container
    this.urlUI = $('<div class="descrUI"></div>');
    this.editDiv.append(this.urlUI);
    //create label
    this.urlUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"URL")+'</div>'+
                          '<div class="imageupload">'+
//                            '<form method="post" action="'+basepath+'/services/uploadImage.php" name ="imgupload" id="uploadimageform" enctype="multipart/form-data">' +
                            '<form method="post" name ="imgupload" id="uploadimageform" enctype="multipart/form-data">' +
                              '<input  type="hidden" value="imageuploadform" name="'+progressInputName+'">'+
                              '<input hidden="true" id="imageupload" type="file" name="file">'+
                            '</form>'+
                            '<div id="progressUI">'+
                             '<progress id="progressBar"></progress>'+
                             '<div id="status"></div>'+
                            '</div>'+
                            '<button id="btnupload">Upload Image</button>'+
                          '</div>'+
                        '</div>'));
    this.uploadUI = $('div.imageupload',this.urlUI);
    this.uploadBtn = $('#btnupload',this.uploadUI);
    this.progressUI = $('#progressUI',this.uploadUI);
    this.progressUI.hide();

    //create input with save button
    this.urlUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Type URL here" value="'+(value?value:"")+'"/></div>'+
                    '</div>'));

    //create input with save button
    this.thumb = $('<img id="thumb" class="thumb100" alt="Thumbnail not available"/>');
    this.urlUI.append(this.thumb);
    if (thumbnail) {
      this.thumb.attr('src',thumbnail);
      this.thumb.show();
    }

    //attach event handlers

    //upload button click
    $("#btnupload").click(function(e) {
        $("#imageupload").click();
        e.preventDefault();
      });

    // file select dialog change => file input change
    $('#imageupload').change(function (e) {
        //check file size and extensions supported
        if(this.files && this.files[0] &&
            this.files[0].size && this.files[0].size > maxUploadSize) {
          if (confirm("File exceeds maximum upload filesize ("+maxUploadSize+"). Would you like to choose another file?")) {
            $(this).click();
          }
          return;
        }
        //TODO check file extension
        $("#uploadimageform").submit();
        e.preventDefault();
      });

    //image upload submit
    $('#uploadimageform').submit(function(e) {
      var uldata = new FormData(this);
      //show progress UI
      imgVE.progressUI.show();
      //hide upload button
      imgVE.uploadBtn.hide();
      if (DEBUG.healthLogOn) {
        uldata['hlthLog'] = 1;
      }
      $.ajax({
        type:'POST',
        url: basepath+'/services/uploadImage.php?db='+dbName+'&entTag='+imgVE.entTag,
        data:uldata,
        asynch: true,
        xhr: function() {
            var imgUpXhr = $.ajaxSettings.xhr();
            if(imgUpXhr.upload){
                imgUpXhr.upload.addEventListener('progress',imgVE.handleProgress, false);
            }
            return imgUpXhr;
        },
        cache:false,
        contentType: false,
        processData: false,
        success:function(data,status,xhr){
          //show upload button
          imgVE.uploadBtn.show();
          //reset and hide progress UI
          imgVE.progressUI.hide();
          $('#progressBar').attr({value:0,max:1});
          $("#status",this.progressUI).html("");
          if (!data.errors && data.status && data.status.indexOf('Failed') == -1) {//success
            if (data.thumbUrl) {
              imgVE.thumb.attr('src',data.thumbUrl);
              imgVE.thumb.show();
            }
            if (data.imageUrl) {
              $('div.valueLabelDiv',imgVE.urlUI).html(data.imageUrl);
              $('div.valueInputDiv input',imgVE.urlUI).val(data.imageUrl);
              if (!imgVE.urlUI.hasClass("dirty")) {
                imgVE.urlUI.addClass("dirty");
              }
              imgVE.updateDirtyMarker();
            }
          }
          if (data.editionHealth) {
            DEBUG.log("health","***Upload Image***");
            DEBUG.log("health","Params: "+JSON.stringify(uldata));
            DEBUG.log("health",data.editionHealth);
          }
          alert(data.status);
        },
        error: function(xhr,status,error){
          //show upload button
          imgVE.uploadBtn.show();
          //reset and hide progress UI
          imgVE.progressUI.hide();
          $('#progressBar').attr({value:0,max:1});
          $("#status",this.progressUI).html("");
          //alert user
          if (error) {
            alert("upload failed: "+error);
          } else {
            alert("upload failed");
          }
        }
      });
      e.preventDefault();
    });


/**
* progress handler for image upload
*/

    imgVE.handleProgress = function(e) {
      var percent;
      if(e.lengthComputable){
        percent = Math.round(100*e.loaded/e.total);
        $('#progressBar').attr({value:e.loaded,max:e.total});
        if (e.loaded == e.total) {
          $("#status",this.progressUI).html("Upload complete");
        } else {
          $("#status",this.progressUI).html("" + percent + "% complete");
        }
      }
    }

    //click to edit
    $('div.valueLabelDiv',this.urlUI).unbind("click").bind("click",function(e) {
      imgVE.urlUI.addClass("edit");
      $('div.valueInputDiv input',imgVE.urlUI).focus();
      //$('div.valueInputDiv input',imgVE.urlUI).select();
    });

    //blur to cancel
    $('div.valueInputDiv input',this.urlUI).unbind("blur").bind("blur",function(e) {
      var val = $('div.valueInputDiv input',imgVE.urlUI).val();
      $('div.valueLabelDiv',imgVE.urlUI).html((val?val:"URL"));
      imgVE.urlUI.removeClass("edit");
      //if value diff from entity value then mark input as dirty.
      if ((imgVE.entity && imgVE.entity.value != val) ||
          val.length) {//case where new annotation then if text anno needs saving (dirty)
        if (!imgVE.urlUI.hasClass("dirty")) {
          imgVE.urlUI.addClass("dirty");
        }
      } else if (imgVE.urlUI.hasClass("dirty")) {
        imgVE.urlUI.removeClass("dirty");
      }
      imgVE.updateDirtyMarker();
    });

    DEBUG.traceExit("createUrlUI");
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
    var imgVE = this,
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
        $('div.valueLabelDiv',imgVE.typeUI).html(imgVE.dataMgr.getTermFromID( $(this).prop('trmID')));
        imgVE.typeUI.removeClass("edit");
        if (imgVE.annoTextUI){
          imgVE.annoTextUI.attr("placeholder","Enter "+imgVE.dataMgr.getTermFromID( $(this).prop('trmID'))+" text here");
        }
      } else if (radioGroup.hasClass("VisUI")) {
        $('div.valueLabelDiv',imgVE.visUI).html( $(this).prop('trmID'));
        imgVE.visUI.removeClass("edit");
      }
    });
    return radioGroup;
  },


/**
* create Type property UI editor
*
* @param typeID Current or default term id
*/

  createTypeEditUI: function(typeID) {
    var imgVE = this,
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
* create Type property UI
*/

  createTypeUI: function() {
    var imgVE = this, typeID = (this.entity && this.entity.typeID)?this.entity.typeID:this.typeIDs[0],
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
        imgVE.typeUI.addClass("edit");
      });
    DEBUG.traceExit("createTypeUI");
  },


/**
* create image entity visibility UI
**/

  createVisUI: function() {
    var imgVE = this, visEdit,
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
        imgVE.visUI.addClass("edit");
      });
    DEBUG.traceExit("createVisUI");
  },


/**
* create save Image UI
*/

  createSaveUI: function() {
    var imgVE = this,
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Cancel",type:"cancelBtnDiv"}];
    var imgVE = this,
        radioGroup = $('<div class="radioGroupUI annoSaveUI"></div>');
    radioGroup.append($('<button class="saveBtnDiv" type="button"><span>Save</span></button>'));
    radioGroup.append($('<button class="cancelBtnDiv" type="button"><span>Cancel</span></button>'));
    imgVE.editDiv.append(radioGroup);
    //click to cancel
    $('.cancelBtnDiv',imgVE.editDiv).unbind("click").bind("click",function(e) {
      imgVE.hide();
      if (imgVE.cbVEType && imgVE.dataMgr) {
        imgVE.propMgr.showVE(imgVE.cbVEType);
      }
    });
    //save inflection data
    $('.saveBtnDiv',imgVE.editDiv).unbind("click").bind("click",function(e) {
      imgVE.saveImage();
    });
  },


/**
* sae image entity
*
*/

  saveImage: function() {
    var savedata ={},url, title, typeID, vis,
        imgVE = this;
    DEBUG.traceEntry("saveImage");
    if (this.editDiv.hasClass("dirty") && this.entTag) {
      url = $('div.valueInputDiv input',this.urlUI).val();
      title = $('div.valueInputDiv input',this.valueUI).val();
      typeID = $('.buttonDiv.selected',this.typeUI).prop('trmID');
      vis = $('.buttonDiv.selected',this.visUI).prop('trmID');
      if (!this.imgID && (title.length || url.length)) {//create new img
        savedata["cmd"] = "createImg";
        savedata["containerEntTag"] = this.entTag;
        savedata["typeID"] = typeID;
        savedata["vis"] = vis;
        if (title.length) {
          savedata["title"] = title;
        }
        if (url.length) {
          savedata["url"] = url;
        }
      } else {// update annotation case
        savedata["cmd"] = "updateImg";
        savedata["imgID"] = this.imgID;
        if (this.entity.title != title){
          savedata["title"] = title;
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
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveImage.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var cmd = savedata["cmd"];
              if (typeof data == 'object' && data.success && data.entities &&
                  imgVE && imgVE.dataMgr && imgVE.hide ) {
                //update data
                imgVE.dataMgr.updateLocalCache(data);
                imgVE.updateDirtyMarker();
                imgVE.hide();
                if (imgVE.propMgr && imgVE.propMgr.showVE) {
                  if (imgVE.cbVEType) {
                    imgVE.propMgr.showVE(imgVE.cbVEType,imgVE.entTag);
                  } else {
                    imgVE.propMgr.showVE();
                  }
                  if (imgVE.propMgr.entPropVE &&
                      imgVE.propMgr.entPropVE.controlVE &&
                      imgVE.propMgr.entPropVE.controlVE.type == "SearchVE"  &&
                      imgVE.propMgr.entPropVE.controlVE.updateCursorInfoBar) {
                    imgVE.propMgr.entPropVE.controlVE.updateCursorInfoBar();
                  }
                }
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Save Image***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to save image record. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save image record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveImage");
  },

}

