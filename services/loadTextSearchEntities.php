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
  * loadTextSearchEntities
  *
  *  A service that search results for text objects in the system along with attached annotations and
  *  attributions in a json structure.
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
    if(@$cmdParams['-data']) $_REQUEST["data"] = $cmdParams['-data'];
    if (@$cmdParams['-refresh']) $_REQUEST['refresh'] = $cmdParams['-refresh'];
    if (@$cmdParams['-search']) $_REQUEST['search'] = $cmdParams['-search'];
    //commandline access for setting userid  TODO review after migration
    if (@$cmdParams['-uid'] && !isset($_SESSION['ka_userid'])) $_SESSION['ka_userid'] = $cmdParams['-uid'];
  }


//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Texts.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/Annotations.php';
  require_once dirname(__FILE__) . '/../model/entities/Attributions.php';
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
  $entities = array( 'txt' => array(),
                     'cat' => array(),
                     'tmd' => array(),
                     'edn' => array(),
                     'bln' => array(),
                     'img' => array(),
                     'srf' => array(),
                     'ano' => array(),
                     'atb' => array());
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):null);
  $strSearch = (array_key_exists('search',$_REQUEST)? $_REQUEST['search']:null);
  if (!$data && !$strSearch) {
    array_push($warnings," no search passed querying for all text data");
  } else if ($data){
    if ( isset($data['ckn'])) {//text ids
      $textCKNs = $data['ckn'];
      if (is_array($textCKNs)) {// multiple text
        $condition = "txt_ckn in ('".join("','",$textCKNs)."') ";
      }else if (is_string($textCKNs)) {
        $condition = "";
        if (strpos($textCKNs,",")) {
          $condition = "txt_ckn in ('".str_replace(",","','", $textCKNs)."') ";
        }else if (substr(strtoupper($textCKNs),0,2) =="CK") {
          if (strlen($textCKNs) == 3) {
            $condition = "txt_ckn ilike '$textCKNs%' ";
          } else {
            $condition = "txt_ckn = '$textCKNs' ";
          }
        }
      }else{
        $condition = "";
      }
    }
    if ( !$strSearch && isset($data['search'])) {//general search string for txt_title
      $strSearch = $data['search'];
    }
  }
  if ($strSearch && $strSearch != '""') {
    if (strlen($condition)) {
      $condition .= " or ";
    }
    if (preg_match('/(cki|ckm|ckd)/',strtolower($strSearch)) || strtolower($strSearch) == 'c' || strtolower($strSearch)=='ck') {
      if (strpos($strSearch,",")>6) {
        $condition .= "txt_ckn in ('".str_replace(",","','", strtoupper($strSearch))."')";
      } else {
        $condition .= "txt_ckn ilike '$strSearch%'";
      }
    } else {
      $condition .= "txt_title ilike '%$strSearch%'";
    }
  }
  $refresh = false;
  if ( isset($data['refresh'])) {
    $refresh = $data['refresh'];
  }
  if ( isset($_REQUEST['refresh'])) {
    $refresh = $_REQUEST['refresh'];
  }

  $termInfo = getTermInfoForLangCode('en');
  if (!isset($_SESSION['ka_searchAllResults_'.DBNAME]) || $refresh ||
      (defined("USECACHE") && !USECACHE)) {//if not in SESSION or refresh request
    $imgIDs = array();
    $anoIDs = array();
    $atbIDs = array();
    //load dictionaries
    if (isset($termInfo) && array_key_exists("dictionary-catalogtype",$termInfo['idByTerm_ParentLabel'])) {
      $dictCatTypeID = $termInfo['idByTerm_ParentLabel']["dictionary-catalogtype"];//term dependency
      // get all defined dictionaries
      $catalogs = new Catalogs("cat_type_id = $dictCatTypeID",'cat_id',null,null);
      foreach ($catalogs as $catalog){
        $catID = $catalog->getID();
        if ($catID && !array_key_exists($catID, $entities['cat'])) {
          $entities['cat'][$catID] = array( 'description'=> $catalog->getDescription(),
                                 'id' => $catID,
                                 'value'=> $catalog->getTitle(),
                                 'readonly' => $catalog->isReadonly(),
                                 'ednIDs' => $catalog->getEditionIDs(),
                                 'typeID' => $catalog->getTypeID());
          $AnoIDs = $catalog->getAnnotationIDs();
          if (count($AnoIDs) > 0) {
            $entities['cat'][$catID]['annotationIDs'] = $AnoIDs;
            $anoIDs = array_merge($anoIDs,$AnoIDs);
          }
          $AtbIDs = $catalog->getAttributionIDs();
          if (count($AtbIDs) > 0) {
            $entities['cat'][$catID]['attributionIDs'] = $AtbIDs;
            $atbIDs = array_merge($atbIDs,$AtbIDs);
          }
        } // if catID
      } //for catalog
    }

    $texts = new Texts($condition,'txt_ckn',null,null);//get all user visible text
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
                               'title' => $text->getTitle());
        $cknLookup[$ckn] = $txtID;
        $tImgIDs = $text->getImageIDs();
        if (count($tImgIDs) > 0) {
          $entities['txt'][$txtID]['imageIDs'] = $tImgIDs;
          $imgIDs = array_unique(array_merge($imgIDs,$tImgIDs));
        }
        $AnoIDs = $text->getAnnotationIDs();
        if (count($AnoIDs) > 0) {
          $entities['txt'][$txtID]['annotationIDs'] = $AnoIDs;
          $anoIDs = array_merge($anoIDs,$AnoIDs);
        }
        $AtbIDs = $text->getAttributionIDs();
        if (count($AtbIDs) > 0) {
          $entities['txt'][$txtID]['attributionIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
        $AtbIDs = $text->getEditionReferenceIDs();
        if (count($AtbIDs) > 0) {
          $entities['txt'][$txtID]['refIDs'] = $AtbIDs;
          $atbIDs = array_merge($atbIDs,$AtbIDs);
        }
      } // if txtID
    } //for text
    $txtIDs = array_keys($entities['txt']);

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

    $retVal["success"] = false;
    if (count($errors)) {
      $retVal["errors"] = $errors;
    } else {
      $retVal["success"] = true;
    }
    if (count($warnings)) {
      $retVal["warnings"] = $warnings;
    }
    if (count($termInfo)) {
      $retVal["termInfo"] = $termInfo;
    }
    if (count($cknLookup)) {
      $retVal["cknToTextID"] = $cknLookup;
    }
    if (count($entities)) {
      $retVal["entities"] = array("insert"=>$entities);//dependencies for this to remain "insert"
    }
    $_SESSION['ka_searchAllResults_'.DBNAME] = json_encode($retVal);
  }
  $jsonRetVal = $_SESSION['ka_searchAllResults_'.DBNAME];
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".$jsonRetVal.");";
    }
  } else {
    print $jsonRetVal;
  }

  function getRelatedEntities($entityIDs) {
    global $entities,$anoIDs,$atbIDs,$gra2SclMap,$segID2sclIDs;
    static $prefixProcessOrder = array('lnk','seq','lem','inf','cmp','tok','scl','gra','seg','bln','atb','ano');//important attr before anno to terminate
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
      if ($entID && !array_key_exists($entID,$entities[$prefix]) && !in_array($entID,$entIDs)) {
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
                                      'value'=> $attribution->getTitle().($attribution->getDetail()?$attribution->getDetail():''),
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
              if (in_array(2,$vis)) {
                $vis = "Public";
              } else if (in_array(3,$vis)) {
                $vis = "User";
              } else {
                $vis = "Private";
              }
              $entities['ano'][$anoID]['vis'] = $vis;
              $AnoIDs = $annotation->getAnnotationIDs();
              if (count($AnoIDs) > 0) {
                $entities['ano'][$atbID]['annotationIDs'] = $AnoIDs;
                if (!array_key_exists('ano',$entityIDs)) {
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
