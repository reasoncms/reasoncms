<?php

	include_once( DISCO_INC . 'disco.php' );
	include_once( CARL_UTIL_INC . 'db/sqler.php' );

 	/**
	 * An extension of Disco that handles loading of table structures and data from a database and saves the information back into the database.
	 *
	 * Updated to work with database connects/disconnects when given a named db_conn string, and also to better support distinct init and run phases - nwhite.
	 *
	 * Simplest Use:
	 * <code>
	 * $f = new DiscoDB;
	 * $f->load( 'database','table',$item_id );
	 * $f->run();
	 * </code>
	 *
	 * Simplest use with DB Connection string
	 *
	 * <code>
	 * $f = new DiscoDB;
	 * $f->setup_db('db_conn','table',$item_id);
	 * $f->run();
	 * </code>
	 * @author Dave Hendler
	 * @package disco
	 */
	class DiscoDB extends Disco
	{
		/**
		* True if values have been loaded
		* @var boolean
		*/
		var $_values_loaded = false;
		/**
		* Array of table to use
		* @var array
		*/
		var $_tables;
		/**
		* If this is a new record, record the ID of the first insertion
		* @var mixed
		*/
		var $_inserted_id = false;

		/**
		* Uses this to grab the appropriate row in the db when editing a record.
		* Use set_id_column_name() to change this
		* @var string
		*/
		var $id_column_name = 'id';
		/**
		* Argument to pass to plasmature date types -- whether or not dates fields should be prepopulated with the current date.
		* @var boolean
		*/
		
		/**
		 * When set, the load phase understands that the database is actually a db_connection string - this must be set in order for
		 * disco db to initiate connections to databases outside of reason
		 */
		var $_use_db_connection_string = false;
		var $db_conn;
		var $table;
		var $load_has_run = false;
		
		var $prepopulate_date_fields = false;

		function init($externally_set_up = false) // {{{
		// init works a little differently for discoDB.
		{
			if (!$this->load_has_run)
			{
				if (!empty($this->table) && !empty($this->db_conn))
				{
					if (!empty($this->table))
					{
						$this->_use_db_connection_string();
						$this->load($this->db_conn, $this->table, $this->_id);
					}
				}
				else trigger_error('the disco db form must have the class variables $table and $db_conn defined if you are running init prior to load');
			}
			if ( !isset( $this->_inited ) OR empty( $this->_inited ))
			{
				if ($this->_use_db_connection_string) 
				{
					$this->disco_db_connect();
				}
				// are we first timing it?
				if( empty( $this->_request ) ) $this->_request = conditional_stripslashes($_REQUEST);
				$HTTP_VARS = $this->_request;
				$this->_first_time = (isset( $HTTP_VARS[ 'submitted' ] ) AND !empty( $HTTP_VARS[ 'submitted' ] )) ? false : true;
				
				// tables should not be empty
				if ( !isset( $this->tables ) OR empty( $this->tables ) )
					$this->_internal_error( 'Your Disco DB must have tables specified' );

				if ( !isset( $this->required ) OR !is_array( $this->required ) )
					$this->required = array();

				// determine action that was chosen
				foreach( $this->_request AS $key => $val )
				{
					if( preg_match( '/__button_/' , $key ) )
						$this->chosen_action = preg_replace( '/__button_/' , '' , $key );
				}
				
				
								$HTTP_VARS = $this->_request;
				$this->_first_time = (isset( $HTTP_VARS[ 'submitted' ] ) AND !empty( $HTTP_VARS[ 'submitted' ] )) ? false : true;
				
				// initialize values
				$this->_error_required = array();
				$this->_error_messages = array();
				$this->_error_flag = false;

				// run through the tables array setting everything and getting any kinds of types from the database as needed
				reset( $this->tables );
				while( list( $key, $val ) = each( $this->tables ) )
				{
					// just a table name - this table needs to be loaded from DB
					if ( is_int( $key ) AND is_string( $val ) )
					{
						// load elements for this table
						$this->load_elements_from_db( $this->_db, array( $val ) );
					}
					// this table has elements defined for it
					else if ( is_string( $key ) )
					{
						if ( empty( $val ) )
						{
							$this->load_elements_from_db( $this->_db, array( $key ) );
						}
						else
						{
							// inspect elements in this array
							reset( $val );
							while( list( $el_key, $el_val ) = each( $val ) )
							{
								$element = '';
								$type = '';
								$args = array();

								// probably an element with a type
								if ( is_string( $el_key ) )
								{
									$element = $el_key;
									// this element has extra args
									if ( is_array( $el_val ) )
									{
										$type = $el_val[ 'type' ];
										$args = $el_val;
									}
									else
										$type = $el_val;
								}
								// this is an element without a defined type - guess type from db
								else
								{
									$element = $el_val;
								}
								
								$this->add_element( $element, $type, $args );
								$this->_tables[ $key ][ $element ] = array( 'type' => $type, 'field' => $element );
							}
						}
					}
					else
						$this->_internal_error( 'Your tables are malformed.' );
				}
				
				// now load values from db
				if ( !isset( $this->_id ) )
				{
					$HTTP_VARS = conditional_stripslashes($_REQUEST);
					$this->_id = isset( $HTTP_VARS[ 'id' ] ) ? $HTTP_VARS[ 'id' ] : '';
				}
				$this->load_values();

				// required should only contain defined elements & element groups 
				foreach($this->required as $name)
				{
					if(!$this->_is_element($name) && !$this->_is_element_group($name))
						trigger_error($name.' is present in your required fields, but it is not a defined element or element group.');
				}

				$this->_inited = true;
				if ($this->_use_db_connection_string) $this->disco_db_disconnect();
			}
		} // }}}
		function load( $db, $tables, $id = '' ) // {{{
		{
			if ($this->_use_db_connection_string) // we have a database connection string
			{
				$this->disco_db_connect();
				$this->_db = get_database_name($db);
				$this->disco_db_disconnect();
			}
			else
			{
				$this->_db = $db;
			}
			if ( is_string( $tables ) )
				$this->tables = array( $tables );
			else
			{
				reset( $tables );
				while( list( ,$t ) = each ($tables) )
					$this->tables[ $t ] = array();
			}
			$this->_id = $id;
			$this->load_has_run = true;
		} // }}}
		function set_id_column_name( $name )
		{
			$this->id_column_name = $name;
		}
		
		function load_elements_from_db( $db, $tables ) // {{{
		{
			$this->_db = $db;

			// make into array
			if ( !is_array( $tables ) )
				$this->_tables[ $tables ] = array();
			// otherwise, make all values keys.  change values an empty array
			else
			{
				reset( $tables );
				while( list( ,$t ) = each ($tables) )
					$this->_tables[ $t ] = array();
			}

			// load elements from DB
			reset( $this->_tables );


			while( list( $table, ) = each( $this->_tables ) )
			{		
				// get types
				$types = mysql_query( "show fields from $table" ) OR $this->_internal_error( 'Could not retrieve types from DB' );
				$fields = mysql_list_fields( $this->_db, $table ) OR $this->_internal_error( 'Could not retrieve fields from DB: '.mysql_error() );
				$columns = mysql_num_fields( $fields );
		
				for ($i = 0; $i < $columns; $i++)
				{
					$f = mysql_field_name($fields, $i) OR $this->_internal_error( 'um ... something is wrong. you should check me. and someone should write a more descriptive error message.' );
					$db_type = mysql_result($types, $i,'Type' )  OR $this->_internal_error( 'um ... something is wrong. you should check me. and someone should write a more descriptive error message.' );
						
					list( $type, $args ) = $this->plasmature_type_from_db_type( $f, $db_type, array('find_maxlength_of_text_field' => true, 'do_not_sort_enums' => true));
					$args['db_type'] = $db_type;
					if(($type == 'textDate' || $type == 'textDateTime') && $this->prepopulate_date_fields)
						$args['prepopulate'] = true;
					$this->add_element( $f, $type, $args );
					$this->_tables[ $table ][ $f ] = array( 'field' => $f, 'type' => $t );
				}
			}
		} // }}} 
		function load_values() // {{{
		{
			// if id is set, load the values from the db
			if ( !empty( $this->_id ) )
			{
				// loop through all tables being used
				$tables = '';
				$where = '';
				reset( $this->_tables );
				while( list( $t,$fields ) = each( $this->_tables ) )
				{
					$tables .= ','.$t;
					$where .= 'AND '.$t.'.'.$this->id_column_name.' = "'.$this->_id.'" ';
				}
				//eliminate the comma
				$tables = substr( $tables, 1 );
				//eliminate the AND
				$where = substr( $where, strlen( 'AND ' ) );

				// build the query
				$q = 'SELECT * FROM '.$tables.' WHERE '.$where;
				$res = mysql_query( $q ) or $this->_internal_error( 'mysql error in DiscoDB: '.mysql_error() );
				if ( mysql_num_rows( $res ) > 0 )
				{
					// put values in appropriate places
					$row = mysql_fetch_array( $res, MYSQL_ASSOC );
					while( list( $key, $val ) = each ( $row ) )
					{
						if ( $this->_is_element( $key ) )
						{
							$this->set_value( $key, $val );
						}
					}
				}
				else die( 'id does not exist in DB' );
				mysql_free_result( $res );

				$this->_values_loaded = true;
			}
		} // }}}
		
		function run()
		{
			
			$this->init();
			if ($this->_use_db_connection_string) $this->disco_db_connect();
			parent::run_load_phase();
			parent::run_process_phase();
			parent::run_display_phase();
			if ($this->_use_db_connection_string) $this->disco_db_disconnect();
		}
		
		function process() // {{{
		{
			// update table instead of inserting
			if ( $this->_id )
			{
				reset( $this->_tables );
				// we need to update each table independently
				while( list( $table,$fields ) = each ( $this->_tables ) )
				{
					$values = array();
					while( list( $element,$field_info ) = each ( $fields ) )
						if ( $element != $this->id_column_name )
							$values[ $element ] = $this->get_value( $element );
					$GLOBALS['sqler']->update_one( $table, $values, $this->_id, $this->id_column_name );
				}
			}
			// insert
			else
			{
				// if there is more than one table, we use the same id for each table
				// note: this is used only for one-to-one relationship tables.
				// don't know if this is needed for other types of tables
				// in fact, this may need to be an option at some point.
				// actually, use_same_id probably should be an option.
				// for many-to-many or one-to-many, all ids should be unique
				$insert_id = '';
				reset( $this->_tables );
				while( list( $table,$fields ) = each ( $this->_tables ) )
				{
					$values = array();
					while( list( $element,$field_info ) = each ( $fields ) )
					{
						$values[ $element ] = $this->get_value( $element );
					}
					if ( $insert_id ) $values[ $this->id_column_name ] = $insert_id;
					
					$GLOBALS['sqler']->insert( $table, $values );

					if ( !$insert_id ) $insert_id = mysql_insert_id();
				}
				// keep track of insertion id for extended classes
				$this->_inserted_id = $insert_id;
			}
		} // }}}
		function remove_element( $element ) // {{{
		{
			if( $this->_tables )
			{
				foreach($this->_tables as $t => $fields)
				{
					if ( isset( $fields[ $element ] ) )
						unset( $this->_tables[ $t ][ $element ] );
				}
			}
			
			parent::remove_element($element);
		} // }}}
		
		
		/**
		 * The following are part of additions to discoDB to properly handle connections to databases outside of reason
		 *
		 * @author Nathan White
		 */
		 
		/**
		 * Disconnect from database specified in class variable db_conn, and reconnect to original database
		 */
		
		function disco_db_disconnect()
		{
			$this->_disco_conn(false);
		}
		
		/**
		 * Connect to database specified in class variable db_conn
		 */
		function disco_db_connect()
		{
			$this->_disco_conn(true);
		}
		
		/**
		 * Private function to handle database connections - only makes a new connection when needed
		 * @access private
		 */
		function _disco_conn($bool)
		{
			//static $orig;
			//static $curr;
			if (empty($this->orig)) $this->orig = $this->curr = get_current_db_connection_name();
			if ($bool && ($this->curr != $this->db_conn))
			{
				connectDB($this->db_conn);
				$this->curr = $this->db_conn;
			}
			elseif (!$bool && ($this->curr != $this->orig))
			{
				connectDB($this->orig);
				$this->curr = $this->orig;
			}
		}
		
		/**
		 * Set the disco_db_id
		 */
		function disco_db_set_id($id)
		{
			$this->set_id($id);
		}
		
		/**
		 * Get the disco_db_id
		 */ 
		function disco_db_get_id()
		{
			return $this->get_id();
		}
		
		/**
		 * Set a database connection string
		 */
		function _use_db_connection_string()
		{
			$this->_use_db_connection_string = true;
		}
		
		function set_db_conn($db_conn)
		{
			$this->db_conn = $db_conn;
		}
		
		function set_table_name($table)
		{
			$this->table = $table;
		}
		
		function set_id($id)
		{
			$this->_id = $id;
		}
		
		function get_id()
		{
			return $this->_id;
		}
		
		function get_db_conn()
		{
			return $this->db_conn;
		}
		
		function get_table_name()
		{
			return $this->table;
		}
		
		function setup_db($db_conn, $table_name, $id)
		{
			$this->set_db_conn($db_conn);
			$this->set_table_name($table_name);
			$this->set_id($id);
		}
	}
?>
