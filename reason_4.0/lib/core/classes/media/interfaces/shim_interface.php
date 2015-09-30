<?php
/**
* Interface for integration library shims.
*/ 
interface ShimInterface
{
 	/**
 	 * Performs additional work needed when a media work is deleted from Reason. Used in the 
 	 * content_deleter for media works.
 	 * @param $media_work_deleter an instance of the media_work_deleter class that is doing the deleting
 	 */
 	public function delete_media_work($media_work_deleter);
 	
 	/**
 	 * Performs any necessary actions in the finish_actions run() method.
 	 * @param $media_work entity
 	 * @param $user_entity entity
 	 */
 	public function finish_actions($media_work, $user_entity);
 	
 	/**
 	 * Uploads a video media work.
 	 * @param $filepath string
 	 * @param $media_work
 	 * @param $netid string
 	 * @return obj (Must be an object! Convert an array to an object, if needed)
 	 */
 	public function upload_video($filepath, $media_work, $netid);
 	
 	/**
 	 * Uploads an audio media work.
 	 * @param $filepath string
 	 * @param $media_work
 	 * @param $netid string
 	 * @return obj (Must be an object! Convert an array to an object, if needed)
 	 */
 	public function upload_audio($filepath, $media_work, $netid);
 	
 	/**
 	 * Builds an url for the podcast validation script for the given media work or media file
	 * item.  This is called directly from the validate_requested_podcast script.
 	 * @param $item string
 	 * @param $media_work entity
 	 * @return mixed string or false
 	 */
 	public function get_media_file_url($item, $media_work);
 	
 	/**
 	 * Return true if the podcast url needs an "a.mp3/4" extension to work in iTunes.
 	 * @return boolean
 	 */
	public function requires_extension_for_podcast();
	
	/**
	 * Extracts the entry id from the given entry/job
	 * @param $job mixed array or object
	 */
	public function get_entry_id($job);
	
	/**
	 * Returns an array of the supported av_types for the integraiton library.
	 * @return array (example: array('Video', 'Audio');
	 */
	public static function get_supported_av_types();
}
?>