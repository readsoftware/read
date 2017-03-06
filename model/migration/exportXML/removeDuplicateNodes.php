<?php
  if (!isset($argv)) {
    print "Usage:  removeDuplicateNodes.php filepathname";
    exit;
  }
  $filename = $argv[1];
  $seenNodeHashes = array();
  if (file_exists($filename)) {
    $fileXML = simplexml_load_file($filename);
    $schemaXML = '<?xml version="1.0" encoding="utf-8"?><xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchemaq">'."\n";
    if ($fileXML->getName()== "root") { //processing output of DB schema dump
      foreach ($fileXML->children("xsd",true) as $topNode) {
        if ($topNode->getName()== "schema") {
          foreach ($topNode->children("xsd",true) as $level2Node) {
            $hash = md5($level2Node->asXML());
            if (!array_key_exists($hash,$seenNodeHashes)) {
              $seenNodeHashes[$hash] = 1;
              $schemaXML .= $level2Node->asXML()."\n";
            }
          }
        }
      }
    } else if ($fileXML->getName()== "schema") {
      foreach ($fileXML->children("xsd",true) as $node) {
        $hash = md5($node->asXML());
        if (!array_key_exists($hash,$seenNodeHashes)) {
          $seenNodeHashes[$hash] = 1;
          $schemaXML .= $node->asXML()."\n";
        }
      }
    } else {
      echo "Unknown file format, aborting process";
      exit;
    }
    $schemaXML .= "</xsd:schema>";
    $schema = simplexml_load_string($schemaXML,NULL,NULL,"xsd",true);
    $schema->asXML($filename);
//    echo "Finished processing $filename\n*********** Contents :\n\n";
//    echo file_get_contents($filename);
  } else {
      exit("Failed to open $filename.");
  }
?>
