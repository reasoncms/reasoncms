<?php
/**
 * Class that encapsulates interactions with Kaltura for Reason's Kaltura integration
 *
 * @package reason
 * @subpackage classes
 * @author Marcus Huderle
 */

/**
 * Include dependencies
 */
require_once( INCLUDE_PATH.'kaltura/KalturaClient.php' );
require_once(SETTINGS_INC.'media_integration/kaltura_settings.php');
reason_include_once('classes/media/interfaces/shim_interface.php');
require_once(SETTINGS_INC.'media_integration/media_settings.php');

/**
 * This is a shim to abstract Kaltura's api calls into a set of simple methods.
 */
class KalturaShim implements ShimInterface
{	
	var $user_id;
	var $client;
		
	// Mapping for containerFormat -> mime type
	var $CONTAINER_TO_MIME_MAP = array(
		'mp42' => 'mp4',
		'isom' => 'mp4',
		'ogg' => 'ogg',
		'matroska' => 'webm',
		'mpeg audio' => 'mpeg',
		'flash video' => 'x-flv',
	);
	
	/**
	 * Trigger an error if a KalturaShim is created when Kaltura integration is disabled.
	 */
	function __construct() {
		if (!$this->kaltura_enabled())
			trigger_error('Kaltura integration is not enabled for Reason.  Use of the KalturaShim class is not allowed.');
	}
	
	/**
	 * Returns an array of Kaltura's recognized file extensions.
	 */
	public static function get_recognized_extensions()
	{
		return array('flv', 'f4v', 'mov', 'mp4', 'wmv', 'qt', 'm4v', 'avi', 'wvm', 'mpg', 'ogg', 'rm', 'webm', 'mp3', 'aiff', 'mpeg', 'wav', 'm4a', 'aac', 'ogv');
	}
	
	/**
	 * Returns true if kaltura integration is enabled for Reason.
	 * @return boolean
	 */
	public static function kaltura_enabled()
	{
		return defined('KALTURA_REASON_INTEGRATED') ? KALTURA_REASON_INTEGRATED : false;
	}
	
	
	/**
	 * Returns an array with 'width' and 'height' keys containing the original dimensions for the video.'
	 *
	 * @param string $kaltura_entry_id
	 * @param string $netid
	 * @return array('width'=>123,'height'=>456)
	 */
	public function get_video_original_dimensions($kaltura_entry_id, $netid = 'Reason')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			$entry = $client->media->get($kaltura_entry_id);
			$width = $entry->width;
			$height = $entry->height;
			return array('width' => $width, 'height' => $height);
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	
	/**
	 * Get a thumbnail URL from Kaltura
	 *
	 * Returns the url that contains the generated thumbnail for the  specified entry at the specified seconds into the video.
	 * Returns false if $seconds is not a number.
	 * 
	 * For a complete documentation on possibilities for param $options, go to http://knowledge.kaltura.com/kaltura-thumbnail-api.
	 * Here is an example of an $options array:
	 * 	$options = array('width' => 320, 'height' =>240, 'quality' => 60);
	 *
	 * @param string $kaltura_entry_id
	 * @param float $seconds
	 * @param array $options not required
	 * @return mixed URL or false
	 */
	public function get_thumbnail($kaltura_entry_id, $seconds, $options = array())
	{
		if ($this->kaltura_enabled())
		{
			if (is_numeric($seconds))
			{
				$url = KALTURA_SERVICE_URL.'/p/'.KALTURA_PARTNER_ID.'/thumbnail/entry_id/'.$kaltura_entry_id.'/vid_sec/'.$seconds;
			
				foreach ($options as $key => $val)
				{
					$url .= '/'.$key.'/'.$val;
				}
				return $url;
			}
		}
		return false;
	}
	
