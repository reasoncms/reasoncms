<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('minisite_templates/nav_classes/default.php');
reason_include_once( 'function_libraries/user_functions.php' );

force_secure_if_available();
$current_user = check_authentication();
$current_user_id = get_user_id( $current_user );
if (!reason_user_has_privs( $current_user_id, 'view_sensitive_data' ) )
{
	die('<!DOCTYPE html><html><head><title>Hierarchical page report</title></head><body><h1>Sorry.</h1><p>You do not have permission to view the hierachical page report.</p><p>Only Reason users who have sensitive data viewing privileges may do that.</p></body></html>');
}


function page_report($entity, $pages_object)
{
	echo '<li><a href="'.$pages_object->get_full_url($entity->id()).'">'.$entity->get_value('name');
	if($entity->get_value( 'url' ))
	{
		echo ' ↗';
	}
	echo '</a>';
	echo ' PT: <a href="'.REASON_HTTP_BASE_PATH.'scripts/page_types/view_page_type_info.php#'.reason_htmlspecialchars($entity->get_value( 'custom_page' )).'">'.$entity->get_value( 'custom_page' ).'</a>';
	$child_ids = $pages_object->children($entity->id());
	if(!empty($child_ids))
	{
		echo '<ul>';
		foreach($child_ids as $child_id)
		{
			if(!empty($pages_object->values[$child_id]))
			{
				page_report($pages_object->values[$child_id], $pages_object);
			}
		}
		echo '</ul>';
	}
	echo '</li>';
}

$num = isset($_REQUEST['num']) ? (integer) $_REQUEST['num'] : 0;

$nums = array(1,5,10,25,50,100,200,500,1000);

echo '<p>Number of sites to report:';
foreach($nums as $number)
{
	echo ' <a href="?num='.$number.'">';
	if($number == $num)
	{
		echo '<strong>'.$number.'</strong>';
	}
	else
	{
		echo $number;
	}
	echo '</a>';
}
echo '</p>';

if(!$num)
{
	die();
}

set_time_limit(3600);
$es = new entity_selector();
$es->add_type(id_of('site'));
$es->add_relation('site_state = "Live"');
$es->set_num($num);
$sites = $es->run_one();

foreach($sites as $site_id=>$site)
{
	echo '<section class="site">';
	echo '<h2>'.$site->get_value('name').'</h2>';
	$pages = new MinisiteNavigation();
	$pages->site_info =& $site;

	//for a bot the order probably does not matter, and adding this line will slow things down
	//$pages->order_by = 'sortable.sort_order'

	$pages->init( $site_id, id_of('minisite_page') );
	if($home_id = $pages->root_node())
	{
		echo '<ul>';
		page_report($pages->values[$home_id], $pages);
		echo '</ul>';
	}
	echo '</section>';
}