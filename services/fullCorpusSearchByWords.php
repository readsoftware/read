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

  $dbMgr = new DBManager();
  //check and validate parameters
  $data = (array_key_exists('data',$_REQUEST)? json_decode($_REQUEST['data'],true):$_REQUEST);
  if (!$data) {
    returnXMLErrorMsgPage("invalid viewer request - not enough or invalid parameters");
  } else {
    //get parameters
    $str1 = (isset($data['str1']) && $data['str1'])? $data['str1']:null;
    $str2 = (isset($data['str2']) && $data['str2'])? $data['str2']:null;
    $rng = (isset($data['rng']) && $data['rng'])? $data['rng']:null;

    $query =
    "select r.* from
    (select a.txt_id as txt_id, concat('tok:',a.w1_id),concat('tok:',b.w2_id), 
              a.w1_ord, b.w2_ord, a.w1_loc, b.w2_loc, a.edn_id, a.seq_id, b.w2_ord-a.w1_ord as wrd_rng, 
              a.edn_word_ids[a.w1_ord-1: a.w1_ord+$rng]
      from  (select txt_id, edn_id, seq_id, array_position(edn_word_ids::text[],concat('tok:',tok_id)) as w1_ord, 
                    w1.tok_id as w1_id, w1.tok_value as w1_val, edn_word_ids, 
                    w1.tok_scratch::json->'locLabel' as w1_loc
            from token w1
            left join word_search_lookup on concat('tok:',tok_id) = ANY(edn_word_ids)
            where tok_value ~* '$str1' and not tok_owner_id = 1 and edn_id is not null
            order by edn_id, w1_ord) a
      left join
            (select edn_id, array_position(edn_word_ids::text[],concat('tok:',tok_id)) as w2_ord, 
                    w2.tok_id as w2_id, w2.tok_value as w2_val,w2.tok_scratch::json->'locLabel' as w2_loc
            from token w2
            left join word_search_lookup on concat('tok:',tok_id) = ANY(edn_word_ids)
            where tok_value ~* '$str2' and not tok_owner_id = 1 and edn_id is not null
            order by edn_id, w2_ord) b
      on a.edn_id = b.edn_id
      where abs(b.w2_ord-a.w1_ord) < $rng+1

      union all

      select a.txt_id as txt_id, concat('cmp:',a.w1_id),concat('cmp:',b.w2_id), 
            a.w1_ord, b.w2_ord, a.w1_loc, b.w2_loc, a.edn_id, a.seq_id, b.w2_ord-a.w1_ord as wrd_rng, 
            a.edn_word_ids[a.w1_ord-1: a.w1_ord+$rng]
      from  (select txt_id, edn_id, seq_id, array_position(edn_word_ids::text[],concat('cmp:',cmp_id)) as w1_ord, 
                    w1.cmp_id as w1_id, w1.cmp_value as w1_val, edn_word_ids, 
                    w1.cmp_scratch::json->'locLabel' as w1_loc
            from compound w1
            left join word_search_lookup on concat('cmp:',cmp_id) = ANY(edn_word_ids)
            where cmp_value ~* '$str1' and not cmp_owner_id = 1 and edn_id is not null
            order by edn_id, w1_ord) a
      left join
            (select edn_id, array_position(edn_word_ids::text[],concat('cmp:',cmp_id)) as w2_ord, 
                    w2.cmp_id as w2_id, w2.cmp_value as w2_val,
                    w2.cmp_scratch::json->'locLabel' as w2_loc
            from compound w2
            left join word_search_lookup on concat('cmp:',cmp_id) = ANY(edn_word_ids)
            where cmp_value ~* '$str2' and not cmp_owner_id = 1 and edn_id is not null
            order by edn_id, w2_ord) b
      on a.edn_id = b.edn_id
      where abs(b.w2_ord-a.w1_ord) < $rng+1 ) r
    order by r.txt_id";

    $dbMgr->query($query);

    $retVal = $dbMgr->fetchAllResultRows(false);
    if (array_key_exists("callback",$_REQUEST)) {
      $cb = $_REQUEST['callback'];
      if (strpos("YUI",$cb) == 0) { // YUI callback need to wrap
        print $cb."(".json_encode($retVal).");";
      }
    } else {
      print json_encode($retVal);
    }
  }

  function returnXMLErrorMsgPage($msg) {
    die("<?xml version='1.0' encoding='UTF-8'?>\n<error>$msg</error>");
  }

?>
