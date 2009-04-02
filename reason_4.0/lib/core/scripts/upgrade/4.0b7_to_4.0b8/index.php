<?php
/**
 * Upgrade Reason from 4.0 beta 7 to beta 8
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
<title>Upgrade Reason from 4.0 Beta 7 to 4.0 Beta 8</title>
</head>
<body>
<h2>Upgrade Reason from 4.0 Beta 7 to 4.0 Beta 8</h2>
<h3>Upgrade Notes</h3>
<p>Reason 4 Beta 8 changes the rel_name field for "owns" and "borrows" allowable relationships to be unique. This allows entity selector queries
to be a bit simpler, and allows the relationship_id_of method to work for all allowable relationships. This is a major change; code distributed 
with Reason 4 Beta 8 will be unreliable on a Reason 4 Beta 7 database that has not been upgraded.</p>
<h3>New Settings</h3>
<p>Reason 4 Beta 8 introduces two new settings:</p>
<ol>
<li>DISABLE_REASON_ADMINISTRATIVE_INTERFACE. You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:<br />
<textarea rows="8" cols="100">
/**
 * DISABLE_REASON_ADMINISTRATIVE_INTERFACE
 * Set this to true if you want to temporarily disable the reason administrative interface
 * false = normal -- people can use the administrative interface
 * true = shut down -- people cannot use the administrative interface
 * Boolean (e.g. true, false -- no quotes)
 */
define('DISABLE_REASON_ADMINISTRATIVE_INTERFACE', false);
</textarea>
</li>
<li>REASON_ASSET_MAX_UPLOAD_SIZE_MEGS. You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:<br />
<textarea rows="8" cols="100">
/**
 * REASON_ASSET_MAX_UPLOAD_SIZE_MEGS
 * The largest size that uploaded Reason assets can be, in megabytes.
 * Note that Reason will use the smallest of these three values:
 * post_max_size in php.ini, upload_max_filesize in php.ini, and this setting.
 */
define( 'REASON_ASSET_MAX_UPLOAD_SIZE_MEGS',  50 );
</textarea>
</li>
</ol>
<h3>Scripts to Run</h3>
<ul>

<?
//<li><a href="allowable_rel_structure.php">Update the allowable relationship table structure</a> <em>(this script may have been autorun already)</em></li>
echo '<li>Update the allowable relationship table structure (NOT READY YET)</li>';
?>

<li><a href="remove_rewrite_finish_actions.php">Remove rewrite finish actions that are no longer needed</a></li>
<li><a href="update_types.php">Update several Reason types with new fields / deletes obsolete fields</a></li>
<li><a href="database_cleanup.php">Perform database cleanup and maintenance</a></li>
<li><a href="image_ordering.php">Update the sort_order value for the images module</a></li>
</ul>
</body>
</html>
