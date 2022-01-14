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

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Texts.php';
  require_once dirname(__FILE__) . '/../model/entities/Surfaces.php';
  require_once dirname(__FILE__) . '/../model/entities/Baselines.php';
  require_once dirname(__FILE__) . '/../model/entities/Segments.php';
  require_once dirname(__FILE__) . '/../model/entities/Spans.php';
  require_once dirname(__FILE__) . '/../model/entities/Lines.php';
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;

  $dbMgr = new DBManager();
  $retVal = array();
  $cknLookup = array();
  $entities = array( 'txt' => array(),
                     'tmd' => array(),
                     'edn' => array(),
                     'seq' => array(),
                     'cmp' => array(),
                     'lem' => array(),
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
                     'ano' => array(),
                     'atb' => array());
  $textCKNs = (array_key_exists('ckn',$_REQUEST)? $_REQUEST['ckn']:null);
  $termIDToEnLabel = array();
  $dbMgr->query("SELECT trm_id, trm_labels::hstore->'en' as trm_label, trm_code, trm_parent_id FROM term;");
  while($row = $dbMgr->fetchResultRow()){
    $termIDToEnLabel[$row['trm_id']] = $row['trm_label'];
  }
  if (is_array($textCKNs)) {// multiple text
    $condition = "txt_ckn in ('".strtoupper(join("','",$textCKNs))."') ";
  }else if (is_string($textCKNs)) {
     $condition = "";
     if (strpos($textCKNs,",")) {
      $condition = "txt_ckn in (".strtoupper($textCKNs).") ";
     }else if (substr(strtoupper($textCKNs),0,2) =="CK") {
      $condition = "txt_ckn = '".strtoupper($textCKNs)."' ";
     }
  }else{
    $condition = "";
  }
  $texts = new Texts($condition,'txt_ckn',null,null);
  foreach ($texts as $text){
    if (count($text->getReplacementIDs(true))) continue; // skip forwarding references
    $txtID = $text->getID();
    $ckn = $text->getCKN();
    if ($txtID && !array_key_exists($txtID, $entities['txt'])) {
      $entities['txt'][$txtID] = array( 'CKN' => $ckn,
                               'id' => $txtID,
                               'tmdIDs' => array(),
                               'ednIDs' => array(),
                               'blnIDs' => array(),
                               'value' => $text->getTitle(),
                               'ref' => $text->getRef(),
                               'readonly' => $text->isReadonly(),
                               'editibility' => $text->getOwnerID(),
                               'title' => $text->getTitle());
      $cknLookup[$ckn] = $txtID;
      foreach ($text->getSurfaces(true) as $surface) {
        $srfID = $surface->getID();
        if ($srfID && !array_key_exists($srfID, $entities['srf'])) {
          $entities['srf'][$srfID] = array( 'fragmentID'=> $surface->getFragmentID(),
                                            'number' => $surface->getNumber(),
                                            'label' => $surface->getLabel(),
                                            'value' => $surface->getDescription(),
                                            'description' => $surface->getDescription(),
                                            'layer' => $surface->getLayerNumber(),
                                            'txtIDs' => $surface->getTextIDs(),
                                            'imageIDs' => $surface->getImageIDs());
                      if (count($surface->getImageIDs()) > 0) {
            foreach ($surface->getImages(true) as $image) {
                $imgID = $image->getID();
                if ($imgID && !array_key_exists($imgID, $entities['img'])) {
                  $entities['img'][$imgID] = array( 'title'=> $image->getTitle(),
                                         'txtID' => $txtID,
                                         'srfID' => $srfID,
                                         'url' => $image->getURL(),
                                         'editibility' => $image->getOwnerID(),
                                         'type' => $image->getType(),
                                         'boundary' => $image->getBoundary());
                }// if imgID
            }//for images
          }// if count
          $fragment = $surface->getFragment(true);
          $frgID = $fragment->getID();
          if ($frgID && !array_key_exists($frgID, $entities['frg'])) {
            $entities['frg'][$frgID] = array( 'itemTitle'=> $fragment->getPart(true)->getItem(true)->getTitle(),
                                   'label' => $fragment->getLabel(),
                                   'description' => $fragment->getDescription(),
                                   'locRef' => $fragment->getLocationRef(),
                                   'imageIDs' => $fragment->getImageIDs());
          }
          foreach ($surface->getBaselines() as $baseline) {
            $blnID = $baseline->getID();
            array_push($entities['txt'][$txtID]['blnIDs'],$blnID);
            if ($blnID && !array_key_exists($blnID, $entities['bln'])) {
              $segIDs = $baseline->getSegIDs();
              $entities['bln'][$blnID] = array( 'surfaceID'=> $srfID,
                                     'txtID' => $txtID,
                                     'url' => $baseline->getURL(),
                                     'type' => $baseline->getType(),
                                     'segCount' => count($segIDs),
                                     'segIDs' => $segIDs,
                                     'transcription' => $baseline->getTranscription(),
                                     'editibility' => $baseline->getOwnerID(),
                                     'boundary' => $baseline->getImageBoundary(),
                                     'imageID' => $baseline->getImageID());
            }// if blnID
            foreach ($baseline->getSegments(true) as $segment) {
              $segID = $segment->getID();
//              array_push($entities['bln'][$blnID]['segIDs'],$segID);
              if ($segID && !array_key_exists($segID, $entities['seg'])) {
                $entities['seg'][$segID] = array( 'surfaceID'=> $srfID,
                                        'baselineIDs' => $segment->getBaselineIDs(),
                                        'layer' => $segment->getLayer(),
                                        'id' => $segID,
                                        'readonly' => $segment->isReadonly(),
                                        'editibility' => $segment->getOwnerID(),
                                        'center' => $segment->getCenter(),
                                        'value' => 'seg'.$segID);
                $boundary = $segment->getImageBoundary();
                if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
                  $boundary = $boundary[0];
                  $entities['seg'][$segID]['boundary']= array($boundary->getPoints());
                  $entities['seg'][$segID]['urls']= $segment->getURLs();
                }
                $mappedSegIDs = $segment->getMappedSegmentIDs();
                if (count($mappedSegIDs) > 0) {
                  $entities['seg'][$segID]['mappedSegIDs'] = $mappedSegIDs;
                }
                $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
                if ($segBlnOrder) {
                  $entities['seg'][$segID]['ordinal'] = $segBlnOrder;
                }
                $stringpos = $segment->getStringPos();
                if ($stringpos && count($stringpos) > 0) {
                  $entities['seg'][$segID]['stringpos']= $stringpos;
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
                foreach ($segment->getSpans(true) as $span) {
                  $spnID = $span->getID();
                  if ($spnID && !array_key_exists($spnID, $entities['spn'])) {
                    $spnSegIDs = $span->getSegmentIDs();
                    $entities['spn'][$spnID] = array( 'segIDs'=> $spnSegIDs,
                                           'baselineID' => $blnID);
                    foreach ($span->getLines(true) as $line) {
                      $linID = $line->getID();
                      if ($linID && !array_key_exists($linID, $entities['lin'])) {
                        $linSpnIDs = $line->getSpanIDs();
                        $entities['lin'][$linID] = array( 'spnIDs'=> $linSpnIDs,
                                               'txtID'=> $txtID,
                                               'order' => $line->getOrder(),
                                               'mask' => $line->getMask());
                      }// if linID
                      if ($spnID == $linSpnIDs[count($linSpnIDs)-1]) {//last span to find last segID for line break
                        $entities['lin'][$linID]['brSegID'] = $spnSegIDs[count($spnSegIDs)-1];
                      }
                    } // for line
                  }// if spnID
                } // for span
              }// if segID
            } // for segment
          } // for baseline
        } // if srfID
      } // for surface
      foreach ($text->getTextMetadatas(true) as $textMetadata) {
        $tmdID = $textMetadata->getID();
        if ($tmdID && !array_key_exists($tmdID, $entities['tmd'])) {
          $entities['tmd'][$tmdID] = array( 'txtID'=> $textMetadata->getTextID(),
                                 'refIDs' => $textMetadata->getReferenceIDs(),
                                 'ednIDs' => array(),
                                 'editibility' => $textMetadata->getOwnerID(),
                                 'attrIDs' => $textMetadata->getAttributionIDs(),
                                 'typeIDs' => $textMetadata->getTypeIDs());
          if (count($textMetadata->getReferenceIDs()) > 0) {
            foreach ($textMetadata->getReferences(true) as $attribution) {
              getRelatedEntities($attribution);
            }
          }
          if (count($textMetadata->getAttributionIDs()) > 0) {
            foreach ($textMetadata->getAttributions(true) as $attribution) {
              getRelatedEntities($attribution);
            }
          }
          array_push($entities['txt'][$txtID]['tmdIDs'],$tmdID);
        }//if tmdID
      } // for textMetadata
      foreach ($text->getEditions() as $edition) {
        $ednID = $edition->getID();
        if ($ednID && !array_key_exists($ednID, $entities['edn'])) {
          $entities['edn'][$ednID] = array( 'description'=> $edition->getDescription(),
                                 'attrIDs' => $edition->getAttributionIDs(),
                                 'id' => $ednID,
                                 'value'=> $edition->getDescription(),
                                 'typeID' => $edition->getTypeID(),
                                 'editibility' => $edition->getOwnerID(),
                                 'txtID' => $edition->getTextID(),
                                 'seqIDs' => $edition->getSequenceIDs());
          array_push($entities['txt'][$txtID]['ednIDs'],$ednID);
        }
        if (count($edition->getAttributionIDs()) > 0) {
          foreach ($edition->getAttributions(true) as $attribution) {
            getRelatedEntities($attribution);
          }
        }
        if (count($edition->getSequenceIDs()) > 0) {
          foreach ($edition->getSequences(true) as $sequence) {
            getRelatedEntities($sequence);
          } // for sequence
        }
      } // for editions
    } // if txtID
  } //for text
  $retVal = array("entities" => $entities,
                  "termIDToLabel" => $termIDToEnLabel,
                  "cknToTextID" => $cknLookup);
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }

  function getRelatedEntities($entity) {
    global $entities;
    if (!$entity) return;
    $prefix = $entity->getEntityTypeCode();
    $entID = $entity->getID();
    if ($entID && !array_key_exists($entID,$entities[$prefix])) {
      switch ($prefix) {
        case 'seq':
          $entities['seq'][$entID] = array( 'label'=> $entity->getLabel(),
                                 'attrIDs' => $entity->getAttributionIDs(),
                                 'superscript' => $entity->getSuperScript(),
                                 'editibility' => $entity->getOwnerID(),
                                 'typeID' => $entity->getTypeID(),
                                 'entityIDs' => $entity->getEntityIDs());
          if (count($entity-getEntityIDs())) {
            foreach ($entity->getEntities(true) as $subEntity) {
              getRelatedEntities($subEntity);
            }
          }
          break;
        case 'cmp':
          $entities['cmp'][$entID] = array( 'value'=> $entity->getValue(),
                                 'transcr' => $entity->getTranscription(),
                                 'attrIDs' => $entity->getAttributionIDs(),
                                 'case' => $entity->getCase(),
                                 'class' => $entity->getClass(),
                                 'type' => $entity->getType(),
                                 'tokenIDs' => array(),
                                 'sort' => $entity->getSortCode(),
                                 'sort2' => $entity->getSortCode2(),
                                 'entityIDs' => $entity->getComponentIDs());
          foreach ($entity->getComponents(true) as $subEntity) {
            getRelatedEntities($subEntity);
          }
          foreach ($entity->getTokens() as $token) {
            array_push($entities['cmp'][$entID]['tokenIDs'], $token->getID());
          }
          break;
        case 'tok':
          $entities['tok'][$entID] = array( 'value'=> $entity->getValue(),
                                 'transcr' => $entity->getTranscription(),
                                 'attrIDs' => $entity->getAttributionIDs(),
                                 'affix' => $entity->getNominalAffix(),
                                 'sort' => $entity->getSortCode(),
                                 'sort2' => $entity->getSortCode2(),
                                 'graphemeIDs' => $entity->getGraphemeIDs(),
                                 'syllableClusterIDs' => array());
          foreach ($entity->getGraphemes(true) as $grapheme) {
            getRelatedEntities($grapheme);
            foreach ($grapheme->getSyllableClusters() as $sylCluster) {
             $entities['tok'][$entID]['syllableClusterIDs'][$sylCluster->getID()]=1;
            }
          }
          $entities['tok'][$entID]['syllableClusterIDs'] = array_keys($entities['tok'][$entID]['syllableClusterIDs']);
          break;
        case 'gra':
          $entities['gra'][$entID] = array( 'value'=> $entity->getValue(),
                                 'txtcrit' => $entity->getTextCriticalMark(),
                                 'attrIDs' => $entity->getAttributionIDs(),
                                 'alt' => $entity->getAlternative(),
                                 'emmend' => $entity->getEmmendation(),
                                 'type' => $entity->getType(),
                                 'sort' => $entity->getSortCode());
          if ($entity->getDecomposition()) {
             $entities['gra'][$graID]['decomp'] = preg_replace("/:/",'',$entity->getDecomposition());
          }
          foreach ($entity->getSyllableClusters() as $subEntity) {
            getRelatedEntities($subEntity);
          }
          break;
        case 'scl':
          $entities['scl'][$entID] = array( 'value'=> $syllable->getValue(),
                                  'segID'=> $entity->getSegmentID(),
                                 'txtcrit' => $entity->getTextCriticalMark(),
                                 'attrIDs' => $entity->getAttributionIDs(),
                                 'sort' => $entity->getSortCode(),
                                 'sort2' => $entity->getSortCode2(),
                                 'graphemeIDs' => $entity->getGraphemeIDs());
          getRelatedEntities($entity->getSegment(true));
//         foreach ($entity->getGraphemes(true) as $subEntity) {
//            getRelatedEntities($subEntity);
//          }
          break;
        case 'seg':
          $segment = $entity;
          $entities['seg'][$entID] = array( 'baselineIDs' => $entity->getBaselineIDs(),
              'id' => $entID,
              'baselineIDs' => $segment->getBaselineIDs(),
              'layer' => $segment->getLayer(),
              'readonly' => $segment->isReadonly(),
              'editibility' => $segment->getOwnerID(),
              'center' => $segment->getCenter(),
              'value' => 'seg'.$entID);
          $sclIDs = $segment->getSyllableIDs();
          if ($sclIDs && count($sclIDs) > 0) {
            $entities['seg'][$entID]['sclIDs']= $sclIDs;
          }
          $boundary = $segment->getImageBoundary();
          if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
            $entities['seg'][$entID]['boundary']= array();
            foreach($boundary as $polygon) {
              array_push($entities['seg'][$entID]['boundary'], $polygon->getPoints());
            }
            $entities['seg'][$entID]['urls']= $segment->getURLs();
          }
          $stringpos = $segment->getStringPos();
          if ($stringpos && count($stringpos) > 0) {
            $entities['seg'][$entID]['stringpos']= $stringpos;
          }
          $mappedSegIDs = $segment->getMappedSegmentIDs();
          if (count($mappedSegIDs) > 0) {
            $entities['seg'][$entID]['mappedSegIDs'] = $mappedSegIDs;
          }
          $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
          if ($segBlnOrder) {
            $entities['seg'][$entID]['ordinal'] = $segBlnOrder;
          }
          $segCode = $segment->getScratchProperty("sgnCode");
          if ($segCode) {
            $entities['seg'][$entID]['code'] = $segCode;
            $entities['seg'][$entID]['value'] = $segCode;
          }
          $segCatCode = $segment->getScratchProperty("sgnCatCode");
          if ($segCatCode) {
            $entities['seg'][$entID]['pcat'] = $segCatCode;
    //        $entities['seg'][$entID]['value'] = $segCatCode;
          }
          $segLoc = $segment->getScratchProperty("sgnLoc");
          if ($segLoc) {
            $entities['seg'][$entID]['loc'] = $segLoc;
          }
          break;
        case 'atb':
         $entities['atb'][$entID] = array( 'title'=> $entity->getTitle(),
                                 'grpID' => $entity->getGroupID(),
                                 'description' => $entity->getDescription(),
                                 'detail' => $entity->getDetail(),
                                 'types' => $entity->getTypes());
          break;
        case 'ano':
          break;
        case 'lem':
          $entities['lem'][$entID] = array( 'value'=> $entity->getLemma(),
                                 'type' => $entity->getType(),
                                 'trans' => $entity->getTranslation(),
                                 'order' => $entity->getHomographicOrder(),
                                 'attrIDs' => $entity->getAttributionIDs(),
                                 'pos' => $entity->getPartOfSpeech(),
                                 'spos' => $lemma->getSubpartOfSpeech(),
                                 'decl' => $entity->getDeclension(),
                                 'gender' => $entity->getGender(),
                                 'class' => $entity->getVerbalClass());
          break;
      }
      if (count($entity->getAttributionIDs()) > 0) {
        foreach ($entity->getAttributions(true) as $attribution) {
          getRelatedEntities($attribution);
        }
      }
    }//if key exist
  }
?>
