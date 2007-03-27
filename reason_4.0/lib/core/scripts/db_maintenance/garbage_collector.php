<?php
	// reason garbage collecter
	// runs periodically and deletes all entities marked for deletion at least one week ago.
	// dave hendler 12/3/02
	
	include_once( 'reason_header.php' );
	include_once( CARL_UTIL_INC . 'db/db.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	connectDB( REASON_DB );

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
	  last_modified < DATE_SUB(NOW(), INTERVAL 14 DAY) ) OR
	( state = 'Pending' AND name = '' AND last_modified < DATE_SUB(NOW(), INTERVAL 2 DAY) )";
	$r = db_query( $q, 'Unable to grab items to delete.' );
	while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
	{
		echo '<strong>'.$row['name'].'</strong> was last modified on '.$row['last_modified'].'<br />';
		// delete entity
		$deleted_entities[] = delete_entity( $row['id'] );
		echo '<em>Deleted!</em><br />';
		echo '<br />';
	}

	// spit out the list of deleted items
	pray( $deleted_entities );

	echo '<hr />done<br /><br />';
?>
