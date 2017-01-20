<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['add_Meta_Data_field_to_news'] = 'ReasonUpgrader_47_MetaDataDescription';

class ReasonUpgrader_47_MetaDataDescription implements reasonUpgraderInterface
{
	protected $user_id;

  public function ReasonUpgrader_47_MetaDataDescription()
  {
    $entity_table_name = 'press_release';

    $this->fields = array(
      'meta_description' => array('db_type' => 'text')
    );

    $this->updater = new FieldToEntityTable($entity_table_name, $this->fields);
  }

	public function user_id($user_id = NULL)
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
		return 'Adds Meta Data description field to News / Posts.';
	}

	/**
	 * Get a description of what this upgrade script will do
	 *
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script adds a new field, meta_description, to news / posts.</p>';
	}

  /**
   * Do a test run of the upgrader
   * @return string HTML report
   */
  public function test()
  {
  	if ($this->_test_fields_exist())
  	{
  		return '<p>The fields have already been added.';
  	}
  	else
  	{
  		return '<p>The fields have not yet been added.';
  	}
  }

  protected function _test_fields_exist()
  {
    return $this->updater->field_exists('meta_description');
  }

	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		if($this->_test_fields_exist())
		{
			return '<p>This updater has already been run.</p>';
		}
		else
		{
			$this->updater->update_entity_table();
			ob_start();
			$this->updater->report();
			return ob_get_clean();
		}
	}
}
