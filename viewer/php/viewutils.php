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
* Utility functions for viewers
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Utility Classes
*/


require_once (dirname(__FILE__) . '/../../config.php');//get defines
require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../../model/entities/Terms.php');
// add required for switchInfo
require_once dirname(__FILE__) . '/../../model/entities/Graphemes.php';
require_once dirname(__FILE__) . '/../../model/entities/SyllableClusters.php';
require_once dirname(__FILE__) . '/../../model/entities/Tokens.php';
require_once dirname(__FILE__) . '/../../model/entities/Compounds.php';
require_once dirname(__FILE__) . '/../../model/entities/Baselines.php';
require_once dirname(__FILE__) . '/../../model/entities/Editions.php';
require_once dirname(__FILE__) . '/../../model/entities/Texts.php';
require_once dirname(__FILE__) . '/../../model/entities/Lemmas.php';
require_once dirname(__FILE__) . '/../../model/entities/Inflections.php';
require_once dirname(__FILE__) . '/../../model/entities/Sequences.php';
require_once dirname(__FILE__) . '/../../model/entities/JsonCache.php';
require_once dirname(__FILE__) . '/../../model/entities/Catalogs.php';
require_once dirname(__FILE__) . '/../../model/entities/UserGroups.php';
require_once dirname(__FILE__) . '/../../model/entities/AttributionGroup.php';
require_once dirname(__FILE__) . '/../../model/entities/Attribution.php';


/**
* gets the editions footnote lookup html as json
*
* @returns string json representing the lookup for footnotes of this object with a string representing the html and a footnote lookup table
*/
function getEditionFootnoteTextLookup() {
  global $fnRefTofnText;
  return json_encode($fnRefTofnText);
}

/**
* gets the editions footnote lookup html as json
*
* @returns string json representing the lookup for footnotes of this  object with a string representing the html and a footnote lookup table
*/
function getEditionTOCHtml() {
  global $editionTOCHtml;
  return $editionTOCHtml;
}

/**
* gets the bounding rects for token or compond of the editions
*
* @returns string json representing the lookup for bounding rectangles of a word
*/
function getPolygonByBaselineEntityTagLookup() {
  global $polysByBlnTagTokCmpTag;
  if ($polysByBlnTagTokCmpTag) {
    return json_encode($polysByBlnTagTokCmpTag);
  } else {
    return "{}";
  }
}

/**
* gets the baseline and position of entities of the editions
*
* @returns string json representing the lookup for baseline and position by entity tag
*/
function getBaselinePosByEntityTagLookup() {
  global $blnPosByEntTag;
  if ($blnPosByEntTag) {
    return json_encode($blnPosByEntTag);
  } else {
    return "{}";
  }
}

/**
* gets the editions image urls as json
*
* @returns string json representing the lookup for image urls by image tag and baseline tag
*/
function getImageBaselineURLLookup() {
  global $imgURLsbyBlnImgTag;
  $ret = array('bln'=>array(),'img'=>array());
  if ($imgURLsbyBlnImgTag) {
    if (count($imgURLsbyBlnImgTag['bln'])) {
      ksort($imgURLsbyBlnImgTag['bln']);
      foreach ($imgURLsbyBlnImgTag['bln'] as $sort=>$blnInfo) {
        $ret['bln'][$blnInfo['tag']] = $blnInfo;
      }
    }
    if (count($imgURLsbyBlnImgTag['img'])) {
      ksort($imgURLsbyBlnImgTag['img']);
      foreach ($imgURLsbyBlnImgTag['img'] as $sort=>$blnInfo) {
        $ret['img'][$blnInfo['tag']] = $blnInfo;
      }
    }
  }
  return json_encode($ret);
}

/**
* gets the editions translation footnote lookup html as json
*
* @returns string json representing the lookup for footnotes of this  object with a string representing the html and a footnote lookup table
*/
function getEditionTranslationFootnoteTextLookup() {
  global $tfnRefToTfnText;
  if ($tfnRefToTfnText) {
    return json_encode($tfnRefToTfnText);
  } else {
    return "{}";
  }
}

/**
* gets the editions glossary lookup html
*
*/
function getEditionGlossaryLookup($entTag, $scopeEdnID = null, $refresh = false, $urlMap = null) {
  if (!$entTag) {
    return '{}';
  }
  $catID = null;
  if (substr($entTag,0,3) == "cat") {
    $catID = substr($entTag,3);
    $catalog = new Catalog($catID);
    if ($catalog->hasError() || $catalog->getID() != $catID){
      $catID = null;
    }
  } else if (substr($entTag,0,3) == "edn") {// find all catalogs this edition belongs to and choose the first one.
    $glossaryTypeID = Entity::getIDofTermParentLabel('glossary-catalogtype'); //term dependency
    $catalogs = new Catalogs(substr($entTag,3)." = ANY(cat_edition_ids) and cat_type_id = $glossaryTypeID","cat_id",null,null);
    if (!$catalogs->getError() && $catalogs->getCount() > 0) {
      $catalog = $catalogs->current();//choose the first
      $catID = $catalog->getID();
      $scopeEdnID = substr($entTag,3);
    }
  }
  if ($catID) {
    $urlTemp = null;
    if ( $urlMap && array_key_exists("cat$catID",$urlMap)) {
      $urlTemp = $urlMap["cat$catID"];
    }
    if(USEVIEWERCACHING) {
      $cacheKey = "glosscat$catID"."edn".($scopeEdnID?$scopeEdnID:"all");
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->hasError() || !$jsonCache->getID()) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        $jsonCache->setJsonString(json_encode(getWrdTag2GlossaryPopupHtmlLookup($catID, $scopeEdnID, $refresh, $urlTemp)));
        $jsonCache->setVisibilityIDs($catalog->getVisibilityIDs());
        $jsonCache->setOwnerID($catalog->getOwnerID());
        $jsonCache->save();
//      } else if (($refresh || $jsonCache->isDirty()) && !$jsonCache->isReadonly()) {
      } else if (($refresh || $jsonCache->isDirty()) ) {
        $jsonCache->setJsonString(json_encode(getWrdTag2GlossaryPopupHtmlLookup($catID, $scopeEdnID, isset($refresh)?$refresh:false, $urlTemp)));
        $jsonCache->clearDirtyBit();
        $jsonCache->save();
      } else {
        error_log("warning: cache $cacheKey with cacheID (".($jsonCache->getID()?$jsonCache->getID():"null").
                  ") fall through with refresh = ".($refresh?"true":"false").
                  ", dirty = ".($jsonCache->isDirty()?"true":"false").
                  " and readonly =".($jsonCache->isReadonly()?"true":"false"));
      }
      if ($jsonCache->getID() && !$jsonCache->hasError()) {
        return $jsonCache->getJsonString();
      } else {
        return '""';
      }
    } else {
      return json_encode(getWrdTag2GlossaryPopupHtmlLookup($catID, $scopeEdnID, $refresh, $urlTemp));
    }
  }
  return "{}";
}

/**
* processed embedded footnotes and line markers
*
* footnotes are replaced by a superscript marker with reference id and the footnote text
* is aded to a global lookup with the same reference id. Line markers are validated and
* and placed into a span element with the corresponding entity tag for selection
*
* @param mixed $transText
* @param mixed $entTag
*
* @return string processed translation text with any needed Html
*/
function processTranslationText($transText, $entTag) {
  global $tfnRefToTfnText, $tfnCnt, $lineLabel2SeqTagMap, $fnPreMarker;
  $transHtml = "";
  //process embedded footnotes
  $fnStartIndex = strpos($transText,"((");
  $fnStopIndex = strpos($transText,"))");
  while ($fnStartIndex !== false && $fnStopIndex !== false) {
    $transHtml .= substr($transText,0,$fnStartIndex);//capture string before embedded footnote
    $fnText = substr($transText,2+$fnStartIndex, $fnStopIndex-$fnStartIndex-2);//extract footnote
    if ($fnText && strlen($fnText)) {//if we have a footnote
      $tfnCnt++;
      $tfnTag = $fnPreMarker.$tfnCnt;
      $fnOrd = "$tfnCnt";
      $transHtml .= "<sup id=\"$tfnTag\" class=\"footnote $entTag embedded $fnOrd\" >n</sup>";
      $sepIndex = strpos($fnText,':');
      if ($sepIndex !== false) {
        $fnText = trim(substr($fnText,$sepIndex+1));
      }
      if (json_encode($fnText) == false) {
        $temp = preg_replace('/“/','"', $fnText);
        $temp = preg_replace('/”/','"', $temp);
        if (json_encode($temp) == false) {
          $tfnRefToTfnText[$tfnTag] = json_last_error_msg();
        } else {
          $tfnRefToTfnText[$tfnTag] = $temp;
        }
      } else {
        $tfnRefToTfnText[$tfnTag] = $fnText;
      }
    }
    $transText = substr($transText,2+$fnStopIndex);//capture everything after the embedded footnote
    //check for more embedded footnotes.
    $fnStartIndex = strpos($transText,"((");
    $fnStopIndex = strpos($transText,"))");
  }
  $transHtml .= $transText;
  //process embedded line markers
  $transText = $transHtml;
  $transHtml = "";
  $lnStartIndex = strpos($transText,"[[");
  $lnStopIndex = strpos($transText,"]]");
  while ($lnStartIndex !== false && $lnStopIndex !== false) {
    $transHtml .= substr($transText,0,$lnStartIndex);//capture string before embedded line marker
    $lnLabel = substr($transText,2+$lnStartIndex, $lnStopIndex-$lnStartIndex-4);//extract line label
    if ($lnLabel && array_key_exists($lnLabel,$lineLabel2SeqTagMap)) {//if label is in lookup
      $seqTag = $lineLabel2SeqTagMap[$lnLabel];
      $transHtml .= "<span class=\"linelabel $seqTag\">[$lnLabel]</span>";
    } else {//invalid line marker so leave it as is
      $transHtml .= substr($transText,$lnStartIndex, $lnStopIndex-$lnStartIndex);
    }
    $transText = substr($transText,2+$lnStopIndex);//capture everything after the embedded line marker
    //check for more embedded line markers.
    $lnStartIndex = strpos($transText,"[[");
    $lnStopIndex = strpos($transText,"]]");
  }
  $transHtml .= $transText;
  return $transHtml;
}

/**
* gets annotations for the type of translation being calculated
*
* calls helper to processed embedded footnotes  and line markers
*
* @param mixed $entity
*
* @return string translation with embedded html
*/
function getEntityTranslation($entity) {
  global $translationTypeID;
  $transText = "";
  $entTag = $entity->getEntityTag();
  if ( $linkedAnoIDsByType = $entity->getLinkedAnnotationsByType()) {
    if (array_key_exists($translationTypeID,$linkedAnoIDsByType)) {
      foreach ($linkedAnoIDsByType[$translationTypeID] as $anoID) {
        $annotation = new Annotation($anoID);
        if ($annotation && !$annotation->hasError()) {
          $anoText = $annotation->getText();
          if ($anoText) {
            $transText .= $anoText;
          }
        }
        break;//only get the first
      }
      if ($transText) {
        $transText = processTranslationText($transText,$entTag);
      }
    }
  }
  return $transText;
}

/**
* returns the Html for a word's translation or nothing
*
* @param object $entity Compound or Token
*
* @return string Html representing the translation of $entity or empty string
*/
function getWordTransHtml($entity) {
  $entGID = $entity->getGlobalID();
  $prefix = substr($entGID,0,3);
  $entID = substr($entGID,4);
  $entTag = $prefix.$entID;
  $wordHtml = "";
  $tokIDs = null;

  if ($entity && $prefix == 'cmp' && count($entity->getTokenIDs())) {
    $wordHtml = getEntityTranslation($entity);
    if ($wordHtml) { // has translation
      return $wordHtml;
    } else if (count($entity->getTokenIDs())) {
      $tokIDs = $entity->getTokenIDs();
    }
  } else if ($entity && $prefix == 'tok'){
    $tokIDs = array($entID);
  } else {
    error_log("err, rendering word translation invalid GID $entGID");
  }

  if ($tokIDs) {
    //for each token in word
    $tokCnt = count($tokIDs);
    for($i =0; $i < $tokCnt; $i++) {
      $tokID = $tokIDs[$i];
      $token = new Token($tokID);
      $transRFT = getEntityTranslation($token);
      if ($transRFT) { // has translation
        $wordHtml .= $transRFT;
      }
    }
  }
  return $wordHtml;
}

/**
* finds the list of annotation types for a nested sequences
*
* recursively calls itself for subsequences
*
* @param object $sequence has a structure type containing other structures or entities
*
* @return int array of term ids for annotationtypes
*/
function getSequenceAnnotationTypes($sequence) {
  global $edition,$annoTypeIDs;
  $seqEntGIDs = $sequence->getEntityIDs();
  if (!$seqEntGIDs || count($seqEntGIDs) == 0) {
    error_log("warn, Found empty structural sequence element seq".$sequence->getID()." for edition ".$edition->getDescription()." id=".$edition->getID());
    return;
  }
  if ( $linkedAnoIDsByType = $sequence->getLinkedAnnotationsByType()) {
    $annoTypeIDs = array_unique(array_merge($annoTypeIDs,array_keys($linkedAnoIDsByType)));
  }
  foreach ($seqEntGIDs as $entGID) {
    $prefix = substr($entGID,0,3);
    $entID = substr($entGID,4);
    if ($prefix == 'seq') {
      $subSequence = new Sequence($entID);
      if (!$subSequence || $subSequence->hasError()) {//no sequence or unavailable so warn
        error_log("Warning inaccessible sub-sequence id $entGID skipped.");
      } else {
        getSequenceAnnotationTypes($subSequence);
      }
    }
  }
}

/**
* finds the list of annotation types for a nested sequences
*
* recursively calls itself for subsequences
*
* @param object $sequence has a structure type containing other structures or entities
*
* @return int array of term ids for annotationtypes
*/
function getEditionAnnotationTypes($ednIDs) {
  global $edition,$annoTypeIDs;
  $seqEntGIDs = array();
  $annoTypeIDs = array();
  foreach ($ednIDs as $ednID) {
    $edition = new Edition($ednID);
    if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
      array_push($warnings,"Warning need valid accessible edition id $ednID.");
      continue;
    } else {
      $edSeqs = $edition->getSequences(true);
      $textAnalysisSeq = null;
      $textPhysicalSeq = null;
      if ($edSeqs && $edSeqs->getCount() > 0) {
        foreach ($edSeqs as $edSequence) {
          $seqType = $edSequence->getType();
          if (!$textAnalysisSeq && $seqType == "Analysis"){//warning!!!! term dependency
            $textAnalysisSeq = $edSequence;
          }
          if (!$textPhysicalSeq && $seqType == "TextPhysical"){//warning!!!! term dependency
            $textPhysicalSeq = $edSequence;
          }
        }
        if ($textAnalysisSeq && $textAnalysisSeq->getEntityIDs() && count($textAnalysisSeq->getEntityIDs()) > 0) {
          getSequenceAnnotationTypes($textAnalysisSeq);
        }
        if ($textPhysicalSeq && $textPhysicalSeq->getEntityIDs() && count($textPhysicalSeq->getEntityIDs()) > 0) {
          getSequenceAnnotationTypes($textPhysicalSeq);
        }
      }
    }
  }
  return $annoTypeIDs;
}

/**
* returns the html for a nested structure translations
*
* recursively calls itself for substructures without adding boundary HTML
*
* @param object $sequence has a structure type containing other structures or entities
* @param boolean $addBoundaryHtml determines if boundary HTML element is added, default false
*/
function getStructTransHtml($sequence, $addBoundaryHtml = false) {
  global $edition, $preMarker, $seqBoundaryMarkerHtmlLookup, $curStructHeaderbyLevel;
  $seqEntGIDs = array();
  $structureHtml = "";
  if ($sequence) {
    $seqEntGIDs = $sequence->getEntityIDs();
    $seqType = $sequence->getType();
    $seqTag = $sequence->getEntityTag();
  }
  if (!$seqEntGIDs || count($seqEntGIDs) == 0) {
    error_log("warn, Found empty structural sequence element seq".$sequence->getID()." for edition ".$edition->getDescription()." id=".$edition->getID());
    return;
  }
  //check for boundary entry for this seqTag
  if ($addBoundaryHtml && array_key_exists($seqTag,$seqBoundaryMarkerHtmlLookup)) {
    $structureHtml .= $seqBoundaryMarkerHtmlLookup[$seqTag];
  }
  $seqLabel = $sequence->getLabel();
  $seqSup = $sequence->getSuperScript();
  //calc level
  $curStructLevel = count($curStructHeaderbyLevel);
  if($seqSup) {
    $seqLevels = explode('.',$seqSup);
    $level = count($seqLevels);
  } else {
    $level = $curStructLevel + 1;
  }
  //if level is less than current structural then close section divs to current level and remove level headers from $curStructHeaderbyLevel
  while ( $level < $curStructLevel) {
    unset($curStructHeaderbyLevel[$curStructLevel]);
    $structureHtml .= "</div>";
    --$curStructLevel;
  }
  if ($level == $curStructLevel) {
    //if seqSup is equivalent to current level sup then skip header
    if ($curStructHeaderbyLevel[$curStructLevel]['sup'] == str_replace("(","",str_replace(")","",$seqSup))) {
      $label = null;
    } else {//sibling so signal output of section Header
      $label = (($seqSup && $seqType !== "Section")?$seqSup.($seqLabel?" ".$seqLabel:""):($seqLabel?$seqLabel:""));
    }
    //close section div
    $structureHtml .= "</div>";
  } else {// new struct is deeper case
    $label = (($seqSup && $seqType !== "Section")?$seqSup.($seqLabel?" ".$seqLabel:""):($seqLabel?$seqLabel:""));
  }
  if ($label) {//output header div and toc info
    $structureHtml .= '<div id="'.$preMarker.$seqTag.'" class="secHeader level'.$level.' '.$seqType.' '.$seqTag.'">'.$label.'</div>';
    switch ($seqType) {
      case "Chapter": //warning term dependency
      case "Section": //warning term dependency
        $curStructHeaderbyLevel[$level] = array('sup'=>$seqSup,'label'=> $seqLabel);
        break;
    }
  }
  //open structure div
  $structureHtml .= '<div class="section level'.$level.' '.$seqTag.' '.$seqType.' '.$seqTag.'">';
  $structTransHtml = getEntityTranslation($sequence);
  if ($structTransHtml) {
    $structureHtml .= $structTransHtml;
  } else {
    foreach ($seqEntGIDs as $entGID) {
      $prefix = substr($entGID,0,3);
      $entID = substr($entGID,4);
      if ($prefix == 'seq') {
        $subSequence = new Sequence($entID);
        if (!$subSequence || $subSequence->hasError()) {//no sequence or unavailable so warn
          error_log("Warning inaccessible sub-sequence id $entGID skipped.");
        } else {
          $structureHtml .= getStructTransHtml($subSequence);//todo check if true for boundary marker on multi-part text
        }
      } else if ($prefix == 'cmp' || $prefix == 'tok' ) {
        if ($prefix == 'cmp') {
          $entity = new Compound($entID);
        } else {
          $entity = new Token($entID);
        }
        if (!$entity || $entity->hasError()) {//no word or unavailable so warn
          error_log("Warning inaccessible word id $entGID skipped.");
        } else {
          $structureHtml .= getWordTransHtml($entity);
        }
      }else{
        error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id=".$edition->getID());
        continue;
      }
    }
  }
  $structureHtml .= '</div>';
  return $structureHtml;
}

