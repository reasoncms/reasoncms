<?php
	/**
	 *	Reason Utility Functions
	 *
	 *	This file contains a set of functions that can be considered the core of the Reason global function API.
	 *
	 *	This file contains basic functions for:
	 *	-- reading data from the Reason DB
	 *	-- providing reflection of the Reason DB data structure
	 *	-- privileges
	 *	-- editor integration
	 *	-- common string handling needs & other basic utilities
	 *
	 *	Functions that write to the database -- Create, Update, Delete -- are *not* in this file; they are in admin_actions.php
	 *
	 *	@todo Rethink the global namespace aspect of many of these functions -- can they be put in a static class so as to simplify the API and clean up the global namespace?
	 * 	@todo Replace all direct SQL building with use of DBSelector.
	 *
	 *	@author Dave Hendler
	 *	@author Nate White
	 *	@author Matt Ryan
	 *
	 *	@copyright 2002-2008 Carleton College
	 *	@license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	 *
	 *	@package reason
	 *  @subpackage function_libraries
	 */

	/**
	 * Include dependencies and other files that should be considered part of the basic Reason utilities
	 */
	include_once( 'reason_header.php' );
	include_once( CARL_UTIL_INC . 'db/db_selector.php' );
	include_once( CARL_UTIL_INC . 'basic/misc.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'function_libraries/url_utils.php' );
	reason_include_once( 'classes/object_cache.php' );

	/**
	 * Get the id of an item with a given unique name
	 *
	 * This is the standard way to find the ID of a specific item in the Reason database.
	 * It is how, for example, reason type entities are identified.
	 *
	 * For example, if you are confident a given unique name is in the database:
	 *
	 * $image_type = new entity(id_of('image'));
	 *
	 * Or, if you're not confident:
	 * 
	 * if($id = id_of('notsure', true, false))
	 * {
	 * 	$entity = new entity($id);
	 * 	...
	 * }
	 *
	 * @param string $unique_name The unique name that you want the id of
	 * @param boolean $cache Use a static cache - (entity unique name changes / updates flush the static cache so manually setting this to false is rarely/never necessary)
	 * @param boolean $report_not_found_error Trigger a warning if the unique name is not in the database. Set to false if the value is coming from userland or if it is a test that you will be doing separate reporting on.
	 * @return integer The Reason ID of the corresponding entity or 0 if not found
	 */
	function id_of( $unique_name, $static_cache = true, $report_not_found_error = true )
	{
		static $retrieved = array();
		if( !$static_cache || empty( $retrieved ) )
		{
			$retrieved = reason_get_unique_names();
		}
		if( isset( $retrieved[ $unique_name ] ) )
			return $retrieved[ $unique_name ];
		else
		{
			if($report_not_found_error)
				trigger_error('Unique name requested ('.$unique_name.') not in database');
			return 0;
		}
	}

	/**
	 * Get the unique name of an entity that you have the id of - this avoids any query because we just use our cache.
	 *
	 * @param int $id The id of the entity from which you want a unique name
	 * @param boolean $static_cache (entity unique name changes / updates flush the static cache so manually setting this to false is rarely/never necessary)
	 * @param boolean $report_not_found_error Trigger a warning if the unique name is not in the database. Set to false if the value is coming from userland or if it is a test that you will be doing separate reporting on.
	 * @return mixed The unique_name of the corresponding entity or false if not found.
	 */	
	function unique_name_of( $id, $static_cache = true, $report_not_found_error = true )
	{
		static $retrieved;
		if( !isset( $retrieved ) OR empty( $retrieved ) )
			$retrieved = array();

		if( !$static_cache OR empty( $retrieved) )
		{
			$unique_names = reason_get_unique_names();
			$retrieved = array_flip($unique_names);
		}
		if (isset( $retrieved[ $id ] ) ) return $retrieved[ $id ];
		else
		{
			if($report_not_found_error) trigger_error('Entity id provided ('.id.') does not have a unique name');
			return false;
		}
	}
	
	/**
	 * We use a 10 minutes cache for this super common query. We update the cache in admin_actions.php whenever unique names are added or changed.
	 */
	function reason_get_unique_names()
	{
		$cache = new ReasonObjectCache('reason_unique_names', 600);
		if ($unique_names =& $cache->fetch())
		{
			return $unique_names;
		}
		else
		{
			return reason_refresh_unique_names();
		}
	}
	
	/**
	 * Create/refresh the unique name cache from the database.
	 * 
	 * @return array unique names
	 */
	function reason_refresh_unique_names()
	{
		$dbq = new DBSelector();
		$dbq->add_table('entity');
		$dbq->add_field('entity', 'id');
		$dbq->add_field('entity', 'unique_name');
		$dbq->add_relation('unique_name IS NOT NULL');
		$dbq->add_relation('unique_name != ""');
		$dbq->add_relation('(state = "Live" OR state = "Pending")');
		$r = db_query( $dbq->get_query(),'Error getting unique names in reason_refresh_unique_names' );
		while( $row = mysql_fetch_array( $r ))
		{
			$retrieved[ $row[ 'unique_name' ] ] = $row[ 'id' ];
		}
		mysql_free_result( $r );
		if (!empty($retrieved))
		{
			$cache = new ReasonObjectCache('reason_unique_names');
			if ($result = $cache->set($retrieved))
			{
				id_of('site', false); // refresh the id_of static cache
			}
			return $retrieved;
		}
		else
		{
			trigger_error('reason_refresh_unique_names did not update the cache because no unique names were retrieved');
		}
		return array();
	}
	
	/**
	 * Check to see if a given unique name exists in the Reason database
	 *
	 * @param string $unique_name The unique name to check
	 * @param boolean $cache Set to false to bypass the static cache (this should now always be unnecessary as the cache is invalidated when unique names are added/modified.)
	 * @return boolean true if the unique name exists, false if it does not
	 */
	function reason_unique_name_exists($unique_name, $cache = true)
	{
		if(id_of($unique_name, $cache, false))
			return true;
		return false;
	}
	
	/**
	 * Identifies whether a given string could be used as a unique name
	 *
	 * Note that this function *does not* check whether the given string is already used as a unique name -- only if it matches a fixed pattern
	 *
	 * @param string $string The string to check
	 * @return boolean true if it matches the pattern, otherwise false
	 */
	function reason_unique_name_valid_string($string)
	{
		if(!empty($string) && preg_match( "|^[0-9a-z_]*$|i", $string))
			return true;
		return false;
	}
	/**
	 * Validate a string as fitting the unique name pattern
	 *
	 * This function is similar to @reason_unique_name_valid_string(), but returns the given string if the given string matches the pattern
	 *
	 * @param string $string The string to validate
	 * @return string The given string if it matches the pattern; otherwise an empty string
	 */
	function reason_unique_name_validate_string($string)
	{
		if(reason_unique_name_valid_string($string))
			return $string;
		return '';
	}
	
	/**
	 * Get the id of a relationship in the allowable_relationships table
	 *
	 * This is the standard way to reference a relationship type defined in the allowable_relationships table
	 *
	 * Usage:
	 * $es = new entity_selector();
	 * $es->add_type(id_of('image');
	 * $es->add_left_relationship($page_id, relationship_id_of('page_to_image'); // limit selection to images related to the page via the page_to_image relationship
	 * $images = $es->run_one();
	 *
	 * @param string $relationship_name The name of the relationship we want to get the ID of
	 * @param boolean $static_cache (allowable relationship changes / update flush the static cache so manually setting this to false is rarely/never necessary)
	 * @param boolean $report_not_found_error Set to false if you don't want Reason to emit a warning if the relationship id can't be found
	 * @return mixed The relationship's ID if found; otherwise boolean false
	 */
	function relationship_id_of( $relationship_name, $static_cache = true, $report_not_found_error = true ) // {{{
	{
		static $retrieved = array();
		if( !isset( $retrieved ) OR empty( $retrieved ) )
			$retrieved = array();

		if( !$static_cache OR empty( $retrieved ) )
		{
			$retrieved = reason_get_relationship_names();
		}
		if (isset( $retrieved[ $relationship_name ] ) ) return $retrieved[ $relationship_name ];
		else
		{
			if($report_not_found_error) trigger_error('Relationship name requested ('.$relationship_name.') not in database');
			return false;
		}
	}

	/**
	 * Find the name of a relationship from its ID
	 *
	 * @param integer $relationship_id The ID of the relationship to test
	 * @param boolean $static_cache (allowable relationship changes / update flush the static cache so manually setting this to false is rarely/never necessary)
	 * @param boolean $report_not_found_error Set to false if you don't want Reason to emit a warning if the relationship name can't be found
	 * @todo why are we querying?
	 * @return mixed The (string) relationship name if found, otherwise boolean false
	 */
	function relationship_name_of( $relationship_id, $static_cache = true, $report_not_found_error = true )
	{
		static $retrieved;
		if( !isset( $retrieved ) OR empty( $retrieved ) ) $retrieved = array();
		if (!reason_relationship_names_are_unique() && ( !$static_cache OR !isset( $retrieved[ $relationship_id ] ) OR !$retrieved[ $relationship_id ] ))
		{
			$q = "SELECT name FROM allowable_relationship WHERE id = '" . $relationship_id . "'";
			$r = db_query( $q , "Error getting relationship name" );
			if( $row = mysql_fetch_array( $r ))
			{
				$name = $row['name'];
				$retrieved[ $relationship_id ] = $name;
			}
			mysql_free_result( $r );
		}
		elseif( !$static_cache OR empty( $retrieved) )
		{
			$relationship_names = reason_get_relationship_names();
			$retrieved = array_flip($relationship_names);
		}
		if (isset( $retrieved[ $relationship_id ] ) ) return $retrieved[ $relationship_id ];
		else
		{
			if($report_not_found_error) trigger_error('Relationship id requested ('.$relationship_id.') not in database');
			return false;
		}
	}
	
	/**
	 * We use a 10 minutes cache for this super common query. We update the cache in admin_actions.php whenever allowable relationships are added or changed.
	 */
	function reason_get_relationship_names()
	{
		$cache = new ReasonObjectCache('reason_relationship_names', 600);
		if ($relationship_names =& $cache->fetch())
		{
			return $relationship_names;
		}
		else
		{
			return reason_refresh_relationship_names();
		}
	}
	
	/**
	 * Create/refresh the relationship name cache from the database.
	 * 
	 * @return array unique names
	 */
	function reason_refresh_relationship_names()
	{
		$dbq = new DBSelector();
		$dbq->add_table('allowable_relationship');
		$dbq->add_field('allowable_relationship', 'id');
		$dbq->add_field('allowable_relationship', 'name');
		$r = db_query( $dbq->get_query(),'Error getting relationship anmes in reason_refresh_relationship_names' );
		while( $row = mysql_fetch_array( $r ))
		{
			$retrieved[ $row[ 'name' ] ] = $row[ 'id' ];
		}
		mysql_free_result( $r );
		if (!empty($retrieved))
		{
			$cache = new ReasonObjectCache('reason_relationship_names');
			if ($result = $cache->set($retrieved))
			{
				relationship_id_of('site_to_type', false); // refresh the relationship_id_of static cache
			}
			return $retrieved;
		}
		else
		{
			trigger_error('reason_refresh_relationship_names did not update the cache because no relationship names were retrieved');
		}
		return array();
	}

	/**
	 * Find out if a given relationship name exists in the allowable relationships table
	 *
	 * @param string $relationship_name The name we want to test
	 * @param boolean $cache Set to false if you don't want Reason to consult its process-level cache (for example, if you have added the relationship earlier in the same process)
	 * @return boolean true if found, otherwise false
	 */
	function reason_relationship_name_exists($relationship_name, $cache = true)
	{
		if(relationship_id_of($relationship_name, $cache, false))
			return true;
		return false;
	}
	
	/**
	 * Find out if a given table name exists in reason
	 *
	 * @param string $table_name The table you want to test
	 * @return boolean true if found, otherwise false
	 */
	function reason_table_exists($table_name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->limit_fields('entity.name');
		$es->limit_tables();
		$es->add_relation('entity.name = "'.addslashes($table_name).'"');
		$results = $es->run_one();
		return (!empty($results));
	}
	
	/**
	 * Get all the allowable relationships that feature a given type
	 * @param integer $type_id
	 * @return array of type row arrays (fieldname=>fieldvalue)
	 * @author Matt Ryan
	 */
	function get_allowable_relationships_for_type($type_id)
	{
		$ret = array();
		$q = 'SELECT * FROM allowable_relationship WHERE (relationship_a = "' . addslashes($type_id) . '" OR relationship_b = "'.addslashes($type_id).'")';
		$r = db_query( $q , "Error getting relationships for type: $type_id" );
		while( $row = mysql_fetch_assoc( $r ))
		{
			$ret[$row['id']] = $row;
		}
		mysql_free_result( $r );
		return $ret;
	}

	/**
	 * @deprecated use get_borrows_relationship_id
	 */
	function get_borrow_relationship_id($type_id)
	{
		trigger_error('get_borrow_relationship_id is deprecated - use get_borrows_relationship_id instead');
		return get_borrows_relationship_id($type_id);
	}

   /**
	* Finds the id of the allowable relationship of the "site borrows ..." relationship for a given type.  
	* @param int $type_id The id of the type whose borrows relationship we seek
	* @return mixed $alrel_id The id of the allowable relationship or false if none found
	*/	
	function get_borrows_relationship_id($type_id)
	{
		static $cache = array();
		if(!isset($cache[$type_id]))
		{
			if (reason_relationship_names_are_unique())
			{
				$rel_id = relationship_id_of('site_borrows_'.unique_name_of($type_id));
				if (!empty($rel_id)) $cache[$type_id] = $rel_id;
			}
			else // legacy
			{
				$q = 'SELECT `id` FROM allowable_relationship WHERE name = "borrows" AND relationship_a = '. id_of( 'site' ) . ' AND relationship_b = ' . $type_id.' LIMIT 1';
				$r = db_query( $q , 'Error selecting allowable relationship in get_borrows_relationship_id()' );
				$row = mysql_fetch_array( $r , MYSQL_ASSOC );
				if(!empty($row[ 'id']))
				{
					$cache[$type_id] = $row[ 'id'];
				}
			}
			if (!isset($cache[$type_id]))
			{
				trigger_error('No allowable relationship found for site borrows type id '.$type_id);
				$cache[$type_id] = false;
			}
		}
		return $cache[$type_id];
	}
	
	/**
	* Finds the id of the allowable relationship of the "site owns ..." relationship for a given type.  
	* @param int $type_id The id of the type whose owns relationship we seek
	* @return mixed $alrel_id The id of the allowable relationship or false if none found
	*/
	function get_owns_relationship_id($type_id)
	{
		static $cache = array();
		if(!isset($cache[$type_id]))
		{
			if (reason_relationship_names_are_unique())
			{
				$rel_id = relationship_id_of('site_owns_'.unique_name_of($type_id));
				if (!empty($rel_id)) $cache[$type_id] = $rel_id;
			}
			else // legacy
			{
				$q = 'SELECT `id` FROM allowable_relationship WHERE name = "owns" AND relationship_a = '. id_of( 'site' ) . ' AND relationship_b = ' . $type_id.' LIMIT 1';
				$r = db_query( $q , 'Error selecting allowable relationship in get_owns_relationship_id()' );
				$row = mysql_fetch_array( $r , MYSQL_ASSOC );
				if(!empty($row[ 'id']))
				{
					$cache[$type_id] = $row[ 'id'];
				}
			}
			if (!isset($cache[$type_id]))
			{
				trigger_error('No allowable relationship found for site owns type id '.$type_id);
				$cache[$type_id] = false;
			}
		}
		return $cache[$type_id];
	}
	
	/**
	 * Finds the id of the parent allowable relationship for a given type
	 *
	 * Note that this function caches results, so it can be called multiple times with little performance impact
	 *
	 * As of Reason 4.2, when possible it is preferable to use relationship_id_of with the relationship unique name instead of this method.
	 *
	 * @param integer $type_id the id of the type
	 * @return mixed The alrel id if a parent relationship exists; otherwise false
	 * @author Matt Ryan (mryan@acs.carleton.edu)
	 */
	function get_parent_allowable_relationship_id($type_id)
	{
		static $cache = array();
		if(!isset($cache[$type_id]))
		{
			$q = 'SELECT `id` FROM allowable_relationship WHERE name LIKE "%parent%" AND relationship_a = "'. $type_id . '" AND relationship_b = "' . $type_id.'" LIMIT 0,1';
			$r = db_query( $q , 'Error selecting allowable relationship in get_parent_allowable_relationship_id()' );
			$row = mysql_fetch_array( $r , MYSQL_ASSOC );
			if(!empty($row[ 'id']))
			{
				$cache[$type_id] = $row[ 'id'];
			}
			else
			{
				$cache[$type_id] = false;
			}
		}
		return $cache[$type_id]; 
	}
	
	// big fat warning: this object will not retrieve the 'entity' table.  that is assumed.  use this function at your own risk.
	// REMEMBER TO ADD THE ENTITY TABLE TO THE LIST OF TABLES THIS WILL PRODUCE
	function get_entity_tables_by_type_object( $type ) // {{{
	{
		$dbq = new DBSelector;

		$dbq->add_field( 'e','name' );
		$dbq->add_table( 'e','entity' );
		$dbq->add_table( 'e2','entity' );
		$dbq->add_table( 'r','relationship' );
		$dbq->add_relation( 'e.type = e2.id' );
		$dbq->add_relation( 'e2.unique_name = "content_table"' );
		$dbq->add_relation( 'r.entity_a = '.$type );
		$dbq->add_relation( 'r.entity_b = e.id' );
		return $dbq;

	} // }}}

	/**
 	 * @param type id of a type entity.
 	 * @param whether or not to use the static cache
 	 * @return array of entity tables for type
 	 */
 	function get_entity_tables_by_type( $type, $cache = true )
	{
		static $retrieved;
		if( !$cache OR !isset( $retrieved[ $type ] ) OR !$retrieved[ $type ] )
		{
			$dbq = get_entity_tables_by_type_object( $type );
			
			$tables[] = 'entity';
			$r = db_query( $dbq->get_query(),'Unable to load entity tables by type.' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$tables[] = $row['name'];
			mysql_free_result( $r );
			
			$retrieved[ $type ] = $tables;
			return $tables;
		}
		else
			return $retrieved[ $type ];
	}
	
// 	/**
// 	 * @param type id of a type entity.
// 	 * @param whether or not to use the static cache
// 	 * @return array of entity tables for type
// 	 */
// 	function get_entity_tables_by_type( $type, $cache = true )
// 	{
// 		static $retrieved;
// 		if( !$cache OR !isset( $retrieved[ $type ] ) OR !$retrieved[ $type ] )
// 		{
// 			$object_cache = new ReasonObjectCache('entity_tables_for_type_'.$type, -1);
// 			if ($tables =& $object_cache->fetch())
// 			{
// 				$retrieved[ $type ] = $tables;
// 			}
// 			else $retrieved[ $type ] = reason_refresh_entity_tables_by_type($type);
// 		}
// 		return $retrieved[ $type ];
// 	}
// 	
// 	/**
// 	 * Find entity tables for a given type and store them in a ReasonObjectCache.
// 	 *
// 	 * @param type type_id
// 	 * @return array tables for the type
// 	 */
// 	function reason_refresh_entity_tables_by_type( $type )
// 	{
// 		$cache = new ReasonObjectCache('entity_tables_for_type_'.$type);
// 		$dbq = get_entity_tables_by_type_object( $type );
// 		$tables[] = 'entity';
// 		$r = db_query( $dbq->get_query(),'Unable to load entity tables by type.' );
// 		while( $row = mysql_fetch_assoc($r) )
// 		{
// 			$tables[] = $row['name'];
// 		}
// 		mysql_free_result( $r );
// 		if ($result = $cache->set($tables))
// 		{
// 			get_entity_tables_by_type( $type, false ); // refresh the static cache
// 		}
// 		return $tables;
// 	}
	
	function get_entity_tables_by_id_object( $id ) // {{{
	{
		$dbq = new DBSelector;

		$dbq->add_field( 't','name' );
		
		$dbq->add_table( 't','entity' );
		$dbq->add_table( 'type','entity' );
		$dbq->add_table( 'item','entity' );
		$dbq->add_table( 'r','relationship' );
		if (!reason_relationship_names_are_unique())
		{
			$dbq->add_table( 'ar','allowable_relationship' );
		}

		$dbq->add_relation( 't.type = type.id' );
		$dbq->add_relation( 'type.unique_name = "content_table"' );
		$dbq->add_relation( 'item.id = '.$id );
		$dbq->add_relation( 'r.entity_a = item.type' );
		$dbq->add_relation( 'r.entity_b = t.id' );
		if (reason_relationship_names_are_unique())
		{
			$dbq->add_relation( 'r.type = '.relationship_id_of('type_to_table') );
		}
		else
		{
			$dbq->add_relation( 'ar.name = "type_to_table"' );
		}
		return $dbq;
	} // }}}
	/**
	 * Given an entity id, find the tables that make up its type
	 * @param integer $id the id of a Reason entity
	 * @param boolean $cache false will force this function to do a new query (normally does only one query per type and subsequently refers to an internal cache)
	 * @return array of table names
	 */
	function get_entity_tables_by_id( $id, $cache = true ) // {{{
	{
		$id = (integer) $id;
		static $retrieved;
		if( empty( $retrieved ) )
			$retrieved = array();
		if(!$id)
		{
			trigger_error('Empty ID passed to get_entity_tables_by_id()',FATAL);
			die();
		}
		
		// originally: if( !$cache OR !isset( $retrieved[ $id ] ) OR !$retrieved[ $id ] )
		if( !$cache OR empty( $retrieved[ $id ] ) )
		{
			$dbq = get_entity_tables_by_id_object( $id );
			$tables[] = 'entity';
			$r = db_query( $dbq->get_query(),'Unable to load entity tables by id.' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$tables[] = $row['name'];
			mysql_free_result( $r );

			$retrieved[ $id ] = $tables;
			return $tables;
		}
		else
			return $retrieved[ $id ];
	} // }}}

	function get_types_object() // {{{
	{
		$dbq = new DBSelector;

		$dbq->add_table( 'e','entity' );
		$dbq->add_table( 'e2','entity' );
		$dbq->add_field( 'e','*' );
		$dbq->add_relation( 'e.type = e2.id');
		$dbq->add_relation( 'e2.id = e2.type' );

		return $dbq;
	} // }}}
	function get_types() // {{{
	{
		$dbq = get_types_object();

		$r = db_query( $dbq->get_query(), 'Unable to grab types' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$results[ $row['id'] ] = $row;
		mysql_free_result( $r );
		return $results;
	} // }}}

	function get_type_id_from_name_object( $type_name ) // {{{
	{
		$q = new DBSelector;

		$q->add_table( 'type','entity' );
		$q->add_field( 'type','id' );
		$q->add_relation( 'type.unique_name = "'.$type_name.'"' );

		return $q;
	} // }}}
	function get_type_id_from_name( $type_name ) // {{{
	{
		$q = get_type_id_from_name_object( $type_name );
		$r = db_query( $q->get_query(), 'Unable to grab type id' );
		$row = mysql_fetch_array( $r, MYSQL_ASSOC );
		mysql_free_result( $r );
		return $row['id'];
	} // }}}

	function get_fields_by_content_table( $table, $cache = true ) // {{{
	{
		static $results = '';
		if( empty( $results ) )
			$results = array();
		if( !$cache || empty( $results[ $table ] ) )
		{
			$results[ $table ] = array();
			$q = 'desc ' . $table;
			$r = db_query( $q , 'Error Describing ' . $table );
			while( $row = mysql_fetch_array( $r ) )
				$results[ $table ][] = $row[ 'Field' ];
		}
		return $results[ $table ];
	} // }}}

	//------------------------------------------------
	//	entity retrieval
	//------------------------------------------------
	
	function get_entity_by_id_object( $id ) // {{{
	{
		$dbq = new DBSelector;
		$tables = get_entity_tables_by_id( $id );
		if ( $tables )
		{
			foreach( $tables as $table )
			{
				$dbq->add_field( $table,'*' );
				$dbq->add_table( $table );
				$dbq->add_relation( 'entity.id = '.$table.'.id' );
			}
		}
		$dbq->add_relation( 'entity.id = '.$id );
		return $dbq;
	} // }}}
	
	/**
	 * Return fully populated values for an entity.
	 *
	 * @param id int primary key of entity
	 * @param cache boolean when or not to use static cache
	 */
	function get_entity_by_id( $id, $cache = true ) // {{{
	{
		static $retrieved;
		if( !isset( $retrieved ) OR empty( $retrieved ) )
			$retrieved = array();
		if( !$cache OR !isset( $retrieved[ $id ] ) OR !$retrieved[ $id ] )
		{
			$dbq = get_entity_by_id_object( $id );
			$dbq->set_num(1);
			$r = db_query( $dbq->get_query(), 'Unable to grab entity' );
			$result = array();
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$result = $row;
			mysql_free_result( $r );

			$retrieved[ $id ] = $result;
			return $result;
		}
		else
			return $retrieved[ $id ];
	} // }}}
	
	/**
	 * Set up a dbselector object for grabbing Reason entities
	 *
	 * @param integer $type the id of the type to grab
	 * @param mixed $site_id an site id (integer) or an array of site ids
	 * @param string $sharing either "owns", "borrows", "owns borrows", or ""
	 * @param array $table_mod tables to be included or excluded
	 * @param string $table_action either "include" or "exclude" -- specifies what to do with tables given in $table_mod
	 */
	function get_entities_by_type_object( $type, $site_id = '' , $sharing = 'owns', $table_mod = array(), $table_action = '' ) // {{{
	{
		if(!($type = (integer) $type))
		{
			trigger_error('get_entities_by_type_object() requires an integer type id as its first parameter.');
			return '';
		}
		$dbq = new DBSelector;
		if ($table_action == 'include') $tables = $table_mod;
		else $tables = get_entity_tables_by_type( $type );
		if ($table_action == 'exclude') $tables = array_diff($tables, $table_mod);
		
		foreach( $tables as $table )
		{
			$dbq->add_field( $table,'*' );
			$dbq->add_table( $table );
			// don't match entity table against itself
			if( $table != 'entity' )
				$dbq->add_relation( '`entity`.`id` = `'.$table.'`.`id`' );
		}
		
		$dbq->add_relation( '`entity`.`type` = "'.$type.'"' );

		if( $site_id && $sharing )
		{
			$dbq->add_table( 'r','relationship' );
			if (!reason_relationship_names_are_unique())
			{
				$dbq->add_table( 'ar','allowable_relationship' );
			}
			if(is_array($site_id))
			{
				array_walk($site_id,'db_prep_walk');
				$dbq->add_relation( 'r.entity_a IN ('.implode(',',$site_id).')');
			}
			else
			{
				$dbq->add_relation( 'r.entity_a = "'.addslashes($site_id).'"');
			}
			$dbq->add_relation( 'r.entity_b = entity.id');
			if (!reason_relationship_names_are_unique())
			{
				$dbq->add_relation( 'r.type = ar.id' );
			}
			if( preg_match( '/owns/' , $sharing ) && preg_match( '/borrows/' , $sharing ) )
			{
				if (reason_relationship_names_are_unique())
				{
					$owns_rel_id = get_owns_relationship_id($type);
					$borrows_rel_id = get_borrows_relationship_id($type);
					$dbq->add_relation( '(r.type = '.$owns_rel_id.' OR r.type = '.$borrows_rel_id.')' );
				}
				else $dbq->add_relation( '(ar.name = "owns" OR ar.name = "borrows")' );
			}
			elseif( preg_match( '/borrows/' , $sharing ) )
			{
				if (reason_relationship_names_are_unique())
				{
					$borrows_rel_id = get_borrows_relationship_id($type);
					$dbq->add_relation( '(r.type = '.$borrows_rel_id.')' );
				}
				else $dbq->add_relation( 'ar.name = "borrows"' );
			}
			else
			{
				if (reason_relationship_names_are_unique())
				{
					$owns_rel_id = get_owns_relationship_id($type);
					$dbq->add_relation( '(r.type = '.$owns_rel_id.')' );
				}
				else $dbq->add_relation( 'ar.name = "owns"' );
			}
		}

		return $dbq;
	} // }}}
	function get_entities_by_type( $type, $site_id = '' ) // {{{
	{
		$dbq = get_entities_by_type_object( $type, $site_id );

		$r = db_query( $dbq->get_query(), 'Unable to grab entities' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$results[ $row['id'] ] = $row;
		mysql_free_result( $r );
		return $results;
	} // }}}

	function get_entities_by_type_name_object( $type_name, $site_id = '' ) // {{{
	{
		return get_entities_by_type_object( get_type_id_from_name( $type_name, $site_id ) );
	} // }}}
	function get_entities_by_type_name( $type_name, $site_id = '' ) // {{{
	{
		return get_entities_by_type( get_type_id_from_name( $type_name, $site_id = '' ) );
	} // }}}

	function get_fields_by_type( $type , $full_field = false ) // {{{
	{
		$tables = get_entity_tables_by_type( $type );
		$fields = array();
		foreach( $tables as $value )
		{
			$r = db_query( 'desc ' . $value , 'Unable to select fields' );
			while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
			{
				if( $full_field )
					$fields[ $row[ 'Field' ] ] = $row;
				else
					$fields[ $row[ 'Field' ] ] = $row[ 'Field' ];
			}
			mysql_free_result( $r );
		}
		return $fields;
	} // }}}
	
	//------------------------------------------------
	//	association retrieval
	//------------------------------------------------

	function get_association_types_by_left_type_object( $type ) // {{{
	// retrieves allowable relationships where relationship_a = $type (the left entity type)
	{
		$q = new DBSelector;

		$q->add_table( 'ar','allowable_relationship' );
		$q->add_relation( 'ar.relationship_a = '.$type );

		return $q;
	} // }}}
	function get_association_types_by_left_type( $type ) // {{{
	{
		$q = get_association_types_by_left_type_object( $type );

		$r = db_query( $q->get_query(), 'Unable to get association types by left type' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$res[ $row['id'] ] = $row;
		return $res;
	} // }}}

	function get_associations_by_type_object( $type_a, $type_b ) // {{{
	/*	gets all associations where entity_a's content_type_id = $type_a and entity_b's ctid = $type_b
	 *	returns a list of entity ids 
	 */
	{
		$dbq = new DBSelector;

		$dbq->add_field( 'r', 'entity_a' );
		$dbq->add_field( 'r', 'entity_b' );
		$dbq->add_table( 'r', 'relationship' );
		$dbq->add_table( 'e1', 'entity' );
		$dbq->add_table( 'e2', 'entity' );
		$dbq->add_relation( 'e1.id = r.entity_a' );
		$dbq->add_relation( 'e1.type = '.$type_a );
		$dbq->add_relation( 'e2.id = r.entity_b' );
		$dbq->add_relation( 'e2.type = '.$type_b );

		return $dbq;
	} // }}}
	function get_associations_by_type( $type_a, $type_b ) // {{{
	{
		$dbq = get_associations_by_type_object( $type_a, $type_b );

		$r = db_query( $dbq->get_query(), 'Unable to retrieve associations' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$results[ $row['entity_a'] ] = $row[ 'entity_b' ];
		mysql_free_result( $r );
		return $results;
	} // }}}

	function get_entity_associations_object( $id ) // {{{
	{
		$dbq = new DBSelector;

		$dbq->add_table( 'r','relationship' );
		$dbq->add_field( 'r','entity_b' );
		$dbq->add_relation( 'r.entity_a = '.$id );

		return $dbq;
	} // }}}
	function get_entity_associations( $id ) // {{{
	{
		$dbq = get_entity_associations_object( $id );
		
		$r = db_query( $dbq->get_query(), 'Unable to retrieve associations for this entity' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$res[ $row['entity_b'] ] = $row['entity_b'];
		mysql_free_result( $r );
		return $res;
	} // }}}

	/**
	 * @deprecated - joining across the allowable relationship table based on name is typically unneeded now that relationship ids are unique.
	 */
	function get_entity_associations_by_type_name_object( $relation_type ) // {{{
	{
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_table( 'ar','allowable_relationship' );
		$dbq->add_field( 'r','entity_a' );
		$dbq->add_field( 'r','entity_b' );
		$dbq->add_relation( 'r.type = ar.id' );
		// backwards compatibility 
		if (reason_relationship_names_are_unique() && in_array($relation_type, array('owns', 'borrows', 'archive')))
		{
			$dbq->add_relation( 'ar.type = "'.$relation_type.'"' );
		}
		else
		{
			$dbq->add_relation( 'ar.name = "'.$relation_type.'"' );
		}
		return $dbq;
	} // }}}

	/**
	 * Am I a low-level deprecated method? maybe.
	 */
	function get_entity_associations_by_type_object( $relation_id ) // {{{
	{
		$dbq = new DBSelector;
		$dbq->add_table( 'r','relationship' );
		$dbq->add_table( 'ar','allowable_relationship' );
		$dbq->add_field( 'r','entity_a' );
		$dbq->add_field( 'r','entity_b' );
		$dbq->add_relation( 'r.type = ar.id' );
		$dbq->add_relation( 'ar.id = "'.$relation_id.'"' );
		return $dbq;
	} // }}}

	//------------------------------------------------
	//	more specific functions
	//------------------------------------------------

	/**
	 * should we just use the entity selector here?
	 */
	function auth_site_to_user( $site_id, $user_id ) // {{{
	{
		$d = get_entity_associations_by_type_name_object( 'site_to_user' );
		$d->add_table( 'user','entity' );
		$d->add_relation( 'user.name = "'.$user_id.'"' );
		$d->add_relation( 'user.id = r.entity_b' );
		$d->add_relation( 'r.entity_a = "'.$site_id.'"' );

		if ( !count( $d->run() ) )
			die( 'You are not authorized to use this page' );
	} // }}}

	/**
	 * should we just use the entity selector here?
	 */
	function auth_site_to_type( $site_id, $type_id ) // {{{
	{
		$d = get_entity_associations_by_type_name_object( 'site_to_type' );
		$d->add_relation( 'r.entity_a = '.$site_id );
		$d->add_relation( 'r.entity_b = '.$type_id );

		if ( !$d->run() )
			die( 'This site does not have access to that content type' );
	} // }}}

	/**
	 * does this have some performance reason it is better than just doing $e->get_owner()?
	 * @todo consider deprecation
	 */
	function site_owns_entity( $site_id, $entity_id ) // {{{
	{
		$d = get_entity_by_id_object( $entity_id );
		$d->add_table( 'ar' , 'allowable_relationship' );
		$d->add_table( 'r' , 'relationship' );

		$d->add_relation( 'ar.id = r.type' );
		if (reason_relationship_names_are_unique())
		{
			$d->add_relation( 'ar.type = "owns"' );
		}
		else $d->add_relation( 'ar.name = "owns"' );
		$d->add_relation( 'r.entity_a = ' . $site_id );
		$d->add_relation( 'r.entity_b = ' . $entity_id );
		$r = db_query( $d->get_query() , 'Error checking ownership' );
		if( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
		{
			mysql_free_result( $r );
			return true;
		}
		else
		{
			mysql_free_result( $r );
			return false;
		}
	} // }}}

	function site_borrows_entity( $site_id, $entity_id ) // {{{
	{
		$d = get_entity_by_id_object( $entity_id );
		$d->add_table( 'ar' , 'allowable_relationship' );
		$d->add_table( 'r' , 'relationship' );

		$d->add_relation( 'ar.id = r.type' );
		if (reason_relationship_names_are_unique())
		{
			$d->add_relation( 'ar.type = "borrows"' );
		}
		else $d->add_relation( 'ar.name = "borrows"' );
		$d->add_relation( 'r.entity_a = ' . $site_id );
		$d->add_relation( 'r.entity_b = ' . $entity_id );
		$r = db_query( $d->get_query() , 'Error checking ownership' );
		if( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
		{
			mysql_free_result( $r );
			return true;
		}
		else
		{
			mysql_free_result( $r );
			return false;
		}
	} // }}}
	
	/**
	 * @return array of site entities that borrow an entity.
	 * @todo consider just using entity selector
	 */
	function get_sites_that_are_borrowing_entity($entity_id)
	{
		$d = get_entity_associations_by_type_name_object( 'borrows' );
		$d->add_relation( 'r.entity_b = '.$entity_id );
		$r = db_query( $d->get_query() , 'Error checking borrowing' );
		$sites = array();
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
		{
			$sites[ $row['entity_a'] ] = new entity($row['entity_a']);
		}
		return $sites;
	}
	
	/**
	 * Get the reason id for a username or null if it does not exist (or no username is provided).
	 *
	 * - Uses a static cache to store reason username => id info that is successfully found.
	 * - Note we return the id as a string since that is what the previous (and slow) version of this function did.
	 *
	 * @param $username string name of user entity
	 * @return mixed string entity id of the reason user entity (or null if none found)
	 * @todo we might consider only searching on master admin site to speed this up.
	 * @author Nathan White
	 */
	function get_user_id( $username )
	{
		static $users;
		if (empty($username)) return null;
		if (!isset($users[$username]))
		{
			$es = new entity_selector();
			$es->limit_tables('entity');
			$es->limit_fields();
			$es->add_type(id_of('user'));
			$es->add_relation('entity.name = "'.addslashes($username).'"');
			$es->set_num(1);
			$result = $es->run_one();
			if ($result)
			{
				$result_keys = array_keys($result);
				$users[$username] = (string) reset($result_keys);
			}
			else $users[$username] = null;
		}
		return $users[$username];
	}

	/**
	* Determines if a given reason user has a given role
	*
	* Note: This function is fairly slow (requires a potentially poky db hit on every call).  If you must use it, store its results rather than asking again. Better yet, use reason_user_has_privs().
	*
	* @deprecated Use reason_user_has_privs(), for performance reasons and to allow extensibility of user roles
	* @param integer $user_id
	* @param integer $role_id
	*/
	function user_is_a( $user_id, $role_id ) // {{{
	{
		$user = new entity( $user_id );
		if( $user->has_left_relation_with_entity( new entity( $role_id ), relationship_id_of( 'user_to_user_role' ) ) )
			return true;
		else
			return false;
	} // }}}
	
	/**
	 * Returns the value of REASON_MAINTENANCE_MODE
	 * @return REASON_MAINTENANCE_MODE
	 */
	function reason_maintenance_mode()
	{
		if (defined("REASON_MAINTENANCE_MODE"))
		{
			return REASON_MAINTENANCE_MODE;
		}
		else trigger_error('Please define REASON_MAINTENANCE_MODE in reason_settings.php. It should default to false.');
		return false;
	}
	
	/**
	 * Determines if a given reason user has a given privilege
	 *
	 * Note: This function is *fast*. There's no need to carefully store and pass around its results -- just call it again.
	 *
	 * Privileges:
	 * - add
	 *   - The privilege to create new (pending) entities.
	 * - edit_pending
	 *   - The privilege to edit pending entities
	 * - delete_pending
	 *   - The privilege to delete pending entities (e.g. mark them as "deleted")
	 * - edit
	 *   - The privilege to edit live entities
	 * - delete
	 *   - The privilege to delete live entities (e.g. mark them as "deleted")
	 * - publish
	 *   - The privilege to publish entities (e.g. change their state from pending to live)
	 * - borrow
	 *   - The privilege to borrow entities from other sites
	 * - expunge
	 *   - The privilege to expunge deleted entities from the Reason database (That, is remove them forever)
	 * - duplicate
	 *   - The privilege to duplicate entities (By default, limited to admins as of 3/08, as this is a experimental feature of Reason)
	 * - edit_html
	 *   - The privilege to switch between WYSIWYG view and HTML view in the HTML editor
	 * - switch_theme
	 *   - The privilege to change the site's theme (if the site's theme is not locked by an administrator)
	 * - pose_as_other_user
	 *   - The privilege to interact with the Reason edministrative interface as if they were someone else. NOTE: This is a *very* powerful privilege, as it amounts to superuser rights!
	 * - assign_any_page_type
	 *   - The privilege to choose from all Reason page types, rather than a select few
	 * - edit_head_items
	 *   - The privilege to insert arbitrary HTML into the page head (css, scripts, meta tags, etc.)
	 * - edit_unique_names
	 *   - The privilege to give Reason entities unique names. This is necessary for creating sites and types.
	 * edit_fragile_slugs
	 *   - The privilege to modify a slug that may cause broken links if changed (e.g. publication feed URL slugs)
	 * edit_home_page_nav_link
	 *   - The privilege to insert a custom link to site home pages in the navigation (instead of the standard "Sitename Home")
	 * - edit_form_advanced_options
	 *   - The privilege to edit advanced options in the thor form content manager
	 * - manage_allowable_relationships
	 *   - The privilege to modify, create, and delete the set of relationships can be made between Reason entities. NOTE: This is very powerful, and should only be given to highly trustworthy individuals
	 * - view_sensitive_data
	 *   - The privilege to view any data in Reason (like a fulltext search of the entire Reason db)
	 * - manage_integration_settings
	 *   - The privilege to modify or override foreign keys and other values in Reason that pertain to integration with external data sources
	 * - edit_raw_ldap_filters
	 *   - The privilege to write full LDAP filters/queries (e.g. in the construction of dynamic groups)
	 * - upload_full_size_image
	 *   - The privilege to keep images from being resized upon upload, thereby retaining their original dimensions
	 * - upgrade
	 *   - The privilege to run Reason's upgrade scripts
	 * - db_maintenance
	 *   - The privilege to run standard database cleanup and sanity-checking scripts
	 * - update_urls
	 *   - The privilege to run Reason's .htaccess regeneration script
	 * - bypass_locks
	 *   - The privilege edit any locked field or relationship
	 * - manage_locks
	 *   - The privilege edit any locked field or relationship
	 * - customize_all_themes
	 *   - The privilege to customize any site's theme
	 *
	 * @param integer $user_id The Reason entity id of the user
	 * @param string $privilege
	 * @return boolean true if the user has the privilege, false if not
	 */
	function reason_user_has_privs( $user_id, $privilege /*, $site_id = NULL, $type_id = NULL, $item_id = NULL */ )
	{
		$user_id = (integer) $user_id;
		if(empty($user_id))
			return false;
		static $privs_cache = array();
		if(empty($cache[$user_id]))
		{
			$roles = reason_user_roles($user_id);
		}
		elseif(isset($privs_cache[$user_id][$privilege]))
		{
			return $privs_cache[$user_id][$privilege];
		}
		$privs = reason_get_privs_table();
		foreach($roles as $role)
		{
			if(isset($privs[$role]) && in_array($privilege,$privs[$role]))
			{
				$privs_cache[$user_id][$privilege] = true;
				return true;
			}
		}
		$privs_cache[$user_id][$privilege] = false;
		return false;
	}
	
	/**
	 * Get the user roles for a given user id
	 * @param integer $user_id
	 * @return array of user role entities
	 */
	function reason_user_roles($user_id)
	{
		static $roles_cache = array();
		if(empty($user_id))
		{
			trigger_error('Empty user id passed to reason_user_roles. Returning empty array.');
			return array();
		}
		if(empty($roles_cache[$user_id]))
		{
			$roles_cache[$user_id] = array();
			
			reason_include_once('classes/entity_selector.php');
			$es = new entity_selector();
			$es->add_type(id_of('user_role'));
			$es->limit_tables();
			$es->limit_fields(array('entity.unique_name'));
			$es->add_right_relationship($user_id, relationship_id_of( 'user_to_user_role' ));
			$roles = $es->run_one();
			
			if(!empty($roles))
			{
				foreach($roles as $role)
				{
					if($role->get_value('unique_name'))
						$roles_cache[$user_id][] = $role->get_value('unique_name');
				}
			}
			if(empty($roles_cache[$user_id]))
				$roles_cache[$user_id][] = 'editor_user_role';
		}
		return $roles_cache[$user_id];
	}
	
	/**
	 * Determine if a given role has a given privilege
	 *
	 * @param string $role_name The unique name of the role entity
	 * @param string $privilege The priv identifier string
	 * @return boolean
	 */
	function reason_role_has_privs($role_name,$privilege)
	{
		$privs = reason_get_privs_table();
		if(isset($privs[$role_name]) && in_array($privilege,$privs[$role_name]))
		{
			return true;
		}
		return false;
	}
	
	
	
	/**
	 * Get the way user privileges are assigned to roles
	 *
	 * Returns an array in this format:
	 *
	 * array( 'role_unique_name_1' => array( 'privilege_1', 'privilege_2', ... ), 'role_unique_name_2' => array( 'privilege_2', 'privilege_3', ... ), ...);
	 *
	 * @return array
	 */
	function reason_get_privs_table()
	{
		return array(
				'contribute_only_role'=>array('add','edit_pending','delete_pending',),
				'editor_user_role'=>array('add','edit_pending','delete_pending','edit','delete','publish','borrow','expunge','switch_theme',),
				'power_user_role'=>array('add','edit_pending','delete_pending','edit','delete','publish','borrow','expunge','switch_theme','edit_html','upload_full_size_image',),
				'admin_role'=>array('add','edit_pending','delete_pending','edit','delete','publish','borrow','expunge','duplicate','edit_html','switch_theme','pose_as_other_user','assign_any_page_type','edit_head_items','edit_unique_names','edit_fragile_slugs','edit_home_page_nav_link','edit_form_advanced_options','manage_allowable_relationships','view_sensitive_data','manage_integration_settings','edit_raw_ldap_filters','upload_full_size_image','upgrade','db_maintenance','update_urls','bypass_locks','manage_locks','customize_all_themes',),
		);
	}

	function get_owner_site_id( $entity_id ) //{{{
	{
		// do we really need the whole object? 
		//$d = get_entity_by_id_object( $entity_id );
		
		$d = new DBSelector;

		$d->add_table( 'entity' );
		$d->add_relation( 'entity.id = '.$entity_id );
		$d->add_field( 'r', 'entity_a', 'site_id' );
		$d->add_table( 'ar' , 'allowable_relationship' );
		$d->add_table( 'r' , 'relationship' );
		$d->add_relation( 'ar.id = r.type' );
		if (reason_relationship_names_are_unique())
		{
			$d->add_relation( 'ar.type = "owns"' );
		}
		else $d->add_relation( 'ar.name = "owns"' );
		$d->add_relation( 'r.entity_b = ' . $entity_id );
		$d->set_num(1);
		$r = db_query( $d->get_query() , 'Error getting owning site ID.' );
		if( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
		{
			return $row['site_id'];
		}
		else
			return false;
	} // }}}
	
	/**
	 * @return boolean whether or not relationship unique names have been implemented in this reason database - call me only once
	 */
	function reason_relationship_names_are_unique( $force_refresh = NULL )
	{
		static $rel_names_are_unique;
		if ($force_refresh || !isset($rel_names_are_unique))
		{
			$rel_names_are_unique = reason_relationship_name_exists('site_owns_minisite_page', false); // lets force a fresh draw
			if ($rel_names_are_unique == false)
			{
				trigger_error('Reason relationship names not yet unique - please run the 4.1 to 4.2 upgrade script "allowable_relationship_unique_names"');
			}
		}
		return $rel_names_are_unique;
	}
	
	/**
	 * A simple helper function for getting the content of a uniquely named text blurb
	 *
	 * @param string $unique_name The unique name of the text blurb you want to get the contents of
	 * @param boolean $cache	Use the id_of function's cache or not
	 * @param boolean $report_not_found_error	Trigger an error if the unique name is not in the Reason database
	 * @return mixed	String if success; otherwise NULL
	 * @todo In a future version: return NULL if entity is not a text blurb
	 */
	function get_text_blurb_content( $unique_name, $cache = true, $report_not_found_error = true ) //{{{
	{
		$id = id_of( $unique_name, $cache, $report_not_found_error );
		if(!empty($id))
		{
			$e = new entity( $id );
			if($e->get_value('type') == id_of('text_blurb'))
			{
				return $e->get_value( 'content' );
			}
			else
			{
				trigger_error($unique_name.' is not a text blurb. Use of get_text_blurb_content() for non-blurb entities is deprecated.');
				return $e->get_value( 'content' );
			}
		}
		return;
	} // }}}

	/**
	 * Returns the path components after /minisite_templates/modules/
	 * 
	 * @param string full_path - absolute path
	 * @param string suffix - extension to strip
	 * @param string dir - we retain this for backwards compatibility - carl_basename should be used instead if this is specified
	 *
	 * @return string
	 */
	function module_basename( $full_path, $suffix = '.php', $dir = '/minisite_templates/modules/' )
	{
		return carl_basename( $full_path, $suffix, $dir );
	}
	
	/**
	 * Returns the path components after /reason_package/
	 * 
	 * @param string full_path - absolute path
	 * @param string suffix - extension to strip
	 *
	 * @return string
	 */
	function reason_basename( $full_path, $suffix = '.php' )
	{
		// if the pathname does not appear to be in the reason include we do some symlink hocus pocus.
		if (carl_strpos($full_path, REASON_INC) === FALSE) // something going on with symbolic links possibly.
		{
			// first try local
			$local_path = reason_get_local_path("");
			$real_local_path = realpath($local_path);
			if (carl_substr( $real_local_path, -1 ) != '/') $real_local_path = $real_local_path . "/"; // add trailing slash.
			if (carl_strpos( $full_path, $real_local_path ) !== FALSE)
			{
				return carl_basename( $local_path . carl_substr($full_path, carl_strlen($real_local_path)), $suffix, '/reason_package/' );
			}
			
			// now try core
			$core_path = reason_get_core_path("");
			$real_core_path = realpath($core_path);
			if (carl_substr( $real_core_path, -1 ) != '/') $real_core_path = $real_core_path . "/"; // add trailing slash.
			if (carl_strpos( $full_path, $real_core_path ) !== FALSE)
			{
				return carl_basename( $local_path . carl_substr($full_path, carl_strlen($real_core_path)), $suffix, '/reason_package/' );
			}
		}
		return carl_basename( $full_path, $suffix, '/reason_package/' );
	}

	function clean_vars( &$vars, $rules )
	{
		$call_info = array_shift( debug_backtrace() );
		$code_line = $call_info['line'];
		$file = array_pop( explode('/', $call_info['file']));
		trigger_error('deprecated function clean_vars called by ' . $file . ' on line ' . $code_line . ' - use carl_clean_vars instead', WARNING);
		return carl_clean_vars( $vars, $rules );
	}
	
	/* **** Some helpful queries **** {{{

		>> show all relationships, substitute ids in relationship to the names in entity
		SELECT r.id,e1.name, e2.name FROM entity AS e1, entity AS e2, relationship AS r WHERE r.entity_a = e1.id AND r.entity_b = e2.id;


	 }}} */
	
	/**
	 * Determines if a site shares a type
	 * @param integer $site_id the id of the site
	 * @param integer $type_id the id of the type
	 * @return bool $site_shares_type true if site shares type, false if it does not
	 */
	function site_shares_type($site_id, $type_id)
	{
		static $retrieved;
		if (!isset($retrieved[$site_id][$type_id]))
		{
			$dbq = new DBSelector();
			$dbq->add_table( 'ar','allowable_relationship' );
			$dbq->add_table( 'r', 'relationship' );	

			$dbq->add_relation( 'ar.name = "site_shares_type"' );
			$dbq->add_relation( 'r.type = ar.id' );

			$dbq->add_relation( 'r.entity_a = '.$site_id );
			$dbq->add_relation( 'r.entity_b = '.$type_id );
	
			$q = $dbq->get_query();
			$r = db_query( $q, 'Failed determination of site\'s sharing status.' );
			if( mysql_num_rows( $r ) > 0 )
			{
				$retrieved[$site_id][$type_id] = true;
			}
			else
			{
				$retrieved[$site_id][$type_id] = false;
			}
		}
		return $retrieved[$site_id][$type_id];
	}
	
	
	/**
	 * Determines the HTML editor for a particular site
	 * @param integer $site_id the id of the site
	 * @return string $html_editor_name name of the html editor used by the site	 
	 */
	function html_editor_name($site_id)
	{
		$obj = get_html_editor_integration_object($site_id);
		return $obj->get_plasmature_type();
	}
	
	/**
	 * Returns params particular to the editor assigned to a particular site
	 * @param integer $site_id the id of the site
	 * @param integer $user_id the id of the user; can be 0 to indicate anonymous usage
	 * @return array $params params ready to be passed to the plasmature element
	 */
	function html_editor_params($site_id,$user_id=0)
	{
		$obj = get_html_editor_integration_object($site_id);
		return $obj->get_plasmature_element_parameters($site_id, $user_id);
	}
	function html_editor_options($site_id)
	{
		$obj = get_html_editor_integration_object($site_id);
		return $obj->get_configuration_options();
	}
	
	function get_html_editor_integration_object($site_id)
	{
		static $editor_objects = array();
		if( empty( $editor_objects[$site_id] ) )
		{
			$es = new entity_selector();
			$es->add_type(id_of('html_editor_type'));
			$es->add_right_relationship($site_id,relationship_id_of('site_to_html_editor'));
			$es->set_num(1);
			$editors = $es->run_one();
			
			$html_editor_filename = defined('REASON_DEFAULT_HTML_EDITOR') ? REASON_DEFAULT_HTML_EDITOR : 'base.php';
			if(!empty($editors))
			{
				$editor = current($editors);
				if($editor->get_value('html_editor_filename'))
				{
					$html_editor_filename = $editor->get_value('html_editor_filename');
				}
			}
			if(!strpos($html_editor_filename, '.php'))
			{
				$html_editor_filename .= '.php';
			}
			
			if(reason_file_exists('html_editors/'.$html_editor_filename))
			{
				reason_include_once('html_editors/'.$html_editor_filename);
				if(!empty($GLOBALS[ '_reason_editor_integration_classes' ][ $html_editor_filename ]))
				{
					if(class_exists($GLOBALS[ '_reason_editor_integration_classes' ][ $html_editor_filename ]))
					{
						$editor_objects[$site_id] = new $GLOBALS[ '_reason_editor_integration_classes' ][ $html_editor_filename ];
					}
					else
					{
						trigger_error('The class '.$GLOBALS[ '_reason_editor_integration_classes' ][ $html_editor_filename ].' does not exist; using default editor (plain text)');
					}
				}
				else
				{
					trigger_error('The file html_editors/'.$html_editor_filename.' is not recording its class name in $GLOBALS[ \'_reason_editor_integration_classes\' ]. Using default editor (plain text)');
				}
			}
			else
			{
				trigger_error('The file html_editors/'.$html_editor_filename.' does not exist. Using default editor (plain text)');
			}
			if(!isset($editor_objects[$site_id]))
			{
				reason_include_once('html_editors/base.php');
				$editor_objects[$site_id] = new $GLOBALS[ '_reason_editor_integration_classes' ][ 'base.php' ];
			}
		}
		return $editor_objects[$site_id];
	}
	
	function make_empty_array($site_id = 0, $user_id = 0)
	{
		return array();
	}
	
	/**
	 * Works like implode(), but only includes an element if it is not empty.
	 */
	function implode_non_empty($separator, $array)
	{
		$return = '';
		$fired = false;
		
		if (!is_array($array) && func_num_args() >= 2) {
			$array = func_get_args();
			unset($array[0]);
		}
		
		foreach ((array) $array as $el) {
			if (empty($el) && ($el !== (int) $el))
				continue;
			if ($fired)
				$return .= $separator;
			else
				$fired = true;
			$return .= $el;
		}
		return $return;
	}
	
	/**
	 * Find out what page types use the a given module
	 *
	 * @param mixed $module_name string or array of module names
	 * @return array $page_types
	 */
	function page_types_that_use_module($module_name)
	{
		if(is_array($module_name))
		{
			$ret = array();
			foreach($module_name as $name)
			{
				$ret = array_merge($ret, page_types_that_use_module($name));
			}
			return $ret;
		}
		else
		{
			reason_include_once('classes/page_types.php');
			$rpts =& get_reason_page_types();
			$pt_using_module = $rpts->get_page_type_names_that_use_module($module_name);
			return $pt_using_module;
		}
	}

	/**
	 * Find a template entity given a template name
	 *
	 * @param string $name The name of the template
	 * @return mixed template entity if found; false if not found
	 * @author Matt Ryan
	 * @since Reason 4.0 beta 4
	 */
	function get_template_by_name($name)
	{
		static $retrieved = array();
		if(!isset($retrieved[$name]))
		{
			$es = new entity_selector();
			$es->add_type(id_of('minisite_template'));
			$es->add_relation('entity.name = "'.addslashes($name).'"');
			$es->set_num(1);
			$templates = $es->run_one();
			if(!empty($templates))
				$retrieved[$name] = current($templates);
			else
				$retrieved[$name] = false;
		}
		return $retrieved[$name];
	}
	
	/**
	 * Determine if a given user can edit a given site
	 *
	 * Note: this function takes a *Reason* ID -- other implementations generally take a username/netid
	 *
	 * @param integer $user_id The Reason entity ID of the user
	 * @param integer $site_id The Reason entity ID of the site
	 * @param boolean $use_cache Defaults to true; set to false if your script previously changed this value and you need to know what the new value will be
	 * @return boolean true if user can edit site, false if not
	 */
	function user_can_edit_site($user_id, $site_id, $use_cache = true)
	{
		static $cache = array();
		if(!isset($cache[$user_id]))
			$cache[$user_id] = array();
		if(!$use_cache || !isset($cache[$user_id][$site_id]))
		{
			$es = new entity_selector();
			$es->add_type(id_of('user'));
			$es->add_right_relationship($site_id,relationship_id_of('site_to_user'));
			$es->add_relation('entity.id = "'.$user_id.'"');
			$es->set_num(1);
			$es->limit_tables();
			$es->limit_fields();
			$users = $es->run_one();
			if(empty($users))
				$cache[$user_id][$site_id] = false;
			else
				$cache[$user_id][$site_id] = true;
		}
		return $cache[$user_id][$site_id];
	}
	
	/**
	 * Convert special characters into HTML/XHTML/XML entities, but don't double-encode
	 *
	 * This function is necessary because Tidy will convert some -- but not all -- characters into their entities (quotes remain simple quotes, for example)
	 *
	 * As of php 5.2 it might be possible to use the 4th parameter of htmlspeciachars
	 *
	 * @param string $string
	 * @return string encoded string
	 */
	function reason_htmlspecialchars($string)
	{
		$string = str_replace(array('&amp;','&gt;','&lt;','&quot;','&#039;'),array('&','>','<','"',"'"),$string);
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8' );
	}
	
	/**
	 * Log the amount of time it took to generate the current page/process
	 *
	 * @param integer $milliseconds The number of milliseconds that it took to generate the page
	 * @return boolean success
	 */
	function reason_log_page_generation_time($milliseconds)
	{
		if(defined('REASON_PERFORMANCE_PROFILE_LOG') && REASON_PERFORMANCE_PROFILE_LOG)
		{
			$pieces = array( carl_date('r'), get_current_url(), $milliseconds );
			array_walk( $pieces, 'quote_walk' );
			$msg = implode( ',', $pieces );
			return dlog( $msg, REASON_PERFORMANCE_PROFILE_LOG );
		}
		return false;
	}
	
	/**
	 * Turn an array of unique names into an array of entities
	 *
	 * The returned array is keyed on entity id.
	 *
	 * The returned array will be smaller than the array given if any of the unique names aren't
	 * found in the database.
	 *
	 * @param array $unique_names
	 * @return array Reason entities
	 */
	function reason_unique_names_to_entities($unique_names)
	{
		$entities = array();
		foreach($unique_names as $unique_name)
		{
			if($id = id_of($unique_name) )
			{
				$entities[$id] = new entity($id);
			}
		}
		return $entities;
	}
	
	/**
	 * Get a list of tables that are not normal entity tables, and which should not be treated as such
	 *
	 * @return array
	 */
	function reason_get_protected_tables()
	{
		return array('allowable_relationship','entity','page_cache_log','relationship','URL_history',);
	}
	
	/**
	 * Checks whether or not something is an entity.
	 *
	 * Specifically, this checks if an item is an object and has a method called entity (the entity class constructor). In addition, you
	 * can perform extra checks to see if the entity has a type value (any value or a specific value).
	 *
	 * There are better ways to do this in php 5, but to maintain cross compatibility with php 4 and php 5 instanceof and is_a are not reliable,
	 * and so we do a method_exists check to see if the constructor "entity" is defined.
	 *
	 * Please note this in its default behavior this just checks to see if an item is of class entity - that does not mean it has a type or
	 * or exists in the database.
	 * 
	 * @param object
	 * @param mixed of_type - if "true" makes sure the entity has some value for type, if given a unique name, makes sure the entity is of that type.
	 * @todo the has_value statements will crash when run on an entity that has been initialized with a string instead of a numeric id - consider fixing this
	 * @return boolean
	 */
	function reason_is_entity($obj, $of_type = false)
	{
		$is_entity = (is_object($obj) && method_exists($obj, "entity"));
		if ($is_entity && ($of_type === true))
		{
			return $obj->has_value('type') ? ($obj->get_value('type')) : false;
		}
		elseif ($is_entity && !empty($of_type))
		{
			return (reason_unique_name_exists($of_type) && $obj->has_value('type')) ? ($obj->get_value('type') == id_of($of_type)) : false;
		}
		return $is_entity;
	}
	
	/**
	 * In a multidomain reason install the web path for a site might not be the web path for the current domain.
	 *
	 * This function returns the correct webpath for a given reason site or false if a site with a custom domain setting is not defined in domain_settings.php
	 *
	 * @author Nathan White
	 * @return string absolute file system directory that is the web root for a site
	 */
	function reason_get_site_web_path($site_id_or_entity)
	{
		$site = (is_numeric($site_id_or_entity)) ? new entity($site_id_or_entity) : $site_id_or_entity;
		if (reason_is_entity($site, 'site'))
		{
			$domain = $site->get_value('domain');
			if (!empty($domain) && isset($GLOBALS['_reason_domain_settings'][$domain]['WEB_PATH']))
			{
				return $GLOBALS['_reason_domain_settings'][$domain]['WEB_PATH'];
			}
			elseif (!empty($domain))
			{
				trigger_error('reason_get_site_web_path called on site id ' . $site->id() . ' with domain value ' . $domain . ' that is not defined in domain_settings.php');
				return false;
			}
			return reason_get_default_web_path();
		}
		else
		{
			trigger_error('reason_get_site_web_path passed a value that is not a site_id or site entity');
			return false;
		}
	}
	/**
	 * Get the url of an icon for a given type
	 *
	 * Note that this only supports .png icons at the moment.
	 * If someone wants to use other file types, feel free to add support and contribute back. :)
	 *
	 * @param mixed $type id, unique name, or entity (entity will offer best performance in tight loops)
	 * @param boolean $use_default Provide the url of a default icon if none available
	 * @return string URL of the icon (html encoded), or an empty string if none found and $use_default set to false
	 * @todo add support for image types other than .png (will need some sort of hierarchy of formats)
	 */
	function reason_get_type_icon_url($type,$use_default = true)
	{
		if(!is_object($type))
		{
			if(is_numeric($type))
				$type = new entity($type);
			elseif(is_string($type))
				$type = new entity(id_of($type));
			else
			{
				trigger_error('$type not an object, integer, or string');
				$type = NULL;
			}
		}
		if(isset($type) && $type->get_values() && $type->get_value('unique_name') && (file_exists(REASON_INC.'www/ui_images/types/'.$type->get_value('unique_name').'.png') || file_exists(REASON_INC.'www/local/ui_images/types/'.$type->get_value('unique_name').'.png') ) )
		{
			return REASON_HTTP_BASE_PATH.'ui_images/types/'.reason_htmlspecialchars($type->get_value('unique_name')).'.png';
		}
		elseif($use_default)
		{
			return REASON_HTTP_BASE_PATH.'ui_images/types/default.png';
		}
		else
		{
			return '';
		}
		
	}
	
	/**
	 * Get the default webpath when the setting is not derived from a domain defined in 
	 * domain_settings.php
	 * @return string the file system path to the web root
	 */
	function reason_get_default_web_path()
	{
		if (isset($GLOBALS['_default_domain_settings']['WEB_PATH']))
		{
			return $GLOBALS['_default_domain_settings']['WEB_PATH'];
		}
		else
		{
			trigger_error('A default domain setting is not defined for this Reason instance. Please update package_settings.php to use domain_define 
						  instead of define when setting the WEB_PATH constant.');
			return WEB_PATH;
		}
	}
	
	/**
	 * Get the relationship id for archived entities for a given type
	 * @param integer $type_id
	 * @return mixed integer relationship id if success; false if no success
	 */
	function reason_get_archive_relationship_id($type_id)
	{
		static $cache = array();
		$type_id = (integer) $type_id;
		if(empty($type_id))
		{
			trigger_error('Type ID must be an integer in reason_archive_relationship_id()');
			return false;
		}
		if(!isset($cache[$type_id]))
		{
			$cache[$type_id] = false;
			if (reason_relationship_names_are_unique())
			{
				$rel_id = relationship_id_of(unique_name_of($type_id) . '_archive');
				if (!empty($rel_id)) $cache[$type_id] = $rel_id;
			}
			else
			{
				$q = 'SELECT id FROM allowable_relationship WHERE name LIKE "%archive%" AND relationship_a = '.$type_id.' AND relationship_b = '.$type_id.' LIMIT 0,1';
				$r = db_query( $q, 'Unable to get archive relationship.' );
				$row = mysql_fetch_array( $r, MYSQL_ASSOC );
				mysql_free_result( $r );
				if(!empty($row)) $cache[$type_id] = $row['id'];
			}
		}
		return $cache[$type_id];
	}
	
	/**
	 * Can a given site edit a type? If so, the following are true:
	 *
	 * 1. There is a relationship over the site_to_type for the pair of entities.
	 * 2. There is not a relationships across the site_cannot_edit_type relationship for a pair of entities.
	 *
	 * @param mixed $site_entity_or_id
	 * @param mixed $type_entity_or_id
	 * @return boolean
	 */
	function reason_site_can_edit_type($site_entity_or_id, $type_entity_or_id)
	{
		static $cache = array();
		$site_id = (is_object($site_entity_or_id)) ? $site_entity_or_id->id() : $site_entity_or_id;
		$type_id = (is_object($type_entity_or_id)) ? $type_entity_or_id->id() : $type_entity_or_id;
		if (!empty($site_id) && !empty($type_id))
		{
			if (!isset($cache[$site_id][$type_id]))
			{
				$es = new entity_selector();
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('type'));
				$es->add_right_relationship($site_id, relationship_id_of('site_to_type'));
				$es->add_relation('entity.id = "'.$type_id.'"');
				$result = $es->run_one();
				
				$es2 = new entity_selector();
				$es2->limit_tables();
				$es2->limit_fields();
				$es2->add_type(id_of('type'));
				$es2->add_right_relationship($site_id, relationship_id_of('site_cannot_edit_type'));
				$es2->add_relation('entity.id = "'.$type_id.'"');
				$result2 = $es2->run_one();
				
				$cache[$site_id][$type_id] = (!empty($result) && empty($result2));
			}
		}
		else
		{
			trigger_error('reason_site_can_edit_type was provided invalid parameters and will return false');
			return false;
		}
		return $cache[$site_id][$type_id];
	}
	
	/**
	 * Gets all the relationship info about an allowable relationship
	 * @param int $r_id id in ar table
	 * @return mixed
	 */
	function reason_get_allowable_relationship_info( $alrel_id )
	{
		$cache = array();
		if(!isset($cache[$alrel_id]))
		{
			$q = 'SELECT * FROM `allowable_relationship` WHERE `id` = "' . addslashes($alrel_id) . '"';
			$r = db_query( $q , 'error getting relationship info' );
			$cache[$alrel_id] = mysql_fetch_array( $r , MYSQL_ASSOC );
		}
		return $cache[$alrel_id];
	}
	
	/**
	 * Get the sites a given user has administrative access to
	 *
	 * @param mixed $user entity or user id
	 * @return array of site entities
	 */
	function reason_user_sites($user)
	{
		if(is_object($user))
			$user_id = $user->id();
		else
			$user_id = (integer) $user;
		
		if(empty($user_id))
		{
			trigger_error('reason_user_sites() requires a user entity or integer ID as its first parameter. Returning empty array.');
			return array();
		}
		
		static $cache = array();
		
		if(!isset($cache[$user_id]))
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship($user_id, relationship_id_of('site_to_user'));
			$es->limit_tables();
			$es->limit_fields();
			$cache[$user_id] = $es->run_one();
		}
		
		return $cache[$user_id];
	}
	/**
	 * Factory function for grabbing and setting up the theme customizer for a given site
	 *
	 * @param mixed $site site entity or ID
	 * @param mixed $theme theme entity or ID; if not provided Reason will determine the current theme
	 * @return mixed object or false if no customizer
	 */
	function reason_get_theme_customizer($site, $theme = NULL)
	{
		if(is_numeric($site))
			$site = new entity($site);
		
		if(empty($theme))
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'theme_type' ) );
			$es->add_right_relationship( $site->id() , relationship_id_of( 'site_to_theme' ) );
			$es->set_num(1);
			$tmp = $es->run_one();
			if(!empty($tmp))
				$theme = current( $tmp );
			else
				return false;
		}
		elseif(is_numeric($theme))
		{
			$theme = new entity($theme);
		}
		
		if($theme->get_value('theme_customizer'))
		{
			reason_include_once('theme_customizers/'.$theme->get_value('theme_customizer').'.php');
			if(!empty($GLOBALS[ 'reason_theme_customizers' ][ $theme->get_value('theme_customizer') ]))
			{
				if(class_exists($GLOBALS[ 'reason_theme_customizers' ][ $theme->get_value('theme_customizer') ]))
				{
					if($site->get_value('theme_customization'))
					{
						$all_customization_data = json_decode($site->get_value('theme_customization'));
						$theme_id = $theme->id();
						if(isset($all_customization_data->$theme_id))
						{
							$customization_data = $all_customization_data->$theme_id;
						}
					}
					if(empty($customization_data))
						$customization_data = new stdClass;
					$customizer = new $GLOBALS[ 'reason_theme_customizers' ][ $theme->get_value('theme_customizer') ]();
					$customizer->set_customization_data($customization_data);
				}
				else
				{
					trigger_error('Theme customizer "'.$theme->get_value('theme_customizer').'" not registered properly.');
					$customizer = false;
				}
			}
			else
			{
				trigger_error('Theme customizer "'.$theme->get_value('theme_customizer').'" not found or not registered properly.');
				$customizer = false;
			}
		}
		return $customizer;
	}
?>