<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'twitterModule';
	
	class twitterModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{
			
		}
		function has_content()
		{
			//return true;
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'twitter_feed_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
			$posts = $es->run_one();
			if ($posts != false)
			{
				return true;
			}
			return false;	
		}
		function run()
		{
			$site_id = $this->site_id;
			$theme = get_theme($this->site_id);
			
			//$es = new entity_selector( $site_id );
			//$es->add_type( id_of( 'twitter_feed_type' ) );
			//$twitter_info = $es->run_one();
			
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'twitter_feed_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
			$es->set_order('rel_sort_order'); 
			$twitter_info = $es->run_one();

			foreach ($twitter_info as $info)
			{
				if ($theme->get_value( 'name' ) == 'luther2010')
				{
					echo '<script src="//widgets.twimg.com/j/2/widget.js"></script>'."\n";
					echo '<section class="twitter-feed group" role="group">'."\n";
					echo '<header class="blue-stripe"><h1><span>Recent Tweets</span></h1></header>'."\n";
					echo '<script>'."\n";
					echo 'new TWTR.Widget({
						version: 2,
						type: \'profile\',
						rpp: ' . $info->get_value('twitter_posts') . ',
						interval: 6000,
						width: 212,
						height: 297,
						theme: {
							shell: {
								background: \'#ffffff\',
								color: \'#b4bec9\'
							},
							tweets: {
								background: \'#ffffff\',
								color: \'#0A2244\',
								links: \'#003580\'
							}
						},
						features: {
							scrollbar: false,
							loop: false,
							live: true,
							hashtags: true,
							timestamp: true,
							avatars: false,
							behavior: \'default\'
						}
					}).render().setUser(\'' . $info->get_value('name') . '\').start();';
					echo '</script>'."\n";
					echo '</section>'."\n";
				}
				else
				{
					echo '<div id="twtr-profile-widget">'."\n";
					echo '<script src="//widgets.twimg.com/j/2/widget.js"></script>'."\n";
					echo '<script>'."\n";
					echo 'new TWTR.Widget({
		  				version: 2,
					  	type: \'profile\',
						rpp: ' . $info->get_value('twitter_posts') . ',
						interval: 6000,
						width: 239,
						height: 200,
						theme: {
						    shell: {
						      background: \'#ffffff\',
						      color: \'#b4bec9\'
						    },
						    tweets: {
						      background: \'#ffffff\',
						      color: \'#000000\',
						      links: \'#1985b5\'
		    				}
					  },
					  features: {
					    scrollbar: false,
					    loop: true,
					    live: true,
					    hashtags: true,
					    timestamp: true,
					    avatars: false,
					    behavior: \'default\'
					  }
					}).render().setUser(\'' . $info->get_value('name') . '\').start();
					</script></div>';
				}
			}
		}
		
	}
?>