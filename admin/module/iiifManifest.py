'''
@author      Stephen White  <stephenawhite57@gmail.com>
@copyright   Stephen White
@link        https://github.com/readsoftware
@version     1.0
@license     <http://www.gnu.org/licenses/>
@package     READ.Admin.IIIF

IIIF manifest abstraction
'''

import requests

class IIIFManifest:
    '''
        This class asbstracts json retrieved from the manifest url into an object
        to simplify access to manifest data
    '''
    
    def __init__(self, url = None, dictCache = None):
        self.dict = None
        self.url = None
        self.error = None
        self.metadata = None
        if url != None:
            self.url = url
            self.loadManifest()
            self.fromCache = False
        elif dictCache != None:
            self.dict = dictCache
            if 'metadata' in self.dict:
                self.metadata = {d['label']:d['value'].split('|') if '|' in d['value'] else d['value'] for d in self.dict['metadata']}
            self.fromCache = True

    def loadManifest(self, url = None):
        if not url == None:
            self.url = url
        if self.url != None:
            try:
                r = requests.get(self.url)
                #response = urllib.urlopen(self.url)
                self.dict = r.json()
                if 'metadata' in self.dict:
                    self.metadata = {d['label']:d['value'].split('|') if '|' in d['value'] else d['value'] for d in self.dict['metadata']}
                else:
                    print(f"no metadata key found from {self.url}")
            except requests.exceptions.RequestException as error:
                print(f"unable to get manifest from {url} error: {error}")
                self.url = None

    def getData(self):
        if self.dict:
            return self.dict
        return None

    def isCachedData(self):
        return self.fromCache

    def getMetadataNames(self):
        if self.metadata != None:
            return self.metadata.keys()
        return None

    def getMetadata(self, key = None):
        if self.metadata != None:
            if key == None:
                return self.metadata
            elif key in self.metadata.keys():
                return self.metadata[key]
            else:
                return None
        else:
            return None

    def getLinkParams(self):
        if self.metadata != None and 'links' in self.metadata.keys():
            linkurl = self.metadata['links']
            return {k:v for k,v in [p.split('=') for p in linkurl.split('?')[1].split('&')]}
        return None

    def getItemSiteName(self):
        params = self.getLinkParams()
        if params != None and 'site' in params.keys():
            return params['site']
        return None

    def getItemType(self):
        params = self.getLinkParams()
        if params != None and 'type' in params.keys():
            return params['type']
        return None

    def getUri(self):
        return self.url

    def getItemLabel(self):
        return self.dict['label']

    def getItemDescription(self):
        if 'description' in self.dict:
            if '@value' in self.dict['description']:
                return self.dict['description']['@value']
            else:
                return self.dict['description'][0]['@value']
        print("no description found in ", self.url)
        return None

    def getImageLicenseInfo(self):
        if "license" in self.dict.keys():
            return self.dict['license']
        else:
            return 'open'

    def getImageAttribution(self):
        return self.dict['attribution']

    def getImageCount(self):
        cnt = 0
        if 'sequences' in self.dict and len(self.dict['sequences']) > 0 and 'canvases' in self.dict['sequences'][0]:
            cnt = len(self.dict['sequences'][0]['canvases'])
        return cnt

    def getItemImages(self):
        images = []
        if self.getImageCount() > 0:
            for canvas in self.dict['sequences'][0]['canvases']:
                image = {}
                image['title'] = canvas['label']
                image['url'] = canvas['images'][0]['resource']['@id']
                image['url_id'] = canvas['images'][0]['resource']['service']['@id']
                images.append(image)
        return images
