import os, sys
import pandas as pd

#add parent dir to module search path to find readQueryCursor module
sys.path.append(os.path.dirname(os.getcwd()))
import module.readQueryCursor as rqc

# static data members used for setting default connection and preferences for the library 
statReadConnConfig = rqc.readConnConfig.copy()
statReadUserConfig = rqc.readUserConfig.copy()
statOutputDir = "output" #default directory for outputting results from statistic queries
statDupFilenameLimit = 10

# static member functions for managing defaults
def setReadStatsConnParameter(key = None, value = None):
  if key in statReadConnConfig.keys():
    statReadConnConfig[key] = value
  else:
    print(key,"is not a valid connection parameter")

def getOwnerID():
  return statReadUserConfig['editor']

def getVisibilityIDs():
  return statReadUserConfig['visibility']

def getNextOutputFilename(path = "log", ext="txt", limit = statDupFilenameLimit):
  i = 0
  filepath = path + "." + ext
  while os.path.exists(filepath) and i < limit:
    i += 1
    filepath = path + f"({1})" + "." + ext
  return filepath

'''
## TODO:
- Create library with functions that select output type HTML, json, xslx, csv, ... and return as stream to browser or download as file.
- Add total column for multi column counts.
'''
class ReadStatisticsHelper:
  '''
    This class abstract a statistics package for a READ database corpus.
    It is designed to be connect to one database at a time and maintains the connection.
  '''
  def __init__(self, statRQC = None, statconf = statReadConnConfig,
               statUserPref = statReadUserConfig, dirOutput = statOutputDir):
    if statRQC == None:
      self._RQC = rqc.ReadQueryCursor(conf=statconf, userPref=statUserPref)
    elif isinstance(statRQC,rqc.ReadQueryCursor):
      self._RQC = statRQC
    if dirOutput == None:
      self._outputDir = "./"
    elif os.path.isdir(dirOutput):
      self._outputDir = dirOutput

  def saveDataFrame(self, statDataTable = None, extType = "csv", filename = "ReadStatData", outdir = None):
    '''
      helper function to output statistic DataFrame data to a file 
    '''
    outputFormats = ["csv","pkl","xml","json","xlsx"]
    if outdir == None:
      outdir = self._outputDir
    elif not os.path.isdir(outdir):
      outdir = os.path.curdir
    if outdir[-1] != os.path.sep:
      outdir += os.path.sep
    if not extType in outputFormats:
      print(f"extension {extType} is not a supported format")
      return False
    if not isinstance(statDataTable, pd.DataFrame):
      print(f"data must be a pandas Dataframe {type(statDataTable)} is not a supported")
      return False
    if statDataTable.empty:
      print(f"Dataframe is empty nothing to output, skipping request")
      return False
    outputPath = getNextOutputFilename(path = outdir + filename, ext = extType)
    if extType == "csv":
      statDataTable.to_csv(outputPath)
    elif extType == "pkl":
      statDataTable.to_pickle(outputPath)
    elif extType == "xml":
      statDataTable.to_xml(outputPath)
    elif extType == "json":
      statDataTable.to_json(outputPath)
    elif extType == "xlsx":
      statDataTable.to_excel(outputPath)
    return True

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
      query += "and scl_id in "+\
               "(select distinct(a.sclid::int) "+\
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
