<?php
/**
 * Upgrade to allow categories to be sorted on a page
 *
 * This is part of the beta 5 to beta 6 upgrade
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

/**
*	9/1/2006
*
*   this script updates a reason 4 database for relationship sorting (if needed) and sets up relationship sorting for a particular allowable relationship
*
*	specifically, it does the following
*	- changes the name of the sort_order column in the relationship table to rel_sort_order (if this hasn't been done)
* 	- establishes an initial relationship sort order two related types
*	- makes the relationship sortable in the allowable relationships table
*	@author nathan white
*/

$current_user = reason_require_authentication();
$reason_user_id = get_user_id ( $current_user );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
if (!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('<h1>You must have reason upgrade privileges to run this script</h1>');
}

ini_set('max_execution_time', 1800);
ini_set('mysql_connect_timeout', 1200);
ini_set("memory_limit","256M");

//////////////////////////////////////////////
//////////////// CONFIGURATION ///////////////
//////////////////////////////////////////////

$test_mode = false; // switch to true to actually make database changes

//////////////////////////////////////////////
///////// RELATIONSHIP CONFIGURATION /////////
//////////////////////////////////////////////

// THIS IS SETUP RIGHT NOW FOR THE PAGE TO CATEGORY RELATIONSHIP

$left_side_entity_type = 'minisite_page';
$right_side_entity_type = 'category_type';
$relationship_type = 'page_to_category';
$left_side_relation_limiter = '';
$ordering = '';

//////////////////////////////////////////////
/////////////// FUNCTIONS ////////////////////
//////////////////////////////////////////////

function change_column_name($test_mode)
{
	$q = "SHOW COLUMNS FROM `relationship`";
	$result = db_query($q, 'could not access column in table relationship');
	while ($column = mysql_fetch_row($result))
	{
		$column_names[] = $column[0];
	}
	if (in_array('sort_order', $column_names))
	{
		if ($test_mode) return true;
		else
		{
			$q = "ALTER TABLE `relationship` CHANGE `sort_order` `rel_sort_order` INT( 11 ) NOT NULL DEFAULT '0'";
			db_query($q, 'could not alter table - it has probably already been altered.', false);
			return true;
		}
	}
}

function add_is_sortable_column($test_mode)
{
	$q = "SHOW COLUMNS FROM `allowable_relationship`";
	$result = db_query($q, 'could not access column in table relationship');
	while ($column = mysql_fetch_row($result))
	{
		$column_names[] = $column[0];
	}
	if (in_array('is_sortable', $column_names))
	{
		return false;
	}
	else
	{
		if ($test_mode) return true;
		$q = "ALTER TABLE `allowable_relationship` ADD `is_sortable` ENUM('yes','no') NULL DEFAULT 'no'";
		db_query($q, 'could not alter table - it has probably already been altered.', false);
		return true;
	}
}

function get_relationships_to_update($left_side_entity_type, $right_side_entity_type, $relationship_type, $left_side_relation_limiter, $ordering)
{
	$es = new entity_selector();
	$es->add_type(id_of($left_side_entity_type));
	if (!empty($left_side_relation_limiter)) $es->add_relation($left_side_relation_limiter);
	$result = $es->run_one();
	foreach ($result as $entity)
	{
		if ($entity->has_left_relation_of_type(relationship_id_of($relationship_type)))
		{
			$owns_entity = $entity->get_owner();
			$site_id = (!empty($owns_entity)) ? $owns_entity->id() : '';
			$es2 = new entity_selector($site_id);
			$es2->add_type(id_of($right_side_entity_type));
			$es2->set_sharing( 'owns,borrows' );
			$es2->add_right_relationship( $entity->id() , relationship_id_of($relationship_type));
			if (!empty($site_id)) $es2->add_field( 'ar' , 'name' , 'sharing' );
			$es2->add_field( 'relationship', 'id', 'rel_id' );
			if (!empty($ordering)) $es2->set_order( $ordering );
			//echo '<hr> '.$es2->get_one_query() . '<hr/>';
			$result2 = $es2->run_one();
			$sort_order = 1;
			if (is_array($result2))
			{
				foreach($result2 as $entity)
				{
					$update_array[$entity->get_value('rel_id')] = $sort_order;
					$sort_order++;
				}
			}
		}
	}
	if (isset($update_array) && count($update_array) > 0) return $update_array;
	else 
	{
		trigger_error('Could not find anything to update - check to make sure you have entered a valid left and right side entity, and relationship type');
		die;
	}
}

