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
* editors LemmaVE object
*
* viewer for tokens and compounds (creates lemma when required)
* viewer editor for lemmas
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
* put your comment there...
*
* @type Object
*/

EDITORS.LemmaVE =  function(lemmaVECfg,prefix,id) {
  this.config = lemmaVECfg;
  this.catID = lemmaVECfg['catID'] ? lemmaVECfg['catID']:null;
  this.ednID = lemmaVECfg['ednID'] ? lemmaVECfg['ednID']:null;
  this.prefix = lemmaVECfg['prefix'] ? lemmaVECfg['prefix']:null;
  this.id = lemmaVECfg['id'] ? "lemVE"+lemmaVECfg['id']:null;
  this.entID = lemmaVECfg['entID'] ? lemmaVECfg['entID']:null;//lemma, token or compound
  this.wordlistVE = lemmaVECfg['editor'] ? lemmaVECfg['editor']:null;
  this.dataMgr = lemmaVECfg['dataMgr'] ? lemmaVECfg['dataMgr']:null;
  this.propMgr = lemmaVECfg['propMgr'] ? lemmaVECfg['propMgr']:null;
  this.editDiv = lemmaVECfg['editDiv'] ? $(lemmaVECfg['editDiv']):null;
  this.tag = this.prefix + this.entID;
  if (this.wordlistVE) { // install API link
    this.wordlistVE.lemmaVE = this;
  }
  this.init();
  this.data = {};
  this.dirty = false;
  return this;
};

/**
* put your comment there...
*
* @type Object
*/

EDITORS.LemmaVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("init","init lemma editor");
    if (this.dataMgr) {
      this.glAnnoType = this.dataMgr.termInfo.idByTerm_ParentLabel["glossary-commentarytype"];// warning!! term dependency
    }
    if (this.prefix && this.entID && this.dataMgr && this.dataMgr.getEntity(this.prefix,this.entID)) {
      this.showLemma();
    } else {
      this.editDiv.html('Lemma Editor');
    }
    this.editDiv.unbind("click").bind("click", function(e) {
      var $editElems = $('.edit',this);
      if ($editElems.length > 0) {
        $editElems.removeClass('edit');
      }
    });
    DEBUG.traceExit("init","init lemma editor");
  },

/*****************  property manager interface  *******************************/

