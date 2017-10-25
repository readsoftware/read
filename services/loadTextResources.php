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
  ob_start('ob_gzhandler');

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  if (@$argv) {
    // handle command-line queries
    $cmdParams = array();
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
    if(@$cmdParams['-db']) $_REQUEST["db"] = $cmdParams['-db'];
    if (@$cmdParams['-ids']) $_REQUEST['ids'] = $cmdParams['-ids'];
    //commandline access for setting userid  TODO review after migration
    if (@$cmdParams['-uid'] && !isset($_SESSION['ka_userid'])) $_SESSION['ka_userid'] = $cmdParams['-uid'];
  }

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
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
  $errors = array();
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
//  $entities["update"] = array( 'txt' => array());
  $txtIDs = (array_key_exists('ids',$_REQUEST)? $_REQUEST['ids']:null);
  if (!$txtIDs && isset($_SESSION['ka_searchAllResults_'.DBNAME])) {
    //decode the cached text
    $textSearchAllRetVal = json_decode($_SESSION['ka_searchAllResults_'.DBNAME],true);
    if (isset($textSearchAllRetVal['entities']) &&
        isset($textSearchAllRetVal['entities']['insert'])&&//depends on loadTextSearchEntities using "insert"
        isset($textSearchAllRetVal['entities']['insert']['txt'])){
      $txtIDs = array_keys($textSearchAllRetVal['entities']['insert']['txt']);
    }
  }

  if ($txtIDs && count($txtIDs) > 0) {
    $imgIDs = array();
    $anoIDs = array();
    $atbIDs = array();
    if (is_string($txtIDs)){
      $txtIDs = explode(',',$txtIDs);
    }
    $termInfo = getTermInfoForLangCode('en');
    $dictionaryCatalogTypeID = $termInfo['idByTerm_ParentLabel']['dictionary-catalogtype'];//term dependency

    foreach ($txtIDs as $txtID){
      if ($txtID && !array_key_exists($txtID, $entities['update']['txt'])) {//skip duplicates if any
        $entities['update']['txt'][$txtID] = array('tmdIDs' => array(),
                                                   'ednIDs' => array(),
                                                   'blnIDs' => array());
        $text = new Text($txtID);
        $txtImgIDs = $text->getImageIDs();
        if ($txtImgIDs && count($txtImgIDs) > 0) {
          $imgIDs = array_unique(array_merge($imgIDs,$txtImgIDs));
        }
      }
    } //for txtIDs
    $txtIDs = array_keys($entities['update']['txt']);
    $strTxtIDs = join(",",$txtIDs);
    //find surfaces for all texts
    $surfaces = new Surfaces("srf_text_ids && ARRAY[".$strTxtIDs."]",null,null,null);
    $surfaces->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $srfIDs = array();
    foreach ($surfaces as $surface) {
      $srfID = $surface->getID();
      if ($srfID && !array_key_exists($srfID, $entities['update']['srf'])) {
        $frgID =  $surface->getFragmentID();
        $entities['update']['srf'][$srfID] = array('fragmentID'=>$frgID,
                                         'id' => $srfID,
                                         'number' => $surface->getNumber(),
                                         'value' => $surface->getDescription(),
                                         'description' => $surface->getDescription(),
                                         'layer' => $surface->getLayerNumber(),
                                         'textIDs' => $surface->getTextIDs());
        array_push($srfIDs,$srfID);
        $sImgIDs = $surface->getImageIDs();
        if (count($sImgIDs) > 0) {
          $entities['update']['srf'][$srfID]['imageIDs'] = $sImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$sImgIDs));
        }
        $AnoIDs = $surface->getAnnotationIDs();
        if (count($AnoIDs) > 0) {
          $entities['update']['srf'][$srfID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $surface->getAttributionIDs();
        if (count($AtbIDs) > 0) {
          $entities['update']['srf'][$srfID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    }

    $srfIDs = array_unique($srfIDs);
    //find all baselines for all surfaces
    $baselines = new Baselines("bln_surface_id in (".join(",",$srfIDs).")",null,null,null);
    $baselines->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    $blnIDs = array();
    foreach ($baselines as $baseline) {
      $blnID = $baseline->getID();
      if ($blnID && !array_key_exists($blnID, $entities['update']['bln'])) {
        $url = $baseline->getURL();
        $entities['update']['bln'][$blnID] = array('url' => $url,
                                                   'id' => $blnID,
                                                   'type' => $baseline->getType(),
                                                   'value' => ($url?$url:$baseline->getTranscription()),
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
          $imgIDs = array_unique(array_merge($imgIDs,array($bImgID)));
        }
        $AnoIDs = $baseline->getAnnotationIDs();
        if (count($AnoIDs) > 0) {
          $entities['update']['bln'][$blnID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $baseline->getAttributionIDs();
        if (count($AtbIDs) > 0) {
          $entities['update']['bln'][$blnID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if blnID
    }// for baseline

    //find images for all entities with imageIDs
    $images = new Images("img_id in (".join(",",$imgIDs).")",null,null,null);
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
                                                   'url' => $url,
                                                   'type' => $image->getType(),
                                                   'boundary' => $image->getBoundary());
        if ($url) {
          $info = pathinfo($url);
          $thumbUrl = $info['dirname']."/th".$info['basename'];
          $entities['update']['img'][$imgID]['thumbUrl'] = $thumbUrl;
        }
        $AnoIDs = $image->getAnnotationIDs();
        if (count($AnoIDs) > 0) {
          $entities['update']['img'][$imgID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $image->getAttributionIDs();
        if (count($AtbIDs) > 0) {
          $entities['update']['img'][$imgID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }// if imgID
    }//for images

    //find textmetadata for all texts
    $textMetadatas = new TextMetadatas("tmd_text_id in (".join(",",$txtIDs).")",null,null,null);
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
                               'typeIDs' => $textMetadata->getTypeIDs());
        array_push($entities['update']['txt'][$textMetadata->getTextID()]['tmdIDs'],$tmdID);
        $tmRefIDs =$textMetadata->getReferenceIDs();
        if (count($tmRefIDs) > 0) {
          $entities['update']['tmd'][$tmdID]['refIDs'] = $tmRefIDs;
          $atbIDs = array_merge($atbIDs,$tmRefIDs);
        }
        $AnoIDs = $textMetadata->getAnnotationIDs();
        if (count($AnoIDs) > 0) {
          $entities['update']['tmd'][$tmdID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $textMetadata->getAttributionIDs();
        if (count($AtbIDs) > 0) {
          $entities['update']['tmd'][$tmdID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }//if tmdID
    } // for textMetadata

    //find editions for all textmetadatas
    $editions = new Editions("edn_text_id in (".join(",",$txtIDs).")",null,null,null);
    $editions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
    foreach ($editions as $edition) {
      $ednID = $edition->getID();
      if ($ednID && !array_key_exists($ednID, $entities['update']['edn'])) {
        $entities['update']['edn'][$ednID] = array('description'=> $edition->getDescription(),
                                                   'id' => $ednID,
                                                   'value'=> $edition->getDescription(),
                                                   'readonly' => $edition->isReadonly(),
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
                                                       'ednIDs' => $catalog->getEditionIDs(),
                                                       'typeID' => $catalog->getTypeID());
          }
          array_push($catIDs,$catID);
          $AnoIDs = $catalog->getAnnotationIDs();
          if (count($AnoIDs) > 0) {
            $entities['update']['cat'][$catID]['annotationIDs'] = $AnoIDs;
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $AtbIDs = $catalog->getAttributionIDs();
          if (count($AtbIDs) > 0) {
            $entities['update']['cat'][$catID]['attributionIDs'] = $AtbIDs;
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
        }
        if (count($catIDs)) {
          $entities['update']['edn'][$ednID]['catIDs'] = $catIDs;
        }
        array_push($entities['update']['txt'][$edition->getTextID()]['ednIDs'],$ednID);
        $AnoIDs = $edition->getAnnotationIDs();
        if (count($AnoIDs) > 0) {
          $entities['update']['edn'][$ednID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $edition->getAttributionIDs();
        if (count($AtbIDs) > 0) {
          $entities['update']['edn'][$ednID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      }
    } // for editions

    $entityIDs = array();
    if (count( $atbIDs) > 0) {
      $entityIDs['atb'] = $atbIDs;
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
  }

  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  if (count($entities)) {
    $retVal["entities"] = $entities;
  }
  $jsonRetVal = json_encode($retVal);
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".$jsonRetVal.");";
    }
  } else {
    print $jsonRetVal;
  }

  function getRelatedEntities($entityIDs) {
    global $entities,$anoIDs,$atbIDs,$errors,$warnings,$publicOnly;
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
      if (count($tempIDs) > 0 && !array_key_exists($prefix,$entities["insert"])) {
        $entities["insert"][$prefix] = array();
      }
      $entIDs = array();
      foreach ($tempIDs as $entID){//skip any already processed
        if ($entID && !array_key_exists($entID,$entities["insert"][$prefix])) {
          array_push($entIDs,$entID);
        }
      }
      unset($entityIDs[$prefix]);//we have captured the ids of this entity type remove them so we progress in the recursive call
      if (count($entIDs) > 0) {
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
                if (count($AnoIDs) > 0) {
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
                if (count($AnoIDs) > 0) {
                  $entities["insert"]['ano'][$anoID]['annotationIDs'] = $AnoIDs;
                  if (!array_key_exists('ano',$entityIDs)) {
                    $entityIDs['ano'] = array();
                  }
                  $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
                }
                $AtbIDs = $annotation->getAttributionIDs();
                if (count($AtbIDs) > 0) {
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
