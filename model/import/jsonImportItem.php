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
* jsonImportItem.php
* This service imports or updates READs upper model metadata and returns json data READ client objects for 
* all modified entities 
* 
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/
	define('ISSERVICE',1);
	ini_set("zlib.output_compression_level", 5);
	ob_start();
	
	header("Content-type: text/javascript");
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	
  //DEPENDENCIES
	require_once (dirname(__FILE__) . '/../../common/php/DBManager.php');//get database interface
	require_once (dirname(__FILE__) . '/../../common/php/userAccess.php');
  include_once dirname(__FILE__) . '/../entities/Annotation.php';
  include_once dirname(__FILE__) . '/../entities/Attribution.php';
  include_once dirname(__FILE__) . '/../entities/Bibliography.php';
  include_once dirname(__FILE__) . '/../entities/Baselines.php';
  include_once dirname(__FILE__) . '/../entities/Fragments.php';
  include_once dirname(__FILE__) . '/../entities/Items.php';
  include_once dirname(__FILE__) . '/../entities/Images.php';
  include_once dirname(__FILE__) . '/../entities/MaterialContexts.php';
  include_once dirname(__FILE__) . '/../entities/Runs.php';
  include_once dirname(__FILE__) . '/../entities/Parts.php';
  include_once dirname(__FILE__) . '/../entities/Surfaces.php';
  include_once dirname(__FILE__) . '/../entities/Texts.php';
  include_once dirname(__FILE__) . '/../utility/parser.php';
	require_once (dirname(__FILE__) . '/../../services/clientDataUtils.php');

	$dbMgr = new DBManager();
	$retVal = array();
	$errors = array();
	$warnings = array();
	
  if (isset($_REQUEST['importFilename'])) {
    include_once dirname(__FILE__) . '/'.$_REQUEST['importFilename'];
  } else if (isset($_REQUEST['data'])) {
    $data = json_decode($_REQUEST['data']);
    if (isset($data['itemIdNo'])) {
      $itemIdNo = $data['itemIdNo'];
    }
    if (isset($data['imageDefs'])) {
      $imageDefs = $data['imageDefs'];
    }
    if (isset($data['itemTexts'])) {
      $itemTexts = $data['itemTexts'];
		}
    if (isset($data['textMetadatas'])) {
      $textMetadatas = $data['textMetadatas'];
    }
    if (isset($data['importItem'])) {
      $importItem = $data['importItem'];
		}
		if (isset($data['itemParts'])) {
      $itemParts = $data['itemParts'];
    }
    if (isset($data['materialContexts'])) {
      $materialContexts = $data['materialContexts'];
    }
    if (isset($data['partsFragments'])) {
      $partsFragments = $data['partsFragments'];
    }
    if (isset($data['fragmentSurfaces'])) {
      $fragmentSurfaces = $data['fragmentSurfaces'];
    }
  } else {
    echo "no filename or data supplied";
    exit;
  }

  if (isset($itemIdNo)) {
    $items = new Items("itm_idno = '$itemIdNo'","itm_id");
    if (!$items || $items->getError() || $v->getCount() == 0) {
      echo "invalid item inventory number $itemIdNo supplied";
      exit;
    }
    $item = $items->current();
    $itmID = $item->getID();
    if (!$itmID || $item->hasError()) {
      echo "invalid item for inventory number $itemIdNo supplied";
      exit;
    }
  }

  //PAGE VARIABLES
  $userPrefs = getUserPreferences();
  $visibilityIDs = $userPrefs['defaultVisibilityIDs'];  // DEFAULT VISIBILITY SET TO PUBLIC
  $ownerID = $userPrefs['defaultEditUserID'];
  $defAttrIDs = $userPrefs['defaultAttributionIDs'];
  $updateEntities = array();
  $entityNonce2entID = array();

  //Process Images
  if (count($errors) == 0 && isset($imageDefs) && count($imageDefs) >0) {
    foreach ($imageDefs as $imgNonceId => $imgMetadata) {
			if (intval($imgNonceId)) { //int so existing image
				$image = new Image(intval($imgNonceId));
				if (!$image || $image->hasError() && $image->getID() != intval($imgNonceId)) {
					array_push($warnings,"Load of image ".$imgNonceId." failed ignoring");
					continue;
				}
      } else {
				$image = new Image();
				$image->storeScratchProperty('nonce',$imgNonceId);
				if (isset($itemIdNo))
				$image->storeScratchProperty('itmIdNo',$itemIdNo);
				$image->setOwnerID($ownerID);
				$image->setVisibilityIDs($visibilityIDs);
		  }
      if (isset($image) && !$image->getURL() && !isset($imgMetadata['url'])) {
        continue;
      } else if (isset($imgMetadata['url'])) {
        $image->setURL($imgMetadata['url']);
      }
      if (isset($imgMetadata['polygon'])) {
        $image->setBoundary($imgMetadata['polygon']);
      }
      if (isset($imgMetadata['title'])) {
        $image->setTitle($imgMetadata['title']);
      }
      if (isset($imgMetadata['type'])) {
        $typeID = $image->getIDofTermParentLabel(strtolower($imgMetadata['type']).'-imagetype');//term dependency
        if ($typeID) {
          $image->setType($typeID);
        } else {
          $image->storeScratchProperty('type',$imgMetadata['type']);
        }
      }
      if (isset($imgMetadata['attribution'])) {
        $attrInfo = $imgMetadata['attribution'];
        if (isset($attrInfo['atbid'])) {
          $attrID = $attrInfo['atbid'];
        } else {
          $attrID = createAttribution(
                                      isset($attrInfo["title"])?$attrInfo["title"]:null,
                                      isset($attrInfo["description"])?$attrInfo["description"]:null,
                                      $visibilityIDs, null,
                                      isset($attrInfo["bibliographyid"])?$attrInfo["bibliographyid"]:null,
                                      isset($attrInfo["type"])?$attrInfo["type"]:null,
                                      isset($attrInfo["detail"])?$attrInfo["detail"]:null,
                                      isset($attrInfo["aedid"])?$attrInfo["aedid"]:null,
                                      isset($attrInfo["usergroupid"])?$attrInfo["usergroupid"]:null
                                    );
        }
        if (!$image->getAttributionIDs() || $attrID) {
          if (!$attrID) {
            $attrIDS = $defAttrIDs;
          } else {
            $attrIDS = array_unique(array_merge(array($attrID), $image->getAttributionIDs()));
          }
          $image->setAttributionIDs($attrIDS);
        }
      }
      $image->save();
      if (!$image->hasError()) {
				$entityNonce2entID[$imgNonceId] = $image->getID();
				array_push($updateEntities,$image);
      } else {
				array_push($warnings,"Save of image nonce id $imgNonceId  has errors".$image->getErrors(true).", ignoring");
      }
    }
  }

  //ITEM Texts
  $itmTextIDs = array();
  if(count($errors) == 0 && isset($itemTexts) && count($itemTexts) > 0) {
    foreach ($itemTexts as $txtNonceId => $txtData) {
			if (intval($txtNonceId)) { //int so existing image
				$text = new Text(intval($txtNonceId));
				if (!$text || $text->hasError() && $text->getID() != intval($txtNonceId)) {
					array_push($warnings,"Load of text ".$txtNonceId." failed ignoring");
					continue;
				}
      } else {
				$text = new Text();
				$text->storeScratchProperty('nonce',$txtNonceId);
				if (isset($itemIdNo)) {
          $text->storeScratchProperty('itmIdNo',$itemIdNo);
        }
				$text->setOwnerID($ownerID);
				$text->setVisibilityIDs($visibilityIDs);
		  }
      if (isset($text) && !$text->getCKN() && !isset($txtData['ckn'])) {
        continue;
      } else if (isset($txtData['ckn'])) {
        $text->setCKN($txtData['ckn']);
      }
      if (isset($txtData['shortName'])) {
        $text->setRef($txtData['shortName']);
      }
      if (isset($txtData['title'])) {
        $text->setTitle($txtData['title']);
      }
      if (isset($txtData['types']) && count($txtData['types'])>0) {
        $types = array();
        $typeIDs = array();
        foreach ($txtData['types'] as $strType) {
          $typeID = $text->getIDofTermParentLabel(strtolower($strType).'-texttype');//term dependency
          if ($typeID) {
            array_push($typeIDs,$typeID);
          } else {
            array_push($types,$strType);
          }
        }
        if (count($typeIDs)) {
          $text->setTypeIDs($typeIDs);
        }
        if (count($types)) {
          $text->storeScratchProperty('types',join(',',$types));
        }
      }
      if (isset($txtData['attribution'])) {
        $attrInfo = $txtData['attribution'];
        if (isset($attrInfo['atbid'])) {
          $attrID = $attrInfo['atbid'];
        } else {
          $attrID = createAttribution(
                                      isset($attrInfo["title"])?$attrInfo["title"]:null,
                                      isset($attrInfo["description"])?$attrInfo["description"]:null,
                                      $visibilityIDs, null,
                                      isset($attrInfo["bibliographyid"])?$attrInfo["bibliographyid"]:null,
                                      isset($attrInfo["type"])?$attrInfo["type"]:null,
                                      isset($attrInfo["detail"])?$attrInfo["detail"]:null,
                                      isset($attrInfo["aedid"])?$attrInfo["aedid"]:null,
                                      isset($attrInfo["usergroupid"])?$attrInfo["usergroupid"]:null
                                    );
        }
        if (!$text->getAttributionIDs() || $attrID) {
          if (!$attrID) {
            $attrIDS = $defAttrIDs;
          } else {
            $attrIDS = array_unique(array_merge(array($attrID), $text->getAttributionIDs()));
          }
          $text->setAttributionIDs($attrIDS);
        }
      }
      if (isset($txtData['image_ids'])) {
        $textImgIDs = array();
        foreach ($txtData['image_ids'] as $imgNonce) {
          if (isset($entityNonce2entID[$imgNonce])) {
            array_push($textImgIDs,$entityNonce2entID[$imgNonce]);
          } else if (intval($imgNonce)) {
            array_push($textImgIDs,intval($imgNonce));
          }
        }
        if (count($textImgIDs)) {
          $text->setImageIDs($textImgIDs);
        }
      }
      $text->save();
      if (!$text->hasError()) {
        $entityNonce2entID[$txtNonceId] = $text->getID();
        array_push($itmTextIDs,$text->getID()); 
				array_push($updateEntities,$text);
      } else {
				array_push($warnings,"Save of text nonce id $txtNonceId  has errors".$text->getErrors(true).", ignoring");
      }
    }
  }


  //ITEM TextMetadatas
  if(count($errors) == 0 && isset($textMetadatas) && count($textMetadatas) > 0) {
    foreach ($textMetadatas as $tmdNonceId => $txtMetadata) {
			if (intval($tmdNonceId)) { //int so existing image
				$textMD = new TextMetadata(intval($tmdNonceId));
				if (!$textMD || $textMD->hasError() && $textMD->getID() != intval($tmdNonceId)) {
					array_push($warnings,"Load of text metadata ".$tmdNonceId." failed ignoring");
					continue;
				}
      } else {
				$textMD = new TextMetadata();
				$textMD->storeScratchProperty('nonce',$tmdNonceId);
				$textMD->setOwnerID($ownerID);
				$textMD->setVisibilityIDs($visibilityIDs);
		  }
      if (isset($textMD) && !$textMD->getTextID() && !isset($txtMetadata['textid'])) {
        continue;
      } else if (isset($txtMetadata['textid'])) {
        $txtNonceId = $txtMetadata['textid'];
        if (isset($entityNonce2entID[$txtNonceId])) {
          $textMD->setTextID($entityNonce2entID[$txtNonceId]);
        } else if (intval($txtNonceId)) {
          $textMD->setTextID($txtNonceId);
        }
      }
      if (isset($txtMetadata['types']) && count($txtMetadata['types'])>0) {
        $types = array();
        $typeIDs = array();
        foreach ($txtMetadata['types'] as $strType) {
          $typeID = $textMD->getIDofTermParentLabel(strtolower($strType).'-metadatatype');//term dependency
          if ($typeID) {
            array_push($typeIDs,$typeID);
          } else {
            array_push($types,$strType);
          }
        }
        if (count($typeIDs)) {
          $textMD->setTypeIDs($typeIDs);
        }
        if (count($types)) {
          $textMD->storeScratchProperty('types',join(',',$types));
        }
      }
      if (isset($txtMetadata['references']) && count($txtMetadata['references'])>0) {
        $textMD->setReferenceIDs($txtMetadata['references']);
      }
      if (isset($txtMetadata['scratch'])) {
        foreach ($txtMetadata['scratch'] as $prop => $val) {
          $textMD->storeScratchProperty($prop,$val);
        }
      }
      $textMD->save();
      if (!$textMD->hasError()) {
				array_push($updateEntities,$textMD);
      } else {
				array_push($warnings,"Save of text nonce id $txtNonceId  has errors".$textMD->getErrors(true).", ignoring");
      }
    }
  }


  //ITEM
  $itmID = null;
  $item = null;
  $itemNonceID = null;
  if (count($errors) == 0 && isset($importItem) && count($importItem) > 0) {
    // get item by inventory number
    if (isset($itemIdNo)){
      $items = new Items("itm_idno = '$itemIdNo'","itm_id",null,null);
      if ($items->getCount() == 0 || $items->getError()) {
        array_push($error,"Unable to load item idno $itemIdNo - errors".$items->getErrors(true).", aborting");
      } else {
        $item = $items->current();  
        $itmID = $item->getID();
      }
    }
    //  item data and no item yet, if id then update item else create new item
    if (!$item && count($errors) == 0) {
      if (isset($importItem['id']) && intval($importItem['id'])) {
        $itmID = intval($importItem['id']);
        $item = new Item($itmID);
        if (!$item || $item->hasError() || $item->getID() != $itmID) {
          array_push($error,"Load of item ".$importItem['id']." failed, aborting");
          $itmID = $item = null;
        }
      } else {
        $item = new Item();
        if (!$item || $item->hasError()) {
          array_push($error,"Create of new item failed, aborting");
          $itmID = $item = null;
        } else {
          if (isset($importItem['id'])) {//must be nonce
            $itemNonceID = $importItem['id'];
            $item->storeScratchProperty('nonce',$itemNonceID);
          }
          $item->setOwnerID($ownerID);
          $item->setVisibilityIDs($visibilityIDs);
        }
      }
    }
    // if no errors and item then set properties
    if ($item && count($errors) == 0) {
      if (isset($importItem['title'])) {
        $item->setTitle($importItem['title']);
      }
      if (isset($importItem['description'])) {
        $item->setDescription($importItem['description']);
      }
      if (isset($importItem['idno'])) {
        $item->setIdNo($importItem['idno']);
      }
      if (isset($importItem['subject'])) {
        $typeID = $item->getIDofTermParentLabel(strtolower($importItem['subject']).'-subject');//term dependency
        if ($typeID) {
          $item->setType($typeID);
        } else {
          $item->storeScratchProperty('subject',$importItem['type']);
        }
      }
      if (isset($importItem['shape'])) {
        $shapeID = $item->getIDofTermParentLabel(strtolower($textItem['shape']).'-itemshape');//term dependency
        if ($shapeID) {
          $item->setShapeID($shapeID);
        } else {
          $item->storeScratchProperty('shape',$textItem['shape']);
        }
      }
      if (isset($importItem['measure'])) {
        $item->setMeasure($textItem['measure']);
      }
      if (isset($importItem['attribution'])) {
        $attrInfo = $importItem['attribution'];
        if (isset($attrInfo['atbid'])) {
          $attrID = $attrInfo['atbid'];
        } else {
          $attrID = createAttribution(
                                      isset($attrInfo["title"])?$attrInfo["title"]:null,
                                      isset($attrInfo["description"])?$attrInfo["description"]:null,
                                      $visibilityIDs, null,
                                      isset($attrInfo["bibliographyid"])?$attrInfo["bibliographyid"]:null,
                                      isset($attrInfo["type"])?$attrInfo["type"]:null,
                                      isset($attrInfo["detail"])?$attrInfo["detail"]:null,
                                      isset($attrInfo["aedid"])?$attrInfo["aedid"]:null,
                                      isset($attrInfo["usergroupid"])?$attrInfo["usergroupid"]:null
                                    );
        }
        if (!$item->getAttributionIDs() || $attrID) {
          if (!$attrID) {
            $attrIDS = $defAttrIDs;
          } else {
            $attrIDS = array_unique(array_merge(array($attrID), $item->getAttributionIDs()));
          }
          $item->setAttributionIDs($attrIDS);
        }
      }
      if (isset($importItem['scratch'])) {
        foreach ($importItem['scratch'] as $prop => $val) {
          if ($prop == 'textids') { //todo consider 'addtextids' as a concat fucntion
            $itemTxtIDs = array();
            foreach ($val as $txtNonce) {
              if (isset($entityNonce2entID[$txtNonce])) {
                array_push($itemTxtIDs,$entityNonce2entID[$txtNonce]);
              } else if (intval($txtNonce)) {
                array_push($itemTxtIDs,intval($txtNonce));
              }
            }
            if (count($itemTxtIDs)) {
              $item->storeScratchProperty('textids',$itemTxtIDs);
            }
          } else {
            $item->storeScratchProperty($prop,$val);
          }
        }
      }
      if (isset($importItem['image_ids'])) {
        $itemImgIDs = array();
        foreach ($importItem['image_ids'] as $imgNonce) {
          if (isset($entityNonce2entID[$imgNonce])) {
            array_push($itemImgIDs,$entityNonce2entID[$imgNonce]);
          } else if (intval($imgNonce)) {
            array_push($itemImgIDs,intval($imgNonce));
          }
        }
        if (count($itemImgIDs)) {
          $item->setImageIDs($itemImgIDs);
        }
      }
      $item->save();
      if (!$item->hasError()) {
        if ($itemNonceID) {
          $entityNonce2entID[$itemNonceID] = $item->getID();
        }
        array_push($updateEntities,$item);
      } else {
        array_push($error,"Save of Item nonce id $itemNonceID has errors".$item->getErrors(true).", aborting");
      }
    }
  }

  //PARTS
  if(count($errors) == 0 && isset($itemParts) && count($itemParts) > 0) {
    foreach ($itemParts as $prtNonceId => $prtMetadata) {
			if (intval($prtNonceId)) { //int so existing image
				$part = new Part(intval($prtNonceId));
				if (!$part || $part->hasError() && $part->getID() != intval($prtNonceId)) {
					array_push($warnings,"Load of part ".$prtNonceId." failed ignoring");
					continue;
				}
      } else {
        $part = new Part();
        $part->setOwnerID($ownerID);
        $part->setVisibilityIDs($visibilityIDs);
        $part->storeScratchProperty('nonce',$prtNonceId);
      }
      if (isset($prtMetadata['label'])) {
        $part->setLabel($prtMetadata['label']);
      }
      if (isset($prtMetadata['description'])) {
        $part->setDescription($prtMetadata['description']);
      }
      if (isset($prtMetadata['type'])) {
        $typeID = $part->getIDofTermParentLabel(strtolower($prtMetadata['type']).'-objecttype');//term dependency
        if ($typeID) {
          $part->setType($typeID);
        } else {
          $part->storeScratchProperty('type',$prtMetadata['type']);
        }
      }
      if (isset($prtMetadata['shape'])) {
        $shapeID = $part->getIDofTermParentLabel(strtolower($prtMetadata['shape']).'-objectshape');//term dependency
        if ($shapeID) {
          $part->setShapeID($shapeID);
        } else {
          $part->storeScratchProperty('shape',$prtMetadata['shape']);
        }
      }
      if (isset($prtMetadata['mediums'])) {
        $part->setMediums($prtMetadata['mediums']);
      }
      if (isset($prtMetadata['measure'])) {
        $part->setMeasure($prtMetadata['measure']);
      }
      if (isset($prtMetadata['sequence'])) {
        $part->setSequence($prtMetadata['sequence']);
      }
      if ($itmID) {
        $part->setItemID($itmID);
      }
      if (isset($prtMetadata['scratch'])) {
        foreach ($prtMetadata['scratch'] as $prop => $val) {
          $part->storeScratchProperty($prop,$val);
        }
      }
      if (isset($prtMetadata['image_ids'])) {
        $partImgIDs = array();
        foreach ($prtMetadata['image_ids'] as $imgNonce) {
          if (isset($entityNonce2entID[$imgNonce])) {
            array_push($partImgIDs,$entityNonce2entID[$imgNonce]);
          } else if (intval($imgNonce)) {
            array_push($partImgIDs,intval($imgNonce));
          }
        }
        if (count($partImgIDs)) {
          $part->setImageIDs($partImgIDs);
        }
      }
      $part->save();
      if (!$part->hasError()) {
        $entityNonce2entID[$prtNonceId] = $part->getID();
				array_push($updateEntities,$part);
      } else {
				array_push($warnings,"Save of part nonce id $prtNonceId  has errors".$part->getErrors(true).", ignoring");
      }
    }
  }

  //MATERIALCONTEXT
  if(count($errors) == 0 && isset($materialContexts) && count($materialContexts) > 0) {
    foreach ($materialContexts as $mtxNonceID => $mtxMetadata) {
			if (intval($mtxNonceID)) { //int so existing image
				$materialContext = new MaterialContext(intval($mtxNonceID));
				if (!$materialContext || $materialContext->hasError() && $materialContext->getID() != intval($mtxNonceID)) {
					array_push($warnings,"Load of materialContext ".$mtxNonceID." failed ignoring");
					continue;
				}
      } else {
        $materialContext = new MaterialContext();
        $materialContext->setOwnerID($ownerID);
        $materialContext->setVisibilityIDs($visibilityIDs);
        $materialContext->setAttributionIDs($defAttrIDs);
        $materialContext->storeScratchProperty('nonce',$mtxNonceID);
      }
      if (isset($mtxMetadata['arch_context'])) {
        $materialContext->setArchContext(json_encode($mtxMetadata['arch_context']));
      }
      if (isset($mtxMetadata['find_status'])) {
        $materialContext->setFindStatus($mtxMetadata['find_status']);
      }
      if (isset($mtxMetadata['scratch'])) {
        foreach ($mtxMetadata['scratch'] as $prop => $val) {
          $materialContext->storeScratchProperty($prop,$val);
        }
      }      
      $materialContext->save();
      if (!$materialContext->hasError()) {
        $entityNonce2entID[$mtxNonceID] = $materialContext->getID();
				array_push($updateEntities,$materialContext);
      } else {
				array_push($warnings,"Save of materialContext nonce id $mtxNonceID  has errors".$materialContext->getErrors(true).", ignoring");
      }
    }
  }


  //FRAGMENT
  if(count($errors) == 0 && isset($partsFragments) && count($partsFragments) > 0) {
    foreach ($partsFragments as $fraNonceID => $fraMetadata) {
      if (intval($fraNonceID)) { //int so existing image
         $fragment = new Fragment(intval($fraNonceID));
        if (!$fragment || $fragment->hasError() && $fragment->getID() != intval($fraNonceID)) {
          array_push($warnings,"Load of fragment ".$fraNonceID." failed ignoring");
          continue;
        }
      } else {
        $fragment = new Fragment();
        $fragment->setOwnerID($ownerID);
        $fragment->setVisibilityIDs($visibilityIDs);
        $fragment->setAttributionIDs($defAttrIDs);
        $fragment->storeScratchProperty('nonce',$fraNonceID);
      }
      if (isset($fraMetadata['label'])) {
        $fragment->setLabel($fraMetadata['label']);
      }
      if (isset($fraMetadata['description'])) {
        $fragment->setDescription($fraMetadata['description']);
      }
      if (isset($fraMetadata['measure'])) {
        $fragment->setMeasure($fraMetadata['measure']);
      }
      if (isset($fraMetadata['restore_state'])) {
        $restoreID = $fragment->getIDofTermParentLabel(strtolower($fraMetadata['restore_state']).'-restorationstate');//term dependency
        if ($restoreID) {
          $fragment->setRestoreStateID($restoreID);
        } else {
          $fragment->storeScratchProperty('restore_state',$fraMetadata['restore_state']);
        }
      }
      if (isset($fraMetadata['location_refs'])) {
        $fragment->setLocationRefs($fraMetadata['location_refs']);
      }
      if (isset($fraMetadata['part_id']) && isset($entityNonce2entID[$fraMetadata['part_id']])) {
        $fragment->setPartID($entityNonce2entID[$fraMetadata['part_id']]);
      }
      if (isset($fraMetadata['material_context_ids'])) {
        $fragmentMtxIDs = array();
        foreach ($fraMetadata['material_context_ids'] as $mtxNonce) {
          if (isset($entityNonce2entID[$mtxNonce])) {
            array_push($fragmentMtxIDs,$entityNonce2entID[$mtxNonce]);
          }
        }
        if (count($fragmentMtxIDs)) {
          $fragment->setMaterialContextIDs($fragmentMtxIDs);
        }
      }
      if (isset($fraMetadata['scratch'])) {
        foreach ($fraMetadata['scratch'] as $prop => $val) {
          $fragment->storeScratchProperty($prop,$val);
        }
      }      
      if (isset($fraMetadata['image_ids'])) {
        $fragmentImgIDs = array();
        foreach ($fraMetadata['image_ids'] as $imgNonce) {
          if (isset($entityNonce2entID[$imgNonce])) {
            array_push($fragmentImgIDs,$entityNonce2entID[$imgNonce]);
          }
        }
        if (count($fragmentImgIDs)) {
          $fragment->setImageIDs($fragmentImgIDs);
        }
      }
      $fragment->save();
      if (!$fragment->hasError()) {
        $entityNonce2entID[$fraNonceID] = $fragment->getID();
				array_push($updateEntities,$fragment);
      } else {
				array_push($warnings,"Save of fragment nonce id $fraNonceID  has errors".$fragment->getErrors(true).", ignoring");
      }
    }
  }

  //SURFACE
  if(isset($fragmentSurfaces) && count($fragmentSurfaces) > 0) {
    foreach ($fragmentSurfaces as $srfNonceID => $srfMetadata) {
      if (intval($srfNonceID)) { //int so existing image
        $surface = new Surface(intval($srfNonceID));
        if (!$surface || $surface->hasError() && $surface->getID() != intval($srfNonceID)) {
          array_push($warnings,"Load of surface ".$srfNonceID." failed ignoring");
          continue;
        }
      } else {
        $surface = new Surface();
        $surface->setOwnerID($ownerID);
        $surface->setVisibilityIDs($visibilityIDs);
        $surface->storeScratchProperty('nonce',$srfNonceID);
      }

      if (isset($srfMetadata['text_ids'])) {
        $srfTextIDs = array();
        foreach ($srfMetadata['text_ids'] as $txtNonceID) {
          if (isset($entityNonce2entID[$txtNonceID])) {
            array_push($srfTextIDs,$entityNonce2entID[$txtNonceID]);
          } else if (intval($txtNonceId)) {
            array_push($srfTextIDs,intval($txtNonceId));
          }
        }
        if (count($srfTextIDs)) {
          $surface->setTextIDs($srfTextIDs);
        }
      }
      if (isset($srfMetadata['label'])) {
        $surface->setLabel($srfMetadata['label']);
      }
      if (isset($srfMetadata['description'])) {
        $surface->setDescription($srfMetadata['description']);
      }
      if (isset($srfMetadata['number'])) {
        $surface->setNumber($srfMetadata['number']);
      }
      if (isset($srfMetadata['layer_number'])) {
        $surface->setLayerNumber($srfMetadata['layer_number']);
      }
      if (isset($srfMetadata['scripts'])) {
        $srfScriptIDs = array();
        $errScripts = array();
        foreach($srfMetadata['scripts'] as $strScript) {
          $scrID = $surface->getIDofTermParentLabel(strtolower($strScript).'-languagescript');//term dependency
          if ($scrID) {
            array_push($srfScriptIDs,$scrID);
          } else {
            array_push($errScripts,$strScript);
          }
        }
        if (count($srfScriptIDs)) {
          $surface->setScripts($srfScriptIDs);
        }
        if (count($errScripts)) {
          $surface->storeScratchProperty('scripts',$errScripts);
        }
      }
      if (isset($srfMetadata['fragment_id']) && isset($entityNonce2entID[$srfMetadata['fragment_id']])) {
        $surface->setFragmentID($entityNonce2entID[$srfMetadata['fragment_id']]);
      }
      if (isset($srfMetadata['image_ids'])) {
        $surfaceImgIDs = array();
        foreach ($srfMetadata['image_ids'] as $imgNonce) {
          if (isset($entityNonce2entID[$imgNonce])) {
            array_push($surfaceImgIDs,$entityNonce2entID[$imgNonce]);
          }
        }
        if (count($surfaceImgIDs)) {
          $surface->setImageIDs($surfaceImgIDs);
        }
      }
      $surface->save();
      if (!$surface->hasError()) {
        $entityNonce2entID[$srfNonceID] = $surface->getID();
				array_push($updateEntities,$surface);
      } else {
				array_push($warnings,"Save of surface nonce id $srfNonceID  has errors".$surface->getErrors(true).", ignoring");
      }
    }
  }

  if (count($updateEntities)) {
    foreach($updateEntities as $entity) {
      addNewEntityReturnData($entity->getEntityTypeCode(),$entity);
    }
  }

  $retVal["success"] = false;
  if (count($errors)) {
    $retVal["errors"] = $errors;
  } else {
    $retVal["success"] = true;
  }
  if (count($warnings)) {
    $retVal["warnings"] = $warnings;
  }
  if (count($entities)) {
    $retVal["entities"] = $entities;
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