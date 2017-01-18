<?php

$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_form_prefill_select'] = 'ReasonUpgrader_48_AddFormPrefill';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_48_AddFormPrefill extends reasonUpgraderDefault implements reasonUpgraderInterface
{

	public function newRelationships()
	{
		return array(
//			array(
//				'left_rel' => id_of('av'),
//				'right_rel' => id_of('av_captions'),
//				'rel_unique_name' => 'av_to_av_captions',
//				'details' => array(
//					'display_name' => 'Captions & Subtitles',
//					'description' => 'To connect captions and subtitles to a Media Work',
//					'directionality' => 'unidirectional',
//					'connections' => 'many_to_one',
//					'required' => 'no',
//					'is_sortable' => 'no',
//				)
//			)
		);
	}

	public function newTypes()
	{
		return array(
//			array(
//				'name' => 'Media Captions',
//				'table_name' => 'media_captions', // table name in db
//				'type_details' => array(
//					'new' => 0,
//					'unique_name' => 'av_captions',
//					'plural_name' => '', 
//					'custom_content_handler' => 'media_captions.php3',
//				),
//				'db_schema' => array(
//					'lang' => array('db_type' => 'tinytext '),
//					'kind' => array('db_type' => "enum('captions','subtitles','descriptions','chapters','metadata')"), // per webvtt spec
//					'label' => array('db_type' => 'tinytext'),
//					'content' => array('db_type' => 'mediumtext'),
//				)
//			)
		);
	}

	public function newFields()
	{
		return array(
			array(
				'entityTable' => "form",
				'fields' => array(
					"prefill_these_form_fields" => array(
						'db_type' => 'tinytext'
					)
				),
			),
		);
	}

	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Add select menu to enable prefilled fields from a URL';
	}

	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>Add select menu to enable prefilled fields from a URL</p>";
		return $str;
	}

	public function test()
	{
		$message = "<ol>";

		// step
		if ($this->newTypes()) {
			$newTypes = $this->typesToBeCreated();
			$message .= "<li>Checking if new types need to be created...";
			if (empty($newTypes)) {
				$message .= "<span style='color:green'>All types already exist!</li>";
			} else {
				$newTypesStr = trim(implode(", ", array_keys($newTypes)), ", ");
				$message .= "<strong style='color:red'>Upgrade Needed:</strong> The following new types would be created: $newTypesStr</li>";
			}
		}

		// step
		if ($this->newRelationships()) {
			$newRels = $this->relationshipsToBeCreated();
			$message .= "<li>Checking if relationships need to be created...";
			if (empty($newRels)) {
				$message .= "<span style='color:green'>All relationships already exist!</li>";
			} else {
				$newRelsStr = trim(implode(", ", array_keys($newRels)), ", ");
				$message .= "<strong style='color:red'>Upgrade Needed:</strong> The following new relationships would be created: $newRelsStr</li>";
			}
		}

		// step
		if ($this->newFields()) {
			$newFields = $this->fieldsToBeCreated();
			$message .= "<li>Checking if new fields need to be added...";
			if (empty($newFields)) {
				$message .= "<span style='color:green'>All fields already exist!</li>";
			} else {
				$newFieldsStr = trim(implode(", ", array_keys($newFields)), ", ");
				$message .= "<strong style='color:red'>Upgrade Needed:</strong> The following new fields would be created: $newFieldsStr</li>";
			}
		}

		return $message . "</ol>";
	}

	/**
	 * Run the upgrader
	 * @return string HTML report
	 */
	public function run()
	{
		$message = "<ol>";

		// step
		if ($this->newTypes()) {
			$newTypes = $this->typesToBeCreated();
			if (empty($newTypes)) {
				$message .= "<li>All types already exist.</li>";
			} else {
				$message .= "<li>" . $this->createTypes() . "</li>";
			}
		}

		// step
		if ($this->newRelationships()) {
			$newRels = $this->relationshipsToBeCreated();
			if (empty($newRels)) {
				$message .= "<li>All relationships already exist.</li>";
			} else {
				$message .= "<li>" . $this->createRelationships() . "</li>";
			}
		}

		// step
		if ($this->newFields()) {
			$newFields = $this->fieldsToBeCreated();
			if (empty($newFields)) {
				$message .= "<li>All fields already exist.</li>";
			} else {
				$message .= "<li>" . $this->createFields() . "</li>";
			}
		}

		return $message . "</ol>";
	}

	/**
	 * Create types which don't exist yet as defined in newTypes()
	 * 
	 * @return string status message to display to user 
	 */
	protected function createTypes()
	{
		$str = '';
		$typesToMake = $this->typesToBeCreated();

		foreach ($typesToMake as $typeInfo) {
			$uniqueName = $typeInfo['type_details']['unique_name'];
			$str .= "Making new type...<br>";
			$typeId = reason_create_entity(
					id_of('master_admin'), id_of('type'), $this->user_id(), $typeInfo['name'], $typeInfo['type_details']
			);
			create_default_rels_for_new_type($typeId);
			$tableId = create_reason_table($typeInfo['table_name'], $uniqueName, $this->user_id());
			if ($tableId) {
				$str .= "<span style='color:green'>Made new type $uniqueName with id of $tableId!</span> <br>";
			} else {
				$str .= "<span style='color:red'>ERROR making new type $uniqueName!</span> <br>";
			}

			if (!empty($typeInfo['db_schema'])) {
				$ftet = new FieldToEntityTable($typeInfo['table_name'], $typeInfo['db_schema']);

				$ftet->update_entity_table();
				ob_start();
				$ftet->report();
				$str .= ob_get_contents();
				ob_end_clean();
			}
		}

		return $str;
	}

	/**
	 * Filters out existing types from the type dictionary 
	 * and only returns types which don't yet exist.
	 * 
	 * @return array dictionary of types which don't exist
	 *     to be used by createTypes()
	 */
	protected function typesToBeCreated()
	{
		reason_refresh_unique_names();
		$newTypes = $this->newTypes();

		$typesToBeCreated = array();
		foreach ($newTypes as $typeInfo) {
			$uniqueName = $typeInfo['type_details']['unique_name'];
			if (!reason_unique_name_exists($uniqueName)) {
				$typesToBeCreated[$uniqueName] = $typeInfo;
			}
		}
		return $typesToBeCreated;
	}

	/**
	 * Create relationships which don't exist yet as defined in newRelationships()
	 * 
	 * @return string status message to display to user 
	 */
	protected function createRelationships()
	{
		$message = "";

		// Make any relationships that need to be created
		$relsToMake = $this->relationshipsToBeCreated();

		foreach ($relsToMake as $relInfo) {
			$relName = $relInfo['rel_unique_name'];
			$message .= "Creating relationship $relName... <br>";
			$ownerId = create_allowable_relationship($relInfo["left_rel"], $relInfo["right_rel"], $relName, $relInfo["details"]);
			if ($ownerId) {
				$message .= "<span style='color:green'>Created relationship with id $ownerId</span> <br>";
			} else {
				$message .= "<span style='color:red'>ERROR making relationship $relName!</span> <br>";
			}
		}

		return $message;
	}

	/**
	 * Filters out existing relationships from the relationship dictionary 
	 * and only returns relationships which don't yet exist.
	 * 
	 * @return array dictionary of relationships which don't exist
	 *     to be used by createRelationships()
	 */
	protected function relationshipsToBeCreated()
	{
		reason_refresh_unique_names();
		$newRels = $this->newRelationships();

		$relationshipsToBeCreated = array();
		foreach ($newRels as $relInfo) {
			$relName = $relInfo['rel_unique_name'];
			if (!reason_relationship_name_exists($relName)) {
				$relationshipsToBeCreated[$relName] = $relInfo;
			}
		}

		return $relationshipsToBeCreated;
	}

	/**
	 * Create fields which don't exist yet
	 * 
	 * Uses output from fieldsToBeCreated()
	 * 
	 * @return string status message to display to user
	 */
	protected function createFields()
	{
		$message = "";

		$fieldsToCreate = $this->fieldsToBeCreated();

		foreach ($fieldsToCreate as $info) {
			$updater = new FieldToEntityTable($info['entityTable'], $info['fields']);
			$updater->update_entity_table();
			ob_start();
			$updater->report();
			$message .= ob_get_clean();
			$updater = null;
		}

		return $message;
	}

	/**
	 * Filter out existing fields from the fields defined in newFields()
	 * 
	 * @return array dictionary of fields which don't exist
	 *     to be used by createFields()
	 */
	protected function fieldsToBeCreated()
	{
		$newFields = $this->newFields();
		$fieldsToCreate = array();
		foreach ($newFields as $info) {
			$fields = $info['fields'];
			$updater = new FieldToEntityTable($info['entityTable'], $fields);
			foreach ($fields as $field_name => $field_details) {
				if (!$updater->field_exists($field_name)) {
					$key = $info['entityTable'] . "." . $field_name;
					$fieldsToCreate[$key] = array(
						'entityTable' => $info['entityTable'],
						'fields' => $fields
					);
				}
			}
		}

		return $fieldsToCreate;
	}
}
