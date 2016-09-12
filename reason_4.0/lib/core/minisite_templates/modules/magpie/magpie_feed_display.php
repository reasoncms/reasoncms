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
	$GLOBALS[ '_module_class_names' ][ 'magpie/' . basename( __FILE__, '.php' ) ] = 'Magpie_Feed_Display';
	/**
	 * A minisite module that will display the contents of an RSS feed
	 */
	class Magpie_Feed_Display extends DefaultMinisiteModule
	{
		var $cleanup_rules = array(
			'view_page'=>array('function'=>'turn_into_int'),
			'search'=>array('function'=>'turn_into_string'),
		);
		var $acceptable_params = array(
			'feed_location' => '',
			'is_remote' => true,
			'num_per_page'=>0,
			'desc_char_limit'=>0,
			'show_descriptions'=>true,
			'title'=>'',
            'display_timestamp'=>false,
            'show_entries_lacking_description'=>false,
            'disable_cache'=>false,
		);
		var $feed_location;
		var $is_remote;
		var $options;
		function init( $args = array() )
		{
			parent::init( $args );
			$rel_url = $this->get_feed_relationship_url();
			if(!empty($rel_url))
			{
				$this->feed_location = $rel_url;
				$this->is_remote = true;
			}
			elseif(!empty($this->params['feed_location']))
			{
				$this->feed_location = $this->params['feed_location'];
				$this->is_remote = $this->params['is_remote'];
			}
            if(!empty($this->params['display_timestamp']))
            {
                $this->display_timestamp = $this->params['display_timestamp'];
            }
            if(!empty($this->params['show_entries_lacking_description']))
            {
                $this->_show_entries_lacking_description = $this->params['show_entries_lacking_description'];
            }
            if(!empty($this->params['disable_cache']))
            {
                $this->disable_cache = $this->params['disable_cache'] ;
            }
		}
		function get_feed_relationship_url()
		{
			static $cache = array();
			if(!array_key_exists($this->parent->cur_page->id(), $cache))
			{
				$es = new entity_selector($this->parent->site_id);
				$es->add_type(id_of('external_url'));
				$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_feed_url') );
				$es->set_num(1);
				$urls = $es->run_one();
				if(!empty($urls))
				{
					$url = current($urls);
					$cache[$this->parent->cur_page->id()] = $url->get_value('url');
					$entOptions = [];
					$availOptions = ['num_posts','field_title','field_words','future_posts'];
					foreach($availOptions as $option){
						$entOptions[$option] = $url->get_value($option);
					}
					$this->options = $entOptions;
				}
				else
				{
					$cache[$this->parent->cur_page->id()] = '';
				}
			}
			return $cache[$this->parent->cur_page->id()];
		}
		function has_content()
		{
			if(!empty($this->feed_location))
			{
				return true;
			}
			else
			{
				trigger_error('Magpie Feed modules must be given a feed location (either by relating an external URL or by setting the feed location in the page type) to work');
				return false;
			}
		}
		function run()
		{
        reason_include_once( 'minisite_templates/modules/magpie/reason_rss.php' );
		$rfd = new reasonFeedDisplay();
		$rfd->set_location($this->feed_location, $this->is_remote);
		$rfd->set_page_query_string_key('view_page');
		$rfd->set_search_query_string_key('search');
		$rfd->set_options($this->options);
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
			$rfd->set_story_per_page($this->params['num_per_page']);
		}
		if(!empty($this->params['desc_char_limit']))
		{
			$rfd->set_description_char_limit($this->params['desc_char_limit']);
		}
        if(isset($this->params['display_timestamp']))
        {
            $rfd->set_display_timestamp($this->params['display_timestamp']);
        }
        if(isset($this->params['show_entries_lacking_description']))
        {
            $rfd->set_show_entries_lacking_description($this->params['show_entries_lacking_description']);
        }
		if($this->params['show_descriptions'])
		{
			$rfd->show_descriptions();
		}
		else
		{
			$rfd->hide_descriptions();
		}
		if(!empty($this->params['title']))
		{
			$rfd->set_title($this->params['title']);
		}
        if(isset($this->disable_cache))
        {
            $rfd->set_cache_disable($this->disable_cache);
        }
        echo $rfd->display_feed("feed");
		}
	}
?>
