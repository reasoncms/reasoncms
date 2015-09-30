<?php
/**
 * Functions for manipulating the filesystem
 * @package carl_util
 * @subpackage basic
 */

/**
 * Recursively create directories until the directory requested exists
 *
 * This function allows you to create /foo/bar/baz/ when only /foo/ exists
 *
 * @param string $dir the absolute filesystem directory that you want to be created
 * @param int $mode the permissions on the directories created; defaults to OCTAL 0775 (rwxrwxr-)
 * @param boolean $recursive not sure what this does
 * @return boolean success
 *
 */
function mkdir_recursive($dir, $mode = 0775, $recursive = true)
{
	if( is_null($dir) || $dir === '' ) // bad inputs
	{
		return FALSE;
	}
	if( is_dir($dir) || $dir === '/' ) // we've hit a real directory
	{
		return TRUE;
	}
	if( mkdir_recursive(dirname($dir), $mode, $recursive) ) // try creating the parent directory
	{
		$success = mkdir($dir, $mode); // if that worked, create the directory requested
		if($success)
		{
			chmod($dir, $mode);
		}
		return $success;
	}
	return FALSE;
}

/**
 * Joins path parts together. Empty parts will be ignored.
 *
 * Example:
 * <code>
 * path_join('foo', 'bar', 'baz.txt') => 'foo/bar/baz.txt' // on UNIX
 *                                    => 'foo\\bar\\baz.txt' // on Windows
 * path_join('foo', null, 'bar', '', 'baz.txt') => 'foo/bar/baz.txt' // (UNIX)
 * path_join(array('foo', 'bar')) => 'foo/bar' // with an array
 * </code>
 *
 * @see DIRECTORY_SEPARATOR
 * @param string|array $part,... path parts
 * @return string the joined path
 */
function dir_join($part)
{
	$parts = func_get_args();
	if (is_array($parts[0]))
		$parts = $parts[0];
	return implode(DIRECTORY_SEPARATOR,
		array_filter($parts, '_non_empty_string'));
}

/**
 * Splits a filename into its base and extension.
 * If the file has no extension, the extension field will be an empty string.
 *
 * Example:
 * <code>
 * get_filename_parts("foo.txt") => array("foo", "txt")
 * get_filename_parts("bar") => array("bar", "")
 * get_filename_parts("dir/baz.php") => array("dir/baz", "php")
 * </code>
 *
 * @param string $filename
 * @param boolean $include_dot if true, the extension will be returned as (e.g.) ".txt", not "txt"
 * @return array
 */
function get_filename_parts($filename, $include_dot=false)
{
	$parts = explode('.', $filename);

	if (count($parts) <= 1) {
		return array(basename($filename), '');
	} else {
		$extension = array_pop($parts);
		if (count($parts) > 1 && $parts[count($parts) - 1] == "tar") {
		    // special case for compressed TAR archives
		    $extension = array_pop($parts).".$extension";
		}
		return array(basename($filename, ".$extension"), ($include_dot) ? ".$extension" : $extension);
	}
}

/**
 * @access private
 */
function _non_empty_string($string)
{
	return 0 !== strlen($string);
}

?>