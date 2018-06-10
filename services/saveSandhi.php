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
* saveSandhi
*
* saves sandhi  grapheme data and updates models depending on implied command add, remove or update
*
** }
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
require_once (dirname(__FILE__) . '/clientDataUtils.php');
$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();
$tokenSplitRequired = false;
$ednOwnerID = null;
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
  array_push($errors,"invalid json data - decode failed");
} else {
  $defAttrIDs = getUserDefAttrIDs();
  $defVisIDs = getUserDefVisibilityIDs();
  $defOwnerID = getUserDefEditorID();
  if ( isset($data['ednID'])) {//required
    $edition = new Edition($data['ednID']);
    if ($edition->hasError()) {
      array_push($errors,"creating edition - ".join(",",$edition->getErrors()));
    } else if ($edition->isReadonly()) {
      array_push($errors,"edition readonly");
    } else {
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
      $ednOwnerID = $edition->getOwnerID();
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
  $graTag = null;//required
  if ( isset($data['graTag'])) {//get grapheme tag
    $graTag = $data['graTag'];
    $graID = substr($graTag,3);
  } else {
    array_push($errors,"missing parameter for save sandhi ");
  }
  $entTag = null;
  if ( isset($data['entTag'])) {//get reference Syllable or Token
    $entTag = $data['entTag'];
    $prefix = substr($entTag,0,3);
    $id = substr($entTag,3);
    $displayEntGID = $prefix.":".$id;
  }
  $decomp = null;//required
  if ( isset($data['decomp'])) {//get decomposition string
    $decomp = $data['decomp'];
    if (strlen($decomp)) {
      $matchCnt = preg_match("/([-‐]|[aāiīïüuūeēoō’l̥̄rṛṝ]+)(?:([\s-‐])([aāiīïüuūeēoō’l̥̄rṛṝ]+))?/",$decomp,$decompParts);
      if ($matchCnt == 1 && $decomp == array_shift($decompParts)) {
        if (count($decompParts) == 1) {
          $decompParts = array("",$decompParts[0],"");
        }
        $decomp = join(":",$decompParts);// create decomp sting of form tok1endvowel:sep:tok2startvowel
      } else {
        array_push($errors,"invalid parameter to save sandhi ");
      }
    }
  } else {
    array_push($errors,"missing parameter for save sandhi ");
  }
  $context = null;//required
  if ( isset($data['context'])) {//get context
    $context = $data['context'];
    $ctxLookup = array();
    foreach ($context as $entTag){
      $entPrefix = substr($entTag,0,3);
      $entID = substr($entTag,3);
      if (!array_key_exists($entPrefix,$ctxLookup)) {
        $ctxLookup[$entPrefix] = array($entID);
      } else {
        array_push($ctxLookup[$entPrefix],$entID);
      }
    }
    $strContext = join(" ",$context);
    if (!count($context)) {
      array_push($errors,"invalid parameter to save sandhi ");
    }
  } else {
    array_push($errors,"missing parameter for save sandhi ");
  }
  $tokIDs = null;
  if ( isset($data['tokIDs'])) {//get token ID (s) if passed
    $tokIDs = $data['tokIDs'];
  }
}

//load grapheme and determine command
$cmd = null;
$origDecomp = null;
if (count($errors) == 0 && $graID && $decomp !== null) {
  $grapheme = new Grapheme($graID);
  if ($grapheme->hasError()) {
    array_push($errors,"error opening grapheme '".$grapheme->getValue()."' - ".$grapheme->getErrors(true));
  } else {//determine command
    $origDecomp = $grapheme->getDecomposition();
    if (count($decomp) > 0 && !$origDecomp) {//grapheme doesn't have decomp so add the passed in decomp
      $cmd = "add";
    } else if ($decomp !== $origDecomp) {
      if (strlen($origDecomp) > 0 && strlen($decomp) == 0) {//grapheme has decomp and need to set it to null
        $cmd = 'remove';
      }else if (count($origDecomp) > 0 && count($decomp)>0) {//grapheme has decomp and need to change it to
        $cmd = 'change';
      }
    } else {
      $cmd = "NOP";//original same as new so nothing to do
    }
  }
}

//find token(s) and validate commands
$token1 = $token2 = null;
if (count($errors) == 0 && $graID && $cmd && $cmd != "NOP" ) {
  if ( $cmd == "add" && $prefix && $prefix == 'tok') {//reference entity is tok
    $token1 = new Token($id);
  } else if ($tokIDs && is_array($tokIDs) && count($tokIDs) > 0) {//called with passed token id(s)
    $token1 = new Token($tokIDs[0]);
    if (count($tokIDs) >1) {
      $token2 = new Token($tokIDs[1]);
    }
  } else if ( $cmd == "add" && array_key_exists("tok",$ctxLookup)) {
    $tokID = $ctxLookup["tok"][0];
    $token1 = new Token($tokID);
  } else {//find all edition tokens expensive!!
    $edTokIDs = array();
    foreach($seqText->getEntities(true) as $textDivSeq) {
      foreach($textDivSeq->getEntityIDs() as $gid) {
        if (strpos($gid,'cmp') === 0) {
          $compound = new Compound(substr($gid,4));
          foreach ($compound->getTokens() as $token) {
            array_push($edTokIDs,$token->getID());
          }
        } else {
          array_push($edTokIDs,substr($gid,4));
        }
      }
    }
    //remove any duplicates, TODO consider if there is a case for duplicates
    $edTokIDs = array_unique($edTokIDs);
    //find tokens with grapheme
    $tokens = new Tokens("$graID = ANY(\"tok_grapheme_ids\")");
    //get tokens that are in edition
    foreach ($tokens as $token) {
      if (in_array($token->getID(),$edTokIDs)) { //token is in edition
        if (!$token1) {
          $token1 = $token;
        } else if (!$token2) {
          $token2 = $token;
          break; //stop after 2 - should never be more than 2 in the same edition
        }
      }
    }
  }
  // add command must have $token1 and grapheme cannot be at begin or end
  if ($cmd == "add" && $token1) {
    //get token1 graphemeIDs and find position of target grapheme
    $tokGraIDs = $token1->getGraphemeIDs();
    $graIndex = array_search($graID,$tokGraIDs);
    if ($graIndex == 0 || $graIndex == count($tokGraIDs)-1) {
      array_push($errors,"error adding sandhi at edge of word not supported for graID $graID grapheme ".$grapheme->getValue()." for ".$token1->getValue());
    }
  } else if (($cmd == "remove" || $cmd == "change") && $token1 && $token2){
    // remove or change  command must have 2 tokens in order with shared grapheme at beginning and end
    $firstToken = $secToken = null;
    //get token1 graphemeIDs and find position of target grapheme (should be last)
    $tokGraIDs = $token1->getGraphemeIDs();
    $graIndex = array_search($graID,$tokGraIDs);
    if ($graIndex == 0) {
      $secToken = $token1;
    } else if ($graIndex == count($tokGraIDs)-1) {
      $firstToken = $token1;
    } else {
      array_push($errors,"error changing sandhi grapheme should be at edge of word for graID $graID grapheme ".$grapheme->getValue()."is index $graIndex of ".$token1->getValue());
    }
    //get token2 graphemeIDs and find position of target grapheme (should be first could be swapped)
    $tokGraIDs = $token2->getGraphemeIDs();
    $graIndex = array_search($graID,$tokGraIDs);
    if ($graIndex == 0 && !$secToken) {
      $secToken = $token2;
    } else if ($graIndex == count($tokGraIDs)-1 && ! $firstToken) {
      $firstToken = $token2;
    } else {
      array_push($errors,"error changing sandhi grapheme should be at edge of word for graID $graID grapheme ".$grapheme->getValue()."is index $graIndex of ".$token2->getValue());
    }
    $token1 = $firstToken;
    $token2 = $secToken;
    if (!$token1 || !$token2) {
      array_push($errors,"error missing token information for graID $graID with command $cmd");
    }
  } else {
    array_push($errors,"error missing token information for graID $graID");
  }
  if ($cmd == "change") {
    // determine change type vowel, to toksep or to cmpsep
    // compare graDecomp with decomp separator
    $origDecompParts = explode(":",$origDecomp);
    $typeChange = null;
    if ($decompParts[1] == "-" || $decompParts[1] == "‐" ) {
      if ($origDecompParts[1] == " ") {
        $typeChange = "2cmpsep";
      } else {
        $typeChange = "vowelchng";
      }
    } else if ($decompParts[1] == " " ) {
      if ($origDecompParts[1] == "-" || $origDecompParts[1] == "‐" ) {
        $typeChange = "2toksep";
      } else {
        $typeChange = "vowelchng";
      }
    }
  }
}

//modify grapheme and syllable (with physical line) if needed
if (count($errors) == 0 && $grapheme && $cmd && $cmd != "NOP") {
  $oldPhysLineSeqGID = null;
  $newGraIDs = array();
  if ($grapheme->isReadonly()){
    $syllable = null;
    //find syllable
    if ( $prefix && $prefix == 'scl') {//reference is syllable
      $syllable = new SyllableCluster($id);
    } else if ( array_key_exists('scl',$ctxLookup)) {
      $sclID = $ctxLookup['scl'][0];
      $syllable = new SyllableCluster($sclID);
    } else { // find scl with graID
      $syllables = new SyllableClusters("$graID = Any(\"scl_grapheme_ids\")",null,null,null);
      if ($syllables->getError()) {
        array_push($errors,"error opening syllable for grapheme ID $graID - ".$syllables->getError());
      } else if ($syllables->getCount() != 1) {
        array_push($errors,"error invalid syllable count for grapheme ID $graID - ".$syllables->getCount());
      } else {
        $syllable = $syllables->current();
      }
    }
    if ($syllable) {
      //get syllable info for cloning
      $origSclGID = $syllable->getGlobalID();
      $origSyllableGraIDs = $syllable->getGraphemeIDs();
      $origSyllableGraphemes = $syllable->getGraphemes(true);
      //clone syllable  TODO determine if case for not cloning syllable (owned with readonly grapheme)
      $clonedSyllable = $syllable->cloneEntity($defAttrIDs,$defVisIDs);
      $newSyllableID = $clonedSyllable->getID();
      //clone graphemes
      foreach ($origSyllableGraphemes as $grapheme) {//copy all grapheme and set decomp for clone of ref grapheme
        $newGrapheme = $grapheme->cloneEntity($defAttrIDs,$defVisIDs);
        if ($graID == $grapheme->getID()) {//set the decomposition of the sandhi grapheme
          $newGrapheme->setDecomposition($decomp);
        }
        $newGrapheme->save();
        addNewEntityReturnData('gra',$newGrapheme);
        addUpdateEntityReturnData('gra',$newGrapheme->getID(),'sclID',$newSyllableID);
        if ($graID == $grapheme->getID()) {//capture the sandhi graID
          $sandhiGraID = $newGrapheme->getID();
        }
        //todo add check for failure code
        array_push($newGraIDs,$newGrapheme->getID());
      }
      //update cloned scl with new graphemes
      $clonedSyllable->setGraphemeIDs($newGraIDs);
      $clonedSyllable->save();
      addNewEntityReturnData('scl',$clonedSyllable);
      $newSclID = $clonedSyllable->getID();
      foreach ($newGraIDs as $newSylGraID) {
        addUpdateEntityReturnData('gra',$newSylGraID,'sclID',$newSclID);
      }
      //update physical line seq with cloned scl
      $physLineSeq = null;
      foreach($seqPhys->getEntities(true) as $edPhysLineSeq) {
        if (count($edPhysLineSeq->getEntityIDs()) && in_array($origSclGID,$edPhysLineSeq->getEntityIDs())) {
          $physLineSeq = $edPhysLineSeq;
          break;
        }
      }
      if ($physLineSeq) {
        if ($physLineSeq->isReadonly()) {
          $oldPhysLineSeqGID = $physLineSeq->getGlobalID();
          $physLineSeq = $physLineSeq->cloneEntity($defAttrIDs,$defVisIDs);
        }
        $physLineSeqEntityIDs = $physLineSeq->getEntityIDs();
        $sclIndex = array_search($origSclGID,$physLineSeqEntityIDs);
        array_splice($physLineSeqEntityIDs,$sclIndex,1,$clonedSyllable->getGlobalID());
        $physLineSeq->setEntityIDs($physLineSeqEntityIDs);
        //signal display entity change
        if ($origSclGID == $displayEntGID) {//displayed syllable has changed
          $retVal["displayEntGID"] = $clonedSyllable->getGlobalID();
        }
        //save physical line sequence
        $physLineSeq->save();
        $newPhysLineSeqGID = $physLineSeq->getGlobalID();
        if ($physLineSeq->hasError()) {
          array_push($errors,"error updating physical line sequence '".$physLineSeq->getLabel()."' - ".$physLineSeq->getErrors(true));
        } else if ($oldPhysLineSeqGID){ // cloned
          addNewEntityReturnData('seq',$physLineSeq); //??altered physline parameter??
        } else { // only updated
          addUpdateEntityReturnData('seq',$physLineSeq->getID(),'entityIDs',$physLineSeq->getEntityIDs());
        }
      }
    } else {
      array_push($errors,"error unable to find syllable for readonly grapheme ID $graID - cannot save sandhi information");
    }
  } else {// save decomp
    $grapheme->setDecomposition($decomp);
    $grapheme->save();
    if ($grapheme->hasError()) {
      array_push($errors,"error updating sandhi for '".$grapheme->getLabel()."' - ".$grapheme->getErrors(true));
    }else { // only updated
      $sandhiGraID = $grapheme->getID();
      if ($cmd == "remove") {
        addRemovePropertyReturnData('gra',$grapheme->getID(),'decomp');
      } else {
        addUpdateEntityReturnData('gra',$grapheme->getID(),'decomp',preg_replace("/\:/",'',$grapheme->getDecomposition()));
      }
      if ($token1) {
        if ($cmd == "update") {
          $token1->getValue(true);
          $token1->save();
          addUpdateEntityReturnData('tok',$token1->getID(),'value',$token1->getValue());
          addUpdateEntityReturnData('tok',$token1->getID(),'transcr',$token1->getTranscription());
          addUpdateEntityReturnData('tok',$token1->getID(),'graphemeIDs',$token1->getGraphemeIDs());
          addUpdateEntityReturnData('tok',$token1->getID(),'sort',$token1->getSortCode());
          addUpdateEntityReturnData('tok',$token1->getID(),'sort2',$token1->getSortCode2());
        }
      }
      if ($token2) {
        if ($cmd == "update") {
          $token2->getValue(true);
          $token2->save();
          addUpdateEntityReturnData('tok',$token2->getID(),'value',$token2->getValue());
          addUpdateEntityReturnData('tok',$token2->getID(),'transcr',$token2->getTranscription());
          addUpdateEntityReturnData('tok',$token2->getID(),'graphemeIDs',$token2->getGraphemeIDs());
          addUpdateEntityReturnData('tok',$token2->getID(),'sort',$token2->getSortCode());
          addUpdateEntityReturnData('tok',$token2->getID(),'sort2',$token2->getSortCode2());
        }
      }
    }
  }
}

// adjust tokens depending on command
//for remove we need to add graphemes of token 2 to token1 (excluding shared grapheme) using new graphemes if syllable cloned
if ( count($errors) == 0 && $cmd == "remove" && $token1 && $token2) {
  //capture GID so we can find where to update heirarchy
  $replaceTokCmpGID = $token1->getGlobalID();
  if ($token1->isReadonly()) {
    $token1 = $token1->cloneEntity($defAttrIDs,$defVisIDs);
  }
  if (count($newGraIDs)>0){ // the original syllable was cloned so transfer the new graphemeID
    $tokGraIDs = $token1->getGraphemeIDs();
    foreach ($origSyllableGraIDs as $origGraID) {
      $newTokGraID = array_shift($newGraIDs);
      $tokGraIndex = array_search($origGraID,$tokGraIDs);
      if ($tokGraIndex !== false) {
        array_splice($tokGraIDs,$tokGraIndex,1,$newTokGraID);
      } else {
        array_push($warnings,"warning updating graphemes for token skipping graID $origGraID, not found in token");
      }
    }
    $token1->setGraphemeIDs($tokGraIDs);
    $token1->save();
  }
  // combine graphemes of token1 with token2
  $tokGraIDs = $token1->getGraphemeIDs();
  $tok2GraIDs = $token2->getGraphemeIDs();
  $tok2FirstGraID = array_shift($tok2GraIDs);
  if ($tok2FirstGraID != $sandhiGraID){// warn here instead of reverting
    array_push($warnings,"warning remove: unexpected grapheme at beginning of second token $tok2FirstGraID not equal to sandhi $sandhiGraID");
  }
  //split graphemes and share sandhi vowel
  $tokGraIDs = array_merge($tokGraIDs,$tok2GraIDs);
  $tokSandhiGraIndex = array_search($sandhiGraID,$tokGraIDs);
  $token1->setGraphemeIDs($tokGraIDs);
  $token1->getValue(true);
  $token1->save();
  addRemoveEntityReturnData('tok',$token2);
  $removeTokGID = $token2->getGlobalID();
  if (!$token2->isReadonly()) {
    $token2->markForDelete();
  }
  //signal display entity change
  if ($replaceTokCmpGID != $token1->getGlobalID()) {//cloned
    addNewEntityReturnData('tok',$token1);
    $newTokCmpGID = $token1->getGlobalID();
  } else {
    $replaceTokCmpGID = null;
    addUpdateEntityReturnData('tok',$token1->getID(),'graphemeIDs',$token1->getGraphemeIDs());
    addUpdateEntityReturnData('tok',$token1->getID(),'value',$token1->getValue());
    addUpdateEntityReturnData('tok',$token1->getID(),'transcr',$token1->getTranscription());
    addUpdateEntityReturnData('tok',$token1->getID(),'sort',$token1->getSortCode());
    addUpdateEntityReturnData('tok',$token1->getID(),'sort2',$token1->getSortCode2());
    addUpdateEntityReturnData('tok',$token1->getID(),'syllableClusterIDs',$token1->getSyllableClusterIDs());
  }

  //update containment hierarchy
  $oldTextDivSeqGID = null;
  $newTextDivSeqGID = null;
  //check for compound containers and update as needed
  if (count($errors)==0 && array_key_exists('cmp',$ctxLookup)) {// compounds so change GIDs
    $cmpIDs = $ctxLookup['cmp'];
    while (count($cmpIDs) && ($replaceTokCmpGID || $removeTokGID)) {
      $cmpID = array_pop($cmpIDs);
      $compound = new Compound($cmpID);
      $componentGIDs = $compound->getComponentIDs();
      if ($removeTokGID) {
        $removeTokIndex = array_search($removeTokGID,$componentGIDs);
        if ($removeTokIndex !== false) {
          array_splice($componentGIDs,$removeTokIndex,1);
          $removeTokGID = null;
        }
      }
      if (count($componentGIDs) == 1) {//can remove compound level
        if (!$compound->isReadonly()) {
          $compound = $compound->markForDelete();
        }
        addRemoveEntityReturnData('cmp',$compound);
        $replaceTokCmpGID = "cmp:$cmpID";
        $newTokCmpGID = $componentGIDs[0];
        continue;
      }
      if ($compound->isReadonly()) {
        $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $replaceTokCmpIndex = array_search($replaceTokCmpGID,$componentGIDs);
      if ($replaceTokCmpIndex !== false) {
        array_splice($componentGIDs,$replaceTokCmpIndex,1,$newTokCmpGID);
        $compound->setComponentIDs($componentGIDs);
        $compound->getValue(true);//cause recalc
        $compound->save();
        if ($compound->hasError()) {
          array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
          break;
        } else if ($cmpID != $compound->getID()) { // clone so pragate change
          $replaceTokCmpGID = "cmp:$cmpID";
          $newTokCmpGID = $compound->getGlobalID();
          addNewEntityReturnData('cmp',$compound);
        } else {
          $newTokCmpGID = $replaceTokCmpGID = null;
          addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
          addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
          addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
          addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
          addUpdateEntityReturnData('cmp',$compound->getID(),'sort',$compound->getSortCode());
          addUpdateEntityReturnData('cmp',$compound->getID(),'sort2',$compound->getSortCode2());
        }
      } else {
        array_push($errors,"error unable to find $replaceTokCmpGID in components of '".$compound->getValue());
        $replaceTokCmpGID = null;
      }
    }
  }
  //check textDiv and update as needed
  if (count($errors)==0 && ($replaceTokCmpGID && $newTokCmpGID || $removeTokGID)) {
    $textDivSeq = null;
    //find textDivSeq
    if (array_key_exists('seq',$ctxLookup)) {
      $textDivSeqID = $ctxLookup['seq'][0];
      $textDivSeq = new Sequence($textDivSeqID);
    } else {
      foreach($seqText->getEntities(true) as $edTextDivSeq) {
        if (in_array($origTok1GID,$edTextDivSeq->getEntityIDs())) {
          $textDivSeq = $edTextDivSeq;
          break;
        }
      }
    }
    //update textDivSeq
    if ($textDivSeq) {
      if ($textDivSeq->isReadonly()) {//clone it
        $oldTextDivSeqGID = $textDivSeq->getGlobalID();
        $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
      if ($removeTokGID) {
        $removeTokIndex = array_search($removeTokGID,$textDivSeqEntityIDs);
        if ($removeTokIndex !== false) {
          array_splice($textDivSeqEntityIDs,$removeTokIndex,1);
          $removeTokGID = null;
        }
      }
      if ($replaceTokCmpGID) {
        $replaceTokCmpIndex = array_search($replaceTokCmpGID,$textDivSeqEntityIDs);
        if ($replaceTokCmpIndex !== false) {
          array_splice($textDivSeqEntityIDs,$replaceTokCmpIndex,1,$newTokCmpGID);
        }
      }
      $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
      $textDivSeq->save();
      $newTextDivSeqGID = $textDivSeq->getGlobalID();
      if ($textDivSeq->hasError()) {
        array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
      } else if ($oldTextDivSeqGID){ // cloned
        addNewEntityReturnData('seq',$textDivSeq); //??altered physline parameter??
        $retVal['alteredTextDivSeqID'] = $textDivSeq->getID();
      } else { // only updated
        invalidateCachedSeq($textDivSeq->getID(),$ednOwnerID);
        addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
      }
    }
  }

  // update Text (Text Division Container) Sequence if needed
  if (count($errors) == 0 && $oldTextDivSeqGID && $oldTextDivSeqGID != $newTextDivSeqGID){//cloned so update container
    //clone text sequence if not owned
    if ($seqText->isReadonly()) {
      $oldTextSeqID = $seqText->getID();
      $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
    }
    $textSeqEntityIDs = $seqText->getEntityIDs();
    $txtDivSeqIndex = array_search($oldTextDivSeqGID,$textSeqEntityIDs);
    if ($txtDivSeqIndex) {
      array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTextDivSeqGID);
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
  }
}
//for add we need to split token using new graphemes if syllable cloned
if ( count($errors) == 0 && $cmd == "add" && $token1) {
  //capture GID so we can find where to update heirarchy
  $replaceTokCmpGID = $token1->getGlobalID();
  if ($token1->isReadonly()) {
    $token1 = $token1->cloneEntity($defAttrIDs,$defVisIDs);
  }
  if (count($newGraIDs)>0){ // the original syllable was cloned so transfer the new graphemeID
    $tokGraIDs = $token1->getGraphemeIDs();
    foreach ($origSyllableGraIDs as $origGraID) {
      $newTokGraID = array_shift($newGraIDs);
      $tokGraIndex = array_search($origGraID,$tokGraIDs);
      if ($tokGraIndex !== false) {
        array_splice($tokGraIDs,$tokGraIndex,1,$newTokGraID);
      } else {
        array_push($warnings,"warning updating graphemes for token skipping graID $origGraID, not found in token");
      }
    }
    $token1->setGraphemeIDs($tokGraIDs);
    $token1->save();
  }
  // create split token by cloning token1
  $token2 = $token1->cloneEntity($defAttrIDs,$defVisIDs);
  //split graphemes and share sandhi vowel
  $tokGraIDs = $token1->getGraphemeIDs();
  $tokSandhiGraIndex = array_search($sandhiGraID,$tokGraIDs);
  $token1->setGraphemeIDs(array_slice($tokGraIDs,0,$tokSandhiGraIndex+1));
  $token2->setGraphemeIDs(array_slice($tokGraIDs,$tokSandhiGraIndex));
  $token1->getValue(true);
  $token2->getValue(true);
  $token1->save();
  $token2->save();
  addNewEntityReturnData('tok',$token1);
  addNewEntityReturnData('tok',$token2);
  $replacementTokCmpGIDs = array($token1->getGlobalID(), $token2->getGlobalID());
  //signal display entity change
  if ($replaceTokCmpGID == $displayEntGID) {//displayed entity has changed
    $retVal["displayEntGID"] = $token1->getGlobalID();
  }//TODO consider whether a recalc and redraw of the lines is needed

  $prependTokGID4TextDiv = null;
  $appendTokGID4TextDiv = null;
  $topLevelCmpGID = null;
  //check for compound creation
  if ($decompParts[1] != " ") {//create compound for split token
    $createCmp = new Compound();
    $createCmp->setComponentIDs($replacementTokCmpGIDs);
    $createCmp->getValue(true);//cause recalc
    $createCmp->setOwnerID($defOwnerID);
    $createCmp->setVisibilityIDs($defVisIDs);
    if ($defAttrIDs){
      $createCmp->setAttributionIDs($defAttrIDs);
    }
    $createCmp->save();
    if ($createCmp->hasError()) {
      array_push($errors,"error creating compound '".$createCmp->getValue()."' - ".$createCmp->getErrors(true));
    } else {
      $replacementTokCmpGIDs = array($createCmp->getGlobalID());
      addNewEntityReturnData('cmp',$createCmp);
      //signal display entity change
      if ($replaceTokCmpGID == $displayEntGID) {//displayed entity has changed
        $retVal["displayEntGID"] = $createCmp->getGlobalID();
      }
    }
  } else if (array_key_exists('cmp',$ctxLookup)) {//check if tokGID is at edge of compound for split token case
    $topLevelCmp = new Compound($ctxLookup['cmp'][0]);
    $topLevelCmpGID = $topLevelCmp->getGlobalID();
    $allTokIDs = $topLevelCmp->getTokenIDs();
    $replaceTokIndex = array_search(substr($replaceTokCmpGID,4),$allTokIDs);
    if ($replaceTokIndex == 0) {
      $prependTokGID4TextDiv = array_shift($replacementTokCmpGIDs);
    } else if ($replaceTokIndex == (count($allTokIDs)-1)) {
      $appendTokGID4TextDiv = array_pop($replacementTokCmpGIDs);
    } else {
      //error not covered now.
      array_push($errors,"error sandhi token breaks intra compound not supported");
    }
  }
  //update containment hierarchy
  $oldTextDivSeqGID = null;
  $newTextDivSeqGID = null;
  //check for compound containers and update as needed
  if (count($errors)==0 && array_key_exists('cmp',$ctxLookup)) {// compounds so change GIDs
    $cmpIDs = $ctxLookup['cmp'];
    while (count($cmpIDs) && $replaceTokCmpGID && $replacementTokCmpGIDs) {
      $cmpID = array_pop($cmpIDs);
      $compound = new Compound($cmpID);
      if ($compound->isReadonly()) {
        $compound = $compound->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $componentGIDs = $compound->getComponentIDs();
      $replaceTokCmpIndex = array_search($replaceTokCmpGID,$componentGIDs);
      if ($replaceTokCmpIndex !== false) {
        array_splice($componentGIDs,$replaceTokCmpIndex,1,$replacementTokCmpGIDs);
        $compound->setComponentIDs($componentGIDs);
        $compound->getValue(true);//cause recalc
        $compound->save();
        if ($compound->hasError()) {
          array_push($errors,"error updating compound clone '".$compound->getValue()."' - ".$compound->getErrors(true));
          break;
        } else if ($cmpID != $compound->getID()) { // clone so pragate change
          $replaceTokCmpGID = "cmp:$cmpID";
          $replacementTokCmpGIDs = array($compound->getGlobalID());
          addNewEntityReturnData('cmp',$compound);
        } else {
          $replacementTokCmpGIDs = $replaceTokCmpGID = null;
          addUpdateEntityReturnData('cmp',$compound->getID(),'entityIDs',$compound->getComponentIDs());
          addUpdateEntityReturnData('cmp',$compound->getID(),'tokenIDs',$compound->getTokenIDs());
          addUpdateEntityReturnData('cmp',$compound->getID(),'value',$compound->getValue());
          addUpdateEntityReturnData('cmp',$compound->getID(),'transcr',$compound->getTranscription());
        }
      } else {
        array_push($errors,"error unable to find $replaceTokCmpGID in components of '".$compound->getValue());
        $replacementTokCmpGIDs = $replaceTokCmpGID = null;
      }
    }
  }
  //we need to add unconnected token split to textDiv
  if (count($errors)==0 && ($prependTokGID4TextDiv || $appendTokGID4TextDiv) && $topLevelCmpGID) {
    //check for update of inner compound which means we need to add unconnected token split to textDiv
    if (!$replaceTokCmpGID) { //update completed within compounds so just insert the extra token before or after top level compound
      $replaceTokCmpGID = $topLevelCmpGID;
      if ($prependTokGID4TextDiv) {
        $replacementTokCmpGIDs = array($prependTokGID4TextDiv, $topLevelCmpGID);
      } else if ($appendTokGID4TextDiv) {
        $replacementTokCmpGIDs = array($topLevelCmpGID, $appendTokGID4TextDiv);
      }
    } else { //top level compound changed so need to include unconnected token with replacement GIDs
      if ($prependTokGID4TextDiv) {
        array_unshift($replacementTokCmpGIDs, $prependTokGID4TextDiv);
      } else if ($appendTokGID4TextDiv) {
        array_push($replacementTokCmpGIDs, $appendTokGID4TextDiv);
      }
    }
  }
  //check textDiv and update as needed
  if (count($errors)==0 && $replaceTokCmpGID && $replacementTokCmpGIDs) {
    $textDivSeq = null;
    //find textDivSeq
    if (array_key_exists('seq',$ctxLookup)) {
      $textDivSeqID = $ctxLookup['seq'][0];
      $textDivSeq = new Sequence($textDivSeqID);
    } else {
      foreach($seqText->getEntities(true) as $edTextDivSeq) {
        if (in_array($origTok1GID,$edTextDivSeq->getEntityIDs())) {
          $textDivSeq = $edTextDivSeq;
          break;
        }
      }
    }
    //update textDivSeq
    if ($textDivSeq) {
      if ($textDivSeq->isReadonly()) {//clone it
        $oldTextDivSeqGID = $textDivSeq->getGlobalID();
        $textDivSeq = $textDivSeq->cloneEntity($defAttrIDs,$defVisIDs);
      }
      $textDivSeqEntityIDs = $textDivSeq->getEntityIDs();
      $replaceTokCmpIndex = array_search($replaceTokCmpGID,$textDivSeqEntityIDs);
      if ($replaceTokCmpIndex !== false) {
        array_splice($textDivSeqEntityIDs,$replaceTokCmpIndex,1,$replacementTokCmpGIDs);
        $textDivSeq->setEntityIDs($textDivSeqEntityIDs);
        $textDivSeq->save();
        $newTextDivSeqGID = $textDivSeq->getGlobalID();
        if ($textDivSeq->hasError()) {
          array_push($errors,"error updating text division sequence '".$textDivSeq->getLabel()."' - ".$textDivSeq->getErrors(true));
        } else if ($oldTextDivSeqGID){ // cloned
          addNewEntityReturnData('seq',$textDivSeq); //??altered physline parameter??
          $retVal['alteredTextDivSeqID'] = $textDivSeq->getID();
        } else { // only updated
          invalidateCachedSeq($textDivSeq->getID(),$ednOwnerID);
          addUpdateEntityReturnData('seq',$textDivSeq->getID(),'entityIDs',$textDivSeq->getEntityIDs());
        }
      }
    }
  }

  // update Text (Text Division Container) Sequence if needed
  if (count($errors) == 0 && $oldTextDivSeqGID && $oldTextDivSeqGID != $newTextDivSeqGID){//cloned so update container
    //clone text sequence if not owned
    if ($seqText->isReadonly()) {
      $oldTextSeqID = $seqText->getID();
      $seqText = $seqText->cloneEntity($defAttrIDs,$defVisIDs);
    }
    $textSeqEntityIDs = $seqText->getEntityIDs();
    $txtDivSeqIndex = array_search($oldTextDivSeqGID,$textSeqEntityIDs);
    if ($txtDivSeqIndex) {
      array_splice($textSeqEntityIDs,$txtDivSeqIndex,1,$newTextDivSeqGID);
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
  }
}
//TODO check if there is a case for edition update.
//propagate change up containment hierarchy  TODO

if (count($errors) == 0 && $edition) {
  //touch edition for synch code
  $edition->storeScratchProperty("lastModified",$edition->getModified());
  $edition->save();
  invalidateCachedEdn($edition->getID(),$edition->getCatalogID());
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
