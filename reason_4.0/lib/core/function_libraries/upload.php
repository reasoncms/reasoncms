<?php

/**
 * Uploaded file management. Facilities to set up a background/asynchronous
 * upload session, and to retrieve the files that have been uploaded either
 * through an asynchronous session or through PHP's built-in POST upload
 * mechanism.
 *
 * For admin modules that handle uploads, {@link get_uploaded_files()} should
 * be used to access the uploaded files instead of the PHP <code>$_FILES</code>
 * facility. The <code>get_uploaded_files</code> function returns objects
 * representing both files that were uploaded in the current POST and that were
 * uploaded in an asynchronous upload session, allowing you to handle both
 * cases transparently.
 *
 * @package reason
 * @subpackage function_libraries
 * @since Reason 4.0 beta 8
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

require_once CARL_UTIL_INC.'/basic/filesystem.php';
reason_require_once('function_libraries/reason_session.php');
reason_require_once('classes/uploaded_file.php');

/**
 * Creates a new asynchronous upload session. Files uploaded asynchronously
 * will be recorded in this session, and can be accessed using
 * {@link get_uploaded_files()} after the user (e.g.) submits the form on which
 * the asynchronous uploader appeared. Upload sessions are distinguished from
 * one another by ID's that are unique within the Reason session.
 * 
 * A {@link Session_PHP Reason PHP session} must be started (or must be able to
 * be started) for this function to work; if none can be started, a fatal error
 * will be triggered.
 * 
 * Constraints can be placed on what files will be accepted by the upload
 * handler by using the {@link reason_add_async_upload_constraints} function.
 * 
 * @param array $authenticator specifies a callback that will be used to check
 *        if the user is permitted to upload a file. The array should be
 *        formatted as follows:
 *            array([filename,] callback, [argument, [argument, [...]]])
 *        If a filename is given and non-null, the PHP file with that name
 *        will be included before executing its callback; if it is a relative
 *        path, it will be included using {@link reason_include_once}; if it
 *        is an absolute path, a standard PHP {@link require_once} will be
 *        used. The callback will be called with the username of the logged-in
 *        user as the first argument (NULL if the user is not logged in),
 *        followed by any additional arguments passed in the array. If the user
 *        can upload, the function should return true.
 *        
 * @return string a string that uniquely identifies the created upload session
 *         within the user's active Reason session
 */
function reason_create_async_upload_session($authenticator=null)
{
	$session =& get_reason_session();
	
	if (!is_callable(array(&$session, 'has_started'))) {
		trigger_error("cannot create asynchronous upload session: Reason's ".
			"current session implementation (" + REASON_SESSION_CLASS + ") ".
			"is not compatible with create_async_upload_session(); try using ".
			"Reason's default Session_PHP implementation instead",
			FATAL);
		return;
	}
	
	if (!$session->has_started()) {
		if (!$session->start()) {
			$reason = $session->get_error_msg();
			trigger_error("cannot create asynchronous upload session: cannot ".
				"start Reason session: $reason", FATAL);
			return;
		}
	}
	
	if ($authenticator) {
		$authenticator = _parse_async_upload_authenticator($authenticator);
	}
	
	$id = _create_async_upload_session_id($session);

	$session->set(_async_upload_session_key($id), array(
		'authenticator' => $authenticator,
		'constraints' => array(),
		'files' => array()
	));
	return $id;
}

/**
 * Checks to see if a background upload session with the given ID exists in
 * the user's Reason session.
 * 
 * @param string $session_id a Reason asynchronous upload session ID, as
 *        returned from {@link reason_create_async_upload_session}
 * @return boolean true if an upload session with that ID exists; false if
 *         otherwise
 */
function reason_async_upload_session_exists($session_id)
{
	$reason_session =& get_reason_session();
	$key = _async_upload_session_key($session_id);
	$async_session = $reason_session->get($key);
	return (!empty($async_session) && is_array($async_session) &&
		isset($async_session['files']));
}

