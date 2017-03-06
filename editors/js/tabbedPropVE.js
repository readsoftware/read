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
* managers propertyManager object
*
* handles properties retreieval, display and update
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

EDITORS.TabbedPropVE =  function(tabPropVECfg) {
  this.config = tabPropVECfg;
  this.id = tabPropVECfg['id'] ? tabPropVECfg['id']: ("" + Math.round(Math.random()*10000));
  this.controlEditor = tabPropVECfg['editor'] ? tabPropVECfg['editor']:null;
  this.dataMgr = tabPropVECfg['dataMgr'] ? tabPropVECfg['dataMgr']:null;
  this.propMgr = tabPropVECfg['propMgr'] ? tabPropVECfg['propMgr']:null;
  this.editDiv = tabPropVECfg['editDiv'] ? tabPropVECfg['editDiv']:null;
  this.init();
  return this;
};
/**
* put your comment there...
*
* @type Object
*/

EDITORS.TabbedPropVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("tabPropVE.init","");
    var tabPropVE = this;
    this.propEditors = {};
    this.propGIDs = [];
    $(this.editDiv).unbind('removed').bind('removed',
                              function (e) {
                                var gid = tabPropVE.propGIDs.splice(e.args.item,1)[0];
                                tabPropVE.removePropertyTab(e, gid);
                              });
    DEBUG.traceExit("tabPropVE.init","");
  },


/**
* put your comment there...
*
* @param gid
*/

  setEntity: function(gid) {
    DEBUG.traceEntry("setEntity");
    this.addPropertyTab(gid);
    DEBUG.traceExit("setEntity");
  },


/**
* put your comment there...
*
*/

  show: function() {
    DEBUG.traceEntry("show");
    $(this.editDiv).show();
    DEBUG.traceExit("show");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getType: function() {
    return "tabPropVE";
  },


/**
* put your comment there...
*
*/

  hide: function() {
    DEBUG.traceEntry("hide");
    $(this.editDiv).hide();
    DEBUG.traceExit("hide");
  },


/**
* put your comment there...
*
* @param gid
*
* @returns true|false
*/

  addPropertyTab: function (gid) {
    var prefix = gid.substring(0,3),
        id = parseInt(gid.match(/\d+/)[0]),
        tabPropVE = this,
        tabText,
        entities = this.dataMgr.entities,
        propDiv;
//    if (this.splitterDiv.jqxSplitter('panels')[0].collapsed ||
//        this.splitterDiv.jqxSplitter('panels')[1].collapsed) {//properties are not showing just return
//      return false;
//    }
    if (entities[prefix] && entities[prefix][id]) {
      tabText = entities[prefix][id].value ? entities[prefix][id].value : gid ;
      propDiv = '<div class="propEditContainer '+gid+'" >Loading '+gid+'</div>';
      if (this.propEditors[gid]) {
        //Todo:  switch to tab
        $(this.editDiv).jqxTabs('ensureVisible',this.propGIDs.indexOf(gid));
        return true;
      }
      if (!this.tablabels) { //empty tabs so recreate
        this.tablabels = $('<ul class="propertiesEntityTab" id="'+this.edID+'unorderedList">');
        $(this.editDiv).append(this.tablabels);
        this.tablabels.append($("<li>"+tabText+"</li>"));
        $(this.editDiv).append($(propDiv));
        $(this.editDiv).jqxTabs({ height: '100%',
                                     showCloseButtons: true,
                                     scrollable:false});
//                                     scrollStep:5,
//                                     scrollAnimationDuration: 20,
//                                     scrollPosition: 'left' });
      } else {
        $(this.editDiv).jqxTabs('addAt',0,tabText,propDiv);
      }
      //create editor
      this.propGIDs.unshift(gid);
      this.propEditors[gid] = new EDITORS.propEditor({ prefix: prefix,
                                                       cbClose: function(){
                                                                  delete propMgr.propEditors[gid]
                                                                },
                                                       id:id,
                                                       dataMgr: this.dataMgr,
                                                       propEditDiv:$(this.editDiv).find('.propEditContainer.'+gid).get(0)});
    }
  },


/**
* put your comment there...
*
* @param object e System event object
* @param gid
*/

  removePropertyTab: function (e,gid) {
    if (this.propEditors && this.propEditors[gid]) {
        delete this.propEditors[gid];
        if (Object.size(this.propEditors) == 0 &&
            this.propMgr && this.propMgr.propVEType &&
            this.propMgr.showVE) {
          this.propMgr.showVE(this.propMgr.propVEType,gid);
        }
    }
    //todo add code to switch to primary Property V if no tabs present
  }

}

