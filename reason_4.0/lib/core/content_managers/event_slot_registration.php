<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'EventSlotRegistrationManager';

	/**
	 * A content manager for registration slots
	 */
	class EventSlotRegistrationManager extends ContentManager
	{
		function alter_data()
		{
			$this->set_order(array('name','unique_name', 'slot_description', 'registration_slot_capacity', 'registrant_data'));
			$this->change_element_type('registrant_data', 'solidtext');
			$this->set_comments('registrant_data', 
					     form_comment('Registrant data is managed from the event listing on the public site'));
		}

		function run_error_checks()
		{
			if (!$this->has_error('registration_slot_capacity'))
			{
				$cap = $this->get_value('registration_slot_capacity');
				if (!(is_numeric($cap)) || !($cap > 0))
				{
					$this->set_error('registration_slot_capacity', 'The registration slot capacity must be at least 1');
				}
			}
		}
	}
?>

