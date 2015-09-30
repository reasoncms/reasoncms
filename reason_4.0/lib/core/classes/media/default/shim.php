<?php
reason_include_once('classes/media/interfaces/shim_interface.php');

/**
 * These are simply placeholder functions that are used when no integration library 
 * is being used.  There isn't an external system being used, so this shim is skimpy.
 */ 
class DefaultShim implements ShimInterface
{
 	public function delete_media_work($media_work_deleter)
 	{}
 	
 	public function finish_actions($media_work, $user_entity)
 	{}
 	
 	public function upload_video($filepath, $media_work, $netid, $at_remote_url = false)
 	{}
 	
 	public function upload_audio($filepath, $media_work, $netid, $at_remote_url = false)
 	{}
 	
 	/**
 	* Gets the url for the requested media file. Used by the podcast media_files.php script.
 	* @param $item 
 	* @param $media_work
 	*/
 	public function get_media_file_url($item, $media_work)
 	{
 		return $item->get_value('url');
 	}
 	
 	public function requires_extension_for_podcast()
 	{
 		return false;
 	}
 	
	public function get_entry_id($job) {}
	
	public static function get_supported_av_types()
 	{
 		return array('Video', 'Audio');
 	}
}
?>