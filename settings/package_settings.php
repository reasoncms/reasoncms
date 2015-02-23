<?php
/**
 * This file setups the following:
 *
 * - settings used globally throughout the applications included within reason_package
 * - paths used globally throughout the applications included within reason_package
 * - file system location of binaries that the reason_package requires
 * - the include path and http_path of applications included within reason_package
 *
 * This file should never be included directly, but instead gets included through one of two reason package bootstrap files:
 *
 * paths.php - for carl_util functionality when the full reason environment is not needed
 * reason_header.php - for access to both carl_util and reason libraries
 *
 * @require INCLUDE_PATH must have been defined in paths.php
 * @require SETTINGS_INC must have been defined in paths.php
 *
 * @package carl_util
 */

/////////////////////////////////////////////////////////////////////////////////////////////////////
// The following constants should be customized for your installation of the reason_package
/////////////////////////////////////////////////////////////////////////////////////////////////////

// Basic information about the organization
domain_define( 'FULL_ORGANIZATION_NAME','The Full Name of the Organization' );
domain_define( 'SHORT_ORGANIZATION_NAME', 'Short Org Name' );
domain_define( 'ORGANIZATION_HOME_PAGE_URI', 'http://www.domain_name.domain' );
domain_define( 'WEBMASTER_EMAIL_ADDRESS', 'webmaster@domain_name.domain' );
domain_define( 'WEBMASTER_NAME', 'Joanne Q. Webmaster' );

/////////////////////////////////////////////////////////////////////////////////////////////////////
// The following constants can be left alone, provided reason_package is OUTSIDE the web tree
/////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * The location of the database credentials file
 *
 * SECURITY ALERT: this file MUST be outside of the web tree - otherwise your database credentials are accessible to everyone
 */
domain_define( 'DB_CREDENTIALS_FILEPATH', SETTINGS_INC. 'dbs.xml' );

/**
 * The locations of the http credentials file - this need not be defined unless you have web resources behind https authentication
 *
 * SECURITY ALERT: this file MUST be outside of the web tree - otherwise your database credentials are accessible to everyone
 */
domain_define( 'HTTP_CREDENTIALS_FILEPATH', '' );

/////////////////////////////////////////////////////////////////////////////////////////////////////
// The following should be reviewed to make sure they are appropriate to your environment
/////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * HTTPS_AVAILABLE
 * Boolean; lets the package know if the domain is configured to serve up pages under https or not
 */
domain_define( 'HTTPS_AVAILABLE', false );

/**
 * The file system directory that contains imagemagick binaries (such as mogrify)
 */
domain_define ('IMAGEMAGICK_PATH', '/usr/bin/');

/**
 * The command line path used to invoke tidy (eg. /usr/bin/tidy) - optional if libtidy is part of the php install
 */
domain_define ('TIDY_EXE', '/usr/bin/tidy');

///////////////////////////////////////////////////////////////////////////////////////////////////////
// You shouldn't have to alter any of the constants below in a default install for reason to function
///////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * The absolute file system directory that is the default web root - should include a trailing slash
 */
domain_define( 'WEB_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], "/") . '/' );

/**
 * REASON_PACKAGE_HTTP_BASE_PATH
 * This setting identifies the location of the reason_package web-available directory from the web root
 * This path should be an alias to the reason_package www folder, which should be
 * located outside the web root.
 *
 * The location of the reason_package www folder is /reason_package/www/
 */
domain_define( 'REASON_PACKAGE_HTTP_BASE_PATH','/reason_package/');

/**
 * An optional path to a CSS file that may be used by various reason_package utilities
 */
domain_define( 'UNIVERSAL_CSS_PATH', REASON_PACKAGE_HTTP_BASE_PATH.'css/universal.css'); // Define the path to a CSS file used by various reason_package utilities


/**
 * The host name, (eg www.mysite.com)
 */
domain_define( 'HTTP_HOST_NAME', $_SERVER['HTTP_HOST'] );

/**
 * Make sure that your locale settings use UTF-8.
 *
 * If your server is not set to use utf-8, and you cannot change it at the server level, uncomment
 * the line below and use your appropriate language/country information
 */
//setlocale(LC_ALL, 'en_US.UTF-8');

/**
 * This is deprecated - while carl_get_safer_html($html) respects this currently, it will go away
 * entirely when Reason 4.5 is released.
 */
define('HTML_SANITIZATION_FUNCTION','get_safer_html_html_purifier');

// Define the path to the folder that contains Reason's lib and www folder
define('REASON_INC',INCLUDE_PATH.'reason_4.0/');

// Define the path to carl_util files
define('CARL_UTIL_INC',INCLUDE_PATH.'carl_util/');

// Define the path to disco files
define('DISCO_INC',INCLUDE_PATH.'disco/');

// Define the path to flvplayer files
define('FLVPLAYER_INC',INCLUDE_PATH.'flvplayer/');
define('FLVPLAYER_HTTP_PATH','/flvplayer/');

// Define the path to loki 2 files
define('LOKI_2_INC',INCLUDE_PATH.'loki_2.0/helpers/php/');
define('LOKI_2_HTTP_PATH','/loki_2.0/');

// Define the path to tinymce files
define('TINYMCE_HTTP_PATH','/reason_package/tiny_mce/');

// Define the path to Date Picker files
define('DATE_PICKER_INC', INCLUDE_PATH.'date_picker/');
define('DATE_PICKER_HTTP_PATH', '/date_picker/');

// Define the path to Magpie RSS files
define('MAGPIERSS_INC',INCLUDE_PATH.'magpierss/');

// Define the path to Thor files
define('THOR_INC',INCLUDE_PATH.'thor/');
define('THOR_HTTP_PATH','/thor/');

// Define the path to Tyr files
define('TYR_INC',INCLUDE_PATH.'tyr/');

// Define the path to ADOdb libraries
define('ADODB_INC',INCLUDE_PATH.'adodb/');
define('ADODB_DATE_INC',ADODB_INC.'adodb-time.inc.php');

// Define the path to XML Parser files
define('XML_PARSER_INC',INCLUDE_PATH.'xml/');

// Define the path to HTML Purifier
define('HTML_PURIFIER_INC',INCLUDE_PATH.'htmlpurifier/');

// Define the path to jquery
define('JQUERY_INC',INCLUDE_PATH.'jquery/');
define('JQUERY_HTTP_PATH','/jquery/');
define('JQUERY_URL',JQUERY_HTTP_PATH.'jquery-1.11.2.min.js');
define('JQUERY_UI_URL',JQUERY_HTTP_PATH.'jquery-ui-1.11.3.min.js');
define('JQUERY_UI_CSS_URL',JQUERY_HTTP_PATH.'css/smoothness/jquery-ui.min.css');

// Define the path to Less PHP
define('LESSPHP_INC',INCLUDE_PATH.'lessphp/');
?>
