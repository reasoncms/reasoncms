<?php
/**
 * Functions for manipulating the filesystem
 * @package carl_util
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

?>