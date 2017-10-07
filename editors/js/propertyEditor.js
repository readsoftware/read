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

if (termInfo && termInfo.labelByID ) {
  var trmIDtoLabel = trmIDtoLabel || termInfo.labelByID;
}
/**
* editors sclEditor object
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
* put your comment there...
*
* @type Object
*/

EDITORS.propEditor =  function(propertyVECfg,prefix,id) {
  this.config = propertyVECfg;
  this.prefix = propertyVECfg['prefix'] ? propertyVECfg['prefix']:null;
  this.dataMgr = propertyVECfg['dataMgr'] ? propertyVECfg['dataMgr']:null;
  this.editDiv = propertyVECfg['propEditDiv'] ? propertyVECfg['propEditDiv']:null;
  this.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
  this.id = propertyVECfg['id'] ? propertyVECfg['id']:null;
  this.gid = this.prefix + this.id;
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
EDITORS.propEditor.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("init"," for property editor");
    //retrieve data
    if (!this.entity){
      this.loadEntity();
    } else {
      this.displayProperties();
    }
    DEBUG.traceExit("init"," for property editor");
  },


/**
* put your comment there...
*
*/

  displayProperties: function() {
    var columns = this.entity.columns,
    propEd = this,
    fieldLabel = null,
    entities = this.dataMgr.entities,
    fields = this.entity.data,
    cnt = columns.length, i, propRow, editorContainer,
    tableID = this.prefix+this.id+"propTable",
    saveBtnID = this.prefix+this.id+"Save",
    typeinfo = this.dataMgr.entityInfo.entities[this.prefix],
    editDiv = $(this.editDiv);
    editDiv.html('<div class="entityEditDiv"><table id='+tableID+'></table></div>');
    this.propertyTable = $("#"+tableID);
    propRow = $('<tr class="' + columns[0] + '"/>');
    propRow.append($('<td align="right" class="entitylabel">' + typeinfo.dname + ' (' + this.id +')</td>'));
    propRow.append($('<td align="left" class="propertyvalue"><button id="'+saveBtnID+'" class="savebtn">Save</button></td>'));
    this.saveBtn = $('#'+saveBtnID,propRow);
    this.saveBtn.unbind('click').bind('click',function(e) {propEd.save(e)});
    this.propertyTable.append(propRow);
    for (i=1;i<cnt;i++) {
      fieldName = columns[i].replace(this.prefix+"_",'');
      if (fieldName == "owner_id" || fieldName == "visibility_ids" || fieldName == "scratch") {
        continue;
      }
      propRow = $('<tr class="' + columns[i] + '"/>');
      fieldLabel = typeinfo.nameToIndex[columns[i]]? // ensure this field in defined in terms
      typeinfo.columns[typeinfo.nameToIndex[columns[i]]].dname : fieldName;
      propRow.append($('<td align="right" class="propertylabel">' + fieldLabel +':</td>'));
      editorContainer = $('<td align="left" class="propertyvalue"/>');
      editorContainer.propEd = propEd;
      propRow.append(editorContainer);
      this.insertFieldVE(editorContainer,
        typeinfo.nameToIndex[columns[i]]?
        typeinfo.columns[typeinfo.nameToIndex[columns[i]]]:
        {dname:fieldName,name:columns[i]},//fake it for out of date term definition
        fields[i]?fields[i]:'');
      this.propertyTable.append(propRow);
    }
    propRow = $('<tr class="access"/>');
    propRow.append($('<td align="right" class="propertylabel">Access:</td>'));
    propRow.append($('<td align="left" class="propertyvalue">' +
      (entities[this.prefix][this.id].readonly?'Read Only' : 'Editable' )+ '</td>'));
    this.propertyTable.append(propRow);
    $('tr.editable>.propertylabel',this.propertyTable).unbind('click').bind('click',function(e) {
      if (!$(this).parent().hasClass('editing')) {
        $('.editing',propEd.editDiv).removeClass('editing');
        $(this).parent().addClass('editing');
        $(this).next().find('.editor').focus();
      } else {// click off editing
        $(this).parent().removeClass('editing');
      }
    });
  },


