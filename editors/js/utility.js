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
* UTILITY functions
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Utility
*/
var UTILITY = UTILITY || {};

/**
* calculate bound rect for array of points
*
* @param int array $points of the form [x1,y1,x2,y2,...,xn,yn] or [[x1,y1],[x2,y2],...,[xn,yn]]
* @return NULL|array of int points starting from upper left to right to lower right to left
*/
  UTILITY.getBoundingRect = function (points) {
    var cnt = points?points.length:0, // find number of points
        i;
    if(!cnt) return null;
    var x1 = y1 = 10000000;//upper left
    var x2 = y2 = 0; // lower
    if ( points[0].constructor.name == "Array" && points[0].length === 2) {//tuples
      for(i=0;i<cnt;i++){
        x1 = Math.min(x1,points[i][0]);
        x2 = Math.max(x2,points[i][0]);
        y1 = Math.min(y1,points[i][1]);
        y2 = Math.max(y2,points[i][1]);
      }
      return [[x1,y1],[x2,y1],[x2,y2],[x1,y2]];
    }else{
      for(i=0;i<cnt;){
        x1 = Math.min(x1,points[i]);
        x2 = Math.max(x2,points[i]);
        y1 = Math.min(y1,points[i+1]);
        y2 = Math.max(y2,points[i+1]);
        i +=2;
      }
      return [x1,y1,x2,y1,x2,y2,x1,y2];
    }
  };

/**
* calculate centroid for array of points
*
* @param int array $points of the form [x1,y1,x2,y2,...,xn,yn] or [[x1,y1],[x2,y2],...,[xn,yn]]
* @return NULL|int point indicating the centroid of the points
*/
  UTILITY.getCentroid = function (points) {
    var cnt = points?points.length:0, // find number of points
        i;
    if(!cnt) return null;
    var x = y = 0; //center
    if ( points[0].constructor.name == "Array" && points[0].length === 2) {//tuples
      for(i=0;i<cnt;i++){
        x += points[i][0];
        y += points[i][1];
      }
    } else {
      for(i=0;i<cnt;i++){
        x += points[i];
        y += points[i+1];
      }
    }
    return [Math.round(x/cnt),Math.round(y/cnt)];
  };


/**
* put your comment there...
*
* @param points
* @param dX
* @param dY
* @param {Number} scaleFactor
*
* @returns {Array}
*/

  UTILITY.getTranslatedPoly = function (points, dX, dY, scaleFactor) {
    var poly = [];
    var i;
    if (!scaleFactor) {
      for(i=0;i<points.length;i++){
        poly.push([(points[i][0]+dX), (points[i][1]+dY)]);
      }
      return poly;
    } else {
      for(i=0;i<points.length;i++){
        poly.push([Math.round((points[i][0]+dX)*scaleFactor), Math.round((points[i][1]+dY)*scaleFactor)]);
      }
      return poly;
    }
  };


/**
* put your comment there...
*
* @param entA
* @param entB
*
* @returns {Object}
*/

  UTILITY.compareEntities = function (entA,entB) {
    if (typeof entA == "object" && typeof entB == "object") {
      if (entA.sort && !isNaN(entA.sort) && entB.sort && !isNaN(entB.sort)) {
        if (entA.sort < entB.sort) {
          return -1;
        } else if (entA.sort > entB.sort) {
          return 1;
        } else if (entA.sort2 && !isNaN(entA.sort2) && entB.sort2 && !isNaN(entB.sort2)) {//handle primary sort equal cases
          if (entA.sort2 < entB.sort2) {
            return -1;
          } else if (entA.sort2 > entB.sort2) {
            return 1;
          } else if (entA.order && !isNaN(entA.order) && entB.order && !isNaN(entB.order)) {//handle homograms
          if (entA.order < entB.order) {
            return -1;
          } else if (entA.order > entB.order) {
            return 1;
          }
        }
        }else if (entA.sort2 && !isNaN(entA.sort2) && !entB.sort2) {//A has secondary B does not
          return 1;
        }else if (!entA.sort2 && entB.sort2 && !isNaN(entB.sort2)) {//B has secondary A does not
          return -1;
        }
        return 0;//catch all sub case and return equals
      }
    }
    return false;//if A or B is not an object or doesn't have primary sort
  };

/**
* parse a string assuming a URI type query string
*   ?param=val&param2=val2
* parses window.location.search if no parameter string passed in and
* returns an object of { param:val, param2:val2}
* @param paramString
*
* @returns object contain all parameter name:value pairs
*/

  UTILITY.parseParams = function(paramString) {
    var i, nvPair, nvPairs, params = {};
    if (!paramString) {
        paramString = window.location.search;
    }
    if (paramString.charAt(0) == '?') {//remove ?
        paramString = paramString.substring(1);
    }
    nvPairs = paramString.split('&');
    for ( i=0; i < nvPairs.length; ++i) {
        nvPair = nvPairs[i].split('=');
        nvPair[0] = decodeURIComponent(nvPair[0]);
        if (nvPair[1]) {
            params[nvPair[0]] = decodeURIComponent(nvPair[1]);
        } else {
            params[nvPair[0]] = null;
        }
    }
    return params;
  };

  UTILITY.audioCtx = new (window.AudioContext || window.webkitAudioContext || window.audioContext);


/**
* put your comment there...
*
* @param typ
* @param dur
* @param freq
* @param vol
* @param cb
*/

  UTILITY.beep = function (typ, dur, freq, vol,  cb) {
    var audioCtx, objGain, osc;
    if (!UTILITY.audioCtx) {
      UTILITY.audioCtx = new (window.AudioContext || window.webkitAudioContext || window.audioContext);
    }
    audioCtx = UTILITY.audioCtx;
    objGain = audioCtx.createGain();
    osc = audioCtx.createOscillator();
    osc.connect(objGain);
    objGain.connect(audioCtx.destination);
    dur = dur?dur:50;
    objGain.gain.value = vol?vol:0.3;
    osc.frequency.value = freq?freq:800;
    osc.type = typ?typ:'sine';
    if (cb){
      osc.onended = cb;
    }
    osc.start();
    setTimeout(function(){osc.stop()},dur);
};
