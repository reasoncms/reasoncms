<?php
/**
 * Upgrader that adds an option to filter spam on Disco forms
 *
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

$GLOBALS['_reason_upgraders']['4.5_to_4.6']['add_spam_filter_field_to_forms'] = 'ReasonUpgrader_46_AddSpamFilterFieldToForms';

class ReasonUpgrader_46_AddSpamFilterFieldToForms implements reasonUpgraderInterface
{
	protected $user_id;

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
		return 'Add apply_akismet_filter field to the form type';
	}

	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This upgrade adds a new field, apply_akismet_filter, to the form entity table. The field will determine whether the form should use the Akismet API to filter submissions.</p>';
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		$entity_table_name = 'form';
		$updater = new FieldToEntityTable($entity_table_name, $this->get_fields_definition());
		$to_return = '';
		if(!$updater->field_exists('apply_akismet_filter'))
		{
			$to_return='<p>This updater will add the field "apply_akismet_filter" to the form table</p>';
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
		$entity_table_name = 'form';
		$updater = new FieldToEntityTable($entity_table_name, $this->get_fields_definition());
		if($updater->field_exists('apply_akismet_filter'))
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
		return array('apply_akismet_filter' => array('db_type' => 'enum("true", "false") DEFAULT NULL'));
	}
}
?>
