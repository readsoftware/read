export PGCLIENTENCODING=UTF8
export LANGUAGE="en_US.UTF-8"
export LANG="en_US.UTF-8"
export LC_ALL="en_US.UTF-8"

usage ()
{
  echo 'Usage : rebuildTermsTable.sh dbname'
  exit
}

if [ "$1" == "" ]
then
  usage
fi

echo "preparing to update terms for '$1' database showing differences with termmaintenance term table"

psql -U postgres -d $1 -c "SELECT c.trm_id,concat(c.trm_labels::hstore->'en','_',p.trm_labels::hstore->'en') AS label_parentlabel,c.trm_type_id,c.trm_code, c.trm_list_ids FROM term c LEFT JOIN term p ON c.trm_parent_id = p.trm_id ORDER BY c.trm_ID" > allTermInfoForDB.txt

psql -U postgres -d termmaintenance -c "SELECT c.trm_id,concat(c.trm_labels::hstore->'en','_',p.trm_labels::hstore->'en') AS label_parentlabel,c.trm_type_id,c.trm_code, c.trm_list_ids FROM term c LEFT JOIN term p ON c.trm_parent_id = p.trm_id ORDER BY c.trm_ID" > allTermInfo.txt

diff  -bBwy --suppress-common-lines allTermInfoForDB.txt allTermInfo.txt

echo -n "Please review changes! Press enter to continue. > "

read response

echo "creating view for new terms from termmaintenance database"

psql -U postgres -d $1 -f maintTermViewQuery.sql

echo "output used type ids for database '$1' before term update to trmTypeUsageBeforeTermTableUpdate.txt"

psql -U postgres -d $1 -f typeTermUsageQuery.sql > trmTypeUsageBeforeTermTableUpdate.txt

echo "output used type ids for database termmaintenance before term update to maintTermTypeUsage.txt"

psql -U postgres -d $1 -f typeTermUsageMappedToMaintQuery.sql > maintTermTypeUsage.txt

echo "show differences for used terms in '$1' compared to those term ids in the new term.sql, blank means no differences detected"

diff  -bBwy --suppress-common-lines trmTypeUsageBeforeTermTableUpdate.txt maintTermTypeUsage.txt

psql -U postgres -d $1 -c "DROP VIEW IF EXISTS termmaintenance_term;"

echo -n "Review used term changes! Would you like to update? (Y/n) > "

read response

if [ "$response" != "Y" ]; then
    echo "response '$response' not equal 'Y'. Aborting term update!"
    exit 1
fi

echo " updating term table for database '$1' "

psql -U postgres -d $1 -f disableAllTriggers.sql | grep ERROR

psql -U postgres -d $1 -c 'DELETE FROM term;'

psql -U postgres -d $1 -c 'ALTER SEQUENCE term_trm_id_seq RESTART WITH 1;'

psql -U postgres -d $1 -f term.sql

psql -U postgres -d $1 -f enableAllTriggers.sql | grep ERROR

echo 'output used type ids after term update to trmTypeUsageAfterTermTableUpdate.txt'

psql -U postgres -d $1 -f typeTermUsageQuery.sql > trmTypeUsageAfterTermTableUpdate.txt

echo 'show differences between before and after, blank means no differences detected'

diff  -bBwy --suppress-common-lines trmTypeUsageBeforeTermTableUpdate.txt trmTypeUsageAfterTermTableUpdate.txt