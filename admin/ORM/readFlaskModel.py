from flask import Flask, json
from flask_sqlalchemy import SQLAlchemy
# from flask_sqlalchemy.dialects.postgresql.fields import
# from shapely.geometry import Polygon
from datetime import datetime
import os
# create a deferred db object to hold READ's model declaration
# (not attached to a database)
db = SQLAlchemy()


def connectReadDB(app, dbName=None):
    '''
      helper function that connects/binds a Flask app to
      a READ database
    '''
    # create Flask app obj
    # app = Flask(__name__)

    # setup app config
    if (dbName is None):
        dbName = os.getenv('READ_DBNAME')
        if (dbName is None):
            dbName = 'testdb'
    app_config_obj = None
    if 'APP_CONFIGURATION_FILE' in os.environ.keys():
        app_config_obj = os.environ['APP_CONFIGURATION_FILE']
    print('config.obj',app_config_obj)
    if (app_config_obj is not None and app_config_obj != ''):
        app.config.from_pyfile(app_config_obj)
        app.config['SQLALCHEMY_DATABASE_URI'] = app.config['DATABASE_BASEURI']\
            + str(dbName)
        print("successfully configured app")
    else:
        app.config['SECRET_KEY'] = "98d0tie372SD90AS)(dd78(*&ASWWHD08A"
        app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
        app.config['SQLALCHEMY_DATABASE_URI'] = \
            f'postgresql+psycopg2://postgres:gandhari@db/{str(dbName)}'
#    print(app.config['SQLALCHEMY_DATABASE_URI'])
    app.config['DEBUG'] = True
    db.init_app(app)
    return app, db


Base = db.Model


class Term(Base):
    """
    Fields:
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
      "trm_annotation_ids" int[] NULL,
      "trm_visibility_ids" int[] NULL DEFAULT '{6}',
      "trm_scratch" text NULL
    """
    __tablename__ = 'term'
    trm_id = db.Column(db.Integer, nullable=False, primary_key=True)
    trm_labels = db.Column(db.Text, nullable=False, default="needs label")
    trm_parent_id = db.Column(db.Integer, nullable=True, default=1)
    trm_type_id = db.Column(db.Integer,
                            nullable=True, default=1)
    trm_list_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    trm_code = db.Column(db.Text, nullable=True)
    trm_description = db.Column(db.Text, nullable=True)
    trm_url = db.Column(db.Text, nullable=True)
    trm_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    trm_owner_id = db.Column(db.Integer,
                             nullable=False, default=1)
    trm_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    trm_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[2])
    trm_scratch = db.Column(db.Text, nullable=True)
    cat_type = db.relationship('Catalog', backref='cat_type',
                               primaryjoin='Term.trm_id==Catalog.cat_type_id')
    bln_type = db.relationship('Baseline', backref='bln_type',
                               primaryjoin='Term.trm_id==Baseline.bln_type_id')
    edn_type = db.relationship('Edition', backref='edn_type',
                               primaryjoin='Term.trm_id==Edition.edn_type_id')
#    img_type = db.relationship('Image', backref='img_type',
#                               primaryjoin='Term.trm_id==Image.img_type_id')
    seq_type = db.relationship('Sequence', backref='seq_type',
                               primaryjoin='Term.trm_id==Sequence.seq_type_id')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.trm_labels


