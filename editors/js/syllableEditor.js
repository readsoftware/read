/*
* Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except
* in compliance with the License. You may obtain a copy of the License at
*
* http://www.gnu.org/licenses/gpl-3.0.txt
*
* Unless required by applicable law or agreed to in writing, software distributed under the License
* is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
* or implied. See the License for the specific language governing permissions and limitations under
* the License.
*/

/**
* editors sclEditor object
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
* Constructor for Syllable Editor Object
*
* @type Object
*
* @param {EditionVE} editionVE Reference to the controlling edition editor object
*
* @returns {sclEditor}
*/

EDITORS.sclEditor =  function(editionVE) {
  this.ednVE = editionVE;
  this.contentDiv = editionVE.contentDiv;
  this.dataMgr = editionVE.dataMgr;
  var selectNode,
      selectedNodes = $(".grpGra.selected",this.contentDiv);// could be multiple, so below we only get the first one.
  if (selectedNodes.length) {//selected Nodes so use them
    selectNode = $(selectedNodes.get(0));
  }
  if (!selectNode) {
    selectNode = $(this.contentDiv).find(".grpGra:first");
  }
  this.init(selectNode,"first");
  return this;
};

/**
* Prototype for Syllable Editor Object
*/
EDITORS.sclEditor.prototype = {

/**
* Initialiser for Syllable Editor Object
*
* @param {jqueryNode} $selectSclNode The current syllable selected
* @param string hint Indication of where the select node is
* @param boolean skipSynch Indicator to run cursor synch code
*/

  init: function($selectSclNode,hint,skipSynch) {
    var entities = this.dataMgr.entities,
        classes = $selectSclNode.attr("class");
    if ($selectSclNode && $selectSclNode.hasClass("grpGra")) {
      this.sclID = classes.match(/scl(\d+)/)[1];
      this.rawSyl = this.curSyl = this.computeSyllable();
      this.initTime = (new Date()).getTime();
      this.graphemeIDs = entities['scl'][this.sclID].graphemeIDs;
      if (!skipSynch) {
        this.synchSelection(hint);
      }
      this.saving = false;
      this.dirty = false;
      this.invalid = false;
      $(".selected",this.contentDiv).removeClass("selected");
      this.syllable.addClass("selected");
      var segIDs = [];
      if (entities['scl'] && entities['scl'][this.sclID] && entities['scl'][this.sclID].segID) {
        segIDs.push('seg'+entities['scl'][this.sclID].segID);
        $('.editContainer').trigger('updateselection',[this.ednVE.id,segIDs]);
      }
      $selectSclNode.focus();
      $(this.contentDiv).focus();
    } else {
      DEBUG.log("err","error trying to init syllable editor without syllable indicated");
    }
  },


/**
* re-initialise syllable editor with the current selected syllable
*/

  reInit: function() {
    var selectNode, selectedNodes = $(".grpGra.selected",this.contentDiv);// could be multiple, so we only get the first one.
    if (selectedNodes.length) {//selected Nodes so use them
      selectNode = $(selectedNodes.get(0));
    }
    if (!selectNode) {
      selectNode = $(this.contentDiv).find(".grpGra:first");
    }
    this.init(selectNode);
  },


/**
* remove selected syllable
*
* @param function cbError Callback for error case
*/

  removeSelected: function(cbError) {
   var delSclID = this.sclID,newTextDivSeqGID,
        ednVE = this.ednVE,
        VSE = this,
        linSeqID = this.getLineSeqID(delSclID),
        sclNodes = $('.grpGra.scl'+this.sclID,this.contentDiv),
        sclElem = sclNodes.get(0),
        contxt = sclElem.className.replace(/grpGra/,"")
                                  .replace(/ord\d+/,"")
                                  .replace(/ordL\d+/,"")
                                  .replace(/seg\d+/,"")
                                  .replace(/firstLine/,"")
                                  .replace(/lastLine/,"")
                                  .replace(/selected/,"")
                                  .trim()
                                  .replace(/\s/g,","),
        deldata={
          ednID: this.ednVE.edition.id,
          context: contxt,
          sclID: delSclID,
          lineSeqID: linSeqID
        };
        if (DEBUG.healthLogOn) {
          deldata['hlthLog'] = 1;
        }
    DEBUG.log("data","before deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
    DEBUG.log("data","before deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(linSeqID,0,1,ednVE.lookup.gra));
    DEBUG.log("data","before deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
    //find target syllable if delete
    //call service for idelete with callback to moveToSyllable for the new syllable with select all.
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/deleteSyllable.php?db='+dbName,
          data: deldata,
          asynch: true,
          success: function (data, status, xhr) {
              var delSyllable = ednVE.dataMgr.getEntity('scl',delSclID);
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                if (data.entities.update && data.entities.update.edn) {//edition has been updated so reread
                  if (ednVE.edition.id) {
                    ednVE.edition = ednVE.dataMgr.getEntity('edn',ednVE.edition.id);
                  }
                }
                if (data['newPhysSeqID']) {
                  ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
                }
                DEBUG.log("data","after deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
                if (data['newPhysLineSeqID']) {
                  DEBUG.log("data","after deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(data['newPhysLineSeqID'],0,1,ednVE.lookup.gra));
                } else {
                  DEBUG.log("data","after deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(linSeqID,0,1,ednVE.lookup.gra));
                }
                if (data['newTextSeqID']) {
                  ednVE.textSeq = ednVE.dataMgr.getEntity('seq',data['newTextSeqID']);
                }
                DEBUG.log("data","after deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
                if (data['newTextDivSeqGID']) {
                  DEBUG.log("data","after deleteSyllable sequence dump\n" + DEBUG.dumpSeqData(data['newTextDivSeqGID'].substring(4),0,1,ednVE.lookup.gra));
                }
                //if scl was linked inform other editors that the link is broken
                 if (delSyllable && delSyllable.segID) {
                   // send linkremoved message
                   $('.editContainer').trigger('linkRemoved',[ednVE.id,'scl'+delSclID,'seg'+delSyllable.segID]);
                 }
                //redraw edition
                ednVE.calcEditionRenderMappings();
                ednVE.renderEdition();
                VSE.invalidate();
                VSE.moveSyllable('prev',false);
                if (VSE.syllable) {
                  ednVE.refreshProperty(VSE.syllable.attr('class'));//update property editor
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Delete Syllable***");
                  DEBUG.log("health","Params: "+JSON.stringify(deldata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['error']) {
                alert("An error occurred while trying to save a syllable record. Error: " + data['error']);
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save Syllable. Error: " + error;
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
          }
      });// end ajax
  },


/**
* invalidate syllable editor to ensure proper re-initialisation
*/

  invalidate: function() {
    var VSE = this;
    VSE.invalid = true;
    VSE.curSyl = VSE.rawSyl;
    VSE.dirty = false;
  },


/**
* insert new syllable
*
* @param int sclID Reference syllable for insert position
* @param string alignment Direction of insert before or after reference
* @param string txt Characters use for syllable
* @param function cbError Callback to use in case of failure
*/

  insertNew: function(sclID, alignment, txt, cbError) {
    var VSE = this;
   //check if current syllable is dirty, if so save with callback to insert with sclID, alignment and text
   if (this.saving) {
       DEBUG.log("warn"," in save process unable to start insert new syllable, already saving");
       return;
   } else if (this.dirty) {
       this.saving = true;
       this.save(function(){
                    VSE.saving = false;
                    VSE.dirty = false;
                    VSE.insertNew(sclID,alignment,txt,cbError);
                  },
                  function(errStr) {
                    VSE.saving = false;
                    alert("Error from insert while trying to save current syllable - "+errStr);
                  });
       return;
   }

   var refSclID = sclID?sclID:this.sclID,
        ednVE = this.ednVE,
        insPos = alignment?alignment:(this.caretAtBoundary('left')?'before':'after'),
        //TODO: heuristics for calculation of alignment,
          //if selection then choose smaller diff (can be reverse) select centered or shifted left choose before
          // else choose after.
          // if caret  when less than or equal to center choose before else choose after.
          //get syllable data
          // context hierarchy, alignment, and text (full stop for now) but there may be other things later
          // like insert word or phase
        linSeqID = this.getLineSeqID(refSclID),
        insTxt = txt?txt:'.',
        sclNodes = $('.grpGra.scl'+this.sclID,this.contentDiv),
        sclElem = (insPos == "before" ? sclNodes.get(0) : sclNodes.get(sclNodes.length-1)),
        contxt = sclElem.className.replace(/grpGra/,"")
                                  .replace(/ord\d+/,"")
                                  .replace(/ordL\d+/,"")
                                  .replace(/seg\d+/,"")
                                  .replace(/firstLine/,"")
                                  .replace(/lastLine/,"")
                                  .replace(/selected/,"")
                                  .trim()
                                  .replace(/\s/g,","),
        newSclData={
          ednID: this.ednVE.edition.id,
          context: contxt,
          txtScl: insTxt,
          insPos: insPos,
          refSclGID: "scl:"+refSclID,
          lineSeqID: linSeqID
        };
        if (DEBUG.healthLogOn) {
          newSclData['hlthLog'] = 1;
        }
      DEBUG.log("data","before insertSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before insertSyllable sequence dump\n" + DEBUG.dumpSeqData(linSeqID,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before insertSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
      //call service for insertNew with callback to moveToSyllable for the new syllable with select all.
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/insertNewSyllable.php?db='+dbName,
          data: newSclData,
          asynch: true,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                if (data['newPhysSeqID']) {
                  ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
                }
                DEBUG.log("data","after insertSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
                if (data['newPhysLineSeqID']) {
                  DEBUG.log("data","after insertSyllable sequence dump\n" + DEBUG.dumpSeqData(data['newPhysLineSeqID'],0,1,ednVE.lookup.gra));
                } else {
                  DEBUG.log("data","after insertSyllable sequence dump\n" + DEBUG.dumpSeqData(linSeqID,0,1,ednVE.lookup.gra));
                }
                if (data['newTextSeqID']) {
                  ednVE.textSeq = ednVE.dataMgr.getEntity('seq',data['newTextSeqID']);
                }
                DEBUG.log("data","after insertSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
                if (data['newTextDivSeqGID']) {
                  DEBUG.log("data","after insertSyllable sequence dump\n" + DEBUG.dumpSeqData(data['newTextDivSeqGID'].substring(4),0,1,ednVE.lookup.gra));
                }
                //find new syllable id
                for (entID in data.entities.insert.scl) {
                  newSclID = entID;
                  break;
                }
                //redraw edition
                ednVE.calcEditionRenderMappings(); //todo access change to single line update
                ednVE.renderEdition();//todo access change to single line update
                //  VSE.reInit();
                VSE.moveToSyllable(newSclID,'selectAll');
                if (VSE.syllable) {
                  ednVE.refreshProperty(VSE.syllable.attr('class'));
                }
                if (data.editionHealth) {
                  DEBUG.log("health","***Insert Syllable***");
                  DEBUG.log("health","Params: "+JSON.stringify(newSclData));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['error']) {
                alert("An error occurred while trying to save a syllable record. Error: " + data['error']);
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save Syllable. Error: " + error;
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
          }
      });// end ajax
  },


/**
* save syllable
*
* @param function cbSuccess Callback for success case
* @param function cbError Callback for failure case
*/

  save: function(cbSuccess,cbError) {
  DEBUG.traceEntry("VSE save");
    if ( this.curSyl != this.rawSyl && //unchanged so ignore
        (this.savingHash || this.savingHash != this.sclID + this.rawSyl + this.curSyl)) {//ignore already in save process
      //save the syllable requires the context (edn, seq-tok-scl, prevGra) and newSyl graphemes.
      this.savingHash = this.sclID + this.rawSyl + this.curSyl;
      var VSE = this, ednID = VSE.ednVE.edition.id,
          ednVE = VSE.ednVE, lineOrdTag, headerNode, physLineSeqID,
          tNodes = VSE.textNodes.map(function(index,elem){return elem.textContent;}),
          prevTCM = null, cntGuard = 0, savedata={},prevGrapheme,
          prevNode = $(this.syllable.get(0)).prev(), prevCtxStrings = '', ctxStrings = [], i,
          contxt = VSE.syllable.map(function(index,elem){return elem.className.replace(/grpGra/,"")
                                                                                .replace(/ord\d+/,"")
                                                                                .replace(/ordL\d+/,"")
                                                                                .replace(/firstLine/,"")
                                                                                .replace(/lastLine/,"")
                                                                                .replace(/selected/,"")
                                                                                .trim()
                                                                                .replace(/\s/g,",");}),
          txtDivSeqID = contxt[0].split(",")[0].substring(3);
      for (i=0; i < contxt.length; i++) {// possible to have a split syllable.
        if (prevCtxStrings != contxt[i]) {
          ctxStrings.push(contxt[i]);
          prevCtxStrings = contxt[i];
        }
      }
      while ( !prevNode.hasClass("grpGra") && !prevNode.hasClass("textDivHeader") && cntGuard++ < 500) {
        prevNode = prevNode.prev();
      }
      lineOrdTag = VSE.syllable[0].className.match(/ordL\d+/)[0];
      headerNode = $('.textDivHeader.'+lineOrdTag,ednVE.contentDiv);
      physLineSeqID = headerNode.attr('class').match(/lineseq(\d+)/)[1];
      if (prevNode.hasClass("grpGra")) {//find the previous syllable's last grapheme's TCM
        sclID = prevNode.get(0).className.match(/scl(\d+)/);
        if (sclID) {
          sclID = sclID[1];
          graIDs = entities['scl'][sclID].graphemeIDs;
          if (graIDs & graIDs.length) {
            prevGrapheme = entities['gra'][graIDs[graIDs.length-1]];
            if (prevGrapheme) {
              prevTCM = entities['gra'][graIDs[graIDs.length-1]].txtcrit;
            }
          }
        }
      }
      // if not owned then save raw to new.
      DEBUG.log("gen","in code to calculate save data with origStr "+VSE.rawSyl+" and curStr "+VSE.curSyl);
      DEBUG.log("data","before saveSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before saveSyllable sequence dump\n" + DEBUG.dumpSeqData(physLineSeqID,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before saveSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
      DEBUG.log("data","before saveSyllable sequence dump \n" + DEBUG.dumpSeqData(txtDivSeqID,0,1,ednVE.lookup.gra));
      savedata={
        ednID: ednID,
        prevTCM: prevTCM?prevTCM:"",
        //todo include tNodes to show separation for multiple tokens case
        context: ctxStrings, // TODO need code to handle multiple context of split scl case has 2 tokens
        seqID: physLineSeqID,
        refSclGID: "scl:"+ this.sclID,
        origSyl: this.rawSyl,
        newSyl: this.curSyl
      };
      if (this.strPreSplit) {
        savedata['strPre'] = this.strPreSplit;
      }
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: basepath+'/services/saveSyllable.php?db='+dbName,
          data: savedata,
          asynch: true,
          success: function (data, status, xhr) {
              var sclIDSaved;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                ednVE.dataMgr.updateLocalCache(data,ednVE.edition.txtID);
                if (data.entities.update && data.entities.update.edn) {//edition has been updated so reread
                  if (ednVE.edition.id) {
                    ednVE.edition = ednVE.dataMgr.getEntity('edn',ednVE.edition.id);
                  }
                }
                if (data['newPhysSeqID']) {
                  ednVE.physSeq = ednVE.dataMgr.getEntity('seq',data['newPhysSeqID']);
                }
                DEBUG.log("data","after saveSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.physSeq.id,0,1,ednVE.lookup.gra));
                if (data['newPhysLineSeqID']) {
                  DEBUG.log("data","after saveSyllable sequence dump\n" + DEBUG.dumpSeqData(data['newPhysLineSeqID'],0,1,ednVE.lookup.gra));
                } else {
                  DEBUG.log("data","after saveSyllable sequence dump\n" + DEBUG.dumpSeqData(physLineSeqID,0,1,ednVE.lookup.gra));
                }
                if (data['newTextSeqID']) {
                  ednVE.textSeq = ednVE.dataMgr.getEntity('seq',data['newTextSeqID']);
                }
                DEBUG.log("data","after saveSyllable sequence dump\n" + DEBUG.dumpSeqData(ednVE.textSeq.id,0,1,ednVE.lookup.gra));
                if (data['newTextDivSeqGID']) {
                  DEBUG.log("data","after saveSyllable sequence dump\n" + DEBUG.dumpSeqData(data['newTextDivSeqGID'].substring(4),0,1,ednVE.lookup.gra));
                }
                if (data.entities.insert && data.entities.insert.scl) {
                  for (sclIDSaved in data.entities.insert.scl) {//find first
                    break;
                  }
                } else if (data.entities.update && data.entities.update.scl) {
                  for (sclIDSaved in data.entities.update.scl) {//find first
                    break;
                  }
                }
                DEBUG.trace("VSE.save successHandler","saved slc "+sclIDSaved);
                //redraw edition
                ednVE.calcEditionRenderMappings();//TODO optimize to redraw line or 2
                ednVE.renderEdition();//TODO optimize to redraw line or 2
                VSE.dirty = false;
                if (cbSuccess) {
                  cbSuccess(sclIDSaved);
                } else {
                  ednVE.sclEd.reInit();
                }
                if (VSE.syllable) { //refresh property panel at selected level
                  ednVE.refreshProperty(VSE.syllable.attr('class'));
                }
                if (data.editionHealth) {
                  DEBUG.log("health","*** Save Syllable ***");
                  DEBUG.log("health","Params: "+JSON.stringify(savedata));
                  DEBUG.log("health",data.editionHealth);
                }
              }else if (data['error']) {
                alert("An error occurred while trying to save a syllable record. Error: " + data['error']);
              }
              if (data.warnings) {
                DEBUG.log("warn", "warnings during save syllable - " + data.warnings.join(" : "));
              }
              VSE.saving = false;
              delete VSE.savingHash;
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              errStr = "An error occurred while trying to call save Syllable. Error: " + error;
              if (cbError) {
                cbError(errStr);
              } else {
                alert(errStr);
              }
              DEBUG.trace("VSE.save errorHandler",errStr);
              VSE.saving = false;
              delete VSE.savingHash;
          }
      });// end ajax
    }
//    DEBUG.log("gen""call to save end with origStr "+this.rawSyl+" and curStr "+this.curSyl);
  DEBUG.traceExit("VSE save");
  },


/**
* move syllable - save current if dirty
*
* @param string direction Direction of move next or prev
* @param boolean skipSynch Indicator to run sych code
*
* @returns boolean True for success or false for failure
*/

  moveSyllable: function(direction,skipSynch) {
    var isPrev = direction == "prev",
        VSE = this,
        prevSyllable, nextSyllable;
    DEBUG.log("event","call to move syllable "+ direction +
                (skipSynch?" skipping synch":"") +
                " with origStr "+VSE.rawSyl+" and curStr "+VSE.curSyl);
    //if no syllable in direction beep otherwise move editor to first character of next or
    //last character of previous syllable according to direction
    if (isPrev && this.ordFirst == 1) {
        UTILITY.beep();
        DEBUG.log("warn","BEEP! At first syllable of text cannot move before syllable.");
        return true;
    }
    if (isPrev) {
      prevSyllable = $('.grpGra.ord'+( -1 + parseInt(VSE.ordFirst)),VSE.contentDiv);
      if (!prevSyllable.length) { //might have erased ordinal so try 2
        prevSyllable = $('.grpGra.ord'+(-2 + parseInt(VSE.ordFirst)),VSE.contentDiv);
      }
      if (prevSyllable && prevSyllable.hasClass("grpGra")) { // found syllable
        if (VSE.curSyl != VSE.rawSyl && VSE.dirty) {
          VSE.save( function () {
                      VSE.init(prevSyllable,"last",false);
                      });
        } else {
          VSE.init(prevSyllable,"last",skipSynch);
        }
      } else if (VSE.curSyl != VSE.rawSyl && VSE.dirty) {
          VSE.save( function () {
                      VSE.init(VSE.syllable,"last",false);
                      });
      } else {
        DEBUG.log("err","error handling move prev no syllable found before '"+VSE.curSyl+"'");
      }
    } else {
      nextSyllable = $('.grpGra.ord'+(1 + parseInt(VSE.ordLast)),VSE.contentDiv);
      if (!nextSyllable.length) { //might have erased ordinal so try 2
        nextSyllable = $('.grpGra.ord'+(2 + parseInt(VSE.ordLast)),VSE.contentDiv);
      }
      if (nextSyllable && nextSyllable.hasClass("grpGra")) { // found syllable
        if (this.curSyl != VSE.rawSyl && VSE.dirty && !VSE.invalid) {
          VSE.save( function () {
                      VSE.init(nextSyllable,"first",false);
                      });
        } else {
          VSE.init(nextSyllable,"first",skipSynch);
        }
      } else if (VSE.curSyl != VSE.rawSyl && VSE.dirty) {
          this.VSE( function () {
                      VSE.init(VSE.syllable,"endOfSyllable",false);
                      });
      } else if (VSE.ednVE.autoInsert) {
        this.insertNew();
      } else {
        UTILITY.beep();
        DEBUG.log("err","error handling move next no syllable found after '"+VSE.curSyl+"'");
      }
    }
    return true;
  },


/**
* move to a specified syllable
*
* @param int sclID Syllable cluster entity id of target syllable
* @param string cursorType Identified cursor beginning, end or select all
*/

  moveToSyllable: function(sclID, cursorType) {
    DEBUG.traceEntry("moveToSyllable","id = "+sclID+" cursorType = "+cursorType)
    var entities = this.dataMgr.entities;
    if (sclID && $('.grpGra.scl'+sclID,this.contentDiv).length > 0) {
      this.sclID =sclID;
      this.rawSyl = this.curSyl = this.computeSyllable();
      this.initTime = (new Date()).getTime();
      this.graphemeIDs = entities['scl'][this.sclID].graphemeIDs;
      this.dirty = false;
      $(".selected",this.contentDiv).removeClass("selected");
      this.syllable.addClass("selected");
      var segIDs = [], range, sel;
      if (entities['scl'] && entities['scl'][this.sclID] && entities['scl'][this.sclID].segID) {
        segIDs.push('seg'+entities['scl'][this.sclID].segID);
        $('.editContainer').trigger('updateselection',[this.ednVE.id,segIDs]);
      }
      if (cursorType) {//need to set cursor
        if (window.getSelection) {
          sel = window.getSelection();
          if (sel.getRangeAt && sel.rangeCount) {
            range = sel.getRangeAt(0);
          }
        } else if (document.selection && document.selection.createRange) {
          range = document.selection.createRange();
        }
        if (range) {
          if (cursorType == "caretAtStart") {// move both anchor and cursor
            this.anchorPos = this.cursorPos = 0;
            range.setStart(this.firstTextNode,0);
            range.setEnd(this.firstTextNode,0);
            DEBUG.log("gen","changing range start and end of '"+this.curSyl+"' to start");
          } else if (cursorType == "caretAtEnd") {// move both anchor and cursor
            this.anchorPos = this.cursorPos = this.curSyl.length;
            DEBUG.trace("moveSyl","moving anchor and cursor of '"+this.curSyl+"' to end "+this.anchorPos);
            range.setStart(this.lastTextNode,this.lastTextNode.length);
            range.setEnd(this.lastTextNode,this.lastTextNode.length);
            DEBUG.log("gen","changing range start and end of '"+this.curSyl+"' to end");
          } else if (cursorType == "selectAll") {// move both anchor and cursor
            this.anchorPos = 0;
            this.cursorPos = this.curSyl.length;
            range.setStart(this.firstTextNode,0);
            range.setEnd(this.lastTextNode,this.lastTextNode.length);
            DEBUG.log("gen","changing range start and end of '"+this.curSyl+"' to select all");
          }
          if (sel) {
            sel.removeAllRanges();
            sel.addRange(range);
          }
        }
      }
      this.calculateState();
      this.syllable.focus();
      $(this.contentDiv).focus();
    } else {
      DEBUG.log("err","error trying to move syllable editor without syllable indicated");
    }
    DEBUG.traceExit("moveToSyllable","id = "+sclID+" cursorType = "+cursorType)
  },


/**
* move cursor adjust the cursor within a syllable
*
* @param string direction indentifies direction of cursor movement
* @param boolean isSelect
*
* @returns boolean true or false
*/

  moveCursor: function(direction, isSelect) {
    DEBUG.traceEntry("moveCursor"," dir = "+ direction +" isSelect = "+ isSelect +" with origStr "+this.rawSyl+" and anchorPos"+this.anchorPos);
    var reverse = this.cursorPos < this.anchorPos,//was reverse case
        isLeft = (direction == "left"),
        childNodes, i, pos, posOldCur, sel, range, rangeChanged = false;
    //if cursor is at end and direction to move beyond end then move to previous syllable or next syllable
    //for selection just beep
    if ( isLeft && this.cursorPos == 0 || !isLeft && this.cursorPos == this.curSyl.length) {
      if (isSelect) {
        DEBUG.log("warn","BEEP! At "+ (isLeft?"beginning":"end") +" of syllable cannot select outside of syllable in modify mode.");
        return true;
      }
      return this.moveSyllable(isLeft?"prev":"next");
    }
    posOldCur = this.cursorPos;
    this.cursorPos += isLeft?-1:1;
    if (!isSelect) {
      this.anchorPos = this.cursorPos;
    }
    pos = this.cursorPos;
    // set screen range selection to match syllable position
    childNodes = this.textNodes;
    //run through each node counting positions to id childnode and offset.
    for(i=0; i < childNodes.length; i++){
      if (childNodes[i].textContent.length < pos) {//pos is not in this childnode
        pos -= childNodes[i].textContent.length;
      } else {//found child and pos is offset
        // if cursor at split
        if (this.isSplitSyllable() && i == this.beforeSplitIndex && childNodes[i].textContent.length == pos) {
          //if moving cursor to split, exclude split as split constitutes 2 locations for the same position.
          if (isLeft) {
            i++;
            pos = 0;
          }
        }
        if (window.getSelection) {
          sel = window.getSelection();
          if (sel.getRangeAt && sel.rangeCount) {
            range = sel.getRangeAt(0);
          }
        } else if (document.selection && document.selection.createRange) {
          range = document.selection.createRange();
        }
        this.cursorNodePos = pos;
        this.cursorIndex = i;
        DEBUG.log("gen","moving cursor from "+posOldCur+" to "+this.cursorPos+" with anchor at "+ this.anchorPos);
        if (range) {
          if (!isSelect || this.anchorPos == this.cursorPos) {// move both anchor and cursor
            this.anchorNodePos = this.cursorNodePos;
            this.anchorIndex = this.cursorIndex;
            DEBUG.trace("moveCurso","moving anchor and cursor of '"+this.curSyl+"' to "+this.anchorPos);
            range.setStart(childNodes[i],pos);
            range.setEnd(childNodes[i],pos);
            DEBUG.log("gen","changing range start and end of '"+childNodes[i].textContent+"' to "+pos);
          } else if (reverse || this.cursorPos < this.anchorPos) {//cursor is start
            range.setStart(childNodes[i],pos);
            DEBUG.log("gen","changing range start of '"+childNodes[i].textContent+"' to "+pos);
          } else {//cursor is end
            range.setEnd(childNodes[i],pos);
            DEBUG.log("gen","changing range end of '"+childNodes[i].textContent+"' to "+pos);
          }
          rangeChanged = true;
        }
        break;
      }
    }
    if (sel && rangeChanged) {
      sel.removeAllRanges();
      sel.addRange(range);
    }
    this.calculateState();
    DEBUG.traceExit("moveCursor"," dir = "+ direction +" isSelect = "+ isSelect +" with origStr "+this.rawSyl+" and anchorPos"+this.anchorPos+"  "+this.state);
    return true;
  },


/**
* replace syllable text
*
* @param string txt Replacement text
* @param string mode Identifies type of replacement which can be insert
* @param string cursorMode Identifies what type of cursor after completion
*/

  replaceText: function(txt,mode,cursorMode) {
    DEBUG.traceEntry("replace text"," with '"+txt+"' mode "+mode+"' curmode "+cursorMode);
    var childNodes = this.textNodes, startNode, endNode,startNodeIndex,startNodeOffset,
        i, tmp, sel, range, selectLen, start,end, anchorOffset = 0;
    if (childNodes.length == 1) {//single text node case
      if (mode == "all") {// replace all so just text change text
        this.firstTextNode.textContent = txt;
        start = 0;
        end = txt.length;
      } else if (mode == "augment") {// replace selection with text and keep unselected text and highlite fullstop
        this.firstTextNode.textContent = this.firstTextNode.textContent.substring(0,this.anchorPos < this.cursorPos?this.anchorPos:this.cursorPos) +
                                         txt + this.firstTextNode.textContent.substring(this.anchorPos < this.cursorPos?this.cursorPos:this.anchorPos);
        //set selection around full stop
        start = this.firstTextNode.textContent.indexOf(".");
        if (start == -1) {
          return;
        }
        end = parseInt(start) + 1;
      } else if (mode == "selected") { // replace selected text
        start = this.anchorPos < this.cursorPos?this.anchorPos:this.cursorPos;
        end = parseInt(start) + txt.length;
        tmp = this.firstTextNode.textContent.substring(0,start) + txt;
        tmp += this.firstTextNode.textContent.substring(this.anchorPos > this.cursorPos?this.anchorPos:this.cursorPos);
        this.firstTextNode.textContent = tmp;
      } else if (mode == "left") { // replace character to left of cursor
        start = this.cursorPos - 1;
        end = parseInt(start) + txt.length;
        tmp = this.firstTextNode.textContent.substring(0,start) + txt;
        tmp += this.firstTextNode.textContent.substring(this.cursorPos);
        this.firstTextNode.textContent = tmp;
      } else if (mode == "right") { // replace character to right of cursor
        start = this.cursorPos;
        end = parseInt(start) + txt.length;
        tmp = this.firstTextNode.textContent.substring(0,this.cursorPos) + txt;
        tmp += this.firstTextNode.textContent.substring(this.cursorPos+1);
        this.firstTextNode.textContent = tmp;
      } else if (mode == "insert") { // insert character at cursor
        start = parseInt(this.cursorPos) + txt.length;
        end = start ;
        tmp = this.firstTextNode.textContent.substring(0,this.cursorPos) + txt;
        tmp += this.firstTextNode.textContent.substring(this.cursorPos);
        this.firstTextNode.textContent = tmp;
      }
      this.dirty = true;
      if (cursorMode) {
        switch (cursorMode) {
          case "select":
            break;
          case "before":
            end = start;
            break;
          case "after":
            start = end;
            break;
          case "start":
            start = 0;
            end = 0;
            break;
          case "end":
            start = end = this.lastTextNode.textContent.length;
            break;
        }
        if (window.getSelection) {
          sel = window.getSelection();
          if (sel.getRangeAt && sel.rangeCount) {
            range = sel.getRangeAt(0);
          }
        } else if (document.selection && document.selection.createRange) {
          range = document.selection.createRange();
        }
        if (range) {
          range.setStart(this.firstTextNode,start);
          range.setEnd(this.firstTextNode,end);
          if (sel) {
            sel.removeAllRanges();
            sel.addRange(range);
          }
        }
        this.anchorPos = start;
        DEBUG.trace("replaceText",mode+" mode single node, set anchor to start of '"+this.curSyl+"' "+this.anchorPos);
        this.cursorPos = end;
        this.calculateState();
        this.syllable.focus();
        $(this.contentDiv).focus();
      }
    } else { // multiple text nodes and/or TCM case
//      DEBUG.log("warn","BEEP! VSE Replace text for syllable with embedded TCMs not implemented yet!");
      if (this.anchorPos == this.cursorPos) { // cursor only
          startNodeIndex = this.anchorIndex;
          startNodeOffset = this.anchorNodePos;
      } else if (this.beforeSplitIndex >=0) {
        if (this.anchorPos < this.cursorPos) { //forward selection
          if (this.anchorPos == this.strPreSplit.length) { //anchor at split so start at right side
            startNodeIndex = 1 + this.beforeSplitIndex;
            startNodeOffset = 0;
          } else {
            startNodeIndex = this.anchorIndex;
            startNodeOffset = this.anchorNodePos;
          }
        } else {
          if (this.cursorPos == this.strPreSplit.length) { //anchor at split so start at right side
            startNodeIndex = 1 + this.beforeSplitIndex;
            startNodeOffset = 0;
          } else {
            startNodeIndex = this.cursorIndex;
            startNodeOffset = this.cursorNodePos;
          }
        }
      } else {
        startNodeIndex = this.anchorIndex;
        startNodeOffset = this.anchorNodePos;
      }
      selectLen = this.cursorPos - this.anchorPos;
      if (selectLen < 0) { //reverse selection
//        startNodeOffset = startNodeOffset + selectLen;
//        if (startNodeOffset < 0 ) {
//          DEBUG.log('err',"startNodeOffset is smaller than select length on reverse select");
//          startNodeOffset = 0;
//        }
        selectLen = -selectLen;
      }
//      return;
      if (mode == "all") {// replace all so remove all but first node and change text
        this.firstTextNode.textContent = txt;
        startNode = endNode = this.lastTextNode = this.firstTextNode;
        start = 0;
        end = txt.length;
      } else if (mode == "augment") {// replace selection with text and keep unselected text and highlite fullstop
        // find selection start node, remove selected portion and add text.
        for (i=0;i<this.textNodes.length; i++) {
          // if index is less than start node index then skip
          if ( i < startNodeIndex) {
            anchorOffset += this.textNodes[i].textContent.length;
            continue;
          } else if (i == startNodeIndex) {
            startNode = this.textNodes[i];
            //from startnode beginning to startnode offset retain characters and add text
            tmp = startNode.textContent.substring(0,startNodeOffset) + txt;
            //grab any leftover startnode characters, note substr start pos greater than string returns empty
            tmp += startNode.textContent.substring(startNodeOffset + selectLen);
            selectLen -= (startNode.textContent.length - startNodeOffset);
            //if new string is empty, remove startnode
            if (tmp.length == 0) {
              $(startNode).parent().remove();
              startNode = null;
            } else { //set new text to startnode content
              startNode.textContent = tmp;
            }
          } else if ( selectLen > 0 ) {// remove any other node portions, including entire nodes.
            if ( selectLen >= this.textNodes[i].length){// node entirely in selection so remove it
              if ( i == (this.textNodes.length -1)) {//last node removal so update this.lastNode
                this.lastTextNode = this.textNodes[i-1];
              }
              if ($(this.textNodes[i]).parent().prev().hasClass('TCM') &&
                  $(this.textNodes[i]).parent().prev().hasClass('scl'+this.sclID) &&
                  $(this.textNodes[i]).parent().next().hasClass('scl'+this.sclID) &&
                  $(this.textNodes[i]).parent().next().hasClass('TCM')) { // surrounded by intra syl TCMs
                $(this.textNodes[i]).parent().prev().remove();
                $(this.textNodes[i]).parent().next().remove();
              }
              $(this.textNodes[i]).parent().remove();
            } else { //remove text up to the remaining selection length
              this.textNodes[i].textContent = this.textNodes[i].textContent.substring(selectLen);
            }
            selectLen -= this.textNodes[i].length;
          }
        }
        if (!startNode) {//removed start node so move selection to adjacent node
          if (startNodeIndex > 0) {//prefer previous
            startNode = this.textNodes[--startNodeIndex];
            startNodeOffset = startNode.textContent.length;
          } else {//at first so use next adjacent
            startNode = this.textNodes[1];
            startNodeOffset = 0;
          }
        }
        //set selection around full stop of the selection start node
        start = startNode.textContent.indexOf(".");
        endNode = startNode;
        if (start == -1) {
          return;
        }
        end = start + 1;
      } else if (mode == "selected") { // replace selected text
        // find selection start node, remove selected portion and add text.
        for (i=0;i<this.textNodes.length; i++) {
          // if index is less than start node index then skip
          if ( i < startNodeIndex) {
            continue;
          } else if (i == startNodeIndex) {
            startNode = this.textNodes[i];
            //from startnode beginning to startnode offset retain characters and add text
            tmp = startNode.textContent.substring(0,startNodeOffset) + txt;
            //grab any leftover startnode characters
            tmp += startNode.textContent.substring(startNodeOffset + selectLen);
            selectLen -= (startNode.textContent.length - startNodeOffset);
            //if new string is empty, remove startnode
            if (tmp.length == 0) {
              $(startNode).parent().remove();
              startNode = null;
            } else { //set new text to startnode content
              startNode.textContent = tmp;
            }
          } else if ( selectLen > 0 ) {// remove any other node portions, including entire nodes.
            if ( selectLen >= this.textNodes[i].length){// node entirely in selection so remove it
              if ( i == (this.textNodes.length -1)) {//last node removal so update this.lastNode
                this.lastTextNode = this.textNodes[i-1];
              }
              if ($(this.textNodes[i]).parent().prev().hasClass('TCM') &&
                  $(this.textNodes[i]).parent().prev().hasClass('scl'+this.sclID) &&
                  $(this.textNodes[i]).parent().next().hasClass('scl'+this.sclID) &&
                  $(this.textNodes[i]).parent().next().hasClass('TCM')) { // surrounded by intra syl TCMs
                $(this.textNodes[i]).parent().prev().remove();
                $(this.textNodes[i]).parent().next().remove();
              }
              $(this.textNodes[i]).parent().remove();
              selectLen -= this.textNodes[i].length;
              delete this.textNodes[i];
            } else { //remove up the remaining selection length
              this.textNodes[i].textContent = this.textNodes[i].textContent.substring(selectLen);
              selectLen = 0;
            }
          }
        }
        this.syllable = $('.grpGra.scl'+this.sclID,this.contentDiv);
        if (!startNode) {//removed start node so move selection to adjacent node
          if (startNodeIndex > 0) {//prefer previous
            startNode = this.textNodes[--startNodeIndex];
            startNodeOffset = startNode.textContent.length;
          } else {//at first so use next adjacent
            startNode = this.textNodes[1];
            startNodeOffset = 0;
          }
        }
        endNode = startNode;
        start = startNodeOffset;
        end = startNodeOffset + txt.length;
      } else if (mode == "left") { // replace character to left of cursor
        //if offset is zero remove last character of previous node
        if (startNodeOffset == 0) {
          startNodeIndex--;
          startNodeOffset = this.textNodes[startNodeIndex].textContent.length - 1
        } else {
          startNodeOffset--;
        }
        startNode = this.textNodes[startNodeIndex];
        //from startnode beginning to startnode ofset retain characters and add text
        tmp = startNode.textContent.substring(0,startNodeOffset) + txt;
        //grab any leftover startnode characters
        tmp += startNode.textContent.substring(startNodeOffset + 1);
        if (tmp.length == 0) {
          $(startNode).parent().remove();
          startNode = null;
        } else { //set new text to startnode content
          startNode.textContent = tmp;
        }
        if (!startNode) {//removed start node so move selection to adjacent node
          if (startNodeIndex > 0) {//prefer previous
            startNode = this.textNodes[--startNodeIndex];
            startNodeOffset = startNode.textContent.length;
          } else {//at first so use next adjacent
            startNode = this.textNodes[1];
            startNodeOffset = 0;
          }
        }
        endNode = startNode;
        start = startNodeOffset;
        end = startNodeOffset + txt.length;
      } else if (mode == "right") { // replace character to right of cursor
        startNode = this.textNodes[startNodeIndex];
        //if offset is node length remove first character of next node
        if (startNodeOffset == startNode.textContent.length) {
          //choose next text node
          if (startNodeIndex < (-1+this.textNodes.length)) {
            startNodeIndex++;
            startNode = this.textNodes[startNodeIndex];
            startNodeOffset = 0;
          }//else leave at the end and just add txt
        }
        //from startnode beginning to startnode offset retain characters and add text
        tmp = startNode.textContent.substring(0,startNodeOffset) + txt;
        //grab any leftover startnode characters
        tmp += startNode.textContent.substring(startNodeOffset + 1);
        if (tmp.length == 0) {
          $(startNode).parent().remove();
          startNode = null;
        } else { //set new text to startnode content
          startNode.textContent = tmp;
        }
        if (!startNode) {//removed start node so move selection to adjacent node
          if (startNodeIndex > 0) {//prefer previous
            startNode = this.textNodes[--startNodeIndex];
            startNodeOffset = startNode.textContent.length;
          } else {//at first so use next adjacent
            startNode = this.textNodes[1];
            startNodeOffset = 0;
          }
        }
        endNode = startNode;
        start = startNodeOffset;
        end = startNodeOffset + txt.length;
      } else if (mode == "insert") { // insert character at cursor
        startNode = this.textNodes[startNodeIndex];
        //from startnode beginning to startnode offset retain characters and add text
        tmp = startNode.textContent.substring(0,startNodeOffset) + txt;
        //grab any leftover startnode characters
        tmp += startNode.textContent.substring(startNodeOffset);
        if (tmp.length == 0) {
          $(startNode).parent().remove();
          startNode = null;
        } else { //set new text to startnode content
          startNode.textContent = tmp;
        }
        if (!startNode) {//removed start node so move selection to adjacent node
          if (startNodeIndex > 0) {//prefer previous
            startNode = this.textNodes[--startNodeIndex];
            startNodeOffset = startNode.textContent.length;
          } else {//at first so use next adjacent
            startNode = this.textNodes[1];
            startNodeOffset = 0;
          }
        }
        endNode = startNode;
        start = startNodeOffset;
        end = startNodeOffset + txt.length;
      }
      this.dirty = true;
      if (cursorMode) {
        switch (cursorMode) {
          case "select":
            //set start and end select node
            //check start and end are in range for new nodes.
            break;
          case "before":
            //set start and end select node
            end = start;
            break;
          case "after":
            //set start and end select node
            start = end;
            break;
          case "start":
            //set start and end select node
            endNode = startNode = this.firstTextNode;
            start = 0;
            end = 0;
            break;
          case "end":
            //set start and end select node
            endNode = startNode = this.lastTextNode;
            start = end = this.lastTextNode.textContent.length;
            break;
        }
        if (window.getSelection) {
          sel = window.getSelection();
          if (sel.getRangeAt && sel.rangeCount) {
            range = sel.getRangeAt(0);
          }
        } else if (document.selection && document.selection.createRange) {
          range = document.selection.createRange();
        }
        if (range) {
          range.setStart(startNode,start);
          range.setEnd(endNode,end);
          if (sel) {
            sel.removeAllRanges();
            sel.addRange(range);
          }
        }
        // recalculate position for new text
        this.anchorPos = parseInt(start) + parseInt(anchorOffset);//error needs to include previous node char count
        DEBUG.trace("replaceText",mode+" mode milti-node, set anchor to start+offset of '"+this.curSyl+"' = "+this.anchorPos);
        this.cursorPos = parseInt(end) + parseInt(anchorOffset);//error
        this.calculateState();
        this.syllable.focus();
        $(this.contentDiv).focus();
      }
    }
    DEBUG.traceExit("replace text"," with '"+txt+"' mode "+mode+"' curmode "+cursorMode);
  },


/**
* process key before system action
*
* @param string key identifying keypressed
* @param boolean ctrl indicates control key is pressed
* @param boolean shift indicates shift key is pressed
* @param boolean alt indicates alt key is pressed
* @param object e event object from system
*
* @returns boolean | string Indicates whether to cancel default action or error
*/

  preprocessKey: function(key,ctrl,shift,alt,e) {
    var keyType = key?this._keyTypeMap[key.toLowerCase()]:null,
        posV = this.state.indexOf("V"),
        posA = this.state.indexOf("A"),
        posS = this.state.indexOf("S"),
        posT = this.state.indexOf("T"),
        posC = this.state.indexOf("C"),
        posM = this.state.indexOf("M"),
        posSelStart = this.state.indexOf(">"),
        posSelEnd = this.state.indexOf("<"),
        posStart = Math.min(posSelStart,posSelEnd),
        posEnd = Math.max(posSelStart,posSelEnd),
        posLastM = this.state.lastIndexOf("M"),
        posLastV = this.state.lastIndexOf("V"),
        posLastH = this.state.lastIndexOf("H"),
        posLastC = this.state.lastIndexOf("C"),
       // selection = this.anchorPos != this.cursorPos;
        selection = ((posEnd - posStart) > 1);
        if (selection && posS > -1) { //split syllable selection
          if (posStart == (posS - 1)) { // adjust start to position split, fake a swap
            posStart = posS;
            posS--;
          } else if (posEnd == (posS + 1)) {
            posEnd = posS;
            posS++;
          }
        } 
    DEBUG.log("gen","preprocess key '"+key+"' keyType "+keyType+" cursyl "+this.curSyl+" state "+this.state+" posV "+posV
                +" posSelStart "+posSelStart+" posSelEnd "+posSelEnd+" posStart "+posStart+" posEnd "+posEnd+" posS "+posS);
/****************************Special Control Keys************************************/
    if (key == "z" && ctrl) {
      DEBUG.log("warn","BEEP! Undo not implemented yet!");
      UTILITY.beep();
      return false;
    }
//    if (key == "c" && ctrl) {
//      DEBUG.log("warn","BEEP! Copy not implemented yet!");
      //return false;
//    }
    if (key == "x" && ctrl) {
      DEBUG.log("warn","BEEP! Cut not implemented yet!");
      UTILITY.beep();
      return false;
    }
//    if (key == "v" && ctrl) {
//      DEBUG.log("warn","BEEP! VSE Paste not implemented yet!");
      //return false;
//    }
    if (key == "Backspace" || key == "Del" || key == "Delete") {
      //**********selection cases**********
      if (selection) {
        // split syllable case not allowed
        if (this.isSplitSyllable() && posStart < posS && posS < posEnd) {
          DEBUG.log("warn","BEEP! Deleting of split syllable not currently allowed!");
          UTILITY.beep();
          return "error";
        } else if ( posStart == 0 && posEnd == this.state.length - 1 ) {// fully selected
          //vowel only cases
          if (posV > -1 && posM == -1 && posLastC == -1 && posLastH == -1) {
            if (this.curSyl == ".") {//fullstop
              //degrade to ?
              this.replaceText("?","all","select");
              return false;
            } else { // normal vowel
              //degrade to full stop
              this.replaceText(".","all","select");
              return false;
            }
          } else if (this.curSyl == "?") {//question mark
            //degrade to +
            this.replaceText("+","all","select");
            return false;
          } else if (this.curSyl == "+") {//plus
            //delete for now just stop
            DEBUG.log("warn","BEEP! Syllable delete not implemented yet!");
            return "error";
          } else {//default
            //degrade to ?
            this.replaceText("?","all","select");
            return false;
          }
        } else if (posStart < posV && posV < posEnd) {//vowel in selection
          if (this.curSyl.indexOf(".") > -1 && //fullstop
              posStart == (posV - 1) && posV == (posEnd - 1)) { //only full stop selected
            DEBUG.log("warn","BEEP! deleting vowel placeholder not allowed for partial selection like "+this.curSyl+" with state "+this.state);
            return "error";
          } else {//replace selection with selected full stop
            this.replaceText(".","selected","select");
            return false;
          }
        } else if (posStart < posM && posM < posEnd && posT < posEnd) {// modifier only since no vowel in selection, include virāma if exist
          this.replaceText("","selected","end");
          return false;
        } else if (posEnd < posV) {// consonants only since no vowel in selection
          //selected all characters before split
          if (this.isSplitSyllable() &&  posStart == posC-1 && posEnd == Math.max(posLastC,posLastH)+1 && posS == posEnd+1) {
            DEBUG.log("warn","BEEP! Deleting of all characters before split on a split syllable is currently not allowed");
            UTILITY.beep();
            return "error";
          }
          this.replaceText("","selected","before");
          return false;
        }
      } else {//*********cursor cases**********
        //at syllable beginning
        if (posStart == 0 && key == "Backspace") {
            DEBUG.log("warn","BEEP! At beginning of syllable so nothing to backspace over. Move left to edit previous syllable.");
            return "error"; // at beginning back space not allowed
        } else if (posEnd == this.state.length - 1 && key == "Del") {
            DEBUG.log("warn","BEEP! At end of syllable so nothing to delete. Move right to edit next syllable.");
            return "error";
        } else if ((this.isCursorAtSplit("right") && key == "Backspace") ||
                   (this.isCursorAtSplit("left") && key == "Del")) {
          //call combine Token on editionVE
          this.ednVE.combineTokens((key == "Del")?"next":"prev",null);
//          alert("split syllable tokenCombine underconstruction");
          return false;
        } else if (false && this.isSplitSyllable() && ( key == "Backspace" || key == "Del")) {
          UTILITY.beep();
          DEBUG.log("warn","BEEP! Deleting of characters on a split syllable is currently not allowed");
          return "error";
        } else if (posEnd == posV - 1 && key == "Del" && posV == posLastV && posA == -1) {// at left of vowel with no aspirate
          if (this.curSyl[posV-2] == ".") {// fullstop cases "><."
            if (this.curSyl.length == 1) { // single full stop
              this.replaceText("?","right","select");
              return false;
            } else {
              UTILITY.beep();
              DEBUG.log("warn","BEEP! Cannot delete a full stop with other characters. Remove other characters first.");
              return "error";
            }
          }
          this.replaceText(".","right","select");
          return false;
        } else if (posStart == posV+1 && key == "Backspace" && posV == posLastV && posA == -1) {// at right of vowel with no aspirate
          if (this.curSyl[posV] == ".") {// fullstop cases
            if (this.curSyl.length == 1) { // single full stop
              this.replaceText("?","left","select");
              return false;
            } else {
              UTILITY.beep();
              DEBUG.log("warn","BEEP! Cannot backspace over a full stop. Remove other characters first.");
              return "error";
            }
          }
          this.replaceText(".","left","select");
          return false;
        } else if (key == "Del") {
          if (this.isSplitSyllable() && posStart == 0 && posC == posLastC) { // split and single consonant so trying to backspace single consonant
            DEBUG.log("warn","BEEP! Deleting of characters on a split syllable is currently not allowed");
            return "error";
          }
          if ((posT > posM ) && (posEnd >= posM-1) && (posEnd < posT)) {//modifier with virāma
            if (posEnd == posM-1) { // before modifier
              this.replaceText("","right","select");
            } else {
              this.replaceText("","left","select");
            }
          }
          this.replaceText("","right","select");
          return false;
        } else if (key == "Backspace") {
          if (this.isSplitSyllable()) {
            if ( posStart == 1 && posS == 2) {
              DEBUG.log("warn","BEEP! Deleting of characters on a split syllable is currently not allowed");
              UTILITY.beep();
              return "error";
            }
            if (this.isCursorAtSplit("left") && posC == posLastC && posStart == posLastC + 1) { // split and single consonant so trying to backspace single consonant
              DEBUG.log("warn","BEEP! Deleting of last consonant on a split syllable is currently not allowed");
              UTILITY.beep();
              return "error";
            }
          }
          if ((posT > posM) && (posStart > posM) && (posStart <= posT+1)) {//modifier with virāma
            if (posStart == posT + 1) { // after virāma
              this.replaceText("","left","select");
            } else {
              this.replaceText("","right","select");
            }
          }
          this.replaceText("","left","select");
          return false;
        }
      }
    } else if (!selection && (this.curSyl == "+" || this.curSyl == "?")) {
      UTILITY.beep();
      DEBUG.log("warn","BEEP! Cannot enter characters before or after "+this.curSyl);
      return false;
    } else if (this.isSplitSyllable() && posStart < posS && posS < posEnd) {
      DEBUG.log("warn","BEEP! Replacement across split syllable not currently allowed!");
      UTILITY.beep();
      return false;
    } else if (keyType) {
      switch (keyType) {
        case "V":// if existing vowel and not in selection or position before a consonant then invalid
          if (posStart == 0 && posEnd == this.state.length - 1 ) {//selection select all
            this.replaceText(key,"selected","end");
            return false;
          } else if ((['a','á','à','ã'].indexOf(this.curSyl.substring(posV,posV+1)) > -1 ||
                      this.curSyl.substring(posV,posV+2) == 'a͚') &&
                      (key == 'i' || key == 'u') && //case where I or U is after a, a͚, á, à,or ã
                      (posV < posStart) && // cursor after V
                      (posM==-1 || posStart < posM) && //not after M
                      (posA==-1 || posA>posStart && posA <posEnd) // not A or A selected
                     ) {
            this.replaceText(key,"selected","after");
            return false;
          } else if (!(
                       (posV > -1 && selection && (posStart < posV && posV < posEnd)) || //existing vowel in selection
                        posV == -1 && (posEnd > posLastC && posEnd > posLastH ))){//Consonants without vowel should not happen
            UTILITY.beep();
            DEBUG.log("warn","BEEP! invalid vowel "+key+" for curStr "+this.curSyl+" with state "+this.state);
            return false;
          }
          this.replaceText(key,"selected","after");
          return false;
          break;
        case "M":// if existing vowel modifier and not exclusively in selection or cursor not after vowel at end of syllable then invalid
          if (posStart == 0 && posEnd == this.state.length - 1 ) {//selection select all
            this.replaceText("."+key,"selected","end");
            return false;
          } else if (posStart < posV && posEnd == this.state.length - 1) {//selection starts befor vowel and ends at end of syllable
            this.replaceText("."+key,"selected","end");
            return false;
          } else if (posM > -1 && (posStart > posM || posEnd < posM) ||
              posM == -1 && posEnd < posV) {
            UTILITY.beep();
            DEBUG.log("warn","BEEP! invalid modifier "+key+" for curStr "+this.curSyl+" with state "+this.state);
            return false;
          } else if (!selection && posEnd == this.state.length - 1 && posM == -1) {//no modifier cursor only at end
            this.replaceText(key,"insert","end");
            return false;
          } else if (posM > -1 && posStart < posM && posM < posEnd ) {//modifier only in selection
            this.replaceText(key,"selected","end");
            return false;
          }
          break;
        case "H"://if position after vowel then invalid
          if (posStart == 0 && posEnd == this.state.length - 1 ) {//selection select all
            this.replaceText(key+".","augment","select");
            return false;
          } else if (posEnd > posV && posStart > posV) {
            UTILITY.beep();
            DEBUG.log("warn","BEEP! invalid consonant"+(keyType == "H"?"/aspirate":"")+" after vowel not allowed "+key+" for curStr "+this.curSyl+" with state "+this.state);
            return false;
          } else if ( !selection && posV > posEnd ) {//if cursor and before vowel
            this.replaceText(key,"insert","after");
            return false;
          } else if (selection && posV > posEnd) {
            this.replaceText(key,"selected","after");
            return false;
          }else if (selection && posStart < posV && posV < posEnd) {
            this.replaceText(key+".","augment","select");
            return false;
          }
        case "C"://if anchor and cursor after vowel then invalid
          if (posStart == 0 && posEnd == this.state.length - 1 ) {//selection select all
            this.replaceText(key+".","augment","select");
            return false;
          } else if (key == "_" && this.curSyl.indexOf("_")>-1) {
            posU = this.curSyl.indexOf("_")+1;//add one to adjust to state offsets
            if (posStart == posU || posEnd == posU) {
              UTILITY.beep();
              DEBUG.log("warn","BEEP! double consonant placeholder not allowed for curStr "+this.curSyl+" with state "+this.state);
              return false;
            } else if ( posStart > posV) {//cursor or selection after vowel
              UTILITY.beep();
              DEBUG.log("warn","BEEP! invalid consonant after vowel not allowed "+key+" for curStr "+this.curSyl+" with state "+this.state);
              return false;
            }
          } else if ( posStart > posV) {//cursor or selection after vowel
            if (this._graphemeMap[key]['·'] && this._graphemeMap[key]['·']['typ'] == 'MT') {//anticipate the user is entering a modifier
              this.replaceText(key+"·","augment","select");
            } else {
              UTILITY.beep();
              DEBUG.log("warn","BEEP! invalid consonant after vowel not allowed "+key+" for curStr "+this.curSyl+" with state "+this.state);
            }
            return false;
          } else if ( !selection && posV > posEnd ) {//cursor before vowel
            this.replaceText(key,"insert","after");
            return false;
          } else if ( selection && posV > posEnd) {//selection before V
            this.replaceText(key,"selected","after");
            return false;
          } else if ( selection && posStart < posV && posV < posEnd) {//selection includes V
            this.replaceText(key+".","augment","select");
            return false;
          } else {
            UTILITY.beep();
            DEBUG.log("warn","BEEP! unknown state consonant '"+key+"' for curStr "+this.curSyl+" with state "+this.state);
            return false;
          }
          break;
        case "D"://if anchor and cursor after vowel then invalid
          if (posStart == 0 && posEnd == this.state.length - 1 ) {//selection select all
              UTILITY.beep();
              DEBUG.log("warn","BEEP! Diacritic needs a base character invalid "+key+" for curStr "+this.curSyl+" with state "+this.state);
              return false;
          }  else if ( posEnd < posC && posStart < posC) {//cursor before first consonant
            UTILITY.beep();
            DEBUG.log("warn","BEEP! invalid diacritic before first consonant not allowed "+key+" for curStr "+this.curSyl+" with state "+this.state);
            return false;
          } else if ( key == '̥' ) {//special handling for under circle
            switch (this.rawSyl[posStart-1].toLowerCase()) {//grapheme preceding insertion point
              case 'r':
              case 'l'://if pre start C is r or l this changes their status a vowel
                //so any existing vowel must be selected
                if (selection && posStart < posV && posV < posEnd) {
                  this.replaceText(key,"selected","after");
                  return false;
                } else {//if not then beep.
                  UTILITY.beep();
                  DEBUG.log("warn","BEEP! Under circle with r or l is a vowel and needs to replace existing vowel for curStr "+this.curSyl+" with state "+this.state);
                  return false;
                }
                break;
              case 'm':
              case 'n'://if previous is m or n then if vowel selected augment with fullstop otherwise just insert
                if (selection && (1+posStart) == posV && posV < posEnd) {
                  this.replaceText(key+".","augment","select");
                  return false;
                } else {//if not then beep.
                  this.replaceText(key,"insert","after");
                  return false;
                }
                break;
              default:
                UTILITY.beep();
                DEBUG.log("warn","BEEP! Under circle invalid for curStr "+this.curSyl+" with state "+this.state);
                return false;
            }
          } else if ( key == '·' ) {//special handling for virāma
            switch (this.rawSyl[posStart-1].toLowerCase()) {//grapheme preceding insertion point
              case '·':
                UTILITY.beep();
                DEBUG.log("warn","BEEP! Multiple virāma not supported - syllable "+this.curSyl+" with state "+this.state);
                return false;
                break;
              case 'm':
              case 'n':
              case 't':
              case 'ḷ'://if previous is m,n,t or ḷ and cursor after vowel (and aspirate) then insert
                if (!selection && posStart > posV && posStart > posA) {
                  this.replaceText(key,"insert","after");
                  return false;
                }
                //WARNING fall through to default.
              default:
                UTILITY.beep();
                DEBUG.log("warn","BEEP! Virāma invalid for curStr "+this.curSyl+" with state "+this.state);
                return false;
            }
          } else if ( !selection ) {//cursor
            this.replaceText(key,"insert","after");
            return false;
          } else if ( selection ) {//selection before V
            this.replaceText(key,"selected","after");
            return false;
          } else if ( selection && posStart < posV && posV < posEnd) {//selection includes V
            this.replaceText("."+key,"augment","select");
            return false;
          } else {
            UTILITY.beep();
            DEBUG.log("warn","BEEP! unknown state diacritic '"+key+"' for curStr "+this.curSyl+" with state "+this.state);
            return false;
          }
          break;
        case "I":
        case "P":
          if (posStart == 0 && posEnd == this.state.length - 1 ) {//selection select all
            if (key == '/') {
              this.replaceText('///',"selected","end");
              return false;
            } else {
              this.replaceText(key,"selected","end");
              return false;
            }
          } else if (key == "|" && this.curSyl == "|") {
            this.replaceText(key,"insert","end");
            return false;
          } else {
            UTILITY.beep();
            DEBUG.log("warn","BEEP! "+key+" can only replace whole syllables select all - "+this.curSyl+" with state "+this.state);
            return false;
          }
          break;
        case "N":
        case "O":
          if (posStart == 0 && posEnd == this.state.length - 1){
            if (keyType == 'N' ||
                key == '…' ||
                key == '+' ||
                key == '?') {//selection select all
              this.replaceText(key,"selected","end");
              return false;
            } else if (key == '-') {
              this.replaceText('---',"selected","end");
              return false;
            }
          } else if (keyType == 'N' && posStart == this.state.length - 2 && posEnd == this.state.length - 1){
            var chrs = (this.curSyl + key).split(""), len = chrs.length;
            if (
                len == 4 && this._graphemeMap[chrs[0]][chrs[1]][chrs[2]][chrs[3]] ||
                len == 3 && this._graphemeMap[chrs[0]][chrs[1]][chrs[2]] ||
                len == 2 && this._graphemeMap[chrs[0]][chrs[1]]){
              this.replaceText(key,"insert","end");
              return false;
            }
          }
          UTILITY.beep();
          DEBUG.log("warn","BEEP! Unrecognized token character ignor for "+this.curSyl+" with state "+this.state);
          return false;
          break;
      }
    }else{
      UTILITY.beep();
      DEBUG.log("warn","BEEP! invalid character '"+key+"' ignored for curStr "+this.curSyl+" with state "+this.state);
      return false; //not in keyMap so invalid
    }
    UTILITY.beep(null,500,1000);
    DEBUG.log("err","ERROR! SHOULD NOT GET HERE! Key '"+key+"' ignored for curStr "+this.curSyl+" with state "+this.state);
    return true;
  },


/**
* detects whether the current state indicates a caret
*
* @returns boolean
*/

  hasCaret: function(){
    return this.anchorPos == this.cursorPos;
  },


/**
* detects whether the current state is fully selected syllable
*
* @returns boolean
*/

  isSelectAll: function(){
    return ((this.anchorPos == 0 && this.cursorPos == this.curSyl.length) ||
            (this.cursorPos == 0 && this.anchorPos == this.curSyl.length));
  },


/**
* detects whether the current state shows cursor at syllable boundary
*
* @returns boolean
*/

  caretAtBoundary: function(side){
    return ((this.cursorIndex == 0 && this.cursorPos == 0
             && (!side || side == "left")) ||
            (this.cursorIndex == this.textNodes.length-1 &&
              this.cursorPos == this.textNodes.text().length
              && (!side || side == "right")));
  },


/**
* detects whether the current state is cursor at the beginning of a line
*
* @returns boolean
*/

  caretAtBOL: function(){
    return (this.caretAtBoundary('left') &&
            (this.prevAdjacent().hasClass('textDivHeader') ||
              this.prevAdjacent().prev().hasClass('textDivHeader')));//in case of TCM
  },


/**
* detects whether the current state is cursor at the end of a line
*
* @returns boolean
*/

  caretAtEOL: function(){
    return (this.caretAtBoundary('right') &&
            (this.nextAdjacent().hasClass('linebreak') ||
              this.nextAdjacent().next().hasClass('linebreak')));//in case of TCM
  },


/**
* find next  adjacent grapheme html node
*
* @returns $(node) jquery object
*/

  nextAdjacent: function(){
    //find element caret is in
    var curNode = this.textNodes[this.cursorIndex];
    if (this.cursorNodePos == curNode.textContent.length) {
      return $(curNode.parentNode).next();
    } else {
      return $(curNode.parentNode);
    }
  },


/**
*  previous adjacent grapheme html node
*
* @returns $(node) jquery object
*/

  prevAdjacent: function(){
    //find element caret is in
    var curNode = this.textNodes[this.cursorIndex];
    if (this.cursorNodePos == 0) {
      return $(curNode.parentNode).prev();
    } else {
      return $(curNode.parentNode);
    }
  },


/**
* get the current syllables token id
*
* @param int refSclID Syllablecluster entity id to use as reference for token id
*
* @returns int | null Token entity id or null
*/

  getTokenID: function(refSclID){
    var sclID = refSclID?refSclID:this.sclID,
        refNode = $('.grpGra.scl'+sclID,this.contentDiv);
    if (refNode && refNode.length && refNode.get(0).className && refNode.get(0).className.match(/tok(\d+)/)) {
      return refNode.get(0).className.match(/tok(\d+)/)[1];
    }
    return null;
  },


/**
* detect if cursor is loacted at token boundary
*
* @returns int Identifies -1 start of token, 1 end of token, and 0 within token
*/

  getCurTokenBoundary: function(){
    var tokID = this.getTokenID(),
        entities = this.dataMgr.entities,
        tokGraIDs = (entities.tok && entities.tok[tokID]) ? entities.tok[tokID].graphemeIDs:[],
        tokCurPos = this.getTokenCurPos();
    if (tokCurPos == tokGraIDs.length - 1) {
      return 1;//at end of token
    } else if (tokCurPos == - 1) {
      return -1;//at beginning of token
    } else {
      return 0;//not at boundary
    }
  },


/**
* detects if the current syllable is plit between tokens
*
* @returns true | false
*/

  isSplitSyllable: function(){
    var cntGrpGra = this.syllable.length, i, hasInnerBoundary = false;
    for (i=0;i<cntGrpGra-1;i++) {
      if ($(this.syllable.get(i)).next().hasClass('boundary')) {
        hasInnerBoundary = true;
        break;
      }
    }
    return hasInnerBoundary;
  },


/**
* detect if cursor is at split in a split syllable
*
* @param string side Specifies checking teh side of split
*
* @returns true|false
*/

  isCursorAtSplit: function(side){
    var cursorGrpGra = $(this.syllable.get(this.cursorIndex));
    return (this.isSplitSyllable()&&
            ((this.cursorNodePos == cursorGrpGra.text().length &&
              cursorGrpGra.next().hasClass('boundary')&& (!side || side == "left")) ||
            (this.cursorNodePos == 0 &&
                  cursorGrpGra.prev().hasClass('boundary') && (!side || side == "right"))));
  },


/**
* get grapheme index position of cursor within token
*
* @returns int Index of cursor
*/

  getTokenCurPos: function(){
    var tokID = this.getTokenID(), graPosIndex,
        entities = this.dataMgr.entities,
        preCurGraIDIndex, tokGraIDs = (entities.tok && entities.tok[tokID]) ? entities.tok[tokID].graphemeIDs:[];
    graPosIndex = this.charPosGraStrMap[(this.cursorPos > 0 ?-1 + this.cursorPos:this.cursorPos)];
    preCurGraIDIndex = tokGraIDs.indexOf(this.graphemeIDs[graPosIndex]);
    if ( this.cursorPos == 0 ) {//back up one char for break after
      preCurGraIDIndex--;
    }
    DEBUG.log("gen","Token GraIndex for grapheme before cursor is "+preCurGraIDIndex);
    return preCurGraIDIndex ;
  },


/**
* find Sequence id of the current line
*
* @param int refSclID SyllableCluster entity id used to reference the line
*
* @returns int | null Sequence entity id or null
*/

  getLineSeqID: function(refSclID){
    var sclID = refSclID?refSclID:this.sclID,
        refNode = refSclID?$($('.grpGra.scl'+refSclID,this.contentDiv).get(0)) : $(this.textNodes[this.cursorIndex].parentNode),
        headerNode = refNode.prevUntil("h3,br","span.textDivHeader");
    if (headerNode.length == 1 && headerNode.attr('class').match(/lineseq(\d+)/)) {
      return headerNode.attr('class').match(/lineseq(\d+)/)[1];
    }
    return null;
  },


/**
* calculate number of grapheme group nodes from the current position to the next boundary
*
* @returns int | null
*/

  getSylSplitIndex: function(){
    var cntNodes = this.syllable.length, firstNode = $(this.syllable[0]), nodesTilSplit = null, splitIndex = null;
    nodesTilSplit = firstNode.prev().nextUntil(".boundary,.linebreak,br",'.grpGra.scl'+this.sclID);//get all syl nodes until a boundary
    splitIndex = nodesTilSplit.length;//count of nodes is index of node before split base 1
    if (splitIndex && splitIndex < cntNodes) {
      return splitIndex;
    }
    return null;
  },


/**
* find grapheme characters before split for the split syllable case
*
* @returns string | null
*/

  getPreSplitText: function(){
    var cntNodes = this.syllable.length, preString = '', i;
    if (this.beforeSplitIndex >= 0) {
      for(i=0; i<= this.beforeSplitIndex; i++) {
        preString += this.syllable[i].textContent;
      }
      return preString;
    }
    return null;
  },


/**
* computer all syllable information
*
* @returns string Grapheme characts for the current syllable
*/

  computeSyllable: function(){
    DEBUG.traceEntry("computeSyllable");
    //run through childnode and concatenate all text node contents
    //calculate cursor and section if any
    var i,VSE=this,lastMapping,iSplit;
    this.syllable = $('.grpGra.scl'+this.sclID,this.contentDiv);
    this.charToNodeMap = [];
    //map char/cursorpos to node,offset
    this.textNodes = this.syllable.map(function(index,el){
                  for (i=0;i<el.childNodes[0].textContent.length;i++) {//check this skips TCM
                    VSE.charToNodeMap.push([index,i]);
                  }
                  return el.childNodes[0];
                });
    //fixup Map for position at end of syllable
    lastMapping = this.charToNodeMap[this.charToNodeMap.length-1];
    this.charToNodeMap.push([lastMapping[0],1+ parseInt(lastMapping[1])]);
    this.firstTextNode = this.textNodes[0];
    this.ordFirst = this.firstTextNode.parentNode.className.match(/ord(\d+)/)[1];
    this.lastTextNode = this.textNodes[this.textNodes.length-1];
    this.ordLast = this.lastTextNode.parentNode.className.match(/ord(\d+)/)[1];
    if (iSplit = this.getSylSplitIndex()) {
      this.beforeSplitIndex = iSplit-1;
      this.strPreSplit = this.getPreSplitText();
      this.charToNodeMap[this.strPreSplit.length][0] = this.beforeSplitIndex;//ref to left side of xplit
      this.charToNodeMap[this.strPreSplit.length][1] = this.textNodes[this.beforeSplitIndex].length;
      this.charToNodeMap[this.strPreSplit.length][2] = this.beforeSplitIndex+1;//ref to right side of split
      this.charToNodeMap[this.strPreSplit.length][3] = 0;
    } else if (this.beforeSplitIndex || this.strPreSplit) {
      delete this.beforeSplitIndex;
      delete this.strPreSplit;
    }
    DEBUG.traceExit("computeSyllable");
    return this.syllable.text();
  },

  /**
  * member that synchs the state of the syllable to align with the selection
  * possibly adjusting the selection
  *
  * @param string hint is a string suggesting a cursor positioning
  *
  */

  synchSelection: function(hint){
    var  sel,range,anchorNode,anchorSclNode,anchorOffset,anchorOrd,anchorIsStart,anchorShift,grdCnt=500,
         cursorNode,cursorSclNode,cursorOffset,cursorOrd,cursorShift,caretOnly,nextNode,prevNode,
         calcAnchorPos = false,calcCursorPos = false,rangeChanged = false,
         childNodes,i,pos;
    DEBUG.traceEntry('synchSelection',"Hint = "+ hint +"  for '"+this.curSyl+"' with state "+this.state+" with pos A,C "+this.anchorPos+","+this.cursorPos);

    //get the current browser selection
    if (window.getSelection) {
      sel = window.getSelection();
      if (sel.getRangeAt && sel.rangeCount) {
        range = sel.getRangeAt(0);
      }
    } else if (document.selection && document.selection.createRange) {
      range = document.selection.createRange();
    }

    // setup variables for calculation
    if (range) {
      caretOnly = (range.startContainer == range.endContainer && range.startOffset == range.endOffset);
      anchorIsStart = !(sel && (range.startContainer != sel.anchorNode ||
                    (range.startContainer == sel.anchorNode && range.startOffset != sel.anchorOffset)));
      if (anchorIsStart) {
        anchorNode = range.startContainer
        anchorOffset = range.startOffset;
        cursorNode = range.endContainer;
        cursorOffset = range.endOffset;
      } else {
        anchorNode = range.endContainer
        anchorOffset = range.endOffset;
        cursorNode = range.startContainer;
        cursorOffset = range.startOffset;
      }
      if (hint == "endOfSyllable") {
        range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
        range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
        this.anchorPos = this.cursorPos = this.curSyl.length;
        DEBUG.trace("synchSyl","set anchor to end of '"+this.curSyl+"' to end "+this.anchorPos);
        this.cursorNodePos = this.lastTextNode.textContent.length;
        this.cursorIndex = this.textNodes.length - 1;
        rangeChanged = true;
      } else if (anchorNode &&
          $(anchorNode).parents(".editContainer").length
          ) { // range selection is within editor

        //adjust anchor to nearest grapheme group
        anchorSclNode = anchorNode;

        if (anchorSclNode.nodeName == "#text") {
          anchorSclNode = anchorSclNode.parentNode;
        }
        if (anchorSclNode.id.match(/textContent/)) { // on parent div need to find the nearest grp
          if (anchorOffset > 0 && anchorOffset <= anchorSclNode.childNodes.length) {
            prevNode = $(anchorSclNode.childNodes[anchorOffset]);//.next();
            while (prevNode && prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
              prevNode = prevNode.prev();
            }
            anchorSclNode = prevNode.get(0);
            anchorOffset = anchorSclNode.textContent.length;
          }else{//strange so just set it to first syllable
            anchorSclNode = $(anchorSclNode).children('.grpGra').get(0);
            anchorOffset = 0;
          }
          if (anchorIsStart) {
            range.setStart(anchorSclNode,anchorOffset);
          } else {
            range.setEnd(anchorSclNode,anchorOffset);
          }
      }
        if (!anchorSclNode.className.match(/grpGra/)){ //the node is not a grapheme group
          if (anchorIsStart) { // forward selection
            nextNode = $(anchorSclNode);
              while(nextNode && nextNode.length && grdCnt-- && !nextNode.hasClass('grpGra')) {
                nextNode = nextNode.next();
              }
            if (!nextNode) {//must have hit the end so back up the other way.
              prevNode = $(anchorSclNode);
              while(prevNode && prevNode.length && !prevNode.hasClass('grpGra')) {
                prevNode = prevNode.prev();
              }
              if (prevNode) {
                anchorSclNode = prevNode.get(0);
              }else{
                DEBUG.log("warn","scl" + this.sclID + " can't synch from anchor node " + anchorNode.className);
                anchorSclNode = null;
              }
            }else{
              anchorSclNode = nextNode.get(0);
            }
          }else{ // reverse selection so move left
            prevNode = $(anchorSclNode);
            if (prevNode.attr("id").match(/textContent/)) {// on parent div with reverse so pick last group in text
              prevNode = $(prevNode.children('.grpGra').get(prevNode.children('.grpGra').length -1))
            } else {
              while(prevNode && prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
                prevNode = prevNode.prev();
              }
            }
            if (!prevNode) {//must have hit the start so back the other way.
              nextNode = $(anchorSclNode);
              while(nextNode && nextNode.length && !nextNode.hasClass('grpGra')) {
                nextNode = nextNode.next();
              }
              if (nextNode) {
                anchorSclNode = nextNode.get(0);
              }else{
                DEBUG.log("warn","scl" + this.sclID + " can't synch from anchor node " + anchorNode.className);
                anchorSclNode = null;
              }
            }else{
              anchorSclNode = prevNode.get(0);
            }
          }
        }
        anchorOrd = anchorSclNode && anchorSclNode.className.match(/ord(\d+)/) ? anchorSclNode.className.match(/ord(\d+)/)[1]:0;

        //adjust cursor to nearest grapheme group
        cursorSclNode = cursorNode;
        if (cursorSclNode.nodeName == "#text") {
          cursorSclNode = cursorSclNode.parentNode;
        }
        if (cursorSclNode.id.match(/textContent/)) { // on parent div need to find the nearest grp
          if (cursorOffset > 0 && cursorOffset <= cursorSclNode.childNodes.length) {
            prevNode = $(cursorSclNode.childNodes[cursorOffset]);
            while (prevNode && prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
              prevNode = prevNode.prev();
            }
            if (prevNode.length == 0) { //must not have grpGra before so just use anchor
              cursorSclNode = anchorSclNode;
              cursorOffset = anchorOffset;
            } else {
              cursorSclNode = prevNode.get(0);
              cursorOffset = cursorSclNode.textContent.length;
            }
          }else{//strange so just set it to first syllable
            cursorSclNode = $(cursorSclNode).children('.grpGra').get($(cursorSclNode).children('.grpGra').length-1);
            cursorOffset = cursorSclNode.textContent.length;
          }
        }
        if (!cursorSclNode.className.match(/grpGra/)){ //the node is not a grapheme group
          if (anchorIsStart) { // forward selection so move left to retract selection
            prevNode = $(cursorSclNode);
            while(prevNode && prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
              prevNode = prevNode.prev();
            }
            if (!prevNode) {//must have hit the start so back the other way.
              nextNode = $(cursorSclNode);
              while(nextNode && nextNode.length && !nextNode.hasClass('grpGra')) {
                nextNode = nextNode.next();
              }
              if (nextNode) {
                cursorSclNode = nextNode.get(0);
              }else{
                DEBUG.log("warn","scl" + this.sclID + " can't synch from cursor node " + cursorNode.className);
                cursorSclNode = null;
              }
            }else{
              cursorSclNode = prevNode.get(0);
            }
          }else{ // reverse selection so move right
            nextNode = $(cursorSclNode);
            while(nextNode && nextNode.length && !nextNode.hasClass('grpGra')) {
              nextNode = nextNode.next();
            }
            if (!nextNode) {//must have hit the end so back up the other way.
              prevNode = $(cursorSclNode);
              while(prevNode && prevNode.length && grdCnt-- && !prevNode.hasClass('grpGra')) {
                prevNode = prevNode.prev();
              }
              if (prevNode) {
                cursorSclNode = prevNode.get(0);
              }else{
                DEBUG.log("warn","scl" + this.sclID + " can't synch from cursor node " + cursorNode.className);
                cursorSclNode = null;
              }
            }else{
              cursorSclNode = nextNode.get(0);
            }
          }
        }
        cursorOrd = cursorSclNode && cursorSclNode.className.match(/ord(\d+)/) ? cursorSclNode.className.match(/ord(\d+)/)[1]:0;

        // detect positioning case and adjust range to fit within syllable
        if (this.ordFirst > anchorOrd) {// anchor sequentially before
          this.anchorPos = 0;
          if (this.ordFirst > cursorOrd) {// cursor sequentially before
            //case 1 no direction needed set anchor and cursor to start of first text node
            range.setEnd(this.firstTextNode,0);
            range.setStart(this.firstTextNode,0);
            this.cursorPos = this.cursorNodePos = this.cursorIndex = 0;
          } else if (this.ordFirst <= cursorOrd & cursorOrd <= this.ordLast) { //cursor lies in syllable
            // case 2 forward set selection anchor node to start of first text node
            if (anchorIsStart) {
              range.setStart(this.firstTextNode,0);
            } else {
              range.setEnd(this.firstTextNode,0);
            }
            calcCursorPos = true;
          } else if (this.ordLast < cursorOrd) {// cursor sequentially after
            // case 4 forward set selection anchor node to start of first text node
            // and set selection cursor node to end of last text node
            if (anchorIsStart) {
              range.setStart(this.firstTextNode,0);
              range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
            } else {
              range.setEnd(this.firstTextNode,0);
              range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
            }
            this.cursorPos = this.curSyl.length;
            this.cursorNodePos = this.lastTextNode.textContent.length;
            this.cursorIndex = this.textNodes.length - 1;
          }
          rangeChanged = true;
        } else if (this.ordFirst <= anchorOrd && anchorOrd <= this.ordLast) { //anchor lies in syllable
          calcAnchorPos = true; //synch position with elements of syllable
          if (this.ordFirst > cursorOrd) {// cursor sequentially before
            //case 2 reverse set selection cursor node to start of first text node
            if (anchorIsStart) {
              range.setEnd(this.firstTextNode,0);
            } else {
              range.setStart(this.firstTextNode,0);
            }
            this.cursorPos = this.cursorNodePos = this.cursorIndex = 0;
            rangeChanged = true;
          } else if (this.ordFirst <= cursorOrd & cursorOrd <= this.ordLast) { //cursor lies in syllable
            //case 5
            calcCursorPos = true;
          } else if (this.ordLast < cursorOrd) {// cursor sequentially after
            //case 3 forward set selection cursor node to end of last text node
            if (anchorIsStart) {
              range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
            } else {
              range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
            }
            this.cursorPos = this.curSyl.length;
            this.cursorNodePos = this.lastTextNode.textContent.length;
            this.cursorIndex = this.textNodes.length - 1;
            rangeChanged = true;
          }
        } else if (this.ordLast < anchorOrd) {// anchor sequentially after
          this.anchorPos = this.curSyl.length;
          DEBUG.trace("replaceText","set anchor to end of '"+this.curSyl+"' to end "+this.anchorPos);
          if (this.ordFirst > cursorOrd) {// cursor sequentially before
            //case 4 reverse set selection cursor node to start of first text node
            // and set selection anchor node to end of last text node
            if (anchorIsStart) {
              range.setEnd(this.firstTextNode,0);
              range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
            } else {
              range.setStart(this.firstTextNode,0);
              range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
            }
            this.cursorPos = this.cursorNodePos = this.cursorIndex = 0;
          } else if (this.ordFirst <= cursorOrd & cursorOrd <= this.ordLast) { //cursor lies in syllable
            //case 3 reverse  set selection anchor node to end of last text node
            if (anchorIsStart) {
              range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
            } else {
              range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
            }
            calcCursorPos = true;
          } else if (this.ordLast < cursorOrd) {// cursor sequentially after
            //case 6 no direction needed set selection anchor and cursor to end of last text node
            range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
            range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
            this.cursorPos = this.curSyl.length;
            this.cursorNodePos = this.lastTextNode.textContent.length;
            this.cursorIndex = this.textNodes.length - 1;
          }
          rangeChanged = true;
        }


        if (calcAnchorPos) {// walk the childNodes accumulating text length of text nodes
                            // until finding anchor node then add anchor offset
          childNodes = $('span.grpGra.scl'+this.sclID,this.contentDiv).map(function(index,el){ return el;});
          pos = 0;
          for(i=0; i < childNodes.length; i++){
            if (childNodes[i] == anchorSclNode) {//found anchorNode
              this.anchorNodePos = (anchorIsStart?range.startOffset:range.endOffset);
              this.anchorPos = parseInt(pos) + this.anchorNodePos;
              this.anchorIndex = i;
              DEBUG.trace("synchSyl","set anchor to start of '"+this.curSyl+"' "+this.anchorPos);
              DEBUG.trace("synchSyl","set anchor startOffset "+range.startOffset+" endOffset "+range.endOffset);
              break;
            }else {
              pos += childNodes[i].textContent.length;
            }
          }
        }

        if (calcCursorPos) {
          childNodes = $('span.grpGra.scl'+this.sclID,this.contentDiv).map(function(index,el){ return el;});
          pos = 0;
          for(i=0; i < childNodes.length; i++){
           if (childNodes[i] == cursorSclNode) {//found anchorNode
              this.cursorNodePos = (anchorIsStart?range.endOffset:range.startOffset);
              this.cursorPos = parseInt(pos) + this.cursorNodePos;
              this.cursorIndex = i;
              break;
            }else {
              pos += childNodes[i].textContent.length;
            }
          }
        }

      } else { // selection range not usable use hint to set the selection range
        if (hint == "last") {
          range.setStart(this.lastTextNode,this.lastTextNode.textContent.length);
          range.setEnd(this.lastTextNode,this.lastTextNode.textContent.length);
          this.anchorPos = this.cursorPos = this.curSyl.length;
          DEBUG.trace("synchSyl","set anchor to end of '"+this.curSyl+"' to end "+this.anchorPos);
          this.anchorNodePos = this.cursorNodePos = this.lastTextNode.textContent.length;
          this.anchorIndex = this.cursorIndex = this.textNodes.length - 1;
        } else {
          range.setStart(this.firstTextNode,0);
          range.setEnd(this.firstTextNode,0);
          this.anchorIndex = this.cursorIndex = this.anchorPos = this.cursorPos = this.cursorNodePos = this.cursorIndex = 0;
        }
        rangeChanged = true;
      }
      if (sel && rangeChanged) {
        sel.removeAllRanges();
        sel.addRange(range);
      }
    }
    DEBUG.trace('synchSelection',"Leaving synch selection for '"+this.curSyl+"' with state "+this.state+" with pos A,C "+this.anchorPos+","+this.cursorPos);
    this.calculateState();
    DEBUG.log('gen',"Hint = "+ hint +"  for '"+this.curSyl+"' with state "+this.state+" with pos "+this.anchorPos+","+this.cursorPos);
    DEBUG.traceExit('synchSelection',"Hint = "+ hint +"  for '"+this.curSyl+"' with state "+this.state+" with pos A,C "+this.anchorPos+","+this.cursorPos);
  },

  /**************************  calculate state *******************************/

/**
* calculate syllable state
*
*/

  calculateState: function() {
    //run through graphemes getting type information and interleave with cursor info
    this.state = "";
    this.charPosGraStrMap = [];
    this.graPosStrPosMap = [];
    this.curSyl = this.computeSyllable();
    if (this.curSyl && this.curSyl.length) {
      this.graStrings = [];
      var i = 0, inc, chr,chr2,chr3,chr4,typ,
          cnt = this.curSyl.length,
          splitPositioned = false,
          anchorPositioned = false,
          cursorPositioned = false;
      if(this.anchorPos == 0) {
        this.state += ">";
        anchorPositioned = true;
      }
      if(this.cursorPos == 0) {
        this.state += "<";
        cursorPositioned = true;
      }
      while (i<cnt) {
        chr = this.curSyl[i].toLowerCase();
        typ = "E";
        // convert multi-byte to grapheme - using greedy lookup
        if (this._graphemeMap[chr]){
          //check next character included
          inc=1;
          if (i+1 < cnt){
            chr2 = this.curSyl[i+1].toLowerCase();
          }
          if (this._graphemeMap[chr][chr2]){ // another char for grapheme
            inc++;
            if (i+2 < cnt){
              chr3 = this.curSyl[i+2].toLowerCase();
            }
            if (this._graphemeMap[chr][chr2][chr3]){ // another char for grapheme
              inc++;
              if (i+3 < cnt){
                chr4 = this.curSyl[i+3].toLowerCase();
              }
              if (this._graphemeMap[chr][chr2][chr3][chr4]){ // another char for grapheme
                inc++;
                if (!this._graphemeMap[chr][chr2][chr3][chr4]["srt"]){ // invalid sequence
                  DEBUG.log("warn","scl" + this.sclID + " has incomplete sequence starting at character " + i+ " " +chr + chr2 + chr3 + chr4 +" has no sort code");
                  break;
                }else{//found valid grapheme, save it
                  this.graStrings.push( chr + chr2 + chr3 + chr4);
                  typ = this._graphemeMap[chr][chr2][chr3][chr4]['typ'];
                  this.charPosGraStrMap[i] = this.charPosGraStrMap[1+ i] = //map all 4 chars to the same grapheme position
                    this.charPosGraStrMap[2 + i] = this.charPosGraStrMap[3 + i] = -1 + this.graStrings.length;
                }
              }else if (!this._graphemeMap[chr][chr2][chr3]["srt"]){ // invalid sequence
                DEBUG.log("warn","scl" + this.sclID + " has incomplete sequence starting at character " + i+ " " +chr + chr2 + chr3 +" has no sort code");
                break;
              }else{//found valid grapheme, save it
                this.graStrings.push( chr + chr2 + chr3);
                typ = this._graphemeMap[chr][chr2][chr3]['typ'];
                this.charPosGraStrMap[i] = this.charPosGraStrMap[1+ i] =//map all 3 chars to the same grapheme position
                  this.charPosGraStrMap[2 + i] = -1 + this.graStrings.length;
              }
            }else if (!this._graphemeMap[chr][chr2]["srt"]){ // invalid sequence
              DEBUG.log("warn","scl" + this.sclID + " has incomplete sequence starting at character " + i+ " " +chr + chr2 +" has no sort code");
              break;
            }else{//found valid grapheme, save it
                this.graStrings.push( chr + chr2);
                typ = this._graphemeMap[chr][chr2]['typ'];
                this.charPosGraStrMap[i] = this.charPosGraStrMap[1+ i] = -1 + this.graStrings.length;//map both chars to the same grapheme position
            }
          }else if (!this._graphemeMap[chr]["srt"]){ // invalid sequence
              DEBUG.log("warn","scl" + this.sclID + " has incomplete sequence starting at character " + i+ " " +chr +" has no sort code");
            break;
          }else{//found valid grapheme, save it
                this.graStrings.push( chr );
                typ = this._graphemeMap[chr]['typ'];
                this.charPosGraStrMap[i] = -1 + this.graStrings.length;//map char to the same grapheme position
          }
        }
        this.graPosStrPosMap[-1 + this.graStrings.length] = i;//not used yet
        i += inc;
        if (typ == "CH") {// aspirated case
          if(!anchorPositioned && this.anchorPos <= i) {
            if (this.anchorPos <= i-1) { //split state
              if (this.anchorPos == this.cursorPos) {
                this.state += "C><H";
                cursorPositioned = true;
              } else if(!cursorPositioned && this.cursorPos <= i){
                this.state += "C>H<";
                cursorPositioned = true;
              } else {
                this.state += "C>H";
              }
            } else {
              if (this.anchorPos == this.cursorPos) {
                if (this.beforeSplitIndex >= 0 && this.beforeSplitIndex < this.anchorIndex) {
                  this.state += "CHS><";
                  splitPositioned = true;
                } else {
                  this.state += "CH><";
                }
                cursorPositioned = true;
              } else if (this.cursorPos <= i-1) {
                if (cursorPositioned) {
                  this.state += "CH>";
                } else {
                  this.state += "C<H>";
                  cursorPositioned = true;
                }
              } else {
                this.state += "CH>";
              }
            }
            anchorPositioned = true;
          } else if(!cursorPositioned && this.cursorPos <= i) {
            if (this.cursorPos <= i-1) { //split state
              this.state += "C<H";
            } else {
              this.state += "CH<";
            }
            cursorPositioned = true;
          }else {
            this.state += typ;
          }
        } else if (typ == "VA") {// aspirated case
          if(!anchorPositioned && this.anchorPos <= i) {
            if (this.anchorPos <= i-1) { //split state
              if (this.anchorPos == this.cursorPos) {
                this.state += "V><A";
                cursorPositioned = true;
              } else if(!cursorPositioned && this.cursorPos <= i){
                this.state += "V>A<";
                cursorPositioned = true;
              } else {
                this.state += "V>A";
              }
            } else {
              if (this.anchorPos == this.cursorPos) {
                this.state += "VA><";
                cursorPositioned = true;
              } else if (this.cursorPos <= i-1) {
                if (cursorPositioned) {
                  this.state += "VA>";
                } else {
                  this.state += "V<A>";
                  cursorPositioned = true;
                }
              } else {
                this.state += "VA>";
              }
            }
            anchorPositioned = true;
          } else if(!cursorPositioned && this.cursorPos <= i) {
            if (this.cursorPos <= i-1) { //split state
              this.state += "V<A";
            } else {
              this.state += "VA<";
            }
            cursorPositioned = true;
          }else {
            this.state += typ;
          }
        } else if (typ == "MT") {// virāma case  old model  --- deprecated
          if(!anchorPositioned && this.anchorPos <= i) {
            if (this.anchorPos <= i-1) { //split state
              if (this.anchorPos == this.cursorPos) {
                this.state += "M><T";
                cursorPositioned = true;
              } else if(!cursorPositioned && this.cursorPos <= i){
                this.state += "M>T<";
                cursorPositioned = true;
              } else {
                this.state += "M>T";
              }
            } else {
              if (this.anchorPos == this.cursorPos) {
                this.state += "MT><";
                cursorPositioned = true;
              } else if (this.cursorPos <= i-1) {
                if (cursorPositioned) {
                  this.state += "MT>";
                } else {
                  this.state += "M<T>";
                  cursorPositioned = true;
                }
              } else {
                this.state += "MT>";
              }
            }
            anchorPositioned = true;
          } else if(!cursorPositioned && this.cursorPos <= i) {
            if (this.cursorPos <= i-1) { //split state
              this.state += "M<T";
            } else {
              this.state += "MT<";
            }
            cursorPositioned = true;
          }else {
            this.state += typ;
          }
        } else {
          this.state += typ;
          if (this.beforeSplitIndex >= 0  && i == this.strPreSplit.length && !splitPositioned &&
            (this.cursorIndex > this.beforeSplitIndex || this.anchorIndex > this.beforeSplitIndex)) {
            this.state += "S";
            splitPositioned = true;
          }
          if(!anchorPositioned && this.anchorPos <= i) {
            this.state += ">";
            anchorPositioned = true;
          }
          if(!cursorPositioned && this.cursorPos <= i) {
            this.state += "<";
            cursorPositioned = true;
          }
        }
        if(this.beforeSplitIndex >= 0 && i == this.strPreSplit.length && !splitPositioned &&
           (this.cursorIndex <= this.beforeSplitIndex || this.anchorIndex <= this.beforeSplitIndex)) {
          this.state += "S";
          splitPositioned = true;
        }
      }//end while
      if(!anchorPositioned) {
        this.state += ">";
      }
      if(!cursorPositioned) {
        this.state += "<";
      }
    }
    DEBUG.log("gen","Syllable #" + this.sclID + " state = " + this.state + ", pre = '" + this.strPreSplit + 
              "',  aPos:cPos = " + this.anchorPos + ":" + this.cursorPos +
              ", aI:aO = " + this.anchorIndex + ":" + this.anchorNodePos +
              ", cI:cO = " + this.cursorIndex + ":" + this.cursorNodePos );
  },

// table of valid input characters.
  _keyTypeMap : {
    "0": "N",
    "½": "N",
    "1": "N",
    "2": "N",
    "3": "N",
    "4": "N",
    "5": "N",
    "6": "N",
    "7": "N",
    "8": "N",
    "9": "N",
    ",": "P",
    ".": "I",
    ";": "P",
    ":": "I",
    "!": "P",
    "?": "P",
    "[": "I",
    "]": "I",
    "{": "I",
    "}": "I",
    "(": "I",
    ")": "I",
    "«": "P",
    "»": "P",
    "ϴ": "I",
    "☧": "I",
    "⳩": "I",
    "⸱": "P",
    "·": "P",
    "+": "O",
    "-": "I",
    "A": "I",
    "Â": "I",
    "B": "I",
    "C": "I",
    "D": "I",
    "E": "I",
    "F": "I",
    "G": "I",
    "H": "I",
    "Ĥ": "I",
    "I": "I",
    "Î": "I",
    "Ī": "I",
    "J": "I",
    "K": "I",
    "L": "I",
    "M": "I",
    "N": "I",
    "O": "I",
    "P": "I",
    "Q": "I",
    "R": "I",
    "S": "I",
    "T": "I",
    "U": "I",
    "V": "I",
    "W": "I",
    "X": "I",
    "Y": "I",
    "Z": "I",
    "a": "I",
    "á": "I",
    "ạ": "I",
    "â": "I",
    "b": "I",
    "ḅ": "I",
    "c": "I",
    "ĉ": "I",
    "d": "I",
    "ḍ": "I",
    "e": "I",
    "ẹ": "I",
    "é": "I",
    "ê": "I",
    "f": "I",
    "g": "I",
    "h": "I",
    "ĥ": "I",
    "ḥ": "I",
    "ì": "I",
    "i": "I",
    "ị": "I",
    "í": "I",
    "î": "I",
    "ī": "I",
    "j": "I",
    "ĵ": "I",
    "k": "I",
    "ḳ": "I",
    "l": "I",
    "ḷ": "I",
    "m": "I",
    "ṃ": "I",
    "n": "I",
    "ṇ": "I",
    "o": "I",
    "ó": "I",
    "ọ": "I",
    "ô": "I",
    "p": "I",
    "q": "I",
    "r": "I",
    "ṛ": "I",
    "s": "I",
    "ŝ": "I",
    "ṣ": "I",
    "t": "I",
    "ṭ": "I",
    "u": "I",
    "ú": "I",
    "û": "I",
    "ụ": "I",
    "ⅎ": "I",
    "Ⅎ": "I",
    "ↄ": "I",
    "Ↄ": "I",
    "ⱶ": "I",
    "Ⱶ": "I",
    "v": "I",
    "ṿ": "I",
    "w": "I",
    "ẉ": "I",
    "x": "I",
    "y": "I",
    "ŷ": "I",
    "ỵ": "I",
    "z": "I",
    "ẓ": "I",
    "Ἀ": "I",
    "Α": "I",
    "Β": "I",
    "Γ": "I",
    "Δ": "I",
    "Ε": "I",
    "Ζ": "I",
    "Η": "I",
    "Θ": "I",
    "Ι": "I",
    "Κ": "I",
    "Λ": "I",
    "Μ": "I",
    "Ν": "I",
    "Ξ": "I",
    "Ο": "I",
    "Π": "I",
    "Ρ": "I",
    "Σ": "I",
    "Τ": "I",
    "Υ": "I",
    "Φ": "I",
    "Χ": "I",
    "Ψ": "I",
    "Ω": "I",
    "ἀ": "I",
    "α": "I",
    "β": "I",
    "γ": "I",
    "δ": "I",
    "ε": "I",
    "ἔ": "I",
    "ζ": "I",
    "η": "I",
    "θ": "I",
    "ι": "I",
    "ί": "I",
    "κ": "I",
    "λ": "I",
    "μ": "I",
    "ν": "I",
    "ξ": "I",
    "ο": "I",
    "π": "I",
    "ρ": "I",
    "ς": "I",
    "ϛ": "I",
    "σ": "I",
    "τ": "I",
    "υ": "I",
    "ῦ": "I",
    "φ": "I",
    "ό": "I",
    "χ": "I",
    "ψ": "I",
    "ω": "I",
    "א": "I",
    "ב": "I",
    "ג": "I",
    "ד": "I",
    "ה": "I",
    "ו": "I",
    "ז": "I",
    "ח": "I",
    "ט": "I",
    "י": "I",
    "ך": "I",
    "כ": "I",
    "ל": "I",
    "ם": "I",
    "מ": "I",
    "ן": "I",
    "נ": "I",
    "ס": "I",
    "ע": "I",
    "ף": "I",
    "פ": "I",
    "ץ": "I",
    "צ": "I",
    "ק": "I",
    "ר": "I",
    "ש": "I",
    "ת": "I",
    "̣" :"D",
    "̥" :"D",
    "̆" :"D",
    "̂" :"D",
    "͚" :"D",
    "́" :"D",
    "̀" :"D",
    "̮" :"D",
    "̃" :"D",
    "́" :"D",
    "͞" :"D",
    "̲" :"D",
    "͟" :"D",
    "̄" :"D",
    "̱" :"D"

  },


  // valid grapheme multibyte sequences with sort codes and types
  _graphemeMap : {
    "0": { "srt": "700", "typ": "N" },
    "½": { "srt": "705", "typ": "N" },
    "1": { "srt": "710", "typ": "N" },
    "2": { "srt": "720", "typ": "N" },
    "3": { "srt": "730", "typ": "N" },
    "4": { "srt": "740", "typ": "N" },
    "5": { "srt": "750", "typ": "N" },
    "6": { "srt": "760", "typ": "N" },
    "7": { "srt": "770", "typ": "N" },
    "8": { "srt": "780", "typ": "N" },
    "9": { "srt": "790", "typ": "N" },
    ",": { "srt": "800", "typ": "P" },
    ".": { "srt": "810", "typ": "I" },
    ";": { "srt": "820", "typ": "P" },
    ":": { "srt": "830", "typ": "I" },
    "!": { "srt": "840", "typ": "P" },
    "?": { "srt": "850", "typ": "P" },
    "[": { "srt": "860", "typ": "I" },
    "]": { "srt": "870", "typ": "I" },
    "{": { "srt": "860", "typ": "I" },
    "}": { "srt": "870", "typ": "I" },
    "(": { "srt": "860", "typ": "I" },
    ")": { "srt": "870", "typ": "I" },
    "«": { "srt": "880", "typ": "P" },
    "»": { "srt": "890", "typ": "P" },
    "ϴ": { "srt": "920", "typ": "I" },
    "☧": { "srt": "921", "typ": "I" },
    "⳩": { "srt": "921", "typ": "I" },
    "⸱": { "srt": "922", "typ": "P" },
    "·": { "srt": "922", "typ": "P" },
    "+": { "srt": "900", "typ": "O" },
    "-": { "srt": "910", "typ": "I" },
    "A": { "̂": { "srt": "101", "typ": "I" },
           "srt": "100", "typ": "I" },
    "Â": { "srt": "101", "typ": "I" },
    "B": { "srt": "110", "typ": "I" },
    "C": { "srt": "120", "typ": "I" },
    "D": { "srt": "130", "typ": "I" },
    "E": { "srt": "140", "typ": "I" },
    "F": { "srt": "150", "typ": "I" },
    "G": { "srt": "160", "typ": "I" },
    "H": { "̂": { "srt": "171", "typ": "I" },
           "srt": "170", "typ": "I" },
    "Ĥ": { "srt": "171", "typ": "I" },
    "I": { "̂": { "srt": "181", "typ": "I" },
           "srt": "180", "typ": "I" },
    "Î": { "srt": "181", "typ": "I" },
    "Ī": { "srt": "187", "typ": "I" },
    "J": { "srt": "190", "typ": "I" },
    "K": { "̂": { "srt": "201", "typ": "I" },
           "srt": "200", "typ": "I" },
    "L": { "srt": "210", "typ": "I" },
    "M": { "̂": { "srt": "221", "typ": "I" },
           "srt": "220", "typ": "I" },
    "N": { "srt": "230", "typ": "I" },
    "O": { "srt": "240", "typ": "I" },
    "P": { "srt": "250", "typ": "I" },
    "Q": { "srt": "260", "typ": "I" },
    "R": { "srt": "270", "typ": "I" },
    "S": { "srt": "280", "typ": "I" },
    "T": { "srt": "290", "typ": "I" },
    "U": { "srt": "300", "typ": "I" },
    "V": { "srt": "310", "typ": "I" },
    "W": { "srt": "320", "typ": "I" },
    "X": { "srt": "330", "typ": "I" },
    "Y": { "srt": "340", "typ": "I" },
    "Z": { "srt": "350", "typ": "I" },
    "a": { "̣": { "srt": "105", "csrt": "a", "typ": "I" },
      "᷃": { "srt": "105", "csrt": "a", "typ": "I" },
      "̂": { "srt": "105", "csrt": "a", "typ": "I" },
      "srt": "105", "csrt": "a", "typ": "I" },
    "á": { "srt": "105", "csrt": "a", "typ": "I" },
    "ạ": { "srt": "105", "csrt": "a", "typ": "I" },
    "â": { "srt": "105", "csrt": "a", "typ": "I" },
    "b": { "̣": { "srt": "115", "csrt": "b", "typ": "I" },
      "srt": "115", "typ": "I" },
    "ḅ": { "srt": "115", "csrt": "b", "typ": "I" },
    "c": { "̣": { "srt": "125", "csrt": "c", "typ": "I" },
      "srt": "125", "typ": "I" },
    "ĉ": { "srt": "125", "csrt": "c", "typ": "I" },
    "d": { "̣": { "srt": "136", "csrt": "d", "typ": "I" },
      "̲": { "srt": "136", "csrt": "e", "typ": "I" },
      "͟": { "srt": "136", "csrt": "e", "typ": "I" },
      "srt": "135", "typ": "I" },
    "ḍ": { "srt": "135", "csrt": "d", "typ": "I" },
    "e": { "̂": { "srt": "145", "csrt": "e", "typ": "I" },
      "᷃": { "srt": "145", "csrt": "e", "typ": "I" },
      "̣": { "srt": "146", "csrt": "e", "typ": "I" },
      "̲": { "srt": "146", "csrt": "e", "typ": "I" },
      "͟": { "srt": "146", "csrt": "e", "typ": "I" },
      "srt": "145", "csrt": "e", "typ": "I" },
    "ẹ": { "srt": "145", "csrt": "e", "typ": "I" },
    "é": { "srt": "145", "csrt": "e", "typ": "I" },
    "ê": { "srt": "145", "csrt": "e", "typ": "I" },
    "f": { "̣": { "srt": "156", "csrt": "f", "typ": "I" },
      "̲": { "srt": "156", "csrt": "e", "typ": "I" },
      "͟": { "srt": "156", "csrt": "e", "typ": "I" },
      "srt": "155", "typ": "I" },
    "g": { "̣": { "srt": "165", "csrt": "g", "typ": "I" },
      "srt": "165", "typ": "I" },
    "h": { "̣": { "srt": "175", "csrt": "h", "typ": "I" },
      "srt": "175", "typ": "I" },
    "ĥ": { "srt": "175", "csrt": "h", "typ": "I" },
    "ḥ": { "srt": "176", "csrt": "h", "typ": "I" },
    "ì": { "srt": "189", "csrt": "i", "typ": "I" },
    "i": { "͞": { "srt": "185", "csrt": "i", "typ": "I" },
      "̣": { "srt": "186", "csrt": "i", "typ": "I" },
      "̲": { "srt": "186", "csrt": "e", "typ": "I" },
      "͟": { "srt": "186", "csrt": "e", "typ": "I" },
      "srt": "185", "csrt": "i", "typ": "I" },
    "ị": { "srt": "185", "csrt": "i", "typ": "I" },
    "í": { "srt": "189", "csrt": "i", "typ": "I" },
    "î": { "srt": "185", "csrt": "i", "typ": "I" },
    "ī": { "srt": "185", "csrt": "i", "typ": "I" },
    "j": { "̣": { "srt": "196", "csrt": "j", "typ": "I" },
      "srt": "195", "csrt": "j", "typ": "I" },
    "ĵ": { "srt": "195", "csrt": "j", "typ": "I" },
    "k": { "̣": { "srt": "206", "csrt": "k", "typ": "I" },
      "srt": "205", "csrt": "k", "typ": "I" },
    "ḳ": { "srt": "205", "csrt": "k", "typ": "I" },
    "l": { "̣": { "srt": "216", "csrt": "l", "typ": "I" },
      "̲": { "srt": "216", "csrt": "e", "typ": "I" },
      "͟": { "srt": "216", "csrt": "e", "typ": "I" },
      "srt": "215", "csrt": "l", "typ": "I" },
    "ḷ": { "srt": "215", "csrt": "l", "typ": "I" },
    "m": { "̂": { "srt": "225", "csrt": "m", "typ": "I" },
      "̣": { "srt": "225", "csrt": "m", "typ": "I" },
      " ̄": { "srt": "225", "csrt": "m", "typ": "I" },
      "srt": "225", "csrt": "m", "typ": "I" },
    "ṃ": { "srt": "225", "csrt": "m", "typ": "I" },
    "n": { "̂": { "srt": "235", "csrt": "n", "typ": "I" },
      "̣": { "srt": "235", "csrt": "n", "typ": "I" },
      "srt": "235", "csrt": "n", "typ": "I" },
    "ṇ": { "srt": "235", "csrt": "n", "typ": "I" },
    "o": { "̂": { "srt": "248", "csrt": "o", "typ": "I" },
      "̣": { "srt": "246", "csrt": "o", "typ": "I" },
      "srt": "245", "csrt": "o", "typ": "I" },
    "ó": { "srt": "249", "csrt": "o", "typ": "I" },
    "ọ": { "srt": "246", "csrt": "o", "typ": "I" },
    "ô": { "srt": "248", "csrt": "o", "typ": "I" },
    "p": { "̣": { "srt": "256", "csrt": "p", "typ": "I" },
      "srt": "255", "csrt": "p", "typ": "I" },
    "q": { "̣": { "srt": "266", "csrt": "q", "typ": "I" },
      "srt": "265", "csrt": "q", "typ": "I" },
    "r": { "̣": { "srt": "276", "csrt": "r", "typ": "I" },
      "srt": "275", "csrt": "r", "typ": "I" },
    "ṛ": { "srt": "276", "csrt": "r", "typ": "I" },
    "s": { "̂": { "srt": "288", "csrt": "n", "typ": "I" },
      "̣": { "srt": "286", "csrt": "s", "typ": "I" },
      "̲": { "srt": "286", "csrt": "e", "typ": "I" },
      "͟": { "srt": "286", "csrt": "e", "typ": "I" },
      "srt": "285", "csrt": "s", "typ": "I" },
    "ŝ": { "srt": "285", "csrt": "s", "typ": "I" },
    "ṣ": { "srt": "286", "csrt": "s", "typ": "I" },
    "t": { "̂": { "srt": "295", "csrt": "n", "typ": "I" },
      "̣": { "srt": "295", "csrt": "t", "typ": "I" },
      "srt": "295", "csrt": "t", "typ": "I" },
    "ṭ": { "srt": "295", "csrt": "t", "typ": "I" },
    "u": { 
      "̂": { "srt": "308", "csrt": "u", "typ": "I" },
      "̣": { "srt": "306", "csrt": "u", "typ": "I" },
      "̲": { "srt": "306", "csrt": "e", "typ": "I" },
      "͟": { "srt": "306", "csrt": "e", "typ": "I" },
      "srt": "305", "csrt": "u", "typ": "I" },
    "ú": { "srt": "305", "csrt": "u", "typ": "I" },
    "û": { "srt": "305", "csrt": "u", "typ": "I" },
    "ụ": { "srt": "305", "csrt": "u", "typ": "I" },
    "ⅎ": { "srt": "306", "csrt": "u", "typ": "I" },
    "Ⅎ": { "srt": "306", "csrt": "u", "typ": "I" },
    "ↄ": { "srt": "307", "csrt": "u", "typ": "I" },
    "Ↄ": { "srt": "307", "csrt": "u", "typ": "I" },
    "ⱶ": { "srt": "308", "csrt": "u", "typ": "I" },
    "Ⱶ": { "srt": "308", "csrt": "u", "typ": "I" },
    "v": { "̂": { "srt": "315", "csrt": "v", "typ": "I" },
      "̣": { "srt": "315", "csrt": "v", "typ": "I" },
      "srt": "315", "csrt": "v", "typ": "I" },
    "ṿ": { "srt": "315", "csrt": "v", "typ": "I" },
    "w": { "": { "srt": "325", "csrt": "w", "typ": "I" },
      "̣": { "srt": "325", "csrt": "w", "typ": "I" },
      "srt": "325", "csrt": "w", "typ": "I" },
    "ẉ": { "srt": "325", "csrt": "w", "typ": "I" },
    "x": { "̣": { "srt": "335", "csrt": "x", "typ": "I" },
      "srt": "335", "csrt": "x", "typ": "I" },
    "y": { "̣": { "srt": "345", "csrt": "y", "typ": "I" },
      "srt": "345", "csrt": "y", "typ": "I" },
    "ŷ": { "srt": "345", "csrt": "y", "typ": "I" },
    "ỵ": { "srt": "345", "csrt": "y", "typ": "I" },
    "z": { "̣": { "srt": "355", "csrt": "z", "typ": "I" },
      "srt": "355", "csrt": "z", "typ": "I" },
    "ẓ": { "srt": "355", "csrt": "z", "typ": "I" },
    "Ἀ": { "srt": "400", "typ": "I" },
    "Α": { "srt": "400", "typ": "I" },
    "Β": { "srt": "410", "typ": "I" },
    "Γ": { "srt": "420", "typ": "I" },
    "Δ": { "srt": "430", "typ": "I" },
    "Ε": { "srt": "440", "typ": "I" },
    "Ζ": { "srt": "450", "typ": "I" },
    "Η": { "srt": "460", "typ": "I" },
    "Θ": { "srt": "470", "typ": "I" },
    "Ι": { "srt": "480", "typ": "I" },
    "Κ": { "srt": "185", "typ": "I" },
    "Λ": { "srt": "195", "typ": "I" },
    "Μ": { "srt": "205", "typ": "I" },
    "Ν": { "srt": "215", "typ": "I" },
    "Ξ": { "srt": "225", "typ": "I" },
    "Ο": { "srt": "235", "typ": "I" },
    "Π": { "srt": "245", "typ": "I" },
    "Ρ": { "srt": "255", "typ": "I" },
    "Σ": { "srt": "275", "typ": "I" },
    "Τ": { "srt": "285", "typ": "I" },
    "Υ": { "srt": "295", "typ": "I" },
    "Φ": { "srt": "305", "typ": "I" },
    "Χ": { "srt": "315", "typ": "I" },
    "Ψ": { "srt": "325", "typ": "I" },
    "Ω": { "srt": "335", "typ": "I" },
    "ἀ": { "srt": "405", "typ": "I" },
    "α": { "srt": "405", "typ": "I" },
    "β": { "srt": "415", "typ": "I" },
    "γ": { "srt": "425", "typ": "I" },
    "δ": { "srt": "435", "typ": "I" },
    "ε": { "srt": "445", "typ": "I" },
    "ἔ": { "srt": "445", "typ": "I" },
    "ζ": { "srt": "455", "typ": "I" },
    "η": { "srt": "465", "typ": "I" },
    "θ": { "srt": "475", "typ": "I" },
    "ι": { "srt": "485", "typ": "I" },
    "ί": { "srt": "485", "typ": "I" },
    "κ": { "srt": "495", "typ": "I" },
    "λ": { "srt": "505", "typ": "I" },
    "μ": { "srt": "515", "typ": "I" },
    "ν": { "srt": "525", "typ": "I" },
    "ξ": { "srt": "535", "typ": "I" },
    "ο": { "srt": "545", "typ": "I" },
    "π": { "srt": "555", "typ": "I" },
    "ρ": { "srt": "565", "typ": "I" },
    "ς": { "srt": "575", "typ": "I" },
    "ϛ": { "srt": "575", "typ": "I" },
    "σ": { "srt": "585", "typ": "I" },
    "τ": { "srt": "595", "typ": "I" },
    "υ": { "srt": "605", "typ": "I" },
    "ῦ": { "srt": "605", "typ": "I" },
    "φ": { "srt": "615", "typ": "I" },
    "ό": { "srt": "615", "typ": "I" },
    "χ": { "srt": "625", "typ": "I" },
    "ψ": { "srt": "635", "typ": "I" },
    "ω": { "srt": "645", "typ": "I" },
    "א": { "srt": "345", "typ": "I" },
    "ב": { "srt": "345", "typ": "I" },
    "ג": { "srt": "345", "typ": "I" },
    "ד": { "srt": "345", "typ": "I" },
    "ה": { "srt": "345", "typ": "I" },
    "ו": { "srt": "345", "typ": "I" },
    "ז": { "srt": "345", "typ": "I" },
    "ח": { "srt": "345", "typ": "I" },
    "ט": { "srt": "345", "typ": "I" },
    "י": { "srt": "345", "typ": "I" },
    "ך": { "srt": "345", "typ": "I" },
    "כ": { "srt": "345", "typ": "I" },
    "ל": { "srt": "345", "typ": "I" },
    "ם": { "srt": "345", "typ": "I" },
    "מ": { "srt": "345", "typ": "I" },
    "ן": { "srt": "345", "typ": "I" },
    "נ": { "srt": "345", "typ": "I" },
    "ס": { "srt": "345", "typ": "I" },
    "ע": { "srt": "345", "typ": "I" },
    "ף": { "srt": "345", "typ": "I" },
    "פ": { "srt": "345", "typ": "I" },
    "ץ": { "srt": "345", "typ": "I" },
    "צ": { "srt": "345", "typ": "I" },
    "ק": { "srt": "345", "typ": "I" },
    "ר": { "srt": "345", "typ": "I" },
    "ש": { "srt": "345", "typ": "I" },
    "ת": { "srt": "345", "typ": "I" } 
  } 
}