<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );
	reason_include_once( 'function_libraries/user_functions.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlurbsIfLoggedInModule';
	
	class BlurbsIfLoggedInModule extends BlurbModule
	{
		function has_content()
		{
			if( !empty($this->blurbs) && reason_check_authentication())
                                return true;
                        else
                                return false;
		}
	}
?>
