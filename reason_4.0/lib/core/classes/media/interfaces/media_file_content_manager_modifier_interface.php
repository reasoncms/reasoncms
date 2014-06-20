<?php
/**
* Interface for media file content manager modifiers.
*/ 
interface MediaFileContentManagerModifierInterface
{
	/**
	* Sets the media file content manager instance for this modifier.
	* @param $manager instance of a media file content manager
	*/
	public function set_content_manager($manager);
	
	/**
	* Sets the required head items for the content manager.
	*/
	public function set_head_items();
	
	/**
	* Outputs any info that should be included in the show_form() function in the 
	* media file content manager.
	*/
	public function show_form();
	
	/**
	* Sets up the form.
	*/
	public function alter_data();
	
	/**
	*  Add a callback to the disco form's process()
	*/
	public function process();
	
	/**
	* Add a callback to the disco form's run_error_checks()
	*/
	public function run_error_checks();
	
}
?>