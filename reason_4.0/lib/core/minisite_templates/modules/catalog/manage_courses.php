<?php
/**
 * Catalog: Manage Courses
 *
 * Module for Managing Courses in a Course Catalog site
 *
 * @author Mark Heiman
 * @since 2014-08-20
 * @package MinisiteModule
 *
 * @todo have a settings file that determines which fields are editable, and which are
 * stored on the template or section.
 *
 * NOTES:
 *
 * Course templates are stored on a protected courses site (catalog_courses_site).
 *
 * Visibility of a course in a catalog is determined by whether the course is borrowed into the site.
 */
$GLOBALS[ '_module_class_names' ][ 'catalog/'.basename( __FILE__, '.php' ) ] = 'ManageCoursesModule';

reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/course_functions.php' );

class ManageCoursesModule extends DefaultMinisiteModule
{
	protected $courses = array();
	protected $subjects = array();
	protected $site_subjects = array();
	protected $page_categories = array();
	protected $form;
	protected $course;
	protected $section;
	protected $year;
	protected $catalog_site_id;
	protected $noaccess;
	public $cleanup_rules = array(
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		'subject' => array( 'function' => 'turn_into_string' ),
		'course' => array( 'function' => 'turn_into_int' ),
		'section' => array( 'function' => 'turn_into_int' ),
		'year' => array( 'function' => 'turn_into_int' ),
		'activate' => array( 'function' => 'turn_into_int' ),
		'deactivate' => array( 'function' => 'turn_into_int' ),
		'toggle_course' => array( 'function' => 'turn_into_string' ),
		);
	

	protected $elements = array(//{{{
		'course_info'=>array(
			'type'=>'comment',
			),
		'sections'=>array(
			'type'=>'checkboxgroup_no_sort',
			),
		'subject'=>array(
			'type'=>'select',
			),
		'course_number'=>array(
			'type'=>'text',
			'size'=>6,
			'display_name'=>'Number',
			),
		'title'=>array(
			'type'=>'text',
			'size'=>100,
			),
		'description'=>array(
			'type'=>'tiny_mce',
			'buttons' => array('bold','italic','underline','code'),
			'init_options' => array('plugins' => 'code'),
			'cols' => 80,
			'rows' => 8,
			),
		'prerequisites'=>array(
			'type'=>'textarea',
			'rows' =>3,
			),
		'faculty'=>array(
			'type'=>'text',
			'size'=>50,
			),
		'requirements'=>array(
			'type'=>'solidtext',
			),
		'grading'=>array(
			'type'=>'solidtext',
			'default'=>'GRADED',
			),
		'credits'=>array(
			'type'=>'text',
			'size'=>5,
			),
		'display_in_catalog'=>array(
			'type'=>'checkboxfirst',
			),
		);
	
	protected $required = array();
	
	/**
	  * These are elements whose value is sourced from external data when working with
	  * a course entity with a sourced_id value, and are not editable in this context.
	  */
	protected $sourced_elements = array('subject', 'course_number');


	function init( $args = array() )
	{
		parent::init($args);
		
		reason_require_authentication();
		
		if (!reason_check_access_to_site($this->site_id))
		{
			$this->noaccess = true;
			return;
		}

		if($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/courses/manage_courses.css');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/courses/manage_courses.js');
		}

		if (isset($this->request['year']))
		{
			$this->year = $this->request['year'];
			$this->catalog_site_id = id_of('academic_catalog_'.$this->year.'_site');
			$this->elements['display_in_catalog']['display_name'] = 'Display in '.$this->year.' Catalog';
		}

		// If a request has been made to deactivate a course, do that and reload
		if (!empty($this->request['deactivate']))
		{
			$course = new CourseTemplateType($this->request['deactivate']);
			$this->toggle_course_in_site($course, $this->catalog_site_id, 'remove');
			header( 'Location: '. carl_make_redirect(array('deactivate'=>null)));
			exit;
		}

		// If a request has been made to activate a course, do that and reload
		if (!empty($this->request['activate']))
		{
			$course = new CourseTemplateType($this->request['activate']);
			$this->toggle_course_in_site($course, $this->catalog_site_id, 'add');
			header( 'Location: '. carl_make_redirect(array('activate'=>null)));
			exit;
		}

