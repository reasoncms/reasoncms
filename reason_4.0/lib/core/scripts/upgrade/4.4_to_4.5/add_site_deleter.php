<?php
/**
 * Upgrader that adds a custom deleter to the site type
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

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_site_deleter'] = 'ReasonUpgrader_45_AddSiteDeleter';

/**
 * Adds a custom deleter to the site type
 */
class ReasonUpgrader_45_AddSiteDeleter implements reasonUpgraderInterface
{

	protected $user_id;
	protected $site_type_entity;
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
		return 'Add a custom deleter for sites';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This upgrade adds a deleter for the site type, so that whole sites can be more readily deleted.</p>';
	}
	
	protected function get_site_type_entity()
	{
		if(!isset($this->site_type_entity))
		{
			$this->site_type_entity = new entity(id_of('site'));
		}
		return $this->site_type_entity;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		$e = $this->get_site_type_entity();
		$deleter = $e->get_value('custom_deleter');
		if(!empty($deleter))
			return '<p>This update has already run.</p>';
		else
			return '<p>This update will add a deleter for the site type.</p>';
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$e = $this->get_site_type_entity();
		$deleter = $e->get_value('custom_deleter');
		if(!empty($deleter))
			return '<p>This update has already run.</p>';
		else
		{
			if(reason_update_entity($e->id(), $this->user_id(), array('custom_deleter'=>'site.php')))
				return '<p>Added the site deleter.</p>';
			else
				return '<p>Failed to add the site deleter. Try again. If you are not successful, you may wish to try to add this handler manually: In Master Admin, edit the Site type and select "Site" as its custom deleter.</p>';
		}
	}
}
?>