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
 * A user setting class for changing a user's phone number.
 *
 * @author Ben Cochran
 **/
class phoneSettingClass extends DefaultUserSettingClass
{	
	var $title = 'Editing Phone Number';
	
	function do_mapping($form)
	{
		$form->add_element('phone','text');
		$form->map('phone','user_phone');
		return $form;
	}

	function show_current_value_and_edit_link()
	{
		$array['title'] = 'Phone Number';
		$array['value'] = $this->user->get_value('user_phone');
		$array['link'] = 'phone';
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