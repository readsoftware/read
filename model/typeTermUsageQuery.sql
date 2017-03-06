SELECT c.trm_id,concat(c.trm_labels::hstore->'en','_',p.trm_labels::hstore->'en') AS label_parentlabel,c.trm_type_id,c.trm_code, c.trm_list_ids
FROM term c LEFT JOIN term p ON c.trm_parent_id = p.trm_id
WHERE c.trm_id IN (SELECT DISTINCT(ano_type_id) FROM annotation
                   UNION SELECT DISTINCT(atg_type_id) FROM attributiongroup
                   UNION SELECT DISTINCT(jsc_type_id) FROM jsoncache
                   UNION SELECT DISTINCT(img_type_id) FROM image
                   UNION SELECT DISTINCT(prn_type_id) FROM propernoun
--                   UNION SELECT DISTINCT(trm_type_id) FROM term
                   UNION SELECT DISTINCT(ugr_type_id) FROM usergroup
                   UNION SELECT DISTINCT(itm_type_id) FROM item
                   UNION SELECT DISTINCT(prt_type_id) FROM part
                   UNION SELECT DISTINCT(prt_shape_id) FROM part
                   UNION SELECT DISTINCT(prt_manufacture_id) FROM part
                   UNION SELECT DISTINCT(bln_type_id) FROM baseline
                   UNION SELECT DISTINCT(gra_type_id) FROM grapheme
                   UNION SELECT DISTINCT(lem_type_id) FROM lemma
                   UNION SELECT DISTINCT(lem_part_of_speech_id) FROM lemma
                   UNION SELECT DISTINCT(lem_subpart_of_speech_id) FROM lemma
                   UNION SELECT DISTINCT(lem_nominal_gender_id) FROM lemma
                   UNION SELECT DISTINCT(lem_verb_class_id) FROM lemma
                   UNION SELECT DISTINCT(lem_declension_id) FROM lemma
                   UNION SELECT DISTINCT(inf_case_id) FROM inflection
                   UNION SELECT DISTINCT(inf_nominal_gender_id) FROM inflection
                   UNION SELECT DISTINCT(inf_gram_number_id) FROM inflection
                   UNION SELECT DISTINCT(inf_verb_person_id) FROM inflection
                   UNION SELECT DISTINCT(inf_verb_voice_id) FROM inflection
                   UNION SELECT DISTINCT(inf_verb_tense_id) FROM inflection
                   UNION SELECT DISTINCT(inf_verb_mood_id) FROM inflection
                   UNION SELECT DISTINCT(inf_verb_second_conj_id) FROM inflection
                   UNION SELECT DISTINCT(cat_type_id) FROM catalog
                   UNION SELECT DISTINCT(cat_lang_id) FROM catalog
                   UNION SELECT DISTINCT(edn_type_id) FROM edition
                   UNION SELECT DISTINCT(seq_type_id) FROM sequence)
ORDER BY c.trm_id;
