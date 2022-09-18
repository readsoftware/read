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
  * exportRTFGlossary.php
  *
  *  A service that returns .rtf for an edition
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start();

  header('Pragma: no-cache');
//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Lemmas.php';
  require_once dirname(__FILE__) . '/../model/entities/Inflections.php';
  require_once dirname(__FILE__) . '/../model/entities/Edition.php';
  require_once dirname(__FILE__) . '/../model/entities/Text.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;

  $dbMgr = new DBManager();
  $retVal = array();
  $catID = (array_key_exists('catID',$_REQUEST)? $_REQUEST['catID']:null);
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $refresh = (array_key_exists('refreshWordMap',$_REQUEST) ? $_REQUEST['refreshWordMap']:
              ((array_key_exists('refreshLookUps',$_REQUEST))? $_REQUEST['refreshLookUps']:
               ((array_key_exists('refresh',$_REQUEST))? $_REQUEST['refresh']:
                (defined('DEFAULTHTMLGLOSSARYREFRESH')?DEFAULTHTMLGLOSSARYREFRESH:0))));
  $useTranscription = (!array_key_exists('usevalue',$_REQUEST)? true:false);
  $hideHyphens = (!array_key_exists('showhyphens',$_REQUEST)? true:false);
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $anoIDs = array();
  $atbIDs = array();
