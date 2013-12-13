<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_rss_and_reminder_fields_to_publications'] = 'ReasonUpgrader_45_AddRSSAndReminderFieldsToPublications';

class ReasonUpgrader_45_AddRSSAndReminderFieldsToPublications implements reasonUpgraderInterface
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
		return 'Add blog_feed_include_content, reminder_emails, and reminder_days fields to the publication type';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script adds three new fields, blog_feed_include_content, reminder_days, and reminder_emails to the blog entity table. blog_feed_include_content is used to determine which blog post field is used in the RSS feed description output. reminder_days and reminder_emails allow Reason to send out reminders to emails if a publication has not been posted in for a set number of days.</p>';
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$entity_table_name = 'blog';
		$updater = new FieldToEntityTable($entity_table_name, $this->get_fields_definition());
		$to_return = '';
		if(!$updater->field_exists('blog_feed_include_content'))
		{
			$to_return='<p>This updater will add the field "blog_feed_include_content" to the blog table</p>';
		}
		if(!$updater->field_exists('reminder_days'))
		{
			$to_return.= '<p>This updater will add the field "reminder_days" to the blog table</p>';
		}		
		if(!$updater->field_exists('reminder_emails'))
		{
			$to_return.='<p>This updater will add the field "reminder_emails" to the blog table</p>';
		}
		if(empty($to_return))
		{
			return '<p>This updater has already been run.</p>';
		}
		else
		{
			return $to_return;
		}
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$entity_table_name = 'blog';
		$updater = new FieldToEntityTable($entity_table_name, $this->get_fields_definition());
		if($updater->field_exists('blog_feed_include_content')&&$updater->field_exists('reminder_days')&&$updater->field_exists('reminder_emails'))
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
	
	protected function get_fields_definition()
	{
		return array('blog_feed_include_content' => array('db_type' => 'enum("yes","no")'),'reminder_days' => array('db_type' => 'smallint unsigned'),'reminder_emails' => array('db_type' => 'tinytext'));
	}
}
?>
