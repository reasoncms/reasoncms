<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once(DISCO_INC.'disco.php');
	
	/**
	 * This module allows the user to pose as another usehow_logout = !isset($_SERVER['REMOTE_USER']);
	 */
	class UserPosingModule extends DefaultModule// {{{
	{
		function UserPosingModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Pose as User';
		} // }}}
		function run() // {{{
		{
			$user_netid = reason_require_authentication();
			$es = new entity_selector();
			$es->add_type( id_of( 'user' ) );
			$es->add_relation('`name` = "'.$user_netid.'"');
			$net_user = current($es->run_one());
			$userid = $net_user->id();
			$user = new entity( $this->admin_page->user_id );
			$users = $this->get_users($userid);
			if( !empty($users) )
			{
				$opts = array();
				foreach($users as $uid => $u)
				{
					$opts[$uid] = $u->get_value('name');
				}
				$d = new disco();
				$d->add_element('pose_as','chosen_select',array('options'=>$opts));
				if(isset($opts[$this->admin_page->user_id]))
					$d->set_value('pose_as',$this->admin_page->user_id);
				$d->set_actions( array('Pose') );
				$d->add_callback(array($this,'where_to'),'where_to');
				$d->run();

            }
            else
            {
				echo 'You are <strong>' . reason_htmlspecialchars($user->get_value( 'name' )) .'</strong>';
            }

		} // }}}
	 // }}}
	 function get_users($userid)
	 {
	 	static $users = array();
	 	if(isset($users[$userid]))
	 		return $users[$userid];
	 	$users[$userid] = array();
	 	if( reason_user_has_privs($userid, 'pose_as_other_user' ) || reason_user_has_privs($userid, 'pose_as_non_admin_user' ) )
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'user' ) );
			$es->set_order( 'name ASC' );
			$es->limit_tables();
			$es->limit_fields('name');
			if(!reason_user_has_privs($userid, 'pose_as_other_user' ))
			{
				$roles_es = new entity_selector();
				$roles_es->add_type( id_of( 'user_role' ) );
				$roles_es->add_relation('`unique_name` != "admin_role"');
				$roles = $roles_es->run_one();
				$es->add_left_relationship(array_keys($roles),relationship_id_of('user_to_user_role'));
			}
			$users[$userid] = $es->run_one();
		}
		return $users[$userid];
	 }
	 function where_to($disco)
	 {
	 	$pose_id = (integer) $disco->get_value('pose_as');
	 	if($pose_id)
	 	{
	 		$link_array = array('user_id'=>$pose_id);
	 		if(!empty( $this->admin_page->request['return_module'] ))
	 			$link_array ['cur_module'] = $this->admin_page->request['return_module'];
	 		
	 		return $this->admin_page->make_link($link_array, false, false);
	 	}
	 }
	}
?>
