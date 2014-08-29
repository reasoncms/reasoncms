<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.4_to_4.5']['setup_course_support'] = 'ReasonUpgrader_45_SetupCourseSupport';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_45_SetupCourseSupport implements reasonUpgraderInterface
{
	protected $user_id;
	
	var $course_template_type_details = array (
		'new'=>0,
		'unique_name'=>'course_template_type',
//		'custom_content_handler'=>'course_template.php',
		'plural_name'=>'Courses');	

	var $course_template_type_schema = array(
		'course_number' => array('db_type' => 'varchar(20)'),
		'org_id' => array('db_type' => 'varchar(50)'),
		'title' => array('db_type' => 'tinytext'),
		'short_description' => array('db_type' => 'text'),
		'long_description' => array('db_type' => 'text'),
		'credits' => array('db_type' => 'varchar(50)'),
		'list_of_prerequisites' => array('db_type' => 'text'),
		'status' => array('db_type' => 'enum("Active","Inactive")'),
		'data_source' => array('db_type' => 'varchar(50)'),
		'sourced_id' => array('db_type' => 'varchar(10)'),
		'cache' => array('db_type' => 'text'),
		);	
	
	var $course_section_type_details = array (
		'new'=>0,
		'unique_name'=>'course_section_type',
//		'custom_content_handler'=>'course_template.php',
		'plural_name'=>'Course Sections');

	var $course_section_type_schema = array(
		'course_number' => array('db_type' => 'varchar(20)'),
		'org_id' => array('db_type' => 'varchar(50)'),
		'title' => array('db_type' => 'tinytext'),
		'short_description' => array('db_type' => 'text'),
		'long_description' => array('db_type' => 'text'),
		'credits' => array('db_type' => 'varchar(50)'),
		'academic_session' => array('db_type' => 'varchar(20)'),
		'timeframe_begin' => array('db_type' => 'datetime'),
		'timeframe_end' => array('db_type' => 'datetime'),
		'location' => array('db_type' => 'tinytext'),
		'meeting' => array('db_type' => 'text'),
		'notes' => array('db_type' => 'text'),
		'status' => array('db_type' => 'enum("Active","Inactive")'),
		'data_source' => array('db_type' => 'varchar(50)'),
		'sourced_id' => array('db_type' => 'varchar(10)'),
		'parent_template_id' => array('db_type' => 'varchar(10)'),
		'cache' => array('db_type' => 'text'),
		);	
	
	var $template_to_section_details = array (
		'description'=>'Course Template to Course Section',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_one',
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Course Sections',
		'display_name_reverse_direction'=>'Courses',
		'description_reverse_direction'=>'Courses');	

	var $template_to_page_details = array (
		'description'=>'Course Template to Page',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'yes',
		'display_name'=>'Pages',
		'display_name_reverse_direction'=>'Courses',
		'description_reverse_direction'=>'Courses');	

	var $template_to_category_details = array (
		'description'=>'Course Template to Category',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Categories',
		'display_name_reverse_direction'=>'Courses',
		'description_reverse_direction'=>'Courses');	

	var $section_to_category_details = array (
		'description'=>'Course Section to Category',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Categories',
		'display_name_reverse_direction'=>'Course Sections',
		'description_reverse_direction'=>'Course Sections');	
	
	
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
		return 'Setup course support';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade creates new types for course, subject, department data</p>";
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->course_template_type_exists() && $this->course_section_type_exists())
		{
			return '<p>Course support is set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->course_template_type_exists()) $str .= '<p>Would create course_template type.</p>';
			if (!$this->course_section_type_exists()) $str .= '<p>Would create course_section type.</p>';
			return $str;
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if(!$this->course_template_type_exists() && $this->course_section_type_exists())
		{
			$str = '<p>Course support is set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->course_template_type_exists())
			{
				$str .= $this->create_course_template_type();
			}
			if (!$this->course_section_type_exists())
			{
				$str .= $this->create_course_section_type();
			}
			$str .= $this->create_course_relationships();
			return $str;
		}
		return $str;
	}
	
	/// FUNCTIONS THAT DO THE CREATION WORK
	protected function create_course_template_type()
	{
		$str = '';
		
		$course_template_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Course', $this->course_template_type_details);
		$str .= '<p>Create course template type entity</p>';
		create_default_rels_for_new_type($course_template_type_id);		
		create_reason_table('course_template', $this->course_template_type_details['unique_name'], $this->user_id());
		
		$ftet = new FieldToEntityTable('course_template', $this->course_template_type_schema);
			
		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str .= ob_get_contents();
		ob_end_clean();
		
		//create_relationship( id_of('master_admin'), id_of('social_account_type'), relationship_id_of('site_to_type') );
		return $str;
	}
	
	protected function create_course_section_type()
	{
		$str = '';
		
		$course_section_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Course Section', $this->course_section_type_details);
		$str .= '<p>Create course section type entity</p>';
		create_default_rels_for_new_type($course_section_type_id);		
		create_reason_table('course_section', $this->course_section_type_details['unique_name'], $this->user_id());
		
		$ftet = new FieldToEntityTable('course_section', $this->course_section_type_schema);
			
		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str .= ob_get_contents();
		ob_end_clean();
		
		//create_relationship( id_of('master_admin'), id_of('social_account_type'), relationship_id_of('site_to_type') );
		return $str;
	}

	protected function create_course_relationships()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		$str = '';
		if (!reason_relationship_name_exists('course_template_to_course_section'))
		{
			create_allowable_relationship(id_of('course_template_type'),id_of('course_section_type'),'course_template_to_course_section', $this->template_to_section_details);
			$str .= '<p>Created template to section relationship.</p>';
		}
		if (!reason_relationship_name_exists('course_template_to_page'))
		{
			create_allowable_relationship(id_of('course_template_type'),id_of('minisite_page'),'course_template_to_page', $this->template_to_page_details);
			$str .= '<p>Created template to page relationship.</p>';
		}
		if (!reason_relationship_name_exists('course_template_to_category'))
		{
			create_allowable_relationship(id_of('course_template_type'),id_of('category_type'),'course_template_to_category', $this->template_to_category_details);
			$str .= '<p>Created template to category relationship.</p>';
		}
		if (!reason_relationship_name_exists('course_section_to_category'))
		{
			create_allowable_relationship(id_of('course_section_type'),id_of('category_type'),'course_section_to_category', $this->section_to_category_details);
			$str .= '<p>Created section to category relationship.</p>';
		}
		return $str;
	}
	
	/// FUNCTIONS THAT CHECK IF WE HAVE WORK TO DO
	protected function course_template_type_exists()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		return reason_unique_name_exists('course_template_type');
	}

	protected function course_section_type_exists()
	{
		return reason_unique_name_exists('course_section_type');
	}
	
}
?>