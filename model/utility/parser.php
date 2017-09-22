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
  * Parser entity
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  require_once (dirname(__FILE__) . '/../../config.php');//get defines
  require_once (dirname(__FILE__) . '/graphemeCharacterMap.php');//get map for valid aksara
  require_once (dirname(__FILE__) . '/textCriticalMarks.php');//get TCM utilities
  require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../entities/Baseline.php');
  require_once (dirname(__FILE__) . '/../entities/Grapheme.php');
  require_once (dirname(__FILE__) . '/../entities/Segment.php');
  require_once (dirname(__FILE__) . '/../entities/Sequence.php');
  require_once (dirname(__FILE__) . '/../entities/Run.php');
  require_once (dirname(__FILE__) . '/../entities/Span.php');
  require_once (dirname(__FILE__) . '/../entities/Line.php');
  require_once (dirname(__FILE__) . '/../entities/SyllableCluster.php');
  require_once (dirname(__FILE__) . '/../entities/Token.php');
  require_once (dirname(__FILE__) . '/../entities/Terms.php');
  require_once (dirname(__FILE__) . '/../entities/Compound.php');
  require_once (dirname(__FILE__) . '/../entities/Edition.php');

/**
 * Parser encapsulates a set functions to parse an ancient script line into tokens,graphemes,
 * syllable clusters, segments and baseline
 *
 * <code>
 * require_once 'parser.php';
 *
 * $parser = new Parser("laïda·siṃgaṃsmiasaṃtraï");
 * $segments = $parser->getSegments();
 * echo $segments->current()->getStringPos(true);
 * </code>
 *
 * @author Stephen White  <stephenawhite57@gmail.com>
 */

class Parser {

  //*******************************PRIVATE MEMBERS************************************

  /**
  * private member variables
  * @access private
  */
  private   $_baselines,
            $_lines,
            $_spans,
            $_runs,
            $_segments,
            $_sequences,
            $_editions,
            $_syllableClusters,
            $_graphemes,
            $_tokens,
            $_compounds,
            $_errors,
            $_termLookups,
            $_sessUid,
            $_breakOnError,
            $_configs;

  //****************************CONSTRUCTOR FUNCTION***************************************

  /**
  * Create a polygon instance from a string of points with each point in the form of (x,y)
  * @param string|null with each point in the form of (x,y) or null
  * @access public
  */
  public function __construct( $configs = null ) {
    $this->_sessUid = dechex(substr(ceil(rand() * time()),0,9)).dechex(substr(ceil(rand() * time()),0,9));
    $this->_configs = $configs;
    $this->_baselines = array();
    $this->_graphemes = array();
    $this->_segments = array();
    $this->_sequences = array();
    $this->_editions = array();
    $this->_runs = array();
    $this->_spans = array();
    $this->_lines = array();
    $this->_syllableClusters = array();
    $this->_tokens = array();
    $this->_compounds = array();
    $this->_errors = array();
    // this code depends on the english terms
    //   SequenceType
    //   Analysis
    //   Chapter
    //   TextDivision
    //   Text
    //   TextPhysical
    //   LinePhysical
    //   TextStructure
    //   StructureDivision
    //   RootRefContainer
    //   RootRef
    //   Published
    //   Edition
    //   Transcription
    //   BaselineType
    $this->_breakOnError = false;
    $this->_termLookups = getTermInfoForLangCode('en');
    $this->_termLookups = $this->_termLookups['idByTerm_ParentLabel'];
  }

  //*******************************PUBLIC FUNCTIONS************************************

