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
* editors editionVE object
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>y
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Utility Classes
*/
var EDITORS = EDITORS || {};

/**
* Constructor for Edition Viewer/Editor Object
*
* @type Object
*
* @param editionVECfg is a JSON object with the following possible properties
*  "edition" an entity data element which defines the edition and all it's structures.
*
* @returns {EditionVE}

*/
/**
* Constructor for Word List/ Glossary Viewer/Editor Object
*
* @type Object
*
* @param wordlistCfg is a JSON object with the following possible properties
*  "id" the id of the open pane used to create context ids for elements
*  "catID" the catalog ID of the context
*  "ednID" the edition ID of the context
*  "layoutMgr" reference to the layout manager for displaying multiple editors
*  "dataMgr" reference to the data manager/model manager
*  "editDiv" reference to the div element for display of the WordlistVE
*
* @returns {EntityPropVE}
*/

EDITORS.WordlistVE =  function(wordlistCfg) {
  var srchVE = this;
  //read configuration and set defaults
  this.config = wordlistCfg;
  this.type = "WordlistVE";
  this.editDiv = wordlistCfg['editDiv'] ? wordlistCfg['editDiv']:null;
  this.dataMgr = wordlistCfg['dataMgr'] ? wordlistCfg['dataMgr']:null;
  this.catID = (wordlistCfg['catID'] && this.dataMgr &&
                this.dataMgr.entities.cat[wordlistCfg['catID']])?wordlistCfg['catID']:null;
  this.ednID = (wordlistCfg['ednID'] && this.dataMgr &&
                  this.dataMgr.entities.edn[wordlistCfg['ednID']]) ? wordlistCfg['ednID']:null;
  this.layoutMgr = wordlistCfg['layoutMgr'] ? wordlistCfg['layoutMgr']:null;
  this.id = wordlistCfg['id'] ? wordlistCfg['id']: (this.editDiv.id?this.editDiv.id:null);
  this.init();
  return this;
};

/**
* Prototype for Edition Viewer/Editor Object
*/
EDITORS.WordlistVE.prototype = {

/**
* Initialiser for Wordlist/Glossary Viewer/Editor Object
*/

  init: function() {
    var wordlistVE = this, propMgrCfg;
    this.lemLookup = {};
    this.splitterDiv = $('<div id="'+this.id+'splitter"/>');
    this.contentDiv = $('<div id="'+this.id+'textContent" class="wordlistContentDiv" spellcheck="false" ondragstart="return false;"/>');
    this.propertyMgrDiv = $('<div id="'+this.id+'propManager" class="propertyManager"/>');
    this.splitterDiv.append(this.contentDiv);
    this.splitterDiv.append(this.propertyMgrDiv);
    $(this.editDiv).append(this.splitterDiv);
    this.splitterDiv.jqxSplitter({ width: '100%',
                                      height: '100%',
                                      orientation: 'vertical',
                                      splitBarSize: 1,
                                      showSplitBar:false,
                                      panels: [{ size: '60%', min: '250', collapsible: false},
                                               { size: '40%', min: '150', collapsible: true,collapsed : true}] });
    propMgrCfg ={edID: this.id,
                 propertyMgrDiv: this.propertyMgrDiv,
                 editor: wordlistVE,
                 id: this.id,
                 propVEType: "lemmaVE",
                 dataMgr: this.dataMgr,
                 splitterDiv: this.splitterDiv };
    if (this.catID) {
      propMgrCfg['catID'] = this.catID;
    } else {
      propMgrCfg['ednID'] = this.ednID;
    }
    this.propMgr = new MANAGERS.PropertyManager(propMgrCfg);
    this.displayProperties = this.propMgr.displayProperties;
    this.splitterDiv.unbind('focusin').bind('focusin',this.layoutMgr.focusHandler);
    this.createStaticToolbar();
    if (this.catID) {
      this.displayGlossary();
    } else if (this.ednID){
      this.displayWordList();
    }
  },


/**
* create toolbars for this wordlist/glossary editor
*/

  createStaticToolbar: function () {
    var wordlistVE = this;
    var btnFilterLevelName = this.id+'filterlevel',
        ddbtnShowEdnName = this.id+'showednbutton',
        treeShowEdnName = this.id+'showedntree',
        btnShowPropsName = this.id+'showprops',
        btnDownloadHTMLName = this.id+'downloadhtml',
        btnDownloadRTFName = this.id+'downloadrtf';
    this.viewToolbar = $('<div class="viewtoolbar"/>');
    this.editToolbar = $('<div class="edittoolbar"/>');

    this.filterLevelBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton" id="'+btnFilterLevelName +
                              '" title="Set filtering level" >All</button>'+
                            '<div class="toolbuttonlabel">Show Words</div>'+
                           '</div>');
    $('#'+btnFilterLevelName,this.filterLevelBtnDiv).unbind('click')
                               .bind('click',function(e) {
                                  switch (wordlistVE.levelSelected) {
                                    case 'all':
                                      wordlistVE.levelSelected = 'lemma';
                                      $(this).html("Lemma");
                                      $('.sfiltered',wordlistVE.contentDiv).removeClass('sfiltered');
                                      $('.word',wordlistVE.contentDiv).addClass('sfiltered');
                                      break;
                                    case 'lemma':
                                      wordlistVE.levelSelected = 'unlinked';
                                      $(this).html("Words");
                                      $('.sfiltered',wordlistVE.contentDiv).removeClass('sfiltered');
                                      $('.lemma',wordlistVE.contentDiv).parent().addClass('sfiltered');
                                      break;
                                    case 'unlinked':
                                    default:
                                      wordlistVE.levelSelected = 'all';
                                      $(this).html("All");
                                      $('.sfiltered',wordlistVE.contentDiv).removeClass('sfiltered');
                                      break;
                                  }
                                });
    this.propertyBtnDiv = $('<div class="toolbuttondiv">' +
                            '<button class="toolbutton iconbutton" id="'+btnShowPropsName+
                              '" title="Show/Hide property panel">&#x25E8;</button>'+
                            '<div class="toolbuttonlabel">Properties</div>'+
                           '</div>');
    $('#'+btnShowPropsName,this.propertyBtnDiv).unbind('click')
                                        .bind('click',function(e) {
                                           var paneID, editor;
                                           if (wordlistVE.propMgr &&
                                              typeof wordlistVE.propMgr.displayProperties == 'function'){
                                             if ($(this).hasClass("showUI")) {
                                               wordlistVE.propMgr.displayProperties(false);
                                               $(this).removeClass("showUI");
                                             } else {
                                               wordlistVE.propMgr.displayProperties(true);
                                               $(this).addClass("showUI");
                                             }
                                           }
                                         });
    this.viewToolbar.append(this.filterLevelBtnDiv);
    this.viewToolbar.append(this.propertyBtnDiv);

    if (this.catID) {//create selection dropdown with checkboxes of all editions in the catalog
      var ednIDs = this.dataMgr.getEntity('cat',this.catID)['ednIDs'];
      if (ednIDs && ednIDs.length > 0) {
        //calculate edition array
        this.showEdnBtnDiv = $('<div class="toolbuttondiv">' +
                               '<div id="'+ ddbtnShowEdnName+'"><div id="'+ treeShowEdnName+'"></div></div>'+
                               '<div class="toolbuttonlabel">Editions</div>'+
                               '</div>');
        this.showEdnTree = $('#'+treeShowEdnName,this.showEdnBtnDiv);
        this.showEdnDdBtn = $('#'+ddbtnShowEdnName,this.showEdnBtnDiv);
        this.showEdnTree.jqxTree({
               source: this.getEdnList(ednIDs),
               hasThreeStates: true, checkboxes: true,
               width: '250px',
               theme:'energyblue'
        });
        this.showEdnTree.on('checkChange', function (event) {
            var args = event.args, element = args.element, checked = args.checked,
                dropDownContent = '', i, item = wordlistVE.showEdnTree.jqxTree('getItem',element),
                ednID = item.id,
                checkedItems =  wordlistVE.showEdnTree.jqxTree('getCheckedItems');

            if (ednID != 'all') {
              if (checked) {
                $(".edn"+ednID,wordlistVE.contentDiv).each(function(index,elem) {
                  if ($(elem).hasClass('efiltered')) {
                    $(elem).removeClass('efiltered');
                  }
                });
              } else {
                $(".edn"+ednID,wordlistVE.contentDiv).each(function(index,elem) {
                  if (!$(elem).hasClass('efiltered')) {
                    $(elem).addClass('efiltered');
                  }
                });
              }
            }

            if (checkedItems.length) {
              dropDownContent = '<div class="listDropdownButton">' + checkedItems[0].value + (checkedItems.length>1?"...":"")+ '</div>';
              wordlistVE.showEdnDdBtn.jqxDropDownButton('setContent', dropDownContent);
              //aggregate the checked values to create css tagging rules for injection
            } else {
              //nothing selected so show tags is "off"
              wordlistVE.showEdnDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">Off</div>');
              //if stylesheet exist clear it to remove tagging
            }
        });
        this.showEdnDdBtn.jqxDropDownButton({width:95, height:30 });
        this.showEdnDdBtn.jqxDropDownButton('setContent', '<div class="listDropdownButton">all ...</div>');
        this.viewToolbar.append(this.showEdnBtnDiv);
      }
      this.downloadHTMLurl = basepath+"/services/exportHTMLGlossary.php?db="+dbName+"&catID="+wordlistVE.catID+"&download=1";
      this.downloadHTMLBtnDiv = $('<div class="toolbuttondiv">' +
                              '<a href="'+this.downloadHTMLurl+'" >'+
                              '<button class="toolbutton iconbutton" id="'+btnDownloadHTMLName+
                                '" title="Download Glossary to HTML">Download</button></a>'+
                              '<div class="toolbuttonlabel">HTML</div>'+
                             '</div>');
      this.viewToolbar.append(this.downloadHTMLBtnDiv);
      this.downloadHTMLBtn = $('#'+btnDownloadHTMLName,this.downloadHTMLBtnDiv);
      this.downloadHTMLBtn.attr('disabled','disabled');

      this.downloadRTFurl = basepath+"/services/exportRTFGlossary.php?db="+dbName+"&catID="+wordlistVE.catID+"&download=1";
      this.downloadRTFBtnDiv = $('<div class="toolbuttondiv">' +
                              '<a href="'+this.downloadRTFurl+'" >'+
                              '<button class="toolbutton iconbutton" id="'+btnDownloadRTFName+
                                '" title="Download Glossary to RTF">Download</button></a>'+
                              '<div class="toolbuttonlabel">RTF</div>'+
                             '</div>');
      this.viewToolbar.append(this.downloadRTFBtnDiv);
    }
    this.searchInput = $('<input type="text" id="searchInput" />'),
    this.searchInput.jqxInput({placeHolder: "Enter Word Search Here"});
    this.searchInput.unbind('change').bind('change', function(e) {
                                                     wordlistVE.searchChangeHandler(e);
                                                    });
    this.searchInput.unbind('keyup').bind('keyup', function(e) {
                                                       wordlistVE.searchChangeHandler(e);
                                                    });
    this.search = "";
    this.viewToolbar.append(this.searchInput);

    this.layoutMgr.registerViewToolbar(this.id,this.viewToolbar);
    this.layoutMgr.registerEditToolbar(this.id,this.editToolbar);
    this.levelSelected = 'all';//default
  },


