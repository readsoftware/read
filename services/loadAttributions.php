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
  * loadAttributions
  *
  *  A service that returns a json structure of the attributions of this datastore.
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
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once dirname(__FILE__) . '/../model/entities/Attributions.php';
  require_once dirname(__FILE__) . '/../model/entities/Annotations.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';


  $dbMgr = new DBManager();
  $retVal = array();
  $txtCache = array();
  $anoIDs = array();
  $attrs = array();
  $errors = array();
  $warnings = array();
  $entityIDs = array();
  $entities = array('ano' => array(),
                    'atb' => array());
  // check for cache
  $dbMgr = new DBManager();
  if ($dbMgr->getError()) {
    exit("Error: ".$dbMgr->getError());
  }
  $dbMgr->query("SELECT * FROM jsoncache WHERE jsc_label = 'Attributions'");
  $jsonCache = null;
  if ($dbMgr->getRowCount() > 0 && USECACHE) {
    $row = $dbMgr->fetchResultRow();
    $jsonCache = new JsonCache($row);
    if (!$jsonCache->hasError() && !$jsonCache->isDirty()) {
      $jsonRetVal = $jsonCache->getJsonString();
      if (array_key_exists("callback",$_REQUEST)) {
        $cb = $_REQUEST['callback'];
        if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
          print $cb."(".$jsonRetVal.");";
        }
      } else {
        print $jsonRetVal;
      }
      exit;
    }
  }
  $termInfo = getTermInfoForLangCode('en');

  //get all visible attributions
  $attributions = new Attributions("",'atb_title',null,null);
  foreach ($attributions as $attribution){
    $atbID = $attribution->getID();
    if ($atbID && !array_key_exists($atbID, $entities['atb'])) {
      $entities['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
                             'id' => $atbID,
                              'value'=> $attribution->getTitle().($attribution->getDetail()?": ".$attribution->getDetail():''),
                              'readonly' => $attribution->isReadonly(),
                              'grpID' => $attribution->getGroupID(),
                              'bibID' => $attribution->getBibliographyID(),
                              'description' => $attribution->getDescription(),
                              'detail' => $attribution->getDetail(),
                              'types' => $attribution->getTypes());
      $AnoIDs = $attribution->getAnnotationIDs();
      if (count($AnoIDs) > 0) {
        $entities['atb'][$atbID]['annotationIDs'] = $AnoIDs;
        $anoIDs = array_merge($anoIDs,$AnoIDs);
      }
      array_push($attrs,array(
        'label' => $attribution->getTitle().($attribution->getDetail()?": ".$attribution->getDetail():''),
        'id' => 'atb'.$atbID,
        'value' => 'atb:'.$atbID,
      ));
    } // if atbID
  } //for attributions
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
  $retVal = array("entities" => $entities,
                  "termInfo" => $termInfo,
                  "attrs" => $attrs);
  $jsonRetVal = json_encode($retVal);
  if (count($warnings) > 0) {
    $retVal["warnings"] = $warnings;
  }
  if (count($errors) > 0) {
    $retVal["errors"] = $errors;
  }
  if (count($errors) == 0 && USECACHE) {
    if (!$jsonCache) {
      $jsonCache = new JsonCache();
      $jsonCache->setLabel('Attributions');
      $jsonCache->setJsonString($jsonRetVal);
      $jsonCache->setVisibilityIDs(array(6));
    } else {
      $jsonCache->clearDirty();
      $jsonCache->setJsonString($jsonRetVal);
    }
    $jsonCache->save();
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
    if (count($tempIDs) > 0 && !array_key_exists($prefix,$entities)) {
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
    if (count($entIDs) > 0) {
      switch ($prefix) {
        case 'atb':
          $attributions = new Attributions("atb_id in (".join(",",$entIDs).")",null,null,null);
          $attributions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
          foreach ($attributions as $attribution) {
            $atbID = $attribution->getID();
            if ($atbID && !array_key_exists($atbID, $entities['atb'])) {
              $entities['atb'][$atbID] = array( 'title'=> $attribution->getTitle(),
                                      'id' => $atbID,
                                      'value'=> $attribution->getTitle(),
                                      'readonly' => $attribution->isReadonly(),
                                      'grpID' => $attribution->getGroupID(),
                                      'bibID' => $attribution->getBibliographyID(),
                                      'description' => $attribution->getDescription(),
                                      'detail' => $attribution->getDetail(),
                                      'types' => $attribution->getTypes());
              $AnoIDs = $attribution->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
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
              if (count($AnoIDs) > 0) {
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
              $AnoIDs = $annotation->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['ano'][$anoID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('atb',$entityIDs)) {
                  $entityIDs['ano'] = array();
                }
                $entityIDs['ano'] = array_merge($entityIDs['ano'],$AnoIDs);
              }
            }
          }
          break;
      }//end switch $prefix
    }//end if count $entIDs
    if (count(array_keys($entityIDs)) > 0) {
      getRelatedEntities($entityIDs);
    }
  }
?>
