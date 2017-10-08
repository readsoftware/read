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
* exportRTFEdition
*
*  A service that returns RTF of the edition view of the requested style (hybrid, diplomatic (default) or reconstructed)
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
  ob_start('ob_gzhandler');

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
  $style = (array_key_exists('style',$_REQUEST)? $_REQUEST['style']:'diplomatic');
  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $errors = array();
  $warnings = array();

  //find edition
  $edition = new Edition($ednID);
  if (!$edition || $edition->hasError()) {//no $dition or unavailable so warn
    array_push($warnings,"Warning need valid accessible edition id $ednID.");
  } else {
    $footnoteTypeID = Entity::getIDofTermParentLabel('FootNote-FootNoteType');//warning!!!! term dependency
    $fnTranscrTypeID = Entity::getIDofTermParentLabel('Transcription-FootNote');//warning!!!! term dependency
    $fnReconstrTypeID = Entity::getIDofTermParentLabel('Reconstruction-FootNote');//warning!!!! term dependency
    if ($style == 'reconstructed') {
      $typeIDs = array($fnReconstrTypeID,$footnoteTypeID);
    } else if ($style == 'diplomatic'){
      $typeIDs = array($fnTranscrTypeID);
    } else {
      $typeIDs = array($fnReconstrTypeID,$footnoteTypeID,$fnTranscrTypeID);
    }
    $nonReconBrackets = array('\[','\]','⟪','⟫','\{\{\{','\}\}\}','\{\{','\}\}');
    $nonReconBracketRplcs = array("","","","",'{','}',"","");
    $nonDiploBrackets = array('\(\*','\)','⟨\*','⟩','([^\{])(\{)([^\{])','^(\{)([^\{])','\{\{\{','([^\}])(\})([^\}])','([^\}])(\})$','\}\}\}');
    $nonDiploBracketRplcs = array("","","","",'$1$3','$2','{{','$1$3','$1','}}');
    $srchStrings = ($style == "diplomatic" ? $nonDiploBrackets :
                    ($style == "reconstructed" ? $nonReconBrackets : null));
    $rplcStrings = ($style == "diplomatic" ? $nonDiploBracketRplcs :
                    ($style == "reconstructed" ? $nonReconBracketRplcs : null));
    $tcmNonRTFSrchStrings = array("⟨","⟩","⟪","⟫","\{","\}");
    $tcmRtfRplcStrings = array("\\u10216\\'3f","\\u10217\\'3f","\\u10218\\'3f","\\u10219\\'3f","\\'7b","\\'7d");
    //state variable
    $prevTCMS = "";
    $curGraID = null;
    $lastGraphemeID = null;
    $rtf =
          '{\rtf1\adeflang1025\ansi\ansicpg10000\uc1\adeff0\deff0'.
          '{\fonttbl'.
          '{\f0\fbidi \fnil\fcharset0\fprq2{\*\panose 02020603050405020304}Times New Roman;}'.
          '{\f38\fbidi \fnil\fcharset0\fprq2{\*\panose 02000503060000020004}Gandhari Unicode;}}'.
          '{\stylesheet'.
            '{\ql \li0\ri0\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\lang1031\langfe1031\cgrid\langnp1031\langfenp1031 \snext0 \sqformat \spriority0 Normal;}'.
            '{\s2\ql \li0\ri0\sa200\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \rtlch\fcs1 \ab\ai\af0\afs24\alang2057 \ltrch\fcs0 \fs24\lang2057\langfe1031\cgrid\langnp2057\langfenp1031 \sbasedon0 \snext1 \sqformat \spriority0 Structural;}'.
            '{\*\cs03 \additive \sunhideused \spriority1 Default Paragraph Font;}'.
            '{\*\s04 \b\fs36\f38\sa200 \sqformat \spriority1 doctitle;}'.
            '{\*\cs05 \f38\fs24 \sqformat \spriority1 prose;}'.
            '{\*\cs06 \additive \b\f38\fs36 \sqformat \spriority1 line header;}'.
            '{\s20 \b0\f38\fs24\fi-3600\li360\sb50 \sqformat \spriority1 physical line;}'.
            '{\s21\ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\dbch\cgrid \sbasedon0 \snext21 \slink22 \sqformat \spriority1 footnote text;}'.
            '{\*\cs22 \additive \f38\fs20 \sbasedon3 \slink21 \slocked \ssemihidden Footnote Text Char;}'.
            '{\*\cs23 \additive \f38\fs20 \sqformat \spriority1 footnote number;}'.
            '{\*\cs24 \additive \f38\fs24\super \sbasedon3 \sqformat \spriority1 footnote reference;}'.
          '{\*\cs26 \additive \i \sqformat \spriority1 italic;}'.
          '}'.
          '{\colortbl;\red0\green255\blue255;\red255\green0\blue0;\red0\green175\blue0;\red109\green109\blue109;\red132\green221\blue253;\red0\green121\blue165;\red132\green221\blue253;\red0\green0\blue0;}'.
          '\pard\plain \ftnbj \s2\ql'.
          '{\*\ftnsep \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsep \par }}'.
          '{\*\ftnsepc \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsepc \par }}'.
          '{\*\aftnsep \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsep \par }}'.
          '{\*\aftnsepc \pard\plain \ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs24\cf8\cgrid { \chftnsepc \par }}'.
          '\li0\ri0\sa200\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0\widowctrl\ftnbj\aenddoc\trackmoves0\trackformatting1';

    $titleStyle = '{\pard \s04\b\fs36\f38\sa200 ';
    $proseStyle = '{\cs05\f38\fs24 ';
    $lineHeaderStyle = '{\cs06\b\f38\fs24 ';
    $lineStyle = '{\pard \s20\b0\f38\fs24\fi-360\li360\sb50 ';
    $space = '{ }';
    $italicStyle = '{\cs26\i ';
    $commaRed = '{\f38\cf2 ,}';
    $fullstopRed = '{\f38\cf2 .}';
    $prequote = '{\f38 \u8220\\\'d2}';
    $postquote = '{\f38 \u8221\\\'d3}';
    $homStyle = '{\cs11\b0\f38\super ';
    $footnoteStart = '{\cs24\f38\fs24\super \chftn {\footnote \pard\plain \s21\ql \li0\ri0\widctlpar\wrapdefault\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\dbch\cgrid {\cs23\f38\fs20 \chftn }{ ';
    $footnoteEnd = '}}}';
    $linkStyle = '{\cs12\f38\cf3\lang1031\langfe0\langnp1031 ';
    $endParaStyle = '\par}';
    $endStyle = '}';
    $endRTF = '}';
    $tab = '\tab ';
    $softReturn = '{\line}';
    $hardReturn = '{\par}';
    $eol = "";//this helps readability of raw RTF in a basic line editor and does not affect layout in Word import


    if ($editionTitle = $edition->getDescription()) {
      $rtf .= $titleStyle.utf8ToRtf($editionTitle)."-[$style view]".$endParaStyle.$eol;
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

    if (!$seqText || !$seqText->getEntityIDs() || count($seqText->getEntityIDs()) == 0) {
      array_push($warning,"No Tokenisation Sequences found for edition ".$editionTitle." id=".$edition->getID().". Processing physical lines with syllables only.");
    } else {
      //calculate context mappings for tokenisation and footnote lookups for tok and cmp by last graphemeID
      $graID2BoundaryMap = array();
      $graID2fnRTFMap = array();
      foreach ($seqText->getEntities(true) as $textDivSeq) {
        $seqID = $textDivSeq->getID();
        $bkuplabel = $textDivSeq->getLabel();
        if (!$bkuplabel) {
          $bkuplabel = $textDivSeq->getSuperScript();
        }
        $wrdGIDs = $textDivSeq->getEntityIDs();
        if (count($wrdGIDs) == 0) {
          array_push($warnings,"Found text division without words ".$textDivSeq->getGlobalID());
          continue;
        }
        foreach ($wrdGIDs as $wrdGID) {
          $prefix = substr($wrdGID,0,3);
          $entID = substr($wrdGID,4);
          if ($prefix != 'cmp' && $prefix != 'tok' ) {
            array_push($warnings,"warning, encountered non-word GID $wrdGID in textDiv sequence ID $seqID - ignoring ");
            continue;
          }
          if ($prefix == 'cmp') {
            $entity = new Compound($entID);
          } else {
            $entity = new Token($entID);
          }
          if (!$entity || $entity->hasError()) {//no word or unavailable so warn
            array_push($warnings,"Warning inaccessible word id $entGID skipped.");
          } else {
            walkTokenContext($entity);//get graID footnote lookup and graID boundary lookup
          }
        }
      }
    }
    if (!$seqPhys || !$seqPhys->getEntityIDs() || count($seqPhys->getEntityIDs()) == 0) {
      array_push($errors,"No Physical Lines found for edition ".$editionTitle." id=".$edition->getID());
    } else {//process Edition view line by line
      foreach ($seqPhys->getEntities(true) as $physicalLineSeq) {
        $seqID = $physicalLineSeq->getID();
        //calculate line label
        $label = $physicalLineSeq->getLabel();
        if (!$label) {
          $label = "seq$seqID";
        }
        //********output line header
        $rtf .= $lineStyle.$lineHeaderStyle.$label.$endStyle.$eol;
        $rtf .= getEntityFootnotesRTF($physicalLineSeq);
        //check for freetext and output as is
        if ($physicalLineSeq->getType() == "FreeText") {
          $freetext = $physicalLineSeq->getScratchProperty("freetext");
          if ($freetext) {
            $rtf .= utf8ToRtf($freetext);
          }
          $rtf .= $endParaStyle.$eol;
          continue;
        }
        $sclGIDs = $physicalLineSeq->getEntityIDs();
        $sclCnt = count($sclGIDs);
        //skip empty lines
        if ($sclCnt == 0 || strpos($sclGIDs[0],'scl:') != 0) {
          array_push($warnings,"Found physical Line without syllables ".$physicalLineSeq->getGlobalID());
          $rtf .= $endParaStyle.$eol;
          continue;
        }
        //*********render the physical line
        //set phys line state variables
        $prevTCMS = "S";
        $prevGraIsVowelCarrier = false;
        $prevGraIsAtBoundary = false;
        $prevGraIsA = false;
        $previousGraFootnotes = "";//need to delay to next grapheme to ensure placement outside TCM
        $atLineStart = true;
        //process physical line one syllable at a time.
        for ($i=0; $i < $sclCnt; $i++) {
          $sclRTF = "";
          $hasNonReconConsnt = false;//track non reconstructed consonants for not changing _. to +
          $hasNonAddedConsnt = false;//track non reconstructed consonants for changing vowel to 'a'
          $sclGID = $sclGIDs[$i];
          $syllable = new SyllableCluster(substr($sclGID,4));
          if ($syllable->hasError()) {
            array_push($warnings,"warning, error encountered while trying to open syllable ".$sclGIDs[0]." for phydical line ".$physicalLineSeq->getGlobalID()." - ".join(",",$edition->getErrors()));
            continue;
          }
          $graIDs = $syllable->getGraphemeIDs();
          $graCnt = count($graIDs);
          if ($graCnt == 0 ) {
            array_push($warnings,"Found syllable $sclGID without graphemes ");
            continue;
          }
          //process syllable one grapheme at a time
          for($j=0; $j < $graCnt; $j++) {
            $graID = $graIDs[$j];
            $grapheme = new Grapheme($graID);
            if (!$grapheme) {
              error_log("err,calculating word/syllable rtf, grapheme not available for graID $graID");
              continue;
            }
            $typ = Entity::getTermFromID($grapheme->getType());
            $isConsnt = ($typ == "Consonant");//warning!!! term dependency
            $tcms = $grapheme->getTextCriticalMark();
            if (strpos($tcms,"A") !== false && $style == "diplomatic") { //don't process grapheme and adjust state
              if (array_key_exists($graID,$graID2BoundaryMap)) {
                $prevGraIsAtBoundary = true;
              } else {
                $prevGraIsAtBoundary = false;
              }
              if ($typ == "Vowel" &&  //warning!!! term dependency
                  !$prevGraIsVowelCarrier && $hasNonAddedConsnt &&
                  strpos($prevTCMS,"A") === false) { //vowel start editorial addition output "a"
                  $sclRTF .= "a";
              }
              $prevGraIsVowelCarrier = false;
              continue;
            }
            if (strpos($tcms,"Sd") !== false && $style == "reconstructed") { //don't process grapheme and adjust state
              if (array_key_exists($graID,$graID2BoundaryMap)) {
                $prevGraIsAtBoundary = true;
              } else {
                $prevGraIsAtBoundary = false;
              }
              $prevGraIsVowelCarrier = false;
              continue;
            }
            if ($isConsnt && (!$tcms || (strpos($tcms,"R") === false))) {
              $hasNonReconConsnt = true;
            }
            if ($isConsnt && (!$tcms || (strpos($tcms,"A") === false))) {
              $hasNonAddedConsnt = true;
            }
            $graVal = $grapheme->getValue();
            if ($graVal == "ʔ") {
              $prevGraIsVowelCarrier = true;
              continue;
            }
            //check for TCM transition brackets
            $postTCMBrackets = "";
            $preTCMBrackets = "";
            if ($prevTCMS != $tcms) {
              list($postTCMBrackets,$preTCMBrackets) = getTCMTransitionBrackets($prevTCMS,$tcms,true);
            }
            if ($postTCMBrackets) {
              if ($srchStrings) {
                $postTCMBrackets = mbPregReplace($srchStrings,$rplcStrings,$postTCMBrackets);
              }
              if ($postTCMBrackets) {
                $postTCMBrackets = mbPregReplace($tcmNonRTFSrchStrings,$tcmRtfRplcStrings,$postTCMBrackets);
                $sclRTF .= $postTCMBrackets;
              }
            }
            if ($previousGraFootnotes) {//output footnotes after any closing TCM on the previous grapheme
              $sclRTF .= $previousGraFootnotes;
              $previousGraFootnotes = "";
            }
            if ($prevGraIsAtBoundary) {// since replace can return null
              $sclRTF .= " ";
            }
            if ($preTCMBrackets) {
              if ($srchStrings) {
                $preTCMBrackets = mbPregReplace($srchStrings,$rplcStrings,$preTCMBrackets);
              }
              if ($preTCMBrackets) {
                $preTCMBrackets = mbPregReplace($tcmNonRTFSrchStrings,$tcmRtfRplcStrings,$preTCMBrackets);
                $sclRTF .= $preTCMBrackets;
              }
            }
            if ($style == "diplomatic" && $tcms && (strpos($tcms,"R") !== false)) {
              if ($isConsnt){
                $sclRTF .= ($j==0?" _":"_");
              }else if ($typ == "Vowel") {//term dependency
                if ($prevGraIsVowelCarrier) {
                  $sclRTF .= "+ ";
                } else {
                  $sclRTF .= (array_key_exists($graID,$graID2BoundaryMap)?".":". ");
                  $sclRTF = preg_replace('/_+/',"_",$sclRTF);
                  $sclRTF = preg_replace('/_\./',($hasNonReconConsnt?".. ":($j || $i?" ":"")."+"),$sclRTF);
                }
              }else if ($typ != "VowelModifier"){
                $sclRTF .= "+";
              }
            } else if ($j==1 && $prevGraIsVowelCarrier && $previousA &&
                        ($prevTCMS == $tcms || (!$prevTCMS|| $prevTCMS == "S") && (!$tcms|| $tcms == "S"))) {
              $graVal = $grapheme->getValue();
              if ($graVal == 'i') {
                $sclRTF .= utf8ToRtf("ï");
              }else if ($graVal == 'u') {
                $sclRTF .= utf8ToRtf("ü");
              }else {
                $sclRTF .= utf8ToRtf($graVal);
              }
            } else {
              $sclRTF .=  utf8ToRtf($graVal);
            }
            $prevTCMS = $tcms;
            if ($graVal == "a") {
              $previousA = true;
            } else {
              $previousA = false;
            }
            $prevGraIsVowelCarrier = false;
            $previousGraFootnotes .= getEntityFootnotesRTF($grapheme);
            if (array_key_exists($graID,$graID2fnRTFMap)) {
              $previousGraFootnotes .= $graID2fnRTFMap[$graID];
            }
            if (array_key_exists($graID,$graID2BoundaryMap)) {
              $prevGraIsAtBoundary = true;
            } else {
              $prevGraIsAtBoundary = false;
            }
          }//end for graphIDs
          $previousGraFootnotes .= getEntityFootnotesRTF($syllable);
          $sclRTF = preg_replace('/_\./',"..",$sclRTF);
          $rtf .= preg_replace('/\s\s/'," ",$sclRTF);
        }//end for sclIDs
        if ($prevTCMS != "S") {//close off any TCM
          $tcmBrackets = getTCMTransitionBrackets($prevTCMS,"S");//reduce to S
          if ($tcmBrackets && $srchStrings) {
            $tcmBrackets = mbPregReplace($srchStrings,$rplcStrings,$tcmBrackets);
          }
          if ($tcmBrackets) {
            $tcmBrackets = mbPregReplace($tcmNonRTFSrchStrings,$tcmRtfRplcStrings,$tcmBrackets);
            $rtf .= $tcmBrackets;
          }
        }
        if ($previousGraFootnotes) {
          $rtf .= $previousGraFootnotes;
        }
        //for non-diplomatic styles output hyphen for words that go across physical lines
        if ($style != "diplomatic" && !$prevGraIsAtBoundary) {
          $rtf .= "-";
        }
        $rtf .= $endParaStyle.$eol;
      }
      $rtf .= $endRTF;
    }
  }
  if (count($errors)) {
    header("Content-type: text/html;  charset=utf-8");
    echo "Errors encountered trying to export editions Structure in RTF. Errors: ".join(", ",$errors);
  } else {
    if ($isDownload) {
      header("Content-type: text/rtf;  charset=utf-8");
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
    global $footnoteStart, $footnoteEnd, $eol, $tab, $space, $style, $typeIDs;
    $fnRTF = "";
    if ( $linkedAnoIDsByType = $entity->getLinkedAnnotationsByType()) {
      foreach ($typeIDs as $typeID) {
        if (array_key_exists($typeID,$linkedAnoIDsByType)) {
          foreach ($linkedAnoIDsByType[$typeID] as $anoID) {
            $annotation = new Annotation($anoID);
            $anoText = $annotation->getText();
            if ($anoText) {
              $fnRTF .= $footnoteStart.htmlToRTF(utf8ToRtf($anoText)).$footnoteEnd.$space.$eol;
            }
          }
        }
      }
    }
    return $fnRTF;
  }

/**
  * walk the token hierarchy and update the render mapping
  *
  * @param $entity is the token or compound being walked
  * @param $ctxGIDs string array of GIDs (prefix:ID) for containing entities
  * @global context string representing the containing context
  * @param isLastEnt boolean identifying whether the entity ends at a token boundary
  *
  * @todo remove next Entity code as this is handled with TCM in physical line
  */
  function walkTokenContext($entity, $cntx=null) {
    global $seqID2LabelMap, $graID2BoundaryMap,$graID2fnRTFMap, $warnings, $lastGraphemeID;
    //guard for undefined entity
    if (!$entity) {
      array_push($warnings,"Call to walkTokenContext in exportRTFEdition with no entity ");
    }
    $ctxGIDs = $cntx;
    $prefix = $entity->getEntityTypeCode();
    $entID = $entity->getID();
    $entGID = $entity->getGlobalID();
    if (!$ctxGIDs) {//add the entity's GID to context
      $ctxGIDs = array("$entGID");
    } else {
      array_push($ctxGIDs,"$entGID");
    }
    switch ($prefix) {
      case 'cmp':
        $compound = $entity;
        if ($compound->getComponentIDs() && count($compound->getComponentIDs())>0) {
          foreach ($compound->getComponents(true) as $component) {
            walkTokenContext($component,$ctxGIDs);//recurse
            $fnRTF = getEntityFootnotesRTF($component);//check for footnotes
            if ($fnRTF) {
              if ($lastGraphemeID) {
                if (array_key_exists($lastGraphemeID,$graID2fnRTFMap)) {
                  $graID2fnRTFMap[$lastGraphemeID] .= $fnRTF;
                } else {
                  $graID2fnRTFMap[$lastGraphemeID] = $fnRTF;
                }
              }
            }
          }
          $fnRTF = getEntityFootnotesRTF($compound);
          if ($fnRTF) {
            if ($lastGraphemeID) {
              if (array_key_exists($lastGraphemeID,$graID2fnRTFMap)) {
                $graID2fnRTFMap[$lastGraphemeID] .= $fnRTF;
              } else {
                $graID2fnRTFMap[$lastGraphemeID] = $fnRTF;
              }
            }
          }
        } else {
          array_push($warnings,"Call to walkTokenContext in exportRTFEdition with compound $entGID which has no contained entity IDs");
        }
        break;
      case 'tok':
        $token = $entity;
        $graIDs = $token->getGraphemeIDs();
        if (count($graIDs)>0) {
          $lastGraphemeID = array_pop($graIDs);
          $fnRTF = getEntityFootnotesRTF($token);
          if ($fnRTF) {
            if ($lastGraphemeID) {
              if (array_key_exists($lastGraphemeID,$graID2fnRTFMap)) {
                $graID2fnRTFMap[$lastGraphemeID] .= $fnRTF;
              } else {
                $graID2fnRTFMap[$lastGraphemeID] = $fnRTF;
              }
            }
          }
        } else {
          array_push($warnings,"Call to walkTokenContext in exportRTFEdition with token $entGID which has no grapheme IDs");
        }
        break;
      default:
        array_push($warnings,"Call to walkTokenContext in exportRTFEdition found invalid GID $prefix:$entID");
    }
    //no cntx means top level word so use last graID to mark end of word boundary
    if (!$cntx && $lastGraphemeID) {
      $graID2BoundaryMap[$lastGraphemeID] = $entGID;
    }
  }

?>
