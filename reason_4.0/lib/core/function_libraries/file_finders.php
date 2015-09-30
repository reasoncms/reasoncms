<?php
/**
 * Functions for finding Reason files
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
	
/**
 * Finds files in both the core and local directories and merges them into one listing
 * @author Matt Ryan
 * @date 2006-05-18
 * @param string $dir_path
 * @param string $section
 * @return array $files
 */
function reason_get_merged_fileset($dir_path, $section = 'lib')
{
	$areas = array('core','local');
	$files = array();
	foreach($areas as $area)
	{
		$directory = REASON_INC.$section.'/'.$area.'/'.trim_slashes($dir_path).'/';
		if(is_dir( $directory ) )
		{
			$handle = opendir( $directory );
			while( $entry = readdir( $handle ) )
			{
				if( is_file( $directory.$entry ) )
				{
					$files[$entry] = $entry;
				}
			}
		}
	}
	ksort($files);
	return $files;
}

/**
 * Checks to make sure the given file exists in either the core or the local
 * directories.
 * @param string $path Path inside the local and/or core directories
 * @param string $section "lib" is the default
 * @return boolean true if the file exists; false if otherwise
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */
function reason_file_exists($path, $section = "lib")
{
	return file_exists(reason_get_core_path($path, $section)) ||
		file_exists(reason_get_local_path($path, $section));
}
