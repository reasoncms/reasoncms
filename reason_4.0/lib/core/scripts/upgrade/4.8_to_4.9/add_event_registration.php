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
		$newRels = $this->relationshipsToBeCreated();

		if (empty($newRels)) {
			return "<p>All relationships have already been created. This script doesn't need to run</p>";
		} else {
			$newRels = trim(implode(", ", array_keys($newRels)), ", ");
			$message = "The following new relationships would be created: $newRels";
			return $message;
		}
	}

	/**
	 * Run the upgrader
	 * @return string HTML report
	 */
	public function run()
	{
		$this->dbHelper = new ReasonDbHelper();
		$this->dbHelper->setUsername('mlauer');

		$message = "";
		$newRels = $this->relationshipsToBeCreated();

		if (empty($newRels)) {
			$message = "<p>All relationships have already been created. This script doesn't need to run</p>";
		} else {
			// Make any relationships that need to be created
			$relsToMake = $this->relationshipsToBeCreated();

			foreach ($relsToMake as $relName => $rel) {
				$message .= "Creating relationship $relName... <br>";
				$ownerId = $this->dbHelper->createAllowableRelationshipHelper(
						$rel["leftRel"], $rel["rightRel"], $relName, $rel["details"]
				);
				$message .= "Created relationsip with id $ownerId <br>";
			}
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

}
