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
  * getEntityData
  *
  *  A service that returns a json structure of the texts requested along with their fragemnts, surfaces,
  *  baselines and segments need to drive the segment editor.
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

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  if (@$argv) {
    // handle command-line queries
    $cmdParams = array();
    $cmdStr = "cmdline params:";
    for ($i=0; $i < count($argv); ++$i) {
      if ($argv[$i][0] === '-') {
        if (@$argv[$i+1] && $argv[$i+1][0] != '-') {
        $cmdParams[$argv[$i]] = $argv[$i+1];
        ++$i;
        }else{ // map non-value dash arguments into state variables
          $cmdParams[$argv[$i]] = true;
        }
      } else {//capture others args
        array_push($cmdParams, $argv[$i]);
      }
    }
    if(@$cmdParams['-d']) {
      $_REQUEST["db"] = $cmdParams['-d'];
      $dbn = $_REQUEST["db"];
      $cmdStr .= ' db = '.$_REQUEST['db'];
    }
    if (@$cmdParams['-ckn']) {
      $_REQUEST['ckn'] = $cmdParams['-ckn'];
      $cmdStr .= ' ckn = '.$_REQUEST['ckn'];
    }
    if (@$cmdParams['-ids']) {
      $_REQUEST['ids'] = $cmdParams['-ids'];
      $cmdStr .= ' ids = '.$_REQUEST['ids'];
    }
    //commandline access for setting userid  TODO review after migration
    if (@$cmdParams['-u'] ) {
      if ($dbn) {
        if (!isset($_SESSION['readSessions'])) {
          $_SESSION['readSessions'] = array();
        }
        if (!isset($_SESSION['readSessions'][$dbn])) {
          $_SESSION['readSessions'][$dbn] = array();
        }
        $_SESSION['readSessions'][$dbn]['ka_userid'] = $cmdParams['-u'];
        $cmdStr .= ' uID = '.$_SESSION['readSessions'][$dbn]['ka_userid'];
      }
    }
    echo $cmdStr."\n";
  }
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once dirname(__FILE__) . '/../model/entities/Texts.php';
  require_once dirname(__FILE__) . '/../model/entities/Surfaces.php';
  require_once dirname(__FILE__) . '/../model/entities/Baselines.php';
  require_once dirname(__FILE__) . '/../model/entities/Segments.php';
  require_once dirname(__FILE__) . '/../model/entities/Graphemes.php';
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Lemmas.php';
  require_once dirname(__FILE__) . '/../model/entities/Inflections.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Spans.php';
  require_once dirname(__FILE__) . '/../model/entities/Lines.php';
  require_once dirname(__FILE__) . '/../model/entities/SyllableClusters.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
  require_once dirname(__FILE__) . '/../model/entities/Items.php';
  require_once dirname(__FILE__) . '/../model/entities/Parts.php';
  require_once dirname(__FILE__) . '/../model/entities/Fragments.php';
  require_once dirname(__FILE__) . '/../model/entities/Annotations.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';


  $dbMgr = new DBManager();
  $retVal = array();
  $txtCache = array();
  $errors = array();
  $warnings = array();
  $gra2SclMap = array();
  $segID2sclIDs = array();
  $processedTokIDs = array();
  $cknLookup = array();
  $entityIDs = array();
  $entities = array( 'txt' => array(),
                     'tmd' => array(),
                     'edn' => array(),
                     'seq' => array(),
                     'cmp' => array(),
                     'cat' => array(),
                     'lem' => array(),
                     'inf' => array(),
                     'tok' => array(),
                     'scl' => array(),
                     'gra' => array(),
                     'seg' => array(),
                     'spn' => array(),
                     'lin' => array(),
                     'bln' => array(),
                     'img' => array(),
                     'srf' => array(),
                     'frg' => array(),
                     'prt' => array(),
                     'itm' => array(),
                     'img' => array(),
                     'ano' => array(),
                     'atb' => array());
  $textCKNs = (array_key_exists('ckn',$_REQUEST)? $_REQUEST['ckn']:null);
  $tagIDs = (array_key_exists('ids',$_REQUEST)? $_REQUEST['ids']:null);
  if (is_array($textCKNs)) {// multiple text
    $condition = "txt_ckn in ('".join("','",$textCKNs)."') ";
  }else if (is_string($textCKNs)) {
    $condition = "";
    if (strpos($textCKNs,",")) {
      $condition = "txt_ckn in ('".str_replace(",","','", $textCKNs)."') ";
    }else if (strtoupper(substr($textCKNs,0,2)) =="CK") {
      $condition = "txt_ckn = '".$textCKNs."' ";
    }
  }else{
    $condition = "";
  }
  //process entTags and/or txtIDs
  $textIDs = array();
  if ($tagIDs) {
    if (is_string($tagIDs)) {//convert to array
      if(strpos($tagIDs,",")) {
        $tagIDs = explode(",",$tagIDs);
      } else {
        $tagIDs = array($tagIDs);
      }
    }
//  echo "in tags - ".$condition."\n";
    //convert all entTags to their associated txtID
    foreach( $tagIDs as $tagID) {
      if (is_numeric($tagID)) {
        array_push($textIDs,$tagID);
      } else if (strlen($tagID) > 3) {//find text for this entity tag
        $txtIDs = null;
        $prefix = substr($tagID,0,3);
        $id = substr($tagID,3);
        switch ($prefix) {
          case 'bln':
            $baseline = new Baseline($id);
            if ($baseline->hasError() || !$baseline->getID()) {
              break;
            }
            $id = $baseline->getSurfaceID();
          case 'srf':
            $surface = new Surface($id);
            if (!$surface->hasError() && $surface->getID() == $id) {
              $txtIDs = $surface->getTextIDs();
            }
            break;
          case 'edn':
            $edition = new Edition($id);
            if (!$edition->hasError() && $edition->getID() == $id) {
              $txtIDs = array($edition->getTextID());
            }
            break;
          case 'tmd':
            $textMetadata = new TextMetadata($id);
            if (!$textMetadata->hasError() && $textMetadata->getID() == $id) {
              $txtIDs = array($textMetadata->getTextID());
            }
            break;
          case 'txt':
            $txtIDs = array($id);
        }
        if ($txtIDs && count($txtIDs)) {
          foreach ($txtIDs as $txtID) {
            if (!in_array($txtID,$textIDs)) {
              array_push($textIDs,$txtID);
            }
          }
        }
      }
    }
  }