/**
 * Place constraints on the files that will be accepted as background uploads.
 * 
 * If no upload session exists with that ID, or any of the constraints are
 * invalid, a fatal error is triggered.
 * 
 * @param string $session_id a Reason asynchronous upload session ID, as
 *        returned from {@link reason_create_async_upload_session}
 * @param string $field the name of the file upload field to which these
 *        constraints will apply
 * @param array $options constraints that will control which files are added to
 *                       the session and which are rejected immediately:
 *                       <dl>
 *                         <dt>mime_type</dt>
 *                         <dd>a MIME type pattern (like "image/*") or an array
 *                             of such patterns; if specified, uploaded files
 *                             will be restricted to the given type(s)</dd>
 *                         <dt>max_size</dt>
 *                         <dd>either an integer number of bytes or a php.ini
 *                             size value (like "2M") giving the maximum size
 *                             of uploaded file to accept. note that PHP's
 *                             <code>upload_max_filesize</code> INI setting
 *                             acts as a hard limit on this value
 *                         </dd>
 *                         <dt>max_dimensions</dt>
 *                         <dd>a two-element array giving the maximum width and
 *                             height of an uploaded image, in pixels
 *                         </dd>
 *                         <dt>validator</dt>
 *                         <dd>specifies a function to use to validate the
 *                             uploaded file; a two-element array, where the
 *                             first element is the name of a file to
 *                             {@link reason_include_once()} before executing
 *                             the callback given in the second element. The
 *                             callback function should accept a
 *                             {@link UploadedFile} object representing the
 *                             uploaded file, and return <code>true</code> if
 *                             the file was acceptable, and either an error
 *                             message string or <code>false</code> if it is
 *                             not acceptable
 *                         </dd>
 *						   <dt>convert_to_image</dt>
 *					       <dd>
 *								jonesn update --
 *								specifies whether or not a file uploaded via an 
 *								image uploader should be converted to web-friendly
 *								image type (png)
 *						   </dd>
 *                       </dl>
 * @return void
 */
function reason_add_async_upload_constraints($session_id, $field, $options)
{
	$reason_session =& get_reason_session();
	$key = _async_upload_session_key($session_id);
	$async_session = $reason_session->get($key);
	if (!$async_session) {
		trigger_fatal_error("cannot add asynchronous upload constraints to ".
			var_export($field, true).": ".var_export($session_id, true).
			" is not a valid asynchronous upload session ID");
	}
	
	$constraints = array();
	if (!empty($options['mime_type'])) {
		$constraints['mime_types'] = (array) $options['mime_type'];
	}
	if (!empty($options['extension'])) {
		$constraints['extensions'] = (array) $options['extension'];
	}	
	if (!empty($options['max_size'])) {
		$size = $options['max_size'];
		$size = (is_numeric($size))
			? (int) $size
			: _parse_size_string($size);
		if ($size !== 0 && !$size) {
			trigger_fatal_error("cannot add asynchronous upload constraints: ".
				"invalid maximum size ".var_export($options['max_size']));
		}
		$constraints['max_size'] = $size;
	}
	if (!empty($options['max_dimensions'])) {
		$dims = $options['max_dimensions'];
		if (!is_array($dims) || count($dims) != 2) {
			trigger_fatal_error("cannot add asynchronous upload constraints: ".
				"invalid dimensions; they should be given as a two-element ".
				"array as described in the API documentation; instead got: ".
				var_export($dims, true));
		}
		list($max_width, $max_height) = $dims;
		if (!is_int($max_width) || !is_int($max_height)) {
			trigger_fatal_error("cannot add asynchronous upload constraints: ".
				"both the maximum width and height must be integers");
		}
		$constraints['max_dimensions'] = $dims;
	}
	if (!empty($options['validator'])) {
		$validator = $options['validator'];
		if (!is_array($validator) || count($validator) != 2) {
			trigger_fatal_error("cannot add asynchronous upload constraints: ".
				"invalid validator; it should be a two-element array as ".
				"described in the API documentation; instead got: ".
				var_export($validator, true));
		}
		
		list($file, $callback) = $validator;
		if (!reason_file_exists($file)) {
			trigger_fatal_error("cannot add asynchronous upload constraints: ".
				"validator file ".var_export($file, true)." does not exist ".
				"in Reason's core or local lib directories");
		}
		$constraints['validator'] = $validator;
	}
	if (!empty($options['convert_to_image'])) {
		$constraints['convert_to_image'] = $options['convert_to_image'];
	}
	
	$existing = (array) @$async_session['constraints'][$field];
	$async_session['constraints'][$field] = array_merge($existing,
		$constraints);
	$reason_session->set($key, $async_session);
}