/**
* put your comment there...
*
* @param gid
*/

  setEntity: function(gid) {
    DEBUG.traceEntry("setEntity");
    if (gid) {
      var tag = gid.replace(":","");
          prefix = tag.substring(0,3),
          id = tag.substring(3);
      this.showLemma(prefix,id);
    } else {
      this.showLemma();
    }
    DEBUG.traceExit("setEntity");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getType: function() {
    return "lemmaVE";
  },


/**
* put your comment there...
*
*/

  show: function() {
    DEBUG.traceEntry("show");
    this.editDiv.show();
    DEBUG.traceExit("show");
  },


/**
* put your comment there...
*
*/

  hide: function() {
    DEBUG.traceEntry("hide");
    this.editDiv.hide();
    DEBUG.traceExit("hide");
  },

/************* rebuild Lemma interface ****************/

/**
* put your comment there...
*
* @param prefix
* @param id
*/

  showLemma: function(prefix,id) {
    DEBUG.traceEntry("showLemma");
    if (prefix && id && this.dataMgr.getEntity(prefix,id)) {
      this.prefix = prefix;
      this.entID = id;
      this.tag = this.prefix + this.entID;
      this.entity = this.dataMgr.getEntity(prefix,id);
    }
    if (this.prefix && this.entID && this.entity) {
      this.isLemma = (this.prefix == "lem");
      this.editDiv.html('');
      this.createValueUI();
      if (EDITORS.config.showLemmaVEPhoneticUI) {
//        this.createPhoneticUI();
      }
      this.createDescriptionUI();
      if (EDITORS.config.showLemmaVEPhonologicalUI && 
          this.entity.gloss && 
          this.entity.gloss.indexOf(EDITORS.config.showLemmaVEPhonologicalUI) > -1) {
        this.createPhonologicalUI();
      }
      if (EDITORS.config.showLemmaDeclensionUI) {
        this.createDeclensionUI();
      }
      this.createPOSUI();
      this.createTransUI();
      if (this.isLemma) {
        this.createCompoundAnalysisUI();
        this.createLinkTypeUI()
        this.createRelatedUI()
        this.createAttestedUI();
        this.createTaggingUI();
        this.createAnnotationUI();
      }
      this.editDiv.append('<hr class="viewEndRule">');
    } else {
      this.editDiv.html('Lemma Editor - no lemma information found.');
    }
    DEBUG.traceExit("showLemma");
  },

/*************  Value Interface ****************/

/**
* put your comment there...
*
*/

  createValueUI: function() {
    var lemmaVE = this,
        value = this.isLemma ? this.entity.value.replace(/aʔi/g,'aï').replace(/aʔu/g,'aü').replace(/ʔ/g,''):this.entity.transcr.replace(/aʔi/g,'aï').replace(/aʔu/g,'aü').replace(/ʔ/g,'');
    DEBUG.traceEntry("createValueUI");
    //create UI container
    this.valueUI = $('<div class="valueUI"></div>');
    this.editDiv.append(this.valueUI);
    //create label with navigation
    this.valueUI.append($('<div class="propDisplayUI">'+
                    '<div class="propFlipNavDiv propDisplayElement"><div class="med-flip lemmaNavButton"><span/></div></div>'+
                    '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                    '<div class="lemmaNavDiv propDisplayElement"><div class="med-prevword lemmaNavButton"><span/></div><div class="med-nextword lemmaNavButton"><span/></div></div>'+
                    '</div>'));
    //create input with save button
    this.valueUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+value+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.valueUI).unbind("click").bind("click",function(e) {
        $('div.edit',lemmaVE.editDiv).removeClass("edit");
        lemmaVE.valueUI.addClass("edit");
        $('div.valueInputDiv input',this.valueUI).focus();
//        $('div.valueInputDiv input',this.valueUI).select();
        e.stopImmediatePropagation();
        return false;
      });
      $('div.valueInputDiv input',this.valueUI).unbind("click").bind("click",function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      $('div.valueInputDiv input',this.valueUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
//          lemmaVE.valueUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.valueUI).unbind("input").bind("input",function(e) {
        var curInput = $(this).val(), btnText = $('.saveDiv',lemmaVE.valueUI).html();
        if ($('div.valueLabelDiv',lemmaVE.valueUI).html() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
        if (!curInput && btnText == "Save") {
          $('.saveDiv',lemmaVE.valueUI).html("Delete").css('color','red');
        } else if (curInput && btnText != "Save") {
          $('.saveDiv',lemmaVE.valueUI).html("Save").css('color','white');
        }
      });
      //flip prop display
      $('.med-flip',this.valueUI).unbind("click").bind("click",function(e) {
        if (lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
          lemmaVE.propMgr.showVE("tabPropVE",lemmaVE.propMgr.currentVE.tag);
        }
        e.stopImmediatePropagation();
        return false;
      });
      //previous lemma
      $('.med-prevword',this.valueUI).unbind("click").bind("click",function(e) {
        if (lemmaVE.wordlistVE && lemmaVE.wordlistVE.prevWord) {
          lemmaVE.wordlistVE.prevWord();
        }
        e.stopImmediatePropagation();
        return false;
      });
      //next lemma
      $('.med-nextword',this.valueUI).unbind("click").bind("click",function(e) {
        if (lemmaVE.wordlistVE && lemmaVE.wordlistVE.nextWord) {
          lemmaVE.wordlistVE.nextWord();
        }
        e.stopImmediatePropagation();
        return false;
      });
      //save data
      $('.saveDiv',this.valueUI).unbind("click").bind("click",function(e) {
        var lemProp = {}, isSave = ($(this).html()== 'Save'),
            origText = $('div.valueLabelDiv',lemmaVE.valueUI).html();
        if (isSave) {
          if ($('.propEditUI',lemmaVE.valueUI).hasClass('dirty')) {
            val = $('div.valueInputDiv input',lemmaVE.valueUI).val();
            lemProp["value"] = val;
            $('div.valueLabelDiv',lemmaVE.valueUI).html(val);
            $('.propEditUI',lemmaVE.valueUI).removeClass('dirty');
            lemmaVE.saveLemma(lemProp);
          }
          lemmaVE.valueUI.removeClass("edit");
        } else if (confirm('Are you sure you want to delete lemma "' + origText + '"?')) { // is delete
          lemmaVE.deleteLemma();
        }
        e.stopImmediatePropagation();
        return false;
      });
    DEBUG.traceExit("createValueUI");
  },



/*************  Phonetic Interface ****************/

/**
* put your comment there...
*
*/

  createPhoneticUI: function() {
    var lemmaVE = this,
        value = (this.entity.phonetics?this.entity.phonetics:"Phonetic Analysis"),
        editValue = (this.entity.phonetics?this.entity.phonetics:'');
    DEBUG.traceEntry("createPhoneticUI");
    //create UI container
    this.phonUI = $('<div class="phonUI"></div>');
    this.editDiv.append(this.phonUI);
    //create label
    this.phonUI.append($('<div class="propDisplayUI">'+
                     '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                    '</div>'));
    //create input with save button
    this.phonUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+editValue+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.phonUI).unbind("click").bind("click",function(e) {
        $('div.edit',lemmaVE.editDiv).removeClass("edit");
        lemmaVE.phonUI.addClass("edit");
        $('div.valueInputDiv input',this.phonUI).focus();
        e.stopImmediatePropagation();
        return false;
      });
      $('div.valueInputDiv input',this.phonUI).unbind("click").bind("click",function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      $('div.valueInputDiv input',this.phonUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
//          lemmaVE.phonUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.phonUI).unbind("input").bind("input",function(e) {
        var curInput = $(this).val(), btnText = $('.saveDiv',lemmaVE.phonUI).html();
        if ($('div.valueLabelDiv',lemmaVE.phonUI).html() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
      } else if ($(this).parent().parent().hasClass("dirty")) {
        $(this).parent().parent().removeClass("dirty");
      }
      if (!curInput && btnText == "Save") {
          $('.saveDiv',lemmaVE.phonUI).html("Delete").css('color','red');
      } else if (curInput && btnText != "Save") {
          $('.saveDiv',lemmaVE.phonUI).html("Save").css('color','white');
      }
    });
    //save data
      $('.saveDiv',this.phonUI).unbind("click").bind("click",function(e) {
      var lemProp = {};
        if ($('.propEditUI',lemmaVE.phonUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.phonUI).val();
        lemProp["phonetics"] = val;
          $('div.valueLabelDiv',lemmaVE.phonUI).html(val);
          $('.propEditUI',lemmaVE.phonUI).removeClass('dirty');
        lemmaVE.saveLemma(lemProp);
      }
        lemmaVE.phonUI.removeClass("edit");
      e.stopImmediatePropagation();
      return false;
    });
    DEBUG.traceExit("createPhoneticUI");
  },

  /**
   * put your comment there...
   *
   */

  generatePhonologyStrings: function () {
    var lemma = this.entity, trimmedGloss, glossParts, etymParts,
        etymIndex, lemmaPhono, etymPhono;
    lemmaPhono = this.getPhonologyString(lemma.value);
    //parse etymology to get first form following the configured Language tag
    etymIndex = lemma.gloss.indexOf(EDITORS.config.showLemmaVEPhonologicalUI);
    if (etymIndex == -1) {
      DEBUG.log('err',EDITORS.config.showLemmaVEPhonologicalUI+
                      ' etymology language tag not found unable to generate phonolgy');
      return false;
    }
    trimmedGloss = lemma.gloss.substring(etymIndex);
    glossParts = trimmedGloss.split(',');
    etymParts = glossParts[0].split(/\s+/); //separator is one or more spaces
    if (etymParts.length < 2) {
      DEBUG.log('err',EDITORS.config.showLemmaVEPhonologicalUI+
                      ' etymology format is not recognized.'+
                       ' Use space character to separate language code from word form');
      return false;
    } else {
      etymPhono = etymParts[1].trim();
    }
    //parse it into clusters CC-V-CCH-VM
    etymPhono = this.getPhonologyString(etymPhono);
    return [etymPhono, lemmaPhono];
  },

  /**
   * put your comment there...
   *
   */

  getPhonoComparisonHtml: function (etymPhono, lemmaPhono) {
    var lemmaVE = this, lemma = this.entity, i, lPart, ePart, cnt, 
        lemmaParts, etymParts, lemmaPhonoHtml, etymPhonoHtml;
    if (lemmaPhono && (typeof lemmaPhono) == 'string' && 
        etymPhono && (typeof etymPhono) == 'string') {
      lemmaParts = lemmaPhono.split('-');
      etymParts = etymPhono.split('-');
    }
    cnt = Math.max(lemmaParts.length, etymParts.length);
    if (cnt) {
      lemmaPhonoHtml = '<div class="phonoCompare">';
      etymPhonoHtml = '<div class="phonoCompare">';
      for (i=0; i < cnt; i++) {
        lPart = ePart = '';
        if (i < lemmaParts.length){
          lPart = lemmaParts[i];
        }
        if (i < etymParts.length){
          ePart = etymParts[i];
        }
        if (lPart && !ePart){
          lemmaPhonoHtml += '<span class="phPart misMatch">'+ (i?"-":'') + lPart +'</span>';
        } else if (!lPart && ePart){
          etymPhonoHtml += '<span class="phPart misMatch">'+ (i?"-":'') + ePart +'</span>';
        } else if (lPart == ePart ||  lemmaVE._equivalanceCharMap[ePart] && 
                    lemmaVE._equivalanceCharMap[ePart] == lPart) {
          lemmaPhonoHtml += '<span class="phPart">'+ (i?"-":'') + lPart +'</span>';
          etymPhonoHtml += '<span class="phPart">'+ (i?"-":'') + ePart +'</span>';
        } else {
          lemmaPhonoHtml += '<span class="phPart misMatch">'+ (i?"-":'') + lPart +'</span>';
          etymPhonoHtml += '<span class="phPart misMatch">'+ (i?"-":'') + ePart +'</span>';
        }
      }
    }
    lemmaPhonoHtml += '<span class="phonoPartsCount">('+lemmaParts.length+')</span></div>';
    etymPhonoHtml += '<span class="phonoPartsCount">('+etymParts.length+')</span></div>';
    return [etymPhonoHtml, lemmaPhonoHtml];
  },

  /**
   * put your comment there...
   *
   */

  createPhonologicalUI: function () {
    var lemmaVE = this, lemma = this.entity, trimmedGloss, 
        glossParts, etymParts, etymIndex,
        etymPhono = (lemma.phonology ? lemma.phonology[0] :
                  (lemma.wipPhonology ? lemma.wipPhonology[0] : '')),
        ePhonoCnt = "",
        lemmaPhono = (lemma.phonology ? lemma.phonology[1] :
                  (lemma.wipPhonology ? lemma.wipPhonology[1] : '')),
        lPhonoCnt = "",
        labelValue = (lemma.phonology? lemma.phonology[0] + ' → '+ lemma.phonology[1]:
                    "Phonological Analysis");
DEBUG.traceEntry("createPhonologicalUI");
    if (!lemmaPhono) {
      lemmaPhono = this.getPhonologyString(lemma.value);
    }
    if (!etymPhono) {
      etymIndex = lemma.gloss.indexOf(EDITORS.config.showLemmaVEPhonologicalUI);
      trimmedGloss = lemma.gloss.substring(etymIndex);
      glossParts = trimmedGloss.split(',');
      etymParts = glossParts[0].split(/\s+/); //separator is one or more spaces
      if (etymParts.length < 2) {
        DEBUG.log('err',EDITORS.config.showLemmaVEPhonologicalUI+
                        ' etymology format is not recognized.'+
                         ' Use space character to separate language code from word form');
        etymPhono = 'Not Found';
      } else {
        etymPhono = etymParts[1].trim();
        etymPhono = this.getPhonologyString(etymPhono);
      }
    }
    if (!lemma.phonology) {
      ePhonoCnt = " (" + etymPhono.split('-').length + ")";
      lPhonoCnt = " (" + lemmaPhono.split('-').length + ")";
    }
    //create UI container
    this.phonoUI = $('<div class="phonoUI"></div>');
    this.editDiv.append(this.phonoUI);
    //create label
    this.phonoUI.append($('<div class="propDisplayUI">' +
      '<div class="valueLabelDiv propDisplayElement">' + labelValue + '</div>' +
      '</div>'));
    //create input with save button
    this.phonoUI.append($(
      '<div class="propEditUI phonology">' +
        '<div class="valueInputDiv propEditElement etymphono">'+
          '<span class="etymphonoDisplay">' + etymPhono + ePhonoCnt + '</span>' +
          '<input class="valueInput" value="' + etymPhono + '"/>' +
          '<span><button class="genButton">Generate</button><button class="validateButton">Validate</button>' +
          '<button class="commitButton">Commit</button></span>'+
        '</div>' +
        '<div class="valueInputDiv propEditElement lemmaphono">'+
          '<span class="lemmaphonoDisplay">' + lemmaPhono + lPhonoCnt + '</span>' +
          '<input class="valueInput" value="' + lemmaPhono + '"/>' +
        '</div>' +
      '</div>'
    ));
    $phonoDisplay = $('div.valueLabelDiv', this.phonoUI);
    $lemmaphonoDisplay = $('.lemmaphonoDisplay', this.phonoUI);
    $lemmaphonoInput = $('.lemmaphono input', this.phonoUI);
    $etymphonoDisplay = $('.etymphonoDisplay', this.phonoUI);
    $etymphonoInput = $('.etymphono input', this.phonoUI);
    $generateBtn = $('.genButton', this.phonoUI);
    $validateBtn = $('.validateButton', this.phonoUI);
    $commitBtn = $('.commitButton', this.phonoUI);
    //attach event handlers
    //click to edit
    $phonoDisplay.unbind("click").bind("click", function (e) {
      $('div.edit', lemmaVE.editDiv).removeClass("edit");
      lemmaVE.phonoUI.addClass("edit");
      $('div.valueInputDiv input', lemmaVE.phonoUI).focus();
      e.stopImmediatePropagation();
      return false;
    });
    $lemmaphonoDisplay.unbind("click").bind("click", function (e) {
      $lemmaphonoDisplay.parent().addClass("editing");
      $lemmaphonoInput.focus();
      e.stopImmediatePropagation();
      return false;
    });
    $etymphonoDisplay.unbind("click").bind("click", function (e) {
      $etymphonoDisplay.parent().addClass("editing");
      $etymphonoInput.focus();
      e.stopImmediatePropagation();
      return false;
    });
    $commitBtn.unbind('mousedown').bind('mousedown', function (e) {
      e.stopImmediatePropagation();
      return false;
    });
    $commitBtn.unbind('mouseup').bind('mouseup', function (e) {
      e.stopImmediatePropagation();
      return false;
    });
    $commitBtn.unbind('click').bind('click', function (e) {
      if (lemma.wipPhonology && lemma.wipPhonology.length == 2) {
        //save phonology
        lemma.phonology = lemma.wipPhonology;
        delete lemma.wipPhonology
        //update server model
        var lemProp = {};
        lemProp["phonology"] = lemma.phonology;
        lemProp["wipPhonology"] = '';
        lemmaVE.saveLemma(lemProp);
          //update UI
        $etymphonoDisplay.html(lemma.phonology[0]);
        $lemmaphonoDisplay.html(lemma.phonology[1]);
        $etymphonoDisplay.parent().removeClass("editing");
        $lemmaphonoDisplay.parent().removeClass("editing");
        $phonoDisplay.html(lemma.phonology[0] + ' → '+ lemma.phonology[1]);
        $lemmaphonoInput.parent().parent().removeClass("valid");
      } else {
        UTILITY.beep();
        DEBUG.log('err','WIP Phonology not found, please validate work again');
      }
      e.stopImmediatePropagation();
      return false;
    });
    $validateBtn.unbind('mousedown').bind('mousedown', function (e) {
      e.stopImmediatePropagation();
      return false;
    });
    $validateBtn.unbind('mouseup').bind('mouseup', function (e) {
      e.stopImmediatePropagation();
      return false;
    });
    $validateBtn.unbind('click').bind('click', function (e) {
      var lemmaPhono = $lemmaphonoInput.val(),
          etymPhono = $etymphonoInput.val();
      if (lemma.phonology) {
        delete lemma.phonology;
      }
      //save wip
      lemma.wipPhonology = [etymPhono, lemmaPhono];
      //update server model
      var lemProp = {};
      lemProp["wipPhonology"] = lemma.wipPhonology;
      lemProp["phonology"] = '';
      lemmaVE.saveLemma(lemProp);
    //update UI
      phonologyHtml = lemmaVE.getPhonoComparisonHtml(etymPhono, lemmaPhono);
      $etymphonoDisplay.html(phonologyHtml[0]);
      $lemmaphonoDisplay.html(phonologyHtml[1]);
      $etymphonoDisplay.parent().removeClass("editing");
      $lemmaphonoDisplay.parent().removeClass("editing");
      $phonoDisplay.html('Phonological Analysis')
      if (etymPhono.split('-').length == lemmaPhono.split('-').length){
        $lemmaphonoInput.parent().parent().addClass("valid");
      }
      e.stopImmediatePropagation();
      return false;
    });
    $generateBtn.unbind('mousedown').bind('mousedown', function (e) {
      e.stopImmediatePropagation();
      return false;
    });
    $generateBtn.unbind('mouseup').bind('mouseup', function (e) {
      e.stopImmediatePropagation();
      return false;
    });
    $generateBtn.unbind('click').bind('click', function (e) {
      var lemmaPhono, etymPhono, phonology;
      //regenerate phonology values from lemma and gloss
      phonology = lemmaVE.generatePhonologyStrings();
      if (phonology) {
        var lemProp = {};
        if (lemma.phonology) {
          delete lemma.phonology;
        }
        if (lemma.wipPhonology) {
          delete lemma.wipPhonology;
        }
        //update server model
        lemProp["wipPhonology"] = '';
        lemProp["phonology"] = '';
        lemmaVE.saveLemma(lemProp);
        etymPhono = phonology[0];
        lemmaPhono = phonology[1];
        $lemmaphonoInput.parent().parent().removeClass("valid");
        $lemmaphonoDisplay.parent().removeClass("editing");
        $etymphonoDisplay.parent().removeClass("editing");
        $phonoDisplay.html('Phonological Analysis')
        $lemmaphonoInput.val(lemmaPhono);
        $lemmaphonoDisplay.html(lemmaPhono + " ("+lemmaPhono.split('-').length + ")");
        $etymphonoInput.val(etymPhono);
        $etymphonoDisplay.html(etymPhono + " ("+etymPhono.split('-').length + ")");
      } else {
        alert('unable to generate phonology, please check console for more information on error')
      }
      e.stopImmediatePropagation();
      return false;
    });
    $lemmaphonoInput.unbind('keydown').bind('keydown', function (e) {
      if ((e.keyCode || e.which) == '13') {
        var curInput = $lemmaphonoInput.val(),
            oldInput = $lemmaphonoDisplay.text();
        if (curInput != oldInput) {
          if (!$lemmaphonoInput.parent().parent().hasClass("dirty")) {
            $lemmaphonoInput.parent().parent().addClass("dirty");
          }
          if ($lemmaphonoInput.parent().parent().hasClass("valid")) {
            $lemmaphonoInput.parent().parent().removeClass("valid");
          }
          curInput = $lemmaphonoInput.val();
          curInput = curInput + " (" + curInput.split("-").length + ")";
          $lemmaphonoDisplay.html(curInput);
        }
        //stop editing the user pressed enter
        $lemmaphonoDisplay.parent().removeClass("editing");
      }
    });
    $lemmaphonoInput.unbind('paste').bind('paste', function (e) {
      e.stopImmediatePropagation();
    });
    $lemmaphonoInput.unbind('click').bind('click', function (e) {
      e.stopPropagation();
    });
    $etymphonoInput.unbind('keydown').bind('keydown', function (e) {
      if ((e.keyCode || e.which) == '13') {
        var curInput = $etymphonoInput.val(),
            oldInput = $etymphonoDisplay.text();
        if (curInput != oldInput) {
          if (!$etymphonoInput.parent().parent().hasClass("dirty")) {
            $etymphonoInput.parent().parent().addClass("dirty");
          }
          if ($etymphonoInput.parent().parent().hasClass("valid")) {
            $etymphonoInput.parent().parent().removeClass("valid");
          }
          curInput = $etymphonoInput.val();
          curInput = curInput + " (" + curInput.split("-").length + ")";
          $etymphonoDisplay.html(curInput);
        }
        //stop editing the user pressed enter
        $etymphonoDisplay.parent().removeClass("editing");
       }
    });
     $etymphonoInput.unbind('paste').bind('paste', function (e) {
      e.stopImmediatePropagation();
    });
    $etymphonoInput.unbind('click').bind('click', function (e) {
      e.stopPropagation();
    });

    DEBUG.traceExit("createPhonologicalUI");
  },


  /*************  Compound Analysis Interface ****************/

/**
*
*/

  createCompoundAnalysisUI: function() {
    var lemmaVE = this,
        value = (this.entity.compAnalysis?this.entity.compAnalysis:"Compound Analysis"),
        editValue = (this.entity.compAnalysis?this.entity.compAnalysis:this.entity.value.replace(/aʔi/g,'aï').replace(/aʔu/g,'aü').replace(/ʔ/g,''));
    DEBUG.traceEntry("createCompoundAnalysisUI");
    //create UI container
    this.compUI = $('<div class="compUI"></div>');
    this.editDiv.append(this.compUI);
    //create label with navigation
    this.compUI.append($('<div class="propDisplayUI">'+
                     '<div class="valueLabelDiv propDisplayElement">'+value+'</div>'+
                    '</div>'));
    //create input with save button
    this.compUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+editValue+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.compUI).unbind("click").bind("click",function(e) {
        $('div.edit',lemmaVE.editDiv).removeClass("edit");
        lemmaVE.compUI.addClass("edit");
        $('div.valueInputDiv input',this.compUI).focus();
        e.stopImmediatePropagation();
        return false;
      });
      $('div.valueInputDiv input',this.compUI).unbind("click").bind("click",function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      $('div.valueInputDiv input',this.compUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
//          lemmaVE.compUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.compUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.compUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.compUI).unbind("click").bind("click",function(e) {
        var lemProp = {}, compAnalysis, nodeKey, rootKey = null, rootNode, compKeys;

/**
* put your comment there...
*
* @param key
*/

        function resolveSubLemma(key) {
          var node = compAnalysis[key], j, lemma,
              subKeys = null, matches = [], possMatches = [];
          if (node.lemmaIDs) {
            for (j in node.lemmaIDs) {
              lemma = lemmaVE.dataMgr.getEntity('lem',node.lemmaIDs[j]);
              if (node.subKeys && lemma && lemma.compAnalysis &&
                  node.markup == lemma.compAnalysis) {
                matches.push(lemma);
              } else {
                possMatches.push(lemma);
              }
            }
          }
          if (matches.length == 1) {
            compAnalysis[key]['lemID'] = matches[0].id;
            if (node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (matches.length > 1) { // user selection
            for (j in matches) {
              lemma = matches[j];
              if (confirm("Would you like to link constituent lemma '"+node.value+
                          "' with POS of "+lemmaVE.dataMgr.getTermFromID(lemma.pos))) {
                compAnalysis[key]['lemID'] = lemma.id;
                break;
              }
            }
            if (!compAnalysis[key]['lemID'] && node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (possMatches.length == 1) {
            compAnalysis[key]['lemID'] = possMatches[0].id;
            if (node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (possMatches.length > 1) { // user selection
            for (j in possMatches) {
              lemma = possMatches[j];
              if (confirm("Would you like to link constituent lemma '"+node.value+
                          "' with POS of "+lemmaVE.dataMgr.getTermFromID(lemma.pos))) {
                compAnalysis[key]['lemID'] = lemma.id;
                break;
              }
            }
            if (node.subKeys) {
              subKeys = node.subKeys;
            }
          } else if (node.subKeys) {
            subKeys = node.subKeys;
          }
          if (subKeys) {
            for (j in subKeys) {
              resolveSubLemma(subKeys[j]);
            }
          }
        }

        if ($('.propEditUI',lemmaVE.compUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.compUI).val();
          compAnalysis = lemmaVE.validateCompAnalysis(val);
          if (compAnalysis) {//validates
            compKeys = Object.keys(compAnalysis);
            for (i in compKeys) {
              nodeKey = compKeys[i];
              if (compAnalysis[nodeKey].root) {
                rootKey = nodeKey;
                resolveSubLemma(rootKey);
                break;
              }
            }
            $('div.valueLabelDiv',lemmaVE.compUI).html(val);
          } else {
            $('div.valueLabelDiv',lemmaVE.compUI).html("Compound Analysis");
          }
          $('.propEditUI',lemmaVE.compUI).removeClass('dirty');
          if (compAnalysis && rootKey) {
            lemmaVE.saveCompoundAnalysis(compAnalysis, rootKey);
          }
          lemmaVE.compUI.removeClass("edit");
        }
        e.stopImmediatePropagation();
        return false;
     });
    DEBUG.traceExit("createCompoundAnalysisUI");
  },


/**
* put your comment there...
*
* @param compAnal
*
* @returns {Object}
*/

  validateCompAnalysis: function(compAnal) {
    if (!compAnal) {
      return compAnal;
    }
    var lemmaVE = this, strAnalysis = compAnal, value, markup, hasHead, isHead, lemma, lemID,
        subNodes, subNode, compNodes, i, j, k, subKey, key, l=0, nodeLookup = {}, lemmaIDs,
        reSqBrkOpen = /\[/g,
        reSqBrkClose = /\]/g,
        reCompMarkup = /[\[\]!]/g,//find all compound analysis markup characters
        reBrkNodes = /\[[^\[\]]+\]/g;//text surrounded by square brackets
    if (!strAnalysis.match(reSqBrkOpen) || !strAnalysis.match(reSqBrkClose)) {
      return false;
    }
    if (strAnalysis.match(reSqBrkOpen).length != strAnalysis.match(reSqBrkClose).length) {
      alert("Compound Analysis has mismatched brackets, please correct before saving.");
      return false;
    }
    if (!strAnalysis.match(/!/)) {
      alert("Compound Analysis must use '!' to mark at least one constituent as HEAD WORD.");
      return false;
    }
    subNodes = strAnalysis.match(reBrkNodes);
    if (subNodes.length > 1) {
      for (i in subNodes) {
        l++;
        key = "n"+l;
        markup = subNodes[i];
        value = markup.replace(reCompMarkup,"");
        nodeLookup[key] = { 'markup':markup,
                            'value':value};
        //detect if existing lemma match for linking
        lemID = this.wordlistVE.lookupLemma(markup);
        if (lemID) {//search by companalysis
          lemID = lemID[0];
          lemma = this.dataMgr.getEntity('lem',lemID);
          if (lemma && ((lemma.compAnalysis &&
              (markup == lemma.compAnalysis)) || markup == compAnal)) {
            nodeLookup[key]['lemID'] = lemID;
            if (lemID == this.entID) {
              nodeLookup[key]['root'] = 1;
            }
          }
        } else {//search by lemma value, we might find a lemma that matches and we want to attach compound analysis
          lemmaIDs = this.wordlistVE.lookupLemma(value);
          if (lemmaIDs && lemmaIDs.length) {
            for (k=0; k<lemmaIDs.length; k++) {
              lemID = lemmaIDs[k];
              lemma = this.dataMgr.getEntity('lem',lemID);
              if (lemma && !lemma.compAnalysis) {
                nodeLookup[key]['lemID'] = lemID;
                break;
              }
            }
          }
        }
        if (markup.match(/^\[!/)) {
          nodeLookup[key]['head'] = 1;
        }
        strAnalysis = strAnalysis.replace(markup,key);
      }
      while (compNodes = strAnalysis.match(reBrkNodes)) {//process compound constituents
        if (strAnalysis.match(reSqBrkOpen).length != strAnalysis.match(reSqBrkClose).length) {
          alert("Compound Analysis has mismatched brackets, please correct before saving.");
          return false;
        }
        for (i in compNodes) {
          l++;
          key = "n"+l;
          markup = compNodes[i];
          value = markup.replace(reCompMarkup,"");//recompute value from markup
          subNodes = value.match(/(n\d+)/g);//find all node keys
          hasHead = false;
          for (j in subNodes) {
            subKey = subNodes[j];
            subNode = nodeLookup[subKey];
            if (subNode.head) {
              hasHead = true;
            }
            if (subNode.subKeys) {// is compound so need to wrap markup
              if (subNode.head) {// comphead so mark it with !
                markup = markup.replace(subKey,"[!"+subNode.markup+"]");
              } else {
                markup = markup.replace(subKey,"["+subNode.markup+"]");
              }
            } else {//leaf
              markup = markup.replace(subKey,subNode.markup);
            }
            value = value.replace(subKey,subNode.value);
          }
          isHead = false;
          if (markup.match(/^\[!/)) {
            isHead = true;
          }
          markup = markup.replace(/^\[!?/,"").replace(/\]$/,"");
          nodeLookup[key] = { 'markup':markup,
                              'subKeys': subNodes,
                              'value':value};
          if (isHead) {
            nodeLookup[key]['head'] = 1;
          }
          //detect if existing lemma match for linking
          lemID = this.wordlistVE.lookupLemma(markup);
          if (lemID) {//search by companalysis
            lemID = lemID[0];
            lemma = this.dataMgr.getEntity('lem',lemID);
            if (lemma && ((lemma.compAnalysis &&
                (markup == lemma.compAnalysis)) || markup == compAnal)) {
              nodeLookup[key]['lemID'] = lemID;
              if (lemID == this.entID) {
                nodeLookup[key]['root'] = 1;
              }
            }
          } else {//search by lemma value, we might find a lemma that matches and we want to attach compound analysis
            lemmaIDs = this.wordlistVE.lookupLemma(value);
            if (lemmaIDs && lemmaIDs.length) {
              for (k=0; k<lemmaIDs.length; k++) {// search the lemma for a match to the markup
                lemID = lemmaIDs[k];
                lemma = this.dataMgr.getEntity('lem',lemID);
                if (lemma && ((lemma.compAnalysis &&
                    (markup == lemma.compAnalysis)) || markup == compAnal)) {
                  nodeLookup[key]['lemID'] = lemID;
                  if (lemID == this.entID) {
                    nodeLookup[key]['root'] = 1;
                  }
                  break;
                }
              }
            }
          }
          if (!hasHead && lemID != this.entID) {
            alert("Compound Analysis has missing HEAD WORD for compound constituent '"+markup+"', please correct before saving.");
            return false;
          }
          strAnalysis = strAnalysis.replace(compNodes[i],key);
        }// end for compent nodes
      }// end while constituent nodes exist
    } else {
      alert("Compound Analysis must have more than 1 subnode, please correct before saving.");
      return false;
    }
    if (strAnalysis.match(/(\[|\])/)) {
      alert("Compound Analysis has misaligned brackets, please correct before saving.");
      return false;
    } else { //process root node
      l++;
      key = "n"+l;
      markup = strAnalysis;
      value = strAnalysis;
      subNodes = value.match(/(n\d+)/g);//find all node keys
      hasHead = false;
      for (j in subNodes) {
        subKey = subNodes[j];
        subNode = nodeLookup[subKey];
        if (subNode.head) {
          hasHead = true;
        }
        if (subNode.subKeys) {// is compound so need to wrap markup
          if (subNode.head) {// comphead so mark it with !
            markup = markup.replace(subKey,"[!"+subNode.markup+"]");
          } else {
            markup = markup.replace(subKey,"["+subNode.markup+"]");
          }
        } else {//leaf
          markup = markup.replace(subKey,subNode.markup);
        }
        value = value.replace(subKey,subNode.value);
      }
      if (!hasHead) {
        alert("Compound Analysis has missing HEAD WORD for compound '"+markup+"', please correct before saving.");
        return false;
      }
      nodeLookup[key] = { 'markup':markup,
                          'subKeys': subNodes,
                          'root': 1,
                          'value':value};
      lemID = this.wordlistVE.lookupLemma(markup);
      if (lemID) {//search by companalysis
        lemID = lemID[0];
        lemma = this.dataMgr.getEntity('lem',lemID);
        if (lemma && ((lemma.compAnalysis &&
            (markup == lemma.compAnalysis)) || markup == compAnal)) {
          nodeLookup[key]['lemID'] = lemID;
        }
      } else {//search by lemma value, we might find a lemma that matches and we want to attach compound analysis
        lemmaIDs = this.wordlistVE.lookupLemma(value);
        if (lemmaIDs && lemmaIDs.length) {
          for (k=0; k<lemmaIDs.length; k++) {// search the lemma for a match to the markup
            lemID = lemmaIDs[k];
            lemma = this.dataMgr.getEntity('lem',lemID);
            if (lemma && lemma.compAnalysis &&
                (markup == lemma.compAnalysis)) {
              nodeLookup[key]['lemID'] = lemID;
              break;
            }
          }
        }
      }
    }
    if (nodeLookup[key]['lemID'] && nodeLookup[key]['lemID'] != lemmaVE.entity.id) {
      alert('Lemma id='+nodeLookup[key]['lemID']+' has matching compound analysis where duplicates are not allowed. Please change analysis and try again.');
      return false;
    } else if (nodeLookup[key].value  != lemmaVE.entity.value.replace(/ʔ/g,'')) {
      if(!confirm("Compound Analysis does not match lemma. Press OK to continue saving or cancel to review spelling.")) {
        return false;
      } else {
        nodeLookup[key].value = lemmaVE.entity.value.replace(/ʔ/g,'')
        nodeLookup[key]['lemID'] = lemmaVE.entity.id;
      }
    }
    return nodeLookup;
  },

/************* Etymology Interface ****************/

/**
* put your comment there...
*
*/

  createDescriptionUI: function() {
    var lemmaVE = this,
        value = this.isLemma ? this.entity.gloss:"";
    DEBUG.traceEntry("createDescriptionUI");
    //create UI container
    this.descrUI = $('<div class="descrUI"></div>');
    this.editDiv.append(this.descrUI);
    //create label
    this.descrUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"Etymology")+'</div>'+
                          '</div>'));
    //create input with save button
    this.descrUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+(value?value:"")+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.descrUI).unbind("click").bind("click",function(e) {
        $('div.edit',lemmaVE.editDiv).removeClass("edit");
        lemmaVE.descrUI.addClass("edit");
        $('div.valueInputDiv input',this.descrUI).focus();
        //$('div.valueInputDiv input',this.descrUI).select();
        e.stopImmediatePropagation();
        return false;
      });
      $('div.valueInputDiv input',this.descrUI).unbind("click").bind("click",function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      $('div.valueInputDiv input',this.descrUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
//          lemmaVE.descrUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.descrUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.descrUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.descrUI).unbind("click").bind("click",function(e) {
        var lemProp = {};
        if ($('.propEditUI',lemmaVE.descrUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.descrUI).val();
          lemProp["gloss"] = val;
          $('div.valueLabelDiv',lemmaVE.descrUI).html(val);
          $('.propEditUI',lemmaVE.descrUI).removeClass('dirty');
          lemmaVE.saveLemma(lemProp);
        }
        lemmaVE.descrUI.removeClass("edit");
        e.stopImmediatePropagation();
        return false;
      });
    DEBUG.traceExit("createDescriptionUI");
  },

/**
* put your comment there...
*
*/

  getDeclensionList: function() {
    var lemmaVE = this,
        listName = EDITORS.config.declensionListName,
        trmID, trmIDs, term, declUIListID, declList = [];
    declUIListID = this.dataMgr.getIDFromTermParentTerm(listName,'Declension'); // warning!!! term dependency
    trmIDs = this.dataMgr.getTermListFromID(declUIListID);
    for (i=0; i < trmIDs.length; i++) {
      trmID = trmIDs[i];
      term = this.dataMgr.getTermFromID(trmID);
      declList.push({'name':term,'tag':'trm'+trmID,'id':trmID});
    }
    return declList;
  },

/**
* put your comment there...
*
*/

  createDeclensionUI: function() {
    var lemmaVE = this,
        trmID = this.entity.decl ? this.entity.decl:null;
    this.declensionList = this.getDeclensionList();
    var source =
            {
                localdata: this.declensionList,
                datatype: "array"
            };
    var dataAdapter = new $.jqx.dataAdapter(source);
    DEBUG.traceEntry("lemmaVE.createDeclensionUI");
    //create UI container
    this.declUI = $('<div class="declUI"></div>');
    this.editDiv.append(this.declUI);
    //create label
    this.declUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+
                          '<span class="valueLabelDivHeader">Declension: </span>'+
                          '<span class="valueLabelDivEditor"></span>'+
                          '</div>'+
                          '</div>'));
    //create input with save button
    this.declUI.append($('<div class="propEditUI">'+
                    '<div class="declListDiv propEditElement"></div>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      this.declUI.unbind("click").bind("click",function(e) {
        if (lemmaVE.declUI.hasClass("edit")) {
          lemmaVE.declUI.removeClass("edit");
        } else {
          lemmaVE.declUI.addClass("edit");
        }
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      this.declUI.unbind("blur").bind("blur",function(e) {
          lemmaVE.declUI.removeClass("edit");
      });
      this.declList = $('div.declListDiv',this.declUI);
      // Create jqxListBox
      this.declList.jqxListBox({  source: dataAdapter,
                                  displayMember: "name",
                                  valueMember: "id",
                                  height: 300,
                                  width: 200,
          renderer: function (index, label, value) {
              var datarecord = lemmaVE.declensionList[index];
              return'<span title="'+datarecord.name + '" tag="'+datarecord.tag+'" >' + datarecord.name + '</span>';
          }
      });
      //select cur Declention in list and make it visible
      var curDecl;
      if (trmID) {
        curDecl = this.declList.jqxListBox('getItemByValue', trmID);
        if (curDecl) {
          this.declList.jqxListBox('selectItem', curDecl);
          this.declList.jqxListBox('ensureVisible', curDecl);
          $('.valueLabelDivEditor',lemmaVE.declUI).html(curDecl.label);
        }
      }

      //handle editor select
      this.declList.unbind("select").bind("select",function(e) {
        var args = e.args, item = args.item,
            declName = item.label, declTrmID = item.value,
            lemProp = {};
        lemProp["decl"] = declTrmID;
        $('.valueLabelDivEditor',lemmaVE.declUI).html(declName);
        lemmaVE.declUI.removeClass("edit");
        lemmaVE.saveLemma(lemProp);
        //if editor has changed then save tempory session preferences
      });
    DEBUG.traceExit("lemmaVE.createDeclensionUI");
  },

/*************  radio button helper ****************/

/**
* put your comment there...
*
* @param grpName
* @param uiClass
* @param list
* @param initTermID
* @param defTermID
* @param isUncertain
* @param clearsAll
*/

  createRadioGroupUI: function(grpName, uiClass, list, initTermID, defTermID, isUncertain, clearsAll) {
    var lemmaVE = this,
        radioGroup = $('<div class="radioGroupUI'+(uiClass?' '+uiClass:'')+'"></div>'),
        i, listDef, buttonDiv;
    if (grpName) {
      radioGroup.prop('grpName',grpName);
    }
    if (initTermID) {
      radioGroup.prop('origID',initTermID);
    }
    if (isUncertain) {
      radioGroup.prop('origCF',2);
    }
    if (clearsAll) {
      radioGroup.prop('clearsAll',clearsAll);
    }
    for (i in list) {
      listDef = list[i];
      buttonDiv = $('<button class="'+
                    (listDef.type?listDef.type:"buttonDiv")+
                    '" type="button"><span>'+listDef.label+'</span></button>');
      buttonDiv.prop('trmID',listDef.trmID);
      if (listDef.showSub || listDef.showSub == "") {
        buttonDiv.prop('showSub',listDef.showSub);
      }
      if (listDef.showInfl) {
        buttonDiv.prop('showInfl',listDef.showInfl);
      }
      if (initTermID && listDef.trmID == initTermID) {// initialize single selection, assumes list has unique trmIDs
        buttonDiv.addClass('selected');
        if (isUncertain) {
          buttonDiv.addClass('uncertain');
        }
      }
      if (defTermID && listDef.trmID == defTermID) {// initialize single selection, assumes list has unique trmIDs
        buttonDiv.addClass('default');
      }
      radioGroup.append(buttonDiv);
    }
    if (defTermID && $('.buttonDiv.selected',radioGroup).length == 0 && $('.buttonDiv.default',radioGroup).length == 1) {
//      $('.buttonDiv.default',radioGroup).addClass('selected');
      //since there is not initial value selected it must be null or out of range
      //so default represents a change in value, need to mark dirty
//      radioGroup.addClass("dirty");
    }
    //click to select
    $('.buttonDiv',radioGroup).unbind("click").bind("click",function(e) {
      var ctxDiv = $(this).parent().parent(),showSub = $(this).prop('showSub'),
          classes = ctxDiv.attr('class').replace(/show[^\s]+/g,"").trim();//get all non show classes
      if (showSub || showSub == "") {//showSub can be blank to remove submenus
        classes += showSub?" "+showSub :"";
        ctxDiv.attr('class',classes);
      }
      //if button selected and uncertainty remove both
      if ($(this).hasClass('uncertain')) {
        $(this).removeClass('uncertain');
        $(this).removeClass('selected');
      } else if ($(this).hasClass('selected')) {//if button selected add uncertainty
        $(this).addClass('uncertain');
      } else {
        if (radioGroup.prop('clearsAll')) {//reset all buttons and update dirty flags
          ctxDiv.find('.selected').removeClass('selected');
          ctxDiv.find('.uncertain').removeClass('uncertain');
          $('.radioGroupUI',ctxDiv).each(function(index, elem) {
            var radioGroup2 = $(elem);
            if (radioGroup2 != radioGroup) { // not current group
              if (radioGroup2.prop('origCF') ||  radioGroup2.prop('origID')) { // is dirty
                if (!radioGroup2.hasClass("dirty")) {
                  radioGroup2.addClass("dirty");
                }
              } else { // not dirty so remove flags
                if (radioGroup2.hasClass("dirty")) {
                  radioGroup2.removeClass("dirty");
                }
              }
            }
          });
        } else {//remove selected and uncertainty from all group buttons
          $(this).parent().find('.selected').removeClass('selected');
          $(this).parent().find('.uncertain').removeClass('uncertain');
        }
        //add selected to this button and force refresh
        $(this).addClass('selected');
      }
      //check value against original and update dirty flag for group
      if ((radioGroup.prop('origCF') && !$(this).hasClass('uncertain')) ||
          (!radioGroup.prop('origCF') && $(this).hasClass('uncertain')) ||
          (!$(this).hasClass('selected') && radioGroup.prop('origID'))||
          ($(this).hasClass('selected') && radioGroup.prop('origID') != $(this).prop('trmID'))) { // is dirty
        if (!radioGroup.hasClass("dirty")) {
          radioGroup.addClass("dirty");
        }
      } else { // not dirty so remove flags
        if (radioGroup.hasClass("dirty")) {
          radioGroup.removeClass("dirty");
        }
      }
      //check dirty flag of all groups and update UI dirty flag for save button.
      if ( ctxDiv.find('.dirty').length) { //properties have changed
        if (!ctxDiv.hasClass("dirty")) {
          ctxDiv.addClass("dirty");
        }
      } else {
        if (ctxDiv.hasClass("dirty")) {
          ctxDiv.removeClass("dirty");
        }
      }
      e.stopImmediatePropagation();
      return false;
    });
    return radioGroup;
  },

/************* Part Of Speech Interface ****************/

/**
* put your comment there...
*
*/

  createPOSEditUI: function() {
    var lemmaVE = this, showSub,
        nounID = 664,
        pronID = 667,
        verbID = 685,
        numID = 662,
        adjID = 674,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:null,
        spos = this.isLemma && this.entity.spos ? this.entity.spos:null,
        ggen = this.isLemma && this.entity.gender ? this.entity.gender:null,
        vclass = this.isLemma && this.entity['class'] ? this.entity['class']:null,
        cf = this.isLemma && this.entity.certainty ? this.entity.certainty:[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        posEdit = $('<div class="posEditUI radioUI"></div>'),
        listPOS = [{label: "adj.",trmID:674,showSub:"showSubAdj",showInfl:"showCase showAdjGender showNumber showAdjConj"},
                   {label: "adp.",trmID:661,showSub:""},
                   {label: "adv.",trmID:655,showSub:""},
                   {label: "ind.",trmID:656,showSub:""},
                   {label: "noun",trmID:664,showSub:"showSubNoun showGramGender",showInfl:"showGender showCase showNumber"},
                   {label: "num.",trmID:662,showSub:"showSubNum"},
                   {label: "pron.",trmID:667,showSub:"showSubPron"},
                   {label: "v.",trmID:685,showSub:"",showInfl:"showSubVerb"}],
        listSubAdj = [{label: "common",trmID:675,showInfl:"showCase showAdjGender showNumber showAdjConj"},
                      {label: "gdv.",trmID:676,showInfl:"showCase showAdjGender showNumber showAdjConj"},
                       {label: "pp.",trmID:677,showInfl:"showCase showAdjGender showNumber showAdjConj"},
                       {label: "pres. part.",trmID:678}],
        listSubNum = [{label: "ord.",trmID:683,showInfl:"showCase showGender showNumber"},
                       {label: "card.",trmID:682,showInfl:"showCase showGender showNumber"}],
        listSubNoun = [{label: "common",trmID:665,showInfl:"showCase showGender showNumber"},
                       {label: "proper",trmID:666,showInfl:"showCase showGender showNumber"}],
        listSubPron = [{label: "dem.",trmID:669,showInfl:"showCase showAdjGender showNumber"},
                       {label: "indef.",trmID:670,showInfl:"showCase showAdjGender showNumber"},
                       {label: "interr.",trmID:671,showInfl:"showCase showAdjGender showNumber"},
                       {label: "pers.",trmID:668,showInfl:"showCase showNumber"},
                       {label: "rel.",trmID:672,showInfl:"showCase showAdjGender showNumber"},
                       {label: "refl.",trmID:673,showInfl:"showCase showNumber"}],
        listGramGen = [{label: "m.",trmID:491},
                       {label: "mn.",trmID:494,showInfl:"showGender"},
                       {label: "n.",trmID:492},
                       {label: "mfn.",trmID:1383,showInfl:"showGender"},
                       {label: "nf.",trmID:1384,showInfl:"showGender"},
                       {label: "f.",trmID:493},
                       {label: "mf.",trmID:495,showInfl:"showGender"}],
        listClass = [{label: "1",trmID:831},
                     {label: "2",trmID:832},
                     {label: "3",trmID:833},
                     {label: "4",trmID:834},
                     {label: "5",trmID:835}],
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Clear",type:"clearBtnDiv"}];
    posEdit.append(this.createRadioGroupUI("pos", "PosUI", listPOS,pos,null,cf[0]==2,1));
    posEdit.append(this.createRadioGroupUI("spos", "SubNounUI", listSubNoun,spos,665, cf[1]==2));
    posEdit.append(this.createRadioGroupUI("gender", "GramGenderUI", listGramGen, ggen, 491, cf[2]==2));
    posEdit.append(this.createRadioGroupUI("spos", "SubNumeralUI", listSubNum,spos, 683, cf[1]==2));
    posEdit.append(this.createRadioGroupUI("spos", "SubAdjectiveUI", listSubAdj,spos,675, cf[1]==2));
    posEdit.append(this.createRadioGroupUI("class", "ClassUI", listClass,vclass,null, cf[3]==2));
    posEdit.append(this.createRadioGroupUI("spos", "SubPronUI", listSubPron,spos,669, cf[1]==2));
    posEdit.append(this.createRadioGroupUI(null, null, listCtrl));
    showSub = $('.PosUI button.buttonDiv.selected',posEdit).prop('showSub');
    if (showSub && showSub.length) {// ensure sub groups are shown
      posEdit.addClass(showSub);
    }
    //click to clear
    $('.clearBtnDiv',posEdit).unbind("click").bind("click",function(e) {
      var ctxDiv = posEdit;
      //remove selected and uncertainty from all groups buttons
      ctxDiv.find('.selected').removeClass('selected');
      ctxDiv.find('.uncertain').removeClass('uncertain');
      //check each radioGroup against it's original values and update dirty flag for group
      $('.radioGroupUI',ctxDiv).each(function(index, elem) {
        var radioGroup = $(elem);
        if (radioGroup.prop('origCF') ||  radioGroup.prop('origID')) { // is dirty
          if (!radioGroup.hasClass("dirty")) {
            radioGroup.addClass("dirty");
          }
        } else { // not dirty so remove flags
          if (radioGroup.hasClass("dirty")) {
            radioGroup.removeClass("dirty");
          }
        }
      });
      //check dirty flag of all groups and update UI dirty flag for save button.
      if ( ctxDiv.find('.dirty').length) { //properties have changed
        if (!ctxDiv.hasClass("dirty")) {
          ctxDiv.addClass("dirty");
        }
      } else {
        if (ctxDiv.hasClass("dirty")) {
          ctxDiv.removeClass("dirty");
        }
      }
      e.stopImmediatePropagation();
      return false;
    });
    //save data
    $('.saveBtnDiv',posEdit).unbind("click").bind("click",function(e) {
      var value = 'POS', posVal, posID, posCF = 3, lemProps = {},
          sposVal, sposID, sposCF = 3, genVal, genID, genCF = 3,
          classCF = 3,declCF = 3,// WARNING!! hardcode to unused until Gandhari declination research is finished
          posButtonDiv, subPosButtonDiv, genButtonDiv;
      if ($('.posEditUI',lemmaVE.posUI).hasClass('dirty')) {
        //calculate lemma display for POS - SPOS - etc
        posButtonDiv = $('.PosUI button.buttonDiv.selected',posEdit);
        if (posButtonDiv.length) {// pos selected so calc all the rest
          //read selected values and uncertain values
          posVal = posButtonDiv.text();
          posID = posButtonDiv.prop('trmID');
          posCF = posButtonDiv.hasClass('uncertain')?2:1;
          value = posVal;
          //if noun then use gender if set else use mnf. for display
          if (posID == nounID) {
            subPosButtonDiv = $('.SubNounUI button.buttonDiv.selected',posEdit);
            if (!subPosButtonDiv || subPosButtonDiv.length == 0) {// subPOS for pronoun selected so calc all the rest
              subPosButtonDiv = $('.SubNounUI button.buttonDiv.default',posEdit);
            }
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
            genButtonDiv = $('.GramGenderUI button.buttonDiv.selected',posEdit);
            if (!genButtonDiv || genButtonDiv.length == 0) {// subPOS for pronoun selected so calc all the rest
              genButtonDiv = $('.GramGenderUI button.buttonDiv.default',posEdit);
            }
            if (genButtonDiv.length) {// gender selected so calc all the rest
              //read selected values and uncertain values
              genVal = genButtonDiv.text();
              genID = genButtonDiv.prop('trmID');
              genCF = genButtonDiv.hasClass('uncertain')?2:1;
              value = genVal + (genCF==2?'(?)':'');
            }
          } else if (posID == pronID) {// if pronoun check for spos
            subPosButtonDiv = $('.SubPronUI button.buttonDiv.selected',posEdit);
            if (!subPosButtonDiv || subPosButtonDiv.length == 0) {// subPOS for pronoun selected so calc all the rest
              subPosButtonDiv = $('.SubPronUI button.buttonDiv.default',posEdit);
            }
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
                //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
          } else if (posID == numID) {// if numeral check for spos
            subPosButtonDiv = $('.SubNumeralUI button.buttonDiv.selected',posEdit);
            if (!subPosButtonDiv || subPosButtonDiv.length == 0) {// subPOS for pronoun selected so calc all the rest
              subPosButtonDiv = $('.SubNumeralUI button.buttonDiv.default',posEdit);
            }
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
          } else if (posID == adjID) {// if numeral check for spos
            subPosButtonDiv = $('.SubAdjectiveUI button.buttonDiv.selected',posEdit);
            if (!subPosButtonDiv || subPosButtonDiv.length == 0) {// subPOS for pronoun selected so calc all the rest
              subPosButtonDiv = $('.SubAdjectiveUI button.buttonDiv.default',posEdit);
            }
            if (subPosButtonDiv.length) {// subPOS for pronoun selected so calc all the rest
              //read selected values and uncertain values
              sposVal = subPosButtonDiv.text();
              sposID = subPosButtonDiv.prop('trmID');
              sposCF = subPosButtonDiv.hasClass('uncertain')?2:1;
            }
          }
          lemProps["pos"] = posID?posID:null;
          lemProps["spos"] = sposID?sposID:null;
          lemProps["gender"] = genID?genID:null;
          lemProps["certainty"] = [posCF,sposCF,genCF,classCF,declCF];
          lemmaVE.saveLemma(lemProps);
        } else {
          lemProps["pos"] = null;
          lemProps["spos"] = null;
          lemProps["gender"] = null;
          lemProps["certainty"] = [3,3,3,3,3];
          lemmaVE.saveLemma(lemProps);
        }
        $('div.valueLabelDiv',lemmaVE.posUI).html(value);
        $('.posEditUI',lemmaVE.posUI).removeClass('dirty');
      }
      lemmaVE.posUI.removeClass("edit");
      e.stopImmediatePropagation();
      return false;
    });
    posEdit.append($('<input style="visibility:hidden;" />'));
    return posEdit;
  },


/**
* put your comment there...
*
*/

  createPOSUI: function() {
    var lemmaVE = this, value,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:null,
        cf = this.isLemma && this.entity.certainty ? this.entity.certainty:[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        spos = this.isLemma && this.entity.spos ? this.entity.spos:null;
    if (pos && this.dataMgr.getTermFromID(pos)) {
      value = this.dataMgr.getTermFromID(pos) + (cf[0]==2?'(?)':'');
      if (value == 'noun' && this.entity.gender && this.dataMgr.getTermFromID(this.entity.gender)) {
        value = this.dataMgr.getTermFromID(this.entity.gender) + (cf[2]==2?'(?)':'');
      }
    }
    DEBUG.traceEntry("createPOSUI");
    //create UI container
    this.posUI = $('<div class="posUI"></div>');
    this.editDiv.append(this.posUI);
    //create label
    this.posUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"POS")+'</div>'+
                          '</div>'));
    //create input with save button
    this.posUI.append(this.createPOSEditUI());
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.posUI).unbind("click").bind("click",function(e) {
        $('div.edit',lemmaVE.editDiv).removeClass("edit");
        lemmaVE.posUI.addClass("edit");
        $('div.posEditUI input',this.posUI).focus();
        e.stopImmediatePropagation();
        return false;
      });
      //click to cancel
      $('div.posEditUI input',this.posUI).unbind("blur").bind("blur",function(e) {
//        lemmaVE.posUI.removeClass("edit");
      });
      //mark dirty on input
      $('div.posEditUI',this.posUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.posUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
    DEBUG.traceExit("createPOSUI");
  },

/************* Gloss Interface ****************/

/**
* put your comment there...
*
*/

  createTransUI: function() {
    var lemmaVE = this,
        value = this.isLemma ? this.entity.trans:"";
    DEBUG.traceEntry("createTransUI");
    //create UI container
    this.transUI = $('<div class="transUI"></div>');
    this.editDiv.append(this.transUI);
    //create label
    this.transUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+(value?value:"Gloss")+'</div>'+
                          '</div>'));
    //create input with save button
    this.transUI.append($('<div class="propEditUI">'+
                    '<div class="valueInputDiv propEditElement"><input class="valueInput" value="'+(value?value:"")+'"/></div>'+
                    '<button class="saveDiv propEditElement">Save</button>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      $('div.valueLabelDiv',this.transUI).unbind("click").bind("click",function(e) {
        $('div.edit',lemmaVE.editDiv).removeClass("edit");
        lemmaVE.transUI.addClass("edit");
        $('div.valueInputDiv input',lemmaVE.transUI).focus();
        //$('div.valueInputDiv input',lemmaVE.transUI).select();
        e.stopImmediatePropagation();
        return false;
      });
      $('div.valueInputDiv input',this.transUI).unbind("click").bind("click",function(e) {
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      $('div.valueInputDiv input',this.transUI).unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
//          lemmaVE.transUI.removeClass("edit");
        }
      });
      //mark dirty on input
      $('div.valueInputDiv input',this.transUI).unbind("input").bind("input",function(e) {
        if ($('div.valueLabelDiv',this.transUI).text() != $(this).val()) {
          if (!$(this).parent().parent().hasClass("dirty")) {
            $(this).parent().parent().addClass("dirty");
          }
        } else if ($(this).parent().parent().hasClass("dirty")) {
          $(this).parent().parent().removeClass("dirty");
        }
      });
      //save data
      $('.saveDiv',this.transUI).unbind("click").bind("click",function(e) {
        var lemProp = {};
        if ($('.propEditUI',lemmaVE.transUI).hasClass('dirty')) {
          val = $('div.valueInputDiv input',lemmaVE.transUI).val();
          lemProp["trans"] = val;
          $('div.valueLabelDiv',lemmaVE.transUI).html(val);
          $('.propEditUI',lemmaVE.transUI).removeClass('dirty');
          lemmaVE.saveLemma(lemProp);
        }
        lemmaVE.transUI.removeClass("edit");
        e.stopImmediatePropagation();
        return false;
      });
    DEBUG.traceExit("createTransUI");
  },


/**
* put your comment there...
*
* @param attestedGID
*/

  linkAttestedForm: function(attestedGID) {
    var lemmaVE = this;
    this.saveLemma(null, attestedGID, (this.linkToInfID?this.linkToInfID:null), null, true);
  },


/**
* put your comment there...
*
* @param infID
* @param attestedGID
*/

  unlinkAttested: function(infID,attestedGID) {
    var lemmaVE = this;
    this.saveLemma(null, attestedGID, infID, null, false);
  },


/**
* put your comment there...
*
* @param infID
* @param attestedGID
* @param buttonDiv
*/

  initShowInflectionEditUI: function(infID,attestedGID,buttonDiv) {
    var lemmaVE = this;
    if (!this.inflectionEditUI.children().length) {
      this.inflectionEditUI.children().remove();
    }
    this.createInflectionEditUI(infID,attestedGID,buttonDiv);
  },


/**
* put your comment there...
*
* @param infID
* @param attestedGID
* @param btnDiv
*/

  createInflectionEditUI: function(infID, attestedGID, btnDiv) {
    var lemmaVE = this, showSub, nounID = 664, pronID = 667, verbID = 685,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:null,
        spos = this.isLemma && this.entity.spos ? this.entity.spos:null,
        vclass = this.isLemma && this.entity['class'] ? this.entity['class']:null,
        inflection, sverb = '686', gen, num, infcase, per, tense , mood, conj2nd, icf = [3,3,3,3,3,3,3,3],
        infEdit = this.inflectionEditUI, lemmaShowInf = [], posSelectedBtns,
        listSubVerb = [{label: "Finite",trmID:'686',showSub:"showSubVerb showNumber showPerson showTense showMood"},
                       {label: "Derivative",trmID:'687',showSub:"showSubVerb showV2ndConj"}],
        listGen = [{label: "m.",trmID:485},
                   {label: "n.",trmID:486},
                   {label: "f.",trmID:487}],
        listAdjGen = [{label: "m.",trmID:491},
                       {label: "mn.",trmID:494},
                       {label: "n.",trmID:492},
                       {label: "mfn.",trmID:1383},
                       {label: "nf.",trmID:1384},
                       {label: "f.",trmID:493},
                       {label: "mf.",trmID:495}],
        listNum = [{label: "sg.",trmID:499},
                   {label: "du.",trmID:500},
                   {label: "pl.",trmID:501}],
        listCase = [//{label: "dir.",trmID:361},
                   {label: "nom.",trmID:362},
                   {label: "acc.",trmID:363},
                   {label: "instr.",trmID:364},
                   {label: "dat.",trmID:365},
//                   {label: "dat/gen.",trmID:366},
                   {label: "abl.",trmID:367},
                   {label: "gen.",trmID:368},
                   {label: "loc.",trmID:369},
                   {label: "voc.",trmID:370}/*,
                   {label: "stem.",trmID:1069}*/],
        listPerson = [{label: "1st",trmID:850},
                     {label: "2nd",trmID:851},
                     {label: "3rd",trmID:852}],
        listTense = [{label: "pres.",trmID:865},
                     {label: "fut.",trmID:868},
                     {label: "perf.",trmID:867},
                     {label: "pret.",trmID:872}],
        listMood = [{label:  "ind.",trmID:846},
                     {label: "opt.",trmID:845},
                     {label: "impv.",trmID:844}],
        listV2ndConj = [{label: "abs.",trmID:860},
                       {label:  "inf.",trmID:861}],
        list2ndConj = [{label: "desid.",trmID:857},
                       {label: "intens.",trmID:859}],
        listCtrl = [{label: "Save",type:"saveBtnDiv"},
                    {label: "Clear",type:"clearBtnDiv"},
                    {label: "Cancel",type:"cancelBtnDiv"}];
    //find the show inflection UI indicators
    posSelectedBtns = $('.posEditUI button.buttonDiv.selected',lemmaVE.posUI);
    if (posSelectedBtns.length == 0) {
      alert("Error - trying to set inflection without lemma POS selected.");
      return;
    }
    posSelectedBtns.each(function(index,elem){
      var i,showInf = $(elem).prop('showInfl');
      if (showInf) {
        //handle multiple space separation
        showInf = showInf.replace(/\s+/g,',').split(',');
        for (i=0; i<showInf.length; i++) {
          if (lemmaShowInf.indexOf(showInf[i]) == -1) {
            lemmaShowInf.push(showInf[i]);
          }
        }
      }
    });
    if (infID) {
      inflection = this.dataMgr.getEntity('inf',infID);
      icf =  inflection.certainty;
      gen = inflection.gender;
      num = inflection.num;
      infcase = inflection['case'];
      per = inflection.person;
      tense = inflection.tense;
//      voice = inflection.voice;
      mood = inflection.mood;
      conj2nd = inflection.conj2nd;
      if (conj2nd) {
        sverb = '687';
      }
    }
    infEdit.html('');
            //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
    if (lemmaShowInf.indexOf('showSubVerb') > -1) {
      infEdit.append(this.createRadioGroupUI("spos", "SubVerbUI", listSubVerb,sverb,null,null,1));
      infEdit.append(this.createRadioGroupUI("tense", "TenseUI", listTense,tense,null,icf[0] == 2));
//      infEdit.append(this.createRadioGroupUI("voice", "VoiceUI", listVoice,voice,null,icf[1] == 2));
      infEdit.append(this.createRadioGroupUI("mood", "MoodUI", listMood,mood,null,icf[2] == 2));
      infEdit.append(this.createRadioGroupUI("num", "NumberUI", listNum,num,null,icf[4] == 2));
      infEdit.append(this.createRadioGroupUI("person", "PersonUI", listPerson,per,null,icf[6] == 2));
      infEdit.append(this.createRadioGroupUI("conj2nd", "V2ndConjUI", listV2ndConj,null,conj2nd,icf[7] == 2));
    } else {// other inflectables use a subset of the following
      if (lemmaShowInf.indexOf('showGender') > -1) {
        infEdit.append(this.createRadioGroupUI("gender", "GenderUI", listGen, gen,null,icf[3] == 2));
      }
      if (lemmaShowInf.indexOf('showAdjGender') > -1) {
        infEdit.append(this.createRadioGroupUI("gender", "AGenderUI", listAdjGen, gen,null,icf[3] == 2));
      }
      if (lemmaShowInf.indexOf('showNumber') > -1) {
        infEdit.append(this.createRadioGroupUI("num", "NumberUI", listNum,num,null,icf[4] == 2));
      }
      if (lemmaShowInf.indexOf('showCase') > -1) {
        infEdit.append(this.createRadioGroupUI("case", "CaseUI", listCase,infcase,null,icf[5] == 2));
      }
      if (lemmaShowInf.indexOf('showAdjConj') > -1) {
        infEdit.append(this.createRadioGroupUI("conj2nd", "A2ndConjUI", list2ndConj,conj2nd,null,icf[7] == 2));
      }
    }
    infEdit.append(this.createRadioGroupUI(null, null, listCtrl));
    infEdit.addClass(lemmaShowInf.join(" "));
    if (pos == verbID) {
      showSub = $('.SubVerbUI button.buttonDiv.selected',infEdit).prop('showSub');
      if (showSub.length) {// ensure sub groups are shown
        infEdit.addClass(showSub);
      }
    }
    if (infID) { //link direct UI for inflecting tokens with the current inflection values.
      infEdit.append('<div class="linkLikeFormsDiv"><span class="linkAttestedFormButton"><u>Link Attested Form</u></span></div>');
    }
    //click to clear
    $('.clearBtnDiv',infEdit).unbind("click").bind("click",function(e) {
      var ctxDiv = infEdit;
      //remove selected and uncertainty from all groups buttons
      ctxDiv.find('.selected').removeClass('selected');
      ctxDiv.find('.uncertain').removeClass('uncertain');
      //check each radioGroup against it's original values and update dirty flag for group
      $('.radioGroupUI',ctxDiv).each(function(index, elem) {
        var radioGroup = $(elem);
        if (radioGroup.prop('origCF') ||  radioGroup.prop('origID')) { // is dirty
          if (!radioGroup.hasClass("dirty")) {
            radioGroup.addClass("dirty");
          }
        } else { // not dirty so remove flags
          if (radioGroup.hasClass("dirty")) {
            radioGroup.removeClass("dirty");
          }
        }
      });
      //check dirty flag of all groups and update UI dirty flag for save button.
      if ( ctxDiv.find('.dirty').length) { //properties have changed
        if (!ctxDiv.hasClass("dirty")) {
          ctxDiv.addClass("dirty");
        }
      } else {
        if (ctxDiv.hasClass("dirty")) {
          ctxDiv.removeClass("dirty");
        }
      }
      if (lemmaVE.linkToInfID) {
        delete lemmaVE.linkToInfID;
        lemmaVE.wordlistVE.setLinkMode(false);
        $('span.linkAttestedFormButton',this.infEdit).html('<u>Link Attested Form</u>');
      }
      e.stopImmediatePropagation();
      return false;
    });
    //save inflection data
    $('.cancelBtnDiv',infEdit).unbind("click").bind("click",function(e) {
      lemmaVE.attestedUI.removeClass("edit");
      if (lemmaVE.linkToInfID) {
        delete lemmaVE.linkToInfID;
        lemmaVE.wordlistVE.setLinkMode(false);
      }
      e.stopImmediatePropagation();
      return false;
    });
    //save inflection data
    $('.saveBtnDiv',infEdit).unbind("click").bind("click",function(e) {
      var infProps = {}, value = '', pname,
          certainty = {'tense':3,'voice':3,'mood':3,'gender':3,'num':3,'case':3,'person':3,'conj2nd':3},
          infPropNames = Object.keys(certainty);
      if (infEdit.hasClass('dirty')) {
        //for each radio group find name and value of selected
        $('.buttonDiv.selected',infEdit).each(function(index,elem) {
            var propname = $(elem).parent().prop('grpName'),
                propval = $(elem).prop('trmID');
            if (infPropNames.indexOf(propname) > -1) {
              infProps[propname] = propval;
              certainty[propname] = $(elem).hasClass('uncertain')?2:1;
              value += (value != ''?' ':'') + $(elem).text();
            }
        });
        infProps['certainty'] = [];
        for (pname in certainty) {
          infProps['certainty'].push(certainty[pname]);
        }
        lemmaVE.saveLemma(null,attestedGID,infID,infProps);
        if (btnDiv) {
          btnDiv.html(value?value:"inflection");
        }
        infEdit.removeClass('dirty');
      }
      lemmaVE.attestedUI.removeClass("edit");
      if (lemmaVE.linkToInfID) {
        delete lemmaVE.linkToInfID;
        lemmaVE.wordlistVE.setLinkMode(false);
      }
      e.stopImmediatePropagation();
      return false;
    });
    if (infID) {
      $('span.linkAttestedFormButton',this.infEdit).unbind("click").bind("click",function(e) {
        if ($(this).text() == 'Link Attested Form') {//switch to linking mode for directly linking attested forms with the same inflection
          $(this).html('<u>Leave link attested mode</u>');
          lemmaVE.linkToInfID = infID;
          lemmaVE.wordlistVE.setLinkMode(true);
        } else {//cancel linking mode
          $(this).html('<u>Link Attested Form</u>');
          delete lemmaVE.linkToInfID;
          lemmaVE.wordlistVE.setLinkMode(false);
          lemmaVE.showLemma();
        }
        e.stopImmediatePropagation();
        return false;
      });
    }
  },

/************* Attested Form Interface ****************/

/**
* put your comment there...
*
*/

  createAttestedUI: function() {
    var lemmaVE = this,i,j, prefix, id, infID, infEntIDs, attestedEntry, nounID = 664, adjID = 674, pronID = 667, verbID = 685,
        pos = this.isLemma && this.entity.pos ? this.entity.pos:"",
        gen = this.isLemma && this.entity.gender ? this.entity.gender:null,
        inflectable = (pos && (pos == this.dataMgr.termInfo.idByTerm_ParentLabel['v.-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['adj.-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['noun-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['pron.-partofspeech'] ||//term dependency
                               pos == this.dataMgr.termInfo.idByTerm_ParentLabel['num.-partofspeech']))? true:false,//term dependency
        entities = [], infMap = {},entity, displayUI, infVal, inflection,
        attested, attestedAnnoTag, attestedAnno, attestedAnnoLabel, entIDs = this.isLemma ? this.entity.entityIDs:"";
    DEBUG.traceEntry("createAttestedUI");
    //create UI container
    this.attestedUI = $('<div class="attestedUI"></div>');
    this.editDiv.append(this.attestedUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="attestedUIHeader"><span>Attestations:</span>'+
                       '<span class="addButton"><u>'+(this.wordlistVE.attestedLinkMode?'Leave link mode':'Add new')+'</u></span></div>'));
    //create a list of tokens and map them to inflections
    if (entIDs && entIDs.length) {
      for (i in entIDs) {
        prefix = entIDs[i].substring(0,3);
        id = entIDs[i].substring(4);
        if ( prefix == "inf") { //inflection so get entities
          infID = id;
          entity = this.dataMgr.getEntity(prefix,id);
          infEntIDs = entity.entityIDs;
          for (j in infEntIDs) {
            prefix = infEntIDs[j].substring(0,3);
            id = infEntIDs[j].substring(4);
            entity = this.dataMgr.getEntity(prefix,id);
            if (entity) {
              entity.tag = infEntIDs[j].replace(':','');
              entity.gid = infEntIDs[j];
              entities.push(entity);
              infMap[entity.tag] = infID;
            } else {
              DEBUG.log('err',"inflection inf:"+infID+" lemma lem:"+lemmaVE.entity.id+" has component that doesn't load "+prefix+id);
            }
          }
        } else {// add to set with no inflection mapping
          entity = this.dataMgr.getEntity(prefix,id);
          if (entity) {
            entity.tag = entIDs[i].replace(':','');
            entity.gid = entIDs[i];
            entities.push(entity);
            infMap[entity.tag] = 0;
          } else {
              DEBUG.log('err',"lemma lem:"+lemmaVE.entity.id+" has component that doesn't load "+prefix+id);
            }
        }
      }
      entities.sort(UTILITY.compareEntities);
      for (i=0; i<entities.length; i++) {
        attested = entities[i];
        infVal = "";
        if (inflectable) {// setup inflection
          infID = infMap[attested.tag];
          if (infID) { //calc inflection string
            inflection = this.dataMgr.getEntity('inf',infID);
            //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
            if (inflection.tense && this.dataMgr.getTermFromID(inflection.tense)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.tense);
              if (inflection.certainty && inflection.certainty[0] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.voice && this.dataMgr.getTermFromID(inflection.voice)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.voice);
              if (inflection.certainty && inflection.certainty[1] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.mood && this.dataMgr.getTermFromID(inflection.mood)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.mood);
              if (inflection.certainty && inflection.certainty[2] == 2) {
                infVal += '(?)';
              }
            }
            if ( pos != verbID && inflection.gender && this.dataMgr.getTermFromID(inflection.gender)) {
              //TODO add code to check multi gender noun, only show inf gender of noun if subset of lemma gender
              infVal +=  this.dataMgr.getTermFromID(inflection.gender);
              if (inflection.certainty && inflection.certainty[3] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.num && this.dataMgr.getTermFromID(inflection.num)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.num);
              if (inflection.certainty && inflection.certainty[4] == 2) {
                infVal += '(?)';
              }
            }
            if (pos != verbID && inflection["case"] && this.dataMgr.getTermFromID(inflection["case"])) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection["case"]);
              if (inflection.certainty && inflection.certainty[5] == 2) {
                infVal += '(?)';
              }
            }
            if (inflection.person && this.dataMgr.getTermFromID(inflection.person)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.person);
              if (inflection.certainty && inflection.certainty[6] == 2) {
                infVal += '(?)';
              }
            }
            if ((pos == verbID || pos == adjID) && inflection.conj2nd && this.dataMgr.getTermFromID(inflection.conj2nd)) {
              infVal += (infVal?' ':'') + this.dataMgr.getTermFromID(inflection.conj2nd);
              if (inflection.certainty && inflection.certainty[7] == 2) {
                infVal += '(?)';
              }
            }
            infVal = (infVal?infVal:"inflection");
          } else { //use placeholder
            infVal = "inflection";
          }
        }
        attestedAnnoTag = null;
        attestedAnno = "Annotate attested form";
        attestedAnnoLabel = "A";
        if (attested.linkedAnoIDsByType && attested.linkedAnoIDsByType[this.glAnnoType]) { //has a glossary annotation
          attestedAnnoTag = "ano"+attested.linkedAnoIDsByType[this.glAnnoType][0];
          temp = this.dataMgr.getEntityFromGID(attestedAnnoTag);
          if (temp && temp.text && temp.text.length) {
            attestedAnno = temp.text;
            if (attestedAnno.length > 250) {
              attestedAnno = attestedAnno.substring(0,249) + "…";
            }
            attestedAnnoLabel = "…"
          }
        }
        attestedEntry = $('<div class="attestedentry">' +
                            '<span class="attestedformloc '+attested.tag+'">' + (attested.locLabel?attested.locLabel:"$nbsp;") + '</span>'+
                            '<span class="attestedform '+attested.tag+'">' +
                            attested.transcr.replace(/aʔi/g,'aï').replace(/aʔu/g,'aü').replace(/ʔ/g,'') + '</span>'+
                            (infVal?'<span class="inflection '+attested.tag+'">' + infVal + '</span>':'') +
                            '<span class="attestedui '+attested.tag+'">' +
                            '<span class="attestedannoui"><span class="attestedannobtn '+attested.tag+'"' + ' title="'+attestedAnno+'"' + '>' +
                                        attestedAnnoLabel + '</span>' +
                            '</span><span class="unlink '+attested.tag+'"><u>unlink</u></span>'+
                            '</span>'+
                          '</div>');
        $('.attestedannobtn',attestedEntry).prop('anoTag',(attestedAnnoTag?attestedAnnoTag:null));
        $('.attestedannobtn',attestedEntry).prop('gid',attested.gid);
        $('.removeanno',attestedEntry).prop('gid',attested.gid);
        $('.inflection',attestedEntry).prop('gid',attested.gid);
        $('.unlink',attestedEntry).prop('gid',attested.gid);//attach entGID of attested to be unlinked
        if (infID) {//attach link to inflection if inflected entity
          $('.inflection',attestedEntry).prop('infID',infID);
          $('.unlink',attestedEntry).prop('infID',infID);
        }
        displayUI.append(attestedEntry);
      }
    }
    this.attestedUI.append(displayUI);
    $('span.attestedform',this.attestedUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'), entTag;
      entTag = classes.match(/(?:tok|cmp)\d+/)[0];
      //trigger selection change
      $('.editContainer').trigger('updateselection',[lemmaVE.id,[entTag],entTag]);
      e.stopImmediatePropagation();
      return false;
    });
    $('span.attestedannobtn',this.attestedUI).unbind("click").bind("click",function(e) {
      //show anno editor with glossary annotation if exist
      lemmaVE.propMgr.showVE("annoVE",$(this).prop('anoTag'),$(this).prop('gid'));
      e.stopImmediatePropagation();
      return false;
    });
    //remove anno
    $('span.removeanno',this.attestedUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          anoTag = classes.match(/ano\d+/);
      if (anoTag && anoTag.length) {
          anoTag = anoTag[0];
          lemmaVE.removeAnno(anoTag,$(this).prop('gid'));
      }
      e.stopImmediatePropagation();
      return false;
    });
    $('span.inflection',this.attestedUI).unbind("click").bind("click",function(e) {
      $('div.edit',lemmaVE.editDiv).removeClass("edit");
      lemmaVE.attestedUI.addClass("edit");
      lemmaVE.initShowInflectionEditUI( $(this).prop('infID'), $(this).prop('gid'),$(this));
      e.stopImmediatePropagation();
      return false;
    });
    $('span.unlink',this.attestedUI).unbind("click").bind("click",function(e) {
      lemmaVE.unlinkAttested( $(this).prop('infID'), $(this).prop('gid'));
      e.stopImmediatePropagation();
      return false;
    });
    //create inflectionUI container
    this.inflectionEditUI = $('<div class="infEditUI"/>');
    this.attestedUI.append(this.inflectionEditUI);
    //attach event handlers
    $('span.addButton',this.attestedUI).unbind("click").bind("click",function(e) {
      if ($(this).text() == 'Add new') {//switch to linking mode
        $(this).html('<u>Leave link mode</u>');
        lemmaVE.wordlistVE.setLinkMode(true);
      } else {//cancel linking mode
        $(this).html('<u>Add new</u>');
        lemmaVE.wordlistVE.setLinkMode(false);
      }
      e.stopImmediatePropagation();
      return false;
    });
    DEBUG.traceExit("createAttestedUI");
  },

