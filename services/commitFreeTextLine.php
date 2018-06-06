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
  * commitFreeTextLine
  *
  * given an freetext line sequence ID and edition ID the service uses the parser to create temporary entities
  * for the transliteration in the freetext sequence, provided it's valid, it saves and links all the entities
  * into the current editions molecule. It returns all entity data and update data in a few cases.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Services
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilities
  require_once (dirname(__FILE__) . '/../model/utility/parser.php');//get utilities
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $entities = array();
  $warnings = array();
  $text = null;
  $edition = null;
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    array_push($errors,"invalid json data - decode failed");
  } else {
    $defAttrIDs = getUserDefAttrIDs();
    $defVisIDs = getUserDefVisibilityIDs();
    $defOwnerID = getUserDefEditorID();
    if ( isset($data['ednID'])) {//get edition
      $edition = new Edition($data['ednID']);
      if ($edition->hasError()) {
        array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
      } else if ($edition->isReadonly()) {
        array_push($errors,"edition readonly");
      } else {
        $ednOwnerID = $edition->getOwnerID();
        $text = $edition->getText(true);
        $ckn = $text->getCKN();
        if (!$ckn) {
          $ckn = "txt".$text->getID();
        }
        //get default attribution
        if (!$defAttrIDs || count($defAttrIDs) == 0) {
          $attrIDs = $edition->getAttributionIDs();
          if ($attrIDs && count($attrIDs) > 0 ) {
            $defAttrIDs = array($attrIDs[0]);//only select the first one assume it is primary
          }
        }
        //get default visibility
        if (!$defVisIDs || count($defVisIDs) == 0) {
          $visIDs = $edition->getVisibilityIDs();
          if ($visIDs && count($visIDs) > 0 ) {
            $defVisIDs = array($visIDs[0]);//only select the first one assume it is primary todo: check spec
          }
        }
        //find edition's Physical and Text sequences
        $seqPhys = null;
        $seqText = null;
        $oldPhysSeqID = null;
        $oldTextSeqID = null;
        $edSeqs = $edition->getSequences(true);
        foreach ($edSeqs as $edSequence) {
          $seqType = $edSequence->getType();
          if (!$seqPhys && $seqType == "TextPhysical"){//term dependency
            $seqPhys = $edSequence;
          }
          if (!$seqText && $seqType == "Text"){//term dependency
            $seqText = $edSequence;
          }
        }
      }
    } else {
      array_push($errors,"inaccessable edition");
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  $freetextSeqID = null;
  $freetextSeqGID = null;
  $prevGraID = null;
  $nextGraID = null;
  $nextTxtDivSeq = null;
  $prevTxtDivSeq = null;
  $prevCtxGIDs = null;
  $nextCtxGIDs = null;
  $freetextLine = null;
  if (count($errors) == 0 && $seqPhys && count($seqPhys->getEntityIDs())){ // need minimal 'TextPhysical' with entities
    if ( isset($data['seqID'])) {//get reference freetext Line sequence ID
      $freetextLine = new Sequence($data['seqID']);
      if ($freetextLine->hasError()) {
        array_push($errors,"creating freetext sequence - ".join(",",$freetextLine->getErrors()));
      } else if ($freetextLine->isReadonly()) {//should never get here
        array_push($errors,"freetext sequence readonly");
      } else {
        $freetextSeqID = $freetextLine->getID();
        $freetextSeqGID = $freetextLine->getGlobalID();
        $physLineGIDs = $seqPhys->getEntityIDs();
        $freetextIndex = array_search($freetextSeqGID,$physLineGIDs);
        if ($freetextIndex !== false) {
          $freetextIsFirst = ($freetextIndex == 0);
          if (!$freetextIsFirst) {
            // find nearest previous neighbor line physical starting from freetext line's position skipping other freetext
            $prevLineGIDs = array_splice($physLineGIDs,0,$freetextIndex);
            while ($neighborGID = array_pop($prevLineGIDs)) {// search backwards to the first line
              $physLine = new Sequence(substr($neighborGID,4));
              if ($physLine->getType() == 'LinePhysical'){//term dependency
                $lineSclGIDs = $physLine->getEntityIDs();
                if (count($lineSclGIDs)) {
                  $prevSclGID = array_pop($lineSclGIDs);
                  $prevSyllable = new SyllableCluster(substr($prevSclGID,4));
                  if ($prevSyllable->hasError()) {
                    array_push($warnings,"commit freetext - unable to load previous syllable $prevSclGID");
                  } else {
                    $graIDs = $prevSyllable->getGraphemeIDs();
                    $prevGraID = array_pop($graIDs);
                    // calculate containment heirarchy
                    $ctxGIDs = array();
                    $ctxTokens = new Tokens("$prevGraID = ANY(tok_grapheme_ids)",null,null,null);
                    if ($ctxTokens->getCount()) {
                      foreach ($ctxTokens as $token) {
                        $temp=$token->getGraphemeIDs();
                        if (array_pop($temp) == $prevGraID) {
                          array_push($ctxGIDs,$token->getGlobalID());
                        }
                      }
                      if (count($ctxGIDs)>1) {
                        array_push($warnings,"commit freetext - found multiple contianing tokens for previous grapheme : ".join(',',$ctxGIDs));
                      }
                    } else {
                      array_push($warnings,"commit freetext - no token found for previous grapheme gra:$prevGraID");
                    }
                    $newGIDs = $ctxGIDs; //start with the tokGIDs
                    while (count($newGIDs)) {
                      $entGIDs = $newGIDs;
                      $newGIDs = array();
                      foreach ($entGIDs as $entGID) {
                        $ctxCompounds = new Compounds("'$entGID' = ANY(cmp_component_ids)",null,null,null);
                        if ($ctxCompounds->getCount()) {
                          foreach ($ctxCompounds as $compound) {
                            $cmpGID = $compound->getGlobalID();
                            if (!in_array($cmpGID,$ctxGIDs)){
                              array_push($ctxGIDs,$cmpGID);
                              array_push($newGIDs,$cmpGID);
                            }
                          }
                        }
                      }
                    }
                    if (count($ctxGIDs)) {
                      $prevCtxGIDs = $ctxGIDs;
                    }
                    break;
                  }
                }
                array_push($warnings,"commit freetext - found empty previous physical line sequence $neighborGID");
              }
            }
          }
          //remove freetext GID
          array_splice($physLineGIDs,0,1);
          //forward to the last line.
          $freetextIsLast = (count($physLineGIDs) == 0);
          if (!$freetextIsLast) {
            // find nearest next neighbor line physical starting from freetext line's position skipping other freetext
            while ($neighborGID = array_splice($physLineGIDs,0,1)) {// search forwards to the last line
              $physLine = new Sequence(substr($neighborGID[0],4));
              if ($physLine->getType() == 'LinePhysical'){//term dependency
                $lineSclGIDs = $physLine->getEntityIDs();
                if (count($lineSclGIDs)) {
                  $nextSclGID = $lineSclGIDs[0];
                  $nextSyllable = new SyllableCluster(substr($nextSclGID,4));
                  if ($nextSyllable->hasError()) {
                    array_push($warnings,"commit freetext - unable to load previous syllable $nextSclGID");
                  } else {
                    $graIDs = $nextSyllable->getGraphemeIDs();
                    $nextGraID = $graIDs[0];
                    // calculate containment heirarchy
                    $ctxGIDs = array();
                    $ctxTokens = new Tokens("$nextGraID = ANY(tok_grapheme_ids)",null,null,null);
                    if ($ctxTokens->getCount()) {
                      foreach ($ctxTokens as $token) {
                        $graIDs = $token->getGraphemeIDs();
                        if ($graIDs[0] == $nextGraID) {
                          array_push($ctxGIDs,$token->getGlobalID());
                        }
                      }
                      if (count($ctxGIDs)>1) {
                        array_push($warnings,"commit freetext - found multiple contianing tokens for previous grapheme : ".join(',',$ctxGIDs));
                      }
                    } else {
                      array_push($warnings,"commit freetext - no token found for previous grapheme gra:$nextGraID");
                    }
                    $newGIDs = $ctxGIDs; //start with the tokGIDs
                    while (count($newGIDs)) {
                      $entGIDs = $newGIDs;
                      $newGIDs = array();
                      foreach ($entGIDs as $entGID) {
                        $ctxCompounds = new Compounds("'$entGID' = ANY(cmp_component_ids)",null,null,null);
                        if ($ctxCompounds->getCount()) {
                          foreach ($ctxCompounds as $compound) {
                            $cmpGID = $compound->getGlobalID();
                            if (!in_array($cmpGID,$ctxGIDs)){
                              array_push($ctxGIDs,$cmpGID);
                              array_push($newGIDs,$cmpGID);
                            }
                          }
                        }
                      }
                    }
                    if (count($ctxGIDs)) {
                      $nextCtxGIDs = $ctxGIDs;
                    }
                    break;
                  }
                }
                array_push($warnings,"commit freetext - found empty next physical line sequence ".$neighborGID[0]);
              }
            }
          }
          if ($seqText && ($nextCtxGIDs || $prevCtxGIDs)) {
            // walk the editions 'TextDivision' sequences until the previous and
            // next tokens/compounds are found in a 'TextDivision'.
            $txtDivSequences = $seqText->getEntities(true);
            if ($txtDivSequences->getCount()){
              if ($prevCtxGIDs) {
                foreach ($txtDivSequences as $sequence) {
                  $seqEntityIDs = $sequence->getEntityIDs();
                  if (!$seqEntityIDs || count($seqEntityIDs) == 0) {
                    continue;
                  }
                  $interGIDs = array_intersect($prevCtxGIDs,$seqEntityIDs);
                  if (count($interGIDs)) {
                    $prevTxtDivSeq = $sequence;
                    foreach($interGIDs as $interGID) {
                      $prevTokCmpGID = $interGID;
                      break;
                    }
                    break;
                  }
                }
              }
              if ($nextCtxGIDs) {
                foreach ($txtDivSequences as $sequence) {
                  $seqEntityIDs = $sequence->getEntityIDs();
                  if (!$seqEntityIDs || count($seqEntityIDs) == 0) {
                    continue;
                  }
                  $interGIDs = array_intersect($nextCtxGIDs,$sequence->getEntityIDs());
                  if (count($interGIDs)) {
                    $nextTxtDivSeq = $sequence;
                    foreach($interGIDs as $interGID) {
                      $nextTokCmpGID = $interGID;
                      break;
                    }
                    break;
                  }
                }
              }
            } else {
              array_push($warnings,"commit freetext - found empty text sequence ".$seqText->getGlobalID());
            }
          }
        } else {
          array_push($errors,"freetext sequence not found in physical lines from sequence ".$seqPhys->getGlobalID());
        }
      }
    }
  } else {
    if (!$seqPhys) {
      array_push($errors,"freetext commit - text physical sequence not found for edition ".$edition->getGlobalID());
    } else {
      array_push($errors,"freetext commit - text physical sequence empty ".$seqPhys->getGlobalID());
    }
  }
  $freetext = $freetextLine->getScratchProperty('freetext');
  $parserConfigs = array(
    createParserConfig($defOwnerID,$defVisIDs,$defAttrIDs,$ckn,
                        null,null,null,$freetextLine->getLabel(),$freetextIndex+1,null,$freetext)
  );
  $parser = new Parser($parserConfigs);
  $parser->setBreakOnError(true);
  $parser->parse();
  $errStr1 = null;
  if (count($parser->getErrors())) {
    foreach ($parser->getErrors() as $error) {
      if (preg_match('/(?:at|for)?\s?character (\d+)/', $error, $matches)) {
        $errIndex = $matches[1];
        $errStr1 = "&nbsp;&nbsp;".mb_substr($freetext,0,$errIndex).
              "<span class=\"errhilite\">".mb_substr($freetext,$errIndex,1)." </span>".
              "<span class=\"errmsg\">  error: ".
              mb_substr($error,0,mb_strpos($error,$matches[0])) ."</span>";
        $errStr1 = preg_replace("/%20/"," ",$errStr1);
      }
      array_push($errors,"Parsing errors: $errStr1");
    }
  } else {
    $ignoreTypeIDs = null; //this will ignore none or use default for addInLine
    $ignoreTypeIDs = array(
      Entity::getIDofTermParentLabel('textphysical-sequencetype'),//warning!!! term dependency
      Entity::getIDofTermParentLabel('linephysical-textphysical'),//warning!!! term dependency
      Entity::getIDofTermParentLabel('analysis-sequencetype'),//warning!!! term dependency
      Entity::getIDofTermParentLabel('chapter-analysis'),//warning!!! term dependency
      Entity::getIDofTermParentLabel('rootref-textreference'),//warning!!! term dependency
      Entity::getIDofTermParentLabel('textreference-sequencetype')//warning!!! term dependency
    );
    if ($seqText) {
        array_push($ignoreTypeIDs, Entity::getIDofTermParentLabel('text-sequencetype'));//warning!!! term dependency
    }
    $parser->saveParseResults(null,true,$ignoreTypeIDs);
    if (count($parser->getErrors())) {
      array_push($errors,"Errors saving parser results:".join(" | ",$parser->getErrors()));
    } else {
      //add new return data for graphemes, syllables, segments, baseline, tokens, compounds
      $graphemes = $parser->getGraphemes();
      if (count($graphemes)) {
        foreach ($graphemes as $grapheme) {
          addNewEntityReturnData('gra',$grapheme);
        }
      }
      $syllables = $parser->getSyllableClusters();
      if (count($syllables)) {
        foreach ($syllables as $syllable) {
          addNewEntityReturnData('scl',$syllable);
          $sclID = $syllable->getID();
          foreach ($syllable->getGraphemeIDs() as $graID) {
            addUpdateEntityReturnData('gra',$graID,'sclID',$sclID);
          }
        }
      }
      $segments = $parser->getSegments();
      if (count($segments)) {
        foreach ($segments as $segment) {
          addNewEntityReturnData('seg',$segment);
        }
      }
      $baselines = $parser->getBaselines();
      if (count($baselines)) {
        foreach ($baselines as $baseline) {
          addNewEntityReturnData('bln',$baseline);
        }
      }
      $tokens = $parser->getTokens();
      if (count($tokens)) {
        foreach ($tokens as $token) {
          addNewEntityReturnData('tok',$token);
        }
      }
      $compounds = $parser->getCompounds();
      if (count($compounds)) {
        foreach ($compounds as $compound) {
          addNewEntityReturnData('cmp',$compound);
        }
      }
      //check sequence adding new
      $sequences = $parser->getSequences();
      $textDivision = null;
      $newTextDivision = null;
      $newTextSeq = null;
      if (count($sequences)) {
        foreach ($sequences as $sequence) {
          $seqType = $sequence->getType();
          $seqID = $sequence->getID();
          if ($seqType == "LinePhysical") {//term dependency
            //add linephysical GIDs to freetextLine
            $lpEntNonces = $sequence->getEntityIDs();
            $lpEntGIDs = array();
            foreach ($lpEntNonces as $nonce) {
              $gid = $parser->getGIDFromNonce($nonce);
              if ($gid) {
                array_push($lpEntGIDs,$gid);
              }
            }
            $freetextLine->setEntityIDs($lpEntGIDs);
            //change freetextLine type to linePhysical
            $freetextLine->setTypeID($sequence->getTypeID());
            $freetextLine->save();
            //update freetextSeqID entityIDs data
            addUpdateEntityReturnData('seq',$freetextLine->getID(),'entityIDs',$freetextLine->getEntityIDs());
            addUpdateEntityReturnData('seq',$freetextLine->getID(),'typeID',$freetextLine->getTypeID());
            if ($seqID !== null) {
              $sequence->markForDelete();
            }
          } else if ($seqType == "TextDivision") {//term dependency
            if (!$seqID) {
              array_push($warnings,"Warning saving freetext has unsaved textdivision sequence");
              $textDivision = $sequence;
            } else {
              $newTextDivision = $sequence;
              addNewEntityReturnData('seq',$newTextDivision);
              $retVal['newTextDivSeqID'] = $newTextDivision->getID();
            }
          } else if ($seqType == "Text" && $seqID) {//term dependency
            $newTextSeq = $sequence;
          }
        }
      }
      if (!$seqText && $newTextSeq) {//if no seqText add new text seq to edition and add update info for edition
        $ednsequenceIDs = $edition->getSequenceIDs();
        array_push($ednsequenceIDs,$newTextSeq->getID());
        $edition->setSequenceIDs($ednsequenceIDs);
        $edition->save();
        addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
        addNewEntityReturnData('seq',$newTextSeq);
        $retVal['refreshAll'] = true; //early days this should only happen when first starting if at all
      } else {//update existing seqText
        //check if split textDiv is needed
        if ($prevTxtDivSeq && $nextTxtDivSeq && $prevTxtDivSeq->getID() == $nextTxtDivSeq->getID()) {
          $newTxtDivSeqIDs = array();
          // split textDiv after prevTokComGID
          $replaceTxtDivSeqID = $prevTxtDivSeq->getID();
          $replaceTxtDivSeqGID = $prevTxtDivSeq->getGlobalID();
          if ($prevTxtDivSeq->isReadonly()) {
            $prevTxtDivSeq = $prevTxtDivSeq->cloneEntity($defAttrIDs,$defVisIDs,$defOwnerID);
          }
          $prevTokCmpGIDs = $prevTxtDivSeq->getEntityIDs();
          $splitIndex = array_search($prevTokCmpGID,$prevTokCmpGIDs);
          $nextTokCmpGIDs = array_splice($prevTokCmpGIDs,$splitIndex);
          //create post txtDiv seq for split
          $nextTxtDivSeq = $prevTxtDivSeq->cloneEntity($defAttrIDs,$defVisIDs,$defOwnerID);
          //adjust pre textDiv seq entityids
          $prevTxtDivSeq->setEntityIDs($prevTokCmpGIDs);
          $prevTxtDivSeq->save();
          array_push($newTxtDivSeqIDs,$prevTxtDivSeq->getGlobalID());
          array_push($newTxtDivSeqIDs,$newTextDivision->getGlobalID());
          //if reused exist update of entityIDs, in cloned case add new so just add new for both.
          addNewEntityReturnData('seq',$prevTxtDivSeq);
          //adjust next textDiv seq entityIDs
          $nextTxtDivSeq->setEntityIDs($nextTokCmpGIDs);
          $nextTxtDivSeq->save();
          addNewEntityReturnData('seq',$nextTxtDivSeq);
          array_push($newTxtDivSeqIDs,$nextTxtDivSeq->getGlobalID());
          //update text seq entityIDs
          $seqTextEntIDs = $seqText->getEntityIDs();
          $replaceIndex = array_search($replaceTxtDivSeqGID,$seqTextEntIDs);
          array_splice($seqTextEntIDs,$replaceIndex,1,$newTxtDivSeqIDs);
          $seqText->setEntityIDs($seqTextEntIDs);
          $seqText->save();
          //add text seq update info
          addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
          //add textDiv label replacement info to return data
          $retVal['replaceLbls'] = array('seq'.$nextTxtDivSeq->getID()=>
                                          array( 'oldSeqTag'=>'seq'.$replaceTxtDivSeqID,
                                                 'tokCmpGIDs'=>$nextTokCmpGIDs));
          if ( $replaceTxtDivSeqID != $prevTxtDivSeq->getID()) {//clone so update prev textDiv seq label
            $retVal['replaceLbls']['seq'.$prevTxtDivSeq->getID()] =
                array( 'oldSeqTag'=>'seq'.$replaceTxtDivSeqID,
                       'tokCmpGIDs'=>$prevTokCmpGIDs);
          }
          //add new textDiv return data
          addNewEntityReturnData('seq',$newTextDivision);
        } else { //just insert the new textDiv seqID in the text seq
          //update text seq entityIDs
          $seqTextEntIDs = $seqText->getEntityIDs();
          //find insert index using $prevTxtDivSeq  GID
          if ($prevTxtDivSeq) {
            $insertTxtDivSeqID = $prevTxtDivSeq->getGlobalID();
            $insertIndex = array_search($insertTxtDivSeqID,$seqTextEntIDs);
            $insertIndex++;
          } else { // no prev so insert at the beginning
            $insertIndex = 0;
          }
          array_splice($seqTextEntIDs,$insertIndex,0,$newTextDivision->getGlobalID());
          //if seqText is readonly then clone and update edition seq
          if ($seqText->isReadOnly()) {
            //$seqText = $seqText->
          }
          //insert GID into seqText entityIDs
          $seqText->setEntityIDs($seqTextEntIDs);
          $seqText->save();
          //add text seq update info
          addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
        }
      }
    }
  }

  if (count($errors) == 0 && $edition) {
    //touch edition for synch code
    $edition->storeScratchProperty("lastModified",$edition->getModified());
    $edition->save();
    invalidateCachedEditionEntities($edition->getID());
    invalidateCachedEditionViewerHtml($edition->getID());
    invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
  }

  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  if ($errStr1) {
    $retVal["errString"] = $errStr1;
  }
  if (count($entities)) {
    $retVal["entities"] = $entities;
  }
  if ($healthLogging && $edition) {
    $retVal["editionHealth"] = checkEditionHealth($edition->getID(),false);
  }
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }
/*

  $graCnt = count($parser->getGraphemes());
  echo "<h2> Entities </h2>";
  echo '<table style="width:100%">';
  echo '<tr>';
  echo '<th>Graphemes IDs</th>';
  $graLookup = array();
  foreach ($parser->getGraphemes() as $grapheme) {
    $graID = mb_substr(mb_strstr($grapheme->getScratchProperty("nonce"),"#",true),5);
    $graLookup[$graID] = $grapheme;
    echo "<td style=\"border: 1px solid\">$graID</td>";
  }
  echo '</tr>';
  echo '<tr>';
  echo '<th>Graphemes</th>';
  foreach ($parser->getGraphemes() as $grapheme) {
    $decomp = $grapheme->getDecomposition();
    if (mb_strlen($decomp)){
      $value = preg_replace("/\:/","",$decomp);
    } else {
      $value = $grapheme->getGrapheme();
    }
    echo "<td style=\"border: 1px solid; text-align: center\">".$value."</td>";
  }
  echo '</tr>';
  echo '<tr>';
  echo '<th>SyllableClusters</th>';
  foreach ($parser->getSyllableClusters() as $syllable) {
    $sclGraIDs = $syllable->getGraphemeIDs();
    $sclGraCnt = count($sclGraIDs);
    $value = '';
    foreach ($sclGraIDs as $graID) {
      $value .= $graLookup[substr($graID,1)]->getGrapheme();
    }
//    $value = $syllable->getSegmentID();
    echo "<td style=\"border: 1px solid; text-align: center\" colspan=\"$sclGraCnt\">$value</td>";
  }
  echo '</tr>';
  echo '<tr>';
  echo '<th>Tokens</th>';
  foreach ($parser->getTokens() as $token) {
    $tokGraIDs = $token->getGraphemeIDs();
    $tokGraCnt = count($tokGraIDs);
    $decomp = $graLookup[substr($tokGraIDs[0],1)]->getDecomposition();
    if (mb_strlen($decomp)){
      $tokGraCnt--;
    }
    $value = $token->getValue();
    echo "<td style=\"border: 1px solid; text-align: center\" colspan=\"$tokGraCnt\">$value</td>";
  }
  echo '</tr>';
  echo '<th>Token IDs</th>';
  foreach ($parser->getTokens() as $token) {
    $tokID = mb_substr(mb_strstr($token->getScratchProperty("nonce"),"#",true),5);
    $tokGraIDs = $token->getGraphemeIDs();
    $tokGraCnt = count($tokGraIDs);
    $decomp = $graLookup[substr($tokGraIDs[0],1)]->getDecomposition();
    if (mb_strlen($decomp)){
      $tokGraCnt--;
    }
    echo "<td style=\"border: 1px solid; text-align: center\" colspan=\"$tokGraCnt\">$tokID</td>";
  }
  echo '</tr>';
  echo '</table>';
  echo "<h2> Compounds </h2>";
  foreach ($parser->getCompounds() as $compound) {
    echo (($ckn = $compound->getScratchProperty("cknLine")) ? "$ckn ":"")."\"".$compound->getCompound()."\"".
          " - ".$compound->getTranscription()." SC -  ".$compound->getSortCode()."     ".
         "componentIDs - ".$compound->getComponentIDs(true)."     ".mb_strstr($compound->getScratchProperty("nonce"),"#",true)."<br>";
  }
}
</body>
</html>
*/
?>
