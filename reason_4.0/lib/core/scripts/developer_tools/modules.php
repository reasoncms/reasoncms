<?php
/*
 * This script lists all modules and a link to a page on which that module is found.
 * it is useful for finding a page that hosts a particular module so you can troubleshoot
 * and is also useful for taking a quick survey of Reason modules after making a core change
 */

include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'minisite_templates/page_types.php' );
reason_include_once( 'classes/entity_selector.php');

$current_user = reason_require_authentication();
if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to view modules.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}

$page_types = $GLOBALS['_reason_page_types'];
$pages = array();
$modules = array();

$es = new entity_selector();
$es->add_type(id_of('minisite_page'));
$es->limit_tables('page_node');
$es->limit_fields('custom_page');
$result = $es->run_one();

foreach ($result as $k=>$mypage)
{
	$page_type_value = $mypage->get_value('custom_page');
	if (empty($page_type_value)) $page_type_value = 'default';
	$reason_page_types[$page_type_value][$k] = 'true';
}

foreach( $page_types AS $page_type => $type )
{
	foreach( $type AS $section => $module_info )
	{
		$module = is_array( $module_info ) ? $module_info[ 'module' ] : $module_info;
		if( !empty( $module ) )
		{
			if (isset($reason_page_types[$page_type]))
			{
				$modules[$module][$page_type] = $reason_page_types[$page_type];
			}
		}
	}
}

echo "<table>\n";
echo "<tr>\n";
echo "<th>Module</th>\n";
echo "<th>Pages using this module (approx)</th>\n";
echo "<th>Random Page Using this module</th>\n";
echo "</tr>\n";
foreach( $modules AS $module => $my_page_types )
{
	echo "<tr>\n";
	echo "<td>$module</td>\n";
	$page_total = 0;
	$tmp_pages = array();
	foreach( $my_page_types AS $page_type => $module_pages )
	{
		$page_total += count( $module_pages );
		$tmp_pages = array_merge( array_keys( $module_pages ), $tmp_pages );
	}
	reset( $my_page_types );
	list( ,$pt ) = each( $my_page_types );
	reset( $pt );
	list( $page_id, ) = each( $pt );
	echo "<td>$page_total</td>\n";
	$url = build_URL($tmp_pages[ rand( 0, count( $tmp_pages ) - 1 ) ]);
	echo '<td>';
	echo '<a href="'.$url.'">'.substr($url,0,50).(strlen($url) > 50 ? '...' : '').'</a></td>'."\n";
	echo "</tr>\n";
}
echo "</table>\n";

?>
