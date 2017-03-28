<?php
// This script looks for Vimeo-integrated media works that do not have an associated image.
// Then, it gives them a thumbnail image from Vimeo and updates some metadata that
// wasn't accessible during the media work's creation.
include_once('reason_header.php');
reason_include_once( 'function_libraries/user_functions.php');
include_once(CARL_UTIL_INC.'basic/url_funcs.php');

// require authentication if not running from the command line interface
if (php_sapi_name() != 'cli')
{
	$authenticated_user_netid = reason_require_authentication('admin_login');
}
else
{
	include_once('reason_header.php');
}

if (REASON_MAINTENANCE_MODE) die();

reason_include_once( 'classes/media/vimeo/shim.php' );

$es = new entity_selector();
$es->add_type(id_of('av'));
$es->add_relation('media_work.av_type = "Video"');
$es->add_relation('media_work.integration_library = "vimeo"');
$es->add_relation('media_work.transcoding_status = "converting"');
$media_works = array_merge($es->run_one(), $es->run_one('','Pending'));

$shim = new VimeoShim();

foreach ($media_works as $media_work)
{
	if (update_metadata($media_work, $shim))
	{
		attach_thumbnail($media_work, $shim);
		$user = new entity($media_work->get_value('created_by'));
		$netid = $user->get_value('name');
		send_email($media_work, 'success', $netid);
	}
}

function attach_thumbnail($media_work, $shim)
{	
	// create image file in the vimeo temp directory
	$image_url = $shim->get_thumbnail($media_work->get_value('entry_id'));
	if ($image_url)
	{
		$tmp_path = VimeoShim::get_temp_dir().'tmp_thumbnail_'.$media_work->get_value('id');
		$f = fopen($tmp_path, 'w');
		$contents = get_reason_url_contents($image_url);
		fwrite($f, $contents);
		fclose($f);
		
		$user = new entity($media_work->get_value('created_by'));
		$username = $user->get_value('name');
	
		// Create a reason entity out of the temp image file
		if( !empty($tmp_path) && file_exists( $tmp_path) && $username)
		{
			if ($id = create_image_entity($media_work, $username))
			{
				reason_include_once('content_managers/image.php3');
				$im = new ImageManager();
				$im->thumbnail_width = REASON_STANDARD_MAX_THUMBNAIL_WIDTH;
				$im->thumbnail_height = REASON_STANDARD_MAX_THUMBNAIL_HEIGHT;
				$im->max_width = REASON_STANDARD_MAX_IMAGE_WIDTH;
				$im->max_height = REASON_STANDARD_MAX_IMAGE_HEIGHT;
				$im->load_by_type( id_of('image'), $id, $media_work->get_value('created_by') );
				
				$im->handle_standard_image($id, $tmp_path);		
				$im->create_default_thumbnail($id);
				
				$values = array();
				foreach($im->get_element_names() as $element_name)
				{
					$values[ $element_name ] = $im->get_value($element_name);
				}
				reason_update_entity( $id, get_user_id($username), $values, false );			
				create_relationship($media_work->get_value('id'), $id, relationship_id_of('av_to_primary_image'));
			}
		}	
	}
	else
	{
		echo date(DATE_RFC822).': No thumbnail url found for media work with id '.$media_work->get_value('entry_id')."\n";
	}
}

function create_image_entity($media_work, $username)
{
	$name = $media_work->get_value('name').' (Generated Thumbnail)';
	$values = array();
	$values['new'] = '0';
	$values['description'] = 'A thumbnail image for Vimeo media work '.$media_work->get_value('name');
	$values['no_share'] = '0';
	
	$e = new entity($media_work->get_value('id'));
	$site_id = $e->get_owner()->id();
	
	if ($username)
	{
		return reason_create_entity($site_id, id_of('image'), get_user_id($username), $name, $values);
	}
	else
	{
		echo date(DATE_RFC822).': Empty username. Could not create image entity for Media Work with id '.$media_work->get_value('id').'.'."\n";
	}
	return false;
}

function update_metadata($media_work, $shim)
{
	// update the duration field of the media work.
	$data_obj = $shim->get_video_data($media_work->get_value('entry_id'));
	if ($data_obj && property_exists($data_obj, 'duration') && property_exists($data_obj, 'thumbnails') && !$data_obj->is_transcoding)
	{
		reason_update_entity($media_work->get_value('id'), $media_work->get_value('created_by'), array('transcoding_status' => 'ready', 'media_duration' => format_seconds_as_human_readable(intval($data_obj->duration))), false);
		return true;
	}
	return false;
}

/**
 * Sends the uploader an email regarding the status of the media work's transcoding if they
 * wanted an email notification.  
 * ** REASON_HOST must be set for the generated link to the media work to be correct.
 *
 * @param $media_work entity
 * @param $data object
 * @param $status string
 */
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
		$query_string = carl_make_query_string($params);
		$link = html_entity_decode('https://'.REASON_HOST.'/reason/index.php'.$query_string);
		
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
		
		$mail = new PHPMailer(true);
		$mail->CharSet = 'utf-8';
                
		$mail->addAddress($to);
		$mail->setFrom(WEBMASTER_EMAIL_ADDRESS);
		$mail->Subject = $subject;
		$mail->Body    = $message;
                
		$mail->send(); 
	}
}

?>