<?php
//    include_once dirname(__FILE__) . '/../tests/testSetup.php';
    require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
    require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/utility/parser.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/Grapheme.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/Segment.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/SyllableCluster.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/Token.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/Lemma.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/Annotation.php');//get utilities
    require_once (dirname(__FILE__) . '/../model/entities/Compound.php');//get utilities
 $userID = 4;
 header('content-type: text/html; charset: utf-8');
 //echo md5("GudrunMe");
/*      $image_pos='{"((219,706),(1460,706),(1449,921),(219,921))"}';
      $categories='{"InscriptionRubbing","Inscription Photo"}';
      $url='http://localhost/Gandhari.org/images/32/CKI0032_2.PNG';
      $visibility_ids='{2}';
      $attribution_ids='{41}';
      $arg=array();
      $arg['img_image_pos']=$image_pos;
      $arg['img_categories']=$categories;
      $arg['img_url']=$url;
      $arg['img_visibility_ids']=$visibility_ids;
      $arg['img_attribution_ids']=$attribution_ids;
      $image = new Image($arg);

  $image = new Image(26);
      var_dump($image);
      $image->setBoundary($image_pos);
      $image->setOwnerID(14);
      if ($image->save()){
        echo "<br> image was saved as id ".$image->getID()."<br>";
      };
*/
/*
$parserConfigs = array(
  createParserConfig(null,"{2}","{1}","CKI0060","i_text960","rev",null,"3",3,null,"^1^ie Takṣaśi⟨*la⟩e ta⟨*ṇu⟩vae bosi‐sa[tva]‐gahami maha‐⟨*ra⟩jasa raja~a,a‐a~tirajasa"),
  createParserConfig(null,"{1}","{8}","CKM0239","m_text3863","r",null,"5",5,"RSS1","1 yeṇa aʔiṣpa aṇada ☸^9^ ◊ [c.d.ṇ. ^10^?ri?] ? ? [ṇa] ^11^◊ ^12^aṇaṇavosae ◊ evam=eva driga ◊ [s.s̱i^13^] ///"),
  createParserConfig(null,"{2}","{39}","CKM0077","m_text2393","r","Dhp‐GK","101",103,"Dhp‐GKS1","!148!«98a»hiri tasa avaramu «98b»svadisa parivaraṇa «98c»dhamaha saradhi bromi «98d»same‐diṭhi‐purejavu ◦"),
  createParserConfig(null,"{2}","{6}","CKM0011","m_text12588","V","Nid-GL2","GCv12",259,"BLS04","+ + + + + + /// ? [kh]. [śa]maso · [eva] śamasa‐vi⟨*va⟩[śaṇa]e [ma]go · taṣ̄ae samudag[o] · dukha[sa sa]///(*bhavo)")
);
*/
//    include_once (dirname(__FILE__) . '/../model/migration/AzesBibliographyMigration.php');//get utilities
//exit;
// $ownerID = 2,$vis,$attr,$ckn,$trid,$txtid,$mask,$order,$scribe,$edition,$side = null,$part = null,$fragment = null
/*
$parserConfigs = array(
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text958",null,"1",1,null,"«A»sa 1 100 20 10 4 1 1 Ayasa Aṣaḍasa masasa divase 10 4 1 iśa diva[se pradi]stavita bhagavato dhatu[o] Ura[sa]="),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text959",null,"2",2,null,"keṇa [Iṃ]tavhria‐putraṇa «B» Bahalieṇa Ṇoacae ṇagare vastaveṇa teṇa ime pradistavita bhagavato dhatuo Dhamara="),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text960",null,"3",3,null,"ie Takṣaśi⟨*la⟩e taṇuvae bosisatva‐gahami maha‐rajasa «C»raja~a,a‐a~tirajasa deva‐putrasa Khuṣaṇasa aroga‐dakṣiṇae 20"),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text961",null,"4",4,null,"4 1 1 «D» sarva‐[bu]dhaṇa puyae pracaga‐budhaṇa^*2^ puyae araha⟨*ta⟩ṇa pu[ya]e sarva‐sa(*tva)ṇa puyae mata‐pitu puyae mitra~a,a‐a~maca‐ñati‐sa="),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text962",null,"5",5,null,"lohi⟨*ta⟩ṇa [pu]yae atvaṇo aroga‐dakṣiṇae ṇivaṇae hotu a[ya] de‐sama‐paricago^*1^")
);
*/
/*
$parserConfigs = array(
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text958",null,null,"1",1,null," dhatu[o] Ura[sa]="),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text959",null,null,"2",2,null,"keṇa [Iṃ]tavhria‐putraṇa Dhamara="),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text960",null,null,"3",3,null,"ie Takṣaśi⟨*la⟩e taṇuvae aroga‐dakṣiṇae"),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text961",null,null,"4",4,null,"sarva‐[bu]dhaṇa puyae pracaga‐budhaṇa^*2^ mata‐pitu puyae mitra~a,a‐a~maca‐ñati‐sa="),
  createParserConfig(2,"{2}",'{1}',"CKI0060","i_text962",null,null,"5",5,null,"lohi⟨*ta⟩ṇa  de‐sama‐paricago^*1^")
);
*/
/*$lemma = new Lemma();
$str = "mahā·rājan";
echo "Setting value = $str <br>";
echo "Lemma sort = ".$lemma->getSortCode()."<br>";
$lemma->setValue($str);
echo "Lemma value = ".$lemma->getValue()."<br>";
echo "Lemma search = ".$lemma->getSearchValue()."<br>";
echo "Lemma sort = ".$lemma->getSortCode()."<br>";
*/
/*
$tokens = new Tokens();
foreach ($tokens as $token) {
  $token->calculateValues();
  $token->save();
  if ($token->hasError()) {
    echo $token->getErrors(true)."<br>";
  }
}

$compounds = new Compounds("not cmp_id=0");
$compounds->setAutoAdvance(true);
foreach ($compounds as $compound) {
  $compound->calculateValues();
  $compound->save();
  if ($compound->hasError()) {
    echo $compound->getErrors(true)."<br>";
  }
}
*/
/*
$parserConfigs = array(
//  createParserConfig(2,"{2}",'{1}',"CKI0557","i_text1185",null,null,"1",1,null,"sevetsara catu‐satatimi 20 20 20 10 4"),
//  createParserConfig(2,"{2}",'{1}',"CKI0557","i_text1186",null,null,"2",2,null,"dudha 1 Zaṇatraṃ⟨*mi⟩^1186§01^ Budhadevasa vira daṇa‐mukhe")
//  createParserConfig(2,"{2}",'{1}',"CKI0059","i_text944",null,null,"1",1,null,"daṇami tar[u]ka 1 1 p(*u)ña‐kareṇe~e,a e~va amata śiva‐thala rama . . . ma"),
//  createParserConfig(2,"{2}",'{1}',"CKI0602","i_text1262",null,null,"2",1,null,"(*deya‐dharmo~o,o a~)///[y]aṃ saṃghami cadu‐diśa(*mi) ///"),
//  createParserConfig(2,"{2}",'{1}',"CKI0647","i_text1310",null,null,"3",1,null,"(*deya‐dharmo~o,o a~yaṃ . . . saṃghami cadu‐diśami acaryana maha‐saṃghigana pari)///[gra]hami imena kuśala‐m[u]///(*lena . . . bhavatu)"),
//  createParserConfig(2,"{2}",'{1}',"CKI0195","i_text1118",null,null,"4",1,null,"/// Soñatina‐putra‐dana anaṣa‐Vo="),
//  createParserConfig(2,"{2}",'{1}',"CKI0195","i_text1119",null,null,"4",2,null,"ghasa~a,a a~caryana‐bhiṣu Naradakha cira‐tithito")
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','U',1,null,'!0!«0a»Budhavarmasa ṣamaṇasa «0b»Budhaṇadisa[r]dhavayarisa «0c»ida Dharmapadasa postaka «0d»Dharmaśraveṇa likhida arañi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','1',2,null,'«I» !1!«I.1a»na jaḍaï na gotreṇa «I.1b»na yaca bhodi bramaṇo «I.1c»yo du brahetva pavaṇa «I.1d»aṇuthulaṇi sarvaśo «I.1e»brahidare va pavaṇa «I.1f»brammaṇo di pravucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','2',3,null,'!2!«I.2a»ki di jaḍaï drumedha «I.2b»ki di ayiṇa‐śa[ḍ]ia «I.2c»adara gahaṇa kitva «I.2d»bahire parimajasi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','3',4,null,'!3!«I.3a»yasa dha[r]mo viaṇea «I.3b»same‐sabudha‐deśida «I.3c»sakhaca ṇa namasea «I.3d»agi‐hotra ba brammaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','4',5,null,'!4!«I.4a»na yaca brammaṇo bhodi «I.4b»na trevija na śotria «I.4c»na agi‐parikiryaï «I.4d»udake oruhaṇeṇa va ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','5',6,null,'!5!«I.5a»purve‐nivasa yo uvedi «I.5b»svaga avaya ya paśadi «I.5c»atha jadi‐kṣaya prato «I.5d»abhiña‐vosido muṇi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','6',7,null,'!6!«I.6a»edahi trihi vijahi «I.6b»treviju bhodi brammaṇu «I.6c»vijacaraṇasabarṇo «I.6d»brammaṇo di pravucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','7',8,null,'!7!«I.7a»trihi vijahi sabarṇo «I.7b»śadu kṣiṇa‐puṇarbhavu «I.7c»asido sarva‐lokasya «I.7d»brammaṇo di pravucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','8',9,null,'!8!«I.8a»taveṇa bramma‐yiryeṇa «I.8b»sañameṇa dameṇa ca «I.8c»edeṇa brammaṇo bhodi «I.8d»eda brammaña utamu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','9',10,null,'!9!«I.9a»china sodu parakamu «I.9b»kama praṇuyu bramaṇa «I.9c»na aprahaï muṇi kama «I.9d»ekatvu adhikachadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','10',11,null,'!10!«I.10a»china sodu parakamu «I.10b»kama praṇuyu bramaṇa «I.10c»sagharaṇa kṣaya ñatva «I.10d»akadaño si brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','11',12,null,'!11!«I.11a»na brammaṇasa praharea «I.11b»na~a,a a~sa mujea bramaṇi «I.11c»dhi bramaṇasa hadara «I.11d»tada vi dhi yo ṇa mujadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','12',13,null,'!12!«I.12a»madara pidara j̄atva «I.12b»rayaṇa dvayu śotria «I.12c»raṭha saṇayara j̄atva «I.12d»aṇiho yadi brammaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','13',14,null,'!13!«I.13a»rayaṇa pradhamu j̄atva «I.13b»pariṣa ja aṇadara «I.13c»doṣi sa‐señaka j̄atva «I.13d»aṇiho yadi brammaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','14',15,null,'!14!«I.14a»yada dvaeṣu dharmeṣu «I.14b»parako bhodi brammaṇo «I.14c»atha~a,a a~sa sarvi sañoka «I.14d»astag̱achadi jaṇada ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','15',16,null,'!15!«I.15a»na bramaṇase~e,a e~diṇa ki ji bhodi «I.15b»yo na nisedhe maṇasa priaṇi «I.15c»yado yado ya~a,a a~sa maṇo nivartadi «I.15d»tado tado samudim aha saca ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','16',17,null,'!16!«I.16a»brahetva pavaṇi brammaṇo «I.16b»samaïrya śramaṇo di vucadi «I.16c»parvahia atvaṇo mala «I.16d»tasa parvaïdo di vucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','17',18,null,'!17!«I.17a»na aho brammaṇa bromi «I.17b»yoṇeka‐matra‐sabhamu «I.17c»bho‐vaï namu so bhodi «I.17d»sayi bhodi sakijaṇo «I.17e»akijaṇa aṇadaṇa «I.17f»tam aho bro⟨*mi bra⟩mmaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','18',19,null,'!18!«I.18a»nihaï daṇa bhudeṣu «I.18b»traseṣu thavareṣu ca «I.18c»yo na hadi na ghadhedi «I.18d»tam aho bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','19',20,null,'!19!«I.19a»yo du drigha ci rasa ji «I.19b»aṇo‐thulu śuhaśuhu «I.19c»loki adiṇa na adiadi «I.19d»tam aho brommi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','20',21,null,'!20!«I.20a»yo du kama prahatvaṇa «I.20b»aṇakare parivaya «I.20c»kama‐bhoka‐parikṣiṇa «I.20d»tam aho bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','21',22,null,'!21!«I.21a»vari puṣkarapatre va «I.21b»arage r iva sarṣava «I.21c»yo na lipadi kamehi «I.21d»tam ahu bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','22',23,null,'!22!«I.22a»akakaśa viñamaṇi «I.22b»gira saca udiraï «I.22c»yaï na~a,a a~viṣaï ka ji «I.22d»tam ahu bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','23',24,null,'!23!«I.23a»yasya kaeṇa vayaï «I.23b»maṇasa na~a,a a~sti drukida «I.23c»savrudu trihi ṭ́haṇehi «I.23d»tam aho bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','24',25,null,'!24!«I.24a»vaśada varada «I.24b»mana‐bhaṇi aṇudhada «I.24c»artha dharma ja deśedi «I.24d»tam aho bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','25',26,null,'!25!«I.25a»vaśada varada «I.25b»mana‐bhaṇi aṇudhada «I.25c»utamatha aṇuprato «I.25d»tam aho bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','26',27,null,'!26!«I.26a»yasya rako ca doṣo ca «I.26b»avija ca viraïda «I.26c»kṣiṇasavu arahada «I.26d»tam ahu bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','27',28,null,'!27!«I.27a»yasya rako ca doṣo ca «I.27b»maṇu makṣu pravadido «I.27c»paṇa‐bhara visañutu «I.27d»tam ahu bromi brammaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','28',29,null,'!28!«I.28a»ak[r]ośa vadha‐ban̄a ca «I.28b»aduṭh[u] yo tidikṣadi «I.28c»kṣadi‐bala balaṇeka «I.28d»tam ahu bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','29',30,null,'!29!«I.29a»avirudhu virudheṣu «I.29b»ata‐daṇeṣu nivudu «I.29c»sadaṇeṣu aṇadaṇa «I.29d»tam aho bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','30',31,null,'!30!«I.30a»yo idhe~e,a e~va preaṇadi «I.30b»dukhasa kṣaya atvaṇo «I.30c»vipramutu visañutu «I.30d»tam aho bromi brammaṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','31',32,null,'!31!«I.31a»na~a,a a~vaj̄aï na~a,a a~vij̄apu «I.31b»pavaka na vicidaï «I.31c»sadaśam atha‐daśavi «I.31d»tam aho bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','32',33,null,'!32!«I.32a»asatsiṭha ghahaṭ́hehi «I.32b»aṇakarehi yu~u,a u~haï «I.32c»aṇova‐sari apicha «I.32d»tam aho brommi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','33',34,null,'!33+65!«I.33a»aradi radi ca yo hitva !33!«I.33b»sab[r]ayaṇ[o] pradisvado «I.33c»sarva‐bhava‐parikṣiṇa «I.33d»tam aho brommi brammaṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','34',35,null,'!64!«I.34a»yasa pure ya pacha ya «I.34b»+ + + + + + + !34!.i «I.34c»akijaṇa aṇadaṇa «I.34d»tam ahu brommi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','35',36,null,'!63!«I.35a»yasa pari avare ca «I.35b»para(*vare)^2326§01^ + + + + !35!«I.35c»vikadadvara visañota «I.35d»tam aho brommi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','36',37,null,'!62+66!«I.36a»chitvaṇa paja saṃdaṇa «I.36b»+ + + + + + + + !36!«I.36c»nani‐bhava‐parikṣiṇa «I.36d»tam ahu bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','37',38,null,'!67!«I.37a»ruva chada rasa gan̄a «I.37b»phaṣa dhama ya kevala !37!«I.37c»prahaï na paritasadi «I.37d»tam ahu brommi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','38',39,null,'!68!«I.38a»patsukula‐dhara jadu «I.38b»kiśa dhamaṇi‐sadhada !38!«I.38c»jayada rukha‐mulasya «I.38d»tam ahu brommi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','39',40,null,'!69!«I.39a»eṇe‐jag̱a kiśa vira «I.39b»apahara alolubh!39!u «I.39c»apaṭ́ha apa‐kica ji «I.39d»tam ahu brommi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','40',41,null,'!70!«I.40a»akrodhu aṇuvayasaṃ «I.40b»viprasana aṇavila !40!«I.40c»cadra ba vimali śudhu «I.40d»tam ahu bromi brammaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','41',42,null,'«I.41a» + + + (*prava)!71![ra^2332§01^ dhira] «I.41b»(*ma)[h](*eṣi)^2332§02^ [v]iy[i]daviṇo !41!«I.41c»aṇiha ṇadaka budhu «I.41d»tam ahu bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','42',43,null,'!72!«I.42a»chetva nadhi valatra ya «I.42b»sadaṇa samadikrammi !42!«I.42c»ukṣita‐phalia vira «I.42d»tam aho brommi brammaṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','43',44,null,'!73!«I.43a»yasa gadi na jaṇadi «I.43b»deva gan̄avamaṇ(*uṣa)^2334§01^ !43!«I.43c»tadhakadasa budhasa «I.43d»tam ahu brommi bramaṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','44',45,null,'!94!«I.44a»yo cudi uvedi satvaṇa «I.44b»vavati ca !480!vi sa!44!rvaśo «I.44c»budhu adima‐śarira «I.44d»tam aho bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','45',46,null,'!95!«I.45a»akrodhu aṇuvayasa «I.45b»vipramutu p[u]ṇa[r]bhava !45!«I.45c»budhu vada‐mala dhira «I.45d»tam aho bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','46',47,null,'!96!«I.46a»yo du puñe ca pave ca «I.46b»uhu ṣag̱a uvacaï !46!«I.46c»aṣag̱a viraya budhu «I.46d»tam ahu bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','47',48,null,'!97!«I.47a»jaï parakada budhu «I.47b»jidavi akadaggadi !47!«I.47c»pruju devamaṇuśaṇa «I.47d»tam ahu brommi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','48',49,null,'!98!«I.48a»jaï para‐kada budhu «I.48b»kida‐kica aṇasr̥vu !48!«I.48c»budhu daśabaloveda «I.48d»tam ahu bromi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','49',50,null,'!99!«I.49a»gammira‐praña medhavi «I.49b»ma!99+471!rgamargasa !99!koia !49!«I.49c»utamu pravara vira «I.49d»tam ahu brommi bramaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','50',51,null,'!100!«I.50a»diva tavadi adicu «I.50b»radi avhaï cadrimu «I.50c»sanadhu kṣa!50!trio tavadi «I.50d»j̄aï tavadi bramaṇo «I.50e»adha sarva aho‐ratra «I.50f»budhu tavadi teyasa 20 20 10','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','51',52,null,'«II» !101!«I.51a»kaeṇa savrudo bhikhu «I.51b»atha vayaï !101+466!savru!101!do !51!«I.51c»maṇeṇa savrudo bhikhu «I.51d»sarva druggadeo jahi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','52',53,null,'!102!«II.52a»kaeṇa sañamu sadhu «II.52b»sadhu vayaï sañamu «II.52c»maṇeṇa sañamu sadhu !52!«II.52d»sadhu savatra sañamu «II.52e»sarvatra sañado bhikhu «II.52f»sarva dugadio jahi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','53',54,null,'!103!«II.53a»hasta‐sañadu pada‐sañadu «II.53b»vaya‐sañadu savudidrio «II.53c»aj̄atva‐!53!rado samahido «II.53d»eko saduṣido tam ahu bhikhu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','54',55,null,'!104!«II.54a»yo muheṇa sañado bhikhu «II.54b»maṇa‐bhaṇi aṇudhado «II.54c»artha dhar!54!ma ci deśedi «II.54d»masuru tasa bhaṣida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','55',56,null,'!105!«II.55a»śuñakare praviṭhasa «II.55b»śada‐citasa bhikhuṇo «II.55c»amaṇuṣa‐radi !55!bhodi «II.55d»sa[me] dhar[ma] vivaśadu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','56',57,null,'!106!«II.56a»yado yado sammaṣadi «II.56b»kanaṇa udaka‐vaya «II.56c»lahadi pridi‐!56!pramoju «II.56d»amudu ta viaṇadu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','57',58,null,'!107!«II.56Aa»śuñakare praviṭhasa «II.56Ab»śada‐citasa bhikhuṇo «II.56Ac»ama!57!ṇuṣa‐radi bhodi «II.56Ad»same dharma vivaśadu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','58',59,null,'!108!«II.57a»pajag!108+474!ieṇa turieṇa «II.57b»na !108!radi bhodi tadiśa «II.57c»[ya]!58!tha [e]kaga‐citasa «II.57d»same dharma vivaśadu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','59',60,null,'!109!«II.58a»na~a,a a~sti j̄aṇa aprañasa «II.58b»praña na~a,a a~sti aj̄ayado «II.58c»yasa jaṇa ca praña ya «II.58d»so hu !59!nirvaṇasa sadii ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','60',61,null,'!110!«II.59a»tatra~a,a a~ï adi bhavadi «II.59b»tadha prañasa bhikhuṇo «II.59c»idriagoti saduṭhi «II.59d»pradimukhe i (*sava)!60!r[o]^2352§01^ ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','61',62,null,'!111!«II.60a»mitra bhayea paḍiruva «II.60b»śudhayiva ata[dr]idi «II.60c»paḍisa!111+479!dharagu!111!ti~i,i a~sa «II.60d»ayarakuśa(*lo sia)^2353§01^','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','62',63,null,'!112!«II.60e»tadu ayara‐kuśalo «II.60f»suhu bhikhu vihaṣisi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','63',64,null,'!113!«II.61a»salavhu na~a,a a~dimañea «II.61b»na~a,a a~ñeṣa svihao sia «II.61c»añeṣa svihao bhikhu «II.61d»samadhi na~a,a a~dhikachadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','64',65,null,'!114!«II.62a»apa‐labho du yo bhikhu «II.62b»salavhu na~a,a a~dimañadi «II.62c»ta gu deva praśaj̄adi «II.62d»śudhayivu atadrida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','65',66,null,'!115!«II.63a»kamaramu kama‐radu «II.63b»kamu aṇuvicidao «II.63c»kamu aṇusvaro bhikhu !115+477!«II.63d»sadharma pariha!115!yadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','66',67,null,'!116!«II.64a»dhamaramu dhama‐radu «II.64b»dhamu aṇuvicidao «II.64c»dhamu aṇusvaro bhikhu «II.64d»sadharma na parihayadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','67',68,null,'!117!«II.65a»na śila‐vada‐matreṇa «II.65b»baho‐ṣukeṇa va maṇo «II.65c»adha samadhi‐labheṇa «II.65d»vevita‐śayaṇeṇa va ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','68',69,null,'!118!«II.66a»phuṣamu nekhama‐sukhu «II.66b»aprudha‐jaṇa‐sevida «II.66c»bhikhu viśpaśa ma~a,a a~vad[i] !118+476!«II.66d»apra!118!te asava‐kṣaye ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','69',70,null,'!119!«II.67a»na bhikhu tavada bhodi «II.67b»yavada bhikṣadi para «II.67c»veśma dharma samadaï !119+473!«II.67d»bhikhu bhodi na !119!tavada ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','70',71,null,'!120!«II.68a»yo du baheti pavaṇa «II.68b»vadava bramma‐yiyava «II.68c»saghaï caradi loku «II.68d»so du bhikhu du vucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','71',72,null,'!121!«II.69a»metra‐vihari yo !472!bhikhu «II.69b»prasanu budha‐śaśaṇe «II.69c»duṇadi pavaka dharma «II.69d»druma‐patra ba maduru ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','72',73,null,'!122!«II.70a»metra‐vihara yo bhikhu «II.70b»prasanu budha‐śaśa[ṇ]e «II.70c»paḍivij̄u pada śada «II.70d»sagharavośamu suha ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','73',74,null,'!123!«II.71a»udaga‐citu yo bhikhu «II.71b»abhivuyu priapria «II.71c»adhikachi pada śada «II.71d»akavuruṣa‐sevida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','74',75,null,'!124!«II.72a»pramoja‐baholu yo bhikhu «II.72b»abhivuyu priapria «II.72c»adhikachi pada śada «II.72d»aseyaṇe moyaka ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','75',76,null,'!125!«II.73a»apramada‐radu yo bhikhu «II.73b»pramadi bhaya‐daśima «II.73c»abhavu parihaṇaï «II.73d»nivaṇase~e,a e~va sadii ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','76',77,null,'!126!«II.74a»apramada‐radu yo bhikhu «II.74b»pramadi bhaya‐da[śima] !202!«II.74c»sañoya!126!ṇa [aṇu]‐thu[l]a !475!«II.74d»ḍahu ag!126!i va gachadi [◦]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','77',78,null,'!127!«II.75a»jaï bhikhu ma yi pramati «II.75b»ma de kama‐guṇa bhametsu c!127+202!i!127!ta «II.75c»ma loha‐guḍa gili pramata «II.75d»kani dukham ida di ḍaj̄ama[ṇo]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','78',79,null,'!128!«II.76a»sija bhikhu ima nama «II.76b»sita di lahu bheṣidi «II.76c»chetva raka ji doṣa ji «II.76d»tado nivaṇa eṣidi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','79',80,null,'!129!«II.77a»kodhaṇa akidaña i «II.77b»drohiṇi pa[r]i[va]ja[ï] «II.77c»brama‐yirya cara !478!bhikhu !465+129!«II.77d»same‐sabudha‐śaśaṇi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','81',81,null,'!130!«II.78a»paja china paje jahi «II.78b»paja utvari‐bhavaï «II.78c»paja‐ṣag̱adhio bhikhu «II.78d»oha‐tiṇo di vucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','82',82,null,'!131!«II.79a»savaśu nama‐ruvasa «II.79b»yasa na~a,a a~sti mamaïda «II.79c»asata i na śoyadi «II.79d»so hu bhikhu du vucadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','83',83,null,'!132!«II.80a»alagido ya vi carea dhamu «II.80b»dadu śadu sañadu bramma‐yari «II.80c»saveṣu bhudeṣu nihaï daṇa «II.80d»so bramaṇo so ṣamaṇo so bhikhu','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','84',84,null,'!133!«II.81a»yo na~a,a a~jakamo bhavaṣ[u] sara «II.81b»viyi[ṇ]i p[u]ph[a] viva (*tvaya)^2375§02^ [puraṇa] «II.81c»s[o] bhikhu jahadi [o](*rapara)^2375§01^ !136a!«II.81d»urako jiṇa !133!viva udumareṣ[u]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','85',85,null,'!134!«II.82a»yo upad[i](*da) [v]iṇedi kodhu «II.82b»visaḍa + + + + + + + + + «II.82c» + + + + + + !204!orupa[r]a «II.82d»urako jiṇa v(*iva tva)!209!ya^2376§01^ puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','86',86,null,'!135!«II.83a»yo maṇa udavahi aśeṣa «II.83b»bisa(*puṣa)^2377§01^ + + + + + vikaśa «II.83c»so bhikhu jahadi [o](*ra)para^2377§02^ «II.83d»urako jiṇa viva tvaya puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','87',87,null,'!136!«II.84a»yo taṣ̄a udachaï aśeṣa «II.84b»sa[r](*ida)^2378§01^ + + + + + śoṣa[ï]tha «II.84c»so bhikhu jaha(*di)^2378§02^ orapara «II.84d»urako jiṇa viva tvaya puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','88',88,null,'!137!«II.85a»yo sarvakeleśa dalaïtha «II.85b»naḍa(*sedu)^2379§01^ + + + + + (*ma)hoho «II.85c»so (*bhi)kh[u ja]hadi orapara «II.85d»urako jiṇa viva tvaya puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','89',89,null,'!138!«II.86a»yo ecasari na precasari «II.86b»sar[v]a [iha] + + + + + + + «II.86c»so bhikhu jaha(*di)^2380§01^ orapara «II.86d»urako jiṇa viva tvaya puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','90',90,null,'!139!«II.87a»yo ne~e,a e~casari na precasari «II.87b»sarvu vidadham ida di ñatva (*lo)!134a!ku^2381§01^ !139!«II.87c»so bhikhu jahadi orapara «II.87d»urako jiṇa viva tvaya puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','91',91,null,'!140!«II.88a»yasa aṇośea na sadi ke yi «II.88b»oru akamaṇaï pracea i «II.88c»so bhikhu jahadi ora‐para «II.88d»urako jiṇa viva tvayo puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','92',92,null,'!141!«II.89a»yasa vaṇaśea na sadi ke yi «II.89b»viṇavanaü bhavaï hedu‐kapa «II.89c»so bhikhu jahadi ora‐para «II.89d»urako jiṇa viva tvaya puraṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','93',93,null,'«II.90a» + + + + + + + + + + «II.90b» + + + !142![t]i[ṇa]kadaka(*da)^2384§01^ [v]iśal[a] «II.90c»so bhikhu jahadi orapara «II.90d»urako jiṇa viva tvaya puraṇa 20 20','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',94,null,'. . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','94',95,null,'«III» «III.91a» + + + + + + + + + + «III.91b» + + + + + + + + + + «III.91c» + + + + (*horah)!464!oru^2386§01^ «III.91d»phalam ich!143!o va vaṇasma vaṇaru','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','95',96,null,'«III.92a» + + + + + + + + + + «III.92b» + + + + + + + + + + + «III.92c» + + + + + + !144!paśadha «III.92d»muto ban̄aṇam eva jayadi · «III.93a» + + + + + + + + «III.93b» + + + + + + + + «III.93c» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','96',97,null,'!145!yi «III.93d»nivaṇa bhodha bhikṣavi ◦ «III.94a» + + + + + + + + + + «III.94b» + + + + + + + + + + + «III.94c» + + + + + + + + + + «III.94d» + + !146!kṣira‐vayo va madara','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','97',98,null,'«III.95a» + + + + + + + + + + «III.95b» + + + + + + + + + + + «III.95c» + + + + + !459!suh[e]ṣiṇo «III.95d»yokam aed[i] !455!puṇapuṇu ciraï','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','98',99,null,'«III.96a» + + + + + + + + + + + «III.96b» (*sagapara)!462+457!ku puruṣasa kama «III.96c»ciṭhadu ci!432+460!t[r]aṇi tadhe~e,a e~va !451!loke «III.96d»atha~a,a a~tha dhira veṇeadi chana','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','99',100,null,'«III.96Aa» + + + + + + + + «III.96Ab» + + + + + + + + «III.96Ac» + + + (*bha)!458!va‐śa!452!laṇa «III.96Ad»sabrayaṇo pradisvado','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',101,null,'. . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',102,null,'«IV» . . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',103,null,'«V» . . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',104,null,'«VI» . . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','100',105,null,'!147!«VI.97a»[u]juo namu so magu «VI.97b»abhaya namu sa diśa «VI.97c»radho akuyaṇo namu «VI.97d»dharma‐trakehi sahado ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','101',106,null,'!148!«VI.98a»hiri tasa avaramu «VI.98b»svadi~i,i a~sa parivaraṇa «VI.98c»dhama~a,u a~ha saradhi bromi «VI.98d»same‐diṭhi‐purejavu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','102',107,null,'!149!«VI.99a»yasa edadiśa yaṇa «VI.99b»gihi parvaïdasa va «VI.99c»sa vi ediṇa yaṇeṇa «VI.99d»nivaṇase~e,a e~va sadia ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','103',108,null,'!150!«VI.100a»supraüdhu praüjadi «VI.100b»imi Godama‐ṣavaka «VI.100c»yeṣa diva ya radi ca «VI.100d»nica budha‐kada svadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','104',109,null,'!151!«VI.101a»supraüdhu praüj̄adi «VI.101b»imi Godama‐ṣavaka «VI.101c»yeṣa diva ya radi ca «VI.101d»nica dhamma‐kada svadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','105',110,null,'!152!«VI.102a»supraüdhu praüj̄adi «VI.102b»imi Godama‐ṣavaka «VI.102c»yeṣa diva ya radi ca «VI.102d»nica saḡ̱a‐kada svadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','106',111,null,'!153!«VI.103a»supraüdhu praüj̄adi «VI.103b»imi Godama‐ṣavaka «VI.103c»yeṣa diva ya radi ca «VI.103d»nica kaya‐kada svadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','107',112,null,'!154!«VI.104a»supraüdhu praüj̄adi «VI.104b»imi Godama‐ṣavaka «VI.104c»yeṣa diva ya radi ca «VI.104d»ahitsaï rado maṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','108',113,null,'!155!«VI.105a»supraüdhu praüj̄adi «VI.105b»imi Godama‐ṣavaka «VI.105c»yeṣa diva ya radi ca «VI.105d»bhamaṇaï rado maṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','109',114,null,'!156!«VI.106a»savi saghara aṇica di «VI.106b»yada prañaya paśadi «VI.106c»tada nivinadi dukha «VI.106d»e(*ṣo) !234!magu viśo!449!dh[i]a','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','110',115,null,'!157!«VI.107a»savi saghara dukha di «VI.107b»yada prañaï gradhadi «VI.107c»tada nivinadi dukha !235!«VI.107d»eṣo magu viśodhia','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','111',116,null,'!158!«VI.108a»sarvi dhama aṇatva di «VI.108b»yada paśadi cakhkṣuma «VI.108c»tada nivinadi dukha «VI.108d»eṣo mago viśodhia ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','112',117,null,'!159!«VI.109a»magaṇa aṭhaḡio śeṭho «VI.109b»sacaṇa caüri pada «VI.109c»viraku śeṭho dhamaṇa «VI.109d»praṇa‐bhudaṇa cakhuma ◦ ❉','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','112A',118,null,'!159A!ga 20 10','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','113',119,null,'«VII» !160!«VII.110a»udiṭha na pramajea «VII.110b»dhamu sucarida cari «VII.110c»dhama‐cari suhu śeadi «VII.110d»asvi loki parasa yi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','114',120,null,'!161!«VII.111a»uṭ́haṇeṇa apramadeṇa «VII.111b»sañameṇa dameṇa ca «VII.111c»divu karodi medhavi «VII.111d»ya jara na~a,a a~bhimardadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','115',121,null,'!162!«VII.112a»uṭ́haṇamado svadimado «VII.112b»suyi‐kamasa niśama‐cariṇo «VII.112c»sañadasa hi dhama‐jiviṇo «VII.112d»apramatasa yaśi~i,u i~dha vaḍhadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','116',122,null,'!163!«VII.113a»uṭ́haṇe‐alasa aṇuṭ́hehadu «VII.113b»yoi bali alasie uvidu «VII.113c»satsana‐sagapa‐maṇo svadima «VII.113d»prañaï maga alasu na vinadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','117',123,null,'!164!«VII.114a»na tavada dhama‐dharo «VII.114b»yavada baho bhaṣadi «VII.114c»yo du apa bi ṣutvaṇa «VII.114d»dhamu kaeṇa phaṣaï ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','118',124,null,'!165!«VII.114e»so ho dhama‐dharo bhodi «VII.114f»yo dhamu na pramajadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','119',125,null,'!166!«VII.115a»apramadu amuda‐pada «VII.115b»pramadu mucuṇo pada «VII.115c»apramata na miyadi «VII.115d»ye pramata yadha mudu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','120',126,null,'!167!«VII.116a»eda viśeṣadha ñatva «VII.116b»apramadasa paṇido «VII.116c»apramadi pramodia «VII.116d»ariaṇa goyari rado ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','121',127,null,'!168!«VII.117a»pramada aṇuyujadi «VII.117b»bala drumedhiṇo jaṇa «VII.117c»apramada du medhavi «VII.117d»dhaṇa śeṭhi va rakṣa!168+468!di ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','122',128,null,'!169!«VII.118a»apramatu pramateṣu «VII.118b»suteṣu baho‐jagaru «VII.118c»avalaśa va bhadraśu «VII.118d»hitva yadi sumedhasu !468!◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','123',129,null,'!170!«VII.119a»pramadu apramadeṇa «VII.119b»yadha nudadi paṇidu «VII.119c»praña‐prasada aruśu «VII.119d»aśoka śoiṇo jaṇa «VII.119e»pravada‐ṭ́ho !188a!va bhuma‐ṭ́ha «VII.119f»dhiru bala avekṣidi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','124',130,null,'!171!«VII.120a»apramadeṇa makavha «VII.120b»devaṇa samidhi gadu «VII.120c»apramada praśaj̄adi «VII.120d»pramadu gara!189a!hidu sada ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','125',131,null,'!172!«VII.121a»(*hi)ṇa dha[r]ma na sevaa «VII.121b»pramadeṇa na savasi «VII.121c»micha‐diṭhi na roy[e]a «VII.121d»na sia loka‐vaḍhaṇo','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','126',132,null,'!173!«VII.122a»yo du puvi pramajeti «VII.122b»pacha su na pramajadi «VII.122c»so ida loku ohasedi «VII.122d»abha muto va suriu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','127',133,null,'!174!«VII.123a»arahadha nikhamadha «VII.123b»yujatha budha‐śaśaṇe «VII.123c»dhuṇatha mucuṇo seṇa «VII.123d»naḍakara ba kuñaru ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','128',134,null,'!175!«VII.124a»apramata svadimada «VII.124b»suśila bhodu bhikṣavi «VII.124c»susamahida‐sagapa «VII.124d»sacita aṇurakṣadha ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','129',135,null,'!176!«VII.125a»yo imasma dhama‐viṇaï «VII.125b»apramatu vihaṣidi «VII.125c»prahaï jadi‐satsara «VII.125d»dukhusa~a,a a~da kariṣadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','130',136,null,'!177!«VII.126a»ta yu vadami bhadrañu «VII.126b»yavadi~i,a i~tha samakada «VII.126c»apramada‐rada bhodha «VII.126d»sadhami supravedidi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','131',137,null,'!178!«VII.127a»pramada parivajeti !469!«VII.127b»apramada‐rada sada «VII.127c»bhavetha kuśala dhama «VII.127d»yoka‐kṣemasa prataa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','132',138,null,'!179!«VII.128a»te kṣemaprata suhiṇo !470!«VII.128b»apramadavihar!454!io «VII.128c»phuṣad[i] + + (*niva)[ṇa]^2425§01^ «VII.128d» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','133',139,null,'!180!«VII.129a»apramadi pramodia «VII.129b»ma gami‐radi‐sabhamu «VII.129c»apramato hi j̄ayadu «VII.129d»viśeṣa adhikachadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','134',140,null,'!181!«VII.130a»apramadi pramodia «VII.130b»ma gami‐radi‐sabhamu «VII.130c»apramato hi jayadu «VII.130d»kṣaya dukhasa pramuṇi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','135',141,null,'«VII.131a» (*aprama)!182!darada^2428§01^ bhodha «VII.131b»khaṇo yu ma uvacaï «VII.131c»khaṇadida hi śoyadi «VII.131d»niraeṣu samapi[da]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','136',142,null,'!183!«VII.132a»apramadarada bhodha «VII.132b»sadhami supravedide «VII.132c»drugha udhvaradha atvaṇa «VII.132d»pagasana va kuña(*ru)^2429§01^','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','137',143,null,'!184!«VII.133a»na~a,a a~ï kalu pramadasa «VII.133b»aprati asava‐kṣaye «VII.133c»pramata duhu amedi «VII.133d»siha ba mruya‐madia ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','138',144,null,'!185!«VII.134a»na~a,a a~ï pramada‐samayu «VII.134b»aprati asava‐kṣayi «VII.134c»apramato hi jayadu «VII.134d»pranodi paramu sukhu ❉','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','138A',145,null,'!185A!ga 20 4 1','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','139',146,null,'«VIII» !186+440!«VIII.135a»diṭhadharmasuhatha!435!ï !431!«VIII.135b»sabarakaïda!437!ï ca «VIII.135c» + + !186![ru]khapra!461!h[a]ṇaṇi !429!«VIII.135d»cita rakṣe!186!a paṇid[u ◦]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','140',147,null,'!434!«VIII.136a»phanaṇa cava!436!la cita «VIII.136b»drurakṣa drunivaraṇa «VIII.136c»u(*ju)^2434§01^ + + + + + + «VIII.136d» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','141',148,null,'!441!«VIII.137Aa»duraga[ma] eka(*cara)^2435§01^ «VIII.137Ab» + + + + + + + + «VIII.137Ac» + + + + + + + + «VIII.137Ad» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','142',149,null,'!442!«VIII.137Ba»vario va thale kṣit(*o) «VIII.137Bb» + + + + + + + + «VIII.137Bc» + + + + + + + + «VIII.137Bd» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','143',150,null,'!443!«VIII.137Ca»aṇuvaṭ́hida‐citasa «VIII.137Cb» + + + + + + + + «VIII.137Cc» + + + + + + + + «VIII.137Cd» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','144',151,null,'!444!«VIII.137Da»aṇuvaṣuda‐cita[sa] «VIII.137Db» + + + + + + + + «VIII.137Dc» + + + + + + + + «VIII.137Dd» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','145',152,null,'!445!«VIII.138Aa»sudu(*daśa)^2439§01^ + + + + «VIII.138Ab» + + + + + + + + «VIII.138Ac» + + + + + + + + «VIII.138Ad» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','146',153,null,'!438!«VIII.138Ba»kummovamu ka[ya] + + + + + «VIII.138Bb» + + + + + + + + + + + + «VIII.138Bc» + + + + + + + + + + «VIII.138Bd» + + + + + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','147',154,null,'!446!«VIII.138Ca»[sa](*ma)[dhi] + + + + + «VIII.138Cb» + + + + + + + + «VIII.138Cc» + + + + + + + + «VIII.138Cd» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','148',155,null,'!447!«VIII.138Da»samadhi mu[c]i + + + «VIII.138Db» + + + + + + + + «VIII.138Dc» + + + + + + + + «VIII.138Dd» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','149',156,null,'!439!«VIII.138E»(*ci)///teṇa vaji[da] ///','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','149',157,null,'!430!«VIII.138E» /// yo d[u] vi [pra] ///','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','149',158,null,'!433!«VIII.138E» /// [tayo] ///','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',159,null,'. . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',160,null,'«IX» . . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',161,null,'«X» . . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','150',162,null,'«X.139Aa» + + + + + + + + «X.139Ab» + + + + + + + + «X.139Ac» + + + + + (*kṣaya)!450r![d]i^2444§01^ «X.139Ad»apa!463!matse va palare','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','151',163,null,'«X.139Ba» + + + + + + + + «X.139Bb» + + + + + + + + «X.139Bc» + + + + + + + + !453!«X.139Bd»pora[ṇa]ṇi [aṇu]svar[u]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',164,null,'. . .','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','152',165,null,'!187+501!«X.140a»[dh](*i)^2446§01^ + jimi jare astu «X.140b»dr[u](*vaṇakaraṇi)^2446§02^ [ja]r[e] «X.140c» + + + + + + + + «X.140d» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','153',166,null,'!188!«X.141a»yo vi varṣaśada jivi «X.141b»so vi mrucuparayaṇo «X.141c»na ka ji pari(*vajedi)^2447§01^ «X.141d» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','154',167,null,'!189!«X.142a»parijiṇam ida ruvu «X.142b»roaneḍa pravhaguṇo «X.142c»bhetsidi p[u]di(*sadeho)^2448§01^ «X.142d» + + + + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','155',168,null,'!190!«X.143a»ko nu harṣ[o] ki(*m a)[ṇano] !512!«X.143b»[ta]va pa(*j)va!481!lide sado «X.143c»anakarasm[a] pakṣiti «X.143d»pra(*divu)^2449§01^ + + + + +','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','156',169,null,'!191!«X.144a»yam eva paḍhama radi «X.144b»gabhi vasadi maṇavo «X.144c»aviṭ́hidu !482!so vayadi «X.144d»so gachu na nivatadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','157',170,null,'!192!«X.145a»yasa radivivasiṇa^2451§01^ «X.145b»ayu aparado sia «X.145c»apodake !483!va matsaṇa «X.145d»ki teṣa u kumulaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','158',171,null,'!193!«X.146a»ye vrudha ye ya dahara «X.146b»ye ca maj̄ima‐poruṣa !402!«X.146c»aṇ[u]pova (*pravaya)!484![d]i «X.146d»[pha]!499!la paka va banaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','159',172,null,'!194!«X.147a»yadha phalaṇa pakaṇa «X.147b»nice padaṇado bhayo «X.147c»emu jadasa macasa «X.147d»nica maraṇado bhayo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','160',173,null,'!195!«X.148a»yadha nadi pravadia «X.148b»rakṣa vahadi kulaya «X.148c»emu jara ya mruca ya «X.148d»aya payedi praṇiṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','161',174,null,'!196!«X.149a»yadha vi tadri vikadi «X.149b»ya yed eva odu opadi «X.149c»apaka bhodi votavi «X.149d»oharaṇase~e,a e~va sadii ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','162',175,null,'!197!«X.150a»emam eva maṇuśa[na] «X.150b»(*ye) y[a~a,a a~ñe] sadi praṇayo «X.150c»ya ya i vivasadi !207!radi «X.150d»mara!197!ṇase~e,a e~va sadii','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','163',176,null,'!198!«X.151a»sadi eki na diśadi «X.151b»pradu diṭho baho‐jaṇo «X.151c»pradu eki na diśadi «X.151d»sadi diṭha baho‐jaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','164',177,null,'!199!«X.152a»tatra ko viśpaśi maco «X.152b»daharo si di jivid!489!i «X.152c»daha!199!ra vi miyadi «X.152d»nara nari ca ekada ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','165',178,null,'!200!«X.153a»ayireṇa vada~a,a a~ï kayu !490+500+510!«X.153b»paḍhaï vari śaïṣidi !500!«X.153c»tuchu a!200![vaka]da‐viñaṇa «X.153d»niratha ba kaḍiḡara ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','166',179,null,'!201+491!«X.154a»[ya]ṇi~i,i i~(*ma)ṇi avathaṇi «X.154b»alaüṇi ba !511!śarada !491!«X.154c»[śa]gha‐[va]r[ṇa]!201!ṇ[i] śiṣaṇi «X.154d»taṇi diṣpaṇi ka radi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','167',180,null,'!492!«X.155a»yaṇi~i,i i~maṇi pravhaguṇi «X.155b»vikṣitaṇi diśo diśa «X.155c»kavodaka!505!ṇi !203!aṭ́hiṇi «X.155d»taṇi diṣpaṇi ka radi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','168',181,null,'!493!«X.156a»imiṇa pudi‐kaeṇa «X.156b»adureṇa pravhaguṇa «X.156c»nicaśuha‐vijiṇeṇa «X.156d»jara‐dhameṇa sa!506!vaśu «X.156e»nime!203a!dha parama śodhi «X.156f»yoka‐kṣemu aṇutara ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','169',182,null,'!205!«X.157a»imiṇa pudi‐kaeṇa «X.157b»vidvareṇa + + + + !494!«X.157c»[n]icaśuha‐vijiṇeṇa «X.157d»jara‐dhameṇa savaśu «X.157e»nim!205!edha parama śodhi «X.157f»yoka‐kṣemu aṇutara ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','170',183,null,'!206!«X.158a»imiṇa pudi‐kaeṇa «X.158b»viśravadeṇa pudiṇa «X.158c»nicaśuha‐vijiṇeṇa «X.158d»jara‐dha!495![m]eṇa savaśu «X.158e»n[i]medha parama śodhi «X.158f»yoka‐kṣema !206!aṇutara [◦]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','171',184,null,'!208!«X.159a»ayara jiyamaṇeṇa «X.159b»ḍaj̄amaṇeṇa nivrudi «X.159c»nimedha !496!parama śodhi «X.159d»yoka‐kṣemu aṇutara !208![◦]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','172',185,null,'!210!«X.160a»jiyadi hi raya‐radha sucitra «X.160b»adha śarira bi jara uvedi «X.160c»sada du dharma na !497!jara [u]vedi «X.160d»sado hi ṣa sabhi praveraya!210!di ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','173',186,null,'!211!«X.161a»muj[u] pur[a] muju pachadu «X.161b»maj̄adu muju bhavasa parako «X.161c»sarvatra vi[mutamo]ṇa[so] !211+448+498!«X.161d»na puṇu jadijara uvehiṣ[i !211!❉]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','173A',187,null,'!211A!ga 20 4 1','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','174',188,null,'«XI» !212!«XI.162a»arogaparama^2469§01^ labha «XI.162b»saduṭhiparama^2469§02^ dhaṇa «XI.162c»v⟨*i⟩śpaśaparama^2469§03^ mitra «XI.162d»nivaṇa paramo suha','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','175',189,null,'!485!«XI.163a»(*ji)[k]itsaparama^2470§01^ roka !213!«XI.163b»sagharaparama^2470§02^ duha «XI.163c»eda ñatva yadhabh[u]du «XI.163d»nivaṇa paramo suha ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','176',190,null,'!486!«XI.164a» (*ma)trasuhapari!502!caï^2471§01^ «XI.164b»yo [pa]śi vi[v]ulu suha !214!«XI.164c»[ca]yi matrasuha dhiro «XI.164d»sabaśu vi[v]ula suha ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','177',191,null,'!215+487!«XI.165a» (*su)[haï^2472§01^ vada] !503!jivamu «XI.165b»[u]s!215!ueṣu aṇusua «XI.165c»[u]s[u]eṣu maṇuśeṣu «XI.165d»viharamu aṇusua ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','178',192,null,'!216!«XI.166a»suhaï vada jivamu «XI.166b»veraṇeṣu averaṇa !488!«XI.166c»veraṇeṣu ma!216!ṇuśeṣu «XI.166d»viharamu averaṇa ·◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','179',193,null,'!217!«XI.167a»suhaï (*vada)^2474§01^ jivamu «XI.167b»kijaṇeṣu akijaṇa «XI.167c»kijaṇeṣu maṇu(*śe)[ṣ]u^2474§02^ «XI.167d»v[i]haramu akijaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','180',194,null,'!218!«XI.168a»suhaï vada jivamu «XI.168b»yeṣa mu na~a,a a~sti kajaṇi «XI.168c»kijaṇeṣu maṇuśeṣu «XI.168d»viharamu akijaṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','181',195,null,'!219!«XI.169a»na ta driḍha ban̄aṇam aha dhira «XI.169b»ya a⟨*ya⟩sa taruva babaka va «XI.169c»saratacita maṇikuṇaleṣu «XI.169d»putreṣu dareṣu ya ya aveha','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','182',196,null,'!220!«XI.170a»eda driḍha ban̄aṇam aha dhira «XI.170b»ohariṇa śiśila drupamokṣu «XI.170c»eda bi chitvaṇa parivrayadi «XI.170d»aṇavehiṇo kama‐suhu prahaï','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','183',197,null,'!221!«XI.171a»ye raka‐rata aṇuvadadi sodu «XI.171b»saï‐gada (*ma)[kaḍa]o [jala] «XI.171c»eda b[i] chitvaṇa parivrayadi «XI.171d»aṇavehiṇo kama‐suha prahaï','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','184',198,null,'!222!«XI.172a»ahivadaṇa‐śilisa «XI.172b»nica vridhavayariṇo «XI.172c»catvari tasa vardhadi «XI.172d»ayo kirta suha bala ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','185',199,null,'!223+513!«XI.173a»drulavhu puruṣayañu !509!«XI.173b»[na] sa savatra (*ja)!223![yadi] «XI.173c»yatra (*sa)^2480§01^ [ja]yadi viru «XI.173d»ta kulu suhu modadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','186',200,null,'!224+514!«XI.174a»[s]uha śayadi muṇaya «XI.174b»na te thiṇa va(*śaṇ)!515!u[a] «XI.174c»suha śikṣida‐!224!savasa «XI.174d»kici teṣa na vijadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','187',201,null,'!225!«XI.175a»suha darśaṇa aria!516!ṇa «XI.175b»savaso vi sada suho «XI.175c»adaśeṇeṇa !225!balaṇa «XI.175d»nicam eva suhi sia ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','188',202,null,'!517!«XI.176a»[ba]la‐saghada‐cariu «XI.176b»drigham adhvaṇa śoyiṣu «XI.176c»dukhu balehi [sa]!226!vasu «XI.176d»amitrehi va savrasi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','189',203,null,'!518!«XI.176e» (*dhi)[ra^2484§01^ du] suhasavasa «XI.176f»ñadihi va samakamo !518!«XI.177a»dhira hi praña i !227!bhayea paṇido «XI.177b»dhorekaśila vadamada aria','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','190',204,null,'!519!«XI.177c» (*ta)^2485§01^ [ta]diśa sapuruṣa sumedha «XI.177d»bhay[ea] nakṣatrapatha va cadrimu !519!«XI.178a»ra!228!dhearo va camasa «XI.178b»parikica uvahaṇa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','191',205,null,'!229!«XI.178c»ya !520![ya] jahadi kamaṇa «XI.178d»ta ta samajadi suh[a] «XI.178e»sarva ca suhu !229!ichia «XI.178f»sarva kama paricaï ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','192',206,null,'!230!«XI.179a»[pa](*radukhuvadha)ṇ[e]ṇa^2487§01^ «XI.179b»(*yo atva)[ṇa s]u(*hu)^2487§02^ [icha](*di)^2487§03^ !521!«XI.179c»(*ve)rasaṣaga(*sat)[si!230!ṭha]^2487§04^ «XI.179d»so duha na parimucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','193',207,null,'!231!«XI.180a»jaya vera prasahadi «XI.180b»dukhu śayadi parayidu «XI.180c»uvaśadu sohu śayadi «XI.180d»hitva jaya‐parayaa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','194',208,null,'!232!«XI.181a»aṇica va[da] saghara «XI.181b»upada‐vaya‐!504!dharmi!232!ṇo «XI.181c»upajiti niruj̄adi «XI.181d»teṣa uvaśamo suho ❉','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','194A',209,null,'!504!ga (*20)^2490§01^','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','195',210,null,'«XII» !78!«XII.182a»na tavada theru bhodi «XII.182b»ya [a]sa [pali](*da^2491§01^ śi)[r](*o)^2491§02^ !83!«XII.182c»parivako vayu tasa «XII.182d»mohaji(*ṇo) !233![d]i (*vu)cadi [◦]','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','196',211,null,'!79!«XII.183a»yo du puñe ca pave ca «XII.183b»duhayasa na lipadi !84!«XII.183c»tam ahu thera bro!93!mi «XII.183d»yasa na sadi igida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','197',212,null,'!80!«XII.184a»ki ja vi daharu bhodi «XII.184b»kaḍa‐keśu śuśu yui !85!«XII.184c»vikada‐chano ca !92!kameṣu «XII.184d»bramayari pradisvadu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','198',213,null,'!81!«XII.185a»so vada‐doṣu medhavi «XII.185b»praña‐śila‐sama!91!hidu «XII.185c».u + + + visañuto «XII.185d»thero di pravucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','199',214,null,'«XII.186a» (*ṇa va)!82![ka]raṇamatreṇa^2495§01^ «XII.186b»varṇapu!61!ṣkalarṇaï va !90!«XII.186c»sadaruvu naru bhodi ◦ «XII.186d»iṣui matsari śaḍhu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','200',215,null,'«XII.187a» + + + + + + + + «XII.187b» + + + + + + + + «XII.187c» + + + + + + + + !89!«XII.187d»[sada]‐ruv[u] di vucadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','201',216,null,'«XII.188a» + + + + + !77!ṣamaṇo «XII.188b»avradu alia bhaṇi «XII.188c»icha‐loha‐sa!88!mavarṇo «XII.188d»ṣamaṇo ki bhaviṣadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','202',217,null,'«XII.189a» + + + + !76!va pavaṇi «XII.189b»ta viñu śramaṇa vidu «XII.189c»śamadhare va pa(*va)!87!ṇi «XII.189d»śramaṇo di pravucadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','203',218,null,'!236!«XII.190a»baho bi ida sahida bhaṣa!75!maṇa «XII.190b»na takaru bhodi naru pramatu «XII.190c»govo va gaü gaṇa!86!ü pareṣa «XII.190d»na bhakava ṣamañathasa bhodi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','204',219,null,'!237!«XII.191a»apa bi ida sahida bhaṣamaṇa !237+74!«XII.191b»dhamasa bhod[i] !237!aṇudha[ma]cari «XII.191c»aṇuvad[i]aṇu idha va horo va «XII.191d»so bhakava ṣamañathasa bhodi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','205',220,null,'!238!«XII.192a»anikaṣayu kaṣaya «XII.192b»yo vastra parihasidi «XII.192c»avedu dama‐soraca «XII.192d»na so kaṣaya arahadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','206',221,null,'!239!«XII.193a»yo du vada‐kaṣayu «XII.193b»śileṣu susamahidu «XII.193c»uvedu dama‐soraca «XII.193d»so du kaṣaya arahadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','207',222,null,'!240!«XII.194a»y⟨*o d⟩u metra bhavayadi «XII.194b»apramaṇa nirovadhi «XII.194c»taṇu sañoyaṇo bhodi «XII.194d»paśadu vadhi‐sakṣaya ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','208',223,null,'!241!«XII.195a»eka bi ya praṇa aduṭha‐citu «XII.195b»metrayadi kuśala teṇa bhodi «XII.195c»sarve ya praṇa maṇasa~a,a a~ṇuabadi «XII.195d»prahona aria prakarodi puñu','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','209',224,null,'!242!«XII.196a»yo sata‐ṣaṇa pradhavi vicirya «XII.196b»rayerṣayu yayamaṇa~a,a a~ṇaparyaya «XII.196c»aśpa‐veka puruṣa‐veka same‐«XII.196d»paśa vaya‐veka niragaḍa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','210',225,null,'!243!«XII.197a»metrasa citasa subhavidasa «XII.197b»diṭhe va dharmi uvavaja va muṇo «XII.197c»kala ami na~a,a a~ṇubhavadi sarvaśo «XII.197d»cadri pravha tara‐gaṇa va sarvi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','211',226,null,'!244!«XII.198a»yo na hadi na ghadhedi «XII.198b»na jeṇadi na yavaï «XII.198c»mitri~i,i a~sa sarva‐bhudeṣu «XII.198d»vera tasa na keṇa yi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','212',227,null,'!245!«XII.199a»yo du metreṇa citeṇa «XII.199b»sarva‐bhuda aṇuabadi «XII.199c»udhva tiya adho ya~a,a a~vi «XII.199d»yo loku aṇuabadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','213',228,null,'!246!«XII.200a»aparitam ida cita «XII.200b»apramaṇa subhavida «XII.200c»ya pramaṇa‐kida karmu «XII.200d»na ta tatra viśiśadi ❉','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','213A',229,null,'!246A!ga 10 4 4 1','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','214',230,null,'«XIII» !247!«XIII.201a»maṇopuvagama dhama «XIII.201b»maṇośeṭha maṇojava «XIII.201c»maṇasa hi praduṭheṇa «XIII.201d»bhaṣadi ⟨*va⟩ karodi va «XIII.201e»tado ṇa duhu amedi «XIII.201f»cako va vahaṇe pathi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','215',231,null,'!248!«XIII.202a»maṇo‐puvagama dhama «XIII.202b»maṇo‐śeṭha maṇo‐java «XIII.202c»maṇasa hi prasaneṇa «XIII.202d»bhaṣadi va karodi va «XIII.202e»tado ṇa suhu amedi «XIII.202f»chaya va aṇukamiṇi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','216',232,null,'!249!«XIII.203a»pava ma kada di śoyadi «XIII.203b»bhuyu śoyadi drugadi gado «XIII.203c»so śoyadi so vihañadi «XIII.203d»diṣpa kamu kiliṭha atvaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','217',233,null,'!250!«XIII.204a»puña ma kida di nanadi «XIII.204b»bhuyu nanadi sugadi gado «XIII.204c»so nanadi so pramodadi «XIII.204d»diṣpa kamu viśudhu atvaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','218',234,null,'!251!«XIII.205a»idha śoyadi preca śoyadi «XIII.205b»pava‐kamu duhayatra śoyadi «XIII.205c»so śoyadi so vihañadi «XIII.205d»diṣpa kamu kiliṭha atvaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','219',235,null,'!252!«XIII.206a»idha nanadi preca nanadi «XIII.206b»puña‐kamo duhayatra nanadi «XIII.206c»so nanadi so pramodadi «XIII.206d»diṣpa kamu viśudhu atvaṇo ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','220',236,null,'!253!«XIII.207a»pava ja puruṣu kuya «XIII.207b»na ṇa kuya puṇapuṇu «XIII.207c»na tasa chana kuvia «XIII.207d»dukhu pavasa ayayu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','221',237,null,'!254!«XIII.208a»puña ca puruṣu kuya «XIII.208b»kuya yo ṇa puṇapuṇu «XIII.208c»atha~a,a a~tha chana korvia «XIII.208d»sukhu puñasa ucayu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','222',238,null,'!255!«XIII.209a»na apu mañea pavasa «XIII.209b»na me ta akamiṣadi «XIII.209c»uda‐binu‐nivadeṇa «XIII.209d»uda‐kubho va puyadi «XIII.209e»puyadi balu paveṇa «XIII.209f»stuka‐stoka bi ayaro ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','223',239,null,'!256!«XIII.210a»na apu mañea puñasa «XIII.210b»na me ta akamiṣadi «XIII.210c»uda‐binu‐nivadeṇa «XIII.210d»uda‐kubho va puyadi «XIII.210e»puyadi dhiru puñeṇa «XIII.210f»stoka‐stuka bi ayaru ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','224',240,null,'!257!«XIII.211a»kaya‐kamu vayi‐kamu «XIII.211b»maṇo‐kama ca pavaka «XIII.211c»asevaïti drupañu «XIII.211d»niraeṣu vavajadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','225',241,null,'!258!«XIII.212a»kaya‐kamu vayi‐kamu «XIII.212b»maṇo‐kama ca bhadaka «XIII.212c»asevaïti sapraño «XIII.212d»sukadiṣu vavajadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','226',242,null,'!259!«XIII.213a»asari saravadiṇo «XIII.213b»sari asaradaśiṇo «XIII.213c»te sara na~a,a a~dhikachadi «XIII.213d»michasaggapagoyara ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','227',243,null,'!260!«XIII.214a»sara du saradu ñatva «XIII.214b»asara ji asarado «XIII.214c»te sara adhikachadi «XIII.214d»same‐sagapa‐goyara ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','228',244,null,'!261!«XIII.215a»śaru yadha drugahido «XIII.215b»hasta aṇuvikatadi «XIII.215c»ṣamaña droparamuṭho «XIII.215d»niraya uvakaḍhadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','229',245,null,'!262!«XIII.216a»śaru yadha sugahido «XIII.216b»hasta na~a,a a~ṇuvikatadi «XIII.216c»ṣamaña suparamuṭho «XIII.216d»sukadiṣu vakaḍhadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','230',246,null,'!263!«XIII.217a»śuhaṇupaśi viharadu «XIII.217b»idrieṣu asavudu «XIII.217c»bhoyaṇasa amatraño «XIII.217d»kusidu hiṇa‐viryava «XIII.217e»ta gu prasahadi raku «XIII.217f»vadu rakhkṣa ba drubala ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','231',247,null,'!264!«XIII.218a»aśuhaṇupaśi viharadu «XIII.218b»idrieṣu sisavudu «XIII.218c»bhoyaṇasa ya matraño «XIII.218d»ṣadhu aradha‐viryava «XIII.218e»ta gu na prasahadi raku «XIII.218f»vadu śela va parvada ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','232',248,null,'!265!«XIII.219a»yadha akara druchana «XIII.219b»vuṭhi samadibhinadi «XIII.219c»emu arakṣida cata «XIII.219d»raku samadibhinadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','233',249,null,'!266!«XIII.220a»yadha akara suchana «XIII.220b»vuṭhi na samadibhinadi «XIII.220c»emu surakṣida cita «XIII.220d»raku na samadibhinadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','234',250,null,'!267!«XIII.221a»sujivu ahirieṇa «XIII.221b»kaya‐śuriṇa dhakṣiṇa «XIII.221c»prakhaṇiṇo prakabhiṇa «XIII.221d»sagiliṭheṇa jaduṇa ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','235',251,null,'!268!«XIII.222a»hirimada du drujivu «XIII.222b»nica śuyi‐gameṣiṇo «XIII.222c»aliṇeṇa aprakabhiṇa «XIII.222d»śudhayiveṇa jaduṇa ❉','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','236',252,null,'!269!«XIII.223a»brama i bhikhu tasiṇa i pavu «XIII.223b»araha mageṇa ya apramadu «XIII.223c»cita ji balu adha va jara i «XIII.223d»suheṇa theru yamaeṇa treḍaśa','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','236A',253,null,'!269+!ga 20 1 1','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','237',254,null,'«XIV» !270!«XIV.224a»dhamapridi suhu śayadi «XIV.224b»viprasaneṇa cedaso «XIV.224c»ariapravedidi dharmi «XIV.224d»sada ramadi paṇidu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','238',255,null,'!271!«XIV.225a»yatha vi rada gammiro «XIV.225b»viprasano aṇavilo «XIV.225c»emu dhamu ṣuṇitvaṇa «XIV.225d»viprasidadi paṇida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','239',256,null,'!272!«XIV.226a»sarvatra ya sa‐puruṣa vivedi «XIV.226b»na kama‐kama lavayadi dhira «XIV.226c»suheṇa phuṭha adha va duheṇa «XIV.226d»na uca~a,a‐a~vaya paṇida daśayadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','240',257,null,'!273!«XIV.227a»atmaṇam eva pradhamu «XIV.227b»pradiruvi niveśaï «XIV.227c»tada~a,a a~ñi aṇuśaśea «XIV.227d»na kiliśea paṇidu ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','241',258,null,'!274!«XIV.228a»aṣadhehi kradavehi «XIV.228b»phiśuṇehi vivhuda‐nanahi «XIV.228c»sakha na karia paṇido «XIV.228d»sag̱adi kavuruṣehi paviya','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','242',259,null,'!275!«XIV.229a»ṣadhehi ya peśalehi ya «XIV.229b»[śilava]da yi bahoṣudehi ya «XIV.229c»sakha kuvia paṇido «XIV.229d»sagadi sapuruṣehi bhadia ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','243',260,null,'!276!«XIV.230a»anuśaśadi ovadadi «XIV.230b»asabhe hi navaraï «XIV.230c»paṇidaṇa prio bhodi «XIV.230d»balaṇa bhodi aprio ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','244',261,null,'!277!«XIV.231a»nisedara pravatara «XIV.231b»yo paśi vaji‐daśaṇa «XIV.231c»nigiśa‐vadi medhavi «XIV.231d»tadiśa paṇada bhayi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','245',262,null,'!278!«XIV.231e»tadi bhayamaṇaṇa «XIV.231f»ṣeho bhodi na paviu !278!«XIV.232a»kaeṇa kuśala kitva «XIV.232b»vayaï kuśala baho','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','246',263,null,'!279!«XIV.232c»maṇeṇa kuśala kitva «XIV.232d»apramaṇa nirovadhi «XIV.232e»avavaji suhi loke «XIV.232f»paṇidu uvavajadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','247',264,null,'!280!«XIV.233a»yavajiva bi ya balu «XIV.233b»paṇida payuvasadi «XIV.233c»ne~e,a e~va dhamu viaṇadi «XIV.233d»praña hi~i,i a~sa na vijadi','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','248',265,null,'!281!«XIV.234a»muhuta bi ya viñu «XIV.234b»paṇada payuvasadi «XIV.234c»so du dhamu viaṇadi «XIV.234d»praña hi~i,i a~sa tadovia','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','249',266,null,'!282!«XIV.235a»na abhaṣamaṇa jaṇadi «XIV.235b»miṣa balehi paṇida «XIV.235c»bhaṣamaṇa du jaṇadi «XIV.235d»deśada amuda duduhi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','250',267,null,'!283!«XIV.236a»deśehi jodaï dhamu «XIV.236b»uṣivi iṣiṇa dhay[u] «XIV.236c»subhaṣida‐dhaya hi iṣayu «XIV.236d»dhamo ha iṣiṇa dhayu','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','251',268,null,'!284!«XIV.237a»poraṇam ida adura «XIV.237b»na ida ajetaṇa iva «XIV.237c»ninadi tuṣ̄i‐bhaveṇa «XIV.237d»ninadi baho‐bhaṇiṇo «XIV.237e»mana‐bhaṇi vi ninadi «XIV.237f»na~a,a a~sti loki aninia','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','252',269,null,'!285!«XIV.238a»praśaj̄a śaśvada na~a,a a~sti «XIV.238b»nina nica na vijadi «XIV.238c»tasa nina‐praśaj̄aṣu «XIV.238d»na sammijadi paṇida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','253',270,null,'!286!«XIV.239a»śelu yadha eka‐khaṇo «XIV.239b»vadeṇa na sabhijadi «XIV.239c»emu nina‐praśaj̄aṣu «XIV.239d»na sammijadi paṇida ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','254',271,null,'!287!«XIV.240a»ekada ninido prodhu «XIV.240b»ekada ji praśaj̄idu «XIV.240c»na i aha na i bheṣida «XIV.240d»na yi edarahi vijadi ◦','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','255',272,null,'!288!«XIV.241a»yo nu ho viña praśaj̄adi «XIV.241b»aṇuija śuhaśuhu «XIV.241c»achidra‐vuti medhavi «XIV.241d»praña‐śila‐samahida','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','256',273,null,'!289!«XIV.242a»nikhu jabodaṇase~e,a i~va «XIV.242b»ko ṇa ninidu arahadi «XIV.242c»deva mi ṇa praśajadi «XIV.242d»bramoṇa vi praśajidu ❉','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','256A',274,null,'!289A!ga 10 4 4 1','recto',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','257',275,null,'«XV» !370!«XV.243a»ayoa va na jaṇadi «XV.243b»michaviṇayadu iva «XV.243c»avijaï va samu!367![śa] «XV.243d»budhaṇa va adaśaṇi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','258',276,null,'!369+300!«XV.244a»aṇuyoa du jaṇadi «XV.244b»same‐viṇiad[u] iva !368+300!«XV.244c»yoṇiśa viyiṇi dhama «XV.244d»sevamaṇa bahoṣuda ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','259',277,null,'!301!«XV.245a»duhaeṇa i artheṇa «XV.245b»sevidavi bahoṣuda «XV.245c»suhiṇa uyidatheṇa «XV.245d»nica kicha‐kadeṇa ca ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','260',278,null,'!302!«XV.246a»prahodi duhiṇo dokhu «XV.246b»avaṇedu bahoṣuda «XV.246c»kathaï paḍiruvaï «XV.246d»bhaṣamaṇa puṇa‐puṇu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','261',279,null,'!303!«XV.247a»suhidasa vi pramoju «XV.247b»jaṇayadi bahoṣuda «XV.247c»deśada amuda dhamu «XV.247d»dukha‐vaśama‐kamia ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','262',280,null,'!304!«XV.248a»śi⟨*la⟩mada maha‐praña «XV.248b»dharmakama‐bahoṣuda «XV.248c»bhayidavi saprañeṇa «XV.248d»bhuyasa bhorim ichadu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','263',281,null,'!305!«XV.249a»bahuṣuda dhama‐dhara «XV.249b»sapraña budha‐ṣavaka «XV.249c»śrudi‐viñati akakṣu «XV.249d»ta bhayea tadhavidha ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','264',282,null,'!306!«XV.250a»sabhir ava samasea «XV.250b»sabhi kuvia sadhavu «XV.250c»sada sadharmu añaï «XV.250d»sarva‐dukha pramucadi [◦]','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','265',283,null,'!307!«XV.251a»yadha akara suchana «XV.251b»praviśi tamasa~a,a o~rṇudu «XV.251c»vijamaṇa vi ruveṣu «XV.251d»cakhkṣuma vi na paśadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','266',284,null,'!308!«XV.252a»emam eva idhe~e,a e~kacu «XV.252b»jadima vi ca yo naro «XV.252c»aṣutva na viaṇadi «XV.252d»dhama {dhama} kalaṇa‐pavaka','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','267',285,null,'!309!«XV.253a»pradiveṇa nu ruvani «XV.253b»yadha paśadi cakhkṣuma «XV.253c»emu ṣutva viaṇadi «XV.253d»dhama kalaṇa‐pavaka','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','268',286,null,'!310!«XV.254a»suṣuda ṣuda‐vaḍhaṇa «XV.254b»ṣuda prañaya vaḍhadi «XV.254c»praña artha viśodhedi «XV.254d»artha śudho suhavaü','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','269',287,null,'!311!«XV.255a»so artha‐ladhu medhavi «XV.255b»praña‐śila‐samahidu «XV.255c»ṣuda‐dhamu suyi‐drakṣu «XV.255d»panodi paramu suhu','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','270',288,null,'!312!«XV.256a»nica hi aviaṇada «XV.256b»caradi amara viva «XV.256c»sadhama du viaṇada «XV.256d»adurase~e,a i~va śadvari ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','271',289,null,'!313!«XV.257a»kim añatra aṣamaṇadha «XV.257b»dhamase~e,a e~va adaśaṇe «XV.257c»eva apasa jividi «XV.257d»vera kuyadi keṇa i','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','272',290,null,'!314!«XV.258a»ye śaśaṇa arahadu «XV.258b»ariaṇa dhama‐jiviṇo «XV.258c»paḍikośadi drumedho «XV.258d»diṭhi niṣaï pavia «XV.258e»phalaṇi kaḍakase~e,a i~va «XV.258f»atva‐kañaï phaladi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','272A',291,null,'!314A!ga 10 4 1 1','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','273',292,null,'«XVI» !315!«XVI.259a»ekasaṇa ekasaya «XVI.259b»ekaïyaï savudu «XVI.259c»eku ramahi atvaṇa «XVI.259d»arañi ekao vasa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','274',293,null,'!316!«XVI.260a»yasa ṣadha i praña ya «XVI.260b»viya otrapia hiri «XVI.260c»s[o] ho maha‐dhaṇa bhodi «XVI.260d»moham aña baho dhaṇa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','275',294,null,'!317!«XVI.261a»na sadi putra traṇaï «XVI.261b»na bhoa na vi banava «XVI.261c»adeṇa abhiduṇasa «XVI.261d»na~a,a a~sti ñadihi traṇadha ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','276',295,null,'!318!«XVI.262a»drupravaï druabhiramu «XVI.262b»druaj̄avasaṇa ghara «XVI.262c»dukhu samaṇa‐savaso «XVI.262d»dukhaṇuvadida bhava ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','277',296,null,'!319!«XVI.263a»kiche maṇuśa‐pradilabhu «XVI.263b»kicha macaṇa jivida «XVI.263c»kiche sadhama‐śramaṇa «XVI.263d»kiche budhaṇa upaya ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','278',297,null,'!320!«XVI.264a»sukaraṇi asadhuṇi «XVI.264b»atvaṇo ahidaṇa yi «XVI.264c»ya du hida ji sadhu ji «XVI.264d»ta gu parama‐drukara ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','279',298,null,'!321!«XVI.265a»apaṇa~a,o a~tha paratheṇa «XVI.265b»na kuda yiṇo havaï «XVI.265c»atvatha paramu ñatva «XVI.265d»svakathaparamu sia','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','280',299,null,'!322!«XVI.266a»ayoi yuji atvaṇa «XVI.266b»yoase~e,a e~va ayujadu «XVI.266c»atha hitva pria‐gaha «XVI.266d»svihadi arthaṇupaśiṇo','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','281',300,null,'!323!«XVI.267a»nedi hi mahavira «XVI.267b»sadhameṇa tadhakada «XVI.267c»dhameṇa neamaṇaṇa «XVI.267d»ka y asua viaṇadu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','282',301,null,'!324!«XVI.268a»alia bhaṣamaṇasa «XVI.268b»avakamadi devada «XVI.268c»muha ji pudia bhodi «XVI.268d»saga‐ṭ́haṇa i bhatsadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','283',302,null,'!325!«XVI.269a»abhuda‐vadi naraka uvedi «XVI.269b»yo ya~a,a a~vi^2583§01^ kitva na karodi āha «XVI.269c»uvhaï ami preca sama bhavadi «XVI.269d»nihiṇa‐kama maṇuya paratri','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','284',303,null,'!326!«XVI.270a»catvari ṭ́haṇaṇi naro pramatu «XVI.270b»avajadi para‐darovasevi «XVI.270c»amuña‐labha aniama‐saya «XVI.270d»nina tridia niraya caüṭ́ha','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','285',304,null,'!327!«XVI.271a»na pareṣa vilomaṇi «XVI.271b»na pareṣa kidakida «XVI.271c»atvaṇo i samikṣea «XVI.271d»samaṇi viṣamaṇi ca ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','286',305,null,'!328!«XVI.272a»supaśi vaja añeṣa «XVI.272b»atvaṇo maṇa drudaśa «XVI.272c»pareṣa eṣu vajaṇa «XVI.272d»upuṇadi yatha busu','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','287',306,null,'!329!«XVI.272e»atvaṇo maṇa chadedi «XVI.272f»kali va kidava śaḍha ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','288',307,null,'!330!«XVI.273a»alajidavi lajadi «XVI.273b»lajidavi na lajadi «XVI.273c»abhayi bhaya‐darśavi «XVI.273d»bhayi abhaya‐darśaṇo','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','289',308,null,'!331!«XVI.273e»michadiṭhisamadaṇa «XVI.273f»satva gachadi drugadi ❉','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','289A',309,null,'!331A!ga 10 4 1','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','290',310,null,'«XVII» !332!«XVII.274a»kothu jahi viprayahea maṇa «XVII.274b»sañoyaṇa savi adikamea «XVII.274c»ta namaruvasa aṣajamaṇa «XVII.274d»akijaṇa na~a,a a~ṇuvadadi dukhu','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','291',311,null,'!333!«XVII.275a»yo du upadida kodhu «XVII.275b»radha bhada va dharaï «XVII.275c»tam aho saradi bromi «XVII.275d»rasviggaha idara jaṇa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','292',312,null,'!334!«XVII.276a»ya gu na prasahadi kudhu «XVII.276b»no yasa kuradi vaśa «XVII.276c»so hu rakṣadi atvaṇa «XVII.276d»nine~e,a e~va na~a,a a~vaciṭhadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','293',313,null,'!335!«XVII.277a»akodhaṇasa vi⟨*yi⟩di «XVII.277b»ṭ́hidadhamasa rayiṇo «XVII.277c»suhu puruṣu asea «XVII.277d»śidachade va sva ghari ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','294',314,null,'!336!«XVII.278a»uṣavha viva go‐sag̱i «XVII.278b»śilamadu akodhaṇo «XVII.278c»baho ṇa payuvasadi «XVII.278d»rayaṇa viva dhamia ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','295',315,null,'!337!«XVII.279a»hasti va muyajadaṇa «XVII.279b»śela⟨*ṇa⟩ hemavañ iva «XVII.279c»sakaro va śravadiṇa «XVII.279d»adico tavada r iva ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','296',316,null,'!338!«XVII.279e»⟪khaṇakhaṇi tidikṣea «XVII.279f»kodhu rakṣea atvaṇi⟫','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','297',317,null,'!339!«XVII.280a»jiṇa kodha akotheṇa «XVII.280b»asadhu sadhuṇa jiṇa «XVII.280c»jiṇa kradava daṇeṇa «XVII.280d»saceṇa alia jiṇa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','298',318,null,'!340!«XVII.281a»saca bhaṇi na kuvea «XVII.281b»daya apadu yayida «XVII.281c»edehi trihi ṭ́haṇehi «XVII.281d»gacha devaṇa sadii ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','299',319,null,'!341!«XVII.282a»kudhu atha na jaṇadi «XVII.282b»kudhu dhamu na paśadi «XVII.282c»anu tada tamu bhodi «XVII.282d»ya kodhu sahadi naru ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','300',320,null,'!342!«XVII.283a»ma sa kodhu pramujea «XVII.283b»dukhu kodhasa avaraṇa «XVII.283c»mahoru mañati sadhu «XVII.283d»pacha tavadi kodhaṇo ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','301',321,null,'!343!«XVII.284a»nakara aṭ́hi‐pakara «XVII.284b»matsa‐lohida‐levaṇa «XVII.284c»yatra rako ya doṣo ya «XVII.284d»maṇo makṣo samokadu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','302',322,null,'!344!«XVII.285a»makṣia matsari bhodi «XVII.285b»prahata bhodi gadhavo «XVII.285c»kodhaṇo suaro bhodi «XVII.285d»ma sa kuju kumaraka ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','303',323,null,'!345!«XVII.286a»suradu bhodi bhadrañu «XVII.286b»surado suho modadi «XVII.286c»suradasa aï dhamu «XVII.286d»edha paśadha tadiṇo ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','304',324,null,'!346!«XVII.287a»soracasa phala paśa «XVII.287b»tava kichaṣu jadiṣu «XVII.287c»yatra edadiśo sadu «XVII.287d»surado di na hañadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','305',325,null,'!347!«XVII.288a»ki nu j̄atva suho śayadi «XVII.288b»ki nu j̄atva na śoyadi «XVII.288c»kisa nu eka‐dhamasa «XVII.288d»vadha royeṣi godama','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','306',326,null,'!348!«XVII.289a»kodhu j̄atva suha śayadi «XVII.289b»kodhu j̄atva na śoyadi «XVII.289c»kodhasa viṣa‐mulasa «XVII.289d»masuragasa bramaṇa','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','307',327,null,'!349!«XVII.289e»vadha aria praśaj̄adi «XVII.289f»ta ji j̄atva na śoyadi ❉','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','307A',328,null,'!349A!ga 10 4 1 1','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','308',329,null,'«XVIII» !350!«XVIII.290a»yatha vi ruyida puṣu «XVIII.290b»vaṇama!366!da aganaa «XVIII.290c»emu subhaṣi!350!da vaya «XVIII.290d»apha[la] (*bhodi)^2610§01^ akuvad[u]','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','309',330,null,'!351!«XVIII.291a»yatha vi ruyida puṣu «XVIII.291b»vaṇamada saganaa «XVIII.291c»emu subhaṣida vaya «XVIII.291d»saphala bhodi kuvadu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','310',331,null,'!352!«XVIII.292a»yatha vi bhamaru puṣ!360!pa !352!«XVIII.292b»vaṇa‐gana ah[e]ḍa[ï] «XVIII.292c»paridi rasam adaï «XVIII.292d»emu gami muṇi cara ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','311',332,null,'!353!«XVIII.293a»yada vi puṣpa‐raśi!359!sa «XVIII.293b»kuya mala‐guṇa baho «XVIII.293c»emu jadeṇa maceṇa «XVIII.293d»kata[v]i + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','312',333,null,'!354!«XVIII.294a»p[u]ṣa[ṇ]i [ye]!358!va payiṇadu «XVIII.294b»vasitamaṇasa nara «XVIII.294c»sutu gamu mahoho va «XVIII.294d»a[da]!371!(*go)^2614§01^ + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','313',334,null,'«XVIII.295a» + + + + + !357![pra]divada vayadi «XVIII.295b»na malia takar[a] canaṇa va «XVIII.295c»sadaṇa gano pradivada vaïdi «XVIII.295d»sa[rva] !373!diśa sapuruṣo padaïdi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','314',335,null,'«XVIII.296a» + + + + + + !365![ya vi] «XVIII.296b» + + + + + + + + «XVIII.296c» + + + !372![ganaja](*da)ṇa «XVIII.296d»śilagano !376!ivu~u,a u~tama [◦]','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','315',336,null,'«XVIII.297a» + + (*sa)!364![baṇa]śilaṇa^2617§01^ «XVIII.297b»apramada!374!vihariṇa «XVIII.297c»samadañavimutaṇa «XVIII.297d»ga!377!di Maro na vinadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','316',337,null,'!363!«XVIII.298a»vaṣia yatha puṣaṇa «XVIII.298b»poraṇaṇi pra!375!mujadi «XVIII.298c»emu raka ji doṣa ji !378!«XVIII.298d»vipramujadha bhikṣavi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','317',338,null,'!362!«XVIII.299a»uchina siṇeha atvaṇo «XVIII.299b»kumudu śaradaka ba p[r]a!379!ṇiṇa «XVIII.299c»śadi‐magam eva brohaï «XVIII.299d»nivaṇa sukadeṇa deśida ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','318',339,null,'!361!«XVIII.300a»pheṇovamu kayam ida viditva «XVIII.300b»mari[y]i(*dhama)^2620§01^ + + + !380![bhu]daï «XVIII.300c»chetvaṇa Marasa p[a]{p[a]}vuṣeaṇa «XVIII.300d»a(*daśaṇa)^2620§02^ + + + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','319',340,null,'«XVIII.301a» + + + + + + + + + + «XVIII.301b»(*Yamaloka ji) !381!ida sadevaka «XVIII.301c»ko dhamapada sudeśida «XVIII.301d»kuśala puṣa viva payeṣidi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','320',341,null,'!382!«XVIII.302a»budhu pradha(*vi^2622§01^ viye)ṣidi^2622§02^ «XVIII.302b»Yamaloka ji ida sadevaka «XVIII.302c»budhu dhamapada sudeśida «XVIII.302d»kuśala puṣa viva payiṣidi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','321',342,null,'!383!«XVIII.303a»yadha sagara‐uḍasa «XVIII.303b»uj̄idasa maha‐pathe «XVIII.303c»padumu tatra jaea «XVIII.303d»suyi‐gan̄a maṇoramu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','322',343,null,'!384!«XVIII.304a»e(*mu) !419!sag[h]asadhama[ü] !384!«XVIII.304b»an̄ahodi prudhijaṇe «XVIII.304c»abhi[r]oyadi prañaï «XVIII.304d»samesabudhaṣavaka ❉','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','322A',344,null,'!384A!ga 10 4 1','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','323',345,null,'«XIX» !385!«XIX.305a»yo s[a]hasa sahasaṇi «XIX.305b»sag̱ami maṇuṣa jiṇi «XIX.305c»eka ji jiṇi atvaṇa «XIX.305d»so ho sagamu utamu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','324',346,null,'!386!«XIX.306a»sahasa bi ya !415!vayaṇa !386!«XIX.306b»aṇatha‐pada‐sahida «XIX.306c»eka vaya‐pada ṣevha «XIX.306d»ya ṣutva uvaśamadi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','325',347,null,'«XIX.307a»(*yo ja) !415a![va]ya‐!387!śada [bha]ṣi «XIX.307b»aṇatha‐pada‐sahida «XIX.307c»e!414!ka vaya‐[pa](*da) !387!ṣ[e]hu «XIX.307d»ya ṣutva uvaśamadi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','326',348,null,'!388!«XIX.308a» (*sa)[ha]sa^2629§01^ bi ya gadhaṇa «XIX.308b»aṇathapadasahida «XIX.308c»eka gadhapada ṣeho «XIX.308d»ya ṣutva uvaśamadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','327',349,null,'!389!«XIX.309a»[yo] ja gadha‐śada bhaṣi «XIX.309b»aṇatha‐pada‐sahida «XIX.309c»eka gadha‐pada ṣebha «XIX.309d»ya ṣutva uvaśamadi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','328',350,null,'!390!«XIX.310a»masamasi sahasiṇa «XIX.310b»yo yaea śadeṇa ca «XIX.310c»ne~e,a e~vi budhi prasadasa «XIX.310d»kala avedi ṣoḍaśa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','329',351,null,'!391!«XIX.311a»[ma]samase sahaseṇa «XIX.311b»yo yaea śadeṇa ca «XIX.311c»ne~e,a e~va (*dhami)^2632§01^ prasa(*da)sa^2632§02^ «XIX.311d»kala avedi ṣoḍaśa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','330',352,null,'!392!«XIX.312a»masamasi sahasiṇa «XIX.312b»yo yaea śadeṇa ca «XIX.312c»ne~e,a e~va sag̱i prasadasa «XIX.312d»kala avedi ṣoḍaśa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','331',353,null,'!393!«XIX.313a»masamasi sahaseṇa «XIX.313b»yo yaea śadeṇa ca «XIX.313c»ne~e,a e~va saghasadhameṣu «XIX.313d»kala avedi ṣoḍaśa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','332',354,null,'!394!«XIX.314a»masamasi sahaseṇa «XIX.314b»yo yaea śadeṇa ca «XIX.314c» (*ne~e,a e~va)^2635§01^ + + + + teṣu «XIX.314d»kala avedi ṣoḍaśa','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','333',355,null,'!395!«XIX.315a»masamase sahaseṇa «XIX.315b»yo yaea śadeṇa ca «XIX.315c»ekapaṇaṇuabisa «XIX.315d»kala na~a,a a~vedi ṣoḍaśa ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','334',356,null,'!396!«XIX.316a»ya ja vaṣa‐śada jivi «XIX.316b»kusidhu hiṇa‐viyava «XIX.316c»muhutu jivida ṣevha «XIX.316d»virya arahado driḍha ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','335',357,null,'!397!«XIX.317a»ya ji vaṣa‐śado jivi «XIX.317b»apaśu udaka‐vaya «XIX.317c»muhutu jivida ṣevha «XIX.317d»paśado udaka‐vaya ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','336',358,null,'!398!«XIX.318a»ya ja vaṣa‐śada jivi «XIX.318b»apaśu dhamu utamu «XIX.318c»mohotu !417!jivida ṣehu «XIX.318d»paśadu dhamu utamu','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','337',359,null,'!399!«XIX.319a»ya ja vaṣa‐śada jadu «XIX.319b»agi pariyara vaṇi «XIX.319c»kṣireṇa sapi‐teleṇa «XIX.319d»diva‐ratra atadrido','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','338',360,null,'!400!«XIX.320a»eka ji bhavidatvaṇa «XIX.320b»muhuta viva puyaï «XIX.320c»sa m eva puyaṇa ṣevha «XIX.320d»ya ji vaṣaśada hodu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','339',361,null,'!401!«XIX.321a»ya k[e] ja yaṭha va hoda va l⟨*o⟩ke «XIX.321b»s[a]vat[sa]ra yayadi puña(*ve)[kṣa] «XIX.321c»sava bi ta na cadu‐bhaku vedi «XIX.321d»ahivadaṇa ujukadeṣu ṣiho','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','339A',362,null,'!401A!ga 10 4 1 1 1','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','340',363,null,'«XX» !403!«XX.322a»śilamadu suyidrakṣo «XX.322b»dhamaṭ́ho sadhujivaṇo «XX.322c»atvaṇo karako sadu «XX.322d»ta jaṇo kuradi priu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','341',364,null,'!404!«XX.323a»ṣadhu śileṇa sabaṇo «XX.323b»yaśa‐bhoa‐samapidu «XX.323c»yeṇa yeṇe~e,a e~va vayadi «XX.323d»teṇa teṇe~e,a e~va puyidu ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','342',365,null,'!405!«XX.324a»yo na~a,a a~tvahedu na parasa hedu «XX.324b»pavaṇi kamaṇi samayarea «XX.324c»na [i]chi a(*dhameṇa^2646§01^ sa)!413!midhi a[t]vaṇo !405!«XX.324d»so śi[lava] paṇidu dhamio sia','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','343',366,null,'!406!«XX.325a»sañadu sukadi yadi «XX.325b»drugadi yadi asañadu «XX.325c»ma sa viśpaśa avaja «XX.325d»ida vidva samu cari ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','344',367,null,'!407!«XX.326a»savudu pradimukhasa «XX.326b»idrieṣu ya pajaṣu «XX.326c»pramuṇi aṇupruviṇa «XX.326d»sava‐sañoyaṇa‐kṣaya ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','345',368,null,'!408!«XX.327a»śudhasa hi sada phagu «XX.327b»śudhasa posadhu sada «XX.327c»śudhasa s[u]yi(*kamasa)^2649§01^ «XX.327d»tasa samajadi vada ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','346',369,null,'!409!«XX.328a»dhamu cari sucarida «XX.328b» (*na ta)^2650§01^ drucarida cari «XX.328c»dhamayari suh[a] śedi «XX.328d»asvi loki parasa yi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','347',370,null,'!410!«XX.329a»aho nako va sagami «XX.329b»cavadhivadida śara «XX.329c»adivaka ti[d]i[kṣa]mi «XX.329d»druśilo hi baho‐jaṇo','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','348',371,null,'!411!«XX.330a»yasa acada‐druśilia «XX.330b»malua va vilada vaṇi «XX.330c»kuya so tadha (*a)tvaṇa «XX.330d»yadha ṇa viṣamu ichadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','349',372,null,'«XX.331a» (*ṣehu^2653§01^ a)!412!yokuḍu bhuta «XX.331b»tata agiśi!526!hovam[o] «XX.331c» + + + + + (*bhu)!412![je]a «XX.331d»raṭhapiṇa asañadu','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','349A',373,null,'!500r!ga 10','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','350',374,null,'«XXI» !416!«XXI.332a»ida ja mi keca ida ji karia «XXI.332b»ida kari + + + + + + + «XXI.332c» + + + + + [pa]riphanamaṇa «XXI.332d»abhimadadi mucu[jara] saśoa','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','351',375,null,'!418!«XXI.333a»idha vaṣa kariṣamu «XXI.333b»idha h[e]madagi(*ṣiṣu)^2656§01^ «XXI.333c» + + + + + + + + «XXI.333d» + + + + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','352',376,null,'!420!«XXI.334a»ta putrapaśusamadha «XXI.334b» + + + + + + + + «XXI.334c»sutu ga(*mu mahoho va)^2657§01^ «XXI.334d» + + + + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','353',377,null,'!421!«XXI.335a»puve i kica paḍijaga[rea] !525!«XXI.335b»no yo !421!kici kica‐kale vadea «XXI.335c»ta tadiśa paḍikama kica‐kari «XXI.335d»no i kica kici‐ali vadea','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','354',378,null,'!422!«XXI.336a»ya puvi karaṇiaṇi «XXI.336b»pacha ṣa katu ichadi «XXI.336c»athadu batsadi balu «XXI.336d»suhadu parihayadi','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','355',379,null,'!423!«XXI.337a»akida kuki!523!da !423!ṣehu «XXI.337b»pacha tavadi drukida «XXI.337c»kida nu sukida ṣeh[o] «XXI.337d»ya kitva na~a,a a~ṇutapa[di]','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','356',380,null,'!424!«XXI.338a»ya !524!g[u] k[u]ya ta vadia «XXI.338b» + + + + + + + + !424!a «XXI.338c»[akaroda bhaṣama](*ṇa) «XXI.338d» + + + + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','357',381,null,'!467+425!«XXI.339a»ya kica ta a(*vavidha)^2662§01^ «XXI.339b» + + + + + !522![ki]yadi «XXI.339c»unaḍaṇa prama(*taṇa) «XXI.339d» + + + + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','358',382,null,'!426!«XXI.339e»asava teṣa vaḍhadi «XXI.339f»ara te asava‐kṣa(*ya)','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','359',383,null,'!427!«XXI.340a»yeṣa du susamaradha «XXI.340b»nica kayakada sva[d]i «XXI.340c» + + + + + + + + «XXI.340d» + + + + + + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','360',384,null,'!428!«XXI.340e»sadaṇa sabrayaṇaṇa «XXI.340f»taṣa kṣa[yad]i + + +','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',385,null,'«XXII» . . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','361',386,null,'«XXII.341a» + + + + + + + + «XXII.341b» + + + + + + + + «XXII.341c» + + + + + + + + !450!«XXII.341d»atvadada tada vara ◦◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',387,null,'. . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','362',388,null,'«XXII.342a» + + + + + + + + «XXII.342b» + + + + + + + + «XXII.342c» + + + + + + + + «XXII.342d» + + (*dade)!456!ṣu gachadi ◦','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',389,null,'. . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',390,null,'«XXIII» . . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',391,null,'«XXIV» . . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',392,null,'«XXV» . . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','L',393,null,'«XXVI» . . .','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','362',394,null,'«M» !355!«M.343a»adikradaya ratri «M.343b»devada uvasagrami «M.343c»vaditva muninu pada «M.343d»imi praśaña pradiprocha','verso',null,null,'Khotan Dharmapada Fragment A'),
createParserConfig(12,'{12}','{1}','CKM0077',null,null,'DhpK','364',395,null,'!356!«M.344a»ki‐śila ke‐samacara «M.344b»ke‐guna kena karmaṇa «M.344c»keh[i] darmehi sabana «M.344d»ke jaṇa sparga‐g̱amiyu [◦]','verso',null,null,'Khotan Dharmapada Fragment A'),
);
*/
/*
$parser = new Parser($parserConfigs);
$parser->parse();
//$parser->saveParseResults();
echo "<h2> Input Strings </h2>";
foreach ($parser->getConfigs() as $lnCfg) {
  echo $lnCfg["transliteration"]."<br>";
}

echo "<h2> Errors </h2>";
foreach ($parser->getErrors() as $error) {
  echo "<span style=\"color:red;\">error -   $error </span><br>";
}
echo "<h2> New baseline character strings</h2>";
foreach ($parser->getBaselines() as $baseline) {
  echo $baseline->getTranscription()."<br>";
}
*/
/*
echo "<h2> Graphemes </h2>";
foreach ($parser->getGraphemes() as $grapheme) {
  echo $grapheme->getGrapheme()."     ".$grapheme->getSortCode()."     ".$grapheme->getType()."     ".mb_strstr($grapheme->getScratchProperty("nonce"),"#",true).
        (($footnote = $grapheme->getScratchProperty("footnote")) ? " footnote id $footnote ":"").
        (($subfrag = $grapheme->getScratchProperty("subfragment")) ? " subfragment id $subfrag ":"").
        (($tcm = $grapheme->getTextCriticalMark()) ? " TCM state is $tcm ":"")."<br>";
}
echo "<h2> Segments </h2>";
foreach ($parser->getSegments() as $segment) {
  echo "bln - ".$segment->getBaselineIDs(true)."   Pos - ".$segment->getStringPos(true)."     ".mb_strstr($segment->getScratchProperty("nonce"),"#",true)."<br>";
}
echo "<h2> SyllableClusters </h2>";
foreach ($parser->getSyllableClusters() as $syllable) {
  echo "segID - ".$syllable->getSegmentID()."   graIDs - ".$syllable->getGraphemeIDs(true)."     ".mb_strstr($syllable->getScratchProperty("nonce"),"#",true).
          (($tcm = $syllable->getTextCriticalMark()) ? " TCM state is $tcm ":"")."<br>";
}
echo "<h2> Tokens </h2>";
foreach ($parser->getTokens() as $token) {
  echo (($ckn = $token->getScratchProperty("cknLine")) ? "$ckn ":"")."\"".$token->getToken()."\"".
        " - ".$token->getTranscription()." SC -  ".$token->getSortCode()."     ".mb_strstr($token->getScratchProperty("nonce"),"#",true).
       (($heading = $token->getScratchProperty("heading")) ? "  heading = $heading ":"")."<br>";
}
echo "<h2> Compounds </h2>";
foreach ($parser->getCompounds() as $compound) {
  echo (($ckn = $compound->getScratchProperty("cknLine")) ? "$ckn ":"")."\"".$compound->getCompound()."\"".
        " - ".$compound->getTranscription()." SC -  ".$compound->getSortCode()."     ".
       "componentIDs - ".$compound->getComponentIDs(true)."     ".mb_strstr($compound->getScratchProperty("nonce"),"#",true)."<br>";
}
*/
/*
echo "<h2> Sequences Tokens</h2>";
foreach ($parser->getSequences() as $sequence) {
	if ($sequence->getType() == "Text" || $sequence->getType() == "TextDivision")
  echo (($ckn = $sequence->getScratchProperty("cknLine")) ? "$ckn ":"")." {".$sequence->getType()."-".$sequence->getLabel()."} ".
       " entityIDs - ".$sequence->getEntityIDs(true)."     ".mb_strstr($sequence->getScratchProperty("nonce"),"#",true)."<br>";
}
echo "<h2> Sequences Physical</h2>";
foreach ($parser->getSequences() as $sequence) {
	if ($sequence->getType() == "TextPhysical" || $sequence->getType() == "LinePhysical")
  echo (($ckn = $sequence->getScratchProperty("cknLine")) ? "$ckn ":"")." {".$sequence->getType()."-".$sequence->getLabel()."} ".
       " entityIDs - ".$sequence->getEntityIDs(true)."     ".mb_strstr($sequence->getScratchProperty("nonce"),"#",true)."<br>";
}
echo "<h2> Sequences Verses</h2>";
foreach ($parser->getSequences() as $sequence) {
	if ($sequence->getType() == "Stanza" || $sequence->getType() == "Pāda")
  echo (($ckn = $sequence->getScratchProperty("cknLine")) ? "$ckn ":"")." {".$sequence->getType()."-".$sequence->getLabel()."} ".
       " entityIDs - ".$sequence->getEntityIDs(true)."     ".mb_strstr($sequence->getScratchProperty("nonce"),"#",true)."<br>";
}
echo "<h2> Sequences Other</h2>";
foreach ($parser->getSequences() as $sequence) {
	if ($sequence->getType() != "Text" && $sequence->getType() != "TextDivision" &&
			$sequence->getType() != "TextPhysical" && $sequence->getType() != "LinePhysical" &&
			$sequence->getType() != "Stanza" && $sequence->getType() != "Pāda")
  echo (($ckn = $sequence->getScratchProperty("cknLine")) ? "$ckn ":"")." {".$sequence->getType()."-".$sequence->getLabel()."} ".
       " entityIDs - ".$sequence->getEntityIDs(true)."     ".mb_strstr($sequence->getScratchProperty("nonce"),"#",true)."<br>";
}
if (count($parser->getErrors()) === 0 && 
    isset($_REQUEST["saveResults"])) {
  $parser->saveParseResults();
}
*/
/*
echo bin2hex("‐")."<br>";
echo "<br>";
echo bin2hex("‐")."<br>";*/


