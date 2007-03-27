<?

/**
 * asset_access_handler.php is a procedural wonder that uses the asset_access.php class to secure assets
 *
 * @author Nathan White
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
		trigger_error('asset_access_handler.php was run at url ' . get_current_url() . ' but was given an entity id ' . $id . ' that is not an asset! ');
	}
}
	
if (!defined('ERROR_404_PAGE'))
{
	echo '<h2>Not Found</h2>';
	echo '<p>The resource you are trying to access could not be located</p>';
}
else header ( 'Location: '.ERROR_404_PAGE );
?>
