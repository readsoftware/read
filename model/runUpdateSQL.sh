export PGCLIENTENCODING=UTF8
export LANGUAGE="en_US.UTF-8"
export LANG="en_US.UTF-8"
export LC_ALL="en_US.UTF-8"

usage ()
{
  echo 'Usage : runUpdateSQL.sh dbname commands.sql'
  exit
}

if [ "$1" == "" ]
then
  usage
fi

if [ "$2" == "" ]
then
  usage
fi

psql -U postgres -d $1 -f $2

#/C/xampp/PostgreSQL/9.5/bin/psql -U postgres -d $1 -f /C/xampp/htdocs/kanishka/model/authTokenTable.sql
