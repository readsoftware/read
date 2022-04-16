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
  $refreshWordMap = ((array_key_exists('refreshWordMap',$_REQUEST) || array_key_exists('refreshLookUps',$_REQUEST))? true:false);
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
//    $cmpTokTag2LocLabel = getWordTagToLocationLabelMap($catalog,$refreshWordMap);
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
    if ($glossaryTitle = $catalog->getTitle()) {
      $rtf .= $titleStyle.utf8ToRtf($glossaryTitle).$endStyle.$eol.$hardReturn.$eol;
    }

    $glossaryCommentTypeID = Entity::getIDofTermParentLabel('Glossary-CommentaryType');//warning!!! term dependency
    $lemmas = new Lemmas("lem_catalog_id = $catID and not lem_owner_id = 1","lem_sort_code,lem_sort_code2");
    if ($lemmas->getCount() > 0) {
      $lemIDs = array();
      foreach($lemmas as $lemma) {
        $hasAttestations = false;
        $lemGID = $lemma->getGlobalID();
        $lemTag = 'lem'.$lemma->getID();
        if ($lemmaOrder = $lemma->getHomographicOrder()) {
          $rtf .= $homStyle.$lemmaOrder.$endStyle.$eol;
        }
        $lemmaValue = utf8ToRtf(preg_replace('/ʔ/','',$lemma->getValue()));
        $rtf .= $lemStyle.$lemmaValue.$endStyle.$eol;
        $lemmaGender = $lemma->getGender();
        $lemmaPosID = $lemma->getPartOfSpeech();
        $lemmaPos = $lemmaPosID && array_key_exists($lemmaPosID,$termLookup) ? $termLookup[$lemmaPosID] : '';
        $isVerb = false;
        if ($lemmaPos) {
          $isVerb = ($lemmaPos == 'v.');
        }
        $lemmaSposID = $lemma->getSubpartOfSpeech();
        $lemmaSpos = $lemmaSposID && array_key_exists($lemmaSposID,$termLookup) ? $termLookup[$lemmaSposID] : '';
        if ($lemmaSpos  == "common adj.") {//warning term dependency
          $lemmaSpos = "adj.";
        }
        $lemmaCF = $lemma->getCertainty();//[3,3,3,3,3],//posCF,sposCF,genCF,classCF,declCF
        if ($lemmaGender) {//warning Order Dependency for display code lemma gender (like for nouns) hides subPOS hides POS
          $rtf .= $space.$eol.$posStyle.$termLookup[$lemmaGender].($lemmaCF[2]==2?'(?)':'').$endStyle.$eol;
        } else if ($lemmaSpos) {
          $rtf .= $space.$eol.$posStyle.$lemmaSpos.($lemmaCF[1]==2?'(?)':'').$endStyle.$eol;
        }else if ($lemmaPos) {
          $rtf .= $space.$eol.$posStyle.$lemmaPos.($lemmaCF[0]==2?'(?)':'').$endStyle.$eol;
        }
        if ($lemmaEtym = $lemma->getDescription()) {
          //replace embedded HTML markup
          $rtf .= $space.$eol.$etymStyle.htmlToRTF(utf8ToRtf($lemmaEtym)).$endStyle.$eol;
        }
        if ($lemmaGloss = $lemma->getTranslation()) {
          //replace embedded HTML markup
          $rtf .= $comma.$eol.$space.$eol.$prequote.$eol.$glossStyle.htmlToRTF(utf8ToRtf($lemmaGloss)).$endStyle.$eol.$fullstop.$eol.$postquote.$eol;
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
              $rtf .= $space.$eol.$lemAnoStyle."(".htmlToRTF(utf8ToRtf($lemmaCommentary)).")".$endStyle.$eol;
            }
          }
        }
        $pattern = array("/aʔi/","/aʔu/","/ʔ/","/°/","/\/\/\//","/#/","/◊/","/◈/","/◯/");
        $replacement = array("aï","aü","","","","","","","");
        $lemmaComponents = $lemma->getComponents(true);
        if ($lemmaComponents && $lemmaComponents->getCount()) {
          $rtf .= $softReturn.$eol;
          $hasAttestations = true; // signal see also
          $groupedForms = array();
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
              if ($isVerb) {
                if ($vmood) {
                  $vmood = $termLookup[$vmood];
                }
                if ($vtense) {
                  $vtense = $termLookup[$vtense];
                  if (strtolower($vtense) == "pres." && strtolower($vmood) == "ind.") { //term dependency
                    $vmood = null;
                  }
                }
                if ($vmood) {
                  $vtensemood = $vmood.($ingCF[2]==2?'(?)':'');
                } else if ($vtense) {
                  $vtensemood = $vtense.($ingCF[0]==2?'(?)':'');
                } else {
                  $vtensemood = '?';
                }
                if (!array_key_exists($vtensemood,$groupedForms)) {
                  $groupedForms[$vtensemood] = array();
                }
                if ($num) {
                  $num = $termLookup[$num].($ingCF[4]==2?'(?)':'');
                } else {
                  $num = '?';
                }
                if (!array_key_exists($num,$groupedForms[$vtensemood])) {
                  $groupedForms[$vtensemood][$num] = array();
                }
                if ($vper) {
                  $vper = $termLookup[$vper].($ingCF[6]==2?'(?)':'');
                } else {
                  $vper = '?';
                }
                if (!array_key_exists($vper,$groupedForms[$vtensemood][$num])) {
                  $groupedForms[$vtensemood][$num][$vper] = array();
                }
                if ($conj2) {
                  $conj2 = $termLookup[$conj2].($ingCF[7]==2?'(?)':'');
                } else {
                  $conj2 = '?';
                }
                if (!array_key_exists($conj2,$groupedForms[$vtensemood][$num][$vper])) {
                  $groupedForms[$vtensemood][$num][$vper][$conj2] = array();
                }
                $node = &$groupedForms[$vtensemood][$num][$vper][$conj2];
              } else {
                if ($gen) {
                  $gen = $termLookup[$gen].($ingCF[3]==2?'(?)':'');
                } else {
                  $gen = '?';
                }
                if (!array_key_exists($gen,$groupedForms)) {
                  $groupedForms[$gen] = array();
                }
                if ($num) {
                  $num = $termLookup[$num].($ingCF[4]==2?'(?)':'');
                } else {
                  $num = '?';
                }
                if (!array_key_exists($num,$groupedForms[$gen])) {
                  $groupedForms[$gen][$num] = array();
                }
                if ($case) {
                  $case = $termLookup[$case].($ingCF[5]==2?'(?)':'');
                } else {
                  $case = '?';
                }
                if (!array_key_exists($case,$groupedForms[$gen][$num])) {
                  $groupedForms[$gen][$num][$case] = array();
                }
                if ($conj2) {
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
                $loc = $inflectionComponent->getLocation().($attestedCommentary?$attestedAnoStyle." (".htmlToRTF(utf8ToRtf($attestedCommentary)).")".$endStyle.$eol:"");
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
              //$loc = $cmpTokTag2LocLabel[$entTag].($attestedCommentary?$attestedAnoStyle." (".htmlToRTF(utf8ToRtf($attestedCommentary)).")".$endStyle.$eol:"");
              $loc = $lemmaComponent->getLocation().($attestedCommentary?$attestedAnoStyle." (".htmlToRTF(utf8ToRtf($attestedCommentary)).")".$endStyle.$eol:"");
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
            $displayOrder1 = array('m.','m.(?)','mn.','mn.(?)','n.','n.(?)','nf.','nf.(?)','f.','f.(?)','mf.','mf.(?)','mnf.','mnf.(?)','?');
            $displayOrder2 = array('sg.','sg.(?)','du.','du.(?)','pl.','pl.(?)','?');
            $displayOrder3 = array('nom.','nom.(?)','acc.','acc.(?)','instr.','instr.(?)','dat.','dat.(?)','dat/gen.','dat/gen.(?)','abl.','abl.(?)','gen.','gen.(?)','loc.','loc.(?)','voc.','voc.(?)','?');
            $displayOrder4 = array('des.','des.(?)','int.','int.(?)','?');
          }
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
//                  } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?'){
//                    $rtf .= "; ";
                  } else {
                    $rtf .= "; ";
                  }
                  $rtf .= $infStyle;
                  if ($isFirstKey1) {
                    $isFirstKey1 = false;
                    if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 == '?') {
                      if ($lemmaPos != 'adv.' && $lemmaPos != 'ind.'){ //term dependency
                        $rtf .= "unclear: ";
                      }
                    } else if ($key1 == '?' && $key2 == '?' && $key3 == '?' && $key4 != '?') {
                      $rtf .= $key4." ";
                    } else if (!$lemmaGender && $lemmaSpos != 'pers.'){//handle noun supress infection gender output
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
        }        $rtf .= $hardReturn.$eol;//end paragraph
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

  function getWordTagToLocationLabelMap($catalog, $refreshWordMap) {
    global $term_parentLabelToID;
    $catID = $catalog->getID();
    if (!$refreshWordMap && array_key_exists("cache-cat$catID".DBNAME,$_SESSION) &&
          array_key_exists('wrdTag2LocLabel',$_SESSION["cache-cat$catID".DBNAME])) {
      return $_SESSION["cache-cat$catID".DBNAME]['wrdTag2LocLabel'];
    }
    $editionIDs = $catalog->getEditionIDs();
    $wrdTag2LocLabel = array();
    $sclTagToLabel = array();
    $ednLblBySeqTag = array();
    $seqTag2EdnTag = array();
    foreach ($editionIDs as $ednID) {
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
//            $ednLabel='sort'.$text->getID();
          }
        }
        $ednSequences = $edition->getSequences(true);
        //for edition find token sequences and find physical sequences and create sclID to label
        foreach ($ednSequences as $ednSequence) {
          if ($ednSequence->getTypeID() == $term_parentLabelToID['text-sequencetype']) {//term dependency
            $txtSeqGIDs = array_merge($txtSeqGIDs,$ednSequence->getEntityIDs());
          }
          if ($ednSequence->getTypeID() == $term_parentLabelToID['textphysical-sequencetype']) {//term dependency
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
          $seqTag2EdnTag[$tag] = $ednTag;
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
          if ($label && $sclGIDs && count($sclGIDs)) {//create lookup for location of word span B11-B12
            $label = "$ord:".$label; //save ordinal of line for sorting later.
            foreach ($sclGIDs as $sclGID) {
              $tag = preg_replace("/:/","",$sclGID);
              $sclTagToLabel[$tag] = $label;
            }
          }
          $ord++;
        }
      }
      if ($ednLblBySeqTag && count($ednLblBySeqTag) > 0) {
        //for each token sequence
        foreach ($ednLblBySeqTag as $ednSeqTag => $ednLabel) {
          if ($ednLabel) {
            $ednLabel .= ":";
          }
          $ednTag = $seqTag2EdnTag[$ednSeqTag];
          $sequence = new Sequence(substr($ednSeqTag,3));
          $defLabel = $ednLabel . ($sequence->getSuperScript()?$sequence->getSuperScript():($sequence->getLabel()?$sequence->getLabel():$ednSeqTag));
          $words = $sequence->getEntities(true);
          if ($words->getCount() == 0) {
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
              if ($sclIDs && count($sclIDs) > 0) {
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
                if ($sclIDs && count($sclIDs) > 0) {
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
                    $label .= "-" . $label2;
                  }
                }
                $wrdTag2LocLabel[$wtag] = $ednLabel . $label;
              } else {
                $wrdTag2LocLabel[$wtag] = $defLabel;
              }
            } else if ($prefix == 'tok') {
              $sclIDs = $word->getSyllableClusterIDs();
              if ($sclIDs && count($sclIDs) > 0) {
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
                  $label .= "–" . ($posColon !== false?substr($label2, $posColon+1):$label2);
                }
                $wrdTag2LocLabel[$wtag] = $ednLabel . $label;
              } else {
                $wrdTag2LocLabel[$wtag] = $defLabel;
              }
            }
          }
        }
      }
    }
    $_SESSION["cache-cat$catID"]['wrdTag2LocLabel'] = $wrdTag2LocLabel;
    return $wrdTag2LocLabel;
  }
?>
