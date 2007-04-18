<?php
/*
 * This script lists all modules and a link to a page on which that module is found.
 * it is useful for finding a page that hosts a particular module so you can troubleshoot
 * and is also useful for taking a quick survey of Reason modules after making a core change
 */

include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/page_types.php' );
reason_include_once( 'function_libraries/URL_History.php' );

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to view modules.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}

connectDB( REASON_DB );
$page_types = $GLOBALS['_reason_page_types'];
$pages = array();
$modules = array();
foreach( $page_types AS $page_type => $type )
{
	if( $page_type != 'default' )
	{
		$type = array_merge( $type, $page_types[ 'default' ] );
	}
	foreach( $type AS $section => $module_info )
	{
		$module = is_array( $module_info ) ? $module_info[ 'module' ] : $module_info;
		if( !empty( $module ) )
		{
			$q = 'SELECT e.id id FROM entity e, page_node p WHERE e.id = p.id AND e.state = "Live" AND p.custom_page = "'.$page_type.'"';
			$r = db_query( $q, 'Unable to get page for this page type' );
			while( $row = mysql_fetch_assoc( $r ) )
			{
				$pages[ $page_type ][ $row['id' ]] = true;
			}
			if( !empty( $pages[ $page_type ] ) )
			{
				$modules[ $module ][ $page_type ] = $pages[ $page_type ];
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
foreach( $modules AS $module => $page_types )
{
	echo "<tr>\n";
	echo "<td>$module</td>\n";
	$page_total = 0;
	$tmp_pages = array();
	foreach( $page_types AS $page_type => $module_pages )
	{
		$page_total += count( $module_pages );
		$tmp_pages = array_merge( array_keys( $module_pages ), $tmp_pages );
	}
	reset( $page_types );
	list( ,$pt ) = each( $page_types );
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
