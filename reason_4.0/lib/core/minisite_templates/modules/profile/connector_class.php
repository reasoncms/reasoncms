<?php
/**
 * @package reason_local
 * @subpackage classes
 */

reason_include_once( 'minisite_templates/modules/profile/config.php' );

/**
 * Profile Connector Class
 *
 * This is a collection of methods for getting information about profiles and tags,
 * and for managing the tag cache that profile applications use for performance.
 *
 * @author Mark Heiman
 */
class ProfileConnector
{	
	/** 
	 * Profile tag data is heavily cached, because it is expensive to recreate.
	 * This value determines how often the cache is rebuilt/
	 */
	protected $cache_life = 21600; //6 hours
	
	/** List of affiliations that your profiles support, in the format
	 * directory_service_value => display_name
	 */
	public $affiliations = array(
		'student' => 'Students',
		'faculty' => 'Faculty',
		'staff' => 'Staff',
		'alum' => 'Alumni',
		);
	
	/** 
	 * Whether the master tag_cache has been rebuilt during this session.
	 * We occasionally need to rebuild it, but we don't want to do it more
	 * than once for a single request.
	 */
	protected $refreshed = false;
	
	protected $config;
	
	/** Storage for cached data */
	protected $tag_cache;
	protected $slug_index;
	protected $relation_index;

	/**
	 * When the class is instantiated, load all the cached data
	 */
	function __construct($refresh_cache = false)
	{
		$this->config = new ProfileConfig();
		$this->load_all_caches($refresh_cache);
	}
	
	/**
	  * Given an array of profiles structured as array(profile_id => array_of_ds_results),
	  * this method returns the data sorted into buckets by primary affiliation
	  *
	  * @param array $profiles
	  * @return array
	  */
	function get_profiles_by_affiliation($profiles)
	{
		$profiles_by_affil = array();
		foreach ($profiles as $profile_id => $profile)
		{
			if (is_array($profile))
			{
				// Only track affiliations we support
				if (!isset($this->affiliations[$profile['ds_affiliation'][0]])) continue;
				
				$profile['display_name'] = $profile['ds_fullname'][0];
				if (isset($profile['ds_classyear'][0]))
					$profile['display_name'] .= ' ’'.substr($profile['ds_classyear'][0], -2);
				$profiles_by_affil[$profile['ds_affiliation'][0]][$profile['ds_username'][0]] = $profile;
			} else {
				error_log( 'No affilation for '.$profile['ds_fullname'][0] . '('.$profile['ds_affiliation'][0].')');	
			}
		}
	
		return $profiles_by_affil;
	}
	
	/**
	  * Given a profile person object and an array of profiles grouped by affiliation 
	  * (produced by @see get_profiles_by_affiliation()), this method returns that array sorted so that 
	  * the person's affiliation is first.
	  *
	  * @param object $person ProfilePerson 
	  * @param array $profiles_by_affil 
	  * @return array
	  */
	function sort_profiles_by_user_affiliations($person, $profiles_by_affil)
	{
		// Sort the affiliations based on those held by the profilee
		if ($person && $affiliations = $person->get_ds_value('ds_affiliation'))
		{
			foreach ($affiliations as $affil)
			{
				if (isset($profiles_by_affil[$affil]))
				{
					$sorted_profiles_by_affil[$affil] = $profiles_by_affil[$affil];
					unset($profiles_by_affil[$affil]);
				}
			}
			
			if (isset($sorted_profiles_by_affil))
				$profiles_by_affil = array_merge($sorted_profiles_by_affil, $profiles_by_affil);
		}
		return $profiles_by_affil;
	}
	
