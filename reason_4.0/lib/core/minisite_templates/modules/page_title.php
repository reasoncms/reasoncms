<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageTitleModule';

	class PageTitleModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			return !empty( $this->parent->title );
		}
		function run()
		{
  			echo '<h2 class="pageTitle"><span>'.$this->parent->title.'</span></h2>';
		}
	}
?>
