<?php
/**
 * Class that encapsulates interactions with Vimeo
 *
 * @package reason
 * @subpackage classes
 * @author Marcus Huderle
 */

/**
 * Include dependencies
 */
require_once(SETTINGS_INC.'media_integration/vimeo_settings.php');
reason_include_once('classes/media/interfaces/shim_interface.php');
require_once(INCLUDE_PATH.'vimeo/vimeo.php');

/**
 * This class gives some basic functionality for the needed interactions with Vimeo's API.
 */
class VimeoShim implements ShimInterface
{	
	/**
	* Vimeo thumbnails can be retrieved using the Vimeo simple API.
	*/
	public function get_thumbnail($entry_id)
	{
		$data = $this->get_video_data($entry_id);
		if ($data)
		{
			return end($data->thumbnails->thumbnail)->_content;
		}
		return false;
	}
	
	/**
	 * Get the directory to use for temp storage for files
	 *
	 * @return string
	 */
	public static function get_temp_dir()
	{
		$dir = REASON_TEMP_DIR.'vimeo/';
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
		return $dir;
	}
	
	/**
	 * Attempts to delete a video from Vimeo. If the video wasn't uploaded through Reason, the 
	 * deletion call will simply fail.
	 * @param $media_work_deleter
	 */
 	public function delete_media_work($media_work_deleter) 
 	{
 		if (VIMEO_UPLOADING_ENABLED)
 		{
			$media_work = new entity($media_work_deleter->get_value('id'));
			$vimeo = new phpVimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, VIMEO_ACCESS_TOKEN, VIMEO_ACCESS_TOKEN_SECRET);
			
			try
			{
				$vimeo->call('vimeo.videos.delete', array('video_id' => $media_work->get_value('entry_id')));
			}
			catch (VimeoAPIException $e) 
			{}
		}
 	}
 	
 	/**
 	 * Attempts to update the metadata of a video on Vimeo. If the video wasn't uploaded through
 	 * Reason, the update call will simply fail.
 	 */
 	public function finish_actions($media_work, $user_entity)
 	{
 		if (VIMEO_UPLOADING_ENABLED)
 		{
			$vimeo = new phpVimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, VIMEO_ACCESS_TOKEN, VIMEO_ACCESS_TOKEN_SECRET);
			
			try
			{
				$vimeo->call('vimeo.videos.setTitle', array('video_id' => $media_work->get_value('entry_id'), 'title' => $media_work->get_value('name')));
				$vimeo->call('vimeo.videos.setDescription', array('video_id' => $media_work->get_value('entry_id'), 'description' => $media_work->get_value('description')));
				$vimeo->call('vimeo.videos.clearTags', array('video_id' => $media_work->get_value('entry_id')));
				$vimeo->call('vimeo.videos.addTags', array('tags'=> str_replace(' ', ',', $media_work->get_value('keywords')), 'video_id' => $media_work->get_value('entry_id')));
			}
			catch (VimeoAPIException $e)
			{}
		}
 	}
 	
 	/**
 	 * Uploads a video to Vimeo.
 	 *
 	 * @param $filepath string
 	 * @param $media_work entity
 	 * @param $netid string
 	 * @param $at_remote_url boolean
 	 * @return mixed int or false
 	 */
 	public function upload_video($filepath, $media_work, $netid, $at_remote_url = false)
 	{
 		if (VIMEO_UPLOADING_ENABLED)
 		{
			$vimeo = new phpVimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, VIMEO_ACCESS_TOKEN, VIMEO_ACCESS_TOKEN_SECRET);
	
			try {
				$video_id = '';
				if (!$at_remote_url)
				{
					$filename = basename($filepath);
					$newpath = WEB_PATH.WEB_TEMP.$filename;
					$video_id = $vimeo->upload($newpath);
				}
				else
				{
					$filename = basename($filepath);
					$parts = explode('.', $filename);
					$extension = end($parts);
					$id = uniqid();
					$new_filename = $id.'.'.$extension;
					$newpath = WEB_PATH.WEB_TEMP.$new_filename;
					
					/*
					$fp = fopen($newpath, 'w+');
					$ch = curl_init($filepath);
					curl_setopt($ch, CURLOPT_TIMEOUT, 1800);
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);
					*/
					$filepath = str_replace($filename, urlencode($filename), $filepath);
					
					set_time_limit(0);
					exec('curl -o '.$newpath.' -L -m 180000 "'.$filepath.'"');
					//exec('wget -O '.$newpath.' -q -t 1 "'.$filepath.'"');
					
					$video_id = $vimeo->upload($newpath, false, '.', 2097152, $media_work->get_value('entry_id'));
					//unlink($newpath);
				}
				
				if ($video_id) { // successful upload
					//$vimeo->call('vimeo.videos.setPrivacy', array('privacy' => 'nobody', 'video_id' => $video_id));
					$vimeo->call('vimeo.videos.setTitle', array('title' => $media_work->get_value('name'), 'video_id' => $video_id));
					if ($media_work->get_value('description'))
					{
						$vimeo->call('vimeo.videos.setDescription', array('description' => $media_work->get_value('description'), 'video_id' => $video_id));
					}
					if ($media_work->get_value('keywords'))
					{
						$vimeo->call('vimeo.videos.addTags', array('tags'=> str_replace(' ', ',', $media_work->get_value('keywords')), 'video_id' => $video_id));
					}
					$obj = new stdClass();
					$obj->id = $video_id;
					return $obj;
				}
				else {
					// Video file did not exist
					if ($at_remote_url)
					{
						trigger_error("Video file does not exist: ".$filepath);
					}
					else
					{
						trigger_error("Video file does not exist: ".$newpath);
					}
					return false;
				}
			}
			catch (VimeoAPIException $e) {
				trigger_error("Encountered an API error -- code {$e->getCode()} - {$e->getMessage()}");
				return false;
			}
		}
 	}
 	
 	/**
 	 * Vimeo only supports video.
 	 */
 	public function upload_audio($filepath, $media_work, $netid)
 	{}
 	
 	/**
 	 * Vimeo not compatible with podcasting.
 	 */
 	public function get_media_file_url($item, $media_work)
 	{}
 	
 	/**
 	 * Makes a call to the Vimeo simple API and returns an object with some standard metadata.
 	 *
 	 * @param $entry_id string
 	 * @return array or false
 	 */
	function get_video_data($entry_id) 
	{
		$vimeo = new phpVimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, VIMEO_ACCESS_TOKEN, VIMEO_ACCESS_TOKEN_SECRET);
			
		try
		{
			$info = $vimeo->call('vimeo.videos.getInfo', array('video_id' => $entry_id));
		}
		catch (VimeoAPIException $e) 
		{
			return false;
		}

		if ($info)
		{
			return $info->video[0];
		}
		return false;
	} 
	
	public function requires_extension_for_podcast()
 	{
 		return false;
 	}
 	
 	/**
 	 * Returns an array of the video formats Vimeo will accept. List found here: 
 	 * library.rice.edu/services/dmc/guides/video/VideoFormatsGuide.pdf‎
 	 * There is also documentation about .flv not being accepted by Vimeo.
 	 * @return array
 	 */
 	public static function get_recognized_extensions()
 	{
 		return array('mp4', 'mov', 'avi', 'wmv', 'mpeg', 'webm', 'flv', 'asf', 'asx', 'divx', 'dv', 'dvx', 'm4v', 'mpg', 'qt', '3g2', '3gp', '3ivx', '3vx');
 	}
 	
 	public function get_entry_id($video_id)
 	{
 		return $video_id;
 	}
 	
 	public static function get_supported_av_types()
 	{
 		return array('Video');
 	}
}
?>