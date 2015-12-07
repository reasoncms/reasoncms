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
			$this->set_order(array('name','unique_name', 'slot_description', 'registration_slot_capacity', 'notification_email', 'registrant_data'));
			$this->change_element_type('registrant_data', 'solidtext');
			$this->set_comments('registrant_data', 
					     form_comment('Registrant data is managed from the event listing on the public site'));
			$this->set_comments('notification_email', form_comment('To receive notifications when people register for this slot, enter usernames or email addresses here.'));
			// Plug in the user as the contact if this is a new slot
			if( !$this->get_value('notification_email') )
			{
				$e = new entity($this->get_value('id'));
				if(1 == $e->get_value('new') && $e->get_value('last_modified') == $e->get_value('creation_date'))
				{
					$user = new entity( $this->admin_page->user_id );
					$this->set_value( 'notification_email', $user->get_value('name') );
				}
			}
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
			if ($notifications = $this->get_value('notification_email'))
			{
				$addresses = preg_split('/[\s,;]+/', $notifications);
			}
		}
	}
?>