/**
* gets multi edition header structure html
*
* calculates the HTML for displaying multi edition buttons ordered published  followed by research
*
* @param int array $ednIDs identifies the editions to calculate
*
* @returns string html representing header information to attach to the edition textViewer header
*/
function getMultiEditionHeaderHtml($ednIDs) {
  $publishedEditionsHTML = array();
  $pubSortKeys = array();
  $researchEditionsHTML = array();
  $researchSortKeys = array();
  $defaultEditionFound = false;
  $defaultEditionHTML = null;
  $eIndex = 0;
  foreach ($ednIDs as $ednID) {//accumulate in order all subsequence for text, text physical and analysis
    $edition = new Edition($ednID);
    if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
      error_log("Warning edition id $ednID not accessible. Skipping.".
        ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
      continue;
    } else {
      if (!$defaultEditionFound && $edition->getScratchProperty('default')) {//if the edition is default show first
        $defaultEditionFound = true;
        $ord = "default";
      } else {
        $ord = $edition->getScratchProperty('ordinal');
      }
      $date = "";
      $descr = $edition->getDescription();
      $hdrText = $descr;
      $atbIDs = $edition->getAttributionIDs();
      if ($atbIDs && count($atbIDs) > 0) {
        $attribution = new Attribution($atbIDs[0]);
        if ($attribution && !$attribution->hasError()) {
          $title = $attribution->getTitle();
          $detail = $attribution->getDetail();
          if ($detail) {
            $descr .= " ($title,$detail)";
          }
          if (preg_match("/[^\d](\d\d\d\d)[^\d]?/",$title,$matches)) {
            $date = $matches[1];
            $hdrText = $title;
          }
        }
      }
      if ($ord == "default") {
        $defaultEditionHTML = "<button id=\"edn$ednID\" class=\"textEdnButton research defaultedition\" title=\"$descr\">$hdrText</button>";
      } else {
        if ($ord && is_numeric($ord)) {
          $sort = intval($ord);
//        } else if ($date) {
//          $sort = $date;
        } else {
          $sort = $eIndex;
//          $sort = intval($ednID)*10000;
        }
        if ($edition->isResearchEdition()) {
          $researchEditionsHTML[$ednID] = "<button id=\"edn$ednID\" class=\"textEdnButton research\" title=\"$descr\">$hdrText</button>";
          $researchSortKeys[$sort] = $ednID;
        } else { // published ??
          $publishedEditionsHTML[$ednID] = "<button id=\"edn$ednID\" class=\"textEdnButton published\" title=\"$descr\">$hdrText</button>";
          $pubSortKeys[$sort] = $ednID;
        }
      }
      $eIndex++;
    }
  }
  //create headerDiv Html
  $html = "<div id=\"multiEditionHeaderMenu\">";
  if ($defaultEditionHTML) {
    $html .= $defaultEditionHTML;
  }
  if (count($pubSortKeys)) {
    ksort($pubSortKeys,SORT_NUMERIC);
    foreach ($pubSortKeys as $sort=>$ednID) {
      $html .= $publishedEditionsHTML[$ednID];
    }
  }
  if (count($researchSortKeys)) {
    ksort($researchSortKeys,SORT_NUMERIC);
    foreach ($researchSortKeys as $sort=>$ednID) {
      $html .= $researchEditionsHTML[$ednID];
    }
  }
  $html .= "</div>";
  return $html;
}

