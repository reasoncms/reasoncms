<?php

/**
 * asset_access_handler.php is a procedural wonder that uses the asset_access.php class to secure assets
 *
 * @author Nathan White
 * @package reason
 * @subpackage scripts
 */

include 'reason_header.php';
reason_include_once ('classes/assets/asset_access.php');

$id = (!empty($_GET['id'])) ? (int) $_GET['id'] : '';

if (empty($id))
{
	trigger_error('asset_access_handler.php was run at url ' . get_current_url() . ' but was not given an asset id. There may be a problem with rewrite rules.');
}
else
{
	$asset_access = new ReasonAssetAccess($id);
	if (!$asset_access->run())
	{
		trigger_error('asset_access_handler.php was run at url ' . get_current_url() . ' but was given an entity id ' . $id . ' that is not an asset. Rewrite rules may need to be run for this site.');
	}
}
	
if(defined('ERROR_404_PATH') && defined('WEB_PATH') && file_exists(WEB_PATH.ERROR_404_PATH) && is_readable(WEB_PATH.ERROR_404_PATH))
{
	http_response_code(404);
	include(WEB_PATH.ERROR_404_PATH);
}
else
{
	http_response_code(404);
	echo '<!DOCTYPE html>'."\n";
	echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
	echo '<head><title>File Not Found (HTTP 404)</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>'."\n";
	echo '<body>'."\n";
	echo '<h2>File Not Found (HTTP 404)</h2>'."\n";;
	echo '<p>The file you are trying to access is not available.</p>'."\n";
	echo '</body>'."\n";
	echo '</html>'."\n";
}
?>