/************* Tagging Interface ****************/

/**
* put your comment there...
*
*/

  createTaggingUI: function() {
    var lemmaVE = this,i,j, anoTag, tag, annoID, annoIDs, displayUI, annotation,
        customTagID = this.dataMgr.termInfo.idByTerm_ParentLabel['customtype-tagtype'],//term dependency
        annoTagIDsByType = (this.entity && this.entity.linkedByAnoIDsByType)? this.entity.linkedByAnoIDsByType:[];
    DEBUG.traceEntry("createTaggingUI");
    //create UI container
    this.tagUI = $('<div class="tagUI"></div>');
    this.editDiv.append(this.tagUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create a list of Tags
    if (Object.keys(annoTagIDsByType).length) {
      //create Header
      displayUI.append($('<div class="tagUIHeader"><span>Tags:</span><span class="addButton"><u>Edit tags</u></span></div>'));
      for (i in annoTagIDsByType) {
        if (this.dataMgr.entTagToLabel['trm'+i]) {
          annoIDs = annoTagIDsByType[i];
          tag = this.dataMgr.entTagToLabel['trm'+i];
          for (j in annoIDs) {
            annoID =  annoIDs[j];
            annotation = this.dataMgr.getEntity("ano",annoID);
            if (annotation && tag == "CustomType") {
              text = annotation.text;
            } else {
              text = tag;
            }
            anoTag = "ano" + annoID;
            //TODO add code to display the different types linked vs URL vs text
            displayUI.append(this.createTagEntry(text,anoTag));
          }
        }
      }
    } else {
      //create Header
      displayUI.append($('<div class="tagUIHeader"><span>Tags:</span><span class="addButton"><u>Add tags</u></span></div>'));
    }
    //add or edit tags for this entity
    this.tagUI.append(displayUI);
    $('span.addButton',this.tagUI).unbind("click").bind("click",function(e) {
      if (lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
        lemmaVE.propMgr.showVE("tagVE", lemmaVE.tag);
      }
      e.stopImmediatePropagation();
      return false;
    });
    //remove tag
    $('span.removetag',this.tagUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          anoTag = classes.match(/ano\d+/)[0];
      entPropVE.removeTag(anoTag);
      e.stopImmediatePropagation();
      return false;
    });
    //create input with save button
    DEBUG.traceExit("createTaggingUI");
  },


