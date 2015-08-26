<?php
/**
 * This script is called by Zencoder after all of the output files have been completed and the job
 * is complete. We download the original file, if needed, and we also send an email to the
 * uploader to notify them of the status of the job.
 *
 * @author Marcus Huderle
 */

// Make sure this points to a copy of Zencoder.php on the same server as this script.
include_once('reason_header.php');
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
require_once(INCLUDE_PATH.'zencoder/Services/Zencoder.php');
include_once(CARL_UTIL_INC.'basic/url_funcs.php');
reason_require_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
include_once( CARL_UTIL_INC . 'basic/misc.php' );
reason_include_once('classes/media/zencoder/shim.php');
reason_include_once('content_managers/image.php3');
reason_include_once('function_libraries/user_functions.php');

// give the script unlimited time to finish, since we might be downloading large files
set_time_limit(0);

$zencoder = new Services_Zencoder(ZENCODER_FULL_ACCESS_KEY);
$notification = $zencoder->notifications->parseIncoming();
$media_work = get_media_work($notification->job->id);

if ($media_work)
{
	$user = new entity($media_work->get_value('created_by'));
	$netid = $user->get_value('name');
	
	if ($media_work->get_value('transcoding_status') == 'error')
	{
		$media_work->set_value('transcoding_status', 'converting');
	}
	if ($media_work->get_value('transcoding_status') == 'converting')
	{	
		// first check to see if all successfully transcoded
		$success = true;
		foreach ($notification->job->outputs as $label => $obj)
		{	
			if ($obj->state != "finished") 
			{
				$sucess = false;
				echo $obj->id.' not successful in encoding.'."\n";
				break;
			}
		}
		
		if ($success)
		{
			reason_update_entity($media_work->get_value('id'), $media_work->get_owner()->get_value('id'), array('transcoding_status' => 'finalizing'), false);
			if ($media_work->get_value('av_type') == 'Video')
			{
				process_video($media_work, $notification, $netid);
			}
			elseif ($media_work->get_value('av_type') == 'Audio')
			{
				process_audio($media_work, $notification, $netid);
			}
			else
			{
				echo 'Media Work with id '.$media_work->id().' has invalid av_type.'."\n";
				set_error($media_work);
				send_email($media_work, $notification->job, 'error', $netid);
			}
		}
		else
		{
			echo 'There were errors or cancellations in the transcoding process.'."\n";
			set_error($media_work);
			send_email($media_work, $notification->job, 'error', $netid);
		}
	}
	elseif ($media_work->get_value('transcoding_status') == 'error')
	{
		send_email($media_work, $notification->job, 'error', $netid);
	}
}
else
{
	echo 'No media work with entry_id '.addslashes($notification->job->id).' found in database.'."\n";
	set_error($media_work);
}

/**
 * Update some metadata to finalize the media work. Store the original file, if needed. And clean 
 * up the temporary file, if needed.
 *
 * @param $media_work entity
 * @param $notification object
 * @param $netid string
 */
function process_video($media_work, $notification, $netid)
{
	echo 'Processing Video...'."\n";
	$output = reset($notification->job->outputs);
	
	$duration = get_human_readable_duration(intval($output->duration_in_ms));
	
	$filepath = $media_work->get_value('tmp_file_name');
	if (strpos($filepath, 'http') !== 0)
	{
		$filepath = WEB_PATH.WEB_TEMP.$filepath;	
	}
	finish_processing($notification, $media_work, $netid, $duration);
	ZencoderShim::get_storage_class()->post_process_video($filepath, $media_work);
	// delete the temporary file from our server
	if (strpos($media_work->get_value('tmp_file_name'), 'http') !== 0)
	{
		unlink(WEB_PATH.WEB_TEMP.$media_work->get_value('tmp_file_name'));
	}
}

/**
 * Update some metadata to finalize the media work. Store the original file, if needed. And clean 
 * up the temporary file, if needed.
 *
 * @param $media_work entity
 * @param $notification object
 * @param $netid string
 */
