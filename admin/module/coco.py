import module.readQueryCursor as RQC
import pandas as pd
import json
from PIL import Image, ImageFile
import requests
from io import BytesIO
import os

conf = RQC.readConnConfig
APP = os.environ.get('DB_SERVERNAME')
conf['host'] = os.environ.get('DB_SERVERNAME')
conf['user'] = os.environ.get('DB_USERNAME')
conf['password'] = os.environ.get('DB_PASSWORD')

print("APP: "+APP)
print("HOST: "+conf['host'])
print("USER: "+conf['user'])
print("PASS: "+conf['password'])

def getCOCO(db):
    conf['database'] = db
    myRQC = RQC.ReadQueryCursor(conf)
    print(conf)
    myRQC.query(
        '''
    WITH tags as (
        SELECT trim(both '"' from RIGHT(trm_labels, LENGTH(trm_labels) - 4)) AS trm_parent, trm_id AS trm_ids, trm_code as trm_codes, (SELECT count(c.trm_id) from term c WHERE c.trm_parent_id=p.trm_id) as counter
        FROM term p
        WHERE trm_parent_id=1387
    ),
    tagChildren as (
        SELECT trm_parent, trm_parent_id, trim(both '"' from RIGHT(trm_labels, LENGTH(trm_labels) - 4)) AS trm_child, trm_id as trm_child_id, trm_code, counter
        from tags
        join term
        on term.trm_parent_id = tags.trm_ids
    ),
    tagWithAndWithoutChildren as (
        SELECT *
        FROM tagChildren
        UNION (
            SELECT trm_parent, trm_ids as trm_parent_id, '0' as trm_child, trm_ids as trm_child_id, trm_codes as trm_code, counter
            FROM tags
            WHERE counter=0
        )
        ORDER BY trm_child_id
    )
    SELECT *
    FROM tagWithAndWithoutChildren
        ''')

    children = myRQC.getRowsAsIndexMultiDict(myRQC.getColumnNames().index('trm_parent'))

    myRQC.query(
        '''
    WITH segment_id AS (
        SELECT *, unnest(segment.seg_baseline_ids)
        AS seg_baseline_id
        FROM segment
    ),
    baselineQuery AS (
        SELECT bln_id, bln_image_id, img_id, img_title, img_url
        FROM baseline
        JOIN image
        ON baseline.bln_image_id=image.img_id
        WHERE baseline.bln_type_id=355
    ),
    segmentQuery AS (
        SELECT seg_id, seg_image_pos, bln_id, bln_image_id, img_id, img_title, img_url
        FROM segment_id
        JOIN baselineQuery
        ON segment_id.seg_baseline_id=baselineQuery.bln_id
    ),
    syllableQuery AS (
        SELECT scl_id, scl_segment_id, scl_grapheme_ids, seg_id, seg_image_pos, bln_id, bln_image_id, img_id, img_title, img_url
        FROM segmentQuery
        JOIN syllablecluster
        ON segmentQuery.seg_id=syllablecluster.scl_segment_id
    ),
    grapheme_id AS (
        SELECT *, unnest(syllableQuery.scl_grapheme_ids)
        AS scl_grapheme_id
        FROM syllableQuery
    ),
    graphemeQuery AS (
        SELECT *
        FROM grapheme_id
        JOIN grapheme
        ON grapheme_id.scl_grapheme_id=grapheme.gra_id
    ),
    -- until here was the query without tags
    tags as (
        SELECT trim(both '"' from RIGHT(trm_labels, LENGTH(trm_labels) - 4)) AS trm_parent, trm_id AS trm_ids, trm_code as trm_codes, (SELECT count(c.trm_id) from term c WHERE c.trm_parent_id=p.trm_id) as counter
        FROM term p
        WHERE trm_parent_id=1387
    ),
    tagChildren as (
        SELECT trm_parent, trm_parent_id, trim(both '"' from RIGHT(trm_labels, LENGTH(trm_labels) - 4)) AS trm_child, trm_id as trm_child_id, trm_code, counter
        from tags
        join term
        on term.trm_parent_id = tags.trm_ids
    ),
    terms as (
        SELECT *
        FROM tagChildren
        UNION (
            SELECT trm_parent, trm_ids as trm_parent_id, '0' as trm_child, trm_ids as trm_child_id, trm_codes as trm_code, counter
            FROM tags
            WHERE counter=0
        )
        ORDER BY trm_child_id
    ),
    resultt AS (
        SELECT ano_id, ano_linkto_ids, ano_type_id, trm_child_id, trm_parent_id, trm_code
        FROM annotation
        JOIN terms
        ON annotation.ano_type_id=terms.trm_child_id
    ),
    separatedSCL AS (
        SELECT ano_id, unnest(ano_linkto_ids) as ano_linkto_id, ano_type_id, trm_child_id, trm_parent_id, trm_code
        FROM resultt
        ORDER BY ano_linkto_id DESC
    ),
    separatedSCL2 AS (
        SELECT LTRIM(ano_linkto_id, 'scl:') as ano_linkto_id, string_agg(ano_id::character varying, ',') as ano_ids, string_agg(trm_child_id::character varying, ',') as trm_ids, string_agg(trm_code, ',') as trm_codes
        FROM separatedSCL
        GROUP BY 1
    ),
    separatedJoinSCL AS(
        SELECT *
        FROM separatedSCL2
        JOIN syllablecluster
        ON syllablecluster.scl_id=separatedSCL2.ano_linkto_id::integer
    ),
    segmentAnnotations AS(
        SELECT ano_linkto_id, ano_ids, trm_ids, trm_codes, scl_segment_id
        FROM separatedJoinSCL
    )
    SELECT *
    FROM segmentAnnotations
    JOIN graphemeQuery
    ON segmentAnnotations.ano_linkto_id::int=graphemeQuery.scl_id
        ''')

    print(myRQC.getResultCount())
    # return your first five rows
    # dataframe.head(30)

    result = myRQC.getRowsAsIndexMultiDict(myRQC.getColumnNames().index('img_url'))
    output = json.dumps(result, indent=0, sort_keys=True, default=str, ensure_ascii=False)
    res = json.loads(output)
    # print(res["http://localhost/images/homer2/txt1/P.Corn.Inv.MSS.A.101.XIII.jpg"])

    ''' Create JSON file'''
    initialization = {
        "licenses": [
            {
                "id": 1,
                "name": "Attribution-NonCommercial-ShareAlike License",
                "url": "http://creativecommons.org/licenses/by-nc-sa/2.0/"
            }
        ],
        "categories": [],
        "images": [],
        "annotations": []
    }
    # initialization = json.dumps(initialization, indent=0, sort_keys=True)

    headers = {
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36'
    }

    ''' Images array '''
    for row in res:
        # we replace 'localhost' with 'read' because the docker container can't access the localhost
        if APP != 'localhost':
            row1 = row.replace("localhost", "read")
            response = requests.get(row1, headers)
        else:
            response = requests.get(row, headers)

        if response.status_code == 200:
            img = Image.open(BytesIO(response.content))
            width, height = img.size
            # print(width,height)
        else:
            width, height = 0, 0
            # print(width,height)

        output = {
            "license": 1,
            "img_url": row,
            "height": height,
            "width": width,
            "id": res[row][0]['bln_id'],
            "bln_id": res[row][0]['bln_id'],
            "file_name": res[row][0]['img_title'] + ".jpg",
            "date_captured": None
        }

        initialization['images'].append(output)

    print("Finish images")

    ''' Categories array'''
    csv = pd.read_csv('./module/categories.csv')

    for index, row in csv.iterrows():
        output = {
            "id": row["id"],
            "name": row["name"],
            "supercategory": row["supercategory"]
        }
        initialization['categories'].append(output)

    print("Finish categories")

    ''' Annotations array '''
    index = 0

    # outfile = open("result.json")
    # data = json.load(outfile)

    def convert(string):
        li = list(string.split(","))
        return li

    for row in res.values():
        for row1 in row:
            # print(row1['trm_ids'])

            category = next(x for x in initialization['categories'] if x['name'] == row1['gra_grapheme'])

            position = row1['seg_image_pos'].replace('{"(', '')
            position = position.replace(')\"}', '')
            position = position.replace('),(', ',')
            position = position.replace('(', '')
            position = position.replace(')', '')
            position = convert(position)

            topleftX = position[0]
            topleftY = position[1]

            width = int(position[4]) - int(position[0])
            height = int(position[5]) - int(position[1])

            trm_list = row1['trm_ids'].split(",")
            # print(trm_list)
            a = {}  # tags are collected here
            for trm in trm_list:
                for row in children:
                    for value in children[row]:
                        if (int(trm) == value['trm_child_id']):
                            key = value['trm_parent']
                            if (value['counter'] == 0):
                                a.setdefault(key, 1)
                                # a[key].append(1)
                            else:
                                a.setdefault(key, [])
                                a[key].append(value['trm_code'])

            index = index + 1

            output = {
                "id": index,
                "seg_id": row1['seg_id'],
                "image_id": row1['bln_id'],
                "category_id": category['id'],
                "bbox": [
                    int(topleftX),
                    int(topleftY),
                    width,
                    height
                ],
                "area": width * height,
                "iscrowd": 1,
                # "tags":{
                #     "BaseType": [],
                #     "FootMarkType": [],
                #     "VowelType": []
                # }
                "tags": a
            }

            initialization['annotations'].append(output)

    initialization = json.dumps(initialization, indent=0, sort_keys=True)
    jsonFile = open("./coco.json", "w")
    jsonFile.write(initialization)
    jsonFile.close()
    print("Finished annotations")


