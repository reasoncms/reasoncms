<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.4_to_4.5']['setup_profiles'] = 'ReasonUpgrader_46_SetupProfiles';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_46_SetupProfiles implements reasonUpgraderInterface
{
	protected $user_id;
	
	var $profile_type_details = array (
		'new'=>0,
		'unique_name'=>'profile_type',
		'plural_name'=>'Profiles');	

	/**
	 * Edit this if you want your profile type created initially with different fields.
	 */
	var $profile_type_schema = array(
		'user_guid' => array('db_type' => 'varchar(64)'),
		'visibility' => array('db_type' => 'enum("public","local")'),
		'extra_fields' => array('db_type' => 'text'),
		'overview' => array('db_type' => 'text'),
		'professional_history' => array('db_type' => 'text'),
		'highlights' => array('db_type' => 'text'),
		'organizations' => array('db_type' => 'text'),
		'skills' => array('db_type' => 'text'),
		);	
	
	var $relationships = array(
		'profile_to_interest_category' => array (
			'left' => 'profile_type',
			'right' => 'category_type',
			'details' => array(
				'description'=>'Profile to Interest Category',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Interest Categories',
				'display_name_reverse_direction'=>'Profiles (Interest)',
				'description_reverse_direction'=>'Profiles'
				),
		),
		
		'profile_to_personal_interest_category' => array (
			'left' => 'profile_type',
			'right' => 'category_type',
			'details' => array(
				'description'=>'Profile to Personal Interest Category',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Interest Categories',
				'display_name_reverse_direction'=>'Profiles (Interest)',
				'description_reverse_direction'=>'Profiles'
				),
		),

		'profile_to_resume' => array (
			'left' => 'profile_type',
			'right' => 'asset',
			'details' => array(
				'description'=>'Profile to Resume',
				'directionality'=>'unidirectional',
				'connections'=>'one_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Resume',
				'display_name_reverse_direction'=>'Profiles',
				'description_reverse_direction'=>'Profiles'
				),
		),

		'profile_to_image' => array (
			'left' => 'profile_type',
			'right' => 'image',
			'details' => array(
				'description'=>'Profile to Image',
				'directionality'=>'unidirectional',
				'connections'=>'one_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Image',
				'display_name_reverse_direction'=>'Profiles',
				'description_reverse_direction'=>'Profiles'
				),
		),

		'profile_to_external_url' => array (
			'left' => 'profile_type',
			'right' => 'image',
			'details' => array(
				'description'=>'Profile to External URL',
				'directionality'=>'unidirectional',
				'connections'=>'one_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'External URL',
				'display_name_reverse_direction'=>'Profiles',
				'description_reverse_direction'=>'Profiles'
				),
		),

		'page_to_profile' => array (
			'left' => 'minisite_page',
			'right' => 'profile_type',
			'details' => array(
				'description'=>'Page to Profile',
				'directionality'=>'unidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Attach Profiles',
				'display_name_reverse_direction'=>'Pages',
				'description_reverse_direction'=>'Pages'
				),
		),

		'parent_category_to_category' => array (
			'left' => 'category_type',
			'right' => 'category_type',
			'details' => array(
				'description'=>'Parent Category to Child Category',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Add Child Category',
				'display_name_reverse_direction'=>'Add Parent Category',
				'description_reverse_direction'=>'Parent Categories'
				),
		),		
	);		

	
	
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
		return 'Add user profiles';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = '<p>This upgrade sets up the data structures required to support the Reason profiles modules,
			which, in conjunction with the directory services infrastructure, provides user-editable
			personal profiles for your Reason instance.</p>';
		$str .= '<p>This script will create a profile type with the basic fields one might use for a 
			standard academic profile. If you intend to use profiles for other purposes, you may want
			to edit this upgrade script to create different fields; otherwise, you can edit the type
			in the Master Admin once it has been created.</p>';
		$str .= '<p>For profiles to function, you\'ll also need to setup a site that contains pages with
			the profile, profile_explore, and profile_list page type, and then configure config.php according
			to your setup with the proper slugs, unique names, and anything else needed.</p>';
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->profiles_type_exists())
		{
			return '<p>Profiles support is set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->profiles_type_exists()) $str .= '<p>Would create profiles type and associated relationships.</p>';
			return $str;
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if($this->profiles_type_exists())
		{
			$str = '<p>Profiles support is set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->profiles_type_exists())
			{
				$str .= $this->create_profiles_type();
			}
			$str .= $this->create_profiles_relationships();
			return $str;
		}
		return $str;
	}
	
	/// FUNCTIONS THAT DO THE CREATION WORK
	protected function create_profiles_type()
	{
		$str = '';
		
		$profile_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Profile', $this->profile_type_details);
		$str .= '<p>Create profile type entity</p>';
		create_default_rels_for_new_type($profile_type_id);		
		create_reason_table('profile', $this->profile_type_details['unique_name'], $this->user_id());
		
		$ftet = new FieldToEntityTable('profile', $this->profile_type_schema);
			
		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str .= ob_get_contents();
		ob_end_clean();

		if(db_query('ALTER TABLE `profile` ADD INDEX ( `user_guid` )'))
		{
			$str .= '<p>Successfully added index on profile.user_guid</p>';
		}
		else
		{
			$str .= '<p>Attempted to add index on profile.user_guid, but failed.</p>';
		}
		
		return $str;
	}

	protected function create_profiles_relationships()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		$str = '';
		foreach ($this->relationships as $name => $data)
		{
			if (!reason_relationship_name_exists($name))
			{
				create_allowable_relationship(id_of($data['left']),id_of($data['right']), $name, $data['details']);
				$str .= '<p>Created '.$name.' relationship.</p>';
			} else {
				$str .= '<p>'.$name.' relationship already exists.</p>';
			}
		}
		return $str;
	}
	
	/// FUNCTIONS THAT CHECK IF WE HAVE WORK TO DO
	protected function profiles_type_exists()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		return reason_unique_name_exists('profile_type');
	}	
}
?>