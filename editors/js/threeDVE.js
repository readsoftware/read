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
    var frameElement = $('<div class="iframe-wrapper"><div class="iframe-overlay"></div><iframe src="" id="tdvFrame" allow="autoplay; fullscreen; vr" ' +
        'allowvr allowfullscreen mozallowfullscreen="true" webkitallowfullscreen="true">' +
        '</iframe></div>');
    $(this.editDiv).append(frameElement);

    // Initiate the viewer.
    $(this.editDiv).find('.iframe-wrapper').width(frameWidth);
    $(this.editDiv).find('.iframe-wrapper').height(frameHeight);
    $(this.editDiv).find('.iframe-wrapper').css({
      "display": "inline-block",
      "position": "relative"
    });
    $(this.editDiv).find('.iframe-overlay').css({
      "position": "absolute",
      "z-index": "1",
      "width": "100%",
      "height": "100%",
      "left": "0",
      "top": "0"
    });
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

          tdVE.api.addEventListener('viewerready', function () {
            var i;
            if (tdVE.annotationData.syllable.length > 0 || tdVE.annotationData.token.length > 0 || tdVE.annotationData.compound.length > 0) {
              tdVE.createAnnotations();
              if (tdVE.id === tdVE.layoutMgr.focusPaneID) {
                tdVE.showAllCurrentAnnotations();
              }
              tdVE.api.addEventListener('annotationFocus', function(index) {
                var segIDs = tdVE.findSegIDByAnnotationIndex(index);
                if (segIDs) {
                  $('.editContainer').trigger('updateselection',[tdVE.id, segIDs]);
                }
              });

            }
            tdVE.addEventHandlers();
          });
          tdVE.api.start();
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
   * Create all annotations in the 3D model.
   */
  createAnnotations: function () {
    if (this.annotationData.syllable.length > 0) {
      this.createModelAnnotation('syllable', 0, true);
    }
  },

  /**
   * Create a single annotation in the 3D model.
   *
   * This method is used to iterate the annotation data to create the
   * annotations. Once one annotation is created, it will continue to create
   * the next until all the annotation data are consumed.
   *
   * @param {string} objLevel The annotation object level, which could be
   *   'syllable', 'token' or 'compound'.
   * @param {int} anoDataIndex The index of the annotation data item.
   * @param {boolean} hidden Hide/Show the created annotation
   */
  createModelAnnotation: function (objLevel, anoDataIndex, hidden) {
    var tdVE = this;
    this.api.createAnnotationFromWorldPosition(
        this.annotationData[objLevel][anoDataIndex].coords,
        this.annotationData[objLevel][anoDataIndex].cameraPosition,
        this.annotationData[objLevel][anoDataIndex].cameraTarget,
        this.annotationData[objLevel][anoDataIndex].title,
        this.annotationData[objLevel][anoDataIndex].text,
        function(err, index) {
          if(!err) {
            tdVE.annotationData[objLevel][anoDataIndex].index = index;
            if (hidden) {
              tdVE.api.hideAnnotation(index);
            }
            if (anoDataIndex + 1 < tdVE.annotationData[objLevel].length) {
              tdVE.createModelAnnotation(objLevel, anoDataIndex + 1, hidden);
            } else {
              if (objLevel === 'syllable' && tdVE.annotationData.token.length > 0) {
                tdVE.createModelAnnotation('token', 0, true);
              } else if (objLevel === 'token' && tdVE.annotationData.compound.length > 0) {
                tdVE.createModelAnnotation('compound', 0, true);
              }
            }
          }
        }
    );
  },

  /**
   * Refresh the 3D model annotations based on the current object level.
   *
   * This will hide/show according annotations based on the current object
   * level.
   */
  refreshAnnotations: function () {
    var i;
    var objLevel = this.layoutMgr.getEditionObjectLevel();
    switch (objLevel) {
      case 'token':
        for (i = 0; i < this.annotationData.token.length; i++) {
          this.api.showAnnotation(this.annotationData.token[i].index);
        }
        for (i = 0; i < this.annotationData.compound.length; i++) {
          this.api.hideAnnotation(this.annotationData.compound[i].index);
        }
        for (i = 0; i < this.annotationData.syllable.length; i++) {
          this.api.hideAnnotation(this.annotationData.syllable[i].index);
        }
        break;
      case 'compound':
        for (i = 0; i < this.annotationData.compound.length; i++) {
          this.api.showAnnotation(this.annotationData.compound[i].index);
        }
        for (i = 0; i < this.annotationData.token.length; i++) {
          this.api.hideAnnotation(this.annotationData.token[i].index);
        }
        for (i = 0; i < this.annotationData.syllable.length; i++) {
          this.api.hideAnnotation(this.annotationData.syllable[i].index);
        }
        break;
      default:
        for (i = 0; i < this.annotationData.syllable.length; i++) {
          this.api.showAnnotation(this.annotationData.syllable[i].index);
        }
        for (i = 0; i < this.annotationData.token.length; i++) {
          this.api.hideAnnotation(this.annotationData.token[i].index);
        }
        for (i = 0; i < this.annotationData.compound.length; i++) {
          this.api.hideAnnotation(this.annotationData.compound[i].index);
        }
    }
  },

  /**
   * Hide all annotations in the 3D model.
   */
  hideAllAnnotations: function () {
    var objLevel;
    var i;
    for (objLevel in this.annotationData) {
      if (this.annotationData.hasOwnProperty(objLevel)) {
        for (i = 0; i < this.annotationData[objLevel].length; i++) {
          if (typeof this.annotationData[objLevel][i].index !== 'undefined') {
            this.api.hideAnnotation(this.annotationData[objLevel][i].index);
          }
        }
      }
    }
  },

  /**
   * Hide all annotations displayed based on the current object level.
   */
  hideAllCurrentAnnotations: function () {
    var objLevel = this.layoutMgr.getEditionObjectLevel();
    var i;
    if (this.annotationData[objLevel]) {
      for (i = 0; i < this.annotationData[objLevel].length; i++) {
        if (typeof this.annotationData[objLevel][i].index !== 'undefined') {
          this.api.hideAnnotation(this.annotationData[objLevel][i].index);
        }
      }
    }
  },

  /**
   * Display all annotations based on the current object level.
   */
  showAllCurrentAnnotations: function () {
    var objLevel = this.layoutMgr.getEditionObjectLevel();
    var i;
    if (this.annotationData[objLevel]) {
      for (i = 0; i < this.annotationData[objLevel].length; i++) {
        if (typeof this.annotationData[objLevel][i].index !== 'undefined') {
          this.api.showAnnotation(this.annotationData[objLevel][i].index);
        }
      }
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
   * @return {Object} The annotation data is an object which has the properties
   *   of different object levels: 'syllable', 'token', and 'compound'.
   *
   *   Under each property, there is an array which contains the list of
   *   annotation data for that object level.
   *
   *   Each annotation data item is an object which has the following
   *     properties:
   *
   *   - title: (string) The annotation tooltip title.
   *   - text: (string) The annotation tooltip content.
   *   - coords: (array) The annotation position.
   *   - cameraPosition: (array) The annotation camera position.
   *   - cameraTarget: (array) The annotation camera target.
   *   - segIDs: (array) The segment IDs associated with the annotation. Each
   *     ID
   *     is in the format of 'segXX'.
   *   - entityID: (string) The entity GID associated with the annotation.
   */
  getAnnotationData: function () {
    var objLevel = this.layoutMgr.getEditionObjectLevel();
    var anoData = {};
    var i, j;

    if (
        typeof this.dataMgr.tdViewerData !== 'undefined' &&
        typeof this.dataMgr.tdViewerData.annotations !== 'undefined' &&
        typeof this.dataMgr.tdViewerData.annotations.syllables !== 'undefined'
    ) {
      var sclData = this.dataMgr.tdViewerData.annotations.syllables;
      var sclIDs;
      var tokIDs;
      var parsedTokID;
      var tokAnoSclID;
      var anoSegIDs;
      var anoTitle;
      var anoDescription;
      var transText;
      var chayaText;

      // Syllable.
      anoData.syllable = [];
      sclIDs = this.getEditionSyllableIDs();
      if (sclIDs.length > 0) {
        for (i = 0; i < sclIDs.length; i++) {
          var sclParsedID = this.parseGID(sclIDs[i]);
          if (sclData.hasOwnProperty(sclParsedID.id)) {
            anoTitle = sclData[sclParsedID.id].sclTrans.replaceAll("ʔ", "");
            anoDescription = '';
            for (j = 0; j < sclData[sclParsedID.id].annotations.length; j++) {
              anoData.syllable.push({
                title: anoTitle,
                text: anoDescription,
                coords: sclData[sclParsedID.id].annotations[j].coords.split(','),
                cameraPosition: sclData[sclParsedID.id].annotations[j].cameraPosition.split(','),
                cameraTarget: sclData[sclParsedID.id].annotations[j].cameraTarget.split(','),
                segIDs: ['seg' + sclData[sclParsedID.id].segID],
                entityID: 'scl:' + sclIDs[i]
              });
            }
          }
        }
      }

      // Token.
      anoData.token = [];
      tokIDs = this.getEditionTokenIDs(true);
      for (i = 0; i < tokIDs.length; i++) {
        parsedTokID = this.parseGID(tokIDs[i]);
        sclIDs = this.getTokenSyllableIDs(parsedTokID.id);
        tokAnoSclID = this.findArrayMidItem(sclIDs);
        if (sclData.hasOwnProperty(tokAnoSclID)) {
          anoSegIDs = [];
          for (j = 0; j < sclIDs.length; j++) {
            if (typeof sclData[sclIDs[j]] !== 'undefined') {
              anoSegIDs.push('seg' + sclData[sclIDs[j]].segID);
            }
          }
          for (j = 0; j < sclData[tokAnoSclID].annotations.length; j++) {
            anoTitle = this.dataMgr.entities.tok[parsedTokID.id].transcr.replaceAll("ʔ", "");
            anoDescription = '';
            transText = this.getTranslationText(tokIDs[i]);
            if (transText !== null) {
              anoDescription += '<p>Translation: ' + transText + '</p>';
            }
            chayaText = this.getChayaText(tokIDs[i]);
            if (chayaText !== null) {
              anoDescription += '<p>Chaya: ' + chayaText + '</p>';
            }

            anoData.token.push({
              title: anoTitle,
              text: anoDescription,
              coords: sclData[tokAnoSclID].annotations[j].coords.split(','),
              cameraPosition: sclData[tokAnoSclID].annotations[j].cameraPosition.split(','),
              cameraTarget: sclData[tokAnoSclID].annotations[j].cameraTarget.split(','),
              segIDs: anoSegIDs,
              entityID: tokIDs[i]
            });
          }
        }
      }

      // Compound.
      anoData.compound = [];
      tokIDs = this.getEditionTokenIDs(false);
      var tokTrans;
      for (i = 0; i < tokIDs.length; i++) {
        parsedTokID = this.parseGID(tokIDs[i]);
        if (parsedTokID.prefix === 'cmp') {
          sclIDs = this.getCompoundSyllableIDs(parsedTokID.id);
        } else {
          sclIDs = this.getTokenSyllableIDs(parsedTokID.id);
        }
        tokAnoSclID = this.findArrayMidItem(sclIDs);
        if (sclData.hasOwnProperty(tokAnoSclID)) {
          anoSegIDs = [];
          for (j = 0; j < sclIDs.length; j++) {
            if (typeof sclData[sclIDs[j]] !== 'undefined') {
              anoSegIDs.push('seg' + sclData[sclIDs[j]].segID);
            }
          }
          for (j = 0; j < sclData[tokAnoSclID].annotations.length; j++) {
            if (parsedTokID.prefix === 'cmp') {
              anoTitle = this.dataMgr.entities.cmp[parsedTokID.id].transcr.replaceAll("ʔ", "");
            } else {
              anoTitle = this.dataMgr.entities.tok[parsedTokID.id].transcr.replaceAll("ʔ", "");
            }
            transText = this.getTranslationText(tokIDs[i]);
            anoDescription = '';
            if (transText !== null) {
              anoDescription += '<p>Translation: ' + transText + '</p>';
            }
            chayaText = this.getChayaText(tokIDs[i]);
            if (chayaText !== null) {
              anoDescription += '<p>Chaya: ' + chayaText + '</p>';
            }

            anoData.compound.push({
              title: anoTitle,
              text: anoDescription,
              coords: sclData[tokAnoSclID].annotations[j].coords.split(','),
              cameraPosition: sclData[tokAnoSclID].annotations[j].cameraPosition.split(','),
              cameraTarget: sclData[tokAnoSclID].annotations[j].cameraTarget.split(','),
              segIDs: anoSegIDs,
              entityID: tokIDs[i]
            });
          }
        }
      }
    }
    return anoData;
  },

  /**
   * Get the syllable GIDs of the edition.
   *
   * @return {Array} An list of syllable GIDs.
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
                      sclIDs.push(subSeq.entityIDs[j]);
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
   * Get the token/compound GIDs from the edition.
   *
   * @param {boolean} breakCompound Whether to break compound into tokens.
   * @return {Array} A list of entity GIDs.
   */
  getEditionTokenIDs: function (breakCompound) {
    var tokenIDs = [];
    var i, j, k;
    if (this.edition !== null) {
      var ednSeqIDs = this.edition.seqIDs;
      if (ednSeqIDs) {
        var ednTokSeq = null;
        for (i = 0; i < ednSeqIDs.length; i++) {
          if (this.dataMgr.entities.seq[ednSeqIDs[i]].typeID === '738') {
            ednTokSeq = this.dataMgr.entities.seq[ednSeqIDs[i]];
            break;
          }
        }
        if (ednTokSeq) {
          var subSeqIDs = ednTokSeq.entityIDs;
          if (subSeqIDs) {
            for (i = 0; i < subSeqIDs.length; i++) {
              var subSeqParsedID = this.parseGID(subSeqIDs[i]);
              if (subSeqParsedID.prefix === 'seq') {
                var subSeq = this.dataMgr.entities.seq[subSeqParsedID.id];
                if (subSeq.entityIDs) {
                  for (j = 0; j < subSeq.entityIDs.length; j++) {
                    var parsedEntityID = this.parseGID(subSeq.entityIDs[j]);
                    if (parsedEntityID.prefix === 'tok') {
                      tokenIDs.push(subSeq.entityIDs[j]);
                    } else if (parsedEntityID.prefix === 'cmp') {
                      if (breakCompound) {
                        var cmp = this.dataMgr.entities.cmp[parsedEntityID.id];
                        if (cmp && cmp.tokenIDs && cmp.tokenIDs.length > 0) {
                          for (k = 0; k < cmp.tokenIDs.length; k++) {
                            tokenIDs.push('tok:' + cmp.tokenIDs[k]);
                          }
                        }
                      } else {
                        tokenIDs.push(subSeq.entityIDs[j]);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return tokenIDs;
  },

  /**
   * Get the entity annotation text.
   *
   * @param {string} entityGID The entity GID.
   * @param {string} anoTypeID The entity annotation type ID.
   * @return {string}
   */
  getEntityAnnotationText: function (entityGID, anoTypeID) {
    var annotations = this.dataMgr.entities.ano;
    var anoText = null;
    var i;
    if (annotations) {
      for (i in annotations) {
        if (
            annotations[i].typeID === anoTypeID &&
            annotations[i].linkedFromIDs &&
            annotations[i].linkedFromIDs.indexOf(entityGID) >= 0
        ) {
          anoText = annotations[i].text;
          break;
        }
      }
    }
    return anoText;
  },

  /**
   * Get the translation text of an entity.
   *
   * @param {string} entityGID The entity GID.
   * @return {*|string}
   */
  getTranslationText: function (entityGID) {
    return this.getEntityAnnotationText(entityGID, "761");
  },

  /**
   * Get the chaya text of an entity.
   *
   * @param {string} entityGID The entity GID.
   * @return {*|string}
   */
  getChayaText: function (entityGID) {
    return this.getEntityAnnotationText(entityGID, "1421");
  },

  /**
   * Get the syllable IDs from a token.
   *
   * @param {int} tokenID The ID of the token.
   * @return {array} A list of syllable IDs.
   */
  getTokenSyllableIDs: function (tokenID) {
    var i;
    var sclIDs = [];
    var token = this.dataMgr.entities.tok[tokenID];
    for (i = 0; i < token.syllableClusterIDs.length; i++) {
      if (sclIDs.indexOf(token.syllableClusterIDs[i]) < 0) {
        sclIDs.push(token.syllableClusterIDs[i]);
      }
    }
    return sclIDs;
  },

  /**
   * Get the syllable IDs from a compound.
   *
   * @param {int} cmpID The ID of the compound.
   * @return {Array} A list of syllable IDs.
   */
  getCompoundSyllableIDs: function (cmpID) {
    var tokIDs = this.dataMgr.entities.cmp[cmpID].tokenIDs;
    var i;
    var sclIDs = [];
    for (i = 0; i < tokIDs.length; i++) {
      sclIDs = sclIDs.concat(this.getTokenSyllableIDs(tokIDs[i]));
    }
    return sclIDs;
  },

  /**
   * Find the middle element from an array.
   *
   * @param {Array} a The input array.
   * @return {*} The middle element. If the number of elements is odd, then it
   *   will return the first middle element.
   */
  findArrayMidItem: function (a) {
    if (a.length > 0) {
      if (a.length === 1) {
        return a[0];
      } else if (a.length % 2 === 1) {
        return a[(a.length - 1) / 2];
      } else {
        return a[a.length / 2 - 1];
      }
    }
    return null;
  },

  /**
   * Find the annotation index by the segment IDs.
   *
   * @param {array} segIDs
   * @return {int}
   */
  findAnnotationIndexBySegID: function (segIDs) {
    var i;
    var objLevel = this.layoutMgr.getEditionObjectLevel();
    for (i = 0; i < this.annotationData[objLevel].length; i++) {
      if (this.arrayEqual(this.annotationData[objLevel][i].segIDs, segIDs)) {
        return this.annotationData[objLevel][i].index;
      }
    }
    return null;
  },

  /**
   * Find the segment IDs by the annotation index.
   *
   * @param {int} index
   * @return {Array}
   */
  findSegIDByAnnotationIndex: function (index) {
    var i;
    var objLevel = this.layoutMgr.getEditionObjectLevel();
    for (i = 0; i < this.annotationData[objLevel].length; i++) {
      if (this.annotationData[objLevel][i].index === index) {
        return this.annotationData[objLevel][i].segIDs;
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
   * Test whether two arrays are equal.
   *
   * @param {Array} a
   * @param {Array} b
   * @return {boolean}
   */
  arrayEqual: function (a, b) {
    if (a === b) return true;
    if (a == null || b == null) return false;
    if (a.length !== b.length) return false;
    for (var i = 0; i < a.length; ++i) {
      if (a[i] !== b[i]) return false;
    }
    return true;
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
        if (segID) {
          var annoIndex = tdVE.findAnnotationIndexBySegID(selectionGIDs);
          if (annoIndex !== null) {
            tdVE.hideAllCurrentAnnotations();
            tdVE.api.showAnnotation(annoIndex, function(err, index) {
              if (!err) {
                tdVE.api.gotoAnnotation(annoIndex, {preventCameraAnimation: false, preventCameraMove: false});
              }
            });
          }
        }
      }
    }

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);

    /**
     * Event handler when the object level is changed.
     */
    function objLevelChangedHandler() {
      if (tdVE.id === tdVE.layoutMgr.focusPaneID) {
        tdVE.refreshAnnotations();
      } else {
        tdVE.hideAllAnnotations();
      }
    }

    $(this.editDiv).unbind('objectLevelChanged').bind('objectLevelChanged', objLevelChangedHandler);

    // Bind the click event to the iframe overlay.
    $(this.editDiv).find('.iframe-overlay').unbind('click').bind('click', function () {
      $(this).hide();
      tdVE.showAllCurrentAnnotations();
      tdVE.layoutMgr.curLayout.trigger('focusin', tdVE.id);
    });

    /**
     * Event handler when the 3D VE pane is out of focus.
     */
    function focusoutHandler() {
      $(this).find('.iframe-overlay').show();
      tdVE.hideAllAnnotations();
    }

    $(this.editDiv).unbind('focusout').bind('focusout', focusoutHandler);
  }
};
