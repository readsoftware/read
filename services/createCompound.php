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
    * combines adjacent tokens/compounds into a compound or splits a token into a compound of 2 tokens
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
          if (!$seqPhys && $seqType == "TextPhysical"){//warning!!!! term dependency
            $seqPhys = $edSequence;
          }
          if (!$seqText && $seqType == "Text"){//warning!!!! term dependency
            $seqText = $edSequence;
          }
        }
      }
    } else {
      array_push($errors,"unaccessable edition");
    }
    $insPos = null;
    if ( isset($data['insPos'])) {//get alignment info
      $insPos = $data['insPos'];
    }
  }
  $healthLogging = false;
  if ( isset($data['hlthLog'])) {//check for health logging
    $healthLogging = true;
  }
  if (count($errors) == 0) {
    if (isset($data['context'])) {
      $context = $data['context'];
      while ($gid = array_pop($context)) {
        $id = substr($gid,3);
        switch (substr($gid,0,3)) {
          case "tok":
              $splitToken = new Token($id);
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
    if ( count($errors) == 0 && $splitToken ) {
      $tokGraIDs = $splitToken->getGraphemeIDs();
      if (is_numeric($insPos) && $insPos > -1 && $insPos < (count($tokGraIDs)-1)) {//todo  check for case of boundary and find adjacent token to use in compound
        $oldTokCmpGID = $splitToken->getGlobalID();
        if ($splitToken->isReadonly()){
          //clone token
          $splitToken = $splitToken->cloneEntity($defAttrIDs,$defVisIDs);
        }
        $newToken = $splitToken->cloneEntity($defAttrIDs,$defVisIDs);
        //divide up graphemes
        //if position is in range of token graphemes then split
        $splitTokGraIDs = array_splice($tokGraIDs,1+$insPos);
        $splitToken->setGraphemeIDs($tokGraIDs);
        $splitToken->getValue(true);//cause recalc
        $splitToken->save();
        $newSplitTokGID = $splitToken->getGlobalID();
        $newToken->setGraphemeIDs($splitTokGraIDs);
        $newToken->getValue(true);//cause recalc
        $newToken->save();
        $newTokGID = $newToken->getGlobalID();
        $tokCmpComponentGIDs = array($newSplitTokGID,$newTokGID);
        //**********new tok
        if ($splitToken->hasError()) {
          array_push($errors,"error cloning token '".$splitToken->getValue()."' - ".$splitToken->getErrors(true));
        } else if ($newToken->hasError()) {
          array_push($errors,"error cloning token '".$newToken->getValue()."' - ".$newToken->getErrors(true));
        } else if ($oldTokCmpGID != $newSplitTokGID) {
          addNewEntityReturnData('tok',$splitToken);
          addNewEntityReturnData('tok',$newToken);
        } else {
          addUpdateEntityReturnData('tok',$splitToken->getID(),'graphemeIDs',$splitToken->getGraphemeIDs());
          addUpdateEntityReturnData('tok',$splitToken->getID(),'value',$splitToken->getValue());
          addUpdateEntityReturnData('tok',$splitToken->getID(),'transcr',$splitToken->getTranscription());
          addUpdateEntityReturnData('tok',$splitToken->getID(),'syllableClusterIDs',$splitToken->getSyllableClusterIDs());
          addNewEntityReturnData('tok',$newToken);
        }
      } else { // error pos out of range
        array_push($errors,"error splitting token - position'".$insPos."' is out of range for ".$splitToken->getValue(true));
      }
      //create compound
      if (count($errors) == 0 && isset($tokCmpComponentGIDs) && count($tokCmpComponentGIDs) > 0) {//create compound
        $createCmp = new Compound();
        $createCmp->setComponentIDs($tokCmpComponentGIDs);
        $createCmp->getValue(true);//cause recalc
        $createCmp->setOwnerID($defOwnerID);
        $createCmp->setVisibilityIDs($defVisIDs);
        if ($defAttrIDs){
          $createCmp->setAttributionIDs($defAttrIDs);
        }
        $createCmp->save();
        $newTokCmpGID = $createCmp->getGlobalID();
        if ($createCmp->hasError()) {
          array_push($errors,"error creating compound '".$createCmp->getValue()."' - ".$createCmp->getErrors(true));
        } else {
          addNewEntityReturnData('cmp',$createCmp);
        }
      }
      //update compound heirarchy
      if (count($errors) == 0 && isset($compounds) && count($compounds) > 0) {//update compounds
        while (count($compounds) > 0) {
          $compound = array_shift($compounds);
          $componentIDs = null;
          if ($oldTokCmpGID != $newTokCmpGID) {
            $componentIDs = $compound->getComponentIDs();
            $tokCmpIndex = array_search($oldTokCmpGID,$componentIDs);
            array_splice($componentIDs,$tokCmpIndex,1,$newTokCmpGID);
          }
          $oldTokCmpGID = $compound->getGlobalID();
          if ($compound->isReadonly()) {
            $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
          }
          // update compound container
          if ($componentIDs) {
            $compound->setComponentIDs($componentIDs);
          }
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
          }
        }
      }
      if (count($errors) == 0 && $oldTokCmpGID != $newTokCmpGID) {//token or compound change to text division sequence
        $oldTxtDivSeqGID = $textDivSeq->getGlobalID();
        $oldTxtDivSeqID = $textDivSeq->getID();
        if ($textDivSeq->isReadonly()) {
          $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
        }
        // update text dividion components ids by replacing $oldTokCmpGID with $newTokCmpGID
        $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
        $tokCmpIndex = array_search($oldTokCmpGID,$textDivSeqEntityIDs);
        array_splice($textDivSeqEntityIDs,$tokCmpIndex,1,$newTokCmpGID);
        $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
        //send remove data for token or top level compound
        //addRemoveEntityReturnData(substr($oldTokCmpGID,0,3),substr($oldTokCmpGID,4));
        //save text division sequence
        $textDivSeq->save();
        $newTxtDivSeqGID = $textDivSeq->getGlobalID();
        if ($textDivSeq->hasError()) {
          array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
        }else if ($oldTxtDivSeqGID != $newTxtDivSeqGID){//cloned so it's new
          addNewEntityReturnData('seq',$textDivSeq);
          //addRemoveEntityReturnData('seq',$oldTxtDivSeqID);
          $retVal['alteredTextDivSeqID'] = $textDivSeq->getID();
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
        }else { // only updated
          //changed components on a cached sequence so invalidate cache to recalc on next refresh
          invalidateCachedSeq($textDivSeq->getID(),$ednOwnerID);
          addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
        }
      }
    }
    if (count($errors) == 0 && $edition) {
      //touch edition for synch code
      $edition->storeScratchProperty("lastModified",$edition->getModified());
      // update edition if sequences cloned
      if ($oldTextSeqID) {
        //get segIDs
        $edSeqIds = $edition->getSequenceIDs();
        //if phys changed update id

        if ($oldTextSeqID) {
          $seqIDIndex = array_search($oldTextSeqID,$edSeqIds);
          array_splice($edSeqIds,$seqIDIndex,1,$seqText->getID());
        }
        //update edition seqIDs
        $edition->setSequenceIDs($edSeqIds);
      }
      $edition->save();
      invalidateCachedEdn($edition->getID(),null,$edition->getCatalogID());
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
