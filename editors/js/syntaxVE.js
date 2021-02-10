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
* editors syntaxVE object
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
* Constructor for Syntax Viewer/Editor Object
*
* @type Object
*
* @param syntaxVECfg is a JSON object with the following possible properties
*  "edition" an entity data element which defines the edition and all it's structures.
*
* @returns {SyntaxVE}

*/


EDITORS.SyntaxVE = function(syntaxCfg) {
  //read configuration and set defaults
  this.config = syntaxCfg;
  this.type = "SyntaxVE";
  this.editDiv = syntaxCfg['editDiv'] ? syntaxCfg['editDiv']:null;
  this.dataMgr = syntaxCfg['dataMgr'] ? syntaxCfg['dataMgr']:null;
  this.ednID = (syntaxCfg['ednID'] && this.dataMgr &&
    this.dataMgr.entities.edn[syntaxCfg['ednID']]) ? syntaxCfg['ednID']:null;
  this.layoutMgr = syntaxCfg['layoutMgr'] ? syntaxCfg['layoutMgr']:null;
  this.eventMgr = syntaxCfg['eventMgr'] ? syntaxCfg['eventMgr']:null;
  this.startOffset = syntaxCfg['startOffset'] ? syntaxCfg['startOffset']: {x:20, y:20};
  this.wordSpacing = syntaxCfg['wordSpacing'] ? syntaxCfg['wordSpacing']:10;
  this.id = syntaxCfg['id'] ? syntaxCfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.data = [];
  this.relTypes = [];
  this.relType2Index = null;
  this.linemarkerPrefix = '[';
  this.linemarkerSuffix = ']';
  this.changes = {};
  this.incompChanges = {};
  this.init();
  return this;
};

/**
* Syntax viewer/editor
*
* @type Object
*/
EDITORS.SyntaxVE.prototype = {

  createRelType2Index:  function() {
    var syntaxVE = this, t2id = {};
    if (syntaxVE.relTypes && syntaxVE.relTypes.length) {
      syntaxVE.relTypes.forEach((nodegrp,i) => {
        t2id[nodegrp.type] = i;
      })
    }
    return t2id;
  },


  getRelTypeClass:  function (type) {
    if (!type){
      type = 'unk';
    }
    return type.replace('/','-').replace('?','unk').toLowerCase();
  //return this.relTypes[this.relType2Index[type]].code;
  },

  getRelTypeTermID:  function (type) {
    return this.relTypes[this.relType2Index[type]].id;
  },

  /**
  * initialization funciton to create the html container frame with an expandable lower panel
  * for property editors and contextual/workflow editors.
  * @author Stephen White  <stephenawhite57@gmail.com>
  */

  init: function() {
    var syntaxVE = this, propMgrCfg;
    syntaxVE.splitterDiv = $('<div id="'+syntaxVE.id+'splitter"/>');
    syntaxVE.contentDiv = $('<div id="'+syntaxVE.id+'textContent" class="syntaxContentDiv" spellcheck="false" ondragstart="return false;"/>');
    syntaxVE.propertyMgrDiv = $('<div id="'+syntaxVE.id+'propManager" class="propertyManager"/>');
    syntaxVE.splitterDiv.append(syntaxVE.contentDiv);
    syntaxVE.splitterDiv.append(syntaxVE.propertyMgrDiv);
    $(syntaxVE.editDiv).append(syntaxVE.splitterDiv);
    syntaxVE.splitterDiv.jqxSplitter({ width: '100%',
                              height: '100%',
                              orientation: 'horizontal',
                              splitBarSize: 1,
                              showSplitBar:false,
                              panels: [{ size: '60%', min: '250', collapsible: false},
                                { size: '40%', min: '150', collapsible: true,collapsed : true}] });
    propMgrCfg ={id: syntaxVE.id,
                  propertyMgrDiv: syntaxVE.propertyMgrDiv,
                  editor: syntaxVE,
                  propVEType: "entPropVE",
                  ednID: syntaxVE.ednID,
                  dataMgr: syntaxVE.dataMgr,
                  splitterDiv: syntaxVE.splitterDiv };
    syntaxVE.propMgr = new MANAGERS.PropertyManager(propMgrCfg);
    syntaxVE.displayProperties = syntaxVE.propMgr.displayProperties;
    syntaxVE.splitterDiv.unbind('focusin').bind('focusin',syntaxVE.layoutMgr.focusHandler);
    syntaxVE.createStaticToolbar();
  },

/**
* put your comment there...
*
*/

  createStaticToolbar: function() {
    var syntaxVE = this;
    syntaxVE.viewToolbar = $('<div class="viewtoolbar"/>');
    syntaxVE.editToolbar = $('<div class="edittoolbar"/>');

    var btnShowPropsName = this.id+'showprops',
        ddbtnCurFuncName = this.id+'curtagbutton',
        treeCurFuncName = this.id+'curtagtree';

    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    if (this.relTypes && this.relTypes.length > 0) {
      this.curFuncBtnDiv = $('<div class="toolbuttondiv">' +
        '<div id="'+ ddbtnCurFuncName+'"><div id="'+ treeCurFuncName+'"></div></div>'+
        '<div class="toolbuttonlabel">Current Tag</div>'+
        '</div>');
      this.funcTree = $('#'+treeCurFuncName,this.curFuncBtnDiv);
      this.funcDdBtn = $('#'+ddbtnCurFuncName,this.curFuncBtnDiv);
      this.funcTree.jqxTree({
        source: this.relTypes,
        //       hasThreeStates: false, checkboxes: true,
        width: '200px',
        theme:'energyblue'
      });
      this.funcTree.on('select', function (event) {
        var args = event.args, dropDownContent = '',
        item =  syntaxVE.funcTree.jqxTree('getItem', args.element);
        if (item.value) {
          //save selected tag to edition VE
          syntaxVE.curTagID = item.value.replace(":","");
          syntaxVE.curTagLabel = item.label;
          //display current tag
          dropDownContent = '<div class="listDropdownButton">' + item.label + '</div>';
          syntaxVE.funcDdBtn.jqxDropDownButton('setContent', dropDownContent);
          syntaxVE.funcDdBtn.jqxDropDownButton('close');
        }
      });
      this.funcDdBtn.jqxDropDownButton({width:95, height:28,closeDelay: 50});
    }

    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
      '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
      '" title="Show/Hide property panel">&#x25E8;</button>'+
      '<div class="toolbuttonlabel">Properties</div>'+
      '</div>');
    this.propertyBtn = $('#'+btnShowPropsName,this.propertyBtnDiv);
    this.propertyBtn.unbind('click').bind('click',function(e) {
      syntaxVE.showProperties(!$(this).hasClass("showUI"));
    });
    this.viewToolbar.append(this.propertyBtnDiv);
    this.editToolbar.append(this.curFuncBtnDiv);


    syntaxVE.layoutMgr.registerViewToolbar(syntaxVE.id,syntaxVE.viewToolbar);
    syntaxVE.layoutMgr.registerEditToolbar(syntaxVE.id,syntaxVE.editToolbar);
    syntaxVE.addEventHandlers();
    syntaxVE.loadEditionSyntaxData(syntaxVE.ednID);
  },

