<?php
/*
 * This script finds a random page for each of the Reason page types and displays links to those pages.
 * It is useful for testing major changes to Reason to make sure they do not affect obscure
 * page types in adverse ways.
 */

include_once( 'reason_header.php' );
reason_include_once('function_libraries/URL_History.php');
reason_include_once( 'classes/entity_selector.php' );
connectDB( REASON_DB );

reason_include_once( 'function_libraries/user_functions.php' );
if(!on_secure_page())
{ 
	force_secure();
}
$current_user = check_authentication();
if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to get page types.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}

$used_page_types = array();
$q = 'SELECT DISTINCT(pn.custom_page), e.id FROM page_node pn, entity e WHERE pn.id = e.id and e.state = "Live" ORDER BY RAND(), pn.custom_page ASC';
$r = db_query( $q, 'unable to retrieve distinct page types' );
while( $row = mysql_fetch_assoc( $r ) )
{
	if(empty($row[ 'custom_page' ]))
	{
		$page_type = 'no page type specified (default)';
	}
	else
	{
		$page_type = trim($row[ 'custom_page' ]);
	}
	$used_page_types[ $page_type ]  = $row['id'];
}
ksort($used_page_types);
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>'."\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
echo '<title>Reason Page Types</title>'."\n";;
echo '</head>'."\n";
echo '<body>'."\n";
echo '<h1>Sample pages for each page type in Reason</h1>'."\n";
echo '<ol>'."\n";
foreach( $used_page_types AS $type => $page_id )
{
	$site_id = get_owner_site_id( $page_id );
	$url = build_URL( $page_id );
	echo '<li>'.$type.': '.'<a href="http://'.REASON_HOST.$url.'">'.$url.'</a></li>'."\n";
}
echo '</ol>'."\n";
echo '</body></html>';

?>
