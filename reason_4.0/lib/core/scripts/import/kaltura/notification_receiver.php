<?php

/**
* This is a receiver script which specifically handles "entry update" server notifications from 
* Kaltura. To keep the user experience very simple, the uploading process is rather sophisticated.
* Initially, Kaltura merely processes the original uploaded file, and then it sends a server
* notification to this script.  This script responds by uploading that same file to Kaltura, but
* it also tells Kaltura to transcode that file into the appropriate Transcoding Profile which is
* determined by the dimensions of the file and/or the file type (Video/Audio).  After Kaltura
* finishes transcoding, it will send another notification to this script.  Lastly, this script
* will create Media Files to represent each transcoded flavor and attach them to the associated
* Media Work in Reason.  An optional email notification is sent to the user, too.
*
* You must tell Kaltura the url of this script in the Kaltura Management Console in the 
* "Settings->Integration Settings" tabs.
*
* @author Marcus Huderle
* @package reason
* @subpackage scripts
*/

/**
 * Include dependencies
 */
include_once('reason_header.php');
require_once(SETTINGS_INC.'media_integration/kaltura_settings.php');
reason_require_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
reason_require_once('function_libraries/user_functions.php');
require_once(INCLUDE_PATH.'kaltura/KalturaNotificationClient.php');
require_once(INCLUDE_PATH.'kaltura/KalturaClient.php');
reason_include_once('classes/media/kaltura/shim.php');
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once('content_managers/image.php3');
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );
include_once( CARL_UTIL_INC . 'basic/misc.php' );

/**
 * Class that encapsulates kaltura notification receiver logic
 *
 * NOTE: You must change the notification url in the Kaltura Management Console to match this
 * script's url to receive server notifications from Kaltura!
 */
class ReasonKalturaNotificationReceiver
{
	var $filename = 'kaltura_server_notifications.txt';
	var $kaltura_shim;
	var $all_flavors_complete = false;
	
	public static function run()
	{
		// This sleep is probably not needed, but it makes me feel safe.
		sleep(1);
		$receiver = new ReasonKalturaNotificationReceiver();
		$receiver->kaltura_shim = new KalturaShim();
		
		$notification = $receiver->get_data($_POST);
		
		if ($notification == false)
			die();
		
		$receiver->write_to_log($_POST, $notification);
		$receiver->update_reason_media_work($notification);
	}

	// Optional function.  Simply writes some data to a log, if needed.
	function write_to_log($params, $notification)
	{
		$fh = fopen(REASON_LOG_DIR.$this->filename, 'a');
		fwrite($fh, "NOTFICATION RECEIVED\n");
		
		$stringData = date(DATE_RFC822)."\n";
		fwrite($fh, $stringData);
			
		foreach ($notification->data as $data) {
			ob_start();
			print_r($data);
		
			$flavor_assets = $this->kaltura_shim->get_flavor_assets_for_entry($data['entry_id'], $data['puser_id']);
			foreach ($flavor_assets as $asset)
			{
				echo "Flavor==========\n";
				echo "id: ".$asset->id."\n";
				echo "size: ".$asset->size."\n";
				echo "width: ".$asset->width."\n";
				echo "height: ".$asset->height."\n";
				echo "container format: ".$asset->containerFormat."\n";
				echo "video codec id: ".$asset->videoCodecId."\n";
				echo "\n";
			}
			fwrite($fh, ob_get_contents());
			ob_end_clean();
		}
		
		fclose($fh);
	}
	
