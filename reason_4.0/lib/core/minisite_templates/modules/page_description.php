<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageDescriptionModule';

	class PageDescriptionModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			if($this->cur_page->get_value('description'))
				return true;
			else
				return false;
		}
		function run()
		{
			echo '<div id="pageDescription">'."\n";
			echo $this->cur_page->get_value('description');
			echo '</div>'."\n";
		}
	}
?>