class UserGroup(Base):
    """
    Fields:
      "ugr_id" serial NOT NULL PRIMARY KEY,
      "ugr_name" text NOT NULL UNIQUE,
      "ugr_type_id" int NOT NULL DEFAULT 335,
       -- todo correct default set immutable in term table
      "ugr_given_name" text,
      "ugr_family_name" text,
      "ugr_description" text NULL,
      "ugr_password" text,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "ugr_member_ids" int[] NULL,
      "ugr_admin_ids" int[] NULL,
      "ugr_scratch" text NULL
    """
    typeLbl = 'ugr'
    __tablename__ = 'usergroup'
    ugr_id = db.Column(db.Integer, nullable=False, primary_key=True)
    ugr_name = db.Column(db.Text, unique=True, nullable=False)
    ugr_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=False,
                            default=335)  # Warning Term Dependency
    ugr_given_name = db.Column(db.Text, nullable=True)
    ugr_family_name = db.Column(db.Text, nullable=True)
    ugr_description = db.Column(db.Text, nullable=True)
    ugr_password = db.Column(db.Text, nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    ugr_member_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    ugr_admin_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    ugr_scratch = db.Column(db.Text, nullable=True)
    ano_owner = db.relationship('Annotation', backref='owner')
    atb_owner = db.relationship('Attribution', backref='owner')
    atg_owner = db.relationship('AttributionGroup', backref='owner')
    bln_owner = db.relationship('Baseline', backref='owner')
    bib_owner = db.relationship('Bibliography', backref='owner')
    cat_owner = db.relationship('Catalog', backref='owner')
    cmp_owner = db.relationship('Compound', backref='owner')
    col_owner = db.relationship('Collection', backref='owner')
    dat_owner = db.relationship('DateTemporal', backref='owner')
    edn_owner = db.relationship('Edition', backref='owner')
    era_owner = db.relationship('Era', backref='owner')
    frg_owner = db.relationship('Fragment', backref='owner')
    gra_owner = db.relationship('Grapheme', backref='owner')
    img_owner = db.relationship('Image', backref='owner')
    itm_owner = db.relationship('Item', backref='owner')
    lem_owner = db.relationship('Lemma', backref='owner')
    prn_owner = db.relationship('ProperNoun', backref='owner')
    prt_owner = db.relationship('Part', backref='owner')
    scl_owner = db.relationship('SyllableCluster', backref='owner')
    seg_owner = db.relationship('Segment', backref='owner')
    seq_owner = db.relationship('Sequence', backref='owner')
    txt_owner = db.relationship('TextDoc', backref='owner')
    tmd_owner = db.relationship('TextMetadata', backref='owner')
    tok_owner = db.relationship('Token', backref='owner')
    trm_owner = db.relationship('Term', backref='owner')

    def __int__(self):
        ''' function to display representation of object '''
        return self.ugr_id

    def __str__(self):
        ''' function to display representation of object '''
        return self.ugr_name

    def __repr__(self):
        ''' function to display representation of object '''
        return self.ugr_name


class Annotation(Base):
    """
    Fields:
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
      "ano_visibility_ids" int[] NULL DEFAULT '{6}',
      "ano_scratch" text NULL
    """
    typeLbl = 'ano'
    __tablename__ = 'annotation'
    ano_id = db.Column(db.Integer, nullable=False, primary_key=True)
    ano_linkfrom_ids = db.Column(db.ARRAY(db.Text), nullable=True)
    ano_linkto_ids = db.Column(db.ARRAY(db.Text), nullable=True)
    ano_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    ano_text = db.Column(db.Text, nullable=True)
    ano_url = db.Column(db.Text, nullable=True)
    ano_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    ano_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    ano_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    ano_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    ano_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.ano_text


class AttributionGroup(Base):
    """
    Fields:
      "atg_id" serial NOT NULL PRIMARY KEY,
      "atg_name" text,
      "atg_type_id" int NOT NULL DEFAULT 352,
      "atg_realname" text,
      "atg_date_created" date NOT NULL DEFAULT CURRENT_DATE,
      "atg_description" text NULL,
      "atg_member_ids" int[] NULL,
      "atg_admin_ids" int[] NULL,
      "atg_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "atg_owner_id" int NULL DEFAULT 2,
      "atg_annotation_ids" int[] NULL,
      "atg_visibility_ids" int[] NULL DEFAULT '{6}',
      "atg_scratch" text NULL
    """
    typeLbl = 'atg'
    __tablename__ = 'attributiongroup'
    atg_id = db.Column(db.Integer, nullable=False, primary_key=True)
    atg_name = db.Column(db.Text)
    atg_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=False)
    atg_realname = db.Column(db.Text)
    atg_date_created = db.Column(
        db.DateTime, nullable=False, default=datetime.now())
    atg_description = db.Column(db.Text, nullable=True)
    atg_member_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    atg_admin_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    atg_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    atg_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    atg_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    atg_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    atg_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.atg_name


class Attribution(Base):
    """
    Fields:
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
    """
    typeLbl = 'atb'
    __tablename__ = 'attribution'
    atb_id = db.Column(db.Integer, nullable=False, primary_key=True)
    atb_title = db.Column(db.Text, nullable=False)
    atb_types = db.Column(db.ARRAY(db.Text), nullable=True)
    atb_bib_id = db.Column(db.Integer, db.ForeignKey('bibliography.bib_id'),
                           nullable=True)
    atb_detail = db.Column(db.Text, nullable=True)
    atb_description = db.Column(db.Text, nullable=True)
    atb_group_id = db.Column(db.Integer,
                             db.ForeignKey(AttributionGroup.atg_id),
                             nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    atb_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    atb_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    atb_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    atb_scratch = db.Column(db.Text, nullable=True)
    atb_group = db.relationship('AttributionGroup', backref='group')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.atb_title


class Authtoken(Base):
    """
    Fields:
      "aut_id" serial NOT NULL PRIMARY KEY,
      "aut_selector" varchar(32) NOT NULL UNIQUE,
      "aut_hashed_validator" varchar(64) NULL,
      "aut_expire" int default  now()::abstime::int,
      "modified" timestamp default CURRENT_TIMESTAMP,
      "aut_user_id" int NOT NULL
    """
    typeLbl = 'aut'
    __tablename__ = 'authtoken'
    aut_id = db.Column(db.Integer, nullable=False, primary_key=True)
    aut_selector = db.Column(db.VARCHAR(32), nullable=False, unique=True)
    aut_hashed_validator = db.Column(db.VARCHAR(64), nullable=True)
    aut_expire = db.Column(db.Integer, default=datetime.now())
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    aut_user_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False)


class Image(Base):
    """
    Fields:
      "img_id" serial NOT NULL PRIMARY KEY,
      "img_title" text NULL,
      "img_type_id" int NOT NULL DEFAULT 1,
      "img_url" text,
      "img_image_pos" polygon[] NULL,
      "img_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "img_owner_id" int NULL DEFAULT 2,
      "img_annotation_ids" int[] NULL,
      "img_visibility_ids" int[] NULL DEFAULT '{6}',
      "img_scratch" text NULL
    """
    typeLbl = 'img'
    __tablename__ = 'image'

    img_id = db.Column(db.Integer, nullable=False, primary_key=True)
    img_title = db.Column(db.Text, nullable=True)
    img_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    img_url = db.Column(db.Text, nullable=True)
    img_image_pos = db.Column(db.ARRAY(db.Text), nullable=True)
    img_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    img_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    img_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    img_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[6])
    img_scratch = db.Column(db.Text, nullable=True)
    baselines = db.relationship('Baseline', backref='image',
                                primaryjoin='Image.img_id==Baseline.bln_image_id')
    surfaces = db.relationship('Surface', backref='images',
                                primaryjoin='Image.img_id==any_(foreign(Surface.srf_image_ids))')
    texts = db.relationship('TextDoc', backref='images',
                                primaryjoin='Image.img_id==any_(foreign(TextDoc.txt_image_ids))')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.img_title


