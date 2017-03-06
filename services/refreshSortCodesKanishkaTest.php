<?php
  define("DBNAME",'kanishkatest');
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  impersonateUser(13,'stevewh',array(1=>1,13=>1,4=>1));
  include 'refreshSortCodes.php';
  restoreUser();
?>
