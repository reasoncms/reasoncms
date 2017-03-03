<?php
/**
 * Code to add fields to entity tables
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include necessary Reason libraries
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * Field to Entity Table Class
 *
 * Intended for use by scripts that need to add field(s) to entity tables.
 *
 * Sample Usage:
 *
 * <code>
 *
 * $entity_table_name = 'user';
 * $fields = array('user_surname' => array('db_type' => 'tinytext'),
 *				   'user_given_name' => array('db_type' => 'tinytext'),
 *			 	   'user_email' => array('db_type' => 'tinytext'),
 *				   'user_phone' => array('db_type' => 'tinytext'),
 *				   'user_password_hash' => array('db_type' => 'tinytext'),
 *				   'user_authoritative_source' => array('db_type' => "enum('reason','external')"));
 *
 * $updater = new FieldToEntityTable($entity_table_name, $fields);
 * $updater->update_entity_table();
 * $updater->report();
 *
 * </code>
 *
 * @author nwhite
 * @package reason
 * @subpackage classes
 */
class FieldToEntityTable 
{
	var $entity_table_name;
	var $entity_table_id;
	var $fields;
	var $ma_site_id;
	var $field_id;
	var $field_to_entity_table_rel_id;
	var $user_id;
	var $err = array();
	var $warn = array();
	var $report = array();
	var $queries = array();
	
	var $test_mode = false;

	function FieldToEntityTable($entity_table_name = '', $fields = array(), $user_netID = '')
	{
		// set master admin site id
		$this->ma_site_id = id_of('master_admin');
		$this->field_id = id_of('field');
		$this->field_to_entity_table_rel_id = relationship_id_of('field_to_entity_table');
		if (empty($user_netID)) $user_netID = check_authentication();
		$this->user_id = get_user_id($user_netID);
		if (empty($this->user_id))
		{
			trigger_error('The FieldToEntityTable Class cannot get a user_id for user ' . $user_netID, HIGH);
		}
		if (!empty($entity_table_name)) $this->set_entity_table($entity_table_name);
		foreach ($fields as $k=>$v)
		{
			$this->add_field($k, $v);
		}
	}
	
	function set_entity_table($entity_table_name)
	{
		$es = new entity_selector($this->ma_site_id);
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.$entity_table_name.'"');
		$results = $es->run_one();
		$result_count = count($results);
		if ($result_count == 0) $this->err[] = 'The entity table ' . $entity_table_name . ' could not be found';
		elseif ($result_count > 1) $this->err[] = 'Multiple entity tables were found with the name ' . $entity_table_name . ' - the script cannot be run until this is fixed.';
		elseif ($result_count == 1)
		{
			$this->entity_table_id = key($results);
			$this->entity_table_name = $entity_table_name;
			return true;
		}
		return false;
	}

	function add_field($field_name = '', $field_meta = array())
	{
		if (empty($field_name) || empty($field_meta))
		{
			if (empty($field_name)) $this->err[] = 'add_field was called but not provided a field name';
			if (empty($field_meta)) $this->err[] = 'add_field was called with no description of field characteristics';
			return false;
		}
		if (empty($field_meta['db_type']))
		{
			$this->err[] = 'add_field was called but ' . $field_name . ' must have a db_type defined in order to be added';
			return false;
		}
		else
		{
			if ($this->field_exists($field_name))
			{
				$this->warn[] = 'field ' .$field_name . ' already exists in the entity table';
				return false;
			}
			else
			{
				$field_meta['type'] = $this->field_id;
				$this->fields[$field_name] = $field_meta;
			}
		}
	}

	function update_entity_table()
	{
		$process = false;
		if (count($this->fields) == 0)
		{
			$this->err[] = 'There are no fields to add that do not already exist in the entity table ' . $this->entity_table_name;
		}
		if (count($this->err) > 0)
		{
			$this->err[] = 'Entity table ' . $this->entity_table_name . ' not updated because of errors';
			return false;
		}
		foreach ($this->fields as $k=>$v)
		{
			$process = true;
			$q = 'ALTER TABLE `'.$this->entity_table_name.'` ADD `'.$k.'` '.$v['db_type'];
			if ($this->test_mode) $this->queries[] = $q;
			else $r = db_query( $q, 'There was a problem altering the table to add the field - probably a syntax error in the db_type description');
		
			// Create the field entity
			if ($this->test_mode) $this->report[] = 'The field ' . $k . ' would be created and added to the entity table ' . $this->entity_table_name;
			else 
			{
				$v['new'] = 0;
				$id = reason_create_entity( $this->ma_site_id, $this->field_id, $this->user_id, $k, $v );
				
				// Relate the new field entity to the entity table
				create_relationship( $id, $this->entity_table_id, $this->field_to_entity_table_rel_id);
				$this->report[] = 'The field ' . $k . ' was created and added to the entity table ' . $this->entity_table_name;
			}
		}
		if ($process && !$this->test_mode) $this->report[] = 'Finished - Entity table updated';
		elseif ($process && $this->test_mode) $this->report[] = 'Finished - Entity table would have been updated';
		return true;
	}
	
	function report()
	{
		if (count($this->warn) > 0) 
		{
			echo '<h3>Warnings</h3>';
			$this->array_to_list_HTML($this->warn);
		}
		if (count($this->err) > 0) 
		{
			echo '<h3>Errors</h3>';
			$this->array_to_list_HTML($this->err);
		}
		if ($this->test_mode)
		{
			if (count($this->queries) > 0)
			{
				echo '<h3>Queries that Would Have Run</h3>';
				$this->array_to_list_HTML($this->queries);
			}
		}
		if (count($this->report) > 0)
		{
			echo '<h3>Report</h3>';
			$this->array_to_list_HTML($this->report);
		}
	}

	//returns id of field if it exists in the entity_table
	function field_exists($field_name)
	{
		if (empty($this->has_fields))
		{
			$es = new entity_selector($this->ma_site_id);
			$es->add_type($this->field_id);
			$es->add_left_relationship($this->entity_table_id, $this->field_to_entity_table_rel_id);
			$results = $es->run_one();
			foreach($results as $entity)
			{
				$this->has_fields[] = $entity->get_value('name');
			}
		}
		if (!empty($this->has_fields) && in_array($field_name, $this->has_fields))
		{
			return true;
		}
		return false;
	}
	
	function array_to_list_HTML($array = array())
	{
		if (count($array) > 0)
		{
			echo '<ul>';
			foreach ($array as $v)
			{
				echo '<li>'.$v.'</li>';
			}
			echo '</ul>';
		}
	}
}

?>

