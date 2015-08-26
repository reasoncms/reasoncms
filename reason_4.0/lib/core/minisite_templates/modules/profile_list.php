<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'minisite_templates/modules/profile/config.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ProfileDisplayModule';

/**
 * Profile Display Module
 * This module displays lists of profiles based on the combination of parameters passed in the 
 * page type. Some of the parameters rely on the existence of a built out department -> subject -> major
 * structure in your Reason instance, but most should work with just a basic profiles setup.
 */
class ProfileDisplayModule extends DefaultMinisiteModule
{
	public $acceptable_params = array(
		// Retrieve profiles owned or borrowed by the site
		'get_site_profiles' => false,
		
		// Retrieve profiles attached to this (or another) page
		'get_page_profiles' => false,
		
		// Alternate page to draw profiles from
		'source_page_unique_name' => null,
		
		// Retrieve profiles of people with one of the listed majors
		'get_profiles_by_majors' => array(),
		
		// Retrieve profiles of people whose major matches the subject associated with the
		// office/department entity associated with this site.
		'get_profiles_by_site_subjects' => false,
		
		// Retrieve profiles of people with a particular interest tag
		'get_profiles_by_tags' => array(),

		// Retrieve profiles of people interest tags matching the categories attached to this page
		// (or the page specified by source_page)
		'get_profiles_by_page_categories' => false,
		
		// Show profiles that have child tags of any of the tags designated above (NOT IMPLEMENTED)
		'descend_tag_hierarchy' => false,
		
		// Show photos with profiles
		'show_photos' => true,
		
		// Include content from the listed profile sections
		'include_profile_sections' => array(),
		
		// Only show profiles who have content in the listed profile sections
		'require_profile_sections' => array(),
		
		// Show link to full profile
		'show_profile_link' => true,
		
		// Only show profiles from the listed affiliations
		'limit_to_profile_types' => array('student','faculty','staff','alum'),
		
		// Limit the number of profiles shown (0 for all)
		'max_shown' => 0,
		
		// Whether to sort the profiles into career buckets or not
		'organize_by_field' => false,
		
		// Add a list of internal links to the field sections
		'show_field_links' => false,

		// Randomize the profiles before displaying
		'randomize' => false,

		'profile_link_base' => '',
		);
		
	protected $config;
	protected $pc;
	protected $profiles = array();
	
	function init( $args = array() )
	{
		parent::init($args);

		$this->config = new profileConfig();

		if($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/profiles/list.css');
		}

		if (empty($this->params['profile_link_base']))
			$this->params['profile_link_base'] = reason_get_site_url(id_of($this->config->profiles_site_unique_name));
		
		$this->pc = new $this->config->connector_class();
		
		// Go through all the parameters and grab all the profiles that match what's being requested.
		// We'll sort through them and throw out any that don't apply at the run stage -- it's
		// more efficient that way.
		
		if ($this->params['get_site_profiles'])
			$this->get_site_profiles();

		if ($this->params['get_page_profiles'])
			$this->get_page_profiles();

		foreach ($this->params['get_profiles_by_tags'] as $tag)
			$this->get_tag_profiles($tag);
		
		if ($this->params['get_profiles_by_page_categories'])
			$this->get_page_category_profiles();
		
		if ($this->params['get_profiles_by_majors'])
			$this->get_profiles_by_major($this->params['get_profiles_by_majors']);

		if ($this->params['get_profiles_by_site_subjects'])
			$this->get_profiles_by_site_subjects();

		if ($this->params['randomize'])
			shuffle($this->profiles);
		else
			uasort($this->profiles, array($this, 'sort_profiles_by_name'));
	}
	
	function run()
	{
		$html = '';
		$count = $protected_count = 0;
		$buckets = array();
		foreach ($this->profiles as $person)
		{
			if (!$person->is_valid()) continue;
			
			// Discard any profiles that don't match the affiliation restrictions
			$affiliations = $person->get_ds_value('ds_affiliation');
			if (!count(array_intersect($this->params['limit_to_profile_types'], $affiliations))) continue;
			
			if (($person->get_profile_field('visibility') == 'local') && !reason_check_authentication()) 
			{
				$protected_count++;
				continue;
			}
			
			// Increment only if we got content for this person
			if ($content = $this->get_profile_html($person)) 
			{
				if (!$this->params['organize_by_field'])
				{
					$html .= $content;
					$count++;
				}
				else 
				{
					$emp_data = $person->get_alum_employment();
					$field = (!empty($emp_data['field'])) ? $emp_data['field'] : 'Other Careers';
						
					$buckets[$field][] = $content;
				}
			}
			// Stop if we've reached our limit
			if ($this->params['max_shown'] > 0 && $count >= $this->params['max_shown']) break;
		}
		
		if (!$this->params['organize_by_field'])
		{
			echo $html;
		} else {
			ksort($buckets);
			
			if ($this->params['show_field_links'])
			{
				echo '<ul class="fieldLinks">';
				foreach ($buckets as $field => $profiles)
				{	
					echo '<li><a href="#'.preg_replace('/\W/', '', $field).'">'.$field.'</a></li>';
				}
				echo '</ul>';
			}
			
			foreach ($buckets as $field => $profiles)
			{
				echo '<h3 class="careerField"><a name="'.preg_replace('/\W/', '', $field).'">'.$field.'</a></h3>';
				foreach ($profiles as $html)
					echo $html;
			}
		}
	}
	
