<?php

/**
 * Handles removing files that were uploaded asynchronously.
 *
 * @package reason
 * @subpackage scripts
 * @since Reason 4.0 beta 8
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

require 'common.inc.php';

if (empty($_POST['name']) || !isset($_POST['index'])) {
	final_response(400, "Invalid file removal request.");
}

$name = $_POST['name'];
if (empty($session['files'][$name])) {
	final_response(404, "No files have been uploaded with that name.");
}

$index = $_POST['index'];
if (empty($session['files'][$name][$index])) {
	final_response(404, "No file has been uploaded with that index.");
}
$info = $session['files'][$name][$index];

if ($info['path'])
	@unlink($info['path']);
if ($info['original_path'])
	@unlink($info['original_path']);

unset($session['files'][$name][$index]);
$reason_session->set(_async_upload_session_key($upload_sid), $session);

final_response(200, "File removed.");
