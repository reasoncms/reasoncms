<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['external_url_feed_fields'] = 'ReasonUpgrader_47_ExternaUrlFeedFields';

class ReasonUpgrader_47_ExternaUrlFeedFields implements reasonUpgraderInterface
{
    protected $user_id;

  public function ReasonUpgrader_47_ExternaUrlFeedFields()
  {
    $entity_table_name = 'external_url';

    $this->fields = array(
      'num_posts'      => array('db_type' => 'tinytext'),
      'field_title'    => array('db_type' => 'tinytext'),
      'field_words'    => array('db_type' => 'tinytext'),
      'future_posts'   => array('db_type' => "enum('true','false')"),
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
        return 'Adds fields for External URL (RSS feed) enhancements.';
    }

    /**
     * Get a description of what this upgrade script will do
     *
     * @return string HTML description
     */
    public function description()
    {
        return '<p>This script adds 4 new fields to external URLs which add filtering and other display options.</p>';
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
    return $this->updater->field_exists('num_posts') &&
           $this->updater->field_exists('field_title') &&
           $this->updater->field_exists('field_words') &&
           $this->updater->field_exists('future_posts');
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
