
'''
@author      Stephen White  <stephenawhite57@gmail.com>
@copyright   Stephen White
@link        https://github.com/readsoftware
@version     1.0
@license     <http://www.gnu.org/licenses/>
@package     READ.Admin.Module
READ link Maintenance Library
'''
import readQueryCursor as rqc

linkColTableLookupByPrefix = {
    'seq':['sequence','seq_entity_ids'],
    'cmp':['compound','cmp_component_ids'],
    'inf':['inflection','inf_component_ids'],
    'lem':['lemma','lem_component_ids'],
    'edn':['edition','edn_sequence_ids'],
    'tok':['token','tok_grapheme_ids'],
    'scl':['syllablecluster','scl_grapheme_ids'],
    'ano':['annotation','ano_linkto_ids'],
}

# TODO: create api for connect to db

def getChildLinksForContainer(containerGID):
    if containerGID == None:
        print(f'call to getChildLinkdsForContainer with invalid param {containerGID}')
        return []
    if containerGID.find(':') != 3:
        print(f'call to getChildLinkdsForContainer expected GID to have : {containerGID}')
        return []
    prefix,entID = containerGID.split(":")
    useGIDForm = False
    if not prefix in linkColTableLookupByPrefix.keys():
        print(f'call to getChildLinkdsForContainer unexpected prefix "{prefix}"')
        return []
    else:
        tblName, colName = linkColTableLookupByPrefix.get(prefix)
        myRQC.query(f"select {colName} from {tblName} where {prefix}_id = {entID};")
        return myRQC.getRow(0)[0]    

def adjustChildLinks(adjustmentList):
    if adjustmentList == None or len(adjustmentList) == 0:
        return ["NOP: invalid or empty list, nothing to do"]  
    log = ["Log run "]
    for fix in adjustmentList:
        if fix == None or len(fix) < 3:
            log.append("Found invalid command")
            continue
        containerGID = fix[0]
        containerLabel = fix[1]
        cmdLists = fix[2]
        if cmdLists == None or  len(cmdLists) == 0:
            log.append(f"Found invalid command lists for {containerGID} of {containerLabel}")
            continue
        log.append(f"********Processing commands for {containerGID} of {containerLabel}")
        log.append("command to check edition health, run before and after updates")
        log.append(f"http://localhost/readV1RC/dev/testEditionLinks.php?db=gandhari_staging&ednIDs={containerGID[3:]}")
        for cmdList in cmdLists:
            if cmdList == None or  len(cmdList) <3:
                log.append("Found invalid command list")
                continue
            trgGID = cmdList[0]
            cmd = cmdList[1]
            links = cmdList[2]
            log.append(f"Command for {trgGID} is {cmd} with links {str(links)}")
            childLinks = getChildLinksForContainer(trgGID)
            if cmd == 'remove':
                for link in links:
                    if link in childLinks:
                        childLinks.remove(link)
                    else: 
                        log.append(f"child link {link} not found in childlinks of {trgGID}.")
            # add code to ensure GID is correctly formed with :
            prefix,entID = trgGID.split(":")
            tblName, colName = linkColTableLookupByPrefix.get(prefix)
            log.append(f"UPDATE {tblName} set {colName} = '{{{str(childLinks).strip('[]')}}}' where {prefix}_id = {entID};")
    return log