class Bibliography(Base):
    """
    Fields:
      "bib_id" serial NOT NULL PRIMARY KEY,
      "bib_name" text NULL,
      "bib_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "bib_owner_id" int NULL DEFAULT 2,
      "bib_annotation_ids" int[] NULL,
      "bib_visibility_ids" int[] NULL DEFAULT '{6}',
      "bib_scratch" text NULL
    """
    typeLbl = 'bib'
    __tablename__ = 'bibliography'
    bib_id = db.Column(db.Integer, nullable=False, primary_key=True)
    bib_name = db.Column(db.Text, nullable=False)
    bib_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    bib_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    bib_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    bib_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[6])
    bib_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.bib_name


class Catalog(Base):
    """
    Fields:
      "cat_id" serial NOT NULL PRIMARY KEY,
      "cat_title" text NULL,
      "cat_type_id" int NULL default=373,
      "cat_lang_id" int NULL,
      "cat_description" text NULL,
      "cat_edition_ids" int[] NULL,
      "cat_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "cat_owner_id" int NULL DEFAULT 2,
      "cat_annotation_ids" int[] NULL,
      "cat_visibility_ids" int[] NULL DEFAULT '{6}',
      "cat_scratch" text NULL
    """
    typeLbl = 'cat'
    __tablename__ = 'catalog'
    cat_id = db.Column(db.Integer, nullable=False, primary_key=True)
    cat_title = db.Column(db.Text, nullable=True)
    cat_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True, default=373)
    cat_lang_id = db.Column(db.Integer, nullable=True)
    cat_description = db.Column(db.Text, nullable=True)
    cat_edition_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    cat_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    cat_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    cat_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    cat_visibility_ids = db.Column(db.ARRAY(db.Integer),
                                   nullable=True, default=[6])
    cat_scratch = db.Column(db.Text, nullable=True)
    lem_catalog = db.relationship('Lemma', backref='catalog')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.cat_title


class Collection(Base):
    """
    Fields:
      "col_id" serial NOT NULL PRIMARY KEY,
      "col_title" text NOT NULL DEFAULT 'Need Title',
      "col_location_refs" text[] NULL,
      "col_description" text NOT NULL DEFAULT 'Need Description',
      "col_item_part_fragment_ids" varchar(31)[] NULL,
      "col_exclude_part_fragment_ids" varchar(31)[] NULL,
      "col_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "col_owner_id" int NULL DEFAULT 2,
      "col_annotation_ids" int[] NULL,
      "col_visibility_ids" int[] NULL DEFAULT '{6}',
      "col_scratch" text NULL
    """
    typeLbl = 'col'
    __tablename__ = 'collection'
    col_id = db.Column(db.Integer, nullable=False, primary_key=True)
    col_title = db.Column(db.Text, nullable=False, default="Enter Title Here")
    col_location_refs = db.Column(db.ARRAY(db.Text), nullable=True)
    col_description = db.Column(db.Text, nullable=False,
                                default="Enter Description Here")
    col_item_part_fragment_ids = db.Column(db.ARRAY(db.VARCHAR(31)),
                                           nullable=True)
    col_exclude_part_fragment_ids = db.Column(db.ARRAY(db.VARCHAR(31)),
                                              nullable=True)
    col_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    col_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    col_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    col_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    col_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.col_title


class Compound(Base):
    """
    Fields:
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
      "cmp_annotation_ids" int[] NULL,
      "cmp_visibility_ids" int[] NULL DEFAULT '{6}',
      "cmp_scratch" text NULL
    """
    typeLbl = 'cmp'
    __tablename__ = 'compound'
    cmp_id = db.Column(db.Integer, nullable=False, primary_key=True)
    cmp_value = db.Column(db.Text, nullable=True)
    cmp_transcription = db.Column(db.Text, nullable=True)
    cmp_component_ids = db.Column(db.ARRAY(db.VARCHAR(31)), nullable=True)
    cmp_case_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    cmp_class_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    cmp_type_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    cmp_sort_code = db.Column(db.Text, nullable=True)
    cmp_sort_code2 = db.Column(db.Text, nullable=True)
    cmp_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    cmp_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    cmp_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    cmp_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    cmp_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.cmp_value


class DateTemporal(Base):
    """
    Fields:
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
      "dat_annotation_ids" int[] NULL,
      "dat_visibility_ids" int[] NULL DEFAULT '{6}',
      "dat_scratch" text NULL
    """
    typeLbl = 'dat'
    __tablename__ = 'date'
    dat_id = db.Column(db.Integer, nullable=False, primary_key=True)
    dat_prob_begin_date = db.Column(db.Integer, nullable=False, default=9999)
    dat_prob_end_date = db.Column(db.Integer, nullable=True)
    dat_entity_id = db.Column(db.ARRAY(db.VARCHAR(31)), nullable=True)
    dat_evidences = db.Column(db.ARRAY(db.Text), nullable=True)
    dat_preferred_era_id = db.Column(db.Integer, nullable=True)
    dat_era_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    dat_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    dat_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    dat_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    dat_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    dat_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.dat_prob_begin_date


