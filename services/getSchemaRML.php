<?php
/**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this distribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can redistribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/
/**
  * getSchemaRML
  *
  *  A service that returns a ReadML structure of the schema for the entities of the system.
  *
  * by default the service outputs all entity definitions. This can be targeted by supplying the entityList value as a single quoted comma delimited list of entities
  * entityList='item','fragment','part','text','surface'
  *
  * by default the Schema returns entityType properties. Properties can be suppressed by supplying the noProps value.
  * noProps=1
  *
  * http://localhost/kanishka/services/getSchemaRML.php
  *   returns a full schema rml output.
  *
  * http://localhost/kanishka/services/getSchemaRML.php?noProps=1
  *   returns a full schema rml output without properties.
  *
  * http://localhost/kanishka/services/getSchemaRML.php?entityList='item','fragment','part','text','surface'
  *   returns schema rml output for 'item','fragment','part','text' and 'surface' entities.
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Service
  */
  define('ISSERVICE',1);
  ini_set("zlib.output_compression_level", 5);
  ob_start('ob_gzhandler');

  header('Content-type: text/xml; charset=utf-8');
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  if(!defined("DBNAME")) define("DBNAME","kanishkatest");
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/xmlNodeHelper.php');//XML helper functions

  $dbMgr = new DBManager();
  $retVal = array();
  $entityTypeList = (array_key_exists('entityList',$_REQUEST)? $_REQUEST['entityList']:
                                                                "'collection','item','part','fragment','image','span','surface','text',
                                                                 'textmetadata','baseline','segment','run','line','syllablecluster','materialcontext',
                                                                 'grapheme','token','compound','lemma','term','catalog','attributiongroup',
                                                                 'sequence','link','edition','bibliography','annotation','attribution','date','era'");
  $includeProperties = (array_key_exists('noProps',$_REQUEST)? false : true);

  $query = "SELECT xmlelement(name entityType,
                    xmlattributes(oid as id, relname as name),
                    xmlelement(name description, pg_catalog.obj_description(oid))";
  if ($includeProperties) {
    $query .= ",xmlelement(name properties,
                           (SELECT xmlagg(prop.pxml) as props
                            FROM (SELECT xmlelement(name property,
                                                    (SELECT xmlelement(name name,
                                                                       xmlattributes('trm:'||pt.trm_id as id),
                                                                       (SELECT xmlagg(plabel.plxml) as plabels
                                                                        FROM (SELECT xmlelement(name label, xmlattributes(key as lang),value) as plxml
                                                                              FROM each(pt.trm_labels::hstore)) plabel))
                                                     FROM term pt
                                                     WHERE pt.trm_code = c.column_name), -- property name labels
                                                    xmlelement(name columnName, c.column_name), -- table column name
                                                    (CASE WHEN c.is_nullable = 'NO' THEN xmlelement(name required)END), -- required
                                                    xmlelement(name type, CASE WHEN c.data_type = 'ARRAY' THEN 'array'|| c.udt_name
                                                                               WHEN c.data_type = 'USER-DEFINED' THEN c.udt_name
                                                                               ELSE c.data_type END  ), -- property type
                                                    xmlelement(name description,pg_catalog.col_description(oid,c.ordinal_position)), -- property description
                                                    (SELECT xmlelement(name termset,
                                                                       (SELECT xmlagg(term.txml) as terms
                                                                        FROM (SELECT xmlelement(name term,
                                                                                                xmlattributes('trm:'||s.trm_id as id),
                                                                                                (SELECT xmlagg(tlabel.lxml) as tlabels
                                                                                                 FROM (SELECT xmlelement(name label, xmlattributes(sl.key as lang),sl.value) as lxml
                                                                                                       FROM each(s.trm_labels::hstore) sl ) tlabel))as txml
                                                                              FROM term s
                                                                              WHERE s.trm_id = any (g.trm_list_ids)) term)
                                                                       )
                                                     FROM term g
                                                     WHERE g.trm_code = c.column_name AND char_length(array_to_string(g.trm_list_ids,',')) > 0) -- property termSet if exist
                                                    ) as pxml -- Property
                                  FROM INFORMATION_SCHEMA.COLUMNS c
                                  WHERE c.table_name = relname) prop)
                           )";
  }
  $query .= '                  ) as entitytype
  FROM  pg_catalog.pg_class
  WHERE relname in ('.$entityTypeList.') order by relname;';

  echo startRML(null,false);
  $dbMgr->query($query);
  echo openXMLNode('entityTypes');
  while ( $row = $dbMgr->fetchResultRow(null,false)) {
    echo $row[0]."\n";
  }
  echo closeXMLNode('entityTypes');
  echo endRML();
?>
