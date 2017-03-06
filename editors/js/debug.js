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
* DEBUG functions
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Debug
*/
var DEBUG = DEBUG || {};

  DEBUG.dataOn = false;//for dataManager data info
  DEBUG.navOn = false;//for UI navigation info
  DEBUG.warnOn = true;
  DEBUG.errOn = true;
  DEBUG.eventOn = false;
  DEBUG.genOn = false;
  DEBUG.healthLogOn = true;
  DEBUG.levelOverrides = [];
  DEBUG.outputChildIDs = true;
  DEBUG.traceOn = false;
  DEBUG.level = 0;
  DEBUG.addTimings = false;
  DEBUG.traceLevel = 10;
  DEBUG.logtxt = "";
  DEBUG.start = (new Date()).getTime();

/**
* log message to console
*
* @param string cat indicates the category of output for filtering debug info
* @param string info to output
*/

  DEBUG.log = function(cat, info) {
    var i, tab = "\t", indent="",
      elapse = (new Date()).getTime() - DEBUG.start;
    if (info && info.length) {
      if (DEBUG.addTimings) {
        info = info + " time = " + elapse +" ms";
      }
      switch (cat) {
        case "data":
          if (DEBUG.dataOn){
            console.log(info);// define DEBUG.output as central function for redirected logging.
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "nav":
          if (DEBUG.navOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "gen":
          if (DEBUG.genOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "warn":
          if (DEBUG.warnOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "trace":
          if (DEBUG.traceOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "health":
          if (DEBUG.healthLogOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "err":
          if (DEBUG.errOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
        case "event":
          if (DEBUG.eventOn){
            console.log(info);
            DEBUG.logtxt += "\n" + info;
          }
          break;
      }
    }
  };

/**
* traceEntry outputs an indented message to console as entry point
*   should have a matching traceExit call
* @param string codePointName names the point in the code (typically a function name.
* @param string msg to output
*/

  DEBUG.traceEntry = function(codePointName, msg) {
    DEBUG.level++;
    DEBUG.trace(codePointName+" Entry",msg);
  }

/**
* traceExit outputs an indented message to console as exit point
*   should have a matching traceEntry call
* @param string codePointName names the point in the code (typically a function name.
* @param string msg to output
*/

  DEBUG.traceExit = function(codePointName, msg) {
    DEBUG.trace(codePointName+" Exit",msg);
    DEBUG.level--;
  }

/**
* trace output an indented message to console
*
* @param int level indicates the nesting level to enable trace ouput and for indenting output
* @param string msg to output
*/

  DEBUG.trace = function(codePointName, msg, levelAdjust) {
    var i, tab = " ", indent="",
        overrideLevel = (DEBUG.levelOverrides.indexOf(codePointName) >-1);
    if (typeof DEBUG.level == "undefined") {
      DEBUG.level = 0;
    }
    if (!isNaN(levelAdjust)) {
      DEBUG.level += levelAdjust;
    }
    if (overrideLevel || DEBUG.level <= DEBUG.traceLevel) {//within trace level so output
      for(i=0;i<DEBUG.level;i++) {
        indent += tab;
      }
      DEBUG.log('trace',indent+"TRACE-"+codePointName+": "+msg);
    }
  };

/**
* dump Sequence data
*
* @param int seqID foreign key of the sequence to output
* @param int level indicates the nesting level for indenting output
* @param object graLK lookup table passed to compound and token code for finding boundaries
* @param string msg tp prepend to formatted output
* @returns string of formatted data describing the sequence entity
*/

  DEBUG.dumpSeqData = function(seqID, level, depth, graLK, msg) {
    var entities = dataManager ? dataManager.entities : (window.entities ? window.entities: null),
        termIDtoLabel = (dataManager && dataManager.termInfo) ?
                            dataManager.termInfo.labelByID :
                              (window.trmIDtoLabel) ? window.trmIDtoLabel: null,
        dumpTxt = msg?msg+" ":"", gid, prefix, id, entity = entities.seq[seqID],i, tab = "\t";
    if (level) {
      for(i=0;i<level;i++) {
        dumpTxt += tab;
      }
    }
    if (entity) {
      // output entity info
      dumpTxt += "seq:"+seqID+entity.value+
                ((entity.typeID && trmIDtoLabel)? " ("+trmIDtoLabel[entity.typeID]+")":"")+"\n";
      depth--;
      //output contained entities
      if (depth && entity.entityIDs && entity.entityIDs.length) {
        for(i=0; i< entity.entityIDs.length; i++) {
          gid = entity.entityIDs[i];
          prefix = gid.substring(0,3);
          id = gid.substring(4);
          switch (prefix) {
            case "seq":
              dumpTxt += DEBUG.dumpSeqData(id,1+level, depth, graLK);
              break;
            case "cmp":
              dumpTxt += DEBUG.dumpCmpData(id,1+level, depth, graLK);
              break;
            case "tok":
              dumpTxt += DEBUG.dumpTokData(id,1+level, depth, graLK);
              break;
            default:
              dumpTxt += gid;
          }
        }
      } else if (DEBUG.outputChildIDs && entity.entityIDs && entity.entityIDs.length) {
        dumpTxt += tab + "contains "+entity.entityIDs.join(',')+"\n";
      }
    }
    return dumpTxt+"\n";
  };

/**
* dump Compound data
*
* @param int cmpID foreign key of the compound to output
* @param int level indicates the nesting level for indenting output
* @param object graLK lookup table passed to token code for finding boundaries
* @param string msg tp prepend to formatted output
* @returns string of formatted data describing the compound entity
*/

  DEBUG.dumpCmpData = function(cmpID, level, depth, graLK, msg) {
    var entities = dataManager ? dataManager.entities : (window.entities ? window.entities: null),
        termIDtoLabel = (dataManager && dataManager.termInfo) ?
                            dataManager.termInfo.labelByID :
                              (window.trmIDtoLabel) ? window.trmIDtoLabel: null,
        dumpTxt = msg?msg+" ":"", gid, prefix, id, entity = entities.cmp[cmpID],i, tab = "\t";
    if (level) {
      for(i=0;i<level;i++) {
        dumpTxt += tab;
      }
    }
    if (entity) {
      // output entity info
      dumpTxt += "cmp:"+cmpID+entity.value+
                ((entity.typeID &&trmIDtoLabel)? trmIDtoLabel[entity.typeID]:"")+"\n";
      depth--;
      //output contained entities
      if (depth && entity.entityIDs && entity.entityIDs.length) {
        for(i=0; i< entity.entityIDs.length; i++) {
          gid = entity.entityIDs[i];
          prefix = gid.substring(0,3);
          id = gid.substring(4);
          switch (prefix) {
            case "cmp":
              dumpTxt += DEBUG.dumpCmpData(id,1+level, depth, graLK);
              break;
            case "tok":
              dumpTxt += DEBUG.dumpTokData(id,1+level, depth, graLK);
              break;
            default:
              dumpTxt += gid;
          }
        }
      } else if (DEBUG.outputChildIDs && entity.entityIDs && entity.entityIDs.length) {
        dumpTxt += tab + "contains "+entity.entityIDs.join(',')+"\n";
      }
    }
    return dumpTxt;
  };

/**
* dump Token data
*
* @param int tokID foreign key of the token to output
* @param int level indicates the nesting level for indenting output
* @param object graLK lookup table used for finding boundaries
* @param string msg tp prepend to formatted output
* @returns string of formatted data describing the token entity
*/

  DEBUG.dumpTokData = function(tokID, level, depth, graLK, msg) {
    var entities = dataManager ? dataManager.entities : (window.entities ? window.entities: null),
        termIDtoLabel = (dataManager && dataManager.termInfo) ?
                            dataManager.termInfo.labelByID :
                              (window.trmIDtoLabel) ? window.trmIDtoLabel: null,
        dumpTxt = msg?msg+" ":"", gid, prefix, id, lkGra, indent = "", entity = entities.tok[tokID],i, tab = "\t";
    if (level) {
      for(i=0;i<level;i++) {
        indent += tab;
      }
    }
    // output entity info
    dumpTxt += indent+"tok:"+tokID+" "+entity.value+" "+
              ((entity.typeID &&trmIDtoLabel)? trmIDtoLabel[entity.typeID]:"")+ "\n" + indent + tab +
              " gra("+entity.graphemeIDs.join()+")\n";
    //output contained entities
    if (graLK) {
      for(i=0; i< entity.graphemeIDs.length; i++) {
        lkGra = graLK[entity.graphemeIDs[i]];
        dumpTxt += indent + tab + "gra:" + entity.graphemeIDs[i] +" "+
                   (lkGra ? (lkGra.tokctx + (lkGra.boundary?"\n boundary: "+ lkGra.boundary:"")):"")+"\n";
      }
    }
    return dumpTxt;
  };


