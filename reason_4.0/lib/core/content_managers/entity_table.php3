<?php
/**
 * A content manager for entity data tables
 * @package reason
 * @subpackage content_managers
 */
 
 /**
  * Save the class name in the globals so that the admin page can use this content manager
  */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'EntityTableManager';
	
	/**
 	 * A content manager for entity/data tables
 	 *
 	 * This content manager creates a table when the entity is first created, and handles various error checks.
 	 */
	class EntityTableManager extends ContentManager
	{
		/**
		 * Do form customization and setup
		 *
		 * Saves previous entity so that we can dtermine if entity has been changed in error checks, etc.
		 *
		 * Locks the name field if the table has already been set up
		 */
		function alter_data()
		{
			$this->_old_entity = new entity($this->get_value('id'));
			$this->_old_entity->get_values();
			if( $this->_old_entity->get_value('name') )
			{
				$this->change_element_type( 'name', 'solidtext' );
				$this->add_comments('name', form_comment('Table names cannot be altered once a table is created'));
			}
			else
			{
				$this->add_comments('name', form_comment('Table names can only contain alphanumeric characters and underscores.<br />Choose carfully, as table names cannot be altered once a table is created.'));
			}
		}
		/**
		 * Handles error checking
		 *
		 * Runs parent error checks, then makes sure table is OK to create if it has not already been created
		 */
		function run_error_checks()
		{
			parent::run_error_checks();
			if(!$this->_has_errors() && !$this->_old_entity->get_value('name'))
			{
				if(!$this->_table_name_meets_specs($this->get_value('name')))
				{
					$this->set_error('name','Table names can only contain basic characters, numbers, and underscores.');
				}
				elseif(!$this->_table_name_is_acceptable_length($this->get_value('name')))
				{
					$this->set_error('name','Table names may not contain more than 64 characters.');
				}
				elseif($this->_table_name_conflicts_with_other_entity_table($this->get_value('name')))
				{
					$this->set_error('name','An entity table with that name already exists. Please choose a different table name.');
				}
				elseif($this->_table_is_in_db($this->get_value('name')))
				{
					$this->set_error('name','That is a is a reserved table name. Please choose a different table name.');
				}
				elseif( $this->_table_is_protected($this->get_value('name')) )
				{
					$this->set_error('name','That is a protected table name. Please choose a different table name.');
				}
			}
		}
		/**
		 * Makes sure table name does not include any unacceptable characters
		 *
		 * @param string $name Name of the prospective table
		 * @return boolean true if OK, false if not OK
		 */
		function _table_name_meets_specs($name)
		{
			if( preg_match( "|^[0-9a-z_]*$|i" , $name ) )
				return true;
			else
				return false;
		}
		/**
		 * Makes sure table name is not loo long to be used in db
		 *
		 * @param string $name Name of the prospective table
		 * @return boolean true if OK, false if not OK
		 */
		function _table_name_is_acceptable_length($name)
		{
			if(strlen($name) <= 64)
				return true;
			else
				return false;
		}
		/**
		 * Makes sure table with same name does not already exist as an entity table
		 *
		 * @param string $name Name of the prospective table
		 * @return boolean true if conflicting, false if not conflicting
		 */
		function _table_name_conflicts_with_other_entity_table($name)
		{
			$es = new entity_selector();
			$es->add_type(id_of('content_table'));
			$es->add_relation('entity.name = "'.reason_sql_string_escape($this->get_value('name')).'"');
			$es->set_num(1);
			$tables = $es->run_one();
			if(empty($tables))
				return false;
			else
				return true;
		}
		/**
		 * Makes sure table with same name does not already exist in the database
		 *
		 * @param string $name Name of the prospective table
		 * @return boolean true if conflicting, false if not conflicting
		 */
		function _table_is_in_db($name)
		{
			$results = array();
			$handle = db_query( 'SHOW TABLES' );
			while( $row = mysql_fetch_array( $handle, MYSQL_ASSOC ) )
				$results[] = $row;
			mysql_free_result( $handle );
			foreach($results as $result)
			{
				if(current($result) == $name)
					return true;
			}
			return false;
		}
		/**
		 * Makes sure table does not use name of a protected Reason table
		 *
		 * @param string $name Name of the prospective table
		 * @return boolean true if conflicting, false if not conflicting
		 */
		function _table_is_protected($name)
		{
			return in_array($name, reason_get_protected_tables());
		}
		/**
		 * Creates the table when a new entity is saved
		 *
		 * calls CMfinish() after creating the table
		 *
		 * Uses a custom db_query -- should it use standard table creation code instead?
		 *
		 * @todo Look into using standard table creation code instead of a custom query
		 */
		function finish()
		{
			if( $this->get_value('name') && !$this->_old_entity->get_value('name') && !$this->_table_is_in_db($this->get_value('name')) )
			{
				$q = "CREATE TABLE ".reason_sql_string_escape($this->get_value('name'))." (id int unsigned primary key)" ;
				db_query( $q, 'Unable to create new table' );
			}
			
			return $this->CMfinish();
		}
	}
?>