/**
* handle 'change' event for search input
*
* @param object e System event object
*/

  searchChangeHandler: function (e) {
    var wordlistVE = this, search = this.searchInput.val(),regEx;
    if (this.search != search) {
      this.search = search;
      if (!search) { //remove all nmfiltered = no match filtered
        $('.wsfiltered',wordlistVE.contentDiv).removeClass('wsfiltered');
      } else {
        regEx = new RegExp(search);
        $('.word,.linkedword',wordlistVE.contentDiv).each(function(index,elem) {
          var value = $(elem).attr('srch');
          if (value.match(regEx)) {
            if ($(elem).hasClass('wsfiltered')) {
              $(elem).removeClass('wsfiltered');
            }
          } else {
            if (!$(elem).hasClass('wsfiltered')) {
              $(elem).addClass('wsfiltered');
            }
          }
        });
        $('.lemma',wordlistVE.contentDiv).each(function(index,elem) {
          var value = $(elem).attr('srch'),
              valueEtym = $(elem).parent().find('.lemmagloss').text(),
              valueGloss = $(elem).parent().find('.lemmatrans').text(),
              linkMatchCnt = $(elem).siblings('.linkedword:not(.wsfiltered)').length;
          if (value.match(regEx) || linkMatchCnt > 0 ||
              valueEtym && valueEtym.match(regEx)|| valueGloss && valueGloss.match(regEx)) {
            if ($(elem).parent().hasClass('wsfiltered')) {
              $(elem).parent().removeClass('wsfiltered');
            }
          } else {
            if (!$(elem).parent().hasClass('wsfiltered')) {
              $(elem).parent().addClass('wsfiltered');
            }
          }
        });
      }
    }
  },


/**
* calculate list of editions
*
* @param int[] ednIDs Edition entity ids used to create list item array
*
* @returns object array Item structures for jqWidget listbox
*/

  getEdnList: function (ednIDs) {
    var items = [],tree, i, item, tagID, value, edition, text;
   //foreach seg create item
    for (i in ednIDs) {
      ednID = ednIDs[i];
      edition = this.dataMgr.getEntity('edn',ednID);
      text = this.dataMgr.getEntity('txt',edition.txtID);
      value = (text.ref?text.ref:text.CKN);
      item = {id:ednID,
              label:edition.value + "("+value+")",
              value:value
             };
      items.push(item);
    }
    tree = [{id:'all',
             label:"All Editions",
             items:items,
             expanded:true,
             checked:true,
             value:"all"
            }];
    return tree;
  },


/**
* refresh wordlistVE display
* @deprecated
* @param int catID Catalog entity id
* @param int lemID Lemma entity id
*/

  refreshDisplay: function (catID, lemID) {
    if (!this.catID && catID) {
      this.catID = catID;
      if (this.layoutMgr) {
        this.layoutMgr.editors[this.id].config.entGID = "wl-cat:" + catID;
        this.layoutMgr.pushState();
        if (this.layoutMgr.editors['searchVE']) {
          this.layoutMgr.editors['searchVE'].updateCursorInfoBar();
        }
      }
    }
    if (this.catID) {
      this.displayGlossary();
      //find lemma entry and trigger dblclick
    } else if (this.ednID){
      this.displayWordList();
    }
  },


/**
* trigger dblclick on previous word based for filtered display
*
* @returns true|false
*/

  prevWord: function () {
    var curEntry = $('.selected',this.contentDiv).parent(),prevNode;
    if (curEntry.length && curEntry.prev().hasClass('wordlistentry')) {
      switch (this.levelSelected) {
        case 'lemma':
          prevNode = curEntry.prevAll('div:has(span.lemma)').first();
          break;
        case 'unlinked':
          prevNode = curEntry.prevAll('div:has(span.word)').first();
          break;
        case 'all':
        default:
          prevNode = curEntry.prev('.wordlistentry');
          break;
      }
      if (prevNode.length == 1) {
        prevNode.children().first().trigger('dblclick');
        prevNode[0].scrollIntoView();
        return true;
      }
    }
    UTILITY.beep();
    return false;
  },


