(: XQuery file: getNode.xq :)
(: example call  servername:8984/rest?run=readXQ/getNode.xq&db=test&tableName=token&id=25 :)
declare variable $db as xs:string external;
declare variable $tableName as xs:string external;
declare variable $id as xs:integer external;
let $idName := if ($tableName = "token") then "tok_id"
          else if ($tableName = "compound") then "cmp_id"
          else if ($tableName = "sequence") then "seq_id"
          else if ($tableName = "grapheme") then "gra_id"
          else if ($tableName = "syllablecluster") then "scl_id"
          else "???"
for $nodes in db:open($db)//*[name() eq $nodeName][*[name() eq $idName and xs:integer(data()) eq $id]]
 return $nodes
