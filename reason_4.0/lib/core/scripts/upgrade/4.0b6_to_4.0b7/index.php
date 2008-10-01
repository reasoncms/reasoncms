<?php
/**
 * Upgrade Reason from 4.0 beta 6 to beta 7
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
<title>Upgrade Reason from 4.0 Beta 6 to 4.0 Beta 7</title>
</head>
<body>
<h2>Upgrade Reason from 4.0 Beta 6 to 4.0 Beta 7</h2>
<h3>Upgrade Notes</h3>
<p>Reason's object caching system has been moved into carl_util/cache/. There is a new settings file called object_cache_settings.php that
   may need to be configured for your environment. The object caching system now supports caching using memcached or mysql, as well
   as caching to the file system. By default, the object cache is configured to use file system caching to the REASON_LOG_DIR (in the Reason
   environment, or to /tmp/ if the reason_header has not been loaded.</p>
<p>The setting REASON_DEFAULT_ALLOWED_TAGS in previous distributions of Reason contained an odd assortment of tags. You may 
want to update this setting in reason_settings.php to more closely match the set of XHTML compliant tags. More information can be found in an <a 
href="https://apps.carleton.edu/opensource/reason/developers/changes/?story_id=399475">April 4, 2008 Reason change log post</a>.</p>
<h3>Scripts to Run</h3>
<p>The following scripts should be run to upgrade a Reason 4 Beta 6 database to Reason 4 Beta 7. If you setup Reason with the database distributed 
with Reason 4 Beta 7 you do not need to run these scripts.</p>
<ul>
<li><a href="page_access.php">Add the page access allowable relationship</a></li>
<li><a href="custom_footer.php">Add capability to customize site footers</a></li>
<li><a href="site_parent_rel.php">Upgrade parent-child site relationship</a></li>
<li><a href="new_themes.php">Add new themes</a></li>
<li><a href="post_feed.php">Upgrade news feed generator</a></li>
<li><a href="forms.php">Upgrade thor form infrastructure</a></li>
<li><a href="kill_cruft.php">Kill database cruft (and more)</a></li>
</ul>
<h3>Scripts to Maybe Run</h3>
<ul>
<li><a href="blurb_page_type_change.php">Change blurb page types to demote headings</a></li>
</ul>
</body>
</html>
