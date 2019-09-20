<?php
/**
 * Copy entities from one site to another -- step 3
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once( 'reason_header.php' );
include_once( DISCO_INC . 'disco.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/url_manager.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'scripts/copy/copy_entities_among_sites_helper.php' );
reason_include_once( 'classes/job.php' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
force_secure_if_available();
$current_user    = reason_check_authentication();
$current_user_id = get_user_id( $current_user );

if ( empty( $current_user_id ) ) {
	die( '<h1>Sorry.</h1><p>You do not have permission to copy entities among sites.</p><p>Only Reason admins may do that.</p></body></html>' );
} elseif ( ! reason_user_has_privs( $current_user_id, 'db_maintenance' ) ) {
	die( '<h1>Sorry.</h1><p>You do not have permission to copy entities among sites.</p><p>Only Reason admins who have database maintenance privs may do that.</p></body></html>' );
}
if ( ! empty( $_REQUEST['new_site_ids'] ) &&
     ! empty( $_REQUEST['old_site_id'] ) &&
     ! empty( $_REQUEST['allowable_relationship_id'] ) &&
     ! empty( $_REQUEST['type_id'] ) ) {
	$new_site_ids              = (array) $_REQUEST['new_site_ids'];
	$old_site_id               = (integer) $_REQUEST['old_site_id'];
	$type_id                   = (integer) $_REQUEST['type_id'];
	$allowable_relationship_id = (integer) $_REQUEST['allowable_relationship_id'];
	$borrows_relationship_id   = get_borrows_relationship_id( $type_id );
	$type_name                 = unique_name_of( $type_id );
//	$pre_process_method_exists = method_exists('CopyEntitiesPreProcess', $type_name);
//	$post_process_method_exists = method_exists('CopyEntitiesPostProcess', $type_name);
} else {
	header( 'Location: ' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'scripts/copy/copy_entities_among_sites.php' );
	die();
}

// Copy the things
$new_entities = [];
foreach ( $new_site_ids as $entity_id => $new_site_id ) {
	if ( $new_site_id != $old_site_id ) {
		$new_ent_id = duplicate_entity( $entity_id, false, true, [], $new_site_id );
		create_relationship( $new_site_id, $new_ent_id, relationship_id_of( "site_owns_$type_name" ) );
		$new_entities[] = $new_ent_id;
	}
}
$result = count( $new_site_ids );

echo '<!DOCTYPE html>';
echo '<html><head>';
echo '<title>Reason: Copy Entities Among Sites: Done</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if ( defined( 'UNIVERSAL_CSS_PATH' ) && UNIVERSAL_CSS_PATH != '' ) {
	echo '<link rel="stylesheet" type="text/css" href="' . UNIVERSAL_CSS_PATH . '" />' . "\n";
}
echo '<link rel="stylesheet" type="text/css" href="' . REASON_HTTP_BASE_PATH . 'css/reason_admin/move_entities.css" />' . "\n";
echo '</head><body>';

echo '<h1>Copy Entities From One Site to Another</h1>';
if ( $result ) {
	echo( '<p>Successfully copied entities! Now, you may ' .
	      '<a href="' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'scripts/copy/copy_entities_among_sites.php">' .
	      'copy other entities among sites</a> ' .
	      'or <a href="' . securest_available_protocol() . '://' . REASON_WEB_ADMIN_PATH . '">return to Reason admin</a>.</p>' );

	echo( '<p><strong>Please note:</strong> This script has done the particulars outlined in the report. There may be more you have to do yourself. For instance,
	        if you copied page(s), you\'ll need to attach them to the new page tree before they will show up.</p>' );
} else {
	echo '<p>Your copy-entities job was not completed successfully. Please look carefully at the report to see what you may need to change.</p>';
}
if ( isset( $_SESSION['copy_entities_among_sites__http_referer'] ) ) {
	unset( $_SESSION['copy_entities_among_sites__http_referer'] );
}

echo '<h3>Full Report</h3><p>N/A</p>';
//$report = $job_stack->get_report();
//echo $report;
echo '</body></html>';
?>