/**
* put your comment there...
*
* @param tag
* @param anoTag
*/

  createTagEntry: function(tag,anoTag) {
    var lemmaVE = this;
    DEBUG.trace("createTagEntry");
    //create Annotation Entry
    return($('<div class="tagentry '+anoTag+'">' +
         '<span class="tag">' + tag + '</span>'+
         '<span class="removetag '+anoTag+'" title="remove tag '+tag+'">X</span>'+
         '</div>'));
    //create input with save button
  },


/**
* put your comment there...
*
* @param anoTag
*/

  removeTag: function(anoTag) {
    var lemmaVE = this, savedata = {};
    DEBUG.traceEntry("removeTag");
    savedata["entGID"] = this.prefix+":"+this.entID;
    savedata["tagRemoveFromGIDs"] = [anoTag];
    if (DEBUG.healthLogOn) {
      savedata['hlthLog'] = 1;
    }
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveTags.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              lemmaVE.dataMgr.updateLocalCache(data,null);
              lemmaVE.propMgr.showVE();
            }
            if (data.editionHealth) {
              DEBUG.log("health","***Remove Tag***");
              DEBUG.log("health","Params: "+JSON.stringify(savedata));
              DEBUG.log("health",data.editionHealth);
            }
            if (data.errors) {
              alert("An error occurred while trying to retrieve a record. Error: " + data.errors.join());
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve a record. Error: " + error);
        }
    });
    DEBUG.traceExit("removeTag");
  },

