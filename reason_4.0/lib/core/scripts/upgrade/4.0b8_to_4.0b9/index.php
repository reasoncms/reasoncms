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
<p>Reason 4 Beta 9 introduces new settings:</p>
<ol>
<li>REASON_LOG_LOGINS. You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:<br />
<textarea rows="8" cols="100">
/**
 * REASON_LOG_LOGINS
 *
 * The Reason login module can log all login and logout actions.  If you set this value to true,
 * a log file will be populated at REASON_LOG_DIR/reason_login.log
 */
define('REASON_LOG_LOGINS', true);</textarea>
</li>
</ol>

<h3>Scripts to Run</h3>
<ul>
<li><a href="feature.php">Adds the feature type to Reason</a></li>
</ul>
</body>
</html>