// check for cache
  if ($txtIDs && count($textIDs) == 1 && USECACHE) {// text ids
    $text = new Text($textIDs[0]);
    //todo check edition mod times are less than cache
    if ($text->isPublic()) {
      if ($text->getJsonCacheID()) { // text is public and has cache
        // text entities are cached so use cache
        $jsonCache = new JsonCache($text->getJsonCacheID());
        if (!$jsonCache->hasError()) {
          print $jsonCache->getJsonString();
          exit;
        }
      } else if ($text->getCKN()) { // check if there is a cache entry for this text
        // use ckn for lookup
        $jsonCache = new JsonCache($text->getCKN());
        if (!$jsonCache->hasError() && $jsonCache->getLabel() == $text->getCKN()) {
          print $jsonCache->getJsonString();
          exit;
        }
      }
    }
  }


  if ($txtIDs && count($textIDs)) {// text ids
    $condition2 = "txt_id in ('".join("','",$textIDs)."') ";
  } else {
    $condition2 = "";
  }

  //aggregate conditions
  if($condition) {
    if ($condition2) {
      $condition .= " or ".$condition2;
    }
  } else if ($condition2) {
    $condition = $condition2;
  }

  $imgIDs = array();
  $anoIDs = array();
  $atbIDs = array();
  $attrs = array();
  $texts = null;

  if (strlen($condition)) {
//    echo "calling to get text ".DBNAME." ".$condition."\n";
//    echo "userid ".getUserID()."\n";
    $texts = new Texts($condition,'txt_ckn',null,null);
  }
  if ($texts && $texts->getError()) {//problem getting texts
    array_push($warnings,"Warning text available error ".$texts->getError());
  } else if (!$texts || $texts->getCount() == 0) {//no condition or text unavailable so warn
    array_push($warnings,"Warning no text available for text IDs ".join(',',$textIDs));
  } else {// texts found so process them
    $termInfo = getTermInfoForLangCode('en');
    $dictionaryCatalogTypeID = $termInfo['idByTerm_ParentLabel']['dictionary-catalogtype'];//term dependency
    foreach ($texts as $text){
      if (count($text->getReplacementIDs(true))) continue; // skip forwarding references
      $txtID = $text->getID();
      if ($txtID && !array_key_exists($txtID, $entities['txt'])) {
        $ckn = $text->getCKN();
        $entities['txt'][$txtID] = array( 'CKN' => $ckn,
                               'id' => $txtID,
                               'tmdIDs' => array(),
                               'ednIDs' => array(),
                               'blnIDs' => array(),
                               'value' => $text->getTitle(),
                               'ref' => $text->getRef(),
                               'editibility' => $text->getOwnerID(),
                               'readonly' => $text->isReadonly(),
                               'title' => $text->getTitle());
        $cknLookup[$ckn] = $txtID;
        $tImgIDs = $text->getImageIDs();
        if ($tImgIDs && count($tImgIDs) > 0) {
          $entities['txt'][$txtID]['imageIDs'] = $tImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$tImgIDs));
        }
        $AnoIDs = $text->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['txt'][$txtID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $text->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['txt'][$txtID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $text->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['txt'][$txtID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $text->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['txt'][$txtID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
        $AtbIDs = $text->getEditionReferenceIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['txt'][$txtID]['refIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      } // if txtID
    } //for text
    $txtIDs = array_keys($entities['txt']);
  //  $textsSwitchInfo = getSwitchInfo($txtIDs);
    //find surfaces for all texts
    $surfaces = new Surfaces("srf_text_ids && ARRAY[".join(",",$txtIDs)."]",null,null,null);
    $surfaces->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $srfIDs = array();
    $frgIDs = array();
    foreach ($surfaces as $surface) {
      $srfID = $surface->getID();
      if ($srfID && !array_key_exists($srfID, $entities['srf'])) {
        $frgID =  $surface->getFragmentID();
        array_push($frgIDs,$frgID);
        $entities['srf'][$srfID] = array( 'fragmentID'=>$frgID,
                                        'id' => $srfID,
                                        'number' => $surface->getNumber(),
                                        'label' => $surface->getLabel(),
                                        'value' => $surface->getDescription(),
                                        'description' => $surface->getDescription(),
                                        'layer' => $surface->getLayerNumber(),
                                        'textIDs' => $surface->getTextIDs());
        array_push($srfIDs,$srfID);
        $sImgIDs = $surface->getImageIDs();
        if ($sImgIDs && count($sImgIDs) > 0) {
          $entities['srf'][$srfID]['imageIDs'] = $sImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$sImgIDs));
        }
        $AnoIDs = $surface->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['srf'][$srfID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $surface->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['srf'][$srfID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $surface->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['srf'][$srfID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $surface->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['srf'][$srfID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    }
    //find fragments for all surfaces
    $fragments = new Fragments("frg_id in (".join(",",$frgIDs).")",null,null,null);
    $fragments->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $mcxIDs = array();
    $prtIDs = array();
    foreach ($fragments as $fragment) {
      $frgID = $fragment->getID();
      $fragMcxIDs = $fragment->getMaterialContextIDs();
      $mcxIDs = array_merge($mcxIDs, $fragMcxIDs);
      if ($frgID && !array_key_exists($frgID, $entities['frg'])) {
        $prtID = $fragment->getPartID();
        array_push($prtIDs,$prtID);
        $entities['frg'][$frgID] = array( 'partID'=> $prtID,
                               'id' => $frgID,
                               'value' => $fragment->getLabel(),
                               'label' => $fragment->getLabel(),
                               'mcxIDs' => $fragMcxIDs,
                               'description' => $fragment->getDescription(),
                               'readonly' => $fragment->isReadonly(),
                               'locRef' => $fragment->getLocationRefs());
        $fImgIDs = $fragment->getImageIDs();
        if ($fImgIDs && count($fImgIDs) > 0) {
          $entities['frg'][$frgID]['imageIDs'] = $fImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$fImgIDs));
        }
        $AnoIDs = $fragment->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['frg'][$frgID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $fragment->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['frg'][$frgID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $fragment->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['frg'][$frgID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $fragment->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['frg'][$frgID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    }
    //find parts for all fragments
    $parts = new Parts("prt_id in (".join(",",$prtIDs).")",null,null,null);
    $parts->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $itmIDs = array();
    foreach ($parts as $part) {
      $prtID = $part->getID();
      if ($prtID && !array_key_exists($prtID, $entities['prt'])) {
        $itmID = $part->getItemID();
        array_push($itmIDs,$itmID);
        $entities['prt'][$prtID] = array( 'itemID'=> $itmID,
                               'id' => $prtID,
                               'label' => $part->getLabel(),
                               'value' => $part->getLabel(),
                               'readonly' => $part->isReadonly(),
                               'typeID' => $part->getType(),
                               'order' => $part->getSequence(),
                               'manufactureID' => $part->getManufactureID(),
                               'mediumIDs' => $part->getMediums(),
                               'measure' => $part->getMeasure(),
                               'shapeID' => $part->getShape());
        $pImgIDs = $part->getImageIDs();
        if ($pImgIDs && count($pImgIDs) > 0) {
          $entities['prt'][$prtID]['imageIDs'] = $pImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$pImgIDs));
        }
        $AnoIDs = $part->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['prt'][$prtID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $part->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['prt'][$prtID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $part->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['prt'][$prtID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
      }
    }
    //find items for all parts
    $items = new Items("itm_id in (".join(",",$itmIDs).")",null,null,null);
    $items->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    foreach ($items as $item) {
      $itmID = $item->getID();
      if ($itmID && !array_key_exists($itmID, $entities['itm'])) {
        $entities['itm'][$itmID] = array( 'title' => $item->getTitle(),
                               'id' => $itmID,
                               'value' => $item->getTitle(),
                               'editibility' => $item->getOwnerID(),
                               'readonly' => $item->isReadonly(),
                               'typeID' => $item->getType(),
                               'measure' => $item->getMeasure(),
                               'shapeID' => $item->getShapeID());
        $iImgIDs = $item->getImageIDs();
        if ($iImgIDs && count($iImgIDs) > 0) {
          $entities['itm'][$itmID]['imageIDs'] = $iImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$iImgIDs));
        }
        $AnoIDs = $item->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['itm'][$itmID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $item->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['itm'][$itmID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $item->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['itm'][$itmID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
      }
    }
    //calc fragment item titles
    //$entities['frg'][$frgID] = array( 'itemTitle'=> $fragment->getPart(true)->getItem(true)->getTitle(),
    //find all baselines for all surfaces
    $baselines = new Baselines("bln_surface_id in (".join(",",$srfIDs).")",null,null,null);
    $baselines->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $blnIDs = array();
    foreach ($baselines as $baseline) {
      $blnID = $baseline->getID();
      if ($blnID && !array_key_exists($blnID, $entities['bln'])) {
        $segIDs = $baseline->getSegIDs();
        $entities['bln'][$blnID] = array( 'url' => $baseline->getURL(),
                               'id' => $blnID,
                               'type' => $baseline->getType(),
                               'value' => ($baseline->getURL()?$baseline->getURL():$baseline->getTranscription()),
                               'readonly' => $baseline->isReadonly(),
                               'editibility' => $baseline->getOwnerID(),
                               'transcription' => $baseline->getTranscription(),
                               'boundary' => $baseline->getImageBoundary(),
                               'segCount' => ($segIDs?count($segIDs):0),
                               'segIDs' => $segIDs);
        array_push($blnIDs,$blnID);
        $srfID = $baseline->getSurfaceID();
        if ($srfID && array_key_exists($srfID,$entities['srf'])) {
          $entities['bln'][$blnID]['surfaceID'] = $srfID;
          if ($entities['srf'][$srfID]['textIDs']) {
            $entities['bln'][$blnID]['textIDs'] = $entities['srf'][$srfID]['textIDs'];
            foreach ($entities['bln'][$blnID]['textIDs'] as $blnTxtID) {
              array_push($entities['txt'][$blnTxtID]['blnIDs'],$blnID);
            }
          }
        }
        $bImgID = $baseline->getImageID();
        if ($bImgID) {
          $entities['bln'][$blnID]['imageID'] = $bImgID;
          $imgIDs = array_unique(array_merge($imgIDs,array($bImgID)));
        }
        $AnoIDs = $baseline->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['bln'][$blnID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $baseline->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['bln'][$blnID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $baseline->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['bln'][$blnID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $baseline->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['bln'][$blnID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if blnID
    }// for baseline
    //find segments for all baselines
    $segments = new Segments("seg_baseline_ids && ARRAY[".join(",",$blnIDs)."]",null,null,null);
    $segments->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $segIDs = array();
    foreach ($segments as $segment) {
      $segID = $segment->getID();
      if ($segID && !array_key_exists($segID, $entities['seg'])) {
        $sBlnIDs = $segment->getBaselineIDs();
        foreach( $sBlnIDs as $blnID) {
          $entities['bln'][$blnID]['segIDs'] = array_merge($entities['bln'][$blnID]['segIDs'],array($segID));
        }
        $entities['seg'][$segID] = array( 'surfaceID'=> $entities['bln'][$blnIDs[0]]['surfaceID'],
                               'id' => $segID,
                               'baselineIDs' => $sBlnIDs,
                               'value' => 'seg'.$segID,
                               'center' => $segment->getCenter(),
                               'editibility' => $segment->getOwnerID(),
                               'readonly' => $segment->isReadonly(),
                               'layer' => $segment->getLayer());
        $boundary = $segment->getImageBoundary();
        if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
          $entities['seg'][$segID]['boundary']= array();
          foreach($boundary as $polygon) {
            array_push($entities['seg'][$segID]['boundary'], $polygon->getPoints());
          }
          $entities['seg'][$segID]['urls']= $segment->getURLs();
        }
        $stringpos = $segment->getStringPos();
        if ($stringpos && count($stringpos) > 0) {
          $entities['seg'][$segID]['stringpos']= $stringpos;
        }
        array_push($segIDs,$segID);
        $mappedSegIDs = $segment->getMappedSegmentIDs();
        if ($mappedSegIDs && count($mappedSegIDs) > 0) {
          $entities['seg'][$segID]['mappedSegIDs'] = $mappedSegIDs;
        }
        $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
        if ($segBlnOrder) {
          $entities['seg'][$segID]['ordinal'] = $segBlnOrder;
        }
        $segCode = $segment->getScratchProperty("sgnCode");
        if ($segCode) {
          $entities['seg'][$segID]['code'] = $segCode;
          $entities['seg'][$segID]['value'] = $segCode;
        }
        $segCatCode = $segment->getScratchProperty("sgnCatCode");
        if ($segCatCode) {
          $entities['seg'][$segID]['pcat'] = $segCatCode;
  //        $entities['seg'][$segID]['value'] = $segCatCode;
        }
        $segLoc = $segment->getScratchProperty("sgnLoc");
        if ($segLoc) {
          $entities['seg'][$segID]['loc'] = $segLoc;
        }
        $AnoIDs = $segment->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['seg'][$segID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $segment->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['seg'][$segID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $segment->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['seg'][$segID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $segment->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['seg'][$segID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if segID
    } // for segment
    //find images for all entities with imageIDs
    $images = new Images("img_id in (".join(",",$imgIDs).")",null,null,null);
    $images->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    foreach ($images as $image) {
      $imgID = $image->getID();
      if ($imgID && !array_key_exists($imgID, $entities['img'])) {
        $title = $image->getTitle();
        $url = $image->getURL();
        $entities['img'][$imgID] = array( 'title'=> $image->getTitle(),
                               'id' => $imgID,
                               'value'=> ($title?$title:substr($url,strrpos($url,'/')+1)),
                               'readonly' => $image->isReadonly(),
                               'url' => $url,
                               'type' => $image->getType(),
                               'boundary' => $image->getBoundary());
        if ($url) {
          $info = pathinfo($url);
          $dirname = $info['dirname'];
          if (strpos($dirname,'full/full') > -1) { //assume iiif
            $fullpath = str_replace('full/full','full/pct:5',$dirname).'/'.$info['basename'];
          } else {
            $fullpath =  $dirname."/th".$info['basename'];
          }
          $entities['img'][$imgID]['thumbUrl'] = $fullpath;
        }
        $AnoIDs = $image->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['img'][$imgID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $image->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['img'][$imgID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $image->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['img'][$imgID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $image->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['img'][$imgID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if imgID
    }//for images
    //find spans for all segments
    $spans = new Spans("spn_segment_ids[1] in(".join(",",$segIDs).")",null,null,null);
    $spans->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $spnIDs = array();
    foreach ($spans as $span) {
      $spnID = $span->getID();
      if ($spnID && !array_key_exists($spnID, $entities['spn'])) {
        $spnSegIDs = $span->getSegmentIDs();
        $entities['spn'][$spnID] = array( 'segIDs'=> $spnSegIDs,
                               'id' => $spnID,
                               'value' => 'spn'.$spnID,
                               'readonly' => $span->isReadonly(),
                               'baselineID' => $entities['seg'][$spnSegIDs[0]]['baselineIDs'][0]);
        array_push($spnIDs,$spnID);
        $AnoIDs = $span->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['spn'][$spnID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $span->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['spn'][$spnID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $span->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['spn'][$spnID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $span->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['spn'][$spnID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if spnID
    } // for span
    //find lines for all spans
    $lines = new Lines("lin_span_ids && ARRAY[".join(",",$spnIDs)."]",null,null,null);
    $lines->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    foreach ($lines as $line) {
      $linID = $line->getID();
      if ($linID && !array_key_exists($linID, $entities['lin'])) {
        $linSpnIDs = $line->getSpanIDs();
        $entities['lin'][$linID] = array( 'spnIDs'=> $linSpnIDs,
                               'id' => $linID,
                               'order' => $line->getOrder(),
                               'value' => $line->getMask(),
                               'readonly' => $line->isReadonly(),
                               'mask' => $line->getMask());
        if ($linSpnIDs && count($linSpnIDs) && @$linSpnIDs[count($linSpnIDs)-1] && @$entities['spn'][$linSpnIDs[count($linSpnIDs)-1]] ) {
          $lastSpnSegIDs = $entities['spn'][$linSpnIDs[count($linSpnIDs)-1]]['segIDs'];
          $entities['lin'][$linID]['txtID'] = @$entities['bln'][$entities['spn'][$linSpnIDs[0]]['baselineID']]['txtID'];
          $entities['lin'][$linID]['brSegID'] = $lastSpnSegIDs[count($lastSpnSegIDs)-1];
        }
        $AnoIDs = $line->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['lin'][$linID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $line->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['lin'][$linID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $line->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['lin'][$linID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $line->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['lin'][$linID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if linID
    } // for line
    //find textmetadata for all texts
    $textMetadatas = new TextMetadatas("tmd_text_id in (".join(",",$txtIDs).")",null,null,null);
    $textMetadatas->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $tmdIDs = array();
    foreach ($textMetadatas as $textMetadata) {
      $tmdID = $textMetadata->getID();
      if ($tmdID && !array_key_exists($tmdID, $entities['tmd'])) {
        array_push($tmdIDs,$tmdID);
        $tmdTxtID = $textMetadata->getTextID();
        $entities['tmd'][$tmdID] = array( 'txtID'=> $tmdTxtID,
                               'id' => $tmdID,
                               'ednIDs' => array(),
                               'readonly' => $textMetadata->isReadonly(),
                               'editibility' => $textMetadata->getOwnerID(),
                               'typeIDs' => $textMetadata->getTypeIDs());
        array_push($entities['txt'][$tmdTxtID]['tmdIDs'],$tmdID);
        $tmRefIDs =$textMetadata->getReferenceIDs();
        if ($tmRefIDs && count($tmRefIDs) > 0) {
          $entities['tmd'][$tmdID]['refIDs'] = $tmRefIDs;
          $atbIDs = array_merge($atbIDs,$tmRefIDs);
        }
        $AnoIDs = $textMetadata->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['tmd'][$tmdID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $textMetadata->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['tmd'][$tmdID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $textMetadata->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['tmd'][$tmdID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $textMetadata->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['tmd'][$tmdID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }//if tmdID
    } // for textMetadata
    //find editions for all textmetadatas
    $editions = new Editions("edn_text_id in (".join(",",$txtIDs).")",null,null,null);
    $editions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $seqIDs = array();
    foreach ($editions as $edition) {
      $ednID = $edition->getID();
      if ($ednID && !array_key_exists($ednID, $entities['edn'])) {
        $entities['edn'][$ednID] = array( 'description'=> $edition->getDescription(),
                               'id' => $ednID,
                               'value'=> $edition->getDescription(),
                               'readonly' => $edition->isReadonly(),
                               'editibility' => $edition->getOwnerID(),
                               'typeID' => $edition->getTypeID(),
                               'txtID' => $edition->getTextID(),
                               'seqIDs' => $edition->getSequenceIDs());
        array_push($entities['txt'][$edition->getTextID()]['ednIDs'],$ednID);
        $seqIDs = array_merge($seqIDs,$edition->getSequenceIDs());
        $catalogs = new Catalogs($ednID." = ANY (\"cat_edition_ids\") AND cat_type_id != $dictionaryCatalogTypeID",null,null,null);
        $catalogs->setAutoAdvance(false); // make sure the iterator doesn't prefetch
        $catIDs = array();
        foreach ($catalogs as $catalog) {
          $catID = $catalog->getID();
          if ($catID && !array_key_exists($catID, $entities['cat'])) {
            $entities['cat'][$catID] = array( 'description'=> $catalog->getDescription(),
                                   'id' => $catID,
                                   'value'=> $catalog->getTitle(),
                                   'readonly' => $catalog->isReadonly(),
                                   'editibility' => $catalog->getOwnerID(),
                                   'ednIDs' => $catalog->getEditionIDs(),
                                   'typeID' => $catalog->getTypeID());
          }
          array_push($catIDs,$catID);
          $lemmas = new Lemmas("lem_catalog_id = $catID");
          if ($lemmas && $lemmas->getCount() > 0) {
            $lemIDs = array();
            foreach($lemmas as $lemma) {
              $lemID = $lemma->getID();
              array_push($lemIDs,$lemID);
              if ($lemID && !array_key_exists($lemID, $entities['lem'])) {
                $entities['lem'][$lemID] = array( 'value'=> $lemma->getValue(),
                                       'id' => $lemID,
                                       'readonly' => $lemma->isReadonly(),
                                       'trans' => $lemma->getTranslation(),
                                       'search' => $lemma->getSearchValue(),
                                       'gloss' => $lemma->getDescription(),
                                       'typeID' => $lemma->getTypeID(),
                                       'certainty' => $lemma->getCertainty(),
                                       'catID' => $lemma->getCatalogID(),
                                       'entityIDs' => $lemma->getComponentIDs(),
                                       'pos' => $lemma->getPartOfSpeech(),
                                       'spos' => $lemma->getSubpartOfSpeech(),
                                       'order' => $lemma->getHomographicOrder(),
                                       'decl' => $lemma->getDeclension(),
                                       'gender' => $lemma->getGender(),
                                       'class' => $lemma->getVerbalClass(),
                                       'sort' => $lemma->getSortCode(),
                                       'sort2' => $lemma->getSortCode2());
                if ($lemma->getCompoundAnalysis()) {
                  $entities['lem'][$lemID]['compAnalysis'] = $lemma->getCompoundAnalysis();
                }
                if ($lemma->getScratchProperty('phonetics')) {
                  $entities["update"]['lem'][$lemID]['phonetics'] = $lemma->getScratchProperty('phonetics');
                }
                $lemCompIDs = $lemma->getComponentIDs();
                if ($lemCompIDs && count($lemCompIDs) > 0) {
                  foreach ($lemCompIDs as $gid) {
                    list($entPrefix,$entID) = explode(':',$gid);
                    if (!array_key_exists($entPrefix,$entityIDs)) {
                      $entityIDs[$entPrefix] = array($entID);
                    } else if (!in_array($entID,$entityIDs[$entPrefix])){ //add if doesn't exist
                      array_push($entityIDs[$entPrefix],$entID);
                    }
                  }
                }
                $AnoIDs = $lemma->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities['lem'][$lemID]['annotationIDs'] = $AnoIDs;
                  $anoIDs = array_merge($anoIDs,$AnoIDs);
                }
                $linkedAnoIDsByType = $lemma->getLinkedAnnotationsByType();
                if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                  $entities['lem'][$lemID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                  foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                    $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  }
                }
                $linkedByAnoIDsByType = $lemma->getLinkedByAnnotationsByType();
                if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                  $entities['lem'][$lemID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                  foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                    $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  }
                }
                $AtbIDs = $lemma->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities['lem'][$lemID]['attributionIDs'] = $AtbIDs;
                  $atbIDs = array_merge($atbIDs,$AtbIDs);
                }
              }
              $entities['cat'][$catID]['lemIDs'] = $lemIDs;
            }
          }
          $AnoIDs = $catalog->getAnnotationIDs();
          if ($AnoIDs && count($AnoIDs) > 0) {
            $entities['cat'][$catID]['annotationIDs'] = $AnoIDs;
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $linkedAnoIDsByType = $catalog->getLinkedAnnotationsByType();
          if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
            $entities['cat'][$catID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
            foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
              $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
            }
          }
          $linkedByAnoIDsByType = $catalog->getLinkedByAnnotationsByType();
          if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
            $entities['cat'][$catID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
            foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
              $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
            }
          }
          $AtbIDs = $catalog->getAttributionIDs();
          if ($AtbIDs && count($AtbIDs) > 0) {
            $entities['cat'][$catID]['attributionIDs'] = $AtbIDs;
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
        }
        if ($catIDs && count($catIDs)) {
          $entities['edn'][$ednID]['catIDs'] = $catIDs;
        }
        $AnoIDs = $edition->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['edn'][$ednID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $linkedAnoIDsByType = $edition->getLinkedAnnotationsByType();
        if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
          $entities['edn'][$ednID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
          foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $linkedByAnoIDsByType = $edition->getLinkedByAnnotationsByType();
        if ($linkedByAnoIDsByType && count($linkeBydAnoIDsByType) > 0){
          $entities['edn'][$ednID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
          foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
            $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
          }
        }
        $AtbIDs = $edition->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['edn'][$ednID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    } // for editions
    if (count( $seqIDs) > 0) {
      $entityIDs['seq'] = $seqIDs;
    }
    if (count( $atbIDs) > 0) {
  //    $entityIDs['atb'] = $atbIDs;
    }
    if (count( $anoIDs) > 0) {
      $entityIDs['ano'] = $anoIDs;
    }
    if (count( $entityIDs) > 0) {
      getRelatedEntities($entityIDs);
    }
    // strip away empty entityType arrays
    foreach ($entities as $prefix => $entityArray) {
      if ( count($entityArray) == 0) {
        unset($entities[$prefix]);
      }
    }
    $textsSwitchInfo = getSwitchInfoByTextFromEntities($entities,$gra2SclMap,$errors,$warnings);
    $retVal = array("entities" => $entities,
                    "cknToTextID" => $cknLookup);
    if ($textsSwitchInfo) {
      $retVal["switchInfoByTextID"] = $textsSwitchInfo;
    }
  }
  if (count($warnings) > 0) {
    $retVal["warnings"] = $warnings;
  }
  if (count($errors) > 0) {
    $retVal["errors"] = $errors;
  }
  $jsonRetVal = json_encode($retVal);
  if (count($errors) == 0 && count(array_keys($cknLookup)) == 1 && USECACHE) {
    foreach($cknLookup as $ckn => $txtID) {
      $text = new Text($txtID);
      if ($text->isPublic()) {
        $jCacheID = $text->getJsonCacheID();
        if ($jCacheID) {
          $jsonCache = new JsonCache($jCacheID);
        } else {
          $jsonCache = new JsonCache();
          $jsonCache->setLabel($ckn);
        }
        $jsonCache->setJsonString($jsonRetVal);
        $jsonCache->setVisibilityIDs(DEFAULTCACHEVISID?array(DEFAULTCACHEVISID):array(6));
        $jsonCache->setOwnerID(DEFAULTCACHEOWNERID?DEFAULTCACHEOWNERID:6);
        $jsonCache->save();
        if (!$jsonCache->hasError()) {
          $text->setJsonCacheID($jsonCache->getID());
          $text->save();
        }
      }
    }
  }
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".$jsonRetVal.");";
    }
  } else {
    print $jsonRetVal;
  }

  function getRelatedEntities($entityIDs) {
    global $entities,$anoIDs,$atbIDs,$gra2SclMap,$segID2sclIDs,$processedTokIDs,$errors,$warnings;
    static $prefixProcessOrder = array('lnk','seq','lem','inf','scl','gra','cmp','tok','seg','bln','atb','ano');//important attr before anno to terminate
    if (!$entityIDs) return;
    $prefix = null;
    foreach ($prefixProcessOrder as $entCode) {
      if (array_key_exists( $entCode,$entityIDs) && count($entityIDs[$entCode]) > 0) {
        $prefix = $entCode;
        break;
      }
    }
    if (!$prefix) {
      return;
    }
    $tempIDs = $entityIDs[$prefix];
    if ($tempIDs && count($tempIDs) > 0 && !array_key_exists($prefix,$entities)) {
      $entities[$prefix] = array();
    }
    $entIDs = array();
    foreach ($tempIDs as $entID){//skip any already processed
      if ($entID && !array_key_exists($entID,$entities[$prefix])) {
        array_push($entIDs,$entID);
      }else if ($prefix == "seg" && array_key_exists($entID,$segID2sclIDs)) {
        $entities['seg'][$entID]['sclIDs'] = $segID2sclIDs[$entID];
      }
    }
    unset($entityIDs[$prefix]);//we have captured the ids of this entity type remove them so we progress in the recursive call
    if ($entIDs && count($entIDs) > 0) {
      switch ($prefix) {
        case 'seq':
          $sequences = new Sequences("seq_id in (".join(",",$entIDs).")",null,null,null);
          $sequences->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($sequences as $sequence) {
            $seqID = $sequence->getID();
            if ($seqID && !array_key_exists($seqID, $entities['seq'])) {
              $entities['seq'][$seqID] = array( 'label'=> $sequence->getLabel(),
                                     'id' => $seqID,
                                     'value'=> $sequence->getLabel(),
                                     'editibility' => $sequence->getOwnerID(),
                                     'readonly' => $sequence->isReadonly(),
                                     'superscript' => $sequence->getSuperScript(),
                                     'typeID' => $sequence->getTypeID());
              $sEntIDs = $sequence->getEntityIDs();
              if ($sEntIDs && count($sEntIDs) > 0) {
                $entities['seq'][$seqID]['entityIDs'] = $sEntIDs;
                foreach ($sEntIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (!array_key_exists($entPrefix,$entityIDs)) {
                    $entityIDs[$entPrefix] = array($entID);
                  } else { //add if doesn't exist
                    array_push($entityIDs[$entPrefix],$entID);
                  }
                }
              }
              $AnoIDs = $sequence->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['seq'][$seqID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $sequence->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['seq'][$seqID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $sequence->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['seq'][$seqID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $sequence->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['seq'][$seqID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'cmp':
          $compounds = new Compounds("cmp_id in (".join(",",$entIDs).")",null,null,null);
          $compounds->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($compounds as $compound) {
            $cmpID = $compound->getID();
            if ($cmpID && !array_key_exists($cmpID, $entities['cmp'])) {
              $entities['cmp'][$cmpID] = array( 'value'=> $compound->getValue(),
                                     'id' => $cmpID,
                                     'modStamp' => $compound->getModificationStamp(),
                                     'transcr' => $compound->getTranscription(),
                                     'readonly' => $compound->isReadonly(),
                                     'case' => $compound->getCase(),
                                     'class' => $compound->getClass(),
                                     'type' => $compound->getType(),
                                     'tokenIDs' => $compound->getTokenIDs(),
                                     'sort' => $compound->getSortCode(),
                                     'sort2' => $compound->getSortCode2());
              $cEntIDs = $compound->getComponentIDs();
              if ($cEntIDs && count($cEntIDs) > 0) {
                $entities['cmp'][$cmpID]['entityIDs'] = $cEntIDs;
                foreach ($cEntIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (array_key_exists($entPrefix,$entityIDs)) {
                    //if (!in_array($entID,$entityIDs[$entPrefix])) {
                      array_push($entityIDs[$entPrefix],$entID);
                    //}
                  } else {
                    $entityIDs[$entPrefix] = array($entID);
                  }
                }
              }
              $AnoIDs = $compound->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['cmp'][$cmpID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $compound->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['cmp'][$cmpID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $compound->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['cmp'][$cmpID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $compound->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['cmp'][$cmpID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'tok':
          $tokens = new Tokens("tok_id in (".join(",",$entIDs).")",null,null,null);
          $tokens->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $graIDs = array();
          foreach ($tokens as $token) {
            $tokID = $token->getID();
            if ($tokID && !array_key_exists($tokID, $entities['tok'])) {
              $entities['tok'][$tokID] = array( 'value'=> $token->getValue(),
                                     'id' => $tokID,
                                     'modStamp' => $token->getModificationStamp(),
                                     'transcr' => $token->getTranscription(),
                                     'affix' => $token->getNominalAffix(),
                                     'readonly' => $token->isReadonly(),
                                     'sort' => $token->getSortCode(),
                                     'sort2' => $token->getSortCode2(),
                                     'syllableClusterIDs' => array());
              array_push($processedTokIDs,$tokID);
              $tGraIDs = $token->getGraphemeIDs();
              if ($tGraIDs && count($tGraIDs) > 0) {
                $entities['tok'][$tokID]['graphemeIDs'] = $tGraIDs;
                if (!array_key_exists('gra',$entityIDs)) {
                  $entityIDs['gra'] = array();
                }
                foreach ($tGraIDs as $graID) {
                  array_push($entityIDs['gra'],$graID);
                }
              }
              $AnoIDs = $token->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['tok'][$tokID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $token->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['tok'][$tokID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $token->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['tok'][$tokID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $token->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['tok'][$tokID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'gra':
          $graphemes = new Graphemes("gra_id in (".join(",",$entIDs).")",null,null,null);
          $graphemes->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($graphemes as $grapheme) {
            $graID = $grapheme->getID();
            if ($graID && !array_key_exists($graID, $entities['gra'])) {
              $entities['gra'][$graID] = array( 'value'=> $grapheme->getValue(),
                                     'id' => $graID,
                                     'txtcrit' => $grapheme->getTextCriticalMark(),
                                     'readonly' => $grapheme->isReadonly(),
                                     'alt' => $grapheme->getAlternative(),
                                     'emmend' => $grapheme->getEmmendation(),
                                     'type' => $grapheme->getType(),
                                     'sort' => $grapheme->getSortCode());
              if ($grapheme->getDecomposition()) {
                 $entities['gra'][$graID]['decomp'] = preg_replace("/:/",'',$grapheme->getDecomposition());
              }
              $AnoIDs = $grapheme->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['gra'][$graID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $grapheme->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['gra'][$graID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $grapheme->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['gra'][$graID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
            }
          }
          break;
        case 'scl':
          $syllables = new SyllableClusters("scl_id in (".join(",",$entIDs).")",null,null,null);
          $syllables->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $sclIDs = array();
          foreach ($syllables as $syllable) {
            $sclID = $syllable->getID();
            if ($sclID && !array_key_exists($sclID, $entities['scl'])) {
              $entities['scl'][$sclID] = array( 'value'=> $syllable->getValue(),
                                                'txtcrit' => $syllable->getTextCriticalMark(),
                                                'id' => $sclID,
                                                'modStamp' => $syllable->getModificationStamp(),
                                                'readonly' => $syllable->isReadonly(),
                                                'sort' => $syllable->getSortCode(),
                                                'sort2' => $syllable->getSortCode2());
              $sGraIDs = $syllable->getGraphemeIDs();
              if ($sGraIDs && ccount($sGraIDs) > 0) {
                $entities['scl'][$sclID]['graphemeIDs'] = $sGraIDs;
                if (!array_key_exists('gra',$entityIDs)) {
                  $entityIDs['gra'] = array();
                }
                foreach ($sGraIDs as $sGraID) {
                  if (!in_array($sGraID,$entityIDs['gra'])) {
                      array_push($entityIDs['gra'],$sGraID);
                  }
                  $gra2SclMap[$sGraID] = $sclID;
                }
              }
              $scratch = $syllable->getScratchPropertiesJsonString();
              if ($scratch){
                $entities['scl'][$sclID]['scratch'] = $scratch;
              }
              $segID = $syllable->getSegmentID();
              if ($segID && count($segID) > 0) {
                $entities['scl'][$sclID]['segID'] = $segID;
                if (!array_key_exists(@$segID,$segID2sclIDs)) {
                  $segID2sclIDs[$segID] = array($sclID);
                } else if (!in_array($sclID,$segID2sclIDs[$segID])) {
                    array_push($segID2sclIDs[$segID],$sclID);
                }
                if (!array_key_exists('seg',$entityIDs)) {
                  $entityIDs['seg'] = array($segID);
                } else if (!in_array($segID,$entityIDs['seg'])) {
                    array_push($entityIDs['seg'],$segID);
                }
              }
              $AnoIDs = $syllable->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['scl'][$sclID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $syllable->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['scl'][$sclID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $syllable->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['scl'][$sclID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $syllable->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['scl'][$sclID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'seg':
          $segments = new Segments("seg_id in (".join(",",$entIDs).")",null,null,null);
          $segments->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $blnIDs = array();
          foreach ($segments as $segment) {
            $segID = $segment->getID();
            if ($segID && !array_key_exists($segID, $entities['seg'])) {
              $entities['seg'][$segID] = array( 'layer' => $segment->getLayer(),
                                                'id' => $segID,
                                                'editibility' => $segment->getOwnerID(),
                                                'readonly' => $segment->isReadonly(),
                                                'center' => $segment->getCenter(),
                                                'value' => 'seg'.$segID);
              if (array_key_exists($segID,$segID2sclIDs)) {
                $entities['seg'][$segID]['sclIDs']= $segID2sclIDs[$segID];
              }
              $boundary = $segment->getImageBoundary();
              if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
                $entities['seg'][$segID]['boundary']= array();
                foreach($boundary as $polygon) {
                  array_push($entities['seg'][$segID]['boundary'], $polygon->getPoints());
                }
                $entities['seg'][$segID]['urls']= $segment->getURLs();
              }
              $mappedSegIDs = $segment->getMappedSegmentIDs();
              if ($mappedSegIDs && count($mappedSegIDs) > 0) {
                $entities['seg'][$segID]['mappedSegIDs'] = $mappedSegIDs;
              }
              $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
              if ($segBlnOrder) {
                $entities['seg'][$segID]['ordinal'] = $segBlnOrder;
              }
              $sBlnIDs = $segment->getBaselineIDs();
              if ($sBlnIDs && count($sBlnIDs) > 0) {
                $entities['seg'][$segID]['baselineIDs'] = $sBlnIDs;
                if (!array_key_exists('bln',$entityIDs)) {
                  $entityIDs['bln'] = array();
                }
                foreach ($sBlnIDs as $blnID) {
                  if (!in_array($blnID,$entityIDs['bln'])) {
                      array_push($entityIDs['bln'],$blnID);
                  }
                }
              }
              $stringpos = $segment->getStringPos();
              if ($stringpos && count($stringpos) > 0) {
                $entities['seg'][$segID]['stringpos']= $stringpos;
              }
              // ??? do we need to get spans??? mapped segments???
              $AnoIDs = $segment->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['seg'][$segID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $segment->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['seg'][$segID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $segment->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['seg'][$segID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $segment->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['seg'][$segID]['attributionIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'atb':
          $attributions = new Attributions("atb_id in (".join(",",$entIDs).")",null,null,null);
          $attributions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($attributions as $attribution) {
            $atbID = $attribution->getID();
            if ($atbID && !array_key_exists($atbID, $entities['atb'])) {
              $entities['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
                                      'id' => $atbID,
                                      'value'=> $attribution->getTitle().($attribution->getDetail()?$attribution->getDetail():''),
                                      'readonly' => $attribution->isReadonly(),
                                      'grpID' => $attribution->getGroupID(),
                                      'bibID' => $attribution->getBibliographyID(),
                                      'description' => $attribution->getDescription(),
                                      'detail' => $attribution->getDetail(),
                                      'types' => $attribution->getTypes());
              $AnoIDs = $attribution->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['atb'][$atbID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $attribution->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['atb'][$atbID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $attribution->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['atb'][$atbID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
            }
          }
          break;
        case 'ano':
          $annotations = new Annotations("ano_id in (".join(",",$entIDs).")",null,null,null);
          $annotations->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($annotations as $annotation) {
            $anoID = $annotation->getID();
            if ($anoID && !array_key_exists($anoID, $entities['ano'])) {
              $entities['ano'][$anoID] = array( 'text'=> $annotation->getText(),
                                      'id' => $anoID,
                                      'modStamp' => $annotation->getModificationStamp(),
                                      'linkedFromIDs' => $annotation->getLinkFromIDs(),
                                      'linkedToIDs' => $annotation->getLinkToIDs(),
                                      'value' => ($annotation->getText() ||
                                                  $annotation->getURL()),//todo reformat this to have semantic term with entity value
                                      'readonly' => $annotation->isReadonly(),
                                      'url' => $annotation->getURL(),
                                      'typeID' => $annotation->getTypeID());
              $vis = $annotation->getVisibilityIDs();
              if (in_array(6,$vis)) {
                $vis = "Public";
              } else if (in_array(3,$vis)) {
                $vis = "User";
              } else {
                $vis = "Private";
              }
              $entities['ano'][$anoID]['vis'] = $vis;
              $AnoIDs = $annotation->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['ano'][$anoID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $annotation->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['ano'][$anoID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $annotation->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['ano'][$anoID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $annotation->getAnnotationIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['ano'][$anoID]['annotationIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'lem':
          $lemmas = new Lemmas("lem_id in (".join(",",$entIDs).")",null,null,null);
          $lemmas->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $lemIDs = array();
          foreach ($lemmas as $lemma) {
            $lemID = $lemma->getID();
            if ($lemID && !array_key_exists($lemID, $entities['lem'])) {
              $entities['lem'][$lemID] = array( 'value'=> $lemma->getValue(),
                                     'id' => $lemID,
                                     'readonly' => $lemma->isReadonly(),
                                     'trans' => $lemma->getTranslation(),
                                     'search' => $lemma->getSearchValue(),
                                     'gloss' => $lemma->getDescription(),
                                     'order' => $lemma->getHomographicOrder(),
                                     'typeID' => $lemma->getTypeID(),
                                     'certainty' => $lemma->getCertainty(),
                                     'catID' => $lemma->getCatalogID(),
                                     'entityIDs' => $lemma->getComponentIDs(),
                                     'class' => $lemma->getVerbalClass(),
                                     'pos' => $lemma->getPartOfSpeech(),
                                     'spos' => $lemma->getSubpartOfSpeech(),
                                     'gender' => $lemma->getGender(),
                                     'decl' => $lemma->getDeclension(),
                                     'sort' => $lemma->getSortCode(),
                                     'sort2' => $lemma->getSortCode2());
              if ($lemma->getCompoundAnalysis()) {
                $entities['lem'][$lemID]['compAnalysis'] = $lemma->getCompoundAnalysis();
              }
              $lemCompIDs = $lemma->getComponentIDs();
              if ($lemCompIDs && count($lemCompIDs) > 0) {
                foreach ($lemCompIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (!array_key_exists($entPrefix,$entityIDs)) {
                    $entityIDs[$entPrefix] = array($entID);
                  } else if (!in_array($entID,$entityIDs[$entPrefix])){ //add if doesn't exist
                    array_push($entityIDs[$entPrefix],$entID);
                  }
                }
              }
              $AnoIDs = $lemma->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['lem'][$lemID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $lemma->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['lem'][$lemID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $lemma->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['lem'][$lemID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $lemma->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['lem'][$lemID]['annotationIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
        case 'inf':
          $inflections = new Inflections("inf_id in (".join(",",$entIDs).")",null,null,null);
          $inflections->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          $infIDs = array();
          foreach ($inflections as $inflection) {
            $infID = $inflection->getID();
            if ($infID && !array_key_exists($infID, $entities['inf'])) {
              $entities['inf'][$infID] = array( 'id' => $infID,
                                     'readonly' => $inflection->isReadonly(),
                                     'chaya' => $inflection->getChaya(),
                                     'entityIDs' => $inflection->getComponentIDs(),
                                     'certainty' => $inflection->getCertainty(),
                                     'gender' => $inflection->getGender(),
                                     'num' => $inflection->getGramaticalNumber(),
                                     'case' => $inflection->getCase(),
                                     'person' => $inflection->getVerbalPerson(),
                                     'voice' => $inflection->getVerbalVoice(),
                                     'tense' => $inflection->getVerbalTense(),
                                     'mood' => $inflection->getVerbalMood(),
                                     'conj2nd' => $inflection->getSecondConjugation());
              $infCompIDs = $inflection->getComponentIDs();
              if ($infCompIDs && count($infCompIDs) > 0) {
                foreach ($infCompIDs as $gid) {
                  list($entPrefix,$entID) = explode(':',$gid);
                  if (!array_key_exists($entPrefix,$entityIDs)) {
                    $entityIDs[$entPrefix] = array($entID);
                  } else if (!in_array($entID,$entityIDs[$entPrefix])){ //add if doesn't exist
                    array_push($entityIDs[$entPrefix],$entID);
                  }
                }
              }
              $AnoIDs = $inflection->getAnnotationIDs();
              if ($AnoIDs && count($AnoIDs) > 0) {
                $entities['inf'][$infID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
              $linkedAnoIDsByType = $inflection->getLinkedAnnotationsByType();
              if ($linkedAnoIDsByType && count($linkedAnoIDsByType) > 0){
                $entities['inf'][$infID]['linkedAnoIDsByType'] = $linkedAnoIDsByType;
                foreach ($linkedAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $linkedByAnoIDsByType = $inflection->getLinkedByAnnotationsByType();
              if ($linkedByAnoIDsByType && count($linkedByAnoIDsByType) > 0){
                $entities['inf'][$infID]['linkedByAnoIDsByType'] = $linkedByAnoIDsByType;
                foreach ($linkedByAnoIDsByType as $anoType => $linkedAnoIDs) {
                  $anoIDs = array_merge($anoIDs,$linkedAnoIDs);
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$linkedAnoIDs);
                }
              }
              $AtbIDs = $inflection->getAttributionIDs();
              if ($AtbIDs && count($AtbIDs) > 0) {
                $entities['inf'][$infID]['annotationIDs'] = $AtbIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['atb'] = array();
                }
                $entityIDs['atb'] = array_merge($entityIDs['atb'],$AtbIDs);
              }
            }
          }
          break;
      }//end switch $prefix
    }//end if count $entIDs
    if (count(array_keys($entityIDs)) > 0) {
      getRelatedEntities($entityIDs);
    } else {// post process
      //calculated ordered list of syllableCluster ids for each token
      foreach ($processedTokIDs as $newTokID) {
        $prevSclID = null;
        if (isset($entities['tok'][$newTokID]['graphemeIDs'])) {
          foreach($entities['tok'][$newTokID]['graphemeIDs'] as $tokGraID) {
            $graSclID = @$gra2SclMap[$tokGraID];
            if (!$graSclID) {
              array_push($warnings,"grapheme id  $tokGraID for token id $newTokID is not in syllable map.");
              continue;
            }
            if ($graSclID != $prevSclID) {//skip graphemes of captured syllable
              array_push($entities['tok'][$newTokID]['syllableClusterIDs'],$graSclID);
            }
            $prevSclID = $graSclID;
          }
        }
      }
    }
  }
?>
