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
* imageViewer object
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  viewer
*/
var VIEWERS = VIEWERS || {};

/**
* Constructor for Image Viewer Object
*
* @type Object
*
* @param imgVCfg is a JSON object with the following possible properties
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

VIEWERS.ImageViewer =  function(imgVCfg) {
  var imgV = this, imgFilename,imgSrc, posLookup, imgLookup, polygonLookup,
      imgContainerDiv = $('<div id="imgContainerDiv" />');
  //read configuration and set defaults
  this.config = imgVCfg;
  this.type = "ImageV";
  this.id = imgVCfg['id'] ? imgVCfg['id']: null;
  this.dbName = imgVCfg['dbName'] ? imgVCfg['dbName']: dbName;
  this.basepath = imgVCfg['basepath'] ? imgVCfg['basepath']: basepath;
  this.imgCanvas = imgVCfg['imageCanvas'] ? imgVCfg['imageCanvas']:null;
  this.zoomFactor = imgVCfg['zoomFactor'] ? imgVCfg['zoomFactor']:70;
  this.vpOffset = imgVCfg['initViewportOffset'] && !isNaN(imgVCfg['initViewportOffset'].x) && !isNaN(imgVCfg['initViewportOffset'].y) ? imgVCfg['initViewportOffset']:{x:0,y:0};
  this.zoomFactorRange = { min:20,max:250,inc:2 };
  this.imgViewContainer = imgVCfg['imgContainerDiv']?imgVCfg['imgContainerDiv']: $('#imageViewerContent').get(0);
  this.$imgViewHeader = imgVCfg['imgViewHeader']?imgVCfg['imgViewHeader']: $('#imageViewerHdr');
  this.polygons = [];
  this.syncY = this.newTop = 0;
  this.selectedPolygons = {};
  this.$downloadLink = $('<a href="#" " class="downloadlink" download/>');
  $('body').append(this.$downloadLink);
  this.viewAllPolygons = false; //intially hide any polygons
  posLookup = imgVCfg['posLookup']?imgVCfg['posLookup']: null;
  imgLookup = imgVCfg['imgLookup']?imgVCfg['imgLookup']: null;
  polygonLookup = imgVCfg['polygonLookup']?imgVCfg['polygonLookup']: null;
  this.initData(posLookup,imgLookup,polygonLookup);
  //create canvas if needed and attach to container
  if (!this.imgCanvas && this.imgViewContainer) {
    this.imgCanvas = document.createElement('canvas');
    this.imgCanvas.tabIndex = 1;
    this.imgViewContainer.appendChild(this.imgCanvas);
  }

  //adjust initial size of image canvas
  if (this.imgViewContainer) {
    this.imgCanvas.width = this.imgViewContainer.clientWidth;
    this.imgCanvas.height = this.imgViewContainer.clientHeight-30;
    //drag resize - note for smoother adjust change this to mousemove
    $(this.imgViewContainer).unbind('mouseup').bind('mouseup',function(e) {
              DEBUG.log("gen","resize called");
              if (imgV.image.height && imgV.image.width && (imgV.imgCanvas.width != imgV.imgViewContainer.clientWidth ||
                  imgV.imgCanvas.height != imgV.imgViewContainer.clientHeight)) {
                imgV.imgCanvas.width = imgV.imgViewContainer.clientWidth;
                imgV.imgCanvas.height = imgV.imgViewContainer.clientHeight-30;
                imgV.imgContext = imgV.imgCanvas.getContext('2d');
                imgV.imgContext.imageSmoothingEnabled = false;
                imgV.vpSize.width = imgV.imgCanvas.width *100 / imgV.zoomFactor;
                imgV.vpSize.height = imgV.imgCanvas.height *100 / imgV.zoomFactor;
                imgV.draw();
              }
    });
  }
  //setup canvas and context
  this.imgCanvas.className = "imgCanvas";

  this.imgCanvas.onmouseleave = function(e) {
    delete this.focusMode;
  };
  this.imgCanvas.onmouseenter = function(e) {
    if (!this.focusMode) {
      this.focusMode = "mouseIn";
    }
  };

  //setup image ui tools
  if (this.imgViewContainer) {
    this.$resSourceDiv = $('<div class="sourceLine">Source: unknown</div>');
    $(this.imgViewContainer).append(this.$resSourceDiv);
  }
  if (this.$imgViewHeader) {
    this.$baselineMenu = $('<div id="imgVBlnMenu" class="menuLabelDiv" title="Select baseline to view">Linked</div>');
    this.$imgViewHeader.append(this.$baselineMenu);
    this.$blnMenuPanel = $('<div id="baselines" class="menuPanel" />');
    $('body').append(this.$blnMenuPanel);
    this.$imgMenu = $('<div id="imgVImgMenu" class="menuLabelDiv" title="Select Img to view">Other</div>');
    this.$imgViewHeader.append(this.$imgMenu);
    this.$imgMenuPanel = $('<div id="images" class="menuPanel"/>');
    $('body').append(this.$imgMenuPanel);
    this.$zoomDIV = $('<div class="zoomUI"><div id="zoomOut" title="Zoom Out" class="zoomButton">-</div><div id="zoomIn" title="Zoom In" class="zoomButton">+</div></div>').get(0);
    this.$imgViewHeader.append(this.$zoomDIV);
    this.initImageUI();
  }
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
VIEWERS.ImageViewer.prototype = {
  // configure all values that require the dom image and view elements to be loaded first.

/**
* put your comment there...
*
*/

  initData: function(posLookup,imgLookup,polygonLookup) {
    this.posLookup = posLookup;
    this.imgLookup = imgLookup;
    this.polygonLookup = polygonLookup;
    this.polygons = [];
    this.imgSrc = null;
    this.syncY = this.newTop = 0;
    this.selectedPolygons = {};
    this.viewAllPolygons = false; //intially hide any polygons
    //******** viewer change  DATA lookup URL by blnID/imgID
    if (this.imgLookup && this.imgLookup.bln && Object.keys(this.imgLookup.bln).length) {
      this.entTag = Object.keys(this.imgLookup.bln)[0];
      this.imgInfo = this.imgLookup.bln[this.entTag];
      this.imgSrc = this.imgInfo['url'];
    } else if (this.imgLookup && this.imgLookup.img && Object.keys(this.imgLookup.img).length) {
      this.entTag = Object.keys(this.imgLookup.img)[0];
      this.imgInfo = this.imgLookup.img[this.entTag];
      this.imgSrc = this.imgInfo['url'];
    }
    if (this.imgSrc) {
      this.imgFilename = this.imgSrc.substring(this.imgSrc.lastIndexOf("/")+1);
    }
  },


