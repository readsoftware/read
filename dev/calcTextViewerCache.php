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
  * calcTextViewerCache 
  *
  *  A script that recalculates the cache for editions use for the READ Viewer and
	* it can be called from the command line or browser.
	* terminal:
  * php read/dev/calcTextViewerCache.php -d testdb -ckn CKI0001.1 -userID 4
  * to recalculate cache for editions of a single text
  * php read/dev/calcTextViewerCache.php -d testdb -ckn all -userID 4
  * to recalculate cache for editions of all text
	* browser:  (must be logged in)
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ckn=CKI0001.1
  * to recalculate cache for editions of a single text
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ckn=CKI0001
  * to recalculate cache for editions of all texts of an item
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ckn=CKI1_CKI15
  * to recalculate cache for editions of all texts of items 1 thru 15
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ckn=all
  * to recalculate cache for editions of all text
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ednids=1,2
  * to recalculate cache for editions 1 and 2
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ednids=1
  * to recalculate cache for editions 1
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&catid=1
  * to recalculate cache for editions of catalog id = 1
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
	*/
	$isCmdLineLaunch = false;
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
    if (@$ARGV['-ednids']) $_REQUEST['ednids'] = $ARGV['-ednids'];
    if (@$ARGV['-catid']) $_REQUEST['catid'] = $ARGV['-catid'];
    if (@$ARGV['-userID']) $userID = $ARGV['-userID'];
  }

  if (!$isCmdLineLaunch) { //browser case
		header("Content-type: text/javascript");
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
	}

  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../viewer/php/viewutils.php');//get viewer utilities

  $dbMgr = new DBManager();
  $retVal = array();

  $ckn = (array_key_exists('ckn',$_REQUEST)? $_REQUEST['ckn']:null);
  $catID = (array_key_exists('catid',$_REQUEST)? $_REQUEST['catid']:null);
  $ednIDs = (array_key_exists('ednids',$_REQUEST)? $_REQUEST['ednids']:null);
  $ckns = null;
	if (!$ckn && !$ednIDs && !$catID) {
		print "insufficient information to update cache \n";
  } else if ($ckn) {
		$extraCondition = "";
    if (strtolower($ckn) == 'all') { 
      $query = "select edn_id from edition ".
              "where edn_owner_id != 1 and ".
                    "edn_text_id in (select txt_id from text ".
                                      "where txt_owner_id != 1 ".
                                      "order by txt_id) order by edn_id;";
      $dbMgr->query($query);
      $ednIDs = null;
      if ($dbMgr->getError() || $dbMgr->getRowCount() == 0) {
        print "error updating viewer cache for $ckn \n";
      } else {
        $ednIDs = $dbMgr->fetchAllResultRows();
        if ($ednIDs && count($ednIDs) > 0) {
          $tempIDs = array();
          foreach ($ednIDs as $ednID) {
            array_push($tempIDs,$ednID['edn_id']);
          }
          $ednIDs = $tempIDs;
        }
      }
    } else { // parse ckn param, could be list and could contain ranges
      if (strpos(',',$ckn) !== false) { // comma delimited list of ckn or ckn ranges
        $ckns = explode(',',$ckns);
      } else { // single ckn or ckn range
        $ckns = array($ckn);
      }
      $cknList = array();
      $id2 = $ckn2 = null;
      foreach ($ckns as $ckn) {
        if (strpos($ckn,'_') !== false && strpos($ckn,'_') == strrpos($ckn,'_')) { // ckn range
          list($ckn, $ckn2) = explode('_',$ckn);
        }
        if (!$ckn2){
          array_push($cknList, $ckn);
          continue;
        }
        if (strlen($ckn)>7 || $ckn2 && strlen($ckn2)>7 ) { //unknown format !!dependency
          print "illegal format using $ckn"."_$ckn2 \n";
          continue;
        } else { //ensure ckns are formatted
          if (preg_match("/(ck[cdim])(\d+)/i",$ckn,$match)) {
            $prefix = strtoupper($match[1]);
            $id = $match[2];
            $ckn = $prefix.str_pad($id,4,"0",STR_PAD_LEFT);//zero fill
            array_push($cknList, $ckn);
          }
          if ($ckn2 && preg_match("/(ck[cdim])(\d+)/i",$ckn2,$match)) {//range so expand 
            $prefix = strtoupper($match[1]);
            $id2 = $match[2];
            $ckn2 = $prefix.str_pad($id2,4,"0",STR_PAD_LEFT);//zero fill
            $id = intval($id);
            $id2 = intval($id2);
            if ( $id + 1 < $id2) {
              for ($i = $id+1; $i < $id2; $i++) {
                $id = strval($i);
                $rangeCkn = $prefix.str_pad($id,4,"0",STR_PAD_LEFT);//zero fill
                array_push($cknList, $rangeCkn); //add computed ckn to list
              }
            }
            array_push($cknList, $ckn2);//add end of range ckn to list
          }
        }
      }
      $ednIDs = array();
      foreach ($cknList as $ckn) {
        $query = "select edn_id from edition ".
                 "where edn_owner_id != 1 and ".
                       "edn_text_id in (select txt_id from text ".
                                        "where txt_owner_id != 1 and ".
                                              "txt_ckn like '$ckn%' ".
                                        "order by txt_id) order by edn_id;";
        $dbMgr->query($query);
        if ($dbMgr->getError()) {
          print "error updating viewer cache for $ckn - error: $dbMgr->getError() \n";
        } else if ($dbMgr->getRowCount() == 0) {
          print "warning no editions found for $ckn \n";
        } else {
          $tempIDs = $dbMgr->fetchAllResultRows();
          if ($tempIDs && count($tempIDs) > 0) {
            foreach ($tempIDs as $ednID) {
              array_push($ednIDs,$ednID['edn_id']);
            }
          }
        }
      }
    }
  } else if ($catID) {
    $catalog = new Catalog($catID);
    if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
      print "unable to open catalog $catID aborting cache updated for $ckn, edn$ednID \n";
    } else {
      $ednIDs = $catalog->getEditionIDs();
    }
  } else {
    $ednList = array();
    $ednIDs = explode(",",$ednIDs);
    foreach ($ednIDs as $ednID) {
      $ednID2 = null;
      if (strpos($ednID,'_') !== false && strpos($ednID,'_') == strrpos($ednID,'_')) { // ckn range
        list($ednID, $ednID2) = explode('_',$ednID);
      }
      if (strlen($ednID)>7 || $ednID2 && strlen($ednID2)>7 ) { //unknown format !!dependency
        print "illegal format using $ednID"."_$ednID2 \n";
        continue;
      } else { //expand ranges
        array_push($ednList, intval($ednID));
        if ($ednID2) {//range so expand 
          $id = intval($ednID);
          $id2 = intval($ednID2);
          if ( $id + 1 < $id2) {
            for ($i = $id+1; $i < $id2; $i++) {
              array_push($ednList, $i); //add computed ednID to list
            }
          }
          array_push($ednList, $id2);//add end of range ednID to list
        }
      }
    }
    $ednIDs = $ednList;
  }
  if ($ednIDs && count($ednIDs) > 0) {
    foreach ($ednIDs as $ednID) {
      getEditionsStructuralViewHtml(array($ednID), true);
      print "cache updated for edn$ednID \n";
    }
  }

?>
