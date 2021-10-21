<?php
  if( array_key_exists('db',$_REQUEST) && !defined("DBNAME")) {
    define("DBNAME",$_REQUEST['db']);
  }
  if(!defined("PROJECT_TITLE")) define("PROJECT_TITLE","Gāndhārī");
  if(!defined("READ_DIR")) define("READ_DIR","/maindev");
  if(!defined("DBNAME")) define("DBNAME","gandhari_staging");
  if(!defined("HOSTNAME")) define("HOSTNAME","localhost");
  if(!defined("REQSCHEMA")) define("REQSCHEMA",(@$_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:"http"));
  if(!defined("DOCUMENT_ROOT")) define("DOCUMENT_ROOT",$_SERVER['DOCUMENT_ROOT']);
  if(!defined("SITE_ROOT")) define("SITE_ROOT",REQSCHEMA."://".HOSTNAME);
  if(!defined("SITE_BASE_PATH")) define("SITE_BASE_PATH","http://".HOSTNAME."/maindev");
  if(!defined("READ_ROOT")) define("READ_ROOT",DOCUMENT_ROOT.READ_DIR);
  if(!defined("IMAGE_ROOT")) define("IMAGE_ROOT",DOCUMENT_ROOT."/images");
  if(!defined("EXPORT_ROOT")) define("EXPORT_ROOT",DOCUMENT_ROOT."/export");
  if(!defined("XML_EXPORT_ROOT")) define("XML_EXPORT_ROOT",EXPORT_ROOT."/xml");
  if(!defined("EPIDOC_EXPORT_ROOT")) define("EPIDOC_EXPORT_ROOT",XML_EXPORT_ROOT."/epidoc");
  if(!defined("THUMBNAIL_SUB_PATH")) define("THUMBNAIL_SUB_PATH","/thumb");
  if(!defined("SEGMENT_CACHE_SUB_PATH")) define("SEGMENT_CACHE_SUB_PATH","/segment_cache");
  if(!defined("SEGMENT_CACHE_BASE_PATH")) define("SEGMENT_CACHE_BASE_PATH",IMAGE_ROOT.SEGMENT_CACHE_SUB_PATH."/");
  if(!defined("IMAGE_SITE_BASE_URL")) define("IMAGE_SITE_BASE_URL",SITE_ROOT."/images");
  if(!defined("SEGMENT_CACHE_BASE_URL")) define("SEGMENT_CACHE_BASE_URL",IMAGE_SITE_BASE_URL.SEGMENT_CACHE_SUB_PATH);
  if(!defined("PORT")) define("PORT","5432");
  //if(!defined("DBSERVERNAME")) define("DBSERVERNAME","localhost");
  if(!defined("DBSERVERNAME")) define("DBSERVERNAME","db");
  if(!defined("USERNAME")) define("USERNAME","postgres");
  if(!defined("PASSWORD")) define("PASSWORD","gandhari");
  if(!defined("DEFAULTVISIBILITY")) define("DEFAULTVISIBILITY","Users");

  if(!defined("SETENVCMD")) define("SETENVCMD",'export'); //for windows bash use 'set'
  if(!defined("CMDSEPARATOR")) define("CMDSEPARATOR",';');//for windows bash use '&'

  //caching
  if(!defined("USECACHE")) define("USECACHE",true);
  if(!defined("USEVIEWERCACHING")) define("USEVIEWERCACHING",true);
  if(!defined("USESEGMENTCACHING")) define("USESEGMENTCACHING",true);
  if(!defined("USEDYNAMICLEMMAINFO")) define("USEDYNAMICLEMMAINFO",true);
  if(!defined("DEFAULTANNOTATIONSREFRESH")) define("DEFAULTANNOTATIONSREFRESH",0);//set >= 1 always refresh
  if(!defined("DEFAULTSEARCHREFRESH")) define("DEFAULTSEARCHREFRESH",0);
  if(!defined("DEFAULTCATALOGREFRESH")) define("DEFAULTCATALOGREFRESH",0);
  if(!defined("DEFAULTHTMLGLOSSARTREFRESH")) define("DEFAULTHTMLGLOSSARTREFRESH",0);
  if(!defined("DEFAULTEDITIONREFRESH")) define("DEFAULTEDITIONREFRESH",0);
  if(!defined("DEFAULTCACHEOWNERID")) define("DEFAULTCACHEOWNERID",4);
  if(!defined("DEFAULTCACHEVISID")) define("DEFAULTCACHEVISID",6);

  if(!defined("ENABLECATALOGRESOURCE")) define("ENABLECATALOGRESOURCE",'1');//0 = not enable else enabled
