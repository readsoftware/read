<?php
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
  * entityEditor
  *
  * creates a spreadsheet for the given entity with all or constrained entities according to the passed
  * parameters in the form of:
  *
  * {"tableprefix":{ //
  *                 "ids":[list of ids or 'all' to indicate all entities], //optional
  *                 "gids":[list of global ids whose data will be returned in a normalized format], //optional
  *  (not implemented yet)               "depth": numeric link level, //optionally used to retrieve linked entities default is 0 or no linked entities,
  *                 "columnName":"equatedValue" //for array fields this will translate to 'any' matching}}
  *
  * when no parameters are passed it creates the default editor with a menu.
  */

  require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../../model/entities/EntityFactory.php');//get user access control
  $dbMgr = new DBManager();

?>
<html>
  <head>
    <meta http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <meta name="author" content="Stephen Whtie">
    <meta http-equiv="Reply-to" content="stephenawhite57@gmail.com">
    <meta name="generator" content="PhpED 6.0">
    <meta name="description" content="Utility entity editor for managing the database.">
    <meta name="keywords" content="">
    <meta name="creation-date" content="06/01/2011">
    <meta name="revisit-after" content="15 days">
    <title>Read Utility Entity Editor</title>
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.energyblue.css" type="text/css" />
    <script type="text/javascript" src="/jquery/jquery-1.11.0.min.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxcore.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxtouch.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxdata.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxbuttons.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxscrollbar.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxmenu.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxwindow.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.js"></script>
    <!--script type="text/javascript" src="/jqwidget/jqwidgets/jqxgriddbug.js"></script-->
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.selection.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.filter.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.sort.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.edit.js"></script>
    <!--script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.editdbug.js"></script-->
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.pager.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.columnsresize.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxgrid.storage.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxdropdownlist.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxlistbox.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxcombobox.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxcheckbox.js"></script>
    <script type="text/javascript" src="/jqwidget/jqwidgets/jqxnumberinput.js"></script>
    <script type="text/javascript" src="<?=SITE_BASE_PATH?>/common/php/getEntityInfo.php?db=<?=DBNAME?>"></script>

    <script type="text/javascript">
      /**
       * Add support for Function.name in IE
      **/
      if (Function.prototype.name === undefined && Object.defineProperty !== undefined) {
          Object.defineProperty(Function.prototype, 'name', {
              get: function() {
                  var funcNameRegex = /function\s([^(]{1,})\(/;
                  var results = (funcNameRegex).exec((this).toString());
                  return (results && results.length > 1) ? results[1].trim() : "";
              },
              set: function(value) {}
          });
      }

      //helper functions for spreadsheets
      var minIDRange = "1",
          maxIDRange = "1000";

      //Set column unit width
      var colUnitWidth = 50;

     // create column configuration row for grid adapter
      var getColumnsConfig = function(prefix,isBlended,isOrdered,showGID,colLimit,noLinks) {
        var i, column;
        var columnDefs;
        columnDefs = [];
        if (!colLimit){//limits the display columns
          colLimit = 99;//set higher than any tables column count
        }

        //add global id field with renderer that reads prefix and pkey and displays the combo for heterogenous fields
        if (isBlended) {
         columnDefs.push({ text: 'GID', menu:false, datafield: 'bld_id', columntype:'button', width:80, sortable:true, pinned:true,
                       editable:true, filterable:true, filtertype: 'textbox', buttonclick: fkClickHandler, cellsrenderer: fkButtonRenderer});
         columnDefs.push({ text: 'PV Pairs', menu:false, datafield: 'bld_properties', columntype:'textbox', sortable:true,
                       editable:false, cellclassname:'readonlycell', filterable:true, filtertype: 'textbox'});
        } else {
          var columns = entityInfo.entities[prefix].columns;
          for (i in columns) {
            if (i >= colLimit) break; //over limit so stop defining columns to stop grid display
            column = columns[i];
            columnDef = { text: column['dname'], datafield: column['name'], pinned: false, menu: false, coltype: column['type']};
            if (termInfo.fkTypeIDs[column['type']]) {
              columnDef['sortable'] = false;
              columnDef['filterable'] = false;
            }
            if (column['nullable']) {
              columnDef['nullable'] = true;
            }
            //set pinned for primary key
            if (column['type'] == termInfo.idByTerm_ParentLabel['Key-TermType'.toLowerCase()]) {//term dependency
              columnDef['pinned'] = true;
              if (showGID) {
                columnDef['text'] = 'GID';
              }
              if (noLinks) {
                columnDef['columntype'] = 'textbox';
              }else{
                columnDef['columntype'] = 'button';
                columnDef['buttonclick'] = fkClickHandler;
              }
//              columnDef['cellsrenderer'] = fkButtonRenderer;
              columnDef['width'] = 60;
  //            columnDef['cellsrenderer'] = pkButtonRenderer;
            }
            //set editable
            if (termInfo.automationTypeIDs[column['type']]) {
              columnDef['editable'] = false;
              columnDef['cellclassname'] = 'readonlycell';
            }
            //set columntype
            if ( !termInfo.fkTypeIDs[column['type']] && !termInfo.uiAssistTypeIDs[column['type']] &&
                    column['type'] != termInfo.idByTerm_ParentLabel['Key-TermType'.toLowerCase()]) {//term dependency
              if (column['type'] == termInfo.idByTerm_ParentLabel['List-Single-TermType'.toLowerCase()]) {//term dependency
                columnDef['columntype'] = 'dropdownlist';
                columnDef['displayField'] = column['dname'];
                columnDef['width'] = 2 * colUnitWidth;
              } else if (column['type'] == termInfo.idByTerm_ParentLabel['List-Multiple-TermType'.toLowerCase()] ||//term dependency
                          column['type'] == termInfo.idByTerm_ParentLabel['List-MultipleOrdered-TermType'.toLowerCase()]) {//term dependency
                columnDef['columntype'] = 'template';
                columnDef['width'] = 4 * colUnitWidth;
              } else if (column['dtype'] == 'int') {
                columnDef['columntype'] = 'number';
                columnDef['width'] = 1 * colUnitWidth;
              } else if (column['dtype'] == 'boolean') {
                columnDef['columntype'] = 'checkbox';
                columnDef['width'] = 1 * colUnitWidth;
              } else {
                columnDef['columntype'] = 'textbox';
                columnDef['width'] = 2 * colUnitWidth;
              }
            } else if ( termInfo.fkTypeIDs[column['type']]) { //Foreign Key Field
              if (noLinks) {
                columnDef['columntype'] = 'textbox';
                columnDef['width'] = 3 * colUnitWidth;
              }else{
                columnDef['columntype'] = 'button';
                columnDef['buttonclick'] = fkClickHandler;
                columnDef['cellsrenderer'] = fkButtonRenderer;
                columnDef['cellclassname'] = 'fkbuttoncell';
                columnDef['width'] = 3 * colUnitWidth;
              }
              columnDef['editable'] = false;
            }
            else {
              if(!columnDef['width']) {
                columnDef['width'] = 2 * colUnitWidth;
              }
            }


            //Set custom column width
            if(column['width']) {
              columnDef['width'] = column['width'] * colUnitWidth;
            }

            if ( !termInfo.fkTypeIDs[column['type']]) {
              if (column['type'] == termInfo.idByTerm_ParentLabel['List-Single-TermType'.toLowerCase()]) {//term dependency
                columnDef['filtertype'] = 'checkedlist';
              } else if (column['type'] == termInfo.idByTerm_ParentLabel['(UI)AssistedDate-TermType'.toLowerCase()] || column['type'] == termInfo.idByTerm_ParentLabel['AutomationDate-TermType'.toLowerCase()]) {//term dependency
                columnDef['filtertype'] = 'date';
              } else if (column['dtype'] == 'int' || column['dtype'] == 'float' || column['type'] == termInfo.idByTerm_ParentLabel['AutomationNumber-TermType'.toLowerCase()]) {//term dependency
                columnDef['filtertype'] = 'number';
              } else if (column['dtype'] == 'boolean') {
                columnDef['filtertype'] = 'checkbox';
              } else {
                columnDef['filtertype'] = 'checkedlist';
              }
            }
            //editor binding
            if (column['type'] == termInfo.idByTerm_ParentLabel['List-Single-TermType'.toLowerCase()]) {//term dependency
              columnDef['createeditor'] = getSelectEditorCreator(prefix,column['name']);
              columnDef['initeditor'] = selectEditorInit;
  //            columnDef['cellvaluechanging'] = cellvaluechanging;
              columnDef['cellsrenderer'] = selectCellRenderer;
            } else if (column['type'] == termInfo.idByTerm_ParentLabel['List-Multiple-TermType'.toLowerCase()] || column['type'] == termInfo.idByTerm_ParentLabel['List-MultipleOrdered-TermType'.toLowerCase()]) {//term dependency
              columnDef['createeditor'] =  getMultiSelectEditorCreator(prefix,column['name']);
              columnDef['initeditor'] = multiSelectEditorInit;
              columnDef['geteditorvalue'] = getMultiIDSelectEditorValue;
              columnDef['cellsrenderer'] = getMultiSelectCellRenderer(prefix);
            }

            //set column group
            if(column['group']) {
              columnDef['columngroup'] = column['group'];
            }

            columnDefs.push(columnDef);
          }//for
        }//else
        //add order field with renderer that inserts order #
        //Put the 'Order' column after the ID column.
        if (isOrdered){
         columnDefs.splice(1,0,{ text: 'Order', menu:false, datafield: 'order', columntype:'numberinput', width:50, sortable:true,
                           editable:true, filterable:false,
                           createeditor: function(row, cellValue, editor, cellText, width, height) {
                                          editor.jqxNumberInput({ decimalDigits: 0, inputMode: 'simple',  width: width, height: height});},
                           initeditor: function (row, cellValue, editor, cellText, width, height) {
                                              editor.jqxNumberInput({ decimal: parseInt(cellValue)});}
         });
        }
        return columnDefs;
      }

      var getColumnGroupsConfig = function(prefix) {
        var columns = entityInfo.entities[prefix].columns;
        var groups = [];
        for(var i in columns) {
          if(columns[i]['group']) {
            var groupName = columns[i]['group'];
            if(groups.indexOf(groupName) < 0) {
              groups.push(groupName);
            }
          }
        }
        var groupsDef = [];
        for(var i in groups) {
          groupsDef.push({
            text: groups[i],
            name: groups[i],
            align: 'center'
          });
        }
        groupsDef.push({
          text: 'NoGroup',
          name: 'NoGroup',
          align: 'center'
        });
        return groupsDef;
      }

      // get datafields configuration for entity grid adapter
      var getDatafieldsConfig = function(prefix,isBlended,isOrdered,showGID) {
        var i, column, datafields = [];
        if (isBlended) {//Blend is a hard coded format which allows viewing heterogenous sets and assumes the service has merged the content
          datafields.push( { name: 'bld_id', type: 'string', map: "0"} );
          datafields.push( { name: 'bld_properties', type: 'string', map: "1"} );
          if (isOrdered){
            datafields.push( { name: 'order', type: 'number', map: "2"} );
          }
        }else{
          //add order field and map to the last column as this is where service will place it
          var columns = entityInfo.entities[prefix].columns;
          for (i in columns) {
            column = columns[i];
            if (column['type'] == termInfo.idByTerm_ParentLabel['List-Single-TermType'.toLowerCase()]) {//term dependency //coded lookup add extra display field
              datafields.push( { name: column['dname'], value: column['name'],
                                 values: {source: getDASelectLookup(prefix,column['name']).records, value: 'trmID', name: 'label' } } );
            }
            datafields.push( { name: column['name'], type: column['dtype'], map: "" + column['ord'] } );
          }
          if (isOrdered){
            datafields.push( { name: 'order', type: 'number', map: "" + columns.length } );
          }
        }
        return datafields;
      }

      // generic callback for fk field click handling
      var fkClickHandler = function(row,event) {
          var ids = event.target.value.replace("{","").replace("}","");
          var colname = this.datafield;
          var prefix = colname.substring(0,3);
          var fkTypes = entityInfo.fkConstraints[prefix]?entityInfo.fkConstraints[prefix][colname]:null;
          var isPKey = (this.text == "ID" || this.text == "GID" || colname.substring(4) == "id");
          if (!fkTypes && isPKey) {
            fkTypes = [(prefix == 'bld' ? ids.substring(0,3):prefix)];
            if (ids.indexOf(':')== 3) {
              ids = ids.substring(4);
            }
          }
          var coltypeinfo = entityInfo.fkConstraints[prefix]?termInfo.fkTypeIDs[entityInfo.fkConstraints[prefix]['typeByColumnName'][colname]]:null;
          if (!coltypeinfo && isPKey) {
            coltypeinfo = {'ho':1,'si':1};
          }
          var dataQuery = "",
              isOrdered = coltypeinfo['ord'],
              isBlended = coltypeinfo['he'],
              showGID = false,
              dataPrefix;
          var rowdata = $('#jqxgrid').jqxGrid('getrowdata', row),
              ctxPrefix = prefix == 'bld' ? rowdata[prefix+'_id'].substring(0,3):prefix,
              ctxPKeyID = prefix == 'bld' ? rowdata[prefix+'_id'].substring(4):rowdata[prefix+'_id'];
              colname = prefix == 'bld' ? colname.replace('bld',ctxPrefix):colname;// for bld should only be id

          // callback for onSelect used with empty field "find"
          var findOnSelect = function(luprefix,addedIDs,addedRowData) {
              var updateRow = row,
                  updateRowData = rowdata;
              if (isBlended ) { //make newID a GID
                //newID = luprefix + ':' + newID;
                addedIDs = $.map(addedIDs,function(item){
                  return luprefix+':'+item;
                });
              }

              var dataUpdateQuery,
                  newID;
              if(termInfo["labelByID"][entityInfo["fkConstraints"][ctxPrefix]["typeByColumnName"][colname]] == "FK-HomogenousSingle") {
                if(addedIDs.length > 0) {
                  newID = addedIDs[addedIDs.length - 1];
                }
                else {
                  newID = 'null';
                }
                dataUpdateQuery = '{"'+ctxPrefix+'":[{"'+ctxPrefix+'_id":'+ctxPKeyID+',"'+colname+'":"'+newID+'"}]}';
              }
              else {
                newID = addedIDs.join();
                dataUpdateQuery = '{"'+ctxPrefix+'":[{"'+ctxPrefix+'_id":'+ctxPKeyID+',"'+colname+'":"{'+newID+'}"}]}';
              }



              $.ajax({
                  dataType: 'json',
                  url: '<?=SITE_BASE_PATH?>/services/saveEntityData.php?db=<?=DBNAME?>',
                  data: 'data='+dataUpdateQuery,
                  success: function (data, status, xhr) {
                      if (Object.size(data)) {
                        var entity = entityInfo.entities[ctxPrefix].name;
                        if (data[entity].columns &&
                            data[entity].records[0] &&
                            data[entity].columns.indexOf(colname) > -1) {
                          savedIDs = data[entity].records[0][data[entity].columns.indexOf(colname)];
                          $("#jqxgrid").jqxGrid('updatebounddata','cells');
                          return;
                        }
                        if (data[entity].errors.length) {
                          alert("An error occurred saving while trying to save to a context record. Error: " + data[entity].errors.join());
                        }
                        if (data[error]) {
                          alert("An error occurred while trying to save to a context record. Error: " + data[error]);
                        }
                      }
                  },// end success cb
                  error: function (xhr,status,error) {
                      // add record failed.
                      alert("An error occurred while trying to save to a context record. Error: " + error);
                  }
              });// end ajax
         }// end lookupOnSelectHandler

          //handle no records case
          if (ids === "find" || ids.length == 0) {
            if (coltypeinfo['ho']) {//handle homogenous case
              //launch "find" lookup to select(entity) id
              //then launch editor with entity and ID and lookup
              showLookup(fkTypes[0],findOnSelect);
            }else if (coltypeinfo['he']) {//handle hetergenous case
              //go to "select" entity with type selections constrained for field, followed by lookup(entity) for id
              //then launch editor with entity and ID and lookup
              getEntityTypePopup(fkTypes, function(ePrefix) {showLookup(ePrefix,findOnSelect);});
            }else if (coltypeinfo['pr']) {
              alert("code for empty key-value pair field not impletemented yet");
              return;
            }else{// error case - should not get here.
              alert("error while trying to launch new entity grid.");
            }
          } else { //existing records case
            if (coltypeinfo['ho']) {//handle homogenous case
              dataPrefix = fkTypes[0];
              jsonIDsString = JSON.stringify(ids.match(/(\d+-\d+)|(\d+)/g));
              dataQuery = '{"'+dataPrefix+'":{"ids":'+jsonIDsString+(isOrdered?',"ordered":1':'')+'}}';
              contextHistory.push({'entity':this.text,
                                   'ctxPrefix' : ctxPrefix,
                                   'dataPrefix' : dataPrefix,
                                   'ctxPKeyID':ctxPKeyID,
                                   'colname':colname,
                                   'ids':ids,
                                   'q':dataQuery});
            }else if (coltypeinfo['he']) {//handle hetergenous case
              showGID = true;
              if (coltypeinfo['si']) {//heterogenous single case
                //separate the prefix from the id and validate
                if (ids.length != 1) {
                  alert("Navigating to record from singular heterogenous field with more than one foreign key. Using first key!");
                }
                // get the first prefix
                dataPrefix = ids.match(/([a-z]{3})/g)[0];
                if (fkTypes.indexOf(dataPrefix) == -1) {
                  alert("Navigating to record from singular heterogenous field with invalid entity type. Aborting!");
                  return;
                }
                //get the first id
                ids = ids.match(/(\d+)/g)[0];
                //construct dataQuery send showGID to signal service to prepend prefix to id
                dataQuery = '{"'+dataPrefix+'":{"ids":["'+ids+'"], "showGID":1}}';
                //set lookup constraint
              } else {//heterogenous multiple case
                //signal blended
                dataPrefix = "bld";
                isBlended = true;
                //process ids
                jsonIDsString = JSON.stringify(ids.match(/([a-z]{3}\:\d+)/g));
                dataQuery = '{"'+dataPrefix+'":{"ids":'+jsonIDsString+',"blended":1'+(isOrdered?',"ordered":1':'')+'}}';
              }

              contextHistory.push({'entity':this.text,
                                   'ctxPrefix' : ctxPrefix,
                                   'dataPrefix' : dataPrefix,
                                   'ctxPKeyID':ctxPKeyID,
                                   'colname':colname,
                                   'ids':ids,
                                   'q':dataQuery});
            }else if (coltypeinfo['pr']) {
              alert(" pr case for grid not impletemented yet");
            }else{// error case - should not get here.
              alert("error while trying to launch new entity grid.");
            }
          }
          $('#new').show();
          $('#clone').show();
          if(isPKey) {
            $('#delete').show().html('Delete').jqxButton({disabled: false });
            $('#save').hide();
            $('#lookup').hide();
          }
          else {
            $('#delete').show().html('Remove').jqxButton({disabled: false });
            $('#save').show();
            $('#lookup').show();
          }

          //Modify query string in URL
          var queryObj = {
            type: 'fky',
            data: ctxPrefix,
            ids: ctxPKeyID,
            col: colname
          };
          addQueryString(queryObj);
          //Add query object to context history
          contextHistory[contextHistory.length-1]['queryObj'] = queryObj;
          var filters = getFiltersInfo();
          if(filters.length > 0) {
            contextHistory[contextHistory.length-2]['filtersObj'] = filters;
          }

          //update contextHistory - previous context gets id and the dataPrefix gets pushed
          setEntityGrid(dataPrefix,dataQuery,isOrdered,isBlended,showGID);
      }

      //show or hide columns based on showConfig object
      var showHideColumnGroups = function(prefix, groupShowConfig) {
        for(var groupName in columnGroupInfo[prefix]) {
          if(groupShowConfig[groupName]) { //if the groupName in showConfig object, show column group
            for(var i in columnGroupInfo[prefix][groupName]) {
              $("#jqxgrid").jqxGrid('showcolumn', columnGroupInfo[prefix][groupName][i]);
            }
          }
          else {
            for(var i in columnGroupInfo[prefix][groupName]) {
              $("#jqxgrid").jqxGrid('hidecolumn', columnGroupInfo[prefix][groupName][i]);
            }
          }
        }
      }

      //Add groups in setting popup
      var addGroupsSetting = function(prefix) {
        if(columnGroupInfo[prefix]) {
          var groupCheckBoxes = [];
          $('#groupsetting').html('<h4>Show column groups:</h4>');
          for(var groupName in columnGroupInfo[prefix]) {
            $('#groupsetting').append('<div id="grpst_ckb_'+groupName+'">'+groupName+'</div>');
            $('#grpst_ckb_'+groupName).jqxCheckBox({ width: 80, height: 25 });
            groupCheckBoxes.push(groupName);
          }
          //Add click handler to 'OK' button in setting popup window
          $('#settingtools_ok').unbind('click').bind('click',{groups:groupCheckBoxes,dataPrefix:prefix},function(event) {
              var groupNames = event.data.groups;
              var groupShowConfig = {};
              for(var i in groupNames) {
                if($('#grpst_ckb_'+groupNames[i]).jqxCheckBox('checked')) {
                  groupShowConfig[groupNames[i]] = 1;
                }
              }
              showHideColumnGroups(event.data.dataPrefix, groupShowConfig);
              $("#settingpopup").jqxWindow('close');
          });

          $('#settingbutton').show();
        }
        else {
          $('#settingbutton').hide();
        }
      }


      // set entity grid
      var setEntityGrid = function(dataPrefix,dataQuery,isOrdered,isBlended,showGID) {
        if (!dataQuery) {
          dataQuery = '{"'+dataPrefix+'":{"ids":["'+minIDRange+'-'+maxIDRange+'"]}}';
          contextHistory.push({'entity':entityInfo.entities[dataPrefix].dname,
                                'ids' :minIDRange+'-'+maxIDRange,
                                'q':dataQuery,
                                'dataPrefix':dataPrefix});
        }
        if ($('#jqxgrid').length == 0) {
          $('#gridcontainer').html('<div id="jqxgrid"></div>');
        }
        if ($('#ctxhist').length == 0) {
          $('body').append($('<div id="ctxhist" style="height: 25px; margin: 5px;"></div>'));
        }
        if ($('#util').length == 0) {
          $('body').append($('<div id="util"></div>'));
        }
        var source ={
          datatype: "json",
          datafields: getDatafieldsConfig(dataPrefix,isBlended,isOrdered,showGID),
          id:dataPrefix + "_id",
          url: '<?=SITE_BASE_PATH?>/services/getEntityData.php',
          root: (isBlended?'blended': entityInfo.entities[dataPrefix].name) +'>records',
          data: {q:dataQuery,db:'<?=DBNAME?>'},
          addrow: function (rowid, rowdata, position, commit) {
            var addPKey, data, addPrefix, addData, props;
            if (isBlended || dataPrefix == 'bld') {// blended recorded being added, unpack the properties
              props = (rowdata['bld_properties']).split('|');
              addPrefix = rowdata['bld_id'].substring(0,3);
              addPKey = addPrefix + '_id';
              var i,field, propname, propval;
              addData = '{"'+addPrefix+'":[{"'+addPKey+'":"new"';
              for (i in props) {
                fieldSplitPos = props[i].indexOf(':');
                propname = props[i].substring(0,fieldSplitPos);
                if ( propname.substring(0,4) != addPrefix + "_") {// when cloning a blended the prefix is stripped need to add it back in
                  propname = addPrefix + "_" + propname.trim();
                }
                propval = props[i].substring(fieldSplitPos+1).trim();
                propval = propval.replace('<b>',"").trim();
                propval = propval.replace('</b>',"").trim();
                if (propname.substring(4) == "scratch") {
                  continue;
                }
                if (propval && (propval != '')) {
                  addData += ',"' + propname + '":"' + propval + '"';
                }
              }
            } else {
              addPrefix = dataPrefix;
              addData ='{"'+addPrefix+'":[{"'+addPrefix+'_id":"new"';
              addPKey = addPrefix + '_id';
              for (colname in rowdata) {
                if (colname == addPKey || colname.substring(0,4) != addPrefix+'_') continue; //skip non record columns and the primary key
                newVal = rowdata[colname];
                oldVal = ((isNaN(rowid) || rowid >= this.originaldata.length)?null : this.originaldata[rowid][colname]);
                if (newVal != oldVal) {
                  addData += ',"' + colname + '":"' + newVal + '"';
                }
              }//{"tok":[{"tok_id":355,"tok_grapheme_ids":"{594,595,596,597,598}"},...]}
            }
            addData += '}]}';
            $.ajax({
                dataType: 'json',
                url: '<?=SITE_BASE_PATH?>/services/saveEntityData.php?db=<?=DBNAME?>',
                data: 'data='+addData,
                success: function (data, status, xhr) {
                    var entity = entityInfo.entities[addPrefix].name;
                    // add record suceeded.
                    if (Object.size(data)) {
                      if (data[entity].columns &&
                          data[entity].records[0] &&
                          data[entity].columns.indexOf(addPrefix + "_id") > -1) {
                        newID = data[entity].records[0][data[entity].columns.indexOf(addPrefix + "_id")];
                        if (isBlended) {
                          newID = addPrefix + ':' + newID;
                        }
                        commit(true,newID);
                        if (isBlended) {//need to directly set the id as there is a bug where framework doesn't set for blended
                          //setTimeout(function() {
                            var rowid = $('#jqxgrid').jqxGrid('getrowboundindexbyid', newID);
                            var source = $("#jqxgrid").jqxGrid('source')
                            if (source.records.length) {
                              source.records[rowid][dataPrefix + "_id"] = newID;
                            }
                            $("#jqxgrid").jqxGrid('refreshdata');
                          //},100);
                        }

                        //Save set to link cloned record to the entity
                        if(contextHistory[contextHistory.length -1]['colname'] && $('#save').is(":visible")) {
                          //check whether it's a foreign key
                          $('#save').trigger('click');
                        }

                        return;
                      }
                      if (data[entity].errors.length) {
                        alert("An error occurred saving while trying to add a record. Error: " + data[entity].errors.join());
                      }
                      if (data[error]) {
                        alert("An error occurred while trying to add a record. Error: " + data[error]);
                      }
                    }
                    commit(false);
                },
                error: function (xhr,status,error) {
                    // add record failed.
                    alert("An error occurred while trying to add a record. Error: " + error);
                    commit(false);
                }
            });
          },
          deleterow: function (rowid, commit) {
            if($('#delete').html() == 'Delete') { //Delete entity
              var pkey = dataPrefix+'_id';
              var rowids = [];
              if(rowid instanceof Array)
              {
                rowids = rowid.slice();
              }
              else {
                rowids.push(rowid);
              }
              var entIds = [];
              for(var i in rowids) {
                var row = $('#jqxgrid').jqxGrid('getrowdatabyid', rowids[i]);
                entIds.push(row[pkey]);
              }
              var queryObj = {};
              queryObj[dataPrefix] = entIds;
              var query = JSON.stringify(queryObj);
              //console.log(query);

              $.ajax({
                dataType: 'json',
                type: "POST",
                url: '<?=SITE_BASE_PATH?>/services/deleteEntity.php?db=<?=DBNAME?>',
                data: 'data='+query,
                success: function (data, status, xhr) {
                    //console.log(data);
                    if(data["success"]) {
                      alert(data["total"] + " records have been successfully deleted.");
                      commit(true);
                    }
                    else {
                      var msg = "Failed to delete " + data["failed"].length + " of " + entIds.length + " records: \n";
                      for(var i in data["failed"]) {
                        msg += data["failed"][i] + " - " + data["errors"][i] + "\n";
                      }
                      alert(msg);
                      commit(false);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert("An error occurs when deleting the record: "+errorThrown);
                    commit(false);
                }
              });
//              alert("success "+rowid);
//              commit(true);
            }
            else { //unlink entity
              commit(true);
            }
          },
          updaterow: function (rowid, rowdata, commit) {
            var colname, oldVal, newVal,
                pkey = dataPrefix+'_id',
                dirty = false,
                key = ((rowid < 0 || rowdata[pkey] == "") ?'new1':rowdata[pkey]),
                data ='{"'+dataPrefix+'":[{"'+pkey+'":"' + key + '"'; // set pkey for record to update
                // todo check for  owner and visibility values. Need to bring in user preferences here.
            for (colname in rowdata) {
              if (colname == pkey || colname.substring(0,4) != dataPrefix+'_') continue; //skip non record columns and the primary key
              newVal = rowdata[colname];
              oldVal = ((isNaN(rowid) || rowid >= this.originaldata.length)?null : this.originaldata[rowid][colname]);
              if ( oldVal === null && !newVal ) continue;
              if (newVal != oldVal) {
                data += ',"' + colname + '":"' + newVal + '"';
                dirty = true;
              }
            }//{"tok":[{"tok_id":355,"tok_grapheme_ids":"{594,595,596,597,598}"},
             //        {"tok_id":"new14","tok_grapheme_ids":"{595,596,599,600,601}"}]}
            data += '}]}';
            if (!dirty) {
              commit(true);
              return;
            }
            $.ajax({
                dataType: 'json',
                url: '<?=SITE_BASE_PATH?>/services/saveEntityData.php?db=<?=DBNAME?>',
                data: 'data='+data,
                success: function (data, status, xhr) {
                    // update command is executed.
                    commit(true);
                },
                error: function () {
                    // cancel changes.
                    commit(false);
                }
            });
          },
        };

        var dataAdapter = new $.jqx.dataAdapter(source,{autobind: true});
        //show context menu
        renderEntityCtxMenu(dataPrefix);
        $("#jqxgrid").jqxGrid('clear');
        $("#jqxgrid").jqxGrid('selectedcell',null);


        var bcData = {
          ordered: isOrdered,
          prefix: dataPrefix
        };

        $("#jqxgrid").unbind('bindingcomplete').bind('bindingcomplete',bcData, function(event)
        {
          //Set default sort by 'Order' for ordered fields
          if(event.data.ordered) {
            $("#jqxgrid").jqxGrid('sortby', 'order', 'asc');
          }
          //Hide all column groups by default
          showHideColumnGroups(event.data.prefix,{});
          //restore previous state
//          if(contextHistory) {
//            if(contextHistory.length > 0 && contextHistory[contextHistory.length-1]['ctxState']) {
//              $("#jqxgrid").jqxGrid('loadstate', contextHistory[contextHistory.length-1]['ctxState']);
//            }
//          }
          //Apply filters from query string
          contextHistory[contextHistory.length-1]['filterLock'] = true; //Use a lock to prevent on filter event clear all filters
          applyFiltersFromUrl();
          contextHistory[contextHistory.length-1]['filterLock'] = false;
        });


        //When column resized, refresh cell data (This will re-render the button size when a column is resized)
        $("#jqxgrid").on("columnresized", function (event) {
          var position = $('#jqxgrid').jqxGrid('scrollposition');
          $('#jqxgrid').jqxGrid('refreshdata');
          $('#jqxgrid').jqxGrid('scrolloffset', position.top, position.left);
        });

        //Add group settings
        addGroupsSetting(dataPrefix);

        //Add filter event to append filters as query string
        $("#jqxgrid").on("filter", function (event) {
          if(!contextHistory[contextHistory.length-1]['filterLock']) {
            addFiltersToUrl(getFiltersInfo());
          }
        });

        try {
        $("#jqxgrid").jqxGrid({
          source: dataAdapter,
          theme: 'energyblue',
          width: "100%",
          height: 750,
          columngroups: getColumnGroupsConfig(dataPrefix),
          altrows: true,
          altstep: 4,
          showtoolbar: true,
          rendertoolbar: toolbarRenderer,
          renderstatusbar: renderStatusBar,
          selectionmode: "singlecell",
          columnsresize: true,
          sortable: true,
          editable: true,
          editmode: 'selectedrow', // allows multiple changes to fields and calls updaterow when focus changes to another row
          filterable: true,
          showfilterrow: true,
          columns: getColumnsConfig(dataPrefix,isBlended,isOrdered,showGID)
          });
        } catch(e) {
        }
        //mark grid as no selected row. Tried to unselect and clearselection but this doesn't clear the selected cell data so no way to tell unselected state.

      }

      var fkButtonRenderer = function (row, columnfield, value, defaulthtml, columnproperties, rowdata) {
          return (value && value != "{}") ? (""+ value).replace("{","").replace("}","") : 'find';
      }

      var contextHistory = [];
      var toolbarRenderer = function (toolbar) {
        toolbar.append($('#ctxhist'));
        //use contextHistory to create a link trail for the user navigation
        renderHistory();
      }

      var resetToHome = function () {
        //clear query string in URL
        addQueryString({});

        $("#jqxgrid").jqxGrid('destroy');
        $('#new').hide();
        $('#clone').hide();
        $('#delete').hide();
        $('#save').hide();
        $('#lookup').hide();
        $('#relatedmenu').html('');
      }

      var renderHistory = function () {
        $('#ctxhist').html('');
        var histHTML = '<a href=# id="home" onclick="resetToHome();">Home</a>';
        for (var i=0; i < contextHistory.length; i++) {
          var crumb = contextHistory[i];
          if (i) {
            histHTML += '->';
          }
          histHTML += '<a href=# id="'+i+'" onclick="historyClickHandler(this);"'+
                        (crumb.ctxPrefix?' ctxPrefix="'+crumb.ctxPrefix+'"':'')+
                        (crumb.dataPrefix?' dataPrefix="'+crumb.dataPrefix+'"':'')+
                        '" q='+"'"+crumb.q+"' >"+crumb.entity;
          if (crumb.ctxFiltered) {
            histHTML += '(filtered)';
          }else if (crumb.ctxPKeyID) {
            histHTML += '('+crumb.ctxPrefix+':'+crumb.ctxPKeyID+')';
          }else if (crumb.ids) {
            histHTML += '('+crumb.ids+')';
          }
          histHTML += '</a>';
        }
        $('#ctxhist').html(histHTML);
      }

      var rendererToolbar = function () {
        $("#mainmenu").jqxMenu({ source: mainMenuSource,
                                 width: '925px',
                                 height: '30px',
                                 animationHideDuration : 50,
                                 animationHideDelay : 0,
                                 animationShowDelay: 0,
                                 animationShowDuration: 50});
        $("#mainmenu").on('itemclick', menuClickHandler);
        //Hide menu popup elements on initialization which will solve the scrollbar issues
        //when have a long sub menu.
        $(".jqx-menu-popup").hide();
      }

      var getEntityTypePopup = function (prefixes,callback) {
        if (!prefixes || prefixes.constructor.name != "Array" || prefixes.length == 0) return null;
        if (prefixes.length == 1) {
          callback(prefixes[0]);
          return;
        }
        //create source for list
        var source = [];
        $.each(prefixes, function (index,prefix) {// make an entry for each
          source.push({label : entityInfo.entities[prefix].dname,
                       value : prefix});
        });
        var listHeight = 26 * prefixes.length;
        $("<div id='entityTypePopupWin'><div id='popupEntityTypeList'></div></div>").jqxWindow({ title:"Select entity type",
            content: '<div id="popupEntityTypeList"></div><input id="Cancel" type="button" value="Cancel" />',
            isModal:true,autoOpen: false, height: (listHeight + 40) + "px", maxHeight: (listHeight+40) + "px", width: '100%', theme: 'energyblue' });
        $("#popupEntityTypeList").jqxListBox({ source: source, height: listHeight});
        $('#entityTypePopupWin').on('close', function (event) {$("#entityTypePopupWin").jqxWindow('destroy');});
        $("#popupEntityTypeList").on('select', function (event) {
          if (event.args) {
            callback(event.args.item.value);
            $("#entityTypePopupWin").jqxWindow('close');
          }
        });
        $("#entityTypePopupWin").jqxWindow('open');
      }

      var showLookup = function(luprefix, cbOnSelect) {
        var constraint = 'all';  //todo determine appropriate constraints
        var source ={
          datatype: "json",
          asynch: false,
          root: entityInfo.entities[luprefix].name +'>records',
          datafields: getDatafieldsConfig(luprefix),
          url: '<?=SITE_BASE_PATH?>/services/getEntityData.php',
          data: {q:'{"'+luprefix+'":{"ids":["'+constraint+'"]}}',db:'<?=DBNAME?>'},
          prefix : luprefix
        };

        var dataAdapter = new $.jqx.dataAdapter(source,{
                autobind: true,
                loadError: function(jqXHR, status, error) {
                  alert ( "unable to load "+ luprefix + " status = " + status);
                },
        });

        $("#util").html('<div id="popupWin"><div>Header</div><div style="padding:0px;">Content</div></div>');
        $("#popupWin ").jqxWindow({ title:entityInfo.entities[luprefix].name, content: '<div id="popupGrid"></div><div style="text-align:right;margin-top:10px;padding:5px;width:95%;"><input type="button" value="Add" id="lookupAdd" /></div>',
                                    isModal:true, height:600, width: 1250, theme: 'energyblue' });

        //Calculate height and width of the popup grid.
        var popupGridHeight = 600 - $("#popupGrid").prev().outerHeight()-100;
        var popupGridWidth = $("#popupGrid").parent().width()-2;

        $("#popupGrid").jqxGrid({
          source: dataAdapter,
          theme: 'energyblue',
          width: popupGridWidth,
          height: popupGridHeight,
          altrows: true,
          columnsresize: true,
          sortable: false,
          editable: false,
          filterable: false,
          selectionmode: 'multiplerows',
          columns: getColumnsConfig(luprefix,false,false,false,4,true)
        });

        //get rid of the space between window and list.
        $("#popupGrid").css("padding", "0px");

        //Initialise 'Add' button for lookup popup
        $("#lookupAdd").jqxButton({height: '30'});

        $("#popupWin").on('close', function (event) {
          $("#popupGrid").jqxGrid('destroy');
          $("#popupWin ").jqxWindow('destroy');
        });
        //Bind click event for 'Add' button
        $('#lookupAdd').bind('click', function (event) {
          var addedIDs = [];
          var addedRowData = [];
          var selectedRowIndexes = $('#popupGrid').jqxGrid('selectedrowindexes');
          if ( selectedRowIndexes ) {// get the selected ids
            for(var i in selectedRowIndexes) {
              var rowdata = $('#popupGrid').jqxGrid('getrowdata', selectedRowIndexes[i]);
              addedIDs.push(rowdata[luprefix + '_id']);
              addedRowData.push(rowdata);
            }
          }
          cbOnSelect(luprefix, addedIDs, addedRowData);
          //release the popup window and grid
          $("#popupWin").jqxWindow('close');
        }); // end lookupAdd click
      }// end showLookup

      // Create the button menu with buttons for each FK column excluding owner and visibility
      var renderEntityCtxMenu = function (dataPrefix) {
          // get entity context menu container
          var entityCtxMenu = $('#relatedmenu');
          // clear previous menu
          entityCtxMenu.html('');
          //if blended prefix then nothing to do but clear
          if (dataPrefix == 'bld') return;

          //create first button for current entity -- last entry in history
          var chIndex = contextHistory.length -1;
          var contx = contextHistory[chIndex]; // get last history element
          //find each FK Field type and columnName
          var entity = entityInfo.entities[dataPrefix];
          var ctxType = entityInfo.fkConstraints[dataPrefix].typeByColumnName[contx.colname];
          entityCtxMenu.append($('<input style="margin-left: 10px; margin-top: 20px;" type="button" value="'+contx.entity+'" id="ctxMain" />'));
          $('#ctxMain').jqxButton({  height: 30 });
          $('#ctxMain').unbind('click').bind('click',function (event) {// bug causing dual click events work around is to unbind first
            //need to set history back to original context
            // cut history back to original context
            contextHistory = contextHistory.slice(0,1+parseInt(chIndex));
            setEntityGrid(dataPrefix,contx.q,false,false);
            renderHistory();
          });
          var i,fktype,col;
          // for each field create button element and add
          // appropriate handler
          for (i in entity.columns) {
            col = entity.columns[i];
            if (termInfo.fkTypeIDs[col.type] &&
                  col.name != dataPrefix + "_id" &&
                  col.name != dataPrefix + "_visibility_ids" &&
                  col.name != dataPrefix + "_owner_id") {
                entityCtxMenu.append($('<input style="margin-left: 10px; margin-top: 20px;" type="button" value="'+col.dname+'" id="'+col.name+'" />'));
                $('#'+col.name).jqxButton({  height: 30 });
                $('#'+col.name).unbind('click').bind('click',ctxMenuClickHandler);
            }
          }
      }

      var ctxMenuClickHandler = function(event) {
          var aggColName = this.id;
          var chIndex = contextHistory.length -1;
          //Add filterd status to the current context histroy
          var fltInfo = $('#jqxgrid').jqxGrid('getfilterinformation');
          if(fltInfo.length > 0) {
            contextHistory[chIndex]['ctxFiltered'] = 1
          } else {
            contextHistory[chIndex]['ctxFiltered'] = 0
          }
          //save the state of current gird
          contextHistory[chIndex]['ctxState'] = $("#jqxgrid").jqxGrid('savestate');

          // get last history element
          var contx = contextHistory[chIndex];
          var srcPrefix = aggColName.substring(0,3);
          var fkTypes = entityInfo.fkConstraints[srcPrefix][aggColName];
          var coltypeinfo = termInfo.fkTypeIDs[entityInfo.fkConstraints[srcPrefix]['typeByColumnName'][aggColName]];
          if (!coltypeinfo) {
            coltypeinfo = {'ho':1,'si':1};
          }
          var dataQuery = "",
              dataPrefix,
              isOrdered = coltypeinfo['ord'],
              isBlended = false,
              showGID = false,
              isPKeyCol = (contx.colname && contx.colname.substring(4) == 'id'),
              srcIDs = contx.ids,
              jsonIDsString;

          if (coltypeinfo['he']) {//handle hetergenous case
            dataPrefix = 'bld';
            showGID = true;
            isBlended = true;
          } else if (coltypeinfo['ho']) {
            dataPrefix = fkTypes[0];
          } else if (coltypeinfo['pr']) {
            alert(" pr case for ctx Menu not impletemented yet");
          } else {// error case - should not get here.
            alert("error while trying to handle ctxMenu click.");
          }

          //Get filtered rows ids so when in context grid, it will show only filtered results
          var rows = $('#jqxgrid').jqxGrid('getrows');
          var filteredIDs = new Array();
          for(var i in rows) {
            filteredIDs.push(rows[i][srcPrefix+"_id"]);
          }

          jsonIDsString = JSON.stringify(filteredIDs.join().match(/([a-z]{3}\:\d+)/g));
          if (jsonIDsString == 'null') {
            jsonIDsString = JSON.stringify(filteredIDs.join().match(/(\d+-\d+)|(\d+)/g));
          }
          dataQuery = '{"'+srcPrefix+'":{"ids":'+jsonIDsString+', '+
                        '"aggregate":1, "aggcol":"'+aggColName+'", '+
                        '"aggprefix":"'+dataPrefix+'"}}';

          contextHistory.push({'entity':this.value,
                               'ctxPrefix' : srcPrefix,
                               'dataPrefix' : dataPrefix,
                               'ctxPKeyID':"aggr",
                               'colname':aggColName,
                               'ids':srcIDs,
                               'q':dataQuery});
          $('#new').show();
          $('#clone').show();
          $('#delete').show().html('Delete').jqxButton({disabled: false });
          $('#save').hide();
          $('#lookup').hide();
          //update contextHistory - previous context gets id and the dataPrefix gets pushed
          //Updated by YL: the aggregated foreign key records may not need order column since it doesn't make sense.

          //Modify query string in URL
          var filterInfo = $("#jqxgrid").jqxGrid('getfilterinformation');
          var queryObj = {
            type: 'tra',
            data: srcPrefix,
            ids: filterInfo.length?filteredIDs.join():srcIDs,
            col: aggColName
          };
          addQueryString(queryObj);

          //Add query object to context history
          contextHistory[contextHistory.length-1]['queryObj'] = queryObj;
          var filters = getFiltersInfo();
          if(filters.length > 0) {
            contextHistory[contextHistory.length-2]['filtersObj'] = filters;
          }

          setEntityGrid(dataPrefix,dataQuery,false,isBlended,showGID);
      }

      var renderStatusBar = function (statusbar) {
          // appends statusBar container to the status bar.
          statusbar.append($('statusbar'));
          $('#new').jqxButton({  width: 60, height: 20 });
          $('#clone').jqxButton({  width: 60, height: 20 });
          $('#delete').jqxButton({  width: 65, height: 20 });
          $('#save').jqxButton({  width: 80, height: 20 });
          $('#lookup').jqxButton({  width: 50, height: 20 });
          // add new row.
          $('#new').unbind('click').bind('click',function (event) {// bug causing dual click events work around is to unbind first
              var rowscount = $("#jqxgrid").jqxGrid('getdatainformation').rowscount;
              var contx = contextHistory[contextHistory.length -1]; // get last history element
              //check for context and find fkConstraints otherwise constrain to dataPrefix
              if (contx.ctxPrefix) {
                //find constraints
                var coltypeinfo = termInfo.fkTypeIDs[entityInfo.fkConstraints[contx.ctxPrefix]['typeByColumnName'][contx.colname]];
                var fktypes = entityInfo.fkConstraints[contx.ctxPrefix][contx.colname];
                getEntityTypePopup(fktypes, function(selectedPrefix) {
                  var newRowData = {};
                  if (contx.dataPrefix == 'bld') { //blended create new blended
                    newRowData["bld_id"] = selectedPrefix + ":new";
                    newRowData["bld_properties"] = selectedPrefix + "_owner_id:2|"+
                                                   selectedPrefix + "_visibility_ids:{2}";
                  } else { // normal new record
                    if (selectedPrefix !== 'ugr') {
                      newRowData[selectedPrefix + "_owner_id"] = 2;
                      newRowData[selectedPrefix + "_visibility_ids"] = '{2}';
                    }
                    else {
                      //Add default user name
                      newRowData[selectedPrefix + "_name"] = 'New User';
                    }
                  }
                  if (coltypeinfo['ord']) {
                    var rowscount = $("#jqxgrid").jqxGrid('getdatainformation').rowscount;
                    newRowData["order"] = rowscount +1; // todo check for is ordered
                  }
                  $("#jqxgrid").jqxGrid('addrow', null, newRowData, 'last');
                });
              } else if (contx.dataPrefix) {
                var newRowData = {};
                if (contx.dataPrefix !== 'ugr') {
                  newRowData[contx.dataPrefix + "_owner_id"] = 2;
                  newRowData[contx.dataPrefix + "_visibility_ids"] = '{2}';
                }
                else {
                  newRowData[contx.dataPrefix + "_name"] = 'New User';
                }
                $("#jqxgrid").jqxGrid('addrow', null, newRowData, 'last');
              } else {
                alert("New - unable to determine entity type to create.");
              }
              return false;
          });
          // add a copy of the selected row.
          $('#clone').unbind('click').bind('click',function (event) {// bug causing dual click events work around is to unbind first
              var contx = contextHistory[contextHistory.length -1]; // get last history element
              var selectedCell = $("#jqxgrid").jqxGrid('selectedcell');
              if ( !selectedCell || selectedCell.rowindex == -1 ) { // no row selected
               alert("Please select a row first and then press clone.");
              }
              var rowData = $("#jqxgrid").jqxGrid('getrowdata',selectedCell.rowindex);
              var newRowData = {};
              var colname, pkColname, prefix, blended = false;
              for (colname in rowData) {
                if (colname.substring(4) == "scratch" ||
                    (colname == contx.dataPrefix+'_id' && colname != 'bld_id')) {// primary key and scratch are dropped for clone
                  continue;
                } else if (colname == 'order' && rowData[colname]) {
                  var rowscount = $("#jqxgrid").jqxGrid('getdatainformation').rowscount;
                  newRowData[colname] = rowscount + 1;
                } else if (colname.substring(0,4) == contx.dataPrefix+'_'
                            && rowData[colname]) { // field has a value so copy it to new data
                  newRowData[colname] = rowData[colname];
                }
              }
              $("#jqxgrid").jqxGrid('addrow', null, newRowData, 'last');
              return false;
          });
          // delete selected row or remove from .
          $('#delete').unbind('click').bind('click',function (event) {
              var selectedCell = $("#jqxgrid").jqxGrid('getselectedcell');
              var id = $('#jqxgrid').jqxGrid('getrowid', selectedCell.row);
              $("#jqxgrid").jqxGrid('deleterow', id);
              if($('#delete').html() != 'Delete') { //Save data set when unlink foreign key item
                $('#save').trigger('click');
              }
//              alert("Row "+id+" has been removed from this grid and the set has been saved to the context record.");
              return false;
          });
          // save set to FK field of context record.
          $('#save').unbind('click').bind('click',function (event) {
            //when click first sort data by order column
            $("#jqxgrid").jqxGrid('sortby', 'order', 'asc');
            refreshOrder();
            $('#jqxgrid').jqxGrid('refreshdata');
            // heterogenous needs globalIDs
            var contx = contextHistory[contextHistory.length -1];
            var ctxLastIndex = contextHistory.length - 1,
                contx = contextHistory[ctxLastIndex]; // get last history element
            if (contx.ctxPKeyID) {
              var prefix = contx.ctxPrefix,
                  pkID = contx.ctxPKeyID,
                  pkColname = contx.dataPrefix + "_id",
                  colname = contx.colname,
                  ids = contx.ids,
                  dataQuery = contx.q,
                  newIDs = [],
                  i,id,dataUpdateQuery;
              //find new ids from set
              var rows = $('#jqxgrid').jqxGrid('getdisplayrows');
              for (i=0; i<rows.length; i++) {
                  newIDs.push(rows[i][pkColname]);
              }
              //CHeck if it's multiple or single fk field
              //Single
              if(termInfo["labelByID"][entityInfo["fkConstraints"][prefix]["typeByColumnName"][colname]] == "FK-HomogenousSingle") {
                if(newIDs.length > 0) {
                  newIDs = newIDs[newIDs.length - 1];
                }
                else {
                  newIDs = "null";
                }
                dataUpdateQuery = '{"'+prefix+'":[{"'+prefix+'_id":'+pkID+',"'+colname+'":'+newIDs+'}]}';

                quotedIDs = '"'+ids.split(",").join('","') + '"';
                if(newIDs == 'null') {
                  newIDs = '';
                }
                quotedNewIDs = '"'+newIDs+'"';
                dataQuery = dataQuery.replace(quotedIDs,quotedNewIDs);
              }
              else { //Multiple
                newIDs = newIDs.join();
                dataUpdateQuery = '{"'+prefix+'":[{"'+prefix+'_id":'+pkID+',"'+colname+'":"{'+newIDs+'}"}]}';
                quotedIDs = '"'+ids.split(",").join('","') + '"';
                quotedNewIDs = '"'+newIDs.split(",").join('","') + '"';
                dataQuery = dataQuery.replace(quotedIDs,quotedNewIDs);
              }
              $.ajax({
                  dataType: 'json',
                  url: '<?=SITE_BASE_PATH?>/services/saveEntityData.php?db=<?=DBNAME?>',
                  data: 'data='+dataUpdateQuery,
                  success: function (data, status, xhr) {
                      if (Object.size(data)) {
                        var entity = entityInfo.entities[prefix].name;
                        if (data[entity].columns &&
                            data[entity].records[0] &&
                            data[entity].columns.indexOf(colname) > -1) {
                          savedIDs = data[entity].records[0][data[entity].columns.indexOf(colname)];
                          contextHistory[ctxLastIndex].q = dataQuery;
                          contextHistory[ctxLastIndex].ids = newIDs;
                          renderHistory();
                          //Refresh the order number
                          refreshOrder();
                          alert("Saving links to " + entity + " record ID " + pkID + " : " + savedIDs);
                          //update contextHistory read, modify and overwrite
                          return;
                        }
                        if (data[entity].errors.length) {
                          alert("An error occurred saving while trying to save to a context record. Error: " + data[entity].errors.join());
                        }
                        if (data[error]) {
                          alert("An error occurred while trying to save to a context record. Error: " + data[error]);
                        }
                      }
                  },
                  error: function (xhr,status,error) {
                      // add record failed.
                      alert("An error occurred while trying to save to a context record. Error: " + error);
                  }
                });
              }
              return false;
          });
          // search for a record.
          $('#lookup').unbind('click').bind('click',function (event) {
            //get context information to determine lookup functionality add - add multiple - replace
            var ctxLastIndex = contextHistory.length - 1,
                contx = contextHistory[ctxLastIndex]; // get last history element
            //popup grid lookup onSelect Handler
            var lookupOnSelect = function(luprefix,addedIDs,addedRowData) {
                var ids = contx.ids;
                var dataQuery = contx.q;
                var dataPrefix = contx.dataPrefix;
                var isBlended = (dataQuery.indexOf('blended')>-1 || contx.dataPrefix == 'bld');
                var isOrdered = (dataQuery.indexOf('ordered')>-1);
                var strReplace,dataUpdateQuery,
                    showGID = false;

                //Get select cell's row index
                var selectedCell = $('#jqxgrid').jqxGrid('getselectedcell');
                var selectedRowIndex = selectedCell ? selectedCell['rowindex'] : -1;

                //if (ids.length > 0) {// non empty grid need to append
                  var lastID = '"' +ids.split(',').pop()+ '"';
                  if (isBlended || lastID.indexOf(':')>0) { //make newID a GID
                    addedIDs = $.map(addedIDs, function(n) {
                      return luprefix + ':' + n;
                    });
                    showGID = true;
                  }
                  //strReplace = lastID + ',' + $.map(addedIDs, function(n) {return '"' + n + '"';}).join() + ']';
                  //dataQuery = dataQuery.replace(lastID+']', strReplace);
                  var existingIDs = ids.split(',');
                  strReplace = '';
                  for(i=0; i<existingIDs.length; i++) {
                    if(i > 0) {
                      strReplace += ',';
                    }
                    if(i == selectedRowIndex) {
                      strReplace += $.map(addedIDs, function(n) {return '"' + n + '"';}).join() + ',';
                    }
                    strReplace += '"' + existingIDs[i] + '"';
                  }
                  if(selectedRowIndex < 0) {
                    strReplace += ',' + $.map(addedIDs, function(n) {return '"' + n + '"';}).join();
                  }

                  //single FK
                  if(termInfo["labelByID"][entityInfo["fkConstraints"][contx.ctxPrefix]["typeByColumnName"][contx.colname]] == "FK-HomogenousSingle") {
                    dataQuery = dataQuery.replace('['+ $.map(existingIDs, function(n) {return '"' + n + '"';}).join() + ']', '["'+ addedIDs[addedIDs.length - 1] + '"]');
                  }
                  else { //Multiple FK
                    dataQuery = dataQuery.replace('['+ $.map(existingIDs, function(n) {return '"' + n + '"';}).join() + ']', '['+ strReplace + ']');
                  }
                //}

                var newIDs = [];
                var rows = $('#jqxgrid').jqxGrid('getboundrows');
                for (i=0; i<rows.length; i++) {
                  if(i == selectedRowIndex) {
                    for(j=0; j<addedIDs.length; j++) {
                      newIDs.push(addedIDs[j]);
                    }
                  }
                  newIDs.push(rows[i][dataPrefix + '_id']);
                }
                if(selectedRowIndex < 0 || ids.length < 1) {
                  $.merge(newIDs,addedIDs);
                }

                //single FK
                if(termInfo["labelByID"][entityInfo["fkConstraints"][contx.ctxPrefix]["typeByColumnName"][contx.colname]] == "FK-HomogenousSingle") {
                  if(newIDs.length > 0) {
                    newIDs = newIDs[newIDs.length - 1]
                  }
                  else {
                    newIDs = 'null';
                  }
                  dataUpdateQuery = '{"'+contx.ctxPrefix+'":[{"'+contx.ctxPrefix+'_id":'+contx.ctxPKeyID+',"'+contx.colname+'":"'+newIDs+'"}]}';
                  if(newIDs == 'null') {
                    newIDs = '';
                  }
                }
                else { //Multiple FK
                  newIDs = newIDs.join();
                  dataUpdateQuery = '{"'+contx.ctxPrefix+'":[{"'+contx.ctxPrefix+'_id":'+contx.ctxPKeyID+',"'+contx.colname+'":"{'+newIDs+'}"}]}';
                }

                $.ajax({
                    dataType: 'json',
                    url: '<?=SITE_BASE_PATH?>/services/saveEntityData.php?db=<?=DBNAME?>',
                    data: 'data='+dataUpdateQuery,
                    success: function (data, status, xhr) {
                        if (Object.size(data)) {
                          var entity = entityInfo.entities[contx.ctxPrefix].name;
                          if (data[entity].columns &&
                              data[entity].records[0] &&
                              data[entity].columns.indexOf(contx.colname) > -1) {
                            savedIDs = data[entity].records[0][data[entity].columns.indexOf(contx.colname)];
                            alert("Saving links to " + entity + " record ID " + contx.ctxPKeyID + " : " + savedIDs);
                            contextHistory[ctxLastIndex].q = dataQuery;
                            contextHistory[ctxLastIndex].ids = newIDs;
                            setEntityGrid(dataPrefix,dataQuery,isOrdered,isBlended,showGID);
                            //update contextHistory read, modify and overwrite
                            return;
                          }
                          if (data[entity].errors.length) {
                            alert("An error occurred saving while trying to save to a context record. Error: " + data[entity].errors.join());
                          }
                          if (data[error]) {
                            alert("An error occurred while trying to save to a context record. Error: " + data[error]);
                          }
                        }
                    },// end success cb
                    error: function (xhr,status,error) {
                        // add record failed.
                        alert("An error occurred while trying to save to a context record. Error: " + error);
                        commit(false);
                    }
                });// end ajax
           }// end lookupOnSelectHandler
          //no context at menu level and no lookup
          if (!contx.ctxPrefix) {
            return;
          }
          //get fkConstraints to setup select entity type popup
          var fktypes = entityInfo.fkConstraints[contx.ctxPrefix][contx.colname].sort();
          if (fktypes.length == 1) {
            showLookup(fktypes[0],lookupOnSelect);
          } else {
            getEntityTypePopup(fktypes, function(prefix) {showLookup(prefix,lookupOnSelect);});
          }
        });//lookup click
      }


      // generic callback for menu click handling
      var historyClickHandler = function(link) {
            //get value
            var dataQuery = $(link).attr('q');
            var isBlended = (dataQuery.indexOf('blended')>-1);
            var isOrdered = (dataQuery.indexOf('ordered')>-1);
            var id = $(link).attr('id');
            var dataPrefix = $(link).attr('dataPrefix');
            var contx = contextHistory[parseInt(id)]; // get last history element
            $('#new').show();
            $('#clone').show();
            if (!contx.ctxPKeyID || contx.ctxPKeyID == 'aggr') { //this is a non field context list
              $('#delete').show().html('Delete').jqxButton({disabled: false });
              $('#save').hide();
              $('#lookup').hide();
            } else { // field context list
              $('#delete').show().html('Remove').jqxButton({disabled: false });
              $('#save').show();
              $('#lookup').show();
            }
            // cut history back to click link - no update needed to link we just make the grid match link's data
            var temp = contextHistory.slice(0,1+parseInt(id));
            contextHistory = temp;

            //Apply query string
            if(contextHistory[parseInt(id)]['queryObj']) {
              addQueryString(contextHistory[parseInt(id)]['queryObj']);
            }

            if(contextHistory[parseInt(id)]['filtersObj']) {
              addFiltersToUrl(contextHistory[parseInt(id)]['filtersObj']);
            }

            setEntityGrid(dataPrefix,dataQuery,isOrdered,isBlended);
            renderHistory();
      }

      // generic callback for menu click handling
      var menuClickHandler = function(event) {
            //get value
            var value = $(event.target).attr('item-value');
            if (!value) {
              return;
            }
            //parse prefix
            var dataPrefix = value.substring(0,3);
            var range = value.substring(4);
            var dataQuery = '{"'+dataPrefix+'":{"ids":["'+range+'"]}}';
            // reset context
            contextHistory = [{'entity':entityInfo.entities[dataPrefix].dname,
                                'ids' :range,
                                'q':dataQuery,
                                'dataPrefix':dataPrefix}];
            $('#ctxhist').html('');
            $('#ctxhist').html(entityInfo.entities[dataPrefix].dname+'('+range+')');
            $('#new').show();
            $('#clone').show();
            $('#delete').show().html('Delete').jqxButton({disabled: false });
            $('#save').hide();
            $('#lookup').hide();

            //Modify query string in URL
            var queryObj = {
              type: 'ent',
              data: dataPrefix
            };

            addQueryString(queryObj);

            //Add query object to context history
            contextHistory[contextHistory.length-1]['queryObj'] = queryObj;
            var filters = getFiltersInfo();
            if(filters.length > 0) {
              contextHistory[contextHistory.length-2]['filtersObj'] = filters;
            }

            //call setGrid
            setEntityGrid(dataPrefix,dataQuery,false,false);
      }

      // data adapter for enum fields
      var daSelectLookup = {};
      var getDASelectLookup = function(entity,datafield) {
        if (!daSelectLookup[datafield]) {
          daSelectLookup[datafield] = new $.jqx.dataAdapter({ datatype: "array",
                                                             datafields:
                                                             [
                                                                { name: 'trmID', type: 'number'},
                                                                { name: 'label', type: 'string'}
                                                             ],
                                                           //  localdata: lookups[entity][datafield]['data']},
                                                             localdata: entityInfo.lookups[entity][datafield]},
                                                           { autobind: true});
        }
        return daSelectLookup[datafield];
      };

      var getMultiIDSelectEditorValue = function (row, cellvalue, editor) {
              // return the editor's value.
              return '{'+editor.val()+'}';
      }

      // template for multi select editor creation
      var getMultiSelectEditorCreator = function(entity,datafield) {
        return function(row,value,editor) {
               editor.jqxDropDownList({source:getDASelectLookup(entity,datafield),
                                        checkboxes: true,
                                        displayMember: 'label',
                                        valueMember: 'trmID'});}
      };

      // multi select initialiser
      var multiSelectEditorInit = function (row, cellvalue, editor, celltext, pressedkey) {
          // set the editor's current value. The callback is called each time the editor is displayed.
          var items = editor.jqxDropDownList('getItems');
          editor.jqxDropDownList('uncheckAll');
          if (cellvalue) {
            var values = cellvalue.split(/,\s*/);
            for (var j = 0; j < values.length; j++) {
                for (var i = 0; i < items.length; i++) {
                    if (items[i].value == values[j]) {
                        editor.jqxDropDownList('checkIndex', i);
                    }
                }
            }
          }
      };

      // template for multi select cell renderer
      var  getMultiSelectCellRenderer = function(entity) {
        return function (row,columnfield,value,defaulthtml, colprops) {
                        if (value) {
                          var vals = value.replace("{","").replace("}","")
                          vals = vals.split(',');
                          var ret = "";
                         // lu = lookups[entity][columnfield].data;
                          lu = entityInfo.lookups[entity][columnfield];
                          for(var i=0; i<vals.length;i++) {
                            for(var j=0; j<lu.length; j++) {
                              if (lu[j].trmID == vals[i]){
                                if (i) ret += ",";
                                ret += lu[j].label;
                              }
                            }
                          }
                          return ret;
                        }
                      }
      };

      // template for single select editor
      var getSelectEditorCreator = function(entity,datafield) {
        return  function(row,value,editor) {
          editor.jqxDropDownList({source: getDASelectLookup(entity,datafield),
                                                                    incrementalSearch: false,
                                                                    animationType: 'none',
                                                                    autoOpen: true,
                                                                    displayMember: 'label',
                                                                    valueMember: 'trmID'});
        };
      };

      // select initialiser
      var selectEditorInit = function (row, cellvalue, editor, celltext, pressedkey) {
          // set the editor's current value. The callback is called each time the editor is displayed.
          var items = editor.jqxDropDownList('getItems');
          if (celltext) {
            for (var i = 0; i < items.length; i++) {
                if (items[i].value == celltext) {
                    editor.jqxDropDownList('selectIndex', i);
                }
            }
          }
      };

      // select cell renderer
      var  selectCellRenderer = function (row,columnfield,value,defaulthtml, colprops, bounddata) {
            var val = bounddata[columnfield];
            var prefix = columnfield.substring(0,3);
            var ret = "";
            if (!val) return;
            lu = entityInfo.lookups[prefix][columnfield];
            for(var j=0; j<lu.length; j++) {
              if (lu[j].trmID == val){
                ret = lu[j].label;
                break;
              }
            }
            return ret;
      };

      //Render setting button and dialog
      var renderSetting = function () {
        $('#settingbutton').jqxButton({ width: '16', height: '16'});
        $('#settingbutton').hide();
        $('#settingbutton').on('click', settingClickHandler);

        $("#settingpopup").jqxWindow({
          height:480,
          width: 640,
          autoOpen: false,
          isModal: true,
          theme: 'energyblue',
          okButton: $('#settingtools_ok'),
          initContent: function () {
            $('#settingtools_ok').jqxButton({width: '65px',});
          }
        });
      }

      //Setting button click handler
      var settingClickHandler = function(event) {
        $("#settingpopup").jqxWindow('open');
      }

      //
      var refreshOrder = function() {
        var cols = $("#jqxgrid").jqxGrid("columns");
        if(cols.records[1].text == 'Order') {
          var rows = $("#jqxgrid").jqxGrid('getrows');
          for(var i in rows) {
            rows[i]['OriginRowIndex'] = $('#jqxgrid').jqxGrid('getrowboundindex', i);
          }
          rows.sort(function compare(a, b) {
            return a.order - b.order;
          });
          for(var i in rows) {
            rows[i]['order'] = i;
          }
          for(var i in rows) {
            //var rowIndex = $("#jqxgrid").jqxGrid('getrowboundindex', rows[i]['OriginRowIndex']);
            //var id = $('#jqxgrid').jqxGrid('getrowid', rows[i]['OriginRowIndex']);
            $("#jqxgrid").jqxGrid('setcellvalue', rows[i]['OriginRowIndex'], "order", rows[i]['order']);
          }
        }
      }

      //Parse query strings from url and direct to the corresponding grid
      var parseUrlToGrid = function() {
        var uri = new URI(window.location.href);
        var queryObj = uri.search(true);
        if(queryObj['type'] == 'ent') { //entity
          var ctxPrefix = queryObj['data'];
          if(ctxPrefix) {
            var query = '{"' + ctxPrefix +'":{"ids":["all"]}}';
            contextHistory.push({
              dataPrefix: ctxPrefix,
              entity: entityInfo['entities'][ctxPrefix]['dname'],
              ids: "all",
              q: query
            });

            //Initiate tool buttons
            $('#new').show();
            $('#clone').show();
            $('#delete').show().html('Delete').jqxButton({disabled: false });
            $('#save').hide();
            $('#lookup').hide();

            setEntityGrid(ctxPrefix,query,false,false,false);
          }

        }
        else if(queryObj['type'] == 'fky') { //foreign key
          var ctxPrefix = queryObj['data'];
          var ctxID = queryObj['ids'];
          var col = queryObj['col'];
          if(ctxPrefix && ctxID && col) {
            var isOrder;
            if(entityInfo.fkConstraints[ctxPrefix]['typeByColumnName'][col]) {
              isOrder = termInfo.fkTypeIDs[entityInfo.fkConstraints[ctxPrefix]['typeByColumnName'][col]]['ord']?1:0;
            }
            else {
              isOrder = 0;
            }
            var isBlend;
            if(entityInfo.fkConstraints[ctxPrefix]['typeByColumnName'][col]) {
              isBlend = termInfo.fkTypeIDs[entityInfo.fkConstraints[ctxPrefix]['typeByColumnName'][col]]['he']?1:0;
            }
            else {
              isBlend = 0;
            }
            var showGID = isBlend?1:0;
            var prefix;
            var entityName;
            if(isBlend) {
              prefix = 'bld';
              entityName = getBlendEntityName(ctxPrefix, col);
            }
            else {
              if(entityInfo.fkConstraints[ctxPrefix][col]) {
                prefix = entityInfo.fkConstraints[ctxPrefix][col][0];
              }
              else {
                prefix = ctxPrefix;
              }
              entityName = entityInfo['entities'][prefix]['dname'];
            }
            var result = queryEntityData('{"'+ctxPrefix+'":{"ids":["'+ctxID+'"]}}');
            var fkValue;
            var ids;
            var qids;
            if(result) {
              var key = Object.keys(result)[0];
              if(result[key]['records'].length) {
                var valueIndex = result[key]['columns'].indexOf(col);
                if(valueIndex >= 0) {
                  fkValue = result[key]['records'][0][valueIndex];
                }
              }
            }
            if(fkValue) {
              if(fkValue.indexOf("{") >= 0) {
                ids = fkValue.substring(1,fkValue.length-1);
              }
              else {
                ids = fkValue;
              }
              qids = ids.split(',');
            }
            var dataQueryObj = {};
            dataQueryObj[prefix] = {};
            dataQueryObj[prefix]['ids'] = qids;
            if(isOrder) {
              dataQueryObj[prefix]['ordered'] = 1;
            }
            if(isBlend) {
              dataQueryObj[prefix]['blended'] = 1;
            }
            var dataQuery = JSON.stringify(dataQueryObj);
            contextHistory.push({
              colname: col,
              ctxPKeyID: ctxID,
              ctxPrefix: ctxPrefix,
              dataPrefix: prefix,
              entity: entityName,
              ids: ids,
              q: dataQuery
            });

            //Initiate tool buttons
            $('#new').show();
            $('#clone').show();
            if(col.substring(4) == "id") {
              $('#delete').show().html('Delete').jqxButton({disabled: false });
              $('#save').hide();
              $('#lookup').hide();
            }
            else {
              $('#delete').show().html('Remove').jqxButton({disabled: false });
              $('#save').show();
              $('#lookup').show();
            }

            setEntityGrid(prefix,dataQuery,isOrder,isBlend,showGID);
          }
        }
        else if(queryObj['type'] == 'tra') { //traverse
          var ctxPrefix = queryObj['data'];
          var ctxIDs = queryObj['ids'];
          var col = queryObj['col'];
          if(ctxPrefix && ctxIDs && col) {
            //var isOrder = termInfo.fkTypeIDs[entityInfo.fkConstraints[ctxPrefix]['typeByColumnName'][col]]['ord']?1:0;
            var isOrder = 0;
            var isBlend = termInfo.fkTypeIDs[entityInfo.fkConstraints[ctxPrefix]['typeByColumnName'][col]]['he']?1:0;
            var showGID = isBlend?1:0;
            var prefix;
            var entityName;
            if(isBlend) {
              prefix = 'bld';
              entityName = getBlendEntityName(ctxPrefix, col);
            }
            else {
              prefix = entityInfo.fkConstraints[ctxPrefix][col][0];
              entityName = entityInfo['entities'][prefix]['dname'];
            }

            var ctxIDStrArray = ctxIDs.split(',');

            var dataQueryObj = {};
            dataQueryObj[ctxPrefix] = {};
            dataQueryObj[ctxPrefix]['ids'] = ctxIDStrArray;
            dataQueryObj[ctxPrefix]['aggregate'] = 1;
            dataQueryObj[ctxPrefix]['aggcol'] = col;
            dataQueryObj[ctxPrefix]['aggprefix'] = prefix;
            var dataQuery = JSON.stringify(dataQueryObj);

            contextHistory.push({
              colname: col,
              ctxPKeyID: "aggr",
              ctxPrefix: ctxPrefix,
              dataPrefix: prefix,
              entity: entityName,
              ids: ctxIDs,
              q: dataQuery
            });

            //Initiate tool buttons
            $('#new').show();
            $('#clone').show();
            $('#delete').show().html('Delete').jqxButton({disabled: false });
            $('#save').hide();
            $('#lookup').hide();

            setEntityGrid(prefix,dataQuery,isOrder,isBlend,showGID);
          }
        }

      }

      var getBlendEntityName = function(prefix, colName) {
        var cols = entityInfo['entities'][prefix]['columns'];
        if(cols) {
          for(var i in cols) {
            if(cols[i]['name'] == colName) {
              return cols[i]['dname'];
              break;
            }
          }
        }
      }

      var queryEntityData = function(query) {
        var result;
        $.ajax({
          url:'<?=SITE_BASE_PATH?>/services/getEntityData.php',
          async: false,
          data: {q:query, db:'<?=DBNAME?>'},
          dataType: 'text',
          success:function(data) {
            result = $.parseJSON(data);
          }
        });
        return result;
      }

      var applyFiltersFromUrl = function() {
        var uri = new URI(window.location.href);
        var queryObj = uri.search(true);
        var count = getFilterCountFromUrl(queryObj);
        var filter_or_operator = 1;
        if(count) {
          for(var i=1;i<=count;i++) {
            var filterCol = queryObj['filter'+i];
            var filterType = queryObj['filter_type'+i];
            var filterOp = queryObj['filter_op'+i];
            var filterVal = queryObj['filter_value'+i];

            if(filterType == 'string') {
              var filterGroup = new $.jqx.filter();
              var strVal = filterVal.split(',').map(function(value) {
                return value.substring(1, value.length-1);
              });
              for(var j in strVal) {
                var filter = filterGroup.createfilter('stringfilter', strVal[j], filterOp);
                filterGroup.addfilter(filter_or_operator, filter);
              }
              $("#jqxgrid").jqxGrid('addfilter', filterCol, filterGroup);
            }
            else if(filterType == 'numeric') {
              var filterGroup = new $.jqx.filter();
              filterVal = parseInt(filterVal.substring(1, filterVal.length-1), 10);
              var filter = filterGroup.createfilter('numericfilter', filterVal, filterOp);
              filterGroup.addfilter(filter_or_operator, filter);
              $("#jqxgrid").jqxGrid('addfilter', filterCol, filterGroup);
            }
          }
          $("#jqxgrid").jqxGrid('applyfilters');
        }
      }

      var getFilterCountFromUrl = function(queryObj) {
        var keys = Object.keys(queryObj);
        var count = 0;
        var patt = /filter\d+/;
        for(var i in keys) {
          if(patt.test(keys[i])) {
            count++;
          }
        }
        return count;
      }

      var addQueryString = function(queryObj) {
//        var currentPath = location.pathname;
//        var currentFile = currentPath.substring(currentPath.lastIndexOf("/") + 1);
        var defaultParams = ["db"];
        var uri = new URI(window.location.href);
        var currentQueryObj = uri.search(true);
        var newQueryObj = {};
        var keys = Object.keys(currentQueryObj);
        for(var i in keys) {
          if(defaultParams.indexOf(keys[i]) < 0) {
            delete currentQueryObj[keys[i]];
          }
        }
        $.extend(newQueryObj, currentQueryObj, queryObj)
        uri.removeSearch(keys);
        uri.setSearch(newQueryObj);
        history.pushState(queryObj, '', uri.filename()+uri.search());
      }

      var getFiltersInfo = function() {
        var filterInfo = $("#jqxgrid").jqxGrid('getfilterinformation');
        var filters = [];
        if(filterInfo) {
          for(var i = 0; i < filterInfo.length; i++) {
            //console.log(filterInfo[i]);
            var filterCol = filterInfo[i]['filtercolumn'];
            var filter = filterInfo[i].filter.getfilters();
            //console.log(filter);
            var filterType = '';
            var filterCon = '';
            var filterVal = '';
            for(var j in filter) {
              filterType = filter[j].type.substr(0,filter[j].type.length - 6);
              filterCon = filter[j].condition.toLowerCase();
              filterVal += filterVal==''?'"'+filter[j].value+'"':',"'+filter[j].value+'"';
            }
            filters.push({
              'filterCol': filterCol,
              'filterType': filterType,
              'filterCon': filterCon,
              'filterVal': filterVal
            });
          }
        }
        return filters;
      }

      var addFiltersToUrl = function(filters) {
        //var filterInfo = $("#jqxgrid").jqxGrid('getfilterinformation');

        //console.log(filterInfo[0].filter.getfilters());
        var uri = new URI(window.location.href);
        var currentQueryObj = uri.search(true);
        var keys = Object.keys(currentQueryObj);
        uri.removeSearch(keys);
        var count = getFilterCountFromUrl(currentQueryObj);
        //remove all current filer parameters
        if(count) {
          for(var i = 1; i <= count; i++) {
            if(currentQueryObj['filter'+i]) {
              delete currentQueryObj['filter'+i];
            }
            if(currentQueryObj['filter_type'+i]) {
              delete currentQueryObj['filter_type'+i];
            }
            if(currentQueryObj['filter_op'+i]) {
              delete currentQueryObj['filter_op'+i];
            }
            if(currentQueryObj['filter_value'+i]) {
              delete currentQueryObj['filter_value'+i];
            }
          }
        }

        for(var i = 0; i < filters.length; i++) {
          //console.log(filterInfo[i]);
          currentQueryObj['filter'+(i+1)] = filters[i]['filterCol'];
          currentQueryObj['filter_type'+(i+1)] = filters[i]['filterType'];
          currentQueryObj['filter_op'+(i+1)] = filters[i]['filterCon'];
          currentQueryObj['filter_value'+(i+1)] = filters[i]['filterVal'];
        }

        uri.setSearch(currentQueryObj);
        history.pushState(currentQueryObj, '', uri.filename()+uri.search());
      }

      var URI = function(url) {
        var uri = decodeURIComponent(url);
        var pattern = /.*\?([^?#]+)\#?.*/;

        //Return full uri string
        this.getFullUri = function() {
          return uri;
        }

        //return the query string without the ?
        this.getQueryStr = function() {
          var match = pattern.exec(uri);
          if(match) {
            return match[1];
          }
          else {
            return null;
          }
        }

        //return query string as string with ? or a object with key value pairs.
        this.search = function(flag) {
          if(arguments.length === 0) { //Return query string with ?
            var queryString = '';
            if(this.getQueryStr()) {
              queryString = this.getQueryStr();
            }
            return '?' + queryString;
          }
          else if(arguments.length === 1 && flag === true) { //Return query string as object
            var params = {};
            if(this.getQueryStr()) {
              var pairs = this.getQueryStr().split('&');
              for (var i in pairs) {
                var pair = pairs[i].split('=');
                params[pair[0]] = pair[1];
              }
            }
            return params;
          }
          else {
            return null;
          }
        }

        //Add query strings based on the provided object.
        this.setSearch = function(params) {
          var anchorPattern = /([^#]*)(\#?[^#?]*)/;
          var match = anchorPattern.exec(uri);
          var firstPart='';
          var secondPart='';
          if(match) {
            firstPart = match[1];
            secondPart = match[2];
          }
          var queryString = $.param(params);
          if(!this.getQueryStr()) {
            if(queryString !== "") {
              firstPart += '?';
            }
          }
          else {
            firstPart += '&';
          }
          firstPart += queryString;
          uri = firstPart + secondPart;
        }

        //Provide the keys and remove parameters from uri
        this.removeSearch = function(paramKeys) {
          for(var i in paramKeys) {
            var removePattern = new RegExp('\&?' + paramKeys[i] + '\=[^&#]*');
            uri = uri.replace(removePattern, '');
          }
          if(!this.getQueryStr()) {
            uri = uri.replace('?', '');
          }
        }

        this.filename = function() {
          var lastS = uri.lastIndexOf('/');
          var lastQ = uri.lastIndexOf('?');
          var lastH = uri.lastIndexOf('#');
          var lastP;
          if(lastQ > 0) {
            return uri.substring(lastS+1, lastQ);
          }
          else if(lastH > 0) {
            return uri.substring(lastS+1, lastH);
          }
          else {
            return uri.substr(lastS+1);
          }
        }
      }

      //structures used to create spreadsheets

      $(document).ready(function () {
        rendererToolbar();
        renderSetting();
        parseUrlToGrid();
      });
    </script>
    <style type="text/css">
      .readonlycell  {
        background-color: #efefef;
      }

      .fkbuttoncell .jqx-button {
        text-align: left;
      }
    </style>
  </head>
  <body >
    <div id="settingbutton" style="float: right;margin-right:20px;margin-top:10px"><img src="../images/setting.png" width="16" /></div>
    <div id="toolbar" style='height: 50px; margin: 5px; padding-right: 250px; padding-bottom: 10px;'>
      <div id="mainmenu" style='margin-left: 200px; padding-bottom: 5px;'></div>
      <div id="relatedmenu" style='float: left; padding-top: 5px; padding-bottom: 15px;'></div>
    </div>
    <div style="clear: both;"></div>
    <div id="gridcontainer">
      <div id="jqxgrid"></div>
    </div>
    <div id="ctxhist" style="height: 25px; margin: 5px;"></div>
    <div id="util"></div>
    <div id="statusbar" style='overflow: hidden; position: relative; margin: 5px;'>
      <div id="new" style='float: right; margin-right: 5px; display: none;'>New</div>
      <div id="clone" style='float: right; margin-right: 5px; display: none;'>Clone</div>
      <div id="delete" style='float: right; margin-right: 5px;  display: none;'>Delete</div>
      <div id="save" style='float: right; margin-right: 5px;  display: none;'>Save Order</div>
      <div id="lookup" style='float: right; margin-right: 5px;  display: none;'>Lookup</div>
    </div>
    <div id="settingpopup">
      <div id="settingtitle">Settings</div>
      <div id="settingcontent" style="padding: 15px;">
        <div id="groupsetting">
        </div>
        <div id="settingtools" style="float: right; margin-right: 10px;"><input type="button" id="settingtools_ok" value="OK" /></div>
      </div>
    </div>
  </body>
</html>
