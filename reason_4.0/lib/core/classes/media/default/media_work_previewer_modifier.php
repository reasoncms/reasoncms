<?php
reason_include_once('classes/media/interfaces/media_work_previewer_modifier_interface.php');

/**
 * A class that modifies the given Media Work content previewer for non-integrated media works.
 */
class DefaultMediaWorkPreviewerModifier implements MediaWorkPreviewerModifierInterface
{
	/**
	 * The previewer this modifier class will modify.
	 */
	protected $previewer;

	/**
	 * Sets the media work previewer instance.
	 * @param $previewer
	 */
	function set_previewer($previewer)
	{
		$this->previewer = $previewer;
	}
	
	/**
	 * There are no head items to set for the default media work previewer.
	 * @param $head_items
	 */
	function set_head_items($head_items)
	{}
	
	/**
	 * Adds rows of content to the previewer.
	 */
	function display_entity()
	{
		$this->previewer->start_table();
		$this->previewer->show_all_values( $this->previewer->_entity->get_values() );
		$this->previewer->end_table();
	}
}
?>