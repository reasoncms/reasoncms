<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
	/**
	 * Include parent class
	 */
	include_once( DISCO_INC.'disco_db.php' );
	/**
	 * Register form with Reason
	 */
	$GLOBALS[ '_custom_form_class_names' ][ module_basename( __FILE__, '.php') ] = 'DefaultCustomForm';
	
	/**
	 * DefaultCustomForm is an extension of DiscoDB that sets up a framework for DiscoDB forms used by the form_custom reason module
	 * It modifies the DiscoDB init, run, and process methods.
	 *
	 * A new method pre_init_and_run form is introducted, which typically would set the class variable init_and_run_form, or other intial
	 * tasks like adding head items to the page.
	 *
	 * In the init phase, init_no_form is invoked if the class variable init_and_run_form is set to false.
	 *
	 * In the run phase, run_no_form is invoked if the class variable init_and_run_form is set to false.
	 *
	 * In the process phase, the class variable allowable_fields is consulted. If not empty, then only the fields in this array
	 * will be updated by the database calls.
	 *
	 * @deprecated use the standard form module with the DB model
	 * @author Nathan White
	 */
	class DefaultCustomForm extends DiscoDB
	{
		var $db_conn;
		var $table;
		var $_id = '0'; // set this internally so disco will not grab the id from the request directly
		var $init_and_run_form = true;
		
		/**
		 * The data corresponding to the current id
		 * @var array
		 */
		var $row_data;
		
		/**
		 * cur_request is deprecated - use row_data instead
		 * @var array
		 * @deprecated in favor of row_data which is less confusing
		 */
		var $cur_request;
		var $cleanup_rules = array();
		var $allowable_fields;
		var $head_items; // populated by module with a reference to template head items object
		
		/**
		 * Inits the DiscoDB form using class variables $db_conn, $table, and $id
		 */
		function init( $externally_set_up = false )
		{
			if (isset($this->_inited) == false)
			{
				$cleanup_rules = $this->get_cleanup_rules();
				$request = conditional_stripslashes($_REQUEST);
				$this->request = carl_clean_vars($request, $cleanup_rules);
				$this->pre_init_and_run_form();
				if ($this->init_and_run_form)
				{
					parent::init();
				}
				else 
				{
					$this->init_no_form();
				}
			}
		}
		
		function &get_custom_form()
		{
			return $this;
		}
		
		function get_cleanup_rules()
		{
			return $this->cleanup_rules;
		}
		
		/**
		 * Populate cur_request according to the current disco_db_id
		 * @deprecated should use refresh_row_data()
		 */
		function refresh_cur_request()
		{
			$this->refresh_row_data();
		}
		
		function refresh_row_data()
		{
			$id = $this->disco_db_get_id();
			if ($id)
			{
				$qry = 'SELECT * from `' . $this->table . '` WHERE `id` = '.$id.' LIMIT 0,1';
				$this->disco_db_connect();
				$result = db_query($qry);
				if (mysql_num_rows($result) > 0)
				{
					while ($row = mysql_fetch_assoc($result))
					{
						$this->set_row_data($row);
					}
				}
				else
				{
					$row = false;
					$this->set_row_data($row);
				}
				$this->disco_db_disconnect();

			}
			else trigger_error('The form does not have an id so cannot refresh the row data');
		}
		
		function set_row_data(&$data)
		{
			$this->row_data = $data;
		}
		
		/**
		 * return a reference to the data for the current row
		 */
		function &get_row_data()
		{
			if (!isset($this->row_data) && $this->disco_db_get_id()) $this->refresh_row_data();
			return $this->row_data;
		}
		
		/**
		 * Invokes pre_process method before running simplified discoDB process that works with a single table and only considers allowable_fields
		 */
		function process()
		{
			$this->pre_process();
			$this->main_process();
			$this->post_process();
		}
		
		/**
		 * Runs immediately before discoDB process phase when error_checking is complete and form has no errors
		 */
		function pre_process()
		{
		}
		
		/**
		 * Runs the process where the database is actually altered
		 */
		function main_process()
		{
			$fields = $this->_tables[$this->table];
			$allowable_fields = (isset($this->allowable_fields) && is_array($this->allowable_fields)) ? $this->allowable_fields : array_keys($fields);
			if (!empty($allowable_fields))
			{		
				foreach ($fields as $field_name => $field_values)
				{
					if ($field_name != $this->id_column_name)
					{
						if (in_array($field_name, $allowable_fields)) $values[$field_name] = $this->get_value($field_name);
					}
				}
				if (isset($values))
				{
					$this->disco_db_connect();
					if ($this->_id)
					{
						$GLOBALS['sqler']->update_one($this->table, $values, $this->_id, $this->id_column_name);
					}
					else
					{
						$GLOBALS['sqler']->insert( $this->table, $values );
						$this->disco_db_set_id(mysql_insert_id()); // set id to what was just inserted
					}
					$this->disco_db_disconnect();
					$this->refresh_row_data(); // update row data with the just inserted row
				}
			}
		}
		
		/**
		 * Runs immediately after discoDB database commits - before where_to is called - often used for e-mailing or the like
		 */
		function post_process()
		{
		}
		
		/**
		 * Init phase when no disco form is initialized
		 */ 
		function init_no_form()
		{
		}
		
		/**
		 * Runs the DiscoDB form using class variables $db_conn, $table, and $id
		 */
		function run()
		{
			if ($this->init_and_run_form) 
			{
				parent::run();
			}
			else $this->run_no_form();
		}
		
		/**
		 * Run phase when no disco form is initialized
		 */ 
		function run_no_form()
		{
		}
		
		/**
		 * Run before the discoDB form init and run are executed - common usage would be a conditional that sets $this->init_and_run_form to false
		 */
		function pre_init_and_run_form()
		{
		}
		
		function has_content()
		{
			return true;
		}
		
		function get_custom_title()
		{
			return false;
		}
		
		/**
		 * Returns the db_value for a field if it exists in the class variable row_data - otherwise returns false
		 */
		function get_db_value($field)
		{
			$data =& $this->get_row_data();
			if (isset($data[$field])) return $data[$field];
			else return false;
		}
		
		/**
		 * Sets whether or not the module should allow the admin control box to be shown if the user has access to the admin interface
		 */
		function allow_show_admin_control_box()
		{
			return true;
		}
	}
?>
