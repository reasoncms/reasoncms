<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.5_to_4.6']['site_title_tag_pattern_fields'] = 'ReasonUpgrader_46_TitleTagFields';

class ReasonUpgrader_46_TitleTagFields implements reasonUpgraderInterface
{
	protected $user_id;

  public function ReasonUpgrader_46_TitleTagFields()
  {
    $entity_table_name = 'site';

    $this->fields = array(
      'home_title_pattern'      => array('db_type' => 'tinytext'),
      'secondary_title_pattern' => array('db_type' => 'tinytext'),
      'item_title_pattern'      => array('db_type' => 'tinytext'),
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
		return 'Adds fields for title tag customization.';
	}

	/**
	 * Get a description of what this upgrade script will do
	 *
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script adds 3 new fields, home_title_pattern, to sites.</p>';
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
    return $this->updater->field_exists('home_title_pattern') &&
           $this->updater->field_exists('secondary_title_pattern') &&
           $this->updater->field_exists('item_title_pattern');
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
