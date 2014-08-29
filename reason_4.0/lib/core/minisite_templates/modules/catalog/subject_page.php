<?php
/**
 * Catalog: Subject Page
 *
 * Module for displaying a subject page for a course catalog
 *
 * @author Mark Heiman
 * @since 2014-08-20
 * @package MinisiteModule
 *
 * 
 */
$GLOBALS[ '_module_class_names' ][ 'catalog/'.basename( __FILE__, '.php' ) ] = 'CatalogSubjectPageModule';

reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/course_functions.php' );

class CatalogSubjectPageModule extends DefaultMinisiteModule
{
	public $cleanup_rules = array(
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		);

	function init( $args = array() )
	{
		parent::init($args);
		
		// If we're in ajax mode, we just return the data and quit the module.
		$api = $this->get_api();
		if ($api && ($api->get_name() == 'standalone'))
		{
			if (isset($this->request['subject']))
			{
				$this->do_course_lookup($this->request['subject']);
				exit;
			}
			if (isset($this->request['toggle_course']))
			{
				echo json_encode($this->do_course_toggle($this->request['toggle_course']));
				exit;
			}
		}

		//$this->build_course_list();

		if($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/courses/manage_courses.css');
			//$head_items->add_javascript(JQUERY_URL, true);
			//$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/courses/manage_courses.js');
		}

	}
	
	function run()
	{
		
	}
}