    /**
    * saveParseResults - saves parse entities fixing up any links need
    *
    * @return boolean true if successful parse, false otherwise
    */
    public function saveParseResults($dbMgr = null, $isAddInLine = false, $ignoreSeqTypeIDs = null) {
      $idLookup = array();
      if (!$dbMgr) {
        $dbMgr = new DBManager();
      }
      if ($isAddInLine && !$ignoreSeqTypeIDs) { //converting freetext and no ignore ids so default to ignore all
        $ignoreSeqTypeIDs = array(
          $this->_termLookups['analysis-sequencetype'],//warning!!! term dependency
          $this->_termLookups['chapter-analysis'],//warning!!! term dependency
          $this->_termLookups['rootref-textreference'],//warning!!! term dependency
          $this->_termLookups['textreference-sequencetype'],//warning!!! term dependency
          $this->_termLookups['textphysical-sequencetype'],//warning!!! term dependency
          $this->_termLookups['text-sequencetype']//warning!!! term dependency
        );
      }
      //      $dbMgr->startTransaction();
      //for each baseline save and make entry in blnID lookup
      foreach ($this->_baselines as $baseline){
        $nonce = $baseline->getScratchProperty("nonce");
        if (!$baseline->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$baseline->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $baseline->getID();
      }
      //for each segment fix up blnID, save and make entry in segID lookup
      foreach ($this->_segments as $segment){
        $nonce = $segment->getScratchProperty("nonce");
        $blnIDs = $segment->getBaselineIDs();
        $blnIDs[0] = $idLookup["bln_".$blnIDs[0]];
        $segment->setBaselineIDs($blnIDs);
        if (!$segment->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$segment->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $segment->getID();
      }
      //for each grapheme save and make entry in graID lookup
      foreach ($this->_graphemes as $grapheme){
        $nonce = $grapheme->getScratchProperty("nonce");
        if (!$grapheme->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$grapheme->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $grapheme->getID();
      }
      //for each run fix up segID and save
      foreach ($this->_runs as $run){
        $nonce = $run->getScratchProperty("nonce");
        if (!$run->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$run->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $run->getID();
      }
      //for each syllableCluster fix up segID and graIDs then save
      foreach ($this->_syllableClusters as $syllable){
        $nonce = $syllable->getScratchProperty("nonce");
        $segID = $syllable->getSegmentID();
        $syllable->setSegmentID($idLookup["seg_".$segID]);
        $tempGraIDs = $syllable->getGraphemeIDs();
        $graIDs = array();
        foreach ($tempGraIDs as $graID) {
          array_push($graIDs, $idLookup["gra_".$graID]);
        }
        $syllable->setGraphemeIDs($graIDs);
        $syllable->calculateSortCodes();
        if (!$syllable->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$syllable->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $syllable->getID();
      }
      //for each span fix up segIDs, save and make entry in spnID lookup
      foreach ($this->_spans as $span){
        $nonce = $span->getScratchProperty("nonce");
        $tempSegIDs = $span->getSegmentIDs();
        $segIDs = array();
        foreach ($tempSegIDs as $segID) {
          array_push($segIDs, $idLookup["seg_".$segID]);
        }
        $span->setSegmentIDs($segIDs);
        if (!$span->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$span->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $span->getID();
      }
      //for each line fix up spnIDs and save
      foreach ($this->_lines as $line){
        $nonce = $line->getScratchProperty("nonce");
        $tempSpnIDs = $line->getSpanIDs();
        $spnIDs = array();
        foreach ($tempSpnIDs as $spnID) {
          array_push($spnIDs, $idLookup["spn_".$spnID]);
        }
        $line->setSpanIDs($spnIDs);
        if (!$line->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$line->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
      }
      //for each token fix up graIDs, save and make entry in tokID lookup
      foreach ($this->_tokens as $token){
        $nonce = $token->getScratchProperty("nonce");
        $tempGraIDs = $token->getGraphemeIDs();
        $graIDs = array();
        foreach ($tempGraIDs as $graID) {
          array_push($graIDs, $idLookup["gra_".$graID]);
        }
        $token->setGraphemeIDs($graIDs);
        $token->calculateValues();
        if (!$token->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$token->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $token->getID();
      }
      //for each compound fix up tokIDs and save
      foreach ($this->_compounds as $compound){// todo modify this to handle compound of compounds
        $nonce = $compound->getScratchProperty("nonce");
        $tempTokIDs = $compound->getComponentIDs();
        $tokIDs = array();
        foreach ($tempTokIDs as $tokID) {
          array_push($tokIDs, "tok:".$idLookup["tok_".substr($tokID,4)]);
        }
        $compound->setComponentIDs($tokIDs);
        $compound->calculateValues();
        if (!$compound->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$compound->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $compound->getID();
      }
      // save each sequence without Entity IDs since a sequence can contain other sequences where temp IDs need to be translated
      foreach ($this->_sequences as $sequence){
        //if converting freetext then we ignore structural markers as the calling service will implement
        if ($isAddInLine && (in_array($sequence->getTypeID(), $ignoreSeqTypeIDs))) {
          continue;
        }
        $nonce = $sequence->getScratchProperty("nonce");
        if (!$sequence->save($dbMgr)){
          array_push($this->_errors,"unable to save ".$nonce." error - ".join("||",$token->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
        $idLookup[mb_strstr($nonce,"#",true)] = $sequence->getID();
      }
      //for each sequence fix up entity IDs, resave
      foreach ($this->_sequences as $sequence){
        if ($isAddInLine && (in_array($sequence->getTypeID(), $ignoreSeqTypeIDs))) {
          continue;
        }
        $tempEGIDs = $sequence->getEntityIDs();
        if (count($tempEGIDs) == 0) continue;
        $entityGIDs = array();
        foreach ($tempEGIDs as $tempEGID) {
          list($prefix,$id) = explode( ":", $tempEGID);
          if (!array_key_exists($prefix."_".$id,$idLookup)) {
            array_push($this->_errors,"unable to find entity id $tempEGID in lookup");
          }else{
            array_push($entityGIDs, $prefix.":".$idLookup[$prefix."_".$id]);
          }
        }
        $sequence->setEntityIDs($entityGIDs);
        if (!$sequence->save($dbMgr)){
          array_push($this->_errors,"unable to update token".$sequence->getID()." error - ".join("||",$sequence->getErrors()));
          if ($dbMgr->rollback()){
            array_push($this->_errors,"rollback complete");
          }else{
            array_push($this->_errors,"rollback error - ".$dbMgr->getError());
          }
          return false;
        }
      }
      if (!$isAddInLine && count($this->_editions)) {
        foreach ($this->_editions as $edition){
          $tempSeqIDs = $edition->getSequenceIDs();
          if (count($tempSeqIDs) == 0) continue;
          $sequenceIDs = array();
          foreach ($tempSeqIDs as $tempSeqID) {
            if (!array_key_exists("seq_".$tempSeqID,$idLookup)) {
              array_push($this->_errors,"unable to find temporary seq id $tempSeqID in lookup");
            }else{
              array_push($sequenceIDs, $idLookup["seq_".$tempSeqID]);
            }
          }
          $edition->setSequenceIDs($sequenceIDs);
          if (!$edition->save($dbMgr)){
            array_push($this->_errors,"unable to update token".$edition->getID()." error - ".join("||",$edition->getErrors()));
            if ($dbMgr->rollback()){
              array_push($this->_errors,"rollback complete");
            }else{
              array_push($this->_errors,"rollback error - ".$dbMgr->getError());
            }
            return false;
          }
        }
      }
//      $dbMgr->commit();
   }
    /**
    * parseGraphemes - parses a stream of characters into graphemes
    *
    * @param array mixed of parser configuration metadata
    * @return boolean true if successful parse, false otherwise
    */
    public function parse($config=null) {
      global $graphemeCharacterMap;
      // setup
      if ($config) {
        $lines = array($config);
      }else{
        $lines = $this->_configs;
      }
      if (!@$lines) {
        array_push($this->_errors,"no line information to parse");
        return false;
      }
      $parseGUID = "#".$this->_sessUid;
      $ckn = null;
      $cfgLnCnt = 0;
      $curTokLineSequence = null;
      $srtKey = (!defined("USESKTSORT")|| !USESKTSORT)?"srt":"ssrt";

      foreach($lines as $lnCfg) {
        ++$cfgLnCnt;
        $lineWrap = false;
        if (array_key_exists("transliteration",$lnCfg)) {
          $cnt = mb_strlen($lnCfg["transliteration"]);
        }
        if (!@$cnt) {
          array_push($this->_errors,"no line information to parse for row ".$lnCfg["AzesTRID"]);
          continue;
        }else{
          $script = $lnCfg["transliteration"];
        }
        $transcription = "";
        $segState = "S";
        $tcmState = "S";
        $heading = null;
        $prevCKN = $ckn;
        //todo code to check line wrap which validates ckn is the same for both lines and next line order is greater than previous line
        $ckn = array_key_exists("ckn",$lnCfg) ? $lnCfg["ckn"] : "cknxxxx";
        $ownerID = array_key_exists("ownerID",$lnCfg) ? $lnCfg["ownerID"] : 4;
        $vis = array_key_exists("visibilityIDs",$lnCfg) ? $lnCfg["visibilityIDs"] : "{3}";
        $attr = array_key_exists("attributionIDs",$lnCfg) ? $lnCfg["attributionIDs"] : null;
        $txtid = array_key_exists("txtid",$lnCfg) ? $lnCfg["txtid"] : null;
        //create sequence for this line's tokens for the current edition
        $prevTokLineSequence = $curTokLineSequence;// first time through this is null
        $curTokLineSequence = new Sequence();
        $curTokLineSequence->setVisibilityIDs($vis);
        $curTokLineSequence->setOwnerID($ownerID);
        $curTokLineSequence->setTypeID($this->_termLookups['textdivision-text']);//term dependency
        if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
          foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
            $curTokLineSequence->storeScratchProperty($key,$value);
          }
        }
        if (@$attr){
          $curTokLineSequence->setAttributionIDs($attr);
        }
        array_push($this->_sequences,$curTokLineSequence);
        $tokLineSeqIndex = count($this->_sequences);
        $tokLineSeqTempID = 0 - $tokLineSeqIndex;
        $curTokLineSequence->storeScratchProperty("nonce","seq_".$tokLineSeqTempID.$parseGUID);
        //create sequence for this line's syllables for the current edition
        $curPhysLineSequence = new Sequence();
        $curPhysLineSequence->setVisibilityIDs($vis);
        $curPhysLineSequence->setOwnerID($ownerID);
        $curPhysLineSequence->setTypeID($this->_termLookups['linephysical-textphysical']);//term dependency
        if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
          foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
            $curPhysLineSequence->storeScratchProperty($key,$value);
          }
        }
        if (@$attr){
          $curPhysLineSequence->setAttributionIDs($attr);
        }
        array_push($this->_sequences,$curPhysLineSequence);
        $physLineSeqIndex = count($this->_sequences);
        $physLineSeqTempID = 0 - $physLineSeqIndex;
        $curPhysLineSequence->storeScratchProperty("nonce","seq_".$physLineSeqTempID.$parseGUID);
        //handle adding tokLineSeq to textEdTokSeq
        if($ckn != $prevCKN) {
          $analysisSequence = null;//new text so end previous struct sequence
          //start a new text sequence to capture tokenized lines - NOTE: assumes that config is ordered
          $txtEdTokSequence = new Sequence();
          $txtEdTokSequence->setVisibilityIDs($vis);
          $txtEdTokSequence->setOwnerID($ownerID);
          $txtEdTokSequence->setEntityIDs(array("seq:".$tokLineSeqTempID));
          $txtEdTokSequence->setLabel("Tokenisation for $ckn");
          $txtEdTokSequence->setTypeID($this->_termLookups['text-sequencetype']);//term dependency
          if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
            foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
              $txtEdTokSequence->storeScratchProperty($key,$value);
            }
          }
          if (@$attr){
            $txtEdTokSequence->setAttributionIDs($attr);
          }
          array_push($this->_sequences,$txtEdTokSequence);
          $txtEdTokSeqIndex = count($this->_sequences);
          $txtEdTokSeqTempID = 0 - $txtEdTokSeqIndex;
          $txtEdTokSequence->storeScratchProperty("nonce","seq_".$txtEdTokSeqTempID.$parseGUID);
          //start a new text sequence to capture physical lines - NOTE: assumes that config is ordered
          $txtEdPhysSequence = new Sequence();
          $txtEdPhysSequence->setVisibilityIDs($vis);
          $txtEdPhysSequence->setOwnerID($ownerID);
          $txtEdPhysSequence->setEntityIDs(array("seq:".$physLineSeqTempID));
          $txtEdPhysSequence->setLabel("text Physical Edition for $ckn");
          $txtEdPhysSequence->setTypeID($this->_termLookups['textphysical-sequencetype']);//term dependency
          if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
            foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
              $txtEdPhysSequence->storeScratchProperty($key,$value);
            }
          }
          if (@$attr){
            $txtEdPhysSequence->setAttributionIDs($attr);
          }
          array_push($this->_sequences,$txtEdPhysSequence);
          $txtEdPhysSeqIndex = count($this->_sequences);
          $txtEdPhysSeqTempID = 0 - $txtEdPhysSeqIndex;
          $txtEdPhysSequence->storeScratchProperty("nonce","seq_".$txtEdPhysSeqTempID.$parseGUID);
          //create a new edition to hold the Tokenized sequence (text edition sequence)
          $curEdition = new Edition();
          $curEdition->setVisibilityIDs($vis);
          $curEdition->setOwnerID($ownerID);
          $curEdition->setTypeID($this->_termLookups['published-editiontype']);//term dependency
          $curEdition->setSequenceIDs(array($txtEdTokSeqTempID,$txtEdPhysSeqTempID));
          $description = (array_key_exists('editionDescription',$lnCfg)?$lnCfg['editionDescription']:"Edition for $ckn");
          $curEdition->setDescription($description);
          if(array_key_exists("Edition",$lnCfg) && array_key_exists("scratch",$lnCfg["Edition"])) {
            foreach ($lnCfg["Edition"]["scratch"] as $key => $value) {
              $curEdition->storeScratchProperty($key,$value);
            }
          }
          if (@$txtid){
            $curEdition->setTextID($txtid);
          }
          if (@$attr){
            $curEdition->setAttributionIDs($attr);
          }
          array_push($this->_editions,$curEdition);
          $editionIndex = count($this->_editions);
          $editionTempID = 0 - $editionIndex;
          $curEdition->storeScratchProperty("nonce","edn_".$editionTempID.$parseGUID);
        }else{
          //add tokLineSeq to existing txtEdTokSeq
          $entityIDs = $txtEdTokSequence->getEntityIDs();
          array_push($entityIDs,"seq:".$tokLineSeqTempID);
          $txtEdTokSequence->setEntityIDs($entityIDs);
          //add physLineSeq to existing txtEdPhysSeq
          $entityIDs = $txtEdPhysSequence->getEntityIDs();
          array_push($entityIDs,"seq:".$physLineSeqTempID);
          $txtEdPhysSequence->setEntityIDs($entityIDs);
        }
        $curBaseline = new Baseline();
        $curBaseline->setVisibilityIDs($vis);
        $curBaseline->setType($this->_termLookups['transcription-baselinetype']);//term dependency
        $curBaseline->setOwnerID($ownerID);
        if (@$attr){
          $curBaseline->setAttributionIDs($attr);
        }
        foreach ( $lnCfg["Baseline"]["scratch"] as $key => $value) {
          $curBaseline->storeScratchProperty($key,$value);
        }
        array_push($this->_baselines,$curBaseline);
        $blnIndex = count($this->_baselines);
        $blnTempID = 0 - $blnIndex;
        $this->_baselines[$blnIndex-1]->storeScratchProperty("nonce","bln_".$blnTempID.$parseGUID);
        $curSpan = new Span();
        $curSpan->setVisibilityIDs($vis);
        if (@$attr){
          $curSpan->setAttributionIDs($attr);
        }
        $curSpan->setOwnerID($ownerID);
        array_push($this->_spans,$curSpan);
        $spnIndex = count($this->_spans);
        $spnTempID = 0 - $spnIndex;
        $this->_spans[$spnIndex-1]->storeScratchProperty("nonce","spn_".$spnTempID.$parseGUID);
        $curLine = new Line();
        $curLine->setVisibilityIDs($vis);
        if (@$attr){
          $curLine->setAttributionIDs($attr);
        }
        $curLine->setOwnerID($ownerID);
        if (@$spnTempID){
          $curLine->setSpanIDs(array($spnTempID));
        }
        $lineMask = "XX";
        if (array_key_exists("mask",$lnCfg["Line"])) {
          $curLine->setMask($lnCfg["Line"]["mask"]);
          $lineMask = $lnCfg["Line"]["mask"];
          $curTokLineSequence->setLabel($lineMask);
          $curTokLineSequence->storeScratchProperty("cknLine",$ckn.".$lineMask");
          $curPhysLineSequence->setLabel($lineMask);
          $curPhysLineSequence->storeScratchProperty("cknLine",$ckn.".$lineMask");
        }
        if (array_key_exists("order",$lnCfg["Line"])) {
          $curLine->setOrder($lnCfg["Line"]["order"]);
        }
        foreach ( $lnCfg["Line"]["scratch"] as $key => $value) {
          $curLine->storeScratchProperty($key,$value);
        }
        array_push($this->_lines,$curLine);
        $linIndex = count($this->_lines);
        $linTempID = 0 - $linIndex;
        $this->_lines[$linIndex-1]->storeScratchProperty("nonce","lin_".$linTempID.$parseGUID);
        if (array_key_exists("Run",$lnCfg)) {
          $curRun = new Run();
          $curRun->setVisibilityIDs($vis);
          if (@$attr){
            $curRun->setAttributionIDs($attr);
          }
          $curRun->setOwnerID($ownerID);
          foreach ( $lnCfg["Run"]["scratch"] as $key => $value) {
            $curRun->storeScratchProperty($key,$value);
          }
          if (array_key_exists("scribe",$lnCfg["Run"])) {
            $curRun->setScribe($lnCfg["Run"]["scribe"]);
          }
          array_push($this->_runs,$curRun);
          $runIndex = count($this->_runs);
          $runTempID = 0 - $runIndex;
          $this->_runs[$runIndex-1]->storeScratchProperty("nonce","run_".$runTempID.$parseGUID);
        }else{
          $runIndex = null;
        }
        // loop through characters of the script
        for ($i =0; $i<$cnt;) {
          $atCompoundSeparator = false;//used to detect end of line compound separator for crossline compounds
          $inc = 1;
          $char = mb_substr($script,$i,1);
          $validGrapheme = false; //flag not grapheme and set if found for linewrap

          if (bin2hex($char[0]) == "e2") {// non breaking space
            if (bin2hex($char[1]) == "80") {
              if (bin2hex($char[2]) == "a8") {
                $i++;
                continue;
              }
            }
          }

          if ($char == "." && mb_substr($script,$i,5) == ". . ."){//elipses
            $char = "…";
            $inc = 5;
          }

          switch ($char) {
            case "<"://quote
              //check if quote start <b>
              if (mb_substr($script,$i,3) == '<b>'){
                if (@$inQuote) {
                  array_push($this->_errors,"start quote found nested in quote !!ignoring!!, located at character $i of line ".$curLine->getOrder()." cfg line # $cfgLnCnt");
                } else { //setup quote sequence
                  $inQuote = true;
                  // check token end for the case where there is no preceeding space
                  if ($tokIndex) {
                    $tokIndex = null;
                    $cmpIndex = null;
                  }
                  //start the qoute sequence
                  $curQuoteSequence = new Sequence();
                  $curQuoteSequence->setVisibilityIDs($vis);
                  $curQuoteSequence->setOwnerID($ownerID);
                  $curQuoteSequence->setLabel("quote in $ckn");
                  $curQuoteSequence->setTypeID($this->_termLookups['rootref-textreference']);//term dependency
                  if (@$attr){
                    $curQuoteSequence->setAttributionIDs($attr);
                  }
                  array_push($this->_sequences,$curQuoteSequence);
                  $curQuoteSeqIndex = count($this->_sequences);
                  $quoteSeqTempID = 0 - $curQuoteSeqIndex;
                  $curQuoteSequence->storeScratchProperty("nonce","seq_".$quoteSeqTempID.$parseGUID);
                  $curQuoteSequence->storeScratchProperty("cknLine",$ckn.".$lineMask");
                  if(!@$txtRefSequence) { //start a new txtRefContainer sequence to capture tokenized quoteSeq
                    $txtRefSequence = new Sequence();
                    $txtRefSequence->setVisibilityIDs($vis);
                    $txtRefSequence->setOwnerID($ownerID);
                    $txtRefSequence->setEntityIDs(array("seq:".$quoteSeqTempID));
                    $txtRefSequence->setLabel("text Reference Container for $ckn");
                    $txtRefSequence->setTypeID($this->_termLookups['textreference-sequencetype']);//term dependency
                    if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
                      foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
                        $txtRefSequence->storeScratchProperty($key,$value);
                      }
                    }
                    if (@$attr){
                      $txtRefSequence->setAttributionIDs($attr);
                    }
                    array_push($this->_sequences,$txtRefSequence);
                    $txtRefSeqIndex = count($this->_sequences);
                    $txtRefSeqTempID = 0 - $txtRefSeqIndex;
                    $txtRefSequence->storeScratchProperty("nonce","seq_".$txtRefSeqTempID.$parseGUID);
                    $edSeqIDs = $curEdition->getSequenceIDs();
                    array_push($edSeqIDs,$txtRefSeqTempID);
                    $curEdition->setSequenceIDs($edSeqIDs);
                  }else{//add lineSeq to existing txtSeq
                    $entityIDs = $txtRefSequence->getEntityIDs();
                    array_push($entityIDs,"seq:".$quoteSeqTempID);
                    $txtRefSequence->setEntityIDs($entityIDs);
                  }
                }
                $i += 3; // move char pointer paste marker
              } else if (mb_substr($script,$i,4) == '</b>') {
                if (!$inQuote) {
                  array_push($this->_errors,"end quote found outside quote !!ignoring!!, located at character $i of line ".$curLine->getOrder()." cfg line # $cfgLnCnt");
                } else { //close quote sequence
                  $inQuote = false;
                  //null qouteSequence
                  $curQuoteSeqIndex = null;
                  // check token end for the case where there is no space preceeding the </b>
                  // need to skip all text criticals when looking ahead for token separator
                  if ($tokIndex) {
                    for ($j = $i+4; $j < $cnt; $j++) {
                      $searchChar = mb_substr($script,$j,1);
                      //if not text critical
                      if (!in_array($searchChar,array("⟨","*","⟪","{","(",")","}","[","]","⟩","⟫"))) {
                        //check for token separator
                        if ($searchChar != '-' && $searchChar != '‐') {
                          $tokIndex = null;
                          $cmpIndex = null;
                        }
                        break;
                      }
                    }
                  }
                }
                $i += 4; // move char pointer paste marker
              } else { //illegal text markup
                $unknownMarkup = mb_strstr(mb_substr($script,$i),">",true).'>';
                array_push($this->_errors,"illegal text markup $unknownMarkup at character $i of line ".$curLine->getOrder()." cfg line # $cfgLnCnt");
                $i += mb_strlen($unknownMarkup);
                if ($i >= $cnt) {
                  array_push($this->_errors,"illegal text markup starting at character $i of $ckn.$lineMask cfg line # $cfgLnCnt, reached end of line");
                }
              }
              break;
            case "«"://author heading
              //read tag until end marker "»"
              $heading = mb_strstr(mb_substr($script,$i+1),"»",true);
              if (!@$heading) {
              array_push($this->_errors,"heading indicator has empty label, at character $i of line ".$curLine->getOrder()." cfg line # $cfgLnCnt");
              }else{//start the division sequence
                $curStructDivSequence = new Sequence();
                $curStructDivSequence->setVisibilityIDs($vis);
                $curStructDivSequence->setOwnerID($ownerID);
                $curStructDivSequence->setLabel($heading);
                $curStructDivSequence->setTypeID($this->_termLookups['chapter-analysis']);//term dependency
                if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
                  foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
                    $curStructDivSequence->storeScratchProperty($key,$value);
                  }
                }
                if (@$attr){
                  $curStructDivSequence->setAttributionIDs($attr);
                }
                array_push($this->_sequences,$curStructDivSequence);
                $curStructDivSeqIndex = count($this->_sequences);
                $curStructDivSeqTempID = 0 - $curStructDivSeqIndex;
                $curStructDivSequence->storeScratchProperty("nonce","seq_".$curStructDivSeqTempID.$parseGUID);
                $curStructDivSequence->storeScratchProperty("cknLine",$ckn.".$lineMask");
                if(!@$analysisSequence) { //start a new txtStruct sequence to capture divSeq
                  $analysisSequence = new Sequence();
                  $analysisSequence->setVisibilityIDs($vis);
                  $analysisSequence->setOwnerID($ownerID);
                  $analysisSequence->setEntityIDs(array("seq:".$curStructDivSeqTempID));
                  $analysisSequence->setLabel("text Structure for $ckn");
                  $analysisSequence->setTypeID($this->_termLookups['analysis-sequencetype']);//term dependency
                  if(array_key_exists("Sequence",$lnCfg) && array_key_exists("scratch",$lnCfg["Sequence"])) {
                    foreach ( $lnCfg["Sequence"]["scratch"] as $key => $value) {
                      $analysisSequence->storeScratchProperty($key,$value);
                    }
                  }
                  if (@$attr){
                    $analysisSequence->setAttributionIDs($attr);
                  }
                  array_push($this->_sequences,$analysisSequence);
                  $analysisSeqIndex = count($this->_sequences);
                  $analysisSeqTempID = 0 - $analysisSeqIndex;
                  $analysisSequence->storeScratchProperty("nonce","seq_".$analysisSeqTempID.$parseGUID);
                  //add analysisSeq to edition
                  $edSeqIDs = $curEdition->getSequenceIDs();
                  array_push($edSeqIDs,$analysisSeqTempID);
                  $curEdition->setSequenceIDs($edSeqIDs);
                }else{
                  //add structDivSeq to existing analysisSeq
                  $entityIDs = $analysisSequence->getEntityIDs();
                  array_push($entityIDs,"seq:".$curStructDivSeqTempID);
                  $analysisSequence->setEntityIDs($entityIDs);
                }
              }
              $i += mb_strlen($heading) + 2;
              if ($i >= $cnt) {
                array_push($this->_errors,"incomplete heading $heading at character $i of $ckn.$lineMask , reached end of line before finding » cfg line # $cfgLnCnt");
              }
              break;
            case "~"://vowel sharing token split tag
              //read tag until ~,
              if (!@$graIndex) {
                array_push($this->_errors,"no grapheme before replacement sequence at character $i of $ckn.$lineMask"." cfg line # $cfgLnCnt");
                break;
              }
              $prevGrapheme = $this->_graphemes[$graIndex-1]->getValue();
              $replaceCmd = mb_strstr(mb_substr($script,$i+1),"~",true);
              //todo write test for posSep
              $posSep = mb_strpos($replaceCmd,",");
              $matchGrapheme = mb_substr($replaceCmd,0,$posSep);
              $replacementString = mb_substr($replaceCmd,$posSep+1);
              if (@$matchGrapheme != $prevGrapheme) {//check marker is the same as previous grapheme
                array_push($this->_errors,"replacement sequence at character $i of $ckn.$lineMask has marker grapheme $matchGrapheme that doesn't match previous grapheme $prevGrapheme"." cfg line # $cfgLnCnt");
              }
              //parse grapheme decomposition
              if ($posSep = mb_strpos($replacementString," ")){
                $isCompSep = false;
              } else if ($posSep = mb_strpos($replacementString,"-")){
                $isCompSep = true;
              } else if ($posSep = mb_strpos($replacementString,"‐")){
                $isCompSep = true;
              }else{
                array_push($this->_errors,"sandhi replacement sequence at character $i of $ckn.$lineMask has no valid separator"." cfg line # $cfgLnCnt");
              }
              //todo write test for posSep and decomp
              $leftStr = mb_substr($replacementString,0,$posSep);
              $rightStr = mb_substr($replacementString,$posSep+1);
              $decomp = ($leftStr?$leftStr:"").":".($isCompSep?"-":" ").":".($rightStr?$rightStr:"");
              //add to previous grapheme
              $this->_graphemes[$graIndex-1]->setDecomposition($decomp);
              //modify previous token value
              $tokValue = $curToken->getToken();
              $posGra =  mb_strrpos($tokValue,$prevGrapheme);
              $tokValue = mb_substr($tokValue,0,$posGra).$leftStr.mb_substr($tokValue,$posGra+1);
              $curToken->setToken($tokValue);
              //handle entities
              if (@$isCompSep && !@$cmpIndex){//must be first separator so create compound
                if(!@$tokIndex || !@$tokTempID) {
                  array_push($this->_errors,"sandhi replacement at character $i of $ckn.$lineMask with invalid token ids"." cfg line # $cfgLnCnt");
                }
                $curCompound = new Compound();
                $curCompound->setComponentIDs(array('tok:'.$tokTempID));
                $curCompound->setVisibilityIDs($vis);
                $curCompound->setOwnerID($ownerID);
                if(array_key_exists("Compound",$lnCfg) && array_key_exists("scratch",$lnCfg["Compound"])) {
                  foreach ( $lnCfg["Compound"]["scratch"] as $key => $value) {
                    $curCompound->storeScratchProperty($key,$value);
                  }
                }
                if (@$attr){
                  $curCompound->setAttributionIDs($attr);
                }
                array_push($this->_compounds,$curCompound);
                $cmpIndex = count($this->_compounds);
                $cmpTempID = 0 - $cmpIndex;
                $this->_compounds[$cmpIndex-1]->storeScratchProperty("nonce","cmp_".$cmpTempID.$parseGUID);
                $this->_compounds[$cmpIndex-1]->storeScratchProperty("cknLine",$ckn.".$lineMask");
                if (@$curStructDivSeqIndex) { //there is a structural division sequence entity so fix up compound token by removing the previous token id (1st of compound) and add compound id
                  $entityIDs = $this->_sequences[$curStructDivSeqIndex-1]->getEntityIDs();
                  $lastentityGID = array_pop($entityIDs);
                  if ($lastentityGID == "tok:$tokTempID"){//when can replace the token with compound globalID
                    array_push($entityIDs,"cmp:".$cmpTempID);
                    $this->_sequences[$curStructDivSeqIndex-1]->setEntityIDs($entityIDs);
                  }else{//we are not in sych raise an error
                    array_push($this->_errors,"division sequence - sandhi marker (line $lineMask character $i) last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                  }
                }
                if (@$curQuoteSeqIndex) { //there is a quote (root ref) seq so fix up compound token by removing the previous token id (1st of compound) and add compound id
                  $entityIDs = $curQuoteSequence->getEntityIDs();
                  $lastentityGID = array_pop($entityIDs);
                  if ($lastentityGID == "tok:$tokTempID"){//when can replace the token with compound globalID
                    array_push($entityIDs,"cmp:".$cmpTempID);
                    $curQuoteSequence->setEntityIDs($entityIDs);
                  }else{//we are not in sych raise an error
                    array_push($this->_errors,"line sequence - sandhi marker (line $lineMask character $i) last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                  }
                }
                if (@$tokLineSeqIndex) { //there is a tokenised line seq so fix up compound token by removing the previous token id (1st of compound) and add compound id
                  $entityIDs = $curTokLineSequence->getEntityIDs();
                  if (!$entityIDs && $prevTokLineSequence) {// at the start of a line must be token wrap
                    $entityIDs = $prevTokLineSequence->getEntityIDs();
                    $usePrev = true;
                  }else{
                    $usePrev = false;
                  }
                  $lastentityGID = array_pop($entityIDs);
                  if ($lastentityGID == "tok:$tokTempID"){//when can replace the token with compound globalID
                    array_push($entityIDs,"cmp:".$cmpTempID);
                    if ($usePrev) {
                      $prevTokLineSequence->setEntityIDs($entityIDs);
                    }else {
                      $curTokLineSequence->setEntityIDs($entityIDs);
                    }
                  } else {//we are not in sych raise an error //todo need to handle case where token spans lines and is 1st of a compound
                    array_push($this->_errors,"line sequence - sandhi marker (line $lineMask character $i) last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                  }
                }
              }
              //process token
              $curToken = new Token();
              $curToken->setGraphemeIDs(array($graTempID));
              $curToken->setToken($rightStr?$rightStr:"");//sandhi so need decomposition
              $curToken->setVisibilityIDs($vis);
              $curToken->setOwnerID($ownerID);
              if(array_key_exists("Token",$lnCfg) && array_key_exists("scratch",$lnCfg["Token"])) {
                foreach ( $lnCfg["Token"]["scratch"] as $key => $value) {
                  $curToken->storeScratchProperty($key,$value);
                }
              }
              if (@$attr){
                $curToken->setAttributionIDs($attr);
              }
              array_push($this->_tokens,$curToken);
              $tokIndex = count($this->_tokens);
              $tokTempID = 0 - $tokIndex;
              $this->_tokens[$tokIndex-1]->storeScratchProperty("nonce","tok_".$tokTempID.$parseGUID);
              $this->_tokens[$tokIndex-1]->storeScratchProperty("cknLine",$ckn.".$lineMask");
              //if compound update with new token
              if (@$cmpIndex && $isCompSep){
                $components = $this->_compounds[$cmpIndex-1]->getComponentIDs();
                array_push($components,"tok:".$tokTempID);
                $this->_compounds[$cmpIndex-1]->setComponentIDs($components);
              }else if (@$tokLineSeqIndex || @$curStructDivSeqIndex || $curQuoteSeqIndex) { //there is a division marker and not in compound so add token to sequence
                if (@$curStructDivSeqIndex) {
                  $entityIDs = $this->_sequences[$curStructDivSeqIndex-1]->getEntityIDs();
                  array_push($entityIDs,"tok:".$tokTempID);
                  $this->_sequences[$curStructDivSeqIndex-1]->setEntityIDs($entityIDs);
                }
                if (@$curQuoteSeqIndex) {
                  $entityIDs = $curQuoteSequence->getEntityIDs();
                  array_push($entityIDs,"tok:".$tokTempID);
                  $curQuoteSequence->setEntityIDs($entityIDs);
                }
                if (@$tokLineSeqIndex) {
                  $entityIDs = $curTokLineSequence->getEntityIDs();
                  array_push($entityIDs,"tok:".$tokTempID);
                  $curTokLineSequence->setEntityIDs($entityIDs);
                }
              }
              if (@$cmpIndex && !$isCompSep){
                $cmpIndex = null;
              }
              //adjust strng count
              $i += mb_strlen($replaceCmd) + 2;
              break;
            case "!"://subfragment transition
              if (@$subfrag) {
                array_push($this->_errors,"subfragment transition marker $subfrag not saved, overwritten by marker at character $i"." cfg line # $cfgLnCnt");
              }
              //read tag until ! attach to grapheme
              $subfrag = mb_strstr(mb_substr($script,$i+1),"!",true);
              $i += mb_strlen($subfrag) + 2;
              break;
            case "^"://footnote marker belong to nearest grapheme
              //read footnote marker
              $footnote = mb_strstr(mb_substr($script,$i+1),"^",true);
              $atBOL = ($i==0);
              $i += mb_strlen($footnote) + 2;
              $footnote = $ckn.$footnote;
              if (@$sclIndex) {// placed it on the previous syllable
                $this->_syllableClusters[$sclIndex-1]->storeScratchProperty('footnote',$footnote);
              }else if ($atBOL && @$physLineSeqIndex) {// placed it on the physicalLine sequence
                $this->_sequences[$physLineSeqIndex-1]->storeScratchProperty('footnote',$footnote);
              }
              if (@$graIndex) {// placed it on the previous grapheme - - - deprecate
                $this->_graphemes[$graIndex-1]->storeScratchProperty('footnote',$footnote);
                $footnote = null; // stop from being attach to next grapheme
              }
              break;
            case "[":// text critical uncertainy start
            case "]":// text critical uncertainy stop
            case "(":// text critical editorial restoration start
            case ")":// text critical editorial restoration stop
            case "⟨":// text critical editorial insertion start
            case "⟩":// text critical editorial insertion stop
            case "⟪":// text critical scribal insertion start
            case "⟫":// text critical scribal insertion stop
            case "{":// text critical editorial deletion start
            case "}":// text critical editorial deletion stop
              if ($char == "(" || $char == "⟨"){
                if (mb_substr($script,$i+1,1)== '*') {
                  $tcm = $char."*";
                  $i+=2;
                }else{
                  array_push($this->_errors,"found no * for $char at character $i for TCM brackets for cknLine $ckn.$lineMask"." cfg line # $cfgLnCnt");
                  $i++;
                }
              }else if ($char == "{" && mb_substr($script,$i+1,1)== '{'){//text critical scribal deletion start
                $tcm = "{{";
                $i+=2;
              }else if ($char == "}" && mb_substr($script,$i+1,1)== '}'){//text critical scribal deletion stop
                $tcm = "}}";
                $i+=2;
              }else{
                $tcm = $char;
                $i++;
              }
              $nextTCMS = getNextTCMState($tcmState,$tcm);
              if ( $nextTCMS == "E"){
                array_push($this->_errors,"found illegal TCM $tcm at character ".($i - strlen($tcm))." while in TCM state $tcmState for cknLine $ckn.$lineMask"." cfg line # $cfgLnCnt");
              }else{
                $tcmState = $nextTCMS;
              }
              break;
            case "=":// token break intra syllable or cross line token
              // set all current entity vars to null except segment and syllable
              $i++;
              if ($i >= $cnt) {// check if cross line
                $lineWrap = $curLine->getOrder();
                $sclIndex = null;
                $segIndex = null;
                $segTempID = null;
                $sclTempID = null;
              }else{
                $tokIndex = null;
                $cmpIndex = null;
                $cmpTempID = null;
                $tokTempID = null;
              }
              $graIndex = null;
              $graTempID = null;
              break;
            case " ":// token or compound separator U+0020
              // set all current entity vars to null
              if (@$numberToken && $tokIndex) { //number token so save preTok info for processing numbers
                $prevNumberTokIndex = $tokIndex;
                $prevNumberTokTempID = $tokTempID;
              }
              if (!@$numberToken) { // if no previous numberToken then (sp) means close current compound
                $cmpIndex = null;
                $cmpTempID = null;
              }
              $numberToken = false;
              $tokIndex = null;
              $tokTempID = null;
              if ($segState != "C") {//C means that space is splitting a syllable.
                $graIndex = null;
                $sclIndex = null;
                $segIndex = null;
                $graTempID = null;
                $segTempID = null;
                $sclTempID = null;
                $segState = "S";//signal new syllable
              } else {
                error_log("at token break with grapheme consonant = ".$curGrapheme->getGrapheme()." and seg state $segState ");
              }
              $i++;
              break;
            case "-":// compound token separator U+002D  unicode minus  -
            case "‐":// compound token separator multiByte e28090 hyphen
              if ($i == 0) {// at the start of a line and compound hyphens are not allowed
                array_push($this->_errors,"error - compound hyphen found character $i at start of line $lineMask of $ckn"." cfg line # $cfgLnCnt");
              } else if  (mb_substr($script,$i-1,1) == " "){//previous char is a space and is not allowed
                array_push($this->_errors,"error - compound hyphen following space found at character $i on line $lineMask of $ckn "." cfg line # $cfgLnCnt");
              } else if (!@$cmpIndex && $tokIndex){//must be first separator of this compound
                $curCompound = new Compound();
                $curCompound->setComponentIDs(array('tok:'.$tokTempID));
                $curCompound->setVisibilityIDs($vis);
                $curCompound->setOwnerID($ownerID);
                if (@$attr){
                  $curCompound->setAttributionIDs($attr);
                }
                if(array_key_exists("Compound",$lnCfg) && array_key_exists("scratch",$lnCfg["Compound"])) {
                  foreach ( $lnCfg["Compound"]["scratch"] as $key => $value) {
                    $curCompound->storeScratchProperty($key,$value);
                  }
                }
                array_push($this->_compounds,$curCompound);
                $cmpIndex = count($this->_compounds);
                $cmpTempID = 0 - $cmpIndex;
                $this->_compounds[$cmpIndex-1]->storeScratchProperty("nonce","cmp_".$cmpTempID.$parseGUID);
                $this->_compounds[$cmpIndex-1]->storeScratchProperty("cknLine",$ckn.".$lineMask");
//                $this->_tokens[$tokIndex-1]->setCompoundIDs(array($cmpTempID));
                if (@$curStructDivSeqIndex) { //there is a division marker so remove the previous token and add compound id
                  $entityIDs = $this->_sequences[$curStructDivSeqIndex-1]->getEntityIDs();
                  $lastentityGID = array_pop($entityIDs);
                  if ($lastentityGID == "tok:$tokTempID"){//when can replace the token with compound globalID
                    array_push($entityIDs,"cmp:".$cmpTempID);
                    $this->_sequences[$curStructDivSeqIndex-1]->setEntityIDs($entityIDs);
                  }else{//we are not in sych raise an error
                    array_push($this->_errors,"divison sequence - compound marker (line $lineMask character $i), last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                  }
                }
                if (@$curQuoteSeqIndex) { //there is a line seq so remove the previous token and add compound id
                  $entityIDs = $curQuoteSequence->getEntityIDs();
                  $lastentityGID = array_pop($entityIDs);
                  if ((!@$lastentityGID && $cmpTempID) || $lastentityGID == "tok:$tokTempID"){//we can replace the token with compound globalID
                    array_push($entityIDs,"cmp:".$cmpTempID);
                    $curQuoteSequence->setEntityIDs($entityIDs);
                  }else{//we are not in sych raise an error
                    array_push($this->_errors,"quote sequence - compound marker (line $lineMask character $i) last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                  }
                }
                if (@$tokLineSeqIndex) { //there is a line seq so remove the previous token and add compound id
                  $entityIDs = $curTokLineSequence->getEntityIDs();
                  if (!$entityIDs && $prevTokLineSequence) {// at the start of a line must be token wrap
                    $entityIDs = $prevTokLineSequence->getEntityIDs();
                    $usePrev = true;
                  }else{
                    $usePrev = false;
                  }
                  $lastentityGID = array_pop($entityIDs);
                  if ((!@$lastentityGID && $cmpTempID) || $lastentityGID == "tok:$tokTempID"){//when can replace the token with compound globalID
                    array_push($entityIDs,"cmp:".$cmpTempID);
                    if ($usePrev) {
                      $prevTokLineSequence->setEntityIDs($entityIDs);
                    }else {
                      $curTokLineSequence->setEntityIDs($entityIDs);
                    }
                  }else{//we are not in sych raise an error
                    array_push($this->_errors,"token line sequence - compound marker (line $lineMask character $i) last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                  }
                }
              }
              $graIndex = null;
              $sclIndex = null;
              $segIndex = null;
              $tokIndex = null;
              $tokTempID = null;
              $graTempID = null;
              $segTempID = null;
              $sclTempID = null;
              $segState = "S";//signal new syllable
              $i++;
              $atCompoundSeparator = true; // use when changing lines to all compound to span lines.
              break;
            default:
              $testChar = $char;
              $char = mb_strtolower($char);
              $graphemeIsUpper = ($testChar != $char);//uppercase Grapheme
//              if ($testChar != $char){//uppercase
//                if ($segState != 'S' || @$tokIndex){// upper case must be start of first segment of a Token
//                  array_push($this->_errors,"found uppercase $char at character $i in line $lineMask not at start of token"." cfg line # $cfgLnCnt");
//                }
//              }
              // convert multi-byte to grapheme - using greedy lookup
              if (array_key_exists($char,$graphemeCharacterMap)){
                //check next character included
                $char2 = mb_substr($script,$i+1,1);
                if (array_key_exists($char2,$graphemeCharacterMap[$char])){ // another char for grapheme
                  $inc++;
                  $char3 = mb_substr($script,$i+2,1);
                  if (array_key_exists($char3,$graphemeCharacterMap[$char][$char2])){ // another char for grapheme
                    $inc++;
                    $char4 = mb_substr($script,$i+3,1);
                    if (array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3])){ // another char for grapheme
                      $inc++;
                      if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4])){ // invalid sequence
                        array_push($this->_errors,"incomplete transcription at character $i line $lineMask, grapheme $char$char2$char3$char4 has no sort code"." cfg line # $cfgLnCnt");
                        return false;
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
                    }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3])){ // invalid sequence
                      array_push($this->_errors,"incomplete transcription at character $i line $lineMask, grapheme $char$char2$char3 needs follow-on char and $char4 is not a valid follow-on"." cfg line # $cfgLnCnt");
                      return false;
                    }else{//found valid grapheme, save it
                      $str = $char.$char2.$char3;
                      $ustr = $testChar.$char2.$char3;
                      $typ = $graphemeCharacterMap[$char][$char2][$char3]['typ'];
                      if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3])) {
                        $srt = $graphemeCharacterMap[$char][$char2][$char3]['ssrt'];
                      } else {
                        $srt = $graphemeCharacterMap[$char][$char2][$char3]['srt'];
                      }
                    }
                  }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2])){ // invalid sequence
                    array_push($this->_errors,"incomplete transcription at character $i line $lineMask, grapheme $char$char2 needs follow-on char and $char3 is not a valid follow-on"." cfg line # $cfgLnCnt");
                    return false;
                  }else{//found valid grapheme, save it
                    $str = $char.$char2;
                    $ustr = $testChar.$char2;
                    if (array_key_exists("typ",$graphemeCharacterMap[$char][$char2])) {
                      $typ = $graphemeCharacterMap[$char][$char2]['typ'];
                    } else {
                      array_push($this->_errors,"grapheme type not found for character $i line $lineMask, grapheme $char$char2  cfg line # $cfgLnCnt");
                    }
                    if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2])) {
                      $srt = $graphemeCharacterMap[$char][$char2]['ssrt'];
                    } else {
                      $srt = $graphemeCharacterMap[$char][$char2]['srt'];
                    }
                  }
                }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char])){ // invalid sequence
                  array_push($this->_errors,"incomplete transcription at character $i, grapheme $char needs follow-on char and $char2 is not a valid follow-on"." cfg line # $cfgLnCnt");
                  return false;
                }else{//found valid grapheme, save it
                  $str = $char;
                  $ustr = $testChar;
                  if (array_key_exists("typ",$graphemeCharacterMap[$char])) {
                    $typ = $graphemeCharacterMap[$char]['typ'];
                  } else {
                    array_push($this->_errors,"type not found for character $i line $lineMask, grapheme $char cfg line # $cfgLnCnt");
                  }
                  if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char])) {
                    $srt = $graphemeCharacterMap[$char]['ssrt'];
                  } else {
                    $srt = $graphemeCharacterMap[$char]['srt'];
                  }
                }
                $validGrapheme = true;
                switch ($typ) {
                  case "V"://Vowel
                    $typTermID = $this->_termLookups['vowel-graphemetype'];//term dependency
                    break;
                  case "C"://Conconant
                    $typTermID = $this->_termLookups['consonant-graphemetype'];//term dependency
                    break;
                  case "O"://Other
                    $typTermID = $this->_termLookups['unknown-graphemetype'];//term dependency
                    break;
                  case "P":// Puncutation
                    $typTermID = $this->_termLookups['punctuation-graphemetype'];//term dependency
                    break;
                  case "I"://IntraSyllable
                    $typTermID = $this->_termLookups['intrasyllablepunctuation-graphemetype'];//term dependency
                    break;
                  case "M": //Vowel Modifier
                    $typTermID = $this->_termLookups['vowelmodifier-graphemetype'];//term dependency
                    break;
                  case "N": //Number
                    $typTermID = $this->_termLookups['numbersign-graphemetype'];//term dependency
                    break;
                }
                if ($typ == "N") {
                  $numberToken = true;
                }else if (@$prevNumberTokIndex){// non number and had previous number so clear number state info and end any compound
                  $prevNumberTokIndex = $prevNumberTokTempID = null;
                  $cmpIndex = $cmpTempID = null;
                  $numberToken = false;
                }
                //handle vowel carrier insert case
                if (($segState == "S" || $segState == "V" || $segState == "I") && $typ == "V") {//Start new Syllable with Vowel Carrier
                  //insert vowel carrier
                  $curGrapheme = new Grapheme();
                  $curGrapheme->setGrapheme("ʔ");
                  $curGrapheme->setType($this->_termLookups['consonant-graphemetype']);//term dependency
                  $curGrapheme->setSortCode("195");
                  $curGrapheme->setVisibilityIDs($vis);
                  $curGrapheme->setOwnerID($ownerID);
                  if ($tcmState != "S") {
                    $curGrapheme->setTextCriticalMark($tcmState);
                  }
                  array_push($this->_graphemes,$curGrapheme);
                  $carrierIndex = count($this->_graphemes);
                  $carrierTempID = 0 - $carrierIndex;
                  $this->_graphemes[$carrierIndex-1]->storeScratchProperty("nonce","gra_".$carrierTempID.$parseGUID);
                  $transcription .= "ʔ";
                }
                //create grapheme
                $curGrapheme = new Grapheme();
                $curGrapheme->setGrapheme($str);
                if ($graphemeIsUpper) {
                  $curGrapheme->setUppercase($ustr);
                }
                $curGrapheme->setType($typTermID);
                $curGrapheme->setSortCode($srt);
                $curGrapheme->setVisibilityIDs($vis);
                $curGrapheme->setOwnerID($ownerID);
                if ($tcmState != "S") {
                  $curGrapheme->setTextCriticalMark($tcmState);
                }
                array_push($this->_graphemes,$curGrapheme);
                $graIndex = count($this->_graphemes);
                $graTempID = 0 - $graIndex;
                $this->_graphemes[$graIndex-1]->storeScratchProperty("nonce","gra_".$graTempID.$parseGUID);
                $posSegStart = mb_strlen($transcription) +1;
                $transcription .= $str;
                $posSegStop = mb_strlen($transcription);
                if (@$footnote) {// placed footnote on this grapheme
                  $this->_graphemes[$graIndex-1]->storeScratchProperty('footnote',$footnote);
                  $sfootnote = (@$atBOL?null:$footnote);//pass it to syllable
                  $footnote = null; // stop from being attach to next grapheme
                }
                if (@$subfrag) {// placed subfragment on this grapheme
                  $this->_graphemes[$graIndex-1]->storeScratchProperty('subfragment',$subfrag);
                  $subfrag = null; // stop from being attach to next grapheme
                }
                $prevState = $segState;
                $segState = getNextSegmentState($prevState,$typ);
                if ($prevState == "S" || $segState == "S") {//start a new syllable
                  //create new Segment attached to Bln
                  $curSegment = new Segment();
                  $curSegment->setBaselineIDs(array($blnTempID));
                  $curSegment->setVisibilityIDs($vis);
                  $curSegment->setOwnerID($ownerID);
                  if (@$attr){
                    $curSegment->setAttributionIDs($attr);
                  }
                  if (@$carrierIndex) {
                    $curSegment->setStringPos(array(array($posSegStart-1,$posSegStop)));
                    $carrierIndex = null;
                  }else{
                    $curSegment->setStringPos(array(array($posSegStart,$posSegStop)));
                  }
                  array_push($this->_segments,$curSegment);
                  $segIndex = count($this->_segments);
                  $segTempID = 0 - $segIndex;
                  if (@$spnIndex){
                    $segmentIDs = $this->_spans[$spnIndex-1]->getSegmentIDs();
                    array_push($segmentIDs,$segTempID);
                    $this->_spans[$spnIndex-1]->setSegmentIDs($segmentIDs);
                  }
                  $this->_segments[$segIndex-1]->storeScratchProperty("nonce","seg_".$segTempID.$parseGUID);
                  //create new SyllableCluster attached to segment with index of new grapheme
                  $curSyllable = new SyllableCluster();
                  $curSyllable->setSegmentID($segTempID);
                  $curSyllable->setVisibilityIDs($vis);
                  $curSyllable->setOwnerID($ownerID);
                  if (@$sfootnote) {// placed footnote passed from grapheme on this syllable
                    $curSyllable->storeScratchProperty('footnote',$sfootnote);
                    $sfootnote = null; // stop from being attach to next syllable
                  }
                  if (@$attr){
                    $curSyllable->setAttributionIDs($attr);
                  }
                  if (strpos($tcmState,"I") !== false) {
                    $curSyllable->setTextCriticalMark($tcmState);
                  }
                  if (@$carrierTempID) {
                    $curSyllable->setGraphemeIDs(array($carrierTempID,$graTempID));
                  }else{
                    $curSyllable->setGraphemeIDs(array($graTempID));
                  }
                  array_push($this->_syllableClusters,$curSyllable);
                  $sclIndex = count($this->_syllableClusters);
                  $sclTempID = 0 - $sclIndex;
                  $this->_syllableClusters[$sclIndex-1]->storeScratchProperty("nonce","scl_".$sclTempID.$parseGUID);
                  if (@$physLineSeqIndex) {
                    $entityIDs = $curPhysLineSequence->getEntityIDs();
                    array_push($entityIDs,"scl:".$sclTempID);
                    $curPhysLineSequence->setEntityIDs($entityIDs);
                  }
                  //punctuation is a token by itself and terminates previous token or compound
                  if($typ == "P"){
                    $tokIndex = null;
                    $cmpIndex = null;
                  }
                  //create Token if no cur token
                  if(@!$tokIndex){
                    $curToken = new Token();
                    $curToken->setVisibilityIDs($vis);
                    $curToken->setOwnerID($ownerID);
                    if (@$attr){
                      $curToken->setAttributionIDs($attr);
                    }
                    if (@$carrierTempID) {
                      $curToken->setGraphemeIDs(array($carrierTempID,$graTempID));
                      $curToken->setToken("ʔ".$str);
                    }else{
                      $curToken->setGraphemeIDs(array($graTempID));
                      $curToken->setToken($str);
                    }
                    if(array_key_exists("Token",$lnCfg) && array_key_exists("scratch",$lnCfg["Token"])) {
                      foreach ( $lnCfg["Token"]["scratch"] as $key => $value) {
                        $curToken->storeScratchProperty($key,$value);
                      }
                    }
                    array_push($this->_tokens,$curToken);
                    $tokIndex = count($this->_tokens);
                    $tokTempID = 0 - $tokIndex;
                    $this->_tokens[$tokIndex-1]->storeScratchProperty("nonce","tok_".$tokTempID.$parseGUID);
                    $this->_tokens[$tokIndex-1]->storeScratchProperty("cknLine",$ckn.".$lineMask");
                    if (@$heading) {
                      $this->_tokens[$tokIndex-1]->storeScratchProperty("heading",$heading);
                      $heading = null;
                    }
                    //if compound update with new token
                    if (@$cmpIndex){
                      $componentIDs = $this->_compounds[$cmpIndex-1]->getComponentIDs();
                      array_push($componentIDs,"tok:".$tokTempID);
                      $this->_compounds[$cmpIndex-1]->setComponentIDs($componentIDs);
//                      $this->_tokens[$tokIndex-1]->setCompoundIDs(array($cmpTempID));
                    }else if (@$numberToken && @$prevNumberTokIndex){//we have a number Tok with a previous number Tok and no compound
                      //create number compound
                      $curCompound = new Compound();
                      $curCompound->setComponentIDs(array('tok:'.$prevNumberTokTempID,'tok:'.$tokTempID));
                      $curCompound->setVisibilityIDs($vis);
                      $curCompound->setOwnerID($ownerID);
                      if (@$attr){
                        $curCompound->setAttributionIDs($attr);
                      }
                      if(array_key_exists("Compound",$lnCfg) && array_key_exists("scratch",$lnCfg["Compound"])) {
                        foreach ( $lnCfg["Compound"]["scratch"] as $key => $value) {
                          $curCompound->storeScratchProperty($key,$value);
                        }
                      }
                      array_push($this->_compounds,$curCompound);
                      $cmpIndex = count($this->_compounds);
                      $cmpTempID = 0 - $cmpIndex;
                      $this->_compounds[$cmpIndex-1]->storeScratchProperty("nonce","cmp_".$cmpTempID.$parseGUID);
                      $this->_compounds[$cmpIndex-1]->storeScratchProperty("cknLine",$ckn.".$lineMask");
//                      $this->_tokens[$tokIndex-1]->setCompoundIDs(array($cmpTempID));
//                      $this->_tokens[$prevNumberTokIndex-1]->setCompoundIDs(array($cmpTempID));
                      if (@$curStructDivSeqIndex) { //there is a division marker so remove the previous token and add compound id
                        $entityIDs = $this->_sequences[$curStructDivSeqIndex-1]->getEntityIDs();
                        $lastentityGID = array_pop($entityIDs);
                        if ($lastentityGID == "tok:$prevNumberTokTempID"){//when can replace the token with compound globalID
                          array_push($entityIDs,"cmp:".$cmpTempID);
                          $this->_sequences[$curStructDivSeqIndex-1]->setEntityIDs($entityIDs);
                        }else{//we are not in sych raise an error
                          array_push($this->_errors,"division sequence - compound number creation (line $lineMask character $i) last entity $lastentityGID does not match tok:$prevNumberTokTempID"." cfg line # $cfgLnCnt");
                        }
                      }
                      if (@$curQuoteSeqIndex) { //there is a qoute sequence so remove the previous token and add compound id
                        $entityIDs = $curQuoteSequence->getEntityIDs();
                        $lastentityGID = array_pop($entityIDs);
                        if ($lastentityGID == "tok:$prevNumberTokTempID"){//when can replace the token with compound globalID
                          array_push($entityIDs,"cmp:".$cmpTempID);
                          $curQuoteSequence->setEntityIDs($entityIDs);
                        }else{//we are not in sych raise an error
                          array_push($this->_errors,"division sequence - compound number creation (line $lineMask character $i) last entity $lastentityGID does not match tok:$prevNumberTokTempID"." cfg line # $cfgLnCnt");
                        }
                      }
                      if (@$tokLineSeqIndex) { //there is a line seq so remove the previous token and add compound id
                        $entityIDs = $curTokLineSequence->getEntityIDs();
                        $lastentityGID = array_pop($entityIDs);
                        if ( !@$lastentityGID && $prevNumberTokIndex || @$compoundWrapID == $cmpTempID) { // new line sequence and a wrapping number compound
                          $compoundWrapID = $cmpTempID; // track the compound and add token
                          array_push($entityIDs,"tok:".$tokTempID);
                          $curTokLineSequence->setEntityIDs($entityIDs);
                        } else if ($lastentityGID == "tok:$prevNumberTokTempID"){//when can replace the token with compound globalID
                          array_push($entityIDs,"cmp:".$cmpTempID);
                          $curTokLineSequence->setEntityIDs($entityIDs);
                        }else{//we are not in sych raise an error
                          array_push($this->_errors,"line sequence - compound number creation (line $lineMask character $i) last entity $lastentityGID does not match tok:$tokTempID"." cfg line # $cfgLnCnt");
                        }
                      }
                    }else if (@$tokLineSeqIndex || @$curStructDivSeqIndex || $curQuoteSeqIndex) { //there is a division marker and not in compound so add token to sequence
                      if (@$curStructDivSeqIndex) {
                        $entityIDs = $this->_sequences[$curStructDivSeqIndex-1]->getEntityIDs();
                        array_push($entityIDs,"tok:".$tokTempID);
                        $this->_sequences[$curStructDivSeqIndex-1]->setEntityIDs($entityIDs);
                      }
                      if (@$curQuoteSeqIndex) {
                        $entityIDs = $curQuoteSequence->getEntityIDs();
                        array_push($entityIDs,"tok:".$tokTempID);
                        $curQuoteSequence->setEntityIDs($entityIDs);
                      }
                      if (@$tokLineSeqIndex) {
                        $entityIDs = $curTokLineSequence->getEntityIDs();
                        array_push($entityIDs,"tok:".$tokTempID);
                        $curTokLineSequence->setEntityIDs($entityIDs);
                      }
                    }
                  }else{// update Token with grapheme
                    $graphemes = $this->_tokens[$tokIndex-1]->getGraphemeIDs();
                    if (@$carrierTempID) {
                      array_push($graphemes,$carrierTempID);
                      $str = "ʔ".$str;
                    }
                    array_push($graphemes,$graTempID);
                    $this->_tokens[$tokIndex-1]->setGraphemeIDs($graphemes);
                    $curToken->setToken($curToken->getToken().$str);
                  }
                  //if segState is start need set state to current grapheme type
                  $carrierTempID = null;
                  $segState = $typ;
                }else{//still in same syllable and seg
                  // update seg with new grapheme index
                  $segPos = $this->_segments[$segIndex -1]->getStringPos();
                  $segPos[0][1] = mb_strlen($transcription);
                  $this->_segments[$segIndex -1]->setStringPos($segPos);
                  // update scl with new grapheme
                  $graphemes = $this->_syllableClusters[$sclIndex-1]->getGraphemeIDs();
                  array_push($graphemes,$graTempID);
                  $this->_syllableClusters[$sclIndex-1]->setGraphemeIDs($graphemes);
                  if(@!$tokIndex){// no token so must be intra syllable split
                    $curToken = new Token();
                    $curToken->setGraphemeIDs(array($graTempID));
                    $curToken->setToken($str);
                    $curToken->setVisibilityIDs($vis);
                    $curToken->setOwnerID($ownerID);
                    if (@$attr){
                      $curToken->setAttributionIDs($attr);
                    }
                    if(array_key_exists("Token",$lnCfg) && array_key_exists("scratch",$lnCfg["Token"])) {
                      foreach ( $lnCfg["Token"]["scratch"] as $key => $value) {
                        $curToken->storeScratchProperty($key,$value);
                      }
                    }
                    array_push($this->_tokens,$curToken);
                    $tokIndex = count($this->_tokens);
                    $tokTempID = 0 - $tokIndex;
                    $this->_tokens[$tokIndex-1]->storeScratchProperty("nonce","tok_".$tokTempID.$parseGUID);
                    $this->_tokens[$tokIndex-1]->storeScratchProperty("cknLine",$ckn.".$lineMask");
                    if (@$curStructDivSeqIndex && !@$cmpIndex) { //there is a structural division marker and not in compound so add token to sequence
                        $entityIDs = $this->_sequences[$curStructDivSeqIndex-1]->getEntityIDs();
                        array_push($entityIDs,"tok:".$tokTempID);
                        $this->_sequences[$curStructDivSeqIndex-1]->setEntityIDs($entityIDs);
                    }
                    if (@$curQuoteSeqIndex && !@$cmpIndex) { //there is a quote sequence and not in compound so add token to sequence
                        $entityIDs = $curQuoteSequence->getEntityIDs();
                        array_push($entityIDs,"tok:".$tokTempID);
                        $curQuoteSequence->setEntityIDs($entityIDs);
                    }
                    if (@$tokLineSeqIndex && !@$cmpIndex) { //there is a tokenisation sequence and not in compound so add token to sequence
                        $entityIDs = $curTokLineSequence->getEntityIDs();
                        array_push($entityIDs,"tok:".$tokTempID);
                        $curTokLineSequence->setEntityIDs($entityIDs);
                    }
                  }else{// update token with new grapheme
                    $graphemes = $this->_tokens[$tokIndex-1]->getGraphemeIDs();
                    array_push($graphemes,$graTempID);
                    $this->_tokens[$tokIndex-1]->setGraphemeIDs($graphemes);
                    $this->_tokens[$tokIndex-1]->setToken($this->_tokens[$tokIndex-1]->getToken().$str);
                  }
                }
                if ($segState == "M") {// syllable ending
                  $segState = "S"; //signal new syllable
                } else if ($segState == "E") {
                  array_push($this->_errors,"$char created illegal syllable at character $i in linemask $lineMask of $ckn syllable id(".mb_substr(mb_strstr($curSyllable->getScratchProperty("nonce"),"#",true),5).") cfg line # $cfgLnCnt");
                  if ($this->_breakOnError) {
                    return;
                  }
                }
              }else{
                array_push($this->_errors,"found unknown transcription symbol $char at character $i in linemask $lineMask of $ckn not found in grapheme character map"." cfg line # $cfgLnCnt");
                if ($this->_breakOnError) {
                  return;
                }
              }
              $i += $inc;//adjust read pointer
              $char = $char2 = $char3 = $char4 = null;
          }// switch
        }//for each char
        // if not complete syllable add error for eol incomplete syllable.
        if ($segState == "C") {
          array_push($this->_errors,"In complete syllable at end of line at character $i in linemask $lineMask of $ckn cfg line # $cfgLnCnt");
        }
        // update baseline
        $this->_baselines[$blnIndex-1]->setTranscription($transcription);
        $graIndex = null;
        $segIndex = null;
        $sclIndex = null;
        if (@$numberToken && @$tokIndex) { //number token so save preTok info for processing numbers cross line
          $prevNumberTokIndex = $tokIndex;
          $prevNumberTokTempID = $tokTempID;
        }
        if (!@$lineWrap) {
          $tokIndex = null;
          //numbers might wrap a line and won't use a wrap symbol
          //could also be at a compound token boundary
          // if not then end compound
          if (!@$numberToken && !@$atCompoundSeparator) {
            $cmpIndex = null;
          }
        }
      }//for each line
    }

  //********GETTERS*********

    /**
    * Baselines - array of baselines from the current parse
    *
    * @return array of Baselines objects
    */
    public function getBaselines() {
      return $this->_baselines;
    }

    /**
    * Lines - array of lines from the current parse
    *
    * @return array of Lines objects
    */
    public function getLines() {
      return $this->_lines;
    }

    /**
    * Spans - array of spans from the current parse
    *
    * @return array of Spans objects
    */
    public function getSpans() {
      return $this->_spans;
    }

    /**
    * Runs - array of runs from the current parse
    *
    * @return array of Runs objects
    */
    public function getRuns() {
      return $this->_runs;
    }

    /**
    * Graphemes - array of graphemes from the current parse line
    *
    * @return array of Grapheme objects
    */
    public function getGraphemes() {
      return $this->_graphemes;
    }

    /**
    * Segments - array of segments from the current parse line
    *
    * @return array of Segment objects
    */
    public function getSegments() {
      return $this->_segments;
    }

    /**
    * Sequences - array of sequences from the current text
    *
    * @return array of Sequence objects
    */
    public function getSequences() {
      return $this->_sequences;
    }

    /**
    * Editions - array of editions from the current text
    *
    * @return array of Edition objects
    */
    public function getEditions() {
      return $this->_editions;
    }

    /**
    * SyllableClusters - array of syllableClusters from the current parse line
    *
    * @return array of SyllableCluster objects
    */
    public function getSyllableClusters() {
      return $this->_syllableClusters;
    }

    /**
    * Gets the break on error boolean value
    * @return boolean where the parser breaks on error
    */
    public function getBreakOnError() {
      return $this->_breakOnError;
    }

    /**
    * Gets the error strings
    * @return array of error strings from parsing
    */
    public function getErrors() {
      return $this->_errors;
    }

    /**
    * Tokens - array of tokens from the current parse line
    *
    * @return array of Token objects
    */
    public function getTokens() {
      return $this->_tokens;
    }

    /**
    * Compounds - array of compounds from the current parse line
    *
    * @return array of Compound objects
    */
    public function getCompounds() {
      return $this->_compounds;
    }

    /**
    * Gets the configurations array
    * @return array object config containing configuration metadata for each transliterated script
    */
    public function getConfigs() {
      return $this->_configs;
    }

    /**
    * Gets the termID for parentterm - term string
    * @return int  trmID if found or null
    */
    public function getTermID($parentterm_term) {
      return (array_key_exists($parentterm_term,$this->_termLookups)?$this->_termLookups[$parentterm_term]:null);
    }

  //********SETTERS*********

    /**
    * Sets the configurations array
    * @param array object $configs containing configuration metadata for each transliterated script
    */
    public function setConfigs($configs) {
      $this->_configs = $configs;
    }

    /**
    * Sets the break on error boolean value
    * @return boolean where the parser breaks on error
    */
    public function setBreakOnError($break = true) {
      $this->_breakOnError = $break;
    }

  //*******************************PRIVATE FUNCTIONS************************************
    /**
    * getNextSegmentState - state engine for segmenting a stream of grapheme types
    *
    * segmentation use the following state transitions
    * where  S = startSeg, C =Consonant, V = Vowel, VM = V modifier, P = Punctuation,
    *        D = Digit, E = Error and . = missing C or V
    * S(C)→C(C)→CC(V)→CCV(~VM)→S
    * S(C)→C(C)→CC(V)→CCV(VM)→VM(~VM)→S
    * S(C)→C(C)→CC(.)→CC.(~VM)→S
    * S(C)→C(C)→CC(.)→CC.(VM)→VM(~VM)→S
    * S(C)→C(V)→CV(~VM)→S
    * S(C)→C(V)→CV(VM)→VM(~VM)→S
    * S(C)→C(.)→C.(VM)→VM(~VM)→S
    * S(C)→C(.)→C.→S(~VM)
    * S(V)→V(~VM)→S
    * S(.)→.(~VM && ~V)→S
    * S(.)→.(V)→V(~VM)→S
    * S(.)→.(V)→V(VM)→VM→
    * S(.)→.(VM)→VM(~VM)→S
    * S(V)→V(VM)→VVM(~VM)→S
    * S(P)→P(~VM)→S
    * S(D)→D(~VM)→S
    * S→*→.→E(.)
    * S→*→VM→E(VM)
    * S→E(VM)
    * S→CC→E(VM|P|D|.|C)
    *
    * Flatten Transissions
    * S(C)→C
    * S(V)→V
    * S(.)→V
    * S(P)→P
    * S(D)→D
    * S(O)→O
    * C(C)→C
    * C(_)→C
    * C(V)→V
    * C(.)→V
    * V(~M)→S
    * V(M)→M
    * M(~M)→S
    * P(~M)→S
    * D(~M)→S
    * O(~M)→S
    * M(M)→E
    * P(M)→E
    * D(M)→E
    * O(M)→E
    * M(M)→E
    * S(M)→E
    * C(M|P|D|O)→E
    *
    * @param string $curState indicates the current state of segmentation
    * @param string $nextType indicates the type of the next grapheme in sequence
    * @return string indicating the transitioned to state
    */  //DEPRECATED
