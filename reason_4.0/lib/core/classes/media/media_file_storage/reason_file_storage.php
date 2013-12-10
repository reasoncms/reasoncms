<?php

include_once('reason_header.php');
reason_include_once('classes/media/media_file_storage/media_file_storage.php');

if(!defined('REASON_MEDIA_FILE_PATH')) define('REASON_MEDIA_FILE_PATH', 'media/files/');
if(!defined('REASON_MEDIA_FILE_FULL_PATH')) define('REASON_MEDIA_FILE_FULL_PATH', '/usr/local/webapps/reason-data/images_and_assets/'.REASON_MEDIA_FILE_PATH);

/**
 * Class that manages media files with the Reason filesystem.
 * 
 * @author Marcus Huderle
 *
 */
class ReasonMediaFileStorageClass extends MediaFileStorageClass
{	
	/**
	 * Actually stores the file in the file system.
	 * @param $media_file_or_id entity or integer
	 * @param $file_url string
	 * @param $custom_filename string (optional name of file to be stored)
	 * @param $media_work string
	 * @param $extra_dir string
	 * @return mixed location of where it was stored or false. (ex: '/reason_package/reason_4.0/www/media/files/...rest of path'
	 */
	public static function store_media($media_file_or_id, $file_url, $custom_filename = '', $media_work = null, $extra_dir = '')
	{
		if (is_object($media_file_or_id))
		{
			$id = $media_file_or_id->get_value('id');
		}
		else
		{
			$id = $media_file_or_id;
		}
		$media_file = new entity($id);
		$url = parse_url($file_url);
		
		if ($custom_filename)
		{
			$filename = $custom_filename;
		}
		else
		{
			$filename = basename($url['path']);
		}
		if ($storage_path = parent::get_path($media_file_or_id, $filename, $media_work, $extra_dir))
		{
			if ($custom_filename)
			{
				$storage_path = str_replace(basename($storage_path), $custom_filename, $storage_path);
			}
			
			$full_storage_path = REASON_MEDIA_FILE_FULL_PATH.$storage_path;
			if (self::_create_directories($full_storage_path))
			{
				if(strpos($file_url, "http") !== false)
				{
					set_time_limit(0);
					$fp = fopen($full_storage_path, 'w+');
					$ch = curl_init($file_url);
					curl_setopt($ch, CURLOPT_TIMEOUT, 18000);
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);
				}
				else
				{
					@mkdir(dirname($full_storage_path), 0775, true);
					copy($file_url, $full_storage_path);
				}
				chmod($full_storage_path, 0775); // TODO: what are the correct permissions?
				return REASON_HTTP_BASE_PATH.REASON_MEDIA_FILE_PATH.$storage_path;
			}
			else
			{
				trigger_error('Failed to create directories for '.$storage_path);
			}
		}
		else
		{
			trigger_error('An invalid storage path was created.');
		}
		return false;
	}
	
	/**
	 * Attempts to create all the necessary directories in the given file path.
	 * @return boolean true on success; false on failure
	 */
	private static function _create_directories($path)
	{
		$truncated_path = substr($path, 0, strrpos($path, '/')+1); // don't include filename
		if (!is_dir($truncated_path))
		{
			$old_umask = umask(0);
			$success = mkdir($truncated_path, 0775, true); // TODO: what are the permissions?
			umask($old_umask);
			return $success;
		}
		return true;
	}
	
	/**
	 * Deletes the file from the file system along with any empty parent directories.
	 * @param $media_file_or_id entity or integer
	 * @param $media_work entity
	 * return boolean true if successful; false otherwise
	 */
	public static function delete_media($media_file_or_id, $media_work = null, $extra_dir = '')
	{
		if (is_object($media_file_or_id))
		{
			$id = $media_file_or_id->get_value('id');
		}
		else
		{
			$id = $media_file_or_id;
		}
		$media_file = new entity($id);
		$filename = self::_get_filename_from_media_file($media_file);
		
		if ($storage_path = parent::get_path($media_file_or_id, $filename, $media_work, $extra_dir))
		{
			$full_storage_path = REASON_MEDIA_FILE_FULL_PATH.$storage_path;
			if (unlink($full_storage_path))
			{
				self::_delete_parent_dirs($storage_path);
			}
			else
			{
				trigger_error('Could not delete file at '.$full_storage_path.'.');
			}
		}
		else
		{
			trigger_error('An invalid storage path was created.');
		}
	}
	
	
	/**
	 * Deletes the still images associated with the media work being deleted.
	 *
	 * @param $media_work
	 * @param $num number of still images to delete
	 */
	public static function delete_media_work_stills($media_work, $num)
	{	
		$stills_path = self::get_path('', 'placeholder.jpg', $media_work, 'stills');
		$stills_path = str_replace(basename($stills_path), '', $stills_path);
		
		for ($i = 0; $i < $num; $i += 1)
		{
			if (!unlink($stills_path.$i.'.jpg'))
			{
				trigger_error('Could not delete still '.$i.' for media work '.$media_work->id().'.');
			}
		}
		self::_delete_parent_dirs($stills_path.$i.'.jpg');
	}
	
	/**
	 * Returns the base path for web-available stills.
	 *
	 * @param $media_work
	 */
	public static function get_stills_base_url($media_work)
	{
		$stills_path = self::get_path('', 'placeholder.jpg', $media_work, 'stills');
		$stills_path = str_replace(basename($stills_path), '', $stills_path);
	
		return self::get_base_url().$stills_path;
 	}
	
	/**
	 * Deletes the original file of a media work and any empty parent directories.
	 * @param $media_work entity
	 */
	public static function delete_original_media($media_work)
	{
		$path = parent::get_path('', '', $media_work, 'original');
		$full_path = REASON_MEDIA_FILE_FULL_PATH.$path;
		if (unlink($full_path))
		{
			self::_delete_parent_dirs($path);
		}
		else
		{
			trigger_error('Could not delete original file at '.$full_path.'.');
		}
	}
	
	// recursively delete empty parent directories. I'm fairly sure it's safe...
	private static function _delete_parent_dirs($path)
	{
		$path = rtrim($path, '/');
		$path = substr($path, 0, strrpos($path, '/'));
		if (!$path)
		{
			return;
		}
		else
		{
			$full_path = REASON_MEDIA_FILE_FULL_PATH.$path;
			if (self::_is_dir_empty($full_path))
			{
				rmdir($full_path);
				self::_delete_parent_dirs($path);
			}
		}
	}
	
	// return true if the directory is empty
	private static function _is_dir_empty($dir_path)
	{
		if (!is_readable($dir_path)) return null; 
		$handle = opendir($dir_path);
		while (($entry = readdir($handle)) !== false) {
			if ($entry != "." && $entry != "..") {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Performs any necessary actions to update the video media file in question. We actually store the
	 * file in Reason here and update the url fields for the media file in Reason.
	 *  
	 * @param $values array
	 * @param $type string
	 * @param $media_work entity
	 * @param $media_file entity
	 */
	public static function update_video_media_file_in_notification_receiver($values, $type, $media_work, $media_file)
	{
		$reason_url = carl_construct_link(array(), array(), self::store_media($media_file->get_value('id'), $values['url'], '', $media_work, $media_file->get_value('flavor_id')));

		$values['url'] = $reason_url;
		$values['download_url'] = $reason_url;
		reason_update_entity($media_file->get_value('id'), $media_work->get_owner()->get_value('id'), $values, false);
	}
	
	/**
	 * Performs any necessary actions to update the audio media file in question. We actually store the
	 * file in Reason here and update the url fields for the media file in Reason.
	 *  
	 * @param $values array
	 * @param $type string
	 * @param $media_work entity
	 * @param $media_file entity
	 * @param $mime_types array
	 */
	public static function update_audio_media_file_in_notification_receiver($values, $type, $media_work, $media_file)
	{
		$reason_url = carl_construct_link(array(), array(), self::store_media($media_file->get_value('id'), $values['url'], '', $media_work));
		
		$values['url'] = $reason_url;
		$values['download_url'] = $reason_url;
		reason_update_entity($media_file->get_value('id'), $media_work->get_owner()->get_value('id'), $values, false);
	}
	
	/**
	 * Returns false because there shouldn't be a destination url with reason file storage for the
	 * Zencoder job.
	 * 
	 * @param $media_file_id string/int
	 * @param $file_extension string
	 * @return boolean
	 */
	public static function get_destination_url_for_transcoded_file($media_file_id, $file_extension, $media_work, $extra_dir = '')
	{
		return false;
	}
	
	/**
	 * Returns url for the original file of the given media work.
	 *
	 * @param $media_work_or_id
	 * @return string
	 */
	public static function get_original_file_for_media_work($media_work_or_id)
	{
		if (!is_object($media_work_or_id))
		{
			$media_work_or_id = new entity($media_work_or_id);
		}
		
		$base_path = self::_generate_dir_path($media_work_or_id).self::_generate_name($media_work_or_id->get_value('name').' original.');
	}
	
	/**
	 * Returns the base url for the web available files.
	 * @return string
	 */
	public static function get_base_url()
	{
		return REASON_HTTP_BASE_PATH.REASON_MEDIA_FILE_PATH;
	}
	
	/**
	 * Called in the Zencoder notification receiver after the Reason media work entity has been
	 * created. We're simply storing the original file and NOT creating a Reason entity for it.
	 *
	 * @param $filepath string
	 * @param $media_work entity
	 */
	public static function post_process_video($filepath, $media_work)
	{
		self::store_media(false, $filepath, '', $media_work, 'original');
	}
	
	/**
	 * Called in the Zencoder notification receiver after the Reason media work entity has been
	 * created. We are either storing the original file (and not creating a Reason entity for it),
	 * or we are storing the original AND creating a Reason entity for it (for mp3 or ogg original
	 * files).
	 *
	 * @param $filepath string
	 * @param $media_work entity
	 * @param $num_outputs int
	 * @param $shim object
	 * @param $netid string
	 */
	public static function post_process_audio($filepath, $media_work, $num_outputs, $shim, $netid)
	{
		if ($num_outputs == 1) 
		{
			$shim->create_audio_source_file($filepath, $media_work, get_user_id($netid));
		}
		elseif ($num_outputs == 2) 
		{
			self::store_media(false, $filepath, '', $media_work, 'original');
		}
	}
	
	/** 
	 * No additional outputs are needed for the Zencoder job in this case, so just return
	 * the provided outputs array.
	 *
	 * @param $outputs array
	 * @param $filename string
	 * @param $media_work entity
	 * @param $shim object
	 * @param $av_type 'Video' or 'Audio'
	 * @return array 
	 */
	public static function add_additional_outputs($outputs, $filename, $media_work, $shim, $av_type)
	{
		return $outputs;
	}
}
?>