	/**
	  * Returns the profile section display name for the corresponding Reason relationship. If
	  * you pass a profilePerson object, the name will match the label used on that person's
	  * profile. e.g. profile_to_interest_category returns "Professional Interests" for a staff person.
	  *
	  * @param string $rel Allowable relationship name
	  * @param object $person ProfilePerson 
	  */
	function get_section_name_from_relationship($rel, $person = null)
	{
		if (isset($this->config->tag_section_relationship_names[$rel]))
			return $this->get_section_name($this->config->tag_section_relationship_names[$rel], $person);
		
		return $rel;
	}
	
	/**
	  * Returns the profile section display name for the corresponding section internal name. If
	  * you pass a profilePerson object, the name will match the label used on that person's
	  * profile. e.g. 'tags' returns "Professional Interests" for a staff person.
	  *
	  * @param string $section 
	  * @param object $person ProfilePerson 
	  */
	function get_section_name($section, $person = null)
	{
		$affil = ($person) ? $person->get_first_ds_value('ds_affiliation') : 'none';
		
		if (isset($this->config->profile_sections_by_affiliation[$affil][$section]) && is_string($this->config->profile_sections_by_affiliation[$affil][$section]))
			return $this->config->profile_sections_by_affiliation[$affil][$section];
		else if (isset($this->config->profile_sections_by_affiliation[$affil][$section]['label']))
			return $this->config->profile_sections_by_affiliation[$affil][$section]['label'];
		else if (isset($this->config->section_defaults[$section]['label']))
			return $this->config->section_defaults[$section]['label'];
		else
			return ucwords(str_replace('_',' ', $section));		
	}
	
	/**
	  * Given the name of a Reason relationship, this method returns the corresponding
	  * tag section slug.
	  *
	  * @param string $rel Relationship name
	  * @return string Profile section
	  *
	  */
	function get_section_id_from_relationship($rel)
	{
		$sections = array_flip($this->config->tag_section_relationship_names);
		if (isset($sections[$rel])) return $sections[$rel];
		return false;
	}
	
	/**
	  * Generates an array of connection data for a given user. The returned array looks like this:
	  * array(section_name => array(tag_id => tag_data))
	  *
	  * @param object $p Profile 
	  * @return array
	  */
	function get_connections_for_user($p)
	{
		$connections = $profile_ids = array();
		foreach ($this->config->tag_section_relationship_names as $section => $rel)
		{
			$categories = array();
			if ( ($profile_id = $p->get_profile_id()) && ($rel_id = relationship_id_of($rel)))
			{
				$es = new entity_selector();
				$es->add_type(id_of('category_type'));
				$es->add_right_relationship($profile_id, $rel_id);
				if ($results = $es->run_one())
				{
					foreach($results as $result)
					{
						if ($tag = $this->get_tag_by_id($result->get_value('id')))
						{
							if (isset($tag['profiles'][$rel]))
							{
								// Drop the user from the list of profiles, and if that
								// makes the list empty, drop the tag.
								unset($tag['profiles'][$rel][$profile_id]);
								if (empty($tag['profiles'][$rel])) continue;
								
								$connections[$section][$result->get_value('id')] = $tag;
								$profile_ids = array_merge($profile_ids, $tag['profiles'][$rel]);
							}
						}
					}						
				}
			}
		}
		
		// Merge the profile data into the connections array, pruning any branches without connections
		if ($profile_ids)
		{
			$dir_data = $this->get_directory_data_for_profile_ids($profile_ids);
			
			foreach ($connections as $section => $tags)
			{
				foreach ($tags as $tag => $tag_data)
				{
					foreach ($tag_data['profiles'] as $rel => $profiles)
					{
						if ($section !== $this->get_section_id_from_relationship($rel)) continue;
						foreach ($profiles as $key => $profile)
						{							
							if (isset($dir_data[$profile]) && isset($this->affiliations[$dir_data[$profile]['edupersonprimaryaffiliation'][0]]))
							{
								$connections[$section][$tag]['profiles'][$rel][$key] = $dir_data[$profile];	
							} else {
								unset($connections[$section][$tag]['profiles'][$rel][$key]);
							}
						}
						if (empty($connections[$section][$tag]['profiles'][$rel]))
						{
							unset($connections[$section][$tag]['profiles'][$rel]);
						}

					}
					if (empty($connections[$section][$tag]['profiles']))
					{
						unset($connections[$section][$tag]);
					}
				}
			}
		}
		return $connections;		
	}
	
