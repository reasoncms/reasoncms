<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherGmapModule';

	class NorseFormModule extends DefaultMinisiteModule
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
			
		}
	}
?>