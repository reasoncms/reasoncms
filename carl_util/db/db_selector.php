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
	include_once('sql_string_escape.php');
	
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
			// $this->count_query = 'SELECT COUNT(1) AS count FROM '.$tables.' '.$where;
			// modified from SELECT COUNT(1)....problem is that some queries return duplicate entities. For instance given a "Department" entity,
			// and an "Employee" entity, a relationship of department<->employee, and an employee who belongs to multiple departments. When writing an
			// entity_selector for this scenario, the employee appears in the resultset twice (calling client doesn't see it in the eventual array 
			// returned b/c it's associative and the duplicate entries overwrite one another). But this led to strange situation where running the selector
			// could return a number of items and running the count selector would return a greater number. Changing the count_query brings these in line.
			// 
			// note - if you're paginating results, you should be sure to optimize the es using "DISTINCT". Is there a reason to not always use DISTINCT?
			$this->count_query = 'SELECT COUNT(DISTINCT entity.id) AS count FROM '.$tables.' '.$where;
			
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
		 * Add a simple WHERE clause based on field/value/operator parameters
		 *
		 * Pass single or multiple fields and values to be evaluated using a given operator
		 *
		 * If multiple fields or values are passed, evaluation will be based on a logical
		 * OR, e.g. the clause will match if any of the fields match any of the values 
		 * using the operator provided.
		 *
		 * If logical AND is desired, use add_condition() or add_relation() multiple 
		 * times -- multiple clauses are logically ANDed together in query assembly.
		 *
		 * Note that you can use the "=" & "IN" operators interchangeably; the method 
		 * determines which to use based on the values provides.
		 *
		 * Note also that the "!=', "<>" and "NOT IN" operators are also interchangeable.
		 *
		 * @param mixed $field String single field, array multiple fields
		 * @param mixed $value String single value, array multiple values
		 * @param mixed $operator "=","IN","!=","NOT IN","<>",">","<",">=","<=","LIKE"
		 *
		 * @return mixed string condition added on success; boolean false on failure
		 */
		function add_condition($field, $operator, $value)
		{
			if($condition = $this->get_condition($field, $operator, $value))
			{
				$this->add_relation($condition);
				return $condition;
			}
			trigger_error('No condition could be made from arguments', HIGH);
			return false;
		}
		function add_condition_set()
		{
			$args = func_get_args();
			if(empty($args))
			{
				trigger_error('get_condition_set() must have at least one argument.');
				return false;
			}
			return call_user_func_array(array($this,'get_condition_set'),$args);
		}
		function get_condition_set()
		{
			$conditions = array();
			$args = func_get_args();
			if(empty($args))
			{
				trigger_error('get_condition_set() must have at least one argument.');
				return NULL;
			}
			$num = 1;
			foreach($args as $arg)
			{
				if($condition = call_user_func_array(array($this,'get_condition'),$arg))
				{
					$conditions[] = $condition;
				}
				else
				{
					trigger_error('Each argument to get_condition_set() must be a 3+ element array that conforms to the requirements of get_condition(). Argument #'.$num.' did not meet requirements.');
					return NULL;
				}
				$num++;
			}
			return '('.implode(' OR ', $conditions).')';
		}
		/**
		 * Get a simple WHERE clause based on field/value/operator parameters
		 *
		 * Pass single or multiple fields and values to be evaluated using a given operator
		 *
		 * If multiple fields or values are passed, evaluation will be based on a logical
		 * OR, e.g. if any of the fields match any of the values using the operator provided
		 *
		 * Note that the "!=" parameter is supported but that all output uses the "<>" 
		 * operator for consistency and db portability
		 *
		 * @todo sanity check/whitelist chars in field names
		 * @todo parse field names into table.field and use backtick quoting style
		 * @todo add support for "%LIKE%","%LIKE","LIKE%"
		 *
		 * @param mixed $field String single field, array multiple fields
		 * @param string $operator "=","<=>","IN","!=","NOT IN","<>",">","<",">=","<=","LIKE","NOT LIKE"
		 * @param mixed $value String single value, array multiple values
		 * @param string $boolean 'AUTO', 'OR' or 'AND'
		 */
		function get_condition($field, $operator, $value, $boolean = 'AUTO', $parens_wrap = true)
		{
			// Gotta have some kind of field
			if(empty($field))
			{
				trigger_error('No field provided to get_condition(). Unable to continue.');
				return NULL;
			}
			if(!in_array($boolean, array('AUTO','OR','AND')))
			{
				trigger_error('Unrecognized boolean value provided to get_condition(). Unable to continue.');
				return NULL;
			}
			
			// Several operators are treated as logically identical in get_condition().
			// Here we map them on to a standard set of operators
			$operator_map = array(
				'!=' => '<>',
			);
			if(isset($operator_map[$operator]))
			{
				$operator = $operator_map[$operator];
			}
			
			// The "AUTO" value for booleans means that negations use AND, other comparators
			// use OR
			$negation_operators = array(
				'<>', 'NOT IN', 'NOT LIKE',
			);
			if('AUTO' == $boolean)
			{
				if(in_array($operator, $negation_operators))
				{
					$boolean = 'AND';
				}
				else
				{
					$boolean = 'OR';
				}
			}
			
			// Coerce the field value into a standard format (e.g. single-value array if 
			// passed a string)
			if(!is_array($field))
				$field = array($field);
			
			$statements = array();
			switch($operator)
			{
				case 'NOT IN':
					if('OR' == $boolean)
					{
						// The NOT IN clause is inherently a logical AND so specifying OR is ambiguous
						trigger_error('The "NOT IN" operator is not compatible with the "OR" boolean.');
						return NULL;
					}
				case 'IN':
					if('AND' == $boolean)
					{
						// The NOT IN clause is inherently a logical AND so specifying OR is ambiguous
						trigger_error('The "IN" operator is not compatible with the "AND" boolean.');
						return NULL;
					}
					if( is_null($value) )
					{
						trigger_error('NULL values are not supported for IN clauses.');
						return NULL;
					}
					elseif( is_array($value) )
					{
						if(empty($value))
						{
							trigger_error('Empty arrays are not supported for IN clauses.');
							return NULL;
						}
						elseif(in_array(NULL, $value, true))
						{
							trigger_error('NULL values are not supported for IN clauses.');
							return NULL;
						}
					} 
					else
					{
						$value = array($value);
					}
					$vals_clause = '('.implode(',',$this->escape_quote($value)).')';
					foreach($field as $f)
					{
						$statements[] = $f.' '.$operator.' '.$vals_clause;
					}
					break;
				case '=':
				case '<=>':
				case '<>':
				case '>':
				case '<':
				case '>=':
				case '<=':
				case 'LIKE':
				case 'NOT LIKE':
					if(is_array($value))
					{
						foreach($value as $val)
						{
							if($clause = $this->get_condition($field, $operator, $val, $boolean, false))
							{
								$statements[] = $clause;
							}
							else
							{
								trigger_error('Unable to process parameters (Field: '.$field.', Operator: '.$operator.', Value: '.$val.')');
								return NULL;
							}
						}
						break;
					}
					if(is_null($value))
					{
						if('=' == $operator)
						{
							$actual_operator = 'IS';
						}
						elseif('<>' == $operator)
						{
							$actual_operator = 'IS NOT';
						}
						elseif('<=>' == $operator)
						{
							$actual_operator = '<=>';
						}
						else
						{
							trigger_error('NULL values are incompatible with comparators other than "=", "<=>", and "<>"');
							return NULL;
						}
						foreach($field as $f)
						{
							$statements[] = $f.' '.$actual_operator.' NULL';
						}
						break;
					}
					if(is_object($value))
					{
						if(!method_exists($value, '__toString'))
						{
							trigger_error('Object values must have a __toString() method to be used in get_condition().');
							return NULL;
						}
					}
					elseif(!is_scalar($value))
					{
						trigger_error('Non-scalar value passed. Unable to proceed.');
						return NULL;
					}
					foreach($field as $f)
					{
						$statements[] = $f.' '.$operator.' '.$this->escape_quote($value);
					}
					break;
				default:
					trigger_error('Unrecognized operator "'.$operator.'" passed to get_condition(). Unable to continue.');
					return NULL;
			}
			$num = count($statements);
			if($num > 1)
			{
				$condition = implode(' '.$boolean.' ',$statements);
				if($parens_wrap)
					$condition = '('.$condition.')';
			}
			elseif(1 == $num)
			{
				$condition = reset($statements);
			}
			else
			{
				trigger_error('No statements made (unknown cause). Unable to proceed.');
				return NULL;
			}
			return $condition;
		}
		/**
		 * Prepare value or value array for use in SQL statement
		 *
		  * String values will be escaped and quoted; integer values will be returned as-is
		  *
		  * Uses single quotes to conform to ANSI SQL standard for maximum portability
		  *
		  * Note that a db connection must exist before this method can be called
		  *
		 * @param $value mixed string, integer, or array of strings or integers
		 */
		function escape_quote($value)
		{
			if(is_array($value))
			{
				return array_map(array($this, 'escape_quote'),$value);
			}
			if(is_int($value))
			{
				return $value;
			}
			return '\''.$this->escape_string($value).'\'';
		}
		
		protected function escape_string($string)
		{
			return carl_util_sql_string_escape($string);
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