/**
* put your comment there...
*
* @param bShow bool indicating whether to show or hide the properties panel
*/

showProperties: function (bShow) {
  var syntaxVE = this;
  if (syntaxVE.propMgr &&
    typeof syntaxVE.propMgr.displayProperties == 'function'){
    syntaxVE.propMgr.displayProperties(bShow);
    if (syntaxVE.propertyBtn.hasClass("showUI") && !bShow) {
      syntaxVE.propertyBtn.removeClass("showUI");
    } else if (!syntaxVE.propertyBtn.hasClass("showUI") && bShow) {
      syntaxVE.propertyBtn.addClass("showUI");
    }
  }
},


/**
*
*/

  addEventHandlers: function() {
    var syntaxVE = this;


    /**
    * put your comment there...
    *
    * @param object e System event object
    * @param senderID
    * @param selectionIDs
    * @param entID
    */

    function updateSelectionHandler(e,senderID, selectionIDs, entID) {
      if (senderID == syntaxVE.id) {
        return;
      }
      var i, id, prefix, url;
      DEBUG.log("event","selection changed recieved by "+syntaxVE.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      if (entID && entID.length && entID.length > 3) {
        prefix = entID.substr(0,3);
        id = entID.substr(3);
        if (( prefix == 'tok' || prefix == 'cmp') && syntaxVE.dictionary ) {
          entity = syntaxVE.dataMgr.getEntity(prefix,id);
          //TODO: Add code and state to indicate scroll to Word.
        }
      }
    };

    $(syntaxVE.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);
  },

  loadEditionSyntaxData: function(ednID, cb = null) {
    var syntaxVE = this;
    $.ajax({
      type: 'POST',
      dataType: 'json',
      url: basepath+'/services/getEditionSyntaxData.php?db='+dbName+'&ednID='+ednID,//caution dependency on context having basepath and dbName
      async: true,
      success: function (data, status, xhr) {
          if (typeof data == 'object' && data.success && data.data && data.syntaxInfo ) {
            //update data
            //syntaxVE.dataMgr.updateLocalCache(data);
            //syntaxVE.removeDirtyMarkers();
            syntaxVE.relTypes = data.syntaxInfo;
            syntaxVE.relType2Index = syntaxVE.createRelType2Index();
            syntaxVE.data = data.data;
            syntaxVE.changes = {};
            syntaxVE.createSyntaxUI();
            syntaxVE.restoreIncompleteChanges();
            syntaxVE.redrawSvgLinks(d3.select(`#${syntaxVE.data.nodegrps[0].id}`));
            if (cb) {
              cb();
            }
          } else if (data.errors) {
            alert("An error occurred while trying to load edition "+ ednID +
                  " syntax data.\nError: " + data.errors.join());
          }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to load syntax information.\nError: " + error);
        }
    });
  },

  enableSave: function(state = true) {
    var syntaxVE = this;
    if (state) {
      syntaxVE.d3cmdSave.attr("disabled",null);
    } else {
      syntaxVE.d3cmdSave.attr("disabled","disabled");
    }
  },

  refresh: function(scrollleft = 0) {
    var syntaxVE = this;
    syntaxVE.contentDiv.html("");
    syntaxVE.loadEditionSyntaxData(syntaxVE.ednID, function(){
              syntaxVE.d3CanvasDiv.node().scrollLeft = scrollleft;
    });
    //todo check which case we need scrolltop
  },

  hasSaveChanges: function() {
    var syntaxVE = this, cnt=0;
    for (wrdGID in syntaxVE.changes) {
      if (!syntaxVE.changes[wrdGID][0].match(/^add\:new(\:|\|\?)/)){
        cnt++;
      }
    }
    return cnt;
  },

  hasIncompleteChanges: function() {
    var syntaxVE = this, cnt=0;
    for (wrdGID in syntaxVE.changes) {
      if (syntaxVE.changes[wrdGID][0].match(/^add\:new(\:|\|\?)/)){
        cnt++;
      }
    }
    return cnt;
  },

  saveIncompleteChanges: function() {
    var syntaxVE = this;
    syntaxVE.incompChanges = {};
    for (wrdGID in syntaxVE.changes) {
      if (syntaxVE.changes[wrdGID][0].match(/^add\:new(\:|\|\?)/)){
        syntaxVE.incompChanges[wrdGID] = syntaxVE.changes[wrdGID];
      }
    }
  },

  restoreIncompleteChanges: function() {
    var syntaxVE = this, wrdTag, iDist, chngParts,
        rels, typ, toTag,
        nodeTag2Index = syntaxVE.data.nodegrps[0].nodeID2Index;

    if (syntaxVE.incompChanges && Object.keys(syntaxVE.incompChanges).length){
      if (!syntaxVE.changes) {
        syntaxVE.changes = {};
      }
      for (wrdTag in syntaxVE.incompChanges) {
        syntaxVE.changes[wrdTag] = syntaxVE.incompChanges[wrdTag];
        chngParts = syntaxVE.incompChanges[wrdTag][0].split(':');
        typ = chngParts[1].split('|').pop();
        toTag = chngParts[2].split('|').pop();
        iDist = Math.abs(nodeTag2Index[wrdTag] - nodeTag2Index[toTag]);
        //create relation list
        if (!syntaxVE.data.nodegrps[0].rels) { //create distance list if none
          syntaxVE.data.nodegrps[0].rels = [];
        }
        rels = syntaxVE.data.nodegrps[0].rels;
        //add relation to list
        if (!rels[iDist]) {
          rels[iDist] = [];
        }
        rels[iDist].push({
          from: wrdTag,
          type: typ,
          to: toTag,
          dirty:1
        });
      }
    }
  },

  getCompleteChanges: function() {
    var syntaxVE = this,
        compChanges = {};
    for (wrdGID in syntaxVE.changes) {
      if (!syntaxVE.changes[wrdGID][0].match(/^add\:new(\:|\|\?)/)){
        compChanges[wrdGID] = syntaxVE.changes[wrdGID];
      }
    }
    return compChanges;
  },

  save: function() {
    var syntaxVE = this,
        savedata = {};
    if (syntaxVE.hasSaveChanges()) {
      DEBUG.log("gen","saving all changes to syntax is under construction");
      //get savedata
      savedata['changes'] = syntaxVE.getCompleteChanges();
      savedata['ednID'] = syntaxVE.ednID;
      $.ajax({
        type: 'POST',
        dataType: 'json',
        data: savedata,
        url: basepath+'/services/saveSyntaxChanges.php?db='+dbName,//caution dependency on context having basepath and dbName
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success) {
              syntaxVE.dataMgr.updateLocalCache(data,null);
              syntaxVE.refresh(syntaxVE.d3CanvasDiv.node().scrollLeft);
            } else if (data.errors) {
              alert("An error occurred while trying to load edition "+ ednID +
                    " syntax data.\nError: " + data.errors.join());
            }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to load syntax information.\nError: " + error);
          }
      });
    } else {
      syntaxVE.enableSave(false);
    }
