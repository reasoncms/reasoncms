<?php

/**
 * JSON encoding.
 * 
 * This library ensures that a minimal version of
 * {@link http://www.php.net/json_encode json_encode()} is available, even when
 * running under PHP versions that do not include it.
 * 
 * @package carl_util
 * @subpackage basic
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

if (!function_exists('json_encode')) {
	/**
	 * Encodes the given PHP value as JSON.
	 * 
	 * Associative arrays will be encoded as JSON objects; non-associative
	 * arrays will be encoded as JSON arrays. A non-associative array is one
	 * that does not have any non-numeric keys.
	 * 
	 * @param mixed $data PHP value to encode
	 * @return string JSON encoding of $data
	 * @see http://www.php.net/json_encode PHP's native json_encode()
	 */
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

	/**
	 * Returns the JSON encoding of the given non-associative array.
	 * @access private
	 * @param array $data
	 * @return string
	 */
	function _json_encode_array($data)
	{
		return "[".implode(', ', array_map('json_encode', $data))."]";
	}

	/**
	 * Returns the JSON encoding of the given object or associative array.
	 * @access private
	 * @param array|object $data
	 * @return string
	 */
	function _json_encode_object($object)
	{
		$pairs = array();
		foreach ((array) $object as $key => $value) {
			$pairs[] = json_encode((string) $key).': '.json_encode($value);
		}
		return "{".implode(', ', $pairs)."}";
	}
}
