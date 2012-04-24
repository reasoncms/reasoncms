<?php
/**
 * Class that encapsulates entity locks
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once( CARL_UTIL_INC.'db/db_selector.php');

/*

Places to indicate and/or apply locks:

Core:

Admin interface:

Listers: indicate items that are entirely locked to current user with color lock; indicate items that have at least one lock applied to lock managers with grey transparent lock; respect & communicate state locks on deleted lister (done in default lister; still need to go through other listers)

Content managers: find custom-implemented relationship fields and check locks
Found so far:
news.php (publication & section relationship fields)

History: Lock history items that have diffs on any locked fields (done)

Associators, Reverse Associators: Lock all items if the current item is locked on this rel for this user. Note that this relationship is locked with grey icon if cur user is a lock manager. Lock items on other sides of relationships (e.g. both selecting and deselecting) if they are locked for this user. Note that lock exists for these items if user is lock administrator. (done)
Also note that we should lock sorting if rel is locked. (done)

Batch delete: respect state lock; note state lock for lock admins (done)

Cloner: respect relationsip locks???

Delete: Respect state field locks for current user; notify lock admins about lock existence (done)

doAssociate: Respect locks (done)

doBorrow: Respect locks on borrow relationships -- or should we leave borrow rels out, as we already control them with no_share?

doDissassociate: Respect locks (done)

Content Manager:Indicate locks for lock admins (grey) & for those currently affected by them (color). Respect in process phase or error check phase to ensure that these values do not change even if request is forged.

Expunge: Respect state field locks for current user (done); notify lock admins about lock existence (done)

No Delete: Add lock explanation (done)

Preview: add lock indicators for current user (color) and for lock admins (grey) (done)

Sort Posts: Respect locks on date field (done)

Undelete: Respect state locks (done)

Modules:

Content: Respect (done) & communicate content field lock for current user; communicate locked status to lock admins

Blurb: Respect (done) & communicate content field lock for current user; communicate locked status to lock admins

Other stuff:
Expungement -- delete locks as well (avoid cruft!) (done)

Move entities among sites (done)

Saving and deletion of locks (done)

Admin Page: Communicate locks on 1st level (done); communicate locks on second level


Local:

Modules:

club Sports: Respect (done) & communicate content field lock for current user; communicate locked status to lock admins

Other things to do:

Complete lock administration interface (done)

Complete documentation of class


*/

/**
 * Class that encapsulates locks on fields and relationships
 *
 * Most read access handled through the entity class itself.
 *
 * Programmers should only need to interact directly with the locks object
 * if they need to add or delete locks.
 *
 * @author Matt Ryan
 * @todo add logging to lock removal operations
 */
class ReasonEntityLocks
{
	/**
	 * The entity this locks object refers to. This is set in the constructor.
	 */
	protected $_entity;
	
	/**
	 * The raw locks as a 2d associative array, precisely as drawn from the database. 
	 *
	 * This variable is lazy-loaded, so it is best to access it via _get_raw_locks().
	 *
	 * Example contents:
	 * <code>
	 * array(
	 *	0 => array(
	 *		'id' => 1,
	 *		'entity_id' => '1234',
	 *		'field_name' => 'datetime', // Empty string, field name or * for all fields
	 *		'allowable_relationship_id' => '', // Empty string, allowable_relationship id, or -1 for all relationships
	 *		'allowable_relationship_direction' => '', // Empty string if for field; "left" or "right" if for relationship(s)
	 *		'date_last_edited' => '2011-02-04',
	 *		'last_edited_by' => '34', // Reason user id
	 *	),
	 *	1 => array (
	 * 		...
	 *	),
	 *	...
	 * )
	 * </code>
	 */
	protected static $_raw_locks = array();
	
	/**
	 * The current user entity.
	 *
	 * This variable is lazy-loaded, so it is best to access it via _get_current_user().
	 */
	protected $_current_user;
	
	protected $_context_site;
	
	/**
	 * Constructor for the class
	 *
	 * @param object $entity
	 * @return void
	 */
	function __construct($entity)
	{
		if(empty($entity) || !is_object($entity))
			trigger_error('ReasonEntityLocks must be constructed with a Reason entity');
		else
			$this->_entity = $entity;
	}
	
