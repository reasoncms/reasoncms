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
 * A user setting class for changing a user's email address.
 *
 * @author Ben Cochran
 **/
class emailSettingClass extends DefaultUserSettingClass
{
	var $disco_form = 'email_setting_form.php';
	var $disco_class_name = 'emailSettingForm';
	var $title = 'Editing Email';
	
	function do_mapping($form)
	{
		$form->add_element('email','text');
		$form->map('email','user_email');
		return $form;
	}

	function show_current_value_and_edit_link()
	{
		$array['title'] = 'Email Address';
		$array['value'] = $this->user->get_value('user_email');
		$array['link'] = 'email';
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