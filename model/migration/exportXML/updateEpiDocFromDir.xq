declare variable $dbxName as xs:string external := "basexDbName";
declare variable $dbName as xs:string external := "psqlDbName";
declare variable $dir as xs:string external := "/path/to/local/dir/of/tei"; (: local path where exported documents are :)

let $schema := "http://www.stoa.org/epidoc/schema/latest/tei-epidoc.rng"
let $path := $dir || "/" || $dbName
for $docname in file:list($path)
  let $filepath := $path || "/" || $docname
  let $vReport :=   try {
                       validate:rng-report($filepath,$schema)
                    } catch * {
                      <report>
                        <status>
                         'Error [' || $err:code || ']: ' || $err:description
                       </status>
                      </report>
                    }
  let $isValidEpidoc :=(data($vReport/report/status) eq "valid")
  let $dbExist := db:exists($dbxName)
  let $mod := file:last-modified($filepath)
  let $fileurl := "file:/" || $filepath
  let $filename := replace($docname,"epidoc_","")
  let $dbxFilepath := "epidoc/" || $filename
  let $returnInfo :=
      <fileinfo>
         <storeTo> {$dbxFilepath} </storeTo>
         <filepath> {$filepath} </filepath>
         <lastmod> {$mod} </lastmod>
         {$vReport}
       </fileinfo>
return db:replace($dbxName,$dbxFilepath,doc($filepath))