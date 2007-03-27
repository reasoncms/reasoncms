<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NavigationModule';
	
	class NavigationModule extends DefaultMinisiteModule
	{
		function run()
		{
			echo '<div id="minisiteNavigation">';
			$this->parent->pages->do_display();
			echo '</div>';
		}
	}

?>
