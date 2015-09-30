<?php
/**
 * This script shows info on how many page types you have, how many are in use, whether they are core or local, 
 * and provides a random link for each page type. It uses the db table admin framework to provide basic filtering.
 *
 * It is useful for testing major changes to Reason to make sure they do not affect obscure
 * page types in adverse ways, or for finding obsolete or little used page types.
 *
 * -- Updated 5/20/09 integration with table admin, report on #s, reduced false positives, uses entity selector API
 *
 * @author Nathan White 
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'minisite_templates/page_types.php' );
reason_include_once( 'classes/page_types.php' );
include_once( CARL_UTIL_INC . 'db/table_admin.php' );

if (reason_require_authentication() && !reason_check_privs( 'view_sensitive_data' ))
{
	die('<h1>Sorry.</h1><p>You do not have permission to view page types.</p></body></html>');
}

echo '<!DOCTYPE html>'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>'."\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
echo '<title>Reason Page Types</title>'."\n";
echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'css/forms/form_data.css" />'."\n";
echo '</head>'."\n";
echo '<body>'."\n";
echo '<h2>Page Type Information</h2>';
echo '<p>This table shows information about each page type defined in the Reason instance. For each page type that is assigned to a live page,
         a random url is generated. This module can help you verify that page types are working properly, or to identify page types that are
         not being used and should perhaps be deleted.</p>';

$es = new entity_selector();
$es->add_type(id_of('site'));
$es->limit_tables();
$sites = $es->run_one();
$es = new entity_selector();
$es->add_type(id_of('site_type_type'));
$es->limit_tables();
$site_types = $es->run_one();
$es = new entity_selector();
$es->add_type(id_of('theme_type'));
$es->limit_tables();
$themes = $es->run_one();
echo '<form action="?" method="get">'."\n";
echo '<p><label for="sitePicker">Site:</label> <select id="sitePicker" name="site_id">';
echo '<option value="">All</option>';
foreach($sites as $id => $site)
{
	echo '<option value="'.$id.'"'.($id == $_REQUEST['site_id'] ? ' selected="selected"' : '').'>'.strip_tags($site->get_value('name')).'</option>';
}
echo '</select></p>';
echo '<p><label for="siteTypePicker">Site Type:</label> <select id="siteTypePicker" name="site_type_id">';
echo '<option value="">All</option>';
foreach($site_types as $id => $site_type)
{
	echo '<option value="'.$id.'"'.($id == $_REQUEST['site_type_id'] ? ' selected="selected"' : '').'>'.strip_tags($site_type->get_value('name')).'</option>';
}
echo '</select></p>';
echo '<p><label for="themePicker">Theme:</label> <select id="themePicker" name="theme_id">';
echo '<option value="">All</option>';
foreach($themes as $id => $theme)
{
	echo '<option value="'.$id.'"'.($id == $_REQUEST['theme_id'] ? ' selected="selected"' : '').'>'.strip_tags($theme->get_value('name')).'</option>';
}
echo '</select></p>';
echo '<input type="submit" value="Submit">';
echo '</form>'."\n";

$site_ids = NULL;
if(!empty($_REQUEST['site_id']) || !empty($_REQUEST['site_type_id']) || !empty($_REQUEST['theme_id']))
{
	$es = new entity_selector();
	$es->add_type(id_of('site'));
	$es->limit_tables();
	$es->limit_fields();
	if(!empty($_REQUEST['site_id']))
		$es->add_relation('`entity`.`id` = "'.(integer) $_REQUEST['site_id'].'"');
	if(!empty($_REQUEST['site_type_id']))
		$es->add_left_relationship((integer) $_REQUEST['site_type_id'], relationship_id_of('site_to_site_type'));
	if(!empty($_REQUEST['theme_id']))
		$es->add_left_relationship((integer) $_REQUEST['theme_id'], relationship_id_of('site_to_theme'));
	$sites = $es->run_one();
	$site_ids = array_keys($sites);
	if(empty($site_ids))
		$site_ids = array(0);
}
// grab all the pages along with their page type
$es = new entity_selector($site_ids);
$es->add_type(id_of('minisite_page'));
$es->limit_tables(array('page_node', 'url'));
$es->limit_fields('entity.name, page_node.custom_page, page_node.url_fragment, url.url');
$es->add_right_relationship_field( 'owns', 'entity' , 'id' , 'owner_id' );
$es->add_left_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
// we add some relations so that we grab only valid pages with names that are not custom url pages
$es->add_relation('(entity.name != "") AND ((url.url = "") OR (url.url IS NULL))');
$result = $es->run_one();
shuffle($result); // we lose ids due to the shuffle but we don't care

if(empty($result))
{
	echo '<h3>No results.</h3>';
	echo '</body>';
	echo '</html>';
	die();
}

$rpt =& get_reason_page_types();

// lets parse the entities and build our data set
foreach ($result as $page)
{
	$page_type = (trim($page->get_value('custom_page'))) ? trim($page->get_value('custom_page')) : 'default'; // no page type is considered default by reason
	if (!isset($data_pages[$page_type]['page_type'])) $data_pages[$page_type]['page_type'] = $page_type;
	if (!isset($data_pages[$page_type]['location']))
	{
		$pt = @$rpt->get_page_type($page_type);
		$data_pages[$page_type]['location'] = (is_object($pt) ? $pt->get_location() : "");
	}	
	if (!isset($data_pages[$page_type]['url'])) $data_pages[$page_type]['url'] = '<a href="'.reason_get_page_url($page).'">'.reason_get_page_url($page).'</a>';
	if (!isset($data_pages[$page_type]['count'])) $data_pages[$page_type]['count'] = 1;
	else $data_pages[$page_type]['count']++;
}

// lets add page_types that do not exist
$page_types_no_pages = array_diff(array_values($rpt->get_page_type_names()), array_keys($data_pages));
foreach ($page_types_no_pages as $page_type_no_page)
{
	$data_pages[$page_type_no_page]['page_type'] = $page_type_no_page;
	$pt = @$rpt->get_page_type($page_type_no_page);
	$data_pages[$page_type_no_page]['location'] = (is_object($pt) ? $pt->get_location() : "");
	$data_pages[$page_type_no_page]['url'] = '';
	$data_pages[$page_type_no_page]['count'] = 0;
}

// let's sort our data set
$sort_field = (isset($_GET['table_sort_field']) && check_against_array($_GET['table_sort_field'], array('page_type', 'count', 'url', 'location')))
			  ? $_GET['table_sort_field']
			  : 'count';
			  
$sort_order = (isset($_GET['table_sort_order']) && check_against_array($_GET['table_sort_order'], array('desc', 'asc')))
			  ? $_GET['table_sort_order']
			  : 'desc';

// do our manual sorting
$sort_func = "_sort_".$sort_field."_".$sort_order;
uasort($data_pages, $sort_func);

$table_admin = new TableAdmin();
$table_admin->set_show_actions_first_cell(false);
$table_admin->set_fields_to_entity_convert(array('count','page_type','location'));
$table_admin->init_from_array( $data_pages, true );
$table_admin->run();

echo '</body>';
echo '</html>';


function _sort_count_desc($a, $b)
{
	if ( $a['count'] == $b['count'] ) return 0;
	return ( $a['count'] > $b['count'] ) ? -1 : 1;
}

function _sort_count_asc($a, $b)
{
	if ( $a['count'] == $b['count'] ) return 0;
	return ( $a['count'] < $b['count'] ) ? -1 : 1;
}

function _sort_url_desc($a, $b)
{
	return (strcasecmp($b['url'], $a['url']));
}

function _sort_url_asc($a, $b)
{
	return (strcasecmp($a['url'], $b['url']));
}

function _sort_page_type_desc($a, $b)
{
	return (strcasecmp($b['page_type'], $a['page_type']));
}

function _sort_page_type_asc($a, $b)
{	 	
	return (strcasecmp($a['page_type'], $b['page_type']));
}

function _sort_location_desc($a, $b)
{
	return (strcasecmp($b['location'], $a['location']));
}

function _sort_location_asc($a, $b)
{	 	
	return (strcasecmp($a['location'], $b['location']));
}
?>