/**
* put your comment there...
*
*/

  initImageUI: function() {
    var imgV = this, blnImgTags = [], otherImgTags = [],sourceNames = [], atbID;
    if (this.$resSourceDiv && this.imgInfo) {
      this.imgTitle = this.imgInfo.title;
      if (this.imgInfo.source && Object.keys(this.imgInfo.source).length) {
        for (atbID in this.imgInfo.source) {
          sourceNames.push(this.imgInfo.source[atbID]);
        }
      } else {
        sourceNames.push("unknown");
      }
      this.$resSourceDiv.html((this.imgTitle?('('+this.imgTitle+')'):'')+' Source: '+sourceNames.join(','));
    }
    //clear menu panels
    this.$blnMenuPanel.html("");
    this.$imgMenuPanel.html("");
    //setup menus from lookup data
    if (this.imgLookup && this.imgLookup.bln && Object.keys(this.imgLookup.bln).length) {
      imgV.$baselineMenu.addClass('showMenu');
      for (lkupID in this.imgLookup.bln) {
        blnInfo = this.imgLookup.bln[lkupID];
        blnID = lkupID.substr(3);
        resLabel = blnInfo.title;
        blnImgTags.push(blnInfo.imgTag);
        elemID = "blnLkupID" + lkupID;
        thumbUrl = (blnInfo.thumbUrl?blnInfo.thumbUrl:blnInfo.url);
        downloadURL = (this.basepath?this.basepath:'')+'/services/downloadImage.php?'+
                      (this.dbName?'db='+this.dbName+'&':'')+
                      (blnInfo.url?'url='+blnInfo.url:'blnID='+blnID);
        $resDiv =$('<div id="'+elemID+'" class="imgmenuresource"><img src="'+thumbUrl+'" class="resImageIconBtn"/>' +
                    resLabel +'<a href="'+downloadURL+'" download title="Download '+resLabel+'"><div class="downloadbtndiv"/></a></div>');//&#x2193;</a></div>');
        $resDiv.prop('lkupID',lkupID);
        $resDiv.unbind('click').bind('click', function(e) {
          var lkupID = $(this).prop('lkupID');
          if (!$(e.target).hasClass('downloadbtndiv')) {
            imgV.loadBaselineAt(lkupID, {x:(imgV.vpLoc?imgV.vpLoc.x:0), y:0});
            imgV.$blnMenuPanel.removeClass('showMenu');
            e.stopImmediatePropagation();
            return false;
          } else {
            setTimeout(function(){ imgV.$blnMenuPanel.removeClass('showMenu');},500);
          }
        });
        this.$blnMenuPanel.append($resDiv);
      }
      this.$baselineMenu.unbind('click').bind('click', function(e) {
        var offset = imgV.$baselineMenu.offset();
        imgV.$blnMenuPanel.css('left',""+(offset.left)+"px");
        imgV.$blnMenuPanel.css('top',""+(offset.top+20)+"px");
        if (imgV.$imgMenuPanel && imgV.$imgMenuPanel.hasClass('showMenu')) {
          imgV.$imgMenuPanel.removeClass('showMenu');
        }
        imgV.$blnMenuPanel.toggleClass('showMenu');
        imgV.$blnMenuPanel.focus();
        e.stopImmediatePropagation();
        return false;
      });
        this.$blnMenuPanel.unbind('blur').bind('blur', function(e) {
          if ($(this).hasClass('showMenu')) {
            $(this).removeClass('showMenu');
          }
          e.stopImmediatePropagation();
          return false;
        });
    } else {
      imgV.$baselineMenu.removeClass('showMenu');
    }
    if (this.imgLookup && this.imgLookup.img && Object.keys(this.imgLookup.img).length) {
      for (lkupID in this.imgLookup.img) {
        if (blnImgTags.indexOf(lkupID) == -1){//non baseline image so other
          otherImgTags.push(lkupID);
        }
      }
      if (otherImgTags.length > 0) {
        imgV.$imgMenu.addClass('showMenu');
        for (otherIndex in otherImgTags) {
          lkupID = otherImgTags[otherIndex];
          imgID = lkupID.substr(3);
          imgInfo = this.imgLookup.img[lkupID];
          resLabel = imgInfo.title;
          elemID = "imgLkupID" + lkupID;
          thumbUrl = (imgInfo.thumbUrl?imgInfo.thumbUrl:imgInfo.url);
          downloadURL = (this.basepath?this.basepath:'')+'/services/downloadImage.php?'+
                        (this.dbName?'db='+this.dbName+'&':'')+
                        (imgInfo.url?'url='+imgInfo.url:'imgID='+imgID);
          $resDiv =$('<div id="'+elemID+'" class="imgmenuresource"><img src="'+thumbUrl+'" class="resImageIconBtn"/>' +
                      resLabel +'<a href="'+downloadURL+'" download title="Download '+resLabel+'"><div class="downloadbtndiv"/></a></div>');//&#x2193;</a></div>');
          $resDiv.prop('lkupID',lkupID);
          $resDiv.unbind('click').bind('click', function(e) {
            var lkupID = $(this).prop('lkupID');
            if (!$(e.target).hasClass('downloadbtndiv')) {
              imgV.loadImage(lkupID);
              imgV.$imgMenuPanel.removeClass('showMenu');
              e.stopImmediatePropagation();
              return false;
            } else {
              setTimeout(function(){ imgV.$imgMenuPanel.removeClass('showMenu');},500);
            }
         });
          this.$imgMenuPanel.append($resDiv);
        }
        this.$imgMenu.unbind('click').bind('click', function(e) {
          var offset = imgV.$imgMenu.offset();
          imgV.$imgMenuPanel.css('left',""+(offset.left)+"px");
          imgV.$imgMenuPanel.css('top',""+(offset.top+20)+"px");
          if (imgV.$blnMenuPanel && imgV.$blnMenuPanel.hasClass('showMenu')) {
            imgV.$blnMenuPanel.removeClass('showMenu');
          }
          imgV.$imgMenuPanel.toggleClass('showMenu');
          imgV.$imgMenuPanel.focus();
          e.stopImmediatePropagation();
          return false;
        });
        this.$imgMenuPanel.unbind('blur').bind('blur', function(e) {
          if ($(this).hasClass('showMenu')) {
            $(this).removeClass('showMenu');
          }
          e.stopImmediatePropagation();
          return false;
        });
      } else {
        imgV.$imgMenu.removeClass('showMenu');
      }
    } else {
      imgV.$imgMenu.removeClass('showMenu');
    }
    if (! this.image) {
      this.image = new Image();
      this.image.onload = function(e) {
        imgV.loadWordPolygons();
        imgV.imgContext = imgV.imgCanvas.getContext('2d');
        imgV.imgContext.imageSmoothingEnabled = false;
        imgV.initImage();
      };
      this.image.crossOrigin = "anonymous";
    }
    if (this.image.src != this.imgSrc) {
      this.image.src = this.imgSrc;
    } else {
      imgV.loadWordPolygons();
      imgV.initImage();
    }
  },


