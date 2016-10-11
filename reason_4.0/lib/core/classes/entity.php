<?php
/**
 * Entity Class
 * @package reason
 * @subpackage classes
 */

/**
 * Include the reason libraries
 */
include_once('reason_header.php');

/**
 * Set a global that will be used in the first run of db_query in the absence of an explicitly set connection name
 *
 * This approach replaces an unfortunate direct call to connectDB.
 */
if (defined("REASON_DB")) $GLOBALS['_db_query_first_run_connection_name'] = REASON_DB;
reason_include_once('classes/locks.php');
reason_include_once('config/entity_delegates/config.php');

/**
 * Include database management stuff
 */
include_once( CARL_UTIL_INC . 'db/db.php' );

/**
 * A basic class that abstracts away everything difficult about getting data from an entity
 * 
 * This allows a user to simply specify an id and create a new entity class.  When they want to get 
 * information about the entity, they just call a function and the class will attempt to retrieve the data
 * for them.  This allows the user to not worry about where the data is coming from, a challenge since
 * most entities have their data spread across multiple tables.  For example:
 * <code>
 * $e = new entity( $id );
 * $e->get_value( 'name' );
 * $e->get_value( 'author' );
 * </code>
 * On line 1, the entity will create a new entity object, but will not yet run any database queries.
 *
 * On line 2, the entity will query the DB for all "personal" information about the entity, store it,
 * and return the entity's name.
 *
 * On line 3, the entity does not query, rather it uses information already gathered on line 2.  If the
 * column doesn't exist, it returns false.
 *
 * Delegates
 *
 * Entities can be extended with delegates, which are lazy-loaded as needed. By default 
 * entity methods act as "final" methods -- they cannot be overloaded by delegates.
 * However, methods can be implemented as "non-final" methods by internally calling
 * a delegate if it exists, or falling through to calling delegates in certain circumstances.
 *
 * Non-final methods (which delegates can overload):
 * - get_display_name()
 * - get_url()
 * 
 *
 * @author Brendon Stanton
 * @author Mark Heiman
 * @package reason
 */
class entity
{
	/**#@+
	 * @access private
	 */
	/**
	 * @var int id of entity
	 */
	var $_id;

	/**
	 *
	 * Note that even though this is a private variable, the entity_selector class cheats and fills in the
	 * values itself when run.  This prevents having to requery the DB for the same data.
	 * @var array the values of the actual entity
	 */	
	var $_values = array();
	/**
	 * Relationships where this element appears on
	 * left side of relationship (entity_a)
	 * @var array
	 */
	var $_left_relationships = array(); 
	/**
	 * Relationships where this element appears on
	 * right side of relationship (entity_b)
	 * @var array
	 */
	var $_right_relationships = array();
	/**
	 * Info about relationships where this element appears on
	 * left side of relationship (entity_a)
	 *
	 * This contains the contents of the relationship table rather than the entities
	 * ($_left_relationships contains the entities)
	 *
	 * @var array
	 */
	var $_left_relationships_info = array();
	/**
	 * Info about relationships where this element appears on
	 * left side of relationship (entity_a)
	 *
	 * This contains the contents of the relationship table rather than the entities
	 * ($_right_relationships contains the entities)
	 *
	 * @var array
	 */
	var $_right_relationships_info = array(); 
	/**
	 * Variable to assure caching works properly.  Sometimes an entity will change during the course of 
	 * loading a page and you need to always make sure you get the updated information
	 * setting this to false assures that you will always get the more recent info from the DB
	 * @var boolean
	 */
	 
	protected $_right_relationships_loaded = false;
	protected $_left_relationships_loaded = false;
	
	var $_cache = true;
	/**
	 * Contains the local environment
	 */
	var $_env = array( 'restrict_site' => true );
	/**
	 * The entity locks object
	 *
	 * This class var is lazy-loaded; access it via get_locks().
	 *
	 * @var boolean
	 */
	protected $_locks = false;
	protected $_delegates;
	protected $_delegates_hash;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * Creates a new entity object
	 * @param int $id the id of the entity													
	 * @param boolean $cache set to false if you don't want the entity to cache values
	 */
	function entity( $id, $cache = true ) // {{{
	{
		/* This attempt at input checking needs some additional thought, as there are in-the-wild 
		   instances of entities being instantiated with strings, etc. as just kind of 'stand-in' 
		   objects that don't correspond to items in the Reason DB. */
		/* $id = (integer) $id;
		if(empty($id))
		{
			$bt = debug_backtrace(false);
			$first = current($bt);
			$msg = 'Entity instantiated without valid ID. Called by '.str_replace(array(INCLUDE_PATH,WEB_PATH), '...', $first['file']).' on line '.$first['line'].'.';
			trigger_error($msg);
		} */
		$this->_id = (int) $id;
		$this->_cache = $cache;
	} // }}}
	
