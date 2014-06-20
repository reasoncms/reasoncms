<?php
/**
 * Interface used by displayer chrome classes.
 */
interface DisplayerChromeInterface
{
	/**
	 * Sets the media work entity that this displayer chrome will work with
	 * @param $media_work entity
	 */
	public function set_media_work($media_work);
	
	/**
	 * Adds any required head items by this displayer chrome.
	 * @param $head_items array
	 */
	public function set_head_items($head_items);
	
	/**
	 * Sets the module that its working with.  Useful for interacting with module like AV.
	 * @param $module object
	 */
	public function set_module($module);
	
	/**
	 * Returns the html markup to display the chrome.
	 * @return string
	 */
	public function get_html_markup();
	
	/**
	 * Sets the width for the display of the media portion of the chrome.
	 * @param $width int
	 */
	public function set_media_width($width);
	
	/**
	 * Sets the height for the display of the media portion of the chrome.
	 * @param $height int
	 */
	public function set_media_height($height);
	
	/**
	 * Enables/Disables google analytics where appropriate.
	 * @param bool $on
	 */
	public function set_google_analytics($on);
}
?>