<?php
  ini_set("zlib.output_compression_level", 5);
  ob_start();

  require_once ('userAccess.php');//get access control utilities
  require_once ('utils.php');//get utilities
  require_once ('DBManager.php');//get database interface

  header('Content-type: text/javascript');
  header('Cache-Control: no-transform,private,max-age=300,s-maxage=900');
  $dbMgr = new DBManager();
  if ($dbMgr->getError()) {
    exit("Error: ".$dbMgr->getError());
  }
  //term lookup table
  $termInfo = array();
  $termIDToEnLabel = array();
  $termIDToCode = array();
  $termIDToTerm = array();
  $term_parentLabelToID = array();
  $termIDToParentID = array();
  $enumTermIDs = array();
  $enumTypeIDs = array();
  $fkFieldTermIDs = array();//
  $automationTermIDs = array();//
  $uiAssistTermIDs = array();//
  $enumLables = array('SystemList','ContentList','EntityList','List-Single','List-Multiple','List-MultipleOrdered');
  //Set the custom width for columns. In the future this will be in the database
  $columnWidthConfig = array(
    "tok_grapheme_ids" => 6,
    "col_item_part_fragment_ids" => 5
  );
  //Set column groups. To be added into database
  $columnGroups = array(
    "tok" => array(
      "tok_attribution_ids" => "Control",
      "tok_owner_id" => "Control",
      "tok_annotation_ids" => "Control",
      "tok_visibility_ids" => "Control"
    ),
    "col" => array(
      "col_attribution_ids" => "Control",
      "col_owner_id" => "Control",
      "col_annotation_ids" => "Control",
      "col_visibility_ids" => "Control"
    )
  );

