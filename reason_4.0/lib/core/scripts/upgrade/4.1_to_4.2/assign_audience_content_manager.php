<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['assign_audience_content_manager'] = 'ReasonUpgrader_41_AssignAudienceContentManager';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_41_AssignAudienceContentManager implements reasonUpgraderInterface
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
		return 'Assign audience content manager';
	}
	
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>This upgrade sets the audience type to use the new audience content manager.</p>";
		return $str;
	}
	
	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		if($this->content_manager_assigned())
		{
			return '<p>This script has already run.</p>';
		}
		else
		{
			return '<p>Would set the audience type to use the audience content manager.</p>';
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if($this->content_manager_assigned())
		{
			return '<p>This script has already run.</p>';
		}
		else
		{
			// lets set it and we'll set new to 0 at the same time just in case...
			reason_update_entity( id_of('audience_type'), $this->user_id(), array('custom_content_handler' => 'audience.php', 'new' => 0) );
			return '<p>Set the audience type to use the audience content manager.</p>'; 
		}
	}
	
	protected function content_manager_assigned()
	{
		$e = new entity(id_of('audience_type'));
		$cm = $e->get_value('custom_content_handler');
		return (!empty($cm));
	}
}
?>