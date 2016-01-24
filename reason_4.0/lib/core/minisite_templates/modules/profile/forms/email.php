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
 * Email edit form.
 *
 * - Edit a single email address.
 *
 * @todo make section name agnostic
 */
class emailProfileEditForm extends defaultProfileEditForm
{
	function on_every_time()
	{
		$this->add_element('email', 'text');
		$person = $this->get_person();
		$value = $person->get_profile_field('email');
		if (!empty($value)) $this->set_value('email', $value);
	}
	
	/**
	 * @todo what is left?
	 */
	function run_error_checks()
	{
		$email = $this->get_value('email');
		if ( (strlen($email) > 0) && !$this->validate_email($email))
		{
			$this->set_error('email', 'You need to provide a valid email address.');
		}
	}
	
	private function validate_email($email)
	{
		return (filter_var($email, FILTER_VALIDATE_EMAIL));
	}
	
	/**
	 * Save new / updated sites as external urls using profile person methods.
	 *
	 * - Call sync
	 */
	function process()
	{
		$person = $this->get_person();
		$value = $this->get_value('email');
		$person->update_profile_entity_field('email', $value);
	}
}