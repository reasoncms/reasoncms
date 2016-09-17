<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/upgrade/denormalization_helper.php');
reason_include_once('classes/field_to_entity_table_class.php');
$GLOBALS['_reason_upgraders']['4.7_to_4.8']['denormalize_text_blurbs'] = 'ReasonUpgrader_48_DenormalizeTextBlurbs';

class ReasonUpgrader_48_DenormalizeTextBlurbs implements reasonUpgraderInterface
{
	protected $helper;

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
		return 'Simplify and upgrade the Text Blurb data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 *
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script creates a text_blurb table and moves the Text Blurb contents of the chunk table into it for better database performance.</p><p>It also adds a class_name field to the new table. </p>';
	}

	protected function _destination_table()
	{
		return 'text_blurb';
	}

	protected function _source_tables()
	{
		return array('chunk');
	}

	protected function _type_unique_name()
	{
		return 'text_blurb';
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
		$message = $helper->test();
		$message .= $this->_test_fields();
		return $message;
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$helper = $this->_get_helper();
		$message = '<h4>Table Move Report</h4>'.$helper->run();
		$message .= $this->_add_fields();
		return $message;

	}
	protected function _test_fields()
	{
		$unadded_fields = array();
		$updater = new FieldToEntityTable('text_blurb');
		foreach($this->_get_fields_to_add() as $field_name=>$field_info)
		{
			if(!$updater->field_exists($field_name))
				$unadded_fields[] = $field_name;
		}
		if(empty($unadded_fields))
		{
			return '<p>All fields added.</p>'."\n";
		}
		return '<p>These fields will be added to the text_blurb type: '.implode(', ',$unadded_fields).'</p>'."\n";
	}
	protected function _add_fields()
	{
		$updater = new FieldToEntityTable('text_blurb', $this->_get_fields_to_add());
		$updater->update_entity_table();
		ob_start();
		$updater->report();
		return ob_get_clean();
	}

	protected function _get_fields_to_add()
	{
		return array('class_name' => array('db_type' => 'text'));
	}
}
?>