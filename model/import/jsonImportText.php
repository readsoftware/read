<?php

  //DEPENDENCIES
  require_once (dirname(__FILE__) . '/../../../common/php/userAccess.php');
  require_once (dirname(__FILE__) . '/../../../common/php/utils.php');//get utilities
  include_once dirname(__FILE__) . '/../../entities/Annotation.php';
  include_once dirname(__FILE__) . '/../../entities/Attribution.php';
  include_once dirname(__FILE__) . '/../../entities/Bibliography.php';
  include_once dirname(__FILE__) . '/../../entities/Baseline.php';
  include_once dirname(__FILE__) . '/../../entities/Fragment.php';
  include_once dirname(__FILE__) . '/../../entities/Item.php';
  include_once dirname(__FILE__) . '/../../entities/Image.php';
  include_once dirname(__FILE__) . '/../../entities/MaterialContext.php';
  include_once dirname(__FILE__) . '/../../entities/Run.php';
  include_once dirname(__FILE__) . '/../../entities/Part.php';
  include_once dirname(__FILE__) . '/../../entities/Surface.php';
  include_once dirname(__FILE__) . '/../../entities/Text.php';
  include_once dirname(__FILE__) . '/../../utility/parser.php';

  if (!isset($_REQUEST['txtImportFilename'])) {
    echo "no filename supplied";
    exit;
  }
  include_once dirname(__FILE__) . '/'.$_REQUEST['txtImportFilename'];

  if (!isset($textCKN)) {
    echo "invalid import file supplied";
    exit;
  }
  //PAGE VARIABLES
  $userPrefs = getUserPreferences();
  $visibilityIDs = $userPrefs['defaultVisibilityIDs'];  // DEFAULT VISIBILITY SET TO PUBLIC
  $ownerID = $userPrefs['defaultEditUserID'];
  $defAttrIDs = $userPrefs['defaultAttributionIDs'];


  //Create Images
  $images = array();
  $txtImageIDs = array();
  if (isset($imageDefs) && count($imageDefs) >0) {
    foreach ($imageDefs as $imgNonce => $imgMetadata) {
      $image = new Image();
      if (!array_key_exists('url',$imgMetadata)) {
        continue;
      } else {
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
      $image->storeScratchProperty('nonce',$imgNonce);
      $image->storeScratchProperty('ckn',$textCKN);
      $image->setOwnerID($ownerID);
      $image->setVisibilityIDs($visibilityIDs);
      $image->setAttributionIDs($defAttrIDs);
      $image->save();
      if (!$image->hasError()) {
        $images[$imgNonce] = $image;
        $txtImageIDs[$imgNonce] =$image->getID();
      }
    }
  }

  //TEXT init
  $text = new Text();
  $text->setCKN($textCKN);
  $text->setTitle($textTitle);
  $text->setOwnerID($ownerID);
  $text->setVisibilityIDs($visibilityIDs);
  $text->setAttributionIDs($defAttrIDs);
  if ($txtImageIDs && count(array_values($txtImageIDs))) {
    $text->setImageIDs(array_values($txtImageIDs));
  }
  $text->Save();
  if ($text->hasError()) {
    return;
  }
  $txtID = $text->getID();
  if (isset($textnotes) && count($textnotes) > 0 ) {
    $annoIDs = array();
    //create annotations for this text
    foreach ($textnotes as $anoType => $anoText) {
      $annoID = createAnnotation($anoType,$anoText,"txt:$txtID");
      if ($annoID) {
        array_push($annoIDs,$annoID);
      }
    }
    $text->setAnnotationIDs($annoIDs);
    $text->Save();
  }

  //ITEM
  $itmID = null;
  if(isset($textItem) && array_key_exists('title',$textItem)) {
    $item = new Item();
    $item->setTitle($textItem['title']);
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
        if (array_key_exists($imgNonce,$images)) {
          array_push($itemImgIDs,$images[$imgNonce]->getID());
        }
      }
      if (count($itemImgIDs)) {
        $item->setImageIDs($itemImgIDs);
      }
    }
    $item->setOwnerID($ownerID);
    $item->setVisibilityIDs($visibilityIDs);
//    $item->setAttributionIDs($defAttrIDs);
    $item->storeScratchProperty('ckn',$textCKN);
    $item->save();
    if (!$item->hasError()) {
      $itmID = $item->getID();
    }else{
      error_log($item->getErrors(true));
    }
  }

  //PARTS
  $parts = array();
  if(isset($textParts) && count($textParts) > 0) {
    foreach ($textParts as $prtNonce => $prtMetadata) {
      $part = new Part();
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
          if (array_key_exists($imgNonce,$images)) {
            array_push($partImgIDs,$images[$imgNonce]->getID());
          }
        }
        if (count($partImgIDs)) {
          $part->setImageIDs($partImgIDs);
        }
      }
      $part->setOwnerID($ownerID);
      $part->setVisibilityIDs($visibilityIDs);
