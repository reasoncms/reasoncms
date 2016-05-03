<?php
/**
 * A sophisticated error handling utility.
 *
 * If you want to use the error notification system coded up here, all you
 * really have to do is use the trigger_error() or user_error() functions.
 * They are both the same function, so you can use whichever you please.
 * The basic usage is trigger_error( error_message, error_level ), so, for
 * example, if I want to trigger an emergency message for some action, I
 * would use trigger_error( "Something really bad happened", EMERGENCY ).
 * The constants defined below can be used in place of EMERGENCY for
 * different levels of notification.
 * 
 * Including this file will check the value of the {@link MAINTENANCE_MODE_ON}
 * constant. If it is defined and equal to <code>true</code>, and the user is
 * not a developer, the user will be redirected to the maintenance message,
 * whose location should be given in {@link MAINTENTANCE_MODE_URL}.
 *
 * @package carl_util
 * @subpackage error_handler
 */

// Ensure that PHP errors of all levels go through the handler; including
// E_STRICT errors under PHP 5. (In PHP 6, E_ALL includes E_STRICT.)
error_reporting(defined('E_STRICT') ? E_ALL | E_STRICT : E_ALL);

/**
 * Provides access to the error handler configuration items.
 * 
 * If called with one argument, the value of the configuration item named
 * by that argument will be returned; if called with two, the configuration
 * item will be set to the second argument, and any previous value will be
 * returned.
 * 
 * If <code>$name</code> is null, returns a copy of the storage array for
 * the error handler configuration.
 * 
 * A number of legacy functions also access these settings; for example, see
 * {@link turn_carl_util_error_logging_on} or {@link carl_util_output_errors}.
 * 
 * @param string $name configuration item name
 * @param mixed $value desired value for the named configuration item
 * @return mixed the existing value
 */
function error_handler_config($name, $value=null)
{
	static $config = array();
	
	if ($name === null)
		return $config;
	
	$current_value = (isset($config[$name])) ? $config[$name] : null;
	
	if ($value !== null) {
		$config[$name] = $value;
	}
	return $current_value;
}

// Set the address that error email messages will come from.
error_handler_config('from_email', 'errors@'._get_error_host());

// Set defaults for error handler mode settings.
error_handler_config('log_errors', true);
error_handler_config('display_errors', true);
error_handler_config('display_context', true);
error_handler_config('script_mode', false);
error_handler_config('send_emails', false);
error_handler_config('send_pages', false);

if (!defined('SETTINGS_INC') || !defined('CARL_UTIL_INC'))
	require_once 'paths.php';

/**
 * Include the error handler's settings file.
 * Settings defined there can override the defaults that were set above.
 */
require_once SETTINGS_INC.'error_handler_settings.php';

include_once CARL_UTIL_INC.'basic/misc.php';
include_once CARL_UTIL_INC.'dev/pray.php';

/**
 * Returns the value of the "log_errors" error handler setting.
 * @return boolean true if errors are being logged, false if otherwise
 * @see error_handler_config
 * @see turn_carl_util_error_logging_on
 */
function carl_util_log_errors()
{
    return error_handler_config("log_errors");
}

/**
 * Enables error logging.
 * @return void
 * @see error_handler_config the "log_errors" setting
 * @see turn_carl_util_error_logging_off
 */
function turn_carl_util_error_logging_on()
{
    error_handler_config("log_errors", true);
}

/**
 * Disables error logging.
 * @return void
 * @see error_handler_config the "log_errors" setting
 * @see turn_carl_util_error_logging_on
 */
function turn_carl_util_error_logging_off()
{
    error_handler_config("log_errors", false);
}

/**
 * Returns the value of the "display_errors" error handler setting.
 * @return boolean true if error output is on, false if otherwise
 * @see error_handler_config
 * @see turn_carl_util_error_output_on
 */
function carl_util_output_errors()
{
    return error_handler_config("log_errors");
}

/**
 * Enables error output.
 * @return void
 * @see error_handler_config the "display_errors" setting
 * @see turn_carl_util_error_output_off
 */
function turn_carl_util_error_output_on()
{
    error_handler_config("display_errors", true);
}

/**
 * Disables error output.
 * @return void
 * @see error_handler_config the "display_errors" setting
 * @see turn_carl_util_error_output_on
 */
function turn_carl_util_error_output_off()
{
    error_handler_config("display_errors", false);
}