//  if(!defined("LINKSYLPATTERN")) define("LINKSYLPATTERN","L1:S1,L5+5:S1");
  if(!defined("USESKTSORT")) define("USESKTSORT",'0');
  //if(!defined("READ_FILE_STORE")) define("READ_FILE_STORE",'\\READ\\readfilestore');
  if(!defined("READ_FILE_STORE")) define("READ_FILE_STORE",'/var/www/readfilestore');
  //if(!defined("PSQL_PATH")) define("PSQL_PATH",'\\READ\\software\\PostgreSQL\\bin');
  if(!defined("PSQL_PATH")) define("PSQL_PATH",'');
  if(!defined("MAX_UPLOAD_SIZE")) {
    $maxUpload = intval(ini_get("upload_max_filesize"));
    $maxPost = intval(ini_get("post_max_size"));
    define("MAX_UPLOAD_SIZE",!ini_get("file_uploads")?'0': ''.min(array($maxPost,$maxUpload)));
  }
  define("CROP_IMAGE_SERVICE_PATH",SITE_BASE_PATH."/common/php/cropImagePoly.php");

  //configure viewer/editors for site
  define("SHOWLEMMAPHONETIC",0);
  define("SHOWLEMMAPHONOLOGY",0);
  define("SHOWLEMMADECLENSION",0);
  define("DECLENSIONLIST",'OIADeclension');
  define('EDITCOLLISIONDETECTION',1);
  define("EDITTOOLSOPENONSTART",1);
  define("VIEWTOOLSOPENONSTART",1);
  define("LAYOUTTOOLSOPENONSTART",1);
  define("TOOLSIDEBAROPENONSTART",1);

  //configure viewer defaults for site
  define("SHOWEXPORTBUTTON",false); // defaults to true so this is required to turn export off
  define("SHOWVIEWERCONTENTOUTLINE",false);
  define("SHOWIMAGEVIEW",true);
  define("USEPHYSICALVIEW",true);
  define("SHOWTRANSLATIONVIEW",true);
  define("SHOWCHAYAVIEW",false);
  define("EXPORTFULLGLOSSARY",false);
  define("ALLOWIMAGEDOWNLOAD",false);
  define("ALLOWTEIDOWNLOAD",false);
  define("USESCROLLLEMMAPOPUP",false);
  define("USELEMMAEXTRAINFO",true);
  define("FORMATENCODEDETYM",true);
  define("SHOWETYMPARENS",false);
  define("LEMMALINKTEMPLATE",READ_DIR."/plugins/dictionary/?search=%lemval%");
  define("FOOTNOTEMARKER",'‡');
  
  //location label formatting
  define("CKNMATCHREGEXP","'(CK[CDIM]+)0*(\\d+)'");//SQL grp1 match starting non numeric characters followed by zero or more 0 grp2 match following numbers
  define("CKNREPLACEMENTEXP","'\\1\\2'");//SQL replace is grp1 followed by grp2
  define("CKNREPLACEFLAGS","'i'");//SQL ignore case during match
  define("CKNLINENUMSEPARATOR",":");//separate txt label from line label using a space
  define("DEFAULTTOVERSELABEL", false);//when true system uses token verse-pāda location if available 

  //viewer title formatting
  define("INVMATCHREGEXP","/(CK[CDIM])0*(\\d+)(\.\\d+)?/i");//grp1 match starting non numeric characters followed by zero or more 0 grp2 match following numbers
  define("INVREPLACEMENTEXP","\\1 \\2");//replace is grp1 followed by grp2
  define("VIEWERINVTITLESEP"," — ");//separate txt label from line label using a space

  // part-fragment-side labeling control
  define("SUBIMGTITLEFORPARTSIDELABEL",true);//allow image title to be used when not part side label is defined
  define("INCLUDEFRAGINPARTSIDELABEL",true);//include fragment label in the part side label is defined
  define("INLINEPARTSIDELABEL",true);//include fragment label in the part side label is defined
  define("PARTFRAGSEPARATOR"," ");//separate part label from fragment label using this string
  define("FRAGSIDESEPARATOR"," ");//separate fragment label from side label using this string

  if(!defined("READVIEWER_CSS_PATH")) define("READVIEWER_CSS_PATH","../plugins/dev/css/readviewer.css"); // must be relative to getTextViewer.php directory
  if(!defined("VIEWER_EXPORT_SUBDIR")) define("VIEWER_EXPORT_SUBDIR","/readviewer");
// to export with separation of project databases use the line below. Also consider
// symbolic links to READ's viewer support subdirectories css and js in viewer export
// directory for automatic system update
//  if(!defined("VIEWER_EXPORT_SUBDIR")) define("VIEWER_EXPORT_SUBDIR","/readviewer/".DBNAME);
  if(!defined("VIEWER_EXPORT_PATH")) define("VIEWER_EXPORT_PATH",DOCUMENT_ROOT.VIEWER_EXPORT_SUBDIR);
  if(!defined("VIEWER_BASE_URL")) define("VIEWER_BASE_URL",SITE_ROOT.VIEWER_EXPORT_SUBDIR);

  // READ Workbench
  // if(!defined("WORKBENCH_BASE_URL")) define("WORKBENCH_BASE_URL", REQSCHEMA . "://import-read-corpus.sydney.edu.au");
  if(!defined("SETENVCMD")) define("SETENVCMD",'export'); //for windows bash use 'set'
  // String character used in the terminal to separate commands
  if(!defined("CMDSEPARATOR")) define("CMDSEPARATOR",'&');//for windows bash use '&'
  // String representing the path to the directory used for readfilestore snapshot/restore directory
  //if(!defined("READ_FILE_STORE")) define("READ_FILE_STORE",'\\xampp\\readfilestore');//simple script for managing db snapshot and restore
  // String of path to psql.exe command line executable for database management
  if(!defined("PSQL_PATH")) define("PSQL_PATH",'\\READ\\software\\PostgreSQL\\9.3\\bin');////simple script for managing db snapshot and restore


  $info = new SplFileInfo(SEGMENT_CACHE_BASE_PATH);
  if (!$info->isDir()) {
    $isDir = mkdir($info, 0775, true);
    if (!$isDir) {//point at the temp dir which will only can temporarily
      uopz_redefine("SEGMENT_CACHE_BASE_PATH", sys_get_temp_dir()); //todo this has issues with multiple databases. Perhaps we place the db name on the file.
    }
  }
  $info = new SplFileInfo(XML_EXPORT_ROOT);
  if (!$info->isDir()) {
    $isDir = mkdir($info, 0775, true);
    if (!$isDir) {//point at the temp dir which will only store temporarily
      uopz_redefine("XML_EXPORT_ROOT", DOCUMENT_ROOT);
      uopz_redefine("EPIDOC_EXPORT_ROOT", DOCUMENT_ROOT."/epidoc");
    }
  }
?>
