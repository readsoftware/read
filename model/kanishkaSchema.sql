/**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this distribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can redistribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/
/**
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/
/*
  general paterens followed for referential integrity
  array columns are handled (if at all) through triggers and utilities. This can be updated when 9.3 is available with ELEMENT REFERENCES
  link fields are divided into 2 categories "associated" and "bonded" which are treated as a light link that is breakable and
  as a subpart relationship which must be maintained. Associated links are set to NULL on DELETE and bonded links are Restricted and maybe cascade
  in somecase on DELETE. ON UPDATE is only defined where it makes sense or set to NO ACTION.
*/



-- ********* Add hstore module for key=>value pairs
CREATE EXTENSION IF NOT EXISTS hstore;
CREATE EXTENSION IF NOT EXISTS dblink;

--.***** functions ********
CREATE OR REPLACE FUNCTION update_modified()
RETURNS TRIGGER AS $$
BEGIN
   IF row(NEW.*) IS DISTINCT FROM row(OLD.*) THEN
      NEW.modified = now();
      RETURN NEW;
   ELSE
      RETURN OLD;
   END IF;
END;
$$ language 'plpgsql';

CREATE OR REPLACE FUNCTION set_modified()
RETURNS TRIGGER AS $$
BEGIN
  NEW.modified = now();
  RETURN NEW;
END;
$$ language 'plpgsql';
-- *************************************UTILITY TABLES****************************************

-- ************* ANNOTATION TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_anoType";
DROP INDEX IF EXISTS "fki_anoOwner";
-- DROP TRIGGER IF EXISTS update_annotation_modtime ON annotation;
DROP TABLE IF EXISTS annotation CASCADE;


