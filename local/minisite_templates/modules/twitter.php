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
			echo '<div class="test">Hello World</div>'."\n";
			echo '<div id="twtr-profile-widget"></div>'."\n";
			echo '<script src="http://widgets.twimg.com/j/1/widget.js"></script>'."\n";
			echo '<link href="http://widgets.twimg.com/j/1/widget.css" type="text/css" rel="stylesheet">'."\n";
			echo '<script>'."\n";
			echo 'new TWTR.Widget({profile: true,id: \'twtr-profile-widget\',loop: true,width: 225,height: 300,theme:{shell: {background: \'#6688bb\',color: \'#ffffff\'},tweets: {background: \'#ffffff\',color: \'#444444\',links: \'#1985b5\'}}}).render().setProfile(\'lutherultimate\').start();	</script>';
		}
	}
?>
