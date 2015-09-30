<?php
/**
 * Kills and/or modifies cruft in the database
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Kill Cruft</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class killCruft
{
	var $mode;
	var $reason_user_id;
		
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->remove_site_to_minisite_template_allowable_relationship();
		$this->remove_allowable_relationships_with_missing_types();
		$this->remove_orphaned_relationships();
		$this->update_whats_new_in_reason_blurb();
		$this->remove_sortable_table_from_blurbs();
		$this->update_type_type_finish_actions();
	}
	
	function remove_site_to_minisite_template_allowable_relationship()
	{
		echo'<hr/>';
		if (reason_relationship_name_exists('site_to_minisite_template'))
		{
			$rel_id = relationship_id_of('site_to_minisite_template', false);
			$q = 'DELETE from allowable_relationship WHERE id='.$rel_id;
			if ($rel_id && ($this->mode == 'run') )
			{
				db_query($q);
				echo '<p>Deleted site_to_minisite_template relationship using this query:</p>';
				echo '<p>'.$q.'</p>';
			}
			elseif ($rel_id && ($this->mode == 'test'))
			{
				echo '<p>Would delete site_to_minisite_template relationship using this query:</p>';
				echo '<p>'.$q.'</p>';
			}
		}
		else
		{
			echo '<p>The relationship name site_to_minisite_template does not exist in your database</p>';
		}
	}
	
	function remove_allowable_relationships_with_missing_types()
	{
		echo '<hr/>';
		$ids = get_allowable_relationships_with_missing_types();
		if ($this->mode == 'run')
		{
			if (count($ids) > 0)
			{
				$deleted_count = remove_allowable_relationships_with_missing_types();
				echo '<p>Removed ' . $deleted_count . ' allowable relationships with missing types.</p>';
			}
			else
			{
				echo '<p>Nothing to delete there are no allowable relationships with missing types.</p>';
			}
		}
		else
		{
			echo '<p>Would delete ' . count($ids) . ' allowable relationships with missing types.</p>';
		}
	}
	
	/**
	 * If the number to delete is huge we'll do it in chunks
	 */
	function remove_orphaned_relationships()
	{
		echo '<hr/>';
		$orphans = get_orphaned_relationship_ids();
		if ($this->mode == 'run')
		{
			if (count($orphans) > 0)
			{
				$deleted_count = remove_orphaned_relationships();
				echo '<p>Removed ' . $deleted_count . ' orphaned relationships.</p>';
			}
			else
			{
				echo '<p>Nothing to delete there are no orphaned relationships.</p>';
			}
		}
		else
		{
			echo '<p>Would delete ' . count($orphans) . ' orphaned relationships (this may be inaccurate if the previous steps would delete allowable relationships).</p>';
		}
	}
	
	function update_whats_new_in_reason_blurb()
	{
		echo '<hr/>';
		if (reason_unique_name_exists('whats_new_in_reason_blurb'))
		{
			$id = id_of('whats_new_in_reason_blurb');
			$e = new entity($id);
			$name = $e->get_value('name');
			if (trim($name) == 'Welcome to Reason 4 Beta 4')
			{
				if ($this->mode == 'run')
				{
					reason_update_entity($id, $this->reason_user_id, array('name' => 'Welcome to Reason'));
					echo "<p>Updated the blurb with unique_name 'whats_new_in_reason_blurb' to remove the version number reference.</p>";
				}
				else
				{
					echo "<p>Would update the blurb with unique_name 'whats_new_in_reason_blurb' to remove the version number reference.</p>";
				}
			}
			else
			{
				echo "<p>The blurb with unique_name 'whats_new_in_reason_blurb' does not need updating.</p>";
			}
		}
		else
		{
			echo "<p>The blurb with unique_name 'whats_new_in_reason_blurb' does not exist in this instance.</p>";
		}
	}
	
	function remove_sortable_table_from_blurbs()
	{
		echo '<hr/>';
		$tables = get_entity_tables_by_type(id_of('text_blurb'), false); // no cache
		if (!in_array("sortable", $tables))
		{
			echo "<p>The text blurb type does not use the entity table sortable - this script has probably been run</p>";
		}
		else
		{
			$es = new entity_selector();
			$es->add_type(id_of('content_table'));
			$es->add_relation('entity.name = "sortable"');
			$es->add_right_relationship(id_of('text_blurb'), relationship_id_of('type_to_table'));
			$es->set_num(1);
			$es->limit_tables();
			$es->limit_fields();
			$result = $es->run_one();
			if (!empty($result))
			{
				$table = current($result);
				// grab all text blurb entities
				$es2 = new entity_selector();
				$es2->add_type(id_of('text_blurb'));
				$es2->limit_tables();
				$es2->limit_fields();
				$result2 = $es2->run_one();
				$ids = implode(",", array_keys($result2));
				$q = "DELETE from sortable WHERE id IN(".$ids.")";
				if ($this->mode == "test")
				{
					echo "<p>Would delete all relationships between the text blurb type and the sortable entity table across type_to_table<p>";
					echo "<p>Would also run this query to zap all the entities in the sortable table that correspond to text blurbs:</p>";
					echo "<p>" . $q . "</p>";
				}
				else
				{
					delete_relationships( array('entity_a' => id_of('text_blurb'), 'entity_b' => $table->id(), 'type' => relationship_id_of('type_to_table')));
					db_query($q);
					echo "<p>Deleted type_to_table relationship bewteen text blurbs and sortable, and the corresponding sortable entities</p>";
				}
			}
			else
			{
				echo '<p>Could not find the entity table even though get_entity_tables_by_type says that it exists. Doing nothing.</p>';
			}
		}
	}
	
	function update_type_type_finish_actions()
	{
		echo '<hr/>';
		$type_id = id_of('type');
		$type = new entity($type_id);
		$finish_action = $type->get_value('finish_actions');
		if ($finish_action == 'fix_amputees.php') // indicate update has already taken place
		{
			echo "<p>The type type is already running fix_amputees as a finish action. The script has probably been run.</p>";
		}
		elseif (!empty($finish_action)) // indicate update should be done manually
		{
			echo "<p>The type type currently has a finish action assigned (".$finish_action."). It will not be automatically updated,
				  but you could manually change it by going to the type type in Master Admin, and choosing fix amputees to be the finish
				  action for the type.</p>";
		}
		elseif ($this->mode == 'test') // indicate that we would do the update
		{
			echo "<p>Would update the type type to run fix_amputees as a finish action.</p>";
		}
		else // actually perform update
		{
			$values['finish_actions'] = 'fix_amputees.php';
			reason_update_entity($type_id, $this->reason_user_id, $values);
			echo "<p>Updated the type type to run fix_amputees as a finish action.</p>";
		}
	}
}

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have Reason upgrade rights to run this script');
}

?>
<h2>Reason: Kill Cruft and More</h2>
<p>As Reason changes, sometimes old crufty relationships and entities persist past their useful lifespan. This script zaps cruft and does a few 
more database upgrade actions.</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Deletes the site_to_minisite_template allowable relationship.</li>
<li>Removes any allowable relationships that reference missing types.</li>
<li>Delete orphaned relationships (those that do not correspond to a valid allowable relationship).</li>
<li>If the blurb with unique name whats_new_in_reason_blurb is titled "Welcome to Reason 4 Beta 4" we update it to remove the version reference.</li>
<li>Removes the sortable table from the blurb type.</li>
<li>Updates the type type to use a finish action which fixes amputees.</li>
</ul>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new killCruft();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
