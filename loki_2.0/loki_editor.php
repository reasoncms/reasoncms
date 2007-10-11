<?php

/**
 * A cache-aware script that compiles all of Loki's JavaScript files
 * and sends them all to the browser.
 *
 * Note that this file contains complex sorting rules that ensure that
 * all JavaScript files that are depended on by others are included first.
 *
 * @author Eric Naeseth
 */

error_reporting(0);

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

if (!$d = dir(LOKI_JS_PATH)) {
	response_code(500, 'Internal Server Error');
	header('Content-Type: text/plain');
	
	echo 'Failed to open the Loki directory.';
}

$latest_modified = filemtime(__FILE__);
$files = array();

function compare_filenames($a, $b)
{
	static $priority_util_files = array(
		'Util.js', 'Util.Scheduler.js', 'Util.Function.js', 'Util.Array.js',
		'Util.Node.js', 'Util.Element.js', 'Util.Event.js'
	);
	
	$a_ut = (0 == strncmp($a, 'Util', 4));
	$a_ui = (0 == strncmp($a, 'UI', 2));
	$b_ut = (0 == strncmp($b, 'Util', 4));
	$b_ui = (0 == strncmp($b, 'UI', 2));
	
	if (!$a_ut && !$a_ui) {
		return (!$b_ut && !$b_ui)
			? strcasecmp($a, $b)
			: -1;
	} else if (!$b_ut && !$b_ui) {
		return 1;
	} else if ($a_ut) {
		if ($b_ui)
			return -1;
			
		foreach ($priority_util_files as $special_file) {
			if ($a == $special_file)
				return -1;
			if ($b == $special_file)
				return 1;
		}
		
		return strcasecmp($a, $b);
	} else if ($b_ut) {
		if ($a_ui)
			return 1;
		else
			return strcasecmp($a, $b);
	} else if ($a == 'UI.js') {
		return -1;
	} else if ($b == 'UI.js') {
		return 1;
	} else {
		return strcasecmp($a, $b);
	}
}

function add_file(&$files, $file)
{
	for ($i = 0; $i < count($files); $i++) {
		if (compare_filenames($files[$i], $file) < 0)
			continue;
		
		for ($j = (count($files) - 1); $j >= $i; $j--) {
			$files[$j + 1] = $files[$j];
		}
		
		break;
	}
	
	$files[$i] = $file;
}

while (false !== $e = $d->read()) {
	if ($e{0} == '.' || substr($e, -3) != '.js')
		continue;
	
	$path = $d->path.DIRECTORY_SEPARATOR.$e;
	$mtime = filemtime($path);
	if ($mtime > $latest_modified)
		$latest_modified = $mtime;
	
	add_file($files, $e);
}
$d->close();

header('Last-Modified: '.gmstrftime('%a %d %b %Y %H:%M:%S GMT',
	$latest_modified));
header('Language: en-us');
header('Cache-Control: public');
header('Cache-Control: must-revalidate', false);
header('Content-Type: text/javascript; charset=utf-8');

if ($max_age === null && $latest_modified <= $ims || $latest_modified + $max_age > time()) {
	response_code(304, 'Not Modified');
	exit;
}

echo "// Loki WYSIWIG Editor 2.0\n";
echo '// Compiled ', strftime('%Y-%m-%d %H:%M:%S'), "\n\n";

foreach ($files as $file) {
	$path = LOKI_JS_PATH.DIRECTORY_SEPARATOR.$file;
	
	echo "\n// file $file \n\n";
	readfile($path);
}

?>