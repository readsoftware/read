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
  * php read/dev/calcTextViewerCache.php -d testdb -ckn CKI0001 -userID 4
  * to recalculate cache for editions of a single text
  * php read/dev/calcTextViewerCache.php -d testdb -ckn all -userID 4
  * to recalculate cache for editions of all text
	* browser:  (must be logged in)
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ckn=CKI0001
  * to recalculate cache for editions of a single text
  * http://localhost/read/dev/calcTextViewerCache.php?db=testdb&ckn=all
  * to recalculate cache for editions of all text
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
	if (!$ckn) {
		echo "insufficient information to update cache";
  } else {
		$extraCondition = "";
		if (strtolower($ckn) != 'all') { // single text case
			$extraCondition = " and txt_ckn = '$ckn'";
		}
		$query = "select edn_id from edition".
						" where edn_text_id in (select txt_id from text ".
																		"where txt_owner_id != 1$extraCondition".
																	 " order by txt_id) order by edn_id;";
		$dbMgr->query($query);
		$ednIDs = null;
		if ($dbMgr->getError() || $dbMgr->getRowCount() == 0) {
			print "error updating viewer cache for $ckn";
		} else {
			$ednIDs = $dbMgr->fetchAllResultRows();
		}
		if ($ednIDs && count($ednIDs) > 0) {
			foreach ($ednIDs as $ednID) {
				$ednID = $ednID['edn_id'];
				getEditionsStructuralViewHtml(array($ednID), true);
				print "cache updated for $ckn, edn$ednID";
			}
		}
  }

?>
