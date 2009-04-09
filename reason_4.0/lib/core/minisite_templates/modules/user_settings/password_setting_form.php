<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
include_once (DISCO_INC . 'disco.php');

/**
 * A user setting form to change a user's password. Check their against
 * their current password and requires a new password to be 6 characters.
 *
 * @author Ben Cochran
 **/
class passwordSettingForm extends Disco
{
	var $elements = array('old_pass' => array('type' => 'password', 'display_name' => 'Current Password'),
						  'new_pass' => array('type' => 'password', 'display_name' => 'New Password'),
						  'confirm_new_pass' => array('type' => 'password', 'display_name' => 'Confirm New Password'));
	var $entity;
	var $pass_hash;
	var $new_pass_hash = '';
	var $completed = false;
	var $success;
	
	/**
	 * We want to check for the following:
	 *  1. The input for 'old password' is correct
	 *  2. The new password is at least 6 characters long
	 *  3. The new password and the confirmation password must match
	 *  4. The new password must be different from the old password
	 *
	 * @return void
	 **/
	function run_error_checks()
	{
		$this->pass_hash = $this->entity->get_value('user_password_hash');
		
		$input_hash = sha1($this->get_value('old_pass'));
		$new_pass = $this->get_value('new_pass');
		$confirm_pass = $this->get_value('confirm_new_pass');
		if ($input_hash != $this->pass_hash) $this->set_error('old_pass', 'Your current password is incorrect');
		if (strlen($this->get_value('new_pass')) < 6) $this->set_error('new_pass', 'Your new password must be at least 6 characters');
		if ($new_pass != $confirm_pass) $this->set_error('confirm_new_pass', 'Your new password and the password confirmation field must be the same');
		if ($input_hash == sha1($this->get_value('new_pass'))) $this->set_error('new_pass', 'Your new password must be different than your old password');
	}
	
	/**
	 * Updates the entity and hides the form
	 **/
	function process()
	{
		$this->show_form = false;
		$this->new_pass_hash = sha1($this->get_value('new_pass'));
		
		if (!empty($this->new_pass_hash))
		{
			if ($this->update_pass($this->new_pass_hash))
			{
				$this->success = true;
			}
			else
			{
				$this->success = false;
			}
		}
		$this->completed = true;
	}
	
	function update_pass($new_pass)
	{
		$user_id = $this->entity->id();
		return reason_update_entity($user_id, $user_id, array('user_password_hash' => $new_pass));
	}
	
	function where_to()
	{
		return make_link(array('user_setting'=> ''));
	}
}
?>