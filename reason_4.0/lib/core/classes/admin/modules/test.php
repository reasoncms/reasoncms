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
	 * A demo "hello world" administrative module
	 */
	class TestModule extends DefaultModule// {{{
	{
		function TestModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'HELLO WORLD!!!!!!!!!';
		} // }}}
		function run() // {{{
		{
			echo '<h4>HEELO WORLD</h4>';
		} // }}}
	} // }}}
?>