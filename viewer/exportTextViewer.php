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
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/

/**
* exprtTextViewer
*
* export textViewer HTML framework  and supporting files to a static location
* according to the setting in config.php and the text/edition preferences
*/

  require_once (dirname(__FILE__) . '/../common/php/sessionStartUp.php');//initialize the session
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/php/viewutils.php');//get utilities for viewing

  startLog();
  $verbose = true;
  //determine output directories writablity
  if (!file_exists(VIEWER_EXPORT_PATH)) {
    logAddMsgExit("Configured Viewer Export path ".VIEWER_EXPORT_PATH." does not exist. Please inform your system administrator");
  }
  $info = new SplFileInfo(VIEWER_EXPORT_PATH);
  if (!$info->isDir() || !$info->isWritable()) {
    logAddMsgExit("Configured Viewer Export path ".VIEWER_EXPORT_PATH." needs to be a writeable directory.");
  }
  $exportDir = VIEWER_EXPORT_PATH;
  $exportBaseURL = VIEWER_BASE_URL;
  if ($verbose) {
    logAddMsg("Export path ".VIEWER_EXPORT_PATH." verified.");
  }

  if (!file_exists($exportDir."/css")) {
    logAddMsgExit("Configured Viewer Export path ".VIEWER_EXPORT_PATH." 'css' subdirectory does not exist. Please inform your system administrator");
  }
  $info = new SplFileInfo($exportDir."/css");
  if (!$info->isDir() || !$info->isWritable()) {
    logAddMsgExit("Viewer Export directory $exportDir needs a 'css' subdirectory that is writeable directory.");
  }
  if ($verbose) {
    logAddMsg("Export path for css verified.");
  }
  if (!file_exists($exportDir."/js")) {
    logAddMsgExit("Configured Viewer Export path ".VIEWER_EXPORT_PATH." 'js' subdirectory does not exist. Please inform your system administrator");
  }
  $info = new SplFileInfo($exportDir."/js");
  if (!$info->isDir() || !$info->isWritable()) {
    logAddMsgExit("Viewer Export directory $exportDir needs a 'js' subdirectory that is writeable directory.");
  }
  if ($verbose) {
    logAddMsg("Export path for js verified.");
  }
  if (!file_exists($exportDir."/images")) {
    logAddMsgExit("Configured Viewer Export path ".VIEWER_EXPORT_PATH." 'images' subdirectory does not exist. Please inform your system administrator");
  }
  $info = new SplFileInfo($exportDir."/images");
  if (!$info->isDir() || !$info->isWritable()) {
    logAddMsgExit("Viewer Export directory $exportDir needs a 'images' subdirectory that is writeable directory.");
  }
  if ($verbose) {
    logAddMsg("Export path for images verified.");
  }
  //todo in version 2: calculate image locations (local moves)
  // consider downsizing option that resizes and stores the image and
  // transpose the segmentation data

  // check and update support files css, images and js
  $cssSubPathFilename = "/css/readviewer.css";
  $exportViewerCss = new SplFileInfo($exportDir.$cssSubPathFilename);
  $readViewerCss = new SplFileInfo(dirname(__FILE__).$cssSubPathFilename);
  if ($exportViewerCss->isFile() && !$exportViewerCss->isWritable()) {
    logAddMsgExit("Unable to sync Viewer support file '$cssSubPathFilename' (not writable) aborting export.");
  } else if (!$readViewerCss->isFile() || !$readViewerCss->isReadable()) {
    logAddMsgExit("Unable to read Viewer support file '$cssSubPathFilename' aborting export.");
  } else if (!$exportViewerCss->isFile() || $exportViewerCss->getMTime() < $readViewerCss->getMTime()){
    if ( !copy(dirname(__FILE__).$cssSubPathFilename,$exportDir.$cssSubPathFilename)) {
      logAddMsgExit("Unable to sync Viewer support file 'readviewer.css' aborting export.");
    } else {
      logAddMsg("Sync'd Viewer support file 'readviewer.css'.");
    }
  } else {
    logAddMsg("Checked support file 'readviewer.css' up to date.");
  }
  $cssSubPathFilename = "/css/exGlossary.css";
  $exportGlossaryCss = new SplFileInfo($exportDir.$cssSubPathFilename);
  $readGlossaryCss = new SplFileInfo(dirname(__FILE__).'/../common'.$cssSubPathFilename);
  if ($exportGlossaryCss->isFile() && !$exportGlossaryCss->isWritable()) {
    logAddMsgExit("Unable to sync Viewer support file '$cssSubPathFilename' (not writable) aborting export.");
  } else if (!$readGlossaryCss->isFile() || !$readGlossaryCss->isReadable()) {
    logAddMsgExit("Unable to read Viewer support file '$cssSubPathFilename' aborting export.");
  } else if (!$exportGlossaryCss->isFile() || $exportGlossaryCss->getMTime() < $readGlossaryCss->getMTime()){
    if ( !copy(dirname(__FILE__).'/../editors'.$cssSubPathFilename,$exportDir.$cssSubPathFilename)) {
      logAddMsgExit("Unable to sync Viewer support file 'exGlossary.css' aborting export.");
    } else {
      logAddMsg("Sync'd Viewer support file 'exGlossary.css'.");
    }
  } else {
    logAddMsg("Checked support file 'exGlossary.css' up to date.");
  }
  $cssSubPathFilename = "/images/download.png";
  $exportViewerImage = new SplFileInfo($exportDir.$cssSubPathFilename);
  $readViewerImage = new SplFileInfo(dirname(__FILE__).$cssSubPathFilename);
  if ($exportViewerImage->isFile() && !$exportViewerImage->isWritable()) {
    logAddMsgExit("Unable to sync Viewer support file '$cssSubPathFilename' (not writable) aborting export.");
  } else if (!$readViewerImage->isFile() || !$readViewerImage->isReadable()) {
    logAddMsgExit("Unable to read Viewer support file '$cssSubPathFilename' aborting export.");
  } else if (!$exportViewerImage->isFile() || $exportViewerImage->getMTime() < $readViewerImage->getMTime()){
    if ( !copy(dirname(__FILE__).$cssSubPathFilename,$exportDir.$cssSubPathFilename)) {
      logAddMsgExit("Unable to sync Viewer support file 'download.png' aborting export.");
    } else {
      logAddMsg("Sync'd Viewer support file 'download.png'.");
    }
  }
  $jsSubPathFilename = "/js/imageViewer.js";
  $exportViewerJs = new SplFileInfo($exportDir.$jsSubPathFilename);
  $readViewerJs = new SplFileInfo(dirname(__FILE__).$jsSubPathFilename);
  if ($exportViewerJs->isFile() && !$exportViewerJs->isWritable()) {
    logAddMsgExit("Unable to sync Viewer support file '$jsSubPathFilename' (not writable) aborting export.");
  } else if (!$readViewerJs->isFile() || !$readViewerJs->isReadable()) {
    logAddMsgExit("Unable to read Viewer support file '$jsSubPathFilename' aborting export.");
  } else if (!$exportViewerJs->isFile() || $exportViewerJs->getMTime() < $readViewerJs->getMTime()){
    if ( !copy(dirname(__FILE__).$jsSubPathFilename,$exportDir.$jsSubPathFilename)) {
      logAddMsgExit("Unable to sync Viewer support file 'imageViewer.js' aborting export.");
    } else {
      logAddMsg("Sync'd Viewer support file 'imageViewer.js'.");
    }
  } else {
    logAddMsg("Checked support file 'imageViewer.js' up to date.");
  }
  $jsSubPathFilename = "/js/debug.js";
  $exportViewerJs = new SplFileInfo($exportDir.$jsSubPathFilename);
  $readViewerJs = new SplFileInfo(dirname(__FILE__)."/../editors".$jsSubPathFilename);
  if ($exportViewerJs->isFile() && !$exportViewerJs->isWritable()) {
    logAddMsgExit("Unable to sync Viewer support file '$jsSubPathFilename' (not writable) aborting export.");
  } else if (!$readViewerJs->isFile() || !$readViewerJs->isReadable()) {
    logAddMsgExit("Unable to read Viewer support file '$jsSubPathFilename' aborting export.");
  } else if (!$exportViewerJs->isFile() || $exportViewerJs->getMTime() < $readViewerJs->getMTime()){
    if ( !copy(dirname(__FILE__)."/../editors".$jsSubPathFilename,$exportDir.$jsSubPathFilename)) {
      logAddMsgExit("Unable to sync Viewer support file 'debug.js' aborting export.");
    } else {
      logAddMsg("Sync'd Viewer support file 'debug.js'.");
    }
  } else {
    logAddMsg("Checked support file 'debug.js' up to date.");
  }
  $jsSubPathFilename = "/js/utility.js";
  $exportViewerJs = new SplFileInfo($exportDir.$jsSubPathFilename);
  $readViewerJs = new SplFileInfo(dirname(__FILE__)."/../editors".$jsSubPathFilename);
  if ($exportViewerJs->isFile() && !$exportViewerJs->isWritable()) {
    logAddMsgExit("Unable to sync Viewer support file '$jsSubPathFilename' (not writable) aborting export.");
  } else if (!$readViewerJs->isFile() || !$readViewerJs->isReadable()) {
    logAddMsgExit("Unable to read Viewer support file '$jsSubPathFilename' aborting export.");
  } else if (!$exportViewerJs->isFile() || $exportViewerJs->getMTime() < $readViewerJs->getMTime()){
    if ( !copy(dirname(__FILE__)."/../editors".$jsSubPathFilename,$exportDir.$jsSubPathFilename)) {
      logAddMsgExit("Unable to sync Viewer support file 'utility.js' aborting export.");
    } else {
      logAddMsg("Sync'd Viewer support file 'utility.js'.");
    }
  } else {
    logAddMsg("Checked support file 'utility.js' up to date.");
  }

  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    returnXMLErrorMsgPage("invalid viewer request - not enough or invalid parameters");
  } else {
  // get parameters
    $xednIDs = null;
    if ( isset($data['xednIDs'])) {
      $xednIDs = $data['xednIDs'];
      $xednIDs = explode(",",$xednIDs);
      $xednID = intval($xednIDs[0]);
      if (!is_int($xednID)) {
        $xednIDs = $xednID = null;
      }
    }
    $cfgStatic = null;
    if ( isset($data['cfgStatic'])) {// bitmap config
      $cfgStatic = $data['cfgStatic'];
      $staticoverwrite = ($cfgStatic&128?true:false);
      $exportGlossary = ($cfgStatic&64?true:false);
      $allowImageDownload = ($cfgStatic&32?true:false);
      $allowTeiDownload = ($cfgStatic&16?true:false);
      $showContentOutline = ($cfgStatic&8?true:false);
      $showImageView = ($cfgStatic&4?true:false);
      $showTranslationView = ($cfgStatic&2?true:false);
      $showChayaView = ($cfgStatic&1?true:false);
    } else {
      $cfgStatic = 127; //default is no overwrite bit
      $staticoverwrite = false;
      $exportGlossary = $allowImageDownload = $allowTeiDownload =
                        $showContentOutline = $showImageView =
                        $showTranslationView = $showChayaView = true;
    }
    $title = null;
    if ( isset($data['title'])) {
      $title = $data['title'];
    }
    $cfgEntityTag = null;
    $cfgEntity = null;
    if ( isset($data['cfgEntityTag'])) {
      $cfgEntTag = $data['cfgEntityTag'];
      $prefix = substr($cfgEntTag,0,3);
      $cfgEntityID = substr($cfgEntTag,3);
      if ($prefix == "txt") {
        $cfgEntity = new Text($cfgEntityID);
        if ($cfgEntity->hasError()){
          $cfgEntity = null;
        }
      } else if ($prefix == "edn") {
        $cfgEntity = new Edition($cfgEntityID);
        if ($cfgEntity->hasError()){
          $cfgEntity = null;
        }
      }
      if ($cfgEntity) {
        $cfgEntityTag = DBNAME.$cfgEntTag;
      }
    }
    $refreshLookUps = (!isset($data['refreshLookUps']) || !$data['refreshLookUps'])? false:true;
    $basefilename = null;
    if ( isset($data['fname']) && strlen($data['fname']) > 0)  {
      $basefilename = $data['fname'];
    } else if (isset($data['txtID'])){
      $txtID = $data['txtID'];
      $text = new Text($txtID);
      if ($text->hasError()) {
        logAddMsg("unable to load text $txtID - ".join(",",$text->getErrors()));
      } else {
        $basefilename = ($text->getCKN()?str_replace(' ','_',trim($text->getCKN())):($text->getRef()?str_replace(' ','_',trim($text->getRef())):null));
      }
    }
    if (!$basefilename){
      $basefilename = "tempfname";
    }
    $catIDs = $catID = null;
    if ( isset($data['catID'])) {
      $catID = $data['catID'];
      $catIDs = explode(",",$catID);
      $catID = intval($catIDs[0]); //first id is primary
      if (!is_int($catID)) {
        $catIDs = $catID = null;
      }
    }
  }
  //update session Static View configuration
  if ($cfgEntityTag) {
    $_SESSION["cfgStaticView$cfgEntityTag"] = array("fname"=>$basefilename,
                                                    "title"=>($title?$title:"unknown title"),
                                                    "cfgStaticLayout"=>($cfgStatic&127));
  }

  //setup urlLookup
  $urlMap = array("tei"=>array());