/**
* gets the editions Structural Translation/Chaya html
*
* calculates the structure view of this edition's translation/chaya annotations stopping at depth frist node
* with annotation, also process embedded physical line markers and footnote markers
*
* @param int array $ednIDs identifies the editions to calculate
* @param int $annoTypeID identifies the type of translation to calculate
* @param boolean $forceRecalc indicating whether to ignore cached values
*
* @returns mixed object with a string representing the html and a footnote lookup table
*/
function getEditionsStructuralTranslationHtml($ednIDs, $annoTypeID = null, $forceRecalc = false) {
  global $edition,$lineLabel2SeqTagMap, $tfnRefToTfnText, $tfnCnt, $translationTypeID,
  $seqBoundaryMarkerHtmlLookup, $curStructHeaderbyLevel,$preMarker, $fnPreMarker;
  $seqBoundaryMarkerHtmlLookup = array();
  $curStructHeaderbyLevel = array();
  $lineLabel2SeqTagMap = array();
  $tfnRefToTfnText = array();
  $warnings = array();
  $tfnCnt = 0;
  $html = "";
  $physicalLineSeqIDs = array();
  $textDivSeqIDs = array();
  $analysisSeqIDs = array();
  $sourceNameLookup = array();
  $isFirstEdn = true;
  $jsonCache = null;
  if (count($ednIDs) == 1) {
    if(USEVIEWERCACHING) {
      if (!$annoTypeID || !Entity::getTermFromID($annoTypeID)) {
        $annoTypeID = Entity::getIDofTermParentLabel('Translation-AnnotationType');//warning!!!! term dependency
        $transType = "Translation";
      } else {
        $transType = Entity::getTermFromID($annoTypeID);
      }
      $cacheKey = "edn".$ednIDs[0].$transType."HTML";
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->getID() && !$jsonCache->hasError() && !$jsonCache->isDirty() && !$forceRecalc) {
        $cachedEditionData = json_decode($jsonCache->getJsonString(),true);
        //set dependent globals before returning html string
        $tfnRefToTfnText = $cachedEditionData['tfnRefToTfnText'];
        //return html
        return json_encode($cachedEditionData['transHtml']);
      } else if ($jsonCache->hasError() || !$jsonCache->getID()) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        if (!$edition) {
          $edition = new Edition($ednIDs[0]);
        }
        if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
          array_push($warnings,"Warning need valid accessible edition id ".$ednIDs[0].". Skipping.".
            ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
          $jsonCache = null;
        } else {
          $jsonCache->setVisibilityIDs($edition->getVisibilityIDs());
          $jsonCache->setOwnerID($edition->getOwnerID());
        }
      }
    }
  }
  // this will handle cases where a text is split across multiple text-edition entities where the first
  // is used to determine the title
  // for the single text-edition case the $ednIDs will have a single ednID
  // for the text-multi-edition case this code will be called once for each edition with the same text
  foreach ($ednIDs as $ednID) {//accumulate in order all subsequences for text, text physical and analysis
    $edition = new Edition($ednID);
    if (!$edition || $edition->hasError() || $edition->getID() != $ednID) {//no edition or unavailable so warn
      array_push($warnings,"Warning need valid accessible edition id $ednID. Skipping.".
        ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
    } else {
      $edSeqIDs = $edition->getSequenceIDs();
      if (count($edSeqIDs) == 0) {//no sequences so warn and skip
        array_push($warnings,"Warning edition id $ednID has no sequences. Skipping.");
        continue;
      }
      $seqPhys = $seqText = $textAnalysisSeq = null;
      foreach ($edSeqIDs as $edSeqID) {
        $edSequence = new Sequence($edSeqID);
        if (!$edSequence || $edSequence->hasError()) {//no sequence or unavailable so warn
          array_push($warnings,"Warning unable to load edition $ednID's sequence seq:$edSeqID. Skipping.".
            ($edSequence->hasError()?" Error: ".join(",",$edSequence->getErrors()):""));
        } else {
          $seqType = $edSequence->getType();
          $componentIDs = $edSequence->getEntityIDs();
          if (!$componentIDs || count($componentIDs) == 0) {//no sequences so warn and skip
            array_push($warnings,"Warning edition edn:$ednID's sequence seq:$edSeqID has no components. Skipping.");
            continue;
          }
          if (!$seqPhys && $seqType == "TextPhysical"){//warning!!!! term dependency
            $physicalLineSeqIDs = array_merge($physicalLineSeqIDs,$componentIDs);
          } else if (!$seqText && $seqType == "Text"){//warning!!!! term dependency
            $textDivSeqIDs = array_merge($textDivSeqIDs,$componentIDs);
          } else if (!$textAnalysisSeq && $seqType == "Analysis"){//warning!!!! term dependency
            if ($isFirstEdn) {
              $isFirstEdn = false;
            } else {//add entry to boundary lookup
              $entTag = str_replace(':','',$componentIDs[0]);
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
            $analysisSeqIDs = array_merge($analysisSeqIDs,$componentIDs);
          } else {//ignoring sequence so warn
            array_push($warnings,"Warning no code to handle sequence seq:$edSeqID of type $seqType. Skipping.");
          }
        }
      }
      $attributions = $edition->getAttributions(true);
      if ($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
        foreach ($attributions as $attribution) {
          $atbID = $attribution->getID();
          $title = $attribution->getTitle();
          $detail = $attribution->getDetail();
          if (!array_key_exists($atbID,$sourceNameLookup)) {
            $sourceNameLookup[$atbID] = $title;
          }
        }
      }
    }//endelse valid edtion
  }// end foreach ednID

  if (count($analysisSeqIDs) == 0) {
    array_push($warnings,"Warning no structural analysis found for edition id ".$ednIDs[0].". Skipping.");
  } else {//process analysis
    //calculate  post grapheme id to physical line label map
    if (count($physicalLineSeqIDs) > 0) {
      foreach ($physicalLineSeqIDs as $physicalLineSeqGID) {
        $physicalLineSeq = new Sequence(substr($physicalLineSeqGID,4));
        if (!$physicalLineSeq || $physicalLineSeq->hasError()) {//no $physicalLineSeqIDsequence or unavailable so warn
          array_push($warnings,"Warning unable to load edition ".$ednIDs[0]."'s physicalline sequence seq:$physicalLineSeqGID. Skipping.".
            ($physicalLineSeq->hasError()?" Error: ".join(",",$physicalLineSeq->getErrors()):""));
        } else {
          $label = $physicalLineSeq->getLabel();
          $seqTag = 'seq'.$physicalLineSeq->getID();
          if (!$label) {
            $label = $seqTag;
          }
          $lineLabel2SeqTagMap[$label] = $seqTag;
        }
      }
    }
    if ($annoTypeID) {
      $translationTypeID = $annoTypeID;
      $transType = (Entity::getTermFromID($annoTypeID) != "Translation"?Entity::getTermFromID($annoTypeID):"Generic");//warning!!!! term dependency
      $preMarker = substr(strtolower($transType),0,1);
      $fnPreMarker = $preMarker.'fn';
    } else {
      $transType = "Generic";
      $translationTypeID = Entity::getIDofTermParentLabel('Translation-AnnotationType');//warning!!!! term dependency
      $preMarker = 't';
      $fnPreMarker = 'tfn';
    }

    $html = "";
    $tokCnt = 0;
    //start to calculate HTML using each entity of the analysis container
    foreach ($analysisSeqIDs as $entGID) {
      $prefix = substr($entGID,0,3);
      $entID = substr($entGID,4);
      if ($prefix == 'seq') {
        $subSequence = new Sequence($entID);
        if (!$subSequence || $subSequence->hasError()) {//no sequence or unavailable so warn
          error_log("warn, Warning inaccessible sub-sequence id $entID skipped.");
        } else {
          $html .= getStructTransHtml($subSequence,true);
        }
      } else if ($prefix == 'cmp' || $prefix == 'tok' ) {
        if ($prefix == 'cmp') {
          $entity = new Compound($entID);
        } else {
          $entity = new Token($entID);
        }
        if (!$entity || $entity->hasError()) {//no word or unavailable so warn
          error_log("warn, Warning inaccessible word id $entGID skipped.");
        } else {
          $html .= getWordTransHtml($entity,false);
        }
      }else{
        error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id=".$edition->getID());
        continue;
      }
    }
    //check for termination divs by inspection of $curStructHeaderbyLevel
    if (count($curStructHeaderbyLevel) > 0) {
      foreach (array_keys($curStructHeaderbyLevel) as $key) {
        $html .= "</div>";
      }
    }
  }
  $sourceHtml = "";
  if ($sourceNameLookup && count($sourceNameLookup) > 0) {
    $isFrist = true;
    $sourceHtml = "<div class=\"source edn1\"><span class=\"sourcelabel\">Source: </span>";
    foreach ($sourceNameLookup as $atbID => $title) {
      $sources = '';
      foreach ($sourceNameLookup as $atbID => $title) {
        $titlez = str_replace('_',' ',$title);
        $sources .= "<span class=\"sourceitem atb$atbID\">$titlez</span>, ";
      }
      $sourceHtml .= trim($sources,', ')."</div>";
    }
    $sourceHtml .= "</span></div>";
  }
  $html .= $sourceHtml;
  if(USEVIEWERCACHING && $jsonCache) {
    $cachedEditionData = array();
    //store dependent globals used to calc html
    $cachedEditionData['tfnRefToTfnText'] = $tfnRefToTfnText;
    //save html
    $cachedEditionData['transHtml'] = $html;
    $jsonCache->setJsonString(json_encode($cachedEditionData));
    $jsonCache->clearDirtyBit();
    $jsonCache->save();
  }
  return json_encode($html);
}

/**
* returns footnotes of an entity as Html fragment
*
* @param object $entity that can be annotated
*/
function getEntityBoundaryHtml($entity,$classList = "") {
  global $typeIDs;
  $entTag = $entity->getEntityTag();
  if (!$classList) {
    $classList = "entityBoundary $entTag";
  }
  $boundaryHtml = "<div class=\"$classList\">";
  if (method_exists($entity,'getDescription')) {
    $boundaryHtml .= $entity->getDescription();
  }else if (method_exists($entity,'getTitle')) {
    $boundaryHtml .= $entity->getTitle();
  }else if (method_exists($entity,'getLabel')) {
    $boundaryHtml .= $entity->getLabel();
  }
  if ($linkedAnoIDsByType = $entity->getLinkedAnnotationsByType()) {
    foreach ($typeIDs as $typeID) {
      if (array_key_exists($typeID,$linkedAnoIDsByType)) {
        $type = strtolower( Entity::getTermFromID($typeID));
        $typeTag = "trm".$typeID;
        foreach ($linkedAnoIDsByType[$typeID] as $anoID) {
          $annotation = new Annotation($anoID);
          $anoText = $annotation->getText();
          if ($anoText) {
            $fnTag = "ano".$anoID;
            $boundaryHtml .= "<span class=\"boundaryNote $entTag $type $typeTag $fnTag\" >$anoText</sup>";
          }
        }
      }
    }
  }
  $boundaryHtml .= "</div>";
  return $boundaryHtml;
}

/**
* returns footnotes of an entity as Html fragment
*
* @param object $entity that can be annotated
*/
function getSubEntityFootnotesHtml($entity, $refresh = false, $drillDown = true) {
//  global $fnRefTofnText;
  $fnHtml = "";
  $fnTextByAnoTag = array();
  if (!method_exists($entity,"getComponents")) {
    return array('fnHtml' => "", 'fnTextByAnoTag' => array());
  }
  $subEntities = $entity->getComponents(true);
  if ($subEntities->getCount() == 0) {
    return array('fnHtml' => "", 'fnTextByAnoTag' => array());
  }
  foreach ($subEntities as $subEntity){
    $fnInfo = getEntityFootnotesHtml($subEntity, $refresh, $drillDown);
    $fnHtml .= $fnInfo['fnHtml'];
    $fnTextByAnoTag = array_merge($fnTextByAnoTag,$fnInfo['fnTextByAnoTag']);
  }
  return array('fnHtml' => $fnHtml, 'fnTextByAnoTag' => $fnTextByAnoTag);
}

/**
* returns footnotes of an entity as Html fragment
*
* @param object $entity that can be annotated
*/
function getEntityFootnotesHtml($entity, $refresh = false, $drillDown = true) {
  global $typeIDs;
  $fnInfo = getEntityFootnoteInfo($entity, $typeIDs, $refresh);
  if (is_null($fnInfo)) {
    if ($entity->getEntityTypeCode() == "cmp" && $drillDown && method_exists($entity,"getComponents")) {
      return getSubEntityFootnotesHtml($entity, $refresh);
    }
      return array('fnHtml' => "", 'fnTextByAnoTag' => array());
  }
  return $fnInfo;
}

/**
* adds entity boundaries to the polygons lookup and entity scrolltop to the Pos Lookup
*
* @param object $entity a Token or Compound
*
*/
function addWordToEntityLookups($entity, $refresh = false) {
  global $blnPosByEntTag, $polysByBlnTagTokCmpTag;
  $prefix = $entity->getEntityTypeCode();
  if ($prefix == "tok" || $prefix == "cmp" ) {
    // call update baseline info code if refresh is high enough
    if (is_numeric($refresh) && $refresh > 1) {
      $entity->updateBaselineInfo(true);
    }
    $polysByBln = $entity->getBaselinePolygons();// call once as updates scrolltop at the same time
    if ($polysByBln && count($polysByBln) > 0) {
      foreach( $polysByBln as $blnTag => $polygons){
        $polysByBlnTagTokCmpTag[$blnTag][$entity->getEntityTag()] = $polygons;
      }
    }
    if ($scrollTopInfo = $entity->getScrollTopInfo()) {
      $blnPosByEntTag['word'][$entity->getEntityTag()] = $scrollTopInfo;
    }
  }
}

/**
* returns the Html for a word
*
* @param object $entity Compound or Token
* @param boolean $isLastStructureWord
* @param object or null $nextToken
* @param boolean $refresh indicating the need to refresh cached information
* @param string $ctxClass is added to the class attribute of the html node
* @return string Html representing the $entity
*/
function getWordHtml($entity, $isLastStructureWord, $nextToken = null, $refresh = false, $ctxClass = '') {
  global $prevTCMS, $graID2LineHtmlMarkerlMap, $graID2PSnFMarkerMap, $fnRefTofnText, $wordCnt, $graID2WordGID;
  $footnoteHtml = "";
  $entGID = $entity->getGlobalID();
  $prefix = substr($entGID,0,3);
  $entID = substr($entGID,4);
  $entTag = $prefix.$entID;
  $wordHtml = "";
  $nextTCMS = "";
  $tcms = "";
  $prevGraIsVowelCarrier = false;
  $previousA = null;
  $tokIDs = null;

  if ($entity && $prefix == 'cmp' && count($entity->getTokenIDs())) {
    $tokIDs = $entity->getTokenIDs();
  } else if ($entity && $prefix == 'tok'){
    $tokIDs = array($entID);
  } else {
    error_log("err, rendering word html invalid GID $entGID");
    return $wordHtml;
  }
  if ($tokIDs) {
    ++$wordCnt;
    //for each token in word
    $tokCnt = count($tokIDs);
//    $prevGraID = null;
    for($i =0; $i < $tokCnt; $i++) {
      $tokID = $tokIDs[$i];
      $token = new Token($tokID);
      $graIDs = $token->getGraphemeIDs();
      //new code ---- add code to get  linemarker from token scratch, possible for long words to cross multiple lines
      //$graID2LineHtmlMarkerlMap = $token->getScratchProperty("htmlLineMarkers");
      $lastT = ($i == -1 + $tokCnt);
      //for each grapheme in token
      $graCnt = count($graIDs);
      if ($graCnt && $i == 0) {
        $firstT = true;
        //open word span
        list($tdSeqTag,$wordTag,$sandhiWordPos) = $graID2WordGID[$graIDs[0]];
        //$wordHtml .= '<span class="grpTok '.($ctxClass?$ctxClass.' ':'').$entTag.' ord'.$wordCnt.'">';
        $wordHtml .= '<span class="grpTok '.($sandhiWordPos?"sandhi$sandhiWordPos ":'').($tdSeqTag?$tdSeqTag.' ':'').$wordTag.' ord'.$wordCnt.'">';
      }
      for($j=0; $j<$graCnt; $j++) {
        $graID = $graIDs[$j];
        $grapheme = new Grapheme($graID);
        if ($j+1 == $graCnt && $sandhiWordPos == 1) { // last grapheme of this token and sandhi so skip it
          continue;
        }
        if (!$grapheme) {
          error_log("err,calculating word html and grapheme not available for graID $graID");
          $prevGraIsVowelCarrier = false;
          continue;
        }
        if ($grapheme->getValue() == "ʔ") {
          $prevGraIsVowelCarrier = true;
          continue;
        }
        $firstG = ($j==0 || $j==1 && $prevGraIsVowelCarrier);
        $lastG = ($j == -1 + $graCnt);
        //check for TCM transition brackets
        $tcms = $grapheme->getTextCriticalMark();
        $postTCMBrackets = "";
        $preTCMBrackets = "";
        if ($prevTCMS != $tcms) {
          list($postTCMBrackets,$preTCMBrackets) = getTCMTransitionBrackets($prevTCMS,$tcms,true);
        }
        if ($postTCMBrackets && !($i == 0 && $firstG)) {
          $wordHtml .= $postTCMBrackets;
        }
        //get part-side-n-fragment markers
        if (INLINEPARTSIDELABEL && array_key_exists($graID,$graID2PSnFMarkerMap) && array_key_exists('sideMarker',$graID2PSnFMarkerMap[$graID])) {
          $sideMarker = $graID2PSnFMarkerMap[$graID]['sideMarker'];
        } else {
          $sideMarker = '';
        }
        if ($graID && array_key_exists($graID,$graID2LineHtmlMarkerlMap)) {
          if ( $i == 0 && $firstG) {
            $wordHtml = $sideMarker.$graID2LineHtmlMarkerlMap[$graID].$wordHtml;
          } else {
            $wordHtml .= $sideMarker.$graID2LineHtmlMarkerlMap[$graID];
          }
          $prevTCMS = "";//at a new physical line so reset TCM
        }

        if ($footnoteHtml) {
          $wordHtml .= $footnoteHtml;
          $footnoteHtml = "";
        }
        if ($preTCMBrackets) {
          $wordHtml .= $preTCMBrackets;
        }
        //add grapheme
        $graTemp = $grapheme->getValue();
        if ($prevGraIsVowelCarrier && $previousA && ($prevTCMS == $tcms || (!$prevTCMS|| $prevTCMS == "S") && (!$tcms|| $tcms == "S"))) {
          if ($graTemp == 'i') {
            $graTemp = "ï";
          }else if ($graTemp == 'u') {
            $graTemp = "ü";
          }
        }
        $prevTCMS = $tcms;
        $wordHtml .= $graTemp;
//        $prevGraID = $graID;
        if (strtolower($graTemp) == "a") {
          $previousA = true;
        } else {
          $previousA = false;
        }
        $prevGraIsVowelCarrier = false;
      }//end for graphIDs
      $fnInfo = getEntityFootnotesHtml($token, $refresh);
      $fnHtml = $fnInfo['fnHtml'];
      $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
      if ($fnHtml) {
        $footnoteHtml .= $fnHtml;
        $fnRefTofnText = array_merge($fnRefTofnText,$fnTextByAnoTag);
      }
    }//end for token IDs
    if ($nextToken) {//find tcm for first grapheme of next token to check for closing brackets
      $nextGraIDs = $nextToken->getGraphemeIDs();
      if (count($nextGraIDs) > 0) {
        $nextGrapheme = new Grapheme($nextGraIDs[0]);
        $nextTCMS = $nextGrapheme->getTextCriticalMark();
        if ($nextTCMS != $tcms) {
          $postTCMBrackets = "";
          $preTCMBrackets = "";
          list($postTCMBrackets,$preTCMBrackets) = getTCMTransitionBrackets($tcms,$nextTCMS,true);
          $wordHtml .= $postTCMBrackets;
        }
      }
    }
    if ($isLastStructureWord && $prevTCMS && $prevTCMS != "S") {//close off any TCM
      $tcmBrackets = getTCMTransitionBrackets($prevTCMS,"S");//reduce to S
      $prevTCMS = "";//reset since we closed off TCMs for the structure.
      //This will ensure next structures output will have opening TCMs
      if ($tcmBrackets) {
        $wordHtml .= $tcmBrackets;
      }
    }
    if ($prefix == "cmp") {//end of compound so add cmp entity footnotes
      $fnInfo = getEntityFootnotesHtml($entity, $refresh, false);
      $fnHtml = $fnInfo['fnHtml'];
      $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
      if ($fnHtml) {
        $footnoteHtml .= $fnHtml;
        $fnRefTofnText = array_merge($fnRefTofnText,$fnTextByAnoTag);
      }
    }
    if ($footnoteHtml) {
      $wordHtml .= $footnoteHtml;
      $footnoteHtml = "";
    }
    $wordHtml = preg_replace('/\/\/\//',"",$wordHtml); // remove edge indicator
    $wordHtml = preg_replace('/_+/',"_",$wordHtml); // multple missing consonants
    $wordHtml = preg_replace('/_/',".",$wordHtml); // multple missing consonants
    $wordHtml .= "</span>";
    //      $wordRTF = preg_replace('/\.\./',".",$wordRTF); // multple missing consonants
  }
  return $wordHtml;
}

/**
* returns the html for a nested structure
*
* recursively calls itself for substructures
*
* @param object $sequence has a structure type containing other structures or entities
* @param int $level indicate the level of nest in the structural hierarchy
*/
function getStructHTML($sequence, $refresh = false, $addBoundaryHtml = false) {
  global $edition, $editionTOCHtml, $seqBoundaryMarkerHtmlLookup, $fnRefTofnText,$blnPosByEntTag, $curStructHeaderbyLevel;
  // check sequence for cached html by edition keying
  $structureHtml = $sequence->getScratchProperty("edn".$edition->getID()."structHtml");
  if (defined("USEVIEWERCACHING") && !USEVIEWERCACHING || $refresh || !$structureHtml ) {
    $structureHtml = "";
    if ($sequence) {
      $seqEntGIDs = $sequence->getEntityIDs();
      $seqType = $sequence->getType();
      $seqTag = $sequence->getEntityTag();
    }
    if (!$seqEntGIDs || count($seqEntGIDs) == 0) {
      error_log("warn, Found empty structural sequence element seq".$sequence->getID()." for edition ".$edition->getDescription()." id=".$edition->getID());
      return;
    }
    //check for boundary entry for this seqTag
    if ($addBoundaryHtml && array_key_exists($seqTag,$seqBoundaryMarkerHtmlLookup)) {
      $structureHtml .= $seqBoundaryMarkerHtmlLookup[$seqTag];
    }
    $seqLabel = $sequence->getLabel();
    $seqSup = $sequence->getSuperScript();
    //calc level
    $curStructLevel = count($curStructHeaderbyLevel);
    if($seqSup) {
      $seqLevels = explode('.',$seqSup);
      $level = count($seqLevels);
    } else {
      $level = $curStructLevel + 1;
    }
    //if level is less than current structural then close section divs to current level and remove level headers from $curStructHeaderbyLevel
    while ( $level < $curStructLevel) {
      unset($curStructHeaderbyLevel[$curStructLevel]);
      $structureHtml .= "</div>";
      --$curStructLevel;
    }
    if ($level == $curStructLevel) {
      //if seqSup is equivalent to current level sup then skip header
      if ($curStructHeaderbyLevel[$curStructLevel]['sup'] == str_replace("(","",str_replace(")","",$seqSup))) {
        $label = null;
      } else {//sibling so signal output of section Header
        $label = (($seqSup && $seqType !== "Section")?$seqSup.($seqLabel?" ".$seqLabel:""):($seqLabel?$seqLabel:""));
      }
      //close section div
      $structureHtml .= "</div>";
    } else {// new struct is deeper case
      $label = (($seqSup && $seqType !== "Section")?$seqSup.($seqLabel?" ".$seqLabel:""):($seqLabel?$seqLabel:""));
    }
    if ($label) {//output header div and toc info
      if ($seqType != "Pāda" && $seqType != "Item") {//warning!!!! term dependency
        $structureHtml .= '<div id="'.$seqTag.'" class="secHeader level'.$level.' '.$seqType.' '.$seqTag.'">'.$label;
        $fnInfo = getEntityFootnotesHtml($sequence, $refresh);
        $fnHtml = $fnInfo['fnHtml'];
        $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
        if ($fnHtml) {
          $structureHtml .= $fnHtml;
          $fnRefTofnText = array_merge($fnRefTofnText,$fnTextByAnoTag);
        }
        $structureHtml .= '</div>';
      }
      if ($seqType == "Chapter" || $seqType == "Section" || 
          ($level == 1 && ($seqType == "Stanza" || $seqType == "List"))) {//warning term dependency
        $editionTOCHtml .= '<div id="toc'.$seqTag.'" class="tocEntry level'.$level.' '.$seqType.' '.$seqTag.'">'.$label.'</div>';
        $curStructHeaderbyLevel[$level] = array('sup'=>$seqSup,'label'=> $seqLabel);
      }
    }
    //open structure div
    $structureHtml .= '<div class="section level'.$level.' '.$seqTag.' '.$seqType.'">';
    //output inline markers
    if ($seqType == "Pāda") {//warning!!!! term dependency
      if ($seqLabel || $seqSup) {
        $structureHtml .= '<div class="secMarker level'.$level.' '.$seqTag.' '.$seqType.'">'.$label.'</div>';
      }
    } else if ($seqType == "Item") {//warning!!!! term dependency
      if ($seqLabel || $seqSup) {
        $structureHtml .= '<div class="itemBullet level'.$level.' '.$seqTag.' '.$seqType.'">'.$label.'</div>';
      }
    }

    $cntGID = count($seqEntGIDs);
    if ($cntGID > 0) {
      //wrapp prose for pada and item sequences
      if ($seqType == "Pāda" || $seqType == "Item") {//warning!!!! term dependency
        $structureHtml .= '<div class="prose level'.$level.' '.$seqTag.' '.$seqType.'">';
      }
      for ($i = 0; $i < $cntGID; $i++) {
        $entGID = $seqEntGIDs[$i];
        $prefix = substr($entGID,0,3);
        $entID = substr($entGID,4);
        $nextEntGID = $i+1<$cntGID?$seqEntGIDs[$i+1]:null;
        $nextToken = null;
        if ( $nextEntGID ) {
          switch (substr($nextEntGID,0,3)) {
            case 'cmp':
              $nextToken = new Compound(substr($nextEntGID,4));
              if (!$nextToken || $nextToken->hasError()) {//no sequence or unavailable so warn
                error_log("Warning inaccessible entity id $nextEntGID skipped.");
                $nextToken = null;
                break;
              } else {
                $nextEntGID = $nextToken->getTokenIDs();
                if ($nextEntGID && count($nextEntGID)) {
                  $nextEntGID = "tok:".$nextEntGID[0];
                } else {
                  error_log("Warning inaccessible entity id ".$nextToken->getGlobalID()." skipped.");
                  $nextToken = null;
                  break;
                }
              }
            case 'tok':
              $nextToken = new Token(substr($nextEntGID,4));
              if (!$nextToken || $nextToken->hasError()) {//no sequence or unavailable so warn
                error_log("Warning inaccessible entity id $nextEntGID skipped.");
                $nextToken = null;
                break;
              }
          }
        }
        $entTag = null;
        if ($prefix == 'seq') {
          $subSequence = new Sequence($entID);
          if (!$subSequence || $subSequence->getID() != $entID || $subSequence->hasError()) {//no sequence or unavailable so warn
            error_log("Warning inaccessible sub-sequence id $entID skipped.");
            continue;
          } else {
            $structureHtml .= getStructHTML($subSequence,$refresh);//todo verify if need to add boundary for sub structure
            $entTag = $subSequence->getEntityTag();
            $entType = 'construct';
          }
        } else if ($prefix == 'cmp' || $prefix == 'tok' ) {
          if ($prefix == 'cmp') {
            $entity = new Compound($entID);
          } else {
            $entity = new Token($entID);
          }
          if (!$entity || $entity->hasError()) {//no word or unavailable so warn
            error_log("Warning inaccessible word id $entGID skipped.");
          } else {
            $entTag = $entity->getEntityTag();
            $entType = 'word';
            addWordToEntityLookups($entity, $refresh);
            $structureHtml .= getWordHtml($entity,$i+1 == $cntGID, $nextToken, $refresh);
          }
        }else{
          error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id=".$edition->getID());
          continue;
        }
        if ($i == 0 && array_key_exists($entTag,$blnPosByEntTag[$entType])) {// first contained entity so if baseline position then copy for sequence.
          $blnPosByEntTag['construct'][$seqTag] = $blnPosByEntTag[$entType][$entTag];
        }
      }//end for
      //close prose wrapper
      if ($seqType == "Pāda" || $seqType == "Item") {//warning!!!! term dependency
        $structureHtml .= '</div>';
      }
    }
    if (!$label) {//output header div
      $fnInfo = getEntityFootnotesHtml($sequence, $refresh);
      $fnHtml = $fnInfo['fnHtml'];
      $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
      if ($fnHtml) {
        $structureHtml .= $fnHtml;
        $fnRefTofnText = array_merge($fnRefTofnText,$fnTextByAnoTag);
      }
}
    $structureHtml .= '</div>';
    //cache html to sequence cache
    if (defined("USEVIEWERCACHING") && USEVIEWERCACHING) {
      $sequence->storeScratchProperty("edn".$edition->getID()."structHtml",$structureHtml);
      $sequence->save();
    }
  }
  return $structureHtml;
}

/**
* returns the html for tokenisation in physical line layout
*
* @param int array of text division $sequence ids containing global ids ordered words of the text
* @param boolean $addBoundaryHtml determine whether to add boundary info for each edition in a multi edition calculation
*/
function getPhysicalLinesHTML2($linePhysSeqIDs, $graID2WordGID, $refresh = false, $addBoundaryHtml = false) {
  global  $edition, $editionTOCHtml, $graID2LineHtmlMarkerlMap, $graID2PSnFMarkerMap, 
          $blnPosByEntTag, $blnInfobyBlnTag, $graID2StructureInlineLabels, $fnRefTofnText,
          $seqBoundaryMarkerHtmlLookup, $curStructHeaderbyLevel;

  $wordCnt = 0;
  //start to calculate HTML using each text division container
  $cntLinePhysGID = count($linePhysSeqIDs);
  $wordTag = null;
  $tdSeqTag = null;
  $preBlnTag = null;
  $word = null;
  $nextLineSequence = null;
  $physicalLinesHtml = '';
  $freetextCnt = 0;
  $fnRefTofnText = array();
  $freetextLines = array();
  $fnRefTofnTextByLine = array();
  //**************process each physical line
  for ($i = 0; $i < $cntLinePhysGID; $i++) {
    $linePhysSeqGID = $linePhysSeqIDs[$i];
    $physicalLineHtml = null;
    $nextLineSeqGID = $i+1<$cntLinePhysGID?$linePhysSeqIDs[$i+1]:null;
    if (strpos($linePhysSeqGID,'seq') === 0) {
      if ($nextLineSequence && $linePhysSeqGID == $nextLineSequence->getGlobalID()) {
        $linePhysSequence = $nextLineSequence;
      } else {
        $linePhysSequence = new Sequence(substr($linePhysSeqGID,4));
      }
      if (!$linePhysSequence || $linePhysSequence->hasError()) {//no sequence or unavailable so warn
        error_log("warn, Warning inaccessible line physical sequence $linePhysSeqGID skipped.");
        continue;
      } else {//physical line sequence
        //check for cached value
        if (USEVIEWERCACHING) {
          $physicalLineHtml = $linePhysSequence->getScratchProperty("edn".$edition->getID()."physLineHtml");
          if (!$physicalLineHtml) {
            $physicalLineHtml = $linePhysSequence->getScratchProperty("physLineHtml");
          }
          $fnRefTofnTextByLine = $linePhysSequence->getScratchProperty("edn".$edition->getID()."fnTextByAnoTag");
          if ($fnRefTofnTextByLine && count($fnRefTofnTextByLine) == 0) {
            $fnRefTofnTextByLine = $linePhysSequence->getScratchProperty("fnTextByAnoTag");
          }
        }
        if ($physicalLineHtml && !$refresh) {
          $physicalLinesHtml .= $physicalLineHtml;
          $nextLineSequence = null;
          $wordTag = $linePhysSequence->getScratchProperty("wrappedWordTag");
          $fnRefTofnText = array_merge($fnRefTofnText,$fnRefTofnTextByLine);
          continue;
        } else { // calculate physical line HTML
          $physicalLineHtml = '';
          $lineSclGIDs = $linePhysSequence->getEntityIDs();
          $seqType = $linePhysSequence->getType();
          $seqTag = $linePhysSequence->getEntityTag();
          $fnRefTofnTextByLine = array();
          $nextLineSclGID = null;
          $nextLineSequence = null;
          $hasWrappingWord = false;
          $prevTCMS = "S";
          $nextTCMS = "";
          $tcms = "";
          $prevGraIsVowelCarrier = false;
          $previousA = null;
          $previousNum = false;
          $typeIdNumber = Entity::getIDofTermParentLabel("numbersign-graphemetype");//term dependency
          // check for baseline change
          if (defined('SHOWBLNBOUNDARIES') && SHOWBLNBOUNDARIES && 
              isset($blnPosByEntTag) && isset($blnPosByEntTag['line']) &&
              isset($blnPosByEntTag['line'][$seqTag]) && isset($blnPosByEntTag['line'][$seqTag]['blnTag'])) {
            $blnTag = $blnPosByEntTag['line'][$seqTag]['blnTag'];
            if ($blnTag && $blnTag != $preBlnTag && isset($blnInfobyBlnTag[$blnTag])
                && isset($blnInfobyBlnTag[$blnTag]['title']) && $blnInfobyBlnTag[$blnTag]['title']) {
              $blnTitle = $blnInfobyBlnTag[$blnTag]['title'];
              $physicalLineHtml .= "<div class=\"blnBoundary $blnTag $seqTag\">$blnTitle</div>";
              $preBlnTag = $blnTag;
            }
          } 
          //check for boundary entry for this seqTag
          if ($addBoundaryHtml && array_key_exists($seqTag,$seqBoundaryMarkerHtmlLookup)) {
            $physicalLineHtml .= $seqBoundaryMarkerHtmlLookup[$seqTag];
          }
          if (!$lineSclGIDs || count($lineSclGIDs) == 0) {
            error_log("warn, Found empty line physical sequence element $seqTag for edition ".$edition->getDescription()." id=".$edition->getID());
            if ($seqType == "FreeText") {
              $freetextCnt++;
              array_push($freetextLines,$linePhysSequence);
            }
            continue;
          }
          // check for annotation anotext for this line if exist
          $fnInfo = getEntityFootnotesHtml($linePhysSequence, $refresh);
          $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
          if (count($fnTextByAnoTag)>0) {
            foreach ($fnTextByAnoTag as $anoTag => $anoText){
              $fnRefTofnTextByLine[$anoTag] = $anoText;
            }
          }
          if ($nextLineSeqGID && strpos($nextLineSeqGID,'seq') === 0) {
            $nextLineSequence = new Sequence(substr($nextLineSeqGID,4));
            if (!$nextLineSequence || $nextLineSequence->hasError()) {//no sequence or unavailable so warn
              error_log("warn, Warning inaccessible line physical sequence $nextLineSeqGID skipped.");
              $nextLineSequence = null;
            } else {//determine if this line has a wrapping word at the end
              $nextSclGIDs = $nextLineSequence->getEntityIDs();
              if ($nextSclGIDs &&  count($nextSclGIDs) > 0) {
                $nextLineSclGID = $nextSclGIDs[0];
                $nextLineSyllable = new SyllableCluster(substr($nextLineSclGID,4));
                if (!$nextLineSyllable || $nextLineSyllable->hasError()) {//no syllable or unavailable so warn
                  error_log("Warning inaccessible next syllable $nextLineSclGID skipped.");
                } else {
                  $nextLineGraIDs = $nextLineSyllable->getGraphemeIDs();
                  $hasWrappingWord = !(array_key_exists($nextLineGraIDs[0],$graID2WordGID));
                }
              }
            }
          }
          //**************process the syllables of this line
          $cntGID = count($lineSclGIDs);
          for ($j = 0; $j < $cntGID; $j++) {
            $sclGID = $lineSclGIDs[$j];
            $sclID = substr($sclGID,4);
            $syllable = new SyllableCluster($sclID);
            if (!$syllable || $syllable->hasError()) {//no syllable or unavailable so warn
              error_log("Warning inaccessible syllable $sclGID skipped.");
            } else {
              $graIDs = $syllable->getGraphemeIDs();
              $graCnt = count($graIDs);
              //check for start of line headers
              if ($j == 0 && $graCnt) { //start of physical line
                if (INLINEPARTSIDELABEL && array_key_exists($graIDs[0],$graID2PSnFMarkerMap) && array_key_exists('sideMarker',$graID2PSnFMarkerMap[$graIDs[0]])) {
                  $physicalLineHtml .= $graID2PSnFMarkerMap[$graIDs[0]]['sideMarker'];
                } else  if (INLINEPARTSIDELABEL && $graCnt > 1 && array_key_exists($graIDs[1],$graID2PSnFMarkerMap) && array_key_exists('sideMarker',$graID2PSnFMarkerMap[$graIDs[1]])) {// case where glottal starts line
                  $physicalLineHtml .= $graID2PSnFMarkerMap[$graIDs[1]]['sideMarker'];
                }
                $lineHtmlMarker = null;
                if (array_key_exists($graIDs[0],$graID2LineHtmlMarkerlMap)) {
                  $lineHtmlMarker = $graID2LineHtmlMarkerlMap[$graIDs[0]];
                } else if ($graCnt > 1 && array_key_exists($graIDs[1],$graID2LineHtmlMarkerlMap)) { //case where glottal starts line
                  $lineHtmlMarker = $graID2LineHtmlMarkerlMap[$graIDs[1]];
                }
                if ($lineHtmlMarker) {
                  $physicalLineHtml .= "<div class=\"physicalLineDiv\">";
                  $physicalLineHtml .= $lineHtmlMarker;
                  $physicalLineHtml .= "<div class=\"physicalLineWrapperDiv\">";
                }
              }
              //************** process each grapheme in syllable
              for($l=0; $l<$graCnt; $l++) {
                $graID = $graIDs[$l];
                $grapheme = new Grapheme($graID);
                if (!$grapheme) {
                  error_log("err,calculating word html and grapheme not available for graID $graID");
                  $prevGraIsVowelCarrier = false;
                  continue;
                } else {
                  $structLookupGraID = ($grapheme->getSortCode() == "195" && ($graCnt > (1+$l))? $graIDs[1+$l] : $graID);
                  // check for inline fragment marker
                  if (array_key_exists($graID,$graID2PSnFMarkerMap) && array_key_exists('fragLabel',$graID2PSnFMarkerMap[$graID])) {
                    $physicalLineHtml .= $graID2PSnFMarkerMap[$graID]['fragLabel'];
                  } else  if ($grapheme->getSortCode() == "195" and array_key_exists($graIDs[$l + 1],$graID2PSnFMarkerMap) && 
                      array_key_exists('fragLabel',$graID2PSnFMarkerMap[$graIDs[$l + 1]])) { //case where glottal starts line
                    $physicalLineHtml .= $graID2PSnFMarkerMap[$graIDs[$l+1]]['fragLabel'];
                  }
                  //track type for numbers
                  $isNumber = ($grapheme->getType() == $typeIdNumber);
                  //check for TCM transition brackets
                  $tcms = $grapheme->getTextCriticalMark();
                  $postTCMBrackets = "";
                  $preTCMBrackets = "";
                  if ($prevTCMS != $tcms) {
                    list($postTCMBrackets,$preTCMBrackets) = getTCMTransitionBrackets($prevTCMS,$tcms,true);
                  }
                  if ($postTCMBrackets && !($j==0 && $l == 0)) {//ignore post brackets at beginning of line, should not exist
                    $physicalLineHtml .= $postTCMBrackets;
                  }
                  //check for new word start - current grapheme starts new word
                  if (array_key_exists($graID,$graID2WordGID)) {
                    if (isset($word) && ($j > 0 || $l > 0)) {//not start of line so must be existing word to close
                      $fnInfo = getEntityFootnotesHtml($word, $refresh);
                      $fnHtml = $fnInfo['fnHtml'];
                      $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
                      if ($fnHtml) {
                        $physicalLineHtml .= $fnHtml;
                        foreach ($fnTextByAnoTag as $anoTag => $anoText){
                          $fnRefTofnTextByLine[$anoTag] = $anoText;
                        }
                        $fnHtml = "";
                      }
                      $physicalLineHtml .= '</span>';
                      $previousA = false;
                    }
                    // switch to new word
                    if (array_key_exists($graID,$graID2WordGID)) {
                      $word = EntityFactory::createEntityFromGlobalID($graID2WordGID[$graID][1]);
                      addWordToEntityLookups($word, $refresh);  // check for inline structure marker
                    }
                    if (isset($graID2StructureInlineLabels) && !$prevGraIsVowelCarrier && 
                        array_key_exists($structLookupGraID,$graID2StructureInlineLabels)) {
                      $inlineHtmlLabels = $graID2StructureInlineLabels[$structLookupGraID];
                      if (count($inlineHtmlLabels)) {
                        $structLabelsHtml = "";
                        foreach($inlineHtmlLabels as $htmlLabel) {
                          $structLabelsHtml = $htmlLabel.$structLabelsHtml;
                        }
                        $physicalLineHtml .= $structLabelsHtml;
                      }
                    }
                    list($tdSeqTag,$wordTag,$sandhiWordPos) = $graID2WordGID[$graID];
                    $physicalLineHtml .= '<span class="grpTok '.($sandhiWordPos?"sandhi$sandhiWordPos ":'').($tdSeqTag?$tdSeqTag.' ':'').$wordTag.'">';
                  } else if ($j==0 && $l == 0) {//first grapheme of physical line need to start wordhtml with prevous infor
                    // check for inline structure marker
                    if (isset($graID2StructureInlineLabels) && array_key_exists($structLookupGraID,$graID2StructureInlineLabels)) {
                      $inlineHtmlLabels = $graID2StructureInlineLabels[$structLookupGraID];
                      if (count($inlineHtmlLabels)) {
                        $structLabelsHtml = "";
                        foreach($inlineHtmlLabels as $htmlLabel) {
                          $structLabelsHtml = $htmlLabel.$structLabelsHtml;
                        }
                        $physicalLineHtml .= $structLabelsHtml;
                      }
                    }
                    $physicalLineHtml .= '<span class="grpTok '.($tdSeqTag?$tdSeqTag.' ':'').$wordTag.'">';
                  }
                  if ($preTCMBrackets) {
                    $physicalLineHtml .= $preTCMBrackets;
                  }
                  if ($grapheme->getValue() == "ʔ") {
                    $prevGraIsVowelCarrier = true;
                    $prevTCMS = $tcms;
                    $previousNum = false;
                    continue;
                  }
                  //add grapheme
                  $graTemp = $grapheme->getValue();
                  if ($prevGraIsVowelCarrier && $previousA && ($prevTCMS == $tcms || (!$prevTCMS|| $prevTCMS == "S") && (!$tcms|| $tcms == "S"))) {
                    if ($graTemp == 'i') {
                      $graTemp = "ï";
                    }else if ($graTemp == 'u') {
                      $graTemp = "ü";
                    }
                  }
                  $prevTCMS = $tcms;
                  if ($isNumber && $previousNum) {
                    $physicalLineHtml .= "&nbsp;";
                  }
                  $physicalLineHtml .= $graTemp;
//                  $prevGraID = $graID;
                  if (strtolower($graTemp) == "a") {
                    $previousA = true;
                  } else {
                    $previousA = false;
                  }
                  $previousNum = $isNumber;
                  $prevGraIsVowelCarrier = false;
                }
              }//end for graphIDs
            }
          }//end for syllable IDs
          if ($prevTCMS && $prevTCMS != "S") {//close off any TCM
            $tcmBrackets = getTCMTransitionBrackets($prevTCMS,"S");//reduce to S
            $prevTCMS = "";//reset since we closed off TCMs for the edition.
            //This will ensure next structures output will have opening TCMs
            if ($tcmBrackets) {
              $physicalLineHtml .= $tcmBrackets;
            }
          }
          if ($hasWrappingWord){
            $physicalLineHtml .= "-";
          } else if (isset($word)) {// word ends at EOL so check for footnote
            $fnInfo = getEntityFootnotesHtml($word, $refresh);
            $fnHtml = $fnInfo['fnHtml'];
            $fnTextByAnoTag = $fnInfo['fnTextByAnoTag'];
            if ($fnHtml) {
              $physicalLineHtml .= $fnHtml;
              foreach ($fnTextByAnoTag as $anoTag => $anoText){
                $fnRefTofnTextByLine[$anoTag] = $anoText;
              }
              $fnHtml = "";
              $word = null; //signal already processed footnote
            }
            //$physicalLineHtml .= '</span>';
            $previousA = false;
          }
          $physicalLineHtml .= "</span></div></div>";
          $physicalLineHtml = preg_replace('/_+/',"_",$physicalLineHtml); // multple missing consonants
          $physicalLineHtml = preg_replace('/_/',".",$physicalLineHtml); // multple missing consonants
          //store html to sequence for cache
          $linePhysSequence->storeScratchProperty("edn".$edition->getID()."physLineHtml",$physicalLineHtml);
          $linePhysSequence->storeScratchProperty("edn".$edition->getID()."fnTextByAnoTag",$fnRefTofnTextByLine);
          //transfer annotation text lookup from line to global for global caching.
          $fnRefTofnText = array_merge($fnRefTofnText,$fnRefTofnTextByLine);
          if ($hasWrappingWord && $wordTag) {
            $linePhysSequence->storeScratchProperty("wrappedWordTag",$wordTag);
          }
          $linePhysSequence->save();
        }// end calculate physical line HTML
        $physicalLinesHtml .= $physicalLineHtml;
      }// end else physical line sequence
    } else {
      error_log("warn, Found unknown tokenisation element $seqGID for edition $ednID");
      continue;
    }
  }// end for physLineSeqIDs
  if (!$physicalLinesHtml && $freetextCnt == $cntLinePhysGID) { // all FreeText means plain text edition
    $physicalLinesHtml = getFreeTextHTML($freetextLines);
  }
  return $physicalLinesHtml;
}

function getFreeTextHTML($freetextLines) {
  $freetextHTML = "";
  $cnt = 0;
  foreach ($freetextLines as $freetextLine) {
    $cnt++;
    $seqID = $freetextLine->getID();
    $label = $freetextLine->getLabel();
    $freetext = $freetextLine->getScratchProperty('freetext');
    
    if (!$label) {
      $label = "NL$cnt";
    }
    $freetextHTML .= '<div class="physicalLineDiv">'.
                       '<span class="lineHeader seq'.$seqID.'">'.$label.'</span>'.
                         '<div class="physicalLineWrapperDiv">'.$freetext.'</div></div>';
  }
  return $freetextHTML;
}


/**
* gets the editions Structural layout html
*
* calculates the structure view of this edition of the text with granularity of token,
* embedded physical line markers and footnote markers
*
* @param int $ednID identified the edition to calculate
* @param boolean $forceRecalc indicating whether to ignore cached values
*
* @returns mixed object with a string representing the html and a footnote lookup table
*/
function getEditionsStructuralViewHtml($ednIDs, $forceRecalc = false, $isMultiText = false) {
  global $edition, $prevTCMS, $graID2LineHtmlMarkerlMap, $graID2PSnFMarkerMap, $graID2StructureInlineLabels,// $sclTag2BlnPolyMap,
  $wordCnt, $fnRefTofnText, $typeIDs, $imgURLsbyBlnImgTag, $blnInfobyBlnTag, $curBlnTag, $graID2WordGID,
//  $sclTagLineStart,
  $seqBoundaryMarkerHtmlLookup, $curStructHeaderbyLevel,
  $editionTOCHtml, $polysByBlnTagTokCmpTag, $blnPosByEntTag;
  $footnoteHtml = "";
  $wordCnt = 0;
  $curBlnTag = null;
  $seqBoundaryMarkerHtmlLookup = array();
  $curStructHeaderbyLevel = array();
  $graID2LineHtmlMarkerlMap = array();
  $graID2PSnFMarkerMap = array();
  $prevTCMS = "";
  $editionTOCHtml = "";
  $warnings = array();
  $typeIDs = array();
  $footnoteTypeID = Entity::getIDofTermParentLabel('FootNote-FootNoteType');//warning!!!! term dependency
  $fnReconstrTypeID = Entity::getIDofTermParentLabel('Reconstruction-FootNote');//warning!!!! term dependency
  $typeIDs = array($footnoteTypeID,$fnReconstrTypeID);
  $fnRefTofnText = array();
  $physicalLineSeqIDs = array();
  $textDivSeqIDs = array();
  $analysisSeqIDs = array();
  $sourceNameLookup = array();
  $polysByBlnTagTokCmpTag = array();
  $blnPosByEntTag = array('line'=>array(),'word'=>array(),'construct'=>array());
//  $sclTag2BlnPolyMap = array();
//  $sclTagLineStart = array();
  $blnInfobyBlnTag = array();
  $imgURLsbyBlnImgTag = array('img'=>array(),'bln'=>array());
  $isFirstEdn = true;
  $jsonCache = null;
  if (count($ednIDs) == 1) {
    if(USEVIEWERCACHING) {
      $cacheKey = "edn".$ednIDs[0].(USEPHYSICALVIEW?"physicalviewHTML":"structviewHTML");
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache && $jsonCache->getID() && !$jsonCache->hasError() && !$jsonCache->isDirty() && !$forceRecalc) {
        $cachedEditionData = json_decode($jsonCache->getJsonString(),true);
        //set dependent globals before returning html string
        $fnRefTofnText = $cachedEditionData['fnRefTofnText'];
        $editionTOCHtml = $cachedEditionData['editionTOCHtml'];
        $imgURLsbyBlnImgTag = $cachedEditionData['imgURLsbyBlnImgTag'];
        $blnPosByEntTag = $cachedEditionData['blnPosByEntTag'];
        $polysByBlnTagTokCmpTag = $cachedEditionData['polysByBlnTagTokCmpTag'];
        //return html
        return json_encode($cachedEditionData['editionHtml']);
      } else if (!$jsonCache || $jsonCache->hasError() || !$jsonCache->getID()) {//need to create a new cache object
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        if (!$edition) {
          $edition = new Edition($ednIDs[0]);
        }
        if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
          array_push($warnings,"Warning need valid accessible edition id $ednIDs[0]. Skipping.".
            ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
          $jsonCache = null;
        } else { //align with edition editability and visibility
          $jsonCache->setVisibilityIDs($edition->getVisibilityIDs());
          $jsonCache->setOwnerID($edition->getOwnerID());
        }
      }
    }
  } else if (count($ednIDs) > 1) {
    if(USEVIEWERCACHING) {
      $cacheKey = "edn".join("_",$ednIDs).(USEPHYSICALVIEW?"physicalviewHTML":"structviewHTML");
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache && $jsonCache->getID() && 
         !$jsonCache->hasError() && 
         !$jsonCache->isDirty() && 
         !$forceRecalc) {
        $cachedEditionData = json_decode($jsonCache->getJsonString(),true);
        //set dependent globals before returning html string
        $fnRefTofnText = $cachedEditionData['fnRefTofnText'];
        $editionTOCHtml = $cachedEditionData['editionTOCHtml'];
        $imgURLsbyBlnImgTag = $cachedEditionData['imgURLsbyBlnImgTag'];
        $blnPosByEntTag = $cachedEditionData['blnPosByEntTag'];
        $polysByBlnTagTokCmpTag = $cachedEditionData['polysByBlnTagTokCmpTag'];
        //return html
        return json_encode($cachedEditionData['editionHtml']);
      } else if (!$jsonCache || $jsonCache->hasError() || !$jsonCache->getID()) {//need to create a new cache object
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        if (!$edition) {
          $edition = new Edition($ednIDs[0]);
        }
        if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
          array_push($warnings,"Warning need valid accessible edition id $ednIDs[0]. Skipping.".
            ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
          $jsonCache = null;
        } else { //align with edition editability and visibility
          $jsonCache->setVisibilityIDs($edition->getVisibilityIDs());
          $jsonCache->setOwnerID($edition->getOwnerID());
        }
      }
    }
  }
  $graID2WordGID = array();
  //multiple editions here means a multipart text with each part as a text + edition
  foreach ($ednIDs as $ednID) {//accumulate in order all subsequence for text, text physical and analysis
    $edition = new Edition($ednID);
    if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
      array_push($warnings,"Warning need valid accessible edition id $ednID. Skipping.".
        ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
    } else {
      $edSeqIDs = $edition->getSequenceIDs();
      if (count($edSeqIDs) == 0) {//no sequences so warn and skip
        array_push($warnings,"Warning edition id $ednID has no sequences. Skipping.");
        continue;
      }
      //get image lookup information
      $text = $edition->getText(true);
      if ($text && !$text->hasError()) {
        $images = $text->getImages(true);
        if ($images && $images->getCount() > 0) {
          $sourceLookup = array();
          foreach ($images as $image) {
            $tag = $image->getEntityTag();
            $attributions = $image->getAttributions(true);
            if ($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
              foreach ($attributions as $attribution) {
                $atbID = $attribution->getID();
                $title = $attribution->getTitle();
                if (!array_key_exists($atbID,$sourceLookup)) {
                  $sourceLookup[$atbID] = $title;
                }
              }
            }
            $url = $image->getURL();
            $ord = $image->getScratchProperty('ordinal');
            if ($ord) {
              $sort = $ord;
            } else {
              $sort = 1000 * intval($image->getID());
            }
            $imgURLsbyBlnImgTag['img'][$sort] = array('tag'=>$tag, 'url'=>$url, 'source'=>$sourceLookup);
            $title = $image->getTitle();//?$image->getTitle():substr($url,strrpos($url,'/')+1);//filename
            $imgURLsbyBlnImgTag['img'][$sort]['title'] = $title?$title:"";
            if ($url) {
              $info = pathinfo($url);
              $dirname = $info['dirname'];
              if (strpos($dirname,'full/full') > -1) { //assume iiif
                $fullpath = str_replace('full/full','full/pct:5',$dirname).'/'.$info['basename'];
              } else if (strpos($dirname,'iiif') > -1 && strpos($dirname,'/full/0') > -1 ) { //assume iiif     
                $fullpath = str_replace('/full/0','/pct:5/0',$dirname).'/'.$info['basename'];
              } else {
                $fullpath =  $dirname."/th".$info['basename'];
              }
              $imgURLsbyBlnImgTag['img'][$sort]['thumbUrl'] = $fullpath;
            }
          }
        }
      }
      // get aggregate list of sequence ids for physical Text, Tokens and analysis
      $seqPhys = $seqText = $textAnalysisSeq = null;
      foreach ($edSeqIDs as $edSeqID) {
        $edSequence = new Sequence($edSeqID);
        if (!$edSequence || $edSequence->hasError()) {//no sequence or unavailable so warn
          array_push($warnings,"Warning unable to load edition $ednID's sequence seq:$edSeqID. Skipping.".
            ($edSequence->hasError()?" Error: ".join(",",$edSequence->getErrors()):""));
        } else {
          $seqType = $edSequence->getType();
          $componentIDs = $edSequence->getEntityIDs();
          if (!$componentIDs || count($componentIDs) == 0) {//no sequences so warn and skip
            array_push($warnings,"Warning edition edn:$ednID's sequence seq:$edSeqID has no components. Skipping.");
            continue;
          }
          if (!$seqPhys && $seqType == "TextPhysical"){//warning!!!! term dependency
            $physicalLineSeqIDs = array_merge($physicalLineSeqIDs,$componentIDs);
            $entTag = str_replace(':','',$componentIDs[0]);
            if (!$isFirstEdn) {
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
            if ($isMultiText) {
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($text,"textBoundary $entTag");
            }
          } else if (!$seqText && $seqType == "Text"){//warning!!!! term dependency
            $textDivSeqIDs = array_merge($textDivSeqIDs,$componentIDs);
            $entTag = str_replace(':','',$componentIDs[0]);
            if (!$isFirstEdn) {
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
            if ($isMultiText) {
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($text,"textBoundary $entTag");
            }
          } else if (!$textAnalysisSeq && $seqType == "Analysis"){//warning!!!! term dependency
            $analysisSeqIDs = array_merge($analysisSeqIDs,$componentIDs);
            $entTag = str_replace(':','',$componentIDs[0]);
            if (!$isFirstEdn) {
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
            if ($isMultiText) {
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($text,"textBoundary $entTag");
            }
          } else {//ignoring sequence so warn
            array_push($warnings,"Warning no code to handle sequence seq:$edSeqID of type $seqType. Skipping.");
          }
        }
      }
      if ($isFirstEdn) {
        $isFirstEdn = false;
        $attributions = $edition->getAttributions(true);
        if ($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
          foreach ($attributions as $attribution) {
            $atbID = $attribution->getID();
            $title = $attribution->getTitle();
            if (!array_key_exists($atbID,$sourceNameLookup)) {
              $sourceNameLookup[$atbID] = $title;
            }
          }
        }
      }
//      $graID2WordGID = array();
      $ednLookupInfo = getEdnLookupInfo($edition, $typeIDs,(count($analysisSeqIDs) > 0 && !USEPHYSICALVIEW),$forceRecalc,$isMultiText);
      if ($ednLookupInfo){
        if (array_key_exists("lineScrollTops",$ednLookupInfo) && count($ednLookupInfo['lineScrollTops'])) {
          foreach ($ednLookupInfo['lineScrollTops'] as $seqTag => $lineBlnScrollTop) {
            $blnPosByEntTag['line'][$seqTag] = $lineBlnScrollTop;
          }
        }
        if (count($ednLookupInfo['htmlLineMarkerByGraID'])) {
          foreach ($ednLookupInfo['htmlLineMarkerByGraID'] as $lineGraID => $lineHtml) {
            $graID2LineHtmlMarkerlMap[$lineGraID] = $lineHtml;
          }
        }
        //get part-side-n-fragment markers
        if (array_key_exists("PFSHtmlMarkerMap",$ednLookupInfo) && count($ednLookupInfo['PFSHtmlMarkerMap'])) {
          foreach ($ednLookupInfo['PFSHtmlMarkerMap'] as $sclGraID => $PSnFMarker) {
            $graID2PSnFMarkerMap[$sclGraID] = $PSnFMarker;
          }
        }
        if (array_key_exists("blnInfoBySort",$ednLookupInfo) && count($ednLookupInfo['blnInfoBySort'])) {
          foreach ($ednLookupInfo['blnInfoBySort'] as $sort => $blnInfo) {
            $imgURLsbyBlnImgTag['bln'][$sort] = $blnInfo;
            $blnInfo['sort'] = $sort;
            $blnInfobyBlnTag[$blnInfo['tag']] = $blnInfo;
          }
        }
        if (array_key_exists("graID2StructureInlineLabels",$ednLookupInfo) && count($ednLookupInfo['graID2StructureInlineLabels'])) {
          if ($graID2StructureInlineLabels && count($graID2StructureInlineLabels)) {
            $graID2StructureInlineLabels = $graID2StructureInlineLabels + $ednLookupInfo['graID2StructureInlineLabels'];
          } else {
            $graID2StructureInlineLabels = $ednLookupInfo['graID2StructureInlineLabels'];
          }
        }
        if (array_key_exists("gra2WordGID",$ednLookupInfo) && count($ednLookupInfo['gra2WordGID'])) {
          if ($graID2WordGID && count($graID2WordGID)) {
            $graID2WordGID = $graID2WordGID + $ednLookupInfo['gra2WordGID'];
          } else {
            $graID2WordGID = $ednLookupInfo['gra2WordGID'];
          }
        }
      }
    }//end else valid edition
  }// end foreach ednID

  //calculate attributed source for edition
  $sourceHtml = "";
  if ($sourceNameLookup && count($sourceNameLookup) > 0) {
    $isFrist = true;
    $sourceHtml = "<div class=\"source edn1\"><span class=\"sourcelabel\">Source: </span>";
    $sources = '';
    foreach ($sourceNameLookup as $atbID => $title) {
      $titlez = str_replace('_',' ',$title);
      $sources .= "<span class=\"sourceitem atb$atbID\">$titlez</span>, ";
    }
    $sourceHtml .= trim($sources,', ')."</div>";
  }

  $html = "";
  if (count($analysisSeqIDs) == 0 && count($textDivSeqIDs) == 0) {
    if (count($physicalLineSeqIDs) > 0) {
      $html .= getPhysicalLinesHtml2($physicalLineSeqIDs, $graID2WordGID,$forceRecalc,true);
    } else {
      array_push($warnings,"Warning no structural analysis found for edition id $ednID. Skipping.");
    }
  } else {//process analysis
    //calculate  post grapheme id to generate physical line label map
    $tokCnt = 0;
    if (count($analysisSeqIDs) > 0 && !USEPHYSICALVIEW) {
      //start to calculate HTML using each entity of the analysis container
      foreach ($analysisSeqIDs as $entGID) {
        $prefix = substr($entGID,0,3);
        $entID = substr($entGID,4);
        if ($prefix == 'seq') {
          $subSequence = new Sequence($entID);
          if (!$subSequence || $subSequence->hasError()) {//no sequence or unavailable so warn
            error_log("warn, Warning inaccessible sub-sequence id $entID skipped.");
          } else {
            $html .= getStructHtml($subSequence,$forceRecalc,true);
          }
        } else if ($prefix == 'cmp' || $prefix == 'tok' ) {
          if ($prefix == 'cmp') {
            $entity = new Compound($entID);
          } else {
            $entity = new Token($entID);
          }
          if (!$entity || $entity->hasError()) {//no word or unavailable so warn
            error_log("warn, Warning inaccessible word id $entGID skipped.");
          } else {
            addWordToEntityLookups($entity,$forceRecalc);
            $html .= getWordHtml($entity,false,null,$forceRecalc);
          }
        }else{
          error_log("warn, Found unknown structural element $entGID for edition $ednID");
          continue;
        }
      }
      //check for termination divs by inspection of $curStructHeaderbyLevel
      if (count($curStructHeaderbyLevel) > 0) {
        foreach (array_keys($curStructHeaderbyLevel) as $key) {
          $html .= "</div>";
        }
      }
    } else {// process textdivisions in physical line layout
//      $html .= getPhysicalLinesHtml($textDivSeqIDs,$forceRecalc,true);
      $html .= getPhysicalLinesHtml2($physicalLineSeqIDs, $graID2WordGID,$forceRecalc,true);
    }
  }// end else
  $html .= $sourceHtml;
  if(USEVIEWERCACHING && $jsonCache) {
    $cachedEditionData = array();
    //save html
    $cachedEditionData['editionHtml'] = $html;
    //store dependent globals used to calc html
    $cachedEditionData['fnRefTofnText'] = $fnRefTofnText;
    $cachedEditionData['editionTOCHtml'] = $editionTOCHtml;
    $cachedEditionData['imgURLsbyBlnImgTag'] = $imgURLsbyBlnImgTag;
    $cachedEditionData['blnPosByEntTag'] = $blnPosByEntTag;
    $cachedEditionData['polysByBlnTagTokCmpTag'] = $polysByBlnTagTokCmpTag;
    $jsonCache->setJsonString(json_encode($cachedEditionData));
    $jsonCache->clearDirtyBit();
    $jsonCache->save();
  }
  if (count($warnings) > 0) {
    foreach ($warnings as $msg) {
      error_log("warning - $msg");
    }
  }
  $ret = json_encode($html);
  error_log(substr($ret,0,200));
  return $ret;
}


function getWrdTag2GlossaryPopupHtmlLookup($catID,$scopeEdnID = null,$refresh = 0, $linkTemplate = null, $useTranscription = true, $hideHyphens = true) {
  global $exportGlossary;
  $catalog = new Catalog($catID);
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    error_log("Warning need valid catalog id $catID.");
    return array();
  } else {
    $sepLabel = defined('CKNLINENUMSEPARATOR')?CKNLINENUMSEPARATOR:null;
    if ($scopeEdnID){
      $textTypeID = Entity::getIDofTermParentLabel('text-sequencetype');// warning!!! term dependency
      //find the text division sequence ids for the scoped edition as a comma separated list
      $query = "select regexp_replace(array_to_string(seq_entity_ids,','),'seq:','','g')".
              " from edition left join sequence on seq_id = ANY(edn_sequence_ids)".
              " where edn_id = $scopeEdnID and seq_type_id = $textTypeID;";
      $dbMgr = new DBManager();
      $dbMgr->query($query);
      if ($dbMgr->getRowCount()==0) {
        error_log("Warning no text div for edition $scopeEdnID.");
        return array();
      } else {
        $strTxtDivSeqIDs = $dbMgr->fetchResultRow();
        $strTxtDivSeqIDs = $strTxtDivSeqIDs[0];
      }
      // query to find only lemma where tokens of the scoped edition are attestations
      $condition = " not lem_owner_id = 1 and lem_component_ids && ".
              "(select array_agg(ids.gid) ".
               "from (select unnest(seq_entity_ids) as gid from sequence where seq_id in ($strTxtDivSeqIDs)) as ids)".
         " union ".
         "select * from lemma where lem_catalog_id = $catID and  not lem_owner_id = 1 and ".
            "cast(lem_component_ids as text[]) && (select array_agg(igids.gid) ".
                          "from (select concat('inf:',inf_id) as gid from inflection where inf_component_ids && ".
                                  "cast((select array_agg(ids.gid) from (select unnest(seq_entity_ids) as gid from sequence ".
                                                                         "where seq_id in ($strTxtDivSeqIDs)) as ids) as text[])) as igids)";
    } else {
      $condition = "lem_catalog_id = $catID and not lem_owner_id = 1";
    }
    $glossaryCommentTypeID = Entity::getIDofTermParentLabel('glossary-commentarytype'); //term dependency
    $entTag2GlossaryHtml = array();
    $attested2LemmaInfoMap = array();
    if ($linkTemplate) {
      $glossaryHRefUrl =$linkTemplate;
    } else if (defined("LEMMALINKTEMPLATE")) {
      $glossaryHRefUrl = LEMMALINKTEMPLATE;//READ_DIR."/plugins/dictionary/?search=%lemval%";
    } else if ($exportGlossary){
      $glossaryHRefUrl = READ_DIR."/services/exportHTMLGlossary.php?db=".DBNAME."&catID=$catID#%lemtag%";
    } else {
      $glossaryHRefUrl = "";
    }
    $offset = 0;
    $cnt = 100;
    while ($cnt == 100) {// get lemma in pages of 100 to avoid memory overload, consider configure param for tuning
      $lemmas = new Lemmas($condition,"lem_sort_code,lem_sort_code2",$offset,100);
      $cnt = $lemmas->getCount(); // should get 100 except possibly for last page
      if ($cnt > 0) {
        $lemmas->setAutoAdvance(false);
        foreach($lemmas as $lemma) {
          $hasAttestations = false;
          $lemGID = $lemma->getGlobalID();
          $lemTag = 'lem'.$lemma->getID();
          $lemHtml = $lemma->getScratchProperty('entry');//check for cache
          if ($lemHtml && !$refresh && USEVIEWERCACHING) {
            $entTag2GlossaryHtml[$lemTag] = array('entry' => $lemHtml);// lemma article
            $attestedHtml = $lemma->getScratchProperty('attestations');
            if ($attestedHtml){
              $entTag2GlossaryHtml[$lemTag]['attestedHtml'] = $attestedHtml;//lemma attested html - citations
            }
            $relatedHtml = $lemma->getScratchProperty('related');
            if ($relatedHtml){
              $entTag2GlossaryHtml[$lemTag]['relatedHtml'] = $relatedHtml;//lemma attested html - citations
            }
            $attested2LemmaInfoMap = $lemma->getScratchProperty('attestedInfoMap');
            if ($attested2LemmaInfoMap) {
              $entTag2GlossaryHtml = array_merge($entTag2GlossaryHtml,$attested2LemmaInfoMap);
            }
          } else {
            $lemHtml = "<div class=\"lemmaentry $lemTag\">";
            if ($lemmaOrder = $lemma->getHomographicOrder()) {
              $lemHtml .= "<sup class=\"homographic\">".$lemmaOrder."</sup>";
            }
            $lemmaValue = preg_replace('/ʔ/','',$lemma->getValue());
            $lemmaLookupURL = $glossaryHRefUrl;
            if ($lemmaLookupURL && strpos($lemmaLookupURL,"%lemtag%")) {
              $lemmaLookupURL = str_replace("%lemtag%",$lemTag,$lemmaLookupURL);
            }
            if ($lemmaLookupURL && strpos($lemmaLookupURL,"%lemval%")) {
              $lemmaLookupURL = str_replace("%lemval%",($lemmaOrder?$lemmaOrder:'').$lemmaValue,$lemmaLookupURL);
            }
            if ($lemmaLookupURL == "") {//no link case
              $lemHtml .= "<span class=\"lemmaheadword\">$lemmaValue</span><span class=\"lemmapos\">";
            } else {
              $lemHtml .= "<span class=\"lemmaheadword\"><a target=\"mygloss\" href=\"$lemmaLookupURL\">$lemmaValue</a></span><span class=\"lemmapos\">";
            }
            $lemmaGenderID = $lemma->getGender();
            $lemmaPosID = $lemma->getPartOfSpeech();
            $lemmaPos = $lemmaPosID && is_numeric($lemmaPosID) && Entity::getTermFromID($lemmaPosID) ? Entity::getTermFromID($lemmaPosID) : '';
            $isVerb = null;
            if ($lemmaPos) {
              $isVerb = ($lemmaPos == 'v.');
            }
            $lemmaSposID = $lemma->getSubpartOfSpeech();
            $lemmaSpos = $lemmaSposID && is_numeric($lemmaSposID) && Entity::getTermFromID($lemmaSposID) ? Entity::getTermFromID($lemmaSposID) : '';
            $lemmaCF = $lemma->getCertainty();//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
            if ($lemmaGenderID && is_numeric($lemmaGenderID)) {//warning Order Dependency for display code lemma gender (like for nouns) hides subPOS hides POS
              $lemHtml .=  Entity::getTermFromID($lemmaGenderID).($lemmaCF[2]==2?'(?)':'');
            } else if ($lemmaSpos ) {
              $lemHtml .=  $lemmaSpos.($lemmaCF[1]==2?'(?)':'');
            }else if ($lemmaPos) {
              $lemHtml .=  $lemmaPos.($lemmaCF[0]==2?'(?)':'');
            }
            $lemHtml .= "</span>";
            if ($lemmaEtym = $lemma->getDescription()) {
              //replace embedded HTML markup
              $lemHtml .= "<span class=\"etymology\">".(SHOWETYMPARENS?"(":"");
              $lemHtml .= (FORMATENCODEDETYM?formatEtym($lemmaEtym):$lemmaEtym);
              $lemHtml .= (SHOWETYMPARENS?")":"").",</span>";
            }
            if ($lemmaGloss = $lemma->getTranslation()) {
              //replace embedded HTML markup
              $lemHtml .= "<span class=\"gloss\">&ldquo;$lemmaGloss&rdquo;</span>";
            }
            if ( $linkedAnoIDsByType = $lemma->getLinkedAnnotationsByType()) {
              if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
                $lemmaCommentary = "";
                foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
                  $annotation = new Annotation($anoID);
                  $comment = $annotation->getText();
                  if ($comment) {
                    $lemmaCommentary .= "<span class=\"lemmacomment\">($comment)</span>";
                  }
                }
                if ($lemmaCommentary) {
                  $lemHtml .= $lemmaCommentary;
                }
              }
            }
            $lemHtml .= "</div>";
            $entTag2GlossaryHtml[$lemTag] = array('entry' => $lemHtml);// lemma article
            $lemma->storeScratchProperty('entry',$lemHtml);
            $lemmaComponents = $lemma->getComponents(true);
            if ($lemmaComponents && $lemmaComponents->getCount()) {
              $hasAttestations = true; // signal see also
              $groupedForms = array();
              $pattern = array("/aʔi/","/aʔu/","/ʔ/","/°/","/\/\/\//","/#/","/◊/","/◈/","/◯/");
              $replacement = array("aï","aü","","","","","","","");//display as replacements  separate a followed by vowel i is displayed as aï
              foreach ($lemmaComponents as $lemmaComponent) {
                $entPrefix = $lemmaComponent->getEntityTypeCode();
                $entID = $lemmaComponent->getID();
                $entTag = $entPrefix.$entID;
                if ($entPrefix == 'inf') {//inflections
                  $inflection = $lemmaComponent;
                  $infTag = $entTag;
                  //calculate inflection string
                  //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
                  $ingCF = $inflection->getCertainty();
                  $case = $inflection->getCase();
                  $gen = $inflection->getGender();
                  $num = $inflection->getGramaticalNumber();
                  $vper = $inflection->getVerbalPerson();
                  $vvoice = $inflection->getVerbalVoice();
                  $vtense = $inflection->getVerbalTense();
                  $vmood = $inflection->getVerbalMood();
                  $conj2 = $inflection->getSecondConjugation();
                  $infString = '';
                  if ($isVerb) { //term dependency
                    if ($vmood && is_numeric($vmood)) {
                      $vtensemood = Entity::getTermFromID($vmood).($ingCF[2]==2?'(?)':'');
                      $infString .= "<span class=\"inflectdescript\">$vtensemood</span>";
                    } else if ($vtense && is_numeric($vtense)) {
                      $vtensemood = Entity::getTermFromID($vtense).($ingCF[0]==2?'(?)':'');
                      $infString .= "<span class=\"inflectdescript\">$vtensemood</span>";
                    } else {
                      $vtensemood = '?';
                    }
                    if (!array_key_exists($vtensemood,$groupedForms)) {
                      $groupedForms[$vtensemood] = array();
                    }
                    if ($num && is_numeric($num)) {
                      $num = Entity::getTermFromID($num).($ingCF[4]==2?'(?)':'');
                    } else {
                      $num = '?';
                    }
                    if (!array_key_exists($num,$groupedForms[$vtensemood])) {
                      $groupedForms[$vtensemood][$num] = array();
                    }
                    if ($vper && is_numeric($vper)) {
                      $vper = Entity::getTermFromID($vper).($ingCF[6]==2?'(?)':'');
                      $infString .= "<span class=\"inflectdescript\">$vper</span>";
                    } else {
                      $vper = '?';
                    }
                    if (!array_key_exists($vper,$groupedForms[$vtensemood][$num])) {
                      $groupedForms[$vtensemood][$num][$vper] = array();
                    }
                    if ($num && $num != '?') {
                      $infString .= "<span class=\"inflectdescript\">$num</span>";
                    }
                    if ($conj2 && is_numeric($conj2)) {
                      $conj2 = Entity::getTermFromID($conj2).($ingCF[7]==2?'(?)':'');
                      $infString .= "<span class=\"inflectdescript\">$conj2</span>";
                    } else {
                      $conj2 = '?';
                    }
                    if (!array_key_exists($conj2,$groupedForms[$vtensemood][$num][$vper])) {
                      $groupedForms[$vtensemood][$num][$vper][$conj2] = array();
                    }
                    $node = &$groupedForms[$vtensemood][$num][$vper][$conj2];
                  } else {
                    if ($gen && is_numeric($gen)) {
                      $gen = Entity::getTermFromID($gen).($ingCF[3]==2?'(?)':'');
                      if (!$lemmaGenderID){//handle noun supress infection gender output
                        $infString .= "<span class=\"inflectdescript\">$gen</span>";
                      }
                    } else {
                      $gen = '?';
                    }
                    if (!array_key_exists($gen,$groupedForms)) {
                      $groupedForms[$gen] = array();
                    }
                    if ($num && is_numeric($num)) {
                      $num = Entity::getTermFromID($num).($ingCF[4]==2?'(?)':'');
                    } else {
                      $num = '?';
                    }
                    if (!array_key_exists($num,$groupedForms[$gen])) {
                      $groupedForms[$gen][$num] = array();
                    }
                    if ($case && is_numeric($case)) {
                      $case = Entity::getTermFromID($case).($ingCF[5]==2?'(?)':'');
                      $infString .= "<span class=\"inflectdescript\">$case</span>";
                    } else {
                      $case = '?';
                    }
                    if (!array_key_exists($case,$groupedForms[$gen][$num])) {
                      $groupedForms[$gen][$num][$case] = array();
                    }
                    if ($num) {
                      $infString .= "<span class=\"inflectdescript\">$num</span>";
                    }
                    if ($conj2 && is_numeric($conj2)) {
                      $conj2 = Entity::getTermFromID($conj2).($ingCF[7]==2?'(?)':'');
                      $infString .= "<span class=\"inflectdescript\">$conj2</span>";
                    } else {
                      $conj2 = '?';
                    }
                    if (!array_key_exists($conj2,$groupedForms[$gen][$num][$case])) {
                      $groupedForms[$gen][$num][$case][$conj2] = array();
                    }
                    $node = &$groupedForms[$gen][$num][$case][$conj2];
                  }
                  $inflectionComponents = $inflection->getComponents(true);
                  foreach ($inflectionComponents as $inflectionComponent) {
                    if ($inflectionComponent->isMarkedDelete()) {
                      error_log("found inflection component marked for delete ".$inflectionComponent->getGlobalID());
                      continue;
                    }
                    if ($useTranscription) {
                      $value = $inflectionComponent->getTranscription();
                    } else {
                      $value = $inflectionComponent->getValue();
                    }
                    if ($hideHyphens) {
                      $value = preg_replace("/\-/","",$value);
                    }
                    $value = preg_replace($pattern,$replacement,$value);
                    $sc = $inflectionComponent->getSortCode();
                    $entTag = $inflectionComponent->getEntityTag();
                    $entTag2GlossaryHtml[$entTag] = array('lemTag' => $lemTag,// word link to inflection data and lemma - popup shows inflection for clicked word only
                      'infTag'=> $infTag,
                      'infHtml'=> $infString);
                    //TODO:Syntax   add code here to check inflectionComponent has syntax info and add to glossary info
                    //this will cache the information for the popup html calc code
                    $syntacticDependency = $inflectionComponent->getScratchProperty('syntacticRelation');
                    if ($syntacticDependency) {
                      $entTag2GlossaryHtml[$entTag]['syntax'] = "<span class=\"syntaxDependency\">| $syntacticDependency</span>";
                    }
                    $attested2LemmaInfoMap[$entTag] = $entTag2GlossaryHtml[$entTag];
                    $attestedCommentary = "";
                    if ( $linkedAnoIDsByType = $inflectionComponent->getLinkedAnnotationsByType()) {
                      if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
                        foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
                          $annotation = new Annotation($anoID);
                          $comment = $annotation->getText();
                          if ($comment) {
                            if ($attestedCommentary) {
                              $attestedCommentary .= " ";
                            }
                            $attestedCommentary .= $comment;
                          }
                        }
                      }
                    }
                    if ($attestedCommentary) {
                      $entTag2GlossaryHtml[$entTag]['glossaryCommentary'] = $attestedCommentary;
                    }
                    if (is_numeric($refresh) && $refresh > 1) {//ensure label is current
                      $inflectionComponent->updateLocationLabel();
                    }
                    $loc = $inflectionComponent->getLocation().($attestedCommentary?" (".$attestedCommentary.")":"");
                    //accumulate locations
                    if (!array_key_exists($sc,$node)) {
                      $node[$sc] = array('value'=>
                        array($value =>
                          array('loc'=>
                            array())));
                    } else if (!array_key_exists($value,$node[$sc]['value'])) {
                      $node[$sc]['value'][$value] =  array('loc'=>
                        array());
                    }
                    if (array_key_exists($loc,$node[$sc]['value'][$value]['loc'])) {
                      $node[$sc]['value'][$value]['loc'][$loc] += 1;
                    } else {
                      $node[$sc]['value'][$value]['loc'][$loc] = 1;
                    }
                  }
                } else { //un-inflected form
                  if ($useTranscription) {
                    $value = $lemmaComponent->getTranscription();
                  } else {
                    $value = $lemmaComponent->getValue();
                  }
                  if (!$value) {
                    continue;
                  }
                  if ($hideHyphens) {
                    $value = preg_replace("/\-/","",$value);
                  }
                  $value = preg_replace($pattern,$replacement,$value);
                  $sc = $lemmaComponent->getSortCode();
                  $entTag = preg_replace("/:/","",$lemmaComponent->getGlobalID());
                  $entTag2GlossaryHtml[$entTag] = array('lemTag' => $lemTag);//word link to lemma
                  //check lemmaComponent has syntax info and add to glossary info
                  $syntacticDependency = $lemmaComponent->getScratchProperty('syntacticRelation');
                  if ($syntacticDependency) {
                    $entTag2GlossaryHtml[$entTag]['syntax'] = "<span class=\"syntaxDependency\">| $syntacticDependency</span>";
                  }
                  $attested2LemmaInfoMap[$entTag] = $entTag2GlossaryHtml[$entTag];
                  $attestedCommentary = "";
                  if ( $linkedAnoIDsByType = $lemmaComponent->getLinkedAnnotationsByType()) {
                    if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
                      foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
                        $annotation = new Annotation($anoID);
                        $comment = $annotation->getText();
                        if ($comment) {
                          if ($attestedCommentary) {
                            $attestedCommentary .= " ";
                          }
                          $attestedCommentary .= $comment;
                        }
                      }
                    }
                  }
                  if ($attestedCommentary) {
                    $entTag2GlossaryHtml[$entTag]['glossaryCommentary'] = $attestedCommentary;
                  }
                  if (is_numeric($refresh) && $refresh > 1) {//ensure label is current
                    $lemmaComponent->updateLocationLabel();
                  }
                  $loc = $lemmaComponent->getLocation().($attestedCommentary?" (".$attestedCommentary.")":"");
                  if (! array_key_exists('?',$groupedForms)) {
                    $groupedForms['?'] = array();
                  }
                  if (! array_key_exists('?',$groupedForms['?'])) {
                    $groupedForms['?']['?'] = array();
                  }
                  if (! array_key_exists('?',$groupedForms['?']['?'])) {
                    $groupedForms['?']['?']['?'] = array();
                  }
                  if (! array_key_exists('?',$groupedForms['?']['?']['?'])) {
                    $groupedForms['?']['?']['?']['?'] = array();
                  }
                  $node = &$groupedForms['?']['?']['?']['?'];
                  // accumulate locations
                  if (! array_key_exists($sc,$node)) {
                    $node[$sc] = array('value'=>
                      array($value =>
                        array('loc'=>
                          array())));
                  } else if (! array_key_exists($value,$node[$sc]['value'])) {
                    $node[$sc]['value'][$value] =  array('loc'=>
                      array());
                  }
                  if (array_key_exists($loc,$node[$sc]['value'][$value]['loc'])) {
                    $node[$sc]['value'][$value]['loc'][$loc] += 1;
                  } else {
                    $node[$sc]['value'][$value]['loc'][$loc] = 1;
                  }
                }
              }
              if ($isVerb) {
                $displayOrder1 = array('pres.','pres.(?)','Indic.','Indic.(?)','opt.','opt.(?)','impv.','impv.(?)','fut.','fut.(?)','perf.','perf.(?)','pret.','pret.(?)','?');
                $displayOrder2 = array('sg.','sg.(?)','du.','du.(?)','pl.','pl.(?)','?');
                $displayOrder3 = array('1st','1st(?)','2nd','2nd(?)','3rd','3rd(?)','?');
                $displayOrder4 = array('inf.','inf.(?)','abs.','abs.(?)','?');
              } else {
                $displayOrder1 = array('m.','m.(?)','mn.','mn.(?)','n.','n.(?)','mnf.','mnf.(?)','nf.','nf.(?)','f.','f.(?)','mf.','mf.(?)','?');
                $displayOrder2 = array('sg.','sg.(?)','du.','du.(?)','pl.','pl.(?)','?');
                $displayOrder3 = array('dir.','dir.(?)','nom.','nom.(?)','acc.','acc.(?)','instr.','instr.(?)','dat.','dat.(?)','dat/gen.','dat/gen.(?)','abl.','abl.(?)','gen.','gen.(?)','loc.','loc.(?)','voc.','voc.(?)','?');
                $displayOrder4 = array('desid.','desid.(?)','intens.','intens.(?)','?');
              }
              $firstComponent = true;
              $attestedHtml = "<div class=\"attestedforms $lemTag\">";
              foreach ($displayOrder1 as $key1) {
                if (!array_key_exists($key1,$groupedForms)){
                  continue;
                }
                $isFirstKey1 = true;
                foreach ($displayOrder2 as $key2) {
                  if (!array_key_exists($key2,$groupedForms[$key1])){
                    continue;
                  }
                  foreach ($displayOrder3 as $key3) {
                    if (!array_key_exists($key3,$groupedForms[$key1][$key2])){
                      continue;
                    }
                    foreach ($displayOrder4 as $key4) {
                      if (!array_key_exists($key4,$groupedForms[$key1][$key2][$key3])){
                        continue;
                      }
                      if ($firstComponent) {
                        $firstComponent = false;
                      } else {
                        $attestedHtml .= "; ";
                      }
                      if ($isFirstKey1) {
                        $isFirstKey1 = false;
                        if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?') {
                          if ($lemmaPos != 'adv.' && $lemmaPos != 'ind.'){ //term dependency
                            $attestedHtml .= "<span class=\"inflectdescript unclear\">unclear: </span>";
                          }
                        } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 != '?') {
                          $attestedHtml .= "<span class=\"inflectdescript\">$key4</span>";
                        } else if (!$lemmaGenderID &&  $lemmaSpos != 'pers.'){//handle noun supress infection gender output
                          $attestedHtml .= "<span class=\"inflectdescript\">$key1</span>";
                        }
                      }
                      if ($key1 != '?' || $key1 == '?' && ($key2 != '?' || $key3 != '?')) {
                        $attestedHtml .= "<span class=\"inflectdescript\">$key3</span>";
                        $attestedHtml .= "<span class=\"inflectdescript\">$key2</span>";
                      }
                      $grpNode = $groupedForms[$key1][$key2][$key3][$key4];
                      ksort($grpNode);
                      $isFirstNode = true;
                      foreach ($grpNode as $sc => $formInfo) {
                        if ($isFirstNode) {
                          $isFirstNode = false;
                        } else {
                          $attestedHtml .= ", ";
                        }
                        $isFirstForm = true;
                        foreach ($formInfo['value'] as $formTranscr => $locInfo) {
                          if ($isFirstForm) {
                            $isFirstForm = false;
                          } else {
                            $attestedHtml .= ", ";
                          }
                          $attestedHtml .= "<span class=\"attestedform\">$formTranscr</span>";
                          $sortedLocs = array_keys($locInfo['loc']);
                          usort($sortedLocs,"compareWordLocations");
                          $isFirstLoc = true;
                          foreach ($sortedLocs as $formLoc) {
                            $cntLoc = $locInfo['loc'][$formLoc];
                            //remove internal ordinal
                            $locParts = explode(":",$formLoc);
                            if (count($locParts) == 3) {
                              if (strpos(trim($locParts[0]),"sort") === 0) {
                                $formLoc = $locParts[2];
                              } else {//if label separator is defined then prepend text label
                                $formLoc = ($sepLabel !== null?$locParts[0].$sepLabel:'').$locParts[2];
                              }
                            } else if (count($locParts) == 2) {
                              $formLoc = $locParts[1];
                            }
                            if ($isFirstLoc) {
                              $isFirstLoc = false;
                              $attestedHtml .= "<span class=\"attestedformloc\">".$formLoc.($cntLoc>1?" [".$cntLoc."×]":"");
                            } else {
                              $attestedHtml .= ",</span><span class=\"attestedformloc\">".$formLoc.($cntLoc>1?" [".$cntLoc."×]":"");
                            }
                          }
                          $attestedHtml .= "</span>";
                        }
                      }
                    }
                  }
                }
              }
              $attestedHtml .= ".</div>";
              $entTag2GlossaryHtml[$lemTag]['attestedHtml'] = $attestedHtml;//lemma attested html - citations
              $lemma->storeScratchProperty('attestations',$attestedHtml);
              $lemma->storeScratchProperty('attestedInfoMap',$attested2LemmaInfoMap);
            }
            $relatedGIDsByLinkType = $lemma->getRelatedEntitiesByLinkType();
            $seeLinkTypeID = Entity::getIDofTermParentLabel('See-LemmaLinkage');
            $cfLinkTypeID = Entity::getIDofTermParentLabel('Compare-LemmaLinkage');
            $relatedHtml = "";
            if ($relatedGIDsByLinkType && array_key_exists($seeLinkTypeID,$relatedGIDsByLinkType)) {
              $isFirst = true;
              $linksHeader = "<span class=\"lemmaLinksHeader seeLinksHeader\">See</span>";
              if ($hasAttestations) {
                $linksHeader = "<span class=\"lemmaLinksHeader seeAlsoLinksHeader\">See also</span>";
              }
              $seeLinks = array();
              foreach ($relatedGIDsByLinkType[$seeLinkTypeID] as $linkGID) {
                $entity = EntityFactory::createEntityFromGlobalID($linkGID);
                $linkTag = str_replace(':','',$linkGID);
                if ($entity && !$entity->hasError()) {
                  if (method_exists($entity,'getValue')) {
                    $value = preg_replace($pattern,$replacement,$entity->getValue());
                  } else {
                    continue;
                  }
                  if (method_exists($entity,'getSortCode')) {
                    $sort = $entity->getSortCode();
                  } else {
                    $sort = substr($linkGID,4);
                  }
                  $seeLinks[$sort] = "<span class=\"seeLink $linkTag\">$value</span>";
                }
              }
              if (count($seeLinks)) {
                $relatedHtml .= "<div class=\"lemmaSeeLinks $lemTag\">";
                ksort($seeLinks,SORT_NUMERIC);
                foreach ($seeLinks as $sort => $linkHtml) {
                  if ($isFirst) {
                    $isFirst = false;
                    $relatedHtml .= $linksHeader." ".$linkHtml;
                  }else{
                    $relatedHtml .= ", ".$linkHtml;
                  }
                }
                $relatedHtml .= ".</div>";
              }
            }
            // find see also links
            $cfLinks = array();
            if ($relatedGIDsByLinkType && array_key_exists($cfLinkTypeID,$relatedGIDsByLinkType)) {
              $isFirst = true;
              $linksHeader = "<span class=\"lemmaLinksHeader cfLinksHeader\">Cf.</span>";
              foreach ($relatedGIDsByLinkType[$cfLinkTypeID] as $linkGID) {
                $entity = EntityFactory::createEntityFromGlobalID($linkGID);
                $linkTag = str_replace(':','',$linkGID);
                if ($entity && !$entity->hasError()) {
                  if (method_exists($entity,'getValue')) {
                    $value = preg_replace($pattern,$replacement,$entity->getValue());
                  } else {
                    continue;
                  }
                  if (method_exists($entity,'getSortCode')) {
                    $sort = $entity->getSortCode();
                  } else {
                    $sort = substr($linkGID,4);
                  }
                  $cfLinks[$sort] = "<span class=\"cfLink $linkTag\">$value</span>";
                }
              }
              if (count($cfLinks)) {
                $relatedHtml .= "<div class=\"lemmaCfLinks $lemTag\">";
                ksort($cfLinks,SORT_NUMERIC);
                foreach ($cfLinks as $sort => $linkHtml) {
                  if ($isFirst) {
                    $isFirst = false;
                    $relatedHtml .= $linksHeader." ".$linkHtml;
                  }else{
                    $relatedHtml .= ",".$linkHtml;
                  }
                }
                $relatedHtml .= ".</div>";
              }
            }
            if ($relatedHtml !== "") {
              $entTag2GlossaryHtml[$lemTag]['relatedHtml'] = $relatedHtml;//lemma related html
              $lemma->storeScratchProperty('related',$relatedHtml);
            }
            $lemma->save();
            unset($lemma);
          }
        }//end foreach lemma
        unset($lemmas);
        $offset += $cnt; //adjust offset to read next 100
      }//end if $cnt > 0
    }//end while $cnt ==100
    return $entTag2GlossaryHtml;
  }
}

