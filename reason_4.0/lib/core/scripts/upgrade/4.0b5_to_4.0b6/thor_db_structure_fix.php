<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include Reason libraries & other dependencies
 */
include_once('reason_header.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/entity_selector.php');
include_once(XML_PARSER_INC . 'xmlparser.php');

/** 
 * Thor database structure fix
 *
 * Thor database enum fields are set to not allow null values, regardless of a fields "required" setting
 * When saving a radio button or option group set, this means that the first possible enum value 
 * is always saved in the field rather than NULL.
 * 
 * This script that will look through a thor database in a reason instance and do the following
 *
 * - Identify tables that have enum fields that match the problem case
 * - Alter the field in the appropriate table to allow "NULL" for all ENUM fields
 * - Preserve existing values and default value settings as reflected in thor XML
 * - Ensure data is the same before and after alter table operations - trigger fatal error if not
 *
 * @author Nathan White
 */

$user_netid = reason_require_authentication();
if($user_netid)
{
	$reason_user_id = get_user_id($user_netid);
}
if (!empty($reason_user_id) && reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	if (isset($_REQUEST['do_it']) && ($_REQUEST['do_it'] == 1))
	{
		echo '<h3>Performing operations</h3>';
		$test_mode = false;
	}
	else
	{
		$test_mode = true;
		echo '<h3>Reporting on what I will do</h3>';
		echo '<a href="?do_it=1">Do it Now!</a>';
	}
	$thor_db_update = new ThorDatabaseUpdate;
	$thor_db_update->init();
	$thor_db_update->set_test_mode($test_mode);
	$thor_db_update->run();
	$report =& $thor_db_update->get_report();
	if (!empty($report))
	{
		foreach ($report as $headline=>$items)
		{
			pray ($report);
		}
	}
	else
	{
		echo '<p>Nothing to report - script has probably already been run in this instance.</p>';
	}
}
else
{
	echo '<h3>Unauthorized</h3>';
	echo '<p>You must have Reason upgrade rights to run this script</p>';
}

class ThorDatabaseUpdate
{
	var $thor_tables;
	var $prefix = 'form_';
	var $test_mode = true;
	
	function init()
	{
		$this->_build_tables();
	}
	
	function &get_tables()
	{
		if (!isset($this->thor_tables)) $this->_build_tables();
		return $this->thor_tables;
	}
	
	function set_test_mode($bool)
	{
		$this->test_mode = ($bool);
	}
	
	function get_test_mode()
	{
		return $this->test_mode;
	}
	
	function _build_tables()
	{
		connectDB(THOR_FORM_DB_CONN);
		$result = db_query("show tables");
		connectDB(REASON_DB);
		if (mysql_num_rows($result) > 0)
		{
			while ($row = mysql_fetch_row($result))
			if (substr($row[0], 0, strlen($this->prefix)) == $this->prefix) $return[substr($row[0], strlen($this->prefix))] = $row[0];
		}
		$this->thor_tables = (isset($return)) ? $this->_validate_thor_tables($return) : array();
	}
	
	function _validate_thor_tables($all_tables)
	{
		// create string of ids
		$form_ids = array_keys($all_tables);
		$form_id_string = implode(",",$form_ids);
		
		// select all form entities with ids in $all_tables - ones that are not selected do not correspond to a reason entity
		$es = new entity_selector();
		$es->add_type(id_of('form'));
		$es->limit_tables();
		$es->limit_fields();
		$es->add_relation('entity.id IN ('.$form_id_string.')');
		$result = $es->run_one();
		$form_ids_in_reason = array_keys($result);
		$invalid_ids = (array_diff($form_ids, $form_ids_in_reason));
		foreach ($invalid_ids as $id)
		{
			unset($all_tables[$id]);
		}
		return $all_tables;
	}
	
	function run()
	{
		$thor_tables =& $this->get_tables();
		foreach ($thor_tables as $table_id => $table_name)
		{
			$thor_table = new ThorTableUpdate();
			$thor_table->init($table_id, $table_name);
			$thor_table->set_test_mode($this->get_test_mode());
			if ($thor_table->run())
			{
				$report[$table_name] = $thor_table->get_report();
			}
		}
		if (isset($report)) $this->set_report('Tables updated', $report);
	}
	
