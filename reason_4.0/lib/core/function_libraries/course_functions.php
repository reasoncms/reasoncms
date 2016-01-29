<?php

/**
 * Functions for manipulating and locating course entities.
 *
 * @author Mark Heiman
 * @package reason
 * @subpackage function_libraries
 */

include_once(CARL_UTIL_INC.'cache/object_cache.php');

// If you need to extend the course entities for local use (which you probably do), just redefine
// these globals in your extending file with the name of your local classes.
$GLOBALS['course_template_class'] = 'CourseTemplateType';
$GLOBALS['course_section_class'] = 'CourseSectionType';
$GLOBALS['catalog_helper_class'] = 'CatalogHelper';

// Working with catalog data we need to be always in UTF8
mysql_set_charset('utf8');

class CourseTemplateEntityFactory
{
	public function get_entity(&$row)
	{
		return new $GLOBALS['course_template_class']($row['id']);	
	}
}

class CourseTemplateType extends Entity
{
	protected $sections;
	protected $limit_to_year = false;
	protected $external_data;
	protected $helper;
	
	function CourseTemplateType($id, $cache=true)
	{
		parent::__construct($id, $cache);
		$this->helper = new $GLOBALS['catalog_helper_class']();
	}
	
	public function set_academic_year_limit($year)
	{
		if ($year && is_numeric($year))
			$this->limit_to_year = $year;
		else
			$this->limit_to_year = false;		
	}

	public function get_value($col, $refresh = false)
	{
		$custom_getter = 'get_value_'.$col;
		if (method_exists($this, $custom_getter))
		{
			return ($this->$custom_getter($refresh));
		}
		else
		{
			return parent::get_value($col, $refresh);
		}
	}
	
	public function get_sections($honor_limit = true)
	{
		if (!is_array($this->sections))
		{
			$this->sections = array();
			if ($sections = $this->get_left_relationship('course_template_to_course_section'))
			{
				foreach ( $sections as $key => $section)
				{
					$this->sections[$section->id()] = new $GLOBALS['course_section_class']($section->id());
					$this->sections[$section->id()]->get_values();
				}
			}
		}

		uasort($this->sections, array($this->helper, 'sort_courses_by_number_and_date'));
		
		// If an academic year limit has been set, only return those that match
		if ($this->limit_to_year && $honor_limit)
		{
			$sections = array();
			foreach ( $this->sections as $key => $section)
			{
				$year = $this->helper->term_to_academic_year($section->get_value('academic_session'));
				if ($year != $this->limit_to_year) 
				{
					continue;
				}
				$sections[$key] = $section;
			}
			return $sections;
		}

		return $this->sections;
	}
	
	/**
	 * Return the distinct titles associated with this course's sections. Honors year
	 * limits.  Used primarily to find courses with sections that have different titles.
	 */
	public function get_value_section_titles($refresh = false)
	{
		$titles = array();
		if ($sections = $this->get_sections())
		{
			foreach ($sections as $key => $section)
			{
				if (!in_array($section->get_value('title', $refresh), $titles))
				{
					$titles[$key] = $section->get_value('title');
				}
			}
		}
		return $titles;
	}
	
	/**
	 * Return the long description for a course. If year limit is in place, load description from
	 * sections offered that year, otherwise draw the description from the template. If no year limit
	 * is in place, grab the most recent section description.
	 * 
	 * @param boolean $refresh
	 * @return string
	 */
	public function get_value_long_description($refresh = false)
	{
		if ($this->limit_to_year)
		{
			$start_date = $this->helper->get_catalog_year_start_date($this->limit_to_year);
		}
		else
		{
			$start_date = 0;
		}
			
		foreach ( $this->get_sections(false) as $key => $section)
		{
			if ($desc = $section->get_value('long_description', $refresh))
			{
				if ($section->get_value('timeframe_begin', $refresh) > $start_date)
				{
					$long_description = $desc;	
				}
			}
		}
		
		if (empty($long_description))
			$long_description = parent::get_value('long_description', $refresh);
		
		return $long_description;
	}

