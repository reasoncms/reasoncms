<?php
/**
 * Reports on some general statistics about the current Reason instance
 *
 * Includes:
 * - The entity ID that Reason has incremented up to
 * - The total number of entities
 * - The relationship ID that Reason has incremented up to
 * - The total number of relationships in Reason
 * - The most active users
 * - the top types in numbers of entities
 * - The most recently created, edited, and deleted entities
 * - The least recently edited entities
 * - and more
 *
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');

/**
 * Begin the page
 */
?><!DOCTYPE html>
<html><head><title>Reason Stats</title>
<?php
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
?>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="none" />
</head>
<body>

<h3>Reason Stats</h3>
<?php

	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'view_sensitive_data' ) )
	{
		die('<p>You do not have permission to view Reason stats.</p><p>Only Reason users who have sensitive data viewing privileges may do that.</p></body></html>');
	}
	
	if (!THIS_IS_A_DEVELOPMENT_REASON_INSTANCE && (!isset($_REQUEST['run_anyway']) || ($_REQUEST['run_anyway'] != 1)))
	{
		echo '<h4>Before we do this...</h4><p>This script is really intensive, and should really only be run on a development instance so that it doesn\'t disrupt a production instance of Reason.</p>';
		echo '<p>If your Reason database is large, you should import your database to a development instance of Reason and run the script there. If your instance of Reason ';
		echo 'is small, it is probably safe to ignore this warning.</p>';
		echo '<a href="?run_anyway=1">Run this script despite the warning.</a></p>';
		die;
	}
	
	connectDB( REASON_DB );

	// counts of things
	$single_res_queries = array(
		array(
			'q' => 'SELECT MAX(id) AS max_id FROM entity',
			'name' => 'Entity Auto Increment ID',
			'var' => 'max_id'
		),
		array(
			'q' => 'SELECT COUNT(*) AS count FROM entity',
			'name' => 'Number of Entities',
			'var' => 'count'
		),
		array(
			'q' => 'SELECT MAX(id) AS max_id FROM relationship',
			'name' => 'Relationship Auto Increment ID',
			'var' => 'max_id'
		),
		array(
			'q' => 'SELECT COUNT(*) AS count FROM relationship',
			'name' => 'Number of Relationships',
			'var' => 'count'
		)
	);
	$num = isset( $_REQUEST[ 'num' ] ) ? $_REQUEST[ 'num' ] : 5;
	reset( $single_res_queries );
	while( list( , $info ) = each( $single_res_queries ) )
	{
		$r = db_query( $info['q'], 'Unable to get '.$info['name'] );
		$row = mysql_fetch_array( $r, MYSQL_ASSOC );
		mysql_free_result( $r );
		echo $info['name'].': '.$row[ $info['var'] ].'<br /><br />';
	}

	$multiple_result_queries = array(
		array(
			'q' => 'SELECT user.name AS username, COUNT(*) AS number_of_entities_last_edited FROM entity AS user, entity AS e WHERE e.last_edited_by = user.id AND e.state = "Live" GROUP BY e.last_edited_by ORDER BY number_of_entities_last_edited DESC LIMIT ' . $num,
			'name' => 'Active Users (Top '.$num.')'
		),
		array(
			'q' => 'SELECT type.name AS type, COUNT(e.name) AS number_of_entities FROM entity AS type LEFT JOIN entity AS e ON e.type = type.id WHERE type.type = 1 AND e.state = "Live" GROUP BY type.id ORDER BY number_of_entities DESC LIMIT ' . $num,
			'name' => 'Number of Entities by Type (Top '.$num.')'
		),
		array(
			//'q' => 'SELECT type.name AS type, COUNT(*) AS number_of_entities FROM entity AS type, entity AS e WHERE e.type = type.id GROUP BY e.type ORDER BY number_of_entities ASC LIMIT 5',
			'q' => 'SELECT type.name AS type, COUNT(e.name) AS number_of_entities FROM entity AS type LEFT JOIN entity AS e ON e.type = type.id WHERE type.type = 1 AND e.state = "Live" GROUP BY type.id ORDER BY number_of_entities ASC LIMIT ' . $num,
			'name' => 'Number of Entities by Type (Bottom '.$num.')'
		),
		array(
			'q' => 'SELECT e.id,e.name,type.name as type,user.name as last_edited_by,DATE_FORMAT(e.creation_date,"%M %e, %Y %r") as creation_date FROM entity as e, entity as type, entity as user WHERE e.type = type.id AND e.last_edited_by = user.id AND e.state = "Live" ORDER BY e.creation_date DESC LIMIT ' . $num,
			'name' => 'Recently Created'
		),
		array(
			'q' => 'SELECT e.id,e.name,type.name as type,user.name as last_edited_by,DATE_FORMAT(e.last_modified,"%M %e, %Y %r") as last_modified FROM entity as e, entity as type, entity as user WHERE e.type = type.id AND e.last_edited_by = user.id AND e.state = "Live" ORDER BY e.last_modified DESC LIMIT ' . $num,
			'name' => 'Recently Edited'
		),
		array(
			'q' => 'SELECT e.id,e.name,type.name as type,user.name as last_edited_by,DATE_FORMAT(e.last_modified,"%M %e, %Y %r") as last_modified FROM entity as e, entity as type, entity as user WHERE e.type = type.id AND e.last_edited_by = user.id AND e.state = "Live" ORDER BY e.last_modified ASC LIMIT ' . $num,
			'name' => 'Not Recently Edited'
		),
		array(
			'q' => 'SELECT ar.name, e1.name AS type_a, e2.name as type_b, count(*) AS number_of_relationships FROM allowable_relationship AS ar, relationship AS r, entity AS e1, entity AS e2 WHERE r.type = ar.id AND ar.relationship_a = e1.id AND ar.relationship_b = e2.id GROUP BY r.type ORDER BY number_of_relationships DESC LIMIT ' . $num,
			'name' => 'Number of Relationships by Type (with ownership)'
		),
		array(
			'q' => 'SELECT ar.name, e1.name AS type_a, e2.name as type_b, count(*) AS number_of_relationships FROM allowable_relationship AS ar, relationship AS r, entity AS e1, entity AS e2 WHERE r.type = ar.id AND ar.relationship_a = e1.id AND ar.relationship_b = e2.id AND ar.name != "owns" GROUP BY r.type ORDER BY number_of_relationships DESC LIMIT ' . $num,
			'name' => 'Number of Relationships by Type (without ownership)'
		),
		array(
			'q' => 'SELECT state, count(*) AS Count FROM entity GROUP BY state ORDER BY Count DESC',
			'name' => 'Entities by State',
		),
	);
	reset( $multiple_result_queries );
	while( list( , $mrq ) = each( $multiple_result_queries ) )
	{
		$first_row = true;
		echo '<strong>'.$mrq['name'].'</strong><br /><br />';
		echo '<table border="1" cellpadding="5">';
		$r = db_query( $mrq['q'], 'Unable to '.$mrq['name'] );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
		{
			if( $first_row )
			{
				echo '<tr>';
				reset( $row );
				while( list( $key, ) = each( $row ) )
					echo '<th>'.prettify_string($key).'</th>';
				echo '</tr>';
				$first_row = false;
			}
			echo '<tr>';
			reset( $row );
			while( list( $key, $val ) = each( $row ) )
				echo '<td>'.$val.'</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '<br />';
	}
//	echo '<strong>Session Variables</strong>';
//	pray( $_SESSION );
?>
</body>
</html>
