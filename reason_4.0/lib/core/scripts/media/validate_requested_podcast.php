<?php
/*
* Given a restricted media work, this script will ask for user http authentication.  If the user
* does not have access, this script will header to a file that tells the user he/she does not
* have access to the requested media.  If the user does have access, this script will header to the
* actual requested item.
* 
* 
*
* REQUEST VARS:
*		media_file_id	
*		media_work_id
*		hash
*
*/

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once( 'classes/group_helper.php' );
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/media_work_displayer.php');


$media_file_id = !empty($_REQUEST['media_file_id']) ? (integer) $_REQUEST['media_file_id'] : 0;
$media_work_id = !empty($_REQUEST['media_work_id']) ? (integer) $_REQUEST['media_work_id'] : 0;
$hash = !empty($_REQUEST['hash']) ? (string) $_REQUEST['hash'] : '';

if ( !$media_file_id || !$media_work_id || !$hash)
{
	http_response_code(404);
	die();
}


$media_file = new entity($media_file_id);
if (!$media_file->get_values() || $media_file->get_value('type') != id_of('av_file'))
{
	http_response_code(404);
	die();
}
# First, grab the media_work associated with the provided media file
$es = new entity_selector();
$es->add_type(id_of('av'));
$es->add_left_relationship($media_file->id(), relationship_id_of('av_to_av_file'));
$es->add_relation('`entity`.`id` = "'.addslashes($media_work_id).'"');
$es->add_relation('`media_work`.`integration_library` = "kaltura"');
$es->set_num(1);
$works = $es->run_one();

if(empty($works))
{
	http_response_code(404);
	die();
}

$media_work = current($works);

# check to make sure the REQUEST var hash is correct
if (MediaWorkDisplayer::get_hash($media_work) != $hash)
{
	http_response_code(404);
	die();
}

# Grab the media_work's associated group if it has one 
$es = new entity_selector();
$es->add_type(id_of('group_type'));
$es->add_right_relationship($media_work->id(), relationship_id_of('av_restricted_to_group'));
$group = current($es->run_one());

$extension = $media_file->get_value('av_type') == 'Video' ? 'mp4' : 'mp3';

# If there is an associated group, check to see if the user has access
# If there is NO associated group, the podcast is available, and we header to its url
if ($group)
{
	$gh = new group_helper();
	$gh->set_group_by_entity($group);
	
	# Get http authentication if the group requires login
	# If it doesn't require login, we can header to the podcast's url
	if($gh->requires_login())
	{
		$username = reason_require_http_authentication();
		if( !$gh->is_username_member_of_group($username) )
		{
			// header to the access denied media
			header('Location: http://'.HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'modules/av/no_access_message.'.$extension);
			die();
		}
	}
	
}

# If we make it here, the podcast is safe to provide
$file_url = $media_file->get_value('url');
	
header('Location: '.$file_url.'/a.'.$extension.'?novar=0');

?>