<?php

  $imageDefs = array(
    "img1" => array(// itmimg1 is a local nonce that will be used to lookup the image if exist for deduplication
                    //it can also be a image ID (int)
      "url" => "/images/gandhari/myitem.png",
      "attribution" => array(
                              "title" => "Tom Smith",
                              "description" => "snapshot of Rock",
                              //"bibliographyid" => null,
                              "type" => "reference",//Edition,Reference,Source,Catalog,Lexicon,Content,Parallel,Annotation,Comment,Image,Spurious,PrimaryEdition,SecondaryEdition,VisualDocumentation,Lemma,ArchaeologicalReport
                              //"detail" => "",
                              "aedid" => null,
                              "usergroupid" => null
														),
      //"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
      "type" => "EyeCopy"//"InscriptionPhotograph","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
		),
		"img2" => array(// txtimg1 is a local nonce that will be used to lookup the image if exist for deduplication
											//it can also be a image ID (int)
			"title" => "Epitaph 1 text",
			"url" => "/images/gandhari/mytxt.jpg",
			"attribution" => array(
											"title" => "Jane Euler",
											"description" => "Snapshot of text",
											//"bibliographyid" => null,
											"type" => "reference",//Edition,Reference,Source,Catalog,Lexicon,Content,Parallel,Annotation,Comment,Image,Spurious,PrimaryEdition,SecondaryEdition,VisualDocumentation,Lemma,ArchaeologicalReport
											//"detail" => "",
											"aedid" => null,
											"usergroupid" => null
										),
			//"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
			"type" => "EyeCopy"//"InscriptionPhotograph","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
		)
  );
 
  $itemTexts = array(
    "txt1" => array(
				"title" => "RockyRoad Edict 1",
				"image_ids"  => array("img2"),
				/*"attribution" => array(
																"title" => "",
																"description" => "",
																//"bibliographyid" => null,
																"type" => "reference",//Edition,Reference,Source,Catalog,Lexicon,Content,Parallel,Annotation,Comment,Image,Spurious,PrimaryEdition,SecondaryEdition,VisualDocumentation,Lemma,ArchaeologicalReport
																//"detail" => "",
																"aedid" => null,
																"usergroupid" => null
															),*/
				//"shortName" => "A2k",
				"ckn" =>"CKN####.n"
      )
  );

  $textMetadatas = array(
		"tmd1" => array(//"id"=>"nonce_string_or_id_if_updating_existing",
			"textid"=>"txt1",
			"scratch" => array("CKN"=>"CKN####.n","type"=>"Royal Edict","ruler"=>"A\u015boka"),
			"references" => array(118,119),
		)
  );

  $importItem = array(
		//"id"=>"nonce_string_or_id_if_updating_existing",
		"title"=>"Rock on the Road",
		"description"=>"Rock found on the Road to Mumbi",
		"subject" => "Stone",
		//"shape" => "shapeOfItemTerm",
		//"measure" => "Unit:cm, Height:,Width:,Depth:",
		"scratch" => array("Type"=>"Royal Edict","textids"=> array("txt1")),
		"image_ids" => array("img1"),
  	"idno"=>"CKN#####"
  );

  $itemParts = array(
    "prt1" => array(
      "label"=>"",
			//"description"=>"Rock chip found on the Road to Mumbi",
			// "type" => "typeOfPartTerm",//Boulder,Garland holder bracket,Buddha footprints,Writing-board,Pedestal,Plate,
																		//Water-giver,Cornelian,Kārṣāpanas,Tank,Frieze,Seal,Pillar,Jar (fragment),
																		//Toilet-tray,Inkwell,Vase,Cup,Sieve,Volute bracket (with winged male figure),
																		//Ring,Token,Rock wall,Ring seal,Pot,Ladle,Buddha statue, Sculpture,Well,
																		//Water giver,Slab,Auspicious ground,Stūpa,Jar (fragments),Six lamps,
																		//Model stūpa pedestal,Dabber,Bodhisattva,Mould (for ear‐pendants),Chapel,
																		//Stūpa,Lamp,Small box,Bhadavala,Brick,Pond,Frieze, Buddha, Maitreya,
																		//Vajrapāṇi,Stone,Sculpture,Reliquary,Buddha statue,Plaque,Well,Bowl,
																		//Lotus pond,Unknown,Relief fragment,Mural,Collar‐bone legend,Rock,Relief,
																		//Sherds,Intaglio,Frieze fragment,Sherd,Goblet,Pavement stone,Round stone,Sculpture

      // "shape" => "shapeOfPartTerm",
			"mediums" => array("Stone"),//Birch bark,Palm leaf,Paper,silk,Chalcedony,Steatite,Agate,Black glass,
																	//Red stone,Rock Crystal,Cornelian,Stone,pottery,Grey schist,Terracotta,
																	//Marble,Clay,Schist,Silver,Plaster,Slate,gold,Green schist,Stucco,Bronze,
																	//Green phyllite,gemstone,brass,glass,Gilded silver,Granite,Earthware,Copper,
																	//Schist,Gray schist,Schist,Garnet intaglio,Limestone,Gilded bronze
      //"measure" => "Unit:cm, Height:,Width:,Depth:",
      //"item_id" => "toBeFilledIn", //filled with item id above
      //"image_ids"  => array("img?"),
      "sequence" => 1  // 1 is default, set so last property in array without comma
			)
  );

  $materialContexts = array(
    "mtx1" => array(
      //"find_status" => "",
      "arch_context" => array("area"=>"Mansehra, Khyber Pakhtunkhwa, Pakistan")
			//"scratch" => array("prop1"=>"value1","prop2"=>"value2"),
			)
  );

  $partsFragments = array(
    "fra1" => array(
      "label"=>"simpFra",
      "description" =>"sample frag",
      //"measure" => "Unit:cm, Height:,Width:",
      //"restore_state" => "original",
      "location_refs" => array("Mansehra, Khyber Pakhtunkhwa, Pakistan"),
      "material_context_ids" => array("mtx1"),
			//"image_ids"  => array("img2"),
			//"scratch" => array("prop1"=>"value1","prop2"=>"value2"),
			"part_id"  => "prt1" //must match a part nonce or existing part id
    )
  );

  $fragmentSurfaces = array(
    "srf1" => array(
      "text_ids" => array("txt1"),
      "label" =>"Recto",
      //"description" =>"",
      //"number" => 1,
      //"layer_number" => 1,
      "scripts" => array("Krah"),
      //"image_ids"  => array("img?"),
      //"layer_number" => 1,
			"number" => 1,
      "fragment_id"  => "fra1"
      )
  );

?>
