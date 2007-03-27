<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
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