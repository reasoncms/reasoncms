<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'tagboardModule';
	
	class tagboardModule extends DefaultMinisiteModule
	{
		var $acceptable_params = array(
			'name_id' => '',
		);
		
		function init( $args = array() )
		{
			
		}
		function has_content()
		{
			return true;
		}
		function run()
		{
			if (!empty($this->params['name_id']))
			{
				$tagname = $this->params['name_id'];
			}
			else{
				$tagname = "LutherCollege/184204";
			}
			//echo '<div id="tagboard-embed" style="margin-top:-2.5rem;"></div>' . "\n";
			echo '<div id="tagboard-embed"></div>' . "\n";
			echo '<script>var tagboardOptions = {tagboard:"' . $tagname . '", postCount: 50, mobilePostCount: 50, autoLoad: true};</script>' . "\n";
			echo '<script src="https://tagboard.com/public/js/embed.js"></script>'."\n";
			echo '<p class="tagboardLink"><a href="http://www.tagboard.com/' . $tagname . '">Click to see all posts on tagboard.com. </a></p>';
		}
	}
?>