	/**
	 * Magic __call method to permit runtime delegation of addtional methods
	 *
	 * @todo Do we want pseudo-namespaces?
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		return $this->call_delegate($name, $arguments);
	}
	public function call_delegate($name, $arguments, $delegate_path = null)
	{
		$delegates = $this->get_delegates();
		if(!$delegate_path)
		{
			foreach($delegates as $delegate)
			{
				if(method_exists($delegate, $name))
					return call_user_func_array(array($delegate,$name),$arguments);
			}
			trigger_error('Method '.$name.' is not supported by any delegates (' . implode(', ',array_keys($delegates)) . ')');
		}
		else
		{
			if(isset($delegates[$delegate_path]))
			{
				if(method_exists($delegates[$delegate_path], $name))
					return call_user_func_array(array($delegates[$delegate_path],$name),$arguments);
				trigger_error('Method '.$name.' is not supported by delegate ' . $delegate_path );
			}
			trigger_error('No delegate found for ' . $delegate_path );
		}

	}
	/**
	 * Does the entity class or one of its delegates support a given method?
	 *
	 * Note that this function is using method_exists, which will return true for private/
	 * protected methods
	 *
	 * @param string $name Name of the method
	 * @return boolean
	 */
	public function method_supported($name)
	{
		if(method_exists($this,$name))
			return true;
		foreach($this->get_delegates() as $delegate)
		{
			if(method_exists($delegate, $name))
				return true;
		}
		return false;
	}
	/**
	 * @todo implement a way to get the superset of methods supported by this entity and its delegates
	 * @return array
	 */
	/* public function get_all_methods()
	{
		trigger_error('entity::get_all_methods() is not yet implemented');
		return array();
	} */
	/**
	 * Check to see if this entity supports or inherits a particular interface
	 *
	 * @param string $name Name of the class or interface
	 * @return boolean
	 * @todo add a cache if we are calling this a lot
	 */
	public function is_or_has($name)
	{
		if(is_a($this,$name))
			return true;
		foreach($this->get_delegates() as $delegate)
		{
			if(is_a($delegate, $name))
				return true;
		}
		return false;
	}
	/**
	 * Grab The Entity's ID
	 *
	 * This is a faster way to get the id than the get_value function which can come in handy since it is
	 * often referenced.
	 * @return int
	 */
	function id() // {{{
	{
		return $this->_id;
	} // }}}
	
	function set_type_id($type_id = NULL)
	{
		$this->_type_id = (integer) $type_id;
	}
	/**
	 * @todo get the entity selector to set the type id by default
	 */
	function type_id()
	{
		if(!isset($this->_type_id))
			$this->_type_id = (integer) $this->get_value('type');
		return $this->_type_id;
	}
	/**
	 * Sets a local environment variable.
	 *
	 * This can be used to help with selections on stuff like selecting relationship sites.
	 * @param string $field name of the field
	 * @param mixed $value value of the field
	 */
		function set_env( $field , $value ) //{{{
		{
			$this->_env[$field] = $value;
		} // }}}
	/**
	 * @todo figure out how to pass along type when known to reduce item-by-item DB fetches
	 */
	function get_delegates()
	{
		$config = get_entity_delegates_config();
		if(!isset($this->_delegates))
		{
			$this->_delegates = $config->get_delegates($this);
			$this->_delegates_hash = $config->type_hash($this->type_id());
		}
		elseif($config->type_hash($this->type_id()) != $this->_delegates_hash)
		{
			$new_delegates = $config->get_delegates($this, NULL, true);
			$append = array();
			foreach($this->_delegates as $k=>$d)
			{
				if(isset($new_delegates[$k]))
					$new_delegates[$k] = $d;
				else
					$append[$k] = $d;
			}
			$this->_delegates = array_merge($new_delegates,$append);
		}
		return $this->_delegates;
	}
	function add_delegate($path, $delegate)
	{
		$this->get_delegates();
		$this->_delegates[$path] = $delegate;
	}
	/**
	 * Function that actually gets the values from the DB
	 * @access private
	 * @return array
	 */
	function _get_values() // {{{
	{
		return get_entity_by_id( $this->_id, $this->_cache );
	} // }}}
	/**
	 * Checks to see if the values need to be grabbed and does it, then returns them
	 * @return array
	 */
	function get_values($fetch = true) // {{{
	{
		if( !$this->_values && $fetch )
			$this->_values =  $this->_get_values();
		return $this->_values;
	} // }}}
	function refresh_values($use_cache = true)
	{
		$this->_values = $this->_values + get_entity_by_id( $this->_id, $use_cache );
	}
	/**
	 * Returns the available fields for the entity
	 * @return array
	 */
	function get_characteristics() // {{{
	{
		if( !$this->_values )
			$this->_values = get_entity_by_id( $this->_id );
		$c = array();
		reset( $this->_values );
		while( list( $key , ) = each( $this->_values ) )
			$c[] = $key;
		return $c;
	} // }}}
	/**
	 * returns a specific value for the entity or false if the field doesn't exist
	 * @param string $col name of the field to grab
	 * @return mixed
	 */
	function get_value( $col, $refresh = true ) // {{{
	{
		if( empty( $this->_values ) )
			$this->_values = get_entity_by_id( $this->_id );
		if( !empty( $this->_values[ $col ]) OR (isset($this->_values[$col]) AND strlen($this->_values[ $col ]) > 0) )
			return $this->_values[ $col ];
		elseif(!array_key_exists($col, $this->_values))
		{
			if ($refresh)
			{			
				return $this->get_value_refresh ($col);
			}
			else 
			{
				$val = $this->get_value_from_delegate( $col );
				if(null !== $val)
					return $val;
				else
					trigger_error('"'.$col.'" field not retrieved from database');
			}
		}
		return false;
	} // }}}
	
