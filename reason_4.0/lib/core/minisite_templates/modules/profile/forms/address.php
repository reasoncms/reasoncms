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
 * Address edit form.
 *
 * - Edit an address - provide textarea with 3 lines.
 * 
 * @todo should we have address1 and address2 fields? Maybe think about nl to br?
 */
class addressProfileEditForm extends defaultProfileEditForm
{
	function on_every_time()
	{
		$this->add_element('address', 'textarea', array('rows' => 3));
		$person = $this->get_person();
		$value = $person->get_profile_field('address');
		if (!empty($value)) $this->set_value('address', $value);
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
	 * Save new / updated sites as external urls using profile person methods.
	 *
	 * - Call sync
	 */
	function process()
	{
		$person = $this->get_person();
		$value = $this->get_value('address');
		$person->update_profile_entity_field('address', strip_tags($value));
	}
}