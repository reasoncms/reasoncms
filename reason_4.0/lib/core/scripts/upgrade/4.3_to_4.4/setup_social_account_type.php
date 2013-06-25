<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.3_to_4.4']['setup_social_account_type'] = 'ReasonUpgrader_44_SetupSocialAccountType';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_44_SetupSocialAccountType implements reasonUpgraderInterface
{
	protected $user_id;
	
	var $social_account_type_details = array (
		'new'=>0,
		'unique_name'=>'social_account_type',
		'custom_content_handler'=>'social_account.php',
		'plural_name'=>'Social Accounts');
	
	var $site_to_social_account_details = array (
		'description'=>'Site to Social Account',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Associate Social Account',
		'display_name_reverse_direction'=>'Associate with Site',
		'description_reverse_direction'=>'Sites using this Social Account');	
		
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
		return 'Setup Social Account Type';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade creates a new type, social account, which powers social sharing in Reason.</p>";
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->social_account_type_exists() && $this->publication_social_sharing_field_exists())
		{
			return '<p>Social sharing is setup. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->social_account_type_exists()) $str .= '<p>Would create social account type.</p>';
			if (!$this->publication_social_sharing_field_exists()) $str .= '<p>Would add enable_social_sharing checkbox to publications.</p>';
			return $str;
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if($this->social_account_type_exists() && $this->publication_social_sharing_field_exists())
		{
			$str = '<p>Social sharing is setup. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->social_account_type_exists())
			{
				$str .= $this->create_social_account_type();
			}
			if (!$this->publication_social_sharing_field_exists())
			{
				$str .= $this->create_publication_social_sharing_field();
				$str .= '<p>Added enable_social_sharing checkbox to publications.</p>';
			}
			return $str;
		}
		return $str;
	}
	
	/// FUNCTIONS THAT DO THE CREATION WORK
	protected function create_social_account_type()
	{
		$str = '';
		
		$social_account_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Social Account', $this->social_account_type_details);
		$str .= '<p>Create social account type entity</p>';
		create_default_rels_for_new_type($social_account_type_id);		
		create_reason_table('social_account', $this->social_account_type_details['unique_name'], $this->user_id());
		
		$ftet = new FieldToEntityTable('social_account', array(
			'account_type' => array('db_type' => 'tinytext'),
			'account_id' => array('db_type' => 'tinytext'),
			'account_details' => array('db_type' => 'text')));
			
		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str .= ob_get_contents();
		ob_end_clean();	
		
		create_allowable_relationship(id_of('site'),id_of('social_account_type'),'site_to_social_account', $this->site_to_social_account_details);
		$str .= '<p>Created site to social account relationship.</p>';
		
		create_relationship( id_of('master_admin'), id_of('social_account_type'), relationship_id_of('site_to_type') );
		return $str;
	}
	
	protected function create_publication_social_sharing_field()
	{
		$ftet = new FieldToEntityTable('blog', array('enable_social_sharing' => array('db_type' => "enum('yes','no')")));
		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str = ob_get_contents();
		ob_end_clean();
		return $str;
	}
	
	/// FUNCTIONS THAT CHECK IF WE HAVE WORK TO DO
	protected function social_account_type_exists()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		return reason_unique_name_exists('social_account_type');
	}
	
	protected function publication_social_sharing_field_exists()
	{
		$fields = get_fields_by_type( id_of('publication_type') );
		return isset($fields['enable_social_sharing']);
	}
}
?>