	/**
	 * Get all raw locks for the entity
	 *
	 * @return array of lock rows, each row an array of key => value
	 */
	protected function _get_raw_locks()
	{
		if(!isset(self::$_raw_locks[$this->_entity->id()]))
		{
			$this->_refresh_locks_cache();
		}
		return self::$_raw_locks[$this->_entity->id()];
	}
	
	/**
	 * Set up locks functionality in Reason
	 *
	 * This method creates the appropriate table(s) in Reason
	 * so that locks functionality can work.
	 *
	 * @return boolean success
	 */
	public function enable_locks()
	{
		static $lock_table_exists = false;
		if(!$lock_table_exists)
		{
			$q = 'CREATE TABLE IF NOT EXISTS `entity_lock` (
 				`id` int(10) unsigned NOT NULL auto_increment,
 				`entity_id` int(10) unsigned NOT NULL,
 				`field_name` tinytext NOT NULL,
 				`allowable_relationship_id` int(11) NOT NULL,
 				`allowable_relationship_direction` enum(\'left\',\'right\') default NULL,
 				`date_last_edited` timestamp NOT NULL,
 				`last_edited_by` int(10) unsigned NOT NULL,
 				PRIMARY KEY (`id`),
 				KEY `entity_id` (`entity_id`)
			) TYPE=MyISAM;';
			if(db_query($q, 'Error creating entity_lock table'))
				$locks_table_exists = true;
		}
		return $locks_table_exists;
	}
	
	/**
	 * Refresh the in-memory cache of lock information
	 *
	 * @return void
	 */
	protected function _refresh_locks_cache()
	{
		$dbs = new DBSelector();
		$dbs->add_table('entity_lock');
		$dbs->add_relation('`entity_id` = "'.addslashes($this->_entity->id()).'"');
		self::$_raw_locks[$this->_entity->id()] = $dbs->run('Error getting locks for entity '.$this->_entity->id().'.', false);
	}
	
	/**
	 * Get all locks pertaining to fields on the entity
	 *
	 * @return array of lock rows, each row an array of key => value
	 */
	public function get_field_locks()
	{
		$field_locks = array();
		foreach($this->_get_raw_locks() as $lock)
		{
			if(!empty($lock['field_name']))
				$field_locks[] = $lock;
		}
		return $field_locks;
	}
	
	/**
	 * Get the "all fields lock" row, if it exists for the entity
	 *
	 * @return mixed Associative array of column_name => value if present; otherwise NULL
	 */
	public function get_all_fields_lock()
	{
		foreach($this->get_field_locks() as $lock)
		{
			if('*' == $lock['field_name'])
				return $this->_all_fields_lock = $lock;
		}
		return null;
	}
	
	/**
	 * Get the row that locks a given field, if one exists for the entity
	 *
	 * If all fields are locked on the entity with an "All fields" lock,
	 * that lock will be returned instead.
	 *
	 * @param string $field_name
	 * @return array of lock rows, each row an array of key => value
	 */
	public function get_field_lock($field_name)
	{
		foreach($this->get_field_locks() as $lock)
		{
			if('*' == $lock['field_name'] || $field_name == $lock['field_name'])
				return $lock;
		}
		return null;
	}
	
	/**
	 * Get all relationship locks in a given direction for the entity
	 *
	 * If all relationships in that direction are locked on the entity 
	 * with an "All relationships" lock, that lock will be returned instead.
	 *
	 * @param mixed $direction NULL, 'left', or 'right' (NULL will return both left and right relationship locks)
	 * @return array Associative array of column_name => value
	 */
	public function get_relationship_locks($direction = null)
	{
		$ret = array();
		foreach($this->_get_raw_locks() as $lock)
		{
			if(!empty($lock['allowable_relationship_id']) && ( empty($direction) || $direction == $lock['allowable_relationship_direction']) )
				$ret[$lock['id']] = $lock;
		}
		return $ret;
	}
	
	/**
	 * Get the "all relationships lock" row, if it exists in the requested direction for the entity
	 *
	 * @param string $direction 'left' or 'right'
	 * @return mixed Associative array of column_name => value if present; otherwise NULL
	 */
	public function get_all_relationships_lock($direction)
	{
		foreach($this->get_relationship_locks($direction) as $lock)
		{
			if('-1' == $lock['allowable_relationship_id'] && $direction == $lock['allowable_relationship_direction'])
				return $lock;
		}
		return null;
	}
	
