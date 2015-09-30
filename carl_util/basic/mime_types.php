<?php

/**
 * MIME type checking and matching.
 *
 * @package carl_util
 * @subpackage basic
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 * @author Nathan White <nwhite@carleton.edu>
 */

/**
 * Determines if the given MIME types matches the given type pattern.
 * Useful since {@link fnmatch()} is not available on non-POSIX systems.
 *
 * If <code>$pattern</code> is an array, each element of the array will be
 * checked for a match.
 *
 * Examples:
 * <code>
 * mime_type_matches("image/*", "image/png") ==> true
 * mime_type_matches("image/png", "image/png") ==> true
 * mime_type_matches("application/*", "image/png") ==> false
 * </code>
 *
 * @param string|array $pattern MIME type pattern, or an array of such patterns
 * @param string $type MIME type to check against the pattern(s)
 * @return boolean <code>true</code> if the type matches the pattern(s);
 *         <code>false</code> if otherwise
 */
function mime_type_matches($pattern, $type)
{
	list($t_main, $t_sub) = explode('/', $type);
	
	foreach ((array) $pattern as $single_pattern) {
		list($p_main, $p_sub) = explode('/', $single_pattern);
		
		if ($p_main != '*' && $p_main != $t_main)
			continue;
		if ($p_sub && $p_sub != '*' && $p_sub != $t_sub)
			continue;

		return true; // match!
	}
	
	return false;
}

/**
 * Determines the MIME type of a file based solely on its file extension.
 * Requires that the configuration constant {@link APACHE_MIME_TYPES} has been
 * defined and contains the path to an Apache mime.types file.
 *
 * {@internal Adapted from <code>ReasonAssetAccess.get_mime_type()</code>.}
 *
 * @param string $extension the file extension whose MIME type is desired
 * @param string $default what to return if the type cannot be determined
 * @return string the determined MIME type, or <code>$default</code> if the
 *         type could not be determined
 **/
function mime_type_from_extension($extension, $default=null)
{
	static $cache = array();
	static $error_reported = false;
	
	$extension = ltrim(strtolower($extension), '.');
	if (isset($cache[$extension]))
		return $cache[$extension];
	
	if (!defined('APACHE_MIME_TYPES')) {
		if (!$error_reported) {
			$error_reported = true;
			trigger_warning('cannot determine MIME type of file based on its '.
				'extension: the APACHE_MIME_TYPES constant is not defined');
			return $default;
		}
	}
	
	if (!is_file(APACHE_MIME_TYPES)) {
		if (!$error_reported) {
			$error_reported = true;
			trigger_warning('cannot determine MIME type of file based on its '.
				'extension: no such file: '.
				var_export(APACHE_MIME_TYPES, true));
			return $default;
		}
	}
	
	$file = fopen(APACHE_MIME_TYPES, 'rt');
	$result = $default;
	while (!feof($file)) {
		if (fscanf($file, "%s\t%[^\n]", $type, $extension_list)) {
			if ($type[0] == '#' || empty($extension_list))
				continue;
			
			if (in_array($extension, explode(' ', $extension_list))) {
				// match!
				$cache[$extension] = $result = $type;
				break;
			}
		}
	}
	
	fclose($file);
	return $result;
}

/**
 * Determines the MIME type of an actual file using any available techniques:
 * <code>getimagesize()</code>, the PHP Fileinfo extension, the UNIX
 * <code>file(1)</code> utility, or the mime.types file.
 * 
 * @param string $path the path to the file whose MIME types is desired
 * @param string $default what to return if the type cannot be determined
 * @param string $filename if <code>$path</code> does not reflect the actual
 *        name of the file (e.g., the path has no file extension), pass the
 *        true filename here
 * @return string the determined MIME type, or <code>$default</code> if the
 *         type could not be determined
 */
function get_mime_type($path, $default=null, $filename=null)
{
	$type = _get_mime_type_fileinfo($path);
	
	if (!$type || $type == 'application/octet-stream')
		$type = _get_mime_type_unix($path);
	
	if (!$filename)
		$filename = basename($path);
	$extension = strtolower(ltrim(strrchr($filename, '.'), '.'));
	$type = _sanity_check_mime_type($extension, $type);
	
	if (!$type || $type == 'application/octet-stream') {
		if (!$filename)
			$filename = $path;
		$type = mime_type_from_extension($extension);
	}
	
	if (!$type || mime_type_matches('image/*', $type)) {
		// If we detected an image type, verify it using getimageinfo().
		// If the image was not valid, the type will be reset to NULL.
		$type = _get_mime_type_image($path);
	}
	
	return ($type) ? $type : $default;
}

/**
 * @access private
 */
function _get_mime_type_fileinfo($path)
{
	if (!function_exists('finfo_open'))
		return false;
	
	$fidb = finfo_open( (defined("FILEINFO_MIME_TYPE") ? FILEINFO_MIME_TYPE : FILEINFO_MIME ) );
	if (!$fidb)
		return false;
	
	$type = finfo_file($fidb,$path);
	finfo_close($fidb);
	return $type;
}

/**
 * @access private
 */
function _get_mime_type_unix($path)
{
	$command = "file -Lbi ".escapeshellarg($path);
	$output = array();
	$ret = null;
	$result = exec($command, $output, $ret);
	
	if ($ret !== 0 || !$result)
		return false;
	
	// file(1) may output multiple media types; let's just take the first one
	$m = array();
	if (!preg_match('/([\w\.-]+\/[\w\.-]+)(;\s*\w+=[\w\.\-]+)*/', $result, $m))
		return false;
	return $m[0];
}

/**
 * Fixes stupid results from file(1).
 * 
 * On some systems, the file command can return some results that we really
 * don't want. For example, some assume that the presence of a C-style comment
 * start token (/*) means that the file is a C source or header file
 * (text/x-c). This is bad when it is given (for example) a CSS file that has
 * any comments in it.
 * 
 * @param string $file_extension
 * @param string $mime_type
 * @return string a maybe-fixed MIME type
 * @access private
 */
function _sanity_check_mime_type($file_extension, $mime_type)
{
	if ($file_extension == 'css') {
		// We're just going to assume it's CSS.
		
		$m = array();
		return (preg_match('/;\s*charset=(\S+)/', $mime_type, $m))
			? "text/css; charset={$m[1]}"
			: "text/css";
	}
	if ($file_extension == 'xls') {
		// We're just going to assume it's Excel.
		return "application/excel";
	}
	if ($file_extension == 'mp4') {
		// We're just going to assume it's video/mp4.
		return "video/mp4";
	}
	if ($file_extension == 'flv') {
		// We're just going to assume it's video/x-flv.
		return "video/x-flv";
	}
	if ($file_extension == 'wmv') {
		// We're just going to assume it's video/x-ms-wmv.
		return "video/x-ms-wmv";
	}
	if ($file_extension == 'docx') {
		// We're just going to assume it's an office 2007 doc.
		return "application/vnd.openxmlformats";
	}
	if ($file_extension == 'pptx') {
		// We're just going to assume it's an office 2007 doc.
		return "application/vnd.openxmlformats";
	}
	if ($file_extension == 'xlsx') {
		// We're just going to assume it's an office 2007 doc.
		return "application/vnd.openxmlformats";
	}
	return $mime_type;
}

/**
 * @access private
 */
function _get_mime_type_image($path)
{
	$result = @getimagesize($path);
	if (!$result)
		return null;
	return @$result['mime'];
}
