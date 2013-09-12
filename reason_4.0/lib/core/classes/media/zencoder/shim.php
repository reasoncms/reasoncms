<?php
/**
 * Include dependencies
 */
require_once(SETTINGS_INC.'media_integration/media_settings.php');
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
reason_include_once('classes/media/interfaces/shim_interface.php');
require_once(INCLUDE_PATH.'zencoder/Services/Zencoder.php');
include_once(CARL_UTIL_INC.'basic/url_funcs.php');
reason_include_once('function_libraries/util.php');

/**
 * Class that encapsulates interactions with Zencoder's services.
 *
 * @package reason
 * @subpackage classes
 * @author Marcus Huderle
 */
class ZencoderShim implements ShimInterface
{	
	private static $storage_class;

	/**
	 * Zencoder integration has been designed to work with either Amazon S3 or Reason file
	 * storage.
	 */
	public static function get_storage_class()
	{
		if (!self::$storage_class)
		{
			self::$storage_class = self::_get_storage_class();
		}
		return self::$storage_class;
	}

	private static function _get_storage_class()
	{
		if (strcasecmp(ZENCODER_FILE_STORAGE_OPTION, 'reason') == 0) 
		{
			reason_include_once('classes/media/media_file_storage/reason_file_storage.php');
			return new ReasonMediaFileStorageClass();
		}
		elseif (strcasecmp(ZENCODER_FILE_STORAGE_OPTION, 's3') == 0)
		{
			reason_include_once('classes/media/media_file_storage/s3_file_storage.php');
			return new S3MediaFileStorageClass();
		}
		else
		{
			trigger_error('Invalid storage option for Zencoder: '.ZENCODER_FILE_STORAGE_OPTION);
			return false;
		}
	}

	/**
	 * Get the directory to use for temp storage for files
	 *
	 * @return string
	 */
	public static function get_temp_dir()
	{
		$dir = WEB_PATH.WEB_TEMP.'zencoder/';
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
		return $dir;
	}
	
	/**
	 * Delete the actual files stored and expunge the attached media files.
	 * @param $media_work_deleter obj
	 */
 	public function delete_media_work($media_work_deleter) 
 	{
 		$media_work = new entity($media_work_deleter->get_value('id'));
 		$this->delete_original_file($media_work);
 	
 		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($media_work_deleter->get_value('id'), relationship_id_of('av_to_av_file'));
		$media_files = $es->run_one();
		foreach ($media_files as $media_file)
		{
			$this->delete_media_file($media_file, $media_work_deleter->get_value('id'), $media_work_deleter->admin_page->user_id);
		}
		
		self::get_storage_class()->delete_media_work_stills($media_work, self::get_num_stills());
 	}
 	
 	/**
 	 * Expunges a Reason media file entity and deletes the actual associated file as well.
 	 * @param $media_file entity
 	 * @param $media_work_id int
 	 * @param $user_id int
 	 * @param $preserve_original_file boolean
 	 */
 	public function delete_media_file($media_file, $media_work_id, $user_id, $preserve_original_file = false)
 	{
 		$media_work = new entity($media_work_id);
 		if (!($preserve_original_file && $media_file->get_value('flavor_id') == 'original'))
 		{
 			self::get_storage_class()->delete_media($media_file->id(), $media_work, $media_file->get_value('flavor_id'));
 		}
 		reason_expunge_entity($media_file->id(), $user_id);
 	}
 	
 	/**
 	 * Deletes the original file for the media work if there is no media file that uses that
 	 * file.
 	 */
 	public function delete_original_file($media_work)
 	{
 		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($media_work->get_value('id'), relationship_id_of('av_to_av_file'));
		$media_files = $es->run_one();
		$no_original = true;
		foreach ($media_files as $media_file)
		{
			if ($media_file->get_value('flavor_id') == 'original')
			{
				$no_original = false;
				break;
			}
		}
		if ($no_original)
		{
			self::get_storage_class()->delete_original_media($media_work);
		}
 	}

 	public function finish_actions($media_work, $user_entity)
 	{}
 	
