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

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Lemmas.php';
  require_once dirname(__FILE__) . '/../model/entities/Inflections.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;

  $dbMgr = new DBManager();
  $retVal = array();
  $gra2SclMap = array();
  $edn2CatMap = array();
  $segID2sclIDs = array();
  $cknLookup = array();
  $catID = (array_key_exists('cat',$_REQUEST)? $_REQUEST['cat']:null);
//  $ednID = (array_key_exists('edn',$_REQUEST)? $_REQUEST['edn']:null);
  $entities = array( 'insert' => array(),
                     'update' => array());
  $entities["update"] = array( 'edn' => array(),
                               'seq' => array(),
                               'cmp' => array(),
                               'cat' => array(),
                               'lem' => array(),
                               'inf' => array(),
                               'tok' => array(),
                               'ano' => array(),
                               'atb' => array());
  $termInfo = getTermInfoForLangCode('en');

  $anoIDs = array();
  $atbIDs = array();
  $ednIDs = array();
  $errors = array();
  $warnings = array();
  $entityIDs = array();
  //find catalogs
  $catalog = new Catalog($catID);
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    array_push($warnings,"Warning no catalog available for id $catID .");
  } else if (!$catalog->isMarkedDelete()){
    $catID = $catalog->getID();
    $entities['update']['cat'][$catID] = array('description'=> $catalog->getDescription(),
                                               'id' => $catID,
                                               'value'=> $catalog->getTitle(),
                                               'readonly' => $catalog->isReadonly(),
                                               'ednIDs' => $catalog->getEditionIDs(),
                                               'typeID' => $catalog->getTypeID());
    $AnoIDs = $catalog->getAnnotationIDs();
    if (count($AnoIDs) > 0) {
      $entities['update']['cat'][$catID]['annotationIDs'] = $AnoIDs;
      $anoIDs = array_merge($anoIDs,$AnoIDs);
    }
    $AtbIDs = $catalog->getAttributionIDs();
    if (count($AtbIDs) > 0) {
      $entities['update']['cat'][$catID]['attributionIDs'] = $AtbIDs;
      $atbIDs = array_merge($atbIDs,$AtbIDs);
    }
    $lemmas = new Lemmas("lem_catalog_id = $catID and not lem_owner_id = 1");
    if ($lemmas->getCount() > 0) {
      $lemIDs = array();
      foreach($lemmas as $lemma) {
        $lemID = $lemma->getID();
        array_push($lemIDs,$lemID);
        if ($lemID && !array_key_exists($lemID, $entities["update"]['lem'])) {
          $entities["update"]['lem'][$lemID] = array( 'value'=> $lemma->getValue(),
                                 'id' => $lemID,
                                 'readonly' => $lemma->isReadonly(),
                                 'trans' => $lemma->getTranslation(),
                                 'search' => $lemma->getSearchValue(),
                                 'gloss' => $lemma->getDescription(),
                                 'typeID' => $lemma->getTypeID(),
                                 'certainty' => $lemma->getCertainty(),
                                 'catID' => $lemma->getCatalogID(),
                                 'entityIDs' => $lemma->getComponentIDs(),
                                 'pos' => $lemma->getPartOfSpeech(),
                                 'spos' => $lemma->getSubpartOfSpeech(),
                                 'order' => $lemma->getHomographicOrder(),
                                 'decl' => $lemma->getDeclension(),
                                 'gender' => $lemma->getGender(),
                                 'class' => $lemma->getVerbalClass(),
                                 'sort' => $lemma->getSortCode(),
                                 'sort2' => $lemma->getSortCode2());
          if ($lemma->getCompoundAnalysis()) {
            $entities["update"]['lem'][$lemID]['compAnalysis'] = $lemma->getCompoundAnalysis();
          }
          if ($lemma->getScratchProperty('phonetics')) {
            $entities["update"]['lem'][$lemID]['phonetics'] = $lemma->getScratchProperty('phonetics');
          }
          $lemCompIDs = $lemma->getComponentIDs();
          if (count($lemCompIDs) > 0) {
            foreach ($lemCompIDs as $gid) {
              list($entPrefix,$entID) = explode(':',$gid);
              if (!array_key_exists($entPrefix,$entityIDs)) {
                $entityIDs[$entPrefix] = array($entID);
              } else if (!in_array($entID,$entityIDs[$entPrefix])){ //add if doesn't exist
                array_push($entityIDs[$entPrefix],$entID);
              }
            }
          }
          $AnoIDs = $lemma->getAnnotationIDs();
          if (count($AnoIDs) > 0) {
            $entities["update"]['lem'][$lemID]['annotationIDs'] = $AnoIDs;
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $AtbIDs = $lemma->getAttributionIDs();
          if (count($AtbIDs) > 0) {
            $entities["update"]['lem'][$lemID]['attributionIDs'] = $AtbIDs;
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
        }
        $entities["update"]['cat'][$catID]['lemIDs'] = $lemIDs;
      }
    }
  } else {
    array_push($errors,"Error no catalog available for id $catID .");
  }
  if (count( $atbIDs) > 0) {
    $entityIDs['atb'] = $atbIDs;
  }
  if (count( $anoIDs) > 0) {
    $entityIDs['ano'] = $anoIDs;
  }
  if (count( $entityIDs) > 0) {
    getRelatedEntities($entityIDs);
  }
  // strip away empty entityType arrays
  foreach ($entities['update'] as $prefix => $entityArray) {
    if ( count($entityArray) == 0) {
      unset($entities['update'][$prefix]);
    }
  }

  $retVal = array("entities" => $entities,
                  "termInfo" => $termInfo);
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }

  function getRelatedEntities($entityIDs) {
    global $entities,$anoIDs,$atbIDs,$gra2SclMap,$segID2sclIDs;
    static $prefixProcessOrder = array('lnk','seq','lem','inf','cmp','tok','scl','gra','seg','bln','atb','ano');//important attr before anno to terminate
    if (!$entityIDs) return;
    $prefix = null;
    foreach ($prefixProcessOrder as $entCode) {
      if (array_key_exists( $entCode,$entityIDs) && count($entityIDs[$entCode]) > 0) {
        $prefix = $entCode;
        break;
      }
    }
    if (!$prefix) {
      return;
    }
    $tempIDs = $entityIDs[$prefix];
    if (count($tempIDs) > 0 && !array_key_exists($prefix,$entities['update'])) {
      $entities['update'][$prefix] = array();
    }
    $entIDs = array();
    foreach ($tempIDs as $entID){//skip any already processed
      if ($entID && !array_key_exists($entID,$entities['update'][$prefix])) {
        array_push($entIDs,$entID);
      }
    }
    unset($entityIDs[$prefix]);//we have captured the ids of this entity type remove them so we progress in the recursive call
    if (count($entIDs) > 0) {
      switch ($prefix) {
        case 'seq':
          $sequences = new Sequences("seq_id in (".join(",",$entIDs).")",null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($sequences as $sequence) {
            $seqID = $sequence->getID();
            if ($seqID && !array_key_exists($seqID, $entities['update']['seq'])) {
              $entities['update']['seq'][$seqID] = array( 'label'=> $sequence->getLabel(),
                                     'id' => $seqID,
                                     'value'=> $sequence->getLabel(),
                                     'readonly' => $sequence->isReadonly(),
                                     'superscript' => $sequence->getSuperScript(),
                                     'typeID' => $sequence->getTypeID());
              $sEntIDs = $sequence->getEntityIDs();
              if (count($sEntIDs) > 0) {
                $entities['update']['seq'][$seqID]['entityIDs'] = $sEntIDs;
                foreach ($sEntIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (!array_key_exists($entPrefix,$entityIDs)) {
                    $entityIDs[$entPrefix] = array($entID);
                  } else { //add if doesn't exist
                    array_push($entityIDs[$entPrefix],$entID);
                  }
                }
              }
              $AnoIDs = $sequence->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['seq'][$seqID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $AtbIDs = $sequence->getAttributionIDs();
              if (count($AtbIDs) > 0) {
                $entities['update']['seq'][$seqID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'cmp':
          $compounds = new Compounds("cmp_id in (".join(",",$entIDs).")",null,null,null);
          $compounds->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($compounds as $compound) {
            $cmpID = $compound->getID();
            if ($cmpID && !array_key_exists($cmpID, $entities['update']['cmp'])) {
              $entities['update']['cmp'][$cmpID] = array( 'value'=> $compound->getValue(),
                                     'id' => $cmpID,
                                     'transcr' => $compound->getTranscription(),
                                     'readonly' => $compound->isReadonly(),
                                     'case' => $compound->getCase(),
                                     'class' => $compound->getClass(),
                                     'type' => $compound->getType(),
                                     'tokenIDs' => $compound->getTokenIDs(),
                                     'sort' => $compound->getSortCode(),
                                     'sort2' => $compound->getSortCode2());
              $cEntIDs = $compound->getComponentIDs();
              if (count($cEntIDs) > 0) {
                $entities['update']['cmp'][$cmpID]['entityIDs'] = $cEntIDs;
                foreach ($cEntIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (array_key_exists($entPrefix,$entityIDs)) {
                    //if (!in_array($entID,$entityIDs[$entPrefix])) {
                      array_push($entityIDs[$entPrefix],$entID);
                    //}
                  } else {
                    $entityIDs[$entPrefix] = array($entID);
                  }
                }
              }
              $AnoIDs = $compound->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['cmp'][$cmpID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $AtbIDs = $compound->getAttributionIDs();
              if (count($AtbIDs) > 0) {
                $entities['update']['cmp'][$cmpID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'tok':
          $tokens = new Tokens("tok_id in (".join(",",$entIDs).")",null,null,null);
          $tokens->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $graIDs = array();
          $processedTokIDs = array();
          foreach ($tokens as $token) {
            $tokID = $token->getID();
            if ($tokID && !array_key_exists($tokID, $entities['update']['tok'])) {
              $entities['update']['tok'][$tokID] = array( 'value'=> $token->getValue(),
                                     'id' => $tokID,
                                     'transcr' => $token->getTranscription(),
                                     'readonly' => $token->isReadonly(),
                                     'affix' => $token->getNominalAffix(),
                                     'sort' => $token->getSortCode(),
                                     'sort2' => $token->getSortCode2(),
                                     'syllableClusterIDs' => array());
              array_push($processedTokIDs,$tokID);
              $tGraIDs = $token->getGraphemeIDs();
              if (count($tGraIDs) > 0) {
                $entities['update']['tok'][$tokID]['graphemeIDs'] = $tGraIDs;
              }
              $AnoIDs = $token->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['tok'][$tokID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $AtbIDs = $token->getAttributionIDs();
              if (count($AtbIDs) > 0) {
                $entities['update']['tok'][$tokID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'gra':
          $graphemes = new Graphemes("gra_id in (".join(",",$entIDs).")",null,null,null);
          $graphemes->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($graphemes as $grapheme) {
            $graID = $grapheme->getID();
            if ($graID && !array_key_exists($graID, $entities['update']['gra'])) {
              $entities['update']['gra'][$graID] = array( 'value'=> $grapheme->getValue(),
                                     'id' => $graID,
                                     'txtcrit' => $grapheme->getTextCriticalMark(),
                                     'readonly' => $grapheme->isReadonly(),
                                     'alt' => $grapheme->getAlternative(),
                                     'emmend' => $grapheme->getEmmendation(),
                                     'type' => $grapheme->getType(),
                                     'sort' => $grapheme->getSortCode());
              if ($grapheme->getDecomposition()) {
                 $entities['update']['gra'][$graID]['decomp'] = preg_replace("/:/",'',$grapheme->getDecomposition());
              }
              $AnoIDs = $grapheme->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['gra'][$graID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
            }
          }
          break;
        case 'scl':
          $syllables = new SyllableClusters("scl_id in (".join(",",$entIDs).")",null,null,null);
          $syllables->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $sclIDs = array();
          foreach ($syllables as $syllable) {
            $sclID = $syllable->getID();
            if ($sclID && !array_key_exists($sclID, $entities['update']['scl'])) {
              $entities['update']['scl'][$sclID] = array( 'value'=> $syllable->getValue(),
                                                'txtcrit' => $syllable->getTextCriticalMark(),
                                                'id' => $sclID,
                                                'readonly' => $syllable->isReadonly(),
                                                'sort' => $syllable->getSortCode(),
                                                'sort2' => $syllable->getSortCode2());
              $sGraIDs = $syllable->getGraphemeIDs();
              if (count($sGraIDs) > 0) {
                $entities['update']['scl'][$sclID]['graphemeIDs'] = $sGraIDs;
                if (!array_key_exists('gra',$entityIDs)) {
                  $entityIDs['gra'] = array();
                }
                foreach ($sGraIDs as $graID) {
                  if (!in_array($graID,$entityIDs['gra'])) {
                      array_push($entityIDs['gra'],$graID);
                  }
                }
              }
              $segID = $syllable->getSegmentID();
              if (count($segID) > 0) {
                $entities['update']['scl'][$sclID]['segID'] = $segID;
                if (!array_key_exists($segID,$segID2sclIDs)) {
                  $segID2sclIDs[$segID] = array($sclID);
                } else if (!in_array($segID,$entityIDs['seg'])) {
                    array_push($segID2sclIDs[$segID],$sclID);
                }
                if (!array_key_exists('seg',$entityIDs)) {
                  $entityIDs['seg'] = array($segID);
                } else if (!in_array($segID,$entityIDs['seg'])) {
                    array_push($entityIDs['seg'],$segID);
                }
              }
              $AnoIDs = $syllable->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['scl'][$sclID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $AtbIDs = $syllable->getAttributionIDs();
              if (count($AtbIDs) > 0) {
                $entities['update']['scl'][$sclID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'atb':
          $attributions = new Attributions("atb_id in (".join(",",$entIDs).")",null,null,null);
          $attributions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($attributions as $attribution) {
            $atbID = $attribution->getID();
            if ($atbID && !array_key_exists($atbID, $entities['update']['atb'])) {
              $entities['update']['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
                                      'id' => $atbID,
                                      'value'=> $attribution->getTitle().($attribution->getDetail()?": ".$attribution->getDetail():''),
                                      'readonly' => $attribution->isReadonly(),
                                      'grpID' => $attribution->getGroupID(),
                                      'bibID' => $attribution->getBibliographyID(),
                                      'description' => $attribution->getDescription(),
                                      'detail' => $attribution->getDetail(),
                                      'types' => $attribution->getTypes());
              $AnoIDs = $attribution->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['atb'][$atbID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
           }
          }
          break;
        case 'ano':
          $annotations = new Annotations("ano_id in (".join(",",$entIDs).")",null,null,null);
          $annotations->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($annotations as $annotation) {
            $anoID = $annotation->getID();
            if ($anoID && !array_key_exists($anoID, $entities['update']['ano'])) {
              $entities['update']['ano'][$anoID] = array( 'text'=> $annotation->getText(),
                                      'id' => $anoID,
                                      'modStamp' => $annotation->getModificationStamp(),
                                      'linkedFromIDs' => $annotation->getLinkFromIDs(),
                                      'linkedToIDs' => $annotation->getLinkToIDs(),
                                      'value' => ($annotation->getText() ||
                                                  $annotation->getURL()),//todo reformat this to have semantic term with entity value
                                      'readonly' => $annotation->isReadonly(),
                                      'url' => $annotation->getURL(),
                                      'typeID' => $annotation->getTypeID());
              $vis = $annotation->getVisibilityIDs();
              if (in_array(6,$vis)) {
                $vis = "Public";
              } else if (in_array(3,$vis)) {
                $vis = "User";
              } else {
                $vis = "Private";
              }
              $entities['update']['ano'][$anoID]['vis'] = $vis;
              $AnoIDs = $annotation->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['ano'][$atbID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $AtbIDs = $annotation->getAnnotationIDs();
              if (count($AtbIDs) > 0) {
                $entities['update']['ano'][$atbID]['annotationIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'inf':
          $inflections = new Inflections("inf_id in (".join(",",$entIDs).")",null,null,null);
          $inflections->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $infIDs = array();
          foreach ($inflections as $inflection) {
            $infID = $inflection->getID();
            if ($infID && !array_key_exists($infID, $entities['update']['inf'])) {
              $entities['update']['inf'][$infID] = array( 'id' => $infID,
                                     'readonly' => $inflection->isReadonly(),
                                     'chaya' => $inflection->getChaya(),
                                     'entityIDs' => $inflection->getComponentIDs(),
                                     'case' => $inflection->getCase(),
                                     'certainty' => $inflection->getCertainty(),
                                     'gender' => $inflection->getGender(),
                                     'num' => $inflection->getGramaticalNumber(),
                                     'person' => $inflection->getVerbalPerson(),
                                     'voice' => $inflection->getVerbalVoice(),
                                     'tense' => $inflection->getVerbalTense(),
                                     'mood' => $inflection->getVerbalMood(),
                                     'conj2nd' => $inflection->getSecondConjugation());
              $infCompIDs = $inflection->getComponentIDs();
              if (count($infCompIDs) > 0) {
                foreach ($infCompIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (!array_key_exists($entPrefix,$entityIDs)) {
                    $entityIDs[$entPrefix] = array($entID);
                  } else if (!in_array($entID,$entityIDs[$entPrefix])){ //add if doesn't exist
                    array_push($entityIDs[$entPrefix],$entID);
                  }
                }
              }
              $AnoIDs = $inflection->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['update']['inf'][$infID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $AtbIDs = $inflection->getAttributionIDs();
              if (count($AtbIDs) > 0) {
                $entities['update']['inf'][$infID]['annotationIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
      }//end switch $prefix
    }//end if count $entIDs
    if (count(array_keys($entityIDs)) > 0) {
      getRelatedEntities($entityIDs);
    }
  }
?>
