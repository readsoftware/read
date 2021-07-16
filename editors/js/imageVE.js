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
* editors imageViewerEditor object
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
* Constructor for Image Viewer Object
*
* @type Object
*
* @param imgVECfg is a JSON object with the following possible properties
*  "imageCanvas" an HTML5 canvas element for displaying the image.
*  "imageEditDiv" an HTML DIV element where the editor UI (canvas object + tools) is or can be attached.
*  "initViewPercent" a number between 10 and 100 indication the initial percentage of the image to display.
*  "initViewOffset" a point indicating the image coordinate to place at the upperleft of the viewer
*  "navOpacity" a number from 10 to 100 indicating the opacity of the navigation panel
*  "navSizePercent" a number from 5 to 30 roughly indicating the size of the nav panel as a percentage of the viewer's size
*  "navPositionTop" a number pixels from the top to position the top of the nav panel
*  "navPositionLeft" a number pixels from the left to position the left of the nav panel
*
* @returns {ImageVE}

*/

EDITORS.ImageVE =  function(imgVECfg) {
  var imgVE = this, imgFilename,imgSrc, entGID,
      imgContainerDiv = $('<div id="imgContainerDiv" />');
  //read configuration and set defaults
  this.config = imgVECfg;
  this.type = "ImageVE";
  this.dataMgr = imgVECfg['dataMgr'] ? imgVECfg['dataMgr']:null;
  this.layoutMgr = imgVECfg['layoutMgr'] ? imgVECfg['layoutMgr']:null;
  this.eventMgr = imgVECfg['eventMgr'] ? imgVECfg['eventMgr']:null;
  this.id = imgVECfg['id'] ? imgVECfg['id']: null;
  this.image = imgVECfg['image'] ? imgVECfg['image']:null;
  this.imgCanvas = imgVECfg['imageCanvas'] ? imgVECfg['imageCanvas']:null;
  this.zoomFactor = imgVECfg['initViewPercent'] ? imgVECfg['initViewPercent']:150;
  this.vwOffset = imgVECfg['initViewOffset'] && !isNaN(imgVECfg['initViewOffset'].x) && !isNaN(imgVECfg['initViewOffset'].y) ? imgVECfg['initViewOffset']:{x:0,y:0};
  this.navOpacity = imgVECfg['navOpacity'] ? imgVECfg['navOpacity']/100:0.5;
  this.navSizePercent = imgVECfg['navSizePercent'] ? imgVECfg['navSizePercent']:10;
  this.navPositionTop = imgVECfg['navPositionTop'] ? imgVECfg['navPositionTop']:10;
  this.navPositionLeft = imgVECfg['navPositionLeft'] ? imgVECfg['navPositionLeft']:10;
  this.editDiv = imgVECfg['imageEditDiv']?imgVECfg['imageEditDiv']: null;
  this.blnEntity = imgVECfg['baseline']?imgVECfg['baseline']: null;
  this.imgEntity = imgVECfg['imgEntity']?imgVECfg['imgEntity']: null;
  if (this.blnEntity) {
    entGID = "bln:"+ this.blnEntity.id;
  } else if (this.imgEntity) {
    entGID = "img:"+this.imgEntity.id;
  }
  this.splitterDiv = $('<div id="'+this.id+'splitter"/>');
  this.propertyMgrDiv = $('<div id="'+this.id+'propManager" class="propertyManager"/>');
  this.splitterDiv.append(imgContainerDiv);
  this.splitterDiv.append(this.propertyMgrDiv);
  $(this.editDiv).append(this.splitterDiv);
  this.splitterDiv.jqxSplitter({ width: '100%',
                                    height: '100%',
                                    orientation: 'vertical',
                                    splitBarSize: 1,
                                    showSplitBar:false,
                                    panels: [{ size: '60%', min: '250', collapsible: false},
                                             { size: '40%', min: '150', collapsed: true, collapsible: true}] });
  this.propMgr = new MANAGERS.PropertyManager({id: this.id,
                                               propertyMgrDiv: this.propertyMgrDiv,
                                               editor: imgVE,
                                               entGID: entGID,
                                               propVEType: "entPropVE",
                                               dataMgr: this.dataMgr,
                                               splitterDiv: this.splitterDiv });
  this.displayProperties = this.propMgr.displayProperties;
  this.imgEditContainer = $('#imgContainerDiv',this.editDiv).get(0);
  this.navParent = imgVECfg['imageEditDiv']?imgVECfg['imageEditDiv']: document.getElementById('body');
  this.zoomFactorRange = { min:5,max:200,inc:2 };
  this.polygons = [];
  this.polygonLookup = {};
  this.fadeColors = {};
  this.selectedPolygons = {};
  this.viewAllPolygons = false; //intially hide any polygons
/*
  if (this.dataMgr && this.dataMgr.entities) {//warninig!!!!! patch until change code to use dataMgr api
    window.entities = this.dataMgr.entities;
    window.trmIDtoLabel = this.dataMgr.termInfo.labelByID;
  }*/
  //create canvas if needed and attach to container
  if (!this.imgCanvas && this.imgEditContainer) {
    this.imgCanvas = document.createElement('canvas');
    this.imgCanvas.tabIndex = 1;
    this.imgEditContainer.appendChild(this.imgCanvas);
    this.imgCanvas.onmouseleave = function(e) {
      var containerClass = e.target.parentNode.className;
      if ($(this).parents('.editContainer')[0] != $(e.relatedTarget).parents('.editContainer')[0]) {
        delete imgVE.focusMode;
//        DEBUG.log("gen"'image canvas has been left '+ containerClass);
//        DEBUG.log("gen"'for '+ (e.relatedTarget ? e.relatedTarget.className :"unknown"));
      }
    };
    this.imgCanvas.onmouseenter = function(e) {
      var containerClass = e.target.parentNode.className;
      if (!imgVE.focusMode) {
        imgVE.focusMode = "mouseIn";
//        DEBUG.log("gen"'image canvas has been entered '+ containerClass);
      }
    };
  }

  if (this.blnEntity && this.blnEntity.segIDs && this.blnEntity.segIDs.length > 0) {
    var i, len = this.blnEntity.segIDs.length, maxOrdinal = 0, segOrdinal, segment;
    for (i=0; i<len; i++) {// add segments to baseline image
      segID = this.blnEntity.segIDs[i];
      segment = this.dataMgr.entities['seg'][segID];
      if (segment) {
        if (segment.ordinal && parseInt(segment.ordinal) > maxOrdinal) {
          maxOrdinal = segment.ordinal;
        }
        //WARNING todo add code for multiple polygon boundary
        if (segment.boundary && segment.boundary.length) {
          for (j=0; j<segment.boundary.length;j++){
            this.addImagePolygon(segment.boundary[j],"seg"+segID,false,segment['sclIDs'],
                                  segment.center,(segment.ordinal?segment.ordinal:null),(segment.code?segment.code:null),
                                  (segment.loc?segment.loc:null));//WARNING!! todo code change boundary can be multiple polygons
          }
        }
      }
    }
    this.maxOrdinal = (1 + parseInt(maxOrdinal));
  }

  //adjust initial size of image canvas
  if (this.imgEditContainer) {
    this.imgCanvas.width = this.imgEditContainer.clientWidth;
    this.imgCanvas.height = this.imgEditContainer.clientHeight;
    $(this.splitterDiv).on('resize',function(e) {
              DEBUG.log("gen","resize for " + imgVE.imgEditContainer.id + " called");
    });
    $(this.splitterDiv).on('resizePane',function(e,w,h) {
            if (imgVE.initHeight != h || imgVE.initWidth != w) {
              $(imgVE.imgEditContainer).height(h);
              $(imgVE.imgEditContainer).width(w);
              DEBUG.log("gen","resizePane for " + imgVE.imgEditContainer.id + " with w-" + w + " h-"+h+ " initw-" + imgVE.initWidth + " inith-"+imgVE.initHeight);
              imgVE.init();
            } else {
              DEBUG.log("gen","resizePane for " + imgVE.imgEditContainer.id + " image editor skipped - same size initw-" + imgVE.initWidth + " inith-"+imgVE.initHeight);
            }
            return false;
    });
  }
  if (this.blnEntity && this.blnEntity.url) {
    imgSrc = this.blnEntity.url;
  } else if (this.imgEntity && this.imgEntity.url) {
    imgSrc = this.imgEntity.url;
  }
  //setup canvas and context
  this.imgCanvas.className = "imgCanvas";
  this.imgContext = this.imgCanvas.getContext('2d');

  //setup image map navigation tool
  this.navCanvas = document.createElement('canvas');
  this.navContext = this.navCanvas.getContext('2d');
  this.navDIV = document.createElement('div');
  this.navDIV.appendChild(this.navCanvas);
  //position this to the top lefthand corner plus config value or 10 pixels
  this.navDIV.style.left = this.navPositionLeft + 'px';
  this.navDIV.style.top = this.navPositionTop + 'px';
  this.navParent.appendChild(this.navDIV);
  this.zoomDIV = $('<div class="zoomUI"><div id="zoomOut" class="zoomButton">-</div><div id="zoomIn" class="zoomButton">+</div></div>').get(0);
  this.navDIV.appendChild(this.zoomDIV);
  if (imgSrc) {
    imgFilename = imgSrc.substring(imgSrc.lastIndexOf("/")+1);
    this.imgNameDiv = $('<div class="imgNameDiv">'+imgFilename+'</div>').get(0);
    this.navDIV.appendChild(this.imgNameDiv);
  }
  if (!this.image) {
    this.image = new Image();
  }
//  this.image.crossOrigin = 'anonymous';//allow cross origin images
// CORS issue which affects getImageData for smooth and fast redraw and clipping of images
  this.crossSize = 10;
  if (this.image.width == 0 || this.image.height == 0) { // image not loaded
    this.image.onload = function(e) {
      imgVE.init();
    };
    if (imgSrc) {
      this.image.src = imgSrc;
    } else {
      alert("Failed to load baseline or image due to lack of information");
    }
  } else {//passed in an image and it's loaded
    this.init();
  }
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
EDITORS.ImageVE.prototype = {
  // configure all values that require the dom image and view elements to be loaded first.

/**
* put your comment there...
*
*/

  init: function() {
    this.imgAspectRatio = this.image.width/this.image.height;
    this.initHeight = this.imgEditContainer.clientHeight;
    this.initWidth = this.imgEditContainer.clientWidth;
    this.imgCanvas.width = Math.min((this.imgEditContainer && this.imgEditContainer.clientWidth?this.imgEditContainer.clientWidth:this.imgCanvas.width),this.image.width);
    this.imgCanvas.height = Math.min((this.imgEditContainer && this.imgEditContainer.clientHeight?this.imgEditContainer.clientHeight:this.imgCanvas.height),this.image.height);
    var width = Math.floor(this.imgCanvas.width  * (this.navSizePercent/100)),
        height = Math.floor(this.imgCanvas.width  * (this.navSizePercent/100) /this.imgAspectRatio);
    this.addEventHandlers();
    this.navCanvas.width = width;
    this.navCanvas.height = height;
    this.navDIV.style.width = (width + 10) + 'px';
    this.navDIV.style.height =  Math.min((height + 35),this.initHeight-15) + 'px'; // include room for UI and source label
    this.navDIV.className = 'navDiv';
    this.navDIV.style.left =  this.navPositionLeft + 'px';;
    this.navDIV.style.top =  this.navPositionTop + 'px';
    this.initViewport();
    this.draw();
    this.createStaticToolbar();
    this.syncNextSegOrdinal();
  },


/**
* put your comment there...
*
*/

  resize: function () {
    this.init();
  },


/**
* sets the focus on the canvas element
*
*/

  setFocus: function () {
    this.imgCanvas.focus();
  },


/**
* Syncs/calculates (max + 1) the next segment order number for this baseline
*
*/

  syncNextSegOrdinal: function () {
    var i, len, maxOrdinal = 0, segID, segIDs;
    if (this.blnEntity && this.blnEntity.segIDs && this.blnEntity.segIDs.length) {
      segIDs = this.blnEntity.segIDs;
    } else if (this.dataMgr.entities.seg) {
      segIDs = Object.keys(this.dataMgr.entities.seg);
    }
    if (segIDs && segIDs.length) {
      len = segIDs.length;
      for (i=0; i<len; i++) {// add segments to baseline image
        segID = segIDs[i];
        segment = this.dataMgr.entities['seg'][segID];
        if (segment) {
          if (segment.ordinal && parseInt(segment.ordinal) > maxOrdinal) {
            maxOrdinal = segment.ordinal;
          }
        }
      }
    }
    this.ordinalSetInput.val(1 + parseInt(maxOrdinal));
  },


/**
* saves a new created polygon
*
*/

savePolygons: function () {
  var imgVE = this, cnt = imgVE.polygons.length,
      i, savedata = {}, segs = {};
  imgVE.clearPath();
  //for each new or dirty segment polygon
  for(i=0; i<cnt; i++) {
    polygon = imgVE.polygons[i];
    if (polygon.dirty) {
      id = polygon.label.substr(3);
      segs[id] = {"boundary":'{"'+JSON.stringify(polygon.polygon).replace(/\[/g,"(").replace(/\]/g,")")+'"}'};
    }
  }
  if (Object.keys(segs).length) {
    //save polygon segment
    savedata["segs"] = segs;
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/updateSegments.php?db='+dbName, //TODO: convert this to saveSegment service with update cache model
        data: savedata,
        asynch: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              imgVE.dataMgr.updateLocalCache(data);
              if (Object.keys(data.entities.update.seg).length) {
                var segID, polygon, segData;
                for(segID in data.entities.update.seg) {
                  segData = data.entities.update.seg[segID];
                  if (segData && segData.boundary) {
                    segLabel = 'seg'+segID;
                    polygon = imgVE.polygons[imgVE.polygonLookup[segLabel]-1]
                    delete polygon.dirty;
                    polygon.polygon = segData.boundary[0]; //TODO design how to manage for multiple polygon segment
                    polygon.center = UTILITY.getCentroid(polygon.polygon);
                  }
                }
                imgVE.selectedPolygons = {};
                imgVE.drawImage();
                imgVE.drawImagePolygons();
              }
              if (data['error']) {
                alert("An error occurred while trying to save to a segments. Error: " + data[error]);
              }
            }
        },// end success cb
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to save to a segment record. Error: " + error);
        }
    });// end ajax
  }
},


