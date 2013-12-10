<?php
/**
 * Interface for media work displayers.
 */ 
interface MediaWorkDisplayerInterface
{
	/**
	 * Assigns the given media work to the displayer.
	 * @param $media_work entity
	 */
	public function set_media_work($media_work);
 	
 	/**
 	 * Returns the html to display the media.  The media is embedded in an iframe.
 	 * @return string
 	 */
 	public function get_display_markup();
 	
 	/**
 	 * Returns the html markup that embeds the media on a page.
 	 * @return string
 	 */
 	public function get_embed_markup();
 	
 	/**
 	 * Returns the url for the iframe used to display the media.
 	 * @param $iframe_height int
 	 * @param $iframe_width int
 	 * @return string
 	 */
 	public function get_iframe_src($iframe_height, $iframe_width);
 	
 	/**
 	 * Sets the width of the displayer.
 	 * @param $width int
 	 */
 	public function set_width($width);
 	
 	/**
 	 * Sets the height of the displayer.
 	 * @param $height int
 	 */
 	public function set_height($height);
 	
 	/**
 	 * Returns array of media files being used for playback.
 	 * @return array
 	 */
 	public function get_media_files();
 	
 	/**
 	 * Sets whether or not the media autoplays. 
 	 * @param $autostart boolean
 	 */
 	public function set_autostart($autostart);
 	
 	/**
 	 * Sets whether or not the controls should be overlayed onto the player.
 	 * @param $show_controls boolean
 	 */
 	public function set_show_controls($show_controls);
 	
 	/**
 	 * Gets a hash associated with the current media work. Used for validating the iframe script.
 	 * @return string
 	 */
 	public function get_hash();
 	
 	/**
 	 * Gets a hash for the given media file.
 	 * @return string
 	 */
 	public function get_media_file_hash($media_file);
 	
 	/**
 	 * Gets a hash for the end of the original filename when storing an original file.
 	 * @return string
 	 */
 	public function get_original_filename_hash();
 	
 	/**
 	 * Gets the height that the item should be embedded at, in pixels
 	 * @return integer
 	 */
 	public function get_embed_height();
 	
 	/**
 	 * Gets the width that the item should be embedded at, in pixels
 	 * @return integer
 	 */
 	public function get_embed_width();
}
?>