class Langsort(Base):
    """
    Fields:
      "srt_id" serial NOT NULL PRIMARY KEY,
      "srt_iso_name" text NULL,
      "srt_name" text NULL,
      "srt_description" text NULL,
      "srt_lang_weight" text NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP
    """
    typeLbl = 'srt'
    __tablename__ = 'langsort'
    srt_id = db.Column(db.Integer, nullable=False, primary_key=True)
    srt_iso_name = db.Column(db.Text, nullable=True)
    srt_name = db.Column(db.Text, nullable=True)
    srt_description = db.Column(db.Text, nullable=True)
    srt_lang_weight = db.Column(db.Text, nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    lsorting = db.relationship('Encode', backref='Lang Sort')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.srt_name


class Encode(Base):
    """
    Fields:
      "enc_id" serial NOT NULL PRIMARY KEY,
      "enc_langsort_id" int NULL,
      "enc_code" text NULL,
      "enc_type_id" int NULL,
      "enc_weight" text NULL,
      "enc_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP
    """
    typeLbl = 'enc'
    __tablename__ = 'encode'
    enc_id = db.Column(db.Integer, nullable=False, primary_key=True)
    enc_langsort_id = db.Column(db.Integer, db.ForeignKey('langsort.srt_id'),
                                nullable=True)
    enc_code = db.Column(db.Text, nullable=True)
    enc_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    enc_weight = db.Column(db.Text, nullable=True)
    enc_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())

    def __repr__(self):
        ''' function to display representation of object '''
        return self.enc_code


class Era(Base):
    """
    Fields:
      "era_id" serial NOT NULL PRIMARY KEY,
      "era_title" text NOT NULL DEFAULT 'Need Title',
      "era_begin_date" int NOT NULL,
      "era_end_date" int NULL,
      "era_order" int NULL,
      -- TODO Check whether this can be calculated and not required
      "era_preferred" bool NULL,
      "era_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "era_owner_id" int NULL DEFAULT 2,
      "era_annotation_ids" int[] NULL,
      "era_visibility_ids" int[] NULL DEFAULT '{6}',
      "era_scratch" text NULL
    """
    typeLbl = 'era'
    __tablename__ = 'era'
    era_id = db.Column(db.Integer, nullable=False, primary_key=True)
    era_title = db.Column(db.Text, nullable=False,
                          default='Enter Era Name Here')
    era_begin_date = db.Column(db.Integer, nullable=False)
    era_end_date = db.Column(db.Integer, nullable=True)
    era_order = db.Column(db.Integer, nullable=True)
    era_preferred = db.Column(db.Boolean, nullable=True)
    era_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    era_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    era_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    era_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[6])
    era_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.era_title


class Item(Base):
    """
    Fields:
      "itm_id" serial NOT NULL PRIMARY KEY,
      "itm_title" text NOT NULL DEFAULT 'Need Title',
      "itm_description" text NULL,
      "itm_idno" text NULL,
      "itm_type_id" int NULL,
      "itm_shape_id" int NULL,
      "itm_measure" text NULL,
      "itm_image_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "itm_owner_id" int NULL DEFAULT 2,
      "itm_annotation_ids" int[] NULL,
      "itm_visibility_ids" int[] NULL DEFAULT '{6}',
      "itm_scratch" text NULL
    """
    typeLbl = 'itm'
    __tablename__ = 'item'

    itm_id = db.Column(db.Integer, nullable=False, primary_key=True)
    itm_title = db.Column(db.Text, nullable=False, default="Enter Title Here")
    itm_description = db.Column(db.Text, nullable=True)
    itm_idno = db.Column(db.Text, nullable=True)
    itm_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    itm_shape_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                             nullable=True)
    itm_measure = db.Column(db.Text, nullable=True)
    itm_image_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    itm_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    itm_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    itm_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    itm_scratch = db.Column(db.Text, nullable=True)
    parts = db.relationship('Part', backref='item')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.itm_title or (self.typeLbl + str(self.itm_id))


class Part(Base):
    """
    Fields:
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
      "prt_image_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "prt_owner_id" int NULL DEFAULT 2,
      "prt_annotation_ids" int[] NULL,
      "prt_visibility_ids" int[] NULL DEFAULT '{6}',
      "prt_scratch" text NULL
    """
    typeLbl = 'prt'
    __tablename__ = 'part'

    prt_id = db.Column(db.Integer, nullable=False, primary_key=True)
    prt_label = db.Column(db.Text, nullable=True)
    prt_description = db.Column(db.Text, nullable=True)
    prt_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    prt_shape_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                             nullable=True)
    prt_mediums = db.Column(db.ARRAY(db.Text), nullable=True)
    prt_measure = db.Column(db.Text, nullable=True)
    prt_manufacture_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                                   nullable=True)
    prt_sequence = db.Column(db.Integer, nullable=True)
    prt_item_id = db.Column(db.Integer, db.ForeignKey('item.itm_id'),
                            nullable=True)
    prt_image_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    prt_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    prt_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    prt_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    prt_scratch = db.Column(db.Text, nullable=True)
    fragments = db.relationship('Fragment', backref='part')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.prt_label or (self.typeLbl + str(self.prt_id))


