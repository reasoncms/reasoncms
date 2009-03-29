<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register controller with Reason
	 */
	reason_include_once( 'minisite_templates/modules/form/controllers/default.php' );
	$GLOBALS[ '_form_controller_class_names' ][ basename( __FILE__, '.php') ] = 'ThorFormController';

	/**
	 * ThorFormController
	 *
	 * Provides a custom init_admin and init_summary method 
	 *
	 * @todo implement data models in table admin and deprecate me - thor can just use the default controller
	 * @author Nathan White
	 *
	 */
	class ThorFormController extends DefaultFormController
	{
		/**
		 * Default admin view gets a thor table admin object and inits it
		 */
		function init_admin()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/hide_nav.css');
			$admin =& $model->get_admin_object();
			$admin->init_thor_admin();
		}
		
		/**
		 * Default summary view gets a table admin object and sets its data
		 */
		function init_summary()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');		
			$summary =& $model->get_summary_object();
			$user_values = $model->get_values_for_user_summary_view();
			$summary->set_data_from_array($user_values);		
		}
	}
?>