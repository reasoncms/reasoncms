<?php
/**
 * Find local dependencies
 *
 * This script checks all files in Reason's core directory to see if they use one of the reason_include() functions to bring in a file *not* in the core.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start the page
 */
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>'."\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
echo '<title>Reason Core Dependencies on Local Files</title>'."\n";
echo '</head>'."\n";
echo '<body>'."\n";
echo '<h1>Reason: Check for core->local dependencies</h1>'."\n";


include_once( 'reason_header.php' );
connectDB( REASON_DB );
reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$reason_user_id = get_user_id ( $current_user );
if (!reason_user_has_privs( $reason_user_id, 'view_sensitive_data' ) )
{
	die('<h1>Sorry.</h1><p>You do not have proper permissions to run this script.</p></body></html>');
}

$dir = REASON_INC.'lib/core/';
echo '<p>In most cases, core files should not include local files.</p>'."\n";
echo '<p>This script checks all files in Reason\'s core directory ('.$dir.') to see if they use one of the reason_include() functions to bring in a file <em>not</em> in the core.</p>'."\n";
echo '<h2>Results:</h2>'."\n";

// Load all of the filenames from core into an array
$files = get_file_list($dir);
$paths = array_keys($files);
$error_list = array();

// Loop through all the files, opening those with php* extensions
foreach ($files as $path => $file) {
	if (preg_match('/\.php.?$/', $file))
	{
		$contents = file_get_contents($path);
		// find all reason include/require
		if(preg_match_all('/reason_(?:include|require)(?:_once)?\(\s*[\'\"]([^\)]+)[\'\"]\s*\)/', $contents, $matches))
		{
			foreach ($matches[1] as $target)
			{
				// Throw out any includes that contain variable references
				if (strpos($target, '$') !== FALSE) continue;
				
				$found = false;
				$cleantarget = str_replace('/', '\/', $target);
				// Loop through all the paths looking for any that end with this string
				foreach ($paths as $matchpath)
				{
					if (preg_match('/'.$cleantarget.'$/', $matchpath)) $found = true;
				}
				// If we don't find any, add this to the array of errors
				if (!$found) 
				{
					if (!isset($error_list[$path])) $error_list[$path] = array();
					$error_list[$path][] = $target;
				}
			}
		}
	}
}


if(!empty($error_list))
{
	echo '<h3>'.count($error_list).' file(s) in core have dependencies on local files</h2>'."\n";
	echo '<ul>';
	foreach ($error_list as $file => $ilist)
	{
		echo '<li>'.$file.'</li><ul>';
		foreach ($ilist as $include)
		{
			echo '<li>'.$include;
			if($file == $dir.'minisite_templates/page_types.php' && $include == 'minisite_templates/page_types_local.php')
			{
				echo ' <em>(Note: this is an expected (e.g. "normal") dependency)</em>';
			}
			echo '</li>'."\n";
		}
		echo '</ul>'."\n";
	}
	echo '</ul>'."\n";
}
else
{
	echo '<h3>No core files have local dependencies</h2>'."\n";
}

echo '</body>'."\n";
echo '</html>';

// Recursively get an array containing all filenames below the given dir, keyed by full path
function get_file_list ($dir) { 
	$files = array();
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file != '.' && $file != '..' && $file != '.svn') 
				{
					if (is_dir($dir.$file.'/'))
					{
						$files = array_merge($files, get_file_list($dir.$file.'/'));
					} else {
						$files[$dir.$file] = $file;
					}

				}
			}
		closedir($dh);
		}
	}
	return $files;
}

?>
