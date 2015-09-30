<?php
/**
 * Third step of the db minization process
 *
 * This stage of the script removes the following items from this Reason instance:
 * - All entities that do not belong to a site
 * - All tables not used by a type (excluding the special tables like relationship, etc.)
 * - All fields not used by Reason
 * - All entries in cache/history/log tables
 * - All allowable relationships which do not match up with a Reason type
 * - All relationships which do not match up with a Reason entity
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * This script may take a long time, so extend the time limit to infinity
 */
set_time_limit( 0 );

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/user_functions.php');

// make sure user is authenticated, is a member of master admin, AND has the admin role.
force_secure_if_available();

$authenticated_user_netid = check_authentication();

auth_site_to_user( id_of('master_admin'), $authenticated_user_netid );

$user_id = get_user_id( $authenticated_user_netid );

if(!reason_user_has_privs( $user_id, 'minimize_db' ) )
{
	die('you must have minimize_db privileges to view this page. NOTE: For security reasons, admin users DO NOT have minimize_db privileges. If you are an admin user, you must add minimize_db privs to the admin role in this Reason instance, or set up a minimize-db-specific role and assume it.');
}

?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Minimize the Reason DB - Step 3</title>
</head>
<style type="text/css">
h2,h3 {
	display:inline;
}
</style>
<body>
<h1>Minimize this Reason Instance: Step 3</h1>
<?php

$protected_tables = array('e'=>'entity','r'=>'relationship','ar'=>'allowable_relationship','uh'=>'URL_history','ss'=>'system_status','pcl'=>'page_cache_log');
$protected_fields = array('id');
$tables_to_empty = array('URL_history','page_cache_log','system_status');

