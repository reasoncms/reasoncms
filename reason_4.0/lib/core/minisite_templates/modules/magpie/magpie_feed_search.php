<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	/**
 	 * Register module with Reason
 	 */
	$GLOBALS[ '_module_class_names' ][ 'magpie/' . basename( __FILE__, '.php' ) ] = 'Magpie_Feed_Search';
	/**
	 * A minisite module that will display a search interface to the magpie_feed_display module.
	 *
	 * This module is intended to be used in concert with (on the same page as) magpie_feed_display.
	 */
	class Magpie_Feed_Search extends DefaultMinisiteModule
	{
		var $options;

		function has_content()
		{
			return true;
		}
		function run()
		{
			reason_include_once( 'minisite_templates/modules/magpie/reason_rss.php' );
			$rfd = new reasonFeedDisplay();
			$rfd->set_options($this->options);
			$rfd->set_page_query_string_key('view_page');
			$rfd->set_search_query_string_key('search');
       		echo $rfd->generate_search();
		}
	}
?>