//TODO save/update links function
  },

  createSyntaxUI: function() {
    var syntaxVE = this,
        d3UxDiv = d3.select(syntaxVE.contentDiv.get(0));

    syntaxVE.d3Title = d3UxDiv.append('h3')
                          .attr("id","texttitle")
                          .attr("class","maintitle")
                          .attr('tabindex', "-1")
                          .text((syntaxVE.data.ref?syntaxVE.data.ref+" ":'') + syntaxVE.data.title);

    syntaxVE.d3cmdRefresh = d3UxDiv.append('button')
                          .attr("id","textrefresh")
                          .attr("class","cmdrefresh")
                          .attr('tabindex', "-1")
                          .text("Refresh")
                          .on('click', function(){
                            let doRefresh = true;
                            if (syntaxVE.hasIncompleteChanges() &&
                                confirm("You are about to refresh which will reload the current edition from the "+
                                    "database. Would you like keep incomplete changes?")) {
                              syntaxVE.saveIncompleteChanges();
                            } else {
                              syntaxVE.incompChanges = {}
                            }
                            if (doRefresh) {
                              syntaxVE.refresh(syntaxVE.d3CanvasDiv.node().scrollLeft);
                            }
                          });

    syntaxVE.d3cmdSave = d3UxDiv.append('button')
                          .attr("id","syntaxSave")
                          .attr("class","cmdSave")
                          .attr('tabindex', "-1")
                          .text("Save")
                          .on('click', function(){
                                        if (syntaxVE.hasSaveChanges() &&
                                             confirm("Save all changes?")) {
                                          syntaxVE.saveIncompleteChanges();
                                          syntaxVE.save();
                                        } else {
                                          syntaxVE.enableSave(false);
                                        }
                                      })
                          .attr("disabled","disabled");

    syntaxVE.d3CanvasDiv = d3UxDiv.append('div')
                          .attr("class", "canvas")
                          .attr('tabindex', "-1")
                          .style("overflow","auto")
                          .on('click', function (_,_,elem) {
                                         if (elem != syntaxVE.d3SynFuncSelect.node()) {
                                          syntaxVE.d3SynFuncSelect.style("display","none");
                                         }
                                       })
                          .on('scroll', function () {
                                          syntaxVE.d3SynFuncSelect.style("display","none");
                                        });

    syntaxVE.d3SynFuncSelect = syntaxVE.d3CanvasDiv.append('select')
                                .attr("id","synfuncselector")
                                .attr('tabindex', "-1");


    syntaxVE.d3SynFuncSelect.selectAll("option")
              .data(syntaxVE.relTypes)
              .enter()
                .append("option")
                .text(function(relType) {
                  return relType.type;
                })
                .attr("value", function (_, i) {
                  return i;
                });

    syntaxVE.svgGraphs = syntaxVE.d3CanvasDiv.selectAll('svg')
          .data(syntaxVE.data.nodegrps)
          .enter()
          .append('svg')
          // .attr("width", 600)
            //.style("overflow-x","auto")
            .style("min-height", '200px')
            .attr("id", nodegrp => nodegrp.id)
            .attr('tabindex', "-1")
            .append('defs') //define arrowhead markers for links
              .append('marker')
                .attr('id','arrowhead')
                .attr('viewBox','0 0 10 10')
                .attr('refX',1)
                .attr('refY',5)
                .attr('markerHeight',6)
                .attr('markerWidth',6)
                .attr('orient','auto')
                  .append('path')
                    .attr('d',"M 0 0 L 10 5 L 0 10 Z")
                    .attr("fill","black");
    syntaxVE.svgGraphs.each((nodegrp) => {
            d3.select(`#${nodegrp.id}`).selectAll('g')
                    .data( nodegrp => nodegrp.nodes)
                    .enter()
                    .append('g')
                    .attr('class', nodegrp => nodegrp.id);
            nodegrp.nodeID2Index = {};
            nodegrp.nodes.forEach((word,i) => {
              word.no = i+1;
              nodegrp.nodeID2Index[word.id] = i;
            });
            nodegrp.nodes.forEach((word,i) => {
              var fIndex, tIndex, indxDist;
              if (word.rel) {
                if (!nodegrp.rels) {
                  nodegrp.rels = [];
                }
                fIndex = word.no;
                tIndex = nodegrp.nodeID2Index[word.rel.target] + 1;
                indxDist = Math.abs(fIndex - tIndex);
                if (indxDist) {
                  if (!nodegrp.rels[indxDist]) {
                    nodegrp.rels[indxDist] = [];
                  }
                  nodegrp.rels[indxDist].push({from:word.id, type:word.rel.type, to: word.rel.target})
                }
              }
            });
          });

    syntaxVE.svgGraphs.each((nodegrp)=> {
            //set start point
            var wLoc = {x:syntaxVE.startOffset.x, y:syntaxVE.startOffset.y},
                svg = d3.select(`#${nodegrp.id}`); //get each svg graph
            svg.on('mousemove', function() {
                  var startLoc, endLoc, point = d3.mouse(this);
                  if (svg.dragrel) {
                    //console.log( point) // log the mouse x,y position
                    if (svg.newRel){
                      svg.newRel.endLoc = {x: point[0], y:point[1]};
                      startLoc = svg.newRel.startLoc;
                      endLoc = svg.newRel.endLoc;
                      svg.newpath.attr('d',`M ${startLoc.x} ${startLoc.y} C ${startLoc.x } ${startLoc.y + 20 } ${endLoc.x} ${endLoc.y + 20} ${endLoc.x} ${endLoc.y + 10}`);
                    }
                  }
                })
                .on('mouseenter', function() {
                  if (svg.dragrel) {
                    delete svg.dragrel;
                  }
                  if (svg.newRel) {
                    delete svg.newRel;
                  }
                  if (svg.newpath) {
                    svg.newpath.remove();
                    delete svg.newpath;
                  }
                })
                .on('mouseleave', function() {
                  if (svg.dragrel) {
                    delete svg.dragrel;
                  }
                  if (svg.newRel) {
                    delete svg.newRel;
                  }
                  if (svg.newpath) {
                    svg.newpath.remove();
                    delete svg.newpath;
                  }
                })
                .on('click', function(_,_,target) {
                  if ( syntaxVE.id != syntaxVE.layoutMgr.focusPaneID) {
                    syntaxVE.layoutMgr.focusHandler(null,syntaxVE.id);
                  }
                   return;
                });
            svg.selectAll('g').each((word)=> {
                var wordGObj = d3.select(`g.${word.id}`)
                                  .attr('x', wLoc.x)
                                  .attr('y', wLoc.y),
                    wordTObj = wordGObj.append('text')
                                .attr("id", word => word.id),
                    wordTSpan, tWidth = 0, posTSpan, wordStartOffsetX,
                    infTSpan, linkPt, lineMarkerSpan;
                if (word.linemarker) {
                  lineMarkerSpan = wordTObj.append('tspan')
                                    .attr('text-anchor', 'middle')
                                    .attr('class',`linemarker ${word.linemarker.id}`)
                                    .attr('tabindex', "0") //nextLineTabIndex++)
                                    .attr('y', wLoc.y)
                                    .text(syntaxVE.linemarkerPrefix + word.linemarker.text + syntaxVE.linemarkerSuffix);
                  tWidth = Math.max(tWidth,parseInt(lineMarkerSpan.node().scrollWidth));
                }
                wordTSpan = wordTObj.append('tspan')
                                  .attr('class',`word ${word.id}`)
                                  .attr('text-anchor', 'middle')
                                  .attr('x',0)
                                  .attr('y', wLoc.y + 20)
                                  .text(word.text.replace(/ʔ/g,''));
                tWidth = parseInt(wordTSpan.node().scrollWidth);
                wordStartOffsetX = -tWidth/2;
                if (word.linemarker) {
                  lineMarkerSpan.attr('x', word.linemarker.offset*8 + wordStartOffsetX + syntaxVE.wordSpacing)
                }
                if (word.sub1) {
                  posTSpan = wordTObj.append('tspan')
                                    .attr('text-anchor', 'middle')
                                    .attr('class',`pos ${word.id}`)
                                    .attr('x', 0)
                                    .attr('y', wLoc.y + 40)
                                    .text(word.sub1);
                  tWidth = Math.max(tWidth,parseInt(posTSpan.node().scrollWidth));
                }
                if (word.sub2) {
                  infTSpan = wordTObj.append('tspan')
                                    .attr('text-anchor', 'middle')
                                    .attr('class',`morph ${word.id}`)
                                    .attr('x', 0)
                                    .attr('y', wLoc.y + 55)
                                    .attr('font', 'bold 8px Verdana, Helvetica, Arial, sans-serif')
                                    .text(word.sub2);
                  tWidth = Math.max(tWidth,parseInt(infTSpan.node().scrollWidth));
                }
                if (word.sub1) {
                  linkPt = wordGObj.append('circle')
                                    .attr('class',`linkpoint ${word.id}`)
                                    .attr('cx', 0)
                                    .attr('cy', wLoc.y + 65)
                                    .attr('r', 4)
                                    .on('click',(word,i,n) => {
                                      // user clicked on linkpoint circle under a word
                                      var startLoc, endLoc, svgData = svg.data()[0];
                                      // user selected a source word (clicked on linkpoint circle)
                                      if (!svg.newRel) {
                                        // user selected a source word
                                        if (word.rel && word.rel.target) {
                                          //source word already link so warn user about single relation constraint
                                          alert("only a single syntax relation allowed, please remove the current one first");
                                          return;
                                        }
                                        svg.newRel = {
                                          from:word.id,
                                          iDist:1,
                                          index:svgData.nodeID2Index[word.id],
                                          dirty:1,
                                          startLoc: word.linkLoc,
                                          endLoc: {x:word.linkLoc.x+3, y:word.linkLoc+3}
                                        };
                                        svg.dragrel = true;
                                        startLoc = svg.newRel.startLoc;
                                        endLoc = svg.newRel.endLoc;
                                        svg.newpath = svg.append('path')
                                            .attr('id','newrelID')
                                            .attr('stroke','green')
                                            .attr('stroke-dasharray',"5,5")
                                            .attr('stroke-width',1)
                                            .attr('fill','transparent')
                                            .attr('d',`M${startLoc.x},${startLoc.y} C${startLoc.x },${startLoc.y + 40} ${endLoc.x},${endLoc.y + 40} ${endLoc.x},${endLoc.y + 10}`)
                                            .attr('marker-end','url(#arrowhead)');
                                        return;
                                      }
                                      if (word.linkLoc.x == svg.newRel.startLoc.x && word.linkLoc.y == svg.newRel.startLoc.y) {
                                        //user clicked on start node
                                        if (svg.dragrel) {
                                          delete svg.dragrel;
                                        }
                                        if (svg.newRel) {
                                          delete svg.newRel;
                                        }
                                        if (svg.newpath) {
                                          svg.newpath.remove();
                                          delete svg.newpath;
                                        }
                                        return;
                                      } else { // user clicked on different linkpoint circle
                                        svg.newRel.to = word.id;
                                        svg.newRel.type = "new";
                                        iDist = Math.abs(svg.newRel.index - svg.data()[0].nodeID2Index[word.id]);
                                        //create relation list
                                        if (!svgData.rels) { //create distance list if none
                                          svgData.rels = [];
                                        }
                                        //add relation to list
                                        if (!svgData.rels[iDist]) {
                                          svgData.rels[iDist] = [];
                                        }
                                        svgData.rels[iDist].push({
                                          from: svg.newRel.from,
                                          type: svg.newRel.type,
                                          to: svg.newRel.to,
                                          dirty:1
                                        });
                                        cmd = 'add:'+svg.newRel.type+':'+svg.newRel.to;
                                        syntaxVE.addSyntaxChange(svg.newRel.from,cmd);
                                        //update word with relation data
                                        word = svgData.nodes[svg.newRel.index];
                                        if (!word.rel) {
                                          word.rel = { 'target':"",'type':""};
                                        }
                                        word.rel['target'] = svg.newRel.to;
                                        word.rel['type'] = svg.newRel.type;
                                        //remove rel create data
                                        svg.newpath.remove();
                                        //enable the save button
                                        if (syntaxVE.hasSaveChanges()) {
                                          syntaxVE.enableSave(true);
                                        }
                                        //redraw all links for correct selection overlay
                                        syntaxVE.redrawSvgLinks(svg);
                                        //clear relation drag temporary data
                                        delete svg.newRel;
                                        delete svg.newpath;
                                        delete svg.dragrel;
                                      }
                                    });
                }
        wordGObj.attr('transform',`translate(${tWidth/2 + wLoc.x},0)`);
        word.linkLoc = {x:tWidth/2 + wLoc.x, y:wLoc.y + 65};
        wLoc.x += tWidth + syntaxVE.wordSpacing;
      });
      // adjust svg element's width to include the new word node
      svg.attr('width', wLoc.x);
    })

    syntaxVE.svgGraphs.data().forEach((nodegrp,i)  => {
      var svg = d3.select(`#${nodegrp.id}`);
      syntaxVE.drawSvgLinks(svg);
    });
  },

  redrawSvgLinks:  function (svg) {
      var syntaxVE = this;

      svg.selectAll("text.linktype").remove();
      svg.selectAll("path.linkpath").remove();
//          .each((_,i,elem) => {
//            if (elem[0].parentNode.id != "arrowhead") {
//              d3.select(elem).remove();
//            }
//          });
      syntaxVE.drawSvgLinks(svg);
  },

  addSyntaxChange:  function(relFrom, cmdStr) {
    var syntaxVE = this, curCmdStrs, curCmdStr, compCmd,
        curParts, newParts, cmd1, cmd2, typ1, typ2, trg1, trg2;
    if (!syntaxVE.changes[relFrom]) {
      syntaxVE.changes[relFrom]=[cmdStr];
      DEBUG.log("gen",relFrom+" first command = "+cmdStr);
    } else {
      curCmdStrs = syntaxVE.changes[relFrom];
      if (curCmdStrs.length > 1) {
        DEBUG.log("gen",relFrom+" has multiple commands stacked"+
                        curCmdStrs.join(' -> '));
      }
      curCmdStr = curCmdStrs.pop();
      curParts = curCmdStr.split(':');
      cmd1 = curParts[0];
      typ1 = curParts[1].split('|')[0];
      trg1 = curParts[2].split('|')[0];
      newParts = cmdStr.split(':');
      cmd2 = newParts[0];
      typ2 = newParts[1].split('|').pop();
      trg2 = newParts[2].split('|').pop();
      switch (cmd1) {
        case 'add':
          //add followed by del => NOP
          if (cmd2 == 'del') {
            compCmd = null;
          } else if (cmd2 == 'chng' && trg1 == trg2) {
            compCmd = 'add:'+typ1+"|"+typ2+':'+trg1;
          } else {
            DEBUG.log("err",relFrom+" has "+cmd2+" after add which is not possible");
          }
          break;
        case 'del':
          //del followed by add => change oldtype to newtype and/or oldtarget to newtarget
          if (cmd2 == 'add') {
            compCmd = 'chng:'+typ1+"|"+typ2+':'+(trg1 == trg2?trg1:trg1+'|'+trg2);
          } else {
            DEBUG.log("err",relFrom+" has "+cmd2+" after del which is not possible");
          }
          break;
        case 'chng':
          //chng followed by del => del, chng followed by chng => chng
          if (cmd2 == 'del') {
            compCmd = 'del:'+typ1+"|"+typ2+':'+(trg1 == trg2?trg1:trg1+'|'+trg2);
          } else if (cmd2 == 'chng') {
            if (typ1 == typ2 && trg1 == trg2) {
              compCmd = null;
            } else {
              compCmd = 'chng:'+typ1+"|"+typ2+':'+(trg1 == trg2?trg1:trg1+'|'+trg2);
            }
          } else {
            DEBUG.log("err",relFrom+" has "+cmd2+" after chng which is not possible");
          }
          break;
      }
      DEBUG.log("gen",relFrom+" "+curCmdStr+" + "+cmdStr+" = "+compCmd);
      if (!compCmd){
        delete syntaxVE.changes[relFrom];
      } else {
        syntaxVE.changes[relFrom] = [compCmd];
      }
    }
  },

  removeLink:  function (nodegrp, rel, iDist) {
      var syntaxVE = this, index, word;
      //remove from rels
      index = -1;
      //find index of relation in nodegrp.rels
      nodegrp.rels[iDist].forEach((arel,i) => {
          if (arel.from == rel.from) {
            index=i;
            return; //found so terminate forEach
          };
        });
      if (index != -1) {
        nodegrp.rels[iDist].splice(index,1);
      }
      //remove from word.rel
      word = nodegrp.nodes[nodegrp.nodeID2Index[rel.from]];
      word.rel.from = "";
      word.rel.type = "";
      word.rel.target = "";
      //save command to changes
      cmd = 'del:'+rel.type+':'+rel.to;
      syntaxVE.addSyntaxChange(rel.from,cmd);
      //enable the save button
      if (syntaxVE.hasSaveChanges()) {
        syntaxVE.enableSave(true);
      }

      //remove text .wrdTag
      d3.selectAll(`text.linktype.${rel.from}`).remove();
      //remove path
      d3.selectAll(`path#link${rel.from}`).remove();
  },

  changeLinkType:  function (nodegrp, rel, iDist, newType) {
      var syntaxVE = this, index, word,
          curType = rel.type;
      //find current from rels
      index = -1;
      //find index of relation in nodegrp.rels
      nodegrp.rels[iDist].forEach((arel,i) => {
          if (arel.from == rel.from) {
            index=i;
            return;
          };
        });
      if (index != -1) {
        nodegrp.rels[iDist][index].type = newType;
        nodegrp.rels[iDist][index].dirty = 1;
      } else { // should not get here, but if not relation found abort
        return;
      }
      //change type for word.rel
      word = nodegrp.nodes[nodegrp.nodeID2Index[rel.from]];
      word.rel.type = newType;
      //add command for change to changes
      cmd = 'chng:'+curType+'|'+rel.type+':'+rel.to;
      syntaxVE.addSyntaxChange(rel.from,cmd);
      //enable the save button
      if (syntaxVE.hasSaveChanges()) {
        syntaxVE.enableSave(true);
      }
  },

  drawLink:  function (svg, nodegrp, rel, iDist) {
      var syntaxVE = this,
          relID = `link${rel.from}`,
          lineType = (rel.dirty ? "5,5":""),
          startLoc = nodegrp.nodes[nodegrp.nodeID2Index[rel.from]].linkLoc,
          endLoc = nodegrp.nodes[nodegrp.nodeID2Index[rel.to]].linkLoc,
          xDist = Math.abs(startLoc.x - endLoc.x),
          cpOffset = Math.max(Math.round(xDist/5),25),
          reverse = ( startLoc.x > endLoc.x );
      svg.append('path')
          .attr('id',relID)
          .attr('class',`linkpath ${syntaxVE.getRelTypeClass(rel.type)} ${rel.from}`) // ${ reltype.code.toLowerCase()}`)
         // .attr('stroke',getRelTypeClass(rel.type))
          .attr('stroke-width',1)
          .attr('stroke-dasharray',lineType)
          .attr('fill','transparent')
          .attr('d',`M${startLoc.x},${startLoc.y} C${startLoc.x +(reverse?-6*iDist -5:6*iDist+5)},${startLoc.y + cpOffset} ${endLoc.x},${endLoc.y + cpOffset} ${endLoc.x},${endLoc.y + 5}`)
          .attr('marker-end','url(#arrowhead)')
          .on('click', function() {
            if (!svg.dragrel && confirm(`Would you like to remove link from ${nodegrp.nodes[nodegrp.nodeID2Index[rel.from]].text} to ${nodegrp.nodes[nodegrp.nodeID2Index[rel.to]].text} of type ${rel.type} ?`)) {
              syntaxVE.removeLink(nodegrp, rel, iDist);
            }
          });
      svg.append('text')
          //.style('font-size','12')
          .attr('class',`linktype ${rel.from}`)
          .attr('dy',-3) //ensure there is spacing between line and text
          .append('textPath')
            .attr('xlink:href',`#${relID}`)
            .attr('class',`linktype ${syntaxVE.getRelTypeClass(rel.type)} ${nodegrp.id} ${rel.from}`)// ${ reltype.code.toLowerCase()}`)
            .attr('startOffset',(reverse?'45%':'55%'))
            .attr('text-anchor','middle')
          //  .attr('fill',getRelTypeClass(rel.type))
            .attr('side',(reverse?'right':'left'))
            .text(rel.type)
            .attr("transform", `rotate(${(reverse?180:0)},0,0)`)
            .on('dblclick', function() {
                var relBBox = this.getBoundingClientRect(),
                    offset = syntaxVE.contentDiv.offset(),
                    typeIndex = syntaxVE.relType2Index[this.textContent];
                syntaxVE.d3SynFuncSelect.node().selectedIndex = typeIndex;
                //console.log(relBBox);
                //adjust for scroll offset
                syntaxVE.d3SynFuncSelect.style("left",""+Math.round(relBBox.left - offset.left)+"px")
                                        .style("top",""+Math.round(relBBox.top - offset.top)+"px")
                                        .style("display","block")
                                        .on("change", function() {
                                            //console.log(this,this.getBoundingClientRect());
                                            var newType = syntaxVE.relTypes[this.value].type;
                                            if (newType != rel.type) {
                                              syntaxVE.changeLinkType(nodegrp, rel, iDist, newType);
                                              //redraw all links
                                              syntaxVE.redrawSvgLinks(svg);
                                            }
                                            //this.blur();
                                            d3.select(this).style("display","none");
                                        });
              });
      if (startLoc.y + cpOffset > svg.attr('height')) {
        svg.attr('height',startLoc.y + cpOffset +20);
      }
  },

  drawSvgLinks:  function (svg) {
      var syntaxVE = this,
          nodegrp = svg.data()[0],
          distIndices = [];
      if (!nodegrp.rels) {
        return;
      }
      nodegrp.rels.forEach((rels,iDist) => {
        distIndices.push(iDist);
      });
      distIndices.sort(function(a, b) {
        return parseInt(a) > parseInt(b)
      }).reverse();
      distIndices.forEach( iDist => {
        nodegrp.rels[iDist].forEach( rel => {
          syntaxVE.drawLink(svg, nodegrp, rel, iDist);
        });
      });
  }

};

