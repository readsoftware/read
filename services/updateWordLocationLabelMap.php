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
  * loadCatalogEntities
  *
  *  A service that returns a json structure of the catalog (unrestricted type) requested along with its
  *  lemma and the lemma's inflections, compounds, and tokens.
  *  There is NO CACHING of this information.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header("Content-type: text/plain");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Lemmas.php';
  require_once dirname(__FILE__) . '/../model/entities/Inflections.php';
  require_once dirname(__FILE__) . '/../model/entities/Edition.php';
  require_once dirname(__FILE__) . '/../model/entities/Text.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;

  $dbMgr = new DBManager();
  $retVal = array();
  $catID = (array_key_exists('catID',$_REQUEST)? $_REQUEST['catID']:null);
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $cmpTokTag2LocLabel = array();
  $errors = array();
  $warnings = array();
  //find catalogs
  if ($catID) {
    $catalog = new Catalog($catID);
  }else{
    $catalog = null;
  }
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    echo "Usage: updateWordLocationLabelMap.php?db=yourDBName&catID=###";
  } else {
    $cmpTokTag2LocLabel = getWordTagToLocationLabelMap($catalog,true);
    echo print_r($cmpTokTag2LocLabel,true);
  }

  function getWordTagToLocationLabelMap($catalog, $refreshWordMap) {
    global $term_parentLabelToID;
    $catID = $catalog->getID();
    if (!$refreshWordMap && array_key_exists("cache-cat$catID".DBNAME,$_SESSION) &&
          array_key_exists('wrdTag2LocLabel',$_SESSION["cache-cat$catID".DBNAME])) {
      return $_SESSION["cache-cat$catID".DBNAME]['wrdTag2LocLabel'];
    }
    $editionIDs = $catalog->getEditionIDs();
    $wrdTag2LocLabel = array();
    $sclTagToLabel = array();
    $ednLblBySeqTag = array();
    $seqTag2EdnTag = array();
    foreach ($editionIDs as $ednID) {
      $txtSeqGIDs = array();
      $physSeqGIDs = array();
      $ednLabel = '';
      $ednTag = 'edn'.$ednID;
      $edition = new Edition($ednID);
      if (!$edition->hasError()) {
        $text = $edition->getText(true);
        if ($text && !$text->hasError()) {
          $ednLabel = $text->getRef();
          if (!$ednLabel) {
            $ednLabel='t'.$text->getID();
          }
        }
        echo "processing edition $ednID labeled $ednLabel\n";
        ob_flush();
        flush();
        $ednSequences = $edition->getSequences(true);
        //for edition find token sequences and find physical sequences and create sclID to label
        foreach ($ednSequences as $ednSequence) {
          if ($ednSequence->getTypeID() == $term_parentLabelToID['text-sequencetype']) {//term dependency
            $txtSeqGIDs = array_merge($txtSeqGIDs,$ednSequence->getEntityIDs());
          }
          if ($ednSequence->getTypeID() == $term_parentLabelToID['textphysical-sequencetype']) {//term dependency
            $physSeqGIDs = array_merge($physSeqGIDs,$ednSequence->getEntityIDs());
          }
        }
      } else {
        error_log("error loading edition $ednID : ".$edition->getErrors(true));
        continue;
      }
      if ($txtSeqGIDs && count($txtSeqGIDs)) {// capture each text token sequence once
        foreach ($txtSeqGIDs as $txtSeqGID) {
          $tag = preg_replace("/:/","",$txtSeqGID);
          if (array_key_exists($tag,$ednLblBySeqTag)) {// use label from first edition found with this sequence tag, should never happened if single edition per text
            continue;
          }
          $ednLblBySeqTag[$tag] = $ednLabel;//todo: this overwrites the edition, ?? do we need to associate a line sequence with a primary edition for the reuse case??
          $seqTag2EdnTag[$tag] = $ednTag;
        }
      }
      if ($physSeqGIDs && count($physSeqGIDs)) {// capture each physical line sequence once
        foreach ($physSeqGIDs as $physSeqGID) {
          $sequence = new Sequence(substr($physSeqGID,4));
          $label = $sequence->getSuperScript();
          if (!$label) {
            $label = $sequence->getLabel();
          }
          if (!$label) {
            $label = 'seq'.$sequence->getID();
          }
          $sclGIDs = $sequence->getEntityIDs();
          if ($label && count($sclGIDs)) {//create lookup for location of word span B11-B12
            foreach ($sclGIDs as $sclGID) {
              $tag = preg_replace("/:/","",$sclGID);
              $sclTagToLabel[$tag] = $label;
            }
          }
        }
      }
      if ($ednLblBySeqTag && count($ednLblBySeqTag) > 0) {
        //for each token sequence
        foreach ($ednLblBySeqTag as $ednSeqTag => $ednLabel) {
          if ($ednLabel) {
            $ednLabel .= ":";
          }
          $ednTag = $seqTag2EdnTag[$ednSeqTag];
          $sequence = new Sequence(substr($ednSeqTag,3));
          $defLabel = $ednLabel . ($sequence->getSuperScript()?$sequence->getSuperScript():($sequence->getLabel()?$sequence->getLabel():$ednSeqTag));
          $words = $sequence->getEntities(true);
          if ($words->getCount() == 0) {
            error_log("no words for sequence $ednSeqTag having edition label $ednLabel");
            continue;
          }else{
            //error_log("words for sequence $ednSeqTag having edition label $ednLabel include ".join(',',$words->getKeys()));
          }
          //calculate a location label for each word and add to $wrdTag2LocLabel
          foreach ($words as $word) {
            $fSclID = $lSclID = null;
            $prefix = $word->getEntityTypeCode();
            $id = $word->getID();
            $wtag = $prefix.$id;
            if ($word->getSortCode() >= 0.7) {
               continue;
            }
            // find first and last SclID for word to calc attested form location
            if ($prefix == 'cmp') {
              $tokenSet = $word->getTokens();
              $tokens = $tokenSet->getEntities();
              $fToken = $tokens[0];
              $sclIDs = $fToken->getSyllableClusterIDs();
              $fSclID = $sclIDs[0];
              $sclTag = 'scl'.$fSclID;
              if ( array_key_exists($sclTag,$sclTagToLabel)) {
                $label = $sclTagToLabel[$sclTag];
              } else {
                $tokID = $fToken->getID();
                error_log("no start label founds for $sclTag of tok$tokID from $prefix$id for sequence $ednSeqTag having label $ednLabel");
                $label = null;
              }
              if ($label) {
                $lToken = $tokens[count($tokens)-1];
                $sclIDs = $lToken->getSyllableClusterIDs();
                $lSclID = $sclIDs[count($sclIDs)-1];
                $sclTag = 'scl'.$lSclID;
                if ( array_key_exists($sclTag,$sclTagToLabel)) {
                  $label2 = $sclTagToLabel[$sclTag];
                } else {
                  $tokID = $lToken->getID();
                  error_log("no end label founds for $sclTag of tok$tokID from $prefix$id for sequence $ednSeqTag having label $ednLabel");
                  $label2 = null;
                }
                if($label2 && $label2 != $label) {
                  $label .= "-" . $label2;
                }
                $wrdTag2LocLabel[$wtag] = $ednLabel . $label;
              } else {
                $wrdTag2LocLabel[$wtag] = $defLabel;
              }
            } else if ($prefix == 'tok') {
              $sclIDs = $word->getSyllableClusterIDs();
              $fSclID = $sclIDs[0];
              $sclTag = 'scl'.$fSclID;
              if ( array_key_exists($sclTag,$sclTagToLabel)) {
                $label = $sclTagToLabel[$sclTag];
              } else {
                error_log("no start label founds for $sclTag processing $prefix$id for sequence $ednSeqTag having label $ednLabel");
                $label = null;
              }
              if ($label) {
                $lSclID = $sclIDs[count($sclIDs)-1];
                $sclTag = 'scl'.$lSclID;
                if ( array_key_exists($sclTag,$sclTagToLabel)) {
                  $label2 = $sclTagToLabel[$sclTag];
                } else {
                  error_log("no end label founds for $sclTag processing $prefix$id for sequence $ednSeqTag having label $ednLabel");
                  $label2 = null;
                }
                if($label2 && $label2 != $label) {
                  $label .= "-" . $label2;
                }
                $wrdTag2LocLabel[$wtag] = $ednLabel . $label;
              } else {
                $wrdTag2LocLabel[$wtag] = $defLabel;
              }
            }
          }
        }
      }
    }
    $_SESSION["cache-cat$catID"]['wrdTag2LocLabel'] = $wrdTag2LocLabel;
    return $wrdTag2LocLabel;
  }
?>
