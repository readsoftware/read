<?php
  require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');
  $sc2CharLookup = array();

  function addScCharPair($str, $children) {
    global $sc2CharLookup;
    foreach ($children as $key => $value) {
      if ($key == "typ" || $key == "styp" || $key == "ssrt" ){//skip
        continue;
      } else if ($key == "srt"){//add to lookup
        if (!array_key_exists($value,$sc2CharLookup)) {
          $sc2CharLookup[$value] = array($str);
        } else {
          array_push($sc2CharLookup[$value],$str);
        }
      } else {
        addScCharPair($str.$key,$value);
      }
    }
  }

  foreach ($graphemeCharacterMap as $key=>$value) {
    addScCharPair($key,$value);
  }
  ksort($sc2CharLookup);
  print 'sortCodeToCharLookup = '. json_encode($sc2CharLookup,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>