	/**
	 * returns a specific value for the entity or false if the field doesn't exist
	 * @param string $col name of the field to grab
	 * @return mixed
	 */
	function get_value_refresh( $col ) // {{{
	{
		$this->refresh_values();
		if( empty( $this->_values ) )
			$this->_values = get_entity_by_id( $this->_id );
		if( !empty( $this->_values[ $col ]) OR (isset($this->_values[$col]) AND strlen($this->_values[ $col ]) > 0) )
			return $this->_values[ $col ];
		elseif(!array_key_exists($col, $this->_values))
		{
			$val = $this->get_value_from_delegate( $col );
			if(null !== $val)
				return $val;
			else
				trigger_error('"'.$col.'" field not retrieved from database');
			// echo '<pre>';debug_print_backtrace();echo '</pre>';
		}
		return false;
	} // }}}
	
	/**
	 * Get a value from delegate
	 * @param string $col the column/key requested
	 * @param mixed $delegate_path the delegate path or null for first delegate that
	 * supports this value
	 * @return mixed NULL if no delegate supports, mixed non-null value otherwise
	 */
	function get_value_from_delegate( $col, $delegate_path = null )
	{
		$delegates = $this->get_delegates();
		if(!$delegate_path)
		{
			foreach($delegates as $delegate)
			{
				$val = $delegate->get_value( $col );
				if(NULL !== $val)
					return $val;
			}
		}
		elseif(isset($delegates[$delegate_path]))
		{
			return $delegate->get_value( $col );
		}
		return null;
	}
	
	function set_value($col, $val)
	{
		$this->_values[ $col ] = $val;
	}
	
	/**
	 * Returns true if the field exists on the entity and is not NULL.
	 *
	 * NOTE - this will return false if a field exists on the entity but has the value NULL
	 */
	function has_value($col)
	{
		$values = $this->get_values();
		return (isset($values[ $col ]));
	}
	function unset_value($col)
	{
		unset($this->_values[ $col ]);
	}
	/**
	 * Gets the display name of the entity
	 *
	 * This isn't an actual attribute of the entity, rather it is a function of the entity's type.
	 * Gets the display_name_handler an then calls the function on the current object
	 *
	 * @todo remove entire display name block in 4.8
	 *
	 * @return string display name of the object
	 */
	function get_display_name()
	{
		if($name = @$this->call_delegate('get_display_name', array()))
		{
			return $name;
		}
		
		$type_id = $this->type_id();
		if(!empty($type_id))
		{
			$type = new entity( $type_id );
			if( $type->get_value( 'display_name_handler' ) )
			{
				$file = 'display_name_handlers/' . $type->get_value( 'display_name_handler' );
				if(reason_include_once( $file ))
				{
					$display_handler = $GLOBALS['display_name_handlers'][$type->get_value( 'display_name_handler' )];
					if(empty($display_handler))
						trigger_error('Custom display name handler not registered in '.$file);
					elseif(!function_exists($display_handler))
						trigger_error('Custom display name handler not registered properly in '.$file);
					else
					{
						trigger_error('Display name handlers are deprecated. Please migrate your display name generation to an entity delegate for the type "'.$type->get_value( 'unique_name' ).'"');
						return $display_handler( $this );
					}
				}
				else
				{
					trigger_error('Unable to use custom display name handler -- no file at ('.$file.')' );
				}	
			}
		}
		else
		{
			trigger_error('Item id '.$this->id().' does not have an entry in Type field. Potential database corruption.');
		}
		
		return $this->get_value( 'name' );
	}

