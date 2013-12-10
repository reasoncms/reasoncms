<?php
/**
 * Class that encapsulates interactions with YouTube
 *
 * @package reason
 * @subpackage classes
 * @author Marcus Huderle
 */

/**
 * Include dependencies
 */
reason_include_once('classes/media/interfaces/shim_interface.php');

/**
 * This class gives some basic functionality for the needed interactions with YouTube's API.
 */
class YoutubeShim implements ShimInterface
{	
	/**
	* YouTube thumbnails can be retrieved from http://img.youtube.com/vi/<video id>/0.jpg
	*/
	public function get_thumbnail($entry_id)
	{
		return 'https://img.youtube.com/vi/'.$entry_id.'/0.jpg';
	}
	
	/**
	 * Get the directory to use for temp storage for files
	 *
	 * @return string
	 */
	public static function get_temp_dir()
	{
		$dir = REASON_TEMP_DIR.'youtube/';
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
		return $dir;
	}
	
 	public function delete_media_work($media_work_deleter) 
 	{}
 	
 	public function finish_actions($media_work, $user_entity)
 	{}
 	
 	public function upload_video($filepath, $media_work, $netid, $at_remote_url = false)
 	{}
 	
 	public function upload_audio($filepath, $media_work, $netid, $at_remote_url = false)
 	{}
 	
 	public function get_media_file_url($item, $media_work)
 	{}
 	
 	/**
 	 * Makes a call to the YouTube API and returns an object with some standard metadata.
 	 * This code is borrowed from http://www.ibm.com/developerworks/library/x-youtubeapi/
 	 *
 	 * @param $entry_id string
 	 * @return object or false
 	 */
	function get_video_data($entry_id) 
	{
		try
		{
			$obj= new stdClass;
			// set video data feed URL
			$feedURL = 'http://gdata.youtube.com/feeds/api/videos/'.$entry_id;
		
			// read feed into SimpleXML object
			$entry = simplexml_load_file($feedURL);
			if ($entry)
			{
				// get nodes in media: namespace for media information
				$media = $entry->children('http://search.yahoo.com/mrss/');
				$obj->title = $media->group->title;
				$obj->description = $media->group->description;
			  
				// get video player URL
				$attrs = $media->group->player->attributes();
				$obj->watchURL = $attrs['url']; 
				
				// get video thumbnail
				$attrs = $media->group->thumbnail[0]->attributes();
				$obj->thumbnailURL = $attrs['url']; 
					
				// get <yt:duration> node for video length
				$yt = $media->children('http://gdata.youtube.com/schemas/2007');
				$attrs = $yt->duration->attributes();
				$obj->length = $attrs['seconds'];
			}
			else
			{
				return false;
			}
		}
		catch (Exception $e)
		{
			return false;
		}
		return $obj;      
	} 
	
	public function requires_extension_for_podcast()
 	{
 		return false;
 	}
 	
 	public function get_entry_id($job) 
 	{}
	
	public static function get_supported_av_types()
 	{
 		return array('Video');
 	}
}
?>