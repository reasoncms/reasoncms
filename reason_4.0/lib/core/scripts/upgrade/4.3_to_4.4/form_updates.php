<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.3_to_4.4']['form_updates'] = 'ReasonUpgrader_44_FormUpdates';

class ReasonUpgrader_44_FormUpdates implements reasonUpgraderInterface
{
	protected $user_id;
	protected $helper;
	protected $new_fields = array(
		'tableless' => 'bool default 0',
		'submission_limit' => 'int',
		'open_date' => 'datetime',
		'close_date' => 'datetime',
	);
	
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->user_id = $user_id;
		else
			return $this->user_id;
	}
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Add fields for form enhancements to the form type.';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		$str  = '<p>This script adds new fields (tableless, submission_limit, open_date, and close_date) to the form type to support new form features in Reason 4.4.</p>';
		return $str;
	}

    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		return $this->add_fields(true);
	}
	
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		return $this->add_fields();
	}
	
	protected function add_fields($test_mode = false)
	{
		$log = '';
		foreach ($this->new_fields as $field => $db_type)
		{
			$field_params = array($field => array('db_type' => $db_type));
			$updater = new FieldToEntityTable('form', $field_params);
			$updater->test_mode = $test_mode;
			$updater->update_entity_table();
			
			ob_start();
			$updater->report();
			$log .= ob_get_contents();
			ob_end_clean();
		}
		return $log;
	}	
}
?>