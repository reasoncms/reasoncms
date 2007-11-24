<?php
/**
 * Upgrade from beta 3 to beta 4
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start page
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade To Reason 4 Beta 4</title>
</head>

<body>
<h1>Upgrade to Reason 4 Beta 4</h1>
<p>If you have a freshly installed copy of Reason 4 Beta 4 you do not need to run these scripts. These scripts add a created_by field
for every entity in a reason database, and make a variety of changes which migrate the database from having a "blog" type to a 
"publication" type that supports blogs and other publications.</p>
<p>These scripts should be run immediately after you upgrade the files in the file system to Reason 4 Beta 4.</p>
<ol>
<li><a href="create_created_by.php">Create created_by field</a></li>
<li><a href="text_blurb_reorient.php">Change the direction of text blurb to page relationships</a></li>
<li><a href="news_to_image_sorting.php">Makes news_to_image relationships sortable and sets initial values</a></li>
<li><a href="event_repeat_field_name_change.php">Change the repeat field name to support mysql 5.x</a></li>
</ol>
<p>These scripts should be non destructive and do not depend on files changed in 4.0b4 - they can be run anytime</p>
<ol>
<li><a href="add_feed_display_relationship.php">Add the page to feed relationship</a></li>
<li><a href="add_user_popup_alert_pref.php">Add the user popup alert preference to user type</a></li>
<li><a href="misc_updates.php">Miscellaneous additional updates</a></li>
</ol>
<p>These are publications upgrade scripts and should all be run at the same time - they will not work right unless
the instance has been upgraded to 4.0b4 files</p>
<ol>
<li><a href="upgrade_db.php">Initial DB Upgrade to support publications</a></li>
<li><a href="blog_to_publication.php">DB Conversion of blog references to publication</a></li>
<li><a href="blog_to_publication2.php">A few extras that need doing ...</a></li>
<li><a href="blog_to_publication3.php">Creates publication to related publication relationship</a></li>
</ol>
</body>
</html>
