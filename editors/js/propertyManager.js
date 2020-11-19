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
* manages the initialiation of property viewer editors
* and the switching between them
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Managers
*/

var MANAGERS = MANAGERS || {};


/**
* put your comment there...
*
* @type Object
*/

MANAGERS.PropertyManager =  function(propMgrCfg) {
  this.config = propMgrCfg;
  this.id = propMgrCfg['id'] ? propMgrCfg['id']: "" + Math.round(Math.random()*10000);
  this.catID = propMgrCfg['catID'] ? propMgrCfg['catID']:null;
  this.ednID = propMgrCfg['ednID'] ? propMgrCfg['ednID']:null;
  this.controlEditor = propMgrCfg['editor'] ? propMgrCfg['editor']:null;
  this.dataMgr = propMgrCfg['dataMgr'] ? propMgrCfg['dataMgr']:null;
  this.propVEType = propMgrCfg['propVEType'] ? propMgrCfg['propVEType']:null;
  this.propertyMgrDiv = propMgrCfg['propertyMgrDiv'] ? propMgrCfg['propertyMgrDiv']:null;
  this.splitterDiv = propMgrCfg['splitterDiv'] ? propMgrCfg['splitterDiv']:null;
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
MANAGERS.PropertyManager.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("propMgr.init","");
    var propMgr = this, lemmaEdCfg, entPropVECfg, tabPropVECfg;
    if (propMgr.propVEType) {
      propMgr.propVEDiv = $('<div id="'+propMgr.id+'propVEContainer" class="propertyVEContainer '+propMgr.propVEType+' autoScrollY"/>');
      $(propMgr.propertyMgrDiv).append(propMgr.propVEDiv);
      // create a lemmaVE
      if (propMgr.propVEType == "lemmaVE" && propMgr.controlEditor.type == "WordlistVE") {
        lemmaEdCfg = {dataMgr: propMgr.dataMgr,
                      propMgr: propMgr,
                      id:propMgr.id,
                      editor: propMgr.controlEditor,
                      editDiv:propMgr.propVEDiv.get(0)
                    };
        if (propMgr.catID) {
          lemmaEdCfg['catID'] = propMgr.catID;
        } else {
          lemmaEdCfg['ednID'] = propMgr.ednID;
        }
        propMgr.lemmaVE = new EDITORS.LemmaVE(lemmaEdCfg);
        propMgr.currentVE = propMgr.lemmaVE;
      } else if (propMgr.propVEType == "entPropVE") {
        entPropVECfg = {dataMgr: propMgr.dataMgr,
                        id:propMgr.id,
                        propMgr: propMgr,
                        ednID: propMgr.ednID,
                        editor: propMgr.controlEditor,
                        contentDiv:propMgr.propVEDiv.get(0)
                      };
        if (propMgr.config.hideSubType) {
          entPropVECfg['hideSubType'] = true;
        }
        if (propMgr.config.hideComponents) {
          entPropVECfg['hideComponents'] = true;
        }
        propMgr.entPropVE = new EDITORS.EntityPropVE(entPropVECfg);
        propMgr.currentVE = propMgr.entPropVE;
      } else if (propMgr.propVEType == "annoVE") {
        annoVECfg = {dataMgr: propMgr.dataMgr,
                     id:propMgr.id,
                     propMgr: propMgr,
                     editor: propMgr.controlEditor,
                     editDiv:propMgr.propVEDiv.get(0)
                   };
        propMgr.annoVE = new EDITORS.AnnoVE(annoVECfg);
        propMgr.currentVE = propMgr.annoVE;
      } else if (propMgr.propVEType == "tagVE") {
        tagVECfg = {dataMgr: propMgr.dataMgr,
                    id:propMgr.id,
                    propMgr: propMgr,
                    editor: propMgr.controlEditor,
                    editDiv:propMgr.propVEDiv.get(0)
                  };
        propMgr.tagVE = new EDITORS.TagVE(tagVECfg);
        propMgr.currentVE = propMgr.tagVE;
      }
    }
    // always make tabbed prop VE
    propMgr.propertiesDiv = $('<div id="'+propMgr.id+'propContainer" class="propertyContainer"/>');
    $(propMgr.propertyMgrDiv).append(propMgr.propertiesDiv);
    tabPropVECfg = {dataMgr: propMgr.dataMgr,
                    propMgr: propMgr,
                    id:propMgr.id,
                    editor: propMgr.controlEditor,
                    editDiv:propMgr.propertiesDiv.get(0)
                  };
    propMgr.tabPropVE = new EDITORS.TabbedPropVE(tabPropVECfg);
    if (!propMgr.currentVE) {
      propMgr.currentVE = propMgr.tabPropVE;
    }
    DEBUG.traceExit("propMgr.init","");
  },


/**
* put your comment there...
*
* @param showProps
*/

  displayProperties: function (showProps) {
    if (showProps) {
      this.splitterDiv.jqxSplitter('expand');
      this.splitterDiv.jqxSplitter({ showSplitBar: true });
    } else {
      this.splitterDiv.jqxSplitter('collapse');
      this.splitterDiv.jqxSplitter({ showSplitBar: false });
    }
  },

