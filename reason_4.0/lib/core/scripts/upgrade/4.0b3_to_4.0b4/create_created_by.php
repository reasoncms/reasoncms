<?php
/**
 * Add created_by field and attempt to figure out sensible values
 *
 * This script is part of the 4.0 beta 3 to beta 4 upgrade
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

// create_created_by.php adds the created_by field to the entity table, and tries to intelligently determine
// an accurate initial value for all existant entities. this is accomplished by looking at each entities
// archive, and consulting the last_modified value of the oldest archived entity associated with a pending,
// deleted, or live entity.
//
// author nwhite
// 12-23-2006

// try to increase limits in case user chooses a really big chunk
set_time_limit(1800);
ini_set('max_execution_time', 1800);
ini_set('mysql_connect_timeout', 1200);

$output = '';
$field_exists = false;
$root_user_id = get_user_id('root');
force_secure_if_available();

$user_netID = check_authentication();
$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

echo '<h2>Reason Entity Updater - add the created_by field</h2>';
if (!isset ($_POST['verify']))
{
        echo '<p>This script creates a new field in the entity table (if needed) called created_by which holds the userid of the person
		 who first creates an entity. After creating the field, it attempts to populate the field for all existant entities by
		 considering the archive.</p>';
		echo '<p><strong>A mature reason instance without the created_by field may require this script to be run multiple times.</strong></p>';
		echo '<p>Considering bunches of 20000 entities at a time seems to be reliable. Higher numbers could result in problems with memory';
		echo ' use or script execution time. Adjust the number as you see fit. On a copy of Carleton\'s production database, it required';
		echo ' 4 script executions examining 20000 entities each time to completely update the database. Overall this takes 5 minutes.</p>';
		echo '<p>~~ Your results may vary ~~</p>';
}

if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run'))
{
	$q = 'SHOW COLUMNS FROM entity';
	$result = db_query($q, 'could not get fields');
	while($table = mysql_fetch_assoc($result))
	{
		$fields[] = $table['Field'];
	}
	if (in_array('created_by', $fields))
	{
		$output[] = 'The created_by field already exists in the entity table';
		$field_exists = true;
	}
	else
	{
		$field_exists = create_created_by_field();
	}
}

if ($field_exists) //just created or already present
{
	$e = prep_entities($_POST['num']);
	if (count($e) > 0)
	{
		foreach ($e as $k => $v)
		{
			if ($v == 0) $v = $root_user_id; // in case of chance entity never had a last modified - use root_user_id
			$q = 'UPDATE entity SET created_by = ' . $v . ', last_modified = entity.last_modified WHERE id = ' . $k;
			$result = mysql_query($q);
		}
		$output[] = 'updated ' . count($e) . ' entities';
		echo '<p>There are additional entities that need to be processed - please run the script again.</p>';
		echo_form();
	}
	else
	{
		$output[] = 'All entities have created_by ids set';
		$output[] = '<a href="index.php">Continue Reason beta 3 to beta 4 upgrades</a>';
	}
}

else
{
	echo_form();
}


pray ($output);

function echo_form()
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<p>Number of entities to examine: <input type="text" name="num" value="20000" /></p>';
	echo '<p><input type="submit" name="verify" value="Run" /></p>';
	echo '</form>';
}

function create_created_by_field()
{
	$q = "ALTER TABLE `entity` ADD `created_by` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'";
	$result = db_query($q, 'problem creating created_by field');
	return true;
}

function populate_archive_ids()
{
	$q = 'SELECT id, relationship_a FROM allowable_relationship WHERE name LIKE "%archive%" AND relationship_a = relationship_b';
	$r = db_query( $q, 'Unable to get archive relationships.' );
	while ($row = mysql_fetch_assoc( $r ))
	{
		$a_ids[$row['relationship_a']] = $row['id'];
	}
	return $a_ids;
}

function prep_entities($limit = -1)
{
	global $a_ids;
	$a_ids = populate_archive_ids();
	$q = 'SELECT id, type, last_edited_by FROM entity WHERE state != "Archived" AND created_by = 0';
	$result = db_query($q, 'could not select entities');
	$counter = 0;
	$entities = array();
	while (($row = mysql_fetch_assoc($result)) && ($counter != $limit))
	{
		$build = build_entities($row['id'], $row['type'], $row['last_edited_by'], $entities);
		if ($build == false) $entities[$row['id']] = $row['last_edited_by'];
		$counter++;
	}
	return $entities;
}

function build_entities($e, $t, $last_edited_by, &$entities) // {{{
{
	global $a_ids;
	$cb_new = '';
	if ($rel_id = (isset($a_ids[$t])) ? $a_ids[$t] : false)
	{
		$es = new entity_selector();
		$es->add_type( $t );
		$es->add_right_relationship( $e, $rel_id );
		$es->limit_fields (array ('entity.last_edited_by'));
		$es->limit_tables (array ('entity'));
		$es->set_order( 'last_modified ASC, entity.id ASC' );
		$archived = $es->run_one(false,'Archived');

		foreach ($archived as $k => $v)
		{
			if (empty($cb_new))
			{
				$result = true;
				$cb_new = $v->get_value('last_edited_by');
				$entities[$e] = $cb_new;
			}
			$entities[$k] = $cb_new;
		}
	}
	return isset($result);
} // }}}
?>