	//////////////////////////////////////////////////////
	//  
	//   Relationship functions:
	//
	// Three types of relationships: Left, Right, and both
	// usually we want to check to see if something is 
	// in one side, but we might want both.
	//
	// The entity class as originally written stored all
	// relationship table rows for related entities, indexed
	// both by relationship id and name, as well as instantiating
	// entities for all those relationships, also double indexed.
	// Performance has been significantly improved by grabbing 
	// relationship table rows only as needed, indexing them 
	// only by relationship id, and instantiating entities 
	// from that data only as requested. Historic methods
	// continue to return the same values as before (double 
	// indexed) but that data is generated dynamically from
	// the smaller set actually stored on the entity.
	//
	//////////////////////////////////////////////////////

	/**
	 * Initialize the _left_relationships and _left_relationships_info arrays
	 * @access private
	 * @todo cache db structure info so it doesn't need to rediscover it every time
	 */
	function _init_left_relationships() // {{{
	{
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_field( 'r','*' );
		$dbq->add_table( 'entity' , 'entity' );
		$dbq->add_relation( 'entity.state = "Live"' );
		$dbq->add_relation( 'entity.id = r.entity_b' );
		$dbq->add_relation( 'r.entity_a = '.$this->id() );
		//$dbq->add_relation( 'r.type != 0' ); // There are some bad rels out there with type=0
		if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
		{
			$dbq->add_relation( '(r.site=0 OR r.site=' . (integer) $this->_env['site'] . ')' );
		}
		$rels = $dbq->run( 'Unable to grab relationships' );
		foreach( $rels as $r)
		{
			$this->_left_relationships_info[ $r['type'] ][] = $r;
		}
		
		$this->_left_relationships_loaded = true;
	} // }}}
	/**
	 * Initialize the _right_relationships and _right_relationships_info arrays
	 * @access private
	 * @todo cache db structure info so it doesn't need to rediscover it every time
	 */
	function _init_right_relationships() // {{{
	{
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_field( 'r','*' );
		$dbq->add_table( 'entity' , 'entity' );
		$dbq->add_relation( 'entity.state = "Live"' );
		$dbq->add_relation( 'entity.id = r.entity_a' );
		$dbq->add_relation( 'r.entity_b = '.$this->id() );
		if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
		{
			$dbq->add_relation( '(r.site=0 OR r.site=' . (integer) $this->_env['site'] . ')' );
		}
		$dbq->set_order( 'rel_sort_order' );
		$rels = $dbq->run();
		foreach( $rels AS $r )
		{
			$this->_right_relationships_info[ $r['type'] ][] = $r;
		}
		
		$this->_right_relationships_loaded = true;
	} // }}}
	
	/**
	 * This function returns an array of related entities (if passed as relationship type)
	 * or all the related entities keyed on relationship type (if no type is passed). It
	 * populates the *_relationships class var as needed, so entities aren't instantiated
	 * more than once.
	 *
	 * @param string $side 'left' or 'right'
	 * @param mixed $type optional; either the name or id of an allowable relationship
	 * @return array
	 */
	protected function _get_related_entities($side, $type = null)
	{
		$info = '_' . $side . '_relationships_info';
		$entities = '_' . $side . '_relationships';
		$column = ($side == 'right') ? 'entity_a' : 'entity_b';
		
		if ($type)
		{
			list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
		
			// If already instantiated, don't try again.
			if (array_key_exists($rel_id, $this->$entities)) return $this->{$entities}[$rel_id];
		
			// If we have rows in the info array, make matching ones in the entities array
			if (array_key_exists($rel_id, $this->$info))
			{
				$this->{$entities}[$rel_id] = array();
				foreach ($this->{$info}[$rel_id] as $key => $row)
				{
					$this->{$entities}[$rel_id][$key] = new entity( $row[$column] );
				}
				return $this->{$entities}[$rel_id];
			}
		}
		else
		{
			// If we have rows in the info array, make matching ones in the entities array
			foreach ($this->$info as $type => $values)
			{
				// (if that rel type hasn't already been populated)
				if (!array_key_exists($type, $this->$entities))
				{
					foreach ($values as $key => $row)
					{
						$this->{$entities}[$type][$key] = new entity( $row[$column] );
					}
				}	
			}
			return $this->$entities;
		}
		return array();
	}
	
