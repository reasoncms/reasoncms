<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_page_indexable_flag'] = 'ReasonUpgrader_45_PageIndexableUpdate';

class ReasonUpgrader_45_PageIndexableUpdate implements reasonUpgraderInterface
{
	protected $user_id;
	protected $helper;
	protected $new_fields = array(
		'indexable' => 'bool default 1',
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
		return 'Add field to manage page visibility to search engines.';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		$str  = '<p>This script adds a new field to pages to allow specifying whether the page should be visible to search engines.</p>';
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
			$updater = new FieldToEntityTable('page_node', $field_params);
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