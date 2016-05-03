<?php
	
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['add_notification_to_event_slot'] = 'ReasonUpgrader_47_AddNotificationToEventSlot';

class ReasonUpgrader_47_AddNotificationToEventSlot implements reasonUpgraderInterface
{
	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id = get_user_id(reason_require_authentication());
	}
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Adds notification_email field to Event Registration Slot type';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>This upgrade adds the field notification_email to the Event Registration Slot type. This field allows slot registrations to notify multiple recipients or no recipients, independent of the contact name on the event.</p>";
		return $str;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{	
		$log = $this->add_notification_email_field(true);
		return $log;
	}
	
        /**
         * Run the upgrader
         * @return string HTML report
         */
	public function run()
	{
		$log = $this->add_notification_email_field(false);
		$log .= $this->copy_notification_data_to_slots(); 
		return $log;
	}

	protected function add_notification_email_field($test_mode = false)
	{
		$log = '';
		$field_params = array('notification_email' => array('db_type' => 'tinytext'));
		$updater = new FieldToEntityTable('registration_slot', $field_params);
		$updater->test_mode = $test_mode;
		$updater->update_entity_table();
		
		ob_start();
		$updater->report();
		$log .= ob_get_contents();
		ob_end_clean();

		return $log;
	}

	/**
	 * Find all existing registration slots, and copy the contact information from their associated
	 * events into the new notification_email field.
	 */
	protected function copy_notification_data_to_slots()
	{
		$es = new entity_selector();
		$es->add_type(id_of('registration_slot_type'));
		$es->add_right_relationship_field('event_type_to_registration_slot_type','event','contact_username','contact');
		$results = $es->run_one();
		foreach ($results as $entity)
			reason_update_entity($entity->id(), $this->user_id(), array('notification_email'=>$entity->get_value('contact')), false);		
		return count($results).' slot entities updated.'."\n";
	}
}

?>
