<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the base class, include utilities, & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/blurb.php' );
	reason_include_once( 'function_libraries/user_functions.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlurbsIfLoggedInModule';
	
	/**
	 * A minisite module that acts like the blurb module, but only shows blurbs when a user is logged in
	 */
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
