<?php
/**
 * Upgrade Reason from 4.0 beta 4 to beta 5
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason from 4.0 Beta 4 to 4.0 Beta 5</title>
</head>
<body>
<h2>Upgrade Reason from 4.0 Beta 4 to 4.0 Beta 5</h2>
<h3>Settings Changes</h3>
<p>There are a variety of new settings and changes to the settings files. It is recommended that you replace your existing settings files with files downloaded from the Reason 4 Beta 5 package.</p>
<h4>package_settings.php</h4>
<ul>
<li>XML_PARSER_INC and XML_PARSER_DIRNAME - package_settings.php path constants - replacement for the PEAR XML Unserializer</li>
<li>HTML_PURIFIER_INC and HTML_PURIFIER_DIRNAME - package_settings.php path constants - replacement for PEAR's HTML_Safe library</li>
<li>LIBCURLEMU_INC and LIBCURLEMU_DIRNAME - package_settings.php path constants - more flexibility for curl to use curllib, curl command line, or curl emulation</li>
<li>CURL_PATH - package_settings.php path constant - denotes location of curl binary - used if libcurl is unavailable</li>
<li>HTML_SANITIZATION_FUNCTION - package_settings.php new settings - denotes what function to use to sanitize HTML</li>
</ul>
<h4>reason_settings.php</h4>
<ul>
<li>REASON_LOGIN_PATH - reason_settings.php setting - new setting</li>
<li>REASON_LOGIN_URL - <strong>removed</strong> from reason_settings.php - is now set dynamically in the header depending upon whether https is available</li>
<li>REASON_ICALENDAR_UID_DOMAIN - reason_settings.php setting - brand new sets domain for use in icalendar generation</li>
<li>REASON_DEFAULT_ALLOWED_TAGS - reason_settings.php setting - a whitelist of the HTML tags Reason will allow to be saved to the database</li>
</ul>
<h4>tidy.conf</h4>
<p>The tidy configuration file needs to have an additional line:</p>
<p><pre>show-body-only: yes</pre></p>
<h3>Scripts to Run</h3>
<ul>
<li><a href="upgrade_site_type_content_manager.php">Upgrade content manager for site types</a></li>
</ul>
</body>
</html>