	// Returns the notification provided by Kaltura's server.
	// Borrowed from http://knowledge.kaltura.com/how-handle-kaltura-server-notifications-php
	function get_data($params)
	{	
		unset($this->params['q']); // removes one of Drupal's parameters which is not relevant for the notification
		
		$noti = new KalturaNotificationClient($params, KALTURA_ADMIN_SECRET);
		if ($noti->valid_signature === NULL) {
			$fh = fopen(REASON_LOG_DIR.$this->filename, 'a');
			fwrite($fh, "The KalturaNotficationClient has no signature.\nExiting notificationReceiver script.\n\n");
			fclose($fh);
			//return false;
		}
		elseif ($noti->valid_signature === FALSE) {
			$fh = fopen(REASON_LOG_DIR.$this->filename, 'a');
			fwrite($fh, "The KalturaNotficationClient has an invalid signature.\nExiting notificationReceiver script.\n\n");
			fclose($fh);
			//return false;
		}
		return $noti;
	}
	
	// Updated the 'status' attribute of the Reason media file that Kaltura finished processing.
	function update_reason_media_work($notification)
	{
		foreach ($notification->data as $data)
		{			
			$es = new entity_selector();
			$es->add_type(id_of('av'));
			$es->add_relation('media_work.entry_id = "'.addslashes($data['entry_id']).'"');
			$results = array_merge($es->run_one(), $es->run_one('','Pending'));
			
			if (!empty($results))
			{	
				$media_work = current($results);
				// First, check to see if the status is abnormal
				if ($data['status'] <= 0)
				{
					reason_update_entity($media_work->id(), $data['puser_id'], array('transcoding_status' => 'error'), false);
					$this->send_email($media_work, $data, 'error');
					trigger_error('Kaltura unsuccessfully transcoded media entry with id = '.$data['entry_id']);
					return;
				}
				
				// Kaltura sends multiple notifications.  This is to make sure we're not overwriting
				// a successful transcoding.
				if ($media_work->get_value('transcoding_status') != 'ready')
				{
					$flavor_assets = $this->kaltura_shim->get_flavor_assets_for_entry($data['entry_id'], $data['puser_id']);
					if (count($flavor_assets) > 0)
					{
						$might_require_email = $media_work->get_value('transcoding_status') != 'ready';
						
						// assume it's going to be an error, set it to 'ready' at the end of this script
						reason_update_entity($media_work->id(), $data['puser_id'], array('transcoding_status' => 'error'), false);
						
						// remove the temporary file in tmp
						try
						{
							$tmp = $media_work->get_value('tmp_file_name');
							if ( strpos($tmp, 'http') !== 0 )
							{
								if($full_tmp = $this->_get_full_tmp_file_path($media_work))
									unlink($full_tmp);
							}
						}
						catch (Exception $ex)
						{}
						
						if ($data['status'] == -1 || $data['status'] == -2)
						{
							$this->send_email($media_work, $data, 'error');
							trigger_error('Kaltura unsuccessfully transcoded media entry with id = '.$data['entry_id']);
							return;
						}
						try
						{
							$this->update_entity_values($media_work, $data);
							$this->generate_thumbnail($media_work, $flavor_assets, $data);
						}
						catch (Exception $ex)
						{
							$this->send_email($media_work, $data, 'error');
							return;
						}
						
						// only send an email of upload success if the media work's status has changed to
						// 'ready' during this receiver script
						$media_work = new entity($media_work->id());
						if ($might_require_email == true && $media_work->get_value('transcoding_status') == 'ready')
							$this->send_email($media_work, $data, 'success');
					}
				}
			}
			else
			{
				trigger_error('No media file found in Reason with entry_id '.$data['entry_id']);
			}
		}
	}
	
	function _get_full_tmp_file_path($media_work)
	{
		if (strpos($media_work->get_value('tmp_file_name'), 'http') === 0)
			return $media_work->get_value('tmp_file_name');
		if(file_exists(KalturaShim::get_temp_dir().$media_work->get_value('tmp_file_name')))
			return KalturaShim::get_temp_dir().$media_work->get_value('tmp_file_name');
		if(file_exists(substr_replace(WEB_PATH,"",-1).WEB_TEMP.$media_work->get_value('tmp_file_name')))
			return substr_replace(WEB_PATH,"",-1).WEB_TEMP.$media_work->get_value('tmp_file_name');
		return NULL;
	}
	
