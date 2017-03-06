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
* editors tagVE object
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

EDITORS.TagVE =  function(tagVECfg) {
  this.config = tagVECfg;
  this.id = tagVECfg['id'] ? "tagVE"+tagVECfg['id']:null;
  this.entTag = tagVECfg['entTag'] ? tagVECfg['entTag']:null;//
  this.cbVEType = tagVECfg['cbVEType'] ? tagVECfg['cbVEType']:null;//callback VE type
  this.controlVE = tagVECfg['editor'] ? tagVECfg['editor']:null;
  this.dataMgr = tagVECfg['dataMgr'] ? tagVECfg['dataMgr']:null;
  this.propMgr = tagVECfg['propMgr'] ? tagVECfg['propMgr']:null;
  this.editDiv = tagVECfg['editDiv'] ? $(tagVECfg['editDiv']):null;
  this.editDiv.addClass("autoScrollY");
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/

EDITORS.TagVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    this.curTagID = null;
    this.anchorTagID = null;
    this.parentTagID = null;
    this.customTrmTag = 'trm' + this.dataMgr.termInfo.idByTerm_ParentLabel['customtype-tagtype'];//term dependency
    DEBUG.traceEntry("init","init tag editor");
    if (this.dataMgr){
      this.customTagID = this.dataMgr.termInfo.idByTerm_ParentLabel["customtype-tagtype"];//term dependency
    }
    this.showTags();
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
      this.tag = tag;
    }
    this.showTags();
    DEBUG.traceExit("setEntity");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getType: function() {
    return "tagVE";
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

  showTags: function(entTag) {
    DEBUG.traceEntry("showTags");
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
    this.editDiv.html('<div class="taggingHeader">Tagging '+(value?' for '+value:'')+'</div>');
    this.createCustomUI();
    this.createMultiTagUI();
//    this.createAttrUI();
    this.setTagView();
    this.createSaveUI();
    if (this.controlVE.type == 'EditionVE') {

    }
    DEBUG.traceExit("showTags");
  },


/**
* put your comment there...
*
*/

  setTagView: function () {
    var tagVE = this, tagID2Path = this.dataMgr.entTagToPath,
        anchorPath, parentIndex, parentElem, parentItem, $parent, tagItemTree = this.dataMgr.tags,
        curTag, $anchor, anchorIndex, anchorItem, anchorElem;
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
      if (this.curTagID) {
        curTag = $('li#'+this.curTagID,this.tagTree);
        if (curTag.length) {
          this.tagTree.jqxTree("selectItem",curTag[0]);
          this.tagTree.jqxTree("ensureVisible",curTag[0]);
        }
      }
    } else {
      this.tagTree.jqxTree('refresh')
    }
  },


