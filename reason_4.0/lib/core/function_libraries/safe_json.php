<?php

/**
 * @package reason
 * @subpackage function_libraries
 */

/**
 * This function will handle encoding data into JSON. The data can contain chars that
 * might not properly be utf8.
 *
 * @param $value
 * @return mixed|string encoded JSON string
 */
function safe_json_encode($value)
{
	$encoded = json_encode($value);
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			return $encoded;
		case JSON_ERROR_UTF8:
			$clean = utf8ize($value);
			return safe_json_encode($clean);
		default:
			return $encoded;
	}
}
function utf8ize($mixed)
{
	if (is_array($mixed)) {
		foreach ($mixed as $key => $value) {
			$mixed[$key] = utf8ize($value);
		}
	} else if (is_string($mixed)) {
		return utf8_encode($mixed);
	}
	return $mixed;
}
