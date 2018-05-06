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
require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get utilies
require_once dirname(__FILE__) . '/../../model/entities/Terms.php';
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
      } else if (($refresh || $jsonCache->isDirty()) && !$jsonCache->isReadonly()) {
        $jsonCache->setJsonString(json_encode(getWrdTag2GlossaryPopupHtmlLookup($catID, $scopeEdnID, $refresh || $jsonCache->isDirty(), $urlTemp)));
        $jsonCache->clearDirtyBit();
        $jsonCache->save();
      } else {
        error_log("warning: cache $cacheKey with cacheID (".($jsonCache->getID()?$jsonCache->getID():"null").
                  ") fall through with refresh = $refresh, dirty = ".$jsonCache->isDirty()." and readonly =".$jsonCache->isReadonly());
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
  global $graID2LineHtmlMarkerlMap, $wordCnt;
  $footnoteHtml = "";
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
      foreach ($edSeqs as $edSequence) {
        $seqType = $edSequence->getType();
        if (!$textAnalysisSeq && $seqType == "Analysis"){//warning!!!! term dependency
          $textAnalysisSeq = $edSequence;
        }
      }
      if ($textAnalysisSeq && $textAnalysisSeq->getEntityIDs() && count($textAnalysisSeq->getEntityIDs()) > 0) {
        getSequenceAnnotationTypes($textAnalysisSeq);
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
          $structureHtml .= getStructTransHtml($subSequence);
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
        error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id="+$edition->getID());
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
  foreach ($ednIDs as $ednID) {//accumulate in order all subsequence for text, text physical and analysis
    $edition = new Edition($ednID);
    if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
      error_log("Warning edition id $ednID not accessible. Skipping.".
        ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
      continue;
    } else {
      $ord = $edition->getScratchProperty('ordinal');
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
      if ($ord && is_numeric($ord)) {
        $sort = $ord;
      } else if ($date) {
        $sort = $date;
      } else {
        $sort = intval($ednID)*10000;
      }
      if ($edition->isResearchEdition()) {
        $researchEditionsHTML[$ednID] = "<button id=\"edn$ednID\" class=\"textEdnButton research\" title=\"$descr\">$hdrText</button>";
        $researchSortKeys[$sort] = $ednID;
      } else { // published ??
        $publishedEditionsHTML[$ednID] = "<button id=\"edn$ednID\" class=\"textEdnButton published\" title=\"$descr\">$hdrText</button>";
        $pubSortKeys[$sort] = $ednID;
      }
    }
  }
  //create headerDiv Html
  $html = "<div id=\"multiEditionHeaderMenu\">";
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
  foreach ($ednIDs as $ednID) {//accumulate in order all subsequences for text, text physical and analysis
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
    $fnCnt = 0;
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
        error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id="+$edition->getID());
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
      if ($isFrist) {
        $sourceHtml .= "<span class=\"sourceitem atb$atbID\">$title";
      } else {
        $sourceHtml .= ",</span><span class=\"sourceitem atb$atbID\">$title";
      }
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
        foreach ($linkedAnoIDsByType[$typeID] as $anoID) {
          $annotation = new Annotation($anoID);
          $anoText = $annotation->getText();
          if ($anoText) {
            $fnTag = "ano".$anoID;
            $typeTag = "trm".$typeID;
            $boundaryHtml .= "<span class=\"boundaryNote $entTag $typeTag $fnTag\" >$anoText</sup>";
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
function getEntityFootnotesHtml($entity) {
  global $fnRefTofnText, $typeIDs, $fnCnt;
  $fnHtml = "";
  $entTag = $entity->getEntityTag();
  if ($linkedAnoIDsByType = $entity->getLinkedAnnotationsByType()) {
    foreach ($typeIDs as $typeID) {
      if (array_key_exists($typeID,$linkedAnoIDsByType)) {
        foreach ($linkedAnoIDsByType[$typeID] as $anoID) {
          $annotation = new Annotation($anoID);
          $anoText = $annotation->getText();
          if ($anoText) {
            $fnTag = "ano".$anoID;
            $typeTag = "trm".$typeID;
            $fnOrd = "ord$fnCnt";
            $fnHtml .= "<sup id=\"$fnTag\" class=\"footnote $entTag $typeTag $fnOrd\" >n</sup>";
            $fnRefTofnText[$fnTag] = $anoText;
          }
        }
      }
    }
  }
  return $fnHtml;
}

/**
* returns array of polygons for a word
*
* @param object $token
* @param string $entTag for the entity containing this token
* @param boolean $isFinalToken defining this is last token of $entTag entity
*
*/
function addWordPolygonsToEntityLookup($token, $entTag, $isFinalToken) {
  global $sclTag2BlnPolyMap, $blnPosByEntTag, $polysByBlnTagTokCmpTag, $sclTagLineStart, $curPoints, $curBlnTag;
  $sclIDs = $token->getSyllableClusterIDs();
  if (count($sclIDs) > 0) {
    //for each syllable of a token
    $blnTag = $polygons = $segPolygon = null;
    if ($curBlnTag == null) {
        $curPoints = array();
    }
    foreach ($sclIDs as $sclID) {
      //get bln from calculated map
      $sclTag = 'scl'.$sclID;
      $segPoints = array();
      if (array_key_exists($sclTag,$sclTag2BlnPolyMap)) {
        list($blnTag,$polygons) = $sclTag2BlnPolyMap[$sclTag];//todo adjust for cross baseline syllables
        if ($polygons && count($polygons) > 0) {
          $polygon = $polygons[0];
          if (is_a($polygon,'Polygon')) {
            $segPoints = $polygon->getBoundingRect();
          } else {
            $segPoints = getBoundingRect($polygon);
          }
        }
      }
      //if switching baseline or spanning new line then close current polygon and clear current points.
      if( $curBlnTag && $curBlnTag != $blnTag || in_array($sclTag, $sclTagLineStart)) {
        if (count($curPoints) > 0) {//existing point accumulation so save bounding bbox
          if (!array_key_exists($curBlnTag,$polysByBlnTagTokCmpTag)) {
            $polysByBlnTagTokCmpTag[$curBlnTag] = array($entTag => array(pointsArray2ArrayOfTuples(getBoundingRect($curPoints))));
          } else if (!array_key_exists($entTag,$polysByBlnTagTokCmpTag[$curBlnTag])) {
            $polysByBlnTagTokCmpTag[$curBlnTag][$entTag] = array(pointsArray2ArrayOfTuples(getBoundingRect($curPoints)));
          } else {
            array_push($polysByBlnTagTokCmpTag[$curBlnTag][$entTag],pointsArray2ArrayOfTuples(getBoundingRect($curPoints)));
          }
          $curPoints = array();
        }
        $curBlnTag = $blnTag;
      } else if (!$curBlnTag) {
        $curBlnTag = $blnTag;
      }
      // aggregate segment points

      $curPoints = array_merge($curPoints,$segPoints);
    }
    //if final token then store points as bndRect tuples and store entity baseline position data
    if ($isFinalToken && $curBlnTag) {
      if (!array_key_exists($curBlnTag,$polysByBlnTagTokCmpTag)) {
        $polysByBlnTagTokCmpTag[$curBlnTag] = array($entTag => array(pointsArray2ArrayOfTuples(getBoundingRect($curPoints))));
      } else if (!array_key_exists($entTag,$polysByBlnTagTokCmpTag[$curBlnTag])) {
        $polysByBlnTagTokCmpTag[$curBlnTag][$entTag] = array(pointsArray2ArrayOfTuples(getBoundingRect($curPoints)));
      } else {
        array_push($polysByBlnTagTokCmpTag[$curBlnTag][$entTag],pointsArray2ArrayOfTuples(getBoundingRect($curPoints)));
      }
      $curPoints = array();
      if (array_key_exists($curBlnTag,$polysByBlnTagTokCmpTag)
          && array_key_exists($entTag,$polysByBlnTagTokCmpTag[$curBlnTag])) {
        $entULPoints = $polysByBlnTagTokCmpTag[$curBlnTag][$entTag][0][0];
        $entLRPoints = $polysByBlnTagTokCmpTag[$curBlnTag][$entTag][0][2];
        $blnPosByEntTag['word'][$entTag] = array('blnTag'=>$blnTag,'x'=>$entULPoints[0],'y'=>$entULPoints[1],'h'=>($entLRPoints[1]-$entULPoints[1]));
      }
    }
  }
}

/**
* returns the Html for a word
*
* @param object $entity Compound or Token
* @param boolean $isLastStructureWord
* @param object or null $nextToken
* @param string $ctxClass is added to the class attribute of the html node
* @return string Html representing the $entity
*/
function getWordHtml($entity, $isLastStructureWord, $nextToken = null, $ctxClass = '') {
  global $prevTCMS, $graID2LineHtmlMarkerlMap, $wordCnt;
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
    //open word span
    $wordHtml .= '<span class="grpTok '.($ctxClass?$ctxClass.' ':'').$entTag.' ord'.$wordCnt.'">';
    //for each token in word
    $tokCnt = count($tokIDs);
    $prevGraID = null;
    for($i =0; $i < $tokCnt; $i++) {
      $tokID = $tokIDs[$i];
      $token = new Token($tokID);
      addWordPolygonsToEntityLookup($token, $entTag, ($i == ($tokCnt - 1)));
      $graIDs = $token->getGraphemeIDs();
      $firstT = ($i==0);
      $lastT = (1+$i == $tokCnt);
      //for each grapheme in token
      $graCnt = count($graIDs);
      for($j=0; $j<$graCnt; $j++) {
        $graID = $graIDs[$j];
        if ($prevGraID == $graID){//sandhi case of repeated grapheme
          continue;
        }
        $grapheme = new Grapheme($graID);
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
        $lastG = (1+$j == $graCnt);
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
        if ($graID && array_key_exists($graID,$graID2LineHtmlMarkerlMap)) {
          if ( $i == 0 && $firstG) {
            $wordHtml = $graID2LineHtmlMarkerlMap[$graID].$wordHtml;
          } else {
            $wordHtml .= $graID2LineHtmlMarkerlMap[$graID];
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
        $prevGraID = $graID;
        if ($graTemp == "a") {
          $previousA = true;
        } else {
          $previousA = false;
        }
        $prevGraIsVowelCarrier = false;
      }//end for graphIDs
      $footnoteHtml = getEntityFootnotesHtml($token);
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
      $footnoteHtml .= getEntityFootnotesHtml($entity);
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
function getStructHTML($sequence, $addBoundaryHtml = false) {
  global $edition, $editionTOCHtml, $seqBoundaryMarkerHtmlLookup, $blnPosByEntTag, $curStructHeaderbyLevel;
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
    $structureHtml .= '<div id="'.$seqTag.'" class="secHeader level'.$level.' '.$seqType.' '.$seqTag.'">'.$label;
    $structureHtml .= getEntityFootnotesHtml($sequence);
    $structureHtml .= '</div>';
    if ($seqType == "Chapter") { //warning term dependency
      $editionTOCHtml .= '<div id="toc'.$seqTag.'" class="tocEntry level'.$level.' '.$seqType.' '.$seqTag.'">'.$label.'</div>';
      $curStructHeaderbyLevel[$level] = array('sup'=>$seqSup,'label'=> $seqLabel);
    }
  }
  //open structure div
  $structureHtml .= '<div class="section level'.$level.' '.$seqTag.' '.$seqType.'">';
  $cntGID = count($seqEntGIDs);
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
        $structureHtml .= getStructHTML($subSequence);
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
        $structureHtml .= getWordHtml($entity,$i+1 == $cntGID, $nextToken);
      }
    }else{
      error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id="+$edition->getID());
      continue;
    }
    if ($i == 0 && array_key_exists($entTag,$blnPosByEntTag[$entType])) {// first contained entity so if baseline position then copy for sequence.
      $blnPosByEntTag['construct'][$seqTag] = $blnPosByEntTag[$entType][$entTag];
    }
  }//end for cntGID
  if (!$label) {//output header div
    $structureHtml .= getEntityFootnotesHtml($sequence);
  }
  $structureHtml .= '</div>';
  return $structureHtml;
}

/**
* returns the html for tokenisation in physical line layout
*
* @param int array of text division $sequence ids containing global ids ordered words of the text
* @param boolean $addBoundaryHtml determine whether to add boundary info for each edition in a multi edition calculation
*/
function getPhysicalLinesHTML($textDivSeqIDs, $addBoundaryHtml = false) {
  global $edition, $editionTOCHtml, $graID2LineHtmlMarkerlMap, $seqBoundaryMarkerHtmlLookup, $curStructHeaderbyLevel;

  $wordCnt = 0;
  $prevTCMS = "";
  $nextTCMS = "";
  $tcms = "";
  $prevGraIsVowelCarrier = false;
  $previousA = null;
  $physicalLinesHtml = "";
  //start to calculate HTML using each text division container
  $cntTxtDivGID = count($textDivSeqIDs);
  for ($i = 0; $i < $cntTxtDivGID; $i++) {
    $seqGID = $textDivSeqIDs[$i];
    if (strpos($seqGID,'seq') === 0) {
      $txtDivSequence = new Sequence(substr($seqGID,4));
      if (!$txtDivSequence || $txtDivSequence->hasError()) {//no sequence or unavailable so warn
        error_log("warn, Warning inaccessible tokenisation sequence $seqGID skipped.");
        continue;
      } else {//process text division
        $fTxtDivSeq = ($i == 0);// also indicate first physical line and first word of this scope
        $nextSeqGID = $i+1<$cntTxtDivGID?$textDivSeqIDs[$i+1]:null;
        $seqEntGIDs = $txtDivSequence->getEntityIDs();
        $seqType = $txtDivSequence->getType();
        $seqTag = $txtDivSequence->getEntityTag();
        //check for boundary entry for this seqTag
        if ($addBoundaryHtml && array_key_exists($seqTag,$seqBoundaryMarkerHtmlLookup)) {
          $physicalLinesHtml .= $seqBoundaryMarkerHtmlLookup[$seqTag];
        }
        if (!$seqEntGIDs || count($seqEntGIDs) == 0) {
          error_log("warn, Found empty tokenisation sequence element $seqTag for edition ".$edition->getDescription()." id=".$edition->getID());
          continue;
        }
        $cntGID = count($seqEntGIDs);
        for ($j = 0; $j < $cntGID; $j++) {//process the words of this text division
          $wordGID = $seqEntGIDs[$j];
          $prefix = substr($wordGID,0,3);
          $wordID = substr($wordGID,4);
          $nextToken = null;
          //find next token for boundary determination
          $nextEntGID = $j+1<$cntGID?$seqEntGIDs[$j+1]:null;
          if (!$nextEntGID && $nextSeqGID) { //get next entGID from next text division
            $nextTxtDivSequence = new Sequence(substr($nextSeqGID,4));
            if (!$nextTxtDivSequence || $nextTxtDivSequence->hasError()) {//no sequence or unavailable so warn
              error_log("warn, Warning inaccessible tokenisation sequence $nextSeqGID next entity set to null.");
            } else {
              $nextSeqEntGIDs = $nextTxtDivSequence->getEntityIDs();
              if ($nextSeqEntGIDs && count($nextSeqEntGIDs) > 0) {
                $nextEntGID = $nextSeqEntGIDs[0];
              }
            }
          }
          if ( $nextEntGID ) {
            switch (substr($nextEntGID,0,3)) {
              case 'cmp':
                $nextToken = new Compound(substr($nextEntGID,4));
                if (!$nextToken || $nextToken->hasError()) {//no sequence or unavailable so warn
                  error_log("Warning inaccessible entity id $nextEntGID skipped.");
                  $nextToken = null;
                  break;
                } else {//change to GID for first token of this compound
                  $nextEntGID = $nextToken->getTokenIDs();
                  if ($nextEntGID && count($nextEntGID)) {//found so change and fall through to token processing
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
          //process current word
          $wordTag = null;
          $tokIDs = null;
          if ($prefix == 'cmp' || $prefix == 'tok' ) {
            if ($prefix == 'cmp') {
              $entity = new Compound($wordID);
            } else {
              $entity = new Token($wordID);
            }
            if (!$entity || $entity->hasError()) {//no word or unavailable so warn
              error_log("Warning inaccessible word id $wordGID skipped.");
            } else {
              $wordTag = $entity->getEntityTag();
              if ($entity && $prefix == 'cmp' && count($entity->getTokenIDs())) {
                $tokIDs = $entity->getTokenIDs();
              } else if ($entity && $prefix == 'tok'){
                $tokIDs = array($wordID);
              } else {
                error_log("err, rendering word for physical line and found invalid GID $wordGID");
                continue;
              }
              $footnoteHtml = "";
              $wordHtml = '<span class="grpTok '.($seqTag?$seqTag.' ':'').$wordTag.' ord'.$wordCnt.'">';
              $isLastWord = (($j+1 == $cntGID) && ($i+1 == $cntTxtDivGID));
              if ($tokIDs) {
                ++$wordCnt;
                //for each token in word
                $tokCnt = count($tokIDs);
                $prevGraID = null;
                for($k =0; $k < $tokCnt; $k++) {
                  $tokID = $tokIDs[$k];
                  $firstT = ($k==0);
                  $lastT = (1+$k == $tokCnt);
                  $token = new Token($tokID);
                  addWordPolygonsToEntityLookup($token, $wordTag, $lastT);
                  $graIDs = $token->getGraphemeIDs();
                  //for each grapheme in token
                  $graCnt = count($graIDs);
                  for($l=0; $l<$graCnt; $l++) {
                    $graID = $graIDs[$l];
                    if ($prevGraID == $graID){//sandhi case of repeated grapheme
                      continue;
                    }
                    $grapheme = new Grapheme($graID);
                    if (!$grapheme) {
                      error_log("err,calculating word html and grapheme not available for graID $graID");
                      $prevGraIsVowelCarrier = false;
                      continue;
                    }
                    if ($grapheme->getValue() == "ʔ") {
                      $prevGraIsVowelCarrier = true;
                      continue;
                    }
                    $isNewPhysicalLine = ($graID && array_key_exists($graID,$graID2LineHtmlMarkerlMap));
                    $firstG = ($l==0 || $l==1 && $prevGraIsVowelCarrier);
                    $lastG = (1+$l == $graCnt);
                    //check for TCM transition brackets
                    $tcms = $grapheme->getTextCriticalMark();
                    $postTCMBrackets = "";
                    $preTCMBrackets = "";
                    if ($prevTCMS != $tcms) {
                      list($postTCMBrackets,$preTCMBrackets) = getTCMTransitionBrackets($prevTCMS,$tcms,true);
                    }
                    if ($isNewPhysicalLine) {//grapheme marks physical line beginning so close previous and start new line
                      $postTCMBrackets = getTCMTransitionBrackets($prevTCMS,"S");
                      if ($postTCMBrackets && !($firstT && $firstG)) {
                        $wordHtml .= $postTCMBrackets;
                      }
                      if ($footnoteHtml) {
                        $wordHtml .= $footnoteHtml;
                        $footnoteHtml = "";
                      }
                      $preTCMBrackets = getTCMTransitionBrackets("S",$tcms);
                      //output current word HTML
                      //if in a compound output hyphen
                      //if not first physical line then close physical line div and start a new line
                      $physicalLinesHtml .= ((!$fTxtDivSeq && $wordHtml && !($firstT && $firstG))?$wordHtml."-</span>":"").
                                            (!$fTxtDivSeq?"</div>":"")."<div class=\"physicalLineDiv\">".$graID2LineHtmlMarkerlMap[$graID];
                      //open word span
                      $wordHtml = '<span class="grpTok '.($seqTag?$seqTag.' ':'').$wordTag.' ord'.$wordCnt.'">';
                      $prevTCMS = "S";//at a new physical line so reset TCM//???need to recalc previous brackets???
                      $previousA = null;
                    } else if ($postTCMBrackets && !($firstT && $firstG)) {// lookahead will close previous token using postBrackets of this grapheme so skip if first of word
                      $wordHtml .= $postTCMBrackets;
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
                    $prevGraID = $graID;
                    if ($graTemp == "a") {
                      $previousA = true;
                    } else {
                      $previousA = false;
                    }
                    $prevGraIsVowelCarrier = false;
                  }//end for graphIDs
                  $footnoteHtml = getEntityFootnotesHtml($token);
                }//end for token IDs
                if ($nextToken) {//find tcm for first grapheme of next token to check for closing brackets
                  $nextGraIDs = $nextToken->getGraphemeIDs();
                  if (count($nextGraIDs) > 0) {
                    $nextGraID = $nextGraIDs[0];
                    $nextGrapheme = new Grapheme($nextGraID);
                    if ($nextGrapheme->getValue() == "ʔ") {
                      $nextGraID = $nextGraIDs[1];
                      $nextGrapheme = new Grapheme($nextGraID);
                    }
                    $nextStartsOnNewLine = ($nextGraID && array_key_exists($nextGraID,$graID2LineHtmlMarkerlMap));
                    $nextTCMS = $nextGrapheme->getTextCriticalMark();
                    if ($nextStartsOnNewLine) {
                      $postTCMBrackets = getTCMTransitionBrackets($tcms,"S");
                      $wordHtml .= $postTCMBrackets;
                    } else if ($nextTCMS != $tcms) {
                      $postTCMBrackets = "";
                      $preTCMBrackets = "";
                      list($postTCMBrackets,$preTCMBrackets) = getTCMTransitionBrackets($tcms,$nextTCMS,true);
                      $wordHtml .= $postTCMBrackets;
                    }
                  }
                }
                if ($isLastWord && $prevTCMS && $prevTCMS != "S") {//close off any TCM
                  $tcmBrackets = getTCMTransitionBrackets($prevTCMS,"S");//reduce to S
                  $prevTCMS = "";//reset since we closed off TCMs for the edition.
                  //This will ensure next structures output will have opening TCMs
                  if ($tcmBrackets) {
                    $wordHtml .= $tcmBrackets;
                  }
                }
                if ($prefix == "cmp") {//end of compound so add cmp entity footnotes
                  $footnoteHtml .= getEntityFootnotesHtml($entity);
                }
                if ($footnoteHtml) {
                  $wordHtml .= $footnoteHtml;
                  $footnoteHtml = "";
                }
                //$wordHtml = preg_replace('/\/\/\//',"",$wordHtml); // remove edge indicator
                $wordHtml = preg_replace('/_+/',"_",$wordHtml); // multple missing consonants
                $wordHtml = preg_replace('/_/',".",$wordHtml); // multple missing consonants
                $wordHtml .= "</span>";
                //      $wordRTF = preg_replace('/\.\./',".",$wordRTF); // multple missing consonants
              }
              $physicalLinesHtml .= $wordHtml;
            }
          }else{
            error_log("warn, Found unknown word element $wordGID for edition ".$edition->getDescription()." id="+$edition->getID());
            continue;
          }
        }//end for cntGID
      }// end  of process text division

    } else {
      error_log("warn, Found unknown tokenisation element $seqGID for edition $ednID");
      continue;
    }
  }// end for txtDivSeqIDs
  return $physicalLinesHtml;
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
function getEditionsStructuralViewHtml($ednIDs, $forceRecalc = false) {
  global $edition, $prevTCMS, $graID2LineHtmlMarkerlMap, $sclTag2BlnPolyMap,
  $wordCnt, $fnRefTofnText, $typeIDs, $fnCnt, $imgURLsbyBlnImgTag, $curBlnTag,
  $sclTagLineStart, $seqBoundaryMarkerHtmlLookup, $curStructHeaderbyLevel,
  $editionTOCHtml, $polysByBlnTagTokCmpTag, $blnPosByEntTag;
  $footnoteHtml = "";
  $wordCnt = 0;
  $curBlnTag = null;
  $seqBoundaryMarkerHtmlLookup = array();
  $curStructHeaderbyLevel = array();
  $graID2LineHtmlMarkerlMap = array();
  $prevTCMS = "";
  $editionTOCHtml = "";
  $warnings = array();
  $typeIDs = array();
  $fnCnt = 0;
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
  $sclTag2BlnPolyMap = array();
  $sclTagLineStart = array();
  $imgURLsbyBlnImgTag = array('img'=>array(),'bln'=>array());
  $isFirstEdn = true;
  $jsonCache = null;
  if (count($ednIDs) == 1) {
    if(USEVIEWERCACHING) {
      $cacheKey = "edn".$ednIDs[0]."structviewHTML";
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->getID() && !$jsonCache->hasError() && !$jsonCache->isDirty() && !$forceRecalc) {
        $cachedEditionData = json_decode($jsonCache->getJsonString(),true);
        //set dependent globals before returning html string
        $fnRefTofnText = $cachedEditionData['fnRefTofnText'];
        $editionTOCHtml = $cachedEditionData['editionTOCHtml'];
        $imgURLsbyBlnImgTag = $cachedEditionData['imgURLsbyBlnImgTag'];
        $blnPosByEntTag = $cachedEditionData['blnPosByEntTag'];
        $polysByBlnTagTokCmpTag = $cachedEditionData['polysByBlnTagTokCmpTag'];
        //return html
        return json_encode($cachedEditionData['editionHtml']);
      } else if ($jsonCache->hasError() || !$jsonCache->getID()) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        if (!$edition) {
          $edition = new Edition($ednID);
        }
        if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
          array_push($warnings,"Warning need valid accessible edition id $ednID. Skipping.".
            ($edition->hasError()?" Error: ".join(",",$edition->getErrors()):""));
          $jsonCache = null;
        } else {
          $jsonCache->setVisibilityIDs($edition->getVisibilityIDs());
          $jsonCache->setOwnerID($edition->getOwnerID());
        }
      }
    }
  }

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
              $imgURLsbyBlnImgTag['img'][$sort]['thumbUrl'] = $info['dirname']."/th".$info['basename'];
            }
          }
        }
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
            if (!$isFirstEdn) {
              $entTag = str_replace(':','',$componentIDs[0]);
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
          } else if (!$seqText && $seqType == "Text"){//warning!!!! term dependency
            $textDivSeqIDs = array_merge($textDivSeqIDs,$componentIDs);
            if (!$isFirstEdn) {
              $entTag = str_replace(':','',$componentIDs[0]);
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
          } else if (!$textAnalysisSeq && $seqType == "Analysis"){//warning!!!! term dependency
            if (!$isFirstEdn) {
              $entTag = str_replace(':','',$componentIDs[0]);
              $seqBoundaryMarkerHtmlLookup[$entTag] = getEntityBoundaryHtml($edition,"partBoundary $entTag");
            }
            $analysisSeqIDs = array_merge($analysisSeqIDs,$componentIDs);
          } else {//ignoring sequence so warn
            array_push($warnings,"Warning no code to handle sequence seq:$edSeqID of type $seqType. Skipping.");
          }
        }
      }
      if ($isFirstEdn) {
        $isFirstEdn = false;
      }
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
    }//endelse valid edtion
  }// end foreach ednID

  $html = "";
  if (count($analysisSeqIDs) == 0 && count($textDivSeqIDs) == 0) {
    array_push($warnings,"Warning no structural analysis found for edition id $ednID. Skipping.");
  } else {//process analysis
    //calculate  post grapheme id to physical line label map
    $useInlineLabel = (count($analysisSeqIDs) > 0 && !USEPHYSICALVIEW);
    if (count($physicalLineSeqIDs) > 0) {
      foreach ($physicalLineSeqIDs as $physicalLineSeqGID) {
        $physicalLineSeq = new Sequence(substr($physicalLineSeqGID,4));
        if (!$physicalLineSeq || $physicalLineSeq->hasError()) {//no $physicalLineSeqIDsequence or unavailable so warn
          array_push($warnings,"Warning unable to load edition $ednID's physicalline sequence seq:$physicalLineSeqGID. Skipping.".
            ($physicalLineSeq->hasError()?" Error: ".join(",",$physicalLineSeq->getErrors()):""));
        } else {
          $seqType = $physicalLineSeq->getType();
          if ($seqType == "FreeText") {
            array_push($warnings,"Warning edition $ednID's sequence seq:$physicalLineSeqGID is a FreeText Line. Skipping line label map.");
            continue;
          } else if ($seqType == "LinePhysical") {
            $sclGIDs = $physicalLineSeq->getEntityIDs();
            if (count($sclGIDs) == 0 || strpos($sclGIDs[0],'scl:') != 0) {
              array_push($warnings,"Warning edition $ednID's sequence seq:$physicalLineSeqGID is a physical Line without syllables ".$physicalLineSeq->getGlobalID());
              continue;
            }
            $isFirstScl = true;
            foreach ($sclGIDs as $sclGID) {
              $syllable = new SyllableCluster(substr($sclGID,4));
              if ($syllable->hasError()) {
                array_push($warnings,"warning, error encountered while trying to open syllable $sclGID for physical line ".$physicalLineSeq->getGlobalID()." of edition $ednID - ".join(",",$syllable->getErrors()));
                continue;
              }
              $segment = $syllable->getSegment(true);
              $sclTag = $syllable->getEntityTag();
              $blnTag = null;
              if ($segment && $polygons = $segment->getImageBoundary()) {//linked to image baseline
                $segBaselines = $segment->getBaselines(true);//todo modify for cross baseline segemnts
                if ($segBaselines && $segBaselines->getCount() > 0) {
                  $sourceLookup = array();
                  $segBaseline = $segBaselines->current();//todo adjust code for cross baseline segments
                  $attributions = $segBaseline->getAttributions(true);
                  if ($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
                    foreach ($attributions as $attribution) {
                      $atbID = $attribution->getID();
                      $title = $attribution->getTitle();
                      if (!array_key_exists($atbID,$sourceLookup)) {
                        $sourceLookup[$atbID] = $title;
                      }
                    }
                  }
                  $blnTag = $segBaseline->getEntityTag();
                  $url = $segBaseline->getURL();
                  $ord = $segBaseline->getScratchProperty('ordinal');
                  $blnImgTag = 'img'.$segBaseline->getImageID();
                  if ($ord) {
                    $sort = $ord;
                  } else {
                    $sort = 1000 * intval($segBaseline->getID());
                  }
                  $imgURLsbyBlnImgTag['bln'][$sort] = array('tag'=>$blnTag, 'url'=>$url, 'imgTag'=>$blnImgTag, 'source'=> $sourceLookup);
                  //$title = substr($url,strrpos($url,'/')+1);//filename
                  $image = $segBaseline->getImage(true);
                  //if ($image && $image->getTitle()) {
                  $title = $image?$image->getTitle():'';
                  //}
                  $imgURLsbyBlnImgTag['bln'][$sort]['title'] = $title?$title:"";
                  if ($url) {
                    $info = pathinfo($url);
                    $imgURLsbyBlnImgTag['bln'][$sort]['thumbUrl'] = $info['dirname']."/th".$info['basename'];
                  }
                  $sclTag2BlnPolyMap[$sclTag] = array($blnTag,$polygons);
                  $bRectPts = $polygons[0]->getBoundingRect();
                }
              }
              if ($isFirstScl) {
                $isFirstScl = false;
                array_push($sclTagLineStart,$sclTag);
                $graIDs = $syllable->getGraphemeIDs();
                if (count($graIDs) == 0 ) {
                  array_push($warnings,"Found syllable without graphemes $sclGID for physical line ".$physicalLineSeq->getGlobalID()." of edition $ednID");
                  continue;
                }
                $label = $physicalLineSeq->getLabel();
                $seqTag = $physicalLineSeq->getEntityTag();
                if ($blnTag) {
                  $blnPosByEntTag['line'][$seqTag] = array('blnTag'=>$blnTag,'x'=>$bRectPts[0],'y'=>$bRectPts[1],'h'=>($bRectPts[5]-$bRectPts[1]));
                }
                if (!$label) {
                  $label = $seqTag;
                }
                if ($useInlineLabel) {
                  $lineHtml = "<span class=\"linelabel $seqTag\">[$label]";
                  $lineHtml .= getEntityFootnotesHtml($physicalLineSeq);//add any line footnotes to end of label
                  $lineHtml .= "</span>";
                } else { //use header format
                  $lineHtml = "<span class=\"lineHeader $seqTag\">$label";
                  $lineHtml .= getEntityFootnotesHtml($physicalLineSeq);//add any line footnotes to end of label
                  $lineHtml .= "</span>";
                }
                if (strpos($syllable->getSortCode(),"0.19")=== 0 &&
                strpos($syllable->getSortCode2(),"0.5")=== 0 &&
                count($graIDs) > 1) { //begins with vowel carrier so choose second grapheme
                  $graID2LineHtmlMarkerlMap[$graIDs[1]] = $lineHtml;
                } else {
                  $graID2LineHtmlMarkerlMap[$graIDs[0]] = $lineHtml;
                }
              }
            }
          }
        }
      }
    }
    $fnCnt = 0;
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
            $html .= getStructHtml($subSequence,true);
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
            $html .= getWordHtml($entity,false);
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
      $html .= getPhysicalLinesHtml($textDivSeqIDs,true);
    }
  }// end else
  $sourceHtml = "";
  if ($sourceNameLookup && count($sourceNameLookup) > 0) {
    $isFrist = true;
    $sourceHtml = "<div class=\"source edn1\"><span class=\"sourcelabel\">Source: </span>";
    foreach ($sourceNameLookup as $atbID => $title) {
      if ($isFrist) {
        $sourceHtml .= "<span class=\"sourceitem atb$atbID\">$title";
      } else {
        $sourceHtml .= ",</span><span class=\"sourceitem atb$atbID\">$title";
      }
    }
    $sourceHtml .= "</span></div>";
  }
  $html .= $sourceHtml;
  if(USEVIEWERCACHING && $jsonCache) {
    $cachedEditionData = array();
    //store dependent globals used to calc html
    $cachedEditionData['fnRefTofnText'] = $fnRefTofnText;
    $cachedEditionData['editionTOCHtml'] = $editionTOCHtml;
    $cachedEditionData['imgURLsbyBlnImgTag'] = $imgURLsbyBlnImgTag;
    $cachedEditionData['blnPosByEntTag'] = $blnPosByEntTag;
    $cachedEditionData['polysByBlnTagTokCmpTag'] = $polysByBlnTagTokCmpTag;
    //save html
    $cachedEditionData['editionHtml'] = $html;
    $jsonCache->setJsonString(json_encode($cachedEditionData));
    $jsonCache->clearDirtyBit();
    $jsonCache->save();
  }
  return json_encode($html);
}


function getWordTagToLocationLabelMap($catalog, $refreshWordMap = false) {
  $textTypeTrmID = Entity::getIDofTermParentLabel('text-sequencetype'); //term dependency
  $physTextTypeTrmID = Entity::getIDofTermParentLabel('textphysical-sequencetype'); //term dependency
  $catID = $catalog->getID();
  if ($catID) {
    $cacheKey = "cat$catID"."wrdTag2LocLabel";
    $jsonCache = null;
    if(!$refreshWordMap && USEVIEWERCACHING) {
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->getID() && !$jsonCache->hasError() && !$jsonCache->isDirty() && !$refreshWordMap) {
        return json_decode($jsonCache->getJsonString(),true);
      } else if ($jsonCache->hasError() || !$jsonCache->getID()) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        $jsonCache->setVisibilityIDs($catalog->getVisibilityIDs());
        $jsonCache->setOwnerID($catalog->getOwnerID());
      }
    } else/**/ if (!$refreshWordMap && array_key_exists("cache-cat$catID".DBNAME,$_SESSION) &&
        array_key_exists('wrdTag2LocLabel',$_SESSION["cache-cat$catID".DBNAME])) {
      return $_SESSION["cache-cat$catID".DBNAME]['wrdTag2LocLabel'];
    }
  }
  $editionIDs = $catalog->getEditionIDs();
  $wrdTag2LocLabel = array();
  $sclTagToLabel = array();
  $ednLblBySeqTag = array();
  foreach ($editionIDs as $ednID) {
    //check for cached json mapping for this edition
    $txtSeqGIDs = array();
    $physSeqGIDs = array();
    $ednLabel = '';
    $ednTag = 'edn'.$ednID;
    $edition = new Edition($ednID);
    if (!$edition->hasError()) {
      $text = $edition->getText(true);
      if ($text && !$text->hasError()) {
        $ednLabel = $text->getRef();
        if (!$ednLabel) {
//          $ednLabel='sort'.$text->getID();
        }
      }
      $ednSequences = $edition->getSequences(true);
      //for edition find token sequences and find physical sequences and create sclID to label
      foreach ($ednSequences as $ednSequence) {
        if ($ednSequence->getTypeID() == $textTypeTrmID) {//term dependency
          $txtSeqGIDs = array_merge($txtSeqGIDs,$ednSequence->getEntityIDs());
        }
        if ($ednSequence->getTypeID() == $physTextTypeTrmID) {//term dependency
          $physSeqGIDs = array_merge($physSeqGIDs,$ednSequence->getEntityIDs());
        }
      }
    } else {
      error_log("error loading edition $ednID : ".$edition->getErrors(true));
      continue;
    }
    if ($txtSeqGIDs && count($txtSeqGIDs)) {// capture each text token sequence once
      foreach ($txtSeqGIDs as $txtSeqGID) {
        $tag = preg_replace("/:/","",$txtSeqGID);
        if (array_key_exists($tag,$ednLblBySeqTag)) {// use label from first edition found with this sequence tag, should never happened if single edition per text
          continue;
        }
        $ednLblBySeqTag[$tag] = $ednLabel;//todo: this overwrites the edition, ?? do we need to associate a line sequence with a primary edition for the reuse case??
      }
    }
    if ($physSeqGIDs && count($physSeqGIDs)) {// capture each physical line sequence once
      $ord = 1;
      foreach ($physSeqGIDs as $physSeqGID) {
        $sequence = new Sequence(substr($physSeqGID,4));
        $label = $sequence->getSuperScript();
        if (!$label) {
          $label = $sequence->getLabel();
        }
        if (!$label) {
          $label = 'seq'.$sequence->getID();
        }
        $sclGIDs = $sequence->getEntityIDs();
        if ($label && count($sclGIDs)) {//create lookup for location of word span B11-B12
          $label = "$ord:".$label; //save ordinal of line for sorting later.
          foreach ($sclGIDs as $sclGID) {
            $tag = preg_replace("/:/","",$sclGID);
            $sclTagToLabel[$tag] = $label;
          }
        }
        $ord++;
      }
    }
  }
  if ($ednLblBySeqTag && count($ednLblBySeqTag) > 0) {
    //for each token sequence
    foreach ($ednLblBySeqTag as $ednSeqTag => $ednLabel) {
      if ($ednLabel) {
        $ednLabel .= ":";
      }
      $sequence = new Sequence(substr($ednSeqTag,3));
      $defLabel = $ednLabel . ($sequence->getSuperScript()?$sequence->getSuperScript():($sequence->getLabel()?$sequence->getLabel():$ednSeqTag));
      $words = $sequence->getEntities(true);
      if ($words && $words->getCount() == 0) {
        error_log("no words for sequence $ednSeqTag having edition label $ednLabel");
        continue;
      }else{
        //error_log("words for sequence $ednSeqTag having edition label $ednLabel include ".join(',',$words->getKeys()));
      }
      //calculate a location label for each word and add to $wrdTag2LocLabel
      foreach ($words as $word) {
        $fSclID = $lSclID = null;
        $prefix = $word->getEntityTypeCode();
        $id = $word->getID();
        $wtag = $prefix.$id;
        if ($word->getSortCode() >= 0.7) {
          continue;
        }
        // find first and last SclID for word to calc attested form location
        if ($prefix == 'cmp') {
          $tokenSet = $word->getTokens();
          $tokens = $tokenSet->getEntities();
          $fToken = $tokens[0];
          $sclIDs = $fToken->getSyllableClusterIDs();
          if (count($sclIDs) > 0) {
            $fSclID = $sclIDs[0];
            $sclTag = 'scl'.$fSclID;
            if ( array_key_exists($sclTag,$sclTagToLabel)) {
              $label = $sclTagToLabel[$sclTag];
            } else {
              $tokID = $fToken->getID();
              error_log("no start label founds for $sclTag of tok$tokID from $prefix$id for sequence $ednSeqTag having label $ednLabel");
              $label = null;
            }
          }
          if ($label) {
            $lToken = $tokens[count($tokens)-1];
            $sclIDs = $lToken->getSyllableClusterIDs();
            if (count($sclIDs) > 0) {
              $lSclID = $sclIDs[count($sclIDs)-1];
              $sclTag = 'scl'.$lSclID;
              if ( array_key_exists($sclTag,$sclTagToLabel)) {
                $label2 = $sclTagToLabel[$sclTag];
              } else {
                $tokID = $lToken->getID();
                error_log("no end label founds for $sclTag of tok$tokID from $prefix$id for sequence $ednSeqTag having label $ednLabel");
                $label2 = null;
              }
              if($label2 && $label2 != $label) {
                $posColon = strpos($label2,':');
                $label .= "&ndash;" . ($posColon !== false?substr($label2, $posColon+1):$label2);
              }
            }
            $wrdTag2LocLabel[$wtag] = $ednLabel . $label;//todo change to edition only calc  for caching by edition
          } else {
            $wrdTag2LocLabel[$wtag] = $defLabel;//todo change to edition only calc  for caching by edition
          }
        } else if ($prefix == 'tok') {
          $sclIDs = $word->getSyllableClusterIDs();
          if (count($sclIDs) > 0) {
            $fSclID = $sclIDs[0];
            $sclTag = 'scl'.$fSclID;
            if ( array_key_exists($sclTag,$sclTagToLabel)) {
              $label = $sclTagToLabel[$sclTag];
            } else {
              error_log("no start label founds for $sclTag processing $prefix$id for sequence $ednSeqTag having label $ednLabel");
              $label = null;
            }
          } else {
            error_log("no syllable IDs found for ".$word->getGlobalID()." processing $prefix$id for sequence $ednSeqTag having label $ednLabel");
            $label = null;
          }
          if ($label) {
            $lSclID = $sclIDs[count($sclIDs)-1];
            $sclTag = 'scl'.$lSclID;
            if ( array_key_exists($sclTag,$sclTagToLabel)) {
              $label2 = $sclTagToLabel[$sclTag];
            } else {
              error_log("no end label founds for $sclTag processing $prefix$id for sequence $ednSeqTag having label $ednLabel");
              $label2 = null;
            }
            if($label2 && $label2 != $label) {
              $posColon = strpos($label2,':');
              $label .= "&ndash;" . ($posColon !== false?substr($label2, $posColon+1):$label2);
            }
            $wrdTag2LocLabel[$wtag] = $ednLabel . $label;//todo change to edition only calc  for caching by edition
          } else {
            $wrdTag2LocLabel[$wtag] = $defLabel;//todo change to edition only calc  for caching by edition
          }
        }
      }
      //TODO if edition mapping is not empty then merge into $wrdTag2LocLabel and update cache for catID ednID
    }
  }
  //update cache for catID word to loc map
  $_SESSION["cache-cat$catID".DBNAME]['wrdTag2LocLabel'] = $wrdTag2LocLabel;
  if (USEVIEWERCACHING && $jsonCache) {
    $jsonCache->setJsonString(json_encode($wrdTag2LocLabel));
    $jsonCache->clearDirtyBit();
    $jsonCache->save();
  }/**/
  return $wrdTag2LocLabel;
}


