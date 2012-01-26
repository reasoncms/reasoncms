<?php
/**
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.0_to_4.1']['admin_link_dynamic_site_id'] = 'ReasonUpgrader_41_AdminLinkSiteID';

class ReasonUpgrader_41_AdminLinkSiteID implements reasonUpgraderInterface
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
		return 'Add Dynamic Site ID Option to Administrative Link Content Manager';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		return 'This upgrader adds an option when creating admin links to dynamically add the current site ID to the link. The default value is false. If set to true, and a site_id is also directly embedded in the URL, any dynamic site id will win.';
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->_dynamic_site_id_option_exists())
			return 'This script has already run.';
		else
			return 'This script will add a "dynamic site id" option to the administrative link content manager.';
	}
    
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if($this->_dynamic_site_id_option_exists())
		{
			return 'This script has already run.';
		}
		else
		{
			if ($this->_add_dynamic_site_id_option())
			{
				return 'Added dynamic site id option to the administrative link content manager.';
			}
			else
			{
				return 'Failed to add dynamic site id option to the administrative link content manager.';
			}
		}
	}
	
	/**
	 * @return boolean
	 */
	protected function _add_dynamic_site_id_option()
	{
		$entity_table_name = 'admin_link';
		$fields = array('add_dynamic_site_id' => array('db_type' => "enum('true','false')"));
		$updater = new FieldToEntityTable($entity_table_name, $fields);
		$result = $updater->update_entity_table();
		$updater->report();
		return $result;
	}
	
	protected function _dynamic_site_id_option_exists()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('admin_link'));
		$es->set_num(1);
		$result = $es->run_one();
		if ($result)
		{
			$ret = reset($result);
			$values = $ret->get_values();
			return array_key_exists('add_dynamic_site_id', $values);
		}
		return false;
	}

}


?>
