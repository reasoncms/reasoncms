<?php
/**
 * A class that encapsulates page-level access controls in Reason
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
reason_include_once('classes/group_helper.php');
reason_include_once('classes/entity_selector.php');

/**
 * A class to enable page-level access controls in Reason
 *
 * Sample usage:
 *
 * $auth_username = reason_check_authentication();
 * $rpa = new reasonPageAccess();
 * $rpa->set_page_tree($this->pages);
 * if(!$rpa->has_access($auth_username,$this->page_id))
 * {
 * 	if(!empty($auth_username))
 * 		// 403
 * 	else
 * 		// force login
 * }
 * else
 * 	// OK to view page
 */
class reasonPageAccess
{
	var $_page_tree;
	var $_pages_to_groups = array();
	var $_group_helpers = array();
	
	/**
	 * provide a page tree object (e.g. minisite navigation)
	 * @param object $page_tree
	 * @return void
	 */
	function set_page_tree(&$page_tree)
	{
		$this->_page_tree =& $page_tree;
	}
	/**
	 * Get the groups that apply to a given page
	 * @param integer $page_id
	 * @return array of group objects
	 */
	function get_groups($page_id)
	{
		if(!isset($this->_pages_to_groups[$page_id]))
		{
			$alrel_id = relationship_id_of('page_to_access_group');
			if(!$alrel_id)
			{
				trigger_error('page_to_access_group needs to be added. Please upgrade your database at '.REASON_HTTP_BASE_PATH.'scripts/upgrade/4.0b6_to_4.0b7/');
				return array();
			}
			$chain = $this->_page_tree->get_id_chain($page_id);
			if(empty($chain))
			{
				trigger_error('Page '.$page_id.'does not appear to be in site.');
				$this->_pages_to_groups[$page_id] = array();
			}
			else
			{
				$es = new entity_selector();
				$es->add_type(id_of('group_type'));
				$es->limit_tables();
				$es->limit_fields();
				$es->add_right_relationship($chain, $alrel_id);
				$es->set_num(count($chain));
				$this->_pages_to_groups[$page_id] = $es->run_one();
			}
		}
		return $this->_pages_to_groups[$page_id];
	}
	/**
	 * Find out if a user has access to a given page
	 * 
	 * We also determine if the anonymous user has access so that modules know whether or not a page is public.
	 *
	 * @param string $username
	 * @param integer $page_id
	 * @return boolean username has access to view page
	 * @todo Look into merging group representations to reduce number of dir service queries
	 */
	function has_access($username, $page_id)
	{
		if($groups = $this->get_groups($page_id))
		{
			foreach($groups as $group_id => $group)
			{
				if (!isset($this->_group_helpers[$id]))
				{
					$this->_group_helpers[$id] = new group_helper();
					$this->_group_helpers[$id]->set_group_by_entity($group);
				}
				if(!$this->_group_helpers[$id]->has_authorization($username))
					return false;
			}
		}
		return true;
	}
}
?>