	/**
	 * Generate a default HTML snippet for the course title. Override this if you
	 * want to use a diffferent pattern.
	 * 
	 * @param boolean $refresh
	 * @return string
	 */
	protected function get_value_display_title($refresh = false)
	{
		$html = '<span class="courseNumber" course="course_'.$this->id().'_'.$this->limit_to_year.'">';
		$html .= $this->get_value('org_id', $refresh).' '.$this->get_value('course_number', $refresh);
		$html .= '</span> ';
		$html .= '<span class="courseTitle">';
		$html .= $this->get_value('title', $refresh);
		$html .= '</span> ';
	
		return $html;
	}
	
	/**
	  * Returns an array of the academic terms in which this course was offered, formatted as
	  * ( start_date => term_name), sorted by date. Only one element exists per term, even if
	  * multiple sections of this course are offered in a given term.
	  *
	  * @return array
	  */
	public function get_offer_history($honor_limit = true)
	{
		$history = array();
		foreach ( $this->get_sections($honor_limit) as $key => $section)
		{
			$history[$section->get_value('timeframe_begin')] = $section->get_value('academic_session');
		}
		ksort($history);
		return $history;
	}
	
	/**
	 * Generate a default HTML snippet for displaying details about if and
	 * when the course is offered in the current academic year. Override this if you want to use a 
	 * different pattern.
	 * 
	 * @param boolean $honor_limit Whether to honor existing year restriction
	 * @return string
	 */
	function get_offer_history_html($honor_limit = true)
	{
		if ($terms = $this->get_offer_history($honor_limit))
		{
			$term_names = array('SU'=>'Summer','FA'=>'Fall','WI'=>'Winter','SP'=>'Spring');
			
			foreach ($terms as $term)
			{
				list(,$termcode) = explode('/', $term);
				$offered[$termcode] = $term_names[$termcode];
			}
			$history = join(', ', $offered);
		} else if ($this->limit_to_year) {
			$history = 'Not offered '.$this->helper->get_display_year($this->limit_to_year);
		}
		
		if (!empty($history))
			return '<span class="courseAttributesOffered">'.$history.'</span>';
	}
	
	/**
	  * Returns the academic year in which this course was last offered. The value
	  * is the year that the session started, e.g. courses offered in the 2013-14 academic
	  * year return 2013. Courses with no history return 0;
	  *
	  * @return integer
	  */
	public function get_last_offered_academic_year()
	{
		if ($history = $this->get_offer_history(false))
		{
			$year = $this->helper->term_to_academic_year(end($history));
		}
		
		return (isset($year)) ? $year : 0;
	}

	/**
	  * Returns the academic session in which this course was last offered.
	  *
	  * @return string
	  */
	public function get_last_offered_academic_session()
	{
		if ($history = $this->get_offer_history(false))
		{
			$session = end($history);
		}
		
		return (isset($session)) ? $session : null;
	}

	public function get_section_by_id($id)
	{
		if ($sections = $this->get_sections(false))
		{
			if (isset($sections[$id])) return $sections[$id];
		}
		return false;
	}

	/**
	 * Retrieve and cache course data from an external source. Used for values that are not defined
	 * in the base course template schema. You'll need to extend this class to provide your own
	 * retrieval routine.
	 */
	protected function fetch_external_data($refresh = false)
	{
		if (empty($this->external_data))
		{
			// Do we want to check for the empty array here?
			if ($cache = $this->get_value('cache'))
			{
				$this->external_data = json_decode($cache, true);
			}
			
			if ($refresh || !isset($this->external_data))
			{
				// Insert your routine for retrieving external data here.
				
				// Indicate that we've tried and failed to retrieve the data, so we don't keep trying
				$this->external_data = array();

				if (!empty($this->external_data))
				{
					$this->set_value('cache', json_encode($this->external_data));
					reason_update_entity( 
						$this->id(), 
						$this->get_value('last_edited_by'), 
						array('cache' => $this->get_value('cache')),
						false);
				}
			}
		}
	}
}





class CourseSectionEntityFactory
{
	public function get_entity(&$row)
	{
		return new $GLOBALS['course_section_class']($row['id']);	
	}
}

class CourseSectionType extends Entity
{	
	protected $external_data = array();
	protected $helper;
	
