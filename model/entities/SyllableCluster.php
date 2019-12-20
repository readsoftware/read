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
  * Classes to deal with SyllableCluster entities
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
  require_once (dirname(__FILE__) . '/Segment.php');
  require_once (dirname(__FILE__) . '/Graphemes.php');

//*******************************************************************
//****************   SYLLABLECLUSTER CLASS  *************************
//*******************************************************************
  /**
  * SyllableCluster represents syllablecluster entity which is metadata about an artefact
  *
  * <code>
  * require_once 'SyllableCluster.php';
  *
  * $syllablecluster = new SyllableCluster( $resultRow );
  * echo "syllablecluster has layer # ".$syllablecluster->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class SyllableCluster extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_value,
              $_segment_id,
              $_segment,
              $_grapheme_ids = array(),
              $_graphemes_unsynched = true,
              $_graphemes,
              $_text_critical_mark,
              $_sort_code,
              $_sort_code2;


    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an syllablecluster instance from an syllablecluster table row
    * @param array $row associated with columns of the syllablecluster table, a valid scl_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'syllablecluster';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM syllablecluster WHERE scl_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= scl_owner_id or ".getUserID()." = ANY (\"scl_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('scl_id',$row)) {
          error_log("unable to query for syllablecluster ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=@$arg['scl_id'] ? $arg['scl_id']:NULL;
        $this->_segment_id=@$arg['scl_segment_id'] ? $arg['scl_segment_id']:NULL;
        $this->_grapheme_ids=@$arg['scl_grapheme_ids'] ? $arg['scl_grapheme_ids']:NULL;
        $this->_text_critical_mark=@$arg['scl_text_critical_mark'] ? $arg['scl_text_critical_mark']:NULL;
        $this->_sort_code=@$arg['scl_sort_code'] ? $arg['scl_sort_code']:NULL;
        $this->_sort_code2=@$arg['scl_sort_code2'] ? $arg['scl_sort_code2']:NULL;
        if (!array_key_exists('scl_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          if (array_key_exists('scl_grapheme_ids',$arg))$arg['scl_grapheme_ids'] = $this->idsToString($arg['scl_grapheme_ids']);
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new syllablecluster to be initialized through setters
    }

    //****************************STATIC MEMBERS***************************************

    public static function compareSyllables($sclA,$sclB) {
      if ($sclA->getSortCode() < $sclB->getSortCode()) {
        return -1;
      } else if ($sclA->getSortCode() > $sclB->getSortCode()) {
        return 1;
      }
      //todo add code here Phase 2 of model upgrade. sort 2 comparison
      return 0;
    }

    //***************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "scl";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_segment_id) {
        $this->_data['scl_segment_id'] = $this->_segment_id;
      }
      if (count($this->_grapheme_ids)) {
        $this->_data['scl_grapheme_ids'] = $this->idsToString($this->_grapheme_ids);
      }
      if ($this->_text_critical_mark) {
        $this->_data['scl_text_critical_mark'] = $this->_text_critical_mark;
      }
      if (count($this->_sort_code)) {
        $this->_data['scl_sort_code'] = $this->_sort_code;
      }
      if (count($this->_sort_code2)) {
        $this->_data['scl_sort_code2'] = $this->_sort_code2;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************

    /**
    * Calculate sort codes for this syllable cluster
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateSortCodes(){
      $graphemes = $this->getGraphemes(true);
      if (@$graphemes){
        $typeVowel = Entity::getIDofTermParentLabel("vowel-graphemetype");//term dependency
        $sort = $sort2 = "0.";
        $last = $graphemes->getCount() - 1;
        foreach ($graphemes as $grapheme){
          if (!$grapheme->getSortCode()){
            $grapheme->calculateSort();
          }
          $graSort = $grapheme->getSortCode();
          $sort .= substr($graSort,0,2);
          $sort2 .= substr($graSort,2,1);
        }
        $this->setSortCode($sort);
        $this->setSortCode2($sort2);
        return true;
      }
      return false;
    }


    //********GETTERS*********

    /**
    * Get Syllable's value
    *
    * @return string value of this syllable
    */
    public function getValue($raw = false) {
      if (!$this->_value || $this->_graphemes_unsynched) {//calculate syllable value
        $this->_value = "";
        $this->_sort_code = "";
        $graphemes = $this->getGraphemes(true);
        if (@$graphemes){
          $sort = $sort2 = "0.";
          $value = '';
          foreach ($graphemes as $grapheme){
            if (!$grapheme->getSortCode()){
              $grapheme->calculateSort();
            }
            $graSort = $grapheme->getSortCode();
            $sort .= substr($graSort,0,2);
            $sort2 .= substr($graSort,2,1);
            if (!$raw) {
              $value .= $grapheme->getValue();
            } else {
              $value .= $grapheme->getGrapheme();
            }
          }
          $this->setSortCode($sort);
          $this->setSortCode2($sort2);
          $this->_value = $value;
        }
      }
      return $this->_value;
    }

    /**
    * Get SyllableCluster's segment unique ID
    *
    * @return int segment ID of this syllablecluster
    */
    public function getSegmentID() {
      return $this->_segment_id;
    }

    /**
    * Get SyllableCluster's segment object
    *
    * @return segment of this syllablecluster or NULL
    */
    public function getSegment($autoExpand = false) {
      if (!$this->_segment && $autoExpand && is_numeric($this->_segment_id)) {
        $this->_segment = new Segment(intval($this->_segment_id));
      }
      return $this->_segment;
    }

    /**
    * Get SyllableCluster's grapheme unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string that contains grapheme IDs of this syllablecluster
    */
    public function getGraphemeIDs($asString = false) {
      if ($asString){
        return $this->idsToString($this->_grapheme_ids);
      }else{
        return $this->idsStringToArray($this->_grapheme_ids);
      }
    }

    /**
    * Get SyllableCluster's grapheme objects
    *
    * @return grapheme iterator for the graphemes of this syllablecluster or NULL
    */
    public function getGraphemes($autoExpand = false) {
      if ((!$this->_graphemes || $this->_graphemes_unsynched) && $autoExpand && count($this->getGraphemeIDs())>0) {
        if (strpos($this->idsToString($this->_grapheme_ids),"-") !== false){ //found temp id
          $this->_graphemes = null;
        }else{
          $this->_graphemes = new Graphemes("gra_id in (".join(",",$this->getGraphemeIDs()).")",null,null,null);
          $this->_graphemes->setAutoAdvance(false);
          $this->_graphemes->setOrderMap($this->getGraphemeIDs());//ensure the iterator will server objects as in order list of ids.
          $this->_graphemes_unsynched = false;
        }
      }
      return $this->_graphemes;
    }

    /**
    * Get SyllableCluster's physical line sequence objects
    *
    * @return sequence iterator for the physical line sequence containers of this syllablecluster or NULL
    */
    public function getPhysLineSequences() {
      $physLineSequences = null;
      if ($this->_id) {
        $seqTypeID = $this->getIDofTermParentLabel("linephysical-textphysical"); //warning!!! term dependency
        if ($seqTypeID) {
          $condition = "'scl:".$this->_id."' = ANY(seq_entity_ids) and ".
                      "seq_owner_id != 1 and ".
                      "seq_type_id = $seqTypeID";
          $physLineSequences = new Sequences($condition,null,null,null);
          if (!$physLineSequences->getError() && $physLineSequences->getCount() > 0) {
            $physLineSequences->setAutoAdvance(false);
          } else {
            $physLineSequences = null;
          }
        }
      }
      return $physLineSequences;
    }

    /**
    * Gets the text critical marks for this SyllableCluster
    * @return string text critical marks
    */
    public function getTextCriticalMark() {
      return $this->_text_critical_mark;
    }

    /**
    * Get Sort Code of the syllablecluster
    *
    * @return string sort code of this syllablecluster
    */
    public function getSortCode() {
      if (!$this->_sort_code && count($this->getGraphemeIDs())) {
        $this->calculateSortCodes();
      }
      return $this->_sort_code;
    }

    /**
    * Get Sort Code of the syllablecluster
    *
    * @return string sort code of this syllablecluster
    */
    public function getSortCode2() {
      if (!$this->_sort_code2 && count($this->getGraphemeIDs())) {
        $this->calculateSortCodes();
      }
      return $this->_sort_code2;
    }

    //********SETTERS*********

    /**
    * Set SyllableCluster's segment unique ID
    *
    * @param int $id segment ID of this syllablecluster
    */
    public function setSegmentID($id) {
      if($this->_segment_id != $id) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("scl_segment_id",$id);
      }
      $this->_segment_id = $id;
    }

    /**
    * Set SyllableCluster's grapheme unique IDs
    *
    * @param int array $ids that contains grapheme IDs of this syllablecluster
    */
    public function setGraphemeIDs($ids) {
      if ((is_string($this->_grapheme_ids) && $this->_grapheme_ids != $this->idsToString($ids))
            || $this->_grapheme_ids != $ids) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("scl_grapheme_ids",$this->idsToString($ids));
        $this->_value = $this->_graphemes = null;
        $this->_graphemes_unsynched = true;
      }
      $this->_grapheme_ids = $ids;
    }

    /**
    * Sets the text critical marks for this SyllableCluster
    * @param string $mark text critical marks
    */
    public function setTextCriticalMark($mark) {
      if($this->_text_critical_mark != $mark) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("scl_text_critical_mark",$mark);
      }
      $this->_text_critical_mark = $mark;
    }

    /**
    * Set Sort Code of the syllablecluster
    *
    * @param string $sc sort code of this syllablecluster
    */
    public function setSortCode($sc) {
      if($this->_sort_code != $sc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("scl_sort_code",$sc);
      }
      $this->_sort_code = $sc;
    }

    /**
    * Set Sort Code of the syllablecluster
    *
    * @param string $sc sort code of this syllablecluster
    */
    public function setSortCode2($sc) {
      if($this->_sort_code != $sc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("scl_sort_code2",$sc);
      }
      $this->_sort_code2 = $sc;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
