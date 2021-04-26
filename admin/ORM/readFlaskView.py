from functools import partial
from sqlalchemy.orm import load_only
from flask_admin.contrib.sqla import ModelView
# from flask_admin.contrib.sqla.fields import CheckboxListField
from .readFlaskModel import UserGroup
import hashlib
# import json
# from shapely.geometry import Polygon


def getUserGroupList(columns=None):
    u = UserGroup.query
    if columns:
        u = u.options(load_only(*columns))
    print(u)
    return u


def getUserGroupListFactory(columns=None):
    return partial(getUserGroupList, columns=columns)


# subsequently when defining LoginForm
def addAllViews(admin, rfm, db):
    ''' adds all READ Views to the passed flask admin object '''
    admin.add_view(TermView(rfm.Term, db.session, category="System"))
    admin.add_view(UserGroupView(rfm.UserGroup, db.session, category="System"))
    admin.add_view(AnnotationView(rfm.Annotation, db.session,
                   category="Metadata"))
    admin.add_view(AttributionView(rfm.Attribution, db.session,
                   category="Metadata"))
    admin.add_view(AttributionGroupView(rfm.AttributionGroup, db.session,
                   category="Metadata"))
    admin.add_view(BaselineView(rfm.Baseline, db.session, category="Source"))
    admin.add_view(BibliographyView(rfm.Bibliography, db.session,
                   category="Metadata"))
    admin.add_view(CatalogView(rfm.Catalog, db.session, category="Collection"))
    admin.add_view(CompoundView(rfm.Compound, db.session, category="Core"))
    admin.add_view(CollectionView(rfm.Collection, db.session,
                   category="Collection"))
    admin.add_view(DateTemporalView(rfm.DateTemporal, db.session,
                   category="Metadata"))
    admin.add_view(EditionView(rfm.Edition, db.session, category="Core"))
    admin.add_view(EncodeView(rfm.Encode, db.session, category="System"))
    admin.add_view(EraView(rfm.Era, db.session, category="Metadata"))
    admin.add_view(FragmentView(rfm.Fragment, db.session, category="Physical"))
    admin.add_view(GraphemeView(rfm.Grapheme, db.session, category="Core"))
    admin.add_view(InflectionView(rfm.Inflection, db.session, category="Core"))
    admin.add_view(ImageView(rfm.Image, db.session, category="Source"))
    admin.add_view(ItemView(rfm.Item, db.session, category="Physical"))
    admin.add_view(LemmaView(rfm.Lemma, db.session, category="Core"))
    admin.add_view(LangsortView(rfm.Langsort, db.session, category="System"))
    admin.add_view(PartView(rfm.Part, db.session, category="Physical"))
    admin.add_view(ProperNounView(rfm.ProperNoun, db.session,
                   category="Metadata"))
    admin.add_view(SegmentView(rfm.Segment, db.session, category="Physical"))
    admin.add_view(SequenceView(rfm.Sequence, db.session, category="Core"))
    admin.add_view(SurfaceView(rfm.Surface, db.session, category="Physical"))
    admin.add_view(SyllableClusterView(rfm.SyllableCluster, db.session,
                   category="Core"))
    admin.add_view(TextDocView(rfm.TextDoc, db.session,
                   name='Text', category="Collection"))
    admin.add_view(TextMetadataView(rfm.TextMetadata, db.session,
                   category="Metadata"))
    admin.add_view(TokenView(rfm.Token, db.session, category="Core"))


