<?php
/**
 * This script links to an administrative lister page for each type in reason
 *
 * It can be used to ease the checking of each type after major changes/upgrades
 *
 * @package reason
 * @subpackage scripts
 */

include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
if (!reason_user_has_privs( get_user_id ( $current_user ), 'view_sensitive_data' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to view type listers.</p>');
}

connectDB( REASON_DB );

$type_id = id_of('type');
$site_id = id_of('site');
$user_id = id_of('user');
$master_admin_id = id_of('master_admin');

$site_to_user_id = relationship_id_of('site_to_user');
$site_to_type_id = relationship_id_of('site_to_type');

// This query returns one of every type that is associated with a site and a user_id that has access to that site
//$q = 'SELECT r1.entity_b as user, r3.entity_b as user2, r1.entity_a as site, r2.entity_b as type FROM relationship as r1, relationship as r2, relationship as r3, entity 

$q = 'SELECT DISTINCT (r2.entity_b) as type, r1.entity_a as site, r1.entity_b as user FROM relationship as r1, relationship as r2, entity 
		WHERE r1.type = '.$site_to_user_id.' 
		AND r2.type = '.$site_to_type_id.' 
		
		and r1.entity_a != "0"
		AND r1.entity_a = r2.entity_a 
		AND entity.id = r1.entity_a 
		AND entity.name != "" 
		AND entity.state = "Live" 
		GROUP BY type, site';

$r = db_query( $q, 'error');

$string = array();
$count = array();
$link = array();

$es = new entity_selector();
$es->add_type(id_of('type'));
$result = $es->run_one();

foreach($result as $a_type)
{
	$a_type_name = $a_type->get_value('name');
	$all_types[$a_type_name] = $a_type_name;
}

while ($row=mysql_fetch_assoc($r))
{
	$e1 = new entity($row['site']);
	$e2 = new entity($row['type']);
	$site_name = $e1->get_value('name');
	$type_name = $e2->get_value('name');
	$thelink = '<a href="' . securest_available_protocol() . '://'.REASON_WEB_ADMIN_PATH.'?site_id='.$row['site'].'&type_id='.$row['type'].'&user_id='.$row['user'].'">'.$type_name.'</a>';
	$string[$type_name][] = $site_name;
	$link[$type_name][] = $thelink;
	unset($all_types[$type_name]);
}

foreach ($string as $k=>$v)
{
	$proceed = false;
	$count = count($v);
	while ($proceed == false)
	{
		$num = rand(0, $count - 1);
		if ($v[$num] != 'MASTER ADMIN') $proceed = true;
		else
		{
			if ($count == 1) $proceed = true;
		}
	}
	$count_array[] = ($count);
	$output_array[] = $v[$num];
	$link_output[] = $link[$k][$num];
}
echo '<h3>Type Tester</h3>';
echo '<p>This script will generate a table that links to a random content lister page for each type. This script may be useful to make sure that all types are operational</p>';
echo '<table padding="5px">' . "\n";
echo "<tr>\n";
echo "<th>Site</th>\n";
echo "<th>Type</th>\n";
echo "<th>Number of Sites with Type</th>\n";
echo "</tr>\n";
foreach( $output_array AS $k => $v )
{
	echo "<tr>\n";
	echo "<td>$v</td>\n";
	echo "<td>$link_output[$k]</td>\n";
	echo "<td>$count_array[$k]</td>";
	echo "</tr>\n";
}
echo "</table>\n";

echo '<h3>The following types are not present on a live site to which any user has access</h3>';
pray ($all_types);
?>
