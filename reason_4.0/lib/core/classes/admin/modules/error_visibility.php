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
	 * This module allows the user to turn on & off error reporting
	 */
	class ErrorVisibilityModule extends DefaultModule// {{{
	{
		function ErrorVisibilityModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Toggle Error Visibility';
		} // }}}
		function run() // {{{
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			{
				echo '<p>Sorry; use of this module is restricted.</p>'."\n";
				return;
			}
			
			if(!empty($this->admin_page->request['error_reporting_state']))
			{
				switch($this->admin_page->request['error_reporting_state'])
				{
					case 'on':
						override_developer_status(true);
						break;
					case 'off':
						override_developer_status(false);
						break;
				}
			}
			
			$status = is_developer();
			
			echo '<form method="post" action="'.htmlspecialchars(get_current_url()).'">';
			if($status)
			{
				echo '<h3>In-page error reporting is currently ON.</h3>'."\n";
				echo '<input type="submit" value="Turn Error Reporting Off" /><input type="hidden" name="error_reporting_state" value="off" />';
			}
			else
			{
				echo '<h3>In-page error reporting is currently OFF.</h3>'."\n";
				echo '<input type="submit" value="Turn Error Reporting On" /><input type="hidden" name="error_reporting_state" value="on" />';
			}
			echo '</form>';
			echo '<p>Note: changes made via this form only last for the duration of the current session.</p>';
		} // }}}
	} // }}}
?>