	/**
	 * returns true if entity has a left relationship of the given type
	 * @param mixed $e either the name or id of an allowable relationship
	 * @return boolean
	 */
	function has_left_relation_of_type( $e ) // {{{
	{
		$rels = $this->get_left_relationships_info($e);
		return !empty($rels[$e]);
	} // }}}
	/**
	 * returns true if entity has a right relationship of the given type
	 * @param mixed $e either the name or id of an allowable relationship
	 * @return boolean
	 */
	function has_right_relation_of_type( $e ) // {{{
	{
		$rels = $this->get_right_relationships_info($e);
		return !empty($rels[$e]);
	} // }}}

	/**
	 * returns true if entity has a left relationship with the given entity 
	 * @param entity $e the entity we are checking
	 * @param mixed $type an optional relationship type which is either an id or name of an AR
	 * @return boolean
	 */
	function has_left_relation_with_entity( $e , $type = false) // {{{
	{
		if(!is_object($e))
		{
			$id = (integer) $e;
		}
		elseif(!method_exists($e, 'id'))
		{
			trigger_error('The first parameter of has_left_relation_with_entity() must be a Reason entity or id');
			return;
		}
		else
		{
			$id = $e->id();
		}
		
		if ($type) list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
		
		// If we know the rel type, we can just look at that subset of relationship data
		if (!empty($rel_id))
		{
			$rows = $this->get_left_relationships_info($rel_id);
			foreach ($rows[$rel_id] as $row)
			{
				if ( $id == $row['entity_b'] )
					return true;
			}
			return false;
		}
		
		// If we don't know the rel type, we have to load all the relationships (ouch)
		if( !$this->_left_relationships_loaded )
			$this->_init_left_relationships();
		foreach( $this->_left_relationships_info AS $rel_id => $rows )
		{
			foreach( $rows AS $row )
			{
				if ( $id == $row['entity_b'] )
					return true;
			}
		}
		return false;
	} // }}}
	/**
	 * returns true if entity has a right relationship with the given entity 
	 * @param entity $e the entity we are checking
	 * @param mixed $type an optional relationship type which is either an id or name of an AR
	 * @return boolean
	 */
	function has_right_relation_with_entity( $e , $type = false) // {{{
	{
		if(!is_object($e))
		{
			$id = (integer) $e;
		}
		elseif(!method_exists($e, 'id'))
		{
			trigger_error('The first parameter of has_right_relation_with_entity() must be a Reason entity or id');
			return;
		}
		else
		{
			$id = $e->id();
		}
		
		if ($type) list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
		
		// If we know the rel type, we can just look at that subset of relationship data
		if (!empty($rel_id))
		{
			$rows = $this->get_right_relationships_info($rel_id);
			foreach ($rows[$rel_id] as $row)
			{
				if ( $id == $row['entity_a'] )
					return true;
			}
			return false;
		}
		
		// If we don't know the rel type, we have to load all the relationships (ouch)
		if( !$this->_right_relationships_loaded )
			$this->_init_right_relationships();
		foreach( $this->_right_relationships_info AS $rel_id => $rows )
		{
			foreach( $rows AS $row )
			{
				if ( $id == $row['entity_a'] )
					return true;
			}
		}
		return false;
	} // }}}

	/** 
	 * Gets all left relationships of the entity
	 * @return array all left relationships of the entity
	 */
	function get_left_relationships() // {{{
	{
		if( !$this->_left_relationships_loaded )
			$this->_init_left_relationships();
		return $this->_sweeten_relationship_data($this->_get_related_entities('left'));
	} // }}}
	/** 
	 * Gets all right relationships of the entity
	 * @return array all left relationships of the entity
	 */
	function get_right_relationships() // {{{
	{
		if( !$this->_right_relationships_loaded )
			$this->_init_right_relationships();
		return $this->_sweeten_relationship_data($this->_get_related_entities('right'));
	} // }}}
	/** 
	 * Gets the left relationships of a given name for an object
	 * @param mixed $rel_name name or id of an AR
	 */	
	function get_left_relationship( $rel_name ) // {{{
	{
		if (!array_key_exists( $rel_name, $this->_left_relationships_info))
			$this->_get_left_relationship_query( $rel_name );
		return $this->_get_related_entities('left', $rel_name);
	} // }}}
	/** 
	 * Gets the right relationships of a given name for an object
	 * @param mixed $rel_name name or id of an AR
	 */	
	function get_right_relationship( $rel_name )  // {{{
	{
		if (!array_key_exists( $rel_name, $this->_right_relationships_info))
			$this->_get_right_relationship_query( $rel_name );
		return $this->_get_related_entities('right', $rel_name);
	} // }}}

