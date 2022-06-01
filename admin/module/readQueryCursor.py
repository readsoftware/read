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
import os

dbAdminUsername = os.getenv('POSTGRES_USER')
dbAdminPassword = os.getenv('POSTGRES_PASSWORD')

if dbAdminUsername == None:
    dbAdminUsername = 'unknownName'
if dbAdminPassword == None:
    dbAdminPassword = 'unknownPassword'

readConnConfig = {
    'host': 'localhost',
    'port': '5432',
    'database': 'testdb',
    'user': dbAdminUsername,
    'password': dbAdminPassword
}

readUserConfig = {
    'editor': 12,
    'visibility': [6],
    'attribution': None
}


def setReadConnection(key=None, value=None):
    if key in readConnConfig.keys():
        readConnConfig[key] = value
    else:
        print(key, "is not a valid connection parameter")


def getOwnerID():
    return readUserConfig['editor']


def getVisibilityIDs():
    return readUserConfig['visibility']


class ReadQueryCursor:
    '''
        This class asbstracts CUD results from the READ Database in the form of an iteratable cursor
    '''

    def __init__(self, conf=readConnConfig, userPref=readUserConfig):
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
            self._conn = psycopg2.connect(host=conf['host'],
                                          port=conf['port'],
                                          database=conf['database'],
                                          user=conf['user'],
                                          password=conf['password'])
            self._cursor = self._conn.cursor()
        except (Exception, psycopg2.Error) as error:
            self._error = "Error while connecting to PostgreSQL : " + str(error)
        finally:
            if (self._cursor):
                print("connected to db", conf['database'])

    def __del__(self):
        self.close()

    def __iter__(self):
        return self

    def __next__(self):
        # move pointer to next row
        if self._index == None:
            self._index = -1
        elif self._index < len(self._resultrows):
            self._index += 1
        # check that we still have rows
        if self._resultrows and self._index < len(self._resultrows):
            # return next row
            return self._resultrows[self._index]
        else:
            if self._index == 0:
                self._index = None
            raise StopIteration

    def getOwnerID(self):
        return self._ownerid

    def getVisibilityIDs(self):
        return self._visibilityids

    def reset(self):
        self._query = None
        self._error = None
        self._index = None
        self._resultrows = None
        self._columnnames = None

    def restart(self):
        self._index = -1

    def query(self, query=None, params=None):
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
            # print(self._query)
            if params:
                self._cursor.execute(self._query, params)
                self._conn.commit()
            else:
                self._cursor.execute(self._query)
            self._columnnames = [desc[0] for desc in self._cursor.description]
            # print(self._columnnames)
            self._resultrows = self._cursor.fetchall()
        #            print(len(self._resultrows))
        except (Exception, psycopg2.Error) as error:
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
        sql += ','.join([str(v) for v in list(kvData.values())])
        sql += ') RETURNING *'

        self.query(sql, list(kvData.values()))

    def update(self, tableName, kvData, kvPKey):
        """ update a row in the tableName table """
        sql = "UPDATE "
        sql += tableName
        sql += " set "
        sql += ','.join([k + "='" + str(v) + "'" for k, v in kvData.items()])
        sql += ' where '
        sql += ' and '.join([k + "=" + str(v) for k, v in kvPKey.items()])
        sql += ' RETURNING *'

        self.query(sql, list(kvData.values()))

    def getTermID(self, term, parentterm):
        """ query term table for trm_id """
        sql = "SELECT c.trm_id "
        sql += "FROM term c LEFT JOIN term p on c.trm_parent_id = p.trm_id "
        sql += "WHERE c.trm_labels ilike '%" + term + "%' and p.trm_labels ilike '%" + parentterm + "%'"
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getColumnValue("trm_id")

    def getTermFromID(self, trmID, langCode='en'):
        """ query term table for trm_id """
        sql = f"SELECT c.trm_labels::hstore->'{langCode}' as label "
        sql += "FROM term "
        sql += f"WHERE trm_id = {trmID}"
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getColumnValue("label")

    def getTermIDStrict(self, term, parentterm, langCode='en'):
        """ query term table for trm_id """
        sql = "SELECT c.trm_id "
        sql += "FROM term c LEFT JOIN term p on c.trm_parent_id = p.trm_id "
        sql += f"WHERE c.trm_labels::hstore->'{langCode}' ilike '" + term + "' and p.trm_labels::hstore->'en' ilike '" + parentterm + "'"
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getColumnValue("trm_id")

    def getTermCode(self, trmID):
        """ query term table for trm_id term's code"""
        sql = "SELECT trm_code FROM term WHERE trm_id = " + str(trmID)
        self.query(sql)
        if self.hasError():
            return None
        else:
            self.seek(0)
            return self.getColumnValue("trm_code")

    def getChildTerms(self, parentTrmID):
        """ query term table for child terms of parentTrmID term"""
        sql = "SELECT trm_id as id, trm_labels::hstore->'en' as label, trm_code as code "
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

    def setQuery(self, query=None):
        self._query = query

    def getResultCount(self):
        if not self._resultrows:
            return 0
        else:
            return len(self._resultrows)

    def seek(self, index=None):
        self._index = index

    def getIndex(self):
        if self._index == None and len(self._resultrows):
            self._index = 0
        return self._index

    def getRow(self, index=None):
        if index == None:
            if not self._resultrows or self._index == None and len(self._resultrows) == 0 \
                    or self._index != None and self._index >= len(self._resultrows):
                return None
            else:
                if self._index == None and len(self._resultrows):
                    self._index = 0
                index = self._index
        if index > -1 and index < len(self._resultrows):
            return self._resultrows[index]
        return None

    def getRowAsDict(self, index=None):
        if index == None:
            if not self._resultrows or self._index == None and len(self._resultrows) == 0 \
                    or self._index != None and self._index >= len(self._resultrows):
                return None
            else:
                if self._index == None and len(self._resultrows):
                    self._index = 0
                index = self._index
        if index > -1 and index < len(self._resultrows):
            return {colName: self.getColumnValue(colName) for colName in self.getColumnNames()}
        return None

    def getRowsAsKVDict(self, kColIndex=None, vColIndex=None, kColumnName=None, vColumnName=None):
        if self._resultrows == None or self._index == None and len(self._resultrows) == 0:
            return None
        if kColIndex != None and (kColIndex >= len(self.getColumnNames()) or kColIndex < 0):
            return None
        if kColIndex == None and kColumnName != None and kColumnName in self._columnnames:
            kColIndex = self._columnnames.index(kColumnName)
        if vColIndex != None and (vColIndex >= len(self.getColumnNames()) or vColIndex < 0):
            return None
        if vColIndex == None and vColumnName != None and vColumnName in self._columnnames:
            vColIndex = self._columnnames.index(vColumnName)
        self.restart()
        return {row[kColIndex]: row[vColIndex] for row in self}

    def getRowsAsIndexMultiKVDict(self, colIndex=None, columnName=None, kColIndex=None, vColIndex=None,
                                  kColumnName=None, vColumnName=None):
        if self._resultrows == None or self._index == None and len(self._resultrows) == 0:
            return None
        if kColIndex != None and (kColIndex >= len(self.getColumnNames()) or kColIndex < 0):
            return None
        if kColIndex == None and kColumnName != None and kColumnName in self._columnnames:
            kColIndex = self._columnnames.index(kColumnName)
        if vColIndex != None and (vColIndex >= len(self.getColumnNames()) or vColIndex < 0):
            return None
        if vColIndex == None and vColumnName != None and vColumnName in self._columnnames:
            vColIndex = self._columnnames.index(vColumnName)
        if colIndex != None and (colIndex >= len(self.getColumnNames()) or colIndex < 0):
            return None
        if columnName != None and columnName in self._columnnames:
            colIndex = self._columnnames.index(columnName)
        indexDict = {}
        self.restart()
        for row in self:
            if row[colIndex] == None:
                continue
            indexValue = row[colIndex]
            entKey = self.getColumnValue(self._columnnames[kColIndex])
            entValue = self.getColumnValue(self._columnnames[vColIndex])
            if not indexValue in indexDict:
                indexDict[indexValue] = {entKey: entValue}
            else:
                indexDict[indexValue][entKey] = entValue
        return indexDict

    def getRowsAsIndexDict(self, colIndex=0, columnName=None):
        if self._resultrows == None or self._index == None and len(self._resultrows) == 0:
            return None
        if colIndex and (colIndex >= len(self.getColumnNames()) or colIndex < 0):
            colIndex = 0
        if columnName != None and columnName in self._columnnames:
            colIndex = self._columnnames.index(columnName)
        self.restart()
        return {row[colIndex]: {colName: self.getColumnValue(colName) for colName in self._columnnames} for row in self}

    def getRowsAsIndexMultiDict(self, colIndex=0, columnName=None):
        if self._resultrows == None or self._index == None and len(self._resultrows) == 0:
            return None
        if colIndex and (colIndex >= len(self.getColumnNames()) or colIndex < 0):
            colIndex = 0
        if columnName != None and columnName in self._columnnames:
            colIndex = self._columnnames.index(columnName)
        indexDict = {}
        self.restart()
        for row in self:
            entry = {colName: self.getColumnValue(colName) for colName in self._columnnames}
            if row[colIndex] == None:
                continue
            indexValue = row[colIndex]
            # remove TCM markup TODO: expand API to include list of characters to strip
            if type(indexValue) == str:
                indexValue = indexValue.rstrip('*').rstrip('?')
            if not indexValue in indexDict:
                indexDict[indexValue] = []
            indexDict[indexValue].append(entry)
        return indexDict

    def getColumnValue(self, columnName=None):
        if not columnName \
                or self._index == None and len(self._resultrows) == 0 \
                or self._index != None and self._index >= len(self._resultrows) \
                or self._resultrows == None or len(self._resultrows) == 0 \
                or not columnName in self._columnnames:
            return None
        else:
            if self._index == None and len(self._resultrows) > 0:
                self._index = 0
            return self._resultrows[self._index][self._columnnames.index(columnName)]

    def close(self):
        self._conn.close()
        print("connection to READ closed")
