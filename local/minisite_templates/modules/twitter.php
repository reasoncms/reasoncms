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
			echo '<div id="twtr-profile-widget">'."\n";
			echo '<script src="http://widgets.twimg.com/j/2/widget.js"></script>'."\n";
			//echo '<link href="http://widgets.twimg.com/j/1/widget.css" type="text/css" rel="stylesheet">'."\n";
			echo '<script>'."\n";
			echo 'new TWTR.Widget({
  				version: 2,
			  	type: \'profile\',
				rpp: 6,
				interval: 6000,
				width: 239,
				height: 300,
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
			}).render().setUser(\'lutherultimate\').start();
			</script></div>';
		}
	}
?>