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
* managers dataManager object
*
* handles retreieval and update of data
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
* Constructor for Data Manager Object
*
* @type Object
*
* @param dataMgrCfg is a JSON object with the following possible properties
*  "entTagToLabel" reference to lookup table for term entTag to location label
*  "entTagToPath" reference to lookup table for term entTag to location label
*  "tagIDToAnoID" reference to lookup table for tag term id to Annotation entity id
*  "tags" reference to tag items hierarchical information structure
*  "seqTypeTagToLabel" reference to lookup table for sequence type term entTag to label
*  "seqTypeTagToList" reference to lookup table for sequence type term entTag to term list ids
*  "seqTypes" reference to sequence type items hierarchical information structure
*  "linkTypeTagToLabel" reference to lookup table for link type term entTag to label
*  "linkTypeTagToList" reference to lookup table for link type term entTag to term list ids
*  "linkTypes" reference to link type items hierarchical information structure
*  "basepath" string for basepath of READ
*  "dbname" string for database name of the current session
*  "entityInfo" structure of information of entity types
*  "username" current user name
*
* @returns {DataManager}

*/

MANAGERS.DataManager =  function(dataMgrCfg) {
  this.config = dataMgrCfg;
  this.entTagToLabel = dataMgrCfg['entTagToLabel'] ? dataMgrCfg['entTagToLabel']:"";
  this.entTagToPath = dataMgrCfg['entTagToPath'] ? dataMgrCfg['entTagToPath']:"";
  this.tagIDToAnoID = dataMgrCfg['tagIDToAnoID'] ? dataMgrCfg['tagIDToAnoID']:"";
  this.tags = dataMgrCfg['tags'] ? dataMgrCfg['tags']:"";
  this.seqTypeTagToLabel = dataMgrCfg['seqTypeTagToLabel'] ? dataMgrCfg['seqTypeTagToLabel']:"";
  this.seqTypeTagToList = dataMgrCfg['seqTypeTagToList'] ? dataMgrCfg['seqTypeTagToList']:"";
  this.seqTypes = dataMgrCfg['seqTypes'] ? dataMgrCfg['seqTypes']:"";
  this.linkTypeTagToLabel = dataMgrCfg['linkTypeTagToLabel'] ? dataMgrCfg['linkTypeTagToLabel']:"";
  this.linkTypeTagToList = dataMgrCfg['linkTypeTagToList'] ? dataMgrCfg['linkTypeTagToList']:"";
  this.linkTypes = dataMgrCfg['linkTypes'] ? dataMgrCfg['linkTypes']:"";
  this.basepath = dataMgrCfg['basepath'] ? dataMgrCfg['basepath']:"";
  this.dbName = dataMgrCfg['dbname'] ? dataMgrCfg['dbname']:null;
  this.entityInfo = dataMgrCfg['entityInfo'] ? dataMgrCfg['entityInfo']:entityInfo;
  this.username = dataMgrCfg['username'] && dataMgrCfg['username'] != "unknown" ? dataMgrCfg['username']:null;
  this.init();
  return this;
};

/**
* Prototype for Data Manager Object
*/