	function CourseSectionType($id=null, $cache=true)
	{
		parent::__construct($id, $cache);
		$this->helper = new $GLOBALS['catalog_helper_class']();
	}

	
	public function get_template()
	{
		if ($entities = $this->get_right_relationship('course_template_to_course_section'))
		{
			$template = reset($entities);
			return new $GLOBALS['course_template_class']($template->id());
		}
	}

	public function get_value($col, $refresh = false)
	{
		$custom_getter = 'get_value_'.$col;
		if (method_exists($this, $custom_getter))
		{
			return ($this->$custom_getter($refresh));
		}
		else
		{
			return parent::get_value($col, $refresh);
		}
	}
	
	/**
	 * Retrieve and cache section data from an external source. Used for values that are not defined
	 * in the base course section schema. You'll need to extend this class to provide your own
	 * retrieval routine.
	 */
	protected function fetch_external_data($refresh = false)
	{
		if (empty($this->external_data['section']))
		{
			if ($cache = $this->get_value('cache'))
			{
				$this->external_data = json_decode($cache, true);
			}
			
			if ($refresh || !isset($this->external_data['section']))
			{
				// Insert your routine for retrieving external data here.

				// Indicate that we've tried and failed to retrieve the data, so we don't keep trying
				$this->external_data['section'] = array();

				if (!empty($this->external_data['section']))
					$this->update_cache();
			}
		}
	}
	
	protected function update_cache()
	{
		$encoded = json_encode($this->external_data);
		if ($encoded != $this->get_value('cache'))
		{
			$this->set_value('cache', json_encode($this->external_data));
			reason_update_entity( 
				$this->id(), 
				$this->get_value('last_edited_by'), 
				array('cache' => $this->get_value('cache')),
				false);
		}
	}
}

/**
 * A collection of methods for working with catalog and course data. You will probably want to
 * extend this class locally to meet your particular needs.
 */
class CatalogHelper
{
	protected $year;
	protected $site;
	
	public function __construct($year = null)
	{
		if ($year)
		{
			$this->year = $year;
			if (!$this->site = id_of('academic_catalog_'.$year.'_site'))
				trigger_error('No catalog site for '.$year.' in CatalogHelper');
		}
	}
	
	/**
	  * Find courses that have been attached to the specified page.
	  *
	  */
	public function get_page_courses($page_id)
	{
		$courses = array();
		$es = new entity_selector();
		$es->description = 'Selecting courses for this page';
		$factory = new CourseTemplateEntityFactory();
		$es->set_entity_factory($factory);
		$es->add_type( id_of('course_template_type') );
		$es->add_left_relationship( $page_id, relationship_id_of('course_template_to_page') );
		$results = $es->run_one();
		foreach ($results as $id => $entity)
		{
			if (isset($courses[$id])) continue;
			$entity->include_source = 'page_courses';
			$courses[$id] = $entity;
		}
		return $courses;
	}

	/**
	  * Find courses that are owned or borrowed by the specified site.
	  *
	  */
	public function get_site_courses($site)
	{
		$courses = array();
		if ($site && (is_int($site) || $site = id_of($site)))
		{
			$es = new entity_selector( $site );
			$es->description = 'Selecting courses on site';
			$factory = new CourseTemplateEntityFactory();
			$es->set_entity_factory($factory);
			$es->add_type( id_of('course_template_type') );
			$results = $es->run_one();
			foreach ($results as $id => $entity)
			{
				if (isset($courses[$id])) continue;
				$entity->include_source = 'site_courses';
				$courses[$id] = $entity;
			}
		}
		return $courses;
	}

	/**
	  * Find courses that use given categories and add them to our collection.
	  *
	  * @param $cats array category list (id => name)
	  */
	public function get_courses_by_category($cats, $catalog_site = null)
	{
		$courses = array();
		foreach ($cats as $id => $category)
		{
			if ($catalog_site && (is_int($catalog_site) || $catalog_site = id_of($catalog_site)))
				$es = new entity_selector($catalog_site);
			else
				$es = new entity_selector();
			$es->description = 'Selecting courses by category';
			$factory = new CourseTemplateEntityFactory();
			$es->set_entity_factory($factory);
			$es->add_type( id_of('course_template_type') );
			$es->add_left_relationship( $id, relationship_id_of('course_template_to_category') );
			$results = $es->run_one();
			foreach ($results as $id => $entity)
			{
				if (isset($courses[$id])) continue;
				$entity->include_source = 'categories';
				$courses[$id] = $entity;
			}
		}
		return $courses;
	}

