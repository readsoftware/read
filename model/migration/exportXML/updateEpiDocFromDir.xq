declare namespace epi = 'dbax/epi';
declare variable $dbxName as xs:string external := "test";
declare variable $dbName as xs:string external := "sample";
declare variable $dir as xs:string external := "./epidoc"; (: local path where exported document are :)
declare %updating function epi:epidocImport(
  $filepathname  as xs:string,
  $dbxFilepath  as xs:string,
  $dbxName  as xs:string,
  $retInfo as element()*
){
  db:output($retInfo),
  db:replace($dbxName, $dbxFilepath, doc($filepathname))
};

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

where (not(file:is-dir($filepath)) and $isValidEpidoc and $dbExist)
  let $mod := file:last-modified($filepath)
  let $fileurl := "file:/" || $filepath
  let $filename := replace($docname,"epidoc_","")
  let $dbxFilepath := $dbName || "/epidoc/" || $filename
  let $returnInfo :=
      <fileinfo>
         <storeTo> {$dbxFilepath} </storeTo>
         <path> {$dir} </path>
         <name> {$docname} </name>
         <lastmod> {$mod} </lastmod>
       </fileinfo>
return epi:epidocImport($filepath,$dbxFilepath,$dbxName,$returnInfo)
