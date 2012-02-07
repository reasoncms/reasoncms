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
		function has_content()
		{
			return $this->parent->pages->main_nav_has_content();
		}
		function run()
		{
			echo '<div id="minisiteNavigation" class="'.$this->get_api_class_string().'">';
			$this->parent->pages->do_display();
			echo '</div>';
		}
		function get_documentation()
		{
			return '<p>Displays the main site navigation</p>';
		}
	}

?>
