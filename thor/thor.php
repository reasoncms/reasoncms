<?php
/**
 * @package thor
 */

/**
 * Include dependencies
 */
include_once('paths.php');
require_once( THOR_INC .'disco_thor.php');
include_once(TYR_INC.'tyr.php');
require_once( INCLUDE_PATH . 'xml/xmlparser.php' );
include_once ( SETTINGS_INC.'thor_settings.php' );
include_once( CARL_UTIL_INC . 'db/db.php'); // Requires ConnectDB Functionality

/**
 * ThorCore
 *
 * - Adds elements described in thor xml to a disco form
 * - Table creation / validation
 *
 * @todo table creation / validation?
 * @todo finish me
 * @todo deprecate option xml on methods ... lets require it to get set before - that way we can parse once per instance.
 * @author Nathan White
 */
class ThorCore
{
	var $_xml = false;
	var $_table = false;
	var $_db_conn = THOR_FORM_DB_CONN;
	var $_extra_fields = array('id', 'submitted_by', 'submitter_ip', 'date_created', 'date_modified');
	
	function ThorCore($xml = '', $table = '')
	{
		if ($xml) $this->set_thor_xml($xml);
		if ($table) $this->set_thor_table($table);
	}
	
	/**
	 * If you need to instantiate thor with a different db_conn this can be called - but typically the
	 * the database connection is determined by the THOR_FORM_DB_CONN constant.
	 */
	function set_db_conn($db_conn)
	{
		$this->_db_conn = $db_conn;
	}
	
	function get_db_conn()
	{
		return $this->_db_conn;
	}
	
	function set_thor_xml($xml)
	{
		$xml = new XMLParser($xml);
		$xml->Parse();
		$this->_xml = $xml;	
	}
		
	function get_thor_xml()
	{
		return $this->_xml;
	}
	
	function set_thor_table($table)
	{
		$this->_table = $table;
	}
	
	function get_thor_table()
	{
		return $this->_table;
	}
	
	function &get_display_values()
	{
		if (!isset($this->_display_values))
		{
			$this->_build_display_values();	
		}
		return $this->_display_values;
	}
	
	function append_thor_elements_to_form(&$disco_obj)
	{
		$xml = $this->get_thor_xml();
		if ($xml && $disco_obj)
		{
			foreach ($xml->document->tagChildren as $node)
			{
				if ($node->tagName == 'input') $this->_transform_input($node, $disco_obj);
				elseif ($node->tagName == 'textarea') $this->_transform_textarea($node, $disco_obj);
				elseif ($node->tagName == 'radiogroup') $this->_transform_radiogroup($node, $disco_obj);
				elseif ($node->tagName == 'checkboxgroup') $this->_transform_checkboxgroup($node, $disco_obj);
				elseif ($node->tagName == 'optiongroup') $this->_transform_optiongroup($node, $disco_obj);
				elseif ($node->tagName == 'hidden') $this->_transform_hidden($node, $disco_obj);
				elseif ($node->tagName == 'comment') $this->_transform_comment($node, $disco_obj);
			}
			$this->_transform_submit($xml->document->tagAttrs, $disco_obj);
		}
		else
		{
			trigger_error('To add thor elements to a disco form you need to provide a disco object and thor xml');
		}
	}
	
	function apply_magic_transform_to_form(&$disco_obj, $transform_array)
	{
		$display_values =& $this->get_display_values();
		foreach ($display_values as $key => $details)
		{
			$real_label = $details['label'];
			$normalized_label = strtolower(str_replace(" ", "_", $real_label));
			if (isset($transform_array[$real_label]) || isset($transform_array[$normalized_label]))
			{
				$value = (isset($transform_array[$real_label])) ? $transform_array[$real_label] : $transform_array[$normalized_label];
				$disco_obj->set_value($key, $value);
			}
		}
	}
	
	function apply_solidtext(&$disco_obj, $transform_array)
	{
		$display_values =& $this->get_display_values();
		foreach ($display_values as $key => $details)
		{
			$real_label = $details['label'];
			$normalized_label = strtolower(str_replace(" ", "_", $real_label));
			if (isset($transform_array[$real_label]) || isset($transform_array[$normalized_label]))
			{
				$disco_obj->change_element_type($key, 'solidtext');
			}
		}
	}
	
	/**
	 * Works for thor forms where there is one row per username
	 * @return boolean true if a record was found for the username
	 */
	function apply_values_for_user_to_form(&$disco_obj, $username)
	{
		if ($this->table_exists())
		{
			$user_values = $this->get_values_for_user($username);
			$display_values =& $this->get_display_values();
			if ($user_values)
			{
				foreach ($user_values as $k=>$v)
				{
					if ($disco_obj->get_element($k))
					{
						$disco_obj->set_value($k, $v);
					}
					elseif (isset($display_values[$k]['group_id']))
					{
						$group[$display_values[$k]['group_id']][] = $v;
					}
				}
				if (isset($group))
				{
					foreach ($group as $k=>$v)
					{
						if ($disco_obj->get_element($k))
						{
							$disco_obj->set_value($k, $v);
						}
					}
				}
				return true;
			}
			else return false;
		}
		return false;
	}
	
