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
  if(!defined("SITE_BASE_PATH")) define("SITE_BASE_PATH",REQSCHEMA."://".HOSTNAME.READ_DIR);
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
  if(!defined("USECACHE")) define("USECACHE",false);
  if(!defined("USEVIEWERCACHING")) define("USEVIEWERCACHING",true);
  if(!defined("DEFAULTCACHEOWNERID")) define("DEFAULTCACHEOWNERID",6);
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

  //configure viewer for site
  define("SHOWVIEWERCONTENTOUTLINE",false);
  define("SHOWIMAGEVIEW",true);
  define("USEPHYSICALVIEW",false);
  define("SHOWTRANSLATIONVIEW",true);
  define("SHOWCHAYAVIEW",false);
  define("FORMATENCODEDETYM",true);
  define("SHOWETYMPARENS",false);

  $info = new SplFileInfo(SEGMENT_CACHE_BASE_PATH);
  if (!$info->isDir()) {
    $isDir = mkdir($info, 0775, true);
    if (!$isDir) {//point at the temp dir which will only can temporarily
      uopz_redefine("SEGMENT_CACHE_BASE_PATH", sys_get_temp_dir()); //tod this has issues with multiple databases. Perhaps we place the db name on the file.
    }
  }
  $info = new SplFileInfo(XML_EXPORT_ROOT);
  if (!$info->isDir()) {
    $isDir = mkdir($info, 0775, true);
    if (!$isDir) {//point at the temp dir which will only can temporarily
      uopz_redefine("XML_EXPORT_ROOT", DOCUMENT_ROOT);
      uopz_redefine("EPIDOC_EXPORT_ROOT", DOCUMENT_ROOT."/epidoc");
    }
  }
?>
