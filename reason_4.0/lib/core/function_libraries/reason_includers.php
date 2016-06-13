<?php
/**
 * This file contains a set of include functions which should replace the use of include, 
 * include_once, require, and require_once in Reason code. They first check to see if the 
 * file exists in the local directory before falling over to the core directory and finally 
 * spitting out an error if no file is found.
 * @package reason
 * @subpackage function_libraries
 * @author Matt Ryan <mryan@carleton.edu>
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

/**
 * Gets the path to a Reason file within the core directory.
 * @param string $path Path inside the core directory
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return string the path to the file
 */
function reason_get_core_path($path, $section = "lib")
{
	return REASON_INC.$section."/core/".$path;
}

/**
 * Gets the path to a Reason file within the local directory.
 * @param string $path Path inside the local directory
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return string the path to the file
 */
function reason_get_local_path($path, $section = "lib")
{
	return REASON_INC.$section."/local/".$path;
}

/**
 * Gets the path to a Reason library file.
 * Returns the path to the file in the local directory if it exists, otherwise
 * the path to the file in the core directory if that exists, otherwise null.
 * @param string path inside the local or core directory
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return string the path to the file, or NULL if it wasn't found
 */
function reason_resolve_path($path, $section = "lib")
{
	$resolved = reason_get_local_path($path, $section);
	if (file_exists($resolved))
		return $resolved;
	
	$resolved = reason_get_core_path($path, $section);
	if (file_exists($resolved))
		return $resolved;
	
	return null;
}
 
/**
 * Reason Includer -- base function that handles all inclusions and requirements
 * First checks to see if the file exists in the local directory, then in the core
 * directory.
 * @param string $path Path inside the core and/or local directories
 * @param string $section "lib" is the default and nothing else is supported right now
 * @param string $function Chose type of include: include_once, include, require_once, or require; defaults to include_once
 * @return bool true if an inclusion was executed (and possibly succeeded); false if otherwise
 */
function reason_includer($path, $section = 'lib', $function = 'include_once')
{
	$prefix = REASON_INC.$section;
	$local = $prefix."/local/".$path;
	$core = $prefix."/core/".$path;
	if (file_exists($local))
	{
		return _reason_include_file($function, $local);
	}
	else if (file_exists($core))
	{
		return _reason_include_file($function, $core);
	}
	else
	{
		$level = ($function == 'require' || $function == 'require_once')
			? E_USER_ERROR
			: WARNING;
		trigger_error('reason_includer(): file does not exist at '.$local.
			' or '.$core, $level);
		return false;
	}
}
/**
 * Reason Include Once -- a wrapper for Reason Includer that replaces include_once()
 * Simpler interface that should generally only require a single parameter -- the path
 * @param string $path Path inside the core and/or local directories
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return bool $success
 */
function reason_include_once($path, $section = 'lib')
{
	return reason_includer($path, $section);
}
/**
 * Reason Include -- a wrapper for Reason Includer that replaces include()
 * Simpler interface that should generally only require a single parameter -- the path
 * @param string $path Path inside the core and/or local directories
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return bool $success
 */
function reason_include($path, $section = 'lib')
{
	return reason_includer($path, $section, 'include');
}
/**
 * Reason Require Once -- a wrapper for Reason Includer that replaces require_once()
 * Simpler interface that should generally only require a single parameter -- the path
 * @param string $path Path inside the core and/or local directories
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return bool $success
 */
function reason_require_once($path, $section = 'lib')
{
	return reason_includer($path, $section, 'require_once');
}
/**
 * Reason Require -- a wrapper for Reason Includer that replaces require()
 * Simpler interface that should generally only require a single parameter -- the path
 * @param string $path Path inside the core and/or local directories
 * @param string $section "lib" is the default and nothing else is supported right now
 * @return bool $success
 */
function reason_require($path, $section = 'lib')
{
	return reason_includer($path, $section, 'require');
}
/**
 * Low-level function to include a file.
 * @param string $behavior the name of a PHP inclusion construct (e.g., "include_once")
 * @param string $file the name of the file to include using the given construct
 * @return bool true if $behavior was a valid construct name, false if otherwise
 * @access private
 */
function _reason_include_file($behavior, $file)
{
	switch ($behavior)
	{
		case 'include_once':
			return include_once($file);
		case 'require_once':
			return require_once($file);
		case 'include':
			return include($file);
		case 'require':
			return require($file);
		default:
			$behavior_repr = var_export($behavior, true);
			trigger_error('_reason_include_file(): '.$behavior_repr.
				' is not the name of a PHP inclusion statement like '.
				'"include" or "require_once"', E_USER_ERROR);
			return false;
	}
}