	function set_report($report_key, $report = false)
	{
		if ($report) 
		{
			$this->report[$report_key] = $report;
		}
	}
	
	function &get_report()
	{
		return $this->report;
	}
}

class ThorTableUpdate
{
	var $table_id;
	var $table_name;
	var $test_mode = true;
	var $report = false;
	
	function ThorTableUpdate()
	{
	}
	
	function init($table_id = false, $table_name = false)
	{
		if ($table_id) $this->set_table_id($table_id);
		if ($table_name) $this->set_table_name($table_name);
		$this->user_id = get_user_id(reason_require_authentication());
	}
	
	function set_table_id($table_id)
	{
		$this->table_id = $table_id;
	}
	
	function set_table_name($table_name)
	{
		$this->table_name = $table_name;
	}
	
	function get_table_id()
	{
		return $this->table_id;
	}
	
	function get_table_name()
	{
		return $this->table_name;
	}

	function set_test_mode($bool)
	{
		$this->test_mode = ($bool);
	}
	
	function get_test_mode()
	{
		return $this->test_mode;
	}

	function run()
	{
		if ($this->table_needs_update())
		{
			return $this->table_update();
		}
		return false;
	}
	
	/**
	 * Analyze the table
	 */
	function table_needs_update()
	{
		// examine table representation in thor - see if it has radio button or select fields
		$table_entity = new entity($this->get_table_id());
		$table_xml = $table_entity->get_value('thor_content');
		
		// lets do a quick look to see if the table has radio group or select values - if so, we'll take a look at the structure
		if ( (strpos($table_xml, 'optiongroup') !== false) || (strpos($table_xml, 'radiogroup') !== false) )
		{
			// check if the enum fields already allow NULL values - if so the field does not need an update
			connectDB(THOR_FORM_DB_CONN);
			$qry = 'SHOW COLUMNS FROM `'.$this->get_table_name().'`';
   	     	$result = db_query($qry);
   	     	while ($row = mysql_fetch_assoc($result))
   	     	{
   	     		if ( (substr($row['Type'], 0, 4) == 'enum') && (strtolower($row['Null']) != 'yes') )
   	     		{
   	     			$this->set_field_needs_update($row['Field']);
   	     			$needs_update = true;
   	     		}
   	     	}
   	     	connectDB(REASON_DB);
   	     	if (isset($needs_update)) return true;		
		}
		return false;
	}
	
	function set_field_needs_update($field)
	{
		$this->needs_update[$this->get_table_name()][$field] = true;
	}
	
	function &get_fields_that_need_update()
	{
		return $this->needs_update[$this->get_table_name()];
	}
	
	/**
	 * Returns an existing or new XML_Parser object for the thor content of a reason form.
	 * @param reason form id
	 * @return object XML_Parser
	 */
	function &getXMLParser()
	{
		static $xml_parsers;
		
		$id = $this->get_table_id();
		if (isset($xml_parsers[$id])) return $xml_parsers[$id];
		else
		{
			$table_entity = new entity($id);
			$table_xml = $table_entity->get_value('thor_content');
			$xml_parser = new XMLParser($table_xml);
			$xml_parser->Parse();
			$xml_parsers[$id] =& $xml_parser;
		}
		return $this->getXMLParser();
	}
	
