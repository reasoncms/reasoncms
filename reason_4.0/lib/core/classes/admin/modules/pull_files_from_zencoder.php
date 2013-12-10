<?php
include_once('reason_header.php');
reason_include_once('classes/admin/modules/default.php');
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
require_once(INCLUDE_PATH.'zencoder/Services/Zencoder.php');
include_once(CARL_UTIL_INC.'basic/url_funcs.php');
reason_require_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
include_once( CARL_UTIL_INC . 'basic/misc.php' );
reason_include_once('classes/media/zencoder/shim.php');
reason_include_once('content_managers/image.php3');
reason_include_once('classes/media/media_file_storage/reason_file_storage.php');
reason_include_once('classes/media/media_file_storage/s3_file_storage.php');
reason_include_once( 'function_libraries/user_functions.php');

/** 
 * This module attempts to re-download files from Zencoder. Note, that Zencoder's download urls
 * expire after 24 hours, so this module will only apply to recently-created Media Works.
 */
class zencoderMediaWorkUpdateModule extends DefaultModule
{	

	function init()
	{
		parent::init();
		
		$this->admin_page->title = 'Re-Download File from Zencoder';
		$this->media_work = new entity($this->admin_page->id);
		
		if ($this->media_work->get_value('integration_library') == 'zencoder')
		{
			$this->user = new entity($this->admin_page->user_id);
		}
	}

