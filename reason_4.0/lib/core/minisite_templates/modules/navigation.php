<?php
/**
 * @package reason
 * @subpackage minisite_templates
 */
	
	/**
	 * Include parent class; register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NavigationModule';
	
	/**
	 * A minisite module that presents the main site navigation
	 */
	class NavigationModule extends DefaultMinisiteModule
	{
		var $acceptable_params = array('wrapper_element' => 'div');
		function has_content()
		{
			if($pages = $this->get_page_nav())
				return $pages->main_nav_has_content();
			return false;
		}
		function run()
		{
			$pages = $this->get_page_nav();
			if(empty($pages))
				return;
			echo '<'.$this->params['wrapper_element'].' id="minisiteNavigation" class="'.$this->get_api_class_string().'" role="navigation" aria-label="page">';
			$pages->do_display();
			echo '</'.$this->params['wrapper_element'].'>';
		}
		function get_documentation()
		{
			return '<p>Displays the main site navigation</p>';
		}
	}

?>