/**
 * Enables context output.
 * @return void
 * @see error_handler_config the "display_context" setting
 * @see turn_carl_util_error_context_off
 */
function turn_carl_util_error_context_on()
{
    error_handler_config("display_context", true);
}

/**
 * Disables context output.
 * @return void
 * @see error_handler_config the "display_context" setting
 * @see turn_carl_util_error_context_on
 */
function turn_carl_util_error_context_off()
{
    error_handler_config("display_context", false);
}

/**
 * Should the current request be treated as a developer for the purposes of
 * error display?
 *
 * @return boolean true if the remote user is a known developer, false if not
 */
function is_developer()
{
	if(isset($_SESSION['carl_util_error_handler_override']))
		return $_SESSION['carl_util_error_handler_override'];
	
	if(!empty($_SERVER['REMOTE_ADDR']))
		return is_developer_ip_address(get_user_ip_address());
	
	return false;
}

/**
 * Is a given IP address registered as belonging to a developer?
 * @return boolean
 * @uses $GLOBALS['_DEVELOPER_INFO']
 */
function is_developer_ip_address($ip)
{
	static $ip_list = null;
	
	if ($ip_list === null) {
		// Create a fast lookup table of developer IP's.
		$ip_list = array();
		foreach ($GLOBALS['_DEVELOPER_INFO'] AS $dev) {
			foreach ((array) $dev['ip'] as $address) {
				$ip_list[$address] = true;
			}
		}
	}

	return (!empty($ip))
		? isset($ip_list[$ip])
		: true;
}

/**
 * Set a session variable that overrides the ip-based developer logic
 * for inline error reporting
 *
 * @param boolean $is_developer true for dev error reporting, false for non-dev error hiding
 * @return void
 */
function override_developer_status($is_developer)
{
	if($is_developer)
		$_SESSION['carl_util_error_handler_override'] = true;
	else
		$_SESSION['carl_util_error_handler_override'] = false;
}

/**
 * Clear any overrides that might be in place for developer status
 * @return void
 */
function clear_developer_status_override()
{
	if(isset($_SESSION['carl_util_error_handler_override']))
		unset($_SESSION['carl_util_error_handler_override']);
}

/**
 * Is there an override currently in place?
 * @return true if in place & set to true, false if in place & set to false, or NULL if not in place
 */
function get_developer_status_override()
{
	if(isset($_SESSION['carl_util_error_handler_override']))
		return $_SESSION['carl_util_error_handler_override'];
	else
		return NULL;
}

/**
 * Error level for "really bad" errors.
 * If an EMERGENCY-level error is triggered, phones should probably ring.
 */
define('EMERGENCY', E_USER_ERROR);
/** Alias for {@link EMERGENCY}. */
define('FATAL', E_USER_ERROR);

/**
 * Error level for "pretty bad" errors.
 * If a HIGH-level error is triggered, emails should probably be sent.
 *
 * Note that, while <code>HIGH</code> is defined in terms of the PHP
 * <code>E_USER_WARNING</code> constant, the error handler defined here
 * ({@link carl_util_handle_error}) will halt execution when an error of
 * this level is triggered. To trigger an error with behavior like that of
 * <code>E_USER_WARNING</code>, see {@link MEDIUM}.
 */
define('HIGH', E_USER_WARNING);
/** Alias for {@link HIGH}. */
define('ERROR', E_USER_WARNING);

/**
 * Error level for conditions that should simply be noted.
 * The {@link carl_util_handle_error} error handler will simply log these
 * errors, and will not interrupt execution.
 */
define('MEDIUM', E_USER_NOTICE);
/** Alias for {@link MEDIUM}. */
define('WARNING', E_USER_NOTICE);

/**
 * Returns a name for the given error level.
 * @param int $level PHP error level
 * @return string description of that error level
 */
function error_level_name($level)
{
	static $names = null;
	
	if (!$names) {
		$names = array(
			FATAL => 'Fatal',
			ERROR => 'Error',
			WARNING => 'Warning',
			E_WARNING => 'Warning',
			E_USER_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_USER_NOTICE => 'Notice'
		);
		
		if (defined('E_RECOVERABLE_ERROR'))
			$names[E_RECOVERABLE_ERROR] = 'Recoverable Error';
		if (defined('E_STRICT'))
			$names[E_STRICT] = 'Compatibility Notice';
		if (defined('E_DEPRECATED'))
			$names[E_DEPRECATED] = 'Deprecation';
		if (defined('E_USER_DEPRECATED'))
			$names[E_USER_DEPRECATED] = 'Deprecation';
	}
	
	return (isset($names[$level]))
		? $names[$level]
		: "(level $level)";
}

