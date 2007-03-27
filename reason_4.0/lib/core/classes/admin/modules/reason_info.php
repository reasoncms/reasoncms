<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
	class ReasonInfoModule extends DefaultModule// {{{
	{
		function ReasonInfoModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'About Reason';
			//$this->admin_page->set_breadcrumbs( array(''=> 'About Reason' ) );
		} // }}}
		function run() // {{{
		{
			?>	<!-- Updated 05/15/2003 by BK - Please do not change/update this without consulting with me first -->
				<p><strong>Reason</strong> is an attempt to create a broad and general way of managing database-driven websites.</p>
				<p>Its purpose is to make a user friendly editing environment which is as extensible as possible.</p>
				<p>If you have want to learn more about Reason, please visit the <a href="http://apps.carleton.edu/opensource/reason/">Reason website</a>.</p>
				<p class="smallText">Current Version: <?php echo REASON_VERSION; ?></p>
			<?php
		} // }}}
	} // }}}
?>