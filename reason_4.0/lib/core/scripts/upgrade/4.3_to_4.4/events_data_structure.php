<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/upgrade/denormalization_helper.php');

$GLOBALS['_reason_upgraders']['4.3_to_4.4']['events_data_structure'] = 'ReasonUpgrader_44_EventsDataStructure';

class ReasonUpgrader_44_EventsDataStructure implements reasonUpgraderInterface
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
		return 'Simplify the events data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script flattens the event type for better database performance. Tables moved include meta, dated, chunk, show_hide, duration_time, days_of_week, and url.</p>';
	}
	
	protected function _destination_table()
	{
		return 'event';
	}
	
	protected function _source_tables()
	{
		return array('meta', 'dated', 'chunk', 'show_hide', 'duration_time', 'days_of_week', 'url');
	}

	protected function _type_unique_name()
	{
		return 'event_type';
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