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
 * A user setting class for changing a user's given name and surname.
 *
 * @author Ben Cochran
 **/
class nameSettingClass extends DefaultUserSettingClass
{
	var $title = 'Editing Name';
	
	function do_mapping($form)
	{
		$form->add_element('given_name','text');
		$form->map('given_name','user_given_name');
		$form->add_element('surname','text');
		$form->map('surname','user_surname');
		return $form;
	}

	function show_current_value_and_edit_link()
	{
		$array['title'] = 'Name';
		$array['value'] = $this->user->get_value('user_given_name').' '.$this->user->get_value('user_surname');
		$array['link'] = 'name';
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