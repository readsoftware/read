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
  * loadCatalogEntities
  *
  *  A service that returns a json structure of the catalog (unrestricted type) requested along with its
  *  lemma and the lemma's inflections, compounds, and tokens.
  *  There is NO CACHING of this information.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header("Content-type: text/plain");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

//  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once dirname(__FILE__) . '/../model/entities/Tokens.php';
  require_once dirname(__FILE__) . '/../model/entities/Lemmas.php';
  require_once dirname(__FILE__) . '/../model/entities/Inflections.php';
  require_once dirname(__FILE__) . '/../model/entities/Edition.php';
  require_once dirname(__FILE__) . '/../model/entities/Text.php';
  require_once dirname(__FILE__) . '/../model/entities/Sequences.php';
  require_once dirname(__FILE__) . '/../model/entities/Catalogs.php';
  require_once dirname(__FILE__) . '/../model/entities/JsonCache.php';
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
  require_once dirname(__FILE__) . '/../model/entities/Baselines.php';
  require_once (dirname(__FILE__) . '/../viewer/php/viewutils.php');//get utilities for viewing
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;
//  echo "mem limit = ".ini_get('memory_limit');
  ob_flush();
  $dbMgr = new DBManager();
  $retVal = array();
  $catID = (array_key_exists('catID',$_REQUEST)? $_REQUEST['catID']:null);
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $refresh = (array_key_exists('refresh',$_REQUEST)? $_REQUEST['refresh']:0);//default is set to include recalc word location
  $termInfo = getTermInfoForLangCode('en');
  $termLookup = $termInfo['labelByID'];
  $term_parentLabelToID = $termInfo['idByTerm_ParentLabel'];
  $getWrdTag2GlossaryPopupHtmlLookup = array();
  $errors = array();
  $warnings = array();
  //find catalogs
  if ($catID) {
    $catalog = new Catalog($catID);
  }else{
    $catalog = null;
  }
  if (!$catalog || $catalog->hasError()) {//no catalog or unavailable so warn
    echo "Usage: updateWordLocationLabelMap.php?db=yourDBName&catID=###[&ednID=###]";
  } else {
    if(USEVIEWERCACHING) {
      $cacheKey = "glosscat$catID"."edn".($ednID?$ednID:"all");
      $dbMgr = new DBManager();
      if (!$dbMgr || $dbMgr->getError()) {
        exit("error loading dataManager");

      }
      $dbMgr->query("select * from jsoncache where jsc_label = '$cacheKey' and not jsc_owner_id = 1;");
      $cnt = $dbMgr->getRowCount();
      if ($cnt == 0) {
        $jsonCache = new JsonCache();
        $jsonCache->setLabel($cacheKey);
        $jsonCache->setVisibilityIDs($catalog->getVisibilityIDs());
        $jsonCache->setOwnerID($catalog->getOwnerID());
      } else {
        if ($cnt > 1) {
          for ($i = 1; $i < $cnt; $i++) {
            $temp = new JsonCache($dbMgr->fetchResultRow());
            $temp->markForDelete();
          }
        }
        $jsonCache = new JsonCache($cacheKey);
      }
    }
    $getWrdTag2GlossaryPopupHtmlLookup = getWrdTag2GlossaryPopupHtmlLookup($catID,$ednID, $refresh);
    echo print_r($getWrdTag2GlossaryPopupHtmlLookup,true);
    if(USEVIEWERCACHING) {
      $jsonCache->setJsonString(json_encode($getWrdTag2GlossaryPopupHtmlLookup));
      $jsonCache->save();
      unset($jsonCache);
    }
  }

?>
