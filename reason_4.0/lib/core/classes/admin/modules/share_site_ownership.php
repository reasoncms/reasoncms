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
	class ShareSiteOwnershipModule extends DefaultModule // {{{
	{
		var $sites = array();
		var $user_id;
		var $new_user_id;
		var $user_sites = array();
		var $change_count = 0;
		
		function ShareSiteOwnershipModule( &$page ) // {{{
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
			$this->admin_page->title = 'Share Site Ownership';
			if (!empty($this->admin_page->request['share_user_id']))
			{
				$this->user_id = (int) $this->admin_page->request['share_user_id'];
				$this->user_sites = $this->get_user_sites($this->user_id);
			}
			$this->new_user_id = (!empty($this->admin_page->request['new_user_id'])) ? (int) $this->admin_page->request['new_user_id'] : '';
			if (!empty($this->admin_page->request['share_sites'])) 
			{
				$this->sites = $this->admin_page->request['share_sites'];
				if ($this->new_user_id)
				{
					$curr_sites = $this->get_user_sites($this->new_user_id);
					foreach ($this->sites as $site_id)
					{
						if (!isset($curr_sites[$site_id]))
						{
							create_relationship($site_id, $this->new_user_id, relationship_id_of('site_to_user'));
							$this->change_count++;
						}
					}
				}
			}
		} // }}}
		/**
		 * Lists the users who currently have access to the site
		 * 
		 * @return void
		 */
		function run() // {{{
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			{
				echo '<p>Sorry; use of this module is restricted.</p>'."\n";
				return;
			}
			echo '<p>Use this form to assign the set of sites owned by one user to another user.</p>';
			
			if ($this->change_count)
				echo '<p class="callOut">'.$this->change_count.' sites assigned.</p>';
			
			echo '<form method="post" name="userForm" id="userForm">'."\n";
			echo '<h4>Step One: Choose the Original Site Owner</h4>';
			echo '<label for="share_user_id">Site Owner</label>: ';
			echo '<select name="share_user_id" class="jumpDestination siteMenu" id="share_user_id"';
			echo ' onchange="document.forms[\'userForm\'].submit()">'."\n";
			echo '<option value="">--</option>'."\n";
			$users = $this->get_all_users();
			foreach( array_keys($users) AS $user_id )
			{
				echo '<option value="'. $user_id . '" ';
				if( $user_id == $this->user_id )
					echo ' selected="selected"';
				echo '>' . $users[$user_id]->get_value( 'name' ) . '</option>' . "\n";
			}
			echo '</select>';
			echo '</form>';
			
			if (!empty($this->user_id) && !empty($this->user_sites))
			{
				echo '<h4>Step Two: Choose the Sites to be Assigned to the New Owner</h4>';
				echo '<form method="post" name="siteForm" id="siteForm">'."\n";
				echo '<input type="hidden" name="share_user_id" value="'.$this->user_id.'" />';
				echo '<ul style="list-style:none">'."\n";
				foreach($this->user_sites as $site_id => $site)
				{
					echo '<li><input type="checkbox" name="share_sites[]" value="'.$site_id.'"';
					if (empty($this->sites) || isset($this->sites[$site_id]))
						echo ' checked="checked"';
					echo '/> '.$site->get_value('name').'</li>'."\n";
				}
				echo '</ul>'."\n";
				echo '<h4>Step Three: Choose the New Owner</h4>';
				echo '<label for="new_user_id">New Site Owner</label>: ';
				echo '<select name="new_user_id" class="jumpDestination siteMenu" id="new_user_id">'."\n";
				echo '<option value="">--</option>'."\n";
				foreach( array_keys($users) AS $user_id )
				{
					echo '<option value="'. $user_id . '" ';
					if( $user_id == $this->new_user_id )
						echo ' selected="selected"';
					echo '>' . $users[$user_id]->get_value( 'name' ) . '</option>' . "\n";
				}
				echo '</select>';
				echo '<p><input type="submit" name="submit" value="Assign Sites" /></p>';
				echo '</form>';
			} else if (!empty($this->user_id)) {
				echo '<p>This user does not manage any sites.</p>';		
			}
		} // }}}
		
		function get_all_users()
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'user' ) );
			$es->set_order( 'name ASC' );
			$es->limit_tables();
			$es->limit_fields('name');
			return $es->run_one();			
		}
		
		
		function get_user_sites($userid)
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship($userid, relationship_id_of('site_to_user'));
			return $es->run_one();
		}
	} // }}}
?>