/**
* call current property editors setEntity method
*
*/

  setEntity: function (gid) {
    if (gid && this.currentVE && this.currentVE.setEntity) {
      this.currentVE.setEntity(gid);
    }
  },


/**
* call current property editors clear method
*
*/

  clearVE: function () {
    if (this.currentVE && this.currentVE.clear) {
      this.currentVE.clear();
    }
  },


/**
* put your comment there...
*
*/

  entityUpdated: function (entTag) {
    if (this.currentVE && this.currentVE.afterUpdate) {
      this.currentVE.afterUpdate(entTag);
    }
  },


/**
* showVE creates if need a subordinate viewer/editor or propVE of selected type
*
* @param propVEType
* @param gid
*/

  showVE: function (propVEType,gid,cfg) {
    DEBUG.traceEntry("showVE");
    var propMgr = this;
    // check if this is call to switch to properties view of the current entity
    if (!gid && propVEType == "tabPropVE") {
      gid = propMgr.currentVE.tag;
    }
    switch (propVEType) {
      case 'attrVE':
        if (!propMgr.attrVE) {
          propMgr.attrVEDiv = $('<div id="'+propMgr.id+'attrVEContainer" class="attrVEContainer '+propMgr.propVEType+'"/>');
          $(propMgr.propertyMgrDiv).append(propMgr.attrVEDiv);
          attrVECfg = {dataMgr: propMgr.dataMgr,
                        id:propMgr.id,
                        propMgr: propMgr,
                        editor: propMgr.controlEditor,
                        editDiv:propMgr.attrVEDiv.get(0)
                      };
          if (propMgr.currentVE && propMgr.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            attrVECfg['entTag'] = propMgr.currentVE.tag;
            attrVECfg['cbVEType'] = propMgr.currentVE.getType();
          }
          propMgr.attrVE = new EDITORS.AttrVE(attrVECfg);
        }
        if (propMgr.attrVE && propMgr.attrVE.setEntity && propMgr.attrVE.show) {
          if (propMgr.currentVE != propMgr.attrVE) {
            if (propMgr.currentVE) {
              propMgr.currentVE.hide();
              if (propMgr.currentVE.tag){
                propMgr.attrVE.entTag = propMgr.currentVE.tag;//set the tagVE scope entity
              }
            }
            propMgr.attrVE.show();
          }
          propMgr.attrVE.setEntity(gid);
        }
        break;
      case 'tagVE':
        if (!propMgr.tagVE) {
          propMgr.tagVEDiv = $('<div id="'+propMgr.id+'tagVEContainer" class="tagVEContainer '+propMgr.propVEType+'"/>');
          $(propMgr.propertyMgrDiv).append(propMgr.tagVEDiv);
          tagVECfg = {dataMgr: propMgr.dataMgr,
                        id:propMgr.id,
                        propMgr: propMgr,
                        editor: propMgr.controlEditor,
                        editDiv:propMgr.tagVEDiv.get(0)
                      };
          if (propMgr.currentVE && propMgr.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            tagVECfg['entTag'] = propMgr.currentVE.tag;
            tagVECfg['cbVEType'] = propMgr.currentVE.getType();
          }
          propMgr.tagVE = new EDITORS.TagVE(tagVECfg);
        }
        if (propMgr.tagVE && propMgr.tagVE.setEntity && propMgr.tagVE.show) {
          if (propMgr.currentVE != propMgr.tagVE) {
            if (propMgr.currentVE) {
              propMgr.currentVE.hide();
              if (propMgr.currentVE.tag){
                propMgr.tagVE.entTag = propMgr.currentVE.tag;//set the tagVE scope entity
              }
            }
            propMgr.tagVE.show();
          }
          propMgr.tagVE.setEntity(gid);
        }
        break;
      case 'imgVE':
        // if the property Manager does not have an instance of the Image VE create one
        if (!propMgr.imgVE) {
          propMgr.imgVEDiv = $('<div id="'+propMgr.id+'imgVEContainer" class="imgVEContainer '+propMgr.propVEType+'"/>');
          $(propMgr.propertyMgrDiv).append(propMgr.imgVEDiv);
          imgVECfg = {dataMgr: propMgr.dataMgr,
                        id:propMgr.id,
                        propMgr: propMgr,
                        editor: propMgr.controlEditor,
                        editDiv:propMgr.imgVEDiv.get(0)
                      };
          if (propMgr.currentVE && propMgr.currentVE.tag) {//capture data to redisplay current editor on exit from imgEditor
            imgVECfg['entTag'] = propMgr.currentVE.tag;
            imgVECfg['cbVEType'] = propMgr.currentVE.getType();
          }
          propMgr.imgVE = new EDITORS.ImgVE(imgVECfg);
        }
        if (propMgr.imgVE && propMgr.imgVE.setEntity && propMgr.imgVE.show) {
          if (propMgr.currentVE != propMgr.imgVE) {
            if (propMgr.currentVE) {
              propMgr.currentVE.hide();
              if (propMgr.currentVE.tag){
                propMgr.imgVE.entTag = propMgr.currentVE.tag;//set the imgVE scope entity
              }
            }
            propMgr.imgVE.show();// ensure imgVE editor is visible
          }
          propMgr.imgVE.setEntity(gid);
        }
        break;
      case 'annoVE':
        if (!propMgr.annoVE) {
          propMgr.annoVEDiv = $('<div id="'+propMgr.id+'annoVEContainer" class="annoVEContainer '+propMgr.propVEType+'"/>');
          $(propMgr.propertyMgrDiv).append(propMgr.annoVEDiv);
          annoVECfg = {dataMgr: propMgr.dataMgr,
                        id:propMgr.id,
                        propMgr: propMgr,
                        editor: propMgr.controlEditor,
                        editDiv:propMgr.annoVEDiv.get(0)
                      };
          if (propMgr.currentVE && propMgr.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            annoVECfg['entTag'] = propMgr.currentVE.tag;
            annoVECfg['cbVEType'] = propMgr.currentVE.getType();
          }
          propMgr.annoVE = new EDITORS.AnnoVE(annoVECfg);
        }
        if (propMgr.annoVE && propMgr.annoVE.setEntity && propMgr.annoVE.show) {
          if (propMgr.currentVE != propMgr.annoVE) {
            if (propMgr.currentVE) {
              propMgr.currentVE.hide();
              if (propMgr.currentVE.tag){
                propMgr.annoVE.entTag = (cfg && cfg['ctxGID']?cfg['ctxGID']:propMgr.currentVE.tag);//set the annoVE scope entity
              }
            }
            if (cfg && cfg.typeIDs) {
              propMgr.annoVE.typeIDs = cfg.typeIDs;
            } else {
              propMgr.annoVE.typeIDs = propMgr.annoVE.config.typeIDs;
            }
            propMgr.annoVE.show();// ensure anno editor is visible
          }
          propMgr.annoVE.setEntity(gid);
        }
        break;
      case 'entPropVE':
        // if the property Manager does not have an instance of the Entity Property VE create one
        if (!propMgr.entPropVE) {
          propMgr.entPropVEDiv = $('<div id="'+propMgr.id+'propVEContainer" class="annoVEContainer '+propMgr.propVEType+'"/>');
          $(propMgr.propertyMgrDiv).append(propMgr.entPropVEDiv);
           entPropVECfg = {dataMgr: propMgr.dataMgr,
                        id:propMgr.id,
                        propMgr: propMgr,
                        ednID: propMgr.ednID,
                        editor: propMgr.controlEditor,
                        contentDiv:propMgr.entPropVEDiv
                      };
          if (propMgr.currentVE && propMgr.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            entPropVECfg['entTag'] = propMgr.currentVE.tag;
            entPropVECfg['cbVEType'] = propMgr.currentVE.getType();
          }
          propMgr.entPropVE = new EDITORS.EntityPropVE(entPropVECfg);
        }
        // if the entPropVE is valid then ensure it shows and sync the view with the entID
        if (propMgr.entPropVE && propMgr.entPropVE.setEntity && propMgr.entPropVE.show) {
          if (propMgr.currentVE && propMgr.currentVE != propMgr.entPropVE) {
            propMgr.currentVE.hide();
            propMgr.currentVE = propMgr.entPropVE;
          }
          propMgr.entPropVE.show();
          if (gid) {
            propMgr.entPropVE.setEntity(gid);
          } else {
            propMgr.entPropVE.setEntity();
          }
        }
        break;
      case 'tabPropVE':
        if (propMgr.tabPropVE && propMgr.tabPropVE.setEntity && propMgr.tabPropVE.show) {
          if (propMgr.currentVE && propMgr.currentVE != propMgr.tabPropVE) {
            propMgr.currentVE.hide();
            propMgr.currentVE = propMgr.tabPropVE;
          }
          propMgr.tabPropVE.show();
          if (gid) {
            propMgr.tabPropVE.setEntity(gid);
          } else {
            propMgr.tabPropVE.setEntity();
          }
        }
        break;
      case 'lemmaVE':
        if (propMgr.lemmaVE && propMgr.lemmaVE.setEntity && propMgr.lemmaVE.show) {
          if (propMgr.currentVE && propMgr.currentVE != propMgr.lemmaVE) {
            propMgr.currentVE.hide();
            propMgr.currentVE = propMgr.lemmaVE;
          }
          propMgr.lemmaVE.show();
          if (gid) {
            propMgr.lemmaVE.setEntity(gid);
          } else {
            propMgr.lemmaVE.setEntity();
          }
        }
        break;
      default:
          if (propMgr.currentVE) {
            propMgr.currentVE.show();
            if (propMgr.currentVE.setEntity) {
              if (gid) {
                propMgr.currentVE.setEntity(gid);
              } else {
                propMgr.currentVE.setEntity();
              }
            }
          } else if (gid && propMgr.tabPropVE &&
                      propMgr.tabPropVE.setEntity && propMgr.tabPropVE.show) {
            propMgr.tabPropVE.show();
            propMgr.tabPropVE.setEntity(gid);
          }
    }
    DEBUG.traceExit("showVE");
  },
}

