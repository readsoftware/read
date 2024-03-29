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
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/
ini_set("zlib.output_compression_level", 5);
ob_start('ob_gzhandler');

require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once dirname(__FILE__) . '/../model/entities/Term.php';
require_once dirname(__FILE__) . '/../model/entities/Terms.php';

header('Content-type: text/javascript');
header('Cache-Control: no-transform,private,max-age=300,s-maxage=900');

$tagsInfo = getTagsInfo();
if ($tagsInfo && count($tagsInfo) > 0 ){
  if (array_key_exists('tags',$tagsInfo)) {
    print  "var tagInfo = ".json_encode($tagsInfo['tags'],true).";\n";
  }
  if (array_key_exists('entTagToLabel',$tagsInfo)) {
    print  "var entTagToLabel = ".json_encode($tagsInfo['entTagToLabel'],true).";\n";
  }
  if (array_key_exists('entTagToPath',$tagsInfo)) {
    print  "var entTagToPath = ".json_encode($tagsInfo['entTagToPath'],true).";\n";
  }
  if (array_key_exists('tagIDToAnoID',$tagsInfo)) {
    print  "var tagIDToAnoID = ".json_encode($tagsInfo['tagIDToAnoID'],true).";\n";
  }
}

?>
