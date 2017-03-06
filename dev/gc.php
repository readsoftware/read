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
  * GC is a garbage collection utility
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Entity Classes
  */
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control

if (!isSysAdmin()) {
  echo "insufficient privileges.";
  return;
}
$prefix2TableName = array(//placed in order of delete top down cascade.
    "ano"=>"annotation",
    "atb"=>"attribution",
    "prt"=>"part",
    "frg"=>"fragment",
    "img"=>"image",
    "col"=>"collection",
    "itm"=>"item",
    "run"=>"run",
    "lin"=>"line",
    "spn"=>"span",
    "srf"=>"surface",
    "bln"=>"baseline",
    "seg"=>"segment",
    "edn"=>"edition",
    "tmd"=>"textMetadata",
    "txt"=>"text",
    "seq"=>"sequence",
    "lem"=>"lemma",
    "inf"=>"inflection",
    "cmp"=>"compound",
    "tok"=>"token",
    "gra"=>"grapheme",
    "scl"=>"syllablecluster",
    "trm"=>"term",
    "cat"=>"catalog",
    "bib"=>"bibliography",
    "dat"=>"date",
    "era"=>"era");
  $dbMgr = new DBManager();

foreach ($prefix2TableName as $prefix => $tableName) {
  $qStr = "select array_agg(".$prefix."_id) from $tableName where ".($prefix != "srf" ?$prefix."_owner_id = 1 and ":"")."5 = ANY(".$prefix."_visibility_ids)";
  $dbMgr->query($qStr);
  if ($dbMgr->getRowCount()) {
    $ids = $dbMgr->fetchResultRow();
    $ids = $ids[0];
    if ($ids) {
      print "$tableName has ".$ids." marked for delete <br>";
      $ids = explode(',',substr($ids,1,-1));
      foreach ($ids as $id) {
        $qStr = "select * from $tableName where ".$prefix."_id = $id";
        $dbMgr->query($qStr);
        $row = $dbMgr->fetchResultRow(0,false,PGSQL_ASSOC);
        $qStr = "delete from $tableName where ".$prefix."_id = $id";
        $dbMgr->query($qStr);
        $errStr = $dbMgr->getError();
        if ($errStr) {
          print "error encountered trying to delete $prefix:$id from $tableName - $errStr <br/>";
        } else {
          print "deleted $prefix:$id from $tableName - $errStr <br/>";
          if (array_key_exists('modified',$row)) {
            unset($row['modified']);
          }
          $values = join(',',array_values($row));
          $values = preg_replace("/\{/","'{",$values);
          $values = preg_replace("/\}/","}'",$values);
          $values = preg_replace("/\,\,/",",NULL,",$values);
          $restoreStr = "INSERT INTO $tableName (".
                        join(',',array_keys($row)).") <br/>VALUES (".
                        $values.")<br/>";
          print $restoreStr;
        }
      }
    }
  } else if ($dbMgr->getError()) {
    print $dbMgr->getError()."<br/>";
  }
}
?>
