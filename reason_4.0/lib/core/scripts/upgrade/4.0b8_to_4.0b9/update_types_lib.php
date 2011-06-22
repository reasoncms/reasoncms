<?php
/**
 * Reason 4 Beta 8 to Beta 9 type updates.
 *
 * A lot of methods we use to create and manipulate types are includeded. Also see "create_allowable_relationship" in 
 * /reason_package/reason_4.0/lib/core/function_libraries/admin_actions.php
 *
 * Feature type details:
 *
 * - New Reason type (We'll call it "Feature" for the purposes of this document)
 * - unique name should be feature_type
 *
 * Entity Table:
 * feature
 * 
 * Fields:
 * feature.title (tinytext) required
 * feature.text (text)
 * feature.destination_url (URL)
 * feature.show_text (boolean) required
 * feature.crop_style (enum("fill","fit")) required
 * feature.bg_color (6-char hex string)
 *
 * Relationships:
 * feature_to_image (many-to-many -- module will pick image at random)
 * feature_to_media_work (many-to-many -- module will pick media work at random)
 * page_to_feature (many_to_many, sortable)
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include the Reason basic libraries
 */
include ('reason_header.php');
/**
 * Include the db selector utility class
 */
include_once(CARL_UTIL_INC.'db/db_selector.php');
/**
 * Include the sqler utility class
 */
include_once( CARL_UTIL_INC . 'db/sqler.php' );
/**
 * Include various other Reason utilities
 */
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('classes/amputee_fixer.php');
/**
 * Run type updates that are part of the Reason 4.0 beta 8 to 4.0 beta 9 upgrade
 */
class updateTypes
{
	var $mode;
	var $reason_id;
/*
 * feature.title (tinytext) required
 * feature.text (text)
 * feature.destination_url (URL)
 * feature.show_text (boolean) required
 * feature.crop_style (enum("fill","fit")) required
 * feature.bg_color (6-char hex string)	
*/
	var $feature_type_details = array (
		'new'=>0,
		'unique_name'=>'feature_type',
		'custom_content_handler'=>'feature.php',
		'plural_name'=>'Features');
		
	var $feature_table_fields = array(
		'title'=>'tinytext',
		'text'=> 'text',
		'destination_url'=>'tinytext',
		'show_text'=>'boolean',
		'crop_style'=>'enum("fill","fit")',
		'bg_color'=>'varchar(6)'
		);
		
	var $new_event_fields = array(
		'address'=>'tinytext',
		'latitude'=>'double',
		'longitude'=>'double',
		);

	var $geopoint_fields = array(
		'geopoint'=>'point NOT NULL'
		);
		
	var $feature_to_image_details = array (
		'description'=>'Feature to Image',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Attach Image',
		'display_name_reverse_direction'=>'Add to feature',
		'description_reverse_direction'=>'Features using this image');		

	var $feature_to_media_work_details = array (
		'description'=>'Feature to Media Work',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Attach Media Work',
		'display_name_reverse_direction'=>'Add to feature',
		'description_reverse_direction'=>'Features using this media work');		

 // page_to_feature (many_to_many, sortable)
	var $page_to_feature_details = array (
		'description'=>'Page to Feature',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'yes',
		'display_name'=>'Attach Feature',
		'display_name_reverse_direction'=>'Place feature on page',
		'description_reverse_direction'=>'Featured on pages');		
			
	
	function create_feature_type($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
		
		//create feature type entity
		$this->create_feature_type_entity();
		
		// create feature entity table
		$table='feature';
		$type_unique_name=$this->feature_type_details['unique_name'];
		$this->add_new_entity_table_to_type($table, $type_unique_name);
		
		// add the entity table to the feature type
		
		// add fields to the feature entity table
		foreach($this->feature_table_fields as $fname=>$ftype)
		{
			$this->add_field_to_entity_table($table, $fname, $ftype);
		}
		
		// create all the necessary relationships for the feature type
		// feature_to_image (many-to-many -- module will pick image at random)
		$this-> add_allowable_relationship('feature_type','image','feature_to_image',$this->feature_to_image_details);
		
	 	// feature_to_media_work (many-to-many -- module will pick media work at random)
		$this-> add_allowable_relationship('feature_type','av','feature_to_media_work',$this->feature_to_media_work_details);

	 	// page_to_feature (many_to_many, sortable)
		$this-> add_allowable_relationship('minisite_page','feature_type','page_to_feature',$this->page_to_feature_details);
	}
	
