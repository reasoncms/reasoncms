<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Register model with Reason
 */
$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'AbstractFormModel';

/**
 * The AbstractFormModel
 *
 * @author Nathan White
 * @todo instead of the parallelism here we ought to have an array of available views - one would likely be the admin view
 */
class AbstractFormModel
{
	var $_view;
	var $_admin_view;
	
	function AbstractFormModel()
	{
	}
	
	function init()
	{
	}
	
	/**
	 * When a view is instantiated - the model is given an opportunity to transform it
	 */
	function transform_form()
	{
		return false;
	}

	/**
	 * When an admin view is instantiated - the model is given an opportunity to transform it
	 */
	function transform_admin_form()
	{
		return false;
	}
	
	/**
	 * Process the request vars that are passed from the controller
	 */
	function handle_request_vars(&$request_vars)
	{
		return false;
	}
	
	/**
 	 * Should validate the request - the outcome would typically result in setting the internal form_id if appropriate
	 */
	function validate_request()
	{
		return false;
	}
	
	/**
	 * Returns the view
	 */
	function &get_view()
	{
		if (!isset($this->_view))
		{
			trigger_error('The model requested a reference to the view but it is not available.');
		}
		return $this->_view;
	}
	
	/**
	 * Provides a reference to the active view that the model can use
	 */
	function set_view(&$view)
	{
		$this->_view =& $view;
	}

	/**
	 * Returns the view selected in the content manager
	 */
	function &get_admin_view()
	{
		if (!isset($this->_admin_view))
		{
			trigger_error('The model requested a reference to the admin view but it is not available.');
		}
		return $this->_admin_view;
	}
	
	/**
	 * Returns the view selected in the content manager
	 */
	function set_admin_view(&$admin_view)
	{
		$this->_admin_view =& $admin_view;
	}
}
?>
