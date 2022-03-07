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
* saveGlossary
*
* saves entity data for glossary entities lem and inf given data of the form
*
*   data =
*   {
*     cmd: "createLem",
*     ednID: 9,
*     tokID: 5,
*     lemProps: {"value": "dhadu"}
*   }
*
*
* return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
*
* {
*   "success": true,
*   "entities": { "insert":
*                 { "cat":
*                   { 25:
*                     {'value': 'Richard CKM xxx Glossary',
*                      'readonly': 'false',
*                      'typeID': 535,
*                      'ednIDs': [9]
*                     }
*                   }
*                 }
*                 { "lem":
*                   { 155:
*                     {'value': 'dhadu',
*                      'readonly': 'false',
*                      'catID': 25,
*                      'entityIDs': ['tok:5']
*                     }
*                   }
*                 }
*               }
* }
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

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Inflection.php');
require_once (dirname(__FILE__) . '/../model/entities/Lemmas.php');//include Lemma.php
require_once (dirname(__FILE__) . '/../model/entities/Catalogs.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $cmd = null;
  if ( isset($data['cmd'])) {//get command
    $cmd = $data['cmd'];
  } else {
    array_push($errors,"saveLemma requires a command - aborting save");
  }
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $ednID = null;
  $edition = null;
  if ( isset($data['ednID'])) {//get edition
    $edition = new Edition($data['ednID']);
    if ($edition->hasError()) {
      array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
    } else {
      $ednID = $edition->getID();
    }
  }
  $catID = null;
  $catalog = null;
  if ( isset($data['catID'])) {//get catalog
    $catalog = new Catalog($data['catID']);
    if ($catalog->hasError()) {
      array_push($errors,"creating catalog - ".join(",",$catalog->getErrors()));
    } else {
      $catID = $catalog->getID();
    }
  }
  $lemID = null;
  $lemma = null;
  if ( isset($data['lemID'])) {//get lemma
    $lemma = new Lemma($data['lemID']);
    if ($lemma->hasError()) {
      array_push($errors,"creating lemma - ".join(",",$lemma->getErrors()));
    } else {
      $lemID = $lemma->getID();
      if (!$catID) {
        $catalog = new Catalog($lemma->getCatalogID());
        if ($catalog->hasError()) {
          array_push($errors,"creating catalog - ".join(",",$catalog->getErrors()));
        } else {
          $catID = $catalog->getID();
        }
      } else if ($lemma->getCatalogID() != $catID) {
        array_push($errors,"lemma doesn't belong to glossary");
      }
    }
  }
  if ($catalog && $catalog->isReadonly()) {
    array_push($errors,"insufficient previledges for Glossary");
  }
  if ($lemma && $lemma->isReadonly()) {
    array_push($errors,"insufficient previledges to update Lemma");
  }
  $tokGID = null;
  $token = null;
  if ( isset($data['tokGID'])) {//get token
    $token = EntityFactory::createEntityFromGlobalID($data['tokGID']);
    if ($token->hasError()) {
      array_push($errors,"creating token - ".join(",",$token->getErrors()));
    } else {
      $tokGID = $token->getGlobalID();
    }
  }
  $infGID = null;
  $inflection = null;
  if ( isset($data['infID'])) {//get inflection
    $inflection = new Inflection($data['infID']);
    if ($inflection->hasError()) {
      array_push($errors,"creating inflection - ".join(",",$inflection->getErrors()));
    } else {
      $infGID = $inflection->getGlobalID();
    }
  }
  $lemProps = null;
  if ( isset($data['lemProps'])) {//get lemma props
    $lemProps = $data['lemProps'];
  }
  $infProps = null;
  if ( isset($data['infProps'])) {//get inflection
    $infProps = $data['infProps'];
  }
  $linkToEntGID = null;
  if ( isset($data['entGID'])) {//get linked entity GID
    $linkToEntGID = $data['entGID'];
  }
  $linkTypeID = null;
  if ( isset($data['linkTypeID'])) {//get link type id
    $linkTypeID = $data['linkTypeID'];
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}
if (count($errors) == 0) {
  switch ($cmd) {
    case "createLem":
      if (!$tokGID || (!$catID && !$ednID) || !$lemProps) {
        array_push($errors,"insufficient data to create lemma for Glossary");
      } else {//create lemma
        if (!$catID) { // need to create Glossary catalog
          $catalog = new Catalog();
          $catalog->setTitle("Glossary - ".getUserName().date(" j-n-Y "));
          $catalog->setOwnerID(getUserID());
          $catalog->setTypeID(Entity::getIDofTermParentLabel("glossary-catalogtype"));//term dependency
          $catalog->setOwnerID($defOwnerID);
          $catalog->setVisibilityIDs($defVisIDs);
          if ($defAttrIDs){
            $catalog->setAttributionIDs($defAttrIDs);
          }
          $catalog->setEditionIDs(array($ednID));
          $catalog->save();
          if ($catalog->hasError()) {
            array_push($errors,"error creating catalog '".$catalog->getDescription()."' - ".$catalog->getErrors(true));
          }else{
            addNewEntityReturnData('cat',$catalog);
            //flag allresourses as dirty
//            invalidateAllTextResources();
            $catID = $catalog->getID();
            //need to update the editions calculate catIDs field
            $catalogs = new Catalogs($ednID." = ANY (\"cat_edition_ids\")",null,null,null);
            $catalogs->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            $catIDs = array();
            foreach ($catalogs as $ednCatalog) {
              $catID = $ednCatalog->getID();
              array_push($catIDs,$catID);
            }
            if (count($catIDs)) {
              addUpdateEntityReturnData('edn',$ednID,'catIDs',$catIDs);
            }
          }
        }
        $lemma = new Lemma();
        $lemma->setCatalogID($catID);
        $lemma->setOwnerID($defOwnerID);
        $lemma->setVisibilityIDs($defVisIDs);
        if ($defAttrIDs){
          $lemma->setAttributionIDs($defAttrIDs);
        }
        $lemma->setComponentIDs(array($tokGID));
        foreach($lemProps as $propname => $propval) {
          switch ($propname) {
            case 'value':
              $lemma->setValue($propval);
              break;
            case 'trans':
              $lemma->setTranslation($propval);
              break;
            case 'gloss':
              $lemma->setDescription($propval);
              break;
            case 'pos':
              $lemma->setPartOfSpeech((!$propval || $propval == "")?null:$propval);
              break;
            case 'spos':
              $lemma->setSubpartOfSpeech((!$propval || $propval == "")?null:$propval);
              break;
            case 'order':
              $lemma->setHomographicOrder($propval);
              break;
            case 'decl':
              $lemma->setDeclension((!$propval || $propval == "")?null:$propval);
              break;
            case 'gender':
              $lemma->setGender((!$propval || $propval == "")?null:$propval);
              break;
            case 'class':
              $lemma->setVerbalClass((!$propval || $propval == "")?null:$propval);
              break;
            case 'certainty':
              $lemma->setCertainty($propval);
              break;
          }
        }
        if (!$lemma->getValue() && $token) {
          $lemma->setValue($token->getValue());
        }
        $lemma->save();
        if ($lemma->hasError()) {
          array_push($errors,"error creating lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
        }else{
          addNewEntityReturnData('lem',$lemma);
          $lemID = $lemma->getID();
        }
        $lemmas = new Lemmas("lem_catalog_id = $catID");
        if ($lemmas->getCount() > 0) {
          $lemIDs = array();
          foreach($lemmas as $lemma) {
            $lemID = $lemma->getID();
            array_push($lemIDs,$lemID);
          }
          addUpdateEntityReturnData('cat',$catID,'lemIDs',$lemIDs);
        }
      }
      break;
    case "updateLem":
      foreach($lemProps as $propname => $propval) {
        switch ($propname) {
          case 'value':
            $lemma->setValue($propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'value',$lemma->getValue());
            break;
          case 'trans':
            $lemma->setTranslation($propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'trans',$lemma->getTranslation());
            break;
          case 'gloss':
            $lemma->setDescription($propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'gloss',$lemma->getDescription());
            break;
          case 'pos':
            $lemma->setPartOfSpeech((!$propval || $propval == "")?null:$propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'pos',$lemma->getPartOfSpeech());
            break;
          case 'spos':
            $lemma->setSubpartOfSpeech((!$propval || $propval == "")?null:$propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'spos',$lemma->getSubpartOfSpeech());
            break;
          case 'order':
            $lemma->setHomographicOrder($propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'order',$lemma->getHomographicOrder());
            break;
          case 'decl':
            $lemma->setDeclension((!$propval || $propval == "")?null:$propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'decl',$lemma->getDeclension());
            break;
          case 'gender':
            $lemma->setGender((!$propval || $propval == "")?null:$propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'gender',$lemma->getGender());
            break;
          case 'class':
            $lemma->setVerbalClass((!$propval || $propval == "")?null:$propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'class',$lemma->getVerbalClass());
            break;
          case 'certainty':
            $lemma->setCertainty((!$propval || $propval == "")?array(3,3,3,3,3):$propval);
            addUpdateEntityReturnData('lem',$lemma->getID(),'certainty',$lemma->getCertainty());
            break;
          default:
            $lemma->storeScratchProperty($propname, $propval);
            if (!$propval) {
                addRemovePropertyReturnData('lem', $lemma->getID(), $propname);
            } else {
                addUpdateEntityReturnData('lem', $lemma->getID(), $propname, $lemma->getScratchProperty($propname));
            }
    }
      }
      $lemma->save();
      if ($lemma->hasError()) {
        array_push($errors,"error updating lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
      }
      break;
    case "linkTok":
      if ($inflection) {// add token to passed inflection
        $entIDs = $inflection->getComponentIDs();
        if ($entIDs && is_array($entIDs)) {
          if (in_array($tokGID,$entIDs)) {
            break;
          }
          array_push($entIDs,$tokGID);
        } else {
          $entIDs = array($tokGID);
        }
        $inflection->setComponentIDs($entIDs);
        $inflection->save();
        if ($inflection->hasError()) {
          array_push($errors,"error updating inflection '".$inflection->getID()."' - ".$inflection->getErrors(true));
        } else {
          addUpdateEntityReturnData('inf',$inflection->getID(),'entityIDs',$inflection->getComponentIDs());
        }
      } else if ($lemma) {
        $entIDs = $lemma->getComponentIDs();
        if ($entIDs && is_array($entIDs)) {
          if (in_array($tokGID,$entIDs)) {
            break;
          }
          array_push($entIDs,$tokGID);// fix 612 here  ensure unique.
        } else {
          $entIDs = array($tokGID);
        }
        $lemma->setComponentIDs($entIDs);
        $lemma->save();
        if ($lemma->hasError()) {
          array_push($errors,"error updating lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
        } else {
          addUpdateEntityReturnData('lem',$lemma->getID(),'entityIDs',$lemma->getComponentIDs());
        }
      }
      break;
    case "inflectTok":
      if ($lemma) {
        //if token in inf then remove from old before then proceed
        if ($inflection){
          unlinkInflectedToken($tokGID,$inflection,$lemma);
        } else {
          unlinkUninflectedToken($tokGID,$lemma);
        }
        //calc lemma's inflection hash lookup
        $lemEntities = $lemma->getComponents(true);
        $hashLU = array();
        if ($lemEntities) {
          foreach ($lemEntities as $entity) {
            if(substr($entity->getGlobalID(),0,3) != "inf") {
              continue;
            }
            $gender = $entity->getGender();
            $num = $entity->getGramaticalNumber();
            $case = $entity->getCase();
            $person = $entity->getVerbalPerson();
            $voice = $entity->getVerbalVoice();
            $tense = $entity->getVerbalTense();
            $mood = $entity->getVerbalMood();
            $conj2nd = $entity->getSecondConjugation();
            $cf = $entity->getCertainty();
            $hash = "".($tense?$tense.$cf[0]:"").
                    "-".($voice?$voice.$cf[1]:"").
                    "-".($mood?$mood.$cf[2]:"").
                    "-".($gender?$gender.$cf[3]:"").
                    "-".($num?$num.$cf[4]:"").
                    "-".($case?$case.$cf[5]:"").
                    "-".($person?$person.$cf[6]:"").
                    "-".($conj2nd?$conj2nd.$cf[7]:"");
            if (array_key_exists($hash,$hashLU)) {
              array_push($warnings,"lemma ".$lemma->getGlobalID()." has duplicate inflections ".
                                  $entity->getGlobalID()." and ".$hashLU[$hash]->getGlobalID());
              continue;
            }
            $hashLU[$hash] = $entity;
          }
        }
        //calc tok's inflection hash (infProps)
        if (array_key_exists('certainty',$infProps)) {
          $cf = $infProps['certainty'];
        } else {
          $cf = array(3,3,3,3,3,3,3,3);
        }//          certainty = {'tense':3,'voice':3,'mood':3,'gender':3,'num':3,'case':3,'person':3,'conj2nd':3},
        $hash = "".(array_key_exists('tense',$infProps)?$infProps['tense'].$cf[0]:"").
                "-".(array_key_exists('voice',$infProps)?$infProps['voice'].$cf[1]:"").
                "-".(array_key_exists('mood',$infProps)?$infProps['mood'].$cf[2]:"").
                "-".(array_key_exists('gender',$infProps)?$infProps['gender'].$cf[3]:"").
                "-".(array_key_exists('num',$infProps)?$infProps['num'].$cf[4]:"").
                "-".(array_key_exists('case',$infProps)?$infProps['case'].$cf[5]:"").
                "-".(array_key_exists('person',$infProps)?$infProps['person'].$cf[6]:"").
                "-".(array_key_exists('conj2nd',$infProps)?$infProps['conj2nd'].$cf[7]:"");
        //if hash lookup add token to inf
        if (array_key_exists($hash,$hashLU)) {
          $tokInflection = $hashLU[$hash];
          $entIDs = $tokInflection->getComponentIDs();
          if ($entIDs && is_array($entIDs)) {
            if (!in_array($tokGID,$entIDs)) {
              array_push($entIDs,$tokGID);
            }
          } else {
            $entIDs = array($tokGID);
          }
          $tokInflection->setComponentIDs($entIDs);
          $tokInflection->save();
          if ($tokInflection->hasError()) {
            array_push($errors,"error updating inflection '".$tokInflection->getID()."' - ".$tokInflection->getErrors(true));
          } else {
            addUpdateEntityReturnData('inf',$tokInflection->getID(),'entityIDs',$tokInflection->getComponentIDs());
          }
        } else {
        //else create new inflection insert newInfID into lem list order
          $tokInflection = new Inflection();
          $tokInflection->setOwnerID($defOwnerID);
          $tokInflection->setVisibilityIDs($defVisIDs);
          if ($defAttrIDs){
            $tokInflection->setAttributionIDs($defAttrIDs);
          }
          $tokInflection->setComponentIDs(array($tokGID));
          $tokInflection->save();
          foreach($infProps as $propname => $propval) {//TODO  account for error
            switch ($propname) {
              case 'gender':
                $tokInflection->setGender((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'gender',$tokInflection->getGender());
                break;
              case 'num':
                $tokInflection->setGramaticalNumber((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'num',$tokInflection->getGramaticalNumber());
                break;
              case 'case':
                $tokInflection->setCase((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'case',$tokInflection->getCase());
                break;
              case 'person':
                $tokInflection->setVerbalPerson((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'person',$tokInflection->getVerbalPerson());
                break;
              case 'voice':
                $tokInflection->setVerbalVoice((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'tense',$tokInflection->getVerbalTense());
                break;
              case 'tense':
                $tokInflection->setVerbalTense((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'tense',$tokInflection->getVerbalTense());
                break;
              case 'mood':
                $tokInflection->setVerbalMood((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'mood',$tokInflection->getVerbalMood());
                break;
              case 'conj2nd':
                $tokInflection->setSecondConjugation((!$propval || $propval == "")?null:$propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'conj2nd',$tokInflection->getSecondConjugation());
                break;
              case 'certainty':
                $tokInflection->setCertainty($propval);
//                addUpdateEntityReturnData('inf',$tokInflection->getID(),'certainty',$tokInflection->getCertainty());
                break;
            }
          }
          $tokInflection->save();
          if ($tokInflection->hasError()) {
            array_push($errors,"error updating inflection '".$tokInflection->getID()."' - ".$tokInflection->getErrors(true));
          } else {
            addNewEntityReturnData('inf',$tokInflection);
          }
          $entIDs = $lemma->getComponentIDs();
          if ($entIDs && is_array($entIDs)) {
            array_push($entIDs,$tokInflection->getGlobalID());
          } else {
            $entIDs = array($tokInflection->getGlobalID());
          }
          $lemma->setComponentIDs($entIDs);
          $lemma->save();
          if ($lemma->hasError()) {
            array_push($errors,"error updating lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
          } else {
            addUpdateEntityReturnData('lem',$lemma->getID(),'entityIDs',$lemma->getComponentIDs());
          }
        }
      }
      break;
    case "unlinkTok":
      if ($lemma) {
        if (!$infGID) { //unlink uninflected token
         unlinkUninflectedToken($tokGID,$lemma);
        } else {//inflected token
          unlinkInflectedToken($tokGID,$inflection,$lemma);
        }
      }
      break;
    case "removeLink":
      if ($lemma && $linkToEntGID && $linkTypeID) {
        //find link annotation
        $link = getRelationshipLink($lemma->getGlobalID(),$linkToEntGID,$linkTypeID);
        if ($link) {
          //mark for delete
          $anoID = $link->getID();
          $link->markForDelete();
          //remove id from lemma annotationIDs
          $annoIDs = $lemma->getAnnotationIDs();
          $index = array_search($anoID,$annoIDs);
          array_splice($annoIDs,$index,1);
          $lemma->setAnnotationIDs($annoIDs);
          $lemma->save();
          //set update information
          addRemoveEntityReturnData('ano',$anoID);
          addUpdateEntityReturnData('lem',$lemma->getID(),'annotationIDs',$lemma->getAnnotationIDs());
          addUpdateEntityReturnData('lem',$lemma->getID(),'relatedEntGIDsByType',$lemma->getRelatedEntitiesByLinkType());
        }
      }
      break;
    default:
      array_push($errors,"unknown command");
  }
  if ($lemma->getScratchProperty("entry")) { //clear cached entry html
    $lemma->storeScratchProperty("entry",'');
    $lemma->save();
  }
  invalidateCachedCatalogEntities($catalog->getID());
  invalidateCachedViewerLemmaHtmlLookup($catalog->getID(),null);
}

/*
addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
} else {
addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs());
addUpdateEntityReturnData('tok',$token->getID(),'sort', $token->getSortCode());
addUpdateEntityReturnData('tok',$token->getID(),'sort2', $token->getSortCode2());
}
addNewEntityReturnData('tok',$newSplitToken);
if (isset($newSplitToken2)) {
addNewEntityReturnData('tok',$newSplitToken2);
}

//update compound heirarchy
if (count($errors) == 0 && isset($compounds) && count($compounds) > 0) {//update compounds
while (count($compounds)) {
$compound = array_shift($compounds);
$componentGIDs = $compound->getComponentIDs();
$oldTokCmpIndex = array_search($oldTokCmpGID,$componentGIDs);
array_splice($componentGIDs,$oldTokCmpIndex,1,$tokCmpReplaceGIDs);
$tokCmpReplaceGIDs = $componentGIDs;
$oldTokCmpGID = $compound->getGlobalID();
if (!$compound->isReadonly()) {
$compound->markForDelete();
addRemoveEntityReturnData('cmp',$compound->getID());
}
if ($compound->hasError()) {
array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
break;
}
}
}
$newTokCmpGID = $tokCmpReplaceGIDs;//ensure this gets altered in the text div
}

}else { // only updated
addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
}
//update edition seqIDs
$edition->setSequenceIDs($edSeqIds);
$edition->save();
if ($edition->hasError()) {
array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
}else{
addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
}
*/

$retVal["success"] = false;
if (count($errors)) {
  $retVal["errors"] = $errors;
} else {
  $retVal["success"] = true;
}
if (count($warnings)) {
  $retVal["warnings"] = $warnings;
}
if (count($entities)) {
  $retVal["entities"] = $entities;
}
if ($healthLogging && $catalog) {
//  $retVal["editionHealth"] = checkEditionHealth($catalog->getID(),false);
}
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}

function unlinkInflectedToken($tokGID,$inflection,$lemma) {
//  global $inflection,$lemma;
  $entIDs = $inflection->getComponentIDs();
  $tokCmpIndex = array_search($tokGID,$entIDs);
  if ($tokCmpIndex !== false){
    array_splice($entIDs,$tokCmpIndex,1);
    $inflection->setComponentIDs($entIDs);
    $inflection->save();
    if ($inflection->hasError()) {
      array_push($errors,"error unlinking token $tokGID inflection $infGID - ".$inflection->getErrors(true));
    } else if (count($entIDs) > 0) {
      addUpdateEntityReturnData('inf',$inflection->getID(),'entityIDs',$inflection->getComponentIDs());
    } else { //last inflected token so remove inflection
      $inflection->markForDelete();
      addRemoveEntityReturnData('inf',$inflection->getID());
      $entIDs = $lemma->getComponentIDs();
      $infIndex = array_search($inflection->getGlobalID(),$entIDs);
      array_splice($entIDs,$infIndex,1);
      $lemma->setComponentIDs($entIDs);
      $lemma->save();
      if ($lemma->hasError()) {
        array_push($errors,"error unlinking token $tokGID lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
      } else {
        addUpdateEntityReturnData('lem',$lemma->getID(),'entityIDs',$lemma->getComponentIDs());
      }
    }
  }
}
function unlinkUninflectedToken($tokGID,$lemma) {
//  global $lemma;
  $entIDs = $lemma->getComponentIDs();
  if ($entIDs && is_array($entIDs)) {
    $tokCmpIndex = array_search($tokGID,$entIDs);
    if ($tokCmpIndex !== false){
      array_splice($entIDs,$tokCmpIndex,1);
      $lemma->setComponentIDs($entIDs);
      $lemma->save();
      if ($lemma->hasError()) {
        array_push($errors,"error unlinking token $tokGID lemma '".$lemma->getValue()."' - ".$lemma->getErrors(true));
      } else {
        addUpdateEntityReturnData('lem',$lemma->getID(),'entityIDs',$lemma->getComponentIDs());
      }
    }
  }
}

?>
