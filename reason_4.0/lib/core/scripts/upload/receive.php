<?php
/**
 * Handles asynchronous file uploads from (e.g., SWFUpload). Use the Reason
 * {@link UploadedFile} class to work with files uploaded using this script
 * (and uploaded to PHP's built-in POST upload handler).
 *
 * @package reason
 * @subpackage scripts
 * @since Reason 4.0 beta 8
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */
require 'common.inc.php';
require_once CARL_UTIL_INC.'basic/cleanup_funcs.php';
require_once CARL_UTIL_INC.'basic/image_funcs.php';
/* 
 *  include the reason_settings.php file to get the allowable minimum uploaded image dimensions defined in the file 
 */
include_once('settings/reason_settings.php');

if (!function_exists('json_encode')) {
	// A very simple JSON encoder for PHP < 5.2.
	function json_encode($data) {
		if ($data === null) {
			return "null";
		} else if (is_bool($data)) {
			return ($data) ? "true" : "false";
		} else if (is_int($data) || is_float($data)) {
			return (string) $data;
		} else if (is_string($data)) {
			return '"'.addslashes($data).'"';
		} else if (is_object($data)) {
			return _json_encode_object($data);
		} else if (is_array($data)) {
			foreach (array_keys($data) as $key) {
				if (!is_numeric($key))
					return _json_encode_object($data);
			}
			return _json_encode_array($data);
		} else {
			return '';
		}
	}
	
	function _json_encode_array($data)
	{
		return "[".implode(', ', array_map('json_encode', $data))."]";
	}
	
	function _json_encode_object($object)
	{
		$pairs = array();
		foreach ((array) $object as $key => $value) {
			$pairs[] = json_encode((string) $key).': '.json_encode($value);
		}
		return "{".implode(', ', $pairs)."}";
	}
}