//      $part->setAttributionIDs($defAttrIDs);
      $part->storeScratchProperty('nonce',$prtNonce);
      $part->storeScratchProperty('ckn',$textCKN);
      $part->save();
      if (!$part->hasError()) {
        $parts[$prtNonce] = $part;
      }else{
        error_log($part->getErrors(true));
      }
    }
  }

  //MATERIALCONTEXT
  $mtxs = array();
  if(isset($materialContexts) && count($materialContexts) > 0) {
    foreach ($materialContexts as $mtxNonce => $mtxMetadata) {
      $materialContext = new MaterialContext();
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
      $materialContext->setOwnerID($ownerID);
      $materialContext->setVisibilityIDs($visibilityIDs);
      $materialContext->setAttributionIDs($defAttrIDs);
      $materialContext->storeScratchProperty('nonce',$mtxNonce);
      $materialContext->storeScratchProperty('ckn',$textCKN);
      $materialContext->save();
      if (!$materialContext->hasError()) {
        $mtxs[$mtxNonce] = $materialContext;
      }else{
        error_log($materialContext->getErrors(true));
      }
    }
  }


  //FRAGMENT
  $fragments = array();
  if(isset($textFragments) && count($textFragments) > 0) {
    foreach ($textFragments as $fraNonce => $fraMetadata) {
      $fragment = new Fragment();
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
      if (array_key_exists('part_id',$fraMetadata) && array_key_exists($fraMetadata['part_id'],$parts)) {
        $fragment->setPartID($parts[$fraMetadata['part_id']]->getID());
      }
      if (array_key_exists('material_context_ids',$fraMetadata)) {
        $fragmentMtxIDs = array();
        foreach ($fraMetadata['material_context_ids'] as $mtxNonce) {
          if (array_key_exists($mtxNonce,$mtxs)) {
            array_push($fragmentMtxIDs,$mtxs[$mtxNonce]->getID());
          }
        }
        if (count($fragmentMtxIDs)) {
          $fragment->setMaterialContextIDs($fragmentMtxIDs);
        }
      }
      if (array_key_exists('image_ids',$fraMetadata)) {
        $fragmentImgIDs = array();
        foreach ($fraMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$images)) {
            array_push($fragmentImgIDs,$images[$imgNonce]->getID());
          }
        }
        if (count($fragmentImgIDs)) {
          $fragment->setImageIDs($fragmentImgIDs);
        }
      }
      $fragment->setOwnerID($ownerID);
      $fragment->setVisibilityIDs($visibilityIDs);
      $fragment->setAttributionIDs($defAttrIDs);
      $fragment->storeScratchProperty('nonce',$fraNonce);
      $fragment->storeScratchProperty('ckn',$textCKN);
      $fragment->save();
      if (!$fragment->hasError()) {
        $fragments[$fraNonce] = $fragment;
      }else{
        error_log($fragment->getErrors(true));
      }
    }
  }

  //SURFACE
  $surfaces = array();
  if(isset($textSurfaces) && count($textSurfaces) > 0) {
    foreach ($textSurfaces as $srfNonce => $srfMetadata) {
      $surface = new Surface();
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
      if (array_key_exists('fragment_id',$srfMetadata) && array_key_exists($srfMetadata['fragment_id'],$fragments)) {
        $surface->setFragmentID($fragments[$srfMetadata['fragment_id']]->getID());
      }
      if (array_key_exists('image_ids',$srfMetadata)) {
        $surfaceImgIDs = array();
        foreach ($srfMetadata['image_ids'] as $imgNonce) {
          if (array_key_exists($imgNonce,$images)) {
            array_push($surfaceImgIDs,$images[$imgNonce]->getID());
          }
        }
        if (count($surfaceImgIDs)) {
          $surface->setImageIDs($surfaceImgIDs);
        }
      }
      $surface->setOwnerID($ownerID);
      $surface->setVisibilityIDs($visibilityIDs);
//      $surface->setAttributionIDs($defAttrIDs);
      $surface->storeScratchProperty('nonce',$srfNonce);
      $surface->storeScratchProperty('ckn',$textCKN);
      $surface->save();
      if (!$surface->hasError()) {
        $surfaces[$srfNonce] = $surface;
      }else{
        error_log($surface->getErrors(true));
      }
    }
  }

  //BASELINE
  $baselines = array();
  if(isset($textImageBaselines) && count($textImageBaselines) > 0) {
    foreach ($textImageBaselines as $blnNonce => $blnMetadata) {
      $baseline = new Baseline();
      $typeID = $baseline->getIDofTermParentLabel('image-baselinetype');//term dependency
      if ($typeID) {
        $baseline->setType($typeID);
      }
      if (array_key_exists('imgID',$blnMetadata) && array_key_exists($blnMetadata['imgID'],$images)) {
        $baseline->setImageID($images[$blnMetadata['imgID']]->getID());
      }
      if (array_key_exists('polygon',$blnMetadata)) {
        $baseline->setImageBoundary($blnMetadata['polygon']);
      }
      if (array_key_exists('scriptLanguage',$blnMetadata)) {
        $baseline->storeScratchProperty('scriptLanguage',$blnMetadata['scriptLanguage']);
      }
      if (array_key_exists('surface_id',$blnMetadata) && array_key_exists($blnMetadata['surface_id'],$surfaces)) {
        $baseline->setSurfaceID($surfaces[$blnMetadata['surface_id']]->getID());
      }
      $baseline->setOwnerID($ownerID);
      $baseline->setVisibilityIDs($visibilityIDs);
      $baseline->setAttributionIDs($defAttrIDs);
      $baseline->storeScratchProperty('nonce',$blnNonce);
      $baseline->storeScratchProperty('ckn',$textCKN);
      $baseline->save();
      if (!$baseline->hasError()) {
        $baselines[$blnNonce] = $baseline;
      }
    }
  }

  //RUN

  //EDITIONS
  if (isset($editionDef) && array_key_exists('TranscriptionLines', $editionDef) && count($editionDef['TranscriptionLines']) > 0) {
    $description = null;
    if (array_key_exists('Description', $editionDef)) {
      $description = $editionDef['Description'];
    }
    $parserConfigs = array();
    $order = 1;
    foreach ($editionDef['TranscriptionLines'] as $linemask => $transcription) {
      $parserConfig = createParserConfig(
        $ownerID,
        $visibilityIDs,
        $defAttrIDs,
        $textCKN,
        null,
        $txtID,
        null,
        $linemask,
        $order++,
        null, //$scribe,
        $transcription,
        null,
        null,
        null,
        $description
        );
      array_push ($parserConfigs, $parserConfig);
    }
    $parser = new Parser($parserConfigs);
    $parser->parse();
    $parser->saveParseResults();
    // add text comments to edition for now
    if (isset($editionNotes) && count($editionNotes) > 0 ) {
      $annoIDs = array();
      $editions = $parser->getEditions();
      $edition = $editions[0];
      //create annotations for this text
      foreach ($editionNotes as $anoType => $anoText) {
        $annoID = createAnnotation($anoType,$anoText,"edn:".$edition->getID());
        if ($annoID) {
          array_push($annoIDs,$annoID);
        }
      }
      $edition->setAnnotationIDs($annoIDs);
      $edition->save();
    }

    //check for line comments
    if (array_key_exists('physlineComments', $editionDef) &&
        count($editionDef['physlineComments']) > 0) {
      $physLineTermID = Entity::getIDofTermParentLabel('linephysical-textphysical');//term dependency
      foreach ($parser->getSequences() as $sequence) {
        if ($sequence->getTypeID() == $physLineTermID &&
            array_key_exists($sequence->getLabel(),$editionDef['physlineComments'] )) {
          $annoIDs = $sequence->getAnnotationIDs();
          foreach ($editionDef['physlineComments'][$sequence->getLabel()] as $comment) {
            $annoID = createAnnotation('comment',$comment,"seq:".$sequence->getID());
            if ($annoID) {
              array_push($annoIDs,$annoID);
            }
          }
          if (count($annoIDs)) {
            $sequence->setAnnotationIDs($annoIDs);
            $sequence->save();
          }
        }
      }
    }

    //check for structure comments
    if (array_key_exists('analysisComments', $editionDef) &&
        count($editionDef['analysisComments']) > 0) {
      $chapterTermID = Entity::getIDofTermParentLabel('chapter-analysis');//term dependency
      foreach ($parser->getSequences() as $sequence) {
        if ($sequence->getTypeID() == $chapterTermID &&
            array_key_exists($sequence->getLabel(),$editionDef['analysisComments'] )) {
          $annoIDs = $sequence->getAnnotationIDs();
          foreach ($editionDef['analysisComments'][$sequence->getLabel()] as $comment) {
            $annoID = createAnnotation('comment',$comment,"seq:".$sequence->getID());
            if ($annoID) {
              array_push($annoIDs,$annoID);
            }
          }
          if (count($annoIDs)) {
            $sequence->setAnnotationIDs($annoIDs);
            $sequence->save();
          }
        }
      }
    }
    echo "<h1> $textCKN </h1>";

    echo "<h2> Input Strings </h2>";
    foreach ($parser->getConfigs() as $lnCfg) {
      echo $lnCfg["transliteration"]."<br>";
    }

    echo "<h2> Errors </h2>";
    $errormsg = "";
    foreach ($parser->getErrors() as $error) {
      echo "<span style=\"color:red;\">error -   $error </span><br>\n";
      $errormsg .= "\n  -\t".$error;
    }

    echo "<h2> New baseline character strings</h2>";
    foreach ($parser->getBaselines() as $baseline) {
      echo $baseline->getTranscription()."<br>";
    }
  }


function createAnnotation($annoTypeTerm = "comment", $annoText = "", $fromGID = null, $toGID = null) {
  global $visibilityIDs,$ownerID,$defAttrIDs;
  $annotation = new Annotation();
  $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-commentarytype');//term dependency
  if (!$typeID) {
     $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-workflowtype');//term dependency
  }
  if (!$typeID) {
    $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-footnotetype');//term dependency
  }
  if (!$typeID) {
    $typeID = $annotation->getIDofTermParentLabel(strtolower($annoTypeTerm).'-linkagetype');//term dependency
  }
  if (!$typeID) {
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

?>