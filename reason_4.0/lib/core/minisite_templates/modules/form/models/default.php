<?php

$GLOBALS[ '_form_model_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultFormModel';

/**
 * The DefaultFormModel
 */
class DefaultFormModel
{
	/**
	 * Form Models are provided with a reference to the module upon instantiation and should localize only what is needed.
	 *
	 * The default model localizes site_id, page_id, and head_items, and passes a reference to the module to localize so that
	 * models can localize other items from the module (if needed).
	 *
	 * @author Nathan White
	 */
	function DefaultFormModel(&$module)
	{
		$this->site_id = $module->site_id;
		$this->page_id = $module->page_id;
		$this->head_items =& $module->parent->head_items;
		$this->localize($module);
	}
	
	/**
	 * Localize module variables upon instantiation if needed
	 */
	function localize(&$module)
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