	/**
	  * Find courses with particular academic subjects and add them to our collection.
	  *
	  * @param array $codes Array of subject codes
	  * @param string $catalog_site Optional unique name of a catalog site. If you pass this,
	  *				only courses associated with that site will be included.
	  */
	public function get_courses_by_subjects($codes, $catalog_site = null)
	{
		$courses = array();
		if ($catalog_site && (is_int($catalog_site) || $catalog_site = id_of($catalog_site)))
			$es = new entity_selector($catalog_site);
		else
			$es = new entity_selector();
		$es->description = 'Selecting courses by subject';
		$factory = new CourseTemplateEntityFactory();
		$es->set_entity_factory($factory);
		$es->add_type( id_of('course_template_type') );
		$es->add_relation('org_id in ("'.join('","', $codes).'")');
		$es->set_order('ABS(course_number), title');
		$results = $es->run_one();
		foreach ($results as $id => $entity)
		{
			if (isset($courses[$id])) continue;
			$entity->include_source = 'subjects';
			$courses[$id] = $entity;
		}
		return $courses;
	}

	public function get_courses_by_org_id($codes, $catalog_site = null)
	{
		return $this->get_courses_by_subjects($codes, $catalog_site);
	}

	/**
	  * Find courses by their id in the source data
	  *
	  * @param array $ids Array of ids
	  * @param string or int $catalog_site Optional unique name or id of a catalog site. If you pass this,
	  *				only courses associated with that site will be included.
	  */
	public function get_courses_by_sourced_id($ids, $catalog_site = null)
	{
		$courses = array();
		if ($catalog_site && (is_int($catalog_site) || $catalog_site = id_of($catalog_site)))
			$es = new entity_selector($catalog_site);
		else
			$es = new entity_selector();
		$es->description = 'Selecting courses by sourced_id';
		$factory = new CourseTemplateEntityFactory();
		$es->set_entity_factory($factory);
		$es->add_type( id_of('course_template_type') );
		$es->add_relation('sourced_id in ("'.join('","', $ids).'")');
		$es->set_order('org_id, course_number');
		$results = $es->run_one();
		foreach ($results as $id => $entity)
		{
			if (isset($courses[$id])) continue;
			$entity->include_source = 'subjects';
			$courses[$id] = $entity;
		}
		return $courses;
	}

	public function sort_courses_by_name($a, $b)
	{
		$a_name = $a->get_value('name');
		$b_name = $b->get_value('name');
		if ($a_name == $b_name) {
			return 0;
		}
		return ($a_name < $b_name) ? -1 : 1;
	}

	public function sort_courses_by_date($a, $b)
	{
		$a_name = $a->get_value('timeframe_begin');
		$b_name = $b->get_value('timeframe_begin');
		if ($a_name == $b_name) {
			return 0;
		}
		return ($a_name > $b_name) ? -1 : 1;
	}

	public function sort_courses_by_number_and_date($a, $b)
	{
		$a_num = $a->get_value('course_number');
		$b_num = $b->get_value('course_number');
		$a_name = $a->get_value('timeframe_begin');
		$b_name = $b->get_value('timeframe_begin');
		if ($a_name.$a_num == $b_name.$b_num) {
			return 0;
		}
		return ($a_name.$a_num > $b_name.$b_num) ? -1 : 1;
	}


