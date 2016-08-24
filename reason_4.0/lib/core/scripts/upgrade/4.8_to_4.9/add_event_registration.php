<?php

$GLOBALS['_reason_upgraders']['4.8_to_4.9']['add_event_registration'] = 'ReasonUpgrader_49_UpdateEventRegistration';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once("scripts/upgrade/reason_db_helper.php");

class ReasonUpgrader_49_UpdateEventRegistration extends reasonUpgraderDefault implements reasonUpgraderInterface
{

	public function newRelationshipsDictionary()
	{
		return array('event_to_form' => array(
				'leftRel' => id_of('event_type'),
				'rightRel' => id_of('form'),
				'details' => array(
					'description' => 'Event to Form for event registration purposes',
					'directionality' => 'bidirectional',
					'connections' => 'many_to_many',
					'required' => 'no',
					'is_sortable' => 'no',
					'display_name' => 'Registration Form',
				)
			)
		);
	}

	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Update Event Slots to Registration';
	}

	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>This upgrade exports event slot data, removes that functionality, and adds event registration form functionality.</p>";
		return $str;
	}

	public function test()
	{
		$message = "<ol>";

		// step
		$eventSlotsInFuture = $this->checkForEventSlots();
		$message .= "<li>Checking if there are any event slots currently open...";
		if ($eventSlotsInFuture == "") {
			$message .= "Success! No event slots are currently in use.</li>";
		} else {
			$message .= "<strong>WARNING:</strong> Event slots configured for future events. "
					. "There is no migration path forward; slot data is exported and relationships are removed."
					. "Proceed with caution. Matching Events: <br>"
					. "$eventSlotsInFuture</li>";
		}

		// step
		$newRels = $this->relationshipsToBeCreated();
		$message .= "<li>Checking if relationships need to be created...";
		if (empty($newRels)) {
			$message .= "Relationships already exist!</li>";
		} else {
			$newRels = trim(implode(", ", array_keys($newRels)), ", ");
			$message .= "<strong>Upgrade Needed:</strong> The following new relationships would be created: $newRels</li>";
		}

		// step
		$entityTable = 'form';
		$fieldUpdater = new FieldToEntityTable($entityTable, $this->getFieldsDefinition());
		$message .= "<li>Checking if new field for forms needs to be created...";
		if (!$fieldUpdater->field_exists('include_thank_you_in_email')) {
			$message .= '<strong>Upgrade Needed:</strong> The field "include_thank_you_in_email" will be added to forms.</li>';
		} else {
			$message .= 'The form field "include_thank_you_in_email" already exists</li>';
		}

		return $message . "</ol>";
	}

	/**
	 * Run the upgrader
	 * @return string HTML report
	 */
	public function run()
	{
		$this->dbHelper = new ReasonDbHelper();
		$currentUsername = get_authentication_from_session();
		$this->dbHelper->setUsername($currentUsername);

		$message = "<ol>";
		$newRels = $this->relationshipsToBeCreated();

		if (empty($newRels)) {
			$message = "<li>All relationships already exist.</li>";
		} else {
			$message .= $this->checkForEventSlots();
			$message .= $this->createRelationships();
		}
		
		$entityTable = 'form';
		$fieldUpdater = new FieldToEntityTable($entityTable, $this->getFieldsDefinition());
		if ($fieldUpdater->field_exists('include_thank_you_in_email')) {
			$message .= '<li>The form field "include_thank_you_in_email" already exists</li>';
		} else {
			$fieldUpdater->update_entity_table();
			ob_start();
			$fieldUpdater->report();
			$message .= ob_get_clean();
		}


		return $message . "</ol>";
	}
	
	public function createRelationships()
	{
		$message = "";
		
		// Make any relationships that need to be created
		$relsToMake = $this->relationshipsToBeCreated();

		foreach ($relsToMake as $relName => $rel) {
			$message .= "Creating relationship $relName... <br>";
			$ownerId = $this->dbHelper->createAllowableRelationshipHelper(
					$rel["leftRel"], $rel["rightRel"], $relName, $rel["details"]
			);
			$message .= "Created relationsip with id $ownerId <br>";
		}
		
		return $message;
	}

	public function relationshipsToBeCreated()
	{
		reason_refresh_unique_names();
		$newRels = $this->newRelationshipsDictionary();

		$relationshipsToBeCreated = array();
		foreach ($newRels as $relName => $relInfo) {
			if (!reason_relationship_name_exists($relName)) {
				$relationshipsToBeCreated[$relName] = $relInfo;
			}
		}

		return $relationshipsToBeCreated;
	}
	
	public function getFieldsDefinition()
	{
		return array('include_thank_you_in_email' => array('db_type' => 'enum("yes", "no") DEFAULT NULL'));
	}

	public function checkForEventSlots()
	{
		$message = "";
		$future_events = $this->getEventsWithSlots();
		foreach ($future_events as $event) {
			$numSlots = count($event->get_left_relationship('event_type_to_registration_slot_type'));
			$message .= "Event {$event->get_value('name')} (id: {$event->id()}) has $numSlots slot(s) active.<br>\n";
		}

		return $message;
	}

	public function getEventsWithSlots($startDateForSQL = "NOW()")
	{
		$es = new entity_selector();
		$es->description = "Get events with regisration slot relation";
		$es->add_type(id_of('event_type'));
		$es->add_relation("`event`.`datetime` > $startDateForSQL");
		$es->add_left_relationship_field('event_type_to_registration_slot_type', 'registration_slot', 'id', 'slot_id');
		return $es->run_one();
	}
	
	public function exportOldSlots()
	{
		
	}

}
 