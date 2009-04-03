<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'db/table_admin.php');
/**
 * Register view with Reason
 */
$GLOBALS[ '_form_admin_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultDBAdminForm';

/**
 * Default DB Admin Form
 *
 * Extends DiscoDefaultAdmin to allow interaction with our model. This is done by:
 *
 * 1. providing set_model and get_model methods
 * 2. set the model form id to the table action form id
 * 3. add a custom_init method option to allow for head items, etc.
 *
 * @todo consider move away from DiscoDB and DiscoDefaultAdmin and use the model directly for all phases (more like the form view)
 *
 * @author Nathan White
 */

 class DefaultDBAdminForm extends DiscoDefaultAdmin
 {			
 	/**
 	 * The class requires a model
 	 */
	var $_model;

	var $allowable_actions = array ('view' => true);
	
	/**
	 *  Accepts a reference to the model
	 */
	function set_model(&$model)
	{
		$this->_model =& $model;
	}
	
	function setup_form(&$table_admin)
	{
		$model =& $this->get_model();
		$model->set_form_id($table_admin->get_table_action_id());
		if (method_exists($this, 'custom_init')) $this->custom_init();
		parent::setup_form($table_admin);
	}
	
	/**
	 * Return a reference to the model
	 */
	function &get_model()
	{
		return $this->_model;
	}
}
?>