		if (isset($this->request['course']))
		{
			$this->course = new CourseTemplateType($this->request['course']);
			
			$this->form = new disco();
			$this->form->box_class = 'StackedBox';
			$this->form->elements = $this->elements;
			$this->form->required = $this->required;
			$this->form->actions = array('submit'=>'Save Course');
			$this->form->error_header_text = 'There\'s a problem:';
			//$this->form->add_callback(array(&$this, 'pre_show_form'),'pre_show_form');
			$this->form->add_callback(array(&$this, 'process_editor_submission'),'process');
			//$this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
			$this->form->add_callback(array(&$this, 'where_to'),'where_to');
			$this->form->init();
		}

		if (isset($this->request['section']))
		{
			$this->section = new CourseSectionType($this->request['section']);
		}		
	}
	
	function run()
	{
		if ($this->noaccess)
		{
			echo '<p>You don\'t appear to have access to this site. If you think this is
					an error, please contact the site owner listed at the bottom of the page.</p>';
			return;
		}
			
		echo $this->get_menus();
		
		if (isset($this->course))
		{
			echo '<h3>Edit '.$this->course->get_value('org_id'). ' ' .$this->course->get_value('course_number') .'</h3>';
			echo $this->get_course_editor($this->course);
		}
		else if (isset($this->request['subject']))
		{
			echo $this->get_course_list($this->request['subject']);	
		}
	}
	
	/**
	  * Generate the HTML for the menus that allow you to switch between subjects and years.
	  */
	function get_menus()
	{
		$html = '<div id="courseNavigation">'."\n";
		$html .= 'Manage Courses for ';
		$html .= '<select id="courseSubjects">'."\n";
		$html .= '<option value="">--</option>';	
		foreach (get_course_subjects($this->year) as $subject)
		{
			$selected = (isset($this->request['subject']) && $subject == $this->request['subject']) ? 'selected' : '';
			$html .= '<option value="'.$subject.'" '.$selected.'>'.$subject.'</option>'."\n";;	
		}
		$html .= '</select>'."\n";

		$html .= '<select id="courseYears">'."\n";
		$html .= '<option value="">--</option>';	
		foreach (get_catalog_years() as $year)
		{
			$selected = (isset($this->request['year']) && $year == $this->request['year']) ? 'selected' : '';
			$html .= '<option value="'.$year.'" '.$selected.'>'.get_display_year($year).'</option>'."\n";;	
		}
		$html .= '</select>'."\n";


		if (isset($this->course))
		{
			$html .= '<a href="'.carl_make_link(array('course'=>null)).'">Back to list</a>'."\n";
			$html .= '<input type="hidden" id="courseId" value="'.$this->course->id().'" />'."\n";
		}
		$html .= '</div>'."\n";
		return $html;
	}
	
	/**
	 * When a subject is selected, generate lists of courses, showing those that are borrowed into
	 * the site and those that are not.
	 * 
	 * @param string $subject
	 * @return string HTML
	 */
	function get_course_list($subject)
	{
		$subject_courses = get_courses_by_subjects(array($subject));
		
		$site_courses = get_site_courses($this->catalog_site_id);
		
		$html = '<h3>Listed Courses</h3>';
		$html .= '<ul class="courseListActive">';
		foreach ($subject_courses as $id => $course)
		{
			if (isset($site_courses[$id]))
			{
				$html .= $this->get_course_list_row($course, 'active');
				unset($subject_courses[$id]);
			}
		}
		$html .= '</ul>';
		
		$html .= '<h3>Unlisted Courses</h3>';
		$html .= '<ul class="courseListInactive">';
		foreach ($subject_courses as $id => $course)
		{
			$html .= $this->get_course_list_row($course, 'inactive');
		}
		$html .= '</ul>';
		return $html;
	}
	
	/**
	 * Get a single row in a course list, handling the fact that there might be multiple section-based
	 * listings for a single course template.
	 * 
	 * @param object $course
	 * @param string $status Which set of courses are we in (active/inactive)
	 * @return string HTML
	 */
	function get_course_list_row($course, $status = 'active')
	{	
		// We want to limit our view to the academic year we're looking at
		if ($this->year) $course->set_academic_year_limit($this->year);
		
		$html = '';
		$subject = $course->get_value('org_id');
		$number = $course->get_value('course_number');
		$name = $course->get_value('title');

		$titles = $course->get_value('section_titles');
		
		// If all the sections for this course have the same title, just list it
		if (count($titles) < 2)
		{
			$html .= '<li class="courseListRow">';
			$html .= '<span class="courseNumber">'.$subject .' '. $number.'</span>';
			$html .= ' <a href="'.carl_make_link(array('course'=>$course->id())).'">';
			$html .= $name .'</a>';
			if ($history = $course->get_last_offered_academic_session())
			{
				if (term_to_academic_year($history) == $this->year)
					$html .= ' <span class="courseListTerm termHighlight">'. $history .'</span>';
				else
					$html .= ' <span class="courseListTerm">'. $history .'</span>';				
			}
			if ($status == 'active')
				$html .= '<a class="deactivateCourse" title="Unlist course" href="'.carl_make_link(array('deactivate'=>$course->id())).'">–</a>'."\n";
			else
				$html .= '<a class="activateCourse" title="List course" href="'.carl_make_link(array('activate'=>$course->id())).'">+</a>'."\n";

			$html .= '</li>';
		}
		// If there are sections with different titles, we need to list them separately
		// and at a section id to the link.
		else
		{
			$first = true;
			foreach ($titles as $id => $name)
			{
				$html .= '<li class="courseListRow">';
				$section = $course->get_section_by_id($id);
				if ($first)
				{
					$subj_numb = $subject .' '. $number;
					$first = false;
				}
				else
				{
					$subj_numb = '';
				}
				$html .= '<span class="courseNumber">'.$subj_numb.'</span>';
				$html .= ' <a href="'.carl_make_link(array('course'=>$course->id(), 'section'=>$id)).'">';
				$html .= $name .'</a>';
				if ($history = $section->get_value('academic_session'))
				{
					if (term_to_academic_year($history) == $this->year)
						$html .= ' <span class="courseListTerm termHighlight">'. $history .'</span>';
					else
						$html .= ' <span class="courseListTerm">'. $history .'</span>';				
				}
				$html .= '</li>';
			}
		}
		
		return $html;
	}
	
	function process_editor_submission()
	{
		if (!$this->course->get_value('sourced_id'))
		{
			$this->course->set_value('org_id', $this->form->get_value('subject'));
			$this->course->set_value('course_number', $this->form->get_value('course_number'));
		}
		
		$this->course->set_value('name', join(' ', array($this->form->get_value('subject'), $this->form->get_value('course_number'), $this->form->get_value('title'))));
		$this->course->set_value('list_of_prerequisites', $this->form->get_value('prerequisites'));
		$this->course->set_value('credits', $this->form->get_value('credits'));
		$this->course->set_value('title', $this->form->get_value('title'));
		$this->course->set_value('long_description', $this->form->get_value('description'));
		
		reason_update_entity( $this->course->id(), get_user_id(reason_check_authentication()), $this->course->get_values(), true);
		
		// Apply title and description changes to selected sections
		if ($sections = $this->course->get_sections())
		{
			foreach ($this->form->get_value('sections') as $id)
			{
				if (isset($sections[$id]))
				{
					$sections[$id]->set_value('title', $this->form->get_value('title'));
					$sections[$id]->set_value('long_description', $this->form->get_value('description'));
					reason_update_entity( $id, get_user_id(reason_check_authentication()), $sections[$id]->get_values(), true);
				}
			}
		}
		
		if ($this->catalog_site_id)
		{
			if ($this->form->get_value('display_in_catalog'))
				$this->toggle_course_in_site($this->course, $this->catalog_site_id, 'add');
			else 
				$this->toggle_course_in_site($this->course, $this->catalog_site_id, 'remove');
		}
	}
	
	/**
	 * Add or remove the borrowed relationship for this course on this site.
	 * 
	 * @param object $course
	 * @param integer $site_id
	 * @param string $action
	 */
	function toggle_course_in_site($course, $site_id, $action = 'add')
	{
		if ($action == 'add' && !$course->owned_or_borrowed_by($site_id))
			create_relationship( $site_id, $course->id(), get_borrows_relationship_id(id_of('course_template_type')));
		else if ($action != 'add' && $course->owned_or_borrowed_by($site_id))
			delete_borrowed_relationship( $site_id, $course->id(), get_borrows_relationship_id(id_of('course_template_type')));
	}
	
	function where_to()
	{
		return carl_make_redirect(array(
			'course'=>null, 
			'section'=>null, 
			'subject'=>$this->course->get_value('org_id'), 
			'year'=>$this->year));	
	}
	
	/**
	 * Generate and run the disco form for editing a course entity.
	 * 
	 * @param object $course
	 */
	function get_course_editor($course)
	{
		if ($this->year) $course->set_academic_year_limit($this->year);
	
		$this->form->set_element_properties('subject', array('options' => get_course_subjects()));
		
		$this->form->set_value('subject', $course->get_value('org_id'));
		$this->form->set_value('course_number', $course->get_value('course_number'));
		
		// If a section has been passed as a parameter, then we want to pull the data from
		// that section, since we're dealing with a situation where a single course has
		// sections with different titles/descriptions.
		if (isset($this->section))
		{
			$title = $this->section->get_value('title');
			$description = $this->section->get_value('long_description');
		}
		else
		{
			$title = $course->get_value('title');
			$description = $course->get_value('long_description');		
		}
		$this->form->set_value('title', $title);
		$this->form->set_value('description', $description);
		$this->form->set_value('prerequisites', $course->get_value('list_of_prerequisites'));
		$this->form->set_value('faculty', join(', ', $this->format_faculty_for_display($course->get_value('faculty'))));
		$credits = ($course->get_value('credits')) ? $course->get_value('credits') : 'N/A';
		$this->form->set_value('credits', $credits);
		
		if ($this->catalog_site_id)
			$this->form->set_value('display_in_catalog', $course->owned_or_borrowed_by($this->catalog_site_id));

		if ($sections = $course->get_sections())
		{
			$checked = array();
			$sections = array_reverse($sections);
			foreach ($sections as $section)
			{
				$id = $section->id();
				$section_list[$id] = '<span class="courseNumber">'.$section->get_value('course_number').'</span> ';
				$section_list[$id] .= '<span class="courseSession">'.$section->get_value('academic_session').'</span> ';
				$section_list[$id] .= '<span class="courseTitle">'.$section->get_value('title').'</span>';
				// Preselect the sections whose title and description match what we're editing
				if ($section->get_value('long_description') == $description && $section->get_value('title') == $title)
					$checked[] = $id;
			}
			$this->form->set_element_properties('sections', array('options' => $section_list));
			$this->form->set_value('sections', $checked);
			$this->form->set_comments('sections', '<p class="comment">Any changes made to this course will be applied to the selected sections.<p>');
		}
		else
		{
			$this->form->change_element_type('sections', 'solidtext');
			$this->form->set_value('sections', 'No Current Sections Found');
		}
		
		// If this entity has an id tying it to an external data source, make the externally-sourced
		// fields non-editable.
		if ($course->get_value('sourced_id'))
		{
			foreach ($this->sourced_elements as $element)
				$this->form->change_element_type($element, 'solidtext');
			
			$info = '<ul id="courseInfo">';
			$info .= '<li>Course ID: '.$course->get_value('sourced_id').'</li>'."\n";
			if ($start = $course->get_value('start_date'))
				$start = date('M d, Y', strtotime($start));
			else
				$start = 'NOT SET';
			$info .= '<li>Start Date: '.$start.'</li>'."\n";
			if ($end = $course->get_value('end_date'))
				$info .= '<li>End Date: '.date('M d, Y', strtotime($end)).'</li>'."\n";
			$info .= '</li>';
			
			$this->form->set_element_properties('course_info', array('text' => $info));

			if ($course->get_value('requirements'))
				$this->form->set_value('requirements', join(' ', $course->get_value('requirements')));
			else
				$this->form->set_value('requirements', 'N/A');

			if ($grading = $course->get_value('grading'))
				$this->form->set_value('grading', $grading);
		}
		
		
		$this->form->run();
	}
	
	function format_faculty_for_display($faculty)
	{
		foreach ($faculty as $key => $value)
		{
			list($last, $first) = explode(', ', $value);
			$faculty[$key] = $first[0] . '. ' . $last;
		}
		return $faculty;
	}
}
