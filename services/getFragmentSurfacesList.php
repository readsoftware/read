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
* getFragmentSurfacesList
*
* get list of fragments for a given part
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Services
*/
define('ISSERVICE',1);
ini_set("zlib.output_compression_level", 5);
ob_start();

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies


$dbMgr = new DBManager();

//check and validate parameters
$data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
if (!$data) {
	error_log("invalid request - not enough or invalid parameters");
	$retval = '';
} else {
	//get parameters
	if (isset($data['frgID']) && is_numeric($data['frgID'])) {
		$frgID = intval($data['frgID']);
		if (!is_int($frgID)) {
			$frgID = null;
		}
	}
}

if ($frgID) {
	$query = 	"select srf_id, srf_number,".
						"(case when srf_label is null then concat('surf-',srf_id) else srf_label end) as srfdisplay,".
						" srf_label, cast(srf_text_ids as int[]) as srftxtids ".
						'from surface '.
						"where srf_fragment_id = $frgID ".
						'order by srf_id';

	$dbMgr->query($query);
	if ($dbMgr->getError()) {
		error_log($dbMgr->getError());
	} else {
		$retVal = $dbMgr->fetchAllResultRows(false);
	}
}
if (array_key_exists("callback",$_REQUEST)) {
  $cb = $_REQUEST['callback'];
  if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
    print $cb."(".json_encode($retVal).");";
  }
} else {
  print json_encode($retVal);
}
?>