	function send_email($media_work, $data, $status)
	{	
		if ($media_work->get_value('email_notification'))
		{
			$user = new entity(get_user_id($data['puser_id']));
			
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', $data['puser_id'], array('ds_email','ds_fullname','ds_phone',));
			$to = $dir->get_first_value('ds_email');
			$owner = $media_work->get_owner();
			$params = array(
					'site_id' => $owner->id(),
					'type_id' => id_of('av'),
					'id' => $media_work->id(),
					'cur_module' => 'Editor',
			);
			$link = html_entity_decode(carl_construct_link($params, array(''), '/reason/index.php'));
			
			
			if ($status == 'success')
			{
				$subject = '[Reason] Media processing complete: '.html_entity_decode(strip_tags($media_work->get_value('name')));
				
				$message = 'Media Work Processed'."\n\n";
				$message .= 'Name:'."\n".html_entity_decode(strip_tags($media_work->get_value('name')))."\n\n";
				$message .= 'Site:'."\n".html_entity_decode(strip_tags($owner->get_value('name')))."\n\n";
				$message .= 'View it at this url: '.$link."\n\n";
				$message .= 'Uploaded by:'."\n".$user->get_value('name')."\n\n";
			}
			else
			{
				$subject = '[Reason] Media error: '.html_entity_decode(strip_tags($media_work->get_value('name')));
				
				$message = 'Media Work Error During Processing'."\n\n";
				$message .= 'Name:'."\n".html_entity_decode(strip_tags($media_work->get_value('name')))."\n\n";
				$message .= 'Site:'."\n".html_entity_decode(strip_tags($owner->get_value('name')))."\n\n";
				$message .= 'Uploaded by:'."\n".$user->get_value('name')."\n\n";
				$message .= 'View it at this url: '.$link."\n\n";
				$message .= 'If you continue to get this error after multiple attempts, please contact your Reason Administrator regarding this issue: '.WEBMASTER_EMAIL_ADDRESS."\n\n";
			}
			
			$mail = new PHPMailer(true);
			$mail->CharSet = 'utf-8';

			$mail->addAddress($to);
			$mail->setFrom(WEBMASTER_EMAIL_ADDRESS);
			$mail->Subject = $subject;
			$mail->Body    = $message;

			$mail->send();  
		}
	}
	
