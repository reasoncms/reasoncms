<?php
/**
 * Upgrade Reason from 4.0 beta 7 to beta 8
 *
 * @package reason
 * @subpackage scripts
 *
 * @todo add links to documentation for major changes and info on upgrading
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
<p>Reason 4 Beta 8 supports and encourages setups in which the entire reason_package exists outside of the web tree. Moving forward, this will be the recommended setup. 
There are a variety of changes to the setup files distributed with Reason to support this type of setup. Existing installs should continue to function as they did before, 
but it is recommended that you replace your settings directory with the one distributed in Reason 4 Beta 8. You should also replace your paths.php file with the one distributed 
in Reason 4 Beta 8.</p>
<p>The settings directory in Reason 4 Beta 8 also includes a new settings file, domain_settings.php. This release includes highly experimental (and partial) support for 
multi-domain Reason instances. Show stopping issues remain with .htaccess rewrites and url history features. Do not attempt to configure domain_settings.php in a production 
instance at this point, and if you do, don't expect anything will go smoothly!</p>
<?php
// This change will probably be pushed to Reason 4 Beta 9 or Reason 4 RC 1
//
//<p>Reason 4 Beta 8 changes the rel_name field for "owns" and "borrows" allowable relationships to be unique. This allows entity selector queries
//to be a bit simpler, and allows the relationship_id_of method to work for all allowable relationships. This is a major change; code distributed 
//with Reason 4 Beta 8 will be unreliable on a Reason 4 Beta 7 database that has not been upgraded.</p>
?>
<h3>New Settings</h3>
<p>Reason 4 Beta 8 introduces six new settings:</p>
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
<li>REASON_DISABLE_AUTO_UPDATE_CHECK. You should make sure the setting is defined in the
reason_settings.php used in your reason instance. You can copy and paste the following:<br />
<textarea rows="4" cols="100">
/**
 * REASON_DISABLE_AUTO_UPDATE_CHECK
 *
 * If you want Reason to stop checking for updates, set this to true. (Not recommended... but if
 * you don't want Reason phoning home to check for updates, this setting is for you.)
 */
define('REASON_DISABLE_AUTO_UPDATE_CHECK', false);
</textarea>
</li>
<li>DATE_PICKER_INC and DATE_PICKER_HTTP_PATH. These settings should be defined in package_settings.php. You can copy and paste the following:<br />
<textarea rows="5" cols="100">
/**
 * Define the path to Date Picker files
 */
define('DATE_PICKER_INC', INCLUDE_PATH.'date_picker/');
define('DATE_PICKER_HTTP_PATH', '/date_picker/');
</textarea>
<p>Note that if you use the default value for DATE_PICKER_HTTP_PATH, you should also create a symbolic link from /date_picker/ 
to the file system location of reason_package/date_picker/ (or <a href="<?php echo REASON_HTTP_BASE_PATH . 'setup.php?fix_mode=true'; ?>">
rerun setup.php with fix mode enabled</a>) which will attempt to make the symlink for you.</p>
</li>
<li>REASON_ALLOWS_INLINE_EDITING. This settings should be defined in reason_settings.php. You can copy and paste the following:<br />
<textarea rows="5" cols="100">
/**
 * REASON_ALLOWS_INLINE_EDITING
 *
 * This constant determines whether a Reason instance exposes inline editing features to users with
 * proper privileges (determined on a module to module basis). It defaults to true and should be 
 * left as true to take advantage of inline editing features provided by Reason modules.
 */
define('REASON_ALLOWS_INLINE_EDITING', true);
</textarea>
<p>The implications of this setting and the inline editing framework are explained in more detail in a <a href="https://apps.carleton.edu/opensource/reason/developers/changes/?story_id=613674">reason change log post</a>.</p>
</li>
</ol>
<h3>Additional Notes</h3>
<p>Starting with this release, it is especially important to ensure that the setting THIS_IS_A_DEVELOPMENT_REASON_INSTANCE is properly defined
in your settings file. This is because Reason now uses the value of this setting to determine if the a meta tag excluding robots should be
included on pages. Please make sure that your production instance of Reason has this setting set to <tt>false</tt>, and your development and
testing instances of Reason have this setting set to <tt>true</tt>. This upgrade helps to ensure that your development and testing versions of
Reason are not inadvertently indexed by Google or other search engines.</p>
<h3>Scripts to Run</h3>
<ul>

<?php
//<li><a href="allowable_rel_structure.php">Update the allowable relationship table structure</a> <em>(this script may have been autorun already)</em></li>
?>

<li><a href="remove_rewrite_finish_actions.php">Remove rewrite finish actions that are no longer needed</a></li>
<li><a href="update_types.php">Update several Reason types with new fields / deletes obsolete fields</a></li>
<li><a href="database_cleanup.php">Perform database cleanup and maintenance</a></li>
<li><a href="image_ordering.php">Update the sort_order value for the images module</a></li>
<li><a href="tame_url_history.php">Cleans cruft like external urls from the URL History table</a></li>
<li><a href="minor_updates.php">Additional minor updates</a></li>
</ul>
</body>
</html>