/**
 * Gets the URI of the given asynchronous upload script.
 * @param string $script the name of the script
 * @return the URI of that script
 */
function reason_get_async_upload_script_uri($script)
{
	if (!preg_match('/\.php\d?$/', $script))
		$script .= ".php";
	return REASON_HTTP_BASE_PATH."scripts/upload/$script";
}

/**
 * Given an asynchronous upload session ID, returns the Reason session key
 * that would store the information for that upload session.
 * @param string $id
 * @return string
 * @access private
 */
function _async_upload_session_key($id)
{
	return "_upload_session_$id";
}

/**
 * Creates a unique asynchronous upload session ID within the given Reason
 * session.
 * @param Session $session
 * @return string
 * @access private
 */
function _create_async_upload_session_id(&$session)
{
	do {
		$id = '';
		for ($i = 0; $i < 6; $i++)
			$id .= dechex(rand(0, 15));
	} while ($session->get(_async_upload_session_key($id)));
	
	return $id;
}

/**
 * @access private
 */
function _get_async_upload_session($id)
{
	$reason_session =& get_reason_session();
	$key = _async_upload_session_key($id);
	$async_session = $reason_session->get($key);
	return ($async_session) ? $async_session : null;
}

/**
 * Gets information about any files that have been uploaded in POST body of the
 * current request or in the asynchronous upload session identified by the
 * given ID.
 * 
 * Only files that were completely transferred and accepted will be returned.
 * 
 * If no asynchronous upload session ID is given, only files in the POST body
 * of the current request will be returned. If an ID is given, but no upload
 * session actually exists with the given ID, a notice is triggered.
 *
 * @param string $async_session_id the ID for the asynchronous upload session
 *        whose files are desired
 * @return array one {@link UploadedFile} object for each uploaded file in
 *         the POST body of the current request or the asynchronous upload
 *         session (if there is one)
 */
function reason_get_uploaded_files($async_session_id=null)
{
	$files = array();
	
	if ($async_session_id) {
		$async_session = _get_async_upload_session($async_session_id);
		if ($async_session) {
			foreach ($async_session['files'] as $name => $records) {
				foreach ($records as $record) {
					$file = _uploaded_file_from_async($async_file);
					if ($file)
						$files[] = $file;
				}
			}
		} else {
			trigger_warning('tried to get the files from asynchronous upload '.
				'session '.var_export($async_session_id, true).', but no '.
				'such session exists');
		}
	}
	
	foreach ($_FILES as $post_file) {
		$file = _uploaded_file_from_php($post_file);
		if ($file)
			$files[] = $file;
	}
	
	return $files;
}

/**
 * Gets information about a specific file, whether it was uploaded in the POST
 * body of the current request or in the asynchronous upload session identified
 * by the given ID.
 * 
 * If no such file was received, if an empty file was received, or if there was
 * an error in receiving or storing the file or if it was rejected by PHP,
 * <code>null</code> will be returned.
 * 
 * If an asynchronous upload session ID is given, but no session with that ID
 * actually exists, a notice is triggered.
 * 
 * @param string $name the form field name under which the file was submitted
 * @param string $async_session_id the ID for the asynchronous upload session
 * @param boolean $clear if true, and the uploaded file is found in the
 *        asynchronous session, the file's record will be removed from the
 *        session
 * @return UploadedFile information about the uploaded file, or
 *         <code>null</code> if no such file was uploaded or if there was an
 *         error in uploading it
 */