/**
* saves a new created polygon
*
*/

  savePolygon: function () {
    var imgVE = this, path = imgVE.getPath(), setOrdVal,
        i, savedata = {}, polygon, segData, newPolyIndex,
        blnID, cnt;
    if (path && path.length > 2 && imgVE.blnEntity) {
      newPolyIndex = imgVE.addImagePolygon(path,"new"+imgVE.newPolyCounter++,true);
      imgVE.clearPath();
      blnID = imgVE.blnEntity.id;
      setOrdVal = parseInt(imgVE.ordinalSetInput.val());
      polygon = imgVE.polygons[newPolyIndex-1];
      savedata = {segID:polygon.label,//warning seg_id assumes new##  not a seg##  id
                  baselineIDs:'{'+blnID+'}',
                  boundary:'{"'+JSON.stringify(polygon.polygon).replace(/\[/g,"(").replace(/\]/g,")")+'"}',
                  layer:1,
                  visibilityIDs:"{3}"};
      if ( setOrdVal ) {
        savedata["ordinal"] = setOrdVal;
        setOrdVal++;
        imgVE.ordinalSetInput.val(setOrdVal);
      }
      //reset new indices
      imgVE.newPolyIndices = [];
      //save polygon segment
      $.ajax({
          dataType: 'json',
          url: basepath+'/services/saveSegment.php?db='+dbName, //TODO: convert this to saveSegment service with update cache model
          data: savedata,
          asynch: true,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                imgVE.dataMgr.updateLocalCache(data,null);
                if (data.tempIDMap) {
                  for (segID in data.tempIDMap) {
                    tempID = data.tempIDMap[segID];
                    segment = imgVE.dataMgr.getEntity('seg',segID);
                    segLabel = 'seg'+segID;
                    //update baseline entity
                    blnIDs = segment.baselineIDs;
                    if (blnIDs && blnIDs.length) {
                      for (i =0; i<blnIDs.length; i++) {
                        baseline = imgVE.dataMgr.getEntity('bln',blnIDs[i]);
                        if (baseline && baseline.segIDs && baseline.segIDs.indexOf(segID) == -1) {
                          baseline.segIDs.push(segID);
                        }
                      }
                    }
                    // add new lookup seg:id for newX index
                    imgVE.polygonLookup[segLabel] = imgVE.polygonLookup[tempID];
                    delete imgVE.polygonLookup[tempID];
                    // change polygon label of newX and color
                    imgVE.polygons[imgVE.polygonLookup[segLabel]-1].label = segLabel;
                    imgVE.polygons[imgVE.polygonLookup[segLabel]-1].color = "red";
                    imgVE.polygons[imgVE.polygonLookup[segLabel]-1].hidden = true;
                    if (segment.ordinal) {
                      imgVE.polygons[imgVE.polygonLookup[segLabel]-1].order = segment.ordinal;
                    }
                    if (segment.code) {
                      imgVE.polygons[imgVE.polygonLookup[segLabel]-1].code = segment.code;
                    }
                    if (segment.loc) {
                      imgVE.polygons[imgVE.polygonLookup[segLabel]-1].loc = segment.loc;
                    }
                    imgVE.selectedPolygons[segLabel] = 1;
                  }
                  if (imgVE.selectedPolygons && Object.keys(imgVE.selectedPolygons).length == 1) {
                    //enable delete button
                    imgVE.delSegBtn.removeAttr('disabled');
                  } else if (!imgVE.delSegBtn.attr('disabled')){
                    //disable delete button
                    imgVE.delSegBtn.attr('disabled','disabled');
                  }
                  //todo ensure that selected are have unselected color
                  imgVE.selectedPolygons = {};
                  if (imgVE.linkMode) {//user is drawing a new segment for linking scl
                    imgVE.linkMode=false;
                    imgVE.drawImage();
                    imgVE.drawImagePolygons();
                    $('.editContainer').trigger('linkResponse',[imgVE.id,segLabel]);
                  } else {
                    imgVE.drawImage();
                    imgVE.drawImagePolygons();
                  }
                }
                if (data['segment'] && data['segment'].errors && data['segment'].errors.length) {
                  alert("An error occurred while trying to save to a segment record. Error: " + data['segment'].errors.join());
                }
                if (data['error']) {
                  alert("An error occurred while trying to save to a segment record. Error: " + data[error]);
                }
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to save to a segment record. Error: " + error);
          }
      });// end ajax

    } else if (cnt && imgVE.imgEntity) { //save baseline boundary
      alert("save code for new baselines from image is under construction");
    }
  },


/**
* put your comment there...
*
*/

  createStaticToolbar: function () {
    var imgVE = this;
    this.newPolyCounter = 1;
    this.newPolyIndices = [];
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    var btnLinkSegName = this.id+'LinkSeg';
    this.linkSegBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnLinkSegName +
                              '" title="Link segment to entity">&#x1F517;</button>'+
                            '<div class="toolbuttonlabel">Link segment</div>'+
                           '</div>');
    $('#'+btnLinkSegName,this.linkSegBtnDiv).unbind('click')
                               .bind('click',function(e) {
      var selectedSeg;
      for ( selectedSeg in imgVE.selectedPolygons) {
        break; //get first key only
      }
      var polygon = imgVE.getImagePolygonByName(selectedSeg);
      if (polygon) {
        if (!polygon.linkIDs) {
          DEBUG.log("event","image segment to link is "+ selectedSeg+" sending link request.");
        }else{
          DEBUG.log("event","image segment to link is "+ selectedSeg + " is already linked to syllables " + polygon.linkIDs.join());
          if (!confirm("Image segment is already linked, would you like to continue?")) {
            DEBUG.log("event","user canceled image segment linking of "+ selectedSeg);
            return;
          }
        }
        alert("Please select a syllable to link this segment to.");
        imgVE.linkSource = selectedSeg;
        $('.editContainer').trigger('linkRequest',[imgVE.id,selectedSeg]);
      }else{
        alert("Please select a segmented akṣara before pressing link.");
      }
    });

    // save polygon
    var btnSavePolysName = this.id+'saveSeg';
    this.savePolysBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnSavePolysName +
                              '" title="Save image polygon as segment">&#x1F4BE;</button>'+
                            '<div class="toolbuttonlabel">Save polygon</div>'+
                           '</div>');
    // save polygon click handler
    $('#'+btnSavePolysName,this.savePolysBtnDiv).unbind('click')
                      .bind('click',function(e) {
                        //add code to detect select polygons dirty and call savePolygons
                        var hasSelectedDirty = false, i, cnt;
                        if (imgVE.polygons && imgVE.polygons.length) {
                          cnt = imgVE.polygons.length;
                          for(i=0; i<cnt; i++) {
                            if (imgVE.polygons[i].dirty) {
                              hasSelectedDirty = true;
                              break;
                            }
                          }
                        }
                        if (hasSelectedDirty) {
                          imgVE.savePolygons();
                        } else {
                          imgVE.savePolygon();
                        }
    });

    // select polygons
    var btnSelectPolysName = this.id+'selectPolys';
    this.selectPolysBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnSelectPolysName +
                              '" title="Select image polygons">&#x1F4BE;</button>'+
                            '<div class="toolbuttonlabel">Select polygons</div>'+
                           '</div>');
    // select polygons click handler
    $('#'+btnSelectPolysName,this.selectPolysBtnDiv).unbind('click')
                               .bind('click',function(e) {
                                 imgVE.selectPolygons();
    });

    // replace polygon
    var btnReplacePolyName = this.id+'updateSeg';
    this.replacePolyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnReplacePolyName +
                              '" title="Replace select segment polygon with new one">&#x25C8;</button>'+
                            '<div class="toolbuttonlabel">Replace polygon</div>'+
                           '</div>');
    // replace polygon click handler
    $('#'+btnReplacePolyName,this.replacePolyBtnDiv).unbind('click').bind('click',function(e) {
      if (!Object.keys(imgVE.selectedPolygons).length ||
           Object.keys(imgVE.selectedPolygons).length > 1 ) {
        DEBUG.log("warn","Must select just one segment to be replaced. Aborting!");
        return;
      }
      var selectedPoly, segTag, segID,
          savedata = {}, segs = {},
          polygon;
      for (segTag in imgVE.selectedPolygons) {
        selectedPoly = imgVE.polygons[imgVE.polygonLookup[segTag]-1];
        break;
      }
      if (!selectedPoly || (imgVE.dataMgr.entities.seg[selectedPoly.label.substr(3)]).readonly) {
        DEBUG.log("warn","You don't have access to edit selected segment. Aborting!");
//        return;
      }
      polygon = imgVE.getPath();
      if (!polygon || polygon.length < 3) {
        DEBUG.log("warn","You must create a valid replacement polygon before pressing replace. Aborting!");
        return;
      }
      selectedPoly.polygon = polygon;
      selectedPoly.center = UTILITY.getCentroid(polygon);

      //build data for updateSegments of 1
      segID = selectedPoly.label.substr(3);
      segs[id] = {"boundary":'{"'+JSON.stringify(selectedPoly.polygon).replace(/\[/g,"(").replace(/\]/g,")")+'"}'};
      savedata["segs"] = segs;

//      savedata.seg.push( {seg_id:selectedPoly.label.substr(3),//label is tag i.e."seg151"
//                          seg_image_pos:'{"'+JSON.stringify(selectedPoly.polygon).replace(/\[/g,"(").replace(/\]/g,")")+'"}',
//                         });//WARNING default to logged in users group??
//      return;
      //save synch
      $.ajax({
          dataType: 'json',
          url: basepath+'/services/updateSegments.php?db='+dbName,
          data: savedata,
          asynch: true,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.success && data.entities) {
                imgVE.dataMgr.updateLocalCache(data);
                if (Object.keys(data.entities.update.seg).length) {
                  var segID, polygon, segData;
                  for(segID in data.entities.update.seg) {
                    segData = data.entities.update.seg[segID];
                    if (segData && segData.boundary) {
                      segLabel = 'seg'+segID;
                      polygon = imgVE.polygons[imgVE.polygonLookup[segLabel]-1]
                      delete polygon.dirty;
                      polygon.polygon = segData.boundary[0]; //TODO design how to manage for multiple polygon segment
                      polygon.center = UTILITY.getCentroid(polygon.polygon);
                    }
                  }
                  imgVE.selectedPolygons = {};
                  imgVE.drawImage();
                  imgVE.drawImagePolygons();
                }
                if (data['error']) {
                  alert("An error occurred while trying to replace segment boundary. Error: " + data[error]);
                }
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to replace segment boundary. Error: " + error);
          }
      });
    });

    var btnSegOrdinalName = this.id+'orderSeg';
    this.orderSegBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnSegOrdinalName +
                              '" title="Set segment order by numbering">Off</button>'+
                            '<div class="toolbuttonlabel">Number segs.</div>'+
                           '</div>');
    this.orderSegBtn = $('#'+btnSegOrdinalName,this.orderSegBtnDiv);
    imgVE.orderSegMode = "off";
    //set segment order code
    this.orderSegBtn.unbind('click').bind('click',function(e) {
          var savedata ={};
          if (imgVE.linkMode) {
            return;
          }
          imgVE.syncNextSegOrdinal();
          switch (imgVE.orderSegMode) {
            case 'off':
              //ask user if continue or restart
              if (!confirm("Press OK to start set Seg. number to "+ imgVE.ordinalSetInput.val() +
                           "(or set manually). Press Cancel to remove all numbers and set Seg. number to 1.")) {
                //restart - call service to clear seg ordinals with success update cache, set nextOrdinal = 1 and redraw
                savedata["cmd"] = "clearOrdinals";
                savedata["blnID"] = imgVE.blnEntity.id;
                // set state on
                imgVE.orderSegMode = "resetting";
                //update btn html
                imgVE.orderSegBtn.html("Resetting");
                //jqAjax synch save
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: basepath+'/services/orderSegment.php?db='+dbName,//caution dependency on context having basepath and dbName
                    data: savedata,
                    async: true,
                    success: function (data, status, xhr) {
                        var segID,polygon;
                        if (typeof data == 'object' && data.success ) {
                          //update data
                          if (data.entities){
                            imgVE.dataMgr.updateLocalCache(data,null);
                            if (data.entities.removeprop && data.entities.removeprop.seg) {
                              for (segID in data.entities.removeprop.seg) {
                                if (data.entities.removeprop.seg[segID][0] == 'ordinal' &&
                                    imgVE.polygonLookup['seg'+segID]) {
                                  polygon = imgVE.polygons[imgVE.polygonLookup['seg'+segID]-1];
                                  if (polygon.order) {
                                    delete polygon.order;
                                  }
                                }
                              }
                            }
                          }
                          // set state on
                          imgVE.orderSegMode = "on";
                          //update btn html
                          imgVE.orderSegBtn.html("On");
                          imgVE.drawImage();
                          imgVE.drawImagePolygons();
                          imgVE.ordinalSetInput.val(1);
                        }
                        if (data.editionHealth) {
                          DEBUG.log("health","***Save Lemma cmd="+cmd+"***");
                          DEBUG.log("health","Params: "+JSON.stringify(savedata));
                          DEBUG.log("health",data.editionHealth);
                        }
                        if (data.errors) {
                          alert("An error occurred while trying to resetting segment ordinals for baseline bln"+imgVE.blnEntity.id+". Error: " + data.errors.join());
                        }
                    },
                    error: function (xhr,status,error) {
                        // add record failed.
                        alert("An error occurred while trying to resetting segment ordinals for baseline bln"+imgVE.blnEntity.id+". Error: " + error);
                    }
                });
              } else {//continue - redraw
                // set state on
                imgVE.orderSegMode = "on";
                //update btn html
                $(this).html("On");
                imgVE.drawImage();
                imgVE.drawImagePolygons();
              }
              $(imgVE.editDiv).addClass('ordNumberingMode');
              break;
            case 'setting':
            case 'resetting':
            case 'on':
              // set state off
              imgVE.orderSegMode = "off";
              $(imgVE.editDiv).removeClass('ordNumberingMode');
              //update btn html
              $(this).html("Off");
              // redraw
              imgVE.drawImage();
              imgVE.drawImagePolygons();
              break;
          }
        });

    var btnDeleteSegName = this.id+'deleteSeg';
    this.deleteSegBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnDeleteSegName +
                              '" title="Delete selected segments">-</button>'+
                            '<div class="toolbuttonlabel">Delete segment</div>'+
                           '</div>');
    this.delSegBtn = $('#'+btnDeleteSegName,this.deleteSegBtnDiv);
    //delete segment code
    this.delSegBtn.unbind('click').bind('click',function(e) {
      var i,
          deldata = {seg:[]},
          polygon,
          cnt = Object.keys(imgVE.selectedPolygons).length;
      if (cnt > 1) { //delete segments to baseline
        alert("Deletion of multiple segments is not currently supported. Please select one at a time.");
      } else if (cnt == 1 && imgVE.blnEntity) { //delete segment from baseline
        var blnID = imgVE.blnEntity.id,
            segTag = Object.keys(imgVE.selectedPolygons)[0],
            segment = imgVE.dataMgr.getEntityFromGID(segTag);
        if (segment.sclIDs && segment.sclIDs.length > 0) {
         if (!confirm("You are about to remove a segment that is linked to 1 or more syllables,"+
                      " would you like to proceed?")) {
           return;
         }
        }
        deldata.seg = [segment.id];
        //delete
        $.ajax({
            dataType: 'json',
            url: basepath+'/services/deleteEntity.php?db='+dbName,
            data: 'data='+JSON.stringify(deldata),
            asynch: true,
            success: function (data, status, xhr) {
                var segID = segment.id, segLabel = segTag;
                if (typeof data == 'object' && data.success) {
                  //remove polygon
                  imgVE.removeImagePolygon(segLabel);
                  delete imgVE.selectedPolygons[segLabel];
                  if (imgVE.selectedPolygons && Object.keys(imgVE.selectedPolygons).length == 1) {
                    //enable delete button
                    imgVE.delSegBtn.removeAttr('disabled');
                  } else if (!imgVE.delSegBtn.attr('disabled')){
                    //disable delete button
                    imgVE.delSegBtn.attr('disabled','disabled');
                  }
                  //reset new indices
                  imgVE.drawImage();
                  imgVE.drawImagePolygons();
                  //remove segment from cache
                  imgVE.dataMgr.removeEntityFromCache('seg',segID);
                }
            }
        });
      }
    });
    this.delSegBtn.attr('disabled','disabled');

    if (this.blnEntity) {
      var btnAutoLinkOrdName = this.id+'AutoLinkOrd';
      this.autoLinkOrdBtnDiv = $('<div class="toolbuttondiv">' +
                              '<button class="toolbutton" id="'+btnAutoLinkOrdName +
                                '" title="Auto link numbered segments">Auto Link</button>'+
                              '<div class="toolbuttonlabel">Link by number</div>'+
                             '</div>');
      $('#'+btnAutoLinkOrdName,this.autoLinkOrdBtnDiv).unbind('click')
                                 .bind('click',function(e) {
        var blnID = imgVE.blnEntity.id, defaultMode;
        if (imgVE.ordinalSetInput.val() && imgVE.ordinalSetInput.val() >= 1) {
          mode = Object.keys(imgVE.selectedPolygons).length;
          imgVE.autoLinkOrdMode = true;
          $('.editContainer').trigger('autoLinkOrdRequest',[imgVE.id,blnID,mode]);
        } else {
          alert("Please order segments first");
        }
      });
    }

    var inputSetOrdinalName = this.id+'setOrd';
    this.ordinalSetInputDiv = $('<div class="toolnumberinputdiv">' +
                            '<input type="number" min="1" oninput='+"\"this.value=this.value.replace(/[^0-9]/g,'').replace(/^0/,'');\""+' class="toolnumberinput" id="'+inputSetOrdinalName +
                              '" title="Set segment order number" value="'+(this.maxOrdinal?this.maxOrdinal:"")+'"/>'+
                            '<div class="toolinputlabel">Seg. Number</div>'+
                           '</div>');
    this.ordinalSetInput = $('#'+inputSetOrdinalName,this.ordinalSetInputDiv);
    //set segment order
    this.ordinalSetInput.unbind('change').bind('change',function(e) {
        var savedata ={}, setOrdVal = imgVE.ordinalSetInput.val(),
            cnt = Object.keys(imgVE.selectedPolygons).length;
        if (imgVE.linkMode) {
          return;
        }
        if (cnt == 1) {
          segTag = Object.keys(imgVE.selectedPolygons)[0];
          savedata["cmd"] = "setOrdinal";
          savedata["segID"] = segTag.substring(3);
          savedata["ord"] = setOrdVal;
          //jqAjax synch save
          $.ajax({
              type: 'POST',
              dataType: 'json',
              url: basepath+'/services/orderSegment.php?db='+dbName,//caution dependency on context having basepath and dbName
              data: savedata,
              async: true,
              success: function (data, status, xhr) {
                  var segID, polygon, baseline;
                  if (typeof data == 'object' && data.success && data.entities) {
                    //update data
                    imgVE.dataMgr.updateLocalCache(data,null);
                    if (data.entities.update && data.entities.update.seg) {
                      for (segID in data.entities.update.seg) {
                        if (data.entities.update.seg[segID]['ordinal']) {
                          polygon = imgVE.polygons[imgVE.polygonLookup['seg'+segID]-1];
                          if (polygon) {
                            polygon.order = data.entities.update.seg[segID]['ordinal'];
                          }
                        }
                        //update baseline entity
                        segment = imgVE.dataMgr.getEntity('seg',segID);
                        blnIDs = segment.baselineIDs;
                        if (blnIDs.length) {
                          for (i =0; i<blnIDs.length; i++) {
                            baseline = imgVE.dataMgr.getEntity('bln',blnIDs[i]);
                            if (baseline && baseline.segIDs && baseline.segIDs.indexOf(segID) == -1) {
                              baseline.segIDs.push(segID);
                            }
                          }
                        }
                      }
                    } else if (data.entities.removeprop &&
                                data.entities.removeprop.seg) {
                      for (segID in data.entities.removeprop.seg) {
                        polygon = imgVE.polygons[imgVE.polygonLookup['seg'+segID]-1];
                        if (polygon && polygon.order) {
                          delete polygon.order;
                        }
                      }
                    }
                    imgVE.drawImage();
                    imgVE.drawImagePolygons();
                  }
                  if (data.editionHealth) {
                    DEBUG.log("health","***Save Lemma cmd="+cmd+"***");
                    DEBUG.log("health","Params: "+JSON.stringify(savedata));
                    DEBUG.log("health",data.editionHealth);
                  }
                  if (data.errors) {
                    alert("An error occurred while trying to set segment ordinal for segment "+segTag+". Error: " + data.errors.join());
                  }
              },
              error: function (xhr,status,error) {
                  // add record failed.
                  alert("An error occurred while trying to set segment ordinal for segment "+segTag+". Error: " + error);
              }
          });
        }
    });

    if (this.blnEntity && linkToSyllablePattern) {
      var btnAutoLinkPatternName = this.id+'AutoLinkPattern';
      this.autoLinkPatternBtnDiv = $('<div class="toolbuttondiv">' +
                              '<button class="toolbutton" id="'+btnAutoLinkPatternName +
                                '" title="Auto link using pattern '+linkToSyllablePattern+'">Pattern Link</button>'+
                              '<div class="toolbuttonlabel">'+linkToSyllablePattern+'</div>'+
                             '</div>');
      $('#'+btnAutoLinkPatternName,this.autoLinkPatternBtnDiv).unbind('click')
                                 .bind('click',function(e) {
        var blnID = imgVE.blnEntity.id, defaultMode;
        if (imgVE.ordinalSetInput.val() && imgVE.ordinalSetInput.val() > 1) {
          mode = 0;// 0 = auto immediate return - just need the edition
          imgVE.autoLinkPatternMode = true;
          $('.editContainer').trigger('autoLinkOrdRequest',[imgVE.id,blnID,mode]);
        } else {
          alert("Please order segments first");
        }
      });
    }

    if (this.blnEntity) {
      var btnShowSegName = this.id+'ShowSeg';
      this.showSegBtnDiv = $('<div class="toolbuttondiv">' +
                              '<button class="toolbutton" id="'+btnShowSegName +
                                '" title="Show image segments">Show</button>'+
                              '<div class="toolbuttonlabel">Segment display</div>'+
                             '</div>');
      $('#'+btnShowSegName,this.showSegBtnDiv).unbind('click')
                                 .bind('click',function(e) {
        if ( this.textContent == "Show") {
          imgVE.viewAllPolygons = true;
          this.textContent = "Numbers";
          this.title = "Show ordinal numbers";
        } else if ( this.textContent == "Numbers") {
          imgVE.showPolygonNumbers = true;
          this.textContent = "Code";
          this.title = "Show segment codes";
        } else if ( this.textContent == "Code") {
          imgVE.showPolygonNumbers = false;
          imgVE.showPolygonCodes = true;
          this.textContent = "Location";
          this.title = "Show segment locations";
        } else if ( this.textContent == "Location") {
          imgVE.showPolygonCodes = false;
          imgVE.showPolygonLocs = true;
          this.textContent = "Hide";
          this.title = "Hide image segments";
        } else {
          imgVE.viewAllPolygons = false;
          imgVE.showPolygonNumbers = false;
          imgVE.showPolygonCodes = false;
          imgVE.showPolygonLocs = false;
          this.textContent = "Show";
          this.title = "Show image segments";
        }
        imgVE.drawImage();
        imgVE.drawImagePolygons();
      });
    }

    var btnShowPropsName = this.id+'showprops';
    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
                              '" title="Show/Hide property panel">&#x25E8;</button>'+
                            '<div class="toolbuttonlabel">Properties</div>'+
                           '</div>');
    this.propertyBtn = $('#'+btnShowPropsName,this.propertyBtnDiv);
    this.propertyBtn.unbind('click').bind('click',function(e) {
                                           imgVE.showProperties(!$(this).hasClass("showUI"));
                                         });

    var btnImgInvertName = this.id+'ImgInvert';
    this.imgInvertBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnImgInvertName +
                              '" title="Invert image colors">Invert</button>'+
                            '<div class="toolbuttonlabel">Invert image</div>'+
                           '</div>');
    $('#'+btnImgInvertName,this.imgInvertBtnDiv).unbind('click')
                               .bind('click',function () {imgVE.invert()});

    var btnImgStretchName = this.id+'ImgStretch';
    this.imgStretchBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnImgStretchName +
                              '" title="Revert to original image">+</button>'+
                            '<div class="toolbuttonlabel">Contrast</div>'+
                           '</div>');
    $('#'+btnImgStretchName,this.imgStretchBtnDiv).unbind('click')
                               .bind('click',function () {imgVE.stretch()});

    var btnImgReduceName = this.id+'ImgReduce';
    this.imgReduceBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnImgReduceName +
                              '" title="Reduce image color value (fixed precentage)">&#x2012;</button>'+
                            '<div class="toolbuttonlabel">Contrast</div>'+
                           '</div>');
    $('#'+btnImgReduceName,this.imgReduceBtnDiv).unbind('click')
                               .bind('click',function () {imgVE.reduce()});

    var btnImgEmbossName = this.id+'ImgEmboss';
    this.imgEmbossBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnImgEmbossName +
                              '" title="Convert color changes to embossing">Emboss</button>'+
                            '<div class="toolbuttonlabel">Emboss image</div>'+
                           '</div>');
    $('#'+btnImgEmbossName,this.imgEmbossBtnDiv).unbind('click')
                               .bind('click',function () {imgVE.emboss()});

    var btnImgNormalName = this.id+'ImgNormal';
    this.imgNormalBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnImgNormalName +
                              '" title="Revert to original image">Normal</button>'+
                            '<div class="toolbuttonlabel">Reset image</div>'+
                           '</div>');
    $('#'+btnImgNormalName,this.imgNormalBtnDiv).unbind('click')
                               .bind('click', function () {
                                                imgVE.drawImage();
                                                imgVE.clearImageCommands();
                                              });
    var btnImgFadeName = this.id+'ImgFade';
    this.imgFadeBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnImgFadeName +
                              '" title="Fade selected colors on entire image">Fade</button>'+
                            '<div class="toolbuttonlabel">Fade image</div>'+
                           '</div>');
    $('#'+btnImgFadeName,this.imgFadeBtnDiv).unbind('click')
                               .bind('click',function () {imgVE.fade()});
    if (this.blnEntity) {
      this.viewToolbar.append(this.showSegBtnDiv);
    }
    this.viewToolbar.append(this.propertyBtnDiv);
    this.viewToolbar.append(this.imgInvertBtnDiv)
                .append(this.imgStretchBtnDiv)
                .append(this.imgReduceBtnDiv)