	function upgrade_event_type($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
		
		$table = 'event';
		foreach ($this->new_event_fields as $fname=>$ftype)
		{
			$this->add_field_to_entity_table($table, $fname, $ftype);
		}
	}
	
	function create_geocode_data_directory($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
		
		// first check it it exists and is writable - if so, we are probably all good.
		$path = REASON_INC . 'data/geocodes/';
		if (file_exists($path) && is_writable($path)) echo '<p>The geocode data directory exists and is writable - this script has probably been run.</p>';
		elseif ($this->mode == 'test')
		{
			if (!file_exists($path) && is_writable(REASON_INC.'data/'))
			{
				echo '<p>would run mkdir to make this directory - ' . REASON_INC.'data/geocodes/</p>'; 
			}
			elseif (!file_exists($path) && is_writable(REASON_INC.'data/'))
			{
				echo '<p>would require you to manually create the directory ' . REASON_INC.'data/geocodes/ because apache does not have write permissions to the data directory.</p>';
			}
			if (file_exists($path) && !is_writable(REASON_INC.'data/geocodes/'))
			{
				echo '<p>would inform you that you will need to modify the permissions of ' . REASON_INC.'data/geocodes/ so that apache can save geocode data</p>';
			}
		}
		elseif ($this->mode == 'run')
		{
			if (!file_exists($path) && is_writable(REASON_INC.'data/'))
			{
				mkdir(REASON_INC.'data/geocodes');
				echo 'Ran mkdir to make this directory - ' . REASON_INC.'data/geocodes/';
				clearstatcache();
			}
			if (!file_exists($path) && !is_writable(REASON_INC.'data/'))
			{
				echo '<p>You need to manually create the geocode data directory ('.REASON_INC.'data/geocodes/) because apache does not have write permissions to the data directory.</p>';
			}
			if (file_exists($path) && !is_writable(REASON_INC.'data/geocodes/'))
			{
				echo '<p>The geocode directory exists but is not writable by apache - you will need to modify the permissions of the directory ('.REASON_INC.'data/geocodes/) so that apache can save geocode data</p>';
			}
		}
	}
	
	function mysql_supports_triggers_and_spatial_data()
	{
		$qry = 'SELECT version()';
		$result = db_query($qry);
		while ($row = mysql_fetch_assoc($result))
		{
			// lets strip everything but numbers and . characters - ignore beta, alpha, etc
			$version = preg_replace('/[^0-9.]/i','',reset($row));
			return (version_compare($version, '5.0.2', '>='));
		}
		return false;
	}
	
	function lat_and_lon_exist()
	{
		$fields = get_fields_by_content_table('event');
		return (in_array('latitude', $fields) && in_array('longitude', $fields));
	}
	
	function get_insert_trigger_sql()
	{
		$sql = "CREATE TRIGGER geopoint_trigger_insert BEFORE INSERT ON event \n";
		$sql .= "FOR EACH ROW \n";
		$sql .= "SET new.GEOPOINT = GEOMFROMTEXT( CONCAT(  'POINT(', COALESCE(NEW.LATITUDE,0),  ' ', COALESCE(NEW.LONGITUDE,0),  ')' ) )";
		return $sql;
	}

