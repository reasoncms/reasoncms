<?php
/**
 * Reason 4 Beta 8 to Beta 9 type updates.
 *
 * A lot of methods we use to create and manipulate types are includeded. Also see "create_allowable_relationship" in 
 * /reason_package/reason_4.0/lib/core/function_libraries/admin_actions.php
 *
 * Feature type details:
 *
 * - New Reason type (We'll call it "Feature" for the purposes of this document)
 *
 * Fields:
 * feature.title (tinytext) required
 * feature.text (text)
 * feature.destination_url (URL)
 * feature.show_text (boolean) required
 * feature.crop_style (enum("fill","fit")) required
 * feature.bg_color (6-char hex string)
 *
 * Relationships:
 * feature_to_image (many-to-many -- module will pick image at random)
 * feature_to_media_work (many-to-many -- module will pick media work at random)
 * page_to_feature (many_to_many, sortable)
 *
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
 * Run type updates that are part of the Reason 4.0 beta 8 to 4.0 beta 9 upgrade
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
		$this->fix_amputees();
	}
	
	function create_feature_type($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
		
		$this->create_feature_type_entity();
		// create feature entity table
		// add fields to the feature entity table
		// add the entity table to the feature type
		// create all the necessary relationships for the feature type
	}
	
	/**
	 * Write me - use reason_create_entity()
	 */
	function create_feature_type_entity()
	{
		if (reason_unique_name_exists('feature_type'))
		{
			echo '<p>This script has probably already been run</p>';
		}
		elseif ($this->mode == 'test')
		{
			echo '<p>Would create the feature type</p>';
		}
		elseif ($this->mode == 'run')
		{
			// do it
		}
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
}