//  $cmpTokTag2LocLabel = array();
  $errors = array();
  $warnings = array();
  //find catalogs
  $catalog = new Catalog($catID);
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    array_push($warnings,"Warning need valid catalog id $catID.");
  } else {
    $tcmSrchStrings = array("⟨","⟩","⟪","⟫","\{","\}");
    $tcmRtfRplcStrings = array("\\u10216\\'3f","\\u10217\\'3f","\\u10218\\'3f","\\u10219\\'3f","\\'7b","\\'7d");
    $rtf ='{\rtf1\adeflang1025\ansi\ansicpg10000\uc1\adeff0\deff0'."\n".
          '{\fonttbl{\f0\fbidi \fnil\fcharset0\fprq2{\*\panose 02020603050405020304}Times New Roman;}'."\n".
          '{\f38\fbidi \fnil\fcharset0\fprq2{\*\panose 02000503060000020004}Gandhari Unicode;}}'."\n".
          '{\stylesheet{\ql \li0\ri0\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin0\itap0 \fs20\lang1031\langfe1031\cgrid\langnp1031\langfenp1031 \snext0 \sqformat \spriority0 Normal;}'."\n".
          '{\s1\ql \fi-425\li425\ri0\sa200\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin425\itap0 \rtlch\fcs1 \ab\ai\af0\afs24\alang2057 \ltrch\fcs0 \fs24\lang2057\langfe1031\cgrid\langnp2057\langfenp1031 \sbasedon0 \snext1 \sqformat \spriority0 Glossary;}'."\n".
          '{\*\cs55 \additive \sunhideused \spriority1 Default Paragraph Font;}'."\n".
          '{\*\cs02 \additive \f38 \sqformat \spriority1 etym;}'."\n".
          '{\*\cs51 \additive \b\i\fs24\f38 \sqformat \spriority1 lemma;}'."\n".
          '{\*\cs03 \additive \i \sqformat \spriority1 pali;}'."\n".
          '{\*\cs04 \additive \i\f38 \sqformat \spriority1 attestation;}'."\n".
          '{\*\cs05 \additive \f38 \sqformat \spriority1 pos;}'."\n".
          '{\*\cs06 \additive \f38 \sqformat \spriority1 gloss;}'."\n".
          '{\*\cs07 \additive \f38 \sqformat \spriority1 inflection;}'."\n".
          '{\*\cs08 \additive \f38 \sqformat \spriority1 annotationlemma;}'."\n".
          '{\*\cs09 \additive \f38 \sqformat \spriority1 annotationattestation;}'."\n".
          '{\*\cs10 \additive \f38 \sqformat \spriority1 line;}'."\n".
          '{\*\cs11 \additive \b0\f38\super \sbasedon55 \sqformat \spriority1 homograph;}'."\n".
          '{\*\cs12 \additive \f38\cf3\lang1031\langfe0\langnp1031 \sbasedon1 \sqformat \spriority1 link;}'."\n".
          '{\*\cs13 \additive \f38\cf3\lang1031\langfe0\langnp1031 \sbasedon12 \sqformat \spriority1 linkalso;}'."\n".
          '{\*\cs14 \additive \f38\cf1\lang1031\langfe0\langnp1031 \sbasedon12 \sqformat \spriority1 linkcustom;}'."\n".
          '{\*\cs15 \additive \b\i\f38\fs24 \sbasedon01 \sqformat \spriority1 lemmarelated;}'."\n".
          '{\*\cs16 \additive \b\fs48\f38 \sqformat \spriority1 glossarytitle;}'."\n".
          '{\*\cs26 \additive \i \sqformat \spriority1 italic;}'."\n".
          '}'."\n".
          '{\colortbl;\red0\green255\blue255;\red255\green0\blue0;\red0\green175\blue0;}'."\n".
          '\pard\plain \ltrpar\s1\ql'."\n".
          '\fi-426\li426\ri0\sa200\nowidctlpar\wrapdefault\hyphpar0\aspalpha\aspnum\faauto\adjustright\rin0\lin426\itap0'."\n\n";

          $titleStyle = '{\cs16\b\fs48\f38 ';
          $lemStyle = '{\cs51\b\i\fs24\f38 ';
          $posStyle = '{\cs05\f38 ';
          $glossStyle = '{\cs06\f38 ';
          $etymStyle = '{\cs02\f38 ';
          $lemAnoStyle = '{\cs08\f38 ';
          $attestedAnoStyle = '{\cs09\f38 ';
          $attestedStyle = '{\cs04\i\f38 ';
          $relatedStyle = '{\cs15\b\i\f38\fs24 ';
          $infStyle = '{\cs07\f38 ';
          $linRefStyle = '{\cs10\f38 ';
          $space = '{ }';
          $italicStyle = '{\cs26\i ';
          $commaRed = '{\f38\cf2 ,}';
          $fullstopRed = '{\f38\cf2 .}';
          $comma = ',';
          $fullstop = '.';
          $prequote = '{\f38 \u8220\\\'d2}';
          $postquote = '{\f38 \u8221\\\'d3}';
          $homStyle = '{\cs11\b0\f38\super ';
          $linkStyle = '{\cs12\f38\cf3\lang1031\langfe0\langnp1031 ';
          $endStyle = '}';
          $softReturn = '{\line}';
          $hardReturn = '{\par}';
          $eol = "";//this helps readability of raw RTF in a basic line editor and does not affect layout in Word import
    //output glossary title
    if ($glossaryTitle = $catalog->getTitle()) {
      $rtf .= $titleStyle.utf8ToRtf($glossaryTitle).$endStyle.$eol.$hardReturn.$eol;
    }

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
        //output homograph order if exist
        if ($lemmaOrder = $lemma->getHomographicOrder()) {
          $rtf .= $homStyle.$lemmaOrder.$endStyle.$eol;
        }
        //output lemma value
        $lemmaValue = utf8ToRtf(preg_replace('/ʔ/','',$lemma->getValue()));
        $rtf .= $lemStyle.$lemmaValue.$endStyle.$eol;
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
        $lemmaSpos = $lemmaSposID && array_key_exists($lemmaSposID,$termLookup) ? $termLookup[$lemmaSposID] : '';
        if ($lemmaSpos  == "common adj.") {//warning term dependency
          $lemmaSpos = "adj.";
        }
        $lemmaCF = $lemma->getCertainty();//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        $lemmaGenderID = $lemma->getGender();
        //output lemma POS
        $lemmaGender = $lemmaGenderID && is_numeric($lemmaGenderID) && Entity::getTermFromID($lemmaGenderID) ? Entity::getTermFromID($lemmaGenderID) : null;
        $posNode = null;
        //calculate the lemmas POS label
        if ($isNoun && $lemmaGender) {//warning Order Dependency for display code lemma gender (like for nouns) hides subPOS hides POS
          $rtf .= $space.$eol.$posStyle.$lemmaGender.($lemmaCF[2]==2?'(?)':'').$endStyle.$eol;
        } else if ($lemmaSpos) {
          $rtf .= $space.$eol.$posStyle.$lemmaSpos.($lemmaCF[1]==2?'(?)':'').$endStyle.$eol;
        }else if ($lemmaPos) {
          $rtf .= $space.$eol.$posStyle.$lemmaPos.($lemmaCF[0]==2?'(?)':'').$endStyle.$eol;
        }
        // output Etymology
        if ($lemmaEtym = $lemma->getDescription()) {
          //replace embedded HTML markup
          $rtf .= $space.$eol.$etymStyle.htmlToRTF(utf8ToRtf($lemmaEtym)).$endStyle.$eol;
        }
        // output Translation
        if ($lemmaGloss = $lemma->getTranslation()) {
          //replace embedded HTML markup
          $rtf .= $comma.$eol.$space.$eol.$prequote.$eol.$glossStyle.htmlToRTF(utf8ToRtf($lemmaGloss)).$endStyle.$eol.$fullstop.$eol.$postquote.$eol;
        }
        // output any lemma notes
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
              $rtf .= $space.$eol.$lemAnoStyle."(".htmlToRTF(utf8ToRtf($lemmaCommentary)).")".$endStyle.$eol;
            }
          }
        }
        // tranforms for attest form value
        $pattern = array("/aʔi/","/aʔu/","/ʔ/","/°/","/\/\/\//","/#/","/◊/","/◈/","/◯/");
        $replacement = array("aï","aü","","","","","","","");

        // Start calculation for displaying morphology and attested forms for this lemma
        $lemmaComponents = $lemma->getComponents(true);
        if ($lemmaComponents && $lemmaComponents->getCount() && !$lemmaComponents->getError()) {
          $rtf .= $softReturn.$eol;
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
              //calculate inflection string
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
                $value = preg_replace($pattern,$replacement,$value);
                $value = mbPregReplace($tcmSrchStrings,$tcmRtfRplcStrings,$value);
                $value = utf8ToRtf($value);
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
                $loc = $inflectionComponent->getLocation().($attestedCommentary?$attestedAnoStyle." (".htmlToRTF(utf8ToRtf($attestedCommentary)).")".$endStyle.$eol:"");
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
              $value = mbPregReplace($tcmSrchStrings,$tcmRtfRplcStrings,$value);
              $value = utf8ToRtf($value);
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
              $loc = $lemmaComponent->getLocation().($attestedCommentary?$attestedAnoStyle." (".htmlToRTF(utf8ToRtf($attestedCommentary)).")".$endStyle.$eol:"");
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
                  $rtf .= "; ";
                }
                $rtf .= $infStyle;
                //calc header using previous found node indicies low to high output non matching
                $inflectionHeaderRTF = "";
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
                  $inflectionHeaderRTF .= $inflCatVal." ";
                }
                //if no inflection header then we must be unclear values
                if ($inflectionHeaderRTF == '') {
                  $inflectionHeaderRTF = "unclear: ";
                }
                $rtf .= $inflectionHeaderRTF.$endStyle;
                ksort($nodePtr);
                $isFirstNode = true;
                foreach ($nodePtr as $sc => $formInfo) {
                  if ($isFirstNode) {
                    $isFirstNode = false;
                  } else {
                    $rtf .= ", ";
                  }
                  $isFirstForm = true;
                  foreach ($formInfo['value'] as $formTranscr => $locInfo) {
                    if ($isFirstForm) {
                      $isFirstForm = false;
                    } else {
                      $rtf .= ", ";
                    }
                    $rtf .= $attestedStyle.$formTranscr.$endStyle;
                    $sortedLocs = array_keys($locInfo['loc']);
                    usort($sortedLocs,"compareWordLocations");
                    $isFirstLoc = true;
                    foreach ($sortedLocs as $formLoc) {
                      $cntLoc = $locInfo['loc'][$formLoc];
                      if ($isFirstLoc) {
                        $rtf .= $space.$eol.$linRefStyle;
                        $isFirstLoc = false;
                      } else {
                        $rtf .= ", ";
                      }
                      //remove internal ordinal
                      $locParts = explode(":",$formLoc);
                      if ($locParts && count($locParts) == 3) {
                        if (strpos(trim($locParts[0]),"sort") === 0) {
                          $formLoc = $locParts[2];
                        } else {
                          $formLoc = $locParts[0].$locParts[2];
                        }
                      } else if ($locParts && count($locParts) == 2) {
                        $formLoc = $locParts[1];
                      }
                      if (strpos($formLoc,"–")) {//replace en dash with \'96
                        $formLoc = preg_replace("/–/","\\\'96",$formLoc);
                      }
                      $rtf .= $formLoc.($cntLoc>1?" [".$cntLoc.utf8ToRtf("×]"):"");
/*
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
*/
                    }
                    $rtf .= $endStyle.$eol;
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
                $rtf .= ", ";
              }
              $isFirstForm = true;
              if (! array_key_exists('value',$formInfo)) {
                error_log(print_r($formInfo));
              }
              foreach ($formInfo['value'] as $formTranscr => $locInfo) {
                if ($isFirstForm) {
                  $isFirstForm = false;
                } else {
                  $rtf .= ", ";
                }
                $rtf .= $attestedStyle.$formTranscr.$endStyle;
                $sortedLocs = array_keys($locInfo['loc']);
                usort($sortedLocs,"compareWordLocations");
                $isFirstLoc = true;
                foreach ($sortedLocs as $formLoc) {
                  $cntLoc = $locInfo['loc'][$formLoc];
                  if ($isFirstLoc) {
                    $rtf .= $space.$eol.$linRefStyle;
                    $isFirstLoc = false;
                  } else {
                    $rtf .= ", ";
                  }
                  //remove internal ordinal
                  $locParts = explode(":",$formLoc);
                  if ($locParts && count($locParts) == 3) {
                    if (strpos(trim($locParts[0]),"sort") === 0) {
                      $formLoc = $locParts[2];
                    } else {
                      $formLoc = $locParts[0].$locParts[2];
                    }
                  } else if ($locParts && count($locParts) == 2) {
                    $formLoc = $locParts[1];
                  }
                  if (strpos($formLoc,"–")) {//replace en dash with \'96
                    $formLoc = preg_replace("/–/","\\\'96",$formLoc);
                  }
                  $rtf .= $formLoc.($cntLoc>1?" [".$cntLoc.utf8ToRtf("×]"):"");
                }
                $rtf .= $endStyle.$eol;
              }
            }
          }
          $rtf .= $fullstop.$eol;