/**
 * Checks if the given error level is terminal.
 * @param int $level PHP error level
 * @return boolean <code>true</code> if errors of this level should cause
 *         script execution to terminate; <code>false</code> if otherwise
 */
function level_is_terminal($level)
{
	return ($level == E_ERROR || $level == FATAL || $level == ERROR);
}

/** @access private */
function _get_error_host()
{
	if (!empty($_SERVER['HTTP_HOST']))
		return $_SERVER['HTTP_HOST'];
	else if (!empty($_SERVER['SERVER_NAME']))
		return $_SERVER['SERVER_NAME'];
	else
		return strtolower(trim(`hostname`));
}

$GLOBALS['_ERRORS_SO_FAR'] = array();
/**
 * Returns the list of errors that have occurred so far during this request.
 * @return array
 */
function carl_util_get_error_list()
{
	return $GLOBALS[ '_ERRORS_SO_FAR' ];
}

// Check if we're in maintenance mode.
if (defined('MAINTENANCE_MODE_ON') && MAINTENANCE_MODE_ON === true) {
	if (!is_developer()) {
		$url = MAINTENANCE_MODE_URL;
		if (!empty($GLOBALS['_maintenance_estimate']))
			$url .= "?estimate={$GLOBALS['_maintenance_estimate']}";
		header("Location: $url");
		exit;
	} else {
		echo '<h1 style="background: #DD0; color: #000; font-weight: '.
			'normal; text-transform: uppercase;">Maintenance mode is on!</h1>';
	}
}

/** @access private */
function _carl_util_send_error_status($status=500)
{
	if (!headers_sent()) {
		if(function_exists('http_response_code'))
			http_response_code($status);
		else
		{
			$proto = (!empty($_SERVER['SERVER_PROTOCOL']))
				? $_SERVER['SERVER_PROTOCOL']
				: 'HTTP/1.0';
			header("$proto $status");
		}
	}
}

/** @access private */
function _carl_util_store_error($level, $message, $file, $line)
{
	$GLOBALS['_ERRORS_SO_FAR'][] = array(
		'number' => $level,
		'string' => $message,
		'file' => $file,
		'line' => $line
	);
}

/** @access private */
function _clean_filename($filename, $prefix="&hellip;")
{
	$search_dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
	
	foreach ($search_dirs as $dir) {
		if ($dir == ".")
			continue;
		$dir = realpath($dir).DIRECTORY_SEPARATOR;
		$len = strlen($dir);
		if (substr($filename, 0, $len) == $dir) {
			$filename = substr($filename, $len);
			if ($filename[0] != DIRECTORY_SEPARATOR)
				$filename = DIRECTORY_SEPARATOR.$filename;
			return "$prefix$filename";
		}
	}
	
	return $filename;
}

/** @access private */
function _carl_util_display_error($level, $message, $file, $line, $context)
{
	$s_level = error_level_name($level);
	list($escaped_message, $include_loc) = _process_message($message);
	$file = _clean_filename($file);
	
	$err = '<div style="border: 2px solid #E00; background-color: #EEE; '.
		'color: black; padding: 0.5em; margin: 0.5em; font-size: 90%;">';
	$err .= "<strong>$s_level:</strong> $escaped_message";
	if ($include_loc) {
		if (preg_match('/[\.?!]$/', $message)) {
			// full sentence
			$err .= " (<code>$file:$line</code>)";
		} else {
			// fragment
			$err .= " at <code>$file:$line</code>";
		}
	}
	
	if (level_is_terminal($level) && error_handler_config('display_context') ) {
		$escaped_context = spray($context);
		$err .= '<br />';
		$err .= '<b style="font-size: 90%; margin-top: 0.3em;">Context:</b>'.
			"\n\n<pre>$escaped_context</pre>";
		$err .= "<br /><br /><strong>Script execution halted.</strong>";
	}
	$err .= "</div>\n";
	
	if (php_sapi_name() == 'cli') {
		$err = strip_tags(preg_replace('=<br */?>=i', "\n", $err));
	}
	
	echo $err;
}

/** @access private */
function _carl_util_escape_error_part($part)
{
	if (is_numeric($part))
		return $part;
	else
		return '"'.addslashes($part).'"';
}