	function get_update_trigger_sql()
	{
		$sql = "CREATE TRIGGER geopoint_trigger_update BEFORE UPDATE ON event \n";
		$sql .= "FOR EACH ROW \n";
		$sql .= "SET new.GEOPOINT = GEOMFROMTEXT( CONCAT(  'POINT(', COALESCE(NEW.LATITUDE,0),  ' ', COALESCE(NEW.LONGITUDE,0),  ')' ) )";
		return $sql;	
	}
	
	function has_create_trigger_privs()
	{
		return false;
	}
	
	/**
	 * There are a variety of requirements for this to work:
	 *
	 * - MySQL must support spatial data and triggers (version 5.0.2 and above)
	 * - MySQL user must have privileges to create triggers
	 * - The event table type must be MyISAM
	 */
	function add_event_binary_data($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		if ($this->mysql_supports_triggers_and_spatial_data() && $this->lat_and_lon_exist())
		{
			$table = 'event';
			foreach ($this->geopoint_fields as $fname=>$ftype)
			{
				$this->add_field_to_entity_table($table, $fname, $ftype);
			}
			
			$index_name = 'geopoint_index';
			$exists = false;
			$qry = 'SHOW INDEX from event';
			$result = db_query($qry);
			
			while ($row = mysql_fetch_assoc($result))
			{
				if ($row['Key_name'] == $index_name)
				{
					$exists = true;
					break;
				}
			}
			if ($exists)
			{
				echo '<p>Index '.$index_name.' exists - script has probably been run. If you are getting errors on event entity creation, your triggers are probably not correctly setup. Run the following ';
				echo 'SQL statements from an account that has privileges to create triggers.</p>';
				$sql1 = $this->get_insert_trigger_sql();
				$sql2 = $this->get_update_trigger_sql();
				echo '<pre>';
				echo $sql1;
				echo "\n\n";
				echo $sql2;
				echo '</pre>';
			}
			elseif ($this->mode == 'test')
			{
				echo '<p>Would populate geopoint fields for all event entities, create a spatial index called '. $index_name . ', and create database triggers.</p>';
			}
			elseif ($this->mode == 'run')
			{
				$qry = "UPDATE event SET geopoint = GeomFromText(concat('POINT(',COALESCE(latitude,0),' ',COALESCE(longitude,0),')'))";
				$result = db_query($qry);
				echo '<p>Set geopoint values for all event entities</p>';
				
				$qry2 = 'CREATE SPATIAL INDEX '.$index_name.' ON event(geopoint)';
				$result2 = db_query($qry2);
				echo '<p>Created spatial index ' . $index_name . ' for geopoint column.</p>';
				
				// lets attempt to create the triggers - if it fails, spit out the appropriate SQL.
				$qry3 = $this->get_insert_trigger_sql();
				$result3 = db_query($qry3, 'could not create insert trigger', false);
				if (!$result3)
				{
					echo '<p>Could not automatically create the insert trigger. Run this SQL as a MySQL user with SUPER or TRIGGER privileges:</p>';
					echo '<pre>';
					echo $this->get_insert_trigger_sql();
					echo '</pre>';
				}
				else echo '<p>Created insert trigger</p>';
				
				$qry4 = $this->get_update_trigger_sql();
				$result4 = db_query($qry4, 'could not create update trigger', false);
				if (!$result4)
				{
					echo '<p>Could not automatically create the update trigger. Run this SQL as a MySQL user with SUPER or TRIGGER privileges:</p>';
					echo '<pre>';
					echo $this->get_update_trigger_sql();
					echo '</pre>';
				}
				else echo '<p>Created update trigger</p>';
			}
		}
		elseif (!$this->lat_and_lon_exist())
		{
			echo '<p>You need to run the event location upgrade script before your can run this script.</p>';
		}
		else
		{
			echo '<p>You need to upgrade to MySQL 5.0.2 or above before you can run this script.</p>';
		}
	}
	