	/**
	 * Returns the source file extension of the given kaltura-integrated media work.
	 *
	 * @param $media_work
	 */
	public function get_source_file_extension($media_work, $netid = 'Reason')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			$list = $client->flavorAsset->getFlavorAssetsWithParams($media_work->get_value('entry_id'));
			return $list[0]->flavorAsset->fileExt;
		}
		catch (Exception $e)
		{
			trigger_error('Media Work with entry_id '.$media_work->get_value('entry_id').' does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
			return false;
		}
	}
	
	
	/**
	 * Returns the url for the media's source data (useful for providing a download link to unmodified files)
	 *
	 * @param string $kaltura_entry_id
	 * @param string $netid
	 */
	public function get_original_data_url($kaltura_entry_id, $netid = 'Reason')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			return $client->media->get($kaltura_entry_id)->downloadUrl;
		}
		catch (Exception $e)
		{
			trigger_error('Media Work with entry_id '.$kaltura_entry_id.' does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
			return false;
		}
	}
	
	/**
	 * Returns the download url for a specific flavor of an entry in Kaltura.
	 * 
	 * @param string $kaltura_flavor_id id of a specific flavor for an entry in kaltura
	 * @param string $netid
	 */
	public function get_flavor_download_url($kaltura_flavor_id, $netid = 'Reason')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			$entry_id = $client->flavorAsset->get($kaltura_flavor_id)->entryId;
			return KALTURA_SERVICE_URL.'/p/'.KALTURA_PARTNER_ID.'/download/entry_id/'.$entry_id.'/flavor/'.$kaltura_flavor_id;
		}
		catch (Exception $e)
		{
			trigger_error('Media File with flavor_id '.$kaltura_flavor_id.' does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
			return false;
		}
	}
	
	
	/**
	 * Returns the length of the media entry in seconds.
	 *
	 * Returns false if something goes wrong.
	 *
	 * @param string $kaltura_entry_id
	 * @param string $netid
	 * @return mixed integer or false
	 */	
	public function get_media_length_in_milliseconds($kaltura_entry_id, $netid = 'Reason')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			return $client->media->get($kaltura_entry_id)->msDuration;
		}
		catch (Exception $e)
		{
			trigger_error('Media Work with entry_id '.$kaltura_entry_id.' does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
			return false;
		}
	}
	
	/*
	 * Tells Kaltura to convert the given media entry to the given transcoding profile.  Doesn't produce a server
	 * notification.
	 *
	 * @param string $kaltura_entry_id
	 * @param int $transcoding_profile_id
	 * @param string $netid
	 * @return void
	 */
	public function convert_media($kaltura_entry_id, $transcoding_profile_id, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try 
		{
			$client->media->convert($kaltura_entry_id, $transcoding_profile_id);
		}
		catch (Exception $e)
		{
			trigger_error('Could not convert Kaltura entry_id '.$entry_id.' because it does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
			return false;
		}
	}
	
	/**
	 * Gets the flavor assets associated with the given entry_id.
	 *
	 * @param string entry_id id of a KalturaMediaEntry
	 * @param string netid
	 * @return array
	 */
	public function get_flavor_assets_for_entry($entry_id, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try
		{
			$flavorFilter = new KalturaAssetFilter();
			$flavorFilter->entryIdEqual = $entry_id;
			$flavor_assets = $client->flavorAsset->listAction($flavorFilter)->objects;
		}
		catch (Exception $e)
		{
			return false;
		}
		
		return $flavor_assets;
	}
	
	/**
	 * Creates a new flavorParams in Kaltura.  Returns true upon success, or false if the flavor already exists.
	 *
	 * @param array $params
	 * @param string $netid
	 */
	public function add_flavor_param($params, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		$pager = new KalturaFilterPager();
		$pager->pageSize = -1;
		$list = $client->flavorParams->listAction(null, $pager);
		$objects = $list->objects;
		foreach ($objects as $param)
		{
			if ($param->name == $params->name)
				return false;
		}
		
		$flavor_param = new KalturaFlavorParams();
		foreach ($params as $key => $val) $flavor_param->$key = $val;
		
		$client->flavorParams->add($flavor_param);
		return true;
	}
	
	public function delete_flavor_param($id, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		$client->flavorParams->delete($id);
	}
	
	/**
	 * Creates a new transcoding profile in Kaltura.  Returns true upon succes, or false if the profile already exists.
	 *
	 * @param conversionProfile
	 * @param string $netid
	 */
	public function add_transcoding_profile($conversionProfile, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		$pager = new KalturaFilterPager();
		$pager->pageSize = -1;
		$list = $client->conversionProfile->listAction(null, $pager);
		$objects = $list->objects;
		foreach ($objects as $profile)
		{
			if ($profile->name == $conversionProfile->name)
				return false;
		}
		$client->conversionProfile->add($conversionProfile);
		return true;
	}
	
	/**
	 * Return an array of form {name: id} of existing flavors in Kaltura.
	 *
	 * @param string $netid
	 */
	public function get_flavor_ids($netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		$result = array();
		$pager = new KalturaFilterPager();
		$pager->pageSize = -1;
		$list = $client->flavorParams->listAction(null, $pager);
		$objects = $list->objects;
		foreach ($objects as $param)
		{
			$result[$param->name] = $param->id;
		}
		return $result;
	}
	
	
	/**
	 * Starts a new session with Kaltura and returns a client object.
	 *
	 * @param string netid
	 * @param boolean isAdmin default true
	 * @return mixed KalturaClient or false
	 */
	private function _get_kaltura_client($netid, $isAdmin = true)
	{
		if (empty($netid))
			$netid = 'anonymous';
		if ($this->kaltura_enabled())
		{
			if ($netid != $this->user_id)
			{
				$kConfig = new KalturaConfiguration(KALTURA_PARTNER_ID);
				$kConfig->serviceUrl = KALTURA_SERVICE_URL;
				$this->client = new KalturaClient($kConfig);
		
				$session_type = ($isAdmin)? KalturaSessionType::ADMIN : KalturaSessionType::USER; 
				$this->user_id = $netid;		
						
				try
				{
					$ks = $this->client->generateSession(KALTURA_ADMIN_SECRET, $this->user_id, $session_type, KALTURA_PARTNER_ID);
					$this->client->setKs($ks);
				}
				catch(Exception $ex)
				{
					trigger_error('could not start Kaltura session - check configurations in 	KalturaTestConfiguration class');
				}
				return $this->client;
			}
			else
			{
				return $this->client;
			}
		}
		else
		{
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
		$dir = REASON_TEMP_DIR.'kaltura-imports/';
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
		return $dir;
	}
	
	/**
	 * Updates the media entry metadata in kaltura.
	 */
	public function finish_actions($media_work, $user_entity)
	{
		$this->_update_media_entry_metadata($media_work->get_value('entry_id'), $user_entity->get_value('name'), $media_work->get_value('name'), '', '', $this->_get_categories($media_work));
	}
	
	/**
	 * Generates a list of categories for the given media work.
	 */
	function _get_categories($media_work)
	{
		$es = new entity_selector();
		$es->add_type(id_of('category_type'));
		$es->add_right_relationship($media_work->get_value('id'), relationship_id_of('av_is_about_category'));
		$abouts = $es->run_one();
		
		$es = new entity_selector();
		$es->add_type(id_of('category_type'));
		$es->add_right_relationship($media_work->get_value('id'), relationship_id_of('av_refers_to_category'));
		$refers = $es->run_one();
		
		$names = array();
		foreach ($abouts as $cat)
		{
			$names[] = $cat->get_value('name');
		}
		foreach ($refers as $cat)
		{
			$names[] = $cat->get_value('name');
		}
		
		$site = $media_work->get_owner();
		$names[] = $site->get_value('name');
		
		return $names;
	}
	
	/**
	 * Changes the metadata of a kaltura media entry.
	 *
	 * @param string $entry_id id of a KalturaMediaEntry
	 * @param string $netid
	 * @param string $title
	 * @param string $description
	 * @param array $tags
	 * @param array $categories
	 * @return array
	 */
	public function _update_media_entry_metadata($entry_id, $netid, $title = '', $description = '', $tags = '', $categories = '')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try
		{
			$entry = $client->media->get($entry_id);
		}
		catch (Exception $e)
		{
			return false;
		}
		
		// Only update if the entry's status is 'ready', 'pending', or 'preconvert'
		if ($entry->status == 2 || $entry->status == 4 || $entry->status == 1)
		{
			$entry = new KalturaMediaEntry();
			if (!empty($title))
				$entry->name = $title;
			if (!empty($description))
				$entry->description = html_entity_decode(strip_tags($description));
			if (!empty($tags))
				$entry->tags = implode(", ", $tags);
			if (!empty($categories))
				$entry->categories = implode(", ", $categories);
			try 
			{
				$client->media->update($entry_id, $entry);
			} 
			catch (Exception $e)
			{
				trigger_error('Could not update metadata for Media Work with entry_id '.$entry_id.' because it does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
				return false;
			}
		}
	}
	
	/**
	 * Deletes a media work's attached media files and the actual entry in kaltura.
	 */
	public function delete_media_work($media_work_deleter)
	{
		$e = new entity( $media_work_deleter->get_value( 'id' ) );
		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($media_work_deleter->get_value('id'), relationship_id_of('av_to_av_file'));
		$media_files = $es->run_one();
		
		foreach ($media_files as $file)
		{
			reason_expunge_entity($file->id(), $media_work_deleter->admin_page->user_id);
		}
		if($e->get_value('entry_id'))
		{
			$shim = new KalturaShim();
			$user = new entity($media_work_deleter->admin_page->user_id);
			
			$shim->delete_media($e, $user->get_value('name'));
		}
	}
	
	/**
	 * Deletes a media entry from Kaltura.
	 * 
	 * @param string $kaltura_entry_id
	 * @param string $netid
	 * @return void
	 */
	public function delete_media($media_work_or_entry_id, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		if (is_object($media_work_or_entry_id))
		{
			$media_work_or_entry_id = $media_work_or_entry_id->get_value('entry_id');
		}
		
		try 
		{
			$client->media->delete($media_work_or_entry_id);
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	/**
	 * Deletes a flavor asset from Kaltura.
	 * 
	 * @param string $kaltura_flavor_id
	 * @param string $netid
	 * @return void
	 */
	public function delete_flavor_asset($kaltura_flavor_id, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			$client->flavorAsset->delete($kaltura_flavor_id);
		}
		catch (Exception $e)
		{
		}
	}
	
	/**
	 * Uploads/Adds a video to Kaltura. The notification receiver handles everything after this initial upload.
	 *
	 * @param string $filepath
	 * @param entity $media_work
	 * @param string $netid
	 */
	public function upload_video($filepath, $media_work, $netid, $at_remote_url = false)
	{
		$tags = explode(" ", $media_work->get_value('keywords'));
		$categories = $this->_get_categories($media_work);
		
		if ($at_remote_url)
		{
			$file_contents = carl_util_get_url_contents($filepath);
			$filename = basename($filepath);
			$parts = explode('.', $filename);
			$extension = end($parts);
			$id = uniqid();
			$new_filename = $id.'.'.$extension;
			$newpath = WEB_PATH.WEB_TEMP.$new_filename;
			if (file_put_contents($newpath, $file_contents))
			{
				$media_work->set_value('tmp_file_name', $new_filename);
				if ($transcoding_profile = $this->_get_transcoding_profile($newpath))
				{
					$new_entry = $this->_upload_media($newpath, $media_work->get_value('name'), $media_work->get_value('description'), $tags, $categories, $netid, KalturaMediaType::VIDEO, $transcoding_profile);
					return $new_entry;
				}
			}
		}
		else
		{
			if ($transcoding_profile = $this->_get_transcoding_profile($filepath))
			{
				$new_entry = $this->_upload_media($filepath, $media_work->get_value('name'), $media_work->get_value('description'), $tags, $categories, $netid, KalturaMediaType::VIDEO, $transcoding_profile);
				return $new_entry;
			}
		}
		return false;
	}
	
	private function _get_transcoding_profile($filepath)
	{
		$filename = end((explode('/', $filepath)));
 		$oldpath = WEB_PATH.WEB_TEMP.$filename;
 		$newpath = self::get_temp_dir().$filename;
 		copy($oldpath, $newpath);
 		
 		$info = $this->_get_vid_info($newpath);
 		if (!$info)
 			return false;
 		
 		if ($info['height'] <= MEDIA_WORK_SMALL_HEIGHT)
		{
			if ($info['bitrate'] < MEDIA_WORK_SMALL_HEIGHT_BITRATE)
				$transcoding_profile = KALTURA_VIDEO_SMALL_LOW_BANDWIDTH_TRANSCODING_PROFILE;
			else
				$transcoding_profile = KALTURA_VIDEO_SMALL_TRANSCODING_PROFILE;
		}
		else if ($info['height'] <= MEDIA_WORK_MEDIUM_HEIGHT)
		{
			if ($info['bitrate'] < MEDIA_WORK_MEDIUM_HEIGHT_BITRATE)
				$transcoding_profile = KALTURA_VIDEO_MEDIUM_LOW_BANDWIDTH_TRANSCODING_PROFILE;
			else
				$transcoding_profile = KALTURA_VIDEO_MEDIUM_TRANSCODING_PROFILE;							
		}
		else
		{
			if ($info['bitrate'] < MEDIA_WORK_LARGE_HEIGHT_BITRATE)
			{	
				if ($info['bitrate'] < MEDIA_WORK_MEDIUM_HEIGHT_BITRATE)
					$transcoding_profile = KALTURA_VIDEO_LARGE_VERY_LOW_BANDWIDTH_TRANSCODING_PROFILE;
				else
					$transcoding_profile = KALTURA_VIDEO_LARGE_LOW_BANDWIDTH_TRANSCODING_PROFILE;
			}
			else
				$transcoding_profile = KALTURA_VIDEO_LARGE_TRANSCODING_PROFILE;
		}
		unlink($newpath);
 		return $transcoding_profile;
	}
	
	/**
	 * Gets the height and bitrate of the given video file using ffmpeg.
	 * @param $filepath string
	 * @return array
	 */
	private function _get_vid_info($filepath)
	{
		// we want the stderr output
 		$output = shell_exec('ffmpeg -i '.$filepath.' 2>&1 1>/dev/null');
 		$lines = explode("\n", $output);
		// find the line containing the file's bitrate
		$info = array();
		foreach ($lines as $line)
		{
			if (strpos($line, 'bitrate'))
			{
				$match = preg_match('/bitrate: (?P<bitrate>\d+) /', $line, $bitrate);
				if ($match)
				{
					$info['bitrate'] = intval($bitrate['bitrate']);
				}
				else
				{
					return false;
				}
			}
			elseif (strpos($line, 'Video:'))
			{
				$match = preg_match('/(?P<width>\d+)x(?P<height>\d+)/', $line, $dimensions);
				if ($match)
				{
					$info['height'] = intval($dimensions['height']);
				}
				else
				{
					return false;
				}
			}
		}
		return $info;
	}
	
	/**
	 * Uploads/Adds a video to Kaltura with a specified transcoding profile. This function is used by the notification
	 * receiver for the re-upload to kaltura.
	 *
	 * @param string $filepath
	 * @param entity $media_work
	 * @param string $netid
	 * @param int $transcoding_profile (refer to kaltura settings for constants)
	 */
	public function upload_video_with_specified_transcoding_profile($filepath, $media_work, $netid, $transcoding_profile)
	{
		$tags = explode(" ", $media_work->get_value('keywords'));
		$categories = $this->_get_categories($media_work);
	
		$new_entry = $this->_upload_media($filepath, $media_work->get_value('name'), $media_work->get_value('description'), $tags, $categories, $netid, KalturaMediaType::VIDEO, $transcoding_profile);
		return $new_entry;
	}
	
	/**
	 * Uploads/Adds an Audio file to Kaltura.
	 * 
	 * @param string $filepath
	 * @param entity $media_work
	 * @param string $netid
	 */
	public function upload_audio($filepath, $media_work, $netid, $at_remote_url = false)
	{
		// Determine the correct transcoding profile from the given filename's extension
		$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
		if ($extension == 'mp3')
			$transcoding_profile = KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE;
		elseif ($extension == 'ogg')
			$transcoding_profile = KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE;
		else
			$transcoding_profile = KALTURA_AUDIO_TRANSCODING_PROFILE;
			
		$tags = explode(" ", $media_work->get_value('keywords'));
		$categories = $this->_get_categories($media_work);	
			
		if ($at_remote_url)
		{
			$file_contents = carl_util_get_url_contents($filepath);
			$filename = basename($filepath);
			$parts = explode('.', $filename);
			$extension = end($parts);
			$id = uniqid();
			$new_filename = $id.'.'.$extension;
			$newpath = WEB_PATH.WEB_TEMP.$new_filename;
			$media_work->set_value('tmp_file_name', $new_filename);
			if (file_put_contents($newpath, $file_contents))
			{
				$new_entry = $this->_upload_media($newpath, $media_work->get_value('name'), $media_work->get_value('description'), $tags, $categories, $netid, KalturaMediaType::AUDIO, $transcoding_profile);
				return $new_entry;
			}
		}
		else
		{
			$new_entry = $this->_upload_media($filepath, $media_work->get_value('name'), $media_work->get_value('description'), $tags, $categories, $netid, KalturaMediaType::AUDIO, $transcoding_profile);
			return $new_entry;
		}
	}
	
	/**
 	 * Uploads/Adds a file to Kaltura.
	 * 
	 * @param string $filePath
	 * @param string $title
	 * @param string $description
	 * @param array	$tags
	 * @param array	$categories
	 * @param string $netid
	 * @param KalturaMediaType media_type
	 * @return KalturaMediaEntry or false if unsuccessful
	 */
	private function _upload_media($filePath, $title, $description, $tags, $categories, $netid, $media_type, $transcoding_profile)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) {
			echo 'no client '.$netid;
			return false;
		}
		$filePath = str_replace('https://', 'http://', $filePath);
		$entry = new KalturaMediaEntry();
		if (!empty($title)) $entry->name = $title;
		if (!empty($description)) $entry->description = html_entity_decode(strip_tags($description));
		if (!empty($tags)) $entry->tags = implode(", ", $tags);
		if (!empty($categories)) $entry->categories = implode(", ", $categories);
		if (!empty($media_type)) $entry->mediaType = $media_type;
		$entry->conversionProfileId = $transcoding_profile;
		
		if ( strpos($filePath, 'http') === 0 ) // url upload
		{
			$new_entry = $client->media->addFromUrl($entry, $filePath);
		}
		else // file system
		{
			try {
				$token = $client->upload->upload($filePath);
			} catch (KalturaClientException $e) {
				return false;
			}
			if (empty($token))
				return false;
			
			try {
				$new_entry = $client->media->addFromUploadedFile($entry, $token);
			} 
			catch (Exception $e) {
				return false;
			}
		}
		
		return $new_entry;
	}
	
	/**
 	 * Gets the url for the requested media file. Used by the podcast media_files.php script.
 	 * @param $item
 	 * @param $media_work
 	 * @return string 
 	 */
 	public function get_media_file_url($item, $media_work)
 	{
 		reason_include_once( 'classes/media/kaltura/media_work_displayer.php' );
 		$displayer = new KalturaMediaWorkDisplayer();
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
	
	public function requires_extension_for_podcast()
 	{
 		return true;
 	}
	
	public function get_entry_id($entry)
	{
		return $entry->id;
	}
	
	public static function get_supported_av_types()
 	{
 		return array('Video', 'Audio');
 	}
}
?>