	/**
	  * Generates an array of connection data for a given tag. The returned array looks like this:
	  * array(section_name => array(tag_id => tag_data))
	  *
	  * @param string $slug
	  * @return array
	  */
	function get_connections_for_tag($slug)
	{
		$connections = $profile_ids = array();
		if (!($tag = $this->get_tag_by_slug($slug))) return $connections;
		

		if (isset($tag['profiles']))
		{
			foreach ($tag['profiles'] as $rel => $profiles)
			{
				$connections[$this->get_section_id_from_relationship($rel)][$tag['id']] = $tag;
				$profile_ids = array_merge($profile_ids, $profiles);
			}
		}
		
		if (isset($tag['children']))
		{
			foreach ($tag['children'] as $id)
			{
				if (($tag = $this->get_tag_by_id($id)) && isset($tag['profiles']))
				{
					foreach ($tag['profiles'] as $rel => $profiles)
					{
						$connections[$this->get_section_id_from_relationship($rel)][$tag['id']] = $tag;
						$profile_ids = array_merge($profile_ids, $profiles);
					}
				}
			}
		}
				
		if ($profile_ids)
		{
			$dir_data = $this->get_directory_data_for_profile_ids($profile_ids);
					
			foreach ($connections as $section => $tags)
				foreach ($tags as $tag => $tag_data)
					foreach ($tag_data['profiles'] as $rel => $profiles)
					{
						if ($section !== $this->get_section_id_from_relationship($rel)) continue;
						foreach ($profiles as $key => $profile)
							if (isset($dir_data[$profile]) && isset($this->affiliations[$dir_data[$profile]['edupersonprimaryaffiliation'][0]]))
							{
								$connections[$section][$tag]['profiles'][$rel][$key] = $dir_data[$profile];	
							} else {
								unset($connections[$section][$tag]['profiles'][$rel][$key]);
								if (empty($connections[$section][$tag]['profiles'][$rel]))
									unset($connections[$section][$tag]);
							}
					}
		}

		return $connections;
	}
	
	/**
	  * Extends the internal tag cache by adding child and parent data about the passed tag.
	  *
	  * @param string $slug
	  */
	function get_relations_for_tag($slug)
	{
		$relations = array();
		$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
		$es->enable_multivalue_results();
		$es->add_type(id_of('category_type'));
		$es->add_relation('slug="'.mysql_real_escape_string($slug).'"');
		$es->add_left_relationship_field( 'parent_category_to_category' , 'entity' , 'id' , 'child_id',  false );
		if ($results = $es->run_one())
		{
			foreach($results as $result)
			{
				if ($children = $result->get_value('child_id'))
				{
					if (!is_array($children)) $children = array($children);
					
					foreach ($children as $id)
					{
						$this->tag_cache[$result->id()]['children'][$id] = $id;
						$this->tag_cache[$id]['parents'][$result->id()] = $result->id();
					}
				}
			}
		}
	}
	
	/**
	  * Returns an array of URLs of pages that have the given tag attached to them.
	  *
	  * @param string $slug
	  * @return array
	  */
	function get_pages_for_tag($slug)
	{
		$links = array();
		$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
		$es->enable_multivalue_results();
		$es->add_type(id_of('category_type'));
		$es->add_relation('slug="'.mysql_real_escape_string($slug).'"');
		$es->add_right_relationship_field( 'page_to_category' , 'entity' , 'id' , 'page_id',  false );
		if ($results = $es->run_one())
		{
			foreach($results as $result)
			{
				if ($pages = $result->get_value('page_id'))
				{
					if (!is_array($pages)) $pages = array($pages);
					
					foreach ($pages as $id)
					{
						$page = new Entity($id);
						$owner = $page->get_owner();
						$site_name = $owner->get_value('unique_name');
						$links[$site_name][$id] = build_URL($id);
					}
				}
			}
		}
		return $links;
	}
	
