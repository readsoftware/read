export PGCLIENTENCODING=UTF8
export LANGUAGE="en_US.UTF-8"
export LANG="en_US.UTF-8"
export LC_ALL="en_US.UTF-8"

if ! psql -U postgres -lqt | cut -d \| -f 1 | grep -qw termmaintenance; then
  echo "termmaintenance database doesn't exist so creating"
  psql -U postgres -c "CREATE DATABASE termmaintenance WITH OWNER = postgres ENCODING = 'UTF8' TABLESPACE = pg_default LC_COLLATE = 'C' LC_CTYPE = 'C' CONNECTION LIMIT = -1 TEMPLATE template0;"
  echo "importing term.sql "
  psql -U postgres -d termmaintenance -f kanishkaTerm.sql
  psql -U postgres -d termmaintenance -f term.sql
else
  monthday=$(date '+%m_%d')
  echo "archiving terms_last to  terms$monthday"
  psql -U postgres -d termmaintenance -c "DROP TABLE IF EXISTS terms$monthday"
  psql -U postgres -d termmaintenance -c "ALTER TABLE IF EXISTS terms_last RENAME TO terms$monthday"
  echo "archiving terms to terms_last"
  psql -U postgres -d termmaintenance -c "ALTER TABLE IF EXISTS term RENAME TO terms_last"
  echo "importing term.sql "
  psql -U postgres -d termmaintenance -f kanishkaTerm.sql
  psql -U postgres -d termmaintenance -f term.sql
  echo "show changes between terms to terms_last"
  psql -U postgres -d termmaintenance -c "SELECT c.trm_id,concat(c.trm_labels::hstore->'en','_',p.trm_labels::hstore->'en') AS label_parentlabel,c.trm_type_id,c.trm_code, c.trm_list_ids FROM term c LEFT JOIN term p ON c.trm_parent_id = p.trm_id ORDER BY c.trm_ID" > allTermInfo.txt
  psql -U postgres -d termmaintenance -c "SELECT c.trm_id,concat(c.trm_labels::hstore->'en','_',p.trm_labels::hstore->'en') AS label_parentlabel,c.trm_type_id,c.trm_code, c.trm_list_ids FROM terms_last c LEFT JOIN terms_last p ON c.trm_parent_id = p.trm_id ORDER BY c.trm_ID" > allLastTermInfo.txt
  diff  -bBwy --suppress-common-lines allLastTermInfo.txt allTermInfo.txt
fi