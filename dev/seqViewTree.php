<?php
/**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this distribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can redistribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/

/**
* userUI
*
* creates a framework for the user interface including a layout manager and data manager
* it support opening with a parameter set that defines the layout and data needed to reproduce
* the exact view currently shown (perhaps minus any selection).
* when no parameters are passed it creates the default layout with no data and a single empty view.
*/

  require_once (dirname(__FILE__) . '/../common/php/sessionStartUp.php');//initialize the session
  require_once (dirname(__FILE__) . '/../common/php/DBManager.php');//get database interface
  require_once (dirname(__FILE__) . '/../common/php/userAccess.php');//get user access control
  require_once (dirname(__FILE__) . '/../model/entities/EntityFactory.php');//get user access control
//  $userID = 12;
  $dbMgr = new DBManager();
//  $ckn = (array_key_exists("ckn",$_REQUEST) ? $_REQUEST["ckn"]:"CKI02661,CKI02662,CKM0237");
  $ednID = (array_key_exists('ednID',$_REQUEST)? $_REQUEST['ednID']:null);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Lang" content="en">
    <title><?=defined("PROJECT_TITLE")?PROJECT_TITLE:"Set PROJECT_TITLE in config.php"?></title>
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.base.css" type="text/css" />
    <link rel="stylesheet" href="/jqwidget/jqwidgets/styles/jqx.energyblue.css" type="text/css" />
    <!--link rel="stylesheet" href="../common/css/kanishka.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/imageViewer.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/editionVE.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/wordlistVE.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/paleoVE.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/lemmaVE.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/searchVE.css" type="text/css" />
    <link rel="stylesheet" href="../editors/css/propertyVE.css" type="text/css" /-->
    <script src="/jquery/jquery-1.11.0.min.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcore.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtouch.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdata.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtabs.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownbutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttons.js"></script>
    <script src="/jqwidget/jqwidgets/jqxbuttongroup.js"></script>
    <script src="/jqwidget/jqwidgets/jqxradiobutton.js"></script>
    <script src="/jqwidget/jqwidgets/jqxscrollbar.js"></script>
    <script src="/jqwidget/jqwidgets/jqxexpander.js"></script>
    <script src="/jqwidget/jqwidgets/jqxnavigationbar.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdragdrop.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.pager.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.selection.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.filter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxgrid.sort.js"></script>
    <script src="/jqwidget/jqwidgets/jqxmenu.js"></script>
    <script src="/jqwidget/jqwidgets/jqxwindow.js"></script>
    <script src="/jqwidget/jqwidgets/jqxsplitter.js"></script>
    <script src="/jqwidget/jqwidgets/jqxdropdownlist.js"></script>
    <script src="/jqwidget/jqwidgets/jqxlistbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxinput.js"></script>
    <script src="/jqwidget/jqwidgets/jqxcheckbox.js"></script>
    <script src="/jqwidget/jqwidgets/jqxvalidator.js"></script>
    <script src="/jqwidget/jqwidgets/jqxpanel.js"></script>
    <script src="/jqwidget/jqwidgets/jqxtree.js"></script>
    <script type="text/javascript" src="../common/php/getEntityInfo.php?db=<?=DBNAME?>"></script>
    <script type="text/javascript" src="../services/getTagInfo.php?db=<?=DBNAME?>"></script>
    <script type="text/javascript">
      var sktSort = ('<?=USESKTSORT?>' == "0" || !'<?=USESKTSORT?>')?false:true,
          maxUploadSize = parseInt(<?=MAX_UPLOAD_SIZE?>),
          progressInputName='<?php echo ini_get("session.upload_progress.name"); ?>',
          dbName = '<?=DBNAME?>', basepath="<?=SITE_BASE_PATH?>";
    </script>
    <script src="../editors/js/utility.js"></script>
    <script src="../editors/js/debug.js"></script>
    <script src="../editors/js/dataManager.js"></script>
    <style type="text/css">
      .linkedtextgroup {
        display: none;
      }
      .linkedtextgroup.selected {
        display: block;
      }
    </style>

    <script type="text/javascript">
    var navPanelDiv, $contDiv, layoutManager, dataManager,
        cmpTypeID, tokTypeID, sclTypeID; //dependency on element type for leaf nodes


    function getTermListMenuItems(trmTag) {
      var trmTypeIDs, trmTypeID,i, items = [], trmID = trmTag.substring(3),
          itemLabel, itemValue, item, itemID;
      if (!dataManager || !dataManager.getTermListFromID) {
        return null;
      }
      trmTypeIDs = dataManager.getTermListFromID(trmID);
      if (trmTypeIDs && trmTypeIDs.length) {
        for (i=0; i<trmTypeIDs.length; i++) {
          trmTypeID = trmTypeIDs[i];
          itemLabel = dataManager.getTermFromID(trmTypeID);
          itemID = "trm" + trmTypeID;
          if (!itemLabel || trmTypeID == cmpTypeID || trmTypeID == tokTypeID || trmTypeID == sclTypeID) {
            continue;
          }
          item = { label: itemLabel,
                      id: itemID,
                   value: trmTypeID };
          items.push(item);
        }
      }
      return items;
    }

