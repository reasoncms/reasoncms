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
<title>Upgrade Reason from 4.0 Beta 8 to 4.0 Beta 9</title>
</head>
<body>
<h2>Upgrade Reason from 4.0 Beta 8 to 4.0 Beta 9</h2>

<h3>New Settings</h3>
<p>Reason 4 Beta 9 introduces new settings - if you manually maintain your settings files you'll need to add them:</p>
<ol>
<li><strong>REASON_PACKAGE_HTTP_BASE_PATH</strong><p>This identifies an alias in the web root that links to the new
reason_package/www/ folder. This folder should contain items associated with the reason_package
that need to be web accessible (such as the colorpicker javascript and css files used by the colorpicker
plasmature object). You can copy and paste the following into your package_settings.php file:</p>
<textarea rows="10" cols="105">
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
<textarea rows="8" cols="105">
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
<textarea rows="7" cols="105">
/**
 * REASON_SIZED_IMAGE_DIR
 *
 * Full path to the directory where Reason's sized image class (sized_image.php) should store sized images.
 */
define('REASON_SIZED_IMAGE_DIR', REASON_INC.'www/sized_images/');</textarea><br/><br/>
</li>
<li><strong>REASON_SIZED_IMAGE_DIR_WEB_PATH</strong><p>You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:</p>
<textarea rows="7" cols="105">
/**
 * REASON_SIZED_IMAGE_DIR_WEB_PATH
 *
 * Full path to the directory where Reason's sized image class (sized_image.php) should store sized images.
 */
define('REASON_SIZED_IMAGE_DIR_WEB_PATH', REASON_HTTP_BASE_PATH.'sized_images/');</textarea>
</li>
</ol>

<h3>Scripts to Run</h3>
<ul>
<li><a href="feature.php">Adds the feature type to Reason</a></li>
</ul>
</body>
</html>
