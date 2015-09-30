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
	 * Administrative module that zaps a user's current session
	 *
	 * This may be less important now that we have session-based authentication
	 * as logging out does essentially the same thing, but this can
	 * be useful if there is some weird state and you want to clear it up.
	 */
	class KillSessionModule extends DefaultModule// {{{
	{
		function KillSessionModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			session_unset();
			session_destroy();
			$link = 'index.php';
			if( !empty( $this->admin_page->user_id ) )
				$link .= '?user_id=' . $this->admin_page->user_id; 
			header( 'Location: ' . $link );
			die();
		} // }}}
		function run() // {{{
		{
		} // }}}
	} // }}}
?>