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
 * Service to save the 3D model UID of a text.
 *
 * The service only accept POST request. The following keys can be specified in
 * the request body.
 *
 * - txtID: (required) The text ID.
 * - uid: (optional) The 3D model UID. If omitted or set to empty, it will delete
 *   the UID of the text.
 *
 * @author      Yang Li
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

header("Content-type: text/javascript");
header('Cache-Control: no-cache');
header('Pragma: no-cache');

require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies
require_once (dirname(__FILE__) . '/../model/entities/Text.php');
require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');

$errors = [];
$response = [];

$txtID = empty($_POST['txtID']) ? null : $_POST['txtID'];
$uid = empty($_POST['uid']) ? null : $_POST['uid'];

if (empty($txtID)) {
  $errors[] = 'Missing text ID';
} else {
  $text = new Text($txtID);
  if ($text->hasError()) {
    $errors[] = 'Unable to find the text';
  } else {
    if (isset($uid)) {
      $text->set3DModelUID($uid);
    } else {
      $text->clear3DModelUID();
    }
    $text->save();
  }
}

if (count($errors) > 0) {
  $response['success'] = FALSE;
  $response['errors'] = $errors;
} else {
  $response['success'] = TRUE;
}

print json_encode($response);