MANAGERS.DataManager.prototype = {

/**
* Initialiser for Data Manager Object
*/

  init: function() {
    DEBUG.traceEntry("dataMgr.init","");
    var dataMgr = this;
    this.loadingCatalog = false;
    this.loadingText = false;
    this.loadingEdition = false;
    this.textResourcesLoaded = false;
    this.entities = {};
    this.switchInfoByTextID = {};
    this.switchLookup = {};
    this.cknToTxtID = {};
    this.termInfo = {};
    this.loadedText = {}
    this.loadedBaseline = {}
    this.loadedEdition = {}
    this.loadedCatalog = {}
    this.textUnavailable = {}
    this.baselineUnavailable = {}
    this.editionUnavailable = {}
    this.catalogUnavailable = {}
    this.loadAttributions();
    DEBUG.traceExit("dataMgr.init","");
  },


/**
* reset Data Manager's cached information
*/

  flushLocalCache: function () {
    DEBUG.traceEntry("flushLocalCache","");
    this.loadingCatalog = false;
    this.loadingText = false;
    this.loadingEdition = false;
    this.textResourcesLoaded = false;
    this.entities = {};
    this.switchInfoByTextID = {};
    this.cknToTxtID = {};
    this.termInfo = {};
    this.loadedText = {}
    this.loadedBaseline = {}
    this.loadedEdition = {}
    this.loadedCatalog = {}
    this.textUnavailable = {}
    this.baselineUnavailable = {}
    this.editionUnavailable = {}
    this.catalogUnavailable = {}
    DEBUG.traceExit("flushLocalCache","");
  },


/**
* update local cache from passed data
*
* @param object retData Structure of entity and lookup data to be updated,added, or removed for cache
* @param int txtID Text entity id
*/

  updateLocalCache: function (retData, txtID) {
    DEBUG.traceEntry("updateLocalCache","");
    var entities = this.entities,
        prefix, entID, ckn;
    //insert new data
    if (retData.entities.insert) {
      var inserts = retData.entities.insert;
      for (prefix in inserts) {
        for (entID in inserts[prefix]) {
          if (!this.entities[prefix]) {
            this.entities[prefix] = {};
          }
          this.entities[prefix][entID] = inserts[prefix][entID];
         DEBUG.log("data","inserted " + prefix + entID + " entity");
        }
      }
      for (prefix in inserts) {
        for (entID in inserts[prefix]) {
          if (prefix == 'scl' || prefix == 'tok' || prefix == 'cmp') {
            this.calcSwitchHash(prefix,entID);
          }
        }
      }
    }
    //update data
    if (retData.entities.update) {
      var updates = retData.entities.update, prop;
      for (prefix in updates) {
        for (entID in updates[prefix]) {
          for (prop in updates[prefix][entID]) {
            if (!this.entities[prefix]) {
              this.entities[prefix] = {};
            }
            if (!this.entities[prefix][entID]) {
              this.entities[prefix][entID] = {};
            }
            this.entities[prefix][entID][prop] = updates[prefix][entID][prop];
            DEBUG.log("data","updated " + prefix + entID + " " + prop + " property");
            if (prop == "linkedAnoIDsByType") {//annotations changed
              if (!this.entTag2LinkedAnoIDsByType) {
                this.entTag2LinkedAnoIDsByType = {};
              }
              this.entTag2LinkedAnoIDsByType[prefix + entID]=updates[prefix][entID][prop];
            }
            if (prop == "linkedByAnoIDsByType") {//annotations changed
              if (!this.entTag2LinkedByAnoIDsByType) {
                this.entTag2LinkedByAnoIDsByType = {};
              }
              this.entTag2LinkedByAnoIDsByType[prefix + entID]=updates[prefix][entID][prop];
            }
          }
        }
      }
      for (prefix in updates) {
        for (entID in updates[prefix]) {
          for (prop in updates[prefix][entID]) {
            if ((prop == 'entityIDs' || prop == 'graphemeIDs') &&
                (prefix == 'scl' || prefix == 'tok' || prefix == 'cmp')) {
              this.calcSwitchHash(prefix,entID);
              continue;
            }
          }
        }
      }
    }
    //link data add a link id or tag to a calculated array
    if (retData.entities.link) {
      var links = retData.entities.link, prop,entTag;
      for (prefix in links) {
        for (entID in links[prefix]) {
          for (prop in links[prefix][entID]) {
            if (this.entities[prefix] && this.entities[prefix][entID]) {
              if (!this.entities[prefix][entID][prop]) {
                this.entities[prefix][entID][prop] = [];
              }
              if (this.entities[prefix][entID][prop].indexOf(links[prefix][entID][prop]) == -1) {
                this.entities[prefix][entID][prop].push(links[prefix][entID][prop]);
                DEBUG.log("data","link " + prefix + entID + " " + prop + " property using " + links[prefix][entID][prop]);
              }
            }
          }
        }
      }
    }
    //remove entity from switch lookup
    if (retData.entities.removeswitchhashes) {
      var removeEntTags = retData.entities.removeswitchhashes,
          entTag, entity, i, hash, index;
      for (i in removeEntTags) {
        entTag = removeEntTags[i];
        entity = this.getEntityFromGID(entTag);
        if (entity && entity.startSegID) {
          oldStartID = entity.startSegID;
          oldEndID = entity.endSegID;
          if (oldStartID && oldEndID && this.switchLookup[oldStartID]
              && this.switchLookup[oldStartID][oldEndID] && this.switchLookup[oldStartID][oldEndID][entTag]) {
            delete this.switchLookup[oldStartID][oldEndID][entTag];
          }
        }
      }
    }

    //remove old data
    if (retData.entities.remove) {
      var removals = retData.entities.remove,
          index;
      for (prefix in removals) {
        for (index in removals[prefix]) {
          entID = removals[prefix][index];
          if (this.entities[prefix] && this.entities[prefix][entID] ) {
            delete this.entities[prefix][entID];
            DEBUG.log("data","removed " + prefix + entID + " entity");
          }
        }
      }
    }
    //update tags info
    if (retData.tagsInfo) {
      if (retData.tagsInfo.tags){
        this.tags = retData.tagsInfo.tags;
      }
      if (retData.tagsInfo.entTagToLabel){
        this.entTagToLabel = retData.tagsInfo.entTagToLabel;
      }
      if (retData.tagsInfo.entTagToPath){
        this.entTagToPath = retData.tagsInfo.entTagToPath;
      }
      if (retData.tagsInfo.tagIDToAnoID){
        this.tagIDToAnoID = retData.tagsInfo.tagIDToAnoID;
      }
    }
    //update ckn to ID map
    if (retData.cknToTextID) {
      for (ckn in retData.cknToTextID) {
        this.cknToTxtID[ckn] = retData.cknToTextID[ckn];
      }
    }
    //update termInfo
    if (retData.termInfo) {
      this.termInfo = retData.termInfo;
    }
    //update attributions
    if (retData.attrs) {//todo check if we need to update the tree Lookups
      this.attrs = retData.attrs;
    }
    DEBUG.traceExit("updateLocalCache","");
  },


/**
* calculate switch hash for an entity
*
* @param string prefix Short string indicating the type of entity
* @param int entID Entity id of the target entity
*/

  calcSwitchHash: function (prefix, entID) {
    var startID, endID, oldStartID, oldEndID, startSclID, endSclID, startTokID, endTokID, entity;
    if (this.entities[prefix] && this.entities[prefix][entID]) {
      entity = this.entities[prefix][entID];
      switch (prefix) {
        case "scl":
          if (entity['segID']) {
            startID = endID = entity['segID'];
          } else {
            DEBUG.log("error",'in dataManager.calcSwitchHash syllable id ' + entID + ' is missing segment id.');
          }
          break;
        case "tok":
          if (entity['syllableClusterIDs']) {
            startSclID = entity['syllableClusterIDs'][0];
            endSclID = entity['syllableClusterIDs'][entity['syllableClusterIDs'].length-1];
            if (startSclID && this.entities['scl'][startSclID] && this.entities['scl'][startSclID]['segID']) {
              startID = this.entities['scl'][startSclID]['segID'];
            } else {
              DEBUG.log("error",'in dataManager.calcSwitchHash token id ' + entID + ' start syllable invalid or missing segment id.');
            }
            if (endSclID && this.entities['scl'][endSclID] && this.entities['scl'][endSclID]['segID']) {
              endID = this.entities['scl'][endSclID]['segID'];
            } else {
              DEBUG.log("error",'in dataManager.calcSwitchHash token id ' + entID + ' end syllable invalid or missing segment id.');
            }
          } else {
            DEBUG.log("error",'in dataManager.calcSwitchHash token id ' + entID + ' is missing syllables IDs.');
          }
          break;
        case "cmp":
          if (entity['tokenIDs']) {
            startTokID = entity['tokenIDs'][0];
            endTokID = entity['tokenIDs'][entity['tokenIDs'].length-1];
            if (startTokID && this.entities['tok'][startTokID] && this.entities['tok'][startTokID]['syllableClusterIDs']) {
              startSclID = this.entities['tok'][startTokID]['syllableClusterIDs'][0];
              if (startSclID && this.entities['scl'][startSclID] && this.entities['scl'][startSclID]['segID']) {
                startID = this.entities['scl'][startSclID]['segID'];
              } else {
                DEBUG.log("error",'in dataManager.calcSwitchHash start token id ' + startTokID + ' start syllable invalid or missing segment id.');
              }
            } else {
              DEBUG.log("error",'in dataManager.calcSwitchHash start token id ' + startTokID + ' is missing syllables IDs.');
            }
            if (endTokID && this.entities['tok'][endTokID] && this.entities['tok'][endTokID]['syllableClusterIDs']) {
              endSclID = this.entities['tok'][endTokID]['syllableClusterIDs'][this.entities['tok'][endTokID]['syllableClusterIDs'].length-1];
              if (endSclID && this.entities['scl'][endSclID] && this.entities['scl'][endSclID]['segID']) {
                endID = this.entities['scl'][endSclID]['segID'];
              } else {
                DEBUG.log("error",'in dataManager.calcSwitchHash end token id ' + endTokID + ' end syllable invalid or missing segment id.');
              }
            } else {
              DEBUG.log("error",'in dataManager.calcSwitchHash end token id ' + endTokID + ' is missing syllables IDs.');
            }
          } else {
            DEBUG.log("error",'in dataManager.calcSwitchHash compound id ' + entID + ' is missing token IDs.');
          }
          break;
      }//end switch
      tag = prefix+entID;
      if (startID && endID) {
        if (entity.startSegID) {// saveprevious hash
          oldStartID = entity.startSegID;
          oldEndID = entity.endSegID;
        }
        if ( oldStartID == startID && oldEndID == endID) {// nothing changed so warning and nothing to do
          DEBUG.log("warn","segIDs for entity " + tag + " have not changed nothing to do");
        } else {
          entity.startSegID = startID;
          entity.endSegID = endID;
          //remove tag from old hash
          if (oldStartID && oldEndID && this.switchLookup[oldStartID]
              && this.switchLookup[oldStartID][oldEndID] && this.switchLookup[oldStartID][oldEndID][tag]) {
            delete this.switchLookup[oldStartID][oldEndID][tag];
          }
          if (!this.switchLookup[startID]) {
            this.switchLookup[startID]= {};
          }
          if (!this.switchLookup[startID][endID]) {
            this.switchLookup[startID][endID] = {};
          }
          if (!this.switchLookup[startID][endID][tag]) {
            this.switchLookup[startID][endID][tag] = 1;
          }
        }
      }
      if (!startID) {
        DEBUG.log("error","start segID for entity " + tag + " was not found not adding switch info");
      }
      if (!endID) {
        DEBUG.log("error","end segID for entity " + tag + " was not found not adding switch info");
      }
    } else {
      DEBUG.log("error",'in dataManager.calcSwitchHash entity not found ' + prefix + entID + ' is missing from local cache.');
    }
  },


/**
* remove entity from cache
*
* @param string prefix Short string indicating the type of entity
* @param int entID Entity id of the target entity
*/

  removeEntityFromCache: function(prefix,entID) {//caution this may leave dangling reference
    delete this.entities[prefix][entID];
  },


/**
* find paleography tag items
*
* @returns object array Item structures for jqWidget listbox
*/

  getPaleographyTags: function() {
    var paleoTags = null,i;
    if (this.tags) {
      if (!this.paleoTagIndex || this.tags[this.paleoTagIndex].label && this.tags[this.paleoTagIndex].label != "Paleography") {
        this.paleoTagIndex = null;
        for (i in this.tags){
          if (this.tags[i].label && this.tags[i].label == "Paleography") {
            this.paleoTagIndex = i;
          }
        }
      }
      if (this.paleoTagIndex && this.tags[this.paleoTagIndex].items) {
        paleoTags = this.tags[this.paleoTagIndex].items;
      }
    }
    return paleoTags;
  },


/**
* get entity from Entity global id
*
* @param string gid Entity global id
*/

  getEntityFromGID: function(gid) {
    var tag = gid.replace(":",""),
        prefix = tag.substr(0,3),
        id = tag.substr(3);
    return this.getEntity(prefix,id);
  },


/**
* get entity from prefix and id
*
* @param string prefix Short string indicating the type of entity
* @param int entID Entity id of the target entity
*/

  getEntity: function(prefix,entID) {
    var dataMgr = this;
    if (this.entities && this.entities[prefix] && this.entities[prefix][entID]) {
      return this.entities[prefix][entID];
    }
    return null;
  },


/**
* check entity type
*
* @param entity
* @param typeTerm
*
* @returns true|false
*/

  checkEntityType: function(entity,typeTerm) {
    var dataMgr = this, term;
    if (entity && entity['type']) {
      return (this.getTermFromID(entity['type']) == typeTerm);
    } else if (entity && entity['typeID']) {
      return (this.getTermFromID(entity['typeID']) == typeTerm);
    }
    return false;
  },


/**
* get text entity id from text inventory id
*
* @param string ckn Text inventory id
*
*  @returns int | null Text entity id
*/

  getTextIDFromCKN: function(ckn) {
    var dataMgr = this;
    if (this.cknToTxtID &&  this.cknToTxtID[ckn]) {
      return this.cknToTxtID[ckn];
    }
    return null;
  },


/**
* get text entity id from entity tag
*
* @param string tag Text entity tag
*
*  @returns int | null Text entity id
*/

  getTextIDFromEntityTag: function(tag) {
    var dataMgr = this, entity,
        prefix = tag.substr(0,3),
        id = tag.substr(3);
    if (this.entities && this.entities[prefix] && this.entities[prefix][id]) {
      entity = this.entities[prefix][id];
      if ( entity.txtID ) {
        return entity.txtID;
      } else if ( entity.textIDs ) {
        return entity.textIDs[0];  //TODO need to discuss a selection strategy for multiple text (surface)
      } else if (entity.tmdID && this.entities.tmd
                  && this.entities.tmd[entity.tmdID]
                  && this.entities.tmd[entity.tmdID].txtID) {
        return this.entities.tmd[entity.tmdID].txtID;
      }
    }
    return null;
  },


/**
* get term label from term id
*
* @param int trmID Term entity id
*
* @returns string Term label or empty string
*/

  getTermFromID: function(trmID) {
    var dataMgr = this;
    if (this.termInfo && this.termInfo.labelByID && this.termInfo.labelByID[trmID]) {
      return this.termInfo.labelByID[trmID];
    }
    return '';
  },


/**
* get term id from term label - parent term label
*
* @param string term Term label
* @param string parentTerm Term label of parent term
*
*  @returns int | null Term entity id
*/

  getIDFromTermParentTerm: function(term,parentTerm) {
    var label = term.toLowerCase() + '-' + parentTerm.toLowerCase();
    if (this.termInfo && this.termInfo.idByTerm_ParentLabel && this.termInfo.idByTerm_ParentLabel[label]) {
      return this.termInfo.idByTerm_ParentLabel[label];
    }
    return '';
  },


/**
* get term list from term id
*
* @param int trmID Term entity id
*
*  @returns int[] | null List Term entity ids or null
*/

  getTermListFromID: function(trmID) {
    var dataMgr = this, list;
    if (this.termInfo && this.termInfo.termByID &&
        this.termInfo.termByID[trmID] && this.termInfo.termByID[trmID]['trm_list_ids']) {
      list = this.termInfo.termByID[trmID]['trm_list_ids'];
      return list.replace(/[\{\}]/g,"").split(",");
    }
    return null;
  },


/**
* test if text data is loaded
*
* @param string tagID Text entity id or Entity tag
*
* @returns true|false
*/

  isTextLoaded: function(tagID) {
    var txtID;
    if (isNaN(tagID)) {
      txtID = this.getTextIDFromEntityTag(tagID);
    } else {
      txtID = tagID;
    }
    if (txtID && (this.loadedText && this.loadedText[txtID] || this.entities.txt && this.entities.txt[txtID])) {
      return true;
    }
    return false;
  },


/**
* test availability of text for a given entity tag
*
* @param string tagID Text entity id or Entity tag
*
* @returns true|false
*/

  textForTagUnavailable: function(tagID) {
    if (!tagID || (this.textUnavailable && this.textUnavailable[tagID] )) {
      return true;
    }
    return false;
  },


/**
* load text given an associated entity tag
*
* @param string tagID Text entity id or Entity tag
* @param function cb Callback function
*/

  loadTextForEntityTag: function(tagID,cb) {
    var dataMgr = this, txtID = this.getTextIDFromEntityTag(tagID);
    if (txtID) {
      this.loadText(txtID,cb);
    }
  },


/**
* get matching entity tags given and entity tag
*
* @param string entTag
* @param string entTag Entity tag
* @param int ednID Edition entity id
*
* @returns string[] Array of matching entity tags
*/

  getMatchEntities: function(entTag,ednID) {
    var dataMgr = this, matchSet = [],
        prefix = entTag.substring(0,3),
        entID = entTag.substring(3),
        entity;
    if (prefix == 'scl' || prefix == 'tok' || prefix == 'cmp' ) {
      entity = this.getEntityFromGID(entTag);
      if (entity && !entity.startSegID) {
        this.calcSwitchHash(prefix,entID);
      }
      if (entity && entity.startSegID && entity.endSegID
           && this.switchLookup && this.switchLookup[entity.startSegID]
           && this.switchLookup[entity.startSegID][entity.endSegID]
           && this.switchLookup[entity.startSegID][entity.endSegID][entTag]) {
        matchSet = Object.keys(this.switchLookup[entity.startSegID][entity.endSegID]);
      }
    }
    return matchSet;
  },


/**
* stack load text calls
*
* @param string tagID Text entity id or Entity tag
* @param function cb Callback function
*/

  stackLoadText: function(tagID,cb) {
    if (!this.txtStack) {
      this.txtStack = {};
    }
    if (!this.txtStack[tagID]) {
      this.txtStack[tagID] = cb;
    }
  },


/**
* process load text call stack
*/

  processLoadTextStack: function() {
    var txtID, cb;
    if (!this.loadingText && this.txtStack &&
          Object.keys(this.txtStack).length) {
      for (tagID in this.txtStack) {
        cb = this.txtStack[tagID];
        delete this.txtStack[tagID];
        this.loadText(tagID, cb);
      }
    }
  },


/**
* load text
*
* @param string tagID Text entity id or Entity tag
* @param function cb Callback function
*/

  loadText: function(tagID,cb) {
    DEBUG.traceEntry("dataMgr.loadText","tagID = " + tagID);
    var dataMgr = this, dataQuery = "",txtID = null,
        i,temp;
    if (dataMgr.loadingText) {
      if (tagID == dataMgr.loadingText) {
        DEBUG.log("warn"," call with same txtID to loadText which is not re-entrant, cancelling this request to load txtID "+ txtID);
      } else if (!this.textForTagUnavailable(tagID)){
        this.stackLoadText(tagID,cb);
      }
      DEBUG.traceExit("dataMgr.loadText","2 tagID = " + tagID);
      return;
    }
    if (tagID && !this.isTextLoaded(tagID) && !this.textForTagUnavailable(tagID)) {//test load again as call may have been on stack and another task may have loaded it
      dataQuery = {ids:tagID};
      dataMgr.loadingText = tagID;
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: this.basepath+'/services/loadTextEntities.php?db='+this.dbName,//caution dependency on context having basepath and dbName
          data: dataQuery,
          async: true,
          success: function (data, status, xhr) {
              DEBUG.traceEntry("dataMgr.loadText.SuccessCB","tagID = " + tagID);
              var loadedTxtID;
              dataMgr.loadingText = 0;
              if (typeof data == 'object' && data.entities) {
                if (!data.entities.insert) {
                  temp = data.entities;
                  delete data.entities;
                  data.entities = {};
                  data.entities['insert'] = temp;
                }
                dataMgr.updateLocalCache(data, null);
                //update any switch info returned
                if (data.switchInfoByTextID) {
                  for (txtID in data.switchInfoByTextID) {
                    if (!data.switchInfoByTextID[txtID].error) {
                      dataMgr.switchInfoByTextID[txtID] = data.switchInfoByTextID[txtID];
                    }
                  }
                }
                //flag text as loaded
                if (data.entities.insert && data.entities.insert.txt) {
                  for (loadedTxtID in data.entities.insert.txt) {
                    dataMgr.loadedText[loadedTxtID] = 1;
                  }
                }
              } else {//nothing found for tag so mark it as not available
                dataMgr.textUnavailable[tagID] = 1;
              }
              if (data.warnings) {
                DEBUG.log("warn", "warnings during loadText - " + data.warnings.join(" : "));
              }
              if (cb && typeof cb == "function") {
                cb();
              }
              dataMgr.processLoadTextStack();
              DEBUG.traceExit("dataMgr.loadText.SuccessCB","tagID = " + tagID);
          },
          error: function (xhr,status,error) {
              // add record failed.
              dataMgr.loadingText = 0;
              dataMgr.processLoadTextStack();
              alert("An error occurred while trying to retrieve text data records. Error: " + error);
          }
      });
    } else if (cb && typeof cb == "function") {
      cb();
    }
    DEBUG.traceExit("dataMgr.loadText","1 tagID = " + tagID);
  },


