<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module
 */
reason_include_once('classes/admin/modules/default.php');
include_once( DISCO_INC .'disco.php');
	
/**
 * Allows master admin user to easily grant themselves access to a site or pose as a user with access when confronted with access denied errors.
 *
 * @author Marcus Huderle
 * @author Nathan White
 */
class SiteAccessDeniedModule extends DefaultModule
{
	var $request;
	var $requested_site_id;
	var $users_with_access;
	var $can_pose_as_other_user = false;
	var $has_master_admin_edit_access = false;
	
	function SiteAccessDeniedModule( &$page )
	{
		$this->default_args[] = 'requested_url';
		$this->admin_page =& $page;
	}

	function init()
	{
		$this->request = carl_get_request();
		$this->admin_page->title = 'Access Denied';
				
		if ($requested_site_id = $this->get_requested_site_id())
		{
			// lets double check whether the user has access
			if (user_can_edit_site($this->admin_page->user_id, $requested_site_id))
			{
				header('Location: ' . urldecode($this->get_destination_url_with_user_id($this->admin_page->user_id)));
				exit;
			}
			else
			{ 
				$this->can_pose_as_other_user = reason_user_has_privs($this->admin_page->user_id, 'pose_as_other_user');
				$this->has_master_admin_edit_access = (user_can_edit_site($this->admin_page->user_id, id_of('master_admin')));
			}
		}
		else
		{
			$redirect = carl_make_redirect(array('cur_module' => '', 'requested_url' => '', 'site_id' => ''));
			header('Location: ' . $redirect);
			exit;
		}
	}
	
	function get_requested_site_id()
	{
		if (array_key_exists('requested_url', $this->request))
		{
			$parsed_url = parse_url($this->request['requested_url']);
			$query = $parsed_url['query'];
			$query_array = $this->convert_url_query($query);
			if (array_key_exists('site_id', $query_array))
			{
				return $query_array['site_id'];
			}
		}
		return false;
	}
	
	/**
	 * Returns the url query as associative array.  
	 * This code is taken from Simon D's comment at http://php.net/manual/en/function.parse-url.php.
	 *
	 * @param	string	query
	 * @return	array	params
	 */
	function convert_url_query($query) 
	{
		$queryParts = explode('&', $query);
		$params = array();
		foreach ($queryParts as $param)
		{
			$item = explode('=', $param);
			$params[$item[0]] = $item[1];
		}
		return $params;
	} 
	
	/**
	 * Returns the array query in a proper url query string format
	 *
	 * @param	array	query
	 * @return	string	query_string
	 */
	function convert_url_array($query) 
	{
		$query_string = '?';
		foreach ($query as $key => $val)
		{
			$query_string .= $key.'='.$val.'&';
		}
		$query_string = substr($query_string ,"",-1);
		return $query_string;
	}		
	
	
	function run()
	{	
		if ($this->requested_site_id = $this->get_requested_site_id())
		{
			$site_entity = new entity($this->requested_site_id);
			echo '<p>You do not have access to '.$site_entity->get_value('name').'.</p>'."\n";
		
			if ($this->has_master_admin_edit_access || ($this->can_pose_as_other_user && $this->get_users_with_access()))
			{
				$this->show_form();
			}
		}
	}
	
	/**
	 * @return mixed array of users with access OR boolean false
	 */
	function get_users_with_access()
	{
		if (!isset($this->users_with_access))
		{
			$es = new entity_selector();
			$es->add_type(id_of('user'));
			$es->add_right_relationship($this->requested_site_id, relationship_id_of('site_to_user'));
			$es->set_order('name ASC');
			$users = $es->run_one();
			
			$this->users_with_access = array();
			foreach ($users as $userid => $user)
			{
				$this->users_with_access[] = array('name' => $user->get_value('name'), 'id' => $user->id());
			}
			if (empty($this->users_with_access)) $this->users_with_access = FALSE;
		}
		return $this->users_with_access;
	}
	
	function build_usernames()
	{
		$names = array();
		foreach ($this->get_users_with_access() as $index => $pair)
		{
			$names[$index] = $pair['name'];
		}
		return $names;
	}
	
	function pre_show_form()
	{
		echo '<hr/><h3>Gain Access to the Site</h3>';
		echo '<p>You have appropriate privileges to gain access to the site. You may:</p>';
	}
	
	function show_form()
	{
		$form = new Disco();
		$form->strip_tags_from_user_input = true;
		
		if ($this->has_master_admin_edit_access)
		{
			$form->add_element('add_user', 'checkbox');
			$form->set_display_name('add_user', 'Add self to site');
		}
		
		if ($this->can_pose_as_other_user && $this->get_users_with_access())
		{
			if ($this->has_master_admin_edit_access)
			{
				$form->add_element('or_comment', 'comment', array('text' => '<strong>OR</strong>')); 
			}
			$form->add_element('user_list', 'select', array('options' => $this->build_usernames()));
			$form->set_display_name('user_list', 'Pose as a user with access');
		}
		
		$form->add_callback(array(&$this, 'pre_show_form'),'pre_show_form');
		$form->add_callback(array(&$this, 'process'),'process');
		$form->add_callback(array(&$this, 'where_to'), 'where_to');
		$form->run();
	}
	
	function process(&$disco)
	{
		// Create a new relationship between user and site
		if ($this->has_master_admin_edit_access && ($disco->get_value('add_user') == true))
		{
			create_relationship($this->requested_site_id, $this->admin_page->user_id, relationship_id_of('site_to_user'));
		}
		
		if ($this->can_pose_as_other_user && $this->get_users_with_access())
		{
			$a = $disco->get_value('user_list');
			$users = $this->get_users_with_access();
			if (array_key_exists($a, $users))
			{				
				// reconstruct the requested_url's query string to include (or change) the new
				// user_id that the user will be posing as
				$pose_user_id = $users[$a]['id'];
				$this->request['requested_url'] = $this->get_destination_url_with_user_id($pose_user_id);
			}
		}
	}
	
	function get_destination_url_with_user_id($user_id)
	{
		$base_url = urldecode($this->request['requested_url']);	
		$base_url_parsed = parse_url($base_url);
		$base_url_query = $base_url_parsed['query'];
		$base_url_query_array = $this->convert_url_query($base_url_query);
		$base_url_query_array['user_id'] = $user_id;
		$new_query_string = $this->convert_url_array($base_url_query_array);
		$query_index = strpos($base_url, '?');
		$new_url = substr($base_url, 0, $query_index);
		$new_url .= $new_query_string;
		return urlencode($new_url);
	}
	
	function where_to(&$disco)
	{
		return urldecode($this->request['requested_url']);
	}
}
?>