	function fix_policy_content_sorter($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
		
		$policy_type_id = (reason_unique_name_exists('policy_type')) ? id_of('policy_type') : false;
		if ($policy_type_id)
		{
			$policy_type = new entity($policy_type_id);
			$sorter = $policy_type->get_value('custom_sorter');
			
			if (reason_file_exists('content_sorters/policy.php'))
			{
				if ($sorter == 'policy.php')
				{
					echo '<p>The policy type is using the correct content sorter.</p>';
				}
				elseif ($this->mode == 'test')
				{
					echo '<p>Would update the policy type to use the correct content sorter.</p>';
				}
				elseif ($this->mode == 'run')
				{
					reason_update_entity($policy_type_id, $this->reason_id, array('custom_sorter' => 'policy.php'));
					echo '<p>Updated policy type to use the correct content sorter.</p>';
				}
			}
			else echo '<p>The policy.php content sorter was not found in the file system - make sure you have updated all files before running this script.</p>';
		}
		else echo '<p>Your reason instance does not appear to have the policy type so we are not doing anything with it.</p>';
	}
	
	/**
	 * Add Feature to Type table
	 */
	function create_feature_type_entity()
	{
		if (reason_unique_name_exists('feature_type'))
		{
			echo '<p>This script has probably already been run</p>';
		}
		elseif ($this->mode == 'test')
		{
			echo '<p>Would create the feature type</p>';
		}
		elseif ($this->mode == 'run')
		{
			// do it
			echo "Created feature type";
			$feature_id=reason_create_entity(id_of('master_admin'), id_of('type'), $this->reason_id, 'Feature', $this->feature_type_details);
				create_default_rels_for_new_type($feature_id, $this->feature_type_details ['unique_name']);
//			reason_expunge_entity( $feature_id, $this->reason_id );
		}
	}
	
	function add_allowable_relationship($a_side,$b_side,$name,$other_data = array())
	{
		if (reason_relationship_name_exists($name, false))
		{
			echo '<p>'.$name.' already exists. No need to update.</p>'."\n";
			return false;
		}
		if($this->mode == 'run')
		{
			$r_id = create_allowable_relationship(id_of($a_side), id_of($b_side), $name, $other_data);
			if($r_id)
			{
				echo '<p>'.$name.' allowable relationship successfully created</p>'."\n";
			}
			else
			{
				echo '<p>Unable to create '.$name.' allowable relationship</p>';
				echo '<p>You might try creating the relationship '.$name.' yourself in the reason administrative interface'; 
				echo '- it should include the following characteristics:</p>';
				pray ($other_data);
			}
		}
		else
		{
			echo '<p>Would have created '.$name.' allowable relationship.</p>'."\n";
		}
	}
	

////////////////////////////////////////////////////////////////////////////////////////////////
/*
	The following methods are generic, and so are not specific to creating a feature type.
*/
////////////////////////////////////////////////////////////////////////////////////////////////
	
	function fix_amputees()
	{
		if ($this->mode == 'test') echo '<p>Would attempt to fix amputees</p>';
		else
		{
			$fixer = new AmputeeFixer();
        	$fixer->fix_amputees();
        	$fixer->generate_report();
        }
	}
	
	function add_new_entity_table_to_type($table, $type_unique_name)
	{
		$type_id = reason_unique_name_exists($type_unique_name) ? id_of($type_unique_name) : false;
		if ($type_id)
		{
			$tables = get_entity_tables_by_type($type_id, false);
			
			if (!in_array($table, $tables))
			{
				if ($this->mode == 'test') echo '<p>Would create table ' . $table . ' for type ' . $type_unique_name . '</p>';
				else 
				{
					create_reason_table($table, $type_unique_name, $this->reason_id);
					echo '<p>Created table ' . $table . ' for type ' . $type_unique_name . '</p>';
				}
			}
			else
			{
				echo '<p>The table ' . $table . ' for type ' . $type_unique_name . ' already exists.</p>';
			}
		}
	}
	