//*************************************TEI Static Export **********************************
  if ($allowTeiDownload) {
    //generate static TEI files
    $singleEdition = (count($xednIDs)== 1);
    foreach ($xednIDs as $xednID){
      // todo check if multiEdition text then test if edition is public or research
      $edition = new Edition($xednID);
      if ($edition->hasError()){
        logAddMsg("Unable to access edition id '$xednID'.");
        continue;
      }
      //create TEI files and save to static location
      $url = SITE_BASE_PATH."/services/exportEditionToEpiDoc.php?db=".DBNAME."&ednID=$xednID";
      $editionTEI = getServiceContent($url);
      if ($singleEdition) {
        $teiFilename = "$basefilename".".TEI.xml";
      } else {
        $ednTitle = $edition->getDescription();
        if ($ednTitle && strlen($ednTitle)) {
          $teiFilename = $basefilename."_".preg_replace("/[^A-Za-z0-9\_\-\.]/", '_',$ednTitle).".TEI.xml";
        } else {
          $teiFilename = "$basefilename.$xednID".".TEI.xml"; //todo consider using edition title/attribution
        }
      }
      if (!$staticoverwrite && file_exists("$exportDir/$teiFilename")) {
        logAddMsgExit("TEI static export file '$teiFilename' exist and overwrite not enabled, aborting export.\n".
                      "If you would like to export, enable overwrite option in Export Dialog before starting export.",false,true);
      }
      if ($editionTEI && $hTEI = fopen("$exportDir/$teiFilename","w")) {
        fwrite($hTEI,$editionTEI);
        fclose($hTEI);
        $url = SITE_BASE_PATH."/services/downloadTextfile.php?url=$exportBaseURL/$teiFilename";
        logAddLink("Open exported TEI '$teiFilename'","$exportBaseURL/$teiFilename");
        logAddLink("Download link for Export TEI '$teiFilename'",$url);
      } else {
        logAddMsg("Unable to add TEI static export '$teiFilename'.");
      }
      // add url for TEI to url mapping
      $urlMap["tei"]["edn$xednID"] = $url;
    }
  }
