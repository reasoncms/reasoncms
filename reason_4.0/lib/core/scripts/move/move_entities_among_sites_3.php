<?php
/**
 * Move entities from one site to another -- step 3
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once( DISCO_INC .'disco.php');
reason_include_once( 'classes/entity_selector.php');

reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'classes/url_manager.php');

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$current_user_id = get_user_id($current_user);
if (empty( $current_user_id ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason users may do that.</p></body></html>');
}
elseif (!reason_user_has_privs( $current_user_id, 'edit' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason users who have full editing privs may do that.</p></body></html>');
}
if ( !empty($_REQUEST['new_site_ids']) && !empty($_REQUEST['old_site_id']) &&
	!empty($_REQUEST['allowable_relationship_id']) )
{
	$new_site_ids = (array) $_REQUEST['new_site_ids'];
	$old_site_id = (integer) $_REQUEST['old_site_id'];
	$allowable_relationship_id = (integer) $_REQUEST['allowable_relationship_id'];
}
else
{
	header('Location: ' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH  . 'scripts/move/move_entities_among_sites.php');
	die();
}
$user = new entity($current_user_id);

foreach ( $new_site_ids as $entity_id => $new_site_id )
{
	$entity_id = (integer) $entity_id;
	$new_site_id = (integer) $new_site_id;
	$entity = new entity($entity_id);
	if($entity_id && $new_site_id && $entity->user_can_edit_relationship($allowable_relationship_id,$user,'left'))
	{
		$q = ( 'UPDATE relationship SET entity_a="' . addslashes($new_site_id) . '" ' .
		   'WHERE entity_a="' . addslashes($old_site_id) . '" ' .
		   'AND entity_b="' . addslashes($entity_id) . '" ' .
		   'AND type="' . addslashes($allowable_relationship_id) . '"' );
		$r = db_query($q, 'Unable to update relationships.');
	}
}

$urlm = new url_manager($old_site_id);
$urlm->update_rewrites();

foreach($_REQUEST['new_site_ids'] as $new_site_id)
{
	$site_id = (integer) $new_site_id;
	if($new_site_id != $old_site_id)
	{
		$urlm = new url_manager($new_site_id);
		$urlm->update_rewrites();
	}
}


echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
echo '<html><head>';
echo '<title>Reason: Move Entities Among Sites: Done</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'css/reason_admin/move_entities.css" />'."\n";
echo '</head><body>';

echo '<h1>Move Entities Among Sites</h1>';
echo ( '<p>Successfully moved entities! Now, you may ' .
	   '<a href="' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'scripts/move/move_entities_among_sites.php">' .
	   'move other entities among sites</a> ' .
	   'or <a href="' . securest_available_protocol() . '://' . REASON_WEB_ADMIN_PATH  . '">return to Reason admin</a>.</p>' );

echo ( '<p><strong>N.B.:</strong> This script has merely updated the entity_as
		in the relationship table where the type has an id
		corresponding to an allowable relationship named
		&quot;owns&quot;. So depending on the particulars of the
		entities you&apos;ve moved, <strong>you might not be
		done</strong>. If you&apos;ve moved some minisite pages, for
		example, you still have to choose one of the new site&apos;s
		pages as a parent for each newly-moved page (assuming that you
		want the pages to show up in the navigation).</p>' );

if ( isset($_SESSION['move_entities_among_sites__http_referer']) )
	unset($_SESSION['move_entities_among_sites__http_referer']);

echo '</body></html>';

?>