function reason_get_uploaded_file($name, $async_session_id=null, $clear=false)
{
	if ($async_session_id) {
		$async_session = _get_async_upload_session($async_session_id);
		if ($async_session) {
			if (isset($async_session['files'][$name])) {
				$records = $async_session['files'][$name];
				if (is_array($records) && count($records) > 0) {
					$keys = array_keys($records);
					$key = $keys[count($keys) - 1];
					$async_file = $records[$key];
					$file = _uploaded_file_from_async($async_file);
					if (!$file || $clear) {
						unset($async_session['files'][$name][$key]);
						$session =& get_reason_session();
						$id = $async_session_id;
						$session->set(_async_upload_session_key($id),
							$async_session);
					}
					if ($file)
						return $file;
				}
			}
		} else {
			trigger_warning("tried to get the file $name from asynchronous ".
				'upload session '.var_export($async_session_id, true).', but '.
				'no such session exists');
		}
	}
	
	return (isset($_FILES[$name]))
		? _uploaded_file_from_php($_FILES[$name])
		: null;
}

/**
 * Creates an UploadedFile instance from a PHP <code>$_FILES</code> entry.
 * @param array $post_file
 * @return UploadedFile
 */
function _uploaded_file_from_php($post_file)
{
	if ($post_file['size'] > 0 && $post_file['error'] == UPLOAD_ERR_OK) {
		return new UploadedFile(basename($post_file['name']),
			$post_file['tmp_name'], $post_file['size']);
	} else {
		return null;
	}
}

/**
 * Creates an UploadedFile instance from an asynchronous upload session entry.
 * @param array $async_file
 * @return UploadedFile
 */
function _uploaded_file_from_async($async_file)
{
	return (is_readable($async_file['path']))
		? new UploadedFile($async_file['name'], $async_file['path'], null,
			$async_file['original_path'])
		: null;
}

/**
 * Parses a size string in the format used by php.ini (e.g. "3M", "1.4 KB").
 * @access private
 * @see get_php_size_setting_as_bytes()
 */
function _parse_size_string($size)
{
	$size = strtolower($size);
	if (!preg_match('/(\d+(?:\.\d+)?)\s*([kmg])b?$/', $size, $m)) {
		trigger_fatal_error('invalid size string '.var_export($size, true).
			"; valid strings look like '2M', '4K', etc.", 2);
		return null;
	}
	
	$size = (float) $m[1];
	$suffix = $m[2];
	switch ($suffix) { // note the (intentional) lack of break statements
		case 'g':
			$size *= 1024;
		case 'm':
			$size *= 1024;
		case 'k':
			$size *= 1024;
	}
	return (int) $size;
}

/** @access private */
function _parse_async_upload_authenticator($auth)
{
	if (!is_array($auth) || empty($auth)) {
		trigger_fatal_error("upload authenticator must be specified as an ".
			"array as per the reason_create_async_upload_session API docs; ".
			"instead got ".var_export($auth, true), 2);
	}
	
	$callback = array_shift($auth);
	if ($inc_type = _detect_filename($callback)) {
		$filename = $callback;
		$callback = array_shift($auth);
	} else {
		$filename = null;
	}
	
	if ($filename) {
		if ($inc_type == "absolute" && !file_exists($filename)) {
			trigger_fatal_error("upload authenticator file ".
				var_export($filename, true)." does not exist", 2);
		} else if ($inc_type == "relative") {
			if (!reason_file_exists($filename)) {
				trigger_fatal_error("upload authenticator file ".
					var_export($filename, true)." does not exist in either ".
					"the local or the core Reason lib directory", 2);
			}
			$filename = reason_resolve_path($filename);
		}
	}
	
	return array(
		"file" => $filename,
		"callback" => $callback,
		"arguments" => $auth
	);
}

/** @access private */
function _detect_filename($string)
{
	static $abs_pattern = '/^(\/|[A-Za-z]:\\|\\\\)/';
	static $file_pattern = '/\.(php\d?|inc)$/';
	
	if ($string === null || preg_match($abs_pattern, $string))
		return "absolute";
	else if (preg_match($file_pattern, $string))
		return "relative";
	else
		return null;
}
