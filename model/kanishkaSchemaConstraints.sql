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


-- *************************************UTILITY TABLES****************************************

-- ************* ANNOTATION TABLE

ALTER TABLE annotation
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "anoType",
    DROP CONSTRAINT IF EXISTS "anoOwner",
-- then recreate new
    ADD CONSTRAINT "anoType" FOREIGN KEY (ano_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "anoOwner" FOREIGN KEY (ano_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* ATTRIBUTION TABLE

ALTER TABLE attribution
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "atbBiblio",
    DROP CONSTRAINT IF EXISTS "atbGroup",
    DROP CONSTRAINT IF EXISTS "atbOwner",
-- then recreate new
    ADD CONSTRAINT "atbBiblio" FOREIGN KEY (atb_bib_id) REFERENCES bibliography ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "atbGroup" FOREIGN KEY (atb_group_id) REFERENCES attributiongroup ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "atbOwner" FOREIGN KEY (atb_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* ATTRIBUTIONGROUP TABLE

ALTER TABLE attributiongroup
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "atgType",
    DROP CONSTRAINT IF EXISTS "atgOwner",
-- then recreate new
    ADD CONSTRAINT "atgType" FOREIGN KEY (atg_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "atgOwner" FOREIGN KEY (atg_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* BIBLIOGRAPHY TABLE

ALTER TABLE bibliography
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "bibOwner",
-- then recreate new
    ADD CONSTRAINT "bibOwner" FOREIGN KEY (bib_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* IMAGE TABLE

ALTER TABLE image
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "imgType",
    DROP CONSTRAINT IF EXISTS "imgOwner",
-- then recreate new
    ADD CONSTRAINT "imgType" FOREIGN KEY (img_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "imgOwner" FOREIGN KEY (img_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* PROPERNOUN TABLE

ALTER TABLE propernoun
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "prnType",
    DROP CONSTRAINT IF EXISTS "prnOwner",
-- then recreate new
    ADD CONSTRAINT "prnType" FOREIGN KEY (prn_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "prnOwner" FOREIGN KEY (prn_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* TERM TABLE

ALTER TABLE term
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "trmParent",
    DROP CONSTRAINT IF EXISTS "trmType",
    DROP CONSTRAINT IF EXISTS "trmOwner",
-- then recreate new
    ADD CONSTRAINT "trmParent" FOREIGN KEY (trm_parent_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "trmType" FOREIGN KEY (trm_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "trmOwner" FOREIGN KEY (trm_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* usergroup TABLE

ALTER TABLE usergroup
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "ugrType",
-- then recreate new
    ADD CONSTRAINT "ugrType" FOREIGN KEY (ugr_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE;



-- *************************************UPPER MODEL****************************************



-- ************* COLLECTION TABLE

ALTER TABLE collection
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "colOwner",
-- then recreate new
    ADD CONSTRAINT "colOwner" FOREIGN KEY (col_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* FRAGMENT TABLE

ALTER TABLE fragment
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "frgPart",
    DROP CONSTRAINT IF EXISTS "frgRestState",
    DROP CONSTRAINT IF EXISTS "frgOwner",
-- then recreate new
    ADD CONSTRAINT "frgPart" FOREIGN KEY (frg_part_id) REFERENCES part ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "frgRestState" FOREIGN KEY (frg_restore_state_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "frgOwner" FOREIGN KEY (frg_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* ITEM TABLE

ALTER TABLE item
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "itmType",
    DROP CONSTRAINT IF EXISTS "itmShape",
    DROP CONSTRAINT IF EXISTS "itmOwner",
-- then recreate new
    ADD CONSTRAINT "itmType" FOREIGN KEY (itm_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "itmShape" FOREIGN KEY (itm_shape_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "itmOwner" FOREIGN KEY (itm_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* MATERIALCONTEXT TABLE

ALTER TABLE materialcontext
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "mcxOwner",
-- then recreate new
    ADD CONSTRAINT "mcxOwner" FOREIGN KEY (mcx_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* PART TABLE

ALTER TABLE part
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "prtType",
    DROP CONSTRAINT IF EXISTS "prtShape",
    DROP CONSTRAINT IF EXISTS "prtItem",
    DROP CONSTRAINT IF EXISTS "prtOwner",
-- then recreate new
    ADD CONSTRAINT "prtType" FOREIGN KEY (prt_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "prtShape" FOREIGN KEY (prt_shape_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "prtItem" FOREIGN KEY (prt_item_id) REFERENCES item ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "prtOwner" FOREIGN KEY (prt_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* SURFACE TABLE

ALTER TABLE surface
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "srfFragment",
    DROP CONSTRAINT IF EXISTS "srfText",
-- then recreate new
    ADD CONSTRAINT "srfFragment" FOREIGN KEY (srf_fragment_id) REFERENCES fragment ON DELETE SET NULL ON UPDATE CASCADE;
--    ADD CONSTRAINT "srfText" FOREIGN KEY (srf_text_ids) REFERENCES text ON DELETE SET NULL ON UPDATE CASCADE;





-- **************************************Core Model****************************************************



-- ************* BASELINE TABLE

ALTER TABLE baseline
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "blnImgage",
    DROP CONSTRAINT IF EXISTS "blnType",
    DROP CONSTRAINT IF EXISTS "blnSurface",
    DROP CONSTRAINT IF EXISTS "blnOwner",
-- then recreate new
    ADD CONSTRAINT "blnImgage" FOREIGN KEY (bln_image_id) REFERENCES image ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "blnType" FOREIGN KEY (bln_type_id) REFERENCES term ON DELETE SET DEFAULT ON UPDATE CASCADE,
    ADD CONSTRAINT "blnSurface" FOREIGN KEY (bln_surface_id) REFERENCES surface ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "blnOwner" FOREIGN KEY (bln_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* COMPOUND TABLE

ALTER TABLE compound
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "cmpCase",
    DROP CONSTRAINT IF EXISTS "cmpClass",
    DROP CONSTRAINT IF EXISTS "cmpType",
    DROP CONSTRAINT IF EXISTS "cmpLemma",
    DROP CONSTRAINT IF EXISTS "cmpOwner",
-- then recreate new
    ADD CONSTRAINT "cmpCase" FOREIGN KEY (cmp_case_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "cmpClass" FOREIGN KEY (cmp_class_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "cmpType" FOREIGN KEY (cmp_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "cmpOwner" FOREIGN KEY (cmp_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* GRAPHEME TABLE

ALTER TABLE grapheme
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "graType",
    DROP CONSTRAINT IF EXISTS "graOwner",
-- then recreate new
    ADD CONSTRAINT "graType" FOREIGN KEY (gra_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "graOwner" FOREIGN KEY (gra_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* LEMMA TABLE

ALTER TABLE lemma
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "lemType",
    DROP CONSTRAINT IF EXISTS "lemLang",
    DROP CONSTRAINT IF EXISTS "lemDecl",
    DROP CONSTRAINT IF EXISTS "lemGender",
    DROP CONSTRAINT IF EXISTS "lemPOS",
    DROP CONSTRAINT IF EXISTS "lemClass",
    DROP CONSTRAINT IF EXISTS "lemOwner",
-- then recreate new
    ADD CONSTRAINT "lemType" FOREIGN KEY (lem_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "lemDecl" FOREIGN KEY (lem_declension_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "lemGender" FOREIGN KEY (lem_nominal_gender_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "lemPOS" FOREIGN KEY (lem_part_of_speech_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "lemClass" FOREIGN KEY (lem_verb_class_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "lemOwner" FOREIGN KEY (lem_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* LINE TABLE

ALTER TABLE line
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "linOwner",
-- then recreate new
    ADD CONSTRAINT "linOwner" FOREIGN KEY (lin_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* RUN TABLE

ALTER TABLE run
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "runScribe",
    DROP CONSTRAINT IF EXISTS "runScript",
    DROP CONSTRAINT IF EXISTS "runWriting",
    DROP CONSTRAINT IF EXISTS "runText",
    DROP CONSTRAINT IF EXISTS "runBaseline",
    DROP CONSTRAINT IF EXISTS "runOwner",
-- then recreate new
    ADD CONSTRAINT "runScribe" FOREIGN KEY (run_scribe_id) REFERENCES propernoun ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "runScript" FOREIGN KEY (run_script_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "runWriting" FOREIGN KEY (run_writing_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "runText" FOREIGN KEY (run_text_id) REFERENCES text ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "runBaseline" FOREIGN KEY (run_baseline_id) REFERENCES baseline ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "runOwner" FOREIGN KEY (run_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* SEGMENT TABLE

ALTER TABLE segment
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "segClarity",
    DROP CONSTRAINT IF EXISTS "segOwner",
-- then recreate new
    ADD CONSTRAINT "segClarity" FOREIGN KEY (seg_clarity_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "segOwner" FOREIGN KEY (seg_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* SPAN TABLE

ALTER TABLE span
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "spnType",
    DROP CONSTRAINT IF EXISTS "spnOwner",
-- then recreate new
    ADD CONSTRAINT "spnType" FOREIGN KEY (spn_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "spnOwner" FOREIGN KEY (spn_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* SYLLABLECLUSTER TABLE

ALTER TABLE syllablecluster
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "sclSegment",
    DROP CONSTRAINT IF EXISTS "sclOwner",
-- then recreate new
    ADD CONSTRAINT "sclSegment" FOREIGN KEY (scl_segment_id) REFERENCES segment ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "sclOwner" FOREIGN KEY (scl_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* TOKEN TABLE

ALTER TABLE token
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "tokLemma",
    DROP CONSTRAINT IF EXISTS "tokOwner",
-- then recreate new
    ADD CONSTRAINT "tokOwner" FOREIGN KEY (tok_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;





-- *********************************Ancillary Model*************************************



-- ************* CATALOG TABLE

ALTER TABLE catalog
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "catType",
    DROP CONSTRAINT IF EXISTS "catOwner",
-- then recreate new
    ADD CONSTRAINT "catType" FOREIGN KEY (cat_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "catOwner" FOREIGN KEY (cat_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* DATE TABLE

ALTER TABLE date
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "datOwner",
-- then recreate new
    ADD CONSTRAINT "datOwner" FOREIGN KEY (dat_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* EDITION TABLE

ALTER TABLE edition
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "ednOwner",
-- then recreate new
    ADD CONSTRAINT "ednOwner" FOREIGN KEY (edn_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* ERA TABLE

ALTER TABLE era
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "eraOwner",
-- then recreate new
    ADD CONSTRAINT "eraOwner" FOREIGN KEY (era_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* SEQUENCE TABLE

ALTER TABLE sequence
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "seqType",
--    DROP CONSTRAINT IF EXISTS "seqTheme",
    DROP CONSTRAINT IF EXISTS "seqOwner",
-- then recreate new
    ADD CONSTRAINT "seqType" FOREIGN KEY (seq_type_id) REFERENCES term ON DELETE SET NULL ON UPDATE CASCADE,
--    ADD CONSTRAINT "seqTheme" FOREIGN KEY (seq_theme_id) REFERENCES theme ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT "seqOwner" FOREIGN KEY (seq_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* TEXT TABLE

ALTER TABLE text
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "txtOwner",
-- then recreate new
    ADD CONSTRAINT "txtOwner" FOREIGN KEY (txt_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;


-- ************* TEXTMETADATA TABLE

ALTER TABLE textmetadata
-- Remove old constratint first
    DROP CONSTRAINT IF EXISTS "tmdText",
    DROP CONSTRAINT IF EXISTS "tmdOwner",
-- then recreate new
    ADD CONSTRAINT "tmdText" FOREIGN KEY (tmd_text_id) REFERENCES text ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT "tmdOwner" FOREIGN KEY (tmd_owner_id) REFERENCES usergroup ON DELETE SET DEFAULT ON UPDATE CASCADE;



