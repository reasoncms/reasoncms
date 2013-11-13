<?php
/**
 * This is the reason_package bootstrap file. It is required by the carl util utility package and reason.
 *
 * It must exist at a file system path ending with /reason_package/paths.php, and it must
 * be in the php include path such that it is loadable by php with this simple statement:
 *
 * include_once('paths.php');
 *
 * paths.php file defines the minimal file system paths needed by the reason_package, namely:
 *
 * INCLUDE_PATH - the absolute file system path to the root of the reason_package directory
 * SETTINGS_INC - the location of the settings directory that contains the reason_package settings
 *
 * paths.php additionally defines a method domain_define
 *
 * @package carl_util
 */

/**
 * Defines a constant using the settings for the current domain, if available, or the provided default value.
 * 
 * @param constant string name of the constant to define
 * @param default string default value to set if there is not a value for $GLOBALS['_current_domain_settings[$constant]
 * @return void
 */
function domain_define($constant, $default) 
{
	define ($constant, (isset($GLOBALS['_current_domain_settings'][$constant])) ? $GLOBALS['_current_domain_settings'][$constant] : $default);
	$GLOBALS['_default_domain_settings'][$constant] = $default; // lets store the default in case something cares about the difference
}

/** 
 * The location of the reason_package folder - put this outside of the web tree
 */
define ('INCLUDE_PATH', dirname(__FILE__) . '/');

/**
 * The location of the reason_package settings folder - this should be outside the web tree
 *
 * By default, the constant will be set to settings_local if such a directory exists parallel to settings
 */
define ('SETTINGS_INC', 
       (file_exists(INCLUDE_PATH . 'settings_local'))
       ? INCLUDE_PATH . 'settings_local/'
       : INCLUDE_PATH . 'settings/');
       
/**
 * Load in domain specific settings. Any setting defined with the domain_define will use domain specific settings when available
 * fixes awesome!
 */
include_once (SETTINGS_INC . 'domain_settings.php');

/**
 * Load the package_settings for the reason_packge.
 */
require_once( SETTINGS_INC . 'package_settings.php');
?>
