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
		$admin_links = array('delete_duplicate_relationships_admin_link', 'delete_headless_chickens_admin_link', 'delete_widowed_relationships_admin_link', 'fix_amputees_admin_link', 'import_photos_admin_link', 'view_page_type_info_admin_link', 'remove_duplicate_entities_admin_link');
		$to_return = '';
		foreach ($admin_links as $admin_link)
		{
			$to_return.=$this->get_admin_link_relationship($admin_link);
		}
		$es = new entity_selector;
		$es->add_type(id_of('admin_link'));
		$results = $es->run_one();
		foreach($results as $res_id => $result)
		{
			$name = $result->get_value('name');
			if($name=='Site Stucture Analysis')
			{
				$to_return.=$this->get_admin_link_relationship($result->id(),$name);
			}
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
		$admin_links = array('delete_duplicate_relationships_admin_link', 'delete_headless_chickens_admin_link', 'delete_widowed_relationships_admin_link', 'fix_amputees_admin_link', 'import_photos_admin_link', 'view_page_type_info_admin_link', 'remove_duplicate_entities_admin_link');
		$to_return = '';
		foreach ($admin_links as $admin_link)
		{
			$to_return.=$this->remove_admin_link_relationship($admin_link);
		}
		$es = new entity_selector;
		$es->add_type(id_of('admin_link'));
		$results = $es->run_one();
		foreach($results as $res_id => $result)
		{
			if($result->get_value('name')=='Site Stucture Analysis')
			{	
				$to_return.=$this->remove_admin_link_relationship($result->id());
			}
		}	
		if($to_return=='')
		{
			$to_return = '<p>This updater has already been run.</p>';
		}
		return $to_return;
		
	}
	
	protected function remove_admin_link_relationship($admin_link,$name='')
	{
		$admin_text = $admin_link;
		$es = new entity_selector;
		$es->add_type(id_of('site'));
		if(!is_numeric($admin_link))
		{	
			$admin_link = id_of($admin_link);
		}
		if(empty($admin_link))
			return '';
		
		$es->add_left_relationship($admin_link,relationship_id_of('site_to_admin_link'));
		$results = $es->run_one();
		$master_admin_found = False;
		foreach($results as $result_id=>$object)
		{
			if($object->id()==id_of('master_admin'))
			{
				$master_admin_found = True;
			}
		}
		if($master_admin_found)
		{
			if(!empty($name))
			{
				$admin_text = $name;
			}
			delete_relationships(array('entity_a'=>id_of('master_admin'),'entity_b'=>$admin_link,'type'=>relationship_id_of('site_to_admin_link')));
			return '<p>'.$admin_text.' deleted</p>';
		}	
		return '';
	}
	
	protected function get_admin_link_relationship($admin_link,$name='')
	{
		$admin_text = $admin_link;
		$es = new entity_selector;
		$es->add_type(id_of('site'));
		if(!is_numeric($admin_link))
		{	
			$admin_link = id_of($admin_link);
		}
		if(empty($admin_link))
			return '';
		
		$es->add_left_relationship($admin_link,relationship_id_of('site_to_admin_link'));	
		$results = $es->run_one();
		$master_admin_found = False;
		foreach($results as $result_id=>$object)
		{
			if($object->id()==id_of('master_admin'))
			{
				$master_admin_found = True;
			}
		}
		if($master_admin_found)
		{
			if(!empty($name))
			{
				$admin_text=$name;
			}
			return '<p>'. $admin_text.' will be deleted</p>';
		}	
		else
		{
			return '';
		}
	}		
}
?>
