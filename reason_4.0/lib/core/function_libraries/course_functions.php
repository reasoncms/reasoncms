<?php

/**
 * Functions for manipulating and locating course entities.
 *
 * @author Mark Heiman
 * @package reason
 * @subpackage function_libraries
 */

include_once(CARL_UTIL_INC.'cache/object_cache.php');
 
class CourseTemplateEntityFactory
{
	public function get_entity(&$row)
	{
		return new CourseTemplateType($row['id']);	
	}
}

class CourseTemplateType extends Entity
{
	protected $sections;
	protected $limit_to_year = false;
	protected $external_data;
	
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
					$this->sections[$section->id()] = new CourseSectionType($section->id());
				}
			}
		}

		uasort($this->sections, 'sort_courses_by_number_and_date');
		
		// If an academic year limit has been set, only return those that match
		if ($this->limit_to_year && $honor_limit)
		{
			$sections = array();
			foreach ( $this->sections as $key => $section)
			{
				$year = term_to_academic_year($section->get_value('academic_session'));
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
	public function get_value_section_titles($refresh = true)
	{
		$titles = array();
		if ($sections = $this->get_sections())
		{
			foreach ($sections as $key => $section)
			{
				if (!in_array($section->get_value('title'), $titles))
				{
					$titles[$key] = $section->get_value('title');
				}
			}
		}
		return $titles;
	}
	
	/**
	  * 
	  * @todo if year limit is in effect, only grab description from that year or older sections
	  */
	public function get_value_long_description($refresh = true)
	{
		$start_date = 0;
		foreach ( $this->get_sections(false) as $key => $section)
		{
			if ($desc = $section->get_value('long_description'))
			{
				if ($section->get_value('timeframe_begin') > $start_date)
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
	  *
	  * @todo if year limit is in effect, only grab description from that year or older sections
	  */
	public function get_value_credits($refresh = true)
	{
		$sections = $this->get_sections(false);
		array_reverse($sections);
		
		$max = $min = 0;
		foreach ( $sections as $key => $section)
		{
			if ($credits = $section->get_value('credits'))
			{
				if (preg_match('/(\d+)-(\d+)/', $credits, $matches))
				{
					if (!$min || $matches[1] < $min)
						$min = $matches[1];
					if (!$max || $matches[2] > $max)
						$max = $matches[2];					
				}
				else
				{
					if (!$min || $credits < $min)
						$min = $credits;
					if (!$max || $credits > $max)
						$max = $credits;
				}
			}
		}
		
		if ($min)
		{
			if ($min != $max)
				return $min .'-'. $max;
			else
				return $min;
		}
		
		return parent::get_value('credits', $refresh);
	}
	
	/**
	  * Return the requirements fulfilled by this course. If no year limit is set,
	  * you'll get back all requirements fulfilled by any section. If a year limit 
	  * is set, and sections occur within that limit, you'll get back all the
	  * requirements met by that section. If a year limit is set and no sections occur
	  * in that year, you'll get the requirements met by the sections in the most
	  * recent year the course was offered.
	  *
	  * @param boolean $refresh
	  * @return array
	  */
	public function get_value_requirements($refresh = true)
	{
		$sections = $this->get_sections(false);
		array_reverse($sections);
		$requirements = array();
		foreach ( $sections as $key => $section)
		{
			if ($reqs = $section->get_value('requirements'))
			{
				if ($this->limit_to_year)
				{
					$year = term_to_academic_year($section->get_value('academic_session'));
					if ($year != $this->limit_to_year)
					{
						if (!isset($latest_reqs[$year]))
							$latest_reqs[$year] = $reqs;
						else
							$latest_reqs[$year] += $reqs;
						continue;
					}
				}
				$requirements += $reqs;
			}
		}
		if (!empty($requirements))
		{
			if ($this->limit_to_year)
				return filter_requirements_by_academic_year($requirements, $this->limit_to_year);
			else
				return $requirements;
		}
		else if (isset($latest_reqs))
		{
			ksort($latest_reqs);
			if ($this->limit_to_year)
				return filter_requirements_by_academic_year(end($latest_reqs), $this->limit_to_year);
			else
				return end($latest_reqs);
		}
		else
			return array();
	}
	
	/**
	 * Return how this course is graded. If no year limit is set, you'll get back the value for
	 * the most recent term. If a year limit is set, and sections occur within that limit, you'll 
	 * get back the value for the last section in that year. If a year limit is set and no sections occur
	 * in that year, you'll get the value from the most recent year the course was offered.
	 *
	 * @param boolean $refresh
	 * @return array
	 */
	public function get_value_grading($refresh = true)
	{
		$sections = $this->get_sections(false);
		array_reverse($sections);
		foreach ( $sections as $key => $section)
		{
			if ($grading = $section->get_value('grading', $refresh))
			{
				if ($this->limit_to_year)
				{
					$year = term_to_academic_year($section->get_value('academic_session'));
					if ($year > $this->limit_to_year)
						continue;
					else
						return $grading;
				}
			}
		}
	}
	
	/**
	 * Return the course level for this course. If no year limit is set, you'll get back the value for
	 * the most recent term. If a year limit is set, and sections occur within that limit, you'll 
	 * get back the value for the last section in that year. If a year limit is set and no sections occur
	 * in that year, you'll get the value from the most recent year the course was offered.
	 *
	 * @param boolean $refresh
	 * @return array
	 */
	public function get_value_course_level($refresh = true)
	{
		$sections = $this->get_sections(false);
		array_reverse($sections);
		foreach ( $sections as $key => $section)
		{
			if ($level = $section->get_value('course_level', $refresh))
			{
				if ($this->limit_to_year)
				{
					$year = term_to_academic_year($section->get_value('academic_session'));
					if ($year > $this->limit_to_year)
						continue;
					else
						return $level;
				}
			}
		}
	}
	
	/**
	 * Return the gov codes for this course. If no year limit is set, you'll get back the values for
	 * the most recent term. If a year limit is set, and sections occur within that limit, you'll 
	 * get back the values for the last section in that year. If a year limit is set and no sections occur
	 * in that year, you'll get the values from the most recent year the course was offered.
	 *
	 * @param boolean $refresh
	 * @return array
	 */
	public function get_value_gov_codes($refresh = true)
	{
		$sections = $this->get_sections(false);
		array_reverse($sections);
		foreach ( $sections as $key => $section)
		{
			if ($codes = $section->get_value('gov_codes', $refresh))
			{
				if ($this->limit_to_year)
				{
					$year = term_to_academic_year($section->get_value('academic_session'));
					if ($year > $this->limit_to_year)
						continue;
					else
						return $codes;
				}
			}
		}
		return array();
	}
	
	/**
	  * Return an array of faculty who teach sections of this course.
	  * 
	  * @return array id => name
	  */
	public function get_value_faculty($refresh = true)
	{
		$sections = $this->get_sections();
		$faculty = array();
		foreach ( $sections as $key => $section)
		{
			if ($fac = $section->get_value('faculty', $refresh))
			{
				$faculty = $faculty + $fac;
			}
		}
		return $faculty;
	}

	public function get_value_start_date($refresh = true)
	{
		$this->fetch_external_data($refresh);
		if (isset($this->external_data['CRS_START_DATE']))
			return $this->external_data['CRS_START_DATE'];
	}
	
	public function get_value_end_date($refresh = true)
	{
		$this->fetch_external_data($refresh);
		if (isset($this->external_data['CRS_END_DATE']))
			return $this->external_data['CRS_END_DATE'];
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
			$year = term_to_academic_year(end($history));
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

	protected function fetch_external_data()
	{
		if (empty($this->external_data))
		{
			// Do we want to check for the empty array here?
			if ($cache = $this->get_value('cache'))
			{
				$this->external_data = json_decode($cache, true);
			}
			
			if (!isset($this->external_data))
			{
				$query = 'SELECT * FROM colleague_data.IDM_CRS WHERE COURSES_ID = '.$this->get_value('sourced_id');

				if ($result = mysql_query($query))
				{
					$this->external_data = mysql_fetch_assoc($result);
					$this->external_data['timestamp'] = time();
				} else {
					// Indicate that we've tried and failed to retrieve the data, so we don't keep trying
					$this->external_data = array();
				}

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
		return new CourseSectionType($row['id']);	
	}
}

class CourseSectionType extends Entity
{	
	protected $external_data = array();
	
	public function get_template()
	{
		if ($entities = $this->get_right_relationship('course_template_to_course_section'))
		{
			$template = reset($entities);
			return new CourseTemplateType($template->id());
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
	
	public function get_value_requirements($refresh = true)
	{
		$this->fetch_external_data($refresh);
		if (isset($this->external_data['section']['XSEC_COURSE_TYPES_LIST']))
		{
			$reqs = explode(' ', $this->external_data['section']['XSEC_COURSE_TYPES_LIST']);
			
			// Suppress any codes we shouldn't show
			$reqs = array_diff($reqs, array('CX','IE','LP'));
			
			return $reqs;
		}
	}
	
	public function get_value_faculty($refresh = true)
	{
		$this->fetch_faculty_data($refresh);
		$faculty = array();
		foreach ($this->external_data['faculty'] as $id => $data)
		{
			$faculty[$id] = $data['Carleton_Name'];
		}
		return $faculty;
	}
	
	/**
	  * @todo Find out why some 400 courses are marked S/CR/NC but most are S/NC
	  *
	  */
	public function get_value_grading($refresh = true)
	{
		$this->fetch_external_data($refresh);
		if (isset($this->external_data['section']['SEC_ONLY_PASS_NOPASS_FLAG']))
		{
			if (($this->external_data['section']['SEC_ONLY_PASS_NOPASS_FLAG'] == 'Y') && strpos('10', $this->external_data['section']['XSEC_SEC_COURSE_LEVELS_SV']) === false)
			{
				if ($this->external_data['section']['SEC_COURSE_NO'] == '400')
					return 'S/NC';
				else
					return 'S/CR/NC';
			}
		}
	}
	
	public function get_value_course_level($refresh = true)
	{
		$this->fetch_external_data($refresh);
		if (isset($this->external_data['section']['XSEC_SEC_COURSE_LEVELS_SV']))
		{
			return $this->external_data['section']['XSEC_SEC_COURSE_LEVELS_SV'];
		}
	}
	
	public function get_value_gov_codes($refresh = true)
	{
		$this->fetch_external_data($refresh);
		if (isset($this->external_data['section']['XSEC_LOCAL_GOVT_CODES_SV']))
		{
			return explode('|',$this->external_data['section']['XSEC_LOCAL_GOVT_CODES_SV']);
		}
		return array();
	}
	
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
				$query = 'SELECT * FROM colleague_data.IDM_COURSE WHERE COURSE_SECTIONS_ID = '.$this->get_value('sourced_id');

				if ($result = mysql_query($query))
				{
					$this->external_data['section'] = mysql_fetch_assoc($result);
					$this->external_data['timestamp'] = time();
				} else {
					// Indicate that we've tried and failed to retrieve the data, so we don't keep trying
					$this->external_data['section'] = array();
				}

				if (!empty($this->external_data['section']))
					$this->update_cache();
			}
		}
	}
	
	protected function fetch_faculty_data($refresh = false)
	{
		if (empty($this->external_data['faculty']))
		{
			if ($cache = $this->get_value('cache'))
			{
				$this->external_data = json_decode($cache, true);
			}

			if ($refresh || !isset($this->external_data['faculty']))
			{
				$this->external_data['faculty'] = array();
				$query = 'SELECT Id, First_Name, NickName, Last_Name, Carleton_Name, Fac_Catalog_Name FROM colleague_data.IDM_CRS_SEC_FACULTY, colleague_data.EmployeesByPosition_All 
					WHERE CSF_COURSE_SECTION = '.$this->get_value('sourced_id') .'
					AND CSF_FACULTY = EmployeesByPosition_All.Id';

				mysql_set_charset('utf8');
				if ($result = mysql_query($query))
				{
					while ($row = mysql_fetch_assoc($result))
					{
						$this->external_data['faculty'][$row['Id']] = $row;	
					}
					$this->external_data['timestamp'] = time();
				}

				if (!empty($this->external_data['faculty']))
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
  * Find courses that have been attached to the specified page.
  *
  */
function get_page_courses($page_id)
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
function get_site_courses($site)
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
function get_courses_by_category($cats, $catalog_site = null)
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
function get_courses_by_subjects($codes, $catalog_site = null)
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

function get_courses_by_org_id($codes, $catalog_site = null)
{
	return get_courses_by_subjects($codes, $catalog_site);
}

/**
  * Find courses by their id in the source data
  *
  * @param array $codes Array of ids
  * @param string or int $catalog_site Optional unique name or id of a catalog site. If you pass this,
  *				only courses associated with that site will be included.
  */
function get_courses_by_sourced_id($ids, $catalog_site = null)
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

function sort_courses_by_name($a, $b)
{
	$a_name = $a->get_value('name');
	$b_name = $b->get_value('name');
	if ($a_name == $b_name) {
		return 0;
	}
	return ($a_name < $b_name) ? -1 : 1;
}

function sort_courses_by_date($a, $b)
{
	$a_name = $a->get_value('timeframe_begin');
	$b_name = $b->get_value('timeframe_begin');
	if ($a_name == $b_name) {
		return 0;
	}
	return ($a_name > $b_name) ? -1 : 1;
}

function sort_courses_by_number_and_date($a, $b)
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
function get_course_subjects($year = null)
{
	$cache = new ObjectCache('course_subject_cache_'.$year, 60*24);
	if ($subjects = $cache->fetch()) return $subjects;

	$subjects = array();
	$q = 'SELECT distinct org_id FROM course_section';
	if ($year) $q .= ' WHERE timeframe_begin > "'.get_catalog_year_start_date($year).'" AND timeframe_end < "'.get_catalog_year_end_date($year).'"';
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
  * Get the list of years for which we have catalog sites.
  *
  * @todo Finish
  */
function get_catalog_years()
{
	return array(2013=>2013, 2014=>2014, 2015=>2015);
}

function get_display_year($year)
{
	return $year . '-' . ((int) substr($year, -2) + 1);
}

function term_to_academic_year($term)
{
	list($year,$term) = explode('/',$term);
	$year = 2000 + $year;
	if (($term == 'WI') || ($term == 'SP')) $year--;
	return $year;
}

function get_catalog_year_start_date($year)
{
	return $year.'-09-01 00:00:00';
}

function get_catalog_year_end_date($year)
{
	return ($year + 1).'-07-01 00:00:00';
}

/**
  * Some requirements only apply for some year ranges. This filters a list based on the provided year
  * and gives you the ones you want.
  */
function filter_requirements_by_academic_year($reqs, $year)
{
	foreach($reqs as $key => $req)
	{
		if ($year > 2013)
			if (in_array($req, array('AL','HU','SS','MS','RAD','WR','ND')))
				unset($reqs[$key]);
	}
	return $reqs;
}


