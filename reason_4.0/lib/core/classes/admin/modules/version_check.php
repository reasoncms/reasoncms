<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/version_check.php');
	
	/**
	 * Find out if the currently-running version is up to date
	 */
	class ReasonVersionCheckModule extends DefaultModule// {{{
	{
		function VersionCheckModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
		} // }}}
		function run() // {{{
		{
			$vc = new reasonVersionCheck;
			$resp = $vc->check();
			echo '<p>'.htmlspecialchars($resp['message'], ENT_QUOTES);
			if(!empty($resp['url']))
				echo ' <a href="'.htmlspecialchars($resp['url'], ENT_QUOTES).'">Link</a>';
			echo '<p>'."\n";
			echo '<p class="smallText">Current version: '.htmlspecialchars($vc->get_current_version_id(), ENT_QUOTES).'</p>'."\n";
		} // }}}
	} // }}}
?>