/**
.* getEditionStructuralItems
 *   create nested array hierarchy of items of the edition's structural elements
 * sample item list create is shown here. This code give a minimal form of this list for version 1
 * var source = [
 *    { label: "Item 1",
 *       html: '<div class="myItem1">Item 1</div>',
 *         id: 'seq55',
 *      value: 'seq55',
 *   expanded: true,
 *   selected: false,
 *      items: [
 *              { label: "Item 1.1" },
 *              { label: "Item 1.2", selected: true }
 *             ],
 *       icon: "../images/trm1395.png",
 *   iconsize: "16px"
 *    },
 *    { label: "Item 2" }
 * ];
 *    value - sets the item's value.
 *    html - item's html. The html to be displayed in the item.
 *    id - sets the item's id.
 *    disabled - sets whether the item is enabled/disabled.
 *    checked - sets whether the item is checked/unchecked(when tree/list checkboxes are enabled).
 *    expanded - sets whether the item is expanded or collapsed.
 *    selected - sets whether the item is selected.
 *    items - sets an array of sub items.
 *    icon - sets the item's icon(url is expected).
 *    iconsize - sets the size of the item's icon.
 *
 *
*/
  function getSubItems(entGIDs, cntSubLevels) {
    var seqGID, i, j, trmTypeTag, entType, prefix,
        typeID, sequence, item, id, entity,
        subItems, itemHTML, items = [],
        gid, tag, entTag;

    if (entGIDs.length){
      for (i=0; i<entGIDs.length; i++) {
        gid = entGIDs[i];
        tag = gid.replace(":","");
        prefix = gid.substring(0,3);
        id = gid.substring(4);
        itemHTML = "";
        entType = "";
        trmTypeTag = "";
        item = {};
        entity = dataManager.getEntity(prefix,id);
        if (!entity){//skip entity not loaded
//               DEBUG.log("warn","Found entity not loaded"+tag);
          continue;
        }
        item['id'] = tag;
        switch (prefix) {
          case "seq":
            entType = dataManager.getTermFromID(entity.typeID).toLowerCase();
            trmTypeTag = 'trm' + entity.typeID;
            break;
          case "cmp":
            entType = dataManager.getTermFromID(cmpTypeID).toLowerCase();
            trmTypeTag = 'trm' + cmpTypeID;
            break;
          case "tok":
            entType = dataManager.getTermFromID(tokTypeID).toLowerCase();
            trmTypeTag = 'trm' + tokTypeID;
            break;
          case "scl":
            entType = dataManager.getTermFromID(sclTypeID).toLowerCase();
            trmTypeTag = 'trm' + sclTypeID;
            break;
          default:
            if (entity.typeID) {
              entType = dataManager.getTermFromID(entity.typeID).toLowerCase();
              trmTypeTag = 'trm' + entity.typeID;
            }
        }
        item['value'] = trmTypeTag;
        itemHTML = '<div class="'+(entType?entType+' ':'')+tag+(trmTypeTag?' '+trmTypeTag:'')+'"'+
                       ' title="'+(entType?entType+' ':'')+tag+'" >'+
                       (entity.sup?entity.sup + (entity.label?" " +entity.label:""):
                                        (entity.label?entity.label:
                                          (entity.value?entity.value:
                                            (entType?entType+'('+tag+')':tag))))+'</div>';
        if (prefix == "seq" && entity.entityIDs && entity.entityIDs.length) {
          if (cntSubLevels) {
            subItems = getSubItems(entity.entityIDs,cntSubLevels-1);
          } else {
            subItems = [{label:"Loading...",id:tag+"children"}];
          }
        }
        if (subItems && subItems.length) {
          item['items'] = subItems;
        }
        if (itemHTML && itemHTML.length) {
          item['html'] = itemHTML;
        }
        items.push(item);
      } // end for entGIDs
    }
    if (items && items.length) {
      return items;
    }
    return null;
  }

  function getEditionStructuralItems(ednID) {
//    DEBUG.traceEntry("editionVE.renderEditionStructure","ednID = " + this.edition.id);
      var seqIDs, seqGIDs, edition;

    if (!dataManager || !dataManager.entities ||
        !dataManager.entities['edn'] || !dataManager.entities['edn'][ednID] ) {
      return null;
    }
    edition = dataManager.entities['edn'][ednID];
    seqIDs = edition.seqIDs;
    seqGIDs = $(seqIDs).map(function(index,str) {return "seq:"+str;})
    return getSubItems(seqGIDs,1);
  };

  function createContextMenu(e,item) {
    var scrollTop = $(window).scrollTop(),
        scrollLeft = $(window).scrollLeft(),
        $ctxMenu, menuItems, tag = item.id;

    $ctxMenu = $('.structPopupMenu');
    if ($ctxMenu.length) {
      $ctxMenu.jqxMenu('destroy');
    }
    menuItems = getTermListMenuItems(item.value);
    if (!item.hasItems) {//item has no childred so can be removed
      menuItems.push({ label: "Remove",
                          id: "Remove",
                       value: "Remove" });
    }
    if (menuItems && menuItems.length) {
      $ctxMenu = $('<div id="ctxMenu" class="structPopupMenu"/>');
      $ctxMenu.jqxMenu({ width: '120px',
                         source: menuItems,
                         autoOpenPopup: false,
                         mode: 'popup' });
      $ctxMenu.unbind('itemclick').bind('itemclick', function (e) {
          var itemMenuElement = e.args,
              menuLabel = $.trim($(itemMenuElement).text()),
              itemTypeTag, newStructTag,itemType,selectedItem;
          switch ($.trim($(itemMenuElement).text())) {
              case "Remove":
                  selectedItem = $('#structTree').jqxTree('selectedItem');
                  if (selectedItem != null) {
                      $('#structTree').jqxTree('removeItem', selectedItem.element);
                  }
                  break;
              default:
                  selectedItem = $('#structTree').jqxTree('selectedItem');
                  itemTypeTag = itemMenuElement.id,
                  newStructTag = 'seq'+Math.round(1000*Math.random());
                  itemType = dataManager.getTermFromID(itemTypeTag.substring(3));
                  if (selectedItem != null) {
                      $('#structTree').jqxTree('addTo', { id: newStructTag,
                                                       value: itemTypeTag,
                                                        html: '<div class="'+itemType.toLowerCase()+'" title="'+newStructTag+'">'+itemType+'</div' }, selectedItem.element);
                      attachMenuEventHandler();
                      $('#structTree').jqxTree('expandItem', selectedItem.element);
                  }
          }
      });
      $ctxMenu.on('closed', function (e) {
        $(this).jqxMenu('destroy');
      });
      $ctxMenu.jqxMenu('open', parseInt(e.clientX) + 5 + scrollLeft, parseInt(e.clientY) + 5 + scrollTop);
    }
  }

  function attachMenuEventHandler($elem) {
    // open the context menu when the user presses the mouse right button.
    $elem.find("li").unbind('mousedown').bind('mousedown', function (e) {
        var target = $(e.target).parents('li:first')[0];
        if ((e.which == 3 || e.button == 2) && target != null) {
          $("#structTree").jqxTree('selectItem', target);
          createContextMenu(e, $("#structTree").jqxTree('getItem', target));
          return false;
        } else if (target != null) {
          console.log("show structure class = " + $(e.args).attr('class'));
        }
    });
/*    $("#structTree li").unbind('mouseenter').bind('mouseenter', function (e) {
        var $linkedText;
        $linkedText = $('.linkedtextgroup:first',this);
        if ($linkedText && $linkedText.length) {
          $linkedText.addClass('selected');
        }
    });
    $("#structTree li").unbind('mouseleave').bind('mouseleave', function (e) {
        var $linkedText;
        $linkedText = $('.linkedtextgroup',this);
        if ($linkedText && $linkedText.length) {
          $linkedText.removeClass('selected');
        }
    });*/
  }

  function showStructureTree(ednID) {
    var $structTree =$('#structTree');
    $structTree.jqxTree({
          source: getEditionStructuralItems(ednID),
          enableHover: false,
          allowDrag: true,
          allowDrop: true,
          theme:'energyblue',
          dragStart: function (item) {
            if (
                dataManager.getTermFromID(item.value.substring(3)) == "Text"  ||
                dataManager.getTermFromID(item.value.substring(3)) == "TextDivision"  ||
                dataManager.getTermFromID(item.value.substring(3)) == "TextPhysical"  ||
                dataManager.getTermFromID(item.value.substring(3)) == "LinePhysical") {
                return false;
            }
          },
          dragEnd: function (item, dropItem, args, dropPosition, tree) {
            var fromParentTag = item.parentId, toParentTag = dropItem.parentId,
                message;
              if (!fromParentTag){
                fromParentTag = "edn"+ednID;
              }
              if (!toParentTag){
                toParentTag = "edn"+ednID;
              }
              if (fromParentTag == toParentTag && dropPosition != "inside") {
                message = "moving " + item.id + " to position " + dropPosition + " " +dropItem.id +" of "+toParentTag;
              } else {
                message = "removing " + item.id + " from "+fromParentTag+" to position " + dropPosition + " " +dropItem.id +" of "+toParentTag;
              }
              alert(message);
              return true;
          }
    });

    $structTree.css('visibility', 'visible');
    attachMenuEventHandler($structTree);
    $structTree.unbind("expand").bind("expand", function (e) {
      var elem = e.args.element,
        item = $structTree.jqxTree('getItem', elem),
        tag = item.id, subItems,
        entity = dataManager.getEntityFromGID(tag),
        loadElement;
      if (item.hasItems && item.nextItem.id == (item.id + "children")) {
        loadElement = item.nextItem.element;
      }
      if (loadElement) {
        if (entity.entityIDs && entity.entityIDs.length) {
          subItems = getSubItems(entity.entityIDs,0);
          if (subItems) {
            $structTree.jqxTree('addTo', subItems, elem);
            $structTree.jqxTree('removeItem', loadElement);
            attachMenuEventHandler($(elem));
          }
        }
      }
    });
    // disable the default browser's context menu.
    $(document).on('contextmenu', function (e) {
        if ($(e.target).parents('.jqx-tree').length > 0) {
            return false;
        }
        return true;
    });
  }

      $(document).ready( function () {
        if (typeof seqTypeInfo == "undefined") {
          seqTypeInfo = null;
        }
        if (typeof linkTypeInfo == "undefined") {
          linkTypeInfo = null;
        }
        if (typeof basepath == "undefined") {
          basepath = null;
        }
        if (typeof entityInfo == "undefined") {
          entityInfo = null;
        }
        $contDiv = $('#frameContentPanel');
        dataManager = new MANAGERS.DataManager({ dbname: dbName,
                                                 seqTypes: ((seqTypeInfo && seqTypeInfo['types']) ?seqTypeInfo['types']:""),
                                                 seqTypeTagToLabel: ((seqTypeInfo && seqTypeInfo['seqTypeTagToLabel'])?seqTypeInfo['seqTypeTagToLabel']:""),
                                                 seqTypeTagToList: ((seqTypeInfo && seqTypeInfo['seqTypeTagToList'])?seqTypeInfo['seqTypeTagToList']:""),
                                                 linkTypes: ((linkTypeInfo && linkTypeInfo['types']) ?linkTypeInfo['types']:""),
                                                 linkTypeTagToLabel: ((linkTypeInfo && linkTypeInfo['linkTypeTagToLabel'])?linkTypeInfo['linkTypeTagToLabel']:""),
                                                 linkTypeTagToList: ((linkTypeInfo && linkTypeInfo['linkTypeTagToList'])?linkTypeInfo['linkTypeTagToList']:""),
                                                 tags: ((typeof tagInfo == "undefined")?"":tagInfo),
                                                 entTagToLabel: ((typeof entTagToLabel == "undefined")?"":entTagToLabel),
                                                 entTagToPath: ((typeof entTagToPath == "undefined")?"":entTagToPath),
                                                 tagIDToAnoID: ((typeof tagIDToAnoID == "undefined")?"":tagIDToAnoID),
                                                 basepath: basepath,
                                                 entityInfo: entityInfo,
                                                 username: "<?= @$username?$username:"unknown"?>" });
        if (dataManager) {
          dataManager.loadTextSearch();
          dataManager.loadTextResources(function(){
                dataManager.loadEdition('<?=$ednID?>', function(){
                  cmpTypeID = dataManager.getIDFromTermParentTerm("compound","systementity");// warning!!! term dependency
                  tokTypeID = dataManager.getIDFromTermParentTerm("token","systementity");// warning!!! term dependency
                  sclTypeID = dataManager.getIDFromTermParentTerm("syllablecluster","systementity");// warning!!! term dependency
                  showStructureTree('<?=$ednID?>');
                });
          });
        }
      });
      </script>
  </head>
<body>
  <div id="frameContentPanel">
    <div id='structTree' style='visibility: hidden; float: left; margin-top: 20px; margin-left: 20px;'/>
  </div>
</body>
</html>
