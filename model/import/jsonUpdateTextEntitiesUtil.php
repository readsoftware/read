<?php
  require_once (dirname(__FILE__) . '/../../common/php/utils.php');
  $textCKN = "CKI0222.1";

  $entitiesConfig = array(
    "imageDefs" => array(
      "img1" => array(// img1 is a text local nonce that will be used to lookup the image if exist for deduplication
              //it can also be a image ID (int)
        "title" => "Avalokeśvara Inscription",
        "type" => "InscriptionPhotograph",//"InscriptionPhotograph","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
        "url" => "/images/Gandhari/txt222/s_CKI0222.1.jpg",
        "attribution" => array(
                        "title" => "Dani 1958",
                        "description" => "",
                        "bibliographyid" => null,
                        "type" => "reference",
                        "detail" => "",
                        "aedid" => null,
                        "usergroupid" => null
                      )
      //"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
      ),
      "img2" => array(// img1 is a text local nonce that will be used to lookup the image if exist for deduplication
          //it can also be a image ID (int)
        "title" => "Avalokeśvara Relief",
        "type" => "InscriptionPhotograph",//"InscriptionPhotograph","InscriptionEyeCopy","InscriptionPhotograph","ReliquaryPhotograph","EyeCopy","ManuscriptReconstruction","ManuscriptConserved","InscriptionPhotographInfraRed","ReconstructedSurface",
        "url" => "/images/Gandhari/txt222/s_CKI0222.2.jpg",
        "attribution" => array(
                          "title" => "Dani 1958",
                          "description" => "",
                          "bibliographyid" => null,
                          "type" => "reference",
                          "detail" => "",
                          "aedid" => null,
                          "usergroupid" => null
                        )
        //"polygon" => new Polygon("(55,65),(34,63),(45,95)) //cropping polygon
      )
    )
  );

  updateTextEntites($textCKN, $entitiesConfig); 


?>