//                .append(this.imgEmbossBtnDiv)
                .append(this.imgFadeBtnDiv)
                .append(this.imgNormalBtnDiv);
    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    if (this.blnEntity) {
      this.editToolbar.append(this.linkSegBtnDiv)
                  .append(this.savePolysBtnDiv)
                  .append(this.replacePolyBtnDiv)
                  .append(this.selectPolysBtnDiv)
                  .append(this.deleteSegBtnDiv) 
                  .append(this.orderSegBtnDiv)
                  .append(this.autoLinkOrdBtnDiv)
                  .append(this.ordinalSetInputDiv);
      if (this.autoLinkPatternBtnDiv) {
        this.editToolbar.append(this.autoLinkPatternBtnDiv);
      }
    }
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
  },


/**
* put your comment there...
*
*/

  initViewport: function () {
    this.navAspectRatio = this.imgCanvas.width/this.imgCanvas.height;
    var vpWidth  = Math.floor(this.navCanvas.width * this.zoomFactor/100),
        vpHeight = Math.floor(vpWidth/this.navAspectRatio);
    if (vpHeight > this.navCanvas.height) {
      vpHeight = this.navCanvas.height;
      vpWidth = Math.floor(vpHeight * this.navAspectRatio);
    }
    this.vpMaxLoc = { x: this.navCanvas.width - vpWidth, y: this.navCanvas.height - vpHeight };
//    this.vpLoc = { x: Math.min(this.vwOffset.x * this.zoomFactor, this.vpMaxLoc.x) || 0,
    this.vpLoc = { x:  this.vpMaxLoc.x,
      y: Math.min( this.vwOffset.y * this.zoomFactor, this.vpMaxLoc.y) || 0 };
    this.vpSize = { width: vpWidth || 50, height: vpHeight || 50 };
    this.vpLastLoc =  { x: 0, y: 0 };
  },


/**
* turn on or off the property pane
*
* @param boolean bShow Show property pane
*/

showProperties: function (bShow) {
  var imgVE = this;
  if (imgVE.propMgr &&
      typeof imgVE.propMgr.displayProperties == 'function'){
        imgVE.propMgr.displayProperties(bShow);
    if (imgVE.propertyBtn.hasClass("showUI") && !bShow) {
      imgVE.propertyBtn.removeClass("showUI");
    } else if (!imgVE.propertyBtn.hasClass("showUI") && bShow) {
      imgVE.propertyBtn.addClass("showUI");
    }
  }
},


/**
* put your comment there...
*
* @param polygons
*/

  setImagePolygons: function (polygons) {
    //todo add code to validate the polygon array
    this.polygons = polygons;
  },


/**
* put your comment there...
*
* @param polygons
*/

  getImagePolygons: function (polygons) {
    //todo add code to validate the polygon array
    return this.polygons;
  },


/**
* put your comment there...
*
* @param index
*/

  getImagePolygonAt: function (index) {
    if (index == 0 || isNaN(index) || index > this.polygons.length) return null;
    return this.polygons[index-1];
  },


/**
* put your comment there...
*
* @param label
*/

  getImagePolygonByName: function (label) {
    var index = this.polygonLookup[label];
    return this.polygons[index-1];
  },


/**
* put your comment there...
*
* @param label
*/

  getIndexbyPolygonName: function (label) {
    var imgVE = this, index = null;
    if (this.polygonLookup[label]) {
      index = this.polygonLookup[label];
    } else if (label.match(/seg/)) { //check mapped segments
      segment = this.dataMgr.getEntityFromGID(label);
      if (segment && segment.mappedSegIDs && segment.mappedSegIDs.length) {
        $.each(segment.mappedSegIDs, function(i,val) {
          if (!index && imgVE.polygonLookup['seg'+val]) {
            index = imgVE.polygonLookup['seg'+val];
          }
        });
      }
    }
    return index;
  },


/**
* put your comment there...
*
* @param polygon
* @param label
* @param visible
* @param linkIDs
* @param center
*/

  addImagePolygon: function (polygon,label,visible,linkIDs,center,ordinal,code,loc) {
    //todo add code to validate the polygon
    var clr = "green";
    if (!linkIDs){
      clr = "red";
    }
    this.polygons.push({polygon:polygon,
                        center: center?center:UTILITY.getCentroid(polygon),
                        order: ordinal?ordinal:null,
                        code: code?code:null,
                        loc: loc?loc:null,
                        color:clr,
                        width:1, //polygon width
                        label:label,
                        hidden:(visible?false:true),
                        linkIDs:linkIDs});
    this.polygonLookup[label] = this.polygons.length;
    return this.polygons.length;
  },


/**
* put your comment there...
*
* @param label
*/

  removeImagePolygon: function (label) {
    //todo add code to validate the polygon
    var i = this.polygonLookup[label],pIndex = i-1, polygon, segTag;
    if (i && this.polygons.length >= i){
      for (;i < this.polygons.length; i++) {
        polygon = this.polygons[i];
        segTag = polygon.label;
        this.polygonLookup[segTag] = this.polygonLookup[segTag]-1;
      }
      delete this.polygonLookup[label];
      this.polygons.splice(pIndex,1);
    }
  },


/**
* put your comment there...
*
* @returns {Array}
*/

  getSelectedPolygonLabels: function () {
    var labels = [],i;
    for(i in this.selectedPolygons) {
      labels.push(i);
    }
    return labels;
  },


/**
* put your comment there...
*
* @param label
*/

  selectPolygonByName: function (label) {
    var imgVE = this;
    if (this.polygonLookup[label]) {
      this.selectedPolygons[label] = 1;
    } else if (label.match(/seg/)) { //check mapped segments
      segment = this.dataMgr.getEntityFromGID(label);
      if (segment && segment.mappedSegIDs && segment.mappedSegIDs.length) {
        $.each(segment.mappedSegIDs, function(i,val) {
          if (imgVE.polygonLookup['seg'+val]) {
            imgVE.selectedPolygons['seg'+val] = 1;
          }
        });
      }
    }
  },


