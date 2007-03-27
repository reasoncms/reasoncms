<?php
	reason_include_once('classes/admin/modules/default.php');
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