<?php
/**
 * Upgrade Reason from 4.0 beta 5 to beta 6
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
<title>Upgrade Reason from 4.0 Beta 5 to 4.0 Beta 6</title>
</head>
<body>
<h2>Upgrade Reason from 4.0 Beta 5 to 4.0 Beta 6</h2>
<h3>Settings Changes</h3>
<p>There are several new settings and changes to the settings files. Use the new settings files included with the Reason 4 Beta 6 package or add/update the
following settings:
<h4>package_settings.php</h4>
<ul>
<li>LOKI_2_INC - changed from INCLUDE_PATH.LOKI_2_DIRNAME.'/' to INCLUDE_PATH.LOKI_2_DIRNAME.'/helpers/php/' to reflect updated Loki 2 directory structure</li>
<li>JQUERY_DIRNAME, JQUERY_INC, JQUERY_HTTP_PATH, JQUERY_URL: <p>Add the following lines to package_settings.php to setup jQuery constants:</p>
<ul>
<li>define('JQUERY_DIRNAME', 'jquery');</li>
<li>define('JQUERY_INC',INCLUDE_PATH.JQUERY_DIRNAME.'/');</li>
<li>define('JQUERY_HTTP_PATH',REASON_PACKAGE_WEB_AVAILABLE_HTTP_PATH.JQUERY_DIRNAME.'/');</li>
<li>define('JQUERY_URL',JQUERY_HTTP_PATH.'jquery_latest.js');</li>
</ul></li>
</ul>
<h4>reason_settings.php</h4>
<ul>
<li>REASON_DEFAULT_TIMEZONE - setting should be defined as a <a href="http://www.php.net/manual/en/timezones.php">php timezone</a>. Needed only for php5 installations.</li>
</ul>
<h3>Scripts to Run</h3>
<p>The following scripts should be run to upgrade a Reason 4 Beta 5 database to Reason 4 Beta 6. If you setup Reason with the database distributed with Reason 4 Beta 6 you do not need to run these scripts.</p>
<ul>
<li><a href="publications.php">Upgrade publications framework</a></li>
<li><a href="thor_db_structure_fix.php">Update thor tables</a></li>
<li><a href="classified.php">Add classified framework</a></li>
<li><a href="assets.php">Update assets</a></li>
<li><a href="page_to_category_sorting.php">Update page to category relationship for relationship sorting</a></li>
<li><a href="quote_type.php">Create the quote type</a></li>
</ul>
</body>
</html>