/**
* put your comment there...
*
*/

  initImage: function() {
    this.imgAspectRatio = this.image.width/this.image.height;
    this.addEventHandlers();
    this.initViewport();
    this.draw();
  },


/**
* put your comment there...
*
*/

  loadBaselineAt: function(blnTag, offset) {
    var sourceNames = [], atbID,
        blnInfo = this.imgLookup.bln[blnTag];
    this.imgInfo = blnInfo;
    this.entTag = blnInfo.tag;
    if (offset && (offset.x || offset.x == 0) && (offset.y || offset.y == 0)) {
      this.vpOffset.x = !isNaN(offset.x)?offset.x:0;
      this.vpOffset.y = !isNaN(offset.y)?offset.y:0;
    }
    this.image.src = blnInfo.url;
    this.imgTitle = blnInfo.title;
    if (blnInfo.source && Object.keys(blnInfo.source).length) {
      for (atbID in blnInfo.source) {
        sourceNames.push(blnInfo.source[atbID]);
      }
    } else {
      sourceNames.push("unknown");
    }
    this.$resSourceDiv.html((this.imgTitle?('('+this.imgTitle+')'):'')+' Source: '+sourceNames.join(','));
  },


/**
* put your comment there...
*
*/

  loadImage: function(imgTag) {
    var sourceNames = [], atbID,
        imgInfo = this.imgLookup.img[imgTag];
    this.imgInfo = imgInfo;
    this.entTag = imgInfo.tag;
    this.vpOffset.x = 0;
    this.vpOffset.y = 0;
    this.image.src = imgInfo.url;
    this.imgTitle = imgInfo.title;
    if (imgInfo.source && Object.keys(imgInfo.source).length) {
      for (atbID in imgInfo.source) {
        sourceNames.push(imgInfo.source[atbID]);
      }
    } else {
      sourceNames.push("unknown");
    }
    this.$resSourceDiv.html((this.imgTitle?('('+this.imgTitle+')'):'')+' Source: '+sourceNames.join(','));
  },


