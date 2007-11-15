<?php
/**
 * This file contains a set of include functions which should replace the use of include, 
 * include_once, require, and require_once in Reason code. They first check to see if the 
 * file exists in the local directory before falling over to the core directory and finally 
 * spitting out an error if no file is found.
 * @package reason
 * @author Matt Ryan
 * @date 2006-05-17
 */
 
/**
 * Reason Includer -- base function that handles all inclusions and requirements
 * First checks to see if the file exists in the local directory, then in the core
 * directory.
 * @param string $path Path inside the core and/or local directories
 * @param string $section Choose between "lib", "www", and "data"; "lib" is the default
 * @param string $function Chose type of include: include_once, include, require_once, or require; defaults to include_once
 * @return bool $success
 */
function reason_includer($path, $section = 'lib', $function = 'include_once')
{
	$localpath = REASON_INC.$section.'/local/'.$path;
	$corepath = REASON_INC.$section.'/core/'.$path;
	if($function == 'include_once' || $function == 'require_once')
	{
		$included_files = get_included_files();
		if(in_array($corepath,$included_files) || in_array($localpath,$included_files))
		{
			return true;
		}
	}
	if(file_exists($localpath))
	{

		if($function == 'include_once'){ 	include_once($localpath); 	}
		elseif($function == 'include'){ 		include($localpath); 		}
		elseif($function == 'require_once'){ 	require_once($localpath); 	}
		elseif($function == 'require'){ 		require($localpath); 		}
		else
		{
			trigger_error('reason_includer(): function name passed is not one of the following strings: include_once, include, require_once,require');
			return false;
		}
		return true;
	}
	elseif(file_exists($corepath))
	{
		if($function == 'include_once'){ 	include_once($corepath); 	}
		elseif($function == 'include'){ 		include($corepath); 		}
		elseif($function == 'require_once'){ 	require_once($corepath); 	}
		elseif($function == 'require'){ 		require($corepath); 		}
		else
		{
			trigger_error('reason_includer(): function name passed is not one of the following strings: include_once, include, require_once,require');
			return false;
		}
		return true;
	}
	else
	{
		trigger_error('reason_includer(): file does not exist at '.$localpath.' or '.$corepath);
		return false;
	}
}
/**
 * Reason Include Once -- a wrapper for Reason Includer that replaces include_once()
 * Simpler interface that should generally only require a single parameter -- the path
 * @param string $path Path inside the core and/or local directories
 * @param string $section Choose between "lib", "www", and "data"; "lib" is the default
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
 * @param string $section Choose between "lib", "www", and "data"; "lib" is the default
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
 * @param string $section Choose between "lib", "www", and "data"; "lib" is the default
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
 * @param string $section Choose between "lib", "www", and "data"; "lib" is the default
 * @return bool $success
 */
function reason_require($path, $section = 'lib')
{
	return reason_includer($path, $section, 'require');
}
?>
