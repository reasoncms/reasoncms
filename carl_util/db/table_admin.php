<?php
/**
 * Carl Table Admin
 *
 * @package carl_util
 * @subpackage db
 */

/**
 * include dependencies
 */
include_once( 'paths.php' );
include_once( CARL_UTIL_INC . 'db/db.php'); // Requires ConnectDB Functionality
include_once( CARL_UTIL_INC . 'basic/misc.php'); // Requires carl_clean_vars
include_once( DISCO_INC . 'disco_db.php'); // Requires Disco_DB

/**
 * Carl Table Admin
 *
 * @author nathan white
 *
 * Provides an interface to edit a database table, with support for a variety of operations
 * - row creation
 * - row editing
 * - row viewing
 * - row deletion
 * - data export (currently only .csv)
 * - data filtering
 *
 * A custom DiscoDB can optionally be provided in order to customize the interface for table row operations.
 *
 * Sample allowing editing and row deletion privileges
 * <code>
 * $table_admin = new TableAdmin();
 * $table_admin->set_allow_row_delete(true);
 * $table_admin->set_allow_edit(true);
 * $table_admin->init('my_db_conn', 'my_table_name');
 * $table_admin->run();
 * </code>
 *
 * Sample with a custom admin form
 *
 * <code>
 * $admin_form = new AdminCustomForm();
 * $table_admin = new TableAdmin();
 * $table_admin->set_admin_form($admin_form);
 * $table_admin->set_privileges_from_admin_form);
 * $table_admin->init('my_db_conn', 'my_table_name');
 * $table_admin->run();
 * </code>
 *
 * Sample sourced from a data array - note that sorting is done by mysql so will not work properly without some implementation work
 *
 * <code>
 * $table_admin = new TableAdmin();
 * $table_admin->init_from_array( array(0 => array('color' => 'red', 'shape' => 'square') ) );
 * $table_admin->run();
 * </code>
 *
 * @todo add pagination
 * @todo add more export formats
 * @todo implement archiving
 * @todo abstract out html generation into markup generator like system
 * @todo implement plug in data models
 * @todo allow filtering to take place in php or mysql
 */
class TableAdmin
{
	/**
	 * @var string populated with requested global table action
	 */
	var $table_action;
	/**
	 * @var string populated with requested table row action
	 */
	var $table_row_action;
	/**
	 * @var string which field label to sort
	 */
	var $sort_field = '';	
	/**
	 * @var string sort order to use
	 */
	var $sort_order = '';	
	/**
	 * @var string export mode currently only csv is supported
	 */
	var $export_format = 'csv'; 
	/**
	 * $var string default no data message
	 */
	var $default_no_data_message = '<p><strong>No data is available.</strong></p>';
	/**
	 * $var string no data message
	 */
	var $no_data_message;
	/**
	 * @var array data filters, indexed by column name
	 */
	var $fields_to_show;
	/**
	 * @var array if populated, only the named fields are converted - otherwise all are converted
	 */
	var $fields_to_entity_convert;
	/**
	 * @var array if populated, limits fields exported from summary view to field names in this array
	 */
	var $fields_to_export;
	/**
	 * @var array if populated, limits sortable fields to field names in this array
	 */
	var $fields_that_allow_sorting;
	/**
	 * @var boolean allow filters
	 */
	 var $filters = array();
	/**
	 * @var array if populated, limits fields shown in summary view to field names in this array
	 */
	var $allow_filters = true;
	/**
	 * @var boolean allow deletion
	 */ 
	var $allow_delete = false;	
	/**
	 * @var boolean allow row editing
	 */ 
	var $allow_row_delete = false;	
	/**
	 * @var boolean allow row editing
	 */ 
	var $allow_edit = false;
	/**
	 * @var boolean allow data export
	 */ 
	var $allow_export = true;
	/**
	 * @var boolean allow user to archive data
	 */ 
	var $allow_archive = true;
	/**
	 * @var boolean allow individual row viewing
	 */ 
	var $allow_view = true;
	/**
	 * @var boolean allow creation of new rows
	 */ 
	var $allow_new = false;
	/**
	 * @var boolean allow file downloads
	 */ 
	var $allow_download_files = false;	
	/**
	 * @var boolean whether or not to show the "Displaying x of y rows" header
	 */
	var $show_header = true;
	/**
	 * @var boolean whether or not to show action column in first table cell
	 */
	var $show_actions_first_cell = true;
	/**
	 * @var boolean whether or not to show action column in last table cell
	 */
	var $show_actions_last_cell = false;	
	/**
	 * @var object discoDB form that defines administrative interface and options - if unspecified a default with basic functionality is used
	 */
	var $admin_form = false;
	/**
	 * @var string primary key field of table
	 */
	var $primary_key = 'id'; // primary key field of table
	/**
	 * @var string name of table to store achive - not implemented yet
	 */ 
	var $history_table = 'table_history';
	/**
	 * @var string prefix to use in class/id attributes
	 */
	var $style_prefix = 'table_';
	/**
	 * @var filename_frag is appended to date-stamped filename when data is downloaded
	 */
	var $filename_frag = 'form';	
	/**
	 * @var if set provides static filename for downloaded files
	 */
	var $filename_real = '';
	/**
	 * @var array mapping column_names to type and display labels
	 * @access private
	 */ 
	var $_display_values = array();	
	/**
	 * @var int total number of rows in $table_name
	 */
	var $_total_rows;
	/**
	 * @var int total number of filtered rows
	 */
	var $_filtered_rows;
	/**
	 * @var array populated with a sample row when data is built - allows header row to render correctly if filtering gets rid of all rows
	 * @access private
	 */ 
	var $_row = array();
	/**
	 * @var string db connection string of database to connect with
	 */
	var $_db_conn;
	/**
	 * @var string original db connection string when class is inited - same as db_conn if no connection was present
	 */
	var $_orig_db_conn;
	/**
	 * @var string name of table to tame
	 */
	var $_table_name;
	/**
	 * @var array table data
	 */
	 var $_table_data;
	/**
	 * @var string target for file downloads
	 */
	 var $file_download_base_url = '/thor/getFormFile.php';	 
	/**
	 * @var array cleanup rules
	 */
	var $cleanup_rules = array('table_sort_order' => array('function' => 'check_against_array', 'extra_args' => array('asc', 'ASC', 'desc', 'DESC')),
							   'table_filters' => array('function' => 'turn_into_array'),
							   'table_filter_clear' => array('function' => 'turn_into_string'),
							   'table_action_id' => array('function' => 'check_against_regexp', 'extra_args' => array('naturalnumber')));
	
	var $_no_db_mode = false;
	/**
	 * Constructor
	 * @return void
	 */
	function TableAdmin()
	{
	}
	
	/**
	 * INIT METHODS
	 */
	
	/**
	 * 
	 */
	function init($db, $table_name, $filename_frag = '', $filename_real = '')
	{
		$this->set_db_conn($db);
		$this->set_table_name($table_name);
		$this->set_filename_frag($filename_frag);
		
		// grab information from the table and setup display value array
		if ($this->_check_table_exists())
		{
			$this->_build_display_values();
		
			// grab the request and set parameters accordingly
			$this->_set_params_from_request();
		
			// not implemented
			//$this->init_history();
			
			// call appropriate init
			if (isset($this->table_action)) $this->init_action();
			elseif (isset($this->table_row_action) && isset($this->table_action_id) && $this->verify_table_action_id()) $this->init_row_action();
			else $this->init_default();
		}
		else
		{
			trigger_error('The table ' . $this->get_table_name() . ' does not exist - using database connection ' . $this->get_db_conn());
		}
	}
	
	/**
	 * Init from array takes an array of display values and sets up the request and _display_values
	 */
	function init_view_no_db($display_values = array(), $custom_setup = false)
	{
		if (!$display_values) trigger_error('You must provide an array mapping field_name_keys to display_names for each column of the view you want to setup', FATAL);
		else
		{
			$this->_no_db_mode = true;
			$this->_build_display_values_from_array($display_values);
			
			if (!$custom_setup) // disable functions that are enabled by default class
			{
				$this->set_show_actions_first_cell(false);
				$this->set_allow_filters(false);
				$this->set_allow_export(false);
				$this->set_allow_view(false);
				$this->set_allow_archive(false);
				$this->set_show_header(false);
			}
			
			$this->_set_params_from_request();
			$this->init_default();
		}
	}
	
