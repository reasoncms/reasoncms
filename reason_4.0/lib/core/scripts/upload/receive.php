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
		final_response(500, "Failed to place the uploaded file in temporary ".
			"storage.");
	}
	
	$img_info = @getimagesize($temp_path);
	if ($img_info)
	{
		// fix a permission idiosyncrasy so the permissions are consistent
		@copy($temp_path, $temp_path.".tmp");
		@rename($temp_path.".tmp", $temp_path);
		list($width, $height) = $img_info;
		if ($constraints && !empty($constraints['max_dimensions'])) {
			list($max_width, $max_height) = $constraints['max_dimensions'];
			
			if ($width > $max_width || $height > $max_height) {
				$unscaled_path = add_name_suffix($temp_path, '-unscaled');
				if (@copy($temp_path, $unscaled_path)) {
					if (resize_image($temp_path, $max_width, $max_height)) {
						list($width, $height) = getimagesize($temp_path);
						clearstatcache();
						$filesize = filesize($temp_path);
					} else {
						@unlink($unscaled_path);
						$unscaled_path = null;
					}
				}
			}
		}
	}
	
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
	}
}
$reason_session->set(_async_upload_session_key($upload_sid), $session);
final_response(200, $response);

function check_constraints($constraints, $file) {
	$path = $file->get_temporary_path();
	
	if (!empty($constraints['mime_types'])) {
		if (!$file->mime_type_matches($constraints['mime_types'])) {
			error_log('Upload reject for file "'.$file->get_filename().'" of MIME type "'. $file->get_mime_type() .'"');
			final_response(415, "File is not of an allowed type.");
		}
	}
	if (!empty($constraints['max_size'])) {
		if ($file->get_size() > (int) $constraints['max_size']) {
			final_response(413, "File is unacceptably large.");
		}
	}
	if (!empty($constraints['validator'])) {
		list($file, $callback) = $constraints['validator'];
		reason_include_once($file);
		if (!call_user_func($callback, $file)) {
			final_response(406, "Invalid or unacceptable file uploaded.");
		}
	}
}

function add_name_suffix($path, $suffix) {
	$parts = explode('.', $path);
	$length = count($parts);
	$target = ($length > 1) ? ($length - 2) : 0;
	
	$parts[$target] .= $suffix;
	return implode('.', $parts);
}

function get_next_index($file) {
	$max = -1;
	foreach (array_keys($file) as $key) {
		$max = max($max, $key);
	}
	return $max + 1;;
}