if(empty($_POST['do_it']) && empty($_POST['test_it']))
{
?>
<form method="post">
<p>This stage of the script removes the following items from this Reason instance:</p>
<ul>
<li>All entities that do not belong to a site</li>
<li>All tables not used by a type (excluding the following: <?php echo implode(', ',$protected_tables); ?>)</li>
<li>All fields not used by Reason</li>
<li>All entries in these tables: <?php echo implode(', ',$tables_to_empty); ?></li>
<li>All allowable relationships which do not match up with a Reason type</li>
<li>All relationships which do not match up with a Reason entity</li>
</ul>
<p><strong>This script is highly destructive.</strong> You should only run this script on a copy of your main Reason instance.</p>
<p>This is so destructive there is a Reason setting that expressly prohibits it, and which is true by default: PREVENT_MINIMIZATION_OF_REASON_DB.</p>
<?php


$limit_sel = '<select size="1" name="limit" id="limit">';
$limit_sel .= '<option value="10">10</option>';
$limit_sel .= '<option value="20">20</option>';
$limit_sel .= '<option value="40">40</option>';
$limit_sel .= '<option value="80">80</option>';
$limit_sel .= '<option value="160">160</option>';
$limit_sel .= '<option value="320">320</option>';
$limit_sel .= '<option value="640">640</option>';
$limit_sel .= '<option value="1280">1280</option>';
$limit_sel .= '<option value="-1" selected="selected">All</option>';
$limit_sel .= '</select>';

if(PREVENT_MINIMIZATION_OF_REASON_DB)
{
	
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>true</strong>.  This means that this script will not do anything when run. You can, however, see what this script <strong>would</strong> do by clicking the button below.</p>';
	echo '<p><label for="limit">Test delete: </label>'.$limit_sel.' unowned items per type</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
}
else
{
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>false</strong>.  This means that this instance has been set up in a way that allows this script to be run. Remember to <em>only run this script on a <strong>copy</strong> of your real Reason instance</em>.</p>';
	echo '<p><label for="limit">Delete (or test delete): </label>'.$limit_sel.' unowned items per type</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
	echo '<input type="submit" name="do_it" value="Run the script" />';
}
?>
</form>
<?php
}
else
{
	$out = array();
	$test_mode = true;
	if(!PREVENT_MINIMIZATION_OF_REASON_DB && !empty($_POST['do_it']))
	{
		$test_mode = false;
	}
	
	if(!empty($_POST['limit']))
	{
		$limit = turn_into_int($_POST['limit']);
	}
	else
	{
		$limit = -1;
	}
	
	echo '<p><a href="?">Return to form</a></p>';
	
	$es = new entity_selector();
	$all_types = $es->run_one(id_of('type'));
	
	// All entities that do not belong to a site
	
	$out[] = '<h2>Entered unowned entity deletion phase</h2>';
	
	$es->set_num($limit);
	foreach($all_types as $type_id=>$type)
	{
		$entities = $es->run_one($type_id);
		foreach($entities as $entity)
		{
			if(!$entity->has_right_relation_of_type( 'owns' ))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted: '.$entity->get_value('name').' (id: '.$entity->id().')';
				}
				else
				{
					delete_entity($entity->id());
					$out[] = 'Deleted: '.$entity->get_value('name').' (id: '.$entity->id().')';
				}
			}
		}
	}
	
	// All tables not used by a type
	
	$out[] = '<h2>Entered table deletion phase</h2>';
	$all_used_tables = $protected_tables;
	foreach($all_types as $type_id=>$type)
	{
		$es = new entity_selector();
		$es->set_num($limit);
		$es->add_right_relationship($type_id, relationship_id_of('type_to_table') );
		$tables = $es->run_one(id_of('content_table'));
		
		foreach($tables as $table)
		{
			$all_used_tables[$table->id()] = $table->get_value('name');
		}
	}
	
	$all_tables = array();
	$r = db_query( 'SHOW TABLES' );
	while($row = mysql_fetch_array($r, MYSQL_ASSOC))
	{
		$all_tables[] = current($row);
	}
	foreach($all_tables as $table)
	{
		if(!in_array($table, $all_used_tables))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted table: '.$table;
			}
			else
			{
				$r = db_query( 'DROP TABLE `'.$table.'`' );
				$out[] = 'Deleted table: '.$table;
			}
		}
	}
	
	$out[] = '<h2>Entered table entity deletion phase</h2>';
	$es = new entity_selector();
	$all_reason_tables = $es->run_one(id_of('content_table'));
	foreach($all_reason_tables as $table_id=>$table_entity)
	{
		if(!array_key_exists($table_id, $all_used_tables))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted table entity: '.$table_entity->get_value('name').' (id: '.$table_id.')';
			}
			else
			{
				delete_entity($table_id);
				$out[] = 'Deleted table entity: '.$table_entity->get_value('name').' (id: '.$table_id.')';
			}
		}
	}
	
	// All fields not used by Reason
	// This part is not quite ready for prime time
	/* $out[] = '<h2>Entered field deletion phase</h2>';
	foreach($all_used_tables as $table_id=>$table_name)
	{
		if(!in_array($table_name,$protected_tables))
		{
			$es = new entity_selector();
			$es->add_left_relationship($table_id, relationship_id_of('field_to_entity_table'));
			$field_entities = $es->run_one(id_of('field'));
			
			$all_fields = array();
			$r = db_query( 'SHOW COLUMNS FROM '.$table_name );
			while($row = mysql_fetch_array($r, MYSQL_ASSOC))
			{
				$all_fields[$row['Field']] = $row['Field'];
			}
			$orphan_fields = $all_fields;
			foreach($field_entities as $field_ent)
			{
				if(!empty($orphan_fields[$field_ent->get_value('name')]))
				{
					unset($orphan_fields[$field_ent->get_value('name')]);
				}
			}
			foreach($protected_fields as $prot_field)
			{
				if(!empty($orphan_fields[$prot_field]))
				{
					unset($orphan_fields[$prot_field]);
				}
			}
			foreach($orphan_fields as $field_name)
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted '.$table_name.'.'.$field_name;
				}
				else
				{
					// drop column
					$q = 'ALTER TABLE `'.$table_name.'` DROP `'.$field_name.'`';
					$r = db_query($q);
					$out[] = 'Deleted '.$table_name.'.'.$field_name;
				}
			}
		}
	} */
	
	// empty tables
	$out[] = '<h2>Entered table emptying phase</h2>';
	foreach($tables_to_empty as $table)
	{
		$q = 'TRUNCATE TABLE `'.$table.'`';
		if($test_mode)
		{
			$out[] = 'Would have run query "'.$q.'"';
		}
		else
		{
			$r = db_query($q);
			$out[] = 'Ran query "'.$q.'"';
		}
	}
	
	// alrel cleanup
	$out[] = '<h2>Entered Allowable relationship cleanup phase</h2>';
	$q = 'SELECT * FROM `allowable_relationship` WHERE 1=1';
	$r = db_query($q);
	while($row = mysql_fetch_array($r, MYSQL_ASSOC))
	{
		$alrels[$row['id']] = $row;
	}
	
	//pray($alrels);
	
	$checked = array();
	$sides = array('relationship_a','relationship_b');
	foreach($alrels as $id=>$alrel)
	{
		foreach($sides as $side)
		{
			if(!array_key_exists($alrel[$side],$checked))
			{
				$es = new entity_selector();
				$es->add_relation('entity.id = "'.$alrel[$side].'"');
				$es->set_num(1);
				$ents = $es->run_one(id_of('type'));
				if(empty($ents))
				{
					$checked[$alrel[$side]] = 'not_ok';
				}
				else
				{
					$checked[$alrel[$side]] = 'ok';
				}
			}
			if($checked[$alrel[$side]] == 'not_ok')
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted '.$alrel['name'].' (id: '.$id.')';
				}
				else
				{
					$q = 'DELETE FROM `allowable_relationship` WHERE `id` = '.$id.' LIMIT 1';
					$r = db_query($q);
					$out[] = 'Deleted '.$alrel['name'].' (id: '.$id.')';
				}
				unset($alrels[$id]);
			}
		}
	}
	
	$row = array();
	
	// relationship checking phase
	$out[] = '<h2>Entered relationship checking phase</h2>';
	$q = 'SELECT * FROM `relationship` WHERE `type` NOT IN ("'.implode('","',array_keys($alrels)).'")';
	$r = db_query($q);
	while($row = mysql_fetch_array($r, MYSQL_ASSOC))
	{
		if($test_mode)
		{
			$out[] = 'Would have deleted rel: '.$row['id'];
		}
		else
		{
			$q = 'DELETE FROM `relationship` WHERE `id` = '.$row['id'].' LIMIT 1';
			$dr = db_query($q);
			$out[] = 'Deleted rel: '.$row['id'];
		}
	}
	
	$out[] = '<h2>Entered replacement of last_edited_by field with root</h2>';
	$root_id = get_user_id( 'root' );
	if(!empty($root_id))
	{
		$q = 'UPDATE `entity` SET `last_edited_by` = '.$root_id.' WHERE 1=1';
		if($test_mode)
		{
			$out[] = 'Would have run query: '.$q;
		}
		else
		{
			$r = db_query($q);
			$out[] = 'Updated all entities to have root be their last_edited_by user';
		}
	}
	else
	{
		$out[] = 'Unable to replace last_edited_by field values -- no root user exists';
	}
	
	pray($out);
	
	echo '<p><a href="?">Return to form</a></p>';
	echo '<h2>Next:</h2>';
	echo '<p><a href="../db_maintenance/delete_duplicate_relationships.php">Delete duplicate Rels</a></p>';
echo '<p><a href="../db_maintenance/delete_headless_chickens.php">Delete headless chickens</a></p>';
	echo '<p><a href="../db_maintenance/delete_widowed_relationships.php">Delete widowed relationships</a></p>';
	echo '<p><a href="../db_maintenance/amputees.php">Fix amputees</a></p>';
	echo '<p><a href="minimize_4.php">Step 4</a></p>';
}
?>
</body>
</html>
