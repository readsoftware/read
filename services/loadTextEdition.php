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
  * loadTextEdition
  *
  *  A service that returns a json structure of the edition of a text along with its sequences, fragemnts, surfaces,
  *  baselines and segments need to drive the segment editor.
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

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  if (isset($argv)) {
    // handle command-line queries
    $cmdParams = array();
    $cmdStr = "cmdline params:";
    for ($i=0; $i < count($argv); ++$i) {
      if ($argv[$i][0] === '-') {
        if (@$argv[$i+1] && $argv[$i+1][0] != '-') {
        $cmdParams[$argv[$i]] = $argv[$i+1];
        ++$i;
        }else{ // map non-value dash arguments into state variables
          $cmdParams[$argv[$i]] = true;
        }
      } else {//capture others args
        array_push($cmdParams, $argv[$i]);
      }
    }
    if(@$cmdParams['-d']) {
      $_REQUEST["db"] = $cmdParams['-d'];
      $dbn = $_REQUEST["db"];
      $cmdStr .= ' db = '.$_REQUEST['db'];
    }
    if (@$cmdParams['-edn']) {
      $_REQUEST['edn'] = $cmdParams['-edn'];
      $cmdStr .= ' edn = '.$_REQUEST['edn'];
    }
    //commandline access for setting userid  TODO review after migration
    if (@$cmdParams['-u'] ) {
      if ($dbn) {
        if (!isset($_SESSION['readSessions'])) {
          $_SESSION['readSessions'] = array();
        }
        if (!isset($_SESSION['readSessions'][$dbn])) {
          $_SESSION['readSessions'][$dbn] = array();
        }
        $_SESSION['readSessions'][$dbn]['ka_userid'] = $cmdParams['-u'];
        $cmdStr .= ' uID = '.$_SESSION['readSessions'][$dbn]['ka_userid'];
      }
    }
    echo $cmdStr."\n";
  }
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once dirname(__FILE__) . '/../model/entities/Edition.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Segments.php';
  require_once dirname(__FILE__) . '/../model/entities/Graphemes.php';
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/SyllableClusters.php';
  require_once dirname(__FILE__) . '/../model/entities/Annotations.php';
  require_once dirname(__FILE__) . '/../model/entities/Attributions.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';


  $dbMgr = new DBManager();
  $dbMgr->useCommonConnect($dbMgr);
  $retVal = array();
  $txtCache = array();
  $errors = array();
  $warnings = array();
  $gra2SclMap = array();
  $segID2sclIDs = array();
  $processedTokIDs = array();
  $cknLookup = array();
  $entityIDs = array();
  $entities = array();
  $entities = array( 'insert' => array(),
                     'update' => array());
  $entities["update"] = array( 'edn' => array());
  $entities['insert'] = array( 'seq' => array(),
                               'cmp' => array(),
                               'tok' => array(),
                               'scl' => array(),
                               'gra' => array(),
                               'seg' => array(),
                               'ano' => array(),
                               'atb' => array());
  $retString = "";
  $imgIDs = array();
  $anoIDs = array();
  $atbIDs = array();
  $attrs = array();
  $publicOnly = false;
  $userOnly = false;
  $saveEditionToCache = false;
  $ednID = (array_key_exists('edn',$_REQUEST)? $_REQUEST['edn']:null);
  $edition = null;
  if ($ednID) {
    $edition = new Edition($ednID);
  }

  if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
    array_push($warnings,"Warning no edition available for id $ednID .");
  } else if ($edition->isReadonly() && !$edition->isMarkedDelete()){// edition non owner check cached snapshot
    $publicOnly = $edition->isPublic();
    $userOnly = isLoggedIn();
    if (USECACHE) {
      $retString = getCachedEdition($ednID);//get user or public cached edition if there is one.
    }
  }
  if (count($warnings) == 0 && !$retString) {// edition found and  so process/load it
    $refresh = ($edition->getScratchProperty('refresh')?$edition->getScratchProperty('refresh'):
                  (defined('DEFAULTEDITIONREFRESH')?DEFAULTEDITIONREFRESH:0));
    $saveEditionToCache = true;
    $termInfo = getTermInfoForLangCode('en');
    $dictionaryCatalogTypeID = $termInfo['idByTerm_ParentLabel']['dictionary-catalogtype'];//term dependency
    $textSeqTypeID = $termInfo['idByTerm_ParentLabel']['text-sequencetype'];//term dependency
    $textDivSeqTypeID = $termInfo['idByTerm_ParentLabel']['textdivision-text'];//term dependency
    $textPhysSeqTypeID = $termInfo['idByTerm_ParentLabel']['textphysical-sequencetype'];//term dependency
    $linePhysSeqTypeID = $termInfo['idByTerm_ParentLabel']['linephysical-textphysical'];//term dependency
    $ednID = $edition->getID();
    $retString = '{"entities":{"update":{"edn":{"'.$ednID.'":{"description":'.json_encode($edition->getDescription()).','.
                               '"id":"'.$ednID.'",'.
                               '"value":'.json_encode($edition->getDescription()).','.
                               '"readonly":'.($edition->isReadonly()?'true':'false').','.
                               '"typeID":"'.$edition->getTypeID().'",'.
                               '"editibility":"'.$edition->getOwnerID().'",'.
                               '"txtID":"'.$edition->getTextID().'",'.
                               '"seqIDs":["'.join('","',$edition->getSequenceIDs()).'"]';
//    $saveToCache = $edition->isReadonly();
    $AnoIDs = $edition->getAnnotationIDs();
    if ($AnoIDs && count($AnoIDs) > 0) {
      $retString .= ',"annotationIDs":["'.join('","',$AnoIDs).'"]';
      $anoIDs = array_merge($anoIDs,$AnoIDs);
    }
    $AtbIDs = $edition->getAttributionIDs();
    if ($AtbIDs && count($AtbIDs) > 0) {
      $retString .= ',"attributionIDs":["'.join('","',$AtbIDs).'"]';
      $atbIDs = array_merge($atbIDs,$AtbIDs);
    }
    $retString .= '}}},"insert":{';// place insert to ensure an overwrite in the local cache.
    //load edition structural sequences
    $seqIDs = $edition->getSequenceIDs();
    if ($seqIDs && count($seqIDs) > 0) {
      $condition = "seq_id in (".join(",",$seqIDs).")";
      if ($publicOnly){
        //get only public entities
        $condition .= ' and (2 = ANY ("seq_visibility_ids") or 6 = ANY ("seq_visibility_ids"))';
      } else if ($userOnly) {
        $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && seq_visibility_ids';
      }
      $sequences = new Sequences($condition,null,null,null);
      $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
      $seqRetString = null;
      if ($sequences && $sequences->getCount()>0) {
        $retString .= '"seq":{';
        $txtDivSeqIDs = array();
        $linePhysSeqIDs = array();
        $structuralSeqIDs = array();
        $seqRetString = '';
        //get physical and textdiv so that they are processed physical before token so that gra to scl map is constructed
        foreach ($sequences as $sequence) {
          $seqID = $sequence->getID();
          $seqTypeID = $sequence->getTypeID();
          if ($seqTypeID == $textSeqTypeID){
            // get all the child IDs
            $txtDivSeqIDs = $sequence->getEntityIDs();
            $txtDivSeqIDs = preg_replace("/seq\:/","",$txtDivSeqIDs);
          } else if ($seqTypeID == $textPhysSeqTypeID){
            // get all the child IDs
            $linePhysSeqIDs = $sequence->getEntityIDs();
            $linePhysSeqIDs = preg_replace("/seq\:/","",$linePhysSeqIDs);
          } else {//other structural definitions
            array_push($structuralSeqIDs,$seqID);
            continue;
          }
          if ($seqRetString != '') {
            $seqRetString .= ',';
          }
          //output container for textDiv or LinePhys
          $seqRetString .='"'.$seqID.'":{"label":"'.$sequence->getLabel().'",'.
                                        '"id":"'.$seqID.'",'.
                                        '"value":"'.$sequence->getLabel().'",'.
                                        '"readonly":'.($sequence->isReadonly()?'true':'false').','.
                                        '"editibility":"'.$sequence->getOwnerID().'",'.
                                        '"typeID":"'.$seqTypeID.'",'.
                                        '"entityIDs":['.(count($sequence->getEntityIDs())?'"'.join('","',$sequence->getEntityIDs()).'"':'').']';
          $superscript = $sequence->getSuperScript();
          if ($superscript && count($superscript) > 0) {
            $seqRetString .= ',"sup":"'.$superscript.'"';
          }
          $AnoIDs = $sequence->getAnnotationIDs();
          if ($AnoIDs && count($AnoIDs) > 0) {
            $seqRetString .= ',"annotationIDs":["'.join('","',$AnoIDs).'"]';
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $AtbIDs = $sequence->getAttributionIDs();
          if ($AtbIDs && count($AtbIDs) > 0) {
            $seqRetString .= ',"attributionIDs":["'.join('","',$AtbIDs).'"]';
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
          $seqRetString .= '}';
        }
        if ($linePhysSeqIDs && count($linePhysSeqIDs) > 0) {
          $condition = "seq_id in (".join(",",$linePhysSeqIDs).")";
          if ($publicOnly){
            //get only public entities
            $condition .= ' and (2 = ANY ("seq_visibility_ids") or 6 = ANY ("seq_visibility_ids"))';
          } else if ($userOnly) {
            $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && seq_visibility_ids';
          }
          $sequences = new Sequences($condition,null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          if ($sequences && $sequences->getCount()>0) {
            foreach ($sequences as $sequence) {
              $seqID = $sequence->getID();
              if ($seqRetString != '') {
                $seqRetString .= ',';
              }
              $seqEntityIDs = $sequence->getEntityIDs();
              $seqRetString .='"'.$seqID.'":{"label":"'.$sequence->getLabel().'",'.
                                            '"id":"'.$seqID.'",'.
                                            '"value":"'.$sequence->getLabel().'",'.
                                            '"readonly":'.($sequence->isReadonly()?'true':'false').','.
                                            '"editibility":"'.$sequence->getOwnerID().'",'.
                                            '"typeID":"'.$sequence->getTypeID().'",'.
                                            '"children":'.getSeqData($sequence, $refresh).','.
                                            '"entityIDs":['.((isset($seqEntityIDs) && count($seqEntityIDs))?'"'.join('","',$seqEntityIDs).'"':'').']';
              $superscript = $sequence->getSuperScript();
              if ($superscript && count($superscript) > 0) {
                $seqRetString .= ',"sup":"'.$superscript.'"';
              }
              $freetext = $sequence->getScratchProperty('freetext');
              if ($freetext ) {
                $seqRetString .= ',"freetext":"'.$freetext.'"';
              }
              $validationMsg = $sequence->getScratchProperty('validationMsg');
              if ($validationMsg ) {
                $seqRetString .= ',"validationMsg":"'.rawurlencode($validationMsg).'"';
              }
              $AnoIDs = $sequence->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $seqRetString .= ',"annotationIDs":["'.join('","',$AnoIDs).'"]';
                $anoIDs = array_merge($anoIDs,$AnoIDs);
              }
              $AtbIDs = $sequence->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $seqRetString .= ',"attributionIDs":["'.join('","',$AtbIDs).'"]';
                $atbIDs = array_merge($atbIDs,$AtbIDs);
              }
              $seqRetString .= '}';
            }
          }
        }
        if ($txtDivSeqIDs && count($txtDivSeqIDs) > 0) {
          $condition = "seq_id in (".join(",",$txtDivSeqIDs).")";
          if ($publicOnly){
            //get only public entities
            $condition .= ' and (2 = ANY ("seq_visibility_ids") or 6 = ANY ("seq_visibility_ids"))';
          } else if ($userOnly) {
            $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && seq_visibility_ids';
          }
          $sequences = new Sequences($condition,null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          if ($sequences && $sequences->getCount()>0) {
            foreach ($sequences as $sequence) {
              $seqID = $sequence->getID();
              if ($seqRetString != '') {
                $seqRetString .= ',';
              }
              $seqEntityIDs = $sequence->getEntityIDs();
              $seqRetString .='"'.$seqID.'":{"label":"'.$sequence->getLabel().'",'.
                                            '"id":"'.$seqID.'",'.
                                            '"value":"'.$sequence->getLabel().'",'.
                                            '"readonly":'.($sequence->isReadonly()?'true':'false').','.
                                            '"editibility":"'.$sequence->getOwnerID().'",'.
                                            '"typeID":"'.$sequence->getTypeID().'",'.
                                            '"children":'.getSeqData($sequence, $refresh).','.
                                            '"entityIDs":["'.((isset($seqEntityIDs) && count($seqEntityIDs)>0)?join('","',$seqEntityIDs):'').'"]';
              $superscript = $sequence->getSuperScript();
              if ($superscript && count($superscript) > 0) {
                $seqRetString .= ',"sup":"'.$superscript.'"';
              }
              $AnoIDs = $sequence->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $seqRetString .= ',"annotationIDs":["'.join('","',$AnoIDs).'"]';
                $anoIDs = array_merge($anoIDs,$AnoIDs);
              }
              $AtbIDs = $sequence->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $seqRetString .= ',"attributionIDs":["'.join('","',$AtbIDs).'"]';
                $atbIDs = array_merge($atbIDs,$AtbIDs);
              }
              $seqRetString .= '}';
            }
          }
        }
        if ($structuralSeqIDs && count($structuralSeqIDs) > 0) {
          $condition = "seq_id in (".join(",",$structuralSeqIDs).")";
          if ($publicOnly){//get only public entities
            $condition .= ' and (2 = ANY ("seq_visibility_ids") or 6 = ANY ("seq_visibility_ids"))';
          } else if ($userOnly) {
            $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && seq_visibility_ids';
          }
          $sequences = new Sequences($condition,null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          if ($sequences && $sequences->getCount()>0) {
            foreach ($sequences as $sequence) {
              $seqID = $sequence->getID();
              if ($seqRetString != '') {
                $seqRetString .= ',';
              }
              $seqEntityIDs = $sequence->getEntityIDs();
              $seqRetString .='"'.$seqID.'":{"label":"'.$sequence->getLabel().'",'.
                                            '"id":"'.$seqID.'",'.
                                            '"value":"'.$sequence->getLabel().'",'.
                                            '"readonly":'.($sequence->isReadonly()?'true':'false').','.
                                            '"editibility":"'.$sequence->getOwnerID().'",'.
                                            '"typeID":"'.$sequence->getTypeID().'",'.
                                            '"children":'.getChildEntitiesJsonString($sequence->getEntityIDs()).
                                            ((isset($seqEntityIDs) && count(@$seqEntityIDs))?',"entityIDs":["'.join('","',$seqEntityIDs).'"]':'');
              $superscript = $sequence->getSuperScript();
              if ($superscript && count($superscript) > 0) {
                $seqRetString .= ',"sup":"'.$superscript.'"';
              }
              $AnoIDs = $sequence->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $seqRetString .= ',"annotationIDs":["'.join('","',$AnoIDs).'"]';
                $anoIDs = array_merge($anoIDs,$AnoIDs);
              }
              $AtbIDs = $sequence->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $seqRetString .= ',"attributionIDs":["'.join('","',$AtbIDs).'"]';
                $atbIDs = array_merge($atbIDs,$AtbIDs);
              }
              $seqRetString .= '}';
            }
          }
        }
        $retString .= $seqRetString.'}';// close seq entities
      }
    }
    if (count( $atbIDs) > 0) {
      $entityIDs['atb'] = $atbIDs;
    }
    if (count( $anoIDs) > 0) {
      $entityIDs['ano'] = $anoIDs;
    }
    if (count( $entityIDs) > 0) {
//      getRelatedEntities($entityIDs);
      $otherEntitiesJson = getOtherEntitiesJsonString($entityIDs);
      if ($otherEntitiesJson != '{}'){
        $retString .= (@$seqRetString?',':'').substr($otherEntitiesJson,1,-1);//take away {} to include in the inserts
      }
    }
    $retString .= '}}}';
  }
  if (count($warnings) > 0) {
//    $retVal["warnings"] = $warnings;
  }
  if (count($errors) > 0) {
//    $retVal["errors"] = $errors;
  }
  if ($saveEditionToCache) {
    saveCachedEdition($ednID,$retString);
  }
