<?php
/**
 * Reason garbage collector
 *
 * runs periodically and deletes all entities marked as deleted at least two weeks ago.
 *
 * Also deleted pending entities without names that are more than 2 days old (e.g. someone 
 * created a new entity but never actually submitted the form that would have populated the entity)
 *
 * Best practice is to put this in your daily cron tab and pipe the results into a file as a record of what was deleted.
 *
 * @author Dave Hendler, Matt Ryan
 * @package reason
 * @subpackage scripts
 */
	
	include_once( 'reason_header.php' );
	include_once( CARL_UTIL_INC . 'db/db.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	connectDB( REASON_DB );
	
	// if this is being run in the browser, require authentication
	if( php_sapi_name() != 'cli' )
	{
		force_secure_if_available();
		$current_user = check_authentication();
		$user_id = get_user_id($current_user);
		if (empty( $user_id ) || !reason_user_has_privs( $user_id, 'view_sensitive_data' ) || !reason_user_has_privs( $user_id, 'db_maintenance') )
		{
			die('<h1>Sorry.</h1><p>You do not have permission to run the garbage collector (You must be a reason user with the privileges to view sensitive data and do database maintenance.)</p>');
		}
	}
	else
	{
		$user_id = get_user_id('causal_agent');
	}
	
	if(!defined('REASON_DELETED_ITEM_EXPUNGEMENT_WAIT_DAYS') )
	{
		echo '<p>Note: REASON_DELETED_ITEM_EXPUNGEMENT_WAIT_DAYS not defined; defaulting to 14 days</p>'."\n";
		$wait_days = 14;
	}
	else
	{
		$wait_days = REASON_DELETED_ITEM_EXPUNGEMENT_WAIT_DAYS;
		if(empty($wait_days) || !is_numeric($wait_days))
		{
			echo '<p>Note: REASON_DELETED_ITEM_EXPUNGEMENT_WAIT_DAYS not set as a number of days; defaulting to 14 days</p>'."\n";
			$wait_days = 14;
		}
	}

	// select all entities to delete
	$q = 
"SELECT
	id,
	name,
	DATE_FORMAT(last_modified,'%M %e, %Y %r') AS last_modified
FROM
	entity
WHERE
	( state = 'Deleted' AND 
	  last_modified < DATE_SUB(NOW(), INTERVAL ".$wait_days." DAY) ) OR
	( state = 'Pending' AND name = '' AND last_modified < DATE_SUB(NOW(), INTERVAL 2 DAY) )";
	$r = db_query( $q, 'Unable to grab items to delete.' );
	
	while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
	{
		echo '<strong>'.$row['name'].'</strong> was last modified on '.$row['last_modified'].'<br />';
		// delete entity
		$deleted_entities[] = reason_expunge_entity( $row['id'], $user_id );
		echo '<em>Expunged!</em><br />';
		echo '<br />';
	}

	// spit out the list of deleted items
	if(!empty($deleted_entities))
		pray( $deleted_entities );
	else
		echo '<p>No entities expunged</p>';

	echo '<hr />done<br /><br />';
?>