	/**
	 * Get a relationship lock for a given relationship and direction, if one exists
	 *
	 * @param mixed $relationship (integer) allowable relationship id or (string) allowable relationshiup name
	 * @param string $direction 'left' or 'right'
	 * @return array Associative array of column_name => value if found; otherwise NULL
	 */
	public function get_relationship_lock($relationship, $direction)
	{
		if(is_numeric($relationship))
			$relationship_id = (integer) $relationship;
		else
			$relationship_id = relationship_id_of($relationship);
		
		if(empty($relationship_id))
		{
			trigger_error('Relationship "'.$relationship.'" not found in get_relationship_lock');
			return false;
		}
		foreach($this->get_relationship_locks($direction) as $lock)
		{
			if( ( '-1' == $lock['allowable_relationship_id'] || $relationship_id == $lock['allowable_relationship_id']) )
				return $lock;
		}
		return null;
	}
	
	/**
	 * Does the entity have any locks?
	 *
	 * return boolean
	 */
	public function has_lock()
	{
		$locks = $this->_get_raw_locks();
		return !empty($locks);
	}
	
	/**
	 * Does the entity have an "all fields" lock?
	 *
	 * @return boolean
	 */
	public function has_all_fields_lock()
	{
		$locks = $this->get_all_fields_lock();
		return !empty($locks);
	}
	
