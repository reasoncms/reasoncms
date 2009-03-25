<?php

	reason_include_once( 'minisite_templates/modules/magpie/magpie_feed_display.php' );

	$GLOBALS[ '_module_class_names' ][ 'magpie/' . basename( __FILE__, '.php' ) ] = 'Magpie_Feed_Nav';

	class Magpie_Feed_Nav extends Magpie_Feed_Display
	{
		function run()
		{
        	reason_include_once( 'minisite_templates/modules/magpie/reason_rss.php' );
        	
			$rfd = new reasonFeedDisplay();
			$rfd->set_location($this->feed_location, $this->is_remote);


            if(isset($this->disable_cache))
            {
                $rfd->set_cache_disable($this->disable_cache);
            }
            
            if(isset($this->display_timestamp))
            {
                $rfd->set_display_timestamp($this->display_timestamp);
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
       		echo $rfd->display_feed('nav');

		}
	}
?>