/**
* stack load edition calls
*
* @param int ednID Edition entity id
* @param function cb Callback function
*/

  stackLoadEdition: function(ednID,cb) {
    DEBUG.traceEntry("dataMgr.stackLoadEdition",'ednID = '+ednID+(cb?" with cb":""));
    if (!this.ednStack) {
      this.ednStack = {};
    }
    if (!this.ednStack[ednID]) {
      this.ednStack[ednID] = [];
    }
    this.ednStack[ednID].push(cb);
    DEBUG.traceExit("dataMgr.stackLoadEdition",'ednID = '+ednID+(cb?" with cb":""));
  },


/**
* process load edition call stack
*/

  processLoadEditionStack: function() {
    DEBUG.traceEntry("dataMgr.processLoadEditionStack","");
    var ednID, cb,exitMsg = "stack empty";
    if (!this.loadingEdition && this.ednStack &&
          Object.keys(this.ednStack).length) {
      for (ednID in this.ednStack) {
        if (this.ednStack[ednID] && this.ednStack[ednID].length) {
          cb = this.ednStack[ednID].shift();
          if (this.ednStack[ednID].length == 0) {
            delete this.ednStack[ednID];
          }
          break; // only get the first one
        }
      }
      if (ednID) {
        DEBUG.trace('AfterStackPop loadEdition Call','ednID = '+ednID,1);
        this.loadEdition(ednID, cb);
        exitMsg = 'ednID = '+ ednID;
      }
    } else if (this.loadingEdition) {
      exitMsg = 'skip process - loading ednID = '+ this.loadingEdition;
    }
    DEBUG.traceExit("dataMgr.processLoadEditionStack",exitMsg);
  },


