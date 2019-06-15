<?php
  $textCKN = null;

  $imageDefs = array(
    "img1" => array(// img1 is a text local nonce that will be used to lookup the image if exist for deduplication
                    //it can also be a image ID (int)
      "type" => "InscriptionRubbing",//"InscriptionPhotograph","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
      "url" => "/images/vp/EIAD???/EIAD_???.JPG",
      "attribution" => array(
                              "title" => "",
                              "description" => "",
                              "bibliographyid" => null,
                              "type" => "reference",
                              "detail" => "",
                              "aedid" => null,
                              "usergroupid" => null
                            )
      //"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
    )
  );

  $textItem = array(
    "title"=>"",
   // "type" => "typeOfItemTerm",
   // "shape" => "shapeOfItemTerm",
   //"measure" => "Unit:cm, Height:,Width:,Depth:",
   //"image_ids" => array("img1")
  );

  $textParts = array(
    "prt1" => array(
      "label"=>"",
      // "type" => "typeOfPartTerm",
      // "shape" => "shapeOfPartTerm",
      //"mediums" => array("marble"),
      //"measure" => "Unit:cm, Height:,Width:,Depth:",
      //"sequence" => 1,
      //"item_id" => "toBeFilledIn",
      //"image_ids"  => array("img1")
    )
  );

  $materialContexts = array(
    "mtx1" => array(
      "arch_context" => "",
      "find_status" => ""
    )
  );

  $textFragments = array(
    "fra1" => array(
      "label"=>"",
      "description" =>"",
      //"measure" => "Unit:cm, Height:,Width:",
      //"restore_state" => "original",
      "location_refs" => array(""),
      "part_id"  => "prt1",
      "material_context_ids" => array("mtx1"),
      "image_ids"  => array("img1")
    )
  );

  $textSurfaces = array(
    "srf1" => array(
      //"text_ids" => array("toBeFilledIn"),
      "description" =>"",
      //"number" => 1,
      //"layer_number" => 1,
      "scripts" => array(""),
      "fragment_id"  => "fra1",
      "image_ids"  => array("img1")
    )
  );

  $textImageBaselines = array(
    "bln1" => array(
      "imgID" => "img1",
      //"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
      "surface_id" => "srf1",
      "scriptLanguage" => "" //temporary storage location
    )
  );


?>
