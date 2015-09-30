<?php
/**
 * Deletes all but one from any sets of identical relationships
 *
 * This script is a useful tool to keep the Reason DB from getting too crufty.
 * This script probably should be run regularly. In fact, it should probably be 
 * made into a cron job at some point.
 *
 * @package reason
 * @subpackage scripts
 * @todo Add to crontab (and decide which mode to use in cron...)
 */
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'db_maintenance' ) )
	{
		die('<html><head><title>Reason: Delete Duplicate Relationships</title></head><body><h1>Sorry.</h1><p>You do not have permission to delete duplicate relationships.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
	}
	
	?>
	<html>
	<head>
	<title>Reason: Delete Duplicate Relationships</title>
	</head>
	<body>
	<h1>Delete Duplicate Relationships</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>This script will remove all but one relationship from each set of identical relationships. This script is a useful tool to keep the Reason DB from getting too crufty.
	This script probably should be run regularly. In fact, it should probably be made into a cron job at some point.</p>
	<p><input type="checkbox" name="ignore_rel_sort" value="ignore_rel_sort" CHECKED /> Ignore relationship sort order field when checking for duplicates.</p>
	<p><em>Ignoring relationship sort order is probably a good idea. Duplicate entities in all ways except for relationship sort order can result in unpredictable
	relationship sort ordering behavior by the entity selector, and such relationships will only show up once in a lister.</em></p>
	<input type="submit" name="do_it" value="Run the script" />
	</form>
	<?php
	}
	else
	{
		connectDB( REASON_DB );
	
		if (isset($_POST['ignore_rel_sort']) && ($_POST['ignore_rel_sort'] == 'ignore_rel_sort'))
		{
			$ignore_rel_sort = true;
		}
		else $ignore_rel_sort = false;
		
		if ($ignore_rel_sort) $q = 'select *, count(*) as count from relationship group by entity_a, entity_b, type, site having count > 1 order by count';
		else $q = 'select *, count(*) as count from relationship group by entity_a, entity_b, type, site, rel_sort_order having count > 1 order by count';
		$r = db_query( $q, 'Unable to grab duplicate relationships.' );
		$duplicate_set_counter = 0;
		$queries = array();
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
		{
			if ($ignore_rel_sort) $q = 'DELETE FROM relationship WHERE id != '.$row['id'].' AND entity_a = '.$row['entity_a'].' AND entity_b = '.$row['entity_b'].' AND type = '.$row['type'].' AND site = '.$row['site'];
			else $q = 'DELETE FROM relationship WHERE id != '.$row['id'].' AND entity_a = '.$row['entity_a'].' AND entity_b = '.$row['entity_b'].' AND type = '.$row['type'].' AND site = '.$row['site'].' AND rel_sort_order = '.$row['rel_sort_order'];
			$queries[] = $q;
			db_query( $q, 'Unable to delete duplicate relationships.' );
			$duplicate_set_counter++;
		}
		echo '<h2>Done.</h2>';
		if($duplicate_set_counter > 0)
		{
			echo '<p>Deleted '.$duplicate_set_counter.' sets of duplicate relationships.</p>';
			echo '<ul><li>';
			echo implode('</li><li>',$queries);
			echo '</li></ul>';
		}
		else
		{
			echo '<p>No duplicate relationships found.</p>';
		}
	}
?>
	</body>
	</html>