	function update_entity_values($media_work, $data)
	{
		$flavor_assets = $this->kaltura_shim->get_flavor_assets_for_entry($data['entry_id'], $data['puser_id']);
		
		$valid_flavors = $this->get_valid_flavors($media_work, $flavor_assets, $data);
		
		$owner = $media_work->get_owner();
				
		// Determine what the av_type field should be.  Is it a Video or Audio file?
		if ($data['media_type'] == KalturaMediaType::VIDEO)
			$media_type = 'Video';
		elseif ($data['media_type'] == KalturaMediaType::AUDIO)
			$media_type = 'Audio';
		else 
			trigger_error('invalid media type from Kaltura Server notification: '.$data['media_type']);
		
		foreach ($valid_flavors as $asset)
		{
			$asset_id = $asset->id;
			
			$convert_asset_to_reason = true;
			if ($media_type == 'Audio' && count($flavor_assets) > 2 && $asset->flavorParamsId == 0)
			{
				$convert_asset_to_reason = false;
			}
			else if ($media_type == 'Video' && $asset->flavorParamsId == 0)
			{
				$convert_asset_to_reason = false;
			}
			
			// don't create a Reason Media File for the source flavor of videos or a source flavor for a non-mp4 or non-ogg audio file
			if ($convert_asset_to_reason)
			{	
				$format = $this->kaltura_shim->CONTAINER_TO_MIME_MAP[$asset->containerFormat];
				
				$values = array(
					'url' => $data['data_url'].'/flavor/'.$asset_id,
					'download_url' => $this->kaltura_shim->get_flavor_download_url($asset_id),
					'media_size_in_bytes' => $asset->size*1024,
					'media_size' => format_bytes_as_human_readable($asset->size*1024),
					'default_media_delivery_method' => 'progressive_download',
					'media_format' => 'HTML5',
					'width' => $asset->width,
					'height' => $asset->height,
					'media_is_progressively_downloadable' => true,
					'media_is_streamed' => false,
					'media_quality' => $asset->bitrate.' kbps',
					'flavor_id' => $asset_id,
					'mime_type' => strtolower($media_type).'/'.$format,
					'media_duration' => $this->get_human_readable_duration($data['length_in_msecs']),
					'av_type' => $media_type,
				);
					
				$flavor_name = $media_work->get_value('name').' - '.$format;
				if ($media_type == 'Video')
					$flavor_name .= ' ('.$asset->width.'x'.$asset->height.')';
	
				$flavor_entity_id = reason_create_entity($owner->id(), id_of('av_file'), get_user_id($data['puser_id']), $flavor_name, $values);
				$media_files[] = new entity($flavor_entity_id);
				
				create_relationship($media_work->id(), $flavor_entity_id, relationship_id_of('av_to_av_file'));
			}
		}
		
		if ($this->all_flavors_complete == true)
		{
			$media_work_values = array(
				// might not need this line
				'transcoding_status' => 'ready',
				'media_duration' => $this->get_human_readable_duration($data['length_in_msecs']),
			);
			
			reason_update_entity($media_work->id(), $data['puser_id'], $media_work_values, false);
		}
		else
		{
			$media_work_values = array(
				'transcoding_status' => 'converting',
			);
			reason_update_entity($media_work->id(), $data['puser_id'], $media_work_values, false);
		}
	}
	
	function generate_thumbnail($media_work, $flavor_assets, $data)
	{
		// We use two pieces of logic to determine which profile to use.
		// First, check the height of the video.  Then, make sure its bitrate
		// is high enough for that transcoding profile.  We fall back to the
		// Small profile.
		$asset = current($flavor_assets);
		
		// auto generate the placard image
		// grab the possible associated image
		$es = new entity_selector();
		$es->add_type(id_of('image'));
		$es->add_right_relationship($media_work->id(), relationship_id_of('av_to_primary_image'));
		$image = current($es->run_one());
		
		// Only create a default placard image for videos that don't have a user-specified image.
		if ($media_work->get_value('av_type') == 'Video')
		{
			if (!empty($image)) 
			{
				if (strpos($image->get_value('name'), "(Generated Thumbnail)") != false)
				{
					$this->associate_image($media_work, $data);
				}
			}
			else
			{
				$this->associate_image($media_work, $data);
			}
		}
		// If it's audio AND it has a previous kaltura-generated thumbnail attached, just get rid of it.
		else if ($media_work->get_value('av_type') == 'Audio')
		{
			if (!empty($image))
			{
				if (strpos($image->get_value('name'), "(Generated Thumbnail)") != false) 
				{
					delete_relationships( array( 'entity_a' => $media_work->id(), 'entity_b' => $image->id(),'type' => relationship_id_of('av_to_primary_image')));
				}
			}
		}
	}
	
