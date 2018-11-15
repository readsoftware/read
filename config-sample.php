<?php
  if( array_key_exists('db',$_REQUEST) && !defined("DBNAME")) {
    define("DBNAME",$_REQUEST['db']);
  }
  if(!defined("PROJECT_TITLE")) define("PROJECT_TITLE","DEFAULT PROJECT TITLE");
  if(!defined("READ_DIR")) define("READ_DIR","/READ");
  if(!defined("DBNAME")) define("DBNAME","database");
  if(!defined("HOSTNAME")) define("HOSTNAME","localhost");
  if(!defined("REQSCHEMA")) define("REQSCHEMA",(@$_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:"http"));
  if(!defined("DOCUMENT_ROOT")) define("DOCUMENT_ROOT",$_SERVER['DOCUMENT_ROOT']);
  if(!defined("SITE_ROOT")) define("SITE_ROOT",REQSCHEMA."://".HOSTNAME);
  if(!defined("SITE_BASE_PATH")) define("SITE_BASE_PATH",SITE_ROOT.READ_DIR);
  if(!defined("READ_ROOT")) define("READ_ROOT",DOCUMENT_ROOT.READ_DIR);
  if(!defined("IMAGE_ROOT")) define("IMAGE_ROOT",DOCUMENT_ROOT."/images");
  if(!defined("EXPORT_ROOT")) define("EXPORT_ROOT",DOCUMENT_ROOT."/export");
  if(!defined("XML_EXPORT_ROOT")) define("XML_EXPORT_ROOT",EXPORT_ROOT."/xml");
  if(!defined("EPIDOC_EXPORT_ROOT")) define("EPIDOC_EXPORT_ROOT",XML_EXPORT_ROOT."/epidoc");
  if(!defined("SEGMENT_CACHE_SUB_PATH")) define("SEGMENT_CACHE_SUB_PATH","/segment_cache");
  if(!defined("THUMBNAIL_SUB_PATH")) define("THUMBNAIL_SUB_PATH","/thumb");
  if(!defined("SEGMENT_CACHE_BASE_PATH")) define("SEGMENT_CACHE_BASE_PATH",IMAGE_ROOT.SEGMENT_CACHE_SUB_PATH."/");
  if(!defined("IMAGE_SITE_BASE_URL")) define("IMAGE_SITE_BASE_URL",SITE_ROOT."/images");
  if(!defined("SEGMENT_CACHE_BASE_URL")) define("SEGMENT_CACHE_BASE_URL",IMAGE_SITE_BASE_URL.SEGMENT_CACHE_SUB_PATH);
  if(!defined("PORT")) define("PORT","5432");
  if(!defined("DBSERVERNAME")) define("DBSERVERNAME","localhost");
  if(!defined("USERNAME")) define("USERNAME","postgres");
  if(!defined("PASSWORD")) define("PASSWORD","password");
  if(!defined("DEFAULTVISIBILITY")) define("DEFAULTVISIBILITY","Users");
  //caching
  if(!defined("USECACHE")) define("USECACHE",false);
  if(!defined("USEVIEWERCACHING")) define("USEVIEWERCACHING",true);
  if(!defined("DEFAULTANNOTATIONSREFRESH")) define("DEFAULTANNOTATIONSREFRESH",0);//set >= 1 always refresh
  if(!defined("DEFAULTSEARCHREFRESH")) define("DEFAULTSEARCHREFRESH",0);
  if(!defined("DEFAULTCATALOGREFRESH")) define("DEFAULTCATALOGREFRESH",0);
  if(!defined("DEFAULTHTMLGLOSSARTREFRESH")) define("DEFAULTHTMLGLOSSARTREFRESH",0);
  if(!defined("DEFAULTEDITIONREFRESH")) define("DEFAULTEDITIONREFRESH",0);
  if(!defined("DEFAULTCACHEOWNERID")) define("DEFAULTCACHEOWNERID",4);
  if(!defined("DEFAULTCACHEVISID")) define("DEFAULTCACHEVISID",6);

  if(!defined("ENABLECATALOGRESOURCE")) define("ENABLECATALOGRESOURCE",'0');//0 = not enable else enabled
//  if(!defined("LINKSYLPATTERN")) define("LINKSYLPATTERN","L1:S1,L5+5:S1");
//  if(!defined("USESKTSORT")) define("USESKTSORT",'1');
//  if(!defined("READ_FILE_STORE")) define("READ_FILE_STORE",'\\xampp\\readfilestore');//simple script for managing db snapshot and restore
//  if(!defined("PSQL_PATH")) define("PSQL_PATH",'\\xampp\\PostgreSQL\\9.3\\bin');////simple script for managing db snapshot and restore
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

  //configure viewer defaults for site
  define("SHOWEXPORTBUTTON",false); // defaults to true so this is required to turn export off
  define("SHOWVIEWERCONTENTOUTLINE",false);
  define("SHOWIMAGEVIEW",true);
  define("USEPHYSICALVIEW",false);
  define("SHOWTRANSLATIONVIEW",true);
  define("SHOWCHAYAVIEW",false);
  define("EXPORTFULLGLOSSARY",false);
  define("ALLOWIMAGEDOWNLOAD",false);
  define("ALLOWTEIDOWNLOAD",true);
  define("USESCROLLLEMMAPOPUP",false);
  define("USELEMMAEXTRAINFO",true);
  define("FORMATENCODEDETYM",true);
  define("SHOWETYMPARENS",false);
  define("LEMMALINKTEMPLATE",READ_DIR."/plugins/dictionary/?search=%lemval%");

//location label formatting
  define("CKNMATCHREGEXP","'([a-z]+)0*(\\d+)'");//grp1 match starting non numeric characters followed by zero or more 0 grp2 match following numbers
  define("CKNREPLACEMENTEXP","'\\1\\2'");//replace is grp1 followed by grp2
  define("CKNREPLACEFLAGS","'i'");//ignore case during match
  define("CKNLINENUMSEPARATOR",":");//separate txt label from line label using a space
  define("SUBIMGTITLEFORPARTSIDELABEL",true);//allow image title to be used when not part side label is defined
  define("INCLUDEFRAGINPARTSIDELABEL",true);//include fragment label in the part side label is defined
  define("INLINEPARTSIDELABEL",true);//include fragment label in the part side label is defined

  if(!defined("VIEWER_EXPORT_SUBDIR")) define("VIEWER_EXPORT_SUBDIR","/readviewer");
// to export with separation of project databases use the line below. Also consider
// symbolic links to READ's viewer support subdirectories css and js in viewer export
// directory for automatic system update
//  if(!defined("VIEWER_EXPORT_SUBDIR")) define("VIEWER_EXPORT_SUBDIR","/readviewer/".DBNAME);
  if(!defined("VIEWER_EXPORT_PATH")) define("VIEWER_EXPORT_PATH",DOCUMENT_ROOT.VIEWER_EXPORT_SUBDIR);
  if(!defined("VIEWER_BASE_URL")) define("VIEWER_BASE_URL",SITE_ROOT.VIEWER_EXPORT_SUBDIR);

  // READ Workbench
  if(!defined("WORKBENCH_BASE_URL")) define("WORKBENCH_BASE_URL", REQSCHEMA . "://import-read-corpus.sydney.edu.au");

  $info = new SplFileInfo(SEGMENT_CACHE_BASE_PATH);
  if (!$info->isDir()) {
    $isDir = mkdir($info, 0775, true);
    if (!$isDir) {//point at the temp dir which will only store temporarily
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
