<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['allowable_relationship_unique_names'] = 'ReasonUpgrader_41_AllowableRelationshipUniqueNames';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_41_AllowableRelationshipUniqueNames implements reasonUpgraderInterface
{
	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
        /**
         * Get the title of the upgrader
         * @return string
         */
	public function title()
	{
		return 'Upgrade allowable relationship table';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "This upgrade adds a type column to allowable_relationship with enumerated values ('owns', 'borrows', 'archive', 'association'). It changes all archive relationships ";
		$str .= 'that are "many_to_many" to be "many_to_one". It populates missing column values in order to normalize types. It removes any allowable relationships that reference missing ';
		$str .= 'types. Finally, it changes the allowable relationship names that are currently "owns" or "borrows" so that they are unique.';
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if(reason_relationship_names_are_unique())
		{
			return 'This script has already run';
		}
		else
		{
			ob_start();
			$uars = new updateAllowableRelStructure();
			$uars->do_updates('test', $this->user_id());
			$str = ob_get_contents();
			ob_end_clean();
			return $str;
		}
	}
        /**
         * Run the upgrader
         * @return string HTML report
         */
	public function run()
	{
		if(reason_relationship_names_are_unique())
		{
			return 'This script has already run';
		}
		else
		{
			ob_start();
			$uars = new updateAllowableRelStructure();
			$uars->do_updates('run', $this->user_id());
			$str = ob_get_contents();
			ob_end_clean();
			return $str;
		}
	}
}

class updateAllowableRelStructure
{
	var $mode;
		
	function do_updates($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		// The updates
		$this->remove_allowable_relationships_with_missing_types();
		$this->add_type_column();
		$this->update_owns_and_borrows_allowable_relationships();
		$this->update_archive_allowable_relationships();
	}
	
	function add_type_column()
	{
		$q = 'SELECT * from allowable_relationship LIMIT 1';
		$results = db_query($q);
		$result = mysql_fetch_assoc($results);
		if (isset($result['type'])) echo '<p>The type field is already present this script has probably been run.</p>';
		else
		{
			if ($this->mode == 'test') echo '<p>Would add enumerated type column to the allowable relationship table.</p>';
			else // lets alter the table to add it and report out
			{
				$q = 'ALTER TABLE allowable_relationship ADD COLUMN `type` ENUM("owns", "borrows", "archive", "association") NOT NULL DEFAULT "association"';
				$results = db_query($q);
				echo '<p>Added the type field to the allowable relationship table</p>';
			}
		}
		
	}
	
	function update_owns_and_borrows_allowable_relationships()
	{
		// find all the columns with name fields equal to owns or borrows
		$q = 'SELECT * from allowable_relationship WHERE ((name = "owns") OR (name = "borrows"))';
		$results = db_query($q);
		while ($result = mysql_fetch_assoc($results))
		{
			$rows[$result['id']] = $result;
		}
		$rowcount = (isset($rows)) ? count($rows) : 0;
		if ($this->mode == 'test') echo '<p>Would update ' . $rowcount . ' owns or borrows allowable relationships</p>';
		elseif (isset($rows))
		{
			foreach ($rows as $rel_id => $row)
			{
				$a_entity = new entity($row['relationship_a']);
				$b_entity = new entity($row['relationship_b']);
				$update['name'] = $a_entity->get_value('unique_name') . '_' . $row['name'] . '_' . $b_entity->get_value('unique_name');
				$update['type'] = ($row['name'] == 'owns') ? "owns" : "borrows";
				$update['connections'] = ($row['name'] == 'owns') ? "many_to_one" : "many_to_many";
				$update['required'] = ($row['name'] == 'owns') ? "yes" : "no"; // is ownership really required??
				$update['directionality'] = ($row['name'] == 'owns') ? 'unidirectional' : 'bidirectional';
				$update['is_sortable'] = "no";
				$updates[$rel_id] = $update;
			}
			if (!empty($updates))
			{
				$q = 'LOCK TABLE allowable_relationship WRITE';
				$result = db_query($q);
				$sqler = new sqler;
				foreach ($updates as $rel_id => $update)
				{
					$sqler->update_one( 'allowable_relationship', $update, $rel_id );
				}
				$q = 'UNLOCK TABLES';
				$result = db_query($q);
				reason_refresh_relationship_names();
			}
			echo '<p>Updated ' . $rowcount . ' owns or borrows allowable relationships</p>';
		}
		else echo '<p>There are no owns or borrows allowable relationships to update</p>';
	}
	
	function update_archive_allowable_relationships()
	{
		// find all the archive columns
		$q = 'SELECT * from allowable_relationship WHERE (SUBSTRING(name, -7) = "archive")';
		$results = db_query($q);
		while ($result = mysql_fetch_assoc($results))
		{
			if (!empty($result['type']) && $result['type'] == 'archive') continue;
			$rows[$result['id']] = $result;
		}
		$rowcount = (isset($rows)) ? count($rows) : 0;
		if ($this->mode == 'test') echo '<p>Would update ' . $rowcount . ' archive allowable relationships</p>';
		elseif (isset($rows))
		{ 
			foreach ($rows as $rel_id => $row)
			{
				$update['type'] = "archive";
				$update['connections'] = "many_to_one";
				$update['required'] = "no";
				$update['directionality'] = "unidirectional";
				$update['is_sortable'] = "no";
				$updates[$rel_id] = $update;
			}
			if (!empty($updates))
			{
				$q = 'LOCK TABLE allowable_relationship WRITE';
				$result = db_query($q);
				$sqler = new sqler;
				foreach ($updates as $rel_id => $update)
				{
					$sqler->update_one( 'allowable_relationship', $update, $rel_id );
				}
				$q = 'UNLOCK TABLES';
				$result = db_query($q);
				reason_refresh_relationship_names();
			}
			echo '<p>Updated ' . $rowcount . ' archive allowable relationships</p>';
		}
		else echo '<p>There are no archive allowable relationships to update</p>';
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
				reason_refresh_relationship_names();
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
}
?>
