<?php

$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultFormModel';

/**
 * The DefaultFormModel
 */
class DefaultFormModel
{
	/**
	 * Form Models can optionally be provided with a reference to a module, in which case the following will occur:
	 * 
	 * 1. The model site_id variable will be set to the module site_id
	 * 2. The model page_id variable will be set to the module page_id
	 * 3. The model head_items variable will be be set to refer to the module head_items variable
	 * 4. The module object will be passed to a method called localize that does nothing by default
	 *
	 * @author Nathan White
	 */
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
		$this->localize($module);
	}
	
	function set_site_id($site_id)
	{
		$this->site_id = $site_id;
	}
	
	function set_page_id($page_id)
	{
		$this->page_id = $page_id;
	}
	
	function set_head_items(&$head_items)
	{
		$this->head_items =& $head_items;
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