	/**
	 * Returns a save array that can be used by disco, or passed to save_values
	 * @todo instead of accessing $_REQUEST directly - we should probably parse the disco get_values array.
	 */
	function get_form_values_for_save()
	{
		$db_save_array = conditional_stripslashes($_REQUEST);
		if (isset($db_save_array['transform']))
		{
			foreach ($db_save_array['transform'] as $k=>$v) //process checkbox transformations
			{
				foreach ($v as $k2=>$v2)
				{
					$db_save_array[$v2] = isset($db_save_array[$k][$k2]) ? $db_save_array[$k][$k2] : '';
				}
				unset ($db_save_array['transform'][$k]);
				unset ($db_save_array[$k]);
			}
		}
		foreach ($db_save_array as $k => $v)
		{
			if (substr($k, 0, 3) != "id_")
			{
				if (in_array($k, $this->_extra_fields) == false) unset ($db_save_array[$k]);
			}
		}
		return $db_save_array;
	}
	
	function save_values($values)
	{
		if ($this->get_thor_table() && $values)
		{
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
  			$GLOBALS['sqler']->mode = 'get_query';
  			$query = $GLOBALS['sqler']->insert( $this->get_thor_table(), $values );
  			$result = db_query($query);
  			$GLOBALS['sqler']->mode = '';
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('save_values called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		elseif (empty($values))
  		{
  			trigger_error('save_values called but no the values array was empty');
  			return NULL;
  		}
	}
	
	function save_values_for_user($values, $username)
	{
		if ($this->get_thor_table() && $values && $username)
		{
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
  			$GLOBALS['sqler']->mode = 'get_query';
  			$query = $GLOBALS['sqler']->update_one( $this->get_thor_table(), $values, $username, 'submitted_by' );
  			$result = db_query($query);
  			$GLOBALS['sqler']->mode = '';
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('save_values_for_user called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		elseif (empty($values))
  		{
  			trigger_error('save_values_for_user called but no the values array was empty');
  			return NULL;
  		}
  		elseif (empty($username))
  		{
  			trigger_error('save_values_for_user called but the username array was empty');
  			return NULL;
  		}
	}
	
	function get_values_for_user($username)
	{
		$table = $this->get_thor_table();
		if ($this->get_thor_table() && $username)
		{
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
  			$q = 'SELECT * FROM '.$this->get_thor_table().' WHERE submitted_by = "'.$username.'"';
  			$res = mysql_query($q);
  			if (mysql_num_rows($res) > 0)
  			{
  				$result = mysql_fetch_assoc($res);
  			}
  			else $result = false;
  			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			return $result;
  		}
  		elseif (!$this->get_thor_table())
  		{
  			trigger_error('get_values_for_user called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
  		}
  		elseif (empty($username))
  		{
  			trigger_error('get_values_for_user called but the username passed was empty');
  			return NULL;
  		}
	}
	
	function get_output_data_for_screen($username)
	{
		$raw_data = $this->get_values_for_user($username);
		$display_values =& $this->get_display_values();
		
		//unset id and extra fields
		$unset_array = $this->_extra_fields;
		$unset_array[] = 'id';
		foreach ($unset_array as $v)
		{
			unset($raw_data[$v]);
		}
		
		foreach ($raw_data as $k=>$v)
		{
			if (isset($display_values[$k]))
			{
				if (isset($display_values[$k]['group_id']))
				{
					$group_id = $display_values[$k]['group_id'];
					if (isset($display_values[$group_id]) && !(empty($v)))
					{
						$user_data[$display_values[$group_id]['label']][] = $display_values[$k]['label'];
					}
				}
				else
				{
					$label = $display_values[$k]['label'];
					$user_data[$label] = $v;
				}
			}
		}
		return $user_data;
	}
	
	function get_output_data_for_email($username)
	{
		$raw_data = $this->get_values_for_user($username);
		$display_values =& $this->get_display_values();
		
		$unset_array[] = 'id';
		foreach ($unset_array as $v)
		{
			unset($raw_data[$v]);
		}
		
		foreach ($raw_data as $k=>$v)
		{
			if (isset($display_values[$k]))
			{
				if (isset($display_values[$k]['group_id']))
				{
					$group_id = $display_values[$k]['group_id'];
					if (isset($display_values[$group_id]) && !(empty($v)))
					{
						$user_data[$display_values[$group_id]['label']][] = $display_values[$k]['label'];
					}
				}
				else
				{
					$label = $display_values[$k]['label'];
					$user_data[$label] = $v;
				}
			}
		}
		return $user_data;
	}
	
	function get_create_table_sql()
	{
		$db_structure = $this->_build_db_structure();
		$q = 'CREATE TABLE ' . $this->get_thor_table() . '(`id` int(11) NOT NULL AUTO_INCREMENT, ';
		//$q .= '`formkey` tinytext NOT NULL , ';
		foreach ($db_structure as $k=>$v)
		{
			switch ($v['type']) {
			case 'tinytext':
   				$q .= '`'.$k.'` tinytext NOT NULL , ';
   				break;
			case 'enum':
   				$q .= '`'.$k.'` enum(';
   				foreach ($v['options'] as $option)
   				{
   					$q .= "'" . mysql_real_escape_string($option) . "',";
   				}
   				$q = substr( $q, 0, -1 ); // trim trailing comma
   				$q .= ') NULL , ';
   				break;
			case 'text':
   				$q .= '`'.$k.'` text NOT NULL , ';
   				break;
			}
		}
		$q .= '`submitted_by` tinytext NOT NULL , ';
		$q .= '`submitter_ip` tinytext NOT NULL , ';
		$q .= '`date_created` timestamp default 0 NOT NULL , ';
		$q .= '`date_modified` timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP , ';
		$q .= 'PRIMARY KEY(`id`)) TYPE = MYISAM;';
		return $q;
	}
	
	function table_exists()
	{
		if (!isset($this->_table_exists))
		{
			$table = $this->get_thor_table();
			if ($this->get_thor_table())
			{
				$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
				if ($reconnect_db) connectDB($this->get_db_conn());
  				$q = 'show tables like "'.$this->get_thor_table().'"';
  				$res = mysql_query($q);
  				if (mysql_num_rows($res) > 0) $this->_table_exists = true;
  				else $this->_table_exists = false;
  				if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
  			}
  			else
  			{
  				trigger_error('table_exists called but no table has been defined via the thorCore set_thor_table method');
  				return NULL;
  			}
  		}
  		return $this->_table_exists;
	}
	
	function create_table()
	{
		if ($this->get_thor_table() && !$this->table_exists())
		{
			$sql = $this->get_create_table_sql();
			$reconnect_db = (get_current_db_connection_name() != $this->get_db_conn()) ? get_current_db_connection_name() : false;
			if ($reconnect_db) connectDB($this->get_db_conn());
			$res = db_query($sql);
			if ($reconnect_db) connectDB($reconnect_db); // reconnect to default DB
			return true;
		}
		else
  		{
  			$error = ($this->get_thor_table()) ? 'The table already exists' : 'You need to define the table name using set_thor_table before calling create_table'; 
  			trigger_error($error);
  			return NULL;
  		}
	}
	
	function create_table_if_needed()
	{
		if ($this->get_thor_table() && !$this->table_exists())
		{
			return $this->create_table();
		}
		elseif (!$this->get_thor_table())
		{
			trigger_error('create_table_if_needed called but no table has been defined via the thorCore set_thor_table method');
  			return NULL;
		}
		return false;
	}
	
	/**
	 * Updated for php 4 and php 5 compatibility using Adam Flynn's XML Parser class instead of xpath
	 * - see http://www.criticaldevelopment.net/xml/
	 *
	 * @author Nathan White
	 */
	function _build_db_structure()
	{
		$xml = $this->get_thor_xml();
		foreach ($xml->document->tagChildren as $node) // we use tagName to make sure we iterate through them by order instead of type
		{	
			if ($node->tagName == 'input') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
			elseif ($node->tagName == 'textarea') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'text';
			}
			elseif ($node->tagName == 'hidden') {
			$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
			elseif (($node->tagName == 'radiogroup') || ($node->tagName == 'optiongroup')) {
				$db_structure[$node->tagAttrs['id']]['type'] = 'enum';
				$node_children = $node->tagChildren;
				foreach ($node_children as $node2) {
					$db_structure[$node->tagAttrs['id']]['options'][] = $node2->tagAttrs['value'];
				}
			}
			elseif ($node->tagName == 'checkboxgroup') {
				$node_children = $node->tagChildren;
				foreach ($node_children as $node2) {
					$db_structure[$node2->tagAttrs['id']]['type'] = 'tinytext';
				}
			}
		}
		return $db_structure;
	}
	
	function _build_display_values()
	{
		$xml = $this->get_thor_xml();
		$display_values = array();
		foreach ($xml->document->tagChildren as $k=>$v)
		{
			$tagname = is_object($v) ? $v->tagName : '';
			if (method_exists($this, '_build_display_'.$tagname))
			{
				$build_function = '_build_display_'.$tagname;
				$display_values = array_merge($display_values, $this->$build_function($v));
			}
		}
		foreach ($this->_extra_fields as $field_name)
		{
			$display_values[$field_name]['label'] = prettify_string($field_name);
			$display_values[$field_name]['type'] = 'text';
		}
		$this->_display_values = (isset($display_values)) ? $display_values : array();
	}

	/**
	 * Helper functions for _build_display_values()
	 * @access private
	 */
	 
	function _build_display_input($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'input';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}

	function _build_display_hidden($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'hidden';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
 
	function _build_display_textarea($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'textarea';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}

	function _build_display_checkboxgroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => 'checkboxgroup'); 
		$element_children = $element_obj->tagChildren;
		$type = 'checkbox';
		foreach ($element_children as $element_child) 
		{
			$child_attrs = $element_child->tagAttrs;
			$label = $child_attrs['label'];
			$display_values[$child_attrs['id']] = array('label' => $label, 'type' => $type, 'group_id' => $element_attrs['id'] );
		}
		return $display_values;
	}
	
	function _build_display_radiogroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$element_children = $element_obj->tagChildren;
		$id = $element_attrs['id'];
		$label = $element_attrs['label'];
		$type = 'radiogroup';
		foreach ($element_children as $element_child)
		{
			$child_attrs =& $element_child->tagAttrs;
			$options[] = $child_attrs['value'];
		}
		$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		return $display_values;
	}

	function _build_display_optiongroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$element_children = $element_obj->tagChildren;
		$id = $element_attrs['id'];
		$label = $element_attrs['label'];
		$type = 'optiongroup';
		foreach ($element_children as $element_child)
		{
			$child_attrs =& $element_child->tagAttrs;
			$options[] = $child_attrs['value'];
		}
		$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		return $display_values;
	}
	
	function _transform_input($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$size = (!empty($element->tagAttrs['size'])) ? $element->tagAttrs['size'] : 30;
		$maxlength = (!empty($element->tagAttrs['maxlength'])) ? $element->tagAttrs['maxlength'] : '';
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$default = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = array('size' => $size,
					  'maxlength' => $maxlength,
					  'display_name' => $display_name,
					  'default' => $default);

		$d->add_element($id, 'text', $args);
		if ( $required ) $d->add_required($id);
	}

	function _transform_hidden($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$value = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$d->add_element($id, 'hidden');
		$d->set_value($id, $value);
		$d->set_display_name($id, '(hidden field) ' . $display_name);
		if ( $required) $d->add_required($id);
	}
 
	function _transform_comment($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$args = Array('text' => $element->tagData);
		$d->add_element($id, 'comment', $args);
	}
 
	function _transform_textarea($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$rows = (!empty($element->tagAttrs['rows'])) ? $element->tagAttrs['rows'] : 6;
		$cols = (!empty($element->tagAttrs['cols'])) ? $element->tagAttrs['cols'] : 40;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$default = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = Array('rows' => $rows,
					  'cols' => $cols,
					  'display_name' => $display_name,
					  'default' => $default);

		$d->add_element($id, 'textarea', $args);
		if ( $required ) $d->add_required($id);
	}

	function _transform_radiogroup($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$args = Array( 'options' => Array(),
					   'display_name' => $display_name,
					   'default' => '' );

		$element_children = $element->tagChildren; 
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$args['options'][$value] = $value;
			if ( $selected ) $args['default'] = $value;
		}

		$d->add_element($id, 'radio_no_sort', $args);
		if ( $required ) $d->add_required($id);
	}

	function _transform_optiongroup($element, &$d)
	{
		$id = $element->tagAttrs['id'];
		$multiple = (!empty($element->tagAttrs['multiple'])) ? true : false;
		$size = (!empty($element->tagAttrs['size']) && ($element->tagAttrs['size'] > 1)) ? $element->tagAttrs['size'] : 1;
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		
		$args = array('options' => array(),
					  'multiple' => $multiple,
					  'size' => $size,
					  'default' => array(),
					  'display_name' => $display_name,
					  'add_null_value_to_top' => !$required);
					  
		$element_children = $element->tagChildren;
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$multiple = (!empty($element_child->tagAttrs['multiple'])) ? true : false;
			$args['options'][$value] = $value;
				
			if ( !$multiple && $selected ) $args['default'] = $value;
			else if ( $selected ) $args['default'][]= $value;
		}

		$d->add_element($id, 'select_no_sort', $args);
		if ( $required ) $d->add_required($id);
	}
	
