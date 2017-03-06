CREATE EXTENSION IF NOT EXISTS dblink;
DROP VIEW IF EXISTS termmaintenance_term;
CREATE OR REPLACE VIEW termmaintenance_term AS
  SELECT *
    FROM dblink('dbname=termmaintenance user=postgres password=gandhari', 'select trm_id,trm_labels,trm_type_id,trm_parent_id,trm_code,trm_list_ids from term')
    AS t1(trm_id int, trm_labels text, trm_type_id int, trm_parent_id int, trm_code text, trm_list_ids int[]);
