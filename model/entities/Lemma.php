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
  * Classes to deal with Lemma entities
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
  require_once (dirname(__FILE__) . '/Entity.php');
  require_once (dirname(__FILE__) . '/Tokens.php');

//*******************************************************************
//****************   LEMMA CLASS  *************************
//*******************************************************************
  /**
  * Lemma represents lemma entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Lemma.php';
  *
  * $lemma = new Lemma( $resultRow );
  * echo "lemma is ".$lemma->getLemma();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Lemma extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_value,
              $_search,
              $_translation,
              $_homographOrder,
              $_type_id,
              $_certainty,
              $_part_of_speech_id,
              $_subpart_of_speech_id,
              $_nominal_gender_id,
              $_verb_class_id,
              $_declension_id,
              $_description,
              $_catalog_id,
              $_component_ids=array(),
              $_components,
              $_sort_code,
              $_sort_code2,
              $_parallels = array();


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an lemma instance from an lemma table row
    * @param array $row associated with columns of the lemma table, a valid lem_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'lemma';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM lemma WHERE lem_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= lem_owner_id or ".getUserID()." = ANY (\"lem_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('lem_id',$row)) {
          error_log("unable to query for lemma ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg) && array_key_exists('lem_id',$arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['lem_id'] ? $arg['lem_id']:NULL;
        $this->_value=@$arg['lem_value'] ? $arg['lem_value']:NULL;
        $this->_search=@$arg['lem_search'] ? $arg['lem_search']:NULL;
        $this->_translation=@$arg['lem_translation'] ? $arg['lem_translation']:NULL;
        $this->_homographOrder=@$arg['lem_homographorder'] ? $arg['lem_homographorder']:NULL;
        $this->_type_id=@$arg['lem_type_id'] ? $arg['lem_type_id']:NULL;
        $this->_certainty=@$arg['lem_certainty'] ? $arg['lem_certainty']:NULL;
        $this->_part_of_speech_id=@$arg['lem_part_of_speech_id'] ? $arg['lem_part_of_speech_id']:NULL;
        $this->_subpart_of_speech_id=@$arg['lem_subpart_of_speech_id'] ? $arg['lem_subpart_of_speech_id']:NULL;
        $this->_verb_class_id=@$arg['lem_verb_class_id'] ? $arg['lem_verb_class_id']:NULL;
        $this->_nominal_gender_id=@$arg['lem_nominal_gender_id'] ? $arg['lem_nominal_gender_id']:NULL;
        $this->_declension_id=@$arg['lem_declension_id'] ? $arg['lem_declension_id']:NULL;
        $this->_description=@$arg['lem_description'] ? $arg['lem_description']:NULL;
        $this->_catalog_id=@$arg['lem_catalog_id'] ? $arg['lem_catalog_id']:NULL;
        $this->_component_ids=@$arg['lem_component_ids'] ? $arg['lem_component_ids']:NULL;
        $this->_sort_code=@$arg['lem_sort_code'] ? $arg['lem_sort_code']:NULL;
        $this->_sort_code2=@$arg['lem_sort_code2'] ? $arg['lem_sort_code2']:NULL;
        if (!array_key_exists('lem_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('lem_component_ids',$arg))$arg['lem_component_ids'] = $this->stringOfStringsToArray($arg['lem_component_ids']);
          if (array_key_exists('lem_certainty',$arg))$arg['lem_certainty'] = $this->idsToString($arg['lem_certainty']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new lemma to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "lem";
    }

        /**
    * Calculate search value for $val
    *
    * @param string $val a lemma display string possibly containing TCMs and morpheme divider
    * @return string for searching
    */
    public function calcSearchValue($val){
      return preg_replace('/<|>|\{|\}|\[|\]|\(|\)|\-|-|⟨|⟫|⟪|⟩|«|»|·|\!|\~|\^|\=|\*|†/',"",$val);
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if (count($this->_value)) {
        $this->_data['lem_value'] = $this->_value;
      }
      if (count($this->_search)) {
        $this->_data['lem_search'] = $this->_search;
      }
      if (count($this->_translation)) {
        $this->_data['lem_translation'] = $this->_translation;
      }
      if ($this->_homographOrder) {
        $this->_data['lem_homographorder'] = $this->_homographOrder;
      }
      if ($this->_type_id) {
        $this->_data['lem_type_id'] = $this->_type_id;
      }
      if ($this->_certainty) {
        $this->_data['lem_certainty'] = $this->_certainty;
      }
      if ($this->_verb_class_id) {
        $this->_data['lem_verb_class_id'] = $this->_verb_class_id;
      }
      if ($this->_part_of_speech_id) {
        $this->_data['lem_part_of_speech_id'] = $this->_part_of_speech_id;
      }
      if ($this->_subpart_of_speech_id) {
        $this->_data['lem_subpart_of_speech_id'] = $this->_subpart_of_speech_id;
      }
      if ($this->_nominal_gender_id) {
        $this->_data['lem_nominal_gender_id'] = $this->_nominal_gender_id;
      }
      if ($this->_declension_id) {
        $this->_data['lem_declension_id'] = $this->_declension_id;
      }
      if ($this->_description) {
        $this->_data['lem_description'] = $this->_description;
      }
      if ($this->_catalog_id) {
        $this->_data['lem_catalog_id'] = $this->_catalog_id;
      }
      if ($this->_component_ids) {
        $this->_data['lem_component_ids'] = $this->_component_ids;
      }
      if (count($this->_sort_code)) {
        $this->_data['lem_sort_code'] = $this->_sort_code;
      }
      if (count($this->_sort_code2)) {
        $this->_data['lem_sort_code2'] = $this->_sort_code2;
      }
    }

    protected function replaceClassNasals($string) {
      $anusvara   = array("ṃk", "ṃḱ", "ṃḵ", "ṃg", "ṃḡ", "ṃṅ", "ṃc", "ṃj", "ṃñ", "ṃṭ", "ṃḍ", "ṃṇ", "ṃt", "ṃṯ", "ṃd", "ṃḏ", "ṃn", "ṃp", "ṃb", "ṃm", "ṃṁ");
      $classnasal = array("ṅk", "ṅḱ", "ṅḵ", "ṅg", "ṅḡ", "ṅṅ", "ñc", "ñj", "ññ", "ṇṭ", "ṇḍ", "ṇṇ", "nt", "nṯ", "nd", "nḏ", "nn", "mp", "mb", "mm", "mṁ");

      $return = str_replace($anusvara, $classnasal, $string);

      return $return;
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Calculate sort value for this lemma
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateSortCodes(){
      global $graphemeCharacterMap;
      $str = $this->calcSearchValue($this->_value);
      $this->setSearchValue($str);
      $str = $this->replaceClassNasals($str);
      $cnt = mb_strlen($str);
      $sort = $sort2 = "0.";
      $prevTyp = "V"; //setup for leading vowel to get carrier sort code
      $errors = array();
      $srt = $typ = null;
      for ($i =0; $i<$cnt;) {
        $inc=1;
        $char = mb_substr($str,$i,1);
        $testChar = $char;
        $char = mb_strtolower($char);

        // convert multi-byte to grapheme - using greedy lookup
        if ($char && array_key_exists($char,$graphemeCharacterMap)) {
          //check next character included
          $char2 = mb_substr($str,$i+1,1);
          if (($i+1 < $cnt) && $char2 && array_key_exists($char2,$graphemeCharacterMap[$char])){ // another char for grapheme
            $char3 = mb_substr($str,$i+2,1);
            if (($i+2 < $cnt) && $char3 && array_key_exists($char3,$graphemeCharacterMap[$char][$char2])){ // another char for grapheme
              $char4 = mb_substr($str,$i+3,1);
              if (($i+3 < $cnt) && $char4 && array_key_exists($char4,$graphemeCharacterMap[$char][$char2][$char3])){ // another char for grapheme
                $char5 = mb_substr($str,$i+4,1);
                if (($i+4 < $cnt) && array_key_exists($char5,$graphemeCharacterMap[$char][$char2][$char3][$char4])){ // another char for grapheme
                  $char6 = mb_substr($str,$i+5,1);
                  if (($i+5 < $cnt) && array_key_exists($char6,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])){ // another char for grapheme
                    $char7 = mb_substr($str,$i+6,1);
                    if (($i+6 < $cnt) && array_key_exists($char7,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])){ // another char for grapheme
                      $char8 = mb_substr($str,$i+7,1);
                      if (($i+7 < $cnt) && array_key_exists($char8,$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])){ // another char for grapheme
                        if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8])){ // invalid sequence
                          array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7$char8 has no sort code"." cfg line # $cfgLnCnt");
                          return false;
                        }else{//found valid grapheme, save it
                          if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8])) {
                            $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8]['ssrt'];
                          } else {
                            $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7][$char8]['srt'];
                          }
                        }
                      }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])){ // invalid sequence
                        array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5$char6$char7 has no sort code"." cfg line # $cfgLnCnt");
                        return false;
                      }else{//found valid grapheme, save it
                        if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7])) {
                          $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7]['ssrt'];
                        } else {
                          $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6][$char7]['srt'];
                        }
                      }
                    }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])){ // invalid sequence
                      array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5$char6 has no sort code"." cfg line # $cfgLnCnt");
                      return false;
                    }else{//found valid grapheme, save it
                      if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6])) {
                        $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6]['ssrt'];
                      } else {
                        $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5][$char6]['srt'];
                      }
                    }
                  }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])){ // invalid sequence
                    array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4$char5 has no sort code"." cfg line # $cfgLnCnt");
                    return false;
                  }else{//found valid grapheme, save it
                    if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4][$char5])) {
                      $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5]['ssrt'];
                    } else {
                      $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4][$char5]['srt'];
                    }
                  }
                }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3][$char4])){ // invalid sequence
                  array_push($this->_errors,"incomplete grapheme $char$char2$char3$char4, has no sort code");
                }else{//found valid grapheme, save it
                  if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3][$char4])) {
                    $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4]['ssrt'];
                  } else {
                    $srt = $graphemeCharacterMap[$char][$char2][$char3][$char4]['srt'];
                  }
                }
              }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2][$char3])){ // invalid sequence
                array_push($this->_errors,"incomplete grapheme $char$char2$char3, has no sort code");
              }else{//found valid grapheme, save it
                if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2][$char3])) {
                  $srt = $graphemeCharacterMap[$char][$char2][$char3]['ssrt'];
                } else {
                  $srt = $graphemeCharacterMap[$char][$char2][$char3]['srt'];
                }
              }
            }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char][$char2])){ // invalid sequence
              array_push($this->_errors,"incomplete grapheme $char$char2, has no sort code");
            }else{//found valid grapheme, save it
              if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char][$char2])) {
                $srt = $graphemeCharacterMap[$char][$char2]['ssrt'];
              } else {
                $srt = $graphemeCharacterMap[$char][$char2]['srt'];
              }
            }
          }else if ((!defined("USESKTSORT")|| !USESKTSORT) && !array_key_exists("srt",$graphemeCharacterMap[$char])){ // invalid sequence
            array_push($this->_errors,"incomplete grapheme $char, has no sort code");
          }else{//found valid grapheme, save it
            if (defined("USESKTSORT") && USESKTSORT && array_key_exists("ssrt",$graphemeCharacterMap[$char])) {
              $srt = $graphemeCharacterMap[$char]['ssrt'];
            } else {
              $srt = $graphemeCharacterMap[$char]['srt'];
            }
          }
        }
        if ($srt && $typ && $typ == "V" && $prevTyp=="V") {
          $sort .= "19";
          $sort2 .= "5";
        } else if (!$typ) {
          array_push($errors, "No grapheme type found for char pos $i of $str");
        }
        if ($srt) {
          $sort .= substr($srt, 0, 2);
          $sort2 .= substr($srt, 2, 1);
        } else {
          array_push($errors, "No grapheme sort code found for char pos $i of $str");
        }
        $i += $inc;//adjust read pointer
        $prevTyp = $typ;
        $typ = $srt = $char = $char2 = $char3 = $char4 = null;
      }
      $this->setSortCode($sort);
      $this->setSortCode2($sort2);
    }

    //********GETTERS*********

    /**
    * Get Lemma's display value with morpheme dividers or TCMs
    *
    * @return string value of this lemma
    */
    public function getValue() {
      return $this->_value;
    }

    /**
    * Get Lemma's Catalog's unique ID
    *
    * @return int returns the primary Key for the catalog of this lemma
    */
    public function getCatalogID() {
      return $this->_catalog_id;
    }

    /**
    * Get Lemma's catalog object
    *
    * @return catalog that contains this lemma or NULL
    */
    public function getCatalog($autoExpand = false) {
      if (!$this->_catalog && $autoExpand && is_numeric($this->_catalog_id)) {
        $this->_catalog = new Catalog(intval($this->_catalog_id));
      }
      return $this->_catalog;
    }

    /**
    * Get Lemma's component unique IDs
    *
    * @param boolean $asString determines when to return as a string (default = false)
    * @return string array|string that contains a list of component typPrefix:IDs of this lemma
    */
    public function getComponentIDs($asString = false) {
      if ($asString){// don't have to test for token as componentIDs will be null
        return $this->stringsToString($this->_component_ids);
      }else{
        return $this->stringOfStringsToArray($this->_component_ids);
      }
    }

    /**
    * Get Lemma's component objects
    *
    * @return Lemma iterator for the components of this lemma or NULL
    */
    public function getComponents($autoExpand = false) {
      if (!$this->_components && $autoExpand && $this->_component_ids && count($this->getComponentIDs())>0) {
        $this->_components = new OrderedSet();
        $this->_components->loadEntities($this->getComponentIDs());
      }
      return $this->_components;
    }

    /**
    * Get TypeID of the Lemma
    *
    * @param int indentifying the term from typology of terms for types of Lemma
    * @return string from a typology of terms for types for Lemmata
    */
    public function getTypeID() {
      return $this->_type_id;
    }

    /**
    * Get Type of the Lemma
    *
    * @param string $lang identifying which language to return
    * @return string from a typology of terms for types for Lemmata
    */
    public function getType($lang = "default") {
      return Entity::getTermFromID($this->_type_id);
    }

    /**
    * Get Lemma's certainty values
    *
    * @param boolean $asString determines when to return as a string (default = false)
    * @return int array|string of certainty values of the decomposition for this lemma
    */
    public function getCertainty($asString = false) {
      if ($asString){
        return $this->idsToString($this->_certainty);
      }else{
        return $this->idsStringToArray($this->_certainty);
      }
    }

   /**
    * Get Lemma's compAnalysis
    *
    * @return string encoding the compound analysis of this lemma
    */
    public function getCompoundAnalysis() {
      return $this->getScratchProperty('compAnalysis');
    }

   /**
    * Get Lemma's homographOrder
    *
    * @return int homographOrder of this lemma
    */
    public function getHomographicOrder() {
      return $this->_homographOrder;
    }

    /**
    * Get Lemma's translation
    *
    * @return string translation of this lemma
    */
    public function getTranslation() {
      return $this->_translation;
    }

    /**
    * Get Lemma's gloss/description
    * @return string gloss/description of this lemma
    */
    public function getDescription() {
      return $this->_description;
    }

    /**
    * Gets Lemma's search form
    * @return string search value
    */
    public function getSearchValue() {
      return $this->_search;
    }

    /**
    * Get Declension of the lemma
    *
    * @return string from a typology of terms for Declension for lemmas
    */
    public function getDeclension() {
      return $this->_declension_id;
    }

    /**
    * Get Nominal Gender of the lemma
    *
    * @return string from a typology of terms for Nominal Gender for lemmas
    */
    public function getGender() {
      return $this->_nominal_gender_id;
    }

    /**
    * Get Part of Speech of the lemma
    *
    * @return string from a typology of terms for Part of Speech for lemmas
    */
    public function getPartOfSpeech() {
      return $this->_part_of_speech_id;
    }

    /**
    * Get Subpart of Speech of the lemma
    *
    * @return string from a typology of terms for Subpart of Speech for lemmas
    */
    public function getSubpartOfSpeech() {
      return $this->_subpart_of_speech_id;
    }

    /**
    * Get Verbal Class of the lemma
    *
    * @return string from a typology of terms for Verbal Class for lemmas
    */
    public function getVerbalClass() {
      return $this->_verb_class_id;
    }

    /**
    * Get Sort Code of the lemma
    *
    * @return string sort code of this lemma
    */
    public function getSortCode() {
      return $this->_sort_code;
    }

    /**
    * Get Secondary Sort Code of the lemma
    *
    * @return string secondary sort code of this lemma
    */
    public function getSortCode2() {
      return $this->_sort_code2;
    }

    //********SETTERS*********

    /**
    * Set Lemma's value
    *
    * @param string $lemma value of this lemma
    */
    public function setValue($lemma) {
      if($this->_value != $lemma) {
        $this->_dirty = true;
        $this->_value = $lemma;
        $this->calculateSortCodes();
        $this->setDataKeyValuePair("lem_value",$lemma);
      }
    }

    /**
    * Set Lemma's value
    *
    * @param string $lemma value of this lemma
    */
    public function setLemma($lemma) {
      $this->setValue($lemma);
    }

    /**
    * Set Catalog ID of the lemma
    *
    * @param int $catID unique id of the catalog for this lemma
    */
    public function setCatalogID($catID) {
      if($this->_catalog_id != $catID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_catalog_id",$catID);
      }
      $this->_catalog_id = $catID;
    }

    /**
    * Set Lemma's component unique globalIDs
    *
    * @param string array that contains component globalIDs for attested forms of this lemma
    */
    public function setComponentIDs($ids) {
      if($this->stringsToString($this->_component_ids) != $this->stringsToString($ids)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_component_ids",$this->stringsToString($ids));
        $this->_component_ids = $ids;
      }
    }

    /**
    * Set Type of the lemma
    *
    * @param int $typeID indentifying id of term from a typology of terms for lemmata
    */
    public function setTypeID($typeID) {
      if($this->_type_id != $typeID) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_type_id",$typeID);
      }
      $this->_type_id = $typeID;
    }

    /**
    * Set Lemma's Certainty Values
    *
    * @param int array $certainties of certainty values for this Lemma
    */
    public function setCertainty($certainties) {
      if($this->_certainty != $certainties) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_certainty",$this->idsToString($certainties));
      }
      $this->_certainty = $certainties;
    }

   /**
    * Set Lemma's compAnalysis
    *
    * @param string encoding the compound analysis of this lemma
    */
    public function setCompoundAnalysis($compAnalysis) {
      return $this->storeScratchProperty('compAnalysis',$compAnalysis);
    }

   /**
    * Set Lemma's homographOrder
    *
    * @param int $ord homographOrder of this lemma
    */
    public function setHomographicOrder($ord) {
      if($this->_homographOrder != $ord) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_homographOrder",$ord);
      }
      $this->_homographOrder = $ord;
    }

    /**
    * Set Lemma's translation
    *
    * @param string $tran translation of this lemma
    */
    public function setTranslation($tran) {
      if($this->_translation != $tran) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_translation",$tran);
      }
      $this->_translation = $tran;
    }

    /**
    * Sets Lemma's search form
    * @param string $srchForm search form
    */
    public function setSearchValue($srchVal) {
      if($this->_search != $srchVal) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_search",$srchVal);
      }
      $this->_search = $srchVal;
    }

    /**
    * Set Description of the lemma
    *
    * @param string $descr describing this lemmas
    */
    public function setDescription($descr) {
      if($this->_description != $descr) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_description",$descr);
      }
      $this->_description = $descr;
    }

    /**
    * Set Declension of the lemma
    *
    * @param string $decl from a typology of terms for Declension for lemmas
    */
    public function setDeclension($decl) {
      if($this->_declension_id != $decl) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_declension_id",$decl);
      }
      $this->_declension_id = $decl;
    }

    /**
    * Set Nominal Gender of the lemma
    *
    * @param string $gend from a typology of terms for Nominal Gender for lemmas
    */
    public function setGender($gend) {
      if($this->_nominal_gender_id != $gend) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_nominal_gender_id",$gend);
      }
      $this->_nominal_gender_id = $gend;
    }

    /**
    * Set Part of Speech of the lemma
    *
    * @param string $PoS from a typology of terms for Part of Speech for lemmas
    */
    public function setPartOfSpeech($PoS) {
      if($this->_part_of_speech_id != $PoS) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_part_of_speech_id",$PoS);
      }
      $this->_part_of_speech_id = $PoS;
    }

    /**
    * Set Subpart of Speech of the lemma
    *
    * @param string $PoS from a typology of terms for Subpart of Speech for lemmas
    */
    public function setSubpartOfSpeech($sPoS) {
      //TODO validate that subPos has POS for parent
      if($this->_subpart_of_speech_id != $sPoS) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_subpart_of_speech_id",$sPoS);
      }
      $this->_subpart_of_speech_id = $sPoS;
    }

    /**
    * Set Verbal Class of the lemma
    *
    * @param string $verbClass from a typology of terms for Verbal Class for lemmas
    */
    public function setVerbalClass($verbClass) {
      if($this->_verb_class_id != $verbClass) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_verb_class_id",$verbClass);
      }
      $this->_verb_class_id = $verbClass;
    }

    /**
    * Set primary Sort Code of the lemma
    *
    * @param string $sort primary code for this lemma
    */
    public function setSortCode($sort) {
      if(strcmp($this->_sort_code, $sort)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_sort_code",$sort);
      }
      $this->_sort_code = $sort;
    }

    /**
    * Set secondary Sort Code of the lemma
    *
    * @param string $sort secondary code for this lemma
    */
    public function setSortCode2($sort2) {
      if(strcmp($this->_sort_code2, $sort2)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("lem_sort_code2",$sort2);
      }
      $this->_sort_code2 = $sort2;
    }
    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
