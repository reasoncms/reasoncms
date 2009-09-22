<?php
/**
 * This script updates reason beta 1 and beta 2 instances to use a more flexible entity-based audience system
 *
 * @package reason
 * @subpackage scripts
 */
set_time_limit( 0 );
include_once('reason_header.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/entity_selector.php');
connectDB(REASON_DB);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Fix Audiences</title>
</head>
<?php
	$audiences = array(
						'students'=>array(
											'name'=>'Students',
											'unique_name'=>'students_audience',
											'directory_service_value'=>'student',
										),
						'faculty'=>array(
											'name'=>'Faculty',
											'unique_name'=>'faculty_audience',
											'directory_service_value'=>'faculty',
										),
						'staff'=>array(
											'name'=>'Staff',
											'unique_name'=>'staff_audience',
											'directory_service_value'=>'staff',
										),
						'alumni'=>array(
											'name'=>'Alumni',
											'unique_name'=>'alumni_audience',
											'directory_service_value'=>'alum',
										),
						'public'=>array(
											'name'=>'General Public',
											'unique_name'=>'public_audience',
											'directory_service_value'=>'public',
										),
						'families'=>array(
											'name'=>'Families',
											'unique_name'=>'families_audience',
											'directory_service_value'=>'family',
										),
						'prospective_students'=>array(
												'name'=>'Prospective Students',
												'unique_name'=>'prospective_students_audience',
												'directory_service_value'=>'prospect',
												'directory_service'=>'ldap_carleton_prospects',
											),
						'new_students'=>array(
												'name'=>'New Students',
												'unique_name'=>'new_students_audience',
												'directory_service_value'=>'new_student',
												'directory_service'=>'ldap_carleton,ldap_carleton_prospects',
												'audience_filter'=>'(|(ds_affiliation=student)(&(ds_affiliation=prospect)(|(carlProspectStatus=Deferred*)(carlProspectStatus=Deposit*))))',
											),
					);
	$out = array();
