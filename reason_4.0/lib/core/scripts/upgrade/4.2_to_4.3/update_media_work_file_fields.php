<?php
/**
 * Upgrader that adds kaltura-related fields to the media work type and maskes the page-to-media-
 * work field sortable.
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

$GLOBALS['_reason_upgraders']['4.2_to_4.3']['update_media_work_file_fields'] = 'ReasonUpgrader_42_UpdateMediaWorkFields';

class ReasonUpgrader_42_UpdateMediaWorkFields implements reasonUpgraderInterface
{

	var $media_work_fields = array(
		'entry_id' => 'tinytext',
		'av_type' => 'enum( "Audio","Video")',
		'media_duration' => 'tinytext',
		'transcoding_status' => 'enum("ready", "converting", "error")',
		'integration_library' => 'tinytext',
		'tmp_file_name' => 'tinytext',
		'email_notification' => 'bool',
		'show_embed' => 'bool DEFAULT 1',
		'show_download' => 'bool DEFAULT 1',
	);
	
	var $media_file_fields = array(
		'flavor_id' => 'tinytext',
		'mime_type' => 'tinytext',
		'download_url' => 'tinytext',
	);
	

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
		return 'Update media work/file fields';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>This upgrade adds fields 'entry_id', 'av_type', 'media_duration', 'transcoding_status', 'integration_library', 'tmp_file_name', 'email_notification', 'show_embed', and 'show_download' to Media Work.  It also adds 'html5' to the enumeration of 'media_format'.  This upgrade also adds the fields 'flavor_id', 'download_url', and 'mime_type' to Media File. It changes the 'required' field of the av_to_av_type allowable relationship to 'no'.  It assigns a content deleter to Media Work.  It changes the content previewer of Media Work to the media_work_previewer.  Lastly, it created an av_restricted_to_group allowable relationship for access control in Kaltura integrated works.</p>";
		return $str;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{		
		$log = $this->add_media_work_fields(true);
		
		$log .= $this->add_media_file_fields(true);
		
		if ($this->updated_allowable_relationship('av_to_av_file', 'required', 'no'))
			$log .= "<p>The av_to_av_file allowable relationship's 'required' field has already been set to 'no'.</p>";
		else
			$log .= "<p>Would update the av_to_av_file allowable realtionship's 'required' field to 'no'.</p>";
		
		if ($this->updated_allowable_relationship('minisite_page_to_av', 'is_sortable', 'yes'))
			$log .= "<p>The minisite_page_to_av allowable relationship's 'is_sortable' field has already been set to 'yes'.</p>";
		else
			$log .= "<p>Would update the minisite_page_to_av allowable realtionship's 'is_sortable' field to 'yes'.</p>";
		
		
		if ($this->updated_media_format_definition())
			$log .= "<p>HTML5 has already been added to the av.media_format's db_type field.</p>";
		else
			$log .= "<p>Would add HTML5 to av.media_format db_type field.</p>";
		
		if ($this->updated_module('av', 'custom_deleter', 'media_work.php'))
			$log .=  "<p>The Media Work's custom deleter has already been updated.</p>";
		else
			$log .= "<p>Would update the Media Work type's custom deleter to media_work.php.</p>";
			
		if ($this->updated_module('av', 'custom_previewer', 'media_work.php'))
			$log .=  "<p>The Media Work's custom previewer has already been updated.</p>";
		else
			$log .= "<p>Would update the Media Work type's custom previewer to media_work.php.</p>";
		
		if ($this->updated_module('av', 'finish_actions', 'media_work_finish.php'))
			$log .=  "<p>The Media Work's finish actions has already been updated.</p>";
		else
			$log .= "<p>Would update the Media Work type's finish actions to media_work_finish.php.</p>";
		
		if ($this->updated_module('av_file', 'custom_previewer', 'media_file.php'))
			$log .=  "<p>The Media File's custom previewer has already been updated.</p>";
		else
			$log .= "<p>Would update the Media File type's custom previewer to media_file.php.</p>";
			
		if ($this->updated_module('av', 'display_name_handler', 'media_work.php'))
			$log .=  "<p>The Media Work's display_name_handler has already been updated.</p>";
		else
			$log .= "<p>Would update the Media Work type's display_name_handler to media_work.php.</p>";
		
		if ($this->created_restricted_group_relationship() == false)
			$log .= '<p>Would create the av_restricted_to_group allowable relationship.</p>'."\n";
		else
			$log .= '<p>Already created the av_restricted_to_group allowable relationship.</p>'."\n";
		
		return $log;
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$log = $this->add_media_work_fields(false);
		
		$log .= $this->add_media_file_fields(false);
		
		if ( !$this->updated_allowable_relationship('av_to_av_file', 'required', 'no') )
		{
			update_allowable_relationship(relationship_id_of('av_to_av_file'), array('required' => 'no', 'is_sortable' => 'yes'));
			$log .= "<p>The av_to_av_file allowable relationship's 'required' field was changed to 'no'.</p>";
		}
		else
		{
			$log .= "<p><p>The av_to_av_file allowable relationship's 'required' field has already been set to 'no'.</p></p>";
		}
		
		if ( !$this->updated_allowable_relationship('minisite_page_to_av', 'is_sortable', 'yes') )
		{
			update_allowable_relationship(relationship_id_of('minisite_page_to_av'), array('is_sortable' => 'yes', 'is_sortable' => 'yes'));
			$log .= "<p>The minisite_page_to_av allowable relationship's 'is_sortable' field was changed to 'yes'.</p>";
		}
		else
		{
			$log .= "<p><p>The minisite_page_to_av allowable relationship's 'is_sortable' field has already been set to 'yes'.</p></p>";
		}
		
		if ($this->updated_media_format_definition())
		{
			$log .= "<p>HTML5 has already been added to the av.media_format's db_type field.</p>";
		}
		else
		{
			if (reason_update_field_definition('av.media_format', "enum('Quicktime','Windows Media','Real','Flash','MP3','AIFF','Flash Video','HTML5')", $this->_user_id) == true)
			{
				$log .= "<p>Added HTML5 to av.media_format's enum. </p>";
			}
			else
			{
				$log .= "<p>Couldn't add HTML5 to av.media_format's enum.</p>";
			}
		}
		
		if ($this->updated_module('av', 'custom_deleter', 'media_work.php'))
		{
			$log .= "<p>Already updated the Media Work type's custom deleter to 'media_work.php'.</p>";
		}
		else
		{
			$this->add_module('av', 'custom_deleter', 'media_work.php');
			$log .= "<p>Updated the Media Work type's custom deleter to 'media_work.php'.</p>";
		}
		
		if ($this->updated_module('av', 'custom_previewer', 'media_work.php'))
		{
			$log .= "<p>Already updated the Media Work type's custom previewer to 'media_work.php'.</p>";
		}
		else
		{
			$this->add_module('av', 'custom_previewer', 'media_work.php');
			$log .= "<p>Updated the Media Work type's custom previewer to 'media_work.php'.</p>";
		}
		
		
		if ($this->updated_module('av', 'finish_actions', 'media_work_finish.php'))
		{
			$log .= "<p>Already updated the Media Work type's finish actions to 'media_work_finish.php'.</p>";
		}
		else
		{
			$this->add_module('av', 'finish_actions', 'media_work_finish.php');
			$log .= "<p>Updated the Media Work type's finish actions to 'media_work_finish.php'.</p>";
		}
		
		if ($this->updated_module('av', 'display_name_handler', 'media_work.php'))
		{
			$log .= "<p>Already updated the Media Work type's display_name_handler to 'media_work.php'.</p>";
		}
		else
		{
			$this->add_module('av', 'display_name_handler', 'media_work.php');
			$log .= "<p>Updated the Media Work type's display_name_handler to 'media_work.php'.</p>";
		}
		
		if ($this->updated_module('av_file', 'custom_previewer', 'media_file.php'))
		{
			$log .= "<p>Already updated the Media File type's custom previewer to 'media_file.php'.</p>";
		}
		else
		{
			$this->add_module('av_file', 'custom_previewer', 'media_file.php');
			$log .= "<p>Updated the Media File type's custom previewer to 'media_file.php'.</p>";
		}
		
		if ($this->created_restricted_group_relationship() == true)
		{
			$log .= "<p>Already created the 'av_restricted_to_group' allowable_relationship.</p>";
		}
		else
		{
			$this->create_restricted_group_relationship();
			$log .= "<p>Created the 'av_restricted_to_group' allowable_relationship.</p>";
		}
		
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
	
	protected function add_media_file_fields($test_mode = false)
	{
		$log = '';
		foreach ($this->media_file_fields as $field => $db_type)
		{
			$field_params = array($field => array('db_type' => $db_type));
			$updater = new FieldToEntityTable('av', $field_params);
			$updater->test_mode = $test_mode;
			$updater->update_entity_table();
			
			ob_start();
			$updater->report();
			$log .= ob_get_contents();
			ob_end_clean();
		}
		return $log;
	}	
	
	protected function updated_media_format_definition()
	{
		$e = new entity_selector();
		$e->add_type(id_of('content_table'));
		$e->add_relation('entity.name = "av"');
		$table = current($e->run_one());
		
		$es = new entity_selector();
		$es->add_type(id_of('field'));
		$es->add_left_relationship($table->id(), relationship_id_of('field_to_entity_table'));
		$es->add_relation('entity.name = "media_format"');
		$field = current($es->run_one());
		
		return strpos(strtolower($field->get_value('db_type')), 'html5') === false ? false : true;
	}
	
	protected function updated_allowable_relationship($rel_name, $field, $val)
	{
		$rel = reason_get_allowable_relationship_info(relationship_id_of($rel_name));
		if ($rel[$field] == $val)
		{
			return true;
		}
		else
			return false;
	}
	
	protected function add_module($unique_name, $module, $name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_relation('entity.unique_name = "'.addslashes($unique_name).'"' );
		$media_work_type = current($es->run_one());
		
		reason_update_entity($media_work_type->id(), $this->_user_id, array($module => $name));
	}
	
	protected function updated_module($unique_name, $module, $name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_relation('entity.unique_name = "'.addslashes($unique_name).'"' );
		$media_work_type = current($es->run_one());
		
		if ($media_work_type->get_value($module) == $name)
		{
			return true;
		}
		else
			return false;
	}
	
	protected function created_restricted_group_relationship()
	{
		$dbq = new DBSelector;
		$dbq->add_table( 'ar','allowable_relationship' );
		$dbq->add_relation( 'ar.name = "av_restricted_to_group"' );
		$q = $dbq->run();
		if (empty($q))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	protected function create_restricted_group_relationship()
	{
		$args = array(
			'description'=>'Used for media access control.',
			'connections'=>'one_to_many',
			'display_name'=>'Restricted to Group',
			'custom_associator'=>'Content Manager',
		);
		create_allowable_relationship(id_of('av'), id_of('group_type'), 'av_restricted_to_group', $args);	
	}
	
	
	
	
	
}
?>