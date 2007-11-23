<?php
/**
 * Move entities from one site to another -- step 1
 *
 * @package reason
 * @subpackage scripts
 *
 * @todo move this into the admin interface
 * @todo come up with a way to better handle moving different types, 
 *       esp. those that need special treatment when moved (like pages & assets)
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php');
reason_include_once( 'classes/entity_selector.php');

class DiscoMoveEntities extends Disco
{
	function where_to()
	{
		return ( securest_available_protocol() .'://' . REASON_HOST . REASON_HTTP_BASE_PATH. 'scripts/move/move_entities_among_sites_2.php' .
				 '?site_id=' . urlencode($this->get_value('site_id')) .
				 '&type_id=' . urlencode($this->get_value('type_id')) );
	}
}

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
echo '<html><head>';
echo '<title>Reason: Move Entities Among Sites: Step 1</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
echo '</head><body>';

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$user_id = get_user_id($current_user);
if (empty( $user_id ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason users may do that.</p></body></html>');
}

echo '<h1>Move Entities Among Sites</h1>';
echo '<h2>Step 1 of 2: Choose site and entity type</h2>';

$es = new entity_selector();
$es->add_type(id_of('site'));
$es->add_left_relationship($user_id, relationship_id_of('site_to_user'));
$es->set_order('entity.name ASC');
$sites = $es->run_one();

$es = new entity_selector();
$es->add_type(id_of('type'));
$es->add_table('ar', 'allowable_relationship');
$es->add_relation('ar.relationship_a = ' . id_of('site'));
$types = $es->run_one();

$site_options = array();
foreach( $sites AS $site )
	$site_options[$site->id()] = $site->get_value('name');
			
$type_options = array();
foreach( $types AS $type )
	$type_options[$type->id()] = $type->get_value('name');

$d = new DiscoMoveEntities;
$d->add_element('site_id', 'select', array('options' => $site_options));
$d->add_element('type_id', 'select', array('options' => $type_options));
$d->add_required('site_id');
$d->add_required('type_id');
$d->actions = array('Continue');
$d->run();

echo '</body></html>';

?>