	/**
	 * Build the HTML display for an individual profile
	 *
	 * @param object $person  profilePerson
	 * @return text
	 */
	protected function get_profile_html($person)
	{
		// Grab any profile section content requested. If profile section content is required, 
		// bail out of this call if we don't get any back. Otherwise, we'll use $fields down below.
		if ($this->params['include_profile_sections'] || $this->params['require_profile_sections'])
		{
			$fields = $this->get_profile_fields($person);
			
			if ($this->params['require_profile_sections'] && !array_intersect($this->params['require_profile_sections'], array_keys($fields)))
			{
				return null;
			}
		}

		$html = '<div class="profileContainer">'."\n";
		$name = $person->get_display_name();
		if ($this->params['show_photos'] && $image = $person->get_image())
		{	
			$html .= '<div class="profilePhoto">'."\n";
			$link = ($this->params['show_profile_link']) ? $this->params['profile_link_base'].$person->get_username() : htmlspecialchars($image['link']);
			$html .= '<a href="'.$link.'"><img src="'.htmlspecialchars($image['src']).'" width="200" height="200" alt="'.htmlspecialchars($image['alt']).'" /></a>'."\n";
			$html .= '</div>'."\n";
		}
		$html .= '<div class="profileText">'."\n";
		$html .= '<h4 class="name">'.htmlspecialchars($name).'</h4>';
		
		if ($majors = $person->get_majors())
		{
			$html .= '<p class="majors">'."\n";
			if (!empty($majors['majors'])) $major_string[] = '<span class="majors">'.implode(', ',$majors['majors']).'</span>';
			if (!empty($majors['concentrations'])) $major_string[] = '<span class="concentrations">'.implode(', ',$majors['concentrations']).'</span>';
			
			if (isset($major_string))
				$html .= join(', ', $major_string)."\n";
			$html .= '</p>'."\n";			
		}
		
		if ($person->is_affil('alum') && $emp_data = $person->get_alum_employment())
		{
			$title_string = (isset($emp_data['title'])) ? $emp_data['title'] : '';
			if (isset($emp_data['employer']))
				$title_string .= ($title_string) ? ', '.$emp_data['employer'] : $emp_data['employer'];
			if ($title_string)
				$html .= '<p class="employment">'.$title_string.'</p>'."\n";
		}

		// Include any profile section content ($fields is defined at the top)
		if ($this->params['include_profile_sections'] && $sections = $this->get_sections_html($fields))
			$html .= $sections;

		$html .= '</div>'."\n";
		
		if ($this->params['show_profile_link'])
		{
			$html .= '<p class="profileLink"><a href="'.$this->params['profile_link_base'].$person->get_username().'">View Full Profile</a></p>'."\n";	
		}
		
		$html .= '</div>'."\n";
		
		return $html;
	}
	
	/**
	  * Assemble the html to display the profile sections listed in the include_profile_sections
	  * parameter for a single profile.
	  *
	  * @param array $fields  Array of profile fields generated by get_profile_fields()
	  */
	protected function get_sections_html($fields)
	{
		$html = '';
		foreach ($this->params['include_profile_sections'] as $section)
		{
			if (isset($fields[$section]))
			{
				if (count($fields) > 1)
					$html .= '<h5 class="profileSection">'.$fields[$section]['name'].'</h5>'."\n";
			
				$html .= $fields[$section]['content'];
			}
		}
		return $html;
	}
	
	protected function get_profile_fields($person)
	{
		$fields = array();
		$sections = array_unique(array_merge($this->params['include_profile_sections'], $this->params['require_profile_sections']));
		foreach ($sections as $section)
		{
			if ($name = $this->pc->get_section_name($section, $person))
			{
				if ($data = $person->get_profile_field($section))
				{
					$fields[$section]['name'] = $name;
					$fields[$section]['content'] = $data;
				}
			} else {
				trigger_error('Invalid section name ('.$section.') passed in get_profile_fields');
			}
		}
		return $fields;
	}	
	