	/**
	 * @param array needed_fixes
	 * @return string description of fixes performed and result of data integrity check
	 */
	function table_update()
	{
		$fix_reason_content = false;	
		$xml_parser =& $this->getXMLParser();
		$fields_to_update =& $this->get_fields_that_need_update();
		if (isset($xml_parser->document->radiogroup))
        foreach ($xml_parser->document->radiogroup as $index => $radiogroup)
        {
        	$field_id = $radiogroup->tagAttrs['id'];
        	if (isset($fields_to_update[$field_id]))
        	{
        		if ($enum_result = $this->populate_enum($radiogroup->tagChildren))
        		{
        			$to_process[$field_id]['depth'] = $index;
        			$to_process[$field_id]['options'] = $enum_result['options'];
        			//if (isset($enum_result['selected'])) $to_process[$field_id]['selected'] = $enum_result['selected'];
        		}
        	}
        }
        if (isset($xml_parser->document->optiongroup))
        foreach ($xml_parser->document->optiongroup as $index => $optiongroup)
        {
        	$field_id = $optiongroup->tagAttrs['id'];
        	if (isset($fields_to_update[$field_id]))
        	{
        		if ($enum_result = $this->populate_enum($optiongroup->tagChildren))
   	     		{
        			$to_process[$field_id]['options'] = $enum_result['options'];
        			if (!isset($optiongroup->tagAttrs['required']))
        			{
        				// we are forcing these to required since it was not null previously, and not required,
        				// but the interface was basically forcing a simulation of the "required" setting
        				$to_process[$field_id]['required'] = 'required'; 
        			}
        			$to_process[$field_id]['label'] = $optiongroup->tagAttrs['label'];
        		}
        	}
        }
        
        //$this->set_report('bones i am updating table id ' . $this->table_id . ' which has name ' . $this->table_name);
		if (isset($to_process)) 
		{
			// build query
			foreach ($to_process as $field_name => $field_data)
			{
				$qry = '';
				$qry = 'ALTER TABLE `'.$this->get_table_name().'` CHANGE `'.$field_name.'` `'.$field_name.'`';
				$qry .= ' ENUM ("'. implode('","', $field_data['options']) . '")';
				$qry .= ' NULL ';
				
				// thor never sets a default value - we will keep this in the xml
				//if (isset($field_data['selected']))
				//{
				//	$qry .= ' DEFAULT "'.$field_data['options'][$field_data['selected']].'"';
				//}
				$update_thor[$field_name] = $qry;
				
				if (isset($field_data['required']))
				{
					$fix_reason_content = true;
					$search[] = 'label="' . $field_data['label'] . '" id="' . $field_name . '">';
					$replace[] = 'required="required" label="' . $field_data['label'] . '" id="' . $field_name . '">';
					$field_to_required[] = $field_name;
				}
			}
			
			if ($fix_reason_content)
			{
				if (!$this->test_mode) 
				{
					$table_entity = new entity($this->get_table_id());
					$table_xml = $table_entity->get_value('thor_content');
					$new_table_xml = str_replace($search, $replace, $table_xml);				
					reason_update_entity( $this->get_table_id(), $this->user_id, array('thor_content' => $new_table_xml), false);
				}
				$output['update_reason'] = 'option groups with ids ' . implode(", ", $field_to_required) . ' marked required';
			}
			
			if (!empty($update_thor))
			{
				if (!$this->test_mode)
				{
					connectDB(THOR_FORM_DB_CONN);
					
					$old_data = $this->get_table_data($this->get_table_name());
					foreach ($update_thor as $qry)
					{
						db_query($qry);
					}
					$new_data = $this->get_table_data($this->get_table_name());
					connectDB(REASON_DB);
					
					// data check makes sure data in table is the same before and after query. If not - dies with a fatal error.
					if (array_diff_assoc_recursive($new_data, $old_data))
					{
						echo '<h2>Original data</h2>';
						pray ($old_data);
						echo '<h2>New data</h2>';
						pray ($new_data);
						echo '<h2>Difference detected in new data</h2>';
						pray (array_diff_assoc_recursive($new_data, $old_data));
						trigger_error('integrity problem - result not the same ... script terminating ... table ' . $this->get_table_name() . ' may be corrupted', FATAL);
					}
				}
				$output['update_thor'] = $update_thor;
			}
			$this->set_report($output);
		}
		return true;
	}
	
	function get_table_data()
	{
		$qry = 'SELECT * FROM ' . $this->get_table_name();
		$result = db_query($qry);
		if (mysql_num_rows($result) > 0)
		{
			while ($row = mysql_fetch_assoc($result))
			{
				$ret[] = $row;
			}
			return $ret;
		}
		else return array();
	}
	
	function populate_enum($options)
	{
		$index = 0;
		foreach ($options as $option)
		{
			if (isset($option->tagAttrs['value']))
			{
				$enum['options'][$index] = reason_sql_string_escape($option->tagAttrs['value']);
			}
			//if (isset($option->tagAttrs['selected']))
			//{
			//	$enum['selected'] = $index;
			//}
			$index++;
		}
		return (!empty($enum)) ? $enum : false;
	}
	
	function set_report($report)
	{
		$this->report = $report;
	}
	
	function get_report()
	{
		return $this->report;
	}
}

?>