<?php

/**
 * The SQLer
 * A wrapper for quick and easy SQL INSERTs, UPDATEs, and DELETEs.
 * 
 * @package carl_util
 * @subpackage db
 * @copyright Carleton College
 * @license GNU General Public License: http://www.gnu.org/copyleft/gpl.html
 */
 
 /**
  * Include the carl_util error handler
  */
include_once('paths.php');
include_once(CARL_UTIL_INC.'error_handler/error_handler.php');
include_once( CARL_UTIL_INC . 'db/db.php' );
/*
 * The SQLer
 *
 * A wrapper for quick and easy SQL INSERTs, UPDATEs, and DELETEs.
 * The general data format is an associative array.  It takes care of the rest.
 * 
 * A singleton instance of SQLER is stored in $GLOBALS['sqler'] to reduce the need to constantly reinstantiate this class
 * 
 *
 * @author dave hendler
 */

	// class SQLER {{{
	class SQLER
	{
		/**
		 * if $mode is set to 'get_query' class methods will return query strings instead of executing queries
		 * @access public
		 * @var string $mode can be set to "get_query", which will cause functions to return rather than performing query
		 */
		var $mode = '';

		/**
		 * Inserts a single row into a database
		 * 
		 * @access public
		 * @param string $table The name of the table to which you want to add a row
		 * @param array $data An associative array of data (column_name => value)
		 * @return mixed (string query if mode is "get_query"; otherwise boolean success)
		 */
		function insert( $table, $data, $die_on_error = true ) // {{{
		{
			$fields = $values = '';
			reset( $data );
			foreach($data as $key => $val )
			{
				if (is_array($val))
				{
					trigger_error('Array value passed for key "'.$key.'" in SQLER insert.');
					continue;
				}
				$fields .= '`'.$key.'`,';
				$values .= ($val !== NULL) ? '"'.carl_util_sql_string_escape( $val ).'",' : 'NULL,';
			}
			$fields = substr( $fields, 0, -1 );
			$values = substr( $values, 0, -1 );

			$q = 'INSERT INTO '.$table.' ('.$fields.') VALUES ('.$values.')';
			if ($this->mode == 'get_query') return $q;
			else
			{
				return (boolean) db_query( $q, 'Unable to insert data into '.$table, $die_on_error );
			}
		}
		// }}}

		/**
		 * Update a single row in a database
		 * 
		 * @access public
		 * @param string $table The name of the table that contains the row to update
		 * @param array $data An associative array of data (column_name => value)
		 * @param integer $id The id of the row to update
		 * @param string $primary_key The name of the ID column (defaults to "id")
		 * @return mixed (string query if mode is "get_query"; otherwise boolean success)
		 */
		function update_one( $table, $data, $id, $primary_key = 'id' ) // {{{
		{
			$set_these = '';
			foreach($data as $key => $val )
			{
				$set_these .= '`'.$key.'` = ';
				$set_these .= ($val !== NULL) ? '"'.carl_util_sql_string_escape( $val ).'",' : 'NULL,';
			}
			$set_these = substr( $set_these, 0, -1 );

			$q = 'UPDATE '.$table.' SET '.$set_these.' WHERE '.$primary_key.' = "'.$id.'" LIMIT 1';
			if ($this->mode == 'get_query') return $q;
			else 
			{
				return (boolean) db_query( $q, 'Unable to update data in '.$table );
			}
		}
		// }}}

		/**
		 * Delete a single row from a database
		 * 
		 * @access public
		 * @param string $table The name of the table from which we are deleting the row
		 * @param integer $id The id of the row to delete
		 * @param string $primary_key The name of the ID column (defaults to "id")
		 * @return mixed (string query if mode is "get_query"; otherwise boolean success)
		 */
		function delete_one( $table, $id, $primary_key = 'id' ) // {{{
		{
			$q = 'DELETE FROM '.$table.' WHERE '.$primary_key.' = "'.carl_util_sql_string_escape($id).'" LIMIT 1';
			if ($this->mode == 'get_query') return $q;
			else 
			{
				return (boolean) db_query( $q, 'Unable to delete data from '.$table );
			}
		}
		// }}}
	}
	// }}}

	$GLOBALS['sqler'] = new SQLER;

?>
