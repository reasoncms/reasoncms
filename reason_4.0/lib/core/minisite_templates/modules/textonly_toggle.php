<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the parent class & dependencies, and register the module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/url_utils.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'TextOnlyToggleModule';

	/**
	 * A minisite module that displays a link to switch between full graphics and limited graphics mode
	 * @deprecated Will be removed in future versions of Reason
	 */
	class TextOnlyToggleModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			trigger_error('Reason\'s text-only mode has been removed. Please remove the textonly_toggle module from your page types!');
			return false;
		}
		function run()
		{
		}
	}
?>