 	/**
	 * Returns the url for the media's source data (useful for providing a download link to unmodified files)
	 *
	 * @param $media_work entity
	 * @return url string
	 */
	public function get_original_data_url($media_work)
	{
		return self::get_storage_class()->get_base_url().self::get_storage_class()->get_path('', '', $media_work, 'original');
	}
	
	/**
	 * Returns the source file extension of the given media work
	 *
	 * @param $media_work entity
	 * @return extension string
	 */
	public function get_source_file_extension($media_work)
	{
		return end((explode('.', $media_work->get_value('original_filename'))));
	}
 	
 	/**
 	 * Uploads a video file to Zencoder.
 	 * 
 	 * @param $filepath string
 	 * @param $media_work entity
 	 * @param $netid string
 	 * @param $at_remote_url boolean (true if $filepath is at a remote url)
 	 * @return mixed object or false
 	 */
 	public function upload_video($filepath, $media_work, $netid, $at_remote_url = false)
 	{
 		$media_files_to_delete = $this->_get_media_files($media_work);
 		$media_work_to_delete = new entity($media_work->id());
 	
 		$filename = $this->_sanitize_filename(basename($filepath));
		$filepath = str_replace(basename($filepath), rawurlencode(basename($filepath)), $filepath);
		
 		// First, get the height of the video to determine which ouputs we want Zencoder to produce.
 		if (strpos($filepath, 'http://') === 0 || $at_remote_url)
 		{
 			$filepath = str_replace('https://', 'http://', $filepath);
 			$vid_height = $this->_get_video_height($filepath);
 		}
 		else
 		{
 			if (strpos($filepath, 'https://') === 0)
 			{
 				$filepath = str_replace('https://', 'http://', $filepath);
				$vid_height = $this->_get_video_height($filepath);
 			}
 			else
 			{
				$vid_height = $this->_get_video_height($filepath);
 				$filepath = carl_construct_link(array(''), array(''), WEB_TEMP.$filename);
 				$filepath = str_replace('https://', 'http://', $filepath);
 			}
 		}
		
		// TEMPORARY HACK!! We need ffmpeg on Chicago!
		$profile = 'large';
		return $this->_upload_video_with_specified_profile($filepath, $media_work, $netid, $profile, $media_files_to_delete, $media_work_to_delete);
		
		/*if ($vid_height)
		{
			// pick the correct transcoding profile based on the video's height
			if ($vid_height >= MEDIA_WORK_LARGE_HEIGHT)
			{
				$profile = 'large';
			}
			elseif ($vid_height >= MEDIA_WORK_MEDIUM_HEIGHT)
			{
				$profile = 'medium';
			}
			else
			{
				$profile = 'small';
			}
			return $this->_upload_video_with_specified_profile($filepath, $media_work, $netid, $profile, $media_files_to_delete, $media_work_to_delete);
		}
		else
		{
			// If we couldn't identify the video's height, then we'll default to 'small'.
			return $this->_upload_video_with_specified_profile($filepath, $media_work, $netid, 'small', $media_files_to_delete, $media_work_to_delete);
		}*/
 	}
	
 	/**
 	 * Gets the height of the video at the specified filepath using ffmpeg's output. 
 	 *
 	 * @param $filepath string
 	 * @return mixed int or false
 	 */
 	private function _get_video_height($filepath)
 	{
 		// we want the stderr output
 		$output = shell_exec('ffmpeg -i "'.$filepath.'" 2>&1 1>/dev/null');
 		$lines = explode("\n", $output);
		// find the line containing the video dimensions		
		foreach ($lines as $line)
		{
			if (strpos($line, 'Video:'))
			{	
				$match = preg_match('/(?P<width>\d+)x(?P<height>\d+)/', $line, $dimensions);
				if ($match)
				{
					return intval($dimensions['height']);
				}
				else
				{
					return false;
				}
			}
		}
		return false;
 	}
 	