/**
* put your comment there...
*
*/

  unselectAllPolygons: function () {
    this.selectedPolygons ={};
  },


/**
* put your comment there...
*
*/

selectAllPolygons: function () {
  if (Object.keys(this.polygonLookup).length) {
    this.selectedPolygons ={};
    for (key in this.polygonLookup) {
      this.selectedPolygons[key] = 1;
    }
  }
},


/**
* put your comment there...
*
*/

moveSelectPolygons: function (dx, dy) {
  if (Object.keys(this.selectedPolygons).length) {
    var polygon, x, y;
    if (!dx) {
      dx = 0;
    }
    if (!dy) {
      dy = 0;
    }
    if (dx || dy) {
      for (key in this.selectedPolygons) {
        polygon = this.polygons[this.polygonLookup[key]-1];
        polygon.polygon = UTILITY.getTranslatedPoly(polygon.polygon,dx,dy);
        polygon.center[0] += dx;
        polygon.center[1] += dy;
        polygon.dirty = true;
        this.polygons[this.polygonLookup[key]-1] = polygon;
      }
    }
  }
  this.drawImage();
  this.drawImagePolygons();
},


/**
* put your comment there...
*
*/

scaleSelectPolygons: function (b) {
  var cntPoly = Object.keys(this.selectedPolygons).length;
  if (cntPoly) {
    var polygon, xCtr = 0, yCtr = 0, key, i, cntL, orgPoly, sfactor;
    if (!b || b == 1) {
      return;
    }
    for (key in this.selectedPolygons) {
      polygon = this.polygons[this.polygonLookup[key]-1];
      xCtr += polygon.center[0];
      yCtr += polygon.center[1];
    }
    xCtr = Math.round(xCtr/cntPoly);
    yCtr = Math.round(yCtr/cntPoly);
    for (key in this.selectedPolygons) {
      i = this.polygonLookup[key]-1;
      sfactor = b;
      cntL = 5;
      polygon = Object.assign(this.polygons[i].polygon);
      polygon = UTILITY.getTranslatedPoly(polygon,-xCtr,-yCtr,sfactor);
      polygon = UTILITY.getTranslatedPoly(polygon,xCtr,yCtr);
      orgPoly = this.polygons[i].polygon;
      // try up to cntL times to have scale change polygon
      while (cntL && orgPoly[0][0] == polygon[0][0] &&
                     orgPoly[0][1] == polygon[0][1] &&
                     orgPoly[2][0] == polygon[2][0] &&
                     orgPoly[2][1] == polygon[2][1]) {
        if (sfactor > 1) {
          sfactor = sfactor * 1.05;
        } else {
          sfactor = sfactor * .95
        }
        polygon = UTILITY.getTranslatedPoly(polygon,-xCtr,-yCtr,sfactor);
        polygon = UTILITY.getTranslatedPoly(polygon,xCtr,yCtr);
        cntL--;
      }
      this.polygons[i].dirty = true;
      this.polygons[i].center = UTILITY.getCentroid(polygon);
      this.polygons[i].polygon = polygon;
    }
    this.drawImage();
    this.drawImagePolygons();
  }
},


/**
* put your comment there...
*
*/

selectPolygons: function () {
  if (!this.path || this.path.length < 3) {
    this.selectAllPolygons();
  } else {
    var bRect = UTILITY.getBoundingRect(this.path),
        polygon, i, x, y,
        minx = bRect[0][0],
        maxx = bRect[1][0],
        miny = bRect[1][1],
        maxy = bRect[2][1];
    this.selectedPolygons ={};
    for (i in this.polygons) {
      polygon = this.polygons[i];
      x = polygon.center[0];
      y = polygon.center[1];
      if ( x > minx && x < maxx && y > miny && y < maxy) {
        this.selectedPolygons[polygon.label] = 1;
      }
    }
    this.path = null;
  }
  this.imgCanvas.focus();
  this.drawImage();
  this.drawImagePolygons();
},


/**
* put your comment there...
*
* @param index
* @param hilite
*/

  setImagePolygonHilite: function(index,hilite){
    if (index === null || index == 0 || isNaN(index) || index > this.polygons.length) return;
    if (hilite || hilite === false) {
      this.polygons[index-1].hilite = hilite;
    }
  },


/**
* put your comment there...
*
* @param index
* @param color
* @param width
* @param show
*/

  setImagePolygonDisplay: function (index,color,width,show) {
    if (index == 0 || isNaN(index) || index > this.polygons.length) return;
    if (color) {
      this.polygons[index-1].color = (color?color:"green");
    }
    if (width) {
      this.polygons[index-1].width = (width?width:1);
    }
    if (show || show === false) {
      this.polygons[index-1].hidden = !show;
    }
  },


/**
* put your comment there...
*
*/

  getImageSrc: function () {
    return this.image.src;
  },


/**
* put your comment there...
*
* @param src
*/

  setImageSrc: function (src) {//note that this will trigger onload which calls init.
    this.image.src = src;
  },


/**
* put your comment there...
*
*/

  scaleViewport: function () {
    var width  = this.navCanvas.width * this.zoomFactor/100,
        height = Math.floor(width/this.navAspectRatio);
//        height = this.navCanvas.height * this.zoomFactor/100;
//    if (height > this.navCanvas.height) {
//      height = this.navCanvas.height;
//      width = Math.floor(height * this.navAspectRatio);
//    }
    this.vpMaxLoc = { x: this.navCanvas.width - width, y: this.navCanvas.height - height };
    this.vpSize = { width: width || 50, height: height || 50 };
  },


/**
* put your comment there...
*
* @param mouse
* @param offset
*/

  moveViewport: function(mouse, offset) {
    this.vpLoc.x = Math.max(0, Math.min(mouse[0] - offset[0], this.vpMaxLoc.x));
    this.vpLoc.y = Math.max(0, Math.min(mouse[1] - offset[1], this.vpMaxLoc.y));
    this.vpLastLoc.x = this.vpLoc.x;
    this.vpLastLoc.y = this.vpLoc.y;
  },


/**
* put your comment there...
*
* @param deltaX
* @param deltaY
*/

  moveViewportRelative: function(deltaX, deltaY) {
    this.vpLoc.x = Math.max(0, Math.min(this.vpLastLoc.x + deltaX, this.vpMaxLoc.x));
    this.vpLoc.y = Math.max(0, Math.min(this.vpLastLoc.y + deltaY, this.vpMaxLoc.y));
    this.vpLastLoc.x = this.vpLoc.x;
    this.vpLastLoc.y = this.vpLoc.y;
  },

/**
* put your comment there...
*
*/

  adjustViewportForScroll: function() {
    var imgVE = this, navDivViewBottom = imgVE.navDIV.offsetHeight + imgVE.navDIV.scrollTop,
        vpBottom = imgVE.vpSize.height + imgVE.vpLoc.y;
    if (vpBottom > navDivViewBottom) {
      //vpBottom below navDiv bottom move vp up by relative amount
      imgVE.moveViewportRelative(0, -(vpBottom - navDivViewBottom + 2)); // 2 pixel needed to clear border
      imgVE.draw();
    } else if (imgVE.vpLoc.y <  imgVE.navDIV.scrollTop) {
      //vpTop above navDiv scrollTop move vpLoc down by relative amount
      imgVE.moveViewportRelative(0,(imgVE.navDIV.scrollTop - imgVE.vpLoc.y)); 
      imgVE.draw();
     }
  },

/**
* put your comment there...
*
*/

  adjustScrollForViewport: function() {
    var imgVE = this, navDivViewBottom = imgVE.navDIV.offsetHeight + imgVE.navDIV.scrollTop,
        vpBottom = imgVE.vpSize.height + imgVE.vpLoc.y;
        if (vpBottom > navDivViewBottom) {
          //vpBottom below navDiv bottom move vp up by relative amount
          imgVE.navDIV.scrollTop = Math.min(imgVE.navDIV.scrollTop + (vpBottom - navDivViewBottom + 2),imgVE.navDIV.scrollTopMax); // 2 pixel needed to clear border
        } else if (imgVE.vpLoc.y <  imgVE.navDIV.scrollTop) {
          //vpTop above navDiv scrollTop move vpLoc to scrollTop
          imgVE.navDIV.scrollTop = Math.max(imgVE.vpLoc.y - 2,0); 
        }
      },


/**
* put your comment there...
*
* @param deltaX
* @param deltaY
*/

  moveNavPanelRelative: function(deltaX, deltaY) {
    var left = this.navPositionLeft, minLeft = 4, //remember that highlighted border witdh
        top = this.navPositionTop, minTop = 4,
        width =  parseInt(this.navDIV.style.width),
        maxLeft = Math.max(minLeft,(this.imgCanvas.width - width)),
        height = parseInt(this.navDIV.style.height),
        maxTop = Math.max(minTop,(this.imgCanvas.height - height));

    this.navPositionLeft = Math.max(minLeft, Math.min(left + deltaX, maxLeft));
    this.navPositionTop = Math.max(minTop, Math.min(top + deltaY, maxTop));
    this.navDIV.style.left = this.navPositionLeft + "px";
    this.navDIV.style.top = this.navPositionTop + "px";
  },


/**
* put your comment there...
*
* @param posY
*/

  moveViewportToImagePosY: function(posY) {
    this.vpLoc.y = Math.max(0, Math.min(posY * this.navCanvas.height / this.image.height, this.vpMaxLoc.y));
    this.vpLastLoc.y = this.vpLoc.y;
  },


/**
* put your comment there...
*
*/

  eraseNavPanel: function() {
    this.navContext.clearRect(0,0,this.navCanvas.width,this.navCanvas.height);
  },


/**
* put your comment there...
*
* @param refViewCorner
*/

  findNearestPoly: function (refType,visibleOnly) {
    var imgVE = this,
        x = imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width,
        y = imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height,
        xmax = (imgVE.vpSize.width + imgVE.vpLoc.x) * imgVE.image.width / imgVE.navCanvas.width,
        ymax = (imgVE.vpSize.height + imgVE.vpLoc.y) * imgVE.image.height / imgVE.navCanvas.height,
        xpos,ypos,candidatePoly = null, minDist = 1000, i, polygon, dist;
    switch (refType) {
      case "bottomleft":
          xpos = x;
          ypos = ymax;
        break;
      case "bottomright":
          xpos = xmax;
          ypos = ymax;
        break;
      case "topline":
          xpos = x;
          ypos = y;
        break;
      case "topleft":
          xpos = x;
          ypos = y;
        break;
      case "topright":
      default:
          xpos = xmax;
          ypos = y;
        break;
    }
    //foreach polygon
    for (i=0; i < this.polygons.length; i++) {
      polygon = this.polygons[i];
      xctr = polygon.center[0];
      yctr = polygon.center[1];
      //if visible
      if ( !visibleOnly || !isNaN(xctr) && !isNaN(yctr) && x <= xctr && xctr <= xmax&& y <= yctr && yctr <= ymax) {
        //find distance
        if (refType == "topline") {//use absolute y delta
          dist = Math.sqrt((xctr - xpos)*(xctr - xpos)+(yctr - ypos)*(yctr - ypos));
        } else {
          dist = Math.abs(yctr - ypos);
        }
        //if distance is min the save as candidate
        if (dist < minDist) {
          candidatePoly = polygon;
          minDist = dist;
        }
      }
    }
    DEBUG.log("data","Found visible Segment #"+(candidatePoly?candidatePoly.label:"none found")+" in "+refType + visibleOnly?" only visible":"");
    return candidatePoly;
  },


/**
* put your comment there...
*
* @param ctx
* @param x
* @param y
* @param rx
* @param ry
* @param rw
* @param rh
*/

  pointInRect: function (ctx, x, y, rx, ry, rw, rh) {
    ctx.beginPath();
    ctx.rect( rx, ry, rw, rh);
    return ctx.isPointInPath(x, y);
  },


/**
* put your comment there...
*
* @param ctx
* @param x
* @param y
* @param path
*/

  pointInPath: function (ctx, x, y,path) {
    ctx.beginPath();
    ctx.moveTo(path[0][0],path[0][1]);
    for (i=1; i < path.length; i++) {
      ctx.lineTo(path[i][0],path[i][1]);
    }
    ctx.closePath();
    return ctx.isPointInPath(x, y);
  },


/**
* put your comment there...
*
* @param x
* @param y
*
* @returns {Array}
*/

  hitTestPolygons: function ( x, y) {
    var i,polygonObj, hitPolyIndices = [],
        cnt = this.polygons.length;
    for (i=0; i < cnt; i++) {
      polygonObj = this.polygons[i];
      if (this.pointInPath(this.imgContext,x,y,polygonObj.polygon)) {
        hitPolyIndices.push(i+1);// indices are store from 1 array is zero based
      }
    }
    return hitPolyIndices;
  },


/**
* put your comment there...
*
* @param event
* @param canvas
*
* @returns {Array}
*/

  eventToCanvas: function(event,canvas) {
    var bbox = canvas.getBoundingClientRect();
    return [Math.round(event.clientX - bbox.left * (canvas.width  / bbox.width)),
            Math.round(event.clientY - bbox.top  * (canvas.height / bbox.height))];
  },