function verify_relationship($left_side_entity_type, $right_side_entity_type, $relationship_type)
{
	$pass = true;
	if (!reason_relationship_name_exists($relationship_type))
	{
		trigger_error('The relationship name ' . $relationship_type . ' does not exist!');
		$pass = false;
	}
	if (!reason_unique_name_exists($left_side_entity_type))
	{
		trigger_error('The left side type with unique name ' . $relationship_type . ' does not exist!');
		$pass = false;
	}
	if (!reason_unique_name_exists($right_side_entity_type))
	{
		trigger_error('The right side type with unique name ' . $relationship_type . ' does not exist!');
		$pass = false;
	}
	
	$r_id = relationship_id_of($relationship_type);
	$q = 'SELECT is_sortable FROM allowable_relationship WHERE id='.$r_id;
	$results = db_query($q);
	$result = mysql_fetch_assoc($results);
	if ($result['is_sortable'] == 'yes')
	{
		echo '<p>The relationship is already sortable - this script has probably been run already</p>';
		$pass = false;
	}
	return $pass;
}

function update_allowable_relationship($left_side_entity_type, $right_side_entity_type, $relationship_type, $test_mode)
{
	$relationship_a = id_of($left_side_entity_type);
	$relationship_b = id_of($right_side_entity_type);
	if ($test_mode == false)
	{
		$q = 'UPDATE allowable_relationship SET is_sortable="yes" WHERE relationship_a='.$relationship_a.' AND relationship_b='.$relationship_b.' AND name="'.$relationship_type.'"';
		db_query($q, 'The allowable relationship ' . $relationship_type . ' could not be updated. There is most likely a problem with your naming of either the leftside or rightside entities, or the relationship type');
	}
}

//////////////////////////////////////////////
/////////////// ACTION PART //////////////////
//////////////////////////////////////////////
$test_mode = (isset ($_POST['verify']) && ($_POST['verify'] == 'Run the Script' )) ? false : true;

echo '<h2>Setting up Relationship Sorting for an Allowable Relationship</h2>';
if (!isset ($_POST['verify']))
{
	echo '<p>This script is used to enable and initialize relationship sort order for the page_to_category relationship</p>';
}
echo '<h3>Relationship Configuration:</h3>';
echo '<ul>';
echo '<li>Left-side Entity is <strong>' . $left_side_entity_type . '</strong></li>';
echo '<li>Right-side Entity is <strong>' . $right_side_entity_type . '</strong></li>';
echo '<li>Allowable Relationship name is <strong>' . $relationship_type . '</strong></li>';
echo '<li>Filter Left-side Entities by <strong>' . $left_side_relation_limiter . '</strong></li>';
echo '<li>Default Order of Right-side Entities is <strong>' . $ordering . '</strong></li>';
echo '</ul><hr />';

if (isset ($_POST['verify']) && ( ($_POST['verify'] == 'Run the Script') || ($_POST['verify'] == 'Test the Script') )) 
{
	if (!verify_relationship($left_side_entity_type, $right_side_entity_type, $relationship_type))
	{
		die;
	}
	
	if (change_column_name($test_mode)) // attempt to change column name from sort_order to rel_sort_order
	{
		if ($test_mode) echo '<p>Would have updated relationship table column name: sort_order => rel_sort_order</p>';
		else echo '<p>Updated relationship table column name: sort_order => rel_sort_order</p>';
	}
	if (add_is_sortable_column($test_mode))
	{
		if ($test_mode) echo '<p>Would have added is_sortable column to the allowable relationship table</p>';
		else echo '<p>Added is_sortable column to the allowable_relationships table</p>';
	}
	$result = update_allowable_relationship($left_side_entity_type, $right_side_entity_type, $relationship_type, $test_mode);
	if ($test_mode) echo '<p>Would have set is_sortable in the allowable relationships table to "yes."</p>';
	else echo '<p>Set is_sortable in the allowable relationship table for ' . $relationship_type . ' relationship to "yes"</p>';
	$process_array = get_relationships_to_update($left_side_entity_type, $right_side_entity_type, $relationship_type, $left_side_relation_limiter, $ordering);
	$count = 0;
	if ($test_mode)
	{
		echo 'Would have updated ' . count($process_array) . ' relationships:';
	}
	else
	{
		foreach ($process_array as $k=>$v)
		{
			$q = 'UPDATE relationship SET rel_sort_order='.$v.' WHERE id='.$k;
			db_query($q, 'could not update relationship');
			$count++;
		}
		if ($count > 0) echo 'Updated ' . $count . ' relationships:';
	}
	pray ($process_array);
}

echo '<form name="doit" method="post" src="'.get_current_url().'" />';
echo '<input type="submit" name="verify" value="Run the Script">';
echo '<input type="submit" name="verify" value="Test the Script">';
echo '</form>';
?>
