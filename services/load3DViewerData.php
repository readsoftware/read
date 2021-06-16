<?php
/**
 * A service to get 3D viewer data, including model UID and annotations.
 */

define('ISSERVICE',1);
ini_set("zlib.output_compression_level", 5);
ob_start();

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once dirname(__FILE__) . '/../model/entities/Texts.php';
require_once dirname(__FILE__) . '/../model/entities/Segments.php';
require_once dirname(__FILE__) . '/../model/entities/SyllableClusters.php';

$dbMgr = new DBManager();
$responseData = [];

$texts = new Texts('', 'txt_id', null, null);
if ($texts->getCount() > 0) {
  $responseData['models'] = [];
  foreach ($texts as $text) {
    $tdvData = $text->getScratchProperty('tdViewer');
    if (isset($tdvData['uid'])) {
      $responseData['models'][$text->getID()] = [
        'txtID' => $text->getID(),
        'modelUID' => $tdvData['uid'],
      ];
    }
  }
}

$sclAnoData = [];
$syllables = new SyllableClusters('', 'scl_id', null, null);
/**
 * @var \SyllableCluster $syllable
 */
foreach ($syllables as $syllable) {
  $sclSeg = $syllable->getSegment(TRUE);
  if ($sclSeg) {
    $sclSegTDVData = $sclSeg->getScratchProperty('tdViewer');
    if (isset($sclSegTDVData['annotations']) && count($sclSegTDVData['annotations']) > 0) {
      $sclAnoItem = [
        'sclID' => $syllable->getID(),
        'segID' => $sclSeg->getID(),
      ];
      $sclGraphemeValues = [];
      $sclGraphemes = $syllable->getGraphemes(TRUE);
      /**
       * @var \Grapheme $sclGrapheme
       */
      foreach ($sclGraphemes as $sclGrapheme) {
        $sclGraphemeValues[] = $sclGrapheme->getGrapheme();
      }
      $sclAnoItem['sclTrans'] = implode('', $sclGraphemeValues);
      $sclAnoItem['annotations'] = $sclSegTDVData['annotations'];
      $sclAnoData[$syllable->getID()] = $sclAnoItem;
    }
  }
}

if (!empty($sclAnoData)) {
  $responseData['annotations']['syllables'] = $sclAnoData;
}

$jsonRetVal = json_encode($responseData);
ob_clean();
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".$jsonRetVal.");";
  }
} else {
  print $jsonRetVal;
}
