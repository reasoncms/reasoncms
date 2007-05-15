<?php
/*
 * Edit Module
 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EditLinkModule';
	
	/*
	 * Module creates the login/logout and page editing links
	 */
	class EditLinkModule extends DefaultMinisiteModule
	{
		/*
		 * Class-level storage for the session object
		 */
		var $sess;
		/*
		 * Class-level storage for the username determined in is_reason_user()
		 */
		var $username;
		/*
		 * Tells the template that this module always contains content
		 */
		function has_content() // {{{
		{
			return true;
		} // }}}
		/*
		 * run the module
		 */
		function run() // {{{
		{
			$this->sess =& get_reason_session();
			if( $this->is_reason_user() )
			{
				$link = '/admin/index.php?site_id='. $this->site_id . '&amp;type_id=' . id_of( 'minisite_page' )
						. '&amp;id=' . $this->page_id . '&amp;cur_module=Editor'
						. '&amp;fromweb=' . $_SERVER[ 'REQUEST_URI' ];
				echo '<div class="editDiv">'."\n";
				echo prettify_string($this->username).': You may <a href="'.$link.'" class="editLink">edit this page</a>'."\n";
				echo '</div>';
			}
			// if they are not a backend user, check to see if this site is front end editable
			/* else
			{
				// check to see if this site has the Site User type
				$e = new entity( $this->site_id );
				$is_editable = $e->has_left_relation_with_entity( new entity(id_of( 'site_user_type' ) ) );
				if( $is_editable )
				{ */
					// check to see if this user is logged in
					echo '<p id="footerLoginLink">';
					if( $this->sess->exists() )
					{
						if( !HTTPS_AVAILABLE OR (!empty( $_SERVER['HTTPS'] ) AND strtolower( $_SERVER['HTTPS'] ) == 'on' ))
						{
							echo 'Logged in as '.$this->username.'. ';
							echo '<a href="'.REASON_LOGIN_URL.'?logout=1">Logout</a>';
						}
						// this should hopefully never happen since the template is now bouncing to HTTPS as needed
						else
						{
							$parts = parse_url( get_current_url() );
							$url = securest_available_protocol() . '://'.$parts['host'].$parts['path'].(!empty($parts['query']) ? '?'.$parts['query'] : '' );
							echo 'You are logged in but on an insecure page.  To edit, please go to <a href="'.$url.'">the secure page</a>';
						}
					}
					// otherwise, show login link
					else
					{
						echo '<a href="'.REASON_LOGIN_URL.'">Login</a>';
					}
					echo '</p>';
				/* }
			} */
		} // }}}
		/*
		 * run the module
		 * @return boolean true if the user should get a link to edit the site on the backend
		 */
		function is_reason_user() // {{{
		{
			$ret = false;
			if($this->sess->exists() && $this->sess->get('username'))
			{
				$this->username = $this->sess->get('username');
			}
			else
				$this->username = false;
			if( $this->username )
			{
				$ret = $this->get_sites_users();	
			}
			return $ret;
		} // }}}

		/*
		 * query the db to see if the current user has permission to edit the site
		 * @return boolean true if the user is a backend user of the current site
		 */
		function get_sites_users() // {{{
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'user' ) );
			$es->add_right_relationship( $this->site_id , relationship_id_of( 'site_to_user' ) );
			$es->add_relation('entity.name LIKE "'.addslashes($this->username).'"');
			$es->set_num(1);

			$users = $es->run_one();
			if(!empty($users))
			{
				return true;
			}
			
			return false;
		} // }}}
		function get_documentation()
		{
			return '<p>Provies a link to log in and log out of Reason. If you are logged in and have access to administer this site, this module provides a link to edit the current page.</p>';
		}
	}

?>
