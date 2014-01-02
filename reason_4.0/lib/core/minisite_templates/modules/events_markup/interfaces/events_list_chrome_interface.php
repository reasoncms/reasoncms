<?php
/**
 * Interface for event list chrome
 * @package reason
 * @subpackage events_markup
 */
/**
 * Interface for event list chrome
 */
interface eventsListChromeMarkup
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
	 * Get the list chrome markup
	 * @return string markup
	 */
	public function get_markup();
}