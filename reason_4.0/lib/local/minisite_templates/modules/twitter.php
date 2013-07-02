<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'minisite_templates/modules/feed/models/twitter/twitter.php' );
	reason_include_once( 'minisite_templates/modules/feed/views/twitter/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'twitterModule';
	
	class twitterModule extends DefaultMinisiteModule
	{
		var $model;
		var $view;
		var $controller;
		var $twitter_info;
		var $tweet_html;

		function init( $args = array() )
		{
			$site_id = $this->site_id;
			$theme = get_theme($this->site_id);
			
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'twitter_feed_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
			$es->set_order('rel_sort_order'); 
			$this->twitter_info = $es->run_one();

			foreach ($this->twitter_info as $info) {
				$twitter_name = $info->get_value('name');
			}

			$this->model = new ReasonTwitterFeedModel();
			$this->view =  new ReasonTwitterDefaultFeedView();
			$this->model->config('screen_name', $twitter_name);
			$this->controller = new ReasonMVCController($this->model, $this->view);

			$head_items = $this->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/twitter.css');
		}

		function has_content()
		{
			$this->tweet_html = $this->controller->run();
			if ( $this->tweet_html && $this->twitter_info ){
				return true;
			} else {
				return false;
			}	
		}

		function run()
		{
			echo '<div class="twitter-feed">';
			echo $this->tweet_html;
			echo '</div>';
		}
	}