function getCatalogHTML($catID, $isStaticView = false, $refresh = 0, $useTranscription = false, $hideHyphens = false) {
  global $termLookup,$cmpTokTag2LocLabel,$term_parentLabelToID, $infCategoryDisplayOrderLookup,$catNameList;
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $sepLabel = defined('CKNLINENUMSEPARATOR')?CKNLINENUMSEPARATOR:null;
  $cmpTokTag2LocLabel = array();
  $htmlGlossary = "";
  //find catalog
  $catalog = new Catalog($catID);
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    return array("warnings","Warning need valid catalog id $catID.");
  } else if (USEVIEWERCACHING && !$refresh && !$catalog->getScratchProperty('refresh')){
    //check for cached version
    $htmlGlossary = $catalog->getScratchProperty('html');
  }
  if (!$htmlGlossary){
//    $cmpTokTag2LocLabel = getWordTagToLocationLabelMap($catalog,$refreshWordMap);
    $refreshLevel = defined('DEFAULTHTMLGLOSSARYREFRESH') && DEFAULTHTMLGLOSSARYREFRESH > 1? DEFAULTHTMLGLOSSARYREFRESH:1;
    $refreshGlossaryHRefUrl = SITE_BASE_PATH."/services/exportHTMLGlossary.php?db=".DBNAME."&refreshLookUps=$refreshLevel&catID=$catID";
    $htmlDomDoc = new DOMDocument('1.0','UTF-8');
    //HTML Header
    $htmlDomDoc->loadXML(
          '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'.
          '<html xmlns="http://www.w3.org/1999/xhtml">'.
          '<head>'.
            '<meta http-equiv="Expires" content="Fri, Jan 01 2017 00:00:00 GMT"/>'.
            '<meta http-equiv="Pragma" content="no-cache"/>'.
            '<meta http-equiv="Cache-Control" content="no-cache"/>'.
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.
            '<meta http-equiv="Lang" content="en"/>'.
            '<meta name="author" content="READ System"/>'.
            '<meta http-equiv="Reply-to" content="@.com"/>'.
            '<meta name="generator" content="READ 1.0"/>'.
            '<meta name="description" content="'.($catalog->getDescription()?$catalog->getDescription():'').'"/>'.
            '<title>Untitled</title>'.
            '<link rel="stylesheet" href="./css/exGlossary.css" type="text/css"/>'.
          '</head>'.
          '<body/>'.
          '</html>');
    // if there is a title change the header title
    if ($glossaryTitle = $catalog->getTitle()) {
      $htmlDomDoc->getElementsByTagName("title")->item(0)->nodeValue = $glossaryTitle;
    }
    $bodyNode = $htmlDomDoc->getElementsByTagName("body")->item(0);
    $glossaryType = $catalog->getTypeID();
    if (array_key_exists($glossaryType,$termLookup)) {
      $bodyClass = $termLookup[$glossaryType];
    } else {
      $bodyClass = 'simpleGlossary';
    }
    $bodyNode->setAttribute('class',$bodyClass);
    // output catalog title
    $titleNode = $htmlDomDoc->createElement('h3',$glossaryTitle);
    $bodyNode->appendChild($titleNode);
    //for dynamic viewer show refresh button to recalc the HTML for the glossary
    if (!$isStaticView && defined('SHOWHTMLGLOSSARYREFRESHBUTTON') && SHOWHTMLGLOSSARYREFRESHBUTTON) {
      $refreshANode = $htmlDomDoc->createElement('a');
      $refreshANode->setAttribute('class',"aRefresh");
      $refreshANode->setAttribute('href',$refreshGlossaryHRefUrl);
      $btnRefreshNode = $htmlDomDoc->createElement('button','refresh');
      $btnRefreshNode->setAttribute('class',"btnRefresh");
      $refreshANode->appendChild($btnRefreshNode);
      $bodyNode->appendChild($refreshANode);
    }
    $glossaryAnoIDs = $catalog->getAnnotationIDs();
    $glossaryAtbIDs = $catalog->getAttributionIDs();
    // term is for glossary commentary annotations
    $glossaryCommentTypeID = Entity::getIDofTermParentLabel('Glossary-CommentaryType');//warning!!! term dependency
    //get all lemmas for this catalog in sorted order then by homographic order
    $lemmas = new Lemmas("lem_catalog_id = $catID and not lem_owner_id = 1","lem_sort_code,lem_sort_code2,lem_homographorder");
    //Get glossary Lemmas
    if ($lemmas->getCount() > 0) {
      $lemIDs = array();
      //iterate through the lemmas
      foreach($lemmas as $lemma) {
        if ($lemma->isMarkedDelete()) {
          continue;
        }
        //initialize control variables
        $isNoun = $isPronoun = $isAdjective = $isNumeral = $isVerb = $isInflectable = false;
        $hasAttestations = false;
        $lemGID = $lemma->getGlobalID();
        $lemTag = 'lem'.$lemma->getID();
        //create 'Entry' node for this lemma
        $entryNode = $htmlDomDoc->createElement('div');
        $entryNode->setAttribute('class',"entry");
        $entryNode->setAttribute('id',"$lemTag");
        $bodyNode->appendChild($entryNode);
        //create lemma definition container node
        $lemmaDefNode = $htmlDomDoc->createElement('div');
        $lemmaDefNode->setAttribute('class','lemmadef');
        $entryNode->appendChild($lemmaDefNode);
        //output homograph order if exist
        if ($lemmaOrder = $lemma->getHomographicOrder()) {
          $lemmaDefNode->appendChild($htmlDomDoc->createElement('sup',$lemmaOrder));
        }
        //output lemma value
        $lemmaValue = preg_replace('/ʔ/','',$lemma->getValue());
        $lemmaNode = $htmlDomDoc->createElement('span',$lemmaValue);
        $lemmaNode->setAttribute('class','lemma');
        $lemmaDefNode->appendChild($lemmaNode);
        $lemmaPosID = $lemma->getPartOfSpeech();
        $lemmaPos = null;
        if ($lemmaPosID && array_key_exists($lemmaPosID,$termLookup)) {
          $lemmaPos = $termLookup[$lemmaPosID];
          $isNoun = $lemmaPos == 'noun'; // term dependency
          $isPronoun = $lemmaPos == 'pron.'; // term dependency
          $isAdjective = $lemmaPos == 'adj.'; // term dependency
          $isNumeral = $lemmaPos == 'num.'; // term dependency
          $isVerb = $lemmaPos == 'v.'; // term dependency
          $isInflectable = ($isNoun || $isPronoun || $isAdjective || $isNumeral || $isVerb);
        }
        $lemmaSposID = $lemma->getSubpartOfSpeech();
        $lemmaSpos = $lemmaSposID && is_numeric($lemmaSposID) && Entity::getTermFromID($lemmaSposID) ? Entity::getTermFromID($lemmaSposID) : null;
        if ($lemmaSpos  == "common adj.") {//warning term dependency
          $lemmaSpos = "adj.";
        }
        $lemmaCF = $lemma->getCertainty();//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        $lemmaGenderID = $lemma->getGender();
        //output lemma POS
        $lemmaGender = $lemmaGenderID && is_numeric($lemmaGenderID) && Entity::getTermFromID($lemmaGenderID) ? Entity::getTermFromID($lemmaGenderID) : null;
        $posNode = null;
        //calculate the lemmas POS label
        if ($isNoun && $lemmaGender) { // nouns show gender classification
          $posNode = $htmlDomDoc->createElement('span',$lemmaGender.($lemmaCF[2]==2?'(?)':''));
        } else if ($lemmaSpos) { // if sub POS show it
          $posNode = $htmlDomDoc->createElement('span',$lemmaSpos.($lemmaCF[1]==2?'(?)':''));
        }else if ($lemmaPos) { // show POS
          $posNode = $htmlDomDoc->createElement('span',$lemmaPos.($lemmaCF[0]==2?'(?)':''));
        }
        if ($posNode){
          $posNode->setAttribute('class','pos');
          $lemmaDefNode->appendChild($posNode);
        }
        // output Etymology
        if ($lemmaEtym = $lemma->getDescription()) {
          $lemmaEtym = html_entity_decode($lemmaEtym);
          $dDoc = new DOMDocument();
          $dDoc->loadXML("<span>$lemmaEtym</span>",LIBXML_NOXMLDECL);
          $etym = $dDoc->getElementsByTagName("span")->item(0);
          if ($etym) {
            $etym->setAttribute("class", "etym");
            $etymNode = $htmlDomDoc->importNode($etym,true);
            $lemmaDefNode->appendChild($etymNode);
          } else {
            error_log("unable to encode etymology for lemma id ".$lemma->getGlobalID());
          }
        }
        // output Translation
        if ($lemmaGloss = $lemma->getTranslation()) {
          $lemmaGloss = html_entity_decode($lemmaGloss);
          $dDoc = new DOMDocument();
          $dDoc->loadXML("<span>$lemmaGloss</span>",LIBXML_NOXMLDECL);
          $gloss = $dDoc->getElementsByTagName("span")->item(0);
          if ($gloss) {
            $gloss->setAttribute("class", "gloss");
            $glossNode = $htmlDomDoc->importNode($gloss,true);
            $lemmaDefNode->appendChild($glossNode);
          } else {
            error_log("unable to encode gloss for lemma id ".$lemma->getGlobalID());
          }
        }
        // output any lemma notes
        if ( $linkedAnoIDsByType = $lemma->getLinkedAnnotationsByType()) {
          if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
            $lemmaCommentary = "";
            foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
              $annotation = new Annotation($anoID);
              $comment = $annotation->getText();
              if ($comment) {
                if ($lemmaCommentary) {//Already output a comment so need a space delimitor
                  $lemmaCommentary .= " ";
                }
                $lemmaCommentary .= $comment;
              }
            }
            if ($lemmaCommentary) {// if commentary create a node for it
              $dDoc = new DOMDocument();
              if ($dDoc->loadXML("<span>($lemmaCommentary)</span>",LIBXML_NOXMLDECL)) {
                $commentLemma = $dDoc->getElementsByTagName("span")->item(0);
                if ($commentLemma) {
                  $commentLemma->setAttribute("class", "lemmaCommentary");
                  $commentLemmaNode = $htmlDomDoc->importNode($commentLemma,true);
                  $lemmaDefNode->appendChild($commentLemmaNode);
                } else {
                  error_log("unable to add comment to $lemTag likely ill formatted html $lemmaCommentary");
                }
              } else {
                error_log("unable to add comment to $lemTag likely ill formatted html $lemmaCommentary");
              }
            }
          }
        }
        // tranforms for attest form value
        $pattern = array("/aʔi/","/aʔu/","/ʔ/","/°/","/\/\/\//","/#/","/◊/","/◈/","/◯/");
        $replacement = array("aï","aü","","","","","","","");
        // Start calculation for displaying morphology and attested forms for this lemma
        $lemmaComponents = $lemma->getComponents(true);
        if ($lemmaComponents && $lemmaComponents->getCount() && !$lemmaComponents->getError()) {
          $hasAttestations = true; // signal see also
          //init tree to show attested word inflection groups for the current lemma
          $morphGroupTreeRoot = array();
          $uncertainBranchNode = &$morphGroupTreeRoot;
          $uncertainBranchNeedsInit = true;
          // categories used and display ordering is determined by the lemma POS
          list($catNames,$catDisplayOrders) = getPOSInfCategoryDisplayInfo($lemmaPos,$lemmaSpos);
          $cntCatNames = 0;
          if ($catNames && is_array($catNames)) {
            $cntCatNames = count($catNames);
          }
          //process each component and create keyed groups for the atteseted forms including uninflected
          foreach ($lemmaComponents as $lemmaComponent) {
            if ($lemmaComponent->isMarkedDelete()) {
              continue;
            }
            $entPrefix = $lemmaComponent->getEntityTypeCode();
            $entID = $lemmaComponent->getID();
            if ($entPrefix == 'inf') {//inflections
              $inflection = $lemmaComponent;
              //inflection certainty order = {'tense'0,'voice'1,'mood'2,'gender':3,'num'4,'case'5,'person'6,'conj2nd'7};
              $ingCF = $inflection->getCertainty();
              $case = $gen = $num = $vper = $vvoice = $vmood = $conj2 = null;
              //transform morphology codes of categories used into text form
              // build a sparse tree of display ordered category values
              // set reference to morphology branch for this inflection
              $curInflBranchNode = &$morphGroupTreeRoot;
              foreach ($catNames as $catName) {
                switch ($catName) {
                  case 'case':
                    $case = $inflection->getCase();
                    if ($case && is_numeric($case)) {
                      $curKey = $termLookup[$case].($ingCF[5]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'gen':
                    $gen = $inflection->getGender();
                    if ($gen && is_numeric($gen)) {
                      $curKey = $termLookup[$gen].($ingCF[3]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'num':
                    $num = $inflection->getGramaticalNumber();
                    if ($num && is_numeric($num)) {
                      $curKey = $termLookup[$num].($ingCF[4]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'pers':
                    $vper = $inflection->getVerbalPerson();
                    if ($vper && is_numeric($vper)) {
                      $curKey = $termLookup[$vper].($ingCF[6]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'voice':
                    $vvoice = $inflection->getVerbalVoice();
                    if ($vvoice && is_numeric($vvoice)) {
                      $curKey = $termLookup[$vvoice].($ingCF[1]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'tense':
                    $vtense = $inflection->getVerbalTense();
                    if ($vtense && is_numeric($vtense)) {
                      $curKey = $termLookup[$vtense].($ingCF[0]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'mood':
                    $vmood = $inflection->getVerbalMood();
                    if ($vmood && is_numeric($vmood)) {
                      $curKey = $termLookup[$vmood].($ingCF[2]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  case 'conj':
                    $conj2 = $inflection->getSecondConjugation();
                    if ($conj2 && is_numeric($conj2)) {
                      $curKey = $termLookup[$conj2].($ingCF[7]==2?'(?)':'');
                    } else {
                      $curKey = '?';
                    }
                    break;
                  default:
                  $curKey = 'err?';
                }
                if (!array_key_exists($curKey,$curInflBranchNode)) {
                  $curInflBranchNode[$curKey] = array();
                }
                // advance morphology branch node pointer
                $curInflBranchNode = &$curInflBranchNode[$curKey];
              }
              $node = &$curInflBranchNode;
              $inflectionComponents = $inflection->getComponents(true);
              //process this inflection's attestations
              foreach ($inflectionComponents as $inflectionComponent) {
                if ($inflectionComponent->isMarkedDelete()) {
                  continue;
                }
                //guard code
                if (!$inflectionComponent->getID()){ //skip unreadable links
                  continue;
                }
                if ($useTranscription) {
                  $value = $inflectionComponent->getTranscription();
                } else {
                  $value = $inflectionComponent->getValue();
                }
                if ($hideHyphens) {
                  $value = preg_replace("/\-/","",$value);
                }
                //clean value of attested form
                $value = preg_replace($pattern,$replacement,$value);
                $sc = $inflectionComponent->getSortCode();
                $entTag = preg_replace("/:/","",$inflectionComponent->getGlobalID());
                $attestedCommentary = "";
                if ( $linkedAnoIDsByType = $inflectionComponent->getLinkedAnnotationsByType()) {
                  if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
                    foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
                      $annotation = new Annotation($anoID);
                      $comment = $annotation->getText();
                      if ($comment) {
                        if ($attestedCommentary) {
                          $attestedCommentary .= " ";
                        }
                        $attestedCommentary .= $comment;
                      }
                    }
                  }
                }
                if (is_numeric($refresh) && $refresh > 1) {//ensure location label is current
                  $inflectionComponent->updateLocationLabel();
                }
                //attach attested comment to location this will sort out separately
                $loc = $inflectionComponent->getLocation().($attestedCommentary?" (".$attestedCommentary.")":"");
                //accumulate locations for this inflection node as json ld 
                // {'65792432'=> {'value'=> {'bhagava' => {'loc' => {'4r11'=> 1}}}}}
                //this allows sorting at each level in hierarchy and counts for multiple forms at same line location
                if (!array_key_exists($sc,$node)) {
                  $node[$sc] = array('value'=>
                                       array($value =>
                                              array('loc'=>
                                                     array())));
                } else if (!array_key_exists($value,$node[$sc]['value'])) {
                  $node[$sc]['value'][$value] =  array('loc'=>
                                                        array());
                }
                if (array_key_exists($loc,$node[$sc]['value'][$value]['loc'])) {
                  $node[$sc]['value'][$value]['loc'][$loc] += 1;
                } else {
                  $node[$sc]['value'][$value]['loc'][$loc] = 1;
                }
              }
            } else { //un-inflected inflectible form
              if ($useTranscription) {
                $value = $lemmaComponent->getTranscription();
              } else {
                $value = $lemmaComponent->getValue();
              }
              if (!$value) {
                continue;
              }
              if ($hideHyphens) {
                $value = preg_replace("/\-/","",$value);
              }
              $value = preg_replace($pattern,$replacement,$value);
              $sc = $lemmaComponent->getSortCode();
              $entTag = preg_replace("/:/","",$lemmaComponent->getGlobalID());
              $attestedCommentary = "";
              if ( $linkedAnoIDsByType = $lemmaComponent->getLinkedAnnotationsByType()) {
                if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
                  foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
                    $annotation = new Annotation($anoID);
                    $comment = $annotation->getText();
                    if ($comment) {
                      if ($attestedCommentary) {
                        $attestedCommentary .= " ";
                      }
                      $attestedCommentary .= $comment;
                    }
                  }
                }
              }
              if (is_numeric($refresh) && $refresh > 1) {//ensure label is current
                $lemmaComponent->updateLocationLabel();
              }
              $loc = $lemmaComponent->getLocation().($attestedCommentary?" (".$attestedCommentary.")":"");
              if ($uncertainBranchNeedsInit) {
                $uncertainBranchNeedsInit = false;
                for ($i = 0; $i < $cntCatNames; $i++) {
                  if (!array_key_exists('?',$uncertainBranchNode)) {
                    $uncertainBranchNode['?'] = array();
                  }
                  // advance morphology branch node pointer
                  $uncertainBranchNode = &$uncertainBranchNode['?'];
                }
              }
              $node = &$uncertainBranchNode;
              // accumulate sort codes and locations
              if (! array_key_exists($sc,$node)) {
                $node[$sc] = array('value'=>
                                     array($value =>
                                            array('loc'=>
                                                   array())));
              } else if (! array_key_exists($value,$node[$sc]['value'])) {
                $node[$sc]['value'][$value] =  array('loc'=>
                                                     array());
              }
              if (array_key_exists($loc,$node[$sc]['value'][$value]['loc'])) {
                $node[$sc]['value'][$value]['loc'][$loc] += 1;
              } else {
                $node[$sc]['value'][$value]['loc'][$loc] = 1;
              }
            }
          }
          //now grouping is complete, order for display by group by walking tree
          //according to the display orderings

          //calculate html for attestforms
          $attestedHtml = "<div class=\"attestedforms $lemTag\">";
          if ($catDisplayOrders) {
            $cntCategories = count($catDisplayOrders);
            $isFirstInflectionHeader = true;
            $catCnts = array();
            $catPtr = array();
            $prevCatPtr = array();
            foreach ($catDisplayOrders as $catOrder) {
              array_push($catCnts, count($catOrder));
              array_push($catPtr, 0);
              array_push($prevCatPtr, -1);
            }
            //find all group nodes in display order
            while ($catPtr[0] < $catCnts[0]) {
              $nodePtr = &$morphGroupTreeRoot;
              //from low to high index test pointer group key exist in grouped attested forms
              $i = 0;
              for ($i; $i < $cntCategories; $i++) {
                //if when non existent break
                if (!isset($nodePtr[$catDisplayOrders[$i][$catPtr[$i]]])) {
                  break;
                }
                //walk the branch
                $nodePtr = &$nodePtr[$catDisplayOrders[$i][$catPtr[$i]]];
              }
              //if all index find valid group process group
              if ($i == $cntCategories) {
                //process node
                //output header
                if ($isFirstInflectionHeader) { //first inflection Header so no separator
                  $isFirstInflectionHeader = false;
                } else { //output inflection separator
                  $attestedHtml .= "<span class=\"inflectsep\">;</span>";
                }
                //calc header using previous found node indicies low to high output non matching
                $inflectionHeaderHtml = "";
                $inflValsInsync = true;
                for ($j = 0; $j < $cntCategories; $j++) {
                  //if prevPtr index value is the same as the catPtr then skip
                  if ($inflValsInsync && $catPtr[$j] == $prevCatPtr[$j]) {
                    continue;
                  } else {
                    $inflValsInsync = false;
                  }
                  //get inflection category value
                  $inflCatVal = $catDisplayOrders[$j][$catPtr[$j]];
                  if ($inflCatVal == '?') {
                    continue;
                  }
                  //add html element string
                  $inflectionHeaderHtml .= "<span class=\"inflectdescript\">$inflCatVal</span>";
                }
                //if no inflection header then we must be unclear values
                if ($inflectionHeaderHtml == '') {
                  $inflectionHeaderHtml = "<span class=\"inflectdescript unclear\">unclear: </span>";
                }
                $attestedHtml .= $inflectionHeaderHtml;
                ksort($nodePtr);
                $isFirstNode = true;
                foreach ($nodePtr as $sc => $formInfo) {
                  if ($isFirstNode) {
                    $isFirstNode = false;
                  } else {
                    $attestedHtml .= ",";
                  }
                  $isFirstForm = true;
                  if (! array_key_exists('value',$formInfo)) {
                    error_log(print_r($formInfo));
                  }
                  foreach ($formInfo['value'] as $formTranscr => $locInfo) {
                    if ($isFirstForm) {
                      $isFirstForm = false;
                    } else {
                      $attestedHtml .= ",";
                    }
                    $attestedHtml .= "<span class=\"attestedform\">$formTranscr</span>";
                    $sortedLocs = array_keys($locInfo['loc']);
                    usort($sortedLocs,"compareWordLocations");
                    $isFirstLoc = true;
                    foreach ($sortedLocs as $formLoc) {
                      $cntLoc = $locInfo['loc'][$formLoc];
                      //remove internal ordinal
                      $locParts = explode(":",$formLoc);
                      if (count($locParts) == 3) {
                        if (strpos($locParts[0],"sort") === 0) {//special sort tagging
                          $formLoc = $locParts[2];
                        } else {//if label separator is defined then prepend text label
                          $formLoc = ($sepLabel !== null?$locParts[0].$sepLabel:'').$locParts[2];
                        }
                      } else if (count($locParts) == 2) {
                        $formLoc = $locParts[1];
                      }
                      if ($isFirstLoc) {
                        $isFirstLoc = false;
                        $attestedHtml .= "<span class=\"attestedformloc\">".html_entity_decode($formLoc).($cntLoc>1?" [".$cntLoc."×]":"");
                      } else {
                        $attestedHtml .= ",</span><span class=\"attestedformloc\">".html_entity_decode($formLoc).($cntLoc>1?" [".$cntLoc."×]":"");
                      }
                    }
                    $attestedHtml .= "</span>";
                  }
                }
                //set cat index to last category index
                $i = $cntCategories - 1;
                //save current catPtr
                $prevCatPtr = $catPtr;
              }
              //from high to low starting at current $i index increment pointer[index]
              for ($k = $i; $k>=0; $k--) {
                //increment ith pointer value
                $catPtr[$k]++;
                //if value is less than ith count then break else set to zero
                if ($catPtr[$k] < $catCnts[$k] || $k == 0) {
                  break;
                }
                $catPtr[$k] = 0;
              }
            }
          } else { //uninflectibles
            $nodePtr = $uncertainBranchNode;
            ksort($nodePtr);
            $isFirstNode = true;
            foreach ($nodePtr as $sc => $formInfo) {
              if ($isFirstNode) {
                $isFirstNode = false;
              } else {
                $attestedHtml .= ",";
              }
              $isFirstForm = true;
              if (! array_key_exists('value',$formInfo)) {
                error_log(print_r($formInfo));
              }
              foreach ($formInfo['value'] as $formTranscr => $locInfo) {
                if ($isFirstForm) {
                  $isFirstForm = false;
                } else {
                  $attestedHtml .= ",";
                }
                $attestedHtml .= "<span class=\"attestedform\">$formTranscr</span>";
                $sortedLocs = array_keys($locInfo['loc']);
                usort($sortedLocs,"compareWordLocations");
                $isFirstLoc = true;
                foreach ($sortedLocs as $formLoc) {
                  $cntLoc = $locInfo['loc'][$formLoc];
                  //remove internal ordinal
                  $locParts = explode(":",$formLoc);
                  if (count($locParts) == 3) {
                    if (strpos($locParts[0],"sort") === 0) {//special sort tagging
                      $formLoc = $locParts[2];
                    } else {//if label separator is defined then prepend text label
                      $formLoc = ($sepLabel !== null?$locParts[0].$sepLabel:'').$locParts[2];
                    }
                  } else if (count($locParts) == 2) {
                    $formLoc = $locParts[1];
                  }
                  if ($isFirstLoc) {
                    $isFirstLoc = false;
                    $attestedHtml .= "<span class=\"attestedformloc\">".html_entity_decode($formLoc).($cntLoc>1?" [".$cntLoc."×]":"");
                  } else {
                    $attestedHtml .= ",</span><span class=\"attestedformloc\">".html_entity_decode($formLoc).($cntLoc>1?" [".$cntLoc."×]":"");
                  }
                }
                $attestedHtml .= "</span>";
              }
            }
          }
          $attestedHtml .= ".</div>";
          $dDoc = new DOMDocument();
          $dDoc->loadXML($attestedHtml,LIBXML_NOXMLDECL);
          $attestedForms = $dDoc->getElementsByTagName("div")->item(0);
          $attestedFormsNode = $htmlDomDoc->importNode($attestedForms,true);
          $entryNode->appendChild($attestedFormsNode);
        }
        //calculate related links
        $relatedGIDsByLinkType = $lemma->getRelatedEntitiesByLinkType();
        $seeLinkTypeID = Entity::getIDofTermParentLabel('See-LemmaLinkage');
        $cfLinkTypeID = Entity::getIDofTermParentLabel('Compare-LemmaLinkage');
        $altLinkTypeID = Entity::getIDofTermParentLabel('Alternate-LemmaLinkage');
        $relatedHtml = "";
        if ($relatedGIDsByLinkType && array_key_exists($seeLinkTypeID,$relatedGIDsByLinkType)) {
          $isFirst = true;
          $linksHeader = "<span class=\"lemmaLinksHeader seeLinksHeader\">See</span>";
          if ($hasAttestations) {
            $linksHeader = "<span class=\"lemmaLinksHeader seeAlsoLinksHeader\">See also</span>";
          }
          $seeLinks = array();
          foreach ($relatedGIDsByLinkType[$seeLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            $linkTag = str_replace(':','',$linkGID);
            if ($entity && !$entity->hasError()) {
              if (method_exists($entity,'getValue')) {
                $value = preg_replace($pattern,$replacement,$entity->getValue());
              } else {
                continue;
              }
              if (method_exists($entity,'getSortCode')) {
                $sort = $entity->getSortCode();
              } else {
                $sort = substr($linkGID,4);
              }
              $seeLinks[$sort] = "<span class=\"seeLink $linkTag\"><a href=\"#$linkTag\">$value</a></span>";
            }
          }
          if (count($seeLinks)) {
            $relatedHtml .= "<div class=\"lemmaSeeLinks $lemTag\">";
            uksort($seeLinks,"compareSortKeys");
            foreach ($seeLinks as $sort => $linkHtml) {
              if ($isFirst) {
                $isFirst = false;
                $relatedHtml .= $linksHeader." ".$linkHtml;
              }else{
                $relatedHtml .= ",".$linkHtml;
              }
            }
            $relatedHtml .= ".</div>";
          }
        }
        $cfLinks = array();
        if ($relatedGIDsByLinkType && array_key_exists($cfLinkTypeID,$relatedGIDsByLinkType)) {
          $isFirst = true;
          $linksHeader = "<span class=\"lemmaLinksHeader cfLinksHeader\">Cf.</span>";
          foreach ($relatedGIDsByLinkType[$cfLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            $linkTag = str_replace(':','',$linkGID);
            if ($entity && !$entity->hasError()) {
              if (method_exists($entity,'getValue')) {
                $value = preg_replace($pattern,$replacement,$entity->getValue());
              } else {
                continue;
              }
              if (method_exists($entity,'getSortCode')) {
                $sort = $entity->getSortCode();
              } else {
                $sort = substr($linkGID,4);
              }
              $cfLinks[$sort] = "<span class=\"cfLink $linkTag\"><a href=\"#$linkTag\">$value</a></span>";
            }
          }
          if (count($cfLinks)) {
            $relatedHtml .= "<div class=\"lemmaCfLinks $lemTag\">";
            uksort($cfLinks,"compareSortKeys");
            foreach ($cfLinks as $sort => $linkHtml) {
              if ($isFirst) {
                $isFirst = false;
                $relatedHtml .= $linksHeader." ".$linkHtml;
              }else{
                $relatedHtml .= ",".$linkHtml;
              }
            }
            $relatedHtml .= ".</div>";
          }
        }
        $altLinks = array();
        if ($relatedGIDsByLinkType && array_key_exists($altLinkTypeID,$relatedGIDsByLinkType)) {
          $isFirst = true;
          $linksHeader = "<span class=\"lemmaLinksHeader altLinksHeader\">Alt.</span>";
          foreach ($relatedGIDsByLinkType[$altLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            $linkTag = str_replace(':','',$linkGID);
            if ($entity && !$entity->hasError()) {
              if (method_exists($entity,'getValue')) {
                $value = preg_replace($pattern,$replacement,$entity->getValue());
              } else {
                continue;
              }
              if (method_exists($entity,'getSortCode')) {
                $sort = $entity->getSortCode();
              } else {
                $sort = substr($linkGID,4);
              }
              $altLinks[$sort] = "<span class=\"cfLink $linkTag\"><a href=\"#$linkTag\">$value</a></span>";
            }
          }
          if (count($altLinks)) {
            $relatedHtml .= "<div class=\"lemmaAltLinks $lemTag\">";
            uksort($altLinks,"compareSortKeys");
            foreach ($altLinks as $sort => $linkHtml) {
              if ($isFirst) {
                $isFirst = false;
                $relatedHtml .= $linksHeader." ".$linkHtml;
              }else{
                $relatedHtml .= ",".$linkHtml;
              }
            }
            $relatedHtml .= ".</div>";
          }
        }
        if ($relatedHtml !== "") {
          $relatedHtml = "<div class=\"lemmaLinks\">$relatedHtml</div>";
          $dDoc = new DOMDocument();
          $dDoc->loadXML($relatedHtml,LIBXML_NOXMLDECL);
          $relatedLinks = $dDoc->getElementsByTagName("div")->item(0);
          $relatedLinksNode = $htmlDomDoc->importNode($relatedLinks,true);
          $entryNode->appendChild($relatedLinksNode);
        }
      }
      $htmlGlossary = $htmlDomDoc->saveHTML();
      $catalog->storeScratchProperty('html',$htmlGlossary);
      $catalog->save();
    } // lemma count > 0
  }
  if ($htmlGlossary){
    if(!$isStaticView) {
     $htmlGlossary = preg_replace('/link rel\="stylesheet" href\="\.\/css\/exGlossary\.css" type="text\/css"/',
            'link rel="stylesheet" href="'.SITE_BASE_PATH.'/common/css/exGlossary.css" type="text/css"',$htmlGlossary);
    }
    return array("success", $htmlGlossary);
  }
  return array("error", "unable to caclulate glossary html");
}

?>