	function run()
	{	
		if (REASON_MAINTENANCE_MODE) die("This module can't be run in Reason Maintenance Mode.");
		//if ($this->media_work->get_value('transcoding_status') != 'ready') die('This media work must have a transcoding status of \'ready\' to use this module.');
		
		if (!array_key_exists('run', $this->admin_page->request) || !$this->admin_page->request['run'])
		{
			echo '<p>Attempt to re-download the media files from Zencoder. This will only work if the job was submitted less than 24 hours ago.</p>'."\n";
			$url = carl_make_link(array('run' => '1'));
			echo '<p><a href="'.$url.'">Re-Download Files</a></p>'."\n";
			
			if ($this->media_work->get_value('av_type') == 'Audio')
			{
				echo '<p>Re-encode the audio file with Zencoder.</p>'."\n";
				$url = carl_make_link(array('run' => '2'));
				echo '<p><a href="'.$url.'">Re-Encode Audio</a></p>'."\n";
			}
		}
		else
		{
			if ($this->admin_page->request['run'] == 1)
			{
				$zencoder = new Services_Zencoder(ZENCODER_FULL_ACCESS_KEY);
						
				$values = array(
					'transcoding_status' => 'finalizing',
				);
				reason_update_entity($this->media_work->id(), $this->media_work->get_owner()->id(), $values, false);
				
				$mime_type_map = array('mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'webm' => 'video/webm', 'mp4' => 'video/mp4');
				$outputs = $zencoder->jobs->details($this->media_work->get_value('entry_id'))->outputs;
				
				if ($this->media_work->get_value('av_type') == 'Video')
				{
					foreach ($outputs as $label => $obj)
					{	
						if (!$label)
						{
							continue;
						}

						$label_parts = explode('_', $label);
						$format = reset($label_parts);
						$id = end($label_parts);
					
						$es = new entity_selector();
						$es->add_type(id_of('av_file'));
						$es->add_relation('entity.id = "'.addslashes($id).'"');
						$media_file = current(array_merge($es->run_one(), $es->run_one('','Pending')));
						
						$duration = $this->get_human_readable_duration(intval($obj->duration_in_ms));
						
						if (ZencoderShim::get_storage_class()->output_url_has_expired($obj->url))
						{
							echo '<p>The url for Media File '.$media_file->id().' has expired. Unable to re-download.</p>';
							continue;
						}
						
						if ($media_file)
						{
							echo '<p>Processing media file '.$media_file->id().'</p>'."\n";
				
							$values = array(
								'new' => 0,
								'av_type' => 'Video',
								'media_format' => 'HTML5',
								'media_size_in_bytes' => $obj->file_size_bytes,
								'media_size' => format_bytes_as_human_readable(intval($obj->file_size_bytes)),
								'media_quality' => $obj->audio_bitrate_in_kbps.' kbps',
								'mime_type' => $mime_type_map[$format],
								'media_duration' => $this->get_human_readable_duration(intval($obj->duration_in_ms)),
								'width' => $obj->width,
								'height' => $obj->height,
								'media_is_progressively_downloadable' => true,
								'url' => $obj->url,
							);
							
							ZencoderShim::get_storage_class()->update_video_media_file_in_notification_receiver($values, $format, $this->media_work, $media_file);
						}
						else
						{
							trigger_error('No Media File with id '.$id.' was found.');
						}
					}
					$this->finish_processing($this->media_work, $this->user->get_value('name'), $duration);
				}
				else
				{
					foreach ($outputs as $label => $obj)
					{
						$label_parts = explode('_', $label);
						$format = reset($label_parts);
						$id = end($label_parts);
					
						$es = new entity_selector();
						$es->add_type(id_of('av_file'));
						$es->add_relation('entity.id = "'.addslashes($id).'"');
						$media_file = current(array_merge($es->run_one(), $es->run_one('','Pending')));
						$duration = $this->get_human_readable_duration(intval($obj->duration_in_ms));
						
						if (ZencoderShim::get_storage_class()->output_url_has_expired($obj->url))
						{
							echo '<p>The url for Media File '.$media_file->id().' has expired. Unable to re-download.</p>';
							continue;
						}
						
						if ($media_file)
						{
							echo '<p>Processing media file '.$media_file->id().'</p>'."\n";
							
							$values = array(
								'av_type' => 'Audio',
								'media_format' => 'HTML5',
								'media_size_in_bytes' => $obj->file_size_bytes,
								'media_size' => format_bytes_as_human_readable(intval($obj->file_size_bytes)),
								'media_quality' => $obj->audio_bitrate_in_kbps.' kbps',
								'mime_type' => $mime_type_map[$format],
								'media_duration' => $this->get_human_readable_duration(intval($obj->duration_in_ms)),
								'url' => $obj->url,
							);
							
							ZencoderShim::get_storage_class()->update_audio_media_file_in_notification_receiver($values, $format, $this->media_work, $media_file);
						}
						else
						{
							echo '<p>Media File with id'.$id.' could not be stored.</p>'."\n";
							trigger_error('Media File with id '.$id.' could not be stored.');
						}
					}
					$this->finish_processing($this->media_work, $this->user->get_value('name'), $duration);
				}
			}
			elseif ($this->admin_page->request['run'] == 2)
			{
				// submit new job to Zencoder using original file
				reason_include_once('classes/media/zencoder/shim.php');
				$shim = new ZencoderShim();
				if (strpos($this->media_work->get_value('tmp_file_name'), 'http') === 0)
				{
					$job = $shim->upload_audio($this->media_work->get_value('tmp_file_name'), $this->media_work, $this->user->get_value('name'), true, true);
				}
				else
				{
					$path = $shim->get_storage_class()->get_path('', '', $this->media_work, 'original');
					$job = $shim->upload_audio($path, $this->media_work, $this->user->get_value('name'), false, true);
				}
				
				if (!$job)
				{
					echo '<p>There was an error during the upload process. Audio files could not be re-encoded.</p>'."\n";
				}
				else
				{
					echo '<p>Audio files have begun encoding with Zencoder.</p>'."\n";
					reason_update_entity($this->media_work->get_value('id'), $this->media_work->get_owner()->get_value('id'), array('transcoding_status' => 'converting', 'entry_id' => $job->id), false);
				}
				// update metadata of media work.  make sure the s3 stuff works.
			}
		}
	}
	
	function finish_processing($media_work, $netid, $duration)
	{
		$values = array(
			'transcoding_status' => 'ready',
			'media_duration' => $duration,
		);
		reason_update_entity($media_work->id(), $media_work->get_owner()->id(), $values, false);
		//$this->send_email($media_work, 'success', $netid);
	}
	
	function send_email($media_work, $status, $netid)
	{	
		if ($media_work->get_value('email_notification'))
		{
			$user = new entity(get_user_id($netid));
			
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', $netid, array('ds_email','ds_fullname','ds_phone',));
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
			
			mail($to, $subject, $message);
		}
	}
	
	// Takes milliseconds and returns, for example, "1 minute 18 seconds"
	function get_human_readable_duration($duration)
	{
		$seconds = intval($duration) / 1000.0;
		return format_seconds_as_human_readable($seconds);
	}
}
?>