/**
* put your comment there...
*
*/

  createCustomUI: function() {
    var tagVE = this;
    DEBUG.traceEntry("createCustomUI");
    //create UI container
    this.custTagUI = $('<div class="custTagUI"></div>');
    this.editDiv.append(this.custTagUI);
    //create label
    this.custTagUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">Create Custom Tag</div>'+
                          '</div>'));
    //create input with save button
    this.custTagUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" placeholder="Type Tag Here"/></div>'+
                    '<div class="saveDiv propEditElement">Save</div>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.custTagUI).unbind("click").bind("click",function(e) {
        tagVE.custTagUI.addClass("edit");
        $('div.valueInputDiv input',tagVE.custTagUI).focus();
        //$('div.valueInputDiv input',tagVE.custTagUI).select();
      });
      //blur to cancel
      $('div.valueInputDiv input',this.custTagUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
          tagVE.custTagUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.custTagUI).unbind("input").bind("input",function(e) {
        if ($(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.custTagUI).unbind("click").bind("click",function(e) {
        var lemProp = {};
        if ($('.propEditUI',tagVE.custTagUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',tagVE.custTagUI).val();
          lemProp["trans"] = val;
          $('.propEditUI',tagVE.custTagUI).removeClass('dirty');
          tagVE.saveCustomTag(val);
        }
        tagVE.custTagUI.removeClass("edit");
      });
    DEBUG.traceExit("createCustomUI");
  },


/**
* put your comment there...
*
*/

  synchTagTreeWithEntity: function() {
    var tagVE = this, typeID, i, tagID, element, treeItem, origTags = {}, tagsByType;
    DEBUG.traceEntry("synchTagTreeWithEntity");
    if (!this.tagTree || !this.dataMgr || !this.dataMgr.tags) {
      return;
    }
    this.entity = this.dataMgr.getEntityFromGID(this.entTag);
    tagsByType = (this.entity && this.entity.linkedByAnoIDsByType) ? this.entity.linkedByAnoIDsByType:null;
    if (tagsByType && Object.keys(tagsByType).length) {
      for (typeID in tagsByType) {
        if (typeID == this.customTagID) {//handle custom tags
          for (i in tagsByType[typeID]) {
            tagID = "ano" + tagsByType[typeID][i];
            origTags[tagID] = 1;
          }
        } else {
          origTags["trm"+typeID] = 1;
        }
      }
    }
    this.origTags = origTags;
    if (Object.keys(origTags).length) {
      for (tagID in origTags) {//set checked for each tag
        element = $('#'+tagID,this.tagTree);
        if (element.length) {
          element = element.get(0);
          this.tagTree.jqxTree('checkItem', element, true);
          treeItem = this.tagTree.jqxTree('getItem',element);
          while (treeItem.parentElement) {
            element = treeItem.parentElement;
            treeItem = this.tagTree.jqxTree('getItem',element);
            this.tagTree.jqxTree('expandItem', element);
          }
        }
      }
    }
    DEBUG.traceExit("synchTagTreeWithEntity");
  },


/**
* put your comment there...
*
*/

  createMultiTagUI: function() {
    var tagVE = this;
    DEBUG.traceEntry("createMultiTagUI");
    //create UI container
    this.multiTagUI = $('<div class="multiTagUI"></div>');
    this.editDiv.append(this.multiTagUI);
    //create dropdown
//    this.dropDownBtn = $('<div></div>');
//    this.multiTagUI.append(this.dropDownBtn);
    //create tagTree
    this.tagTree = $('<div></div>');
    this.multiTagUI.append(this.tagTree);
    this.tagTree.jqxTree({
       source: tagVE.dataMgr.tags,
       hasThreeStates: false, checkboxes: true,
       width: '250px',
       height: '300px',
       theme:'energyblue'
    });
    this.synchTagTreeWithEntity();
    //*********attach event handlers**********
    this.tagTree.on('expand', function (event) {
      var item =  tagVE.tagTree.jqxTree('getItem', event.args.element),
          isOldAnchorParentOfItem = false, item, itemElement, parentItem;
      if (tagVE.anchorTagID != item.id && tagVE.parentTagID != item.id ) {
        if (tagVE.anchorTagID) {
          if (item.id) {
            itemElement = $('li#'+item.id,tagVE.tagTree);
            if (itemElement.length) {
              item = tagVE.tagTree.jqxTree("getItem",itemElement[0]);
              if (item && item.parentElement) {
                parentItem = tagVE.tagTree.jqxTree("getItem",item.parentElement);
                if (tagVE.anchorTagID == parentItem.id) {
                  isOldAnchorParentOfItem = true;
                }
              }
            }
          }
          anchor = $('li#'+tagVE.anchorTagID,tagVE.tagTree);
          anchorItem = tagVE.tagTree.jqxTree("getItem",anchor[0]);
          if (anchorItem && anchorItem.isExpanded && !isOldAnchorParentOfItem) {
            tagVE.tagTree.jqxTree("collapseItem",anchor[0]);
          }
        }
        tagVE.anchorTagID = item.id;
        tagVE.setTagView();
      }
    });
    this.tagTree.on('collapse', function (event) {
      // if collapse is anchor then make parent anchor unless node is top level
      var item =  tagVE.tagTree.jqxTree('getItem', event.args.element),
          anchor,anchorItem;
      if (tagVE.anchorTagID == item.id) {
        if (tagVE.parentTagID) {
          tagVE.anchorTagID = tagVE.parentTagID;
          //tagVE.parentTagID = null;
          tagVE.setTagView();
        }
      } else if (tagVE.parentTagID && tagVE.parentTagID == item.id ) {
        if (tagVE.anchorTagID) {
          anchor = $('li#'+tagVE.anchorTagID,tagVE.tagTree);
          anchorItem = tagVE.tagTree.jqxTree("getItem",anchor[0]);
          if (anchorItem && anchorItem.isExpanded) {
            tagVE.tagTree.jqxTree("collapseItem",anchor[0]);
          }
        }
        tagVE.anchorTagID = tagVE.parentTagID;
        tagVE.parentTagID = null;
        tagVE.setTagView();
      }
    });
    this.tagTree.on('checkChange', function (event) {
        var args = event.args, i,
            checked = args.checked, dirty = false,
            checkedItems = tagVE.tagTree.jqxTree('getCheckedItems'),
            item = tagVE.tagTree.jqxTree('getItem', args.element);
        if (item.id == tagVE.customTrmTag && item.checked){
          tagVE.tagTree.jqxTree('uncheckItem', args.element);
          return;
        }
        //update dirty flag - calc diff between origTags and selected
        if (Object.keys(tagVE.origTags).length != checkedItems.length) {
          dirty = true;
        } else {// same length so check same set
          for (i in checkedItems) {
            if (tagVE.origTags[checkedItems[i].id]) {
              continue;
            }
            dirty = true;
            break;
          }
        }
        if (dirty) { //properties have changed
          if (!tagVE.editDiv.hasClass("dirty")) {
            tagVE.editDiv.addClass("dirty");
          }
        } else {
          if (tagVE.editDiv.hasClass("dirty")) {
            tagVE.editDiv.removeClass("dirty");
          }
        }
    });
    DEBUG.traceExit("createMultiTagUI");
  },


/**
* put your comment there...
*
*/

  createSaveUI: function() {
    var tagVE = this,
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Cancel",type:"cancelBtnDiv"}];
    var tagVE = this,
        radioGroup = $('<div class="radioGroupUI annoSaveUI"></div>');
    radioGroup.append($('<button class="saveBtnDiv" type="button"><span>Save</span></button>'));
    radioGroup.append($('<button class="cancelBtnDiv" type="button"><span>Cancel</span></button>'));
    tagVE.editDiv.append(radioGroup);
    //click to cancel
    $('.cancelBtnDiv',tagVE.editDiv).unbind("click").bind("click",function(e) {
      tagVE.hide();
      if (tagVE.cbVEType && tagVE.dataMgr) {
        tagVE.propMgr.showVE(tagVE.cbVEType);
      }
    });
    //save multi-tag data
    $('.saveBtnDiv',tagVE.editDiv).unbind("click").bind("click",function(e) {
      tagVE.saveTags();
    });
  },


/**
* put your comment there...
*
* @param text
*/

  saveCustomTag: function(text) {
    var tagVE = this,
        savedata ={};
//    alert("creating cstom tag '"+text+"'");
    DEBUG.traceEntry("saveCustomTag");
    savedata["cmd"] = "createCustomTag";
    savedata["linkToGID"] = this.entTag;
    savedata["text"] = text;
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveAnno.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            var cmd = savedata["cmd"], selectedItem, i, element;
            if (typeof data == 'object' && data.success && data.entities &&
                tagVE && tagVE.dataMgr) {
              //update data
              tagVE.dataMgr.updateLocalCache(data,null);
              if (data.tagsInfo && tagVE.tagTree) {//tags updated need to refresh tag tree
                tagVE.tagTree.jqxTree('clear');
                tagVE.tagTree.jqxTree('addTo', tagVE.dataMgr.tags);
                tagVE.synchTagTreeWithEntity();
              }
              if (data.tagsInfo && tagVE.controlVE &&
                  tagVE.controlVE.type == "EditionVE") {
                if (tagVE.controlVE.tagTree) {//tags updated need the edition editors tags
                  //record select value
                  selectedItem = tagVE.controlVE.tagTree.jqxTree('getSelectedItem');
                  tagVE.controlVE.tagTree.jqxTree('clear');
                  tagVE.controlVE.tagTree.jqxTree('addTo', tagVE.dataMgr.tags);
                  //restore selection
                  if (selectedItem) {
                    element = $("#"+selectedItem.id, tagVE.controlVE.tagTree).get(0);
                    if (element){
                      tagVE.controlVE.tagTree.jqxTree('selectItem',element);
                      selectedItem = tagVE.controlVE.tagTree.jqxTree('getSelectedItem');
                      tagVE.controlVE.tagTree.jqxTree('expandItem',selectedItem.parentElement);
                      tagVE.controlVE.curTagID = selectedItem.value.replace(":","");
                    }
                  } else {
                    tagVE.controlVE.curTagID = null;
                  }
                }
                tagVE.controlVE.refreshTagMarkers();
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
    DEBUG.traceExit("saveCustomTag");
  },


/**
* put your comment there...
*
*/

  saveTags: function() {
    var savedata ={}, checkedItems, tagID, i, element, item, origTags = this.origTags,
        tagAddToGIDs = [], tagRemoveFromGIDs = [], tagVE = this;
    DEBUG.traceEntry("saveTags");
    if (!this.editDiv.hasClass("dirty")) {
      this.propMgr.showVE(tagVE.cbVEType);
      return;
    } else if(this.entTag && this.tagTree) {
      //calculate the difference  (added and removed tags)
      checkedItems = this.tagTree.jqxTree('getCheckedItems');
      for (i in checkedItems) {
        tagID = checkedItems[i].id;
        if (origTags[tagID]) {
          delete origTags[tagID];
        } else {//put this GID ano or trm in the add list
          tagAddToGIDs.push(checkedItems[i].value);
        }
      }
      if (Object.keys(origTags).length > 0) {
        for (tagID in origTags) {
          element = $('#'+tagID,this.tagTree.get(0));
          item = this.tagTree.jqxTree('getItem',element.get(0));
          tagRemoveFromGIDs.push(item.value);
        }
      }
      savedata={
        entGID: this.entTag,
      };
      if (tagAddToGIDs.length > 0) {
        savedata['tagAddToGIDs'] = tagAddToGIDs;
      }
      if (tagRemoveFromGIDs.length > 0) {
        savedata['tagRemoveFromGIDs'] = tagRemoveFromGIDs;
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveTags.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var cmd = savedata["cmd"];
              if (typeof data == 'object' && data.success && data.entities &&
                  tagVE && tagVE.dataMgr && tagVE.hide ) {
                //update data
                tagVE.dataMgr.updateLocalCache(data,null);
                tagVE.hide();
                if (tagVE.propMgr && tagVE.propMgr.showVE) {
                  if (tagVE.cbVEType) {
                    tagVE.propMgr.showVE(tagVE.cbVEType,tagVE.entTag);
                  } else {
                    tagVE.propMgr.showVE();
                  }
                }
              }
              if (data.tagsInfo && tagVE.controlVE &&
                  tagVE.controlVE.type == "EditionVE") {
                if (tagVE.controlVE.tagTree) {//tags updated need the edition editors tags
                  //record select value
                  selectedItem = tagVE.controlVE.tagTree.jqxTree('getSelectedItem');
                  tagVE.controlVE.tagTree.jqxTree('clear');
                  tagVE.controlVE.tagTree.jqxTree('addTo', tagVE.dataMgr.tags);
                  //restore selection
                  if (selectedItem) {
                    element = $("#"+selectedItem.id, tagVE.controlVE.tagTree).get(0);
                    if (element){
                      tagVE.controlVE.tagTree.jqxTree('selectItem',element);
                      selectedItem = tagVE.controlVE.tagTree.jqxTree('getSelectedItem');
                      tagVE.controlVE.curTagID = selectedItem.value.replace(":","");
                    }
                  } else {
                    tagVE.controlVE.curTagID = null;
                  }
                }
                tagVE.controlVE.refreshTagMarkers();
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
    DEBUG.traceExit("saveTags");
  }
}

