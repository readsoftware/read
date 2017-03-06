(: XQuery file: getNode.xq :)
declare variable $nodeName as xs:string external;
declare variable $id as xs:integer external;
let $idName :=
          IF $nodeName = "token" THEN "tok_id"
          ELSE IF $nodeName = "compound" THEN "cmp_id"
          ELSE IF $nodeName = "sequence" THEN "seq_id"
          ELSE IF $nodeName = "grapheme" THEN "gra_id"
          ELSE IF $nodeName = "syllsblecluster" THEN "scl_id"
          ELSE "???"
RETURN
   {//$nodeName[$idName=$id]}
