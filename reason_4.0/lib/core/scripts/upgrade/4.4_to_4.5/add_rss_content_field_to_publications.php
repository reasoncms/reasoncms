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

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_rss_content_field_to_publications'] = 'ReasonUpgrader_45_AddRSSContentFieldToPublications';

class ReasonUpgrader_45_AddRSSContentFieldToPublications implements reasonUpgraderInterface
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
		return 'Adds a field to the blog entity which sets which blog post field that is used in the RSS description output';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script adds a new field, blog_feed_include_content, to the blog entity table. This field is used to determine which blog post field is used in the RSS feed description output.</p>';
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$entity_table_name = 'blog';
		$fields = array('blog_feed_include_content' => array('db_type' => 'tinytext'));
		$updater = new FieldToEntityTable($entity_table_name, $fields);
		if($updater->field_exists('blog_feed_include_content'))
		{
			return '<p>This updater has already been run.</p>';
		}
		else
		{
			return '<p>This updater will add the field "blog_feed_include_content" to the blog table</p>';
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
		$fields = array('blog_feed_include_content' => array('db_type' => 'tinytext'));
		$updater = new FieldToEntityTable($entity_table_name, $fields);
		if($updater->field_exists('blog_feed_include_content'))
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