/*    private function getNextSegmentState($curState,$nextType) {
      switch ($curState) {
        case "S"://start
          if ($nextType == "M") return "E";
          else return $nextType;
          break;
        case "C"://consonant
          if ($nextType == "V" || $nextType == "C") return $nextType;
          return "E";
          break;
        case "V"://vowel
          if ($nextType == "M") return "M";
          return "S";
          break;
        case "M"://vowel modifier
          if ($nextType == "M") return "E";
          return $nextType;
          break;
        case "P"://Punctuation
        case "N"://Digit
        case "O"://Other
          if ($nextType == "M") return "E";
          return "S";
          break;
        default:
          return "E";
      }
    }*/
}

/**
* Creates a parser configuration array from given parameters
*
* <code>
* $parseConfig = array(
*    "visibilityIDs" => "{}",//security
*    "attributionIDs" => "{}",//source
*    "ckn" => "",//catid
*    "AzesTRID" => "", //n_text:id
*    "Baseline" => array(
*        "scratch" => array(
*              "CKN" => "",//catid
*              "AzesTRID" => "",//id
*              "srfDesc" => "",//if side
*              "textid" => "")// if textid
*      ),
*    "Line" => array(
*        "mask" => "",
*        "order" => "",
*        "scratch" => array(
*              "CKN" => "",//catid
*              "AzesTRID" => "")//id
*      ),
*    "Run" => array(// if scribe info
*        "scribe" => "",
*        "scratch" => array(
*              "CKN" => "",//catid
*              "AzesTRID" => "")//id
*      ),
*    "transliteration" => ""//edition
*);
* </code>
* @param $ownerID int indetifying the owner for entities created
* @param $vis mixed array|string listing the visibility group ids for this line
* @param $attr mixed array|string listing the attribution ids for this line
* @param $ckn string label for the text
* @param $trid string identifying the table:id this metadata comes from
* @param $side string indicating the side of the artefact the script is on
* @param $txtid string mark this text line
* @param $mask string label used to identify the line
* @param $order int identifying the line order within the text
* @param $scribe string identifying the scribe of this line
* @param $edition string of an editors transliterated line
* @return array of parser configuration metadata
*/
function createParserConfig($ownerID = 4,$vis,$attr,$ckn,$trid,$ktxtid,$textid,$mask,$order,$scribe,$edition,$side = null,$part = null,$fragment = null,$ednDescrip = null) {
  $parseConfig = array();
  $parseConfig["ownerID"] =$ownerID;
  $parseConfig["visibilityIDs"] =$vis;
  $parseConfig["attributionIDs"] = $attr;
  $parseConfig["ckn"] = $ckn;
  if($ktxtid){
    $parseConfig["txtid"] = $ktxtid;
  }
  $parseConfig["AzesTRID"] = $trid;
  $parseConfig["Baseline"] = array( "scratch" => array(
                                          "CKN" => $ckn,
                                          "AzesTRID" => $trid));
  if($ednDescrip){
    $parseConfig["editionDescription"] = $ednDescrip;
  }
  if($side){
    $parseConfig["Baseline"]["scratch"]["srfDesc"] = $side;
  }
  if($part){
    $parseConfig["Baseline"]["scratch"]["prtDesc"] = $part;
  }
  if($fragment){
    $parseConfig["Baseline"]["scratch"]["frgDesc"] = $fragment;
  }
  if($textid){
    $parseConfig["Baseline"]["scratch"]["textid"] = $textid;
  }
  $parseConfig["Line"] = array(
                              "mask" => $mask,
                              "order" => $order,
                              "scratch" => array(
                                          "CKN" => $ckn,
                                          "AzesTRID" => $trid));
  $parseConfig["Token"] = array("scratch" => array(
                                          "order" => $order,
                                          "CKN" => $ckn,
                                          "AzesTRID" => $trid));
  $parseConfig["Compound"] = array("scratch" => array(
                                          "order" => $order,
                                          "CKN" => $ckn,
                                          "AzesTRID" => $trid));
  if($scribe){
    $parseConfig["Run"] = array(
                              "scribe" => $scribe,
                              "scratch" => array(
                                          "CKN" => $ckn,
                                          "AzesTRID" => $trid));
  }
  $parseConfig["transliteration"] = $edition;
  return $parseConfig;
}

?>
