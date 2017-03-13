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
* exportEpiDoc
*
* calculates EpiDoc for editions and saves to a defined directory
*
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Services
*/

  if (@$argv) {
    $isCmdLineLaunch = true;
    // handle command-line queries
    $ARGV = array();
    for ($i = 0;$i < count($argv);++$i) {
        if ($argv[$i][0] === '-') {
            if (@$argv[$i + 1] && $argv[$i + 1][0] != '-') {
                $ARGV[$argv[$i]] = $argv[$i + 1];
                ++$i;
            } else {
                $ARGV[$argv[$i]] = true;
            }
        } else {
            array_push($ARGV, $argv[$i]);
        }
    }
    if (@$ARGV['-v']) $_REQUEST["v"] = 1;
    if (@$ARGV['-f']) $_REQUEST["f"] = 1;
    if (@$ARGV['-db']) $_REQUEST["db"] = $ARGV['-db'];
    if (@$ARGV['-ckn']) $_REQUEST['ckn'] = $ARGV['-ckn'];
    if (@$ARGV['-ednIDs']) $_REQUEST['ednIDs'] = $ARGV['-ednIDs'];
    if (@$ARGV['-userID']) $userID = $ARGV['-userID'];
}

  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');
//header('Content-type: text/xml; charset=utf-8');
  header('Pragma: no-cache');

  error_reporting(E_ERROR);

  include_once(dirname(__FILE__).'/utilRML.php');
  $logReport = ""; // log string to return from call to this service
  $ednIDs = (array_key_exists('ednIDs',$_REQUEST)? $_REQUEST['ednIDs']:null);
  $verbose = (array_key_exists('v',$_REQUEST)? true:false);
  $force = (array_key_exists('f',$_REQUEST)? true:false);

 //check path
  $path = (array_key_exists('subpath',$_REQUEST)? $_REQUEST['subpath']:null);
  if ($path) {
    $info = new SplFileInfo($path);
    if (!$info->isDir()) {// dir doesn't exist
      $isDir = mkdir($path, 0775, true);//try to create it
      if (!$isDir) {//point at the temp dir which will only can temporarily
        //log error echo "Error: unable to open destination. Nothing uploaded.";
        $path = null;
      }
    }
  }
  if (!$path && defined("EPIDOC_EXPORT_ROOT")) {
    $path = EPIDOC_EXPORT_ROOT."/".DBNAME;
    $info = new SplFileInfo($path);
    if (!$info->isDir()) {// dir doesn't exist
      $isDir = mkdir($path, 0775, true);//try to create it
      if (!$isDir) {//point at the temp dir which will only can temporarily
        //log error echo "Error: unable to open destination. Nothing uploaded.";
        $path = null;
      }
    }
  }
  if (!$path && defined("XML_EXPORT_ROOT")) {
    $path = XML_EXPORT_ROOT."/epidoc/".DBNAME;
    $info = new SplFileInfo($path);
    if (!$info->isDir()) {// dir doesn't exist
      $isDir = mkdir($path, 0775, true);//try to create it
      if (!$isDir) {//point at the temp dir which will only can temporarily
        //log error echo "Error: unable to open destination. Nothing uploaded.";
        $path = null;
      }
    }
  }
  if (!$path && defined("EXPORT_ROOT")) {
    $path = EXPORT_ROOT."/xml/epidoc/".DBNAME;
    $info = new SplFileInfo($path);
    if (!$info->isDir()) {// dir doesn't exist
      $isDir = mkdir($path, 0775, true);//try to create it
      if (!$isDir) {//point at the temp dir which will only can temporarily
        //log error echo "Error: unable to open destination. Nothing uploaded.";
        $path = null;
      }
    }
  }
  if (!$path && defined("DOCUMENT_ROOT")) {
    $path = DOCUMENT_ROOT."/export/xml/epidoc/".DBNAME;
    $info = new SplFileInfo($path);
    if (!$info || !$info->isDir()) {// dir doesn't exist
      $isDir = mkdir($info, 0775, true);//try to create it
      if (!$isDir) {//point at the temp dir which will only can temporarily
        //log error echo "Error: unable to open destination. Nothing uploaded.";
        $path = null;
      }
    }
  }
  if (!$path) {
    exit("unable to access directory for export");
  }