 	/**
 	 * Uploads a video file to Zencoder to be transcoded into the formats as specified.
 	 * 
 	 * @param $filepath string
 	 * @param $media_work entity
 	 * @param $netid string
 	 * @param $profile int or string in {1,2,3,'small','medium','large}. Use _get_video_output_specs() to provide this array.
 	 * @param $media_files_to_delete array
 	 * @param $media_work_to_delete entity
 	 * @return mixed object or false
 	 */
 	private function _upload_video_with_specified_profile($filepath, $media_work, $netid, $profile, $media_files_to_delete, $media_work_to_delete)
 	{
 		try {
			$zencoder = new Services_Zencoder(ZENCODER_FULL_ACCESS_KEY);
			
			$outputs = $this->_get_video_output_specs($profile, $media_work, $filepath);
			if (!$outputs) return false;
						
			$encoding_job = $zencoder->jobs->create(
				array(
					"input" => $filepath,
					"private" => true,
					"test" => ZENCODER_TEST_MODE,
					"outputs" => $outputs,
					"notifications" => array(
						$this->get_final_notification_receiver_url(),
					),
				)
			);
			if ($encoding_job)
			{
				$this->_remove_existing_media($media_files_to_delete, $media_work_to_delete, $media_work, get_user_id($netid));
			}
			return $encoding_job;
		} catch (Services_Zencoder_Exception $e) {
			return false;	
		}
 	}
 	
 	/**
 	 * Creates the appropriate number of media files for the given profile. Also create a relationship
 	 * between each of them and the given media work. Return the array of media files, too.
 	 * 
 	 * @param $media_work entity
 	 * @param $profile string
 	 * @return array
 	 */
 	private function _create_media_files($media_work, $profile)
 	{
 		$media_files = array();
 		if ($profile == 'small')
 		{
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp4 small', array('av_type' => 'Video', 'flavor_id' => 'small'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' webm small', array('av_type' => 'Video', 'flavor_id' => 'small'));
 		} 
 		elseif ($profile == 'medium')
 		{
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp4 small', array('av_type' => 'Video', 'flavor_id' => 'small'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' webm small', array('av_type' => 'Video', 'flavor_id' => 'small'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp4 medium', array('av_type' => 'Video', 'flavor_id' => 'medium'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' webm medium', array('av_type' => 'Video', 'flavor_id' => 'medium'));
 		}
 		elseif ($profile == 'large')
 		{
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp4 small', array('av_type' => 'Video', 'flavor_id' => 'small'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' webm small', array('av_type' => 'Video', 'flavor_id' => 'small'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp4 medium', array('av_type' => 'Video', 'flavor_id' => 'medium'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' webm medium', array('av_type' => 'Video', 'flavor_id' => 'medium'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp4 large', array('av_type' => 'Video', 'flavor_id' => 'large'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' webm large', array('av_type' => 'Video', 'flavor_id' => 'large'));
 		}
 		elseif ($profile == 'mp3')
 		{
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' ogg', array('av_type' => 'Audio') );
 		}
 		elseif ($profile == 'ogg')
 		{
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp3', array('av_type' => 'Audio'));
 		}
 		elseif ($profile == 'audio')
 		{
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' mp3', array('av_type' => 'Audio'));
 			$media_files[] = reason_create_entity( $media_work->get_owner()->id(), id_of('av_file'), $media_work->get_value('created_by'), $media_work->get_value('name').' ogg', array('av_type' => 'Audio'));
 		}
 		else
 		{
 			return false;
 		}
 		 		
 		foreach ($media_files as $id)
 		{
 			create_relationship($media_work->id(), $id, relationship_id_of('av_to_av_file'));
 		}
 		
 		return $media_files;
 	}
 	
