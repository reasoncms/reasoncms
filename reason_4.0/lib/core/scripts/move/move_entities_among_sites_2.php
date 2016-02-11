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
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason admins may do that.</p></body></html>');
}
elseif (!reason_user_has_privs( $user_id, 'db_maintenance' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason admins who have database maintenance privs may do that.</p></body></html>');
}
if ( !empty($_REQUEST['site_id']) && !empty($_REQUEST['type_id'])  )
{
	$site_id = (integer) $_REQUEST['site_id'];
	$type_id = (integer) $_REQUEST['type_id'];
	
	$start_date = (!empty($_REQUEST['creation_date_start'])) ? $_REQUEST['creation_date_start'] : '';
	$end_date = (!empty($_REQUEST['creation_date_end'])) ? $_REQUEST['creation_date_end'] : '';
	$name = (!empty($_REQUEST['name_contains'])) ? $_REQUEST['name_contains'] : '';
	$sort = (!empty($_REQUEST['sort'])) ? $_REQUEST['sort'] : 'entity.id';	
}
else
{
	header('Location: ' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH  . 'scripts/move/move_entities_among_sites.php');
	die();
}

$user = new entity($user_id);

echo '<!DOCTYPE html>'."\n";
echo '<html><head>'."\n";
echo '<title>Reason: Move Entities Among Sites: Step 2</title>'."\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'css/reason_admin/move_entities.css" />'."\n";
echo '<script type="text/javascript" src="'.JQUERY_URL.'"></script>'."\n";
echo '<script type="text/javascript" src="'.REASON_HTTP_BASE_PATH.'js/move_entities.js"></script>'."\n";
echo '</head><body>'."\n";

echo '<h1>Move Entities Among Sites</h1>'."\n";
echo '<h2>Step 2 of 2: Choose which site owns each entity</h2>'."\n";

echo '<form method="post" action="' . REASON_HTTP_BASE_PATH . 'scripts/move/move_entities_among_sites_3.php' . '">'."\n";

// 1. Get $allowable_relationship_id where relationship_a =
// id_of('site') and relationship_b = $type_id (if there's not one,
// die)
$allowable_relationship_id = get_owns_relationship_id($type_id);

// 2. Get list of entity_bs of relationships whose entity_a = $site_id
// and whose type = $allowable_relationship_id;
$es = new entity_selector();
$es->add_type($type_id);
$es->add_right_relationship($site_id, $allowable_relationship_id);
if ($start_date) $es->add_relation('entity.creation_date >= "'.mysql_real_escape_string($start_date).'"');
if ($end_date) $es->add_relation('entity.creation_date <= "'.mysql_real_escape_string($end_date).'"');
if ($name) $es->add_relation('entity.name LIKE "%'.mysql_real_escape_string($name).'%"');
$es->set_order(mysql_real_escape_string($sort).' ASC');
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
$options_chunk = '';
foreach ( $sites as $site )
{
	$options_chunk .=  ( '<option value="' . $site->id() . '" ' .
				  ($site->id() == $site_id ? ' selected="selected" ' : ' ') .
				  '>' .
				  reason_htmlspecialchars(strip_tags($site->get_value('name')), ENT_QUOTES) .
				  '</option>' );
}


echo '<table id="entity_mover" width="100%">'."\n";
echo '<tr><th><a href="'.carl_make_link(array('sort'=>'entity.id'), '', '', false).'">Id</a></th>';
echo '<th><a href="'.carl_make_link(array('sort'=>'entity.name'), '', '', false).'">Name</a></th>';
echo '<th><a href="'.carl_make_link(array('sort'=>'entity.creation_date'), '', '', false).'">Created</a></th>';
echo '<th>Belongs to<sup><span class="small">1</span></sup> <select name="set_all">'.$options_chunk.'</select></th></tr>'."\n";
foreach ( $entity_bs as $entity_b )
{
	$select = '<select name="new_site_ids[' . $entity_b->id() . ']">';
	$select .= $options_chunk;
	$select .= '</select>';


	echo '<tr>';
	echo '<td class="id">' . $entity_b->id() . '</td>';
	echo '<td class="name">' . $entity_b->get_display_name() . '</td>';
	echo '<td class="created">' . $entity_b->get_value('creation_date') . '</td>';
	echo '<td class="site">' . ( $entity_b->user_can_edit_relationship($allowable_relationship_id,$user,'left') ? $select : 'Locked' ) . '</td>';

	echo '</tr>'."\n";
}
echo '</table>'."\n";

echo '<input type="hidden" name="type_id" value="' . $type_id . '" />';
echo '<input type="hidden" name="old_site_id" value="' . $site_id . '" />';
echo '<input type="hidden" name="allowable_relationship_id" value="' . $allowable_relationship_id . '" />';

echo '<p><input type="submit" value="Finish" /></p>'."\n";
echo '</form>';
echo '<p class="small"><sup>1</sup>Lists sites that (a) you own and (b) are associated with these entities&apos; type.</p>'."\n";

echo '</body></html>';

?>
