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
  * exportHTMLGlossary
  *
  *  A service that returns HTML encoding of the catalog (unrestricted type)
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Services Classes
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header("Content-type: text/html;  charset=utf-8");
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
  require_once dirname(__FILE__) . '/../model/entities/Compounds.php';
  require_once dirname(__FILE__) . '/../model/entities/Images.php';
  require_once (dirname(__FILE__) . '/../viewer/php/viewutils.php');//get utilities for viewing
//  $userID = array_key_exists('userID',$_REQUEST) ? $_REQUEST['userID']:12;
  $catID = (array_key_exists('catID',$_REQUEST)? $_REQUEST['catID']:null);
  $exportFilename = (array_key_exists('filename',$_REQUEST)? $_REQUEST['filename']:null);
  $isDownload = (array_key_exists('download',$_REQUEST)? $_REQUEST['download']:null);
  $isStaticView = (array_key_exists('staticView',$_REQUEST)? ($_REQUEST['staticView']==0?false:true):false);
  $useTranscription = (!array_key_exists('usevalue',$_REQUEST)? true:false);
  $hideHyphens = (!array_key_exists('showhyphens',$_REQUEST)? true:false);
  $refresh = ((array_key_exists('refreshWordMap',$_REQUEST) ? $_REQUEST['refreshWordMap']:
                      (array_key_exists('refreshLookUps',$_REQUEST))? $_REQUEST['refreshLookUps']:
                       (defined('DEFAULTHTMLGLOSSARYREFRESH')?DEFAULTHTMLGLOSSARYREFRESH:0)));


  list($result,$text) = getCatalogHTML($catID,$isStaticView,$refresh,$useTranscription,$hideHyphens);
  if ($result == "success") {
    if ($isDownload) {
      header("Content-Disposition: attachment; filename=readGlossary.html");
      header("Expires: 0");
    }
    echo $text;
  } else {
    startLog();
    logAddMsgExit($text);
  }
  exit;

?>