class Fragment(Base):
    """
    Fields:
      "frg_id" serial NOT NULL  PRIMARY KEY,
      "frg_label" text NULL,
      "frg_description" text NULL,
      "frg_measure" text NULL,
      "frg_restore_state_id" int NULL,
      "frg_location_refs" text[] NULL,
      "frg_part_id" int NULL,
      "frg_material_context_ids" int[] NULL,
      "frg_image_ids" int[] NULL,
      "frg_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "frg_owner_id" int NULL DEFAULT 2,
      "frg_annotation_ids" int[] NULL,
      "frg_visibility_ids" int[] NULL DEFAULT '{6}',
    """
    typeLbl = 'frg'
    __tablename__ = 'fragment'

    frg_id = db.Column(db.Integer, nullable=False, primary_key=True)
    frg_label = db.Column(db.Text, nullable=True)
    frg_description = db.Column(db.Text, nullable=True)
    frg_measure = db.Column(db.Text, nullable=True)
    frg_restore_state_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                                     nullable=True)
    frg_location_refs = db.Column(db.ARRAY(db.Text), nullable=True)
    frg_part_id = db.Column(db.Integer, db.ForeignKey(Part.prt_id),
                            nullable=True)
    frg_material_context_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    frg_image_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    frg_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    frg_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    frg_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    frg_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    frg_scratch = db.Column(db.Text, nullable=True)
    surfaces = db.relationship('Surface', backref='fragment')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.frg_label or (self.typeLbl + str(self.frg_id))


class Grapheme(Base):
    """
    Fields:
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
      "gra_annotation_ids" int[] NULL,
      "gra_visibility_ids" int[] NULL DEFAULT '{6}',
      "gra_scratch" text NULL
    """
    typeLbl = 'gra'
    __tablename__ = 'grapheme'

    gra_id = db.Column(db.Integer, nullable=False, primary_key=True)
    gra_grapheme = db.Column(db.VARCHAR(
        15), nullable=False, default='Need Grapheme')
    gra_uppercase = db.Column(db.VARCHAR(31), nullable=True)
    gra_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    gra_text_critical_mark = db.Column(db.Text, nullable=True)
    gra_alt = db.Column(db.VARCHAR(31), nullable=True)
    gra_emmendation = db.Column(db.VARCHAR(63), nullable=True)
    gra_decomposition = db.Column(db.VARCHAR(63), nullable=True)
    gra_sort_code = db.Column(db.VARCHAR(63), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    gra_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    gra_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    gra_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[6])
    gra_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.gra_grapheme


class Inflection(Base):
    """
    Fields:
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
      "inf_annotation_ids" int[] NULL,
      "inf_visibility_ids" int[] NULL DEFAULT '{6}',
      "inf_scratch" text NULL
    """
    typeLbl = 'inf'
    __tablename__ = 'inflection'

    inf_id = db.Column(db.Integer, nullable=False, primary_key=True)
    inf_chaya = db.Column(db.Text, nullable=True)
    inf_component_ids = db.Column(db.ARRAY(db.Text), nullable=True)
    inf_certainty = db.Column(db.ARRAY(db.Integer), nullable=True)
    inf_case_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_nominal_gender_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_gram_number_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_verb_person_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_verb_voice_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_verb_tense_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_verb_mood_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_verb_second_conj_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    inf_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    inf_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    inf_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    inf_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    inf_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return 'inf' + str(self.inf_id)


class Lemma(Base):
    """
    Fields:
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
      "lem_annotation_ids" int[] NULL,
      "lem_visibility_ids" int[] NULL DEFAULT '{6}',
      "lem_scratch" text NULL
    """
    typeLbl = 'lem'
    __tablename__ = 'lemma'

    lem_id = db.Column(db.Integer, nullable=False, primary_key=True)
    lem_value = db.Column(db.Text, nullable=True)
    lem_search = db.Column(db.Text, nullable=True)
    lem_translation = db.Column(db.Text, nullable=True)
    lem_homographorder = db.Column(db.Integer, nullable=True)
    lem_type_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    lem_certainty = db.Column(db.ARRAY(db.Integer), nullable=True)
    lem_part_of_speech_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    lem_subpart_of_speech_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    lem_nominal_gender_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    lem_verb_class_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    lem_declension_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    lem_description = db.Column(db.Text, nullable=True)
    lem_catalog_id = db.Column(db.Integer, db.ForeignKey('catalog.cat_id'),
                               nullable=True)
    lem_component_ids = db.Column(db.ARRAY(db.VARCHAR(31)), nullable=True)
    lem_sort_code = db.Column(db.Text, nullable=True)
    lem_sort_code2 = db.Column(db.Text, nullable=True)
    lem_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    lem_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    lem_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    lem_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[6])
    lem_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.lem_value


