<?php
/**
 * Change the name of the field event.repeat to event.recurrence
 *
 * This script will change the name of the event.repeat field to
 * event.recurrence so that Reason can be run under MySQL 5.x
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once ('reason_header.php');
include_once (CARL_UTIL_INC. '/db/db.php');
reason_include_once ('classes/entity_selector.php');
reason_include_once ('function_libraries/user_functions.php');
connectDB( REASON_DB );

$current_user = reason_require_authentication();
$cur_user_id = get_user_id ( $current_user );

if(empty($cur_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $cur_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

$es = new entity_selector();
$es->add_type(id_of('content_table'));
$es->add_relation('entity.name = "event"');
$es->set_num(1);
$tables = $es->run_one();
if(empty($tables))
{
	$msg = 'Not able to find event entity table. Not able to proceed.';
	echo $msg;
	trigger_error($msg,EMERGENCY);
	die();
}
else
{
	$event_table = current($tables);
}

$es = new entity_selector();
$es->add_type(id_of('field'));
$es->set_num(1);
$es->add_relation('entity.name = "repeat"');
$es->add_left_relationship($event_table->id(), relationship_id_of('field_to_entity_table'));
$repeat_fields = $es->run_one();
if(empty($repeat_fields))
{
	echo '<p>This upgrade script has already been run -- you do not need to run it again.</p>';
	echo '<p><a href="index.php">Return to upgrade scripts</a></p>';
	die();
}
elseif(empty($_POST['run']))
{
	echo '<p>This script will change the name of the event.repeat field to event.recurrence so that Reason can be run under MySQL 5.x</p>';
	echo '<form method="POST" target="?">';
	echo '<input type="submit" name="run" value="Run the Script" />';
	echo '</form>';
}
else
{
	echo '<p>Changing event.repeat to event.recurrence</p>';
	$repeat_field = current($repeat_fields);
	$q = 'ALTER TABLE `event` CHANGE `repeat` `recurrence` '.$repeat_field->get_value('db_type');
	$success = db_query( $q, 'Unable to change column.' );
	if(!$success)
	{
		$msg = 'Unable to rename repeat field; aborting process';
		echo $msg;
		trigger_error($msg,EMERGENCY);
		die();
	}
	echo '<p>Successfully changed the database table; will now change the Reason entity that references the table (id #'.$repeat_field->id().')</p>';
	$success = reason_update_entity($repeat_field->id(),$cur_user_id,array('name'=>'recurrence'));
	if(!$success)
	{
		$msg = 'Unable to update repeat field entity';
		echo $msg;
		trigger_error($msg,EMERGENCY);
		die();
	}
	echo '<p>Successfully updated the repeat field entity.</p>';
	echo '<p><a href="index.php">Return to upgrade scripts</a></p>';
}


?>