/************* Annotation Interface ****************/

/**
* put your comment there...
*
*/

  createAnnotationUI: function() {
    var lemmaVE = this,i,j, prefix, id, infID, infEntIDs,
        displayUI,annotation,
        annoIDsByType = (this.entity && this.entity.linkedAnoIDsByType)? this.entity.linkedAnoIDsByType:[];
    DEBUG.traceEntry("createAnnotationUI");
    //create UI container
    this.annoUI = $('<div class="annoUI"></div>');
    this.editDiv.append(this.annoUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="annoUIHeader"><span>Annotations:</span><span class="addButton"><u>Add new</u></span></div>'));
    //create a list of Annotations
    if (Object.keys(annoIDsByType).length) {
      for (i in annoIDsByType) {
        annoType = this.dataMgr.getTermFromID(i);
        annoIDs = annoIDsByType[i];
        for (j in annoIDs) {
          annoID =  annoIDs[j];
          annotation = this.dataMgr.getEntity("ano",annoID);
          if (annotation) {
            annotation.tag = "ano" + annoID;
            //TODO add code to display the different types linked vs URL vs text
            displayUI.append(this.createAnnotationEntry(annotation));
          }
        }
      }
    }
    this.annoUI.append(displayUI);
    $('div.annotationentry',this.annoUI).unbind("dblclick").bind("dblclick",function(e) {
      var classes = $(this).attr("class"),anoTag,annotation;
      if (classes.match(/ano\d+/)) {
        anoTag = classes.match(/ano\d+/)[0];
      }
      annotation = lemmaVE.dataMgr.getEntityFromGID(anoTag);
      if (annotation && !annotation.readonly && lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
        lemmaVE.propMgr.showVE("annoVE",anoTag);
      } else {
        UTILITY.beep();
      }
    });
    $('span.addButton',this.annoUI).unbind("click").bind("click",function(e) {
      if (lemmaVE.propMgr && lemmaVE.propMgr.showVE) {
        lemmaVE.propMgr.showVE("annoVE");
      }
      e.stopImmediatePropagation();
      return false;
    });
    //remove anno
    $('span.removeanno',this.annoUI).unbind("click").bind("click",function(e) {
      var classes = $(this).attr('class'),
          anoTag = classes.match(/ano\d+/);
      if (anoTag && anoTag.length) {
          anoTag = anoTag[0];
          lemmaVE.removeAnno(anoTag);
      }
      e.stopImmediatePropagation();
      return false;
    });
    //create input with save button
    DEBUG.traceExit("createAnnotationUI");
  },


/**
* put your comment there...
*
* @param anno
*/

  createAnnotationEntry: function(anno) {
    var lemmaVE = this,
        annoType = this.dataMgr.getTermFromID(anno.typeID);
    DEBUG.trace("createAnnotationEntry");
    //create Annotation Entry
    return($('<div class="annotationentry '+annoType+' '+anno.tag+'">' +
         '<span class="annotation">' + anno.text + '</span>'+
         '<span class="modstamp">' + anno.modStamp + '</span>'+
         (anno.readonly?'':'<span class="removeanno '+anno.tag+'" title="remove tag '+anno.tag+'">X</span>')+
         '</div>'));
  },


/**
* put your comment there...
*
* @param anoTag
*/

  removeAnno: function(anoTag,linkFromGID) {
    var lemmaVE = this, savedata = {};
    DEBUG.traceEntry("removeAnno");
    savedata["cmd"] = "removeAno";
    savedata["linkFromGID"] = (linkFromGID?linkFromGID: this.prefix+":"+this.entID);
    savedata["anoTag"] = anoTag;
    //jqAjax synch save
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: basepath+'/services/saveAnno.php?db='+dbName,//caution dependency on context having basepath and dbName
        data: savedata,
        async: true,
        success: function (data, status, xhr) {
            if (typeof data == 'object' && data.success && data.entities) {
              //update data
              lemmaVE.dataMgr.updateLocalCache(data,null);
              if (lemmaVE.wordlistVE && lemmaVE.prefix=='lem') {
                lemmaVE.wordlistVE.updateLemmaEntry(lemmaVE.entID);
              }
              lemmaVE.propMgr.showVE();
              if (lemmaVE.propMgr.annoVE) {
                lemmaVE.propMgr.annoVE.removeDirtyMarkers();
              }
            }
            if (data.errors) {
              alert("An error occurred while trying to remove annotation record. Error: " + data.errors.join());
            }
        },
        error: function (xhr,status,error) {
            // add record failed.
            alert("An error occurred while trying to retrieve a record. Error: " + error);
        }
    });
    DEBUG.traceExit("removeAnno");
  },


