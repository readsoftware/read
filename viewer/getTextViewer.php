<?php
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
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/

/**
* viewer
*
* creates a framework for the text viewer interface according to the setting in config.php
* it support opening with a parameter set that defines the layout.
*/

  require_once (dirname(__FILE__) . '/../common/php/sessionStartUp.php');//initialize the session
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//get user access control
  require_once (dirname(__FILE__) . '/php/viewutils.php');//get utilities for viewing
  require_once (dirname(__FILE__) . '/php/testdata.php');//get user access control
  $dbMgr = new DBManager();
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    returnXMLErrorMsgPage("invalid viewer request - not enough or invalid parameters");
  } else {
    if ( isset($data['ednID'])) {//required
      $ednID = $data['ednID'];
      $ednIDs = explode(",",$ednID);
      $ednID = $ednIDs[0];
      $glossaryEntTag = "edn".$ednID;
      $edition = new Edition($ednID);
      if ($edition->hasError()) {
        returnXMLErrorMsgPage("unable to load edition - ".join(",",$edition->getErrors()));
      }
      $text = $edition->getText(true);
      if (!$text) {
        returnXMLErrorMsgPage("invalid viewer request - access denied");
      }
      $title = ($text->getCKN()?$text->getCKN()." ∙ ":"").$text->getTitle();
    } else {
      returnXMLErrorMsgPage("invalid viewer request - not enough or invalid parameters");
    }
    if ( isset($data['catID'])) {//optional override
      $glossaryEntTag = "cat".$data['catID'];
    }
  }
  $showContentOutline = defined("SHOWVIEWERCONTENTOUTLINE")?SHOWVIEWERCONTENTOUTLINE:true;
  $showImageView = defined("SHOWIMAGEVIEW")?SHOWIMAGEVIEW:true;
  $showTranslationView = defined("SHOWTRANSLATIONVIEW")?SHOWTRANSLATIONVIEW:true;
  $showChayaView = defined("SHOWCHAYAVIEW")?SHOWCHAYAVIEW:true;

  //TODO add code to ensure that baseline/images exist, translation exist and CHAYA exist
  $edAnnoTypes = getEditionAnnotationTypes($edition->getID());
  $blnIDs = array(1);
  $hasTranslation = in_array(Entity::getIDofTermParentLabel('translation-annotationtype'),$edAnnoTypes);
  $hasChaya = in_array(Entity::getIDofTermParentLabel('chaya-translation'),$edAnnoTypes);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title><?=$title?></title>
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.energyblue.css" type="text/css" />
    <link rel="stylesheet" href="./css/readviewer.css" type="text/css" />
    <script src="/jquery/jquery-1.11.0.min.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcore.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtouch.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdata.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtabs.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownbutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttons.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttongroup.js"></script>
    <script src="/jqwidget/jqwidgets/jqxradiobutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxscrollbar.js"></script>
    <script src="/jqwidget/jqwidgets/jqxexpander.js"></script>
    <script src="/jqwidget/jqwidgets/jqxnavigationbar.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdragdrop.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.pager.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.selection.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.filter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.sort.js"></script>
    <script src="/jqwidget/jqwidgets/jqxmenu.js"></script>
    <script src="/jqwidget/jqwidgets/jqxwindow.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdocking.js"></script>
    <script src="/jqwidget/jqwidgets/jqxsplitter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownlist.js"></script>
    <script src="/jqwidget/jqwidgets/jqxlistbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtooltip.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcheckbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxvalidator.js"></script>
    <script src="/jqwidget/jqwidgets/jqxpanel.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtree.js"></script>
    <script src="../editors/js/debug.js"></script>
    <script type="text/javascript">
      var sktSort = ('<?=USESKTSORT?>' == "0" || !'<?=USESKTSORT?>')?false:true,
          maxUploadSize = parseInt(<?=MAX_UPLOAD_SIZE?>),
          linkToSyllablePattern = '<?=defined("LINKSYLPATTERN")?LINKSYLPATTERN:""?>',
          progressInputName='<?php echo ini_get("session.upload_progress.name"); ?>',
          dbName = '<?=DBNAME?>',
          basepath="<?=SITE_BASE_PATH?>";
      var edStructHtml = <?=getEditionsStructuralViewHtml($ednIDs)?>,
          edFootnotes = <?=getEditionFootnoteTextLookup()?>,
          edGlossaryLookup = <?=getEditionGlossaryLookup($glossaryEntTag)?>,