/* 
            $firstComponent = true;
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
                    $rtf .= "; ";
                  }
                  $rtf .= $infStyle;
                  if ($isFirstKey1) {// ensure none repeating labels
                    $isFirstKey1 = false;
                    if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?') {
                      if ($lemmaPos != 'adv.' && $lemmaPos != 'ind.'){ //term dependency
                        $rtf .= "unclear: ";
                      }
                    } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 != '?') {
                      $rtf .= $key4." ";
                    } else if (!$lemmaGenderID && $lemmaSpos != 'pers.'){//handle noun supress infection gender output
                      $rtf .= $key1." ";
                    }
                  } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?') {
                    if ($lemmaPos != 'adv.' && $lemmaPos != 'ind.'){ //term dependency
                      $rtf .= "unclear: ";
                    }
                  }
                  if ($key1 != '?' || $key1 == '?' && ($key2 != '?' || $key3 != '?')) {
                    $rtf .= $key3." ".$key2." ";
                  }
                  $rtf .= $endStyle;
                  $grpNode = $groupedForms[$key1][$key2][$key3][$key4];
                  ksort($grpNode);
                  $isFirstNode = true;
                  foreach ($grpNode as $sc => $formInfo) {
                    if ($isFirstNode) {
                      $isFirstNode = false;
                    } else {
                      $rtf .= ", ";
                    }
                    $isFirstForm = true;
                    foreach ($formInfo['value'] as $formTranscr => $locInfo) {
                      if ($isFirstForm) {
                        $isFirstForm = false;
                      } else {
                        $rtf .= ", ";
                      }
                      $rtf .= $attestedStyle.$formTranscr.$endStyle;
                      $sortedLocs = array_keys($locInfo['loc']);
                      usort($sortedLocs,"compareWordLocations");
                      $isFirstLoc = true;
                      foreach ($sortedLocs as $formLoc) {
                        $cntLoc = $locInfo['loc'][$formLoc];
                        if ($isFirstLoc) {
                          $rtf .= $space.$eol.$linRefStyle;
                          $isFirstLoc = false;
                        } else {
                          $rtf .= ", ";
                        }
                        //remove internal ordinal
                        $locParts = explode(":",$formLoc);
                        if ($locParts && count($locParts) == 3) {
                          if (strpos(trim($locParts[0]),"sort") === 0) {
                            $formLoc = $locParts[2];
                          } else {
                            $formLoc = $locParts[0].$locParts[2];
                          }
                        } else if ($locParts && count($locParts) == 2) {
                          $formLoc = $locParts[1];
                        }
                        if (strpos($formLoc,"–")) {//replace en dash with \'96
                          $formLoc = preg_replace("/–/","\\\'96",$formLoc);
                        }
                        $rtf .= $formLoc.($cntLoc>1?" [".$cntLoc.utf8ToRtf("×]"):"");
                      }
                      $rtf .= $endStyle.$eol;
                    }
                    //$rtf .= $endStyle.$eol;
                  }
                }
              }
            }
          }
          $rtf .= $fullstop.$eol;
        } else {
          $rtf .= $space;
*/
        } else {
          $rtf .= $space;
        }
        $relatedGIDsByLinkType = $lemma->getRelatedEntitiesByLinkType();
        $seeLinkTypeID = Entity::getIDofTermParentLabel('See-LemmaLinkage');
        $cfLinkTypeID = Entity::getIDofTermParentLabel('Compare-LemmaLinkage');
        $altLinkTypeID = Entity::getIDofTermParentLabel('Alternate-LemmaLinkage');
        $relatedNode = null;
        if ($relatedGIDsByLinkType && array_key_exists($seeLinkTypeID,$relatedGIDsByLinkType)) {
          $isFirst = true;
          $linkText = 'see';
          if ($hasAttestations) {
            $rtf .= $softReturn.$eol;
            $linkText = 'See also';
          }
          $seeLinks = array();
          foreach ($relatedGIDsByLinkType[$seeLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            if ($entity && !$entity->hasError()) {
              if (method_exists($entity,'getValue')) {
                $value = utf8ToRtf(preg_replace($pattern,$replacement,$entity->getValue()));
              } else {
                $value = $linkGID;
              }
              if (method_exists($entity,'getSortCode')) {
                $sort = $entity->getSortCode();
              } else {
                $sort = substr($linkGID,4);
              }
              $seeLinks[$sort] = $value;
            }
          }
          if (count($seeLinks)) {
            uksort($seeLinks,"compareSortKeys");
            foreach ($seeLinks as $sort => $value) {
              if ($isFirst) {
                $isFirst = false;
                $rtf .= $linkStyle.$linkText.$endStyle.$eol;
                $rtf .= $space.$eol.$relatedStyle.$value.$endStyle.$eol;
              }else{
                $rtf .= $comma.$eol.$space.$eol.$relatedStyle.$value.$endStyle.$eol;
              }
            }
          }
          $rtf .= $fullstop.$eol;
        }
        $cfLinks = array();
        if ($relatedGIDsByLinkType && array_key_exists($cfLinkTypeID,$relatedGIDsByLinkType)) {
          $isFirst = true;
          $linkText = 'Cf.';
          $rtf .= $softReturn.$eol;
          foreach ($relatedGIDsByLinkType[$cfLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            if ($entity && !$entity->hasError()) {
              if (method_exists($entity,'getValue')) {
                $value = utf8ToRtf(preg_replace('/ʔ/','',$entity->getValue()));
              } else {
                $value = $linkGID;
              }
              if (method_exists($entity,'getSortCode')) {
                $sort = $entity->getSortCode();
              } else {
                $sort = substr($linkGID,4);
              }
              $cfLinks[$sort] = $value;
            }
          }
          if (count($cfLinks)) {
            uksort($cfLinks,"compareSortKeys");
            foreach ($cfLinks as $sort => $value) {
              if ($isFirst) {
                $isFirst = false;
                $rtf .= $linkStyle.$linkText.$endStyle.$eol;
                $rtf .= $space.$eol.$relatedStyle.$value.$endStyle.$eol;
              }else{
                $rtf .= $comma.$eol.$space.$eol.$relatedStyle.$value.$endStyle.$eol;
              }
            }
          }
          $rtf .= $fullstop.$eol;
        }
        $altLinks = array();
        if ($relatedGIDsByLinkType && array_key_exists($altLinkTypeID,$relatedGIDsByLinkType)) {
          $isFirst = true;
          $linkText = 'Alt.';
          $rtf .= $softReturn.$eol;
          foreach ($relatedGIDsByLinkType[$altLinkTypeID] as $linkGID) {
            $entity = EntityFactory::createEntityFromGlobalID($linkGID);
            if ($entity && !$entity->hasError()) {
              if (method_exists($entity,'getValue')) {
                $value = utf8ToRtf(preg_replace('/ʔ/','',$entity->getValue()));
              } else {
                $value = $linkGID;
              }
              if (method_exists($entity,'getSortCode')) {
                $sort = $entity->getSortCode();
              } else {
                $sort = substr($linkGID,4);
              }
              $altLinks[$sort] = $value;
            }
          }
          if (count($altLinks)) {
            uksort($altLinks,"compareSortKeys");
            foreach ($altLinks as $sort => $value) {
              if ($isFirst) {
                $isFirst = false;
                $rtf .= $linkStyle.$linkText.$endStyle.$eol;
                $rtf .= $space.$eol.$relatedStyle.$value.$endStyle.$eol;
              }else{
                $rtf .= $comma.$eol.$space.$eol.$relatedStyle.$value.$endStyle.$eol;
              }
            }
          }
          $rtf .= $fullstop.$eol;
        }
        $rtf .= $hardReturn.$eol;//end paragraph
      }
      $rtf .= '}';
    } // else
  }

  if ($isDownload) {
    ob_clean();
    header("Content-type: text/rtf;  charset=UTF-8");
    header("Content-Disposition: attachment; filename=readGlossary.rtf");
    header("Expires: 0");
  }

  echo $rtf;
  return;

  function htmlToRTF($strWithHTML) {
    global $italicStyle, $endStyle;
    $htmlSrchStrings = array('/\<i\>/','/\<\/i\>/','/\<b\>/','/\<\/b\>/','/\<u\>/','/\<\/u\>/');
    $htmlReplStrings = array($italicStyle,$endStyle,'{\b ','}','{\ul ','}');
    $strWithRTF = preg_replace($htmlSrchStrings,$htmlReplStrings,$strWithHTML);
    return $strWithRTF;
  }

?>
