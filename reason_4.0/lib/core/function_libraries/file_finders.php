<?php
/**
 * Functions for finding Reason files
 * @package reason
 * @subpackage function_libraries
 */
 
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
 * Determines if a file exists in either core or local areas
 * @author Matt Ryan
 * @date 2006-05-18
 * @param string $path
 * @param string $section
 * @return bool $file_exists
 */
function reason_file_exists($path, $section = 'lib')
{
	$areas = array('core','local');
	foreach($areas as $area)
	{
		if(file_exists(REASON_INC.$section.'/'.$area.'/'.$path))
		{
			return true;
		}
	}
	return false;
}
?>