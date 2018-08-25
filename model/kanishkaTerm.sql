CREATE EXTENSION IF NOT EXISTS hstore;

CREATE TABLE term
(
  "trm_id" serial NOT NULL PRIMARY KEY,
  "trm_labels" text NOT NULL DEFAULT 'Need Labels',
  "trm_parent_id" int NULL DEFAULT 1,
  "trm_type_id" int NULL DEFAULT 1,
  "trm_list_ids" int[] NULL,
  "trm_code" text NULL,
  "trm_description" text NULL,
  "trm_url" text NULL,
  "trm_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "trm_owner_id" int NULL DEFAULT 2,
  "trm_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "trm_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "trm_scratch" text NULL
)
WITH (
  OIDS=FALSE
);
