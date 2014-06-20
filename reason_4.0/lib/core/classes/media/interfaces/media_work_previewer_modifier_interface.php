<?php
/**
* Interface for media work previewer modifiers.
*/ 
interface MediaWorkPreviewerModifierInterface
{
	/**
	* Sets the media work previewer instance for this modifier.
	* @param $previewer
	*/
	public function set_previewer($previewer);
	
	/**
	* Adds head items to the content previewer.
	* @param $head_items
	*/ 
    public function set_head_items($head_items);
    
    /**
    * Adds additional content to the content previewer.
    */
    public function display_entity();
}
?>