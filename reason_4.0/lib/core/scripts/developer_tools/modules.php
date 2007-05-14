<?php
/*
 * This script lists all modules and a link to a page on which that module is found.
 * it is useful for finding a page that hosts a particular module so you can troubleshoot
 * and is also useful for taking a quick survey of Reason modules after making a core change
 *
 * updated 5/7/07 Nathan White
 *
 * - allows for substring search of module name
 * - ability to view multiple urls for any module
 * - detail view shows site and page name
 * - some speed improvements (could use more)
 *
 */

include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'minisite_templates/page_types.php' );
reason_include_once( 'classes/entity_selector.php');

//xdebug_start_profiling();
//$s = get_microtime();
$current_user = reason_require_authentication();
if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to view modules.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}

$page_types = $GLOBALS['_reason_page_types'];
$pages = array();
$modules_by_page_type = array();

$es = new entity_selector();
$es->add_type(id_of('minisite_page'));
$es->limit_tables('page_node');
$es->limit_fields('custom_page');
$result = $es->run_one();

$detail_mode = (isset($_REQUEST['detail'])) ? ($_REQUEST['detail'] == 'true') : false;
$module_limiter = (isset($_REQUEST['limit'])) ? turn_into_string($_REQUEST['limit']) : '';
$num = (isset($_REQUEST['num'])) ? turn_into_int($_REQUEST['num']) : 'All';

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
			//echo $module;
			//die;
			if ($detail_mode) $check = ($module == $module_limiter) ? true : false;
			else $check = (empty($module_limiter)) ? true : (strpos($module, $module_limiter) !== false);
			if (isset($reason_page_types[$page_type]) && $check)
			{
				$modules_by_page_type[$module][$page_type] = $reason_page_types[$page_type];
			}
		}
	}
}

$module_limiter = htmlentities($module_limiter); // in case of weird chars

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
		$pages = array_rand(array_flip($pages), count($pages)); // randomize array
		foreach ($pages as $page_id)
		{
			$e = new entity($page_id);
			$site = $e->get_owner();
			if ($site != 0)
			{
				$url = build_URL($page_id);
				if ($url)
				{
					$site_name[] = $site->get_value('name');
					$page_name[] = $e->get_value('name');
					$items[] = '<a href="'.$url.'">'.substr($url,0,50).(strlen($url) > 50 ? '...' : '').'</a>';
					$count++;
				}
			}
			if ($count == $num) break;
		}
	}
	echo '<h3>Detail mode for module ' . $module_limiter . '</h3>';
	echo '<p><a href="'.construct_link().'">View all modules</a></p>';
	if (!empty($items))
	{
		$item_count = count($items);
		$total_count = ($num == 'All') ? $item_count : count($pages);
		$approx = ($total_count == $item_count) ? '' : ' (approx)';
		echo '<p>'.$item_count.' valid URLs shown for module ' . $module_limiter .'</p>';
		if ($total_count > 9) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module_limiter, 'num'=>10)) . '" title="10 URLs for module ' . $module_limiter . '">10</a>';
		if ($total_count > 24) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module_limiter, 'num'=>25)) . '" title="25 URLs for module ' . $module_limiter . '">25</a> ';
		if ($total_count > 99) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module_limiter, 'num'=>100)) . '" title="100 URLs for module ' . $module_limiter . '">100</a> ';
		if ($total_count > 199) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module_limiter, 'num'=>'')) . '" title="All URLs for module ' . $module_limiter . '">All ' . $total_count . $approx.'</a> - high database load - not recommended';
		else $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module, 'num'=>'')) . '" title="All URLs for module ' . $module_limiter . '">All ' . $total_count . $approx.'</a>';
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
		echo '<p>No page URLs could be found for module ' . $module_limiter . '</p>';
		echo '<p><a href="'.construct_link().'">View all modules</a></p>';
	}
}
else
{
	if ($module_limiter)
	{
		echo '<h3>Modules limited by substring ' . $module_limiter . '</h3>';
		echo '<p><a href="'.construct_link().'">View all modules</a></p>';
	}
	else echo '<h3>All modules</h3>';
	show_filter($module_limiter);
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
			$tmp_pages = array_merge( array_keys( $module_pages ), $tmp_pages );
		}
		echo "<td>$page_total</td>\n";
		$count = count($tmp_pages);
		while (empty($url) && ($count > 0))
		{
			$key = rand( 0, $count - 1 );
			$url = build_URL($tmp_pages[$key]);
			unset ($tmp_pages[$key]);
			$count = count($tmp_pages);
		}
		if (!$detail_mode)
		{
			echo '<td>';
			echo '<a href="'.$url.'">'.substr($url,0,50).(strlen($url) > 50 ? '...' : '').'</a></td>'."\n";
			echo '<td>';
			if ($page_total > 9) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module, 'num'=>10)) . '" title="10 URLs for module ' . $module_limiter . '">10</a>';
			if ($page_total > 24) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module, 'num'=>25)) . '" title="50 URLs for module ' . $module_limiter . '">25</a> ';
			if ($page_total > 99) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module, 'num'=>100)) . '" title="50 URLs for module ' . $module_limiter . '">100</a> ';
			if ($page_total < 200) $link[] = '<a href="' . make_link(array('detail' => 'true', 'limit' => $module, 'num'=>'')) . '" title="All URLs for module ' . $module_limiter . '">All</a>';
			echo implode(" | ", $link);
			echo '</td>';
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
}

function show_filter($limit = '')
{
	echo '<form method="post" action="'.construct_link().'">';
	echo '<p>Limit by module substring: <input type="text" name="limit" value="'.$limit.'"></p>';
	echo '<p><input type="submit" name="submit" value="Search"></p>';
}

//echo 'time taken - ' . (get_microtime() - $s) . ' seconds';
//xdebug_dump_function_profile(4);
?>
