<?php
/**
 * Given an md5 css basename, if we have a file in the less_compiled directory with the basename, serve it using a 301 redirect.
 *
 * If not, do a basic 404.
 *
 * @package reason
 * @subpackage scripts
 *
 * @author Nathan White
 */

/**
 * Include dependencies
 */
include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );

if (!empty($_GET['basename']) && ctype_alnum($_GET['basename']))
{	
	$path = '/'.trim_slashes(WEB_PATH).'/'.trim_slashes(WEB_TEMP).'/less_compiled/'. substr($_GET['basename'], 0, 2).'/'.$_GET['basename'] . '_';
	if ($result = glob($path."*.css"))
	{
		$cur_timestamp = 0;
		foreach ($result as $afile)
		{
			$css_filename = basename($afile, ".css");
			$new_timestamp = substr($css_filename, strpos($css_filename, "_") + 1);
			$file = (!isset($file) || ($new_timestamp > $cur_timestamp)) ? $afile : $file;
			$cur_timestamp = $new_timestamp;
		}
		http_response_code(301);
		header('Location: ' . dirname(get_current_url()) . '/' . basename($file));
		exit;
	}
}
http_response_code(404);
include_once(WEB_PATH.ERROR_404_PATH);