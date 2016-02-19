<?php

/**
 * Common support code for the background upload scripts.
 *
 * @package reason
 * @subpackage scripts
 * @since Reason 4.0 beta 8
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

require_once 'reason_header.php';

// Prevent the error handler from dumping HTML error messages.
error_handler_config('script_mode', true);

reason_require_once('function_libraries/util.php');
reason_require_once('classes/entity_selector.php');
connectDB(REASON_DB);
reason_require_once('function_libraries/user_functions.php');
reason_require_once('function_libraries/upload.php');
reason_require_once('function_libraries/reason_session.php');

function response_code($code) {
	http_response_code($code);
}

function responseWrapper($code, $msg) {
	final_response($code, Array("message" => $msg));
}

function final_response($code, $message) {
	
	if (is_array($message) || is_object($message)) {
		header('Content-Type: application/json');
		$message = json_encode($message);
	} else {
		header('Content-Type: text/plain');
	}
	
	response_code($code);
	echo trim($message)."\n";
	exit;
}

if (HTTPS_AVAILABLE && !on_secure_page()) {
	final_response(403, "This script must be accessed over a secure ".
		"connection.");
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	final_response(405, "This script must be accessed via a POST request.");
}

if (defined('MAINTENANCE_MODE_ON') && MAINTENANCE_MODE_ON) {
	final_response(503, "This site is currently undergoing maintenance. ".
		"Uploads cannot be accepted at this time.");
}

// When Flash is running as an NPAPI plugin under Windows, it does not send
// the correct cookies with HTTP requests, but instead sends whatever cookies
// are associated with its IE plugin version. SWFUpload instances are made to
// pass the session ID explicitly to work around this.
$reason_session =& get_reason_session();
if (!empty($_REQUEST['reason_sid'])) {
	$reason_session->start($_REQUEST['reason_sid']);
} else {
	$reason_session->start();
}

$upload_sid = @$_REQUEST['upload_sid'];
$session = _get_async_upload_session($upload_sid);
if (!$session) {
	if (empty($_REQUEST['upload_sid'])) {
		final_response(400, "Upload session (upload_sid) not provided.");
	} else {
		final_response(400, "No upload session with ID ".
			$upload_sid);
	}
}

// Permission check.
if (!can_upload($session)) {
	final_response(403, "Permission denied.");
}

function can_upload($session) {
	if ($session['authenticator']) {
		$auth = $session['authenticator'];
		$reason_session =& get_reason_session();

		$username = $reason_session->get("username");
		if (isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id']))
		{
			$username = $reason_session->get('username');
			$param_cleanup_rules = array('user_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')));
			$cleanRequest = array_merge($_REQUEST, carl_clean_vars($_REQUEST, $param_cleanup_rules));
			$nametag = $cleanRequest['user_id'];
			$id = get_user_id($username);
			if (reason_user_has_privs($id, 'pose_as_other_user'))
			{
				$user = new Entity($nametag);
				$username = $user->get_value("name");
			}
		}
		if ($auth['file'])
			require_once $auth['file'];
		
		$args = array_merge(array($username), $auth['arguments']);
		if (!call_user_func_array($auth['callback'], $args))
			return false;
	}
	
	return true;
}

