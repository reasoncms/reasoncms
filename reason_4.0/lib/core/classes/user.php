<?php
/**
 * @package reason
 * @subpackage classes
 */
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/admin_actions.php');
reason_include_once( 'classes/entity_selector.php');

/**
* A class to handle site_to_user relationships.
*
* Common uses include:
*   - adding an array of users to a site - new reason users can optionally be created as needed
*   - removing an array of users from a site
*   - getting user entities currently related to a site
*   - getting site entities currently related to a user
*   - checking if a user is related to a site
*	- setting the primary_maintainer of a site
*
* Sample usage:
* <code>
* 	$user_manager = new User();
*   $user_manager->set_causal_agent('myapp_agent');
*   $user_manager->add_users_to_site(array('user1_netID', 'user2_netID', 'user3_netID'), $site_id));
* </code>
*
* A default $site_id can be specified with the set_site_id method - site_ids can also be passed directly into most methods
*
* A custom $causal_agent can be specified with the set_causal_agent method - the causal agent will be created as a reason user
* if needed. This functionality helps track what application used the class to modify user entities / relationships to a site.
* 
* The default $causal_agent "causal_agent" needs to be a valid reason user on any instance that uses this class.
*
* @author Nathan White
* 4-28-2006
*/

class user
{
	/**
	 * @var int $site_id default site_id to work with - optional field
	 */
	var $site_id;
	
	/**
	 * @var array $users user entities indexed by user_netID
	 * @access private use get_users_from_site function
	 */
	var $users = array();
	
	/**
	 * @var array $sites sites entities related to users (site_to_user relationship) indexed by user_netID
	 * @access private use get_user_sites function
	 */
	var $sites = array();
	
	/**
	 * @var string $causal_agent reason user who is the agent of change for user entities - must be a defined reason user
	 */
	var $causal_agent = 'causal_agent';
	
	function User()
	{
	}
	
	/**
	 * Returns user entity or false if the user doesn't exist
	 * @param string $user_netID
	 * @return object user entity or false if user doesn't exist
	 */
	function get_user($user_netID)
	{	
		if (!empty($this->users[$user_netID])) return $this->users[$user_netID];
		$es = new entity_selector();
		$es->add_type(id_of('user'));
		$es->limit_tables();
		$es->limit_fields();
		$es->add_relation('entity.name = "'.$user_netID.'"');
		$es->set_num(1);
		$result = $es->run_one();
		if (!empty($result))
		{
			$e = current($result);
			$this->users[$user_netID] = $e;
			return $e;
		}
		return false;
	}
	
	/**
	 * Sets the reason user who creates / modifies user entities
	 * @param string $user_netID
	 * @return mixed netID of causal agent or false
	 */
	function set_causal_agent($user_netID)
	{
		if ($this->get_user($user_netID))
		{
			$this->causal_agent = $user_netID;
		}
		else
		{
			$new_user = $this->create_user($user_netID);
			if ($new_user) $this->causal_agent = $new_user->get_value( 'name' );
			else return false;
		}
		return $this->causal_agent;
	}
	
	/**
	 * Sets a default site
	 * @param string $user_netID
	 * @return mixed $site_id or false
	 */
	function set_site_id($site_id)
	{
		if ($this->validate_site_id($site_id))
		{
			$this->site_id = $site_id;
			return $site_id;
		}
		return false;
	}
	
	/**
	 * Gets array of site entities to which a user is related (site_to_user)
	 * @param string $user_netID
	 * @param boolean $force_refresh default false
	 * @return array of site entities related to a user
	 */
	function get_user_sites($user_netID, $force_refresh = false)
	{
		if (!empty($this->sites[$user_netID]) && ($force_refresh == false))
		{
			return $this->sites[$user_netID]; //return from class variable
		}
		else	// get sites, updates, sites array, return user sites
		{
			if ($e = $this->get_user($user_netID))
			{
				$es = new entity_selector();
				$es->add_type(id_of('site'));
				$es->limit_tables();
				$es->limit_fields();
				$es->add_left_relationship($e->id(), relationship_id_of('site_to_user'));
				$result = $es->run_one();
				$this->sites[$user_netID] = $result;				
				return $this->sites[$user_netID];
			}
			return array(); // the user doesn't exist and does not have any sites
		}
	}
	