# line on hold
class Line(Base):
    """
    Fields:
      "lin_id" serial NOT NULL PRIMARY KEY,
      "lin_order" int NULL,
      "lin_mask" text NULL,
      "lin_span_ids" int[] NULL,
      "lin_annotation_ids" int[] NULL,
      "lin_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "lin_owner_id" int NULL DEFAULT 2,
      "lin_visibility_ids" int[] NULL DEFAULT '{6}',
      "lin_scratch" text NULL
    """
    typeLbl = 'lin'
    __tablename__ = 'line'

    lin_id = db.Column(db.Integer, nullable=False, primary_key=True)
    lin_order = db.Column(db.Integer, nullable=True)
    lin_mask = db.Column(db.Text, nullable=True)
    lin_span_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    lin_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    lin_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    lin_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    lin_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    lin_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.lin_mask


class MaterialContext(Base):
    """
    Fields:
      "mcx_id" serial NOT NULL PRIMARY KEY,
      "mcx_arch_context" text NULL,
      "mcx_find_status" text NULL,
      "mcx_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "mcx_owner_id" int NULL DEFAULT 2,
      "mcx_annotation_ids" int[] NULL,
      -- TODO  handle integrity with triggers and/or utilities
        //note: append syntax upmaterialcontext foo set a = a || newInt
      "mcx_visibility_ids" int[] NULL DEFAULT '{6}',
      -- TODO  handle integrity with triggers and/or utilities
        //note: append syntax upmaterialcontext foo set a = a || newInt
      "mcx_scratch" text NULL
    """
    typeLbl = 'mcx'
    __tablename__ = 'materialcontext'

    mcx_id = db.Column(db.Integer, nullable=False, primary_key=True)
    mcx_arch_context = db.Column(db.Text, nullable=True)
    mcx_find_status = db.Column(db.Text, nullable=True)
    mcx_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    mcx_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    mcx_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    mcx_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    mcx_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.mcx_arch_context


class ProperNoun(Base):
    """
    Fields:
      "prn_id" serial NOT NULL PRIMARY KEY,
      "prn_labels" text NOT NULL DEFAULT 'Need Labels',
      "prn_type_id" int NULL,
      "prn_parent_id" int NULL,
      "prn_evidences" text NULL,
      "prn_description" text NULL,
      "prn_url" text NULL,
      "prn_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "prn_owner_id" int NULL DEFAULT 2,
      "prn_annotation_ids" int[] NULL,
      "prn_visibility_ids" int[] NULL DEFAULT '{6}',
      "prn_scratch" text NULL
    """
    typeLbl = 'prn'
    __tablename__ = 'propernoun'

    prn_id = db.Column(db.Integer, nullable=False, primary_key=True)
    prn_labels = db.Column(db.Text, nullable=False,
                           default='Enter lang-code label pair')
    prn_type_id = db.Column(db.Integer, nullable=True)
    prn_parent_id = db.Column(db.Integer, nullable=True)
    prn_evidences = db.Column(db.Text, nullable=True)
    prn_description = db.Column(db.Text, nullable=True)
    prn_url = db.Column(db.Text, nullable=True)
    prn_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    prn_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    prn_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    prn_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    prn_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.prn_labels


class Segment(Base):
    """
    Fields:
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
      "seg_annotation_ids" int[] NULL,
      "seg_visibility_ids" int[] NULL DEFAULT '{6}',
      "seg_scratch" text NULL
    """
    typeLbl = 'seg'
    __tablename__ = 'segment'

    seg_id = db.Column(db.Integer, nullable=False, primary_key=True)
    seg_baseline_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    seg_image_pos = db.Column(db.Text, nullable=True)
    seg_string_pos = db.Column(db.ARRAY(db.Text), nullable=True)
    seg_rotation = db.Column(db.Integer, nullable=True)
    seg_layer = db.Column(db.Integer, nullable=True)
    seg_clarity_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                               nullable=True)
    seg_obscurations = db.Column(db.ARRAY(db.Text), nullable=True)
    seg_url = db.Column(db.Text, nullable=True)
    seg_mapped_seg_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    seg_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    seg_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    seg_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    seg_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    seg_scratch = db.Column(db.Text, nullable=True)

    def __str__(self):
        ''' function to display representation of object '''
        return 'seg' + str(self.seg_id)

    def __repr__(self):
        ''' function to display representation of object '''
        return 'seg' + str(self.seg_id)


class Sequence(Base):
    """
    Fields:
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
      "seq_annotation_ids" int[] NULL,
      "seq_visibility_ids" int[] NULL DEFAULT '{6}',
      "seq_scratch" text NULL
    """
    typeLbl = 'seq'
    __tablename__ = 'sequence'

    seq_id = db.Column(db.Integer, nullable=False, primary_key=True)
    seq_label = db.Column(db.Text, nullable=True)
    seq_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    seq_superscript = db.Column(db.Text, nullable=True)
    seq_entity_ids = db.Column(db.ARRAY(db.VARCHAR(30)), nullable=True)
    seq_theme_id = db.Column(db.Integer, nullable=True)
    seq_ord = db.Column(db.Integer, nullable=True)
    seq_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    seq_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    seq_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    seq_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    seq_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.seq_label


# span on hold
class Span(Base):
    """
    Fields:
      "spn_id" serial NOT NULL PRIMARY KEY,
      "spn_type_id" int NULL,
      "spn_segment_ids" int[] NULL,
      "spn_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "spn_owner_id" int NULL DEFAULT 2,
      "spn_annotation_ids" int[] NULL,
      "spn_visibility_ids" int[] NULL DEFAULT '{6}',
      "spn_scratch" text NULL
    """
    typeLbl = 'spn'
    __tablename__ = 'span'

    spn_id = db.Column(db.Integer, nullable=False, primary_key=True)
    spn_type_id = db.Column(
        db.Integer, db.ForeignKey('term.trm_id'), nullable=True)
    spn_segment_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    spn_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    spn_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    spn_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    spn_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    spn_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return 'spn' + str(self.spn_id)


