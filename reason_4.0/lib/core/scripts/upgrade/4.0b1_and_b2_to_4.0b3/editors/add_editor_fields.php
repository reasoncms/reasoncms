<?php
/**
 * This script updates reason beta 1 and beta 2 instances to support multiple wysiwyg html editors
 *
 * @package reason
 * @subpackage scripts
 */

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Add Editor Database Stuff</title>
</head>

<body>
<?php
include_once('reason_header.php');
reason_include_once ('classes/field_to_entity_table_class.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

force_secure_if_available();
$username = check_authentication();
$user_id = get_user_id( $username );

echo '<h2>Reason Editor Updater</h2>';
if (!isset ($_POST['verify']))
{
	echo '<p>This script creates the following:</p>';
	echo '<ul>';
	echo '<li>The HTML Editor type</li>';
	echo '<li>The html_editor table</li>';
	echo '<li>The html_editor_filename field in the html_editor table</li>';
	echo '<li>The site_to_html_editor allowable relationship</li>';
	echo '<li>The Loki 1, Loki 2, and Tiny MCE editors</li>';
	echo '</ul>';
}
	
if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run the Script'))
{
	$out = array();
	$editor_type_id = id_of('html_editor_type');
	if(empty($editor_type_id))
	{
		echo '<p>HTML Editor type doesn\'t exist</p>';
		$editor_type_id = reason_create_entity( id_of('master_admin'), id_of('type'), $user_id, 'HTML Editor', array('plural_name'=>'HTML Editors','unique_name'=>'html_editor_type','custom_content_handler'=>'html_editor.php3','new'=>'0'));
		echo '<p>HTML Editor type created</p>';
		
		$iq = 'INSERT INTO allowable_relationship (relationship_a,relationship_b,name) VALUES ('.id_of('site').','.$editor_type_id.',"owns")';
		db_query( $iq, 'Unable to add new ownership relationship' );
		$owns_id = mysql_insert_id();
		create_relationship( id_of('master_admin'), $editor_type_id, $owns_id);
		
		echo '<p>Added owns relationship</p>';
	
		// create the archive relationship
		$jq = 'INSERT INTO allowable_relationship (relationship_a,relationship_b,description,name,connections,required) 
			   VALUES ('.$editor_type_id.','.$editor_type_id.',"'.'HTML Editor Archive Relationship","'.'html_editor_type_archive","many_to_many","no")';
		db_query( $jq, 'Unable to add new archive relationship.' );
		
		echo '<p>Added archive relationship</p>';
	
		// create borrows relationship
		$kq = 'INSERT INTO allowable_relationship (relationship_a, relationship_b, description, name, connections, required) VALUES ('.id_of('site').','.$editor_type_id.',"site borrows Editor'.'", "borrows", "many_to_many","no")';
		db_query( $kq, 'Unable to add new borrow relationship.' );
		
		echo '<p>Added borrows relationship</p>';
		
		create_relationship(id_of('master_admin'),$editor_type_id,relationship_id_of('site_to_type'));
		echo 'Added editor type to the master admin site';
	}
	// create site uses editor relationship
	$rel = relationship_id_of('site_to_html_editor');
	if(empty($rel))
	{
		$kq = 'INSERT INTO allowable_relationship (relationship_a, relationship_b, description, name, connections, required) VALUES ('.id_of('site').','.$editor_type_id.',"site uses html editor'.'", "site_to_html_editor", "one_to_many","no")';
		db_query( $kq, 'Unable to add new relationship: sute uses html editor.' );
		
		echo '<p>Added site uses editor allowable relationship</p>';
	}
	else
	{
		echo '<p>Site uses editor allowable relationship already exists</p>';
	}
	$es = new entity_selector();
	$es->add_relation('entity.name = "html_editor"');
	$es->set_num(1);
	$tables = $es->run_one(id_of('content_table'));
	if(empty($tables))
	{
		echo '<p>html_editor table doesn\'t exist</p>';
		$table_id = create_reason_table('html_editor', $editor_type_id, $username);
		echo '<p>html_editor table created</p>';
	}
	else
	{
		$t = current($tables);
		$table_id = $t->id();
	}
	if(!empty($table_id))
	{
		$updater = new FieldToEntityTable('html_editor', array('html_editor_filename'=>array('db_type'=>'tinytext')));
		$updater->update_entity_table();
		$updater->report();
		
		$editor_info = array(
								'loki_1.php'=>array(
									'name'=>'Loki 1',
									'values'=>array('html_editor_filename'=>'loki_1.php')
								),
								'loki_2.php'=>array(
									'name'=>'Loki 2',
									'values'=>array('html_editor_filename'=>'loki_2.php')
								),
								'tiny_mce.php'=>array(
									'name'=>'TinyMCE',
									'values'=>array('html_editor_filename'=>'tiny_mce.php')
								),
		);
		$es = new entity_selector();
		$existing_editors = $es->run_one($editor_type_id);
		foreach($existing_editors as $editor)
		{
			if($editor->get_value('html_editor_filename') && array_key_exists($editor->get_value('html_editor_filename'),$editor_info))
			{
				echo '<p>'.$editor->get_value('name').' already exists</p>';
				unset($editor_info[$editor->get_value('html_editor_filename')]);
			}
		}
		foreach($editor_info as $editor_data)
		{
			echo '<p>Creating '.$editor_data['name'].'</p>';
			$id = reason_create_entity( id_of('master_admin'), $editor_type_id, $user_id, $editor_data['name'], $editor_data['values']);
			if(!empty($id))
			{
				echo '<p>Creating '.$editor_data['name'].' was successful</p>';
			}
		}
	}
	else
	{
		echo '<p>No table available. Some problem happened.</p>';
	}
	
}

else
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<input type="submit" name="verify" value="Run the Script">';
	echo '</form>';
}




?>
</body>
</html>
