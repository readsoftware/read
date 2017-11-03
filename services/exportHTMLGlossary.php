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
  * loadCatalogEntities
  *
  *  A service that returns a json structure of the catalog (unrestricted type) requested along with its
  *  lemma and the lemma's inflections, compounds, and tokens.
  *  There is NO CACHING of this information.
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
  ob_start('ob_gzhandler');

  header("Content-type: text/html;  charset=utf-8");
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
  $exportFilename = (array_key_exists('filename',$_REQUEST)? $_REQUEST['filename']:null);//will export to READ_FILE_STORE/DBNAME/catTag
  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $useTranscription = (!array_key_exists('usevalue',$_REQUEST)? true:false);
  $hideHyphens = (!array_key_exists('showhyphens',$_REQUEST)? true:false);
  $refreshWordMap = (array_key_exists('refreshWordMap',$_REQUEST)? true:false);
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $anoIDs = array();
  $atbIDs = array();
  $cmpTokTag2LocLabel = array();
  $errors = array();
  $warnings = array();
  //find catalogs
  $catalog = new Catalog($catID);
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    array_push($warnings,"Warning need valid catalog id $catID.");
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
            '<link rel="stylesheet" href="'.SITE_BASE_PATH.'/common/css/exGlossary.css" type="text/css"/>'.
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
          $etym->setAttribute("class", "etym");
          $etymNode = $htmlDomDoc->importNode($etym,true);
          $lemmaDefNode->appendChild($etymNode);
        }
        if ($lemmaGloss = $lemma->getTranslation()) {
          $lemmaGloss = html_entity_decode($lemmaGloss);
          $dDoc = new DOMDocument();
          $dDoc->loadXML("<span>$lemmaGloss</span>",LIBXML_NOXMLDECL);
          $gloss = $dDoc->getElementsByTagName("span")->item(0);
          $gloss->setAttribute("class", "gloss");
          $glossNode = $htmlDomDoc->importNode($gloss,true);
          $lemmaDefNode->appendChild($glossNode);
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
        if ($lemmaComponents && $lemmaComponents->getCount()) {
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
                        list($tref,$ord,$label) = explode(":",$formLoc);
                        if (strpos($tref,"sort") === 0) {
                          $formLoc = $label;
                        } else {
                          $formLoc = $tref.$label;
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
        $seeLinkTypeID = Entity::getIDofTermParentLabel('See-LinkageType');
        $cfLinkTypeID = Entity::getIDofTermParentLabel('Compare-LinkageType');
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
  }

  if ($isDownload) {
    header("Content-Disposition: attachment; filename=readGlossary.html");
    header("Expires: 0");
  }

  echo $htmlDomDoc->saveHTML();
  exit;

  function addAttestationNode($htmlDomDoc,$entryNode,$tokCmp, $infString = null) {
    global $cmpTokTag2LocLabel;
    $entTag = $tokCmp->getEntityTypeCode().$tokCmp->getID();
    $entGID = $tokCmp->getGlobalID();
    $attestNode = $htmlDomDoc->createElement('div');
    $attestNode->setAttribute('class',"attestation $entTag");
    $entryNode->appendChild($attestNode);
    $attestedFormNode = $htmlDomDoc->createElement('span',preg_replace('/ʔ/','',$tokCmp->getValue()));
    $attestedFormNode->setAttribute('class',"attestedform");
    $attestNode->appendChild($attestedFormNode);
    if ($infString) {
      $infNode = $htmlDomDoc->createElement('span',$infString);
      $infNode->setAttribute('class',"inflection");
      $attestNode->appendChild($infNode);
    }
    $lnRefNode = $htmlDomDoc->createElement('span',$cmpTokTag2LocLabel[$entTag]);
    $lnRefNode->setAttribute('class',"lineref");
    $attestNode->appendChild($lnRefNode);
  }

  function getWordTagToLocationLabelMap($catalog, $refreshWordMap) {
    global $term_parentLabelToID;
    $catID = $catalog->getID();
    if (!$refreshWordMap && array_key_exists("cache-cat$catID",$_SESSION) &&
          array_key_exists('wrdTag2LocLabel',$_SESSION["cache-cat$catID"])) {
      return $_SESSION["cache-cat$catID"]['wrdTag2LocLabel'];
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
            $ednLabel='sort'.$text->getID();
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
              $fSclID = $sclIDs[0];
              $sclTag = 'scl'.$fSclID;
              if ( array_key_exists($sclTag,$sclTagToLabel)) {
                $label = $sclTagToLabel[$sclTag];
              } else {
                $tokID = $fToken->getID();
                error_log("no start label founds for $sclTag of tok$tokID from $prefix$id for sequence $ednSeqTag having label $ednLabel");
                $label = null;
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
                    $label .= "-" . $label2;
                  }
                }
                $wrdTag2LocLabel[$wtag] = $ednLabel . $label;
              } else {
                $wrdTag2LocLabel[$wtag] = $defLabel;
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
                  $label .= "-" . $label2;
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