function process_audio($media_work, $notification, $netid)
{
	if (!$notification->job->pass_through)
	{
		echo 'Processing Audio...'."\n";
		$output = reset($notification->job->outputs);
		$duration = get_human_readable_duration(intval($output->duration_in_ms));
		
		$num_outputs = count($notification->job->outputs);
		$filepath = $media_work->get_value('tmp_file_name');
		if (strpos($filepath, 'http') !== 0)
		{
			$filepath = WEB_PATH.WEB_TEMP.$filepath;	
		}
		$shim = new ZencoderShim();
		finish_processing($notification, $media_work, $netid, $duration);
		ZencoderShim::get_storage_class()->post_process_audio($filepath, $media_work, $num_outputs, $shim, $netid);
		// delete the original file from our server
		if (strpos($media_work->get_value('tmp_file_name'), 'http') !== 0)
		{
			unlink(WEB_PATH.WEB_TEMP.$media_work->get_value('tmp_file_name'));
		}
	}
	elseif ($notification->job->pass_through == 'reencoding_audio')
	{
		echo 'Processing reencoded Audio...'."\n";
		$values = array(
			'transcoding_status' => 'ready',
		);
		reason_update_entity($media_work->id(), $media_work->get_owner()->id(), $values, false);
	}
	else
	{
		echo 'Unrecognized pass_through value: '.$notification->job->pass_through."\n";
	}
}

/**
 * Updates some metadata on the media work after a successful transcoding. Sends an email to the
 * uploader.  Cleans up the temporary files on our server.
 *
 * @param $notification object
 * @param $media_work entity
 * @param $netid string
 * @param $duration string
 */
function finish_processing($notification, $media_work, $netid, $duration)
{
	$values = array(
		'transcoding_status' => 'ready',
		'media_duration' => $duration,
	);
	reason_update_entity($media_work->id(), $media_work->get_owner()->id(), $values, false);
	send_email($media_work, $notification->job, 'success', $netid);
}

/**
 * Just sets the transcoding status of a media work to 'error'.
 * 
 * @param $media_work entity
 */
function set_error($media_work)
{
	if (is_object($media_work))
	{
		reason_update_entity($media_work->id(), $media_work->get_owner()->id(), array('transcoding_status' => 'error'), false);
	}
	else
	{
		trigger_error('set_error() called with non-entity');
	}
}

/**
 * Fetches the media work with the given entry id
 *
 * @param $job_id string
 */
function get_media_work($job_id)
{
	$es = new entity_selector();
	$es->add_type(id_of('av'));
	$es->add_relation('media_work.entry_id = "'.addslashes($job_id).'"');
	$results = array_merge($es->run_one(), $es->run_one('','Pending'));
	if (!empty($results))
	{
		return current($results);
	}
	else
	{
		return false;
	}
}

/**
 * Sends the uploader an email regarding the status of the media work's transcoding if they
 * wanted an email notification.
 *
 * @param $media_work entity
 * @param $data object
 * @param $status string
 */
function send_email($media_work, $data, $status, $netid)
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
			if (reason_username_has_access_to_site($netid, $owner->id()))
			{
				$message .= 'View it at this url: '.$link."\n\n";
			}
			$message .= 'Uploaded by:'."\n".$user->get_value('name')."\n\n";
		}
		else
		{
			$subject = '[Reason] Media error: '.html_entity_decode(strip_tags($media_work->get_value('name')));
			
			$message = 'Media Work Error During Processing'."\n\n";
			$message .= 'Name:'."\n".html_entity_decode(strip_tags($media_work->get_value('name')))."\n\n";
			$message .= 'Site:'."\n".html_entity_decode(strip_tags($owner->get_value('name')))."\n\n";
			$message .= 'Uploaded by:'."\n".$user->get_value('name')."\n\n";
			if (reason_username_has_access_to_site($netid, $owner->id()))
			{
				$message .= 'View it at this url: '.$link."\n\n";
			}
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
?>