/**
* put your comment there...
*
*/

  createLinkTypeUI: function() {//link type ui for lemma
    var lemmaVE = this,
        value, linkTypeID = (this.linkTypeID),
        valueEditable = (this.prefix == "lem" && this.entity && !this.entity.readonly),
        seeLinkTypeID = this.dataMgr.termInfo.idByTerm_ParentLabel["see-lemmalinkage"],//warning! term dependency
        treeLinkTypeName = this.id+'linktypetree';
    DEBUG.traceEntry("createLinkTypeUI");
    if (!linkTypeID) {
      linkTypeID = seeLinkTypeID;
    }
    value = this.dataMgr.getTermFromID(linkTypeID);
    //create UI container
    this.linkTypeUI = $('<div class="linkTypeUI"></div>');
    this.editDiv.append(this.linkTypeUI);
    //create label with Add new button
    this.linkTypeUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement'+(valueEditable?"":" readonly")+'">Current Linktype '+value+'</div>'+
                          ((this.entity.readonly)?'':('<span class="addButton"><u>Add New '+value+' link</u></span></div>'))+
                          '</div>'));
    //create input with selection tree
    this.linkTypeUI.append($('<div class="propEditUI">'+
                    '<div class="propEditElement"><div id="'+ treeLinkTypeName+'"></div></div>'+
                    '</div>'));
    this.linkTypeTree = $('#'+treeLinkTypeName,this.linkTypeUI);
    this.linkTypeTree.jqxTree({
           source: this.dataMgr.linkTypes,
           hasThreeStates: false, checkboxes: false,
           theme:'energyblue'
    });
    $('.propEditElement',this.linkTypeUI).addClass('linkTypeUI');
    //attach event handlers
      if (valueEditable) {
      //click to edit
        $('div.valueLabelDiv',this.linkTypeUI).unbind("click").bind("click",function(e) {
          $('div.edit',lemmaVE.editDiv).removeClass("edit");
          lemmaVE.linkTypeUI.addClass("edit");
          //init tree to current type
          var curItem = $('#trm'+(lemmaVE.linkTypeID?lemmaVE.linkTypeID:seeLinkTypeID), lemmaVE.linkTypeTree),
              offset = 0;
          if (curItem && curItem.length) {
            curItem = curItem.get(0);
            lemmaVE.suppressSubSelect = true;
            lemmaVE.linkTypeTree.jqxTree('selectItem',curItem);
            //expand selected item sub tree if needed
            curItem = lemmaVE.linkTypeTree.jqxTree('getSelectedItem');
            while (curItem && curItem.parentElement) {
              lemmaVE.linkTypeTree.jqxTree('expandItem',curItem.parentElement);
              curItem = lemmaVE.linkTypeTree.jqxTree('getItem',curItem.parentElement);
            }
            curItem = lemmaVE.linkTypeTree.jqxTree('getItem',$('li:first',lemmaVE.linkTypeTree).get(0));
            while (curItem && !curItem.selected) {
              offset += 25;
              if (curItem.isExpanded) {
                offset += 2;
              }
              curItem = lemmaVE.linkTypeTree.jqxTree('getNextItem',curItem.element);
            }
            delete lemmaVE.suppressSubSelect;
          }
          setTimeout(function(){
              $('.linkTypeUI',lemmaVE.linkTypeUI).scrollTop(offset);
            },50);
          e.stopImmediatePropagation();
          return false;
        });
        //blur to cancel
        this.linkTypeTree.unbind("blur").bind("blur",function(e) {
          var linkType = lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID);
          $('div.valueLabelDiv',lemmaVE.linkTypeUI).html("Current Linktype "+linkType);
          $('span.addButton',lemmaVE.linkTypeUI).html("<u>Add New "+linkType+" link</u>");
          lemmaVE.linkTypeUI.removeClass("edit");
        });
        //change sequence type
        this.linkTypeTree.on('select', function (event) {
            if (lemmaVE.suppressSubSelect) {
              return;
            }
            var args = event.args, dropDownContent = '',
                linkType,
                item =  lemmaVE.linkTypeTree.jqxTree('getItem', args.element);
            if (item.value && item.value != lemmaVE.linkTypeID) {//user selected to change sequence type
              //save new subtype to entity
              lemmaVE.linkTypeID = item.value;
              linkType = lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID)
              $('div.valueLabelDiv',lemmaVE.linkTypeUI).html("Current Linktype "+linkType);
              $('span.addButton',lemmaVE.linkTypeUI).html("<u>Add New "+linkType+" link</u>");
            }
            lemmaVE.linkTypeUI.removeClass("edit");
        });
        //attach event handlers
        $('span.addButton',lemmaVE.linkTypeUI).unbind("click").bind("click",function(e) {
            var linkType;
            lemmaVE.linkTypeID = lemmaVE.linkTypeID?lemmaVE.linkTypeID:seeLinkTypeID;
            linkType = lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID);
            if (lemmaVE.wordlistVE && lemmaVE.wordlistVE.setLinkRelatedMode) {
              lemmaVE.wordlistVE.setLinkRelatedMode(true);
              $('span.addButton',lemmaVE.linkTypeUI).html("Click Lemma for New "+linkType);
            }
          e.stopImmediatePropagation();
          return false;
        });
      }
    DEBUG.traceExit("createLinkTypeUI");
  },

/************* Related Interface ****************/

/**
* put your comment there...
*
*/

  createRelatedUI: function() {
    var lemmaVE = this,i,j, prefix, id, entGID, relEntIDs,
        displayUI, relTypeUIDiv, entity, entities, relEntry,
        relEntGIDsByType = (this.entity && this.entity.relatedEntGIDsByType)? this.entity.relatedEntGIDsByType:[];
    DEBUG.traceEntry("createRelatedUI");
    //create UI container
    this.relUI = $('<div class="relUI"></div>');
    this.editDiv.append(this.relUI);
    displayUI = $('<div class="propDisplayUI"/>');
    //create Header
    displayUI.append($('<div class="relUIHeader"><span>Related:</span></div>'));
    //create a list of Related entities by relationship
    if (Object.keys(relEntGIDsByType).length) {
      for (i in relEntGIDsByType) {
        linkType = this.dataMgr.getTermFromID(i);
        if (linkType == "See") {
          relTypeUIDiv =$('<div class="relUISection expand"><span class="btnRelUIExpander"/><span class="relUIHeader">'+linkType+'</span></div>');
        } else {
          relTypeUIDiv =$('<div class="relUISection"><span class="btnRelUIExpander"/><span class="relUIHeader">'+linkType+'</span></div>');
        }
        displayUI.append(relTypeUIDiv);
        relEntIDs = relEntGIDsByType[i];
        entities = [];
        for (j in relEntIDs) {
          entGID =  relEntIDs[j];
          entities.push(this.dataMgr.getEntityFromGID(entGID));
        }
        entities.sort(UTILITY.compareEntities);
        for (j in entities) {
          entity =  entities[j];
          if (entity) {
            entity.tag = entGID.replace(':','');
            //TODO add code to display the different types linked vs URL vs text
            relEntry = this.createRelatedEntry(entity);
            relEntry.prop('tag',entity.tag);
            $('span.unlink',relEntry).prop('entGID',entGID);
            $('span.unlink',relEntry).prop('typeID',i);
            relTypeUIDiv.append(relEntry);
          }
        }
      }
    }
    this.relUI.append(displayUI);
    $('span.btnRelUIExpander',this.relUI).unbind("click").bind("click",function(e) {
      var isExpanded = $(this).parent().hasClass('expand');
      if (isExpanded) {
        $(this).parent().removeClass('expand');
      } else {
        $(this).parent().addClass('expand');
      }
      e.stopImmediatePropagation();
      return false;
    });
    $('div.relationentry',this.relUI).unbind("dblclick").bind("dblclick",function(e) {
      var entTag = $(this).prop('tag'),entity;
      entity = lemmaVE.dataMgr.getEntityFromGID(entTag);
      if (entity) {
        lemmaVE.showLemma("lem",entity.id);
      } else {
        UTILITY.beep();
      }
    });
    $('span.unlink',this.relUI).unbind("click").bind("click",function(e) {
      lemmaVE.unlinkRelated( $(this).prop('typeID'), $(this).prop('entGID'));
      e.stopImmediatePropagation();
      return false;
    });
    DEBUG.traceExit("createRelatedUI");
  },

  /**
  * put your comment there...
  *
  * @param entity
  */

  createRelatedEntry: function(entity) {
    var lemmaVE = this;
    DEBUG.trace("createRelatedEntry");
    //create Related Entry
    return($('<div class="relationentry '+entity.tag+'">' +
         '<span class="related">' + entity.value + '</span>'+
         '<span class="unlink '+entity.tag+'"><u>unlink</u></span>'+
         '</div>'));
  },