$response = array();
foreach (array_keys($_FILES) as $name) {
	$file = reason_get_uploaded_file($name);
	$filename = $file->get_filename();
	
	// gotta set this before checking for constraints as those are based on the actual disco/plasmature field name, not the generic "file" that comes through from plupload...
	if (isset($_POST["rvFieldName"])) { $name = $_POST["rvFieldName"]; }
	
	$constraints = (!empty($session['constraints'][$name]))
		? $session['constraints'][$name]
		: null;
	
	if ($constraints) {
		check_constraints($constraints, $file);
	}
	
	$m = array();
	if (preg_match('/((?:\.tar)?\.[a-zA-Z0-9]+)$/', $filename, $m)) {
		$extension = strtolower($m[0]);
	} else {
		$extension = '';
	}
	
	$unique_name = sha1(uniqid(mt_rand(), true));
	$temp_uri = WEB_TEMP.$unique_name.strtolower($extension);
	$temp_path = $_SERVER['DOCUMENT_ROOT'].$temp_uri;
	$unscaled_path = null;
	$filesize = $file->get_size();
	
	if (!$file->move($temp_path)) {
		responseWrapper(500, "Failed to place the uploaded file in temporary storage.");
	}
	
	$img_info = @getimagesize($temp_path);
	$file_type = $img_info[2];		
	/*
	 *	If uploading an image, but provide a non-web-friendly type (i.e. pdf), convert it and
	 *	update all of its corresponding paths. Note 1=>'gif',2=>'jpg',3=>'png' 
	*/
	$web_acceptable_types = array(1,2,3);
	if( (!$img_info || !in_array($file_type, $web_acceptable_types) ) 
		&& (!empty($constraints['convert_to_image'])) && $constraints['convert_to_image'] )
	{
		// from image_funcs
		$convert_to = 'png';
		if( $temp_path = convert_to_image($temp_path, $convert_to) )
		{
			$img_info = @getimagesize($temp_path);
			$temp_uri = change_extension( $temp_uri, $convert_to );
			$filename = change_extension( $filename, $convert_to );
			$filesize = filesize($temp_path);
		}
		else
		{
			responseWrapper(501, 'Unable to convert the uploaded file to a web-friendly image');
		}
	}
	if ($img_info)
	{
		// fix a permission idiosyncrasy so the permissions are consistent
		@copy($temp_path, $temp_path.".tmp");
		@rename($temp_path.".tmp", $temp_path);
		list($orig_width, $orig_height) = $img_info;
		list($width, $height) = $img_info;
		
		// If exceeds width or height limit, store an original and resize the standard image
		if ($constraints && !empty($constraints['max_dimensions'])) 
		{
			list($max_width, $max_height) = $constraints['max_dimensions'];
			
			if ($width > $max_width || $height > $max_height) 
			{
				$unscaled_path = add_name_suffix($temp_path, '-unscaled');
				if (@copy($temp_path, $unscaled_path)) 
				{
				
					//Make sure the image won't make php crash:
					if(image_is_too_big($temp_path))
					{
						responseWrapper(422, "The uploaded image's dimensions are too large for the server to process. Try a smaller image.");
					}

				
					if (resize_image($temp_path, $max_width, $max_height)) 
					{
						list($width, $height) = getimagesize($temp_path);
						clearstatcache();
						$filesize = filesize($temp_path);
					} else 
					{
						@unlink($unscaled_path);
						$unscaled_path = null;
					}
				}
			}
		}

	}

	/* 
	 * 	 This next part has been commented out because the final_response function seems unable to handle reuse of one error code with 
	 * 	 two different messages (it currently prints out the other error message passed with the 422 code to final_response)
	*/

	/*
	if ($img_info)
	{
		if (!($img_info[0] > REASON_STANDARD_MIN_IMAGE_WIDTH && $img_info[1] > REASON_STANDARD_MIN_IMAGE_HEIGHT))
			final_response(422, 'Uploaded image dimensions are too small. Please upload another image.');
	} 
	*/
	
	if (empty($session['files'][$name]))
		$session['files'][$name] = array();
	
	$index = get_next_index($session['files'][$name]);
	$session['files'][$name][$index] = array(
		'name' => $filename,
		'path' => $temp_path,
		'original_path' => $unscaled_path
	);
	
	$response[$name] = array(
		'index' => $index,
		'filename' => sanitize_filename_for_web_hosting($filename),
		'uri' => $temp_uri,
		'size' => $filesize
	);
	
	if ($img_info) {
		$response[$name]['dimensions'] = array(
			'width' => $width,
			'height' => $height
		);
		$response[$name]['orig_dimensions'] = array(
			'width' => $orig_width,
			'height' => $orig_height
		);
	}
}

$reason_session->set(_async_upload_session_key($upload_sid), $session);
final_response(200, $response);


function check_constraints($constraints, $file) {
	$path = $file->get_temporary_path();
	
	if (!empty($constraints['mime_types'])) {
		if (!$file->mime_type_matches($constraints['mime_types'])) {
			responseWrapper(415, "File is not of an allowed type.");
		}
	}
	if (!empty($constraints['extensions'])) {
		$filename_parts = explode('.', $file->get_filename());
		$extension = strtolower(end($filename_parts));
		if (!in_array($extension, $constraints['extensions'])) {
			responseWrapper(415, "File is not of an allowed type.");
		}
	}
	if (!empty($constraints['max_size'])) {
		if ($file->get_size() > (int) $constraints['max_size']) {
			responseWrapper(413, "File is unacceptably large.");
		}
	}
	if (!empty($constraints['validator'])) {
		list($file, $callback) = $constraints['validator'];
		reason_include_once($file);
		if (!call_user_func($callback, $file)) {
			responseWrapper(406, "Invalid or unacceptable file uploaded.");
		}
	}
}

function get_next_index($file) {
	$max = -1;
	foreach (array_keys($file) as $key) {
		$max = max($max, $key);
	}
	return $max + 1;
}
