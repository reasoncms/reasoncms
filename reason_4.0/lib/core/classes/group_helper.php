<?php
	/**
	 * The group helper
	 * @package reason
	 * @subpackage classes
	 */
	 
	/**
	 * Include reason libraries
	 */ 
	include_once( 'reason_header.php' );
	/**
	 * Include the directory service
	 */ 
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	
	/**
	* A class to handle authorized groups and permissions.
	*
	* This class wraps up a group entity and provides a simple interface for determining who is a member of the Reason group
	*
	* Sample usage:
	*
	* <code>
	* $gh = new group_helper();
	* $gh->set_group_by_id($id_of_group);
	* if($gh->has_authorization($username))
	* {
	* 	echo $username.' is a part of this group';
	* }
	* </code>
	*
	* Created 2005-10-01
	* @author Matt Ryan & Meg Gibbs
	* @version 1.1 revised Nov. 2006 to add nobody group and full directory service support
	* @see reason_user_is_in_group() for an even simpler interface
	*/
	class group_helper
	{
		/**
		 * The group entity that this class wraps up
		 *
		 * Use either set_group_by_entity() or set_group_by_id() to set this value
		 * @access private
		 * @var object
		 */
		var $group;
		/**
		 * Array of audience entities associated with the group
		 * @var array
		 * @access private
		 */
		var $audiences = array();
		
		/**
		 * Array of fields to use when constructing the group representation
		 * @var array
		 * @access private
		 */
		var $group_fields = array( 	
			'authorized_usernames',
			'arbitrary_ldap_query',
			'ldap_group_filter',
		);
		
		/**
		 * Array of usernames which have been already checked for inclusion in group
		 *
		 * Structure of array:
		 * array('username'=>true,'otherusername'=>false)
		 * where true = is part of group & false = is not part of group
		 *
		 * @var array
		 * @access private
		 */
		var $permissions = array();
		
		/**
		 * An array that represents the group in directory service terms
	 	 * 
		 * The representation array may have any number of separate pieces.
		 * Each piece is an array with at least one of three (each optional) key-value pairs:
		 * <code>
		 * array(
		 * 	'directory_services'=>array('dir_service1','dir_service2','etc'),
		 * 	'filter'=>'(|(ds_username=username)',
		 * 	'group_filter'=>'(ds_groupname=groupname)',
		 * );
		 * </code>
		 *
		 * The directory_services value is an array of directory service names to use.
		 * The filter is an LDAP-style filter on people in the directory; people who are returned by this filter are part of the group.
		 * The group_filter is an LDAP-style filter on groups in the directory; people who are members of the groups returned by this filter are part of the group.
		 *
		 * @var array
		 */
		var $representation = array();
		
		/**
		 * Constructor
		 *
		 * @internal Leave this empty.
		 * @access public
		 */
		function group_helper()
		{
		}
		
		/**
		 * Initializes group helper by setting the group is to wrap up
		 * @param object $group_entity
		 * @access public
		 * @return void
		 */
		function set_group_by_entity($group_entity)
		{
			if(empty($this->group))
			{
				$this->group = $group_entity;
				$this->init_audiences($this->group->id());
			}
			else
			{
				trigger_error('Group already set on group helper');
			}
		}
		
		/**
		 * Initializes group helper by setting the group is to wrap up
		 * @param integer $group_id
		 * @access public
		 * @return void
		 */
		function set_group_by_id($group_id)
		{
			if(empty($this->group))
			{
				$this->group = new entity($group_id);
				$this->init_audiences($this->group->id());
			}
			else
			{
				trigger_error('Group already set on group helper');
			}
		}
		/**
		 * Grabs any audiences the group is related to and stores them on the class
		 * @param integer $group_id
		 * @access private
		 * @return void
		 */
		function init_audiences($group_id)
		{
			$es = new entity_selector();
			$es->add_right_relationship($group_id, relationship_id_of('group_to_audience'));
			$this->audiences = $es->run_one(id_of('audience_type'));
		}
		
		/**
		* Determines whether or not this group is defined to be empty
		* @access public
		* @return boolean true if group may have members; false if the group may not.
		*/		
		function group_has_members()
		{
			if($this->group->get_value('group_has_members') != 'false')
			{
				return true;
			}
			else
			{
				return false;
			}
		
		}
		
		/**
		* Determines whether or not this group requires authentication.
		* @access public
		* @return boolean true if group requires authentication; otherwise, returns false.
		*/			
		function requires_login()
		{
			if($this->group->get_value('require_authentication') == 'true')
				return true;
			else
				return false;
		}
		/**
		* Determines if a user is authorized
		*
		* @access public
		* @param string user's netID
		* @return boolean | NULL true if user is authorized; otherwise, returns false.
		*/			
		function has_authorization($user_netID, $assume_netid_is_in_directory = false)
		{
			if(empty($user_netID) && $this->requires_login())
			{
				return false;
			}
			return $this->is_username_member_of_group($user_netID, $assume_netid_is_in_directory);
		}
		
		/**
		* Helper function to has_authorization()
		*
		* If username given, will return true or false.
		*
		* If no username given, this will be interpreted as meaning "an anonymous user" and will
		* return true, false, or NULL. In this case, true indicates the group includes anybody; 
		* false indicates that it includes nobody; and NULL indicates that the group includes some
		* people and not others -- identification will be necessary to establish group membership.
		*
		* @access private
		* @param string $user_netID -- username. Use an empty string to determine if anonymous access is permitted
		* @return boolean | NULL true if user is a member of the authorized group, false if they are not, NULL if no username passed and access cannot be determined as a result
		*/			
		function is_username_member_of_group($user_netID, $assume_netid_is_in_directory = false)
		{
			if($this->group_has_members())
			{
				if(!$this->requires_login()) // if the group doesn't require login anyone is part of it
				{
					return true;
				}
				elseif(empty($user_netID)) // no id provided means that they are not logged in
				{
					return NULL;
				}
				elseif(array_key_exists($user_netID,$this->permissions)) // have we already determined whether this user has access to this group?
				{
					return $this->permissions[$user_netID];
				}
				// does this group represent a subset of people who can log in?
				elseif($this->group->get_value('limit_authorization') == 'true')
				{
					// build up an LDAP-style query
					
					$rep = $this->get_group_representation();
					$check_info = $this->add_netid_check_to_representation($user_netID,$rep);
					foreach($check_info as $dir_array)
					{
						if(!empty($dir_array['directory_services']))
						{
							$dir = new directory_service($dir_array['directory_services']);
						}
						else
						{
							$dir = new directory_service();
						}
						$dir->merge_results_off();
						if (!empty($dir_array['filter']) && $dir->search_by_filter($dir_array['filter']))
						{
							$members = $dir->get_records();
							if(!empty($members))
							{
								$this->permissions[$user_netID] = true;
								return true;
							}
						}
						if (!empty($dir_array['group_filter']) && $dir->group_search_by_filter($dir_array['group_filter']))
						{
							$groups = $dir->get_records();
							if(!empty($groups))
							{
								$this->permissions[$user_netID] = true;
								return true;
							}
						}
					}
					$this->permissions[$user_netID] = false;
					return false;
				}
				else // if we are not assuming the netid is in the directory check whether the person is in the directory service at all
				{
					if ($assume_netid_is_in_directory || (reason_check_authentication() == $user_netID)) // is authenticated user
					{
						$this->permissions[$user_netID] = true;
						return true;
					}
					else
					{
						if(!empty($dir_array['directory_services'])) $dir = new directory_service($dir_array['directory_services']);
						else $dir = new directory_service();
						$dir->search_by_filter('(ds_username='.ldap_escape($user_netID).')');
						$member = $dir->get_records();
						if (!empty($member))
						{
							$this->permissions[$user_netID] = true;
							return true;
						}
						else
						{
							$this->permissions[$user_netID] = false;
							return false;
						}
					}
				}
			}
			else // group doesn't have any members, so it always returns false
			{
				return false;
			}
	}

	/**
	 * Returns directory service records for a group. Use with caution - if a user sets up a group incorrectly this method could return a huge set of records.
	 *
	 * @param array optional array specifying which attributes are desired for the directory service records
	 *
	 * @author Nathan White
	 * @return array directory service records
	 * @access public
	 *
	 * @todo test with diverse groups - not well tested at this point but works at least for groups that are sets of authorized usernames
	 */
	function get_records_for_group($return_attr = array())
	{
		$filter = $this->get_group_representation();
		if (isset($filter['general']))
		{
			if (isset($filter['general']['group_filter']))
			{
				// get all the members of the group
				$group_filter = $filter['general']['group_filter'];
				$group_dir = new directory_service();
				$group_dir->group_search_by_filter($group_filter);
				$result = current($group_dir->get_records());
				if (!empty($result))
				{
					foreach($result['ds_member'] as $member) $str[] = '(ds_username='.trim($member).')';
					{
						if (!empty($str))
						{
							$filter['general']['filter'] = (isset($filter['general']['filter']))
														 ? '(|'.$filter['general']['filter'].implode('', $str).')'
														 : '(|'.implode('', $str).')';
						}
					}
				}
			}
			if (isset($filter['general']['filter']))
			{
				$dir = new directory_service();
				$dir->search_by_filter($filter['general']['filter'], $return_attr);
				$result = $dir->get_records();
				if (!empty($result)) return $result;
			}
		}
		else
		{
			trigger_error('get_records_for_group called on a group that does not appear to be filtered by meaningful criteria');
		}
		return false;
	}

	/**
	 * Returns directory service records ONLY for the authorized usernames field of a group.
	 *
	 * @param array optional array specifying which attributes are desired for the directory service records
	 * @author Nathan White
	 * @return array directory service records
	 * @access public
	 */
	function get_records_for_authorized_usernames_field($return_attr = array())
	{
		$authorized_usernames_block = $this->get_block_authorized_usernames();
		if (!empty($authorized_usernames_block))
		{
			$filter = '(|'.$authorized_usernames_block.')';
			$dir = new directory_service();
			$dir->search_by_filter($filter, $return_attr);
			$result = $dir->get_records();
			if (!empty($result)) return $result;
		}
		return false;
	}
	
	/**
	 * Returns the directory service-style array that represents the group
	 *
	 * See the class var $representation for documentation of this array
	 *
	 * @return array $representation
	 * @access public
	 */
	function get_group_representation()
	{
		if(empty($this->representation))
		{
			$this->representation = $this->build_group_representation();	
		}
		return $this->representation;
	}
	/**
	 * Puts together the directory service-style array that represents the group
	 *
	 * See the class var $representation for documentation of this array
	 *
	 * @return array $representation
	 * @access private
	 */
	function build_group_representation()
	{
		$general_filter_pieces = array();
		$general_group_filter_pieces = array();
		$filters = array();
		foreach($this->group_fields as $fieldname)
		{
			$method = 'get_block_'.$fieldname;
			$result = $this->$method();
			if(is_array($result))
			{
				if(!empty($result['directory_services']))
				{
					$filters[$fieldname] = $result;
				}
				else
				{
					if(!empty($result['filter']))
					{
						$general_filter_pieces[] = $result['filter'];
					}
					if(!empty($result['group_filter']))
					{
						$general_group_filter_pieces[] = $result['group_filter'];
					}
				}
			}
			elseif(!empty($result))
			{
				$general_filter_pieces[] = $result;
			}
		}
		
		foreach($this->audiences as $audience)
		{
			$aud_filter = '';
			if($audience->get_value('directory_service_value'))
			{
				$aud_filter = '(ds_affiliation='.$audience->get_value('directory_service_value').')';
			}
			if($audience->get_value('audience_filter'))
			{
				$aud_filter = '(|'.$aud_filter.$audience->get_value('audience_filter').')';
			}
			if(!empty($aud_filter))
			{
				if($audience->get_value('directory_service'))
				{
					$dir_services = explode(',',$audience->get_value('directory_service'));
					foreach($dir_services as $key=>$val)
					{
						$dir_services[$key] = trim($val);
					}
					// It seems we should ask reason in any case, since the audience exists in reason in the first place
					if(!in_array('reason',$dir_services))
					{
						$dir_services[] = 'reason';
					}
					$filters[$audience->get_value('unique_name')] = array('directory_services'=>$dir_services,'filter'=>$aud_filter);
				}
				else
				{
					$general_filter_pieces[] = $aud_filter;
				}
			}
		}
		if(!empty($general_filter_pieces) || !empty($general_group_filter_pieces))
		{
			$filters['general'] = array();
			if(!empty($general_filter_pieces))
			{
				if (count($general_filter_pieces) > 1)
					$filters['general']['filter'] = '(|'.implode('',$general_filter_pieces).')';
				else
					$filters['general']['filter'] = reset($general_filter_pieces);
			}
			if(!empty($general_group_filter_pieces))
			{
				if (count($general_group_filter_pieces) > 1)
					$filters['general']['group_filter'] = '(|'.implode('',$general_group_filter_pieces).')';
				else
					$filters['general']['group_filter'] = reset($general_group_filter_pieces);
			}
		}
		return $filters;
	}
	
	/**
	 * Takes a generic representation of the group and adds the username provided to 
	 * limit returned items to those matching the username
	 *
	 * This is useful for the is_username_member_of_group() method, which gets the representation, then adds the user limit with this method.
	 *
	 * @param string $user_netID The username we're going to test
	 * @param array $rep group representation
	 * @return string
	 * @access private
	 */
	function add_netid_check_to_representation($user_netID,$rep)
	{
		$user_netID = ldap_escape($user_netID);
		foreach($rep as $filter_key=>$filter_info)
		{
			if(!empty($filter_info['filter']))
			{
				$rep[$filter_key]['filter'] = '(&(ds_username='.$user_netID.')'.$filter_info['filter'].')';
			}
			if(!empty($filter_info['group_filter']))
			{
				if($this->group->get_value('ldap_group_member_fields'))
				{
					$fields = explode(',',$this->group->get_value('ldap_group_member_fields'));
					$member_chunk = '(|';
					foreach($fields as $field)
					{
						$field = trim($field);
						if(!empty($field))
						{
							$member_chunk .= '('.$field.'='.$user_netID.')';
						}
					}
					$member_chunk .= ')';
				}
				else
				{
					$member_chunk = '(ds_member='.$user_netID.')';
				}
				$rep[$filter_key]['group_filter'] = '(&'.$member_chunk.$filter_info['group_filter'].')';
			}
		}
		return $rep;
	}
	
	/**
	 * Assembles the directory service/LDAP-style block for the enumerated usernames field on the group
	 * @return string
	 */
	function get_block_authorized_usernames()
	{
		$usernames = explode(',', trim($this->group->get_value('authorized_usernames')));
		$string = '';
		foreach($usernames as $username)
		{
			$username = trim($username);
			if(!empty($username))
			{
				$string .= '(ds_username='.trim($username).')';
			}
		}
		if (count($usernames) > 1)
			$string = '(|'.$string.')';
		
		return $string;
	}
	/**
	 * Provides the directory service/LDAP-style block identified in the arbitrary LDAP query field on the group
	 * @return string
	 */
	function get_block_arbitrary_ldap_query()
	{
		return trim($this->group->get_value('arbitrary_ldap_query'));
	}
	
	/**
	 * Provides the directory service/LDAP-style block identified in the ldap_group_filter field
	 *
	 * This field contains a filter on groups rather than people; members of returned groups are mambers of the Reason group
	 *
	 * @return array
	 */
	function get_block_ldap_group_filter()
	{
		if($this->group->get_value('ldap_group_filter'))
		{
			$ret = array();
			$ret['group_filter'] = $this->group->get_value('ldap_group_filter');
			return $ret;
		}
		else
		{
			return false;
		}
	}
}