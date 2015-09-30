<?php
/**
 * Deletes records in Reason tables that do not correspond to a record in the master entity table.
 *
 * "Headless chickens" are records in Reason tables that do not correspond to a record in 
 * the master entity table. This script will delete all of the headless chickens.
 *
 * This script is a useful tool to keep the Reason DB from getting too crufty.
 * This script probably should be run regularly. In fact, it should probably be 
 * made into a cron job at some point.
 *
 * @package reason
 * @subpackage scripts
 * @todo Add to crontab
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
	<title>Reason: Delete Headless Chickens</title>
	</head>
	<body>
	<h1>Delete Headless Chickens</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>Headless chickens are records in Reason tables that do not correspond to a record in the master entity table. This script will delete all of the headless chickens.</p>
	<p>This script is a useful tool to keep the Reason DB from getting too crufty. This script probably should be run regularly. In fact, it should probably be made into a cron job at some point.</p>
	<input type="submit" name="do_it" value="Run the script" />
	</form>
	<?php
	}
	else
	{
	connectDB( REASON_DB );

	$tables = get_entities_by_type_name( 'content_table' );
	$to_delete = array();
	foreach( $tables as $t_info )
	{
		if( !empty($t_info['name']) &&  !empty($t_info['state']) && $t_info['state'] == 'Live')
		{
			$table = $t_info['name'];
			$q = "SELECT t.id FROM $table AS t LEFT JOIN entity AS e ON e.id = t.id WHERE e.id IS NULL";
			$r = db_query( $q, 'Unable to grab headless chickens.' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$to_delete[$table][] = $row['id'];
			if( !empty($to_delete[$table]) )
			{
				$q = 'DELETE FROM '.$table.' WHERE id IN ('.implode(', ',$to_delete[$table]).')';
				$queries[] = $q.'<br /><br />';
				db_query( $q, 'Unable to delete headless chickens.' );
			}
		}
	}
	if(!empty($queries))
	{
		echo '<h2>Headless chickens mopped up:</h2>';
		echo '<ul>';
		foreach($to_delete as $table => $rows)
		{
			echo '<li>'.$table.': '.count($rows).'</li>';
		}
		echo '</ul>';
		echo '<h2>Queries</h2><ul><li>';
		echo implode('</li><li>',$queries);
		echo '</li></ul>';
	}
	else
	{
		echo '<h2>Congratulations!  There are no headless chickens in Reason at the moment.</h2>';
	}
}
?>
</body>
</html>
