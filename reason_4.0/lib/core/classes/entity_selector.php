<?php
	/**
	 * The entity_selector is a core piece of Reason; it grabs sets of entities out of the database
	 *
	 * @author Brendon Stanton
	 * @author Nathan White
	 * @package reason
	 * @subpackage classes
	 *
	 * 
	 * As of Reason 4.2 this has been modified to not select across the allowable relationship table. This change
	 * depends on using unique relationship names (also in Reason 4.2).
	 */
	 
	 /**
	 * Include the necessary files
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/entity.php' );
	
	/**
	 * defines array_diff_assoc for case where someone is using PHP versions earlier that 4.3
	 */
	if (!function_exists('array_diff_assoc'))
	{
		function array_diff_assoc($a1, $a2)
		{
			foreach($a1 as $key => $value)
			{
				if(isset($a2[$key]))
				{
					if((string) $value !== (string) $a2[$key])
					{
						$r[$key] = $value;
					}
				}
				else
				{
					$r[$key] = $value;
				}
			}
			return $r ;
		}
	}
	
	/**
	 * table of returns the a string in the form [table name].$name
	 * this function is used if you know that a specific entity has a specific name, but you don't
	 * know the specific table.  It can be used like this:
	 * <code>
	 * $es->add_relation( table_of( 'content' , id_of( 'news' ) ) . ' = "stuff"');
	 * </code>
	 * if news has a field called content, it will return the proper string (i.e. chunk.content)
	 * otherwise returns false, which will likely corrupt the query forcing you to go back and fix stuff
	 * also, this table will not find aliases, so if you're using one, you will not be able to use this
	 * @param string $name name of entity's field that you wish to look up
	 * @param int $type entity's type
	 * @return string of form <table>.<field>
	 */
	function table_of( $name , $type) // {{{
	{
		$tables = get_entity_tables_by_type( $type );
		foreach( $tables AS $t )
		{
			$x = get_fields_by_content_table( $t );
			if( in_array( $name , $x ) )
			{
				return $t . '.' . $name;
			}
		}
		return false;
	} // }}}
	
	/**
	 * Generic sorting function for reason
	 *
	 * Works by dynamically creating a comparison function and calling usort
	 * In the following examples, assume that $z is an unsorted array of entities
	 * <code>
	 * entity_sort($z)
	 * </code>
	 * Will sort entities by name using strcmp
	 * <code>
	 * entity_sort($z,'id','DESC','numerical')
	 * </code>
	 * This will use the numerical comparison.  Use this when sorting numbers as opposed
	 * to strings.  You can also create your own comparison functions and pass them in as
	 * parameters.
	 * @author Brendon Stanton
	 * @param array $ents an array of unsorted entities, passed by reference
	 * @param string $field the name of the field for sorting
	 * @param string $dir either 'ASC' or 'DESC'
	 * @param string $cmp the comparison function, use numerical for number comparisons, uses 'strcmp' by default
	 */
	function entity_sort(&$ents,$field='name',$dir='ASC',$cmp='strcmp', $process_null = false) //{{{
	{
		if ($process_null)
		{
			$ec = '';
		}
		else
		{
			$ec = 'if( !$a->get_value(\''.$field.'\') OR !$b->get_value(\''.$field.'\'))'."\n";
			$ec .= 'return 0;'."\n";
		}
		if( !function_exists('numerical') )
		{
			function numerical($a,$b)
			{
				if (empty($a)) $a = '0';
				if (empty($b)) $b = '0';
				return $a-$b;
			}
		}
		if($dir=='ASC')
			$ec .= 'return '.$cmp.'($a->get_value(\''.$field.'\'),$b->get_value(\''.$field.'\'));'."\n";
		else
			$ec .= 'return -'.$cmp.'($a->get_value(\''.$field.'\'),$b->get_value(\''.$field.'\'));'."\n";
		usort($ents,create_function('$a,$b',$ec));
	} // }}}
	
	/**
	 * Checks an array of entities to see if an entity exists in the 
	 * array which has the value of $value in the field $field
	 *
	 * @param array $entity_array the array of entities to search
	 * @param mixed $value the value to search for
	 * @param string $field the name of the field to check
	 * @return mixed will return false if no entity exists, otherwise returns the entity object
	 */
	function entity_in_array($entity_array, $value, $field='id') //{{{
	{
		foreach( $entity_array as $e)
			if( $e->get_value($field) == $value )
				return $e;
		return false;
	} // }}}

	/**
	 * entity_selector class
	 * Extension of DBSelector used for grabbing an array of entity objects.
	 * @author Brendon Stanton
	 */
	class entity_selector extends DBSelector // {{{
	{ 
		/**#@+
		 * @access public
		 */
		/**
		 * @var integer
		 */
		var $type;
		/**
		 * @var integer
		 */
		var $site_id;
		/**
		 * @var string
		 */
		var $description;
		/**
		 * @var boolean
		 */
		var $owns = true;
		/**
		 * @var boolean
		 */
		var $borrows = true;
		/**
		 * @var object entity factory class
		 */
		var $entity_factory_class;
		/**
		 * Table Mod Array
		 *
		 * $table_mod contains an array of entity tables to include or exclude
		 *
		 * This array should be set using the limit_tables() and exclude_tables() function
		 * @var array
		 */
		var $table_mod = array();

		/**
		 * Table Mod Action
		 *
		 * $table_mod_action specifies whether to include or exclude tables in $table_mod
		 *
		 * This value should be set using the limit_tables() and exclude_tables() function
		 *
		 * @var string table_mod should be the string "include" or "exclude"
		 */
		var $table_mod_action = '';

		var $limit_fields = '';
		
		/**#@-*/	
		/**
		 * Contains the local environment
		 * @access private
		 * @var array
		 */
		var $_env = array( 'restrict_site' => true );
		
		/**
		 * Contains the relationship sort value queue - should be processed right before query
		 * @access private
		 * @var array
		 */
		var $_rel_sort_field_queue = array();
		
		/**
		 * Storage for the multivalue result mode
		 *
		 * If this is on, relationship fields with multiple entries returned will have those values
		 * stored as an array in the entity. Be aware that when this is set to true, 
		 * $entity->get_value() may either return a string or an array
		 *
		 * This var shouldn't be set directly -- use enable_multivalue_results() and disable_multivalue_results().
		 * @access private
		 * @var boolean
		 */
		var $_enable_multivalue_results = false;

		/**
		 * Should I try to dynamically exclude tables that aren't used in the select.
		 *
		 * This can increase performance significantly in cases where you might not know which tables
		 * should be used in advance, but you want to only include the tables that you need. It relies on 
		 * parsing anything added to add_relation before building the query, and will not work well if you
		 * have statements in add_relation that don't use fully qualified entity_table.field_name pairs.
		 */
		var $_exclude_tables_dynamically = false;
				
		/**
 		 * Constructor 
		 * @param int $site_id optional site id, if set will only select entities belonging to the given site
		 */
		 
		var $union = false;
		
		function entity_selector($site_id = false) // {{{
		{
			$this->query = '';
			$this->fields = array();
			$this->tables = array();
			$this->relations = array();
			$this->orderby = '';
			$this->optimize = '';
			$this->start = 0;
			$this->num = -1;
			$this->type = array();
			$this->site_id = $site_id;
		} // }}}
 		 
 		  /**
 		  * Does the actual work of adding relationship sort fields to the entity selector
 		  * @param int $entity_id the entity on the left side of a relationship
 		  * @param int $relationship_id optional parameter specifying relationship ID
 		  * @param string $alias defaults to 'rel_sort_order'
 		  * @access private
 		  */
 		 function _add_rel_sort_field($entity_id, $allowable_rel_id = '', $alias = 'rel_sort_order')
 		 {
 		 	//extract name of field to add
 		 	$relation_array = $this->relations;
 		 	$rel_name_from_entity_id = $rel_name_from_rel_type = '';
 		 	while ($cur_element = array_pop($relation_array)) // begin at end
 		 	{
 		 		if (!empty($allowable_rel_id))
 		 		{
 		 			$test_rel_id_start = strpos($cur_element, 'relationship');
 		 			$test_rel_id_end = strpos($cur_element, '.type = '. $allowable_rel_id);
 		 			if ($test_rel_id_start !== false && $test_rel_id_end !== false)
 		 			{
 		 				$rel_name_from_rel_type = substr($cur_element, 0, $test_rel_id_end);
 		 				continue;
 		 			}
 		 		}
 		 		
 		 		$test_entity_id = strpos($cur_element, '.entity_a = ' . $entity_id);
 		 		if ($test_entity_id != false)
 		 		{
 		 			$rel_name_from_entity_id = substr($cur_element, 0, $test_entity_id);
 		 		}
 		 		
 		 		if (!empty($allowable_rel_id) && (!empty($rel_name_from_entity_id)) && (!empty($rel_name_from_rel_type))
 		 		    && ($rel_name_from_entity_id == $rel_name_from_rel_type))
 		 		{
 		 			$relation_array = array();
 		 			if (!in_array($rel_name_from_entity_id.".rel_sort_order AS ".$alias, $this->fields))
 		 			$this->add_field($rel_name_from_entity_id, 'rel_sort_order', $alias);
 		 		}
 		 		
 		 		elseif (empty($allowable_rel_id) && !empty($rel_name_from_entity_id))
 		 		{
 		 		 	$relation_array = array();
 		 		 	if (!in_array($rel_name_from_entity_id.".rel_sort_order AS ".$alias, $this->fields))
 		 		 	$this->add_field($rel_name_from_entity_id, 'rel_sort_order', $alias);
 		 		}
 		 	}
 		 }
 		 
 		 /**
	 	 * Adds relationship sort field to entities. 
	 	 * 
	 	 * The following code adds the rel_sort value in the relationships table for entities where a page (entity_a) 
	 	 * is on the left side of a relationship with an image (entity_b). 
	 	 * 
	 	 * <code>
	 	 * $es->add_rel_sort_field($page->id(), relationship_id_of('minisite_page_to_image'))
 		 * </code>
 		 *
 		 * Relationship sort values can be added for multiple types, and should be handled appropriately within any module
 		 * The actual private function _add_rel_sort_field is executed at the time of get_one_query
 		 * @param int $entity_id the entity on the left side of a relationship
 		 * @param int $relationship_id optional parameter specifying relationship ID
 		 * @param string $alias defaults to 'rel_sort_order'
 		 * @return void
 		 */
 		 function add_rel_sort_field($entity_id, $allowable_rel_id = '', $alias = 'rel_sort_order')
 		 {
 		 	$this->_rel_sort_field_queue[] = array($entity_id, $allowable_rel_id, $alias);
 		 }
 		 
 		 /**
 		  * Invokes _add_rel_sort_field() to process relationship sort field additions to entity selector
 		  * @access private
 		  */
 		 function _process_rel_sort_fields()
 		 {
 		 	foreach($this->_rel_sort_field_queue as $rel_sort)
 		 	{
 		 		$this->_add_rel_sort_field($rel_sort[0], $rel_sort[1], $rel_sort[2]);
 		 	}
 		 }
 		 
		/**
		 * Optimize entity selector
		 *
		 * currently the only meaningful options are 1) "straight_join," which can speed up
		 * some queries that select entities across multiple sites, 2) 'distinct', which adds
		 * the distinct keyword before the entity.id, and 3) "" (empty string,)
		 * which removes a previously set optimization.
		 *
		 * @param string to indicate type of optimization
		 * @return void
		 */
		function optimize($type)
 		{
 			if (strtolower($type) == 'straight_join')
 			{
 				$this->optimize = 'STRAIGHT_JOIN';
 			}
 			if (strtolower($type) == 'distinct')
 			{
 				$this->optimize = 'DISTINCT';
 			}
 			elseif($type == '')
 			{
 				$this->optimize = '';
 			}
 		}

		/**
		 * Sets the sharing level for the function.  If the paramater is a string, checks to see if it contains "owns" or "borrows".
		 * If parameter is an array, checks for keys "owns" or "borrows".
		 * <code>
		 * $es->set_sharing( "owns,borrows" );
		 * </code>
		 * @param mixed $args names of allowable sharing relations
		 * @return void
		 */
		function set_sharing( $args ) // {{{
		{
			if( is_array( $args ) )
			{
				if( in_array( 'owns' , $args ) )
					$this->owns = true;
				else
					$this->owns = false;

				if( in_array( 'borrows' , $args ) )
					$this->borrows = true;
				else
					$this->borrows = false;
			}
			elseif( is_string( $args ) )
			{
				if( preg_match( '/owns/' , $args ) )
					$this->owns = true;
				else
					$this->owns = false;

				if( preg_match( '/borrows/' , $args ) )
					$this->borrows = true;
				else
					$this->borrows = false;
			}
		} // }}}



		/**
		 * SWALLOW
		 */

		/**
		 *
		 * Takes another DBSelector or entity_selector and combines it with the current table.
		 * Ignores tables with the same name. 
		 * @param entity_selector $es
		 * @return void
		 */
		function swallow($es) // swallows another selector object{{{
		{
			if(is_object($es))
			{
				$this->swallow_tables($es);
				$this->swallow_fields($es);
				$this->swallow_relations($es);
				$this->swallow_start($es);
				$this->swallow_num($es);
				$this->swallow_order($es);
				$this->swallow_types($es);
				$this->swallow_optimize($es);
			}
		} // }}}
		/**#@+
		 * Helper Function for swallow.
		 * @param entity_selector $es
		 * @access private
		 * @return void
		 */
		function swallow_tables($es) // {{{
		{
			$tables = $es->tables;
			reset( $tables );
			while( list ($alias, $value) = each( $tables ) )
			{
				if(!isset($this->tables[ $alias ]))
					$this->tables[ $alias ] = $value;
			}
		} // }}}
		function swallow_fields($es) // {{{
		{
			$fields = $es->fields;
			reset( $fields );
			while( list( , $value) = each( $fields ) )
				$this->fields[] = $value;
		} // }}}
		function swallow_relations($es) // {{{
		{
			$relations = $es->relations;
			reset( $relations );
			while( list( , $value) = each( $relations ) )
				$this->relations[] = $value;
		} // }}}
		function swallow_start($es) // {{{
		{
			$this->start = $es->start;
		} // }}}
		function swallow_num($es) // {{{
		{
			$this->num = $es->num;
		} // }}}
		function swallow_order($es) // {{{
		{
			if(!empty($es->orderby))
			{
				if(empty($this->orderby))
					$this->orderby = $es->orderby;
				else
					$this->orderby = $this->orderby . ', ' . $es->orderby;
			}
		} // }}}
		function swallow_types($es) // {{{
		{
			if( (isset( $es->type ) AND !is_array($es->type) ) OR !isset( $es->type ))
				$es->type = array();
			reset($es->type);
			while( list( , $value) = each($es->type))
			{
				$add = true;
				reset($this->type);
				while( (list( , $v2) = each($this->type)) AND $add)
					if($value == $v2) $add = false;
				if($add) $this->type[] = $value;
			}
		} // }}}
		
		function swallow_optimize($es)
		{
			if(!empty($es->optimize))
			{
				if(empty($this->optimize))
					$this->optimize = $es->optimize;
				else
					$this->optimize = $this->optimize . ', ' . $es->optimize;
			}
		}
		
		/**#@-*/	
		

		/**
		 * MERGE
		 */

		/**
		 * Takes another DBSelector or entity_selector and combines it with the current table.
		 * Attempts to rename tables intelligently.  Sets num and start so that they select 
		 * the max number of objects specified.  If num is set to -1 (i.e. select all) it gets
		 * overwritten with the more restricted one
		 * @param entity_selector $es
		 * @return void
		 */
		function merge($es) // merges current object with another selector object{{{
		{
			if(is_object($es))
			{
				$tables = $this->merge_tables($es);
				$this->merge_fields($es, $tables);
				$this->merge_relations($es, $tables);
				$this->merge_start($es);
				$this->merge_num($es);
				$this->merge_order($es, $tables);
				$this->merge_types($es);
				if( $tables[ 'entity' ] )
					$this->add_relation( 'entity.id = ' . $tables[ 'entity' ] . '.id' ); 
			}
		} // }}}
		/**
		 * recursively renames the tables trying to start with #2
		 * if that is taken, calls the function again with $num+1
		 * @param string $name table name
		 * @param int $num default value of 2, should never be filled in from an outside function
		 * @return string new name of table
	  	 */
		function get_new_table_name($name , $num = 2) // {{{
		{
			if(isset($this->tables[ $name.$num ] ))
				return $this->get_new_table_name( $name , $num +1 );
			return $name.$num;
		} // }}}
		/**#@+
		 * Helper Function for merge.
		 * @access private
		 * @param entity_selector $es
		 * @return void
		 */
		function merge_tables($es) // {{{
		{
			$rename = array();
			$tables = $es->tables;
			reset( $tables );
			while( list ($alias, $value) = each( $tables ) )
			{
				if(isset($this->tables[ $alias ]))
				{
					$new_alias = $this->get_new_table_name($alias);
					$rename[ $alias ] = $new_alias;
					$this->tables[ $new_alias ] = $value;
				}
				else
					$this->tables[ $alias ] = $value;
			}
			return $rename;
		} // }}}
		function merge_fields($es, $tables) // {{{
		{
			$fields = $es->fields;
			reset( $fields );
			while( list( , $value) = each( $fields ) )
			{
				reset($tables);
				while( list($pat, $rep) = each($tables))
					$value = preg_replace('|^'.$pat.'\.|' , $rep.'.' , $value );
				
				$this->fields[] = $value;
			}
		} // }}}
		function merge_relations($es, $tables) // {{{
		{
			$relations = $es->relations;
			reset( $relations );
			while( list( , $value) = each( $relations ) )
			{
				reset($tables);
				while( list($pat, $rep) = each($tables))
				{
					$value = preg_replace('|^'.$pat.'\.|' , ' '.$rep.'.' , $value );
					$value = preg_replace('|[ \f\r\t\n,=]'.$pat.'\.|' , ' '.$rep.'.' , $value );
				}
				$this->relations[] = $value;
			}
		} // }}}
		function merge_start($es) // {{{
		{
			if($es->start < $this->start)
				$this->start = $es->start;
		} // }}}
		function merge_num($es) // {{{
		{
			$start = min($es->start, $this->start);
			$fint = $this->start + $this->num -1;
			$fine = $es->start + $es->num -1;
			
			if(($this->num == -1)&&($es->num == -1))
				$finish = -1;
			elseif($this->num == -1)
				$finish = $fine;
			else
				$finish = max($fint, $fine);
			
			if($finish == -1) $this->num = -1;
			else
				$this->num = $finish - $start + 1;
		} // }}}
		function merge_order($es, $tables) // {{{
		{
			if(!empty($es->orderby))
			{
				if(empty($this->orderby))
					$this->orderby = $es->orderby;
				else
				{
					$value = $es->orderby;
					reset($tables);
					while( list($pat, $rep) = each($tables))
					{
						$value = preg_replace('|^'.$pat.'\.|' , ' '.$rep.'.' , $value );
						$value = preg_replace('|[ \f\r\t\n,=]|'.$pat.'\.|' , ' '.$rep.'.' , $value );
					}
					$this->orderby = $this->orderby . ', ' . $value;
				}
			}
		} // }}}
		function merge_types($es) // {{{
		{
			if(is_array($es->type))
				$es->type = array();
			reset($es->type);
			while( list( , $value) = each($es->type))
			{
				$add = true;
				reset($this->type);
				while( (list( , $v2) = each($this->type)) AND $add)
					if($value == $v2) $add = false;
				if($add) $this->type[] = $value;
			}
		} // }}}
		/**#@-*/	

		/**
		 * Other class functions
		 */
		/**
		 * Sets a local enviornment variable.
		 *
		 * This can be used to help with selections on stuff like selecting relationship sites.
		 * <code>
		 * $es = new entity_selector();
		 * $es->add_type( $v[ 'relationship_b' ] );
		 * $es->set_env( 'site' , $this->admin_page->site_id );
		 * $es->add_right_relationship( $this->admin_page->id , $v[ 'id' ] );
		 * $es->set_env( 'restrict_site' , false );
		 * $es->add_right_relationship( 7 , 'site_to_donkey' );
		 * </code>
		 * The above code will restrict the first relationship to only those relationships that have
		 * 0 or $this->admin_page->site_id in the site id column.  For the site_to_donkey relationship,
		 * it will select all relationships regardless of the site column
		 * @param string $field name of the field
		 * @param mixed $value value of the field
		 */
		function set_env( $field , $value ) //{{{
		{
			$this->_env[$field] = $value;
		} // }}}
		/**
		 * Adds a type to the entity selector.  Normally, this is only called once per ES.
		 * If more than one type is set up in the $type array, then when run() is called
		 * the ES will return an array of all the different types.  However, in practice it
		 * is generally easier to just create a seperate entity_selector for each type.
		 * @param int $id The id of the type you want to select
		 */
		function add_type( $id ) // {{{
		{
			if(turn_into_int($id) == $id)
			{
				$this->type[] = $id;
			}
			else
			{
				trigger_error('entity_selector::add_type not passed an integer', EMERGENCY);
			}
		} // }}}
		/**
		 * Sets the site id for the ES.  This is often not needed as the site_id can be set up
		 * in the constructor.  If site ID is not set up, then the entity select will select all
		 * items of that type, regardless of site.
		 * @param int $id the site's id
		 */
		function set_site( $id ) // {{{
		{
			$this->site_id = $id;
		} // }}}
		/**
		 * Assures that the entities we select are on the left side of a relationship with $entity_id.
		 * In other words, entity_a is the entity we are selecting, and entity_b is the entity with id $entity_id.
		 * If $relationship_type is set, then it also assures that the relationship will be of that type.
		 * Looking at this code now, I'm not sure it will work properly if this function is called more than once
		 * per entity_selector.
		 * @param mixed $entity_id the id of the the entity with which our entities have a relationship (can also be an array of ids)
		 * @param int $relationship_type the type of relationship our entities have
		 * @return void
		 * @todo retest this method calling it more than once.  fix it if it needs fixin
		 */
		function add_left_relationship( $entity_id , $relationship_type = false ) // {{{
		{
			if (!$relationship_type)
			{
				$call_info = array_shift( debug_backtrace() );
        		$code_line = $call_info['line'];
        		$file = array_pop( explode('/', $call_info['file']));
        		$msg = 'entity selector method add_left_relationship called by ' . $file . ' on line ' . $code_line . ' without parameter 2 (relationship_type).';
				trigger_error($msg, WARNING);
			}
			$es = new entity_selector( $this->site_id );
			$es->add_table('relationship' , 'relationship');
			$tables = $this->merge_tables( $es );
			if(isset( $tables['relationship'] ) AND $tables['relationship']) 
			{
				$relationship_name = $tables['relationship'];
			}
			else $relationship_name = 'relationship';
			
			$this->add_relation( $relationship_name . '.entity_a = entity.id' );
			if(is_array($entity_id))
			{
				$prepped_entity_ids = $entity_id;
				array_walk($prepped_entity_ids, 'db_prep_walk');
				$this->add_relation( $relationship_name . '.entity_b IN (' . implode(',',$prepped_entity_ids) . ')' );
			}
			else
				$this->add_relation( $relationship_name . '.entity_b = "' . addslashes($entity_id) . '"');
			if($relationship_type)
			{
				$this->add_relation( $relationship_name . '.type = ' . $relationship_type );
			}
			if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
			{
				$this->add_relation( '(' . $relationship_name . '.site=0 OR ' . $relationship_name . '.site=' . $this->_env['site'] . ')' );
			}
		} // }}}
		/**
		 * Assures that the entities we select are on the right side of a relationship with $entity_id.
		 * In other words, entity_b is the entity we are selecting, and entity_a is the entity with id $entity_id.
		 * If $relationship_type is set, then it also assures that the relationship will be of that type.
		 * Looking at this code now, I'm not sure it will work properly if this function is called more than once
		 * per entity_selector.
		 * @param mixed $entity_id the id of the the entity with which our entities have a relationship (can also be an array of ids)
		 * @param int $relationship_type the type of relationship our entities have
		 * @return void
		 * @todo retest this method calling it more than once.  fix it if it needs fixin
		 */
		function add_right_relationship( $entity_id , $relationship_type = false ) // {{{
		{
			if (!$relationship_type)
			{
				$backtrace = debug_backtrace();
				$call_info = array_shift( $backtrace );
        		$code_line = $call_info['line'];
        		$line_parts = explode('/', $call_info['file']);
        		$file = array_pop( $line_parts );
        		$msg = 'entity selector method add_right_relationship called by ' . $file . ' on line ' . $code_line . ' without parameter 2 (relationship_type).';
				trigger_error($msg, WARNING);
			}
			$es = new entity_selector( $this->site_id );
			$es->add_table('relationship' , 'relationship');
			$tables = $this->merge_tables( $es );
			if(isset( $tables['relationship'] ) AND $tables['relationship']) 
			{
				$relationship_name = $tables['relationship'];
			}
			else
				$relationship_name = 'relationship';
			
			$this->add_relation( $relationship_name . '.entity_b = entity.id' );
			if(is_array($entity_id))
			{
				$in = "";
				foreach( $entity_id AS $e_id )
				{
					$in .= $e_id . ',';
				}
				$in = substr($in, 0,-1);
				$this->add_relation( $relationship_name . '.entity_a IN (' . $in . ')' );
			}
			else
				$this->add_relation( $relationship_name . '.entity_a = ' . $entity_id );
			if($relationship_type)
			{
				$this->add_relation( $relationship_name . '.type = ' . $relationship_type );
			}
			if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
			{
				$this->add_relation( '(' . $relationship_name . '.site=0 OR ' . $relationship_name . '.site=' . $this->_env['site'] . ')' );
			}
		} // }}}
		/**
		 * Gets one of the queries.  Works by creating a new entity selector, adding the owns/borrows
		 * relationship, swallowing a db_selector object, then swallowing the current object.
		 * sort of a funny want to do things, but it assures us that we'll get all the right ones
		 * @param int $type the type id to be selected
		 * @param string $status either Live, Archived, Pending, or All
		 * @return string MYSQL query string to select the objects from the DB.
		 * @todo $status should really be the first variable here, not the second.  I don't think this is called outside of the class anywhere on the site, but it would be disasterous if it did and we switched it.
		 */
		function get_one_query( $type = '', $status = 'Live' ) // {{{
		{
			if( !$type )
			{
				if( $this->type[0] )
					$type = $this->type[0] ;
				else 
					return '';
			}
			
			if (count($this->_rel_sort_field_queue) > 0) $this->_process_rel_sort_fields();
			
			$new_e = new entity_selector($this->site_id);
			$sharing = '';
			if( $this->owns )
				$sharing .= 'owns';
			if( $this->borrows )
				$sharing .= 'borrows';
			if( $status != 'All' )
				$new_e->add_relation( 'entity.state = "'.$status.'"' );
			else
				$new_e->add_relation( 'entity.state != "Archived"' );
			
			if (is_array($this->limit_fields) && empty($this->table_mod) && $this->_exclude_tables_dynamically)
			{
				$to_exclude = false;
				$tables = get_entity_tables_by_type( $type );
				foreach ($tables as $table)
				{
					$exclude = true;
					if ($table == 'entity') $exclude = false;
					if (($exclude == true) && (strpos($this->orderby, $table . '.') !== FALSE)) $exclude = false;
					if ($exclude == true)
					{
						$limit_fields = $this->limit_fields;
						while ($cur_element = array_pop($limit_fields))
						{
							if (strpos($cur_element, $table . '.') !== FALSE)
 		 					{
 		 						$exclude = false;
 		 						continue;
 		 					}
						}
					}
					if ($exclude == true)
					{
						$relation_array = $this->relations;
 		 				while ($cur_element = array_pop($relation_array))
 		 				{
 		 					if (strpos($cur_element, $table . '.') !== FALSE)
 		 					{
 		 						$exclude = false;
 		 						continue;
 		 					}
 		 				}
					}
					if ($exclude) $to_exclude[] = $table;
 		 		}
 		 		if ($to_exclude)
 		 		{
 		 			$this->exclude_tables($to_exclude);
 		 		}
			}
			
			$new_e->swallow( get_entities_by_type_object( $type , $this->site_id, $sharing, $this->table_mod, $this->table_mod_action ));
				
			if (is_array($this->limit_fields)) $new_e->fields = array_unique(array_merge(array('entity.id'), $this->limit_fields));
			$new_e->swallow( $this );
			
			
			if ($this->union)
			{
				$union_clause = '';
				$union_es = carl_clone($new_e);
				
				foreach ($this->diff['fields'] as $k=>$union)
				{
					$multi_union_es[$k] = carl_clone($new_e);
					$multi_union_es[$k]->fields = array_diff($union_es->fields, $this->diff['fields'][$k]);
					$multi_union_es[$k]->tables = array_diff_assoc($union_es->tables, $this->diff['tables'][$k]);
					$multi_union_es[$k]->relations = array_diff($union_es->relations, $this->diff['relations'][$k]);
					
					$alter_array = (array_diff($union_es->fields, $multi_union_es[$k]->fields));
					foreach ($alter_array as $k2=>$v)
					{
						if (isset($this->union_fields[$v]))
						$multi_union_es[$k]->fields[$k2] = $this->union_fields[$v];
					}
					ksort($multi_union_es[$k]->fields);
				}

				foreach ($multi_union_es as $mu_es) $merged_es[] = carl_clone($mu_es);
				
				if (count($multi_union_es) > 1) // if we are unifying multiples we want to run altered multi_union_es queries and one additional
				{
					// prep final selector - needs to select all while padding left and right relationship fields with 0
					foreach ($this->diff['fields'] as $k=>$union)
					{
						$union_es->fields = array_diff($union_es->fields, $this->diff['fields'][$k]);
						$union_es->tables = array_diff_assoc($union_es->tables, $this->diff['tables'][$k]);
						$union_es->relations = array_diff($union_es->relations, $this->diff['relations'][$k]);
					}
					foreach ($new_e->fields as $k=>$v)
					{
						if (!isset($union_es->fields[$k])) $union_es->fields[$k] = $this->union_fields[$v];
						
					}
					ksort($union_es->fields); //$union_es now holds a base es
					
					foreach ($merged_es as $k => $alter_es)
					{
						// fix fields - invert what is being selected as 0 and those that are not
						foreach ($alter_es->fields as $k2=>$v)
						{
							if (isset($this->union_fields[$v])) $merged_es[$k]->fields[$k2] = $this->union_fields[$v];
							elseif (in_array($v, $this->union_fields)) $merged_es[$k]->fields[$k2] = current($this->diff['fields'][$k]);
						}
						
						$merged_es[$k]->tables = array_merge($this->diff['tables'][$k], $union_es->tables);
						$merged_es[$k]->relations = array_merge($this->diff['relations'][$k], $union_es->relations);
					}
					$merged_es[] = carl_clone($union_es);
				}
				else
				{
					$merged_es[] = carl_clone($new_e);
				}
				return $this->get_merged_query($merged_es);
			}
			else return $new_e->get_query();
		} // }}}
		/**
		 * Gets all the queries from the ES.
		 * @return void
		 */
		function get_queries() // {{{
		{	
			$queries = array();
			reset( $this->type );
			while( list( , $one_type) = each( $this->type ) )
				$queries[] = $this->get_one_query( $one_type );
			return $queries;
		} // }}}
		
		/**
		 * Get ids - same args as run_one, but does the query and returns an array of the ids that match.
		 *
		 * <code>
		 * $my_ids = $es->get_ids();
		 * </code>
		 *
		 * Basically - this a low memory alternative to this:
		 *
		 * <code>
		 * $my_entities = $es->run_one();
		 * $my_ids = array_keys($my_entities);
		 * </code>
		 *
		 * @param int $type type_id (or blank for default)
		 * @param string $status Either Live, Pending, Archived... (optional)
		 * @param string $error optional error message
		 * @return array
		 */
		function get_ids($type = '', $status = 'Live', $error = 'get_ids_error')
		{
			$ids = array();
			if( !$type )
			{
				if( isset($this->type[0]) && $this->type[0] )
				{
					$type = $this->type[0] ;
				}
				else
				{
					trigger_error('Entity Selector: No type available. Try using the method add_type($type_id) before calling get_ids(), or call get_ids() with the type id as the first argument.');
					return array();
				}
			}
			$r = db_query( $this->get_one_query( $type , $status) , $this->description.': '.$error );
			while($row = mysql_fetch_assoc($r))
			{
				$ids[] = $row['id'];
			}
			mysql_free_result( $r );
			return $ids;
		}
		
		/**
		 * Runs one query for the ES.  If type is empty, it uses the first type by default.
		 * This is often called without paramaters in code for front end stuff.
		 * <code>
		 * $es = new entity_selector( $site_id );
		 * $es->add_type( $type_id );
		 * $results = $es->run_one();
		 * </code>
		 * @param int $type type_id (or blank for default)
		 * @param string $status Either Live, Pending, Archived, or All ... (optional)
		 * @param string $error optional error message
		 * @return array
		 */
		function run_one($type = '', $status = 'Live' , $error = 'run_one error') // runs query for one type, returns array of results {{{
		{
			if( !$type )
			{
				if( isset($this->type[0]) && $this->type[0] )
				{
					$type = $this->type[0] ;
				}
				else
				{
					trigger_error('Entity Selector: No type available. Try using the method add_type($type_id) before calling run_one(), or call run_one() with the type id as the first argument.');
					return array();
				}
			}
			$query = $this->get_one_query( $type , $status);
			$factory =& $this->get_entity_factory();
			if($this->cache_lifespan)
			{
				$factory_class = ($factory) ? get_class($factory) : '';
				//echo '<p>caching '.$this->cache_lifespan.' secs</p>';
				$cache = new ObjectCache('entity_selector_cache_'.get_current_db_connection_name().'_'.$this->_enable_multivalue_results.'_'.$factory_class.'_'.$query, $this->cache_lifespan);
				$results =& $cache->fetch();
				if(false !== $results)
				{
					//echo '<p>Cache hit</p>';
					return $results;
				}
				//echo '<p>Cache miss</p>';
			}
			$results = array();
			$r = db_query( $query , $this->description.': '.$error );
			
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			{
				//pray ($row);
				if($this->_enable_multivalue_results && isset($results[ $row[ 'id' ] ]))
				{
					$prev_val = $new_val = $key = $val = '';
					$e = $results[ $row[ 'id' ] ];
					foreach($row as $key=>$val)
					{
						$cur_value = $e->get_value($key);
						if (is_array($cur_value))
						{
							if (!in_array($val, $cur_value) && !empty($val))
							{
								$cur_value[] = $val;
								$e->set_value($key, $cur_value);
							}
						}
						elseif (($cur_value != $val) && (!empty($val)))
						{
							if (empty($cur_value)) $e->set_value($key, $val);
							else $e->set_value($key, array($cur_value, $val));
						}						
					}
				}
				else
				{
					if ($factory)
					{
						$e = $factory->get_entity( $row );
					}
					else
					{
						$e = new entity( $row[ 'id' ] );
					}
					$e->_values = $row;
				}
				$results[ $row[ 'id' ] ] = $e;
			}
			mysql_free_result( $r );
			if(!empty($cache))
				$cache->set($results);
			return $results;
		} // }}}
		/**
		 * Turns on multivalue results
		 * 
		 * When multivalue results is on, relationship fields that are multiply matched will all be returned
		 * in an array as the entities' value for the field.
		 *
		 * Otherwise, the last value overwrites previous ones and all entity values are strings
		 * @return void
		 */
		function enable_multivalue_results()
		{
			$this->_enable_multivalue_results = true;
		}
		
		/**
		 * Turns off multivalue results
		 * 
		 * When multivalue results is on, relationship fields that are multiply matched will all be returned
		 * in an array as the entities' value for the field.
		 *
		 * Otherwise, the last value overwrites previous ones and all entity values are strings
		 * @return void
		 */
		function disable_multivalue_results()
		{
			$this->_enable_multivalue_results = false;
		}
		
		/**
		 * Sets exclude tables dynamically
		 * 
		 * When on, we exclude tables from the query that meet this criteria:
		 * 
		 * 1. Are not part of a field name that we are selecting.
		 * 2. Are not the table in a table.field_name string in a relation statement.
		 *
		 * @param boolean true or false
		 * @return void
		 */
		function exclude_tables_dynamically( $boolean = true )
		{
			$this->_exclude_tables_dynamically = $boolean;
		}
		
		/**
		 * Runs all queries for the ES.
		 * @param string $status Either Live, Pending, Archived... (optional)
		 * @param string $error optional error message
		 * @return void
		 */
		function run( $status = 'Live', $error = '') // returns an array of arrays of entities // {{{
		{
			$entities = array();
			reset( $this->type );
			while(list( , $type) = each( $this->type ))
				$entities[ $type ] = $this->run_one( $type , $status, $error );
			return $entities;
		} // }}} 

		/**
		 * Gets the number of entities a query of one type will return
		 * @param string $status Either Live, Pending, Archived... (optional)
		 * @param int $type type_id (or blank for default)
		 * @return int number of results
		 */
		function get_one_count( $status = 'Live' , $type = '') // returns total number of results that match the query.  ignores the start and num variables {{{
		{
			if(!$type)
			{
				if($this->type[0])
					$type = $this->type[0];
				else return 0;
			}
			$new_e = new entity_selector($this->site_id);
			$sharing = '';
			if( $this->owns )
				$sharing .= 'owns';
			if( $this->borrows )
				$sharing .= 'borrows';
			$new_e->swallow( get_entities_by_type_object( $type , $this->site_id, $sharing));
			$new_e->swallow( $this );
			if($status != 'All' )
				$new_e->add_relation( 'entity.state = "' . $status . '"' );
			else
				$new_e->add_relation( 'entity.state != "Archived"' );
			
			if ($this->union) // count based on query for entities which match or do not match conditionals
			{
				$key_search = array_flip($new_e->fields);
				foreach ($this->diff['fields'] as $k=>$union)
				{
					$new_e->fields = array_diff($new_e->fields, $this->diff['fields'][$k]);
					$new_e->tables = array_diff_assoc($new_e->tables, $this->diff['tables'][$k]);
					$new_e->relations = array_diff($new_e->relations, $this->diff['relations'][$k]);
				}
			}
			return $new_e->get_count();
				
		} // }}}
		/**
		 * Gets all the counts for a query
		 * @return array array of results
		 */
		function get_counts() // returns an array of counts for each type // {{{
		{
			$counts = array();
			reset( $this->type );
			while(list( , $type) = each( $this->type ))
				$counts[ $type ] = $this->get_one_count( $type );
			return $counts;
		} // }}} 

		/**
		 * Gets the entity with the greatest value for the specified field
		 * @param string $field the name of the field for which you want the max value
		 * @param int $type type_id (or blank for default)
		 * @return entity entity with max value of field 
		 */
		function get_max( $field , $type = false) // {{{
		{
			if(!$type)
			{
				if($this->type[0])
					$type = $this->type[0];
				else return false;
			}
			$m = new entity_selector($this->site_id);
			$m->swallow($this);
			$m->set_order( $field . ' DESC' );
			$m->set_num(1);

			$item = $m->run_one( $type , 'Live', "error selecting max");
			if(!$item) return false;
			$current = end($item);
			
			return $current;
		} // }}}
		/**
		 * Gets the entity with the smallest value for the specified field
		 * @param string $field the name of the field for which you want the min value
		 * @param int $type type_id (or blank for default)
		 * @return entity entity with min value of field 
		 */
		function get_min( $field , $type = false) // {{{
		{
			if(!$type)
			{
				if($this->type[0])
					$type = $this->type[0];
				else return false;
			}
			$m = new entity_selector($this->site_id);
			$m->swallow($this);
			$m->set_order( $field . ' ASC' );
			$m->set_num(1);

			$item = $m->run_one( $type ,'Live', "error selecting min");
			if(!$item) return false;
			$current = end($item);
			return $current;
		} // }}}
		/**
		 * Adds a new field to the entity which is actually not a field of entity, but rather a field of 
		 * an entity which is related to the current entities.  The entities selected by the ES will be 
		 * on the left side of the relationship.
		 * Will return multiples of an entity if it has multiples of the same relationship.  Not sure
		 * how to change this.
		 * @param string $rel_name the name of the relationship between the entities
		 * @param string $table the table where the field is
		 * @param string $field the name of the field to be selected
		 * @param string $alias that alias for the field
		 * @param mixed $limit_results true return only row for which the related value is defined
		 * 							   false to return all results even if the value does not exist
		 *							   string or array to limit results to the values passed
		 * @return void
		 */
		function add_left_relationship_field( $rel_name , $table , $field , $alias, $limit_results = true ) // {{{
		{
			if ( ($rel_name != "owns") && ($rel_name != "borrows") && !empty($rel_name)) $rel_type_id = relationship_id_of($rel_name);
			elseif ( $rel_name == 'owns' || $rel_name == 'borrows')
			{
				if (empty($this->type))
				{
					$call_info = array_shift( debug_backtrace() );
        			$code_line = $call_info['line'];
        			$file = array_pop( explode('/', $call_info['file']));
        			$msg = 'entity selector method add_left_relationship_field called by ' . $file . ' on line ' . $code_line . ' on a generic "owns" or "borrows" relationship when the type has not been set on the entity selector.';
					trigger_error($msg, WARNING);
					return false;
				}
				elseif ($rel_name == "owns") $rel_type_id = get_owns_relationship_id(reset($this->type));
				elseif ($rel_name == "borrows") $rel_type_id = get_borrows_relationship_id(reset($this->type));
			}
			if (empty($rel_type_id))
			{
				trigger_error('add_left_relationship_field failed - an id could not be determined from the relationship name provided');
				return false;
			}
			if ($limit_results === false)
			{
				$cur_es = carl_clone($this);
				$this->union = true;
			}
			
			$es = new entity_selector();
			
			$es->add_table( 'relationship' , 'relationship' );
			$es->add_table( '__entity__' , 'entity' );
			
			if($table != 'entity' )
				$es->add_table( $table );
			$tables = $this->merge_tables($es);
			
			if( isset( $tables[ 'relationship' ] ) AND $tables[ 'relationship' ] )
				$r = $tables[ 'relationship' ];
			else 
				$r = 'relationship';

			if( isset( $tables[ '__entity__' ] ) AND $tables[ '__entity__' ] )
				$e = $tables[ '__entity__' ];
			else
				$e = '__entity__';

			if( $table == 'entity' )
				$t = $e;
			else
			{
				
				if( !empty($tables[ $table ]) )
					$t = $tables[ $table ];
				else
					$t = $table;
			}	
			
			if( $e != $t )
				$this->add_relation( $e . '.id = ' . $t . '.id' );
			
			$this->add_relation( $e . '.id = ' . $r . '.entity_b' );
			$this->add_relation( 'entity.id = ' . $r . '.entity_a' );
			$this->add_relation( $r . '.type = ' . $rel_type_id );
			
			$this->add_field( $t , $field , $alias );
			if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
			{
				$this->add_relation( '(' . $r . '.site=0 OR ' . $r . '.site=' . $this->_env['site'] . ')' );
			}
			if ($limit_results === false)
			{	
				$this->union_fields[end($this->fields)] = '0 as ' . $alias;
				$this->diff['fields'][] = array_diff_assoc($this->fields, $cur_es->fields);
				$this->diff['tables'][] = array_diff_assoc($this->tables, $cur_es->tables);
				$this->diff['relations'][] = array_diff_assoc($this->relations, $cur_es->relations);
			}
			elseif (is_string($limit_results) || is_array($limit_results))
			{
				$limit_values = (is_string($limit_results)) ? array($limit_results) : $limit_results;
				array_walk($limit_values,'db_prep_walk');
				$this->add_relation($t . '.' . $field . ' IN ('.implode(',', $limit_values).')');
			}
			return array( $alias => array( 'table_orig' => $table, 'table' => $t , 'field' => $field ) );
		} // }}}
		/**
		 * Adds a new field to the entity which is actually not a field of entity, but rather a field of 
		 * an entity which is related to the current entities.  The entities selected by the ES will be 
		 * on the right side of the relationship.
		 * Will return multiples of an entity if it has multiples of the same relationship.  Not sure
		 * how to change this.
		 * @param string $rel_name the name of the relationship between the entities
		 * @param string $table the table where the field is
		 * @param string $field the name of the field to be selected
		 * @param string $alias that alias for the field
		 * @param mixed $limit_results true return only row for which the related value is defined
		 * 							   false to return all results even if the value does not exist
		 *							   string or array to limit results to the values passed
		 * @return void
		 */
		function add_right_relationship_field( $rel_name , $table , $field , $alias, $limit_results = true ) // {{{
		//works if entity has one left relationship of give type, otherwise gives multiples
		{
			if ( ($rel_name != "owns") && ($rel_name != "borrows") && !empty($rel_name)) $rel_type_id = relationship_id_of($rel_name);
			elseif ( $rel_name == 'owns' || $rel_name == 'borrows')
			{
				if (empty($this->type))
				{
					$call_info = array_shift( debug_backtrace() );
        			$code_line = $call_info['line'];
        			$file = array_pop( explode('/', $call_info['file']));
        			$msg = 'entity selector method add_right_relationship_field called by ' . $file . ' on line ' . $code_line . ' on a generic "owns" or "borrows" relationship when the type has not been set on the entity selector.';
					trigger_error($msg, WARNING);
					return false;
				}
				elseif ($rel_name == "owns") $rel_type_id = get_owns_relationship_id(reset($this->type));
				elseif ($rel_name == "borrows") $rel_type_id = get_borrows_relationship_id(reset($this->type));
			}
			if (empty($rel_type_id))
			{
				trigger_error('add_right_relationship_field failed - an id could not be determined from the relationship name provided');
				return false;
			}
			if ($limit_results === false)
			{
				$cur_es = carl_clone($this);
				$this->union = true;
			}
			
			$es = new entity_selector();
			
			$es->add_table( 'relationship' , 'relationship' );
			$es->add_table( '__entity__' , 'entity' );
			
			if($table != 'entity' )
				$es->add_table( $table );
			$tables = $this->merge_tables($es);
			
			if( !empty( $tables[ 'relationship' ] ) )
				$r = $tables[ 'relationship' ];
			else 
				$r = 'relationship';

			if( !empty( $tables[ '__entity__' ] ) )
				$e = $tables[ '__entity__' ];
			else
				$e = '__entity__';

			if( $table == 'entity' )
				$t = $e;
			else
			{
				
				if( !empty($tables[ $table ]) )
					$t = $tables[ $table ];
				else
					$t = $table;
			}	
			
			if( $e != $t )
				$this->add_relation( $e . '.id = ' . $t . '.id' );
			
			$this->add_relation( $e . '.id = ' . $r . '.entity_a' );
			$this->add_relation( 'entity.id = ' . $r . '.entity_b' );
			$this->add_relation( $r . '.type = ' . $rel_type_id );

			$this->add_field( $t , $field , $alias );
			if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
			{
				$this->add_relation( '(' . $r . '.site=0 OR ' . $r . '.site=' . addslashes($this->_env['site']) . ')' );
			}
			if ($limit_results === false)
			{	
				$this->union_fields[end($this->fields)] = '0 as ' . $alias;
				$this->diff['fields'][] = array_diff_assoc($this->fields, $cur_es->fields);
				$this->diff['tables'][] = array_diff_assoc($this->tables, $cur_es->tables);
				$this->diff['relations'][] = array_diff_assoc($this->relations, $cur_es->relations);
			}
			elseif (is_string($limit_results) || is_array($limit_results))
			{
				if(is_array($limit_results) && empty($limit_results))
				{
					$this->add_relation('0 = 1');
				}
				else
				{
					$limit_values = (is_string($limit_results)) ? array($limit_results) : $limit_results;
					array_walk($limit_values,'db_prep_walk');
					$this->add_relation($t . '.' . $field . ' IN ('.implode(',', $limit_values).')');
				}
			}
			return array( $alias => array( 'table_orig' => $table, 'table' => $t , 'field' => $field ) );
		} // }}}

		/**
		 * Sets entity tables to exclude from the entity selector 
		 * The entity table cannot be excluded
		 *
		 * @param array exclude_array - array of entity table names to exclude
		 * @return void
		 */		
		function exclude_tables($exclude = '')
		{
			if (is_array($exclude)) $exclude_array = $exclude;
			elseif (!empty($exclude)) $exclude_array[] = $exclude;
			if (empty($exclude_array))
			{
				trigger_error('exclude_tables called but no tables were provided');
			}
			else
			{
				$this->table_mod_action = 'exclude';
			}
			foreach ($exclude_array as $exclude_table)
			{
				if ($exclude_table != 'entity') $this->table_mod[] = $exclude_table;
			}
		}
		/**
		 * Limits to specified tables those selected by the entity selector
		 * The entity table will always be included in an entity selector even if not passed to this function
		 * When called with no parameters the method limits the selector to the entity table
		 *
		 * @param mixed include - array of entity table names to select or a string with one table name
		 * @return void
		 */
		function limit_tables($include = 'entity')
		{
			if (is_array($include)) $include_array = $include;
			else $include_array[] = empty($include) ? 'entity' : $include;
			$this->table_mod_action = 'include';
			foreach ($include_array as $include_table)
			{
				$this->table_mod[] = $include_table;
			}
			if (!in_array('entity', $this->table_mod)) $this->table_mod[] = 'entity';
		}
		/**
		 * Limits entity selector to fields specified - note that the entity id will always be selected
		 * The entity table will always be included in an entity selector even if not passed to this function
		 *
		 * @param mixed fields - array of field names or string with a single field name in format entitytablename.fieldname
		 * @return void
		 */
		function limit_fields($fields = '')
		{
			if (is_array($fields)) $field_array = $fields;
			elseif (!empty($fields)) $field_array[] = $fields;
			else $field_array = '';
			if (is_array($field_array))
			{
				foreach ($field_array as $field)
				{
					if (isset($this->type[0]) && (strpos($field, ".") === FALSE) && ($tablewithfield = table_of($field, $this->type[0])))
					{
						$this->limit_fields[] = $tablewithfield;
					}
					else
					{
						$this->limit_fields[] = $field;
					}
				}
			}
			else $this->limit_fields = array();
		}
		
		function get_merged_query($es_array)
		{
			$union_text = '';
			$str = '';
			while ($es = array_shift($es_array))
			{
				if (count($es_array) > 0) $str .= '('.$es->get_query(true).')';
				else $str .= '('.$es->get_query(true).')';
				if (count($es_array) > 0) $str .= ' UNION ALL ';
			}
			$str .= ( $this->orderby ) ? ' ORDER BY '.$this->orderby."\n" : '';
			if ($this->num > 0) $str .= "LIMIT\n".$this->start.', '.$this->num;
			return $str;
		}
		
		function set_entity_factory(&$factory_class)
		{
			$this->entity_factory_class =& $factory_class;
		}
		
		function &get_entity_factory()
		{
			if (!isset($this->entity_factory_class)) $this->entity_factory_class = false;
			return $this->entity_factory_class;
		}
	} // }}}
?>