	protected function get_tags_html($person, $section)
	{		
		$tags_str = '';
		if ($person && ($interest_tags = $person->get_categories($section)))
		{	
			$tags_str .= '<ul class="tagList">' . "\n";
			foreach ($interest_tags as $slug => $tag)
			{
				$text = htmlspecialchars($tag);
				$tags_str .= '<li><a class="interestTag" href="'.reason_get_site_url(id_of($this->config->profiles_site_unique_name)).$this->config->explore_slug.'/'.htmlspecialchars($slug).'" title="Find others with this interest">'.htmlspecialchars($tag).'</a></li>' ."\n";
			}
			$tags_str .= '</ul>' . "\n";
		}
		return $tags_str;
	}	
	
	/**
	  * Get the id of the page that we should be looking to for associated profiles. If the source_page
	  * parameter is set, we use that page; if not, we use the current page.
	  */
	protected function get_source_page_id()
	{
		if($this->params['source_page_unique_name'])
		{
			if ($page_id = id_of($this->params['source_page_unique_name']))
				return $page_id;
			else
				trigger_error('source_page_unique_name parameter to profile_display module not a valid unique name');
		}
		return $this->cur_page->id();
	}

	/**
	  * Find profiles that have been attached to the current page and add them to our collection.
	  *
	  */
	protected function get_page_profiles()
	{
		$this->es = new entity_selector();
		$this->es->description = 'Selecting profiles for this page';
		$this->es->add_type( id_of('profile_type') );
		$this->es->add_right_relationship( $this->get_source_page_id(), relationship_id_of('page_to_profile') );
		$results = $this->es->run_one();
		foreach ($results as $id => $entity)
		{
			if (isset($this->profiles[$id])) continue;
			$this->profiles[$id] = new $this->config->person_class($entity->get_value('user_guid'), 'ds_guid');
		}
	}
	
	/**
	  * Find profiles that are owned or borrowed by the current site and add them to our collection.
	  *
	  */
	protected function get_site_profiles()
	{
		$this->es = new entity_selector( $this->site_id );
		$this->es->description = 'Selecting profiles on site';
		$this->es->add_type( id_of('profile_type') );
		$results = $this->es->run_one();
		foreach ($results as $id => $entity)
		{
			if (isset($this->profiles[$id])) continue;
			$this->profiles[$id] = new $this->config->person_class($entity->get_value('user_guid'), 'ds_guid');
		}
	}
	
	/**
	  * Find profiles that use a given interest tag and add them to our collection.
	  *
	  * @param string $slug  Tag slug
	  */
	protected function get_tag_profiles($slug)
	{
		if ($connections = $this->pc->get_connections_for_tag($slug))
		{
			if (isset($connections['tags']))
			{
				foreach ($connections['tags'] as $tagid => $tag)
				{
					if (isset($tag['profiles']['profile_to_interest_category']))
					{
						foreach ($tag['profiles']['profile_to_interest_category'] as $id => $profile)
						{
							if (isset($this->profiles[$id])) continue;
							$this->profiles[$id] = new $this->config->person_class($profile['ds_username']);
						}
					}
				}
			}
		}
	}
	
	/**
	  * Find profiles that use interest tags that match categories attached to this page
	  * and add them to our collection.
	  *
	  */
	protected function get_page_category_profiles()
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
			// Call get_tag_profiles on each category to find corresponding profiles
			foreach ($categories as $cat)
			{
				$this->get_tag_profiles($cat->get_value('slug'));	
			}
		}
	}
	
	/**
	  * Find profiles of people with particular majors and add them to our collection.
	  *
	  * @param array $codes  Array of major codes
	  */
	protected function get_profiles_by_major($codes)
	{
		$all_profiles = $this->pc->get_profiles_by_date();
		foreach ($all_profiles as $affil => $profiles)
		{
			if ($affil != 'student' && $affil != 'alum') continue;
			foreach ($profiles as $advid => $data)
			{
				if (isset($data['major']['majors']) && array_intersect($codes, array_keys($data['major']['majors'])))
				{
					$person = new $this->config->person_class($data['netid']);
					$id = $person->get_profile_id();
					if (isset($this->profiles[$id])) continue;
					$this->profiles[$id] = $person;
				}
			}
		}
	}

	/**
	  * Find profiles whose major matches any subjects associated with the office/department
	  * entity attached to the current site and add them to our collection.
	  *
	  */
	protected function get_profiles_by_site_subjects()
	{
		if (!relationship_id_of('office_department_has_site') || !relationship_id_of('office_department_to_subject'))
		{
			trigger_error('Your Reason instance does not have the appropriate relationships to use get_profiles_by_site_subjects');
			return;
		}
		
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
				if ($subject->get_value('sync_name')) $codes[] = $subject->get_value('sync_name');
		}
		
		if (isset($codes)) $this->get_profiles_by_major($codes);
	}

	protected function sort_profiles_by_name($a, $b)
	{
		$a_name = $a->get_first_ds_value('ds_lastname');
		$b_name = $b->get_first_ds_value('ds_lastname');
		if ($a_name == $b_name) {
			return 0;
		}
		return ($a_name < $b_name) ? -1 : 1;
	}
	
}
