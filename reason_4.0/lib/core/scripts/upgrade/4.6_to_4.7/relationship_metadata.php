<?php
/**
 * Upgrader that makes db changes to support adding relationship metadata
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
// reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['relationship_metadata'] = 'ReasonUpgrader_47_RelationshipMetadataDbChanges';

class ReasonUpgrader_47_RelationshipMetadataDbChanges implements reasonUpgraderInterface
{
	protected $_user_id;
	public function user_id( $user_id = NULL) {
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}

	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return "Upgrade the Reason database to support attaching metadata on relationships";
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		return "<p>This upgrade:<ul>" .
			"<li>modifies the type entity, adding a 'variety' field to distinguish between content, structural, and metadata types</li>" .
			"<li>modifies the field entity, adding a 'is_required' and 'admin_only' fields" .
			"<li>modifies the allowable_relationship table, adding a 'meta_type' and 'meta_availability' column to specify the type of entity that can be associated with this sort of relationship</li>" .
			"<li>modifies the relationship table, adding a 'meta_id' column to associate a particular entity with a particular relationship, as
				well as 'last_edited_by', 'last_modified', and 'creation_date' to enhance relationship history.</li>" .
			"</ul>".
			"<strong>NOTE: Because this upgrade modifies the relationship table, it can take several minutes to complete.</strong> You ".
			"may want to run it during a downtime or low-traffic situation.</p>";
	}
	
	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		return "<p>" .
			"'type' table has column 'variety': " . ($this->columnExistsOnTable("type", "variety") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'field' table has column 'is_required': " . ($this->columnExistsOnTable("field", "is_required") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'field' table has column 'admin_only': " . ($this->columnExistsOnTable("field", "admin_only") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'relationship' table has column 'meta_id': " . ($this->columnExistsOnTable("relationship", "meta_id") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'relationship' table has column 'allow_duplicates': " . ($this->columnExistsOnTable("relationship", "allow_duplicates") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'relationship' table has column 'created_by': " . ($this->columnExistsOnTable("relationship", "created_by") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'relationship' table has column 'last_edited_by': " . ($this->columnExistsOnTable("relationship", "last_edited_by") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'relationship' table has column 'last_modified': " . ($this->columnExistsOnTable("relationship", "last_modified") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'relationship' table has column 'creation_date': " . ($this->columnExistsOnTable("relationship", "creation_date") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'allowable_relationship' table has column 'meta_type': " . ($this->columnExistsOnTable("allowable_relationship", "meta_type") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"'allowable_relationship' table has column 'meta_availability': " . ($this->columnExistsOnTable("allowable_relationship", "meta_availability") ? "<font color=green>yes</font>" : "<font color=red>no</font>") . "<br/>" .
			"</p>";
	}

	private function columnExistsOnTable($tableName, $columnName)
	{
		$q = "SHOW COLUMNS FROM " . $tableName . " WHERE field = '" . $columnName . "'";
		$res = db_query($q, "error checking for column $columnName on table $tableName");
		return mysql_num_rows($res) == 1;
	}

    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		echo "Running upgrader...<br/>";

		$newTypeFields = array(
			'variety' => "enum ('content', 'structural', 'relationship_meta') DEFAULT 'content'"
		);

		$newFieldFields = array(
			'is_required' => "tinyint(1) DEFAULT 0",
			'admin_only' => "tinyint(1) DEFAULT 0",
		);

		$this->addColumnsToTable("relationship", array(
			"meta_id" => "int (10) unsigned not null default 0",		// id of specific entity containing metadata
			"allow_duplicates" => "tinyint(1) not null default 0",			// flag to enable duplicate rels with different metadata
			"created_by" => "int (10) unsigned not null default 0",			
			"last_edited_by" => "int (10) unsigned not null default 0",		
			"creation_date" => "timestamp",									
			"last_modified" => "timestamp"));								
		$this->addColumnToTable("allowable_relationship", "meta_type", "int (10) unsigned not null default 0");							// id of potential metadata's required type
		$this->addColumnToTable("allowable_relationship", "meta_availability", "enum('global','by_site') not null default 'global'");							// id of potential metadata's required type
		$this->addFieldsToEntity("type", $newTypeFields);
		$this->addFieldsToEntity("field", $newFieldFields);
	}

	private function addFieldsToEntity($tableName, $newFields) {
		$test_mode = false;

		foreach ($newFields as $field => $db_type) {
			if ($this->columnExistsOnTable($tableName, $field)) {
				echo "'$tableName' table already has column '$field'; continuing...<br/>";
			} else {
				$field_params = array($field => array('db_type' => $db_type));
				$updater = new FieldToEntityTable($tableName, $field_params);
				$updater->test_mode = $test_mode;
				$updater->update_entity_table();

				ob_start();
				$updater->report();
				$log .= ob_get_contents();
				ob_end_clean();
				echo "<br/>";
			}
		}
	}

	private function addColumnToTable($tableName, $fieldName, $fieldDetails) {
		if ($this->columnExistsOnTable($tableName, $fieldName)) {
			echo "'$tableName' table already has column '$fieldName'; continuing...<br/>";
			return true;
		} else {
			echo "adding column '$fieldName' to '$tableName' table...</br>";
			$q = "ALTER TABLE $tableName ADD COLUMN $fieldName $fieldDetails";
			$result = db_query($q, "error adding '$fieldName' to '$tableName' table");

			if ($result == 1) {
				echo "<font color=\"green\">success!</font><br/>";
				return true;
			} else {
				echo "<font color=\"red\">an error occurred...</font><br/>";
				return false;
			}
		}
	}
	
	private function addColumnsToTable($tableName, $columns) {
		foreach ($columns as $col => $def)
		{
			if ($this->columnExistsOnTable($tableName, $col))
			{
				echo "'$tableName' table already has column '$col'; continuing...<br/>";
				unset($columns[$col]);
			}
			else
			{
				$create_def[] = ' ADD COLUMN '.$col .' '.$def;
			}
		}
		
		if (!empty($create_def))
		{
			echo "adding columns '".join("', '", array_keys($columns))."' to '$tableName' table...</br>";
			$q = "ALTER TABLE $tableName ".join(', ', $create_def);
			$result = db_query($q, "error adding columns to '$tableName' table");

			if ($result == 1) {
				echo "<font color=\"green\">success!</font><br/>";
				return true;
			} else {
				echo "<font color=\"red\">an error occurred...</font><br/>";
				return false;
			}
		}
		return true;
	}	
}
?>