	function _transform_checkboxgroup($element, &$d) {
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = array('options' => array(),
					  'display_name' => $display_name,
					  'default' => array());

		$index = 0;
		$element_children = $element->tagChildren;
		
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$id2 = $element_child->tagAttrs['id'];
			$args['options'][$value] = $value;
			
			if ( $selected ) $args['default'] []= $value;
			$d->add_element('transform['.$id.']['.$index.']', 'hidden');
			$d->set_value('transform['.$id.']['.$index.']', $id2);
			$index++;
		}

		$d->add_element($id, 'checkboxgroup_no_sort', $args);
		if ( $required ) $d->add_required($id);
	}
	
	function _transform_submit($element_attributes, &$d)
	{
		$submit = (!empty($element_attributes['submit'])) ? $element_attributes['submit'] : '';
		$reset = (!empty($element_attributes['reset'])) ? $element_attributes['reset'] : '';
		$d->actions = Array( 'submit' => $submit, 'reset' => $reset);
	}
}

/**
 * Thor: builds an html form out of xml data and handles processing of form inputs
 *
 * Example XML:
 * 
 * <pre>
 * <form email="fillmorn@carleton.edu" nextpage="http://www.carleton.edu" submit="Enter" reset="Clear">
 * 	<input id="first_name" value="Nathanael" size="20" maxlength="40" label="First name:" required="required" />
 * 	<hidden id="last_name" value="Fillmore" />
 * 	<textarea id="message" value="Dear Mr. President, You look like a monkey. Sincerely, Nathanael Fillmore"
 * 	          cols="40" rows="10" label="Message for the President:" required="required" />
 * 	<radiogroup id="affiliation" label="Are you a student, faculty, or staff?" required="required">
 * 		<radio selected="selected" value="student" label="Student" />
 * 		<radio value="faculty" label="Faculty" />
 * 		<radio value="staff" label="Staff" />
 * 	</radiogroup>
 * 	<checkboxgroup id="complaint" label="Which of these describes your complaint?" required="required">
 * 		<checkbox selected="selected" label="You're arrogant, Mr. President" value="arrogance" />
 * 		<checkbox selected="selected" label="You're a bad speaker, Mr. President" value="badspeaker" />
 * 		<checkbox label="Those monkey ears of yours are just too damn handsome, Mr. President" value="handsome" />
 * 	</checkboxgroup>
 * 	<optiongroup id="replacement" label="Who's your favorite replacement?" size="3" required="required">
 * 		<option value="john kerry" label="John Kerry" />
 * 		<option selected="selected" value="howard dean" label="Howard Dean" />
 * 	</optiongroup>
 * 	</form>
 *	</pre>
 *
 * Note: 4/10/2006 added ability to save form data to a database  - nwhite
 *
 * @author Nathanael Fillmore, Nate White
 * @deprecated use ThorLight
 * @since 26 November 2003
 */