	/** 
	 * Gets the right relationships of a given name for an object, 
	 * using a single query rather than the full relationship set.
	 * @param mixed $rel_name name or id of an AR
	 */	
	private function _get_right_relationship_query( $type )  // {{{
	{
		list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
		if ($rel_id == 0) return array();
			
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_field( 'r','*' );
		$dbq->add_table( 'entity' , 'entity' );
		$dbq->add_relation( 'entity.state = "Live"' );
		$dbq->add_relation( 'entity.id = r.entity_a' );
		$dbq->add_relation( 'r.entity_b = '.$this->id() );
		$dbq->add_relation( 'r.type = '.$rel_id );
		if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
		{
			$dbq->add_relation( '(r.site=0 OR r.site=' . (integer) $this->_env['site'] . ')' );
		}
		$dbq->set_order( 'rel_sort_order' );
		$rels = $dbq->run();
		foreach( $rels AS $r )
		{
			$this->_right_relationships_info[ $rel_id ][] = $r;
		}
		
		// If we did the query but didn't get any results, we save an empty array
		// at that key location to indicate that we've done the query and 
		// shouldn't do it again.
		if (!isset($this->_right_relationships_info[$rel_id]))
		{
			$this->_right_relationships_info[ $rel_id ] = array();
		}
		return $this->_right_relationships_info[ $rel_id ];
		
	} // }}}

	/** 
	 * Gets the left relationships of a given name for an object, 
	 * using a single query rather than the full relationship set.
	 * @param mixed $rel_name name or id of an AR
	 */	
	private function _get_left_relationship_query( $type )  // {{{
	{
		list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
		if ($rel_id == 0) return array();
			
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_field( 'r','*' );
		$dbq->add_table( 'entity' , 'entity' );
		$dbq->add_relation( 'entity.state = "Live"' );
		$dbq->add_relation( 'entity.id = r.entity_b' );
		$dbq->add_relation( 'r.entity_a = '.$this->id() );
		$dbq->add_relation( 'r.type = '.$rel_id );
		if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
		{
			$dbq->add_relation( '(r.site=0 OR r.site=' . (integer) $this->_env['site'] . ')' );
		}
		$dbq->set_order( 'rel_sort_order' );
		$rels = $dbq->run();
		
		foreach( $rels AS $r )
		{
			$this->_left_relationships_info[ $rel_id ][] = $r;
		}
		
		// If we did the query but didn't get any results, we save an empty array
		// at that key location to indicate that we've done the query and 
		// shouldn't do it again.
		if (!isset($this->_left_relationships_info[$rel_id]))
		{
			$this->_left_relationships_info[ $rel_id ] = array();
		}
		
		return $this->_left_relationships_info[ $rel_id ];
		
	} // }}}	
	/**
	 * Generic function which returns true if the entity is on either side of a relationship
	 * @param mixed $e name or ID of an AR
	 * @return boolean
	 */
	function has_relation_of_type( $e ) // {{{
	{
		if( $this->has_left_relation_of_type( $e ) OR $this->has_right_relation_of_type( $e ) )
			return true;
		else return false;
	} // }}}
	/**
	 * Generic function which returns true if the entity has a left or right relationship with an entity
	 * @param entity $e the entity we are checking
	 * @param mixed $type an optional relationship type which is either an id or name of an AR
	 * @return boolean
	 */
	function has_relation_with_entity( $e , $type = false ) // {{{
	{
		if($this->has_left_relation_with_entity( $e, $type ) || 
			$this->has_right_relation_with_entity( $e , $type ) )
			return true;
		else return false;
	} // }}}
	/**
	 * Gets all relationships (left and right) of the entity
	 * @return array
	 */
	function get_relationships() // {{{
	{
		return $this->get_left_relationships() + $this->get_right_relationships();
	} // }}}
	/**
	 * Gets a particular relationship (left or right) of the entity
	 * @param mixed $rel_name name of an AR
	 * @return array
	 */
	function get_relationship( $rel_name ) // {{{
	{
		if ($rel = $this->get_left_relationship($rel_name))
			return $rel;
		else if ($rel = $this->get_right_relationship($rel_name))
			return $rel;
		else return null;
	} // }}}
	