CREATE TABLE annotation
(
  "ano_id" serial NOT NULL PRIMARY KEY,
  "ano_linkfrom_ids" text[] NULL,
  "ano_linkto_ids" text[] NULL,
  "ano_type_id" int NULL,
  "ano_text" text NULL,
  "ano_url" text NULL,
  "ano_annotation_ids" int[] NULL,
  "ano_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "ano_owner_id" int NULL DEFAULT 2,
  "ano_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "ano_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_annotation_modtime BEFORE UPDATE ON annotation FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE annotation OWNER TO postgres;

COMMENT ON TABLE annotation IS 'Contains a record for each annotation.';
COMMENT ON COLUMN annotation."ano_id" IS 'Uniquely identifies a annotation object.';
COMMENT ON COLUMN annotation."ano_text" IS 'Note text for this annotation.';
COMMENT ON COLUMN annotation."ano_linkfrom_ids" IS 'Entity GlobalIDs used as the object/context of this annotation.';
COMMENT ON COLUMN annotation."ano_linkto_ids" IS 'Entity GlobalIDs used as the subject of this annotation.';
COMMENT ON COLUMN annotation."ano_type_id" IS 'Link to term identifying the type of this annotation.';
COMMENT ON COLUMN annotation."ano_url" IS 'URL uesd to annotate.';
COMMENT ON COLUMN annotation."ano_annotation_ids" IS 'Links to commentary on this annotation.';
COMMENT ON COLUMN annotation."ano_attribution_ids" IS 'Links to attributions for this annotation.';
COMMENT ON COLUMN annotation."ano_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN annotation."ano_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_anoType" ON annotation ("ano_type_id");
CREATE INDEX "fki_anoOwner" ON annotation ("ano_owner_id");



-- ************* ATTRIBUTION TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_atbBiblio";
DROP INDEX IF EXISTS "fki_atbGroup";
DROP INDEX IF EXISTS "fki_atbOwner";
-- DROP TRIGGER IF EXISTS update_attribution_modtime ON attribution;
DROP TABLE IF EXISTS attribution CASCADE;

CREATE TABLE attribution
(
  "atb_id" serial NOT NULL PRIMARY KEY,
  "atb_title" text,
  "atb_types" text[] NULL,
  "atb_bib_id" int NULL,
  "atb_detail" text NULL,
  "atb_description" text NULL,
  "atb_group_id" int NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "atb_owner_id" int NULL DEFAULT 2,
  "atb_annotation_ids" int[] NULL,
  "atb_visibility_ids" int[] NULL DEFAULT '{6}',
  "atb_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_attribution_modtime BEFORE UPDATE ON attribution FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE attribution OWNER TO postgres;

-- Indexes:

CREATE INDEX "fki_atbBiblio" ON attribution ("atb_bib_id");
CREATE INDEX "fki_atbGroup" ON attribution ("atb_group_id");
CREATE INDEX "fki_atbOwner" ON attribution ("atb_owner_id");



-- ************* AUTHTOKEN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_autSelector";
-- DROP TRIGGER IF EXISTS update_authtoken_modtime ON authtoken;
DROP TABLE IF EXISTS authtoken CASCADE;


CREATE TABLE authtoken
(
  "aut_id" serial NOT NULL PRIMARY KEY,
  "aut_selector" varchar(32) NOT NULL UNIQUE,
  "aut_hashed_validator" varchar(64) NULL,
  "atg_expire" timestamp default CURRENT_TIMESTAMP,
  "modified" timestamp default CURRENT_TIMESTAMP,
  "aut_user_id" int NOT NULL
)
WITH (
  OIDS=FALSE
);


CREATE TRIGGER update_authtoken_modtime BEFORE UPDATE ON authtoken FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE authtoken OWNER TO postgres;
COMMENT ON TABLE authtoken IS 'Contains a record for each authtoken.';
COMMENT ON COLUMN authtoken."aut_id" IS 'Uniquely identifies a authtoken object.';
COMMENT ON COLUMN authtoken."aut_selector" IS 'ISO Language code : label pairs for this authtoken.';
COMMENT ON COLUMN authtoken."aut_hashed_validator" IS 'hash of validator from cookie used to verify this authtoken.';
COMMENT ON COLUMN authtoken."atg_expire" IS 'expiration of token';
COMMENT ON COLUMN authtoken."aut_user_id" IS 'Link to user usergroup.';


-- Indexes:

CREATE INDEX "fki_autSelector" ON authtoken ("aut_selector");



-- ************* attributiongroup TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_atgType";
DROP INDEX IF EXISTS "fki_atgOwner";
-- DROP TRIGGER IF EXISTS update_attributiongroup_modtime ON attributiongroup;
DROP TABLE IF EXISTS attributiongroup CASCADE;

CREATE TABLE attributiongroup
(
  "atg_id" serial NOT NULL PRIMARY KEY,
  "atg_name" text,
  "atg_type_id" int NOT NULL DEFAULT 1,
  "atg_realname" text,
  "atg_date_created" date NOT NULL DEFAULT CURRENT_DATE,
  "atg_description" text NULL,
  "atg_member_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "atg_admin_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "atg_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "atg_owner_id" int NULL DEFAULT 2,
  "atg_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "atg_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "atg_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_attributiongroup_modtime BEFORE UPDATE ON attributiongroup FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE attributiongroup OWNER TO postgres;

COMMENT ON TABLE attributiongroup IS 'Contains a record for each attribution group entry.';
COMMENT ON COLUMN attributiongroup."atg_id" IS 'Uniquely identifies particular attribution group entity.';
COMMENT ON COLUMN attributiongroup."atg_name" IS 'Name of attribution group.';
COMMENT ON COLUMN attributiongroup."atg_type_id" IS 'Type of attribution group.';
COMMENT ON COLUMN attributiongroup."atg_realname" IS 'Actual name of attribution group.';
COMMENT ON COLUMN attributiongroup."atg_date_created" IS 'Date attribution group was created.';
COMMENT ON COLUMN attributiongroup."atg_member_ids" IS 'Links to attribution group entities that belong to this attribution group.';
COMMENT ON COLUMN attributiongroup."atg_admin_ids" IS 'Links to attribution group entities that can admin this attribution group.';
COMMENT ON COLUMN attributiongroup."atg_annotation_ids" IS 'Links to commentary on this attribution group.';
COMMENT ON COLUMN attributiongroup."atg_attribution_ids" IS 'Link to entity that created this attribution group.';
COMMENT ON COLUMN attributiongroup."atg_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN attributiongroup."atg_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_atgType" ON attributiongroup ("atg_type_id");
CREATE INDEX "fki_atgOwner" ON attributiongroup ("atg_owner_id");



-- ************* BIBLIOGRAPHY TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_bibOwner";
-- DROP TRIGGER IF EXISTS update_bibliography_modtime ON bibliography;
DROP TABLE IF EXISTS bibliography CASCADE;

CREATE TABLE bibliography
(
  "bib_id" serial NOT NULL PRIMARY KEY,
  "bib_name" text NULL,
  "bib_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "bib_owner_id" int NULL DEFAULT 2,
  "bib_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "bib_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "bib_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_bibliography_modtime BEFORE UPDATE ON bibliography FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE bibliography OWNER TO postgres;

COMMENT ON TABLE bibliography IS 'Contains a record for each Bibliographic entry.';
COMMENT ON COLUMN bibliography."bib_id" IS 'Uniquely identifies particular bibliographic entity.';
COMMENT ON COLUMN bibliography."bib_name" IS 'Title of Bibliography entry.';
COMMENT ON COLUMN bibliography."bib_annotation_ids" IS 'Links to commentary on this bibliography.';
COMMENT ON COLUMN bibliography."bib_attribution_ids" IS 'Link to entity that created this bibliography.';
COMMENT ON COLUMN bibliography."bib_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN bibliography."bib_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_bibOwner" ON bibliography ("bib_owner_id");



-- ************* CACHE TABLE

-- DROP TRIGGER IF EXISTS update_bibliography_modtime ON bibliography;
DROP TABLE IF EXISTS jsoncache CASCADE;

CREATE TABLE jsoncache
(
  "jsc_id" serial NOT NULL PRIMARY KEY,
  "jsc_label" text NULL,
  "jsc_type_id" int NULL,
  "jsc_json_string" text NULL,
  "jsc_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "jsc_owner_id" int NULL DEFAULT 2,
  "jsc_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "jsc_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "jsc_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_jsoncache_modtime BEFORE UPDATE OF jsc_json_string ON jsoncache FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE jsoncache OWNER TO postgres;

COMMENT ON TABLE jsoncache IS 'Contains a record storing a json string.';
COMMENT ON COLUMN jsoncache."jsc_id" IS 'Uniquely identifies particular json string.';
COMMENT ON COLUMN jsoncache."jsc_label" IS 'A label that identifies particular json string.';
COMMENT ON COLUMN jsoncache."jsc_type_id" IS 'Term id for the type of this particular json string.';
COMMENT ON COLUMN jsoncache."jsc_json_string" IS 'JSON string.';
COMMENT ON COLUMN jsoncache."jsc_annotation_ids" IS 'Links to commentary on this cache.';
COMMENT ON COLUMN jsoncache."jsc_attribution_ids" IS 'Link to entity that contains attribution information for this cache.';
COMMENT ON COLUMN jsoncache."jsc_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN jsoncache."jsc_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:



-- ************* IMAGE TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_imgType";
DROP INDEX IF EXISTS "fki_imgOwner";
-- DROP TRIGGER IF EXISTS update_image_modtime ON image;
DROP TABLE IF EXISTS image CASCADE;

CREATE TABLE image
(
  "img_id" serial NOT NULL PRIMARY KEY,
  "img_title" text NULL,
  "img_type_id" int NOT NULL DEFAULT 1,
  "img_url" text,
  "img_image_pos" polygon[] NULL,
  "img_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "img_owner_id" int NULL DEFAULT 2,
  "img_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "img_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "img_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_image_modtime BEFORE UPDATE ON image FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE image OWNER TO postgres;

COMMENT ON TABLE image IS 'Contains records for each inscription image, those that are identified as Baseline images and those that are identified as EditionReference images.';
COMMENT ON COLUMN image."img_title" IS 'Title of Image.';
COMMENT ON COLUMN image."img_type_id" IS 'Type of image.';
COMMENT ON COLUMN image."img_url" IS 'URL that returns full image.';
COMMENT ON COLUMN image."img_image_pos" IS 'Polygons for cropping image.';
COMMENT ON COLUMN image."img_annotation_ids" IS 'Links to commentary on this image.';
COMMENT ON COLUMN image."img_attribution_ids" IS 'Link to entity that contains attribution information for this image.';
COMMENT ON COLUMN image."img_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN image."img_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_imgType" ON image ("img_type_id");
CREATE INDEX "fki_imgOwner" ON image ("img_owner_id");



-- ************* PROPERNOUN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_prnType";
DROP INDEX IF EXISTS "fki_prnOwner";
-- DROP TRIGGER IF EXISTS update_propernoun_modtime ON propernoun;
DROP TABLE IF EXISTS propernoun CASCADE;


CREATE TABLE propernoun
(
  "prn_id" serial NOT NULL PRIMARY KEY,
  "prn_labels" text NOT NULL DEFAULT 'Need Labels',
  "prn_type_id" int NULL,
  "prn_evidences" text NULL,
  "prn_description" text NULL,
  "prn_url" text NULL,
  "prn_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "prn_owner_id" int NULL DEFAULT 2,
  "prn_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "prn_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "prn_scratch" text NULL
)
WITH (
  OIDS=FALSE
);


CREATE TRIGGER update_propernoun_modtime BEFORE UPDATE ON propernoun FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE propernoun OWNER TO postgres;
COMMENT ON TABLE propernoun IS 'Contains a record for each propernoun.';
COMMENT ON COLUMN propernoun."prn_id" IS 'Uniquely identifies a propernoun object.';
COMMENT ON COLUMN propernoun."prn_labels" IS 'ISO Language code : label pairs for this propernoun.';
COMMENT ON COLUMN propernoun."prn_type_id" IS 'Link to propernoun identifying the type of this propernoun.';
COMMENT ON COLUMN propernoun."prn_description" IS 'Description of the propernoun.';
COMMENT ON COLUMN propernoun."prn_evidences" IS 'Array of semantic pairs identifying the evidence';
COMMENT ON COLUMN propernoun."prn_url" IS 'URL to definition of this propernoun.';
COMMENT ON COLUMN propernoun."prn_annotation_ids" IS 'Links to commentary on this propernoun.';
COMMENT ON COLUMN propernoun."prn_attribution_ids" IS 'Links to attributions for this propernoun.';
COMMENT ON COLUMN propernoun."prn_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN propernoun."prn_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_prnType" ON propernoun ("prn_type_id");
CREATE INDEX "fki_prnOwner" ON propernoun ("prn_owner_id");



-- ************* TERM TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_trmParent";
DROP INDEX IF EXISTS "fki_trmType";
DROP INDEX IF EXISTS "fki_trmOwner";
-- DROP TRIGGER IF EXISTS update_term_modtime ON term;
DROP TABLE IF EXISTS term CASCADE;


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

CREATE TRIGGER update_term_modtime BEFORE UPDATE ON term FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE term OWNER TO postgres;

COMMENT ON TABLE term IS 'Contains a record for each term.';
COMMENT ON COLUMN term."trm_id" IS 'Uniquely identifies a term object.';
COMMENT ON COLUMN term."trm_labels" IS 'ISO Language code : label pairs for this term.';
COMMENT ON COLUMN term."trm_list_ids" IS 'Links to terms contained in this termlist.';
COMMENT ON COLUMN term."trm_parent_id" IS 'Link to parent term.';
COMMENT ON COLUMN term."trm_type_id" IS 'Link to term identifying the type of this term.';
COMMENT ON COLUMN term."trm_description" IS 'Description of the term.';
COMMENT ON COLUMN term."trm_code" IS 'Code for this term.';
COMMENT ON COLUMN term."trm_url" IS 'URL to definition of this term.';
COMMENT ON COLUMN term."trm_annotation_ids" IS 'Links to commentary on this term.';
COMMENT ON COLUMN term."trm_attribution_ids" IS 'Links to attributions for this term.';
COMMENT ON COLUMN term."trm_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN term."trm_visibility_ids" IS 'Links to usergroups that can view.';


-- Index:

CREATE INDEX "fki_trmParent" ON term ("trm_parent_id");
CREATE INDEX "fki_trmType" ON term ("trm_type_id");
CREATE INDEX "fki_trmOwner" ON term ("trm_owner_id");



-- ************* usergroup TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_ugrType";
-- DROP TRIGGER IF EXISTS update_usergroup_modtime ON usergroup;
DROP TABLE IF EXISTS usergroup CASCADE;

CREATE TABLE usergroup
(
  "ugr_id" serial NOT NULL PRIMARY KEY,
  "ugr_name" text NOT NULL UNIQUE,
  "ugr_type_id" int NOT NULL DEFAULT 335, -- todo correct default set immutable in term table
  "ugr_given_name" text,
  "ugr_family_name" text,
  "ugr_description" text NULL,
  "ugr_password" text,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "ugr_member_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "ugr_admin_ids" int[] NULL, -- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "ugr_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_usergroup_modtime BEFORE UPDATE ON usergroup FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE usergroup OWNER TO postgres;


-- Index:

CREATE INDEX "fki_ugrType" ON usergroup ("ugr_type_id");



-- *************************************UPPER MODEL****************************************





-- ************* COLLECTION TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_colOwner";
-- DROP TRIGGER IF EXISTS update_collection_modtime ON collection;
DROP TABLE IF EXISTS collection CASCADE;

CREATE TABLE collection
(
  "col_id" serial NOT NULL PRIMARY KEY,
  "col_title" text NOT NULL DEFAULT 'Need Title',
  "col_location_refs" text[] NULL,
  "col_description" text NOT NULL DEFAULT 'Need Description',
  "col_item_part_fragment_ids" varchar(31)[] NULL,
  "col_exclude_part_fragment_ids" varchar(31)[] NULL,
  "col_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "col_owner_id" int NULL DEFAULT 2,
  "col_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "col_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "col_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_collection_modtime BEFORE UPDATE ON collection FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE collection OWNER TO postgres;

COMMENT ON TABLE collection IS 'Contains a record for each Collection.';
COMMENT ON COLUMN collection."col_id" IS 'Uniquely identifies particular collection.';
COMMENT ON COLUMN collection."col_title" IS 'Title of COLLECTION in the current catalog or historical name.';
COMMENT ON COLUMN collection."col_description" IS 'Description which encompasses all ITEMS in this COLLECTION.';
COMMENT ON COLUMN collection."col_location_refs" IS 'location id-Reference number pairs for this COLLECTION.';
COMMENT ON COLUMN collection."col_item_part_fragment_ids" IS 'Links to objects in this collection.';
COMMENT ON COLUMN collection."col_exclude_part_fragment_ids" IS 'Links to exclude objects for this collection.';
COMMENT ON COLUMN collection."col_annotation_ids" IS 'Links to commentary on this collection.';
COMMENT ON COLUMN collection."col_attribution_ids" IS 'Link to entity that created this collection.';
COMMENT ON COLUMN collection."col_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN collection."col_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_colOwner" ON collection ("col_owner_id");



-- ************* FRAGMENT TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_frgPart";
DROP INDEX IF EXISTS "fki_frgRestState";
DROP INDEX IF EXISTS "fki_frgOwner";
-- DROP TRIGGER IF EXISTS update_fragment_modtime ON fragment;
DROP TABLE IF EXISTS fragment CASCADE;

CREATE TABLE fragment
(
  "frg_id" serial NOT NULL  PRIMARY KEY,
  "frg_label" text NULL,
  "frg_description" text NULL,
  "frg_measure" text NULL,
  "frg_restore_state_id" int NULL,
  "frg_location_refs" text[] NULL,
  "frg_part_id" int NULL,
  "frg_material_context_ids" int[] NULL,
  "frg_image_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "frg_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "frg_owner_id" int NULL DEFAULT 2,
  "frg_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "frg_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "frg_scratch" text NULL
) WITH (
  OIDS=FALSE
);
CREATE TRIGGER update_fragment_modtime BEFORE UPDATE ON fragment FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE fragment OWNER TO postgres;
COMMENT ON TABLE fragment IS 'Contains a record for each fragment of a part.';
COMMENT ON COLUMN fragment."frg_id" IS 'Uniquely identifies particular fragment.';
COMMENT ON COLUMN fragment."frg_part_id" IS 'Link to part this fragment belongs to.';
COMMENT ON COLUMN fragment."frg_description" IS 'Free text description of FRAGMENT.';
COMMENT ON COLUMN fragment."frg_location_refs" IS 'Location/Reference pairs, 1st is current.';
COMMENT ON COLUMN fragment."frg_label" IS 'Part Unique Label identifying particular fragment.';
COMMENT ON COLUMN fragment."frg_restore_state_id" IS 'Link to term defining the restoration state of this fragment.';
COMMENT ON COLUMN fragment."frg_measure" IS 'Measurement in structured format.';
COMMENT ON COLUMN fragment."frg_image_ids" IS 'Links to images of this fragment.';
COMMENT ON COLUMN fragment."frg_annotation_ids" IS 'Links to commentary on this fragment.';
COMMENT ON COLUMN fragment."frg_attribution_ids" IS 'Link to a ATTRIBUTION event.';
COMMENT ON COLUMN fragment."frg_material_context_ids" IS 'Links to Material Context record.';
COMMENT ON COLUMN fragment."frg_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN fragment."frg_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_frgPart" ON fragment ("frg_part_id");
CREATE INDEX "fki_frgRestState" ON fragment ("frg_restore_state_id");
CREATE INDEX "fki_frgOwner" ON fragment ("frg_owner_id");



-- ************* ITEM TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_itmType";
DROP INDEX IF EXISTS "fki_itmShape";
DROP INDEX IF EXISTS "fki_itmOwner";
-- DROP TRIGGER IF EXISTS update_item_modtime ON item;
DROP TABLE IF EXISTS item CASCADE;

CREATE TABLE item
(
  "itm_id" serial NOT NULL PRIMARY KEY,
  "itm_title" text NOT NULL DEFAULT 'Need Title',
  "itm_description" text NULL,
  "itm_idno" text NULL,
  "itm_type_id" int NULL,
  "itm_shape_id" int NULL,
  "itm_measure" text NULL,
  "itm_image_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "itm_owner_id" int NULL DEFAULT 2,
  "itm_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "itm_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "itm_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_item_modtime BEFORE UPDATE ON item FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE item OWNER TO postgres;

COMMENT ON TABLE item IS 'Contains a record for each item.';
COMMENT ON COLUMN item."itm_id" IS 'Uniquely identifies particular item.';
COMMENT ON COLUMN item."itm_title" IS 'Title of ITEM in the current catalog.';
COMMENT ON COLUMN item."itm_description" IS 'Free text description of item.';
COMMENT ON COLUMN item."itm_idno" IS 'Free text identifier/reference number of item.';
COMMENT ON COLUMN item."itm_type_id" IS 'Structured artefact typology.';
COMMENT ON COLUMN item."itm_measure" IS 'Measurement in structured format.';
COMMENT ON COLUMN item."itm_shape_id" IS 'Link to term defining the shape of this item.';
COMMENT ON COLUMN item."itm_image_ids" IS 'Links to images of this item.';
COMMENT ON COLUMN item."itm_annotation_ids" IS 'Links to commentary on this item.';
COMMENT ON COLUMN item."itm_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN item."itm_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_itmType" ON item ("itm_type_id");
CREATE INDEX "fki_itmShape" ON item ("itm_shape_id");
CREATE INDEX "fki_itmOwner" ON item ("itm_owner_id");


-- ************* MATERIALCONTEXT TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_mcxOwner";
-- DROP TRIGGER IF EXISTS update_materialcontext_modtime ON materialcontext;
DROP TABLE IF EXISTS materialcontext CASCADE;


CREATE TABLE materialcontext
(
  "mcx_id" serial NOT NULL PRIMARY KEY,
  "mcx_arch_context" text NULL,
  "mcx_find_status" text NULL,
  "mcx_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "mcx_owner_id" int NULL DEFAULT 2,
  "mcx_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax upmaterialcontext foo set a = a || newInt
  "mcx_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax upmaterialcontext foo set a = a || newInt
  "mcx_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_materialcontext_modtime BEFORE UPDATE ON materialcontext FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE materialcontext OWNER TO postgres;

COMMENT ON TABLE materialcontext IS 'Contains a record for each materialcontext.';
COMMENT ON COLUMN materialcontext."mcx_id" IS 'Uniquely identifies a materialcontext object.';
COMMENT ON COLUMN materialcontext."mcx_arch_context" IS 'Text identifying the archaeological context.';
COMMENT ON COLUMN materialcontext."mcx_find_status" IS 'Term id:Attribution is pair defining the find status.';
COMMENT ON COLUMN materialcontext."mcx_annotation_ids" IS 'Links to commentary on this materialcontext.';
COMMENT ON COLUMN materialcontext."mcx_attribution_ids" IS 'Links to attributions for this materialcontext.';
COMMENT ON COLUMN materialcontext."mcx_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN materialcontext."mcx_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_mcxOwner" ON materialcontext ("mcx_owner_id");



-- ************* PART TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_prtType";
DROP INDEX IF EXISTS "fki_prtShape";
DROP INDEX IF EXISTS "fki_prtItem";
DROP INDEX IF EXISTS "fki_prtOwner";
-- DROP TRIGGER IF EXISTS update_part_modtime ON part;
DROP TABLE IF EXISTS part CASCADE;

CREATE TABLE part
(
  "prt_id" serial NOT NULL PRIMARY KEY,
  "prt_label" text NULL,
  "prt_description" text NULL,
  "prt_type_id" int NULL,
  "prt_shape_id" int NULL,
  "prt_mediums" text[] NULL,
  "prt_measure" text NULL,
  "prt_manufacture_id" int NULL,
  "prt_sequence" int NULL,
  "prt_item_id" int NULL,
  "prt_image_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "prt_owner_id" int NULL DEFAULT 2,
  "prt_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "prt_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "prt_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_part_modtime BEFORE UPDATE ON part FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE part OWNER TO postgres;

COMMENT ON TABLE part IS 'Contains a record for each part of an item.';
COMMENT ON COLUMN part."prt_id" IS 'Uniquely identifies particular part.';
COMMENT ON COLUMN part."prt_type_id" IS 'Structured artefact typology.';
COMMENT ON COLUMN part."prt_label" IS 'Item Unique Label identifying particular part.';
COMMENT ON COLUMN part."prt_description" IS 'Free text description of this part.';
COMMENT ON COLUMN part."prt_sequence" IS 'Numbered order of a PART where there is a physical sequence of parts e.g numbered leaves in a folio.';
COMMENT ON COLUMN part."prt_shape_id" IS 'Shape of part.';
COMMENT ON COLUMN part."prt_mediums" IS 'Material from which part is constructed or composed.';
COMMENT ON COLUMN part."prt_manufacture_id" IS 'Link to term defining the manufacturing technique used for this part.';
COMMENT ON COLUMN part."prt_measure" IS 'Measurement in structured format.';
COMMENT ON COLUMN part."prt_item_id" IS 'Link to item this part belongs to.';
COMMENT ON COLUMN part."prt_image_ids" IS 'Links to images of this part.';
COMMENT ON COLUMN part."prt_annotation_ids" IS 'Links to commentary on this part.';
COMMENT ON COLUMN part."prt_owner_id" IS 'Link to the usergroup that owns this entity.';
COMMENT ON COLUMN part."prt_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_prtType" ON part ("prt_type_id");
CREATE INDEX "fki_prtShape" ON part ("prt_shape_id");
CREATE INDEX "fki_prtItem" ON part ("prt_item_id");
CREATE INDEX "fki_prtOwner" ON part ("prt_owner_id");



-- ************* SURFACE TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_srfFragment";
DROP INDEX IF EXISTS "fki_srfText";
-- DROP TRIGGER IF EXISTS update_surface_modtime ON surface;
DROP TABLE IF EXISTS surface CASCADE;

CREATE TABLE surface
(
  "srf_id" serial NOT NULL PRIMARY KEY,
  "srf_description" text NULL,
  "srf_label" text NULL,
  "srf_number" int NOT NULL DEFAULT 1,
  "srf_layer_number" int NOT NULL DEFAULT 1,
  "srf_scripts" text[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "srf_text_ids" int[] NULL,
  "srf_reconst_surface_id" int,
  "srf_fragment_id" int NULL,
  "srf_image_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "srf_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "srf_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "srf_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_surface_modtime BEFORE UPDATE ON surface FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE surface OWNER TO postgres;

COMMENT ON TABLE surface IS 'Contains a record for each surface of a fragment.';
COMMENT ON COLUMN surface."srf_id" IS 'Uniquely identifies particular surface.';
COMMENT ON COLUMN surface."srf_fragment_id" IS 'Link to fragment this surface lies on.';
COMMENT ON COLUMN surface."srf_label" IS 'Free text label identifying this surface.';
COMMENT ON COLUMN surface."srf_number" IS 'Fragment Unique Number identifying particular surface.';
COMMENT ON COLUMN surface."srf_description" IS 'Free text description of surface.';
COMMENT ON COLUMN surface."srf_layer_number" IS 'Fragment Unique Number identifying particular layer of the surface.';
COMMENT ON COLUMN surface."srf_scripts" IS 'SCRIPTS used for TEXT on the surface.';
COMMENT ON COLUMN surface."srf_text_ids" IS 'Links to a text entities.';
COMMENT ON COLUMN surface."srf_image_ids" IS 'Links to images of this surface.';
COMMENT ON COLUMN surface."srf_annotation_ids" IS 'Links to commentary on this surface.';
COMMENT ON COLUMN surface."srf_reconst_surface_id" IS 'Link to reconstructed surface entity.';
COMMENT ON COLUMN surface."srf_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_srfFragment" ON surface ("srf_fragment_id");
CREATE INDEX "fki_srfText" ON surface ("srf_text_ids");



-- **************************************Core Model****************************************************



-- ************* BASELINE TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_blnImgage";
DROP INDEX IF EXISTS "fki_blnType";
DROP INDEX IF EXISTS "fki_blnSurface";
DROP INDEX IF EXISTS "fki_blnOwner";
-- DROP TRIGGER IF EXISTS update_baseline_modtime ON baseline;
DROP TABLE IF EXISTS baseline CASCADE;

CREATE TABLE baseline
(
  "bln_id" serial NOT NULL PRIMARY KEY,
  "bln_type_id" int NOT NULL DEFAULT 1,
  "bln_image_id" int NULL,
  "bln_surface_id" int NULL,
  "bln_image_position" polygon[] NULL,
  "bln_transcription" text NULL,
  "bln_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "bln_owner_id" int NULL DEFAULT 2,
  "bln_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "bln_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "bln_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_baseline_modtime BEFORE UPDATE ON baseline FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE baseline OWNER TO postgres;

COMMENT ON TABLE baseline IS 'Contains a record for each Image record identified as being a baseline.  May be one per inscription, one per line or one per sequence of lines encompassed by an image. Where no BaseLine image exists record contains a reported transcription of the inscription.';
COMMENT ON COLUMN baseline."bln_id" IS 'Uniquely identifies particular baseline.';
COMMENT ON COLUMN baseline."bln_image_id" IS 'Link to the image record for this baseline.';
COMMENT ON COLUMN baseline."bln_image_position" IS 'polygon defining the boundary of the image record for this baseline.';
COMMENT ON COLUMN baseline."bln_transcription" IS 'This transcription is then used as the baseline for segmentation.';
COMMENT ON COLUMN baseline."bln_type_id" IS 'Link to the term indicating the type of this baseline.';
COMMENT ON COLUMN baseline."bln_surface_id" IS 'Link to surface this baseline refers to in part or in whole.';
COMMENT ON COLUMN baseline."bln_annotation_ids" IS 'Links to commentary on this baseline.';
COMMENT ON COLUMN baseline."bln_attribution_ids" IS 'Links to attributions for this baseline.';
COMMENT ON COLUMN baseline."bln_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN baseline."bln_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_blnImgage" ON baseline ("bln_image_id");
CREATE INDEX "fki_blnType" ON baseline ("bln_type_id");
CREATE INDEX "fki_blnSurface" ON baseline ("bln_surface_id");
CREATE INDEX "fki_blnOwner" ON baseline ("bln_owner_id");



-- ************* COMPOUND TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_cmpType";
DROP INDEX IF EXISTS "fki_cmpLemma";
DROP INDEX IF EXISTS "fki_cmpOwner";
-- DROP TRIGGER IF EXISTS update_compound_modtime ON compound;
DROP TABLE IF EXISTS compound CASCADE;


CREATE TABLE compound
(
  "cmp_id" serial NOT NULL PRIMARY KEY,
  "cmp_value" text NULL,
  "cmp_transcription" text NULL,
  "cmp_component_ids" varchar(31)[],
  "cmp_case_id" int NULL,
  "cmp_class_id" int NULL,
  "cmp_type_id" int NULL,
  "cmp_sort_code" text NULL,
  "cmp_sort_code2" text NULL,
  "cmp_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "cmp_owner_id" int NULL DEFAULT 2,
  "cmp_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "cmp_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "cmp_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_compound_modtime BEFORE UPDATE ON compound FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE compound OWNER TO postgres;

COMMENT ON TABLE compound IS 'Contains a record for each compound.';
COMMENT ON COLUMN compound."cmp_id" IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';
COMMENT ON COLUMN compound."cmp_value" IS 'Display value (calc) of the compound.';
COMMENT ON COLUMN compound."cmp_transcription" IS 'transcription value (calc) of the compound.';
COMMENT ON COLUMN compound."cmp_component_ids" IS 'Links to the tokens and/or compounds for this compound';
COMMENT ON COLUMN compound."cmp_case_id" IS 'Internal component case of compound';
COMMENT ON COLUMN compound."cmp_class_id" IS 'Internal component classification.';
COMMENT ON COLUMN compound."cmp_type_id" IS 'Type of compound.';
COMMENT ON COLUMN compound."cmp_sort_code" IS 'Primary Code used to order compounds.';
COMMENT ON COLUMN compound."cmp_sort_code2" IS 'Secondary Code used to order compounds.';
COMMENT ON COLUMN compound."cmp_annotation_ids" IS 'Links to commentary on this compound.';
COMMENT ON COLUMN compound."cmp_attribution_ids" IS 'Links to attributions for this compound.';
COMMENT ON COLUMN compound."cmp_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN compound."cmp_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_cmpType" ON compound ("cmp_type_id");
CREATE INDEX "fki_cmpOwner" ON compound ("cmp_owner_id");



-- ************* GRAPHEME TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_graType";
DROP INDEX IF EXISTS "fki_graOwner";
-- DROP TRIGGER IF EXISTS update_grapheme_modtime ON grapheme;
DROP TABLE IF EXISTS grapheme CASCADE;

CREATE TABLE grapheme
(
  "gra_id" serial NOT NULL PRIMARY KEY,
  "gra_grapheme" varchar(15) NOT NULL DEFAULT 'Need Grapheme',
  "gra_uppercase" varchar(15) NULL,
  "gra_type_id" int NULL,
  "gra_text_critical_mark" text NULL,
  "gra_alt" varchar(31) NULL,
  "gra_emmendation" varchar(63) NULL,
  "gra_decomposition" varchar(63) NULL,
  "gra_sort_code" varchar(63) NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "gra_owner_id" int NULL DEFAULT 2,
  "gra_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "gra_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "gra_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_grapheme_modtime BEFORE UPDATE ON grapheme FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE grapheme OWNER TO postgres;

COMMENT ON TABLE grapheme IS 'Contains a record for each syllable cluster for a segment.';
COMMENT ON COLUMN grapheme."gra_id" IS 'Uniquely identifies a particular grapheme.';
COMMENT ON COLUMN grapheme."gra_grapheme" IS 'the grapheme.';
COMMENT ON COLUMN grapheme."gra_uppercase" IS 'the uppercase version of grapheme use to Uppercase display.';
COMMENT ON COLUMN grapheme."gra_type_id" IS 'Indicates the type of grapheme.';
COMMENT ON COLUMN grapheme."gra_text_critical_mark" IS 'Critical Marking used to identify aspects of the script critical to this interpretation.';
COMMENT ON COLUMN grapheme."gra_alt" IS 'Indicates alternative grapheme.';
COMMENT ON COLUMN grapheme."gra_emmendation" IS 'emmendation of grapheme.';
COMMENT ON COLUMN grapheme."gra_decomposition" IS 'decomposition of grapheme on token boundary.';
COMMENT ON COLUMN grapheme."gra_sort_code" IS 'Code used to order grapheme.';
COMMENT ON COLUMN grapheme."gra_annotation_ids" IS 'Links to commentary on this grapheme.';
COMMENT ON COLUMN grapheme."gra_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN grapheme."gra_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_graType" ON grapheme ("gra_type_id");
CREATE INDEX "fki_graOwner" ON grapheme ("gra_owner_id");



-- ************* LEMMA TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_lemType";
DROP INDEX IF EXISTS "fki_lemLang";
DROP INDEX IF EXISTS "fki_lemDecl";
DROP INDEX IF EXISTS "fki_lemGender";
DROP INDEX IF EXISTS "fki_lemPOS";
DROP INDEX IF EXISTS "fki_lemClass";
DROP INDEX IF EXISTS "fki_lemOwner";
-- DROP TRIGGER IF EXISTS update_lemma_modtime ON lemma;
DROP TABLE IF EXISTS lemma CASCADE;


CREATE TABLE lemma
(
  "lem_id" serial NOT NULL PRIMARY KEY,
  "lem_value" text NULL,
  "lem_search" text NULL,
  "lem_translation" text NULL,
  "lem_homographorder" int NULL,
  "lem_type_id" int NULL,
  "lem_certainty" int[] NULL,
  "lem_part_of_speech_id" int NULL,
  "lem_subpart_of_speech_id" int NULL,
  "lem_nominal_gender_id" int NULL,
  "lem_verb_class_id" int NULL,
  "lem_declension_id" int NULL,
  "lem_description" text NULL,
  "lem_catalog_id" int NULL,
  "lem_component_ids" varchar(31)[],
  "lem_sort_code" text NULL,
  "lem_sort_code2" text NULL,
  "lem_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "lem_owner_id" int NULL DEFAULT 2,
  "lem_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "lem_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "lem_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_lemma_modtime BEFORE UPDATE ON lemma FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE lemma OWNER TO postgres;

COMMENT ON TABLE lemma IS 'Contains a record for each lemma.';
COMMENT ON COLUMN lemma."lem_id" IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';
COMMENT ON COLUMN lemma."lem_value" IS 'Display value (calc) of the lemma.';
COMMENT ON COLUMN lemma."lem_search" IS 'search value of this lemma';
COMMENT ON COLUMN lemma."lem_description" IS 'description and/or definition of this lemma';
COMMENT ON COLUMN lemma."lem_catalog_id" IS 'Catalog container for the lemma.';
COMMENT ON COLUMN lemma."lem_component_ids" IS 'Attested forms of inflections of lemma.';
COMMENT ON COLUMN lemma."lem_type_id" IS 'Type of lemma.';
COMMENT ON COLUMN lemma."lem_certainty" IS 'Array of Certainty values for decomposition values for this lemma.';
COMMENT ON COLUMN lemma."lem_homographorder" IS 'homograph differentiator for the lemma.';
COMMENT ON COLUMN lemma."lem_translation" IS 'Modern Language translation of this lemma.';
COMMENT ON COLUMN lemma."lem_declension_id" IS 'Declension';
COMMENT ON COLUMN lemma."lem_nominal_gender_id" IS 'Nominal gender';
COMMENT ON COLUMN lemma."lem_part_of_speech_id" IS 'Word classification for role in speech';
COMMENT ON COLUMN lemma."lem_subpart_of_speech_id" IS 'Word subclassification for role in speech';
COMMENT ON COLUMN lemma."lem_verb_class_id" IS 'Verbal class';
COMMENT ON COLUMN lemma."lem_sort_code" IS 'Primary Code used to order lemmata.';
COMMENT ON COLUMN lemma."lem_sort_code2" IS 'Secondary Code used to order lemmata.';
COMMENT ON COLUMN lemma."lem_annotation_ids" IS 'Links to commentary on this lemma.';
COMMENT ON COLUMN lemma."lem_attribution_ids" IS 'Links to attributions for this lemma.';
COMMENT ON COLUMN lemma."lem_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN lemma."lem_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_lemType" ON lemma ("lem_type_id");
CREATE INDEX "fki_lemDecl" ON lemma ("lem_declension_id");
CREATE INDEX "fki_lemGender" ON lemma ("lem_nominal_gender_id");
CREATE INDEX "fki_lemPOS" ON lemma ("lem_part_of_speech_id");
CREATE INDEX "fki_lemClass" ON lemma ("lem_verb_class_id");
CREATE INDEX "fki_lemOwner" ON lemma ("lem_owner_id");



-- ************* LINE TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_linOwner";
-- DROP TRIGGER IF EXISTS update_line_modtime ON line;
DROP TABLE IF EXISTS line CASCADE;

CREATE TABLE line
(
  "lin_id" serial NOT NULL PRIMARY KEY,
  "lin_order" int NULL,
  "lin_mask" text NULL,
  "lin_span_ids" int[] NULL,
  "lin_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "lin_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "lin_owner_id" int NULL DEFAULT 2,
  "lin_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "lin_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_line_modtime BEFORE UPDATE ON line FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE line OWNER TO postgres;

COMMENT ON TABLE line IS 'Contains a record for each line.';
COMMENT ON COLUMN line."lin_id" IS 'Uniquely identifies a particular linester.';
COMMENT ON COLUMN line."lin_order" IS 'Order of line.';
COMMENT ON COLUMN line."lin_mask" IS 'Display number of line.';
COMMENT ON COLUMN line."lin_span_ids" IS 'Array of ids for the span records for this line.';
COMMENT ON COLUMN line."lin_annotation_ids" IS 'Links to commentary on this line.';
COMMENT ON COLUMN line."lin_attribution_ids" IS 'Links to attributions for this line.';
COMMENT ON COLUMN line."lin_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN line."lin_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_linOwner" ON line ("lin_owner_id");



-- ************* RUN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_runText";
DROP INDEX IF EXISTS "fki_runBaseline";
DROP INDEX IF EXISTS "fki_runOwner";
-- DROP TRIGGER IF EXISTS update_run_modtime ON run;
DROP TABLE IF EXISTS run CASCADE;

CREATE TABLE run
(
  "run_id" serial NOT NULL PRIMARY KEY,
  "run_scribe_id" int NULL,
  "run_text_id" int NULL,
  "run_baseline_id" int NULL,
  "run_image_pos" polygon[] NULL,
  "run_script_id" int NULL,
  "run_writing_id" int NULL,
  "run_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "run_owner_id" int NULL DEFAULT 2,
  "run_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "run_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "run_scratch" text NULL
)
WITH (
  OIDS=FALSE
);
CREATE TRIGGER update_run_modtime BEFORE UPDATE ON run FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE run OWNER TO postgres;
COMMENT ON TABLE run IS 'Contains a record for each run.';
COMMENT ON COLUMN run."run_id" IS 'Uniquely identifies a particular run.';
COMMENT ON COLUMN run."run_scribe_id" IS 'Uniquely identifies the scribe for this particular run.';
COMMENT ON COLUMN run."run_script_id" IS 'Term ID of the script used for TEXT in the run.'; -- ?? should this be multiple
COMMENT ON COLUMN run."run_writing_id" IS 'Link to term defining the writing technique used for this run.';
COMMENT ON COLUMN run."run_text_id" IS 'Link to text entity of this run.';
COMMENT ON COLUMN run."run_baseline_id" IS 'Link to the baseline record for this run.';
COMMENT ON COLUMN run."run_image_pos" IS 'Polygons defining the bounds for the scribe run for the link baseline.';
COMMENT ON COLUMN run."run_annotation_ids" IS 'Links to commentary on this run.';
COMMENT ON COLUMN run."run_attribution_ids" IS 'Links to attributions for this run.';
COMMENT ON COLUMN run."run_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN run."run_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_runText" ON run ("run_text_id");
CREATE INDEX "fki_runBaseline" ON run ("run_baseline_id");
CREATE INDEX "fki_runOwner" ON run ("run_owner_id");



-- ************* SEGMENT TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_segClarity";
DROP INDEX IF EXISTS "fki_segOwner";
-- DROP TRIGGER IF EXISTS update_segment_modtime ON segment;
DROP TABLE IF EXISTS segment CASCADE;

CREATE TABLE segment
(
  "seg_id" serial NOT NULL PRIMARY KEY,
  "seg_baseline_ids" int[] NULL,
  "seg_image_pos" polygon[] NULL,
  "seg_string_pos" int[][] NULL,
  "seg_rotation" int NULL,
  "seg_layer" int NULL,
  "seg_clarity_id" int NULL,
  "seg_obscurations" text[] NULL,
  "seg_url" text NULL,
  "seg_mapped_seg_ids" int[] NULL,
  "seg_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "seg_owner_id" int NULL DEFAULT 2,
  "seg_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "seg_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "seg_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER set_segment_modtime BEFORE UPDATE ON segment FOR EACH ROW EXECUTE PROCEDURE  set_modified();

ALTER TABLE segment OWNER TO postgres;

COMMENT ON TABLE segment IS 'Contains a record for each agreed and each alternative segment on each Baseline record.';
COMMENT ON COLUMN segment."seg_id" IS 'Uniquely identifies a particular segment on a particular run on a particular line on particular inscription.';
COMMENT ON COLUMN segment."seg_baseline_ids" IS 'Links to the baseline records for this segment.';
COMMENT ON COLUMN segment."seg_image_pos" IS 'Polygon defining the segment bounds for an image baseline.';
COMMENT ON COLUMN segment."seg_string_pos" IS 'Array of character positions defining the segment bounds for transcription baseline.';
COMMENT ON COLUMN segment."seg_layer" IS 'Ordinal which refers to the layering of characters (overwriting).';
COMMENT ON COLUMN segment."seg_rotation" IS 'Degrees of rotation to be applied to the imaged for this segments bounding box.';
COMMENT ON COLUMN segment."seg_url" IS 'URL that retrieves cropped segment image or thumb.';
COMMENT ON COLUMN segment."seg_obscurations" IS 'Identifies any surface alterations which obscure the script for this segment.';
COMMENT ON COLUMN segment."seg_clarity_id" IS 'link to term identifying the clarity of the script for this segment.';
COMMENT ON COLUMN segment."seg_mapped_seg_ids" IS 'Links to the mapped segment records for this segment.';
COMMENT ON COLUMN segment."seg_annotation_ids" IS 'Links to commentary on this segment.';
COMMENT ON COLUMN segment."seg_attribution_ids" IS 'Links to attributions for this segment.';
COMMENT ON COLUMN segment."seg_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN segment."seg_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_segClarity" ON segment ("seg_clarity_id");
CREATE INDEX "fki_segOwner" ON segment ("seg_owner_id");



-- ************* SPAN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_spnType";
DROP INDEX IF EXISTS "fki_spnOwner";
DROP INDEX IF EXISTS "fki_spnSegment1";
-- DROP TRIGGER IF EXISTS update_span_modtime ON span;
DROP TABLE IF EXISTS span CASCADE;

CREATE TABLE span
(
  "spn_id" serial NOT NULL PRIMARY KEY,
  "spn_type_id" int NULL,
  "spn_segment_ids" int[] NULL,
  "spn_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "spn_owner_id" int NULL DEFAULT 2,
  "spn_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "spn_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "spn_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_span_modtime BEFORE UPDATE ON span FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE span OWNER TO postgres;

COMMENT ON TABLE span IS 'Contains a record for each span.';
COMMENT ON COLUMN span."spn_id" IS 'Uniquely identifies a particular span.';
COMMENT ON COLUMN span."spn_segment_ids" IS 'Array of ids for the segment records for this span.';
COMMENT ON COLUMN span."spn_type_id" IS 'Link to term defining the type of this span.';
COMMENT ON COLUMN span."spn_annotation_ids" IS 'Links to commentary on this span.';
COMMENT ON COLUMN span."spn_attribution_ids" IS 'Links to attributions for this span.';
COMMENT ON COLUMN span."spn_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN span."spn_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_spnType" ON span ("spn_type_id");
CREATE INDEX "fki_spnOwner" ON span ("spn_owner_id");
CREATE INDEX "fki_spnSegment1" on span ((spn_segment_ids[1]));


-- ************* SYLLABLECLUSTER TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_sclSegment";
DROP INDEX IF EXISTS "fki_sclOwner";
DROP INDEX IF EXISTS "fki_sclGrapheme1";
-- DROP TRIGGER IF EXISTS update_syllablecluster_modtime ON syllablecluster;
DROP TABLE IF EXISTS syllablecluster CASCADE;

CREATE TABLE syllablecluster
(
  "scl_id" serial NOT NULL PRIMARY KEY,
  "scl_segment_id" int NULL,
  "scl_grapheme_ids" int[] NULL,
  "scl_text_critical_mark" text NULL,
  "scl_sort_code" text NULL,
  "scl_sort_code2" text NULL,
  "scl_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "scl_owner_id" int NULL DEFAULT 2,
  "scl_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "scl_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "scl_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_syllablecluster_modtime BEFORE UPDATE ON syllablecluster FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE syllablecluster OWNER TO postgres;

COMMENT ON TABLE syllablecluster IS 'Contains a record for each syllable cluster for a segment.';
COMMENT ON COLUMN syllablecluster."scl_id" IS 'Uniquely identifies a particular syllable cluster.';
COMMENT ON COLUMN syllablecluster."scl_segment_id" IS 'Links to the segment records for this syllable cluster.';
COMMENT ON COLUMN syllablecluster."scl_grapheme_ids" IS 'Array of grapheme ids that make up this syllable cluster.';
COMMENT ON COLUMN syllablecluster."scl_text_critical_mark" IS 'Critical Marking used to identify aspects of the script critical to this interpretation.';
COMMENT ON COLUMN syllablecluster."scl_sort_code" IS 'Primary sort code used to order clusters.';
COMMENT ON COLUMN syllablecluster."scl_sort_code2" IS 'Secondary sort code used to order clusters.';
COMMENT ON COLUMN syllablecluster."scl_annotation_ids" IS 'Links to commentary on this segment.';
COMMENT ON COLUMN syllablecluster."scl_attribution_ids" IS 'Links to attributions for this segment.';
COMMENT ON COLUMN syllablecluster."scl_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN syllablecluster."scl_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_sclSegment" ON syllablecluster ("scl_segment_id");
CREATE INDEX "fki_sclOwner" ON syllablecluster ("scl_owner_id");
CREATE INDEX "fki_sclGrapheme1" ON syllablecluster ((scl_grapheme_ids[1]));


-- ************* TOKEN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_tokOwner";
-- DROP TRIGGER IF EXISTS update_token_modtime ON token;
DROP TABLE IF EXISTS token CASCADE;


CREATE TABLE token
(
  "tok_id" serial NOT NULL PRIMARY KEY,
  "tok_value" text NULL,
  "tok_transcription" text NULL,
  "tok_grapheme_ids" int[] NULL,
  "tok_nom_affix" text NULL,
  "tok_sort_code" text NULL,
  "tok_sort_code2" text NULL,
  "tok_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "tok_owner_id" int NULL DEFAULT 2,
  "tok_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "tok_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "tok_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_token_modtime BEFORE UPDATE ON token FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE token OWNER TO postgres;

COMMENT ON TABLE token IS 'Contains a record for each token.';
COMMENT ON COLUMN token."tok_id" IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';
COMMENT ON COLUMN token."tok_value" IS 'Display value (calc) of the token.';
COMMENT ON COLUMN token."tok_transcription" IS 'transcription value (calc) of the token.';
COMMENT ON COLUMN token."tok_grapheme_ids" IS 'Set of grapheme IDs that make up this token.';
COMMENT ON COLUMN token."tok_nom_affix" IS 'Used where the token is comprised of a transcription symbol only.';
COMMENT ON COLUMN token."tok_sort_code" IS 'Primary Code used to order tokens.';
COMMENT ON COLUMN token."tok_sort_code2" IS 'Secondary Code used to order tokens.';
COMMENT ON COLUMN token."tok_annotation_ids" IS 'Links to commentary on this token.';
COMMENT ON COLUMN token."tok_attribution_ids" IS 'Links to attributions for this token.';
COMMENT ON COLUMN token."tok_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN token."tok_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_tokOwner" ON token ("tok_owner_id");



-- ************* TOKEN TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_infOwner";
-- DROP TRIGGER IF EXISTS update_inflection_modtime ON inflection;
DROP TABLE IF EXISTS inflection CASCADE;


CREATE TABLE inflection
(
  "inf_id" serial NOT NULL PRIMARY KEY,
  "inf_chaya" text NULL,
  "inf_component_ids" text[] NULL,
  "inf_certainty" int[] NULL,
  "inf_case_id" int NULL,
  "inf_nominal_gender_id" int NULL,
  "inf_gram_number_id" int NULL,
  "inf_verb_person_id" int NULL,
  "inf_verb_voice_id" int NULL,
  "inf_verb_tense_id" int NULL,
  "inf_verb_mood_id" int NULL,
  "inf_verb_second_conj_id" int NULL,
  "inf_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "inf_owner_id" int NULL DEFAULT 2,
  "inf_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "inf_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "inf_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_inflection_modtime BEFORE UPDATE ON inflection FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE inflection OWNER TO postgres;

COMMENT ON TABLE inflection IS 'Contains a record for each inflection.';
COMMENT ON COLUMN inflection."inf_id" IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';
COMMENT ON COLUMN inflection."inf_chaya" IS 'Chaya value for the inflection.';
COMMENT ON COLUMN inflection."inf_component_ids" IS 'Set of compound/token IDs that are attested forms this inflection of a lemma.';
COMMENT ON COLUMN inflection."inf_certainty" IS 'Array of certainty values (1 2 3) that map to the decomposition values for this inflection in order';
COMMENT ON COLUMN inflection."inf_case_id" IS 'case of inflection';
COMMENT ON COLUMN inflection."inf_nominal_gender_id" IS 'Nominal gender';
COMMENT ON COLUMN inflection."inf_gram_number_id" IS 'the plurality of this inflection';
COMMENT ON COLUMN inflection."inf_verb_person_id" IS 'Verbal person';
COMMENT ON COLUMN inflection."inf_verb_voice_id" IS 'Active, Middle or Passive verb form.';
COMMENT ON COLUMN inflection."inf_verb_tense_id" IS 'Verbal tense';
COMMENT ON COLUMN inflection."inf_verb_mood_id" IS 'Verbal mood';
COMMENT ON COLUMN inflection."inf_verb_second_conj_id" IS 'Verbal Secondary conjugation.';
COMMENT ON COLUMN inflection."inf_annotation_ids" IS 'Links to commentary on this inflection.';
COMMENT ON COLUMN inflection."inf_attribution_ids" IS 'Links to attributions for this inflection.';
COMMENT ON COLUMN inflection."inf_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN inflection."inf_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_infOwner" ON inflection ("inf_owner_id");



-- *********************************Ancillary Model*************************************



-- ************* CATALOG TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_catType";
DROP INDEX IF EXISTS "fki_catOwner";
-- DROP TRIGGER IF EXISTS update_catalog_modtime ON catalog;
DROP TABLE IF EXISTS catalog CASCADE;


CREATE TABLE catalog
(
  "cat_id" serial NOT NULL PRIMARY KEY,
  "cat_title" text NULL,
  "cat_type_id" int NULL,
  "cat_lang_id" int NULL,
  "cat_description" text NULL,
  "cat_edition_ids" int[] NULL,
  "cat_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "cat_owner_id" int NULL DEFAULT 2,
  "cat_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "cat_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "cat_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_catalog_modtime BEFORE UPDATE ON catalog FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE catalog OWNER TO postgres;

COMMENT ON TABLE catalog IS 'Contains a record for each catalog.';
COMMENT ON COLUMN catalog."cat_id" IS 'Uniquely identifies a catalog object.';
COMMENT ON COLUMN catalog."cat_type_id" IS 'Link to the term from a typology for this catalog.';
COMMENT ON COLUMN catalog."cat_lang_id" IS 'Link to the term identifying the source language of this catalog.';
COMMENT ON COLUMN catalog."cat_title" IS 'Display title of the catalog.';
COMMENT ON COLUMN catalog."cat_description" IS 'Description of the catalog.';
COMMENT ON COLUMN catalog."cat_edition_ids" IS 'Links to edition entities that make up this catalog.';
COMMENT ON COLUMN catalog."cat_annotation_ids" IS 'Links to commentary on this catalog.';
COMMENT ON COLUMN catalog."cat_attribution_ids" IS 'Links to attributions for this catalog.';
COMMENT ON COLUMN catalog."cat_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN catalog."cat_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_catType" ON catalog ("cat_type_id");
CREATE INDEX "fki_catOwner" ON catalog ("cat_owner_id");



-- ************* DATE TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_datOwner";
-- DROP TRIGGER IF EXISTS update_date_modtime ON date;
DROP TABLE IF EXISTS date CASCADE;


CREATE TABLE date
(
  "dat_id" serial NOT NULL PRIMARY KEY,
  "dat_prob_begin_date" int NOT NULL DEFAULT 9999,
  "dat_prob_end_date" int NULL,
  "dat_entity_id" varchar(31) NULL,
  "dat_evidences" text[] NULL,
  "dat_preferred_era_id" int NULL,
  "dat_era_ids" int[] NULL,
  "dat_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "dat_owner_id" int NULL DEFAULT 2,
  "dat_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "dat_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "dat_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_date_modtime BEFORE UPDATE ON date FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE date OWNER TO postgres;
COMMENT ON TABLE date IS 'Contains a record for each date.';
COMMENT ON COLUMN date."dat_id" IS 'Uniquely identifies a date object.';
COMMENT ON COLUMN date."dat_prob_begin_date" IS 'Probable beginning of this date.';
COMMENT ON COLUMN date."dat_prob_end_date" IS 'Probable endding of this date.';
COMMENT ON COLUMN date."dat_evidences" IS 'Term ID : value pairs  of this date.';
COMMENT ON COLUMN date."dat_era_ids" IS 'Ids of the possible eras of this date.';
COMMENT ON COLUMN date."dat_preferred_era_id" IS 'Preferred era of this date.';
COMMENT ON COLUMN date."dat_entity_id" IS 'Global ID for entity linked to date .';
COMMENT ON COLUMN date."dat_annotation_ids" IS 'Links to commentary on this date.';
COMMENT ON COLUMN date."dat_attribution_ids" IS 'Links to attributions for this date.';
COMMENT ON COLUMN date."dat_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN date."dat_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_datOwner" ON date ("dat_owner_id");



-- ************* EDITION TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_ednOwner";
-- DROP TRIGGER IF EXISTS update_edition_modtime ON edition;
DROP TABLE IF EXISTS edition CASCADE;


CREATE TABLE edition
(
  "edn_id" serial NOT NULL PRIMARY KEY,
  "edn_description" text NULL,
  "edn_sequence_ids" int[] NULL,
  "edn_text_id" int NULL,
  "edn_type_id" int NULL,
  "edn_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "edn_owner_id" int NULL DEFAULT 2,
  "edn_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "edn_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "edn_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_edition_modtime BEFORE UPDATE ON edition FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE edition OWNER TO postgres;

COMMENT ON TABLE edition IS 'Contains a record for each edition.';
COMMENT ON COLUMN edition."edn_id" IS 'Uniquely identifies a edition object.';
COMMENT ON COLUMN edition."edn_description" IS 'Description of the edition.';
COMMENT ON COLUMN edition."edn_sequence_ids" IS 'Links to sequence that cover this edition.';
COMMENT ON COLUMN edition."edn_text_id" IS 'Link to text entity for this edition.';
COMMENT ON COLUMN edition."edn_type_id" IS 'Link to the term from a typology for this edition.';
COMMENT ON COLUMN edition."edn_annotation_ids" IS 'Links to commentary on this edition.';
COMMENT ON COLUMN edition."edn_attribution_ids" IS 'Links to attributions for this edition.';
COMMENT ON COLUMN edition."edn_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN edition."edn_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_ednOwner" ON edition ("edn_owner_id");


-- ************* ERA TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_eraOwner";
-- DROP TRIGGER IF EXISTS update_era_modtime ON era;
DROP TABLE IF EXISTS era CASCADE;


CREATE TABLE era
(
  "era_id" serial NOT NULL PRIMARY KEY,
  "era_title" text NOT NULL DEFAULT 'Need Title',
  "era_begin_date" int NOT NULL,
  "era_end_date" int NULL,
  "era_order" int NULL, -- TODO Check whether this can be calculated and not required
  "era_preferred" bool NULL,
  "era_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "era_owner_id" int NULL DEFAULT 2,
  "era_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "era_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "era_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_era_modtime BEFORE UPDATE ON era FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE era OWNER TO postgres;

COMMENT ON TABLE era IS 'Contains a record for each era.';
COMMENT ON COLUMN era."era_id" IS 'Uniquely identifies a era object.';
COMMENT ON COLUMN era."era_title" IS 'Title/Name of the era.';
COMMENT ON COLUMN era."era_order" IS 'Order of this era.';
COMMENT ON COLUMN era."era_preferred" IS 'Preferred status of this era.';
COMMENT ON COLUMN era."era_begin_date" IS 'Begin date for this era.';
COMMENT ON COLUMN era."era_end_date" IS 'End date for this era.';
COMMENT ON COLUMN era."era_annotation_ids" IS 'Links to commentary on this era.';
COMMENT ON COLUMN era."era_attribution_ids" IS 'Links to attributions for this era.';
COMMENT ON COLUMN era."era_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN era."era_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_eraOwner" ON era ("era_owner_id");



-- ************* SEQUENCE TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_seqType";
-- DROP INDEX IF EXISTS "fki_seqTheme";
DROP INDEX IF EXISTS "fki_seqOwner";
-- DROP TRIGGER IF EXISTS update_sequence_modtime ON sequence;
DROP TABLE IF EXISTS sequence CASCADE;


CREATE TABLE sequence
(
  "seq_id" serial NOT NULL PRIMARY KEY,
  "seq_label" text NULL,
  "seq_type_id" int NULL,
  "seq_superscript" text NULL,
  "seq_entity_ids" varchar(30)[] NULL,
  "seq_theme_id" int NULL,
  "seq_ord" int NULL,
  "seq_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "seq_owner_id" int NULL DEFAULT 2,
  "seq_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "seq_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "seq_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_sequence_modtime BEFORE UPDATE ON sequence FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE sequence OWNER TO postgres;

COMMENT ON TABLE sequence IS 'Contains a record for each sequence.';
COMMENT ON COLUMN sequence."seq_id" IS 'Uniquely identifies a sequence object.';
COMMENT ON COLUMN sequence."seq_label" IS 'Display label for this sequence.';
COMMENT ON COLUMN sequence."seq_entity_ids" IS 'Ordered list of Entity GlobalIDs that define this sequence.';
COMMENT ON COLUMN sequence."seq_type_id" IS 'Link to term identifying the type of this sequence.';
COMMENT ON COLUMN sequence."seq_superscript" IS 'superscrit identifier for this sequence.';
COMMENT ON COLUMN sequence."seq_theme_id" IS 'Link to display theme for this sequence.';
COMMENT ON COLUMN sequence."seq_ord" IS 'ordinal number define order is a grouping of sibling sequences.';
COMMENT ON COLUMN sequence."seq_annotation_ids" IS 'Links to commentary on this sequence.';
COMMENT ON COLUMN sequence."seq_attribution_ids" IS 'Links to attributions for this sequence.';
COMMENT ON COLUMN sequence."seq_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN sequence."seq_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_seqType" ON sequence ("seq_type_id");
-- CREATE INDEX "fki_seqTheme" ON sequence ("seq_theme_id");
CREATE INDEX "fki_seqOwner" ON sequence ("seq_owner_id");



-- ************* TEXT TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_txtOwner";
-- DROP TRIGGER IF EXISTS update_text_modtime ON text;
DROP TABLE IF EXISTS text CASCADE;

CREATE TABLE text
(
  "txt_id" serial NOT NULL PRIMARY KEY,
  "txt_ckn" text NOT NULL DEFAULT 'Need CKN',
  "txt_title" text NULL,
  "txt_ref" text NULL,
  "txt_type_ids" int[] NULL,
  "txt_replacement_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "txt_edition_ref_ids" int[] NULL,
  "txt_image_ids" int[] NULL,
  "txt_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "txt_jsoncache_id" int NULL,
  "txt_owner_id" int NULL DEFAULT 2,
  "txt_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "txt_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "txt_scratch" text NULL
) WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_text_modtime BEFORE UPDATE ON text FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE text OWNER TO postgres;

COMMENT ON TABLE text IS 'Contains a record for each text of a part.';
COMMENT ON COLUMN text."txt_id" IS 'Uniquely identifies particular text.';
COMMENT ON COLUMN text."txt_ckn" IS 'Label used in research to identify this text.';
COMMENT ON COLUMN text."txt_title" IS 'More descriptive string to identify this text.';
COMMENT ON COLUMN text."txt_ref" IS 'A short string to identify this text.';
COMMENT ON COLUMN text."txt_replacement_ids" IS 'Forwarding links to text entities.';
COMMENT ON COLUMN text."txt_edition_ref_ids" IS 'attrbID to reference Attribute entity.';
COMMENT ON COLUMN text."txt_type_ids" IS 'Links to terms used to categorise this text.';
COMMENT ON COLUMN text."txt_image_ids" IS 'Links to images of this text.';
COMMENT ON COLUMN text."txt_jsoncache_id" IS 'Link to jsoncache entry for the entities of this text.';
COMMENT ON COLUMN text."txt_annotation_ids" IS 'Links to commentary on this text.';
COMMENT ON COLUMN text."txt_attribution_ids" IS 'Links to attributions for this text.';
COMMENT ON COLUMN text."txt_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN text."txt_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_txtOwner" ON text ("txt_owner_id");



-- ************* TEXTMETADATA TABLE

-- Remove old definitions first then recreate new
DROP INDEX IF EXISTS "fki_tmdText";
DROP INDEX IF EXISTS "fki_tmdOwner";
-- DROP TRIGGER IF EXISTS update_textmetadata_modtime ON textmetadata;
DROP TABLE IF EXISTS textmetadata CASCADE;


CREATE TABLE textmetadata
(
  "tmd_id" serial NOT NULL PRIMARY KEY,
  "tmd_text_id" int NULL,
  "tmd_type_ids" int[] NULL,
  "tmd_reference_ids" int[] NULL,
  "tmd_attribution_ids" int[] NULL,
  "modified" TIMESTAMP default CURRENT_TIMESTAMP,
  "tmd_owner_id" int NULL DEFAULT 2,
  "tmd_annotation_ids" int[] NULL,-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "tmd_visibility_ids" int[] NULL DEFAULT '{6}',-- TODO  handle integrity with triggers and/or utilities  //note: append syntax update foo set a = a || newInt
  "tmd_scratch" text NULL
)
WITH (
  OIDS=FALSE
);

CREATE TRIGGER update_textmetadata_modtime BEFORE UPDATE ON textmetadata FOR EACH ROW EXECUTE PROCEDURE  update_modified();

ALTER TABLE textmetadata OWNER TO postgres;

COMMENT ON TABLE textmetadata IS 'Contains a record for each textMetadata.';
COMMENT ON COLUMN textmetadata."tmd_id" IS 'Uniquely identifies a textMetadata object.';
COMMENT ON COLUMN textmetadata."tmd_text_id" IS 'Link to text entity of this textMetadata.';
COMMENT ON COLUMN textmetadata."tmd_type_ids" IS 'TextType Term IDs defining the type or category for the text of this textMetadata.';
COMMENT ON COLUMN textmetadata."tmd_reference_ids" IS 'attrbIDs to consulted reference Attribute entity.';
COMMENT ON COLUMN textmetadata."tmd_annotation_ids" IS 'Links to commentary on this textMetadata.';
COMMENT ON COLUMN textmetadata."tmd_attribution_ids" IS 'Links to attributions for this textMetadata.';
COMMENT ON COLUMN textmetadata."tmd_owner_id" IS 'Link to owner usergroup.';
COMMENT ON COLUMN textmetadata."tmd_visibility_ids" IS 'Links to usergroups that can view.';


-- Indexes:

CREATE INDEX "fki_tmdText" ON textmetadata ("tmd_text_id");
CREATE INDEX "fki_tmdOwner" ON textmetadata ("tmd_owner_id");



