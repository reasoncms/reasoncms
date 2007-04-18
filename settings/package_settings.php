<?php
/**
 * This file defines the locations of all of the apps in the Reason package.
 * Alter the constant INCLUDE_PATH to indicate the location of this directory.
 * The constant INCLUDE_PATH should be an absolute path from the root of the server,
 * and the same string should also be set as a php include path.
 */
 
/////////////////////////////////////////////////////////////////////////////////////////////////////
// The following constants should be customized for your installation of reason
/////////////////////////////////////////////////////////////////////////////////////////////////////

// Basic information about the organization
define( 'FULL_ORGANIZATION_NAME','The Full Name of the Organization' );
define( 'SHORT_ORGANIZATION_NAME', 'Short Org Name' );
define( 'ORGANIZATION_HOME_PAGE_URI', 'http://www.domain_name.domain' );
define( 'WEBMASTER_EMAIL_ADDRESS', 'webmaster@domain_name.domain' );
define( 'WEBMASTER_NAME', 'Joanne Q. Webmaster' );

define( 'INCLUDE_PATH','/path/to/the/reason/package/'); // where on the machine is the reason package location?
define( 'WEB_PATH', '/path/to/the/server/web/root/' ); // where on the machine is the web root of the server?
define( 'HTTP_HOST_NAME','server.domain_name.domain'); // e.g. www.foobar.com

define( 'PHP_CLI', '/path/to/your/bin/php' ); // where is a command-line executable version of php?
define( 'PHP_CLI_ARGS', '-d include_path='.INCLUDE_PATH.':.' ); // what arguments should be passed when executing php at the command line?

define( 'HTTP_CREDENTIALS_FILEPATH', '/path/to/your/copy/of/http_creds.php' );
define( 'DB_CREDENTIALS_FILEPATH', '/path/to/your/copy/of/dbs.xml' );

define('TIDY_EXE', '/usr/bin/tidy' ); // Define the path to tidy

define('IMAGEMAGICK_PATH', '/usr/bin/'); // Define the path to imagemagick files

define('UNIVERSAL_CSS_PATH', ''); // if you have css that you want included on all pages, enter its url here

/**
 * HTTPS_AVAILABLE
 * Boolean; lets the package know if this server is configured to serve up pages under https or not
 */
define( 'HTTPS_AVAILABLE', true );

///////////////////////////////////////////////////////////////////////////////////////////////////////
// You shouldn't have to alter any of the constants below in a default install for reason to function
///////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Location of web-available portions of Reason package
 *
 * Several parts of the Reason package need to be web-available.
 * Modify this setting to indicate the URL of the web-available directory 
 * where these are located.
 *
 * If you have unpacked Reason inside the web tree, you can simply identify the location
 * of the reason package from the http root.
 *
 */
define('REASON_PACKAGE_WEB_AVAILABLE_HTTP_PATH','/reason_package/');

// Define the path to the settings files for the various applications in the package
define('SETTINGS_INC',INCLUDE_PATH.'settings/');

// Define the path to reason files
define('REASON_INC',INCLUDE_PATH.'reason_4.0/');

// Define the path to carl_util files
define('CARL_UTIL_INC',INCLUDE_PATH.'carl_util/');

// Define the path to tidy.conf
define('TIDY_CONF', SETTINGS_INC . 'tidy.conf' );

// Define the path to disco files
define('DISCO_INC',INCLUDE_PATH.'disco/');

// Define the path to flvplayer files
define('FLVPLAYER_DIRNAME', 'flvplayer');
define('FLVPLAYER_INC',INCLUDE_PATH.FLVPLAYER_DIRNAME.'/');
define('FLVPLAYER_HTTP_PATH',REASON_PACKAGE_WEB_AVAILABLE_HTTP_PATH.FLVPLAYER_DIRNAME.'/');

// Define the path to loki files
define('LOKI_DIRNAME', 'loki_1.0');
define('LOKI_INC',INCLUDE_PATH.LOKI_DIRNAME.'/');
define('LOKI_HTTP_PATH',REASON_PACKAGE_WEB_AVAILABLE_HTTP_PATH.LOKI_DIRNAME.'/');

// Define the path to loki files
define('LOKI_2_DIRNAME', 'loki_2.0');
define('LOKI_2_INC',INCLUDE_PATH.LOKI_2_DIRNAME.'/');
define('LOKI_2_HTTP_PATH',REASON_PACKAGE_WEB_AVAILABLE_HTTP_PATH.LOKI_2_DIRNAME.'/');

// Define the path to tinymce files
define('TINYMCE_DIRNAME', 'tiny_mce');
define('TINYMCE_INC',INCLUDE_PATH.TINYMCE_DIRNAME.'/');
define('TINYMCE_HTTP_PATH',REASON_PACKAGE_WEB_AVAILABLE_HTTP_PATH.TINYMCE_DIRNAME.'/');

// Define the path to Magpie RSS files
define('MAGPIERSS_INC',INCLUDE_PATH.'magpierss/');

// Define the path to Thor files
define('THOR_DIRNAME', 'thor');
define('THOR_INC',INCLUDE_PATH.THOR_DIRNAME.'/');
define('THOR_HTTP_PATH','/thor/');

// Define the path to Tyr files
define('TYR_INC',INCLUDE_PATH.'tyr/');

// Define the path to ADOdb libraries
define('ADODB_INC',INCLUDE_PATH.'adodb/');
define('ADODB_DATE_INC',ADODB_INC.'adodb-time.inc.php');
?>