	/** 
	 * Get info about the left relationships of the entity
	 * 
	 * Note that this returns the values of the relationship table, not the entities themselves
	 *
	 * @param mixed $type optional name or ID of an AR
	 * @return array
	 */
	function get_left_relationships_info($type = null) // {{{
	{
		if (!$type)
		{
			if( !$this->_left_relationships_loaded )
				$this->_init_left_relationships();
			return $this->_sweeten_relationship_data($this->_left_relationships_info);
		}
		else
		{
			list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
			if (array_key_exists($rel_id, $this->_left_relationships_info))
				return $this->_sweeten_relationship_data(array($rel_id => $this->_left_relationships_info[$rel_id]));
			else
				return $this->_sweeten_relationship_data(array($rel_id => $this->_get_left_relationship_query($rel_id)));
		}	
	} // }}}
	/** 
	 * Get info about the right relationships of the entity
	 *
	 * Note that this returns the values of the relationship table, not the entities themselves
	 *
	 * @return array
	 */
	function get_right_relationships_info($type = null) // {{{
	{
		if (!$type)
		{
			if( !$this->_right_relationships_loaded )
				$this->_init_right_relationships();
			return $this->_sweeten_relationship_data($this->_right_relationships_info);
		}
		else
		{
			list($rel_id, $rel_name) = $this->_normalize_rel_key($type);
			if (array_key_exists($rel_id, $this->_right_relationships_info))
				return $this->_sweeten_relationship_data(array($rel_id => $this->_right_relationships_info[$rel_id]));
			else
				return $this->_sweeten_relationship_data(array($rel_id => $this->_get_right_relationship_query($rel_id)));
		}	
	} // }}}

	/**
	 * Relationship data is stored keyed on relationship ID. In previous versions
	 * of the entity class, this data was also keyed on relationship name, doubling
	 * the data stored. This method takes a relationship array (either *_relationships
	 * or *_relationships_info) and populates the relationship name keys, providing a 
	 * way for methods to continue returning the same data structures as before.
	 *
	 * @param array $data
	 */
	private function _sweeten_relationship_data($data)
	{
		foreach($data as $rel_id => $values)
		{
			if ($rel_name = relationship_name_of($rel_id))
			{
				if (!array_key_exists($rel_name, $data))
					$data[$rel_name] = $values;
			}
		}
		return $data;
	}

	/**
	 * Given a relationship ID or name, this function returns an array of the form
	 * array(id, name) so that you always know what you've got.
	 * @param mixed $key name or ID of an AR
	 * @return array
	 */
	private function _normalize_rel_key($key)
	{
		if (is_numeric($key))
		{
			return array((int)$key, relationship_name_of($key));
		}
		else if ($rel_id = relationship_id_of($key))
		{
			return array((int)$rel_id, $key);
		}
		else
		{
			return array(0, $key);
		}
	}

	/**
	 * Returns an entity of the site that owns this entity
	 * @return entity
	 */
	function get_owner() // {{{
	{
		$right_rels = $this->get_right_relationship( get_owns_relationship_id( $this->type_id() ) );
		if( !empty( $right_rels[ 0 ] ) )
			return $right_rels[ 0 ];
		else
			return 0;
	} // }}}