/**
* check alternative editions are loaded
*
* @param int ednID Edition entity id
*/

  checkAlternateEdition: function(ednID) {
    var dataMgr = this, txtID, index, altEdnID, altEdnIDs, cnt;
    if (!dataMgr.annoLoaded && !dataMgr.annoLoadFailed){
      dataMgr.loadAnnotations(function() {dataMgr.checkAlternateEdition(ednID);});
    }
    if (!ednID) {
      return;
    }
    if (this.entities && this.entities.edn &&
        this.entities.edn[ednID] && this.entities.edn[ednID]['txtID']) {
      txtID = this.entities.edn[ednID]['txtID'];
    } else { // unable to determine other editions so return
      return;
    }
    if (this.entities && this.entities.txt &&
        this.entities.txt[txtID] && this.entities.txt[txtID]['ednIDs']) {
      altEdnIDs = this.entities.txt[txtID]['ednIDs'];
      cnt = altEdnIDs.length;
      for (index in altEdnIDs) {
        altEdnID = altEdnIDs[index];
        if (altEdnID && altEdnID != ednID && !this.isEditionLoaded(altEdnID) && !this.editionForIDUnavailable(altEdnID)) {
          this.loadEdition(altEdnID, null);
          break; // load one at a time to Allow user selected editions to load with priority
        }
        cnt--;
      }
      if (cnt == 0) {// launch get Tags and Annotations after loading all editions.
//        setTimeout(function() { dataMgr.loadAnnotations();
//                              },50);
      }
    }
  },


