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
  * utilRML
  *
  *  Utility functions for creating ReadML for the data of a text edition.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  RML
  */

  //  if(!defined("DBNAME")) define("DBNAME","kanishka");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/xmlNodeHelper.php');//XML helper functions
  require_once (dirname(__FILE__) . '/../model/entities/Editions.php');
  require_once (dirname(__FILE__) . '/../model/entities/Texts.php');
  require_once (dirname(__FILE__) . '/../model/entities/TextMetadatas.php');
  require_once (dirname(__FILE__) . '/../model/entities/Surfaces.php');
  require_once (dirname(__FILE__) . '/../model/entities/Baselines.php');
  require_once (dirname(__FILE__) . '/../model/entities/Fragments.php');
  require_once (dirname(__FILE__) . '/../model/entities/Parts.php');
  require_once (dirname(__FILE__) . '/../model/entities/Items.php');
  require_once (dirname(__FILE__) . '/../model/entities/Compounds.php');
  require_once (dirname(__FILE__) . '/../model/entities/Tokens.php');
  require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
  require_once (dirname(__FILE__) . '/../model/entities/Segments.php');
  require_once (dirname(__FILE__) . '/../model/entities/SyllableClusters.php');

  $prefixToTableName = array(
            "col" => "collection",
            "itm" => "item",
            "prt" => "part",
            "frg" => "fragment",
            "img" => "image",
            "spn" => "span",
            "srf" => "surface",
            "txt" => "text",
            "tmd" => "textMetadata",
            "bln" => "baseline",
            "seg" => "segment",
            "run" => "run",
            "lin" => "line",
            "scl" => "syllableCluster",
            "gra" => "grapheme",
            "tok" => "token",
            "cmp" => "compound",
            "lem" => "lemma",
            "per" => "person",
            "trm" => "term",
            "cat" => "catelog",
            "seq" => "sequence",
            "lnk" => "link",
            "edn" => "edition",
            "bib" => "bibliography",
            "ano" => "annotation",
            "atb" => "attribution",
            "dat" => "date",
            "era" => "era");

