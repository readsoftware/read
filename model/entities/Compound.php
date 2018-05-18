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
  * Classes to deal with Compound entities
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
  require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get utility functions
  require_once (dirname(__FILE__) . '/Entity.php');
  require_once (dirname(__FILE__) . '/Lemma.php');
  require_once (dirname(__FILE__) . '/Token.php');
  require_once (dirname(__FILE__) . '/OrderedSet.php');
  require_once (dirname(__FILE__) . '/Compounds.php');

  //*******************************************************************
  //****************   COMPOUND CLASS  *************************
  //*******************************************************************
  /**
  * Compound represents compound entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Compound.php';
  *
  * $compound = new Compound( $resultRow );
  * echo "compound has lemma # ".$compound->getLemma();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Compound extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_value,
    $_transcription,
    $_component_ids=array(),
    $_components,
    $_case_id,
    $_class_id,
    $_type_id,
    $_sort_code,
    $_sort_code2;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an compound instance from a compound table row or a token table row
    * @param array $row associated with columns of the compound table, a valid cmp_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null) {
      $this->_table_name = 'compound';
      if (is_numeric($arg) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//          $dbMgr->query("SELECT * FROM compound WHERE cmp_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= cmp_owner_id or ".getUserID()." = ANY (\"cmp_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('cmp_id',$row)) {
          error_log("unable to query for compound ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['cmp_id'] ? $arg['cmp_id']:NULL;
        $this->_value=@$arg['cmp_value'] ? $arg['cmp_value']:NULL;
        $this->_transcription=@$arg['cmp_transcription'] ? $arg['cmp_transcription']:NULL;
        $this->_component_ids=@$arg['cmp_component_ids'] ? $arg['cmp_component_ids']:NULL;
        $this->_case_id=@$arg['cmp_case_id'] ? $arg['cmp_case_id']:NULL;
        $this->_class_id=@$arg['cmp_class_id'] ? $arg['cmp_class_id']:NULL;
        $this->_type_id=@$arg['cmp_type_id'] ? $arg['cmp_type_id']:NULL;
        $this->_sort_code=@$arg['cmp_sort_code'] ? $arg['cmp_sort_code']:NULL;
        $this->_sort_code2=@$arg['cmp_sort_code2'] ? $arg['cmp_sort_code2']:NULL;
        if (!array_key_exists('cmp_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('cmp_component_ids',$arg))$arg['cmp_component_ids'] = $this->arraysOfIdsToString($arg['cmp_component_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new compound to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "cmp";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->calculateValues();
      $this->synchBaseData();
      if (count($this->_value)) {
        $this->_data['cmp_value'] = $this->_value;
      }
      if (count($this->_transcription)) {
        $this->_data['cmp_transcription'] = $this->_transcription;
      }
      if (count($this->_component_ids)) {
        $this->_data['cmp_component_ids'] = $this->arraysOfIdsToString($this->_component_ids);
      }
      if ($this->_case_id) {
        $this->_data['cmp_case_id'] = $this->_case_id;
      }
      if ($this->_class_id) {
        $this->_data['cmp_class_id'] = $this->_class_id;
      }
      if ($this->_type_id) {
        $this->_data['cmp_type_id'] = $this->_type_id;
      }
      if (count($this->_sort_code)) {
        $this->_data['cmp_sort_code'] = $this->_sort_code;
      }
      if (count($this->_sort_code2)) {
        $this->_data['cmp_sort_code2'] = $this->_sort_code2;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Get ordered list of ids for the tokens of this compound
    *
    * @return int array of ids for the tokens of this compound
    */
    public function getTokenIDs(){
      $tokenIDs = array();
      $componentIDs = $this->getComponentIDs();
      if ($componentIDs) {
        foreach($componentIDs as $gid) {
          list($prefix,$id) = explode( ":", $gid);
          switch ($prefix){
           case "cmp":
              $compound = new Compound($id);
              $tokenIDs = array_merge($tokenIDs, $compound->getTokenIDs());
              if (count($compound->getErrors())) {
                array_merge($this->_errors,$compound->getErrors());
              }
              break;
           case "tok":
              array_push($tokenIDs, $id);
              break;
           default:
              array_push($this->_errors,"unknown component type $prefix with id $id");
          }
        }
      }
      return $tokenIDs;
    }

    /**
    * Get tokens for this compound
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    public function getTokens(){
      $tokens = new OrderedSet();
      $tokens->loadEntities(explode(",","tok:".join(",tok:",$this->getTokenIDs())));
      return $tokens;
    }

    //****************************PUBLIC FUNCTIONS************************************

    /**
    * Calculate location label for this compound and update cached value
    *
    * @return string representing this compound's location in the text
    */
    public function updateLocationLabel($autosave = true){
      $locLabel = getWordLocation($this->getEntityTag());
      $this->storeScratchProperty("locLabel",$locLabel);
      if ($autosave){
        $this->save();
      }
      return $locLabel;
    }

    /**
    * Calculate baseline image boundaries and scrolltop for this compound and update cached value
    *
    * @param boolean $autosave that indicates whether to autosave
    * @return boolean indicating success
    */
    public function updateBaselineInfo($autosave = true){
      if (!$this->_id) return false;
      list($polygons,$blnScrollTop) = getWordsBaselineInfo($this->getTokenIDs());
      $this->storeScratchProperty("blnPolygons",$polygons);
      $this->storeScratchProperty("blnScrollTopInfo",$blnScrollTop);
      if ($autosave){
        $this->save();
      }
      return !$this->hasError();
    }

    /**
    * Calculate value for this compound
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateValues(){
      $tokens = $this->getTokens();
      $value = null;
      $transcription = null;
      if (@$tokens){
        $value = "";
        $transcription = "";
        $tcms = "";
        $typeVowel = Entity::getIDofTermParentLabel("vowel-graphemetype");//term dependency
        $typeNumber = Entity::getIDofTermParentLabel("numbersign-graphemetype");//term dependency
        $sort = $sort2 = "0.";
        $j = 0;
        $lastT = $tokens->getCount() - 1;
        $prevTokenLastGraphemeID = 0;
        foreach ($tokens as $token) {
          $graphemes = $token->getGraphemes(true);
          if ($graphemes && $graphemes->valid()) {
            $i = 0;
            $last = $graphemes->getCount() - 1;
            foreach ($graphemes as $grapheme){
              $decomp = $grapheme->getDecomposition();
              if (!$grapheme->getSortCode()){
                $grapheme->calculateSort();
              }
              $graSort = $grapheme->getSortCode();
              if ($prevTokenLastGraphemeID != $grapheme->getID()) {//skip any duplicate grapheme use
                $sort .= substr($graSort,0,2);
                $sort2 .= substr($graSort,2,1);
              }
              if ($decomp && ($i === 0 || $i == $last) ) {// handle sandhi vowels
                $sandhi = explode(":",$decomp);
                $strVal = ($j==0 && $i== 0 )? $sandhi[2]: // 1st token of compound with first grapheme vowel sandhi use second part of decomposition
                          (($j==$lastT && $i == $last) ? $sandhi[0] : // last token of compound with last grapheme vowel sandhi use first part of decomposion
                          (($i ||
                            $prevTokenLastGraphemeID && // test for intra-compound token transition
                              $prevTokenLastGraphemeID != $grapheme->getID()) ? $grapheme->getValue():""));
//                $strTrans = $i?$sandhi[0]:"";
                $strTrans = $i?$grapheme->getValue():"";
  /*              $strVal = $j==0 && $i== 0 ? "ʔ".$sandhi[2]: // 1st token of compound with first grapheme vowel sandhi use second part of decomposition
                          $j==$lastT && $i == $last ? $sandhi[0] : // last token of compound with last grapheme vowel sandhi use first part of decomposion
                          ($i || // test for intra-compound token transition
                            $prevTokenLastGraphemeID &&
                              $prevTokenLastGraphemeID != $grapheme->getID()) ? $grapheme->getValue():"";
                $strTrans = $i?$sandhi[0]:"ʔ".$sandhi[2]. (($j!=$lastT && $i == $last)? "-":"");
                */
              }else{
  //              if($j===0 && $i === 0 ){
                  $str = $grapheme->getValue();
  //              }else{
  //                $str = $grapheme->getGrapheme();
  //              }
                $strVal = $str.($grapheme->getType()==$typeNumber?" ":"");
                $strTrans = $str.(($j!=$lastT && $i == $last)? "-":"");
              }
              $nextTCM = $grapheme->getTextCriticalMark();
              $tcmBrackets = getTCMTransitionBrackets($tcms,$nextTCM);
              $value .= $strVal;
              $transcription .= $tcmBrackets.$strTrans;
              $tcms = $nextTCM;
              if($i == $last) {
                $prevTokenLastGraphemeID = $grapheme->getID();
              }
              $i++;
            }
          }
          $j++;
        }//for tokens
        if ($tcms) {
          $transcription .= getTCMTransitionBrackets($tcms,"");
        }
      }
      $this->setSortCode($sort);
      $this->setSortCode2($sort2);
      $this->setCompound($value);
      $this->setTranscription($transcription);
    }

    /**
    * Calculate sort codes for this compound
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateSortCodes(){
      $tokens = $this->getTokens();
      if (@$tokens && $tokens->getCount()){
        $sort = $sort2 = "0.";
        foreach ($tokens as $token) {
          if (!$token->getSortCode() ){
            $token->calculateSortCodes();
          }
          $tokSort = $token->getSortCode();
          $tokSort2 = $token->getSortCode2();
          $sort .= substr($tokSort,2);
          $sort2 .= substr($tokSort2,2);
        }
        $this->setSortCode($sort);
        $this->setSortCode2($sort2);
        return true;
      }
      return false;
    }


    //********GETTERS*********

    /**
    * Get Compound's value
    *
    * @param boolean $reCalculate that indicates whether to recalculate
    * @return string value of this compound
    */
    public function getValue($reCalculate = false) {
      if (!$this->_value || $reCalculate) {
        $this->calculateValues();
      }
      return $this->_value;
    }

    /**
    * Get Compound's value
    *
    * @param boolean $reCalculate that indicates whether to recalculate
    * @return string value of this compound
    */
    public function getCompound($reCalculate = false) {
      return $this->getValue($reCalculate);
    }

    /**
    * Get Compound's location label
    *
    * @param boolean $reCalculate that indicates whether to recalculate location label
    * @return string location label of this compound
    */
    public function getLocation($reCalculate = false, $autosave = true) {
      if ($reCalculate || !$this->getScratchProperty("locLabel")){
        return $this->updateLocationLabel($autosave);
      }
      return $this->getScratchProperty("locLabel");
    }

    /**
    * Get Compound's boundary polygons
    *
    * @param boolean $reCalculate that indicates whether to recalculate Baseline info
    * @return array of polygons indexed by baseline id
    */
    public function getBaselinePolygons($reCalculate = false, $autosave = true) {
      if ($reCalculate || !$this->getScratchProperty("blnPolygons")){
        $this->updateBaselineInfo($autosave);
      }
      return $this->getScratchProperty("blnPolygons");
    }

    /**
    * Get Compound's scrolltop info
    *
    * @param boolean $reCalculate that indicates whether to recalculate Baseline info
    * @return array of scrolltop information objects indexed by baseline id
    */
    public function getScrollTopInfo($reCalculate = false, $autosave = true) {
      if ($reCalculate || !$this->getScratchProperty("blnScrollTopInfo")){
        $this->updateBaselineInfo($autosave);
      }
      return $this->getScratchProperty("blnScrollTopInfo");
    }

    /**
    * Get Compound's transcription
    *
    * @param boolean $reCalculate that indicates whether to recalculate
    * @return string transcription of this compound or NULL
    */
    public function getTranscription($reCalculate = false) {
      if (!$this->_transcription || $reCalculate) {
        $this->calculateValues();
      }
      return $this->_transcription;
    }

    /**
    * Get Compound's component unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string that contains a list of component typPrefix:IDs of this compound
    */
    public function getComponentIDs($asString = false) {
      if ($asString){// don't have to test for token as componentIDs will be null
        return $this->stringsToString($this->_component_ids);
      }else{
        return $this->stringOfStringsToArray($this->_component_ids);
      }
    }

    /**
    * Get Compound's component objects
    *
    * @return Compound iterator for the components of this compound or NULL
    */
    public function getComponents($autoExpand = false) {
      if (!$this->_components && $autoExpand && count($this->getComponentIDs())>0) {
        $this->_components = new Compounds();
        $this->_components->setAutoAdvance(false);
        $this->_components->loadComponents($this->getComponentIDs());
      }
      return $this->_components;
    }

    /**
    * Get Case of the compound
    *
    * @return string from a typology of terms for Case for compounds or tokens
    */
    public function getCase() {
      return @$this->_case_id;
    }

    /**
    * Get Class of the compound
    *
    * @return string from a typology of terms for Class of compounds
    */
    public function getClass() {
      return @$this->_class_id;
    }

    /**
    * Get Type of the compound
    *
    * @return string from a typology of terms for types for compounds
    */
    public function getType() {
      return @$this->_type_id;
    }

    /**
    * Get Sort Code of the Compound
    *
    * @return string sort code of this Compound
    */
    public function getSortCode() {
      return $this->_sort_code;
    }

    /**
    * Get Secondary Sort Code of the Compound
    *
    * @return string secondary sort code of this Compound
    */
    public function getSortCode2() {
      return $this->_sort_code2;
    }

    /**
    * Get Compound's annotations
    *
    * @return iterator that contains annotation objects for this Compound or NULL
    */
    public function getAnnotations($autoExpand = false) {
      if (!$this->_annotations && $autoExpand && count($this->getAnnotationIDs())>0) {
        $this->_annotations = new Annotations("ano_id in (".join(",",$this->getAnnotationIDs()).")",null,null,null);
        $this->_annotations->setAutoAdvance(false);
      }
      return $this->_annotations;
    }

    /**
    * Get Compound's Attributions unique IDs
    *
    * @return int array for attribuation object IDs for this Compound
    */
    public function getAttributionIDs($asString = false) {
      if ($asString) {
        return $this->idsToString($this->_attribution_ids);
      } else {
        return $this->idsStringToArray($this->_attribution_ids);
      }
    }

    /**
    * Get Compound's Attributions
    *
    * @return iterator that contains attribuation objects for this Compound or NULL
    */
    public function getAttributions($autoExpand = false) {
      if (!$this->_attributions && $autoExpand && count($this->getAttributionIDs())>0) {
        $this->_attributions = new Attributions("atb_id in (".join(",",$this->getAttributionIDs()).")",null,null,null);
        $this->_attributions->setAutoAdvance(false);
      }
      return $this->_attributions;
    }

    //********SETTERS*********

    /**
    * Set Compound's value
    *
    * @param string $token of this compound
    */
    public function setCompound($compound) {
      if($this->_value != $compound) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_value",$compound);
      }
      $this->_value = $compound;
    }

    /**
    * Set Compound's transcription
    *
    * @param string transcription of this compound or NULL
    */
    public function setTranscription($transcription) {
      if($this->_transcription != $transcription) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_transcription",$transcription);
      }
      $this->_transcription = $transcription;
    }

    /**
    * Set Compound's component unique IDs
    *
    * @param string array that contains component IDs of this compound
    */
    public function setComponentIDs($ids) {
      if($this->_component_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_component_ids",$this->stringsToString($ids));
      }
      $this->_component_ids = $ids;
    }

    /**
    * Set Case of the compound
    *
    * @param string $case from a typology of terms for Case for compounds or tokens
    */
    public function setCase($case) {
      if($this->_case_id != $case) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_case_id",$case);
      }
      $this->_case_id = $case;
    }

    /**
    * Set Class of the compound
    *
    * @param string $class from a typology of terms for Class for compounds
    */
    public function setClass($class) {
      if($this->_class_id != $class) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_class_id",$class);
      }
      $this->_class_id = $class;
    }

    /**
    * Set Type of the compound
    *
    * @param string  $type from a typology of terms for Type for compounds
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Set Sort Code of the compound
    *
    * @param string $sc sort code of this compound
    */
    public function setSortCode($sc) {
      if(strcmp($this->_sort_code, $sc)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_sort_code",$sc);
      }
      $this->_sort_code = $sc;
    }

    /**
    * Set Secondary Sort Code of the compound
    *
    * @param string $sc2 sort code of this compound
    */
    public function setSortCode2($sc2) {
      if(strcmp($this->_sort_code2, $sc2)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("cmp_sort_code2",$sc2);
      }
      $this->_sort_code2 = $sc2;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
