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
* exportPaleography
*
* exports several forms of paleography information depending on the parameters passed.
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Services
*/
define('ISSERVICE',1);
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');


require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');//get map for valid aksara
require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');
require_once (dirname(__FILE__) . '/../model/entities/Graphemes.php');
require_once (dirname(__FILE__) . '/../model/entities/Token.php');
require_once (dirname(__FILE__) . '/../model/entities/Segments.php');
require_once (dirname(__FILE__) . '/../model/entities/Sequences.php');
require_once (dirname(__FILE__) . '/../model/entities/OrderedSet.php');
require_once (dirname(__FILE__) . '/../model/entities/Edition.php');
require_once (dirname(__FILE__) . '/../model/entities/Catalog.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');

$dbMgr = new DBManager();
$retVal = array();
$errors = array();
$warnings = array();

$catID = (array_key_exists('catID',$_REQUEST)? $_REQUEST['catID']:null);
$ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
$downloadCnt = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:0);

if (!$catID && !$ednID) {
  array_push($errors,"Must indicate a catalog or edition to export paleographic information.");
} else {
  if ($catID) {
    $catalog = new Catalog($catID);
    if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
      array_push($warnings,"Warning need valid catalog id $catID. Errors: ".join(".",$catalog->getErrors()));
    } else {
      $ednIDs = $catalog->getEditionIDs();
    }
  }
  if (( @!$ednIDs || count($ednIDs) == 0) && $ednID) {//use ednID
    $ednIDs = array($ednID);
  }
  if ( @!$ednIDs || count($ednIDs) == 0) {
    array_push($errors,"need valid catalog or edition id to export paleographic information.");
  } else {
    $editions = new Editions("edn_id in (".join(",",$ednIDs).")");
    if (!$editions || $editions->getCount() == 0 ) {
      array_push($errors,"error loading editions");
    } else {
      //create archive
      $zip = new ZipArchive();
      $filepath = sys_get_temp_dir()."/";
      $archiveFilename = ($catID?"cat$catID":"edn$ednID")."_paleography.zip"; //"/C/xampp/tmp/
      //create the archive file and exit with error if failed
      if ($zip->open($filepath.$archiveFilename, ZIPARCHIVE::CREATE )!==TRUE) {
          exit("cannot open $archiveFilename \n");
      }
      //get Paleography terms lookup
      $paleoTagTermsLookup = array();
      $paleoTermID = Entity::getIDofTermParentLabel("paleography-tagtype");
      $paleoTagTerms = getChildTerms($paleoTermID);
      $classificationTags = array("BaseType","FootMarkType","VowelType");
      foreach ($paleoTagTerms as $paleoTag) {
        if (in_array ($paleoTag->getLabel(),$classificationTags)) {
          $subTagTerms = getChildTerms($paleoTag->getID());
          foreach ($subTagTerms as $subTag) {
            $paleoTagTermsLookup[$subTag->getID()] = $subTag;
          }
        } else {
          $paleoTagTermsLookup[$paleoTag->getID()] = $paleoTag;
        }
      }
      //for each edition
      foreach ($editions as $edition) {
        $ednID = $edition->getID();
        //  find text ref label
        $text = $edition->getText(true);
        if ($text->hasError()) {
          $txtLabel = "err";
        } else if ($text->getRef()) {
          $txtLabel = $text->getRef();
        } else if ($text->getCKN()) {
          $txtLabel = $text->getCKN();
        } else {
          $txtLabel = 'txt'.$text->getID();
        }
        $edSeqs = $edition->getSequences(true);
        $seqPhys = null;
        foreach ($edSeqs as $edSequence) {
          $seqType = $edSequence->getType();
          if (!$seqPhys && $seqType == "TextPhysical"){
            $seqPhys = $edSequence;
            break;
          }
        }
        if (!$seqPhys) {
          array_push($warning,"physical text sequence for edn$ednID to export paleographic information.");
        }else{
          $physLineSeqs = $seqPhys->getEntities(true);
          if (!$physLineSeqs && $physLineSeqs->getCount() == 0 ) {
            array_push($errors,"error loading physical lines for edn$ednID, skipping");
          } else {
            //  for each physical line sequence
            $segCnt = 0;
            foreach ($physLineSeqs as $physLineSeq) {
              $aksaraOffset = 0;
              //    get line label
              if ($physLineSeq->getType() == 'LinePhysical'){//term dependency
                $lineLabel = $physLineSeq->getLabel();
                if (!$lineLabel) {
                  $lineLabel = $physLineSeq->getSuperScript();
                }
                if (!$lineLabel) {
                  $lineLabel = 'seq'.$physLineSeq->getID();
                }
                $lineSyllables = $physLineSeq->getEntities(true);
                if (!$lineSyllables && $lineSyllables->getCount() == 0 ) {
                  array_push($errors,"error loading physical line syllabless for $lineLabel of $ednID, skipping");
                } else {
                  //    foreach syllable
                  foreach ($lineSyllables as $lineSyllable) {
                    //      get syllable value
                    $transcription = preg_replace("/ʔ/","",$lineSyllable->getValue(true));
                    //      count syllable character offset
                    $segCnt++;
                    $aksaraOffset++;
                    //      getSegment
                    $segment = $lineSyllable->getSegment(true);
                    if (!$segment) {
                      continue;
                    }
                    $urls = $segment->getURLs();
                    //if type not image then continue
                    if (!$urls) {
                      continue;
                    }
                    //init tag variable
                    $classificationTags = $btTag = $ftTag = $vtTag = $selectionTag = $otherTags = "";
                    $tagIDs = $lineSyllable->getLinkedByAnnotationsByType();
                    //if linked by ano by type tag,  check syllable for paleo tags
                    if ($tagIDs && count($tagIDs) > 0 ) {
                      foreach ($tagIDs as $tagTypeID=>$anoIDs) {
                        //check paleographic classification tags and concatenate '_' with trm_code value
                        if (array_key_exists($tagTypeID,$paleoTagTermsLookup)) {
                          $paleoTag = $paleoTagTermsLookup[$tagTypeID];
                          $parentLabel = Entity::getTermFromID($paleoTag->getParentID());
                          switch ($parentLabel) {
                            case 'BaseType':
                              $btTag = "_".$paleoTag->getCode();
                              break;
                            case 'FootMarkType':
                              $ftTag = "_".$paleoTag->getCode();
                              break;
                            case 'VowelType':
                              $vtTag = "_".$paleoTag->getCode();
                              break;
                            default:
                              if ($paleoTag->getLabel() == "Default") {
                                $selectionTag = "_".$paleoTag->getCode();
                              } else { //any other paleo tags concatenate trm_code value with '_'
                                $otherTags .= "_".$paleoTag->getCode();
                              }
                          }
                        }
                      }
                    }
                    $classificationTags = $btTag.$ftTag.$vtTag;
                    $locationLabel = "_".$lineLabel.".".$aksaraOffset;
                    $segFilenameBase = SEGMENT_CACHE_BASE_PATH.DBNAME."seg".$segment->getID();
                    $localFilenameBase = "paleoClips/".$txtLabel."_edn$ednID/".$transcription.$classificationTags.$locationLabel.$selectionTag.$otherTags;
                    //      retrieve png bits
                    $urlCnt = 0;
                    foreach ($urls as $url) {
                      $urlCnt++;//handle segments with multiple urls
                      $localFilename = $localFilenameBase.($urlCnt>1?"_part$urlCnt":"").".png";
                      $segFilename = $segFilenameBase.($urlCnt>1?"_part$urlCnt":"").".png";
                      //if segment url not cached then save it
                      $bytesSaved = 0;
                      if (!file_exists($segFilename)) {
                        $bytesSaved = file_put_contents($segFilename,loadURLContent($url,true));
                        if (!$bytesSaved) {
                          array_push($errors,"error caching segment image for $segFilename line syllable for $lineLabel of $ednID, skipping");
                        }
                      }
                      //      add to archive
                      if (file_exists($segFilename)) {
                        $isLoaded = $zip->addFile($segFilename,$localFilename);
                      }
                      if (!$isLoaded) {
                        array_push($errors,"unable to add $filename from $url into zip file");
                      }
                      //      add xif ????
                    }
  //                  if ($segCnt > $downloadCnt) break;
                  }
                }
  //              if ($segCnt > $downloadCnt) break;
              }
            }
          }
        }
      }
      $zip->close();
      if ($downloadCnt) {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".$archiveFilename."\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filepath.$archiveFilename));
        ob_end_clean();
        ob_end_flush();
        readfile($filepath.$archiveFilename);
        exit;
      }
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
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
