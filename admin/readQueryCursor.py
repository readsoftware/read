'''
@author      Stephen White  <stephenawhite57@gmail.com>
@copyright   Stephen White
@link        https://github.com/readsoftware
@version     1.0
@license     <http://www.gnu.org/licenses/>
@package     READ.Admin.QueryCursor

psycopg2  READ SQL QueryCursor
'''
import psycopg2


readConnConfig = {
             'host':'localhost',
             'port':'5432',
             'database':'testdb',
             'user': 'postgres',
             'password':'gandhari'
            }

readUserConfig = {
             'editor': 12,
             'visibility':[6],
             'attribution':None
            }

def setReadConnection(key = None, value = None):
    if key in readConnConfig.keys():
        readConnConfig[key] = value
    else:
        print(key,"is not a valid connection parameter")

def getOwnerID():
    return readUserConfig['editor']
        
def getVisibilityIDs():
    return readUserConfig['visibility']
        
class ReadQueryCursor:
    '''
        This class asbstracts CUD results from the READ Database in the form of an iteratable cursor
    '''
    def __init__(self, conf = readConnConfig, userPref = readUserConfig):
        self._conn = None
        self._cursor = None
        self._query = None
        self._error = None
        self._index = -1
        self._resultrows = None
        self._columnnames = None
        self._ownerid = userPref['editor']
        self._visibilityids = userPref['visibility']
        try:
            self._conn = psycopg2.connect(host = conf['host'],
                                        port = conf['port'],
                                        database = conf['database'], 
                                        user = conf['user'], 
                                        password = conf['password'])
            self._cursor = self._conn.cursor()
        except (Exception, psycopg2.Error) as error :
            self._error = "Error while connecting to PostgreSQL : " + error
        finally:
            if (self._cursor):
                print("connected to db",readConnConfig['database'])

    def __del(self):
      self.close()

    def __iter__(self):
        return self
    
    def __next__(self):
        #move pointer to next row
        if self._index == None:
            self._index = 0
        else:
            self._index += 1
        #check that we still have rows
        if self._resultrows and self._index < len(self._resultrows):
            #return next row
            return self._resultrows[self._index]
        else:
            raise StopIteration

    def getOwnerID(self):
        return self._ownerid
        
    def getVisibilityIDs(self):
        return self._visibilityids
        
    def reset(self):
        self._query = None
        self._error = None
        self._index = -1
        self._resultrows = None
        self._columnnames = None

    def query(self, query = None, params = None):
        if not query:
            if not self._query:
                self._error = None
                self._index = None
                self._resultrows = None
                self._columnnames = None
                return
        else:
            self._query = query
        # try to query and get all results
        try:
            self._error = None
            self._index = None
#            print(self._query)
            if params:
                self._cursor.execute(self._query, params)
                self._conn.commit()
            else:
                self._cursor.execute(self._query)
            self._columnnames = [desc[0] for desc in self._cursor.description]
#            print(self._columnnames)
            self._resultrows = self._cursor.fetchall()
#            print(len(self._resultrows))
        except (Exception, psycopg2.Error) as error :
            msg = "Error while querying : " + str(error)
            self._error = msg
            print(msg)
        
    def insert(self, tableName, kvData, pkey):
        """ insert a new row into the tableName table """
        sql = "INSERT INTO "
        sql += tableName 
        sql += "("
        sql += ",".join(kvData.keys())
        sql += ") VALUES("
        sql += ','.join(['%s' for v in list(kvData.values())])
        sql += ') RETURNING *'

        self.query(sql,list(kvData.values()))

    def update(self,tableName,kvData,kvPKey):
        """ update a row in the tableName table """
        sql = "UPDATE "
        sql += tableName 
        sql += " set "
        sql += ','.join([k+"='"+str(v)+"'" for k, v in kvData.items()])
        sql += ' where '
        sql += ','.join([k+"="+str(v) for k, v in kvPKey.items()])
        sql += ' RETURNING *'

        self.query(sql,list(kvData.values()))

    def getTermID(self,term, parentterm):
        """ query term table for trm_id """
        sql =  "SELECT c.trm_id "
        sql += "FROM term c LEFT JOIN term p on c.trm_parent_id = p.trm_id "
        sql += "WHERE c.trm_labels ilike '%" + term + "%' and p.trm_labels ilike '%"+parentterm+"%'"
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getColumnValue("trm_id")

    def getTermCode(self,trmID):
        """ query term table for trm_id term's code"""
        sql =  "SELECT trm_code FROM term WHERE trm_id = " + str(trmID)
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getColumnValue("trm_code")

    def getChildTerms(self,parentTrmID):
        """ query term table for child terms of parentTrmID term"""
        sql =  "SELECT trm_id as id, trm_labels::hstore->'en' as label, trm_code as code "
        sql += "FROM term WHERE trm_parent_id = " + str(parentTrmID)
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getAllResults()

    def getColumnNames(self):
        if self._query and self._columnnames:
            return self._columnnames
        else:
            return None

    def getAllResults(self):
        return self._resultrows
    
    def hasError(self):
        return self._error != None
    
    def getError(self):
        return self._error
    
    def clearError(self):
        self._error = None
    
    def getQuery(self):
        return self._query
    
    def setQuery(self, query = None):
        self._query = query
    
    def getResultCount(self):
        if not self._resultrows:
            return 0
        else:
            return len(self._resultrows)
        
    def seek(self,index = None):
        self._index = index
    
    def getRow(self,index = None):
        if index == None:
            if not self._resultrows or self._index == None or self._index >= len(self._resultrows):
                return None
            else:
                return self._resultrows[self._index]
        elif index > -1 and index < len(self._resultrows):
            return self._resultrows[index]
        return None
    
    def getColumnValue(self, column = None):
        if not column or self._index == None or self._index >= len(self._resultrows) or not self._resultrows or len(self._resultrows) == 0 or not column in self._columnnames:
            return None
        else:
            return self._resultrows[self._index][self._columnnames.index(column)]

    def close(self):
      self._conn.close()
      print("connection to READ closed")