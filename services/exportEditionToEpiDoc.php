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
* exportEditionToEpiDoc
*
* retrieves RML for a text edition and transforms it to EpiDoc using the XSLT
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

  if (isset($argv)) {
    $isCmdLineLaunch = true;
    // handle command-line queries
    $ARGV = array();
    for ($i = 0;$i < count(@$argv);++$i) {
        if (@$argv[$i][0] === '-') {
            if (@$argv[$i + 1] && @$argv[$i + 1][0] != '-') {
                $ARGV[@$argv[$i]] = @$argv[$i + 1];
                ++$i;
            } else {
                $ARGV[@$argv[$i]] = true;
            }
        } else {
            array_push($ARGV, @$argv[$i]);
        }
    }
    if (@$ARGV['-db']) $_REQUEST["db"] = $ARGV['-db'];
    if (@$ARGV['-ckn']) $_REQUEST['ckn'] = $ARGV['-ckn'];
    if (@$ARGV['-ednID']) $_REQUEST['ednID'] = $ARGV['-ednID'];
    if (@$ARGV['-userID']) $userID = $ARGV['-userID'];
  }

  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);

  if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    header('Content-Encoding: gzip');
    ob_start('ob_gzhandler');
  } else {
    ob_start();
  }

//header('Content-type: text/xml; charset=utf-8');
  header('Pragma: no-cache');

  include_once(dirname(__FILE__).'/utilRML.php');

  error_reporting(E_ERROR);
  $textRML = null;
  $textCKN = (array_key_exists('ckn',$_REQUEST)? $_REQUEST['ckn']:null);
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $textRML = calcEditionRML($ednID, $textCKN);
  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $textRMLDoc = new DOMDocument('1.0','utf-8');
  $suc = $textRMLDoc->loadXML($textRML);
  if (!$suc){
    echo "unable to load RML";
    return;
  }
  $textRMLDoc->xinclude();//todo write code here to squash xincludes down to some limit.
  $xslDoc = new DOMDocument('1.1','utf-8');
  $suc = $xslDoc->load(dirname(__FILE__)."/xsl/rml2EpiDoc.xsl");
  $xslProc = new XSLTProcessor();
  $xslProc->importStylesheet($xslDoc);
  // set up common parameters for stylesheets.
  //$xslProc->setParameter('','transform',$styleFilename);
  $epiXML = $xslProc->transformToXML($textRMLDoc);
//file_put_contents("./testrml.xml",$textRML)
  $epiXML = substr($epiXML,strpos($epiXML,">")+1);
  if (strpos($epiXML,'xmlns=""')) {//php XSLT parser is ouputting blank xmlns statements and fails validation
    $epiXML = str_replace('xmlns=""','',$epiXML);//remove any blank xmlns statements
  }
  $epiXML = str_replace('&lt;','<',$epiXML);//fixup angle brackets for ab element
  $epiXML = str_replace('&gt;','>',$epiXML);//remove any blank xmlns statements
  $epiXML = str_replace('\n','',$epiXML);//remove any blank xmlns statements
  $testDoc = new DOMDocument('1.0','utf-8');
  $testDoc->loadXML($epiXML);
  set_error_handler("errorHandler");
  if (!isset($isCmdLineLaunch) && !$testDoc->relaxNGValidate("http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng")) {
    header("Content-type: text/javascript;  charset=utf-8");
    error_log("transformation with 'rml2EpiDoc.xsl' failed validation against 'tei-epidoc.rng'");
//    return;
  }
  restore_error_handler();
  $epiXML = "<?xml version='1.0' encoding='UTF-8'?>"."\n".
            '<?xml-model'." ".'href="http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng" type="application/xml" schematypens="http://relaxng.org/ns/structure/1.0"?>'."\n".
            '<?xml-model'." ".'href="http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng" type="application/xml" schematypens="http://purl.oclc.org/dsdl/schematron"?>'."\n".
            $epiXML;
//  error_log(print_r($doc,true));
  ob_clean();
  if ($isDownload) {
    header("Content-type: application/tei+xml;  charset=utf-8");
    header("Content-Disposition: attachment; filename=epidoc_".$_REQUEST['ckn'].".tei");
    header("Expires: 0");
  } else {
    header('Content-type: text/xml;  charset=utf-8');
    header('Cache-Control: no-cache');
  }
  echo $epiXML;

  return;

function returnXMLSuccessMsgPage($msg) {
	global $verbose;
    if (@$verbose) {
	    die("<html><body><success>$msg</success></body></html>");
    }else{
      error_log("successful epidoc transform ".$msg);
    }
}

function returnXMLErrorMsgPage($msg) {
	global $verbose;
	if (@$verbose) {
        die("<?xml version='1.0' encoding='UTF-8'?>\n<error>$msg</error>");
    }
   error_log("errorant epidoc transform ".$msg);
}

function errorHandler($errno, $errmsg, $file, $line)
{
    if (!(error_reporting() & $errno)) {
//        return;
    }

    switch ($errno) {
    case E_USER_ERROR:
        error_log("validation ERROR[$errno]:  $errmsg  validation error on line $line in file $file Aborting...");
        exit(1);
        break;

    case E_USER_WARNING:
        error_log("validation WARNING[$errno]:  $errmsg ");
        break;

    case E_USER_NOTICE:
        error_log("validation NOTICE[$errno]:  $errmsg ");
        break;

    default:
        error_log("Unknown error type: [$errno] on line $line in file $file - $errmsg");
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}
?>