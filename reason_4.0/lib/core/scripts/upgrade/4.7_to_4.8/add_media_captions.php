<?php

$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_media_captions'] = 'ReasonUpgrader_48_AddMediaCaptions';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_48_AddMediaCaptions extends reasonUpgraderDefault implements reasonUpgraderInterface
{

	// this is a function because the use of id_of()
	public function newRelationships()
	{
		return array(
			array(
				'left_rel' => id_of('av'),
				'right_rel' => id_of('av_captions'),
				'rel_unique_name' => 'av_to_av_captions',
				'details' => array(
					'display_name' => 'Captions & Subtitles',
					'description' => 'To connect captions and subtitles to a Media Work',
					'directionality' => 'unidirectional',
					'connections' => 'many_to_one',
					'required' => 'no',
					'is_sortable' => 'no',
				)
			)
		);
	}

	public function newTypes()
	{
		return array(
			array(
				'name' => 'Media Captions',
				'table_name' => 'media_captions', // table name in db
				'type_details' => array(
					'new' => 0,
					'unique_name' => 'av_captions',
					'plural_name' => '', // I don't think we want one
					'custom_content_handler' => 'media_captions.php3',
				),
				'db_schema' => array(
					'lang' => array('db_type' => 'tinytext '),
					'kind' => array('db_type' => "enum('captions','subtitles','descriptions','chapters','metadata')"), // per webvtt spec
					'label' => array('db_type' => 'tinytext'),
					'content' => array('db_type' => 'mediumtext'),
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
		return 'Add Captions for Media Works';
	}

	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>Add new types and fields to support captions and subtitles</p>";
		return $str;
	}

	public function test()
	{
		$message = "<ol>";

		// step
		$newTypes = $this->typesToBeCreated();
		$message .= "<li>Checking if new types need to be created...";
		if (empty($newTypes)) {
			$message .= "<span style='color:green'>All types already exist!</li>";
		} else {
			$newTypesStr = trim(implode(", ", array_keys($newTypes)), ", ");
			$message .= "<strong style='color:red'>Upgrade Needed:</strong> The following new types would be created: $newTypesStr</li>";
		}

		// step
		$newRels = $this->relationshipsToBeCreated();
		$message .= "<li>Checking if relationships need to be created...";
		if (empty($newRels)) {
			$message .= "<span style='color:green'>All relationships already exist!</li>";
		} else {
			$newRelsStr = trim(implode(", ", array_keys($newRels)), ", ");
			$message .= "<strong style='color:red'>Upgrade Needed:</strong> The following new relationships would be created: $newRelsStr</li>";
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
		$newTypes = $this->typesToBeCreated();
		if (empty($newTypes)) {
			$message .= "<li>All types already exist.</li>";
		} else {
			$message .= "<li>" . $this->createTypes() . "</li>";
		}

		// step
		$newRels = $this->relationshipsToBeCreated();
		if (empty($newRels)) {
			$message .= "<li>All relationships already exist.</li>";
		} else {
			$message .= "<li>" . $this->createRelationships() . "</li>";
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

	public function user_id($user_id = NULL)
	{
		if (!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}

}
