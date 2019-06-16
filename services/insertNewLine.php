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
  require_once (dirname(__FILE__) . '/clientDataUtils.php');
  $dbMgr = new DBManager();
  $retVal = array();
  $errors = array();
  $entities = array();
  $warnings = array();
  $ednOwnerID = null;
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):null);
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
    $refLineSeqGID = null;
    if ( isset($data['refLineSeqGID'])) {//get reference physical Line sequence GID
      $refLineSeqGID = $data['refLineSeqGID'];
    }
    $txtScl = null;
    if ( isset($data['txtScl'])) {//get text for Syllable NOT IMPLEMENTED YET
      $txtScl = $data['txtScl'];
    } else {
      $txtScl = '.';
    }
    $lnLabel = null;
    if ( isset($data['label'])) {//get label for new physical line sequence
      $lnLabel = $data['label'];
    }
    $refDivSeqID = null;
    if ( isset($data['refDivSeqID'])) {//get refence text division sequence ID
      $refDivSeqID = $data['refDivSeqID'];
    }
    $refEntGID = null;
    if ( isset($data['refEntGID'])) {//get refence entity GID
      $refEntGID = $data['refEntGID'];
    }
    $insPos = null;
    if ( isset($data['insPos'])) {//get alignment info
      $insPos = $data['insPos'];
    }
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }
  if (count($errors) == 0 && isset($seqText) && isset($seqPhys)) {
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
//        $char = mb_strtolower($char);
        $graphemeIsUpper = false;//uppercase Grapheme make it lower case to allow all caps for logograms
//        $graphemeIsUpper = ($testChar != $char);//uppercase Grapheme
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
    if (count($errors) == 0) {//parsed syllable with no errors so create new graphemes and new syllable
      //create new graphemes
      foreach ($parsedGraData as $graData) {
        if (@$prevTCM) {
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
      $newSclGID = $newSyllable->getGlobalID();
      $newSclID = $newSyllable->getID();
      foreach ($newSylGraIDs as $newSylGraID) {
        addUpdateEntityReturnData('gra',$newSylGraID,'sclID',$newSclID);
      }
      if ($newSyllable->hasError()) {
        array_push($errors,"error creating new syllable '".$newSyllable->getValue()."' - ".$newSyllable->getErrors(true));
      }else{
        //find insert index to calc line label if needed
        $physSeqEntityIDs = $seqPhys->getEntityIDs();
        //find index of refLineSeqGID in physical sequence
        $refSeqIndex = array_search($refLineSeqGID,$physSeqEntityIDs);
        if ($insPos == 'after') {// need to move to next GID for insert
          $refSeqIndex++;
        }
        //if no label create a default
        if (!$lnLabel) {
          $lnLabel = "NL".($refSeqIndex + 1);
        }
        // create new physical line seq
        $newPhysLine = new Sequence();
        $newPhysLine->setEntityIDs(array($newSclGID));
        $newPhysLine->setLabel($lnLabel);
        $newPhysLine->setTypeID(Entity::getIDofTermParentLabel('linephysical-textphysical'));//term dependency
        $newPhysLine->setOwnerID($defOwnerID);
        $newPhysLine->setVisibilityIDs($defVisIDs);
        if ($defAttrIDs){
          $newPhysLine->setAttributionIDs($defAttrIDs);
        }
        $newPhysLine->save();
        $retVal['physLineSeqGID'] = $newPhysLineSeqGID = $newPhysLine->getGlobalID();
        if ($newPhysLine->hasError()) {
          array_push($errors,"error creating physical line sequence '".$newPhysLine->getValue()."' - ".$newPhysLine->getErrors(true));
        } else {//insert into physycal sequence
          addNewEntityReturnData('seq',$newPhysLine);
          //update physical sequence
          if ($refSeqIndex == count($physSeqEntityIDs)) { //append
            array_push($physSeqEntityIDs,$newPhysLineSeqGID);
            $retVal['lastReplaced'] = true;
          } else { //splice
            array_splice($physSeqEntityIDs,$refSeqIndex,0,$newPhysLineSeqGID);
            if ($refSeqIndex == 0) {
              $retVal['firstReplaced'] = true;
            }
          }
          $retVal['newLineOrd'] = $refSeqIndex + 1;
          if ($seqPhys->isReadonly()) {
            $oldPhysSeqID = $seqPhys->getID();
            $seqPhys = $seqPhys->cloneEntity($defAttrIDs,$defVisIDs);
          }
          $seqPhys->setEntityIDs($physSeqEntityIDs);
          $seqPhys->save();
          if ($seqPhys->hasError()) {
            array_push($errors,"error updating physical sequence '".$seqPhys->getLabel()."' - ".$seqPhys->getErrors(true));
          }else if ($oldPhysSeqID){// return insert data
            addNewEntityReturnData('seq',$seqPhys);
            //addRemoveEntityReturnData('seq',$oldPhysSeqID);
          }else {
            addUpdateEntityReturnData('seq',$seqPhys->getID(),'entityIDs',$seqPhys->getEntityIDs());
          }
        }
      }
    }
    if (count($errors) == 0) {//updated physical line side, so handle token  text side now
      // create new token
      $newToken = new Token();
      $newToken->setGraphemeIDs($newSylGraIDs);
      $newToken->setOwnerID($defOwnerID);
      $newToken->setVisibilityIDs($defVisIDs);
      if ($defAttrIDs){
        $newToken->setAttributionIDs($defAttrIDs);
      }
      $newToken->save();
      addNewEntityReturnData('tok',$newToken);
      $retVal['tokGID'] = $newTokGID = $newToken->getGlobalID();
      if ($newToken->hasError()) {
        array_push($errors,"error creating new token '".$newToken->getValue()."' - ".$newToken->getErrors(true));
      } else { // find reference entity index for token insert
        $textDivSeq = new Sequence($refDivSeqID);
        $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
        $oldTxtDivSeqID = $textDivSeq->getID();
        if ($textDivSeq->isReadonly()) {
          $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
        }
        // update text dividion components ids by replacing $oldTokCmpGID with $newTokCmpGID
        $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
        $refEntIndex = array_search($refEntGID,$textDivSeqEntityIDs);
        if ($insPos == 'after') {// need to move to next GID for insert
          $refEntIndex++;
        }
        if ($refEntIndex == count($physSeqEntityIDs)) { //append
          array_push($textDivSeqEntityIDs,$newTokGID);
        } else { //splice
          array_splice($textDivSeqEntityIDs,$refEntIndex,0,$newTokGID);
        }
        $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
        //save text division sequence
        $textDivSeq->save();
        $retVal['textDivSeqGID'] = $newTxtDivSeqGID = $textDivSeq->getGlobalID();
        if ($textDivSeq->hasError()) {
          array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
        }else if ($oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so it's new
          addNewEntityReturnData('seq',$textDivSeq);
          //addRemoveEntityReturnData('seq',$oldTxtDivSeqID);
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
        } else { // only updated
          invalidateCachedSeqEntities($textDivSeq->getID(),$edition->getID());
          addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
        }
      }
    }
    if (count($errors) == 0 && $edition) {
      //touch edition for synch code
      $edition->storeScratchProperty("lastModified",$edition->getModified());
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
      invalidateCachedEditionViewerHtml($edition->getID());
      invalidateCachedViewerLemmaHtmlLookup(null,$edition->getID());
      if ($edition->hasError()) {
        array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
      }else{
        //array_push($updated,$edition);//********** updated edn
        addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
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
