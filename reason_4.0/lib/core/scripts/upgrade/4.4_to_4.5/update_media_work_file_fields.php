<?php
/**
 * Upgrader that adds storage-related fields to media works.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['update_media_work_file_fields'] = 'ReasonUpgrader_44_UpdateMediaWorkFields';

class ReasonUpgrader_44_UpdateMediaWorkFields implements reasonUpgraderInterface
{
	var $media_work_fields = array(
		'original_filename' => 'tinytext',
		'salt' => 'tinytext',
	);
	// TODO: add 'finalizing' to 'transcoding_status' enum field

	protected $user_id;
	public function user_id( $user_id = NULL)
	{
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
		return 'Update media work storage-related fields';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>This upgrade adds fields 'original_filename' and 'salt' to Media Work entities. These fields are used for file storage-related tasks such as constructing storage paths.</p>";
		return $str;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{	
		$log = $this->add_media_work_fields(true);
		$log .= $this->modify_transcoding_status(true);
		return $log;
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$log = $this->add_media_work_fields(false);
		$log .= $this->modify_transcoding_status(false);
		return $log;
	}
	
	protected function add_media_work_fields($test_mode = false)
	{
		$log = '';
		foreach ($this->media_work_fields as $field => $db_type)
		{
			$field_params = array($field => array('db_type' => $db_type));
			$updater = new FieldToEntityTable('media_work', $field_params);
			$updater->test_mode = $test_mode;
			$updater->update_entity_table();
			
			ob_start();
			$updater->report();
			$log .= ob_get_contents();
			ob_end_clean();
		}
		return $log;
	}
	
	protected function modify_transcoding_status($test_mode = false)
	{
		$log = '';
		$q = 'SHOW COLUMNS FROM `media_work`;';
		$result = db_query( $q, 'There was a problem with SHOW COLUMNS... Probably a syntax error.');
		
		if ($this->transcoding_status_exists($result))
		{
			if ($this->finalizing_enum_exists($result))
			{
				$log .= '<p>\'finalizing\' is already in the transcoding_status enum.</p>'."\n";
			}
			else
			{
				$log .= $this->add_finalizing_enum($test_mode);
			}
		}
		else
		{
			$log .= '<p>The transcoding_status field does not exist in the media_work table. It will be added.</p>'."\n";
			$log .= $this->add_transcoding_status_field($test_mode);
		}	
		return $log;
	}
	
	// Checks for existence of 'transcoding_status' field in media_work table.
	protected function transcoding_status_exists($mysql_result)
	{
		while ($row = mysql_fetch_assoc($mysql_result)) 
		{
			if ($row['Field'] == 'transcoding_status') 
			{
				return true;
			}
		}
		return false;
	}

	// Checks for existence of 'finalizing' in the 'transcoding_status' enum.
	protected function finalizing_enum_exists($mysql_result)
	{
		mysql_data_seek($mysql_result, 0);
		while ($row = mysql_fetch_assoc($mysql_result)) 
		{
			if ($row['Field'] == 'transcoding_status') 
			{
				if (strpos($row['Type'],'finalizing') !== false)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}
		return false;
	}
	
	// Adds the whole transcoding_status field.
	protected function add_transcoding_status_field($test_mode = false)
	{
		$log = '';
		$field_params = array('transcoding_status' => array('db_type' => 'enum("ready", "converting", "error", "finalizing")'));
		$updater = new FieldToEntityTable('media_work', $field_params);
		$updater->test_mode = $test_mode;
		$updater->update_entity_table();
		
		ob_start();
		$updater->report();
		$log .= ob_get_contents();
		ob_end_clean();

		return $log;
	}
	
	// Modifies the transcoding_status to add 'finalizing' to the enum definition
	protected function add_finalizing_enum($test_mode = false)
	{
		if ($test_mode)
		{
			return '<p>\'finalizing\' will be added to the transcoding_status enum.</p>'."\n";
		}
		
		$q = 'ALTER TABLE `media_work` MODIFY COLUMN `transcoding_status` ENUM("ready", "converting", "error", "finalizing");';
		$result = db_query( $q, 'There was a problem with altering the `media_work` `transcoding_status` column definition... Probably a syntax error.');
		if ($result == 1)
		{
			$es = new entity_selector();
			$es->add_type(id_of('field'));
			$es->add_relation('entity.name = "transcoding_status"');
			$field_entity = current($es->run_one());
			reason_update_entity($field_entity->id(), $this->_user_id, array('db_type' => 'enum("ready", "converting", "error", "finalizing")'), false);
			
			return '<p>\'finalizing\' was added to the \'transcoding_status\' field\'s enum.</p>'."\n";
		}
		else
		{
			return $result;
		}
	}
}
?>