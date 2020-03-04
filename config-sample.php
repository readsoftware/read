<?php
// check for the name of the database in the request
if( array_key_exists('db',$_REQUEST) && !defined("DBNAME")) {
    define("DBNAME",$_REQUEST['db']);
  }
  // set the html title used in this installation
  if(!defined("PROJECT_TITLE")) define("PROJECT_TITLE","DEFAULT PROJECT TITLE");
  // the sub path from the document root directory where READ is located
  if(!defined("READ_DIR")) define("READ_DIR","/READ");
  // the default database name used if not given in the request URL
  if(!defined("DBNAME")) define("DBNAME","database");
  // the name or IP used to create READ URLs
  if(!defined("HOSTNAME")) define("HOSTNAME","localhost");
  // the protocol used in creating READ URLs
  if(!defined("REQSCHEMA")) define("REQSCHEMA",(@$_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:"http"));
  // the document root under which READ is located
  if(!defined("DOCUMENT_ROOT")) define("DOCUMENT_ROOT",$_SERVER['DOCUMENT_ROOT']);
  // the site root URL
  if(!defined("SITE_ROOT")) define("SITE_ROOT",REQSCHEMA."://".HOSTNAME);
  //  the READ site base path for calculation READ URLs - for READ services
  if(!defined("SITE_BASE_PATH")) define("SITE_BASE_PATH",SITE_ROOT.READ_DIR);
  // the path where READ is located
  if(!defined("READ_ROOT")) define("READ_ROOT",DOCUMENT_ROOT.READ_DIR);
  // the path wehre READ images are located
  if(!defined("IMAGE_ROOT")) define("IMAGE_ROOT",DOCUMENT_ROOT."/images");
  // the path where READ should export to
  if(!defined("EXPORT_ROOT")) define("EXPORT_ROOT",DOCUMENT_ROOT."/export");
  // the path where READ should export generated XML
  if(!defined("XML_EXPORT_ROOT")) define("XML_EXPORT_ROOT",EXPORT_ROOT."/xml");
  // the path where READ should export generated epidoc
  if(!defined("EPIDOC_EXPORT_ROOT")) define("EPIDOC_EXPORT_ROOT",XML_EXPORT_ROOT."/epidoc");
  // the subpath from where READ should cache clipped image of every segment
  if(!defined("SEGMENT_CACHE_SUB_PATH")) define("SEGMENT_CACHE_SUB_PATH","/segment_cache");
  // deprecated ********** stored at image path
  if(!defined("THUMBNAIL_SUB_PATH")) define("THUMBNAIL_SUB_PATH","/thumb");
  // the path where READ should cache clips of each image segment
  if(!defined("SEGMENT_CACHE_BASE_PATH")) define("SEGMENT_CACHE_BASE_PATH",IMAGE_ROOT.SEGMENT_CACHE_SUB_PATH."/");
  // the base URL used to create image URLs
  if(!defined("IMAGE_SITE_BASE_URL")) define("IMAGE_SITE_BASE_URL",SITE_ROOT."/images");
  // the base URL for cached segments used to create URLs to access clipped segment images 
  if(!defined("SEGMENT_CACHE_BASE_URL")) define("SEGMENT_CACHE_BASE_URL",IMAGE_SITE_BASE_URL.SEGMENT_CACHE_SUB_PATH);
  // database connection port
  if(!defined("PORT")) define("PORT","5432");
  // the db connection server name of the database server, can be the same as the READ server
  if(!defined("DBSERVERNAME")) define("DBSERVERNAME","localhost");
  // the connection username with access to the database with rights equal to access type
  if(!defined("USERNAME")) define("USERNAME","postgres");
  // the connection password for the username above
  if(!defined("PASSWORD")) define("PASSWORD","password");
  // the default visibility usergroup name used when there is not user preference
  if(!defined("DEFAULTVISIBILITY")) define("DEFAULTVISIBILITY","Users");
  // boolean constant to control the use of general caching
  if(!defined("USECACHE")) define("USECACHE",false);
  // boolean constant to control the use of caching for dynamic READViewer calculation
  if(!defined("USEVIEWERCACHING")) define("USEVIEWERCACHING",true);
  // boolean constant to control the use of caching of segment clips for paleography reporting
  if(!defined("USESEGMENTCACHING")) define("USESEGMENTCACHING",true);
  // boolean constant to use dynamic calls to retrieve lemma popup info in the READ Viewer
  if(!defined("USEDYNAMICLEMMAINFO")) define("USEDYNAMICLEMMAINFO",true);