class Surface(Base):
    """
    Fields:
      "srf_id" serial NOT NULL PRIMARY KEY,
      "srf_description" text NULL,
      "srf_label" text NULL,
      "srf_number" int NOT NULL DEFAULT 1,
      "srf_layer_number" int NOT NULL DEFAULT 1,
      "srf_scripts" text[] NULL,
      "srf_text_ids" int[] NULL,
      "srf_reconst_surface_id" int,
      "srf_fragment_id" int NULL,
      "srf_image_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "srf_annotation_ids" int[] NULL,
      "srf_visibility_ids" int[] NULL DEFAULT '{6}',
      "srf_scratch" text NULL
    """
    typeLbl = 'srf'
    __tablename__ = 'surface'

    srf_id = db.Column(db.Integer, nullable=False, primary_key=True)
    srf_description = db.Column(db.Text, nullable=True)
    srf_label = db.Column(db.Text, nullable=True)
    srf_number = db.Column(db.Integer, nullable=False, default=1)
    srf_layer_number = db.Column(db.Integer, nullable=False, default=1)
    srf_scripts = db.Column(db.ARRAY(db.Text), nullable=True)
    srf_text_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    srf_reconst_surface_id = db.Column(db.Integer, nullable=True)
    srf_fragment_id = db.Column(db.Integer, db.ForeignKey(Fragment.frg_id),
                                nullable=True)
    srf_image_ids = db.Column(db.ARRAY(db.Integer),
                              nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    srf_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    srf_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    srf_scratch = db.Column(db.Text, nullable=True)
    baselines = db.relationship('Baseline', backref='surface')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.srf_label or (self.typeLbl + str(self.srf_id))


class Baseline(Base):
    """
    Fields:
      "bln_id" serial NOT NULL PRIMARY KEY,
      "bln_type_id" int NOT NULL DEFAULT 356,
      "bln_image_id" int NULL,
      "bln_surface_id" int NULL,
      "bln_image_position" polygon[] NULL,
      "bln_transcription" text NULL,
      "bln_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "bln_owner_id" int NULL DEFAULT 2,
      "bln_annotation_ids" int[] NULL,
      "bln_visibility_ids" int[] NULL DEFAULT '{6}',
      "bln_scratch" text NULL
    """
    typeLbl = 'bln'
    __tablename__ = 'baseline'
    bln_id = db.Column(db.Integer, nullable=False, primary_key=True)
    bln_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=False, default=356)
    bln_image_id = db.Column(db.Integer, db.ForeignKey(Image.img_id),
                             nullable=True)
    bln_surface_id = db.Column(db.Integer, db.ForeignKey(Surface.srf_id),
                               nullable=True)
    bln_image_position = db.Column(db.Text, nullable=True)
    bln_transcription = db.Column(db.Text, nullable=True)
    bln_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    bln_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    bln_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    bln_visibility_ids = db.Column(db.ARRAY(db.Integer), nullable=True,
                                   default=[6])
    bln_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.typeLbl + str(self.bln_id)


class SyllableCluster(Base):
    """
    Fields:
      "scl_id" serial NOT NULL PRIMARY KEY,
      "scl_segment_id" int NULL,
      "scl_grapheme_ids" int[] NULL,
      "scl_text_critical_mark" text NULL,
      "scl_sort_code" text NULL,
      "scl_sort_code2" text NULL,
      "scl_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "scl_owner_id" int NULL DEFAULT 2,
      "scl_annotation_ids" int[] NULL,
      "scl_visibility_ids" int[] NULL DEFAULT '{6}',
      "scl_scratch" text NULL
    """
    typeLbl = 'scl'
    __tablename__ = 'syllablecluster'
    scl_id = db.Column(db.Integer, nullable=False, primary_key=True)
    scl_segment_id = db.Column(db.Integer, nullable=True)
    scl_grapheme_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    scl_text_critical_mark = db.Column(db.Text, nullable=True)
    scl_sort_code = db.Column(db.Text, nullable=True)
    scl_sort_code2 = db.Column(db.Text, nullable=True)
    scl_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    scl_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    scl_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    scl_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    scl_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return 'scl' + str(self.scl_id)


class TextDoc(Base):
    """
    Fields:
      "txt_id" serial NOT NULL PRIMARY KEY,
      "txt_ckn" text NOT NULL DEFAULT 'Need CKN',
      "txt_title" text NULL,
      "txt_ref" text NULL,
      "txt_type_ids" int[] NULL,
      "txt_replacement_ids" int[] NULL,
      "txt_edition_ref_ids" int[] NULL,
      "txt_image_ids" int[] NULL,
      "txt_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "txt_jsoncache_id" int NULL,
      "txt_owner_id" int NULL DEFAULT 2,
      "txt_annotation_ids" int[] NULL,
      "txt_visibility_ids" int[] NULL DEFAULT '{6}',
      "txt_scratch" text NULL
    """
    typeLbl = 'txt'
    __tablename__ = 'text'
    txt_id = db.Column(db.Integer, nullable=False, primary_key=True)
    txt_ckn = db.Column(db.Text, nullable=False, default='needs idno')
    txt_title = db.Column(db.Text, nullable=True)
    txt_ref = db.Column(db.Text, nullable=True)
    txt_type_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    txt_replacement_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    txt_edition_ref_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    txt_image_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    txt_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    txt_jsoncache_id = db.Column(db.Integer, nullable=True)
    txt_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    txt_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    txt_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    txt_scratch = db.Column(db.Text, nullable=True)
    tmd_text = db.relationship('TextMetadata', backref='text')
    edn_text = db.relationship('Edition', backref='text')

    def __repr__(self):
        ''' function to display representation of object '''
        return self.txt_title or (self.typeLbl + str(self.txt_id))


