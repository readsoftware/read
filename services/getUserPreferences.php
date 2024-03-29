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
* getUserPreferences
*
* Load the user's preferences.
*
* Preference include default attributions, viewability, and edit control among others.
* Arguments passed as name=value where name is a valid preference name will change the value
* for that preference before returning an array of all preferences.
* Preferences are initialized using the default array and stored in the $_SESSION['ka_UserPrefs'],
* later they may be maintained on a per DB basis.

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
ob_start('ob_gzhandler');

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../services/clientDataUtils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Attribution.php');


$retVal = array('userDefPrefs' => getUserPreferences(DBNAME),
                'userUIList' => getUserUGrpList());//get current UI set
if ($retVal['userDefPrefs'] &&
    $retVal['userDefPrefs']['defaultAttributionIDs'] &&
    count($retVal['userDefPrefs']['defaultAttributionIDs']) > 0 ) {
    foreach ($retVal['userDefPrefs']['defaultAttributionIDs'] as $atbID){
      if ($atbID){
          addNewEntityReturnData('atb',new Attribution($atbID));
      }
    }
}
if (count($entities)) {
  $retVal["entities"] = $entities;
}

  //return preferences strucuture
print json_encode($retVal);

?>
