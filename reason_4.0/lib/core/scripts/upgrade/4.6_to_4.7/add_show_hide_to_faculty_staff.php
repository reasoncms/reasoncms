<?php
	
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['add_show_hide_to_faculty_staff'] = 'ReasonUpgrader_47_AddShowHideFacultyStaff';

class ReasonUpgrader_47_AddShowHideFacultyStaff implements reasonUpgraderInterface
{
	protected $user_id;
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
		return 'Adds show_hide field to Faculty/Staff type';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>This upgrade adds the field show_hide to the Faculty/Staff type. This field allows administrators to suppress faculty/staff items.</p>";
		return $str;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{	
		$log = $this->add_show_hide_field(true);
		return $log;
	}
	
        /**
         * Run the upgrader
         * @return string HTML report
         */
	public function run()
	{
		$log = $this->add_show_hide_field(false);
		return $log;
	}

	protected function add_show_hide_field($test_mode = false)
	{
		$log = '';
		$field_params = array('show_hide' => array('db_type' => 'enum("show","hide")'));
		$updater = new FieldToEntityTable('faculty_staff', $field_params);
		$updater->test_mode = $test_mode;
		$updater->update_entity_table();
		
		ob_start();
		$updater->report();
		$log .= ob_get_contents();
		ob_end_clean();

		return $log;
	}
}

?>
