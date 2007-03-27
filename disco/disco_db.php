<?php

	include_once( DISCO_INC . 'disco.php' );
	include_once( CARL_UTIL_INC . 'db/sqler.php' );

 	/**
	 * An extension of Disco that handles loading of table structures and data from a database and saves the information back into the database.
	 *
	 * Simplest Use:
	 * <code>
	 * $f = new DiscoDB;
	 * $f->load( 'database','table',$item_id );
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
		var $prepopulate_date_fields = false;

		function init() // {{{
		// init works a little differently for discoDB.
		{
			if ( !isset( $this->_inited ) OR empty( $this->_inited ))
			{
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
					$HTTP_VARS = conditional_stripslashes(get_http_vars());
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
			}
		} // }}}
		function load( $db, $tables, $id = '' ) // {{{
		{
			$this->_db = $db;
			if ( is_string( $tables ) )
				$this->tables = array( $tables );
			else
			{
				reset( $tables );
				while( list( ,$t ) = each ($tables) )
					$this->tables[ $t ] = array();
			}
			$this->_id = $id;
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

/*  Deprecated in favor of $this->plasmature_type_from_db_type*()
					$args = array();
					// show correct form element based on field type from DB
					// ids are protected
					if ( $f == 'id' )
						$t = 'hidden';
					// date types
					else if ( preg_match( '/^date/i', $t ) )
						$t = 'textDate';
					// timestamp or datetime
					else if( preg_match( '/^(timestamp|datetime)/i', $t ) )
						// at some point, make a make_datetime function
						$t = 'textDateTime';
					// textarea types - big blobs
					else if( preg_match( '/^(text|blob|mediumblob|mediumtext|longblob|longtext)/i', $t ) )
						$t = 'textarea';
					// automatically link tables if field name ends in _id
					else if ( preg_match( '/(.*)_id$/i', $f, $matches ) )
					{
						$t = 'tablelinker';
						$args[ 'table' ] = $matches[1];
						$args[ 'display_name' ] = $matches[1];
					}
					// enumerated types - make a select
					else if ( preg_match( "/^enum\((.*)\)$/", $t, $matches ) )
					{
						$options = array();
						$opts = array();
						$t = 'select';
						// explode on the commas
						$options = explode( ',', $matches[1] );
						// get rid of the single quotes at the beginning and end of the string
						// MySQL also escapes single quotes with single quotes, so if we see two single quotes, replace those two with one
						reset( $options );
						while( list( $key,$val ) = each ( $options ) )
							$options[ $key ] = str_replace("''","'",substr( $val,1,-1 ));
						reset( $options );
						while( list( ,$val ) = each( $options ) )
							$opts[ $val ] = $val;
						$args['options'] = $opts;
					}
					// default type
					else
						$t = ''; */
						
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
/*				reset( $this->_tables );
				while( list( $t, $fields ) = each( $this->_tables ) )
				{
					if ( isset( $fields[ $element ] ) )
						unset( $this->_tables[ $t ][ $element ] );
				} */
			}
			
			parent::remove_element($element);
			/*if ( isset( $this->_elements[ $element ] ) )
				unset( $this->_elements[ $element ] );
			if( isset( $this->elements[ $element ] ) )
				unset( $this->elements[ $element ] );
			unset( $this->_errors[ $element ] );*/
		} // }}}
	}
?>
