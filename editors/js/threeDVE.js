var EDITORS = EDITORS || {};

/**
 * Constructor for 3D VE Object
 *
 * @type Object
 *
 * @param config is a JSON object with the following possible properties
 *
 * @returns {threeDVE}
 */

EDITORS.threeDVE = function (config) {
  var tdVE = this;
  //read configuration and set defaults
  this.config = config;
  this.type = "3DVE";
  this.editDiv = config['editDiv'] ? config['editDiv'] : null;
  this.dataMgr = config['dataMgr'] ? config['dataMgr'] : null;
  this.edition = config['edition'] ? config['edition'] : null;
  this.ednID = (config['ednID'] && this.dataMgr &&
      this.dataMgr.entities.edn[config['ednID']]) ? config['ednID'] : null;
  this.layoutMgr = config['layoutMgr'] ? config['layoutMgr'] : null;
  this.id = config['id'] ? config['id'] : (this.editDiv.id ? this.editDiv.id : null);
  this.api = null;
  this.annotationData = [];
  this.init();
  return this;
};

/**
 * Prototype for 3D VE Object
 */
EDITORS.threeDVE.prototype = {

  /**
   * Initiate the VE object.
   */
  init: function () {
    var tdVE = this;
    var frameWidth = $(this.editDiv).width();
    var frameHeight = $(this.editDiv).height();
    var frameElement = $('<iframe src="" id="tdvFrame" allow="autoplay; fullscreen; vr" ' +
        'allowvr allowfullscreen mozallowfullscreen="true" webkitallowfullscreen="true">' +
        '</iframe>');
    $(this.editDiv).append(frameElement);

    // Initiate the viewer.
    var iframe = document.getElementById('tdvFrame');
    iframe.width = frameWidth;
    iframe.height = frameHeight;
    var uid = this.getModelUID();
    if (uid !== null) {
      var client = new Sketchfab(iframe);

      this.annotationData = this.getAnnotationData();

      client.init(uid, {
        annotation_visible: 0,
        success: function onSuccess(api) {
          tdVE.api = api;
          tdVE.api.start();
          tdVE.api.addEventListener('viewerready', function () {
            var i;
            if (tdVE.annotationData.length > 0) {
              for (i = 0; i < tdVE.annotationData.length; i++) {
                // Closure to pass the delta in the callback function.
                (function (delta) {
                  tdVE.api.createAnnotationFromWorldPosition(
                      tdVE.annotationData[i].coords,
                      tdVE.annotationData[i].cameraPosition,
                      tdVE.annotationData[i].cameraTarget,
                      tdVE.annotationData[i].title,
                      tdVE.annotationData[i].text,
                      function(err, index) {
                        if(!err) {
                          tdVE.annotationData[delta].index = index;
                        }
                      }
                  );
                })(i);
              }

              tdVE.api.addEventListener('annotationFocus', function(index) {
                var segID = tdVE.findSegIDByAnnotationIndex(index);
                if (segID) {
                  $('.editContainer').trigger('updateselection',[tdVE.id, ['seg' + segID]]);
                }
              });

            }
            tdVE.addEventHandlers();
          });
        },
        error: function onError() {
          alert('Failed to load the 3D viewer');
        }
      });
    } else {
      alert('There is no 3D model available for this edition');
    }
  },

  /**
   * Get the Sketchfab model UID based on the edition.
   *
   * @return {string}
   */
  getModelUID: function () {
    if (
      this.edition !== null &&
      typeof this.dataMgr.tdViewerData !== 'undefined' &&
      typeof this.dataMgr.tdViewerData.models !== 'undefined' &&
      this.dataMgr.tdViewerData.models.hasOwnProperty(this.edition.txtID)
    ) {
      return this.dataMgr.tdViewerData.models[this.edition.txtID].modelUID;
    }
    return null;
  },

  /**
   * Get the data to create the Sketchfab annotations.
   *
   * @return {Array}
   */
  getAnnotationData: function () {
    var anoData = [];
    var i, j;
    if (
        typeof this.dataMgr.tdViewerData !== 'undefined' &&
        typeof this.dataMgr.tdViewerData.annotations !== 'undefined' &&
        typeof this.dataMgr.tdViewerData.annotations.syllables !== 'undefined'
    ) {
      var sclIDs = this.getEditionSyllableIDs();
      var sclData = this.dataMgr.tdViewerData.annotations.syllables;
      if (sclIDs.length > 0) {
        for (i = 0; i < sclIDs.length; i++) {
          if (sclData.hasOwnProperty(sclIDs[i])) {
            for (j = 0; j < sclData[sclIDs[i]].annotations.length; j++) {
              anoData.push({
                title: sclData[sclIDs[i]].sclTrans,
                text: sclData[sclIDs[i]].sclTrans,
                coords: sclData[sclIDs[i]].annotations[j].coords.split(','),
                cameraPosition: sclData[sclIDs[i]].annotations[j].cameraPosition.split(','),
                cameraTarget: sclData[sclIDs[i]].annotations[j].cameraTarget.split(','),
                segID: sclData[sclIDs[i]].segID,
                sclID: sclData[sclIDs[i]].sclID
              });
            }
          }
        }
      }
    }
    return anoData;
  },

  /**
   * Get the syllable IDs of the edition.
   *
   * @return {Array}
   */
  getEditionSyllableIDs: function () {
    var sclIDs = [];
    var i, j;
    if (this.edition !== null) {
      var ednSeqIDs = this.edition.seqIDs;
      if (ednSeqIDs) {
        var ednSclSeq = null;
        for (i = 0; i < ednSeqIDs.length; i++) {
          if (this.dataMgr.entities.seq[ednSeqIDs[i]].typeID === '736') {
            ednSclSeq = this.dataMgr.entities.seq[ednSeqIDs[i]];
            break;
          }
        }
        if (ednSclSeq) {
          var subSeqIDs = ednSclSeq.entityIDs;
          if (subSeqIDs) {
            for (i = 0; i < subSeqIDs.length; i++) {
              var subSeqParsedID = this.parseGID(subSeqIDs[i]);
              if (subSeqParsedID.prefix === 'seq') {
                var subSeq = this.dataMgr.entities.seq[subSeqParsedID.id];
                if (subSeq.entityIDs) {
                  for (j = 0; j < subSeq.entityIDs.length; j++) {
                    var parsedEntityID = this.parseGID(subSeq.entityIDs[j]);
                    if (parsedEntityID.prefix === 'scl') {
                      sclIDs.push(parsedEntityID.id);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return sclIDs;
  },

  /**
   * Find the annotation index by the segment ID.
   *
   * @param {string} segID
   * @return {int}
   */
  findAnnotationIndexBySegID: function (segID) {
    var i;
    for (i = 0; i < this.annotationData.length; i++) {
      if (this.annotationData[i].segID === segID) {
        return this.annotationData[i].index;
      }
    }
    return null;
  },

  /**
   * Find the segment ID by the annotation index.
   *
   * @param {int} index
   * @return {string}
   */
  findSegIDByAnnotationIndex: function (index) {
    var i;
    for (i = 0; i < this.annotationData.length; i++) {
      if (this.annotationData[i].index === index) {
        return this.annotationData[i].segID;
      }
    }
    return null;
  },

  /**
   * Parse the READ GID.
   *
   * @param {string} gid
   * @return {{prefix: string, id: string}}
   */
  parseGID: function (gid) {
    var match = /^([a-z]*):?([0-9]+)$/g.exec(gid);
    return {
      prefix: match[1],
      id: match[2]
    };
  },

  /**
   * Bind VE events.
   */
  addEventHandlers: function() {
    var tdVE = this;

    /**
     * Event handler when there's an update selection from other VE.
     *
     * @param {object} e
     * @param {string} senderID
     * @param {array} selectionGIDs
     */
    function updateSelectionHandler(e,senderID, selectionGIDs) {
      if (senderID == tdVE.id) {
        return;
      }
      var i, id;
      DEBUG.log("event","selection changed recieved by 3DVE in " + tdVE.id + " from "+senderID+" selected ids "+ selectionGIDs.join());
      if (selectionGIDs.length > 0) {
        var segID = null;
        for (i = 0; i < selectionGIDs.length; i++) {
          if (selectionGIDs[i]) {
            var parsedGID = tdVE.parseGID(selectionGIDs[i]);
            if (parsedGID.prefix === 'seg') {
              segID = parsedGID.id;
              break;
            }
          }
        }
        if (segID) {
          var annoIndex = tdVE.findAnnotationIndexBySegID(segID);
          if (annoIndex) {
            tdVE.api.gotoAnnotation(annoIndex, {preventCameraAnimation: false, preventCameraMove: false});
          }
        }
      }
    }

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);
  }
};
