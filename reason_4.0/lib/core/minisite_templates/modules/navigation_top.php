<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NavigationTopModule';
	
	class NavigationTopModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			return $this->parent->pages->top_nav_has_content();
		}
		function run()
		{
			echo '<div id="topNavigation">';
			$this->parent->pages->show_top_nav();
			echo '</div>';
		}
	}

?>
