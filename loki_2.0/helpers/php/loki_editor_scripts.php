<?php

/**
 * A cache-aware script that compiles all of Loki's JavaScript files
 * and sends them all to the browser.
 *
 * Currently the cache-awareness really only works when PHP is running as
 * an Apache module.
 *
 * Note that this file contains complex sorting rules that ensure that
 * all JavaScript files that are depended on by others are included first.
 *
 * @author Eric Naeseth
 */

error_reporting(0);

if (!defined('DIRECTORY_SEPARATOR'))
	define('DIRECTORY_SEPARATOR', '/');

function path_join()
{
	$args = func_get_args();
	return implode(DIRECTORY_SEPARATOR, $args);
}


require_once path_join(dirname(__FILE__), 'inc', 'js_filenames.php');

if (!defined('LOKI_2_PATH')) {
	if (defined('LOKI_2_INC')) {
		// old constant name
		define('LOKI_2_PATH', LOKI_2_INC);
	} else {
		
		define('LOKI_2_PATH',
			dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR);
	}
}

if (is_callable('apache_request_headers')) {
	$headers = apache_request_headers();
	$ims = (isset($headers['If-Modified-Since']))
		? strtotime($headers['If-Modified-Since'])
		: 0;
		
	$max_age = get_max_age($headers);
} else {
	$ims = 0;
}

function get_max_age($headers)
{
	if (!$cc = @$headers['Cache-Control'])
		return null;
	
	$m = array();
	if (!preg_match('/max-age\s*=\s*(\d+)/', $cc, $m))
		return null;
	
	return (int) $m[1];
}

if (!defined('LOKI_JS_PATH')) {
	define('LOKI_JS_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR.
		'js');
}

function response_code($code, $description='')
{
	$protocol = (isset($_SERVER['SERVER_PROTOCOL']))
		? $_SERVER['SERVER_PROTOCOL']
		: 'HTTP/1.1';
	
	header($protocol.' '.((string) $code).' '.$description);
}

$finder = new Loki2ScriptFinder(LOKI_2_PATH.'js');
if (!$finder->files) {
	response_code(500, 'Internal Server Error');
	header('Content-Type: text/plain');
	
	echo 'Failed to open the Loki directory '.LOKI_2_PATH.'js';
	exit;
}

$latest_modified = $finder->latest_modified_time;

header('Last-Modified: '.gmstrftime('%a %d %b %Y %H:%M:%S GMT',
	$latest_modified));
header('Language: en-us');
header('Cache-Control: public');
header('Cache-Control: must-revalidate', false);
header('Content-Type: application/javascript; charset=utf-8');

if ($max_age === null && $latest_modified <= $ims || $latest_modified + $max_age > time()) {
	response_code(304, 'Not Modified');
	exit;
}

echo "// Loki WYSIWIG Editor 2.0\n";
echo '// Compiled ', strftime('%Y-%m-%d %H:%M:%S UTC%z'), "\n\n";

foreach ($finder->files as $file) {
	$path = path_join(LOKI_2_PATH.'js', $file);
	
	echo "\n// file $file \n\n";
	readfile($path);
}