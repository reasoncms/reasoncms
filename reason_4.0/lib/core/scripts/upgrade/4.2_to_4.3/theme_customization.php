<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.2_to_4.3']['theme_customization'] = 'ReasonUpgrader_43_ThemeCustomization';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_43_ThemeCustomization implements reasonUpgraderInterface
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
		return 'Upgrade DB for theme customization';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade adds the theme_customization field to the site table, and adds a theme table with a theme_customizer field.</p>";
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->already_run())
		{
			return '<p>This script has already run.</p>';
		}
		else
		{
			$ret = '';
			if(!$this->site_changes_made())
				$ret .= '<p>Would add the theme_customization field to the site table.</p>';
			if(!$this->theme_changes_made())
			 	$ret .=  '<p>Would add a theme table with a theme_customizer field.</p>';
			return $ret;
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if($this->already_run())
		{
			return '<p>This script has already run.</p>';
		}
		else
		{
			$ret = '';
			if(!$this->site_changes_made())
			{
				$updater = new FieldToEntityTable('site', array('theme_customization' => array('db_type' => 'text')));
				$updater->update_entity_table();
				ob_start();
				$updater->report();
				$ret .= ob_get_flush();
			}
			if(!$this->theme_changes_made())
			{
				$table_id = create_reason_table('theme', 'theme_type', $this->user_id());
				$updater = new FieldToEntityTable('theme', array('theme_customizer' => array('db_type' => 'tinytext')));
				$updater->update_entity_table();
				ob_start();
				$updater->report();
				$ret .= ob_get_flush();
				
			}
			return $ret;
		}
	}
	
	protected function already_run()
	{
		return ($this->site_changes_made() && $this->theme_changes_made());
	}
	protected function site_changes_made()
	{
		$fields = get_fields_by_type( id_of('site') );
		return in_array('theme_customization',$fields);
	}
	protected function theme_changes_made()
	{
		$fields = get_fields_by_type( id_of('theme_type') );
		return in_array('theme_customizer',$fields);
	}
}
?>