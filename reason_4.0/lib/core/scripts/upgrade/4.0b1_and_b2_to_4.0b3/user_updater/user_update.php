<?php
/**
 * This script updates the reason database to that Reason can be used as a primary authentication service
 *
 * This script is part of the beta1/2 to beta 3 upgrade package
 *
 * @package reason
 * @subpackage scripts
 */
include ('reason_header.php');
reason_include_once ('classes/field_to_entity_table_class.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
//reason_include_once('function_libraries/course_utilities.php');

force_secure_if_available();
$user_netID = check_authentication();
$user_id = get_user_id($user_netID);
if(empty($user_id))
{
	die('You must be a valid Reason user to run this script');
}
if (!reason_user_has_privs( $user_id, 'upgrade' ) )
{
	die('Sorry. You do not have permission to run this script. Only Reason users who have upgrade privileges may do that.');
}

echo '<h2>Reason User Updater</h2>';
if (!isset ($_POST['verify']))
{
	echo '<p>This script creates a number of fields in the user entity table, and sets the user type to use the user content previewer.';
	echo 'Running this script changes the reason database so that the Reason user type can be used for authentication purposes.</p>';
}
	
if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run the Script'))
{
	// Use the field_to_entity_table_class to add needed fields to 'user' entity table
	$fields = array('user_surname' => array('db_type' => 'tinytext'),
					'user_given_name' => array('db_type' => 'tinytext'),
					'user_email' => array('db_type' => 'tinytext'),
					'user_phone' => array('db_type' => 'tinytext'),
					'user_password_hash' => array('db_type' => 'tinytext'),
					'user_authoritative_source' => array('db_type' => "enum('reason','external')"));
	
	$updater = new FieldToEntityTable('user', $fields);
	$updater->update_entity_table();
	$updater->report();
	
	// Set the custom_previewer value for the User type to User
	$user_type_entity = new entity(id_of('user'));
	$previewer = $user_type_entity->get_value('custom_previewer');
	if ($previewer != "user.php")
	{
		$update_array = array ('type' => array('custom_previewer' => 'user.php'));
		$update = update_entity($user_type_entity->id(), $user_id, $update_array, false);
		if ($update) echo '<h2>Updated user content previewer to user.php</h2>';
	}
	else echo '<h3>User content previewer already set to user.php</h3>';
	
	$group_fields = array(	'ldap_group_filter' => array('db_type' => 'text'),
							'ldap_group_member_fields' => array('db_type'=>'tinytext'),
							'group_has_members' => array('db_type'=>'enum(\'true\',\'false\')'),
						);
	$updater = new FieldToEntityTable('user_group', $group_fields);
	$updater->update_entity_table();
	$updater->report();
	
	// check to see if field is still in db
	$results = db_query( 'DESCRIBE user_group' );
	$field_exists = false;
	while($row = mysql_fetch_array($results))
	{
		if($row['Field'] == 'course_identifier_strings')
		{
			$field_exists = true;
			break;
		}
	}
	$ok_to_delete_field = true;
	if($field_exists)
	{
		$es = new entity_selector();
		$es->add_type(id_of('group_type'));
		$es->add_relation('user_group.course_identifier_strings != ""');
		//$es->add_relation('user_group.course_identifier_strings NOT NULL');
		//$es->add_relation('(user_group.ldap_group_filter = "" OR user_group.ldap_group_filter = NULL)');
		$groups = $es->run_one();
		$member_fields = 'ds_member,ds_owner';
		echo 'There are '.count($groups).' groups with course identifier strings<br />';
		// for carleton version
		//foreach($groups as $group)
		//{
		//	echo 'Looking at '.$group->get_value('name').' (ID: '.$group->id().')<br />';
		//	$filter = get_course_ldap_representation($group->get_value('course_identifier_strings'));
		//	if(!empty($filter))
		//	{
		//		reason_update_entity($group->id(),$user_id,array('ldap_group_filter'=>$filter,'ldap_group_member_fields'=>$member_fields),false);
		//		echo 'Updated '.$group->get_value('name').' (ID: '.$group->id().')<br />';
		//	}
		//	else
		//	{
		//		echo 'Was not able to generate ldap filter for '.$group->get_value('course_identifier_strings').'<br />';
		//		$ok_to_delete_field = false;
		//	}
		//}
		if($ok_to_delete_field)
		{
			echo 'Finding course_identifier_strings field...<br />';
			$es = new entity_selector();
			$es->add_type(id_of('field'));
			$es->add_relation('name = "course_identifier_strings"');
			$es->set_num(1);
			$fields = $es->run_one();
			if(!empty($fields))
			{
				$field = current($fields);
				reason_expunge_entity($field->id(), $user_id);
				echo 'Deleted course_identifier_strings field<br />';
			}
			else
			{
				echo 'Couldn\'t find course_identifier_strings field -- possibly already deleted<br />';
			}
			
			//check for field existence again
			$results = db_query( 'DESCRIBE user_group' );
			$field_exists = false;
			while($row = mysql_fetch_array($results))
			{
				if($row['Field'] == 'course_identifier_strings')
				{
					$field_exists = true;
					break;
				}
			}
			if($field_exists)
			{
				echo 'course_identifier_strings field still exista in DB<br />';
				$q = 'ALTER TABLE user_group DROP course_identifier_strings';
				db_query( $q, 'Unable to drop field from table.' );
				echo 'course_identifier_strings field dropped<br />';
			}
		}
		else
		{
			echo 'Was not able to delete course_identifier_strings field, as at least one group was unable to be updated<br />';
		}
	}
	else
	{
		echo 'course_identifier_strings field does not exist in DB<br />';
	}
	
	$rel_id = relationship_id_of('user_to_audience');
	if(empty($rel_id))
	{
		echo 'user_to_audience allowable relationship does not exist<br />';
		$kq = 'INSERT INTO allowable_relationship (relationship_a, relationship_b, description, name, connections, required, is_sortable, directionality) VALUES ('.id_of('user').','.id_of('audience_type').',"user is a member of audience'.'", "user_to_audience", "many_to_many","no","yes","bidirectional")';
		db_query( $kq, 'Unable to add new relationship: user_to_audience.' );
		
		echo 'created user_to_audience allowable relationship.<br />';
	}
	else
	{
		echo 'user_to_audience allowable relationship already exists<br />';
	}
	
	// change the group 
	//$group_type = new entity(id_of('group_type'));
	//if($group_type->get_value('custom_content_handler') != 'group_carleton.php')
	//{
	//	echo 'Group type uses generic group content manager<br />';
		//reason_update_entity($group_type->id(),$user_id,array('custom_content_handler'=>'group_carleton.php'));
		//echo 'Updated group type to use Carleton content manager<br />';
	//}
	//else
	//{
		//echo 'Group type already uses group_carleton.php as content manager<br />';
	//}
	
	// change the facstaff type 
	//$facstaff_type = new entity(id_of('faculty_staff'));
	//$values = array();
	//if($facstaff_type->get_value('custom_content_handler') != 'faculty_staff_carleton.php3')
	//{
	//	echo 'Faculty/Staff type uses generic content manager: '.$facstaff_type->get_value('custom_content_handler').'<br />';
	//	$values['custom_content_handler']='faculty_staff_carleton.php3';
	//}
	//if($facstaff_type->get_value('custom_previewer') != 'faculty_staff_carleton.php')
	//{
	//	echo 'Faculty/Staff type uses generic content previewer<br />';
	//	$values['custom_previewer']='faculty_staff_carleton.php';
	//}
	//if(!empty($values))
	//{
	//	reason_update_entity($facstaff_type->id(),$user_id,$values);
	//	echo 'Updated Faculty/Staff type with these values:';
	//	pray($values);
	//}
	//else
	//{
	//	echo 'Faculty/Staff type has appropriate content manager and previewer.<br />';
	//}
	
	// change the event type 
	//$event_type = new entity(id_of('event_type'));
	//if($event_type->get_value('custom_content_handler') != 'event_carleton.php3')
	//{
	//	echo 'Event type uses core content manager<br />';
	//	reason_update_entity($event_type->id(),$user_id,array('custom_content_handler'=>'event_carleton.php3'));
	//	echo 'Event type updated.<br />';
	//}
	//else
	//{
	//	echo 'Event type already uses appropriate content manager.<br />';
	//}
	
	// change the site type 
	//$site_type = new entity(id_of('site'));
	//if($site_type->get_value('custom_content_handler') != 'site_carleton.php3')
	//{
	//	echo 'Site type uses core content manager<br />';
	//	reason_update_entity($site_type->id(),$user_id,array('custom_content_handler'=>'site_carleton.php3'));
	//	echo 'Site type updated.<br />';
	//}
	//else
	//{
	//	echo 'Site type already uses appropriate content manager.<br />';
	//}
	echo '<h2>The script has completed</h2>';
}

else
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<input type="submit" name="verify" value="Run the Script">';
	echo '</form>';
}

?>

