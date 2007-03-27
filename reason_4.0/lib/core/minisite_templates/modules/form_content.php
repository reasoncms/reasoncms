<?php

	reason_include_once( 'minisite_templates/modules/content.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FormContentModule';

	class FormContentModule extends ContentModule
	{
		function run()
		{
			// Don't display the content if the form has already been
			// submitted, because we'll already be displaying a
			// thank-you message in main_post, and it would look silly
			// to have the content there too
			if ( !array_key_exists('thor_success', $_REQUEST) )
				parent::run();
		}
	}
?>
