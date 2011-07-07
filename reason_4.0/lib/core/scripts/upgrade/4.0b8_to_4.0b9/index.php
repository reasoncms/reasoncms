<?php
/**
 * Upgrade Reason from 4.0 beta 8 to beta 9
 *
 * @package reason
 * @subpackage scripts
 *
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason from 4.0 Beta 8 to 4.0 Beta 9 (otherwise known as 4.0)</title>
</head>
<body>
<h2>Upgrade Reason from 4.0 Beta 8 to 4.0 Beta 9 (otherwise known as 4.0)</h2>
<h3>New Settings</h3>
<p>This version of Reason introduces new settings - if you manually maintain your settings files you'll need to add them:</p>
<ol>
<li><strong>REASON_PACKAGE_HTTP_BASE_PATH</strong><p>This identifies an alias in the web root that links to the new
reason_package/www/ folder. This folder should contain items associated with the reason_package
that need to be web accessible (such as the colorpicker javascript and css files used by the colorpicker
plasmature object). You can copy and paste the following into your package_settings.php file:</p>
<textarea rows="9" cols="110">
/**
 * REASON_PACKAGE_HTTP_BASE_PATH
 * This setting identifies the location of the reason_package web-available directory from the web root
 * This path should be an alias to the reason_package www folder, which should be 
 * located outside the web root. 
 *
 * The location of the reason_package www folder is /reason_package/www/
 */
domain_define( 'REASON_PACKAGE_HTTP_BASE_PATH','/reason_package/');
</textarea><br/><br/>
</li>
<li><strong>REASON_LOG_LOGINS</strong><p>You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:</p>
<textarea rows="7" cols="110">
/**
 * REASON_LOG_LOGINS
 *
 * The Reason login module can log all login and logout actions.  If you set this value to true,
 * a log file will be populated at REASON_LOG_DIR/reason_login.log
 */
define('REASON_LOG_LOGINS', false);</textarea><br/><br/>
</li>
<li><strong>REASON_SIZED_IMAGE_DIR</strong><p>You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:</p>
<textarea rows="6" cols="110">
/**
 * REASON_SIZED_IMAGE_DIR
 *
 * Full path to the directory where Reason's sized image class (sized_image.php) should store sized images.
 */
define('REASON_SIZED_IMAGE_DIR', REASON_INC.'www/sized_images/');</textarea><br/><br/>
</li>
<li><strong>REASON_SIZED_IMAGE_DIR_WEB_PATH</strong><p>You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:</p>
<textarea rows="6" cols="110">
/**
 * REASON_SIZED_IMAGE_DIR_WEB_PATH
 *
 * Full path to the directory where Reason's sized image class (sized_image.php) should store sized images.
 */
define('REASON_SIZED_IMAGE_DIR_WEB_PATH', REASON_HTTP_BASE_PATH.'sized_images/');</textarea><br/><br/>
</li>
<li><strong>REASON_EVENT_GEOLOCATION_ENABLED</strong><p>You should make sure the setting is defined in the 
reason_settings.php used in your reason instance. You can copy and paste the following:</p>
<textarea rows="13" cols="110">
/**
 * REASON_EVENT_GEOLOCATION_ENABLED
 *
 * Reason event geolocation adds mapping features to the event content manager and to Reason event modules.
 * These features use the free version of google maps, and are enabled by default. Google provides info on
 * the terms and conditions of their mapping service here:
 *
 * http://code.google.com/apis/maps/terms.html
 * 	 
 * If you are using Reason in an environment that does not quality for free use of Google maps you should
 * disable event geolocation.
 */
define('REASON_EVENT_GEOLOCATION_ENABLED', true);</textarea><br/><br/>
</li>
<li><strong>REASON_MYSQL_SPATIAL_DATA_AVAILABLE</strong><p>You should make sure the setting is defined in the 
reason_settings.php used in your reason instance. You can copy and paste the following:</p>
<textarea rows="11" cols="110">
/**
 * REASON_MYSQL_SPATIAL_DATA_AVAILABLE
 *
 * If you are running MySQL 5, Reason can store location information as binary data in MySQL, and keep
 * this data up to date using triggers. This is off by default - you should only turn it on if you have
 * upgraded your database to support this functionality as described in the binary spatial data upgrade
 * script.
 *	
 * If you enable this on a database that does not have this support Reason will crash.
 */
define('REASON_MYSQL_SPATIAL_DATA_AVAILABLE', false);</textarea><br/><br/>
</li>
<li><strong>REASON_IPINFODB_API_KEY</strong><p>You should make sure the setting is defined in the
reason_settings.php used in your Reason instance. You can copy and paste the following - if you have 
(or make) an API key for the api.ipinfodb.com service make sure to add the appropriate key.</p>
<textarea rows="8" cols="110">
/**
 * REASON_IPINFODB_API_KEY
 *
 * Optionally provide your api key for the api.ipinfodb.com ip address geolocation service.
 *
 * With an API key, Reason can provide superior ip geolocation results.
 */
define('REASON_IPINFODB_API_KEY', '');</textarea>
</li>

<li><strong>JQUERY_UI_URL</strong><p>You should make sure the setting is defined in the
package_settings.php file used in your Reason instance. You can copy and paste the following:</p>
<textarea rows="2" cols="110">
define('JQUERY_UI_URL',JQUERY_HTTP_PATH.'jquery_ui_latest.js');</textarea>
</li>

<li><strong>JQUERY_UI_CSS_URL</strong><p>You should make sure the setting is defined in the
package_settings.php file used in your Reason instance. You can copy and paste the following:</p>
<textarea rows="2" cols="110">
define('JQUERY_UI_CSS_URL',JQUERY_HTTP_PATH.'css/smoothness/jquery-ui.css');</textarea>
</li>
</ol>
<h3>Scripts to Run</h3>
<ul>
<li><a href="feature.php">Add the feature type to Reason, fix the policy content sorter</a></li>
<li><a href="event_location.php">Adds location-related fields to event type</a></li>
<li><a href="misc.php">Makes miscellaneous minor upgrades to Reason</a></li>
</ul>
<h3>Scripts to Optionally Run</h3>
<ul>
<li><a href="event_binary_data.php">Add binary spatial data field to events to store location data - requires MySQL 5+ and MyISAM tables</a></li>
</ul>
</body>
</html>
