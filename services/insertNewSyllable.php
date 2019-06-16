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
  * saveEntityData
  *
    * saves entity data for passed entity data structure of the form
  *
  * {"tableprefix or tablename": array of records
  *                   where each record is an array of columnName:value pairs
  *                             where id column is required even new records of the form prefix:newID,
  * }
  *
  * {"tok":[{"tok_id":355,"tok_grapheme_ids":"{594,595,596,597,598}"},
  *         {"tok_id":"new14","tok_grapheme_ids":"{595,596,599,600,601}"}]
  * }
  *
  * return json format:  //whole record data returned due to multi-user editing  ?? should datestamp affect save
  *
  * { "entityName" : {
  *           "total" : #entityRecords,
  *           "success": true or false,   //if errors encountered false is returned
  *           "columns": array of columnNames for the records returned
  *           "records" : array of records where each record is an array of column values in "columns" order
  *    }
  * }
  *
  * { "token" : {
  *           "total" : 2,
  *           "success": true,
  *           "columns": ["tok_id","tok_value"," ...,"tok_scratch"],
  *           "records" : [[355,"putra",.....,"\"CKN\":\"CKI02661\""],
  *                        [1209,"putre",.....,"\"CKN\":\"CKI02661\""]]
  *    }
  * }
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
  ob_start('');

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
  $entities = array();
  $warnings = array();
  $ednOwnerID = null;
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
        //get default attribution
        if (!$defAttrIDs || count($defAttrIDs) == 0) {
          $attrIDs = $edition->getAttributionIDs();
          if ($attrIDs && count($attrIDs) > 0 ) {
            $defAttrIDs = array($attrIDs[0]);
          }
        }
        //get default visibility
        if (!$defVisIDs || count($defVisIDs) == 0) {
          $visIDs = $edition->getVisibilityIDs();
          if ($visIDs && count($visIDs) > 0 ) {
            $defVisIDs = array($visIDs[0]);
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
          if (!$seqPhys && $seqType == "TextPhysical"){
            $seqPhys = $edSequence;
          }
          if (!$seqText && $seqType == "Text"){
            $seqText = $edSequence;
          }
        }
      }
    } else {
      array_push($errors,"unaccessable edition");
    }
    $refSclGID = null;
    if ( isset($data['refSclGID'])) {//get reference SyllableClusterGID
      $refSclGID = $data['refSclGID'];
    }
    $txtScl = null;
    if ( isset($data['txtScl'])) {//get new txt SyllableClusterGID
      $txtScl = $data['txtScl'];
    } else {
      $txtScl = '.';
    }
    $insPos = null;
    if ( isset($data['insPos'])) {//get alignment info - where to insert
      $insPos = $data['insPos'];
    }
    $physLineSeqID = null;
    $lineSclIDs = null;
    if ( isset($data['lineSeqID'])) {//get line sequence
      $physLineSeqID = $data['lineSeqID'];
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
      } else {
        $lineSclIDs = $physLineSeq->getEntityIDs();
        if (!$refSclGID && count($lineSclIDs) > 0) {//case where the is no reference syllable given then insert at beginning of line
          $refSclGID = $lineSclIDs[0]; // get first syllable
          $insPos = "before";
        }
      }
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  if (count($errors) == 0  && $refSclGID && substr($refSclGID,0,3) == 'scl') {
    if (isset($data['context'])) {
      $context = explode(",",$data['context']);
      while ($gid = array_pop($context)) {
        $id = substr($gid,3);
        switch (substr($gid,0,3)) {
          case "tok":
              $token = new Token($id);
            break;
          case "cmp":
            if (!@$compounds) {
              $compounds = array(new Compound($id));
            } else {
                array_push($compounds, new Compound($id));
            }
            break;
          case "seq":
            $textDivSeq = new Sequence($id);
            break;
        }
      }
    } else {
      array_push($errors,"missing context");
    }
    if ($txtScl && count($errors) == 0) {
      //for each char in $strSylNew compare against $strSylDB
      $parsedGraData = array();
      $newSylGraIDs = array();
      if ($txtScl == '.') { //default syllable insert
        array_push($parsedGraData,array( "gra_grapheme"=>"ʔ",
                                         'gra_type_id'=>$graphemeTypeTermIDMap[$graphemeCharacterMap["ʔ"]['typ']],
                                         'gra_sort_code'=>$graphemeCharacterMap["ʔ"]['srt']));
        array_push($parsedGraData,array( "gra_grapheme"=>".",
                                         'gra_type_id'=>$graphemeTypeTermIDMap[$graphemeCharacterMap["."]['typ']],
                                         'gra_sort_code'=>$graphemeCharacterMap["."]['srt']));
      } else { //need to parse and validate into graphmemes
        $cnt = mb_strlen($txtScl);
        $sylState = "S"; //start of syllable
        for ($i=0; $i< $cnt;) {
          $char = mb_substr($strSylNew,$i,1);
          $inc = 1;
          $testChar = $char;
//          $char = mb_strtolower($char);
          $graphemeIsUpper = false;//uppercase Grapheme make it lower case to allow all caps for logograms
//          $graphemeIsUpper = ($testChar != $char);//uppercase Grapheme
          // convert multi-byte to grapheme - using greedy lookup
          if (array_key_exists($char,$graphemeCharacterMap)){
            //check next character included
            $char2 = mb_substr($strSylNew,$i+1,1);
            if (($i+$inc < $cnt) && array_key_exists($char2,$graphemeCharacterMap[$char])){ // another char for grapheme
              $inc++;
              $char3 = mb_substr($strSylNew,$i+2,1);
              if (($i+$inc < $cnt) && array_key_exists($char3,$graphemeCharacterMap[$char][$char2])){ // another char for grapheme
                $inc++;
                $char4 = mb_substr($strSylNew,$i+3,1);
                if (($i+$inc < $cnt) && array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3])){ // another char for grapheme
                  $inc++;
                  $char5 = mb_substr($strSylNew,$i+4,1);
                  if (($i+$inc < $cnt) && array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3][$char4])){ // another char for grapheme
                    $inc++;
                    $char6 = mb_substr($strSylNew,$i+5,1);
                    if (($i+$inc < $cnt) && array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])){ // another char for grapheme
                      $inc++;
                      $char7 = mb_substr($strSylNew,$i+6,1);
                      if (($i+$inc < $cnt) && array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])){ // another char for grapheme
                        $inc++;
                        $char8 = mb_substr($strSylNew,$i+7,1);
                        if (($i+$inc < $cnt) && array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])){ // another char for grapheme
                          $inc++;
                          if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8])){ // invalid sequence
                            array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7$char8 has no sort code");
                            return false;
                          }else{//found valid grapheme, save it
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
                          array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7 has no sort code");
                          return false;
                        }else{//found valid grapheme, save it
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
                        array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5$char6 has no sort code");
                        return false;
                      }else{//found valid grapheme, save it
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
                      array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5 has no sort code");
                      return false;
                    }else{//found valid grapheme, save it
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
                  }else{//found valid grapheme, save it
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
                break;
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
            array_push($parsedGraData,array( "gra_grapheme"=>"ʔ",'gra_type_id'=>$graphemeCharacterMap["ʔ"]['typ'],'gra_sort_code'=>$graphemeCharacterMap["ʔ"]['srt']));
          }
          $prevState = $sylState;
          $sylState = getNextSegmentState($prevState,$typ);
          $i += $inc;//adjust read pointer
          $char = $char2 = $char3 = $char4 = null;
          $typTermID = $graphemeTypeTermIDMap[$typ];
          if ( $sylState == "E") {
            array_push($errors,"invalid syllable grapheme sequence, $typ cannot follow $prevState in a syllable");
            break;
          }
          $graData = array( "gra_grapheme"=>$str,'gra_type_id'=>$typTermID,'gra_sort_code'=>$srt);
          if ($graphemeIsUpper) {
            $graData['gra_uppercase'] = $ustr;
          }
          array_push($parsedGraData,$graData);
        }
      }
      if (count($errors) == 0) {//parsed syllable with no errors so run through old syllabe to match graphemes and calculate TCM
        //get TCM from adjacent syllable
        $refSyllable = new SyllableCluster(substr($refSclGID,4));
        if ($refSyllable->hasError()) {
          array_push($warnings,"error loading syllable to get TCM, using null TCM");
          $adjSclTCM = null;
        } else {
          $adjSlcGraphemes = $refSyllable->getGraphemes(true);
          $refSlcGraIDs = $refSyllable->getGraphemeIDs();
          $indexTCM = ($insPos == 'before' ? 0 : count($adjSlcGraphemes)-1);
          $prevTCM = $adjSlcGraphemes->searchKey($refSlcGraIDs[$indexTCM])->getTextCriticalMark();
        }
        //create new graphemes
        foreach ($parsedGraData as $graData) {
          if ($prevTCM) {
            $graData["gra_text_critical_mark"] = $prevTCM;
          }
          $grapheme = new Grapheme($graData);
          $grapheme->setOwnerID($defOwnerID);
          $grapheme->setVisibilityIDs($defVisIDs);
          if ($defAttrIDs){
            $grapheme->setAttributionIDs($defAttrIDs);
          }
          $grapheme->save();
          array_push($newSylGraIDs, $grapheme->getID());
          addNewEntityReturnData('gra',$grapheme);
        }
        //create new syllable
        $newSyllable = new SyllableCluster();
        $newSyllable->setGraphemeIDs($newSylGraIDs);
        $newSyllable->setOwnerID($defOwnerID);
        $newSyllable->setVisibilityIDs($defVisIDs);
        if ($defAttrIDs){
          $newSyllable->setAttributionIDs($defAttrIDs);
        }
        $newSyllable->save();
        addNewEntityReturnData('scl',$newSyllable);
        $newSclID = $newSyllable->getID();
        foreach ($newSylGraIDs as $newSylGraID) {
          addUpdateEntityReturnData('gra',$newSylGraID,'sclID',$newSclID);
        }
        if ($newSyllable->hasError()) {
          array_push($errors,"error creating syllable '".$newSyllable->getValue()."' - ".$newSyllable->getErrors(true));
        }else{// update container hierarchy clone as needed
          //physical sequence hierarchy first
          $oldPhysLineSeqID = null;
          $oldPhysLineSeqGID = null;
          if ($physLineSeq->isReadonly()) {//clone physicalLine sequence if not owned
            $oldPhysLineSeqID = $physLineSeq->getID();
            $oldPhysLineSeqGID = $physLineSeq->getGlobalID();
            $physLineSeq = $physLineSeq->cloneEntity($defAttrIDs,$defVisIDs);
          }
          //find index of refScl in physical line sequence
          $physLineSclGIDs = $physLineSeq->getEntityIDs();
          $refSclIndex = array_search($refSclGID,$physLineSclGIDs);
          if ($insPos == 'after') {// need to move to next GID for insert
            $refSclIndex++;
          }
          if ($refSclIndex == count($physLineSclGIDs)) { //prepend
            array_push($physLineSclGIDs,"scl:$newSclID");
          } else { //splice
            array_splice($physLineSclGIDs,$refSclIndex,0,"scl:$newSclID");
          }
          $physLineSeq->setEntityIDs($physLineSclGIDs);
          $physLineSeq->save();
          if ($physLineSeq->hasError()) {
            array_push($errors,"error updating physical line sequence '".$physLineSeq->getLabel()."' - ".$physLineSeq->getErrors(true));
          }else if ($oldPhysLineSeqID){//cloned so send new physical line seq data and mark old to be removed
            addNewEntityReturnData('seq',$physLineSeq);
            $retVal['newPhysLineSeqID'] = $physLineSeq->getID();
            //addRemoveEntityReturnData('seq',$oldPhysLineSeqID);
            //update physical sequence
            if ($seqPhys->isReadonly()) {
              $oldPhysSeqID = $seqPhys->getID();
              $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
            }
            $physSeqEntityIDs = $seqPhys->getEntityIDs();
            $seqIndex = array_search($oldPhysLineSeqGID,$physSeqEntityIDs);
            array_splice($physSeqEntityIDs,$seqIndex,1,$physLineSeq->getGlobalID());
            $seqPhys->setEntityIDs($physSeqEntityIDs);
            $seqPhys->save();
            if ($seqPhys->hasError()) {
              array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
            }else if ($oldPhysSeqID){// return insert data
              addNewEntityReturnData('seq',$seqPhys);
              //addRemoveEntityReturnData('seq',$oldPhysSeqID);
            }else {
              //**********update seq
              addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
            }
          }else {//update return data
            //changed components on a cached sequence so invalidate cache to recalc on next refresh
            invalidateCachedSeqEntities($physLineSeq->getID(),$edition->getID());
            addUpdateEntityReturnData('seq',$physLineSeq->getID(),'entityIDs',$physLineSeq->getEntityIDs());
          }
          $needsSeparateToken = false;
          if (count($errors) == 0) { // update token
            //find insert location within the token
            $tokGraIDs = $token->getGraphemeIDs();
            if (count($tokGraIDs) == 1) {//check for punctuation type to determine if new syllable needs new token.
              $grapheme = new Grapheme($tokGraIDs[0]);
              $needsSeparateToken = ($grapheme->getType() == Entity::getIDofTermParentLabel("punctuation-graphemetype") ||//term dependency
                                      $grapheme->getType() == Entity::getIDofTermParentLabel("unknown-graphemetype"));//term dependency
            }
            if (!$needsSeparateToken) {
              $insIndex = null;
              //if before
              if ($insPos == 'before') {//then use refScl's first graID and backup one position
                $insIndex = array_search($refSlcGraIDs[0],$tokGraIDs);
              } else { // use refScl's last graID
                $insIndex = array_search($refSlcGraIDs[count($refSlcGraIDs)-1],$tokGraIDs);
                if ($insIndex !== false) {
                  $insIndex++;
                }
              }
              if ($insIndex == count($tokGraIDs)) { //append
                $tokGraIDs = array_merge($tokGraIDs,$newSylGraIDs);
              } else { //splice
                array_splice($tokGraIDs,$insIndex,0,$newSylGraIDs);
              }
              $oldTokCmpGID = null;
              if ($token->isReadonly()){
                $oldTokCmpGID = $token->getGlobalID();
                //clone token
                $token = $token->cloneEntity($defAttrIDs,$defVisIDs);
              }
              $token->setGraphemeIDs($tokGraIDs);
              $token->getValue(true);//cause recalc
              $token->updateLocationLabel();
              $token->updateBaselineInfo();
              $token->save();
              $newTokCmpGID = $token->getGlobalID();
              //**********new tok
              if ($token->hasError()) {
                array_push($errors,"error cloning token '".$token->getValue()."' - ".$token->getErrors(true));
              } else if ($oldTokCmpGID) {
                addNewEntityReturnData('tok',$token);
                //addRemoveEntityReturnData(substr($oldTokCmpGID,0,3),substr($oldTokCmpGID,4));
              } else {
                addUpdateEntityReturnData('tok',$token->getID(),'graphemeIDs',$token->getGraphemeIDs());
                addUpdateEntityReturnData('tok',$token->getID(),'value',$token->getValue());
                addUpdateEntityReturnData('tok',$token->getID(),'transcr',$token->getTranscription());
                addUpdateEntityReturnData('tok',$token->getID(),'syllableClusterIDs',$token->getSyllableClusterIDs());
                addUpdateEntityReturnData('tok',$token->getID(),'sort', $token->getSortCode());
                addUpdateEntityReturnData('tok',$token->getID(),'sort2', $token->getSortCode2());
              }
              //************ compounds *******************
              if (count($errors) == 0 && $oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID
                  && isset($compounds) && count($compounds) > 0) {//update compounds
                while (count($compounds) && $oldTokCmpGID != $newTokCmpGID) {
                  $compound = array_shift($compounds);
                  $componentIDs = $compound->getComponentIDs();
                  $tokCmpIndex = array_search($oldTokCmpGID,$componentIDs);
                  array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID);
                  $oldTokCmpGID = $compound->getGlobalID();
                  if ($compound->isReadonly()) {
                    $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
                  }
                  // update compound container
                  $compound->setComponentIDs($componentIDs);
                  $compound->getValue(true);//cause recalc
                  $compound->updateLocationLabel();
                  $compound->updateBaselineInfo();
                  $compound->save();
                  $newTokCmpGID = $compound->getGlobalID();
                  if ($compound->hasError()) {
                    array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
                  }else if ($oldTokCmpGID != $newTokCmpGID){
                    addNewEntityReturnData('cmp',$compound);
                    //addRemoveEntityReturnData(substr($oldTokCmpGID,0,3),substr($oldTokCmpGID,4));
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
            } else { // new token case
              $oldTokCmpGID = $token->getGlobalID();//become reference point for inserting new token
              $newToken = new Token();
              $newToken->setGraphemeIDs($newSylGraIDs);
              $token->setOwnerID($defOwnerID);
              $token->setVisibilityIDs($defVisIDs);
              if ($defAttrIDs){
                $token->setAttributionIDs($defAttrIDs);
              }
              $newToken->getValue(true);//cause recalc
              $newToken->save();
              $newTokCmpGID = $newToken->getGlobalID();
              if ($token->hasError()) {
                array_push($errors,"error cloning token '".$newToken->getValue()."' - ".$newToken->getErrors(true));
              } else {
                addNewEntityReturnData('tok',$newToken);
              }
            }
          }
          $oldTxtDivSeqGID = null;
          //******************** text division sequence ****************
          if (count($errors) == 0 && $oldTokCmpGID && $oldTokCmpGID != $newTokCmpGID) {//token or compound change to text division sequence
            $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
            $oldTxtDivSeqID = $textDivSeq->getID();
            if ($textDivSeq->isReadonly()) {
              $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
            }
            // update text dividion components ids by replacing $oldTokCmpGID with $newTokCmpGID
            $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
            $tokCmpIndex = array_search($oldTokCmpGID,$textDivSeqEntityIDs);
            if ($needsSeparateToken) { // adjust index for insertion point
              if ($insPos != 'before') {//insert after so increment index
                if ($tokCmpIndex !== false) {
                  $tokCmpIndex++;
                }
              }
              array_splice($textDivSeqEntityIDs,$tokCmpIndex,0,$newTokCmpGID);
            } else { // exchange old for new
              array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmpGID);
            }
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
              invalidateCachedSeqEntities($textDivSeq->getID(), $edition->getID());
              addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
            }
          }
        }
        //******************** text sequence ****************
        if (count($errors) == 0 && $oldTxtDivSeqGID && $oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so update container
          //clone text sequence if not owned
          if ($seqText->isReadonly()) {
            $oldTextSeqID = $seqText->getID();
            $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
          }
          $textSeqEntityIDs = $seqText->getEntityIDs();
          $txtDivSeqIndex = array_search($oldTxtDivSeqGID,$textSeqEntityIDs);
          array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID);
          $seqText->setEntityIDs($textSeqEntityIDs);
          //save text sequence
          $seqText->save();
          if ($seqText->hasError()) {
            array_push($errors,"error updating text sequence '".$seqText->getLabel()."' - ".$seqText->getErrors(true));
          }else if ($oldTextSeqID){//cloned so it's new
            addNewEntityReturnData('seq',$seqText);//**********new seq
            //addRemoveEntityReturnData('seq',$oldTextSeqID);
          }else { // only updated
            addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
          }
        }
        //******************** edition *********************
        if (count($errors) == 0 && $edition) {
          //touch edition for synch code
          $edition->storeScratchProperty("lastModified",$edition->getModified());
          $edition->setStatus('changed');
          // update edition if sequences cloned
          if (count($errors) == 0 && ($oldPhysSeqID || $oldTextSeqID)) {
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
          invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
          invalidateCachedEditionViewerHtml($edition->getID());
          if ($edition->hasError()) {
            array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
          }else{
            //array_push($updated,$edition);//********** updated edn
            addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
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
  ob_clean();
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }
  ?>
