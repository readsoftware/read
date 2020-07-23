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

  $imageType2CODE = array(
                      0=>'UNKNOWN',
                      1=>'GIF',
                      2=>'JPEG',
                      3=>'PNG',
                      4=>'SWF',
                      5=>'PSD',
                      6=>'BMP',
                      7=>'TIFF_II',
                      8=>'TIFF_MM',
                      9=>'JPC',
                      10=>'JP2',
                      11=>'JPX',
                      12=>'JB2',
                      13=>'SWC',
                      14=>'IFF',
                      15=>'WBMP',
                      16=>'XBM',
                      17=>'ICO',
                      18=>'COUNT');

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
  $ugrUserTypeID = Entity::getIDofTermParentLabel('user-grouptype');
  $ugrGroupTypeID = Entity::getIDofTermParentLabel('group-grouptype');
  $groupMemberNamesQuery =
    "select * from (select g.ugr_id as id,g.ugr_name as alias,string_agg(concat(u.ugr_name,':',u.ugr_given_name,' ', u.ugr_family_name),',') as names ".
    "from usergroup as g left join usergroup as u on u.ugr_id = ANY(g.ugr_member_ids) ".
    "where g.ugr_type_id = $ugrGroupTypeID and (u.ugr_given_name is not null or u.ugr_family_name is not null) ".
    "group by g.ugr_id ".
    "union ".
    "select ugr_id as id, ugr_name as alias, concat(ugr_given_name,' ', ugr_family_name) as names ".
    "from usergroup ".
    "where ugr_type_id = $ugrUserTypeID and (ugr_given_name is not null or ugr_family_name is not null) or ".
          "ugr_name in ('Public','Users')) ugr ".
    "order by id";
  $fullnameLookup = array();
  $dbMgr->query($groupMemberNamesQuery);
  while($row = $dbMgr->fetchResultRow()){
    $id = $row['id'];
    $alias = $row['alias'];
    $names = explode(",",$row['names']);
    $groupXML = "";
    if (count($names) > 1) {//it's user group
      $groupXML .= openXMLNode("Group",array('name' => $alias));
      foreach ($names as $nameinfo) {
        list($usrAlias,$fullname) = explode(":",$nameinfo);
        $groupXML .= makeXMLNode("Member",array('alias' => $usrAlias),trim($fullname));
      }
      $groupXML .= closeXMLNode('Group');
    } else if ($alias == "Public" || $alias == "Users") {
      $groupXML .= makeXMLNode("Group",array('alias' => $alias),trim($names[0]));
    } else {
      $groupXML .= makeXMLNode("Individual",array('alias' => $alias),trim($names[0]));
    }
    $fullnameLookup[$row['id']] = $groupXML;
  }

  $errorMsg = null;

  function calcEditionRML($ednID, $ckn) {
    global $errorMsg,$fullnameLookup;

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
    $prtIDs = null;
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

    $itmIDs = array();
    $parts = null;
    $items = null;
    // get all parts
    if (isset($prtIDs) && count($prtIDs)) {
      $parts = new Parts('prt_id in ('.join(",",$prtIDs).')','prt_id',null,null);//null offset and limit will get all in case of no condition
      foreach($parts as $part){
        if ($part->getItemID()) {
          array_push($itmIDs,$part->getItemID());
        }
      }
      $parts->rewind();
    }

  // get all items
    if (isset($itmIDs) && count($itmIDs)) {
      $items = new Items('itm_id in ('.join(",",$itmIDs).')','itm_id',null,null);//null offset and limit will get all in case of no condition
    }

    // get all baselines
    if (isset($srfIDs) && count($srfIDs)) {
      $baselines = new Baselines('bln_surface_id in ('.join(",",$srfIDs).')','bln_id',null,null);//null offset and limit will get all in case of no condition
    }

  //get all textMetadatas
    if ($txtID) {
      $textmetadatas = new TextMetadatas("tmd_text_id = $txtID",'tmd_id',null,null);//null offset and limit will get all in case of no condition
    }

  //$condition = "$txtID = ANY (\"srf_text_ids\")";
  //start generating RML
    $RML = startRML(null,false);
    $attr = array('time'=>date(DATE_RFC2822),'dbname'=>DBNAME);
    if ($ednID){
      $attr['ednID'] = $ednID;
    }
    if ($ckn){
      $attr['ckn'] = $ckn;
    }
    $RML .= openXMLNode('requestInfo',$attr);
    $RML .= makeXMLNode('requestor',null,(array_key_exists(getUserID(),$fullnameLookup)?$fullnameLookup[getUserID()] :"unknown"),true,false);
    $RML .= closeXMLNode('requestInfo');
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
        $RML .= getSurfaceRML($surface);
      }
    }

    if (isset($fragments) && $fragments->getCount() > 0) {
      foreach($fragments as $fragment){
        $RML .= getFragmentRML($fragment);
      }
    }

    if (isset($parts) && $parts->getCount() > 0) {
      foreach($parts as $part){
        $RML .= getPartRML($part);
      }
    }

    if (isset($items) && $items->getCount() > 0) {
      foreach($items as $item){
        $RML .= getItemRML($item);
      }
    }

    if (isset($baselines) && $baselines->getCount() > 0) {
      foreach($baselines as $baseline){
        $RML .= getBaselineRML($baseline);
      }
    }

    foreach($editions as $edition){
      $RML .= getEditionRML($edition);
      break;//epidoc export only handles one
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
      $label = $entityLookup['term']['idByCode']['edn_description']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'description');
      $rml .= makeXMLNode($label,null,$edition->getDescription());
    }
    $seqRML = "";
    if (count($edition->getSequenceIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['edn_sequence_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode']['tmd_text_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'text');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'text','id'=>$textmetadata->getTextID()));
      $rml .= closeXMLNode($label);
      $txtRML = getTextRML($textmetadata->getText(true));
    }
    if (count($textmetadata->getTypeIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['tmd_type_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'types');
      $rml .= openXMLNode($label);
      foreach($textmetadata->getTypeIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    $refRML = "";
    if (count($textmetadata->getReferenceIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['tmd_reference_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode']['txt_ckn']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'CKN');
      $rml .= makeXMLNode($label,null,$text->getCKN());
    }
    if ($text->getTitle()) {
      $label = $entityLookup['term']['idByCode']['txt_title']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$text->getTitle());
    }
    $txtRML = "";
    if (count($text->getReplacementIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['txt_replacement_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode']['txt_type_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'types');
      $rml .= openXMLNode($label);
      foreach($text->getTypeIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    $refRML = "";
    if (count($text->getEditionReferenceIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['txt_edition_ref_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode']['txt_image_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'images');
      $rml .= openXMLNode($label);
      foreach($text->getImageIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $text->getImages(true) as $image) {
        $imgRML .= getImageRML($image);
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

 function getImageRML($image){
    global $entityLookup,$imageType2CODE;
    if (!array_key_exists('image', $entityLookup)){
      $entityLookup['image'] = array();
    }
    if (in_array($image->getID(), $entityLookup['image'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('image',array('id' => $image->getID(), 'modified' => $image->getModificationStamp()));
    if ($image->getTitle()) {
      $label = $entityLookup['term']['idByCode']['img_title']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$image->getTitle());
    }
    if ($image->getURL()) {
      $label = $entityLookup['term']['idByCode']['img_url']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'url');
      $imgInfo = null;
      $imgUrl = $image->getURL();
      if (!preg_match("/^http/i",$imgUrl) && preg_match("/^\//",$imgUrl)) {
        $imgUrl = SITE_ROOT.$imgUrl;
      }
      try {
        $imgInfo = getimagesize($imgUrl);
      } catch(Exception $e){
      }
      $width = $height = $type = $attr = null;
      if ($imgInfo) {
        list($width, $height, $type, $attr) = $imgInfo;
      }
      $attr = null;
      if ($width && $height) {
        $attr = array('width' => $width,'height' => $height,'mimetype' => image_type_to_mime_type($type));
      }
      $rml .= makeXMLNode($label,$attr,$image->getURL());
    }
    if ($image->getTypeID()){
      $label = $entityLookup['term']['idByCode']['img_type_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$image->getTypeID()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$image->getTypeID(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if (count($image->getBoundary()) > 0){
      $label = $entityLookup['term']['idByCode']['img_image_pos']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'boundary');
      $rml .= openXMLNode($label);
      foreach($image->getBoundary() as $polygon) {
        $rml .= makeXMLNode('points',null,$polygon->getPolygonString());
        $bRect = new BoundingBox($polygon->getPolygonString());
        $rml .= makeXMLNode('boundingbox',
                             array('left'=>$bRect->getXOffset(),
                                   'top'=>$bRect->getYOffset(),
                                   'right'=>$bRect->getXOffset()+$bRect->getWidth(),
                                   'bottom'=>$bRect->getYOffset()+$bRect->getHeight()),
                             $bRect->getPolygonString());
        $center = $polygon->getCenter();
        $rml .= makeXMLNode('center',null,"(".$center[0].",".$center[1].")");
      }
      $rml .= closeXMLNode($label);
    }
    $rml .= getAnnotationLinksRML('img',$image->getAnnotationIDs());
    $rml .= getAttributionLinksRML('img',$image->getAttributionIDs());
    $rml .= getOwnerRML('img',$image->getOwnerID());
    $rml .= getVisibilityRML('img',$image->getVisibilityIDs());
    $rml .= closeXMLNode('image');
    array_push($entityLookup['image'],$image->getID());
    if (count($image->getAttributionIDs()) > 0){
      foreach($image->getAttributions(true) as $attribution) {
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
      $label = $entityLookup['term']['idByCode']['seq_label']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'label');
      $rml .= makeXMLNode($label,null,$sequence->getLabel());
    }
    if ($sequence->getSuperScript()) {
      $label = $entityLookup['term']['idByCode']['seq_superscript']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'label');
      $rml .= makeXMLNode($label,null,$sequence->getSuperScript());
    }
    if ($sequence->getTypeID()) {
      $label = $entityLookup['term']['idByCode']['seq_type_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$sequence->getTypeID()]['englabel'];//warning term trm_code dependency
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$sequence->getTypeID(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    $seqRML = "";
    if (count($sequence->getEntityIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['seq_entity_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode']['cmp_value']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'value');
      $rml .= makeXMLNode($label,null,$compound->getValue());
    }
    if ($compound->getTranscription()) {
      $label = $entityLookup['term']['idByCode']['cmp_transcription']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'transcription');
      $rml .= makeXMLNode($label,null,$compound->getTranscription());
    }
    $cpnRML = "";
    if (count($compound->getComponentIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['cmp_component_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'components');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($compound->getComponentIDs() as $gid) {
        $prefix = substr($gid,0,3);
        $id = substr($gid,4);
        $rml .= makeXMLNode('link',array('entity' => $prefixToTableName[$prefix],'id'=>$id));
      }
      $rml .= closeXMLNode($label);
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
      $label = $entityLookup['term']['idByCode']['tok_value']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'value');
      $rml .= makeXMLNode($label,null,$token->getValue());
    }
    if ($token->getTranscription()) {
      $label = $entityLookup['term']['idByCode']['tok_transcription']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'transcription');
      $rml .= makeXMLNode($label,null,$token->getTranscription());
    }
    $graRML = "";
    if (count($token->getGraphemeIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['tok_grapheme_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode']['tok_nom_affix']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'affix');
      $rml .= makeXMLNode($label,null,$token->getNominalAffix());
    }
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
    $rml .= $graRML;
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
      $label = $entityLookup['term']['idByCode']['scl_grapheme_ids']['englabel'];//warning term trm_code dependency
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
    $segRML = "";
    if ($syllable->getSegmentID()) {
      $label = $entityLookup['term']['idByCode']['scl_segment_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'segment');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'segment','id'=>$syllable->getSegmentID()));
      $rml .= closeXMLNode($label);
      $segRML = getSegmentRML($syllable->getSegment(true));
    }
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
    $rml .= $graRML.$segRML;
    if (count($syllable->getAttributionIDs()) > 0){
      foreach($syllable->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getSegmentRML($segment){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('segment', $entityLookup)){
      $entityLookup['segment'] = array();
    }
    if (in_array($segment->getID(), $entityLookup['segment'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('segment',array('id' => $segment->getID(), 'modified' => $segment->getModificationStamp()));
    $blnRML = "";
    if (count($segment->getBaselineIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['seg_baseline_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'baselines');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($segment->getBaselineIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'baseline','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $segment->getBaselines(true) as $baseline) {
        $blnRML .= getBaselineRML($baseline);
      }
    }
    if (count($segment->getImageBoundary()) > 0){
      $label = $entityLookup['term']['idByCode']['seg_image_pos']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'boundaries');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($segment->getImageBoundary() as $polygon) {
        $rml .= openXMLNode('boundary');
        $rml .= makeXMLNode('points',null,$polygon->getPolygonString());
        $bRect = new BoundingBox($polygon->getPolygonString());
        $rml .= makeXMLNode('boundingbox',
                             array('left'=>$bRect->getXOffset(),
                                   'top'=>$bRect->getYOffset(),
                                   'right'=>$bRect->getXOffset()+$bRect->getWidth(),
                                   'bottom'=>$bRect->getYOffset()+$bRect->getHeight()),
                             $bRect->getPolygonString());
        $center = $polygon->getCenter();
        $rml .= makeXMLNode('center',null,"(".$center[0].",".$center[1].")");
        $rml .= closeXMLNode('boundary');
      }
      $rml .= closeXMLNode($label);
    }
    if (count($segment->getStringPos()) > 0){
      $label = $entityLookup['term']['idByCode']['seg_string_pos']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'stringpos');
      $rml .= makeXMLNode('stringposition',null,$segment->getStringPos(true));
    }
    if ($segment->getLayer()) {
      $label = $entityLookup['term']['idByCode']['seg_layer']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'layer');
      $rml .= makeXMLNode($label,null,$segment->getLayer());
    }
    if ($segment->getRotation()) {
      $label = $entityLookup['term']['idByCode']['seg_rotation']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'rotation');
      $rml .= makeXMLNode($label,null,$segment->getRotation());
    }
    if ($segment->getClarity()) {
      $label = $entityLookup['term']['idByCode']['seg_clarity_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$segment->getClarity()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$segment->getClarity(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if (count($segment->getObscurations()) > 0) {
      $label = $entityLookup['term']['idByCode']['seg_obscurations']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'obscurations');
      $rml .= openXMLNode($label,array('ordered' => 'false'));
      foreach($segment->getObscurations() as $obscuration) {
        $rml .= makeXMLNode('obscuration',null,$obscuration);
      }
      $rml .= closeXMLNode($label);
    }
    $rml .= getAnnotationLinksRML('seg',$segment->getAnnotationIDs());
    $rml .= getAttributionLinksRML('seg',$segment->getAttributionIDs());
    $rml .= getOwnerRML('seg',$segment->getOwnerID());
    $rml .= getVisibilityRML('seg',$segment->getVisibilityIDs());
    $rml .= closeXMLNode('segment');
    array_push($entityLookup['segment'],$segment->getID());
    $rml = $blnRML.$rml;
    if (count($segment->getAttributionIDs()) > 0){
      foreach($segment->getAttributions(true) as $attribution) {
        $rml .= getAttributionRML($attribution);
      }
    }
    return $rml;
  }

  function getBaselineRML($baseline){
    global $entityLookup, $prefixToTableName;
    if (!array_key_exists('baseline', $entityLookup)){
      $entityLookup['baseline'] = array();
    }
    if (in_array($baseline->getID(), $entityLookup['baseline'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('baseline',array('id' => $baseline->getID(), 'modified' => $baseline->getModificationStamp()));
    $imgRML = "";
    if ($baseline->getImageID()){
      $label = $entityLookup['term']['idByCode']['bln_image_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'image');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$baseline->getImageID()));
      $rml .= closeXMLNode($label);
      $imgRML .= getImageRML($baseline->getImage(true));
    }
    if (count($baseline->getImageBoundary()) > 0){
      $label = $entityLookup['term']['idByCode']['bln_image_position']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'boundaries');
      $rml .= openXMLNode($label,array('ordered' => 'true'));
      foreach($baseline->getImageBoundary() as $polygon) {
        $rml .= openXMLNode('boundary');
        $rml .= makeXMLNode('points',null,$polygon->getPolygonString());
        $bRect = new BoundingBox($polygon->getPolygonString());
        $rml .= makeXMLNode('boundingbox',null,$bRect->getPolygonString());
        $center = $polygon->getCenter();
        $rml .= makeXMLNode('center',null,"(".$center[0].",".$center[1].")");
        $rml .= closeXMLNode('boundary');
      }
      $rml .= closeXMLNode($label);
    }
    if ($baseline->getTranscription()) {
      $label = $entityLookup['term']['idByCode']['bln_transcription']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'layer');
      $rml .= makeXMLNode($label,null,$baseline->getTranscription());
    }
    if ($baseline->getType()) {
      $label = $entityLookup['term']['idByCode']['bln_type_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$baseline->getType()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$baseline->getType(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    $srfRML = "";
    if ($baseline->getSurfaceID()){
      $label = $entityLookup['term']['idByCode']['bln_surface_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'surface');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'surface','id'=>$baseline->getSurfaceID()));
      $rml .= closeXMLNode($label);
      $srfRML .= getSurfaceRML($baseline->getSurface(true));
    }
    $rml .= getAnnotationLinksRML('bln',$baseline->getAnnotationIDs());
    $rml .= getAttributionLinksRML('bln',$baseline->getAttributionIDs());
    $rml .= getOwnerRML('bln',$baseline->getOwnerID());
    $rml .= getVisibilityRML('bln',$baseline->getVisibilityIDs());
    $rml .= closeXMLNode('baseline');
    array_push($entityLookup['baseline'],$baseline->getID());
    $rml = $srfRML.$imgRML.$rml;
    if (count($baseline->getAttributionIDs()) > 0){
      foreach($baseline->getAttributions(true) as $attribution) {
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
      $label = $entityLookup['term']['idByCode']['gra_grapheme']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'grapheme');
      $rml .= makeXMLNode($label,null,$grapheme->getValue());
    }
    // todo code for term ids.
    if ($grapheme->getType()) {
      $label = $entityLookup['term']['idByCode']['gra_type_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
//      $value = $entityLookup['term']['byID'][$grapheme->getTypeID()]['englabel'];
//      $value = ($value?$value:'');
//      $rml .= openXMLNode($label);
//      $rml .= makeXMLNode('link',array('entity' => 'textmetadata','id'=>$grapheme->getTypeID(),'value' => $value));
      $rml .= makeXMLNode($label,null,$grapheme->getType());
//      $rml .= closeXMLNode($label);
    }
    if ($grapheme->getTextCriticalMark()) {
      $label = $entityLookup['term']['idByCode']['gra_text_critical_mark']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'tcm');
      $rml .= makeXMLNode($label,null,$grapheme->getTextCriticalMark());
    }
    if ($grapheme->getAlternative()) {
      $label = $entityLookup['term']['idByCode']['gra_alt']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'alternative');
      $rml .= makeXMLNode($label,null,$grapheme->getAlternative());
    }
    if ($grapheme->getEmmendation()) {
      $label = $entityLookup['term']['idByCode']['gra_emmendation']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'emmendation');
      $rml .= makeXMLNode($label,null,$grapheme->getEmmendation());
    }
    if ($grapheme->getDecomposition()) {
      $label = $entityLookup['term']['idByCode']['gra_decomposition']['englabel'];//warning term trm_code dependency
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

  function getSurfaceRML($surface){
    global $entityLookup;
    if (!array_key_exists('surface', $entityLookup)){
      $entityLookup['surface'] = array();
    }
    if (in_array($surface->getID(), $entityLookup['surface'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('surface',array('id' => $surface->getID(), 'modified' => $surface->getModificationStamp()));
    if ($surface->getDescription()) {
      $label = $entityLookup['term']['idByCode']['srf_description']['englabel'];
      $label = ($label?$label:'description');
      $rml .= makeXMLNode($label,null,$surface->getDescription());
    }
    if ($surface->getNumber()) {
      $label = $entityLookup['term']['idByCode']['srf_number']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'number');
      $rml .= makeXMLNode($label,null,$surface->getNumber());
    }
    if ($surface->getLayerNumber()) {
      $label = $entityLookup['term']['idByCode']['srf_layer_number']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'layer');
      $rml .= makeXMLNode($label,null,$surface->getLayerNumber());
    }
    $frgRML = "";
    if ($surface->getFragmentID()) {
      $label = $entityLookup['term']['idByCode']['srf_fragment_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'fragment');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'fragment','id'=>$surface->getFragmentID()));
      $rml .= closeXMLNode($label);
      $frgRML .= getFragmentRML($surface->getFragment(true));
    }
    if (count($surface->getScripts()) > 0){
//      $label = $entityLookup['term']['idByCode']['srf_scripts']['englabel'];
//      $label = ($label?$label:'scripts');
//      $rml .= makeXMLNode($label,$surface->getScripts(true));
      $rml .= makeXMLNode('scripts',$surface->getScripts(true));
    }
    if (count($surface->getTextIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['srf_text_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'texts');
      $rml .= openXMLNode($label);
      foreach($surface->getTextIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'text','id'=>$id));
      }
      $rml .= closeXMLNode($label);
    }
    $imgRML = "";
    if (count($surface->getImageIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['srf_image_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'images');
      $rml .= openXMLNode($label);
      foreach($surface->getImageIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $surface->getImages(true) as $image) {
        $imgRML .= getImageRML($image);
      }
    }
    $rml .= getAnnotationLinksRML('srf',$surface->getAnnotationIDs());
//    $rml .= getOwnerRML('srf',$surface->getOwnerID());
    $rml .= getVisibilityRML('srf',$surface->getVisibilityIDs());
    $rml .= closeXMLNode('surface');
    array_push($entityLookup['surface'],$surface->getID());
    $rml .= $imgRML.$frgRML;
    return $rml;
  }

  function getFragmentRML($fragment){
    global $entityLookup;
    if (!array_key_exists('fragment', $entityLookup)){
      $entityLookup['fragment'] = array();
    }
    if (in_array($fragment->getID(), $entityLookup['fragment'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('fragment',array('id' => $fragment->getID(), 'modified' => $fragment->getModificationStamp()));
    $prtRML = "";
    if ($fragment->getPartID()) {
      $label = $entityLookup['term']['idByCode']['frg_part_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'part');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'part','id'=>$fragment->getPartID()));
      $rml .= closeXMLNode($label);
      $prtRML .= getPartRML($fragment->getPart(true));
    }
    if ($fragment->getDescription()) {
      $label = $entityLookup['term']['idByCode']['frg_description']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'description');
      $rml .= makeXMLNode($label,null,$fragment->getDescription());
    }
    if (count($fragment->getLocationRefs()) > 0){
//      $label = $entityLookup['term']['idByCode']['frg_location_refs']['englabel'];
//      $label = ($label?$label:'locations');
//      $rml .= makeXMLNode($label,$fragment->getLocationRefs(true));
      $rml .= makeXMLNode('locations',$fragment->getLocationRefs(true));
    }
    if ($fragment->getLabel()) {
      $label = $entityLookup['term']['idByCode']['frg_label']['englabel'];
      $label = ($label?$label:'label');
      $rml .= makeXMLNode($label,null,$fragment->getLabel());
    }
    if ($fragment->getRestoreStateID()) {
      $label = $entityLookup['term']['idByCode']['frg_restore_state_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$fragment->getRestoreStateID()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$fragment->getRestoreStateID(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if ($fragment->getMeasure()) {
      $label = $entityLookup['term']['idByCode']['frg_measure']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'measure');
      $rml .= makeXMLNode($label,null,$fragment->getMeasure());
    }
    $imgRML = "";
    if (count($fragment->getImageIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['frg_image_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'images');
      $rml .= openXMLNode($label);
      foreach($fragment->getImageIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $fragment->getImages(true) as $image) {
        $imgRML .= getImageRML($image);
      }
    }
    $mcxRML = "";
    if (count($fragment->getMaterialContextIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['frg_material_context_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'materialcontext');
      $rml .= openXMLNode($label);
      foreach($fragment->getMaterialContextIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'materialcontext','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $fragment->getMaterialContexts(true) as $materialContext) {
        $mcxRML .= getMaterialContextRML($materialContext);
      }
    }
    $rml .= getAnnotationLinksRML('frg',$fragment->getAnnotationIDs());
    $rml .= getOwnerRML('frg',$fragment->getOwnerID());
    $rml .= getVisibilityRML('frg',$fragment->getVisibilityIDs());
    $rml .= closeXMLNode('fragment');
    array_push($entityLookup['fragment'],$fragment->getID());
    $rml .= $imgRML.$prtRML.$mcxRML;
    return $rml;
  }

  function getPartRML($part){
    global $entityLookup;
    if (!array_key_exists('part', $entityLookup)){
      $entityLookup['part'] = array();
    }
    if (in_array($part->getID(), $entityLookup['part'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('part',array('id' => $part->getID(), 'modified' => $part->getModificationStamp()));
    if ($part->getType()) {
      $label = $entityLookup['term']['idByCode']['prt_type_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$part->getType()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$part->getType(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if ($part->getLabel()) {
      $label = $entityLookup['term']['idByCode']['prt_label']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'label');
      $rml .= makeXMLNode($label,null,$part->getLabel());
    }
    if ($part->getSequence()) {
      $label = $entityLookup['term']['idByCode']['prt_sequence']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'sequencenumber');
      $rml .= makeXMLNode($label,null,$part->getSequence());
    }
    if ($part->getShape()) {
      $label = $entityLookup['term']['idByCode']['prt_shape_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'shape');
      $value = $entityLookup['term']['byID'][$part->getShape()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$part->getShape(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if (count($part->getMediums()) > 0){
      $label = $entityLookup['term']['idByCode']['prt_mediums']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'mediums');
//      $rml .= makeXMLNode($label,$part->getLocationRefs(true));
      $rml .= makeXMLNode($label,null,$part->getMediums(true));
    }
    if ($part->getManufactureID()) {
      $label = $entityLookup['term']['idByCode']['prt_manufacture_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'manufacturing');
      $value = $entityLookup['term']['byID'][$part->getManufactureID()]['englabel'];
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$part->getManufactureID(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if ($part->getMeasure()) {
      $label = $entityLookup['term']['idByCode']['prt_measure']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'measure');
      $rml .= makeXMLNode($label,null,$part->getMeasure());
    }
    $itmRML = "";
    if ($part->getItemID()) {
      $label = $entityLookup['term']['idByCode']['prt_item_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'item');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'item','id'=>$part->getItemID()));
      $rml .= closeXMLNode($label);
      $itmRML .= getItemRML($part->getItem(true));
    }
    $imgRML = "";
    if (count($part->getImageIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['prt_image_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'images');
      $rml .= openXMLNode($label);
      foreach($part->getImageIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $part->getImages(true) as $image) {
        $imgRML .= getImageRML($image);
      }
    }
    $rml .= getAnnotationLinksRML('prt',$part->getAnnotationIDs());
    $rml .= getOwnerRML('prt',$part->getOwnerID());
    $rml .= getVisibilityRML('prt',$part->getVisibilityIDs());
    $rml .= closeXMLNode('part');
    array_push($entityLookup['part'],$part->getID());
    $rml .= $imgRML.$itmRML;
    return $rml;
  }

  function getItemRML($item){
    global $entityLookup;
    if (!array_key_exists('item', $entityLookup)){
      $entityLookup['item'] = array();
    }
    if (in_array($item->getID(), $entityLookup['item'])){
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('item',array('id' => $item->getID(), 'modified' => $item->getModificationStamp()));
    if ($item->getTitle()) {
      $label = $entityLookup['term']['idByCode']['itm_title']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$item->getTitle());
    }
    if ($item->getType()) {
      $label = $entityLookup['term']['idByCode']['itm_type_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'type');
      $value = $entityLookup['term']['byID'][$item->getType()]['englabel'];//warning term trm_code dependency
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$item->getType(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    if ($item->getMeasure()) {
      $label = $entityLookup['term']['idByCode']['itm_measure']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'measure');
      $rml .= makeXMLNode($label,null,$item->getMeasure());
    }
    if ($item->getShapeID()) {
      $label = $entityLookup['term']['idByCode']['itm_shape_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'shape');
      $value = $entityLookup['term']['byID'][$item->getShapeID()]['englabel'];//warning term trm_code dependency
      $value = ($value?$value:'');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$item->getShapeID(),'value' => $value));
      $rml .= closeXMLNode($label);
    }
    $imgRML = "";
    if (count($item->getImageIDs()) > 0){
      $label = $entityLookup['term']['idByCode']['itm_image_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'images');
      $rml .= openXMLNode($label);
      foreach($item->getImageIDs() as $id) {
        $rml .= makeXMLNode('link',array('entity' => 'image','id'=>$id));
      }
      $rml .= closeXMLNode($label);
      foreach( $item->getImages(true) as $image) {
        $imgRML .= getImageRML($image);
      }
    }
    $rml .= getAnnotationLinksRML('itm',$item->getAnnotationIDs());
    $rml .= getOwnerRML('itm',$item->getOwnerID());
    $rml .= getVisibilityRML('itm',$item->getVisibilityIDs());
    $rml .= closeXMLNode('item');
    array_push($entityLookup['item'],$item->getID());
    $rml .= $imgRML;
    return $rml;
  }

  function getMaterialContextRML($materialcontext){
    global $entityLookup;
    if (!array_key_exists('materialcontext', $entityLookup)){
      $entityLookup['materialcontext'] = array();
    }
    if (in_array($materialcontext->getID(), $entityLookup['materialcontext'])){//warning term trm_code dependency
      // output error log message ??
     return "";
    }
    $rml = openXMLNode('materialcontext',array('id' => $materialcontext->getID(), 'modified' => $materialcontext->getModificationStamp()));
    if ($materialcontext->getArchContext()) {
      $label = $entityLookup['term']['idByCode']['mcx_arch_context']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'archcontext');
      $rml .= makeXMLNode($label,null,$materialcontext->getArchContext());
    }
    if ($materialcontext->getFindStatus()) {
      $label = $entityLookup['term']['idByCode']['mcx_find_status']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'findstatus');
      $rml .= makeXMLNode($label,null,$materialcontext->getFindStatus());
    }
    $rml .= getAnnotationLinksRML('mcx',$materialcontext->getAnnotationIDs());
    $rml .= getOwnerRML('mcx',$materialcontext->getOwnerID());
    $rml .= getVisibilityRML('mcx',$materialcontext->getVisibilityIDs());
    $rml .= closeXMLNode('materialcontext');
    array_push($entityLookup['materialcontext'],$materialcontext->getID());
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
      $label = $entityLookup['term']['idByCode']['atb_title']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'title');
      $rml .= makeXMLNode($label,null,$attribution->getTitle());
    }
    if ($attribution->getDescription()) {
      $label = $entityLookup['term']['idByCode']['atb_description']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'description');
      $rml .= makeXMLNode($label,null,$attribution->getDescription());
    }
    if ($attribution->getBibliographyID()) {
      $label = $entityLookup['term']['idByCode']['atb_bib_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'bibliography');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'bibliography','id'=>$attribution->getBibliographyID()));
      $rml .= closeXMLNode($label);
//      $bibRML = getBibliographyRML($attribution->getBibliography(true));
    }
    if ($attribution->getGroupID()) {
      $label = $entityLookup['term']['idByCode']['atb_group_id']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'group');
      $rml .= openXMLNode($label);
      $rml .= makeXMLNode('link',array('entity' => 'attributiongroup','id'=>$attribution->getGroupID()));
      $rml .= closeXMLNode($label);
//      $grpRML = getBibliographyRML($attribution->getGroup(true));
    }
    if (count($attribution->getTypes()) > 0){
      $label = $entityLookup['term']['idByCode']['atb_types']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'types');
      $rml .= makeXMLNode($label,$attribution->getTypes(true));
//      foreach($attribution->getTypes() as $type) {
//        $rml .= makeXMLNode('link',array('entity' => 'term','id'=>$id));
//      }
//      $rml .= closeXMLNode($label);
    }
    if ($attribution->getDetail()) {
      $label = $entityLookup['term']['idByCode']['atb_detail']['englabel'];//warning term trm_code dependency
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
    $xml = (array_key_exists($ownerID,$fullnameLookup) ? $fullnameLookup[$ownerID] : "unknown");
    $label = $entityLookup['term']['idByCode'][$prefix.'_owner_id']['englabel'];//warning term trm_code dependency
    $label = ($label?$label:'owner');
    $rml .= makeXMLNode($label,array('entity' => 'usergroup'), $xml,true,false);
    return $rml;
  }

  function getVisibilityRML($prefix,$visibilityIDs) {
  global $fullnameLookup,$entityLookup;
    $rml = "";
    if (is_array($visibilityIDs) && count($visibilityIDs) > 0){
      $label = $entityLookup['term']['idByCode'][$prefix.'_visibility_ids']['englabel'];//warning term trm_code dependency
      $label = ($label?$label:'visibility');
      $rml .= openXMLNode($label,array('entity' => 'usergroup'));
      foreach ($visibilityIDs as $id) {
        $rml .= (array_key_exists($id,$fullnameLookup) ? $fullnameLookup[$id] : "<unknown/>");
      }
      $rml .= closeXMLNode($label);
    }
    return $rml;
  }

  function getAnnotationLinksRML($prefix,$annotationIDs){
    global $entityLookup;
    $rml = "";
    if (is_array($annotationIDs) && count($annotationIDs) > 0){
      $label = $entityLookup['term']['idByCode'][$prefix.'_annotation_ids']['englabel'];//warning term trm_code dependency
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
      $label = $entityLookup['term']['idByCode'][$prefix.'_attribution_ids']['englabel'];//warning term trm_code dependency
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
