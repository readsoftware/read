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
 * Service to save the 3D model annotation of a segment.
 *
 * The service only accept POST request. The following keys can be specified in
 * the request body.
 *
 * - segID: (required) The segment ID.
 * - coords: (optional) The 3D position of the 3D annotation.
 * - cameraPosition: (optional) The camera position of the 3D annotation.
 * - cameraTarget: (optional) The camera target of the 3D annotation.
 *
 * If only the segment ID is passed in, it will delete the current annotation
 * of the segment.
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
require_once (dirname(__FILE__) . '/../model/entities/Segment.php');
require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');
require_once (dirname(__FILE__) . '/clientDataUtils.php');

$errors = [];
$response = [];

$segID = empty($_POST['segID']) ? null : $_POST['segID'];
$coords = empty($_POST['coords']) ? null : $_POST['coords'];
$cameraPosition = empty($_POST['cameraPosition']) ? null : $_POST['cameraPosition'];
$cameraTarget = empty($_POST['cameraTarget']) ? null : $_POST['cameraTarget'];

if (empty($segID)) {
  $errors[] = 'Missing segment ID';
} else {
  $segment = new Segment($segID);
  if ($segment->hasError()) {
    $errors[] = 'Unable to find the segment';
  } else {
    if (isset($coords) || isset($cameraPosition) || isset($cameraTarget)) {
      $segment->set3DModelAnnotations([
        [
          'coords' => $coords,
          'cameraPosition' => $cameraPosition,
          'cameraTarget' => $cameraTarget,
        ],
      ]);
    } else {
      $segment->clear3DModelAnnotations();
    }
    $segment->save();
  }
}

if (count($errors) > 0) {
  $response['success'] = FALSE;
  $response['errors'] = $errors;
} else {
  $response['success'] = TRUE;
}

print json_encode($response);
