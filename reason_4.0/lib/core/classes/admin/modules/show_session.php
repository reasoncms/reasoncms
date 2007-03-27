<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
	class ShowSessionModule extends DefaultModule// {{{
	{
		function ShowSessionModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
		} // }}}
		function run() // {{{
		{
			pray( $_SESSION );
		} // }}}
	} // }}}
?>