	/**
	  * Given an array of Reason profile IDs, this method returns an array of directory service results keyed
	  * on the profile ids.
	  */
	function get_directory_data_for_profile_ids($ids)
	{				
		if (empty($ids)) return array();
		
		$records_by_id = array();
		$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
		$es->add_type(id_of('profile_type'));
		$es->add_relation(' entity.id in ('.join(',', $ids).')');
		if ($results = $es->run_one())
		{
			foreach($results as $result)
			{
				/* Carleton-specific */
				$filter_parts[$result->id()] = sprintf('(carlcolleagueid=%07s)', $result->get_value('colleague_id'));
				$guid_to_profile_id[$result->get_value('colleague_id')] = $result->id();
			}
		}
		if (count($filter_parts) > 1)
			$filter = '(|'.join($filter_parts).')';
		else
			$filter = reset($filter_parts);
		
		$ds = new directory_service('ldap_carleton');
		$ds->search_by_filter($filter, array('carlcolleagueid','carladvanceid','ds_guid','ds_fullname','ds_affiliation','ds_classyear'));
		$records = $ds->get_records();
		
		foreach ($records as $record)
		{
			$records_by_id[$guid_to_profile_id[(int)$record['carlcolleagueid'][0]]] = $record;	
		}
		
		return $records_by_id;
	}

	/**
	 * Build an array of profile data sorted by profile creation date. This is an expensive operation used for
	 * reporting purposes.
	 */
	function get_profiles_by_date($refresh = false)
	{
		$cache = new ReasonObjectCache('profiles_by_date', 86400);
		if (!$refresh && $profiles = $cache->fetch()) return $profiles;
		
		// If we got here and the cache is locked, the cache doesn't exist but another 
		// process is already rebuilding it, so we're kind of out of luck.
		if ($cache->is_locked())
		{
			return array();
		}
		$cache->lock(200);

		$ds = new directory_service('ldap_carleton');
		$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
		$es->add_type(id_of('profile_type'));
		$es->orderby = 'entity.creation_date ASC';
		$count = 0;
		if ($results = $es->run_one())
		{
			foreach($results as $result)
			{
				if ($result->get_value('advance_id'))
					$person = new $this->config->person_class(sprintf('%010d',$result->get_value('advance_id')), 'carladvanceid');
				else
					$person = new $this->config->person_class(sprintf('%07d',$result->get_value('colleague_id')), 'carlcolleagueid');
				if ($person->is_valid())
				{
					if (!($advid = $person->get_advance_id()))
						error_log('No Advance ID for profile: '.$person->get_display_name());
					else
						$profiles[$person->get_first_ds_value('ds_affiliation')][$advid] = array(
							'date' => $result->get_value('creation_date'),
							'name' => $person->get_display_name(), 
							'firstname' => $person->get_first_ds_value('ds_firstname'), 
							'lastname' => $person->get_first_ds_value('ds_lastname'), 
							'year' => $person->get_first_ds_value('ds_classyear'),
							'netid' => $person->get_first_ds_value('ds_username'),
							'mail' => $person->get_first_ds_value('ds_email'),
							'major' => $person->get_majors(),
							);
					//	if (!$result->get_value('advance_id'))
					//		reason_update_entity( $result->id(), get_user_id('causal_agent'), array('advance_id' => $advid), false);
				}		
				$count++;
				//if ($count == 50) break;
			}
		}
		
		$cache->set($profiles);
		$cache->unlock();
		
		return $profiles;
	}
	
