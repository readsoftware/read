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
  * loadTextResources
  *
  *  A service that returns a json string of the resources for texts loaded including their surfaces,
  *  textmetadatas, baselines, images, editions and edition glossaries. It also retrieves related
  *  annotations and attributions.
  *  This service relies on ids passed in or loadTextSearchEntities cache of loaded text. There is
  *  NO CACHING for this service, data is always fresh.
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
  ob_start();

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  if (isset($argv)) {
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

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Texts.php';
  require_once dirname(__FILE__) . '/../model/entities/Surfaces.php';
  require_once dirname(__FILE__) . '/../model/entities/Baselines.php';
  require_once dirname(__FILE__) . '/../model/entities/Editions.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;

  $dbMgr = new DBManager();
  $retVal = array();
  $gra2SclMap = array();
  $segID2sclIDs = array();
  $cknLookup = array();
//  $errors = array();
  $warnings = array();
  $condition = "";
  $entities = array( 'insert' => array(),
                     'update' => array());
  $entities["update"] = array( 'cat' => array(),
                               'txt' => array(),
                               'tmd' => array(),
                               'edn' => array(),
                               'bln' => array(),
                               'img' => array(),
                               'srf' => array(),
                               'ano' => array(),
                               'atb' => array());

  $txtIDs = (array_key_exists('ids',$_REQUEST)? $_REQUEST['ids']:null);
  $updateCache = (array_key_exists('update',$_REQUEST)? $_REQUEST['update']:null);
  $removeTextFromCache = (array_key_exists('remove',$_REQUEST)? $_REQUEST['remove']:null);
  $refresh = (isset($data['refresh'])?$data['refresh']:
              (isset($_REQUEST['refresh'])?$_REQUEST['refresh']:
                (defined('DEFAULTTEXTRESOURCEREFRESH')?DEFAULTTEXTRESOURCEREFRESH:0)));
  $jsonRetVal = "";
  $jsonCache = null;
  $isLoadAll = false;
  if (($removeTextFromCache || !$txtIDs)  && !$refresh && defined("USECACHE") && USECACHE) {
    // check for cache
    $dbMgr = new DBManager();
    if (!$dbMgr->getError()) {
      $dbMgr->query("SELECT * FROM jsoncache WHERE jsc_label = 'AllTextResources'");
      if ($dbMgr->getRowCount() > 0 ) {
        $row = $dbMgr->fetchResultRow();
        $jsonCache = new JsonCache($row);
        if (!$jsonCache->hasError() && !$jsonCache->isDirty()) {
          $jsonRetVal = $jsonCache->getJsonString();
        }
      }
    }
  }

  // prep text query condition
  if ($txtIDs && strlen($txtIDs)) { //param is a string if supplies
    $condition = "txt_id in ($txtIDs) and not txt_owner_id = 1";
  } else {
    $condition = "not txt_owner_id = 1";
    $isLoadAll = true;
  }

  // call to remove text from cache
  if ($removeTextFromCache && $txtIDs && $jsonRetVal) {
    $curCache = json_decode($jsonRetVal,true);
    $txtIDs = explode(',',$txtIDs);
    foreach ($txtIDs as $txtID) {
      if (array_key_exists($txtID,$curCache['entities']['insert']['txt'])) {
        unset($curCache['entities']['insert']['txt'][$txtID]);
      }
      if (array_key_exists($txtID,$curCache['entities']['update']['txt'])) {
        unset($curCache['entities']['update']['txt'][$txtID]);
      }
    }
    $jsonCache->setJsonString(json_encode($curCache));
    $jsonCache->clearDirtyBit();
    $jsonCache->save();
    $jsonRetVal = json_encode(array("success"=>true));
  }

  if (!$jsonRetVal || $updateCache) {
    $imgIDs = array();
    $img2blnIDs = array();
    $anoIDs = array();
    $atbIDs = array();
    $orderBy = (defined('TEXT_INV_SORT')?TEXT_INV_SORT:'txt_ckn');
    if ($condition) {
      $texts = new Texts($condition,$orderBy,null,null);
    }
    $termInfo = getTermInfoForLangCode('en');
    $dictionaryCatalogTypeID = $termInfo['idByTerm_ParentLabel']['dictionary-catalogtype'];//term dependency

    foreach ($texts as $text){
      $txtID = $text->getID();
      if ($txtID && !array_key_exists($txtID, $entities['update']['txt'])) {//skip duplicates if any
        $entities['update']['txt'][$txtID] = array('tmdIDs' => array(),
                                                   'ednIDs' => array(),
                                                   'editibility' => $text->getOwnerID(),
                                                   'imageIDs' => array(),
                                                   'blnIDs' => array());
        $txtImgIDs = $text->getImageIDs();
        if ($txtImgIDs && count($txtImgIDs) > 0) {
          $imgIDs = array_merge($imgIDs,$txtImgIDs);
          $entities['update']['txt'][$txtID]['imageIDs'] = $txtImgIDs;
        }
      }
    } //for texts
    $txtIDs = array_keys($entities['update']['txt']);
    $strTxtIDs = join(",",$txtIDs);
    //find surfaces for all texts
    $surfaces = new Surfaces("srf_text_ids && ARRAY[".$strTxtIDs."] and not 5 = ANY(srf_visibility_ids)",null,null,null);
    $surfaces->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $srfIDs = array();
    foreach ($surfaces as $surface) {
      $srfID = $surface->getID();
      if ($srfID && !array_key_exists($srfID, $entities['update']['srf'])) {
        $frgID =  $surface->getFragmentID();
        $entities['update']['srf'][$srfID] = array('fragmentID'=>$frgID,
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
          $entities['update']['srf'][$srfID]['imageIDs'] = $sImgIDs;
          $imgIDs = array_merge($imgIDs,$sImgIDs);
        }
        $AnoIDs = $surface->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['update']['srf'][$srfID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $surface->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['update']['srf'][$srfID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    }

    $srfIDs = array_unique($srfIDs);
    //find all baselines for all surfaces

    $imageBaselineTypeID = $termInfo['idByTerm_ParentLabel']['image-baselinetype'];//term dependency
//    $baselines = new Baselines("not bln_owner_id = 1 and bln_surface_id in (".join(",",$srfIDs).")",null,null,2800);
    $baselines = new Baselines("bln_type_id = $imageBaselineTypeID and not bln_owner_id = 1 and bln_surface_id in (".join(",",$srfIDs).")",null,null,null);
    $baselines->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $blnIDs = array();
    foreach ($baselines as $baseline) {
      if ($baseline->isMarkedDelete()) {
        continue;
      }
      $blnID = $baseline->getID();
      if ($blnID && !array_key_exists($blnID, $entities['update']['bln'])) {
        $url = $baseline->getURL();
//        $segIDs = $baseline->getSegIDs();
        $entities['update']['bln'][$blnID] = array('url' => $url,
                                                   'id' => $blnID,
                                                   'type' => $baseline->getType(),
                                                   'value' => ($url?$url:$baseline->getTranscription()),
                                                   'isLinked' => $baseline->isLinked(),
//                                                   'segCount' => $baseline->getSegmentCount(),
//                                                   'segIDs' => $segIDs,
                                                   'editibility' => $baseline->getOwnerID(),
                                                   'readonly' => $baseline->isReadonly(),
                                                   'transcription' => $baseline->getTranscription(),
                                                   'boundary' => $baseline->getImageBoundary());
        $srfID = $baseline->getSurfaceID();
        if ($srfID && array_key_exists($srfID,$entities['update']['srf'])) {
          $entities['update']['bln'][$blnID]['surfaceID'] = $srfID;
          if ($entities['update']['srf'][$srfID]['textIDs']) {
            $entities['update']['bln'][$blnID]['textIDs'] = $entities['update']['srf'][$srfID]['textIDs'];
            foreach ($entities['update']['bln'][$blnID]['textIDs'] as $blnTxtID) {
              array_push($entities['update']['txt'][$blnTxtID]['blnIDs'],$blnID);
            }
          }
        }
        if ($url) {
          $info = pathinfo($url);
          $thumbUrl = $info['dirname']."/th".$info['basename'];
          $entities['update']['bln'][$blnID]['thumbUrl'] = $thumbUrl;
        }
        $bImgID = $baseline->getImageID();
        if ($bImgID) {
          $entities['update']['bln'][$blnID]['imageID'] = $bImgID;
          $imgIDs = array_merge($imgIDs,array($bImgID));
          if(!array_key_exists($bImgID,$img2blnIDs)){
            $img2blnIDs[$bImgID] = array($blnID);
          } else {
            $img2blnIDs[$bImgID] = array_unique(array_merge($img2blnIDs[$bImgID],array($blnID)));
          }
        }
        $AnoIDs = $baseline->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['update']['bln'][$blnID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $baseline->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['update']['bln'][$blnID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if blnID
    }// for baseline

    //find images for all entities with imageIDs
    $imgIDs = array_unique($imgIDs);
    $images = new Images("img_id in (".join(",",$imgIDs).") and not img_owner_id = 1",null,null,null);
    $images->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    foreach ($images as $image) {
      $imgID = $image->getID();
      if ($imgID && !array_key_exists($imgID, $entities['update']['img'])) {
        $title = $image->getTitle();
        $url = $image->getURL();
        $entities['update']['img'][$imgID] = array('title'=> $title,
                                                   'id' => $imgID,
                                                   'value'=> ($title?$title:substr($url,strrpos($url,'/')+1)),
                                                   'readonly' => $image->isReadonly(),
                                                   'editibility' => $image->getOwnerID(),
                                                   'url' => $url,
                                                   'type' => $image->getType(),
                                                   'boundary' => $image->getBoundary());
        if ($url) {
          $info = pathinfo($url);
          $thumbUrl = $info['dirname']."/th".$info['basename'];
          $entities['update']['img'][$imgID]['thumbUrl'] = $thumbUrl;
        }
        if (isset($img2blnIDs[$imgID])) {
          $entities['update']['img'][$imgID]['blnIDs'] = $img2blnIDs[$imgID];
        }
        $AnoIDs = $image->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['update']['img'][$imgID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $image->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['update']['img'][$imgID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if imgID
    }//for images

    //find textmetadata for all texts
    $textMetadatas = new TextMetadatas("tmd_text_id in (".join(",",$txtIDs).") and not tmd_owner_id = 1",null,null,null);
    $textMetadatas->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $tmdIDs = array();
    foreach ($textMetadatas as $textMetadata) {
      $tmdID = $textMetadata->getID();
      if ($tmdID && !array_key_exists($tmdID, $entities['update']['tmd'])) {
        array_push($tmdIDs,$tmdID);
        $entities['update']['tmd'][$tmdID] = array( 'txtID'=> $textMetadata->getTextID(),
                               'id' => $tmdID,
                               'ednIDs' => array(),
                               'readonly' => $textMetadata->isReadonly(),
                               'editibility' => $textMetadata->getOwnerID(),
                               'typeIDs' => $textMetadata->getTypeIDs());
        array_push($entities['update']['txt'][$textMetadata->getTextID()]['tmdIDs'],$tmdID);
        $tmRefIDs =$textMetadata->getReferenceIDs();
        if ($tmRefIDs && count($tmRefIDs) > 0) {
          $entities['update']['tmd'][$tmdID]['refIDs'] = $tmRefIDs;
          $atbIDs = array_merge($atbIDs,$tmRefIDs);
        }
        $AnoIDs = $textMetadata->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['update']['tmd'][$tmdID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $textMetadata->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['update']['tmd'][$tmdID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }//if tmdID
    } // for textMetadata

    //find editions for all textmetadatas
    $editions = new Editions("edn_text_id in (".join(",",$txtIDs).") and not edn_owner_id = 1",null,null,null);
    $editions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    foreach ($editions as $edition) {
      if ($edition->isMarkedDelete()) {
        continue;
      }
      $ednID = $edition->getID();
      if ($ednID && !array_key_exists($ednID, $entities['update']['edn'])) {
        $entities['update']['edn'][$ednID] = array('description'=> $edition->getDescription(),
                                                   'id' => $ednID,
                                                   'value'=> $edition->getDescription(),
                                                   'readonly' => $edition->isReadonly(),
                                                   'editibility' => $edition->getOwnerID(),
                                                   'typeID' => $edition->getTypeID(),
                                                   'txtID' => $edition->getTextID(),
                                                   'seqIDs' => $edition->getSequenceIDs());
        $catalogs = new Catalogs($ednID." = ANY (\"cat_edition_ids\") AND cat_type_id != $dictionaryCatalogTypeID",null,null,null);
        $catalogs->setAutoAdvance(false); // make sure the iterator doesn't prefetch
        $catIDs = array();
        foreach ($catalogs as $catalog) {
          $catID = $catalog->getID();
          if ($catID && !array_key_exists($catID, $entities['update']['cat'])) {
            $entities['update']['cat'][$catID] = array('description'=> $catalog->getDescription(),
                                                       'id' => $catID,
                                                       'value'=> $catalog->getTitle(),
                                                       'readonly' => $catalog->isReadonly(),
                                                       'editibility' => $catalog->getOwnerID(),
                                                       'ednIDs' => $catalog->getEditionIDs(),
                                                       'typeID' => $catalog->getTypeID());
          }
          array_push($catIDs,$catID);
          $AnoIDs = $catalog->getAnnotationIDs();
          if ($AnoIDs && count($AnoIDs) > 0) {
            $entities['update']['cat'][$catID]['annotationIDs'] = $AnoIDs;
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $AtbIDs = $catalog->getAttributionIDs();
          if ($AtbIDs && count($AtbIDs) > 0) {
            $entities['update']['cat'][$catID]['attributionIDs'] = $AtbIDs;
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
        }
        if ($catIDs && count($catIDs)) {
          $entities['update']['edn'][$ednID]['catIDs'] = $catIDs;
        }
        if (!in_array($ednID,$entities['update']['txt'][$edition->getTextID()]['ednIDs'])) {
          array_push($entities['update']['txt'][$edition->getTextID()]['ednIDs'],$ednID);
        }
        $AnoIDs = $edition->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities['update']['edn'][$ednID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $edition->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities['update']['edn'][$ednID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    } // for editions

    $entityIDs = array();
    if ($atbIDs && count( $atbIDs) > 0) {
      $entityIDs['atb'] = $atbIDs;
    }
    if ($anoIDs && count( $anoIDs) > 0) {
      $entityIDs['ano'] = $anoIDs;
    }
    if ($entityIDs && count( $entityIDs) > 0) {
      getRelatedEntities($entityIDs);
    }
    // strip away empty entityType arrays
    foreach ($entities['update']['txt'] as $txtID => $propsArray) {
      if (count($propsArray['tmdIDs']) == 0) {
        unset($entities['update']['txt'][$txtID]['tmdIDs']);
      }
      if (count($propsArray['imageIDs']) == 0) {
        unset($entities['update']['txt'][$txtID]['imageIDs']);
      }
      if (count($propsArray['blnIDs']) == 0) {
        unset($entities['update']['txt'][$txtID]['blnIDs']);
      }
      if (count($propsArray['ednIDs']) == 0) {
        unset($entities['update']['txt'][$txtID]['ednIDs']);
      }
      if (count($propsArray) == 0) {
        unset($entities['update']['txt'][$txtID]);
      }
    }
    foreach ($entities['update'] as $prefix => $entityArray) {
      if ( count($entityArray) == 0) {
        unset($entities['update'][$prefix]);
      }
    }
//    $retVal["success"] = false;
//    if (count($errors)) {
//      $retVal["errors"] = $errors;
//    } else {
//      $retVal["success"] = true;
//    }
    if (count($warnings)) {
      $retVal["warnings"] = $warnings;
    }
    if (count($entities)) {
      $retVal["entities"] = $entities;
    }
    $jsonRetVal = json_encode($retVal);
    if (USECACHE && ($isLoadAll || $updateCache)) {
      if (!$jsonCache) {
        $dbMgr->query("SELECT * FROM jsoncache WHERE jsc_label = 'AllTextResources'");
        if ($dbMgr->getRowCount() > 0 ) {
          $row = $dbMgr->fetchResultRow();
          $jsonCache = new JsonCache($row);
        } else {
          $jsonCache = new JsonCache();
          $jsonCache->setLabel('AllTextResources');
          $jsonCache->setVisibilityIDs((defined("DEFAULTCACHEVISID") && DEFAULTCACHEVISID)?array(DEFAULTCACHEVISID):array(6));
          $jsonCache->setOwnerID((defined("DEFAULTCACHEOWNERID") && DEFAULTCACHEOWNERID)?DEFAULTCACHEOWNERID:6);
        }
      }
      if ($isLoadAll) {
        $jsonCache->setJsonString($jsonRetVal);
        $jsonCache->clearDirtyBit();
        $jsonCache->save();
      } else if ($updateCache && (count($entities['insert']) > 0 || count($entities['update']) > 0 )) {
        $curCache =  $jsonCache->getJsonString();
        if ($curCache) {
          $curCache = json_decode($curCache,true);
          if (count($entities['insert']) > 0) {
            if (!array_key_exists('insert',$curCache['entities'])) {
              $curCache['entities']['insert'] = $entities['insert'];
            } else {
              foreach ($entities['insert'] as $prefix => $id2properties) {
                if (!array_key_exists($prefix,$curCache['entities']['insert'])) {
                  $curCache['entities']['insert'][$prefix] = $id2properties;
                } else {
                  foreach ($id2properties as $id => $properties) {
                    $curCache['entities']['insert'][$prefix][$id] = $properties;
                  }
                }
              }
            }
          }
          if (count($entities['update']) > 0) {
            if (!array_key_exists('update',$curCache['entities'])) {
              $curCache['entities']['update'] = $entities['update'];
            } else {
              foreach ($entities['update'] as $prefix => $id2properties) {
                if (!array_key_exists($prefix,$curCache['entities']['update'])) {
                  $curCache['entities']['update'][$prefix] = $id2properties;
                } else {
                  foreach ($id2properties as $id => $properties) {
                    $curCache['entities']['update'][$prefix][$id] = $properties;
                  }
                }
              }
            }
          }
          $jsonCache->setJsonString(json_encode($curCache));
          $jsonCache->clearDirtyBit();
          $jsonCache->save();
        }
      }
    }
  }
  if (!$jsonRetVal){
    $jsonRetVal = json_encode(array("success"=>true));
  }
  ob_clean();
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".$jsonRetVal.");";
    }
  } else {
    print $jsonRetVal;
  }

  function getRelatedEntities($entityIDs) {
    global $entities,$anoIDs,$atbIDs,$publicOnly;
    static $prefixProcessOrder = array('atb','ano');//important attr before anno to terminate
    if (!$entityIDs || count($entityIDs) == 0) return '""';
    //collect entities by type
    while (count(array_keys($entityIDs)) > 0) {
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
      if ($tempIDs && count($tempIDs) > 0 && !array_key_exists($prefix,$entities["insert"])) {
        $entities["insert"][$prefix] = array();
      }
      $entIDs = array();
      foreach ($tempIDs as $entID){//skip any already processed
        if ($entID && !array_key_exists($entID,$entities["insert"][$prefix])) {
          array_push($entIDs,$entID);
        }
      }
      unset($entityIDs[$prefix]);//we have captured the ids of this entity type remove them so we progress in the recursive call
      if ($entIDs && count($entIDs) > 0) {
        switch ($prefix) {
          case 'atb':
            $attributions = new Attributions("atb_id in (".join(",",$entIDs).")",null,null,null);
            $attributions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($attributions as $attribution) {
              $atbID = $attribution->getID();
              if ($atbID && !array_key_exists($atbID, $entities["insert"]['atb'])) {
                $entities["insert"]['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
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
                  $entities["insert"]['atb'][$atbID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
              }
            }
            break;
          case 'ano':
            $annotations = new Annotations("ano_id in (".join(",",$entIDs).")",null,null,null);
            $annotations->setAutoAdvance(false); // make sure the iterator doesn't prefetch
            foreach ($annotations as $annotation) {
              $anoID = $annotation->getID();
              if ($anoID && !array_key_exists($anoID, $entities["insert"]['ano'])) {
                $entities["insert"]['ano'][$anoID] = array( 'text'=> $annotation->getText(),
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
                $entities["insert"]['ano'][$anoID]['vis'] = $vis;
                $AnoIDs = $annotation->getAnnotationIDs();
                if ($AnoIDs && count($AnoIDs) > 0) {
                  $entities["insert"]['ano'][$anoID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $annotation->getAttributionIDs();
                if ($AtbIDs && count($AtbIDs) > 0) {
                  $entities["insert"]['ano'][$anoID]['attributionIDs'] = $AtbIDs;
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
    }// end while
  }

?>