//scope - calculate editions to export
  //if no ids then error (list of ids or all)
  if (!$ednIDs || count($ednIDs) == 0) {
    exit("no editions specified to export");
  }
  if (is_array($ednIDs)) {// multiple text
    $condition = "edn_id in (".join(",",$ednIDs).") ";
  }else if (is_string($ednIDs)) {
    $condition = null;
    if (strpos($ednIDs,",")) {
      $condition = "edn_id in ($ednIDs)";
    }else if (strtolower($ednIDs) == "all") {
      $condition = "";
    } else if (is_numeric($ednIDs)) {
      $condition = "edn_id = $ednIDs";
    } else {
      exit("edition data not recognised");
    }
  }else{
    $condition = "";
  }
  $editions = new Editions($condition,null,null,null);
  if ($editions && ($editions->getCount()==0 || $editions->getError())) {
    exit("editions not loaded");
  }
  $editions->setAutoAdvance(false); // make sure the iterator doesn't prefetch
  $exportEditions = array();
  $exportKey = "epidocExportTime";
  foreach ($editions as $edition) {
    //if not force update then check each edition modify against export timestamp
    if (!$force) {
      $lastExportTime = $edition->getScratchProperty($exportKey);
      //if no export timestamp then add for export
      if (!$lastExportTime) {
        array_push($exportEditions,$edition);
      } else if ($lastExportTime < $edition->getModified()) {
        //TODO adjust all services to update edition modify property
        array_push($exportEditions,$edition);
      } else {
        //up to date so log notification this edition's epidoc up to date
      }
    } else {
      array_push($exportEditions,$edition);
    }
  }

  if (count($exportEditions) > 0 ) {
    //for each editions calculate the EpiDoc
    foreach ($exportEditions as $edition) {
      //getEpiDoc TEI for edition
      $success = null;
      $textRML = calcEditionRML($edition->getID(), null);
      if (!$textRML){
        //TODO log failure
        continue;
      }
      $textRMLDoc = new DOMDocument('1.0','utf-8');
      $suc = $textRMLDoc->loadXML($textRML);
      if (!$suc){
        //TODO log failure
        continue;
      }
      $textRMLDoc->xinclude();//todo write code here to squash xincludes down to some limit.
      $xslDoc = new DOMDocument('1.1','utf-8');
      $suc = $xslDoc->load(dirname(__FILE__)."/xsl/rml2EpiDoc.xsl");
      if (!$suc){
        //TODO log failure
        continue;
      }
      $xslProc = new XSLTProcessor();
      $xslProc->importStylesheet($xslDoc);
      // set up common parameters for stylesheets.
      //$xslProc->setParameter('','transform',$styleFilename);
      $epiXML = $xslProc->transformToXML($textRMLDoc);
      //if successful then
      if ($epiXML) {
        $epiXML = substr($epiXML,strpos($epiXML,">")+1);
        if (strpos($epiXML,'xmlns=""')) {//php XSLT parser is ouputting blank xmlns statements and fails validation
          $epiXML = str_replace('xmlns=""','',$epiXML);//remove any blank xmlns statements
        }
        $testDoc = new DOMDocument('1.1','utf-8');
        $epiXML = trim($epiXML);
        $testDoc->loadXML($epiXML);
        if (false && !$testDoc->relaxNGValidate("http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng")) {
          //log error
          //    echo "transformation with 'rml2EpiDoc.xsl' failed validation against 'tei-epidoc.rng'";
//          continue;
        }
        $epiXML = "<?xml version='1.0' encoding='UTF-8'?>\n".
                  '<?xml-model'." ".'href="http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng" type="application/xml" schematypens="http://relaxng.org/ns/structure/1.0"?>'."\n".
                  '<?xml-model'." ".'href="http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng" type="application/xml" schematypens="http://purl.oclc.org/dsdl/schematron"?>'."\n".
                  $epiXML;
        //calc filename and path epidoc_txtCKN_ednID.tei
        $textLabel = null;
        $txtID = $edition->getTextID();
        if ($txtID) {
          $text = new Text($txtID);
          if ($text && !$text->hasError() && $text->getID() == $txtID) {
            if ($text->getCKN()) {
              $textLabel = $text->getCKN();
            } else if ($text->getRef()) {
              $textLabel = $text->getRef();
            } else {
              $textLabel = "txt$txtID";
            }
          }
        }
        $filepathname = $path."/epidoc_$textLabel"."_edn".$edition->getID().".tei";
        $info = new SplFileInfo($filepathname);
        if ($info->isFile()) {
          //log overwrite
        }
        $file = $info->openfile("w");
        //write epidoc to file and close
        $file->fwrite($epiXML);
        //if success
        if ($file->getSize() == strlen($epiXML)) {
        //log write and update export timestamp for edition
          $edition->storeScratchProperty($exportKey,$edition->getModified());
          $edition->save();
        }
      }
    }
  }

//if verbose echo log results
//if log file append results to logfile


function returnXMLSuccessMsgPage($msg) {
	global $verbose;
    if (@$verbose) {
	    die("<html><body><success>$msg</success></body></html>");
    }else{
      error_log("successful transform ".$msg);
    }
}

function returnXMLErrorMsgPage($msg) {
	global $verbose;
	if (@$verbose) {
        die("<?xml version='1.0' encoding='UTF-8'?>\n<error>$msg</error>");
    }
   error_log("errored transform ".$msg);
}
