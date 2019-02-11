<?php

$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_event_registration'] = 'ReasonUpgrader_48_UpdateEventRegistration';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once("scripts/upgrade/reason_db_helper.php");

class ReasonUpgrader_48_UpdateEventRegistration extends reasonUpgraderDefault implements reasonUpgraderInterface
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
					// Relationships are managed in the Thor content manager.
					// They're added when an event ticket item is added to a form
					'custom_associator' => 'yes',
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
		$id = id_of("registration_slot_type");
		$type_exists = !empty($id);
		if (array_key_exists('download_backup_report', $_GET) && $_GET['download_backup_report'] == 'yes') {
			$this->exportOldSlots();
			exit;
		}

		$message = "<p><strong>WARNING: All Event Slot Data will be deleted. 
Download a backup below, and make sure you have a recent Reason database snapshot.
</strong></p><ol>";

		// step
		if ($type_exists) {
			$url = carl_make_link(['download_backup_report' => 'yes']);
			$link = "<a href='$url'>Download Slot Data<a/>";
			$message .= "<li>Download a backup of Event Slot data: $link</li>";
		} else {
			$message .= "<li>Event Slot Type not found. Is it already deleted?</li>";
		}

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

		// step
		$pages = $this->getPagesWithEventSlotTypes();
		$message .= "<li>Checking if any page types need to be updated...";
		if (!empty($pages)) {
			$message .= '<strong>Upgrade Needed:</strong> The page types "event_slot_registration" and "event_slot_registration_cache_1_hour" will change to "events".</li>';
		} else {
			$message .= 'No pages using the old event slot page types.</li>';
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
			$message .= "<li>All relationships already exist.</li>";
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

		// step
		if ($this->deleteEventSlots()) {
			$message .= "<li>All event slot entities purged.</li>";
		}

		// step
		$pages = $this->getPagesWithEventSlotTypes();
		if (!empty($pages)) {
			$message .= "<li>Updating page types...";
			$message .= $this->changePagesToEventPageType();
			$message .= "</li>";
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
		$id = id_of("registration_slot_type");
		$type_exists = !empty($id);
		if (!$type_exists) {
			return [];
		}

		$es = new entity_selector();
		$es->description = "Get events with regisration slot relation";
		$es->add_type(id_of('event_type'));
		$es->add_relation("`event`.`datetime` > $startDateForSQL");
		$es->add_left_relationship_field('event_type_to_registration_slot_type', 'registration_slot', 'id', 'slot_id');
		return $es->run_one();
	}

	/**
	 * Return report with info about all events with registration slot data
	 * @return array
	 */
	public function getExistingSlotReport()
	{
		$id = id_of("registration_slot_type");
		$type_exists = !empty($id);
		if (!$type_exists) {
			return [];
		}

		$es = new entity_selector();
		$es->add_type(id_of('event_type'));
		$es->add_left_relationship_field('event_type_to_registration_slot_type', 'registration_slot', 'id', 'slot_id');
		$events = $es->run_one();

		$report = [];
		foreach ($events as $event) {
			$event_values = $event->get_values();

			$slots = $event->get_left_relationship(relationship_id_of('event_type_to_registration_slot_type'));
			foreach ($slots as $slot) {
				$slot_values = $slot->get_values();

				$event_info = array_combine(
					array_map(function ($k) {
						return 'event_' . $k;
					}, array_keys($event_values)),
					$event_values
				);
				$slot_info = array_combine(
					array_map(function ($k) {
						return 'slot_' . $k;
					}, array_keys($slot_values)),
					$slot_values
				);
				$report[] = array_merge($event_info, $slot_info);
			}
		}

		return $report;
	}

	/**
	 * Write a CSV to output buffers of existing registration slots
	 */
	public function exportOldSlots()
	{

		$rows = $this->getExistingSlotReport();

		if($rows) {
			$filename = REASON_HOST . "_event_registration_slots_backup_" . date("Y-m-d-H-i-s") . ".csv";

			ob_clean();
			header("Content-Type: text/csv");
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			$out = fopen('php://output', 'w');
			fputcsv($out, array_keys($rows[0]));
			foreach ($rows as $row) {
				fputcsv($out, $row);
			}
			fclose($out);
		}
	}

	/**
	 * Remove all traces of Registration Slot in database
	 *
	 * @return bool TRUE on a successful delete of primary type, FALSE otherwise
	 */
	public function deleteEventSlots()
	{
		$type_exists = reason_unique_name_exists("registration_slot_type");

		// Delete instances of Registration Slots
		if ($type_exists) {
			$es = new entity_selector();
			$es->add_type(id_of('registration_slot_type'));
			$slots = $es->run_one();
			foreach ($slots as $slot) {
				reason_expunge_entity($slot->id(), $this->user_id);
			}
		}

		// Delete Registration Slot Entity Fields
		if ($type_exists) {
			$es = new entity_selector();
			$es->add_type(id_of('field'));
			$list = implode("', '", get_fields_by_content_table("registration_slot"));
			$es->add_relation("`entity`.`name` IN ('$list')");
			$fields = $es->run_one();
			foreach ($fields as $field) {
				reason_expunge_entity($field->id(), $this->user_id);
			}
		}

		// Delete Registration Slot Entity Table
		if ($type_exists) {
			$es = new entity_selector();
			$es->add_type(id_of('content_table'));
			$es->add_relation("`entity`.`name` = 'registration_slot'");
			$table = $es->run_one();
			reason_expunge_entity(array_pop($table)->id(), $this->user_id);
		}

		// Delete Registration Slot Type
		$type_deleted = false;
		if ($type_exists) {
			$type_deleted = true;
			$type_entity = new entity(id_of("registration_slot_type"));
			reason_expunge_entity($type_entity->id(), $this->user_id);
		}

		// Delete Registration Slot Allowable Relationships
		if ($type_exists) {
			$delete_allowable_rels = "DELETE FROM `allowable_relationship` WHERE `relationship_a` = {$type_entity->id()} OR `relationship_b` = {$type_entity->id()}";
			db_query($delete_allowable_rels);
		}

		// Delete Registration Slot Table
		if ($type_exists) {
			$drop_table = "DROP TABLE `registration_slot`";
			db_query($drop_table);
		}

		return $type_exists && $type_deleted;
	}

	/**
	 * Search for pages with page types that were removed
	 * @return array array of entities matching removed page types
	 */
	function getPagesWithEventSlotTypes()
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->enable_multivalue_results();
		$es->limit_tables('page_node');
		$es->limit_fields('custom_page');
		$es->add_relation('(page_node.custom_page = "event_slot_registration" OR page_node.custom_page = "event_slot_registration_cache_1_hour")');
		return $es->run_one();
	}


	/**
	 * Update pages with old page types to Events page type
	 * @return string
	 */
	function changePagesToEventPageType()
	{
		$to_change = $this->getPagesWithEventSlotTypes();

		$message = "";
		foreach ($to_change as $entity) {
			$values = array('custom_page' => 'events');
			$url = reason_get_page_url($entity);
			$message .= 'Changed page type on: <a href="' . $url . '">' . $url . '</a><br>';
			reason_update_entity($entity->id(), $this->user_id, $values);
		}
		return $message ? $message : "No changes made to page types";
	}

}
 