<?php

$textCKN="UniqueID";
$textTitle="Title in Search Results";

$editionNotes = array(
      "comment"=>"Found ‘in the path round the stūpa, on the west side’",
      "comment"=>"text and translation owed to J.F. Fleet",
      "description"=>"Pṛthivīmūlarāja grants village Kaṭṭuceruvu to his son Harivarman."
);
$editionDef = array (
  "Description"=>"Used as Edition Title",
  "TranscriptionLines" => array(//transcription markup for text, must be parser compatible
    "1"=>"«A»svasti bhadantanāgārjunācāryyasya",
    "2"=>"śiṣya ◊ tacchiṣyeṇa ca(*ndra)-",
    "3"=>"prabheṇa kārāpitā ◊ «B»  devamanu(*ṣ)[ya]-",
    "4"=>"vibhūtipūrvvakaṁ pratisthāpitā"),
  "physlineComments" => array (//annotations on physical line sequence so keys need to match seq label
    "1"=>array(
           "nāgārjunācāryyasya] corr. nāgārjuṇācāryyasya."
           ),
    "2"=>array(
           "śiṣya] corr. śiṣyo."
           ),
    "2"=>array(
           "jayaprabhācāryya] corr. jayaprabhācāryyas."
           ),
    "3"=>array(
           "kārāpitā] kārāpitāṁ Burgess. "
           ),
    "3"=>array(
           "devamanu(ṣ)[ya]] devamanu(ja) Burgess."
           ),
    "3"=>array(
           "satu-] emend satya°."
           ),
    "4"=>array(
           "buddhattva-] corr. buddhatva°."
           ),
    "4"=>array(
           "buddhapratimā] buddhapratimāṁ Burgess."
           )
  ),
  "analysisComments" => array (//annotations on structure sequence so keys need to match seq label
    "A"=>array(
      "Footnote for seq label A"
    ),
    "B"=>array(
      "Footnote for seq label B"
    )
  )
);


  $imageDefs = array(
    "img1" => array(
      "type" => "InscriptionRubbing",//"InscriptionPhotograph","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
      "url" => "/images/manuscripts/136/CKM0136.1.JPG"
      //"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
    ),
    "img2" => array(
      "type" => "InscriptionPhotograph",//"InscriptionRubbing","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
      "url" => "/images/manuscripts/136/CKM0136.2.JPG"
    ),
    "img3" => array(
      "type" => "InscriptionPhotograph",//"InscriptionRubbing","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
      "url" => "/images/manuscripts/136/CKM0136.3.JPG"
    )
  );

  $textItem = array(
    "title"=>"image of standing Buddha",
   // "type" => "typeOfItemTerm",
   // "shape" => "shapeOfItemTerm",
   //"measure" => "Unit:cm, Height:,Width:,Depth:",
   //"image_ids" => array("img1")
  );

  $textParts = array(
    "prt1" => array(
      "label"=>"plate 1",
      "mediums" => array("copper"),
      "measure" => "Unit:cm, Height:4.5,Width:18.7",
      "sequence" => 1,
      "image_ids"  => array("img1")
    ),
    "prt2" => array(
      "label"=>"plate 2",
      "mediums" => array("copper"),
      "measure" => "Unit:cm, Height:4.5,Width:18.7",
      "sequence" => 2,
      "image_ids"  => array("img2,img3")
    )
  );

  $materialContexts = array(
    "mtx1" => array(
      "arch_context" => "Jagayyapeta",
      ////"find_status" => ""
    )
  );

  $textFragments = array(
    "fra1" => array(
      "label"=>"plate 1",
      //"measure" => "Unit:cm, Height:,Width:",
      //"restore_state" => "original",
      "location_refs" => array("Hyderabad, in the Museum, 794"),
      "part_id"  => "prt1",
      "material_context_ids" => array("mtx1"),
      "image_ids"  => array("img1")
    ),
    "fra2" => array(
      "label"=>"plate 2",
      //"measure" => "Unit:cm, Height:,Width:",
      //"restore_state" => "original",
      "location_refs" => array("Hyderabad, in the Museum, 794"),
      "part_id"  => "prt2",
      "material_context_ids" => array("mtx1"),
      "image_ids"  => array("img2,img3")
    )
  );

  $textSurfaces = array(
    "srf1" => array(
      //"text_ids" => array("toBeFilledIn"),
      "description" =>"verso",
      "number" => 2,
      //"layer_number" => 1,
//      "scripts" => array(""),
      "fragment_id"  => "fra1",
      "image_ids"  => array("img1")
    ),
    "srf2" => array(
      //"text_ids" => array("toBeFilledIn"),
      "description" =>"recto",
      "number" => 1,
      //"layer_number" => 1,
//      "scripts" => array(""),
      "fragment_id"  => "fra2",
      "image_ids"  => array("img2")
    ),
    "srf3" => array(
      //"text_ids" => array("toBeFilledIn"),
      "description" =>"verso",
      "number" => 2,
      //"layer_number" => 1,
//      "scripts" => array(""),
      "fragment_id"  => "fra2",
      "image_ids"  => array("img3")
    )
  );

  $textImageBaselines = array(
    "bln1" => array(
      "imgID" => "img1",
      "surface_id" => "srf1",
    ),
    "bln2" => array(
      "imgID" => "img2",
      "surface_id" => "srf2",
    ),
    "bln3" => array(
      "imgID" => "img3",
      "surface_id" => "srf3",
    )
  );


?>
