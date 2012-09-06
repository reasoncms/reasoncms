<?php
/**
 * Find which pages use which modules
 *
 * This script lists all modules and a link to a page on which that module is found.
 * it is useful for finding a page that hosts a particular module so you can troubleshoot
 * and is also useful for taking a quick survey of Reason modules after making a core change
 *
 * updated 5/7/07 Nathan White
 * - allows for substring search of module name
 * - ability to view multiple urls for any module
 * - detail view shows site and page name
 * - some speed improvements (could use more)
 *
 * updated 1/10/2008 to add limiting by core/local file location
 * not that when limited to "core" modules, the actual page when being tested would use the "local" 
 * version of the module if available and the files are titled the same.
 *
 * updated 5/6/2009 modified to use reasonPageURL class and filter out more invalid pages (ie those with url.url populated)
 *
 * updated 2/14/2012 changed to not use array_merge which is super slow - yielding a huge performance improvement.
 *
 * @author Nathan White
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'classes/page_types.php' );
reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'classes/url/page.php' );
include_once( CARL_UTIL_INC . 'basic/misc.php' );

echo '<!DOCTYPE html>'."\n";
echo '<html><head><title>Reason: Modules</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body>';

//if (!carl_is_php5()) xdebug_start_profiling();
$current_user = reason_require_authentication();
if (!reason_user_has_privs( get_user_id ( $current_user ), 'view_sensitive_data' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to view modules.</p>');
}

$pages = array();
$modules_by_page_type = array();

$es = new entity_selector();
$es->add_type(id_of('minisite_page'));
$es->limit_tables(array('page_node', 'url'));
$es->limit_fields('entity.name, page_node.custom_page, page_node.url_fragment, url.url');
$es->add_right_relationship_field( 'owns', 'entity' , 'id' , 'owner_id' );
$es->add_right_relationship_field( 'owns', 'entity', 'name', 'site_name' );
$es->add_left_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
// we add some relations so that we grab only valid pages with names that are not custom url pages
$es->add_relation('(entity.name != "") AND ((url.url = "") OR (url.url IS NULL))');
$result = $es->run_one();

$builder = new reasonPageURL();
$builder->provide_page_entities($result);

$request = carl_get_request();
$detail_mode = (isset($request['detail'])) ? ($request['detail'] == 'true') : false;
$module_limiter = (isset($request['limit'])) ? conditional_stripslashes(turn_into_string($request['limit'])) : '';
$detail_limiter = (isset($request['detail_limit'])) ? conditional_stripslashes(turn_into_string($request['detail_limit'])) : '';

$core_local_limiter = (isset($request['core_local_limit'])) 
					  ? check_against_array($request['core_local_limit'], array('core', 'local')) 
					  : '';					  
$num = (isset($request['num'])) ? turn_into_int($request['num']) : 'All';

if (isset($request['reset']))
{
	header("Location: " . carl_make_redirect(array('limit' => '', 'core_local_limit' => '')));
	exit();
}

// Make an array with first dimension of page type name, second dimension of every page
// ID using the pt, third dimension 'true' for every page type returned by the query.
foreach ($result as $k=>$mypage)
{
	$page_type_value = $mypage->get_value('custom_page');
	if (empty($page_type_value)) $page_type_value = 'default';
	$reason_page_types[$page_type_value][$k] = 'true';
}


$rpts =& get_reason_page_types();
$all_page_types = $rpts->get_page_types();
foreach ($all_page_types as $page_type_name => $pt_obj)
{
	$regions = $pt_obj->get_region_names();
	foreach ($regions as $region)
	{
		$region_info = $pt_obj->get_region($region);
		if (!empty($region_info['module_name']))
		{
			if ($detail_mode) 
				$check = ($region_info['module_name'] == $detail_limiter) ? true : false;
			else 
				$check = (empty($module_limiter)) ? true : (stripos($region_info['module_name'], $module_limiter) !== false);
			if (isset($reason_page_types[$page_type_name]) && $check)
			{
				if (empty($core_local_limiter) || module_location_is_acceptable($region_info['module_name'], $core_local_limiter))
				{
					
					$modules_by_page_type[$region_info['module_name']][$page_type_name] = $reason_page_types[$page_type_name];
					
				}
			}
		}
	}
}



$module_limiter = reason_htmlspecialchars($module_limiter); // in case of weird chars - parse the limiter since we are going to display it
$detail_limiter = reason_htmlspecialchars($detail_limiter); // in case of weird chars - parse the limiter since we are going to display it

if ($detail_mode)
{
	$pages = array();
	$items = $count = '';
	foreach ($modules_by_page_type as $module => $my_page_types )
	{
		
		foreach ($my_page_types as $page_type => $module_pages)
		{
			$pages = array_merge (array_keys($module_pages), $pages);
		}
		shuffle($pages); // randomize array
		foreach ($pages as $page_id)
		{
			$page =& $result[$page_id];
			$builder->set_id($page_id);
			$url = $builder->get_url();
			if ($url)
			{
				$site_name[] = $page->get_value('site_name');
				$page_name[] = $page->get_value('name');
				$items[] = '<a href="'.$url.'">'.substr($url,0,50).(strlen($url) > 50 ? '...' : '').'</a>';
				$count++;
			}
			if ($num != 'All' && $count >= $num) break;
		}
	}
	echo '<h3>Detail mode for module ' . $detail_limiter . '</h3>';
	echo '<p><a href="'.carl_make_link(array('num' => '', 'detail'=>'', 'detail_limit' => '')).'">Return to Summary View</a></p>';
	if (!empty($items))
	{
		$item_count = count($items);
		$total_count = ($num == 'All') ? $item_count : count($pages);
		$approx = ($total_count == $item_count) ? '' : ' (approx)';
		echo '<p>'.$item_count.' valid URLs shown for module ' . $detail_limiter .'</p>';
		if ($total_count > 9) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'detail_limit' => $detail_limiter, 'num'=>10)) . '" title="10 URLs for module ' . $detail_limiter . '">10</a>';
		if ($total_count > 24) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'detail_limit' => $detail_limiter, 'num'=>25)) . '" title="25 URLs for module ' . $detail_limiter . '">25</a> ';
		if ($total_count > 99) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'detail_limit' => $detail_limiter, 'num'=>100)) . '" title="100 URLs for module ' . $detail_limiter . '">100</a> ';
		if ($total_count > 199) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'detail_limit' => $detail_limiter, 'num'=>'')) . '" title="All URLs for module ' . $detail_limiter . '">All ' . $total_count . $approx.'</a> - high database load - not recommended';
		else $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'detail_limit' => $detail_limiter, 'num'=>'')) . '" title="All URLs for module ' . $detail_limiter . '">All ' . $total_count . $approx.'</a>';
		echo '<p>Number to show: '.implode(" | ", $link) . '</p>';
			
		echo '<table border="1" cellpadding="2" cellspacing="0">'."\n";
		echo "<tr>\n";
		echo "<th>Site Name</th>\n";
		echo "<th>Page Name</th>\n";
		echo "<th>Page URL</th>\n";
		echo "</tr>\n";
		foreach($items as $k=>$v)
		{
			echo '<tr>';
			echo '<td>'.$site_name[$k].'</td>' . "\n";
			echo '<td>'.$page_name[$k].'</td>' . "\n";
			echo '<td>'.$items[$k].'</td>' . "\n";
			echo '</tr>';
		}
		echo '</table>';
	}
	else
	{
		echo '<p>No page URLs could be found for module ' . $detail_limiter . '</p>';
	}
}
else
{
	$module_location_text = ($core_local_limiter) ? ucfirst($core_local_limiter) . ' modules' : 'All modules';
	$module_limit_text = ($module_limiter) ? ' limited by substring ' . $module_limiter : '';
	echo '<h3>'.$module_location_text . $module_limit_text.'</h3>';
	show_filter($module_limiter, $core_local_limiter);
	if (empty($modules_by_page_type))
	{
		echo '<hr /><p>No results to show</p><hr />';
		die;
	}
	echo '<table border="1" cellpadding="2" cellspacing="0">'."\n";
	echo "<tr>\n";
	echo "<th>Module</th>\n";
	echo "<th>Pages using this module (approx)</th>\n";
	echo "<th>Random Page Using this module</th>\n";
	echo "<th>More URLs</th>";
	echo "</tr>\n";

	foreach( $modules_by_page_type AS $module => $my_page_types )
	{
		echo "<tr>\n";
		echo "<td>$module</td>\n";
		$page_total = $url = $link = '';
		$tmp_pages = array();
		foreach( $my_page_types AS $page_type => $module_pages )
		{
			$page_total += count( $module_pages );
			$keys = array_keys($module_pages);
			foreach ($keys as $id)
			{
				if (!isset($tmp_pages[$id]))
				{
					$tmp_pages[$id] = $id;
				}
			}
		}
		echo "<td>$page_total</td>\n";
		shuffle($tmp_pages);
		while (empty($url) && (!empty($tmp_pages)))
		{
			$page_id = array_pop($tmp_pages);
			if (isset($result[$page_id]))
			{
				$builder->set_id($page_id);
				$url = $builder->get_url();
			}
		}
		if (!$detail_mode)
		{
			echo '<td>';
			echo '<a href="'.$url.'">'.substr($url,0,50).(strlen($url) > 50 ? '...' : '').'</a></td>'."\n";
			echo '<td>';
			if ($page_total > 9) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'limit' => $module_limiter, 'detail_limit' => $module, 'num'=>10)) . '" title="10 URLs for module ' . $module . '">10</a>';
			if ($page_total > 24) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'limit' => $module_limiter, 'detail_limit' => $module, 'num'=>25)) . '" title="50 URLs for module ' . $module . '">25</a> ';
			if ($page_total > 99) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'limit' => $module_limiter, 'detail_limit' => $module, 'num'=>100)) . '" title="50 URLs for module ' . $module . '">100</a> ';
			if ($page_total < 200) $link[] = '<a href="' . carl_make_link(array('detail' => 'true', 'limit' => $module_limiter, 'detail_limit' => $module, 'num'=>'')) . '" title="All URLs for module ' . $module . '">All</a>';
			echo implode(" | ", $link);
			echo '</td>';
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo '</body></html>';
}

// make sure the module file exists at the specified location
function module_location_is_acceptable($name, $location)
{
	$file = REASON_INC.'lib/'.$location.'/minisite_templates/modules/'.$name.'.php';
	$file2 = REASON_INC.'lib/'.$location.'/minisite_templates/modules/'.$name.'/module.php';
	return (file_exists($file) || file_exists($file2));
}

function show_filter($string_limit = '', $location_limit = '')
{
	if ($location_limit != 'core') $options[] = '<a href="'.carl_make_link(array('core_local_limit' => 'core', 'limit' => $string_limit)).'">Core</a>';
	else $options[] = 'Core';
	if ($location_limit != 'local') $options[] = '<a href="'.carl_make_link(array('core_local_limit' => 'local', 'limit' => $string_limit)).'">Local</a>';
	else $options[] = 'Local';
	if ( ($location_limit == 'local') || ($location_limit == 'core') ) $options[] = '<a href="'.carl_make_link(array('core_local_limit' => '', 'limit' => $string_limit)).'">Any</a>';
	else $options[] = 'Any';
	echo '<p>Filter by module location: ' . implode(" | ", $options) . '</p>';
	echo '<form method="post" action="'.carl_make_link(array('core_local_limit' => $location_limit)).'">';
	echo '<p>Filter by module substring: <input type="text" name="limit" value="'.$string_limit.'"></p>';
	$reset_button = ($string_limit || $location_limit)
					? ' <input type="submit" name="reset" value="Clear Filters">' 
					: '';
	echo '<p><input type="submit" name="submit" value="Search">'.$reset_button.'</p>';
}


//echo 'time taken - ' . (get_microtime() - $s) . ' seconds';
//if (!carl_is_php5()) xdebug_dump_function_profile(4);
?>
