import os, sys
import numpy as np
#add parent dir to module search path to find readQueryCursor module
sys.path.append(os.path.dirname(os.getcwd()))
import module.readQueryCursor as rqc

# static data members used for setting default connection and preferences for the library 
statReadConnConfig = rqc.readConnConfig.copy()
statReadUserConfig = rqc.readUserConfig.copy()

# static member functions for managing defaults
def setStatReadConnection(key = None, value = None):
  if key in statReadConnConfig.keys():
    statReadConnConfig[key] = value
  else:
    print(key,"is not a valid connection parameter")

def getOwnerID():
  return statReadUserConfig['editor']

def getVisibilityIDs():
  return statReadUserConfig['visibility']

class ReadStatisticsHelper:
  '''
    This class abstract a statistics package for a READ database corpus.
    It is designed to be connect to one database at a time and maintains the connection.
  '''
  def __init__(self, statRQC = None, statconf = statReadConnConfig, statUserPref = statReadUserConfig):
    if statRQC == None:
      self._RQC = rqc.ReadQueryCursor(conf=statconf, userPref=statUserPref)
    elif statRQC.isinstance(rqc.ReadQueryCursor):
      self._RQC = statRQC

  def getGraphemeCountsByText(self, txtinv):
    ret = {}
    myRQC = self._RQC
    textPhysSeqTermID = myRQC.getTermIDStrict('textphysical','sequencetype')
    linePhysSeqTermID = myRQC.getTermIDStrict('linephysical','textphysical')
    query = \
      "select gra_grapheme as grapheme, count(gra_id) as cnt "+\
      "from edition "+\
        "left join text on edn_text_id = txt_id "+\
        "left join sequence tp on tp.seq_id = ANY(edn_sequence_ids) "+\
        "left join sequence pl on concat('seq:',pl.seq_id) = ANY(tp.seq_entity_ids) "+\
        "left join syllablecluster on concat('scl:',scl_id) = ANY(pl.seq_entity_ids) "+\
        "left join grapheme on gra_id = scl_grapheme_ids[1] "+\
      "where edn_owner_id != 1 and tp.seq_owner_id != 1 "+\
      f"and txt_owner_id != 1 and txt_ckn = '{txtinv}' "+\
      f"and tp.seq_type_id = {textPhysSeqTermID} "+\
      f"and pl.seq_type_id = {linePhysSeqTermID} "+\
      "group by gra_grapheme order by gra_grapheme"
    myRQC.query(query)
    if myRQC.hasError():
      #error so output message
      print(f"Error grapheme counts from text {txtinv} from database: {myRQC.getError} ")
      return ret
    ret = myRQC.getRowsAsKVDict(kColumnName='grapheme', vColumnName='cnt')
    return ret

  def getGraphemeCountsByImage(self , anoTagID = None):
    ret = {}
    myRQC = self._RQC
    imageBaselineTermID = myRQC.getTermIDStrict('image','baselinetype')
    query = \
      "select img_title as image, gra_grapheme as grapheme, count(gra_id) as cnt "+\
      "from baseline "+\
        "left join image on img_id = bln_image_id "+\
        "left join segment on bln_id = ANY(seg_baseline_ids) "+\
        "left join syllablecluster on scl_segment_id = seg_id "+\
        "left join grapheme on gra_id = scl_grapheme_ids[1] "+\
      "where bln_owner_id != 1 and seg_owner_id != 1 "+\
        "and gra_grapheme is not null and img_title is not null "+\
      f"and bln_type_id = {imageBaselineTermID} "
    if anoTagID != None:
      query += "and scl_id in (select distinct(a.sclid::int) "+\
                "from (select  replace(unnest(ano_linkto_ids),'scl:','') as sclid "+\
                      f"from annotation where ano_type_id = {anoTagID}) a "+\
                "order by a.sclid::int ASC) "
    query += "group by img_title, gra_grapheme order by img_title, gra_grapheme"
    myRQC.query(query)
    if myRQC.hasError():
      #error so output message
      print(f"Error grapheme counts from baslines from database: {myRQC.getError} ")
      return ret
    ret = myRQC.getRowsAsIndexMultiKVDict(columnName='image', kColumnName='grapheme', vColumnName='cnt')
    return ret
