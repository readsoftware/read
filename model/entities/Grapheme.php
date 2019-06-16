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
  * Classes to deal with Grapheme entities
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
  require_once (dirname(__FILE__) . '/Tokens.php');
  require_once (dirname(__FILE__) . '/SyllableClusters.php');
  require_once (dirname(__FILE__) . '/../utility/graphemeCharacterMap.php');//get map for valid aksara

//*******************************************************************
//****************   GRAPHEME CLASS  ********************************
//*******************************************************************
  /**
  * Grapheme represents grapheme entity which is metadata about an artefact
  *
  * <code>
  * require_once 'Grapheme.php';
  *
  * $grapheme = new Grapheme( $resultRow );
  * echo "grapheme has layer # ".$grapheme->getLayer();
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */
  class Grapheme extends Entity{

    //*******************************PRIVATE MEMBERS************************************

    /**
    * private member variables
    * @access private
    */
    private   $_grapheme,
              $_uppercase,
              $_tokens,
              $_syllable_clusters,
              $_type_id,
              $_text_critical_mark,
              $_alt,
              $_emmendation,
              $_decomposition,
              $_sort_code;

    //****************************CONSTRUCTOR FUNCTION***************************************

    /**
    * Create an grapheme instance from an grapheme table row
    * @param array $row associated with columns of the grapheme table, a valid gra_id or null
    * @access public
    * @todo  change security to use stored Proc for compare userAccessIDs with VisibilityIDs
    */
    public function __construct( $arg = null ) {
      $this->_table_name = 'grapheme';
      if (is_numeric($arg) && is_int(-1 + $arg + 1) && $arg > 0) {// this is an ID so need to query the db
        $dbMgr = new DBManager();
//        $dbMgr->query("SELECT * FROM grapheme WHERE gra_id = $arg".(isSysAdmin()?"":" AND (".getUserID()."= gra_owner_id or ".getUserID()." = ANY (\"gra_visibility_ids\"))")." LIMIT 1");
        $dbMgr->query($this->getSingleEntityAccessQuery($arg));
        $row = $dbMgr->fetchResultRow(0);
        if(!$row || !array_key_exists('gra_id',$row)) {
          error_log("unable to query for grapheme ID = $arg ");
        }else{
          $arg = $row;
        }
      }
      if(is_array($arg)) {//initialize from resultRow
        $this->initializeBaseEntity($arg);
        $this->_id=isset($arg['gra_id']) ? $arg['gra_id']:NULL;
        $this->_grapheme=(isset($arg['gra_grapheme']) || $arg['gra_grapheme'] ==="0") ? $arg['gra_grapheme']:NULL;
        $this->_uppercase=isset($arg['gra_uppercase']) ? $arg['gra_uppercase']:NULL;
        $this->_type_id=isset($arg['gra_type_id']) ? $arg['gra_type_id']:NULL;
        $this->_text_critical_mark=isset($arg['gra_text_critical_mark']) ? $arg['gra_text_critical_mark']:NULL;
        $this->_alt=isset($arg['gra_alt']) ? $arg['gra_alt']:NULL;
        $this->_emmendation=isset($arg['gra_emmendation']) ? $arg['gra_emmendation']:NULL;
        $this->_decomposition=isset($arg['gra_decomposition']) ? $arg['gra_decomposition']:NULL;
        $this->_sort_code=isset($arg['gra_sort_code']) ? $arg['gra_sort_code']:NULL;
        if (!array_key_exists('gra_id',$arg)) {// must be a new record
          //ensure everything is encoded for save
          $this->_data = $this->prepBaseEntityData($arg);
          $this->_dirty = true;
        }
      }
      //otherwise treat as new grapheme to be initialized through setters
    }

    //*******************************PROTECTED FUNCTIONS************************************

    /**
    * Get global id prefix
    *
    * @return string used to prefix an objects id to ensure global system uniqueness
    */
    protected function getGlobalPrefix(){
      return "gra";
    }

    /**
    * synch properties to data for save
    *
    */
    protected function synchData() {
      $this->synchBaseData();
      if ($this->_grapheme) {
        $this->_data['gra_grapheme'] = $this->_grapheme;
      }
      if ($this->_uppercase) {
        $this->_data['gra_uppercase'] = $this->_uppercase;
      }
      if ($this->_type_id) {
        $this->_data['gra_type_id'] = $this->_type_id;
      }
      if ($this->_text_critical_mark) {
        $this->_data['gra_text_critical_mark'] = $this->_text_critical_mark;
      }
      if ($this->_alt) {
        $this->_data['gra_alt'] = $this->_alt;
      }
      if ($this->_emmendation) {
        $this->_data['gra_emmendation'] = $this->_emmendation;
      }
      if ($this->_decomposition) {
        $this->_data['gra_decomposition'] = $this->_decomposition;
      }
      if (count($this->_sort_code)) {
        $this->_data['gra_sort_code'] = $this->_sort_code;
      }
    }

    //*******************************PUBLIC FUNCTIONS************************************
    /**
    * Lookup sort value for this character map
    *
    * @return boolean true if successful, false otherwise
    */
    public function calculateSort(){
      global $graphemeCharacterMap;
      $str = $this->_grapheme;
      $cnt = mb_strlen($str);
      $i=0; $srt="000";
      $char = mb_substr($str,$i,1);

      // convert multi-byte to grapheme - using greedy lookup
      if ($char && array_key_exists($char,$graphemeCharacterMap)){
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
      } else {
        return;
      }
      $this->setSortCode($srt);
    }
    //********GETTERS*********

    /**
    * Get Grapheme's grapheme
    *
    * @return string value pf this grapheme
    */
    public function getGrapheme() {
      return $this->_grapheme;
    }

    /**
    * Get Grapheme's value in Case form
    *
    * @return string value pf this grapheme
    */
    public function getValue() {
      return $this->_uppercase?$this->_uppercase:$this->_grapheme;
    }

     /**
    * Get Tokens object which contains all tokens attached to this grapheme
    *
    * @return Tokens iterator with all tokens linked to this grapheme
    */
    public function getTokens() {
      if (!$this->_tokens) {
        $condition = $this->_id." = ANY (\"tok_grapheme_ids\") ";
        $this->_tokens = new Tokens($condition,null,null,null);
        $this->_tokens->setAutoAdvance(false);
      }
      return $this->_tokens;
    }

     /**
    * Get SyllableClusters object which contains all syllableClusters attached to this grapheme
    *
    * @return SyllableClusters iterator with all syllableClusters linked to this grapheme
    */
    public function getSyllableClusters() {
      if (!$this->_syllable_clusters) {
        $condition = $this->_id." = ANY (\"scl_grapheme_ids\") ";
        $this->_syllable_clusters = new SyllableClusters($condition,null,null,null);
        $this->_syllable_clusters->setAutoAdvance(false);
      }
      return $this->_syllable_clusters;
    }

    /**
    * Get Type of the grapheme
    *
    * @return string from a typology of terms for types of graphemes
    */
    public function getType() {
      return $this->_type_id;
    }

    /**
    * Gets the text critical marks for this Grapheme
    * @return string text critical marks
    */
    public function getTextCriticalMark() {
      return $this->_text_critical_mark;
    }

    /**
    * Get Alternative for grapheme
    *
    * @return string from a typology of terms for graphemes
    */
    public function getAlternative() {
      return $this->_alt;
    }

    /**
    * Get Emmendation for grapheme
    *
    * @return string Emmendation
    */
    public function getEmmendation() {
      return $this->_emmendation;
    }

    /**
    * Get Decomposition for grapheme
    *
    * @return string Decomposition into vowels for last:first on token split
    */
    public function getDecomposition() {
      return $this->_decomposition;
    }

    /**
    * Get Sort Code of the grapheme
    *
    * @return string sort code of this grapheme
    */
    public function getSortCode() {
      return $this->_sort_code;
    }

    /**
    * Get Entity's Attributions unique IDs
    *
    * @param boolean $asString determines where to return as a string (default = false)
    * @return int array|string for attribuation object IDs for this Entity
    */
    public function getAttributionIDs($asString = false) {
      if ($asString) {
        return '';
      } else {
        return null;
      }
    }

    //********SETTERS*********

    /**
    * Set Grapheme's grapheme
    *
    * @param string $grapheme
    */
    public function setGrapheme($grapheme) {
      if($this->_grapheme != $grapheme) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_grapheme",$grapheme);
      }
      $this->_grapheme = $grapheme;
    }

    /**
    * Set Grapheme's uppercase form for grapheme
    *
    * @param string $ucgrapheme
    */
    public function setUppercase($ucgrapheme) {
      if($this->_uppercase != $ucgrapheme) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_uppercase",$ucgrapheme);
      }
      $this->_uppercase = $ucgrapheme;
    }

    /**
    * Set Type of the grapheme
    *
    * @param string $type from a typology of terms for types of graphemes
    * @todo add code to validate against enum
    */
    public function setType($type) {
      if($this->_type_id != $type) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_type_id",$type);
      }
      $this->_type_id = $type;
    }

    /**
    * Sets the text critical marks for this Grapheme
    * @param string $mark text critical marks
    * @todo add code to validate against enum
    */
    public function setTextCriticalMark($mark) {
      if($this->_text_critical_mark != $mark) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_text_critical_mark",$mark);
      }
      $this->_text_critical_mark = $mark;
    }

    /**
    * Set Alternative for the grapheme
    *
    * @param string $alt from a typology of terms for alternative types for graphemes
    */
    public function setAlternative($alt) {
      if($this->_alt != $alt) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_alt",$alt);
      }
      $this->_alt = $alt;
    }

    /**
    * Set Emmendation for grapheme
    *
    * @param string $emmend Emmendation
    */
    public function setEmmendation($emmend) {
      if($this->_emmendation != $emmend) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_emmendation",$emmend);
      }
      $this->_emmendation = $emmend;
    }

    /**
    * Set Decomposition for grapheme
    *
    * @param string $decomp in form of vowels for last:first on token split
    */
    public function setDecomposition($decomp) {
      if($this->_decomposition != $decomp) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_decomposition",$decomp);
      }
      $this->_decomposition = $decomp;
    }

    /**
    * Set Sort Code of the grapheme
    *
    * @param string $sc sort code of this grapheme
    */
    public function setSortCode($sc) {
      if($this->_sort_code != $sc) {
        $this->_dirty = true;
        $this->setDataKeyValuePair("gra_sort_code",$sc);
      }
      $this->_sort_code = $sc;
    }

    /**
    * Set Entity's Attributions unique IDs
    *
    * @param int array $attributionIDs of attribution object IDs for this Entity
    */
    public function setAttributionIDs($attributionIDs) {
      return;
    }

    //*******************************PRIVATE FUNCTIONS************************************

  }
?>
