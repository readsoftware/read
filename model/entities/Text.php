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
  * Classes to deal with Text entities
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Entity Classes
  */
  require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/Entity.php');
  require_once (dirname(__FILE__) . '/Surfaces.php');
  require_once (dirname(__FILE__) . '/Lines.php');
  require_once (dirname(__FILE__) . '/Images.php');
  require_once (dirname(__FILE__) . '/TextMetadatas.php');
  require_once (dirname(__FILE__) . '/Texts.php');

//*******************************************************************
//****************   TEXT CLASS    **********************************
//*******************************************************************
  /**
  * Text represents text entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Text.php';
  *
  * $text = new Text( $resultRow );
  * echo $text->getTitle();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Text extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_ckn,
              $_title,
              $_ref,
              $_replacement_ids=array(),
              $_replacements,
              $_edition_ref_ids,
              $_edition_refs,
              $_jsoncache_id,
              $_type_ids,
              $_image_ids=array(),
              $_textmetadatas,
              $_editions,
              $_images,
              $_surfaces,
              $_lines;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an text instance from an text table row
    * @param array $row associated with columns of the text table, a valid txt_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'text';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM text WHERE txt_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= txt_owner_id or ".getUserID()." = ANY (\"txt_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('txt_id',$row)) {
          error_log("unable to query for text ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['txt_id'] ? $arg['txt_id']:NULL;
        $this->_ckn=@$arg['txt_ckn'] ? $arg['txt_ckn']:NULL;
        $this->_title=@$arg['txt_title'] ? $arg['txt_title']:NULL;
        $this->_ref=@$arg['txt_ref'] ? $arg['txt_ref']:NULL;
        $this->_replacement_ids=@$arg['txt_replacement_ids'] ? $arg['txt_replacement_ids']:NULL;
        $this->_edition_ref_ids=@$arg['txt_edition_ref_ids'] ? $arg['txt_edition_ref_ids']:NULL;
        $this->_type_ids=@$arg['txt_type_ids'] ? $arg['txt_type_ids']:NULL;
        $this->_jsoncache_id=@$arg['txt_jsoncache_id'] ? $arg['txt_jsoncache_id']:NULL;
        $this->_image_ids=@$arg['txt_image_ids'] ? $arg['txt_image_ids']:NULL;
        if (!array_key_exists('txt_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('txt_replacement_ids',$arg))$arg['txt_replacement_ids'] = $this->idsToString($arg['txt_replacement_ids']);
          if (array_key_exists('txt_edition_ref_ids',$arg))$arg['txt_edition_ref_ids'] = $this->stringsToString($arg['txt_edition_ref_ids']);
          if (array_key_exists('txt_type_ids',$arg))$arg['txt_type_ids'] = $this->idsToString($arg['txt_type_ids']);
          if (array_key_exists('txt_image_ids',$arg))$arg['txt_image_ids'] = $this->idsToString($arg['txt_image_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new text to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "txt";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_ckn)) {
        $this->_data['txt_ckn'] = $this->_ckn;
      }
      if (count($this->_title)) {
        $this->_data['txt_title'] = $this->_title;
      }
      if (count($this->_ref)) {
        $this->_data['txt_ref'] = $this->_ref;
      }
      if (count($this->_replacement_ids)) {
        $this->_data['txt_replacement_ids'] = $this->idsToString($this->_replacement_ids);
      }
      if (count($this->_edition_ref_ids)) {
        $this->_data['txt_edition_ref_ids'] = $this->idsToString($this->_edition_ref_ids);
      }
      if (count($this->_type_ids)) {
        $this->_data['txt_type_ids'] = $this->idsToString($this->_type_ids);
      }
      if (count($this->_image_ids)) {
        $this->_data['txt_image_ids'] = $this->idsToString($this->_image_ids);
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********
    /**
    * Gets the CK Number for this Text
    * @return string for teh CK Number
    */
    public function getCKN() {
      return $this->_ckn;
    }

    /**
    * Gets the Inventory Number for this Text
    * @return string for the Inv Number
    */
    public function getInv() {
      return $this->_ckn;
    }

    /**
    * Get Title of the text
    *
    * When this is not stored it is caclulated from related entities.
    * @return string represents a more human readable label for this text
    * @todo add code for calculating the title
    */
    public function getTitle() {
      if (!$this->_title){
        //calculate title here
      }
      return $this->_title;
    }

    /**
    * Get Value of the text
    *
    * When this is not stored it is caclulated from related entities.
    * @return string represents a more human readable label for this text
    * @todo add code for calculating the title
    */
    public function getValue() {
      if (!$this->_title){
        //calculate title here
      }
      return $this->_title;
    }

    /**
    * Get Reference Number/Label of the text
    *
    * @return string represents a Number/label for this text
    */
    public function getRef() {
      if (!$this->_ref){
      }
      return $this->_ref;
    }

    /**
    * Get Unique IDs of text entities which replace this one
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string of text entity IDs
    */
    public function getReplacementIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_replacement_ids);
      }else{
        return $this->idsStringToArray($this->_replacement_ids);
      }
    }

    /**
    * Get Text's Replacement objects
    *
    * @return Text replacement for this text or NULL
    */
    public function getReplacements($autoExpand = false) {
      if (!$this->_replacements && $autoExpand && count($this->getReplacementIDs())>0) {
        $this->_replacements = new Texts("txt_id in (".join(",",$this->getReplacementIDs()).")",null,null,null);
        $this->_replacements->setAutoAdvance(false);
      }
      return $this->_replacements;
    }

    /**
    * Get Text's Edition Reference semantic link IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string of Edition Reference semantic link IDs
    */
    public function getEditionReferenceIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_edition_ref_ids);
      }else{
        return $this->stringOfStringsToArray($this->_edition_ref_ids);
      }
    }

    /**
    * Get Text's EditionReference object
    *
    * @return EditionReference for this text or NULL
    * @todo Decide how to represent semantically link attributions (not just attribution iterator)
    */
    public function getEditionReference($autoExpand = false) {
      if (!$this->_edition_refs && $autoExpand && count($this->getEditionReferenceIDs())>0) {
        $this->_edition_refs = new Attributions("atb_id in (".join(",",$this->getEditionReferenceIDs()).")",null,null,null);
        $this->_edition_refs->setAutoAdvance(false);
      }
      return $this->_edition_refs;
    }

    /**
    * Get Text's Type term unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for term object IDs identifying the type for this text
    */
    public function getTypeIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_type_ids);
      }else{
        return $this->idsStringToArray($this->_type_ids);
      }
    }

    /**
    * Get Text's Images unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for image object IDs for this text
    */
    public function getImageIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_image_ids);
      }else{
        return $this->idsStringToArray($this->_image_ids);
      }
    }

    /**
    * Get Text's images
    *
    * @return iterator that contains image objects of this text or NULL
    */
    public function getImages($autoExpand = false) {
      if (!$this->_images && $autoExpand && $this->_image_ids && is_array($this->getImageIDs()) && count($this->getImageIDs())>0) {
        $this->_images = new Images("not (5 = ANY(img_visibility_ids)) and img_id in (".join(",",$this->getImageIDs()).")",null,null,null);
        $this->_images->setAutoAdvance(false);
      }
      return $this->_images;
    }

    /**
    * Get Text's JsonCache ID
    *
    * @return int returns the primary Key for JsonCache of the Text
    */
    public function getJsonCacheID() {
      return $this->_jsoncache_id;
    }


     /**
    * Get textmetadatas object which contains all textmetadatas attached to this text
    *
    * @return TextMetadatas iterator with all textmetadatas linked to this text
    */
    public function getTextMetadatas() {
      if (count($this->getReplacementIDs())>0) {
        $condition = "not tmd_owner_id = 1 and tmd_text_id in (".join(",",$this->getReplacementIDs()).")";
      }else{
        $condition = "not tmd_owner_id = 1 and tmd_text_id = ".$this->_id;
      }
      $this->_textmetadatas = new TextMetadatas($condition,null,null,null);
      $this->_textmetadatas->setAutoAdvance(false);
      return $this->_textmetadatas;
    }

   /**
    * Get ids of all textmetadatas attached to this text
    *
    * @return int[] array of textmetadata ids for all textmetadatas linked to this text
    */
    public function getTextMetadataIDs() {
      $dbMgr = new DBManager();
      $dbMgr->query("select array_agg(tmd_id) from textmetadata ".
                    "where ".$this->_id." = ANY (tmd_text_ids) and not tmd_owner_id = 1;");
      if ($dbMgr->getRowCount()) {
        $row = $dbMgr->fetchResultRow();
        $tmdIDs = explode(',',trim($row[0],"\"{}"));
        if ($tmdIDs[0] == "" ) {
          return array();
        } 
        return $tmdIDs;
      }
      return array();
    }

     /**
    * Get editions object which contains all editions attached to this text
    *
    * @return Editions iterator with all editions linked to this text
    */
    public function getEditions() {
      if ($this->getReplacementIDs() && count($this->getReplacementIDs())>0) {
        $condition = "not edn_owner_id = 1 and edn_text_id in (".join(",",$this->getReplacementIDs()).")";
      }else{
        $condition = "not edn_owner_id = 1 and edn_text_id = ".$this->_id;
      }
      $this->_editions = new Editions($condition,"(edn_scratch::jsonb->>'default')::text , (edn_scratch::jsonb->>'ordinal')::text::int",null,null);
      $this->_editions->setAutoAdvance(false);
      return $this->_editions;
    }

   /**
    * Get ids of all editions attached to this text
    *
    * @return int[] array of edition ids for all editions linked to this text
    */
    public function getEditionIDs() {
      $dbMgr = new DBManager();
      $dbMgr->query("select array_agg(edn_id) from edition ".
                    "where ".$this->_id." = edn_text_id and not edn_owner_id = 1 ".
                    "order by (edn_scratch::jsonb->>'default')::text , (edn_scratch::jsonb->>'ordinal')::text::int;");
      if ($dbMgr->getRowCount()) {
        $row = $dbMgr->fetchResultRow();
        $ednIDs = explode(',',trim($row[0],"\"{}"));
        if ($ednIDs[0] == "" ) {
          return array();
        } 
        return $ednIDs;
      }
      return array();
    }

    /**
    * Get surfaces object which contains all surfaces attached to this text
    *
    * @return Surfaces iterator with all surfaces linked to this text
    */
    public function getSurfaces() {
      if (count($this->getReplacementIDs())>0) {
        // TODO correct this for getting intersection.
        //$condition = "srf_text_ids in (".join(",",$this->getReplacementIDs()).")";
      }else{
        $condition = "".$this->_id." = ANY (srf_text_ids) and  not (5 = ANY(sfr_visibility_ids));";
      }
      $this->_surfaces = new Surfaces($condition,null,null,null);
      $this->_surfaces->setAutoAdvance(false);
      return $this->_surfaces;
    }

   /**
    * Get ids of all surfaces attached to this text
    *
    * @return int[] array of surface ids for all surfaces linked to this text
    */
    public function getSurfaceIDs() {
      $dbMgr = new DBManager();
      $dbMgr->query("select array_agg(srf_id) from surface ".
                    "where ".$this->_id." = ANY (srf_text_ids) and not (5 = ANY(sfr_visibility_ids));");
      if ($dbMgr->getRowCount()) {
        $row = $dbMgr->fetchResultRow();
        $srfIDs = explode(',',trim($row[0],"\"{}"));
        if ($srfIDs[0] == "" ) {
          return array();
        } 
        return $srfIDs;
      }
      return array();
    }

    /**
    * Get Baseline ids of all baselines attached to this text
    *
    * @return int[] baseline ids of all baselines linked to this text
    */
    public function getBaselineIDs() {
      $dbMgr = new DBManager();
      $dbMgr->query("select array_agg(distinct bln_id) from baseline ".
                      "left join surface on bln_surface_id = srf_id ".
                      "where ".$this->_id." = ANY (srf_text_ids) and not bln_owner_id = 1 and srf_id is not null");
      if ($dbMgr->getRowCount()) {
        $row = $dbMgr->fetchResultRow();
        $blnIDs = explode(',',trim($row[0],"\"{}"));
        if ($blnIDs[0] == "" ) {
          return array();
        } 
        return $blnIDs;
      }
      return array();
    }

    /**
    * Get Lines object which contains all lines attached to this text
    *
    * @return Lines iterator with all lines linked to this text
    */
    public function getLines() {
      if (!$this->_lines) {
        $dbMgr = new DBManager();
        $dbMgr->query("select distinct lin_id from surface ".
                        "left join baseline on bln_surface_id = srf_id ".
                        "left join segment on bln_id = ANY (seg_baseline_ids) ".
                        "left join span on seg_id = ANY (spn_segment_ids) ".
                        "left join line on spn_id = ANY (lin_span_ids) ".
                        "where ".$this->_id." = ANY (srf_text_ids) and lin_span_ids is not null".
                        (isSysAdmin()?"":" AND (".getUserID()."= txt_owner_id OR ".
                                           getUserID()." = ANY (\"txt_visibility_ids\"))"));
        $linIDs = array();
        while($row = $dbMgr->fetchResultRow()) {
          array_push($linIDs,$row[0]);
        }
        $this->_lines = new Lines("lin_id in (".join(",",$linIDs).")","lin_order",0,count($linIDs));
      }
      return $this->_lines;
    }


    //********SETTERS*********

    /**
    * Sets the CK Number for this Text
    * @param string $ckn
    */
    public function setCKN($ckn) {
      if($this->_ckn != $ckn) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_ckn",$ckn);
      }
      $this->_ckn = $ckn;
    }

   /**
    * Set Ref of the text
    *
    * @param string $ref represents a number/label identifier for this text
    */
    public function setRef($ref) {
      if($this->_ref != $ref) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_ref",$ref);
      }
      $this->_ref = $ref;
    }

   /**
    * Set Title of the text
    *
    * @param string $title represents a more human readable label for this text
    */
    public function setTitle($title) {
      if($this->_title != $title) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_title",$title);
      }
      $this->_title = $title;
    }

    /**
    * set Unique IDs of text entities which replace this one
    *
    * @return array $text_ids of text entity IDs
    * @todo add code to check all IDs values are valid
    */
    public function setReplacements($text_ids) {
      if($this->_replacement_ids != $text_ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_replacement_ids",$this->idsToString($text_ids));
      }
       $this->_replacement_ids = $text_ids;
    }

    /**
    * Set Text's EditionReference object
    *
    * @param int $edRefIDs of the primary Key for the EditionReference of this text
    */
    public function setEditionRefIDs($edRefIDs) {
      if($this->_edition_ref_ids != $edRefIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_edition_ref_ids",$this->stringsToString($edRefIDs));
      }
      $this->_edition_ref_ids = $edRefIDs;
    }

    /**
    * Set Text's Type term unique IDs
    *
    * @param int array $imageIDs of image object IDs for this text
    */
    public function setTypeIDs($typeIDs) {
      if($this->_type_ids != $typeIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_type_ids",$this->idsToString($typeIDs));
      }
      $this->_type_ids = $typeIDs;
    }

    /**
    * Set Text's Images unique IDs
    *
    * @param int array $imageIDs of image object IDs for this text
    */
    public function setImageIDs($imageIDs) {
      if($this->_image_ids != $imageIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("txt_image_ids",$this->idsToString($imageIDs));
      }
      $this->_image_ids = $imageIDs;
    }

    /**
    * Set Text's JsonCache ID
    *
    * @param int $jsonCacheID of JsonCache object that contains the json string for the Entity's of this Text
    */
    public function setJsonCacheID($jsonCacheID) {
      if($this->_jsoncache_id != $jsonCacheID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair($this->getGlobalPrefix()."_jsoncache_id",$jsonCacheID);
      }
      $this->_jsoncache_id = $jsonCacheID;
    }


    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
