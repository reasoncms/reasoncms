<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
include_once('default_setting_class.php');

/**
 * A user setting class for changing a user's password.
 *
 * @author Ben Cochran
 **/
class passwordSettingClass extends DefaultUserSettingClass
{
	// We use a non-default disco form for this setting.
	var $disco_form = 'password_setting_form.php';
	var $disco_class_name = 'passwordSettingForm';
	var $title = 'Changing Password';

	function do_mapping($form)
	{
		return $form;
	}

	function show_current_value_and_edit_link()
	{
		$array['title'] = 'Password';
		// Showing their password would be silly, we'll use "--" instead.
		$array['value'] = '--';
		$array['link'] = 'password';
		return $array;
	}
	
	/**
	 * Checks that a user's authoritative source is reason before allowing
	 * this setting to be changed.
	 *
	 * @return boolean
	 **/
	function conditional_run_function()
	{
		if ($this->user->get_value('user_authoritative_source') == 'reason')
			return true;
		else
			return false;
	}
}