def getFilteredCOCO(file="coco.json", listt=[]):
    with open(file) as m:
        # load json file
        data = json.load(m)
        # load again the json file as a temp structure to store the result
        output = json.load(m)
        # clear the 'annotation' list, so we can insert the filtered
        # annotation
        output['annotations'].clear()

        # iterate through all annotations
        for i in data['annotations']:
            # after each iteration assign an "exists" var as False, so
            # while checking if some filter from listt exists, you assign
            # it as true, so it breaks from the loop to not enter the same
            # annotation again if for any case, also the other filters
            # appear in the annotation
            exists = False
            # go through filters
            for x in listt:
                # assuming it is not the first iteration, if there was a match,
                # break from loop, check the next annotation
                if exists:
                    break
                else:
                    for type in i['tags']:
                        if not isinstance(i['tags'][type], list):
                            i['tags'][type] = [i['tags'][type]]
                        if x == i['tags'][type][0]:
                            output['annotations'].append(i)
                            exists = True
                        # break from loop if one is already satisfied
                        if exists:
                            break

        jsonFile = open("cocoFiltered.json", "w")
        out = json.dumps(output, indent=0, sort_keys=True)
        jsonFile.write(out)
        jsonFile.close()
        print("done")


def getFilteredCOCO_AND(file="coco.json", listt=[]):
    with open(file) as m:
        # load json file
        data = json.load(m)
        # load again the json file as a temp structure to store the result
        output = json.load(open(file))
        # clear the 'annotation' list, so we can insert the filtered
        # annotations
        output['annotations'].clear()
        # initiate a counter to check if all the filters from listt
        # are present on the annotation
        count = 0

        # iterate through all annotations
        for i in data['annotations']:
            # after each iteration reset the counter to go again through
            # the filters
            count = 0
            # go through filters
            for filter in listt:
                # get the type of tag from the coco format
                for type in i['tags']:
                    # if the type of the tag is not a list, convert it
                    if not isinstance(i['tags'][type], list):
                        i['tags'][type] = [str(i['tags'][type])]
                    # if the child of the tag equals the filter from the listt
                    if filter == i['tags'][type][0]:
                        # increase the counter by 1
                        count += 1
            # after iteration of every filter, if every filter has been present
            if count == len(listt):
                # append the annotation to an temp list
                output['annotations'].append(i)

        jsonFile = open("cocoFilteredAND.json", "w")
        out = json.dumps(output, indent=0, sort_keys=True)
        jsonFile.write(out)
        jsonFile.close()
        print("done")


def getFilteredCOCO_OR(file="coco.json", listt=[]):
    with open(file) as m:
        # load json file
        data = json.load(m)
        # load again the json file as a temp structure to store the result
        output = json.load(open(file))
        # clear the 'annotation' list, so we can insert the filtered
        # annotation
        output['annotations'].clear()

        # iterate through all annotations
        for i in data['annotations']:
            # after each iteration assign an "exists" var as False, so
            # while checking if some filter from listt exists, you assign
            # it as true, so it breaks from the loop to not enter the same
            # annotation again if for any case, also the other filters
            # appear in the annotation
            exists = False
            # go through filters
            for x in listt:
                # assuming it is not the first iteration, if there was a match,
                # break from loop, check the next annotation
                if exists:
                    break
                else:
                    for type in i['tags']:
                        if not isinstance(i['tags'][type], list):
                            i['tags'][type] = [i['tags'][type]]
                        if x == i['tags'][type][0]:
                            output['annotations'].append(i)
                            exists = True
                        # break from loop if one is already satisfied
                        if exists:
                            break

        jsonFile = open("cocoFilteredOR.json", "w")
        out = json.dumps(output, indent=0, sort_keys=True)
        jsonFile.write(out)
        jsonFile.close()
        print("done")
