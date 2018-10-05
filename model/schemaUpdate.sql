-- ************* Schema Mods

ALTER TABLE item ADD COLUMN itm_description text;
ALTER TABLE item ADD COLUMN itm_idno text;
COMMENT ON COLUMN item."itm_description" IS 'Free text description of item.';
COMMENT ON COLUMN item."itm_idno" IS 'Free text identifier/reference number of item.';
ALTER TABLE part ADD COLUMN prt_description text;
COMMENT ON COLUMN part."prt_description" IS 'Free text description of this part.';
ALTER TABLE sequence ADD COLUMN seq_ord int;
COMMENT ON COLUMN sequence."seq_ord" IS 'ordinal number define order is a grouping of sibling sequences.';
ALTER TABLE surface ADD COLUMN srf_label text;
COMMENT ON COLUMN surface."srf_label" IS 'Free text label identifying this surface.';
ALTER TABLE syllablecluster ADD COLUMN scl_sort_code2 text;
COMMENT ON COLUMN syllablecluster."scl_sort_code2" IS 'Secondary sort code used to order clusters.';

