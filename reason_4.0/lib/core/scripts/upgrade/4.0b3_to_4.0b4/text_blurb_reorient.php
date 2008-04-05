<?php
/**
 * Change the direction of the page-text blurb relationship
 *
 * This script is part of the beta 3 to beta 4 upgrade
 *
 * The relationship direction has been changed so that blurbs can be shared among 
 * sites and placed on various pages, as well as making it possible for them to be 
 * sorted on those relationships
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

// try to increase limits in case user chooses a really big chunk
set_time_limit(1800);
ini_set('max_execution_time', 1800);
ini_set('mysql_connect_timeout', 1200);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Reason Upgrade: Reorient Text Blurb Relationships</title>
</head>

<body>
<?php

force_secure_if_available();

$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

echo '<h2>Reason: Reorient Text Blurb Relationship</h2>';
if ( (!isset ($_POST['verify'])) && (!isset ($_POST['verify2'])))
{
        echo '<p>This script changes the direction of the text_blurb_to_minisite_page relationship and changes all the relationships to match.</p>';
		echo_form();
}

if (isset ($_POST['verify']))
{
	$test_mode = true;
	if($_POST['verify'] == 'Run')
		$test_mode = false;
	do_action($test_mode);
}

if (isset ($_POST['verify2']))
{
	$test_mode = true;
	if($_POST['verify2'] == 'Run')
		$test_mode = false;
	do_action2($test_mode);
}

function echo_form()
{
	echo '<form name="doit" method="post" action="'.get_current_url().'" />';
	echo '<input type="submit" name="verify" value="Run" />';
	echo '<input type="submit" name="verify" value="Test" />';
	echo '</form>';
}

function echo_form2()
{
	echo '<p>This phase of the script will <strong>RESET ALL EXISTING RELATIONSHIP SORT ORDER DATA FOR THE minisite_page_to_text_blurb ALLOWABLE RELATIONSHIP</strong> - so do not run it unless ';
	echo 'this is what you actually intend to do. If you have never run this script before on this reason instance, you should run it in order to preserve the previous sort order of text blurbs on pages.</p>';
	echo '<form name="doit" method="post" action="'.get_current_url().'" />';
	echo '<input type="submit" name="verify2" value="Run" />';
	echo '<input type="submit" name="verify2" value="Test" />';
	echo '</form>';
}

function do_action2( $test_mode = true)
{
	$left_side_entity_type = 'minisite_page';
	$right_side_entity_type = 'text_blurb';
	$relationship_type = 'minisite_page_to_text_blurb';
	$left_side_relation_limiter = '';
	$ordering = 'sortable.sort_order ASC';
	$rel_sort_order_array = get_relationships_to_update($left_side_entity_type, $right_side_entity_type, $relationship_type, $left_side_relation_limiter, $ordering);
	$count = set_rel_sort_order($rel_sort_order_array, $test_mode);
	if ($test_mode) echo '<p>Would establish sort order for ' . $count . ' relationships</p>';
	else echo '<p>Established sort order for ' . $count . ' relationships</p>';
	pray ($rel_sort_order_array);
}

function do_action($test_mode = true)
{
	$rel_id = relationship_id_of('text_blurb_to_minisite_page');
	if(empty($rel_id))
	{
		echo '<p>No rel ID for text_blurb_to_minisite_page found.  This script may already have been run. If you have not run phase 2, you should do so now.</p>';
		echo_form2();
	}
	else
	{
		echo '<p>Rel ID for text_blurb_to_minisite_page: '.$rel_id.'</p>';
		$rels = grab_rels($rel_id);
		if(empty($rels))
		{
			echo '<p>No rels found</p>';
		}
		else
		{
			echo '<p>'.count($rels).' rels found</p>';
			if (!$test_mode) echo_form2();
			echo '<ol>';
			foreach($rels as $rel)
			{
				if($test_mode)
				{
					echo '<li>Would have swapped rel id '.$rel['id'].'</li>';
				}
				else
				{
					if(swap_rel_direction($rel))
					{
						echo '<li>Swapped rel id '.$rel['id'].'</li>';
					}
					else
					{
						echo '<li>Problem swapping rel id '.$rel['id'].'</li>';
					}
				}
			}
			echo '</ol>';
		}
		if($test_mode)
		{
			echo '<p>Would have updated allowable rel</p>';
		}
		else
		{
			if(update_allowable_rel($rel_id, $test_mode))
			{
				echo '<p>Updated allowable rel. You should probably now run phase 2 of the script.</p>';
				echo_form2();
			}
			else
			{
				echo '<p>Problem updating allowable rel</p>';
			}
		}
	}
}
function grab_rels($rel_type_id)
{
		$dbs = new DBSelector();
		$dbs->add_table( 'rel', $table = 'relationship' );
		$dbs->add_relation( '`type` = "'.$rel_type_id.'"');
		return $dbs->run();
}
function swap_rel_direction($rel)
{
	if($GLOBALS['sqler']->update_one( 'relationship', array('entity_a'=>$rel['entity_b'],'entity_b'=>$rel['entity_a']), $rel['id'] ))
		return true;
	else
		return false;
}
function update_allowable_rel($rel_type_id, $test_mode)
{
	if($GLOBALS['sqler']->update_one( 'allowable_relationship', array('name '=>'minisite_page_to_text_blurb','directionality'=>'bidirectional','is_sortable'=>'yes','display_name'=>'Place Blurbs','display_name_reverse_direction'=>'Place on Pages','description_reverse_direction'=>'On Pages','relationship_a'=>id_of('minisite_page'),'relationship_b'=>id_of('text_blurb')), $rel_type_id ))
		return true;
	else
		return false;
}

function get_relationships_to_update($left_side_entity_type, $right_side_entity_type, $relationship_type, $left_side_relation_limiter, $ordering)
{
	ini_set("memory_limit","256M"); // might be more than it needs but should be sufficient
	$es = new entity_selector();
	$es->add_type(id_of($left_side_entity_type));
	if (!empty($left_side_relation_limiter)) $es->add_relation($left_side_relation_limiter);
	$result = $es->run_one();
	foreach ($result as $entity)
	{
		if ($entity->has_left_relation_of_type(relationship_id_of($relationship_type)))
		{
			$owns_entity = $entity->get_owner();
			$es2 = new entity_selector($owns_entity->id());
			$es2->add_type(id_of($right_side_entity_type));
			$es2->set_sharing( 'owns,borrows' );
			$es2->add_right_relationship( $entity->id() , relationship_id_of($relationship_type));
			$es2->add_field( 'ar' , 'name' , 'sharing' );
			$es2->add_field( 'relationship', 'id', 'rel_id' );
			if (!empty($ordering)) $es2->set_order( $ordering );
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

function set_rel_sort_order($rel_sort_order_array, $test_mode)
{
	$count = 0;
	foreach ($rel_sort_order_array as $k=>$v)
	{
		if (!$test_mode)
		{
			$q = 'UPDATE relationship SET rel_sort_order='.$v.' WHERE id='.$k;
			db_query($q, 'could not update relationship');
		}
		$count++;
	}
	return $count;
}

?>
</body>
</html>