class Thor
{
	var $_nextpage;
	var $_email;
	var $_xml;
	var $_html;
	var $_d;
	var $_db_conn;
	var $_table_name;
	var $_show_submitted_data;
	var $extra_fields = array('submitted_by', 'submitter_ip'); // extra fields that aren't represented in thor
	var $disco_thor_class = 'DiscoThor';

	function Thor($xml, $email, $nextpage, $db_conn = '', $table_name = '') {
		$this->_xml = $xml;
		$this->_email = $email;
		$this->_nextpage = $nextpage;
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;
	}

	function init()
	{
		$this->_build_form();
	}
	
	function set_db_conn($db_conn, $table_name)
	{
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;
	}
	
	function set_custom_disco_thor($thor_name)
	{
		$this->disco_thor_class = $thor_name;
	}
	
	function set_error_check($element, $error_mapping)
	{
	
	}
	
	function get_html() {
		$result = $this->_d->run();
		if ($this->_d->finished)
		{
			if ((!empty($this->_db_conn)) && (!empty($this->_table_name))) $this->save_to_db();
			
			if ($this->_show_submitted_data) 
			{
				if (!session_id()) session_start();
				$_SESSION['form_confirm'] = ($this->_d->get_values_array(false, false));
			}
			
			if (!empty($this->_email)) $this->_d->process_email(); // Tyr e-mail processing includes redirect so execute after database save
			else header( 'Location: ' . $this->_nextpage );
		}
	}
	
