<?php
/**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this rngribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can rerngribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is rngributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/
/**
* createWordSearchLookup
*
* recreates the word_search_lookup table for the given database which is used by fullCorpusSearchByWords.php
*
*  /readV1RC/services/fullCorpusSearchByWords.php?db=mydb&str1=.*tre$&str2=^pari.*&rng=3
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Services
*/
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start();

  header("Content-type: text/javascript");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../common/php/utils.php');//get utilies

  define('CHUNK_SIZE',500);

  $dbMgr = new DBManager();
  //check and validate parameters
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);

  $sizeCorpusQuery = "SELECT count(distinct(edn_id)) from edition;";
  $dbMgr->query($sizeCorpusQuery);
  $ednCount = $dbMgr->fetchResultRow(0);
  $subQueryCount = intdiv($ednCount[0],500) + 1;

  $query ="
    DROP AGGREGATE IF EXISTS array_cat_agg(anyarray);
    CREATE AGGREGATE array_cat_agg(anyarray) (
      SFUNC=array_cat,
      STYPE=anyarray
    );

    DROP TABLE IF EXISTS word_search_lookup;
    
    CREATE TABLE word_search_lookup AS
    (
      select t.* from
      (";

  for ($i=0; $i <= $subQueryCount; $i++) {
    $start = $i * CHUNK_SIZE;
    $end = $start + CHUNK_SIZE;
    if ($start){
      $query .= "
      union all ";
    }
    $query .= 
         "select w.txt_id as txt_id, w.txt_title as txt_title, w.txt_ref as txt_ref, ".
                "w.edn_id, w.tseqID as seq_id, w.edn_description, ".
                "array_cat_agg(w.wordIDs)::varchar(30)[] as edn_word_ids ".
         "from ".
         "(select txt_id, txt_title, txt_ref, edn_id, p.seq_id as tseqID, edn_description, ".
                  "c.seq_id, c.seq_entity_ids as wordIDs, ".
                  "array_position( p.seq_entity_ids::text[],concat('seq:',c.seq_id)) as divord ".
          "from  sequence p ".
               "left join sequence c on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids) ".
               "left join edition on p.seq_id = ANY(edn_sequence_ids) ".
               "left join text on txt_id = edn_text_id ".
           "where c.seq_id is not null and p.seq_type_id = 738 and ".
                 "not p.seq_owner_id = 1 and not c.seq_owner_id = 1 and not edn_owner_id = 1 and ".
                   "edn_id > $start and edn_id <=$end ".
           "order by p.seq_id,divord) w ".
        "group by w.txt_id, w.txt_title, w.txt_ref, w.edn_id, w.tseqID, w.edn_description ";
  }
/*      union all 
      select w.txt_id as txt_id, w.edn_id, w.tseqID as seq_id, w.edn_description, array_cat_agg(w.wordIDs)::varchar(30)[] as edn_word_ids
      from
      (select edn_text_id as txt_id, edn_id, p.seq_id as tseqID, edn_description, c.seq_id, c.seq_entity_ids as wordIDs, 
              array_position( p.seq_entity_ids::text[],concat('seq:',c.seq_id)) as divord
        from  sequence p 
            left join sequence c on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids)
            left join edition on p.seq_id = ANY(edn_sequence_ids)
        where c.seq_id is not null and p.seq_type_id = 738 and 
              not p.seq_owner_id = 1 and not c.seq_owner_id = 1 and not edn_owner_id = 1 and 
              edn_id > 500 and edn_id <=1000 
        order by p.seq_id,divord) w
      group by w.txt_id, w.edn_id, w.tseqID, w.edn_description
      union all
      select w.txt_id as txt_id, w.edn_id, w.tseqID as seq_id, w.edn_description, array_cat_agg(w.wordIDs)::varchar(30)[] as edn_word_ids
      from
      (select edn_text_id as txt_id, edn_id, p.seq_id as tseqID, edn_description, c.seq_id, c.seq_entity_ids as wordIDs, 
              array_position( p.seq_entity_ids::text[],concat('seq:',c.seq_id)) as divord
        from  sequence p 
            left join sequence c on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids)
            left join edition on p.seq_id = ANY(edn_sequence_ids)
        where c.seq_id is not null and p.seq_type_id = 738 and 
              not p.seq_owner_id = 1 and not c.seq_owner_id = 1 and not edn_owner_id = 1 and 
              edn_id > 1000 and edn_id <=1500 
        order by p.seq_id,divord) w
      group by w.txt_id, w.edn_id, w.tseqID, w.edn_description
      union all
      select w.txt_id as txt_id, w.edn_id, w.tseqID as seq_id, w.edn_description, array_cat_agg(w.wordIDs)::varchar(30)[] as edn_word_ids
      from
      (select edn_text_id as txt_id, edn_id, p.seq_id as tseqID, edn_description, c.seq_id, c.seq_entity_ids as wordIDs, 
              array_position( p.seq_entity_ids::text[],concat('seq:',c.seq_id)) as divord
        from  sequence p 
            left join sequence c on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids)
            left join edition on p.seq_id = ANY(edn_sequence_ids)
        where c.seq_id is not null and p.seq_type_id = 738 and 
              not p.seq_owner_id = 1 and not c.seq_owner_id = 1 and not edn_owner_id = 1 and 
            edn_id > 1500 and edn_id <=2000 
        order by p.seq_id,divord) w
      group by w.txt_id, w.edn_id, w.tseqID, w.edn_description
      union all
      select w.txt_id as txt_id, w.edn_id, w.tseqID as seq_id, w.edn_description, array_cat_agg(w.wordIDs)::varchar(30)[] as edn_word_ids
      from
      (select edn_text_id as txt_id, edn_id, p.seq_id as tseqID, edn_description, c.seq_id, c.seq_entity_ids as wordIDs, 
              array_position( p.seq_entity_ids::text[],concat('seq:',c.seq_id)) as divord
        from  sequence p 
            left join sequence c on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids)
            left join edition on p.seq_id = ANY(edn_sequence_ids)
        where c.seq_id is not null and p.seq_type_id = 738 and 
              not p.seq_owner_id = 1 and not c.seq_owner_id = 1 and not edn_owner_id = 1 and 
            edn_id > 2000 and edn_id <=2500 
        order by p.seq_id,divord) w
      group by w.txt_id, w.edn_id, w.tseqID, w.edn_description
      union all
      select w.txt_id as txt_id, w.edn_id, w.tseqID as seq_id, w.edn_description, array_cat_agg(w.wordIDs)::varchar(30)[] as edn_word_ids
      from
      (select edn_text_id as txt_id, edn_id, p.seq_id as tseqID, edn_description, c.seq_id, c.seq_entity_ids as wordIDs, 
              array_position( p.seq_entity_ids::text[],concat('seq:',c.seq_id)) as divord
        from  sequence p 
            left join sequence c on concat('seq:',c.seq_id) = ANY(p.seq_entity_ids)
            left join edition on p.seq_id = ANY(edn_sequence_ids)
        where c.seq_id is not null and p.seq_type_id = 738 and 
              not p.seq_owner_id = 1 and not c.seq_owner_id = 1 and not edn_owner_id = 1 and 
              edn_id > 2500 and edn_id <=3000 
        order by p.seq_id,divord) w
      group by w.txt_id, w.edn_id, w.tseqID, w.edn_description
      */
  $query .= "
      ) t order by t.txt_id
    ) WITH DATA;";

  $startt = time();
  $dbMgr->query($query);
  $endt = time();

  $error = $dbMgr->getError();
  $retVal = array();
  $retVal['duration'] = substr($endt - $startt, 0, 4)." secs";
  if ($error){
    $retVal['success'] = false;
    $retVal['message'] = "Error: $error";
  } else {
    $dbMgr->clearResults();
    $sizeLookupQuery = "SELECT count(distinct(edn_id)) from word_search_lookup;";
    $dbMgr->query($sizeLookupQuery);
    $created = $dbMgr->fetchResultRow(0);
    $created = $created[0];
    $retVal['success'] = true;
    $retVal['message'] = "Success: created word_search_lookup with $created rows";
  }
  if (array_key_exists("callback",$_REQUEST)) {
    $cb = $_REQUEST['callback'];
    if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
      print $cb."(".json_encode($retVal).");";
    }
  } else {
    print json_encode($retVal);
  }

  function returnXMLErrorMsgPage($msg) {
    die("<?xml version='1.0' encoding='UTF-8'?>\n<error>$msg</error>");
  }

?>
