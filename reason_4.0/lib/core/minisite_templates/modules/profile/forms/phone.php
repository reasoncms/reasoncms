<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );

/**
 * Phone edit form.
 *
 * - Edit a phone number
 * 
 * @todo lets add jquery mask plug-in or something to enforce formatting.
 */
class phoneProfileEditForm extends defaultProfileEditForm
{
	function on_every_time()
	{
		$this->add_element('phone', 'text');
		$person = $this->get_person();
		$value = $person->get_profile_field('phone');
		if (!empty($value)) $this->set_value('phone', $value);
	}
	
	/**
	 * @todo should we run a meaningful check on this?
	 */
	function run_error_checks()
	{
		//$email = $this->get_value('address');
		//if ( (strlen($email) > 0) && !$this->validate_email($email))
		//{
		//	$this->set_error('email', 'You need to provide a valid email address.');
		//}
	}
	
	/**
	 * Save
	 */
	function process()
	{
		$person = $this->get_person();
		$value = $this->get_value('phone');
		$person->update_profile_entity_field('phone', strip_tags($value));
	}
}