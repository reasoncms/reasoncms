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
 * @author Brendon Stanton
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
		$this->_id = $id;
		$this->_cache = $cache;
	} // }}}
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
	/**
	 * Sets a local enviornment variable.
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
	function get_values() // {{{
	{
		if( !$this->_values )
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
			trigger_error('"'.$col.'" field not retrieved from database');
		}
		return false;
	} // }}}
	
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
	 * @return string display name of the object
	 */
	function get_display_name() // {{{
	{
		$type_id = $this->get_value( 'type' );
		if(!empty($type_id))
		{
			$type = new entity( $type_id );
			if( $type->get_value( 'display_name_handler' ) )
			{
				$file = 'display_name_handlers/' . $type->get_value( 'display_name_handler' );
				reason_include_once( $file );
				$display_handler = $GLOBALS['display_name_handlers'][$type->get_value( 'display_name_handler' )];
				return $display_handler( $this );
			}
		}
		else
		{
			trigger_error('Item id '.$this->id().' does not have an entry in Type field. Potential database corruption.');
		}
		return $this->get_value( 'name' );
	} // }}}

	//////////////////////////////////////////////////////
	//  
	//   Relationship functions:
	//
	// Three types of relationships: Left, Right, and both
	// usually we want to check to see if something is 
	// in one side, but we might want both.
	//
	// Relationships can be indexed by number or name, but
	// if there are two different relationship types (not
	// two relationships, but two seperate types), using 
	// the name index will only grab one of them.  To be 
	// sure in this case, use an index and not a name.
	//
	//////////////////////////////////////////////////////

	/**
	 * Initialize the _left_relationships and _left_relationships_info arrays
	 * @access private
	 * @todo cache db structure info so it doesn't need to rediscover it every time
	 */
	function _init_left_relationships() // {{{
	{
		//first, get relationship types
		$dbq = new DBSelector;
		$dbq->add_field( 'allow' , '*' );

		$dbq->add_table( 'allow' , 'allowable_relationship' );
		$dbq->add_table( 'entity' , 'entity' );

		$dbq->add_relation( 'entity.id = ' . $this->_id );
		$dbq->add_relation( 'entity.type = allow.relationship_a' );
		// see note on this line down  in right relationship land
		//$dbq->add_relation( 'entity.state = "Live"' );

		$r1 = db_query( $dbq->get_query() , 'Entity Error:  Could not get relationships' );
		
		$rel_name = array();
		while( $row = mysql_fetch_array( $r1 , MYSQL_ASSOC ) )
		{
			$this->_left_relationships[ $row['id'] ] = array();
			$this->_left_relationships[ $row['name'] ] = array();
			$this->_left_relationships_info[ $row['id'] ] = array();
			$this->_left_relationships_info[ $row['name'] ] = array();
			$rel_name[ $row['id'] ] = $row['name'];
		}
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
			$dbq->add_relation( '(r.site=0 OR r.site=' . $this->_env['site'] . ')' );
		}
		$rels = $dbq->run( 'Unable to grab relationships' );
		reset( $rels );
		while( list( ,$r ) = each( $rels ) )
		{
			$e = new entity( $r['entity_b'] );
			$this->_left_relationships[ $r['type'] ][] = $e;
			$this->_left_relationships_info[ $r['type'] ][] = $r;
			$this->_left_relationships[ $rel_name[ $r['type'] ] ][] = $e;
			$this->_left_relationships_info[ $rel_name[ $r['type'] ] ][] = $r;
		}
	} // }}}
	/**
	 * Initialize the _right_relationships and _right_relationships_info arrays
	 * @access private
	 * @todo cache db structure info so it doesn't need to rediscover it every time
	 */
	function _init_right_relationships() // {{{
	{
		//first, get relationship types
		$dbq = new DBSelector;
		$dbq->add_field( 'allow' , '*' );

		$dbq->add_table( 'allow' , 'allowable_relationship' );
		$dbq->add_table( 'entity' , 'entity' );

		$dbq->add_relation( 'entity.id = ' . $this->_id );
		$dbq->add_relation( 'entity.type = allow.relationship_b' );
		// we think this should be commented out.  it makes an error not appear.  you probably want more than that.  i don't have it.
		//$dbq->add_relation( 'entity.state = "Live"' );

		$r1 = db_query( $dbq->get_query() , 'Entity Error:  Could not get relationships' );
		$rel_name = array();
		while( $row = mysql_fetch_array( $r1 , MYSQL_ASSOC ) )
		{
			$this->_right_relationships[ $row['id'] ] = array();
			$this->_right_relationships[ $row['name'] ] = array();
			$this->_right_relationships_info[ $row['id'] ] = array();
			$this->_right_relationships_info[ $row['name'] ] = array();
			$rel_name[ $row['id'] ] = $row['name'];
		}
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_field( 'r','*' );
		$dbq->add_table( 'entity' , 'entity' );
		$dbq->add_relation( 'entity.state = "Live"' );
		$dbq->add_relation( 'entity.id = r.entity_a' );
		$dbq->add_relation( 'r.entity_b = '.$this->id() );
		if( $this->_env['restrict_site'] AND !empty($this->_env['site']) )
		{
			$dbq->add_relation( '(r.site=0 OR r.site=' . $this->_env['site'] . ')' );
		}
		$dbq->set_order( 'rel_sort_order' );
		$rels = $dbq->run();
		foreach( $rels AS $r )
		{
			$e = new entity( $r['entity_a'] );
			$this->_right_relationships[ $r['type'] ][] = $e;
			$this->_right_relationships_info[ $r['type'] ][] = $r;
			if(!empty($rel_name[ $r['type'] ]))
			{
				$this->_right_relationships[ $rel_name[ $r['type'] ] ][] = $e;
				$this->_right_relationships_info[ $rel_name[ $r['type'] ] ][] = $r;
			}
		}
	} // }}}
	
	/**
	 * returns true if entity has a left relationship of the given type
	 * @param mixed $e either the name or id of an allowable relationship
	 * @return boolean
	 */
	function has_left_relation_of_type( $e ) // {{{
	{
		if( empty($this->_left_relationships) )
			$this->_init_left_relationships();
		if( !empty($this->_left_relationships[ $e ]) )
			return true;
		else return false;
	} // }}}
	/**
	 * returns true if entity has a right relationship of the given type
	 * @param mixed $e either the name or id of an allowable relationship
	 * @return boolean
	 */
	function has_right_relation_of_type( $e ) // {{{
	{
		if( empty($this->_right_relationships) )			
			$this->_init_right_relationships();
		if( !empty($this->_right_relationships[ $e ]) )
			return true;
		else return false;
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
		if( empty( $this->_left_relationships ) )
			$this->_init_left_relationships();
		foreach( $this->_left_relationships AS $name => $relate )
		{
			if( empty( $type ) OR $name == $type)
			{
				foreach( $relate AS $item )
				{
					if($id == $item->id() )
						return true;
				}
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
		if( empty( $this->_right_relationships ) )
			$this->_init_right_relationships();
		foreach( $this->_right_relationships AS $name => $relate )
		{
			if( empty( $type ) OR $name == $type)
			{
				foreach( $relate AS $item )
				{
					if($id == $item->id() )
						return true;
				}
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
		if( !$this->_left_relationships )
			$this->_init_left_relationships();
		return $this->_left_relationships;
	} // }}}
	/** 
	 * Gets all right relationships of the entity
	 * @return array all left relationships of the entity
	 */
	function get_right_relationships() // {{{
	{
		if( !$this->_right_relationships )
			$this->_init_right_relationships();
		return $this->_right_relationships;
	} // }}}
	/** 
	 * Gets the left relationships of a given name for an object
	 * @param mixed $rel_name name or id of an AR
	 */	
	function get_left_relationship( $rel_name ) // {{{
	{
		if( !$this->_left_relationships )
			$this->_init_left_relationships();
		return empty($this->_left_relationships[ $rel_name ]) ? array() : $this->_left_relationships[ $rel_name ];
	} // }}}
	/** 
	 * Gets the right relationships of a given name for an object
	 * @param mixed $rel_name name or id of an AR
	 */	
	function get_right_relationship( $rel_name )  // {{{
	{
		if( !$this->_right_relationships )
			$this->_init_right_relationships();
		return empty($this->_right_relationships[ $rel_name ]) ? array() : $this->_right_relationships[ $rel_name ];
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
	 * Gets all relationships (left and right) of the entity
	 * @param mixed $rel_name name or ID of an AR
	 * @return array
	 */
	function get_relationship( $rel_name ) // {{{
	{
		$all = $this->get_relationships();
		return $all[ $rel_name ];
	} // }}}
	
	/** 
	 * Get info about the left relationships of the entity
	 * 
	 * Note that this returns the values of the relationship table, not the entities themselves
	 *
	 * @return array
	 */
	function get_left_relationships_info() // {{{
	{
		if( !$this->_left_relationships_info )
			$this->_init_left_relationships();
		return $this->_left_relationships_info;
	} // }}}
	/** 
	 * Getinfo about the right relationships of the entity
	 *
	 * Note that this returns the values of the relationship table, not the entities themselves
	 *
	 * @return array
	 */
	function get_right_relationships_info() // {{{
	{
		if( !$this->_right_relationships_info )
			$this->_init_right_relationships();
		return $this->_right_relationships_info;
	} // }}}

	/**
	 * Returns an entity of the site that owns this entity
	 * @return entity
	 */
	function get_owner() // {{{
	{
		$right_rels = $this->get_right_relationship( get_owns_relationship_id( $this->get_value('type') ) );
		if( !empty( $right_rels[ 0 ] ) )
			return $right_rels[ 0 ];
		else
			return 0;
	} // }}}
	/**
	 * Returns true if entity is owned or borrowed by site in first argument
	 * @param integer $site_id
	 * @return bool
	 */
	function owned_or_borrowed_by($site_id) // {{{
	{
		$site = new entity($site_id);
		$owner = $this->get_owner();
		if( $owner->id() == $site->id() || $this->has_right_relation_with_entity( $site, get_borrows_relationship_id( $this->get_value('type') ) ) )
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
	function user_can_edit_relationship($relationship, $user = null, $direction)
	{
		$locks = $this->get_locks();
		return $locks->user_can_edit_relationship($relationship, $user, $direction);
	}
}

?>