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
  * Classes to deal with Token entities
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
  require_once (dirname(__FILE__) . '/../utility/textCriticalMarks.php');//get TCM utilities
  require_once (dirname(__FILE__) . '/../../common/php/utils.php');//get utils
  require_once (dirname(__FILE__) . '/Entity.php');
  require_once (dirname(__FILE__) . '/Graphemes.php');

  //*******************************************************************
  //****************   TOKEN CLASS  *************************
  //*******************************************************************
  /**
  * Token represents token entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Token.php';
  *
  * $token = new Token( $resultRow );
  * echo "token has layer # ".$token->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Token extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_value,
              $_transcription,
              $_grapheme_ids = array(),
              $_graphemes,
              $_nom_affix,
              $_sort_code,
              $_sort_code2;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an token instance from a token table row
    * @param array $row associated with columns of the token table, a valid tok_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null, $dbMgr = null ) {
      $this->_table_name = 'token';
      if (is_numeric($arg)&& is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = ($dbMgr ? $dbMgr : new DBManager());
//        $dbMgr->query("SELECT * FROM token WHERE tok_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= tok_owner_id or ".getUserID()." = ANY (\"tok_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow();
        if(!$row || !array_key_exists('tok_id',$row)) {
          error_log("unable to query for token ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['tok_id'] ? $arg['tok_id']:NULL;
        $this->_value=@$arg['tok_value'] ? $arg['tok_value']:NULL;
        $this->_transcription=@$arg['tok_transcription'] ? $arg['tok_transcription']:NULL;
        $this->_grapheme_ids=@$arg['tok_grapheme_ids'] ? $arg['tok_grapheme_ids']:NULL;
        $this->_nom_affix=@$arg['tok_nom_affix'] ? $arg['tok_nom_affix']:NULL;
        $this->_sort_code=@$arg['tok_sort_code'] ? $arg['tok_sort_code']:NULL;
        $this->_sort_code2=@$arg['tok_sort_code2'] ? $arg['tok_sort_code2']:NULL;
        if (!array_key_exists('tok_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('tok_grapheme_ids',$arg))$arg['tok_grapheme_ids'] = $this->idsToString($arg['tok_grapheme_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new token to be initialized through setters
    }
    //****************************STATIC MEMBERS***************************************

    public static function compareCompoundsAndTokens($tokA,$tokB) {
      if ($tokA->getSortCode() < $tokb->getSortCode()) {
        return -1;
      } else if ($tokA->getSortCode() > $tokb->getSortCode()) {
        return 1;
      } else if ($tokA->getSortCode2() < $tokb->getSortCode2()) {
        return -1;
      } else if ($tokA->getSortCode2() > $tokb->getSortCode2()) {
        return 1;
      }
      return 0;
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "tok";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->calculateValues();
      $this->synchBaseData();
      if (count($this->_grapheme_ids)) {
        $this->_data['tok_grapheme_ids'] = $this->idsToString($this->_grapheme_ids);
      }
      if (count($this->_value)) {
        $this->_data['tok_value'] = $this->_value;
      }
      if (count($this->_transcription)) {
        $this->_data['tok_transcription'] = $this->_transcription;
      }
      if ($this->_nom_affix) {
        $this->_data['tok_nom_affix'] = $this->_nom_affix;
      }
      if (count($this->_sort_code)) {
        $this->_data['tok_sort_code'] = $this->_sort_code;
      }
      if (count($this->_sort_code2)) {
        $this->_data['tok_sort_code2'] = $this->_sort_code2;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Calculate location label for this token and update cached value
    *
    * @return string representing this token's location in the text
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
    * Calculate baseline image boundaries and scrolltop for this token and update cached value
    *
    * @param boolean $autosave that indicates whether to autosave
    * @return boolean indicating success
    */
    public function updateBaselineInfo($autosave = true){
      if (!$this->_id) return false;
      list($polygons,$blnScrollTop) = getWordsBaselineInfo($this->_id);
      $this->storeScratchProperty("blnPolygons",$polygons);
      $this->storeScratchProperty("blnScrollTopInfo",$blnScrollTop);
      if ($autosave){
        $this->save();
      }
      return !$this->hasError();
    }

    /**
    * Calculate value for this token
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateValues(){
      if (array_key_exists("tok_grapheme_ids",$this->_data)) {//check if graphemeIDs have changed
        $this->_graphemes = null; // and ensure refresh
      }
      $graphemes = $this->getGraphemes(true);
      $value = null;
      $transcription = null;
      if (@$graphemes){
        $value = "";
        $transcription = "";
        $tcms = "";
        $typeVowel = Entity::getIDofTermParentLabel("vowel-graphemetype");//term dependency
        $sort = $sort2 = "0.";
        $i = 0;
        $last = $graphemes->getCount() - 1;
        $vowelCarrier = false;
        foreach ($graphemes as $grapheme){
          $decomp = $grapheme->getDecomposition();
          if (!$grapheme->getSortCode()){
            $grapheme->calculateSort();
          }
          $graSort = $grapheme->getSortCode();
          if ($decomp && ($i === 0 || $i == $last) ) {// handle sandhi vowels
            $sandhi = explode(":",$decomp);
            $str = $i?$sandhi[0]:$sandhi[2];
//            $str = $i?$sandhi[0]:"ʔ".$sandhi[1];
            $sort .= ($i?"19":"19").substr($graSort,0,2);//ensure that vowel sandhi sorts like token starting with a vowel by faking a vowel carrier.
            $sort2 .= ($i?"5":"5").substr($graSort,2,1);
          }else{
//            if($i===0 || $i === 1 && $vowelCarrier){
              $str = $grapheme->getValue();// be sure to get uppercase if it exist
//            } else {
//              $str = $grapheme->getGrapheme();
//            }
            if($i===0 && $str == "ʔ"){
              $vowelCarrier = true;
            }
            if ($grapheme->getType()==$typeVowel && $i == 0) {
              $sort .= "19";
              $sort2 .= "5";
            }
            $sort .= substr($graSort,0,2);
            $sort2 .= substr($graSort,2,1);
          }
          $nextTCM = $grapheme->getTextCriticalMark();
          $tcmBrackets = "";
          if ($nextTCM != $tcms) {
            $tcmBrackets = getTCMTransitionBrackets($tcms,$nextTCM);
          }
          $value .= $str;
          $transcription .= $tcmBrackets.$str;
          $tcms = $nextTCM;
          $i++;
        }
        if ($tcms) {
          $transcription .= getTCMTransitionBrackets($tcms,"");
        }
      }
      if(@$sort) {
        $this->setSortCode($sort);
      }
      if(@$sort2) {
        $this->setSortCode2($sort2);
      }
      $this->setToken($value);
      $this->setTranscription($transcription);
    }

    /**
    * Calculate sort codes for this token
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateSortCodes(){
      $graphemes = $this->getGraphemes(true);
      if (@$graphemes){
        $typeVowel = Entity::getIDofTermParentLabel("vowel-graphemetype");//term dependency
        $sort = $sort2 = "0.";
        $i = 0;
        $last = $graphemes->getCount() - 1;
        foreach ($graphemes as $grapheme){
          $decomp = $grapheme->getDecomposition();
          if (!$grapheme->getSortCode()){
            $grapheme->calculateSort();
          }
          $graSort = $grapheme->getSortCode();
          if ($decomp && ($i === 0 || $i == $last) && $grapheme->getType()==$typeVowel) {// handle sandhi vowels
            $sort .= ($i?"19":"19").substr($graSort,0,2);
            $sort2 .= ($i?"5":"5").substr($graSort,2,1);
          }else{
            if ($grapheme->getType()==$typeVowel && $i == 0) {
              $sort .= "19";
              $sort2 .= "5";
            }
            $sort .= substr($graSort,0,2);
            $sort2 .= substr($graSort,2,1);
          }
          $i++;
        }
        $this->setSortCode($sort);
        $this->setSortCode2($sort2);
        return true;
      }
      return false;
    }

    //********GETTERS*********

    /**
    * Get Token's value
    *
    * @param boolean $reCalculate that indicates whether to recalculate
    * @return string value of this token
    */
    public function getValue($reCalculate = false) {
      if (!$this->_value || $reCalculate) {//calculate token value
        $this->calculateValues();
      }
      return $this->_value;
    }

    /**
    * Get Token's value
    *
    * @param boolean $reCalculate that indicates whether to recalculate
    * @return string value of this token
    */
    public function getToken($reCalculate = false) {
      return $this->getValue($reCalculate);
    }

    /**
    * Get Token's location label
    *
    * @param boolean $reCalculate that indicates whether to recalculate location label
    * @return string location label of this token
    */
    public function getLocation($autosave = true) {
      if ( !$this->getScratchProperty("locLabel")){
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
    public function getBaselinePolygons($autosave = true) {
      if (!$this->getScratchProperty("blnPolygons")){
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
    public function getScrollTopInfo($autosave = true) {
      if (!$this->getScratchProperty("blnScrollTopInfo")){
        $this->updateBaselineInfo($autosave);
      }
      return $this->getScratchProperty("blnScrollTopInfo");
    }

    /**
    * Get Token's transcription
    *
    * @param boolean $reCalculate that indicates whether to recalculate
    * @return string transcription of this token or NULL
    */
    public function getTranscription($reCalculate = false) {
      if ($reCalculate) {
        //call calculate transcription value
        $this->calculateValues();
      }
      return $this->_transcription;
    }

    /**
    * Get Token's grapheme unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string that contains grapheme IDs of this token
    */
    public function getGraphemeIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_grapheme_ids);
      }else{
        return $this->idsStringToArray($this->_grapheme_ids);
      }
    }

    /**
    * Get Token's syllablCluster unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string that contains syllablecluster IDs of the graphemes of this token
    */
    public function getSyllableClusterIDs($asString = false, $refresh = false) {
      $sclIDs = $this->getScratchProperty('sclIDs');
      if (!$sclIDs || $refresh) {
        $sclIDs = array();
        $sclGraIDs = array();
        $graIDs = $this->getGraphemeIDs();
        while ($graIDs && $graID = array_shift($graIDs)) {//for each token grapheme
          if (count($sclGraIDs)==0){//get syllable for the current grapheme
            $syls = new SyllableClusters("$graID = ANY(scl_grapheme_ids)",null,null,null);
            if (!$syls || $syls->getCount() == 0) {
                array_push($this->_errors,"invalid token state tok:".$this->_id."has grapheme gra:$graID not associated with a visible syllableCluster");
                return null;
            }
            //for each of the sylables find the one which matches the order of graphemes
            $sclGraIDs = $syls->current()->getGraphemeIDs();
            array_push($sclIDs,$syls->current()->getID());
            $graIndex = array_search($graID,$sclGraIDs);
            if ($graIndex > 0) {
              if (count($sclIDs) == 1) {//first syllable of token and token 1st grapheme not at beginning of syllable, likely split syllable so trim
                $sclGraIDs = array_slice($sclGraIDs,$graIndex);
              } else {
                array_push($this->_errors,"token graphemes are out of synch with syllableCluster id = ".$syls->current()->getID());
                return null;
              }
            }
          }
          while ($sclGraIDs && $sclGraID = array_shift($sclGraIDs)) {//match syllable graphemes to token graphemes until end of either
            if ($graID == $sclGraID) {
              if (count($sclGraIDs)==0) { // need another syllable so loop outer
                break;
              }
              $graID = array_shift($graIDs);
              if (!$graID) { //end of token so done
                break;
              }
            }else{
              //error case token and syllable are out of synch
              array_push($this->_errors,"token graphemes are out of synch with syllableCluster id = ".$syls->current()->getID());
              return null;
            }
          }
        }
        $this->storeScratchProperty('sclIDs',$sclIDs);
        $this->save();
      }
      if ($asString){
        return $this->idsToString($sclIDs);
      }else{
        return $sclIDs;
      }
    }

    /**
    * Get Token's grapheme objects
    *
    * @return Grapheme iterator for the graphemes of this token or NULL
    */
    public function getGraphemes($autoExpand = false) {
      if (!$this->_graphemes && $autoExpand && count($this->getGraphemeIDs())>0) {
        if (strpos($this->idsToString($this->_grapheme_ids),"-") !== false){ //found temp id
          $this->_graphemes = null;
        }else{
          $this->_graphemes = new Graphemes("gra_id in (".join(",",$this->getGraphemeIDs()).")",null,null,null);
          $this->_graphemes->setAutoAdvance(false);
          $this->_graphemes->setOrderMap($this->getGraphemeIDs());//ensure the iterator will server objects as in order list of ids.
        }
      }
      return $this->_graphemes;
    }

    /**
    * Gets the nominal affix for this Token
    * @return string nominal affix
    */
    public function getNominalAffix() {
      return $this->_nom_affix;
    }

    /**
    * Get Sort Code of the token
    *
    * @return string sort code of this token
    */
    public function getSortCode() {
      return $this->_sort_code;
    }

    /**
    * Get Secondary Sort Code of the token
    *
    * @return string secondary sort code of this token
    */
    public function getSortCode2() {
      return $this->_sort_code2;
    }

    //********SETTERS*********

    /**
    * Set Token's value
    *
    * @param string $token of this token
    */
    public function setToken($token) {
      if($this->_value != $token) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tok_value",$token);
      }
      $this->_value = $token;
    }

    /**
    * Set Token's transcription
    *
    * @param string transcription of this token or NULL
    */
    public function setTranscription($transcription) {
      if($this->_transcription != $transcription) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tok_transcription",$transcription);
      }
      $this->_transcription = $transcription;
    }

    /**
    * Set Token's grapheme unique IDs
    *
    * @param int array $ids that contains grapheme IDs of this token
    */
    public function setGraphemeIDs($ids) {
      if($this->_grapheme_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tok_grapheme_ids",$this->idsToString($ids));
      }
      $this->_grapheme_ids = $ids;
    }

    /**
    * Sets the nominal affix for this Token
    * @param string $affix nominal affix
    */
    public function setNominalAffix($affix) {
      if($this->_nom_affix != $affix) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tok_nom_affix",$affix);
      }
      $this->_nom_affix = $affix;
    }

    /**
    * Set primary Sort Code of the token
    *
    * @param string $sort primary code for this token
    */
    public function setSortCode($sort) {
      if(strcmp($this->_sort_code, $sort)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tok_sort_code",$sort);
      }
      $this->_sort_code = $sort;
    }

    /**
    * Set secondary Sort Code of the token
    *
    * @param string $sort secondary code for this token
    */
    public function setSortCode2($sort2) {
      if(strcmp($this->_sort_code2, $sort2)) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("tok_sort_code2",$sort2);
      }
      $this->_sort_code2 = $sort2;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