<?php
  if ($showContentOutline) {
?>
          tocHtml = '<?=getEditionTOCHtml()?>',
<?php
  }
  if ($showTranslationView && $hasTranslation) {
?>
          transStructHtml = <?=getEditionsStructuralTranslationHtml($ednIDs)?>,
          transFootnotes = <?=getEditionTranslationFootnoteTextLookup()?>,
<?php
  }
  if ($showChayaView && $hasChaya) {
?>
          chayaStructHtml = <?=getEditionsStructuralTranslationHtml($ednIDs, Entity::getIDofTermParentLabel('chaya-translation'))?>,
          chayaFootnotes = <?=getEditionTranslationFootnoteTextLookup()?>,
<?php
  }
?>
          testHtmlSmall = <?=$testHtmlSmall?>;

/**
* handle 'scroll' event for content div
*
* @param object e System event object
*
* @returns true|false
*/

    function viewScrollHandler(e) {
      var top = this.scrollTop + this.offsetTop, viewHeight = this.offsetHeight, minY, hdrSeqTag = null, $secHdrDivs,
          lineSeqTag = null, $lineLblSpans, lineFraction = 0, hdrFraction = 0, imgScrollData = null;
      e.stopImmediatePropagation();
      if (!this.supressSynchOnce) {
        DEBUG.log("event","scroll view top = "+top+" height = "+viewHeight);
        minY = 10000;
        $lineLblSpans = $(this).find('span.linelabel');
        if ($lineLblSpans.length) {
          $lineLblSpans.each(function(index,lblSpan) {
            if (lblSpan.offsetTop + lblSpan.offsetHeight > top) { //visible
              if (lblSpan.offsetTop < minY) {
                lineSeqTag = lblSpan.className.match(/seq\d+/)[0];
                minY = lblSpan.offsetTop;
                lineFraction = (top - lblSpan.offsetTop)/lblSpan.offsetHeight;
              }
            }
          });
        }
        minY = 10000;
        $secHdrDivs = $(this).find('div.secHeader');
        if ($secHdrDivs.length) {
          $secHdrDivs.each(function(index,secDiv) {
            if (secDiv.offsetTop + secDiv.offsetHeight> top) { //visible
              if (secDiv.offsetTop < minY) {
                hdrSeqTag = secDiv.className.match(/seq\d+/)[0];
                minY = secDiv.offsetTop;
                hdrFraction = (top - secDiv.offsetTop)/secDiv.offsetHeight;
              }
            }
          });
        }
//        imgScrollData = this.getImageScrollData(segTag,lineFraction);
        $('.viewerContent').trigger('synchronize',[this.id,lineSeqTag,lineFraction,hdrSeqTag,hdrFraction,viewHeight,imgScrollData]);
      } else {
        delete this.supressSynchOnce;
      }
      return false;
    };

    /**
    * handle 'synchronize' event for edit div
    *
    * @param object e System event object
    * @param string senderID Identifies the sending view pane for recursion control
    * @param string anchorTag tag of anchor sequence
    * @param number visFraction Fraction of display viewed relative to the anchor entity
    */

    function synchronizeHandler(e,senderID,lineSeqTag,lineFraction,hdrSeqTag,hdrFraction,scrViewHeight,imgScrollData) {
      var $view = $(this), viewHeight = this.offsetHeight, $anchorElem, scrollElem, yAdjust, visFraction, newTop;
      if (senderID == this.id || !$view.parent().hasClass('syncScroll')) {
        return;
      }
      DEBUG.log("event","synch request recieved by "+this.id+" from "+senderID+" with lseqID "+ lineSeqTag + (lineFraction?" with lfraction" + lineFraction:""));
      DEBUG.log("event","synch request recieved by "+this.id+" from "+senderID+" with hseqID "+ hdrSeqTag + (hdrFraction?" with hfraction" + hdrFraction:""));
      $anchorElem = $('span.linelabel.'+lineSeqTag+':first',$view);
      visFraction = lineFraction;
      if (!$anchorElem || !$anchorElem.length) {
        $anchorElem = $('div.secHeader.'+hdrSeqTag+':first',$view);
        visFraction = hdrFraction;
      }
      if ($anchorElem && $anchorElem.length ==1) {
        scrollElem = $anchorElem.get(0);
        newTop = scrollElem.offsetTop - this.offsetTop + scrollElem.offsetHeight * visFraction;
        this.supressSynchOnce = true;
        $view.scrollTop(newTop);
      }
    };


    </script>
    <script src="../editors/js/utility.js"></script>
    <script src="../editors/js/debug.js"></script>
    <script type="text/javascript">
      $(document).ready( function () {
        var
            $textViewer = $('#textViewer'),
            $textViewerHdr = $('#textViewerHdr'),
            $textViewerContent = $('#textViewerContent')
<?php
  if ($showContentOutline) {
?>
,
            $tocNavPanel= $('#tocNavPanel'),
            $tocNavButton= $('.tocNavButton')
<?php
  }
?>
<?php
  if ($showImageView && count($blnIDs) > 0) {
?>
,
            $imageViewer= $('#imageViewer'),
            $imageViewerHdr= $('#imageViewerHdr'),
            $imageViewerContent= $('#imageViewerContent')
<?php
  }
?>
<?php
  if ($showTranslationView && $hasTranslation) {
?>
,
            $transViewer = $('#transViewer'),
            $transViewerHdr = $('#transViewerHdr'),
            $transViewerContent = $('#transViewerContent')
<?php
  }
?>
<?php
  if ($showChayaView && $hasChaya) {
?>
,
            $chayaViewer = $('#chayaViewer'),
            $chayaViewerHdr = $('#chayaViewerHdr'),
            $chayaViewerContent = $('#chayaViewerContent')
<?php
  }
?>
;
<?php
  if ($showContentOutline) {
?>
//initialise toc
            $tocNavPanel.html(tocHtml);
            $('.tocEntry',$tocNavPanel).unbind('click').bind('click', function(e) {
              var $body = $('body'),classes = $(this).attr("class"), tocID, seqTag;
              tocID = $(this).attr('id');
              seqTag = tocID.substring(3);
              $body.removeClass('showTOC');
              $('.viewerContent').trigger('synchronize',[tocID,null,0,seqTag,0,null,null]);
              e.stopImmediatePropagation();
              return false;
            });

            $tocNavButton.unbind('click').bind('click', function(e) {
              var $body = $('body');
              if ($body.hasClass('showTOC')) {
                $body.removeClass('showTOC');
              } else {
                $body.addClass('showTOC');
              }
              e.stopImmediatePropagation();
              return false;
            });


<?php
  }
?>
<?php
  if ($showImageView && count($blnIDs) > 0) {
?>
//initialise imageViewer
            $imageViewer.jqxExpander({expanded:true,
                                      showArrow: false,
                                      expandAnimationDuration:50,
                                      collapseAnimationDuration:50});
//            $imageViewerContent.html(testHtmlSmall);
            $imageViewerContent.height('150px');
<?php
  }
?>
            function closeAllPopups(e) {
              var $showing = $('.showing'), $body = $('body');
              if ($showing && $showing.length) {
                $showing.removeClass('showing');
                $showing.jqxTooltip('close'); //close other
              }
              if ($body.hasClass('showTOC')) {
                $body.removeClass('showTOC');
              }
            }

//initialise textViewer
            $textViewer.jqxExpander({expanded:true,
                                      showArrow: false,
                                      expandAnimationDuration:50,
                                      collapseAnimationDuration:50});
            $textViewerContent.html(edStructHtml);
            $textViewerContent.height('150px');
            if (edFootnotes && typeof edFootnotes == 'object' && Object.keys(edFootnotes).length > 0) {
              $('.footnote',$textViewerContent).unbind('click').bind('click', function(e) {
                var id = this.id, footnoteHtml, $showing;
                  footnoteHtml = (edFootnotes[id]?edFootnotes[id]:"unable to find footnote text or empty footnote");
                  $(this).jqxTooltip({content: '<div class="popupwrapperdiv">'+footnoteHtml+"</div>",
                                      trigger: 'click',
                                      showArrow: false,
                                      autoHide: false });
                  closeAllPopups();
                  $(this).unbind('close').bind('close', function(e) {
                    $(this).jqxTooltip('destroy');
                  });
                  $(this).jqxTooltip('open');
                  $(this).addClass('showing');
                  e.stopImmediatePropagation();
                  return false;
              });
            }
            $textViewerContent.unbind('click').bind('click', closeAllPopups);
            $('.grpTok',$textViewerContent).unbind('click').bind('click', function(e) {
              var classes = $(this).attr("class"), entTag, entTags, lemTag, lemmaInfo, entGlossInfo
                  popupHtml = "No lemma info for " + $(this).text();
              if ( entTags = classes.match(/cmp\d+/)) {//use first cmp tag for tool tip
                entTag = entTags[0];
              } else {
                 entTag = classes.match(/tok\d+/)
              }
              if (entTag && edGlossaryLookup[entTag]) {
                entGlossInfo = edGlossaryLookup[entTag];
                if (entGlossInfo['lemTag']) {
                  lemmaInfo = edGlossaryLookup[entGlossInfo['lemTag']];
                  if (lemmaInfo && lemmaInfo['entry']) {
                    popupHtml = lemmaInfo['entry'];
                    if (entGlossInfo['infHtml']) {
                      popupHtml += entGlossInfo['infHtml'];
                    }
                    if (lemmaInfo['attestedHtml']) {
                      popupHtml += lemmaInfo['attestedHtml'];
                    }
                  }
                }
              }
              closeAllPopups();
              $(this).jqxTooltip({ content: '<div class="popupwrapperdiv">'+popupHtml+"</div>",
                                   trigger: 'click',
                                   showArrow: false,
                                   autoHide: false });
              $(this).unbind('close').bind('close', function(e) {
                $(this).jqxTooltip('destroy');
              });
              $(this).jqxTooltip('open');
              $(this).addClass('showing');
              e.stopImmediatePropagation();
              return false;
            });

            //assign handler for all syllable elements
            $textViewerContent.unbind("scroll").bind("scroll", viewScrollHandler);

            $textViewerContent.unbind('synchronize').bind('synchronize', synchronizeHandler);


            $('.linkScroll').unbind('click').bind('click', function(e) {
              var $viewer = $(this).closest('.viewer');
              if ($viewer.hasClass('syncScroll')) {
                $viewer.removeClass('syncScroll');
                $(this).attr('title','sync scroll off');
              } else {
                $viewer.addClass('syncScroll');
                $(this).attr('title','sync scroll on');
              }
              e.stopImmediatePropagation();
              return false;
            });
<?php
  if ($showTranslationView && $hasTranslation) {
?>
//initialise transViewer
            $transViewer.jqxExpander({expanded:true,
                                      showArrow: false,
                                      expandAnimationDuration:50,
                                      collapseAnimationDuration:50});
            $transViewerContent.html(transStructHtml);
            $transViewerContent.height('150px');
            $transViewerContent.unbind('click').bind('click', closeAllPopups);
            if (transFootnotes && typeof transFootnotes == 'object' && Object.keys(transFootnotes).length > 0) {
              $('.footnote',$transViewerContent).unbind('click').bind('click', function(e) {
                var id = this.id, footnoteHtml;
                  footnoteHtml = (transFootnotes[id]?transFootnotes[id]:"unable to find footnote text or empty footnote");
                  $(this).jqxTooltip({content: '<div class="popupwrapperdiv">'+footnoteHtml+"</div>",
                                      trigger: 'click',
                                      autoHide: false,
                                      showArrow: false });
                  closeAllPopups();
                  $(this).unbind('close').bind('close', function(e) {
                    $(this).jqxTooltip('destroy');
                  });
                  $(this).jqxTooltip('open');
                  $(this).addClass('showing');
                  e.stopImmediatePropagation();
                  return false;
              });
            }

            //assign handler for all syllable elements
            $transViewerContent.unbind("scroll").bind("scroll", viewScrollHandler);

            $transViewerContent.unbind('synchronize').bind('synchronize', synchronizeHandler);
<?php
  }
?>
<?php
  if ($showChayaView && $hasChaya) {
?>
//initialise chayaViewer
            $chayaViewer.jqxExpander({expanded:true,
                                      showArrow: false,
                                      expandAnimationDuration:50,
                                      collapseAnimationDuration:50});
            $chayaViewerContent.html(chayaStructHtml);
            $chayaViewerContent.height('150px');
            $chayaViewerContent.unbind('click').bind('click',closeAllPopups);
            if (chayaFootnotes && typeof chayaFootnotes == 'object' && Object.keys(chayaFootnotes).length > 0) {
              $('.footnote',$chayaViewerContent).unbind('click').bind('click', function(e) {
                var id = this.id, footnoteHtml;
                  footnoteHtml = (chayaFootnotes[id]?chayaFootnotes[id]:"unable to find footnote text or empty footnote");
                  $(this).jqxTooltip({content: '<div class="popupwrapperdiv">'+footnoteHtml+"</div>",
                                      trigger: 'click',
                                      autoHide: false,
                                      showArrow: false });
                  closeAllPopups();
                  $(this).unbind('close').bind('close', function(e) {
                    $(this).jqxTooltip('destroy');
                  });
                  $(this).jqxTooltip('open');
                  $(this).addClass('showing');
                  e.stopImmediatePropagation();
                  return false;
              });
            }

            //assign handler for all syllable elements
            $chayaViewerContent.unbind("scroll").bind("scroll", viewScrollHandler);

            $chayaViewerContent.unbind('synchronize').bind('synchronize', synchronizeHandler);
<?php
  }
?>
      });
    </script>
  </head>
