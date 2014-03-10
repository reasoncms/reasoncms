<?php

/**
 * Thor Admin
 *
 * @package thor
 * @author nathan white
 *
 * Provides a view of thor form data with filtering, and view/edit/delete row action support
 *
 * This class is a bit dirty - but works - table admin should accept data model objects or something instead of needing extension
 *
 * @todo add support for data models to table admin and deprecate me
 */
include_once ( 'paths.php' );
include_once ( CARL_UTIL_INC . 'db/table_admin.php' );
include_once( THOR_INC . 'thor.php');
include_once( TYR_INC . 'tyr.php');

class ThorAdmin extends TableAdmin
{
	var $_thor_core;
	
	/**
	 * @var array extra fields to display that are not actually thor elements described in xml
	 * @todo we should grab this from the ThorCore probably
	 */
	var $extra_fields = array('id', 'submitted_by', 'submitter_ip', 'date_created', 'date_modified');
	
	function ThorAdmin()
	{
	}

	function set_thor_core(&$thor_core)
	{
		$this->_thor_core =& $thor_core;
	}
	
	function &get_thor_core()
	{
		return $this->_thor_core;
	}
	
	function init_thor_admin($thor_core = '')
	{
		if (empty($thor_core)) $thor_core =& $this->get_thor_core();
		if (is_object($thor_core))
		{
			$this->set_db_conn(THOR_FORM_DB_CONN);
			$this->set_table_name($thor_core->get_thor_table());
			$this->set_filename_frag($thor_core->get_thor_table());
		
			// grab information from the table and setup display value array
			if ($this->_check_table_exists())
			{
				$this->thor_build_display_values();
			
				// grab the request and set parameters accordingly
				$this->_set_params_from_request();
		
				// call appropriate init
				if (isset($this->table_action)) $this->init_action();
				elseif (isset($this->table_row_action) && isset($this->table_action_id) && $this->verify_table_action_id()) $this->init_row_action();
				else $this->init_default();
			}
		}
		else trigger_error('ThorAdmin needs to be provided with a thor_core object in order to run');
	}
	
	function thor_build_display_values()
	{
		// thor core method
		$tc =& $this->get_thor_core();
		$this->_display_values =& $tc->get_display_values();
	}
	
	/**
	 * TableAdmin should take an optional data model instead of overloading a private method ...
	 */
	function _delete_data()
	{
		$tc =& $this->get_thor_core();
		return $tc->delete_table();
	}
	

}

