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
* editors attrVE object
*
* Editor for Annotations of type tag decendent terms
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

EDITORS.AttrVE =  function(attrVECfg) {
  this.config = attrVECfg;
  this.id = attrVECfg['id'] ? "attrVE"+attrVECfg['id']:null;
  this.entTag = attrVECfg['entTag'] ? attrVECfg['entTag']:null;//
  this.cbVEType = attrVECfg['cbVEType'] ? attrVECfg['cbVEType']:null;//callback VE type
  this.controlVE = attrVECfg['editor'] ? attrVECfg['editor']:null;
  this.dataMgr = attrVECfg['dataMgr'] ? attrVECfg['dataMgr']:null;
  this.propMgr = attrVECfg['propMgr'] ? attrVECfg['propMgr']:null;
  this.editDiv = attrVECfg['editDiv'] ? $(attrVECfg['editDiv']):null;
  this.editDiv.addClass("autoScrollY");
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/

EDITORS.AttrVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("init","init attribute editor");
    this.showAttrs();
    DEBUG.traceExit("init","init attribute editor");
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
      this.tag = tag;
    }
    this.showAttrs(tag);
    DEBUG.traceExit("setEntity");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getType: function() {
    return "attrVE";
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
* @param string entTag Entity tag
*/

  showAttrs: function(entTag) {
    DEBUG.traceEntry("showAttrs");
    var value;
    if (entTag) {
      this.entTag = entTag;
    }
    if (this.entTag) {
      if (this.dataMgr.getEntityFromGID(this.entTag)) {
        this.entity = this.dataMgr.getEntityFromGID(this.entTag);
        value = this.entity.value;
      }
      if (!value) {
        value = this.entTag;
      }
    }
    if (this.editDiv.hasClass('dirty')) {
      this.editDiv.removeClass('dirty');
    }
    this.editDiv.html('');
    this.editDiv.html('<div class="attributionHeader">Attributions '+(value?' for '+value:'')+'</div>');
    this.createMultiAttrUI();
    this.createSaveUI();
    DEBUG.traceExit("showAttrs");
  },


/**
* put your comment there...
*
*/

  synchAttrListWithEntity: function() {
    var attrVE = this, typeID, i, attrID, element, treeItem, origAttrIDs, origAttrs = {};
    DEBUG.traceEntry("synchAttrListWithEntity");
    if (!this.attrList || !this.dataMgr || !this.dataMgr.attrs) {
      return;
    }
    this.entity = this.dataMgr.getEntityFromGID(this.entTag);
    origAttrIDs = (this.entity && this.entity.attributionIDs) ? this.entity.attributionIDs:null;
    if (origAttrIDs && origAttrIDs.length) {
      for (i in origAttrIDs) {
        attrID = origAttrIDs[i];
        origAttrs[attrID] = 1;
      }
    }
    this.origAttrs = origAttrs;
    if (Object.keys(origAttrs).length) {
      for (attrID in origAttrs) {//set checked for each tag, tree starts clear
        element = this.attrList.jqxListBox('getItemByValue', attrID);
        if (element) {
          this.attrList.jqxListBox('checkItem', element, true);
        }
      }
    }
    DEBUG.traceExit("synchAttrListWithEntity");
  },


/**
* put your comment there...
*
*/

  createMultiAttrUI: function() {
    var attrVE = this,
        source =
            {
                datatype: "jsonp",
                datafields: [
                    { name: 'label' },
                    { name: 'value' }
                ],
                url: basepath+"/services/searchAttributions.php?db="+dbName,
            };
            dataAdapter = new $.jqx.dataAdapter(source,
                {
                  formatData: function (data) {
                    srchString = attrVE.attrSearchInput.val();
                    if (srchString) {
                      data.titleContains = attrVE.attrSearchInput.val();
                    }
                    return data;
                  }
                }
            );
    DEBUG.traceEntry("createMultiAttrUI2");
    //create UI container
    this.multiAttrUI = $(
      '<div class="multiAttrUI" >' +
        '<div>' +
          '<span class="attrSearchLabel" >Search: </span>' +
          '<input class="attrSearchInput" placeholder="Type name here" type="text"/>' +
        '</div>' +
        '<div style="clear: both;"></div>' +
        '<div class="attrSearchListBox"></div>' +
     '</div>'
    );
    this.editDiv.append(this.multiAttrUI);
    //create attrList
    this.attrList = $('.attrSearchListBox',this.multiAttrUI);
    this.attrSearchInput = $('.attrSearchInput',this.multiAttrUI);
    this.attrList.jqxListBox(
            {
                width: 320,
                height: 350,
                source: dataAdapter,
                displayMember: "label",
                valueMember: "value",
                checkboxes:true
            });
    this.attrList.on('checkChange', function (event) {
        var args = event.args, i,
            checked = args.checked, dirty = false,
            checkedItems = attrVE.attrList.jqxListBox('getCheckedItems');
            item = attrVE.attrList.jqxListBox('getItem', args.element);
        //update dirty flag - calc diff between origTags and selected
        if (Object.keys(attrVE.origAttrs).length != checkedItems.length) {
          dirty = true;
        } else {// same length so check same set
          for (i in checkedItems) {
            if (attrVE.origAttrs[checkedItems[i].value]) {
              continue;
            }
            dirty = true;
            break;
          }
        }
        if (dirty) { //properties have changed
          if (!attrVE.editDiv.hasClass("dirty")) {
            attrVE.editDiv.addClass("dirty");
          }
        } else {
          if (attrVE.editDiv.hasClass("dirty")) {
            attrVE.editDiv.removeClass("dirty");
          }
        }
    });
    this.attrList.unbind('bindingComplete').bind('bindingComplete', function (event) {
        attrVE.synchAttrListWithEntity();
    });
    this.attrSearchInput.on('keyup', function (e) {
        if (attrVE.timer) clearTimeout(attrVE.timer);
        attrVE.timer = setTimeout(function () {
            attrVE.attrList.jqxListBox('uncheckAll');
            dataAdapter.dataBind();
        }, 300);
    });
    DEBUG.traceExit("createMultiAttrUI2");
  },


/**
* put your comment there...
*
*/

  createSaveUI: function() {
    var attrVE = this,
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Cancel",type:"cancelBtnDiv"}];
    var attrVE = this,
        radioGroup = $('<div class="radioGroupUI attrSaveUI"></div>');
    radioGroup.append($('<button class="saveBtnDiv" type="button"><span>Save</span></button>'));
    radioGroup.append($('<button class="cancelBtnDiv" type="button"><span>Cancel</span></button>'));
    attrVE.editDiv.append(radioGroup);
    //click to cancel
    $('.cancelBtnDiv',attrVE.editDiv).unbind("click").bind("click",function(e) {
      attrVE.hide();
      if (attrVE.cbVEType && attrVE.propMgr) {
        attrVE.propMgr.showVE(attrVE.cbVEType);
      }
    });
    //save multi-tag data
    $('.saveBtnDiv',attrVE.editDiv).unbind("click").bind("click",function(e) {
      attrVE.saveAttrs();
    });
  },


/**
* put your comment there...
*
*/

  saveAttrs: function() {
    var savedata ={}, checkedItems, attrID, i, element, item, origAttrs = this.origAttrs,
        attrAddToGIDs = [], attrRemoveFromGIDs = [], attrVE = this;
    DEBUG.traceEntry("saveAttrs");
    if (!this.editDiv.hasClass("dirty")) {
      this.propMgr.showVE(attrVE.cbVEType);
      return;
    } else if(this.entTag && this.attrList) {
      //calculate the difference  (added and removed attrs)
      checkedItems = this.attrList.jqxListBox('getCheckedItems');
      for (i in checkedItems) {
        attrID = checkedItems[i].value;
        if (origAttrs[attrID]) {
          delete origAttrs[attrID];
        } else {//put this GID ano or trm in the add list
          attrAddToGIDs.push(checkedItems[i].value);
        }
      }
      if (Object.keys(origAttrs).length > 0) {
        for (attrID in origAttrs) {
          attrRemoveFromGIDs.push('atb:'+attrID);
        }
      }
      savedata={
        entGID: this.entTag,
      };
      if (attrAddToGIDs.length > 0) {
        savedata['attrAddToGIDs'] = attrAddToGIDs;
      }
      if (attrRemoveFromGIDs.length > 0) {
        savedata['attrRemoveFromGIDs'] = attrRemoveFromGIDs;
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveAttrs.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities &&
                  attrVE && attrVE.dataMgr && attrVE.hide ) {
                //update data
                attrVE.dataMgr.updateLocalCache(data,null);
                attrVE.hide();
                if (attrVE.propMgr && attrVE.propMgr.showVE) {
                  if (attrVE.cbVEType) {
                    attrVE.propMgr.showVE(attrVE.cbVEType,attrVE.entTag);
                  } else {
                    attrVE.propMgr.showVE();
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
    DEBUG.traceExit("saveAttrs");
  },

}