	function save_to_db()
	{
		//check if table exists
		if ($this->_check_table_exists() == false)
		{
			//create table
			$this->_create_db_table();
		}
		
		$db_save_array = conditional_stripslashes($_REQUEST);
		if (isset($db_save_array['transform']))
		{
			foreach ($db_save_array['transform'] as $k=>$v) //process checkbox transformations
			{
				foreach ($v as $k2=>$v2)
				{
					$db_save_array[$v2] = isset($db_save_array[$k][$k2]) ? $db_save_array[$k][$k2] : '';
				}
				unset ($db_save_array['transform'][$k]);
				unset ($db_save_array[$k]);
			}
		}
		foreach ($db_save_array as $k => $v)
		{
			if (substr($k, 0, 3) != "id_")
			{
				if (in_array($k, $this->extra_fields) == false) unset ($db_save_array[$k]);
			}
		}
		
		if (count($db_save_array) > 0)
		{
			connectDB($this->_db_conn);
			$GLOBALS['sqler']->mode = 'get_query';
			$query = $GLOBALS['sqler']->insert( $this->_table_name, $db_save_array );
			$result = mysql_query($query);
			if (mysql_error()) 
			{
				trigger_error('There was a problem saving thor form data to the table ' . $this->_table_name . ' at ' . get_current_url());
				echo '<h3>Error Saving Data</h3>';
				echo '<p>The data you submitted has not been saved or e-mailed. Please try the form again later. The Web Services group has been notified of the error and will resolve the problem as quickly as possible.</p>';
				$die = true;
			}
			else $die = false;
			$GLOBALS['sqler']->mode = '';
			connectDB(REASON_DB); // reconnect to default DB
			if ($die) die;
		}
	}
	
