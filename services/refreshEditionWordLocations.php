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
  * refreshEditionWordLocations.php
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

  $dbMgr = new DBManager();
  $retVal = array();
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
  $termInfo = getTermInfoForLangCode('en');
  $errors = array();
  $warnings = array();
  //find catalogs
  if ($ednID) {
    $edition = new Edition($ednID);
  }else{
    $edition = null;
  }
  if (!$edition || $edition->hasError()) {//no edition or unavailable so warn
    echo "Usage: refreshEditionWordLocations.php?db=yourDBName&ednID=###";
  } else {
    $textSeqTypeID = Entity::getIDofTermParentLabel('Text-SequenceType');//warning!!!! term dependency
    $allTokCmpGIDsQuery = "select array_agg(c.comp) ".
                          "from (select unnest(td.seq_entity_ids::text[]) as comp ".
                                "from sequence td ".
                                     "left join sequence txt on concat('seq:',td.seq_id) = ANY(txt.seq_entity_ids) ".
                                     "left join edition on txt.seq_id = ANY(edn_sequence_ids) ".
                                "where txt.seq_type_id = $textSeqTypeID and edn_id = $ednID) c;";
    $dbMgr = new DBManager();
    if (!$dbMgr || $dbMgr->getError()) {
      exit("error loading dataManager");
    }
    $dbMgr->query($allTokCmpGIDsQuery);
    if ($dbMgr->getError()) {
      exit("error for querying words GIDs ".$dbMgr->getError());
    } else if ($dbMgr->getRowCount() < 1) {
      exit("error for querying first wordGIDs row count is 0");
    } else {
      $row = $dbMgr->fetchResultRow();
      $ednTokCmpGIDs = explode(",",trim($row[0],"{}"));
      foreach ($ednTokCmpGIDs as $gid) {
        list($prefix,$id) = explode(':',$gid);
        $entity = EntityFactory::createEntityFromGlobalID($gid);
        if (!$entity) {
          echo "Error accessing word $gid - ".EntityFactory::$error;
        } else if($entity->isMarkedDelete()){
          echo "Error word $gid is marked for delete and should be removed from tokens list";
        } else if($entity->isReadonly()){
          echo "Error word $gid is readonly and cannot be updated, owner id is ".$entity->getOwnerID();
        } else {
          $entity->updateLocationLabel();
          $entity->save();
          echo "Word $gid ".$entity->getValue()." has location of ".$entity->getLocation()."\n";
        }
      }
    }
  }

?>
