<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * An administrative module that reveals the session data
	 *
	 * This is potentially useful for debugging purposes.
	 */
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