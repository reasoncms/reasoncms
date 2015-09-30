<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include the Reason basic libraries
 */
include ('reason_header.php');
/**
 * Include the db selector utility class
 */
include_once(CARL_UTIL_INC.'db/db_selector.php');
/**
 * Include the sqler utility class
 */
include_once( CARL_UTIL_INC . 'db/sqler.php' );
/**
 * Include various other Reason utilities
 */
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('classes/amputee_fixer.php');
/**
 * Run type updates that are part of the Reason 4.0 beta 7 to 4.0 beta 8 upgrade
 */
class updateTypes
{
	var $mode;
	var $reason_id;
		
	function do_updates($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
		
		// The updates
		$this->add_field_to_entity_table('category', 'slug', 'tinytext');
		$this->add_field_to_entity_table('media_work', 'rating', 'tinyint');
		$this->add_new_entity_table_to_type('quote', 'quote_type');
		$this->add_field_to_entity_table('quote', 'rating', 'tinyint');
		$this->add_field_to_entity_table('site', 'domain', 'tinytext');
		$this->add_field_to_entity_table('av', 'caption_url', 'tinytext');
		$this->add_field_to_entity_table('av', 'audio_description_url', 'tinytext');
		
		// THIS STUFF IS NOT ACTUALLY IN THE CORE!
		//$this->add_new_entity_table_to_type('course', 'course_type');
		//$this->add_field_to_entity_table('course', 'slug', 'tinytext');
		
		$this->remove_field_from_entity_table('category', 'old_calendar_equivalent');
		$this->remove_field_from_entity_table('category', 'campus_pipeline_equivalent');
		$this->remove_field_from_entity_table('site', 'is_incarnate');
		$this->remove_field_from_entity_table('site', 'script_url');
		
		$this->move_location_field_to_event();
		$this->modify_allowable_relationship('office_department_to_category', array('display_name' => 'Is Related To Topic / Category',
					     		  													'display_name_reverse_direction' => 'Is Related To Office / Department',
					     		  													'directionality' => 'bidirectional'));
		$this->modify_allowable_relationship('quote_to_category', array(			'display_name' => 'Is About Topic / Category',
					     		  													'display_name_reverse_direction' => 'Is Subject of Quote',
					     		  													));

		$this->fix_amputees();
		//$this->create_location_type(); // note at carleton this method is a lot different - see local copy
	}
	
	function fix_amputees()
	{
		if ($this->mode == 'test') echo '<p>Would attempt to fix amputees</p>';
		else
		{
			$fixer = new AmputeeFixer();
        	$fixer->fix_amputees();
        	$fixer->generate_report();
        }
	}
	
	function add_new_entity_table_to_type($table, $type_unique_name)
	{
		$type_id = reason_unique_name_exists($type_unique_name) ? id_of($type_unique_name) : false;
		if ($type_id)
		{
			$tables = get_entity_tables_by_type($type_id, false);
			
			if (!in_array($table, $tables))
			{
				if ($this->mode == 'test') echo '<p>Would create table ' . $table . ' for type ' . $type_unique_name . '</p>';
				else 
				{
					create_reason_table($table, $type_unique_name, $this->reason_id);
					echo '<p>Created table ' . $table . ' for type ' . $type_unique_name . '</p>';
				}
			}
			else
			{
				echo '<p>The table ' . $table . ' for type ' . $type_unique_name . ' already exists.</p>';
			}
		}
	}
	
	/**
	 * Creates entity tables if necessary
	 */
	function add_field_to_entity_table($table, $field_name, $field_db_type)
	{
		// lets make sure the table exists first
		$es = new entity_selector();
        $es->add_type(id_of('content_table'));
        $es->add_relation('entity.name = "'.$table.'"');
        $results = $es->run_one();
        if ($results)
        {
        	if (in_array($field_name, get_fields_by_content_table($table)))
			{
				echo '<p>The '.$table.' entity table already has the field '.$field_name.' - the script has probably been run.</p>';
				return false;
			}
			else
			{
				$updater = new FieldToEntityTable($table, array($field_name => array('db_type' => $field_db_type)));
				if ($this->mode == 'test') $updater->test_mode = true;
				$updater->update_entity_table();
				$updater->report();
			}
		}
	}
	
	function remove_field_from_entity_table($table_name, $field_name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.$table_name.'"');
		$es->set_num(1);
		$result = $es->run_one();
		$table = reset($result);
		
		if ($table)
		{
			$es2 = new entity_selector();
			$es2->add_type(id_of('field'));
			$es2->add_left_relationship($table->id(), relationship_id_of('field_to_entity_table'));
			$es2->add_relation('entity.name = "'.$field_name.'"');
			$es2->set_num(1);
			$result2 = $es2->run_one();
			$field = reset($result2);
		}
		else $field = false;
		
		if ($field && ($this->mode == 'test'))
		{
			
			echo '<p>Would delete field ' . $field_name . '</p>';
		}
		elseif ($field && ($this->mode == 'run'))
		{
			reason_expunge_entity($field->id(), $this->reason_id);
			echo '<p>Deleted field ' . $field_name . '</p>';
		}
		elseif (!$field)
		{
			echo '<p>Could not delete ' . $field_name . ' - field name not found in the entity table.</p>';
		}
		
