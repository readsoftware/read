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
  * createCompound
  *
    * combines adjacent tokens adjusting the containment hierarchy as needed
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
    $healthLogging = false;
    if ( isset($data['hlthLog'])) {//check for health logging
      $healthLogging = true;
    }
  }

  //parse context data to get text division(s) and tokens to join
  if (count($errors) == 0) {
    if (isset($data['context']) && count ($data['context']) == 2 &&
        $data['context'][0] != $data['context'][1]) {
      $context1 = $data['context'][0];
      $textDivSeqTag1 = array_shift($context1);
      $context2 = $data['context'][1];
      $textDivSeqTag2 = array_shift($context2);
      if (substr($textDivSeqTag1,0,3) == 'seq') {
        $textDivSeq1 = new Sequence(substr($textDivSeqTag1,3));
        if ($textDivSeq1->hasError()) {
          array_push($errors,"error combining tokens trying to load sequence '".$textDivSeqTag1);
        } else {
          $origTextDivID1 = $textDivSeq1->getID();
        }
      } else {
        array_push($errors,"invalid context, expecting sequence tag and found $textDivSeqTag1");
      }
      if ($textDivSeqTag1 != $textDivSeqTag2) { //tokens/compounds are in different text divisions
        if (substr($textDivSeqTag2,0,3) == 'seq') {
          $textDivSeq2 = new Sequence(substr($textDivSeqTag2,3));
          if ($textDivSeq2->hasError()) {
            array_push($errors,"error combining tokens trying to load sequence '".$textDivSeq2);
          }
        } else {
          array_push($errors,"invalid context, expexting sequence tag and found $textDivSeqTag2");
        }
      }
      $tokenTag1 = array_pop($context1);
      $tokPrefix = substr($tokenTag1,0,3);
      if ( $tokPrefix == 'tok') {
        $token1 = new Token(substr($tokenTag1,3));
        if ($token1->hasError()) {
          array_push($errors,"error combining tokens trying to load token '".$token1->getValue()."' - ".$token1->getErrors(true));
        }
      } else {
        array_push($errors,"invalid context, expecting token tag and found $tokenTag1");
      }
      $tokenTag2 = array_pop($context2);
      $tokPrefix = substr($tokenTag2,0,3);
      if ( $tokPrefix == 'tok') {
        $token2 = new Token(substr($tokenTag2,3));
        if ($token2->hasError()) {
          array_push($errors,"error combining tokens trying to load token '".$token2->getValue()."' - ".$token2->getErrors(true));
        } else  if (!$token2->getID()) {
          array_push($errors,"error combining tokens trying to load token $tokenTag2.");
        } else {
          $tok2GraIDs = $token2->getGraphemeIDs();
          $token2GID = $token2->getGlobalID();
        }
      } else {
        array_push($errors,"invalid context, expecting sequence tag and found $tokenTag2");
      }
    } else {
      array_push($errors,"missing context - require 2 context arrays, one for each adjacent token");
    }
  }

  if (count($errors) == 0) {
    // process removal token
    $removeTokCmpGID = $token2GID; //setup to remove from TextDivSeq if not in compound
    if ($token2 && !$token2->isReadonly()) {
      $token2->markForDelete();
      addRemoveEntityReturnData(substr($token2GID,0,3),substr($token2GID,4));
    }
  }

  //update compound hierarchy of removed token
  if (count($errors) == 0 && count($context2) > 0) {
    while (count($context2) && ($removeTokCmpGID || $oldTokCmpGID2 != $newTokCmpGID2)) {
      $cmpTag = array_pop($context2);
      $compound = new Compound(substr($cmpTag,3));
      $componentIDs = $compound->getComponentIDs();
      if ($removeTokCmpGID) { //remove GID from parent
        $tokCmpIndex = array_search($removeTokCmpGID,$componentIDs);
        array_splice($componentIDs,$tokCmpIndex,1);
        $removeTokCmpGID = null;
        if (count($componentIDs) == 1) {//replacement case
          $oldTokCmpGID2 = $compound->getGlobalID();
          $newTokCmpGID2 = $componentIDs[0];
          if (!$compound->isReadonly()) {
            $compound->markForDelete();
            addRemoveEntityReturnData(substr($oldTokCmpGID2,0,3),substr($oldTokCmpGID2,4));
          }
          $cmpCtx1Index2 = array_search(preg_replace("/\:/","",$oldTokCmpGID2),$context1);
          if ($cmpCtx1Index2 !== false ) {
            array_splice($context1,$cmpCtx1Index2,1); // remove container GID from ctx1 since removed
          }
          continue;
        }
      } else {//propagate replacement
        $tokCmpIndex2 = array_search($oldTokCmpGID2,$componentIDs);
        array_splice($componentIDs,$tokCmpIndex2,1,$newTokCmpGID2);
        //update context1 also
        $cmpCtx1Index2 = array_search($oldTokCmpGID2,$context1);
        if ($cmpCtx1Index2 !== false ) {
          array_splice($context1,$cmpCtx1Index2,1,$newTokCmpGID2); // remove container GID from ctx1 since removed
        }
      }
      $oldTokCmpGID2 = $compound->getGlobalID();
      if ($compound->isReadonly()) {
        $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
      }
      // update compound container
      $compound->setComponentIDs($componentIDs);
      $compound->getValue(true);//cause recalc
      $compound->save();
      $newTokCmpGID2 = $compound->getGlobalID();
      $retVal["displayEntGID"] = $newTokCmpGID2;
      if ($compound->hasError()) {
        array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
      }else if ($oldTokCmpGID2 != $newTokCmpGID2){
        addNewEntityReturnData('cmp',$compound);
      }else {
        addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
      }
    }
  }

  //update text division of remove token if needed
  $separateTextDivisions = false;
  if (count($errors) == 0 && ($removeTokCmpGID || $oldTokCmpGID2 != $newTokCmpGID2)) {
    if ($textDivSeqTag2 == $textDivSeqTag1) {
      $textDivSeq2 = $textDivSeq1;
    } else {
      $separateTextDivisions = true;
    }
    $oldTxtDivSeqGID2 = $textDivSeq2->getGlobalID();
    $oldTxtDivSeqID2 = $textDivSeq2->getID();
    if ($textDivSeq2->isReadonly()) {
      $textDivSeq2 = $textDivSeq2->cloneEntity($defAttrIDs,$defVisIDs);
      if (!$separateTextDivisions) {
        $textDivSeq1 = $textDivSeq2;
      }
    }
    // update text dividion components ids by removing $removeTokCmpGID or replacing $oldTokCmpGID with $newTokCmpGID
    $textDivSeq2EntityIDs = $textDivSeq2->getEntityIDs();
    if ($removeTokCmpGID) { //remove GID from parent
      $tokCmpIndex = array_search($removeTokCmpGID,$textDivSeq2EntityIDs);
      array_splice($textDivSeq2EntityIDs,$tokCmpIndex,1);
      //signal handle case where last entity in this text div
    } else {//propagate replacement
      $tokCmpIndex = array_search($oldTokCmpGID2,$textDivSeq2EntityIDs);
      array_splice($textDivSeq2EntityIDs,$tokCmpIndex,1,$newTokCmpGID2);
    }
    $txtDiv2IsEmpty = (count($textDivSeq2EntityIDs) === 0);
    $textDivSeq2->setEntityIDs($textDivSeq2EntityIDs);
    //save text division sequence
    $textDivSeq2->save();
    $newTxtDivSeqGID2 = $textDivSeq2->getGlobalID();
    if ($textDivSeq2->hasError()) {
      array_push($errors,"error updating text division sequence '".$textDivSeq2->getLabel()."' - ".$textDivSeq2->getErrors(true));
    }else if ($oldTxtDivSeqGID2 != $newTxtDivSeqGID2) { // ||//cloned so it's new
      if ($txtDiv2IsEmpty) {//last entity removed (can't be same text div)
        addRemoveEntityReturnData('seq',$oldTxtDivSeqID2);
        if ($separateTextDivisions) {
          $oldTxtDivSeqID2->markForDelete();
          $alteredTextDivSeq2ID = $textDivSeq1->getID();
        }
      } else {
        addNewEntityReturnData('seq',$textDivSeq2);//if same will overwrite
        $alteredTextDivSeq2ID = $textDivSeq2->getID();
      }
      if (array_key_exists('alteredTextDivSeqIDs',$retVal)) {
        if (!array_key_exists($oldTxtDivSeqID2,$retVal['alteredTextDivSeqIDs'])) {
          $retVal['alteredTextDivSeqIDs'][$oldTxtDivSeqID2]= $alteredTextDivSeq2ID;
        }
      } else {
        $retVal['alteredTextDivSeqIDs'] = array();
        $retVal['alteredTextDivSeqIDs'][$oldTxtDivSeqID2]= $alteredTextDivSeq2ID;
      }
      //clone text sequence if not owned
      if ($seqText->isReadonly()) {
        $oldTextSeqID = $seqText->getID();
        $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $textSeqEntityIDs = $seqText->getEntityIDs();
      $txtDivSeqIndex = array_search($oldTxtDivSeqGID2,$textSeqEntityIDs);
      if ($txtDivSeqIndex !== false) {
        if ($txtDiv2IsEmpty) {//just remove
          array_splice($textSeqEntityIDs,$txtDivSeqIndex,1);
        } else {
          array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID2);
        }
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
      }
    } else { // only updated
      //changed components on a cached sequence so invalidate cache to recalc on next refresh
      invalidateCachedSeq($textDivSeq2->getID(),$ednOwnerID);
      if ($txtDiv2IsEmpty) {//last entity removed (can't be same text div)
        addRemoveEntityReturnData('seq',$oldTxtDivSeqID2);
        if ($separateTextDivisions) {
          $textDivSeq2->markForDelete();
        }
        //clone text sequence if not owned
        if ($seqText->isReadonly()) {
          $oldTextSeqID = $seqText->getID();
          $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
        }
        $textSeqEntityIDs = $seqText->getEntityIDs();
        $txtDivSeqIndex = array_search($oldTxtDivSeqGID2,$textSeqEntityIDs);
        if ($txtDivSeqIndex !== false) {
          array_splice($textSeqEntityIDs,$txtDivSeqIndex,1);
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
        }
      } else {
        addUpdateEntityReturnData('seq',$textDivSeq2->getID(),'entityIDs',$textDivSeq2->getEntityIDs());
      }
    }
  }

  //process receiving token
  if ( count($errors) == 0 ) {
    $oldTokCmpGID1 = $token1->getGlobalID();
    if ($token1->isReadonly()){
      //clone token
      $token1 = $token1->cloneEntity($defAttrIDs,$defVisIDs);
    }
    //combine graphemes
    $tokGraIDs = $token1->getGraphemeIDs();
    //remove any shared grapheme
    if ($tokGraIDs[count($tokGraIDs)-1] == $tok2GraIDs[0]) {//todo check this against sandhi
      array_shift($tok2GraIDs);
    }
    $tokGraIDs = array_merge($tokGraIDs, $tok2GraIDs);
    $token1->setGraphemeIDs($tokGraIDs);
    $token1->getValue(true);//cause recalc
    $token1->save();
    $newTokCmpGID1 = $token1->getGlobalID();
    $retVal["displayEntGID"] = $newTokCmpGID1;
    if ($token1->hasError()) {
      array_push($errors,"error changingreceiving token '".$token1->getValue()."' - ".$token1->getErrors(true));
    } else if ($oldTokCmpGID1 != $newTokCmpGID1) {
      addNewEntityReturnData('tok',$token1);
    } else {
      addUpdateEntityReturnData('tok',$token1->getID(),'graphemeIDs',$token1->getGraphemeIDs());
      addUpdateEntityReturnData('tok',$token1->getID(),'value',$token1->getValue());
      addUpdateEntityReturnData('tok',$token1->getID(),'transcr',$token1->getTranscription());
      addUpdateEntityReturnData('tok',$token1->getID(),'sort',$token1->getSortCode());
      addUpdateEntityReturnData('tok',$token1->getID(),'sort2',$token1->getSortCode2());
      addUpdateEntityReturnData('tok',$token1->getID(),'syllableClusterIDs',$token1->getSyllableClusterIDs());
    }
  }

  //update compound hierarchy of receiving token
  if (count($errors) == 0 && count($context1) > 0 && $oldTokCmpGID1 != $newTokCmpGID1) {
    while (count($context1) && $oldTokCmpGID1 != $newTokCmpGID1) {
      $cmpTag = array_pop($context1);
      $compound = new Compound(substr($cmpTag,3));
      $componentIDs = $compound->getComponentIDs();
      $tokCmpIndex = array_search($oldTokCmpGID1,$componentIDs);
      if ($tokCmpIndex !== false) {
        array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID1);
        $oldTokCmpGID1 = $compound->getGlobalID();
        if ($compound->isReadonly()) {
          $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
        }
        // update compound container
        $compound->setComponentIDs($componentIDs);
        $compound->getValue(true);//cause recalc
        $compound->save();
        $newTokCmpGID1 = $compound->getGlobalID();
        if ($compound->hasError()) {
          array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
        }else if ($oldTokCmpGID1 != $newTokCmpGID1){
          addNewEntityReturnData('cmp',$compound);
        }else {
          addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
          addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
          addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
          addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
        }
      }
    }
  }

  //update text division of receiving token if needed
  if (count($errors) == 0 && $oldTokCmpGID1 != $newTokCmpGID1) {
    $oldTxtDivSeqGID1 = $textDivSeq1->getGlobalID();
    if ($textDivSeq1->isReadonly()) {
      $textDivSeq1 = $textDivSeq1->cloneEntity($defAttrIDs,$defVisIDs);
    }
    // update text dividion components ids by replacing $oldTokCmpGID1 with $newTokCmpGID1
    $textDivSeq1EntityIDs = $textDivSeq1->getEntityIDs();
    $tokCmpIndex = array_search($oldTokCmpGID1,$textDivSeq1EntityIDs);
    array_splice($textDivSeq1EntityIDs,$tokCmpIndex,1,$newTokCmpGID1);
    $textDivSeq1->setEntityIDs($textDivSeq1EntityIDs);
    //save text division sequence
    $textDivSeq1->save();
    $newTxtDivSeqGID1 = $textDivSeq1->getGlobalID();
    if ($textDivSeq1->hasError()) {
      array_push($errors,"error updating text division sequence '".$textDivSeq1->getLabel()."' - ".$textDivSeq1->getErrors(true));
    }else if ($oldTxtDivSeqGID1 != $newTxtDivSeqGID1){//cloned so it's new
      addNewEntityReturnData('seq',$textDivSeq1);
      //clone text sequence if not owned
      if ($seqText->isReadonly()) {
        $oldTextSeqID = $seqText->getID();
        $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $textSeqEntityIDs = $seqText->getEntityIDs();
      $txtDivSeqIndex = array_search($oldTxtDivSeqGID1,$textSeqEntityIDs);
      array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTxtDivSeqGID1);
      $seqText->setEntityIDs($textSeqEntityIDs);
      //save text sequence
      $seqText->save();
      if ($seqText->hasError()) {
        array_push($errors,"error updating text sequence '".$seqText->getLabel()."' - ".$seqText->getErrors(true));
      }else if ($oldTextSeqID){//cloned so it's new
        addNewEntityReturnData('seq',$seqText);//**********new seq
      }else { // only updated
        addUpdateEntityReturnData('seq',$seqText->getID(),'entityIDs',$seqText->getEntityIDs());
      }
    } else if (!$separateTextDivisions && $oldTxtDivSeqGID2 != $newTxtDivSeqGID2){//case where it has changed and was cloned
      addNewEntityReturnData('seq',$textDivSeq1);
    }else { // only updated
       //changed components on a cached sequence so invalidate cache to recalc on next refresh
      invalidateCachedSeq($textDivSeq1->getID(),$ednOwnerID);
      addUpdateEntityReturnData('seq',$textDivSeq1->getID(),'entityIDs',$textDivSeq1->getEntityIDs());
    }
    if (array_key_exists('alteredTextDivSeqIDs',$retVal)) {
      if (!array_key_exists($origTextDivID1,$retVal['alteredTextDivSeqIDs'])) {
        $retVal['alteredTextDivSeqIDs'][$origTextDivID1]= $textDivSeq1->getID();
      }
    } else {
      $retVal['alteredTextDivSeqIDs'] = array();
      $retVal['alteredTextDivSeqIDs'][$origTextDivID1]= $textDivSeq1->getID();
    }
  }


  if (count($errors) == 0 && $edition) {
    //touch edition for synch code
    $edition->storeScratchProperty("lastModified",$edition->getModified());
    // update edition if text sequence cloned
    if ($oldTextSeqID) {
      //get segIDs
      $edSeqIds = $edition->getSequenceIDs();
      $seqIDIndex = array_search($oldTextSeqID,$edSeqIds);
      array_splice($edSeqIds,$seqIDIndex,1,$seqText->getID());
      //update edition seqIDs
      $edition->setSequenceIDs($edSeqIds);
    }
    $edition->save();
    invalidateCachedEdn($edition->getID());
    if ($edition->hasError()) {
      array_push($errors,"error updating edtion '".$edition->getDescription()."' - ".$edition->getErrors(true));
    }else{
      addUpdateEntityReturnData('edn',$edition->getID(),'seqIDs',$edition->getSequenceIDs());
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