/**
* put your comment there...
*
* @param editContainer
* @param propInfo
* @param value
*/

  insertFieldVE: function(editContainer,propInfo,value) {
    var typeID = propInfo.type ? propInfo.type:null,
    dataType = propInfo.dtype ? propInfo.dtype:null,
    entities = this.dataMgr.entities,
    termInfo = this.dataMgr.termInfo,
    values,i,str = [],gid,
    dispName = propInfo.dname,
    readonly = entities[this.prefix][this.id].readonly,
    propName = propInfo.name;
    //calculated fields and primary keys are readonly
    if (!typeID || termInfo.automationTypeIDs[typeID] || typeID == termInfo.idByTerm_ParentLabel['Key-TermType']) {//Term definition dependency
      //      editContainer.html(value?value:'');
      EDITORS.createFieldViewer(editContainer,this.prefix,this.id,propName,value);
    } else if (termInfo.fkTypeIDs[typeID]) {
      value = value.replace('{','').replace('}','');
      if (value) {
        if (termInfo.fkTypeIDs[typeID]['mu']) {
          values = value.split(',');
        } else {
          values = [value];
        }
        for(i=0; i<values.length; i++) {
          if (termInfo.fkTypeIDs[typeID]['he']) {
            gid = values[i].split(':');
            if (entities[gid[0]] && entities[gid[0]][gid[1]] && entities[gid[0]][gid[1]].value) {
              str.push(entities[gid[0]][gid[1]].value);
            }
          } else {
            if (entities[propInfo.fktypes[0]] && entities[propInfo.fktypes[0]][values[i]] && entities[propInfo.fktypes[0]][values[i]].value) {
              str.push(entities[propInfo.fktypes[0]][values[i]].value);
            }
          }
        }
        EDITORS.createFieldViewer(editContainer,this.prefix,this.id,propName,str.length?str.join(', '):'');
      }
    } else if (typeID == termInfo.idByTerm_ParentLabel['text-single-termtype']) {
      if (readonly) {
        EDITORS.createFieldViewer(editContainer,this.prefix,this.id,propName,value);
      } else {
        //mark as editable
        editContainer.parent().addClass('editable');
        // convert to Label
        EDITORS.createInputFieldVE(editContainer,this.prefix,this.id,dispName,propName,value);
      }
    } else if (typeID == termInfo.idByTerm_ParentLabel['list-single-termtype']) {
      if (readonly) {
        EDITORS.createFieldViewer(editContainer,this.prefix,this.id,propName,value);
      } else {
        //mark as editable
        editContainer.parent().addClass('editable');
        // convert to Label
        EDITORS.createSelectFieldVE(editContainer,this.prefix,this.id,dispName,propName,value);
      }
    } else if (typeID == termInfo.idByTerm_ParentLabel['list-multiple-termtype'] || typeID == termInfo.idByTerm_ParentLabel['List-MultipleOrdered-TermType']) {
      if (readonly) {
        EDITORS.createFieldViewer(editContainer,this.prefix,this.id,propName,value);
      } else {
        //mark as editable
        editContainer.parent().addClass('editable');
        EDITORS.createMultiSelectFieldVE(editContainer,this.prefix,this.id,dispName,propName,value);
      }
    } else {
      //     editContainer.html(value?"default("+typeID+") "+value:'');
      EDITORS.createFieldViewer(editContainer,this.prefix,this.id,propName,value);
    }
  },


