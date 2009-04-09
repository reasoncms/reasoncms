<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
include_once('default_setting_form.php');

class emailSettingForm extends defaultSettingForm
{

	function run_error_checks()
	{
		if (!check_against_regexp($this->get_value('email'), array('email'))) $this->set_error('email', 'You must enter a valid email address.');
	}

}
?>