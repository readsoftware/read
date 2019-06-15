<?php

  //DEPENDENCIES
  require_once (dirname(__FILE__) . '/../../../common/php/userAccess.php');
  include_once dirname(__FILE__) . '/../../entities/Annotation.php';
  include_once dirname(__FILE__) . '/../../entities/Attribution.php';
  include_once dirname(__FILE__) . '/../../entities/Bibliography.php';
  include_once dirname(__FILE__) . '/../../entities/Baselines.php';
  include_once dirname(__FILE__) . '/../../entities/Fragments.php';
  include_once dirname(__FILE__) . '/../../entities/Items.php';
  include_once dirname(__FILE__) . '/../../entities/Images.php';
  include_once dirname(__FILE__) . '/../../entities/MaterialContexts.php';
  include_once dirname(__FILE__) . '/../../entities/Runs.php';
  include_once dirname(__FILE__) . '/../../entities/Parts.php';
  include_once dirname(__FILE__) . '/../../entities/Surfaces.php';
  include_once dirname(__FILE__) . '/../../entities/Texts.php';
  include_once dirname(__FILE__) . '/../../utility/parser.php';

  if (!@$_REQUEST['txtImportFilename']) {
    echo "no filename supplied";
    exit;
  }
  include_once dirname(__FILE__) . '/'.$_REQUEST['txtImportFilename'];

  if (!isset($textCKN)) {
    echo "invalid import file supplied";
    exit;
  } else {
    $texts = new Texts("txt_ckn = '$textCKN'","txt_id");
    if (!$texts || $texts->getError() || $texts->getCount() == 0) {
      echo "invalid text inventory number $textCKN supplied";
      exit;
    }
    $text = $texts->current();
    $txtID = $text->getID();
    if (!$txtID || $text->hasError()) {
      echo "invalid text for inventory number $textCKN supplied";
      exit;
    }
  }

  //PAGE VARIABLES
  $ckn_Key = $textCKN[2];
  $userPrefs = getUserPreferences();
  $visibilityIDs = $userPrefs['defaultVisibilityIDs'];  // DEFAULT VISIBILITY SET TO PUBLIC
  $ownerID = $userPrefs['defaultEditUserID'];
  $defAttrIDs = $userPrefs['defaultAttributionIDs'];


  //Create Images
  $imagesNonce2image = array();
  $images = new Images("img_scratch like '%$textCKN%'","img_id",null,null);
  if ($images->getCount() == 0 || $images->getError()) {
    echo "Error updating $textCKN - ".$images->getError();
  } else {
    foreach ($images as $image) {
      $nonce = $image->getScratchProperty('nonce');
      if ($nonce) {
        $imagesNonce2image[$nonce] = $image;
      }
    }
  }
  if (isset($imageDefs) && count($imageDefs) >0) {
    $imageIDs = array();
    foreach ($imageDefs as $imgNonce => $imgMetadata) {
      if (array_key_exists($imgNonce,$imagesNonce2image)) {
        $image = $imagesNonce2image[$imgNonce];
      } else {
        if (intval($imgNonce)) {
          $image = new Image(intval($imgNonce));
          $imgNonce = 'img:'.$imgNonce;
          if ($image && !$image->hasErrors()) {
            $image->storeScratchProperty('nonce',$imgNonce);
            $image->storeScratchProperty('ckn',$textCKN);
          } else {
            $image = null;
          }
        }
        if (!$image) {
          $image = new Image();
          $image->storeScratchProperty('nonce',$imgNonce);
          $image->storeScratchProperty('ckn',$textCKN);
          $image->setOwnerID($ownerID);
          $image->setVisibilityIDs($visibilityIDs);
        }
      }
      if (isset($image) && !$image->getURL() && !array_key_exists('url',$imgMetadata)) {
        continue;
      } else if (array_key_exists('url',$imgMetadata)) {
        $image->setURL($imgMetadata['url']);
      }
      if (array_key_exists('polygon',$imgMetadata)) {
        $image->setBoundary($imgMetadata['polygon']);
      }
      if (array_key_exists('type',$imgMetadata)) {
        $typeID = $image->getIDofTermParentLabel(strtolower($imgMetadata['type']).'-imagetype');//term dependency
        if ($typeID) {
          $image->setType($typeID);
        } else {
          $image->storeScratchProperty('type',$imgMetadata['type']);
        }
      }
      if (array_key_exists('attribution',$imgMetadata)) {
        $attrInfo = $imgMetadata['attribution'];
        if (isset($attrInfo['atbid'])) {
          $attrID = $attrInfo['atbid'];
        } else {
          $attrID = createAttribution(
                                      isset($attrInfo["title"])?$attrInfo["title"]:null,
                                      isset($attrInfo["description"])?$attrInfo["description"]:null,
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
        $imagesNonce2image[$imgNonce] = $image;
        array_push($imageIDs, $image->getID());
      }else{
        error_log($image->getErrors(true));
      }
    }
    if ($text && count($imageIDs) > 0) {
      $txtImgIDs = array_unique(array_merge($imageIDs,$text->getImageIDs()));
      $text->setImageIDs($txtImgIDs);
      $text->save();
    }
  }

  //ITEM
  $itmID = null;
  if($textItem && count($textItem) > 0) {
    $items = new Items("itm_scratch like '%$textCKN%'","itm_id",null,null);
    if ($items->getCount() == 0 || $items->getError()) {
      echo "Error updating $textCKN - ".$items->getError();
    } else {
      $item = $items->current();  
      $itmID = $item->getID();
      if (array_key_exists('title',$textItem)) {
        $item->setTitle($textItem['title']);
      }
      if (array_key_exists('type',$textItem)) {
        $typeID = $item->getIDofTermParentLabel(strtolower($textItem['type']).'-itemtype');//term dependency
        if ($typeID) {
          $item->setType($typeID);
        } else {
          $item->storeScratchProperty('type',$textItem['type']);
        }
      }
      if (array_key_exists('shape',$textItem)) {
        $shapeID = $item->getIDofTermParentLabel(strtolower($textItem['shape']).'-itemshape');//term dependency
        if ($shapeID) {
          $item->setShapeID($shapeID);
        } else {
          $item->storeScratchProperty('shape',$textItem['shape']);
        }
      }
      if (array_key_exists('measure',$textItem)) {
        $item->setMeasure($textItem['measure']);
      }
      if (array_key_exists('image_ids',$textItem)) {
        $itemImgIDs = array();
        foreach ($textItem['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($itemImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($itemImgIDs)) {
          $item->setImageIDs($itemImgIDs);
        }
      }
      $item->save();
      if ($item->hasError()) {
        error_log($item->getErrors(true));
      }
    }
  }

  //PARTS
  $partsNonce2part = array();
  $parts = new Parts("prt_scratch like '%$textCKN%'","prt_id",null,null);
  if ($parts->getCount() == 0 || $parts->getError()) {
    echo "Error updating $textCKN - ".$parts->getError();
  } else {
    foreach ($parts as $part) {
      $nonce = $part->getScratchProperty('nonce');
      if ($nonce) {
        $partsNonce2part[$nonce] = $part;
      }
    }
  }
  if($textParts && count($textParts) > 0) {
    foreach ($textParts as $prtNonce => $prtMetadata) {
      if (array_key_exists($prtNonce,$partsNonce2part)) {
        $part = $partsNonce2part[$prtNonce];
      } else {
        $part = new Part();
        $part->setOwnerID($ownerID);
        $part->setVisibilityIDs($visibilityIDs);
        $part->storeScratchProperty('nonce',$prtNonce);
        $part->storeScratchProperty('ckn',$textCKN);
      }
      if (array_key_exists('label',$prtMetadata)) {
        $part->setLabel($prtMetadata['label']);
      }
      if (array_key_exists('type',$prtMetadata)) {
        $typeID = $part->getIDofTermParentLabel(strtolower($prtMetadata['type']).'-parttype');//term dependency
        if ($typeID) {
          $part->setType($typeID);
        } else {
          $part->storeScratchProperty('type',$prtMetadata['type']);
        }
      }
      if (array_key_exists('shape',$prtMetadata)) {
        $shapeID = $part->getIDofTermParentLabel(strtolower($prtMetadata['shape']).'-partshape');//term dependency
        if ($shapeID) {
          $part->setShapeID($shapeID);
        } else {
          $part->storeScratchProperty('shape',$prtMetadata['shape']);
        }
      }
      if (array_key_exists('mediums',$prtMetadata)) {
        $part->setMediums($prtMetadata['mediums']);
      }
      if (array_key_exists('measure',$prtMetadata)) {
        $part->setMeasure($prtMetadata['measure']);
      }
      if (array_key_exists('sequence',$prtMetadata)) {
        $part->setSequence($prtMetadata['sequence']);
      }
      if ($itmID) {
        $part->setItemID($itmID);
      }
      if (array_key_exists('image_ids',$prtMetadata)) {
        $partImgIDs = array();
        foreach ($prtMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($partImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($partImgIDs)) {
          $part->setImageIDs($partImgIDs);
        }
      }
      $part->save();
      if (!$part->hasError()) {
        $partsNonce2part[$prtNonce] = $part;
      }else{
        error_log($part->getErrors(true));
      }
    }
  }

  //MATERIALCONTEXT
  $mtxsNonce2mtx = array();
  $mtxs = new MaterialContexts("mtx_scratch like '%$textCKN%'","mtx_id",null,null);
  if ($mtxs->getCount() == 0 || $mtxs->getError()) {
    echo "Error updating $textCKN - ".$mtxs->getError();
  } else {
    foreach ($mtxs as $materialContext) {
      $nonce = $materialContext->getScratchProperty('nonce');
      if ($nonce) {
        $mtxsNonce2mtx[$nonce] = $materialContext;
      }
    }
  }
  $mtxs = array();
  if(@$materialContexts && count($materialContexts) > 0) {
    foreach ($materialContexts as $mtxNonce => $mtxMetadata) {
      if (array_key_exists($mtxNonce,$mtxsNonce2mtx)) {
        $materialContext = $mtxsNonce2mtx[$mtxNonce];
      } else {
        $materialContext = new MaterialContext();
        $materialContext->setOwnerID($ownerID);
        $materialContext->setVisibilityIDs($visibilityIDs);
        $materialContext->setAttributionIDs($defAttrIDs);
        $materialContext->storeScratchProperty('nonce',$mtxNonce);
        $materialContext->storeScratchProperty('ckn',$textCKN);
      }
      if (array_key_exists('arch_context',$mtxMetadata)) {
        $archCtxID = null; //TODO add code to lookup string
        if ($archCtxID) {
          $materialContext->setArchContextID($archCtxID);
        } else {
          $materialContext->storeScratchProperty('arch_context',$mtxMetadata['arch_context']);
        }
      }
      if (array_key_exists('find_status',$mtxMetadata)) {
        $materialContext->setFindStatus($mtxMetadata['find_status']);
      }
      $materialContext->save();
      if (!$materialContext->hasError()) {
        $mtxsNonce2mtx[$mtxNonce] = $materialContext;
      }else{
        error_log($materialContext->getErrors(true));
      }
    }
  }


  //FRAGMENT
  $fragmentsNonce2fragment = array();
  $fragments = new Fragments("frg_scratch like '%$textCKN%'","frg_id",null,null);
  if ($fragments->getCount() == 0 || $fragments->getError()) {
    echo "Error updating $textCKN - ".$fragments->getError();
  } else {
    foreach ($fragments as $fragment) {
      $nonce = $fragment->getScratchProperty('nonce');
      if ($nonce) {
        $fragmentsNonce2fragment[$nonce] = $fragment;
      }
    }
  }
  if($textFragments && count($textFragments) > 0) {
    foreach ($textFragments as $fraNonce => $fraMetadata) {
      if (array_key_exists($fraNonce,$fragmentsNonce2fragment)) {
        $fragment = $fragmentsNonce2fragment[$fraNonce];
      } else {
        $fragment = new Fragment();
        $fragment->setOwnerID($ownerID);
        $fragment->setVisibilityIDs($visibilityIDs);
        $fragment->setAttributionIDs($defAttrIDs);
        $fragment->storeScratchProperty('nonce',$fraNonce);
        $fragment->storeScratchProperty('ckn',$textCKN);
      }
      if (array_key_exists('label',$fraMetadata)) {
        $fragment->setLabel($fraMetadata['label']);
      }
      if (array_key_exists('description',$fraMetadata)) {
        $fragment->setDescription($fraMetadata['description']);
      }
      if (array_key_exists('measure',$fraMetadata)) {
        $fragment->setMeasure($fraMetadata['measure']);
      }
      if (array_key_exists('restore_state',$fraMetadata)) {
        $restoreID = $fragment->getIDofTermParentLabel(strtolower($fraMetadata['restore_state']).'-fragmentstate');//term dependency
        if ($restoreID) {
          $fragment->setShapeID($restoreID);
        } else {
          $fragment->storeScratchProperty('restore_state',$fraMetadata['restore_state']);
        }
      }
      if (array_key_exists('location_refs',$fraMetadata)) {
        $fragment->setLocationRefs($fraMetadata['location_refs']);
      }
      if (array_key_exists('part_id',$fraMetadata) && array_key_exists($fraMetadata['part_id'],$partsNonce2part)) {
        $fragment->setPartID($partsNonce2part[$fraMetadata['part_id']]->getID());
      }
      if (array_key_exists('material_context_ids',$fraMetadata)) {
        $fragmentMtxIDs = array();
        foreach ($fraMetadata['material_context_ids'] as $mtxNonce) {
          if (array_key_exists($mtxNonce,$mtxsNonce2mtx)) {
            array_push($fragmentMtxIDs,$mtxsNonce2mtx[$mtxNonce]->getID());
          }
        }
        if (count($fragmentMtxIDs)) {
          $fragment->setMaterialContextIDs($fragmentMtxIDs);
        }
      }
      if (array_key_exists('image_ids',$fraMetadata)) {
        $fragmentImgIDs = array();
        foreach ($fraMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($fragmentImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($fragmentImgIDs)) {
          $fragment->setImageIDs($fragmentImgIDs);
        }
      }
      $fragment->save();
      if (!$fragment->hasError()) {
        $fragmentsNonce2fragment[$fraNonce] = $fragment;
      }else{
        error_log($fragment->getErrors(true));
      }
    }
  }

  //SURFACE
  $surfacesNonce2surface = array();
  $surfaces = new Surfaces("srf_scratch like '%$textCKN%'","srf_id",null,null);
  if ($surfaces->getCount() == 0 || $surfaces->getError()) {
    echo "Error updating $textCKN - ".$surfaces->getError();
  } else {
    foreach ($surfaces as $surface) {
      $nonce = $surface->getScratchProperty('nonce');
      if ($nonce) {
        $surfacesNonce2surface[$nonce] = $surface;
      }
    }
  }
  if($textSurfaces && count($textSurfaces) > 0) {
    foreach ($textSurfaces as $srfNonce => $srfMetadata) {
      if (array_key_exists($srfNonce,$surfacesNonce2surface)) {
        $surface = $surfacesNonce2surface[$srfNonce];
      } else {
        $surface = new Surface();
        $surface->setOwnerID($ownerID);
        $surface->setVisibilityIDs($visibilityIDs);
        $surface->storeScratchProperty('nonce',$srfNonce);
        $surface->storeScratchProperty('ckn',$textCKN);
      }
      $surface->setTextIDs(array($txtID));
      if (array_key_exists('description',$srfMetadata)) {
        $surface->setDescription($srfMetadata['description']);
      }
      if (array_key_exists('number',$srfMetadata)) {
        $surface->setNumber($srfMetadata['number']);
      }
      if (array_key_exists('layer_number',$srfMetadata)) {
        $surface->setLayerNumber($srfMetadata['layer_number']);
      }
      if (array_key_exists('scripts',$srfMetadata)) {
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
      if (array_key_exists('fragment_id',$srfMetadata) && array_key_exists($srfMetadata['fragment_id'],$fragmentsNonce2fragment)) {
        $surface->setFragmentID($fragmentsNonce2fragment[$srfMetadata['fragment_id']]->getID());
      }
      if (array_key_exists('image_ids',$srfMetadata)) {
        $surfaceImgIDs = array();
        foreach ($srfMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$imagesNonce2image)) {
            array_push($surfaceImgIDs,$imagesNonce2image[$imgNonce]->getID());
          }
        }
        if (count($surfaceImgIDs)) {
          $surface->setImageIDs($surfaceImgIDs);
        }
      }
      $surface->save();
      if (!$surface->hasError()) {
        $surfacesNonce2surface[$srfNonce] = $surface;
      }else{
        error_log($surface->getErrors(true));
      }
    }
  }

  //BASELINE
  $typeID = $text->getIDofTermParentLabel('image-baselinetype');//term dependency
  $baselineNonce2baseline = array();
  $baselines = new Baselines("bln_type_id = $typeID and bln_scratch like '%$textCKN%'","bln_id",null,null);
  if ($baselines->getCount() == 0 || $baselines->getError()) {
    echo "Error updating $textCKN - ".$baselines->getError();
  } else {
    foreach ($baselines as $baseline) {
      $nonce = $baseline->getScratchProperty('nonce');
      if ($nonce) {
        $baselineNonce2baseline[$nonce] = $baseline;
      }
    }
  }
  $baselines = array();
  if($textImageBaselines && count($textImageBaselines) > 0) {
    foreach ($textImageBaselines as $blnNonce => $blnMetadata) {
      if (array_key_exists($blnNonce,$baselineNonce2baseline)) {
        $baseline = $baselineNonce2baseline[$blnNonce];
      } else {
        $baseline = new Baseline();
        $baseline->setOwnerID($ownerID);
        $baseline->setVisibilityIDs($visibilityIDs);
        $baseline->setAttributionIDs($defAttrIDs);
        $baseline->storeScratchProperty('nonce',$blnNonce);
        $baseline->storeScratchProperty('ckn',$textCKN);
      }
      if ($typeID) {
        $baseline->setType($typeID);
      }
      if (array_key_exists('imgID',$blnMetadata) && array_key_exists($blnMetadata['imgID'],$imagesNonce2image)) {
        $baseline->setImageID($imagesNonce2image[$blnMetadata['imgID']]->getID());
      }
      if (array_key_exists('polygon',$blnMetadata)) {
        $baseline->setImageBoundary($blnMetadata['polygon']);
      }
      if (array_key_exists('scriptLanguage',$blnMetadata)) {
        $baseline->storeScratchProperty('scriptLanguage',$blnMetadata['scriptLanguage']);
      }
      if (array_key_exists('surface_id',$blnMetadata) && array_key_exists($blnMetadata['surface_id'],$surfacesNonce2surface)) {
        $baseline->setSurfaceID($surfacesNonce2surface[$blnMetadata['surface_id']]->getID());
      }
      $baseline->save();
      if (!$baseline->hasError()) {
        $baselineNonce2baseline[$blnNonce] = $baseline;
      }
    }
  }


  echo "<h1> $textCKN updated </h1>";

function createAnnotation($annoTypeTerm = "comment", $annoText = "", $fromGID = null, $toGID = null) {
  global $visibilityIDs,$ownerID,$defAttrIDs;
  $annotation = new Annotation();
  $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-commentarytype');//term dependency
  if ($typeID) {
     $typeID = $annotation->getIDofTermParentLabel('comment-commentarytype');//term dependency
  }
  if ($fromGID) {
    $annotation->setLinkFromIDs(array($fromGID));
  }
  if ($toGID) {
    $annotation->setLinkToIDs(array($toGID));
  }
  if ($annoText) {
    $annotation->setText($annoText);
  }
  if ($typeID) {
    $annotation->setTypeID($typeID);
  }
  $annotation->setVisibilityIDs($visibilityIDs);
  $annotation->setAttributionIDs($defAttrIDs);
  $annotation->setOwnerID($ownerID);
  $annotation->save();
  if ($annotation->hasError()) {
    return null;
  } else {
    return $annotation->getID();
  }
}

function createAttribution($title, $description, $bibliographyID=null, $type='reference', $detail=null, $aEdId=null, $usergroupID=null) {
  global $ckn_Key;
  $attribution = new Attribution();
  $nonce = "";
  if (isset($title)) {
    $attribution->setTitle($title);
    $nonce .= $title;
  }
  $attribution->setDescription($description);
  if (isset($bibliographyID)) {
    $attribution->setBibliographyID($bibliographyID);
    $nonce .= $bibliographyID;   
  }
  if (isset($usergroupID)) {
    $attribution->setGroupID($usergroupID);
  }
  $attribution->setVisibilityIDs($visibilityIDs);
  $typeID = Entity::getIDofTermParentLabel($type.'-attributiontype');
  if ($typeID){
    $attribution->setTypes(array($typeID));
    $nonce .= $typeID;   
  }
  else {
    $attribution->storeScratchProperty($ckn_Key."_editions:ed_cmty",$type);
  }
  if (isset($detail)) {
    $attribution->setDetail($detail);
    $nonce .= $detail;   
  }
  if ($nonce) {
    $attributions = new Attributions("atb_scratch like '%$nonce%'","atb_id",null,null);
    if($attributions && !$attributions->getError() && $attributions->getCount() > 0) {
      $attribution = $attributions->getCurrent();
      return $attribution->getID();
    } else {
      $attribution->storeScratchProperty("nonce",$nonce);
    }
  }
  if ($aEdId) {
    $attribution->storeScratchProperty($ckn_Key."_editions:id",$aEdId);
  }
  $attribution->Save();
  if ($attribution->hasError()) {
    return null;
  } else {
    return $attribution->getID();
  }
}

?>