class Edition(Base):
    """
    Fields:
      "edn_id" serial NOT NULL PRIMARY KEY,
      "edn_description" text NULL,
      "edn_sequence_ids" int[] NULL,
      "edn_text_id" int NULL,
      "edn_type_id" int NULL,
      "edn_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "edn_owner_id" int NULL DEFAULT 2,
      "edn_annotation_ids" int[] NULL,
      "edn_visibility_ids" int[] NULL DEFAULT '{6}',
      "edn_scratch" text NULL
    """
    typeLbl = 'edn'
    __tablename__ = 'edition'
    edn_id = db.Column(db.Integer, nullable=False, primary_key=True)
    edn_description = db.Column(db.Text, nullable=True)
    edn_sequence_ids = db.Column(db.Integer, nullable=True)
    edn_text_id = db.Column(
        db.Integer, db.ForeignKey(TextDoc.txt_id), nullable=True)
    edn_type_id = db.Column(db.Integer, db.ForeignKey('term.trm_id'),
                            nullable=True)
    edn_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    edn_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    edn_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    edn_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    edn_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.edn_description


class TextMetadata(Base):
    """
    Fields:
      "tmd_id" serial NOT NULL PRIMARY KEY,
      "tmd_text_id" int NULL,
      "tmd_type_ids" int[] NULL,
      "tmd_reference_ids" int[] NULL,
      "tmd_attribution_ids" int[] NULL,
      "modified" TIMESTAMP default CURRENT_TIMESTAMP,
      "tmd_owner_id" int NULL DEFAULT 2,
      "tmd_annotation_ids" int[] NULL,
      "tmd_visibility_ids" int[] NULL DEFAULT '{6}',
      "tmd_scratch" text NULL
    """
    typeLbl = 'tmd'
    __tablename__ = 'textmetadata'
    tmd_id = db.Column(db.Integer, nullable=False, primary_key=True)
    tmd_text_id = db.Column(db.Integer, db.ForeignKey(TextDoc.txt_id),
                            nullable=True)
    tmd_type_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    tmd_reference_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    tmd_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    tmd_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'),
                             nullable=False, default=3)
    tmd_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    tmd_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    tmd_scratch = db.Column(db.Text, nullable=True)

    def __str__(self):
        ''' function to get string representation of object '''
        return self.typeLbl + str(self.tmd_id)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.typeLbl + str(self.tmd_id)


class Token(Base):
    """
    Fields:
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
      "tok_annotation_ids" int[] NULL,
      "tok_visibility_ids" int[] NULL DEFAULT '{6}',
      "tok_scratch" text NULL
    """
    typeLbl = 'tok'
    __tablename__ = 'token'
    tok_id = db.Column(db.Integer, nullable=False, primary_key=True)
    tok_value = db.Column(db.Text, nullable=True)
    tok_transcription = db.Column(db.Text, nullable=True)
    tok_grapheme_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    tok_nom_affix = db.Column(db.Text, nullable=True)
    tok_sort_code = db.Column(db.Text, nullable=True)
    tok_sort_code2 = db.Column(db.Text, nullable=True)
    tok_attribution_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    modified = db.Column(db.TIMESTAMP(timezone=False),
                         nullable=False, default=datetime.now())
    tok_owner_id = db.Column(db.Integer, db.ForeignKey('usergroup.ugr_id'), nullable=False, default=3)
    tok_annotation_ids = db.Column(db.ARRAY(db.Integer), nullable=True)
    tok_visibility_ids = db.Column(
        db.ARRAY(db.Integer), nullable=True, default=[6])
    tok_scratch = db.Column(db.Text, nullable=True)

    def __repr__(self):
        ''' function to display representation of object '''
        return self.tok_value


def getScratchProp(entity, prop):
    colnameScratch = entity.typeLbl + '_scratch'
    scratch = getattr(entity, colnameScratch)
    if scratch is None:
        scratch = {}
    else:
        scratch = json.loads(scratch)
    if prop in scratch.keys():
        return scratch[prop]
    else:
        return None


def setScratchProp(sess, entity, prop, value):
    colnameScratch = entity.typeLbl + '_scratch'
    scratch = getattr(entity, colnameScratch)
    if scratch is None:
        scratch = {}
    else:
        scratch = json.loads(scratch)
    scratch[prop] = value
    scratchString = json.dumps(scratch)
    print(scratchString)
    colnameID = entity.typeLbl + '_id'
    entid = getattr(entity, colnameID)
    print(scratchString, colnameScratch, colnameID, entid)
    res = sess.query(type(entity)).filter(colnameID == entid).update(
        {colnameScratch: scratchString}, synchronize_session='fetch')
    print(res)