	/**
	  * Get the list of possible course subjects by looking at the section data. If a year is
	  * passed, limit the result to those subjects with sections during that academic year.
	  *
	  * @param int $year
	  *
	  * @todo Generalize timespan query
	  */
	public function get_course_subjects($year = null)
	{
		$cache = new ObjectCache('course_subject_cache_'.$year, 60*24);
		if ($subjects = $cache->fetch()) return $subjects;

		$subjects = array();
		$q = 'SELECT distinct org_id FROM course_section';
		if ($year) $q .= ' WHERE timeframe_begin > "'.$this->get_catalog_year_start_date($year).'" AND timeframe_end < "'.$this->get_catalog_year_end_date($year).'"';
		$q .= ' ORDER BY org_id';
		if ($result = db_query($q, 'Error selecting course subjects'))
		{
			while ($row = mysql_fetch_assoc($result))
				$subjects[$row['org_id']] = $row['org_id'];
		}
		$cache->set($subjects);
		return $subjects;
	}

	/**
	 * Get the list of years for which we have catalog sites. Assumes that catalog sites use the
	 * naming convention academic_catalog_YEAR_site.
	 * 
	 * @return array
	 */
	public function get_catalog_years()
	{
		$return = array();
		$names = array_flip(reason_get_unique_names());
		if ($catalogs = preg_grep('/^academic_catalog_\d{4}_site$/', $names))
		{
			foreach ($catalogs as $catalog)
			{
				preg_match('/^academic_catalog_(\d{4})_site$/', $catalog, $matches);
				$return[$matches[1]] = $matches[1];
			}
		}
		return $return;
	}

	/**
	 * Convert a bare year (e.g. 2015) into an academic year display (e.g. 2015-16)
	 * 
	 * @param mixed $year
	 * @return string
	 */
	public function get_display_year($year)
	{
		return $year . '-' . ((int) substr($year, -2) + 1);
	}

	/**
	 * Convert a term code into the catalog year in which it occurs.
	 * 
	 * @param string $term
	 * @return integer
	 */
	public function term_to_academic_year($term)
	{
		if ($term)
		{
			list($year,$term) = explode('/',$term);
			$year = 2000 + $year;
			if (($term == 'WI') || ($term == 'SP')) $year--;
			return $year;
		}
	}

	/**
	 * Given a catalog year, return a date representing the start of that academic year. Used to
	 * identify which year a section is offered in. This is a simple default, which you can 
	 * override or extend to do a lookup or something.
	 * 
	 * @param integer $year
	 * @return string
	 */
	public function get_catalog_year_start_date($year)
	{
		return $year.'-09-01 00:00:00';
	}

	/**
	 * Given a catalog year, return a date representing the end of that academic year. Used to
	 * identify which year a section is offered in. This is a simple default, which you can 
	 * override or extend to do a lookup or something.
	 * 
	 * @param integer $year
	 * @return string
	 */
	public function get_catalog_year_end_date($year)
	{
		return ($year + 1).'-07-01 00:00:00';
	}
	
	public function get_catalog_blocks($year, $org_id, $type)
	{
		if ($site = id_of('academic_catalog_'.$year.'_site'))
		{
			$es = new entity_selector( $site );
			$es->description = 'Selecting catalog blocks on site';
			$es->add_type( id_of('course_catalog_block_type') );
			$es->add_relation('org_id = "'.carl_util_sql_string_escape($org_id).'"');
			$es->add_relation('block_type = "'.carl_util_sql_string_escape($type).'"');
			if ($results = $es->run_one())
				return $results;
		}
		else
		{
			trigger_error('No catalog site for '.$year.' in get_catalog_block()');
		}
		return array();
	}
	
/**
	 * Catalog content can contain tags (in {}) that dynamically include lists of courses based on
	 * values on the course objects. This method detects those tags and calls get_course_list() to 
	 * generate the appropriate list, which is then swapped in for the tag.
	 * 
	 * @param string $content
	 * @return string
	 */
	public function expand_catalog_tags($content)
	{
		if (preg_match_all('/\{([^\}]+)\}/', $content, $tags, PREG_SET_ORDER))
		{
			foreach ($tags as $tag)
			{
				// This is looking for {type key1="value" key2="value"} but it's very forgiving about 
				// extra spaces or failure to use the right quotes.
				if (preg_match('/\s*([^\s=]+)(\s+([^\s=]+)\s*=\s*["\']?([^"\'\b]+)["\'\b])*/', $tag[1], $matches))
				{
					$type = strtolower($matches[1]);
					for ($i = 2; $i < count($matches); $i = $i + 3)
					{
						$keys[$matches[$i + 1]] = $matches[$i + 2];
					}
					
					if (!$courses = $this->get_course_list($type, $keys))
						$courses = '<strong>No courses found for '.$tag[0].'</strong>';
					
					$content = str_replace($tag[0], $courses, $content);	
					
				}
				else
				{
					trigger_error('Badly formed catalog tag: '.$tag[1]);
				}
			}
		}
		
		return $content;
	}

