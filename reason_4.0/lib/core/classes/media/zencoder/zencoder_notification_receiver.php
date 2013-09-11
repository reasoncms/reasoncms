<?php
/**
 * This script is run when Zencoder finishes encoding an output file. Using information present
 * in the notification object, we set up a Reason entity for the output file and handle any file 
 * storage that needs to take place. Some info messages are echoed and they can be viewed in 
 * Zencoder's user interface for the corresponding job.
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

set_time_limit(0);

// maps file extensions to mime type
$mime_type_map = array('mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'webm' => 'video/webm', 'mp4' => 'video/mp4');

$zencoder = new Services_Zencoder(ZENCODER_FULL_ACCESS_KEY);
$notification = $zencoder->notifications->parseIncoming();
$output = current($notification->job->outputs);

$media_work = get_media_work($notification->job->id);
if ($media_work)
{
	$user = new entity($media_work->get_value('created_by'));
	$netid = $user->get_value('name');
	
	// first check to see if all successfully transcoded
	if ($output->state != "finished") 
	{
		echo 'Output '.$output->id.' for job '.$notification->job->id.' not successful in encoding.'."\n";
		echo 'There were errors or cancellations in the transcoding process.'."\n";
		set_error($media_work);
	}
	else
	{
		if ($media_work->get_value('av_type') == 'Video')
		{
			process_video($media_work, $notification, $mime_type_map, $netid);
		}
		elseif ($media_work->get_value('av_type') == 'Audio')
		{
			process_audio($media_work, $notification, $mime_type_map, $netid);
		}
		else
		{
			echo 'Media Work with id '.$media_work->id().' has invalid av_type.'."\n";
			set_error($media_work);
		}
	}
}
else
{
	echo 'No media work with entry_id '.addslashes($notification->job->id).' found in database.'."\n";
	set_error($media_work);
}

////////////////////////////////////////////////////////////////////////////////////

/**
 * Create a Media File for the output file encoded by Zencoder, and then attach it
 * to the Media Work it belongs to. Also create a thumbnail for the Media Work and update 
 * some metadata on the original media work with info present in the output files.
 *
 * @param $media_work entity
 * @param $notification object
 * @param $mime_type_map array
 * @param $netid string
 */
function process_video($media_work, $notification, $mime_type_map, $netid)
{
	echo 'Processing Video Output file...'."\n";
	$output = current($notification->job->outputs);
	$label = $output->label;
	$label_parts = explode('_', $label);
	$format = reset($label_parts);
	$id = end($label_parts);

	// get Reason media file for this output file
	$es = new entity_selector();
	$es->add_type(id_of('av_file'));
	$es->add_relation('entity.id = "'.addslashes($id).'"');
	$media_file = current(array_merge($es->run_one(), $es->run_one('','Pending')));
	
	$duration = get_human_readable_duration(intval($output->duration_in_ms));
	
	// prevent this script from getting activated more than once due to large file transfers.
	// Zencoder retries calling scripts if no response is received fast enough.
	if ($media_file && $media_file->get_value('url') != 'downloading')
	{
		echo 'Processing media file '.$media_file->id()."\n";
		attach_thumbnail($output, $media_work, $netid);
		
		reason_update_entity($media_file->get_value('id'), $media_work->get_owner()->get_value('id'), array('url' => 'downloading'), false);
		
		$url = $output->url;
		$name = basename($url);
		$url = str_replace($name, urlencode($name), $url);
		
		$values = array(
			'new' => 0,
			'av_type' => 'Video',
			'media_format' => 'HTML5',
			'media_size_in_bytes' => $output->file_size_in_bytes,
			'media_size' => format_bytes_as_human_readable(intval($output->file_size_in_bytes)),
			'media_quality' => $output->audio_bitrate_in_kbps.' kbps',
			'mime_type' => $mime_type_map[$format],
			'media_duration' => get_human_readable_duration(intval($output->duration_in_ms)),
			'width' => $output->width,
			'height' => $output->height,
			'media_is_progressively_downloadable' => true,
			'url' => $url,
		);
		
		ZencoderShim::get_storage_class()->update_video_media_file_in_notification_receiver($values, $format, $media_work, $media_file);		
	}
	else
	{
		echo 'No Media File with id '.$id.' was found.'."\n";
		trigger_error('No Media File with id '.$id.' was found.');
	}
}

/**
 * Our scheme for processing audio files is much simpler than videos. We simply receive a notification
 * from Zencoder containing data about our desired output formats and create media files with it.
 *
 * @param $media_work entity
 * @param $notification object
 * @param $mime_type_map array
 * @param $netid string
 */