/**
* put your comment there...
*
* @param lemProps
* @param tokGID
* @param infID
* @param infProps
* @param isLink
*/

  saveLemma: function(lemProps, tokGID, infID, infProps, isLink) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("saveLemma");
    if (this.prefix != "lem") {//create lem cases
      savedata["cmd"] = "createLem";
      savedata["tokGID"] = this.prefix+":"+this.entID;
      savedata["lemProps"] = lemProps;
      if (this.catID) {
        savedata["catID"] = this.catID;
      } else if (this.ednID) {
        savedata["ednID"] = this.ednID;
      }
    } else if(!tokGID) {// update lem case
      savedata["cmd"] = "updateLem";
      savedata["lemID"] = this.entID;
      savedata["lemProps"] = lemProps;
    } else if (!infProps) {//lem with tokGID and no inflection properties so link or unlink
      savedata["cmd"] = (isLink?"linkTok":"unlinkTok");
      savedata["lemID"] = this.entID;
      savedata["tokGID"] = tokGID;
      if (infID) {
        savedata["infID"] = infID;
      }
    } else {//inflection
      savedata["cmd"] = "inflectTok";
      savedata["lemID"] = this.entID;
      savedata["tokGID"] = tokGID;
      if (infID) {
        savedata["infID"] = infID;
      }
      if (infProps) {
        savedata["infProps"] = infProps;
      }
    }
    if (DEBUG.healthLogOn) {
      savedata['hlthLog'] = 1;
    }
    //jqAjax synch save
    $.ajax({
      type: 'POST',
      dataType: 'json',
        url: basepath+'/services/saveLemma.php?db='+dbName,//caution dependency on context having basepath and dbName
      data: savedata,
      async: true,
      success: function (data, status, xhr) {
        var newLemID, newCatID, oldLemID = savedata["lemID"],
                tokGID =savedata["tokGID"], cmd = savedata["cmd"];
        if (typeof data == 'object' && data.success && data.entities) {
          //update data
              lemmaVE.dataMgr.updateLocalCache(data,null);
          switch (cmd) {
            case "createLem":
              if (data.entities.insert) {
                if (data.entities.insert.lem) {
                  //find new lemma id
                  for (entID in data.entities.insert.lem) {
                    newLemID = entID;
                    break;
                  }
                }
                if (data.entities.insert.cat) {
                  //find new cat id
                  for (entID in data.entities.insert.cat) {
                    newCatID = entID;
                    lemmaVE.catID = newCatID;
                    break;
                  }
                }
                if (lemmaVE.wordlistVE) {
                  //REPLACE WITH insertLemmaEntry and give tokGID as hint (we don't know if user changed spelling)
                  if (newCatID) {
                    lemmaVE.wordlistVE.refreshDisplay(newCatID, newLemID);
                  } else {
                    lemmaVE.wordlistVE.insertLemmaEntry(newLemID,tokGID);
                    lemmaVE.wordlistVE.removeWordEntry(tokGID);
                  }
                }
                    lemmaVE.showLemma('lem',newLemID);
              }
              break;
            case "updateLem":
              //REPLACE WITH updateLemmaEntry
              //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
              if (lemmaVE.wordlistVE) {
                lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
              }
              if (Object.keys(savedata.lemProps).indexOf('phonology') == -1 ||
                  savedata.lemProps['phonology']) {
                lemmaVE.showLemma('lem', oldLemID);
              }
              break;
            case "linkTok":
              //REPLACE WITH updateLemmaEntry
              //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
              lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                  if (!lemmaVE.linkToInfID) {//if linkTo then don't update lemmaVE display, still in link mode
                    lemmaVE.showLemma('lem',oldLemID);
              }
              lemmaVE.wordlistVE.removeWordEntry(tokGID);
              break;
            case "unlinkTok":
              //REPLACE WITH updateLemmaEntry
              //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
              if (lemmaVE.wordlistVE) {
                lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
              }
                  lemmaVE.showLemma('lem',oldLemID);
                  lemmaVE.wordlistVE.insertWordEntry(tokGID,'lem'+oldLemID);
              break;
            case "inflectTok":
              //REPLACE WITH updateLemmaEntry
              //lemmaVE.wordlistVE.refreshDisplay(null, oldLemID);
              if (lemmaVE.wordlistVE) {
                lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
              }
                  lemmaVE.showLemma('lem',oldLemID);
              break;
          }
        }
        if (data.editionHealth) {
              DEBUG.log("health","***Save Lemma cmd="+cmd+"***");
              DEBUG.log("health","Params: "+JSON.stringify(savedata));
              DEBUG.log("health",data.editionHealth);
        }
        if (data.errors) {
          alert("An error occurred while trying to retrieve a record. Error: " + data.errors.join());
        }
      },
        error: function (xhr,status,error) {
        // add record failed.
        alert("An error occurred while trying to retrieve a record. Error: " + error);
      }
    });
    DEBUG.traceExit("saveLemma");
  },


/**
* put your comment there...
*
* @param toGID
*/

  linkRelatedEntity: function(toGID) {
    var lemmaVE = this,
        fromGID = 'lem:' + lemmaVE.entID,
        linkTypeID = lemmaVE.linkTypeID;
    this.linkRelated(fromGID, linkTypeID, toGID);
    $('span.addButton',lemmaVE.linkTypeUI).html("<u>Add New "+lemmaVE.dataMgr.getTermFromID(lemmaVE.linkTypeID)+" link</u>");
  },

/**
* put your comment there...
*
*/

  afterUpdate: function() {
    this.wordlistVE.updateLemmaEntry(this.entID);
  },


/**
* put your comment there...
*
* @param fromGID
* @param linkTypeID
* @param toGID
*/

  linkRelated: function(fromGID, linkTypeID, toGID) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("linkRelated");
    if (linkTypeID && fromGID && toGID) {//requires all params
      savedata['fromGID'] = fromGID;
      savedata['linkTypeID'] = linkTypeID;
      savedata['toGID'] = toGID;
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax async save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveLink.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var oldLemID = lemmaVE.entID;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                if (lemmaVE.wordlistVE) {
                  lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                }
                lemmaVE.showLemma();
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Unlink Related***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to remove link. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to remove link. Error: " + error);
          }
      });
    } else {
      DEBUG.log("gen","invalid call to linkRelated (" + fromGID + " - " + linkTypeID + " - " + toGID + ")");
    }
    DEBUG.traceExit("linkRelated");
  },


/**
* put your comment there...
*
* @param linkTypeID
* @param string entGID Entity global id (prefix:id)
*/

  unlinkRelated: function(linkTypeID, entGID) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("unlinkRelated");
    if (linkTypeID && entGID) {//requires both params
      savedata["cmd"] = "removeLink";
      savedata['lemID'] = this.entID;
      savedata['linkTypeID'] = linkTypeID;
      savedata['entGID'] = entGID;
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax async save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveLemma.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var oldLemID = lemmaVE.entID, newCatID;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                if (lemmaVE.wordlistVE) {
                  lemmaVE.wordlistVE.updateLemmaEntry(oldLemID);
                }
                lemmaVE.showLemma();
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Unlink Related***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to remove link. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to remove link. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("unlinkRelated");
  },