/**
* put your comment there...
*
*/

  loadWordPolygons: function () {
    this.polygons = [];
    this.selectedPolygons = {};
    if (this.entTag && this.polygonLookup && this.polygonLookup[this.entTag] &&
        Object.keys(this.polygonLookup[this.entTag]).length > 0) {
      for (wordTag in this.polygonLookup[this.entTag]) {
        polygons = this.polygonLookup[this.entTag][wordTag];
        if (polygons.length > 1) {//multiple polygons so find centroid of centriods
          centers = [];
          for (i in polygons) {
            centers.push(UTILITY.getCentroid(polygons[i]));
          }
          center = UTILITY.getCentroid(centers);
        } else if (polygons.length == 1) {
          center = UTILITY.getCentroid(polygons[0])
        } else {
          center = null;
        }
        this.addImagePolygon(polygons,wordTag,false,[wordTag],center,null);//WARNING!! todo code change boundary can be multiple polygons
      }
    }
 },


/**
* put your comment there...
*
*/

  resize: function () {
    this.initImage();
  },


/**
* sets the focus on the canvas element
*
*/

  setFocus: function () {
    this.imgCanvas.focus();
  },


/**
* put your comment there...
*
*/

  initViewport: function () {
    var vpWidth ,vpHeight;
    this.zoomFactor = this.imgCanvas.width/this.image.width * 100;

    vpWidth  = this.imgCanvas.width *100 / this.zoomFactor;
    vpHeight = this.imgCanvas.height *100 / this.zoomFactor;
    this.vpSize = { width: vpWidth || 500, height: vpHeight };
    this.vpMaxLoc = { x: this.image.width, y: this.image.height };
    this.vpLoc = { x: 0, y: this.vpMaxLoc.y/2 };
    this.vpLastLoc =  { x: 0, y: 0 };
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
    var imgV = this, index = null;
    if (this.polygonLookup[label]) {
      index = this.polygonLookup[label];
    } else if (label.match(/seg/)) { //check mapped segments
      segment = this.dataMgr.getEntityFromGID(label);
      if (segment && segment.mappedSegIDs && segment.mappedSegIDs.length) {
        $.each(segment.mappedSegIDs, function(i,val) {
          if (!index && imgV.polygonLookup['seg'+val]) {
            index = imgV.polygonLookup['seg'+val];
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

  addImagePolygon: function (polygons,label,visible,linkIDs,center,ordinal) {
    //todo add code to validate the polygon
    var clr = "blue";
    if (!linkIDs){
      clr = "red";
    }
    this.polygons.push({polygons:polygons,
                        center: center,
                        order: ordinal?ordinal:null,
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
    var imgV = this;
    if (this.polygonLookup[label]) {
      this.selectedPolygons[label] = 1;
    } else if (label.match(/seg/)) { //check mapped segments
      segment = this.dataMgr.getEntityFromGID(label);
      if (segment && segment.mappedSegIDs && segment.mappedSegIDs.length) {
        $.each(segment.mappedSegIDs, function(i,val) {
          if (imgV.polygonLookup['seg'+val]) {
            imgV.selectedPolygons['seg'+val] = 1;
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
* @param posY
*/

  moveViewportToImagePosY: function(posY) {
    this.vpLoc.y = Math.max(0, Math.min(posY, this.vpMaxLoc.y));
    this.vpLastLoc.y = this.vpLoc.y;
  },


/**
* put your comment there...
*
* @param refViewCorner
*/

  findNearestPoly: function (refType,visibleOnly) {
    var imgV = this,
        x = imgV.vpLoc.x * imgV.image.width / imgV.imgCanvas.width,
        y = imgV.vpLoc.y * imgV.image.height / imgV.imgCanvas.height,
        xmax = (imgV.vpSize.width + imgV.vpLoc.x) * imgV.image.width / imgV.imgCanvas.width,
        ymax = (imgV.vpSize.height + imgV.vpLoc.y) * imgV.image.height / imgV.imgCanvas.height,
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
    var i,j,polygonObj, hitPolyIndices = [], polygons,
        cnt = this.polygons.length;
    for (i=0; i < cnt; i++) {
      polygonObj = this.polygons[i];
      polygons = polygonObj.polygons;
      for (j=0; j<polygons.length; j++) {
        if (this.pointInPath(this.imgContext,x,y,polygons[j])) {
          hitPolyIndices.push(i+1);// indices are store from 1 array is zero based
          break;
        }
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
    var bbox = canvas.getBoundingClientRect(),clientX,clientY,i;
    if (event.clientX) {
      clientX = event.clientX;
      clientY = event.clientY;
      return [Math.round(clientX - bbox.left * (canvas.width  / bbox.width)),
              Math.round(clientY - bbox.top  * (canvas.height / bbox.height))];
    } else if (event.type.indexOf("touch") != -1 && event.originalEvent) {// todo add code for touch zoom
      clientX = event.originalEvent.touches[0].clientX;
      clientY = event.originalEvent.touches[0].clientY;
      return [Math.round(clientX - bbox.left * (canvas.width  / bbox.width)),
              Math.round(clientY - bbox.top  * (canvas.height / bbox.height))];
    } else {
      return [0,0];
    }
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
                              Math.min(this.zoomFactorRange.max,this.zoomFactor + ( this.zoomFactorRange.inc * delta)));
    var xNew = Math.round(xNavAllign - xScaleFactor * this.vpSize.width),
        yNew = Math.round(yNavAllign - yScaleFactor * this.vpSize.height);
    this.moveViewport([xNew,yNew], [0,0]);
    this.vpSize.width = this.imgCanvas.width *100 / this.zoomFactor;
    this.vpSize.height = this.imgCanvas.height *100 / this.zoomFactor;

    this.draw();
    DEBUG.log("gen", "delta="+delta+
                      " xScaleFactor="+xScaleFactor+
                      " yScaleFactor="+yScaleFactor+
                      " xNavAllign="+xNavAllign+
                      " yNavAllign="+yNavAllign+
                      " xNew="+xNew+
                      " yNew="+yNew+
                      " zoomFactor="+this.zoomFactor);
    e.preventDefault();//stop window scroll, for dual purpose could use CTRL key to disallow default
    return false;
  },


/**
* put your comment there...
*
* @returns int array representing the center point [x,y] of the viewport
*/

  getViewportCenter: function () {
    return [this.vpSize.width/2 + this.vpLoc.x, this.vpSize.height/2 + this.vpLoc.y];
  },

/**
* put your comment there...
*
* @param direction int indicates zoomin (+1) or zoomout (-1)
*/

  zoomCenter: function (direction) {
    this.zoomPoint(direction,this.getViewportCenter())
  },

/**
* put your comment there...
*
* @param direction
*/

  zoomPoint: function (direction, zoomPoint) {
    var xImgAllign = zoomPoint[0],
        yImgAllign = zoomPoint[1];
    this.zoomFactor = Math.max(this.zoomFactorRange.min,
                              Math.min(this.zoomFactorRange.max,this.zoomFactor + ( this.zoomFactorRange.inc * direction)));
    this.vpSize.width = this.imgCanvas.width *100 / this.zoomFactor;
    this.vpSize.height = this.imgCanvas.height *100 / this.zoomFactor;
    var xNew = Math.round(xImgAllign - this.vpSize.width/2),
        yNew = Math.round(yImgAllign - this.vpSize.height/2);
    this.moveViewport([xNew,yNew], [0,0]);
    this.draw();
  },


/**
*
*/

  addEventHandlers: function() {
    var imgV = this;
  // navDiv events
    $('#zoomOut',imgV.$zoomDIV).unbind('click').bind('click', function(e) {
        setTimeout(function() {imgV.zoomCenter.call(imgV,-1);},50);
        e.stopImmediatePropagation();
        return false;
      });

    $('#zoomIn',imgV.$zoomDIV).unbind('click').bind('click', function(e) {
        setTimeout(function() {imgV.zoomCenter.call(imgV,1);},50);
        e.stopImmediatePropagation();
        return false;
      });

    //image canvas events
    //focus
    imgV.imgCanvas.onmouseleave = function(e) {
      delete this.focusMode;
    };
    imgV.imgCanvas.onmouseenter = function(e) {
      if (!this.focusMode) {
        this.focusMode = "mouseIn";
      }
    };
    // wheel zoom
    imgV.imgCanvas.onwheel = function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgV.id);
      imgV.handleWheel.call(imgV,e); //delegate passing imgV as context
    }
    imgV.imgCanvas.onmousewheel = function(e) {
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgV.id);
      imgV.handleWheel.call(imgV,e); //delegate passing imgV as context
    };
    imgV.imgCanvas.onkeydown = function(e) {
      var keyCode = (e.keyCode || e.which);
      if (keyCode > 36 && keyCode <41) {
        switch (e.keyCode || e.which) {
          case 38://'Up':
            if (e.ctrlKey || e.metaKey) {
              imgV.moveViewportRelative(0,-15);
            } else {
              imgV.moveViewportRelative(0,-2);
            }
            break;
          case 40://'Down':
            if (e.ctrlKey || e.metaKey) {
              imgV.moveViewportRelative(0,15);
            } else {
              imgV.moveViewportRelative(0,2);
            }
            break;
          case 37://"Left":
            if (e.ctrlKey || e.metaKey) {
              imgV.moveViewportRelative(-15,0);
            } else {
              imgV.moveViewportRelative(-2,0);
            }
            break;
          case 39://"Right":
            if (e.ctrlKey || e.metaKey) {
              imgV.moveViewportRelative(15,0);
            } else {
              imgV.moveViewportRelative(2,0);
            }
            break;
        }
        imgV.draw();
        e.stopImmediatePropagation();
        return false;
      }
    };
    imgV.imgCanvas.onkeypress = function(e) {
      var key = e.which == null?String.fromCharCode(e.keyCode):
                (e.which != 0 )?String.fromCharCode(e.which):null;
//      alert('-keypress img in imageVE '+key);
      if (key == '+' || key == '=') {
        imgV.zoomCenter.call(imgV,1);
      } else if (key == '-'){
        imgV.zoomCenter.call(imgV,-1);
      }
      e.stopImmediatePropagation();
      return false;
    };

    imgV.imgCanvas.ondblclick = function (e){
        var pt = imgV.eventToCanvas(e, imgV.imgCanvas);
        //adjust point to be image coordinates
        var x = imgV.vpLoc.x + imgV.vpSize.width/imgV.imgCanvas.width * pt[0],
            y = imgV.vpLoc.y + imgV.vpSize.height/imgV.imgCanvas.height * pt[1],
            i,index,gid,bBox;
        //hittest for target polygons
        var hitPolyIndices = imgV.hitTestPolygons(x,y);
        //unselect existing if no ctrl key pressed
        if (!e.ctrlKey || !e.metaKey) {
          imgV.selectedPolygons = {};
        }
        //add indices to selected array
        for (i=0; i < hitPolyIndices.length; i++) {
          index = hitPolyIndices[i];
          imgV.selectedPolygons[imgV.polygons[index -1].label] = 1;
        }
        //redraw
        imgV.drawImage();
        imgV.drawImagePolygons();
        $('.viewerContent').trigger('updateselection',[imgV.id,imgV.getSelectedPolygonLabels()]);
    };

    imgV.imgCanvas.onclick = function (e){
      var pt = imgV.eventToCanvas(e, imgV.imgCanvas);
      //adjust point to be image coordinates
      var x = imgV.vpLoc.x + imgV.vpSize.width/imgV.imgCanvas.width * pt[0],
          y = imgV.vpLoc.y + imgV.vpSize.height/imgV.imgCanvas.height * pt[1],
          i,index;
      if (!imgV.focusMode || imgV.focusMode != 'focused') {
        imgV.focusMode = 'focused';
        imgV.imgCanvas.focus();
      }
      if (closeAllPopups && typeof closeAllPopups == "function") {
        closeAllPopups();
      }
      if (imgV.$imgMenuPanel && imgV.$imgMenuPanel.hasClass('showMenu')) {
        imgV.$imgMenuPanel.removeClass('showMenu');
      }
      if (imgV.$blnMenuPanel && imgV.$blnMenuPanel.hasClass('showMenu')) {
        imgV.$blnMenuPanel.removeClass('showMenu');
      }
      if (e.ctrlKey || e.metaKey) {
        //hittest for target polygons
        var hitPolyIndices = imgV.hitTestPolygons(x,y);
        //add indices to selected array
        for (i=0; i < hitPolyIndices.length; i++) {
          index = hitPolyIndices[i];
          imgV.selectedPolygons[imgV.polygons[index -1].label] = 1;
        }
        //redraw
        imgV.drawImage();
        imgV.drawImagePolygons();
        $('.viewerContent').trigger('updateselection',[imgV.id,imgV.getSelectedPolygonLabels()]);
      }
    };

    imgV.rbRect,imgV.drgStart,imgV.rbImageData = null;
    $(imgV.imgCanvas).unbind("mousedown touchstart").bind("mousedown touchstart", function (e){
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgV.id);
      //set cursor to grabbing and flag dragnavigation
      if (e.buttons == 1 || e.type == "touchstart") { // left mouse button user might be doing drag navigation
        imgV.imgCanvas.style.cursor = 'pointer';
      //store drag start
        imgV.drgStart = imgV.eventToCanvas(e, imgV.imgCanvas);
        imgV.dragnav = 'down';
        e.preventDefault();
        return;
      }
    });

    $(imgV.imgCanvas).unbind("mousemove touchmove").bind("mousemove touchmove", function (e){
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgV.id);
      if (e.buttons == 1 || e.type == "touchmove") { // left mouse button with move user is drag navigation
        if (e.type == "touchmove" && e.originalEvent.touches.length > 1) {//2 or more touches so treat as zoom
          touchPoints = imgV.eventToCanvas(e, imgV.imgCanvas);
        } else {
          imgV.dragnav = 'move';
          imgV.imgCanvas.style.cursor = 'grabbing';
          //get new postion
          newPos = imgV.eventToCanvas(e, imgV.imgCanvas);
          //move image to new location
          imgV.moveViewportRelative((imgV.drgStart[0] - newPos[0])*100/imgV.zoomFactor,(imgV.drgStart[1] - newPos[1])*100/imgV.zoomFactor);
          imgV.drgStart = newPos;
          imgV.draw()
        }
      }
    });

    $(imgV.imgCanvas).unbind("mouseup touchend").bind("mouseup touchend", function (e){
      DEBUG.log("event", "type: "+e.type+(e.code?" code: "+e.code:"")+" in imageVE canvas "+imgV.id);
      if (imgV.dragnav == 'move' || imgV.dragnav == 'down') {
        delete imgV.dragnav;
        imgV.imgCanvas.style.cursor = 'crosshair';
        e.stopImmediatePropagation();
        return false;
      }
    });


/**
* put your comment there...
*
* @param object e System event object
* @param senderID
* @param selectionIDs
*/

    function updateSelectionHandler(e,senderID, selectionIDs) {
      if (senderID == imgV.id) {
        return;
      }
      DEBUG.log("event","selection changed received by imageVE in "+imgV.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      imgV.unselectAllPolygons();
      if (selectionIDs && selectionIDs.length) {
        $.each(selectionIDs, function(i,val) {
          if (val && val.length) {
           imgV.selectPolygonByName(val);
          }
        });
      }
      imgV.drawImage();
      imgV.drawImagePolygons();
    };

    $(imgV.imgViewContainer).unbind('updateselection').bind('updateselection', updateSelectionHandler);


    /**
    * handle 'synchronize' event for img view
    *
    * @param object e System event object
    * @param string senderID Identifies the sending view pane for recursion control
    * @param string lineSeqTag tag of line sequence anchor nearest the top of the view
    * @param number lineFraction is the fraction of display viewed relative to the anchor entity
    * @param string hdrSeqTag tag of structure sequence anchor nearest the top of the view
    * @param number hdrFraction is the fraction of display viewed relative to the structure anchor entity
    */

    function imgSynchronizeHandler(e,senderID,lineSeqTag,lineFraction,hdrSeqTag,hdrFraction,scrViewHeight,imgScrollData) {
      var $view = $(this), newBln = null, newTop = null, imgPosHash = "", syncY;
      if (senderID == imgV.id || !$view.parent().hasClass('syncScroll') || imgV.entTag.match(/img/)) {
        return;
      }
//      DEBUG.log("event","synchronize recieved by imageV in "+imgV.id+" from "+senderID+" with line anchor seqID "+ lineSeqTag+" and visibility fraction "+lineFraction+" and with header anchor seqID "+ hdrSeqTag+" and visibility fraction "+hdrFraction+" screen view height "+scrViewHeight);
      if (imgV.posLookup) {
        if (lineSeqTag && imgV.posLookup.line[lineSeqTag]) {
          if (imgV.entTag != imgV.posLookup.line[lineSeqTag].blnTag) {
            newBln = imgV.posLookup.line[lineSeqTag].blnTag;
          }
          newTop = Math.max( 0, imgV.posLookup.line[lineSeqTag].y + imgV.posLookup.line[lineSeqTag].h * lineFraction);
          syncY =imgV.posLookup.line[lineSeqTag].y;
        }
        if (hdrSeqTag && imgV.posLookup.construct[hdrSeqTag]
            && (newTop == null ||
                imgV.posLookup.line[lineSeqTag].blnTag != imgV.posLookup.construct[hdrSeqTag].blnTag
                && Math.abs(hdrFraction) < Math.abs(lineFraction))) {
          newBln = null;
          if (imgV.entTag != imgV.posLookup.construct[hdrSeqTag].blnTag) {
            newBln = imgV.posLookup.construct[hdrSeqTag].blnTag;
          }
          newTop = Math.max( 0, imgV.posLookup.construct[hdrSeqTag].y + imgV.posLookup.construct[hdrSeqTag].h * hdrFraction);
          syncY =imgV.posLookup.construct[hdrSeqTag].y;
        }
      }
      imgV.unselectAllPolygons();
//      DEBUG.log("warn","synchronize lSegID:"+ lineSeqTag+"("+lineFraction+") hSeqID:"+ hdrSeqTag+"("+hdrFraction+")" );
//      DEBUG.log("warn","synchronize y:"+ imgV.syncY+"-"+syncY+"   newtop:"+ imgV.newTop+"-"+newTop+")");
      if (newBln || imgV.syncY >= syncY && imgV.newTop >= newTop || imgV.syncY <= syncY && imgV.newTop <= newTop) {
        imgV.syncY = syncY;
        imgV.newTop = newTop;
        if (!newBln && newTop != null) {
          imgV.moveViewportToImagePosY(newTop);
          imgV.draw();
        } else if (newTop != null){
          imgV.loadBaselineAt(newBln, {x:imgV.vpLoc.x, y:newTop});
        }
      }
    };

    $(imgV.imgViewContainer).unbind('synchronize').bind('synchronize', imgSynchronizeHandler);

  },


/**
* put your comment there...
*
*/

  drawImage: function() {
    this.imgContext.clearRect(0,0,this.imgCanvas.width,this.imgCanvas.height);
    this.imgContext.imageSmoothingEnabled = false;
    this.imgContext.drawImage(this.image,
      this.vpLoc.x ,
      this.vpLoc.y ,
      this.vpSize.width,
      this.vpSize.height,
      0, 0,
      this.imgCanvas.width,
      this.imgCanvas.height);
  },


/**
* put your comment there...
*
*/

  drawImagePolygons: function () {
    var imgV = this;
    if (!this.polygons || !this.polygons.length) return;
    this.imgContext.save();
    var i,j,k,polygonObj, polygons, polygon,
        offsetX = imgV.vpLoc.x * imgV.zoomFactor/100,// * imgV.imgCanvas.width / imgV.image.width,
        scaleX = imgV.imgCanvas.width/imgV.vpSize.width,//*imgV.imgCanvas.width/imgV.image.width,
        offsetY = imgV.vpLoc.y* imgV.zoomFactor/100,// * imgV.imgCanvas.height / imgV.image.height,
        scaleY = imgV.imgCanvas.height/imgV.vpSize.height;//*imgV.imgCanvas.height/imgV.image.height;
    this.imgContext.font = "1.4em arial";
    this.imgContext.textAlign = "center";
    this.imgContext.textBaseline = "middle";
    this.imgContext.lineWidth = 2;
    for(i=0;i<this.polygons.length; i++) {
      polygonObj = this.polygons[i];
      if (!polygonObj || polygonObj.hidden && !polygonObj.hilite && !this.selectedPolygons[polygonObj.label]) {
        continue;
      }
      if ( polygonObj.polygons && polygonObj.polygons.length ) {
        this.imgContext.strokeStyle =  polygonObj.color;
        polygons = polygonObj.polygons;
        for (j=0; j<polygons.length; j++) {
          this.imgContext.beginPath();
          polygon = polygons[j];
          this.imgContext.moveTo((polygon[0][0]*scaleX - offsetX),(polygon[0][1]*scaleY - offsetY));
          //this.imgContext.moveTo((polygon[0][0] - offsetX)*scaleX,(polygon[0][1] - offsetY)*scaleY);
          for (k=1; k < polygon.length; k++) {
            this.imgContext.lineTo((polygon[k][0]*scaleX - offsetX),(polygon[k][1]*scaleY - offsetY));
           // this.imgContext.lineTo((polygon[k][0] - offsetX)*scaleX,(polygon[k][1] - offsetY)*scaleY);
          }
          this.imgContext.closePath();
          this.imgContext.stroke();
        }
      }
    }
    this.imgContext.restore();
  },

/**
* put your comment there...
*
*/

  draw: function() {
    this.drawImage();
    this.drawImagePolygons();
  },

};

