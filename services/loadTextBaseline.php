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
  * loadTextBaseline
  *
  *  A service that returns a json structure of the baseline requested along with its
  *  fragments, surfaces, baselines and segments need to drive the image editor.
  *
  *  There is NO CACHING for this service, data is always fresh.
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
    if (@$cmdParams['-bln']) {
      $_REQUEST['bln'] = $cmdParams['-bln'];
      $cmdStr .= ' bln = '.$_REQUEST['bln'];
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
  require_once dirname(__FILE__) . '/../model/entities/Fragments.php';
  require_once dirname(__FILE__) . '/../model/entities/Annotations.php';
  require_once dirname(__FILE__) . '/../model/entities/Attributions.php';

  $dbMgr = new DBManager();
  $retVal = array();
  $imgIDs = array();
  $anoIDs = array();
  $atbIDs = array();
  $segIDs = array();
  $sImgIDs = array();
  $warnings = array();
  $errors = array();
  $entityIDs = array();
  $entities = array( 'insert' => array(),
                     'update' => array());
  $entities["insert"] = array( 'seg' => array(),
                               'srf' => array(),
                               'frg' => array(),
                               'ano' => array(),
                               'atb' => array());
  $entities["update"] = array( 'bln' => array(),
                               'img' => array());
  $blnID = (array_key_exists('blnID',$_REQUEST)? $_REQUEST['blnID']:(array_key_exists('bln',$_REQUEST)? $_REQUEST['bln']:null));
  $baseline = null;
  if ($blnID) {
    $baseline = new Baseline($blnID);
  }

  if (!$baseline || $baseline->hasError()) {//no baseline or unavailable so warn
    array_push($warnings,"Warning no baseline available for id $blnID .");
  } else {
    $blnID = $baseline->getID();
    $srfID = $baseline->getSurfaceID();
    if ($srfID) {
      $surface = $baseline->getSurface(true);
      if (!$surface || $surface->hasError()) {//no surface or unavailable so warn
        array_push($warnings,"Warning no surface available for baseline id $blnID .");
      } else {
        $entities["insert"]['srf'][$srfID] = array( 'fragmentID'=> $surface->getFragmentID(),
                                                    'id' => $srfID,
                                                    'number' => $surface->getNumber(),
                                                    'label' => $surface->getLabel(),
                                                    'value' => $surface->getDescription(),
                                                    'description' => $surface->getDescription(),
                                                    'layer' => $surface->getLayerNumber(),
                                                    'textIDs' => $surface->getTextIDs());
        $sImgIDs = $surface->getImageIDs();
        if ($sImgIDs && count($sImgIDs) > 0) {
          $entities["insert"]['srf'][$srfID]['imageIDs'] = $sImgIDs;
          $imgIDs = array_merge($imgIDs,$sImgIDs);
        }
        $AnoIDs = $surface->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities["insert"]['srf'][$srfID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $surface->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities["insert"]['srf'][$srfID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
        $frgID = $surface->getFragmentID();
        if ($frgID) {
          $fragment = $surface->getFragment(true);
          if (!$fragment || $fragment->hasError()) {//no surface or unavailable so warn
            array_push($warnings,"Warning no fragment available for surface id $srfID .");
          } else {
            $entities["insert"]['frg'][$frgID] = array( //'itemTitle'=> $fragment->getPart(true)->getItem(true)->getTitle(),
                                              'label' => $fragment->getLabel(),
                                              'description' => $fragment->getDescription(),
                                              'locRefs' => $fragment->getLocationRefs());
            $fImgIDs = $fragment->getImageIDs();
            if ($fImgIDs && count($fImgIDs) > 0) {
              $entities["insert"]['frg'][$frgID]['imageIDs'] = $fImgIDs;
              $imgIDs = array_merge($imgIDs,$fImgIDs);
            }
            $AnoIDs = $fragment->getAnnotationIDs();
            if ($AnoIDs && count($AnoIDs) > 0) {
              $entities["insert"]['frg'][$frgID]['annotationIDs'] = $AnoIDs;
              $anoIDs = array_merge($anoIDs,$AnoIDs);
            }
            $AtbIDs = $fragment->getAttributionIDs();
            if ($AtbIDs && count($AtbIDs) > 0) {
              $entities["insert"]['frg'][$frgID]['attributionIDs'] = $AtbIDs;
              $atbIDs = array_merge($atbIDs,$AtbIDs);
            }
          }
        }
      }
    }
    $segments = $baseline->getSegments(true);
    if ($segments && $segments->getCount() > 0) {
      foreach ($segments as $segment) {
        if ($segment->isMarkedDelete()) {
          continue;
        }
        $segID = $segment->getID();
        if ($segID && !array_key_exists($segID, $entities["insert"]['seg'])) {
          array_push($segIDs,$segID);
          $entities["insert"]['seg'][$segID] = array( 'surfaceID'=> $srfID,
                                            'id' => $segID,
                                            'baselineIDs' => $segment->getBaselineIDs(),
                                            'layer' => $segment->getLayer(),
                                            'readonly' => $segment->isReadonly(),
                                            'editibility' => $segment->getOwnerID(),
                                            'center' => $segment->getCenter(),
                                            'value' => 'seg'.$segID);
          $sclIDs = $segment->getSyllableIDs();
          if ($sclIDs && count($sclIDs) > 0) {
            $entities["insert"]['seg'][$segID]['sclIDs']= $sclIDs;
          }
          $boundary = $segment->getImageBoundary();
          if ($boundary && array_key_exists(0,$boundary) && method_exists($boundary[0],'getPoints')) {
            $entities["insert"]['seg'][$segID]['boundary']= array();
            foreach($boundary as $polygon) {
              array_push($entities["insert"]['seg'][$segID]['boundary'], $polygon->getPoints());
            }
            $entities["insert"]['seg'][$segID]['urls']= $segment->getURLs();
          }
          $mappedSegIDs = $segment->getMappedSegmentIDs();
          if ($mappedSegIDs && count($mappedSegIDs) > 0) {
            $entities["insert"]['seg'][$segID]['mappedSegIDs'] = $mappedSegIDs;
          }
          $segBlnOrder = $segment->getScratchProperty("blnOrdinal");
          if ($segBlnOrder) {
            $entities["insert"]['seg'][$segID]['ordinal'] = $segBlnOrder;
          }
          $segCode = $segment->getScratchProperty("sgnCode");
          if ($segCode) {
            $entities["insert"]['seg'][$segID]['code'] = $segCode;
            $entities["insert"]['seg'][$segID]['value'] = $segCode;
          }
          $segCatCode = $segment->getScratchProperty("sgnCatCode");
          if ($segCatCode) {
            $entities["insert"]['seg'][$segID]['pcat'] = $segCatCode;
          }
          $segLoc = $segment->getScratchProperty("sgnLoc");
          if ($segLoc) {
            $entities["insert"]['seg'][$segID]['loc'] = $segLoc;
            $entities["insert"]['seg'][$segID]['value'] = $segLoc;
          }
          $stringpos = $segment->getStringPos();
          if ($stringpos && count($stringpos) > 0) {
            $entities["insert"]['seg'][$segID]['stringpos']= $stringpos;
          }
          $AnoIDs = $segment->getAnnotationIDs();
          if ($AnoIDs && count($AnoIDs) > 0) {
            $entities["insert"]['seg'][$segID]['annotationIDs'] = $AnoIDs;
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $AtbIDs = $segment->getAttributionIDs();
          if ($AtbIDs && count($AtbIDs) > 0) {
            $entities["insert"]['seg'][$segID]['attributionIDs'] = $AtbIDs;
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
        }// if segID
      } // for segment
    }
  }

  $entities["update"]['bln'][$blnID]=array();
  if ($segIDs && count($segIDs) > 0) {
    $segIDs = array_unique($segIDs);
    $entities["update"]['bln'][$blnID]['isLinked'] = (count($segIDs)>0?1:0);
    $entities["update"]['bln'][$blnID]['segCount'] = count($segIDs);
    $entities["update"]['bln'][$blnID]['segIDs'] = $segIDs;
  } else {
    $entities["update"]['bln'][$blnID]['segCount'] = count($segIDs);
    $entities["update"]['bln'][$blnID]['isLinked'] = 0;
  }

  if ($imgIDs && count($imgIDs) > 0) {
    $imgIDs = array_unique($imgIDs);
    $images = new Images('img_id in ('.join(",",$imgIDs).')',null,null.null);
    if($images->getCount()>0) {
      $images->setAutoAdvance(false); // make sure the iterator doesn't prefetch
      foreach ($images as $image) {
        $imgID = $image->getID();
        if ($imgID && !array_key_exists($imgID, $entities["update"]['img'])) {
          $title = $image->getTitle();
          $url = $image->getURL();
          $entities["update"]['img'][$imgID] = array('title'=> $image->getTitle(),
                                                     'id' => $imgID,
                                                     'value'=> ($title?$title:substr($url,strrpos($url,'/')+1)),
                                                     'readonly' => $image->isReadonly(),
                                                     'editibility' => $image->getOwnerID(),
                                                     'url' => $url,
                                                     'type' => $image->getType(),
                                                     'boundary' => $image->getBoundary());
        if ($url) {
          $info = pathinfo($url);
          $dirname = $info['dirname'];
          if (strpos($dirname,'full/full') > -1) { //assume iiif
            $fullpath = str_replace('full/full','full/pct:5',$dirname).'/'.$info['basename'];
          } else if (strpos($dirname,'iiif') > -1 && strpos($dirname,'/full/0') > -1 ) { //assume iiif     
            $fullpath = str_replace('/full/0','/pct:5/0',$dirname).'/'.$info['basename'];
          } else {
            $fullpath =  $dirname."/th".$info['basename'];
          }
          $entities['update']['img'][$imgID]['thumbUrl'] = $fullpath;
        }
        $AnoIDs = $image->getAnnotationIDs();
        if ($AnoIDs && count($AnoIDs) > 0) {
          $entities["update"]['img'][$imgID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $image->getAttributionIDs();
        if ($AtbIDs && count($AtbIDs) > 0) {
          $entities["update"]['img'][$imgID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
        }// if imgID
      }//for images
    }// if count images
  }// if count

  if (count( $atbIDs) > 0) {
    $entityIDs['atb'] = array_unique($atbIDs);
  }
  if (count( $anoIDs) > 0) {
    $entityIDs['ano'] = array_unique($anoIDs);
  }
  if (count( $entityIDs) > 0) {
    getRelatedEntities($entityIDs);
  }

  $retVal = array("entities" => $entities);
  if (count($warnings) > 0) {
    $retVal["warnings"] = $warnings;
  }
  if (count($errors) > 0) {
    $retVal["errors"] = $errors;
  }
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
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
                                        'value'=> $attribution->getTitle().($attribution->getDetail()?": ".$attribution->getDetail():''),
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