	/**
	 * Gets array of user entities that are current users of a site
	 * @param int $site_id
	 * @return array of user entities related to a site
	 */
	function get_users_from_site($site_id = '')
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if (!empty($site_id))
		{
			$es = new entity_selector();
			$es->add_type(id_of('user'));
			$es->add_right_relationship($site_id, relationship_id_of('site_to_user'));
			$users = $es->run_one(id_of('user'));
			return $users;
		}
		else 
		{
			trigger_error('get_users_from_site called on page ' . get_current_url() . ' with no site_id passed or set as a class variable');
			return array();
		}
	}
	
	/**
	 * Checks if a user is a user of a site
	 * @param string $user_netID
	 * @param int $site_id
	 * @param boolean $force_refresh default false forces update of sites array
	 * @return boolean
	 */
	function is_site_user($user_netID, $site_id = '', $force_refresh = false)
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if ($this->get_user($user_netID))
		{
			if (array_key_exists($site_id, $this->get_user_sites($user_netID, $force_refresh)) == true)
			{
				return true;
			}
			else return false;
		}
	}
	
	/**
	 * Create a new reason user when that user does not already exist
	 * @param string $user_netID
	 * @return mixed new user entity or false
	 */
	function create_user($user_netID)
	{
		if (empty($user_netID))
		{
			trigger_error('create_user called but user_netID was not provided - the user was not created');
			return false;
		}
		if ($this->get_user($user_netID) == false)
		{
			if ($causal_agent_entity = $this->get_user($this->causal_agent))
			{
				$eid = reason_create_entity(id_of('master_admin'), id_of('user'), $causal_agent_entity->id(), $user_netID, array('new'=>0));
				$e = new entity($eid);
				return $e;
			}
			else
			{
				trigger_error('create_user called on page ' . get_current_url() . ' but the causal agent ' . $this->causal_agent . ' is not a valid reason user');
				return false;
			}
		}
		else return false;
	}
	
	/**
	 * Creates a "site_to_user" relationship between a reason user and a site
	 * The function will create new reason users as needed, unless the create_flag is set to false
	 * This function uses is_site_user to verify that a site is actually of the site type
	 * 
	 * @param string $user_netID
	 * @param int $site_id
	 * @param boolean create_flag default true
	 * @param boolean validate_site_id_flag default true
	 * @return array site entities related to $user_netID
	 */
	function add_user_to_site($user_netID, $site_id = '', $create_flag = true, $validate_site_id_flag = true)
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if ($user = $this->get_user($user_netID))
		{
			if (($validate_site_id_flag == true) && (!$this->validate_site_id($site_id)))
			{
				trigger_error('add_user_to_site called on page ' . get_current_url() . ' called with an invalid site id');
				return false;
			}
			elseif (!$this->is_site_user($user_netID, $site_id, true)) // checks if user is part of site - forces refresh of data
			{
				create_relationship($site_id, $user->id(), relationship_id_of('site_to_user'));
				return $this->get_user_sites($user_netID, true); // force sites array to refresh with new association
			}
		}
		else
		{
			if ($create_flag == true)
			{
				if ($this->create_user($user_netID))
				{
					return $this->add_user_to_site($user_netID, $site_id, false); // same parameters but create_flag now false to avoid off chance of infinite recursion
				}
				else
				{
					trigger_error('add_user_to_site called on page ' . get_current_url() . ' but the user does not exist and could not be created');
				}
			}
		}
		return false; // default return
	}
	
	/**
	 * Adds multiple users to a site
	 * @uses function add_user_to_site()
	 * @param array $netIDs an array of form array('netid1', 'netid2', 'netid3')
	 * @param int $site_id id of the site to add users to
	 * @param boolean $create_flag whether reason users should be created as necessary default true
	 * @param boolean $validate_site_id_flag default true
	 * @return array subset of $this->sites with affected users
	 */
	function add_users_to_site($netIDs, $site_id = '', $create_flag = true, $validate_site_id_flag = true)
	{
		$affected = array();
		if (empty($site_id)) $site_id = $this->site_id;
		if (($validate_site_id_flag = true) && (!$this->validate_site_id($site_id))) // validate once instead of each iteration
		{
			trigger_error('add_users_to_site called on page ' . get_current_url() . ' called with an invalid site id');
			return false;
		}
		foreach ($netIDs as $user_netID)
		{
			if ($new_user_array = $this->add_user_to_site($user_netID, $site_id, $create_flag, false))
			{
				$affected[$user_netID] = $new_user_array;
			}
		}
		return $affected;
	}
	
	/**
	* Removes a "site_to_user" relationship between a reason user and a site
	* @param string $user_netID
	* @param int $site_id
	* @param boolean $validate_site_id_flag default true
	* @return array site entities related to $user_netID
	*/
	function remove_user_from_site($user_netID, $site_id = '', $validate_site_id_flag = true)
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if ($validate_site_id_flag = true) // validate once instead of each iteration
		{
			if (!$this->validate_site_id($site_id))
			{
				trigger_error('remove_user_from_site called on page ' . get_current_url() . ' called with an invalid site id');
				return false;
			}
		}
		if ($e = $this->get_user($user_netID))
		{
			delete_relationships(array('entity_a' => $site_id, 'entity_b' => $e->id(), 'type' => relationship_id_of('site_to_user')));
			$oldsites = $this->get_user_sites($user_netID, false);
			$newsites = $this->get_user_sites($user_netID, true);
			if (count($oldsites) > count($newsites))
			{
				$ret = (empty($newsites)) ? array('') : $newsites;
				return $ret;
			}
		}
		return false;
	}
	/**
	 * Removes multiple users from a site
	 * @uses function remove_user_from_site()
 	 * @param array $netIDs an array of form array('netid1', 'netid2', 'netid3')
	 * @param int $site_id
	 * @param boolean $validate_site_id_flag
	 * @return array subset of $this->sites with affected users
	 */
	function remove_users_from_site($netIDs, $site_id = '', $validate_site_id_flag = true)
	{
		$affected = array();
		if (empty($site_id)) $site_id = $this->site_id;
		if ($validate_site_id_flag = true)
		{
			if (!$this->validate_site_id($site_id)) // validate once instead of each iteration
			{
				trigger_error('remove_users_from_site called on page ' . get_current_url() . ' called with an invalid site id');
				return false;
			}
		}
		foreach ($netIDs as $user_netID)
		{
			if ($new_user_array = $this->remove_user_from_site($user_netID, $site_id, false))
			{
				$affected[$user_netID] = $new_user_array;
			}
		}
		return $affected;
	}
	
	/**
	 * checks to see if a provided site_id corresponds to an entity of type 'site'
	 * @param site_id
	 * @return boolean
	 */
	function validate_site_id($site_id = '')
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if (!empty($site_id))
		{
			$e = new entity($site_id);
			$e_type = new entity ($e->get_value( 'type' ));
			if ($e_type->get_value( 'name' ) != 'Site') return false;
			else return true;
		}
		return false;
	}
	
	/**
	 * gets the user entity of the primary maintainer of a site
	 * @param site_id
	 * @return mixed user entity of primary maintainer or false
 	 */
	function get_primary_maintainer($site_id = '')
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if ($this->validate_site_id($site_id))
		{
			$e = new entity($site_id);
			$primary_maintainer = $e->get_value( 'primary_maintainer' );
			return $this->get_user($primary_maintainer);
		}
		else 
		{
			trigger_error('get_primary_maintainer called on page ' . get_current_url() . ' with site_id parameter (' .$site_id. ') that does not correspond to an entity of type site');
			return false;
		}
	}

	/**
	 * Changes the primary maintainer of a site to the provided netID
	 * @param string $user_netID must be current user of the site
	 * @param int $site_id
	 * @return boolean
	 */
	function make_user_primary_maintainer($user_netID, $site_id = '')
	{
		if (empty($site_id)) $site_id = $this->site_id;
		if ($this->is_site_user($user_netID, $site_id, true))
		{
			$e = new entity($site_id);
			$primary_maintainer = $e->get_value( 'primary_maintainer' );
			if ($user_netID != $primary_maintainer)
			{
				$values = array ('primary_maintainer' => $user_netID);
           		if (reason_update_entity( $e->id(), get_user_id($this->causal_agent), $values, true) )
				{
					unset ($this->sites); // blow about sites array as primary_maintainer is no longer reliable
					return true;
				}
			}
		}
		return false;
	}
}
?>
