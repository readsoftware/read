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
* editors tcmEditor object
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
/**
* Constructor for TCM Editor Object
*
* @type Object
*
* @param {EditionVE} editionVE Reference to the controlling edition editor object
*
* @returns {tcmEditor}
*/

EDITORS.tcmEditor =  function(editionVE) {
  this.ednVE = editionVE;
  this.contentDiv = editionVE.contentDiv;
  var selectNode, selection, selectedNodes, offset;// could be multiple, so below we only get the first one.
  if (editionVE.sclEd && editionVE.sclEd.syllable) {//selected Nodes so use them
    selectNode = $(editionVE.sclEd.syllable.get(0));
    offset = editionVE.sclEd.curPos;
  } else if (selection = this.getSelectionInfo()) {
    selectNode = $(selection[0]);
    offset = selection[1];
  } else if (selectedNodes = $(".grpGra.selected",this.contentDiv)) {//selected Nodes so use them
    selectNode = $(selectedNodes.get(0));
    offset = 0;
  } else {
    selectNode = $(this.contentDiv).find(".grpGra:first");
    offset = 0;
  }
  this.init(selectNode,offset);
  return this;
};

/**
* Prototype for Syllable Editor Object
*/
EDITORS.tcmEditor.prototype = {

/**
* Initialiser for TCM Editor Object
*
* @param {jqueryNode} $selectSclNode The current syllable selected
* @param int offset in the select node
*/

  init: function(selectNode,offset) {
    var headerNode, selection;
    if (!selectNode) {
      if (selection = this.getSelectionInfo()) {
        selectNode = $(selection[0]);
        offset = selection[1];
      }
    }
    if (!offset || isNaN(offset) || offset < 0 || offset > selectNode.text().length) {
      offset = 0;
    }
    if (selectNode && (selectNode.hasClass("grpGra") ||
                       selectNode.hasClass("TCM") ||
                       selectNode.hasClass("linebreak") ||
                       selectNode.hasClass("boundary"))) {
      headerNode = selectNode.prevUntil("h3,br","span.textDivHeader");
      this.hdrNode = $(headerNode.get(0));
      this.selectNode = selectNode;
      this.offset = offset;
      this.lineSeqID = this.getLineSeqID(selectNode);
      this.lineOrd = this.hdrNode.attr('class').match(/ordL(\d+)/)[1];
      this.initTime = (new Date()).getTime();
      this.saving = false;
      this.dirty = false;
      this.nodes = this.hdrNode.nextUntil('br');
      this.nodes.addClass('tcmedit');
      this.validateState();
      selectNode.focus();
      $(this.contentDiv).focus();
    } else {
      DEBUG.log("err","error trying to init TCM editor without valid line node indicated");
    }
  },


/**
* re-initialiser for TCM Editor Object
*
* @param {jqueryNode} $selectSclNode The current syllable selected
* @param int offset in the select node
*/

  reInit: function(selectNode,offset) {
    this.nodes.removeClass('tcmedit');
    if (selectNode) {
      this.init(selectNode,offset);
      this.synchSelectionToNode();
    } else {
      this.init();
    }
  },


/**
* exit TCM editor
*
* @param function cb Callback function
*
* @returns true|false
*/

  exit: function(cb) {
    var tcmED = this, isCurLineUnsaved = false, lineOrd, unsavedLineOrds = {},
        cntUnsavedLines = 0, tcmdelHeaders;
    //count lines with changes
    $('.tcmadd,.tcmdel',this.contentDiv).each(function(index,elem) {
        lineOrd = elem.className.match(/ordL(\d+)/) ? elem.className.match(/ordL(\d+)/)[1] : null;
        if (lineOrd) {
          unsavedLineOrds[lineOrd] = 1;
          if (lineOrd == this.lineOrd) {
            isCurLineUnsaved = true;
          }
        }
    });
    cntUnsavedLines = Object.keys(unsavedLineOrds).length;
    if ( this.dirty && cntUnsavedLines == 1 && isCurLineUnsaved ) {// case where user leaves TCM after changes without changing lines
      return this.save(function() {
                          if (tcmED.nodes) {
                            tcmED.nodes.removeClass('tcmedit');
                          }
                          $('#contentDiv').find('.tcmadd').remove();
                          $('#contentDiv').find('.tcmerror').removeClass('tcmerror');
                          if (cb) {
                            cb();
                          }
                        },
                        cb);
    } else if ((cntUnsavedLines > 1 || !isCurLineUnsaved && cntUnsavedLines) &&
          !confirm((cntUnsavedLines == 1?
                    "There is 1 line":
                    "There are "+cntUnsavedLines+" lines") +
                    " with invalid Text Critical Marks."+
                    " Press OK to lose these changes or"+
                    " CANCEL to remain in TCM edit mode.")) {
      //NOP since the user is staying in TCM mode.so just fall out of command.
    } else {
      if (tcmED.nodes) {
        tcmED.nodes.removeClass('tcmedit');
      }
//      $('#contentDiv').find('.tcmadd').remove();
//      $('#contentDiv').find('.tcmerror').removeClass('tcmerror');
      tcmED.contentDiv.find('.tcmadd').remove();
      tcmED.contentDiv.find('.tcmerror').removeClass('tcmerror');
      //for each tcmdel header that still exist rerender the line
      $(".textDivHeader.tcmdel",tcmED.contentDiv).each(function(index,elem){
          var lineOrdTag = elem.className.match(/ordL\d+/) ? elem.className.match(/ordL\d+/)[0]:null,
              physLineID = elem.className.match(/lineseq(\d+)/) ? elem.className.match(/lineseq(\d+)/)[1] : null;
          if (lineOrdTag && physLineID) {
            tcmED.ednVE.reRenderPhysLine(lineOrdTag,physLineID);
          }
      });
      if (cb) {
        cb();
      }
      return true;
    }
  },


/**
* get selection information
*
* @returns mixed[] Array of selected node and offset with in the node
*/

  getSelectionInfo: function() {
    var sel,range,node,offset,classes;
    if (window.getSelection) {
      sel = window.getSelection();
      if (sel.getRangeAt && sel.rangeCount) {
        range = sel.getRangeAt(0);
      }
    } else if (document.selection && document.selection.createRange) {
      range = document.selection.createRange();
    }
    if (range.startContainer.nodeName == "#text") {
      node = range.startContainer.parentNode;
    } else {
      node = range.startContainer;
    }
    classes = node.className;
    if ((classes.indexOf("grpGra") +
         classes.indexOf("TCM") +
         classes.indexOf("boundary") +
         classes.indexOf("linebreak" )) > -4) {
      return [node,range.startOffset];
    }
    return null;
  },


/**
* sync browser selection with selection node
*
* @param {jqueryNode} $selectSclNode The current syllable selected
* @param int offset in the select node
*
* @returns true|false
*/

  synchSelectionToNode: function(node,offset) {
    var sel,range,classes;
    if (window.getSelection) {
      sel = window.getSelection();
      if (sel.getRangeAt && sel.rangeCount) {
        range = sel.getRangeAt(0);
      }
    } else if (document.selection && document.selection.createRange) {
      range = document.selection.createRange();
    }
    if (!node) {
      node = this.selectNode.get(0);
      offset = this.offset;
    }
    classes = node.className;
    if (range && (classes.indexOf("grpGra") +
         classes.indexOf("TCM") +
         classes.indexOf("boundary") +
         classes.indexOf("linebreak" )) > -4) {//summed returns = -4 if all fail
      range.setStart(node.firstChild,offset);
      range.setEnd(node.firstChild,offset);
      return true;
    }
    return false;
  },


/**
* get physical line grapheme TCM state map
*
* @returns object State grapheme map recording the TCM state changes
*/

  getStateGraMap: function() {
    var orderedTCMStateGraMap = [], classes, context,
        curTCMState = "S", nextState, elemState, i,
        graIDs = [], curSylID, sylGraIDs, curSylGraStrToIDMap;
    this.nodes.each(function(index,elem) {
      classes = elem.className;
      if (classes.match(/TCM/)) {
        //get state
        elemState = $(elem).attr('state');
        //calc new state
        nextState = tcmValidTransitionLookup[curTCMState][elemState];
        if (nextState) {
          if (graIDs.length) {//length is zero for second of adjacent TCM nodes
            //push curState,graIDs to map
            orderedTCMStateGraMap.push([curTCMState,graIDs]);
            //clear graIDs
            graIDs = [];
          }
          //make newState curState
          curTCMState = nextState;
        }
      } else if (classes.match(/grpGra/)) {
        //if sclID is new then create new syl grapheme str to id map
        if (classes.match(/scl(\d+)/)[1] != curSylID) {
          curSylID = classes.match(/scl(\d+)/)[1];
          sylGraIDs = entities.scl[curSylID].graphemeIDs;
          curSylGraStrToIDMap = [];
          for (i in sylGraIDs) {
            graStr = entities.gra[sylGraIDs[i]].value;
            curSylGraStrToIDMap.push([graStr,sylGraIDs[i]]);
          }
        }
        //for elem text, map str to map until string is done pushing id to graIDs
        grpStr = elem.textContent;
        context = elem.className.replace(/grpGra/,"")
                                .replace(/ord\d+/,"")
                                .replace(/ordL\d+/,"")
                                .replace(/seg\d+/,"")
                                .replace(/firstLine/,"")
                                .replace(/lastLine/,"")
                                .replace(/selected/,"")
                                .replace(/tcmedit/,"")
                                .trim()
                                .replace(/\s/g,",")
                                .replace(/\,+/g,",");

        while (grpStr.length && curSylGraStrToIDMap.length) {
          graStr = curSylGraStrToIDMap[0][0];
          i = grpStr.indexOf(graStr);
          if (i==0 || (i == -1 && graStr == "ʔ")) {// need to keep the vowel carrier to set it's TCM  value equal with it's carried vowel
            graIDs.push([curSylGraStrToIDMap[0][1],context]);
            if (i==0){ // vowel carrier is not represented on display only remove other characters
              grpStr = grpStr.substring(graStr.length);
            }
          } else {
            //error
            DEBUG.log("err","calc TCMStateGra Map - error syl gra '"+graStr +"' order mismatch with grp "+grpStr);
          }
          curSylGraStrToIDMap.shift();
         }
      }
    });
    if (graIDs.length) {
      orderedTCMStateGraMap.push([curTCMState,graIDs]);
    }
    return orderedTCMStateGraMap;
  },


/**
* align offest to grapheme boundary
*/

  alignOffsetToGrapheme: function() {
    var classes = this.selectNode.attr('class'),
        grpStr = this.selectNode.text(),graStr,i, offsetCnt =0,
        graIDs = [], curSylID, sylGraIDs, curSylGrpStrGraOffsetMap = [];
    if (classes.match(/grpGra/) && classes.match(/scl(\d+)/)) {
      curSylID = classes.match(/scl(\d+)/)[1];
      sylGraIDs = entities.scl[curSylID].graphemeIDs;
      for (i in sylGraIDs) {//map each grapheme to group string counting length til at or greater than offset
        graStr = entities.gra[sylGraIDs[i]].value;
        if (grpStr.indexOf(graStr) == 0) {
          offsetCnt += graStr.length;
          if (offsetCnt >= this.offset) {
            this.offset = offsetCnt;
            break;
          }
        }
      }
    }
  },

  saving:false,

/**
* save tcm for given line
*
* @param function cbSuccess Success callback function
* @param function cbError Error callback function
* @returns true|false
*/

  save: function(cbSuccess,cbError) {
    if (this.validateState() && this.dirty ) {
      //save the syllable requires the context (edn, seq-tok-scl, prevGra) and newSyl graphemes.
      var ednID = this.ednVE.edition.id,
          ednVE = this.ednVE, savedata={}, lineOrdTag,
          stateGraMap = this.getStateGraMap();
      // if not owned then save raw to new.
      lineOrdTag = this.hdrNode.attr('class').match(/ordL\d+/)[0];
      refLineSeqID = this.hdrNode.attr('class').match(/lineseq(\d+)/)[1];
      DEBUG.log("gen","save TCMStateGra Map for physLine seq:" + refLineSeqID + " - " + JSON.stringify(stateGraMap));
      this.saving = true;
      savedata={
        ednID: ednID,
        lineSeqID: refLineSeqID,
        stateMap: stateGraMap
      };
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      this.dirty = false;
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/updateGraphemeTCM.php?db='+dbName,
          data: 'data='+JSON.stringify(savedata),
          asynch: false,
          success: function (data, status, xhr) {
              var oldSeqIDTag, newSeqIDTag, seqID, oldTag, newTag, updatedSeq, physLineSeqID;
              if (typeof data == 'object' && data.success) {
                if (data.entities) {
                  //update data
                  ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                  //if textdiv, compound and/or token has a new id update all nodes with the new id
                  if (data.alteredTagIDs) {
                    for (oldTag in data.alteredTagIDs) {
                      newTag = data.alteredTagIDs[oldTag];
                      if (oldTag != newTag) { // for update only need to signal calc for textDiv where oldTag = newTag
                        $('.'+oldTag,ednVE.contentDiv).each(function(hindex,elem){
                                    //update seqID
                                    elem.className = elem.className.replace(oldTag,newTag);
                        });
                      }
                      if (newTag.indexOf('seq') == 0) {
                        ednVE.calcTextDivGraphemeLookups(newTag.substring(3));
                      }
                    }
                  }
                  //recalc any updated text div sequences
                  if (data.entities.update && data.entities.update.seq) {
                    for (seqID in data.entities.update.seq) {
                      updatedSeq = entities.seq[seqID];
                      if ( trmIDtoLabel[updatedSeq.typeID] == "TextDivision") {
                        ednVE.calcTextDivGraphemeLookups(seqID);
                      }
                    }
                  }
                }
                physLineSeqID = data.alteredPhysLineSeqID ? data.alteredPhysLineSeqID:refLineSeqID;
                // calcLineGraphemeLookups
                ednVE.calcLineGraphemeLookups(physLineSeqID);
                //redraw line
                ednVE.reRenderPhysLine(lineOrdTag,physLineSeqID);
                if (cbSuccess) {
                  cbSuccess();
                } else {
                  ednVE.tcmEd.reInit();
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Save Line TCMs***");
                  DEBUG.log("health","Params: "+JSON.stringify(savedata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['error']) {
                alert("An error occurred while trying to save TCM information. Error: " + data['error']);
              }
              ednVE.tcmEd.saving = false;
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save TCM information. Error: " + error;
              if (cbError) {
                cbError(errStr);
                cbError(errStr);
              } else {
                alert(errStr);
              }
              ednVE.tcmEd.saving = false;
          }
      });// end ajax
      return true;
    }
    return false;
//    DEBUG.log("gen""call to save end with origStr "+this.rawSyl+" and curStr "+this.curSyl);
  },


/**
* move to selection
*
* @param function cb Callback function
*
* @returns true|false
*/

  moveToSelection: function(cb) {
    var tcmEd = this, selection = this.getSelectionInfo(), newLineOrd;
    if (selection) {
      //get line ord
      newLineOrd = selection[0].className.match(/ordL(\d+)/)[1];
      if (!newLineOrd) {
        DEBUG.log("warn","BEEP! moving to selection with out line ordinal not allowed.");
        return false;
      }
      //if same line then update node and offset
      if ( this.lineOrd == newLineOrd) {
        this.selectNode = $(selection[0]);
        this.offset = selection[1];
        return true;
      } else {//else for different line call save with call back to init to new node and offset
        if (!this.dirty || !this.save(function() {
                          tcmEd.reInit($(selection[0]),selection[1]);
                          if (cb) {
                            cb();
                          }
                        },
                        cb)) {
          tcmEd.reInit($(selection[0]),selection[1]);
          if (cb) {
            cb();
          }
        }
      }
    } else {
      DEBUG.log("warn","BEEP! moving to selection didn't find useable selection.");
    }
  },


/**
* move to line
*
* @param string direction Indicating direction of move
*
* @returns true|false
*/

  moveLine: function(direction) {
    var tcmEd = this,nodeIndex,selectNode;
    DEBUG.traceEntry("moveLine","in dir = "+ direction );
    if (direction == 'up') {
      if (this.hdrNode.hasClass('startHeader')) {
          DEBUG.log("warn","BEEP! At first line cannot move up.");
          return false;
      } else {
        //simple solution is find header for lineOrd -1 find next node and offset 0
        selectNode = $('.textDivHeader.ordL'+(-1+parseInt(this.lineOrd))).next();
        if (!this.save(function() {
                          tcmEd.reInit();
                        })) {
          setTimeout(function(){tcmEd.moveToSelection();},0);
        }
        //TODO :find node's index
        //find previous lines node at same offset or less if shorter.
        //reInit to node and offset = 0
      }
    } else {
      if (this.hdrNode.hasClass('endHeader')) {
          DEBUG.log("warn","BEEP! At last line cannot move down.");
          return false;
      } else {
        //simple solution is find header for lineOrd +1 find next node and offset 0
        selectNode = $('.textDivHeader.ordL'+(1+parseInt(this.lineOrd))).next();
        if (!this.save(function() {
                          tcmEd.reInit();
                        })) {
          setTimeout(function(){tcmEd.moveToSelection();},0);
        }
        //find node's index
        //find previous lines node at same offset or less if shorter.
        //reInit to node and offset = 0
      }
    }
    DEBUG.traceExit("moveLine","in dir = "+ direction );
  },


/**
* put your comment there...
*
* @param string direction Indicating direction of move
*
* @returns true|false
*/

  moveCursor: function(direction) {
    DEBUG.traceEntry("moveCursor","in dir = "+ direction );
    var isLeft = (direction == "left"),selectNode,offset, tcmEd = this;
    //if cursor is at end and direction to move beyond end then move to previous syllable or next syllable
    //for selection just beep
    if ( isLeft ) {
      if (this.selectNode.prev().hasClass('textDivHeader')) {//at first node
        if (!this.hdrNode.hasClass('startHeader')) {
          //find linebreak of previous line
          selectNode = $(this.hdrNode.prevUntil('.grpGra','.linebreak').get(0));
          //call save with reInit on previous line linebreak
          return this.save(function() {
                            tcmEd.reInit(selectNode,0);
                          });
        } else {
          DEBUG.log("warn","BEEP! At first line cannot move back.");
        }
      } else {
        this.offset -= 1;
        if (this.offset < 0) {
          this.selectNode = this.selectNode.prev();
          if (this.selectNode.hasClass('toksep') && !this.ednVE.toksepVisible) {//skip hidden separator
            this.selectNode = this.selectNode.prev();
          }
          this.offset = this.selectNode.text().length-1;
        }
      }
    } else {
      if (this.selectNode.next().hasClass('linebreak')) {//at last node
        if (!this.hdrNode.hasClass('endHeader')) {
          //find linebreak of previous line
          selectNode = $(this.hdrNode.nextUntil('.grpGra','.textDivHeader').next().get(0));
          //call save with reInit on previous line linebreak
          return this.save(function() {
                            tcmEd.reInit(selectNode,0);
                          });
        } else {
          DEBUG.log("warn","BEEP! At last line cannot move forward.");
        }
      } else {
        this.offset += 1;
        if (this.offset > this.selectNode.text().length) {
          this.selectNode = this.selectNode.next();
          if (this.selectNode.hasClass('toksep') && !this.ednVE.toksepVisible) {//skip hidden separator
            this.selectNode = this.selectNode.next();
          }
          this.offset = 1;
        }
      }
    }
    DEBUG.log("warn","call to move cursor "+ direction +" offset " +this.offset + " at node " + this.selectNode.attr('class'));
    DEBUG.traceExit("moveCursor","in dir = "+ direction );
   return true;
  },


/**
* put your comment there...
*
* @param key
* @param ctrl
* @param shift
* @param alt
* @param event
*
* @returns boolean | string Indicates whether to cancel default action or error
*/

  processKey: function(key,ctrl,shift,alt,event) {
    var tcmState = tcmBracketCharToStateLookup[key],
        isClosingTCM = (tcmState && tcmState.length > 1 && tcmState[0] == '-'),
        prevIsHdr = this.selectNode.prev().hasClass('textDivHeader'),
        prevIsTCM = this.selectNode.prev().hasClass('TCM'),
        nextIsTCM = this.selectNode.next().hasClass('TCM'),
        tcmNode, stateTCMNode, mergeTCMState, isTCMNodeBefore,
        curType = this.getCurNodeType(),newTCMNode,insertPos, sclID, splitNode, str1,str2,
        curPos = this.offset == 0 ? 'start' :
                 (this.offset == this.selectNode.text().length)?'end':'mid',
        prevTCMIsClosing,curTCMIsClosing,nextTCMIsClosing;
    if (curType == 'TCM') {
      curTCMIsClosing = (this.selectNode.attr('state')[0] == "-");
    }
    if (prevIsTCM) {
      prevTCMIsClosing = (this.selectNode.prev().attr('state')[0] == "-");
    }
    if (nextIsTCM) {
      nextTCMIsClosing = (this.selectNode.next().attr('state')[0] == "-");
    }
    if (tcmState) {
      DEBUG.log("gen","process key '"+key+"' for "+event.type+(tcmState?" which has a state of "+tcmState:''));
      //apply state and characters to adjacent/current TCM node or create a new on and insert it.
      //check for additive TCM character
      //if cursor next to TCM
      if (curType == 'TCM' || prevIsTCM && curPos == 'start' || nextIsTCM && curPos == 'end') {
        //find TCM
        tcmNode = curType == 'TCM' ? this.selectNode :
                    ( prevIsTCM ? this.selectNode.prev() : this.selectNode.next());
        stateTCMNode = tcmNode.attr('state');
        isTCMNodeBefore = (curType == 'TCM' && curPos == 'end' || prevIsTCM && curPos == 'start');
        //if existing TCM  and new TCM align (both starting or both closing TCMs)
        if ((isClosingTCM && stateTCMNode[0] == "-"
              || !isClosingTCM && stateTCMNode[0] != "-")
                && tcmMergeLookup[stateTCMNode] && tcmMergeLookup[stateTCMNode][tcmState]) {//startingTCMs
          //then check if they combine to additive state for given order.
          mergeTCMState = tcmMergeLookup[stateTCMNode][tcmState]
        }
      }
      if (mergeTCMState) {
        //change state and text of tcmNode
        tcmNode.attr('state',mergeTCMState);
        tcmNode.text(tcmStateToBracketLookup[mergeTCMState]);
      } else {
        //find sclID
        sclIDNode = curType == 'TCM' || curType == 'grpGra' ? this.selectNode :
                      (curType == 'boundary' && curPos == 'start' || curType == 'linebreak')?
                        this.selectNode.prev():this.selectNode.next();
        sclID = sclIDNode.attr('class').match(/scl(\d+)/)[1];
        //calc insert position
        if (curPos == 'start'){
          insertPos = 'before';
        } else if (curPos == 'end') {//at right side of node
          insertPos = 'after';
        } else {//intra node - handle grpGra and TCM cases }
          //TCM
          if (curType == 'TCM') {
            if (isClosingTCM) {
              insertPos = 'before';
            } else {
              insertPos = 'after';
            }
          } else if (curType == 'grpGra') {
            //clone group and insertafter current then adjust text
            splitNode = this.selectNode.clone();
            //get grapheme aligned offset
            this.alignOffsetToGrapheme();
            str1 = this.selectNode.text().substring(0,this.offset);
            str2 = this.selectNode.text().substring(this.offset);
            this.selectNode.text(str1);
            splitNode.text(str2);
            splitNode.insertAfter(this.selectNode);
            //insert after current
            insertPos = 'after';
          }
        }
        newTCMNode = this.createTCMNode(tcmState,sclID);
        if (this.selectNode.hasClass('boundary')) {
          if (!isClosingTCM && insertPos == 'before') {
            insertPos = 'after';
          } else if (isClosingTCM && insertPos == 'after') {
            insertPos = 'before';
          }
        }
        if (insertPos == 'before') {
          if (this.selectNode.prev().hasClass('boundary') && isClosingTCM) {
            newTCMNode.insertBefore(this.selectNode.prev());
          } else {
            newTCMNode.insertBefore(this.selectNode);
          }
        } else {
          if (this.selectNode.next().hasClass('boundary') && !isClosingTCM) {
            newTCMNode.insertAfter(this.selectNode.next());
          } else {
            newTCMNode.insertAfter(this.selectNode);
          }
        }
        this.selectNode = newTCMNode;
        this.offset = newTCMNode.text().length;
        this.synchSelectionToNode();
      }
      this.dirty = true;
    } else if (key == "Backspace" && (prevIsTCM || curType == 'TCM' && this.offset == this.selectNode.text().length)) {
      //check for TCM adjacent to left and delete entire node if exist
      if (prevIsTCM && this.offset == 0) {
        if (!this.selectNode.prev().hasClass('tcmadd') && !this.hdrNode.hasClass('tcmdel')) {//deleting an existing so mark hdr
          this.hdrNode.addClass('tcmdel');
        }
        this.selectNode.prev().remove();
        this.dirty = true;
      } else if (curType == 'TCM'){
        temp = this.selectNode.next();
        if (!this.selectNode.hasClass('tcmadd') && !this.hdrNode.hasClass('tcmdel')) {//deleting an existing so mark hrd
          this.hdrNode.addClass('tcmdel');
        }
        this.selectNode.remove();
        this.selectNode = temp;
        this.offset = 0;
        this.dirty = true;
      }
    } else if ((key == "Del" || key == "Delete") && (nextIsTCM || curType == 'TCM' && this.offset == 0)) {
      //check for TCM adjacent to right and delete entire node if exist
      if (nextIsTCM && this.offset == this.selectNode.text().length) {
        if (!this.selectNode.next().hasClass('tcmadd') && !this.hdrNode.hasClass('tcmdel')) {//deleting an existing so mark hrd
          this.hdrNode.addClass('tcmdel');
        }
        this.selectNode.next().remove();
        this.dirty = true;
      } else if (curType == 'TCM') {
        temp = this.selectNode.prev();
        if (!this.selectNode.hasClass('tcmadd') && !this.hdrNode.hasClass('tcmdel')) {//deleting an existing so mark hrd
          this.hdrNode.addClass('tcmdel');
        }
        this.selectNode.remove();
        this.selectNode = temp;
        this.offset = temp.text().length;
        this.dirty = true;
      }
    }
    if (this.dirty) {//something was adjusted so refresh node list
      this.nodes = this.hdrNode.nextUntil('br');
    }
    this.validateState();
  },


/**
* calculate TCM html
*
* @param string tcmState State code to lookup brackets
* @param int sclID Indicates SyllableClust entity id that TCM is part of
*/

  createTCMNode: function(tcmState,sclID){
    return $('<span class="TCM scl'+sclID+' ordL'+this.lineOrd+' tcmadd tcmedit"' +
              ' state="'+tcmState+'">'+tcmStateToBracketLookup[tcmState]+'</span>' )
  },


/**
* get current node type
*
* @returns string type indicator
*/

  getCurNodeType: function(){
    if (this.selectNode.hasClass('grpGra')){
      return 'grpGra';
    }
    if (this.selectNode.hasClass('TCM')){
      return 'TCM';
    }
    if (this.selectNode.hasClass('boundary')){
      return 'boundary';
    }
    if (this.selectNode.hasClass('linebreak')){
      return 'linebreak';
    }
  },


/**
* get line sequence id of the referenced node
*
* @param node refNode Reference HTML element node
*/

  getLineSeqID: function(refNode){
    var headerNode = refNode.prevUntil("h3,br","span.textDivHeader");
    if (headerNode.length == 1 && headerNode.attr('class').match(/lineseq(\d+)/)) {
      return headerNode.attr('class').match(/lineseq(\d+)/)[1];
    }
    return null;
  },

  /**************************  calculate state *******************************/

/**
* validate the current lines bracketing creates valid TCM states
*
* @returns true|false
*/

  validateState: function() {
     var classes, curTCMState = "S", autoEndTCMNode, sclID,
         nextState, elemState, isErrorFree = true, reReadNodes = false;
   //remove any .tcmerror
    this.nodes.removeClass('tcmerror');
    //run through TCM Nodes checking transition marking first illegal with .tcmerror and return false
    this.nodes.each(function(index,elem) {
      classes = elem.className;
      if (classes.match(/TCM/)) {
        if (classes.match(/autoGen/)) { // on auto generated ending
          //remove it and let code below regen if needed
          $(elem).remove();
          reReadNodes = true;
        } else if (isErrorFree) {
          //get state
          elemState = $(elem).attr('state');
          //calc new state
          nextState = tcmValidTransitionLookup[curTCMState][elemState];
          if (!nextState) {// hit an invalid TCM
            $(elem).addClass('tcmerror');
            isErrorFree = false;
            curTCMState = nextState = "S";//ensore no end marker autogenerated
          } else {
            curTCMState = nextState;
          }
        }
      }
    });
    if (curTCMState != "S") {//no closing TCM at the end of line
      sclID = this.nodes.last().prev().attr('class').match(/scl(\d+)/)[1];
      autoEndTCMNode = this.createTCMNode("-"+curTCMState,sclID);
      autoEndTCMNode.addClass('autoGen');
      autoEndTCMNode.insertBefore(this.nodes.last());
      reReadNodes = true;
    }
    if (reReadNodes) {
      this.nodes = this.hdrNode.nextUntil('br');
    }
    if ($('.tcmadd.ordL'+this.lineOrd,this.contentDiv).length == 0 &&
        $('.tcmdel.ordL'+this.lineOrd,this.contentDiv).length == 0) {
      this.dirty = false;
    }
    return isErrorFree;
  }
}

var tcmMergeLookup = {
    //Singular TCM States
    "A" : {
      "A" : "I"},
    "-A" : {
      "-A" : "-I"},
    "D" : {
      "D" : "Sd"},
    "-D" : {
      "-D" : "-Sd"}
};

var tcmValidTransitionLookup = {
    //start
    "S" : {
      "S" : "S",
      "A" : "A",
      "D" : "D",
      "DA" : "DA",
      "DR" : "DR",
      "DU" : "DU",
      "DSd" : "DSd",
      "DI" : "DI",
      "DIU" : "DIU",
      "DIR" : "DIR",
      "DISd" : "DISd",
      "DIA" : "DIA",
      "I" : "I",
      "IR" : "IR",
      "IU" : "IU",
      "ID" : "ID",
      "ISd" : "ISd",
      "IA" :"IA",
      "R" : "R",
      "Sd" : "Sd",
      "SdR" : "SdR",
      "SdU" : "SdU",
      "SdD" : "SdD",
      "SdA" : "SdA",
      "SdI" : "SdI",
      "SdIR" : "SdIR",
      "SdIU" : "SdIU",
      "SdID" : "SdID",
      "SdIA" : "SdIA",
      "U" : "U"},
    //Singular TCM States
    "A" : {
      "A" : "E",
      "-A" : "S",
      "S" : "S"},
    "D" : {
      "D" : "Sd",
      "-D" : "S",
      "A" : "DA",
      "R" : "DR",
      "U" : "DU",
      "Sd" : "DSd",
      "I" : "DI",
      "IU" : "DIU",
      "IR" : "DIR",
      "ISd" : "DISd",
      "IA" : "DIA",
      "S" : "S"},
    "I" : {
      "-I"  : "S",
      "-A"  : "A",
      "R" : "IR",
      "U" : "IU",
      "D" : "ID",
      "Sd" : "ISd",
      "A" :"IA",
      "S" : "S"},
    "R" : {
      "-R" : "S",
      "S" : "S"},
    "Sd" : {
      "-Sd" : "S",
      "-D" : "D",
      "R" : "SdR",
      "U" : "SdU",
      "D" : "SdD",
      "A" : "SdA",
      "I" : "SdI",
      "IR" : "SdIR",
      "IU" : "SdIU",
      "ID" : "SdID",
      "IA" : "SdIA",
      "S"   : "S"},
    "U" : {
      "-U" : "S",
      "S" : "S"},
    //Double TCM States
    "DA" : {
      "-DA" : "S",
      "-A" : "D",
      "A" : "DI",
      "S" : "S"},
    "DI" : {
      "-DI" : "S",
      "-I" : "D",
      "-A" : "DA",
      "U" : "DIU",
      "R" : "DIR",
      "Sd" : "DISd",
      "A" : "DIA",
      "S" : "S"},
    "DR" : {
      "-DR" : "S",
      "-R" : "D",
      "S" : "S"},
    "DSd" : {
      "-DSd" : "S",
      "-Sd" : "D",
      "-D" : "Sd",
      "S" : "S"},
    "DU" : {
      "-DU" : "S",
      "-U" : "D",
      "S" : "S"},
    "IA" : {
      "-IA" : "S",
      "-I" : "S",
      "-A" : "I",
      "S" : "S"},
    "ID" : {
      "-ID" : "S",
      "-D" : "I",
      "S" : "S"},
    "IR" : {
      "-IR" : "S",
      "-R" : "I",
      "S" : "S"},
    "ISd" : {
      "-ISd" : "S",
      "-Sd" : "I",
      "S" : "S"},
    "IU" : {
      "-IU" : "S",
      "-U" : "I",
      "S" : "S"},
    "SdA" : {
      "-SdA" : "S",
      "-A" : "Sd",
      "S" : "S"},
    "SdD" : {
      "-SdD" : "S",
      "-Sd" : "D",
      "-D" : "Sd",
      "S" : "S"},
    "SdI" : {
      "-SdI" : "S",
      "-I" : "Sd",
      "R" : "SdIR",
      "U" : "SdIU",
      "D" : "SdID",
      "A" : "SdIA",
      "S" : "S"},
    "SdR" : {
      "-SdR" : "S",
      "-R" : "Sd",
      "S" : "S"},
    "SdU" : {
      "-SdU" : "S",
      "-U" : "Sd",
      "S" : "S"},
    //Triple TCM States
    "DIA" : {
      "-DIA" : "S",
      "-A" : "DI",
      "-IA" : "D",
      "-I" : "D",
      "S" : "S"},
    "DIR" : {
      "-DIR" : "S",
      "-R" : "DI",
      "-IR" : "D",
      "S" : "S"},
    "DISd" : {
      "-DISd" : "S",
      "-Sd" : "DI",
      "-ISd" : "D",
      "S" : "S"},
    "DIU" : {
      "DIU" : "S",
      "-U" : "DI",
      "-IU" : "D",
      "S" : "S"},
    "SdIA" : {
      "-SdIA" : "S",
      "-A" : "SdI",
      "-IA" : "Sd",
      "-I" : "Sd",
      "S" : "S"},
    "SdID" : {
      "-SdID" : "S",
      "-D" : "SdI",
      "-ID" : "Sd",
      "S" : "S"},
    "SdIR" : {
      "-SdIR" : "S",
      "-R" : "SdI",
      "-IR" : "Sd",
      "S" : "S"},
    "SdIU" : {
      "-SdIU" : "S",
      "-U" : "SdI",
      "-IU" : "Sd",
      "S" : "S"}
};

var tcmBracketCharToStateLookup = {
    //char : state
      "*":"",
      "<":"A",
      "<*":"A",
      "⟨":"A",
      "⟨*":"A",
      "{" : "D",
      "<<" : "I",
      "⟪" : "I",
      "{{" : "Sd",
      "(" : "R",
      "(*" : "R",
      "[" : "U",
      ">" : "-A",
      "⟩" : "-A",
      "}" : "-D",
      ">>" : "-I",
      "⟫" : "-I",
      ")" : "-R",
      "}}" : "-Sd",
      "]" : "-U",
      "{⟨" : "DA",
      "{⟨*" : "DA",
      "{<" : "DA",
      "{<*" : "DA",
      "{(" : "DR",
      "{(*" : "DR",
      "{[" : "DU",
      "{{{" : "DSd",
      "{⟪" : "DI",
      "{<<" : "DI",
      "{⟪[" : "DIU",
      "{<<[" : "DIU",
      "{⟪(" : "DIR",
      "{⟪(*" : "DIR",
      "{<<(" : "DIR",
      "{<<(*" : "DIR",
      "{⟪{{" : "DISd",
      "{<<{{" : "DISd",
      "{⟪⟨" : "DIA",
      "{⟪⟨*" : "DIA",
      "{⟪<" : "DIA",
      "{⟪<*" : "DIA",
      "{<<⟨" : "DIA",
      "{<<⟨*" : "DIA",
      "{<<<" : "DIA",
      "{<<<*" : "DIA",
      "⟪(" : "IR",
      "⟪(*" : "IR",
      "<<(" : "IR",
      "<<(*" : "IR",
      "⟪[" : "IU",
      "<<[" : "IU",
      "⟪{" : "ID",
      "<<{" : "ID",
      "⟪{{" : "ISd",
      "<<{{" : "ISd",
      "⟪⟨" : "IA",
      "⟪⟨*" : "IA",
      "⟪<" : "IA",
      "⟪<*" : "IA",
      "<<⟨" : "IA",
      "<<⟨*" : "IA",
      "<<<" : "IA",
      "<<<*" : "IA",
      "{{(" : "SdR",
      "{{(*" : "SdR",
      "{{[" : "SdU",
      "{{{" : "SdD",
      "{{⟨" : "SdA",
      "{{⟨*" : "SdA",
      "{{<" : "SdA",
      "{{<*" : "SdA",
      "{{⟪" : "SdI",
      "{{<<" : "SdI",
      "{{⟪(" : "SdIR",
      "{{⟪(*" : "SdIR",
      "{{<<(" : "SdIR",
      "{{<<(*" : "SdIR",
      "{{⟪[" : "SdIU",
      "{{<<[" : "SdIU",
      "{{⟪{" : "SdID",
      "{{<<{" : "SdID",
      "{{<<⟨" : "SdIA",
      "{{<<⟨*" : "SdIA",
      "{{<<<" : "SdIA",
      "{{<<<*" : "SdIA",
      "{{⟪⟨" : "SdIA",
      "{{⟪⟨*" : "SdIA",
      "{{⟪<" : "SdIA",
      "{{⟪<*" : "SdIA",
      "⟩}" : "-DA",
      ">}" : "-DA",
      "⟫}" : "-DI",
      ">>}" : "-DI",
      ")}" : "-DR",
      "}}}" : "-DSd",
      "]}" : "-DU",
      "⟩⟫" : "-IA",
      "⟩>" : "-IA",
      ">>⟫" : "-IA",
      ">>>" : "-IA",
      "}⟫" : "-ID",
      "}>>" : "-ID",
      ")⟫" : "-IR",
      ")>>" : "-IR",
      "}}⟫" : "-ISd",
      "}}>>" : "-ISd",
      "]⟫" : "-IU",
      "]>>" : "-IU",
      "⟩}}" : "-SdA",
      ">}}" : "-SdA",
      "}}}" : "-SdD",
      "⟫}}" : "-SdI",
      ">>}}" : "-SdI",
      ")}}" : "-SdR",
      "]}}" : "-SdU",
      "⟩⟫}" : "-DIA",
      ">⟫}" : "-DIA",
      "⟩>>}" : "-DIA",
      ">>>}" : "-DIA",
      ")⟫}" : "-DIR",
      ")>>}" : "-DIR",
      "}}⟫}" : "-DISd",
      "}}>>}" : "-DISd",
      "]⟫}" : "-DIU",
      "]>>}" : "-DIU",
      "⟩⟫}}" : "-SdIA",
      ">⟫}}" : "-SdIA",
      "⟩>>}}" : "-SdIA",
      ">>>}}" : "-SdIA",
      "}⟫}}" : "-SdID",
      "}>>}}" : "-SdID",
      ")⟫}}" : "-SdIR",
      ")>>}}" : "-SdIR",
      "]⟫}}" : "-SdIU",
      "]>>}}" : "-SdIU"
};

var tcmStateToBracketLookup = {
    //state : char
      "A":"⟨",
//      "A":"⟨*",
      "D" : "{",
      "I" : "⟪",
      "Sd" : "{{",
      "R" : "(",
//      "R" : "(*",
      "U" : "[",
      "-A" : "⟩",
      "-D" : "}",
      "-I" : "⟫",
      "-R" : ")",
      "-Sd" : "}}",
      "-U" : "]",
      "DA" : "{⟨",
//      "DA" : "{⟨*",
      "DR" : "{(",
//      "DR" : "{(*",
      "DU" : "{[",
      "DSd" : "{{{",
      "DI" : "{⟪",
      "DIU" : "{⟪[",
      "DIR" : "{⟪(",
//      "DIR" : "{⟪(*",
      "DISd" : "{⟪{{",
      "DIA" : "{⟪⟨",
//      "DIA" : "{⟪⟨*",
      "IR" : "⟪(",
//      "IR" : "⟪(*",
      "IU" : "⟪[",
      "ID" : "⟪{",
      "ISd" : "⟪{{",
      "IA" : "⟪⟨",
//      "IA" : "⟪⟨*",
      "SdR" : "{{(",
//      "SdR" : "{{(*",
      "SdU" : "{{[",
      "SdD" : "{{{",
      "SdA" : "{{⟨",
//      "SdA" : "{{⟨*",
      "SdI" : "{{⟪",
      "SdIR" : "{{⟪(",
//      "SdIR" : "{{⟪(*",
      "SdIU" : "{{⟪[",
      "SdID" : "{{⟪{",
      "SdIA" : "{{⟪⟨",
//      "SdIA" : "{{⟪⟨*",
      "-DA" : "⟩}",
      "-DI" : "⟫}",
      "-DR" : ")}",
      "-DSd" : "}}}",
      "-DU" : "]}",
      "-IA" : "⟩⟫",
      "-ID" : "}⟫",
      "-IR" : ")⟫",
      "-ISd" : "}}⟫",
      "-IU" : "]⟫",
      "-SdA" : "⟩}}",
      "-SdD" : "}}}",
      "-SdI" : "⟫}}",
      "-SdR" : ")}}",
      "-SdU" : "]}}",
      "-DIA" : "⟩⟫}",
      "-DIR" : ")⟫}",
      "-DISd" : "}}⟫}",
      "-DIU" : "]⟫}",
      "-SdIA" : "⟩⟫}}",
      "-SdID" : "}⟫}}",
      "-SdIR" : ")⟫}}",
      "-SdIU" : "]⟫}}"
};

