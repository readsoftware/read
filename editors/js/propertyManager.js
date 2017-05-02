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
    if (this.propVEType) {
      this.propVEDiv = $('<div id="'+this.id+'propVEContainer" class="propertyVEContainer '+this.propVEType+' autoScrollY"/>');
      $(this.propertyMgrDiv).append(this.propVEDiv);
      // create a lemmaVE
      if (this.propVEType == "lemmaVE" && this.controlEditor.type == "WordlistVE") {
        lemmaEdCfg = {dataMgr: this.dataMgr,
                      propMgr: this,
                      id:this.id,
                      editor: this.controlEditor,
                      editDiv:this.propVEDiv.get(0)
                    };
        if (this.catID) {
          lemmaEdCfg['catID'] = this.catID;
        } else {
          lemmaEdCfg['ednID'] = this.ednID;
        }
        this.lemmaVE = new EDITORS.LemmaVE(lemmaEdCfg);
        this.currentVE = this.lemmaVE;
      } else if (this.propVEType == "entPropVE") {
        entPropVECfg = {dataMgr: this.dataMgr,
                      id:this.id,
                      propMgr: this,
                      ednID: this.ednID,
                      editor: this.controlEditor,
                      contentDiv:this.propVEDiv.get(0)
                    };
        this.entPropVE = new EDITORS.EntityPropVE(entPropVECfg);
        this.currentVE = this.entPropVE;
      } else if (this.propVEType == "annoVE") {
        annoVECfg = {dataMgr: this.dataMgr,
                      id:this.id,
                      propMgr: this,
                      editor: this.controlEditor,
                      editDiv:this.propVEDiv.get(0)
                    };
        this.annoVE = new EDITORS.AnnoVE(annoVECfg);
        this.currentVE = this.annoVE;
      } else if (this.propVEType == "tagVE") {
        tagVECfg = {dataMgr: this.dataMgr,
                      id:this.id,
                      propMgr: this,
                      editor: this.controlEditor,
                      editDiv:this.propVEDiv.get(0)
                    };
        this.tagVE = new EDITORS.TagVE(tagVECfg);
        this.currentVE = this.tagVE;
      }
    }
    // always make tabbed prop VE
    this.propertiesDiv = $('<div id="'+this.id+'propContainer" class="propertyContainer"/>');
    $(this.propertyMgrDiv).append(this.propertiesDiv);
    tabPropVECfg = {dataMgr: this.dataMgr,
                    propMgr: this,
                    id:this.id,
                    editor: this.controlEditor,
                    editDiv:this.propertiesDiv.get(0)
                  };
    this.tabPropVE = new EDITORS.TabbedPropVE(tabPropVECfg);
    if (!this.currentVE) {
      this.currentVE = this.tabPropVE;
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
* put your comment there...
*
*/

  entityUpdated: function () {
    if (this.currentVE && this.currentVE.afterUpdate) {
      this.currentVE.afterUpdate();
    }
  },


/**
* put your comment there...
*
* @param propVEType
* @param gid
*/

  showVE: function (propVEType,gid) {
    DEBUG.traceEntry("showVE");
    if (!gid && propVEType == "tabPropVE") {
      gid = this.currentVE.tag;
    }
    switch (propVEType) {
      case 'attrVE':
        if (!this.attrVE) {
          this.attrVEDiv = $('<div id="'+this.id+'attrVEContainer" class="attrVEContainer '+this.propVEType+'"/>');
          $(this.propertyMgrDiv).append(this.attrVEDiv);
          attrVECfg = {dataMgr: this.dataMgr,
                        id:this.id,
                        propMgr: this,
                        editor: this.controlEditor,
                        editDiv:this.attrVEDiv.get(0)
                      };
          if (this.currentVE && this.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            attrVECfg['entTag'] = this.currentVE.tag;
            attrVECfg['cbVEType'] = this.currentVE.getType();
          }
          this.attrVE = new EDITORS.AttrVE(attrVECfg);
        }
        if (this.attrVE && this.attrVE.setEntity && this.attrVE.show) {
          if (this.currentVE != this.attrVE) {
            if (this.currentVE) {
              this.currentVE.hide();
              if (this.currentVE.tag){
                this.attrVE.entTag = this.currentVE.tag;//set the tagVE scope entity
              }
            }
            this.attrVE.show();
          }
          this.attrVE.setEntity(gid);
        }
        break;
      case 'tagVE':
        if (!this.tagVE) {
          this.tagVEDiv = $('<div id="'+this.id+'tagVEContainer" class="tagVEContainer '+this.propVEType+'"/>');
          $(this.propertyMgrDiv).append(this.tagVEDiv);
          tagVECfg = {dataMgr: this.dataMgr,
                        id:this.id,
                        propMgr: this,
                        editor: this.controlEditor,
                        editDiv:this.tagVEDiv.get(0)
                      };
          if (this.currentVE && this.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            tagVECfg['entTag'] = this.currentVE.tag;
            tagVECfg['cbVEType'] = this.currentVE.getType();
          }
          this.tagVE = new EDITORS.TagVE(tagVECfg);
        }
        if (this.tagVE && this.tagVE.setEntity && this.tagVE.show) {
          if (this.currentVE != this.tagVE) {
            if (this.currentVE) {
              this.currentVE.hide();
              if (this.currentVE.tag){
                this.tagVE.entTag = this.currentVE.tag;//set the tagVE scope entity
              }
            }
            this.tagVE.show();
          }
          this.tagVE.setEntity(gid);
        }
        break;
      case 'imgVE':
        if (!this.imgVE) {
          this.imgVEDiv = $('<div id="'+this.id+'imgVEContainer" class="imgVEContainer '+this.propVEType+'"/>');
          $(this.propertyMgrDiv).append(this.imgVEDiv);
          imgVECfg = {dataMgr: this.dataMgr,
                        id:this.id,
                        propMgr: this,
                        editor: this.controlEditor,
                        editDiv:this.imgVEDiv.get(0)
                      };
          if (this.currentVE && this.currentVE.tag) {//capture data to redisplay current editor on exit from imgEditor
            imgVECfg['entTag'] = this.currentVE.tag;
            imgVECfg['cbVEType'] = this.currentVE.getType();
          }
          this.imgVE = new EDITORS.ImgVE(imgVECfg);
        }
        if (this.imgVE && this.imgVE.setEntity && this.imgVE.show) {
          if (this.currentVE != this.imgVE) {
            if (this.currentVE) {
              this.currentVE.hide();
              if (this.currentVE.tag){
                this.imgVE.entTag = this.currentVE.tag;//set the imgVE scope entity
              }
            }
            this.imgVE.show();// ensure imgVE editor is visible
          }
          this.imgVE.setEntity(gid);
        }
        break;
      case 'annoVE':
        if (!this.annoVE) {
          this.annoVEDiv = $('<div id="'+this.id+'annoVEContainer" class="annoVEContainer '+this.propVEType+'"/>');
          $(this.propertyMgrDiv).append(this.annoVEDiv);
          annoVECfg = {dataMgr: this.dataMgr,
                        id:this.id,
                        propMgr: this,
                        editor: this.controlEditor,
                        editDiv:this.annoVEDiv.get(0)
                      };
          if (this.currentVE && this.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            annoVECfg['entTag'] = this.currentVE.tag;
            annoVECfg['cbVEType'] = this.currentVE.getType();
          }
          this.annoVE = new EDITORS.AnnoVE(annoVECfg);
        }
        if (this.annoVE && this.annoVE.setEntity && this.annoVE.show) {
          if (this.currentVE != this.annoVE) {
            if (this.currentVE) {
              this.currentVE.hide();
              if (this.currentVE.tag){
                this.annoVE.entTag = this.currentVE.tag;//set the annoVE scope entity
              }
            }
            this.annoVE.show();// ensure anno editor is visible
          }
          this.annoVE.setEntity(gid);
        }
        break;
      case 'entPropVE':
        if (!this.entPropVE) {
          this.entPropVEDiv = $('<div id="'+this.id+'propVEContainer" class="annoVEContainer '+this.propVEType+'"/>');
          $(this.propertyMgrDiv).append(this.entPropVEDiv);
           entPropVECfg = {dataMgr: this.dataMgr,
                        id:this.id,
                        propMgr: this,
                        ednID: this.ednID,
                        editor: this.controlEditor,
                        contentDiv:this.entPropVEDiv
                      };
          if (this.currentVE && this.currentVE.tag) {//capture data to redisplay current editor on exit from annoEditor
            entPropVECfg['entTag'] = this.currentVE.tag;
            entPropVECfg['cbVEType'] = this.currentVE.getType();
          }
          this.entPropVE = new EDITORS.EntityPropVE(entPropVECfg);
        }
        if (this.entPropVE && this.entPropVE.setEntity && this.entPropVE.show) {
          if (this.currentVE && this.currentVE != this.entPropVE) {
            this.currentVE.hide();
            this.currentVE = this.entPropVE;
          }
          this.entPropVE.show();
          if (gid) {
            this.entPropVE.setEntity(gid);
          } else {
            this.entPropVE.setEntity();
          }
        }
        break;
      case 'tabPropVE':
        if (this.tabPropVE && this.tabPropVE.setEntity && this.tabPropVE.show) {
          if (this.currentVE && this.currentVE != this.tabPropVE) {
            this.currentVE.hide();
            this.currentVE = this.tabPropVE;
          }
          this.tabPropVE.show();
          if (gid) {
            this.tabPropVE.setEntity(gid);
          } else {
            this.tabPropVE.setEntity();
          }
        }
        break;
      case 'lemmaVE':
        if (this.lemmaVE && this.lemmaVE.setEntity && this.lemmaVE.show) {
          if (this.currentVE && this.currentVE != this.lemmaVE) {
            this.currentVE.hide();
            this.currentVE = this.lemmaVE;
          }
          this.lemmaVE.show();
          if (gid) {
            this.lemmaVE.setEntity(gid);
          } else {
            this.lemmaVE.setEntity();
          }
        }
        break;
      default:
          if (this.currentVE) {
            this.currentVE.show();
            if (this.currentVE.setEntity) {
              if (gid) {
                this.currentVE.setEntity(gid);
              } else {
                this.currentVE.setEntity();
              }
            }
          } else if (gid && this.tabPropVE &&
                      this.tabPropVE.setEntity && this.tabPropVE.show) {
            this.tabPropVE.show();
            this.tabPropVE.setEntity(gid);
          }
    }
    DEBUG.traceExit("showVE");
  },
}

