<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );

	/**
	 * Theme choosing module for backend
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
				echo '<p>Please contact the <a href="/campus/webgroup/">Web Services Group</a> to add or remove users from this list.</p>'."\n";
			}
		} // }}}
	} // }}}
?>
