<?php
/**
 * Upgrader that adds a display name handler to the field type
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_field_display_name_handler'] = 'ReasonUpgrader_45_AddFieldDisplayNameHandler';

/**
 * Adds a display name handler to the field type
 */
class ReasonUpgrader_45_AddFieldDisplayNameHandler implements reasonUpgraderInterface
{

	protected $user_id;
	protected $field_type_entity;
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
		return 'Add a display name handler for fields';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This upgrade adds a display name handler for the field type, so you can easily see what table a field is part of.</p>';
	}
	
	protected function get_field_type_entity()
	{
		if(!isset($this->field_type_entity))
		{
			$this->field_type_entity = new entity(id_of('field'));
		}
		return $this->field_type_entity;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		$e = $this->get_field_type_entity();
		$dnh = $e->get_value('display_name_handler');
		if(!empty($dnh))
			return '<p>This update has already run.</p>';
		else
			return '<p>This update will add a display name handler for the field type.</p>';
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$e = $this->get_field_type_entity();
		$dnh = $e->get_value('display_name_handler');
		if(!empty($dnh))
			return '<p>This update has already run.</p>';
		else
		{
			if(reason_update_entity($e->id(), $this->user_id(), array('display_name_handler'=>'field.php')))
				return '<p>Added the field display name handler.</p>';
			else
				return '<p>Failed to add the field display name handler. Try again. If you are not successful, you may wish to try to add this handler manually: In Master Admin, edit the Field type and select "Field" as its display name handler.</p>';
		}
	}
}
?>