	function _check_table_exists()
	{
		$ret = true;
		connectDB($this->_db_conn);
  		$q = 'check table ' . $this->_table_name . ' fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  		$res = mysql_query($q);
  		$results = mysql_fetch_assoc($res);
  		if (strstr($results['Msg_text'],"doesn't exist") ) 
  		{
  			$ret = false;
  		}
  		connectDB(REASON_DB); // reconnect to default DB
  		return $ret;
	}
	
	function _create_db_table()
	{
		$db_structure = $this->_build_db_structure();
		
		connectDB($this->_db_conn); // connecting here so that mysql_real_escape_string() uses the right charset for the connection
		
		$q = 'CREATE TABLE ' . $this->_table_name . '(`id` int(11) NOT NULL AUTO_INCREMENT, ';
		//$q .= '`formkey` tinytext NOT NULL , ';
		foreach ($db_structure as $k=>$v)
		{
			switch ($v['type']) {
			case 'tinytext':
   				$q .= '`'.$k.'` tinytext NOT NULL , ';
   				break;
			case 'enum':
   				$q .= '`'.$k.'` enum(';
   				foreach ($v['options'] as $option)
   				{
   					$q .= "'" . mysql_real_escape_string($option) . "',";
   				}
   				$q = substr( $q, 0, -1 ); // trim trailing comma
   				$q .= ') NULL , ';
   				break;
			case 'text':
   				$q .= '`'.$k.'` text NOT NULL , ';
   				break;
			}
		}
		$q .= '`submitted_by` tinytext NOT NULL , ';
		$q .= '`submitter_ip` tinytext NOT NULL , ';
		$q .= '`date_created` timestamp NOT NULL , ';
		$q .= 'PRIMARY KEY(`id`)) TYPE = MYISAM;';
		$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
		connectDB(REASON_DB); // reconnect to default DB
	}
	
	/**
	 * Updated for php 4 and php 5 compatibility using Adam Flynn's XML Parser class instead of xpath
	 * - see http://www.criticaldevelopment.net/xml/
	 *
	 * @author Nathan White
	 */
	function _build_db_structure()
	{
		$xml = new XMLParser($this->_xml);
		$xml->Parse();
		foreach ($xml->document->tagChildren as $node) // we use tagName to make sure we iterate through them by order instead of type
		{	
			if ($node->tagName == 'input') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
			elseif ($node->tagName == 'textarea') {
				$db_structure[$node->tagAttrs['id']]['type'] = 'text';
			}
			elseif ($node->tagName == 'hidden') {
			$db_structure[$node->tagAttrs['id']]['type'] = 'tinytext';
			}
			elseif (($node->tagName == 'radiogroup') || ($node->tagName == 'optiongroup')) {
				$db_structure[$node->tagAttrs['id']]['type'] = 'enum';
				$node_children = $node->tagChildren;
				foreach ($node_children as $node2) {
					$db_structure[$node->tagAttrs['id']]['options'][] = $node2->tagAttrs['value'];
				}
			}
			elseif ($node->tagName == 'checkboxgroup') {
				$node_children = $node->tagChildren;
				foreach ($node_children as $node2) {
					$db_structure[$node2->tagAttrs['id']]['type'] = 'tinytext';
				}
			}
		}
		return $db_structure;
	}

