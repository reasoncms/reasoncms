<?php
	include_once('reason_header.php');
	reason_include_once('function_libraries/admin_actions.php');
	reason_include_once('classes/field_to_entity_table_class.php');

	class ReasonDbHelper {
		public function __construct() {
			$this->username = "";
			$this->userId = -1;
		}

		public function typeAlreadyExists($typeName) {
			$bar = reason_refresh_unique_names();  // force refresh from the database just in case.
			 // var_dump($bar);
			// $foo = id_of($typeName, true, false);
			// $this->println("got foo [" . $foo . "]...");

			$rv = reason_unique_name_exists($typeName);
			// $this->println("checking for type existence (" . $typeName . ")...[" . $rv . "]");
			return $rv;
		}

		public function setUsername($uname) {
			$this->username = $uname;
			$this->userId = get_user_id($this->username);
		}
		
		public function createAllowableRelationshipHelper($aSideId, $bSideId, $relationshipName, $details) {
			if ($this->userId == -1) { die("ERROR - USER ID NOT SET\n"); }

			reason_refresh_unique_names();  // force refresh from the database just in case.
			if (reason_relationship_name_exists($relationshipName, false)) {
				$rv = relationship_id_of($relationshipName, true, false);
				$this->println("Allowable relationship already exists for $relationshipName: $rv");
				return $rv;
			} else {
				$relationshipId = create_allowable_relationship($aSideId, $bSideId, $relationshipName, $details);
				return $relationshipId;
			}
		}

		// creates a type if it doesn't already exist. Either way, returns the id for this unique name.
		public function createTypeHelper($userFacingTypeName, $tableName, $typeUniqueName, $entityTableFields, $details) {
			if ($this->userId == -1) { die("ERROR - USER ID NOT SET\n"); }

			if ($this->typeAlreadyExists($typeUniqueName)) {
				$rv = id_of($typeUniqueName);
				$this->println("Type already exists for $typeUniqueName: $rv");
				return $rv;
			} else {
				$typeId = reason_create_entity(id_of('master_admin'), id_of('type'), $this->userId, $userFacingTypeName, $details, false);
				// at this point we have an entry in the "type" and the "entity" tables representing this new data type, and in the "relationship" table marking this as owned by master_admin
				$this->println("creation of [" . $userFacingTypeName . "] gave us type id [" . $typeId . "]");


				$this->println("refreshing relationship names...");
				reason_refresh_relationship_names();

				create_default_rels_for_new_type($typeId);		
				// now we have some entries in allowable_relationships
				$this->println("created default relationships for this type");

				if (count($entityTableFields) > 0) {
					create_reason_table($tableName, $typeUniqueName, $this->username);
					// now we have a barebones entity table...
					$this->println("created entity table...");

					$ftet = new FieldToEntityTable($tableName, $entityTableFields, $this->username);
					$ftet->update_entity_table();
					$this->println("now the entity table has some additional columns");

					$ftet->report();
					$this->println("");
				} else {
					$this->println("skipping entity table creation...");
				}

				return $typeId;
			}
		}

		public function columnExistsOnTable($tableName, $columnName)
		{
			$q = "SHOW COLUMNS FROM " . $tableName . " WHERE field = '" . $columnName . "'";
			$res = db_query($q, "error checking for column $columnName on table $tableName");
			return mysql_num_rows($res) == 1;
		}

		public function addFieldsToEntity($tableName, $newFields) {
			$test_mode = false;

			foreach ($newFields as $field => $db_type) {
				if ($this->columnExistsOnTable($tableName, $field)) {
					$this->println("'$tableName' table already has column '$field'; continuing...");
				} else {
					$field_params = array($field => array('db_type' => $db_type));
					$updater = new FieldToEntityTable($tableName, $field_params);
					$updater->test_mode = $test_mode;
					$updater->update_entity_table();

					ob_start();
					$updater->report();
					$log .= ob_get_contents();
					ob_end_clean();
					$this->println($log);
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

		public function println($s) {
			echo($s . "<br>");
		}
	}
?>
