<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/upgrade/denormalization_helper.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['minutes_data_structure'] = 'ReasonUpgrader_42_MinutesDataStructure';

class ReasonUpgrader_42_MinutesDataStructure implements reasonUpgraderInterface
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
		return 'Simplify the Minutes data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script moves the Minutes contents of the bigger_chunk, meta, and dated tables into the minutes table for better database performance.</p>';
	}
	
	protected function _destination_table()
	{
		return 'minutes';
	}
	
	protected function _source_tables()
	{
		return array('meta','dated','bigger_chunk');
	}
	
	protected function _type_unique_name()
	{
		return 'minutes_type';
	}
	protected function _get_helper()
	{
		if(!isset($this->helper))
		{
			$this->helper = new denormalizationUpgradeHelper();
			$this->helper->user_id($this->user_id());
			$this->helper->destination_table($this->_destination_table());
			$this->helper->source_tables($this->_source_tables());
			$this->helper->type_unique_name($this->_type_unique_name());
		}
		return $this->helper;
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$helper = $this->_get_helper();
		return $helper->test();
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$helper = $this->_get_helper();
		return $helper->run();
	}
}
?>