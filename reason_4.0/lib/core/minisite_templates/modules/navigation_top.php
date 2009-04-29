<?php
/**
 * @package reason
 * @subpackage minisite_templates
 */
	
	/**
	 * Include parent class; register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NavigationTopModule';
	
	/**
	 * A minisite module that presents the "top nav" (e.g. tab navigation) of the current navigation class
	 */
	class NavigationTopModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			if($pages =& $this->get_page_nav())
			{
				return $pages->top_nav_has_content();
			}
			else
			{
				return false;
			}
		}
		function run()
		{
			echo '<div id="topNavigation">';
			if($pages =& $this->get_page_nav())
			{
				$pages->show_top_nav();
			}
			else
			{
				echo 'Not able to show nav; no page nav object provided to module';
			}
			echo '</div>';
		}
		function get_documentation()
		{
			if($this->has_content())
			{
				return '<p>Displays the top-level navigation for the site</p>';
			}
			else
			{
				return false;
			}
		}
	}

?>
