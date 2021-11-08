<?php
/**
 * Copy entities from one site to another -- step 1
 *
 * @package reason
 * @subpackage scripts
 *
 */

/**
 * include dependencies
 */
include_once( 'reason_header.php' );
include_once( DISCO_INC . 'disco.php' );
reason_include_once( 'classes/entity_selector.php' );

class DiscoCopyEntities extends Disco {
	function where_to() {
		$pass   = array( 'site_id', 'type_id', 'creation_date_start', 'creation_date_end', 'name_contains' );
		$params = array();
		foreach ( $pass as $element ) {
			if ( $val = $this->get_value( $element ) ) {
				$params[] = $element . '=' . urlencode( $val );
			}
		}

		return ( securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'scripts/copy/copy_entities_among_sites_2.php' .
		         '?' . join( '&', $params ) );
	}
}

echo '<!DOCTYPE html>';
echo '<html><head>';
echo '<title>Reason: Copy Entities From One Site to Another: Step 1</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if ( defined( 'UNIVERSAL_CSS_PATH' ) && UNIVERSAL_CSS_PATH != '' ) {
	echo '<link rel="stylesheet" type="text/css" href="' . UNIVERSAL_CSS_PATH . '" />' . "\n";
}
echo '<link rel="stylesheet" type="text/css" href="' . REASON_HTTP_BASE_PATH . 'css/reason_admin/move_entities.css" />' . "\n";
echo '<script type="text/javascript" src="' . JQUERY_URL . '"></script>' . "\n";
echo '<script type="text/javascript" src="' . REASON_HTTP_BASE_PATH . 'js/move_entities.js"></script>' . "\n";
echo '</head><body>';

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$user_id      = get_user_id( $current_user );
if ( empty( $user_id ) ) {
	die( '<h1>Sorry.</h1><p>You do not have permission to copy entities among sites.</p><p>Only Reason admins may do that.</p></body></html>' );
} elseif ( ! reason_user_has_privs( $user_id, 'db_maintenance' ) ) {
	die( '<h1>Sorry.</h1><p>You do not have permission to copy entities among sites.</p><p>Only Reason admins who have database maintenance privs may do that.</p></body></html>' );
}
echo '<h1>Copy Entities From One Site to Another</h1>';
echo '<h2>Step 1 of 2: Choose source site and entity type</h2>';
echo '<div style="background-color: orange; padding: 0.1em 1em; margin: 1em 0; max-width: 42em;">
	<h3>CAVEAT</h3>
	<p>This only modifies the database; if the items have any representation on the
	filesystem (like images, assets, sites, etc.) this will not duplicate them;
	also, strings that need to be different -- like filenames for assets
	or base urls for sites -- are not changed, so the database can be put
	into a bad state accidentally.
	</p>
	<p>
	It also duplicates *all* relationships for the item, which is not
	always desirable -- for example, if you duplicate an image you might
	not want a duplicate of that image to all of a sudden appear on all
	the sidebars where the image appeared. Or a site which borrowed the
	original will all of a sudden also be borrowing its duplicate. It also
	does not respect allowable relationship rules, so if the are
	one-to-many or many-to-one relationships involved, you might end up
	with the db in a bad state -- for example, if you duplicate a group
	that is attached to a page, the page will now have two groups attached
	to it, which is not supposed to happen.
	</p>
	</div>';

$es = new entity_selector();
$es->add_type( id_of( 'site' ) );
$es->add_left_relationship( $user_id, relationship_id_of( 'site_to_user' ) );
$es->set_order( 'entity.name ASC' );
$sites = $es->run_one();

$es = new entity_selector();
$es->add_type( id_of( 'type' ) );
$es->add_table( 'ar', 'allowable_relationship' );
$es->add_relation( 'ar.relationship_a = ' . id_of( 'site' ) );
$types = $es->run_one();

$site_options = array();
foreach ( $sites AS $site ) {
	$site_options[ $site->id() ] = $site->get_value( 'name' );
}

$type_options = array();
foreach ( $types AS $type ) {
	if ( $type->id() === id_of( 'minisite_page' ) ) {
		continue;
	}
	$type_options[ $type->id() ] = $type->get_value( 'name' );
}

$d = new DiscoCopyEntities;
$d->add_element( 'site_id', 'select', [ 'options' => $site_options, 'display_name' => 'Source Site', ] );
$d->add_element( 'type_id', 'select', [ 'options' => $type_options, 'display_name' => 'Entity Type', ] );
$d->add_element( 'options_comment', 'comment', array( 'text' => '<h4>Optional entity filters:</h4>' ) );
$d->add_element( 'creation_date_start', 'textDateTime_js' );
$d->add_element( 'creation_date_end', 'textDateTime_js' );
$d->add_element( 'name_contains', 'text' );
$d->add_required( 'site_id' );
$d->add_required( 'type_id' );
$d->actions = array( 'Continue' );
$d->run();

echo '</body></html>';

?>
