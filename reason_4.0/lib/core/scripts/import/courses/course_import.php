<?php
/**
 * @package reason
 * @subpackage scripts
 *
 * This is the framework for the Course Catalog data import. On its own, it doesn't do 
 * anything; the expectation is that an institution would create a local import class
 * that extends this one, and run that on some regular schedule. The comments below explain
 * the points at which you will need to extend the class.
 *
 * For a minimal implementation, you will need to extend get_course_template_data() and
 * get_course_section_data(), and populate the class vars $template_data_map and
 * $section_data_map (and define any mapping methods, as needed). Then just call the run()
 * method to kick off the import.
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/course_functions.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/root_finder.php');

class CourseImportEngine
{	
	/**
	  * Course entities are all stored in a single container site and borrowed into catalog
	  * sites to indicated that they should be published. This value is the unique name of
	  * the site where you want to store your course entities. It can be a public site
	  * (if, for instance, you have a parent site that "contains" your separate catalog
	  * sites) or a hidden site.
	  */
	protected $courses_site = 'catalog_courses_site';

	/**
	  * This script generates a lot of entities. This value defines the user who should be
	  * set as the creator/editor for those entities.
	  */
	protected $entity_creator = 'causal_agent';

	/**
	  * Importing course data requires mapping values from your external system to the 
	  * Reason course data structures. These data maps define how that mapping happens.
	  * You will extend the get_course_template_data() method below to bring in your raw
	  * data array, one row per course. That data is then processed based on the mapping
	  * defined here. On the left are Reason entity fields. On the right can be one of 
	  * three things:
	  * 1. The array key of a value in your raw data row to map that value directly
	  * 2. "X", where template_map_X() is a local method that takes a data row and returns
	  *    the value that should be assigned to the entity field.
	  * 3. A fixed value to be assigned to all entities (or null, to assign no value)
	  */
	protected $template_data_map = array(
			'course_number' => null,
			'org_id' => null,
			'title' => null,
			'short_description' => null,
			'long_description' => null,
			'credits' => null,
			'list_of_prerequisites' => null,
			'status' => 'Active',
			'data_source' => 'myERP',
			'sourced_id' => null,
			);

	/**
	  * See above; this array is the same as $template_data_map, only it applies to course
	  * section data. Custom mapping methods for sections should be named section_map_X().
	  */
	protected $section_data_map = array(
			'course_number' => null,
			'org_id' => null,
			'title' => null,
			'short_description' => null,
			'long_description' => null,
			'credits' => null,
			'list_of_prerequisites' => null,
			'academic_session' => null,
			'timeframe_begin' => null,
			'timeframe_end' => null,
			'location' => null,
			'meeting' => null,
			'notes' => null,
			'status' => 'Active',
			'data_source' => 'myERP',
			'sourced_id' => null,
			'parent_template_id' => null,
			);
	
	/**
	 * Whether to automatically delete course templates that disappear from the source data feed.
	 * @var boolean
	 */
	protected $delete_missing_templates = false;

	/**
	 * Whether to automatically delete course sections that disappear from the source data feed.
	 * @var boolean
	 */
	protected $delete_missing_sections = false;
	
	/**
	  * Import error logging; no customization required.
	  */
	protected $errors = array();
	
	protected $test_mode = true;
	/*
	 * If set to true, import will display extensive progress reporting. If false, only errors
	 * will be shown.
	 */
	protected $verbose = true;
	
	protected $helper;
	
	function __construct()
	{
		$this->helper = new $GLOBALS['catalog_helper_class']();
	}
	
	/**
	 * Run the full course import process. By default it will attempt to process all courses, but
	 * you can pass an array of org_ids to limit import to particular subjects.
	 * 
	 * @param array $org_ids
	 */
	public function run($org_ids = array())
	{	
		connectDB(REASON_DB);
		mysql_set_charset('utf8');
		
		$this->disable_output_buffering();
		if ($this->verbose) echo "<pre>Running\n";
		
		if (empty($org_ids) && !$org_ids = $this->get_template_org_ids()) $org_ids = array(null);
		foreach ($org_ids as $org_id)
		{
			if ($raw_data = $this->get_course_template_data($org_id))
			{
				if ($mapped_data = $this->map_course_template_data($raw_data))
				{
					$this->build_course_template_entities($mapped_data, $this->get_existing_template_ids($org_id), $this->delete_missing_templates);
					unset($raw_data);
					unset($mapped_data);
				}
				else
				{
					$this->errors[] = 'Course template data failed to map for '.$org_id.'.';	
				}
			}
			else
			{
				$this->errors[] = 'No course template data received for '.$org_id.'.';
			}
			echo join("\n", $this->errors);
			$this->errors = array();
		}

		if (!$org_ids = $this->get_section_org_ids()) $org_ids = array(null);
		foreach ($org_ids as $org_id)
		{
			if ($raw_data = $this->get_course_section_data($org_id))
			{
				if ($mapped_data = $this->map_course_section_data($raw_data))
				{
					$this->build_course_section_entities($mapped_data, $this->get_existing_section_ids($org_id), $this->delete_missing_sections);
					unset($raw_data);
					unset($mapped_data);
				}
				else
				{
					$this->errors[] = 'Course section data failed to map for '.$org_id.'.';
				}
			}
			else
			{
				$this->errors[] = 'No course section data received for '.$org_id.'.';	
			}
			echo join("\n", $this->errors);
			$this->errors = array();
		}
		
		echo join("\n", $this->errors);
		if ($this->verbose) echo "Import Complete.\n";
	}

	/**
	 * Retrieves a list of valid 'org_id' values (your department/subject codes).
	 * Typically this is the same for templates and sections, but you can define them
	 * separately if necessary.
	 */
	protected function get_template_org_ids()
	{
		return $this->get_section_org_ids();
	}

	/**
	 * Retrieves a list of valid 'org_id' values (your department/subject codes).
	 * Typically this is the same for templates and sections, but you can define them
	 * separately if necessary.
	 */
	protected function get_section_org_ids()
	{
		return array();
	}
	
	/**
	 * This method does all the work of retrieving your raw course template data from
	 * wherever it lives: via a query into your ERP, a query to a local data mirror,
	 * retrieving data from flat files, etc. The result should be an array with one row
	 * per course. It doesn't matter how that data is structured -- it will be massaged
	 * into shape by the mapping step.
	 *
	 * You should allow for passing an optional org_id value (a department/subject code)
	 * to retrieve only a subset of data.
	 *
	 * @param mixed $org_id
	 */
	protected function get_course_template_data($org_id = null)
	{
		return array();
	}

	/**
	 * This is a set of rules for excluding course templates from being imported. The method is 
	 * passed a single row of data produced by get_course_template_data(), and based on the values
	 * in that row you can pass true or false to indicate whether the row should be discarded.
	 * This is helpful if you need to exclude courses based on values that are difficult to
	 * restrict in your initial query.
	 * 
	 * @param type $row
	 * @return boolean
	 */
	protected function should_exclude_course_template($row)
	{
		return false;
	}

	/**
	 * This method does all the work of retrieving your raw course section data from
	 * wherever it lives: via a query into your ERP, a query to a local data mirror,
	 * retrieving data from flat files, etc. The result should be an array with one row
	 * per section. It doesn't matter how that data is structured -- it will be massaged
	 * into shape by the mapping step.
	 *
	 * You should allow for passing an optional org_id value (a department/subject code)
	 * to retrieve only a subset of data.
	 *
	 * @param mixed $org_id
	 */
	protected function get_course_section_data($org_id = null)
	{
		return array();
	}

	/**
	 * This is a set of rules for excluding course templates from being imported. The method is 
	 * passed a single row of data produced by get_course_template_data(), and based on the values
	 * in that row you can pass true or false to indicate whether the row should be discarded.
	 * This is helpful if you need to exclude courses based on values that are difficult to
	 * restrict in your initial query.
	 * 
	 * @param type $row
	 * @return boolean
	 */
	protected function should_exclude_course_section($row)
	{
		return false;
	}

	/**
	 * Given the 'sourced_id' value of a course template, return the corresponding entity.
	 *
	 * @param mixed ID
	 */
	protected function get_section_parent($parent_template_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('course_template_type'));
		$es->add_relation('sourced_id = "'.$parent_template_id.'"');
		if ($result = $es->run_one())
		{
			return reset($result);	
		}
		return false;
	}
	
	/**
	 * Given a course section entity, create a relationship with its parent course template.
	 *
	 * @param object
	 */
	protected function link_section_to_parent($section)
	{
		if ($template = $this->get_section_parent($section->get_value('parent_template_id')))
		{
			if (!$parents = $section->get_right_relationship('course_template_to_course_section'))
			{
				return create_relationship( $template->id(), $section->id(), relationship_id_of('course_template_to_course_section'),false,false);
			}
			else if (is_array($parents))
			{
				$current_template = reset($parents);

				// verify that we have the correct parent, and fix if not.	
				if ($current_template->get_value('sourced_id') == $template->get_value('sourced_id'))
				{
					return true;	
				}
				else
				{
					$this->errors[] = 'Incorrect template attached to '.$section->get_value('name');	
				}
			}
			else
			{
				$this->errors[] = 'Non-array '.$parents.' returned from get_right_relationship';
			}
		}
		else
		{
			$this->errors[] = 'No template found for '.$section->get_value('name');
			return false;
		}
	}
	
	/**
	 * Given the massaged array of data produced by map_course_template_data(), build or
	 * update the corresponding entities. You can optionally pass an array of all the
	 * existing course template entity ids, to generate a report of entities that have
	 * dropped from the source feed.
	 *
	 * @param array Course data
	 * @param array IDs of existing course template entities.
	 * @param boolean Whether to delete entities dropped from data feed (not recommended)
	 *
	 * @todo Have deletion of templates pay attention to the time range selected
	 */
	protected function build_course_template_entities($data, $existing = array(), $delete = false)
	{
		if ($this->verbose) echo "Building entities\n";
		$creator = get_user_id($this->entity_creator);
		foreach ($data as $key => $row)
		{
			$name = $this->build_course_template_entity_name($row);
			$es = new entity_selector();
			$es->add_type(id_of('course_template_type'));
			$es->add_relation('sourced_id = "'.$row['sourced_id'].'"');
			if ($result = $es->run_one())
			{
				$course = reset($result);
				// Find all the values that correspond to the data we're importing
				$values = array_intersect_assoc($course->get_values(), $row);
				if ($values != $row)
				{
					if ($this->verbose) $this->errors[] = 'Updating '.$name;
					if (!$this->test_mode)
						reason_update_entity( $course->id(), $creator, $row, false);
				}
				
				$key = array_search($course->id(), $existing);
				if ($key !== false) unset($existing[$key]);
			}
			else
			{
				if ($this->verbose) $this->errors[] = 'Adding '.$name;
				$row['new'] = 0;
				$this->process_new_course_template($row);
				if (!$this->test_mode)
					reason_create_entity( id_of($this->courses_site), id_of('course_template_type'), $creator, $name, $row);
			}
		}
		
		if (count($existing))
		{
			foreach ($existing as $id)
			{
				$course = new $GLOBALS['course_template_class']($id);
				$this->errors[] = 'No longer in feed: '.$course->get_value('name');
				if ($delete && !$this->test_mode) reason_expunge_entity($id, $creator);
			}
		}
	}

	/**
	 * This method provides a hook that is run before a new course template is added to Reason.
	 * If you want to modify values based on existing entities, or do something else when you know
	 * that a template is new, you can do that here. The data row representing the template is
	 * passed and updated by reference.
	 * 
	 * @param type $row
	 */
	protected function process_new_course_template(&$row)
	{
		
	}
	
	/**
	  * Construct the template entity name (only visible in the Reason admin). You can modify this 
	  * method if you want your names constructed differently.
	  *
	  * @param array data row
	  * @return string
	  */
	protected function build_course_template_entity_name($row)
	{
		return sprintf('%s %s %s', $row['org_id'], $row['course_number'], $row['title']);
	}

	/**
	 * Given the data assembled by map_course_section_data(), look to see if there's a matching
	 * entity. If there is, update it if any values have changed. If no entity exists,
	 * create one and link it to its parent course template entity.
	 *
	 * @param array data row
	 * @return string
	 * 
	 * @todo Have deletion of sections pay attention to the time range selected
	 */
	protected function build_course_section_entities($data, $existing = array(), $delete = false)
	{
		if ($this->verbose) echo "Building section entities\n";
		$creator = get_user_id($this->entity_creator);
		foreach ($data as $key => $row)
		{
			$es = new entity_selector();
			$es->add_type(id_of('course_section_type'));
			$name = $this->build_course_section_entity_name($row);
			$es->relations = array();
			$es->add_relation('sourced_id = "'.$row['sourced_id'].'"');
			if ($result = $es->run_one())
			{
				$section = reset($result);
				// Find all the values that correspond to the data we're importing
				$values = array_intersect_assoc($section->get_values(), $row);
				if ($values != $row)
				{
					if ($this->verbose) $this->errors[] = 'Updating: '.$name;
					if (!$this->test_mode)
						reason_update_entity( $section->id(), $creator, $row, false);
				}
				else
				{
					if ($this->verbose) $this->errors[] = 'Unchanged: '.$name;	
				}
				
				$key = array_search($section->id(), $existing);
				if ($key !== false) unset($existing[$key]);
			}
			else
			{
				if ($this->get_section_parent($row['parent_template_id']))
				{
					if ($this->verbose) $this->errors[] = 'Adding: '.$name;
					$row['new'] = 0;
					$this->process_new_course_section($row);
					if (!$this->test_mode)
					{
						$id = reason_create_entity( id_of($this->courses_site), id_of('course_section_type'), $creator, $name, $row);
						$section = new entity($id);
					}
				}
				else
				{
					$this->errors[] = 'No course template found; skipping '.$name;
					continue;
				}
			}
			
			if (!empty($section))
				$this->link_section_to_parent($section);
		}
		
		if (count($existing))
		{
			foreach ($existing as $id)
			{
				$course = new $GLOBALS['course_section_class']($id);
				$this->errors[] = 'No longer in feed: '.$course->get_value('course_number').': '.$course->get_value('name');
				if ($delete && !$this->test_mode) reason_expunge_entity($id, $creator);
			}
		}

	}
	
	/**
	 * This method provides a hook that is run before a new course template is added to Reason.
	 * If you want to modify values based on existing entities, or do something else when you know
	 * that a template is new, you can do that here. The data row representing the template is
	 * passed and updated by reference.
	 * 
	 * @param type $row
	 */
	protected function process_new_course_section(&$row)
	{
		
	}

	/**
	  * Construct the section entity name (only visible in the Reason admin). You can modify this 
	  * method if you want your names constructed differently.
	  *
	  * @param array data row
	  * @return string
	  */
	protected function build_course_section_entity_name($row)
	{
		return sprintf('%s %s %s', $row['course_number'], $row['academic_session'], $row['title'] );
	}
	
	/**
	  * This method accepts the raw data array generated in get_course_template_data() and
	  * maps it to the Reason course schema based on the rules found in $this->template_data_map. 
	  * Rules can specify a one-to-one mapping between a source field and a schema field, 
	  * specify a function to be called to perform the mapping, or assign a static value.
	  *
	  * @param array Raw course template data
	  */
	protected function map_course_template_data($data)
	{
		if ($this->verbose) echo "map_course_template_data\n";
		
		foreach($data as $row)
		{
			unset($mapped_row);
			foreach ($this->template_data_map as $key => $mapkey)
			{
				if ($mapkey)
				{
					if (method_exists($this, 'template_map_'.$mapkey))
					{
						$method = 'template_map_'.$mapkey;
						$mapped_row[$key] = $this->$method($row);
					}
					else if (array_key_exists($mapkey, $row))
					{
						$mapped_row[$key] = $row[$mapkey];	
					}
					else
					{
						$mapped_row[$key] = $mapkey;	
					}
				}
			}
			if (isset($mapped_row))
				$mapped_rows[] = $mapped_row;
			else
				$this->errors[] = 'Failed to map course template row';
		}
		if (isset($mapped_rows))
			return $mapped_rows;
		else
		{
			$this->errors[] = 'Failed to map any course template rows';
			return false;
		}
	}

	/**
	  * This method accepts the raw data array generated in get_course_section_data() and
	  * maps it to the Reason course schema based on the rules found in $this->section_data_map. 
	  * Rules can specify a one-to-one mapping between a source field and a schema field, 
	  * specify a function to be called to perform the mapping, or assign a static value.
	  *
	  * @param array Raw course section data
	  */
	protected function map_course_section_data($data)
	{
		if ($this->verbose) echo "map_course_section_data\n";

		foreach($data as $row)
		{
			unset($mapped_row);
			foreach ($this->section_data_map as $key => $mapkey)
			{
				if ($mapkey)
				{
					if (method_exists($this, 'section_map_'.$mapkey))
					{
						$method = 'section_map_'.$mapkey;
						$mapped_row[$key] = $this->$method($row);
					}
					else if (array_key_exists($mapkey, $row))
					{
						$mapped_row[$key] = $row[$mapkey];	
					}
					else
					{
						$mapped_row[$key] = $mapkey;	
					}
				}
			}
			if (isset($mapped_row))
				$mapped_rows[] = $mapped_row;
			else
			{
				//$this->errors[] = 'Failed to map course section row';
				echo 'Failed to map course section row';
			}
		}
		if (isset($mapped_rows))
			return $mapped_rows;
		else
		{
			$this->errors[] = 'Failed to map any course section rows';
			return false;
		}
	}

	/**
	 * Remove all existing course template entities. Used mostly for catalog setup and testing.
	 */
	public function delete_all_course_entities()
	{
		$user = get_user_id($this->entity_creator);
		$ids = $this->get_existing_template_ids();
		foreach ($ids as $id)
		{
			reason_expunge_entity($id, $user);
		}
		unset($ids);
	}
	
	/**
	 * Remove all existing course section entities. Used mostly for catalog setup and testing.
	 */
	public function delete_all_section_entities()
	{
		$user = get_user_id($this->entity_creator);
		$ids = $this->get_existing_section_ids();
		foreach ($ids as $id)
		{
			reason_expunge_entity($id, $user);
		}
		unset($ids);
		
		// Clear any defunct relationships
		$q = 'DELETE FROM relationship WHERE type in ('.  
			relationship_id_of('course_section_to_category') . ',' .
			relationship_id_of('course_template_to_course_section') . ',' .
			relationship_id_of('site_owns_course_section_type') . ',' .
			relationship_id_of('site_borrows_course_section_type') . ')';
		db_query( $q, 'Unable to delete relationships of entity '.$id );
	}
	
	/**
	 * Get all the ids of course template entities, optionally limited by the org_id value on 
	 * the entity.
	 * 
	 * @param string $org_id
	 * @return array
	 */
	protected function get_existing_template_ids($org_id = null)
	{
		$es = new entity_selector();
		$es->add_type(id_of('course_template_type'));
		if ($org_id) $es->add_relation('org_id = "'.mysql_real_escape_string($org_id).'"');
		if ($result = $es->get_ids())
			return $result;
		else
			return array();
	}
	
	/**
	 * Get all the ids of course section entities, optionally limited by the org_id value on 
	 * the entity.
	 * 
	 * @param string $org_id
	 * @return array
	 */
	protected function get_existing_section_ids($org_id = null)
	{
		$es = new entity_selector();
		$es->add_type(id_of('course_section_type'));
		if ($org_id) $es->add_relation('org_id = "'.mysql_real_escape_string($org_id).'"');
		if ($result = $es->get_ids())
			return $result;
		else
			return array();
	}
	
	protected function disable_output_buffering()
	{
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);
		@ini_set('implicit_flush', 1);
		for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
		ob_implicit_flush(1);
	}

	public function get_reason_subjects()
	{
		$subjects = array();
		$es = new entity_selector();
		$es->add_type(id_of('subject_type'));
		if ($result = $es->run_one())
		{
			foreach ($result as $subject)
			{
				$subjects[$subject->get_value('sync_name')] = $subject;
			}
		}
		return $subjects;
	}

	/**
	 * Remove all existing course blocks in a particular catalog site (designated by academic year). 
	 * Used mostly for catalog setup and testing.
	 * 
	 * @param int $year
	 */
	public function delete_all_course_blocks($year)
	{
		if (!$site_id = id_of('academic_catalog_'.$year.'_site'))
		{
			echo 'No site found: academic_catalog_'.$year.'_site';
			return;
		}

		$es = new entity_selector($site_id);
		$es->add_type(id_of('course_catalog_block_type'));
		if ($result = $es->get_ids())
		{
			$user = get_user_id($this->entity_creator);
			foreach ($result as $id)
			{
				reason_expunge_entity($id, $user);
			}
		}
	}
}