class TermView(ModelView):
    column_exclude_list = ['modified', 'trm_scratch',
                           'bln_type', 'seq_type',
                           'cat_type', 'edn_type', 'trm_description']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('trm_id', 'trm_labels', 'trm_type_id', 'trm_code',
                      'trm_owner_id', 'trm_parent_id', 'trm_scratch')
    column_default_sort = 'trm_id'
    column_labels = dict(trm_id='ID',
                         trm_labels='Labels',
                         trm_parent_id='Parent Term ID',
                         trm_type_id='Term Type ID',
                         trm_list_ids='Term List',
                         trm_code='Code',
                         trm_description='Type',
                         trm_sort_code='Sort Weight',
                         trm_sort_code2='Secondary Sort',
                         trm_attribution_ids='Attribution Links',
                         owner='Term editor',
                         trm_annotation_ids='Annotation Links',
                         trm_visibility_ids='Visibility UGroups',
                         trm_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.trm_list_ids = [int(x) for x in model.trm_list_ids]
        model.trm_attribution_ids = [int(x)
                                     for x in model.trm_attribution_ids]
        model.trm_annotation_ids = [int(x)
                                    for x in model.trm_annotation_ids]
        model.trm_visibility_ids = [int(x)
                                    for x in model.trm_visibility_ids]


class UserGroupView(ModelView):
    column_exclude_list = ['modified', 'ugr_description']
    can_delete = False
    column_display_pk = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('ugr_id', 'ugr_name', 'ugr_family_name', 'ugr_scratch')
    column_default_sort = 'ugr_id'
    column_labels = dict(ugr_id='ID', ugr_name='Username',
                         ugr_given_name='First Name',
                         ugr_family_name='Last Name', ugr_password='Password',
                         ugr_description='Description', ugr_type_id='Group',
                         ugr_member_ids='Members', ugr_admin_ids='Admins',
                         ugr_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.ugr_password = hashlib.md5(
            bytes(model.ugr_password, 'utf-8')).hexdigest()
        model.ugr_member_ids = [int(x) for x in model.ugr_member_ids]
        model.ugr_admin_ids = [int(x) for x in model.ugr_admin_ids]


class AnnotationView(ModelView):
    column_exclude_list = ['modified', 'ano_annotation_ids', 'Scratch']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('ano_id', 'ano_text', 'ano_owner_id', 'ano_scratch')
    column_default_sort = 'ano_id'
    column_labels = dict(ano_id='ID',
                         ano_text='Text',
                         ano_url='URL',
                         ano_linkfrom_ids='From Links',
                         ano_linkto_ids='To Links',
                         ano_type_id='Type',
                         ano_attribution_ids='Attribution Links',
                         owner='Annotation Owner',
                         ano_annotation_ids='Annotation Links',
                         ano_visibility_ids='Visibility UGroups',
                         ano_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.ano_linkfrom_ids = [int(x) for x in model.ano_linkfrom_ids]
        model.ano_linkto_ids = [int(x) for x in model.ano_linkto_ids]
        model.ano_attribution_ids = [int(x)
                                     for x in model.ano_attribution_ids]
        model.ano_annotation_ids = [int(x)
                                    for x in model.ano_annotation_ids]
        model.ano_visibility_ids = [int(x)
                                    for x in model.ano_visibility_ids]


class AttributionView(ModelView):
    column_exclude_list = ['modified', 'atb_annotation_ids', 'Scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('atb_id', 'atb_title', 'atb_owner_id', 'atb_scratch')
    column_default_sort = 'atb_id'
    column_labels = dict(atb_id='ID', atb_title='Title', atb_types='Type List',
                         atb_bib_id='Biblio ID', atb_detail='Details',
                         atb_description='Description',
                         group='Attribution Group',
                         owner='Attribution Owner',
                         atb_annotation_ids='Annotation Links',
                         atb_visibility_ids='Visibility UGroups',
                         atb_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.atb_attribution_ids = [int(x)
                                     for x in model.atb_attribution_ids]
        model.atb_annotation_ids = [int(x)
                                    for x in model.atb_annotation_ids]
        model.atb_visibility_ids = [int(x)
                                    for x in model.atb_visibility_ids]


class AttributionGroupView(ModelView):
    column_exclude_list = ['modified',
                           'atg_date_created', 'atg_annotation_ids', 'Scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('atg_id', 'atg_name', 'atg_owner_id', 'atg_scratch')
    column_default_sort = 'atg_id'
    column_labels = dict(atg_id='ID',
                         atg_name='Name',
                         atg_type_id='Type ID',
                         atg_realname='Full Name',
                         atg_description='Description',
                         atg_member_ids='Member IDs',
                         atg_admin_ids='Admin IDs',
                         atg_attribution_ids='Attribution Links',
                         owner='Attribution Owner',
                         atg_annotation_ids='Annotation Links',
                         atg_visibility_ids='Visibility UGroups',
                         atg_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.atg_member_ids = [int(x) for x in model.atg_member_ids]
        model.atg_admin_ids = [int(x) for x in model.atg_admin_ids]
        model.atg_attribution_ids = [int(x)
                                     for x in model.atg_attribution_ids]
        model.atg_annotation_ids = [int(x)
                                    for x in model.atg_annotation_ids]
        model.atg_visibility_ids = [int(x)
                                    for x in model.atg_visibility_ids]


class BaselineView(ModelView):
    column_exclude_list = ['modified', 'bln_transcription',
                           'bln_annotation_ids', 'Scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    form_columns = ('bln_id', 'bln_type',
                    'bln_surface_id', 'bln_image_position',
                    'bln_attribution_ids', 'owner',
                    'bln_annotation_ids', 'bln_scratch',
                    'bln_visibility_ids', 'bln_image_id', )
    column_filters = ('bln_id', 'bln_type_id',
                      'owner.ugr_name', 'bln_scratch')
    column_sortable_list = ('bln_id', 'bln_type', 'owner')
    column_default_sort = 'bln_id'
    column_labels = dict(bln_id='ID',
                         bln_type_id='Type ID',
                         bln_type='Type',
                         bln_image_id='Image ID',
                         image='Image',
                         bln_surface_id='Surface ID',
                         surface="Surface",
                         bln_image_position='Bounds',
                         bln_transcription='Transcription',
                         bln_attribution_ids='Attribution Links',
                         owner='Baseline Editor',
                         bln_annotation_ids='Annotation Links',
                         bln_visibility_ids='User Visibility',
                         bln_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.bln_attribution_ids = [int(x)
                                     for x in model.bln_attribution_ids]
        model.bln_annotation_ids = [int(x)
                                    for x in model.bln_annotation_ids]
        model.bln_visibility_ids = [int(x)
                                    for x in model.bln_visibility_ids]


class BibliographyView(ModelView):
    column_exclude_list = ['modified', 'bib_annotation_ids', 'Scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('bib_id', 'bib_name', 'bib_owner_id', 'bib_scratch')
    column_default_sort = 'bib_id'
    column_labels = dict(bib_id='ID',
                         bib_name='Name',
                         bib_attribution_ids='Attribution Links',
                         owner='Bibliography Owner',
                         bib_annotation_ids='Annotation Links',
                         bib_visibility_ids='Visibility UGroups',
                         bib_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.bib_attribution_ids = [int(x)
                                     for x in model.bib_attribution_ids]
        model.bib_annotation_ids = [int(x)
                                    for x in model.bib_annotation_ids]
        model.bib_visibility_ids = [int(x)
                                    for x in model.bib_visibility_ids]


class CatalogView(ModelView):
    column_exclude_list = ['modified', 'cat_description',
                           'cat_edition_ids', 'cat_lang_id',
                           'cat_scratch', 'lem_catalog']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('cat_id', 'cat_title', 'cat_type_id',
                      'cat_owner_id', 'cat_scratch')
    column_default_sort = 'cat_id'
    column_labels = dict(cat_id='ID',
                         cat_title='Title',
                         cat_type_id='Catalog Type ID',
                         cat_type='Catalog Type',
                         cat_lang_id='Language ID',
                         cat_description='Description',
                         cat_edition_ids='Edition IDs',
                         cat_attribution_ids='Attribution Links',
                         owner='Catalog Editor',
                         cat_annotation_ids='Annotation Links',
                         cat_visibility_ids='Visibility UGroups',
                         cat_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.cat_edition_ids = [int(x)
                                 for x in model.cat_edition_ids]
        model.cat_attribution_ids = [int(x)
                                     for x in model.cat_attribution_ids]
        model.cat_annotation_ids = [int(x)
                                    for x in model.cat_annotation_ids]
        model.cat_visibility_ids = [int(x)
                                    for x in model.cat_visibility_ids]


class CollectionView(ModelView):
    column_exclude_list = ['modified', 'col_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('col_id', 'col_title', 'col_description',
                      'col_owner_id', 'col_scratch')
    column_default_sort = 'col_id'
    column_labels = dict(col_id='ID',
                         col_title='Title',
                         col_location_refs='Location History',
                         col_description='Description',
                         col_item_part_fragment_ids='Collected Artifact IDs',
                         col_exclude_part_fragment_ids='Exclude List',
                         col_attribution_ids='Image IDs',
                         owner='Collection Editor',
                         col_annotation_ids='Annotation Links',
                         col_visibility_ids='Visibility UGroups',
                         col_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.col_image_ids = [int(x) for x in model.col_image_ids]
        model.col_annotation_ids = [int(x)
                                    for x in model.col_annotation_ids]
        model.col_visibility_ids = [int(x)
                                    for x in model.col_visibility_ids]


class CompoundView(ModelView):
    column_exclude_list = ['modified', 'cmp_case_id', 'cmp_component_ids',
                           'cmp_sort_code2', 'cmp_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('cmp_id', 'cmp_value', 'cmp_owner_id', 'cmp_scratch')
    column_default_sort = 'cmp_id'
    column_labels = dict(cmp_id='ID',
                         cmp_value='Token',
                         cmp_transcription='Transcription',
                         cmp_component_ids='Components',
                         cmp_case_id='Case',
                         cmp_class_id='Class',
                         cmp_type_id='Type',
                         cmp_sort_code='Sort Weight',
                         cmp_sort_code2='Secondary Sort',
                         cmp_attribution_ids='Attribution Links',
                         owner='Compound Owner',
                         cmp_annotation_ids='Annotation Links',
                         cmp_visibility_ids='Visibility UGroups',
                         cmp_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.cmp_attribution_ids = [int(x)
                                     for x in model.cmp_attribution_ids]
        model.cmp_annotation_ids = [int(x)
                                    for x in model.cmp_annotation_ids]
        model.cmp_visibility_ids = [int(x)
                                    for x in model.cmp_visibility_ids]


class DateTemporalView(ModelView):
    column_exclude_list = ['modified', 'dat_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('dat_id', 'dat_prob_begin_date',
                      'dat_scratch', 'dat_prob_end_date')
    column_default_sort = 'dat_id'
    column_labels = dict(dat_id='ID',
                         dat_prob_begin_date='TAQ',
                         dat_prob_end_date='TPQ',
                         dat_entity_id='Linked ID',
                         dat_evidences='Evidences',
                         dat_preferred_era_id='Preferred Era',
                         dat_era_ids='Era Link IDs',
                         dat_attribution_ids='Attribution Links',
                         owner='Date Editor',
                         dat_annotation_ids='Annotation Links',
                         dat_visibility_ids='Visibility UGroups',
                         dat_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.dat_attribution_ids = [int(x)
                                     for x in model.dat_attribution_ids]
        model.dat_annotation_ids = [int(x)
                                    for x in model.dat_annotation_ids]
        model.dat_visibility_ids = [int(x)
                                    for x in model.dat_visibility_ids]


class EditionView(ModelView):
    column_exclude_list = ['modified', 'edn_replacement_ids',
                           'edn_edition_ref_ids', 'edn_image_ids',
                           'edn_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('edn_id', 'edn_description', 'edn_text_id',
                      'edn_owner_id', 'edn_scratch')
    column_default_sort = 'edn_id'
    column_labels = dict(edn_id='ID',
                         edn_description='Description',
                         edn_sequence_ids='Sequence IDs',
                         text='Text',
                         edn_type='Type',
                         edn_attribution_ids='Attribution Links',
                         owner='Edition Editor',
                         edn_annotation_ids='Annotation Links',
                         edn_visibility_ids='Visibility UGroups',
                         edn_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.edn_sequence_ids = [int(x)
                                  for x in model.edn_sequence_ids]
        model.edn_attribution_ids = [int(x)
                                     for x in model.edn_attribution_ids]
        model.edn_annotation_ids = [int(x)
                                    for x in model.edn_annotation_ids]
        model.edn_visibility_ids = [int(x)
                                    for x in model.edn_visibility_ids]


class EncodeView(ModelView):
    column_exclude_list = ['modified']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('enc_id', 'enc_langsort_id', 'enc_code',
                      'enc_type_id')
    column_default_sort = 'enc_id'
    column_labels = dict(enc_id='ID',
                         enc_langsort_id='Langsort',
                         enc_code='Code',
                         enc_type_id='Type ID',
                         enc_weight='Sort',
                         enc_attribution_ids='Attribution Links')

    def on_model_change(self, form, model, is_created):
        model.enc_attribution_ids = [int(x)
                                     for x in model.enc_attribution_ids]


class EraView(ModelView):
    column_exclude_list = ['modified', 'era_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('era_id', 'era_title', 'era_begin_date',
                      'era_end_date', 'era_order', 'era_scratch')
    column_default_sort = 'era_id'
    column_labels = dict(era_id='ID',
                         era_title='Name',
                         era_begin_date='Beginning',
                         era_end_date='Ending',
                         era_order='Ordinal',
                         era_preferred='Preferred Era',
                         era_attribution_ids='Attribution Links',
                         owner='Era Editor',
                         era_annotation_ids='Annotation Links',
                         era_visibility_ids='Visibility UGroups',
                         era_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.era_attribution_ids = [int(x)
                                     for x in model.era_attribution_ids]
        model.era_annotation_ids = [int(x)
                                    for x in model.era_annotation_ids]
        model.era_visibility_ids = [int(x)
                                    for x in model.era_visibility_ids]


class FragmentView(ModelView):
    column_exclude_list = ['modified', 'frg_label', 'frg_description',
                           'frg_location_refs', 'frg_part_id', 'owner',
                           'frg_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('frg_id', 'frg_label', 'frg_description',
                      'frg_part_id', 'frg_scratch')
    column_default_sort = 'frg_id'
    column_labels = dict(frg_id='ID',
                         frg_label='Label',
                         frg_description='Description',
                         frg_part_id='Part ID',
                         frg_measure='Measure',
                         frg_restore_state_id='Restoration State ID',
                         frg_location_refs='Location History',
                         surfaces='Fragment Surfaces',
                         frg_material_context_ids='Material Context IDs',
                         frg_image_ids='Image IDs',
                         frg_attribution_ids='Attribution Links',
                         owner='Fragment Editor',
                         frg_annotation_ids='Annotation Links',
                         frg_visibility_ids='Visibility UGroups',
                         frg_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.frg_image_ids = [int(x) for x in model.frg_image_ids]
        model.frg_material_context_ids = [int(x) for x in
                                          model.frg_material_context_ids]
        model.frg_attribution_ids = [int(x)
                                     for x in model.frg_attribution_ids]
        model.frg_annotation_ids = [int(x)
                                    for x in model.frg_annotation_ids]
        model.frg_visibility_ids = [int(x)
                                    for x in model.frg_visibility_ids]


class GraphemeView(ModelView):
    column_exclude_list = ['modified', 'gra_alt',
                           'gra_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('gra_id', 'gra_grapheme', 'gra_sort_code', 'owner', 'gra_scratch')
    column_default_sort = 'gra_id'
    column_labels = dict(gra_id='ID',
                         gra_grapheme='Grapheme',
                         gra_uppercase='Grapheme Uppercase',
                         gra_type_id='Type',
                         gra_text_critical_mark='TCM',
                         gra_alt='Alternate',
                         gra_emmendation='Emmendation',
                         gra_decomposition='Decomposition',
                         gra_sort_code='Sort Weight',
                         owner='Grapheme Editor',
                         gra_annotation_ids='Annotation Links',
                         gra_visibility_ids='Visibility UGroups',
                         gra_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.gra_annotation_ids = [int(x)
                                    for x in model.gra_annotation_ids]
        model.gra_visibility_ids = [int(x)
                                    for x in model.gra_visibility_ids]


class ImageView(ModelView):
    column_exclude_list = ['modified', 'img_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('img_id', 'owner', 'img_title',
                      'img_type_id', 'img_scratch')
    column_default_sort = 'img_id'
    column_labels = dict(img_id='ID',
                         img_title='Title',
                         img_type_id='Type ID',
                         img_type='Type',
                         img_url='URL',
                         img_image_pos='Image Boundary',
                         img_attribution_ids='Annotation Links',
                         owner='Image Editor',
                         img_annotation_ids='Annotation Links',
                         img_visibility_ids='Visibility UGroups',
                         img_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.img_attribution_ids = [int(x)
                                     for x in model.img_attribution_ids]
        model.img_annotation_ids = [int(x)
                                    for x in model.img_annotation_ids]
        model.img_visibility_ids = [int(x)
                                    for x in model.img_visibility_ids]


class InflectionView(ModelView):
    column_exclude_list = ['modified', 'inf_certainty',
                           'inf_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('inf_id', 'inf_chaya',
                      'inf_case_id', 'inf_nominal_gender_id',
                      'inf_gram_number_id', 'inf_verb_person_id',
                      'inf_verb_voice_id', 'inf_verb_tense_id',
                      'inf_verb_mood_id', 'inf_verb_second_conj_id',
                      'inf_owner_id', 'inf_scratch')
    column_default_sort = 'inf_id'
    column_labels = dict(inf_id='ID',
                         inf_chaya='Chaya',
                         inf_component_ids='Components',
                         inf_certainty='Certainty',
                         inf_case_id='Case ID',
                         inf_nominal_gender_id='Gender ID',
                         inf_gram_number_id='Number ID',
                         inf_verb_person_id='Verb Person ID',
                         inf_verb_voice_id='Verb Voice',
                         inf_verb_tense_id='Verb Tense',
                         inf_verb_mood_id='Verb Mood',
                         inf_verb_second_conj_id='Verb Conjugation',
                         inf_attribution_ids='Attribution Links',
                         owner='Inflection Editor',
                         inf_annotation_ids='Annotation Links',
                         inf_visibility_ids='Visibility UGroups',
                         inf_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.inf_attribution_ids = [int(x)
                                     for x in model.inf_attribution_ids]
        model.inf_annotation_ids = [int(x)
                                    for x in model.inf_annotation_ids]
        model.inf_visibility_ids = [int(x)
                                    for x in model.inf_visibility_ids]


class ItemView(ModelView):
    column_exclude_list = ['modified', 'itm_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('itm_id', 'itm_title', 'itm_description', 'itm_idno',
                      'itm_owner_id', 'itm_type_id', 'itm_scratch')
    column_default_sort = 'itm_id'
    column_labels = dict(itm_id='ID',
                         itm_title='Title',
                         itm_description='Description',
                         itm_idno='Item Ref. No.',
                         itm_type_id='Type ID',
                         itm_shape_id='Shape ID',
                         itm_measure='Measure',
                         itm_image_ids='Image IDs',
                         owner='Item Editor',
                         parts='Item Parts',
                         itm_annotation_ids='Annotation Links',
                         itm_visibility_ids='Visibility UGroups',
                         itm_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.itm_image_ids = [int(x) for x in model.itm_image_ids]
        model.itm_annotation_ids = [int(x)
                                    for x in model.itm_annotation_ids]
        model.itm_visibility_ids = [int(x)
                                    for x in model.itm_visibility_ids]


class LangsortView(ModelView):
    column_exclude_list = ['modified']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('srt_id', 'srt_iso_name', 'srt_name',
                      'srt_description')
    column_default_sort = 'srt_id'
    column_labels = dict(srt_id='ID',
                         srt_iso_name='ISO Code',
                         srt_name='Name',
                         srt_description='Description',
                         srt_lang_weight='Weight')


class LemmaView(ModelView):
    column_exclude_list = ['modified',
                           'lem_description', 'lem_declension_id',
                           'lem_verb_class_id', 'lem_nominal_gender_id',
                           'lem_sort_code2', 'lem_type_id',
                           'lem_subpart_of_speech_id', 'lem_certainty',
                           'lem_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('lem_id', 'lem_value', 'lem_translation',
                      'lem_homographorder', 'lem_description',
                      'lem_part_of_speech_id', 'lem_sort_code',
                      'lem_owner_id', 'lem_type_id', 'lem_scratch')
    column_default_sort = 'lem_id'
    column_labels = dict(lem_id='ID',
                         lem_value='Value',
                         lem_search='Search',
                         lem_translation='Translation',
                         lem_homographorder='Homographic Order',
                         lem_type_id='Type ID',
                         lem_certainty='Certainty',
                         lem_part_of_speech_id='POS ID',
                         lem_subpart_of_speech_id='SPOS ID',
                         lem_nominal_gender_id='Gender ID',
                         lem_verb_class_id='Class ID',
                         lem_declension_id='Declension ID',
                         lem_description='Description',
                         lem_catalog_id='Catalog ID',
                         lem_catalog='Catalog',
                         lem_component_ids='Components',
                         lem_sort_code='Primary Sort',
                         lem_sort_code2='Secondary Sort',
                         lem_attribution_ids='Attribution Links',
                         owner='Lemma Editor',
                         lem_annotation_ids='Annotation Links',
                         lem_visibility_ids='Visibility UGroups',
                         lem_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.lem_attribution_ids = [int(x)
                                     for x in model.lem_attribution_ids]
        model.lem_annotation_ids = [int(x)
                                    for x in model.lem_annotation_ids]
        model.lem_visibility_ids = [int(x)
                                    for x in model.lem_visibility_ids]


class PartView(ModelView):
    column_exclude_list = ['modified', 'prt_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('prt_id', 'prt_label', 'prt_description',
                      'prt_owner_id', 'prt_item_id',
                      'prt_type_id', 'prt_scratch')
    column_default_sort = 'prt_id'
    column_labels = dict(prt_id='ID',
                         prt_label='Label',
                         prt_description='Description',
                         prt_item_id='Item ID',
                         prt_type_id='Type ID',
                         prt_shape_id='Shape ID',
                         prt_mediums='Mediums',
                         prt_measure='Measure',
                         prt_manufacture_id='Manufacture ID',
                         prt_sequence='Sequence Ordinal',
                         fragments='Part Fragments',
                         prt_image_ids='Image IDs',
                         owner='Part Editor',
                         prt_annotation_ids='Annotation Links',
                         prt_visibility_ids='Visibility UGroups',
                         prt_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.prt_image_ids = [int(x) for x in model.prt_image_ids]
        model.prt_annotation_ids = [int(x)
                                    for x in model.prt_annotation_ids]
        model.prt_visibility_ids = [int(x)
                                    for x in model.prt_visibility_ids]


class ProperNounView(ModelView):
    column_exclude_list = ['modified', 'prn_scratch', 'prn_description']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('prn_id', 'prn_labels', 'prn_type_id',
                      'prn_owner_id', 'prn_parent_id',
                      'prn_description', 'prn_scratch')
    column_default_sort = 'prn_id'
    column_labels = dict(prn_id='ID',
                         prn_labels='Labels',
                         prn_type_id='Term Type ID',
                         prn_parent_id='Parent Term ID',
                         prn_evidences='Evidence',
                         prn_description='Description',
                         prn_url='URL',
                         prn_attribution_ids='Attribution Links',
                         owner='Propernoun Editor',
                         prn_annotation_ids='Annotation Links',
                         prn_visibility_ids='Visibility UGroups',
                         prn_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.prn_attribution_ids = [int(x)
                                     for x in model.prn_attribution_ids]
        model.prn_annotation_ids = [int(x)
                                    for x in model.prn_annotation_ids]
        model.prn_visibility_ids = [int(x)
                                    for x in model.prn_visibility_ids]


class SegmentView(ModelView):
    column_exclude_list = ['modified', 'seg_clarity_id',
                           'seg_layer', 'seg_obscurations',
                           'seg_mapped_seg_ids', 'seg_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('seg_id', 'seg_owner_id', 'seg_scratch')
    column_default_sort = 'seg_id'
    column_labels = dict(seg_id='ID',
                         seg_baseline_ids='Baseline Links',
                         seg_image_pos='Image Boundary',
                         seg_string_pos='String Range',
                         seg_rotation='Rotation',
                         seg_layer='Layer',
                         seg_clarity_id='Clarity',
                         seg_obscurations='Obscurities',
                         seg_url='URL',
                         seg_mapped_seg_ids='Mapped IDs',
                         seg_attribution_ids='Annotation Links',
                         owner='Segment Editor',
                         seg_annotation_ids='Annotation Links',
                         seg_visibility_ids='Visibility UGroups',
                         seg_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.seg_baseline_ids = [int(x)
                                  for x in model.seg_baseline_ids]
        model.seg_mapped_seg_ids = [int(x)
                                    for x in model.seg_mapped_seg_ids]
        model.seg_attribution_ids = [int(x)
                                     for x in model.seg_attribution_ids]
        model.seg_annotation_ids = [int(x)
                                    for x in model.seg_annotation_ids]
        model.seg_visibility_ids = [int(x)
                                    for x in model.seg_visibility_ids]


class SequenceView(ModelView):
    column_exclude_list = ['modified', 'seq_clarity_id',
                           'seq_layer', 'seq_obscurations',
                           'seq_entity_ids',
                           'seq_mapped_seq_ids', 'seq_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('seq_id', 'seq_owner_id',
                      'seq_ord', 'seq_type', 'seq_scratch')
    column_default_sort = 'seq_id'
    column_labels = dict(seq_id='ID',
                         seq_label='Label',
                         seq_type='Type',
                         seq_superscript='Superscript',
                         seq_entity_ids='Entity GID',
                         seq_theme_id='Theme ID',
                         seq_ord='Ordinal',
                         seq_attribution_ids='Annotation Links',
                         owner='Sequence Editor',
                         seq_annotation_ids='Annotation Links',
                         seq_visibility_ids='Visibility UGroups',
                         seq_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.seq_attribution_ids = [int(x)
                                     for x in model.seq_attribution_ids]
        model.seq_annotation_ids = [int(x)
                                    for x in model.seq_annotation_ids]
        model.seq_visibility_ids = [int(x)
                                    for x in model.seq_visibility_ids]


class SurfaceView(ModelView):
    column_exclude_list = ['modified', 'srf_edition_ref_ids', 'srf_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('srf_id', 'srf_label', 'srf_description',
                      'srf_scratch')
    column_default_sort = 'srf_id'
    column_labels = dict(srf_id='ID',
                         srf_description='Description',
                         srf_label='Label',
                         srf_fragment_id='Fragment ID',
                         srf_number='Ordinal',
                         srf_layer_number='Layer',
                         srf_scripts='Scripts',
                         srf_text_ids='Text IDs',
                         srf_reconst_surface_id='Reconstructed Surface ID',
                         fragment='Fragment',
#                         srf_image_ids='Image IDs',
                         images='Images',
                         srf_annotation_ids='Annotation Links',
                         srf_visibility_ids='Visibility UGroups',
                         srf_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.srf_text_ids = [int(x) for x in model.srf_text_ids]
        model.srf_image_ids = [int(x) for x in model.srf_image_ids]
        model.srf_annotation_ids = [int(x)
                                    for x in model.srf_annotation_ids]
        model.srf_visibility_ids = [int(x)
                                    for x in model.srf_visibility_ids]


class SyllableClusterView(ModelView):
    column_exclude_list = ['modified', 'scl_nom_affix',
                           'scl_grapheme_ids', 'scl_sort_code2', 'scl_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('scl_id', 'scl_sort_code', 'owner', 'scl_scratch')
    column_default_sort = 'scl_id'
    column_labels = dict(scl_id='ID',
                         scl_segment_id='Segment ID',
                         scl_grapheme_ids='Grapheme IDs',
                         scl_text_critical_mark='TCM',
                         scl_sort_code='Sort Weight',
                         scl_sort_code2='Secondary Sort',
                         scl_attribution_ids='Attribution Links',
                         owner='Syllable Editor',
                         scl_annotation_ids='Annotation Links',
                         scl_visibility_ids='Visibility UGroups',
                         scl_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.scl_grapheme_ids = [int(x) for x in model.scl_grapheme_ids]
        model.scl_attribution_ids = [int(x)
                                     for x in model.scl_attribution_ids]
        model.scl_annotation_ids = [int(x)
                                    for x in model.scl_annotation_ids]
        model.scl_visibility_ids = [int(x)
                                    for x in model.scl_visibility_ids]


class TextDocView(ModelView):
    column_exclude_list = ['modified', 'txt_replacement_ids',
                           'txt_edition_ref_ids', 'txt_image_ids',
                           'txt_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('txt_id', 'txt_ckn', 'txt_title', 'txt_ref',
                      'txt_owner_id', 'txt_scratch')
    column_default_sort = 'txt_id'
    column_labels = dict(txt_id='ID',
                         txt_ckn='Text ID No.',
                         txt_title='Title',
                         txt_ref='Short Ref',
                         txt_type_ids='Text Type IDs',
                         txt_replacement_ids='Replacement IDs',
                         txt_edition_ref_ids='Edition Ref IDs',
                         txt_image_ids='Image IDs',
                         images='Images',
                         txt_attribution_ids='Attribution Links',
                         txt_jsoncache_id='Cache ID',
                         owner='Text Editor',
                         txt_annotation_ids='Annotation Links',
                         txt_visibility_ids='Visibility UGroups',
                         txt_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.txt_type_ids = [int(x) for x in model.txt_type_ids]
        model.txt_replacement_ids = [int(x)
                                     for x in model.txt_replacement_ids]
        model.txt_edition_ref_ids = [int(x)
                                     for x in model.txt_edition_ref_ids]
        model.txt_image_ids = [int(x) for x in model.txt_image_ids]
        model.txt_attribution_ids = [int(x)
                                     for x in model.txt_attribution_ids]
        model.txt_annotation_ids = [int(x)
                                    for x in model.txt_annotation_ids]
        model.txt_visibility_ids = [int(x)
                                    for x in model.txt_visibility_ids]


class TextMetadataView(ModelView):
    column_exclude_list = ['modified', 'tmd_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('tmd_id', 'text', 'owner', 'tmd_scratch')
    column_default_sort = 'tmd_id'
    column_labels = dict(tmd_id='ID',
                         text='Text',
                         tmd_type_ids='Type IDs',
                         tmd_reference_ids='Reference IDs',
                         tmd_nom_affix='Nominal Affix',
                         tmd_sort_code='Sort Weight',
                         tmd_attribution_ids='Attribution Links',
                         owner='TextMetadata Editor',
                         tmd_annotation_ids='Annotation Links',
                         tmd_visibility_ids='Visibility UGroups',
                         tmd_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.tmd_type_ids = [int(x) for x in model.tmd_type_ids]
        model.tmd_reference_ids = [int(x)
                                   for x in model.tmd_reference_ids]
        model.tmd_attribution_ids = [int(x)
                                     for x in model.tmd_attribution_ids]
        model.tmd_annotation_ids = [int(x)
                                    for x in model.tmd_annotation_ids]
        model.tmd_visibility_ids = [int(x)
                                    for x in model.tmd_visibility_ids]


class TokenView(ModelView):
    column_exclude_list = ['modified', 'tok_nom_affix',
                           'tok_grapheme_ids', 'tok_sort_code2', 'tok_scratch']
    # action_disallowed_list = ['delete']
    can_delete = False
    can_create = False
    column_display_pk = True
    column_display_all_relations = True
    can_view_details = True
    edit_modal = True
    details_modal = True
    column_filters = ('tok_id', 'tok_value', 'tok_owner_id',
                      'owner', 'tok_scratch')
    column_default_sort = 'tok_id'
    column_labels = dict(tok_id='ID',
                         tok_value='Token',
                         tok_transcription='Transcription',
                         tok_grapheme_ids='Grapheme IDs',
                         tok_nom_affix='Nominal Affix',
                         tok_sort_code='Sort Weight',
                         tok_sort_code2='Secondary Sort',
                         tok_attribution_ids='Attribution Links',
                         owner='Token Editor',
                         tok_annotation_ids='Annotation Links',
                         tok_visibility_ids='Visibility UGroups',
                         tok_scratch='Scratch')

    def on_model_change(self, form, model, is_created):
        model.tok_grapheme_ids = [int(x) for x in model.tok_grapheme_ids]
        model.tok_attribution_ids = [int(x)
                                     for x in model.tok_attribution_ids]
        model.tok_annotation_ids = [int(x)
                                    for x in model.tok_annotation_ids]
        model.tok_visibility_ids = [int(x)
                                    for x in model.tok_visibility_ids]
