<?php
/**	
 * DBSelector class
 *
 * quick and dirty class to automate query generation
 * just set the appropriate values and let her rip
 *
 * @package carl_util
 * @subpackage db
 * @author dave hendler
 * @todo remove old method of enforcing include_once
 */
	
/**
 * Old method of enforcing include_once
 */
if( !defined( '__DB_SELECTOR' ) )
{
	define ( '__DB_SELECTOR', true );
	
	include_once('db_query.php');
	
	/**
	 * DBSelector class
	 *
	 * quick and dirty class to automate query generation
	 * just set the appropriate values and let her rip
	 */
	class DBSelector
	{
		// data members {{{
		// query which will be run
		var $query;
		// query to get number of results
		var $count_query;

		// array of fields to draw from
		var $fields;
		// array of tables to use
		var $tables;
		// array of clauses for WHERE 
		var $relations;
		// fields to order by
		var $orderby;
		// where to start 
		var $optimize;
		// optimize keyword
		var $start;
		// how many to get
		var $num;
		// }}}
		
		function DBSelector() // constructor  {{{
		{
			$this->query = '';
			$this->fields = array();
			$this->tables = array();
			$this->relations = array();
			$this->orderby = '';
			$this->optimize = '';
			$this->start = 0;
			$this->num = -1;
		} // }}}
		function run( $error_message = '', $die_on_error = true ) // runs query and returns array of results {{{
		{
			$results = array();
			$r = db_query( $this->get_query(),$error_message, $die_on_error );
			if($r)
			{
				while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
					$results[] = $row;
				mysql_free_result( $r );
			}
			return $results;
		} // }}}
		function get_query($merged = false)  //  returns the query (will build query if not already built ){{{
		{
			// FIELDS: convert array to string
			if ( empty( $this->fields ) )
				$fields = '*';
			else
				$fields = implode( ', ',$this->fields );

			// WHERE
			$where = implode( ' AND ',$this->relations );

			// FROM
			reset( $this->tables );
			$tables = '';
			while( list( $alias, $table ) = each( $this->tables ) )
				$tables .= ', '.$table.' AS '.$alias;
			// get rid of leading ', '
			$tables = substr( $tables, 2 );

			// make sure not to have SQL keywords if we are not using them...
			if ( $where )
				$where = 'WHERE '.$where;
			else
				$where = '';

			if ( $this->orderby )
				$orderby = 'ORDER BY '.$this->orderby;
			else
				$orderby = '';
			
			$optimize = (!(empty($this->optimize))) ? ' ' .$this->optimize . ' ' : '';	
			$this->count_query = 'SELECT COUNT(1) AS count FROM '.$tables.' '.$where;
			
			if ($merged)
			{
				$this->query = "SELECT\n".$fields."\nFROM\n".$tables."\n".$where."\n";
			}
			else
			{
				$this->query = "SELECT\n".$optimize.$fields."\nFROM\n".$tables."\n".$where."\n".$orderby."\n";
				if ($this->num > 0) $this->query .= "LIMIT\n".$this->start.', '.$this->num;
			}
			return( $this->query );
		} // }}}
		
		function get_count() // returns total number of results that match the query.  ignores the start and num variables {{{
		{
			$this->get_query();
			$r = db_query( $this->count_query, 'Unable to retrieve number of results' );
			$r = mysql_fetch_array( $r, MYSQL_ASSOC );
			return $r['count'];
		} // }}}
		function add_table( $alias, $table = '' ) // adds table to the query.  if both arguments, uses first argument as an alias {{{
		{
			if ( isset( $this->tables[ $alias ] ) AND !empty( $this->tables[ $alias ] ) )
				echo '<br /><strong>Warning:</strong> DBSelector::add_table - table "'.$alias.'" already added to selector - overwriting entry...<br />';
			if ( empty( $table ) )
				$table = $alias;
			$this->tables[ $alias ] = $table;
		} // }}}
		function add_field( $table, $field, $alias = '',$function = '' ) // adds field.  must specify table.  can add an alias {{{
		{
			$field = $table.'.'.$field;
			if( $function )
				$field = $function.'('.$field.')';
			if ( !empty( $alias ) )
				$field .= ' AS '.$alias;
			$this->fields[ ] = $field;
		} // }}}
		function add_relation( $relation ) // adds a clause to the WHERE part of the query {{{
		{
			$this->relations[] = $relation;
		} // }}}
		function set_start( $start ) // sets the query start {{{
		{
			$this->start = $start;
		} // }}}
		function set_num( $num ) // sets number of records to pull {{{
		{
			$this->num = $num;
		} // }}}
		function set_order( $order ) // sets fields to order results by {{{
		{
			$this->orderby = $order;
		} // }}}
	}
}
?>
