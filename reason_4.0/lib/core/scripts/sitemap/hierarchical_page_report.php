<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('minisite_templates/nav_classes/default.php');
reason_include_once( 'function_libraries/user_functions.php' );

function page_report($entity, $pages_object)
{
	echo '<li><a href="'.$pages_object->get_full_url($entity->id()).'">'.$entity->get_value('name');
	$is_link = false;
	// is this page a link?
	if($entity->get_value( 'url' ))
	{
		$is_link = true;
		echo ' ↗';
	}
	echo '</a>';
	if (!$is_link) {
		echo '<span class="tag">Page Type: <a href="'.REASON_HTTP_BASE_PATH.'scripts/page_types/view_page_type_info.php#'.reason_htmlspecialchars($entity->get_value( 'custom_page' )).'">'.$entity->get_value( 'custom_page' ).'</a></span>';
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
	}
	echo '</li>';
}

function get_all_sites()
{
	$es = get_site_selector();
	$es->orderby = '`entity`.`name` ASC';
	$sites = $es->run_one();
	return $sites;
}

function get_site_by_id($site_id)
{
	$es = get_site_selector();
	$es->add_relation('`entity`.`id` = ' . $site_id);
	$site = $es->run_one();
	return $site;
}

/**
 * @return entity_selector
 */
function get_site_selector()
{
	$es = new entity_selector();
	$es->add_type(id_of('site'));
	$es->add_relation('site_state = "Live"');
	return $es;
}

/**
 * @param $sites
 */
function display_sites($sites)
{
	foreach ($sites as $site_id => $site) {
		echo '<section class="site">';
		echo '<h2>' . $site->get_value('name') . '</h2>';
		$pages = new MinisiteNavigation();
		$pages->site_info =& $site;

		//for a bot the order probably does not matter, and adding this line will slow things down
		//$pages->order_by = 'sortable.sort_order'

		$pages->init($site_id, id_of('minisite_page'));
		if ($home_id = $pages->root_node()) {
			echo '<ul>';
			page_report($pages->values[$home_id], $pages);
			echo '</ul>';
		}
		echo '</section>';
	}
}

function begin_html()
{
	echo '<!DOCTYPE html>
<html><head><title>Reason Hierarchical Page Report</title>';
	if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '') {
		echo '<link rel="stylesheet" type="text/css" href="' . UNIVERSAL_CSS_PATH . '" />' . "\n";
	}
	echo '
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" content="none" />
</head>
<body>
';
	echo '<style media="screen">
body {
	padding:2em;
	font-family:Verdana, Arial, Helvetica, sans-serif;
	background-color:#FFFFFF;
}
.tag {
	height:24px;
	line-height:24px;
	font-size:11px;
}

.tag {
	font-family: "Open Sans", sans-serif;
	margin-left:20px;
	padding:2px 10px;
	background:cornflowerblue;
	color:#fff;
	text-decoration:none;
	-moz-border-radius:4px;
	-webkit-border-radius:4px;	
	border-radius:4px;
}
.tag a {
	color: lightgoldenrodyellow;
}
</style>';
}


function run()
{
	force_secure_if_available();
	$current_user = check_authentication();
	$current_user_id = get_user_id( $current_user );
	if (!reason_user_has_privs( $current_user_id, 'view_sensitive_data' ) )
	{
		die('<!DOCTYPE html><html><head><title>Hierarchical page report</title></head><body><h1>Sorry.</h1><p>You do not have permission to view the hierachical page report.</p><p>Only Reason users who have sensitive data viewing privileges may do that.</p></body></html>');
	}

	begin_html();

	echo '<div id="wrapper">';
	echo '<div class="contentArea" role="main">';
	echo '<h1>Hierarchical Page Report</h1>';
	echo '<p>This report will how you the page hierarchy of a chosen site. Pages will be annotated with the page type. Click through on the page type to view its definition.</p>';

	$site_id = isset($_REQUEST['site_id']) ? (integer)$_REQUEST['site_id'] : null;

	set_time_limit(3600);
	$sites = null;
	echo '<label for="site_list">Choose a live site: </label>';
	echo '<select id="site_list" name="site_list">';
	$site_list = get_all_sites();
	foreach ($site_list as $_site_id => $site) {
		$selected = $site_id === $_site_id;
		echo '<option value="'.$_site_id.'"';
		if ($selected) {
			echo ' selected="selected"';
		}
		echo '">' . $site->get_value('name').'</option>';
	}
	echo '</select>';
	echo '<script>
document.getElementById(\'site_list\').onchange = function() {
window.location = "?site_id=" + this.value;
};
</script>';
	if ($site_id) {
		if ($sites = get_site_by_id($site_id)) {
			display_sites($sites);
		}
	}

	echo '</div></div>';
	echo '</body></html>';
}

run();