	/**
	  * Given a tag slug, return a populated tag array. If the slug isn't found, and the cache hasn't been
	  * refreshed in this session, rebuild the cache and try again.
	  *
	  * @param string $slug
	  */
	public function get_tag_by_slug($slug)
	{
		if (isset($this->slug_index[$slug]))
			return $this->get_tag_by_id($this->slug_index[$slug]);
		else
		{
			if (!$this->refreshed)
			{
				$this->load_all_caches(true);
				if (isset($this->slug_index[$slug]))
					return $this->get_tag_by_id($this->slug_index[$slug]);
			}
		}
		return false;
	}
	
	/**
	  * Given a tag id, return a populated tag array. If the id isn't found, and the cache hasn't been
	  * refreshed in this session, rebuild the cache and try again.
	  *
	  * @param int $id
	  */
	public function get_tag_by_id($id)
	{
		if (isset($this->tag_cache[$id]))
			return $this->tag_cache[$id];
		else
		{
			if (!$this->refreshed)
			{
				$this->load_all_caches(true);
				if (isset($this->tag_cache[$id]))
					return $this->tag_cache[$id];
			}
		}
		return false;
	}
	
	/**
	  * Given a tag slug, return an array of tags with similar names.
	  *
	  * @param string $slug
	  * @return array
	  */
	public function get_similar_tags($slug)
	{
		$similar = array();
		foreach ($this->slug_index as $test_slug => $id)
		{
			similar_text($slug, $test_slug, $percent);
			if ($percent > 50)
				$similar[$test_slug] = $percent;
		}
		
		arsort($similar);
		return $similar;
	}
	
	public function get_top_tags_for_relation($rel)
	{
		$top = array();
		if (isset($this->relation_index[$rel]))
		{
			foreach ($this->relation_index[$rel] as $slug => $tag)
			{
				if (isset($tag['profiles'][$rel]))
					$top[$tag['id']] = count($tag['profiles'][$rel]);
			}
		}
		arsort($top);
		return $top;
	}
	
	/**
	  * Given a tag id, return an array of tags set as children to that tag
	  *
	  * @param int $id
	  * @return array
	  */
	public function get_child_tags($id, $sort=true)
	{
		if (empty($this->tag_cache[$id]['children'])) return array();
		foreach($this->tag_cache[$id]['children'] as $child_id) 
			$children[$child_id] = $this->tag_cache[$child_id];
		if ($sort) uasort($children, array($this, 'name_sort'));
		return $children;
	}
	
	private function name_sort($a, $b)
	{
		if ($a['name'] == $b['name']) return 0;
		return ($a['name'] < $b['name']) ? -1 : 1;
	}
		
	/**
	 * Load all of the caches of tag data, rebuilding if requested.
	 *
	 * @param boolean $refresh_cache (optional)
	 */
	public function load_all_caches($refresh_cache = false)
	{
		$this->get_tag_cache($refresh_cache);
		$this->get_slug_index($refresh_cache);
		foreach ($this->config->tag_section_relationship_names as $rel)
			$this->get_tag_index_by_relation($rel, $refresh_cache);		
	}
	
	/**
	 * Save tag cache to disk.
	 */
	public function save_tag_cache()
	{
		$cache = new ReasonObjectCache('profile_tag_cache_by_id', $this->cache_life);
		$cache->set($this->tag_cache);
	}
	
	/**
	 * Add a new parent->child relationship to the tag cache. This is used when updating relationships
	 * to avoid having to rebuild the cache on every change.
	 *
	 * @param int $parent_id  ID of parent tag
	 * @param int $child_id  ID of child tag
	 * @param boolean $save  Whether to write the changed cache
	 * @param string $prefix  Optional prefix to add to relationship name in cache
	 *			  (so you can have different kinds of parent->child relationships)
	 */
	public function update_cache_relationship($parent_id, $child_id, $save = false, $prefix = '')
	{
		if (isset($this->tag_cache[$parent_id]) && isset($this->tag_cache[$child_id]))
		{
			$this->tag_cache[$parent_id][$prefix.'children'][$child_id] = $child_id;
			$this->tag_cache[$child_id][$prefix.'parents'][$parent_id] = $parent_id;
		
			if ($save) $this->save_tag_cache();
		}
	}
	
