<?
	include_once( DISCO_INC.'disco_db.php' );
	$GLOBALS[ '_custom_form_class_names' ][ module_basename( __FILE__, '.php') ] = 'DefaultCustomForm';
	
	/**
	 * DefaultCustomForm is an extension of DiscoDB that sets up a framework for DiscoDB forms used by the form_custom reason module
	 * It modifieds the DiscoDB init, run, and process methods.
	 *
	 * A new method pre_init_and_run form is introducted, which typically would set the class variable init_and_run_form.
	 *
	 * In the init phase, it alternatively calls init_no_form and run_no_form if the class variable init_and_run_form is set to false.
	 *
	 * In the run phase, it alternatively calls run_no_form if the class variable init_and_run_form is set to false.
	 * conditional parameters specific to the form.
	 *
	 * In the process phase, the class variable allowable_fields is consulted. If not empty, then only the fields in this array
	 * will be updated by the database calls.
	 *
	 * @author Nathan White
	 */
	class DefaultCustomForm extends DiscoDB
	{
		var $db_conn;
		var $table;
		var $_id = '0'; // set this internally so disco will not grab the id from the request directly
		var $init_and_run_form = true;
		var $cur_request;
		var $cleanup_rules = array();
		var $allowable_fields = array();
		
		/**
		 * Inits the DiscoDB form using class variables $db_conn, $table, and $id
		 */
		function init()
		{
			if (isset($this->_inited) == false)
			{
				$this->request = carl_clean_vars(carl_clone($_REQUEST), $this->cleanup_rules);
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
		
		/**
		 * Populate cur_request according to the current disco_db_id
		 */
		function refresh_cur_request()
		{
			$id = $this->disco_db_get_id();
			$qry = 'SELECT * from `' . $this->table . '` WHERE `id` = '.$id.' LIMIT 0,1';
			$this->disco_db_connect();
			$result = db_query($qry);
			$this->disco_db_disconnect();
			if (mysql_num_rows($result) > 0)
			{
				while ($row = mysql_fetch_assoc($result))
				{
					$this->cur_request = $row;
				}
			}
			else trigger_error('The request could not be refreshed - no results were found');
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
			$allowable_fields = (!empty($this->allowable_fields)) ? $this->allowable_fields : array_keys($fields);
			foreach ($fields as $field_name => $field_values)
			{
				if ($field_name != $this->id_column_name)
				{
					if (in_array($field_name, $allowable_fields))
					{
						$values[$field_name] = $this->get_value($field_name);
					}
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
				$this->refresh_cur_request(); // update cur_request with the just inserted row
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
		 * Returns the db_value for a field if it exists in the class variable cur_request - otherwise returns false
		 */
		function get_db_value($field)
		{
			if (isset($this->cur_request[$field])) return $this->cur_request[$field];
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
