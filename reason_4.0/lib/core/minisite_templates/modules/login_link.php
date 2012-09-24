<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent class and dependencies, and register module with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'classes/inline_editing.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EditLinkModule';
	
	/**
	 * A module that displays a login/logout button and an "edit this page" link for 
	 * logged-in site maintainers
	 */
	class EditLinkModule extends DefaultMinisiteModule
	{
		var $login_url;
		var $user_netid;
		
		var $cleanup_rules = array('inline_editing_availability' => array('function' => 'check_against_array', 'extra_args' => array('enable', 'disable')));
		
		/**
		 * Tells the template that this module always contains content
		 */
		function has_content() // {{{
		{
			return true;
		}
		
		/**
		 * Check to see if user requested inline editing to be off or on - if so - perform the change and redirect back to the page
		 *
		 * @todo consider making this standalone - it occurs after navigation has been determined and many modules inited -
		 *       not a huge deal since enabling / disabled inline editing is not done very often and an extra page hit does not come
		 *       at a high cost, but it is not the most efficient way to do things.
		 */
		function init( $args = array() ) 
		{
			$this->user_netid = reason_check_authentication();
			if (isset($this->request['inline_editing_availability'])) $this->set_inline_editing_availability();
		}
		
		/**
		 * Set the inline editing status in the session and redirect
		 */
		function set_inline_editing_availability()
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			if ($this->request['inline_editing_availability'] == 'enable') $inline_edit->enable();
			else $inline_edit->disable();
			$redirect = carl_make_redirect(array('inline_editing_availability' => ''));
            header('Location: ' . $redirect);
            exit();
		}
		
		function get_edit_link()
		{
			$type_id = id_of('minisite_page');
			$qs = carl_construct_query_string(array('site_id' => $this->site_id, 'type_id' => $type_id, 'id' => $this->page_id, 'cur_module' => 'Editor', 'fromweb' => get_current_url()));
			return securest_available_protocol() . '://' . REASON_WEB_ADMIN_PATH . $qs;	
		}
		
		/**
		 * @return boolean
		 */
		function has_admin_edit_privs()
		{
			return (reason_check_access_to_site($this->site_id) && reason_check_privs('edit'));
		}
		
		function get_user_netid()
		{
			if (!isset($this->user_netid))
			{
				$this->user_netid = reason_check_authentication();
			}
			return $this->user_netid;
		}
		
		function get_login_url()
		{
			if (!isset($this->login_url))
			{
				$this->login_url = REASON_LOGIN_URL;
			}
			return $this->login_url;
		}
		
		/*
		 * Output appropriate HTML according to the users login state and access privileges
		 */
		function run() // {{{
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			if ($this->has_admin_edit_privs())
			{
				echo '<div class="editDiv">'."\n";
				echo '<a href="'.$this->get_edit_link().'" class="editLink">Edit page in Reason Admin</a>'."\n";
				echo '</div>';
			}
			if ($inline_edit->reason_allows_inline_editing() && ($inline_edit->is_available() || $inline_edit->is_enabled()))
			{
				if ($inline_edit->is_enabled())
				{
					$link =  carl_make_link(array('inline_editing_availability' => 'disable'));
					$link_text = 'Turn off inline editing';
				}
				else
				{
					$link = carl_make_link(array('inline_editing_availability' => 'enable'));
					$link_text = 'Turn on inline editing';
				}
				echo '<div class="inlineEditDiv">'."\n";
				echo '<a href="'.$link.'" class="inlineEditLink">'.$link_text.'</a>'."\n";
				echo '</div>';
			}
			echo '<p id="footerLoginLink">';
			echo ($this->get_user_netid()) ? '<span class="username">' . $this->get_user_netid() . '</span>: ' : '';
			echo ($this->get_user_netid()) ? '<a href="'. $this->get_login_url().'?logout=1">Logout</a>' : '<a href="'.$this->get_login_url().'">Login</a>';
			echo '</p>';
		}

		function get_documentation()
		{
			return '<p>Provides a link to log in and log out of Reason. If you are logged in and have access to administer this site, this module provides a link to edit the current page.</p>';
		}
	}

?>
