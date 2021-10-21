<?php
  require_once (dirname(__FILE__) . '/../model/utility/graphemeCharacterMap.php');
  print '_graphemeMap = '. json_encode($graphemeCharacterMap,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>