	/**
	 * If the data keys are the column headers, this can be used as a shortcut - just init and run
	 */
	function init_from_array($data, $custom_setup = false)
	{
		$labels = array_keys(current($data));
		foreach ($labels as $label)
		{
			$display_values[$label] = $label;
		}
		$this->init_view_no_db($display_values, $custom_setup);
		$this->set_data_from_array($data);
	}
	
	/**
	 * Init table global action
	 */
	function init_action()
	{
		$form =& $this->get_admin_form();
		if ($form) $form->set_action($this->get_table_action());
		
		switch ($this->get_table_action()) {
		case "delete_all":
			$this->get_data();
			$this->delete_form = new DiscoAdminDelete();
			break;
		case "export":
			$this->set_export_fields();
			break;
		case "batch_download":
			$data = $this->get_data();

			$fileCols = Array();
			$firstRow = current($data);
			foreach ($firstRow as $k => $col) {
				$type = (!empty($this->_display_values[$k]['type'])) ? $this->_display_values[$k]['type'] : 'text';
				if ($type == "file") {
					$fileCols[$k] = $this->_display_values[$k]["label"];
				}
			}
			$this->batch_download_form = new DiscoAdminBatchDownloader($this->get_table_name(), $fileCols, $data, $this->file_download_base_url);
			break;
		case "archive":
			break;
		}
	}
	
	/**
	 * Init table row action
	 */
	function init_row_action()
	{
		$form =& $this->get_admin_form();
		if (!$form) $form = new DiscoDefaultAdmin();
		$this->set_admin_form($form);
		$form->setup_form($this);
		// this is all discodb specific stuff ... should not really be here
		//$form->setup_db($this->get_db_conn(), $this->get_table_name(), $this->get_table_action_id()); 
		//$form->set_action($this->get_table_row_action());
		//$form->set_id_column_name($this->get_primary_key());
		
		$form->init();
	}
	
	/**
	 * Init when an allowable action is not specified
	 */
	function init_default()
	{
		// check on whether columns are limited
		if ($this->admin_form)
		{
			if (!$this->get_fields_to_show()) $this->set_fields_to_show(); // gets fields from admin form if it defines them
			if (!$this->get_fields_to_entity_convert()) $this->set_fields_to_entity_convert(); // gets the fields to entity convert if admin form defines them
			if (!$this->get_fields_that_allow_sorting()) $this->set_fields_that_allow_sorting(); // gets the fields that allow sorting admin form defines them
			if (!$this->get_sort_field()) $this->set_sort_field();
			if (!$this->get_sort_order()) $this->set_sort_order();
			if (!$this->get_no_data_message()) $this->set_no_data_message();
		}			
	}

	/**
	 * Create history table if it does not already exist
	 */
	function init_history()
	{
		if (!$this->_check_table_exists($this->history_table))
		{
			$this->_create_table_history_table();
		}
	}
	
	/**
	 * Handles all the internal logic for an instantiated table viewer - request variables will override any settings that correspond to a request
	 * variable that may have been specified prior to the init ...
	 */
	function _set_params_from_request()
	{
		// alter cleanup rules
		$this->cleanup_rules['table_sort_field'] = array('function' => 'check_against_array', 'extra_args' => array_keys($this->_display_values)); // dynamically add		
		$va = $this->_get_valid_actions();
		$vra = $this->_get_valid_row_actions();
		if (!empty($va)) $this->cleanup_rules['table_action'] = array('function' => 'check_against_array', 'extra_args' => $va);
		if (!empty($vra)) $this->cleanup_rules['table_row_action'] = array('function' => 'check_against_array', 'extra_args' => $vra);
		
		$this->request = carl_clean_vars(conditional_stripslashes($_REQUEST), $this->cleanup_rules);
		if (isset($this->request['table_action'])) 		 $this->set_action($this->request['table_action']);
		if (isset($this->request['table_row_action'])) 	 $this->set_row_action($this->request['table_row_action']);
		if (isset($this->request['table_action_id'])) 	 $this->set_action_id($this->request['table_action_id']);	
		if (isset($this->request['table_sort_order'])) 	 $this->set_sort_order($this->request['table_sort_order']);
		if (isset($this->request['table_sort_field'])) 	 $this->set_sort_field($this->request['table_sort_field']);
		if (isset($this->request['table_filters']))		 $this->set_filters($this->request['table_filters']);
		if (isset($this->request['table_filter_clear'])) $this->clear_filters($this->request['table_filters']);
	}
	
	function _get_valid_actions()
	{
		$actions = array();
		if ($this->allow_export) $actions[] = 'export';
		if ($this->allow_delete) $actions[] = 'delete_all';
		if ($this->allow_archive) $actions[] = 'archive';
		if ($this->allow_download_files) $actions[] = 'batch_download';
		return $actions;
	}
	
	function _get_valid_row_actions()
	{
		$actions = array();
		if ($this->allow_new) $actions[] = 'new';
		if ($this->allow_view) $actions[] = 'view';
		if ($this->allow_edit) $actions[] = 'edit';
		if ($this->allow_row_delete) $actions[] = 'delete';
		// if ($this->allow_download_file) $actions[] = 'download_file';
		return $actions;
	}
	
