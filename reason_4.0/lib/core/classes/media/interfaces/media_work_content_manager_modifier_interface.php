<?php
/**
* Interface for media work previewer modifiers.
*/ 
interface MediaWorkContentManagerModifierInterface
{
	/**
	* Sets the content manager instance for this modifier instance.
	* @param $manager media work content manager instance
	*/
	public function set_content_manager($manager);
	
	/**
	* Sets the required head_items in the content manager.
	* @param $head_items head_items array for the media work content manager
	*/
	public function set_head_items($head_items);
    
    /**
    * Called in content manager's alter_data(). Performs any alterations needed on the fields
    * in the content manager's disco form.
    */
    public function alter_data();
    
    /**
    * Adds a callback for process() in the disco form.
    */ 
    public function process();
    
    /**
    * Adds a callback for run_error_checks() in the disco form
    */
    public function run_error_checks();
}
?>