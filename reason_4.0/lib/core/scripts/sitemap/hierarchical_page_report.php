<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('minisite_templates/nav_classes/default.php');
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'classes/page_types.php');
include_once( CARL_UTIL_INC . 'dev/pray.php' );



class HieracrhicalPageReport {
	private $page_type_report = array();

	function page_report($entity, $pages_object, $output='')
	{
		$pts = new ReasonPageTypes;
		$output .= '<li><a href="'.$pages_object->get_full_url($entity->id()).'">'.$entity->get_value('name');
		$is_link = false;
		// is this page a link?
		if($entity->get_value( 'url' ))
		{
			$is_link = true;
			$output .= ' ↗';
		}
		$output .= '</a>';
		if (!$is_link) {
			$page_type_name = $entity->get_value('custom_page');
			$output .= '<span class="tag">Page Type: <a href="' . REASON_HTTP_BASE_PATH . 'scripts/page_types/view_page_type_info.php#' . reason_htmlspecialchars($page_type_name) . '">' . $page_type_name . '</a></span>';
			if ($page_type_name) {
				$page_type_obj = $pts->get_page_type($page_type_name);
				$this->aggregate_module_info($page_type_obj, $page_type_name);
			}
		}

		$child_ids = $pages_object->children($entity->id());
		$children_output = '';
		if (!empty($child_ids)) {
			$children_output = '<ul>';
			foreach ($child_ids as $child_id) {
				$child_output = '';
				if (isset($pages_object->values[$child_id])) {
					$child_output .= $this->page_report($pages_object->values[$child_id], $pages_object, $child_output);
				}
				$children_output .= $child_output;
			}
			$children_output .= '</ul>';
		}
		$output .= $children_output;

		$output .= '</li>';
		return $output;
	}

	function get_all_sites()
	{
		$es = $this->get_site_selector();
		$es->orderby = '`entity`.`name` ASC';
		$sites = $es->run_one();
		return $sites;
	}

	function get_site_by_id($site_id)
	{
		$es = $this->get_site_selector();
		$es->add_relation('`entity`.`id` = ' . $site_id);
		$site = $es->run_one();
		return reset($site);
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
	function display_site($site)
	{
		$output = '';
		$site_id = $site->_id;
		$pages = new MinisiteNavigation();
		$pages->site_info =& $site;
		$pages->init($site_id, id_of('minisite_page'));
		//for a bot the order probably does not matter, and adding this line will slow things down
		$pages->order_by = 'sortable.sort_order';
		if ($home_id = $pages->root_node()) {
			$page_details = $this->page_report($pages->values[$home_id], $pages);
		}

		$output .= '<section class="site">';
		$output .= '<h2>' . $site->get_value('name') . '</h2>';

		// print page type report
		$pt_types = array('core', 'local');

		foreach ($pt_types as $pt_type) {
			if (isset($this->page_type_report[$pt_type])) {
				$output .= '<p>'.ucfirst($pt_type).' page types used in this site:</p>';
				$output .= '<dl>';
				foreach ($this->page_type_report[$pt_type] as $key=>$val) {
					$output .= '<dt>'.$key.'</dt><dd>'.$val.'</dd>';
				}
				$output .= '</dl>';
			}
		}

		// print module report
		$output .= '<p>Modules used by these page types:</p>';
		$output .= '<ul id="modules">';
		foreach ($this->page_type_report['modules'] as $module_name) {
			$output .= '<li>'.$module_name.'</li>';
		}
		$output .= '</ul>';

		// UL here
		$output .= '<ul>';
		$output .= $page_details;
		$output .= '</ul>';

		$output .= '</section>';

		echo $output;
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
dl, ul#modules {
    padding: 0.5em;
}
dt, #modules li {
	font-weight: bold;
	padding-right: 10px;
}
ul#modules {
list-style-type: none;
padding: 0;
}
dt {
	float: left;
	clear: left;
	text-align: right;
}
  dt::after {
    content: ":";
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

		$this->begin_html();

		echo '<div id="wrapper">';
		echo '<div class="contentArea" role="main">';
		echo '<h1>Hierarchical Page Report</h1>';
		echo '<p>This report will how you the page hierarchy of a chosen site. Pages will be annotated with the page type. Click through on the page type to view its definition.</p>';

		$site_id = isset($_REQUEST['site_id']) ? (integer)$_REQUEST['site_id'] : null;

		set_time_limit(3600);
		$sites = null;
		echo '<label for="site_list">Choose a live site: </label>';
		echo '<select id="site_list" name="site_list">';
		$site_list = $this->get_all_sites();
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
			if ($sites = $this->get_site_by_id($site_id)) {
				$this->display_site($sites);
			}
		}

		echo '</div></div>';
		echo '</body></html>';
	}

	/**
	 * @param $page_type_obj
	 * @param $page_type_name
	 */
	public function aggregate_module_info($page_type_object)
	{
		$_pt_location = $page_type_object->_page_type_location;
		$page_type_name = $page_type_object->get_name();
		$_curr_count = 1;
		if (array_key_exists($_pt_location, $this->page_type_report)) {
			$pt_location = $this->page_type_report[$_pt_location];
			if (array_key_exists($page_type_name, $pt_location)) {
				$_curr_count = (int)$pt_location[$page_type_name] + 1;
			} else {
				$_curr_count;
			}
		}
		$this->page_type_report[$_pt_location][$page_type_name] = $_curr_count;

		$pt_array = $page_type_object->_export_reason_pt_array_var();
		$_pt_modules = array_filter(array_values($pt_array));

		$modules = array();
		foreach ($_pt_modules as $key=>$val) {
			if (is_array($val)) {
				$module_name = $val['module'];
			} else {
				$module_name = $val;
			}
			$modules[] = $module_name;
		}

		if (isset($this->page_type_report['modules'])) {
			$this->page_type_report['modules'] = array_merge($this->page_type_report['modules'], $modules);
		} else {
			$this->page_type_report['modules'] = $modules;
		}
		$this->page_type_report['modules'] = array_unique($this->page_type_report['modules']);
	}

}

$report = new HieracrhicalPageReport();

$report->run();