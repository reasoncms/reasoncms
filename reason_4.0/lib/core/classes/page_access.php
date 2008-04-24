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
			$chain = $this->_page_tree->get_id_chain($page_id);
			$es = new entity_selector();
			$es->add_type(id_of('group_type'));
			$es->limit_tables();
			$es->limit_fields();
			$es->add_right_relationship($chain, relationship_id_of('page_to_access_group'));
			$es->set_num(count($chain));
			$this->_pages_to_groups[$page_id] = $es->run_one();
		}
		return $this->_pages_to_groups[$page_id];
	}
	/**
	 * Find out if a user has access to a given page
	 * @param string $username
	 * @param integer $page_id
	 * @return boolean username has access to view page
	 * @todo rather than doing a separate group membership check against each group, it should be possible to merge the ldap representations of each group into a single one and then run a single directory service request. This could improve performance when there are multiple groups involved.
	 */
	function has_access($username, $page_id)
	{
		if($groups = $this->get_groups($page_id))
		{
			foreach($groups as $group_id=>$group)
			{
				$gh = new group_helper();
				$gh->set_group_by_entity($group);
				if(!$gh->has_authorization($username))
					return false;
			}
		}
		return true;
	}
}
?>