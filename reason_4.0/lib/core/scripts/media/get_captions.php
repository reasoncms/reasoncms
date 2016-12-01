<?php

/**
 * Load caption/subtitle track text for a Media Work.
 * 
 * Required request params:
 *    media_work_id			int
 *    caption_id			int
 * 	  hash					string, shared with the media work
 */
include ('reason_header.php');
require_once(SETTINGS_INC . 'media_integration/media_settings.php');
reason_include_once('classes/media/factory.php');
reason_include_once('classes/media_work_helper.php');
reason_include_once('function_libraries/user_functions.php');

// check to see if the hash key in $_REQUEST is equal to the specified media work's hash.
function reason_media_captions_check_hash($displayer)
{
	return !empty($_REQUEST['hash']) && $displayer->get_hash() == $_REQUEST['hash'];
}

// return the media work entity specified in the request, or false if there is something wrong
function reason_media_captions_get_media_work()
{
	static $media_work;
	if (isset($media_work))
		return $media_work;

	if (!empty($_REQUEST['media_work_id'])) {
		$id = (integer) $_REQUEST['media_work_id'];
		if ($id) {
			$media_work = new entity($id);
			if ($media_work->get_value('type') == id_of('av') &&
					($media_work->get_value('state') == 'Live' ||
					user_can_edit_site(get_user_id(reason_check_authentication()), get_owner_site_id($id)))) {
				return $media_work;
			}
		}
	}
	$media_work = false;
	return $media_work;
}

/**
 * Make sure request params are all in order, then create the Caption entity
 * @return \entity caption entity
 */
function validate_request()
{
	$caption_id = intval($_REQUEST['caption_id']);
	$media_work = reason_media_captions_get_media_work();

	if (!$caption_id) {
		die("Invalid caption id");
	}
	if (!$media_work) {
		die("Invalid media work requested");
	}

	$displayer = MediaWorkFactory::media_work_displayer($media_work->get_value('integration_library'));
	$displayer->set_media_work($media_work);
	$is_valid_request = reason_media_captions_check_hash($displayer);

	if (!$is_valid_request) {
		die("Invalid media request, missing valid token");
	}

	return new entity($caption_id);
}

/**
 * Respond with the caption/subtitle text
 * @param entity $caption caption entity
 */
function send_response(entity $caption)
{
	// One of the dependencies outputs a "\t" or something somewhere,
	// need to clean it out
	ob_clean();

	header("Content-Type: text/vtt; charset=utf-8");

	echo html_entity_decode($caption->get_value('content'));

	exit;
}

$caption = validate_request();
send_response($caption);

// Testing values:
//ob_clean();
//header("Content-Type;: text/vtt; charset=utf-8");
//echo <<<VTT
//WEBVTT
//
//
//00:01.000 --> 00:04.000
//
//Never drink liquid nitrogen.
//
//
//00:05.000 --> 00:09.000
//
//- It will perforate your stomach.
//- You could die
//VTT;