//  $jsonRetVal = json_encode($retVal);
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".$retString.");";
    }
  } else {
    print $retString;
  }

  function getChildEntitiesJsonString($seqEntityIDs) {
    global $anoIDs,$atbIDs,$gra2SclMap,$segID2sclIDs,$processedTokIDs,$errors,$warnings,$publicOnly,$userOnly;
    static $prefixProcessOrder = array('seq','scl','gra','cmp','tok','seg','atb','ano');//important attr before anno to terminate
    $tokLookup = array();
    if (!$seqEntityIDs) return '""';
    //collect entities by type
    $entityIDs = array();
    $entities = array();
    foreach ($seqEntityIDs as $entGID) {
      list($prefix,$entID) = explode(":",$entGID);
      if (!array_key_exists($prefix,$entityIDs)) {
        $entityIDs[$prefix] = array($entID);
      } else {
        array_push($entityIDs[$prefix],$entID);
      }
    }
    while (count(array_keys($entityIDs)) > 0) {
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
      if ($tempIDs && count($tempIDs) > 0 && !array_key_exists($prefix,$entities)) {
        $entities[$prefix] = array();
      }
      $entIDs = array();
      foreach ($tempIDs as $entID){//skip any already processed
        if ($entID && !array_key_exists($entID,$entities[$prefix])) {
          array_push($entIDs,$entID);
        }else if ($prefix == "seg" && array_key_exists($entID,$segID2sclIDs)) {//caution order dependency
          $entities['seg'][$entID]['sclIDs'] = $segID2sclIDs[$entID];
        }
      }
      unset($entityIDs[$prefix]);//we have captured the ids of this entity type remove them so we progress in the recursive call
      if ($entIDs && count($entIDs) > 0) {
        switch ($prefix) {
          case 'seq':
            $condition = "seq_id in (".join(",",$entIDs).")";
            if ($publicOnly){//get only public entities
              $condition .= ' and (2 = ANY ("seq_visibility_ids") or 6 = ANY ("seq_visibility_ids"))';
            } else if ($userOnly) {
              $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && seq_visibility_ids';
            }
            $sequences = new Sequences($condition,null,null,null);
            $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($sequences as $sequence) {
              $seqID = $sequence->getID();
              if ($seqID && !array_key_exists($seqID, $entities['seq'])) {
                $entities['seq'][$seqID] = array( 'label'=> $sequence->getLabel(),
                                       'id' => $seqID,
                                       'value'=> $sequence->getLabel(),
                                       'editibility' => $sequence->getOwnerID(),
                                       'readonly' => $sequence->isReadonly(),
                                       'typeID' => $sequence->getTypeID());
                $superscript = $sequence->getSuperScript();
                if ($superscript && strlen($superscript) > 0) {
                  $entities['seq'][$seqID]['sup'] = $superscript;
                }
                $sEntIDs = $sequence->getEntityIDs();
                if ($sEntIDs && count($sEntIDs) > 0) {
                  $entities['seq'][$seqID]['entityIDs'] = $sEntIDs;
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
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['seq'][$seqID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $sequence->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['seq'][$seqID]['attributionIDs'] = $AtbIDs;
                  if (!array_key_exists('atb',$entityIDs)) {
                    $entityIDs['atb'] = array();
                  }
                  $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
                }
              }
            }
            break;
          case 'cmp':
            $condition = "cmp_id in (".join(",",$entIDs).")";
            if ($publicOnly){//get only public entities
              $condition .= ' and (2 = ANY ("cmp_visibility_ids") or 6 = ANY ("cmp_visibility_ids"))';
            } else if ($userOnly) {
              $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && cmp_visibility_ids';
            }
            $compounds = new Compounds($condition,null,null,null);
            $compounds->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($compounds as $compound) {
              $cmpID = $compound->getID();
              if ($cmpID && !array_key_exists($cmpID, $entities['cmp'])) {
                $entities['cmp'][$cmpID] = array( 'value'=> $compound->getValue(),
                                       'id' => $cmpID,
                                       'modStamp' => $compound->getModificationStamp(),
                                       'transcr' => $compound->getTranscription(),
                                       'readonly' => $compound->isReadonly(),
                                       'case' => $compound->getCase(),
                                       'class' => $compound->getClass(),
                                       'type' => $compound->getType(),
                                       'tokenIDs' => $compound->getTokenIDs(),
                                       'sort' => $compound->getSortCode(),
                                       'sort2' => $compound->getSortCode2());
                $cEntIDs = $compound->getComponentIDs();
                if ($cEntIDs && count($cEntIDs) > 0) {
                  $entities['cmp'][$cmpID]['entityIDs'] = $cEntIDs;
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
                $related = $compound->getRelatedEntitiesByLinkType();
                if ($related && count($related) > 0) {
                  $entities['cmp'][$cmpID]['relatedEntGIDsByType'] = $related;
                }
                $AnoIDs = $compound->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['cmp'][$cmpID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $compound->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['cmp'][$cmpID]['attributionIDs'] = $AtbIDs;
                  if (!array_key_exists('atb',$entityIDs)) {
                    $entityIDs['atb'] = array();
                  }
                  $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
                }
              }
            }
            break;
          case 'tok':
            $condition = "tok_id in (".join(",",$entIDs).")";
            if ($publicOnly){//get only public entities   TODO check if readonly should be a check for visibility on usrgroups for this user
              $condition .= ' and (2 = ANY ("tok_visibility_ids") or 6 = ANY ("tok_visibility_ids"))';
            } else if ($userOnly) {
              $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && tok_visibility_ids';
            }
            $tokens = new Tokens($condition,null,null,null);
            $tokens->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($tokens as $token) {
              $tokID = $token->getID();
              $tokLookup[$tokID] = $token;
              if ($tokID && !array_key_exists($tokID, $entities['tok'])) {
                $entities['tok'][$tokID] = array( 'value'=> $token->getValue(),
                                       'id' => $tokID,
                                       'modStamp' => $token->getModificationStamp(),
                                       'transcr' => $token->getTranscription(),
                                       'affix' => $token->getNominalAffix(),
                                       'readonly' => $token->isReadonly(),
                                       'sort' => $token->getSortCode(),
                                       'sort2' => $token->getSortCode2(),
                                       'syllableClusterIDs' => array());
                array_push($processedTokIDs,$tokID);
                $tGraIDs = $token->getGraphemeIDs();
                if ($tGraIDs && count($tGraIDs) > 0) {
                  $entities['tok'][$tokID]['graphemeIDs'] = $tGraIDs;
                  if (!array_key_exists('gra',$entityIDs)) {
//                    $entityIDs['gra'] = array();
                  }
                  foreach ($tGraIDs as $graID) {
//                    array_push($entityIDs['gra'],$graID);
                  }
                }
                $related = $token->getRelatedEntitiesByLinkType();
                if ($related && count($related) > 0) {
                  $entities['tok'][$tokID]['relatedEntGIDsByType'] = $related;
                }
                $AnoIDs = $token->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['tok'][$tokID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $token->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['tok'][$tokID]['attributionIDs'] = $AtbIDs;
                  if (!array_key_exists('atb',$entityIDs)) {
                    $entityIDs['atb'] = array();
                  }
                  $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
                }
              }
            }
            break;
          case 'gra':
            $condition = "gra_id in (".join(",",$entIDs).")";
            if ($publicOnly){//get only public entities
               $condition .= ' and (2 = ANY ("gra_visibility_ids") or 6 = ANY ("gra_visibility_ids"))';
            } else if ($userOnly) {
              $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && gra_visibility_ids';
            }
            $graphemes = new Graphemes($condition,null,null,null);
            $graphemes->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($graphemes as $grapheme) {
              $graID = $grapheme->getID();
              if ($graID && !array_key_exists($graID, $entities['gra'])) {
                $entities['gra'][$graID] = array( 'value'=> $grapheme->getValue(),
                                       'id' => $graID,
                                       'txtcrit' => $grapheme->getTextCriticalMark(),
                                       'readonly' => $grapheme->isReadonly(),
                                       'alt' => $grapheme->getAlternative(),
                                       'emmend' => $grapheme->getEmmendation(),
                                       'type' => $grapheme->getType(),
                                       'sort' => $grapheme->getSortCode());
                if ($grapheme->getDecomposition()) {
                   $entities['gra'][$graID]['decomp'] = preg_replace("/:/",'',$grapheme->getDecomposition());
                }
                $entities['gra'][$graID]['sclID'] = @$gra2SclMap[$graID];
                if (!$entities['gra'][$graID]['sclID']) {
                  unset($entities['gra'][$graID]['sclID']);
                }
                $AnoIDs = $grapheme->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['gra'][$graID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
              }
            }
            break;
          case 'scl':
            $condition = "scl_id in (".join(",",$entIDs).")";
            if ($publicOnly){//get only public entities
              $condition .= ' and (2 = ANY ("scl_visibility_ids") or 6 = ANY ("scl_visibility_ids"))';
            } else if ($userOnly) {
              $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && scl_visibility_ids';
            }
            $syllables = new SyllableClusters($condition,null,null,null);
            $syllables->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            $sclIDs = array();
            foreach ($syllables as $syllable) {
              $sclID = $syllable->getID();
              if ($sclID && !array_key_exists($sclID, $entities['scl'])) {
                $entities['scl'][$sclID] = array( 'value'=> $syllable->getValue(),
                                                  'txtcrit' => $syllable->getTextCriticalMark(),
                                                  'id' => $sclID,
                                                  'modStamp' => $syllable->getModificationStamp(),
                                                  'readonly' => $syllable->isReadonly(),
                                                  'sort' => $syllable->getSortCode(),
                                                  'sort2' => $syllable->getSortCode2());
                $sGraIDs = $syllable->getGraphemeIDs();
                if ($sGraIDs && count($sGraIDs) > 0) {
                  $entities['scl'][$sclID]['graphemeIDs'] = $sGraIDs;
                  if (!array_key_exists('gra',$entityIDs)) {
                    $entityIDs['gra'] = array();
                  }
                  foreach ($sGraIDs as $sGraID) {
                    if (!in_array($sGraID,$entityIDs['gra'])) {
                        array_push($entityIDs['gra'],$sGraID);
                    }
                    $gra2SclMap[$sGraID] = $sclID;
                  }
                }
                $scratch = $syllable->getScratchPropertiesJsonString();
                if ($scratch){
                  $entities['scl'][$sclID]['scratch'] = $scratch;
                }
                $segID = $syllable->getSegmentID();
                if ($segID && is_numeric($segID)) {
                  $entities['scl'][$sclID]['segID'] = $segID;
/*                  if (!array_key_exists(@$segID,$segID2sclIDs)) {
                    $segID2sclIDs[$segID] = array($sclID);
                  } else if (!in_array($sclID,$segID2sclIDs[$segID])) {
                      array_push($segID2sclIDs[$segID],$sclID);
                  }
                  if (!array_key_exists('seg',$entityIDs)) {
                    $entityIDs['seg'] = array($segID);
                  } else if (!in_array($segID,$entityIDs['seg'])) {
                      array_push($entityIDs['seg'],$segID);
                  }*/
                }
                $AnoIDs = $syllable->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['scl'][$sclID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $syllable->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['scl'][$sclID]['attributionIDs'] = $AtbIDs;
                  if (!array_key_exists('atb',$entityIDs)) {
                    $entityIDs['atb'] = array();
                  }
                  $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
                }
              }
            }
            break;
          case 'seg':
            $condition = "seg_id in (".join(",",$entIDs).")";
            if ($publicOnly){//get only public entities
              $condition .= ' and (2 = ANY ("seg_visibility_ids") or 6 = ANY ("seg_visibility_ids"))';
            } else if ($userOnly) {
              $condition .= ' and ARRAY['.join(",",getUserMembership()).",".getUserID().'] && seg_visibility_ids';
            }
            $segments = new Segments($condition,null,null,null);
            $segments->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            $blnIDs = array();
            foreach ($segments as $segment) {
              $segID = $segment->getID();
              if ($segID && !array_key_exists($segID, $entities['seg'])) {
                $entities['seg'][$segID] = array( 'layer' => $segment->getLayer(),
                                                  'id' => $segID,
                                                  'baselineIDs' => $segment->getBaselineIDs(),
                                                  'readonly' => $segment->isReadonly(),
                                                  'center' => $segment->getCenter(),
                                                  'value' => 'seg'.$segID);
                $boundary = $segment->getImageBoundary();
                if (array_key_exists($segID,$segID2sclIDs)) {
                  $entities['seg'][$segID]['sclIDs']= $segID2sclIDs[$segID];
                }
                if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
                  $boundary = $boundary[0];//WARNING todo handle multipolygon boundary case???
                  $entities['seg'][$segID]['boundary']= array($boundary->getPoints());
                  $entities['seg'][$segID]['urls']= $segment->getURLs();
                }
                $mappedSegIDs = $segment->getMappedSegmentIDs();
                if ($mappedSegIDs && count($mappedSegIDs) > 0) {
                  $entities['seg'][$segID]['mappedSegIDs'] = $mappedSegIDs;
                }
                $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
                if ($segBlnOrder) {
                  $entities['seg'][$entID]['ordinal'] = $segBlnOrder;
                }
                $sBlnIDs = $segment->getBaselineIDs();
                if ($sBlnIDs && count($sBlnIDs) > 0) {
                  $entities['seg'][$segID]['baselineIDs'] = $sBlnIDs;
                }
                $stringpos = $segment->getStringPos();
                if ($stringpos && count($stringpos) > 0) {
                  $entities['seg'][$segID]['stringpos']= $stringpos;
                }
                // ??? do we need to get spans??? mapped segments???
                $AnoIDs = $segment->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['seg'][$segID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $segment->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['seg'][$segID]['attributionIDs'] = $AtbIDs;
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
              if ($atbID && !array_key_exists($atbID, $entities['atb'])) {
                $entities['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
                                        'id' => $atbID,
                                        'value'=> $attribution->getTitle(),
                                        'readonly' => $attribution->isReadonly(),
                                        'grpID' => $attribution->getGroupID(),
                                        'bibID' => $attribution->getBibliographyID(),
                                        'description' => $attribution->getDescription(),
                                        'detail' => $attribution->getDetail(),
                                        'types' => $attribution->getTypes());
                $AnoIDs = $attribution->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['atb'][$atbID]['annotationIDs'] = $AnoIDs;
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
              if ($anoID && !array_key_exists($anoID, $entities['ano'])) {
                $entities['ano'][$anoID] = array( 'text'=> $annotation->getText(),
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
                $entities['ano'][$anoID]['vis'] = $vis;
                $AnoIDs = $annotation->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['ano'][$anoID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $annotation->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['ano'][$anoID]['attributionIDs'] = $AtbIDs;
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
    }// end while
    // post process
    //calculated ordered list of syllableCluster ids for each token
    foreach ($processedTokIDs as $newTokID) {
      $prevSclID = null;
      if (isset($entities['tok'][$newTokID]['graphemeIDs'])) {
        $tokGraIDs = $entities['tok'][$newTokID]['graphemeIDs'];
        if ($gra2SclMap && $tokGraIDs && count($tokGraIDs) > 0 && isset($gra2SclMap[$tokGraIDs[0]])) { // use gra2scl map
          foreach($tokGraIDs as $tokGraID) {
            $graSclID = null;
//            if ($tokGraID && array_key_exists($tokGraID,$gra2SclMap)){
              @$graSclID = array_key_exists($tokGraID,$gra2SclMap)? $gra2SclMap[@$tokGraID]:null;
//            }
            if (!$graSclID) {
              array_push($warnings,"grapheme id  $tokGraID for token id $newTokID is not in syllable map.");
              continue;
            }
            if ($graSclID != $prevSclID) {//skip graphemes of captured syllable
              array_push($entities['tok'][$newTokID]['syllableClusterIDs'],$graSclID);
            }
            $prevSclID = $graSclID;
          }
        } else { // no gra2scl map so query each tokens syllables
          $entities['tok'][$newTokID]['syllableClusterIDs'] = $tokLookup[$newTokID]->getSyllableClusterIDs();
        }
      }
    }
    return json_encode($entities);
  }

  function getOtherEntitiesJsonString($entityIDs) {
    global $anoIDs,$atbIDs,$errors,$warnings,$publicOnly;
    static $prefixProcessOrder = array('atb','ano');//important attr before anno to terminate
    if (!$entityIDs || count($entityIDs) == 0) return '""';
    //collect entities by type
    $entities = array();
    while (count(array_keys($entityIDs)) > 0) {
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
      if ($tempIDs && count($tempIDs) > 0 && !array_key_exists($prefix,$entities)) {
        $entities[$prefix] = array();
      }
      $entIDs = array();
      foreach ($tempIDs as $entID){//skip any already processed
        if ($entID && !array_key_exists($entID,$entities[$prefix])) {
          array_push($entIDs,$entID);
        }
      }
      unset($entityIDs[$prefix]);//we have captured the ids of this entity type remove them so we progress in the recursive call
      if ($entIDs && count($entIDs) > 0) {
        switch ($prefix) {
          case 'atb':
            $attributions = new Attributions("atb_id in (".join(",",$entIDs).")",null,null,null);
            $attributions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($attributions as $attribution) {
              $atbID = $attribution->getID();
              if ($atbID && !array_key_exists($atbID, $entities['atb'])) {
                $entities['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
                                        'id' => $atbID,
                                        'value'=> $attribution->getTitle().($attribution->getDetail()?": ".$attribution->getDetail():''),
                                        'readonly' => $attribution->isReadonly(),
                                        'grpID' => $attribution->getGroupID(),
                                        'bibID' => $attribution->getBibliographyID(),
                                        'description' => $attribution->getDescription(),
                                        'detail' => $attribution->getDetail(),
                                        'types' => $attribution->getTypes());
                $AnoIDs = $attribution->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['atb'][$atbID]['annotationIDs'] = $AnoIDs;
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
              if ($anoID && !array_key_exists($anoID, $entities['ano'])) {
                $entities['ano'][$anoID] = array( 'text'=> $annotation->getText(),
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
                $entities['ano'][$anoID]['vis'] = $vis;
                $AnoIDs = $annotation->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['ano'][$anoID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $annotation->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['ano'][$anoID]['attributionIDs'] = $AtbIDs;
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
    }// end while
    return json_encode($entities);
  }

  function getSeqData($sequence, $refresh = 0) {  // check for cache
    global $publicOnly,$edition,$userOnly;
    if(USECACHE && !$edition->isReadonly()) {
      $cacheKey = "seq".$sequence->getID()."edn".$edition->getID();
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->hasError() || !$jsonCache->getID()) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        $jsonCache->setJsonString(getChildEntitiesJsonString($sequence->getEntityIDs()));
        $jsonCache->setVisibilityIDs($edition->getVisibilityIDs());
        $jsonCache->setOwnerID($edition->getOwnerID());
        $jsonCache->save();
      } else if ($jsonCache->isDirty() || $refresh > 1) {
        $jsonCache->setJsonString(getChildEntitiesJsonString($sequence->getEntityIDs()));
        $jsonCache->clearDirtyBit();
        $jsonCache->save();
      }
      if ($jsonCache->getID() && !$jsonCache->hasError()) {
        return $jsonCache->getJsonString();
      } else {
        return '""';
      }
    } else {
      return getChildEntitiesJsonString($sequence->getEntityIDs());
    }
  }

  function saveCachedEdition($ednID, $jsonString) {  // save json string to cache
    global $publicOnly,$edition,$userOnly;
    if(USECACHE) {
      $cacheKey = "edn".$ednID."cachedEntities";
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->hasError() || !$jsonCache->getID()) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        $jsonCache->setVisibilityIDs(array(6));// warning!!!  6 is reserved system userID for public access
        $jsonCache->setOwnerID($edition->getOwnerID());
      }
      $jsonCache->setJsonString($jsonString);
      $jsonCache->clearDirtyBit();
      $jsonCache->save();
    } else {
      error_log("warning!!! edition $ednID cached info not saved because caching is off");
    }
  }

  function getCachedEdition($ednID) {  // check for cached edition for user or public
    global $publicOnly,$edition,$userOnly;
    if(USECACHE) {
      $cacheKey = "edn".$ednID."cachedEntities";
      $jsonCache = new JsonCache($cacheKey);
      if ($jsonCache->getID() && !$jsonCache->hasError() && !$jsonCache->isDirty()) {
        return $jsonCache->getJsonString();
      } else {
        return "";
      }
    } else {
      error_log("warning!!! edition $ednID cached info not used because caching is off");
      return "";
    }
  }

?>
