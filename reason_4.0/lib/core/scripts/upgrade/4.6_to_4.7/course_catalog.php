<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.6_to_4.7']['course_catalog'] = 'ReasonUpgrader_47_CourseCatalog';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('scripts/upgrade/reason_db_helper.php');

class ReasonUpgrader_47_CourseCatalog implements reasonUpgraderInterface
{
	protected $user_id;

	var $catalog_block_type_details = array (
		'new'=>0,
		'unique_name'=>'course_catalog_block_type',
		'custom_content_handler' => 'catalog_block.php',
		'plural_name'=>'Catalog Blocks');

	var $catalog_block_type_schema = array(
		'title' => array('db_type' => 'varchar(128)'),
		'org_id' => array('db_type' => 'varchar(10)'),
		'block_type' => array('db_type' => 'enum("General","Faculty list","Description","Major/Concentration Requirements","Study Abroad","Pertinent Courses","Course Descriptions")'),
		'content' => array('db_type' => 'text'),
		);

	var $relationships = array(
		'course_catalog_block_to_subject' => array (
			'left' => 'course_catalog_block_type',
			'right' => 'subject_type',
			'details' => array(
				'description'=>'Course Catalog Block to Subject',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Subjects',
				'display_name_reverse_direction'=>'Catalog Blocks',
				'description_reverse_direction'=>'Catalog Blocks'
				),
		),
		'page_to_course_catalog_block' => array (
			'left' => 'minisite_page',
			'right' => 'course_catalog_block_type',
			'details' => array(
				'description'=>'Page to Course Catalog Block',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_one',
				'required'=>'no',
				'is_sortable'=>'yes',
				'display_name'=>'Catalog Blocks',
				'display_name_reverse_direction'=>'Pages',
				'description_reverse_direction'=>'Pages'
				),
		),
		/*
		'page_to_subject' => array (
			'left' => 'minisite_page',
			'right' => 'subject_type',
			'details' => array(
				'description'=>'Page to Subject',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'no',
				'display_name'=>'Subjects',
				'display_name_reverse_direction'=>'Pages',
				'description_reverse_direction'=>'Pages'
				),
		),
		*/
	);

	private $rsnDbHelper;

	public function __construct() {
		$this->rsnDbHelper = new ReasonDbHelper();
		$this->rsnDbHelper->setUsername(reason_check_authentication());
	}

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
		return 'Add course catalog support';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = '<p>This upgrade sets up the data structures required to support course catalogs
			in Reason.</p>';
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
	reason_refresh_unique_names();  // force refresh from the database just in case.
	reason_refresh_relationship_names();

	if (!$this->course_template_type_exists())
		return '<p>This script requires that the course types exist. The upgrader for this is in the 4.4 to 4.5 upgrade set.</p>';

	if($this->catalog_block_type_exists() &&
			reason_relationship_name_exists('course_catalog_block_to_subject') &&
			$this->rsnDbHelper->columnExistsOnTable("course_section", "list_of_prerequisites"))
		{
			return '<p>Catalog support is set up. This script has already run.</p>';
		}
		else
		{
			$str = '<p>Would create catalog types and associated relationships.</p>';
			return $str;
		}
	}

    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if($this->catalog_block_type_exists() &&
			reason_relationship_name_exists('course_catalog_block_to_subject'))
		{
			$str = '<p>Catalog support is set up. This script has already run.</p>';
		}
		else
		{
			$str = '';
			if (!$this->catalog_block_type_exists() )
			{
				$str .= $this->create_catalog_block_type();
			}
			$str .= $this->create_catalog_relationships();
			if (!$this->rsnDbHelper->columnExistsOnTable("course_section", "list_of_prerequisites"))
			{
				$str .= $this->add_prereq_field_to_section(false);
			}
			return $str;
		}
		return $str;
	}

	/// FUNCTIONS THAT DO THE CREATION WORK
	protected function create_catalog_block_type()
	{
		$str = '';

		$catalog_block_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Course Catalog Block', $this->catalog_block_type_details);
		$str .= '<p>Create catalog block type entity</p>';
		create_default_rels_for_new_type($catalog_block_type_id);
		create_reason_table('catalog_block', $this->catalog_block_type_details['unique_name'], $this->user_id());

		$ftet = new FieldToEntityTable('catalog_block', $this->catalog_block_type_schema);

		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str .= ob_get_contents();
		ob_end_clean();

		return $str;
	}

	protected function create_catalog_relationships()
	{
		reason_refresh_relationship_names();  // force refresh from the database just in case.
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

	protected function add_prereq_field_to_section($test_mode = false)
	{
		$log = '';
		$field_params = array('list_of_prerequisites' => array('db_type' => 'text'));
		$updater = new FieldToEntityTable('course_section', $field_params);
		$updater->test_mode = $test_mode;
		$updater->update_entity_table();

		ob_start();
		$updater->report();
		$log .= ob_get_contents();
		ob_end_clean();

		return $log;
	}

	/// FUNCTIONS THAT CHECK IF WE HAVE WORK TO DO
	protected function catalog_block_type_exists()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		return reason_unique_name_exists('course_catalog_block_type');
	}

	protected function course_template_type_exists()
	{
		reason_refresh_unique_names();  // force refresh from the database just in case.
		return reason_unique_name_exists('course_template_type');
	}


}
?>