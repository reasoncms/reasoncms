<?php
	/**
	 * This script used to do image importing.
	 *
	 * It is left in place so that people with bookmarks do not get a 404
	 *
	 * At some point it will probably be OK to remove
	 *
	 * @package reason
	 * @subpackage scripts
	 */

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
	echo '<p><a href="' . securest_available_protocol() . '://'.REASON_WEB_ADMIN_PATH.'">Go to Reason</a></p>'."\n";
	
	echo '</body>'."\n".'</html>';

?>
