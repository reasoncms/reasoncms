<?php
/**
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

$GLOBALS['_reason_upgraders']['4.0_to_4.1']['add_admin_tools'] = 'ReasonUpgrader_41_AdminTools';

class ReasonUpgrader_41_AdminTools implements reasonUpgraderInterface
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
		return 'Add Administrative Tools Module and Manage Master Admin Links';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		return 'This upgrader adds a link to the Admin Tools module and offers the removal of lesser-used admin links from the Master Admin.';
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->_link_in_db())
			return 'This script has already run';
		else
			return 'This script will add the Admin Tools module as a link in the Master Admin';
	}
        /**
         * Run the upgrader
         * @return string HTML report
         */
	public function run()
	{
		if($this->_link_in_db())
			return 'This script has already run';
                else
		{
			if($id = reason_create_entity( id_of('master_admin'), id_of('admin_link'), $this->user_id(), 'Admin Tools', array('url'=>'?cur_module=AdminTools','new' => 0) ))
			{
				if(create_relationship( id_of('master_admin'), $id, relationship_id_of('site_to_admin_link')))
					return 'Created the admin tools link and placed on Master Admin sidebar';
				else
					return 'Created the admin tools link but error occurred placing in Master Admin sidebar. You may want to do this manually.';
			}
			else
			{
				return 'Error creating the AdminTools link. You may want to add this link manually, by adding an Admin Link in Master Admin to the URL "?cur_module=AdminTools"';
			}
		}
	}
	protected function _link_in_db()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('admin_link'));
		$es->add_relation('`url` = "?cur_module=AdminTools"');
		$es->set_num(1);
		$results = $es->run_one();
		return !empty($results);
	}

}


?>