/**
* trigger dblclick on next word based for filtered display
*
* @returns true|false
*/

  nextWord: function () {
    var curEntry = $('.selected',this.contentDiv).parent(),nextNode;
    if (curEntry.length && curEntry.next().hasClass('wordlistentry')) {
      switch (this.levelSelected) {
        case 'lemma':
          nextNode = curEntry.nextAll('div:has(span.lemma)').first();
          break;
        case 'unlinked':
          nextNode = curEntry.nextAll('div:has(span.word)').first();
          break;
        case 'all':
        default:
          nextNode = curEntry.next('.wordlistentry');
          break;
      }
      if (nextNode.length == 1) {
        nextNode.children().first().trigger('dblclick');
        nextNode[0].scrollIntoView();
        return true;
      }
    }
    UTILITY.beep();
    return false;
  },

  lookupLemma: function (val) {
    return this.lemLookup[val];
  },


/**
* display Glossary
*/

  displayGlossary: function () {
    var wordlistVE = this,i,j,k,cnt,sequence, html = "", txtSeqGIDs, physSeqGIDs, multiEd = false,
        catalog = this.dataMgr.entities.cat[this.catID], edition, ednID, fSclID, lSclID, displayValue = '',
        ednIDs, edition, ednLabel, sclIDs, seqIDs, wordGIDs, seqGIDs, label, defLabel, lemma, val, lemmaHTML,
        lemIDs, seqTags = {}, seqGIDs, wordGIDs,sequence, prefix, id, tag, wtag, word, inflection,
        isNoun, isPronoun, isAdjective, isNumeral, isVerb, isInflectable, cf, tempLabel, pos,
        entities = [], linkedWords = {}, linkedWordTags = {}, sclTagToLabel = {}, ednLblByEntTag = {}, seqTag2EdnTag = {};
    if (!(catalog &&
        (catalog.lemIDs && catalog.lemIDs.length ||
         catalog.ednIDs && catalog.ednIDs.length))) {//doesn't meet requirements to display so error it and return
      alert("glossary is missing information needed for display, aborting");
    }
    if (catalog.lemIDs && catalog.lemIDs.length) {
      lemIDs = catalog.lemIDs;
    }
    if (catalog.ednIDs && catalog.ednIDs.length) {
      ednIDs = catalog.ednIDs;
    }
    if (lemIDs) {
      //create sorted wordlist for all catalog lemata and edition tokens not linked as attested to a lemma
      //note that attested forms are listed in edition/line/line position order
      //find lemmata and add linked words to listsequences for edition
      for (i=0; i<lemIDs.length; i++) {
        if (this.dataMgr.entities.lem[lemIDs[i]]) {
          lemma = this.dataMgr.getEntity('lem',lemIDs[i]);
          lemma.tag = 'lem'+lemIDs[i];
          if (lemma.value) {
            val = lemma.value.replace(/ʔ/g,'');
            if (this.lemLookup[val]) {
              if (this.lemLookup[val].indexOf(lemma.id) == -1) {
                this.lemLookup[val].push(lemma.id);
              }
            } else {
              this.lemLookup[val] = [lemma.id];
            }
          } else {
            DEBUG.log('warn',"Lemma "+tag+" has no value, adding undefined ");
            lemma.value = "missing value";
          }
          wordGIDs = lemma.entityIDs;
          if (wordGIDs && wordGIDs.length) {
            for (j=0; j<wordGIDs.length; j++) {
              tag = wordGIDs[j].replace(":","");
              if (tag.substring(0,3)== "inf") {
                inflection = this.dataMgr.getEntity('inf',tag.substring(3));
                inflection.tag = tag;
                for (k=0; k<inflection.entityIDs.length; k++) {
                  tag = inflection.entityIDs[k].replace(":","");
                  if (!linkedWordTags[tag]) {
                    linkedWordTags[tag] = 1;
                  } else {
                    DEBUG.log('warn',"Duplicate word "+tag+" found in Inflection "+inflection.tag);
                  }
                }
              } else {
                if (!linkedWordTags[tag]) {
                  linkedWordTags[tag] = 1;
                } else {
                  DEBUG.log('warn',"Duplicate word "+tag+" found in Lemma "+lemma.tag);
                }
              }
            }
          }
          entities.push(lemma);
        } else {
          DEBUG.log('warn',"Lemma id"+lemIDs[i]+" not loaded");
        }
      }
    }
    if (ednIDs && ednIDs.length) {
      if (ednIDs.length > 1) {
        multiEd = true;
      }
      //for each edition find token sequences and find physical sequences and create sclID to label lookup
      for (i=0; i<ednIDs.length; i++) {
        ednID = ednIDs[i];
        ednTag = 'edn'+ednID;
        ednLabel = "";
        edition = this.dataMgr.getEntity('edn',ednIDs[i]);
        txtSeqGIDs = [];
        physSeqGIDs = [];
        if (multiEd && edition && edition.txtID) {
          ednText = this.dataMgr.getEntity('txt',edition.txtID);
          if (ednText && ednText.ref) {
            ednLabel = ednText.ref;
          }
        }
        if ( edition && edition.seqIDs && edition.seqIDs.length) {
          seqIDs = edition.seqIDs;
          for (j=0; j<seqIDs.length; j++) {
            sequence =  this.dataMgr.getEntity('seq',seqIDs[j]);;
            if (sequence && sequence.typeID == this.dataMgr.termInfo.idByTerm_ParentLabel['text-sequencetype']) {//term dependency
              txtSeqGIDs = txtSeqGIDs.concat(sequence.entityIDs);
            }
            if (sequence && sequence.typeID == this.dataMgr.termInfo.idByTerm_ParentLabel['textphysical-sequencetype']) {//term dependency
              physSeqGIDs = physSeqGIDs.concat(sequence.entityIDs);
            }
          }
        }
        if (txtSeqGIDs && txtSeqGIDs.length) {// capture each sequence once
          for (j=0; j<txtSeqGIDs.length; j++) {
            tag = txtSeqGIDs[j].replace(":","");
            if (ednLblByEntTag[tag]) {// use label from first edition found with this sequence tag, should never happened if single edition per text
              continue;
            }
            ednLblByEntTag[tag] = ednLabel;//todo: this overwrites the edition, ?? do we need to associate a line sequence with a primary edition for the reuse case??
            seqTag2EdnTag[tag] = ednTag;
          }
        }
        if (physSeqGIDs && physSeqGIDs.length) {// capture each sequence once
          for (j=0; j<physSeqGIDs.length; j++) {
            sequence =  this.dataMgr.getEntityFromGID(physSeqGIDs[j]);
            label = sequence.sup?sequence.sup:sequence.label;
            sclGIDs = sequence.entityIDs;
            if (label && sclGIDs.length) {//create lookup for location of word span B11-B12
              for (k=0; k<sclGIDs.length; k++) {
                tag = sclGIDs[k].replace(":","");
                sclTagToLabel[tag] = label;
              }
            }
          }
        }
      }
    }
    if (ednLblByEntTag && Object.keys(ednLblByEntTag).length > 0) {
      //for each token sequence
      for (tag in ednLblByEntTag) {
        ednLabel = ednLblByEntTag[tag];
        ednTag = seqTag2EdnTag[tag];
        if (ednLabel) {
          ednLabel += ": ";
        }
        sequence = this.dataMgr.getEntity(tag.substr(0,3),tag.substr(3));
        defLabel = ednLabel + (sequence.sup?sequence.sup:sequence.label);
        wordGIDs = sequence.entityIDs;
        //for each word
        for (j=0; j<wordGIDs.length; j++) {
          fSclID = lSclID = null;
          wtag = wordGIDs[j].replace(":","");
          prefix = wtag.substr(0,3);
          id = wtag.substr(3);
          word = this.dataMgr.getEntity(prefix,id);
          if (word.sort && word.sort >= 0.7) {
            continue;
          }
          word.tag = wtag;
          if (ednTag) {
            word.edn = ednTag;
          }
          // find first and last SclID for word to calc attested form location
          if (prefix == 'cmp' && word.tokenIDs.length) {
            fToken = this.dataMgr.getEntity('tok',word.tokenIDs[0]);
            if ( fToken && fToken.syllableClusterIDs && fToken.syllableClusterIDs.length && sclTagToLabel['scl'+fToken.syllableClusterIDs[0]]) {
              label = sclTagToLabel['scl'+fToken.syllableClusterIDs[0]];
              lToken = this.dataMgr.getEntity('tok',word.tokenIDs[word.tokenIDs.length - 1]);
              if ( lToken && lToken.syllableClusterIDs && lToken.syllableClusterIDs.length &&
                  sclTagToLabel['scl'+lToken.syllableClusterIDs[lToken.syllableClusterIDs.length - 1]] &&
                  sclTagToLabel['scl'+lToken.syllableClusterIDs[lToken.syllableClusterIDs.length - 1]] != label) {
                label += "-" + sclTagToLabel['scl'+lToken.syllableClusterIDs[lToken.syllableClusterIDs.length - 1]];
              }
              word.locLabel = ednLabel + label;
            } else {
              word.locLabel = defLabel;
            }
          } else if (prefix == 'tok') {
            if ( word.syllableClusterIDs && word.syllableClusterIDs.length && sclTagToLabel['scl'+word.syllableClusterIDs[0]]) {
              label = sclTagToLabel['scl'+word.syllableClusterIDs[0]];
              if (sclTagToLabel['scl'+word.syllableClusterIDs[word.syllableClusterIDs.length - 1]] &&
                  sclTagToLabel['scl'+word.syllableClusterIDs[word.syllableClusterIDs.length - 1]] != label) {
                label += "-" + sclTagToLabel['scl'+word.syllableClusterIDs[word.syllableClusterIDs.length - 1]];
              }
              word.locLabel = ednLabel + label;
            } else {
              word.locLabel = defLabel;
            }
          }
          if (linkedWordTags[wtag]) {//since lemmas have been added to entities we don't want attested forms in our list
            linkedWords[wtag] = word;
          } else {//unlinked form
            //add word to entities and tag to word
            entities.push(word);
          }
        }
      }
    }
    if (entities && entities.length) {
      //sort entities array using UTILITY.compareEntities
      entities.sort(UTILITY.compareEntities);
      html = '<h3 class="wordListTitle cat'+this.catID+'">'+catalog.value+'</h3>';
      for (i=0; i<entities.length; i++) {
        word = entities[i];
        if (word.tag && word.tag.match(/^lem/)) { //lemma
          lemmaHTML = this.calcLemmaHtml(word.id);
          if (lemmaHTML) {
            html += '<div class="wordlistentry">'+lemmaHTML+'</div>';
          } else {
            DEBUG.log('warn',"Lemma "+word.tag+" failed to calc HTML ");
          }
        } else { //unlinked word list entry
          html += this.calcWordEntryHtml(word.tag);
        }
      }//end for entities in lemmas + wordlist
    }
    this.contentDiv.html(html);
    this.addEventHandlers(this.contentDiv);
  },