	/**
	 * Creates entity tables if necessary
	 */
	function add_field_to_entity_table($table, $field_name, $field_db_type)
	{
		// lets make sure the table exists first
		$es = new entity_selector();
        $es->add_type(id_of('content_table'));
        $es->add_relation('entity.name = "'.$table.'"');
        $results = $es->run_one();
        if ($results)
        {
        	if (in_array($field_name, get_fields_by_content_table($table)))
			{
				echo '<p>The '.$table.' entity table already has the field '.$field_name.' - the script has probably been run.</p>';
				return false;
			}
			else
			{
				$updater = new FieldToEntityTable($table, array($field_name => array('db_type' => $field_db_type)));
				if ($this->mode == 'test') $updater->test_mode = true;
				$updater->update_entity_table();
				$updater->report();
			}
		}
	}
	
	function remove_field_from_entity_table($table_name, $field_name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.$table_name.'"');
		$es->set_num(1);
		$result = $es->run_one();
		$table = reset($result);
		
		if ($table)
		{
			$es2 = new entity_selector();
			$es2->add_type(id_of('field'));
			$es2->add_left_relationship($table->id(), relationship_id_of('field_to_entity_table'));
			$es2->add_relation('entity.name = "'.$field_name.'"');
			$es2->set_num(1);
			$result2 = $es2->run_one();
			$field = reset($result2);
		}
		else $field = false;
		
		if ($field && ($this->mode == 'test'))
		{
			
			echo '<p>Would delete field ' . $field_name . '</p>';
		}
		elseif ($field && ($this->mode == 'run'))
		{
			reason_expunge_entity($field->id(), $this->reason_id);
			echo '<p>Deleted field ' . $field_name . '</p>';
		}
		elseif (!$field)
		{
			echo '<p>Could not delete ' . $field_name . ' - field name not found in the entity table.</p>';
		}
		
		// lets remove the column from the entity table
		$q = 'SHOW COLUMNS FROM ' . $table_name;
        $result = db_query($q, 'could not get fields from table ' . $table_name);
        if ($result)
        {
        	while($table = mysql_fetch_assoc($result))
        	{
        		$columns[] = $table['Field'];
        	}
        	if (in_array($field_name, $columns))
        	{
        		if ($this->mode == 'test')
        		{
        			echo '<p>Would drop column ' . $field_name . ' from table ' . $table_name .'</p>';
        		}
        		elseif ($this->mode == 'run')
        		{	
        			$q = 'ALTER TABLE `' . $table_name . '` DROP `' . $field_name . '`';
        			$result = db_query($q, 'could not drop column ' . $field_name . ' from table ' . $table_name);
        			if ($result) echo '<p>Dropped column ' . $field_name . ' from table ' . $table_name .'</p>';
        		}
        	}
        	else
        	{
        		echo '<p>Did not drop column ' . $field_name . ' from the table ' . $table_name . ' because the field is not part of the table</p>';
        	}
        }		
	}
	
	function modify_allowable_relationship($rel_name, $fields)
	{
		
		$rel_id = relationship_id_of($rel_name);
		if ($rel_id)
		{
			$q = 'SELECT * from allowable_relationship WHERE id = ' . $rel_id;
			$results = db_query($q);
			if (mysql_num_rows($results))
			{
				$result = mysql_fetch_assoc($results);
				$needs_update = false;
				foreach ($fields as $k=>$v)
				{
					if ($result[$k] != $v) $needs_update = true;
				}
				if ($needs_update && $this->mode == 'test')
				{
					echo '<p>Would update the allowable relationship ' . $rel_name . '</p>';
				}
				elseif ($needs_update && $this->mode == 'run')
				{
					$sqler = new SQLER();
					$sqler->update_one( 'allowable_relationship', $fields, $rel_id );
					echo '<p>Updated the allowable relationship ' . $rel_name . '</p>';
				}
				else echo '<p>The '.$rel_name.' relationship is up to date. This script has probably been run</p>';
			}
		}
		else echo '<p>The allowable relationship name ' . $rel_name . ' does not exist</p>';
	}
}