	function _build_form()
	{

		$this->_d = new $this->disco_thor_class;
		$xml = new XMLParser($this->_xml);
		$xml->Parse();
		$this->_html = '';

		// II. Transform the form's elements
		foreach ($xml->document->tagChildren as $node)
		{
			if ($node->tagName == 'input') $this->_transform_input($node);
			elseif ($node->tagName == 'textarea') $this->_transform_textarea($node);
			elseif ($node->tagName == 'radiogroup') $this->_transform_radiogroup($node);
			elseif ($node->tagName == 'checkboxgroup') $this->_transform_checkboxgroup($node);
			elseif ($node->tagName == 'optiongroup') $this->_transform_optiongroup($node);
			elseif ($node->tagName == 'hidden') $this->_transform_hidden($node);
			elseif ($node->tagName == 'comment') $this->_transform_comment($node);
		}
		
		$this->_transform_submit($xml->document->tagAttrs);
		if (!empty($this->_email)) $this->_transform_email();
		$this->_transform_nextpage();
	}

	function _transform_input($element)
	{
		$id = $element->tagAttrs['id'];
		$size = (!empty($element->tagAttrs['size'])) ? $element->tagAttrs['size'] : 30;
		$maxlength = (!empty($element->tagAttrs['maxlength'])) ? $element->tagAttrs['maxlength'] : '';
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$default = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = array('size' => $size,
					  'maxlength' => $maxlength,
					  'display_name' => $display_name,
					  'default' => $default);

		$this->_d->add_element($id, 'text', $args);
		if ( $required ) $this->_d->add_required($id);
	}

