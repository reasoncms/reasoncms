<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class
 	 */
	reason_include_once( 'minisite_templates/modules/magpie/magpie_feed_display.php' );
	/**
 	 * Register module with Reason
 	 */
	$GLOBALS[ '_module_class_names' ][ 'magpie/' . basename( __FILE__, '.php' ) ] = 'Magpie_Feed_Nav';
	/**
 	 * A minisite module that will display a list of current items in the feed, with links
 	 * to the items. This module is intended to be used in conjunction with the
 	 * magpie_feed_display module.
 	 */
	class Magpie_Feed_Nav extends Magpie_Feed_Display
	{
		var $options;

		function run()
		{
        	reason_include_once( 'minisite_templates/modules/magpie/reason_rss.php' );
			$rfd = new reasonFeedDisplay();
			$rfd->set_options($this->options);
			$rfd->set_location($this->feed_location, $this->is_remote);
            if(isset($this->disable_cache))
            {
                $rfd->set_cache_disable($this->disable_cache);
            }
            if(isset($this->params['display_timestamp']))
            {
                $rfd->set_display_timestamp($this->params['display_timestamp']);
            }
            if(isset($this->params['show_entries_lacking_description']))
            {
                $rfd->set_show_entries_lacking_description($this->params['show_entries_lacking_description']);
            }
			$rfd->set_page_query_string_key('view_page');
			$rfd->set_search_query_string_key('search');
			if(!empty($this->request['view_page']))
			{
				$rfd->set_page($this->request['view_page']);
			}
			if(!empty($this->request['search']))
			{
				$rfd->set_search_string($this->request['search']);
			}
			if(!empty($this->params['num_per_page']))
			{
				$rfd->set_num_in_nav($this->params['num_per_page']);
			}
			if(!empty($this->params['title']))
			{
				$rfd->set_title($this->params['title']);
			}
       		echo $rfd->display_feed('nav');
		}
	}
?>
