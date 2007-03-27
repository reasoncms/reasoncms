<?php
	/**
	 * Bulk Image Import
	 *
	 * @package Reason_Core
	 * @subpackage scripts
	 *
	 * This script will run through a define directory( INCOMING_DIR ), and make Reason
	 * Image entities out of those images.  It will resize and make thumbnails as
	 * appropriate.
	 *
	 * TODO: Generalize this script for any site.
	 * [6/14/04] the only ID information needed by this script to perform correctly
	 * is the ID of the page to link to and the directory of the incoming images.
	 * If we standardize the incoming directory name, this script can work for
	 * any site.  All that needs to be passed in is the unique_name or id of the
	 * page to relate the images to.  My initial thought is to have a directory
	 * called incoming_photos, incoming_images, or just incoming in the base
	 * directory of a site similarly to how we treat .htaccess and test directories.
	 * To further slickify the system, we could have a bulk image import script
	 * that could drop in a zip file or a number of images into one of these
	 * directories and then import based on those files. -dh
	 *
	 * [12/13/2004] Added a form to populate all uploads with information specified.  Description, name, keywords,
	 * content, and datetime.
	 *
	 * @author Dave Hendler
	 */
	set_time_limit( 0 );	

	include_once( 'reason_header.php' );
	
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
	echo '<html>'."\n".'<head>'."\n".'<title>Import Images Into Reason</title>'."\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
	if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
	{
		echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
	}
	echo '<style type="text/css">body { margin:1.5em; }</style>'."\n";
	echo '</head>'."\n";
	echo '<body>'."\n";
	echo '<h1>Batch Import Images Into Reason</h1>';
	
	echo '<p>This tool has beeen moved. In Reason there is a link titled "Batch Import Images" under every "Add Image" link.</p>'."\n";
	echo '<p><a href="https://'.REASON_WEB_ADMIN_PATH.'">Go to Reason</a></p>'."\n";
	
	echo '</body>'."\n".'</html>';

?>