/**
* calculate item structures for the catalog's list of unused editions
*
* @param int catID Catalog entity id
*
* @returns object array | null Item structures for jqWidget listbox
*/

  calcUnusedEditions: function(catID) {
    var dataMgr = this, catalog, txtID, usedTxtIDs={}, ednList = [],
    usedEdnIDs = [], index, ednID, ednIDs, edition, text, cnt, i;
    if (!catID) {
      return null;
    }
    if (this.entities && this.entities.cat && this.entities.cat[catID] &&
        this.entities.cat[catID]['ednIDs'] && this.entities.cat[catID]['ednIDs'].length ) {
      catalog = this.entities.cat[catID];
      ednIDs = catalog.ednIDs;
      for (i in ednIDs) { //find text ids for in use editions
        usedTxtIDs[this.entities.edn[ednIDs[i]]['txtID']] = 1;
      }
      for (txtID in usedTxtIDs) { // find all edition IDs for used Texts
        text = this.entities.txt[txtID];
        usedEdnIDs = usedEdnIDs.concat(text.ednIDs);
      }
    } else { // unable to determine other editions so return
      return null;
    }
    for (ednID in this.entities.edn) {
      if (usedEdnIDs.indexOf(ednID) == -1) {//if unused
        edition = this.entities.edn[ednID];
        if (edition && edition.value) {
          ednList.push({id:'edn'+ednID,label:edition.value,value:'edn'+ednID});
        }
      }
    }
    return ednList;
  },


/**
* test if an Edition entity is already loaded
*
* @param int ednID Edition entity id
*
* @returns true|false
*/

  isEditionLoaded: function(ednID) {
    if (ednID && !isNaN(ednID) && this.loadedEdition && this.loadedEdition[ednID] ) {
      return true;
    }
    return false;
  },


/**
* check availability of an edition
*
* @param int ednID Edition entity id
*
* @returns true|false
*/

  editionForIDUnavailable: function(ednID) {
    if (!ednID || (this.editionUnavailable && this.editionUnavailable[ednID] )) {
      return true;
    }
    return false;
  },


/**
* load edition
*
* @param int ednID Edition entity id
* @param function cb Callback function
* @param boolean refresh indicating to reload the edition
*/

  loadEdition: function(ednID,cb,refresh) {
    DEBUG.traceEntry("dataMgr.loadEdition","ednID = " + ednID);
    var dataMgr = this, dataQuery = "", exitMsg = "noop",
        i,temp;
    if (!this.textResourcesLoaded || dataMgr.loadingEdition || !dataMgr.annoLoaded) {
      if (dataMgr.loadingEdition && ednID == dataMgr.loadingEdition) {
        exitMsg = "skipping, already loading ednID = " + ednID;
        DEBUG.log("warn"," call with same txtID to loadEdition which is not re-entrant, cancelling this request to load ednID "+ ednID);
      } else if (!this.textResourcesLoaded || !this.editionForIDUnavailable(ednID)|| !dataMgr.annoLoaded){
        DEBUG.trace('Queuing loadEdition Call','ednID = '+ednID + (cb?" with callback":""));
        this.stackLoadEdition(ednID,cb);
        if (!dataMgr.annoLoaded) {
          dataMgr.loadAnnotations();
        }
        exitMsg = "queued loadCall for ednID = " + ednID;
      }
      DEBUG.traceExit("dataMgr.loadEdition","2 "+ exitMsg);
      return;
    }
    if (ednID && (!this.isEditionLoaded(ednID) || refresh) && !this.editionForIDUnavailable(ednID)) {//test load again as call may have been on stack and another task may have loaded it
      dataQuery = {edn:ednID};
      dataMgr.loadingEdition = ednID;
      exitMsg = "call ajax for ednID = " + ednID+(cb?" with cb":"");
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: this.basepath+'/services/loadTextEdition.php?db='+this.dbName,//caution dependency on context having basepath and dbName
          data: dataQuery,
          async: true,
          success: function (data, status, xhr) {
              DEBUG.traceEntry("dataMgr.loadEdition.SuccessCB","ednID = " + ednID);
              var callback = cb, callEdnID = ednID, loadIDs = "", loadedEdnID;
              dataMgr.loadingEdition = 0;
              if (typeof data == 'object' && data.entities && data.entities.update &&
                    data.entities.update.edn && Object.keys(data.entities.update.edn).length > 0
                    && Object.keys(data.entities.update.edn)[0] ) {
                if (!data.entities.insert) {
                  temp = data.entities;
                  delete data.entities;
                  data.entities = {};
                  data.entities['insert'] = temp;
                }
                for (var seqID in data.entities.insert.seq) {
                  var sequence = data.entities.insert.seq[seqID], children;
                  if (sequence['children']) {
                    for (var entType in sequence['children']) {
                      if (!data.entities['insert'][entType]) {
                        data.entities['insert'][entType] = sequence['children'][entType];
                      } else {
                        for (var entID in sequence['children'][entType]) {
                          data.entities['insert'][entType][entID] = sequence['children'][entType][entID];
                        }
                      }
                    }
                    delete sequence['children'];
                  }
                }
                dataMgr.updateLocalCache(data, null);
                //flag edition as loaded
                if (data.entities.update && data.entities.update.edn) {
                  for (loadedEdnID in data.entities.update.edn) {
                    dataMgr.loadedEdition[loadedEdnID] = 1;
                    loadIDs += " " + loadedEdnID;
                  }
                  DEBUG.trace('loaded Edition(s) ',' ednIDs are ' + loadIDs);
                }
              } else {//nothing found for ednID so mark it as not available
                dataMgr.editionUnavailable[ednID] = 1;
              }
              if (data.warnings) {
                DEBUG.log("warn", "warnings during loadEdition - " + data.warnings.join(" : "));
              }
              if (callback && typeof callback == "function") {
                DEBUG.trace('calling callback ',' for loaded edition ' + loadIDs);
                callback();
              }
              dataMgr.checkAlternateEdition(loadedEdnID);
              dataMgr.processLoadEditionStack();
              DEBUG.traceExit("dataMgr.loadEdition.SuccessCB","ednID = " + ednID);
          },
          error: function (xhr,status,error) {
              // add record failed.
              dataMgr.loadingEdition = 0;
              dataMgr.processLoadEditionStack();
              alert("An error occurred while trying to retrieve text edition data records. Error: " + error);
          }
      });
    } else if (cb && typeof cb == "function") {
      cb();
    }
    DEBUG.traceExit("dataMgr.loadEdition","1 " + exitMsg);
  },