	/**
	 * Delete a new parent->child relationship from the tag cache. This is used when updating relationships
	 * to avoid having to rebuild the cache on every change.
	 *
	 * @param int $parent_id  ID of parent tag
	 * @param int $child_id  ID of child tag
	 * @param boolean $save  Whether to write the changed cache
	 * @param string $prefix  Optional prefix to add to relationship name in cache
	 *			  (so you can have different kinds of parent->child relationships)
	 */
	public function clear_cache_relationship($parent_id, $child_id, $save = false, $prefix = '')
	{
		if (isset($this->tag_cache[$parent_id]) && isset($this->tag_cache[$child_id]))
		{
			unset($this->tag_cache[$parent_id][$prefix.'children'][$child_id]);
			unset($this->tag_cache[$child_id][$prefix.'parents'][$parent_id]);
		
			if ($save) $this->save_tag_cache();
		}
	}

	/**
	 * Insert changes into the tag cache (quicker than rebuilding the whole thing from the database)
	 *
	 * @param array $changes  An array of changes, keyed on the tag id
	 * @param boolean $save  Whether to write the changed cache
	 *
	 * You can send things like (11111 => array('name' => 'New Name')) to just change the name of a tag
	 * or (11111 => null) to delete the tag from the cache.
	 */
	public function update_tag_cache($changes, $save = false)
	{
		foreach ($changes as $id => $values)
		{
			if ($values == null)
				unset($this->tag_cache[$id]);
			else if (empty($this->tag_cache[$id]))
				$this->tag_cache[$id] = $values;
			else
				$this->tag_cache[$id] = array_merge($this->tag_cache[$id], $values);
		}
		
		if ($save) $this->save_tag_cache();
	}

	/**
	 * The slug index is a simple array of (tag_slug => category_entity_id), which is cached to improve
	 * lookup performance. This method retrieves the cache from disk or rebuilds it as needed.
	 *
	 * @param boolean $rebuild
	 */
	public function get_slug_index($rebuild=false)
	{
		if (!$rebuild && $this->slug_index) return $this->slug_index;
			
		$cache = new ReasonObjectCache('profile_tag_slug_index', $this->cache_life);
		if (!$rebuild && $this->slug_index = $cache->fetch()) return $this->slug_index;
		
		// Cache needs rebuilding
		// If we got here and the cache is locked, the cache doesn't exist but another 
		// process is already rebuilding it, so we're kind of out of luck.
		if ($cache->is_locked())
		{
			return array();
		}
		$cache->lock(200);

		$this->slug_index = array();
		
		foreach ($this->tag_cache as $id => $tag)
		{
			if (!empty($tag['slug']))
				$this->slug_index[$tag['slug']] = $id;
			else
				trigger_error('No slug for tag id '.$id);
		}
		
		$cache->set($this->slug_index);
		$cache->unlock();
		return $this->slug_index;
	}

	/**
	 * The tag_index_by_relation is a cache of tags grouped by the Reason relationships they have
	 * with profiles, which is used to quickly build lists of connections.
	 *
	 * @param string $relation  Relationship name
	 * @param boolean $rebuild
	 * @return array of tags that have the given relationship
	 */
	public function get_tag_index_by_relation($relation, $rebuild=false)
	{
		if (!$rebuild && isset($this->relation_index[$relation])) return $this->relation_index[$relation];
			
		$cache = new ReasonObjectCache('profile_tag_index_'.$relation, $this->cache_life);
		
		if (!$rebuild && $this->relation_index[$relation] = $cache->fetch()) return $this->relation_index[$relation];
		
		// If we got here and the cache is locked, the cache doesn't exist but another 
		// process is already rebuilding it, so we're kind of out of luck.
		if ($cache->is_locked())
		{
			return array();
		}
		$cache->lock(200);

		$this->relation_index[$relation] = array();
		
		foreach ($this->tag_cache as $id => $tag)
		{
			if (isset($tag['profiles'][$relation]))
				$this->relation_index[$relation][$tag['slug']] = $tag;
		}

		$this->customize_relation_index($relation);
		
		$cache->set($this->relation_index[$relation]);
		$cache->unlock();
		
		return $this->relation_index[$relation];
	}

