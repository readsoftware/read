export PGCLIENTENCODING=UTF8
export LANGUAGE="en_US.UTF-8"
export LANG="en_US.UTF-8"
export LC_ALL="en_US.UTF-8"

usage ()
{
  echo 'Usage : buildDBforImport9.3.sh dbname'
  exit
}

if [ "$1" == "" ]
then
  usage
fi

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -c "DROP DATABASE IF EXISTS $1;"

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -c "CREATE DATABASE $1 WITH OWNER = postgres ENCODING = 'UTF8' TABLESPACE = pg_default LC_COLLATE = 'C' LC_CTYPE = 'C' CONNECTION LIMIT = -1 TEMPLATE template0;"

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/kanishkaSchema.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/term.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/propernoun.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/kanishkaTestData_Era.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/kanishkaTestData_Users.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/kanishkaTestData_Collections.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/kanishkaTestData_AttribGrp.sql

/C/xampp/PostgreSQL/9.3/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/kanishkaSchemaConstraints.sql

/C/xampp/php/php.exe ./migration/AzesBibliographyMigration.php -db $1