/**
* stack Load Basline calls
*
* @param int blnID Baseline entity id
* @param function cb Callback function
*/

  stackLoadBaseline: function(blnID,cb) {
    if (!this.blnStack) {
      this.blnStack = {};
    }
    if (!this.blnStack[blnID]) {
      this.blnStack[blnID] = cb;
    }
  },


/**
* process load baseline call stack
*/

  processLoadBaselineStack: function() {
    var blnID, cb;
    if (!this.loadingBaseline && this.blnStack &&
          Object.keys(this.blnStack).length) {
      for (blnID in this.blnStack) {
        cb = this.blnStack[blnID];
        delete this.blnStack[blnID];
        break; // only get the first one
      }
      if (blnID) {
        this.loadBaseline(blnID, cb);
      }
    }
  },


/**
* test if a baseline is loaded
*
* @param int blnID Baseline entity id
*
* @returns true|false
*/

  isBaselineLoaded: function(blnID) {
    if (blnID && !isNaN(blnID) && this.loadedBaseline && this.loadedBaseline[blnID] ) {
      return true;
    }
    return false;
  },


/**
* test availability of a baseline
*
* @param int blnID Baseline entity id
*
* @returns true|false
*/

  baselineForIDUnavailable: function(blnID) {
    if (!blnID || (this.baselineUnavailable && this.baselineUnavailable[blnID] )) {
      return true;
    }
    return false;
  },


/**
* load baseline
*
* @param int blnID Baseline entity id
* @param function cb Callback function
* @param boolean refresh indicating to reload the edition
*/

  loadBaseline: function(blnID,cb,refresh) {
    DEBUG.traceEntry("dataMgr.loadBaseline","blnID = " + blnID);
    var dataMgr = this, dataQuery = "",
        i,temp;
    if (!this.textResourcesLoaded || dataMgr.loadingBaseline) {
      if (blnID == dataMgr.loadingBaseline) {
        DEBUG.log("warn"," call with same txtID to loadBaseline which is not re-entrant, cancelling this request to load blnID "+ blnID);
      } else if (!this.textResourcesLoaded || !this.baselineForIDUnavailable(blnID)){
        this.stackLoadBaseline(blnID,cb);
      }
      return;
    }
    if (blnID && (!this.isBaselineLoaded(blnID) || refresh) && !this.baselineForIDUnavailable(blnID)) {//test load again as call may have been on stack and another task may have loaded it
      dataQuery = {bln:blnID};
      dataMgr.loadingBaseline = blnID;
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: this.basepath+'/services/loadTextBaseline.php?db='+this.dbName,//caution dependency on context having basepath and dbName
          data: dataQuery,
          async: true,
          success: function (data, status, xhr) {
              DEBUG.traceEntry("dataMgr.loadBaseline.SuccessCB","blnID = " + blnID);
              var loadedBlnID;
              dataMgr.loadingBaseline = 0;
              if (typeof data == 'object' && data.entities && data.entities.update &&
                    data.entities.update.bln && Object.keys(data.entities.update.bln).length > 0) {
                dataMgr.updateLocalCache(data, null);
                //flag baseline as loaded
                loadedBlnID = Object.keys(data.entities.update.bln)[0];
                dataMgr.loadedBaseline[loadedBlnID] = 1;
              } else {//nothing found for blnID so mark it as not available
                dataMgr.baselineUnavailable[blnID] = 1;
              }
              if (data.warnings) {
                DEBUG.log("warn", "warnings during loadBaseline - " + data.warnings.join(" : "));
              }
              if (cb && typeof cb == "function") {
                setTimeout(cb,50);
              }
              dataMgr.processLoadBaselineStack();
              DEBUG.traceExit("dataMgr.loadBaseline.SuccessCB","blnID = " + blnID);
          },
          error: function (xhr,status,error) {
              // add record failed.
              dataMgr.loadingBaseline = 0;
              dataMgr.processLoadBaselineStack();
              alert("An error occurred while trying to retrieve text baseline data records. Error: " + error);
          }
      });
    } else if (cb && typeof cb == "function") {
      cb();
    }
    DEBUG.traceExit("dataMgr.loadBaseline","blnID = " + blnID);
  },


/**
* calculate linked annotations lookup
*/

  calcLinkedAnoLookups: function() {
    if (!this.entities.ano) {
      return;
    }
    this.entTag2LinkedAnoIDsByType = {};
    this.entTag2LinkedByAnoIDsByType = {};
    this.entTag2RelatedGIDsByLinkType = {};
    var i,j,k, linkFromIDs, anno, entTag, anoType;
    for (i in this.entities.ano){
      anno = this.entities.ano[i];
      anoType = anno.typeID;
      if (anno.linkedFromIDs && !anno.linkedToIDs && anoType){
        for (j in anno.linkedFromIDs) {
          entTag = anno.linkedFromIDs[j].replace(':','');
          if (!this.entTag2LinkedAnoIDsByType[entTag]) {
            this.entTag2LinkedAnoIDsByType[entTag] = {};
          }
          if (!this.entTag2LinkedAnoIDsByType[entTag][anoType]) {
            this.entTag2LinkedAnoIDsByType[entTag][anoType] = [anno.id];
          } else {
            this.entTag2LinkedAnoIDsByType[entTag][anoType].push(anno.id);
          }
        }
      }
      if (!anno.linkedFromIDs && anno.linkedToIDs && anoType){
        for (j in anno.linkedToIDs) {
          entTag = anno.linkedToIDs[j].replace(':','');
          if (!this.entTag2LinkedByAnoIDsByType[entTag]) {
            this.entTag2LinkedByAnoIDsByType[entTag] = {};
          }
          if (!this.entTag2LinkedByAnoIDsByType[entTag][anoType]) {
            this.entTag2LinkedByAnoIDsByType[entTag][anoType] = [anno.id];
          } else {
            this.entTag2LinkedByAnoIDsByType[entTag][anoType].push(anno.id);
          }
        }
      }
      if (anno.linkedFromIDs && anno.linkedToIDs && anoType){
        for (j in anno.linkedFromIDs) {
          entTag = anno.linkedFromIDs[j].replace(':','');
          if (!this.entTag2RelatedGIDsByLinkType[entTag]) {
            this.entTag2RelatedGIDsByLinkType[entTag] = {};
          }
          if (!this.entTag2RelatedGIDsByLinkType[entTag][anoType]) {
            this.entTag2RelatedGIDsByLinkType[entTag][anoType] = anno.linkedToIDs;
          } else {
            this.entTag2RelatedGIDsByLinkType[entTag][anoType].concat(anno.linkedToIDs);
          }
        }
      }
    }
  },


