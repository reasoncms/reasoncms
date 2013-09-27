<?php

include_once('reason_header.php');
reason_include_once( 'classes/media/factory.php' );

/**
 * This is an abstract-like class which should never be instantiated. Instead, its children, which override
 * most of these methods should be used. 
 * 
 * This class is an interface for dealing with the actual files associated with Media Files in Reason. Many
 * of the modules have been written to work with either S3 or Reason file storage, so there are a few
 * methods in this class that seem strange, but they are here so those modules can be generic with respect
 * which storage class is being used.
 * 
 * @author Marcus Huderle
 *
 */
class MediaFileStorageClass
{
	/**
	 * Actually stores the file in the file system. Should be overriden by children of this class.
	 * @param $media_file_or_id entity or integer
	 * @param $filepath string
	 * @param $custom_filename string (optional name of file to be stored)
	 * @param $media_work entity (optional)
	 * @param $extra_dir string
	 * @return string location of where it was stored
	 */
	public static function store_media($media_file_or_id, $filepath, $custom_filename = '', $media_work = '', $extra_dir = '')
	{}
	
	/**
	 * Deletes the file from the file system. Should be overriden by children of this class.
	 * @param $media_file_or_id entity or integer
	 * @param $media_work entity
	 * @param $extra_dir string
	 * return boolean true if successful; false otherwise
	 */
	public static function delete_media($media_file_or_id, $media_work = null, $extra_dir = '')
	{}
	
	/**
	 * Deletes the original file of a media work
	 * @param $media_work entity
	 */
	public static function delete_original_media($media_work)
	{}

	/**
	 * Returns the path used for storing the given media file and temporary file name.
	 *
	 * @param $media_file_or_id entity or integer
	 * @param $tmp_file_name string leave this out if getting the original file's path
	 * @param $media_work entity
	 * @param $extra_dir string
	 * @return string
	 */
	public static function get_path($media_file_or_id, $tmp_file_name = '', $media_work = null, $extra_dir = '')
	{
		if (!$media_file_or_id || is_object($media_file_or_id))
		{
			return self::_get_path_from_entities($media_file_or_id, $tmp_file_name, $media_work, $extra_dir);
		}
		elseif (!is_object($media_file_or_id))
		{
			$media_file = new entity($media_file_or_id);
			if ($media_file->get_value('type') == id_of('av_file'))
			{
				return self::_get_path_from_entities($media_file, $tmp_file_name, $media_work, $extra_dir);
			}
			else
			{
				trigger_error('Non-Media File entity with type '.$media_file_or_id->get_value('type').' given.');
			}
		}		
	}
	
	private static function _get_path_from_entities($media_file, $tmp_file_name, $media_work, $extra_dir)
	{
		if (!$media_work)
		{
			$es = new entity_selector();
			$es->add_type(id_of('av'));
			$es->add_left_relationship($media_file->get_value('id'), relationship_id_of('av_to_av_file'));
			$media_work = current(array_merge($es->run_one(), $es->run_one('','Pending','Deleted')));
		}
		if ($media_work)
		{				
			if ($tmp_file_name)
			{
				$extension = end((explode('.', $tmp_file_name)));
			}
			else
			{
				$extension = end((explode('.', $media_work->get_value('original_filename'))));
			}
			
			if ($extension)
			{
				return self::_generate_dir_path($media_work, $extra_dir).self::_generate_name($media_work->get_value('original_filename'), $extension, $extra_dir, $media_file, $media_work);
			}
			else
			{
				trigger_error('Invalid filename: '.$tmp_file_name.'. It has no extension.');
			}
		}
		else
		{
			trigger_error('No Media Work is associated with Media File with id '.$media_file->get_value('id'));
		}
	}
	
	private static function _generate_dir_path($media_work, $extra_dir)
	{
		$work_id = $media_work->get_value('id');
		
		$last_two_digits = str_repeat("0", (strlen($work_id) <= 2 ? 2 - strlen($work_id) : 0)).substr($work_id, -2);
		
		$displayer = MediaWorkFactory::media_work_displayer($media_work);
		$displayer->set_media_work($media_work);
		$hash = substr($displayer->get_hash(), 0, 12); // only first 12 characters of hash
		if (!$hash)
		{
			trigger_error('Hash was NULL for media work with id '.$media_work->get_value('id'));
			return;
		}
		
		if ($extra_dir)
		{
			return $last_two_digits.'/'.$work_id.'_'.$hash.'/'.$extra_dir.'/';
		}
		else
		{
			return $last_two_digits.'/'.$work_id.'_'.$hash.'/';
		}
	}
	