 	/**
 	 * Returns the corresponding output array of specifications for the given profile.
 	 * @param $profile array or false Valid inputs are {1, 2, 3, "small", "medium", "large"}
 	 * @return mixed array or false
 	 */
 	private function _get_video_output_specs($profile, $media_work, $filepath)
 	{
 		$filename = basename($filepath);
		$parts = explode('.', $filename);
		$extension = end($parts);
 	
 		$stills_directory = self::get_storage_class()->get_stills_base_url($media_work);
 	
 		if (intval($profile) == 1 || strtolower($profile) == 'small')
 		{
 			$media_files = $this->_create_media_files($media_work, 'small', $filepath);
 		
 			$output = array(
				array(
					"label" => "mp4_small_".$media_files[0],
					"format" => "mp4",
					"video_codec" => "h264",
					"audio_codec" => "aac",
					"height" => MEDIA_WORK_SMALL_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 300,
					"thumbnails" => array(
						"number" => $this->get_num_stills(),
						"format" => "jpg",
						"base_url" => $stills_directory,
						"filename" => "{{number}}",
					),
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[0], 'mp4', $media_work, 'small'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "webm_small_".$media_files[1],
					"format" => "webm",
					"video_codec" => "vp8",
					"audio_codec" => "vorbis",
					"height" => MEDIA_WORK_SMALL_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 300,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[1], 'webm', $media_work, 'small'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
			);
 		}
 		elseif (intval($profile) == 2 || strtolower($profile) == 'medium')
 		{
 			$media_files = $this->_create_media_files($media_work, 'medium', $filepath);
 			
 			$output = array(
				array(
					"label" => "mp4_small_".$media_files[0],
					"format" => "mp4",
					"video_codec" => "h264",
					"audio_codec" => "aac",
					"height" => MEDIA_WORK_SMALL_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 300,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[0], 'mp4', $media_work, 'small'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "webm_small_".$media_files[1],
					"format" => "webm",
					"video_codec" => "vp8",
					"audio_codec" => "vorbis",
					"height" => MEDIA_WORK_SMALL_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 300,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[1], 'webm', $media_work, 'small'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "mp4_medium_".$media_files[2],
					"format" => "mp4",
					"video_codec" => "h264",
					"audio_codec" => "aac",
					"height" => MEDIA_WORK_MEDIUM_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 600,
					"thumbnails" => array(
						"number" => $this->get_num_stills(),
						"format" => "jpg",
						"base_url" => $stills_directory,
						"filename" => "{{number}}",
					),
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[2], 'mp4', $media_work, 'medium'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "webm_medium_".$media_files[3],
					"format" => "webm",
					"video_codec" => "vp8",
					"audio_codec" => "vorbis",
					"height" => MEDIA_WORK_MEDIUM_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 600,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[3], 'webm', $media_work, 'medium'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
			);
 		}
 		elseif (intval($profile) == 3 || strtolower($profile) == 'large')
 		{
 			$media_files = $this->_create_media_files($media_work, 'large', $filepath);
 			
 			$output = array(
				array(
					"label" => "mp4_small_".$media_files[0],
					"format" => "mp4",
					"video_codec" => "h264",
					"audio_codec" => "aac",
					"height" => MEDIA_WORK_SMALL_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 300,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[0], 'mp4', $media_work, 'small'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "webm_small_".$media_files[1],
					"format" => "webm",
					"video_codec" => "vp8",
					"audio_codec" => "vorbis",
					"height" => MEDIA_WORK_SMALL_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 300,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[1], 'webm', $media_work, 'small'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "mp4_medium_".$media_files[2],
					"format" => "mp4",
					"video_codec" => "h264",
					"audio_codec" => "aac",
					"height" => MEDIA_WORK_MEDIUM_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 600,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[2], 'mp4', $media_work, 'medium'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "webm_medium_".$media_files[3],
					"format" => "webm",
					"video_codec" => "vp8",
					"audio_codec" => "vorbis",
					"height" => MEDIA_WORK_MEDIUM_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 600,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[3], 'webm', $media_work, 'medium'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "mp4_large_".$media_files[4],
					"format" => "mp4",
					"video_codec" => "h264",
					"audio_codec" => "aac",
					"height" => MEDIA_WORK_LARGE_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 1050,
					"thumbnails" => array(
						"number" => $this->get_num_stills(),
						"format" => "jpg",
						"base_url" => $stills_directory,
						"filename" => "{{number}}",
					),
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[4], 'mp4', $media_work, 'large'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
				array(
					"label" => "webm_large_".$media_files[5],
					"format" => "webm",
					"video_codec" => "vp8",
					"audio_codec" => "vorbis",
					"height" => MEDIA_WORK_LARGE_HEIGHT,
					"aspect_mode" => "preserve",
					"video_bitrate" => 1050,
					"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[5], 'webm', $media_work, 'large'),
					"public" => true,
					"notifications" => $this->get_notification_receiver_url(),
				),
			);
 		}
 		else
 		{
 			trigger_error('Provided output profile is invalid: '.$profile);
 			return false;
 		}
 		
 		$output = self::get_storage_class()->add_additional_outputs($output, $filename, $media_work);
 		return $output;
 	}
 	 	
 	/**
 	 * Uploads an audio file to Zencoder.  Zencoder will encode .ogg and .mp3 file.  If the 
 	 * uploaded file is either an mp3 or an ogg with a reasonable bitrate, we keep that file
 	 * for one of the resulting media files, and only ask for the other output from Zencoder.
 	 * encodings.
 	 * @param $filepath string
 	 * @param $media_work obj
 	 * @param $netid string
 	 * @param $at_remote_url boolean (true if $filepath is at a remote url)
 	 * @param $force_both_encodings boolean (true if both mp3 and ogg versions should be encoded)
 	 * @return mixed object or false
 	 */
 	public function upload_audio($filepath, $media_work, $netid, $at_remote_url = false, $force_both_encodings = false)
 	{ 	
 		$media_files_to_delete = $this->_get_media_files($media_work);
 		$media_work_to_delete = new entity($media_work->id());

 		$filename = $this->_sanitize_filename(basename($filepath));
 		$filepath = str_replace(basename($filepath), rawurlencode(basename($filepath)), $filepath);
 		$extension = end((explode('.', $filename)));
 		$needs_deleting = false;
 		if (strpos($filepath, 'http://') === 0 || $at_remote_url)
 		{
 			$bitrate = $this->_get_bitrate($filepath);
 		}
 		else
 		{
 			if (strpos($filepath, 'https://') === 0)
 			{
 				$filepath = str_replace('https://', 'http://', $filepath);
				$bitrate = $this->_get_bitrate($filepath);
 			}
 			else
 			{
				$bitrate = $this->_get_bitrate($filepath);
				if ($force_both_encodings)
				{
					$filepath = self::get_storage_class()->get_base_url().$filepath;
				}
				else
				{
 					$filepath = carl_construct_link(array(''), array(''), WEB_TEMP.$filename);
 				}
 				$filepath = str_replace('https://', 'http://', $filepath);
 			}
 		}

		/*if (!$force_both_encodings)
		{
 			$profile = $this->_get_audio_profile($extension, $bitrate);
 		}
 		else
 		{
 			$profile = 'both';
 		}*/
 		// TEMPORARY HACK TO GET REASONABLE AUDIO ENCODINGS!  INSTALL ffmpeg ON CHICAGO!!
 		$profile = 'both';
 		
		$zencoder = new Services_Zencoder(ZENCODER_FULL_ACCESS_KEY);
		try {
			if ($profile == 'mp3')
			{
				$media_files = $this->_create_media_files($media_work, 'mp3', $filename);
				
				$output = array(
					array(
						"label" => "ogg_".$media_files[0],
						"format" => "ogg",
						"audio_codec" => "vorbis",
						"audio_quality" => 3,
						"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[0], 'ogg', $media_work),
						"public" => true,
						"notifications" => $this->get_notification_receiver_url(),
					),
				);
				$output = self::get_storage_class()->add_additional_outputs($output, $filename, $media_work);
				
				$encoding_job = $zencoder->jobs->create(
					array(
						"input" => $filepath,
						"private" => true,
						"test" => ZENCODER_TEST_MODE,
						"outputs" => $output,
						"notifications" => array(
							$this->get_final_notification_receiver_url(),
						),
					)
				);
				if ($encoding_job)
				{
					$this->_remove_existing_media($media_files_to_delete, $media_work_to_delete, $media_work, get_user_id($netid));
				}
				return $encoding_job;
			}
			elseif ($profile == 'ogg')
			{	
				$media_files = $this->_create_media_files($media_work, 'ogg', $filename);
				
				$output = array(
					array(
						"label" => "mp3_".$media_files[0],
						"format" => "mp3",
						"audio_codec" => "mp3",
						"audio_quality" => 3,
						"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[0], 'mp3', $media_work),
						"public" => true,
						"notifications" => $this->get_notification_receiver_url(),
					),
				);
				$output = self::get_storage_class()->add_additional_outputs($output, $filename, $media_work);
				
				$encoding_job = $zencoder->jobs->create(
					array(
						"input" => $filepath,
						"private" => true,
						"test" => ZENCODER_TEST_MODE,
						"outputs" => $output,
						"notifications" => array(
							$this->get_final_notification_receiver_url(),
						),
					)
				);
				if ($encoding_job)
				{
					$this->_remove_existing_media($media_files_to_delete, $media_work_to_delete, $media_work, get_user_id($netid));
				}
				return $encoding_job;
			}
			else
			{
				if ($needs_deleting)
				{
					unlink($newpath);
				}
				$media_files = $this->_create_media_files($media_work, 'audio', $filename);	
				$dest_url = self::get_storage_class()->get_destination_url_for_transcoded_file(false, $extension, $media_work, 'original');
				
				$pass_through = null;
				if ($force_both_encodings)
				{
					$pass_through = 'reencoding_audio';
				}
				
				$output = array(
					array(
						"label" => "mp3_".$media_files[0],
						"format" => "mp3",
						"audio_codec" => "mp3",
						"audio_quality" => 3,
						"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[0], 'mp3', $media_work),
						"public" => true,
						"notifications" => $this->get_notification_receiver_url(),
					),
					array(
						"label" => "ogg_".$media_files[1],
						"format" => "ogg",
						"audio_codec" => "vorbis",
						"audio_quality" => 3,
						"url" => self::get_storage_class()->get_destination_url_for_transcoded_file($media_files[1], 'ogg', $media_work),
						"public" => true,
						"notifications" => $this->get_notification_receiver_url(),
					),
				);
				$output = self::get_storage_class()->add_additional_outputs($output, $filename, $media_work);
				
				$encoding_job = $zencoder->jobs->create(
					array(
						"input" => $filepath,
						"private" => true,
						"test" => ZENCODER_TEST_MODE,
						"outputs" => $output,
						"notifications" => array(
							$this->get_final_notification_receiver_url(),
						),
						"pass_through" => $pass_through,
					)
				);
				if ($encoding_job)
				{
					if ($force_both_encodings)
					{
						$this->_remove_existing_media_files($media_files_to_delete, $media_work, get_user_id($netid), true);
					}
					else
					{
						$this->_remove_existing_media($media_files_to_delete, $media_work_to_delete, $media_work, get_user_id($netid));
					}
				}
				return $encoding_job;
			} 	
 		} catch (Services_Zencoder_Exception $e) {
			return false;	
		}
 	}
 	
 	private function _get_media_files($media_work)
 	{
 		$es = new entity_selector();
 		$es->add_type(id_of('av_file'));
 		$es->add_right_relationship($media_work->get_value('id'), relationship_id_of('av_to_av_file'));
 		$media_files = $es->run_one();
 		return $media_files;
 	}
 	
 	/**
 	 * Deletes existing media for the given media work. That means deleting the original file, as
 	 * well as deleting media files.
 	 *
 	 * @param $media_files array
 	 * @param $media_work_to_delete entity
 	 * @param $media_work entity
 	 * @param $user_id int
 	 */
 	private function _remove_existing_media($media_files, $media_work_to_delete, $media_work, $user_id)
 	{
 		if (!empty($media_files))
 		{
 			if ($media_work->get_value('original_filename') != $media_work_to_delete->get_value('original_filename'))
 			{
				$this->delete_original_file($media_work_to_delete); 	
 			}
 			$this->_remove_existing_media_files($media_files, $media_work, $user_id, false);
		}
 	}
 	
 	/**
 	 * Deletes existing media files associated with a media work.
 	 * @param $media_files array
 	 * @param $media_work entity
 	 * @param $user_id int
 	 * @param $preserve_original_file boolean
 	 */
 	private function _remove_existing_media_files($media_files, $media_work, $user_id, $preserve_original_file)
 	{
 		// Delete actual Reason entities for media files
		foreach ($media_files as $entity)
		{
			$this->delete_media_file($entity, $media_work->get_value('id'), $user_id, $preserve_original_file);
		}
 	}
 	
 	/**
 	 * Creates a media file whose url is a file that doesn't need to go through Zencoder because
 	 * it's either an mp3 or ogg. 
 	 * @param $filepath string
 	 * @param $media_work entity
= 	 * @param $user_id int
 	 */
 	public static function create_audio_source_file($filepath, $media_work, $user_id)
 	{
 		$filename = basename($filepath);
 		$extension = end((explode('.', $filename)));
 		if ($extension == 'mp3')
 		{
 			$mime_type = 'audio/mpeg';
 		}
 		else
 		{
 			$mime_type = 'audio/ogg';
 		}
 		$needs_deleting = false;
 		if (strpos($filepath, 'http://') !== 0 && strpos($filepath, 'https://') !== 0)
 		{
 			$base_path = str_replace($filename, '', $filepath);
 			$filepath = $base_path.$filename;
 			$filesize = filesize($filepath);
 		}
 		
		$media_file_id = reason_create_entity($media_work->get_owner()->id(), id_of('av_file'), $user_id, $media_work->get_value('name').' '.$extension, array('av_type' =>'Audio', 'flavor_id' => 'original'));
		create_relationship($media_work->id(), $media_file_id, relationship_id_of('av_to_av_file'));
		
		$web_url = self::get_storage_class()->store_media($media_file_id, $filepath, '', $media_work, 'original');
		
		$values = array(
			'media_format' => 'HTML5',
			'url' => $web_url,
			'download_url' => $web_url,
			'mime_type' => $mime_type,
			'media_size_in_bytes' => $filesize,
			'media_size' => format_bytes_as_human_readable($filesize),
			'media_quality' => self::_get_bitrate($filepath).' kbps',
			'media_duration' => self::_get_duration($filepath),
		);
		reason_update_entity($media_file_id, $media_work->get_owner()->id(), $values, false);
 	}
 	
 	/**
 	 * Uses ffmpeg to get the bitrate of the given file.
 	 * @param $filepath string
 	 * @return string
 	 */
 	static function _get_bitrate($filepath)
 	{
 		// we want the stderr output
 		$output = shell_exec('ffmpeg -i '.$filepath.' 2>&1 1>/dev/null');
 		$lines = explode("\n", $output);
		// find the line containing the file's bitrate
		foreach ($lines as $line)
		{
			if (strpos($line, 'bitrate:'))
			{	
				$match = preg_match('/bitrate: (?P<bitrate>\d+) /', $line, $bitrate);
				if ($match)
				{
					return intval($bitrate['bitrate']);
				}
				else
				{
					return false;
				}
			}
		}
		return false;
 	}
 	
 	/**
 	 * Uses ffmpeg to get the human-readable duration of a file.
 	 * @param $filepath string
 	 * @return string
 	 */
 	static function _get_duration($filepath)
 	{
 		$filepath = str_replace('https://', 'http://', $filepath);
 		// we want the stderr output
 		$output = shell_exec('ffmpeg -i '.$filepath.' 2>&1 1>/dev/null');
 		$lines = explode("\n", $output);
		// find the line containing the file's duration
		foreach ($lines as $line)
		{
			if (strpos($line, 'Duration:'))
			{	
				$match = preg_match('/(?P<hours>\d\d):(?P<minutes>\d\d):(?P<seconds>\d\d)/', $line, $duration);
				if ($match)
				{
					break;
				}
				else
				{
					return false;
				}
			}
		}
		$duration_string = '';
		if ($duration['hours'] != '00')
		{
			$duration_string .= intval($duration['hours']).' hours ';
		}
		if ($duration['minutes'] != '00')
		{
			$duration_string .= intval($duration['minutes']).' minutes ';
		}
		if ($duration['seconds'] != '00')
		{
			$duration_string .= intval($duration['seconds']).' seconds ';
		}
		return trim($duration_string);
 	}
 	
 	private function _get_audio_profile($extension, $bitrate)
 	{
 		if (strtolower($extension) == 'mp3' && $bitrate < 800)
 		{
 			return 'mp3';
 		}
 		elseif (strtolower($extension) == 'ogg' && $bitrate < 800)
 		{
 			return 'ogg';
 		}
 		return 'both';
 	}
	
	/**
	 * Builds an url for the podcast validation script for the given media work or media file
	 * item.  This is called directly from the validate_requested_podcast script.
	 * @param $item array
	 * @param $media_work entity
	 * @return mixed string or false
	 */
 	public function get_media_file_url($item, $media_work)
 	{
 		reason_include_once( 'classes/media/zencoder/media_work_displayer.php' );
 		$displayer = new ZencoderMediaWorkDisplayer();
		$displayer->set_media_work($media_work);
		
		switch($item->get_value('mime_type'))
		{
			case 'video/mp4':
				return 'http://'.HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'scripts/media/validate_requested_podcast.mp4?media_file_id='.$item->id().'&amp;media_work_id='.$item->get_value('work_id').'&amp;hash='.$displayer->get_hash();
			case 'audio/mpeg':
				return 'http://'.HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'scripts/media/validate_requested_podcast.mp3?media_file_id='.$item->id().'&amp;media_work_id='.$item->get_value('work_id').'&amp;hash='.$displayer->get_hash();
		}
		return false;
 	}
 	
 	/**
 	 * Returns an array of the input formats Zencoder will accept.  There are undoubtedly more
 	 * obscure formats that Zencoder accepts.
 	 * @return array
 	 */
 	public static function get_recognized_extensions()
 	{
 		return array('flv', 'f4v', 'mov', 'mp4', 'wmv', 'qt', 'm4v', 'avi', 'wvm', 'mpg', 'ogg', 'rm', 'webm', 'mp3', 'aiff', 'aif', 'mpeg', 'wav', 'wma', 'm4a', 'aac', 'ogv', '3gp', '3g2', 'asf', 'mkv', 'mk3d', 'mka', 'mks', 'VOB');
 	}
	
 	public function requires_extension_for_podcast()
 	{
 		return false;
 	}
 	
 	public function get_entry_id($job)
 	{
 		return $job->id;
 	}
 	

 	public static function get_supported_av_types()
 	{
 		return array('Video', 'Audio');
 	}
 	
 	/**
 	 * Returns the number of stills we want to generate when uploading to Zencoder.
 	 * @return int
 	 */
 	public static function get_num_stills()
 	{
 		return 12;
 	}
 	
 	/**
	 * Cleans up a filename by removing all bad characters and replacing spaces with an underscore.
	 * @param $filename
	 * @return string
	 */
	function _sanitize_filename($filename)
	{
		$index = strrpos($filename, '.');
		$name = substr($filename, 0, $index);	
	
		// replace all reserved url characters in the name of the file
		$pattern = '/;|\/|\?|:|@|=|&|\.|#/';
		return str_replace(' ', '_', preg_replace($pattern, '', $name)).substr($filename, $index);
	}
	function get_notification_receiver_url()
	{
		$ret = '';
		if(HTTPS_AVAILABLE)
			$ret .= 'https://';
		else
			$ret .= 'http://';
		$ret .= HTTP_HOST_NAME . REASON_HTTP_BASE_PATH . 'media/zencoder/zencoder_notification_receiver.php';
		return $ret;
	}
	function get_final_notification_receiver_url()
	{
		$ret = '';
		if(HTTPS_AVAILABLE)
			$ret .= 'https://';
		else
			$ret .= 'http://';
		$ret .= HTTP_HOST_NAME . REASON_HTTP_BASE_PATH . '/media/zencoder/zencoder_final_notification_receiver.php';
		return $ret;
	}
}
// 4 more lines
// to get to 
// 1000 total 
// lines!
?>