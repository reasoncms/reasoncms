<?php
/**
 * This script reports on any sites which have more than one root page.
 *
 * @package reason
 * @subpackage scripts
 * @author Nathan White
 */

include_once( 'reason_header.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = reason_check_authentication();
if (!reason_user_has_privs( get_user_id ( $current_user ), 'db_maintenance' ) )
{
	die('<html><head><title>Reason: Find Extra Root Pages</title></head><body><h1>Sorry.</h1><p>You do not have permission to find extra root pages.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
}

?>
<html>
<head>
<title>Reason: Find Extra Root Pages</title>
</head>
<body>
<h1>Find Extra Root Pages</h1>
<?php
if(empty($_POST['do_it']))
{
?>
<form method="post">
<p>When this script is run, it will check your sites and report on those that have more than one root page.</p>
<p>You should edit those sites and delete the extra root page(s) to ensure that your site works properly.</p>
<input type="submit" name="do_it" value="Run the script" />
</form>
<?php
}
else
{
	$report = '';
	
	// first find all sites
	$es = new entity_selector();
	$es->add_type(id_of('site'));
	$es->limit_tables();
	$es->limit_fields();
	$result = $es->run_one();
	$site_ids = array_keys($result);
	
	foreach ($site_ids as $site_id)
	{
		$es = new entity_selector($site_id);
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables();
		$es->limit_fields();
		$meta = $es->add_right_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
		$str = $meta['parent_id']['table'] . "." . $meta['parent_id']['field'];
		$es->add_relation('entity.id = ' . $str);
		$result = $es->run_one();
		$count = count($result);
		if ($count > 1)
		{
			$site = new entity($site_id);
			$report .= '<h3>The site "'. $site->get_value('name') . '" has ' . $count . ' root pages.</h3>';
			$report .= '<ul>';
			foreach ($result as $page)
			{
				$last_mod = $page->get_value('last_modified');
				$page_name = $page->get_value('name');
				$page_name = (!empty($page_name)) ? $page_name : '<em>Untitled</em>';
				$report .= '<li>'. $page_name . ' (last modified ' . prettify_mysql_datetime($last_mod) . ')</li>';
			}
			$report .= '</ul>';
		}
	}

	if (!empty($report))
	{
		echo '<h2>Found sites with multiple root pages</h2>';
		echo $report;
		echo '<p><strong>You should go into the site editing interface, edit pages, choose "list view", and delete the rogue root page(s).</strong></p>';
	}
}
?>
</body>
</html>