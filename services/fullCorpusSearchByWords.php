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
* fullCorpusSearchByWords
*
* search all database words with 2 POSIX search string for a proximity match
*
*  /read/services/fullCorpusSearchByWords.php?db=mydb&str1=.*tre$&str2=^pari.*&rng=3
*
*  /read/services/fullCorpusSearchByWords.php?db=gandhari&str1=.*tre$&rng=5&verbose=1&txtIDs=12,13,15,26,27,55,125,170,242
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
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//create entity

  $dbMgr = new DBManager();
  //check and validate parameters
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    returnXMLErrorMsgPage("invalid viewer request - not enough or invalid parameters");
  } else {
    //get parameters
    $str1 = (isset($data['str1']) && $data['str1'])? $data['str1']:null;
    $str2 = (isset($data['str2']) && $data['str2'])? $data['str2']:null;
    $rng = (isset($data['rng']) && $data['rng'])? $data['rng']:1;
    $verbose = (isset($data['verbose']) && $data['verbose'])? $data['verbose']:null;
    $matchorder = (isset($data['matchorder']) && $data['matchorder'])? $data['matchorder']:null;
    $txtIDs = (isset($data['txtIDs']) && $data['txtIDs'])? $data['txtIDs']:null;
    $txtconstraint = "";
    $orderconstraint = "";
    if ($txtIDs) {
      // todo  add code to clense $txt ids needs to be string of comma separated ints
      $txtconstraint = "and a.txt_id in ($txtIDs)";
    }
    if ($matchorder) {
      $orderconstraint = "and w2.ord-w1.ord > -1";
    }
    if (!$str2) {
      if ($txtconstraint){
        $txtconstraint = "and ".$txtconstraint;
      }
      $query =
      "select r.* from
          (select txt_id, txt_title, txt_ref, w1.edn_id, seq_id, 
                  w1.edn_word_ids[w1.ord-$rng: w1.ord+$rng]::text[] as edn_word_ids,
                  w1.ord as w1_ord, w1.wrd_id as w1_id, w1.val as w1_val, w1.loc as w1_loc,
                  (case when w1.ord > $rng then -$rng when w1.ord = 1 then 0 else -w1.ord + 1 end) as istart 
           from (select w.* from 
                    (select txt_id, txt_title, txt_ref, edn_id, seq_id, 
                            array_position(edn_word_ids::text[],concat('tok:',tok_id)) as ord, 
                            concat('tok:',tok_id)::text as wrd_id, tok_value as val, edn_word_ids, 
                            tok_scratch::json->'locLabel' as loc
                    from token 
                          left join word_search_lookup on concat('tok:',tok_id) = ANY(edn_word_ids)
                    where tok_value ~* '$str1' and not tok_owner_id = 1 
                          and edn_id is not null and txt_id is not null
                          $txtconstraint

                    union all

                    select txt_id, txt_title, txt_ref, edn_id, seq_id, 
                            array_position(edn_word_ids::text[],concat('cmp:',cmp_id)) as ord, 
                            concat('cmp:',cmp_id)::text as wrd_id, cmp_value as val, edn_word_ids, 
                            cmp_scratch::json->'locLabel' as loc
                    from compound 
                          left join word_search_lookup on concat('cmp:',cmp_id) = ANY(edn_word_ids)
                    where cmp_value ~* '$str1' and not cmp_owner_id = 1 
                          and edn_id is not null and txt_id is not null
                          $txtconstraint
                          ) w
                  order by w.edn_id, w.ord
                ) w1
          ) r
      order by r.txt_id";

    } else {
      $query =
      "select r.* from
        (select txt_id, txt_title, txt_ref, w1.edn_id, seq_id, 
          w1.edn_word_ids[w1.ord-$rng: w1.ord+$rng]::text[] as edn_word_ids,
          w1.ord as w1_ord, w2.ord as w2_ord, w1.wrd_id as w1_id, w2.wrd_id as w2_id, 
          w1.val as w1_val, w2.val as w2_val, w1.loc as w1_loc, w2.loc as w2_loc,
          (w2.ord - w1.ord) as dist, (case when w1.ord > $rng then -$rng when w1.ord = 1 then 0 else -w1.ord + 1 end) as istart 
          from (select w.* from 
                  (select txt_id, txt_title, txt_ref, edn_id, seq_id, 
                          array_position(edn_word_ids::text[],concat('tok:',tok_id)) as ord, 
                          concat('tok:',tok_id)::text as wrd_id, tok_value as val, edn_word_ids, 
                          tok_scratch::json->'locLabel' as loc
                  from token 
                        left join word_search_lookup on concat('tok:',tok_id) = ANY(edn_word_ids)
                  where tok_value ~* '$str1' and not tok_owner_id = 1 
                        and edn_id is not null and txt_id is not null
                        $txtconstraint

                  union all

                  select txt_id, txt_title, txt_ref, edn_id, seq_id, 
                          array_position(edn_word_ids::text[],concat('cmp:',cmp_id)) as ord, 
                          concat('cmp:',cmp_id)::text as wrd_id, cmp_value as val, edn_word_ids, 
                          cmp_scratch::json->'locLabel' as loc
                  from compound 
                        left join word_search_lookup on concat('cmp:',cmp_id) = ANY(edn_word_ids)
                  where cmp_value ~* '$str1' and not cmp_owner_id = 1 
                        and edn_id is not null and txt_id is not null
                        $txtconstraint
                  ) w
                order by w.edn_id, w.ord
              ) w1
          left join
              (select w.edn_id, w.ord, w.val, w.loc, w.wrd_id from 
                  (select txt_id, edn_id, seq_id, 
                          array_position(edn_word_ids::text[],concat('tok:',tok_id)) as ord, 
                          concat('tok:',tok_id)::text as wrd_id, tok_value as val, edn_word_ids, 
                          tok_scratch::json->'locLabel' as loc
                  from token 
                        left join word_search_lookup on concat('tok:',tok_id) = ANY(edn_word_ids)
                  where tok_value ~* '$str2' and not tok_owner_id = 1 
                        and edn_id is not null and txt_id is not null
                        $txtconstraint

                  union all

                  select txt_id, edn_id, seq_id, 
                          array_position(edn_word_ids::text[],concat('cmp:',cmp_id)) as ord, 
                          concat('cmp:',cmp_id)::text as wrd_id, cmp_value as val, edn_word_ids, 
                          cmp_scratch::json->'locLabel' as loc
                  from compound 
                        left join word_search_lookup on concat('cmp:',cmp_id) = ANY(edn_word_ids)
                  where cmp_value ~* '$str2' and not cmp_owner_id = 1 
                        and edn_id is not null and txt_id is not null
                        $txtconstraint
                  ) w
                order by w.edn_id, w.ord
              ) w2
          on w1.edn_id = w2.edn_id
          where abs(w2.ord-w1.ord) < $rng+1 $orderconstraint
        ) r
      order by r.txt_id";
    }
    $dbMgr->query($query);

    $rows = $dbMgr->fetchAllResultRows(false);
    $retVal = array();
    if ($rows) {
      foreach ($rows as $row) {
        $result = array();
        $result['txtID'] = $row['txt_id'];
        $result['txt_title'] = $row['txt_title'];
        $result['txt_ref'] = $row['txt_ref'];
        $result['ednID'] = $row['edn_id'];
        $result['seqType'] = "Text";
        $result['seqID'] = $row['seq_id'];
        $result['query'] = array();
        $result['query']['range'] = $rng;
        $result['query']['str1'] = $str1;
        $result['query']['str2'] = $str2;
        $result['query']['txtIDs'] = $txtIDs;
        $result['query']['matchorder'] = $matchorder;
        $result['query']['verbose'] = $verbose;
        $result['match'] = array();
        $result['match']['gid'] = $row['w1_id'];
        $result['match']['wordOrdinal'] = $row['w1_ord'];
        $result['match']['location'] = trim($row['w1_loc'],"\"");
        $entity = EntityFactory::createEntityFromGlobalID($row['w1_id']);
        $result['match']['search'] = $str1;
        $result['match']['value'] = $entity->getValue();
        $result['match']['transcription'] =  $entity->getTranscription();
        if($str2) {
          $result['match2'] = array();
          $result['match2']['distance'] = $row['dist'];
          $result['match2']['gid'] = $row['w2_id'];
          $result['match2']['wordOrdinal'] = $row['w2_ord'];
          $result['match2']['location'] = trim($row['w2_loc'],"\"");
          $entity = EntityFactory::createEntityFromGlobalID($row['w2_id']);
          $result['match2']['search'] = $str2;
          $result['match2']['value'] = $entity->getValue();
          $result['match2']['transcription'] =  $entity->getTranscription();
        }
        $ednWordGIDs = explode(',',trim($row['edn_word_ids'],"{}"));
        if ($verbose){
          $result['context'] = array();
          $startIndex = $row['istart'];
          foreach ($ednWordGIDs as $wordGID) {
//            if ($startIndex == 0) {
//              $startIndex++;
//              continue;
//            }
            $ctxWord = array();
            $word = EntityFactory::createEntityFromGlobalID($wordGID);
            $ctxWord['gid'] = $wordGID;
            $ctxWord['index'] = $startIndex;
            $ctxWord['value'] = $word->getValue();
            $ctxWord['transcription'] =  $word->getTranscription();
            $startIndex++;
            array_push($result['context'],$ctxWord);
          }
        } else {
          $result['context'] = $ednWordGIDs;
        }
        array_push($retVal,$result);
      }
    }
    if (array_key_exists("callback",$_REQUEST)) {
      $cb = $_REQUEST['callback'];
      if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
        print $cb."(".json_encode($retVal,JSON_PRETTY_PRINT).");";
      }
    } else {
      print json_encode($retVal,JSON_PRETTY_PRINT);
    }
  }

  function returnXMLErrorMsgPage($msg) {
    die("<?xml version='1.0' encoding='UTF-8'?>\n<error>$msg</error>");
  }

?>
