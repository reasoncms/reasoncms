<?php
	/**
	  *	Admin Actions
	  *
	  *	These are actions that modify the database.
	  *
	  *	@author Dave Hendler, Matt Ryan, Nate White
	  * @package reason
	  * @subpackage function_libraries
	  */
	
	/**
	 * get the SQLER object for simple queries
	 */
	include_once( 'reason_header.php' );
	include_once( CARL_UTIL_INC . 'db/sqler.php' );
	reason_include_once( 'classes/entity.php' );

	/**
	 * Take a simple array of keys and values, and, given table names,
	 * build an array keyed on table name.
	 *
	 * @param array $tables Simple array; format: array('table1','table2',...)
	 * @param array $flat_values Associative array; format: array('field1'=>'value1','field2'=>'value2','field3'=>'value3',...)
	 * @return array Multidimensional associative array; format: array('table1'=>array('field1'=>'value1','field2'=>'value2'),'table2'=>array('field3'=>'value3',...),...)
	 *
	 * @todo add caching so that db structure does not need to be looked up each time
	 * @todo make query more robust or use standard library to look up field names
	 */
	function values_to_tables( $tables, $flat_values, $ignore = array() ) // {{{
	{
		// build field to table association
		$field_to_table = array();
		foreach( $tables AS $table )
		{
			$q = "DESC $table";
			$r = db_query( $q, 'Unable to get table description.' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$field_to_table[ $row['Field'] ] = $table;
			mysql_free_result( $r );
		}
		
		// build the array of tables->fields->values
		$table_values = array();
		foreach( $flat_values AS $key => $val )
			if( !empty( $field_to_table[ $key ] ) )
				if( !in_array( $key, $ignore ) )
					$table_values[ $field_to_table[ $key ] ][ $key ] = $val;

		return $table_values;
	} // }}}

	/** create_entity( $site_id, $type_id, $user_id, $name, $values = array() ) {{{
	 *	Creates an entity in Reason
	 *	
	 *	Takes a site id, type id, and array of values and creates a new entity and
	 *	an ownership relationship
	 *
	 * Note: this function is deeper than reason_create_entity, and requires you to pre-process the values to be structured in the way they are stored in Reason.
	 *
	 * reason_create_entity() is an easier (and probably better) function to use.
	 *
	 *	@param	integer	$site_id	The ID of the site that will own this entity.
	 *	@param	integer	$type_id	The ID of the type of the entity.
	 *	@param	integer	$user_id	The ID of the user creating the entity.
	 *	@param	string	$name	The name of the entity
	 *	@params	array	$values		An array or arrays.  Top level keys are table names, top level values are arrays.  Each table contains an array of keys(field names in the table) and values(values for those fields).
	 *	@param	boolean $testmode	turn on testing mode; if true, insert statement is echoed rather than executed
	 *	@return	integer	$entity_id	id of the new entity
	 */
	function create_entity( $site_id, $type_id, $user_id, $name, $values = array(),$testmode = false)
	{
		// create entity record
		$q = "INSERT INTO entity (name,creation_date,type,created_by,last_edited_by,state) VALUES ('".addslashes($name)."',NOW(),$type_id,$user_id,$user_id,'Live')";
		if( $testmode )
			echo $q.'<br /><br />';
		else
			db_query( $q, 'Unable to create new entity "'.$name.'"' );

		$entity_id = mysql_insert_id();
		
		// find ownership relationship
		$q = new DBSelector;
		$q->add_table('ar','allowable_relationship');
		$q->add_table('site','entity');
		$q->add_relation( 'site.unique_name = "site"');
		$q->add_relation( 'ar.relationship_a = site.id' );
		$q->add_field( 'ar','id' );
		$q->add_relation( 'ar.relationship_b = "'.$type_id.'"' );
		if (reason_relationship_names_are_unique())
		{
			$q->add_relation( 'ar.type = "owns"' );
		}
		else
		{
			$q->add_relation( 'ar.name = "owns"' );
		}
		$tmp = $q->run();
		if( $tmp )
		{
			list(,$ownership_relation) = each( $tmp );
			$ownership_relation = $ownership_relation['id'];
		}
		if( empty( $tmp ) OR empty( $ownership_relation ) )
			die('No ownership relation exists for type:' . $type_id . '.');
	
		// create ownership relationship
		$q = "INSERT INTO relationship (entity_a, entity_b, type) VALUES ($site_id,$entity_id,$ownership_relation)";
		if( $testmode )
			echo $q.'<br /><br />';
		else
			db_query( $q, 'Unable to create ownership relation for this entity.' );
		// get tables for this type
		$entity_tables = get_entity_tables_by_type( $type_id );
		
		// if more values, create the appropriate records in the entity tables
		foreach( $entity_tables as $table )
		{
			if( $table != 'entity' )
			{
				$keys = $field_values = '';
				if( !empty( $values[ $table ] ) AND is_array( $values[ $table ] ) )
				{
					// loop through all key-val pairs
					foreach( $values[ $table ] as $key => $val )
					{
						// build string of keys and values
						$keys .= ",$key";
						$field_values .= ',"'.addslashes( $val ).'"';
					}
				}
				// create the query
				$q = "INSERT INTO $table (id".$keys.") VALUES ($entity_id".$field_values.")";
				if( $testmode )
					echo $q.'<br /><br />';
				else
					db_query( $q, 'Unable to insert entity values into entity table.' );
			}
			// the entity table already exists, so we have to update it
			else
			{
				if( !empty( $values[ 'entity' ] ) )
				{
					$sqler = new sqler;
					$sqler->update_one( 'entity', $values[ 'entity' ], $entity_id );
				}
			}
		}
		
		// If the newly-created entity has a unique_name and is not an archive entity, lets update the unique name cache.
		if (isset($values['entity']['unique_name']) && !empty($values['entity']['unique_name']) && (!isset($values['entity']['state']) || (isset($values['entity']['state']) && ($values['entity']['state'] != 'Archived'))))
		{
			reason_refresh_unique_names();
		}
		
		// return the id of the new entity
		return $entity_id;
	} // }}}
	
	/**
	 *	Creates an entity in Reason (simplified)
	 *	
	 *	Takes a site id, type id, and array of values and creates a new entity and
	 *	an ownership relationship.
	 *
	 *	This is a wrapper for create_entity() which takes care of figuring out which field goes in which table.
	 *
	 *	@param	integer	$site_id	The ID of the site that will own this entity.
	 *	@param	integer	$type_id	The ID of the type of the entity.
	 *	@param	integer	$user_id	The ID of the user creating the entity.
	 *	@param	string	$name	The name of the entity
	 *	@params	array	$values		Basic array of key-value pairs
	 *	@param	boolean $testmode	turn on testing mode; if true, insert statement is echoed rather than executed
	 *	@return	integer	id of the new entity
	 */
	function reason_create_entity( $site_id, $type_id, $user_id, $name, $values = array(),$testmode = false)
	{
		if(empty($type_id))
		{
			trigger_error('reason_create_entity() needs a type_id to function properly');
			return;
		}
		else
		{
			$tables = get_entity_tables_by_type($type_id);
			$prepped_values = values_to_tables($tables, $values);
			return create_entity( $site_id, $type_id, $user_id, $name, $prepped_values,$testmode);
		}
	}

	/** duplicate_entity( $id, $dup_relationships = true, $maintain_dates = false, $overrides = array() ) {{{
	 *	Duplicates entity with id = $id.
	 *
	 *	Specifically, copies all fields of an entity to a new id.  If dup_relationships
	 *	is true, also copies all relationships, replacing the old id with the new inserted one.
	 *
	 *	@param	$id						ID of entity to duplicate
	 *									OR an entity object to duplicate
	 *	@param	$dup_relationships		Bool that determines whether to duplicate relationships or not
	 *	@param	$maintain_dates			Bool that determines whether to 
	 *	@param	$overrides				array of field => value pairs to override any values for the new entity
	 *	@return							the new, duplicated entity id
	 */
	function duplicate_entity( $id, $dup_relationships = true, $maintain_dates = false, $overrides = array() )
	{
		// get all values and structure from existing object
		if( is_object( $id ) AND get_class( $id ) == 'entity' )
			$e = $id;
		else
			$e = new entity( $id );

		// get the site that owns this entity
		$site = $e->get_owner();
		
		// get the tables used by this type/entity
		$tables = get_entity_tables_by_id( $e->id() );

		//! start of new code (see commented note below)

		$ignored_fields = array( 'id', 'name', 'type', 'last_edited_by' );

		if( !$maintain_dates )
		{
			$ignored_fields[] = 'last_modified';
			$ignored_fields[] = 'creation_date';
		}

		// Don't ignore values set as overrides
		foreach ($ignored_fields as $key => $val)
			if (isset($overrides[$val])) unset ($ignored_fields[$key]);
		
		// convert values of entity to tabled-array structure, make sure to ignore proper fields
		$values = values_to_tables( $tables, array_merge( $e->get_values(), $overrides ), $ignored_fields );
		
		// create new entity record
		$new_entity_id = create_entity(
			$site->id(), 
			$e->get_value('type'), 
			$e->get_value('last_edited_by'), 
			$e->get_value('name'), 
			$values
		);

		// copy relationships
		if( $dup_relationships )
		{
			// make new left relationships
			$left_rels = $e->get_left_relationships();
			foreach( $left_rels AS $rel_type => $rel_obj )
			{
				if( is_int( $rel_type ) )
				{
					foreach( $rel_obj AS $r )
						create_relationship( $new_entity_id, $r->id(), $rel_type );
				}
			}
			// make new right relationships
			$right_rels = $e->get_right_relationships();
			foreach( $right_rels AS $rel_type => $rel_obj )
			{
				if( is_int( $rel_type ) )
				{
					foreach( $rel_obj AS $r )
						create_relationship( $r->id(), $new_entity_id, $rel_type );
				}
			}
		}

		// return the new entity
		return $new_entity_id;
	} // }}}

	/** delete_entity( $id )
	 *
	 *	Removes entity with id = $id and all relationships with that id from the database.
	 *
	 *	This function is deprecated, as it is confusingly named (it does not make an entity
	 *	"deleted," which is just a state change. It actually expunges the entity from the database.)
	 *
	 *	Use reason_expunge_entity() instead.
	 *
	 *	@deprecated
	 *	@param	integer	$id		The ID of the entity to delete
	 *	@return		array	the entity and all relationships of the entity just deleted
	 */
	function delete_entity( $id )
	{
		trigger_error('delete_entity() is deprecated. Use reason_expunge_entity() instead.');
		return reason_expunge_entity( $id );
	}
	
	/** Removes entity with id = $id and all relationships with that id from the database.
	 *
	 *	Specifically, deletes the entry from the entity table and all sub-tables.
	 *	Also deletes all relationships where entity_a or entity_b = id
	 *
	 *	@todo add logging of expungements so that these important actions are traceable
	 *
	 *	@param	integer	$id		The ID of the entity to delete
	 *	@param	integer	$user_id		The ID of the reason user who is deleting the item
	 *	@return		array	the entity and all relationships of the entity just deleted
	 */
	function reason_expunge_entity( $id, $user_id )
	{
		$testmode = false;
		
		if(empty($user_id) || !is_numeric($user_id) || $user_id < 1 )
		{
			trigger_error('Expungement without providing a Reason user id is deprecated. Future releases of Reason will not allow entities to be expunged without providing a user id');
		}
		
		// get all entity information before deleting it
		
		$id = (integer) $id;
		if(empty($id) || $id < 1)
		{
			trigger_error('ID passed to reason_expunge_entity() not a positive integer. Unable to expunge.');
			return false;
		}

		$entity = get_entity_by_id( $id, false );
		
		if(empty($entity['id']))
		{
			trigger_error('Entity id '.$id.' does not exisit; unable to expunge.');
			return false;
		}
		
		$sqler = new SQLER;

		$archives = array();
		$q = "SELECT r.entity_b, r.type, ar.name FROM relationship AS r,  allowable_relationship AS ar WHERE r.entity_a = $id AND r.type = ar.id";
		$r = db_query( $q, 'Unable to retrieve entity left relationships for expungement' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
		{
			$entity[ 'left_relationships' ][] = $row;
			// this is an archive - mark it for deletion.
			if( strpos( $row[ 'name' ], 'archive' ) !== false )
				$archives[] = $row[ 'entity_b' ];
		}
		
		$q = "SELECT entity_a, type FROM relationship WHERE entity_b = $id";
		$r = db_query( $q, 'Unable to retrieve entity right relationships for expungement' );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$entity[ 'right_relationships' ][] = $row;

		// call custom deletion action
		$type = get_entity_by_id( $entity[ 'type' ] );

		if( $type[ 'custom_deleter' ] )
			reason_include_once( 'content_deleters/'.$type['custom_deleter'] );

		// delete from entity table
		$q = "DELETE FROM entity WHERE id = $id";
		if( $testmode )
			echo $q.'<br /><br />';
		else
			db_query( $q, 'Unable to delete entity from entity table.' );

		// delete from sub tables
		$tables = get_entity_tables_by_type( $entity['type'] );

		foreach( $tables as $table )
		{
			if( $table != 'entity' )
			{
				if( $testmode )
					echo '$sqler->delete_one( "'.htmlspecialchars($table).'",'.$id.' )<br /><br />';
				else
					$sqler->delete_one( $table, $id );
			}
		}

		// delete relationships
		$q = "DELETE FROM relationship WHERE entity_a = $id OR entity_b = $id";
		if( $testmode )
			echo $q.'<br /><br />';
		else
			db_query( $q, 'Unable to delete relationships of entity '.$id );

		// delete all archived entities
		foreach( $archives AS $archive )
			reason_expunge_entity( $archive, $user_id );

		// If the recently-expunged entity is live or pending and has a unique_name, let's refresh_reason_unique_names
		if (!empty($entity['unique_name']) && ( !empty($entity['state']) && (($entity['state'] == 'Pending') || ($entity['state'] == 'Live'))))
		{
			reason_refresh_unique_names();
		}

		return $entity;
	} //

	/** update_entity( $id, $user_id, $updates = array(), $archive = true ) {{{
	 *	Updates entity with id = $id with the values of the array $values
	 *
	 *	reason_update_entity() provides an easier-to-use interface 
	 *	where the updates do not need to be organized by table.
	 *
	 * @todo figure out how to refresh the entity data cache on update
	 *
	 *	@param	integer	$id	ID of entity to update
	 *	@param 	integer	$user_id	Reason ID of user making changes
	 *	@param	array	$updates	array of tables with values being array of key-val pairs
	 *	@param	boolean	$archive	boolean that determines whether this update will be archived
	 *	@returns	boolean	true => entity has changed,  false => entity has not changed
	 */
	function update_entity( $id, $user_id, $updates = array(), $archive = true )
	{
		// get original entity before update
		$original = new entity( $id, false );
		$original->get_values();	// this is so unbelievably important.  stupid dave.

		// do the update
		$sqler = new SQLER;

		foreach( $updates AS $table => $fields )
			$sqler->update_one( $table, $fields, $id );

		// check for differences
		$updated_entity = new entity( $id, false );
		$keys = array_keys( $updated_entity->get_values() );
		$changed = false;
		foreach( $keys AS $key )
			if( $updated_entity->get_value( $key ) != $original->get_value( $key ) )
				$changed = true;

		if( !empty($changed) )
		{
			// entity has changed.  update last_mod and who last edited
			if(!empty($updates['entity']['last_modified']))
				$lastmod = '"'.addslashes($updates['entity']['last_modified']).'"';
			else
				$lastmod = 'NOW()';
			$q = 'UPDATE entity SET `last_modified` = '.$lastmod.', `last_edited_by` = "'.addslashes($user_id).'" where `id` = "'.addslashes($id).'"';
			db_query( $q , 'Error updating last_modified' );

			if( !empty($archive) )
			{
				$archived_id = duplicate_entity( $original, false, true, array( 'state' => 'Archived' ) );

				// create archive relationship

				// get archive relationship id
				$q = 'SELECT id FROM allowable_relationship WHERE name LIKE "%archive%" AND relationship_a = '.$original->get_value('type').' AND relationship_b = '.$original->get_value('type');
				$r = db_query( $q, 'Unable to get archive relationship.' );
				$row = mysql_fetch_array( $r, MYSQL_ASSOC );
				mysql_free_result( $r );
				$rel_id = $row['id'];
				
				// actually create the relationship
				create_relationship( $id, $archived_id, $rel_id );
			}
			
			// If the unique_name changes on the updated entity, or a uniquely named entity is deleted or undeleted, lets update the unique name cache
			if ($updated_entity->get_value('unique_name') != $original->get_value('unique_name') ||
				($original->get_value('state') != 'Deleted' && $updated_entity->get_value('state') == 'Deleted' && $original->get_value('unique_name')) ||
				($original->get_value('state') == 'Deleted' && $updated_entity->get_value('state') != 'Deleted' && $updated_entity->get_value('unique_name')))
			{
				reason_refresh_unique_names();
			}
			return true;
		}
		else
			return false;
	} // }}}
	
	/** reason_update_entity( $id, $user_id, $updates = array(), $archive = true ) {{{
	 *	Updates entity with id = $id with the values of the array $values
	 *
	 *	@param	integer	$id	ID of entity to update
	 *	@param 	integer	$user_id	Reason ID of user making changes
	 *	@param	array	$updates	simple array of key-val pairs (no tables needed)
	 *	@param	boolean	$archive	boolean that determines whether this update will be archived
	 *	@returns	boolean	true => entity has changed,  false => entity has not changed
	 */
	function reason_update_entity( $id, $user_id, $updates = array(), $archive = true)
	{
		if(empty($id))
		{
			trigger_error('reason_update_entity() needs an id to function properly');
			return false;
		}
		elseif(empty($user_id))
		{
			trigger_error('reason_update_entity() needs a user_id to function properly');
			return false;
		}
		else
		{
			$e = new entity($id);
			if($e->get_value('type'))
			{
				$tables = get_entity_tables_by_type($e->get_value('type'));
				$prepped_values = values_to_tables($tables, $updates);
				return update_entity( $id, $user_id, $prepped_values, $archive);
			}
			else
			{
				trigger_error('id passed not an entity id or doesn\'t have a type value');
				return false;
			}
		}
	}

	/**
	 *	Creates a relationship from arguments
	 *
	 *	@param	int $entity_a	ID of left entity
	 *	@param	int $entity_b	ID of right entity
	 *	@param	int $relationship_type	ID of the allowable relationship for this relationship
	 *	@param  array $more array of other attributes to set (optional)	 
	 *  @param  boolean $check_for_dup whether or not to check if the relationship already exists on the site
	 *	@return	boolean true if sql statement to create relationship was executed, false indicates no relationship created
	 */
	function create_relationship( $entity_a, $entity_b, $relationship_type ,$more=false, $check_for_dup=true)
	{
		$rel = array(
			'entity_a' => turn_into_int($entity_a),
			'entity_b' => turn_into_int($entity_b),
			'type' => turn_into_int($relationship_type)
		);
		foreach($rel as $key=>$value)
		{
			if(empty($value) || $value < 0)
			{
				trigger_error('create_relationship(): $'.$key.' must be a positive integer. No relationship created');
				return false;
			}
		}
		if(is_array($more))
		{
			$rel = array_merge($rel,$more);
		}
		elseif($more !== false)
		{
			trigger_error('create_relationship(): $more must be an array to be used (is instead a(n) '.gettype($more).') Relationship will be created, but without additional attributes.');
		}
		
		$duplicate = false;
		if ($check_for_dup)
		{
			$q = 'SELECT id, site FROM relationship WHERE entity_a = '.$rel['entity_a'].' AND entity_b = '.$rel['entity_b'].' AND type = '.$rel['type'];
			$my_result = db_query($q, 'Error checking for duplication relationships on creation');
			if (mysql_num_rows($my_result) > 0)
			{
				$existent_site = (isset($rel['site'])) ? $rel['site'] : '0';
				while ($rel = mysql_fetch_assoc($my_result))
				{
					if ($existent_site == $rel['site']) 
					{
						return false;
					}
				}
			}
		}
		$sqler = new SQLER;
		$sqler->insert( 'relationship', $rel );
		return true;
	} // }}}

	/**     
	 *	Updates a relationship with a given id
	 *
	 *	@param	int $id	ID of existant relationship
	 *	@param	array $values key=>value pairs of columns to update
	 *	@return	void
	 *  @author nwhite
	 */
	function update_relationship( $id, $values)
	{
		$q = $q2 = '';
		$id = turn_into_int($id);
		foreach ($values as $k=>$v)
		{
			$col_name = check_against_array($k, array('id', 'entity_a', 'entity_b', 'type', 'site', 'rel_sort_order'));
			$value = turn_into_int($v);
			if (!empty($col_name) && !empty($value))
			{
				$q2 .= $col_name . ' = ' . $value . ', ';
			}
		}
		if (!empty($q2))
		{
			$q2 = substr($q2, 0, -2);
			$q .= 'UPDATE relationship SET ' .$q2 . ' WHERE id = ' . $id;
			db_query( $q, 'Unable to update relationship with id ' . $id );
		}
	} // }}}

	function delete_relationships( $conditions ) //{{{
	{
		if (!empty($conditions))
		{
			if( is_string( $conditions ) )
				$wc = $conditions;
			else
			{
				$wc = '';
				foreach( $conditions AS $f => $v )
					$wc .= $f.'='.$v. ' AND ';
				$wc = substr($wc,0,-5);
			}
			$q = 'DELETE FROM relationship WHERE ' . $wc;
			db_query( $q );
		}
	} // }}}

	/** delete_relationship( $rel_id ) // {{{
	 *	Delete relationship with id = $rel_id
	 *
	 *	@param	$rel_id	ID of relationship to delete
	 *	@return void
	 */
	function delete_relationship( $rel_id ) //{{{
	{
		$sqler = new SQLER;

		$sqler->delete_one( 'relationship', $rel_id );
	} // }}} // }}}

	/** delete_left_relationship( $left_id ) {{{
	 *	Deletes all left relationships where the left entity = $left_id
	 *
	 *	@param	$left_id	ID of left entity
	 */
	function delete_left_relationship( $left_id ) //{{{
	{
		$q = "DELETE FROM relationship WHERE entity_a = $left_id";
		return db_query( $q, 'Unable to delete left relationships.' );
	} // }}} // }}}
	
	/** delete_right_relationship( $right_id ) {{{
	 *	Deletes all right relationships where the right entity = $right_id
	 *
	 *	@param	$right_id	ID of right entity
	 */
	function delete_right_relationship( $right_id ) //{{{
	{
		$q = "DELETE FROM relationship WHERE entity_b = $right_id";
		return db_query( $q, 'Unable to delete right relationships.' );
	} // }}} // }}}

	/** delete_all_relationships( $id ) {{{
	 *	Delete all relationships where $id is the left or right entity
	 *
	 *	@param	$id	ID of entity in left or right side
	 */
	function delete_all_relationships( $id ) //{{{
	{
		$q = "DELETE FROM relationship WHERE entity_a = $id OR entity_b = $id";
		return db_query( $q, 'Unable to delete relationships.' );
	} // }}} // }}}

	/**
	 * As well as removing the borrowing relationship, this removes relationships on the site where the
	 * entity being unborrowed is on the "b" side of the relationship. If the site also owns the entity 
	 * being unborrowed (not a good state) but it can happen, we skip this relationship deletion phase.
	 */
	function delete_borrowed_relationship( $site_id , $id , $rel_id ) // {{{ //{{{
	{
		// if we don't also own the entity, delete associations from the site where our entity is on the b side.
		if (!site_owns_entity( $site_id, $id ))
		{
			$dbq = new DBSelector;
			
			//actual relationships that we are selecting
			$dbq->add_table( 'r' , 'relationship' );
			$dbq->add_table( 'ar' , 'allowable_relationship' );
			$dbq->add_field( 'r' , '*' );
			$dbq->add_relation( 'r.type = ar.id' );
			$dbq->add_relation( 'r.entity_b = ' . $id );
			
			//owns relationship table
			$dbq->add_table( 'r2' , 'relationship' );
			$dbq->add_table( 'ar2' , 'allowable_relationship' );
			
			if (!reason_relationship_names_are_unique())
			{
				$dbq->add_relation( 'ar2.name = "owns"' );
			}
			else
			{
				$dbq->add_relation( 'ar2.type = "owns"' );
			}
			$dbq->add_relation( 'r2.type = ar2.id' );
			$dbq->add_relation( 'r2.entity_a = ' . $site_id );
			$dbq->add_relation( 'r2.entity_b = r.entity_a' );
			
			//current borrowship
			$dbq->add_table( 'r3' , 'relationship' );
			$dbq->add_table( 'ar3' , 'allowable_relationship' );
			
			$dbq->add_relation( 'ar3.id = ' . $rel_id );	
			$dbq->add_relation( 'r3.type = ar3.id' );
			$dbq->add_relation( 'r3.entity_a = ' . $site_id );
			$dbq->add_relation( 'r3.entity_b = ' . $id );
			$x = $dbq->run();
			
			if( $x )
			{
				$first = true;
				$in = '';
				foreach( $x AS $rel )
				{
					if (!$first) $in .= ',';
					else $first = false;
					$in .= $rel[ 'id' ];
				}
				$q = 'DELETE FROM relationship WHERE id IN(' . $in . ')';
				db_query( $q , 'Error removing associations of borrowed item before deleting' );
			}
		}
		
		$q = 'DELETE FROM relationship WHERE entity_a = ' . $site_id . ' AND entity_b = ' . $id . ' AND type = ' . $rel_id;
		db_query( $q , 'Error removing borrowship' );
	}

	function create_reason_table($table_name, $type_unique_name, $username)
	{
		if(str_replace(' ','_',addslashes($table_name)) != $table_name)
		{
			trigger_error( 'The table name ' . $table_name . ' does not exist.');
			return false;
		}
		if(is_numeric($type_unique_name))
			$type_id = $type_unique_name;
		else
			$type_id = id_of($type_unique_name, false); // lets not use cache in case type was just created
		if(empty($type_id))
		{
			trigger_error( 'The type ' . $type_unique_name . ' does not exist.');
			return false;
		}
		else
		{
			$es = new entity_selector();
			$es->add_type(id_of('content_table'));
			$es->add_relation('entity.name = "'.$table_name.'"');
			$results = $es->run_one();
			if(!empty($results))
			{
				trigger_error( 'Unable to create table ' . $table_name . ', which already exists.');
				return false;
			}
			else
			{
				$q = "CREATE TABLE ".$table_name." (id int unsigned primary key)" ;
				if(db_query( $q, 'Unable to create new table' ))
				{
					if(is_numeric($username))
						$user_id = $username;
					else
						$user_id = get_user_id($username);
					$id = reason_create_entity(id_of('master_admin'), id_of('content_table'), $user_id, $table_name, array('new' => 0));
					create_relationship( $type_id, $id, relationship_id_of('type_to_table'));
					// Trigger error on normal behavior? No thanks.
					//trigger_error( 'The table ' . $table_name . ' was created and added to the type ' . $type_unique_name);
					return $id;
				}
			}
		}
	}
		
	
	/**
	 * Create a new allowable relationship
	 *
	 * Checks to make sure we are not duplicating an existing allowable relationship before creating new one.
	 * 
	 * Checks include: 
	 * 1. the type ids must be the ids of existing reason types
	 * 2. the name must be a nonempty string containing only numbers, letters, and underscores that does not already exist in the allowable relationship table (exception: borrows and owns)
	 * 3. for borrows or owns relationships, the type must not already have an allowable relationship of that type
	 *
	 * @param integer $a_side_type_id The id of the type on the left side of the relationship
	 * @param integer $b_side_type_id The id of the type on the right side of the relationship
	 * @param string $name The unique name of the allowable relationship (or "owns" or "borrows")
	 * @param array $other_data Additional data to be stored in the allowable_relationship table, keyed by field name
	 * @return mixed id of newly created relationship if successful; false if failure
	 *
	 * @todo update to do verification and handling of new "type" field
	 */
	function create_allowable_relationship($a_side_type_id,$b_side_type_id,$name,$other_data = array())
	{
		// validate data
		
		$a_side_type_id = turn_into_int($a_side_type_id);
		$b_side_type_id = turn_into_int($b_side_type_id);
		$name = turn_into_string($name);
		
		if(empty($a_side_type_id))
		{
			trigger_error('$a_side_type_id must be a nonzero integer in create_allowable_relationship()');
			return false;
		}
		$a_ent = new entity($a_side_type_id);
		if(!empty($a_ent))
		{
			if($a_ent->get_value('type') != id_of('type'))
			{
				trigger_error('$a_side_type_id must be the ID of a Reason type entity');
				return false;
			}
		}
		else
		{
			trigger_error('$a_side_type_id is not the ID of a Reason entity');
			return false;
		}
		if(empty($b_side_type_id))
		{
			trigger_error('$b_side_type_id must be a nonzero integer in create_allowable_relationship()');
			return false;
		}
		$b_ent = new entity($b_side_type_id);
		if(!empty($b_ent))
		{
			if($b_ent->get_value('type') != id_of('type'))
			{
				trigger_error('$b_side_type_id must be the ID of a Reason type entity');
				return false;
			}
		}
		else
		{
			trigger_error('$b_side_type_id is not the ID of a Reason entity');
			return false;
		}
		if(empty($name))
		{
			trigger_error('$name must be a string in create_allowable_relationship()');
			return false;
		}
		if( !preg_match( "|^[0-9a-z_]*$|i" , $name ) )
		{
			trigger_error('$name must only contain numbers, letters, and underscores');
			return false;
		}
		
		if (!reason_relationship_names_are_unique())
		{
			$repeatable_names = array('borrows','owns');
			if( !in_array($name,$repeatable_names) && reason_relationship_name_exists($name, false) )
			{
				trigger_error('Unable to create allowable relationship named '.$name.' because there is already an allowable relationship with that name in Reason');
				return false;
			}
			if(in_array($name,$repeatable_names))
			{
				if($a_side_type_id != id_of('site'))
				{
					trigger_error('The a_side_type_id of borrows and owns relationships must be the id of the site type');
					return false;
				}
				// check to see if an owns/borrows relationship already exists for this type
				if ( (($name == 'owns') && get_owns_relationship_id($b_side_type_id)) ||
					 (($name == 'borrows') && get_borrows_relationship_id($b_side_type_id)) )
				{
					trigger_error($name.' relationship already exists between '.$a_side_type_id.' and '.$b_side_type_id.'.');
					return false;
				}
			}
		}
		else
		{
			if (reason_relationship_name_exists($name, false))
			{
				trigger_error('Unable to create allowable relationship named '.$name.' because there is already an allowable relationship with that name in Reason');
				return false;
			}
			if (isset($other_data['type']) && ( ($other_data['type'] == 'owns') || ($other_data['type'] == 'borrows') ) )
			{
				if ($a_side_type_id != id_of('site'))
				{
					trigger_error('The a_side_type_id of borrows and owns relationships must be the id of the site type');
					return false;
				}
				// enforce our naming convention
				$owns_name_should_be = $a_ent->get_value('unique_name') . '_owns_' . $b_ent->get_value('unique_name');
				$borrows_name_should_be = $a_ent->get_value('unique_name') . '_borrows_' . $b_ent->get_value('unique_name');
				if ( ($other_data['type'] == 'owns') && ($name != $owns_name_should_be) )
				{
					trigger_error('A new allowable relationship of type owns must follow the naming convention a_side_unique_name_owns_b_side_entity_unique_name');
					return false;
				}
				elseif ( ($other_data['type'] == 'borrows') && ($name != $borrows_name_should_be) )
				{
					trigger_error('A new allowable relationship of type borrows must follow the naming convention a_side_unique_name_borrows_b_side_entity_unique_name');
					return false;
				}
			}
			if (isset($other_data['type']) && ($other_data['type'] == 'archive'))
			{
				if ($a_side_type_id != $b_side_type_id)
				{
					trigger_error('The a_side_type_id and b_side_type_id of archive relationships must be the same.');
					return false;
				}
				$archive_name_should_be = $a_ent->get_value('unique_name') . '_archive';
				if ($name != $archive_name_should_be)
				{
					trigger_error('A new allowable relationship of type archive must follow the naming convention type_unique_name_archive');
					return false;
				}
			}
		}
		
		// do the creation of the allowable relationship
		
		$default_values = array(
								'directionality'=>'unidirectional',
								'is_sortable'=>'no',
								'connections'=>'many_to_many',
								'required'=>'no',
		);
		if (reason_relationship_names_are_unique($default_values['type'] = 'association'));
		$values = array_merge($default_values,$other_data);
		$values['relationship_a'] = $a_side_type_id;
		$values['relationship_b'] = $b_side_type_id;
		$values['name'] = $name;
		
		$sqler = new SQLER();
		if($sqler->insert('allowable_relationship',$values))
		{
			$insert_id = mysql_insert_id();
			reason_refresh_relationship_names();
			return $insert_id;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Change a property of an allowable relationship
	 *
	 * Note that changing relationship_a, relationship_b, name, or type is not permitted by this function.
	 *
	 * @param integer $rel_id
	 * @param array $values
	 * @return success
	 */
	function update_allowable_relationship($rel_id,$values = array())
	{
		if( isset($values['relationship_a']) || isset($values['relationship_b']) || isset($values['name']) || isset($values['type']) )
		{
			trigger_error('update_allowable_relationship does not allow for the change of left-side type, right-side type, name, or relationship type. Rejecting update.');
			return false;
		}
		$sqler = new SQLER();
		return $sqler->update_one('allowable_relationship',$values,$rel_id);
	}
	
	/**
	 * Creates the ownership, borrowing, and archive relationships that are necessary for each Reason type
	 *
	 * @param integer $type_id
	 * @return boolean (success)
	 */
	function create_default_rels_for_new_type($type_id)
	{
		if(empty($type_id))
		{
			trigger_error('Unable to create default relationships for type id '.$type_id.'; no type_id provided', HIGH);
			return false;
		}
		else $type = new entity($type_id);
		$values = $type->get_values();
		if (!reason_is_entity($type, 'type'))
		{
			trigger_error('Unable to create default relationships for type id '.$type_id.'; id does not correspond to a reason type entity.', HIGH);
			return false;
		}
		else $type_unique_name = $type->get_value('unique_name');
		if (func_num_args() > 1)
		{
			trigger_error('The unique name parameter of create_default_rels_for_new_type is deprecated - the type unique name is determined from the type entity itself');
		}
		if (reason_relationship_names_are_unique())
		{
			$owns_id = create_allowable_relationship(id_of('site'),$type_id,'site_owns_'.$type_unique_name,array('connections'=>'many_to_one','directionality'=>'unidirectional','required'=>'yes','is_sortable'=>'no','type'=>'owns'));
			$borrows_id = create_allowable_relationship(id_of('site'),$type_id,'site_borrows_'.$type_unique_name,array('connections'=>'many_to_many','directionality'=>'bidirectional','required'=>'no','is_sortable'=>'no','type'=>'borrows'));
			$archive_id = create_allowable_relationship($type_id,$type_id,$type_unique_name.'_archive',array('connections'=>'many_to_one','directionality'=>'unidirectional','required'=>'no','is_sortable'=>'no','type'=>'archive'));
		}
		else
		{
			$owns_id = create_allowable_relationship(id_of('site'),$type_id,'owns',array('connections'=>'many_to_one','directionality'=>'unidirectional','required'=>'yes','is_sortable'=>'no'));
			$borrows_id = create_allowable_relationship(id_of('site'),$type_id,'borrows',array('connections'=>'many_to_many','directionality'=>'bidirectional','required'=>'no','is_sortable'=>'no'));
			$archive_id = create_allowable_relationship($type_id,$type_id,$type_unique_name.'_archive',array('connections'=>'many_to_one','directionality'=>'unidirectional','required'=>'no','is_sortable'=>'no'));
		}
		if($owns_id && $borrows_id && $archive_id)
			return true;
		else
			return false;
	}
	
	/**
	 * Gets the default thumbnail dimensions for a given site
	 *
	 * @param integer $site_id
	 * @return array dimensions [format: array('width'=>123,'height'=>456)]
	 */
	function get_reason_thumbnail_dimensions($site_id = 0)
	{
		$ret = array('width'=>REASON_STANDARD_MAX_THUMBNAIL_WIDTH,'height'=> REASON_STANDARD_MAX_THUMBNAIL_HEIGHT);
		
		if(!empty($site_id))
		{
			$site = new entity($site_id);
			if(!empty($GLOBALS['_reason_site_custom_thumbnail_sizes'][$site->get_value('unique_name')]))
			{
				$dimensions = $GLOBALS['_reason_site_custom_thumbnail_sizes'][$site->get_value('unique_name')];
				if(!empty($dimensions['height']))
				{
					$ret['height'] = $dimensions['height'];
				}
				if(!empty($dimensions['width']))
				{
					$ret['width'] = $dimensions['width'];
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * Add a reason template entity
	 *
	 * Makes sure the template file exists and there is no existing template entity with the same name before creating entity
	 *
	 * @param string $template_name The name of the template file (not including .php)
	 * @return mixed Id of created template or false if failure
	 * @author Matt Ryan
	 * @since Reason 4.0 beta 4
	 */
	function reason_add_template($template_name)
	{
		if(!get_template_by_name($template_name))
		{
			if(reason_file_exists('minisite_templates/'.$template_name.'.php'))
			{
				return reason_create_entity( id_of('master_admin'), id_of('minisite_template'), $user_id, $template_name, array('new'=>0));
			}
			else
			{
				trigger_error('Template '.$template_name.' does not exist in filesystem; unable to be added');
				return false;
			}
		}
		else
		{
			trigger_error('Template '.$template_name.' already exists in db; unable to be added');
			return false;
		}
	}
	
	/**
	 * Examines the allowable relationships table, removes allowable relationships that reference a type that does not exist
	 *
	 * @todo database abstraction
	 * @todo move into a database maintenance class
	 * @return number of deleted allowable_relationships
	 * @author Nathan White
	 */
	function remove_allowable_relationships_with_missing_types()
	{
		$ids = get_allowable_relationships_with_missing_types();
		if (!empty($ids))
		{
			$ids_to_delete = implode(',', $ids);
			$q = 'DELETE from allowable_relationship WHERE id IN ('.$ids_to_delete.')';
			db_query($q);
			reason_refresh_relationship_names();
			return count($ids);
		}
		return 0;
	}
	
	function get_allowable_relationships_with_missing_types()
	{
		$es = new entity_selector();
		$es->limit_tables();
		$es->limit_fields(array('id', 'name'));
		$es->add_type(id_of('type'));
		$valid_types = $es->run_one('', 'All'); // look at all entities of all state
		$valid_type_ids = implode(',', array_keys($valid_types));
		
		$q = 'SELECT id from allowable_relationship WHERE ((relationship_a NOT IN ('.$valid_type_ids.')) OR
														   (relationship_b NOT IN ('.$valid_type_ids.')))';
		$results = db_query($q);
		while ($result = mysql_fetch_assoc($results))
		{
			$ids[] = $result['id'];
		}
		return (isset($ids)) ? $ids : array();
	}
	
	/**
	 * An orphaned relationship does not correspond to an allowable relationship in the allowable relationship table and should be deleted
	 * @todo move into a database maintenance class which separated detection from deletion
	 * @return number of deleted orphaned relationships
	 */
	function remove_orphaned_relationships()
	{
		$ids = get_orphaned_relationship_ids();
		if (count($ids) > 0)
		{
			$q = 'DELETE FROM relationship where `id` IN ("'.implode('","', $ids).'")';
			$result = db_query($q);
			return count($ids);
		}
		else return 0;
	}
	
	/**
	 * @return array of relationship ids that have a type that does not exist in the allowable relationships table
	 */
	function get_orphaned_relationship_ids()
	{
		$q = 'SELECT id from allowable_relationship';
		$results = db_query($q);
		while ($result = mysql_fetch_assoc($results))
		{
			$ids[$result['id']] = $result['id'];
		}
		if (isset($ids)) // only works if some distinct allowable_relationships existed
		{
			$allowable_rel_ids = implode('","', $ids);
			$q = 'SELECT id from relationship where `type` NOT IN ("'.$allowable_rel_ids.'")';
			$results = db_query($q);
			while ($result = mysql_fetch_assoc($results))
			{
				$orphan_ids[] = $result['id'];
			}
		}
		return (isset($orphan_ids)) ? $orphan_ids : array();
	}
	
	/**
	 * Move all the fields of one table into another table for a specific type
	 *
	 * This method is for denormalizing Reason tables. For example, a type may use a common table
	 * like meta, datetime, or chunk. For performance reasons, it can be desirable to collapse
	 * these tables into a single table just for that type. This method will do that.
	 * 
	 * @param integer $type The ID of the type whose fields we are moving
	 * @param string $source_table The name of the table we are moving fields FROM
	 * @param string $destination_table The name of the table we are moving fields TO
	 * @param integer $user_id The Reason ID of the user who is doing this move
	 * @return boolean Success
	 *
	 * @todo Add limit to ensure fields are only created that don't already exist
	 */
	function reason_move_table_fields($type, $source_table, $destination_table, $user_id)
	{
		// Sanity checks
		
		if(empty($type))
		{
			trigger_error('No type provided in reason_move_table_fields()');
			return false;
		}
		
		if(empty($source_table))
		{
			trigger_error('No source table provided in reason_move_table_fields()');
			return false;
		}
		
		if(!is_string($source_table))
		{
			trigger_error('Source table provided not a string in reason_move_table_fields()');
			return false;
		}
		
		if(empty($destination_table))
		{
			trigger_error('No destination table provided in reason_move_table_fields()');
			return false;
		}
		
		if(!is_string($destination_table))
		{
			trigger_error('Destination table provided not a string in reason_move_table_fields()');
			return false;
		}
		
		if('entity' == $source_table || 'entity' == $destination_table)
		{
			trigger_error('reason_move_table_fields() cannot move fields into or out of the entity table.');
			return false;
		}
		
		if(is_object($type))
		{
			$type_id = $type->id();
		}
		elseif(is_numeric($type))
		{
			$type_id = (integer) $type;
		}
		else
		{
			$type_id = id_of($type);
		}
		
		if(empty($type_id))
		{
			trigger_error('Invalid type specified in reason_move_table_fields().');
			return false;
		}
		
		if(is_object($type))
			$type_entity = $type;
		else
			$type_entity = new entity($type_id);
		
		$type_vals = $type_entity->get_values();
		if(empty($type_vals))
		{
			trigger_error('Type specified (id '.$type_id.') is not a Reason entity in reason_move_table_fields().');
			return false;
		}
		if($type_entity->get_value('type') != id_of('type'))
		{
			trigger_error('Type specified (id '.$type_id.') is not a Type entity in reason_move_table_fields().');
			return false;
		}
		if($type_entity->get_value('state') != 'Live')
		{
			trigger_error('Type specified (id '.$type_id.') is not a live entity in reason_move_table_fields().');
			return false;
		}
		
		if(empty($user_id))
		{
			trigger_error('No user id specified in reason_move_table_fields().');
			return false;
		}
		$user = new entity($user_id);
		if(!$user->get_values() || $user->get_value('type') != id_of('user'))
		{
			trigger_error('Invalid user ID specified in reason_move_table_fields().');
			return false;
		}
		
		// check for table existence
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('`name` = "'.addslashes($source_table).'"');
		$source_table_result = $es->run_one();
		if(empty($source_table_result))
		{
			trigger_error('Source table "'.$source_table.'" does not exist in reason_move_table_fields()');
			return false;
		}
		
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('`name` = "'.addslashes($destination_table).'"');
		$destination_table_result = $es->run_one();
		if(empty($destination_table_result))
		{
			trigger_error('Destination table "'.$destination_table.'" does not exist in reason_move_table_fields()');
			return false;
		}
		
		$source_table_entity = current($source_table_result);
		$destination_table_entity = current($destination_table_result);
		
		// ensure type uses both tables
		
		$type_tables = get_entity_tables_by_type( $type_id );
		
		if(!in_array($source_table, $type_tables))
		{
			trigger_error('Source table "'.$source_table.'" not part of the type in reason_move_table_fields()');
			return false;
		}
		
		if(!in_array($destination_table, $type_tables))
		{
			trigger_error('Destination table "'.$destination_table.'" not part of the type in reason_move_table_fields()');
			return false;
		}
		
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_left_relationship($destination_table_entity->id(),relationship_id_of('type_to_table'));
		$es->add_relation('`entity`.`id` != "'.addslashes($type_id).'"');
		$other_types = $es->run_one();
		
		if(!empty($other_types))
		{
			trigger_error(count($other_types).' other type(s) share the destination table with the type specified in reason_move_table_fields(). reason_move_table_fields() can only move fields into single-type tables.');
			return false;
		}
		
		// get the fields in the old table
		$es = new entity_selector();
		$es->add_type(id_of('field'));
		$es->add_left_relationship($source_table_entity->id(), relationship_id_of('field_to_entity_table'));
		$source_table_fields = $es->run_one();
		
		if(empty($source_table_fields))
		{
			trigger_error('Source table '.$source_table.' does not appear to have any fields associated with it in Reason. Unable to move its content in reason_move_table_fields()');
		}
		
		$q = 'DESCRIBE `'.addslashes($destination_table).'`';
		$handle = db_query( $q, 'Unable to describe destination table in reason_move_table_fields()' );
		$raw_dest_cols = array();
		while($row = mysql_fetch_assoc($handle))
		{
			$raw_dest_cols[] = $row['Field'];
		}
		
		
		foreach($source_table_fields as $k=>$field)
		{
			if(in_array($field->get_value('name'),$raw_dest_cols))
			{
				trigger_error($field->get_value('name').' field is already in destination table. Unable to accomplish reason_move_table_fields().');
				return false;
			}
			$tmp_field_name = $field->get_value('name').'_move_tmp';
			if(in_array($tmp_field_name,$raw_dest_cols))
			{
				trigger_error($tmp_field_name.' field already in destination table. There appears to have been an error in a previous attempt to run reason_move_table_fields(). Please drop this column in MySQL and try again.');
				return false;
			}
			$source_table_fields[$k]->set_value('_field_move_temp_name',$field->get_value('name').'_move_tmp');
		}
		
		// Done with sanity checks
		
		
		// map old to temp field names & create new fields
		$query_parts = array();
		foreach($source_table_fields as $k=>$field)
		{
			$source_table_fields[$k]->set_value('_field_move_temp_name',$field->get_value('name').'_move_tmp');
			$q = 'ALTER TABLE `'.addslashes($destination_table).'` ADD '.addslashes( $field->get_value('_field_move_temp_name') ).' '. $field->get_value('db_type');
			db_query( $q, 'Unable to create new field '.$field->get_value('_field_move_temp_name').' in reason_move_table_fields()' );
			$values = array();
			foreach($field->get_values() as $f=>$v)
			{
				if($f != 'name' && $f != 'id' && strpos($f,'_') !== 0)
				{
					$values[$f] = $v;
				}
			}
			$id = reason_create_entity( id_of('master_admin'), id_of('field'), $user_id, $field->get_value('_field_move_temp_name'), $values);
			$source_table_fields[$k]->set_value('_new_field_id',$id);
			$query_parts[] = '`'.addslashes($destination_table).'`.`'.addslashes($field->get_value('_field_move_temp_name')).'` = `'.addslashes($source_table).'`.`'.addslashes($field->get_value('name')).'`';
		}
		
		// copy content of old fields to new fields
		
		
		$q = 'UPDATE `'.addslashes($destination_table).'`, `'.addslashes($source_table).'`, `entity` SET '.implode(' , ',$query_parts).' WHERE `'.addslashes($destination_table).'`.`id` = `'.addslashes($source_table).'`.`id` AND `'.addslashes($destination_table).'`.`id` = `entity`.`id` AND `entity`.`type` = "'.addslashes($type_id).'";';
		
		db_query($q,'Attempt to move data between fields');
		
		
		// zap source table's type-to-table relationship for this type
		
		$conditions = array(
			'entity_a' => $type_id,
			'entity_b' => $source_table_entity->id(),
			'type' => relationship_id_of('type_to_table'),
		);
		
		delete_relationships( $conditions );
		
		// create new field-to-table relationship for new fields and update field names in new table -- remove temp flag
		
		foreach($source_table_fields as $field)
		{
			create_relationship( $field->get_value('_new_field_id'), $destination_table_entity->id(), relationship_id_of(	'field_to_entity_table' ) );
			$q = 'ALTER TABLE `'.addslashes($destination_table).'` CHANGE '.addslashes($field->get_value('_field_move_temp_name')).' '.addslashes( $field->get_value('name') ).' '.$field->get_value('db_type') ;
			db_query( $q, 'Unable to change field name of '.$field->get_value('_field_move_temp_name').' in reason_move_table_fields()' );
			reason_update_entity( $field->get_value('_new_field_id'), $user_id, array('name' => $field->get_value('name') ) );
		}
		
		// delete the rows from the source table
		
		$q = 'DELETE `'.addslashes($source_table).'` FROM `'.addslashes($source_table).'`, `entity` WHERE `'.addslashes($source_table).'`.`id` = `entity`.`id` AND `entity`.`type` = "'.addslashes($type_id).'"';
		
		db_query($q,'Attempt to delete rows from '.$source_table.' in reason_move_table_fields()');
		
		return true;
			
	}
?>
