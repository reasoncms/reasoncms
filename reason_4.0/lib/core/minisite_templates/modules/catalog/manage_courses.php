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
	public $cleanup_rules = array(
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		'subject' => array( 'function' => 'turn_into_string' ),
		'course' => array( 'function' => 'turn_into_int' ),
		'toggle_course' => array( 'function' => 'turn_into_string' ),
		);
	

	protected $elements = array(//{{{
		'course_info'=>array(
			'type'=>'comment',
			),
		'sections'=>array(
			'type'=>'checkboxgroup_no_sort',
			),
		'requirements'=>array(
			'type'=>'solidtext',
			),
		'grading'=>array(
			'type'=>'solidtext',
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
	  * a course entity with a sourced_id value, and are not editable in that context.
	  */
	protected $sourced_elements = array('subject', 'course_number', 'faculty', 'credits');


	function init( $args = array() )
	{
		parent::init($args);
		
		// If we're in ajax mode, we just return the data and quit the module.
		$api = $this->get_api();
		if ($api && ($api->get_name() == 'standalone'))
		{
			if (isset($this->request['subject']))
			{
				$this->do_course_lookup($this->request['subject']);
				exit;
			}
			if (isset($this->request['toggle_course']))
			{
				echo json_encode($this->do_course_toggle($this->request['toggle_course']));
				exit;
			}
		}

		//$this->build_course_list();

		if($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/courses/manage_courses.css');
			//$head_items->add_javascript(JQUERY_URL, true);
			//$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/courses/manage_courses.js');
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
	}
	
	function run()
	{
		//$this->sync_with_old_catalog(2014);
		
		echo $this->get_subject_menu();
		
		if (isset($this->course))
		{
			echo '<h2>Edit '.$this->course->get_value('org_id'). ' ' .$this->course->get_value('course_number') .'</h2>';
			echo $this->get_course_editor($this->course);
		}
		else if (isset($this->request['subject']))
		{
			echo '<h2>Edit '.$this->request['subject'] .' Courses</h2>';
			echo $this->get_course_list($this->request['subject']);	
		}
	}
	
	function get_subject_menu()
	{
		$html = '<div id="courseNavigation">'."\n";
		$html .= '<select id="courseSubjects">'."\n";
		$html .= '<option value="">--</option>';	
		foreach (get_course_subjects() as $subject)
		{
			$selected = (isset($this->request['subject']) && $subject == $this->request['subject']) ? 'selected' : '';
			$html .= '<option value="'.$subject.'" '.$selected.'>'.$subject.'</option>'."\n";;	
		}
		$html .= '</select>'."\n";
		if (isset($this->course))
		{
			$html .= '<a href="'.carl_make_link(array('course'=>null)).'">Back to list</a>';
		}
		$html .= '</div>'."\n";
		return $html;
	}
	
	function get_course_list($subject)
	{
		$subject_courses = get_courses_by_subjects(array($subject));
		
		$site_courses = get_site_courses($this->site_id);
		
		$html = '<h3>Active Courses</h3>';
		$html .= '<ul class="courseListActive">';
		foreach ($subject_courses as $id => $course)
		{
			if (isset($site_courses[$id]))
			{
				$html .= $this->get_course_list_row($course);
				unset($subject_courses[$id]);
			}
		}
		$html .= '</ul>';
		
		$html .= '<h3>Inactive Courses</h3>';
		$html .= '<ul class="courseListInactive">';
		foreach ($subject_courses as $id => $course)
		{
			$html .= $this->get_course_list_row($course);
		}
		$html .= '</ul>';
		return $html;
	}
	
	function get_course_list_row($course)
	{
		$html = '<li class="courseListRow">';
		list($subject,$number,$name) = explode(' ', $course->get_value('name'), 3);
		
		$html .= '<a href="'.carl_make_link(array('course'=>$course->id())).'">';
		$html .= $subject .' '. $number;
		$html .= '</a> '.$name;
		if ($history = $course->get_last_offered_academic_session())
			$html .= ' ('. $history .')';	
		$html .= '</li>';
		return $html;
	}
	
	function process_editor_submission()
	{
		if (!$this->course->get_value('sourced_id'))
		{
			$this->course->set_value('org_id', $this->form->get_value('subject'));
			$this->course->set_value('course_number', $this->form->get_value('course_number'));
		}
		
		$this->course->set_value('list_of_prerequisites', $this->form->get_value('prerequisites'));
		$this->course->set_value('credits', $this->form->get_value('credits'));
		$this->course->set_value('title', $this->form->get_value('title'));
		$this->course->set_value('long_description', $this->form->get_value('description'));
		
		reason_update_entity( $this->course->id(), get_user_id(reason_check_authentication()), $this->course->get_values(), true);
		
		// Apply title and description changes to selected sections
		if ($sections = $this->course->get_sections())
		{
			var_dump($sections);
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
		
		if ($this->form->get_value('display_in_catalog') && !$this->course->owned_or_borrowed_by($this->site_id))
			create_relationship( $this->site_id, $this->course->id(), get_borrows_relationship_id(id_of('course_template_type')));
		else if (!$this->form->get_value('display_in_catalog') && $this->course->owned_or_borrowed_by($this->site_id))
			delete_borrowed_relationship( $this->site_id, $this->course->id(), get_borrows_relationship_id(id_of('course_template_type')));
	}
	
	function where_to()
	{
		return carl_make_link(array('course'=>null, 'subject'=>$this->course->get_value('org_id')));	
	}
	
	function get_course_editor($course)
	{
		$this->form->set_element_properties('subject', array('options' => get_course_subjects()));
		
		$this->form->set_value('subject', $course->get_value('org_id'));
		$this->form->set_value('course_number', $course->get_value('course_number'));
		$this->form->set_value('title', $course->get_value('title'));
		$this->form->set_value('description', $course->get_value('long_description'));
		$this->form->set_value('prerequisites', $course->get_value('list_of_prerequisites'));
		$this->form->set_value('faculty', join(', ', $this->format_faculty_for_display($course->get_value('faculty'))));
		$this->form->set_value('credits', $course->get_value('credits'));
		$this->form->set_value('display_in_catalog', $course->owned_or_borrowed_by($this->site_id));

		if ($sections = $course->get_sections())
		{
			$checked = array();
			$sections = array_reverse($sections);
			foreach ($sections as $id => $section)
			{
				$section_list[$id] = $section->get_value('name');
				if ($section->get_value('long_description') == $course->get_value('long_description'))
					$checked[] = $id;
			}
			$this->form->set_element_properties('sections', array('options' => $section_list));
			$this->form->set_value('sections', $checked);
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

			$this->form->set_value('requirements', join(' ', $course->get_value('requirements')));

			$this->form->set_value('grading', $course->get_value('grading'));
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
	
	function sync_with_old_catalog($year)
	{
		$courses = array();
		$es = new entity_selector();
		$es->description = 'Selecting courses';
		$factory = new CourseTemplateEntityFactory();
		$es->set_entity_factory($factory);
		$es->add_type( id_of('course_template_type') );
		$es->set_order('course_number');
		$results = $es->run_one();

		connectdb( 'reg_catalog_new' );
		foreach ($results as $id => $entity)
		{
			$query = 'SELECT id FROM course'.$year.' WHERE visible = 1 AND course_id = "'.$entity->get_value('sourced_id').'"';
			if ($result = mysql_query($query))
			{
				if (mysql_num_rows($result))
				{
					connectdb( REASON_DB );	
					create_relationship( $this->site_id, $entity->id(), get_borrows_relationship_id(id_of('course_template_type')));
					connectdb( 'reg_catalog_new' );
				}
			} 
			
		}
		connectdb( REASON_DB );		
	}
	
}