//Refresh default settings
  // Default value that controls Annotation cache refresh
  if(!defined("DEFAULTANNOTATIONSREFRESH")) define("DEFAULTANNOTATIONSREFRESH",0);//set >= 1 always refresh
  // Default value that controls Text Resources cache refresh
  if(!defined("DEFAULTTEXTRESOURCEREFRESH")) define("DEFAULTTEXTRESOURCEREFRESH",1);
  // Default value that controls Search All Results cache refresh
  if(!defined("DEFAULTSEARCHREFRESH")) define("DEFAULTSEARCHREFRESH",1);
  // Default value that controls Catalog cache refresh
  if(!defined("DEFAULTCATALOGREFRESH")) define("DEFAULTCATALOGREFRESH",0);
  // Default value that controls Glossary HTML cache refresh
  if(!defined("DEFAULTHTMLGLOSSARYREFRESH")) define("DEFAULTHTMLGLOSSARYREFRESH",0);
  // Default value that controls edition entities cache refresh
  if(!defined("DEFAULTEDITIONREFRESH")) define("DEFAULTEDITIONREFRESH",0);
  // Default owner id for jsoncache entities
  if(!defined("DEFAULTCACHEOWNERID")) define("DEFAULTCACHEOWNERID",4);
  // Default visibility for jsoncache entities
  if(!defined("DEFAULTCACHEVISID")) define("DEFAULTCACHEVISID",6);

  // Constant to control the visibility of Catalog resources in READ editor
  if(!defined("ENABLECATALOGRESOURCE")) define("ENABLECATALOGRESOURCE",'0');//0 = not enable else enabled
  // String constant defining the syllable selection pattern for auto linking segments 
  //if(!defined("LINKSYLPATTERN")) define("LINKSYLPATTERN","L1:S1,L5+5:S1");
  // Boolean constant to control the sort weights used for text in this system
  //if(!defined("USESKTSORT")) define("USESKTSORT",'1');

// Constants for service to manage db snapshot and restore
  // String representing the terminal command used to set environment variables
  if(!defined("SETENVCMD")) define("SETENVCMD",'export'); //for windows bash use 'set'
  // String character used in the terminal to separate commands
  if(!defined("CMDSEPARATOR")) define("CMDSEPARATOR",';');//for windows bash use '&'
  // String representing the path to the directory used for readfilestore snapshot/restore directory
  //if(!defined("READ_FILE_STORE")) define("READ_FILE_STORE",'\\xampp\\readfilestore');//simple script for managing db snapshot and restore
  // String of path to psql.exe command line executable for database management
  //if(!defined("PSQL_PATH")) define("PSQL_PATH",'\\xampp\\PostgreSQL\\9.3\\bin');////simple script for managing db snapshot and restore

  // Calculate maximum size for successful upload based on system parameters
  if(!defined("MAX_UPLOAD_SIZE")) {
    $maxUpload = intval(ini_get("upload_max_filesize"));
    $maxPost = intval(ini_get("post_max_size"));
  // Maximum file size uploadable
    define("MAX_UPLOAD_SIZE",!ini_get("file_uploads")?'0': ''.min(array($maxPost,$maxUpload)));
  }

  // String path to service for cropping image, use for clipping segments
  define("CROP_IMAGE_SERVICE_PATH",SITE_BASE_PATH."/common/php/cropImagePoly.php");

//Constants for READ viewer/editors configuration options
  // Boolean constant to control the visibility of Lemma Phonetics
  define("SHOWLEMMAPHONETIC",0);
  // Boolean constant to control the visibility of Lemma Phonology
  define("SHOWLEMMAPHONOLOGY",0);
  // Boolean constant to control the visibility of Lemma Declentions
  define("SHOWLEMMADECLENSION",0);
  // String define where to get the list of Declensions
  define("DECLENSIONLIST",'OIADeclension');
  // Boolean constant to control the use of editor editting collision detection between editors
  define('EDITCOLLISIONDETECTION',1);
  // Boolean constant to control Edit Tools Panel default state 1 = open  0 = collapsed
  define("EDITTOOLSOPENONSTART",1);
  // Boolean constant to control View Tools Panel default state 1 = open  0 = collapsed
  define("VIEWTOOLSOPENONSTART",1);
  // Boolean constant to control Layout Tools Panel default state 1 = open  0 = collapsed
  define("LAYOUTTOOLSOPENONSTART",1);
  // Boolean constant to control Start state of Sidebar Tools Panel default state 1 = open  0 = collapsed
  define("TOOLSIDEBAROPENONSTART",1);