	function _transform_hidden($element)
	{
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$value = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $value);
		$this->_d->set_display_name($id, '(hidden field) ' . $display_name);
		if ( $required) $this->_d->add_required($id);
	}
 
	function _transform_comment($element)
	{
		$id = $element->tagAttrs['id'];
		$args = Array('text' => $element->tagData);
		$this->_d->add_element($id, 'comment', $args);
	}
 
	function _transform_textarea($element)
	{
		$id = $element->tagAttrs['id'];
		$rows = (!empty($element->tagAttrs['rows'])) ? $element->tagAttrs['rows'] : 6;
		$cols = (!empty($element->tagAttrs['cols'])) ? $element->tagAttrs['cols'] : 40;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$default = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = Array('rows' => $rows,
					  'cols' => $cols,
					  'display_name' => $display_name,
					  'default' => $default);

		$this->_d->add_element($id, 'textarea', $args);
		if ( $required ) $this->_d->add_required($id);
	}

	function _transform_radiogroup($element)
	{
		$id = $element->tagAttrs['id'];
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$args = Array( 'options' => Array(),
					   'display_name' => $display_name,
					   'default' => '' );

		$element_children = $element->tagChildren; 
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$args['options'][$value] = $value;
			if ( $selected ) $args['default'] = $value;
		}

		$this->_d->add_element($id, 'radio_no_sort', $args);
		if ( $required ) $this->_d->add_required($id);
	}

	function _transform_optiongroup($element)
	{
		$id = $element->tagAttrs['id'];
		$multiple = (!empty($element->tagAttrs['multiple'])) ? true : false;
		$size = (!empty($element->tagAttrs['size']) && ($element->tagAttrs['size'] > 1)) ? $element->tagAttrs['size'] : 1;
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		
		$args = array('options' => array(),
					  'multiple' => $multiple,
					  'size' => $size,
					  'default' => array(),
					  'display_name' => $display_name,
					  'add_null_value_to_top' => !$required);
					  
		$element_children = $element->tagChildren;
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$multiple = (!empty($element_child->tagAttrs['multiple'])) ? true : false;
			$args['options'][$value] = $value;
				
			if ( !$multiple && $selected ) $args['default'] = $value;
			else if ( $selected ) $args['default'][]= $value;
		}

		$this->_d->add_element($id, 'select_no_sort', $args);
		if ( $required ) $this->_d->add_required($id);
	}
	
	function _transform_checkboxgroup($element) {
		$id = $element->tagAttrs['id'];
		$display_name = (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
		$required = (!empty($element->tagAttrs['required'])) ? true : false;
		
		$args = array('options' => array(),
					  'display_name' => $display_name,
					  'default' => array());

		$index = 0;
		$element_children = $element->tagChildren;
		
		foreach ($element_children as $element_child)
		{
			$value = (!empty($element_child->tagAttrs['value'])) ? $element_child->tagAttrs['value'] : '';
			$selected = (!empty($element_child->tagAttrs['selected'])) ? true : false;
			$id2 = $element_child->tagAttrs['id'];
			$args['options'][$value] = $value;
			
			if ( $selected ) $args['default'] []= $value;
			$this->_d->add_element('transform['.$id.']['.$index.']', 'hidden');
			$this->_d->set_value('transform['.$id.']['.$index.']', $id2);
			$index++;
		}

		$this->_d->add_element($id, 'checkboxgroup_no_sort', $args);

		if ( $required ) $this->_d->add_required($id);
	}

	function _transform_email() {
		$id = 'messages[0][to]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $this->_email);
		$this->_d->add_required($id);
	}

	function _transform_nextpage() {
		$id = 'messages[all][next_page]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $this->_nextpage);
		$this->_d->add_required($id);
	}

	function _transform_submit($element_attributes)
	{
		$submit = (!empty($element_attributes['submit'])) ? $element_attributes['submit'] : '';
		$reset = (!empty($element_attributes['reset'])) ? $element_attributes['reset'] : '';
		$this->_d->actions = Array( 'submit' => $submit, 'reset' => $reset);
	}
	
	function set_form_title($form_title) {
		$id = 'messages[all][form_title]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $form_title);
	}

	function set_form_subject($form_subject) {
		$id = 'messages[0][subject]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $form_subject);
	}
	
	function set_show_submitted_data($show) {
		$this->_show_submitted_data = $show;
	//	$id = 'messages[all][show_submitted_data]';
	//	$this->_d->add_element($id, 'hidden');
	//	$this->_d->set_value($id, $show);
	}
	
	function set_submitted_by($netID) {
		$this->_d->add_element('submitted_by', 'hidden');
		$this->_d->set_value('submitted_by', $netID);
	}
	
	function set_submitter_ip($ip) {
		$this->_d->add_element('submitter_ip', 'hidden');
		$this->_d->set_value('submitter_ip', $ip);
	}
	
	function set_value($element, $value)
	{
		$this->_d->set_value($element, $value);
	}
	
	function set_disco_error($element_label, $error)
	{
		$transform_array = $this->_magic_map_keys_to_id($element_label, false);
		if (!empty($transform_array))
		{
			$this->_d->error_checks[$transform_array[strtolower($element_label)]] = $error;
		}
	}
	
	/**
	 * Populate element_mappings array on the disco object - the element mappings array maps
	 * plain text strings corresponding to lowercased display names with "_" characters in place of spaces
	 * to thor unique identifiers.
	 *
	 * @var $prefix string - limits the size of the array mapped by specifying a prefix display names must contain
	 */
	function set_disco_element_mappings($prefix = '')
	{
		$transform_array = $this->_magic_map_keys_to_id($prefix, true);
		if (!empty($transform_array))
		{
			$this->_d->element_mappings = $transform_array;
		}
	}
	
	function change_element_type( $element, $new_type = '', $args = array() )
	{
		$this->_d->change_element_type( $element, $new_type, $args );
	}
	
	/**
	 * magic_transform sets values for disco thor elements according to the transform_array. The standard
	 * form for the transform array has keys that are the lowercase equivalent of form field labels with
	 * spaces replaced by underscores. If alter_string is set to false, keys should be equivalent to form
	 * field labels. If a prefix is specified, magic_transform will only transform values for form fields
	 * that have a label that begins with the prefix (case-insensitive).
	 *
	 * @param transform_array array mapping elements labels to the values that should be set
	 * @param prefix string optional prefix which limits fields to consider
	 * @param editable boolean are transformed fields editable or not?
	 * @param alter_string boolean should get true if keys in the transform array are in format 'this_is_my_label'
	 *
	 */
	function magic_transform($transform_array, $prefix, $editable = true, $alter_string = true)
	{
		$key_table = $this->_magic_map_keys_to_id($prefix, $alter_string);
		foreach ($transform_array as $k => $v)
		{
			if (!empty($key_table[$k]))
			{
				$cur_value = $this->_d->get_value($key_table[$k]);
				if (empty($cur_value))
				{
					$this->set_value($key_table[$k], $v);
					if ($editable == false)
					{
						$this->change_element_type($key_table[$k], 'solidtext', array());
					}
				}
			}
		}
	}
	
	/**
	 * _magic_map_keys_to_id is a helper function for magic_transform that creates an array mapping transform keys
	 * to disco ids. If a prefix is provided, only fields matching the prefix are mapped. If alter_string is true,
	 * display names are converted to lower case and spaces replaced with "_" characters in order to match the transform
	 * array.
	 *
	 * @access private
	 * @param prefix string match is case insensitive
	 * @param alter_string boolean default true
	 */
	function _magic_map_keys_to_id($prefix, $alter_string)
	{
		$mapping = array();
		foreach ($this->_d->_elements as $k=>$v)
		{
			if (!empty($prefix))
			{
				if (substr(strtolower($v->display_name), 0, strtolower(strlen($prefix))) == strtolower($prefix))
				{
					
					if ($alter_string) $key = str_replace(' ', '_', strtolower($v->display_name));
					else ($key = strtolower($v->display_name));
					$mapping[$key] = $k;
				}
			}
			else 
			{
				if ($alter_string) $key = str_replace(' ', '_', strtolower($v->display_name));
				else $key = strtolower($mapping[$v->display_name]);
				if ($key != strtolower($k))
				$mapping[$key] = $k;
			}
		}
		return $mapping;
	}
	
} // end class


?>
