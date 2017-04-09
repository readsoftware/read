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
    if (@$ARGV['-U']) $_REQUEST["basexuser"] = $ARGV['-U'];
    if (@$ARGV['-P']) $_REQUEST["basexpwd"] = $ARGV['-P'];
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
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control

  if (!isLoggedIn()) {
    set_session(session_id(),1,"superuser","admin",null,null,null);
  }

  include_once(dirname(__FILE__).'/../services/utilRML.php');
  $logReport = ""; // log string to return from call to this service
  $ednIDs = (array_key_exists('ednIDs',$_REQUEST)? $_REQUEST['ednIDs']:null);
  $basexUser = (array_key_exists('basexuser',$_REQUEST)? $_REQUEST['basexuser']:(defined('BASEX_ADMIN_USER')?BASEX_ADMIN_USER:null));
  $basexPwd = (array_key_exists('basexuser',$_REQUEST)? $_REQUEST['basexpwd']:(defined('BASEX_ADMIN_PWD')?BASEX_ADMIN_PWD:null));
  $verbose = (array_key_exists('v',$_REQUEST)? true:false);
  $force = (array_key_exists('f',$_REQUEST)? true:false);

  include_once("BaseXClient.php");

  //get list of manifest edition ids
  $basexEpiDocEdnIDs = array();
  $mods = array();
  $session = null;
  if (!$basexUser || !$basexPwd) {
    exit("unable to signin to basex with user - password combination");
  }
  //retrieve info from baseX
  try {
    // create session
    $session = new Session("localhost", "1984", $basexUser, $basexPwd);
  } catch (Exception $e) {
    // print exception
    exit("unable to connect to basex: ".$e->getMessage());
  }
  $dbExist = false;
  if ($session) {
    try {
      //check basex dbname exist
      $input = 'let $db := "'.DBNAME.'"'.
               'return db:exists($db)';
      $query = $session->query($input);
      // get results
      if($query->more()) {
          $dbExist = $query->next();
      }
      // close query instance
      $query->close();
      if ($dbExist) {
        $logReport .= "Db ".DBNAME." exist in baseX \n";
      }
    } catch (Exception $e) {
      // print exception
      $errMsg = "Failed to check existance of ".DBNAME." in basex: ".$e->getMessage();
      $logReport .= $errMsg."\n";
      error_log($errMsg);
    }
    if (!$dbExist) { //try to create db
      try {
        //check basex dbname exist
        $input = 'let $db := "'.DBNAME.'"'.
                 'return db:create($db)';
        $query = $session->query($input);
        // close query instance
        $query->close();
        $logReport .= "Created db ".DBNAME."in baseX \n";
      } catch (Exception $e) {
        // print exception
        $errMsg = "Failed to create db ".DBNAME." in basex: ".$e->getMessage();
        $logReport .= $errMsg."\n";
        error_log($errMsg);
        exit($logReport);
      }
    }

    try {
      //get ednIDs of all epidoc documents for dbname
      $input = 'let $docs := db:list("'.DBNAME.'","epidoc") '.
                'for $docname in $docs '.
                'let $editionTag := replace($docname,"epidoc/.*_edn","") '.
                'let $ednTag := replace($editionTag,"\..*","") '.
                'return $ednTag';
      $query = $session->query($input);
      // loop through all results
      while($query->more()) {
        array_push($basexEpiDocEdnIDs, $query->next());
      }
      // close query instance
      $query->close();
      $logReport .= "Found ".count($basexEpiDocEdnIDs)." epidoc editions in ".DBNAME." db in baseX \n";
    } catch (Exception $e) {
      // print exception
      $errMsg = "Failed to find any epidoc file in ".DBNAME." db in basex: ".$e->getMessage();
      $logReport .= $errMsg."\n";
      error_log($errMsg);
    }

    //ensure that manifest is available
    try {
    $input = 'let $db := "'.DBNAME.'" '.
             'let $r := db:exists($db,"manifest") '.
             'where (not($r)) '.
             'return db:add($db,"<manifest/>","manifest")';
      $query = $session->query($input);
      // close query instance
      $query->close();
      $logReport .= "Checked manifest exists in ".DBNAME." db in baseX \n";
    } catch (Exception $e) {
      // print exception
      $errMsg = "Failed to create manifest for ".DBNAME." db in basex: ".$e->getMessage();
      $logReport .= $errMsg."\n";
      error_log($errMsg);
    }

    try {
      $input = 'let $manifest := doc("'.DBNAME.'/manifest") '.
                'for $edn in $manifest/manifest/edn '.
                'return "edn" || $edn/@id || "," || $edn/@modDate';
      $query = $session->query($input);
      // loop through all results
     while($query->more()) {
          list($ednTag,$modDate) = explode(",",$query->next());
          $mods[$ednTag] = $modDate;
      }
      // close query instance
      $query->close();
      $logReport .= "Found ".count($mods)." edition entries in manifest of ".DBNAME." db in baseX \n";
    } catch (Exception $e) {
      // print exception
      $errMsg = "Failed to read manifest from basex: ".$e->getMessage();
      $logReport .= $errMsg."\n";
      error_log($errMsg);
    }
  }

  if (count($basexEpiDocEdnIDs) > 1 ) {
    sort($basexEpiDocEdnIDs);
  }

  //load stylesheet
  $xslDoc = new DOMDocument('1.1','utf-8');
  $suc = $xslDoc->load(dirname(__FILE__)."/../services/xsl/rml2EpiDoc.xsl");
  if (!$suc){
    exit("Unable to load transform stylesheet from ".dirname(__FILE__)."/../services/xsl/rml2EpiDoc.xsl");
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
      $ednID = $edition->getID();
      $ednTag = "edn".$ednID;
      if ( array_key_exists($ednTag,$mods)) {
        $lastExportTime = $mods[$ednTag];
      }
      //if no export timestamp then add for export
      if (!$lastExportTime) {
        array_push($exportEditions,$edition);
      } else if ($lastExportTime < $edition->getModified()) {
        //TODO adjust all services to update edition modify property (touch edition)
        array_push($exportEditions,$edition);
      } else {
        //up to date so log notification this edition's epidoc up to date
        $logReport .= "EpiDoc of $ednTag found to be up to date, skipping \n";
      }
    } else {
      array_push($exportEditions,$edition);
    }
  }

  if (count($exportEditions) > 0 ) {
    //for each editions calculate the EpiDoc
    foreach ($exportEditions as $edition) {
      $ednID = $edition->getID();
      $ednTag = "edn".$ednID;
      //getEpiDoc TEI for edition
      $success = null;
      $textRML = calcEditionRML($ednID, null);
      if (!$textRML){
        $logReport .= "Failed to create RML of $ednTag, skipping \n";
        continue;
      }
      $textRMLDoc = new DOMDocument('1.0','utf-8');
      $suc = $textRMLDoc->loadXML($textRML);
      if (!$suc){
        $logReport .= "Failed to load DomDocument with RML of $ednTag, skipping \n";
        continue;
      }
      $textRMLDoc->xinclude();//todo write code here to squash xincludes down to some limit.
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
        $epiXML = str_replace('&lt;','<',$epiXML);//fixup angle brackets for ab element
        $epiXML = str_replace('&gt;','>',$epiXML);//remove any blank xmlns statements
        $testDoc = new DOMDocument('1.0','utf-8');
        $epiXML = trim($epiXML);
        $testDoc->loadXML($epiXML);
        if (!$testDoc->relaxNGValidate("http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng")) {
          //log error
          $logReport .= "Failed to validate epidoc transformation of $ednTag RML, skipping \n";
          error_log("transformation with 'rml2EpiDoc.xsl' failed validation against 'tei-epidoc.rng'");
          continue;
        }

        $epiXML = '<?xml-model'." ".'href="http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng" type="application/xml" schematypens="http://relaxng.org/ns/structure/1.0"?>'."\n".
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
        $filepathname = "epidoc/$textLabel"."_edn".$edition->getID().".xml";
        if ($session) {
          try {
            //update epidoc xml in basex
            $input = 'let $xml := \''.$xml.'\' '.
                     'return db:replace("'.DBNAME.'","'.$filepathname.'",$xml)';
            $query = $session->query($input);
            // close query instance
            $query->close();
            $logReport .= "Updated/added $filepathname to basex db ".DBNAME." \n";
          } catch (Exception $e) {
            // print exception
            $errMsg = "Failed to update/add $filepathname to basex db ".DBNAME." error: ".$e->getMessage();
            $logReport .= $errMsg."\n";
            error_log($errMsg);
            continue;
          }
          $modDate = $edition->getModified();
          $nodeStr = "<edn id=\"$ednID\" modDate=\"$modDate\"/>";
          //if entry in manifest then update
          if (array_key_exists($ednTag,$mods)) {
            try {
              //update epidoc xml in basex
              $input = 'let $db := "'.DBNAME.'" '.
                       'let $ednID := "'.$ednID.'" '.
                       'let $nodes := db:open($db,"/manifest")/manifest/edn '.
                       'for $edn in $nodes[@id = $ednID] '.
                       'return replace value of node $edn/@modDate with "'.$modDate.'"';
              $query = $session->query($input);
              // close query instance
              $query->close();
              $logReport .= "Updated timestamp on edn node for edition $ednID in manifest of basex db ".DBNAME." \n";
            } catch (Exception $e) {
              // print exception
              $errMsg = "Failed to insert edn node for edition $ednID into manifest for basex db ".DBNAME." error: ".$e->getMessage();
              $logReport .= $errMsg."\n";
              error_log($errMsg);
            }
          } else {//else insert new entry
            try {
              //update epidoc xml in basex
              $input = 'let $node := \''.$nodeStr.'\' '.
                       'return insert node $node as last into doc(\''.DBNAME.'/manifest\')/manifest';
              $query = $session->query($input);
              // close query instance
              $query->close();
              $logReport .= "Inserted edn node for edition $ednID into manifest for to basex db ".DBNAME." \n";
            } catch (Exception $e) {
              // print exception
              $errMsg = "Failed to insert edn node for edition $ednID into manifest for basex db ".DBNAME." error: ".$e->getMessage();
              $logReport .= $errMsg."\n";
              error_log($errMsg);
            }
          }
        }
      } else {
        $errMsg = "Failed to transform $ednTag for basex db ".DBNAME." skipping";
        $logReport .= $errMsg."\n";
        error_log($errMsg);
      }
    }//end foreach $exportEditions
  }

  if ($session) {
    // close session
    $session->close();
  }

//if verbose echo log results
if ($verbose) {
  echo $logReport;
}
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
