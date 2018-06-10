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
* editors frameV object
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
* Constructor for Frame Viewer Object
*
* @type Object
*
* @param frameVCfg is a JSON object with the following possible properties
*  "url" a service url that is used to set the src of the frame element which displays the results of the service.
*
* @returns {EditionVE}

*/

EDITORS.FrameV =  function(frameVCfg) {
  var srchVE = this;
  //read configuration and set defaults
  this.config = frameVCfg;
  this.type = "FrameV";
  this.editDiv = frameVCfg['editDiv'] ? frameVCfg['editDiv']:null;
  this.dataMgr = frameVCfg['dataMgr'] ? frameVCfg['dataMgr']:null;
  this.dictionary = frameVCfg['dictionary'] ? frameVCfg['dictionary']:null;
  this.url = frameVCfg['url'] ? frameVCfg['url']:null;
  if (this.url && this.url.indexOf('?') > 0) {
    service = this.url.split('?');
    this.serviceUrl = service[0];
  }
  //todo write regexp to test url form
  this.catID = (frameVCfg['catID'] && this.dataMgr &&
                this.dataMgr.entities.cat[frameVCfg['catID']])?frameVCfg['catID']:null;
  this.ednID = (frameVCfg['ednID'] && this.dataMgr &&
                  this.dataMgr.entities.edn[frameVCfg['ednID']]) ? frameVCfg['ednID']:null;
  this.layoutMgr = frameVCfg['layoutMgr'] ? frameVCfg['layoutMgr']:null;
  this.eventMgr = frameVCfg['eventMgr'] ? frameVCfg['eventMgr']:null;
  this.id = frameVCfg['id'] ? frameVCfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.init();
  return this;
};
/**
* put your comment there...
*
* @type Object
*/

EDITORS.FrameV.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    var frameV = this;
    this.frameElem = $('<iframe id="'+this.id+'Frame" class = "iFrameViewer" spellcheck="false"/>');
    $(this.editDiv).append(this.frameElem);
    this.createStaticToolbar();
    if (this.url) {
      this.frameElem.attr("src",this.url);
    } else {
      this.frameElem.html("<html><body>No URL supplied</body></html>");
    }
  },


/**
* put your comment there...
*
*/

  createStaticToolbar: function () {
    var frameV = this;
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');
    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
    this.addEventHandlers();
  },


/**
*
*/

  addEventHandlers: function() {
    var frameV = this, entities = this.dataMgr.entities;


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
* @param entID
*/

    function updateSelectionHandler(e,senderID, selectionIDs, entID) {
      if (senderID == frameV.id || !frameV.dictionary) {
        return;
      }
      var i, id, prefix, url;
      DEBUG.log("event","selection changed recieved by "+frameV.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      if (entID && entID.length && entID.length > 3) {
        prefix = entID.substr(0,3);
        id = entID.substr(3);
        if (prefix == 'tok' || prefix == 'cmp' && frameV.dictionary ) {
          //reset src with value of word
          if (frameV.serviceURL) {
            url = frameV.serviceURL;
          } else {
//            url = basepath+'/plugins/azesdictionary/m_dictionary.php';
//            url = 'https://gandhari.org/beta/plugins/azesdictionary/m_dictionary.php';
            url = 'https://gandhari.org/beta/plugins/azesdictionary/m_dictionary.php';
            url = basepath+'/plugins/dictionary/index.php';
          }
          entity = frameV.dataMgr.getEntity(prefix,id);
          url += '?dictionary='+frameV.dictionary+'&searchstring='+entity.value.replace(/ʔ/g,'')+'&searchtype=F&strJSON={"dictionary":"'+frameV.dictionary+'","mode":"F:'+entity.value.replace(/ʔ/g,'')+'"}';

          frameV.frameElem.attr("src",url);
        }
      }
    };

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);
  }
}

