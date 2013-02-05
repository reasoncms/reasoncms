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
include_once( SETTINGS_INC.'kaltura_integration_settings.php' );

/**
* This is a shim to abstract Kaltura's api calls into a set of simple methods.
*/
class KalturaShim
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
	*
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
	* Returns the url that contains the generated thumbnail for the specified entry at the specified seconds into the video.
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
	* Returns the source file extension of the given kaltura media entry.
	*
	* @param $kaltura_entry_id
	*/
	public function get_source_file_extension($kaltura_entry_id, $netid = 'Reason')
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try {
			$list = $client->flavorAsset->getFlavorAssetsWithParams($kaltura_entry_id);
			return $list[0]->flavorAsset->fileExt;
		}
		catch (Exception $e)
		{
			trigger_error('Media Work with entry_id '.$kaltura_entry_id.' does not exist in this Kaltura Publisher ('.KALTURA_PARTNER_ID.').');
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
	
	
	/**
	* Deletes a media entry from Kaltura.
	* 
	* @param string $kaltura_entry_id
	* @param string $netid
	* @return void
	*/
	public function delete_media($kaltura_entry_id, $netid)
	{
		$client = $this->_get_kaltura_client($netid, true);
		if (!$client) return false;
		
		try 
		{
			$client->media->delete($kaltura_entry_id);
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
	* Uploads/Adds a video to Kaltura.  The notification receiver handles everything after this initial upload.
	* 
	* @param string $filePath
	* @param string $title
	* @param string $description
	* @param array	$tags
	* @param array	$categories
	* @param string $netid
	* @param int 	$transcoding_profile	Don't provide a transcoding profile for typical uploads.
	* @return KalturaMediaEntry or False is unsuccessful
	*/
	public function upload_video($filePath, $title, $description, $tags, $categories, $netid, $transcoding_profile = KALTURA_DEFAULT_TRANSCODING_PROFILE)
	{		
		$new_entry = $this->_upload_media($filePath, $title, $description, $tags, $categories, $netid, KalturaMediaType::VIDEO, $transcoding_profile);
		return $new_entry;
	}
	
	/**
	* Uploads/Adds an Audio file to Kaltura.
	* 
	* @param string $filePath
	* @param string $title
	* @param string $description
	* @param array	$tags
	* @param array	$categories
	* @param string $netid
	* @param string $file_name
	* @return KalturaMediaEntry or False is unsuccessful
	*/
	public function upload_audio($filePath, $title, $description, $tags, $categories, $netid, $file_name)
	{
		// Determine the correct transcoding profile from the given filename's extension
		$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		if ($extension == 'mp3')
			$transcoding_profile = KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE;
		elseif ($extension == 'ogg')
			$transcoding_profile = KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE;
		else
			$transcoding_profile = KALTURA_AUDIO_TRANSCODING_PROFILE;
			
		$new_entry = $this->_upload_media($filePath, $title, $description, $tags, $categories, $netid, KalturaMediaType::AUDIO, $transcoding_profile);
		return $new_entry;
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
	public function update_media_entry_metadata($entry_id, $netid, $title = '', $description = '', $tags = '', $categories = '')
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
}
?>