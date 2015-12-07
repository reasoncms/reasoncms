<?php
/** find and delete duplicate entities
 *
 * Note: This scipt is not finding any duplicates.  I'm not currently sure if this is because there aren't any, or because there is something "off" about it.
 * In any case, this script should probably be considered experimental until we have it figured out.
 * --Matt Ryan, 2006-06-20
 *
 * @package reason
 * @subpackage scripts
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
<title>Reason: Remove Duplicates</title>
</head>
<body>
<h1>Remove Duplicates</h1>
<?php
if(empty($_POST['do_it']))
{
?>
<form method="post">
<p>When this script is run, it will find all of the entities in Reason that are exact replicas of each other and delete all but one of the replicates.</p>
<p>This script is somewhat slow, and should probably be modified to report on what it found, then asks for the green light to delete duplicates.</p>
<p>This script is currently considered experimental, so it's probably not a good idea to run it on a production instance of Reason.</p>
<input type="submit" name="do_it" value="Run the script" />
</form>
<?php
}
else
{
	connectDB( REASON_DB );
	
	$output = '';

	$ignore_fields = array(
		'id',
		'last_edited_by',
		'last_modified',
		'unique_name'
	);

	// get all types
	$q = "SELECT * FROM entity WHERE type = 1";
	$r = db_query( $q, 'no types' );
	while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
		$types[ $row['id'] ] = $row;
	mysql_free_result( $r );

	// run through all types
	reset( $types );
	while( list( $type_id, $type ) = each( $types ) )
	{
		$duplicates = array();
		$tables = array();
		$fields = array();
		$ids_to_delete = array();

		//echo '<hr />';
		//echo 'type '.$type_id.': '.$type['name'].'<br />';

		// get tables of a type
		$tables = get_entity_tables_by_type( $type_id );

		// get fields of those tables
		reset( $tables );
		while( list( ,$table ) = each( $tables ) )
		{
			$q = "DESC $table";
			$r = db_query( $q, 'could not query table '.$table );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			{
				if( !in_array($row['Field'], $ignore_fields ) )
					$fields[ $row['Field'] ] = $row['Field'];
			}
			mysql_free_result( $r );
		}

		// select all info about an entity, grouping by every field
		// AKA, build that funky query, white boy
		$q = 'SELECT *,count(*) as count FROM '.implode( ',',$tables ).' WHERE type = '.$type_id.' AND';
		reset( $tables );
		while( list( ,$table ) = each( $tables ) )
		{
			// don't match entity table against itself
			if( $table != 'entity' )
				$q .= ' entity.id = '.$table.'.id AND';
		}
		$q = substr( $q, 0, -strlen( 'AND' ) );
		$q .= ' GROUP BY '.implode(',',$fields ).' HAVING count > 1 ORDER BY count';
		// get the duplicates info
		$r = db_query( $q, 'unable to run the group by query' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$duplicates[ $row['id'] ] = $row;
		mysql_free_result( $r );

		// now we have a list of duplicates.  eliminate all but one copy
		reset( $duplicates );
		while( list( $id, $d ) = each( $duplicates ) )
		{
			//echo $d['count'].' '.$d['name'].'<br />';
			// find other IDs to delete
			$q = new DBSelector;
			reset( $tables );
			while( list( ,$t ) = each( $tables ) )
			{
				$q->add_table( $t );
				if( $t != 'entity' )
					$q->add_relation( 'entity.id = '.$t.'.id' );
			}
			$q->add_relation( 'entity.id != '.$id );
			reset( $fields );
			while( list( ,$f ) = each( $fields ) )
			{
				if( $d[$f] )
					$q->add_relation( $f.' = "'.reason_sql_string_escape($d[$f]).'"' );
				else
					$q->add_relation( $f.' IS NULL' );
			}
			$q->add_field( 'entity','id' );
			//echo $q->get_query().'<br /><br />';
			$ids_to_delete = $q->run();
			//pray( $ids_to_delete );
			reset( $ids_to_delete );
			while( list( ,$id_to_delete ) = each ( $ids_to_delete ) )
			{
				$itd = $id_to_delete[ 'id' ];
				// delete id from each table
				reset( $tables );
				while( list( $key,$table ) = each ( $tables ) )
				{
					$q = "DELETE FROM $table WHERE id = '$itd'";
					$output .= $q.";\n";
					//$r = mysql_query( $q ) OR die( 'unable to delete from table '.$table.': '.mysql_error() );
				}
				$q = "DELETE FROM relationship WHERE entity_a = $itd OR entity_b = $itd";
					$output .= $q.";\n";
				//$r = db_query( $q, 'Unable to delete relationships' );
			}
		}
	}
	if(empty($output))
	{
		echo '<p>No duplicates found</p>';
	}
	else
	{
		echo nl2br($output);
	}
}
?>
