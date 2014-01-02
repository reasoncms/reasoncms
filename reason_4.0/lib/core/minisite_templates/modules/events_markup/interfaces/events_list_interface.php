<?php
/**
 * Interface for event list markup
 * @package reason
 * @subpackage events_markup
 */
/**
 * Interface for event list markup
 */
interface eventsListMarkup
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
	 * Tell the module what kind of ongoing event display this list markup does
	 *
	 * Note that this does not change the location of the ongoing events -- it is simply
	 * informative for the module/model. When you make a new markup object you need to make sure
	 * that get_markup() and this function agree.
	 *
	 * @return string 'above', 'below', or 'inline'
	 */
	public function get_ongoing_display_type();
	
	/**
	 * Get the list markup
	 *
	 * @return string markup
	 */
	public function get_markup();
}