/**
* put your comment there...
*
*/

  loadEntity: function() {
    var prefix = this.prefix,
    propVE = this,
    entityName = this.dataMgr.entityInfo.entities[prefix].name,
    id = this.id;
    dataQuery = '{"'+prefix+'":{"ids":["'+id+'"]}}';
    $.ajax({
      dataType: 'json',
      url: basepath+'/services/getEntityData.php?db='+dbName,//caution dependency on context having basepath and dbName
      data: 'q='+dataQuery,
      async: true,
      success: function (data, status, xhr) {
        // add record suceeded.
        if (data) {
          if (entityName && data[entityName] && data[entityName].columns &&
            data[entityName].records[0] &&
            data[entityName].columns.indexOf(prefix + "_id") > -1) {
            propVE.entity = {columns:data[entityName].columns,
              data: data[entityName].records[0]};
            propVE.displayProperties();
            return;
          }
          if (entityName && data[entityName] && data[entityName].errors && data[entityName].errors.length) {
            alert("An error occurred while trying to retrieve a record. Error: " + data[entityName].errors.join());
          }
          if (data['error']) {
            alert("An error occurred while trying to retrieve a record. Error: " + data[error]);
          }
        }
      },
      error: function (xhr,status,error) {
        // add record failed.
        alert("An error occurred while trying to retrieve a record. Error: " + error);
      }
    });
  },


/**
* put your comment there...
*
* @param object e System event object
*/

  save: function(e) {
    var field,index,
    prefix = this.prefix,
    savedata ={},
    propVE = this,
    entityName = this.dataMgr.entityInfo.entities[prefix].name,
    id = this.id;
    DEBUG.traceEntry("save","save data for "+propVE.prefix+" "+propVE.id);
    if (this.dirty) {
      //calculate save data
      this.data[prefix+"_id"] = id;
      savedata[prefix] = [this.data];
      //jqAjax synch save
      $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveEntityData.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: 'data='+JSON.stringify(savedata),
        async: true,
        success: function (data, status, xhr) {
          //lookup entity name from prefix to access data
          var entity = entityInfo.entities[prefix].name;
          // add record suceeded.
          if (data) {
            /*              if (data[entity].columns &&
            data[entity].records[0] &&
            data[entity].columns.indexOf(prefix + "_id") > -1) {
            propVE.entity = {columns:data[entity].columns,
            data: data[entity].records[0]};
            propVE.displayProperties();
            return;
            }*/
            propVE.dirty = false;
            for (field in propVE.data) {
              index = propVE.entity.columns.indexOf(field);
              propVE.entity.data[index] = propVE.data[field];
            }
            propVE.data = {};
            $(propVE.editDiv).removeClass('dirty');
            $('.dirty',$(propVE.editDiv)).removeClass('dirty');
            if (data[entityName].errors && data[entityName].errors.length) {
              alert("An error occurred while trying to retrieve a record. Error: " + data[entityName].errors.join());
            }
            if (data.error) {
              alert("An error occurred while trying to retrieve a record. Error: " + data.error);
            }
          }
        },
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to retrieve a record. Error: " + error);
        }
      });
    }
    DEBUG.traceExit("save","save data for "+propVE.prefix+" "+propVE.id);
  }
}

// data adapter for enum fields as static members. Assumes that jqxDropDown use this as readonly.
EDITORS.daSelectLookup = {};
/**
* put your comment there...
*
* @param prefix
* @param datafield
*
* @returns {Object}
*/

EDITORS.getDASelectLookup = function(prefix,datafield) {
  if (!EDITORS.daSelectLookup[datafield]) {
    EDITORS.daSelectLookup[datafield] = new $.jqx.dataAdapter({ datatype: "array",
      datafields:
      [
        { name: 'trmID', type: 'number'},
        { name: 'label', type: 'string'}
      ],
      localdata: dataManager.entityInfo.lookups[prefix][datafield]},
      { autobind: true});
  }
  return EDITORS.daSelectLookup[datafield];
};

// template for multi select editor creation
/**
* put your comment there...
*
* @param container
* @param prefix
* @param id
* @param label
* @param datafield
* @param values
*/