	/**
	 * Catalog content can contain tags (in {}) that dynamically include lists of courses based on
	 * values on the course objects. This method takes the values from a tag and generates html for
	 * the corresponding courses. It uses an extension pattern so that custom methods/functions can 
	 * be defined to handle the generation of course lists for particular keys.
	 *  
	 * @param string $type What kind of list to generate (titles/descriptions)
	 * @param array $keys Key value pairs used to select the courses in the list
	 * @return string
	 */
	public function get_course_list($type, $keys)
	{
		$courses = array();
		foreach ($keys as $key => $val)
		{
			$function = 'get_courses_by_'.$key;
			if (method_exists($this, $function))
				$courses = array_merge($courses, $this->$function(array($val), $this->site));
			//else if (method_exists($this->helper, $function))
			//	$courses = array_merge($courses, $this->helper->$function(array($val), $this->site_id));
			else
			{
				trigger_error('No course function found: '.$function);
			}
		}
		
		if ($courses)
		{
			$html = '';
			
			if ($type == 'descriptions')
			{
				foreach ($courses as $course)
				{
					$html .= $this->get_course_html($course);
				}
			}
			else if ($type == 'titles')
			{
				$html .= '<ul class="courseList">'."\n";
				foreach ($courses as $course)
				{
					$course->set_academic_year_limit($this->year);
					$html .= '<li>'.$course->get_value('display_title');
					if (!$history = $course->get_offer_history())
						$html .= ' (not offered in '.$this->get_display_year($this->year).')';
					$html .= '</li>'."\n";
				}
				$html .= '</ul>'."\n";
			}
			else
			{
				trigger_error('Unrecognized catalog tag type: '.$type);
			}
			return $html;
		}
	}

	/**
	 * Given a course object, generate a default HTML snippet for its description. Override this if you
	 * want to use a diffferent pattern.
	 * 
	 * @param object $course
	 * @return string
	 */
	public function get_course_extended_description($course)
	{
		// Strip off surrounding paragraph tags
		$description = preg_replace(array('/^<p[^>]*>/','/<\/p>$/'), '', $course->get_value('long_description'));
		if ($prereqs = $course->get_value('list_of_prerequisites'))
			$description .= ' <span class="prereqLabel">Prerequisite:</span> '.trim($prereqs, " .").'. ';

		$html = '<span class="courseDescription">'. $description .'</span>';
		
		if ($credit = $course->get_value('credits'))
		{
			$details[] = $credit . (($credit == 1) ? ' credit' : ' credits');	
		}
		
		if ($grading = $course->get_value('grading'))
		{
			$details[] = $grading;	
		}

		if ($requirements = $course->get_value('requirements'))
		{
			$details[] = join(', ', $requirements);	
		}
		
		if ($history = $course->get_offer_history_html())
		{
			$details[] = $history;
		}
		
		if ($faculty = $course->get_value('display_faculty'))
		{
			$details[] = $faculty;
		}
		
		if (isset($details))
		{
			$html .= ' '.ucfirst(join('; ', $details));	
		}
		
		return $html;
	}
	
	/**
	 * Given a course object, generate a default HTML block for displaying it. Override this if you
	 * want to use a diffferent pattern.
	 * 
	 * @param object $course
	 * @return string
	 */
	public function get_course_html($course)
	{		
		$course->set_academic_year_limit($this->year);

		$html = '<div class="courseContainer">'."\n";
		$html .= $course->get_value('display_title');
		$html .= $this->get_course_extended_description($course);
		$html .= '</div>'."\n";
		
		return $html;
	}
	
}
