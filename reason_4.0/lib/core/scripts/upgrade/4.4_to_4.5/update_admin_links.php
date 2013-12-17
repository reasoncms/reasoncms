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

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['update_admin_links'] = 'ReasonUpgrader_45_UpdateAdminLinks';

class ReasonUpgrader_45_UpdateAdminLinks implements reasonUpgraderInterface
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
		return 'Clean up admin links';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This updater will remove several admin links from the Master Admin, as they do not need to be regularly run. All of the links are included in Admin Tools, so they are still available -- just less prominent. To be demoted: Delete Duplicate Relationships, Delete Headless Chickens, Delete Widowed Relationships, Fix Amputees, Import Photos, Page Type Information, Remove Duplicate Entities, and Site Structure Analysis.</p>';	
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		
		$results = $this->get_entities_to_delete($this->get_parameter_array());	
		$to_return = '';
		foreach($results as $res_id => $result)
		{
			$to_return.='<p>Will remove '.$result->get_value('name').' from the admin links on master admin.</p>';
		}
		if($to_return=='')
		{
			$to_return = '<p>This updater has already been run.</p>';
		}
		return $to_return;
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$results = $this->get_entities_to_delete($this->get_parameter_array());	
		$to_return = '';
		foreach($results as $res_id => $result)
		{
			delete_relationships(array('entity_a'=>id_of('master_admin'),'entity_b'=>$result->id(),'type'=>relationship_id_of('site_to_admin_link')));
			$to_return.='<p>Removed '.$result->get_value('name').' from the admin links on master admin.</p>';
		}
		if($to_return=='')
		{
			$to_return = '<p>This updater has already been run.</p>';
		}
		return $to_return;
	}

	protected function get_parameter_array()
	{
		return array('unique_name'=>array('delete_duplicate_relationships_admin_link', 'delete_headless_chickens_admin_link', 'delete_widowed_relationships_admin_link', 'fix_amputees_admin_link', 'import_photos_admin_link', 'view_page_type_info_admin_link', 'remove_duplicate_entities_admin_link'),'name'=>array('Site Stucture Analysis'));
	}	
	
	function get_entities_to_delete($parameters)
	{
		$es = new entity_selector();
		$es->add_type(id_of('admin_link'));
		$es->add_right_relationship(id_of('master_admin'),relationship_id_of('site_to_admin_link'));
		$query_str = "(";
		foreach($parameters as $field => $options)
		{
			$query_str.=addslashes($field).' IN (';
			foreach($options as $option)
			{
				$query_str.="'".addslashes($option)."',";
			}
			$query_str = rtrim($query_str, ',');	
			$query_str.=') OR ';
		}
		$query_str = rtrim($query_str, ' OR ');
		$query_str.= ")";
		$es->add_relation($query_str);
		return $es->run_one();
	}	
}
?>
