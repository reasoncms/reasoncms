<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
  	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/news2_mini.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'news2MiniRandomModule';

/**
 * A minisite module that lists a single random news item from the site, and links to the site's 
 * news page to show the full story.
 *
 * Note: this module is deprecated. Use a related publication instead, setting the num_per_page
 * parameter to 1 the related_order to RAND().
 *
 * @deprecated
 */
class news2MiniRandomModule extends News2MiniModule
{
	var $style_string = 'newsMiniRandom';
	var $num_per_page = 1;
	var $use_other_page_name_as_module_title = false;
	var $jump_to_item_if_only_one_result = false;
	var $has_feed = false;
	
	function alter_es()
	{
		parent::alter_es();
		$this->es->set_order('RAND()');
	}
}
?>
