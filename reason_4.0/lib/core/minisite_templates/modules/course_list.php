<?php
/**
 * Course List
 *
 * Module for displaying lists of courses
 *
 * @author Mark Heiman
 * @since 2014-08-01
 * @package MinisiteModule
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CourseListModule';

reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/course_functions.php' );
//reason_include_once( 'scripts/import/courses/course_import.php' );

class CourseListModule extends DefaultMinisiteModule
{

	public $acceptable_params = array(
		// Retrieve courses owned or borrowed by the site
		'get_site_courses' => false,
		
		// Retrieve courses attached to this (or another) page
		'get_page_courses' => false,
		
		// Alternate page to draw courses from
		'source_page_unique_name' => null,
				
		// Retrieve courses whose subject matches the subject associated with the
		// office/department entity associated with this site.
		'get_courses_by_subjects' => array(),

		// Retrieve courses whose subject matches the subject associated with the
		// office/department entity associated with this site.
		'get_courses_by_site_subjects' => false,
		
		// Retrieve courses with a particular interest tag
		'get_courses_by_tags' => array(),

		// Retrieve courses with tags matching the categories attached to this page
		// (or the page specified by source_page)
		'get_courses_by_page_categories' => false,
		
		// Show courses that have child tags of any of the tags designated above (NOT IMPLEMENTED)
		'descend_tag_hierarchy' => false,
				
		// Limit the number of courses shown (0 for all)
		'max_shown' => 0,
		
		// Whether to sort the courses into subjects or not
		'organize_by_subject' => false,
		
		// Add a list of internal links to the subject sections
		'show_subject_links' => false,

		// Randomize the courses before displaying
		'randomize' => false,
	
		// Which portions of the course description to display
		// array('number','title','description','prereqs','credits','grading','requirements','offered','faculty')
		'display_elements' => array('number','title','description','prereqs','credits','grading','requirements','offered','faculty'),
		);
		
	protected $courses = array();
	protected $subjects = array();
	protected $site_subjects = array();
	protected $page_categories = array();
	public $cleanup_rules = array(
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		'subject' => array( 'function' => 'turn_into_string' ),
		'choose_course' => array( 'function' => 'turn_into_array' ),
		'toggle_course' => array( 'function' => 'turn_into_string' ),
		);
	
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

		$this->build_course_list();

		// Register inline editing
		$inline_edit =& get_reason_inline_editing($this->page_id);
		$inline_edit->register_module($this, $this->user_can_inline_edit());

		if($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/courses/list.css');
			if ($inline_edit->active_for_module($this))
			{
				$head_items->add_javascript(JQUERY_URL, true);
				$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js');
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/courses/course_picker.js');
			}
		}
	}
	
	function run()
	{
		$inline_editing =& get_reason_inline_editing($this->page_id);
		$editing_available = $inline_editing->available_for_module($this);
		$editing_active = $inline_editing->active_for_module($this);
		$html = '<div id="coursesList" class="courses ' . $this->get_api_class_string() . ' ';
		if ($editing_available) $html .= 'editable';
		if ($editing_active) $html .= 'editing';
		$html .= '"><a name="coursesRegion"></a>'."\n";
		
		if ($editing_available) 
		{
			$html .= '<div class="editRegion">';
		
			if ($editing_active) 
			{
				$html .= $this->get_editing_explanation();
				$html .= $this->get_course_picker();	
			} else {
				$html .= '<p class="edit"><a class="editThis" href="'.carl_make_link($inline_editing->get_activation_params($this)).'#coursesRegion">Edit Course List</a></p>'."\n";				
			}
		}
		
		if (!$editing_active)
		{
		
			$count = $protected_count = 0;
			$buckets = array();
			foreach ($this->courses as $course)
			{			
				// Increment only if we got content for this course
				if ($content = $this->get_course_html($course)) 
				{
					if (!$this->params['organize_by_subject'])
					{
						$html .= $content;
						$count++;
					}
					else 
					{
						$subject = $course->get_value('org_id');
						$buckets[$subject][] = $content;
					}
				}
				// Stop if we've reached our limit
				if ($this->params['max_shown'] > 0 && $count >= $this->params['max_shown']) break;
			}
			
			echo $html;
			
			if ($this->params['organize_by_subject'])
			{
				ksort($buckets);
				
				if ($this->params['show_subject_links'])
				{
					echo '<ul class="fieldLinks">';
					foreach ($buckets as $subject => $courses)
					{	
						echo '<li><a href="#'.preg_replace('/\W/', '', $subject).'">'.$subject.'</a></li>';
					}
					echo '</ul>';
				}
				
				foreach ($buckets as $subject => $courses)
				{
					echo '<h3 class="careerField"><a name="'.preg_replace('/\W/', '', $subject).'">'.$subject.'</a></h3>';
					foreach ($courses as $html)
						echo $html;
				}			
			}
		} else {
			echo $html;	
		}
		
		if ($editing_available)
		{
			echo '</div>';
		}
		
		echo '</div>';
		
	}
	
	protected function build_course_list()
	{
		// Go through all the parameters and grab all the courses that match what's being requested.
		// We'll sort through them and throw out any that don't apply at the run stage -- it's
		// more efficient that way.
		
		if ($this->params['get_site_courses'])
			$this->courses = $this->courses + get_site_courses($this->site_id);

		if ($this->params['get_page_courses'])
			$this->courses = $this->courses + get_page_courses($this->get_source_page_id());

		foreach ($this->params['get_courses_by_tags'] as $tag)
			$this->courses = $this->courses + get_courses_by_category($tag);
		
		if ($this->params['get_courses_by_page_categories'])
			$this->courses = $this->courses + $this->get_page_category_courses();
		
		if ($this->params['get_courses_by_subjects'])
			$this->courses = $this->courses + get_courses_by_subjects($this->params['get_courses_by_subjects'], 'academic_catalog_2014_site');

		if ($this->params['get_courses_by_site_subjects'])
			$this->courses = $this->courses + $this->get_courses_by_site_subjects();

		if ($this->params['randomize'])
			shuffle($this->courses);
		else
			uasort($this->courses, 'sort_courses_by_name');		
	}
	
	protected function get_course_html($course)
	{
		$course->set_academic_year_limit(2014);
		if ($course->get_last_offered_academic_year() < 2011) return null;
		
		$html = '<div class="courseContainer">'."\n";
		$html .= '<h4>';
		if (in_array('number', $this->params['display_elements']))
			$html .= $course->get_value('org_id').' '.$course->get_value('course_number').': ';
		if (in_array('title', $this->params['display_elements']))
			$html .= $course->get_value('title');
		$html .= '</h4>';

		if (in_array('description', $this->params['display_elements']))
		{
			$description = preg_replace(array('/^<p[^>]*>/','/<\/p>$/'), '', $course->get_value('long_description'));
			if (in_array('prereqs', $this->params['display_elements']) && $prereqs = $course->get_value('list_of_prerequisites'))
				$description .= ' <span class="prereqLabel">Prerequisite:</span> '.$prereqs;
	
			$html .= '<div class="courseDescription">'. $description .'</div>';
		}
		
		if (in_array('credits', $this->params['display_elements']) && $credit = $course->get_value('credits'))
		{
			$details[] = $credit . (($credit == 1) ? ' credit' : ' credits');	
		}
		
		if (in_array('grading', $this->params['display_elements']) && $grading = $course->get_value('grading'))
		{
			$details[] = $grading;	
		}

		if (in_array('requirements', $this->params['display_elements']) && $requirements = $course->get_value('requirements'))
		{
			$details[] = join(', ', $requirements);	
		}
		
		if (in_array('offered', $this->params['display_elements']))
		{
			if ($history = $course->get_offer_history())
			{
				$term_names = array('SU'=>'Summer','FA'=>'Fall','WI'=>'Winter','SP'=>'Spring');
				
				foreach ($history as $term)
				{
					list($year,$termcode) = explode('/', $term);
					$terms[] = $term_names[$termcode].' '.(2000 + $year);
				}
				$details[] = 'offered ' . join(', ', $terms);
			} else {
				$details[] = 'not offered 2014–15';
			}
		}

		if (in_array('faculty', $this->params['display_elements']) && $fac_data = $course->get_value('faculty'))
		{
			foreach ($fac_data as $id => $name)
			{
				list($last, $first) = explode(', ', $name);
				$faculty[$id] = $first[0].'. '.$last;
			}
			$details[] = join(', ', $faculty);	
		}

		
		if (isset($details))
		{
			$html .= '<div class="courseAttributesInstructor">'.join('; ', $details).'</div>';	
		}
		
		$html .= '</div>'."\n";
		
		return $html;
	}	
	
	protected function get_tags_html($course, $section)
	{		
		$tags_str = '';
		if ($course && ($interest_tags = $course->get_categories($section)))
		{	
			$tags_str .= '<ul class="tagList">' . "\n";
			foreach ($interest_tags as $slug => $tag)
			{
				$text = htmlspecialchars($tag);
				$tags_str .= '<li><a class="interestTag" href="'.reason_get_site_url(id_of('courses_site')).'explore/'.htmlspecialchars($slug).'" title="Find others with this interest">'.htmlspecialchars($tag).'</a></li>' ."\n";
			}
			$tags_str .= '</ul>' . "\n";
		}
		return $tags_str;
	}	
	
	/**
	  * Get the id of the page that we should be looking to for associated courses. If the source_page
	  * parameter is set, we use that page; if not, we use the current page.
	  */
	protected function get_source_page_id()
	{
		if($this->params['source_page_unique_name'])
		{
			if ($page_id = id_of($this->params['source_page_unique_name']))
				return $page_id;
			else
				trigger_error('source_page_unique_name parameter to course_display module not a valid unique name');
		}
		return $this->cur_page->id();
	}
	
	/**
	  * Find courses that use interest tags that match categories attached to this page
	  * and add them to our collection.
	  *
	  */
	protected function get_page_category_courses()
	{
		$courses = array();

		if ($cats = $this->get_page_categories())
		{
			$courses = get_courses_by_category($cats);
		}
		
		return $courses;
	}

	/**
	  * Find courses whose major matches any subjects associated with the office/department
	  * entity attached to the current site and add them to our collection.
	  *
	  */
	protected function get_courses_by_site_subjects()
	{
		if ($codes = $this->get_site_subjects()) 
			return get_courses_by_subjects($codes);
		else
			return array();
	}

	protected function get_page_categories()
	{
		if (empty($this->page_categories))
		{
			// Get the categories attached to this page
			$cat_es = new entity_selector();
			$cat_es->description = 'Selecting categories for this page';
			$cat_es->add_type( id_of('category_type'));
			$cat_es->limit_tables();
			$cat_es->limit_fields();
			$cat_es->add_right_relationship($this->get_source_page_id(), relationship_id_of('page_to_category') );
			
			if ($categories = $cat_es->run_one())
			{
				foreach ($categories as $cat)
				{
					$this->page_categories[$cat->id()] = $cat->get_value('name');	
				}
			}		
		}
		return $this->page_categories;		
	}
	
	protected function get_site_subjects()
	{
		if (empty($this->site_subjects))
		{
			$es = new entity_selector();
			$es->description = 'Selecting department for site';
			$es->add_type( id_of( 'office_department_type' ) );
			$es->add_left_relationship($this->site_id, relationship_id_of('office_department_has_site') );
			$depts = $es->run_one();
			foreach ($depts as $dept_id => $dept)
			{
				$es2 = new entity_selector();
				$es2->description = 'Selecting subjects for department';
				$es2->add_type( id_of( 'subject_type' ) );
				$es2->add_right_relationship($dept_id, relationship_id_of('office_department_to_subject') );
				$subjects = $es2->run_one();
				foreach ($subjects as $sub_id => $subject) 
					if ($subject->get_value('sync_name')) $this->site_subjects[] = $subject->get_value('sync_name');
			}
		}
		return $this->site_subjects;		
	}
				
	function get_documentation()
	{
		return'<p>Displays a list of courses.</p>';
	}

	/**
	 * Determines whether or not the user can inline edit.
	 *
	 * @return boolean;
	 */
	function user_can_inline_edit()
	{
		if (!isset($this->_user_can_inline_edit))
		{
			$this->_user_can_inline_edit = reason_check_access_to_site($this->site_id);
		}
		return $this->_user_can_inline_edit;
	} 

	function get_editing_explanation()
	{
		$sources = array();
		if ($this->params['get_site_courses'])
			$sources[] = 'courses borrowed by this Reason site.';

		if ($this->params['get_page_courses'])
			$sources[] = 'courses associated with this page in the Reason admin.';

		foreach ($this->params['get_courses_by_tags'] as $tag)
			$sources[] = 'courses tagged with the category "'.$tag.'"';
		
		if ($this->params['get_courses_by_page_categories'])
		{
			if ($cats = $this->get_page_categories())
				$sources[] = 'courses that share the categories associated with this page ('.join(', ', $cats).').';
		}
		
		foreach ($this->params['get_courses_by_subjects'] as $subj)
			$sources[] = 'courses in the academic subject "'.$subj.'"';

		if ($this->params['get_courses_by_site_subjects'])
		{
			if ($subjects = $this->get_site_subjects())
				$sources[] = 'courses that share the same academic subject as this Reason site ('.join(', ', $subjects).').';
		}
		
		$string = '<div class="explain">';
		if (count($sources) > 1)
		{
			$string .= '<p>Courses can appear on this page in more than one way. This page is currently displaying: ';
			$string .= '<ul>'."\n";
			foreach ($sources as $source)
				$string .= '<li>'.$source.'</li>'."\n";
			$string .= '</ul>'."\n";
			$string .= '</parse_url>'."\n";
		}
		else
		{
			$string .= '<p>This page is currently displaying ' . $sources[0].'</p>';	
		}
		$string .= '<p>If you need to change how courses are selected for this page, please contact a Reason administrator.</p>';
		$string .= '<p class="edit"><a class="editThis" href="'.carl_make_link(array('inline_edit'=>null)).'">Save and Finish</a></p>'."\n";				
		$string .= '</div>';
		return $string;
	}
	
	function get_course_picker()
	{
		$html = '<div id="coursePicker">'."\n";
		$html .= '<h4>Select Courses for this Page</h4>';
		$html .= '<p>Choose a subject: '.$this->get_subject_menu().'</p>'."\n";
		$html .= '<div id="courseList"></div>'."\n";
		$html .= '</div>'."\n";
		return $html;
	}
	
	function get_subject_menu()
	{
		$html = '<select id="courseSubjects">'."\n";
		$html .= '<option value="">--</option>';	
		foreach (get_course_subjects() as $subject)
		{
			$html .= '<option value="'.$subject.'">'.$subject.'</option>';	
		}
		$html .= '</select>'."\n";
		return $html;
	}
	
	/**
	 * This method provides data for ajax requests. Course subjects come in via the 'value' request
	 * parameter and are used to look up courses, which are echoed as JSON.
	 *
	 * @param string $data  Course subject code
	 * @return void
	 */
	function do_course_lookup($data)
	{
		$editable_sources = array('page_courses','categories');
		
		// Build the list of courses already on the page so we know what's selected
		$this->build_course_list();
		
		if ($courses = get_courses_by_subjects(array($data), 'academic_catalog_2014_site'))
		{
			foreach ($courses as $id => $course)
			{
				$history = $course->get_last_offered_academic_year();
				if ($history < (2014 - 3)) continue;
				
				$output['title'] = html_entity_decode($course->get_value('name'));
				
				$output['title'] .= ' ('. $history .')';	
				
				$output['desc'] = $course->get_value('long_description');
				//if (empty($output['desc'])) continue;
				if (isset($this->courses[$id]))
				{
					$output['selected'] = true;
					$output['editable'] = in_array($this->courses[$id]->include_source, $editable_sources);
				}
				else
				{
					$output['selected'] = false;
					$output['editable'] = true;
				}
					
				$list[$id] = $output;
			}
			if (isset($list))
				echo json_encode($list);
			else
				echo json_encode(false);
		}
	}
	
	function do_course_toggle($id)
	{
		$this->build_course_list();
		if (isset($this->courses[$id]))
		{
			if ($this->courses[$id]->include_source == 'page_courses')
			{
				$course = new entity($id);
				$rels = $course->get_left_relationships_info();
				foreach ($rels['course_template_to_page'] as $rel)
				{
					if ($rel['entity_b'] == $this->get_source_page_id())
					{
						delete_relationship($rel['id']);
						return true;
						break;
					}
				}
			}
			else if ($this->courses[$id]->include_source == 'categories')
			{
				$course = new entity($id);
				$rels = $course->get_left_relationships_info();
				$cats = $this->get_page_categories();
				foreach ($rels['course_template_to_category'] as $rel)
				{
					if (isset($cats[$rel['entity_b']]))
					{
						delete_relationship($rel['id']);
						return true;
						break;
					}
				}
			}
		}
		else
		{
			if ($this->params['get_page_courses'])
			{
				return create_relationship( $id, $this->get_source_page_id(), relationship_id_of('course_template_to_page'),false,false);
			}
			else if ($this->params['get_courses_by_page_categories'])
			{
				if ($cats = $this->get_page_categories())
				{
					// For now we're just attaching the first page category we find to the course;
					// a better, future interface would allow the user to pick the category.
					$cat_id = key($cats);
					return create_relationship( $id, $cat_id, relationship_id_of('course_template_to_category'),false,false);
				}
			}
		}
		return false;
	}
}

?>