	/** This method allows you to add any local data to your relation_index while it is being generated.
	 *  Add data to the $this->relation_index var here and they will be saved to the cache.
	 */
	protected function customize_relation_index($relation)
	{

	}
	
	/**
	 * The tag cache is an index of all active tags keyed on entity id. It contains tag metadata
	 * and information about relationships between tags.
	 *
	 * @param boolean $rebuild
	 * @return array
	 */
	public function get_tag_cache($rebuild=false)
	{
		if (!$rebuild && $this->tag_cache) return $this->tag_cache;
		$cache = new ReasonObjectCache('profile_tag_cache_by_id', $this->cache_life);
		if (!$rebuild && $this->tag_cache = $cache->fetch()) return $this->tag_cache;
		
		// If we got here and the cache is locked, the cache doesn't exist but another 
		// process is already rebuilding it, so we're kind of out of luck.
		if ($cache->is_locked())
		{
			return array();
		}
			
		$cache->lock(200);
		$this->tag_cache = array();
		foreach ($this->config->tag_section_relationship_names as $relationship)
		{
			$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
			$es->enable_multivalue_results();
			$es->add_type(id_of('category_type'));
			$es->add_right_relationship_field( $relationship , 'entity' , 'id' , 'profile_id',  false );
			if ($results = $es->run_one())
			{
				foreach($results as $result)
				{
					if ($profiles = $result->get_value('profile_id'))
					{
						if (!is_array($profiles)) $profiles = array($profiles);
						$profile_count = count($profiles);
					}
					else
						$profile_count = 0;
												
					if (isset($this->tag_cache[$result->id()]['count']))
						$this->tag_cache[$result->id()]['count'] += $profile_count;
					else
					{
						$this->tag_cache[$result->id()]['id'] = $result->id();
						$this->tag_cache[$result->id()]['name'] = $result->get_value('name');
						$this->tag_cache[$result->id()]['slug'] = $result->get_value('slug');
						$this->tag_cache[$result->id()]['count'] = $profile_count;
						$this->tag_cache[$result->id()]['created'] = $result->get_value('creation_date');
					}
					if ($profiles)
					{
						foreach ($profiles as $profile)
							$this->tag_cache[$result->id()]['profiles'][$relationship][$profile] = $profile;
					}
				}
			}	
		}

		// Get parent/child relationships
		$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
		$es->enable_multivalue_results();
		$es->add_type(id_of('category_type'));
		$es->add_left_relationship_field( 'parent_category_to_category' , 'entity' , 'id' , 'child_id',  false );
		if ($results = $es->run_one())
		{
			foreach($results as $result)
			{
				if ($children = $result->get_value('child_id'))
				{
					if (!is_array($children)) $children = array($children);
					
					foreach ($children as $id)
					{
						if (isset($this->tag_cache[$id]))
						{
							$this->tag_cache[$result->id()]['children'][$id] = $id;
							$this->tag_cache[$id]['parents'][$result->id()] = $result->id();
						}
					}
				}
			}
		}

		// Run any local tag cache customizations
		$this->customize_tag_cache();
		
		$cache->set($this->tag_cache);
		$cache->unlock();
		$this->refreshed = true;
		
		return $this->tag_cache;
	}		

	/** This method allows you to add any local data to your tag cache while it is being generated.
	 *  Add data to the $this->tag_cache var here and they will be saved to the cache.
	 */
	protected function customize_tag_cache()
	{
		
	}
	
}