/**
* put your comment there...
*
* @param object e System event object
*/

  handleWheel: function (e) {
    DEBUG.log("gen", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE handleWheel "+this.id+" with delta "+e.deltaX+","+e.deltaY);
    var e = window.event || e; // old IE
    var delta = Math.max(-1, Math.min(1, (e.deltaY || e.wheelDelta || -e.detail.y)));//detect direction
    if (isNaN(delta)) {
      e.preventDefault();
      return;
    }
    var xScaleFactor = e.layerX/this.imgCanvas.width,
        yScaleFactor = e.layerY/this.imgCanvas.height,
    xNavAllign = xScaleFactor * this.vpSize.width + this.vpLoc.x,
    yNavAllign = yScaleFactor * this.vpSize.height + this.vpLoc.y;
    this.zoomFactor = Math.max(this.zoomFactorRange.min,
                              Math.min(this.zoomFactorRange.max,(this.zoomFactor + ( this.zoomFactorRange.inc * delta))));
    this.scaleViewport();
    var xNew = Math.round(xNavAllign - xScaleFactor * this.vpSize.width),
        yNew = Math.round(yNavAllign - yScaleFactor * this.vpSize.height);
    this.eraseNavPanel();
    this.moveViewport([xNew,yNew], [0,0]);
    this.adjustScrollForViewport();
    this.draw();
    DEBUG.log("gen", "delta="+delta+
                      "xScaleFactor="+xScaleFactor+
                      "yScaleFactor="+yScaleFactor+
                      "xNavAllign="+xNavAllign+
                      "yNavAllign="+yNavAllign+
                      "xNew="+xNew+
                      "yNew="+yNew+
                      "zoomFactor="+this.zoomFactor);
    e.preventDefault();//stop window scroll, for dual purpose could use CTRL key to disallow default
  },


/**
* put your comment there...
*
* @param direction
*/

  zoomCenter: function (direction) {
    var xNavAllign = this.vpSize.width/2 + this.vpLoc.x,
        yNavAllign = this.vpSize.height/2 + this.vpLoc.y;
    this.zoomFactor = Math.max(this.zoomFactorRange.min,
                              Math.min(this.zoomFactorRange.max,this.zoomFactor + ( this.zoomFactorRange.inc * direction)));
    this.scaleViewport();
    var xNew = Math.round(xNavAllign - this.vpSize.width/2),
        yNew = Math.round(yNavAllign - this.vpSize.height/2);
    this.eraseNavPanel();
    this.moveViewport([xNew,yNew], [0,0]);
    this.adjustScrollForViewport();
    this.draw();
//    e.preventDefault();//stop window scroll, for dual purpose could use CTRL key to disallow default
  },


/**
* put your comment there...
*
*/

  addEventHandlers: function() {
    var imgVE = this;
  // navDiv events
    //mousedown
    $(imgVE.navDIV).unbind("mousedown touchstart").bind("mousedown touchstart", function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
      var pt = imgVE.eventToCanvas(e,imgVE.navCanvas),
      offset = null;
      e.preventDefault();

      if (imgVE.pointInRect(imgVE.navContext,
                                      pt[0], pt[1],
                                      imgVE.vpLoc.x,
                                      imgVE.vpLoc.y,
                                      imgVE.vpSize.width,
                                      imgVE.vpSize.height)) {// start viewport drag
        startPoint = [pt[0] - imgVE.vpLoc.x,
                      pt[1] - imgVE.vpLoc.y];

        $(imgVE.navCanvas).bind("mousemove touchmove", function(e) {// move viewport
          DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
          imgVE.eraseNavPanel();

          imgVE.moveViewport(imgVE.eventToCanvas(e,imgVE.navCanvas), startPoint);
          imgVE.adjustScrollForViewport();
          imgVE.draw();
        });

        $(imgVE.navCanvas).bind("mouseup touchend", function(e) {//end drag button up
          DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
          $(imgVE.navCanvas).unbind("mousemove touchmove");
          $(imgVE.navCanvas).unbind("mouseup touchend");
          if ($('body').hasClass('synchScroll')) {
            poly = imgVE.findNearestPoly('topleft',true);
            if (!poly) {
              poly = imgVE.findNearestPoly('topright',true);
            }
            if (!poly) {
              poly = imgVE.findNearestPoly('topline',false);
            }
            if (poly) {
              $('.editContainer').trigger('synchronize',[imgVE.id,poly.label,0]);
            }
          }
        });

        imgVE.navCanvas.onmouseout = function(e) {//end drag mouse out of navDiv
          DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
          //imgVE.navCanvas.onmousemove = undefined;
          //imgVE.navCanvas.onmouseup = undefined;
        };

      }
    });
    $(imgVE.navDIV).unbind("scroll").bind("scroll", function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
      DEBUG.log("event", "imageVE navDIV scroll  top = "+imgVE.navDIV.scrollTop +" vpLoc.y = "+imgVE.vpLoc.y);
      imgVE.adjustViewportForScroll();
      imgVE.draw();
      if ($('body').hasClass('synchScroll')) {
        poly = imgVE.findNearestPoly('topleft',true);
        if (!poly) {
          poly = imgVE.findNearestPoly('topright',true);
        }
        if (!poly) {
          poly = imgVE.findNearestPoly('topline',false);
        }
        if (poly) {
          $('.editContainer').trigger('synchronize',[imgVE.id,poly.label,0]);
        }
      }
    });
    imgVE.navCanvas.onwheel = function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
      imgVE.handleWheel.call(imgVE,e); //delegate passing imgVE as context
    };

    imgVE.navCanvas.onmousewheel = function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE nav "+imgVE.id);
      imgVE.handleWheel.call(imgVE,e); //delegate passing imgVE as context
    };

    $('#zoomOut',imgVE.zoomDIV).unbind('click').bind('click', function(e) {
        setTimeout(function() {imgVE.zoomCenter.call(imgVE,1);},50);
      });

    $('#zoomIn',imgVE.zoomDIV).unbind('click').bind('click', function(e) {
        setTimeout(function() {imgVE.zoomCenter.call(imgVE,-1);},50);
      });

  //image canvas events
    // wheel zoom
    imgVE.imgCanvas.onwheel = function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgVE.id);
      imgVE.handleWheel.call(imgVE,e); //delegate passing imgVE as context
    }
    imgVE.imgCanvas.onmousewheel = function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgVE.id);
      imgVE.handleWheel.call(imgVE,e); //delegate passing imgVE as context
    };
    imgVE.imgCanvas.onkeydown = function(e) {
      var keyCode = (e.keyCode || e.which),
          key = e.key?e.key :
                (e.which == null?String.fromCharCode(e.keyCode):
                ((e.which != 0 )?String.fromCharCode(e.which):null)),
          hasSelected = Object.keys(imgVE.selectedPolygons).length > 0;
      if (keyCode > 36 && keyCode <41) {
        switch (e.keyCode || e.which) {
          case 38://'Up':
            if (e.ctrlKey || e.metaKey) {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(0,-5)
              } else {
                imgVE.moveNavPanelRelative(0,-5);
              }
            } else {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(0,-1)
              } else {
                imgVE.moveViewportRelative(0,-1);
              }
            }
            break;
          case 40://'Down':
            if (e.ctrlKey || e.metaKey) {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(0,5)
              } else {
                imgVE.moveNavPanelRelative(0,5);
              }
            } else {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(0,1)
              } else {
                imgVE.moveViewportRelative(0,1);
              }
            }
            break;
          case 37://"Left":
            if (e.ctrlKey || e.metaKey) {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(-3,0)
              } else {
                imgVE.moveNavPanelRelative(-3,0);
              }
            } else {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(-1,0)
              } else {
                imgVE.moveViewportRelative(-1,0);
              }
            }
            break;
          case 39://"Right":
            if (e.ctrlKey || e.metaKey) {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(3,0)
              } else {
                imgVE.moveNavPanelRelative(3,0);
              }
            } else {
              if (hasSelected && e.shiftKey) {
                imgVE.moveSelectPolygons(1,0)
              } else {
                imgVE.moveViewportRelative(1,0);
              }
            }
            break;
        }
        imgVE.draw();
        e.stopImmediatePropagation();
        return false;
      } else if (keyCode == 77 && (e.ctrlKey || e.metaKey)) { //ctrl + m
        imgVE.navDIV.hidden = !imgVE.navDIV.hidden; //toggle navPanel
        e.stopImmediatePropagation();
        return false;
      } else if (key == '+' || key == '=') {
        if (hasSelected ) { // && e.altKey && (e.ctrlKey || e.metaKey)) {
          imgVE.scaleSelectPolygons(1.01)
          e.stopImmediatePropagation();
          return false;
        } else {
          imgVE.zoomCenter.call(imgVE,-1);
        }
      } else if (key == '-' || key == '−'){
        if (hasSelected) { // && e.altKey && (e.ctrlKey || e.metaKey)) {
          imgVE.scaleSelectPolygons(0.99)
          e.stopImmediatePropagation();
          return false;
        } else {
          imgVE.zoomCenter.call(imgVE,1);
        }
      } else if (key == 's' && (e.ctrlKey || e.metaKey)) {
        imgVE.savePolygon();
        e.stopImmediatePropagation();
        return false;
      } else if (key == 'T' && (e.ctrlKey || e.metaKey)) {
        imgVE.layoutMgr.toggleSideBar();
        e.stopImmediatePropagation();
        return false;
      }
    };

    imgVE.imgCanvas.onkeypress = function(e) {
      var key = e.which == null?String.fromCharCode(e.keyCode):
                (e.which != 0 )?String.fromCharCode(e.which):null,
          hasSelected = Object.keys(imgVE.selectedPolygons).length > 0;
//      alert('-keypress img in imageVE '+key);
/*      if (key == '+' || key == '=') {
        if (hasSelected ) { // && e.altKey && (e.ctrlKey || e.metaKey)) {
          imgVE.scaleSelectPolygons(1.01)
          e.stopImmediatePropagation();
          return false;
        } else {
          imgVE.zoomCenter.call(imgVE,-1);
        }
      } else if (key == '-'){
        if (hasSelected) { // && e.altKey && (e.ctrlKey || e.metaKey)) {
          imgVE.scaleSelectPolygons(0.99)
          e.stopImmediatePropagation();
          return false;
        } else {
          imgVE.zoomCenter.call(imgVE,1);
        }
       } else if (key == 's' && (e.ctrlKey || e.metaKey)) {
        imgVE.savePolygon();
        e.stopImmediatePropagation();
        return false;
      }*/
    };