//Constants for  READViewer  configuration options
  // Boolean constant to control the use/visibility of the export button on the dynamic READViewer
  define("SHOWEXPORTBUTTON",false); // defaults to true so this is required to turn export off
  // Boolean constant to control the use/visibility of the TOC button on the dynamic READViewer
  define("SHOWVIEWERCONTENTOUTLINE",false);
  // Boolean constant to control the use/visibility of the image panel on the dynamic READViewer
  define("SHOWIMAGEVIEW",true);
  // Boolean constant to control the use physicalline format of edition in text panel on the dynamic READViewer
  define("USEPHYSICALVIEW",false);
  // Boolean constant to control the use/visibility of the translation panel on the dynamic READViewer
  define("SHOWTRANSLATIONVIEW",true);
  // Boolean constant to control the use/visibility of the chaya panel on the dynamic READViewer
  define("SHOWCHAYAVIEW",false);
  // Boolean constant to control the default value for Full Glossary export on Export Dialogue
  define("EXPORTFULLGLOSSARY",false);
  // Boolean constant to control the use/visibility of Image Download button
  define("ALLOWIMAGEDOWNLOAD",false);
  // Boolean constant to control the use/visibility of TEI Download button
  define("ALLOWTEIDOWNLOAD",true);
  // Boolean constant to control the scrolling on the lemma popup UI
  define("USESCROLLLEMMAPOPUP",false);
  // Boolean constant to control the use/visibility of Lemma extra info (references and attested forms) 
  define("USELEMMAEXTRAINFO",true);
  // Boolean constant to control the ENCODED Formatting of the lemma etymology 
  define("FORMATENCODEDETYM",true);
  // Boolean constant to control the use of enclosing parentheses around the lemma etymology
  define("SHOWETYMPARENS",false);
  // String template used to create the href link for the lemma
  define("LEMMALINKTEMPLATE",READ_DIR."/plugins/dictionary/?search=%lemval%");

//location label formatting
  // regular expression string for pattern match on text CKN used in attestform location labelling
  define("CKNMATCHREGEXP","'([a-z]+)0*(\\d+)'");// grp1 match starting non numeric characters followed by zero or more 0 grp2 match 1 or more following numbers
  // replacement string for creating text ref part of attestedform location label
  define("CKNREPLACEMENTEXP","'\\1\\2'");//replacement string is grp1 match followed by grp2 match
  // flag string for replacement function when forming text ref part of location label
  define("CKNREPLACEFLAGS","'i'");// ignore case during match
  // string to insert between the text ref and line number in the location label
  define("CKNLINENUMSEPARATOR",":");//separate txt label from line label using a colon
  // boolean when true READ uses a token's verse-pāda location if available
  define("DEFAULTTOVERSELABEL", false); 

//viewer title formatting
  // String for matching used to reformating of text CKN to calculate title for READViewer
  define("INVMATCHREGEXP","/([a-z]+)0*(\\d+)/i");//grp1 match starting non numeric characters followed by zero or more 0 grp2 match following numbers
  // String for replacement used to reformating of text CKN to calculate title for READViewer
  define("INVREPLACEMENTEXP","\\1 \\2");//replace is grp1 followed by grp2
  // String to insert between the text inv and text title
  define("VIEWERINVTITLESEP",": ");//separate txt label from line label using a space

// Constants for part-fragment-side labelling
  // Boolean to substitute image title for Part Side label when not available
  define("SUBIMGTITLEFORPARTSIDELABEL",true);
  // Boolean to include fragment label in the part side label is defined
  define("INCLUDEFRAGINPARTSIDELABEL",true);
  // Boolean to inline fragment part side label in text edition 
  define("INLINEPARTSIDELABEL",true);
  // String used to separate part label from fragment label
  define("PARTFRAGSEPARATOR"," ");
  // String used to separate fragment label from side label using this string
  define("FRAGSIDESEPARATOR"," ");

//  if(!defined("READVIEWER_CSS_PATH")) define("READVIEWER_CSS_PATH","../mydirpath/css/myreadviewer.css"); // must be relative to getTextViewer.php directory
  if(!defined("VIEWER_EXPORT_SUBDIR")) define("VIEWER_EXPORT_SUBDIR","/readviewer");
// to export with separation of project databases use the line below. Also consider
// symbolic links to READ's viewer support subdirectories css and js in viewer export
// directory for automatic system update
  // 
//  if(!defined("VIEWER_EXPORT_SUBDIR")) define("VIEWER_EXPORT_SUBDIR","/readviewer/".DBNAME);
  // 
  if(!defined("VIEWER_EXPORT_PATH")) define("VIEWER_EXPORT_PATH",DOCUMENT_ROOT.VIEWER_EXPORT_SUBDIR);
  // 
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