/**
* get linked annoations by type lookup for a given entity
*
* @param string entTag Entity tag
*
* @returns object LinkedAnoIDsByType lookup
*/

  getEntityAnoIDsByType: function(entTag) {
    if (entTag && this.entTag2LinkedAnoIDsByType && this.entTag2LinkedAnoIDsByType[entTag]){
      return this.entTag2LinkedAnoIDsByType[entTag];
    }
    return {};
  },


/**
* get linkedby annotations by type lookup for a given entity
*
* @param string entTag Entity tag
*
* @returns object LinkedByAnoIDsByType lookup
*/

  getEntityAnoTagIDsByType: function(entTag) {
    if (entTag && this.entTag2LinkedByAnoIDsByType && this.entTag2LinkedByAnoIDsByType[entTag]){
      return this.entTag2LinkedByAnoIDsByType[entTag];
    }
    return {};
  },


/**
* get related entity global ids by link type lookup for a given entity
*
* @param string entTag Entity tag
*
* @returns object RelatedGIDsByLinkType lookup
*/

  getEntityRelatedByLinkType: function(entTag) {
    if (entTag && this.entTag2RelatedGIDsByLinkType && this.entTag2RelatedGIDsByLinkType[entTag]){
      return this.entTag2RelatedGIDsByLinkType[entTag];
    }
    return {};
  },


/**
* load annotations
*
* @param function cb Callback function
*/

  loadAnnotations: function(cb) {
    DEBUG.traceEntry("dataMgr.loadAnnotations","");
    var dataMgr = this, dataQuery = "",
        i,temp;
    if (dataMgr.loadingAnnotations) {
      return;
    }
    if (true) {
      dataQuery = {};
      dataMgr.loadingAnnotations = true;
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: this.basepath+'/services/getAnnotations.php?db='+this.dbName,//caution dependency on context having basepath and dbName
          data: dataQuery,
          async: true,
          success: function (data, status, xhr) {
              DEBUG.traceEntry("dataMgr.loadAnnotations.SuccessCB","");
              dataMgr.loadingAnnotations = 0;
              dataMgr.annoLoaded = true;
              dataMgr.annoLoadFailed = false;
              if (typeof data == 'object' && data.entities && data.entities.update &&
                    Object.keys(data.entities.update).length > 0) {
                dataMgr.updateLocalCache(data, null);
              }
              if (data.warnings) {
                DEBUG.log("warn", "warnings during loadAnnotations - " + data.warnings.join(" : "));
              }
              if (layoutManager && layoutManager.notifyEditors) {
                layoutManager.notifyEditors('annotationsLoaded');//??check to see if need layoutMagr memeber of datamanager.
              }
              if (cb && typeof cb == "function") {
                setTimeout(function() { cb();
                                      },50);
              }
              dataMgr.calcLinkedAnoLookups();
              dataMgr.processLoadEditionStack();
              DEBUG.traceExit("dataMgr.loadAnnotations.SuccessCB","");
          },
          error: function (xhr,status,error) {
              // add record failed.
              dataMgr.loadingAnnotations = 0;
              dataMgr.annoLoadFailed = true;
              dataMgr.processLoadEditionStack();
              alert("An error occurred while trying to retrieve annotation data records. Error: " + error);
          }
      });
    } else if (cb && typeof cb == "function") {
      cb();
    }
    DEBUG.traceExit("dataMgr.loadAnnotations","");
  },


/**
* load user preferences
*
* @param function cb Callback function
*/

  loadUserPreferences: function(cb) {
    DEBUG.traceEntry("dataMgr.loadUserPreferences");
    var dataMgr = this;
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: this.basepath+'/services/getUserPreferences.php?db='+this.dbName,//caution dependency on context having basepath and dbName
        async: true,
        success: function (data, status, xhr) {
            var pref;
            DEBUG.traceEntry("dataMgr.getUserPreferences.SuccessCB");
            if (typeof data == 'object' && Object.keys(data).length > 0) {
              if (data.userDefPrefs && Object.keys(data.userDefPrefs) > 0){
                dataMgr.userPrefs = data.userDefPrefs;
              }
              if (data.prefUserUISets && Object.keys(data.prefUserUISets) > 0){
                dataMgr.prefUserUISets = data.prefUserUISets;
              }
            } else {
              DEBUG.log("warn", "no preferences loaded during loadUserPreferences ");
            }
            if (data.warnings) {
              DEBUG.log("warn", "warnings during loadUserPreferences - " + data.warnings.join(" : "));
            }
            if (cb && typeof cb == "function") {
              setTimeout(function() { cb();
                                    },50);
            }
            DEBUG.traceExit("dataMgr.getUserPreferences.SuccessCB");
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve user preferences. Error: " + error);
        }
    });
    DEBUG.traceExit("dataMgr.loadUserPreferences");
  },


/**
* load text resources
*
* @param function cb Callback function
*/

  loadTextResources: function(cb) {
    DEBUG.traceEntry("dataMgr.loadTextResource");
    var dataMgr = this, dataQuery = "",
        i,temp;
    if (dataMgr.loadingTextResources) {
      return;
    }
    if (!this.textResourcesLoaded) {
      dataQuery = {};
      dataMgr.loadingTextResources = true;
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: this.basepath+'/services/loadTextResources.php?db='+this.dbName,//caution dependency on context having basepath and dbName
          data: dataQuery,
          async: true,
          success: function (data, status, xhr) {
              DEBUG.traceEntry("dataMgr.loadTextResource.SuccessCB");
              dataMgr.loadingTextResources = 0;
              if (typeof data == 'object' && data.entities && data.entities.update &&
                    ((data.entities.update.bln && Object.keys(data.entities.update.bln).length > 0) ||
                    (data.entities.update.edn && Object.keys(data.entities.update.edn).length > 0))) {
                dataMgr.updateLocalCache(data, null);
                dataMgr.textResourcesLoaded = true;
              }
              if (data.warnings) {
                DEBUG.log("warn", "warnings during loadBaseline - " + data.warnings.join(" : "));
              }
              if (cb && typeof cb == "function") {
                setTimeout(function() { cb();
                                        dataMgr.processLoadBaselineStack();
                                        dataMgr.processLoadEditionStack();
                                        dataMgr.processLoadCatalogStack();
                                      },50);
              }
              DEBUG.traceExit("dataMgr.loadTextResource.SuccessCB");
          },
          error: function (xhr,status,error) {
              // add record failed.
              dataMgr.loadingBaseline = 0;
              alert("An error occurred while trying to retrieve text baseline data records. Error: " + error);
          }
      });
    } else if (cb && typeof cb == "function") {
      cb();
    }
    DEBUG.traceExit("dataMgr.loadTextResource");
  },