//*************************************Glossary Static Export **********************************
  //create glossary and save if required
  if ($exportGlossary && $catIDs && count($catIDs)) {
    //generate static Glossary files
    foreach ($catIDs as $xcatID){//todo check whethter need to output multiple gloary files
      // todo check catalog is valid
      $catalog = new Catalog($xcatID);
      if ($catalog->hasError()){
        logAddMsg("Unable to access catalog id '$xcatID'.");
        continue;
      }
      $catTitle = $catalog->getTitle();
      //create static Glossary file and save to static location
//      $url = SITE_BASE_PATH."/services/exportHTMLGlossary.php?db=".DBNAME."&staticView=1&catID=$xcatID";
//      $glossaryHTML = getServiceContent($url);
      list($result,$glossaryHTML) = getCatalogHTML($catID,true,$refreshLookUps);
      if ($catTitle && strlen($catTitle)) {
        $glossaryFilename = preg_replace("/[^A-Za-z0-9\_\-\.]/", '_',$catTitle).".html";
      } else {
        $glossaryFilename = "glossary$xcatID"."$basefilename".".html";
      }
      if (!$staticoverwrite && file_exists("$exportDir/$glossaryFilename")) {
        logAddMsgExit("Glossary static export file '$glossaryFilename' exist and overwrite not enabled, aborting export.\n".
                      "If you would like to export, enable overwrite option in Export Dialog before starting export.",false,true);
      }
      if ($result == "success" && $glossaryHTML && $hGloss = fopen("$exportDir/$glossaryFilename","w")) {
        fwrite($hGloss,$glossaryHTML);
        fclose($hGloss);
        $url = "$exportBaseURL/$glossaryFilename";
        logAddLink("Open exported glossary '$glossaryFilename'","$exportBaseURL/$glossaryFilename");
        logAddLink("Download link for Export Glossary '$glossaryFilename'",$url);
      } else {
        logAddMsg("Unable to add glossary static export '$glossaryFilename'. $glossaryHTML");
      }
      // add url for Glossary to url mapping
      $urlMap["gloss"]["cat$xcatID"] = $url."#%lemtag%";
    }
  }