/**
 * Default Thor Admin Form
 *
 * Provides basic view / edit / create / delete privileges on a thor table
 *
 * @author Nathan White
 */

 class DiscoThorAdmin extends Disco
 {			
 	/**
 	 * The class requires a thor_core object
 	 */
	var $_thor_core;

	/**
	 * The real or spoofed netid
	 */
	var $_user_netid;
	
	/**
	 * Contains the current table action - if any
	 */
	var $table_action;
	
	/**
	 * Contains the current table action id - if any
	 */
	var $table_action_id;
	
	/**
	 * Contains the primary key column name
	 */
	var $primary_key_column;
	
	/**
	 * Default sort field
	 */
	var $default_sort_field;
	
	/**
	 * Whether or not to show thor hidden fields in the edit view
	 */
	var $show_hidden_fields_in_edit_view = false;
	
	/**
	 * DiscoThorAdmin requires a reference to a thor_core object to work properly, get display names, etc
	 */
	function set_thor_core(&$thor_core)
	{
		$this->_thor_core =& $thor_core;
	}
	
	function &get_thor_core()
	{
		return $this->_thor_core;
	}
	
	/**
	 * DiscoThorAdmin requires a reference to a real or spoofed netid
	 */
	function set_user_netid($user_netid)
	{
		$this->_user_netid = $user_netid;
	}
	
	function &get_user_netid()
	{
		return $this->_user_netid;
	}
	
	// if we do not have a thor_core object, take it from thor_admin
	function setup_form(&$thor_admin)
	{
		$this->set_action($thor_admin->get_table_row_action());
		$this->set_action_id($thor_admin->get_table_action_id());
		$this->set_primary_key_column($thor_admin->get_primary_key());
		if (!$this->get_thor_core()) $this->set_thor_core($thor_admin->get_thor_core());
		
		$tc =& $this->get_thor_core();
		$tc->append_thor_elements_to_form($this);
		$tc->apply_values_for_primary_key_to_form($this, $this->get_action_id(), $this->get_primary_key_column());
	}
	
	function on_every_time()
	{
		$action = $this->get_action();
		$id = $this->get_action_id();
		
		if ($action == 'view')
		{
			$this->on_every_time_view();
		}
		elseif ($action == 'edit')
		{
			$this->on_every_time_edit();
		}
		elseif ($action == 'delete')
		{
			$this->on_every_time_delete();
		}
	}
	
	/**
	 * Grab the data from thor core and display as a list
	 */
	function on_every_time_view()
	{
		$id = $this->get_action_id();
		$tc =& $this->get_thor_core();
		echo '<h3>Viewing row id ' . $id . '</h3>';
		$link = carl_make_link(array('table_row_action' => '', 'table_action_id' => ''));
		echo '<p class="summaryReturn"><a href="'.$link.'">Return to summary form data</a></p>';
		
		$data = $tc->get_values_for_primary_key($this->get_action_id());
		unset ($data['id']); // lets not show the id in this view
		$data = $tc->transform_thor_values_for_display($data);
		if ($data)
		{
			// we are going to use Tyr to format this up though it is a little silly ... 
			$tyr = new Tyr();
			$html = $tyr->make_html_table($data, false);
			echo $html;
		}
		else echo '<p>No data to display for this row</p>';
		
		// turn off display of the form
		$this->show_form = false;
	}
	
	/**
	 * @todo remove the substring search for transform after thor_core is updated so that checkbox transform fields are cloaked
	 */
	function on_every_time_edit()
	{
		$id = $this->get_action_id();
		echo '<h3>Editing row id ' . $id . '</h3>';
		$link = carl_make_link(array('table_row_action' => '', 'table_action_id' => ''));
		echo '<p class="summaryReturn"><a href="'.$link.'">Return to summary form data</a></p>';
		if ($this->show_hidden_fields_in_edit_view)
		{
			$elements = $this->get_element_names();
			foreach ($elements as $element_name)
			{
				$elm = $this->get_element($element_name);
				if ($elm->type == 'hidden' && (substr($elm->name, 0, 10) != 'transform[')) $this->change_element_type($element_name, 'text');
			}
		}
	}
	
	function on_every_time_delete()
	{
		$tc =& $this->get_thor_core();
		$id = $this->get_action_id();
		{
			$elements = $this->get_element_names();
			foreach ($elements as $element_name)
			{
				$this->remove_element($element_name);
			}
		}
		echo '<h3>Are you sure you want to delete row id ' . $id . '?</h3>';
		$this->actions = array('delete' => 'Confirm Delete', 'cancel' => 'Cancel');
		
		$data = $tc->get_values_for_primary_key($this->get_action_id());
		unset ($data['id']); // lets not show the id in this view
		$data = $tc->transform_thor_values_for_display($data);
		
		if ($data)
		{
			// we are going to use Tyr to format this up though it is a little silly ... 
			$tyr = new Tyr();
			$html = $tyr->make_html_table($data, false);
			echo $html;
		}
		else echo '<p>No data to display for this row</p>';
	}
	
	function run_error_checks()
	{
		parent::run_error_checks();
	}
	
	function process()
	{
		if ($this->get_action() == 'new') $this->process_new();
		elseif ($this->get_action() == 'edit') $this->process_edit();
		elseif ($this->get_action() == 'delete') $this->process_delete();
	}		
	function process_default() { parent::process(); }
	
	function process_new() 
	{
		$tc = $this->get_thor_core();
		$values = $tc->get_thor_values_from_form($this);
		$values['submitted_by'] = reason_check_authentication();
		$values['submitter_ip'] = $_SERVER['REMOTE_ADDR'];
		$values['date_created'] = get_mysql_datetime();
		$tc->insert_values($values);
	}
	
	/**
	 * @todo invoke ThorCore to save edits
	 */
	function process_edit()
	{ 
		$tc = $this->get_thor_core();
		$values = $tc->get_thor_values_from_form($this);
		$tc->update_values_for_primary_key($this->get_action_id(), $values);
	}
	
	/**
	 * invoke ThorCore to delete the row
	 */
	function process_delete()
	{
		if ($this->get_chosen_action() == 'delete')
		{
			$tc = $this->get_thor_core();
			$tc->delete_by_primary_key($this->get_action_id());
		}
	}
	
	function where_to()
	{
		$link = carl_make_redirect(array('table_row_action'=>'', 'table_action_id'=>''));
		return $link;
	}
	
	function set_primary_key_column($column)
	{
		$this->primary_key_column = $column;
	}

	function get_primary_key_column()
	{
		return $this->primary_key_column;
	}
	
	function set_action($action)
	{
		$this->table_action = $action;
	}

	function set_action_id($action_id)
	{
		$this->table_action_id = $action_id;
	}

	function get_action()
	{
		return $this->table_action;
	}
	
	function get_action_id()
	{
		return $this->table_action_id;
	}
	
	function get_default_sort_order()
	{
		return $this->default_sort_order;
	}
	
	function get_default_sort_field()
	{
		return $this->default_sort_field;
	}
}
?>