'''
sample
adjustment_list = [['edn53','Edition for CKI0053.1',[['seq:966','remove',['cmp:110','tok:3564']]]],
['edn57','Edition for CKI0057.1',[['seq:1004','remove',['cmp:113','cmp:114']]]],
['edn61','Edition for CKI0061.1',[['seq:1046','remove',['cmp:123']]]],
['edn63','Edition for CKI0063.1',[['seq:1064','remove',['cmp:126']]]],
['edn88','Edition for CKI0088.1',[['seq:1220','remove',['cmp:127']]]],
['edn129','Edition for CKI0129.1',[['seq:1486','remove',['tok:4083']]]],
['edn154','Edition for CKI0154.1',[['seq:1725','remove',['cmp:161']]]],
['edn180','Edition for CKI0180.1',[['seq:1979','remove',['tok:4957']]]],
['edn185','Edition for CKI0185.1',[['seq:41700',[]]]],
['edn232','Edition for CKI0232.1',[['seq:2321','remove',['tok:5298']]]],
['edn564','Edition for CKI0564.1',[['seq:5112','remove',['tok:9010']]]],
['edn716','Edition for CKI0716.1',[['seq:6106','remove',['cmp:406']]]],
['edn1078','Edition for CKI1073.1',[['seq:8408','remove',['cmp:493']]]],
['edn1389','Edition for CKD0214.1',[['seq:14571','remove',['tok:25890']]]],
['edn1401','Edition for CKD0226.1',[['seq:14783','remove',['tok:26571']]]],
['edn1403','Edition for CKD0228.1',[['seq:14805','remove',['tok:26654']]]],
['edn1421','Edition for CKD0246.1',[['seq:15073','remove',['tok:27226','tok:27228']]]],
['edn1463','Edition for CKD0288.1',[['seq:15693','remove',['tok:29368']]]],
['edn1473','Edition for CKD0298.1',[['seq:15899','remove',['tok:30097','tok:30098','tok:30107']]]],
['edn1491','Edition for CKD0316.1',[['seq:16171','remove',['tok:30968']]]],
['edn1493','Edition for CKD0318.1',[['seq:16203','remove',['tok:31130']]]],
['edn1551','Edition for CKD0376.1',[['seq:17235','remove',['tok:35014','tok:35015','tok:35016']]]],
['edn1594','Edition for CKD0419.1',[['seq:17997','remove',['cmp:817']]]],
['edn1606','Edition for CKD0431.1',[['seq:18235','remove',['tok:38746']]]],
['edn1607','Edition for CKD0432.1',[['seq:18277','remove',['tok:38910']]]],
['edn1768','Edition for CKD0593.1',[['seq:22206','remove',['cmp:1013']]]],
['edn1836','Edition for CKD0661.1',[['seq:23696','remove',['cmp:1080']]]],
['edn1884','Edition for CKD0709.1',[['seq:24864','remove',['cmp:1099']]]],
['edn2059','Edition for CKD0884.1',[['seq:27766','remove',['tok:66002','tok:66007']]]],
['edn2072','Edition for CKM0001.1',[['seq:28020','remove',['tok:66674']],['seq:28362','remove',['tok:67275']],['seq:28436','remove',['tok:67405']]]],
['edn2088','Edition for CKM0014.1',[['seq:30156',[]]]],
['edn2093','Edition for CKM0018.1',[['seq:30647','remove',['tok:79600','tok:79601','tok:79602','tok:79603']]]],
['edn2336','Marino 2020',[['seq:42015','remove',['tok:116850','tok:116851','tok:116852','tok:116853','tok:116858','tok:116862','tok:116882','tok:116890']]]],
['edn2661','Edition for CKC0142.1',[['seq:40535','remove',['tok:113632']]]],
['edn2748','Edition for CKC0229.1',[['seq:41057','remove',['tok:113955']]]],
['edn2753','Edition for CKC0234.1',[['seq:41087','remove',['cmp:2334']]]],
['edn2767','Edition for CKC0248.1',[['seq:41171','remove',['cmp:2337']]]],
['edn2771','Edition for CKC0252.1',[['seq:41195','remove',['cmp:2338']]]],
['edn2780','Edition for CKC0261.1',[['seq:41249','remove',['tok:114058','tok:114060']]]],
['edn2784','Edition for CKC0265.1',[['seq:41273','remove',['tok:114073','tok:114075']]]],
['edn2820','Edition for CKC0301.1',[['seq:41489','remove',['tok:114229','tok:114230','tok:114231','tok:114232','tok:114233']]]],
['edn2840','Edition for CKC0321.1',[['seq:41609','remove',['tok:114308']]]],
['edn2845','Edition for CKC0326.1',[['seq:41639','remove',['tok:11432','tok:114328','tok:114329']]]],
['edn2846','Edition for CKC0327.1',[['seq:41645','remove',['tok:114330','tok:114331','tok:114332','tok:114335']]]],
['edn2855','Edition for CKI0185.1_Marshall',[['seq:41700',[]]]],
['edn2856','Edition for CKM0368.1',[['seq:41871',[]]]],
['edn2865','Falk 2021',[['seq:42041','remove',['tok:116971']]]]]
'''