<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Register module with Reason and include dependencies
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/user_functions.php' );
include_once (DISCO_INC . 'disco.php');
	
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PasswordChangeModule';

/**
 * A minisite module that provides an interface for users to change their Reason account password
 *
 * Note that this module is only for accounts that are not bound to another identity store, and
 * which rely on the other identity store for authentication.
 *
 * In other words, only users who have been set up with a password in the Reason database --
 * and use that password when logging in to Reason -- can change their password using this module.
 */
class PasswordChangeModule extends DefaultMinisiteModule
{
	var $user;
	
	function init()
	{
		$user_netid = check_authentication();
		$this->user = new entity(get_user_id($user_netid));
	}
	
	function run()
	{
		$pass_hash = $this->user->get_value('user_password_hash');
		if (empty($pass_hash))
		{
			echo '<p>You must be an active reason user with a password to change your reason password</p>';
		}
		else
		{
			$new_pass = $this->disco_HTML();
			if (!empty($new_pass))
			{
				if ($this->update_pass($new_pass))
				{
					echo '<p>Your password has been updated.</p>';
				}
				else
				{
					echo '<p>Your password was not updated.</p>';
				}
			}
		}
	}
	
	function disco_HTML()
	{
		$my_form = new changePasswordForm();
		$my_form->user_netid = $this->user->get_value('name');
		$my_form->pass_hash = $this->user->get_value('user_password_hash');
		$my_form->run();
		return $my_form->new_pass_hash;
	}
	
	function update_pass($new_pass)
	{
		$user_id = $this->user->id();
		return reason_update_entity($user_id, $user_id, array('user_password_hash' => $new_pass));
	}
}

class changePasswordForm extends Disco
{
	var $elements = array('old_pass' => array('type' => 'password', 'display_name' => 'Current Password'),
						  'new_pass' => array('type' => 'password', 'display_name' => 'New Password'),
						  'confirm_new_pass' => array('type' => 'password', 'display_name' => 'Confirm New Password'));
	var $user_netid;
	var $pass_hash;
	var $new_pass_hash = '';
	
	function pre_show_form()
	{
		echo '<p>You may use this form to update your password.</p>';
	}
	
	function run_error_checks()
	{
		$input_hash = sha1($this->get_value('old_pass'));
		$new_pass = $this->get_value('new_pass');
		$confirm_pass = $this->get_value('confirm_new_pass');
		if ($input_hash != $this->pass_hash) $this->set_error('old_pass', 'Your current password is incorrect');
		if (strlen($this->get_value('new_pass')) < 6) $this->set_error('new_pass', 'Your new password must be at least 6 characters');
		if ($new_pass != $confirm_pass) $this->set_error('confirm_new_pass', 'Your new password and the password confirmation field must be the same');
		if ($input_hash == sha1($this->get_value('new_pass'))) $this->set_error('new_pass', 'Your new password must be different than your old password');
	}
	
	function process()
	{
		$this->show_form = false;
		$this->new_pass_hash = sha1($this->get_value('new_pass'));
	}
}
?>