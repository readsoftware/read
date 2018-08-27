<?php
 /*
  * Copyright (C) 2013 Gandhari Consortium
  *
  * Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except
  * in compliance with the License. You may obtain a copy of the License at
  *
  * http://www.gnu.org/licenses/gpl-3.0.txt
  *
  * Unless required by applicable law or agreed to in writing, software distributed under the License
  * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
  * or implied. See the License for the specific language governing permissions and limitations under
  * the License.
  */

/**
  * exportRTFStructural
  *
  *  A service that returns RTF of the structural view of the requested edition
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Services Classes
  */

  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start();

//  header("Content-type: text/rtf;  charset=utf-8");
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Edition.php';
  require_once dirname(__FILE__) . '/../model/entities/Text.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/SyllableCluster.php';

  $dbMgr = new DBManager();
  $retVal = array();
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $refreshWordMap = ((array_key_exists('refreshWordMap',$_REQUEST) || array_key_exists('refreshLookUps',$_REQUEST))? true:false);
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $anoIDs = array();
  $atbIDs = array();
  $errors = array();
  $warnings = array();
  //find edition
  $edition = new Edition($ednID);
  if (!$edition || $edition->hasError()) {//no $dition or unavailable so warn
    array_push($warnings,"Warning need valid accessible edition id $ednID.");
  } else {
    $footnoteTypeID = Entity::getIDofTermParentLabel('FootNote-FootNoteType');//warning!!!! term dependency
    $fnReconstrTypeID = Entity::getIDofTermParentLabel('Reconstruction-FootNote');//warning!!!! term dependency
    $typeIDs = array($fnReconstrTypeID,$footnoteTypeID);
    $srchStrings = array('\[','\]','⟪','⟫','\{\{','\}\}');
    $rplcStrings = array("","","","","","");
    $tcmSrchStrings = array("⟨","⟩","⟪","⟫","\{","\}");
    $tcmRtfRplcStrings = array("\\u10216\\'3f","\\u10217\\'3f","\\u10218\\'3f","\\u10219\\'3f","\\'7b","\\'7d");
    $prevTCMS = "";
    $previousGraFootnotes = "";//need to delay to next grapheme to ensure placement outside TCM
    $outputtingProse = false;
    $proseBeginning = true;
    $needsSpace = false;
    $rtf =
          '{\rtf1\adeflang1025\ansi\ansicpg10000\uc1\adeff0\deff0'.
          '{\fonttbl'.
          '{\f0\fbidi \fnil\fcharset0\fprq2{\*\panose 02020603050405020304}Times New Roman;}'.
          '{\f38\fbidi \fnil\fcharset0\fprq2{\*\panose 02000503060000020004}Gandhari Unicode;}}'.
          '{\stylesheet'.
            '{\ql \li0\ri0\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\lang1031\langfe1031\cgrid\langnp1031\langfenp1031 \snext0 \sqformat \spriority0 Normal;}'.
            '{\s2\ql \li0\ri0\sa200\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \rtlch\fcs1 \ab\ai\af0\afs24\alang2057 \ltrch\fcs0 \fs24\lang2057\langfe1031\cgrid\langnp2057\langfenp1031 \sbasedon0 \snext1 \sqformat \spriority0 Structural;}'.
            '{\*\cs02 \additive \sunhideused \spriority1 Default Paragraph Font;}'.
            '{\s03 \b\f38\fs36\sb200\sa200 \sqformat \spriority1 chapter;}'.
            '{\s04 \b\f38\fs36\sb100\sa100 \sqformat \spriority1 chapterlevel1;}'.
            '{\s05 \b\f38\fs36\sb100\sa100 \sqformat \spriority1 chapterlevel2;}'.
            '{\s06 \b\fs48\f38\sa200 \sqformat \spriority1 doctitle;}'.
            '{\*\cs07 \additive \f38\fs24 \sqformat \spriority1 prose;}'.
            '{\*\cs08 \additive \f38\fs24\cf4 \sbasedon07 \sqformat \spriority1 line marker;}'.
            '{\s09 \b\f38\fs24\sb100\sa100 \sqformat \spriority1 section;}'.
            '{\s10 \b\f38\fs24\sb50\sa50 \sqformat \spriority1 sectionlevel1;}'.
            '{\s11 \b\f38\fs24\sb50\sa50 \sqformat \spriority1 sectionlevel2;}'.
            '{\*\cs12 \additive \f38\cf3\lang1031\langfe0\langnp1031 \sbasedon02 \sqformat \spriority1 link;}'.
            '{\*\cs13 \additive \f38\cf3\lang1031\langfe0\langnp1031 \sbasedon12 \sqformat \spriority1 linkalso;}'.
            '{\*\cs14 \additive \f38\cf1\lang1031\langfe0\langnp1031 \sbasedon12 \sqformat \spriority1 linkcustom;}'.
            '{\*\cs15 \additive \b\i\f38\fs24 \sbasedon02 \sqformat \spriority1 lemmarelated;}'.
            '{\s16 \b\fs36\f38\sb50\sa50 \sqformat \spriority1 stanza header;}'.
            '{\s27 \li240\sa100 \sqformat \spriority1 stanza;}'.
            '{\*\cs17 \additive \f38\fs24 \sqformat \spriority1 pada;}'.
            '{\s18 \b\fs36\f38\sb100\sa100 \sqformat \spriority1 list header;}'.
            '{\*\cs19 \additive \b\f38\fs24 \sqformat \spriority1 item header;}'.
            '{\s20 \f38\fs24\fi-3600\li360\saauto1 \sqformat \spriority1 item;}'.
            '{\s21 \fs24\f38\cf6\chshdng0\chcfpat0\chcbpat7 \spriority0 paraheader;}'.
            '{\s22\ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\cf0\cgrid \sbasedon0 \snext22 \slink23 \slocked footnote text;}'.
            '{\*\cs23 \additive \fs24\cf1 \sbasedon2 \slink22 \slocked Footnote Text Char;}'.
            '{\*\cs24 \additive \f38\super \sbasedon2 \slocked footnote reference;}'.
            '{\*\cs25 \additive \b\f38\fs24 \sqformat \spriority1 pada header;}'.
            '{\*\cs26 \additive \i\ \sqformat \spriority1 italic;}'.
          '}'.
          '{\colortbl;\red0\green255\blue255;\red255\green0\blue0;\red0\green175\blue0;\red109\green109\blue109;\red132\green221\blue253;\red0\green121\blue165;\red132\green221\blue253;\red0\green0\blue0;}'.
          '\pard\plain \ftnbj'.
          '{\*\ftnsep \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsep \par }}'.
          '{\*\ftnsepc \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsepc \par }}'.
          '{\*\aftnsep \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsep \par }}'.
          '{\*\aftnsepc \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsepc \par }}'.
          '\li0\ri0\sa200\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0';

    $chStyle = '{\pard \s03\b\f38\fs36\sb200\sa200 ';
    $ch1Style = '{\pard \s04\b\f38\fs36\sb100\sa100 ';
    $ch2Style = '{\pard \s05\b\f38\fs36\sb100\sa100 ';
    $titleStyle = '{\pard \s06\b\fs48\f38\sa200 ';
    $proseStyle = '{\cs07\f38\fs24 ';
    $lineMarkerStyle = '{\cs08\f38\fs24\cf4 ';
    $secStyle = '{\pard \s09\b\f38\fs24\sb100\sa100 ';
    $sec1Style = '{\pard \s10\b\f38\fs24\sb50\sa50 ';
    $sec2Style = '{\pard \s11\b\f38\fs24\sb50\sa50 ';
    $paraHeaderStyle = '{\pard \s21\fs24\f38\cf6\chshdng0\chcfpat0\chcbpat7 ';
    $stanzaHeaderStyle = '{\pard \s16\b\fs36\f38\sb50\sa50 ';
    $stanzaStyle = '{\pard \s27\li240\sa100 ';
    $padaHeaderStyle = '{\cs25\b\f38\fs24 ';
    $padaStyle = '{\cs17\f38\fs24 ';
    $listHeaderStyle = '{\pard \s18\b\fs36\f38\sb100\sa100 ';
    $itemHeaderStyle = '{\cs19\b\f38\fs24 ';
    $itemStyle = '{\pard \s20\f38\fs24\fi-360\li360\saauto1 ';
    $italicStyle = '{\cs26\i ';
    $space = '{ }';
    $commaRed = '{\f38\cf2 ,}';
    $fullstopRed = '{\f38\cf2 .}';
    $prequote = '{\f38 \u8220\\\'d2}';
    $postquote = '{\f38 \u8221\\\'d3}';
    $homStyle = '{\s11\b0\f38\super ';
    $footnoteStart = '{\cs24\super\chftn {\footnote \pard\plain \s22\ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\cf0\cgrid'.
                   '{\cs24\super\chftn }{';
    $footnoteEnd = '}}}';
    $linkStyle = '{\cs12\f38\cf3\lang1031\langfe0\langnp1031 ';
    $endParaStyle = '\par}';
    $endStyle = '}';
    $endRTF = '}';
    $softReturn = '{\line}';
    $hardReturn = '{\par}';
    $eol = "";//this helps readability of raw RTF in a basic line editor and does not affect layout in Word import


    if ($editionTitle = $edition->getDescription()) {
      $rtf .= $titleStyle.utf8ToRtf($editionTitle).$endParaStyle.$eol;
    }
    $edSeqs = $edition->getSequences(true);
    $seqPhys = $seqText = $textAnalysisSeq = null;
    foreach ($edSeqs as $edSequence) {
      $seqType = $edSequence->getType();
      if (!$seqPhys && $seqType == "TextPhysical"){//warning!!!! term dependency
        $seqPhys = $edSequence;
      }
      if (!$seqText && $seqType == "Text"){//warning!!!! term dependency
        $seqText = $edSequence;
      }
      if (!$textAnalysisSeq && $seqType == "Analysis"){//warning!!!! term dependency
        $textAnalysisSeq = $edSequence;
      }
    }

    if (!$textAnalysisSeq || !$textAnalysisSeq->getEntityIDs() || count($textAnalysisSeq->getEntityIDs()) == 0) {
      array_push($errors,"No Text Analysis found for edition ".$editionTitle." id=".$edition->getID());
    } else {//process analysis
      //calculate  post grapheme id to physical line label map
      $graID2LineLabelMap = array();
      $graID2LineFootnoteMap = array();
      if ($seqPhys && $seqPhys->getEntityIDs() && count($seqPhys->getEntityIDs()) > 0) {
        foreach ($seqPhys->getEntities(true) as $physicalLineSeq) {
          $sclGIDs = $physicalLineSeq->getEntityIDs();
          if (count($sclGIDs) == 0 || strpos($sclGIDs[0],'scl:') != 0) {
            array_push($warnings,"Found physical Line without syllables ".$physicalLineSeq->getGlobalID());
            continue;
          }
          $syllable = new SyllableCluster(substr($sclGIDs[0],4));
          if ($syllable->hasError()) {
            array_push($warnings,"warning, error encountered while trying to open syllable ".$sclIDs[0]." for phydical line ".$physicalLineSeq->getGlobalID()." - ".join(",",$edition->getErrors()));
            continue;
          }
          $graIDs = $syllable->getGraphemeIDs();
          if (count($graIDs) == 0 ) {
            array_push($warnings,"Found syllable without graphemes ".$sclIDs[0]);
            continue;
          }
          $label = $physicalLineSeq->getLabel();
          if (!$label) {
            $label = 'seq'.$physicalLineSeq->getID();
          }
          if (strpos($syllable->getSortCode(),"0.19")=== 0 &&
              strpos($syllable->getSortCode2(),"0.5")=== 0 &&
              count($graIDs) > 1) { //begins with vowel carrier so choose second grapheme
            $mapGraID = $graIDs[1];
          } else {
            $mapGraID = $graIDs[0];
          }
          $graID2LineLabelMap[$mapGraID] = $label;
          $physLineFootnote = getEntityFootnotesRTF($physicalLineSeq);
          if ($physLineFootnote) {
            $graID2LineFootnoteMap[$mapGraID] = $physLineFootnote;
          }
        }
      }
      //start to calculate rtf using each entity of the analysis container
      foreach ($textAnalysisSeq->getEntityIDs() as $entGID) {
        $prefix = substr($entGID,0,3);
        $entID = substr($entGID,4);
        if ($prefix == 'seq') {
          $subSequence = new Sequence($entID);
          if (!$subSequence || $subSequence->hasError()) {//no sequence or unavailable so warn
            array_push($warnings,"Warning inaccessible sub-sequence id $entID skipped.");
          } else {
            renderStructRTF($subSequence, 1);
          }
        } else if ($prefix == 'cmp' || $prefix == 'tok' ) {
          if ($prefix == 'cmp') {
            $entity = new Compound($entID);
          } else {
            $entity = new Token($entID);
          }
          if (!$entity || $entity->hasError()) {//no word or unavailable so warn
            array_push($warnings,"Warning inaccessible word id $entGID skipped.");
          } else {
            if (!$outputtingProse) { // open prose section
              $rtf .= $proseStyle;
              $outputtingProse = true;
            }
            renderWordRTF($entity);
          }
        }else{
          error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id="+$edition->getID());
          continue;
        }
      }
      if ($outputtingProse) {
        $rtf .= $endStyle;
      }
      $rtf .= $endRTF;
      $rtf = preg_replace('/\{\\\\line\}\\\\par\}/','\\par}',$rtf); //remove soft return followed by hard return
      $rtf = preg_replace('/\\\\par\}\\\\par\}/','}\\par}',$rtf); //remove hard return followed by hard return
      $rtf = preg_replace('/\\\\par(\}+)\{\\\\par\}/','\\par\\1',$rtf); //remove trailing hard return following hard return
    }
  }
  if (count($errors)) {
    header("Content-type: text/html;  charset=utf-8");
    echo "Errors encountered trying to export editions Structure in RTF. Errors: ".join(", ",$errors);
  } else {
    if ($isDownload) {
      ob_clean();
      header("Content-type: text/rtf;  charset=UTF-8");
      header("Content-Disposition: attachment; filename=readStructuredEdition.rtf");
      header("Expires: 0");
    }
    echo $rtf;
  }
  return;
  function htmlToRTF($strWithHTML) {
    global $italicStyle, $endStyle;
    $htmlSrchStrings = array('/\<i\>/','/\<\/i\>/','/\<b\>/','/\<\/b\>/','/\<u\>/','/\<\/u\>/');
    $htmlReplStrings = array($italicStyle,$endStyle,'{\b ','}','{\ul ','}');
    $strWithRTF = preg_replace($htmlSrchStrings,$htmlReplStrings,$strWithHTML);
    return $strWithRTF;
  }

  function getEntityFootnotesRTF($entity) {
    global $footnoteStart, $footnoteEnd, $eol, $space, $style, $typeIDs;
    $fnRTF = "";
    if ( $linkedAnoIDsByType = $entity->getLinkedAnnotationsByType()) {
      foreach ($typeIDs as $typeID) {
        if (array_key_exists($typeID,$linkedAnoIDsByType)) {
          foreach ($linkedAnoIDsByType[$typeID] as $anoID) {
            $annotation = new Annotation($anoID);
            $anoText = $annotation->getText();
            if ($anoText) {
              $fnRTF .= $footnoteStart.$space.htmlToRTF(utf8ToRtf($anoText)).$footnoteEnd.$eol;
            }
          }
        }
      }
    }
    return $fnRTF;
  }


  function renderWordRTF($entity, $isLastWord) {
    global $prevTCMS, $graID2LineLabelMap, $graID2LineFootnoteMap, $rtf, $tcmSrchStrings, $tcmRtfRplcStrings,
           $srchStrings, $rplcStrings, $endStyle, $needsSpace, $proseBeginning,
           $proseStyle, $eol, $space, $previousGraFootnotes, $lineMarkerStyle;
    $entGID = $entity->getGlobalID();
    $prefix = substr($entGID,0,3);
    $entID = substr($entGID,4);
    $wordParts = array();
    $wordRTF = "";
    $fnRTF = "";
    $prevGraIsVowelCarrier = false;
    $previousA = null;
    $tokIDs = null;

    if ($entity && $prefix == 'cmp' && count($entity->getTokenIDs())) {
      $tokIDs = $entity->getTokenIDs();
    } else if ($entity && $prefix == 'tok'){
      $tokIDs = array($entID);
    } else {
      error_log("err, rendering word rtf invalid GID $entGID");
    }

    if ($tokIDs) {
      //for each token in word
      $tokCnt = count($tokIDs);
      for($i =0; $i < $tokCnt; $i++) {
        $tokID = $tokIDs[$i];
        $token = new Token($tokID);
        $graIDs = $token->getGraphemeIDs();
        $firstT = ($i==0);
        $lastT = (1+$i == $tokCnt);
        //for each grapheme in token
        $graCnt = count($graIDs);
        for($j=0; $j<$graCnt; $j++) {
          $graID = $graIDs[$j];
          $grapheme = new Grapheme($graID);
          if (!$grapheme) {
            error_log("err,calculating word rtf and grapheme not available for graID $graID");
            continue;
          }
          if ($grapheme->getValue() == "ʔ") {
            $prevGraIsVowelCarrier = true;
            continue;
          }
          if ($grapheme->getValue() == "◈") {
            $prevGraIsVowelCarrier = false;
            $previousA = false;
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
          if ($postTCMBrackets) {
            $postTCMBrackets = mbPregReplace($srchStrings,$rplcStrings,$postTCMBrackets);
            if ($postTCMBrackets) {
              $postTCMBrackets = mbPregReplace($tcmSrchStrings,$tcmRtfRplcStrings,$postTCMBrackets);
              array_push($wordParts,($wordRTF?utf8ToRtf($wordRTF):"").$postTCMBrackets);
              $wordRTF = "";
            }
          }
          if ($previousGraFootnotes) {
            array_push($wordParts,$previousGraFootnotes);
            $previousGraFootnotes = "";
          }
          if (array_key_exists($graID,$graID2LineLabelMap)) {
            if ($wordRTF) {
              array_push($wordParts,utf8ToRtf($wordRTF));
            }
            $wordRTF = $lineMarkerStyle;
            if ($firstT && $firstG || $lastT && $lastG) {
              $wordRTF .= ($proseBeginning?"":" ")."[".$graID2LineLabelMap[$graID]."]";
              if ( array_key_exists($graID,$graID2LineFootnoteMap)) {
                $wordRTF .= $graID2LineFootnoteMap[$graID];
              }
              $wordRTF .= " ";
              $needsSpace = false;
            } else {
              $wordRTF .= "[".$graID2LineLabelMap[$graID];
              if ( array_key_exists($graID,$graID2LineFootnoteMap)) {
                $wordRTF .= $graID2LineFootnoteMap[$graID];
              }
              $wordRTF .= "]";
            }
            $wordRTF .= $endStyle;
            $prevTCMS = "";//at a new physical line so reset TCM
            array_push($wordParts,$wordRTF);
            $wordRTF = "";
          }
          if ($needsSpace) {// since replace can return null
            array_push($wordParts,($wordRTF?utf8ToRtf($wordRTF):"")." ");
            $wordRTF = "";
            $needsSpace = false;
          }
          if ($preTCMBrackets) {
            $preTCMBrackets = mbPregReplace($srchStrings,$rplcStrings,$preTCMBrackets);
            if ($preTCMBrackets) {
              $preTCMBrackets = mbPregReplace($tcmSrchStrings,$tcmRtfRplcStrings,$preTCMBrackets);
              array_push($wordParts,($wordRTF?utf8ToRtf($wordRTF):"").$preTCMBrackets);
              $wordRTF = "";
            }
          }
          //add grapheme
          $graTemp = $grapheme->getValue();
          if ($prevGraIsVowelCarrier && $previousA &&
                        ($prevTCMS == $tcms || (!$prevTCMS|| $prevTCMS == "S") && (!$tcms|| $tcms == "S"))) {
            if ($graTemp == 'i') {
              $graTemp = "ï";
            }else if ($graTemp == 'u') {
              $graTemp = "ü";
            }
          }
          $prevTCMS = $tcms;
          if (strpos($tcms,"Sd") === false) {
            $wordRTF .= $graTemp;
          }
          if ($graTemp == "a") {
            $previousA = true;
          } else {
            $previousA = false;
          }
          $prevGraIsVowelCarrier = false;
          $previousGraFootnotes .= getEntityFootnotesRTF($grapheme);
        }//end for graphIDs
        $previousGraFootnotes .= getEntityFootnotesRTF($token);
      }//end for token IDs
      if ($prefix == 'cmp') {//compound so check footnotes
        $previousGraFootnotes .= getEntityFootnotesRTF($entity);
      }
      if ($isLastWord &&$prevTCMS && $prevTCMS != "S") {//close off any TCM
        $tcmBrackets = getTCMTransitionBrackets($prevTCMS,"S");//reduce to S
        $prevTCMS = "";//reset since we closed of TCMs for the structure.
                       //This will ensure next structures output will have opening TCMs
        if ($tcmBrackets) {
          $tcmBrackets = mbPregReplace($srchStrings,$rplcStrings,$tcmBrackets);
        }
        if ($tcmBrackets) {
          $tcmBrackets = mbPregReplace($tcmSrchStrings,$tcmRtfRplcStrings,$tcmBrackets);
          array_push($wordParts,($wordRTF?utf8ToRtf($wordRTF):"").$tcmBrackets);
          $wordRTF = "";
        }
      }
      if ($wordRTF) {
        array_push($wordParts,utf8ToRtf($wordRTF));
      }
      if ($isLastWord && $previousGraFootnotes) {
        array_push($wordParts,$previousGraFootnotes);
        $previousGraFootnotes = "";
      }
      $wordRTF = join("",$wordParts);
      $wordRTF = preg_replace('/\/\/\//',"",$wordRTF); // remove edge indicator
      $wordRTF = preg_replace('/_+/',"_",$wordRTF); // multple missing consonants
      $wordRTF = preg_replace('/_([^\.])*/',".\\1",$wordRTF); // multple missing consonants
//      $wordRTF = preg_replace('/\.\./',".",$wordRTF); // multple missing consonants
      if (!preg_match('/[^\s]/',$wordRTF)) {
        $wordRTF = "";
      }
      $needsSpace = true;
    }
    $rtf .= $wordRTF.$fnRTF;
  }

  function renderStructRTF($sequence, $level) {
    global $chStyle, $ch1Style, $ch2Style, $needsSpace, $proseBeginning,
           $secStyle, $sec1Style, $sec2Style, $endParaStyle,
           $proseStyle, $endStyle,$paraHeaderStyle,$stanzaStyle,
           $stanzaHeaderStyle, $padaStyle, $padaHeaderStyle,
           $listHeaderStyle, $itemHeaderStyle, $itemStyle,
           $eol, $space, $softReturn, $hardReturn, $rtf,
           $outputtingProse, $edition;
    $lvl = $level +1;
    if ($sequence) {
        $seqEntGIDs = $sequence->getEntityIDs();
        $seqType = $sequence->getType();
    }
    if (!$seqEntGIDs || count($seqEntGIDs) == 0) {
      error_log("warn, Found empty structural sequence element seq".$sequence->getID()." for edition ".$edition->getDescription()." id=".$edition->getID());
      return;
    }
    $overrideProseStyle = null;
    $seqLabel = $sequence->getLabel();
    $seqSup = $sequence->getSuperScript();
    $label = ($seqSup?$seqSup.($seqLabel?" ".$seqLabel:""):($seqLabel?$seqLabel:""));
    $endStructRTF = "";
    switch($seqType) {
      case "List": //warning!!!! term dependency
        //close prose if outputting and add hard return
        if ($outputtingProse) {
          $rtf .= $endStyle.$eol.$hardReturn.$eol;
          $needsSpace = false;
          $outputtingProse = false;
        }
        //if label then output list header with hard return
        if ($label) {
          $rtf .= $listHeaderStyle.utf8ToRtf($label).$endParaStyle.$eol;
        }
        //set list structure ending rtf
//        $endStructRTF = $hardReturn.$eol;
        break;
      case "Item": //warning!!!! term dependency
        //close prose if outputting and add hard return
        if ($outputtingProse) {
          $rtf .= $endStyle.$eol;
          $needsSpace = false;
          $outputtingProse = false;
        }
        //if label then output as Item bullet with space
        $rtf .= $itemStyle;
        if ($label) {
          $rtf .= $itemHeaderStyle.utf8ToRtf($label).$endStyle.$eol.$space.$eol;
        }
        //$overrideProseStyle = $itemStyle;
        //set list item structure ending rtf
        $endStructRTF = $endParaStyle.$eol;
        break;
      case "Chapter": //warning!!!! term dependency
        //close prose if outputting and add hard return
        if ($outputtingProse) {
          $rtf .= $endStyle.$eol.$hardReturn.$eol;
          $needsSpace = false;
          $outputtingProse = false;
        }
        //if label then output Chapter (level) header with hard return
        if ($label) {
          $rtf .= ($level==1?$chStyle:($level==2?$ch1Style:$ch2Style)).htmlToRTF(utf8ToRtf($label)).$endParaStyle.$eol;
        }
        //set chapter structure ending rtf
//        $endStructRTF = $hardReturn.$eol;
        break;
      case "Section": //warning!!!! term dependency
        //close prose if outputting and add hard return
        if ($outputtingProse) {
          $rtf .= $endStyle.$eol.$hardReturn.$eol;
          $needsSpace = false;
          $outputtingProse = false;
        }
        //if label then output Section (level) header with hard return
        if ($label) {
          $rtf .= ($level==1?$secStyle:($level==2?$sec1Style:$sec2Style)).htmlToRTF(utf8ToRtf($label)).$endParaStyle.$eol;
        }
        //set section structure ending rtf
//        $endStructRTF = $hardReturn.$eol;
        break;
      case "Paragraph": //warning!!!! term dependency
        //close prose if outputting and add hard return
        if ($outputtingProse ) {
          $rtf .= $endStyle.$eol.$hardReturn.$eol;
          $needsSpace = false;
          $outputtingProse = false;
        }
        //if label then output Paragraph header with hard return
        if ($label) {
          $rtf .= $paraHeaderStyle.utf8ToRtf($label).$endParaStyle.$eol;
          //set paragraph structure ending rtf
        }
//        $endStructRTF = $hardReturn.$eol;
        break;
      case "Sentence": //warning!!!! term dependency
        //set sentence structure ending rtf
//        $endStructRTF = $space.$eol;
        break;
      case "Phrase": //warning!!!! term dependency
      case "Clause": //warning!!!! term dependency
        //set clause structure ending rtf
        $endStructRTF = $space.$eol;
        break;
      case "Stanza": //warning!!!! term dependency
        //close prose if outputting and add hard return
        if ($outputtingProse) {
          $rtf .= $endStyle.$eol;
          $needsSpace = false;
          $outputtingProse = false;
          if ($level > 2) {//hack for stanza inside of list item.
            $rtf .= $hardReturn.$eol;
          }
        }
        //if label then output as stanza id and/or meter header with hard return
        if ($label) {
          $rtf .= $stanzaHeaderStyle.utf8ToRtf($label).$endParaStyle.$eol;
        }
        $rtf .= $stanzaStyle;
        //set stanza structure ending rtf
        $endStructRTF = $endParaStyle.$eol;
        break;
      case "Pāda": //warning!!!! term dependency
        //close prose if outputting
        if ($outputtingProse) {
          $rtf .= $endStyle.$eol;
          $needsSpace = false;
          $outputtingProse = false;
        }
        //if label then output as Pāda id with space
        if ($label) {
          $rtf .= $padaHeaderStyle.utf8ToRtf($label).$endStyle.$eol.$space.$eol;
        }
        $overrideProseStyle = $padaStyle;
        //set pāda structure ending rtf
        $endStructRTF = $softReturn.$eol;
        break;
        default:
    }
    $lastGIDIndex = -1 + count($seqEntGIDs);
    $lastGID = $seqEntGIDs[$lastGIDIndex];
    foreach ($seqEntGIDs as $entGID) {
      $prefix = substr($entGID,0,3);
      $entID = substr($entGID,4);
      if ($prefix == 'seq') {
        $subSequence = new Sequence($entID);
        if (!$subSequence || $subSequence->hasError()) {//no sequence or unavailable so warn
          array_push($warnings,"Warning inaccessible sub-sequence id $entID skipped.");
        } else {
          renderStructRTF($subSequence, $lvl);
        }
      } else if ($prefix == 'cmp' || $prefix == 'tok' ) {
        if ($prefix == 'cmp') {
          $entity = new Compound($entID);
        } else {
          $entity = new Token($entID);
        }
        if (!$entity || $entity->hasError()) {//no word or unavailable so warn
          array_push($warnings,"Warning inaccessible word id $entGID skipped.");
        } else {
          if (!$outputtingProse) { // open prose section
            $rtf .= ($overrideProseStyle?$overrideProseStyle:$proseStyle);
            $outputtingProse = true;
            $proseBeginning = true;
          } else {
            $proseBeginning = false;
          }
          renderWordRTF($entity,$lastGID == $entGID);
        }
      }else{
        error_log("warn, Found unknown structural element $entGID for edition ".$edition->getDescription()." id="+$edition->getID());
        continue;
      }
    }
    $rtf .= getEntityFootnotesRTF($sequence);
    if ($endStructRTF) {
      $rtf .= $endStructRTF;
    }
  }

?>