/* depricatedthis code was outdated by change to looking up term code and categorising by name
  $dbMgr->query("SELECT trm_id from term where trm_labels::hstore->'en' = 'EntityField');");//??????
  if ($row = $dbMgr->fetchResultRow()) {
    $entFieldTrmID = $row[0];
  }*/
  $entityCountByTableName = array();
  $dbMgr->query("SELECT relname,reltuples FROM pg_class C LEFT JOIN pg_namespace N ON (N.oid = C.relnamespace) ".
                "WHERE nspname NOT IN ('pg_catalog', 'information_schema') AND relkind='r' AND nspname = 'public' ".
                "ORDER BY reltuples DESC;");
  while($row = $dbMgr->fetchResultRow()){
    $entityCountByTableName[$row['relname']] = $row['reltuples'];
  }
  $dbMgr->query("SELECT trm_id, trm_labels::hstore->'en' as trm_label, trm_code, trm_list_ids, trm_parent_id FROM term;");
  while($row = $dbMgr->fetchResultRow()){
    $termIDToEnLabel[$row['trm_id']] = $row['trm_label'];
    $termIDToTerm[$row['trm_id']] = $row;
    if ($row['trm_parent_id']) {
     $termIDToParentID[$row['trm_id']] = $row['trm_parent_id'];
    }
    if ($row['trm_code']) {
      $termIDToCode[$row['trm_id']] = $row['trm_code'];
    }
    if (in_array($row['trm_label'], $enumLables)) {
      $enumTypeIDs[$row['trm_id']] = 1;
    }
    if (strpos($row['trm_label'],'FK-') === 0) {
      $subtype = array();
      if(strpos($row['trm_label'],'Hom')) {
        $subtype['ho'] = 1;
      }else  if(strpos($row['trm_label'],'Het')) {
        $subtype['he'] = 1;
      }else{
        $subtype['pr'] = 1;
      }
      if(strpos($row['trm_label'],'Multi')) {
        $subtype['mu'] = 1;
      }else{
        $subtype['si'] = 1;
      }
      if(strpos($row['trm_label'],'Ord')) {
        $subtype['ord'] = 1;
      }
      $fkFieldTermIDs[$row['trm_id']] = $subtype;
    }
    if (strpos($row['trm_label'],'Automation') === 0) {
      $automationTermIDs[$row['trm_id']] = 1;
    }
    if (strpos($row['trm_label'],'(UI)') === 0) {
      $uiAssistTermIDs[$row['trm_id']] = 1;
    }
  }
  foreach ($termIDToParentID as $trmID => $pTrmID) {
    $term_parentLabelToID[mb_strtolower($termIDToEnLabel[$trmID].($pTrmID?'-'.$termIDToEnLabel[$pTrmID]:''),'utf-8')] = $trmID;
  }
  $termInfo['idByTerm_ParentLabel'] = $term_parentLabelToID;
  $termInfo['labelByID'] = $termIDToEnLabel;
  $termInfo['termByID'] = $termIDToTerm;
  $termInfo['codeByID'] = $termIDToCode;
  $termInfo['enumTypeIDs'] = $enumTypeIDs;
  $termInfo['fkTypeIDs'] = $fkFieldTermIDs;
  $termInfo['automationTypeIDs'] = $automationTermIDs;
  $termInfo['uiAssistTypeIDs'] = $uiAssistTermIDs;
  $dbMgr->query("SELECT trm_id as id, trm_labels::hstore->'en' as dname, trm_code as prefix ".
                "FROM term WHERE trm_parent_id in (select trm_id from term where trm_labels::hstore->'en' = 'SystemEntity');");
  $entityInfo = array('entities'=>array(),
                      'lookups'=>array(),
                      'fkConstraints'=>array(),
                      'idToPrefix'=>array(),
                      'nameToPrefix'=>array());
  while($row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC)){
    $entityInfo['entities'][$row['prefix']] = $row;
    $entityInfo['idToPrefix'][$row['id']] = $row['prefix'];
  }
  foreach ($entityInfo['idToPrefix'] as $id => $prefix){
    $dbMgr->query("SELECT trm_id as id, def.ord as ord, def.name as tname, trm_labels::hstore->'en' as dname,trm_type_id as type,".
                        " trm_code as name, trm_list_ids as termset, trm_description as descr, def.dtype, def.nullable ".
                  "FROM term left join (SELECT column_name as col, ordinal_position as ord, table_name as name, data_type as dtype, is_nullable as nullable ".
                                        "FROM INFORMATION_SCHEMA.COLUMNS ".
                                        "WHERE column_name LIKE '$prefix%' ".
                                        "ORDER BY ordinal_position) def on def.col = trm_code ".
                  "WHERE trm_code is not null AND ord is not null ".
                  "ORDER BY ord;");
    while($row = $dbMgr->fetchResultRow(null,null,PGSQL_ASSOC)){
      $column = array();
      $column['id'] = $row['id'];
      $column['ord'] = $row['ord']-1;
      $column['dname'] = $row['dname'];
      $column['type'] = $row['type'];
      $column['name'] = $row['name'];
      //Get custom column width
      if(array_key_exists($row['name'], $columnWidthConfig)) {
        $column['width'] = $columnWidthConfig[$row['name']];
      }
      //Get column group
      if(array_key_exists($prefix, $columnGroups)) {
        if(array_key_exists($row['name'], $columnGroups[$prefix])) {
          $column['group'] = $columnGroups[$prefix][$row['name']];
        }
      }

      $column['nullable'] = ($row['nullable'] == "YES");
      if ($row['dtype'] == 'integer') {
        $column['dtype'] = 'int';
      }else if (strpos($row['name'],'sort_code') > -1) {
        $column['dtype'] = 'float';
      }else{
        $column['dtype'] = 'string';
      }
      if ($row['termset']) {
        $ids = explode(",",trim($row['termset'],"{}"));
        $termset = array();
        if ( array_key_exists($row['type'],$enumTypeIDs)) {
          foreach($ids as $id) {
            array_push($termset,array('trmID'=>$id,'label'=>$termIDToEnLabel[$id]));
          }
          $column['enum'] = $termset;
          if (!array_key_exists($prefix,$entityInfo['lookups'])) {
            $entityInfo['lookups'][$prefix] = array($column['name']=>$termset);
          }else{
            $entityInfo['lookups'][$prefix][$column['name']] = $termset;
          }
        }else{
          foreach($ids as $id) {
            if (array_key_exists($id,$termIDToCode)) {
              array_push($termset,$termIDToCode[$id]);
            }
          }
          $column['fktypes'] = $termset;
          if ($column['type'] != $term_parentLabelToID['key-termtype']) {
            if (!array_key_exists($prefix,$entityInfo['fkConstraints'])) {
              $entityInfo['fkConstraints'][$prefix] = array($column['name']=>$termset,
                                                            'typeByColumnName' => array($column['name']=>$column['type']));
            }else{
              $entityInfo['fkConstraints'][$prefix][$column['name']] = $termset;
              $entityInfo['fkConstraints'][$prefix]['typeByColumnName'][$column['name']] = $column['type'];
            }
          }
        }
      }
      $column['descr'] = $row['descr']?$row['descr']:'';
      if (!array_key_exists($row['tname'],$entityInfo['nameToPrefix'])) {
        $entityInfo['nameToPrefix'][$row['tname']] = $prefix;
        $entityInfo['entities'][$prefix]['name'] = $row['tname'];
        $entityInfo['entities'][$prefix]['count'] = $entityCountByTableName[$row['tname']];
        $entityInfo['entities'][$prefix]['nameToIndex'] = array($column['name']=>0);
        $entityInfo['entities'][$prefix]['columns'] = array($column);
      }else if (array_key_exists('nameToIndex',$entityInfo['entities'][$prefix])){
        $entityInfo['entities'][$prefix]['nameToIndex'][$column['name']] = count($entityInfo['entities'][$prefix]['nameToIndex']);
        array_push($entityInfo['entities'][$prefix]['columns'],$column);
      }
    }
  }
  //fake blended as entity  ?? could be done in term table.
  $entityInfo['nameToPrefix']['blended'] = 'bld';
  $entityInfo['entities']['bld']['name'] = 'blended';
  $entityInfo['entities']['bld']['dname'] = 'Blended Entities';
  $entityInfo['entities']['bld']['count'] = "0";
  $entityInfo['entities']['bld']['prefix'] = 'bld';
  $entityInfo['entities']['bld']['columns'] = array();
  //todo lookup type  for id
  array_push($entityInfo['entities']['bld']['columns'],array('dname'=>'GID','dtype'=>'string','descr'=>'','fktypes'=>array('col','frg','img','itm','prt','srf','txt','atb','atg','bln','cmp','gra','ugr','lem','inf','lin','run','seg','spn','scl','tok','ano','cat','dat','edn','era','mcx','seq','trm','tmd','bib','lnk','prn','ucf'),'name'=>'bld_id','nullable'=>false,'ord'=>0,'type'=>$term_parentLabelToID['key-termtype']));
  array_push($entityInfo['entities']['bld']['columns'],array('dname'=>'PV Pairs','dtype'=>'string','descr'=>'','name'=>'bld_properties','nullable'=>false,'ord'=>1,'type'=>$term_parentLabelToID["text-multiple-termtype"]));

  $mainMenuStructure = array( "Object Group" => array("col","itm","prt","frg","mcx"),
                              "Image Group" => array("srf","img","bln"),
                              "Script Group" => array("seg","spn","run"),
                              "Edition Group" => array("cat","edn","seq","tmd","txt"),
                              "Token Group" => array("scl","gra","tok","cmp","inf","lem"),
                              "Data Group" => array("trm","prn","dat","era"),
                              "Link Group" => array("ano","seq"),
                              "Attribution Group" => array("atb","atg","ugr","bib"));
  //Add All groud which contains or entities
  $allPrefixes = array();
  foreach ($mainMenuStructure as $menuGrpName => $prefixes) {
    $allPrefixes = array_merge($allPrefixes, $prefixes);
  }
  sort($allPrefixes);
  $mainMenuStructure = array_merge(array("All" => $allPrefixes), $mainMenuStructure);

  $mainMenuSource = array();
  $delta = 5000;
  foreach ($mainMenuStructure as $menuGrpName => $prefixes) {
    $subMenu = array();
    foreach ($prefixes as $prefix) {
      if (!array_key_exists($prefix,$entityInfo['entities'])) {
        error_log("prefix $prefix is not in the database definition, may need to update the DB schema");
        continue;
      }
      $cnt = $entityInfo['entities'][$prefix]['count'];
      $displayName = $entityInfo['entities'][$prefix]['dname'];
      if ($cnt < $delta + 100) {//set query to get all entities
        array_push($subMenu, array('label' => $displayName,
                                   'value' => "$prefix:all"));
//                                   'value' => '"'.$prefix.'":{"ids":["all"]}'));
      }else{// large data set so break up into sub range of entities.
        $start = 1;
        $end = $delta;
        $subSubMenu = array();
        while ($end <= $cnt) {
          if ( $cnt < $end + 100) {
            $end = $cnt;
          }
          array_push($subSubMenu, array('label' => $displayName." ($start - $end)",
                                        'value' => "$prefix:$start-$end"));
//                                        'value' => '"'.$prefix.'":{"ids":["'.$start.'-'.$end.'"]}'));
          $start += $delta;
          $end += $delta;
          if ($end > $cnt){
          array_push($subSubMenu, array('label' => $displayName." ($start - $cnt)",
                                        'value' => "$prefix:$start-$cnt"));
//                                        'value' => '"'.$prefix.'":{"ids":["'.$start.'-'.$cnt.'"]}'));
          }
        }
        array_push($subSubMenu, array('label' => $displayName." (all)",
                                   'value' => "$prefix:all"));
//                                   'value' => '"'.$prefix.'":{"ids":["all"]}'));
        $width = (strlen($displayName." ($start - $cnt)")-1) * 10;
        array_push($subMenu, array('label' => $displayName,
                                   'items' => $subSubMenu,
                                   'subMenuWidth' => $width."px"));
      }
    }
    array_push($mainMenuSource, array('label' => $menuGrpName,
                                      'items' => $subMenu));
  }

  //Set column group mapping
  $columnGroupInfo = array();
  foreach($columnGroups as $prefix => $info) {
    $colGroup = array();
    foreach ($info as $column => $group) {
      if(array_key_exists($group, $colGroup)) {
        $colGroup[$group][] = $column;
      }
      else {
        $colGroup[$group] = array();
        $colGroup[$group][] = $column;
      }
    }
    $columnGroupInfo[$prefix] = $colGroup;
  }
  ob_clean();
  print  "var mainMenuSource = ".json_encode($mainMenuSource,true)." ;\n";//to be deprecated used for entity UI tool

  print  "var entityInfo = ".json_encode($entityInfo,true)." ;\n";
  print  "var seqTypeInfo = ".json_encode(getSeqTypeInfo(),true)." ;\n";
  print  "var linkTypeInfo = ".json_encode(getLinkTypeInfo(),true)." ;\n";
  print  "var termInfo = ".json_encode($termInfo,true)." ;\n";
  print  "var columnGroupInfo =".json_encode($columnGroupInfo,true)." ;";//to be deprecated used for entity UI tool
?>
