<?php
/**
 * Interface for event item markup
 * @package reason
 * @subpackage gallery_markup
 */
/**
 * Interface for gallery item markup
 */
interface galleryItemMarkup
{
	/**
	 * Modify the page's head items, if desired
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items);
	
	/**
	 * Set the function bundle for the markup to use
	 * @param object $bundle
	 * @return void
	 */
	public function set_bundle($bundle);
	
	/**
	 * Get the item markup
	 * @return string markup
	 */
	public function get_markup($event);
	
	public function generates_item_name();
}