EDITORS.createMultiSelectFieldVE = function(container,prefix,id,label,datafield,values) {
  var editor = $('<div id="'+prefix+id+datafield+'editor" class="editor"/>'),
  viewer = $('<div id="'+prefix+id+datafield+'" class="viewer"/>'),
  str = [];
  editor.jqxDropDownList({source:EDITORS.getDASelectLookup(prefix,datafield),
    checkboxes: true,
    displayMember: 'label',
    valueMember: 'trmID'});
  var items = editor.jqxDropDownList('getItems');
  editor.jqxDropDownList('uncheckAll');
  if (values) {
    var values = values.split(/,\s*/);
    for (var j = 0; j < values.length; j++) {
      if (trmIDtoLabel[values[i]]) {
        str.push(trmIDtoLabel[values[i]]);
      }
      for (var i = 0; i < items.length; i++) {
        if (items[i].value == values[j]) {
          editor.jqxDropDownList('checkIndex', i);
        }
      }
    }
  }
  viewer.text(str.length?str.join(', '):'');
  container.append(viewer);
  container.append(editor);
};

/**
* template for single select editor
*
* @param container
* @param prefix
* @param id
* @param label
* @param datafield
* @param value
*/

EDITORS.createSelectFieldVE = function(container,prefix,id,label,datafield,value) {
  var editor = $('<div id="'+prefix+id+datafield+'" class="editor"/>'),
  viewer = $('<div id="'+prefix+id+datafield+'" class="viewer"/>'),
  editDiv = $(container.propEd.editDiv),
  propEd = container.propEd;
  editor.jqxDropDownList({source: EDITORS.getDASelectLookup(prefix,datafield),
    incrementalSearch: false,
    animationType: 'none',
    placeHolder: 'Please select '+label,
    autoOpen: true,
    displayMember: 'label',
    valueMember: 'trmID'});
  var items = editor.jqxDropDownList('getItems');
  if (value) {
    for (var i = 0; i < items.length; i++) {
      if (items[i].value == value) {
        editor.jqxDropDownList('selectIndex', i);
      }
    }
  }
  editor.unbind('change').bind('change',
    function(e) {
      if (!editDiv.hasClass('dirty')) {
        editDiv.addClass('dirty');
      }
      if (!container.parent().hasClass('dirty')) {
        container.parent().addClass('dirty');
      }
      propEd.data[datafield] = editor.val();
      propEd.dirty = true;
      viewer.text(trmIDtoLabel[editor.val()]);
  });
  if (value) {
    viewer.text(trmIDtoLabel[value]?trmIDtoLabel[value]:'');
  }
  container.append(viewer);
  container.append(editor);
};

/**
* template for single text editor
*
* @param container
* @param prefix
* @param id
* @param label
* @param datafield
* @param value
*/

EDITORS.createInputFieldVE = function a(container,prefix,id,label,datafield,value) {
  var editor = $('<input type="text" id="'+prefix+id+datafield+'" class="editor"/>'),
  viewer = $('<div id="'+prefix+id+datafield+'" class="viewer"/>'),
  editDiv = $(container.propEd.editDiv),
  propEd = container.propEd;
  editor.jqxInput({placeHolder: "Enter "+(label?label+" ":'')+"value:"});
  editor.val(value?value:'');
  editor.unbind('change').bind('change',
    function(e) {
      if (!editDiv.hasClass('dirty')) {
        editDiv.addClass('dirty');
      }
      if (!container.parent().hasClass('dirty')) {
        container.parent().addClass('dirty');
      }
      propEd.data[datafield] = editor.val();
      propEd.dirty = true;
      viewer.text(editor.val());
  });
  viewer.text(value?value:'');
  container.append(viewer);
  container.append(editor);
};

/**
* put your comment there...
*
* @param container
* @param prefix
* @param id
* @param label
* @param datafield
* @param value
*/

EDITORS.createFieldViewer = function(container,prefix,id,datafield,value) {
  var viewer = $('<div id="'+prefix+id+datafield+'" class="viewer"/>');
  viewer.text(value?value:'');
  container.append(viewer);
};

