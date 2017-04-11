export PGCLIENTENCODING=UTF8
export LANGUAGE="en_US.UTF-8"
export LANG="en_US.UTF-8"
export LC_ALL="en_US.UTF-8"

usage ()
{
  echo 'Usage : extract_dbtables_to_xml.sh dbname'
  exit
}

if [ "$1" == "" ]
then
  usage
fi

ADMINPSWD="admin"
PSQLPATH="/C/xampp/PostgreSQL/9.3/bin/psql -U postgres "
PHPPATH="/C/xampp/php/php "
TABLENAMES_QUERY="SELECT relname
                  FROM pg_class C
                    LEFT JOIN pg_namespace N ON (N.oid = C.relnamespace)
                  WHERE nspname NOT IN ('pg_catalog', 'information_schema')
                    AND relkind='r' AND nspname = 'public'
                  ORDER BY relname;"
echo $TABLENAMES_QUERY | $PSQLPATH -t -d $1 > $1_table_names.txt
echo '<?xml version="1.0" encoding="utf-8"?>' > $1_schema.xsd
echo "<root>" >> $1_schema.xsd

cat $1_table_names.txt | \
while read tablename; do
  tablename=$( echo "${tablename}"| sed -e 's/^ *//' -e 's/ *$//' );
  echo '<?xml version="1.0" encoding="utf-8"?>' > $1_$tablename.xml
  echo '<postgresql_export>' >> $1_$tablename.xml
  if [[ -n "$tablename" ]]
   then
    $PSQLPATH -tAq -d $1 -c "select table_to_xml('$tablename',true,true,'');" >> $1_$tablename.xml
    $PSQLPATH -tAq -d $1 -c "select table_to_xmlschema('$tablename',true,true,'');" >> $1_schema.xsd
  fi
  echo '</postgresql_export>' >> $1_$tablename.xml
#  if [ -e ./$1_$tablename.xml ]
#    then /opt/basex/bin/basexclient -Uadmin -P$ADMINPSWD -c "CHECK $1; REPLACE $1_$tablename.xml $PWD/$1_$tablename.xml"
#  fi
#  if [ -e ./$1_$tablename.xml ]
#  then rm ./$1_$tablename.xml
#  fi

done
echo "</root>" >> $1_schema.xsd
if [[ -e ./removeDuplicateNodes.php  &&  -e ./$1_schema.xsd ]]; then
  $PHPPATH ./removeDuplicateNodes.php $1_schema.xsd
  echo "removed duplicate elements from $1_schema.xsd"
fi
#if [ -e ./$1_schema.xsd ]; then
#  /opt/basex/bin/basexclient -Uadmin -P$ADMINPSWD -c "CHECK $1; REPLACE $1_schema.xsd $PWD/$1_schema.xsd"
#fi