/**
* stack Load Catalog calls
*
* @param int catID Catalog entity id
* @param function cb Callback function
*/

  stackLoadCatalog: function(catID,cb) {
    if (!this.catStack) {
      this.catStack = {};
    }
    this.catStack[catID] = cb;
  },


/**
* process Catalog Load calls stack
*/

  processLoadCatalogStack: function() {
    var catID, cb;
    if (!this.loadingCatalog && this.catStack &&
        Object.keys(this.catStack).length) {
      for (catID in this.catStack) {
        cb = this.catStack[catID];
        delete this.catStack[catID];
        break; // only get the first one
      }
      if (catID) {
        this.loadCatalog(catID, cb);
      }
    }
  },


/**
* * test if a catalog is loaded
*
* @param int catID Catalog entity id
*
* @returns true|false
*/

  isCatalogLoaded: function(catID) {
    if (catID && !isNaN(catID) && this.loadedCatalog && this.loadedCatalog[catID] ) {
      return true;
    }
    return false;
  },


/**
* test availability of a catalog
*
* @param int catID Catalog entity id
*
* @returns true|false
*/

  catalogForIDUnavailable: function(catID) {
    if (!catID || (this.catalogUnavailable && this.catalogUnavailable[catID] )) {
      return true;
    }
    return false;
  },


/**
* load catalog
*
* @param int catID Catalog entity id
* @param function cb Callback function
*/

  loadCatalog: function(catID,cb) {
    DEBUG.traceEntry("dataMgr.loadCatalog","catID = " + catID);
    var dataMgr = this, dataQuery = "",
        i,temp,id,prefix;
    if (!this.textResourcesLoaded || dataMgr.loadingCatalog) {
      if (catID == dataMgr.loadingCatalog) {
        DEBUG.log("warn"," call with same catID to loadCatalog which is not re-entrant, cancelling this request to load catID "+ catID);
      } else if (!this.textResourcesLoaded || !this.catalogForIDUnavailable(catID)){
        this.stackLoadCatalog(catID,cb);
      }
      return;
    }
    if (catID && !this.isCatalogLoaded(catID) && !this.catalogForIDUnavailable(catID)) {//specific load
      dataQuery = {cat:catID};
      dataMgr.loadingCatalog = catID;
      $.ajax({
          type:"POST",
          dataType: 'json',
          url: this.basepath+'/services/loadCatalogEntities.php?db='+this.dbName,//caution dependency on context having basepath and dbName
          data: dataQuery,
          async: true,
          success: function (data, status, xhr) {
              DEBUG.traceEntry("dataMgr.loadCatalog.SuccessCB","catID = " + catID);
              var txtID, tagID, id;
              dataMgr.loadingCatalog = 0;
              if (typeof data == 'object' && data.entities && data.entities.update &&
                    data.entities.update.cat && Object.keys(data.entities.update.cat).length > 0
                    && Object.keys(data.entities.update.cat)[0] ) {
                if (!data.entities.insert) {
                  temp = data.entities;
                  delete data.entities;
                  data.entities = {};
                  data.entities['insert'] = temp;
                }
                dataMgr.updateLocalCache(data, null);
                if (data.entities.update && data.entities.update.cat && data.entities.update.cat[catID]) {
                  dataMgr.loadedCatalog[catID] = 1;
                }
              } else {//nothing found for catID so mark it as not available
                dataMgr.catalogUnavailable[catID] = 1;
              }
              if (cb && typeof cb == "function") {
                cb();
              }
              dataMgr.processLoadCatalogStack();
              DEBUG.traceExit("dataMgr.loadCatalog.SuccessCB","catID = " + catID);
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to retrieve text data records. Error: " + error);
              dataMgr.loadingCatalog = 0;
              dataMgr.processLoadCatalogStack();
          }
      });
    }
    DEBUG.traceExit("dataMgr.loadCatalog","catID = " + catID);
  },


/**
* load attributions
*
* @param function cb Callback function
*/

  loadAttributions: function(cb) {
    DEBUG.traceEntry("dataMgr.loadAttributions");
    var dataMgr = this, dataQuery = "",
        i,temp,id,prefix;
    $.ajax({
        type:"POST",
        dataType: 'json',
        url: this.basepath+'/services/loadAttributions.php?db='+this.dbName,//caution dependency on context having basepath and dbName
        async: true,
        success: function (data, status, xhr) {
          DEBUG.traceEntry("dataMgr.loadAttributions.SuccessCB");
          if (typeof data == 'object' && data.entities) {
            if (!data.entities.insert) {
              temp = data.entities;
              delete data.entities;
              data.entities = {};
              data.entities['insert'] = temp;
            }
            dataMgr.updateLocalCache(data, null);
          }
          if (cb && typeof cb == "function") {
            cb();
          }
          DEBUG.traceExit("dataMgr.loadAttributions.SuccessCB");
        },
        error: function (xhr,status,error) {
          // add record failed.
          alert("An error occurred while trying to retrieve attribution records. Error: " + error);
        }
    });
  DEBUG.traceExit("dataMgr.loadAttributions");
  },


/**
* load text search.
*
* @param string search Search text used in query
* @param function cb Callback function
*/

  loadTextSearch: function(search,cb) {
    DEBUG.traceEntry("dataMgr.loadTextSearch","search = " + search);
    var dataMgr = this,
        temp,textResults;
    dataQuery = search?"search="+search:"";
    $.ajax({
        dataType: 'json',
        url: this.basepath+'/services/loadTextSearchEntities.php?db='+this.dbName,
        data: dataQuery,
        async: true,
        success: function (data, status, xhr) {
            DEBUG.traceEntry("dataMgr.loadTextSearch.SuccessCB","search = " + search);
            if (typeof data == 'object'&& data.success &&
                data.entities && data.entities.insert && data.entities.insert.txt) {
              textResults = data.entities.insert.txt;
              if (!data.entities.insert) {
                temp = data.entities;
                delete data.entities;
                data.entities = {};
                data.entities['insert'] = temp;
              }
              dataMgr.updateLocalCache(data,null);
            }
            if (cb && typeof cb == "function") {
              cb(textResults);
            }
            DEBUG.traceExit("dataMgr.loadTextSearch.SuccessCB","search = " + search);
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve text search results. Error: " + error);
        }
    });
    DEBUG.traceExit("dataMgr.loadTextSearch","search = " + search);
  }

}

