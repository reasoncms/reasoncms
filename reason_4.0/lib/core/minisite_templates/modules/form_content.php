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
	 * A minisite module that hides the page content in various situations.
	 *
	 * - Hide after submission - Hides the page content immediately after a submission. DEFAULT ON
	 * - Hide in admin view - Hides the page content when form admin view is active. DEFAULT ON
	 * - Hide on edit - Hides the page content when editing (whenever a form_id > 0 is present). DEFAULT OFF
	 *
	 * When used in conjunction with a form module, this hides the page's content on form submission
	 * so that the form's thank you message is not buried onder the page content
	 *
	 * @todo report has_content state
	 */
	class FormContentModule extends EditableContentModule
	{
		var $cleanup_rules = array('submission_key' => 'turn_into_string',
								   'form_id' => 'turn_into_int',
								   'form_admin_view' => array('function' => 'check_against_array', 'extra_args' => array('true')));
									    
		var $acceptable_params = array('hide_after_submission' => true,
								       'hide_in_admin_view' => true,
								       'hide_on_edit' => false);
								            
		function run()
		{
			if (!$this->hide_after_submission() && !$this->hide_in_admin_view() && !$this->hide_on_edit())
			{
				parent::run();
			}
		}
		
		function hide_after_submission()
		{
			return ($this->params['hide_after_submission'] && (isset($this->request['submission_key']) && !empty($this->request['submission_key'])));
		}
		
		function hide_in_admin_view()
		{
			return ($this->params['hide_in_admin_view'] && (isset($this->request['form_admin_view'])));
		}
		
		function hide_on_edit()
		{
			return ($this->params['hide_on_edit'] && (isset($this->request['form_id']) && ($this->request['form_id'] > 0)));
		}
	}
?>
