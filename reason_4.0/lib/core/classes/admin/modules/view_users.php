<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');

	/**
	 * An administrative module that lists the users of the current site
	 *
	 * @todo add full names rather than just usernames
	 */
	class ViewUsersModule extends DefaultModule // {{{
	{
		var $users = array();
		var $site;
		
		function ViewUsersModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * Sets up page variables and runs the entity selctor that grabs the users
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			parent::init();
			$this->site = new entity( $this->admin_page->site_id );
			$this->admin_page->title = 'Users with administrative access to '.$this->site->get_value('name');
			$es = new entity_selector();
			$es->add_right_relationship($this->admin_page->site_id, relationship_id_of('site_to_user'));
			$this->users = $es->run_one(id_of('user'));
		} // }}}
		/**
		 * Lists the users who currently have access to the site
		 * 
		 * @return void
		 */
		function run() // {{{
		{
			if(empty($this->users))
			{
				echo '<p>No users currently have access to '.$this->site->get_value('name').'</p>'."\n";
			}
			else
			{
				echo '<p>The following users have access to '.$this->site->get_value('name').':</p>'."\n";
				echo '<ul>'."\n";
				foreach($this->users as $user)
				{
					echo '<li>'.$user->get_value('name').'</li>'."\n";
				}
				echo '</ul>'."\n";
				if( user_can_edit_site($this->admin_page->user_id, id_of('master_admin') ) )
				{
					echo '<p><a href="';
					echo $this->admin_page->make_link( array(
						'site_id'=>id_of('master_admin'),
						'type_id'=>id_of('site'),
						'id'=>$this->site->id(),
						'cur_module'=>'Associator',
						'rel_id'=>relationship_id_of('site_to_user'),
					) );
					echo '">Add or remove individuals from this list</a></p>'."\n";
				}
				else
				{
					echo '<p>Please contact '.REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS.' to add or remove users from this list.</p>'."\n";
				}
			}
		} // }}}
	} // }}}
?>
