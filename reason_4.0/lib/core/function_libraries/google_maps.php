<?php
include_once(CARL_UTIL_INC . 'basic/url_funcs.php');
include_once(SETTINGS_INC . 'map_settings.php');

/**
 * Supports the following Google APIs:
 *     - Maps JavaScript API  name = maps_javascript_api
 *     - Maps Static API      name = maps_static_api
 *     - Geocoding API        name = geocoding_api
 * @return string
 */
function get_google_maps_api_key($name)
{
	$key = '';
	switch ($name) {
		case 'maps_javascript_api':
		case 'maps_static_api':
			$key = $GLOBALS['reason_map_api_keys']['google'];
			break;
		case 'geocoding_api':
			// It's a separate key because it uses different and incompatible key restrictions
			// than the Static & JS APIs.
			$key = $GLOBALS['reason_map_api_keys']['google_geocoding_api'];
	}
	if (!$key) {
		trigger_error("Google Maps API key unavailable API=" . $name);
	}
	return $key;
}

/**
 * Create a Maps JavaScript API URL
 *
 * Flattens multi-valued fields:
 *   If the query params array has arrays or multivalued fields,
 *   the field names will be flattened in the query string.
 *   The php array notation of "[]" or "[N]" is stripped.
 *
 *   For example:
 *       - files[0]=1&files[1]=2"  becomes  "files=1&files=2
 *         or
 *       - a $query_params array:  array('multi_value' => array('one', 'two'), 'single_value' => 'ok')
 *         becomes a query string: multi_value=one&multi_value=two&single_value=ok
 *
 * @param array $query_params
 * @return string
 */
function create_google_maps_js_url($query_params = [])
{
	$defaults = ['v' => 3, 'key' => get_google_maps_api_key('maps_javascript_api')];

	$url = 'https://maps.googleapis.com/maps/api/js?';

	$url .= http_build_query($defaults + $query_params, null, '&', PHP_QUERY_RFC3986);

	// Flatten multi-valued fields
	$url = preg_replace('/%5B[0-9]+%5D=/simU', '=', $url);

	return $url;
}

/**
 * Create a signed URL for a Maps Static API
 * Flattens multi-valued fields (see create_google_maps_js_url())
 * @param $query_params array
 * @return string a signed Maps Static API URL
 */
function create_google_static_map_url($query_params = [])
{
	$defaults = ['key' => get_google_maps_api_key('maps_static_api')];

	$url = 'https://maps.googleapis.com/maps/api/staticmap?';

	$url .= http_build_query($defaults + $query_params, null, '&', PHP_QUERY_RFC3986);

	// Flatten multi-valued fields
	$url = preg_replace('/%5B[0-9]+%5D=/simU', '=', $url);

	$signing_key = $GLOBALS['reason_map_api_keys']['google_static_url_signing_secret'];

	return sign_google_url($url, $signing_key);
}

/**
 * Create a Geocoder API URL
 * Flattens multi-valued fields (see create_google_maps_js_url())
 * @param array $query_params
 * @return string
 */
function create_google_geocode_url($query_params = array())
{
	$defaults = ['key' => get_google_maps_api_key('geocoding_api')];

	$url = 'https://maps.googleapis.com/maps/api/geocode/json?';

	$url .= http_build_query($defaults + $query_params, null, '&', PHP_QUERY_RFC3986);

	// Flatten multi-valued fields
	$url = preg_replace('/%5B[0-9]+%5D=/simU', '=', $url);

	return $url;
}

/**
 * Sign a request URL with a URL signing secret.
 * @param string $input_url The URL to sign
 * @param string $signing_key Your URL signing secret
 * @return string The signed request URL
 */
function sign_google_url($input_url, $signing_key)
{
	return sign_url($input_url, $signing_key);
}
