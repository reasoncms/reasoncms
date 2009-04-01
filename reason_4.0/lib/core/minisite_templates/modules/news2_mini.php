<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
  	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/news2.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'news2MiniModule';

/**
 * A minisite module that lists the 4 most recent news items on the site
 *
 * Note: this module is deprecated. Use the publications module instead, in related mode.
 *
 * @deprecated
 */
class news2MiniModule extends News2Module
{
	var $style_string = 'newsMini';
	var $num_per_page = 4;
	var $no_items_text = 'There are no news items available on this site.';
	var $page_types_available_for_linking = array('news','news_doc','newsNoNavigation','newsNoNavigation_footer_blurb','news_NoNav_sidebarBlurb','issued_news','news_one_at_a_time',);
	var $link_to_a_different_page = true;
	var $use_other_page_name_as_module_title = true;
	
	function show_pagination()
	{
	}
}
?>
