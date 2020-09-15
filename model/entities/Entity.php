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
  * Class for Entity Base class
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
  require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get database interface
  require_once dirname(__FILE__) . '/Annotations.php';
  require_once dirname(__FILE__) . '/Attributions.php';

//*******************************************************************
//****************   ENTITY CLASS    **********************************
//*******************************************************************
  /**
  * Entity represents a base entity for common data and methods
  *
  * <code>
  * require_once 'Entity.php';
  *
  * class baseline extends Entity {
  *   private $_type,
  *           $_surface_id,
  *           ...
  *   public function __construct($arg) {
  *         $this->_owner_id = $arg['bln_owner_id'];
  *         ...}
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  abstract class Entity {

    //*******************************PRIVATE MEMBERS************************************

    /**
    * protected member variables
    * @access protected
    */
    protected $_id,
              $_annotation_ids=array(),
              $_annotations,
              $_attribution_ids=array(),
              $_attributions,
              $_modified,
              $_owner_id,
              $_visibility_ids=array(),
              $_scratchProperties=array(),
              $_tempProperties=array(),
              $_dirty = false,
              $_table_name,
              $_data=array(),
              $_errors=array();

    //****************************STATIC MEMBERS***************************************

    public static $validPrefixes = array("col","itm","prt","frg","atg","ugr","atb","img","spn","srf",
                                          "txt","tmd","bln","seg","run","lin","scl","gra","tok","cmp","lem",
                                          "inf","trm","cat","seq","lnk","edn","ano","bib","dat","era");

    protected static $_termInfo;
    protected static $_userInfoLookup;
    public static function getIDofTermParentLabel($termParentLabel) {
      if (!self::$_termInfo) {
        self::$_termInfo = getTermInfoForLangCode('en');
      }
      if (self::$_termInfo && array_key_exists(mb_strtolower($termParentLabel),self::$_termInfo['idByTerm_ParentLabel'])) {
        return self::$_termInfo['idByTerm_ParentLabel'][mb_strtolower($termParentLabel)];
      } else {
        return null;
      }
    }

    public static function getParentIDFromID($termID) {
      if (!self::$_termInfo) {
        self::$_termInfo = getTermInfoForLangCode('en');
      }
       if (!$termID) {
         error_log("call to retrieve term with empty term id");
         return "";
       }
      return self::$_termInfo['parentIDByID'][$termID];
    }

    public static function getTermList($termID) {
      if (!self::$_termInfo) {
        self::$_termInfo = getTermInfoForLangCode('en');
      }
       if (!$termID) {
         error_log("call to retrieve term list with empty term id");
         return "";
       }
      return self::idsStringToArray(self::$_termInfo['termByID'][$termID]['trm_list_ids']);
    }

    public static function getTermFromID($termID) {
      if (!self::$_termInfo) {
        self::$_termInfo = getTermInfoForLangCode('en');
      }
       if (!$termID) {
         error_log("call to retrieve term with empty term id");
         return "";
       }
      return self::$_termInfo['labelByID'][$termID];
    }

    /**
    * Convert multi polygon string to array of polygons
    *
    * @param string $polysString database polygon array string
    * @return NULL|array of polygon
    */
    public static function polyStringToArray($polysString){
      if (is_string($polysString)) {
        $polygons = null;
        preg_match_all("/(\((?:\(\d+\,\d+\)\,?)+\))+/",$polysString,$matches);
        $cnt = count($matches);
        if ($cnt > 1) {
          $cnt = count($matches[1]);
          $polygons = array();
          for($i=0; $i < $cnt; $i++){
            $polygon = new Polygon($matches[1][$i]);
            array_push($polygons, $polygon);
          }
        }
        $polysString = $polygons;
      }
      if (is_array($polysString) && array_key_exists(0,$polysString)
          && is_a($polysString[0],'Polygon')) {
        return $polysString;
      } else {
        return null;
      }
    }

    /**
    * Convert ids string to array
    *
    * @param string $idsString database id array string
    * @return NULL|array of int ids
    */
    public static function idsStringToArray($idsString){
      if (is_array($idsString)) return $idsString;
      preg_match_all("/([^\"\'\{\},]+)/",$idsString,$matches);
      return @$matches[1]? $matches[1]:null;
    }

    //****************************CONSTRUCTOR FUNCTION***************************************

    //*******************************PROTECTED FUNCTIONS************************************
    /**
    * synch properties to data for save
    *
    */
    abstract protected function synchData();


    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    abstract protected function getGlobalPrefix();

    /**
    * Set obj's database id to null
    *
    */
    protected function clearID(){
      $this->_id = null;
      $this->_dirty = true;
    }

    /**
    * Synch base properties to data for save
    *
    */
    protected function synchBaseData(){
      if (isset($this->_scratchProperties) && count($this->_scratchProperties)) {
        $this->_data[@$this->getGlobalPrefix().'_scratch'] = json_encode($this->_scratchProperties);
      }
      if (isset($this->_annotation_ids) && count($this->_annotation_ids)) {
        $this->_data[@$this->getGlobalPrefix().'_annotation_ids'] = $this->idsToString($this->_annotation_ids);
      }
      if ($this->getAttributionIDs() && count($this->getAttributionIDs())) {
        $this->_data[@$this->getGlobalPrefix().'_attribution_ids'] = $this->getAttributionIDs(true);
      }
      if ($this->getVisibilityIDs() && count($this->getVisibilityIDs())) {
        $this->_data[@$this->getGlobalPrefix().'_visibility_ids'] = $this->idsToString($this->_visibility_ids);
      }
    }

    /**
    * Set data columnName/value pair
    *
    * @param string $columnName identifying the table column name
    * @param mixed $value of the column
    */
    protected function setDataKeyValuePair($columnName, $value){
      $this->_data[$columnName] = $value;
    }

    /**
    * Initialize base data from array of columnName/value pairs
    *
    * @param array $args of columnName/value pairs
    */
    protected function initializeBaseEntity($args){
      $this->_scratchProperties=array_key_exists($this->getGlobalPrefix().'_scratch', $args) ? (is_string($args[$this->getGlobalPrefix().'_scratch']) ? json_decode($args[$this->getGlobalPrefix().'_scratch'],true):$args[$this->getGlobalPrefix().'_scratch']):array();
      $this->_annotation_ids=(array_key_exists($this->getGlobalPrefix().'_annotation_ids',$args)) ? $args[$this->getGlobalPrefix().'_annotation_ids']:array();
      $this->_attribution_ids= (array_key_exists($this->getGlobalPrefix().'_attribution_ids',$args)) ? $args[$this->getGlobalPrefix().'_attribution_ids']:array();
      $this->_modified=(array_key_exists('modified',$args)) ? $args['modified']:null;
      $this->_owner_id=(array_key_exists($this->getGlobalPrefix().'_owner_id',$args)) ? $args[$this->getGlobalPrefix().'_owner_id']:null;
      $this->_visibility_ids=(array_key_exists($this->getGlobalPrefix().'_visibility_ids',$args)) ? $args[$this->getGlobalPrefix().'_visibility_ids']:array();
    }

    /**
    * Prep data columnName/value pair for Base Entity
    *
    * @param array $args of columnName/value pairs
    * @return array of columnName/value pairs
    */
    protected function prepBaseEntityData($args){
      if (array_key_exists($this->getGlobalPrefix().'_scratch',$args) &&
            is_array($args[$this->getGlobalPrefix().'_scratch']) &&
            count($args[$this->getGlobalPrefix().'_scratch']))
        $args[$this->getGlobalPrefix().'_scratch'] = json_encode($args[$this->getGlobalPrefix().'_scratch']);
      if (array_key_exists($this->getGlobalPrefix().'_annotation_ids',$args)) {
        $args[$this->getGlobalPrefix().'_annotation_ids'] = $this->idsToString($args[$this->getGlobalPrefix().'_annotation_ids']);
      }
      if (array_key_exists($this->getGlobalPrefix().'_attribution_ids',$args)) {
        $args[$this->getGlobalPrefix().'_attribution_ids'] = $this->idsToString($args[$this->getGlobalPrefix().'_attribution_ids']);
      }
      if (array_key_exists($this->getGlobalPrefix().'_visibility_ids',$args)) {
        $args[$this->getGlobalPrefix().'_visibility_ids'] = $this->idsToString($args[$this->getGlobalPrefix().'_visibility_ids']);
      }
      return $args;
    }

    /**
    * Build a single entity query with visibility checks
    *
    * @param int $id of entity
    * @return string that represents a postgreSQL query for a single entity that checks access
    */
    protected function getSingleEntityAccessQuery($id){
      $prefix = $this->getGlobalPrefix();
      //select *,case when edn_owner_id = ANY(ARRAY[2,14,15,20]) then 1 else 0 end as editable from edition
      //where edn_owner_id = ANY(ARRAY[2,3,14,15,20]) or ARRAY[2,3,14,15,20] && edn_visibility_ids;
      $q = "SELECT * FROM ".$this->_table_name.
           " WHERE ".$prefix."_id = $id".
            (isSysAdmin()?"":" AND (".$prefix."_owner_id = ANY(ARRAY[".join(",",getUserMembership()).",".getUserID()."])  OR ".
                              "ARRAY[".join(",",getUserMembership()).",".getUserID()."] && ".$prefix."_visibility_ids)").
           " LIMIT 1";
      return $q;
    }

    /**
    * Convert enum string to array
    *
    * @param string $enumsString database enum array string
    * @return NULL|array of string enumerates
    */
    protected function enumStringToArray($enumsString){
      if (is_array($enumsString)) return $enumsString;
      preg_match_all("/([^\"\'\{\},]+)/",$enumsString,$matches);
      return @$matches[1]? $matches[1]:null;
    }

    protected function getOwnerGivenName() {
      if (!self::$_userInfoLookup){
        self::$_userInfoLookup = getUserLookup();
      }
      if (array_key_exists($this->_owner_id,self::$_userInfoLookup)){
        return self::$_userInfoLookup[$this->_owner_id]['givenName'];
      }
      return "";
    }

    protected function getOwnerFamilyName() {
      if (!self::$_userInfoLookup){
        self::$_userInfoLookup = getUserLookup();
      }
      if (array_key_exists($this->_owner_id,self::$_userInfoLookup)){
        return self::$_userInfoLookup[$this->_owner_id]['familyName'];
      }
      return "";
    }

    protected function getOwnerFullName() {
      if (!self::$_userInfoLookup){
        self::$_userInfoLookup = getUserLookup();
      }
      if (array_key_exists($this->_owner_id,self::$_userInfoLookup)){
        return self::$_userInfoLookup[$this->_owner_id]['fullName'];
      }
      return "";
    }

    /**
    * Convert enum array to string
    *
    * @param array $enums string
    * @return string multi-enum array string
    */
    protected function enumsToString($enums){
      if (is_array($enums) && count($enums)){
        $enums = '{"'.join('","',$enums).'"}';
      }
      if (is_string($enums)) return $enums;
      return null;
    }

    /**
    * Convert string of strings to array of strings
    *
    * @param string $stringsString database string array string
    * @return NULL|array of strings
    */
    protected function stringOfStringsToArray($stringsString){
      if (is_array($stringsString)) return $stringsString;
      preg_match_all("/([^\"\'\{\},]+)/",$stringsString,$matches);
      return @$matches[1]? $matches[1]:null;
    }

    /**
    * Convert string array to string of strings
    *
    * @param array $stringsArray  of strings
    * @return string multi-string array string
    */
    protected function stringsToString($stringsArray){
      if (is_array($stringsArray) && count($stringsArray)){
        $stringsArray = '{"'.join('","',$stringsArray).'"}';
      }
      if (is_string($stringsArray)) return $stringsArray;
      return null;
    }

    /**
    * Convert id array to string
    *
    * @param array $ids integers
    * @return string multi-id array string
    */
    protected function idsToString($idsArrays){
      if (is_array($idsArrays) && count($idsArrays)){
        $idsArrays = '{'.join(',',$idsArrays).'}';
      }
      if (is_string($idsArrays)) return $idsArrays;
      return null;
    }

    /**
    * Convert global ids string to array
    *
    * @param string $gIdsString database id array string
    * @return NULL|array of int ids
    */
    protected function gIdsStringToArray($gIdsString){
      if (is_string($gIdsString)) {
        preg_match_all("/([^\"\'\{\},:]+):([^\"\'\{\},:]+)/",$gIdsString,$matches);
        if (count($matches) == 3){// we have a match so process
          $gIds = array();
          $cnt = count($matches[0]);
          for($i=0;$i<$cnt;$i++){
            if (in_array($matches[1][$i],$validPrefixes) && is_numeric($matches[2][$i])){
              if (array_key_exists($matches[1][$i],$gIds)){
                if (!in_array($matches[2][$i],$gIds[$matches[1][$i]])) {
                  array_push($gIds[$matches[1][$i]],$matches[2][$i]);
                }
              }else{
                $gIds[$matches[1][$i]]=array($matches[2][$i]);
              }
            }
          }
          $gIdsString = $gIds;
        }
      }
      if (is_array($gIdsString)) return $gIdsString;
      return null;
    }

    /**
    * Convert global id array to postgresql string array
    *
    * @param array $gIdsArrays integers
    * @return string multi-gId array string
    */
    protected function gIdsToString($gIdsArrays){
      if (is_array($gIdsArrays) && count($gIdsArrays)){
        $str = '{';
        $first = true;
        foreach($gIdsArrays as $prefix => $idsArray) {
          if ($first){
            $first = false;
          }else{
            $str .= ",";
          }
          $str .= '"'.$prefix.":".join("\",\"$prefix:",$idsArray).'"';
        }
        $gIdsArrays = $str.'}';
      }
      if (is_string($gIdsArrays)) return $gIdsArrays;
      return null;
    }

    /**
    * Convert key value pairs string to array
    *
    * @param string $kvPairsString of key:value pair strings from database
    * @return NULL|array of key value pairs
    */
    protected function kvPairsStringToArray($kvPairsString){
      if (is_string($kvPairsString)) {
        preg_match_all("/\"?([^\"\'\{\},:]+)\"?=>\"?([^\"\'\{\},:]+)\"?/",$kvPairsString,$matches);
        if (count($matches) == 3){// we have a match so process
          $pairs = array();
          $cnt = count($matches[0]);
          for($i=0;$i<$cnt;$i++){
            if (array_key_exists($matches[1][$i],$pairs)){
              array_push($this->_errors,"duplicate key found ".$matches[1][$i]." with value of ".$matches[2][$i]);
            }else{
              $pairs[$matches[1][$i]]=$matches[2][$i];
            }
          }
          $kvPairsString = $pairs;
        }
      }
      if (is_array($kvPairsString)) return $kvPairsString;
      return null;
    }

    /**
    * Convert keyed array to postgresql string array
    *
    * @param array $kvPairsArray of key value pairs
    * @return string multi-gId array string
    */
    protected function kvPairsToString($kvPairsArray){
      if (is_array($kvPairsArray) && count($kvPairsArray)){
        $str = '';
        $first = true;
        foreach($kvPairsArray as $key => $value) {
          if ($first){
            $first = false;
          }else{
            $str .= ",";
          }
          $str .= "\"$key\"=>\"$value\"";
        }
        $kvPairsArray = $str;
      }
      if (is_string($kvPairsArray)) return $kvPairsArray;
      return null;
    }

    /**
    * Convert arrays of array of ids string to arrays of array of int
    *
    * @param string $arrayIdsString database id array of array string
    * @return NULL|array of array of int ids
    */
    protected function arraysOfIdsStringToArray($arrayIdsString){
      if (is_string($arrayIdsString)) {
        $ids = null;
        preg_match_all("/(?:\{((?:\d+,?)+)\})/",$arrayIdsString,$matches);
        $cnt = count($matches);
        if ($cnt > 1) {
          $cnt = count($matches[1]);
          $ids = array();
          for($i=0; $i < $cnt; $i++){
            $idArray = explode(",",$matches[1][$i]);
            array_push($ids,$idArray);
          }
        }
        $arrayIdsString = $ids;
      }
      if (is_array($arrayIdsString) && count($arrayIdsString) && is_array($arrayIdsString[0])) return $arrayIdsString;
      return null;
    }

    /**
    * Convert arrays of array of ids to string
    *
    * @param array of arrays $arrayOfArrayOfIds integers
    * @return string array of  multi-id array string
    */
    protected function arraysOfIdsToString($arrayOfArrayOfIds){
      if ($arrayOfArrayOfIds && is_array($arrayOfArrayOfIds) && is_array($arrayOfArrayOfIds[0])) {
        $str = "{";
        $first = true;
        foreach($arrayOfArrayOfIds as $idArray){
          if ($first) {
            $first = false;
          }else{
            $str .= ",";
          }
          $str .= '{'.join(",",$idArray).'}';
        }
        $str .= '}';
        $arrayOfArrayOfIds = $str;
      }
      if (is_string($arrayOfArrayOfIds)) return $arrayOfArrayOfIds;
      return null;
    }

    /**
    * Convert array of polygons to multi polygon string
    *
    * @param array $polygons database polygon array string
    * @return NULL|string multi-polygons database string
    */
    protected function polygonsToString($polygons){
      if ($polygons && is_array($polygons)) {
        $str = "{";
        $first = true;
        foreach($polygons as $polygon){
          if ($first) {
            $first = false;
          }else{
            $str .= ",";
          }
          $str .= '"'.$polygon->getPolygonString().'"';
        }
        $str .= '}';
        $polygons = $str;
      }
      if (is_string($polygons)) return $polygons;
      return null;
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * getScratchProperty - retrieves the value of a scratch property for the given key or returns NULL
    *
    * @param string $key uniquely identifies the scratch property
    * @return string the value of the property or NULL
    */
    public function getScratchProperty($key) {
      return (($this->_scratchProperties && array_key_exists($key,$this->_scratchProperties))? $this->_scratchProperties[$key]:NULL);
    }

    /**
    * getScratchPropertiesJsonString - retrieves all scratch properties as a json string
    *
    * @return string the value of the properties or NULL
    */
    public function getScratchPropertiesJsonString() {
      return (($this->_scratchProperties && count($this->_scratchProperties) > 0) ? json_encode($this->_scratchProperties):NULL);
    }

    /**
    * storeScratchProperty - stores the key-value pair in scratch property array
    *
    * @param string $key uniquely identifies the scratch property
    * @param mixed $value of the property
    */
    public function storeScratchProperty($key,$value) {
      //property remove on none existant property case
      if ((!is_array($this->_scratchProperties) || !array_key_exists($key,$this->_scratchProperties)) && !isset($value)) {
        return;
      }
      // property save or sync cases
      if (!is_array($this->_scratchProperties) || !array_key_exists($key,$this->_scratchProperties) || $this->_scratchProperties[$key] != $value) {
        if (!is_array($this->_scratchProperties)) {
          $this->_scratchProperties = array();
        }
        if (isset($value) && $value !== '') {
          $this->_scratchProperties[$key] = $value;
        } else if (array_key_exists($key,$this->_scratchProperties)){
          unset($this->_scratchProperties[$key]);
        }
        $this->setDataKeyValuePair($this->getGlobalPrefix()."_scratch",json_encode($this->_scratchProperties));
        $this->_dirty = true;
      }
    }

    /**
    * getTempProperty - retrieves the value of a temp property for the given key or returns NULL
    *
    * @param string $key uniquely identifies the temp property
    * @return string the value of the property or NULL
    */
    public function getTempProperty($key) {
      return (($this->_tempProperties && array_key_exists($key,$this->_tempProperties))?
               $this->_tempProperties[$key]:NULL);
    }

    /**
    * removeTempProperty - removes temp property $key
    *
    * @param string $key uniquely identifies the temp property
    */
    public function removeTempProperty($key) {
      if (array_key_exists($key,$this->_tempProperties)) {
        unset($this->_tempProperties[$key]);
      }
    }

    /**
    * storeTempProperty - stores the key-value pair in temp property array
    *
    * @param string $key uniquely identifies the temp property
    * @param mixed $value of the property
    */
    public function storeTempProperty($key,$value) {
      if (!array_key_exists($key,$this->_tempProperties) || $this->_tempProperties[$key] != $value) {
        $this->_tempProperties[$key] = $value;
      }
    }

    /**
    * Get Entity's modification stamp
    *
    * @return string with the family name of editor with the date of the last modification of the entity
    */
    public function getModificationStamp() {
      $mod = explode(" ",$this->_modified);
      return $this->getOwnerFamilyName()." ".($mod && $mod[0]?$mod[0]:"not modified");
    }

    /**
    * Get Entity's Linked Annotations by annotation typeID
    *
    * @return array of string GID of annotation objects or empty array
    */
    public function getLinkedAnnotationsByType() {
      $annotations = new Annotations("'".$this->getGlobalID()."'"." = ANY(ano_linkfrom_ids) and ano_linkto_ids is null and not ano_owner_id = 1","ano_type_id,modified",null,null,null);
      if ($annotations->getCount()>0){
        $linkedAnoIDsByType = array();
        $curType = null;
        foreach ($annotations as $annotation){
          if ($curType != $annotation->getTypeID()){
            $curType = $annotation->getTypeID();
          }
          if ($curType && !array_key_exists($curType, $linkedAnoIDsByType)){
            $linkedAnoIDsByType[$curType] = array();
          }
          if ($curType){
            array_push($linkedAnoIDsByType[$curType],$annotation->getID());
          }
        }
        return $linkedAnoIDsByType;
      } else {
        return array();
      }
    }

    /**
    * Get Entity's Linked By Annotations by annotation typeID
    *
    * @return array of array of int $ID of annotation objects or empty array
    */
    public function getLinkedByAnnotationsByType() {
      $annotations = new Annotations("'".$this->getGlobalID()."'"." = ANY(ano_linkto_ids) and ano_linkfrom_ids is null and not ano_owner_id = 1","ano_type_id,modified",null,null,null);
      if ($annotations->getCount()>0){
        $linkedByAnoIDsByType = array();
        $curType = null;
        foreach ($annotations as $annotation){
          if ($curType != $annotation->getTypeID()){
            $curType = $annotation->getTypeID();
            $linkedByAnoIDsByType[$curType] = array();
          }
          array_push($linkedByAnoIDsByType[$curType],$annotation->getID());
        }
        return $linkedByAnoIDsByType;
      } else {
        return array();
      }
    }

    /**
    * Get Related Entity's by annotation typeID
    *
    * @return array of GID of entities linked by annotation objects by link type or null
    */
    public function getRelatedEntitiesByLinkType() {
      $fromEntTag = $this->getGlobalPrefix().$this->getID();
      $annotations = new Annotations("'".$this->getGlobalID()."'"." = ANY(ano_linkfrom_ids) and ano_linkto_ids is not null and not ano_owner_id = 1 ","ano_type_id,modified",null,null,null);
      if ($annotations->getCount()>0){
        $relatedEntGIDsByLinkType = array();
        $curType = null;
        foreach ($annotations as $annotation){
          $linkToIDs = $annotation->getLinkToIDs();
          if ($curType != $annotation->getTypeID()){
            $curType = $annotation->getTypeID();
            $relatedEntGIDsByLinkType[$curType] = array();
          }
          if ($curType && $linkToIDs && is_array($linkToIDs)) {
            $relatedEntGIDsByLinkType[$curType] = array_merge($relatedEntGIDsByLinkType[$curType],$linkToIDs);
          }
        }
        return $relatedEntGIDsByLinkType;
      } else {
        return array();
      }
    }

    /**
    * Add to Entity's Annotations unique IDs
    *
    * @param int $ID of annotation object to add for this Entity
    */
    public function addAnnotationID($ID) {
      if($ID) {
        $ids = array();
        if($this->_annotation_ids) {//existing IDs
          $ids = $this->getAnnotationIDs();
        }
        if (!in_array($ID,$ids)) {
          array_push($ids,$ID);
          $this->setDataKeyValuePair($this->getGlobalPrefix()."_annotation_ids",$this->idsToString($ids));
          $this->_dirty = true;
          $this->_annotation_ids = $ids;
        }
      }
    }

    /**
    * Add to Entity's Attributions unique IDs
    *
    * @param int $ID of attribution object to add for this Entity
    */
    public function addAttributionID($ID) {
      if($ID) {
        $ids = array();
        if($this->_attribution_ids) {//existing IDs
          $ids = $this->getAttributionIDs();
        }
        if (!in_array($ID,$ids)) {
          array_push($ids,$ID);
          $this->setDataKeyValuePair($this->getGlobalPrefix()."_attribution_ids",$this->idsToString($ids));
          $this->_dirty = true;
          $this->_attribution_ids = $ids;
        }
      }
    }

    /**
    * Add to Entity's Visibility unique IDs
    *
    * @param int $ID of userGroup object to add for this Entity
    */
    public function addVisibilityID($ID) {
      if($ID) {
        $ids = array();
        if($this->_visibility_ids) {//existing IDs
          $ids = $this->getVisibilityIDs();
        }
        if (!in_array($ID,$ids)) {
          array_push($ids,$ID);
          $this->setDataKeyValuePair($this->getGlobalPrefix()."_visibility_ids",$this->idsToString($ids));
          $this->_dirty = true;
          $this->_visibility_ids = $ids;
        }
      }
    }

    /**
    * Query readonly
    *
    * @return boolean indicating that this object is readonly
    */
    public function isReadonly() {
      if ($this->_visibility_ids && $this->getVisibilityIDs() && in_array(2,$this->getVisibilityIDs()) && !in_array(6,$this->getVisibilityIDs()) ||
           $this->getOwnerID() == 2 ||
          ($this->getOwnerID() &&
            $this->getOwnerID()!= getUserDefEditorID())) {  //&&//Todo: review for clone edition
//            !in_array($this->getOwnerID(), getUserMembership()))) { // decision to require impersonation for members && !in_array($this->getOwnerID(), getUserMembership()))) {
       return true;
      }
      return false;
    }

    /**
    * Query published
    *
    * @return boolean indicating that this object is published and immutable
    */
    public function isPublished() {
      return (in_array(2,$this->getVisibilityIDs()));
    }

    /**
    * Query public
    *
    * @return boolean indicating that this object is public
    */
    public function isPublic() {
      return (in_array(2,$this->getVisibilityIDs()) || in_array(6,$this->getVisibilityIDs()));
    }

    /**
    * cloneEntity
    *
    * @param boolean $copyID indicating whether the database id should be cleared from the clone
    * @return entity copy
    */
    public function cloneEntity($attrIDs = null, $visIDs = null, $ownerID = null, $copyID = false) {
      $retObj = clone $this;
      if (!$copyID) {
        $retObj->clearID();
        $retObj->synchData();
        if ($ownerID) {
          $retObj->setOwnerID($ownerID);
        } else {
          $retObj->setOwnerID(getUserDefEditorID());
        }
        if ($visIDs) {
          $retObj->setVisibilityIDs(is_array($visIDs)?$visIDs:array($visIDs));
        } else {
          $retObj->setVisibilityIDs(getUserDefVisibilityIDs());
        }
        if ($attrIDs) {
          $retObj->setAttributionIDs(is_array($attrIDs)?$attrIDs:array($attrIDs));
        } else {
          $retObj->setAttributionIDs(getUserDefAttrIDs());
        }
        $retObj->storeScratchProperty("clonedID",$this->_id);
      }
      return $retObj;
    }

    /**
    * Query errors exist
    *
    * @return boolean indicating that this object states is in error
    */
    public function hasError() {
      return (count($this->_errors) > 0);
    }

    /**
    * Query dirty
    *
    * @return boolean indicating that this object state is different than the database
    */
    public function isDirty() {
      return $this->_dirty;
    }

    /**
    * Query mark for delete
    *
    * @return boolean indicating that this object is marked for delete
    */
    public function isMarkedDelete() {
      return in_array(getMarkedForDeleteUgrpID(),$this->getVisibilityIDs());
    }

    /**
    * Delete
    * marks an entity for delete by setting visibility to System group 'MarkForDelete'
    * and giving owner ship to SysAdmin
    * @return boolean indicating that this object was successfully marked for delete
    */
    public function markForDelete($dbMgr = null) {
      if ($this->getOwnerID() != getUserID() && !isSysAdmin() && !in_array($this->getOwnerID(), getUserMembership())) {
        array_push($this->_errors,"insufficient privilledge to delete ".$this->getGlobalID());
        return false;
      }
      $this->storeScratchProperty("old_owner_id",$this->_owner_id);
      $this->storeScratchProperty("old_visibility_ids",$this->_visibility_ids);
      $this->setVisibilityIDs(array(5)); //assumes usergroup 5 is delete group
      if ($this->getGlobalPrefix() != "srf") {
        $this->setOwnerID(1);
      }
      if (!@$dbMgr ) {
        $dbMgr = new DBManager();
      }
      $res = $dbMgr->update($this->_table_name,$this->_data,$this->getGlobalPrefix()."_id=".$this->_id);
      if (!$res){
        array_push($this->_errors,$dbMgr->getError());
        return false;
      }
      $this->_dirty = false;
      $this->_data = array();
      return true;
    }

    /**
    * Save Entity
    *
    * save entity data to database using id as idicator for insert or update
    *
    * @return boolean indicating success or failure
    */
    public function save($dbMgr = null) {
      if ($this->_dirty && count($this->_data)) {
        if (!@$dbMgr ) {
          $dbMgr = new DBManager();
        }
        if ($this->_id){ // update
          if ((isset($this->_owner_id) && $this->_owner_id != getUserID() && $this->_owner_id != getUserDefEditorID() && !in_array($this->_owner_id, getUserMembership()) ||
              (in_array($this->getGlobalPrefix(),array('ugr','atg')) && !in_array(getUserID(),$this->getAdminIDs())))
              && !isSysAdmin()) {//owner/admin not equal user current user id
            array_push($this->_errors,"User with ID= ".getUserID()." attemping to edit unowned record ".$this->getGlobalPrefix()."_id=".$this->_id);
            return false;
          }
          $res = $dbMgr->update($this->_table_name,$this->_data,$this->getGlobalPrefix()."_id=".$this->_id);
          if (!$res){
            array_push($this->_errors,$dbMgr->getError());
            return false;
          }
        }else{ //insert
//          $this->synchData();
          if (in_array($this->getGlobalPrefix(),array('ugr','atg'))) {
            if (!$this->getMemberIDs() || count($this->getMemberIDs()) == 0) {//set current user id as member
              $this->setMemberIDs(array(getUserID()));
            }
            if (!$this->getAdminIDs() || count($this->getAdminIDs()) == 0) {//set current user id as member
              $this->setAdminIDs(array(getUserID()));
            }
          }
          if ($this->getGlobalPrefix() != 'ugr') {
            if (!$this->_owner_id) {//owner not set so user current user id
              $this->setOwnerID(getUserID());
            } else if (!array_key_exists($this->getGlobalPrefix().'_owner_id',$this->_data)) {//handle clone to create new
              $this->_data[$this->getGlobalPrefix().'_owner_id'] = $this->_owner_id;
            }
            if (!$this->_visibility_ids) {//visibility not set so user current user id
              $this->setVisibilityIDs(array(getUserID()));
            }
          }
          $res = $dbMgr->insert($this->_table_name,$this->_data,$this->getGlobalPrefix()."_id");
          if (!$res){
            array_push($this->_errors,$dbMgr->getError());
            return false;
          }
          $this->_id = $res;
        }
        $this->_dirty = false;
        $this->_data = array();
      }
      return true;
    }

    //********GETTERS*********

    /**
    * Get Entity global unique ID
    *
    * @return int returns the global prefix concatenated with the primary Key for the Entity
    */
    public function getGlobalID() {
      return $this->getGlobalPrefix().":".$this->_id;
    }

    /**
    * Get Entity unique Tag
    *
    * @return int returns the global prefix concatenated with the primary Key for the Entity
    */
    public function getEntityTag() {
      return $this->getGlobalPrefix().$this->_id;
    }

    /**
    * Get Entity Type Code
    *
    * @return string identifying the class code for the Entity
    */
    public function getEntityTypeCode() {
      return $this->getGlobalPrefix();
    }

    /**
    * Get Entity Class
    *
    * @return string identifying the class for the Entity
    */
    public function getEntityType() {
      return $this->_table_name;
    }

    /**
    * Get Entity unique ID
    *
    * @return int returns the primary Key for the Entity
    */
    public function getID() {
      return $this->_id;
    }

    /**
    * Get Entity's Annotations unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for annotation object IDs for this Entity
    */
    public function getAnnotationIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_annotation_ids);
      }else{
        return $this->idsStringToArray($this->_annotation_ids);
      }
    }

    /**
    * Get Entity's annotations
    *
    * @return iterator that contains annotation objects for this Entity or NULL
    */
    public function getAnnotations($autoExpand = false) {
      if (!$this->_annotations && $autoExpand) {
        $anoIDs = $this->getAnnotationIDs();
        if ($anoIDs && count($anoIDs) > 0) {
          $this->_annotations = new Annotations("ano_id in (".join(",",$anoIDs.")",null,null,false));
        }
      }
      return $this->_annotations;
    }

    /**
    * Get Entity's Attributions unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for attribuation object IDs for this Entity
    */
    public function getAttributionIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_attribution_ids);
      }else{
        return $this->idsStringToArray($this->_attribution_ids);
      }
    }

    /**
    * Get Entity's Attributions
    *
    * @return iterator that contains attribuation objects for this Entity or NULL
    */
    public function getAttributions($autoExpand = false) {
      if (!$this->_attributions && $autoExpand && count($this->getAttributionIDs())>0) {
        $this->_attributions = new Attributions("atb_id in (".join(",",$this->getAttributionIDs()).")",'atb_id',null,null,false);
      }
      return $this->_attributions;
    }

    /**
    * Get Entity's modified string
    *
    * @return string for timestamp of last modification of the Entity
    */
    public function getModified() {
      return $this->_modified;
    }

    /**
    * Get Entity's owner ID
    *
    * @return int returns the primary Key for owner of the Entity
    */
    public function getOwnerID() {
      return $this->_owner_id;
    }

    /**
    * Get Entity's Visibility IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for userGroup object IDs that can view this Entity
    */
    public function getVisibilityIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_visibility_ids);
      }else{
        return $this->idsStringToArray($this->_visibility_ids);
      }
    }

    /**
    * Get Entity's error
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string identifying the error of the Entity
    */
    public function getErrors($asString = false) {
      if ($asString) {
        return join(",",$this->_errors);
      }
      return $this->_errors;
    }

    //********SETTERS*********

    /**
    * Set Entity's Annotations unique IDs
    *
    * @param int array $annotationIDs of annotation object IDs for this Entity
    */
    public function setAnnotationIDs($annotationIDs) {
      if($this->_annotation_ids != $annotationIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair($this->getGlobalPrefix()."_annotation_ids",$this->idsToString($annotationIDs));
      }
      $this->_annotation_ids = $annotationIDs;
    }

    /**
    * Set Entity's Attributions unique IDs
    *
    * @param int array $attributionIDs of attribution object IDs for this Entity
    */
    public function setAttributionIDs($attributionIDs) {
      if($this->_attribution_ids != $attributionIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair($this->getGlobalPrefix()."_attribution_ids",$this->idsToString($attributionIDs));
      }
      $this->_attribution_ids = $attributionIDs;
    }

    /**
    * Set Entity's Owner ID
    *
    * @param int $ownerID of userGroup object that owns this Entity
    */
    public function setOwnerID($ownerID) {
      if($this->_owner_id != $ownerID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair($this->getGlobalPrefix()."_owner_id",$ownerID);
      }
      $this->_owner_id = $ownerID;
    }

    /**
    * Set Entity's Visibility IDs
    *
    * @param int array for userGroup object IDs that can view this Entity
    */
    public function setVisibilityIDs($visibilityIDs) {
      if($this->_visibility_ids != $visibilityIDs) {
        $this->_dirty = true;
        $this->setDataKeyValuePair($this->getGlobalPrefix()."_visibility_ids",$this->idsToString($visibilityIDs));
      }
      $this->_visibility_ids = $visibilityIDs;
    }

  }
?>