function process_audio($media_work, $notification, $mime_type_map, $netid)
{
	echo 'Processing Audio...'."\n";
	$output = current($notification->job->outputs);
	$label = $output->label;
	$label_parts = explode('_', $label);
	$format = reset($label_parts);
	$id = end($label_parts);

	// get Reason media file for this output file
	$es = new entity_selector();
	$es->add_type(id_of('av_file'));
	$es->add_relation('entity.id = "'.addslashes($id).'"');
	$media_file = current(array_merge($es->run_one(), $es->run_one('','Pending')));
	$duration = get_human_readable_duration(intval($output->duration_in_ms));
	
	if ($media_file)
	{
		echo 'Processing media file '.$media_file->id();
		$url = $output->url;
		$name = basename($url);
		$url = str_replace($name, urlencode($name), $url);
		$values = array(
			'av_type' => 'Audio',
			'media_format' => 'HTML5',
			'media_size_in_bytes' => $output->file_size_in_bytes,
			'media_size' => format_bytes_as_human_readable(intval($output->file_size_in_bytes)),
			'media_quality' => $output->audio_bitrate_in_kbps.' kbps',
			'mime_type' => $mime_type_map[$format],
			'media_duration' => get_human_readable_duration(intval($output->duration_in_ms)),
			'url' => $url,
		);
		
		ZencoderShim::get_storage_class()->update_audio_media_file_in_notification_receiver($values, $format, $media_work, $media_file);
	}
	else
	{
		echo 'Media File with id'.$id.' could not be stored.'."\n";
		trigger_error('Media File with id '.$id.' could not be stored.');
	}
}

/**
 * Attempt to attach a thumbnail to the media work.
 *
 * @param $obj object
 * @param $media_work entity
 * @param $netid string
 */
function attach_thumbnail($obj, $media_work, $netid)
{	
	// check to see if this is the output file that had a thumbnail generated
	if (property_exists($obj, 'thumbnails'))
	{
		// First, create temp file
		$tmp_path = ZencoderShim::get_temp_dir().'temp_media_image_'.$media_work->get_value('entry_id').'.jpg';
		$f = fopen($tmp_path, 'w');
		$image_url = $obj->thumbnails[0]->images[0]->url;
		$contents = get_reason_url_contents($image_url);
		fwrite($f, $contents);
		fclose($f);
		
		// Then, create image entity.  Attach it to the media work, too.
		if( !empty($tmp_path) AND file_exists( $tmp_path) )
		{
			// Create a new entity for the image
			if ($id = create_image_entity($media_work, $netid))
			{
				$im = new ImageManager();
				//$im->convert_non_web_to = $this->convert_non_web_to;
				$im->thumbnail_width = REASON_STANDARD_MAX_THUMBNAIL_WIDTH;
				$im->thumbnail_height = REASON_STANDARD_MAX_THUMBNAIL_HEIGHT;
				$im->max_width = REASON_STANDARD_MAX_IMAGE_WIDTH;
				$im->max_height = REASON_STANDARD_MAX_IMAGE_HEIGHT;
				$im->load_by_type( id_of('image'), $id, get_user_id($netid) );
				
				$im->handle_standard_image($id, $tmp_path);
				//$im->handle_original_image($id, $image);		
					
				$im->create_default_thumbnail($id);
				
				$dimensions = $obj->thumbnails[0]->images[0]->dimensions;
				$arr = explode('x', $dimensions);
				$thumb_width = intval($arr[0]);
				$thumb_height = intval($arr[1]);
				
				if ( $thumb_width > $im->max_width ||  $thumb_height > $im->max_height)
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
		
				reason_update_entity( $id, get_user_id($netid), $values, false);

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
}

/**
 * Creates an image entity for the video's thumbnail.
 *
 * @param $media_work entity
 * @param $username string
 * @return entity
 */
function create_image_entity($media_work, $username)
{
	$name = $media_work->get_value('name').' (Generated Thumbnail)';
	$values = array();
	$values['new'] = '0';
	$values['description'] = 'A placard image for media work '.$media_work->get_value('name');
	$values['no_share'] = '0';
	
	return reason_create_entity( $media_work->get_owner()->id(), id_of('image'), get_user_id($username), $name, $values);
}

/**
 * Just sets the transcoding status of a media work to 'error'.
 * 
 * @param $media_work entity
 */
function set_error($media_work)
{
	reason_update_entity($media_work->id(), $media_work->get_owner()->id(), array('transcoding_status' => 'error'), false);
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

// Takes milliseconds and returns, for example, "1 minute 18 seconds"
function get_human_readable_duration($duration)
{
	$seconds = intval($duration) / 1000.0;
	return format_seconds_as_human_readable($seconds);
}	
?>