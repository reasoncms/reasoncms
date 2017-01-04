<?php

$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_thor_column'] = 'ReasonUpgrader_48_AddThorColumn';
include_once('reason_header.php');
include_once('thor/thor.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');

class ReasonUpgrader_48_AddThorColumn extends reasonUpgraderDefault implements reasonUpgraderInterface
{

	// Most of this class is generic and can be reused,
	// but copy_date_modified_to_date_user_submitted() is specific to the date_user_submitted column
	public $new_col_name = "date_user_submitted";
	public $new_col_specification = "TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00'";

	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return "Add `{$this->new_col_name}` Column To Existing Thor Tables";
	}

	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>Add `{$this->new_col_name}` Column To Existing Thor Tables/p>";
		return $str;
	}

	public function test()
	{
		$thor_tables_needing_update = $this->get_table_names_missing_new_column();

		echo "<p>Looking for Thor tables missing `{$this->new_col_name}` column...";
		$numOps = count($thor_tables_needing_update);
		if ($numOps > 0) {
			echo "<strong style='color:red'>Upgrade Needed:</strong> $numOps tables need upgrade";
		} else {
			echo "<strong style='color:green'>Upgrade not necessary</strong>";
		}
		echo "</p>";
	}

	/**
	 * Run the upgrader
	 * @return string HTML report
	 */
	public function run()
	{
		$thor_tables_needing_update = $this->get_table_names_missing_new_column();
		$tables_modified = 0;
		foreach ($thor_tables_needing_update as $row) {
			$this->add_new_column_to_table($row['TABLE_NAME']);

			// To avoid user confusion, prefill all submitted dates to dates 
			// last modified. It's likely accurate in almost all cases
			$this->copy_date_modified_to_date_user_submitted($row['TABLE_NAME']);

			$tables_modified++;
		}
		echo "<strong style='color:green'>Added `{$this->new_col_name}` column to $tables_modified tables.</strong>";
	}

	/**
	 * Run a query against Thor DB
	 * 
	 * @param string $query sql query
	 * @return mixed returns a data array, the number of affected rows (int), 
	 *     or null depending on the query issued
	 */
	public function run_thor_query($query)
	{
		connectDB(THOR_FORM_DB_CONN);

		$result = db_query($query);

		$statement = strtolower(substr(trim($query), 0, 6));
		if ($statement === 'select') {
			$returnValue = array();
			while ($row = mysql_fetch_assoc($result)) {
				$returnValue[] = $row;
			}
		} else if ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
			$returnValue = mysql_affected_rows();
		} else {
			$returnValue = null;
		}

		connectDB(REASON_DB);
		return $returnValue;
	}

	public function get_table_names_missing_new_column()
	{
		$thor_db_name = $this->get_thor_db_name();
		$query = "
		SELECT DISTINCT
			TABLE_NAME
		FROM
			INFORMATION_SCHEMA.COLUMNS
		WHERE
			TABLE_SCHEMA = '$thor_db_name'
				AND TABLE_NAME LIKE 'form_%'
				AND TABLE_NAME NOT IN (SELECT DISTINCT
					TABLE_NAME
				FROM
					INFORMATION_SCHEMA.COLUMNS
				WHERE
					COLUMN_NAME = '{$this->new_col_name}'
						AND TABLE_SCHEMA = '$thor_db_name')";

		return $this->run_thor_query($query);
	}

	public function add_new_column_to_table($table_name)
	{
		$thor_db_name = $this->get_thor_db_name();

		$query = "ALTER TABLE `$thor_db_name`.`$table_name`
			ADD COLUMN `{$this->new_col_name}` {$this->new_col_specification}";

		$this->run_thor_query($query);
	}

	public function copy_date_modified_to_date_user_submitted($table_name)
	{
		$thor_db_name = $this->get_thor_db_name();
		$query = "
		SELECT
			COLUMN_NAME
		FROM
			INFORMATION_SCHEMA.COLUMNS
		WHERE
			TABLE_SCHEMA = '$thor_db_name'
				AND TABLE_NAME = '$table_name'
				AND COLUMN_NAME = 'date_modified'";
		$result = $this->run_thor_query($query);

		// Some older thor tables don't have the date_modified columnn,
		// bail if they don't have it
		$modified_col_exists = count($result) > 0;
		if ($modified_col_exists) {
			// Disable date_modified default update
			$query = "ALTER TABLE `$thor_db_name`.`$table_name`
			CHANGE COLUMN `date_modified` `date_modified` TIMESTAMP NOT NULL";
			$this->run_thor_query($query);

			// Copy data
			$query = "UPDATE `$thor_db_name`.`$table_name` SET	`{$this->new_col_name}` = `date_modified`";
			$this->run_thor_query($query);

			// Enable date_modified default update
			$query = "ALTER TABLE `$thor_db_name`.`$table_name`
			CHANGE COLUMN `date_modified` `date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
			$this->run_thor_query($query);
		}
	}

	public function get_thor_db_name()
	{
		$creds = get_db_credentials(THOR_FORM_DB_CONN);
		return $creds['db'];
	}

	public function user_id($user_id = NULL)
	{
		if (!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}

}