	/**
	 * Resets the left right relationship and relationship_info arrays. Can reclaim a significant amount
	 * of memory.
	 */
	function clearRelationshipArrays() {
		$this->_left_relationships = Array();
		$this->_left_relationships_info = Array();
		$this->_right_relationships = Array();
		$this->_right_relationships_info = Array();
	}
	/**
	 * Returns true if entity is owned or borrowed by site in first argument
	 * @param integer $site_id
	 * @return bool
	 */
	function owned_or_borrowed_by($site_id) // {{{
	{
		$site = new entity($site_id);
		$owner = $this->get_owner();
		if( $owner->id() == $site->id() || $this->has_right_relation_with_entity( $site, get_borrows_relationship_id( $this->type_id() ) ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	} // }}}
	
	
	/*
	  Locks Section
	 */
	
	/**
	 * Get the locks object
	 * 
	 * @return object
	 */
	function get_locks()
	{
		if(!is_object($this->_locks))
		{
			$this->_locks = new ReasonEntityLocks($this);
		}
		return $this->_locks;
	}
	
	/**
	 * Does the entity have any locks?
	 *
	 * return boolean
	 */
	function has_lock()
	{
		$locks = $this->get_locks();
		return $locks->has_lock();
	}
	
	/**
	 * Does the entity have an "all fields" lock?
	 *
	 * @return boolean
	 */
	function has_all_fields_lock()
	{
		$locks = $this->get_locks();
		return $locks->has_all_fields_lock();
	}
	
	/**
	 * Does the entity have an "all relationships" lock for the given direction?
	 *
	 * @param string $direction 'left' or 'right'
	 * @return boolean
	 */
	function has_all_relationships_lock($direction)
	{
		$locks = $this->get_locks();
		return $locks->has_all_relationships_lock($direction);
	}
	
	/**
	 * Does the given field have a lock?
	 *
	 * Note that this will return true if the field is specifically locked
	 * or if there is an "all fields lock" on the entity.
	 *
	 * @param string $field_name
	 * @return boolean
	 */
	function field_has_lock($field_name)
	{
		$locks = $this->get_locks();
		return $locks->field_has_lock($field_name);
	}
	
	/**
	 * Does the given relationship have a lock in the direction specified?
	 *
	 * Note that this will return true if the relationship/direction is specifically locked
	 * or if there is an "all relationships lock" on that direction.
	 *
	 * @param mixed $relationship
	 * @param string $direction 'left' or 'right'
	 * @return boolean
	 */
	function relationship_has_lock($relationship, $direction)
	{
		$locks = $this->get_locks();
		return $locks->relationship_has_lock($relationship, $direction);
	}
	
	/**
	 * Could an unidentified user of the given role edit this entity?
	 *
	 * Note that without a given user, this function cannot check
	 * site membership or other important aspects of privilege-granting.
	 * Therefore, this method should only be used for informational
	 * purposes, not to grant privileges, unless other checks are done.
	 *
	 * @param string $role_name
	 * @param string $fields_or_rels 'fields','relationships', or 'all'
	 * @return boolean
	 */
	function role_could_edit($role_name, $fields_or_rels = 'all')
	{
		$locks = $this->get_locks();
		return $locks->role_could_edit($role_name, $fields_or_rels);
	}
	
	/**
	 * Could the given role edit the given field on this entity?
	 *
	 * @param string $field_name Name of field
	 * @param string $role_name Unique name of role
	 * @return boolean
	 * @access public
	 */
	function role_could_edit_field($field_name, $role_name)
	{
		$locks = $this->get_locks();
		return $locks->role_could_edit_field($field_name, $role_name);
	}
	
	/**
	 * Could the given role edit the given relationship on this entity?
	 *
	 * @param integer $relationship ID of allowable relationship
	 * @param string $role_name Unique name of role
	 * @param string $direction 'left' or 'right'
	 * @return boolean
	 * @access public
	 */
	function role_could_edit_relationship($relationship, $role_name, $direction)
	{
		$locks = $this->get_locks();
		return $locks->role_could_edit_relationship($relationship, $role_name, $direction);
	}
	
	/**
	 * Can a given user edit at least one field or relationship of this entity?
	 *
	 * @param mixed $user user entity or null for currently logged-in user
	 * @return boolean
	 */
	function user_can_edit($user = null, $fields_or_rels = 'all')
	{
		$locks = $this->get_locks();
		return $locks->user_can_edit( $user, $fields_or_rels );
	}
	
	/**
	 * Can a given user edit a given field on this entity?
	 *
	 * @param string $field_name
	 * @param mixed $user A user entity or null for the currently-logged-in user
	 * @return boolean
	 */
	function user_can_edit_field($field_name, $user = null)
	{
		$locks = $this->get_locks();
		return $locks->user_can_edit_field( $field_name, $user );
	}
	
	/**
	 * Can a given user edit a given relationship on thie entity?
	 *
	 * @param string $field_name
	 * @param mixed $user A user entity or null for the currently-logged-in user
	 * @param string $direction 'left' or 'right' -- 'left' if this entity is on the right side of the relationship, 'right' if it is on the left (e.g. on which side of the entity is the relationship on?)
	 * @return boolean
	 */
	function user_can_edit_relationship($relationship, $user = null, $direction, $entity_on_other_side =  null, $context_site = null)
	{
		$locks = $this->get_locks();
		return $locks->user_can_edit_relationship($relationship, $user, $direction, $entity_on_other_side, $context_site);
	}
	
	
	/**
	 * Get the URL of the item
	 *
	 * return mixed string or NULL
	 */
	public function get_url($type = '')
	{
		return @$this->call_delegate('get_url', array($type));
	}
	/**
	 * Get the URL to edit this item
	 *
	 * @param $return_url A url to return to after editing
	 * @return mixed URL or null if no owner site found
	 */
	public function get_edit_url($return_url = null)
	{
		$site = $this->get_owner();
		if($site)
		{
			$qs = carl_construct_query_string(array('site_id' => $site->id(), 'type_id' => $this->type_id(), 'id' => $this->id(), 'cur_module' => 'Editor', 'fromweb' => $return_url));
			return securest_available_protocol() . '://' . REASON_WEB_ADMIN_PATH . $qs;
		}
		return NULL;
	}
}