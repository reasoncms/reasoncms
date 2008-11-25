<?php

$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultFormModel';

/**
 * The DefaultFormModel
 */
class DefaultFormModel
{
	/**
	 * Form Models can be inited from a module, in which case the following will happen
	 * 
	 * 1. The model site_id variable will be set to the module site_id
	 * 2. The model page_id variable will be set to the module page_id
	 * 3. The model head_items variable will be be set to refer to the module head_items variable
	 * 4. The model params will be set to refer to the module params
	 * 5. The module object will be passed to a method called localize that does nothing by default
	 *
	 * @author Nathan White
	 */
	
	var $_site_id;
	var $_page_id;
	var $_form_id;
	var $_head_items;
	var $_params;
	var $_form_submission_key;
	
	function DefaultFormModel()
	{
	}
	
	function init()
	{
	}
	
	function init_from_module(&$module)
	{
		if (isset($module->site_id)) $this->set_site_id($module->site_id);
		if (isset($module->page_id)) $this->set_page_id($module->page_id);
		if (isset($module->parent->head_items)) $this->set_head_items($module->parent->head_items);
		if (isset($module->params)) $this->set_params($module->params);
		$this->localize($module);
	}
	
	/**
	 * When a view is instantiated - the model is given an opportunity to transform it
	 */
	function transform_form()
	{
		$disco =& $this->get_view();
		return false;
	}

	/**
	 * When a view is instantiated - the model is given an opportunity to transform it
	 */
	function transform_admin_form()
	{
		$disco =& $this->get_admin_view();
		return false;
	}
	
	/**
	 * Accept and process request vars from the user
	 * @param array parameters key/value pairs of setup parameters
	 */
	function handle_request_vars(&$request_vars)
	{
		return false;
	}
	
	function set_site_id($site_id)
	{
		$this->_site_id = $site_id;
	}
	
	function get_site_id()
	{
		return $this->_site_id;
	}
	
	function set_page_id($page_id)
	{
		$this->_page_id = $page_id;
	}

	function get_page_id()
	{
		return $this->_page_id;
	}
	
	function set_form_id($form_id)
	{
		$this->_form_id = $form_id;
	}
	
	function get_form_id()
	{
		return (isset($this->_form_id)) ? $this->_form_id : false;
	}
	
	function set_form_submission_key($key)
	{
		$this->_form_submission_key = $key;
	}
	
	function get_form_submission_key()
	{
		return $this->_form_submission_key;
	}
	
	function set_head_items(&$head_items)
	{
		$this->_head_items =& $head_items;
	}
	
	function &get_head_items()
	{
		return $this->_head_items;
	}
	
	function set_params(&$params)
	{
		$this->_params =& $params;
	}
	
	function &get_params()
	{
		return $this->_params;
	}

	/**
	 * Returns the view selected in the content manager
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
	
	/**
	 * Localize module variables upon instantiation if needed
	 */
	function localize(&$object)
	{
		return false;
	}
	
	/**
	 * @return boolean whether or not the model can be used (performs any needed integrity checks)
	 */
	function is_usable()
	{
		return false;
	}
}
?>