/**
* calculate word entry html fragment
*
* @param string wordTag word type Entity tag
*/

  calcWordEntryHtml: function (wordTag) {
    var word = this.dataMgr.getEntityFromGID(wordTag), wordHTML = "";
    if (word && word.value) {
      wordHTML = '<div class="wordlistentry"><span class="word '+word.tag+(word.edn?' '+word.edn:"") +'" srch="'+word.value.replace(/ʔ/g,'')+'">' +
                      (!word.transcr?'MISSING':word.transcr.replace(/ʔ/g,'').replace(/\(\*/g,'(').replace(/⟨\*/g,'⟨')) +
                      ' ' + (word.edn?'<span class="edndraghandle">'+word.locLabel+'</span>':word.locLabel) + '</span></div>';
    }
    return wordHTML;
  },


/**
** calculate lemma entry html fragment
*
* @param int lemID Lemma entity id
*
* @returns string Html fragment
*/

  calcLemmaHtml: function (lemID) {
    var wordlistVE = this,i,j,k,cnt,html = "",tag, wtag, word, wordGIDs, inflection, infGIDs, val,
        isNoun, isPronoun, isAdjective, isNumeral, isVerb, isInflectable, cf, tempLabel, pos,
        displayValue = '';
    if (this.dataMgr.entities && this.dataMgr.entities.lem && this.dataMgr.entities.lem[lemID]) {
      lemma = this.dataMgr.entities.lem[lemID];
      if (!lemma.tag) {
        lemma.tag = "lem" + lemID;
      }
      cf = lemma.certainty?lemma.certainty:[3,3,3,3,3];
      pos = this.dataMgr.getTermFromID(lemma.pos);
      isNoun = pos == 'noun';
      isPronoun = pos == 'pron.';
      isAdjective = pos == 'adj.';
      isNumeral = pos == 'num.';
      isVerb = pos == 'v.';
      isInflectable = (isNoun || isPronoun || isAdjective || isNumeral || isVerb);
      //calculate the lemmas POS label
      if (isNoun && lemma.gender) {
        tempLabel = this.dataMgr.getTermFromID(lemma.gender) + (cf[2]==2?'(?)':'');
      } else if (lemma.spos && this.dataMgr.getTermFromID(lemma.spos)) {
        tempLabel = this.dataMgr.getTermFromID(lemma.spos) + (cf[1]==2?'(?)':'');
      } else {
        tempLabel = pos + (cf[0]==2?'(?)':'');
      }
      //output lemma header with gloss and POS
      if (lemma && lemma.value) {
        displayValue = lemma.value.replace(/ʔ/g,'');//.replace(/\(\*/g,'(').replace(/⟨\*/g,'⟨');
      } else {
        displayValue = '';
      }
      html += '<span class="lemma '+lemma.tag+" "+(lemma.readonly?"readonly":"")+'"'+
              ' srch="'+displayValue.replace(/ʔ/g,'')+'">' + displayValue + ' </span>' +
              (lemma.gloss?'<span class="lemmagloss">'+lemma.gloss + ' </span>':"")+
              '<span class="POS">'+ tempLabel + ' </span>' +
              (lemma.trans? '<span class="lemmatrans">'+lemma.trans+'</span>':"");
      if (lemma.entityIDs && lemma.entityIDs.length ) {
        html += '&nbsp;&nbsp;&nbsp;';
        // output all attestations with location info (edition/line no. in sequence order if same line
        if (isInflectable) { //inflection
          infGIDs = this.orderInflections(lemma);
          var curTense=null, curVoice=null, curMood=null,
              curGen=null, curNum=null, curForm=null,
              curCase=null, curPerson=null, cur2ndConj=null;
          //output 'ordered according to inflection' list of unique spelling attested forms
          for (k=0; k<infGIDs.length; k++) {
            //ignore any attested forms not assigned to an inflection (non inf entIDS)
            if (!infGIDs[k].match(/inf/)) {
              continue;
            }
            inflection = this.dataMgr.getEntityFromGID(infGIDs[k]);
          //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
            icf = inflection.certainty ? inflection.certainty : [3,3,3,3,3,3,3,3];
            html += '<span class="morphology '+infGIDs[k].replace(":","")+'">';
            if (inflection.tense && (inflection.tense + (icf[0]==2?'(?)':'')) != curTense) {
              html += ((k && inflection.tense != curTense)?' ◾ ':' ') +
                      this.dataMgr.getTermFromID(inflection.tense) + (icf[0]==2?'(?)':'');
              curTense = inflection.tense + (icf[0]==2?'(?)':'');
              curVoice=null;
              curMood=null;
              curNum = null;
              curPerson = null;
              cur2ndConj=null;
              curForm = null;
              curGen = null;
              curCase = null;
            }
            if (inflection.voice && (inflection.voice + (icf[1]==2?'(?)':'')) != curVoice) {
              html += this.dataMgr.getTermFromID(inflection.voice) + (icf[1]==2?'(?)':'');
              curVoice = ' ' + inflection.voice + (icf[1]==2?'(?)':'');
              curMood=null;
              curNum = null;
              curPerson = null;
              cur2ndConj=null;
              curForm = null;
            }
            if (inflection.mood && (inflection.mood + (icf[2]==2?'(?)':'')) != curMood) {
              html += ((k && inflection.mood != curMood)?' ◾ ':' ') +
                      this.dataMgr.getTermFromID(inflection.mood) + (icf[2]==2?'(?)':'');
              curMood = inflection.mood + (icf[2]==2?'(?)':'');
              curNum = null;
              curPerson = null;
              cur2ndConj=null;
              curForm = null;
            }
            if (inflection.gender && (inflection.gender + (icf[3]==2?'(?)':'')) != curGen) {
              html += ((k && inflection.gender != curGen)?' ◾ ':' ') +
                      this.dataMgr.getTermFromID(inflection.gender) + (icf[3]==2?'(?)':'');
              curGen = inflection.gender + (icf[3]==2?'(?)':'');
              curNum = null;
              curCase = null;
              cur2ndConj=null;
              curForm = null;
              curTense = null;
              curVoice=null;
              curMood=null;
              curPerson = null;
            }
            if (inflection.num && (inflection.num + (icf[4]==2?'(?)':'')) != curNum) {
              html += ' ' + this.dataMgr.getTermFromID(inflection.num) + (icf[4]==2?'(?)':'');
              curNum = inflection.num + (icf[4]==2?'(?)':'');
              curCase = null;
              curPerson = null;
              cur2ndConj=null;
              curForm = null;
            }
            if (inflection['case'] && (inflection['case']+ (icf[5]==2?'(?)':'')) != curCase) {
              html += ' ' + this.dataMgr.getTermFromID(inflection['case']) + (icf[5]==2?'(?)':'');
              curCase = inflection['case'] + (icf[5]==2?'(?)':'');
              cur2ndConj=null;
              curForm = null;
            }
            if (inflection.person && (inflection.person+ (icf[6]==2?'(?)':'')) != curPerson) {
              html += ' ' + this.dataMgr.getTermFromID(inflection.person) + (icf[6]==2?'(?)':'');
              curPerson = inflection.person + (icf[6]==2?'(?)':'');
              cur2ndConj=null;
              curForm = null;
            }
            if (inflection.conj2nd && (inflection. conj2nd+ (icf[7]==2?'(?)':'')) != cur2ndConj) {
              html += ((k && isVerb && inflection.conj2nd != cur2ndConj)?' ◾ ':' ') +
                      this.dataMgr.getTermFromID(inflection.conj2nd) + (icf[7]==2?'(?)':'');
              cur2ndConj = inflection.conj2nd + (icf[7]==2?'(?)':'');
              curForm = null;
            }
            html += ' </span>';
            wordGIDs = inflection.entityIDs;
            if (wordGIDs.length) {
              html += '<span class="attestedforms">';
              for (j=0; j<wordGIDs.length; j++) {
                word = this.dataMgr.getEntityFromGID(wordGIDs[j]);
//                tag = wordGIDs[j].replace(":","");
//                word = linkedWords[tag];
                if (!word || !word.value) {
                  DEBUG.log('err',"word not found in linkWords for tag "+tag);
                  continue;S
                }
                if (word.value == curForm) {// show a particular attested form only once here.
                  continue;
                }
                curForm = word.value;
                html += (j>0?', ':' ') + word.value.replace(/ʔ/g,'');
              }
              html += '</span>';
            }
          }
          html += '&nbsp;&nbsp;&nbsp;';
          for (k=0; k<infGIDs.length; k++) {
            if (infGIDs[k].match(/inf/)) {
              inflection = this.dataMgr.getEntityFromGID(infGIDs[k]);
              wordGIDs = inflection.entityIDs;
              for (j=0; j<wordGIDs.length; j++) {
                word = this.dataMgr.getEntityFromGID(wordGIDs[j]);
                if (word) {
                  html += '<span class="linkedword '+word.tag+(word.edn?' '+word.edn:"") +'" srch="'+word.value+'">' +
                          (k>0?', ':' ') + (word.edn?'<span class="edndraghandle">'+word.locLabel+'</span>':word.locLabel) +
                          ' ' + word.transcr.replace(/ʔ/g,'')+ '</span>';
                }
              }
            } else {
              word = this.dataMgr.getEntityFromGID(wordGIDs[k]);
              if (word) {
                html += '<span class="linkedword '+word.tag+(word.edn?' '+word.edn:"") +'" srch="'+word.value+'">' +
                        (k>0?', ':' ') + (word.edn?'<span class="edndraghandle">'+word.locLabel+'</span>':word.locLabel) +
                        ' ' + word.transcr.replace(/ʔ/g,'')+ '</span>';
              }
            }
          }
        } else { //no inflections     ?? 2 part display unique spellings followed by attested form links ??
          wordGIDs = lemma.entityIDs;
          for (j=0; j<wordGIDs.length; j++) {
            word = this.dataMgr.getEntityFromGID(wordGIDs[j]);
            if (word) {
              html += '<span class="linkedword '+word.tag+(word.edn?' '+word.edn:"") +'" srch="'+word.value+'">' +
                      (j?', ':' ') + (word.edn?'<span class="edndraghandle">'+word.locLabel+'</span>':word.locLabel) +
                      ' ' + word.transcr.replace(/ʔ/g,'')+ '</span>';
            }
          }
        }
      }
    }
    return html;
  },


/**
* update lemma entry
*
* @param int lemID Lemma entity id
*/

  updateLemmaEntry: function (lemID) {
    var lemTag = 'lem'+lemID, $lemmaEntry,
    //calc lemma new HTML
    lemmaHTML = this.calcLemmaHtml(lemID);
    //find entry and replace html
    $lemmaEntry = $('div.wordlistentry:has(.'+lemTag+')', this.editDiv);
    if ($lemmaEntry) {
      $lemmaEntry.html(lemmaHTML);
      this.addEventHandlers($lemmaEntry);
    }
  },


/**
* insert lemma entry
*
* @param int lemID Lemma entity id
* @param string wordGID Entity global id for word psuedo lemma
*/

  insertLemmaEntry: function (lemID, wordGID) {
    var hintTag, hintWord, lemTag = 'lem'+lemID,
        lemma = this.dataMgr.getEntityFromGID(lemTag),
        $node, $srchNode, srchWord, srchDir, $insertNode, position, match,
        $lemmaEntry, lemmaHTML = this.calcLemmaHtml(lemID);
    if (wordGID) {
       hintTag = wordGID.replace(':','');
       hintWord = this.dataMgr.getEntityFromGID(hintTag);
    }
    //find hint entity
    if (hintWord && hintWord.sort && lemma.sort) {
      $srchNode = $('div.wordlistentry:has(.'+hintTag+')',this.contentDiv);
      if (hintWord.sort > lemma.sort || (hintWord.sort == lemma.sort && hintWord.sort2 == lemma.sort2)) {
        srchDir = 'prev';
      } else {
        srchDir = 'next';
      }
    } else if (lemma.sort < 0.35) {
      $srchNode = $('.wordlistentry:first',this.contentDiv);
      srchDir = 'next';
    } else {
      $srchNode = $('.wordlistentry:last',this.contentDiv);
      srchDir = 'prev';
    }
    if (srchDir == 'next') {
      while ($node = $srchNode.next()) {
        match = $node.find('.lemma,.word').attr('class').match(/(lem|cmp|tok)\d+/);
        srchWord = this.dataMgr.getEntityFromGID(match[0]);
        if (srchWord.sort > lemma.sort) {
          break;
        }
        $srchNode = $node;
      }
      srchDir = 'after';
      if ($node) {
        $srchNode = $node;
        srchDir = 'before';
      }
    } else {
      while ($node = $srchNode.prev()) {
        match = $node.find('.lemma,.word').attr('class').match(/(lem|cmp|tok)\d+/);
        srchWord = this.dataMgr.getEntityFromGID(match[0]);
        if (srchWord.sort < lemma.sort) {
          break;
        }
        $srchNode = $node;
      }
      srchDir = 'before';
      if ($node) {
        $srchNode = $node;
        srchDir = 'after';
      }
    }
    //insert entry after/before nearest neighbor
    if ($srchNode) {
      if (srchDir == 'after') {
        $lemmaEntry = $('<div class="wordlistentry">'+lemmaHTML+'</div>').insertAfter($srchNode);
        this.addEventHandlers($lemmaEntry);
      } else {
        $lemmaEntry = $('<div class="wordlistentry">'+lemmaHTML+'</div>').insertBefore($srchNode);
        this.addEventHandlers($lemmaEntry);
      }
    }
  },


/**
* remove word entry from display
*
* @param string wordGID Entity global id for word psuedo lemma
*/

  removeWordEntry: function (wordGID) {
    var wordTag = wordGID.replace(':','');
    // find entry and remove
    $('div.wordlistentry:has(.word.'+wordTag+')', this.editDiv).remove();
  },


/**
* insert word entry
*
* @param string wordGID Entity global id for word psuedo lemma
* @param string hintTag Entity tag referencing the insert location
*/

  insertWordEntry: function (wordGID,hintTag) {
    var wordTag = wordGID.replace(':',''),
        word = this.dataMgr.getEntityFromGID(wordTag),
        hintWord = this.dataMgr.getEntityFromGID(hintTag), $wordEntry,
        $node, $srchNode, srchWord, srchDir, $insertNode, position, match,
        wordHTML = this.calcWordEntryHtml(wordTag);
    if (!wordHTML) {//nothing to insert
      retrun;
    }
    //find hint entity
    if (hintWord && hintWord.sort && word.sort) {
      $srchNode = $('div.wordlistentry:has(.'+hintTag+')',this.contentDiv);
      if (hintWord.sort > word.sort) {
        srchDir = 'prev';
      } else {
        srchDir = 'next';
      }
    } else if (word.sort < 0.35) {
      $srchNode = $('.wordlistentry:first',this.contentDiv);
      srchDir = 'next';
    } else {
      $srchNode = $('.wordlistentry:last',this.contentDiv);
      srchDir = 'prev';
    }
    if (srchDir == 'next') {
      while ($node = $srchNode.next()) {
        match = $node.find('.lemma,.word').attr('class').match(/(lem|cmp|tok)\d+/);
        srchWord = this.dataMgr.getEntityFromGID(match[0]);
        if (srchWord.sort > word.sort) {
          break;
        }
        $srchNode = $node;
      }
      srchDir = 'after';
      if ($node) {
        $srchNode = $node;
        srchDir = 'before';
      }
    } else {
      while ($node = $srchNode.prev()) {
        match = $node.find('.lemma,.word').attr('class').match(/(lem|cmp|tok)\d+/);
        srchWord = this.dataMgr.getEntityFromGID(match[0]);
        if (srchWord.sort < word.sort) {
          break;
        }
        $srchNode = $node;
      }
      srchDir = 'before';
      if ($node) {
        $srchNode = $node;
        srchDir = 'after';
      }
    }
    //insert entry after/before nearest neighbor
    if ($srchNode) {
      if (srchDir == 'after') {
        $wordEntry = $(wordHTML).insertAfter($srchNode);
        this.addEventHandlers($wordEntry);
      } else {
        $wordEntry = $(wordHTML).insertBefore($srchNode);
        this.addEventHandlers($wordEntry);
      }
    }
  },


/**
* order inflection of a lemma
*
* @param object lemma Lemma object
*
* @returns string[] infGIDs Array of entity global ids that follow sort order
*/

  orderInflections: function (lemma) {
    var infGIDs = lemma.entityIDs,k,icf,curGen,curCase,curNum,infHash,
        inflection, infSortedHashes, infMap = {};
    //output 'ordered according to inflection' list of unique spelling attested forms
    for (k=0; k<infGIDs.length; k++) {
      //ignore any attested forms not assigned to an inflection (non inf entIDS)
      if (!infGIDs[k].match(/inf/)) {
        continue;
      }
      inflection = this.dataMgr.getEntityFromGID(infGIDs[k]);
    //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
      icf = inflection.certainty ? inflection.certainty.join('') : '33333333';
      infHash = "";
      if (inflection.tense) {
        infHash += inflection.tense;//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      if (inflection.voice) {
        infHash += inflection.voice;//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      if (inflection.mood) {
        infHash += inflection.mood;//map 2 or 3 into 2
      } else {
        infHash += '8411';
      }
      if (inflection.gender) {
        infHash += inflection.gender;//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      if (inflection.num) {
        infHash += inflection.num;//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      if (inflection['case']) {
        infHash += inflection['case'];//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      if (inflection.person) {
        infHash += inflection.person;//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      if (inflection.conj2nd) {
        infHash += inflection.conj2nd;//map 2 or 3 into 2
      } else {
        infHash += 'none';
      }
      infHash += icf;
      if (infMap[infHash]) {
        DEBUG.log('warn',"Duplicate inflection "+infGIDs[k]+" found in Lemma "+ lemma.tag);
        continue;
      }
      infMap[infHash]=infGIDs[k];
    }
    infGIDs = [];
    infSortedHashes = Object.keys(infMap).sort();
    for (infHash in infSortedHashes) {
//    for (infHash in infMap) {
      infGIDs.push(infMap[infSortedHashes[infHash]]);
    }
    return infGIDs;
  },


/**
* refresh glossary header
*
*/

  refreshGlossaryHeader: function () {
    var wordlistVE = this,
        catalog = this.dataMgr.entities.cat[this.catID],
        headerDiv = $('h3.wordListTitle',this.contentDiv);
    headerDiv.html(catalog.value);
  },


/**
* remove wordlist entry from display
*
* @param string entTag Entity tag to be removed
*/

  removeWordlistEntry: function (entTag) {
    var wordlistVE = this, wordLemIDs, index,
        prefix = entTag.substring(0,3),
        entID = entTag.substring(3), value,
        wlEntry = $('div.wordlistentry:has(span.'+entTag+')');
    if (wlEntry.length) {
      if (prefix == 'lem') {
        value = wlEntry.children('.lemma').html().trim();
        wordlemIDs = wordlistVE.lemLookup[value];
        if (wordlemIDs && wordlemIDs.length) {
          index = wordlemIDs.indexOf(entID);
          if (index > -1) {
            wordlemIDs.splice(index,1);
            if (wordlemIDs.length) {
              wordlistVE.lemLookup[value] = wordlemIDs;
            } else {
              delete wordlistVE.lemLookup[value];
            }
          }
        }
      }
      wlEntry.remove();
    }
  },


/**
* refresh glossary
*
* @param string selectEntTag Entity tag of selected entity
*/

  refreshGlossary: function (selectEntTag) {
    var wordlistVE = this, selectNode;
    this.displayGlossary();
    this.reFilterGlossary();
    if (selectEntTag) {
      selectNode = $("."+selectEntTag,this.contentDiv);
      if (selectNode.length) {
        selectNode.trigger('dblclick');
        selectNode[0].scrollIntoView();
      }
    }
  },


/**
* put your comment there...
*
*/

  reFilterGlossary: function () {
    var wordlistVE = this, checkedItems =  wordlistVE.showEdnTree.jqxTree('getCheckedItems');
    //filter mode
    //filter editions
    //filter searchString
  },


/**
* display wordlist
*/

  displayWordList: function () {
    var wordlistVE = this,i,j,cnt,sequence, html = "",
        edition = this.dataMgr.entities.edn[this.ednID],
        seqIDs, wordGIDs, seqGIDs, label, txtSeqGIDs = [], physSeqGIDs = [],
        sequence, prefix, id, tag, word, entities = [], words = {},
        txtSeqGIDs, physSeqGIDs, fSclID, lSclID, sclIDs, defLabel, seqTags = {},
        linkedWords = {}, linkedWordTags = {}, sclTagToLabel = {}, ednLblByEntTag = {};
    //create sorted wordlist for all edition tokens
    //for edition find token sequences and find physical sequences and create sclID to label
    if ( edition && edition.seqIDs && edition.seqIDs.length) {
      seqIDs = edition.seqIDs;
      for (i=0; i<seqIDs.length; i++) {
        sequence =  this.dataMgr.getEntity('seq',seqIDs[i]);;
        if (sequence.typeID == this.dataMgr.termInfo.idByTerm_ParentLabel['text-sequencetype']) {//term dependency
          txtSeqGIDs = txtSeqGIDs.concat(sequence.entityIDs);
        }
        if (sequence.typeID == this.dataMgr.termInfo.idByTerm_ParentLabel['textphysical-sequencetype']) {//term dependency
          physSeqGIDs = physSeqGIDs.concat(sequence.entityIDs);
        }
      }
    }
    if (txtSeqGIDs && txtSeqGIDs.length) {// capture each sequence once
      for (i=0; i<txtSeqGIDs.length; i++) {
        tag = txtSeqGIDs[i].replace(":","");
        ednLblByEntTag[tag] = this.ednID;
      }
    }
    if (physSeqGIDs && physSeqGIDs.length) {// capture each sequence once
      for (i=0; i<physSeqGIDs.length; i++) {
        sequence =  this.dataMgr.getEntityFromGID(physSeqGIDs[i]);
        label = sequence.label;
        sclGIDs = sequence.entityIDs;
        if (label && sclGIDs.length) {
          for (j=0; j<sclGIDs.length; j++) {
            tag = sclGIDs[j].replace(":","");
            sclTagToLabel[tag] = label;
          }
        }
      }
    }
    for (tag in ednLblByEntTag) {
      sequence = this.dataMgr.getEntity(tag.substr(0,3),tag.substr(3));
      defLabel = sequence.label;
      wordGIDs = sequence.entityIDs;
      //for each word
      for (i=0; i<wordGIDs.length; i++) {
        fSclID = lSclID = null;
        tag = wordGIDs[i].replace(":","");
        if (!tag || tag.length<4) {//no GID so skip
          continue;
        }
        prefix = tag.substr(0,3);
        id = tag.substr(3);
        word = this.dataMgr.getEntity(prefix,id);
        if (word.sort && word.sort >= 0.7) {
          continue;
        }
        word.tag = tag;
        // find first and last SclID for word
        if (prefix == 'cmp' && word.tokenIDs.length) {
          fToken = this.dataMgr.getEntity('tok',word.tokenIDs[0]);
          if ( fToken && fToken.syllableClusterIDs && fToken.syllableClusterIDs.length && sclTagToLabel['scl'+fToken.syllableClusterIDs[0]]) {
            label = sclTagToLabel['scl'+fToken.syllableClusterIDs[0]];
            lToken = this.dataMgr.getEntity('tok',word.tokenIDs[word.tokenIDs.length - 1]);
            if ( lToken && lToken.syllableClusterIDs && lToken.syllableClusterIDs.length &&
                sclTagToLabel['scl'+lToken.syllableClusterIDs[lToken.syllableClusterIDs.length - 1]] &&
                sclTagToLabel['scl'+lToken.syllableClusterIDs[lToken.syllableClusterIDs.length - 1]] != label) {
              label += "-" + sclTagToLabel['scl'+lToken.syllableClusterIDs[lToken.syllableClusterIDs.length - 1]];
            }
            word.locLabel = label;
          } else {
            word.locLabel = defLabel;
          }
        } else if (prefix == 'tok') {
          if ( word.syllableClusterIDs && word.syllableClusterIDs.length && sclTagToLabel['scl'+word.syllableClusterIDs[0]]) {
            label = sclTagToLabel['scl'+word.syllableClusterIDs[0]];
            if (sclTagToLabel['scl'+word.syllableClusterIDs[word.syllableClusterIDs.length - 1]] &&
                sclTagToLabel['scl'+word.syllableClusterIDs[word.syllableClusterIDs.length - 1]] != label) {
              label += "-" + sclTagToLabel['scl'+word.syllableClusterIDs[word.syllableClusterIDs.length - 1]];
            }
            word.locLabel = label;
          } else {
            word.locLabel = defLabel;
          }
        }
        //add word to entities and tag to word
        entities.push(word);
      }
    }
    //sort entities array using UTILITY.compareEntities
    entities.sort(UTILITY.compareEntities);
    html = '<h3 class="wordListTitle edn'+edition.id+'">'+edition.value+' Word List</h3>';
    for (i=0; i<entities.length; i++) {
      word = entities[i];
      html += '<div class="wordlistentry"><span class="word '+word.tag+'" srch="'+word.value+'">' + word.transcr.replace(/ʔ/g,'') + ' ' + word.locLabel + '</span></div>';
    }
    this.contentDiv.html(html);
    this.addEventHandlers(this.contentDiv);
  },


/**
* setLink mode
*
* @param bLinkMode
*/

  setLinkMode: function(bLinkMode) {
    this.attestedLinkMode = bLinkMode;
    this.relatedLinkMode = false;
  },


/**
* set related link mode
*
* @param bLinkMode
*/

  setLinkRelatedMode: function(bLinkMode) {
    this.relatedLinkMode = bLinkMode;
  },

/**
* add event handlers
*
* @param node scope html or jquery node for scope of adding eventhandlers
*/
  addEventHandlers: function(scope) {
    var wordlistVE = this, entities = this.dataMgr.entities;

    if ( false || $('.edndraghandle',scope).length) {
      $('.edndraghandle',scope).unbind('dragStart')
                                .bind('dragStart', function (e) {
                                      var ednTag, lineNum, wordTag,
                                          classes = $(this).parent().attr('class');
                                      if (!classes.match(/edn/)) {
                                        e.preventDefault();
                                        return false;
                                      }
                                      ednTag = classes.match(/edn\d+/)[0];
                                      if (classes.match(/tok/)) {
                                        wordTag = classes.match(/tok\d+/)[0];
                                      } else {
                                        wordTag = classes.match(/cmp\d+/)[0];
                                      }
                                      if ($(this).text().match(/\:/)) {
                                        lineNum = $(this).text().match(/\:\s?(\d+)/)[1];
                                      }
                                      $(this).jqxDragDrop('data', {
                                                                ednTag: ednTag,
                                                                wordTag: wordTag,
                                                                lineNum: lineNum});
                                      });
      $('.edndraghandle',scope).unbind('dragEnd').bind('dragEnd', function (e) {
                                                var data = e.args, target = e.args.target,
                                                    ednTag,paneID;
                                                    if (data && data.ednTag) {
                                                      ednTag = data.ednTag;
                                                      paneID = target.className.match(/pane\d+/);
                                                      if (!paneID) {
                                                        paneID = $(target).parents('.editContainer').attr('class').match(/pane\d+/);
                                                      }
                                                      if (paneID) {
                                                        wordlistVE.layoutMgr.loadPaneContent(ednTag,paneID[0]);
                                                      }
                                                    } else {
                                                      return false;
                                                    }
                                                 });
    }


    /**
    * handle 'updateselection' event
    *
    * @param object e System event object
    * @param string senderID Identifies the sending editor pane for recursion control
    * @param string array selectionGIDs Global entity ids if entities hovered
    * @param string entTag Entity tag of selection
    */

    function updateSelectionHandler(e,senderID, selectionIDs, entTag) {
      if (senderID == wordlistVE.id) {
        return;
      }
      var i, id, prefix;
      DEBUG.log("event","selection changed recieved by "+wordlistVE.id+" from "+senderID+" selected ids "+ selectionIDs.join());
      $(".selected", wordlistVE.contentDiv).removeClass("selected");
      if (selectionIDs && selectionIDs.length && selectionIDs[0].substr(0,3) != 'seg') {
        $.each(selectionIDs, function(i,val) {
          var elem = $('.'+val,wordlistVE.contentDiv)
          if (!elem.hasClass("selected")) {
            elem.addClass("selected");
          }
        });
      } else if (entTag && entTag.length && entTag.length > 3) {
        prefix = entTag.substr(0,3);
        if (prefix == 'tok' || prefix == 'cmp' ) {
          $('.'+entTag,wordlistVE.contentDiv).addClass("selected");
        }
      }
    };

    $(this.editDiv).unbind('updateselection').bind('updateselection', updateSelectionHandler);


    /**
    * handle 'click' event for contentdiv
    *
    * @param object e System event object
    *
    * @returns true|false
    */

    function wordlistClickHandler(e) {
      if ( wordlistVE.id != wordlistVE.layoutMgr.focusPaneID) {
        wordlistVE.layoutMgr.focusHandler(e,wordlistVE.id);
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for focus change
    this.contentDiv.unbind('click').bind('click',wordlistClickHandler);


    /**
    * handle 'click' event for '.word' entries
    *
    * @param object e System event object
    *
    * @returns true|false
    */

    function wordClickHandler(e) {
      if ( wordlistVE.id != wordlistVE.layoutMgr.focusPaneID) {
        wordlistVE.layoutMgr.focusHandler(e,wordlistVE.id);
      }
      //make sure we have a lemmaVE and are in link mode
      if (!wordlistVE.attestedLinkMode || !wordlistVE.lemmaVE) {
        return;
      }
      var classes = $(this).attr('class'), entTag, entGID;
      entTag = classes.match(/(?:tok|cmp)\d+/)[0];
      entGID = entTag.substring(0,3)+":"+entTag.substring(3);
      wordlistVE.lemmaVE.linkAttestedForm(entGID);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all word elements
    $(".word", scope).unbind('click').bind('click',wordClickHandler);


    /**
    * handle 'dblclick' event for '.word' and '.linkedword' entries
    *
    * @param object e System event object
    *
    * @returns true|false
    */

     function wordDblClickHandler(e) {
      var classes = $(this).attr('class'), entTag, lemTag, tag, wordTags = [];
      if(!e.ctrlKey){
        $(".selected", wordlistVE.contentDiv).removeClass("selected");
      }
      entTag = classes.match(/(?:tok|cmp)\d+/)[0];
      if (classes.match(/linked/)) {
        tag = '.linkedword.'+entTag;
        if ($(this).parent().find('.lemma').length) {
          classes = $(this).parent().find('.lemma').attr('class');
          if (classes.match(/lem\d+/)) {
            lemTag = classes.match(/lem\d+/)[0];
            wordlistVE.propMgr.showVE("lemmaVE",lemTag);
          }
        }
      }else{
        tag = '.word.'+entTag;
        wordlistVE.propMgr.showVE("lemmaVE",entTag);
      }
      $(tag,wordlistVE.contentDiv).addClass("selected");
      //find element ids for selected
      $(".word.selected,.linkedword.selected",wordlistVE.contentDiv).each(function(index,el){
           tag = el.className.match(/(?:tok|cmp)\d+/)[0];
           if (wordTags.indexOf(tag) == -1) {
            wordTags.push(tag);
           }
      });
      //trigger selection change
      $('.editContainer').trigger('updateselection',[wordlistVE.id,wordTags,entTag]);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all word elements
    $(".word", scope).unbind('dblclick').bind('dblclick',wordDblClickHandler);
    $(".linkedword", scope).unbind('dblclick').bind('dblclick',wordDblClickHandler);


/**
    * handle 'click' event for '.lemma' entries
*
* @param object e System event object
*
* @returns true|false
*/

    function lemClickHandler(e) {
      if ( wordlistVE.id != wordlistVE.layoutMgr.focusPaneID) {
        wordlistVE.layoutMgr.focusHandler(e,wordlistVE.id);
      }
      //make sure we have a lemmaVE and are in link mode
      if (!wordlistVE.relatedLinkMode || !wordlistVE.lemmaVE) {
        return;
      }
      var classes = $(this).attr('class'), entTag, entGID;
      entTag = classes.match(/lem\d+/)[0];
      entGID = entTag.substring(0,3)+":"+entTag.substring(3);
      if (wordlistVE.lemmaVE.linkRelatedEntity) {
        wordlistVE.lemmaVE.linkRelatedEntity(entGID);
        wordlistVE.relatedLinkMode = false;
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all word elements
    $(".lemma", scope).unbind('click').bind('click',lemClickHandler);


    /**
    * handle 'dblclick' event for '.lemma' entries
    *
    * @param object e System event object
    *
    * @returns true|false
    */

    function lemDblClickHandler(e) {
      var classes = $(this).attr('class'),lemTag, tag, wordTags = [];
      if(!e.ctrlKey){
        $(".selected", wordlistVE.contentDiv).removeClass("selected");
      }
      lemTag = classes.match(/lem\d+/)[0];
      wordlistVE.propMgr.showVE("lemmaVE",lemTag);
      $(this).addClass('selected');
      //find element ids for linked
      $('.linkedword',this.parentNode).addClass('selected');
      //find element ids for selected
      $(".word.selected,.linkedword.selected",wordlistVE.contentDiv).each(function(index,el){
           tag = el.className.match(/(?:tok|cmp)\d+/)[0];
           if (wordTags.indexOf(tag) == -1) {
            wordTags.push(tag);
           }
      });
      $('.editContainer').trigger('updateselection',[wordlistVE.id,wordTags,lemTag]);
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    $(".lemma", scope).unbind('dblclick').bind('dblclick',lemDblClickHandler);


/**
    * handle 'dblclick' event for '.wordListTitle' elements
*
* @param object e System event object
*
* @returns true|false
*/

    function titleDblClickHandler(e) {
      var classes = $(this).attr('class'),catTag;
      catTag = classes.match(/cat\d+/)[0];
      if (catTag) {
        wordlistVE.propMgr.showVE('entPropVE',catTag);
      }
      e.stopImmediatePropagation();
      return false;
    };

    //assign handler for all grapheme group elements
    $(".wordListTitle", scope).unbind('dblclick').bind('dblclick',titleDblClickHandler);
  }

}