	private static function _generate_name($orig_filename, $extension, $extra_dir, $media_file, $media_work)
	{	
		$index = strrpos($orig_filename, '.');
		$name = substr($orig_filename, 0, $index);
		if ($extra_dir == 'original')
		{
			$displayer = MediaWorkFactory::media_work_displayer($media_work);
			$displayer->set_media_work($media_work);
			return $name.'_'.$displayer->get_original_filename_hash().'.'.$extension;
		}
		else
		{
			$displayer = MediaWorkFactory::media_work_displayer($media_work);
			$displayer->set_media_work($media_work);
			return $name.'_'.substr($displayer->get_media_file_hash($media_file), 0, 12).'.'.$extension;
		}
	}
	
	public static function _get_filename_from_media_file($media_file)
	{
		$url = $media_file->get_value('url');
		return substr($url, strrpos($url, '/')+1);
	}
	
	/**
	 * Performs any necessary actions to update the video media file in question. For example, we might need to update
	 * metadata or pull a file down from a url.
	 *  
	 * @param $values array
	 * @param $type string
	 * @param $media_work entity
	 * @param $media_file entity
	 */
	public static function update_video_media_file_in_notification_receiver($values, $type, $media_work, $media_file)
	{}
	
	/**
	 * Performs any necessary actions to update the audio media file in question. For example, we might need to update
	 * metadata or pull a file down from a url.
	 *  
	 * @param $values array
	 * @param $type string
	 * @param $media_work entity
	 * @param $media_file entity
	 */
	public static function update_audio_media_file_in_notification_receiver($values, $type, $media_work, $media_file)
	{}
	
	/**
	 * Returns the url that a transcoded file should be placed when it has completed transcoding.
	 * This function is used by Zencoder, specifically.
	 * 
	 * @param $media_file_id string/int
	 * @param $file_extension string
	 */
	public static function get_destination_url_for_transcoded_file($media_file_id, $file_extension, $media_work)
	{}
	
	/**
	 * Deletes the still images associated with the media work being deleted.
	 *
	 * @param $media_work
	 * @param $num number of stills to delete
	 */
	public static function delete_media_work_stills($media_work, $num)
	{}
	
	/**
	 * Returns the base path for stills generated.
	 *
	 * @param $media_work
	 */
	public static function get_stills_base_url($media_work)
	{}
	
	/**
	 * Returns url for original file of the given media work.
	 *
	 * @param $media_work_or_id
	 */
	public static function get_original_file_for_media_work($media_work_or_id)
	{}
	
	/**
	 * Returns the base url for the web available files used by the storage class.
	 * @return string
	 */
	public static function get_base_url()
	{}
	
	/**
	 * Called in the Zencoder notification receiver. Used to generically handle a video
	 * after the Reason entity has been created.
	 * 
	 * @param $filepath string
	 * @param $media_work entity
	 */
	public static function post_process_video($filepath, $media_work)
	{}
	
	/**
	 * Called in the Zencoder notification receiver. Used to generically handle audio
	 * after the Reason entity has been created.
	 *
	 * @param $filepath string
	 * @param $media_work entity
	 * @param $num_outputs int
	 * @param $shim object
	 * @param $netid string
	 */
	public static function post_process_audio($filepath, $media_work, $num_outputs, $shim, $netid)
	{}
	
	/**
	 * Returns whether or not the provided url has expired. Zencoder urls expire after 24 hours.
	 *
	 * @param $url string
	 * @return boolean
	 */
	public static function output_url_has_expired($url)
	{
		preg_match('/Expires=(\d+)/', $url, $matches);
		if (count($matches) > 1)
		{
			$expiration_date = $matches[1];
			if (time() >= $expiration_date) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Used to append any Zencoder job outputs to the outputs array. S3 storage requires an
	 * additional output to transfer the original file. This function is used to generically
	 * handle that case.
	 * 
	 * @param $outputs array
	 * @param $filename string
	 * @param $media_work output
	 * @param $shim object
	 * @param $av_type 'Video' or 'Audio'
	 * @return array
	 */
	public static function add_additional_outputs($outputs, $filename, $media_work, $shim, $av_type)
	{}
}
?>