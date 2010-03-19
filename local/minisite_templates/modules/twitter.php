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
			return true;
		}
		function run()
		{
			$site_id = $this->site_id;
			
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'twitter_feed_type' ) );
			$twitter_info = $es->run_one();

			foreach ($twitter_info as $info)
			{
				echo '<div id="twtr-profile-widget">'."\n";
				echo '<script src="http://widgets.twimg.com/j/2/widget.js"></script>'."\n";
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
?>