function getWrdTag2GlossaryPopupHtmlLookup($catID,$scopeEdnID = null,$refreshWordMap = false, $linkTemplate = null, $useTranscription = true, $hideHyphens = true) {
  $catalog = new Catalog($catID);
  global $exportGlossary;
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    error_log("Warning need valid catalog id $catID.");
    return array();
  } else {
    if ($scopeEdnID){
      $textTypeID = Entity::getIDofTermParentLabel('text-sequencetype');
      //find the text division sequence ids for the scoped edition as a comma separated list
      $query = "select regexp_replace(array_to_string(seq_entity_ids,','),'seq:','','g') from edition left join sequence on seq_id = ANY(edn_sequence_ids) where edn_id = $scopeEdnID and seq_type_id = $textTypeID;";
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
         "select * from lemma where not lem_owner_id = 1 and ".
            "cast(lem_component_ids as text[]) && (select array_agg(igids.gid) ".
                          "from (select concat('inf:',inf_id) as gid from inflection where inf_component_ids && ".
                                  "cast((select array_agg(ids.gid) from (select unnest(seq_entity_ids) as gid from sequence ".
                                                                         "where seq_id in ($strTxtDivSeqIDs)) as ids) as text[])) as igids)";
    } else {
      $condition = "lem_catalog_id = $catID and not lem_owner_id = 1";
    }
    $glossaryCommentTypeID = Entity::getIDofTermParentLabel('glossary-commentarytype'); //term dependency
    // word map for the entire catalog - this can take a while if the glossary a large number of editions and tokens.
    $cmpTokTag2LocLabel = getWordTagToLocationLabelMap($catalog,$refreshWordMap);
    $entTag2GlossaryHtml = array();
    $lemmas = new Lemmas($condition,"lem_sort_code,lem_sort_code2",null,null);
    if ($lemmas->getCount() > 0) {
      if ($linkTemplate) {
        $glossaryHRefUrl =$linkTemplate;
      } else if (defined("LEMMALINKTEMPLATE")) {
        $glossaryHRefUrl = LEMMALINKTEMPLATE;//READ_DIR."/plugins/dictionary/?search=%lemval%";
      } else if ($exportGlossary){
        $glossaryHRefUrl = READ_DIR."/services/exportHTMLGlossary.php?db=".DBNAME."&catID=$catID#%lemtag%";
      } else {
        $glossaryHRefUrl = "";
      }
      $lemIDs = array();
      foreach($lemmas as $lemma) {
        $hasAttestations = false;
        $lemGID = $lemma->getGlobalID();
        $lemTag = 'lem'.$lemma->getID();
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
          $lemmaLookupURL = str_replace("%lemval%",$lemmaValue,$lemmaLookupURL);
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
        $lemmaCF = $lemma->getCertainty();//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        if ($lemmaGenderID && is_numeric($lemmaGenderID)) {//warning Order Dependency for display code lemma gender (like for nouns) hides subPOS hides POS
          $lemHtml .=  Entity::getTermFromID($lemmaGenderID).($lemmaCF[2]==2?'(?)':'');
        } else if ($lemmaSposID && is_numeric($lemmaSposID)) {
          $lemHtml .=  Entity::getTermFromID($lemmaSposID).($lemmaCF[1]==2?'(?)':'');
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
        $entTag2GlossaryHtml[$lemTag] = array('entry' => $lemHtml);
        $lemmaComponents = $lemma->getComponents(true);
        if ($lemmaComponents && $lemmaComponents->getCount()) {
          $hasAttestations = true; // signal see also
          $groupedForms = array();
          $pattern = array("/aʔi/","/aʔu/","/ʔ/","/°/","/\/\/\//","/#/","/◊/");
          $replacement = array("aï","aü","","","","","");
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
                if ($num) {
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
                $entTag = preg_replace("/:/","",$inflectionComponent->getGlobalID());
                $entTag2GlossaryHtml[$entTag] = array('lemTag' => $lemTag,
                  'infTag'=> $infTag,
                  'infHtml'=> $infString);
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
                if ($entTag && array_key_exists($entTag,$cmpTokTag2LocLabel)) {
                  $loc = $cmpTokTag2LocLabel[$entTag].($attestedCommentary?" (".$attestedCommentary.")":"");
                } else {
                  $loc = "zzz";
                }
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
              $entTag2GlossaryHtml[$entTag] = array('lemTag' => $lemTag);
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
              if ($entTag && array_key_exists($entTag,$cmpTokTag2LocLabel)) {
                $loc = $cmpTokTag2LocLabel[$entTag].($attestedCommentary?" (".$attestedCommentary.")":"");
              } else {
                $loc = "zzz";
              }
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
                        $attestedHtml .= "<span class=\"inflectdescript\">unclear: </span>";
                      }
                    } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 != '?') {
                      $attestedHtml .= "<span class=\"inflectdescript\">$key4</span>";
                    } else if (!$lemmaGenderID){//handle noun supress infection gender output
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
//                      ksort($locInfo['loc']);
                      $isFirstLoc = true;
                      foreach ($sortedLocs as $formLoc) {
                        $cntLoc = $locInfo['loc'][$formLoc];
                        //remove internal ordinal
                        $locParts = explode(":",$formLoc);
                        if (count($locParts) == 3) {
                          if (strpos(trim($locParts[0]),"sort") === 0) {
                            $formLoc = $locParts[2];
                          } else {
                            $formLoc = $locParts[0].$locParts[2];
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
          $entTag2GlossaryHtml[$lemTag]['attestedHtml'] = $attestedHtml;
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
          $entTag2GlossaryHtml[$lemTag]['relatedHtml'] = $relatedHtml;
        }
      }
    }
    return $entTag2GlossaryHtml;
  }
}

function getCatalogHTML($catID, $isStaticView = false, $refreshWordMap = false, $useTranscription = false, $hideHyphens = false) {
  global $termLookup,$cmpTokTag2LocLabel,$term_parentLabelToID;
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $cmpTokTag2LocLabel = array();
  //find catalogs
  $catalog = new Catalog($catID);
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    return array("warnings","Warning need valid catalog id $catID.");
  } else {
    $cmpTokTag2LocLabel = getWordTagToLocationLabelMap($catalog,$refreshWordMap);
    $htmlDomDoc = new DOMDocument('1.0','UTF-8');
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
            '<meta name="generator" content="PhpED 8.0"/>'.
            '<meta name="description" content="'.($catalog->getDescription()?$catalog->getDescription():'').'"/>'.
            '<meta name="revisit-after" content="15 days"/>'.
            '<title>Untitled</title>'.
            ($isStaticView?
            '<link rel="stylesheet" href="./css/exGlossary.css" type="text/css"/>':
            '<link rel="stylesheet" href="'.SITE_BASE_PATH.'/common/css/exGlossary.css" type="text/css"/>').
          '</head>'.
          '<body/>'.
          '</html>');
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
    $titleNode = $htmlDomDoc->createElement('h3',$glossaryTitle);
    $bodyNode->appendChild($titleNode);
    $glossaryAnoIDs = $catalog->getAnnotationIDs();
    $glossaryAtbIDs = $catalog->getAttributionIDs();

    $glossaryCommentTypeID = Entity::getIDofTermParentLabel('Glossary-CommentaryType');//warning!!! term dependency
    $lemmas = new Lemmas("lem_catalog_id = $catID and not lem_owner_id = 1","lem_sort_code,lem_sort_code2");
    if ($lemmas->getCount() > 0) {
      $lemIDs = array();
      $isVerb = false;
      foreach($lemmas as $lemma) {
        $hasAttestations = false;
        $lemGID = $lemma->getGlobalID();
        $lemTag = 'lem'.$lemma->getID();
        $entryNode = $htmlDomDoc->createElement('div');
        $entryNode->setAttribute('class',"entry");
        $entryNode->setAttribute('id',"$lemTag");
        $bodyNode->appendChild($entryNode);
        $lemmaDefNode = $htmlDomDoc->createElement('div');
        $lemmaDefNode->setAttribute('class','lemmadef');
        $entryNode->appendChild($lemmaDefNode);
        if ($lemmaOrder = $lemma->getHomographicOrder()) {
          $lemmaDefNode->appendChild($htmlDomDoc->createElement('superscript',$lemmaOrder));
        }
        $lemmaValue = preg_replace('/ʔ/','',$lemma->getValue());
        $lemmaNode = $htmlDomDoc->createElement('span',$lemmaValue);
        $lemmaNode->setAttribute('class','lemma');
        $lemmaDefNode->appendChild($lemmaNode);
        $lemmaGenderID = $lemma->getGender();
        $lemmaPos = $lemma->getPartOfSpeech();
        if ( $lemmaPos) {
          $isVerb = $termLookup[$lemmaPos] == 'v.';
        }
        $lemmaSpos = $lemma->getSubpartOfSpeech();
        $lemmaCF = $lemma->getCertainty();//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        $posNode = null;
        if ($lemmaGenderID) {
          $posNode = $htmlDomDoc->createElement('span',$termLookup[$lemmaGenderID].($lemmaCF[2]==2?'(?)':''));
          $posNode->setAttribute('class','pos');
          $lemmaDefNode->appendChild($posNode);
        } else if ($lemmaSpos) {
          $posNode = $htmlDomDoc->createElement('span',$termLookup[$lemmaSpos].($lemmaCF[1]==2?'(?)':''));
          $posNode->setAttribute('class','pos');
          $lemmaDefNode->appendChild($posNode);
        }else if ($lemmaPos) {
          $posNode = $htmlDomDoc->createElement('span',$termLookup[$lemmaPos].($lemmaCF[0]==2?'(?)':''));
          $posNode->setAttribute('class','pos');
          $lemmaDefNode->appendChild($posNode);
        }
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
        if ( $linkedAnoIDsByType = $lemma->getLinkedAnnotationsByType()) {
          if (array_key_exists($glossaryCommentTypeID,$linkedAnoIDsByType)) {
            $lemmaCommentary = "";
            foreach ($linkedAnoIDsByType[$glossaryCommentTypeID] as $anoID) {
              $annotation = new Annotation($anoID);
              $comment = $annotation->getText();
              if ($comment) {
                if ($lemmaCommentary) {
                  $lemmaCommentary .= " ";
                }
                $lemmaCommentary .= $comment;
              }
            }
            if ($lemmaCommentary) {
              $dDoc = new DOMDocument();
              $dDoc->loadXML("<span>($lemmaCommentary)</span>",LIBXML_NOXMLDECL);
              $commentLemma = $dDoc->getElementsByTagName("span")->item(0);
              $commentLemma->setAttribute("class", "lemmaCommentary");
              $commentLemmaNode = $htmlDomDoc->importNode($commentLemma,true);
              $lemmaDefNode->appendChild($commentLemmaNode);
            }
          }
        }
        $lemmaComponents = $lemma->getComponents(true);
        if ($lemmaComponents && $lemmaComponents->getCount() && !$lemmaComponents->getError()) {
          $hasAttestations = true; // signal see also
          $groupedForms = array();
          $pattern = array("/aʔi/","/aʔu/","/ʔ/","/°/","/\/\/\//","/#/","/◊/");
          $replacement = array("aï","aü","","","","","");
          foreach ($lemmaComponents as $lemmaComponent) {
            $entPrefix = $lemmaComponent->getEntityTypeCode();
            $entID = $lemmaComponent->getID();
            if ($entPrefix == 'inf') {//inflections
              $inflection = $lemmaComponent;
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
                  $vtensemood = $termLookup[$vmood].($ingCF[2]==2?'(?)':'');
                } else if ($vtense && is_numeric($vtense)) {
                  $vtensemood = $termLookup[$vtense].($ingCF[0]==2?'(?)':'');
                } else {
                  $vtensemood = '?';
                }
                if (!array_key_exists($vtensemood,$groupedForms)) {
                  $groupedForms[$vtensemood] = array();
                }
                if ($num && is_numeric($num)) {
                  $num = $termLookup[$num].($ingCF[4]==2?'(?)':'');
                } else {
                  $num = '?';
                }
                if (!array_key_exists($num,$groupedForms[$vtensemood])) {
                  $groupedForms[$vtensemood][$num] = array();
                }
                if ($vper && is_numeric($vper)) {
                  $vper = $termLookup[$vper].($ingCF[6]==2?'(?)':'');
                } else {
                  $vper = '?';
                }
                if (!array_key_exists($vper,$groupedForms[$vtensemood][$num])) {
                  $groupedForms[$vtensemood][$num][$vper] = array();
                }
                if ($num) {
                }
                if ($conj2 && is_numeric($conj2)) {
                  $conj2 = $termLookup[$conj2].($ingCF[7]==2?'(?)':'');
               } else {
                  $conj2 = '?';
                }
                if (!array_key_exists($conj2,$groupedForms[$vtensemood][$num][$vper])) {
                  $groupedForms[$vtensemood][$num][$vper][$conj2] = array();
                }
                $node = &$groupedForms[$vtensemood][$num][$vper][$conj2];
              } else {
                if ($gen && is_numeric($gen)) {
                  $gen = $termLookup[$gen].($ingCF[3]==2?'(?)':'');
                } else {
                  $gen = '?';
                }
                if (!array_key_exists($gen,$groupedForms)) {
                  $groupedForms[$gen] = array();
                }
                if ($num && is_numeric($num)) {
                  $num = $termLookup[$num].($ingCF[4]==2?'(?)':'');
                } else {
                  $num = '?';
                }
                if (!array_key_exists($num,$groupedForms[$gen])) {
                  $groupedForms[$gen][$num] = array();
                }
                if ($case && is_numeric($case)) {
                  $case = $termLookup[$case].($ingCF[5]==2?'(?)':'');
                } else {
                  $case = '?';
                }
                if (!array_key_exists($case,$groupedForms[$gen][$num])) {
                  $groupedForms[$gen][$num][$case] = array();
                }
                if ($num) {
                }
                if ($conj2 && is_numeric($conj2)) {
                  $conj2 = $termLookup[$conj2].($ingCF[7]==2?'(?)':'');
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
                $loc = $cmpTokTag2LocLabel[$entTag].($attestedCommentary?" (".$attestedCommentary.")":"");
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


              $loc = $cmpTokTag2LocLabel[$entTag].($attestedCommentary?" (".$attestedCommentary.")":"");
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
            $displayOrder1 = array('pres.','pres.(?)','opt.','opt.(?)','impv.','impv.(?)','fut.','fut.(?)','perf.','perf.(?)','pret','pret(?)','?');
            $displayOrder2 = array('sg.','sg.(?)','du.','du.(?)','pl.','pl.(?)','?');
            $displayOrder3 = array('1st','1st(?)','2nd','2nd(?)','3rd','3rd(?)','?');
            $displayOrder4 = array('inf.','inf.(?)','abs.','abs.(?)','?');
          } else {
            $displayOrder1 = array('m.','m.(?)','mn.','mn.(?)','n.','n.(?)','mnf.','mnf.(?)','nf.','nf.(?)','f.','f.(?)','mf.','mf.(?)','?');
            $displayOrder2 = array('sg.','sg.(?)','du.','du.(?)','pl.','pl.(?)','?');
            $displayOrder3 = array('nom.','nom.(?)','acc.','acc.(?)','instr.','instr.(?)','dat.','dat.(?)','dat/gen.','dat/gen.(?)','abl.','abl.(?)','gen.','gen.(?)','loc.','loc.(?)','voc.','voc.(?)','?');
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
                    $attestedHtml .= "<span class=\"inflectsep\">;</span>";
                  }
                  if ($isFirstKey1) {
                    $isFirstKey1 = false;
                    if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?') {
                      if ($lemmaPos && $termLookup[$lemmaPos] != 'adv.' && $termLookup[$lemmaPos] != 'ind.'){ //term dependency
                        $attestedHtml .= "<span class=\"inflectdescript\">unclear: </span>";
                      }
                    } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 != '?') {
                      $attestedHtml .= "<span class=\"inflectdescript\">$key4</span>";
                    } else if (!$lemmaGenderID){//handle noun supress infection gender output
                      $attestedHtml .= "<span class=\"inflectdescript\">$key1</span>";
                    }
                  } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?') {
                    if ($lemmaPos && $termLookup[$lemmaPos] != 'adv.' && $termLookup[$lemmaPos] != 'ind.'){ //term dependency
                      $attestedHtml .= "<span class=\"inflectdescript\">unclear: </span>";
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
                      $attestedHtml .= ",";
                    }
                    $isFirstForm = true;
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
                          if (strpos($locParts[0],"sort") === 0) {
                            $formLoc = $locParts[2];
                          } else {
                            $formLoc = $locParts[0].$locParts[2];
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
            ksort($seeLinks,SORT_NUMERIC);
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
          $relatedHtml = "<div class=\"lemmaLinks\">$relatedHtml</div>";
          $dDoc = new DOMDocument();
          $dDoc->loadXML($relatedHtml,LIBXML_NOXMLDECL);
          $relatedLinks = $dDoc->getElementsByTagName("div")->item(0);
          $relatedLinksNode = $htmlDomDoc->importNode($relatedLinks,true);
          $entryNode->appendChild($relatedLinksNode);
        }
      }
    } // else
    return array("success", $htmlDomDoc->saveHTML());
  }
}

?>