?>
<body>
<?php
if(empty($_REQUEST['do_it']) && empty($_REQUEST['step_2']))
{
	echo '<form>';
	echo 'Number to run: <input type="text" name="num" value="100"/><br />';
	echo '<input type="submit" name="do_it" value="Do it" />';
	echo '</form>';
}
elseif(!empty($_REQUEST['do_it']))
{
	$out[] = 'Starting';
	if(!empty($_REQUEST['num']))
	{
		$num = turn_into_int($_REQUEST['num']);
	}
	else
	{
		$num = 100;
	}
	$audience_type_id = id_of('audience_type');
	if(empty($audience_type_id))
	{
		$out[] = 'Audience type doesn\'t exist';
		$audience_type_id = reason_create_entity( id_of('master_admin'), id_of('type'), get_user_id( 'root' ), 'Audience', array('plural_name'=>'Audiences','unique_name'=>'audience_type'));
		$out[] = 'Audience type created';

		$iq = 'INSERT INTO allowable_relationship (relationship_a,relationship_b,name) VALUES ('.id_of('site').','.$audience_type_id.',"owns")';
		db_query( $iq, 'Unable to add new ownership relationship' );
		$owns_id = mysql_insert_id();
		create_relationship( id_of('master_admin'), $audience_type_id, $owns_id);
		
		$out[] = 'Added owns relationship';
	
		// create the archive relationship
		$jq = 'INSERT INTO allowable_relationship (relationship_a,relationship_b,description,name,connections,required) VALUES ('.$audience_type_id.','.$audience_type_id.',"'.' Audience Archive Relationship","'.'audience_type_archive","many_to_many","no")';
		db_query( $jq, 'Unable to add new archive relationship.' );
		
		$out[] = 'Added archive relationship';
	
		// create borrows relationship
		$kq = 'INSERT INTO allowable_relationship (relationship_a, relationship_b, description, name, connections, required) VALUES ('.id_of('site').','.$audience_type_id.',"site borrows Audience'.'", "borrows", "many_to_many","no")';
		db_query( $kq, 'Unable to add new borrow relationship.' );
		
		$out[] = 'Added borrows relationship';
		
		// Add the sortable table to the audience type
		// haven't done this yet
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "sortable"');
		$es->set_num(1);
		$tables = $es->run_one();
		if(!empty($tables))
		{
			$table = current($tables);
			create_relationship($audience_type_id,$table->id(),relationship_id_of('type_to_table'));
			$out[] = 'Added the sortable table to the audience type';
		}
		else
		{
			$out[] = 'Couldn\'t find the sortable table, so couldn\'t add it to the audience type';
		}
		
		// add the audience type to the master admin site
		// haven't done this yet.
		
		create_relationship(id_of('master_admin'),$audience_type_id,relationship_id_of('site_to_type'));
		$out[] = 'Added audience type to the master admin site';
		
	}
	
	$es = new entity_selector();
	$es->add_relation('entity.name = "audience_integration"');
	$es->set_num(1);
	$tables = $es->run_one(id_of('content_table'));
	if(empty($tables))
	{
		$out[] = "no content table entity exists";
		$table_id = create_reason_table('audience_integration', $audience_type_id, 'root');
		if(!empty($table_id))
		{
			$out[] = 'The table audience_integration was created and added to the type audience_type';
			reason_include_once('classes/field_to_entity_table_class.php');
			reason_include_once('classes/amputee_fixer.php');
			$fields = array('directory_service_value' => array('db_type' => 'tinytext'),
							'directory_service' => array('db_type' => 'tinytext'),
							'audience_filter' => array('db_type' => 'tinytext'),
			);
			$updater = new FieldToEntityTable('audience_integration', $fields);
			$updater->update_entity_table();
			$updater->report();
			$fixer = new AmputeeFixer();
			$fixer->fix_amputees($audience_type_id);
			$fixer->generate_report();
		}
	}
	
	$audience_ids = array();
	foreach($audiences as $audience=>$audience_data)
	{
		$out[] = 'Looking at audience: '.$audience;
		if(!id_of($audience.'_audience'))
		{
			$out[] = $audience.' entity does not exist yet';
			$audience_ids[$audience] = reason_create_entity( id_of('master_admin'), $audience_type_id, get_user_id( 'root' ), $audience_data['name'], $audience_data);
			$out[] = 'Created '.$audience.' entity';
		}
		else
		{
			$out[] = $audience.' exists';
			reason_update_entity( id_of($audience.'_audience'), get_user_id('root'), $audience_data);
			$audience_ids[$audience] = id_of($audience.'_audience');
			$out[] = 'Updated '.$audience.' entity';
		}
	}
	
	// figure out which types use the audience table
	$types = get_types_with_audience_table();
	
	
	// create an allowable relationship for each one
	foreach($types as $type)
	{
		$out[] = $type->get_value('name').' uses the audience table';
		$rel_name = str_replace(' ','_',strtolower($type->get_value('name'))).'_to_audience';
		$alrel_id = relationship_id_of($rel_name);
		if(empty($alrel_id))
		{
			$required = ($type->get_value('name') == 'Group') ? 'no' : 'yes';
			$out[] = $rel_name.' relationship does not exist yet';
			$q = 'INSERT INTO allowable_relationship (relationship_a,relationship_b,name,connections,required,display_name) VALUES ('.$type->id().','.$audience_type_id.',"'.$rel_name.'","many_to_many","'.$required.'","Audiences")';
			db_query( $q, 'Unable to add new relationship' );
			$alrel_id = mysql_insert_id();
			$out[] = $rel_name.' relationship created';
		}
		
		$out[] = 'Grabbing '.$num.' '.$type->get_value('plural_name');
		$es = new entity_selector();
		$es->add_type($type->id());
		$es->set_num($num);
		$items = $es->run_one();
		$pending_items = $es->run_one($type->id(),'Pending');
		$deleted_items = $es->run_one($type->id(),'Deleted');
		
		if(!empty($pending_items))
		{
			$items += $pending_items;
		}
		if(!empty($deleted_items))
		{
			$items += $deleted_items;
		}
		
		// go through all the entities of those types and create relationships to duplicate the data in the audience fields
		foreach($items as $item)
		{
			$out[] = 'inspecting '.$item->get_value('name');
			if(!$item->has_relation_of_type($rel_name))
			{
				foreach($audiences as $audience=>$audience_data)
				{
					if($item->get_value($audience) == 'true')
					{
						create_relationship($item->id(),$audience_ids[$audience],$alrel_id);
						$out[] = 'Created rel between '.$item->get_value('name').' and '.$audience;
						$did_creation = true;
					}
				}
			}
		}
	}
	
	pray($out);
	
	if (isset($did_creation) && ($did_creation == true))
	{
		echo '<p>The script needs to run step one again. Please click submit to continue.</p>';
		echo '<form>';
		echo 'Number to run: <input type="text" name="num" value="100"/><br />';
		echo '<input type="submit" name="do_it" value="Do it" />';
		echo '</form>';
	}
	else
	{
		//echo '<p>Now run svn update on your root.</p>';
		echo '<p>Click submit to run step 2, which will delete the audience fields.</p>';
		echo '<form>';
		echo '<input type="submit" name="step_2" value="Next Step" />';
		echo '</form>';
	}
	
	// update the code to add the audience stuff as disco elements in the content managers
	// update the code to pay attention to the rels on the front end rather than to the fields
	
	
	
}
elseif(!empty($_REQUEST['step_2']))
{
	$out = array();
	
	// remove the audience table from those types
	
	$q = 'SELECT * FROM `relationship` WHERE `entity_b` = '.get_id_of_audience_table().' AND `type` = '.relationship_id_of('type_to_table');
	$r = db_query($q);
	$rels = array();
	while( $row = mysql_fetch_array( $r ))
	{
		$rels[ $row[ 'id' ] ] = $row;
	}
	$deleted_rels = false;
	foreach($rels as $id=>$rel)
	{
		$type = new entity($rel['entity_a']);
		delete_relationship($id);
		$out[] = 'Removed audience table from '.$type->get_value('name');
		$deleted_rels = true;
	}
	if(!$deleted_rels)
	{
		$out[] = 'Audience table is not in any types';
	}
	pray($out);

}

function get_types_with_audience_table()
{
	$es = new entity_selector();
	$es->add_type(id_of('type'));
	$es->add_left_relationship(get_id_of_audience_table(), relationship_id_of('type_to_entity_table'));
	return $es->run_one();
}

function get_id_of_audience_table()
{
	static $id;
	if(empty($id))
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "audience"');
		$es->set_num(1);
		$aud_tables = $es->run_one();
		$aud_table = current($aud_tables);
		if (!empty($aud_table))
		{
			$id = $aud_table->id();
		}
		else
		{
			'<p>There is no audience entity table, which means this script has already probably been run</p>';
			die;
		}
	}
	return $id;
}
	

?>
</body>
</html>