/*    // keydown
    imgVE.imgEditContainer.onkeydown = function(e) {
      if (e.ctrl) {
        //set cursor to grab
        imgVE.imgCanvas.style.cursor = 'grab';
      }
    };

    // keyup
    imgVE.imgEditContainer.onkeyup = function(e) {
      if (e.ctrl) {
        //set cursor to grab
        imgVE.imgCanvas.style.cursor = 'crosshair';
      }
    };
*/
    imgVE.segMode = "done";
    imgVE.imgCanvas.ondblclick = function (e){
      if (imgVE.orderSegMode == "off") {
        var pt = imgVE.eventToCanvas(e, imgVE.imgCanvas);
        //adjust point to be image coordinates
        var x = (imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width + imgVE.vpSize.width/imgVE.navCanvas.width*imgVE.image.width * pt[0]/imgVE.imgCanvas.width),
            y = (imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height + imgVE.vpSize.height/imgVE.navCanvas.height*imgVE.image.height * pt[1]/imgVE.imgCanvas.height),
            i,index,gid,bBox, firstPoly,firstPolyVerts,selectedPoly;
        //hittest for target polygons
        var hitPolyIndices = imgVE.hitTestPolygons(x,y);
        //unselect existing if no ctrl key pressed
        if (!(e.ctrlKey || e.metaKey)) {
          imgVE.selectedPolygons = {};
          // save shift click rectagular size of first hitTest polygon so works for only dblclick polygon
          index = hitPolyIndices[0];
          if (index) {
            bBox = UTILITY.getBoundingRect(imgVE.polygons[index -1].polygon);
            imgVE.aPolyW = bBox[2][0] - bBox[0][0];
            imgVE.aPolyH = bBox[2][1] - bBox[0][1];
          }
        }
        //add indices to selected array
        for (i=0; i < hitPolyIndices.length; i++) {
          index = hitPolyIndices[i];
          gid = imgVE.polygons[index -1].label;
          imgVE.propMgr.showVE(null,gid);
          imgVE.selectedPolygons[gid] = 1;
        }

        if (imgVE.selectedPolygons && Object.keys(imgVE.selectedPolygons).length > 0) {
          if ( Object.keys(imgVE.selectedPolygons).length == 1) {
            //enable delete button
            imgVE.delSegBtn.removeAttr('disabled');
            selectedPoly = imgVE.polygons[imgVE.polygonLookup[Object.keys(imgVE.selectedPolygons)[0]]-1];
            if (selectedPoly.order){
              imgVE.ordinalSetInput.val(selectedPoly.order);
            }
          } else {//check for duplicate overlaying polygons
            selectedTags = Object.keys(imgVE.selectedPolygons);
            firstPoly = imgVE.polygons[imgVE.polygonLookup[selectedTags[0]]-1];
            firstPolyVerts = firstPoly.polygon.join();
            for (i = 1; i < selectedTags.length; i++) {
              testPoly = imgVE.polygons[imgVE.polygonLookup[selectedTags[i]]-1];
              if (testPoly.polygon.join() == firstPolyVerts) {
                delete imgVE.selectedPolygons[selectedTags[i]];
              }
            }
            if (Object.keys(imgVE.selectedPolygons).length == 1) {
              //enable delete button
              imgVE.delSegBtn.removeAttr('disabled');
            } else if (!imgVE.delSegBtn.attr('disabled') && Object.keys(imgVE.selectedPolygons).length > 1){
              //disable delete button
              imgVE.delSegBtn.attr('disabled','disabled');
            }
          }
        }
        //redraw
        imgVE.drawImage();
        imgVE.drawImagePolygons();
        if (imgVE.linkMode) {
          $('.editContainer').trigger('linkResponse',[imgVE.id,imgVE.getSelectedPolygonLabels()[0]]);
        } else {
          $('.editContainer').trigger('updateselection',[imgVE.id,imgVE.getSelectedPolygonLabels(),imgVE.getSelectedPolygonLabels()[0]]);
        }
      }
    };

    imgVE.imgCanvas.onclick = function (e){
      var pt = imgVE.eventToCanvas(e, imgVE.imgCanvas);
      //adjust point to be image coordinates
      var x = (imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width + imgVE.vpSize.width/imgVE.navCanvas.width*imgVE.image.width * pt[0]/imgVE.imgCanvas.width),
          y = (imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height + imgVE.vpSize.height/imgVE.navCanvas.height*imgVE.image.height * pt[1]/imgVE.imgCanvas.height),
          i,index;
      if (imgVE.focusMode != 'focused') {
        imgVE.focusMode = 'focused';
        imgVE.imgCanvas.focus();
        imgVE.layoutMgr.curLayout.trigger('focusin',imgVE.id);
      }
      if (imgVE.dragnav && imgVE.dragnav == "dragendselected") {
        if (e.shiftKey && Object.keys(imgVE.selectedPolygons).length &&
            confirm("Would you like to save the position of the selected polygons?")) {
          //save selected polygon position
          imgVE.savePolygons();
        }
        delete imgVE.dragnav;
        return;
      }
      if (imgVE.orderSegMode == "resetting" || imgVE.orderSegMode == "setting") { //(re)setting segment(s) ordinal so ignore clicks
        return;
      } else if (e.shiftKey && !(e.ctrlKey || e.metaKey || e.altKey)) {
        var w = imgVE.aPolyW ? imgVE.aPolyW:20,
            wL = Math.round(w/2), wR = w - wL, //split size accounting for odd size
            h = imgVE.aPolyH ? imgVE.aPolyH:20,
            hU = Math.round(h/2), hL = h - hU; //split size accounting for odd size
        if (x-wL < 0) x = wL;
        if (x+wR > imgVE.image.width) x = imgVE.image.width - wR;
        if (y-hU < 0) y = hU;
        if (y+hL > imgVE.image.height) y = imgVE.image.height - hL;
        imgVE.path = [[x-wL,y-hU],[x+wR,y-hU],[x+wR,y+hL],[x-wL,y+hL]];
        imgVE.draw();
      } else if (imgVE.orderSegMode == "on") { //ordering segments so call service to set ordinal of clicked polygon
          var hitPolyIndices = imgVE.hitTestPolygons(x,y),
              segTag, savedata = {};
          // polygon hit then mark segment with next ordinal
          if (hitPolyIndices.length > 0) {
            index = hitPolyIndices[0];
            segTag = imgVE.polygons[index -1].label;
            savedata["cmd"] = "setOrdinal";
            savedata["segID"] = segTag.substring(3);
            savedata["ord"] = imgVE.ordinalSetInput.val();
            // set state on
            imgVE.orderSegMode = "setting";
            //update btn html
            imgVE.orderSegBtn.html("Setting");
            //jqAjax synch save
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: basepath+'/services/orderSegment.php?db='+dbName,//caution dependency on context having basepath and dbName
                data: savedata,
                async: true,
                success: function (data, status, xhr) {
                    var segID, polygon, baseline;
                    if (typeof data == 'object' && data.success && data.entities) {
                      //update data
                      imgVE.dataMgr.updateLocalCache(data,null);
                      imgVE.ordinalSetInput.val(1 + parseInt(imgVE.ordinalSetInput.val()));
                      if (data.entities.update && data.entities.update.seg) {
                        for (segID in data.entities.update.seg) {
                          if (data.entities.update.seg[segID]['ordinal']) {
                            polygon = imgVE.polygons[imgVE.polygonLookup['seg'+segID]-1];
                            if (polygon) {
                              polygon.order = data.entities.update.seg[segID]['ordinal'];
                            }
                          }
                          //update baseline entity
                          segment = imgVE.dataMgr.getEntity('seg',segID);
                          blnIDs = segment.baselineIDs;
                          if (blnIDs.length) {
                            for (i =0; i<blnIDs.length; i++) {
                              baseline = imgVE.dataMgr.getEntity('bln',blnIDs[i]);
                              if (baseline && baseline.segIDs && baseline.segIDs.indexOf(segID) == -1) {
                                baseline.segIDs.push(segID);
                              }
                            }
                          }
                        }
                      } else if (data.entities.removeprop &&
                                  data.entities.removeprop.seg) {
                        for (segID in data.entities.removeprop.seg) {
                          polygon = imgVE.polygons[imgVE.polygonLookup['seg'+segID]-1];
                          if (polygon && polygon.order) {
                            delete polygon.order;
                          }
                        }
                      }
                      // set state on
                      imgVE.orderSegMode = "on";
                      //update btn html
                      imgVE.orderSegBtn.html("On");
                      imgVE.drawImage();
                      imgVE.drawImagePolygons();
                    }
                    if (data.editionHealth) {
                      DEBUG.log("health","***Save Lemma cmd="+cmd+"***");
                      DEBUG.log("health","Params: "+JSON.stringify(savedata));
                      DEBUG.log("health",data.editionHealth);
                    }
                    if (data.errors) {
                      alert("An error occurred while trying to setting segment ordinal for segment "+segTag+". Error: " + data.errors.join());
                    }
                },
                error: function (xhr,status,error) {
                    // add record failed.
                    alert("An error occurred while trying to setting segment ordinal for segment "+segTag+". Error: " + error);
                }
            });
          }
        } else if ((e.ctrlKey || e.metaKey) && !(e.shiftKey || e.altKey)) { //user selecting or finishing drag navigation
          //set cursor back to pointer ???
          //hittest for target polygons
          var hitPolyIndices = imgVE.hitTestPolygons(x,y);
          //add indices to selected array
          for (i=0; i < hitPolyIndices.length; i++) {
            index = hitPolyIndices[i];
            if (imgVE.selectedPolygons[imgVE.polygons[index -1].label] == 1) {
              delete imgVE.selectedPolygons[imgVE.polygons[index -1].label];
            } else {
              imgVE.selectedPolygons[imgVE.polygons[index -1].label] = 1;
            }
          }
          //redraw
          if (imgVE.linkMode) {
            imgVE.linkMode=false;
            imgVE.drawImage();
            imgVE.drawImagePolygons();
            $('.editContainer').trigger('linkResponse',[imgVE.id,imgVE.getSelectedPolygonLabels()[0]]);
          } else {
            imgVE.drawImage();
            imgVE.drawImagePolygons();
            $('.editContainer').trigger('updateselection',[imgVE.id,imgVE.getSelectedPolygonLabels()]);
          }
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      } else if (e.altKey && !(e.ctrlKey || e.metaKey)) { //user wants set start point for path
        imgVE.path = [[x,y]];
        imgVE.segMode = "path";
        imgVE.redrawPath();
      } else if (imgVE.segMode == "path"){
        if ( imgVE.path && imgVE.path.length > 2 && //user click on start point so end polygon draw
            Math.abs(imgVE.path[0][0] - x) <= 3 *imgVE.image.width/imgVE.imgCanvas.width*imgVE.zoomFactor/100 &&
            Math.abs(imgVE.path[0][1] - y)<= 3 *imgVE.image.height/imgVE.imgCanvas.height*imgVE.zoomFactor/100) {
          imgVE.segMode = "done";
          imgVE.redrawPath();
        } else{ //add point to path
          imgVE.path.push([x,y]);
          imgVE.redrawPath();
        }
      } else if (imgVE.path && imgVE.path.length>0){
//        imgVE.path = null;
//        imgVE.drawImage();
      }
    };

    imgVE.rbRect,imgVE.drgStart,imgVE.rbImageData = null;
    $(imgVE.imgCanvas).unbind("mousedown touchstart").bind("mousedown touchstart", function (e){
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgVE.id);
      if ((e.ctrlKey || e.metaKey) && (e.buttons == 1 || e.type == "touchstart")) { //user wants to drag navigation
        //set cursor to grabbing and flag dragnavigation
        imgVE.imgCanvas.style.cursor = 'pointer';
        imgVE.dragnav = 'down';
        //store drag start
        imgVE.drgStart = imgVE.eventToCanvas(e, imgVE.imgCanvas);
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }
      if (imgVE.segMode == "path"){//likely that the user is clicking a new vertice
        return;
      }else if (imgVE.rbImageData && imgVE.rbRect) {
        imgVE.imgContext.putImageData(imgVE.rbImageData,imgVE.rbRect[0][0], imgVE.rbRect[0][1]);
        imgVE.rbImageData = null;
      }
      if (imgVE.path) {
        imgVE.path = null;
        imgVE.draw();
      }
      imgVE.drgStart = imgVE.eventToCanvas(e, imgVE.imgCanvas);
      imgVE.rbRect = [imgVE.drgStart];
      imgVE.segMode = "rect";
      e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    });

    $(imgVE.imgCanvas).unbind("mousemove touchmove").bind("mousemove touchmove", function (e){
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgVE.id);
      if ((e.ctrlKey || e.metaKey) && (e.buttons == 1 || e.type == "touchmove")) { // ctrl+left mouse button with move user is drag navigation
        imgVE.dragnav = 'move';
        imgVE.imgCanvas.style.cursor = 'grabbing';
        //get new postion
        newPos = imgVE.eventToCanvas(e, imgVE.imgCanvas);
        //calc delta
        if (e.shiftKey) { 
          //move selected polygons to new location
          dx = (imgVE.drgStart[0] - newPos[0]);
          dy = (imgVE.drgStart[1] - newPos[1]);
          imgVE.dragnav = 'moveselected';
          imgVE.moveSelectPolygons(-dx,-dy);
        } else {
          //move image to new location
          dx = (imgVE.drgStart[0] - newPos[0])*imgVE.vpSize.width/imgVE.imgCanvas.width;
          dy = (imgVE.drgStart[1] - newPos[1])*imgVE.vpSize.height/imgVE.imgCanvas.height;
          imgVE.moveViewportRelative(dx,dy);
        }
        imgVE.drgStart = newPos;
        imgVE.draw()
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }else{
        imgVE.imgCanvas.style.cursor = 'crosshair';
        delete imgVE.dragnav;
      }
      if (imgVE.segMode == "path"){
        return;
      } else if (imgVE.segMode == "rect") {//dragging for rubberband select
        //redraw saved pixels
        if (imgVE.rbImageData && imgVE.rbRect) {
          imgVE.imgContext.putImageData(imgVE.rbImageData,imgVE.rbRect[0][0], imgVE.rbRect[0][1]);
          imgVE.rbImageData = null;
        }
        //capture new corner
        var pt = imgVE.eventToCanvas(e, imgVE.imgCanvas);
        imgVE.rbRect[0] = [Math.min(pt[0],imgVE.drgStart[0]), Math.min(pt[1],imgVE.drgStart[1])];
        imgVE.rbRect[1] = [Math.abs(pt[0]-imgVE.drgStart[0]), Math.abs(pt[1]-imgVE.drgStart[1])];
        //if rect large enough capture pixels and draw rect
        if (imgVE.rbRect[1][0] >2 && imgVE.rbRect[1][1] > 2 ) {
          try {
            imgVE.rbImageData = imgVE.imgContext.getImageData(imgVE.rbRect[0][0],imgVE.rbRect[0][1],imgVE.rbRect[1][0],imgVE.rbRect[1][1]);
            var lw = imgVE.imgContext.lineWidth;
            imgVE.imgContext.strokeRect(imgVE.rbRect[0][0]+lw,
                                            imgVE.rbRect[0][1]+lw,
                                            imgVE.rbRect[1][0]-2*lw,
                                            imgVE.rbRect[1][1]-2*lw);
          } catch (error) {
            // ignore
          }
        }
      }
    });

    $(imgVE.imgCanvas).unbind("mouseup touchend").bind("mouseup touchend", function (e){
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgVE.id);
      if ((e.ctrlKey || e.metaKey) || e.type == "touchend") { // || isDragNavigation ) { //user ending drag navigation
        //close flag
        //reset cursor to grab if ctrl else to pointer
        if (imgVE.dragnav == 'move' || imgVE.dragnav == 'moveselected' || imgVE.dragnav == 'down') {
          imgVE.dragnav = (imgVE.dragnav == 'moveselected'?"dragendselected":"dragend");
          imgVE.imgCanvas.style.cursor = 'crosshair';
          e.stopImmediatePropagation();
        }else{
          delete imgVE.dragnav;
          imgVE.imgCanvas.style.cursor = 'pointer';
        }
        if (imgVE.segMode == "rect") {
          imgVE.segMode = "done";
          imgVE.imgCanvas.style.cursor = 'crosshair';
          e.stopImmediatePropagation();
        }
        return;
      }
      if (imgVE.segMode == "path"){
        return;
      } else if (imgVE.segMode == "rect") {
        //if large enough move rect to imgVE path and redraw
        if (imgVE.rbRect[1] && imgVE.rbRect[1].length > 1 && imgVE.rbRect[1][0] >2 &&  imgVE.rbRect[1][1] > 2 ) {
          var lw = imgVE.imgContext.lineWidth;
          var x = (imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width + imgVE.vpSize.width/imgVE.navCanvas.width*imgVE.image.width * (imgVE.rbRect[0][0]+lw)/imgVE.imgCanvas.width),
              y = (imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height + imgVE.vpSize.height/imgVE.navCanvas.height*imgVE.image.height * (imgVE.rbRect[0][1]+lw)/imgVE.imgCanvas.height),
              w = (imgVE.vpSize.width/imgVE.navCanvas.width*imgVE.image.width * (imgVE.rbRect[1][0]-2*lw)/imgVE.imgCanvas.width),
              h = (imgVE.vpSize.height/imgVE.navCanvas.height*imgVE.image.height * (imgVE.rbRect[1][1]-2*lw)/imgVE.imgCanvas.height);
          imgVE.path = [[x,y],[x+w,y],[x+w,y+h],[x,y+h]];
          imgVE.aPolyW = w;
          imgVE.aPolyH = h;
        }
        //clean up
        imgVE.imgCanvas.style.cursor = 'crosshair';
        e.stopImmediatePropagation();
        imgVE.rbImageData = null;
        imgVE.rbRect = null;
        imgVE.drgStart = null;
        imgVE.segMode = "done";
        imgVE.draw();
      }
    });

    /**
    *
    * linkRequestHandler sets state variables so that the imageVE will  show segments and
    * repond upon creattion or selection of a segment to this link request
    *
    * @param e event object
    * @param senderID
    * @param linkSource
    * @param autoAdvance
    */

    function linkRequestHandler(e,senderID, linkSource, autoAdvance) {
      if (senderID == imgVE.id) {
        return;
      }
      DEBUG.log("event","link request received by imageVE in "+imgVE.id+" from "+senderID+" with source "+ linkSource + (autoAdvance?" with autoAdvance on":""));
      imgVE.linkMode = true;
      imgVE.autoLink = autoAdvance? true : false;
      imgVE.drawImage();
      imgVE.drawImagePolygons();
    };

    $(imgVE.editDiv).unbind('linkRequest').bind('linkRequest', linkRequestHandler);


    /**
    * handle 'autoLinkOrdAbort' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string requestSource Identifies the source baseline that sent the link request being aborted
    */

    function autoLinkOrdAbortHandler(e,senderID, requestSource) {
      if (senderID == imgVE.id) {
        return;
      }
      DEBUG.log("event","link abort recieved by imageVE in "+imgVE.id+" from "+senderID+" with requestSource "+ requestSource);
      if ((imgVE.autoLinkOrdMode || imgVE.autoLinkPatternMode) && requestSource == imgVE.blnEntity.id) {
        imgVE.autoLinkOrdMode = false;
        imgVE.autoLinkPatternMode = false;
      }
    };

    $(this.editDiv).unbind('autoLinkOrdAbort').bind('autoLinkOrdAbort', autoLinkOrdAbortHandler);


    /**
    * handle 'autoLinkOrdReturn' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string requestSource Identifies the source baseline that sent the link request
    * @param string linkTargetEdition Identifies the target edition to link
    * @param string array sclIDs identifing the start and/or stop or set of syllables to use for ordinal linking
    */

    function autoLinkOrdReturnHandler(e,senderID, requestSource, linkTargetEdition, sclIDs, blnIDs) {
      var savedata ={},segID,segIDs;
      if (senderID == imgVE.id) {
        return;
      }
      DEBUG.log("event","auotlinkOrd return received by imageVE in "+imgVE.id+" from "+senderID+" with requestSource "+ requestSource +
                " link to ednID -"+linkTargetEdition+
                (blnIDs && blnIDs.length?"  baseline IDs -"+blnIDs.join():"") +
                (sclIDs && sclIDs.length ?"  syllable IDs -"+sclIDs.join():""));
      if ((imgVE.autoLinkOrdMode || imgVE.autoLinkPatternMode) && requestSource == imgVE.blnEntity.id && linkTargetEdition) {
        //in link mode and have edition info so call link service
        //sclIDs, segIDs, blnIDs, pattern and ednID
        savedata["ednID"] = linkTargetEdition;
        savedata["reqBlnID"] = requestSource;
        savedata["blnIDs"] = (blnIDs && blnIDs.length?blnIDs:[imgVE.blnEntity.id]);
        if (imgVE.autoLinkPatternMode) {// use pattern from configured global linkToSyllablePattern
          savedata["pattern"] = linkToSyllablePattern;
        } else if (sclIDs && sclIDs.length) {
          savedata["sclIDs"] = sclIDs;
        }
        if (Object.keys(imgVE.selectedPolygons).length) {
          segIDs = [];
          for (segID in imgVE.selectedPolygons) {
            segIDs.push(segID.substring(3));
          }
          savedata["segIDs"] = segIDs;
        }
        //jqAjax synch save
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: basepath+'/services/linkOrderedSegments.php?db='+dbName,//caution dependency on context having basepath and dbName
            data: savedata,
            async: true,
            success: function (data, status, xhr) {
              var segID,polygon,segTag,redrawPolygons = false;
              if (typeof data == 'object' && data.success ) {
                //update data
                if (data.entities){
                  imgVE.dataMgr.updateLocalCache(data,null);
                }
                if (imgVE.orderSegMode == "on") {//pressed autolinkord while in order segments mode
                  imgVE.orderSegMode = "off";
                  redrawPolygons = true;
                }
                //TODO update polygons linked.
                if (data.entities.update && data.entities.update.seg){
                  for (segID in data.entities.update.seg) {
                    polygon = imgVE.polygons[imgVE.polygonLookup["seg"+segID]-1];
                    if (polygon) {
                      polygon.color = "green";
                      polygon.linkIDs = data.entities.update.seg[segID]['sclIDs'];
                    }
                  }
                  redrawPolygons = true;
                }
                if (redrawPolygons) {
                  imgVE.drawImage();
                  imgVE.drawImagePolygons();
                }
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Save Lemma cmd="+cmd+"***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to link ordered segments for baseline bln"+imgVE.blnEntity.id+". Error: " + data.errors.join());
              }
              //signal linking complete.
              $('.editContainer').trigger('autoLinkOrdComplete',[imgVE.id,imgVE.blnEntity.id,linkTargetEdition],data.entities.update.scl);
              imgVE.autoLinkOrdMode = false;
            },
            error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while sending a link ordered segments for baseline bln"+imgVE.blnEntity.id+". Error: " + error);
              $('.editContainer').trigger('autoLinkOrdComplete',[imgVE.id,imgVE.blnEntity.id,linkTargetEdition]);
            }
        });
      }
    };

    $(this.editDiv).unbind('autoLinkOrdReturn').bind('autoLinkOrdReturn', autoLinkOrdReturnHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param linkSource
* @param linkTarget
* @param oldLinkTarget
*/

    function linkCompleteHandler(e,senderID, linkSource, linkTarget, oldLinkTarget) {
      if (senderID == imgVE.id) {
        return;
      }
      DEBUG.log("event","link complete recieved by imageVE in "+imgVE.id+" from "+senderID+" with source "+ linkSource+" and target "+linkTarget);
      //todo add code to detect segment to segment and update accordingly
      //update polygon for linking
      var polygon = imgVE.polygons[imgVE.polygonLookup[linkTarget]-1];
      polygon.linkIDs = imgVE.dataMgr.entities['seg'][linkTarget.substring(3)]['sclIDs'];
      polygon.color = "green";
      DEBUG.log("gen","polygon update for "+linkTarget+" now linked to "+ linkSource);
      imgVE.linkMode = false;
      if (oldLinkTarget) {
        removeLink(linkSource, oldLinkTarget);
      }
      imgVE.drawImage();
      imgVE.drawImagePolygons();
      if (imgVE.autoLink) {
        $('.editContainer').trigger('autoLinkAdvance',[imgVE.id,linkSource,linkTarget]);
      }
    };

    $(imgVE.editDiv).unbind('linkComplete').bind('linkComplete', linkCompleteHandler);


/**
* put your comment there...
*
* @param linkSource
* @param linkTarget
*/

    function removeLink(linkSource, linkTarget) {
      var linkedPolyIndex,segSylIDs,sclID,sylIndex;
      //remove link from polygon
      if (imgVE.polygonLookup[linkTarget]) {
        linkedPolyIndex = imgVE.polygonLookup[linkTarget] - 1;
        segSylIDs = imgVE.polygons[linkedPolyIndex].linkIDs;
        sclID = linkSource.substring(3);
        sylIndex = segSylIDs ? segSylIDs.indexOf(sclID):-1;
        if (sylIndex > -1) {
          segSylIDs.splice(sylIndex,1);
          imgVE.polygons[linkedPolyIndex].linkIDs = segSylIDs;
        }
        if (!segSylIDs || segSylIDs.length == 0) {
          imgVE.polygons[linkedPolyIndex].color = "red";
        }
      }
    };


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param linkSource
* @param linkTarget
*/

    function linkRemovedHandler(e,senderID, linkSource, linkTarget) {
      if (senderID == imgVE.id) {
        return;
      }
      DEBUG.log("event","link removed recieved by imageVE in "+imgVE.id+" from "+senderID+" with source "+ linkSource+" and target "+linkTarget);
      removeLink(linkSource,linkTarget);
      imgVE.drawImage();
      imgVE.drawImagePolygons();
    };

    $(imgVE.editDiv).unbind('linkRemoved').bind('linkRemoved', linkRemovedHandler);

    /**
    * linkResponseHandler is a call back event that receives the target entity tag that was selected by the user in another
    * editor. This is where we have enough information to procede or not with the actual linking process.
    *
    *
    * @param object e System event object
    * @param senderID
    * @param linkTarget
    */

    function linkResponseHandler(e,senderID, linkTarget) {
      if (senderID == imgVE.id || !imgVE.linkSource) {
        return;
      }
      var savedata={},
          srcPrefix = imgVE.linkSource.substring(0,3),
          trgPrefix = linkTarget.substring(0,3),
          srcID = imgVE.linkSource.substring(3),
          trgID = linkTarget.substring(3),
          segID, sclID, sclOldSegID, srcSegMappedIDs, trgSegMappedIDs;

          DEBUG.log("event","link response received by imageVE in "+imgVE.id+" from "+senderID+" with source "+imgVE.linkSource+" target "+ linkTarget);

      if ( srcPrefix == "seg" && srcPrefix == trgPrefix) { // segment to segment linking
        if (imgVE.dataMgr.entities['seg'][srcID].readonly) {
          alert("segment to segment linking aborted, not possible with readonly segment segID "+srcID);
          imgVE.linkMode=false;
          imgVE.drawImage();
          imgVE.drawImagePolygons();
          $('.editContainer').trigger('linkAbort',[imgVE.id,"seg"+srcID,"seg"+trgID]);
          return;
        }
        if (imgVE.dataMgr.entities['seg'][trgID].readonly) {
          alert("segment to segment linking aborted, not possible with readonly segment segID "+srcID);
          imgVE.linkMode=false;
          imgVE.drawImage();
          imgVE.drawImagePolygons();
          $('.editContainer').trigger('linkAbort',[imgVE.id,"seg"+srcID,"seg"+trgID]);
          return;
        }
        srcSegMappedIDs = imgVE.dataMgr.entities['seg'][srcID]['mappedSegIDs']?imgVE.dataMgr.entities['seg'][srcID]['mappedSegIDs']:[],
        trgSegMappedIDs = imgVE.dataMgr.entities['seg'][trgID]['mappedSegIDs']?imgVE.dataMgr.entities['seg'][trgID]['mappedSegIDs']:[];
        srcSegMappedIDs.push(trgID);
        trgSegMappedIDs.push(srcID);
        savedata['seg'] = [{seg_id:srcID, seg_mapped_seg_ids: '{'+srcSegMappedIDs.join()+'}'},
                           {seg_id:trgID, seg_mapped_seg_ids: '{'+trgSegMappedIDs.join()+'}'}];
      } else {//received from edition editor after selecting syllable
        if (srcPrefix == "seg") {
          segID = srcID;
          sclID = trgID;
        } else {
          segID = trgID;
          sclID = srcID;
        }
        if (imgVE.dataMgr.entities['scl'][sclID].readonly) {
          alert("segment to syllable linking aborted, not possible with readonly syllable sclID "+sclID);
          imgVE.linkMode=false;
          imgVE.drawImage();
          imgVE.drawImagePolygons();
          $('.editContainer').trigger('linkAbort',[imgVE.id,"seg"+segID,"scl"+sclID]);
          return;
        }
        sclOldSegID = imgVE.dataMgr.entities['scl'][sclID]['segID'];
        oldIsTransSeg = (imgVE.dataMgr.entities['seg'][sclOldSegID] && imgVE.dataMgr.entities['seg'][sclOldSegID]['stringpos'] && imgVE.dataMgr.entities['seg'][sclOldSegID]['stringpos'].length);
        savedata['scl'] = [{scl_id:sclID,scl_segment_id:segID}];
        if (oldIsTransSeg) {
          scratch = (imgVE.dataMgr.entities['scl'][sclID]['scratch']?JSON.parse(imgVE.dataMgr.entities['scl'][1]['scratch']):{});
          scratch['tranSeg'] = 'seg'+sclOldSegID;
          savedata['scl'][0]['scl_scratch'] = JSON.stringify(scratch);
        }
      }
      //save link
      $.ajax({
          dataType: 'json',
          url: basepath+'/services/saveEntityData.php?db='+dbName,
          data: 'data='+JSON.stringify(savedata),
          asynch: false,
          success: function (data, status, xhr) {
              if (typeof data == 'object' && data.syllablecluster && data.syllablecluster.success) {
                if (data['syllablecluster'].columns &&
                    data['syllablecluster'].records[0] &&
                    data['syllablecluster'].columns.indexOf('scl_id') > -1) {
                  var record, segID, sclID, cnt, sclLabel, oldSegPolyIndex, sylIndex, segLabel, segSylIDs,
                      unlinkedSegLabel = !oldIsTransSeg?'seg'+sclOldSegID:null,
                      pkeyIndex = data['syllablecluster'].columns.indexOf('scl_id'),
                      segIdIndex = data['syllablecluster'].columns.indexOf('scl_segment_id');
                  cnt = data['syllablecluster'].records.length;
                  // for each record
                  for(i=0; i<cnt; i++) {
                    record = data['syllablecluster'].records[i];
                    sclID = record[pkeyIndex];
                    segID = record[segIdIndex];
                    sclLabel = 'scl'+sclID;
                    segLabel = 'seg'+segID;
                    // update cached data
                    segSylIDs = imgVE.dataMgr.entities['seg'][segID]['sclIDs'] ? imgVE.dataMgr.entities['seg'][segID]['sclIDs']:[];
                    if (segSylIDs.indexOf(sclID) == -1) {
                      segSylIDs.push(sclID);
                      imgVE.dataMgr.entities['seg'][segID]['sclIDs'] = segSylIDs;
                    }
                    imgVE.dataMgr.entities['scl'][sclID]['segID'] = segID;
                    imgVE.polygons[imgVE.polygonLookup[segLabel]-1].linkIDs = segSylIDs;
                    imgVE.polygons[imgVE.polygonLookup[segLabel]-1].color = "";
                    if (unlinkedSegLabel) { // need to remove the syllable from the previous segment
                      if (imgVE.dataMgr.entities['seg'][sclOldSegID] && imgVE.dataMgr.entities['seg'][sclOldSegID]['sclIDs']) {
                        segSylIDs = imgVE.dataMgr.entities['seg'][sclOldSegID]['sclIDs'];
                        sylIndex = segSylIDs.indexOf(sclID);
                        if (sylIndex > -1) {
                          segSylIDs.splice(sylIndex,1);
                          imgVE.dataMgr.entities['seg'][sclOldSegID]['sclIDs'] = segSylIDs;
                        }
                      }
                      if (imgVE.polygonLookup[unlinkedSegLabel]) {
                        oldSegPolyIndex = imgVE.polygonLookup[unlinkedSegLabel] - 1;
                        segSylIDs = imgVE.polygons[oldSegPolyIndex].linkIDs;
                        sylIndex = segSylIDs ? segSylIDs.indexOf(sclID):-1;
                        if (sylIndex > -1) {
                          segSylIDs.splice(sylIndex,1);
                          imgVE.polygons[oldSegPolyIndex].linkIDs = segSylIDs;
                        }
                        if (!segSylIDs || segSylIDs.length == 0) {
                          imgVE.polygons[oldSegPolyIndex].color = "red";
                        }
                      }
                    }
      DEBUG.log("gen"," linked "+segLabel+" to "+sclLabel);
                  }
                  imgVE.linkMode=false;
                  imgVE.drawImage();
                  imgVE.drawImagePolygons();
                  $('.editContainer').trigger('linkComplete',[imgVE.id,segLabel,sclLabel]);
                }else{
                // todo add code for segment to segment linking
                //also need to add code for differentiating syllable links from mapped segment links
                }
                if (data['segment'] && data['segment'].errors && data['segment'].errors.length) {
                  alert("An error occurred while trying to save to a segment record. Error: " + data['segment'].errors.join());
                }
                if (data['syllablecluster'] && data['syllablecluster'].errors && data['syllablecluster'].errors.length) {
                  alert("An error occurred while trying to save to a segment record. Error: " + data['syllablecluster'].errors.join());
                }
                if (data['error']) {
                  alert("An error occurred while trying to save to a segment record. Error: " + data['error']);
                }
              }
          },// end success cb
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to link. Error: " + error);
          }
      });// end ajax

    };

    $(imgVE.editDiv).unbind('linkResponse').bind('linkResponse', linkResponseHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
*/

    function updateSelectionHandler(e,senderID, selectionIDs) {
      if (senderID == imgVE.id || imgVE.autoLinkOrdMode) {
        return;
      }
      DEBUG.log("event","selection changed received by imageVE in "+imgVE.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      imgVE.unselectAllPolygons();
      if (selectionIDs && selectionIDs.length) {
        $.each(selectionIDs, function(i,val) {
          if (val && val.length) {
           imgVE.selectPolygonByName(val);
          }
        });
      }
      imgVE.linkMode = false;
      imgVE.drawImage();
      imgVE.drawImagePolygons();
    };

    $(imgVE.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
*/

    function enterSyllableHandler(e,senderID, selectionIDs) {
      if (senderID == imgVE.id) {
        return;
      }
//      DEBUG.log("gen""enterSyllable received by "+pimgVE.idane+" for "+ selectionIDs[0]);
      var i, id;
      $.each(selectionIDs, function(i,val) {
        imgVE.setImagePolygonHilite(imgVE.getIndexbyPolygonName(val),true);
      });
      imgVE.drawImagePolygons();
    };

    $(imgVE.editDiv).unbind('enterSyllable').bind('enterSyllable', enterSyllableHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
*/

    function leaveSyllableHandler(e,senderID, selectionIDs) {
      if (senderID == imgVE.id) {
        return;
      }
      var i, id;
      $.each(selectionIDs, function(i,val) {
        imgVE.setImagePolygonHilite(imgVE.getIndexbyPolygonName(val),false);
      });
      imgVE.drawImage();
      imgVE.drawImagePolygons();
    };

    $(imgVE.editDiv).unbind('leaveSyllable').bind('leaveSyllable', leaveSyllableHandler);


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param anchorSegID
* @param visFraction
*/

    function synchronizeHandler(e,senderID, anchorSegID, visFraction, imgPosData) {
      if (senderID == imgVE.id) {
        return;
      }
      DEBUG.log("event","synchronize recieved by imageVE in "+imgVE.id+" from "+senderID+" with anchor segID "+ anchorSegID+" and visibility fraction "+visFraction);
      var top, polygon, deltaPolygon, index, syncSegTag, yOffset = 0;
      if (imgPosData) {
        syncSegTag = "seg"+imgPosData.segID;
      } else {
        return;
      }
      //find segment's polygon
      index = imgVE.polygonLookup[syncSegTag];
      if (!index) {//non image segment id for this imageVE so ignore it
        return;
      } else {
        polygon = imgVE.polygons[-1+index];
      }
      if (imgPosData.deltaSegID) {
        index = imgVE.polygonLookup[imgPosData.deltaSegID];
        if (index) {
          deltaPolygon = imgVE.polygons[-1+index];
        }
      }
      if (polygon && polygon.label == syncSegTag) {
        polygon = polygon.polygon;
        if (deltaPolygon && imgPosData.deltaFactor) {
          deltaPolygon = deltaPolygon.polygon;
          top = polygon[0][1] + imgPosData.deltaFactor * (deltaPolygon[0][1] - polygon[0][1]);
        } else {
          top = polygon[0][1] + imgPosData.segHeightFactor * (polygon[2][1] - polygon[0][1]);
        }
      }
      //calculate position for segment polygon
      if (top) {
        imgVE.eraseNavPanel();
        imgVE.moveViewportToImagePosY(top);
        imgVE.adjustScrollForViewport();
        imgVE.draw();
      }
    };

    $(imgVE.editDiv).unbind('synchronize').bind('synchronize', synchronizeHandler);

  },


/**
* put your comment there...
*
* @returns {Array}
*/

  getPath: function () {
    if (!this.path || !this.path.length) return null;
    var intPoly = [],
        i,x,y,
        cnt = this.path.length;
        for (i=0; i< cnt; i++) {
          intPoly.push([ Math.round(this.path[i][0]),Math.round(this.path[i][1])]);
        }
    return intPoly;
  },


/**
* put your comment there...
*
*/

  clearPath: function () {
    this.path = null;
  },


/**
* put your comment there...
*
*/

  redrawPath: function () {
    var imgVE = this;
    if (!this.path || !this.path.length) return;
    this.imgContext.clearRect(0,0,this.imgCanvas.width,this.imgCanvas.height);
    this.drawImage();
    this.drawImagePolygons();
    this.imgContext.strokeStyle = (imgVE.segMode == "done" ? "green":"red");
    this.imgContext.lineWidth = 2;
    if (this.path.length == 1){
      var x = (this.path[0][0] - imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width)/imgVE.vpSize.width*imgVE.navCanvas.width/imgVE.image.width*imgVE.imgCanvas.width,
          y = (this.path[0][1] - imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height)/imgVE.vpSize.height*imgVE.navCanvas.height/imgVE.image.height*imgVE.imgCanvas.height;
      this.imgContext.beginPath();
      this.imgContext.moveTo(x-this.crossSize,y);
      this.imgContext.lineTo(x+this.crossSize,y);
      this.imgContext.moveTo(x,y-this.crossSize);
      this.imgContext.lineTo(x,y+this.crossSize);
      this.imgContext.stroke();
    }else{
      var i,
      xStart = (this.path[0][0] - imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width)/imgVE.vpSize.width*imgVE.navCanvas.width/imgVE.image.width*imgVE.imgCanvas.width,
      yStart = (this.path[0][1] - imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height)/imgVE.vpSize.height*imgVE.navCanvas.height/imgVE.image.height*imgVE.imgCanvas.height;
      this.imgContext.beginPath();
      if (this.segMode == "path"){
        this.imgContext.rect( xStart-2, yStart-2, 4, 4);
      }
      this.imgContext.moveTo(xStart,yStart);
      for (i=1; i < this.path.length; i++) {
        this.imgContext.lineTo((this.path[i][0] - imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width)/imgVE.vpSize.width*imgVE.navCanvas.width/imgVE.image.width*imgVE.imgCanvas.width,
                               (this.path[i][1] - imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height)/imgVE.vpSize.height*imgVE.navCanvas.height/imgVE.image.height*imgVE.imgCanvas.height);
      }
      this.imgContext.closePath();
      this.imgContext.stroke();
    }
  },


/**
* put your comment there...
*
* @param alpha
*/

  drawNavPanel: function(alpha) {+
    this.navContext.save();
    this.navContext.globalAlpha = alpha;
    this.navContext.imageSmoothingEnabled = false;
    this.navContext.drawImage(this.image,//draw scaled image into pan window
      0, 0,
      this.image.width,
      this.image.height,
      0, 0,
      this.navCanvas.width,
      this.navCanvas.height);
    this.navContext.restore();
  },


/**
* put your comment there...
*
*/

  drawImage: function() {
    var width = this.image.width * this.vpSize.width / this.navCanvas.width,
        height = this.image.height * this.vpSize.height / this.navCanvas.height;//BUG index calcs < 0
    this.imgContext.clearRect(0,0,this.imgCanvas.width,this.imgCanvas.height);
    this.imgContext.imageSmoothingEnabled = false;
    this.imgContext.drawImage(this.image,
      this.vpLoc.x * this.image.width / this.navCanvas.width,
      this.vpLoc.y * this.image.height / this.navCanvas.height,
      width,
      height,
      0, 0,
      this.imgCanvas.width,
      this.imgCanvas.height);
  },


/**
* put your comment there...
*
*/

  drawImagePolygons: function () {
    var imgVE = this;
    if (!this.polygons || !this.polygons.length) return;
    this.imgContext.save();
    var i,j,polygon,
        offsetX = imgVE.vpLoc.x * imgVE.image.width / imgVE.navCanvas.width,
        scaleX = imgVE.navCanvas.width/imgVE.vpSize.width*imgVE.imgCanvas.width/imgVE.image.width,
        offsetY = imgVE.vpLoc.y * imgVE.image.height / imgVE.navCanvas.height,
        scaleY = imgVE.navCanvas.height/imgVE.vpSize.height*imgVE.imgCanvas.height/imgVE.image.height;
    this.imgContext.font = ""+ 1.4*Math.max(1,scaleY)+"em arial";
    this.imgContext.textAlign = "center";
    this.imgContext.textBaseline = "middle";
    for(i=0;i<this.polygons.length; i++) {
      polygon = this.polygons[i];
      if (!this.viewAllPolygons && !this.linkMode && this.orderSegMode != "on") {
        if (polygon.hidden && !polygon.hilite && !this.selectedPolygons[polygon.label]) {
          continue;
        }
      }
      if ( this.orderSegMode != "on" ) {
        this.imgContext.strokeStyle = polygon.hilite? "#DDDDDD" : (this.selectedPolygons[polygon.label]? "#DDDDDD" : (polygon.dirty? "orange":polygon.color));
        if (this.showPolygonNumbers && polygon.order) {// draw order numer if there is one
          this.imgContext.lineWidth = 1;
          this.imgContext.fillStyle = this.imgContext.strokeStyle;
          this.imgContext.fillText(polygon.order,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
          this.imgContext.strokeText(polygon.order,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
        } else if (this.showPolygonCodes && polygon.code) {// draw code if there is one
          this.imgContext.lineWidth = 1;
          this.imgContext.fillStyle = this.imgContext.strokeStyle;
          this.imgContext.fillText(polygon.code,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
          this.imgContext.strokeText(polygon.code,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
        } else if (this.showPolygonLocs && polygon.loc) {// draw code if there is one
          this.imgContext.lineWidth = 1;
          stylecolor = this.imgContext.strokeStyle;
          this.imgContext.strokeStyle = "maroon";
          this.imgContext.fillStyle = "maroon"; 
          this.imgContext.fillText(polygon.loc,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
          this.imgContext.strokeText(polygon.loc,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
          this.imgContext.strokeStyle = stylecolor;
          this.imgContext.fillStyle = stylecolor; 
        }
        this.imgContext.lineWidth = (this.selectedPolygons[polygon.label]? 3 : polygon.width); //polygon width
      } else {
        if (this.showPolygonNumbers && polygon.order) {// draw order number if there is one
          this.imgContext.lineWidth = 1;
          this.imgContext.strokeStyle = "#DDDDDD";
          this.imgContext.fillStyle = "#DDDDDD";
          this.imgContext.fillText(polygon.order,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
          this.imgContext.strokeText(polygon.order,(polygon.center[0] - offsetX)*scaleX,(polygon.center[1] - offsetY)*scaleY);
          this.imgContext.lineWidth = 3 ;
        }
        this.imgContext.strokeStyle = "blue";
        this.imgContext.lineWidth = 1 ;
      }
/*      this.imgContext.lineWidth = 4 ;
      this.imgContext.beginPath();
      this.imgContext.moveTo((polygon.polygon[0][0] - offsetX)*scaleX,(polygon.polygon[0][1] - offsetY)*scaleY);
      for (j=1; j < polygon.polygon.length; j++) {
        this.imgContext.lineTo((polygon.polygon[j][0] - offsetX)*scaleX,(polygon.polygon[j][1] - offsetY)*scaleY);
      }
      this.imgContext.closePath();
      this.imgContext.stroke();

      this.imgContext.strokeStyle = "cyan";
      this.imgContext.lineWidth = 1 ;
*/
      this.imgContext.beginPath();
      this.imgContext.moveTo((polygon.polygon[0][0] - offsetX)*scaleX,(polygon.polygon[0][1] - offsetY)*scaleY);
      for (j=1; j < polygon.polygon.length; j++) {
        this.imgContext.lineTo((polygon.polygon[j][0] - offsetX)*scaleX,(polygon.polygon[j][1] - offsetY)*scaleY);
      }
      this.imgContext.closePath();
      this.imgContext.stroke();
    }
    this.imgContext.restore();
  },


/**
* put your comment there...
*
*/

  drawViewport: function () {
    this.navContext.shadowColor = 'rgba(0,0,0,0.4)';
    this.navContext.shadowOffsetX = 2;
    this.navContext.shadowOffsetY = 2;
    this.navContext.shadowBlur = 3;

    this.navContext.lineWidth = 3;
    this.navContext.strokeStyle = 'white';
    this.navContext.strokeRect( this.vpLoc.x,
                                this.vpLoc.y,
                                this.vpSize.width,
                                this.vpSize.height);
  },


/**
* put your comment there...
*
*/

  clipToViewport: function() {
    this.navContext.beginPath();
    this.navContext.rect( this.vpLoc.x,
                          this.vpLoc.y,
                          this.vpSize.width,
                          this.vpSize.height);
    this.navContext.clip();
  },


/**
* put your comment there...
*
*/

  draw: function() {
    this.drawImage();
    this.drawNavPanel(this.navOpacity);
    this.navContext.save();
    this.clipToViewport();
    this.drawNavPanel(1.0);
    this.navContext.restore();

    this.drawViewport();
    this.drawImagePolygons();
    this.redrawPath();
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getCommandStack: function() {
    if (!this.imgCmdStack){
      return "";
    }
    return this.imgCmdStack.join(",");
  },


/**
* put your comment there...
*
*/

  clearImageCommands: function() {
    this.imgCmdStack = [];
    this.fadeColors = {};
  },


/**
* put your comment there...
*
* @param str
*/

  runCommandString: function(str) {
    if (!str){
      return ;
    }
    this.imgCmdStack = [];
    commands = str.replace(",","");
    commands = commands.toUpperCase();
    var i;
    for (i=0; i<commands.length; i++){
      switch (commands[i]) {
        case 'S':
          this.stretch();
          break;
        case 'R':
          this.reduce();
          break;
        case 'I':
          this.invert();
          break;
        case 'E':
          this.emboss();
          break;
      }
    }
  },
/************************************************  Image Processors**************************************************/

/**
* put your comment there...
*
*/

  stretch: function() {
    var imgdata, data, length, width, max, min, mean, minAdjust, maxAdjust;

    imgdata = this.imgContext.getImageData(0, 0,this.imgCanvas.width, this.imgCanvas.height);
    data = imgdata.data;
    width = imgdata.width;
    length = data.length;
    min = max = 255/2;

    for (i=0; i < length; i++) { // loop through pixels
      // if it's not an alpha
      if ((i+1) % 4 !== 0) {
        if (min && data[i]-min < 0) {
          min = data[i];
        } else if ( max - data[i] < 0) {
          max = data[i];
        }
      }
    }
    mean = max/2 + min/2;
    maxAdjust = (max == 255 ? 2 : Math.floor(127-max/2));
    minAdjust = (min == 0 ? 1 : Math.ceil(min/2));
    for (i=0; i < length; i++) { // loop through pixels
      if ((i+1) % 4 !== 0) {
        if (data[i] > mean) {
          data[i] += maxAdjust;
          if (data[i] > 255) {
            data[i] = 255;
          }
        }else{
          data[i] -= minAdjust;
          if (data[i] < 0) {
            data[i] = 0;
          }
        }
      }
    }
    this.imgContext.putImageData(imgdata, 0, 0);
    if (!this.imgCmdStack) {
      this.imgCmdStack = ["S"];
    }else{
      this.imgCmdStack.push("S");
    }
  },


/**
* put your comment there...
*
*/

  reduce: function() {
    var imgdata, data, length, width;

    imgdata = this.imgContext.getImageData(0, 0,this.imgCanvas.width, this.imgCanvas.height);
    data = imgdata.data;
    width = imgdata.width;
    length = data.length;

    for (i=0; i < length; i++) { // loop through pixels
        // if it's not an alpha
        if ((i+1) % 4 !== 0) {
          data[i] = Math.floor(data[i]*0.9); //inverse color
        }
    }
    this.imgContext.putImageData(imgdata, 0, 0);
    if (!this.imgCmdStack) {
      this.imgCmdStack = ["R"];
    }else{
      this.imgCmdStack.push("R");
    }
  },


/**
* put your comment there...
*
* @returns true|false
*/

  fade: function() {
    var imgdata, data, sampledata, cnt, length, hash, path, fadeColor,
        offsetX = this.vpLoc.x * this.image.width / this.navCanvas.width,
        scaleX = this.navCanvas.width/this.vpSize.width*this.imgCanvas.width/this.image.width,
        offsetY = this.vpLoc.y * this.image.height / this.navCanvas.height,
        scaleY = this.navCanvas.height/this.vpSize.height*this.imgCanvas.height/this.image.height;

    path = this.getPath();
    if(!path && !Object.keys(this.fadeColors).length){
      alert("Please drag select an area of the image to fade");
      return false;
    }
    if (path && path.length) {//add color samples to fade
      //find this.path bounding box  todo add support for polygon sample
      path = UTILITY.getBoundingRect(path);
      // get colors for fade  [[x1,y1],[x2,y1],[x2,y2],[x1,y2]);
      sampledata = this.imgContext.getImageData((path[0][0] - offsetX)*scaleX,
                                                (path[0][1] - offsetY)*scaleY,
                                                (path[2][0] - path[0][0])*scaleX,
                                                (path[2][1] - path[0][1])*scaleY);
      //create has R-G-B for lookup of fade values [R',G',B']
      sampledata = sampledata.data;
      length = sampledata.length;
      var R,G,B;
      for (i=0; i < length-4; i+=4) { // loop through each pixel
        R = Math.floor(sampledata[i]/10);
        G = Math.floor(sampledata[i+1]/10);
        B = Math.floor(sampledata[i+2]/10);
        hash = R+"-"+G+"-"+B;
        if (!this.fadeColors[hash]) {
          this.fadeColors[hash] = [ Math.round((255-R*10)*0.8),G + Math.round((255-G*10)*0.8),B + Math.round((255-B*10)*0.8)];
        }
      }
    }

    //fade image
    if ( Object.keys(this.fadeColors).length){
      imgdata = this.imgContext.getImageData(0, 0,this.imgCanvas.width, this.imgCanvas.height);
      data = imgdata.data;
      length = data.length;

      for (i=0; i < length-4; i+=4) { // loop through each pixel
          // if it's color matched fade by replacing with lookup value
        R = Math.floor(data[i]/10);
        G = Math.floor(data[i+1]/10);
        B = Math.floor(data[i+2]/10);
        hash = R+"-"+G+"-"+B;
        if (this.fadeColors[hash]) { //hit
          fadeColor = this.fadeColors[hash];
          data[i] = Math.min(255,data[i]+fadeColor[0]); //red
          data[i+1] = Math.min(255,data[i+1]+fadeColor[1]); //green
          data[i+2] = Math.min(255,data[i+2]+fadeColor[2]); //blue
        }
      }
      this.imgContext.putImageData(imgdata, 0, 0);
    }
  },


/**
* put your comment there...
*
*/

  invert: function() {
    var imgdata, data, length, width;

    imgdata = this.imgContext.getImageData(0, 0,this.imgCanvas.width, this.imgCanvas.height);
    data = imgdata.data;
    width = imgdata.width;
    length = data.length;

    for (i=0; i < length; i++) { // loop through pixels
        // if it's not an alpha
        if ((i+1) % 4 !== 0) {
          data[i] = 255 - data[i]; //inverse color
        }
    }
    this.imgContext.putImageData(imgdata, 0, 0);
    if (!this.imgCmdStack) {
      this.imgCmdStack = ["I"];
    }else{
      this.imgCmdStack.push("I");
    }
  },


/**
* put your comment there...
*
*/

  emboss: function() {
    var imgdata, data, length, width;

    imgdata = this.imgContext.getImageData(0, 0,this.imgCanvas.width, this.imgCanvas.height);
    data = imgdata.data;
    width = imgdata.width;
    length = data.length;

    for (i=0; i < length; i++) { // loop through pixels
      // if we won't overrun the bounds of the array
      if (i <= length-width*4) {

        // if it's not an alpha
        if ((i+1) % 4 !== 0) {

          // if it's the last pixel in the row, there is
          // no pixel to the right, so copy previous pixel's
          // values.
          if ((i+4) % (width*4) == 0) {
            data[i] = data[i-4];
            data[i+1] = data[i-3];
            data[i+2] = data[i-2];
            data[i+3] = data[i-1];
            i+=4;
          } else { // not the last pixel in the row
            data[i] = 255/2  // Average value
                      + 2*data[i]   // current pixel
                      - data[i+4]   // next pixel
                      - data[i+width*4]; // pixel underneath
          }
        }
      } else if ((i+1) % 4 !== 0) { // last row, no pixels underneath, so copy pixel above
        data[i] = data[i-width*4];
      }
    }
    this.imgContext.putImageData(imgdata, 0, 0);
    if (!this.imgCmdStack) {
      this.imgCmdStack = ["E"];
    }else{
      this.imgCmdStack.push("E");
    }
  }
};