/** @access private */
function _carl_util_log_error($level, $message, $file, $line, $context)
{
	$referrer = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
	$uri = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
	$ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
	$ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$error = array(
		'type' => strtoupper(error_level_name($level)),
		'time' => carl_date('r'),
		'msg' => $message,
		'line' => $line,
		'file' => $file,
		'uri' => $uri,
		'ip' => $ip,
		'ua' => $ua,
		'errno' => $level,
		'referrer' => $referrer
	);
	
	$error = implode(',', array_map('_carl_util_escape_error_part', $error));
	dlog($error, PHP_ERROR_LOG_FILE);
}

/** @access private */
function _get_developer_emails($field='email')
{
	$emails = array();
	foreach ($GLOBALS['_DEVELOPER_INFO'] as $name => $dev) {
		if (!empty($dev[$field]))
			$emails[] = "$name <$dev[$field]>";
	}
	return $emails;
}

/** @access private */
function _carl_util_send_page($level, $message, $file, $line, $context)
{
	$subject = SHORT_ORGANIZATION_NAME.' Web Error';
	$file = _clean_filename($file, "");
	$body = "$message ($file:$line)";
	$headers = "From: ".error_handler_config('from_email');
	
	$recipients = _get_developer_emails('pager');
	if (!empty($recipients)) {
		mail($recipients, $subject, $body, $headers);
	}
}

/** @access private */
function _safe_get_server_var($var_name, $default='')
{
	return (!empty($_SERVER[$var_name]))
		? $_SERVER[$var_name]
		: $default;
}

/** @access private */
function _carl_util_send_email($level, $message, $file, $line, $context)
{
	$recipients = _get_developer_emails();
	if (empty($recipients))
		return;
	
	$subject = SHORT_ORGANIZATION_NAME.' Web '.error_level_name($level);
	$headers = "From: ".error_handler_config('from_email');

	// set up the body of the message
	$body_arr = array(
		'--- Error/Script Info ---',
		'Error: '.strip_tags($message),
		'File: '.$file,
		'Line: '.$line,
	);

	if (empty($_SERVER['_'])) {
		$body_arr[] = 'URL: '._safe_get_server_var('REQUEST_URI');
		$body_arr[] = 'PHP Self: '._safe_get_server_var('PHP_SELF');
	} else {
		$body_arr[] = 'Script being run from the command line.';
	}

	$body_arr = array_merge($body_arr, array(
		'--- User Info ---',
		'Remote User: '._safe_get_server_var('REMOTE_USER'),
		'PHP Auth User: '._safe_get_server_var('PHP_AUTH_USER'),
		'Remote IP: '._safe_get_server_var('REMOTE_ADDR'),
		'User Agent: '._safe_get_server_var('HTTP_USER_AGENT'),
	));
	
	$body = join("\n", $body_arr);
	mail($recipients, $subject, $body, $headers);
}

/** @access private */
function _process_message($message) {
	if (false === strpos($message, "(called"))
		return array(htmlspecialchars($message, ENT_QUOTES, "UTF-8"), true);
	$message = preg_replace('/called in ((?:\w+::)?\w+)/',
		'called in <code>$1</code>', $message);
	$message = preg_replace('/ at (.*?:\d+)\)$/', ' at <code>$1</code>)',
		$message);
	return array($message, false);
}

/**
 * The callback function that handles PHP errors. For more information on what
 * this error handler does and when it gets called, see
 * {@link error_handler.php the documentation for the error handler file}.
 */
function carl_util_handle_error($level, $message, $file, $line, $context)
{
	// Obey the current PHP error_reporting value. One notable effect of this
	// check is that it causes us to obey the @ error-suppression operator.
	if (($level & error_reporting()) == 0) {
		return true;
	}
	
	$store_error = $log_error = error_handler_config('log_errors');
	$display_error = (is_developer() &&
		error_handler_config('display_errors') &&
		!error_handler_config('script_mode') &&
		!isset($_REQUEST['nodebug']));
	$send_email = error_handler_config('send_emails');
	$send_page = error_handler_config('send_pages');
	
	if ($store_error)
		_carl_util_store_error($level, $message, $file, $line, $context);
	if ($display_error)
		_carl_util_display_error($level, $message, $file, $line, $context);
	if ($log_error)
		_carl_util_log_error($level, $message, $file, $line, $context);
	if ($send_email)
		_carl_util_send_email($level, $message, $file, $line, $context);
	if ($send_page)
		_carl_util_send_page($level, $message, $file, $line, $context);
	
	if (level_is_terminal($level)) {
		if (!is_developer() && !error_handler_config('script_mode') && PHP_SAPI != "cli" ) {
			header('Location: '.OHSHI_SCRIPT);
		} else {
			_carl_util_send_error_status();
		}
		exit;
	}
	
	return true;
}

