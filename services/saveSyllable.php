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
* saveSyllable
*
* saves the syllable passed making new or updating existing graphemes as needed, propagates up the containment hierarchy as needed.
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
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid aksara
require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/JsonCache.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$ednOwnerID = null;
$tokenSplitRequired = false;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  $ednID = null;
  if ( isset($data['ednID'])) {//get edition
    $edition = new Edition($data['ednID']);
    if ($edition->hasError()) {
      array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
    } else if ($edition->isReadonly()) {
      array_push($errors,"edition readonly");
    } else {
      $ednID = $edition->getID();
      $ednOwnerID = $edition->getOwnerID();
      //get default attribution from edition if needed
      if (!$defAttrIDs || count($defAttrIDs) == 0) {
        $attrIDs = $edition->getAttributionIDs();
        if ($attrIDs && count($attrIDs) > 0 ) {
          $defAttrIDs = array($attrIDs[0]);
        }
      }
      //get default visibility from edition if needed
      if (!$defVisIDs || count($defVisIDs) == 0) {
        $visIDs = $edition->getVisibilityIDs();
        if ($visIDs && count($visIDs) > 0 ) {
          $defVisIDs = array($visIDs[0]);
        }
      }
      //find edition's Physical and Text sequences
      $seqPhys = null;
      $seqText = null;
      $seqAnalysis = null;
      $oldPhysSeqID = null;
      $oldTextSeqID = null;
      list($seqText,$seqPhys,$seqAnalysis) = getOrderedEditionSequences($edition);
    }
  } else {
    array_push($errors,"unaccessable edition");
  }
  $refSclGID = null;
  if ( isset($data['refSclGID'])) {//get reference SyllableClusterGID
    $refSclGID = $data['refSclGID'];
  }
  $physLineSeqID = null;
  if ( isset($data['seqID'])) {//get reference physical Line sequence ID
    $physLineSeqID = $data['seqID'];
  } else if ($refSclGID){ //search for physical Line using refSclID
    foreach($seqPhys->getEntities(true) as $edPhysLineSeq) {
      if (count($edPhysLineSeq->getEntityIDs()) && in_array($refSclGID,$edPhysLineSeq->getEntityIDs())) {
        $physLineSeqID = $edPhysLineSeq->getID();
        break;
      }
    }
  }
  if ($physLineSeqID) {
    $physLineSeq = new Sequence($physLineSeqID);
    if ($physLineSeq->hasError()) {
      array_push($errors,"creating sequence id = $physLineSeqID - ".join(",",$physLineSeq->getErrors()));
    }
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
}
$syllable = null;
$token = null;
$compounds = null;
$token2 = null;
$compounds2 = null;
$textDivSeq = null;
$textDivSeq2 = null;
$preSplitString = null;
$isSplitSyllable = false;
// retrieve all context entities needed for save
if (count($errors) == 0) {
  if (isset($data['context'])) {
    if (isset($data['strPre'])) {// split syllable case
      $isSplitSyllable = true;
      $preSplitString = $data['strPre'];
    }
    if (is_array($data['context'])) {
      $contexts = $data['context'];
    }else{
      $contexts = array($data['context']);
    }
    $context = explode(",",$contexts[0]);
    while ($gid = array_pop($context)) {
      $id = substr($gid,3);
      switch (substr($gid,0,3)) {
        case "scl":
          if (!$syllable) {
            $syllable = new SyllableCluster($id);//the first is only since this is saveSyllable
          }
          break;
        case "tok":
          $token = new Token($id);
          if ($preSplitString) {//find the grapheme ids for the prestring
            //get syllable value
            //match in reverse the grapheme characters of the syllable to the end graphemes of the token
            //save as original graphemeIDs of preSplit syllable
            //save remaining graphemeIDs as post split string
          }
          break;
        case "cmp":
          $compound = new Compound($id);
          if (!$compounds) {
            $compounds = array($compound);
          } else {
            array_push($compounds, $compound);
          }
          break;
        case "seq":
          $textDivSeq = new Sequence($id);
          break;
      }
    }
    if ($isSplitSyllable && count($contexts) == 2) {
      $context = explode(",",$contexts[1]);
      while ($gid = array_pop($context)) {
        $id = substr($gid,3);
        switch (substr($gid,0,3)) {
          case "scl":
            if (!$syllable || $syllable->getID() != $id) { //split syllable should have same sclID
              array_push($errors,"error non matching split syllable ids");
            }
            break;
          case "tok":
            $token2 = new Token($id);
            break;
          case "cmp":
            if ( $compounds && $compounds[0]->getID() != $id) { //todo check if multiple cmpIDs possible and compare the same
              $compound = new Compound($id);
              if (!$compounds2) {
                $compounds2 = array($compound);
              } else {
                array_push($compounds2, $compound);
              }
            }
            break;
          case "seq":
            if ( $textDivSeq->getID() != $id) {
              $textDivSeq2 = new Sequence($id);
            }
            break;
        }
      }
    } else if ($isSplitSyllable) {
      array_push($errors,"missing context for split syllable");
    }
  } else {
    array_push($errors,"missing context");
  }
}
if (count($errors) == 0) {
  if (!$syllable) {
    array_push($errors,"missing syllable");
  } else if ( $syllable->hasError()) {
    array_merge($errors,$syllable->getErrors());
  } else if (!$token) {
    array_push($errors,"missing token");
  } else if ( $token->hasError()) {
    array_merge($errors,$token->getErrors());
  } else {
    $strSylDB = $syllable->getValue();
    if ( isset($data['origSyl'])) {
      $strSylOrig = $data['origSyl'];
    } else {
      $strSylOrig = $strSylDB;
    }
    $strSylNew = $data['newSyl'];
    if ($strSylOrig != str_replace("ʔ","",$strSylDB)) {//this is problematic for the token as it can be out of synch.
      array_push($warnings,"syllable out of synch - '$strSylDB' (db) vs '$strSylOrig' (orig)");
    }
    //calculate syllable save data
    $oldSylGraIDs = $syllable->getGraphemeIDs();
    $dbGraphemes = $syllable->getGraphemes(true);
    $oldSylGra = array();
    foreach ($dbGraphemes as $dbGrapheme) {
      array_push($oldSylGra,$dbGrapheme);// create 'in order' array of graphemes for syllable
    }
    //for each grapheme in $strSylNew compare against $strSylDB
    $parsedGraData = array();
    $newSylGra = array();
    $reuseGraIndex = array();
    $newSplitIndex = null;
    $errors = array();
    $entities = array();
    $cnt = mb_strlen($strSylNew);
    $sylState = "S"; //start of syllable

    //parse new syllable string into graphemes
    for ($i=0; $i< $cnt;) {
      $char = mb_substr($strSylNew,$i,1);
      $inc = 1;
      $testChar = $char;
//      $char = mb_strtolower($char);
      $graphemeIsUpper = false;//uppercase Grapheme make it lower case to allow all caps for logograms
//      $graphemeIsUpper = ($testChar != $char);//uppercase Grapheme
      // convert multi-byte to grapheme - using greedy lookup
      if (array_key_exists($char,$graphemeCharacterMap)){
        //check next character included
        $char2 = mb_substr($strSylNew,$i+1,1);
        if (array_key_exists($char2,$graphemeCharacterMap[$char])){ // another char for grapheme
          $inc++;
          $char3 = mb_substr($strSylNew,$i+2,1);
          if (array_key_exists($char3,$graphemeCharacterMap[$char][$char2])){ // another char for grapheme
            $inc++;
            $char4 = mb_substr($strSylNew,$i+3,1);
            if (array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3])){ // another char for grapheme
              $inc++;
              $char5 = mb_substr($strSylNew,$i+4,1);
              if (array_key_exists($char5,$graphemeCharacterMap[$char][$char2][$char3][$char4])){ // another char for grapheme
                $inc++;
                $char6 = mb_substr($strSylNew,$i+5,1);
                if (array_key_exists($char6,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])){ // another char for grapheme
                  $inc++;
                  $char7 = mb_substr($strSylNew,$i+6,1);
                  if (array_key_exists($char7,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])){ // another char for grapheme
                    $inc++;
                    $char8 = mb_substr($strSylNew,$i+7,1);
                    if (array_key_exists($char8,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])){ // another char for grapheme
                      $inc++;
                      $char9 = mb_substr($strSylNew,$i+8,1);
                      if (array_key_exists($char9,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8])){ // another char for grapheme
                        $inc++;
                        $char10 = mb_substr($strSylNew,$i+9,1);
                        if (array_key_exists($char10,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9])){ // another char for grapheme
                          $inc++;
                          if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9][$char10])){ // invalid sequence
                            array_push($errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7$char8$char9$char10, has no sort code");
                            break;
                          } else {//found valid grapheme, save it
                            $str = $char.$char2.$char3.$char4.$char5.$char6.$char7.$char8.$char9.$char10;
                            $ustr = $testChar.$char2.$char3.$char4.$char5.$char6.$char7.$char8.$char9.$char10;
                            $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9][$char10]['typ'];
                            if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9][$char10])) {
                              $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9][$char10]['ssrt'];
                            } else {
                              $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9][$char10]['srt'];
                            }
                          }
                        } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9])){ // invalid sequence
                          array_push($errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7$char8$char9, has no sort code");
                          break;
                        } else {//found valid grapheme, save it
                          $str = $char.$char2.$char3.$char4.$char5.$char6.$char7.$char8.$char9;
                          $ustr = $testChar.$char2.$char3.$char4.$char5.$char6.$char7.$char8.$char9;
                          $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9]['typ'];
                          if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9])) {
                            $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9]['ssrt'];
                          } else {
                            $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8][$char9]['srt'];
                          }
                        }
                      } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8])){ // invalid sequence
                        array_push($errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7$char8, has no sort code");
                        break;
                      } else {//found valid grapheme, save it
                        $str = $char.$char2.$char3.$char4.$char5.$char6.$char7.$char8;
                        $ustr = $testChar.$char2.$char3.$char4.$char5.$char6.$char7.$char8;
                        $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8]['typ'];
                        if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8])) {
                          $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8]['ssrt'];
                        } else {
                          $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8]['srt'];
                        }
                      }
                    } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])){ // invalid sequence
                      array_push($errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7, has no sort code");
                      break;
                    } else {//found valid grapheme, save it
                      $str = $char.$char2.$char3.$char4.$char5.$char6.$char7;
                      $ustr = $testChar.$char2.$char3.$char4.$char5.$char6.$char7;
                      $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7]['typ'];
                      if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])) {
                        $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7]['ssrt'];
                      } else {
                        $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7]['srt'];
                      }
                    }
                  } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])){ // invalid sequence
                    array_push($errors,"incomplete grapheme $char$char2$char3$char4$char5$char6, has no sort code");
                    break;
                  } else {//found valid grapheme, save it
                    $str = $char.$char2.$char3.$char4.$char5.$char6;
                    $ustr = $testChar.$char2.$char3.$char4.$char5.$char6;
                    $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6]['typ'];
                    if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])) {
                      $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6]['ssrt'];
                    } else {
                      $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6]['srt'];
                    }
                  }
                } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])){ // invalid sequence
                  array_push($errors,"incomplete grapheme $char$char2$char3$char4$char5, has no sort code");
                  break;
                } else {//found valid grapheme, save it
                  $str = $char.$char2.$char3.$char4.$char5;
                  $ustr = $testChar.$char2.$char3.$char4.$char5;
                  $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5]['typ'];
                  if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])) {
                    $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5]['ssrt'];
                  } else {
                    $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5]['srt'];
                  }
                }
              } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4])){ // invalid sequence
                array_push($errors,"incomplete grapheme $char$char2$char3$char4, has no sort code");
                break;
              } else {//found valid grapheme, save it
                $str = $char.$char2.$char3.$char4;
                $ustr = $testChar.$char2.$char3.$char4;
                $typ = $graphemeCharacterMap[$char][$char2][$char3][$char4]['typ'];
                if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4])) {
                  $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4]['ssrt'];
                } else {
                  $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4]['srt'];
                }
              }
            } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3])){ // invalid sequence
              array_push($errors,"incomplete grapheme $char$char2$char3, has no sort code");
              break;
            } else {//found valid grapheme, save it
              $str = $char.$char2.$char3;
              $ustr = $testChar.$char2.$char3;
              $typ = $graphemeCharacterMap[$char][$char2][$char3]['typ'];
              if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3])) {
                $srt = $graphemeCharacterMap[$char][$char2][$char3]['ssrt'];
              } else {
                $srt = $graphemeCharacterMap[$char][$char2][$char3]['srt'];
              }
            }
          } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2])){ // invalid sequence
            array_push($errors,"incomplete grapheme $char$char2, has no sort code");
            return false;
          } else {//found valid grapheme, save it
            $str = $char.$char2;
            $ustr = $testChar.$char2;
            $typ = $graphemeCharacterMap[$char][$char2]['typ'];
            if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2])) {
              $srt = $graphemeCharacterMap[$char][$char2]['ssrt'];
            } else {
              $srt = $graphemeCharacterMap[$char][$char2]['srt'];
            }
          }
        } else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char])){ // invalid sequence
          array_push($errors,"incomplete grapheme $char, has no sort code");
          break;
        } else {//found valid grapheme, save it
          $str = $char;
          $ustr = $testChar;
          $typ = $graphemeCharacterMap[$char]['typ'];
          if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char])) {
            $srt = $graphemeCharacterMap[$char]['ssrt'];
          } else {
            $srt = $graphemeCharacterMap[$char]['srt'];
          }
        }
      } else {
        array_push($errors,"char $char not found in grapheme character map");
        break;
      }
      if ($sylState == "S" && $typ == "V") {//add vowel carrier for starting vowel grapheme
        array_push($parsedGraData,array( "gra_grapheme"=>"ʔ",'gra_type_id'=>$graphemeTypeTermIDMap[$graphemeCharacterMap["ʔ"]['typ']],'gra_sort_code'=>$graphemeCharacterMap["ʔ"]['srt']));
      }
      $prevState = $sylState;
      $sylState = getNextSegmentState($prevState,$typ);
      $i += $inc;//adjust read pointer
      $char = $char2 = $char3 = $char4 = null;
      $typTermID = $graphemeTypeTermIDMap[$typ];
      if ( $sylState == "E") {
        array_push($errors,"invalid syllable grapheme sequence, $typ cannot follow $prevState in a syllable");
        break;
      } else if ($sylState == "O" || $sylState == "P" || $sylState == "N") {
        $tokenSplitRequired = true;
      }
      $graData = array( "gra_grapheme"=>$str,'gra_type_id'=>$typTermID,'gra_sort_code'=>$srt);
      if ($graphemeIsUpper) {
        $graData['gra_uppercase'] = $ustr;
      }
      array_push($parsedGraData,$graData);
      //remove str from preSplit string until done, then mark index as split
      if ($preSplitString && mb_strpos($preSplitString,$str) === 0) {
        $preSplitString = mb_substr($preSplitString,count($str));
        $newSplitIndex = count($parsedGraData);
      }
    }

    if (count($errors) == 0) {//parsed new syllable with no errors so run through old syllable to match graphemes and calculate TCM
      $lastFindIndex = -1;//keep track of location in oldSylGra set at -1 so first time check is index 0
      $cnt = count($oldSylGra);
//      $dbGraphemes->rewind(); //   $data['prevTCM'];
      if ($cnt > 0) {
        $prevTCM = $oldSylGra[0]->getTextCriticalMark();
      } else {
        $prevTCM = "S";
      }
      $iGraData = 0;
      $preSplitTokenChanged = false;
      $postSplitTokenChanged = false;
      $prevVowelCarrierGrapheme = null;
      foreach ($parsedGraData as $graData) {//map in order new grapheme to old for reuse
        $gra = $graData['gra_grapheme'];
        for ($index = $lastFindIndex+1; $index < $cnt; $index++) {//TODO check assumption that no duplicate character cases exist
          if ($oldSylGra[$index]->getValue() == $gra) {
            break;
          }
        }
        if ($index == $cnt) { //searched to the end, not found, so create a new grapheme in $newSylGra array as a new character with $prevTCM
          if ($iGraData > $lastFindIndex && $iGraData < $cnt) {//grapheme postion lies in old set so find TCM from position
            $graData["gra_text_critical_mark"] = $oldSylGra[$iGraData]->getTextCriticalMark();
          } else if ($prevTCM){
            $graData["gra_text_critical_mark"] = $prevTCM;
          }
          if ($isSplitSyllable) {
            if ($iGraData < $newSplitIndex) {// preSplit new grapheme so mark first token as changed
              $preSplitTokenChanged = true;
            } else {                         // post split new grapheme so mark second token as changed
              $postSplitTokenChanged = true;
            }
          }
          $grapheme = new Grapheme($graData);
          $grapheme->setOwnerID($defOwnerID);
          $grapheme->setVisibilityIDs($defVisIDs);
          $grapheme->storeScratchProperty("debugmessage","created in saveSyllable for sclID ".$syllable->getID());
          $grapheme->save();
          array_push($newSylGra, $grapheme);
          if ($grapheme->getValue() == 'ʔ') { //vowel carrier so track for updating TCM to following vowel
            $prevVowelCarrierGrapheme = $grapheme;
          } else {
            addNewEntityReturnData('gra',$grapheme);
            if ($prevVowelCarrierGrapheme) {//update the vowel carrier
              $prevVowelCarrierGrapheme->setTextCriticalMark($grapheme->getTextCriticalMark());
              addNewEntityReturnData('gra',$prevVowelCarrierGrapheme);
              $prevVowelCarrierGrapheme = null;
            }
          }
        } else { // reusing graphemes
          $lastFindIndex = $index;
          array_push($reuseGraIndex,$index);
          if ($syllable->isReadonly()) {// syllable not editable so need to clone grapheme
            if ($isSplitSyllable) {//since cloning the grapheme, need to flag the changed token
              if ($iGraData < $newSplitIndex) {//if preSplit grapheme then mark first token as changed
                $preSplitTokenChanged = true;
              } else {                         // post split so mark second token as changed
                $postSplitTokenChanged = true;
              }
            }
            $grapheme = $oldSylGra[$index]->cloneEntity($defAttrIDs,$defVisIDs);
            $grapheme->storeScratchProperty("debugmessage","cloned in saveSyllable before cloning readonly syllable sclID ".$syllable->getID());
            $grapheme->save();
            if ($grapheme->getValue() == 'ʔ') { //vowel carrier so track for updating TCM to following vowel
              $prevVowelCarrierGrapheme = $grapheme;
            } else {
              addNewEntityReturnData('gra',$grapheme);
              if ($prevVowelCarrierGrapheme) {//update the vowel carrier
                $prevVowelCarrierGrapheme->setTextCriticalMark($grapheme->getTextCriticalMark());
                $prevVowelCarrierGrapheme->save();
                addNewEntityReturnData('gra',$prevVowelCarrierGrapheme);
                $prevVowelCarrierGrapheme = null;
              }
            }
          } else { //TODO discuss whether grapheme vis should be validated to match syllable
            $grapheme =$oldSylGra[$index];
          }
          $prevTCM = $grapheme->getTextCriticalMark();
          array_push($newSylGra,$grapheme);
        }
        $iGraData++;
      }

      //save any new graphemes
      $newGraIDs = array();
      foreach ($newSylGra as $grapheme) {
        if (!$grapheme->getID()) {
          $grapheme->save();
          if ($grapheme->hasError()) {
            array_push($errors,"error saving grapheme '".$grapheme->getValue()."' - ".$grapheme->getErrors(true));
            break;
          }
        }
        array_push($newGraIDs, $grapheme->getID());
      }

      if (count($errors) == 0) {
        //update syllable and hierarchy if needed
        if ($syllable->isReadonly()) {
          //clone syllable and add new grapheme ids
          $syllableClone = $syllable->cloneEntity($defAttrIDs,$defVisIDs);
          $syllableClone->setGraphemeIDs($newGraIDs);
          $syllableClone->calculateSortCodes();
          $syllableClone->save();
          addNewEntityReturnData('scl',$syllableClone);
          $oldSclID = $syllable->getID();
          $newSclID = $syllableClone->getID();
          foreach ($newGraIDs as $newGraID) {
            addUpdateEntityReturnData('gra',$newGraID,'sclID',$newSclID);
          }
          if ($syllableClone->hasError()) {
            array_push($errors,"error cloning syllable '".$syllableClone->getValue()."' - ".$syllableClone->getErrors(true));
          } else if ($physLineSeq == null) {
            array_push($warnings,"no physical line found for syllable ID $oldSclID");
          } else {// update hierarchy
            $oldPhysLineSeqID = $physLineSeq->getID();
            $oldPhysLineSeqGID = $physLineSeq->getGlobalID();
            if ($physLineSeq->isReadonly()) {
              $physLineSeq = $physLineSeq->cloneEntity($defAttrIDs,$defVisIDs);
            } else {
              invalidateSequenceCache($physLineSeq,$edition->getID());
            }
            // update physical line components ids by replacing $oldSclID with $newSclID
            $oldSclGID = "scl:$oldSclID";
            $physLineSeqEntityIDs = $physLineSeq->getEntityIDs();
            $sclIndex = array_search($oldSclGID,$physLineSeqEntityIDs);
            if ($sclIndex !== false) {
              array_splice($physLineSeqEntityIDs,$sclIndex,1,"scl:$newSclID");
              $physLineSeq->setEntityIDs($physLineSeqEntityIDs);
              //save physical line sequence
              $physLineSeq->save();
              if ($physLineSeq->hasError()) {
                array_push($errors,"error updating physical line sequence '".$physLineSeq->getLabel()."' - ".$physLineSeq->getErrors(true));
              }else if ($oldPhysLineSeqGID != $physLineSeq->getGlobalID()){//cloned so it's new
                addNewEntityReturnData('seq',$physLineSeq);
                $retVal['newPhysLineSeqID'] = $physLineSeq->getID();
              }else { // only updated
                //changed components on a cached sequence so invalidate cache to recalc on next refresh
                addUpdateEntityReturnData('seq',$physLineSeq->getID(),'entityIDs',$physLineSeq->getEntityIDs());
              }
            } else {
              array_push($errors,"no index in physical line found for syllable $oldSclGID");
            }
          }
          if (count($errors) == 0 && $oldPhysLineSeqGID != $physLineSeq->getGlobalID()) {
            $oldPhysSeqID = $seqPhys->getID();
            if ($seqPhys->isReadonly()) {//clone physicalText sequence if not owned
              $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
            }
            // update container if cloned
            $physSeqEntityIDs = $seqPhys->getEntityIDs();
            $seqIndex = array_search($oldPhysLineSeqGID,$physSeqEntityIDs);
            if ($seqIndex !== false) {
              array_splice($physSeqEntityIDs,$seqIndex,1,$physLineSeq->getGlobalID());
              $seqPhys->setEntityIDs($physSeqEntityIDs);
              $seqPhys->save();
              if ($seqPhys->hasError()) {
                array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
              }else if ($oldPhysSeqID != $seqPhys->getID()){
                addNewEntityReturnData('seq',$seqPhys);
              }else {
                addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
              }
            } else {
              array_push($errors,"no index in physical text found for line physical seq $oldPhysLineSeqGID");
            }
          }
        } else { //syllable already owned so remove obsolete graphemes
          // Delete the old graphemes that are not reused
          for ($index =0; $index < $cnt; $index++) {
            if ( !in_array($index,$reuseGraIndex)) {
              $grapheme = $dbGraphemes->searchKey($oldSylGraIDs[$index]);
              if ($grapheme->markForDelete()) {
                //************* deleted grapheme ************//
                addRemoveEntityReturnData("gra",$grapheme->getID());
              } else {
                array_push($warnings,"mark for deleted fails on grapheme '".$grapheme->getValue()."' id = ".$grapheme->getID());
                error_log(" saveSyllable service - mark for deleted fails on grapheme '".$grapheme->getValue()."' id = ".$grapheme->getID());
              }
            }
          }
          //update syllable with new grapheme IDs
          $syllable->setGraphemeIDs($newGraIDs);
          $syllable->calculateSortCodes();
          $syllable->save();
          addUpdateEntityReturnData('scl',$syllable->getID(),'graphemeIDs',$syllable->getGraphemeIDs());
          addUpdateEntityReturnData('scl',$syllable->getID(),'value',$syllable->getValue());
          addUpdateEntityReturnData('scl',$syllable->getID(),'sort',$syllable->getSortCode());
          addUpdateEntityReturnData('scl',$syllable->getID(),'sort2',$syllable->getSortCode2());
        }

        if (isset($physLineSeq) && $physLineSeq->getID() && isset($edition) && $edition->getID()) {
          invalidateSequenceCache($physLineSeq, $edition->getID());
        }

        //find syllable's location within the token(s) for split syllable case
        if ($isSplitSyllable) {
          $oldTokGraIDs = $token->getGraphemeIDs();
          $oldTok2GraIDs = $token2->getGraphemeIDs();
          //match oldSylGraIDs or
          $startIndex = array_search($oldSylGraIDs[0],$oldTokGraIDs);
          $endIndex = array_search($oldSylGraIDs[count($oldSylGraIDs)-1],$oldTok2GraIDs);
          $startTokIDs = array();
          $endTokIDs = array();
          if ( $startIndex === false || $endIndex === false) {
            array_push($errors,"token '".$token->getValue()."' or token '".$token2->getValue()."' is out of synch with original db syllable '".$strSylDB);
          } else {
            //calc newTokGraIDs and any split tokens for the case of syllable replace
            if ($startIndex > 0) {
              $startTokIDs = array_slice($oldTokGraIDs,0,$startIndex);
            }
            if ($endIndex < (count($oldTok2GraIDs) -1)) {
              $endTokIDs = array_slice($oldTok2GraIDs,$endIndex+1);
            }
          }
        } else {//find syllable's location within the token(s) for general case
          $oldTokGraIDs = $token->getGraphemeIDs();
          //match oldSlyGraIDs or
          $startIndex = array_search($oldSylGraIDs[0],$oldTokGraIDs);
          $endIndex = array_search($oldSylGraIDs[count($oldSylGraIDs)-1],$oldTokGraIDs);
          $startTokIDs = array();
          $endTokIDs = array();
          if ( $startIndex === false || $endIndex === false || $startIndex > $endIndex) {
            array_push($errors,"token '".$token->getValue()."' is out of synch with original db syllable '".$strSylDB);
          } else {
            //calc newTokGraIDs and any split tokens for the case of syllable replace
            if ($startIndex > 0) {
              $startTokIDs = array_slice($oldTokGraIDs,0,$startIndex);
            }
            if ($endIndex < (count($oldTokGraIDs) -1)) {
              $endTokIDs = array_slice($oldTokGraIDs,$endIndex+1);
            }
          }
        }

        $oldTokCmp2GID = null;
        if (count($errors) == 0 &&
              //normal case or special single token character already on boundary at first or last position
            (!$tokenSplitRequired || ( count($startTokIDs) == 0 && count($endTokIDs) ==0))) {
          //normal update token
          if ($isSplitSyllable) {//split syllable case - divide grapheme ids into post 1st tok and pre 2nd tok
            $newGraIDsTok2 = array_splice($newGraIDs,$newSplitIndex);
            $newTokGraIDs = array_merge($startTokIDs,$newGraIDs);
            $newTok2GraIDs = array_merge($newGraIDsTok2,$endTokIDs);
            $oldTokCmpGID = null;
            if ($token->isReadonly()){//clone token
              $oldTokCmpGID = $token->getGlobalID();
              $token = $token->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $token->setGraphemeIDs($newTokGraIDs);
            $token->getValue(true);//cause recalc
            $token->getSyllableClusterIDs(false,true); //force refresh will call token->save
            //  $token->save();
            $newTokCmpGID = $token->getGlobalID();
            if ($token->hasError()) {
              array_push($errors,"error cloning token '".$token->getValue()."' - ".$token->getErrors(true));
            } else if ($oldTokCmpGID) {//**********new tok
              addNewEntityReturnData('tok',$token);
            } else {
              addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
              addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
              addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
              addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs(false,true));
              addUpdateEntityReturnData('tok',$token->getID(),'sort', $token->getSortCode());
              addUpdateEntityReturnData('tok',$token->getID(),'sort2', $token->getSortCode2());
            }
            if ($token2->isReadonly()){//clone token
              $oldTokCmp2GID = $token2->getGlobalID();
              $token2 = $token2->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $token2->setGraphemeIDs($newTok2GraIDs);
            $token2->getValue(true);//cause recalc
            $token2->getSyllableClusterIDs(false,true); //force refresh will call token->save
            //  $token2->save();
            $newTokCmp2GID = $token2->getGlobalID();
            if ($token2->hasError()) {
              array_push($errors,"error cloning token '".$token2->getValue()."' - ".$token2->getErrors(true));
            } else if ($oldTokCmp2GID) {//**********new tok2
              addNewEntityReturnData('tok',$token2);
            } else {
              addUpdateEntityReturnData('tok',$token2->getID(),'graphemeIDs',$token2->getGraphemeIDs());
              addUpdateEntityReturnData('tok',$token2->getID(),'value',$token2->getValue());
              addUpdateEntityReturnData('tok',$token2->getID(),'transcr',$token2->getTranscription());
              addUpdateEntityReturnData('tok',$token2->getID(),'syllableClusterIDs',$token2->getSyllableClusterIDs(false,true));
              addUpdateEntityReturnData('tok',$token2->getID(),'sort', $token2->getSortCode());
              addUpdateEntityReturnData('tok',$token2->getID(),'sort2', $token2->getSortCode2());
            }
          } else { //normal syllable
            $newTokGraIDs = array_merge($startTokIDs,$newGraIDs,$endTokIDs);
            $oldTokCmpGID = null;
            if ($token->isReadonly()){//clone token
              $oldTokCmpGID = $token->getGlobalID();
              $token = $token->cloneEntity($defAttrIDs,$defVisIDs);
            } else {
              invalidateWordLemma($token->getGlobalID());
            }
            $token->setGraphemeIDs($newTokGraIDs);
            $token->getValue(true);//cause recalc
            $token->save();
            $newTokCmpGID = $token->getGlobalID();
            //**********new tok
            if ($token->hasError()) {
              array_push($errors,"error cloning token '".$token->getValue()."' - ".$token->getErrors(true));
            } else if ($oldTokCmpGID) {
              addNewEntityReturnData('tok',$token);
            } else {
              // !!!indicate that the textDiv needs update
              addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
              addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
              addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
              addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs(false,true));
              addUpdateEntityReturnData('tok',$token->getID(),'sort', $token->getSortCode());
              addUpdateEntityReturnData('tok',$token->getID(),'sort2', $token->getSortCode2());
            }
          }

          if (count($errors) == 0 && ($oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID ||//we replaced token
              $oldTokCmp2GID && $oldTokCmp2GID != $newTokCmp2GID && !$compounds2)
              && isset($compounds) && count($compounds) > 0) {// and it's contained in a compound so update compounds
            while (count($compounds) && $oldTokCmpGID != $newTokCmpGID) {
              $compound = array_shift($compounds);
              $componentIDs = $compound->getComponentIDs();
              $tokCmpIndex = array_search($oldTokCmpGID,$componentIDs);
              array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID);
              if ($oldTokCmp2GID && $oldTokCmp2GID != $newTokCmp2GID && !$compounds2) {//split tokens in same compound
                $tokCmpIndex = array_search($oldTokCmp2GID,$componentIDs);
                array_splice($componentIDs,$tokCmpIndex,1,$newTokCmp2GID);
                $oldTokCmp2GID = null;//make sure that this is only replaced in the first level compound
              }
              $oldTokCmpGID = $compound->getGlobalID();
              if ($compound->isReadonly()) {
                $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
              }
              // update compound container
              $compound->setComponentIDs($componentIDs);
              $compound->getValue(true);//cause recalc
              $compound->save();
              $newTokCmpGID = $compound->getGlobalID();
              if ($compound->hasError()) {
                array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
              }else if ($oldTokCmpGID != $newTokCmpGID){
                addNewEntityReturnData('cmp',$compound);
              }else {
                addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
                addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
              }
            }
          }
          invalidateWordLemma($oldTokCmpGID);//should we remove the token/compound from the lemma?
          //check for compounds beyond propagated changes
          if (count($errors) == 0 && (!$oldTokCmpGID || $oldTokCmpGID == $newTokCmpGID)
              && isset($compounds) && count($compounds) > 0) {//get here when token update for compounds stops before text physical so we need to recalc other compounds
            while (count($compounds)>0) {
              $compound = array_shift($compounds);
              $compound->getValue(true);//cause recalc
              $compound->save();
              if ($compound->hasError()) {
                array_push($errors,"error updating compound during recalc '".$compound->getValue()."' - ".$compound->getErrors(true));
              }else {
                addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
                addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
              }
            }
          }
          if (count($errors) == 0 && $oldTokCmp2GID && $oldTokCmp2GID != $newTokCmp2GID//we replaced token2
              && isset($compounds2) && count($compounds2) > 0) {// and it's contained in a compound so update compounds
            while (count($compounds2) && $oldTokCmp2GID != $newTokCmp2GID) {
              $compound = array_shift($compounds2);
              $componentIDs = $compound->getComponentIDs();
              $tokCmpIndex = array_search($oldTokCmp2GID,$componentIDs);
              array_splice($componentIDs,$tokCmpIndex,1,$newTokCmp2GID);
              $oldTokCmp2GID = $compound->getGlobalID();
              if ($compound->isReadonly()) {
                $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
              }
              // update compound container
              $compound->setComponentIDs($componentIDs);
              $compound->getValue(true);//cause recalc
              $compound->save();
              $newTokCmp2GID = $compound->getGlobalID();
              if ($compound->hasError()) {
                array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
              }else if ($oldTokCmp2GID != $newTokCmp2GID){
                addNewEntityReturnData('cmp',$compound);
              }else {
                addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
                addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
              }
            }
          }
          //check for compounds beyond propagated changes that need recalc
          if (count($errors) == 0 && (!$oldTokCmp2GID || $oldTokCmp2GID == $newTokCmp2GID)
              && isset($compounds2) && count($compounds2) > 0) {//get here when token update for compounds stops before text physical so we need to recalc other compounds
            while (count($compounds2)>0) {
              $compound = array_shift($compounds2);
              $compound->getValue(true);//cause recalc
              $compound->save();
              if ($compound->hasError()) {
                array_push($errors,"error updating compound during recalc '".$compound->getValue()."' - ".$compound->getErrors(true));
              }else {
                addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
                addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
                addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort', $compound->getSortCode());
                addUpdateEntityReturnData('cmp',$compound->getID(),'sort2', $compound->getSortCode2());
              }
            }
          }
          invalidateWordLemma($oldTokCmp2GID);//should we remove the token/compound from the lemma?
        } else {
          // split token case for mid token special symbol or punctuation
          $oldTokCmpGID = $token->getGlobalID();
          if ($token->isReadonly()){ //clone token
            $token = $token->cloneEntity($defAttrIDs,$defVisIDs);
          }
          $newSplitToken = new Token();
          //divide up graphemes
          if ($startTokIDs && count($startTokIDs) > 0) {//graphemes before split
            $token->setGraphemeIDs($startTokIDs);
            $token->getValue(true);//cause recalc
            $token->getSyllableClusterIDs(false,true); //force refresh will call token->save
          //  $token->save();
            $newTokGID = $token->getGlobalID();
            $newSplitToken->setGraphemeIDs($newGraIDs);
            $newSplitToken->getValue(true);//cause recalc
            $newSplitToken->getSyllableClusterIDs(false,true); //force refresh will call token->save
          //  $newSplitToken->save();
            $newSplitTokGID = $newSplitToken->getGlobalID();
            $tokCmpReplaceGIDs = array($newTokGID,$newSplitTokGID);
            if ($endTokIDs && count($endTokIDs) > 0) {//some graphemes after split too
              $newSplitToken2 = new Token();
              $newSplitToken2->setGraphemeIDs($endTokIDs);
              $newSplitToken2->getValue(true);//cause recalc
              $newSplitToken2->getSyllableClusterIDs(false,true); //force refresh will call token->save
            //  $newSplitToken2->save();
              array_push($tokCmpReplaceGIDs,$newSplitToken2->getGlobalID());
            }
          } else {//replace first syllable case
            $token->setGraphemeIDs($newGraIDs);
            $token->getValue(true);//cause recalc
            $token->getSyllableClusterIDs(false,true); //force refresh will call token->save
          //  $token->save();
            $newTokGID = $token->getGlobalID();
            $newSplitToken->setGraphemeIDs($endTokIDs);
            $newSplitToken->getValue(true);//cause recalc
            $newSplitToken->getSyllableClusterIDs(false,true); //force refresh will call token->save
          //  $newSplitToken->save();
            $newSplitTokGID = $newSplitToken->getGlobalID();
            $tokCmpReplaceGIDs = array($newTokGID,$newSplitTokGID);
          }
          //todo Handle roolback in case of error.
          if ($token->hasError()) {
            array_push($errors,"error splitting token while updating '".$token->getValue()."' - ".$token->getErrors(true));
          } else if ($newSplitToken->hasError()) {
            array_push($errors,"error splitting token while creating '".$newSplitToken->getValue()."' - ".$newSplitToken->getErrors(true));
          } else if (isset($newSplitToken2) && $newSplitToken2->hasError()) {
            array_push($errors,"error splitting token while creating 2 '".$newSplitToken2->getValue()."' - ".$newSplitToken2->getErrors(true));
          } else if ($oldTokCmpGID != $newTokGID) {
            addNewEntityReturnData('tok',$token);
          } else {
            addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
            addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
            addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
            addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs(false,true));
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
              if ($oldTokCmpIndex !== false) {
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
              } else {
                array_push($errors,"no index for entity GID $oldTokCmpGID found for compound ".$compound->getGlobalID());
              }
            }
          }
          $newTokCmpGID = $tokCmpReplaceGIDs;//ensure this gets altered in the text div
        }

        // update Text Division Sequence if needed
        $oldTxtDivSeqGID = null;
        if (count($errors) == 0 && ($oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID ||
                                    $oldTokCmp2GID && $oldTokCmp2GID != $newTokCmp2GID)) {//token or compound change to text division sequence
          $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
          $oldTxtDivSeqID = $textDivSeq->getID();
          if ($textDivSeq->isReadonly()) {
            $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
          }
          // update text dividion components ids by replacing $oldTokCmpGID with $newTokCmpGID
          $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
          if ($oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID) {
            $tokCmpIndex = array_search($oldTokCmpGID,$textDivSeqEntityIDs);
            if ($tokCmpIndex !== false) {
              array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmpGID);
            } else {
              array_push($errors,"no index for entity GID $oldTokCmpGID found for text div seq ".$textDivSeq->getGlobalID());
            }
          }
          if ($oldTokCmp2GID && $oldTokCmp2GID != $newTokCmp2GID) {
            $tokCmpIndex = array_search($oldTokCmp2GID,$textDivSeqEntityIDs);
            if ($tokCmpIndex !== false) {
              array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmp2GID);
            } else {
              array_push($errors,"no index for entity GID $oldTokCmp2GID found for text div seq ".$textDivSeq->getGlobalID());
            }
          }
          if (count($errors) == 0) {
            $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
            //save text division sequence
            $textDivSeq->save();
            $newTxtDivSeqGID = $textDivSeq->getGlobalID();
            if ($textDivSeq->hasError()) {
              array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
            }else if ($oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so it's new
              addNewEntityReturnData('seq',$textDivSeq);
              $retVal['newTextDivSeqGID'] = $newTxtDivSeqGID;
            }else { // only updated
              //changed components on a cached sequence so invalidate cache to recalc on next refresh
              addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
            }
          }
        }
        invalidateSequenceCache($textDivSeq, $edition->getID());
        // update Text (Text Division Container) Sequence if needed
        if (count($errors) == 0 && $oldTxtDivSeqGID && $oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so update container
          //clone text sequence if not owned
          if ($seqText->isReadonly()) {
            $oldTextSeqID = $seqText->getID();
            $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
          }
          $textSeqEntityIDs = $seqText->getEntityIDs();
          $txtDivSeqIndex = array_search($oldTxtDivSeqGID,$textSeqEntityIDs);
          if ($txtDivSeqIndex !== false) {
            array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID);
            $seqText->setEntityIDs($textSeqEntityIDs);
            //save text sequence
            $seqText->save();
            if ($seqText->hasError()) {
              array_push($errors,"error updating text sequence '".$seqText->getLabel()."' - ".$seqText->getErrors(true));
            }else if ($oldTextSeqID){//cloned so it's new
              addNewEntityReturnData('seq',$seqText);
            }else { // only updated
              addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
            }
          } else {
            array_push($errors,"no index for text div seq GID $oldTxtDivSeqGID found for text seq ".$seqText->getGlobalID());
          }
        }
        if (count($errors) == 0 && $edition) {
          //touch edition for synch code
          $edition->storeScratchProperty("lastModified",$edition->getModified());
          $edition->setStatus('changed');
          // update edition if sequences cloned
          if ($oldPhysSeqID || $oldTextSeqID) {
            //get segIDs
            $edSeqIds = $edition->getSequenceIDs();
            //if phys changed update id
            if ($oldPhysSeqID) {
              $seqIDIndex = array_search($oldPhysSeqID,$edSeqIds);
              array_splice($edSeqIds,$seqIDIndex,1,$seqPhys->getID());
              $retVal['newPhysSeqID'] = $seqPhys->getID();
            }
            if ($oldTextSeqID) {
              $seqIDIndex = array_search($oldTextSeqID,$edSeqIds);
              array_splice($edSeqIds,$seqIDIndex,1,$seqText->getID());
              $retVal['newTextSeqID'] = $seqText->getID();
            }
            //update edition seqIDs
            $edition->setSequenceIDs($edSeqIds);
          }
          $edition->save();
          invalidateCachedEditionEntities($edition->getID());
          invalidateCachedEditionViewerInfo($edition);
          invalidateCachedEditionViewerHtml($edition->getID());
          invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
          if ($edition->hasError()) {
            array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
          }else{
            addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
          }
        }
      }
    }
  }
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
?>
