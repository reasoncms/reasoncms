<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Register the module with Reason and include the parent class
	 */
	reason_include_once( 'minisite_templates/modules/content.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FormContentModule';

	/**
	 * A minisite module that only displays the current page's content if the page is not responding to a form submission
	 *
	 * When used in conjunction with a form module, this hides the page's content on form submission
	 * so that the form's thank you message is not buried onder the page content
	 *
	 * @todo use cleanup rules instead of directly looking at $_REQUEST
	 * @todo report has_content state
	 */
	class FormContentModule extends EditableContentModule
	{
		function run()
		{
			// Don't display the content if the form has already been
			// submitted, because we'll already be displaying a
			// thank-you message in main_post, and it would look silly
			// to have the content there too
			if ( !array_key_exists('submission_key', $_REQUEST) )
				parent::run();
		}
	}
?>