	function associate_image($media_work, $data)
	{		
		$tmp_path = KalturaShim::get_temp_dir().'temp_media_image_'.$media_work->get_value('entry_id').'.jpg';
		$f = fopen($tmp_path, 'w');
		
		$thumb_opts = array(
			'width' => $data['width'],
			'quality' => 100,
		);
		$image_url = $this->kaltura_shim->get_thumbnail($media_work->get_value('entry_id'), $data['length_in_msecs']/2.0/1000.0, $thumb_opts);
		$contents = get_reason_url_contents($image_url);

		fwrite($f, $contents);
		fclose($f);
		
		if( !empty($tmp_path) AND file_exists( $tmp_path) )
		{
			// Create a new entity for the image
			if ($id = $this->create_image_entity($media_work, $data))
			{
				$im = new ImageManager();
				//$im->convert_non_web_to = $this->convert_non_web_to;
				$im->thumbnail_width = REASON_STANDARD_MAX_THUMBNAIL_WIDTH;
				$im->thumbnail_height = REASON_STANDARD_MAX_THUMBNAIL_HEIGHT;
				$im->max_width = REASON_STANDARD_MAX_IMAGE_WIDTH;
				$im->max_height = REASON_STANDARD_MAX_IMAGE_HEIGHT;
				$im->load_by_type( id_of('image'), $id, get_user_id($data['puser_id']) );
				
				$im->handle_standard_image($id, $tmp_path);
				//$im->handle_original_image($id, $image);		
					
				$im->create_default_thumbnail($id);
				
				if ($data['width'] > $im->max_width ||  $data['height'] > $im->max_height)
				{
					$image_path = PHOTOSTOCK . reason_format_image_filename($id, 'jpg');
				
					$original_path = add_name_suffix($image_path, '_orig');
					@copy($image_path, $original_path);
				
					resize_image($image_path, $im->max_width, $im->max_height);
				}
	
				// Pull the values generated in the content manager
				// and save them to the entity
				$values = array();
				foreach($im->get_element_names() as $element_name)
				{
					$values[ $element_name ] = $im->get_value($element_name);
				}
		
				reason_update_entity( $id, get_user_id($data['puser_id']), $values, false );

				// Remove any existing association with an image and replace it with this new one
				delete_relationships(array('entity_a' => $media_work->id(), 'type' => relationship_id_of('av_to_primary_image')));

				create_relationship($media_work->id(), $id, relationship_id_of('av_to_primary_image'));
			} 
			else 
			{
				trigger_error('Failed to create image entity.');		
			}
		} 
		else 
		{
			trigger_error('No path to image: '.$tmp_path);
		}
	}
	
	function create_image_entity($media_work, $data)
	{
		$name = $media_work->get_value('name').' (Generated Thumbnail)';
		$values = array();
		$values['new'] = '0';
		$values['description'] = 'A placard image for media work '.$media_work->get_value('name');
		$values['no_share'] = '0';
		
		return reason_create_entity( $media_work->get_owner()->id(), id_of('image'), get_user_id($data['puser_id']), $name, $values);
	}
	
	
	// Takes milliseconds and returns, for example, "1 minute 18 seconds"
	function get_human_readable_duration($duration)
	{
		$seconds = intval($duration) / 1000.0;
		return format_seconds_as_human_readable($seconds);
	}
	
	// Returns a list of flavors that are done converting and are not already associated with the media
	// work in reason.  Also, set the class variable $all_flavors_complete to true if all of the flavors
	// are accounted for, and kaltura isn't still converting any of them.
	function get_valid_flavors($media_work, $flavors, $data)
	{		
		// grab the existing media files
		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($media_work->id(), relationship_id_of('av_to_av_file'));
		$media_files = $es->run_one();
				
		// build an array out of their flavor id's
		$existing_ids = array();
		foreach ($media_files as $file)
		{
			$existing_ids[] = $file->get_value('flavor_id');
		}
		
		// assume this will be true; set it false later if it isn't
		$this->all_flavors_complete = true;
		
		$complete_flavors = array();
		foreach ($flavors as $flavor)
		{	
			// status=4 means the flavorAsset won't be converted because it's not applicable
			if ($flavor->status != 4)
			{
				// status=2 means the flavorAsset is 'ready'
				if ($flavor->status != 2)
				{
					$this->all_flavors_complete = false;
				}
				elseif ( !in_array($flavor->id, $existing_ids) )
				{
					$complete_flavors[] = $flavor;
				}
			}
		}
		
		return $complete_flavors;
	}
}

ReasonKalturaNotificationReceiver::run();

?>