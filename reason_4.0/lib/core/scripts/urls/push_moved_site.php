<?php
/**
 * A script that headers a user to a given site.
 *
 * This script exists to handle requests for the home page of a site
 * that has moved, but a directory still exists on the filesystem at that location.
 *
 * The 404 handler doesn't take care of these, because the 404 script is never called
 * (Apache produces a 403 -- permission denied -- instead.)
 *
 * @package reason
 * @subpackage scripts
 * @todo figure out better way to fall through to 403 page... ERROR_403_PAGE appears 
 *       to contain an absolute URL rather than a filesystem path? This makes including it
 *       tricky.
 */

/**
 * Include basic Reason libraries & settings
 */
include_once('reason_header.php');

/*
 * We don't want people to call this script directly so we check to see if their requested
 * path ends in '.php' and give them an error if it does. Keeps people from fishing around for
 * non-live sites and trying to find things they shouldn't.
 */
$request_uri = get_current_url();
$parts = parse_url($request_uri);
if( substr($parts['path'],-4) == '.php' )
{
	http_response_code(403);
	echo '<html>'."\n";
	echo '<head><title>Permission Denied</title></head>'."\n";
	echo '<body>'."\n";
	echo '<h1>403: Permission Denied</h1><p>This script may only be requested through a .htaccess file.</p>'."\n";
	echo '</body>';
	die();
}

reason_include_once('classes/entity.php');
$id = $_GET['id'];

$e = new entity($id);
if (is_object($e))
{
	$base_url = $e->get_value('base_url');
	$state = $e->get_value('state');
	if ($state == 'Live')
	{
		$url = HTTP_HOST_NAME . $base_url;
		$url_arr = parse_url( get_current_url() );
		if(!empty($url_arr['query']))
		{
			$url .= '?'.$url_arr['query'];
		}
	}
}
if (isset($url))
{
	http_response_code(301);
	header('Location: http://' . $url);
}
else
{
	http_response_code(403);
	include(ERROR_403_PAGE);
	die();
}
?>
