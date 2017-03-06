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
  * Classes to deal with Inflection entities
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
  require_once (dirname(__FILE__) . '/Lemma.php');
  require_once (dirname(__FILE__) . '/Tokens.php');
  require_once (dirname(__FILE__) . '/Compounds.php');

  //*******************************************************************
  //****************   TOKEN CLASS  *************************
  //*******************************************************************
  /**
  * Inflection represents inflection entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Inflection.php';
  *
  * $inflection = new Inflection( $resultRow );
  * echo "inflection has layer # ".$inflection->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Inflection extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private  $_chaya,
    $_component_ids = array(),
    $_components,
    $_case_id,
    $_certainty,
    $_nominal_gender_id,
    $_gram_number_id,
    $_verb_person_id,
    $_verb_voice_id,
    $_verb_tense_id,
    $_verb_mood_id,
    $_verb_second_conj_id;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an inflection instance from an inflection table row
    * @param array $row associated with columns of the inflection table, a valid inf_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'inflection';
      if (is_numeric($arg)&& is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM inflection WHERE inf_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= inf_owner_id or ".getUserID()." = ANY (\"inf_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow();
        if(!$row || !array_key_exists('inf_id',$row)) {
          error_log("unable to query for inflection ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['inf_id'] ? $arg['inf_id']:NULL;
        $this->_chaya=@$arg['inf_chaya'] ? $arg['inf_chaya']:NULL;
        $this->_component_ids=@$arg['inf_component_ids'] ? $arg['inf_component_ids']:NULL;
        $this->_case_id=@$arg['inf_case_id'] ? $arg['inf_case_id']:NULL;
        $this->_certainty=@$arg['inf_certainty'] ? $arg['inf_certainty']:NULL;
        $this->_nominal_gender_id=@$arg['inf_nominal_gender_id'] ? $arg['inf_nominal_gender_id']:NULL;
        $this->_gram_number_id=@$arg['inf_gram_number_id'] ? $arg['inf_gram_number_id']:NULL;
        $this->_verb_person_id=@$arg['inf_verb_person_id'] ? $arg['inf_verb_person_id']:NULL;
        $this->_verb_voice_id=@$arg['inf_verb_voice_id'] ? $arg['inf_verb_voice_id']:NULL;
        $this->_verb_tense_id=@$arg['inf_verb_tense_id'] ? $arg['inf_verb_tense_id']:NULL;
        $this->_verb_mood_id=@$arg['inf_verb_mood_id'] ? $arg['inf_verb_mood_id']:NULL;
        $this->_verb_second_conj_id=@$arg['inf_verb_second_conj_id'] ? $arg['inf_verb_second_conj_id']:NULL;
        if (!array_key_exists('inf_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('inf_component_ids',$arg))$arg['inf_component_ids'] = $this->arraysOfIdsToString($arg['inf_component_ids']);
          if (array_key_exists('inf_certainty',$arg))$arg['inf_certainty'] = $this->idsToString($arg['inf_certainty']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new inflection to be initialized through setters
    }
    //****************************STATIC MEMBERS***************************************

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "inf";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->calculateValues();
      $this->synchBaseData();
      if (count($this->_compound_ids)) {
        $this->_data['inf_component_ids'] = $this->arraysOfIdsToString($this->_component_ids);
      }
      if ($this->_chaya) {
        $this->_data['inf_chaya'] = $this->_chaya;
      }
      if ($this->_nom_affix) {
        $this->_data['inf_nom_affix'] = $this->_nom_affix;
      }
      if ($this->_case_id) {
        $this->_data['inf_case_id'] = $this->_case_id;
      }
      if ($this->_certainty) {
        $this->_data['inf_certainty'] = $this->_certainty;
      }
      if ($this->_nominal_gender_id) {
        $this->_data['inf_nominal_gender_id'] = $this->_nominal_gender_id;
      }
      if ($this->_gram_number_id) {
        $this->_data['inf_gram_number_id'] = $this->_gram_number_id;
      }
      if ($this->_verb_person_id) {
        $this->_data['inf_verb_person_id'] = $this->_verb_person_id;
      }
      if ($this->_verb_voice_id) {
        $this->_data['inf_verb_voice_id'] = $this->_verb_voice_id;
      }
      if ($this->_verb_tense_id) {
        $this->_data['inf_verb_tense_id'] = $this->_verb_tense_id;
      }
      if ($this->_verb_mood_id) {
        $this->_data['inf_verb_mood_id'] = $this->_verb_mood_id;
      }
      if ($this->_verb_second_conj_id) {
        $this->_data['inf_verb_second_conj_id'] = $this->_verb_second_conj_id;
      }
      if (count($this->_sort_code)) {
        $this->_data['inf_sort_code'] = $this->_sort_code;
      }
      if (count($this->_sort_code2)) {
        $this->_data['inf_sort_code2'] = $this->_sort_code2;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    //********GETTERS*********

    /**
    * Get Inflection's Chaya
    *
    * @return string Chaya of this inflection or NULL
    */
    public function getChaya() {
      return $this->_chaya;
    }


    /**
    * Get Inflection's Lemma
    *
    * @return Lemma returns the lemma object for this Inflection
    */
    public function getLemma() {
      return null;
    }

    /**
    * Get Inflection's component unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return string array|string that contains a list of component typPrefix:IDs of this inflection
    */
    public function getComponentIDs($asString = false) {
      if ($asString){
        return $this->stringsToString($this->_component_ids);
      }else{
        return $this->stringOfStringsToArray($this->_component_ids);
      }
    }

    /**
    * Get Inflection's component objects
    *
    * @return Inflection iterator for the components of this Inflection or NULL
    */
    public function getComponents($autoExpand = false) {
      if (!$this->_components && $autoExpand && count($this->getComponentIDs())>0) {
        $this->_components = new OrderedSet();
        $this->_components->loadEntities($this->getComponentIDs());
      }
      return $this->_components;
    }

    /**
    * Get Case of the inflection
    *
    * @return string from a typology of terms for Case for inflections
    */
    public function getCase() {
      return $this->_case_id;
    }

    /**
    * Get Inflection's certainty values
    *
    * @param boolean $asString determines when to return as a string (default = false)
    * @return int array|string of certainty values of the decomposition for this Inflection
    */
    public function getCertainty($asString = false) {
      if ($asString){
        return $this->idsToString($this->_certainty);
      }else{
        return $this->idsStringToArray($this->_certainty);
      }
    }

    /**
    * Get Nominal Gender of the inflection
    *
    * @return string from a typology of terms for Nominal Gender for inflections
    */
    public function getGender() {
      return $this->_nominal_gender_id;
    }

    /**
    * Get Gramatical Number of the inflection
    *
    * @return string from a typology of terms for Gramatical Number for inflections
    */
    public function getGramaticalNumber() {
      return $this->_gram_number_id;
    }

    /**
    * Get Verbal Person of the inflection
    *
    * @return string from a typology of terms for Verbal Person for inflections
    */
    public function getVerbalPerson() {
      return $this->_verb_person_id;
    }

    /**
    * Get Verbal Voice of the inflection
    *
    * @return string from a typology of terms for Verbal Voice for inflections
    */
    public function getVerbalVoice() {
      return $this->_verb_voice_id;
    }

    /**
    * Get Verbal Tense of the inflection
    *
    * @return string from a typology of terms for Verbal Tense for inflections
    */
    public function getVerbalTense() {
      return $this->_verb_tense_id;
    }

    /**
    * Get Verbal Mood of the inflection
    *
    * @return string from a typology of terms for Verbal Mood for inflections
    */
    public function getVerbalMood() {
      return $this->_verb_mood_id;
    }

    /**
    * Get Verbal Second Conjugation of the inflection
    *
    * @return string from a typology of terms for Verbal Second Conjugation for inflections
    */
    public function getSecondConjugation() {
      return $this->_verb_second_conj_id;
    }

    //********SETTERS*********

    /**
    * Set Inflection's Chaya
    *
    * @param string $chaya of this inflection
    */
    public function setChaya($chaya) {
      if($this->_chaya != $chaya) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_chaya",$chaya);
      }
      $this->_chaya = $chaya;
    }

    /**
    * Set Inflection's component unique IDs
    *
    * @param string array that contains component IDs of this Inflection
    */
    public function setComponentIDs($ids) {
      if($this->_component_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_component_ids",$this->stringsToString($ids));
      }
      $this->_component_ids = $ids;
    }

    /**
    * Set Case of the inflection
    *
    * @param string $case from a typology of terms for Case for inflections
    */
    public function setCase($case) {
      if($this->_case_id != $case) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_case_id",$case);
      }
      $this->_case_id = $case;
    }

    /**
    * Set Inflection's Certainty Values
    *
    * @param int array $certainties of certainty values for this Inflection
    */
    public function setCertainty($certainties) {
      if($this->_certainty != $certainties) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_certainty",$this->idsToString($certainties));
      }
      $this->_certainty = $certainties;
    }

    /**
    * Set Nominal Gender of the inflection
    *
    * @param string $gend from a typology of terms for Nominal Gender for inflections
    */
    public function setGender($gend) {
      if($this->_nominal_gender_id != $gend) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_nominal_gender_id",$gend);
      }
      $this->_nominal_gender_id = $gend;
    }

    /**
    * Set Gramatical Number of the inflection
    *
    * @param string $gramNum from a typology of terms for Gramatical Number for inflections
    */
    public function setGramaticalNumber($gramNum) {
      if($this->_gram_number_id != $gramNum) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_gram_number_id",$gramNum);
      }
      $this->_gram_number_id = $gramNum;
    }

    /**
    * Set Verbal Person of the inflection
    *
    * @param string $verbPer from a typology of terms for Verbal Person for inflections
    */
    public function setVerbalPerson($verbPer) {
      if($this->_verb_person_id != $verbPer) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_verb_person_id",$verbPer);
      }
      $this->_verb_person_id = $verbPer;
    }

    /**
    * Set Verbal Voice of the inflection
    *
    * @param string $voice from a typology of terms for Verbal Voice for inflections
    */
    public function setVerbalVoice($voice) {
      if($this->_verb_voice_id != $voice) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_verb_voice_id",$voice);
      }
      $this->_verb_voice_id = $voice;
    }

    /**
    * Set Verbal Tense of the inflection
    *
    * @param string $tense from a typology of terms for Verbal Tense for inflections
    */
    public function setVerbalTense($tense) {
      if($this->_verb_tense_id != $tense) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_verb_tense_id",$tense);
      }
      $this->_verb_tense_id = $tense;
    }

    /**
    * Set Verbal Mood of the inflection
    *
    * @param string $mood from a typology of terms for Verbal Mood for inflections
    */
    public function setVerbalMood($mood) {
      if($this->_verb_mood_id != $mood) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_verb_mood_id",$mood);
      }
      $this->_verb_mood_id = $mood;
    }

    /**
    * Set Verbal Second Conjugation of the inflection
    *
    * @param string $secConj from a typology of terms for Verbal Second Conjugation for inflections
    */
    public function setSecondConjugation($secConj) {
      if($this->_verb_second_conj_id != $secConj) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("inf_verb_second_conj_id",$secConj);
      }
      $this->_verb_second_conj_id = $secConj;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