// setup varibles and lookups
  $dbMgr = new DBManager();
  $dbMgr->query("select trm_labels::hstore->'en' as engLabel,trm_id,trm_labels,
                        trm_parent_id, trm_type_id, trm_list_ids,trm_code
                from term");
  $entityLookup['term'] = array('idByCode' =>array(),'byID' => array());
  while($row = $dbMgr->fetchResultRow()){
    if ($row['trm_code']) {
      $entityLookup['term']['idByCode'][$row['trm_code']] = $row;
    }
    $entityLookup['term']['byID'][$row['trm_id']] = $row;
  }

  $fullnameLookup = array();
  $dbMgr->query("select ugr_id,concat(ugr_given_name,' ', ugr_family_name) as ugr_fullname
                  from usergroup where ugr_given_name is not null or ugr_family_name is not null;");
  while($row = $dbMgr->fetchResultRow()){
    $fullnameLookup[$row['ugr_id']] = $row['ugr_fullname'];
  }
  $errorMsg = null;

  function calcEditionRML($ednID, $ckn) {
    global $errorMsg;

    $txtID = null;
    $text = null;
    $edition = null;
    $editions = null;
    if ($ednID) {
      $edition = new Edition($ednID);
      if ($edition->hasError()) {
        $errorMsg = "<error>Error while loading edition($ednID) - ".join(",",$edition->getErrors())."</error>";
        return;
      } else {
        $txtID = $edition->getTextID();
        if ($txtID) {
          $text = new Text($txtID);
          if ($text->hasError()) {
            $errorMsg = "<error>Error while loading text($txtID) - ".join(",",$text->getErrors())."</error>";
            return;
          } else {
            $editions = array($edition);
          }
        }
      }
    }

    if (!$text && $ckn) {
      // get ckn parameter data
      $condition = "";
      if (is_array($ckn)) {// multiple text
        $condition = "txt_ckn in ('".strtoupper(join("','",$ckn))."') ";
      }else if (is_string($ckn)) {
        $condition = "";
        if (strpos($ckn,",")) {//string of multiple ckn's
        $condition = "txt_ckn in (".strtoupper($ckn).") ";
        }else {
        $condition = "txt_ckn = '".strtoupper($ckn)."' ";
        }
      }

      //get text entity
      $texts = new Texts($condition);
      $text = $texts->current();//only designed to ouput RML for a single text.
      if (!$text || !$text->getID()) {
        $errorMsg = "<error>Texts for condition '$condition' not found aborting RML generation.</error>";
        return;
      }

      //get all related edition ids
      $txtID = $text->getID();
      $editions = $surfaces = $fragments = $parts = $items = $baselines = $textmetadatas = null;
      //get all editions
      $editions = new Editions("edn_text_id = $txtID",'edn_id',null,null);//null offset and limit will get all in case of no condition
    }

    if (!$editions || is_array($editions) && count($editions) == 0) {
      $errorMsg = "<error>No editions available for Text ($txtID) ".($text?$text->getCKN():"").", aborting RML generation.</error>";
      return;
    }

  //get all surfaces
    $surfaces = new Surfaces("$txtID = ANY (\"srf_text_ids\")",'srf_id',null,null);//null offset and limit will get all in case of no condition
    if ($surfaces && $surfaces->getCount() > 0) {
      $srfIDs = array();
      $frgIDs = array();
      foreach($surfaces as $surface){
        array_push($srfIDs,$surface->getID());
        if ($surface->getFragmentID()) {
          array_push($frgIDs,$surface->getFragmentID());
        }
      }
      $surfaces->rewind();
    }

  // get all fragments
    if (count($frgIDs)) {
      $fragments = new Fragments('frg_id in ('.join(",",$frgIDs).')','frg_id',null,null);//null offset and limit will get all in case of no condition
      $prtIDs = array();
      foreach($fragments as $fragment){
        if ($fragment->getPartID()) {
          array_push($prtIDs,$fragment->getPartID());
        }
      }
      $fragments->rewind();
    }

  // get all parts
    if (count($prtIDs)) {
      $parts = new Parts('prt_id in ('.join(",",$prtIDs).')','prt_id',null,null);//null offset and limit will get all in case of no condition
      $itmIDs = array();
      foreach($parts as $part){
        if ($part->getItemID()) {
          array_push($itmIDs,$part->getItemID());
        }
      }
      $parts->rewind();
    }

  // get all items
    if (count($itmIDs)) {
      $items = new Items('itm_id in ('.join(",",$itmIDs).')','itm_id',null,null);//null offset and limit will get all in case of no condition
    }

    // get all baselines
    if (count($srfIDs)) {
      $baselines = new Baselines('bln_surface_id in ('.join(",",$srfIDs).')','bln_id',null,null);//null offset and limit will get all in case of no condition
    }

  //get all textMetadatas
    if ($txtID) {
      $textmetadatas = new TextMetadatas("tmd_text_id = $txtID",'tmd_id',null,null);//null offset and limit will get all in case of no condition
    }

  //$condition = "$txtID = ANY (\"srf_text_ids\")";
  //start generating RML
    $RML = startRML(null,false);
    $RML .= openXMLNode('entities');

    if ($text) {
      $RML .= getTextRML($text);
    }

    if ($textmetadatas && $textmetadatas->getCount() > 0) {
      foreach($textmetadatas as $textmetadata){
        $RML .= getTextMetadataRML($textmetadata);
      }
    }

    if ($surfaces && $surfaces->getCount() > 0) {
      foreach($surfaces as $surface){
    //    $RML .= getSurfaceRML($surface);
      }
    }

    if ($fragments && $fragments->getCount() > 0) {
      foreach($fragments as $fragment){
    //    $RML .= getFragmentRML($fragment);
      }
    }

    if ($parts && $parts->getCount() > 0) {
      foreach($parts as $part){
    //    $RML .= getPartRML($part);
      }
    }

    if ($items && $items->getCount() > 0) {
      foreach($items as $item){
    //    $RML .= getItemRML($item);
      }
    }

    if ($baselines && $baselines->getCount() > 0) {
      foreach($baselines as $baseline){
    //    $RML .= getBaselineRML($baseline);
      }
    }

    foreach($editions as $edition){
      $RML .= getEditionRML($edition);
    }

    $RML .= closeXMLNode('entities');
    $RML .= endRML();

    return $RML;
    // end of calcEditionRML
  }

  function getEditionRML($edition){
    global $entityLookup;
    if (!array_key_exists('edition', $entityLookup)){
      $entityLookup['edition'] = array();
    }
    if (in_array($edition->getID(), $entityLookup['edition'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('edition',array('id' => $edition->getID(), 'modified' => $edition->getModificationStamp()));
    if ($edition->getDescription()) {
      $label = $entityLookup['term']['idByCode']['edn_description']['englabel'];
      $label = ($label?$label:'description');
      $rml .= makeXMLNode($label,null,$edition->getDescription());
    }
    $seqRML = "";
    if (count($edition->getSequenceIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['edn_sequence_ids']['englabel'];
      $label = ($label?$label:'sequences');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($edition->getSequenceIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'sequence','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $edition->getSequences(true) as $sequence) {
        $seqRML .= getSequenceRML($sequence);
      }
    }
 /*   $tmdRML = "";
    if ($edition->getTextMetadataID()) {
      $label = $entityLookup['term']['idByCode']['edn_textmetadata_id']['englabel'];
      $label = ($label?$label:'textmetadata');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'textmetadata','id'=>$edition->getTextMetadataID()));
      $rml .= closeXMLNode($label);
      $tmdRML = getTextMetadataRML($edition->getTextMetadata(true));
    }*/
    $rml .= getAnnotationLinksRML('edn',$edition->getAnnotationIDs());
    $rml .= getAttributionLinksRML('edn',$edition->getAttributionIDs());
    $rml .= getOwnerRML('edn',$edition->getOwnerID());
    $rml .= getVisibilityRML('edn',$edition->getVisibilityIDs());
    $rml .= closeXMLNode('edition');
    array_push($entityLookup['edition'],$edition->getID());
    $rml .= $seqRML;//.$tmdRML;
    if (count($edition->getAttributionIDs()) > 0){
      foreach($edition->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getTextMetadataRML($textmetadata){
    global $entityLookup;
    if (!array_key_exists('textmetadata', $entityLookup)){
      $entityLookup['textmetadata'] = array();
    }
    if (in_array($textmetadata->getID(), $entityLookup['textmetadata'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('textmetadata',array('id' => $textmetadata->getID(), 'modified' => $textmetadata->getModificationStamp()));
    $txtRML = "";
    if ($textmetadata->getTextID()) {
      $label = $entityLookup['term']['idByCode']['tmd_text_id']['englabel'];
      $label = ($label?$label:'text');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'text','id'=>$textmetadata->getTextID()));
      $rml .= closeXMLNode($label);
      $txtRML = getTextRML($textmetadata->getText(true));
    }
    if (count($textmetadata->getTypeIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['tmd_type_ids']['englabel'];
      $label = ($label?$label:'types');
      $rml .= openXMLNode($label);
      foreach($textmetadata->getTypeIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    $refRML = "";
    if (count($textmetadata->getReferenceIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['tmd_reference_ids']['englabel'];
      $label = ($label?$label:'references');
      $rml .= openXMLNode($label);
      foreach($textmetadata->getReferenceIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'attribution','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $textmetadata->getReferences(true) as $attribution) {
        $refRML .= getAttributionRML($attribution);
      }
    }
    $rml .= getAnnotationLinksRML('tmd',$textmetadata->getAnnotationIDs());
    $rml .= getAttributionLinksRML('tmd',$textmetadata->getAttributionIDs());
    $rml .= getOwnerRML('tmd',$textmetadata->getOwnerID());
    $rml .= getVisibilityRML('tmd',$textmetadata->getVisibilityIDs());
    $rml .= closeXMLNode('textmetadata');
    array_push($entityLookup['textmetadata'],$textmetadata->getID());
    $rml .= $txtRML.$refRML;
    if (count($textmetadata->getAttributionIDs()) > 0){
      foreach($textmetadata->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getTextRML($text){
    global $entityLookup;
    if (!array_key_exists('text', $entityLookup)){
      $entityLookup['text'] = array();
    }
    if (in_array($text->getID(), $entityLookup['text'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('text',array('id' => $text->getID(), 'modified' => $text->getModificationStamp()));
    if ($text->getCKN()) {
      $label = $entityLookup['term']['idByCode']['txt_ckn']['englabel'];
      $label = ($label?$label:'CKN');
      $rml .= makeXMLNode($label,null,$text->getCKN());
    }
    if ($text->getTitle()) {
      $label = $entityLookup['term']['idByCode']['txt_title']['englabel'];
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$text->getTitle());
    }
    $txtRML = "";
    if (count($text->getReplacementIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['txt_replacement_ids']['englabel'];
      $label = ($label?$label:'replacementtexts');
      $rml .= openXMLNode($label);
      foreach($text->getReplacementIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'text','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $text->getReplacements(true) as $replacement) {
        $txtRML .= getTextRML($replacement);
      }
    }
    if (count($text->getTypeIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['txt_type_ids']['englabel'];
      $label = ($label?$label:'types');
      $rml .= openXMLNode($label);
      foreach($text->getTypeIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    $refRML = "";
    if (count($text->getEditionReferenceIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['txt_edition_ref_ids']['englabel'];
      $label = ($label?$label:'references');
      $rml .= openXMLNode($label);
      foreach($text->getEditionReferenceIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'attribution','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $text->getEditionReference(true) as $attribution) {
        $refRML .= getAttributionRML($attribution);
      }
    }
    $imgRML = "";
    if (count($text->getImageIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['txt_image_ids']['englabel'];
      $label = ($label?$label:'images');
      $rml .= openXMLNode($label);
      foreach($text->getImageIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $text->getImages(true) as $image) {
//        $imgRML .= getImageRML($image);
      }
    }
    $rml .= getAnnotationLinksRML('txt',$text->getAnnotationIDs());
    $rml .= getAttributionLinksRML('txt',$text->getAttributionIDs());
    $rml .= getOwnerRML('txt',$text->getOwnerID());
    $rml .= getVisibilityRML('txt',$text->getVisibilityIDs());
    $rml .= closeXMLNode('text');
    array_push($entityLookup['text'],$text->getID());
    $rml .= $txtRML.$refRML.$imgRML;
    if (count($text->getAttributionIDs()) > 0){
      foreach($text->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getSequenceRML($sequence,$ord = null){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('sequence', $entityLookup)){
      $entityLookup['sequence'] = array();
    }
    if (in_array($sequence->getID(), $entityLookup['sequence'])){
      // output error log message ??
     return "";
    }
    $attribs = array('id' => $sequence->getID(), 'modified' => $sequence->getModificationStamp());
    if ($ord != null) {
      $attribs["ord"] = $ord;
    }
    $rml = openXMLNode('sequence',$attribs);
    if ($sequence->getLabel()) {
      $label = $entityLookup['term']['idByCode']['seq_label']['englabel'];
      $label = ($label?$label:'label');
      $rml .= makeXMLNode($label,null,$sequence->getLabel());
    }
    if ($sequence->getSuperScript()) {
      $label = $entityLookup['term']['idByCode']['seq_superscript']['englabel'];
      $label = ($label?$label:'label');
      $rml .= makeXMLNode($label,null,$sequence->getSuperScript());
    }
    if ($sequence->getTypeID()) {
      $label = $entityLookup['term']['idByCode']['seq_type_id']['englabel'];
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$sequence->getTypeID()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$sequence->getTypeID(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    $seqRML = "";
    if (count($sequence->getEntityIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['seq_entity_ids']['englabel'];
      $label = ($label?$label:'components');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($sequence->getEntityIDs() as $gid) {
        $prefix = substr($gid,0,3);
        $id = substr($gid,4);
        $rml .= makeXMLNode('link',array('entity' => $prefixToTableName[$prefix],'id'=>$id));
      }
      $rml .= closeXMLNode($label);
      $ord = 1;
      foreach( $sequence->getEntities(true) as $entity) {
        $seqRML .= getEntityRML($entity, $ord++);
      }
    } else if ($sequence->getScratchProperty('freetext')) {
      $rml .= makeXMLNode('freetext',null,$sequence->getScratchProperty('freetext'));
    }
    // todo add code for theme and number (discuss the need for number)
    $rml .= getAnnotationLinksRML('seq',$sequence->getAnnotationIDs());
    $rml .= getAttributionLinksRML('seq',$sequence->getAttributionIDs());
    $rml .= getOwnerRML('seq',$sequence->getOwnerID());
    $rml .= getVisibilityRML('seq',$sequence->getVisibilityIDs());
    $rml .= closeXMLNode('sequence');
    array_push($entityLookup['sequence'],$sequence->getID());
    $rml .= $seqRML;
    if (count($sequence->getAttributionIDs()) > 0){
      foreach($sequence->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getCompoundRML($compound){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('compound', $entityLookup)){
      $entityLookup['compound'] = array();
    }
    if (in_array($compound->getID(), $entityLookup['compound'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('compound',array('id' => $compound->getID(), 'modified' => $compound->getModificationStamp()));
    if ($compound->getValue()) {
      $label = $entityLookup['term']['idByCode']['cmp_value']['englabel'];
      $label = ($label?$label:'value');
      $rml .= makeXMLNode($label,null,$compound->getValue());
    }
    if ($compound->getTranscription()) {
      $label = $entityLookup['term']['idByCode']['cmp_transcription']['englabel'];
      $label = ($label?$label:'transcription');
      $rml .= makeXMLNode($label,null,$compound->getTranscription());
    }
    if (count($compound->getComponentIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['cmp_component_ids']['englabel'];
      $label = ($label?$label:'components');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($compound->getComponentIDs() as $gid) {
        $prefix = substr($gid,0,3);
        $id = substr($gid,4);
        $rml .= makeXMLNode('link',array('entity' => $prefixToTableName[$prefix],'id'=>$id));
      }
      $rml .= closeXMLNode($label);
      $cpnRML = "";
      foreach( $compound->getComponents(true) as $entity) {
        $cpnRML .= getEntityRML($entity);
      }
    }
    //todo add code for classification class and type
    if ($compound->getSortCode() || $compound->getSortCode2()) {
      $rml .= openXMLNode('sortcode');
      $rml .= makeXMLNode('primary',null,$compound->getSortCode());
      $rml .= makeXMLNode('secondary',null,$compound->getSortCode2());
      $rml .= closeXMLNode('sortcode');
    }

    $rml .= getAnnotationLinksRML('cmp',$compound->getAnnotationIDs());
    $rml .= getAttributionLinksRML('cmp',$compound->getAttributionIDs());
    $rml .= getOwnerRML('cmp',$compound->getOwnerID());
    $rml .= getVisibilityRML('cmp',$compound->getVisibilityIDs());
    $rml .= closeXMLNode('compound');
    array_push($entityLookup['compound'],$compound->getID());
    $rml .= $cpnRML;
    if (count($compound->getAttributionIDs()) > 0){
      foreach($compound->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getTokenRML($token){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('token', $entityLookup)){
      $entityLookup['token'] = array();
    }
    if (in_array($token->getID(), $entityLookup['token'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('token',array('id' => $token->getID(), 'modified' => $token->getModificationStamp()));
    if ($token->getValue()) {
      $label = $entityLookup['term']['idByCode']['tok_value']['englabel'];
      $label = ($label?$label:'value');
      $rml .= makeXMLNode($label,null,$token->getValue());
    }
    if ($token->getTranscription()) {
      $label = $entityLookup['term']['idByCode']['tok_transcription']['englabel'];
      $label = ($label?$label:'transcription');
      $rml .= makeXMLNode($label,null,$token->getTranscription());
    }
    if ($token->getTranslation()) {
      $label = $entityLookup['term']['idByCode']['tok_translation']['englabel'];
      $label = ($label?$label:'translation');
      $rml .= makeXMLNode($label,null,$token->getTranslation());
    }
    $graRML = "";
    if (count($token->getGraphemeIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['tok_grapheme_ids']['englabel'];
      $label = ($label?$label:'graphemes');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($token->getGraphemeIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'grapheme','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $token->getGraphemes(true) as $grapheme) {
        $graRML .= getGraphemeRML($grapheme);
      }
    }
    if ($token->getNominalAffix()) {
      $label = $entityLookup['term']['idByCode']['tok_nom_affix']['englabel'];
      $label = ($label?$label:'affix');
      $rml .= makeXMLNode($label,null,$token->getNominalAffix());
    }
    $cmpRML = "";
    //todo add code for gramatical deconstruction and change data to term ids
    if ($token->getSortCode() || $token->getSortCode2()) {
      $rml .= openXMLNode('sortcode');
      $rml .= makeXMLNode('primary',null,$token->getSortCode());
      $rml .= makeXMLNode('secondary',null,$token->getSortCode2());
      $rml .= closeXMLNode('sortcode');
    }
    $rml .= getAnnotationLinksRML('tok',$token->getAnnotationIDs());
    $rml .= getAttributionLinksRML('tok',$token->getAttributionIDs());
    $rml .= getOwnerRML('tok',$token->getOwnerID());
    $rml .= getVisibilityRML('tok',$token->getVisibilityIDs());
    $rml .= closeXMLNode('token');
    array_push($entityLookup['token'],$token->getID());
    $rml .= $cmpRML.$graRML;
    if (count($token->getAttributionIDs()) > 0){
      foreach($token->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getSyllableClusterRML($syllable){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('syllable', $entityLookup)){
      $entityLookup['syllable'] = array();
    }
    if (in_array($syllable->getID(), $entityLookup['syllable'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('syllable',array('id' => $syllable->getID(), 'modified' => $syllable->getModificationStamp()));
    if ($syllable->getValue()) {
      $rml .= makeXMLNode('value',null,$syllable->getValue());
    }
    $graRML = "";
    if (count($syllable->getGraphemeIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['scl_grapheme_ids']['englabel'];
      $label = ($label?$label:'graphemes');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($syllable->getGraphemeIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'grapheme','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $syllable->getGraphemes(true) as $grapheme) {
        $graRML .= getGraphemeRML($grapheme);
      }
    }
    //todo add code for gramatical deconstruction and change data to term ids
    if ($syllable->getSortCode() || $syllable->getSortCode2()) {
      $rml .= openXMLNode('sortcode');
      $rml .= makeXMLNode('primary',null,$syllable->getSortCode());
      $rml .= makeXMLNode('secondary',null,$syllable->getSortCode2());
      $rml .= closeXMLNode('sortcode');
    }
    $rml .= getAnnotationLinksRML('scl',$syllable->getAnnotationIDs());
    $rml .= getAttributionLinksRML('scl',$syllable->getAttributionIDs());
    $rml .= getOwnerRML('scl',$syllable->getOwnerID());
    $rml .= getVisibilityRML('scl',$syllable->getVisibilityIDs());
    $rml .= closeXMLNode('syllable');
    array_push($entityLookup['syllable'],$syllable->getID());
    $rml .= $graRML;
    if (count($syllable->getAttributionIDs()) > 0){
      foreach($syllable->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getGraphemeRML($grapheme){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('grapheme', $entityLookup)){
      $entityLookup['grapheme'] = array();
    }
    if (in_array($grapheme->getID(), $entityLookup['grapheme'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('grapheme',array('id' => $grapheme->getID(), 'modified' => $grapheme->getModificationStamp()));
    if ($grapheme->getValue()) {
      $label = $entityLookup['term']['idByCode']['gra_grapheme']['englabel'];
      $label = ($label?$label:'grapheme');
      $rml .= makeXMLNode($label,null,$grapheme->getValue());
    }
    // todo code for term ids.
    if ($grapheme->getType()) {
      $label = $entityLookup['term']['idByCode']['gra_type_id']['englabel'];
      $label = ($label?$label:'type');
//      $value = $entityLookup['term']['byID'][$grapheme->getTypeID()]['englabel'];
//      $value = ($value?$value:'');
//      $rml .= openXMLNode($label);
//      $rml .= makeXMLNode('link',array('entity' => 'textmetadata','id'=>$grapheme->getTypeID(),'value' => $value));
      $rml .= makeXMLNode($label,null,$grapheme->getType());
//      $rml .= closeXMLNode($label);
    }
    if ($grapheme->getTextCriticalMark()) {
      $label = $entityLookup['term']['idByCode']['gra_text_critical_mark']['englabel'];
      $label = ($label?$label:'tcm');
      $rml .= makeXMLNode($label,null,$grapheme->getTextCriticalMark());
    }
    if ($grapheme->getAlternative()) {
      $label = $entityLookup['term']['idByCode']['gra_alt']['englabel'];
      $label = ($label?$label:'alternative');
      $rml .= makeXMLNode($label,null,$grapheme->getAlternative());
    }
    if ($grapheme->getEmmendation()) {
      $label = $entityLookup['term']['idByCode']['gra_emmendation']['englabel'];
      $label = ($label?$label:'emmendation');
      $rml .= makeXMLNode($label,null,$grapheme->getEmmendation());
    }
    if ($grapheme->getDecomposition()) {
      $label = $entityLookup['term']['idByCode']['gra_decomposition']['englabel'];
      $label = ($label?$label:'decomposition');
      $rml .= makeXMLNode($label,null,$grapheme->getDecomposition());
    }
    $rml .= getAnnotationLinksRML('gra',$grapheme->getAnnotationIDs());
    $rml .= getAttributionLinksRML('gra',$grapheme->getAttributionIDs());
    $rml .= getOwnerRML('gra',$grapheme->getOwnerID());
    $rml .= getVisibilityRML('gra',$grapheme->getVisibilityIDs());
    $rml .= closeXMLNode('grapheme');
    array_push($entityLookup['grapheme'],$grapheme->getID());
    if (count($grapheme->getAttributionIDs()) > 0){
      foreach($grapheme->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getAttributionRML($attribution){
    global $entityLookup;
    if (!array_key_exists('attribution', $entityLookup)){
      $entityLookup['attribution'] = array();
    }
    if (in_array($attribution->getID(), $entityLookup['attribution'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('attribution',array('id' => $attribution->getID(), 'modified' => $attribution->getModificationStamp()));
    if ($attribution->getTitle()) {
      $label = $entityLookup['term']['idByCode']['atb_title']['englabel'];
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$attribution->getTitle());
    }
    if ($attribution->getDescription()) {
      $label = $entityLookup['term']['idByCode']['atb_description']['englabel'];
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$attribution->getDescription());
    }
    if ($attribution->getBibliographyID()) {
      $label = $entityLookup['term']['idByCode']['atb_bib_id']['englabel'];
      $label = ($label?$label:'bibliography');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'bibliography','id'=>$attribution->getBibliographyID()));
      $rml .= closeXMLNode($label);
//      $bibRML = getBibliographyRML($attribution->getBibliography(true));
    }
    if ($attribution->getGroupID()) {
      $label = $entityLookup['term']['idByCode']['atb_group_id']['englabel'];
      $label = ($label?$label:'group');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'attributiongroup','id'=>$attribution->getGroupID()));
      $rml .= closeXMLNode($label);
//      $grpRML = getBibliographyRML($attribution->getGroup(true));
    }
    if (count($attribution->getTypes()) > 0){
      $label = $entityLookup['term']['idByCode']['atb_types']['englabel'];
      $label = ($label?$label:'types');
      $rml .= makeXMLNode($label,$attribution->getTypes(true));
//      foreach($attribution->getTypes() as $type) {
//        $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$id));
//      }
//      $rml .= closeXMLNode($label);
    }
    if ($attribution->getDetail()) {
      $label = $entityLookup['term']['idByCode']['atb_detail']['englabel'];
      $label = ($label?$label:'detail');
      $rml .= makeXMLNode($label,null,$attribution->getDetail());
    }
    $rml .= getAnnotationLinksRML('atb',$attribution->getAnnotationIDs());
    $rml .= getAttributionLinksRML('atb',$attribution->getAttributionIDs());
    $rml .= getOwnerRML('atb',$attribution->getOwnerID());
    $rml .= getVisibilityRML('atb',$attribution->getVisibilityIDs());
    $rml .= closeXMLNode('attribution');
    array_push($entityLookup['attribution'],$attribution->getID());
//    $rml .= $txtRML.$refRML.$imgRML;
    return $rml;
  }

  function getEntityRML($entity,$ord = null) {
    switch ($entity->getEntityType()) {
      case 'sequence':
        return getSequenceRML($entity,$ord);
      case 'token':
        return getTokenRML($entity);
      case 'compound':
        return getCompoundRML($entity);
      case 'syllablecluster':
        return getSyllableClusterRML($entity);
      default:
        return "";
    }
  }


  //entity RML node helper functions

  function getOwnerRML($prefix,$ownerID) {
  global $fullnameLookup, $entityLookup;
    $rml = "";
    $name = (array_key_exists($ownerID,$fullnameLookup) ? $fullnameLookup[$ownerID] : "unknown");
    $label = $entityLookup['term']['idByCode'][$prefix.'_owner_id']['englabel'];
    $label = ($label?$label:'owner');
    $label2 = $entityLookup['term']['idByCode']['ugr_name']['englabel'];
    $label2 = ($label2?$label2:'name');
    $rml .= openXMLNode($label,array('entity' => 'usergroup'));
    $rml .= makeXMLNode($label2,null,$name);
    $rml .= closeXMLNode($label);
    return $rml;
  }

  function getVisibilityRML($prefix,$visibilityIDs) {
  global $fullnameLookup,$entityLookup;
    $rml = "";
    if (is_array($visibilityIDs) && count($visibilityIDs) > 0){
      $label = $entityLookup['term']['idByCode'][$prefix.'_visibility_ids']['englabel'];
      $label = ($label?$label:'visibility');
      $label2 = $entityLookup['term']['idByCode']['ugr_name']['englabel'];
      $label2 = ($label2?$label2:'fullname');
      $rml .= openXMLNode($label,array('entity' => 'usergroup'));
      foreach ($visibilityIDs as $id) {
        $rml .= makeXMLNode($label2,null,(array_key_exists($id,$fullnameLookup) ? $fullnameLookup[$id] : "unknown"));
      }
      $rml .= closeXMLNode($label);
    }
    return $rml;
  }

  function getAnnotationLinksRML($prefix,$annotationIDs){
    global $entityLookup;
    $rml = "";
    if (is_array($annotationIDs) && count($annotationIDs) > 0){
      $label = $entityLookup['term']['idByCode'][$prefix.'_annotation_ids']['englabel'];
      $label = ($label?$label:'annotations');
      $rml .= openXMLNode($label);
      foreach ($annotationIDs as $id) {//todo expand to output annotation info.
        $rml .= makeXMLNode('link',array('entity' => 'annotation','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    return $rml;
  }

  function getAttributionLinksRML($prefix,$attributionIDs){
    global $entityLookup;
    $rml = "";
    if (is_array($attributionIDs) && count($attributionIDs) > 0){
      $label = $entityLookup['term']['idByCode'][$prefix.'_attribution_ids']['englabel'];
      $label = ($label?$label:'attributions');
      $rml .= openXMLNode($label);
      foreach ($attributionIDs as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'attribution','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    return $rml;
  }
  ?>