	// SET COMMANDS THAT PASS THRU TO ADMIN CLASS METHODS IF THEY EXISTS
	function set_fields_to_show($fields_to_show_array = NULL)
	{
		if (!is_null($fields_to_show_array)) $this->fields_to_show = $fields_to_show_array;
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_fields_to_show'))
				{
					$this->fields_to_show = $af->get_fields_to_show();
				}
			}
		}	
	}
	
	// SET COMMANDS WITH SOME LOGIC
	function set_fields_to_entity_convert($fields_to_entity_convert_array = NULL)
	{
		if (!is_null($fields_to_entity_convert_array)) $this->fields_to_entity_convert = $fields_to_entity_convert_array;
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_fields_to_entity_convert'))
				{
					$this->fields_to_entity_convert = $af->get_fields_to_entity_convert();
				}
			}
		}	
	}
	
	function set_export_fields($export_fields_array = NULL)
	{
		if (!is_null($export_fields_array)) $this->fields_to_export = $export_fields_array;
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_fields_to_export'))
				{
					$this->fields_to_export = $af->get_fields_to_export();
				}
			}
		}	
	}
	
	function set_fields_that_allow_sorting($fields_that_allow_sorting_array = NULL)
	{
		if (!is_null($fields_that_allow_sorting_array)) $this->fields_that_allow_sorting = $fields_that_allow_sorting_array;
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_fields_that_allow_sorting'))
				{
					$this->fields_that_allow_sorting = $af->get_fields_that_allow_sorting();
				}
			}
		}
		
	}
	
	function set_no_data_message($no_data_message = NULL)
	{
		if (!is_null($no_data_message)) $this->no_data_message = $no_data_message;
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_no_data_message'))
				{
					$this->no_data_message = $af->get_no_data_message();
				}
			}
		}		
	}
	
	function set_sort_field($field = NULL)
	{
		if (!is_null($field)) $this->sort_field = $field;
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_default_sort_field'))
				{
					$this->sort_field = ($af->get_default_sort_field()) ? $af->get_default_sort_field() : 'id';
				}
				
			}
		}
		if (empty($this->sort_field)) $this->sort_field = 'id';
	}
	
	function set_sort_order($order = NULL)
	{
		//only 'desc' or 'asc' are valid -- if invalid default to 'desc'
		if (!is_null($order)) $this->sort_order = ((strtolower($order) == 'desc') || (strtolower($order) == 'asc')) ? strtolower($order) : 'desc';
		else
		{
			if ($af =& $this->get_admin_form())
			{
				if (method_exists($af, 'get_default_sort_order'))
				{
					$this->sort_order = ((strtolower($order) == 'desc') || (strtolower($order) == 'asc')) ? strtolower($order) : 'desc';
				}
			}
		}
		if (empty($this->sort_order)) $this->sort_order = 'desc';
	}
	
	// BASIC SET COMMANDS
	function set_allow_edit($val = NULL)
	{
		if (!is_null($val)) $this->allow_edit = $val;
	}
	
	function set_allow_view($val = NULL)
	{
		if (!is_null($val)) $this->allow_view = $val;
	}
	
	function set_allow_delete($val = NULL)
	{
		if (!is_null($val)) $this->allow_delete = $val;
	}
	
	function set_allow_row_delete($val = NULL)
	{
		if (!is_null($val)) $this->allow_row_delete = $val;
	}

	function set_allow_download_files($val = NULL)
	{
		if (!is_null($val)) $this->allow_download_files = $val;
	}
	
	function set_allow_export($val = NULL)
	{
		if (!is_null($val)) $this->allow_export = $val;
	}
	
	function set_allow_archive($val = NULL)
	{
		if (!is_null($val)) $this->allow_archive = $val;
	}
	
	function set_allow_new($val = NULL)
	{
		if (!is_null($val)) $this->allow_new = $val;
	}
	
	function set_allow_filters($val = NULL)
	{
		if (!is_null($val)) $this->allow_filters = $val;
	}

	function set_show_header($val = NULL)
	{
		if (!is_null($val)) $this->show_header = $val;
	}
	
	function set_show_actions_first_cell($val = NULL)
	{
		if (!is_null($val)) $this->show_actions_first_cell = $val;
	}
	
	function set_show_actions_last_cell($val = NULL)
	{
		if (!is_null($val)) $this->show_actions_first_cell = $val;
	}
	
	function set_export_format($val = NULL)
	{
		if (!is_null($val)) $this->export_format = $val;
	}
	
	function set_admin_form(&$form)
	{
		$this->admin_form =& $form;
	}
	
	function set_db_conn($db_conn)
	{
		$orig = get_current_db_connection_name();
		$this->_db_conn = $db_conn;
		$this->_orig_db_conn = ($orig) ? $orig : $db_conn;
	}
	
	function set_table_name($table_name)
	{
		$this->_table_name = $table_name;
	}

	function set_filename_real($filename)
	{
		$this->filename_real = $filename;
	}
	
	function set_filename_frag($filename_frag)
	{
		$this->filename_frag = $filename_frag;
	}
	
	/**
	 * Pagination is not yet implemented
	 */
	function set_page($input)
	{
		$this->page = $input;
	}
	
	function set_num_per_page($num_per_page)
	{
		$this->num_per_page = $num_per_page;
	}
	
	function set_filters($filter_array = array())
	{
		if (!empty($filter_array))
		{
			foreach ($filter_array as $k=>$v)
			{
				$v = trim($v);
				if (strlen($v)) $this->filters[$k] = array('name' => $k, 'value' => $v);
			}
		}
	}
	
	function clear_filters($filter_array = array())
	{
		if (!empty($filter_array))
		{
			foreach ($filter_array as $k=>$v)
			{
				$v = trim($v);
				if (strlen($v)) $this->filters[$k] = array('name' => $k, 'value' => '');
			}
		}
	}
	
	function set_action($string)
	{
		$this->table_action = $string;		
	}
	
	function set_row_action($string)
	{
		$this->table_row_action = $string;	
	}
	
	function set_action_id($id)
	{
		$this->table_action_id = $id;
	}
	
	/**
	 * Sets permissions based upon a custom_admin_form that defines the method is_allowable_action($action)
	 */
	function set_privileges_from_admin_form()
	{	
		$form =& $this->get_admin_form();
		if ($form)
		{
			if (method_exists($form, 'is_allowable_action'))
			{
				$this->set_allow_view($form->is_allowable_action('view'));
				$this->set_allow_edit($form->is_allowable_action('edit'));
				$this->set_allow_delete($form->is_allowable_action('delete'));
				$this->set_allow_row_delete($form->is_allowable_action('row_delete'));
				$this->set_allow_export($form->is_allowable_action('export'));
				$this->set_allow_new($form->is_allowable_action('new'));
			}
			else trigger_error('The custom form must define an is_allowable_action method');
		}
		else trigger_error('A custom form must be assigned using the set_admin_form method before calling set_privileges_from_admin_form.');
	}
	
	/**
	 * Set data sets _table_data
	 */
	function set_data_from_array(&$data)
	{
		$this->_row = current($data);
		$this->_total_rows = count($data);
		$this->_filter_data($data);
		$this->_filtered_rows = count ($data);
		$this->_table_data =& $data;
	}
	
	/**
	 * verify that the table_action_id requested is valid
	 */
	function verify_table_action_id()
	{
		$id = $this->table_action_id;
		$data =& $this->get_data();
		if ((isset($data[$id]) && ($this->table_row_action != 'new')) || ($this->allow_new && ($id == 0)))
		{
			return true;
		}
		else return false;	
	}
	
	function verify_row_action()
	{
	
	}

	/**
	 * ALL THE CLASS GET METHODS
	 */
	 
	/**
	 * @return array unfiltered table data, according to sort_field and sort_order - uses _build_data to generate the data
	 */
	function &get_data()
	{
		if (isset($this->_table_data)) return $this->_table_data;
		elseif (!$this->_no_db_mode)
		{
			$this->_table_data =& $this->_build_data();
		}
		else
		{
			trigger_error('You must pass the data using the set_data_from_array method before get_data will return data in no_db_mode');
		}
		return $this->_table_data;
	}

	function &get_filters()
	{
		return $this->filters;
	}
	
	function &get_admin_form()
	{
		return $this->admin_form;
	}
	
	function &get_fields_to_show()
	{
		return $this->fields_to_show;
	}
	
	function &get_fields_to_entity_convert()
	{
		return $this->fields_to_entity_convert;
	}
	
	function &get_fields_that_allow_sorting()
	{
		return $this->fields_that_allow_sorting;
	}
	
	/**
	 * @return string the current table global action
	 */
	function get_table_action()
	{
		return $this->table_action;
	}
	
	/**
	 * @return string the current table row action
	 */
	function get_table_row_action()
	{
		return $this->table_row_action;
	}
	
	/**
	 * @return string the current table row action id
	 */
	function get_table_action_id()
	{
		return (isset($this->table_action_id)) ? $this->table_action_id : false;
	}
	
	/**
	 * @return string table name used by the class
	 */
	function get_table_name()
	{
		return $this->_table_name;
	}
	
	/**
	 * @return string db conn string used by the class
	 */
	function get_db_conn()
	{
		return $this->_db_conn;
	}
	
	function get_orig_db_conn()
	{
		return $this->_orig_db_conn;
	}
	
	function get_primary_key()
	{
		return $this->primary_key;
	}
	
	function get_sort_field()
	{
		return $this->sort_field;
	}
	
	function get_sort_order()
	{
		return $this->sort_order;
	}

	function get_no_data_message()
	{
		return $this->no_data_message;
	}

	function get_field_name_from_label($label)
	{
		static $labels;
		if (!empty($labels[$label])) return $labels[$label];
	
		foreach ($this->_display_values as $field => $details)
			$labels[$details['label']] = $field;

		if (!empty($labels[$label])) return $labels[$label];
		
		return false;
	}
			
	/**
	 * @return boolean true if the row count of unfiltered data is greater than 0
	 */
	function has_data()
	{
		if ($this->_total_rows > 0) return true;
		else return false;
	}
	
	/**
	 * build_data reads in all values stored from a table form according to sort_field and sort_order
	 * @return $my_data array of associative arrays which represent each row
	 */
	function &_build_data()
	{
		$tn = $this->get_table_name();
		$sf = $this->get_sort_field();
		$so = $this->get_sort_order();
		$pk = $this->get_primary_key();
		$cols = $this->build_columns();
		if ($this->_check_table_exists())
		{
			$q = 'SELECT '.$cols.' FROM '. $tn;
			if (!empty($sf)) $q .= ' ORDER BY ' . $sf . ' ' . $so;
			// echo "QUERY [" . $q . "], db [" . $this->get_db_conn() . "], org [" . $this->get_orig_db_conn() . "]";
			connectDB($this->get_db_conn());
			$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
			connectDB($this->get_orig_db_conn()); // reconnect to default DB
			if (mysql_num_rows( $res ) > 0 )
			{
				$af =& $this->get_admin_form();
				$transforms = (method_exists($af, 'does_form_transform_data') && $af->does_form_transform_data());
				while($row = mysql_fetch_assoc($res))
				{
					if ($transforms) $af->transform_data($row);
					if ($row) $my_data[$row[$pk]] = $row;
				}
				$this->_row = current($my_data);
				$this->_total_rows = count($my_data);
				$this->_filter_data($my_data);
				//$my_data = $this->_filter_data_mysql();
				$this->_filtered_rows = count ($my_data);
			}
		}
		else
		{
			$this->_total_rows = $this->_filtered_rows = 0;
			$my_data = array();
		}
		return $my_data;
	}
	
	/**
	 * _filter_data modifies data according to an array of filters, and allows modules to apply filtering rules unrelated to user input 
	 * @param &$data
	 * @param $filter_array
	 */
	function _filter_data(&$data, $filter_array = NULL)
	{
		$filter_array = ($filter_array == NULL) ? $this->get_filters() : $filter_array; // want to operate on a copy of the filter array
		if (!empty($filter_array) && $this->allow_filters)
		{
			$active_filter = array_pop($filter_array);
			
			if ($active_filter['name'] == 'id')
			{
				foreach ($data as $k => $v)
				{
					if ($v[$active_filter['name']] != $active_filter['value']) unset ($data[$k]);
				}
			}
			elseif ($active_filter['value'] == '*NULL*')
			{
				foreach ($data as $k => $v)
				{
					if (!empty($v[$active_filter['name']])) unset ($data[$k]);
				}
			}
			elseif (strlen($active_filter['value']) > 0)
			{
				foreach ($data as $k => $v) 
				{
					if (strpos(strtolower($v[$active_filter['name']]), strtolower($active_filter['value'])) === false) unset ($data[$k]);
				}
			}
			if (count($filter_array) > 0) $this->_filter_data($data, $filter_array);
		}
	}
	
	/**
	 * build_filters builds the WHERE clause of a query string based upon the class array filters
	 *
	 * This function is currently not being used by the class, as all filtering is handled in code rather than queries
	 *
	 * @todo implement
	 * @return string
	 */
	function _filter_data_mysql()
	{
		$ret = '';
		$filters = $this->get_filters();
		if (count($filters) > 0)
		{
			$ret = ' WHERE';
			foreach ($filters as $k=>$v)
			{
				if (!empty($v)) $ret .= ' '. $k . ' LIKE ' . $v . ',';		
			}
			$ret = substr($ret, 0, -1); //trim trailing comma
		}
		echo $ret;
		die;
		return $ret;
	}

    /**
	 * Generates HTML for two table rows - one with input box to control filtering for each column, and the other for "apply filter" and "clear filter" buttons
	 * @param array $row can be any row from the data since the keys are always the same
	 * @todo add hidden labels for each filtering field for accessibility
	 * @return string HTML for the 2 filter related rows
	 */
	function gen_filter_rows($row)
	{
		if (isset($this->fields_to_show)) $this->limit_columns($row);
		$first = ' class="first"';
		$ret = '<tr>';
		$count = 0;
		if ($this->show_actions_first_cell)
		{
			$count++;
			$ret .= '<td'.$first.'><br/></td>';
			$first = '';
		}
		foreach ($row as $k => $v)
		{	
			$type = (!empty($this->_display_values[$k]['type'])) ? $this->_display_values[$k]['type'] : 'text';
			$cur_value = (!empty($this->filters[$k])) ? htmlspecialchars($this->filters[$k]['value'],ENT_QUOTES,'UTF-8') : '';
			$ret .= '<td'.$first.'>';
			if (($type == 'radiogroup') || ($type == 'optiongroup') || ($type == 'enum'))
			{
				$selected = '';
				$ret .= '<select name="table_filters['.$k.']">';
				$ret .= '<option value="">---</option>';
				foreach ($this->_display_values[$k]['options'] as $v2)
				{
					$selected = ($cur_value == $v2) ? ' SELECTED' : '';
					$ret .= '<option value = "'.$v2.'"'.$selected.'>'.$v2.'</option>';
				}
				$ret .= '</select>';
			}
			else
			{
				$ret .= '<input type="text" name="table_filters['.$k.']" value="'.$cur_value.'" size="10" /></td>';
			}
			$count++;
			$first = '';
		}
		if ($this->show_actions_last_cell)
		{
			$count++;
			$ret .= '<td><br/></td>';
		}
		$ret .= '</tr>';
		$ret .= '<tr>';
		$ret .= '<td class="filterButtons" colspan='.$count.'>';
		$ret .= '<input type="submit" name="filter_submit" value="Apply Filters"> <input type="submit" name="table_filter_clear" value="Clear Filters" />';
		$ret .= '</td>';
		$ret .= '</tr>';
		return $ret;
	}

	/**
	 * Generates HTML for a table row containing column headers
	 * @param array $header_row can be any row from the data since the keys are always the same
	 * @todo add mechanism to pad to certain length
	 * @return string HTML for the header row
	 */
	function gen_header_row($header_row)
	{
		if (isset($this->fields_to_show)) $this->limit_columns($header_row);
		$first = ' class="first"';
		$order_display_name = array('asc' => 'Sort Ascending', 'desc' => 'Sort Descending');
		//$parts = parse_url(get_current_url());
		//$base_url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?mode=data_view&';
		$ret = '<tr class="head">';
		if ($this->show_actions_first_cell)
		{
			$ret .= '<th'.$first.'>Action</th>';
			$first = '';
		}

		foreach ($header_row as $k => $v)
		{
			if ($this->field_is_sortable($k))
			{
				if (($this->sort_field == $k) && ($this->sort_order == 'asc')) $order = 'desc';
				else $order = 'asc';
				$url_array = array('table_sort_field' => $k, 'table_sort_order' => $order, 'table_export_format' => '', 'table_action' => '', 'table_row_action' => '', 'table_action_id' => '');
				$this->parse_filters_for_url($url_array);
				$url = carl_make_link($url_array);
			}
			else $url = '';
			$v = (isset($this->_display_values[$k])) ? $this->_display_values[$k]['label'] : $k;
			$ret .= '<th'.$first.'>';
			if ($url) $ret .= '<a href="'.$url.'" title="'.$order_display_name[$order].'">';
			$ret .= htmlspecialchars($v,ENT_QUOTES,'UTF-8');
			if ($url) $ret .= '</a>';
			$ret .= '</th>';
			$first = '';
		}
		if ($this->show_actions_last_cell)
		{
			$ret .= '<th>Action</th>';
		}
		$ret .= '</tr>';
		return $ret;
	}
	
	function field_is_sortable($k)
	{
		return (!isset($this->fields_that_allow_sorting)) ? true : in_array($k, $this->fields_that_allow_sorting);
	}
	
	/**
	 * Generates HTML for a data row
	 * @param array $header_row can be any row from the data since the keys are always the same
	 * @return string HTML for the data row
	 *
	 */
	function gen_data_row($data_row, $class)
	{
		$row_id = (isset($data_row[$this->primary_key])) ? $data_row[$this->primary_key] : false;
		if (isset($this->fields_to_show)) 
		{
			$this->limit_columns($data_row);
		}
		$first = ' class="first"';
		$ret = '<tr class="'.$class.'">';
		if ($row_id && (strlen($row_id) > 0))
		{
			$row_actions = $this->get_row_actions($data_row, $row_id);
			$row_action_html = (!empty($row_actions)) ? $this->get_row_action_html($row_actions, $row_id) : 'None Available';
		}
		if ($this->show_actions_first_cell && isset($row_action_html) )
		{
			$ret .= '<td'.$first.'>'.$row_action_html.'</td>';
			$first = '';
		}
		foreach ($data_row as $k=>$v)
		{
			$v = $this->should_convert_field($k) ? htmlspecialchars($v,ENT_QUOTES,'UTF-8') : $v;	
			$v = (strlen($v) > 0) ? $v : '<br />';

			// if it's a file, let's make it downloadable
			$type = (!empty($this->_display_values[$k]['type'])) ? $this->_display_values[$k]['type'] : 'text';
			if ($type == "file") {
				$link_params = array('table' => $this->get_table_name(), 'row' => $row_id, 'col' => $k, 'filename' => $v);
				$v = "<a href='" . carl_make_link($link_params, $this->file_download_base_url) . "'>$v</a>";
			}

			$ret .= '<td'.$first.'>'.$v.'</td>';
			$first = '';
		}
		if ($this->show_actions_last_cell && isset($row_action_html) )
		{
			$ret .= '<td>'.$row_action_html.'</td>';
		}
		$ret .= '</tr>';
		return $ret;
	}
	
	function should_convert_field($k)
	{
		if (isset($this->_fields_to_entity_convert[$k])) return $this->_fields_to_entity_convert[$k];
		else
		{
			if (isset ($this->fields_to_entity_convert) && in_array($k, $this->fields_to_entity_convert))
			{
				$this->_fields_to_entity_convert[$k] = true;
			}
			elseif (isset ($this->fields_to_entity_convert))
			{
				$this->_fields_to_entity_convert[$k] = false;
			}
			else $this->_fields_to_entity_convert[$k] = true;
		}
		return $this->_fields_to_entity_convert[$k];
	}
	
	/**
	 * limit_columns accepts a row by reference, and removes fields that are set to not display
	 * @param array row to consider
	 * @return void
	 */
	function limit_columns(&$row)
	{
		$tn = $this->get_table_name();
		$dbc = $this->get_db_conn();
		if (!isset($this->_fields_to_unset[$dbc][$tn]))
		{
			$row_fields = array_keys($row);
			$this->_fields_to_unset[$dbc][$tn] = array_diff($row_fields, $this->fields_to_show);
		}
		foreach ($this->_fields_to_unset[$dbc][$tn] as $z) { unset($row[$z]); }
	}
	
	/**
	 * limit_columns accepts a row by reference, and removes fields that are set to not display
	 * @param array row to consider
	 * @return void
	 */
	function limit_export_columns(&$row)
	{
		if (!isset($this->_fields_to_unset))
		{
			$row_fields = array_keys($row);
			$this->_fields_to_unset = array_diff($row_fields, $this->fields_to_export);
		}
		foreach ($this->_fields_to_unset as $z) { unset($row[$z]); }
	}
	
	/**
	 * Returns array with key / value pairs indicating label and link to actions
	 *
	 * Current the following row actions are supported if enabled
	 *
	 * - view / provides a view of a data row with fields converted to solidtext
	 * - edit / provides an editing interface for a single row
	 * - delete / provides ability to delete single row
	 *
	 * @todo consider using basic replacement functions since this is being called once per row and only the row_id will change
	 */
	function get_row_actions(&$data_row, $row_id)
	{
		$row_actions = array();
		
		// view
		if ($this->allow_view)
		{
			$link_params1 = array('table_row_action' => 'view', 'table_action_id' => $row_id, 'table_action' => '');
			$this->parse_filters_for_url($link_params1);
			$row_actions['View'] = carl_make_link($link_params1);
		}
		
		// edit
		if ($this->allow_edit)
		{
			$link_params2 = array('table_row_action' => 'edit', 'table_action_id' => $row_id, 'table_action' => '');
			$this->parse_filters_for_url($link_params2);
			$row_actions['Edit'] = carl_make_link($link_params2);
		}
		
		// delete
		if ($this->allow_row_delete)
		{
			$link_params3 = array('table_row_action' => 'delete', 'table_action_id' => $row_id, 'table_action' => '');
			$this->parse_filters_for_url($link_params3);
			$row_actions['Delete'] = carl_make_link($link_params3);
		}
		return $row_actions;
	}
	
	/**
	 * Iterate through row actions array and return a nice chunk of html
	 *
	 * Current the following row actions are supported if enabled
	 *
	 * - view / provides a view of a data row with fields converted to solidtext
	 * - edit / provides an editing interface for a single row
	 * - delete / provides ability to delete single row
	 */
	function get_row_action_html(&$row_actions, $row_id)
	{
		foreach ($row_actions as $label=>$link)
		{
			$action[] = '<a href="'.$link.'" title="'.$label.' row '. $row_id .'">'.$label.'</a>';
		}
		return implode(" | ", $action);
	}

	// returns true if this form contains any file elements
	function form_contains_file_elements() {
		if ($data_row = current($this->get_data()))
		{
			foreach ($data_row as $k=>$v) {
				$type = (!empty($this->_display_values[$k]['type'])) ? $this->_display_values[$k]['type'] : 'text';
				if ($type == "file") {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Generates HTML to represent the stored data which corresponds to a thor form
	 * @param array $data_array
	 * @param boolean $filters whether or not to run filters
	 * @return string $ret HTML for table
	 */
	function gen_table_html($data_array, $filters = true)
	{
		$ret = '';
		$class = 'odd';
		$header = $this->gen_header_row($this->_row);
		$links_base = $links_export_all = $links_new = $this->gen_menu_links_base();
		$links_export = $this->get_menu_links_base_with_filters();
		$links_delete['table_action'] = 'delete_all';
		$links_delete['table_sort_order'] = '';
		$links_delete['table_sort_field'] = '';
		$links_new['table_sort_order'] = '';
		$links_new['table_sort_field'] = '';
		$links_new['table_row_action'] = 'new';
		$links_new['table_action_id'] = '0';
		//$links_export_all['table_export_format'] = 'csv'; // does not consider filtering
		$links_export_all['table_action'] = 'export';
		if ($this->allow_export) $menu_links['Export Stored Data'] = carl_make_link($links_export_all);
		if ($this->_filtered_rows > 0 && ($this->_filtered_rows != $this->_total_rows))
		{
			$num_string = ($this->_filtered_rows == 1) ? '1 Item' : $this->_filtered_rows . ' Items';
			//$links_export['table_export_format'] = 'csv';
			if ($this->allow_export)
			{
				$links_export['table_action'] = 'export';
				$menu_links['Export Found Set ('.$num_string.')'] = carl_make_link($links_export);
			}
		}

		$links_batchdownload = $this->gen_menu_links_base();
		$links_batchdownload['table_action'] = 'batch_download';
		if ($this->allow_delete) $menu_links['Delete Stored Data'] = carl_make_link($links_delete);
		if ($this->allow_new) $menu_links['Create New Row'] = carl_make_link($links_new);
		if ($this->allow_download_files && $this->form_contains_file_elements()) $menu_links['Batch Download Attachments'] = carl_make_link($links_batchdownload);
		if ($this->show_header) $ret .= '<h3>Displaying '.$this->_filtered_rows.' of '.$this->_total_rows.' rows</h3>';
		if (!empty($menu_links)) $ret .= $this->gen_menu($menu_links);
		$form_open_string = '<form name="search" action="'.htmlspecialchars(get_current_url()).'" method="post">';
		$ret .= $form_open_string;
		$ret .= '<table class="table_data">';
		$ret .= $header;
		if ($this->allow_filters) $ret .= $this->gen_filter_rows($this->_row);
		foreach ($data_array as $data_row)
		{
			$class = ($class == 'odd') ? 'even' : 'odd';
			$ret .= $this->gen_data_row($data_row, $class);
		}
		$ret .= '</table>';
		$ret .= '</form>';
		return $ret;
	}

	/**
	 * Needs to preserve proper request variables relevant to this module and not provide a link to whatever is currently requested
	 */
	function gen_menu_links_base()
	{
		$links_base = array('table_sort_field' => $this->sort_field, 'table_sort_order' => $this->sort_order, 'table_filters_clear' => '', 'table_action' => '', 'table_row_action' => '', 'table_action_id' => '', 'table_export_format' => '');
		return $links_base;
	}
	
	function get_menu_links_base_with_filters()
	{
		$links = $this->gen_menu_links_base();
		$this->parse_filters_for_url($links);
		return $links;
	}
	
	function gen_menu($link_array)
	{
		foreach ($link_array as $k=>$v)
		{
			if (!empty($v))
			{
				$links[] = '<a href="'.$v.'">'.$k.'</a>';
			}
			else 
			{
				$links[] = '<strong>'.$k.'</strong>';
			}
		}
		return '<p><strong>Options</strong>: ' . implode(' | ', $links) . '</p>';
	}
	
	/**
	 * Creates an excel compatible csv file and outputs it as a user download. This function will
	 * not work properly unless output buffering is active. It cleans the output buffer before
	 * sending the appropriate headers to prompt the user to download the .csv file
	 * @param array $table_data
	 * @param string $delim default ","
	 * @param boolean $head whether or not to print header row default true
	 * @param string $filename custom filename for a .csv download defaults to date_$_table_name (XXXX-XX-XX_form_XXXXXX)
	 */
	function gen_csv($table_data, $delim = ',', $head = true, $filename = '')
	{
		$ret = '';
		if ($head) // if head we append a flipped row to the head of the array with flipped and mapped keys
		{
			$row = $this->_row;
			if (isset($this->fields_to_export)) $this->limit_export_columns($row);
			foreach($row as $k=>$v)
			{
				$head_row[$k] = (isset($this->_display_values[$k])) ? $this->_display_values[$k]['label'] : $k;
			}
			array_unshift($table_data, $head_row);
		}
		foreach ($table_data as $data_row)
		{
			if (isset($this->fields_to_export)) $this->limit_export_columns($data_row);
			$line = '';
			$separator = '';
			foreach ($data_row as $k => $v)
			{
				$pos = strpos($v, $delim);
       		 	$v = str_replace('"', '""', $v);
			$v = str_replace("\r\n", "\n", $v); // best linefeed handling I can come up with though mac and windows
							    // excel behave a bit differently
       		 	$line .= $separator. '"' . trim($v) . '"';
        		if ($separator == '') $separator = $delim;
			}
			$line .= "\n";
			$ret .= $line;
		}
		if (!empty($ret))
		{
			// this will only work properly when output buffering is active
			ob_end_clean();
			if (empty($filename)) $filename = $this->get_table_name() . '_'. date("Y-m-d");
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=$filename.csv");
			header("Content-Transfer-Encoding: binary");

			// IE will barf without these
			header("Pragma: private");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			
			header("Expires: 0");
			echo $ret;			
			exit;
		}
	}
	
	function run()
	{
		if (isset($this->table_action))
		{
			$this->run_action();
		}
		elseif (isset($this->table_row_action) && isset($this->table_action_id) && $this->verify_table_action_id())
		{
			$this->run_row_action();
		}
		else
		{
			$this->run_default();
		}
	}
	
	function run_default()
	{
		$data =& $this->get_data();
		if (!$this->has_data())
		{
			$no_data_message = ($this->get_no_data_message()) ? $this->get_no_data_message() : $this->default_no_data_message;
			echo $no_data_message;
			return false;
		}
		else
		{
			$table_viewer_html = $this->gen_table_html($data);
			echo $table_viewer_html;
		}
	}
	
	function run_action()
	{
		if ($this->table_action == 'delete_all')
		{
			$this->run_delete();
		}
		else if ($this->table_action == 'batch_download')
		{
			$this->run_batch_download();
		}
		elseif ($this->table_action == 'export')
		{
			$this->run_export();
		}
	}
	
	function run_row_action()
	{
		if ($this->table_row_action == 'view' || $this->table_row_action == 'edit' || $this->table_row_action == 'delete' || $this->table_row_action == 'new' || $this->table_row_action == "download_file")
		{
			$form =& $this->get_admin_form();
			$form->run();
		}
	}
	
	function run_export()
	{
		if ($this->export_format == 'csv')
		{
			// csv export
			$filename = (!empty($this->real_filename)) ? $this->real_filename : $this->filename_frag . '_' .date("Y-m-d");
			$table_data =& $this->get_data();
 			$this->gen_csv($table_data, ',', true, $filename);
		}
	}

	function run_batch_download()
	{
		$this->batch_download_form->generate();
		echo $this->batch_download_form->get_form_output();
	}

	function run_delete()
	{
		$links_base = $this->gen_menu_links_base();
		$links_export = $links_view = $links_base;
		$this->parse_filters_for_url($links_view);
		
		//$links_export['table_export_format'] = 'csv';
		$links_export['table_action'] = 'export';
		$menu_links['View Stored Data'] = carl_make_link($links_view);
		$menu_links['Delete Stored Data'] = '';
		$this->delete_form->set_num_rows($this->_total_rows);
		$this->delete_form->provide_link_to_csv_export(carl_make_link($links_export));

		if ($this->allow_download_files && $this->form_contains_file_elements()) {
			$links_batchdownload = $this->gen_menu_links_base();
			$links_batchdownload['table_action'] = 'batch_download';
			$this->delete_form->provide_link_to_batch_file_download(carl_make_link($links_batchdownload));
		}

		$this->delete_form->generate();
		$status = $this->delete_form->get_status();
		if (empty($status))
		{
			echo $this->gen_menu($menu_links);
			echo $this->delete_form->get_form_output();
		}
		elseif($status == 'cancel')
		{
			header( 'Location: '. carl_make_redirect($links_view) );
		}
		elseif($status == 'delete_forever')
		{
			if ($this->_check_table_exists())
			{
				$this->_delete_data();
				echo '<p><strong>Deleted ' . $this->_total_rows . ' row(s)</strong></p>';
			}
			else
			{
				echo '<p><strong>There is no data to delete</strong></p>';
			}
		}
	}
	
	function build_columns()
	{
		return '*';
	}
	
	// placeholder function for eventual ability to add columns
	function add_column($column_name)
	{
	}
	
	// placeholder function for eventual ability to remove columns
	function remove_column($column_name)
	{
	}
	
	/**
	 * Augment an array of key / value pairs intended for $_GET / $_POST to have a set of table filters that will be understood by the class
	 *  
	 * @param array url_array array to add table filters to
	 * @param boolean htmlspecialchars default false - should filter values run through htmlspecialchars ... should be false if the resulting array is going through carl_make_link
	 */
	function parse_filters_for_url(&$url_array, $htmlspecialchars = false)
	{
		$filters =& $this->get_filters();
		foreach ($filters as $k => $v)
		{
			$url_array['table_filters'][$k] = ($htmlspecialchars) ? htmlspecialchars($v['value'],ENT_QUOTES,'UTF-8') : $v['value'];
		}
	}
	
	/**
	 * _check_table_exists does just what it says - checks if the table parameter is actually a table in the database
	 *
	 * @return boolean true if the table exists, false otherwise
	 */
	function _check_table_exists($table = '', $force_refresh = false)
	{
		static $table_exists;
		$dbconn = $this->get_db_conn();
		$table = (!empty($table)) ? $table : $this->get_table_name();
		if (isset($table_exists[$dbconn][$table]) && !$force_refresh) return $table_exists[$dbconn][$table];
		else
		{
			connectDB($dbconn);
  			$q = 'show tables like "'.$table.'"';
  			$res = mysql_query($q);
  			connectDB($this->get_orig_db_conn()); // reconnect to default DB
  			if (mysql_num_rows($res) > 0) $table_exists[$dbconn][$table] = true;
  			else $table_exists[$dbconn][$table] = false;
  		}
  		return $table_exists[$dbconn][$table];
	}
	
	/**
	 * Not implememented yet
	 * @todo complete this functionality
	 */
	function _create_table_history_table()
	{
		$q = 'CREATE TABLE '.$this->history_table.'(`id` int(11) NOT NULL AUTO_INCREMENT,
										`table_name` TINYTEXT NOT NULL,
										`date_created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
										`num_rows` int(6) NOT NULL,
										`csv_data` MEDIUMTEXT NOT NULL,
										PRIMARY KEY(`id`)) ENGINE=MYISAM;';
		connectDB($this->get_db_conn());
		$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in table: '.mysql_error() );
		connectDB($this->get_orig_db_conn()); // reconnect to default DB
	}
	
	/**
	 * Returns an array that describes the label and plasmature type, indexed by column name 
	 */
	function _build_display_values()
	{
		$q = 'DESCRIBE ' . $this->get_table_name() or trigger_error( 'Error: mysql error in table: '.mysql_error() );
  		connectDB($this->get_db_conn());
  		$res = mysql_query($q);
  		connectDB($this->get_orig_db_conn()); // reconnect to default DB
  		
  		$this->_display_values = array();
  		while($field = mysql_fetch_assoc($res))
  		{
  			$this->set_field_display_type($field['Field'], $field['Type']);
  			$this->set_field_display_name($field['Field'], $field['Field']);
  		}
	}
	
	/** 
	 * Called by _build_display_values, which passes the field name and db column type to be
	 * used to set the data type for this column. You can override this if you need custom
	 * type handling for a particular field.
	 */
	function set_field_display_type($field, $type)
	{
		if (substr($type, 0, 4) == 'enum')
		{
			preg_match_all("/'(.*?)'/", $type, $matches);
			$this->_display_values[$field]['options'] = $matches[1];
			$this->_display_values[$field]['type'] = 'enum';
		}
		else
		{
			$this->_display_values[$field]['type'] = $type;
		}
	}

	/** 
	 * Called by _build_display_values, which passes the field name that is
	 * used to set the display name for this column. You can override this if you need custom
	 * name handling for a particular field.
	 *
	 * If an admin form is present and defines the get_field_display_name method, 
	 * it will be used to build column labels.
	 */
	function set_field_display_name($field, $field_name)
	{
		if ($this->admin_form && method_exists($this->admin_form, 'get_field_display_name'))
		{
			$this->_display_values[$field]['label'] = $this->admin_form->get_field_display_name($field);
		}
		else $this->_display_values[$field]['label'] = $field_name;
	}

	
	/** 
	 * Returns an array that describes the label and gives a plasmature type of text
	 */
	function _build_display_values_from_array($data)
	{
		foreach ($data as $k=>$v)
		{
			if ($this->admin_form && method_exists($this->admin_form, 'get_field_display_name'))
  			{
  				$v = $this->admin_form->get_field_display_name($k);
  			}
			$display_values[$k] = array('type' => 'text', 'label' => $v);
		}
		$this->_display_values = (isset($display_values)) ? $display_values : array();
	}

	function _delete_data()
	{
		// connect with table database defined in settings.php3
		connectDB($this->get_db_conn());
		$q = 'TRUNCATE TABLE ' . $this->get_table_name();
		$res = mysql_query( $q ) or mysql_error();//or trigger_error( 'Error: mysql error in Thor Data delete - URL ' . get_current_url() . ': '.mysql_error() );
  		connectDB($this->get_orig_db_conn());
		return $res;
	}
}
	
	/**
	 * Provides a basic default admin interface - this will often be extended by classes that want to provide a custom admin but this should
	 * suffice for basic view / edit / create privileges on a discoDB table
	 *
	 * @author Nathan White
	 */
	class DiscoDefaultAdmin extends DiscoDB
	{		
		/**
		 * Defines which actions are allowable in format 'action' => true|false|function_name_that_returns_boolean_result
		 *
		 * @var array
		 */
		var $allowable_actions;
		
		/**
		 * Which fields to show as column headers
		 *
		 * @var array
		 */
		var $fields_to_show;
		
		/**
		 * Display names for column headers for database fields
		 *
		 * @var array
		 */
		var $field_display_names;		
		
		/**
		 * Which fields to include in export
		 *
		 * @var array
		 */
		var $fields_to_export;
		
		/**
		 * Default sort field
		 *
		 * @var string
		 */
		var $default_sort_field;
		
		/**
		 * Default sort order
		 *
		 * @var string asc or desc
		 */
		var $default_sort_order = 'desc';
		
		/**
		 * If set to true, each row of data will be passed to the forms transform_data function
		 */
		var $form_transforms_data = false;
		
		/**
		 * Defines which actions are handled by the class 
		 */
		var $custom_handled_actions = array(); // NOT CURRENTLY BEING USED
		
		/**
		 * Contains the current table action - if any
		 */
		var $table_action;
		
		/**
		 * Returns true if the user has access to the functions allowed by the admin form
		 */
		function authenticate()
		{
			return false;
		}
		
		// setup form is given a reference to the table_admin at instantiation and should be whatever necessary
		function setup_form(&$table_admin)
		{
			$this->setup_db($table_admin->get_db_conn(), $table_admin->get_table_name(), $table_admin->get_table_action_id());
			$this->set_action($table_admin->get_table_row_action());
			$this->set_id_column_name($table_admin->get_primary_key());
		}
		
		/**
		 * Transforms the data passed into the function - set data_row to false to eliminate it from the presented set.
		 */
		function transform_data(&$data_row)
		{
		}

		/**
		 * Allows an admin form object to function as a factory in admin form objects that extend the base class
		 */
		function &get_custom_form()
		{
			return $this;
		}
		
		function pre_show_form()
		{
			if ($this->get_action() == 'new') $this->pre_show_form_new();
			elseif ($this->get_action() == 'view') $this->pre_show_form_view();
			elseif ($this->get_action() == 'edit') $this->pre_show_form_edit();
			elseif ($this->get_action() == 'delete') $this->pre_show_form_delete();
		}		
		function pre_show_form_default() {}
		function pre_show_form_new() { $this->pre_show_form_default(); }
		function pre_show_form_view() { $this->pre_show_form_default(); }
		function pre_show_form_edit() { $this->pre_show_form_default(); }
		function pre_show_form_delete() { $this->pre_show_form_default(); }
	
		function on_every_time()
		{
			if ($this->get_action() == 'new') $this->on_every_time_new();
			elseif ($this->get_action() == 'view') $this->on_every_time_view();
			elseif ($this->get_action() == 'edit') $this->on_every_time_edit();
			elseif ($this->get_action() == 'delete') $this->on_every_time_delete();
		}		
		function on_every_time_default() {}
		function on_every_time_new() { $this->on_every_time_default(); }
		function on_every_time_view() 
		{ 
			$this->actions = array();
			$element_names = $this->get_element_names();
			foreach ($element_names as $element)
			{
				$this->change_element_type($element, 'solidtext');
			}
		}
		
		function on_every_time_edit() { $this->on_every_time_default(); }
		
		function on_every_time_delete()
		{
			$this->actions = array('delete' => 'Confirm Delete', 'cancel' => 'Cancel');
			$element_names = $this->get_element_names();
			foreach ($element_names as $element)
			{
				$this->remove_element($element);
			}
			$this->add_element('delete_confirm_text', 'comment', array('text'=>'<h3>Are you sure you want to delete the record?</h3>'));
		}
		
		function run_error_checks()
		{
			if ($this->get_action() == 'new') $this->run_error_checks_new();
			elseif ($this->get_action() == 'view') $this->run_error_checks_view();
			elseif ($this->get_action() == 'edit') $this->run_error_checks_edit();
			elseif ($this->get_action() == 'delete') $this->run_error_checks_delete();
		}
		
		function run_error_checks_default() {}
		function run_error_checks_new() { $this->run_error_checks_default(); }
		function run_error_checks_view() { $this->run_error_checks_default(); }
		function run_error_checks_edit() { $this->run_error_checks_default(); }
		function run_error_checks_delete() { $this->run_error_checks_default(); }
		
		function process()
		{
			if ($this->get_action() == 'new') $this->process_new();
			elseif ($this->get_action() == 'view') $this->process_view();
			elseif ($this->get_action() == 'edit') $this->process_edit();
			elseif ($this->get_action() == 'delete') $this->process_delete();
		}		
		function process_default() { parent::process(); }
		function process_new() { $this->process_default(); }
		function process_view() { $this->process_default(); }
		function process_edit() { $this->process_default(); }
		function process_delete()
		{
			if ($this->get_chosen_action() == 'delete')
			{
				$id_to_delete = $this->get_id();
				$qry = 'DELETE FROM ' . $this->get_table_name() . ' WHERE '.$this->id_column_name.' = '.$id_to_delete;

				$result = db_query($qry, 'The delete query failed');
			}
		}
		
		function where_to()
		{
			if ($this->get_action() == 'new') return $this->where_to_new();
			elseif ($this->get_action() == 'view') return $this->where_to_view();
			elseif ($this->get_action() == 'edit') return $this->where_to_edit();
			elseif ($this->get_action() == 'delete') return $this->where_to_delete();
		}		
		function where_to_default() { return carl_make_redirect(array('table_row_action'=>'', 'table_action_id'=>'')); }
		function where_to_new() { return $this->where_to_default(); }
		function where_to_view() { return $this->where_to_default(); }
		function where_to_edit() { return $this->where_to_default(); }
		function where_to_delete() { return $this->where_to_default(); }
		
		function is_allowable_action($action_string)
		{
			if (isset($this->allowable_actions[$action_string]))
			{
				$as =& $this->allowable_actions[$action_string];
				if (method_exists($this, $as))
				{
					return $this->$as();
				}
				else
				{
					return $this->allowable_actions[$action_string];
				}
			}
			return NULL;
		}
		
		function does_form_transform_data()
		{
			return $this->form_transforms_data;
		}
		
		function set_action($action)
		{
			$this->table_action = $action;
		}
		
		function get_action()
		{
			return $this->table_action;
		}
		
		function get_field_display_name($field)
		{
			if (isset($this->field_display_names[$field]))
			{
				return $this->field_display_names[$field];
			}
			else return $field;
		}

		function get_fields_to_show()
		{
			if (isset($this->fields_to_show))
			{
				return $this->fields_to_show;
			}
		}
		
		function get_fields_to_export()
		{
			if (isset($this->fields_to_export))
			{
				return $this->fields_to_export;
			}
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
	
	/**
	 * Provides an interface for the delete all option in table_admin which allows export before deletion
	 *
	 * @author Nathan White
	 */
	class DiscoAdminDelete extends Disco
	{
		var $num_rows;              
		var $elements = array('disco_confirm_private' => 'hidden');
		var $actions = array( 'disco_confirm_delete_forever' => 'Delete Forever',
							  'disco_confirm_cancel'         => 'Cancel' );
		var $status = '';
		var $csv_export_string;
		var $batch_file_download_string = "";
		var $form_output;
		
		function set_num_rows($num_rows)
		{
			$this->num_rows = $num_rows;
		}
		
		function pre_show_form()
		{
			if ($this->num_rows > 0)
			{
				echo '<p>If you choose to proceed to delete the stored data, ';
				echo '<strong>all information</strong> that has been entered using this form on any page will be deleted from the database. ';
				echo 'If this information is important, it is highly recommend that you use the following link to <strong>save ';
				echo 'the data from the form before you proceed with deletion!</strong></p>'."\n";

				echo '<hr/>'.$this->csv_export_string;
				echo $this->batch_file_download_string;
				echo '<hr/>';

				echo '<p>Choose the "Delete Forever" button to <strong>permanently delete all ' . $this->num_rows . ' row(s)</strong> of data:</p>';
			}
			else
			{
				$this->show_form = false;
				echo '<p>There appear to be no rows to delete.</p>';
				$this->actions = array();
			}
		}

		function provide_link_to_batch_file_download($link)
		{
			$this->batch_file_download_string = '<ul><li><a href="'.$link.'">Batch Download Attachments</a></li></ul>';
		}
		
		function provide_link_to_csv_export($link)
		{
			$this->csv_export_string = '<ul><li><a href="'.$link.'">Download all '.$this->num_rows.' rows of data as .csv file</a></li></ul>';
		}
		
		function get_status()
		{
			return $this->status;
		}
		
		function get_form_output()
		{
			return $this->form_output;
		}
		
		function generate()
		{
			ob_start();
			$this->run();
			$this->form_output = ob_get_contents();
			ob_end_clean();
		}
		
		function process()
		{
			$this->show_form = false;
			if ($this->get_chosen_action() == 'disco_confirm_delete_forever')
			{
				$this->status = 'delete_forever';
			}
			else
			{
				$this->status = 'cancel';
			}
		}
	}

	/**
	 * 
	 *
	 * @author Tom Feiler
	 */
	class DiscoAdminBatchDownloader extends Disco
	{
		/**
		 * @var string target for file downloads
		 */
		var $file_download_base_url = '/thor/getFormFile.php';	 
		var $actions = array( 'disco_confirm_export' => 'Generate Zip...');
		function __construct($tableName, $fileColumns, $data, $url = null)
		{
			$this->tableName = $tableName;
			$this->fileColumns = $fileColumns;
			$this->data = $data;
			if (!is_null($url)) $this->file_download_base_url = $url;

			usort($this->data, Array("DiscoAdminBatchDownloader", "cmp"));

			$firstRow = current($this->data);
			$lastRow = end($this->data);

			$this->dataMin = $firstRow["id"];
			$this->dataMax = $lastRow["id"];

			reset($this->data);
		}

		private static function cmp($lhs, $rhs) {
			$a = $lhs["id"];
			$b = $rhs["id"];
			if ($a < $b) {
				return -1;
			} else if ($b < $a) {
				return 1;
			} else {
				return 0;
			}
		}

		function pre_show_form()
		{
			echo '<h2>Batch Download Attachments</h2>';
			echo '<p>This will generate a zip with all attachments for the rows in the range:</p>';
		}
		
		function get_form_output()
		{
			return $this->form_output;
		}
		
		function generate()
		{
			$dataRange = Array();
			for ($i = $this->dataMin ; $i <= $this->dataMax ; $i++) {
				$dataRange[$i] = $i;
			}
			$this->add_element('min_id', 'select', array('options' => $dataRange, 'default' => $this->dataMin, 'add_null_value_to_top' => false));
			$this->add_element('max_id', 'select', array('options' => $dataRange, 'default' => $this->dataMax, 'add_null_value_to_top' => false));

			// $this->required = Array('min_id', 'max_id');

			ob_start();
			$this->run();
			$this->form_output = ob_get_contents();
			ob_end_clean();
		}
		
		function process()
		{
			if ($this->get_chosen_action() == "disco_confirm_export") {
				$tc = new ThorCore("", $this->tableName);

				$targetMin = $this->get_value("min_id");
				$targetMax = $this->get_value("max_id");
				$i = 0;
				$zipData = Array();
				foreach ($this->data as $dataRow) {
					if ($dataRow["id"] >= $targetMin && $dataRow["id"] <= $targetMax) {
						foreach ($this->fileColumns as $fileCol => $fileLabel) {
							$fileLabel = substr(str_replace('/','_', $fileLabel), 0, 20);
							$path = $tc->construct_file_storage_location($dataRow["id"], $fileCol, $dataRow[$fileCol]);
							if (file_exists($path)) {
								// echo "[$i]: include $fileLabel [" . $dataRow[$fileCol] . "] -> [" . $path . "]<P>";
								$zipData[] = Array(
													"actualPath" => $path,
													"pathInZip" => "row_" . $dataRow["id"] . "/" . $fileLabel . "/" . $dataRow[$fileCol]
												);
							}
						}
					}
					$i++;
				}

				if (count($zipData) > 0) {
					$filename = date("Y-m-d_h-i-s_") . $this->tableName . "_" . $targetMin . "-" . $targetMax . ".zip";
					$zipPath = THOR_SUBMITTED_FILE_STORAGE_BASEDIR . $this->tableName . "/" . $filename;
					// echo "MAKING ZIP AT [" . $zipPath . "]";
					$zip = new ZipArchive();
					$zip->open($zipPath, ZipArchive::CREATE);
					foreach($zipData as $zd) {
						$actualPath = $zd["actualPath"];
						$pseudoPath = $zd["pathInZip"];
						// echo "<BR>adding [" . $actualPath . "] to zip at [" . $pseudoPath . "]...<BR>";
						$zip->addFile($actualPath, $pseudoPath);
					}
					$zip->close();

					// use an iframe to serve up the zip - getFormFile will return it to the user and delete it when done
					$link_params = array('mode' => 'fetch_zip', 'table' => $this->tableName, 'zipfile' => $zipPath);

					echo '<iframe src="'.carl_make_link($link_params, $this->file_download_base_url).'" id="zipper" style="display:none"></iframe>';
				} else {
					echo "No matching files were found<P>";
				}
			}
		}
	}
?>
