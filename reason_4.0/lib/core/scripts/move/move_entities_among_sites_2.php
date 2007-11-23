<?php
/**
 * Move entities from one site to another -- step 2
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once( DISCO_INC .'disco_db.php');
reason_include_once( 'classes/entity_selector.php');

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$user_id = get_user_id($current_user);
if (empty( $user_id ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason users may do that.</p></body></html>');
}
if ( !empty($_REQUEST['site_id']) && !empty($_REQUEST['type_id'])  )
{
	$site_id = $_REQUEST['site_id'];
	$type_id = $_REQUEST['type_id'];
}
else
{
	header('Location: ' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH  . 'scripts/move/move_entities_among_sites.php');
}

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
echo '<html><head>';
echo '<title>Reason: Move Entities Among Sites: Step 2</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
echo '</head><body>';

echo '<h1>Move Entities Among Sites</h1>';
echo '<h2>Step 2 of 2: Choose which site owns each entity</h2>';

echo '<form method="post" action="' . REASON_HTTP_BASE_PATH . 'scripts/move/move_entities_among_sites_3.php' . '">';

// 1. Get $allowable_relationship_id where relationship_a =
// id_of('site') and relationship_b = $type_id (if there's not one,
// die)
$dbs = new DBSelector();
$dbs->add_table('allowable_relationship');
$dbs->add_relation('relationship_a = "' . id_of('site') . '"');
$dbs->add_relation('relationship_b = "' . addslashes($type_id) . '"');
$dbs->add_relation('name = "owns"');
$dbs->set_num(1);
$allowable_relationships = $dbs->run();
$allowable_relationship_id = $allowable_relationships[0]['id'];

// 2. Get list of entity_bs of relationships whose entity_a = $site_id
// and whose type = $allowable_relationship_id;
$es = new entity_selector();
$es->add_type($type_id);
$es->add_right_relationship($site_id, $allowable_relationship_id);
$entity_bs = $es->run_one();

if ( count($entity_bs) < 1 )
{
	die('<p>Sorry, but it doesn&apos;t look as though the site you selected owns any entities of the type you selected. ' .
		'Please <a href="' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'scripts/move/move_entities_among_sites.php">' .
		'try again</a>.</p>');
}

// 3. Foreach entity_b, output row with these cells:
//      - entity_b->id
//      - entity_b->name
//      - a select box, containing names->ids of sites the user owns,
//        of which the name->id with id of $site_id is selected
$es = new entity_selector();
$es->add_type(id_of('site'));
//$es->add_table('user');
$es->add_left_relationship($user_id, relationship_id_of('site_to_user'));
$es->add_left_relationship($type_id, relationship_id_of('site_to_type'));
$es->set_order('entity.name ASC');
$sites = $es->run_one();

echo <<<TELOS
<style type="text/css">
table {
	border-style:none;
	border-collapse:collapse;
}
th {
	background-color:grey;
	color:white;
	padding:.5ex;
}
td {
	padding:.5ex;
}
tr {
	border:1px solid grey;
}
.small {
	font-size:75%;
}
</style>
TELOS;
echo '<table width="100%">';
echo '<tr><th align="left">Id</th><th align="left">Name</th><th align="left">Belongs to<sup><span class="small">1</span></sup></th></tr>';
foreach ( $entity_bs as $entity_b )
{
	$select = '<select name="new_site_ids[' . $entity_b->id() . ']">';
	foreach ( $sites as $site )
	{
		$select .=  ( '<option value="' . $site->id() . '" ' .
					  ($site->id() == $site_id ? ' selected="selected" ' : ' ') .
					  '>' .
					  htmlentities($site->get_value('name')) .
					  '</option>' );
	}
	$select .= '</select>';


	echo '<tr>';
	echo '<td>' . $entity_b->id() . '</td>';
	echo '<td>' . $entity_b->get_display_name() . '</td>';
	echo '<td>' . $select . '</td>';
	echo '</tr>';
}
echo '</table>';

echo '<input type="hidden" name="old_site_id" value="' . $site_id . '" />';
echo '<input type="hidden" name="allowable_relationship_id" value="' . $allowable_relationship_id . '" />';

echo '<p><input type="submit" value="Finish" /></p>';
echo '</form>';
echo '<p class="small"><sup>1</sup>Lists sites that (a) you own and (b) are associated with these entities&apos; type.</p>';

echo '</body></html>';

?>