/** @access private */
function _carl_util_trigger_error($message, $level, $stack_offset)
{
	static $inc_pattern = '/^(?:include|require)(?:_once)?$/';
	$stack_offset++; // skip over _trigger_error itself
	$stack = debug_backtrace();
	$stack_offset_max = max(array_keys($stack));
	
	// make sure our offset exists ... bring it down if not
	$stack_offset = ($stack_offset > max(array_keys($stack))) ? max(array_keys($stack)) : $stack_offset;
	
	$location = sprintf("%s:%d",
		_clean_filename($stack[$stack_offset]["file"]),
		$stack[$stack_offset]["line"]);
	
	$caller = null;
	if (!empty($stack[$stack_offset + 1])) {
		$next = $stack[$stack_offset + 1];
		if (!preg_match($inc_pattern, $next["function"])) {
			$caller = $next["function"];
			if (!empty($next["class"]))
				$caller = "{$next['class']}::$caller";
		}
	}
	
	$addendum = ($caller)
		? "called in $caller at $location"
		: "called at $location";
	$message = htmlspecialchars($message, ENT_QUOTES, "UTF-8");
	trigger_error("$message ($addendum)", $level);
}

/**
 * Triggers a {@link FATAL} error.
 * @see trigger_error()
 * @param string $message the message to accompany the triggered error
 * @param int $stack_offset the relative stack position of the code that is the
 *        source of the error; the default of 1 assumes it is the function that
 *        called the function that called trigger_fatal_error()
 * @return void
 */
function trigger_fatal_error($message, $stack_offset=1)
{
	_carl_util_trigger_error($message, FATAL, $stack_offset);
}

/**
 * Triggers a {@link HIGH}-level error.
 * @see trigger_error()
 * @param string $message the message to accompany the triggered error
 * @param int $stack_offset the relative stack position of the code that is the
 *        source of the error; the default of 1 assumes it is the function that
 *        called the function that called trigger_high_error()
 * @return void
 */
function trigger_high_error($message, $stack_offset=1)
{
	_carl_util_trigger_error($message, HIGH, $stack_offset);
}

/**
 * Triggers a {@link WARNING}.
 * @see trigger_error()
 * @see trigger_deprecation()
 * @param string $message the message to accompany the triggered error
 * @param int $stack_offset the relative stack position of the code that is the
 *        source of the error; the default of 1 assumes it is the function that
 *        called the function that called trigger_warning()
 * @return void
 */
function trigger_warning($message, $stack_offset=1)
{
	_carl_util_trigger_error($message, WARNING, $stack_offset);
}

/**
 * Triggers a deprecation error.
 * 
 * The error will be of type E_USER_DEPRECATED (where available) or
 * {@link WARNING}.
 * 
 * @see trigger_error()
 * @param string $message the message to accompany the triggered error
 * @param int $stack_offset the relative stack position of the code that called
 *        whatever is now deprecated; the default of 1 assumes it is the
 *        function that called the function that called trigger_deprecation()
 * @return void
 */
function trigger_deprecation($message, $stack_offset=1)
{
	$level = (defined("E_USER_DEPRECATED")) ? E_USER_DEPRECATED : WARNING;
	_carl_util_trigger_error($message, $level, $stack_offset);
}

$prior_err_handler = set_error_handler('carl_util_handle_error');

/** @access private */
function _verify_error_handler_setup()
{
	foreach ($GLOBALS['_DEVELOPER_INFO'] as $dev) {
		if (!empty($dev['ip']))
			return;
	}
	
	echo '<p style="background-color:#ddd;color:#333;font-size:80%;margin:0;'.
		'padding:.35em;border-bottom:1px solid #333;"><strong>Note:</strong> '.
		'The error handler is not yet set up. Administrators must look in '.
		'the PHP error logs ('.PHP_ERROR_LOG_FILE.') to see any errors that '.
		'occur in the execution of this script. To turn this notice off, '.
		'edit '.SETTINGS_INC.'error_handler_settings.php</p>'."\n";
}
_verify_error_handler_setup();
