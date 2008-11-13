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
<h3>Scripts to Run Immediately (Reason will not work properly until you run these)</h3>
<ul>
<?
//<li><a href="allowable_rel_structure.php">Update the allowable relationship table structure</a></li>
?>
<li><a href="remove_rewrite_finish_actions.php">Removes rewrite finish actions that are no longer needed</a></li>
</ul>
</body>
</html>