//*************************************Viewer Static Export **********************************
  //calculate static viewer HTML and export it
  $_REQUEST['staticView'] = 1; // tell getTextViewer code to generate static
  ob_start();
  include_once(dirname(__FILE__).'/getTextViewer.php');
  $viewerHTML = ob_get_contents();
  ob_end_clean();
  $viewerFilename = "$basefilename.html";
  if (!$staticoverwrite && file_exists("$exportDir/$viewerFilename")) {
    logAddMsgExit("Viewer static export file '$viewerFilename' exist and overwrite not enabled, aborting export.\n".
                  "If you would like to export, enable overwrite option in Export Dialog before starting export.",false,true);
  }
  if ($viewerHTML && $hViewer = fopen("$exportDir/$viewerFilename","w")) {
    fwrite($hViewer,$viewerHTML);
    fclose($hViewer);
    $url = SITE_BASE_PATH."/services/downloadTextfile.php?url=$exportBaseURL/$viewerFilename";
    logAddLink("Open exported viewer '$viewerFilename'","$exportBaseURL/$viewerFilename");
    logAddLink("Download link for exported Viewer '$viewerFilename'",$url);//todo call service to zip all. Images??
    if ($cfgEntity && array_key_exists("cfgStaticView$cfgEntityTag",$_SESSION)) {
      $cfgEntity->storeScratchProperty("cfgStaticView",$_SESSION["cfgStaticView$cfgEntityTag"]);
    }
  } else {
    logAddMsg("Unable to export viewer file '$viewerFilename'.");
  }

  flushLog(false,true);
?>
