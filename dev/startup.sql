--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: dblink; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS dblink WITH SCHEMA public;


--
-- Name: EXTENSION dblink; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION dblink IS 'connect to other PostgreSQL databases from within a database';


--
-- Name: hstore; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS hstore WITH SCHEMA public;


--
-- Name: EXTENSION hstore; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION hstore IS 'data type for storing sets of (key, value) pairs';


SET search_path = public, pg_catalog;

--
-- Name: set_modified(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION set_modified() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.modified = now();
  RETURN NEW;
END;
$$;


--
-- Name: update_modified(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION update_modified() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
   IF row(NEW.*) IS DISTINCT FROM row(OLD.*) THEN
      NEW.modified = now();
      RETURN NEW;
   ELSE
      RETURN OLD;
   END IF;
END;
$$;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: annotation; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE annotation (
    ano_id integer NOT NULL,
    ano_linkfrom_ids pg_catalog.text[],
    ano_linkto_ids pg_catalog.text[],
    ano_type_id integer,
    ano_text pg_catalog.text,
    ano_url pg_catalog.text,
    ano_annotation_ids integer[],
    ano_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    ano_owner_id integer DEFAULT 2,
    ano_visibility_ids integer[] DEFAULT '{2}'::integer[],
    ano_scratch pg_catalog.text
);


--
-- Name: TABLE annotation; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE annotation IS 'Contains a record for each annotation.';


--
-- Name: COLUMN annotation.ano_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_id IS 'Uniquely identifies a annotation object.';


--
-- Name: COLUMN annotation.ano_linkfrom_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_linkfrom_ids IS 'Entity GlobalIDs used as the object/context of this annotation.';


--
-- Name: COLUMN annotation.ano_linkto_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_linkto_ids IS 'Entity GlobalIDs used as the subject of this annotation.';


--
-- Name: COLUMN annotation.ano_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_type_id IS 'Link to term identifying the type of this annotation.';


--
-- Name: COLUMN annotation.ano_text; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_text IS 'Note text for this annotation.';


--
-- Name: COLUMN annotation.ano_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_url IS 'URL uesd to annotate.';


--
-- Name: COLUMN annotation.ano_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_annotation_ids IS 'Links to commentary on this annotation.';


--
-- Name: COLUMN annotation.ano_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_attribution_ids IS 'Links to attributions for this annotation.';


--
-- Name: COLUMN annotation.ano_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN annotation.ano_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN annotation.ano_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: annotation_ano_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE annotation_ano_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: annotation_ano_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE annotation_ano_id_seq OWNED BY annotation.ano_id;


--
-- Name: attribution; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE attribution (
    atb_id integer NOT NULL,
    atb_title pg_catalog.text,
    atb_types pg_catalog.text[],
    atb_bib_id integer,
    atb_detail pg_catalog.text,
    atb_description pg_catalog.text,
    atb_group_id integer,
    modified timestamp without time zone DEFAULT now(),
    atb_owner_id integer DEFAULT 2,
    atb_annotation_ids integer[],
    atb_visibility_ids integer[] DEFAULT '{2}'::integer[],
    atb_scratch pg_catalog.text
);


--
-- Name: attribution_atb_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE attribution_atb_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attribution_atb_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE attribution_atb_id_seq OWNED BY attribution.atb_id;


--
-- Name: attributiongroup; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE attributiongroup (
    atg_id integer NOT NULL,
    atg_name pg_catalog.text,
    atg_type_id integer DEFAULT 1 NOT NULL,
    atg_realname pg_catalog.text,
    atg_date_created pg_catalog.date DEFAULT ('now'::pg_catalog.text)::pg_catalog.date NOT NULL,
    atg_description pg_catalog.text,
    atg_member_ids integer[],
    atg_admin_ids integer[],
    atg_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    atg_owner_id integer DEFAULT 2,
    atg_annotation_ids integer[],
    atg_visibility_ids integer[] DEFAULT '{2}'::integer[],
    atg_scratch pg_catalog.text
);


--
-- Name: TABLE attributiongroup; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE attributiongroup IS 'Contains a record for each attribution group entry.';


--
-- Name: COLUMN attributiongroup.atg_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_id IS 'Uniquely identifies particular attribution group entity.';


--
-- Name: COLUMN attributiongroup.atg_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_name IS 'Name of attribution group.';


--
-- Name: COLUMN attributiongroup.atg_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_type_id IS 'Type of attribution group.';


--
-- Name: COLUMN attributiongroup.atg_realname; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_realname IS 'Actual name of attribution group.';


--
-- Name: COLUMN attributiongroup.atg_date_created; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_date_created IS 'Date attribution group was created.';


--
-- Name: COLUMN attributiongroup.atg_member_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_member_ids IS 'Links to attribution group entities that belong to this attribution group.';


--
-- Name: COLUMN attributiongroup.atg_admin_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_admin_ids IS 'Links to attribution group entities that can admin this attribution group.';


--
-- Name: COLUMN attributiongroup.atg_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_attribution_ids IS 'Link to entity that created this attribution group.';


--
-- Name: COLUMN attributiongroup.atg_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN attributiongroup.atg_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_annotation_ids IS 'Links to commentary on this attribution group.';


--
-- Name: COLUMN attributiongroup.atg_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN attributiongroup.atg_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: attributiongroup_atg_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE attributiongroup_atg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: attributiongroup_atg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE attributiongroup_atg_id_seq OWNED BY attributiongroup.atg_id;


--
-- Name: baseline; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE baseline (
    bln_id integer NOT NULL,
    bln_type_id integer DEFAULT 1 NOT NULL,
    bln_image_id integer,
    bln_surface_id integer,
    bln_image_position polygon[],
    bln_transcription pg_catalog.text,
    bln_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    bln_owner_id integer DEFAULT 2,
    bln_annotation_ids integer[],
    bln_visibility_ids integer[] DEFAULT '{2}'::integer[],
    bln_scratch pg_catalog.text
);


--
-- Name: TABLE baseline; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE baseline IS 'Contains a record for each Image record identified as being a baseline.  May be one per inscription, one per line or one per sequence of lines encompassed by an image. Where no BaseLine image exists record contains a reported transcription of the inscription.';


--
-- Name: COLUMN baseline.bln_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_id IS 'Uniquely identifies particular baseline.';


--
-- Name: COLUMN baseline.bln_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_type_id IS 'Link to the term indicating the type of this baseline.';


--
-- Name: COLUMN baseline.bln_image_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_image_id IS 'Link to the image record for this baseline.';


--
-- Name: COLUMN baseline.bln_surface_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_surface_id IS 'Link to surface this baseline refers to in part or in whole.';


--
-- Name: COLUMN baseline.bln_image_position; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_image_position IS 'polygon defining the boundary of the image record for this baseline.';


--
-- Name: COLUMN baseline.bln_transcription; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_transcription IS 'This transcription is then used as the baseline for segmentation.';


--
-- Name: COLUMN baseline.bln_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_attribution_ids IS 'Links to attributions for this baseline.';


--
-- Name: COLUMN baseline.bln_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN baseline.bln_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_annotation_ids IS 'Links to commentary on this baseline.';


--
-- Name: COLUMN baseline.bln_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN baseline.bln_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: baseline_bln_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE baseline_bln_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: baseline_bln_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE baseline_bln_id_seq OWNED BY baseline.bln_id;


--
-- Name: bibliography; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE bibliography (
    bib_id integer NOT NULL,
    bib_name pg_catalog.text,
    bib_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    bib_owner_id integer DEFAULT 2,
    bib_annotation_ids integer[],
    bib_visibility_ids integer[] DEFAULT '{2}'::integer[],
    bib_scratch pg_catalog.text
);


--
-- Name: TABLE bibliography; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE bibliography IS 'Contains a record for each Bibliographic entry.';


--
-- Name: COLUMN bibliography.bib_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN bibliography.bib_id IS 'Uniquely identifies particular bibliographic entity.';


--
-- Name: COLUMN bibliography.bib_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN bibliography.bib_name IS 'Title of Bibliography entry.';


--
-- Name: COLUMN bibliography.bib_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN bibliography.bib_attribution_ids IS 'Link to entity that created this bibliography.';


--
-- Name: COLUMN bibliography.bib_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN bibliography.bib_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN bibliography.bib_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN bibliography.bib_annotation_ids IS 'Links to commentary on this bibliography.';


--
-- Name: COLUMN bibliography.bib_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN bibliography.bib_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: bibliography_bib_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE bibliography_bib_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bibliography_bib_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE bibliography_bib_id_seq OWNED BY bibliography.bib_id;


--
-- Name: catalog; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE catalog (
    cat_id integer NOT NULL,
    cat_title pg_catalog.text,
    cat_type_id integer,
    cat_lang_id integer,
    cat_description pg_catalog.text,
    cat_edition_ids integer[],
    cat_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    cat_owner_id integer DEFAULT 2,
    cat_annotation_ids integer[],
    cat_visibility_ids integer[] DEFAULT '{2}'::integer[],
    cat_scratch pg_catalog.text
);


--
-- Name: TABLE catalog; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE catalog IS 'Contains a record for each catalog.';


--
-- Name: COLUMN catalog.cat_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_id IS 'Uniquely identifies a catalog object.';


--
-- Name: COLUMN catalog.cat_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_title IS 'Display title of the catalog.';


--
-- Name: COLUMN catalog.cat_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_type_id IS 'Link to the term from a typology for this catalog.';


--
-- Name: COLUMN catalog.cat_lang_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_lang_id IS 'Link to the term identifying the source language of this catalog.';


--
-- Name: COLUMN catalog.cat_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_description IS 'Description of the catalog.';


--
-- Name: COLUMN catalog.cat_edition_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_edition_ids IS 'Links to edition entities that make up this catalog.';


--
-- Name: COLUMN catalog.cat_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_attribution_ids IS 'Links to attributions for this catalog.';


--
-- Name: COLUMN catalog.cat_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN catalog.cat_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_annotation_ids IS 'Links to commentary on this catalog.';


--
-- Name: COLUMN catalog.cat_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN catalog.cat_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: catalog_cat_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE catalog_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: catalog_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE catalog_cat_id_seq OWNED BY catalog.cat_id;


--
-- Name: collection; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE collection (
    col_id integer NOT NULL,
    col_title pg_catalog.text DEFAULT 'Need Title'::pg_catalog.text NOT NULL,
    col_location_refs pg_catalog.text[],
    col_description pg_catalog.text DEFAULT 'Need Description'::pg_catalog.text NOT NULL,
    col_item_part_fragment_ids character varying(31)[],
    col_exclude_part_fragment_ids character varying(31)[],
    col_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    col_owner_id integer DEFAULT 2,
    col_annotation_ids integer[],
    col_visibility_ids integer[] DEFAULT '{2}'::integer[],
    col_scratch pg_catalog.text
);


--
-- Name: TABLE collection; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE collection IS 'Contains a record for each Collection.';


--
-- Name: COLUMN collection.col_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_id IS 'Uniquely identifies particular collection.';


--
-- Name: COLUMN collection.col_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_title IS 'Title of COLLECTION in the current catalog or historical name.';


--
-- Name: COLUMN collection.col_location_refs; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_location_refs IS 'location id-Reference number pairs for this COLLECTION.';


--
-- Name: COLUMN collection.col_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_description IS 'Description which encompasses all ITEMS in this COLLECTION.';


--
-- Name: COLUMN collection.col_item_part_fragment_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_item_part_fragment_ids IS 'Links to objects in this collection.';


--
-- Name: COLUMN collection.col_exclude_part_fragment_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_exclude_part_fragment_ids IS 'Links to exclude objects for this collection.';


--
-- Name: COLUMN collection.col_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_attribution_ids IS 'Link to entity that created this collection.';


--
-- Name: COLUMN collection.col_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN collection.col_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_annotation_ids IS 'Links to commentary on this collection.';


--
-- Name: COLUMN collection.col_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN collection.col_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: collection_col_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE collection_col_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: collection_col_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE collection_col_id_seq OWNED BY collection.col_id;


--
-- Name: compound; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE compound (
    cmp_id integer NOT NULL,
    cmp_value pg_catalog.text,
    cmp_transcription pg_catalog.text,
    cmp_component_ids character varying(31)[],
    cmp_case_id integer,
    cmp_class_id integer,
    cmp_type_id integer,
    cmp_sort_code pg_catalog.text,
    cmp_sort_code2 pg_catalog.text,
    cmp_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    cmp_owner_id integer DEFAULT 2,
    cmp_annotation_ids integer[],
    cmp_visibility_ids integer[] DEFAULT '{2}'::integer[],
    cmp_scratch pg_catalog.text
);


--
-- Name: TABLE compound; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE compound IS 'Contains a record for each compound.';


--
-- Name: COLUMN compound.cmp_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_id IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';


--
-- Name: COLUMN compound.cmp_value; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_value IS 'Display value (calc) of the compound.';


--
-- Name: COLUMN compound.cmp_transcription; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_transcription IS 'transcription value (calc) of the compound.';


--
-- Name: COLUMN compound.cmp_component_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_component_ids IS 'Links to the tokens and/or compounds for this compound';


--
-- Name: COLUMN compound.cmp_case_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_case_id IS 'Internal component case of compound';


--
-- Name: COLUMN compound.cmp_class_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_class_id IS 'Internal component classification.';


--
-- Name: COLUMN compound.cmp_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_type_id IS 'Type of compound.';


--
-- Name: COLUMN compound.cmp_sort_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_sort_code IS 'Primary Code used to order compounds.';


--
-- Name: COLUMN compound.cmp_sort_code2; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_sort_code2 IS 'Secondary Code used to order compounds.';


--
-- Name: COLUMN compound.cmp_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_attribution_ids IS 'Links to attributions for this compound.';


--
-- Name: COLUMN compound.cmp_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN compound.cmp_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_annotation_ids IS 'Links to commentary on this compound.';


--
-- Name: COLUMN compound.cmp_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN compound.cmp_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: compound_cmp_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE compound_cmp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: compound_cmp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE compound_cmp_id_seq OWNED BY compound.cmp_id;


--
-- Name: date; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE date (
    dat_id integer NOT NULL,
    dat_prob_begin_date integer DEFAULT 9999 NOT NULL,
    dat_prob_end_date integer,
    dat_entity_id character varying(31),
    dat_evidences pg_catalog.text[],
    dat_preferred_era_id integer,
    dat_era_ids integer[],
    dat_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    dat_owner_id integer DEFAULT 2,
    dat_annotation_ids integer[],
    dat_visibility_ids integer[] DEFAULT '{2}'::integer[],
    dat_scratch pg_catalog.text
);


--
-- Name: TABLE date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE date IS 'Contains a record for each date.';


--
-- Name: COLUMN date.dat_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_id IS 'Uniquely identifies a date object.';


--
-- Name: COLUMN date.dat_prob_begin_date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_prob_begin_date IS 'Probable beginning of this date.';


--
-- Name: COLUMN date.dat_prob_end_date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_prob_end_date IS 'Probable endding of this date.';


--
-- Name: COLUMN date.dat_entity_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_entity_id IS 'Global ID for entity linked to date .';


--
-- Name: COLUMN date.dat_evidences; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_evidences IS 'Term ID : value pairs  of this date.';


--
-- Name: COLUMN date.dat_preferred_era_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_preferred_era_id IS 'Preferred era of this date.';


--
-- Name: COLUMN date.dat_era_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_era_ids IS 'Ids of the possible eras of this date.';


--
-- Name: COLUMN date.dat_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_attribution_ids IS 'Links to attributions for this date.';


--
-- Name: COLUMN date.dat_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN date.dat_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_annotation_ids IS 'Links to commentary on this date.';


--
-- Name: COLUMN date.dat_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN date.dat_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: date_dat_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE date_dat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: date_dat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE date_dat_id_seq OWNED BY date.dat_id;


--
-- Name: edition; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE edition (
    edn_id integer NOT NULL,
    edn_description pg_catalog.text,
    edn_sequence_ids integer[],
    edn_text_id integer,
    edn_type_id integer,
    edn_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    edn_owner_id integer DEFAULT 2,
    edn_annotation_ids integer[],
    edn_visibility_ids integer[] DEFAULT '{2}'::integer[],
    edn_scratch pg_catalog.text
);


--
-- Name: TABLE edition; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE edition IS 'Contains a record for each edition.';


--
-- Name: COLUMN edition.edn_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_id IS 'Uniquely identifies a edition object.';


--
-- Name: COLUMN edition.edn_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_description IS 'Description of the edition.';


--
-- Name: COLUMN edition.edn_sequence_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_sequence_ids IS 'Links to sequence that cover this edition.';


--
-- Name: COLUMN edition.edn_text_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_text_id IS 'Link to text entity for this edition.';


--
-- Name: COLUMN edition.edn_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_type_id IS 'Link to the term from a typology for this edition.';


--
-- Name: COLUMN edition.edn_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_attribution_ids IS 'Links to attributions for this edition.';


--
-- Name: COLUMN edition.edn_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN edition.edn_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_annotation_ids IS 'Links to commentary on this edition.';


--
-- Name: COLUMN edition.edn_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN edition.edn_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: edition_edn_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE edition_edn_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: edition_edn_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE edition_edn_id_seq OWNED BY edition.edn_id;


--
-- Name: era; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE era (
    era_id integer NOT NULL,
    era_title pg_catalog.text DEFAULT 'Need Title'::pg_catalog.text NOT NULL,
    era_begin_date integer NOT NULL,
    era_end_date integer,
    era_order integer,
    era_preferred boolean,
    era_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    era_owner_id integer DEFAULT 2,
    era_annotation_ids integer[],
    era_visibility_ids integer[] DEFAULT '{2}'::integer[],
    era_scratch pg_catalog.text
);


--
-- Name: TABLE era; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE era IS 'Contains a record for each era.';


--
-- Name: COLUMN era.era_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_id IS 'Uniquely identifies a era object.';


--
-- Name: COLUMN era.era_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_title IS 'Title/Name of the era.';


--
-- Name: COLUMN era.era_begin_date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_begin_date IS 'Begin date for this era.';


--
-- Name: COLUMN era.era_end_date; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_end_date IS 'End date for this era.';


--
-- Name: COLUMN era.era_order; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_order IS 'Order of this era.';


--
-- Name: COLUMN era.era_preferred; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_preferred IS 'Preferred status of this era.';


--
-- Name: COLUMN era.era_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_attribution_ids IS 'Links to attributions for this era.';


--
-- Name: COLUMN era.era_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN era.era_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_annotation_ids IS 'Links to commentary on this era.';


--
-- Name: COLUMN era.era_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN era.era_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: era_era_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE era_era_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: era_era_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE era_era_id_seq OWNED BY era.era_id;


--
-- Name: fragment; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE fragment (
    frg_id integer NOT NULL,
    frg_label pg_catalog.text,
    frg_description pg_catalog.text,
    frg_measure pg_catalog.text,
    frg_restore_state_id integer,
    frg_location_refs pg_catalog.text[],
    frg_part_id integer,
    frg_material_context_ids integer[],
    frg_image_ids integer[],
    frg_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    frg_owner_id integer DEFAULT 2,
    frg_annotation_ids integer[],
    frg_visibility_ids integer[] DEFAULT '{2}'::integer[],
    frg_scratch pg_catalog.text
);


--
-- Name: TABLE fragment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE fragment IS 'Contains a record for each fragment of a part.';


--
-- Name: COLUMN fragment.frg_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_id IS 'Uniquely identifies particular fragment.';


--
-- Name: COLUMN fragment.frg_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_label IS 'Part Unique Label identifying particular fragment.';


--
-- Name: COLUMN fragment.frg_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_description IS 'Free text description of FRAGMENT.';


--
-- Name: COLUMN fragment.frg_measure; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_measure IS 'Measurement in structured format.';


--
-- Name: COLUMN fragment.frg_restore_state_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_restore_state_id IS 'Link to term defining the restoration state of this fragment.';


--
-- Name: COLUMN fragment.frg_location_refs; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_location_refs IS 'Location/Reference pairs, 1st is current.';


--
-- Name: COLUMN fragment.frg_part_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_part_id IS 'Link to part this fragment belongs to.';


--
-- Name: COLUMN fragment.frg_material_context_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_material_context_ids IS 'Links to Material Context record.';


--
-- Name: COLUMN fragment.frg_image_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_image_ids IS 'Links to images of this fragment.';


--
-- Name: COLUMN fragment.frg_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_attribution_ids IS 'Link to a ATTRIBUTION event.';


--
-- Name: COLUMN fragment.frg_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN fragment.frg_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_annotation_ids IS 'Links to commentary on this fragment.';


--
-- Name: COLUMN fragment.frg_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN fragment.frg_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: fragment_frg_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE fragment_frg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fragment_frg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE fragment_frg_id_seq OWNED BY fragment.frg_id;


--
-- Name: grapheme; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE grapheme (
    gra_id integer NOT NULL,
    gra_grapheme character varying(15) DEFAULT 'Need Grapheme'::character varying NOT NULL,
    gra_uppercase character varying(15),
    gra_type_id integer,
    gra_text_critical_mark pg_catalog.text,
    gra_alt character varying(31),
    gra_emmendation character varying(63),
    gra_decomposition character varying(63),
    gra_sort_code character varying(63),
    modified timestamp without time zone DEFAULT now(),
    gra_owner_id integer DEFAULT 2,
    gra_annotation_ids integer[],
    gra_visibility_ids integer[] DEFAULT '{2}'::integer[],
    gra_scratch pg_catalog.text
);


--
-- Name: TABLE grapheme; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE grapheme IS 'Contains a record for each syllable cluster for a segment.';


--
-- Name: COLUMN grapheme.gra_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_id IS 'Uniquely identifies a particular grapheme.';


--
-- Name: COLUMN grapheme.gra_grapheme; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_grapheme IS 'the grapheme.';


--
-- Name: COLUMN grapheme.gra_uppercase; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_uppercase IS 'the uppercase version of grapheme use to Uppercase display.';


--
-- Name: COLUMN grapheme.gra_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_type_id IS 'Indicates the type of grapheme.';


--
-- Name: COLUMN grapheme.gra_text_critical_mark; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_text_critical_mark IS 'Critical Marking used to identify aspects of the script critical to this interpretation.';


--
-- Name: COLUMN grapheme.gra_alt; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_alt IS 'Indicates alternative grapheme.';


--
-- Name: COLUMN grapheme.gra_emmendation; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_emmendation IS 'emmendation of grapheme.';


--
-- Name: COLUMN grapheme.gra_decomposition; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_decomposition IS 'decomposition of grapheme on token boundary.';


--
-- Name: COLUMN grapheme.gra_sort_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_sort_code IS 'Code used to order grapheme.';


--
-- Name: COLUMN grapheme.gra_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN grapheme.gra_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_annotation_ids IS 'Links to commentary on this grapheme.';


--
-- Name: COLUMN grapheme.gra_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN grapheme.gra_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: grapheme_gra_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE grapheme_gra_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: grapheme_gra_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE grapheme_gra_id_seq OWNED BY grapheme.gra_id;


--
-- Name: image; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE image (
    img_id integer NOT NULL,
    img_title pg_catalog.text,
    img_type_id integer DEFAULT 1 NOT NULL,
    img_url pg_catalog.text,
    img_image_pos polygon[],
    img_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    img_owner_id integer DEFAULT 2,
    img_annotation_ids integer[],
    img_visibility_ids integer[] DEFAULT '{2}'::integer[],
    img_scratch pg_catalog.text
);


--
-- Name: TABLE image; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE image IS 'Contains records for each inscription image, those that are identified as Baseline images and those that are identified as EditionReference images.';


--
-- Name: COLUMN image.img_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_title IS 'Title of Image.';


--
-- Name: COLUMN image.img_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_type_id IS 'Type of image.';


--
-- Name: COLUMN image.img_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_url IS 'URL that returns full image.';


--
-- Name: COLUMN image.img_image_pos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_image_pos IS 'Polygons for cropping image.';


--
-- Name: COLUMN image.img_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_attribution_ids IS 'Link to entity that contains attribution information for this image.';


--
-- Name: COLUMN image.img_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN image.img_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_annotation_ids IS 'Links to commentary on this image.';


--
-- Name: COLUMN image.img_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN image.img_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: image_img_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE image_img_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: image_img_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE image_img_id_seq OWNED BY image.img_id;


--
-- Name: inflection; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE inflection (
    inf_id integer NOT NULL,
    inf_chaya pg_catalog.text,
    inf_component_ids pg_catalog.text[],
    inf_certainty integer[],
    inf_case_id integer,
    inf_nominal_gender_id integer,
    inf_gram_number_id integer,
    inf_verb_person_id integer,
    inf_verb_voice_id integer,
    inf_verb_tense_id integer,
    inf_verb_mood_id integer,
    inf_verb_second_conj_id integer,
    inf_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    inf_owner_id integer DEFAULT 2,
    inf_annotation_ids integer[],
    inf_visibility_ids integer[] DEFAULT '{2}'::integer[],
    inf_scratch pg_catalog.text
);


--
-- Name: TABLE inflection; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE inflection IS 'Contains a record for each inflection.';


--
-- Name: COLUMN inflection.inf_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_id IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';


--
-- Name: COLUMN inflection.inf_chaya; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_chaya IS 'Chaya value for the inflection.';


--
-- Name: COLUMN inflection.inf_component_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_component_ids IS 'Set of compound/token IDs that are attested forms this inflection of a lemma.';


--
-- Name: COLUMN inflection.inf_certainty; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_certainty IS 'Array of certainty values (1 2 3) that map to the decomposition values for this inflection in order';


--
-- Name: COLUMN inflection.inf_case_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_case_id IS 'case of inflection';


--
-- Name: COLUMN inflection.inf_nominal_gender_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_nominal_gender_id IS 'Nominal gender';


--
-- Name: COLUMN inflection.inf_gram_number_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_gram_number_id IS 'the plurality of this inflection';


--
-- Name: COLUMN inflection.inf_verb_person_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_verb_person_id IS 'Verbal person';


--
-- Name: COLUMN inflection.inf_verb_voice_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_verb_voice_id IS 'Active, Middle or Passive verb form.';


--
-- Name: COLUMN inflection.inf_verb_tense_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_verb_tense_id IS 'Verbal tense';


--
-- Name: COLUMN inflection.inf_verb_mood_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_verb_mood_id IS 'Verbal mood';


--
-- Name: COLUMN inflection.inf_verb_second_conj_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_verb_second_conj_id IS 'Verbal Secondary conjugation.';


--
-- Name: COLUMN inflection.inf_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_attribution_ids IS 'Links to attributions for this inflection.';


--
-- Name: COLUMN inflection.inf_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN inflection.inf_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_annotation_ids IS 'Links to commentary on this inflection.';


--
-- Name: COLUMN inflection.inf_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN inflection.inf_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: inflection_inf_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE inflection_inf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inflection_inf_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE inflection_inf_id_seq OWNED BY inflection.inf_id;


--
-- Name: item; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE item (
    itm_id integer NOT NULL,
    itm_title pg_catalog.text DEFAULT 'Need Title'::pg_catalog.text NOT NULL,
    itm_type_id integer,
    itm_shape_id integer,
    itm_measure pg_catalog.text,
    itm_image_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    itm_owner_id integer DEFAULT 2,
    itm_annotation_ids integer[],
    itm_visibility_ids integer[] DEFAULT '{2}'::integer[],
    itm_scratch pg_catalog.text
);


--
-- Name: TABLE item; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE item IS 'Contains a record for each item.';


--
-- Name: COLUMN item.itm_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_id IS 'Uniquely identifies particular item.';


--
-- Name: COLUMN item.itm_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_title IS 'Title of ITEM in the current catalog.';


--
-- Name: COLUMN item.itm_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_type_id IS 'Structured artefact typology.';


--
-- Name: COLUMN item.itm_shape_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_shape_id IS 'Link to term defining the shape of this item.';


--
-- Name: COLUMN item.itm_measure; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_measure IS 'Measurement in structured format.';


--
-- Name: COLUMN item.itm_image_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_image_ids IS 'Links to images of this item.';


--
-- Name: COLUMN item.itm_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN item.itm_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_annotation_ids IS 'Links to commentary on this item.';


--
-- Name: COLUMN item.itm_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN item.itm_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: item_itm_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE item_itm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: item_itm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE item_itm_id_seq OWNED BY item.itm_id;


--
-- Name: jsoncache; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE jsoncache (
    jsc_id integer NOT NULL,
    jsc_label pg_catalog.text,
    jsc_type_id integer,
    jsc_json_string pg_catalog.text,
    jsc_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    jsc_owner_id integer DEFAULT 2,
    jsc_annotation_ids integer[],
    jsc_visibility_ids integer[] DEFAULT '{2}'::integer[],
    jsc_scratch pg_catalog.text
);


--
-- Name: TABLE jsoncache; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE jsoncache IS 'Contains a record storing a json string.';


--
-- Name: COLUMN jsoncache.jsc_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_id IS 'Uniquely identifies particular json string.';


--
-- Name: COLUMN jsoncache.jsc_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_label IS 'A label that identifies particular json string.';


--
-- Name: COLUMN jsoncache.jsc_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_type_id IS 'Term id for the type of this particular json string.';


--
-- Name: COLUMN jsoncache.jsc_json_string; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_json_string IS 'JSON string.';


--
-- Name: COLUMN jsoncache.jsc_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_attribution_ids IS 'Link to entity that contains attribution information for this cache.';


--
-- Name: COLUMN jsoncache.jsc_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN jsoncache.jsc_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_annotation_ids IS 'Links to commentary on this cache.';


--
-- Name: COLUMN jsoncache.jsc_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN jsoncache.jsc_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: jsoncache_jsc_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE jsoncache_jsc_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jsoncache_jsc_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE jsoncache_jsc_id_seq OWNED BY jsoncache.jsc_id;


--
-- Name: lemma; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE lemma (
    lem_id integer NOT NULL,
    lem_value pg_catalog.text,
    lem_search pg_catalog.text,
    lem_translation pg_catalog.text,
    lem_homographorder integer,
    lem_type_id integer,
    lem_certainty integer[],
    lem_part_of_speech_id integer,
    lem_subpart_of_speech_id integer,
    lem_nominal_gender_id integer,
    lem_verb_class_id integer,
    lem_declension_id integer,
    lem_description pg_catalog.text,
    lem_catalog_id integer,
    lem_component_ids character varying(31)[],
    lem_sort_code pg_catalog.text,
    lem_sort_code2 pg_catalog.text,
    lem_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    lem_owner_id integer DEFAULT 2,
    lem_annotation_ids integer[],
    lem_visibility_ids integer[] DEFAULT '{2}'::integer[],
    lem_scratch pg_catalog.text
);


--
-- Name: TABLE lemma; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE lemma IS 'Contains a record for each lemma.';


--
-- Name: COLUMN lemma.lem_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_id IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';


--
-- Name: COLUMN lemma.lem_value; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_value IS 'Display value (calc) of the lemma.';


--
-- Name: COLUMN lemma.lem_search; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_search IS 'search value of this lemma';


--
-- Name: COLUMN lemma.lem_translation; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_translation IS 'Modern Language translation of this lemma.';


--
-- Name: COLUMN lemma.lem_homographorder; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_homographorder IS 'homograph differentiator for the lemma.';


--
-- Name: COLUMN lemma.lem_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_type_id IS 'Type of lemma.';


--
-- Name: COLUMN lemma.lem_certainty; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_certainty IS 'Array of Certainty values for decomposition values for this lemma.';


--
-- Name: COLUMN lemma.lem_part_of_speech_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_part_of_speech_id IS 'Word classification for role in speech';


--
-- Name: COLUMN lemma.lem_subpart_of_speech_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_subpart_of_speech_id IS 'Word subclassification for role in speech';


--
-- Name: COLUMN lemma.lem_nominal_gender_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_nominal_gender_id IS 'Nominal gender';


--
-- Name: COLUMN lemma.lem_verb_class_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_verb_class_id IS 'Verbal class';


--
-- Name: COLUMN lemma.lem_declension_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_declension_id IS 'Declension';


--
-- Name: COLUMN lemma.lem_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_description IS 'description and/or definition of this lemma';


--
-- Name: COLUMN lemma.lem_catalog_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_catalog_id IS 'Catalog container for the lemma.';


--
-- Name: COLUMN lemma.lem_component_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_component_ids IS 'Attested forms of inflections of lemma.';


--
-- Name: COLUMN lemma.lem_sort_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_sort_code IS 'Primary Code used to order lemmata.';


--
-- Name: COLUMN lemma.lem_sort_code2; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_sort_code2 IS 'Secondary Code used to order lemmata.';


--
-- Name: COLUMN lemma.lem_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_attribution_ids IS 'Links to attributions for this lemma.';


--
-- Name: COLUMN lemma.lem_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN lemma.lem_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_annotation_ids IS 'Links to commentary on this lemma.';


--
-- Name: COLUMN lemma.lem_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN lemma.lem_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: lemma_lem_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE lemma_lem_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lemma_lem_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE lemma_lem_id_seq OWNED BY lemma.lem_id;


--
-- Name: line; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE line (
    lin_id integer NOT NULL,
    lin_order integer,
    lin_mask pg_catalog.text,
    lin_span_ids integer[],
    lin_annotation_ids integer[],
    lin_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    lin_owner_id integer DEFAULT 2,
    lin_visibility_ids integer[] DEFAULT '{2}'::integer[],
    lin_scratch pg_catalog.text
);


--
-- Name: TABLE line; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE line IS 'Contains a record for each line.';


--
-- Name: COLUMN line.lin_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_id IS 'Uniquely identifies a particular linester.';


--
-- Name: COLUMN line.lin_order; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_order IS 'Order of line.';


--
-- Name: COLUMN line.lin_mask; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_mask IS 'Display number of line.';


--
-- Name: COLUMN line.lin_span_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_span_ids IS 'Array of ids for the span records for this line.';


--
-- Name: COLUMN line.lin_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_annotation_ids IS 'Links to commentary on this line.';


--
-- Name: COLUMN line.lin_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_attribution_ids IS 'Links to attributions for this line.';


--
-- Name: COLUMN line.lin_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN line.lin_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN line.lin_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: line_lin_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE line_lin_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: line_lin_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE line_lin_id_seq OWNED BY line.lin_id;


--
-- Name: materialcontext; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE materialcontext (
    mcx_id integer NOT NULL,
    mcx_arch_context pg_catalog.text,
    mcx_find_status pg_catalog.text,
    mcx_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    mcx_owner_id integer DEFAULT 2,
    mcx_annotation_ids integer[],
    mcx_visibility_ids integer[] DEFAULT '{2}'::integer[],
    mcx_scratch pg_catalog.text
);


--
-- Name: TABLE materialcontext; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE materialcontext IS 'Contains a record for each materialcontext.';


--
-- Name: COLUMN materialcontext.mcx_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_id IS 'Uniquely identifies a materialcontext object.';


--
-- Name: COLUMN materialcontext.mcx_arch_context; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_arch_context IS 'Text identifying the archaeological context.';


--
-- Name: COLUMN materialcontext.mcx_find_status; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_find_status IS 'Term id:Attribution is pair defining the find status.';


--
-- Name: COLUMN materialcontext.mcx_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_attribution_ids IS 'Links to attributions for this materialcontext.';


--
-- Name: COLUMN materialcontext.mcx_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN materialcontext.mcx_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_annotation_ids IS 'Links to commentary on this materialcontext.';


--
-- Name: COLUMN materialcontext.mcx_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN materialcontext.mcx_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: materialcontext_mcx_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE materialcontext_mcx_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: materialcontext_mcx_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE materialcontext_mcx_id_seq OWNED BY materialcontext.mcx_id;


--
-- Name: part; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE part (
    prt_id integer NOT NULL,
    prt_label pg_catalog.text,
    prt_type_id integer,
    prt_shape_id integer,
    prt_mediums pg_catalog.text[],
    prt_measure pg_catalog.text,
    prt_manufacture_id integer,
    prt_sequence integer,
    prt_item_id integer,
    prt_image_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    prt_owner_id integer DEFAULT 2,
    prt_annotation_ids integer[],
    prt_visibility_ids integer[] DEFAULT '{2}'::integer[],
    prt_scratch pg_catalog.text
);


--
-- Name: TABLE part; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE part IS 'Contains a record for each part of an item.';


--
-- Name: COLUMN part.prt_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_id IS 'Uniquely identifies particular part.';


--
-- Name: COLUMN part.prt_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_label IS 'Item Unique Label identifying particular part.';


--
-- Name: COLUMN part.prt_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_type_id IS 'Structured artefact typology.';


--
-- Name: COLUMN part.prt_shape_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_shape_id IS 'Shape of part.';


--
-- Name: COLUMN part.prt_mediums; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_mediums IS 'Material from which part is constructed or composed.';


--
-- Name: COLUMN part.prt_measure; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_measure IS 'Measurement in structured format.';


--
-- Name: COLUMN part.prt_manufacture_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_manufacture_id IS 'Link to term defining the manufacturing technique used for this part.';


--
-- Name: COLUMN part.prt_sequence; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_sequence IS 'Numbered order of a PART where there is a physical sequence of parts e.g numbered leaves in a folio.';


--
-- Name: COLUMN part.prt_item_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_item_id IS 'Link to item this part belongs to.';


--
-- Name: COLUMN part.prt_image_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_image_ids IS 'Links to images of this part.';


--
-- Name: COLUMN part.prt_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_owner_id IS 'Link to the usergroup that owns this entity.';


--
-- Name: COLUMN part.prt_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_annotation_ids IS 'Links to commentary on this part.';


--
-- Name: COLUMN part.prt_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN part.prt_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: part_prt_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE part_prt_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: part_prt_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE part_prt_id_seq OWNED BY part.prt_id;


--
-- Name: propernoun; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE propernoun (
    prn_id integer NOT NULL,
    prn_labels pg_catalog.text DEFAULT 'Need Labels'::pg_catalog.text NOT NULL,
    prn_type_id integer,
    prn_evidences pg_catalog.text,
    prn_description pg_catalog.text,
    prn_url pg_catalog.text,
    prn_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    prn_owner_id integer DEFAULT 2,
    prn_annotation_ids integer[],
    prn_visibility_ids integer[] DEFAULT '{2}'::integer[],
    prn_scratch pg_catalog.text
);


--
-- Name: TABLE propernoun; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE propernoun IS 'Contains a record for each propernoun.';


--
-- Name: COLUMN propernoun.prn_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_id IS 'Uniquely identifies a propernoun object.';


--
-- Name: COLUMN propernoun.prn_labels; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_labels IS 'ISO Language code : label pairs for this propernoun.';


--
-- Name: COLUMN propernoun.prn_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_type_id IS 'Link to propernoun identifying the type of this propernoun.';


--
-- Name: COLUMN propernoun.prn_evidences; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_evidences IS 'Array of semantic pairs identifying the evidence';


--
-- Name: COLUMN propernoun.prn_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_description IS 'Description of the propernoun.';


--
-- Name: COLUMN propernoun.prn_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_url IS 'URL to definition of this propernoun.';


--
-- Name: COLUMN propernoun.prn_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_attribution_ids IS 'Links to attributions for this propernoun.';


--
-- Name: COLUMN propernoun.prn_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN propernoun.prn_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_annotation_ids IS 'Links to commentary on this propernoun.';


--
-- Name: COLUMN propernoun.prn_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN propernoun.prn_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: propernoun_prn_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE propernoun_prn_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: propernoun_prn_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE propernoun_prn_id_seq OWNED BY propernoun.prn_id;


--
-- Name: run; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE run (
    run_id integer NOT NULL,
    run_scribe_id integer,
    run_text_id integer,
    run_baseline_id integer,
    run_image_pos polygon[],
    run_script_id integer,
    run_writing_id integer,
    run_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    run_owner_id integer DEFAULT 2,
    run_annotation_ids integer[],
    run_visibility_ids integer[] DEFAULT '{2}'::integer[],
    run_scratch pg_catalog.text
);


--
-- Name: TABLE run; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE run IS 'Contains a record for each run.';


--
-- Name: COLUMN run.run_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_id IS 'Uniquely identifies a particular run.';


--
-- Name: COLUMN run.run_scribe_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_scribe_id IS 'Uniquely identifies the scribe for this particular run.';


--
-- Name: COLUMN run.run_text_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_text_id IS 'Link to text entity of this run.';


--
-- Name: COLUMN run.run_baseline_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_baseline_id IS 'Link to the baseline record for this run.';


--
-- Name: COLUMN run.run_image_pos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_image_pos IS 'Polygons defining the bounds for the scribe run for the link baseline.';


--
-- Name: COLUMN run.run_script_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_script_id IS 'Term ID of the script used for TEXT in the run.';


--
-- Name: COLUMN run.run_writing_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_writing_id IS 'Link to term defining the writing technique used for this run.';


--
-- Name: COLUMN run.run_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_attribution_ids IS 'Links to attributions for this run.';


--
-- Name: COLUMN run.run_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN run.run_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_annotation_ids IS 'Links to commentary on this run.';


--
-- Name: COLUMN run.run_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN run.run_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: run_run_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE run_run_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: run_run_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE run_run_id_seq OWNED BY run.run_id;


--
-- Name: segment; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE segment (
    seg_id integer NOT NULL,
    seg_baseline_ids integer[],
    seg_image_pos polygon[],
    seg_string_pos integer[],
    seg_rotation integer,
    seg_layer integer,
    seg_clarity_id integer,
    seg_obscurations pg_catalog.text[],
    seg_url pg_catalog.text,
    seg_mapped_seg_ids integer[],
    seg_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    seg_owner_id integer DEFAULT 2,
    seg_annotation_ids integer[],
    seg_visibility_ids integer[] DEFAULT '{2}'::integer[],
    seg_scratch pg_catalog.text
);


--
-- Name: TABLE segment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE segment IS 'Contains a record for each agreed and each alternative segment on each Baseline record.';


--
-- Name: COLUMN segment.seg_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_id IS 'Uniquely identifies a particular segment on a particular run on a particular line on particular inscription.';


--
-- Name: COLUMN segment.seg_baseline_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_baseline_ids IS 'Links to the baseline records for this segment.';


--
-- Name: COLUMN segment.seg_image_pos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_image_pos IS 'Polygon defining the segment bounds for an image baseline.';


--
-- Name: COLUMN segment.seg_string_pos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_string_pos IS 'Array of character positions defining the segment bounds for transcription baseline.';


--
-- Name: COLUMN segment.seg_rotation; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_rotation IS 'Degrees of rotation to be applied to the imaged for this segments bounding box.';


--
-- Name: COLUMN segment.seg_layer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_layer IS 'Ordinal which refers to the layering of characters (overwriting).';


--
-- Name: COLUMN segment.seg_clarity_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_clarity_id IS 'link to term identifying the clarity of the script for this segment.';


--
-- Name: COLUMN segment.seg_obscurations; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_obscurations IS 'Identifies any surface alterations which obscure the script for this segment.';


--
-- Name: COLUMN segment.seg_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_url IS 'URL that retrieves cropped segment image or thumb.';


--
-- Name: COLUMN segment.seg_mapped_seg_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_mapped_seg_ids IS 'Links to the mapped segment records for this segment.';


--
-- Name: COLUMN segment.seg_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_attribution_ids IS 'Links to attributions for this segment.';


--
-- Name: COLUMN segment.seg_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN segment.seg_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_annotation_ids IS 'Links to commentary on this segment.';


--
-- Name: COLUMN segment.seg_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN segment.seg_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: segment_seg_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE segment_seg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: segment_seg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE segment_seg_id_seq OWNED BY segment.seg_id;


--
-- Name: sequence; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE sequence (
    seq_id integer NOT NULL,
    seq_label pg_catalog.text,
    seq_type_id integer,
    seq_superscript pg_catalog.text,
    seq_entity_ids character varying(30)[],
    seq_theme_id integer,
    seq_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    seq_owner_id integer DEFAULT 2,
    seq_annotation_ids integer[],
    seq_visibility_ids integer[] DEFAULT '{2}'::integer[],
    seq_scratch pg_catalog.text
);


--
-- Name: TABLE sequence; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE sequence IS 'Contains a record for each sequence.';


--
-- Name: COLUMN sequence.seq_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_id IS 'Uniquely identifies a sequence object.';


--
-- Name: COLUMN sequence.seq_label; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_label IS 'Display label for this sequence.';


--
-- Name: COLUMN sequence.seq_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_type_id IS 'Link to term identifying the type of this sequence.';


--
-- Name: COLUMN sequence.seq_superscript; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_superscript IS 'superscrit identifier for this sequence.';


--
-- Name: COLUMN sequence.seq_entity_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_entity_ids IS 'Ordered list of Entity GlobalIDs that define this sequence.';


--
-- Name: COLUMN sequence.seq_theme_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_theme_id IS 'Link to display theme for this sequence.';


--
-- Name: COLUMN sequence.seq_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_attribution_ids IS 'Links to attributions for this sequence.';


--
-- Name: COLUMN sequence.seq_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN sequence.seq_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_annotation_ids IS 'Links to commentary on this sequence.';


--
-- Name: COLUMN sequence.seq_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN sequence.seq_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: sequence_seq_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE sequence_seq_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sequence_seq_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE sequence_seq_id_seq OWNED BY sequence.seq_id;


--
-- Name: span; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE span (
    spn_id integer NOT NULL,
    spn_type_id integer,
    spn_segment_ids integer[],
    spn_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    spn_owner_id integer DEFAULT 2,
    spn_annotation_ids integer[],
    spn_visibility_ids integer[] DEFAULT '{2}'::integer[],
    spn_scratch pg_catalog.text
);


--
-- Name: TABLE span; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE span IS 'Contains a record for each span.';


--
-- Name: COLUMN span.spn_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_id IS 'Uniquely identifies a particular span.';


--
-- Name: COLUMN span.spn_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_type_id IS 'Link to term defining the type of this span.';


--
-- Name: COLUMN span.spn_segment_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_segment_ids IS 'Array of ids for the segment records for this span.';


--
-- Name: COLUMN span.spn_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_attribution_ids IS 'Links to attributions for this span.';


--
-- Name: COLUMN span.spn_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN span.spn_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_annotation_ids IS 'Links to commentary on this span.';


--
-- Name: COLUMN span.spn_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN span.spn_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: span_spn_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE span_spn_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: span_spn_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE span_spn_id_seq OWNED BY span.spn_id;


--
-- Name: surface; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE surface (
    srf_id integer NOT NULL,
    srf_description pg_catalog.text,
    srf_number integer DEFAULT 1 NOT NULL,
    srf_layer_number integer DEFAULT 1 NOT NULL,
    srf_scripts pg_catalog.text[],
    srf_text_ids integer[],
    srf_reconst_surface_id integer,
    srf_fragment_id integer,
    srf_image_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    srf_annotation_ids integer[],
    srf_visibility_ids integer[] DEFAULT '{2}'::integer[],
    srf_scratch pg_catalog.text
);


--
-- Name: TABLE surface; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE surface IS 'Contains a record for each surface of a fragment.';


--
-- Name: COLUMN surface.srf_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_id IS 'Uniquely identifies particular surface.';


--
-- Name: COLUMN surface.srf_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_description IS 'Free text description of surface.';


--
-- Name: COLUMN surface.srf_number; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_number IS 'Fragment Unique Number identifying particular surface.';


--
-- Name: COLUMN surface.srf_layer_number; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_layer_number IS 'Fragment Unique Number identifying particular layer of the surface.';


--
-- Name: COLUMN surface.srf_scripts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_scripts IS 'SCRIPTS used for TEXT on the surface.';


--
-- Name: COLUMN surface.srf_text_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_text_ids IS 'Links to a text entities.';


--
-- Name: COLUMN surface.srf_reconst_surface_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_reconst_surface_id IS 'Link to reconstructed surface entity.';


--
-- Name: COLUMN surface.srf_fragment_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_fragment_id IS 'Link to fragment this surface lies on.';


--
-- Name: COLUMN surface.srf_image_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_image_ids IS 'Links to images of this surface.';


--
-- Name: COLUMN surface.srf_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_annotation_ids IS 'Links to commentary on this surface.';


--
-- Name: COLUMN surface.srf_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN surface.srf_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: surface_srf_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE surface_srf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: surface_srf_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE surface_srf_id_seq OWNED BY surface.srf_id;


--
-- Name: syllablecluster; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE syllablecluster (
    scl_id integer NOT NULL,
    scl_segment_id integer,
    scl_grapheme_ids integer[],
    scl_text_critical_mark pg_catalog.text,
    scl_sort_code pg_catalog.text,
    scl_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    scl_owner_id integer DEFAULT 2,
    scl_annotation_ids integer[],
    scl_visibility_ids integer[] DEFAULT '{2}'::integer[],
    scl_scratch pg_catalog.text
);


--
-- Name: TABLE syllablecluster; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE syllablecluster IS 'Contains a record for each syllable cluster for a segment.';


--
-- Name: COLUMN syllablecluster.scl_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_id IS 'Uniquely identifies a particular syllable cluster.';


--
-- Name: COLUMN syllablecluster.scl_segment_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_segment_id IS 'Links to the segment records for this syllable cluster.';


--
-- Name: COLUMN syllablecluster.scl_grapheme_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_grapheme_ids IS 'Array of grapheme ids that make up this syllable cluster.';


--
-- Name: COLUMN syllablecluster.scl_text_critical_mark; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_text_critical_mark IS 'Critical Marking used to identify aspects of the script critical to this interpretation.';


--
-- Name: COLUMN syllablecluster.scl_sort_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_sort_code IS 'Code used to order clusters.';


--
-- Name: COLUMN syllablecluster.scl_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_attribution_ids IS 'Links to attributions for this segment.';


--
-- Name: COLUMN syllablecluster.scl_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN syllablecluster.scl_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_annotation_ids IS 'Links to commentary on this segment.';


--
-- Name: COLUMN syllablecluster.scl_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN syllablecluster.scl_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: syllablecluster_scl_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE syllablecluster_scl_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: syllablecluster_scl_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE syllablecluster_scl_id_seq OWNED BY syllablecluster.scl_id;


--
-- Name: term; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE term (
    trm_id integer NOT NULL,
    trm_labels pg_catalog.text DEFAULT 'Need Labels'::pg_catalog.text NOT NULL,
    trm_parent_id integer DEFAULT 1,
    trm_type_id integer DEFAULT 1,
    trm_list_ids integer[],
    trm_code pg_catalog.text,
    trm_description pg_catalog.text,
    trm_url pg_catalog.text,
    trm_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    trm_owner_id integer DEFAULT 2,
    trm_annotation_ids integer[],
    trm_visibility_ids integer[] DEFAULT '{2}'::integer[],
    trm_scratch pg_catalog.text
);


--
-- Name: TABLE term; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE term IS 'Contains a record for each term.';


--
-- Name: COLUMN term.trm_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_id IS 'Uniquely identifies a term object.';


--
-- Name: COLUMN term.trm_labels; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_labels IS 'ISO Language code : label pairs for this term.';


--
-- Name: COLUMN term.trm_parent_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_parent_id IS 'Link to parent term.';


--
-- Name: COLUMN term.trm_type_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_type_id IS 'Link to term identifying the type of this term.';


--
-- Name: COLUMN term.trm_list_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_list_ids IS 'Links to terms contained in this termlist.';


--
-- Name: COLUMN term.trm_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_code IS 'Code for this term.';


--
-- Name: COLUMN term.trm_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_description IS 'Description of the term.';


--
-- Name: COLUMN term.trm_url; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_url IS 'URL to definition of this term.';


--
-- Name: COLUMN term.trm_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_attribution_ids IS 'Links to attributions for this term.';


--
-- Name: COLUMN term.trm_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN term.trm_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_annotation_ids IS 'Links to commentary on this term.';


--
-- Name: COLUMN term.trm_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN term.trm_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: term_trm_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE term_trm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: term_trm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE term_trm_id_seq OWNED BY term.trm_id;


--
-- Name: text; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE text (
    txt_id integer NOT NULL,
    txt_ckn pg_catalog.text DEFAULT 'Need CKN'::pg_catalog.text NOT NULL,
    txt_title pg_catalog.text,
    txt_ref pg_catalog.text,
    txt_type_ids integer[],
    txt_replacement_ids integer[],
    txt_edition_ref_ids integer[],
    txt_image_ids integer[],
    txt_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    txt_jsoncache_id integer,
    txt_owner_id integer DEFAULT 2,
    txt_annotation_ids integer[],
    txt_visibility_ids integer[] DEFAULT '{2}'::integer[],
    txt_scratch pg_catalog.text
);


--
-- Name: TABLE text; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE text IS 'Contains a record for each text of a part.';


--
-- Name: COLUMN text.txt_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_id IS 'Uniquely identifies particular text.';


--
-- Name: COLUMN text.txt_ckn; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_ckn IS 'Label used in research to identify this text.';


--
-- Name: COLUMN text.txt_title; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_title IS 'More descriptive string to identify this text.';


--
-- Name: COLUMN text.txt_ref; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_ref IS 'A short string to identify this text.';


--
-- Name: COLUMN text.txt_type_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_type_ids IS 'Links to terms used to categorise this text.';


--
-- Name: COLUMN text.txt_replacement_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_replacement_ids IS 'Forwarding links to text entities.';


--
-- Name: COLUMN text.txt_edition_ref_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_edition_ref_ids IS 'attrbID to reference Attribute entity.';


--
-- Name: COLUMN text.txt_image_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_image_ids IS 'Links to images of this text.';


--
-- Name: COLUMN text.txt_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_attribution_ids IS 'Links to attributions for this text.';


--
-- Name: COLUMN text.txt_jsoncache_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_jsoncache_id IS 'Link to jsoncache entry for the entities of this text.';


--
-- Name: COLUMN text.txt_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN text.txt_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_annotation_ids IS 'Links to commentary on this text.';


--
-- Name: COLUMN text.txt_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN text.txt_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: text_txt_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE text_txt_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: text_txt_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE text_txt_id_seq OWNED BY text.txt_id;


--
-- Name: textmetadata; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE textmetadata (
    tmd_id integer NOT NULL,
    tmd_text_id integer,
    tmd_type_ids integer[],
    tmd_reference_ids integer[],
    tmd_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    tmd_owner_id integer DEFAULT 2,
    tmd_annotation_ids integer[],
    tmd_visibility_ids integer[] DEFAULT '{2}'::integer[],
    tmd_scratch pg_catalog.text
);


--
-- Name: TABLE textmetadata; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE textmetadata IS 'Contains a record for each textMetadata.';


--
-- Name: COLUMN textmetadata.tmd_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_id IS 'Uniquely identifies a textMetadata object.';


--
-- Name: COLUMN textmetadata.tmd_text_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_text_id IS 'Link to text entity of this textMetadata.';


--
-- Name: COLUMN textmetadata.tmd_type_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_type_ids IS 'TextType Term IDs defining the type or category for the text of this textMetadata.';


--
-- Name: COLUMN textmetadata.tmd_reference_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_reference_ids IS 'attrbIDs to consulted reference Attribute entity.';


--
-- Name: COLUMN textmetadata.tmd_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_attribution_ids IS 'Links to attributions for this textMetadata.';


--
-- Name: COLUMN textmetadata.tmd_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN textmetadata.tmd_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_annotation_ids IS 'Links to commentary on this textMetadata.';


--
-- Name: COLUMN textmetadata.tmd_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN textmetadata.tmd_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: textmetadata_tmd_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE textmetadata_tmd_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: textmetadata_tmd_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE textmetadata_tmd_id_seq OWNED BY textmetadata.tmd_id;


--
-- Name: token; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE token (
    tok_id integer NOT NULL,
    tok_value pg_catalog.text,
    tok_transcription pg_catalog.text,
    tok_grapheme_ids integer[],
    tok_nom_affix pg_catalog.text,
    tok_sort_code pg_catalog.text,
    tok_sort_code2 pg_catalog.text,
    tok_attribution_ids integer[],
    modified timestamp without time zone DEFAULT now(),
    tok_owner_id integer DEFAULT 2,
    tok_annotation_ids integer[],
    tok_visibility_ids integer[] DEFAULT '{2}'::integer[],
    tok_scratch pg_catalog.text
);


--
-- Name: TABLE token; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE token IS 'Contains a record for each token.';


--
-- Name: COLUMN token.tok_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_id IS 'Uniquely identifies a particular syllable on a particular segment on a particular run on a particular line on particular inscription.';


--
-- Name: COLUMN token.tok_value; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_value IS 'Display value (calc) of the token.';


--
-- Name: COLUMN token.tok_transcription; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_transcription IS 'transcription value (calc) of the token.';


--
-- Name: COLUMN token.tok_grapheme_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_grapheme_ids IS 'Set of grapheme IDs that make up this token.';


--
-- Name: COLUMN token.tok_nom_affix; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_nom_affix IS 'Used where the token is comprised of a transcription symbol only.';


--
-- Name: COLUMN token.tok_sort_code; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_sort_code IS 'Primary Code used to order tokens.';


--
-- Name: COLUMN token.tok_sort_code2; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_sort_code2 IS 'Secondary Code used to order tokens.';


--
-- Name: COLUMN token.tok_attribution_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_attribution_ids IS 'Links to attributions for this token.';


--
-- Name: COLUMN token.tok_owner_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_owner_id IS 'Link to owner usergroup.';


--
-- Name: COLUMN token.tok_annotation_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_annotation_ids IS 'Links to commentary on this token.';


--
-- Name: COLUMN token.tok_visibility_ids; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN token.tok_visibility_ids IS 'Links to usergroups that can view.';


--
-- Name: token_tok_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE token_tok_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: token_tok_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE token_tok_id_seq OWNED BY token.tok_id;


--
-- Name: usergroup; Type: TABLE; Schema: public; Owner: -; Tablespace:
--

CREATE TABLE usergroup (
    ugr_id integer NOT NULL,
    ugr_name pg_catalog.text NOT NULL,
    ugr_type_id integer DEFAULT 335 NOT NULL,
    ugr_given_name pg_catalog.text,
    ugr_family_name pg_catalog.text,
    ugr_description pg_catalog.text,
    ugr_password pg_catalog.text,
    modified timestamp without time zone DEFAULT now(),
    ugr_member_ids integer[],
    ugr_admin_ids integer[],
    ugr_scratch pg_catalog.text
);


--
-- Name: usergroup_ugr_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE usergroup_ugr_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usergroup_ugr_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE usergroup_ugr_id_seq OWNED BY usergroup.ugr_id;


--
-- Name: ano_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY annotation ALTER COLUMN ano_id SET DEFAULT nextval('annotation_ano_id_seq'::regclass);


--
-- Name: atb_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY attribution ALTER COLUMN atb_id SET DEFAULT nextval('attribution_atb_id_seq'::regclass);


--
-- Name: atg_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY attributiongroup ALTER COLUMN atg_id SET DEFAULT nextval('attributiongroup_atg_id_seq'::regclass);


--
-- Name: bln_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY baseline ALTER COLUMN bln_id SET DEFAULT nextval('baseline_bln_id_seq'::regclass);


--
-- Name: bib_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY bibliography ALTER COLUMN bib_id SET DEFAULT nextval('bibliography_bib_id_seq'::regclass);


--
-- Name: cat_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY catalog ALTER COLUMN cat_id SET DEFAULT nextval('catalog_cat_id_seq'::regclass);


--
-- Name: col_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY collection ALTER COLUMN col_id SET DEFAULT nextval('collection_col_id_seq'::regclass);


--
-- Name: cmp_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY compound ALTER COLUMN cmp_id SET DEFAULT nextval('compound_cmp_id_seq'::regclass);


--
-- Name: dat_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY date ALTER COLUMN dat_id SET DEFAULT nextval('date_dat_id_seq'::regclass);


--
-- Name: edn_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY edition ALTER COLUMN edn_id SET DEFAULT nextval('edition_edn_id_seq'::regclass);


--
-- Name: era_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY era ALTER COLUMN era_id SET DEFAULT nextval('era_era_id_seq'::regclass);


--
-- Name: frg_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY fragment ALTER COLUMN frg_id SET DEFAULT nextval('fragment_frg_id_seq'::regclass);


--
-- Name: gra_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY grapheme ALTER COLUMN gra_id SET DEFAULT nextval('grapheme_gra_id_seq'::regclass);


--
-- Name: img_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY image ALTER COLUMN img_id SET DEFAULT nextval('image_img_id_seq'::regclass);


--
-- Name: inf_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY inflection ALTER COLUMN inf_id SET DEFAULT nextval('inflection_inf_id_seq'::regclass);


--
-- Name: itm_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY item ALTER COLUMN itm_id SET DEFAULT nextval('item_itm_id_seq'::regclass);


--
-- Name: jsc_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY jsoncache ALTER COLUMN jsc_id SET DEFAULT nextval('jsoncache_jsc_id_seq'::regclass);


--
-- Name: lem_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma ALTER COLUMN lem_id SET DEFAULT nextval('lemma_lem_id_seq'::regclass);


--
-- Name: lin_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY line ALTER COLUMN lin_id SET DEFAULT nextval('line_lin_id_seq'::regclass);


--
-- Name: mcx_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY materialcontext ALTER COLUMN mcx_id SET DEFAULT nextval('materialcontext_mcx_id_seq'::regclass);


--
-- Name: prt_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY part ALTER COLUMN prt_id SET DEFAULT nextval('part_prt_id_seq'::regclass);


--
-- Name: prn_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY propernoun ALTER COLUMN prn_id SET DEFAULT nextval('propernoun_prn_id_seq'::regclass);


--
-- Name: run_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY run ALTER COLUMN run_id SET DEFAULT nextval('run_run_id_seq'::regclass);


--
-- Name: seg_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY segment ALTER COLUMN seg_id SET DEFAULT nextval('segment_seg_id_seq'::regclass);


--
-- Name: seq_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY sequence ALTER COLUMN seq_id SET DEFAULT nextval('sequence_seq_id_seq'::regclass);


--
-- Name: spn_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY span ALTER COLUMN spn_id SET DEFAULT nextval('span_spn_id_seq'::regclass);


--
-- Name: srf_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY surface ALTER COLUMN srf_id SET DEFAULT nextval('surface_srf_id_seq'::regclass);


--
-- Name: scl_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY syllablecluster ALTER COLUMN scl_id SET DEFAULT nextval('syllablecluster_scl_id_seq'::regclass);


--
-- Name: trm_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY term ALTER COLUMN trm_id SET DEFAULT nextval('term_trm_id_seq'::regclass);


--
-- Name: txt_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY text ALTER COLUMN txt_id SET DEFAULT nextval('text_txt_id_seq'::regclass);


--
-- Name: tmd_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY textmetadata ALTER COLUMN tmd_id SET DEFAULT nextval('textmetadata_tmd_id_seq'::regclass);


--
-- Name: tok_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY token ALTER COLUMN tok_id SET DEFAULT nextval('token_tok_id_seq'::regclass);


--
-- Name: ugr_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY usergroup ALTER COLUMN ugr_id SET DEFAULT nextval('usergroup_ugr_id_seq'::regclass);


--
-- Data for Name: annotation; Type: TABLE DATA; Schema: public; Owner: -
--

COPY annotation (ano_id, ano_linkfrom_ids, ano_linkto_ids, ano_type_id, ano_text, ano_url, ano_annotation_ids, ano_attribution_ids, modified, ano_owner_id, ano_visibility_ids, ano_scratch) FROM stdin;
\.


--
-- Name: annotation_ano_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('annotation_ano_id_seq', 1, false);


--
-- Data for Name: attribution; Type: TABLE DATA; Schema: public; Owner: -
--

COPY attribution (atb_id, atb_title, atb_types, atb_bib_id, atb_detail, atb_description, atb_group_id, modified, atb_owner_id, atb_annotation_ids, atb_visibility_ids, atb_scratch) FROM stdin;
\.


--
-- Name: attribution_atb_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('attribution_atb_id_seq', 1, false);


--
-- Data for Name: attributiongroup; Type: TABLE DATA; Schema: public; Owner: -
--

COPY attributiongroup (atg_id, atg_name, atg_type_id, atg_realname, atg_date_created, atg_description, atg_member_ids, atg_admin_ids, atg_attribution_ids, modified, atg_owner_id, atg_annotation_ids, atg_visibility_ids, atg_scratch) FROM stdin;
\.


--
-- Name: attributiongroup_atg_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('attributiongroup_atg_id_seq', 1, false);


--
-- Data for Name: baseline; Type: TABLE DATA; Schema: public; Owner: -
--

COPY baseline (bln_id, bln_type_id, bln_image_id, bln_surface_id, bln_image_position, bln_transcription, bln_attribution_ids, modified, bln_owner_id, bln_annotation_ids, bln_visibility_ids, bln_scratch) FROM stdin;
\.


--
-- Name: baseline_bln_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('baseline_bln_id_seq', 1, false);


--
-- Data for Name: bibliography; Type: TABLE DATA; Schema: public; Owner: -
--

COPY bibliography (bib_id, bib_name, bib_attribution_ids, modified, bib_owner_id, bib_annotation_ids, bib_visibility_ids, bib_scratch) FROM stdin;
\.


--
-- Name: bibliography_bib_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('bibliography_bib_id_seq', 1, false);


--
-- Data for Name: catalog; Type: TABLE DATA; Schema: public; Owner: -
--

COPY catalog (cat_id, cat_title, cat_type_id, cat_lang_id, cat_description, cat_edition_ids, cat_attribution_ids, modified, cat_owner_id, cat_annotation_ids, cat_visibility_ids, cat_scratch) FROM stdin;
\.


--
-- Name: catalog_cat_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('catalog_cat_id_seq', 1, false);


--
-- Data for Name: collection; Type: TABLE DATA; Schema: public; Owner: -
--

COPY collection (col_id, col_title, col_location_refs, col_description, col_item_part_fragment_ids, col_exclude_part_fragment_ids, col_attribution_ids, modified, col_owner_id, col_annotation_ids, col_visibility_ids, col_scratch) FROM stdin;
\.


--
-- Name: collection_col_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('collection_col_id_seq', 1, false);


--
-- Data for Name: compound; Type: TABLE DATA; Schema: public; Owner: -
--

COPY compound (cmp_id, cmp_value, cmp_transcription, cmp_component_ids, cmp_case_id, cmp_class_id, cmp_type_id, cmp_sort_code, cmp_sort_code2, cmp_attribution_ids, modified, cmp_owner_id, cmp_annotation_ids, cmp_visibility_ids, cmp_scratch) FROM stdin;
\.


--
-- Name: compound_cmp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('compound_cmp_id_seq', 1, false);


--
-- Data for Name: date; Type: TABLE DATA; Schema: public; Owner: -
--

COPY date (dat_id, dat_prob_begin_date, dat_prob_end_date, dat_entity_id, dat_evidences, dat_preferred_era_id, dat_era_ids, dat_attribution_ids, modified, dat_owner_id, dat_annotation_ids, dat_visibility_ids, dat_scratch) FROM stdin;
\.


--
-- Name: date_dat_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('date_dat_id_seq', 1, false);


--
-- Data for Name: edition; Type: TABLE DATA; Schema: public; Owner: -
--

COPY edition (edn_id, edn_description, edn_sequence_ids, edn_text_id, edn_type_id, edn_attribution_ids, modified, edn_owner_id, edn_annotation_ids, edn_visibility_ids, edn_scratch) FROM stdin;
\.


--
-- Name: edition_edn_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('edition_edn_id_seq', 1, false);


--
-- Data for Name: era; Type: TABLE DATA; Schema: public; Owner: -
--

COPY era (era_id, era_title, era_begin_date, era_end_date, era_order, era_preferred, era_attribution_ids, modified, era_owner_id, era_annotation_ids, era_visibility_ids, era_scratch) FROM stdin;
\.


--
-- Name: era_era_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('era_era_id_seq', 1, false);


--
-- Data for Name: fragment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY fragment (frg_id, frg_label, frg_description, frg_measure, frg_restore_state_id, frg_location_refs, frg_part_id, frg_material_context_ids, frg_image_ids, frg_attribution_ids, modified, frg_owner_id, frg_annotation_ids, frg_visibility_ids, frg_scratch) FROM stdin;
\.


--
-- Name: fragment_frg_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('fragment_frg_id_seq', 1, false);


--
-- Data for Name: grapheme; Type: TABLE DATA; Schema: public; Owner: -
--

COPY grapheme (gra_id, gra_grapheme, gra_uppercase, gra_type_id, gra_text_critical_mark, gra_alt, gra_emmendation, gra_decomposition, gra_sort_code, modified, gra_owner_id, gra_annotation_ids, gra_visibility_ids, gra_scratch) FROM stdin;
\.


--
-- Name: grapheme_gra_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('grapheme_gra_id_seq', 1, false);


--
-- Data for Name: image; Type: TABLE DATA; Schema: public; Owner: -
--

COPY image (img_id, img_title, img_type_id, img_url, img_image_pos, img_attribution_ids, modified, img_owner_id, img_annotation_ids, img_visibility_ids, img_scratch) FROM stdin;
\.


--
-- Name: image_img_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('image_img_id_seq', 1, false);


--
-- Data for Name: inflection; Type: TABLE DATA; Schema: public; Owner: -
--

COPY inflection (inf_id, inf_chaya, inf_component_ids, inf_certainty, inf_case_id, inf_nominal_gender_id, inf_gram_number_id, inf_verb_person_id, inf_verb_voice_id, inf_verb_tense_id, inf_verb_mood_id, inf_verb_second_conj_id, inf_attribution_ids, modified, inf_owner_id, inf_annotation_ids, inf_visibility_ids, inf_scratch) FROM stdin;
\.


--
-- Name: inflection_inf_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('inflection_inf_id_seq', 1, false);


--
-- Data for Name: item; Type: TABLE DATA; Schema: public; Owner: -
--

COPY item (itm_id, itm_title, itm_type_id, itm_shape_id, itm_measure, itm_image_ids, modified, itm_owner_id, itm_annotation_ids, itm_visibility_ids, itm_scratch) FROM stdin;
\.


--
-- Name: item_itm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('item_itm_id_seq', 1, false);


--
-- Data for Name: jsoncache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY jsoncache (jsc_id, jsc_label, jsc_type_id, jsc_json_string, jsc_attribution_ids, modified, jsc_owner_id, jsc_annotation_ids, jsc_visibility_ids, jsc_scratch) FROM stdin;
\.


--
-- Name: jsoncache_jsc_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('jsoncache_jsc_id_seq', 1, false);


--
-- Data for Name: lemma; Type: TABLE DATA; Schema: public; Owner: -
--

COPY lemma (lem_id, lem_value, lem_search, lem_translation, lem_homographorder, lem_type_id, lem_certainty, lem_part_of_speech_id, lem_subpart_of_speech_id, lem_nominal_gender_id, lem_verb_class_id, lem_declension_id, lem_description, lem_catalog_id, lem_component_ids, lem_sort_code, lem_sort_code2, lem_attribution_ids, modified, lem_owner_id, lem_annotation_ids, lem_visibility_ids, lem_scratch) FROM stdin;
\.


--
-- Name: lemma_lem_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('lemma_lem_id_seq', 1, false);


--
-- Data for Name: line; Type: TABLE DATA; Schema: public; Owner: -
--

COPY line (lin_id, lin_order, lin_mask, lin_span_ids, lin_annotation_ids, lin_attribution_ids, modified, lin_owner_id, lin_visibility_ids, lin_scratch) FROM stdin;
\.


--
-- Name: line_lin_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('line_lin_id_seq', 1, false);


--
-- Data for Name: materialcontext; Type: TABLE DATA; Schema: public; Owner: -
--

COPY materialcontext (mcx_id, mcx_arch_context, mcx_find_status, mcx_attribution_ids, modified, mcx_owner_id, mcx_annotation_ids, mcx_visibility_ids, mcx_scratch) FROM stdin;
\.


--
-- Name: materialcontext_mcx_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('materialcontext_mcx_id_seq', 1, false);


--
-- Data for Name: part; Type: TABLE DATA; Schema: public; Owner: -
--

COPY part (prt_id, prt_label, prt_type_id, prt_shape_id, prt_mediums, prt_measure, prt_manufacture_id, prt_sequence, prt_item_id, prt_image_ids, modified, prt_owner_id, prt_annotation_ids, prt_visibility_ids, prt_scratch) FROM stdin;
\.


--
-- Name: part_prt_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('part_prt_id_seq', 1, false);


--
-- Data for Name: propernoun; Type: TABLE DATA; Schema: public; Owner: -
--

COPY propernoun (prn_id, prn_labels, prn_type_id, prn_evidences, prn_description, prn_url, prn_attribution_ids, modified, prn_owner_id, prn_annotation_ids, prn_visibility_ids, prn_scratch) FROM stdin;
\.


--
-- Name: propernoun_prn_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('propernoun_prn_id_seq', 1, false);


--
-- Data for Name: run; Type: TABLE DATA; Schema: public; Owner: -
--

COPY run (run_id, run_scribe_id, run_text_id, run_baseline_id, run_image_pos, run_script_id, run_writing_id, run_attribution_ids, modified, run_owner_id, run_annotation_ids, run_visibility_ids, run_scratch) FROM stdin;
\.


--
-- Name: run_run_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('run_run_id_seq', 1, false);


--
-- Data for Name: segment; Type: TABLE DATA; Schema: public; Owner: -
--

COPY segment (seg_id, seg_baseline_ids, seg_image_pos, seg_string_pos, seg_rotation, seg_layer, seg_clarity_id, seg_obscurations, seg_url, seg_mapped_seg_ids, seg_attribution_ids, modified, seg_owner_id, seg_annotation_ids, seg_visibility_ids, seg_scratch) FROM stdin;
\.


--
-- Name: segment_seg_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('segment_seg_id_seq', 1, false);


--
-- Data for Name: sequence; Type: TABLE DATA; Schema: public; Owner: -
--

COPY sequence (seq_id, seq_label, seq_type_id, seq_superscript, seq_entity_ids, seq_theme_id, seq_attribution_ids, modified, seq_owner_id, seq_annotation_ids, seq_visibility_ids, seq_scratch) FROM stdin;
\.


--
-- Name: sequence_seq_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('sequence_seq_id_seq', 1, false);


--
-- Data for Name: span; Type: TABLE DATA; Schema: public; Owner: -
--

COPY span (spn_id, spn_type_id, spn_segment_ids, spn_attribution_ids, modified, spn_owner_id, spn_annotation_ids, spn_visibility_ids, spn_scratch) FROM stdin;
\.


--
-- Name: span_spn_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('span_spn_id_seq', 1, false);


--
-- Data for Name: surface; Type: TABLE DATA; Schema: public; Owner: -
--

COPY surface (srf_id, srf_description, srf_number, srf_layer_number, srf_scripts, srf_text_ids, srf_reconst_surface_id, srf_fragment_id, srf_image_ids, modified, srf_annotation_ids, srf_visibility_ids, srf_scratch) FROM stdin;
\.


--
-- Name: surface_srf_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('surface_srf_id_seq', 1, false);


--
-- Data for Name: syllablecluster; Type: TABLE DATA; Schema: public; Owner: -
--

COPY syllablecluster (scl_id, scl_segment_id, scl_grapheme_ids, scl_text_critical_mark, scl_sort_code, scl_attribution_ids, modified, scl_owner_id, scl_annotation_ids, scl_visibility_ids, scl_scratch) FROM stdin;
\.


--
-- Name: syllablecluster_scl_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('syllablecluster_scl_id_seq', 1, false);


--
-- Data for Name: term; Type: TABLE DATA; Schema: public; Owner: -
--

COPY term (trm_id, trm_labels, trm_parent_id, trm_type_id, trm_list_ids, trm_code, trm_description, trm_url, trm_attribution_ids, modified, trm_owner_id, trm_annotation_ids, trm_visibility_ids, trm_scratch) FROM stdin;
1	en=>"SystemOntology"	\N	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
2	en=>"SystemEntity"	1	782	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
3	en=>"ArchaeologicalContext"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
4	en=>"ArchaeologicalStructure"	3	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
5	en=>"Stupa"	4	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
6	en=>"Cave"	4	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
7	en=>"AnnotationType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
8	en=>"TagType"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
9	en=>"Findspot"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
10	en=>"Places"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
11	en=>"School"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
12	en=>"Location"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
13	en=>"Room"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
14	en=>"Ruin"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
15	en=>"Site"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
16	en=>"Parrallels"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
17	en=>"Adressee"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
18	en=>"Concerning"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
19	en=>"Holder"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
20	en=>"Owner"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
21	en=>"Plaintiff"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
22	en=>"Ruler"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
23	en=>"DonorStatus"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
24	en=>"Sender"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
25	en=>"Scribe"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
26	en=>"Dynasty"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
27	en=>"Apraca"	26	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
28	en=>"Oḍi"	26	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
29	en=>"Medium"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
30	en=>"Birch bark"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
31	en=>"Palm leaf"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
32	en=>"Paper"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
33	en=>"silk"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
34	en=>"Chalcedony"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
35	en=>"Steatite"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
36	en=>"Agate"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
37	en=>"Black glass"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
38	en=>"Red stone"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
39	en=>"Rock Crystal"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
40	en=>"Cornelian"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
41	en=>"Stone"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
42	en=>"pottery"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
43	en=>"Grey schist"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
44	en=>"Terracotta"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
45	en=>"Marble"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
46	en=>"Clay"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
47	en=>"Schist"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
48	en=>"Silver"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
49	en=>"Plaster"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
50	en=>"Slate"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
51	en=>"gold"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
52	en=>"Pottery, black"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
53	en=>"Green schist"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
54	en=>"Stucco"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
55	en=>"Bronze"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
56	en=>"Green phyllite"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
57	en=>"gemstone"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
58	en=>"crystal"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
59	en=>"brass"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
60	en=>"glass"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
61	en=>"Gilded silver"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
62	en=>"Granite"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
63	en=>"Earthware"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
64	en=>"Copper"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
65	en=>"Schist, blue"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
66	en=>"Gray schist"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
67	en=>"Schist, green"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
68	en=>"Garnet intaglio"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
69	en=>"Limestone"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
70	en=>"Gilded bronze"	29	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
71	en=>"Subject"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
72	en=>"Small box"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
73	en=>"Bhadavala"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
74	en=>"Brick"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
75	en=>"Pond"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
76	en=>"Frieze, Buddha, Maitreya, Vajrapāṇi"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
77	en=>"Stone"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
78	en=>"Sculpture"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
79	en=>"Reliquary"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
80	en=>"Buddha statue"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
81	en=>"Plaque"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
82	en=>"Well"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
83	en=>"Bowl"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
84	en=>"Lotus pond"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
85	en=>"Unknown"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
86	en=>"Relief fragment"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
87	en=>"Mural"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
88	en=>"Collar‐bone legend"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
89	en=>"Rock"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
90	en=>"Relief"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
91	en=>"Sherds"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
92	en=>"Intaglio"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
93	en=>"Frieze fragment"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
94	en=>"Sherd"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
95	en=>"Goblet"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
96	en=>"Pavement stone"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
97	en=>"Round stone"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
98	en=>"Sculpture, Stūpa"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
99	en=>"Lamp"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
100	en=>"Boulder"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
101	en=>"Garland holder bracket"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
102	en=>"Buddha footprints"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
103	en=>"Writing-board"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
104	en=>"Pedestal"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
105	en=>"Plate"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
106	en=>"Water-giver"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
107	en=>"Cornelian"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
108	en=>"Kārṣāpanas"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
109	en=>"Tank"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
110	en=>"Frieze"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
111	en=>"Seal"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
112	en=>"Pillar"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
113	en=>"Jar (fragment)"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
114	en=>"Toilet-tray"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
115	en=>"Inkwell"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
116	en=>"Vase"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
117	en=>"Cup"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
118	en=>"Sieve"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
119	en=>"Volute bracket (with winged male figure)"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
120	en=>"Ring"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
121	en=>"Token"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
122	en=>"Rock wall"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
123	en=>"Ring seal"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
124	en=>"Pot"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
125	en=>"Ladle"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
126	en=>"Buddha statue, Sculpture"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
127	en=>"Well, Water giver"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
128	en=>"Slab"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
129	en=>"Auspicious ground"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
130	en=>"Stūpa"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
131	en=>"Jar (fragments)"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
132	en=>"Six lamps"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
133	en=>"Model stūpa pedestal"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
134	en=>"Dabber"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
135	en=>"Bodhisattva"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
136	en=>"Mould (for ear‐pendants)"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
137	en=>"Chapel"	71	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
138	en=>"Type"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
139	en=>"Seal"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
140	en=>"Donation ?"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
141	en=>"Private Donation"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
142	en=>"Graffito"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
143	en=>"Location mark"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
144	en=>"Royal Donation"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
145	en=>"Donation"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
146	en=>"Label"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
147	en=>"Royal Edict"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
148	en=>"Miscellaneous"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
149	en=>"Uncertain"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
150	en=>"Lost"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
151	en=>"Forgery"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
152	en=>"Arapacana"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
153	en=>"Seal, oval"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
154	en=>"Date"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
155	en=>"Seal, round"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
156	en=>"Wedge covering‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
157	en=>"Oblong tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
158	en=>"Takhti‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
159	en=>"Rectangular under‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
160	en=>"Stick‐like tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
161	en=>"Silk Strip (Hedin No. 34:65)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
162	en=>"Document on Leather"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
163	en=>"Double‐wedge tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
164	en=>"Rectangular double tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
165	en=>"Label‐like tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
166	en=>"Wedge covering‐tablet (in fragments)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
167	en=>"Part of tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
168	en=>"Wedge under‐tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
169	en=>"Wedge covering‐tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
170	en=>"Label‐like tablet (fragments)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
171	en=>"Rectangular covering‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
172	en=>"Wedge label‐like tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
173	en=>"Wedge under‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
174	en=>"Fragment of covering‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
175	en=>"Fragment of paper MS"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
176	en=>"Strip of fine silk"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
177	en=>"Fragment of fine silk"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
178	en=>"Rectangular under‐tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
179	en=>"Tablet made of cleft stick"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
180	en=>"Rude wooden disk"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
181	en=>"Rectangular covering‐tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
182	en=>"Leather document"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
183	en=>"Piece of tamarisk wood"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
184	en=>"Three fragments of paper MS"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
185	en=>"Two fragments of thick paper"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
186	en=>"Chip off wooden slip"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
187	en=>"Fragment of tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
188	en=>"(none)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
189	en=>"Slip‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
190	en=>"Wedge‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
191	en=>"Rectangular covering‐tablet (broken)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
192	en=>"Rectangular tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
193	en=>"Strip of leather document"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
194	en=>"Wedge under‐tablet (two fragments)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
195	en=>"Small tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
196	en=>"Lath‐like tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
197	en=>"Oblong tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
198	en=>"Elongated oval tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
199	en=>"Stick‐like tablet (with fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
200	en=>"Club‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
201	en=>"Inscribed tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
202	en=>"Takhti‐shaped tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
203	en=>"Rectangular fragment"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
204	en=>"Parabolic tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
205	en=>"Tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
206	en=>"Wedge‐covering tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
207	en=>"Spearhead‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
208	en=>"Fragment of paper"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
209	en=>"Small silk bag"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
210	en=>"Wedge under‐tablet (broken)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
211	en=>"Document on Leather (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
212	en=>"Label‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
213	en=>"Wedge tablet (fragment)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
214	en=>"Wedge tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
215	en=>"Double wedge‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
216	en=>"Half of rectangular covering‐tablet with seal"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
217	en=>"Tablet formed of bough"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
218	en=>"Oblong covering‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
219	en=>"Oblong under ‐tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
220	en=>"Tablet (nearly square)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
221	en=>"Oblong tablet (fragment) [wedge tablet fragment]"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
222	en=>"Wedge under‐tablet (?)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
223	en=>"Wedge under‐tablet (broken in 6 pieces)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
224	en=>"Half of oblong tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
225	en=>"Oblong label‐like tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
226	en=>"Two wedge‐shaped tablets"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
227	en=>"Large oblong tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
228	en=>"Oblong board"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
229	en=>"Elliptical tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
230	en=>"Tounge‐shaped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
231	en=>"Wedge covering‐tablet (cut)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
232	en=>"Slip‐like tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
233	en=>"Irregular oblong tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
234	en=>"Oval‐topped tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
235	en=>"Paper MS"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
236	en=>"Wedge under‐tablet (in fragments)"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
237	en=>"Wooden Tablet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
238	en=>"Lower Part of Wedge"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
239	en=>"Wood Strip"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
240	en=>"Niya Document"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
241	en=>"Wooden Board"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
242	en=>"Long tablet formed of bough"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
243	en=>"Single sheet"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
244	en=>"Poṭhī"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
245	en=>"Long scroll"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
246	en=>"Composite"	138	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
247	en=>"Color"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
248	en=>"Red"	247	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
249	en=>"Green"	247	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
250	en=>"Blue"	247	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
262	en=>"CustomType"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
263	en=>"StickyNoteType"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
264	en=>"CommentaryType"	263	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
265	en=>"Question"	264	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
266	en=>"Comment"	264	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
267	en=>"Issue"	264	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
268	en=>"WorkflowType"	263	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
269	en=>"ToDo"	268	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
270	en=>"InProgress"	268	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
271	en=>"Done"	268	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
272	en=>"State"	268	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
273	en=>"Obsolete"	268	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
274	en=>"FootNoteType"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
275	en=>"FootNote"	274	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
276	en=>"Transcription"	275	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
277	en=>"Reconstruction"	275	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
278	en=>"LinkageType"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
279	en=>"SchoolAssociatedContext"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
280	en=>"IsSanghaOf"	279	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
281	en=>"IsDonorTo"	279	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
282	en=>"IsLaityOf"	279	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
283	en=>"IdentifiesWith"	279	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
284	en=>"IsDoctrineOf"	279	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
285	en=>"IsVinayaOf"	279	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
286	en=>"DateAssociatedContext"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
287	en=>"IsCongruentWith"	286	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
288	en=>"IsDatedBeforeProbableBegin"	286	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
289	en=>"IsDatedAfterProbableEnd"	286	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
290	en=>"IsDatedBy"	286	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
291	en=>"DatedEntityContext"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
292	en=>"RelicEstablishment"	291	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
293	en=>"Inscription"	291	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
294	en=>"ManuscriptWriting"	291	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
295	en=>"CoinStrike"	291	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
296	en=>"BarkPreparation"	291	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
297	en=>"PlaceAssociatedContext"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
298	en=>"IsPlacedAt"	297	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
299	en=>"IsPlacedNear"	297	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
300	en=>"IsPlacedInside"	297	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
301	en=>"IsPlacedOutside"	297	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
302	en=>"PersonRelationship"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
303	en=>"SonOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
304	en=>"DaughterOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
305	en=>"FatherOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
306	en=>"MotherOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
307	en=>"UncleOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
308	en=>"AuntOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
309	en=>"NieceOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
310	en=>"NephewOf"	302	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
311	en=>"PersonRole"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
312	en=>"PrincipalPerson"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
313	en=>"Establisher"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
314	en=>"Donor"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
315	en=>"Ruler"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
316	en=>"Sender"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
317	en=>"Adressee"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
318	en=>"Concerning"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
319	en=>"Plaintiff"	311	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
320	en=>"PersonStatus"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
321	en=>"Great King"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
322	en=>"Meridarch"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
323	en=>"Great donation master"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
324	en=>"Superintendent of construction"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
325	en=>"Governor"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
326	en=>"King of Kings"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
327	en=>"Cozbo"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
328	en=>"???King (aparajitasa )"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
329	en=>"Great??? (Mahanuava)"	320	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
330	en=>"Parallel"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
331	en=>"PaliParallel"	330	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
332	en=>"SanskritParallel"	330	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
333	en=>"ChineseParallel"	330	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
334	en=>"TibetanParallel"	330	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
335	en=>"AlternativeType"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
336	en=>"Replace"	335	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
337	en=>"Append"	335	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
338	en=>"Augment"	335	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
339	en=>"AttributionType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
340	en=>"Edition"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
341	en=>"Reference"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
342	en=>"Source"	339	778	\N	\N	Refers to the nominated source for the transcription in Azes	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
343	en=>"Catalog"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
344	en=>"Lexicon"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
345	en=>"Content"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
346	en=>"Parallel"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
347	en=>"Annotation"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
348	en=>"Comment"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
349	en=>"Image"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
350	en=>"Spurious"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
351	en=>"AttributionGroupType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
352	en=>"Individual"	351	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
353	en=>"Group"	351	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
354	en=>"BaseLineType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
355	en=>"Image"	354	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
356	en=>"Transcription"	354	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
357	en=>"Missing"	354	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
358	en=>"Case"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
359	en=>"OIACase"	358	776	{362,363,364,365,367,368,369,370}	Case	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
360	en=>"GCase"	358	776	{362,363,364,365,367,368,369,370}	Case	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
361	en=>"dir."	358	778	\N	Nominative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
362	en=>"nom."	358	778	\N	Nominative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
363	en=>"acc."	358	778	\N	Accusative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
364	en=>"instr."	358	778	\N	Instrumental	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
365	en=>"dat."	358	778	\N	Dative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
366	en=>"dat/gen."	358	778	\N	Dative/Genetive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
367	en=>"abl."	358	778	\N	Ablative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
368	en=>"gen."	358	778	\N	Genitive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
369	en=>"loc."	358	778	\N	Locative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
370	en=>"voc."	358	778	\N	Vocative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
371	en=>"CatalogType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
372	en=>"Dictionary"	371	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
373	en=>"Glossary"	371	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
374	en=>"Certainty"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
375	en=>"1"	374	778	\N	Certain	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
376	en=>"2"	374	778	\N	Uncertain	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
377	en=>"3"	374	778	\N	Not used	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
378	en=>"Classification"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
379	en=>"TextClassification"	378	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
380	en=>"Genre"	379	776	{382,383}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
381	en=>"TextAttributes"	379	776	{382,383}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
382	en=>"TextClassificationOption1"	379	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
383	en=>"TextClassificationOption2"	379	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
384	en=>"ObjectClassification"	378	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
385	en=>"ObjectClassification1"	379	776	{387,388}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
386	en=>"ObjectClassification2"	379	776	{387,388}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
387	en=>"ObjectClassificationOption1"	379	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
388	en=>"ObjectClassificationOption2"	379	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
389	en=>"CommentaryType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
390	en=>"TranslationCommentary"	389	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
391	en=>"ChayaCommentary"	389	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
392	en=>"TranscriptionCommentary"	389	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
393	en=>"ParallelCommentary"	389	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
394	en=>"InterpretationCommentary"	389	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
395	en=>"CompoundType"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
396	en=>"OIAInternalCompoundType"	395	776	{398,399}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
397	en=>"GInternalCompoundType"	395	776	{398,399}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
398	en=>"Bahuvrihi"	395	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
399	en=>"Dvandva"	395	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
400	en=>"DateEvidence"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
401	en=>"Attested"	400	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
402	en=>"AttestedEra"	401	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
403	en=>"ImperialText"	402	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
404	en=>"RegnalText"	402	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
405	en=>"AttestedDate"	401	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
406	en=>"AttestedYear"	405	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
407	en=>"YearText"	406	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
408	en=>"YearNumeric"	406	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
409	en=>"AttestedMonth"	405	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
410	en=>"IndianMonth"	409	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
411	en=>"Āṣāḍha"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
412	en=>"Āśvayuj"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
413	en=>"Audunaios"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
414	en=>"Caitra"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
415	en=>"Jyaiṣṭha"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
416	en=>"Kārttika"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
417	en=>"Māgha"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
418	en=>"Mārgaśīrṣa"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
419	en=>"Phālguna"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
420	en=>"Śrāvaṇa"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
421	en=>"Prauṣṭhapada"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
422	en=>"Vaiśākha"	410	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
423	en=>"MacedonianMonth"	409	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
424	en=>"Apellaios"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
425	en=>"Artemisios"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
426	en=>"Daisios"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
427	en=>"Gorpiaios"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
428	en=>"Loios"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
429	en=>"Panemos"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
430	en=>"Tiṣya"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
431	en=>"Xandikos"	423	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
432	en=>"AttestedDay"	405	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
433	en=>"CalenderDay"	432	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
434	en=>"CalenderDayText"	433	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
435	en=>"CalenderDayNumeric"	433	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
436	en=>"LunarDay"	432	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
437	en=>"LunarDayText"	436	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
438	en=>"LunarDayNumeric"	436	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
439	en=>"Measured"	400	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
440	en=>"Carbon14"	439	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
441	en=>"Extrapolated"	440	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
442	en=>"Calculated"	440	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
443	en=>"YearsBPNumber"	442	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
444	en=>"YearsBPPlusMinusNumber"	442	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
445	en=>"BPPlusMinusNumber"	442	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
446	en=>"MeasurementResultString"	440	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
447	en=>"CalibratedRange1StdDevString"	446	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
448	en=>"CalibratedRange2StdDevString"	446	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
449	en=>"Conjectured"	400	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
450	en=>"Paleography"	449	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
451	en=>"Declension"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
452	en=>"OIADeclension"	451	776	{454,455,456,457,458,459,460,461,462,463,464,465,466,467,468}	Declension	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
453	en=>"GDeclension"	451	776	\N	Declension	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
454	en=>"a"	451	778	\N	a	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
455	en=>"ā"	451	778	\N	ā	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
456	en=>"i"	451	778	\N	i	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
457	en=>"u"	451	778	\N	u	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
458	en=>"ī"	451	778	\N	ī	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
459	en=>"ī-mono."	451	778	\N	ī-monosyllablic	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
460	en=>"ī-irr."	451	778	\N	ī-irregular	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
461	en=>"ū"	451	778	\N	ū	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
462	en=>"ū-mono."	451	778	\N	ū-monosyllabic	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
463	en=>"an"	451	778	\N	an	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
464	en=>"r̥"	451	778	\N	r̥	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
465	en=>"at"	451	778	\N	at	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
466	en=>"as"	451	778	\N	as	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
467	en=>"in"	451	778	\N	in	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
468	en=>"const."	451	778	\N	const.	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
469	en=>"o"	451	778	\N	o	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
470	en=>"au"	451	778	\N	au	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
471	en=>"is"	451	778	\N	is	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
472	en=>"us"	451	778	\N	us	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
473	en=>"ar"	451	778	\N	ar	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
474	en=>"EditionType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
475	en=>"Reference"	474	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
476	en=>"Published"	474	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
477	en=>"Research"	474	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
478	en=>"FindStatus"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
479	en=>"Recovered"	478	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
480	en=>"Report"	478	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
481	en=>"Unofficial"	478	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
482	en=>"Gender"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
483	en=>"OIAGender"	482	776	{485,487,486}	Gender	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
484	en=>"GGender"	482	776	{485,486,487}	Gender	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
485	en=>"m."	482	778	\N	Male	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
486	en=>"n."	482	778	\N	Neuter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
487	en=>"f."	482	778	\N	Female	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
488	en=>"GrammaticalGender"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
489	en=>"OIAGrammaticalGender"	488	776	{491,493,492}	Gender	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
490	en=>"GGrammaticalGender"	488	776	{491,492,493,494,495,1383,1384}	Gender	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
491	en=>"m."	488	778	\N	Male	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
492	en=>"n."	488	778	\N	Neuter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
493	en=>"f."	488	778	\N	Female	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
494	en=>"mn."	488	778	\N	Male/Neuter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
495	en=>"mf."	488	778	\N	Male/Female	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
496	en=>"GrammaticalNumber"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
497	en=>"OIAGrammaticalNumber"	496	776	{499,500,501}	Grammatical Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
498	en=>"GGrammaticalNumber"	496	776	{499,500,501}	Grammatical Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
499	en=>"sg."	496	778	\N	Singular	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
500	en=>"du."	496	778	\N	Dual	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
501	en=>"pl."	496	778	\N	Plural	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
502	en=>"GraphemeType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
503	en=>"Consonant"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
504	en=>"Vowel"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
505	en=>"NumberSign"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
506	en=>"Punctuation"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
507	en=>"Unknown"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
508	en=>"VowelModifier"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
509	en=>"IntraSyllablePunctuation"	502	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
510	en=>"GroupType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
511	en=>"User"	510	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
512	en=>"Group"	510	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
513	en=>"System"	510	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
514	en=>"ImageType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
515	en=>"InscriptionRubbing"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
516	en=>"InscriptionEyeCopy"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
517	en=>"InscriptionPhotograph"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
518	en=>"ReliquaryPhotograph"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
519	en=>"EyeCopy"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
520	en=>"ManuscriptReconstruction"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
521	en=>"ManuscriptConserved"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
522	en=>"InscriptionPhotographInfraRed"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
523	en=>"ReconstructedSurface"	514	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
524	en=>"InflectionGrammaticalOptions"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
525	en=>"Adverb"	524	777	\N	Adverb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
526	en=>"Indeclinable"	524	777	\N	Indeclinable	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
527	en=>"Akṣara"	524	777	\N	Akṣara	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
528	en=>"Error"	524	777	\N	Error	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
529	en=>"Punctuation"	524	777	\N	Punctuation	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
530	en=>"Adposition"	524	777	\N	Adposition	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
531	en=>"Number"	524	777	\N	Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
532	en=>"Unknown"	524	777	\N	Unknown	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
533	en=>"Noun"	524	777	\N	Noun	Rather than have an additional SubPos (special case noun) this is identified by the selection of a multi-gender value for gender on the Lemma in which case the inflection UI for gender is enabled	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
534	en=>"common"	524	777	\N	Common Noun	Rather than have an additional SubPos (special case noun) this is identified by the selection of a multi-gender value for gender on the Lemma in which case the inflection UI for gender is enabled	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
535	en=>"proper"	524	777	\N	Proper Noun	Rather than have an additional SubPos (special case noun) this is identified by the selection of a multi-gender value for gender on the Lemma in which case the inflection UI for gender is enabled	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
536	en=>"Pronoun"	524	777	\N	Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
537	en=>"Pers."	524	777	\N	Personal Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
538	en=>"Dem."	524	777	\N	Demonstrative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
539	en=>"Indef."	524	777	\N	Indefinite Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
540	en=>"Interr."	524	777	\N	Interrogative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
541	en=>"Rel."	524	777	\N	Relative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
542	en=>"Refl."	524	777	\N	Reflexive Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
543	en=>"Adjective"	524	777	\N	Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
544	en=>"common adj."	524	777	\N	Common Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
545	en=>"gdv."	524	777	\N	Gerundive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
546	en=>"pp."	524	777	\N	Past Participle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
547	en=>"pres. part."	524	777	\N	Present Participle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
548	en=>"Desid."	524	777	\N	Desiderative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
549	en=>"Intens."	524	777	\N	Intensive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
550	en=>"Numeral"	524	777	\N	Numeral	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
551	en=>"Card."	524	777	\N	Cardinal Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
552	en=>"Ord."	524	777	\N	Ordinal Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
553	en=>"SgPl."	524	777	\N	Special Number	One, hundred, thousand	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
554	en=>"Verb"	524	777	\N	Verb	Buyer beware as pret. And perf. do not have a Mood.  We are not going to resolve the notion that selection of particular Verbal tense options triggers display of Verbal Mood options.	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
555	en=>"Finite"	524	777	\N	Finite Verb	Buyer beware as pret. And perf. do not have a Mood.  We are not going to resolve the notion that selection of particular Verbal tense options triggers display of Verbal Mood options.	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
556	en=>"Derivative"	524	777	\N	Derivative Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
557	en=>"InternalComponentClassification"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
558	en=>"OIAInternalComponentClassification"	557	776	{560,561,562,563,564,565,566,567,563,568,569,570,571,572,573,574,575}	Internal Component Classification	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
559	en=>"GInternalComponentClassification"	557	776	\N	Internal Component Classification	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
560	en=>"Itaretara-dvandva"	557	778	\N	Itaretara-dvandva	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
561	en=>"Samahara-dvandva"	557	778	\N	Samahara-dvandva	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
562	en=>"Upapada"	557	778	\N	Upapada	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
563	en=>"Karmadhāraya"	557	778	\N	Karmadhāraya	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
564	en=>"Accusative Tatpuruṣa"	557	778	\N	Accusative Tatpuruṣa	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
565	en=>"Instrumental Tatpuruṣa"	557	778	\N	Instrumental Tatpuruṣa	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
566	en=>"Dative Tatpuruṣa"	557	778	\N	Dative Tatpuruṣa	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
567	en=>"Ablative Tatpuruṣa"	557	778	\N	Ablative Tatpuruṣa	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
568	en=>"Genitive Tatpuruṣa"	557	778	\N	Genitive Tatpuruṣa	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
569	en=>"Locative Tatpuruṣa"	557	778	\N	Locative Tatpuruṣa	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
570	en=>"Bahuvrīhi"	557	778	\N	Bahuvrīhi	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
571	en=>"Negation"	557	778	\N	Negation	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
572	en=>"Prefix"	557	778	\N	Prefix	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
573	en=>"Preposition"	557	778	\N	Preposition	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
574	en=>"Pronoun"	557	778	\N	Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
575	en=>"Verbal"	557	778	\N	Verbal	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
576	en=>"JSONCacheType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
577	en=>"JSONCacheTypeOption1"	576	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
578	en=>"JSONCacheTypeOption2"	576	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
579	en=>"Language"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
580	en=>"G."	579	778	\N	Gāndhārī	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
581	en=>"P."	579	778	\N	Pali	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
582	en=>"OIA"	579	778	\N	Old Indo Aryan	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
583	en=>"MIA"	579	778	\N	Middle Indo Aryan	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
584	en=>"Gk."	579	778	\N	Greek	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
585	en=>"Ir."	579	778	\N	Iranian	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
586	en=>"Chin."	579	778	\N	Chinese	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
587	en=>"LemmaGrammaticalOptions"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
588	en=>"adv."	587	777	\N	Adverb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
589	en=>"ind."	587	777	\N	Indeclinable	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
590	en=>"ptc."	587	777	\N	Indeclinable	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
591	en=>"Akṣara"	587	777	\N	Akṣara	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
592	en=>"Error"	587	777	\N	Error	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
593	en=>"Punctuation"	587	777	\N	Punctuation	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
594	en=>"adp."	587	777	\N	Adposition	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
595	en=>"num."	587	777	\N	Numeral	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
596	en=>"Unknown"	587	777	\N	Unknown	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
597	en=>"noun"	587	777	\N	Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
598	en=>"common"	587	777	\N	Common Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
599	en=>"proper"	587	777	\N	Proper Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
600	en=>"pron."	587	777	\N	Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
601	en=>"pers."	587	777	\N	Personal Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
602	en=>"dem."	587	777	\N	Demonstrative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
603	en=>"indef."	587	777	\N	Indefinite Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
604	en=>"interr."	587	777	\N	Interrogative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
605	en=>"rel."	587	777	\N	Relative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
606	en=>"refl."	587	777	\N	Reflexive Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
607	en=>"adj."	587	777	\N	Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
608	en=>"common adj."	587	777	\N	Common Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
609	en=>"gdv."	587	777	\N	Gerundive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
610	en=>"pp."	587	777	\N	Past Participle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
611	en=>"pres. part."	587	777	\N	Present Participle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
612	en=>"Desid."	587	777	\N	Desiderative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
613	en=>"Intens."	587	777	\N	Intensive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
614	en=>"number"	587	777	\N	Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
615	en=>"card."	587	777	\N	Cardinal Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
616	en=>"ord."	587	777	\N	Ordinal Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
617	en=>"sgpl."	587	777	\N	Special Number	One, hundred, thousand	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
618	en=>"v."	587	777	\N	Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
619	en=>"Finite"	587	777	\N	Finite Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
620	en=>"Derivative"	587	777	\N	Derivative Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
621	en=>"LemmaType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
622	en=>"Person"	621	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
623	en=>"School"	621	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
624	en=>"Place"	621	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
625	en=>"Dynasty"	621	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
626	en=>"Scribe"	621	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
627	en=>"ManufactureTechnique"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
630	en=>"MetadataType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
631	en=>"MetadataTypeOption1"	630	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
632	en=>"MetadataTypeOption2"	630	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
633	en=>"ObjectMedium"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
636	en=>"ObjectShape"	1	781	\N	\N	Structured heirachcial typology of object shapes.	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
639	en=>"ObjectType"	1	781	\N	\N	Structured heirachcial typology of objects.	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
642	en=>"PartOfSpeech"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
643	en=>"OIAPartOfSpeech"	642	776	\N	Part Of Speech	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
644	en=>"GPartOfSpeech"	642	776	{645,646,647,648,649,655,656,657,658,659,660,661,662,663}	Part Of Speech	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
645	en=>"GNoun"	642	776	{665,666}	Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
646	en=>"GPronoun"	642	776	{668,669,670,671,672,673}	Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
715	en=>"Content"	710	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
647	en=>"GAdjective"	642	776	{675,676,677,678,679,680}	Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
648	en=>"GNumber"	642	776	{682,683,684}	Numeral	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
649	en=>"GVerb"	642	776	{686,687}	Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
650	en=>"OIANoun"	642	776	\N	Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
651	en=>"OIAPronoun"	642	776	\N	Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
652	en=>"OIAAdjective"	642	776	\N	Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
653	en=>"OIANumeral"	642	776	\N	Numeral	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
654	en=>"OIAVerb"	642	776	\N	Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
655	en=>"adv."	642	778	\N	Adverb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
656	en=>"ind."	642	778	\N	Indeclinable	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
657	en=>"ptc."	642	778	\N	Particle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
658	en=>"Akṣara"	642	778	\N	Akṣara	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
659	en=>"Error"	642	778	\N	Error	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
660	en=>"Punctuation"	642	778	\N	Punctuation	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
661	en=>"adp."	642	778	\N	Adposition	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
662	en=>"num."	642	778	\N	Numeral	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
663	en=>"Unknown"	642	778	\N	Unknown	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
664	en=>"noun"	642	778	\N	Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
665	en=>"common"	664	778	\N	Common Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
666	en=>"proper"	664	778	\N	Proper Noun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
667	en=>"pron."	642	778	\N	Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
668	en=>"pers."	667	778	\N	Personal Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
669	en=>"dem."	667	778	\N	Demonstrative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
670	en=>"indef."	667	778	\N	Indefinite Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
671	en=>"interr."	667	778	\N	Interrogative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
672	en=>"rel."	667	778	\N	Relative Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
673	en=>"refl."	667	778	\N	Reflexive Pronoun	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
674	en=>"adj."	642	778	\N	Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
675	en=>"common adj."	674	778	\N	Common Adjective	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
676	en=>"gdv."	674	778	\N	Gerundive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
677	en=>"pp."	674	778	\N	Past Participle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
678	en=>"pres. part."	674	778	\N	Present Participle	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
679	en=>"Desid."	674	778	\N	Desiderative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
680	en=>"Intens."	674	778	\N	Intensive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
681	en=>"number"	642	778	\N	Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
682	en=>"card."	662	778	\N	Cardinal Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
683	en=>"ord."	662	778	\N	Ordinal Number	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
684	en=>"sgpl."	662	778	\N	Special Number	One, hundred, thousand	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
685	en=>"v."	642	778	\N	Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
686	en=>"Finite"	554	778	\N	Finite Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
687	en=>"Derivative"	554	778	\N	Derivative Verb	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
688	en=>"ProperNounEvidence"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
689	en=>"SchoolEvidence"	688	776	{693,694,695,696,697}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
690	en=>"PlaceEvidence"	688	776	{698,699,700,701}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
691	en=>"PersonEvidence"	688	776	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
692	en=>"DynastyEvidence"	688	776	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
693	en=>"AttestedSchool"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
694	en=>"AttestedVinaya"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
695	en=>"AttestedDoctine"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
696	en=>"ConjecturedVinaya"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
697	en=>"ConjecturedDoctrine"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
698	en=>"AttestedCountry"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
699	en=>"AttestedRegion"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
700	en=>"AttestedCity"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
701	en=>"AttestedPlace"	688	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
702	en=>"ProperNounType"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
703	en=>"Person"	702	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
704	en=>"Place"	702	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
705	en=>"School"	702	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
706	en=>"Dynasty"	702	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
707	en=>"Scribe"	702	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
708	en=>"Reference"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
709	en=>"EditionReference"	708	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
710	en=>"ContentParallel"	708	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
711	en=>"Edition"	709	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
712	en=>"Reference"	709	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
713	en=>"Catalog"	709	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
714	en=>"ArchaeologicalReport"	709	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1389	en=>"1"	1388	778	\N	bt1	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
716	en=>"Parallel"	710	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
717	en=>"RestorationState"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
718	en=>"RestorationStateOption1"	717	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
719	en=>"RestorationStateOption2"	717	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
720	en=>"LanguageScript"	8	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
721	en=>"pgd-Khar"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
722	en=>"san-Brah"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
723	en=>"el"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
724	en=>"SegmentClarity"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
725	en=>"0"	724	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
726	en=>"1"	724	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
727	en=>"2"	724	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
728	en=>"3"	724	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
729	en=>"4"	724	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
730	en=>"5"	724	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
731	en=>"SegmentObscurations"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
732	en=>"Scratch"	731	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
733	en=>"Fold"	731	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
735	en=>"SequenceType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
736	en=>"TextPhysical"	735	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
737	en=>"LinePhysical"	736	778	{1287}	Line	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
738	en=>"Text"	735	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
739	en=>"TextDivision"	738	778	{972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
740	en=>"Analysis"	735	778	{741,1437,744,750}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
741	en=>"Chapter"	740	778	{744,745,742,1442}	Chapter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
742	en=>"Stanza"	741	778	{743}	Stanza	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
743	en=>"Pāda"	742	778	{972,1345}	Pāda	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
744	en=>"Paragraph"	1437	778	{745,742,972,1345}	Paragraph	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
745	en=>"Sentence"	744	778	{746,747}	Sentence	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
746	en=>"Clause"	745	778	{747}	Clause	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
747	en=>"Phrase"	746	778	{972,1345}	Phrase	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
748	en=>"NounPhrase"	747	778	{972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
749	en=>"VerbPhrase"	747	778	{972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
750	en=>"Formulae"	740	778	\N	Formuale	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
756	en=>"TextReference"	735	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
757	en=>"RootRefContainer"	756	778	{972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
758	en=>"RootRef"	756	778	{972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
759	en=>"Commentary"	756	778	{972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
760	en=>"DependancyTree"	735	778	{881}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
761	en=>"Translation"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
764	en=>"SpanType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
765	en=>"Left"	764	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
766	en=>"Right"	764	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
767	en=>"Both"	764	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
768	en=>"Delaminated"	764	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
769	en=>"Missing"	764	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
770	en=>"SystemLanguage"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
771	en=>"en"	770	778	\N	\N	English	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
772	en=>"de"	770	778	\N	\N	German	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
773	en=>"TermType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
774	en=>"SystemContent"	773	776	{776,777,778,779,780,781,782,783}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
775	en=>"EntityField"	773	776	{784,785,786,787,788,789,790,791,792,793,794,795,796,797,798,799,800,801,802,803,804,805,806,807,808,809,810,811,812,814}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
776	en=>"UIList"	773	778	\N	\N	Enumerated List of options for display in a fielf drop down	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
777	en=>"UISet"	773	778	\N	\N	Used by UI to detemine which set of field options to display	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
778	en=>"Term"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
779	en=>"DefinedTerm"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
780	en=>"SystemList"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
781	en=>"ContentList"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
782	en=>"EntityList"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
783	en=>"Entity"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
784	en=>"Key"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
785	en=>"Text-Single"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
786	en=>"Text-Multiple"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
787	en=>"Number"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
788	en=>"NumberText"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
789	en=>"Boolean"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
790	en=>"AutomationDate"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
791	en=>"AutomationText"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
792	en=>"AutomationTextMultiple"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
793	en=>"AutomationNumber"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
794	en=>"(UI)AssistedDate"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
795	en=>"(UI)AssistedText"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
796	en=>"(UI)AssistedTextMultiple"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
797	en=>"(UI)AssistedNumber"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
798	en=>"(UI)AssistedNumberPolygon"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
799	en=>"(UI)AssistedNumberSubString"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
800	en=>"FK-HomogenousMultiple"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
801	en=>"FK-HomogenousMultipleOrdered"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
802	en=>"FK-HomogenousSingle"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
803	en=>"FK-HeterogenousMultiple"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
804	en=>"FK-HeterogenousMultipleOrdered"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
805	en=>"FK-HeterogenousSingle"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
806	en=>"List-Single"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
807	en=>"List-Multiple"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
808	en=>"List-MultipleOrdered"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
809	en=>"FK-PairMultiple_Semantic(Term)-Attribution"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
810	en=>"FK-PairMultiple_Semantic(Term)-SystemEntity/Text"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
811	en=>"FK-PairMultiple_Semantic(Term)/SystemEntity-SystemEntity"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
812	en=>"FK-PairMultiple_Semantic(ProperNoun)-Text"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
813	en=>"FK-PairSingle_FK-HomogenousSingle-(UI)AssistedNumberPolygon"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
814	en=>"TimeStamp"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
815	en=>"TextType"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
816	en=>"TextTypeOption1"	815	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
817	en=>"TextTypeOption2"	815	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
818	en=>"UtilityConfigurationMenu"	1	780	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
819	en=>"Object"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
820	en=>"Image"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
821	en=>"Script"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
822	en=>"Edtion"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
823	en=>"Token"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
824	en=>"Data"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
825	en=>"Link"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
826	en=>"Attribution"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
827	en=>"Tool"	818	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
828	en=>"VerbalClass"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
829	en=>"OIAVerbalClass"	828	776	{831,832,833,834,835,836,837,838,839,840}	Verbal Class	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
830	en=>"GVerbalClass"	828	776	\N	Verbal Class	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
831	en=>"1"	828	778	\N	1	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
832	en=>"2"	828	778	\N	2	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
833	en=>"3"	828	778	\N	3	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
834	en=>"4"	828	778	\N	4	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
835	en=>"5"	828	778	\N	5	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
836	en=>"6"	828	778	\N	6	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
837	en=>"7"	828	778	\N	7	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
838	en=>"8"	828	778	\N	8	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
839	en=>"9"	828	778	\N	9	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
840	en=>"10"	828	778	\N	10	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
841	en=>"VerbalMood"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
842	en=>"OIAVerbalMood"	841	776	{844,845,846}	Verbal Mood	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
843	en=>"GVerbalMood"	841	776	{844,845,846}	Verbal Mood	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
844	en=>"impv."	841	778	\N	Imperitive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
845	en=>"opt."	841	778	\N	Optative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
846	en=>"Indic."	841	778	\N	Indicative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
847	en=>"VerbalPerson"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
848	en=>"OIAVerbalPerson"	847	776	{850,851,852}	Verbal Person	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
849	en=>"GVerbalPerson"	847	776	{850,851,852}	Verbal Person	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
850	en=>"1st"	847	778	\N	First Person	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
851	en=>"2nd"	847	778	\N	Second Person	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
852	en=>"3rd"	847	778	\N	Third Person	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
853	en=>"VerbalSecondaryConjugation"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
854	en=>"OIAVerbalSecondaryConjugation"	853	776	{856,857,858,859,860,861}	Verbal Secondary Conjugation	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
855	en=>"GVerbalSecondaryConjugation"	853	776	{860,861}	Verbal Secondary Conjugation	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
856	en=>"caus."	853	778	\N	Causative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
857	en=>"desid."	853	778	\N	Desiderative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
858	en=>"Den."	853	778	\N	Denominative	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
859	en=>"intens."	853	778	\N	Intensive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
860	en=>"abs."	853	778	\N	Absolutive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
861	en=>"inf."	853	778	\N	Infinitive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
862	en=>"VerbalTense"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
863	en=>"OIAVerbalTense"	862	776	{865,866,867,868,869,870,871}	Verbal Tense	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
864	en=>"GVerbalTense"	862	776	{865,872,867,868}	Verbal Tense	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
865	en=>"pres."	862	778	\N	Present	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
866	en=>"imp."	862	778	\N	Imperfect	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
867	en=>"perf."	862	778	\N	Perfect	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
868	en=>"fut."	862	778	\N	Future	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
869	en=>"pfut."	862	778	\N	Periphrastic Future	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
870	en=>"aor."	862	778	\N	Aorist	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
871	en=>"cond."	862	778	\N	Conditional	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
872	en=>"pret."	862	778	\N	Preterite	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
873	en=>"VerbalVoice"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
874	en=>"OIAVerbalVoice"	873	776	{875,876,877}	Verbal Voice	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
875	en=>"P."	873	778	\N	Parasmaipada	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
876	en=>"Ā."	873	778	\N	Ātmanepada	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
877	en=>"pass."	873	778	\N	Passive	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
878	en=>"WritingTechnique"	1	781	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
879	en=>"WritingTechniqueOption1"	878	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
880	en=>"WritingTechniqueOption2"	878	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
881	en=>"Annotation"	2	783	\N	ano	An Annotation may be applied to any record(s) (or any nominated field within any record) in any entity.  An Annotation may either apply a semantic to that record, or apply a semantic link to another record(s).	http://www.gandhari.org/kanishka/model/document/Annotation-Annotation.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
882	en=>"Annotations"	881	800	{881}	ano_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Annotation-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
883	en=>"Attributions"	881	800	{895}	ano_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Annotation-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
884	en=>"ID"	881	784	{881}	ano_id	\N	http://www.gandhari.org/kanishka/model/document/Annotation-Annotation-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
885	en=>"LinksFrom"	881	803	{959,1034,1067,1098,1183,1270,1316,895,908,923,972,1052,1369,1135,1215,1229,1260,1287,1345,881,946,989,1007,1021,1159,1247,1301,1170,937,1202}	ano_linkfrom_ids	Entitys, this Annotation is identified with.	http://www.gandhari.org/kanishka/model/document/Annotation-LinksFrom.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
886	en=>"LinksTo"	881	803	{959,1034,1067,1098,1183,1270,1316,895,908,923,972,1052,1369,1135,1215,1229,1260,1287,1345,881,946,989,1007,1021,1159,1247,1301,1170,937,1202}	ano_linkto_ids	Entitys, this Annotation identifies with the Entitys identified in Annotation-LinksFrom.	http://www.gandhari.org/kanishka/model/document/Annotation-LinksTo.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
887	en=>"Owner"	881	802	\N	ano_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Annotation-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
888	en=>"Scratch"	881	786	\N	ano_scratch	\N	http://www.gandhari.org/kanishka/model/document/Annotation-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
889	en=>"Text"	881	786	\N	ano_text	Free text.	http://www.gandhari.org/kanishka/model/document/Annotation-Annotation-Text.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
890	en=>"Type"	881	806	{8,263,274,278,335,761,1422,1418}	ano_type_id	A Term record with a constrained vocabulary derived from AnnotationType.	http://www.gandhari.org/kanishka/model/document/Annotation-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
891	en=>"URL"	881	785	\N	ano_url	A url to extended text or reference documentation for this Annotation.	http://www.gandhari.org/kanishka/model/document/Annotation-Annotation-URL.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
892	en=>"Visibilities"	881	800	{1369}	ano_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Understood to be the semantic of the relationship between: the Entity in Annotation-Link and the value in Annotation-Text or the annotated entity and the value in Annotation-Text if no value in Annotation-Link, or the annotated entity and the value in Annotation-Link if no value in Annotation-Text	http://www.gandhari.org/kanishka/model/document/Annotation-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
893	en=>"AttributionMask"	881	791	\N	\N	Annotation-AttributionMask is calculated from a concatenation of the value in UserGroup-FamilyName and the value in Annotation-Modified.	http://www.gandhari.org/kanishka/model/document/Annotation-Annotation-AttributionMask.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
894	en=>"Modified"	881	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Annotation-Annotation-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
895	en=>"Attribution"	2	783	\N	atb	Each Entity in the system has a field for identification of Attributions.  Attribution encompasses both references to the Bibliography and Attributable Events in the system.	http://www.gandhari.org/kanishka/model/document/Attribution-Attribution.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
896	en=>"Annotations"	895	800	{881}	atb_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Attribution-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1390	en=>"2"	1388	778	\N	bt2	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
897	en=>"AttributionGroup"	895	802	{908}	atb_group_id	AttributionGroup record linked to this record.	http://www.gandhari.org/kanishka/model/document/Attribution-AttributionGroup.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
898	en=>"Bibliography"	895	802	{937}	atb_bib_id	Bibliography record linked to this record.	http://www.gandhari.org/kanishka/model/document/Attribution-Bibliography.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
899	en=>"Description"	895	785	\N	atb_description	Free text description.	http://www.gandhari.org/kanishka/model/document/Attribution-Attribution-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
900	en=>"Detail"	895	785	\N	atb_detail	Where the Attribution record is a system Attributable Event, the value in the Attribution-Detail field is the system generated date stamp.  Where the Attribution record is a reference to the Bibliography, the the value in the Attribution-Detail field may be a page number or plate number.	http://www.gandhari.org/kanishka/model/document/Attribution-Attribution-Detail.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
901	en=>"ID"	895	784	{895}	atb_id	\N	http://www.gandhari.org/kanishka/model/document/Attribution-Attribution-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
902	en=>"Owner"	895	802	{1369}	atb_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Attribution-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
903	en=>"Scratch"	895	786	\N	atb_scratch	\N	http://www.gandhari.org/kanishka/model/document/Attribution-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
904	en=>"Title"	895	785	\N	atb_title	Where the Attribution record has a linked Bibliography record in the Attribution-Bibliography field, this value is calculated by concatenating the values in the Bibliography-Name and Attribution-Detail fields.  Where the Attribution record has a linked AttributionGroup record in the Attribution-AttributionGroup field this value is calculated by concatenating the values in AttributionGroup-Name and Attribution-Detail.	http://www.gandhari.org/kanishka/model/document/Attribution-Attribution-Title.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
905	en=>"Types"	895	807	{340,1424,1425,1426,341,342,343,1436,345,346,347,348,349,350,1427,344}	atb_types	A Term record with a constrained vocabulary derived from AttributionType.	http://www.gandhari.org/kanishka/model/document/Attribution-Types.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
906	en=>"Visibilities"	895	800	{1369}	atb_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Pointers to attribution records from the editionreference entity would refer to the value in attribution- detail as well as the value in the bibliography field. Pointers to attribution records from the image entity would refer to the values in attribution-detail as well as the value in the bibliography field.	http://www.gandhari.org/kanishka/model/document/Attribution-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
907	en=>"Modified"	895	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Attribution-Attribution-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
908	en=>"AttributionGroup"	2	783	\N	atg	AttributionGroup identifies individual Users and groups of Users for the purpose of identifying their contributions to the development of an entity.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
909	en=>"AdminIDs"	908	800	{1369}	atg_admin_ids	UserGroup record linked to this record.  Defines which UserGroup has an Administrator role.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AdminIDs.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
910	en=>"Annotations"	908	800	{881}	atg_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
911	en=>"Attributions"	908	800	{895}	atg_attribution_ids	Attribution record linked to this record.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
912	en=>"DateEstablished"	908	794	\N	atg_date_created	Date this AttributionGroup was established.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup-DateEstablished.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
913	en=>"Description"	908	785	\N	atg_description	Free text description of contributing User or group of contributing Users	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
914	en=>"Fullname"	908	785	\N	atg_realname	Full name of contributing User or group of contributing Users.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup-Fullname.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
915	en=>"ID"	908	784	{1021}	atg_id	\N	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
916	en=>"MemberIDs"	908	800	{1369}	atg_member_ids	UserGroup record linked to this record.  Defines which UserGroup are Member.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-MemberIDs.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
917	en=>"Name"	908	785	\N	atg_name	Abbreviated name of contributing User or group of contributing Users.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup-Name.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
918	en=>"Owner"	908	802	{1369}	atg_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
919	en=>"Scratch"	908	786	\N	atg_scratch	\N	http://www.gandhari.org/kanishka/model/document/AttributionGroup-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
920	en=>"Type"	908	806	{352,353}	atg_type_id	A Term record with a constrained vocabulary derived from AttributionGroupType.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
921	en=>"Visibilities"	908	800	{1369}	atg_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
922	en=>"Modified"	908	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/AttributionGroup-AttributionGroup-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
923	en=>"BaseLine"	2	783	\N	bln	A Baseline is a fixed reference system for the Akṣaras on a Surface.  Where a relevant Image exists, a Baseline identifies an Image with a Surface as the basis for referential anchoring.  An Image associated with a Surface, at a particular resolution, has an Xycoordinate system; a grid much like the reference grids laid out at an archaeological excavation.	http://www.gandhari.org/kanishka/model/document/BaseLine-BaseLine.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
924	en=>"Annotations"	923	800	{881}	bln_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/BaseLine-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
925	en=>"Attributions"	923	800	{895}	bln_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/BaseLine-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
926	en=>"ID"	923	784	{923}	bln_id	\N	http://www.gandhari.org/kanishka/model/document/BaseLine-BaseLine-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
927	en=>"Image"	923	802	{1067}	bln_image_id	Image, part or whole, this Baseline is identified with.	http://www.gandhari.org/kanishka/model/document/BaseLine-Image.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
928	en=>"Owner"	923	802	{1369}	bln_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/BaseLine-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
929	en=>"Position"	923	798	\N	bln_image_position	Xycoordinates (polygon) which defines a cropped area of an Image encompassed by this Baseline.	http://www.gandhari.org/kanishka/model/document/BaseLine-BaseLine-Position.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
930	en=>"Scratch"	923	786	\N	bln_scratch	\N	http://www.gandhari.org/kanishka/model/document/BaseLine-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
931	en=>"Surface"	923	802	{1270}	bln_surface_id	Surface, part or whole, this Baseline is identified with.	http://www.gandhari.org/kanishka/model/document/BaseLine-Surface.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
932	en=>"Transcription"	923	785	\N	bln_transcription	Transcription of Text on the related Surface.  Used where BaseLine-Type is Transcription.  This transcription is the basis for segmentation.  Segments are identified with ordinal character positon ranges on the transcription string.	http://www.gandhari.org/kanishka/model/document/BaseLine-BaseLine-Transcription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
933	en=>"Type"	923	806	{355,356,357}	bln_type_id	A Term record with a constrained vocabulary derived from BaselineType.	http://www.gandhari.org/kanishka/model/document/BaseLine-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
934	en=>"Visibilities"	923	800	{1369}	bln_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Where we have only a rubbing or an eye copy, then for these purposes, that image is associated with that surface as the baseline. A baseline must have at least one segment	http://www.gandhari.org/kanishka/model/document/BaseLine-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
935	en=>"MappedBaseline"	923	813	\N	\N	A Baseline from which Segments are to be transposed.  Where the Baselines are not clones, Xycoordinates (polygon) on this Baseline are recorded in order to define an area on this Baseline for the transposition of Segments.	http://www.gandhari.org/kanishka/model/document/BaseLine-BaseLine-MappedBaseline.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
936	en=>"Modified"	923	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/BaseLine-BaseLine-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
937	en=>"Bibliography"	2	783	\N	bib	A Bibliography is a unique identifier for a published monograph, book chapter or journal article relevant to Gāndhārī and Gandhāran studies.  Each Bibliography record uniquely identifies a publication. Entity Attributes	http://www.gandhari.org/kanishka/model/document/Bibliography-Bibliography.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
938	en=>"Annotations"	937	800	{881}	bib_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Bibliography-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
939	en=>"Attributions"	937	800	{895}	bib_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Bibliography-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
940	en=>"ID"	937	784	{937}	bib_id	\N	http://www.gandhari.org/kanishka/model/document/Bibliography-Bibliography-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
941	en=>"Name"	937	785	\N	bib_name	Each Bibliography-Name record uniquely identifies a publication by the name of the author, the year of publication and, where that author had multiple publications in the same year, a numeric identifier indication the nth publication of that year	http://www.gandhari.org/kanishka/model/document/Bibliography-Bibliography-Name.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
942	en=>"Owner"	937	802	{1369}	bib_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Bibliography-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
943	en=>"Scratch"	937	786	\N	bib_scratch	\N	http://www.gandhari.org/kanishka/model/document/Bibliography-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
944	en=>"Visibilities"	937	800	{1369}	bib_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Bibliography-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
945	en=>"Modified"	937	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Bibliography-Bibliography-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
946	en=>"Catalog"	2	783	\N	cat	A Catalog sequences Edition records to configure Catalogs and Glossarys.	http://www.gandhari.org/kanishka/model/document/Catalog-Catalog.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
947	en=>"Annotations"	946	800	{881}	cat_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Catalog-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1391	en=>"3"	1388	778	\N	bt3	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
948	en=>"Attributions"	946	800	{895}	cat_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Catalog-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
949	en=>"Description"	946	785	\N	cat_description	Free text description.	http://www.gandhari.org/kanishka/model/document/Catalog-Catalog-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
950	en=>"Editions"	946	801	{1007}	cat_edition_ids	Ordered set of linked Editions.	http://www.gandhari.org/kanishka/model/document/Catalog-Editions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
951	en=>"ID"	946	784	{946}	cat_id	\N	http://www.gandhari.org/kanishka/model/document/Catalog-Catalog-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
952	en=>"Owner"	946	802	{1369}	cat_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Catalog-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
953	en=>"Scratch"	946	786	\N	cat_scratch	\N	http://www.gandhari.org/kanishka/model/document/Catalog-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
954	en=>"Title"	946	785	\N	cat_title	Free text title used for display n the user interface.	http://www.gandhari.org/kanishka/model/document/Catalog-Catalog-Title.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
955	en=>"Type"	946	806	{372,373}	cat_type_id	A Term record with a constrained vocabulary derived from CatalogType.	http://www.gandhari.org/kanishka/model/document/Catalog-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
956	en=>"Visibilities"	946	800	{1369}	cat_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Catalog-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
957	en=>"SourceLanguage"	946	806	{580,581,582,583,584,585,586}	cat_lang_id	A Term record with a constrained vocabulary derived from Language.	http://www.gandhari.org/kanishka/model/document/Catalog-SourceLanguage.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
958	en=>"Modified"	946	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Catalog-Catalog-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
959	en=>"Collection"	2	783	\N	col	A Collection is an assemblage of other Collection, Item, Part or Fragment records generally aggregated through circumstances of discovery purchase or provenance.	http://www.gandhari.org/kanishka/model/document/Collection-Collection.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
960	en=>"Annotations"	959	800	{881}	col_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Collection-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
961	en=>"Attributions"	959	800	{895}	col_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Collection-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
962	en=>"CollectionsItemsPartsFragments"	959	804	{959,1098,1183,1034}	col_item_part_fragment_ids	An aggregation of Item, Part or Fragment records which comprise this Collection.  The aggregation may also include another Collection.	http://www.gandhari.org/kanishka/model/document/Collection-CollectionsItemsPartsFragments.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
963	en=>"Description"	959	785	\N	col_description	The description encompasses all the Item, Part or Fragment records in that Collection. Standards, Protocols and Examples To be developed.	http://www.gandhari.org/kanishka/model/document/Collection-Collection-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
964	en=>"ID"	959	784	{959}	col_id	\N	http://www.gandhari.org/kanishka/model/document/Collection-Collection-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
965	en=>"Location-LocationReferences"	959	786	\N	col_location_refs	\N	http://www.gandhari.org/kanishka/model/document/Collection-Location-LocationReferences.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
966	en=>"Owner"	959	802	{1369}	col_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Collection-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
967	en=>"PartsFragmentsSubtract"	959	803	{1183,1034}	col_exclude_part_fragment_ids	Part or Fragment records may be identified as being excluded from the Collection, Item or Part or Fragment records identified in Collection-CollectionsItemsPartsFragments.	http://www.gandhari.org/kanishka/model/document/Collection-PartsFragmentsSubtract.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
968	en=>"Scratch"	959	786	\N	col_scratch	\N	http://www.gandhari.org/kanishka/model/document/Collection-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
969	en=>"Title"	959	785	\N	col_title	Generally an historical or a conventional name. Standards, Protocols and Examples British Library, Bajaur	http://www.gandhari.org/kanishka/model/document/Collection-Collection-Title.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
970	en=>"Visibilities"	959	800	{1369}	col_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Collection-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
971	en=>"Modified"	959	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Collection-Collection-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
972	en=>"Compound"	2	783	\N	cmp	A Compound is composed of Token and/or Compound records which are sequenced to represent an attested compound.	http://www.gandhari.org/kanishka/model/document/Compound-Compound.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
973	en=>"Annotations"	972	800	{881}	cmp_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Compound-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
974	en=>"Attributions"	972	800	{895}	cmp_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Compound-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1031	en=>"Title"	1021	785	\N	era_title	Free text title derived from scholarship.	http://www.gandhari.org/kanishka/model/document/Era-Era-Title.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
975	en=>"CompoundComponents"	972	804	{1345,972}	cmp_component_ids	Ordered Sequence of Token and/or Compound records.	http://www.gandhari.org/kanishka/model/document/Compound-CompoundComponents.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
976	en=>"DisplayValue"	972	791	\N	cmp_value	The Compound-DisplayValue field is concatenated from Token and/or Compound records sequenced on the Compound-CompoundComponents field without Text Critical Marks.	http://www.gandhari.org/kanishka/model/document/Compound-Compound-DisplayValue.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
977	en=>"ID"	972	784	{972}	cmp_id	\N	http://www.gandhari.org/kanishka/model/document/Compound-Compound-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
978	en=>"InternalComponentCase"	972	806	{359,360,361,362,363,364,365,366,367,368,369,370}	cmp_case_id	A Term record with a constrained vocabulary derived from Case.	http://www.gandhari.org/kanishka/model/document/Compound-InternalComponentCase.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
979	en=>"InternalComponentClassification"	972	806	{558,559,560,561,562,563,564,565,566,567,568,569,570,571,572,573,574,575}	cmp_class_id	A Term record with a constrained vocabulary derived from InternalComponentClassification.	http://www.gandhari.org/kanishka/model/document/Compound-InternalComponentClassification.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
980	en=>"Owner"	972	802	{1369}	cmp_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Compound-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
981	en=>"SC"	972	788	\N	cmp_sort_code	The Compound-SC value is the primary sort order  (the macro level sorting where all diacritic variants have the same sort value) for for Compounds calculated from the sequence of Grapheme-Grapheme values.  The only exception to this is Anusvāra and Visarga.   It is anticipated the Anusvāra and Visarga would have a default Grapheme-Grapheme-Sc value.  It is anticipated that Compound-SC value would calculate a contextual value for Anusvāra and Visarga.  It is anticipated that contextual calculation of Anusvāra and Visarga would be enabled for Sanskrit but not for Gāndhārī.	http://www.gandhari.org/kanishka/model/document/Compound-Compound-SC.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
982	en=>"SC2"	972	788	\N	cmp_sort_code2	The Compound-SC2 value is the secondary sort order (the micro level sorting where diacritic variants have a unique sort value) for Compounds calculated from the sequence of Grapheme-Grapheme values.  The only exception to this is Anusvāra and Visarga.   It is anticipated the Anusvāra and Visarga would have a default Grapheme-Grapheme-Sc value.  It is anticipated that Compound-SC2 value would calculate a contextual value for Anusvāra and Visarga.  It is anticipated that contextual calculation of Anusvāra and Visarga would be enabled for Sanskrit but not for Gāndhārī.	http://www.gandhari.org/kanishka/model/document/Compound-Compound-SC2.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
983	en=>"Scratch"	972	786	\N	cmp_scratch	\N	http://www.gandhari.org/kanishka/model/document/Compound-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
984	en=>"Transcription"	972	791	\N	cmp_transcription	The Compound-Transcription field is concatenated from Token and/or Compound records sequenced on the Compound-CompoundComponents field with Text Critical Marks.  Values from the Grapheme-Grapheme and Grapheme-TextCriticalMark fields are calculated to represent the edited transcription; the Compound-DisplayValue augmented with Text Critical Marks.  Automation resolves the rendering of values from Grapheme-TextCriticalMark across strings of values from Grapheme-Grapheme for all Graphemes sequenced into the constituent Tokens.  Where the value in the Grapheme-Grapheme is null, automation will derive a value for representation of that Grapheme in the Compound-Transcription from the value in Grapheme-TextCriticalMark.	http://www.gandhari.org/kanishka/model/document/Compound-Compound-Transcription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
985	en=>"Type"	972	806	{396,397,398,399}	cmp_type_id	A Term record with a constrained vocabulary derived from CompundType.	http://www.gandhari.org/kanishka/model/document/Compound-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
986	en=>"Visibilities"	972	800	{1369}	cmp_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Each compound has metadata, compound-type, which categorizes the type of compound.	http://www.gandhari.org/kanishka/model/document/Compound-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
988	en=>"Modified"	972	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Compound-Compound-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
989	en=>"Date"	2	783	\N	dat	Date record encapsulates attested data and calculations associated with the determination of a Date.	http://www.gandhari.org/kanishka/model/document/Date-Date.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
990	en=>"Annotations"	989	800	{881}	dat_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Date-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
991	en=>"Attributions"	989	800	{895}	dat_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Date-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
992	en=>"CalculatedYear"	989	793	\N	\N	Calculation of this value is predicated on their being a property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric. This value is calculated from the value in Era-Begindate of record cited in Date-EraPreferred and the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric.	http://www.gandhari.org/kanishka/model/document/Date-Date-CalculatedYear.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1004	en=>"Visibilities"	989	800	{1369}	dat_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Unsupported Data Issue of conjectured data in fields like ruler and era in Azes.  This data would seem to be redundant in the new model where Ruler is identified in Person entity and Era identified through Date and Era entities. date-calender Presumed calender date-datetype Category of presumed date date-day Attested day date-id System generated date-attestedyear Attested year date-month Attested month date-yearof Attested era attribution would default to kaniska catalog team term:datedentity identifies the date record with any entity record. term:datedentitycontext utilises a controlled vocabulary from term to apply a semantic to the relationship with the record identified in term:datedentity.	http://www.gandhari.org/kanishka/model/document/Date-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
993	en=>"DateEvidences"	989	810	\N	dat_evidences	Date record may have values in Date-DateEvidences field.  This field uses property value pairs to identify the type of evidence with the evidence value. The first value in the property value pair is a Term value nested under the Term-ParentTerm of 'DateEvidence'. If the Date record has a property value pair whose Term value has a Term-ParentTerm of 'conjectured' then: The evidence value component of this pair is empty. A default value for Date-ProbableBegin is not able to be calculated. A default value for Date-ProbableEnd is not able to be calculated. The basis for conjecture would ultimately rest with any Bibliography references in the Date-Attributions field. If the Date record has a property value pair whose Term value has a Term-ParentTerm of 'carbon14' and if that Term value is ‘measured’: Date-DateEvidences field must contain only a property value pair whose Term value has a Term-ParentTerm of ‘measured’ these include: ‘YearsBP-Number’ ‘BPPlusMinus-Number’ ‘CalibratedRange1StdDev-String’ ‘CalibratedRange2StdDev-String’ A default value for Date-ProbableBegin is calculated from values associated with 'yearsbp-number' and 'yearsbpplusminus-number'. A default value for Date-ProbableEnd is calculated from values associated with 'yearsbp-number' and 'yearsbpplusminus-number'.. The basis for calculation would ultimately rest with any Bibliography references in the Date-Attributions field. If the Date record has a property value pair whose Term value has a Term-ParentTerm of 'carbon14' and if that Term value is ‘extrapolated’: The evidence value component of this pair is empty. A default value for Date-ProbableBegin is not able to be calculated. A default value for Date-ProbableEnd is not able to be calculated. The basis for conjecture would ultimately rest with any Bibliography references in the Date-Attributions field. If the Date record has a property value pair whose Term value has a Term-ParentTerm of 'attested' and if that Term value is ‘extrapolated’: Date-DateEvidences field must contain only a property value pair whose Term value has a Term-ParentTerm of 'attested' these include: 'YearText' 'YearNumeric' 'āṣāḍha' 'CalenderDayText' 'CalenderDayNumeric' 'LunarDayText' A default value for Date-ProbableBegin, Date-ProbableEnd, Date-CalculatedYear, Where there is a property value pair in Date-DateEvidences whose Term component has a Term-ParentTerm value of ‘Measured, this value is calculated from the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPNumber' and the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPPlusMinusNumber' Date-TerminusAnteQuem and Date-TerminusPostQuem is able to be calculated if there is a value for 'YearNumeric'. Issue will be that in most circumstances we have the Year as earTet, a  reference to a compound number.  This however is no use for calculation purposes and basically would require one to (in addition tot eh YearText entry also develop a YearNumeric entry from which we can calculate.	http://www.gandhari.org/kanishka/model/document/Date-DateEvidences.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
994	en=>"Date-ID"	989	784	{989}	dat_id	\N	http://www.gandhari.org/kanishka/model/document/Date-Date-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
995	en=>"EraPreferred"	989	802	{1021}	dat_preferred_era_id	The Era record which is to be identified with this Date for the purposes of calculation of Date-CalculatedYear, Where there is a property value pair in Date-DateEvidences whose Term component has a Term-ParentTerm value of ‘Measured, this value is calculated from the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPNumber' and the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPPlusMinusNumber' Date-TerminusAnteQuem and Date-TerminusPostQuem.	http://www.gandhari.org/kanishka/model/document/Date-EraPreferred.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
996	en=>"Eras"	989	800	{1021}	dat_era_ids	The set of Era records (inclusive of the record Date-EraPreferred) which are to be identified with this Date for the purposes of calculation of Where there is a property value pair in Date-DateEvidences whose Term component has a Term-ParentTerm value of ‘Measured, this value is calculated from the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPNumber' and the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPPlusMinusNumber' Date-TerminusAnteQuem and Date-TerminusPostQuem.	http://www.gandhari.org/kanishka/model/document/Date-Eras.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
997	en=>"EvidenceFrom"	989	805	\N	dat_entity_id	Entity from which evidence for the date has been derived.  This evidence is recorded in the Date-DateEvidences field.	http://www.gandhari.org/kanishka/model/document/Date-EvidenceFrom.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
998	en=>"Owner"	989	802	{1369}	dat_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Date-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
999	en=>"ProbableBegin"	989	794	\N	dat_prob_begin_date	Each Date record must have a value for Date-ProbableBegin.  This field may default to a calculated value which may be overwritten: Where there is a property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric, this value is calculated from the value in Era-Begindate of record cited in Date-EraPreferred and the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric Where there is a property value pair in Date-DateEvidences whose Term component has a Term-ParentTerm value of ‘Measured, this value is calculated from the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPNumber' and the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPPlusMinusNumber'	http://www.gandhari.org/kanishka/model/document/Date-Date-ProbableBegin.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1000	en=>"ProbableEnd"	989	794	\N	dat_prob_end_date	Each Date record must have a value for Date-ProbableEnd.  This field may default to a calculated value which may be overwritten: Where there is a property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric, this value is calculated from the value in Era-EndDate of record cited in Date-EraPreferred and the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric Where there is a property value pair in Date-DateEvidences whose Term component has a Term-ParentTerm value of ‘Measured, this value is calculated from the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPNumber' and the value the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearsBPPlusMinusNumber'	http://www.gandhari.org/kanishka/model/document/Date-Date-ProbableEnd.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1001	en=>"Scratch"	989	786	\N	dat_scratch	\N	http://www.gandhari.org/kanishka/model/document/Date-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1002	en=>"TerminusAnteQuem"	989	793	\N	\N	Calculation of this value is predicated on their being a property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric. This value is calculated from from the latest value in Era-EndDate of records cited in Date-Eras and the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric.	http://www.gandhari.org/kanishka/model/document/Date-Date-TerminusAnteQuem.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1003	en=>"TerminusPostQuem"	989	793	\N	\N	Calculation of this value is predicated on their being a property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric. This value is calculated from from the earliest value in Era-Begindate of records cited in Date-Eras and the number component of the property value pair in Date-DateEvidences whose Term component has a value of ‘YearNumeric.	http://www.gandhari.org/kanishka/model/document/Date-Date-TerminusPostQuem.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1005	en=>"YearOf"	989	793	\N	\N	The Era-Title value corresponding to the value in Date-EraPreferred.	http://www.gandhari.org/kanishka/model/document/Date-Date-YearOf.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1006	en=>"Modified"	989	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Date-Date-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1007	en=>"Edition"	2	783	\N	edn	An Edition record identifies a set of Sequence records with a Text record to instantiate a particular Edition of a Text.	http://www.gandhari.org/kanishka/model/document/Edition-Edition.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1008	en=>"Annotations"	1007	800	{881}	edn_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Edition-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1009	en=>"Attributions"	1007	800	{895}	edn_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Edition-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1010	en=>"Description"	1007	795	\N	edn_description	This is a UI assisted text description.  When an Edition is cloned this would be calculated from the Attribution-Title of the cloned Edition.  For example, “Draft copy of Falk 2008 38-46” where Falk 2008 is derived from the Bibliography-Name and and 38-46 is derived from Attribution-Detail.  This field can be overwritten by the owner of the cloned Edition.	http://www.gandhari.org/kanishka/model/document/Edition-Edition-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1011	en=>"Label"	1007	791	\N	\N	A short form for display in the user interface.  Calculated from the AttributionGroup-Fullname field of the AttributionGroup identified with the Attribution record for this Edition and the date stamp from the Attribution-Detail field.  For Example “Stefan Baums - 1/01/2014”	http://www.gandhari.org/kanishka/model/document/Edition-Edition-Label.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1012	en=>"ID"	1007	784	{1007}	edn_id	\N	http://www.gandhari.org/kanishka/model/document/Edition-Edition-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1013	en=>"Owner"	1007	802	{1369}	edn_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Edition-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1014	en=>"RightsHolder"	1007	802	{908}	edn_attributiongroup_id	Identifies the Rights Holder for this Edition with a link to an AttributionGroup record.  If the value in this field is null; then the Rights Holder is derived from the value identified in Edition-Owner.   Copyright text might be applied through an Annotation on the Edition-RightsHolder field.  We are  ot currently supporting Annotations on fields.  We might: Use Annotation Type to put semantic of Copyright or … Text of Annotation may be a copyright statement URL of Annotation may be to a standard licensing text…	http://www.gandhari.org/kanishka/model/document/Edition-RightsHolder.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1015	en=>"Scratch"	1007	786	\N	edn_scratch	\N	http://www.gandhari.org/kanishka/model/document/Edition-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1016	en=>"Sequences"	1007	800	{1247}	edn_sequence_ids	Ordered set of linked Sequences.	http://www.gandhari.org/kanishka/model/document/Edition-Sequences.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1017	en=>"Text"	1007	802	{1316}	edn_text_id	Text record linked to this record.	http://www.gandhari.org/kanishka/model/document/Edition-Text.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1018	en=>"Visibilities"	1007	800	{1369}	edn_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. This 'physical' sequence record specifies a sequence of sequence records, 1 for each physical line in the text.   single Sequence record of Sequence-Type 'physical'.  This 'physical' Sequence record specifies a sequence of Tokens and/or Compounds which comprise an attributed edition of a text.  For longer text’s The 'text' sequence record may also be a sequence of sequences records which themselves specify a sequence of tokens and/or of compounds. A diplomatic, reconstructed and hybrid edition are generated from the sequence of tokens specified in the 'transcription' sequence record. Each edition record may also have one or more sequence records of sequencetype 'division'.  The sequence of tokens specified in the 'division' sequence record must be the same as or a subset of the sequence of tokens specified in the 'transcription' sequence record. Each edition record may also have one or more sequence records of sequencetype 'reference'.  The sequence of tokens specified in the 'reference' sequence record must be the same as or a subset of the sequence of tokens specified in the 'transcription' sequence record. Each edition record may also have one or more other sequence records (e.g sequencetype 'relicestablishment'.  The sequence of tokens specified in the 'relicestablishment' sequence record must be the same as or a subset of the sequence of tokens specified in the 'transcription' sequence record. Each edition record may have a single textmetadata record associated. Each edition record may have a multiple documentation records associated.  It is anticipated that this approach might be used to link a translation of the text edition-id Concatenate inscription-id with system generated number edition-rendition Specification of which renditions are to be generated for this edition. edition-translation Fluid Translation of inscription.	http://www.gandhari.org/kanishka/model/document/Edition-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1019	en=>"Type"	1007	806	{475,476,477}	edn_type_id	A Term record with a constrained vocabulary derived from EditionType.	http://www.gandhari.org/kanishka/model/document/Edition-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1020	en=>"Modified"	1007	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Edition-Edition-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1021	en=>"Era"	2	783	\N	era	Era records temporal data about an Era.	http://www.gandhari.org/kanishka/model/document/Era-Era.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1022	en=>"Annotations"	1021	800	{881}	era_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Era-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1023	en=>"Attributions"	1021	800	{895}	era_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Era-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1024	en=>"Begindate"	1021	154	\N	era_begin_date	The beginning date of this Era in BCE/CE.  Negative values indicate BCE.	http://www.gandhari.org/kanishka/model/document/Era-Era-Begindate.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1025	en=>"EndDate"	1021	154	\N	era_end_date	The end date of this Era in BCE/CE.  Negative values indicate BCE.	http://www.gandhari.org/kanishka/model/document/Era-Era-EndDate.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1026	en=>"ID"	1021	784	{1021}	era_id	\N	http://www.gandhari.org/kanishka/model/document/Era-Era-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1027	en=>"Order"	1021	531	\N	era_order	The ordinal position of the Era represented in Era-Title in the temporal order as derived from scholarship.  Era records with the same value in Era-Title will have the same value Era-Order.  This field is restricted to a special UserGroup with a Full name of User or group of Users. UserGroup-Name of 'Catalog'.	http://www.gandhari.org/kanishka/model/document/Era-Era-Order.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1028	en=>"Owner"	1021	802	{1369}	era_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Era-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1029	en=>"Preferred"	1021	789	\N	era_preferred	True/False indicator as to whether this record is the 'preferred' record for the era.  This field is restricted to a special UserGroup with a Full name of User or group of Users. UserGroup-Name of 'Catalog'.	http://www.gandhari.org/kanishka/model/document/Era-Era-Preferred.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1030	en=>"Scratch"	1021	786	\N	era_scratch	\N	http://www.gandhari.org/kanishka/model/document/Era-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1032	en=>"Visibilities"	1021	800	{1369}	era_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. era-title (at this stage it would not seem feasible to make this a pointet to person record) era-order, is the temporal order of eras as identified by catalog admistrators as the preferred vales for system purposes.  (Might need to unpack whther and why we nmight need this.) era-preferreed, is the era selected by catalog admistrators as the preferred vales for system purposes.  (Might need to unpack whther and why we nmight need this.) era-begindate is the date proposed (BCE/CE) for beginning of era  era-enddate is is the date proposed (BCE/CE) for end of era	http://www.gandhari.org/kanishka/model/document/Era-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1033	en=>"Modified"	1021	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Era-Era-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1034	en=>"Fragment"	2	783	\N	frg	A Fragment is a piece of a Part.  A Fragment is a piece that is not intentionally separate from that Part rather it is separate through fracture or degradation.	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1035	en=>"Annotations"	1034	800	{881}	frg_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Fragment-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1036	en=>"Attributions"	1034	800	{895}	frg_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Fragment-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1037	en=>"Description"	1034	785	\N	frg_description	Free text description.	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1038	en=>"ID"	1034	784	{1034}	frg_id	\N	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1039	en=>"Images"	1034	800	{1067}	frg_image_ids	Images associated with the Fragment.  An unordered set of Image records.	http://www.gandhari.org/kanishka/model/document/Fragment-Images.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1040	en=>"Label"	1034	785	\N	frg_label	A unique label identifying that Fragment.  Generally employed to support historical labelling systems.	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-Label.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1041	en=>"Location-LocationReferences"	1034	786	\N	frg_location_refs	\N	http://www.gandhari.org/kanishka/model/document/Fragment-Location-LocationReferences.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1042	en=>"MaterialContext"	1034	800	{1159}	frg_material_context_ids	The find and archaeological context of the Fragment.  A MaterialContext record linked to this record.	http://www.gandhari.org/kanishka/model/document/Fragment-MaterialContext.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1043	en=>"Measurement"	1034	785	\N	frg_measure	tbd	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-Measurement.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1044	en=>"Owner"	1034	802	{1369}	frg_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Fragment-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1045	en=>"Part"	1034	802	{1183}	frg_part_id	The Part this Fragmentis a piece of.	http://www.gandhari.org/kanishka/model/document/Fragment-Part.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1046	en=>"PartFragmentDescription"	1034	791	\N	\N	A calculated field which concatenates values from Part-ItemPartDescription and Fragment-Description.  A structured description which encompasses both the Fragment and the Part of which it is a piece.	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-PartFragmentDescription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1047	en=>"RestorationState"	1034	806	{718,719}	frg_restore_state_id	Restoration state of the Fragment.  A Term record with a constrained vocabulary derived from RestorationState.	http://www.gandhari.org/kanishka/model/document/Fragment-RestorationState.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1048	en=>"Scratch"	1034	786	\N	frg_scratch	\N	http://www.gandhari.org/kanishka/model/document/Fragment-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1049	en=>"Scripts"	1034	791	\N	\N	A calculated field which concatenates values from each related Surface record.  Where values are duplicated (e.g Kharoṣṭhī, Kharoṣṭhī) only one instance is displayed.	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-Scripts.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1050	en=>"Visibilities"	1034	800	{1369}	frg_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. A fragment may have a links to object and materialcontext entities.  Note that it may become expedient to also include links to object and materialcontext entities in item if it is found that there is a large degree of data duplication at fragment level.	http://www.gandhari.org/kanishka/model/document/Fragment-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1051	en=>"Modified"	1034	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Fragment-Fragment-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1052	en=>"Grapheme"	2	783	\N	gra	Grapheme is the smallest Transcription Unit of a Segment.	http://www.gandhari.org/kanishka/model/document/Grapheme-Grapheme.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1053	en=>"AlternativeGrapheme"	1052	795	\N	gra_alt	This field has a value for an alternative Transcription Unit possibility for this Grapheme when the value for Grapheme-TextCriticalMark is "/"; the editor identifies both preferred and an alternative Transcription Unit possibilities.	http://www.gandhari.org/kanishka/model/document/Grapheme-AlternativeGrapheme.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1054	en=>"Annotations"	1052	800	{881}	gra_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Grapheme-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1055	en=>"Decomposition"	1052	795	\N	gra_decomposition	Whare a Grapheme is the subject of Sandhi effects, the sandhi decomposition string is stored in Grapheme-Decomposition.  For example, the Grapheme e in tatreva (represneted as tatre~e,a e~va) will have a Grapheme-Decomposition property "a:e".	http://www.gandhari.org/kanishka/model/document/Grapheme-Grapheme-Decomposition.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1392	en=>"4"	1388	778	\N	bt4	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1056	en=>"EmendationFrom"	1052	795	\N	gra_emmendation	This field has a value for an emended Transcription Unit possibility for this Grapheme when the value for Grapheme-TextCriticalMark is "to be developed; the editor identifies and emendation to the Transcription Unit executed by the Scribe.	http://www.gandhari.org/kanishka/model/document/Grapheme-EmendationFrom.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1057	en=>"Grapheme"	1052	795	\N	gra_grapheme	Transcription Unit value.	http://www.gandhari.org/kanishka/model/document/Grapheme-Grapheme.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1058	en=>"Grapheme-Sc"	1057	791	\N	gra_sort_code	Numeric code used to sort Graphemes.  These values are concatenated for Graphemes sequenced in the SyllableCluster-Graphemes field to form a value for SyllableCluster-SyllableCluster-SC. The Grapheme-Grapheme-Sc value is unique to each Transcription Unit.  The only exception to this is Anusvāra and Visarga.  It is anticipated the Anusvāra and Visarga would have a default Grapheme-Grapheme-Sc value.  It is anticipated that contextual calculation of Anusvāra and Visargawould be handled at Token level.	http://www.gandhari.org/kanishka/model/document/Grapheme-Grapheme-Sc.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1059	en=>"ID"	1057	784	{1052}	gra_id	\N	http://www.gandhari.org/kanishka/model/document/Grapheme-Grapheme-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1060	en=>"Owner"	1057	802	{1369}	gra_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Grapheme-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1061	en=>"Scratch"	1057	786	\N	gra_scratch	\N	http://www.gandhari.org/kanishka/model/document/Grapheme-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1062	en=>"TextCriticalMark"	1057	795	\N	gra_text_critical_mark	A Grapheme record may have a Grapheme-TextCriticalMark associated with the value in Grapheme-Grapheme.  Values entered into the SyllableCluster-TextCriticalMark field are populated into the Grapheme-TextCriticalMark field of the Graphemes sequenced in the SyllableCluster-Graphemes field. If the value in grapheme-textcriticalmark is a '.' or a '?' then this value would be populated into the grapheme-grapheme field.	http://www.gandhari.org/kanishka/model/document/Grapheme-TextCriticalMark.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1063	en=>"Type"	1057	806	{503,504,505,506,507,508,509}	gra_type_id	A Term record with a constrained vocabulary derived from GraphemweType.	http://www.gandhari.org/kanishka/model/document/Grapheme-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1064	en=>"Visibilities"	1057	800	{1369}	gra_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. It is anticipated that the process of identification of a syllablecluster with a segment through construction of grapheme records which are sequenced to form that syllablecluster would need to be handled in the UI. Each grapheme record also has an associated onset.  Where the grapheme record is a member of a consonant cluster that grapheme record shares the same onset as the other member(s) of that consonant cluster. This field has a value for an alternative Transcription Unit possibility for this Grapheme when the value for Grapheme-TextCriticalMark is "/".  The first value being the value in grapheme-grapheme and the second being the value in this field. If there is a value in the syllablecluster-textcriticalmark field that this grapheme is sequenced into, then that value needs to be used to populate this field by default. Otherwise, the editor may pick from a list of textcriticalmarks relevant to grapheme level. Critical Marking used to identify aspects of the script critical to this interpretation. A grapheme record has a grapheme-type; Consonant, Vowel, numeral, punctuation or unknown.	http://www.gandhari.org/kanishka/model/document/Grapheme-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1065	en=>"UpperCase"	1057	795	\N	gra_uppercase	Transcription Unit value in upper case.	http://www.gandhari.org/kanishka/model/document/Grapheme-UpperCase.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1066	en=>"Modified"	1057	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Grapheme-Grapheme-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1067	en=>"Image"	2	783	\N	img	An Image is a visual representation (digital photograph, scan, drawing, eye copy, rubbing or other representation) of an Item, Part, Fragment or Surface.	http://www.gandhari.org/kanishka/model/document/Image-Image.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1068	en=>"Annotations"	1067	800	{881}	img_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Image-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1069	en=>"Attributions"	1067	800	{895}	img_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Image-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1070	en=>"ID"	1067	784	{1067}	img_id	\N	http://www.gandhari.org/kanishka/model/document/Image-Image-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1071	en=>"Owner"	1067	802	{1369}	img_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Image-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1072	en=>"Position"	1067	798	\N	img_image_pos	Xycoordinates (polygon) of a cropped portion of an image file.	http://www.gandhari.org/kanishka/model/document/Image-Image-Position.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1073	en=>"Scratch"	1067	786	\N	img_scratch	\N	http://www.gandhari.org/kanishka/model/document/Image-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1074	en=>"Text"	1067	785	\N	img_title	Title of the image.	http://www.gandhari.org/kanishka/model/document/Image-Image-Text.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1075	en=>"Type"	1067	806	{515,516,517,518,519,520,521,522,523}	img_type_id	The production type of the image file.  A Term record with a constrained vocabulary derived from ImageType.	http://www.gandhari.org/kanishka/model/document/Image-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1076	en=>"URL"	1067	785	\N	img_url	URL for image file.	http://www.gandhari.org/kanishka/model/document/Image-Image-URL.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1103	en=>"Images"	1098	800	{1067}	itm_image_ids	An unordered set of Image records associated with the Item.	http://www.gandhari.org/kanishka/model/document/Item-Images.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1104	en=>"Measurement"	1098	785	\N	itm_measure	tbd	http://www.gandhari.org/kanishka/model/document/Item-Item-Measurement.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1393	en=>"5"	1388	778	\N	bt5	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1077	en=>"Visibilities"	1067	800	{1369}	img_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Where we have only a rubbing or an eye copy, then for these purposes, that image is associated with that surface as the baseline. Each image has an image-access of 'public', 'restricted'... Each image has an image-category e.g 'inscriptionphotograph', 'inscriptionphotographinfrared'…	http://www.gandhari.org/kanishka/model/document/Image-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1078	en=>"Modified"	1067	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Image-Image-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1079	en=>"Inflection"	2	783	\N	inf	An Inflection mediates the relationship between an Inflected Token or Compound and a Lemma.	http://www.gandhari.org/kanishka/model/document/Inflection-Inflection.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1080	en=>"Annotations"	1079	800	{881}	inf_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Inflection-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1081	en=>"Attributions"	1079	800	{895}	inf_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Inflection-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1082	en=>"Case"	1079	806	{359,360,361,362,363,364,365,366,367,368,369,370}	inf_case_id	Term record with a constrained vocabulary derived from Case.	http://www.gandhari.org/kanishka/model/document/Inflection-Case.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1084	en=>"GrammaticalNumber"	1079	806	{497,498,499,500,501}	inf_gram_number_id	Term record with a constrained vocabulary derived from GrammaticalNumber.	http://www.gandhari.org/kanishka/model/document/Inflection-GrammaticalNumber.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1085	en=>"ID"	1079	784	{1345}	inf_id	\N	http://www.gandhari.org/kanishka/model/document/Inflection-Inflection-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1086	en=>"Owner"	1079	802	{1369}	inf_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Inflection-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1087	en=>"Scratch"	1079	786	\N	inf_scratch	\N	http://www.gandhari.org/kanishka/model/document/Inflection-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1088	en=>"VerbalMood"	1079	806	{842,843,844,845,846}	inf_verb_mood_id	Term record with a constrained vocabulary derived from VerbalMood.	http://www.gandhari.org/kanishka/model/document/Inflection-VerbalMood.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1089	en=>"VerbalPerson"	1079	806	{848,849,850,851,852}	inf_verb_person_id	Term record with a constrained vocabulary derived from VerbalPerson.	http://www.gandhari.org/kanishka/model/document/Inflection-VerbalPerson.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1090	en=>"VerbalSecondaryConjugation"	1079	806	{854,855,856,857,858,859,860,861}	inf_verb_second_conj_id	Term record with a constrained vocabulary derived from VerbalSecondaryConjugation.	http://www.gandhari.org/kanishka/model/document/Inflection-VerbalSecondaryConjugation.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1091	en=>"VerbalTense"	1079	806	{863,864,865,866,867,868,869,870,871,872}	inf_verb_tense_id	Term record with a constrained vocabulary derived from VerbalTense.	http://www.gandhari.org/kanishka/model/document/Inflection-VerbalTense.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1092	en=>"VerbalVoice"	1079	806	{874,875,876,877}	inf_verb_voice_id	Term record with a constrained vocabulary derived from VerbalVoice.	http://www.gandhari.org/kanishka/model/document/Inflection-VerbalVoice.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1093	en=>"Visibilities"	1079	800	{1369}	inf_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Inflection-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1094	en=>"Certainty"	1079	800	{375,376,377}	inf_certainty	A sequence of Term records with a constrained vocabulary derived from Certainty.  The sequence corresponds to the level of certainty a user ascribes to the nominated values in Inflection-Case, Inflection-GrammaticalNumber, Inflection-VerbalPerson, Inflection-VerbalVoice, Inflection-VerbalTense, Inflection-VerbalMood, Inflection-VerbalSecondaryConjugation.	http://www.gandhari.org/kanishka/model/document/Inflection-Certainty.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1095	en=>"Forms"	1079	803	{1345,972}	inf_component_ids	Homogenous set of Token or Compound records.	http://www.gandhari.org/kanishka/model/document/Inflection-Forms.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1096	en=>"Gender"	1079	806	{483,484,485,486,487,1381,1382}	inf_nominal_gender_id	A Term record with a constrained vocabulary derived from Gender.	http://www.gandhari.org/kanishka/model/document/Inflection-Gender.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1097	en=>"Modified"	1079	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Inflection-Inflection-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1098	en=>"Item"	2	783	\N	itm	An Item is an object, or set of integrated objects, at least one of which has a Kharosthī Akṣara on it.	http://www.gandhari.org/kanishka/model/document/Item-Item.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1099	en=>"Annotations"	1098	800	{881}	itm_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Item-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1100	en=>"Attributions"	1098	800	{895}	\N	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Item-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1101	en=>"Description"	1098	791	\N	\N	Calculated from the concatenation of values from Item-Type and Item-Mediums.  The default calculated value may be overwritten with a structred description. Standards, Protocols and Examples A structured description which encompasses all the Part or Fragment records encapsulated in that Item.	http://www.gandhari.org/kanishka/model/document/Item-Item-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1102	en=>"ID"	1098	784	{1098}	itm_id	\N	http://www.gandhari.org/kanishka/model/document/Item-Item-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1105	en=>"Mediums"	1098	792	\N	\N	Calculated from the concatenation of values from Part-Mediums of each related Part identified with this Item.  Where values are duplicated (e.g Stone, Stone) only one instance is displayed. Standards, Protocols and Examples A structured description which encompasses all the Part or Fragment records encapsulated in that Item.	http://www.gandhari.org/kanishka/model/document/Item-Item-Mediums.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1106	en=>"Owner"	1098	802	{1369}	itm_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Item-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1107	en=>"Scratch"	1098	786	\N	itm_scratch	\N	http://www.gandhari.org/kanishka/model/document/Item-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1108	en=>"Scripts"	1098	792	\N	\N	Calculated from the concatenation of values from Part-Scripts of each related Part identified with this Item.  Where values are duplicated (e.g Kharoṣṭhī, Kharoṣṭhī) only one instance is displayed. Standards, Protocols and Examples A structured description which encompasses all the Part or Fragment records encapsulated in that Item.	http://www.gandhari.org/kanishka/model/document/Item-Item-Scripts.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1109	en=>"Shape"	1098	806	\N	itm_shape_id	The shape of the Item.  A Term record with a constrained vocabulary derived from ObjectShape .	http://www.gandhari.org/kanishka/model/document/Item-Shape.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1110	en=>"Title"	1098	785	\N	itm_title	An historical title for this Item which might have been used in an Edition Reference, Catalog Reference or other Reference. Standards, Protocols and Examples tbd – as items are not currently distinguished from texts there is no existing data.  We might edit the related text title.  So 'Swāt relic vase inscription of the Meridarkh Theodoros' becomes 'Swāt relic vase' or 'Swāt relic vase of the Meridarkh Theodoros'	http://www.gandhari.org/kanishka/model/document/Item-Item-Title.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1111	en=>"Type"	1098	806	\N	itm_type_id	The artefact type of the Item.  A Term record with a constrained vocabulary derived from ObjectType.	http://www.gandhari.org/kanishka/model/document/Item-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1112	en=>"Visibilities"	1098	800	{1369}	itm_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Item-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1113	en=>"Modified"	1098	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Item-Item-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1114	en=>"JSONCache"	2	783	\N	jsc	To be developed.	http://www.gandhari.org/kanishka/model/document/JSONCache-JSONCache.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1115	en=>"ID"	1114	784	{1316}	jsc_id	\N	http://www.gandhari.org/kanishka/model/document/JSONCache-JSONCache-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1116	en=>"Type"	1114	806	{577,578}	jsc_type_id	A Term record with a constrained vocabulary derived from JSONCacheType.	http://www.gandhari.org/kanishka/model/document/JSONCache-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1117	en=>"Label"	1114	785	\N	jsc_label	To be developed	http://www.gandhari.org/kanishka/model/document/JSONCache-JSONCache-Label.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1118	en=>"JSONString"	1114	785	\N	jsc_json_string	To be developed	http://www.gandhari.org/kanishka/model/document/JSONCache-JSONCache-JSONString.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1119	en=>"Owner"	1114	802	{1369}	jsc_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/JSONCache-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1120	en=>"Annotations"	1114	800	{881}	jsc_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/JSONCache-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1121	en=>"Attributions"	1114	800	{895}	jsc_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/JSONCache-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1122	en=>"Visibilities"	1114	800	{1369}	jsc_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/JSONCache-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1123	en=>"Modified"	1114	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/JSONCache-JSONCache-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1124	en=>"Scratch"	1114	786	\N	jsc_scratch	\N	http://www.gandhari.org/kanishka/model/document/JSONCache-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1125	en=>"Line"	2	783	\N	lin	\N	http://www.gandhari.org/kanishka/model/document/Line-Line.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1126	en=>"Annotations"	1125	800	{881}	lin_annotation_ids	\N	http://www.gandhari.org/kanishka/model/document/Line-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1127	en=>"Attributions"	1125	800	{895}	lin_attribution_ids	\N	http://www.gandhari.org/kanishka/model/document/Line-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1128	en=>"Line-ID"	1125	784	{1125}	lin_id	\N	http://www.gandhari.org/kanishka/model/document/Line-Line-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1129	en=>"Line-Mask"	1125	785	\N	lin_mask	\N	http://www.gandhari.org/kanishka/model/document/Line-Line-Mask.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1130	en=>"Line-Order"	1125	531	\N	lin_order	\N	http://www.gandhari.org/kanishka/model/document/Line-Line-Order.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1131	en=>"Owner"	1125	802	{1369}	lin_owner_id	\N	http://www.gandhari.org/kanishka/model/document/Line-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1132	en=>"Scratch"	1125	786	\N	lin_scratch	\N	http://www.gandhari.org/kanishka/model/document/Line-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1133	en=>"Spans"	1125	801	{1260}	lin_span_ids	\N	http://www.gandhari.org/kanishka/model/document/Line-Spans.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1134	en=>"Visibilities"	1125	800	{1369}	lin_visibility_ids	\N	http://www.gandhari.org/kanishka/model/document/Line-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1135	en=>"Lemma"	2	783	\N	lem	Lemma is the Citation Form used to aggregate Inflections or Tokens or Compounds as attested forms of that Lemma.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1136	en=>"Annotations"	1135	800	{881}	lem_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Lemma-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1137	en=>"Attributions"	1135	800	{895}	lem_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Lemma-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1138	en=>"Declension"	1135	806	{452,453,454,455,456,457,458,459,460,461,462,463,464,465,466,467,468,469,470,471,472,473}	lem_declension_id	A Term record with a constrained vocabulary derived from Declemsion.	http://www.gandhari.org/kanishka/model/document/Lemma-Declension.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1139	en=>"HomographOrder"	1135	531	\N	lem_homographOrder	Numeric identifier to disambiguate Homographs	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-HomographOrder.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1140	en=>"ID"	1135	784	{1135}	lem_id	\N	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1141	en=>"Lemma"	1135	785	\N	lem_value	The Citation Form of the Lemma.  The Language of the Lemma is identified through its inclusion in a Catalog of Catalog-Type of 'Glossary' or 'Dictionary' and specification in the Catalog-SourceLanguage field. Lemma-Lemma may, in circumstances where the only Attested Form of a Token or Compound has Text Critical Marks, include those Text Critical Marks.  Lemma-Lemma may also include Morpheme markers implemented as a center dot character.  Need to elaborate on the actual character.  (middle dot Alt+183/U+00B7 )	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-Lemma.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1142	en=>"Search"	1141	785	\N	lem_search	The search value of the Lemma.  Lemma-Search is calculated from Lemma-Lemma by stripping out any Text Critical Marks and Morpheme markers.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-Search.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1143	en=>"NominalGender"	1141	806	{489,490,491,492,493,494,495,1383,1384}	lem_nominal_gender_id	A Term record with a constrained vocabulary derived from Gender.	http://www.gandhari.org/kanishka/model/document/Lemma-NominalGender.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1144	en=>"Owner"	1141	802	{1369}	lem_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Lemma-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1145	en=>"PartOfSpeech"	1141	806	{643,644,645,646,647,648,649,650,651,652,653,654,655,656,657,658,659,660,661,662,663,664,667,674,681,685}	lem_part_of_speech_id	A Term record with a constrained vocabulary derived from PartOfSpeech.	http://www.gandhari.org/kanishka/model/document/Lemma-PartOfSpeech.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1146	en=>"SubPartOfSpeech"	1141	806	{643,644,645,646,647,648,649,650,651,652,653,654,655,656,657,658,659,660,661,662,663,664,667,674,681,685}	lem_subpart_of_speech_id	A Term record with a constrained vocabulary derived from PartOfSpeech.	http://www.gandhari.org/kanishka/model/document/Lemma-SubPartOfSpeech.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1147	en=>"Scratch"	1141	786	\N	lem_scratch	\N	http://www.gandhari.org/kanishka/model/document/Lemma-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1148	en=>"Translation"	1141	785	\N	lem_translation	The English translation of Lemma-Lemma.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-Translation.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1149	en=>"Type"	1141	806	{622,623,624,625,626}	lem_type_id	A Term record with a constrained vocabulary derived from LemmaType.	http://www.gandhari.org/kanishka/model/document/Lemma-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1150	en=>"VerbalClass"	1141	806	{829,830,831,832,833,834,835,836,837,838,839,840}	lem_verb_class_id	A Term record with a constrained vocabulary derived from VerbalClass.	http://www.gandhari.org/kanishka/model/document/Lemma-VerbalClass.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1151	en=>"Visibilities"	1141	800	{1369}	lem_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. A lemma record may have associated with it a, lemma-translation A lemma record may have associated with it a lemma-partofspeech and a lemma-declension A lemma record may have associated with it a lemma-verbalclass	http://www.gandhari.org/kanishka/model/document/Lemma-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1152	en=>"Certainty"	1141	807	{375,376,377}	lem_certainty	A sequence of Term records with a constrained vocabulary derived from Certainty.  The sequence corresponds to the level of certainty a user ascribes to the nominated values in Lemma-PartOfSpeech, Lemma-SubPartOfSpeech, Lemma-NominalGender, Lemma-Declension and Lemma-VerbalClass.	http://www.gandhari.org/kanishka/model/document/Lemma-Certainty.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1153	en=>"Catalog"	1141	802	{946}	lem_catalog_id	Catalog record linked to this record.  It defines which Catalog this Lemma is contained in.	http://www.gandhari.org/kanishka/model/document/Lemma-Catalog.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1154	en=>"SC"	1141	791	\N	lem_sort_code	The Lemma-SC value is the primary sort order for Lemmas.  It is anticipated that contextual calculation of Anusvāra and Visarga would be enabled for Sanskrit but not for Gāndhārī.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-SC.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1155	en=>"SC2"	1141	791	\N	lem_sort_code2	The Lemma-SC2 value is the primary sort order for Lemmas.  It is anticipated that contextual calculation of Anusvāra and Visarga would be enabled for Sanskrit but not for Gāndhārī.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-SC2.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1156	en=>"Description"	1141	785	\N	lem_description	Free text gloss of Lemma.  It is anticipated that Lemma-Description may include URI linkages to related Lemma.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1157	en=>"Forms"	1141	803	{1345,972,1079}	lem_component_ids	Homogenous set of Inflection, Token or Compound records.	http://www.gandhari.org/kanishka/model/document/Lemma-Forms.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1394	en=>"6"	1388	778	\N	bt6	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1158	en=>"Modified"	1141	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Lemma-Lemma-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1159	en=>"MaterialContext"	2	783	\N	mcx	MaterialContext encapsulates the Archaeological Context and Find Context of a Fragment. Entity Attributes	http://www.gandhari.org/kanishka/model/document/MaterialContext-MaterialContext.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1160	en=>"Annotations"	1159	800	{881}	mcx_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/MaterialContext-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1161	en=>"ArchaeologicalContext"	1159	785	\N	mcx_arch_context	Free text description of the Archaeological Context of the Fragment.	http://www.gandhari.org/kanishka/model/document/MaterialContext-MaterialContext-ArchaeologicalContext.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1162	en=>"Attributions"	1159	800	{895}	mcx_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/MaterialContext-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1163	en=>"FindStatus-Attributions"	1159	809	\N	mcx_find_status	A Term record with a constrained vocabulary derived from FindStatus combined with an associated Attribution.	http://www.gandhari.org/kanishka/model/document/MaterialContext-FindStatus-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1164	en=>"MaterialContextDescription"	1159	791	\N	\N	tbd	http://www.gandhari.org/kanishka/model/document/MaterialContext-MaterialContext-MaterialContextDescription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1165	en=>"MaterialContext-ID"	1159	784	{1159}	mcx_id	\N	http://www.gandhari.org/kanishka/model/document/MaterialContext-MaterialContext-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1166	en=>"Owner"	1159	802	{1369}	mcx_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/MaterialContext-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1167	en=>"Scratch"	1159	786	\N	mcx_scratch	\N	http://www.gandhari.org/kanishka/model/document/MaterialContext-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1168	en=>"Visibilities"	1159	800	{1369}	mcx_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Each materialcontext record is attributed.	http://www.gandhari.org/kanishka/model/document/MaterialContext-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1169	en=>"Modified"	1159	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/MaterialContext-MaterialContext-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1170	en=>"Metadata"	2	783	\N	mdt	To be developed from specification in metadata.doc Entity Attributes	http://www.gandhari.org/kanishka/model/document/Metadata-Metadata.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1171	en=>"ID"	1170	784	{1316}	mdt_id	\N	http://www.gandhari.org/kanishka/model/document/Metadata-Metadata-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1172	en=>"Type"	1170	806	{631,632}	mdt_type_id	A Term record with a constrained vocabulary derived from MetadataType.	http://www.gandhari.org/kanishka/model/document/Metadata-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1173	en=>"Classifications"	1170	800	{379,384}	mdt_classification_ids	A Term record with a constrained vocabulary derived from Classification.	http://www.gandhari.org/kanishka/model/document/Metadata-Classifications.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1174	en=>"Owner"	1170	802	{1369}	mdt_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Metadata-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1175	en=>"Annotations"	1170	800	{881}	mdt_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Metadata-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1176	en=>"Attributions"	1170	800	{895}	mdt_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Metadata-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1177	en=>"Visibilities"	1170	800	{1369}	mdt_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. TextMetaData Entity – rationale for not just using  aprperty=value pair on Text  Currently have an attributed TextMetadata entity with a term foreign key.   Doesn’t make sense to have a property value pair on this at is already attributed. At this stage implementation is predicated up building a new attributed record and applying both term values. This seems reasonable given the extent of metadata configuration one might want to undertake on Text entity and the number of records seems supportable.  Inscriptions 800 * n Don’t think we can or need to take the same approach with any other entity. The term:editionreference-consulted field identifies a categorizing term with a pointer to the attribution entity and aggregates all editions refernces catalogs and archaeological reports consulted. Niya Specific Fields – This is dealt with in Field Mappings KI#  implemented in editionrefernce in catalog field as pointer to attribution Stein # - implemented in editionrefernce in catalog field as pointer to attribution BL Reference - implemented in fragment in fragment-location and fragment-locationrefernece Site – implemented using materialcontext-country, materialcontext-region and materialcontext-findspot Ruin –implemented using materialcontext-ruin Room – implemented using materialcontext-room Manuscript Specific Fields – This is dealt with in Field Mappings Basis for date – use date-datedeterminationtype Maintainer – the usage of this field seems to have been overtaken by ownership in the group entity.  Will need to look at how to implement what data there is in this field in Azes	http://www.gandhari.org/kanishka/model/document/Metadata-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1178	en=>"References"	1170	800	{895}	mdt_reference_ids	Attribution records, Editions, References, Catalogs and Archaeological Reports, identified by the Metadata-Owner with the records identified in Metadata-Referents.	http://www.gandhari.org/kanishka/model/document/Metadata-References.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1179	en=>"Images"	1170	800	{1067}	mdt_image_ids	Image records identified by the Metadata-Owner with the records identified in Metadata-Referents.	http://www.gandhari.org/kanishka/model/document/Metadata-Images.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1180	en=>"Scratch"	1170	786	\N	mdt_scratch	\N	http://www.gandhari.org/kanishka/model/document/Metadata-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1181	en=>"Referents"	1170	803	{946,1316,1007,959,1098,1183,1034,1270}	mdt_referent_ids	The set of records that this Metadata record is identified with.  These may be any of, but not a mixture of, Catalogs or Texts or Editions or Collections or Items or Parts or Fragments or Surfaces.	http://www.gandhari.org/kanishka/model/document/Metadata-Referents.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1182	en=>"Modified"	1170	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Metadata-Metadata-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1183	en=>"Part"	2	783	\N	prt	A part is a physically distinct component of an Item.  A Part is intentionally separated fabrication rather than fracture or degradation.  A coin has one Part, a reliquary with base and lid has two.  Palm-leaf manuscripts may have hundreds.  Sheets of birch bark that were once sown or glued together belong to the same Part.	http://www.gandhari.org/kanishka/model/document/Part-Part.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1184	en=>"Annotations"	1183	800	{881}	prt_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Part-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1185	en=>"Attributions"	1183	800	{895}	\N	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Part-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1186	en=>"ID"	1183	784	{1183}	prt_id	\N	http://www.gandhari.org/kanishka/model/document/Part-Part-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1187	en=>"Images"	1183	800	{1067}	prt_image_ids	An unordered set of Image records associated with the Part.	http://www.gandhari.org/kanishka/model/document/Part-Images.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1188	en=>"Item"	1183	802	{1098}	prt_item_id	The Item this Part is a component of.	http://www.gandhari.org/kanishka/model/document/Part-Item.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1189	en=>"ItemPartDescription"	1183	791	\N	\N	Calculated from the concatenation of values from Item-Description and Part-Type.  The default calculated value may be overwritten with a structred description. Standards, Protocols and Examples A structured description which encompasses all the Fragment records encapsulated in that Part	http://www.gandhari.org/kanishka/model/document/Part-Part-ItemPartDescription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1190	en=>"Label"	1183	785	\N	prt_label	A unique label identifying that Part.  This would generally be employed to support historical labelling systems. Standards, Protocols and Examples tbd	http://www.gandhari.org/kanishka/model/document/Part-Part-Label.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1191	en=>"ManufactureTechnique"	1183	806	\N	prt_manufacture_id	Technique used in Part manufacture.  A Term record with a constrained vocabulary derived from ManufactureTechnique.	http://www.gandhari.org/kanishka/model/document/Part-ManufactureTechnique.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1192	en=>"Measurement"	1183	785	\N	prt_measure	tbd	http://www.gandhari.org/kanishka/model/document/Part-Part-Measurement.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1193	en=>"Mediums"	1183	807	\N	prt_mediums	Mediums from which the Part is constituted.  An unordered set of Term records with a constrained vocabulary derived from ObjectMedium.	http://www.gandhari.org/kanishka/model/document/Part-Mediums.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1194	en=>"Owner"	1183	802	{1369}	prt_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Part-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1195	en=>"Scratch"	1183	786	\N	prt_scratch	\N	http://www.gandhari.org/kanishka/model/document/Part-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1196	en=>"Scripts"	1183	792	\N	\N	Calculated from the concatenation of values from Fragment-Scripts of each related Fragment identified with this Part.  Where values are duplicated (e.g Kharoṣṭhī, Kharoṣṭhī) only one instance is displayed. Standards, Protocols and Examples A structured description which encompasses all the Fragment records encapsulated in that Part.	http://www.gandhari.org/kanishka/model/document/Part-Part-Scripts.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1197	en=>"Sequence"	1183	531	\N	prt_sequence	Numbered order of a Part where there is an identifiable physical sequence, e.g. the numbered leaves in a folio. Standards, Protocols and Examples Tbd	http://www.gandhari.org/kanishka/model/document/Part-Part-Sequence.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1198	en=>"Shape"	1183	806	\N	prt_shape_id	The shape of the Part.  A Term record with a constrained vocabulary derived from ObjectShape.	http://www.gandhari.org/kanishka/model/document/Part-Shape.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1199	en=>"Type"	1183	806	\N	prt_type_id	The artefact type of the Part.  A Term record with a constrained vocabulary derived from ObjectType.	http://www.gandhari.org/kanishka/model/document/Part-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1200	en=>"Visibilities"	1183	800	{1369}	prt_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Part-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1201	en=>"Modified"	1183	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Part-Part-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1202	en=>"ProperNoun"	2	783	\N	prn	ProperNoun is an Open Ontology.  ProperNoun encompasses the range of proper nouns encountered in Texts (e.g. persons, places, schools, titles, dynasties).	http://www.gandhari.org/kanishka/model/document/ProperNoun-ProperNoun.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1203	en=>"Annotations"	1202	800	{881}	prn_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/ProperNoun-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1204	en=>"Attributions"	1202	800	{895}	prn_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/ProperNoun-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1205	en=>"Description"	1202	785	\N	prn_description	Free text description.	http://www.gandhari.org/kanishka/model/document/ProperNoun-ProperNoun-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1206	en=>"EntityFrom-Evidences"	1202	811	{689,690,691,692,693,694,695,696,697,698,699,700,701}	prn_evidences	Identifies a record (e.g a Token) to which this ProperNoun is to be applied together with Term semantic to articulate the evidence for its application.	http://www.gandhari.org/kanishka/model/document/ProperNoun-EntityFrom-Evidences.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1207	en=>"ID"	1202	784	{1202}	prn_id	\N	http://www.gandhari.org/kanishka/model/document/ProperNoun-ProperNoun-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1208	en=>"Language-Labels"	1202	786	\N	prn_labels	The label for the ProperNoun.  ProperNoun-Language-Labels is prefaced with the ISO language identifier code (e.g. en: for English).	http://www.gandhari.org/kanishka/model/document/ProperNoun-Language-Labels.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1209	en=>"Owner"	1202	802	{1369}	prn_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/ProperNoun-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1210	en=>"Scratch"	1202	786	\N	prn_scratch	\N	http://www.gandhari.org/kanishka/model/document/ProperNoun-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1211	en=>"Type"	1202	806	{703,704,705,706,707}	prn_type_id	A Term record with a constrained vocabulary derived from ProperNounType.	http://www.gandhari.org/kanishka/model/document/ProperNoun-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1212	en=>"URL"	1202	785	\N	prn_url	A url to extended reference documentation for this ProperNoun.	http://www.gandhari.org/kanishka/model/document/ProperNoun-ProperNoun-URL.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1213	en=>"Visibilities"	1202	800	{1369}	prn_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/ProperNoun-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1214	en=>"Modified"	1202	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/ProperNoun-ProperNoun-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1215	en=>"Run"	2	783	\N	run	A Run identifies a particular Scribe with a Baseline or with nominated positions on a Baseline.	http://www.gandhari.org/kanishka/model/document/Run-Run.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1216	en=>"Annotations"	1215	800	{881}	run_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Run-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1217	en=>"Attributions"	1215	800	{895}	run_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Run-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1218	en=>"BaseLine"	1215	802	{1316}	run_baseline_id	Baseline this Run is identified with.	http://www.gandhari.org/kanishka/model/document/Run-BaseLine.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1219	en=>"ID"	1215	784	{1215}	run_id	\N	http://www.gandhari.org/kanishka/model/document/Run-Run-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1220	en=>"Owner"	1215	802	{1369}	run_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Run-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1221	en=>"Position"	1215	798	\N	run_image_pos	Xycoordinates (polygon) which defines a subset of the Segments on this Baseline.	http://www.gandhari.org/kanishka/model/document/Run-Run-Position.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1222	en=>"Scratch"	1215	786	\N	run_scratch	\N	http://www.gandhari.org/kanishka/model/document/Run-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1223	en=>"Scribe"	1215	806	\N	run_scribe_id	A ProperNoun record with a constrained vocabulary derived from Scribe.	http://www.gandhari.org/kanishka/model/document/Run-Scribe.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1224	en=>"LanguageScript"	1215	806	{721,722,723,1419,1428,1429,1430,1431,1432}	run_script_id	A Term record with a constrained vocabulary derived from LanguageScript.	http://www.gandhari.org/kanishka/model/document/Run-LanguageScript.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1225	en=>"Text"	1215	802	{1316}	run_text_id	Text this Run is identified with.	http://www.gandhari.org/kanishka/model/document/Run-Text.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1226	en=>"Visibilities"	1215	800	{1369}	run_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record.	http://www.gandhari.org/kanishka/model/document/Run-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1227	en=>"WritingTechnique"	1215	806	{879,880}	run_writing_id	A Term record with a constrained vocabulary derived from WritingTechnique. A run with a value in run-baseline but no value in run-position identifies that the scope of run-scribe attribution is to all segments on that baseline Each run record is attributed.	http://www.gandhari.org/kanishka/model/document/Run-WritingTechnique.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1228	en=>"Modified"	1215	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Run-Run-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1229	en=>"Segment"	2	783	\N	seg	A Segment is a subdivision of a Baseline which uniquely identifies a distinct Graphical Cluster of the Kharosthī Script.  A Segment may encompass an Akṣara , a Number Sign Component or a Punctuation Mark.	http://www.gandhari.org/kanishka/model/document/Segment-Segment.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1230	en=>"Annotations"	1229	800	{881}	seg_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Segment-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1231	en=>"Attributions"	1229	800	{895}	seg_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Segment-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1232	en=>"BaseLines"	1229	800	{923}	seg_baseline_ids	Baseline records linked to this Segment. When the BaseLine-Type is 'image' and where a Graphical Cluster is split across two Surfaces, then the Segment record must record two Baselines in the Segment-BaseLines field which correspond to two sets of Xycoordinates in the Segment-Position field.	http://www.gandhari.org/kanishka/model/document/Segment-BaseLines.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1233	en=>"Clarity"	1229	806	{725,726,727,728,729,730}	seg_clarity_id	A nummeric identifier for the level of clarity/legibility of the Graphical Cluster for this Segment.  A Term record with a constrained vocabulary derived from SegmentClarity.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-Clarity.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1234	en=>"ID"	1229	784	{1229}	seg_id	\N	http://www.gandhari.org/kanishka/model/document/Segment-Segment-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1235	en=>"Layer"	1229	531	\N	seg_layer	The level of execution of characters on a Segment.  A numeric identifier; the nth layer of the segment.  By default, where only one layer exists, the value for Segment-Layer is 0.  Additonal Segment records created for each subsequent Segment-Layer are related to each other through overlapping values in Segment-Position.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-Layer.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1236	en=>"MappedSegments"	1229	800	{1229}	seg_mapped_seg_ids	Segment records linked to this Segment. Where multiple Baselines for the same Surface exist each Segment may be mapped to the corresponding Segment on the other Baselines.  Each Segment record may have multiple entries in the Segment-MappedSegments field.	http://www.gandhari.org/kanishka/model/document/Segment-MappedSegments.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1237	en=>"Obscurations"	1229	807	{732,733}	seg_obscurations	Identifies Surface impingements or alterations which obscure the Graphical Cluster for this Segment.  A Term record with a constrained vocabulary derived from SegmentObscurations.	http://www.gandhari.org/kanishka/model/document/Segment-Obscurations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1238	en=>"Owner"	1229	802	{1369}	seg_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Segment-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1239	en=>"Position"	1229	798	\N	seg_image_pos	When the BaseLine-Type is 'image', a Segment is expressed using the Xycoordinates of a polygon enclosing that Graphical Cluster. Where a Graphical Cluster is split across two Surfaces, then the Segment record must record two sets of Xycoordinates in the Segment-Position field which correspond to two Baselines in the Segment-BaseLines field.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-Position.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1240	en=>"Rotation"	1229	797	\N	seg_rotation	A Segment-Rotation value may be recorded for a Segment which is not horizontal with respect to the Baseline.  The value recorded is the degrees of rotation to be applied to the Xycoordinates of a polygon enclosing that Graphical Cluster.  In particular, Baselines of Coins will require a Segment-Rotation value.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-Rotation.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1241	en=>"Scratch"	1229	786	\N	seg_scratch	\N	http://www.gandhari.org/kanishka/model/document/Segment-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1242	en=>"StringPosition"	1229	799	\N	seg_string_pos	When the BaseLine-Type is 'transcription' or where the BaseLine-Type is 'missing', a Segment is expressed as the ordinal positions of a SyllableCluster on the BaseLine-Transcription field. These are recorded as an array of character positions defining the Segment bounds on the on the BaseLine-Transcription string.  Whilts this array represents the ordinal character positions for the start and end of each syllable (e.g. 1-2, 3-5…) they are recorder with a spacing range (e.g. 10-20, 30-50…) to allow for alternative segmentation; the insertion or removal of Graphemes or SyllableClusters in the BaseLine-Transcription string.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-StringPosition.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1243	en=>"URL"	1229	785	\N	seg_url	URL that retrieves cropped segment image or thumb.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-URL.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1244	en=>"Visibilities"	1229	800	{1369}	seg_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Where attributions do not agree on the akṣara segmentation then additional segment records are created for each alternative. Each segment record has metadata which records the segment-alterativetype. Where a segment-layer exists then a segment-layer number might be appended to the segment description. Data from the existing gandhari.org will all by default be baseline-type is 'transcription'.	http://www.gandhari.org/kanishka/model/document/Segment-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1245	en=>"Centroid"	1229	793	\N	\N	When the BaseLine-Type is 'image', a Segment is expressed using the Xycoordinates of a polygon enclosing that Graphical Cluster.  The Segment-Centroid is a calculated value; the Xycoordinates of the center position of that Segment.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-Centroid.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1246	en=>"Modified"	1229	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Segment-Segment-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1247	en=>"Sequence"	2	783	\N	seq	Sequence identifies a set of Entity's with a Term in an ordered series.	http://www.gandhari.org/kanishka/model/document/Sequence-Sequence.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1248	en=>"Annotations"	1247	800	{881}	seq_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Sequence-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1249	en=>"Attributions"	1247	800	{895}	seq_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Sequence-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1250	en=>"Components"	1247	804	{972,1287,1345,1247}	seq_entity_ids	An ordered set of Entitys.  Constraints on what Entitys may be contained are defined in the Term-TermList field of the Term record identified in Sequence-Type.	http://www.gandhari.org/kanishka/model/document/Sequence-Components.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1251	en=>"DisplayLabel"	1247	785	\N	seq_label	The display name of that Sequence for presentation in the user interface.	http://www.gandhari.org/kanishka/model/document/Sequence-Sequence-DisplayLabel.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1252	en=>"ID"	1247	784	{1247}	seq_id	\N	http://www.gandhari.org/kanishka/model/document/Sequence-Sequence-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1302	en=>"Annotations"	1301	800	{881}	trm_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Term-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1253	en=>"SuperScript"	1247	738	\N	seq_superscript	The display identfier of that Sequence for presentation as superscript in the user interface.  Limited to 3 characters.	http://www.gandhari.org/kanishka/model/document/Sequence-Sequence-SuperScript.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1254	en=>"Owner"	1247	802	{1369}	seq_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Sequence-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1255	en=>"Scratch"	1247	786	\N	seq_scratch	\N	http://www.gandhari.org/kanishka/model/document/Sequence-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1256	en=>"Theme"	1247	802	\N	seq_theme_id	A Term record with a constrained vocabulary derived from SequenceTheme.	http://www.gandhari.org/kanishka/model/document/Sequence-Theme.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1257	en=>"Type"	1247	806	{736,738,740,756,760,1385}	seq_type_id	A Term record with a constrained vocabulary derived from SequenceType.	http://www.gandhari.org/kanishka/model/document/Sequence-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1258	en=>"Visibilities"	1247	800	{1369}	seq_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. A Sequence record may sequence a set of sequence-component records which includes multiple instances of the same entity with the same term values.   In this case the term value applies to each entity is the value from term:sequencetype	http://www.gandhari.org/kanishka/model/document/Sequence-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1259	en=>"Modified"	1247	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Sequence-Sequence-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1260	en=>"Span"	2	783	\N	spn	A Span is a sequence of Segments.	http://www.gandhari.org/kanishka/model/document/Span-Span.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1261	en=>"Annotations"	1260	800	{881}	spn_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Span-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1262	en=>"Attributions"	1260	800	{895}	spn_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Span-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1263	en=>"ID"	1260	784	{1260}	spn_id	\N	http://www.gandhari.org/kanishka/model/document/Span-Span-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1264	en=>"Owner"	1260	802	{1369}	spn_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Span-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1265	en=>"Scratch"	1260	786	\N	spn_scratch	\N	http://www.gandhari.org/kanishka/model/document/Span-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1266	en=>"Segments"	1260	801	{1229}	spn_segment_ids	Ordered sequence of linked Segments.	http://www.gandhari.org/kanishka/model/document/Span-Segments.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1267	en=>"Type"	1260	806	{765,766,767,768,769}	spn_type_id	A Term record with a constrained vocabulary derived from SpanType.	http://www.gandhari.org/kanishka/model/document/Span-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1268	en=>"Visibilities"	1260	800	{1369}	spn_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. There is an implicit numeric ordering with regard to the Span records on a Surface. Where attributions agree on the segments there is only one span record. Where attributions do not agree on the segments, a span record is created for each alternative sequence of segments on that baseline. Each span record is attributed.	http://www.gandhari.org/kanishka/model/document/Span-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1269	en=>"Modified"	1260	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Span-Span-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1270	en=>"Surface"	2	783	\N	srf	A Surface is an entire contiguous Face of a Fragment.  A Surface is a physically whole and contiguous Face albeit notions of the efficacy of executing a Text on that Face are taken into account in defining what might constitute a Surface.	http://www.gandhari.org/kanishka/model/document/Surface-Surface.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1271	en=>"Annotations"	1270	800	{881}	srf_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Surface-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1272	en=>"Attributions"	1270	800	{895}	\N	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Surface-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1273	en=>"Description"	1270	785	\N	srf_description	Free text description.	http://www.gandhari.org/kanishka/model/document/Surface-Surface-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1274	en=>"Fragment"	1270	802	{1034}	srf_fragment_id	The Fragment this Surface is a Face of.	http://www.gandhari.org/kanishka/model/document/Surface-Fragment.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1275	en=>"FragmentSurfaceDescription"	1270	791	\N	\N	A calculated field which concatenates values from Fragment-PartFragmentDescription and Surface-Description. A structured description which encompasses both the Surface and the Fragment of which it is a Face.	http://www.gandhari.org/kanishka/model/document/Surface-Surface-FragmentSurfaceDescription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1276	en=>"ID"	1270	784	{1270}	srf_id	\N	http://www.gandhari.org/kanishka/model/document/Surface-Surface-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1277	en=>"Images"	1270	800	{1067}	srf_image_ids	Images associated with the Surface.  An unordered set of Image records.	http://www.gandhari.org/kanishka/model/document/Surface-Images.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1278	en=>"Layer"	1270	531	\N	srf_layer_number	A number which identifies a Surface with the level of execution of characters on that Face.  Where the Surface-Layer is other than the default of 1, then that Surface-Layer number might be appended to the Surface description.	http://www.gandhari.org/kanishka/model/document/Surface-Surface-Layer.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1386	en=>"DictionaryCitation"	1385	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1279	en=>"Number"	1270	531	\N	srf_number	A number which identifies that the set of a Fragment .  By default, Surfaces might be numbered from 1-n; the largest to the smallest Surface of a Fragment, but may be arbitrary.  Where we have a 'missing' fragment (e.g of a birch bark manuscript) then that fragment record would have a related surface record.  A Surface-Number of 0 is reserved for a 'missing' Surface record; a Surface record related to a Fragment with a fragment-number of 0; a 'missing' Fragment (e.g of a birch bark manuscript).	http://www.gandhari.org/kanishka/model/document/Surface-Surface-Number.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1280	en=>"Owner"	1270	802	{1369}	\N	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Surface-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1281	en=>"Reconstructedsurface"	1270	802	\N	srf_reconst_surface_id	The ReconstructedSurface this Surface is a component of.	http://www.gandhari.org/kanishka/model/document/Surface-Reconstructedsurface.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1282	en=>"Scratch"	1270	786	\N	srf_scratch	\N	http://www.gandhari.org/kanishka/model/document/Surface-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1284	en=>"Texts"	1270	800	{1316}	srf_text_ids	Texts associated with the Surface.  An unordered set of Text records.	http://www.gandhari.org/kanishka/model/document/Surface-Texts.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1285	en=>"Visibilities"	1270	800	{1369}	srf_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. A surface (assuming it is not blank) will have a linkage to an inscription, manuscript, coin or niya entity. A surface description encompasses both the item, that part of that item, that fragment of that part and that surface of that fragment. Each surface may be attributed. A surface may be associated with an image.	http://www.gandhari.org/kanishka/model/document/Surface-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1286	en=>"Modified"	1270	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Surface-Surface-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1287	en=>"SyllableCluster"	2	783	\N	scl	A SyllableCluster identifies a Segment with a sequence of Graphemes.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-SyllableCluster.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1288	en=>"Annotations"	1287	800	{881}	scl_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1289	en=>"Attributions"	1287	800	{895}	scl_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1290	en=>"DisplayValue"	1287	791	\N	\N	Concatenation of values from the Grapheme-Grapheme field of the Graphemes sequenced in the SyllableCluster-Graphemes field  Where a SyllableCluster has a sequenced Grapheme which has a Grapheme-TextCriticalMark value of 'Emmended' then automation substitutes the value in the Grapheme-EmmendationFrom field for the value in the Grapheme-Grapheme field.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-SyllableCluster-DisplayValue.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1291	en=>"Graphemes"	1287	801	{1052}	scl_grapheme_ids	Ordered sequence of linked Graphemes.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Graphemes.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1292	en=>"Owner"	1287	802	{1369}	scl_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1293	en=>"Scratch"	1287	786	\N	scl_scratch	\N	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1294	en=>"Segment"	1287	802	{1229}	scl_segment_id	Segment record linked to this SyllableCluster.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Segment.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1295	en=>"SyllableCluster-ID"	1287	784	{1287}	scl_id	\N	http://www.gandhari.org/kanishka/model/document/SyllableCluster-SyllableCluster-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1296	en=>"SyllableCluster-SC"	1287	791	\N	scl_sort_code	Numeric code used to sort SyllableClusters.  This values is concatenated from the SC Values for Graphemes sequenced in the SyllableCluster-Graphemes field.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-SyllableCluster-SC.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1297	en=>"TextCriticalMark"	1287	795	\N	scl_text_critical_mark	The SyllableCluster-TextCriticalMark field has values for Akṣara level Text Critical Marks; those associated with a SyllableCluster.  Values entered into this field are populated into the Grapheme-TextCriticalMark field of the Graphemes sequenced in the SyllableCluster-Graphemes field.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-TextCriticalMark.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1298	en=>"Transcription"	1287	792	\N	\N	Concatenation of values from SyllableCluster-Graphemes field with automated application of associated Text Critical Marks from Grapheme-TextCriticalMark field to represent the edited transcription; the Hybrid rendition of this Syllable complete with Text Critical Marks.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-SyllableCluster-Transcription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1299	en=>"Visibilities"	1287	800	{1369}	scl_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. Scratch Each syllablecluster record has metadata which records the syllablecluster-alterativetype.  Need to challenge the spec for this field.  It can be calculated from comparison to other syllable clusters linked to the same segment.  Secondly need to question whether it does indeed need to be stored at syllable level.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1300	en=>"Modified"	1287	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/SyllableCluster-SyllableCluster-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1301	en=>"Term"	2	783	\N	trm	Term is a comprehensive structured System Ontology.  Term encompasses database Entitys and Fields, System lists and Content Lists, and Defined Terms.  Term is the repository for both domain and system documentation.	http://www.gandhari.org/kanishka/model/document/Term-Term.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1303	en=>"Attributions"	1301	800	{895}	trm_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Term-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1304	en=>"Code"	1301	785	\N	trm_code	Term records with a Term-ParentTerm value under the ‘SystemEntity’ hierarchy have a value in Term-Code which is the database field identifier.  Term records with a Term-ParentTerm value under other than the ‘SystemEntity’ hierarchy have a value in Term-Code which is an unabbreviated form of the value in Term-Language-Labels.	http://www.gandhari.org/kanishka/model/document/Term-Term-Code.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1305	en=>"Description"	1301	785	\N	trm_description	Free text description.	http://www.gandhari.org/kanishka/model/document/Term-Term-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1306	en=>"ID"	1301	784	{1301}	trm_id	\N	http://www.gandhari.org/kanishka/model/document/Term-Term-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1307	en=>"Language-Labels"	1301	796	\N	trm_labels	The label for the Term.  Term-Language-Labels is prefaced with the ISO language identifier code (e.g. en: for English).	http://www.gandhari.org/kanishka/model/document/Term-Language-Labels.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1308	en=>"Owner"	1301	802	{1369}	trm_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Term-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1309	en=>"ParentTerm"	1301	802	{1301}	trm_parent_id	A Term record value which aggregates a set of Terms in an hierarchy.  All Term records have a value in Term-ParentTerm up to but not including the highest level Term value in the Term-ParentTerm field, ‘SystemOntology’	http://www.gandhari.org/kanishka/model/document/Term-ParentTerm.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1310	en=>"TermList"	1301	800	{1301}	trm_list_ids	Term records with a Term-Type of ‘UIList’ encapsulate a subset of Terms with the same value in Term-ParentTerm.  These subsets are presented as separate list options in the user interface.  There is an implied semantic in the ordering of the subset of Terms.	http://www.gandhari.org/kanishka/model/document/Term-TermList.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1311	en=>"Type"	1301	806	{774,775,776,777,778,779,780,781,782,783,784,785,786,787,788,789,790,791,792,793,794,795,796,797,798,799,800,801,802,803,804,805,806,807,808,809,810,811,812,813,814,1433}	trm_type_id	A Term record with a constrained vocabulary derived from TermType.	http://www.gandhari.org/kanishka/model/document/Term-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1312	en=>"Scratch"	1301	786	\N	trm_scratch	\N	http://www.gandhari.org/kanishka/model/document/Term-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1313	en=>"URL"	1301	785	\N	trm_url	A url to extended reference documentation for this Term.	http://www.gandhari.org/kanishka/model/document/Term-Term-URL.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1314	en=>"Visibilities"	1301	800	{1369}	trm_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. term-label allows capacity to specify multiple language variants.  Property value pair identifies the language and label. term-list allows capacity to sequence a set of term records.  Used for designation of allowed values in open list fields. parent-list allows capacity to hierarchical structure term records. term-description allows specification of a display value for UI e.g full label where the actual label is an abbreviation documentation identifies record which encapsulates extended documentation for a term record.  It is anticipated that this will be used for detailed descriptions of entities and fields. Conceptual split between Term and Metadata is Term is an admin controlled system vocabulary - Basically Structural Metadata – Data about Data Attributes Anticipate this one needs to be admin controlled and is a structured system ontology Needs to be orthogonal Hierarchical Parent structure Seems we can't have  records with more than one type or parent Seems intuitively that we can't have multiple instances of a term record (any overlaps will be disambiguated by Parent) Types System ontology Entity Field ForeignKey DefinedTerms TermList Enum lists Open Lists Closed Lists (for dev purposes only) System Ontology This entity can be used both as a resource for a documented ontology of the domain and for system documentation. mapping between implementation name and descriptive name for UI. mapping between implementation name and descriptive name for system documentation mapping between implementation name and descriptive name for system ontology documentation This might include records for: Each entity Each field name Each foreign key (need think through what happens with all the duplication with foreign keys and how different types of fields are going to be handled.) Each defined term. Will be a hierarchy of Parentage for the following Entity Field names encapsulated in an Entity  Field name as a pointer to an entity Open Lists referenced to a field Following are outside Entity Parentage hierarchy TermLists (generic lists that are referred to from multiple fields) Defined Terms (terms used in system ontology documentation) Enumerated Lists All Open lists All Closed Lists (Anticipate replication of Closed lists in this entity for development purpposes - At least in the build stage it would make sense to have them all in one spot so that we can be certain there are no overlaps	http://www.gandhari.org/kanishka/model/document/Term-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1315	en=>"Modified"	1301	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Term-Term-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1316	en=>"Text"	2	783	\N	txt	A Text record identifies a Text with a CKN and allocates a unique title to that Text.	http://www.gandhari.org/kanishka/model/document/Text-Text.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1317	en=>"Annotations"	1316	800	{881}	txt_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Text-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1318	en=>"Attributions"	1316	800	{895}	txt_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Text-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1319	en=>"CKN"	1316	785	\N	txt_ckn	The CKN allocated to this Text.  Text-CKN are allocated by a special UserGroup with a Full name of User or group of Users. UserGroup-Name of 'Catalog'.	http://www.gandhari.org/kanishka/model/document/Text-Text-CKN.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1320	en=>"EditionReferences"	1316	800	{895}	txt_edition_ref_ids	Attribution records identified with this Text.  The set of Editions, References, Catalogs and Archaeological Reports relevant to this Text.	http://www.gandhari.org/kanishka/model/document/Text-EditionReferences.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1321	en=>"TextRef"	1316	785	\N	txt_ref	An abbreviated identifier for the Text for display in the Dictionary UI when diplaying Tokens or Compounds as attested froms of a Lemma.	http://www.gandhari.org/kanishka/model/document/Text-Text-TextRef.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1322	en=>"FindspotRegion"	1316	791	\N	\N	tbd	http://www.gandhari.org/kanishka/model/document/Text-Text-FindspotRegion.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1323	en=>"ID"	1316	784	{1316}	txt_id	\N	http://www.gandhari.org/kanishka/model/document/Text-Text-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1387	en=>"Paleography"	8	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1395	en=>"7"	1388	778	\N	bt7	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1324	en=>"Images"	1316	800	{1067}	txt_image_ids	Images identified with this this Text.	http://www.gandhari.org/kanishka/model/document/Text-Images.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1325	en=>"ItemPartDescription"	1316	791	\N	\N	tbd	http://www.gandhari.org/kanishka/model/document/Text-Text-ItemPartDescription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1326	en=>"Owner"	1316	802	{1369}	txt_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Text-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1327	en=>"ReplacedWith"	1316	800	{1316}	txt_replacement_ids	Text record(s) this record is replaced with.  This allows for forwarding from an historical Text-CKN to new Text-CKN(s) in cases where existing Text-CKN have been split or merged.	http://www.gandhari.org/kanishka/model/document/Text-ReplacedWith.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1328	en=>"Scratch"	1316	786	\N	txt_scratch	\N	http://www.gandhari.org/kanishka/model/document/Text-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1329	en=>"Title"	1316	785	\N	txt_title	tbd	http://www.gandhari.org/kanishka/model/document/Text-Text-Title.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1330	en=>"Types"	1316	807	{816,817}	txt_type_ids	A Term record with a constrained vocabulary derived from TextType.	http://www.gandhari.org/kanishka/model/document/Text-Types.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1331	en=>"Visibilities"	1316	800	{1369}	txt_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. The term:editionreference-attribution field identifies a categorizing term with a pointer to the attribution entity and aggregates all editions refernces catalogs and archaeological reports. The image field is a pointer to the image entity and aggregates all images relevant to a text. The term:texttype field allows for implementation of a controlled vocabulary of genre and sub-genre terms.  Anticipate there needs to be a constraint on only selecting terms from a single parent i.e all terms selected must be either from inscription or manuscript parent term.	http://www.gandhari.org/kanishka/model/document/Text-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1332	en=>"Modified"	1316	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Text-Text-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1333	en=>"JSONCache"	1316	802	{1114}	txt_jsoncache_id	JSONCache identified with this this Text.	http://www.gandhari.org/kanishka/model/document/Text-JSONCache.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1334	en=>"TextMetadata"	2	783	\N	tmd	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-TextMetadata.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1335	en=>"Annotations"	1334	800	{881}	tmd_annotation_ids	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1336	en=>"Attributions"	1334	800	{895}	tmd_attribution_ids	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1337	en=>"ID"	1334	784	{1316}	tmd_id	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-TextMetadata-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1338	en=>"Owner"	1334	802	{1369}	tmd_owner_id	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1339	en=>"References"	1334	800	{895}	tmd_reference_ids	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-References.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1340	en=>"Scratch"	1334	786	\N	tmd_scratch	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1341	en=>"Text"	1334	802	{1316}	tmd_text_id	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Text.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1342	en=>"Types"	1334	807	\N	tmd_type_ids	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Types.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1343	en=>"Visibilities"	1334	800	{1369}	tmd_visibility_ids	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1344	en=>"Modified"	1334	814	\N	modified	\N	http://www.gandhari.org/kanishka/model/document/TextMetadata-TextMetadata-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1345	en=>"Token"	2	783	\N	tok	A Token record is a sequence of Graphemes which are combined to represent an attested Word, Compound Component, Number Sign Component or Punctuation Mark.	http://www.gandhari.org/kanishka/model/document/Token-Token.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1346	en=>"Annotations"	1345	800	{881}	tok_annotation_ids	Annotation records linked to this record.	http://www.gandhari.org/kanishka/model/document/Token-Annotations.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1347	en=>"Attributions"	1345	800	{895}	tok_attribution_ids	Attribution records linked to this record.  It defines either a System Event or a Bibliographic Reference that this record is attributed to.	http://www.gandhari.org/kanishka/model/document/Token-Attributions.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1350	en=>"DisplayValue"	1345	791	\N	tok_value	The Token-DisplayValue field is concatenated from Grapheme records without Text Critical Marks. Where a Punctuation Mark includes a Number Sign Component inside a Punctuation Mark, the Token-DisplayValue the Punctuation Mark and the Number Sign Component with a separating a hyphen.	http://www.gandhari.org/kanishka/model/document/Token-Token-DisplayValue.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1352	en=>"Graphemes"	1345	801	{1052}	tok_grapheme_ids	Ordered sequence of linked Graphemes.	http://www.gandhari.org/kanishka/model/document/Token-Graphemes.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1353	en=>"ID"	1345	784	{1345}	tok_id	\N	http://www.gandhari.org/kanishka/model/document/Token-Token-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1354	en=>"NominalAffix"	1345	785	\N	tok_nom_affix	The Nominal Affix to the ostensible Nominal Stem.	http://www.gandhari.org/kanishka/model/document/Token-NominalAffix.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1355	en=>"Owner"	1345	802	{1369}	tok_owner_id	UserGroup record linked to this record.  It defines which UserGroup owns this record.	http://www.gandhari.org/kanishka/model/document/Token-Owner.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1388	en=>"BaseType"	1387	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1356	en=>"SC"	1345	791	\N	tok_sort_code	The Token-SC value is the primary sort order (the macro level sorting where all diacritic variants have the same sort value) for Tokens calculated from the sequence of Grapheme-Grapheme values.  The only exception to this is Anusvāra and Visarga.   It is anticipated the Anusvāra and Visarga would have a default Grapheme-Grapheme-Sc value.  It is anticipated that Token-SC value would calculate a contextual value for Anusvāra and Visarga.  It is anticipated that contextual calculation of Anusvāra and Visarga would be enabled for Sanskrit but not for Gāndhārī.	http://www.gandhari.org/kanishka/model/document/Token-Token-SC.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1357	en=>"SC2"	1345	791	\N	tok_sort_code2	The Token-SC value is the secondary sort order (the micro level sorting where diacritic variants have a unique sort value) for Tokens calculated from the sequence of Grapheme-Grapheme values.  The only exception to this is Anusvāra and Visarga.   It is anticipated the Anusvāra and Visarga would have a default Grapheme-Grapheme-Sc value.  It is anticipated that Token-SC value would calculate a contextual value for Anusvāra and Visarga.  It is anticipated that contextual calculation of Anusvāra and Visarga would be enabled for Sanskrit but not for Gāndhārī.	http://www.gandhari.org/kanishka/model/document/Token-Token-SC2.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1358	en=>"Scratch"	1345	786	\N	tok_scratch	\N	http://www.gandhari.org/kanishka/model/document/Token-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1359	en=>"Transcription"	1345	791	\N	tok_transcription	The Token-Transcription field is concatenated from Grapheme records with Text Critical Marks.  Values from the Grapheme-Grapheme and Grapheme-TextCriticalMark fields are calculated to represent the edited transcription; the Token-DisplayValue augmented with Text Critical Marks.  Automation resolves the rendering of values from Grapheme-TextCriticalMark across strings of values from Grapheme-Grapheme.  Where the value in the Grapheme-Grapheme is null, automation will derive a value for representation of that Grapheme in the Token-Transcription from the value in Grapheme-TextCriticalMark.	http://www.gandhari.org/kanishka/model/document/Token-Token-Transcription.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1366	en=>"Visibilities"	1345	800	{1369}	tok_visibility_ids	UserGroup records linked to this record.  It defines which UserGroup can view this record. token-alternativetype Indicates rationale for token alternatives. token-syllableclusteralternativetype Concatenates syllablecluster-alternativetype values derived from sequence of grapheme records, The following fields articulate the grammatical deconstruction of the token record. token-partofspeech, token-declension, token-case, token-nominalgender, token-grammaticalnumber, token-verbalperson, token-verbalvoice, token-verbaltense, token-verbalmood, token-verbalconjugation,  alternativetype Each token record has a token-syllableclusteralternativetype field which aggregates values from the syllablecluster-alternativetype field of the syllablecluster records associated with the component grapheme records of this token Need to question the requirement for this field.  If we do continue to store syllablecluster-alternativetype field then we can calculate this field.  Alternatively we can calculate this field from the component graphemes, so no need to aggregate values from syllablecluster-alternativetype. Each token record has metadata which records the token-alterativetype. Need to question the requirement and spec on this field.  If the token sequence's graphemes linked to the same segments then we can calculate whether the difference is due to a difference in grammatical deconstruction or a differences in lemma assignation.	http://www.gandhari.org/kanishka/model/document/Token-Visibilities.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1368	en=>"Modified"	1345	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/Token-Token-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1369	en=>"UserGroup"	2	783	\N	ugr	UserGroup entity identifies individual Users and groups of Users for the purposes of controlling access to system entities.	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1370	en=>"AdminIDs"	1369	800	{1369}	ugr_admin_ids	UserGroup record linked to this record.  Defines which UserGroup has an Administrator role.	http://www.gandhari.org/kanishka/model/document/UserGroup-AdminIDs.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1371	en=>"Description"	1369	785	\N	ugr_description	Free text description of User or group of Users	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-Description.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1372	en=>"Name"	1369	785	\N	ugr_name	Abbreviated name of User or group of Users.	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-Name.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1373	en=>"ID"	1369	784	{1369}	ugr_id	\N	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-ID.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1374	en=>"MemberIDs"	1369	800	{1369}	ugr_member_ids	UserGroup record linked to this record.  Defines which UserGroup are Member.	http://www.gandhari.org/kanishka/model/document/UserGroup-MemberIDs.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1375	en=>"FamilyName"	1369	785	\N	ugr_family_name	Family name of User or group of Users.	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-FamilyName.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1376	en=>"GivenName"	1369	785	\N	ugr_given_name	Given name(s) of User or group of Users.	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-GivenName.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1377	en=>"Password"	1369	785	\N	ugr_password	Password.	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-Password.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1378	en=>"Scratch"	1369	786	\N	ugr_scratch	\N	http://www.gandhari.org/kanishka/model/document/UserGroup-Scratch.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1379	en=>"Type"	1369	806	{511,512,513}	ugr_type_id	A Term record with a constrained vocabulary derived from UserGroupType.	http://www.gandhari.org/kanishka/model/document/UserGroup-Type.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1380	en=>"Modified"	1369	814	\N	modified	The last modified date and time stamp for this record.	http://www.gandhari.org/kanishka/model/document/UserGroup-UserGroup-Modified.htm	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1381	en=>"mn."	482	778	\N	Male/Neuter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1382	en=>"mfn."	482	778	\N	Mael/Female/Neuter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1383	en=>"mfn."	488	778	\N	Male/Female/Neuter	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1384	en=>"nf."	488	778	\N	Neuter/Female	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1385	en=>"CitationContainer"	735	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1396	en=>"8"	1388	778	\N	bt8	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1397	en=>"9"	1388	778	\N	bt9	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1398	en=>"10"	1388	778	\N	bt10	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1399	en=>"FootMarkType"	1387	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1400	en=>"0"	1399	778	\N	ft0	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1401	en=>"1"	1399	778	\N	ft1	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1402	en=>"2"	1399	778	\N	ft2	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1403	en=>"3"	1399	778	\N	ft3	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1404	en=>"4"	1399	778	\N	ft4	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1405	en=>"5"	1399	778	\N	ft5	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1406	en=>"VowelType"	1387	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1407	en=>"1"	1406	778	\N	vt1	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1408	en=>"2"	1406	778	\N	vt2	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1409	en=>"3"	1406	778	\N	vt3	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1410	en=>"4"	1406	778	\N	vt4	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1411	en=>"5"	1406	778	\N	vt5	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1412	en=>"6"	1406	778	\N	vt6	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1413	en=>"7"	1406	778	\N	vt7	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1414	en=>"8"	1406	778	\N	vt8	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1415	en=>"9"	1406	778	\N	vt9	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1416	en=>"10"	1406	778	\N	vt10	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1417	en=>"Default"	1387	778	\N	ex	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1418	en=>"License"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1419	en=>"qma-Brah-x-mia"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1420	en=>"English"	761	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1421	en=>"Chaya"	761	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1422	en=>"Creator"	7	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1423	en=>"Description"	264	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1424	en=>"PrimaryEdition"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1425	en=>"SecondaryEdition"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1426	en=>"VisualDocumentation"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1427	en=>"Lemma"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1428	en=>"pra-Brah"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1429	en=>"pli-Brah"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1430	en=>"pyx"	720	778	\N	\N	Pyu language in Pyu script, with syllable-final consonant.	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1431	en=>"omx"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1432	en=>"obx"	720	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1433	en=>"FK-PairMultiple_Semantic(Term)-Text"	773	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1434	en=>"CompoundConstituent"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1435	en=>"CompoundConstituentHead"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1436	en=>"ArchaeologicalReport"	339	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1437	en=>"Section"	741	778	{744,745,742,1442,972,1345}	Section	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1438	en=>"FreeText"	736	778	\N	Line	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1439	en=>"Compare"	278	778	\N	cf	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1440	en=>"See"	278	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1441	en=>"Glossary"	264	778	\N	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1442	en=>"List"	741	778	{1443}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1443	en=>"Item"	1442	778	{742,744,745,972,1345}	\N	\N	\N	\N	2017-09-08 14:05:33.264841	1	\N	{2}	\N
1444	en=>"LemmaLinkage"  278	778	\N	\N	\N	\N	\N	\N	1	{2}	\N
1445	en=>"Alternate" 1444	778	\N	\N	\N	\N	\N	\N	1	{2}	\N
1446	en=>"GVerbalVoice"  873	776	'{877}'	\N	'Verbal Voice'	\N	\N	\N	1	{2}	\N
1447	en=>"LanguageForm"  1	780	\N	\N	\N	\N	\N	\N	1	{2}	\N
1448	en=>"Prose" 1447	778	\N	\N	\N	\N	\N	\N	1	{2}	\N
1449	en=>"Poetry"    1447	778	\N	\N	\N	\N	\N	\N	1	{2}	\N
1450	en=>"SyntacticFunction" 278	781	\N	\N	Syntactic Function	\N	\N	\N	1	{2}	\N
1451	en=>"PRED"  1450	778	\N	\N	predicate	\N	\N	\N	1	{2}	\N
1452	en=>"SBJ"   1450	778	\N	\N	subject	\N	\N	\N	1	{2}	\N
1453	en=>"OBJ"   1450	778	\N	\N	object	\N	\N	\N	1	{2}	\N
1454	en=>"ATR"   1450	778	\N	\N	attributive	\N	\N	\N	1	{2}	\N
1455	en=>"ADV"   1450	778	\N	\N	adverbial	\N	\N	\N	1	{2}	\N
1456	en=>"ATV/AtvV"  1450	778	\N	\N	complement	\N	\N	\N	1	{2}	\N
1457	en=>"PNOM"  1450	778	\N	\N	predicate nominal	\N	\N	\N	1	{2}	\N
1458	en=>"OCOMP" 1450	778	\N	\N	object complement	\N	\N	\N	1	{2}	\N
1459	en=>"COORD" 1450	778	\N	\N	coordinator	\N	\N	\N	1	{2}	\N
1460	en=>"APOS"  1450	778	\N	\N	apposing element	\N	\N	\N	1	{2}	\N
1461	en=>"AuxP"  1450	778	\N	\N	preposition	\N	\N	\N	1	{2}	\N
1462	en=>"AuxC"  1450	778	\N	\N	conjunction	\N	\N	\N	1	{2}	\N
1463	en=>"AuxR"  1450	778	\N	\N	reflexive passive	\N	\N	\N	1	{2}	\N
1464	en=>"AuxV"  1450	778	\N	\N	auxiliary verb	\N	\N	\N	1	{2}	\N
\.


--
-- Name: term_trm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('term_trm_id_seq', 1465, false);


--
-- Data for Name: text; Type: TABLE DATA; Schema: public; Owner: -
--

COPY text (txt_id, txt_ckn, txt_title, txt_ref, txt_type_ids, txt_replacement_ids, txt_edition_ref_ids, txt_image_ids, txt_attribution_ids, modified, txt_jsoncache_id, txt_owner_id, txt_annotation_ids, txt_visibility_ids, txt_scratch) FROM stdin;
\.


--
-- Name: text_txt_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('text_txt_id_seq', 1, false);


--
-- Data for Name: textmetadata; Type: TABLE DATA; Schema: public; Owner: -
--

COPY textmetadata (tmd_id, tmd_text_id, tmd_type_ids, tmd_reference_ids, tmd_attribution_ids, modified, tmd_owner_id, tmd_annotation_ids, tmd_visibility_ids, tmd_scratch) FROM stdin;
\.


--
-- Name: textmetadata_tmd_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('textmetadata_tmd_id_seq', 1, false);


--
-- Data for Name: token; Type: TABLE DATA; Schema: public; Owner: -
--

COPY token (tok_id, tok_value, tok_transcription, tok_grapheme_ids, tok_nom_affix, tok_sort_code, tok_sort_code2, tok_attribution_ids, modified, tok_owner_id, tok_annotation_ids, tok_visibility_ids, tok_scratch) FROM stdin;
\.


--
-- Name: token_tok_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('token_tok_id_seq', 1, false);


--
-- Data for Name: usergroup; Type: TABLE DATA; Schema: public; Owner: -
--

COPY usergroup (ugr_id, ugr_name, ugr_type_id, ugr_given_name, ugr_family_name, ugr_description, ugr_password, modified, ugr_member_ids, ugr_admin_ids, ugr_scratch) FROM stdin;
1	SysAdmin	513	\N	System Administration	Special system level group with full access for administrating the complete system.	\N	2018-03-25 05:14:43.015	{11,12,13}	{11}	\N
2	Public	513	\N	General Public	Special system level group used to give free open access.	\N	2017-09-08 14:05:33.415031	\N	\N	\N
3	Users	513	\N	Logged in Users	Special system level group used to give access to all user accounts.	\N	2017-09-08 14:05:33.415031	\N	\N	\N
4	Catalog	513	\N	Dictionary Editors	Special editorial group responsible for the default catalog.	\N	2017-09-08 14:05:33.415031	{11,12}	{11}	\N
5	Delete	513	\N	Marked for Delete	Record marked for delete by admin.	\N	2017-09-08 14:05:33.415031	{1}	{1}	\N
6	Reserved2	513	\N	\N	Resrved for future special system level group.	\N	2017-09-08 14:05:33.415031	{1}	{1}	\N
7	Reserved3	513	\N	\N	Resrved for future special system level group.	\N	2017-09-08 14:05:33.415031	{1}	{1}	\N
8	Reserved4	513	\N	\N	Resrved for future special system level group.	\N	2017-09-08 14:05:33.415031	{1}	{1}	\N
9	Reserved5	513	\N	\N	Resrved for future special system level group.	\N	2017-09-08 14:05:33.415031	{1}	{1}	\N
10	Reserved6	513	\N	\N	Resrved for future special system level group.	\N	2017-09-08 14:05:33.415031	{1}	{1}	\N
11	User1	511	User	One	\N	5f4dcc3b5aa765d61d8327deb882cf99	2018-03-25 05:14:20.843	{11}	{11}	\N
12	User2	511	User	Two	\N	5f4dcc3b5aa765d61d8327deb882cf99	2018-03-25 05:14:24.171	{12}	{12}	\N
13	User2	511	User	Three	\N	5f4dcc3b5aa765d61d8327deb882cf99	2018-03-25 05:14:27.905	{13}	{13}	\N
\.


--
-- Name: usergroup_ugr_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('usergroup_ugr_id_seq', 14, true);


--
-- Name: annotation_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY annotation
    ADD CONSTRAINT annotation_pkey PRIMARY KEY (ano_id);


--
-- Name: attribution_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY attribution
    ADD CONSTRAINT attribution_pkey PRIMARY KEY (atb_id);


--
-- Name: attributiongroup_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY attributiongroup
    ADD CONSTRAINT attributiongroup_pkey PRIMARY KEY (atg_id);


--
-- Name: baseline_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY baseline
    ADD CONSTRAINT baseline_pkey PRIMARY KEY (bln_id);


--
-- Name: bibliography_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY bibliography
    ADD CONSTRAINT bibliography_pkey PRIMARY KEY (bib_id);


--
-- Name: catalog_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT catalog_pkey PRIMARY KEY (cat_id);


--
-- Name: collection_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY collection
    ADD CONSTRAINT collection_pkey PRIMARY KEY (col_id);


--
-- Name: compound_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY compound
    ADD CONSTRAINT compound_pkey PRIMARY KEY (cmp_id);


--
-- Name: date_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY date
    ADD CONSTRAINT date_pkey PRIMARY KEY (dat_id);


--
-- Name: edition_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY edition
    ADD CONSTRAINT edition_pkey PRIMARY KEY (edn_id);


--
-- Name: era_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY era
    ADD CONSTRAINT era_pkey PRIMARY KEY (era_id);


--
-- Name: fragment_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY fragment
    ADD CONSTRAINT fragment_pkey PRIMARY KEY (frg_id);


--
-- Name: grapheme_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY grapheme
    ADD CONSTRAINT grapheme_pkey PRIMARY KEY (gra_id);


--
-- Name: image_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY image
    ADD CONSTRAINT image_pkey PRIMARY KEY (img_id);


--
-- Name: inflection_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY inflection
    ADD CONSTRAINT inflection_pkey PRIMARY KEY (inf_id);


--
-- Name: item_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY item
    ADD CONSTRAINT item_pkey PRIMARY KEY (itm_id);


--
-- Name: jsoncache_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY jsoncache
    ADD CONSTRAINT jsoncache_pkey PRIMARY KEY (jsc_id);


--
-- Name: lemma_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT lemma_pkey PRIMARY KEY (lem_id);


--
-- Name: line_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY line
    ADD CONSTRAINT line_pkey PRIMARY KEY (lin_id);


--
-- Name: materialcontext_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY materialcontext
    ADD CONSTRAINT materialcontext_pkey PRIMARY KEY (mcx_id);


--
-- Name: part_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY part
    ADD CONSTRAINT part_pkey PRIMARY KEY (prt_id);


--
-- Name: propernoun_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY propernoun
    ADD CONSTRAINT propernoun_pkey PRIMARY KEY (prn_id);


--
-- Name: run_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY run
    ADD CONSTRAINT run_pkey PRIMARY KEY (run_id);


--
-- Name: segment_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY segment
    ADD CONSTRAINT segment_pkey PRIMARY KEY (seg_id);


--
-- Name: sequence_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY sequence
    ADD CONSTRAINT sequence_pkey PRIMARY KEY (seq_id);


--
-- Name: span_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY span
    ADD CONSTRAINT span_pkey PRIMARY KEY (spn_id);


--
-- Name: surface_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY surface
    ADD CONSTRAINT surface_pkey PRIMARY KEY (srf_id);


--
-- Name: syllablecluster_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY syllablecluster
    ADD CONSTRAINT syllablecluster_pkey PRIMARY KEY (scl_id);


--
-- Name: term_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY term
    ADD CONSTRAINT term_pkey PRIMARY KEY (trm_id);


--
-- Name: text_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY text
    ADD CONSTRAINT text_pkey PRIMARY KEY (txt_id);


--
-- Name: textmetadata_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY textmetadata
    ADD CONSTRAINT textmetadata_pkey PRIMARY KEY (tmd_id);


--
-- Name: token_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY token
    ADD CONSTRAINT token_pkey PRIMARY KEY (tok_id);


--
-- Name: usergroup_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY usergroup
    ADD CONSTRAINT usergroup_pkey PRIMARY KEY (ugr_id);


--
-- Name: usergroup_ugr_name_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace:
--

ALTER TABLE ONLY usergroup
    ADD CONSTRAINT usergroup_ugr_name_key UNIQUE (ugr_name);


--
-- Name: fki_anoOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_anoOwner" ON annotation USING btree (ano_owner_id);


--
-- Name: fki_anoType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_anoType" ON annotation USING btree (ano_type_id);


--
-- Name: fki_atbBiblio; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_atbBiblio" ON attribution USING btree (atb_bib_id);


--
-- Name: fki_atbGroup; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_atbGroup" ON attribution USING btree (atb_group_id);


--
-- Name: fki_atbOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_atbOwner" ON attribution USING btree (atb_owner_id);


--
-- Name: fki_atgOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_atgOwner" ON attributiongroup USING btree (atg_owner_id);


--
-- Name: fki_atgType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_atgType" ON attributiongroup USING btree (atg_type_id);


--
-- Name: fki_bibOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_bibOwner" ON bibliography USING btree (bib_owner_id);


--
-- Name: fki_blnImgage; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_blnImgage" ON baseline USING btree (bln_image_id);


--
-- Name: fki_blnOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_blnOwner" ON baseline USING btree (bln_owner_id);


--
-- Name: fki_blnSurface; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_blnSurface" ON baseline USING btree (bln_surface_id);


--
-- Name: fki_blnType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_blnType" ON baseline USING btree (bln_type_id);


--
-- Name: fki_catOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_catOwner" ON catalog USING btree (cat_owner_id);


--
-- Name: fki_catType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_catType" ON catalog USING btree (cat_type_id);


--
-- Name: fki_cmpOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_cmpOwner" ON compound USING btree (cmp_owner_id);


--
-- Name: fki_cmpType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_cmpType" ON compound USING btree (cmp_type_id);


--
-- Name: fki_colOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_colOwner" ON collection USING btree (col_owner_id);


--
-- Name: fki_datOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_datOwner" ON date USING btree (dat_owner_id);


--
-- Name: fki_ednOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_ednOwner" ON edition USING btree (edn_owner_id);


--
-- Name: fki_eraOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_eraOwner" ON era USING btree (era_owner_id);


--
-- Name: fki_frgOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_frgOwner" ON fragment USING btree (frg_owner_id);


--
-- Name: fki_frgPart; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_frgPart" ON fragment USING btree (frg_part_id);


--
-- Name: fki_frgRestState; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_frgRestState" ON fragment USING btree (frg_restore_state_id);


--
-- Name: fki_graOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_graOwner" ON grapheme USING btree (gra_owner_id);


--
-- Name: fki_graType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_graType" ON grapheme USING btree (gra_type_id);


--
-- Name: fki_imgOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_imgOwner" ON image USING btree (img_owner_id);


--
-- Name: fki_imgType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_imgType" ON image USING btree (img_type_id);


--
-- Name: fki_infOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_infOwner" ON inflection USING btree (inf_owner_id);


--
-- Name: fki_itmOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_itmOwner" ON item USING btree (itm_owner_id);


--
-- Name: fki_itmShape; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_itmShape" ON item USING btree (itm_shape_id);


--
-- Name: fki_itmType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_itmType" ON item USING btree (itm_type_id);


--
-- Name: fki_lemClass; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_lemClass" ON lemma USING btree (lem_verb_class_id);


--
-- Name: fki_lemDecl; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_lemDecl" ON lemma USING btree (lem_declension_id);


--
-- Name: fki_lemGender; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_lemGender" ON lemma USING btree (lem_nominal_gender_id);


--
-- Name: fki_lemOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_lemOwner" ON lemma USING btree (lem_owner_id);


--
-- Name: fki_lemPOS; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_lemPOS" ON lemma USING btree (lem_part_of_speech_id);


--
-- Name: fki_lemType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_lemType" ON lemma USING btree (lem_type_id);


--
-- Name: fki_linOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_linOwner" ON line USING btree (lin_owner_id);


--
-- Name: fki_mcxOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_mcxOwner" ON materialcontext USING btree (mcx_owner_id);


--
-- Name: fki_prnOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_prnOwner" ON propernoun USING btree (prn_owner_id);


--
-- Name: fki_prnType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_prnType" ON propernoun USING btree (prn_type_id);


--
-- Name: fki_prtItem; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_prtItem" ON part USING btree (prt_item_id);


--
-- Name: fki_prtOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_prtOwner" ON part USING btree (prt_owner_id);


--
-- Name: fki_prtShape; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_prtShape" ON part USING btree (prt_shape_id);


--
-- Name: fki_prtType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_prtType" ON part USING btree (prt_type_id);


--
-- Name: fki_runBaseline; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_runBaseline" ON run USING btree (run_baseline_id);


--
-- Name: fki_runOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_runOwner" ON run USING btree (run_owner_id);


--
-- Name: fki_runText; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_runText" ON run USING btree (run_text_id);


--
-- Name: fki_sclGrapheme1; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_sclGrapheme1" ON syllablecluster USING btree ((scl_grapheme_ids[1]));


--
-- Name: fki_sclOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_sclOwner" ON syllablecluster USING btree (scl_owner_id);


--
-- Name: fki_sclSegment; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_sclSegment" ON syllablecluster USING btree (scl_segment_id);


--
-- Name: fki_segClarity; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_segClarity" ON segment USING btree (seg_clarity_id);


--
-- Name: fki_segOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_segOwner" ON segment USING btree (seg_owner_id);


--
-- Name: fki_seqOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_seqOwner" ON sequence USING btree (seq_owner_id);


--
-- Name: fki_seqType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_seqType" ON sequence USING btree (seq_type_id);


--
-- Name: fki_spnOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_spnOwner" ON span USING btree (spn_owner_id);


--
-- Name: fki_spnSegment1; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_spnSegment1" ON span USING btree ((spn_segment_ids[1]));


--
-- Name: fki_spnType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_spnType" ON span USING btree (spn_type_id);


--
-- Name: fki_srfFragment; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_srfFragment" ON surface USING btree (srf_fragment_id);


--
-- Name: fki_srfText; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_srfText" ON surface USING btree (srf_text_ids);


--
-- Name: fki_tmdOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_tmdOwner" ON textmetadata USING btree (tmd_owner_id);


--
-- Name: fki_tmdText; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_tmdText" ON textmetadata USING btree (tmd_text_id);


--
-- Name: fki_tokOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_tokOwner" ON token USING btree (tok_owner_id);


--
-- Name: fki_trmOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_trmOwner" ON term USING btree (trm_owner_id);


--
-- Name: fki_trmParent; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_trmParent" ON term USING btree (trm_parent_id);


--
-- Name: fki_trmType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_trmType" ON term USING btree (trm_type_id);


--
-- Name: fki_txtOwner; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_txtOwner" ON text USING btree (txt_owner_id);


--
-- Name: fki_ugrType; Type: INDEX; Schema: public; Owner: -; Tablespace:
--

CREATE INDEX "fki_ugrType" ON usergroup USING btree (ugr_type_id);


--
-- Name: set_segment_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER set_segment_modtime BEFORE UPDATE ON segment FOR EACH ROW EXECUTE PROCEDURE set_modified();


--
-- Name: update_annotation_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_annotation_modtime BEFORE UPDATE ON annotation FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_attribution_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_attribution_modtime BEFORE UPDATE ON attribution FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_attributiongroup_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_attributiongroup_modtime BEFORE UPDATE ON attributiongroup FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_baseline_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_baseline_modtime BEFORE UPDATE ON baseline FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_bibliography_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_bibliography_modtime BEFORE UPDATE ON bibliography FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_catalog_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_catalog_modtime BEFORE UPDATE ON catalog FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_collection_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_collection_modtime BEFORE UPDATE ON collection FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_compound_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_compound_modtime BEFORE UPDATE ON compound FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_date_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_date_modtime BEFORE UPDATE ON date FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_edition_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_edition_modtime BEFORE UPDATE ON edition FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_era_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_era_modtime BEFORE UPDATE ON era FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_fragment_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_fragment_modtime BEFORE UPDATE ON fragment FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_grapheme_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_grapheme_modtime BEFORE UPDATE ON grapheme FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_image_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_image_modtime BEFORE UPDATE ON image FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_inflection_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_inflection_modtime BEFORE UPDATE ON inflection FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_item_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_item_modtime BEFORE UPDATE ON item FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_jsoncache_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_jsoncache_modtime BEFORE UPDATE OF jsc_json_string ON jsoncache FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_lemma_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_lemma_modtime BEFORE UPDATE ON lemma FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_line_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_line_modtime BEFORE UPDATE ON line FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_materialcontext_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_materialcontext_modtime BEFORE UPDATE ON materialcontext FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_part_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_part_modtime BEFORE UPDATE ON part FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_propernoun_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_propernoun_modtime BEFORE UPDATE ON propernoun FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_run_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_run_modtime BEFORE UPDATE ON run FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_sequence_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_sequence_modtime BEFORE UPDATE ON sequence FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_span_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_span_modtime BEFORE UPDATE ON span FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_surface_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_surface_modtime BEFORE UPDATE ON surface FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_syllablecluster_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_syllablecluster_modtime BEFORE UPDATE ON syllablecluster FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_term_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_term_modtime BEFORE UPDATE ON term FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_text_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_text_modtime BEFORE UPDATE ON text FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_textmetadata_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_textmetadata_modtime BEFORE UPDATE ON textmetadata FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_token_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_token_modtime BEFORE UPDATE ON token FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: update_usergroup_modtime; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER update_usergroup_modtime BEFORE UPDATE ON usergroup FOR EACH ROW EXECUTE PROCEDURE update_modified();


--
-- Name: anoOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY annotation
    ADD CONSTRAINT "anoOwner" FOREIGN KEY (ano_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: anoType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY annotation
    ADD CONSTRAINT "anoType" FOREIGN KEY (ano_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: atbBiblio; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY attribution
    ADD CONSTRAINT "atbBiblio" FOREIGN KEY (atb_bib_id) REFERENCES bibliography(bib_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: atbGroup; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY attribution
    ADD CONSTRAINT "atbGroup" FOREIGN KEY (atb_group_id) REFERENCES attributiongroup(atg_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: atbOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY attribution
    ADD CONSTRAINT "atbOwner" FOREIGN KEY (atb_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: atgOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY attributiongroup
    ADD CONSTRAINT "atgOwner" FOREIGN KEY (atg_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: atgType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY attributiongroup
    ADD CONSTRAINT "atgType" FOREIGN KEY (atg_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: bibOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY bibliography
    ADD CONSTRAINT "bibOwner" FOREIGN KEY (bib_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: blnImgage; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY baseline
    ADD CONSTRAINT "blnImgage" FOREIGN KEY (bln_image_id) REFERENCES image(img_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: blnOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY baseline
    ADD CONSTRAINT "blnOwner" FOREIGN KEY (bln_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: blnSurface; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY baseline
    ADD CONSTRAINT "blnSurface" FOREIGN KEY (bln_surface_id) REFERENCES surface(srf_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: blnType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY baseline
    ADD CONSTRAINT "blnType" FOREIGN KEY (bln_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: catOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT "catOwner" FOREIGN KEY (cat_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: catType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY catalog
    ADD CONSTRAINT "catType" FOREIGN KEY (cat_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: cmpCase; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY compound
    ADD CONSTRAINT "cmpCase" FOREIGN KEY (cmp_case_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: cmpClass; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY compound
    ADD CONSTRAINT "cmpClass" FOREIGN KEY (cmp_class_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: cmpOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY compound
    ADD CONSTRAINT "cmpOwner" FOREIGN KEY (cmp_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: cmpType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY compound
    ADD CONSTRAINT "cmpType" FOREIGN KEY (cmp_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: colOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY collection
    ADD CONSTRAINT "colOwner" FOREIGN KEY (col_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: datOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY date
    ADD CONSTRAINT "datOwner" FOREIGN KEY (dat_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: ednOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY edition
    ADD CONSTRAINT "ednOwner" FOREIGN KEY (edn_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: eraOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY era
    ADD CONSTRAINT "eraOwner" FOREIGN KEY (era_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: frgOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY fragment
    ADD CONSTRAINT "frgOwner" FOREIGN KEY (frg_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: frgPart; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY fragment
    ADD CONSTRAINT "frgPart" FOREIGN KEY (frg_part_id) REFERENCES part(prt_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: frgRestState; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY fragment
    ADD CONSTRAINT "frgRestState" FOREIGN KEY (frg_restore_state_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: graOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY grapheme
    ADD CONSTRAINT "graOwner" FOREIGN KEY (gra_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: graType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY grapheme
    ADD CONSTRAINT "graType" FOREIGN KEY (gra_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: imgOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY image
    ADD CONSTRAINT "imgOwner" FOREIGN KEY (img_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: imgType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY image
    ADD CONSTRAINT "imgType" FOREIGN KEY (img_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: itmOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY item
    ADD CONSTRAINT "itmOwner" FOREIGN KEY (itm_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: itmShape; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY item
    ADD CONSTRAINT "itmShape" FOREIGN KEY (itm_shape_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: itmType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY item
    ADD CONSTRAINT "itmType" FOREIGN KEY (itm_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: lemClass; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT "lemClass" FOREIGN KEY (lem_verb_class_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: lemDecl; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT "lemDecl" FOREIGN KEY (lem_declension_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: lemGender; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT "lemGender" FOREIGN KEY (lem_nominal_gender_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: lemOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT "lemOwner" FOREIGN KEY (lem_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: lemPOS; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT "lemPOS" FOREIGN KEY (lem_part_of_speech_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: lemType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY lemma
    ADD CONSTRAINT "lemType" FOREIGN KEY (lem_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: linOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY line
    ADD CONSTRAINT "linOwner" FOREIGN KEY (lin_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: mcxOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY materialcontext
    ADD CONSTRAINT "mcxOwner" FOREIGN KEY (mcx_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: prnOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY propernoun
    ADD CONSTRAINT "prnOwner" FOREIGN KEY (prn_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: prnType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY propernoun
    ADD CONSTRAINT "prnType" FOREIGN KEY (prn_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: prtItem; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY part
    ADD CONSTRAINT "prtItem" FOREIGN KEY (prt_item_id) REFERENCES item(itm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prtOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY part
    ADD CONSTRAINT "prtOwner" FOREIGN KEY (prt_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: prtShape; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY part
    ADD CONSTRAINT "prtShape" FOREIGN KEY (prt_shape_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: prtType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY part
    ADD CONSTRAINT "prtType" FOREIGN KEY (prt_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: runBaseline; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY run
    ADD CONSTRAINT "runBaseline" FOREIGN KEY (run_baseline_id) REFERENCES baseline(bln_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: runOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY run
    ADD CONSTRAINT "runOwner" FOREIGN KEY (run_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: runScribe; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY run
    ADD CONSTRAINT "runScribe" FOREIGN KEY (run_scribe_id) REFERENCES propernoun(prn_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: runScript; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY run
    ADD CONSTRAINT "runScript" FOREIGN KEY (run_script_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: runText; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY run
    ADD CONSTRAINT "runText" FOREIGN KEY (run_text_id) REFERENCES text(txt_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: runWriting; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY run
    ADD CONSTRAINT "runWriting" FOREIGN KEY (run_writing_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: sclOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY syllablecluster
    ADD CONSTRAINT "sclOwner" FOREIGN KEY (scl_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: sclSegment; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY syllablecluster
    ADD CONSTRAINT "sclSegment" FOREIGN KEY (scl_segment_id) REFERENCES segment(seg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: segClarity; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY segment
    ADD CONSTRAINT "segClarity" FOREIGN KEY (seg_clarity_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: segOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY segment
    ADD CONSTRAINT "segOwner" FOREIGN KEY (seg_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: seqOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY sequence
    ADD CONSTRAINT "seqOwner" FOREIGN KEY (seq_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: seqType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY sequence
    ADD CONSTRAINT "seqType" FOREIGN KEY (seq_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: spnOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY span
    ADD CONSTRAINT "spnOwner" FOREIGN KEY (spn_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: spnType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY span
    ADD CONSTRAINT "spnType" FOREIGN KEY (spn_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: srfFragment; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY surface
    ADD CONSTRAINT "srfFragment" FOREIGN KEY (srf_fragment_id) REFERENCES fragment(frg_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: tmdOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY textmetadata
    ADD CONSTRAINT "tmdOwner" FOREIGN KEY (tmd_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: tmdText; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY textmetadata
    ADD CONSTRAINT "tmdText" FOREIGN KEY (tmd_text_id) REFERENCES text(txt_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: tokOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY token
    ADD CONSTRAINT "tokOwner" FOREIGN KEY (tok_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: trmOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY term
    ADD CONSTRAINT "trmOwner" FOREIGN KEY (trm_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: trmParent; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY term
    ADD CONSTRAINT "trmParent" FOREIGN KEY (trm_parent_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: trmType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY term
    ADD CONSTRAINT "trmType" FOREIGN KEY (trm_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: txtOwner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY text
    ADD CONSTRAINT "txtOwner" FOREIGN KEY (txt_owner_id) REFERENCES usergroup(ugr_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- Name: ugrType; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY usergroup
    ADD CONSTRAINT "ugrType" FOREIGN KEY (ugr_type_id) REFERENCES term(trm_id) ON UPDATE CASCADE ON DELETE SET DEFAULT;


--
-- PostgreSQL database dump complete
--