/**
* put your comment there...
*
*/

  deleteLemma: function() {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("deleteLemma");
    if (this.prefix == 'lem' && this.entID) {//requires lemID
      savedata['lemID'] = this.entID;
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax async save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/deleteLemma.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var oldLemID, i, j, entGID, inflection;
              if (typeof data == 'object' && data.success && data.entities) {
                //update data
                oldLemID = lemmaVE.entID;
                if (lemmaVE.entity && lemmaVE.entity.entityIDs && lemmaVE.entity.entityIDs.length) {
                  for (i in lemmaVE.entity.entityIDs) {
                    entGID = lemmaVE.entity.entityIDs[i];
                    if (entGID.match(/inf/)) {
                      inflection = lemmaVE.dataMgr.getEntityFromGID(entGID);
                      if (inflection && inflection.entityIDs && inflection.entityIDs.length) {
                        for (j in inflection.entityIDs) {
                          entGID = inflection.entityIDs[j];
                          lemmaVE.wordlistVE.insertWordEntry(entGID,'lem'+oldLemID);
                        }
                      }
                    } else {
                      lemmaVE.wordlistVE.insertWordEntry(entGID,'lem'+oldLemID);
                    }
                  }
                }
                lemmaVE.dataMgr.updateLocalCache(data,null);
                if (lemmaVE.wordlistVE){
                  if (!lemmaVE.wordlistVE.nextWord(lemmaVE.tag)) {
                    if (!lemmaVE.wordlistVE.prevWord(lemmaVE.tag)) {
                      lemmaVE.entID = null;
                      lemmaVE.showLemma();
                    };
                  }
                  lemmaVE.wordlistVE.removeWordlistEntry('lem'+oldLemID);
                  if (lemmaVE.tag) {
                    lemmaVE.wordlistVE.scrollEntIntoView(lemmaVE.tag);
                  }
                }
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Delete Lemma***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to remove link. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to remove link. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("deleteLemma");
  },


/**
* put your comment there...
*
* @param compAnalysis
* @param rootkey
*/

  saveCompoundAnalysis: function(compAnalysis, rootkey) {
    var savedata ={},
        lemmaVE = this;
    DEBUG.traceEntry("saveCompoundAnalysis");
    if (compAnalysis && Object.keys(compAnalysis).length>2 || compAnalysis == "") {//minimally valid compound analysis
      savedata["compAnalysis"] = compAnalysis;
      if (rootkey && compAnalysis && compAnalysis[rootkey] && compAnalysis[rootkey].lemID) {
        savedata['lemID'] = compAnalysis[rootkey].lemID;
        savedata['rootkey'] = rootkey;
      } else {
        savedata['lemID'] = this.entID;
      }
      if (DEBUG.healthLogOn) {
        savedata['hlthLog'] = 1;
      }
      //jqAjax synch save
      $.ajax({
          type: 'POST',
          dataType: 'json',
          url: basepath+'/services/saveCompoundAnalysis.php?db='+dbName,//caution dependency on context having basepath and dbName
          data: savedata,
          async: true,
          success: function (data, status, xhr) {
              var newLemID, newCatID,updLemID, oldCompAnalysis;
              if (typeof data == 'object' && data.success && data.entities) {
                if (lemmaVE.isLemma && lemmaVE.entity && lemmaVE.entity.compAnalysis) {
                  oldCompAnalysis = lemmaVE.entity.compAnalysis;
                }
                //update data
                lemmaVE.dataMgr.updateLocalCache(data,null);
                if (compAnalysis) {
                  //TODO find out new lemmas and insert them
                  //change lemma to Update
                  if (data.entities && data.entities.insert && data.entities.insert.lem &&
                      Object.keys(data.entities.insert.lem).length) {
                    for (newLemID in data.entities.insert.lem) {
                      lemmaVE.wordlistVE.insertLemmaEntry(newLemID);
                      lemmaVE.wordlistVE.updateLemmaLookup('lem'+newLemID);
                    }
                  }
                  if (data.entities && data.entities.update && data.entities.update.lem &&
                      Object.keys(data.entities.update.lem).length) {
                    for (updLemID in data.entities.update.lem) {
                      lemmaVE.wordlistVE.updateLemmaEntry(updLemID);
                      lemmaVE.wordlistVE.updateLemmaLookup('lem'+updLemID,((updLemID == lemmaVE.entID)?oldCompAnalysis:null));
                    }
                  }
                  lemmaVE.entity = lemmaVE.dataMgr.getEntity('lem',lemmaVE.entID);
                }
                lemmaVE.showLemma();
              }
              if (data.editionHealth) {
                DEBUG.log("health","***Save Compound Analysis***");
                DEBUG.log("health","Params: "+JSON.stringify(savedata));
                DEBUG.log("health",data.editionHealth);
              }
              if (data.errors) {
                alert("An error occurred while trying to save Compound Analysis. Error: " + data.errors.join());
              }
          },
          error: function (xhr,status,error) {
              // add record failed.
              alert("An error occurred while trying to retrieve a record. Error: " + error);
          }
      });
    }
    DEBUG.traceExit("saveCompoundAnalysis");
  },

  /**
  * get phonology string
  *
  */

  getPhonologyString: function(strStart) {
    //run through graphemes getting type information and interleave with cursor info
    var i = 0, inc, chr, chr2, chr3, chr4, curtyp, typ, grapheme,
        phonoPart, phonoParts = [], cnt = strStart.length;
    while (i<cnt) {
      chr = strStart[i].toLowerCase();
      // convert multi-byte to grapheme - using greedy lookup
      if (this._graphemeMap[chr]){
        //check next character included
        inc=1;
        if (i+1 < cnt){
          chr2 = strStart[i+1].toLowerCase();
        }
        if (this._graphemeMap[chr][chr2]){ // another char for grapheme
          inc++;
          if (i+2 < cnt){
            chr3 = strStart[i+2].toLowerCase();
          }
          if (this._graphemeMap[chr][chr2][chr3]){ // another char for grapheme
            inc++;
            if (i+3 < cnt){
              chr4 = strStart[i+3].toLowerCase();
            }
            if (this._graphemeMap[chr][chr2][chr3][chr4]){ // another char for grapheme
              inc++;
              if (!this._graphemeMap[chr][chr2][chr3][chr4]["srt"]){ // invalid sequence
                DEBUG.log("warn","error '" + strPhono + "' has invalid sequence starting at character " + i+ " " +chr + chr2 + chr3 + chr4 +" has no sort code");
                grapheme = chr + chr2 + chr3;
                typ = this._graphemeMap[chr][chr2][chr3]['typ'];
                break;
              }else{//found valid grapheme, save it
                grapheme = chr + chr2 + chr3 + chr4;
                typ = this._graphemeMap[chr][chr2][chr3][chr4]['typ'];
              }
            }else if (!this._graphemeMap[chr][chr2][chr3]["srt"]){ // invalid sequence
              DEBUG.log("warn","error '" + strPhono + "' has incomplete sequence starting at character " + i+ " " +chr + chr2 + chr3 +" has no sort code");
              grapheme = chr + chr2;
              typ = this._graphemeMap[chr][chr2]['typ'];
              break;
            }else{//found valid grapheme, save it
              grapheme = chr + chr2 + chr3;
              typ = this._graphemeMap[chr][chr2][chr3]['typ'];
              }
          }else if (!this._graphemeMap[chr][chr2]["srt"]){ // invalid sequence
            DEBUG.log("warn","error '" + strPhono + "' has incomplete sequence starting at character " + i+ " " +chr + chr2 +" has no sort code");
            grapheme = chr ;
            typ = this._graphemeMap[chr]['typ'];
            break;
          }else{//found valid grapheme, save it
              grapheme = chr + chr2;
              typ = this._graphemeMap[chr][chr2]['typ'];
            }
        }else if (!this._graphemeMap[chr]["srt"]){ // invalid sequence
          DEBUG.log("warn","error '" + strPhono + "' has incomplete sequence starting at character " + i+ " " +chr +" has no sort code");
          grapheme = "";
          typ = 'E';
          break;
        }else{//found valid grapheme, save it
              grapheme = chr ;
              typ = this._graphemeMap[chr]['typ'];
        }
      }
      i += inc;
      switch (typ) {
        case 'C':
        case 'CH':// aspirated case
          if (curtyp == 'C' || curtyp == 'CH') {
            //accumulate type cluster
            phonoPart += grapheme;
          } else {
            //push cluster and start new cluster
            if (phonoPart) {
              phonoParts.push(phonoPart);
            }
            if (grapheme && grapheme == "ʔ") {
              //vowel carrier gets ignored
              phonoPart = '';
            } else {
              phonoPart = grapheme;
            }
          }
          curtyp = typ;
          break;
        case 'V':
        case 'VA':// aspirated case
        case 'M'://modifier
          if (curtyp == 'V' || curtyp == 'VA' || curtyp == 'M') {
            //accumulate type cluster
            phonoPart += grapheme;
          } else {
            //push cluster and start new cluster
            if (phonoPart) {
              phonoParts.push(phonoPart);
            }
            phonoPart = grapheme;
          }
          curtyp = typ;
          break;
        default:
          //ingnore 
      }
    }//end while
    if (phonoPart) {
      phonoParts.push(phonoPart);
    }
    return phonoParts.join('-');
  },

  // specal table of equivalent characters.
  _equivalanceCharMap : {
    "a" :"ā",
    "ā" :"a",
    "e" :"ē",
    "ē" :"e",
    "i" :"ī",
    "ī" :"i",
    "o" :"ō",
    "ō" :"o",
    "r̥" :"ṛ",
    "ṛ" :"ṝ",
    "ṝ" :"ṛ",
    "u" :"ū",
    "ū" :"u"
    },

  // valid grapheme multibyte sequences with sort codes and types
  _graphemeMap : {
    "0": { "srt": "700", "typ": "N" },
    "½": { "srt": "705", "typ": "N" },
    "1": {
      "0": { "srt": "760", "typ": "N",
        "0": { "srt": "780", "typ": "N",
          "0": { "srt": "790", "typ": "N" }}},
      "srt": "710", "typ": "N" },
    "2": {"srt": "720", "typ": "N" ,
      "0": { "srt": "770", "typ": "N" }},
    "3": { "srt": "730", "typ": "N" ,
      "0": { "srt": "773", "typ": "N" }},
    "4": { "srt": "740", "typ": "N" ,
      "0": { "srt": "774", "typ": "N" }},
    "5": { "srt": "755", "typ": "N" ,
      "0": { "srt": "775", "typ": "N" }},
    "6": { "srt": "756", "typ": "N" ,
      "0": { "srt": "776", "typ": "N" }},
    "7": { "srt": "757", "typ": "N" ,
      "0": { "srt": "777", "typ": "N" }},
    "8": { "srt": "758", "typ": "N" ,
      "0": { "srt": "778", "typ": "N" }},
    "9": { "srt": "759", "typ": "N" ,
      "0": { "srt": "779", "typ": "N" }},
    "‧": { "srt": "800", "typ": "P" },
    "×": { "srt": "801", "typ": "P" },
    "∈": { "srt": "830", "typ": "P" },
    "⌇": { "srt": "880", "typ": "P" },
    "◊": { "srt": "885", "typ": "I" },
    "◈": { "srt": "885", "typ": "I" },
    "○": { "srt": "820", "typ": "P" },
    "⊗": { "srt": "822", "typ": "P" },
    "◎": { "srt": "823", "typ": "P" },
    "◦": { "srt": "810", "typ": "P" },
    "°": { "srt": "810", "typ": "P" },
    "∙": { "srt": "804", "typ": "I" },
    "☒": { "srt": "845", "typ": "P" },
    "☸": { "srt": "840", "typ": "P" },
    "❀": { "srt": "851", "typ": "P" },
    "⁝": { "srt": "806", "typ": "P" },
    "⏑": { "srt": "807", "typ": "P" },
    "⎼": { "srt": "808", "typ": "P" },
    "❉": { "srt": "850", "typ": "P" },
    "–": { "srt": "890", "typ": "P" },
    "—": { "srt": "890", "typ": "P" },
    "|": {
      "|": { "srt": "870", "typ": "P" },
      "srt": "860", "typ": "P" },
    "◯": { "srt": "821", "typ": "I" },
    ":": { "srt": "803", "typ": "P" },
    "*": { "srt": "000", "typ": "V" },
    ".": { "srt": "189", "typ": "V" },
    "_": { "srt": "599", "typ": "C" },
    "ʔ": { "srt": "195", "typ": "C" },
    "?": { "srt": "990", "typ": "O" },
    "+": { "srt": "953", "typ": "O" },
    "\/": {
      "\/": {
        "\/": { "srt": "954", "typ": "O" }}},
    "#": { "srt": "956", "typ": "O" },
    "…": { "srt": "955", "typ": "O" },
    "a": {
      "͚": {
        "i": { "srt": "208","ssrt":"218", "typ": "VA" },
        "u": { "srt": "228","ssrt":"238", "typ": "VA" },
        "srt": "108", "typ": "V" },
      "̣": { "srt": "107", "typ": "V" },
      "i": { "srt": "200","ssrt":"210", "typ": "VA" },
      "u": { "srt": "220","ssrt":"230", "typ": "VA" },
      "srt": "100", "typ": "V" },
    "á": {
      "i": { "srt": "202","ssrt":"212", "typ": "VA" },
      "u": { "srt": "222","ssrt":"232", "typ": "VA" },
      "srt": "102", "typ": "V" },
    "à": {
      "i": { "srt": "203","ssrt":"213", "typ": "VA" },
      "u": { "srt": "223","ssrt":"233", "typ": "VA" },
      "srt": "103", "typ": "V" },
    "ȧ": { "srt": "106", "typ": "V" },
    "â": { "srt": "104", "typ": "V" },
    "ā": {
      "́": { "srt": "102","ssrt":"112", "typ": "V" },
      "̀": { "srt": "103","ssrt":"113", "typ": "V" },
      "̆": { "srt": "101","ssrt":"111", "typ": "V" },
      "srt": "101","ssrt":"110", "typ": "V" },
    "ã": {
      "i": { "srt": "204","ssrt":"214", "typ": "VA" },
      "u": { "srt": "224","ssrt":"234", "typ": "VA" },
      "srt": "104", "typ": "V" },
    "ǎ": { "srt": "105", "typ": "V" },
    "b": {
      "͟": { "h": { "srt": "531", "typ": "C" }},
      "̄": { "srt": "522", "typ": "C" },
      "h": { "srt": "530", "typ": "CH" },
      "srt": "520", "typ": "C" },
    "ḅ": { "srt": "521", "typ": "C" },
    "c": {
      "̱": { "srt": "321", "typ": "C" },
      "̄": { "srt": "322", "typ": "C" },
      "̂": { "srt": "329", "typ": "C" },
      "h": { "srt": "330", "typ": "CH" },
      "srt": "320", "typ": "C" },
    "ć": { "srt": "329", "typ": "C" },
    "d": {
      "h": { "srt": "480", "typ": "CH" },
      "srt": "470", "typ": "C" },
    "ḏ": { "srt": "471", "typ": "C" },
    "ḍ": {
      "̄": { "srt": "412", "typ": "C" },
      "͟": { "h": { "srt": "421", "typ": "C" } },
      "̱": { "srt": "411", "typ": "C" },
      "͞": { "h": { "srt": "422", "typ": "C" } },
      "h": { "srt": "420", "typ": "CH" },
      "srt": "410", "typ": "C" },
    "e": {
      "͚": { "srt": "208", "typ": "V" },
      "̣": { "srt": "207", "typ": "V" },
      "srt": "200", "typ": "V" },
    "é": { "srt": "202", "typ": "V" },
    "è": { "srt": "203", "typ": "V" },
    "ê": { "srt": "204", "typ": "V" },
    "ě": { "srt": "205", "typ": "V" },
    "ĕ": { "srt": "200", "typ": "V" },
    "ē": {
      "̆": { "srt": "201", "typ": "V" },
      "srt": "201", "typ": "V" },
    "ẽ": { "srt": "204", "typ": "V" },
    "ḗ": { "srt": "202", "typ": "V" },
    "ḕ": { "srt": "203", "typ": "V" },
    "g": {
      "̱": { "srt": "291", "typ": "C" },
      "h": { "srt": "300", "typ": "C" },
      "srt": "290", "typ": "C" },
    "ḡ": {
      "̱": { "srt": "293", "typ": "C" },
      "srt": "292", "typ": "C" },
    "h": {
      "̄": { "srt": "652", "typ": "C" },
      "̮": { "srt": "252", "typ": "M" },
      "srt": "650", "typ": "C" },
    "ḣ": { "srt": "654", "typ": "C" },
    "ḥ": { "srt": "250", "typ": "M" },
    "ẖ": { "srt": "251", "typ": "M" },
    "ḫ": { "srt": "252", "typ": "M" },
    "i": {
      "͚": { "srt": "128", "typ": "V" },
      "srt": "120", "typ": "V" },
    "ï": { "srt": "120", "typ": "V" },
    "í": { "srt": "122", "typ": "V" },
    "ì": { "srt": "123", "typ": "V" },
    "î": { "srt": "124", "typ": "V" },
    "ǐ": { "srt": "125", "typ": "V" },
    "ī": {
      "́": { "srt": "122","ssrt":"132", "typ": "V" },
      "̀": { "srt": "123","ssrt":"133", "typ": "V" },
      "̆": { "srt": "121","ssrt":"131", "typ": "V" },
      "̃": { "srt": "124","ssrt":"134", "typ": "V" },
      "srt": "121","ssrt":"130", "typ": "V" },
    "ĩ": { "srt": "124", "typ": "V" },
    "j": {
      "̄": { "srt": "342", "typ": "C" },
      "̱": {
        "̄": { "srt": "343", "typ": "C" },
        "srt": "341", "typ": "C" },
      "h": { "srt": "350", "typ": "CH" },
      "srt": "340", "typ": "C" },
    "ĵ": { "srt": "349", "typ": "C" },
    "k": { "̄": { "srt": "262", "typ": "C" },
      "͟": { "h": { "srt": "281", "typ": "C" } },
      "h": { "srt": "280", "typ": "CH" },
      "srt": "260", "typ": "C" },
    "ḱ": {
      "h": { "srt": "289", "typ": "C" },
      "srt": "270", "typ": "C" },
    "ḵ": { "srt": "261", "typ": "C" },
    "l": {
      "̥": {
        "̄": {
          "̆": { "srt": "181","ssrt":"191", "typ": "V" },
          "́": { "srt": "182","ssrt":"192", "typ": "V" },
          "srt": "181","ssrt":"191", "typ": "V" },
        "́": { "srt": "182", "typ": "V" },
        "̂": { "srt": "184", "typ": "V" },
        "srt": "180", "typ": "V" },
      "srt": "570", "typ": "C" },
    "ḻ": {"srt": "661", "typ": "C" },
    "ḷ": {"srt": "660", "typ": "C",
  //      "·": { "srt": "247", "typ": "MT" },
      "h": { "srt": "670", "typ": "CH" }},
    "m": {
      "̂": { "srt": "546", "typ": "C" },
      "̄": { "srt": "542", "typ": "C" },
      "̐": { "srt": "242", "typ": "M" },
  //      "·": { "srt": "246", "typ": "MT" },
      "̥": { "srt": "549", "typ": "C" },
      "̱": { "srt": "541", "typ": "C" },
      "srt": "540", "typ": "C" },
    "ḿ": { "srt": "549", "typ": "C" },
    "ṁ": { "srt": "544","ssrt":"244", "typ": "M" },
    "ṃ": { "srt": "240", "typ": "M" },
    "n": {
  //      "·": { "srt": "245", "typ": "MT" },
      "̂": { "srt": "499", "typ": "C" },
      "̄": { "srt": "492", "typ": "C" },
      "̥": { "srt": "499", "typ": "C" },
      "srt": "490", "typ": "C" },
    "ṉ": {"srt":"493","typ":"C"},
    "ṅ": { "srt": "310", "typ": "C" },
    "ñ": {
      "̄": { "srt": "362", "typ": "C" },
      "srt": "360", "typ": "C" },
    "ṇ": {
      "̄": { "srt": "432", "typ": "C" },
      "srt": "430", "typ": "C" },
    "o": {
      "͚": { "srt": "228", "typ": "V" },
      "srt": "220", "typ": "V" },
    "ó": { "srt": "222", "typ": "V" },
    "ò": { "srt": "223", "typ": "V" },
    "ô": { "srt": "224", "typ": "V" },
    "ǒ": { "srt": "225", "typ": "V" },
    "ŏ": { "srt": "220", "typ": "V" },
    "ō": {
      "̆": { "srt": "221", "typ": "V" },
      "srt": "221", "typ": "V" },
    "õ": { "srt": "224", "typ": "V" },
    "ṓ": { "srt": "222", "typ": "V" },
    "ṑ": { "srt": "223", "typ": "V" },
    "p": {
      "̄": { "srt": "502", "typ": "C" },
      "͟": {"h": { "srt": "511", "typ": "CH" }},
      "̱": { "srt": "501", "typ": "C" },
      "h": { "srt": "510", "typ": "CH" },
      "srt": "500", "typ": "C" },
    "ṕ": { "srt": "691", "typ": "C" },
    "ṛ": { "srt": "160", "typ": "V" },
    "ṝ": { "ssrt": "170", "typ": "V" },
    "ṟ": { "srt": "561", "typ": "C" },
    "r": {
      "̥": {
        "́": { "srt": "162", "typ": "V" },
      "̀": { "srt": "163", "typ": "V" },
      "̃": { "srt": "164", "typ": "V" },
      "͚": { "srt": "168", "typ": "V" },
      "̄": {
        "̆": { "srt": "161","ssrt":"171", "typ": "V" },
        "́": { "srt": "162","ssrt":"172", "typ": "V" },
        "̃": { "srt": "164","ssrt":"174", "typ": "V" },
        "srt": "161","ssrt":"171", "typ": "V" },
      "̂": { "srt": "164", "typ": "V" },
      "͡": {
        "i": { "srt": "167", "typ": "V" }},
      "srt": "160", "typ": "V" },
      "̱": { "srt": "561", "typ": "C" },
      "srt": "560", "typ": "C" },
    "s": {
      "̂": { "srt": "629", "typ": "C" },
      "̄": { "srt": "622", "typ": "C" },
      "̱": { "srt": "621", "typ": "C" },
      "srt": "620", "typ": "C" },
    "ś": {
      "̱": { "srt": "601", "typ": "C" },
      "̄": { "srt": "602", "typ": "C" },
      "͟": { "srt": "608", "typ": "C" },
      "̂": { "srt": "609", "typ": "C" },
      "srt": "600", "typ": "C" },
    "ṣ": {
      "̂": { "srt": "619", "typ": "C" },
      "̄": { "srt": "612", "typ": "C" },
      "̱": {
        "̄": { "srt": "613", "typ": "C" },
        "srt": "611", "typ": "C" },
      "srt": "610", "typ": "C" },
    "t": {
  //      "·": { "srt": "243", "typ": "MT" },
      "́": { "srt": "449", "typ": "C" },
      "h": {
        "́": { "srt": "460", "typ": "C" },
        "srt": "450", "typ": "CH" },
      "srt": "440", "typ": "C" },
    "ṯ": { "srt": "441", "typ": "C" },
    "ṭ": {
      "́": {
        "h": { "srt": "400", "typ": "CH" },
      "srt": "380", "typ": "C" },
      "h": { "srt": "390", "typ": "CH" },
      "srt": "370", "typ": "C" },
    "u": {
      "͚": { "srt": "148", "typ": "V" },
      "srt": "140", "typ": "V" },
    "ü": { "srt": "140", "typ": "V" },
    "ú": { "srt": "142", "typ": "V" },
    "ù": { "srt": "143", "typ": "V" },
    "û": { "srt": "144", "typ": "V" },
    "ǔ": { "srt": "145", "typ": "V" },
    "ū": {
      "̆": { "srt": "141","ssrt":"151", "typ": "V" },
      "́": { "srt": "142","ssrt":"152", "typ": "V" },
      "̀": { "srt": "143","ssrt":"153", "typ": "V" },
      "̃": { "srt": "144","ssrt":"154", "typ": "V" },
      "srt": "141","ssrt":"150", "typ": "V" },
    "ũ": { "srt": "144", "typ": "V" },
    "v": {
      "́": { "srt": "589", "typ": "C" },
      "͟": { "h": { "srt": "588", "typ": "C" } },
      "̱": { "srt": "581", "typ": "C" },
      "h": { "srt": "590", "typ": "CH" },
      "srt": "580", "typ": "C" },
    "y": {
      "̱": { "srt": "551", "typ": "C" },
      "srt": "550", "typ": "C" },
    "ý": { "srt": "692", "typ": "C" },
    "z": { "srt": "640", "typ": "C" },
    "ẕ": { "srt": "641", "typ": "C" },
    "ẓ": { "srt": "630", "typ": "C" }
  }

}