<body>
<?php
  if ($showContentOutline) {
?>
  <div id="tocNavPanel" class="tocNavPanel"></div>
  <div class="headline"><div class="tocNavButton" title="Table of Contents">&#9776;</div><div class="titleDiv"><?=$title?></div></div>
<?php
  } else {
?>
  <div class="headline"><div class="titleDiv"><?=$title?></div></div>
<?php
  }
?>
<?php
  if ($showImageView && count($blnIDs) > 0) {
?>
    <div id="imageViewer" class="viewer">
      <div id="imageViewerHdr" class="viewerHeader"><div class="viewerHeaderLabel"><button class="linkScroll" title="sync scroll off">&#x1F517;</button>Image</div></div>
      <div id="imageViewerContent" class="viewerContent">test</div>
    </div>
<?php
  }
?>

    <div id="textViewer" class="viewer syncScroll">
      <div id="textViewerHdr" class="viewerHeader"><div class="viewerHeaderLabel"><button class="linkScroll" title="sync scroll on">&#x1F517;</button>Text</div></div>
      <div id="textViewerContent" class="viewerContent">test</div>
    </div>
<?php
  if ($showTranslationView && $hasTranslation) {
?>
    <div id="transViewer" class="viewer syncScroll">
      <div id="transViewerHdr" class="viewerHeader"><div class="viewerHeaderLabel"><button class="linkScroll" title="sync scroll on">&#x1F517;</button>Translation</div></div>
      <div id="transViewerContent" class="viewerContent">test</div>
    </div>
<?php
  }
?>
<?php
  if ($showChayaView && $hasChaya) {
?>
    <div id="chayaViewer" class="viewer syncScroll">
      <div id="chayaViewerHdr" class="viewerHeader"><div class="viewerHeaderLabel"><button class="linkScroll" title="sync scroll on">&#x1F517;</button>Chāyā</div></div>
      <div id="chayaViewerContent" class="viewerContent">test</div>
    </div>
<?php
  }
?>
</body>
</html>
<?php
  function returnXMLSuccessMsgPage($msg) {
    die("<html><body><success>$msg</success></body></html>");
  }

  function returnXMLErrorMsgPage($msg) {
    die("<?xml version='1.0' encoding='UTF-8'?>\n<error>$msg</error>");
  }
?>