/******************** migrate footnote annotations from syl to tok *************************/
$dbMgr = new DBManager();
$qStr = "select ano_scratch::json->>'CKN' as ckn, ".
                       "gra_id, tok_id, ano_id, ano_linkfrom_ids, ".
                       "syl.scl_id as scl_id, syl.scl_scratch::json->>'footnote' as scl_fn, ".
                       "gra_scratch::json->>'footnote' as gra_fn, ".
                       "ano_scratch::json->>'fnHash' as ano_fn ".
        "from annotation ".
              "left join (select scl_id, scl_grapheme_ids,scl_scratch ".
                         "from syllablecluster ".
                         "where scl_scratch like '%footnote%' order by scl_id) as syl ".
                  "on concat('scl:',syl.scl_id) = ANY(ano_linkfrom_ids) ".
              "left join grapheme on gra_id = ANY(syl.scl_grapheme_ids) ".
              "left join token on gra_id = ANY(tok_grapheme_ids) ".
        "where gra_scratch like '%footnote%' ".
              "and syl.scl_id is not null ".
              "and tok_id is not null and gra_id is not null ".
        "order by ckn, gra_id";

$dbMgr->query($qStr);
if ($dbMgr->getRowCount()) {
  while($row = $dbMgr->fetchResultRow()) {
    $ckn = $row["ckn"];
    $anoID = $row["ano_id"];
    $sclID = $row["scl_id"];
    $tokID = $row["tok_id"];
    $annotation = new Annotation($anoID);
    if ($annotation->hasError() || $annotation->getID() != $anoID || $annotation->isReadOnly()) {
      echo "*****ERROR***** For ckn $ckn trying to change ano:$anoID linkfrom scl:$sclID to tok:$tokID <br/>";
    }
    $linkFromIDs = $annotation->getLinkFromIDs();
    if (is_array($linkFromIDs) && count($linkFromIDs) == 1 && $linkFromIDs[0] == "scl:".$sclID) {
      //update annotation linkfrom
      $annotation->setLinkFromIDs(array("tok:$tokID"));
      $annotation->save();
      $newLinkFromIDs = $annotation->getLinkFromIDs();
      //update syllableCluster annotation ids
      $syllable = new SyllableCluster($sclID);
      $sclAnnoIDs = $syllable->getAnnotationIDs();
      if (is_array($sclAnnoIDs)) {
        $sclIndex = array_search($anoID,$sclAnnoIDs);
        if ($sclIndex !== false) {
          array_splice($sclAnnoIDs,$sclIndex,1);
          $syllable->setAnnotationIDs($sclAnnoIDs);
        }
      }
      $syllable->save();
      //update token annotation ids
      $token = new Token($tokID);
      $tokAnnoIDs = $token->getAnnotationIDs();
      if (!$tokAnnoIDs || !is_array($tokAnnoIDs)) {
        $tokAnnoIDs = array($anoID);
      } else {
        array_push($tokAnnoIDs,$anoID);
      }
      $token->setAnnotationIDs($tokAnnoIDs);
      $token->save();
      echo "For ckn $ckn changing ano:$anoID linkfrom ".$linkFromIDs[0]." scl:$sclID to tok:$tokID ".$newLinkFromIDs[0]."<br/>";
    } else {
      echo "*****ERROR***** Invalid Linkfrom - For ckn $ckn trying to change ano:$anoID linkfrom scl:$sclID to tok:$tokID <br/>";
    }
  }
}
return;/*
    if ($ids) {
      print "$tableName has ".$ids." marked for delete <br>";
      $ids = explode(',',substr($ids,1,-1));
      foreach ($ids as $id) {
        $qStr = "select * from $tableName where ".$prefix."_id = $id";
        $dbMgr->query($qStr);
        $row = $dbMgr->fetchResultRow(0,false,PGSQL_ASSOC);
        $qStr = "delete from $tableName where ".$prefix."_id = $id";
        $dbMgr->query($qStr);
        $errStr = $dbMgr->getError();
        if ($errStr) {
          print "error encountered trying to delete $prefix:$id from $tableName - $errStr <br/>";
        } else {
          print "deleted $prefix:$id from $tableName - $errStr <br/>";
          if (array_key_exists('modified',$row)) {
            unset($row['modified']);
          }
          $values = join(',',array_values($row));
          $values = preg_replace("/\{/","'{",$values);
          $values = preg_replace("/\}/","}'",$values);
          $values = preg_replace("/\,\,/",",NULL,",$values);
          $restoreStr = "INSERT INTO $tableName (".
                        join(',',array_keys($row)).") <br/>VALUES (".
                        $values.")<br/>";
          print $restoreStr;
        }
      }
    }
  } else if ($dbMgr->getError()) {
    print $dbMgr->getError()."<br/>";
  }
}
*/
?>
