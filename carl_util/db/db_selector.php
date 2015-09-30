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
		
		var $cache_lifespan;
		// }}}
		
		
		/**
		 * Constructor
		 */
		function DBSelector()
		{
			$this->query = '';
			$this->fields = array();
			$this->tables = array();
			$this->relations = array();
			$this->orderby = '';
			$this->optimize = '';
			$this->start = 0;
			$this->num = -1;
			$this->cache_lifespan = 0;
		} 
		
		/**
		 * Run query and return array of results
		 *
		 * @param string $error_message
		 * @param boolean $die_on_error
		 * @return array
		 */
		function run( $error_message = '', $die_on_error = true )
		{
			$q = $this->get_query();
			if($this->cache_lifespan)
			{
				$cache = new ObjectCache('db_selector_cache_'.get_current_db_connection_name().'_'.$q, $this->cache_lifespan);
				$results =& $cache->fetch();
				if(false !== $results)
				{
					return $results;
				}
			}
			$results = array();
			$r = db_query( $q,$error_message, $die_on_error );
			if($r)
			{
				while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
					$results[] = $row;
				mysql_free_result( $r );
			}
			if(!empty($cache))
				$cache->set($results);
			return $results;
		}
		
		/**
		 * Get the query
		 *
		 * Will build query if not already built.
		 *
		 * @param boolean $merged If true, will ignore ordering and limits
		 * @return string
		 */
		function get_query($merged = false)
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
		}
		
		/**
		 * Get the total number of results that match the query
		 *
		 * Note: this function ignores the start and num variables.
		 *
		 * @return integer
		 */
		function get_count()
		{
			$this->get_query();
			$r = db_query( $this->count_query, 'Unable to retrieve number of results' );
			$r = mysql_fetch_array( $r, MYSQL_ASSOC );
			return $r['count'];
		}
		/**
		 * Add a table to the query
		 *
		 * If only alias given, table name is assumed to be the same as the alias.
		 *
		 * CAUTION: This function does not escape strings, so do not pass it untrusted data
		 *
		 * @param string $alias
		 * @param string $table
		 * @return void
		 */
		function add_table( $alias, $table = '' )
		{
			if ( isset( $this->tables[ $alias ] ) AND !empty( $this->tables[ $alias ] ) )
				echo '<br /><strong>Warning:</strong> DBSelector::add_table - table "'.$alias.'" already added to selector - overwriting entry...<br />';
			if ( empty( $table ) )
				$table = $alias;
			$this->tables[ $alias ] = $table;
		}
		/**
		 * Add a field to the query
		 *
		 * Must specify table; can add an alias and/or function
		 *
		 * CAUTION: This function does not escape strings, so do not pass it untrusted data
		 *
		 * @param string $table
		 * @param string $field
		 * @param string $alias
		 * @param string $function
		 * @return void
		 */
		function add_field( $table, $field, $alias = '',$function = '' )
		{
			$field = $table.'.'.$field;
			if( $function )
				$field = $function.'('.$field.')';
			if ( !empty( $alias ) )
				$field .= ' AS '.$alias;
			$this->fields[ ] = $field;
		}
		/**
		 * Add a clause to the WHERE part of the query
		 *
		 * Multiple relations are ANDed together.
		 *
		 * CAUTION: This function does not escape strings, so use an appropriate string escaping
		 * routine if you are including arbitrary data in a where clause
		 *
		 * @param string $relation
		 * @return void
		 */
		function add_relation( $relation )
		{
			$this->relations[] = $relation;
		}
		/**
		 * Set the index to start at
		 *
		 * @param integer $start
		 * @return void
		 */
		function set_start( $start )
		{
			$this->start = $start;
		}
		/**
		 * Set the number of records to pull
		 *
		 * @param integer $num
		 * @return void
		 */
		function set_num( $num )
		{
			$this->num = $num;
		}
		/**
		 * Set the order clause of the query
		 *
		 * Example: "date DESC"
		 *
		 * @param string $order
		 * @return void
		 */
		function set_order( $order ) // 
		{
			$this->orderby = $order;
		}
		/**
		 * Set a cache lifespan for the query
		 *
		 * Setting lifespan to 0 turns off results caching
		 *
		 * @param integer $lifespan time in seconds to cache
		 * @return void
		 */
		function set_cache_lifespan($lifespan)
		{
			$this->cache_lifespan = $lifespan;
		}
	}
}
?>