		// lets remove the column from the entity table
		$q = 'SHOW COLUMNS FROM ' . $table_name;
        $result = db_query($q, 'could not get fields from table ' . $table_name);
        if ($result)
        {
        	while($table = mysql_fetch_assoc($result))
        	{
        		$columns[] = $table['Field'];
        	}
        	if (in_array($field_name, $columns))
        	{
        		if ($this->mode == 'test')
        		{
        			echo '<p>Would drop column ' . $field_name . ' from table ' . $table_name .'</p>';
        		}
        		elseif ($this->mode == 'run')
        		{	
        			$q = 'ALTER TABLE `' . $table_name . '` DROP `' . $field_name . '`';
        			$result = db_query($q, 'could not drop column ' . $field_name . ' from table ' . $table_name);
        			if ($result) echo '<p>Dropped column ' . $field_name . ' from table ' . $table_name .'</p>';
        		}
        	}
        	else
        	{
        		echo '<p>Did not drop column ' . $field_name . ' from the table ' . $table_name . ' because the field is not part of the table</p>';
        	}
        }		
	}
	
	/**
	 * Move the field location in the location entity table to the event table and then delete the location entity table. 
	 */
	function move_location_field_to_event()
	{
		$fields = get_fields_by_content_table('event');
		
		if (in_array('location', $fields))
		{
			echo '<p>The event entity table already has the location field - the script has probably been run</p>';
			
			$es = new entity_selector();
			$es->add_type(id_of('event_type'));
			$es->set_num(5);
			$es->add_relation('event.location IS NOT NULL');
			$result = $es->run_one();
		}
		elseif ($this->mode == 'run')
		{
			// grab all locations from the location.location table for events
			$es = new entity_selector();
			$es->add_type(id_of('event_type'));
			$es->limit_tables(array('location'));
			$es->limit_fields(array('location.location'));
			$es->add_relation(' ((location.location IS NOT NULL) AND (location.location != ""))');
			$result = $es->run_one('', 'All'); // All does not get archived entities ... hmmm
			$result2 = $es->run_one('', 'Archived');
			
			// populate $ids - an array of entity ids to location value, with it we can do a direct update of the event entity table
			foreach ($result as $k=>$v)
			{
				$ids[$k] = $v->get_value('location');
			}
			foreach ($result2 as $k2=>$v2)
			{
				$ids[$k2] = $v2->get_value('location');
			}
			unset ($es); unset($result); unset($result2);
			
			// lets find our table entity
			$es = new entity_selector();
			$es->add_type(id_of('content_table'));
			$es->add_relation('entity.name = "location"');
			$es->set_num(1);
			$location_result = $es->run_one();
			
			$es2 = new entity_selector();
			$es2->add_type(id_of('content_table'));
			$es2->add_relation('entity.name = "event"');
			$es2->set_num(1);
			$event_result = $es2->run_one();
			
			if ($location_result && $event_result)
			{
				$location_table = reset($location_result);
				$event_result = reset($event_result);
				$field_to_entity_table_id = relationship_id_of('field_to_entity_table');
				
				$es3 = new entity_selector();
				$es3->add_type(id_of('field'));
				$es3->add_relation('entity.name = "location"');
				$es3->add_left_relationship($location_table->id(), $field_to_entity_table_id);
				$es3->set_num(1);
				$es3_result = $es3->run_one();
				
				if ($es3_result)
				{
					$field = reset($es3_result);
					
					// create the column on the event table
					$q = "ALTER TABLE event ADD location ". $field->get_value( 'db_type' );
					$r = db_query( $q, 'Problems - could not add the location column to the event table' );
					
					$sqler = new SQLER();
					// populate the values for the new column
					if (isset($ids))
					{
						foreach ($ids as $id => $location)
						{
							$sqler->update_one( 'event', array('location' => $location), $id );
						}
					}
					
					// update the entity table for the field_to_entity_table_relationship at hand
					$q = 'UPDATE relationship SET entity_b=' . $event_result->id();
					$q .= ' WHERE entity_a='.$field->id().' AND entity_b='.$location_table->id().' AND type='.$field_to_entity_table_id;
					db_query( $q );
					
					// create the column on the event table
					$q = "DROP TABLE location";
					$r = db_query( $q, 'Could not drop the entity table location' );
					
					reason_expunge_entity($location_table->id(), $this->reason_id);
					echo '<p>Moved location.location to event.location, and deleted the location entity table</p>';
				}
			}
		}
		elseif ($this->mode == 'test')
		{
			echo '<p>Would move the location.location to event.location, and delete the location entity table</p>';
		}
	}
	
	function modify_allowable_relationship($rel_name, $fields)
	{
		
		$rel_id = relationship_id_of($rel_name);
		if ($rel_id)
		{
			$q = 'SELECT * from allowable_relationship WHERE id = ' . $rel_id;
			$results = db_query($q);
			if (mysql_num_rows($results))
			{
				$result = mysql_fetch_assoc($results);
				$needs_update = false;
				foreach ($fields as $k=>$v)
				{
					if ($result[$k] != $v) $needs_update = true;
				}
				if ($needs_update && $this->mode == 'test')
				{
					echo '<p>Would update the allowable relationship ' . $rel_name . '</p>';
				}
				elseif ($needs_update && $this->mode == 'run')
				{
					$sqler = new SQLER();
					$sqler->update_one( 'allowable_relationship', $fields, $rel_id );
					echo '<p>Updated the allowable relationship ' . $rel_name . '</p>';
				}
				else echo '<p>The '.$rel_name.' relationship is up to date. This script has probably been run</p>';
			}
		}
		else echo '<p>The allowable relationship name ' . $rel_name . ' does not exist</p>';
	}
	
	function create_location_type()
	{
		if (reason_unique_name_exists('location_type') || reason_unique_name_exists('address_type'))
		{
			echo '<p>This script has probably already been run</p>';
		}
	}
}