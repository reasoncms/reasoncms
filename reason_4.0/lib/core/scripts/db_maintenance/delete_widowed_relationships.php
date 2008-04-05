<?php
/**
 * Deletes relationships that do not have an entity on one or the other side.
 *
 * If one or both of the entities of a relationship have been expunged from the Reason database -- but the relationship still exists -- then the relationship is "widowed." This is one of the types of data cruft that can build up in a relational database without transactions.
 *
 *	This script deletes any widowed relationships in Reason. It should probably should be run regularly. In fact, it should probably be made into a cron job at some point.
 *
 * @package reason
 * @subpackage scripts
 * @todo Add to crontab
 */
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
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
	<title>Reason: Delete Widowed Relationships</title>
	</head>
	<body>
	<h1>Delete Widowed Relationships</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>If one or both of the entities of a relationship have been expunged from the Reason database -- but the relationship still exists -- then the relationship is "widowed."</p>
	<p>This script deletes any widowed relationships in Reason. It should probably should be run regularly. In fact, it should probably be made into a cron job at some point.</p>
	<input type="submit" name="do_it" value="Run the script" />
	</form>
	<?php
	}
	else
	{
		connectDB( REASON_DB );
		$results = array();
		$sides = array('a','b');
		foreach( $sides as $side )
		{
			$q = 'SELECT r.id FROM relationship AS r LEFT JOIN entity AS e ON r.entity_'.$side.' = e.id WHERE e.id IS NULL';
			$r = db_query( $q, 'Unable to grab widowed relationships.' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			{
				if(empty($results[$side]))
				{
					$results[$side] = array();
				}
				$results[$side][] = $row['id'];
				delete_relationship( $row['id'] );
			}
		}
		if(!empty($results))
		{
			echo '<h2>Widowed Relationships Deleted</h2>';
			foreach($results as $side => $ids)
			{
				echo '<h3>Side '.$side.': '.count($ids).' relationships deleted</h3>';
				echo '<ul><li>'.implode('</li><li>',$ids).'</li></ul>';
			}
		}
		else
		{
			echo '<h2>Congratulations</h2>';
			echo '<p>There are no widowed relationships in Reason</p>';
		}
	}
?>
