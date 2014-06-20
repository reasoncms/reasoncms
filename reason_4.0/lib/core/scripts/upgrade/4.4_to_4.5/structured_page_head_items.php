<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['structured_page_head_items'] = 'ReasonUpgrader_45_StructuredPageHeadItems';

class ReasonUpgrader_45_StructuredPageHeadItems implements reasonUpgraderInterface
{
	protected $user_id;
	protected $helper;
	
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
		return 'Add a field for structured head items on pages';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script adds a new field, extra_head_content_structured, to pages.</p>';
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$entity_table_name = 'page_node';
		$fields = array('extra_head_content_structured' => array('db_type' => 'text'));
		$updater = new FieldToEntityTable($entity_table_name, $fields);
		if($updater->field_exists('extra_head_content_structured'))
		{
			return '<p>This updater has already been run.</p>';
		}
		else
		{
			return '<p>This updater will add the field "extra_head_content_structured" to the page_node table</p>';
		}
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$entity_table_name = 'page_node';
		$fields = array('extra_head_content_structured' => array('db_type' => 'text'));
		$updater = new FieldToEntityTable($entity_table_name, $fields);
		if($updater->field_exists('extra_head_content_structured'))
		{
			return '<p>This updater has already been run.</p>';
		}
		else
		{
			$updater->update_entity_table();
			ob_start();
			$updater->report();
			return ob_get_clean();
		}
	}
}
?>