	/**
	 * Does the entity have an "all relationships" lock for the given direction?
	 *
	 * @param string $direction 'left' or 'right'
	 * @return boolean
	 */
	public function has_all_relationships_lock($direction)
	{
		$locks = $this->get_all_relationships_lock($direction);
		return !empty($locks);
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
	public function field_has_lock($field_name)
	{
		$lock = $this->get_field_lock($field_name);
		return !empty($lock);
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
	public function relationship_has_lock($relationship, $direction)
	{
		if($this->get_relationship_lock($relationship, $direction))
		{
			return true;
		}
		else
		{
			return false;
		}
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
	public function role_could_edit($role_name, $fields_or_rels = 'all')
	{
		static $cache = array('all'=>array(),'fields'=>array(),'relationships'=>array(),);
		
		if(!isset($cache[$fields_or_rels]))
		{
			trigger_error('2nd parameter of role_could_edit must be one of: "'.implode('", "',array_keys($cache)).'". Given "'.$fields_or_rels.'"; setting to "all".' );
			$fields_or_rels = 'all';
		}
		
		if(isset($cache[$fields_or_rels][$this->_entity->id()][$role_name]))
			return $cache[$fields_or_rels][$this->_entity->id()][$role_name];
		
		if(
		( $this->_entity->get_value('state') == 'Live' && reason_role_has_privs( $role_name, 'edit') )
		||
		( $this->_entity->get_value('state') == 'Pending' && reason_role_has_privs( $role_name, 'edit_pending') )
		||
		( $this->_entity->get_value('state') == 'Deleted' && ( reason_role_has_privs( $role_name, 'publish') || reason_role_has_privs( $role_name, 'expunge') ) )
		)
		{
			$owner = $this->_entity->get_owner();
			
			if(!reason_site_can_edit_type($owner, $this->_entity->get_value('type')))
				return $cache[$fields_or_rels][$this->_entity->id()][$role_name] = false;
				
			if(reason_role_has_privs( $role_name, 'bypass_locks'))
				return $cache[$fields_or_rels][$this->_entity->id()][$role_name] = true;
			
			switch($fields_or_rels)
			{
				case 'all':
					return $cache[$fields_or_rels][$this->_entity->id()][$role_name] = !( $this->get_all_fields_lock() && $this->get_all_relationships_lock('left') && $this->get_all_relationships_lock('right') );
				case 'fields':
					return $cache[$fields_or_rels][$this->_entity->id()][$role_name] = !$this->get_all_fields_lock();
				case 'relationships':
					return $cache[$fields_or_rels][$this->_entity->id()][$role_name] = !( $this->get_all_relationships_lock('left') && $this->get_all_relationships_lock('right') );
				default:
					trigger_error('Programming error: $fields_or_rels not an acceptable value');
					return false;
			}
		}
		else // all other states are uneditable
		{
			return $cache[$fields_or_rels][$this->_entity->id()][$role_name] = false;
		}
	}
	
	/**
	 * Could any one of the given roles edit this entity?
	 *
	 * Note that without a given user, this function cannot check
	 * site membership or other important aspects of privilege-granting.
	 * Therefore, this method should only be used for informational
	 * purposes, not to grant privileges, unless other checks are done.
	 *
	 * @param array $roles Array of role unique name strings
	 * @param string $fields_or_rels Which types of locks to consider? -- 'all', 'fields', or 'relationships'
	 * @return boolean
	 */
	protected function _one_of_roles_could_edit($roles, $fields_or_rels = 'all')
	{
		foreach($roles as $role)
		{
			if( $this->role_could_edit($role, $fields_or_rels) )
				return true;
		}
		return false;
	}
	
	/**
	 * Could the given role edit the given field on this entity?
	 *
	 * Note that without a given user, this function cannot check
	 * site membership or other important aspects of privilege-granting.
	 * Therefore, this method should only be used for informational
	 * purposes, not to grant privileges, unless other checks are done.
	 *
	 * @param string $field_name Name of field
	 * @param string $role_name Unique name of role
	 * @return boolean
	 */
	public function role_could_edit_field($field_name, $role_name)
	{
		if( $this->role_could_edit($role_name) && !$this->get_field_lock($field_name) )
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Could the given role edit the given relationship on this entity?
	 *
	 * Note that without a given user, this function cannot check
	 * site membership or other important aspects of privilege-granting.
	 * Therefore, this method should only be used for informational
	 * purposes, not to grant privileges, unless other checks are done.
	 *
	 * @param mixed $relationship ID  or name of allowable relationship
	 * @param string $role_name Unique name of role
	 * @param string $direction 'left' or 'right'
	 * @return boolean
	 */
	public function role_could_edit_relationship($relationship, $role_name, $direction)
	{
		if(empty($direction) || ($direction != 'left' && $direction != 'right') )
		{
			trigger_error('Direction required in role_could_edit_relationship -- please provide either "left" or right" as 3rd parameter.');
			return false;
		}
		
		if( $this->role_could_edit($role_name) )
		{
			if($this->get_all_relationships_lock($direction))
				return false;
			
			if($this->relationship_has_lock($relationship, $direction))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Can a given user edit at least one field or relationship 
	 * of this entity?
	 *
	 * @param mixed $user user entity or null for currently logged-in user
	 * @param string $fields_or_rels limit question to just fields or just relationships -- 'all', 'fields', or 'relationships'
	 * @return boolean
	 */
	public function user_can_edit($user = null, $fields_or_rels = 'all')
	{
		static $cache = array('all'=>array(),'fields'=>array(),'relationships'=>array(),);
		
		if(!isset($cache[$fields_or_rels]))
		{
			trigger_error('2nd parameter of user_can_edit must be one of: "'.implode('", "',array_keys($cache)).'". Given "'.$fields_or_rels.'"; setting to "all".' );
			$fields_or_rels = 'all';
		}
		
		if(null === $user)
		{
			$user = $this->_get_current_user();
		}
		
		if(empty($user))
		{
			return false;
		}
		
		if(isset($cache[$fields_or_rels][$this->_entity->id()][$user->id()]))
		{
			return $cache[$fields_or_rels][$this->_entity->id()][$user->id()];
		}
		
		if(!isset($cache[$fields_or_rels][$this->_entity->id()]))
		{
			$cache[$fields_or_rels][$this->_entity->id()] = array();
		}
		
		if($this->_one_of_roles_could_edit(reason_user_roles($user->id()), $fields_or_rels))
		{
			$owner = $this->_entity->get_owner();
			
			if(user_can_edit_site($user->id(), $owner->id()))
			{
				return $cache[$fields_or_rels][$this->_entity->id()][$user->id()] = true;
			}
		}
		return $cache[$fields_or_rels][$this->_entity->id()][$user->id()] = false;
	}
	
	/**
	 * Can a given user edit a given field on this entity?
	 *
	 * @param string $field_name
	 * @param mixed $user A user entity or null for the currently-logged-in user
	 * @return boolean
	 */
	public function user_can_edit_field($field_name, $user = null)
	{
		if(empty($field_name))
		{
			trigger_error('No field name given');
			return false;
		}
		elseif(!in_array($field_name,$this->_entity->get_characteristics()))
		{
			trigger_error('Field requested not a valid field on this entity');
			return false;
		}
		if(null === $user)
			$user = $this->_get_current_user();
		if(!$this->user_can_edit($user))
			return false;
		
		if(reason_user_has_privs( $user->id(), 'bypass_locks'))
			return $cache[$this->_entity->id()][$user->id()] = true;
		
		if($this->get_field_lock($field_name))
			return false;
		
		return true;
	}
	
	/**
	 * Can a given user edit a given relationship on this entity?
	 *
	 * @param string $field_name
	 * @param mixed $user A user entity or null for the currently-logged-in user
	 * @param string $direction 'left' or 'right' -- 'left' if this entity is on the right side of the relationship, 'right' if it is on the left (e.g. on which side of the entity is the relationship on?)
	 *
	 * @return boolean
	 *
	 * @todo check to make sure the requested relationship a) exists, and b) is available on this
	 * type
	 */
	public function user_can_edit_relationship($relationship, $user = null, $direction, $entity_on_other_side =  null, $context_site = null)
	{
		if(empty($direction) || ($direction != 'left' && $direction != 'right') )
		{
			trigger_error('Direction required in role_could_edit_relationship -- please provide either "left" or right" as 3rd parameter.');
			return false;
		}
		
		if(empty($relationship))
		{
			trigger_error('No relationship given');
			return false;
		}
		
		if(null === $user)
			$user = $this->_get_current_user();
			
		if(empty($context_site))
			$context_site = $this->get_context_site();
		
		
		if($entity_on_other_side && !$this->_relationship_check($relationship, $user, $direction, $entity_on_other_side, $context_site))
			return false;
		
		if(reason_user_has_privs( $user->id(), 'bypass_locks'))
			return true;
		$other_direction = 'right' == $direction ? 'left' : 'right';
		if(
			$this->relationship_has_lock($relationship, $direction)
			|| ($entity_on_other_side && $entity_on_other_side->relationship_has_lock($relationship, $other_direction) )
		)
		{
			return false;
		}
		else
			return true;
	}
	
	function _relationship_check($relationship, $user = null, $direction, $entity_on_other_side = null, $context_site = null)
	{
		if(null === $user)
		{
			$user = $this->_get_current_user();
		}
		
		if(empty($user))
		{
			return false;
		}
		
		$this_entity_state = $this->_entity->get_value('state');
		$other_entity_state = $entity_on_other_side ? $entity_on_other_side>get_value('state') : null;
			
		// If one of the entities is deleted or archived, return false
		if('Deleted' == $this_entity_state || 'Deleted' == $other_entity_state || 'Archived' == $this_entity_state || 'Archived' == $other_entity_state)
		{
			// relationships not changeable on archived or deleted entities
			return false;
		}
		// If both entities are live and the user does not have live entity editing privs, return false
		elseif('Live' == $this_entity_state && ( empty($other_entity_state) || 'Live' == $other_entity_state ) )
		{
			if(!reason_user_has_privs( $user->id(), 'edit_live'))
				return false;
		}
		// If either entity is pending and the user does not have pending entity editing privs, return false
		elseif( 'Pending' == $this_entity_state || 'Pending' == $other_entity_state )
		{
			if(!reason_user_has_privs( $user->id(), 'edit_pending'))
				return false;
		}
		// This should never run, but in case there are any additions to the set of entity states this will catch it
		else
		{
			trigger_error('Uncaught state combination: '.$this_entity_state.' and '.$other_entity_state.'. Check logic.' );
			return false;
		}
		
		if(!$context_site)
		{
			$context_site_given = false;
			$context_site = $this->_find_context_site($entity_a,$entity_b,$user);
			$context_site_valid = !empty($context_site);
		}
		else
		{
			$context_site_given = true;
			$context_site_valid = ( $entity_a->is_owned_or_borrowed_by($context_site->id()) && $entity_b->is_owned_or_borrowed_by($context_site->id()) );
		}
		
		// if context site that the user has admin rights to that contains both entities
		if($context_site_valid)
		{
			// if rel is bidirectional or user is admin of A side owner site return true
			
			$alrel = reason_get_allowable_relationship_info( $alrel_id );
			if('bidirectional' == $alrel['directionality'])
			{
				return true;
			}
			else
			{
				if('right' == $direction)
					$a_side_owner_site = $this->_entity->get_owner();
				elseif($entity_on_other_side)
					$a_side_owner_site = $entity_on_other_side->get_owner();
				else
					return false;
				return user_can_edit_site($user->id(), $a_side_owner_site->id());
			}
		}
		else
		{
			if($context_site_given)
				$context_sites = array($context_site->id() => $context_site);
			else
				$context_sites = reason_user_sites($user);
			
			$rels = $this->_get_rels_between_entities($entity_a,$entity_b,$allowable_relationship_id,$context_sites);
			if(!empty($rels))
			{
				return true;
			}
		}
		
		return false;
	}
	
	function _find_context_site($entity_a,$entity_b,$user)
	{
		$a_borrow_id = get_borrow_relationship_id( $entity_a->get_value('type') );
		
		$b_borrow_id = get_borrow_relationship_id( $entity_a->get_value('type') );
	}
	
	function _verify_context_site($context_site, $entity_a, $entity_b)
	{
		return ($entity_a->is_owned_or_borrowed_by($context_site->id()) && $entity_b->is_owned_or_borrowed_by($context_site->id()));
	}
	function _get_rels_between_entities($entity_a,$entity_b,$allowable_relationship_id,$sites)
	{
		$site_ids = array(0) + array_keys($sites);
		array_walk($site_ids,'db_prep_walk');
		$q = 'SELECT * from `relationship` WHERE `entity_a` = "'.addslashes($entity_a->id()).'" AND `entity_b` = "'.addslashes($entity_b->id()).'" AND `type` = "'.addslashes($allowable_relationship_id).'" AND `site` IN ('.implode(',',$site_ids).')';
		$r = db_query( $q , 'error getting relationship info' );
		if($r)
			return mysql_fetch_array( $r , MYSQL_ASSOC );
		else
			return false;
	}
	
	/**
	 * Set a "current user" if other than the one who is logged in
	 * or if running via shell script/cron
	 * (e.g. no current user can be identified)
	 *
	 * @param object $user Reason user entity
	 * @return boolean success
	 */
	public function set_current_user($user)
	{
		if(is_numeric($user))
		{
			$user = new entity($user);
			if(!$user->get_values() || $user->get_value('type') != id_of('user') )
			{
				trigger_error('Invalid user id passed to set_current_user()');
				return false;
			}
		}
		elseif(!is_object($user))
		{
			trigger_error('Invalid user passed to set_current_user()');
			return false;
		}
		$this->_current_user = $user;
		return true;
	}
	
	/**
	 * Get the user entity for the current user
	 * (if they are logged in, if they have a Reason user for them).
	 * @return mixed user entity object if there is a logged-in user and if they have a reason user entity; otherwise boolean false
	 */
	protected function _get_current_user()
	{
		if(!isset($this->_current_user))
		{
			if($username = reason_check_authentication())
			{
				if($user_id = get_user_id( $username ))
					$this->_current_user = new entity($user_id);
				else
					$this->_current_user = false;
			}
			else
			{
				$this->_current_user = false;
			}
		}
		return $this->_current_user;
	}
	
	/* Writing to DB section starts here */
	
	/**
	 * Get the fields that can be locked
	 * @return array('field_name','field_name',...)
	 * @todo There should be a better way to identify non-lockable fields
	 */
	public function get_lockable_fields()
	{
		$entity_values = $this->_entity->get_values();
		unset($entity_values['id']);
		unset($entity_values['last_edited_by']);
		unset($entity_values['type']);
		unset($entity_values['last_modified']);
		unset($entity_values['new']);
		unset($entity_values['creation_date']);
		unset($entity_values['created_by']);
		if(isset($entity_values['sort_order']))
			unset($entity_values['sort_order']);
			
		return array_keys($entity_values);
	}
	
	/**
	 * Get the relationships that can be locked
	 * @return array('rel_id'=>array([alrel info]),'rel_id'=>array([alrel info]),...)
	 * @todo There should be a better way to identify non-lockable relationships
	 */
	public function get_lockable_relationships()
	{
		$rels = get_allowable_relationships_for_type($this->_entity->get_value('type'));
		$rel_unique_names = (reason_relationship_names_are_unique());
		foreach($rels as $rel_id=>$rel)
		{
			$not_lockable = ($rel_unique_names) 
						  ? ( ($rel['type'] == 'archive') || ($rel['type'] == 'borrows') ) 
						  : ( (strpos($rel['name'],'archive') !== false) || (strpos($rel['name'],'borrows') !== false) );
			if($not_lockable) unset($rels[$rel_id]);
		}
		return $rels;
	}
	
	/**
	 * Lock a given field
	 *
	 * Note that this method will return false in any case that does not
	 * result in the lock being added, including:
	 * 1. User does not have privs to do operation
	 * 2. Lock already exists
	 * 3. Field is not lockable
	 * 4. Database write failed
	 *
	 * @param string $field_name
	 * @return boolean lock added
	 */
	public function add_field_lock($field_name)
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to add field lock on entity '.$this->_entity->id().' by unauthorized user: '. ( empty($user) ? '[no authorized user found]' : $user->id() ) );
			return false;
		}
		
		// special case '*' for an all-field lock -- remove all other locks and institute all-field lock
		
		if(!$this->field_has_lock($field_name))
		{
			if('*' != $field_name && !in_array( $field_name, $this->get_lockable_fields() ) )
			{
				trigger_error('"'.$field_name.'" is not a field on entity id '.$this->_entity->id().'. Unable to lock.');
				return false;
			}
			
			if('*' == $field_name)
			{
				$this->remove_all_field_locks();
			}
			
			if($GLOBALS['sqler']->insert('entity_lock',array('entity_id' =>$this->_entity->id(),'field_name'=>$field_name,'last_edited_by'=>$user->id(),), false))
			{
				$this->_refresh_locks_cache();
				return true;
			}
		}
		return false;
	}
	/**
	 * Lock a given relationship/direction combination
	 *
	 * Note that this method will return false in any case that does not
	 * result in the lock being added, including:
	 * 1. User does not have privs to do operation
	 * 2. Lock already exists
	 * 3. Relationship is not lockable
	 * 4. Database write failed
	 *
	 * @param integer $relationship_id
	 * @param string $direction 'left' or 'right'
	 * @return boolean lock added
	 */
	public function add_relationship_lock($relationship_id, $direction)
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to add relationship lock on entity '.$this->_entity->id().' by unauthorized user: '.( empty($user) ? '[no authorized user found]' : $user->id() ) );
			return false;
		}
		
		// special case '*' (or -1?) for an all-relationship lock -- remove all other locks and institute all-relationship lock
		
		
		
		if(!$this->relationship_has_lock($relationship_id, $direction))
		{
			if( '-1' != $relationship_id && !array_key_exists( $relationship_id, $this->get_lockable_relationships() ) )
			{
				trigger_error('"'.$relationship_id.'" is not a lockable relationship on entity id '.$this->_entity->id().'. Unable to lock.');
				return false;
			}
			if($GLOBALS['sqler']->insert('entity_lock',array('entity_id' =>$this->_entity->id(),'allowable_relationship_id'=>$relationship_id,'allowable_relationship_direction'=>$direction,'last_edited_by'=>$user->id(),), false))
			{
				$this->_refresh_locks_cache();
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Remove the lock on a given field
	 *
	 * Note that this method will return false in any case that does not
	 * result in the lock being deleted, including:
	 * 1. User does not have privs to do operation
	 * 2. Lock does not exist
	 * 4. Database delete failed
	 *
	 * @param string $field_name
	 * @return boolean lock deleted
	 * @todo add logging
	 */
	public function remove_field_lock($field_name)
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to remove relationship lock on entity '.$this->_entity->id().' by unauthorized user: '.$user->id());
			return false;
		}
		
		if($this->field_has_lock($field_name))
		{
			$sql = 'DELETE FROM `entity_lock` WHERE `entity_id` = "'.addslashes($this->_entity->id()).'" AND `field_name` = "'.addslashes($field_name).'"';
			
			// execute
			if( db_query( $sql, 'Error removing field lock for entity '.$this->_entity->id(), false ) )
			{
				// log?
			
				// clear cache
				foreach(self::$_raw_locks[$this->_entity->id()] as $k=>$v)
				{
					if($v['field_name'] == $field_name)
					{
						unset(self::$_raw_locks[$this->_entity->id()][$k]);
					}
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Remove the relationship lock on a given relationship/direction combo
	 *
	 * Note that this method will return false in any case that does not
	 * result in the lock being deleted, including:
	 * 1. User does not have privs to do operation
	 * 2. Lock does not exist
	 * 4. Database delete failed
	 *
	 * @param integer $relationship_id
	 * @param string $direction 'left' or 'right'
	 * @return boolean lock deleted
	 * @todo add logging
	 */
	public function remove_relationship_lock($relationship_id, $direction)
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to remove relationship lock on entity '.$this->_entity->id().' by unauthorized user: '.$user->id());
			return false;
		}
		
		if($this->relationship_has_lock($relationship_id, $direction))
		{
			$sql = 'DELETE FROM `entity_lock` WHERE `entity_id` = "'.addslashes($this->_entity->id()).'" AND `allowable_relationship_id` = "'.addslashes($relationship_id).'" AND `allowable_relationship_direction` = "'.addslashes($direction).'"';
			
			// execute
			if( db_query( $sql, 'Error removing relationship lock for entity '.$this->_entity->id(), false ) )
			{
				// log?
			
				// clear cache
				foreach(self::$_raw_locks[$this->_entity->id()] as $k=>$v)
				{
					if($v['allowable_relationship_id'] == $relationship_id && $v['allowable_relationship_direction'] == $direction)
					{
						unset(self::$_raw_locks[$this->_entity->id()][$k]);
					}
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Remove all field locks from this entity
	 *
	 * Note that this method will return false in any case that does not
	 * result in the locks being deleted, including:
	 * 1. User does not have privs to do operation
	 * 2. Entity has no field locks to delete
	 * 4. Database delete failed
	 * 
	 * @return boolean locks deleted
	 * @todo add logging
	 */
	public function remove_all_field_locks()
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to remove all field locks on entity '.$this->_entity->id().' by unauthorized user: '.$user->id());
			return false;
		}
		
		$field_locks = $this->get_field_locks();
		
		if(!empty($field_locks))
		{
			$sql = 'DELETE FROM `entity_lock` WHERE `entity_id` = "'.addslashes($this->_entity->id()).'" AND `field_name` != ""';
			// execute
			if( db_query( $sql, 'Error removing all field locks for entity '.$this->_entity->id(), false ) )
			{
				// log?
			
				foreach(self::$_raw_locks[$this->_entity->id()] as $k=>$v)
				{
					if(!empty($v['field_name']))
					{
						unset(self::$_raw_locks[$this->_entity->id()][$k]);
					}
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Remove all relationship locks from this entity
	 *
	 * Note that this method will return false in any case that does not
	 * result in the locks being deleted, including:
	 * 1. User does not have privs to do operation
	 * 2. Entity has no relationship locks to delete
	 * 4. Database delete failed
	 * 
	 * @return boolean locks deleted
	 * @todo add logging
	 */
	public function remove_all_relationship_locks()
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to remove all relationship locks on entity '.$this->_entity->id().' by unauthorized user: '.$user->id());
			return false;
		}
		
		$rel_locks = $this->get_relationship_locks();
		
		if(!empty($rel_locks))
		{
			$sql = 'DELETE FROM `entity_lock` WHERE `entity_id` = "'.addslashes($this->_entity->id()).'" AND `allowable_relationship_id` != "0"';
			// execute
			if( db_query( $sql, 'Error removing all relationship locks for entity '.$this->_entity->id(), false ) )
			{
				// log?
			
				foreach(self::$_raw_locks[$this->_entity->id()] as $k=>$v)
				{
					if(!empty($v['allowable_relationship_id']))
					{
						unset(self::$_raw_locks[$this->_entity->id()][$k]);
					}
				}
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Remove all locks from this entity
	 *
	 * Note that this method will return false in any case that does not
	 * result in the locks being deleted, including:
	 * 1. User does not have privs to do operation
	 * 2. Entity has no locks to delete
	 * 4. Database delete failed
	 * 
	 * @return boolean locks deleted
	 * @todo add logging
	 */
	public function remove_all_locks()
	{
		$user = $this->_get_current_user();
		if(empty($user) || !reason_user_has_privs($user->id(),'manage_locks'))
		{
			trigger_error('Rejecting attempt to remove all locks on entity '.$this->_entity->id().' by unauthorized user: '.$user->id());
			return false;
		}
		
		$raw_locks = $this->_get_raw_locks();
		
		if(!empty($raw_locks))
		{
			$sql = 'DELETE FROM `entity_lock` WHERE `entity_id` = "'.addslashes($this->_entity->id()).'"';
			
			// execute
			if( db_query( $sql, 'Error removing all locks for entity '.$this->_entity->id(), false ) )
			{
				// log?
			
				self::$_raw_locks[$this->_entity->id()] = array();
				return true;
			}
		}
		return false;
	}
	
	public function set_context_site($site)
	{
		$this->_context_site = $site;
	}
	public function get_context_site()
	{
		return $this->_context_site;
	}
}
?>