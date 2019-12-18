-- ************* Schema Mods

ALTER TABLE propernoun ADD COLUMN prn_parent_id int NULL;
COMMENT ON COLUMN propernoun."prn_parent_id" IS 'Link to propernoun that conceptually contains this propernoun.';

