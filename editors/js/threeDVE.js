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
 * 3D VE object
 *
 * @author      Yang Li
 * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
 * @link        https://github.com/readsoftware
 * @version     1.0
 * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
 * @package     READ Research Environment for Ancient Documents
 * @subpackage  Editor Classes
 */
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
  this.annotationData = {};
  this.viewToolbar = null;
  this.editToolbar = null;
  this.toolAnnotateDiv = null;
  this.toolPropertyDiv = null;
  this.toolAnnotateBtn = null;
  this.toolPropertyBtn = null;
  this.annotateMode = false;
  this.contentWrapperElement = null;
  this.propPaneExpanded = false;
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
    this.contentWrapperElement = $('<div class="tdv-content"></div>');
    var frameWidth = $(this.editDiv).width();
    var frameHeight = $(this.editDiv).height();
    var frameElement = $('<div class="iframe-wrapper"><div class="iframe-status">Adjust the camera and click on the model</div><div class="iframe-overlay"></div><iframe src="" id="tdvFrame" allow="autoplay; fullscreen; vr" ' +
        'allowvr allowfullscreen mozallowfullscreen="true" webkitallowfullscreen="true">' +
        '</iframe></div>');
    var propertyPane = $('<div class="tdv-prop-pane"></div>');
    propertyPane.css({
      "padding": '10px',
      'background-color': '#d9d9d9',
      'border-left': '2px solid #828181'
    });
    this.contentWrapperElement.append(frameElement);
    this.contentWrapperElement.append(propertyPane);
    $(this.editDiv).append(this.contentWrapperElement);
    this.contentWrapperElement.jqxSplitter({
      width: '100%',
      height: '100%',
      orientation: 'vertical',
      splitBarSize: 1,
      showSplitBar:false,
      panels: [
          { size: '60%', min: '250', collapsible: false},
          { size: '40%', min: '150', collapsed: true, collapsible: true}
      ]
    });
    this.loadDefaultPropertyView();

    // Initiate the viewer.
    $(this.editDiv).find('.iframe-wrapper').width(frameWidth);
    $(this.editDiv).find('.iframe-wrapper').height(frameHeight);
    $(this.editDiv).find('.iframe-wrapper').css({
      "display": "inline-block",
      "position": "relative"
    });
    $(this.editDiv).find('.iframe-status').css({
      "position": "absolute",
      "z-index": "1",
      "width": "100%",
      "height": "20px",
      "left": "0",
      "top": "0",
      "background-color": 'orange',
      "padding-left": '5px',
      "display": 'none'
    });
    $(this.editDiv).find('.iframe-overlay').css({
      "position": "absolute",
      "z-index": "2",
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
            }
            tdVE.api.addEventListener('annotationFocus', function(index) {
              var segIDs = tdVE.findSegIDByAnnotationIndex(index);
              if (segIDs) {
                $('.editContainer').trigger('updateselection',[tdVE.id, segIDs]);
              }
              tdVE.loadAnnotationPropertyView(index);
            });
            tdVE.api.addEventListener('click', function (info) {
              if (tdVE.annotateMode) {
                tdVE.api.getCameraLookAt(function(err, camera) {
                  var segID = tdVE.getEdnVESelectedSegID();
                  var sclID = tdVE.getEdnVESelectedSclID();
                  if (segID && sclID) {
                    tdVE.addSyllableAnnotation(segID, sclID, info.position3D, camera.position, camera.target);
                  } else {
                    alert('A segment must be selected from the edition VE');
                  }
                  tdVE.exitAnnotateMode();
                });
              }
            });
            tdVE.addEventHandlers();
          });
          tdVE.api.start();
          tdVE.createStaticToolbar();
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
   * The method is written in this way due to the async nature of Sketchfab
   * api to ensure the sequence of 3D annotation creation.
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
          this.showAnnotationByDatum(this.annotationData.token[i]);
        }
        for (i = 0; i < this.annotationData.compound.length; i++) {
          this.hideAnnotationByDatum(this.annotationData.compound[i]);
        }
        for (i = 0; i < this.annotationData.syllable.length; i++) {
          this.hideAnnotationByDatum(this.annotationData.syllable[i]);
        }
        break;
      case 'compound':
        for (i = 0; i < this.annotationData.compound.length; i++) {
          this.showAnnotationByDatum(this.annotationData.compound[i]);
        }
        for (i = 0; i < this.annotationData.token.length; i++) {
          this.hideAnnotationByDatum(this.annotationData.token[i]);
        }
        for (i = 0; i < this.annotationData.syllable.length; i++) {
          this.hideAnnotationByDatum(this.annotationData.syllable[i]);
        }
        break;
      default:
        for (i = 0; i < this.annotationData.syllable.length; i++) {
          this.showAnnotationByDatum(this.annotationData.syllable[i]);
        }
        for (i = 0; i < this.annotationData.token.length; i++) {
          this.hideAnnotationByDatum(this.annotationData.token[i]);
        }
        for (i = 0; i < this.annotationData.compound.length; i++) {
          this.hideAnnotationByDatum(this.annotationData.compound[i]);
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
          this.hideAnnotationByDatum(this.annotationData[objLevel][i]);
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
        this.hideAnnotationByDatum(this.annotationData[objLevel][i]);
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
        this.showAnnotationByDatum(this.annotationData[objLevel][i]);
      }
    }
  },

  /**
   * Show an 3D annotation by the annotation data item.
   *
   * @param {object} datum The single item of the annotation data array.
   * @param {function} callback Any callback function to execute after the
   *   annotation is shown.
   */
  showAnnotationByDatum: function (datum, callback) {
    if (typeof datum.index !== 'undefined' && (typeof datum.deleted === 'undefined' || !datum.deleted)) {
      if (typeof callback === 'function') {
        this.api.showAnnotation(datum.index, callback);
      } else {
        this.api.showAnnotation(datum.index);
      }
    }
  },

  /**
   * Hide an 3D annotation by the annotation data item.
   *
   * @param {object} datum The single item of the annotation data array.
   * @param {function} callback Any callback function to execute after the
   *   annotation is hidden.
   */
  hideAnnotationByDatum: function (datum, callback) {
    if (typeof datum.index !== 'undefined') {
      if (typeof callback === 'function') {
        this.api.hideAnnotation(datum.index, callback);
      } else {
        this.api.hideAnnotation(datum.index);
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
    var anoData = {
      syllable: [],
      token: [],
      compound: []
    };
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
                entityID: sclIDs[i],
                type: 'syllable'
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
              entityID: tokIDs[i],
              type: 'token'
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
              entityID: tokIDs[i],
              type: 'compound'
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
    var sclGIDs = [];
    var i, j;
    if (this.edition !== null) {
      var ednSeqIDs = this.edition.seqIDs;
      if (ednSeqIDs) {
        var ednSclSeq = null;
        for (i = 0; i < ednSeqIDs.length; i++) {
          // !!!warning term dependency
          if (this.dataMgr.entities.seq[ednSeqIDs[i]].typeID === this.dataMgr.getIDFromTermParentTerm('TextPhysical', 'SequenceType').toString()) {
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
                      sclGIDs.push(subSeq.entityIDs[j]);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return sclGIDs;
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
          // !!!warning term dependency
          if (this.dataMgr.entities.seq[ednSeqIDs[i]].typeID === this.dataMgr.getIDFromTermParentTerm('Text', 'SequenceType').toString()) {
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
    // !!!warning term dependency
    var anoTypeID = this.dataMgr.getIDFromTermParentTerm('Translation', 'AnnotationType').toString();
    return this.getEntityAnnotationText(entityGID, anoTypeID);
  },

  /**
   * Get the chaya text of an entity.
   *
   * @param {string} entityGID The entity GID.
   * @return {*|string}
   */
  getChayaText: function (entityGID) {
    // !!!warning term dependency
    var anoTypeID = this.dataMgr.getIDFromTermParentTerm('Chaya', 'Translation').toString();
    return this.getEntityAnnotationText(entityGID, anoTypeID);
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
   * @param string objLevel The object level of the annotation. If omitted, it
   *     will search across all object levels.
   * @return {int}
   */
  findAnnotationIndexBySegID: function (segIDs, objLevel) {
    var i;
    if (typeof objLevel !== 'undefined' && objLevel) {
      for (i = 0; i < this.annotationData[objLevel].length; i++) {
        if (this.arrayEqual(this.annotationData[objLevel][i].segIDs, segIDs) && !this.annotationData[objLevel][i].deleted) {
          return this.annotationData[objLevel][i].index;
        }
      }
    } else {
      for (objLevel in this.annotationData) {
        if (this.annotationData.hasOwnProperty(objLevel)) {
          for (i = 0; i < this.annotationData[objLevel].length; i++) {
            if (this.arrayEqual(this.annotationData[objLevel][i].segIDs, segIDs) && !this.annotationData[objLevel][i].deleted) {
              return this.annotationData[objLevel][i].index;
            }
          }
        }
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
          var objLevel = tdVE.layoutMgr.getEditionObjectLevel();
          var annoIndex = tdVE.findAnnotationIndexBySegID(selectionGIDs, objLevel);
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
      tdVE.api.unselectAnnotation();
      tdVE.hideAllAnnotations();

    }

    $(this.editDiv).unbind('focusout').bind('focusout', focusoutHandler);
  },

  /**
   * Create the tool pane UI for the 3D VE.
   */
  createStaticToolbar: function() {
    var tdv = this;
    var btnAnnotateID = this.id + 'annotate';
    var btnPropertyID = this.id + 'properties';
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    // Create annotation button.
    this.toolAnnotateDiv = $('<div class="toolbuttondiv">' +
        '<button class="toolbutton" id="' + btnAnnotateID +
        '" title="Create a 3D annotation">Annotate</button>'+
        '<div class="toolbuttonlabel">Create 3D annotations</div>'+
        '</div>');
    this.toolAnnotateBtn = $('#' + btnAnnotateID, this.toolAnnotateDiv);
    this.editToolbar.append(this.toolAnnotateDiv);
    this.toolAnnotateBtn.unbind('click').bind('click', function () {
      if (tdv.layoutMgr.getEditionObjectLevel() === 'syllable') {
        var segID = tdv.getEdnVESelectedSegID();
        if (segID) {
          if (tdv.findAnnotationIndexBySegID(['seg' + segID]) !== null) {
            alert('An annotation has already been associated with this syllable. Delete the existing annotation before creating the new annotation.');
          } else {
            if (tdv.annotateMode) {
              tdv.exitAnnotateMode();
            } else {
              tdv.enterAnnotateMode();
            }
          }
        } else {
          alert('A syllable must be selected from the edition VE');
        }
      } else {
        alert('This operation must be performed under syllable object level.');
      }
    });

    // Create properties button.
    this.toolPropertyDiv = $('<div class="toolbuttondiv">' +
        '<button class="toolbutton iconbutton" id="' + btnPropertyID +
        '" title="Show/Hide property panel">&#x25E8;</button>' +
        '<div class="toolbuttonlabel">Properties</div>' +
        '</div>');
    this.toolPropertyBtn = $('#' + btnPropertyID, this.toolPropertyDiv);
    this.viewToolbar.append(this.toolPropertyDiv);
    this.toolPropertyBtn.unbind('click').bind('click', function () {
      tdv.togglePropertyPane();
      tdv.refreshModelSize();
    });

    this.layoutMgr.registerViewToolbar(this.id, this.viewToolbar);
    this.layoutMgr.registerEditToolbar(this.id, this.editToolbar);
  },

  /**
   * Enter the annotate mode.
   *
   * Once entered, the 3D model will wait for the click to create the annotation
   * on that position.
   */
  enterAnnotateMode: function () {
    this.api.unselectAnnotation();
    this.annotateMode = true;
    this.contentWrapperElement.find('.iframe-status').show();
    this.toolAnnotateBtn.html('Cancel');
  },

  /**
   * Exit the annotate mode.
   */
  exitAnnotateMode: function () {
    this.annotateMode = false;
    this.contentWrapperElement.find('.iframe-status').hide();
    this.toolAnnotateBtn.html('Annotate');
  },

  /**
   * Get the current active Edition VE.
   *
   * @return {object} The edition VE instance, or null if no edition VE is active.
   */
  getActivatedEdnVE: function () {
    var paneID;
    for (paneID in this.layoutMgr.editors) {
      if (this.layoutMgr.editors.hasOwnProperty(paneID) && this.layoutMgr.editors[paneID].type === 'EditionVE') {
        return this.layoutMgr.editors[paneID];
      }
    }
    return null;
  },

  /**
   * Get the segment ID of the current selected syllable in the edition VE.
   *
   * @return {int} Returns null if not found.
   */
  getEdnVESelectedSegID: function () {
    return this.getEdnVESelectedEntityID('seg');
  },

  /**
   * Get the syllable ID of the current selected syllable in the edition VE.
   *
   * @return {int} Returns null if not found.
   */
  getEdnVESelectedSclID: function () {
    return this.getEdnVESelectedEntityID('scl');
  },

  /**
   * Get the entity ID of the current selected entity in the edition VE.
   *
   * @param {string} entityCode The short code of the entity.
   * @return {int} Returns null if not found.
   */
  getEdnVESelectedEntityID: function (entityCode) {
    var ednVE = this.getActivatedEdnVE();
    if (ednVE) {
      var selectedScl = $('.grpGra.selected', ednVE.contentDiv);
      if (selectedScl.length > 0) {
        var regex = new RegExp(entityCode + '\\d+');
        var selectedSegLabel = selectedScl.get(0).className.match(regex);
        if (selectedSegLabel.length > 0) {
          return selectedSegLabel[0].substring(3);
        }
      }
    }
    return null;
  },

  /**
   * Create the datum for the annotation data in the data manager.
   *
   * @param {int} sclID The syllable ID.
   * @param {int} segID The segment ID.
   * @param {array} coords The coordinates of the annotation.
   * @param {array} cameraPosition The camera position of the annotation.
   * @param {array} cameraTarget The camera target of the annotation.
   * @return {object}
   */
  createSyllableAnnotationSourceDatum: function (sclID, segID, coords, cameraPosition, cameraTarget) {
    var sclTrans = '';
    if (typeof this.dataMgr.entities.scl[sclID].value !== 'undefined') {
      sclTrans = this.dataMgr.entities.scl[sclID].value;
    }
    return {
      sclID: sclID,
      sclTrans: sclTrans,
      segID: segID,
      annotations: [{
        coords: coords,
        cameraPosition: cameraPosition,
        cameraTarget: cameraTarget
      }]
    };
  },

  /**
   * Toggle the property pane of the VE.
   */
  togglePropertyPane: function() {
    if (this.propPaneExpanded) {
      this.collapsePropertyPane();
    } else {
      this.expandPropertyPane();
    }
  },

  /**
   * Expand the property pane of the VE.
   */
  expandPropertyPane: function () {
    this.propPaneExpanded = true;
    this.contentWrapperElement.jqxSplitter('expand');
    this.contentWrapperElement.jqxSplitter({ showSplitBar: true });
  },

  /**
   * Collapse the property pane of the VE.
   */
  collapsePropertyPane: function () {
    this.propPaneExpanded = false;
    this.contentWrapperElement.jqxSplitter('collapse');
    this.contentWrapperElement.jqxSplitter({ showSplitBar: false });
  },

  /**
   * Refresh the 3D model size based on the current container size.
   */
  refreshModelSize: function () {
    var width = $('.iframe-wrapper', this.contentWrapperElement).width();
    var height = $('.iframe-wrapper', this.contentWrapperElement).height();
    $('#tdvFrame', this.contentWrapperElement).width(width);
    $('#tdvFrame', this.contentWrapperElement).height(height);
  },

  /**
   * Load the default view of the property pane.
   */
  loadDefaultPropertyView: function () {
    var content = '<p>Select an annotation to see properties.</p>';
    this.contentWrapperElement.find('.tdv-prop-pane').html(content);
  },

  /**
   * Load the view of the annotation in the property pane.
   *
   * @param {int} anoIndex The index of the 3D annotation.
   */
  loadAnnotationPropertyView: function (anoIndex) {
    var tdVE = this;
    var anoDatum = this.findAnnotationDatumByIndex(anoIndex);
    if (anoDatum) {
      var content = '<h4>Annotation: ' + anoDatum.title + '</h4>';
      content += '<p>' + anoDatum.text + '</p>';
      if (anoDatum.type === 'syllable') {
        content += '<p>' +
            '<button class="toolbutton" style="margin-bottom:10px" id="tdvPropBtnAnoEdit">Edit</button> ' +
            '<button class="toolbutton" style="margin-bottom:10px" id="tdvPropBtnAnoDelete">Delete</button> ' +
            '<button class="toolbutton" style="margin-bottom:10px" id="tdvPropBtnAnoNext">Create Next</button> ' +
            '</p>';
      }
      this.contentWrapperElement.find('.tdv-prop-pane').html(content);
      this.contentWrapperElement.find('#tdvPropBtnAnoEdit').unbind('click').bind('click', function () {
        if (tdVE.layoutMgr.getEditionObjectLevel() === 'syllable') {
          tdVE.editAnnotationByIndex(anoIndex);
        } else {
          alert('This operation must be performed under syllable object level.');
        }
      });
      this.contentWrapperElement.find('#tdvPropBtnAnoDelete').unbind('click').bind('click', function () {
        if (tdVE.layoutMgr.getEditionObjectLevel() === 'syllable') {
          tdVE.deleteAnnotationByIndex(anoIndex);
        } else {
          alert('This operation must be performed under syllable object level.');
        }
      });

      this.contentWrapperElement.find('#tdvPropBtnAnoNext').unbind('click').bind('click', function () {
        if (tdVE.layoutMgr.getEditionObjectLevel() === 'syllable') {
          var segPrefixedID = anoDatum.segIDs[0];
          var nextSegID = tdVE.advanceEdnVESyllableSelection(segPrefixedID);
          if (nextSegID) {
            if (tdVE.findAnnotationIndexBySegID(['seg' + nextSegID]) !== null) {
              alert('An annotation has already been associated with this syllable. Delete the existing annotation before creating the new annotation.');
            } else {
              tdVE.enterAnnotateMode();
            }
          } else {
            alert('Reached end of the line.');
          }
        } else {
          alert('This operation must be performed under syllable object level.');
        }
      });
    }
  },

  /**
   * Find the datum from the annotation data based on the 3D annotation index.
   *
   * @param {int} index
   * @return {object}
   */
  findAnnotationDatumByIndex: function (index) {
    if (Object.keys(this.annotationData).length > 0) {
      var anoType;
      var i;
      for (anoType in this.annotationData) {
        if (this.annotationData.hasOwnProperty(anoType) && this.annotationData[anoType].length > 0) {
          for (i = 0; i < this.annotationData[anoType].length; i++) {
            if (typeof this.annotationData[anoType][i].index !== 'undefined' && this.annotationData[anoType][i].index === index) {
              this.annotationData[anoType][i].type = anoType;
              return this.annotationData[anoType][i];
            }
          }
        }
      }
    }
    return null;
  },

  /**
   * Delete an annotation by its index.
   *
   * @param {int} index
   */
  deleteAnnotationByIndex: function (index) {
    var tdVE = this;
    if (Object.keys(this.annotationData).length > 0) {
      var anoType;
      var i;
      for (anoType in this.annotationData) {
        if (this.annotationData.hasOwnProperty(anoType) && this.annotationData[anoType].length > 0) {
          for (i = 0; i < this.annotationData[anoType].length; i++) {
            if (typeof this.annotationData[anoType][i].index !== 'undefined' && this.annotationData[anoType][i].index === index) {
              var segID = this.annotationData[anoType][i]['segIDs'][0].substr(3);
              var sclID = this.annotationData[anoType][i]['entityID'].substr(4);
              if (typeof this.dataMgr.tdViewerData.annotations.syllables[sclID] !== 'undefined') {
                delete this.dataMgr.tdViewerData.annotations.syllables[sclID];
              }
              this.annotationData[anoType][i].deleted = true;
              this.api.unselectAnnotation();
              this.hideAnnotationByDatum(this.annotationData[anoType][i]);
              this.loadDefaultPropertyView();
              $.ajax({
                type:"POST",
                dataType: 'json',
                url: this.dataMgr.basepath + '/services/saveSegment3DAnnotation.php?db=' + this.dataMgr.dbName,
                data: {
                  segID: segID
                },
                success: function (data, status, xhr) {
                  if (!data.success) {
                    alert("An error occurred while trying to delete the 3D model Annotation. Error: " + data.errors.join(','))
                  }
                },
                error: function (xhr,status,error) {
                  alert("An error occurred while trying to delete the 3D model annotation. Error: " + error);
                }
              });
              break;
            }
          }
        }
      }
    }
  },

  /**
   * Edit an annotation by its index.
   *
   * @param {int} index
   */
  editAnnotationByIndex: function (index) {
    this.deleteAnnotationByIndex(index);
    this.enterAnnotateMode();
  },

  /**
   * Add an annotation for a syllable.
   *
   * @param {int} segID The segment ID of the syllable.
   * @param {int} sclID The syllable ID.
   * @param {array} position The position of the annotation.
   * @param {array} camPosition The camera position of the annotation.
   * @param {array} camTarget The camera target of the annotation.
   */
  addSyllableAnnotation: function (segID, sclID, position, camPosition, camTarget) {
    var tdVE = this;
    $.ajax({
      type:"POST",
      dataType: 'json',
      url: this.dataMgr.basepath + '/services/saveSegment3DAnnotation.php?db=' + this.dataMgr.dbName,
      data: {
        segID: segID,
        coords: position.join(','),
        cameraPosition: camPosition.join(','),
        cameraTarget: camTarget.join(',')
      },
      success: function (data, status, xhr) {
        if (!data.success) {
          alert("An error occurred while trying to save the 3D model Annotation. Error: " + data.errors.join(','))
        } else {
          var anoSourceDatum = tdVE.createSyllableAnnotationSourceDatum(
              sclID,
              segID,
              position.join(','),
              camPosition.join(','),
              camTarget.join(',')
          );
          var anoTitle = anoSourceDatum.sclTrans.replaceAll("ʔ", "");
          var anoDescription = '';
          tdVE.api.createAnnotationFromWorldPosition( //creates annotation
              position,
              camPosition,
              camTarget,
              anoTitle,
              anoDescription,
              function(err, index){
                if (typeof tdVE.dataMgr.tdViewerData === 'undefined') {
                  tdVE.dataMgr.tdViewerData = {};
                }
                if (typeof tdVE.dataMgr.tdViewerData.annotations === 'undefined') {
                  tdVE.dataMgr.tdViewerData.annotations = {};
                }
                if (typeof tdVE.dataMgr.tdViewerData.annotations.syllables === 'undefined') {
                  tdVE.dataMgr.tdViewerData.annotations.syllables = {};
                }
                tdVE.dataMgr.tdViewerData.annotations.syllables[sclID] = anoSourceDatum;

                tdVE.annotationData.syllable.push({
                  title: anoTitle,
                  text: anoDescription,
                  coords: position,
                  cameraPosition: camPosition,
                  cameraTarget: camTarget,
                  segIDs: ['seg' + segID],
                  entityID: 'scl:' + sclID,
                  index: index
                });
                tdVE.api.gotoAnnotation(index, {preventCameraAnimation: false, preventCameraMove: false});
              });
        }
      },
      error: function (xhr,status,error) {
        alert("An error occurred while trying to save the 3D model annotation. Error: " + error);
      }
    });
  },

  /**
   * Advance to the next syllable of the current selected syllable in the edition VE.
   *
   * @param {string} segPrefixedID The segment ID with the prefix "seg".
   * @return {int} The segment ID of the next syllable, or null if there's no
   *   following syllable.
   */
  advanceEdnVESyllableSelection: function (segPrefixedID) {
    var ednVE = this.getActivatedEdnVE();
    var nextSclElement = null;
    var nextSegID = null;
    if (ednVE) {
      var sclElement = ednVE.contentDiv.find('span.grpGra.' + segPrefixedID).last();
      var regex = new RegExp('seg\\d+');
      var elementClass;
      var segIDMatch;
      sclElement.nextAll('span.grpGra').each(function () {
        elementClass = $(this).attr('class');
        segIDMatch = elementClass.match(regex);
        if (segIDMatch.length > 0) {
          nextSclElement = $(this);
          nextSegID = segIDMatch[0].substr(3);
          return false;
        }
      });
      if (nextSegID !== null) {
        $(".selected", ednVE.contentDiv).removeClass("selected");
        nextSclElement.addClass('selected');
        nextSclElement.nextAll('span.grpGra.seg' + nextSegID).addClass('selected');
      }
    }
    return nextSegID;
  }
};
