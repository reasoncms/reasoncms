<?php
/**
 * Install the classifieds module
 *
 * @package reason
 * @subpackage scripts
 *
 * @todo remove carleton-specific code at end of script
 */

/**
 * Start script
 */
?><html><head><title>Install the housing module--global installation</title></head><body>
<?php
include_once('reason_header.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/util.php');
reason_include_once('classes/field_to_entity_table_class.php');

function name_es($name) {
	$es = new entity_selector();
	$es->add_type(id_of('content_table'));
	$es->add_relation('entity.name = "'.$name.'"');
	$results = $es->run_one();
	return $results;
}
function id_of_name($name) {
	return key(name_es($name));
}
function name_exists($name) {
	return count(name_es($name))!=0;
}

function create_type($site, $type, $user, $name, $array) {
	$ret = reason_create_entity($site, $type, $user, $name, $array);
	id_of('type', false); //clear cache
	create_default_rels_for_new_type($ret, $array['unique_name']);
	return $ret;
}

$user = get_user_id(check_authentication());
if(empty($user))
{
	die('valid Reason user required');
}
if (!reason_user_has_privs( $user, 'upgrade' ))
{
	die('You must have Reason upgrade rights');
}
$admin_site = id_of('master_admin');

if (empty($_GET['go']))
	echo '<a href="?go=go">Upgrade DB with the classified type</a>';
else {
	echo 'Installing...<br/>';

	echo 'Checking for classified type... ';
	if (reason_unique_name_exists('classified_type')) {
		echo 'Classified type already exists. Proceeding.<br/>';
		$classified = id_of('classified_type');
	} else {
		echo 'Creating new classified type<br/>';
		$classified = create_type($admin_site, id_of('type'), $user, 'Classified',
			array(
				'new' => 0,
				'custom_content_handler' => 'classified.php',
				'plural_name' => 'Classifieds',
				'unique_name' => 'classified_type',
			)
		);
	}

	echo 'Checking for classified entity table... ';
	if (name_exists('classified_table')) {
		echo 'Classified entity table already exists. Proceeding.<br/>';
		$table = id_of_name('classified_table');
	} else {
		echo 'Creating new classified entity table<br/>';
		$table = create_reason_table('classified_table', 'classified_type', $user);
		if($table)
			echo 'The table classified_table was created and added to the type classified_type<br />';
		echo 'Populating the entity table<br/>';
		$fields = array(
			'location' => 'tinytext',
			'price' => 'decimal(10,2)',
			'classified_print_content' => 'text',
			'classified_date_available' => 'datetime',
			'classified_duration_days' => 'int',
			'classified_contact_email' => 'tinytext',
			'display_contact_info' => 'boolean',
		);
		foreach ($fields as $key=>$value)
			$fields[$key] = array('db_type'=>$value);
		$updater = new FieldToEntityTable('classified_table', $fields);
		$updater->update_entity_table();
		$updater->report();
	}

	echo 'Checking for classified category type... ';
	if (reason_unique_name_exists('classified_category_type')) {
		echo 'Classified category type already exists. Proceeding.<br/>';
		$classified_category = id_of('classified_category_type');
	} else {
		echo 'Creating classified category type.<br/>';
		$classified_category = create_type($admin_site, id_of('type'), $user,
			'Classified Category', array(
				'new' => 0,
				'plural_name' => 'Classified categories',
				'unique_name' => 'classified_category_type',
			)
		);
		echo 'Creating allowable relationship with classifieds<br/>';
		create_allowable_relationship($classified, $classified_category, 'classified_to_classified_category');
	}

	echo 'Putting entity tables on classified type<br/>';
	$rel = relationship_id_of('type_to_table');
	foreach(array('classified_table', 'meta', 'chunk', 'dated') as $t)
		if (name_exists($t)) create_relationship($classified, id_of_name($t), $rel);
		else echo "Missing name $t!<br/>";

	echo "Install was successful!<br/>";
}
?>
</body></html>
