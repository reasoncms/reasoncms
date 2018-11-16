<?php
/**
 * Reason Map API Keys array allows any number of map keys to be stored, keyed by API provider
 */
$GLOBALS['reason_map_api_keys'] = array();

switch ($_SERVER['SERVER_NAME']) {
	case 'example.com':
		$GLOBALS['reason_map_api_keys']['google'] = "AIza1234567810example";
		$GLOBALS['reason_map_api_keys']['google_static_url_signing_secret'] = 'AIza1234567810examplesecret';
		$GLOBALS['reason_map_api_keys']['google_geocoding_api'] = 'AIza1234567810example';
		break;
}
