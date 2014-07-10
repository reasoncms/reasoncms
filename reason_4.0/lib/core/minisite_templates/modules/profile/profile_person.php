<?php

include_once('reason_header.php');
include_once( CARL_UTIL_INC. 'dir_service/directory.php' );
reason_include_once('content_managers/image.php3');
reason_include_once('function_libraries/asset_functions.php' );
reason_include_once( 'minisite_templates/modules/profile/config.php' );

/**
  * Profile Person
  * This class defines an object that encapsulates the logic for displaying and updating
  * content from a profile entity.
  */
class profilePerson
{
	/**
	 * This value creates a namespace for custom field data stored on the profile. Other
	 * modules using profile entities in distinctive ways might change this to keep their
	 * data separate.
	 * @see expand_profile_custom_fields()
	 */
	public $context = 'profile';
	
	/**
	 * These vars are used internally.
	 */
	protected $config;
	protected $username;
	protected $ds_record;
	protected $profile;
	protected $profile_entity;
	protected $image_link;

	/**
	 * profilePerson is typically constructed by passing a username, but you can create one
	 * by passing any unique directory service value and corresponding key.
	 *
	 * @param string $key The directory service value to search for (defaults to username)
	 * @param string $val (Optional) The directory service attribute to search	 
	 */
	function __construct($key, $keytype = 'ds_username')
	{		
		$this->config = new ProfileConfig();
		if ($keytype == 'ds_username')
		{
			$this->username = $key;
		} else {
			if ($this->get_ds_record($keytype, $key))
				$this->username = $this->get_first_ds_value('ds_username');
		}

		if (empty($this->username)) $this->is_valid = false;
	}

	/**
	 * Is valid returns true in the following sitation:
	 *
	 * - A directory service record exists for the username provided in the constructor AND
	 * - The user has an affiliation which supports profiles (@see profileConfig::$affiliations_that_have_profiles).
	 */
	public function is_valid()
	{
		if (!isset($this->is_valid))
		{
			if($this->get_ds_record())
			{
				$affiliations = $this->get_ds_value('ds_affiliation');
				if (!empty($affiliations))
				{
					$this->is_valid = (count(array_intersect($this->config->affiliations_that_have_profiles, $affiliations)) > 0);
				}
			}
			if (!isset($this->is_valid)) $this->is_valid = FALSE;
		}
		return $this->is_valid;
	}

	/**
	 * @return boolean true / false whether or not the person is the requested affiliation
	 */
	public function is_affil($affil)
	{
		return ($affiliations = $this->get_ds_value('ds_affiliation')) ? (in_array($affil, $affiliations)) : FALSE;
	}
	
	/**
	 * Translate a profile section name into an allowable relationship name
	 *
	 * @param string $section Profile section
	 */
	public function get_tag_section_relationship_name($section)
	{
		return (isset($this->config->tag_section_relationship_names[$section])) ? $this->config->tag_section_relationship_names[$section] : NULL;
	}
	
	public function get_username()
	{
		return $this->username;
	}

	/**
	 * Get a full directory service record from a unique directory service key and value pair.
	 *
	 * @param string $key The directory service attribute to search
	 * @param string $val The directory service value to search for
	 * @return array
	 */
	public function get_ds_record($key = 'ds_username', $val = 'username')
	{
		if(!isset($this->ds_record))
		{
			$ds = new directory_service();
			if ($val == 'username') $val = $this->get_username();
			$ds->search_by_attribute($key, $val, $this->config->ds_fields);
			$this->ds_record = $ds->get_first_record();
		}
		return $this->ds_record;
	}
	
	/**
	 * Return all values for a given attribute from the most recent directory service query
	 *
	 * @param string $attribute Directory service attribute name
	 */
	public function get_ds_value($attribute)
	{
		$r = $this->get_ds_record();
		if(isset($r[$attribute]))
			return $r[$attribute];
		return NULL;
	}
	
	/**
	 * Return the first value for a given attribute from the most recent directory service query
	 *
	 * @param string $attribute Directory service attribute name
	 */
	public function get_first_ds_value($attribute)
	{
		$v = $this->get_ds_value($attribute);
		if(!empty($v))
		{
			reset($v);
			return current($v);
		}
		return NULL;
	}

	/**
	 * @return boolean true / false whether or not a profile entity exists
	 */
	public function has_profile()
	{
		$entity = $this->get_profile_entity();
		return (!empty($entity));
	}
	
	/**
	 * @return boolean true / false whether or not the profile requires authentication to view
	 */
	public function requires_authentication()
	{
		return ($affiliation = $this->get_first_ds_value('ds_affiliation')) ? in_array($affiliation, $this->config->affiliations_that_require_authentication) : FALSE;
	}

	/**
	 * @return int profile entity id
	 */
	public function get_profile_id()
	{
		return ($profile = $this->get_profile_entity()) ? $profile->id() : NULL;
	}
	
	/**
	 * Returns profile for a person - returns an empty array if nothing is available.
	 *
	 * @param boolean $refresh Force a new pull of entity and relationship info.
	 * @return mixed profile values or boolean FALSE if nothing found
	 */
	public function get_profile( $refresh = false )
	{
		if ( !isset($this->profile) || $refresh )
		{
			$profile_entity = $this->get_profile_entity( $refresh );
			if (is_object($profile_entity))
			{
				$this->profile = $profile_entity->get_values();
				$this->expand_profile_custom_fields();
			}
			else 
			{
				$this->get_profile_default_data();
			}
		}
		return $this->profile;
	}
	
	/**
	 * The profile extra_fields value can contain a JSON representation of additional
	 * profile data. This method expands that into the profile so that it looks like real profile fields.
	 */
	public function expand_profile_custom_fields()
	{
		if (!empty($this->profile['extra_fields']))
		{
			$contexts = json_decode($this->profile['extra_fields'], true);
			if (is_array($contexts))
			{
				foreach($contexts as $context => $fields)
				{
					foreach($fields as $field => $data)
					{
						if (!isset($this->profile[$field]))
							$this->profile[$field] = $data;
					}
				}
			}
		}
	}
	
	/**
	 * Sets up a profile containing any default data that should be show if the
	 * user hasn't created a profile yet (e.g. educational history for faculty)
	 *
	 * @return mixed profile values or boolean FALSE if nothing found
	 */
	public function get_profile_default_data()
	{
		$this->profile = FALSE;
		
		// You can do something else here.
			
		return $this->profile;
	}
	
	/**
	 * Gets the profile entity for the person.
	 *
	 * @param boolean $refresh Whether to reload the data from the database
	 */
	protected function get_profile_entity( $refresh = false )
	{
		if (!isset($this->profile_entity) || $refresh)
		{
			/* Carleton-specific for testing */
			if ($adv_id = $this->get_first_ds_value('carladvanceid'))
			{
				$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
				$es->add_type(id_of('profile_type'));
				$es->add_relation('advance_id = ' . $adv_id);
				if ($result = $es->run_one())
				{
					$this->profile_entity = reset($result);
				}
				else $this->profile_entity = NULL;
			}
			else if ($guid = $this->get_first_ds_value('ds_guid'))
			{
				$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
				$es->add_type(id_of('profile_type'));
				$es->add_relation('user_guid = ' . $col_id);
				if ($result = $es->run_one())
				{
					$this->profile_entity = reset($result);
				}
				else $this->profile_entity = NULL;
			}
			else // lookup failed - we didn't get a valid guid
			{
				trigger_error('Could not get a valid guid for user ' . $this->get_username() . ' - profile entity not retrieved.');
				$this->profile_entity = FALSE;
			}
		}
		return $this->profile_entity;
	}
	
	/**
	 * Create an empty profile for the user.
	 */
	protected function create_profile_entity_if_needed()
	{
		$profile = $this->get_profile_entity();
		if (is_null($profile))
		{
			$name = $this->construct_profile_name();
			reason_create_entity( id_of($this->config->profiles_site_unique_name), id_of('profile_type'), get_user_id('causal_agent'), $this->construct_profile_name(), array('new' => 0, 'user_guid' => $this->get_first_ds_value('ds_guid')) );
			
			// If we have any default values (like faculty educational history) prepopulate the entity
			if ($defaults = $this->get_profile_default_data())
			{
				foreach ($defaults as $section => $data)
				{
					$this->update_profile_entity_field($section, $data, false);
				}
			}
			$this->get_profile( true );
		}
	}
	
	/**
	 * Constructs a name for our profile (internal representation).
	 *
	 * Nathan White '11 (nwhite)
	 */
	protected function construct_profile_name()
	{
		$name = $this->get_first_ds_value('ds_fullname');
		$class_year = $this->get_first_ds_value('ds_classyear');
		$username = '(' . $this->get_username() . ')';
		if (!empty($class_year)) $name .= ' ' . $class_year;	
		return $name . ' ' . $username;
	}
	
	/**
	 * Constructs a public display name.
	 *
	 * @param boolean $show_class_year Whether to include the class year in the display name
	 */
	public function get_display_name($show_class_year = true)
	{
		$name = $this->get_first_ds_value('ds_fullname');
		if ($show_class_year && ($class = $this->get_first_ds_value('ds_classyear')) && is_numeric($class))
			$name .= ' ’'.substr($class,-2);
		return $name;
	}
	
	/**
	 * Runs reason_update_entity for a profile field - creates the profile if it doesn't exist.
	 *
	 * - We are NOT archiving on every update right now - should we?
	 * - I don't think we should create reason user entities for everyone.
	 */
	public function update_profile_entity_field($field, $value, $create = true, $refresh = false)
	{
		if ($create) $this->create_profile_entity_if_needed();
		if ($profile_entity = $this->get_profile_entity())
		{
			$eid = $profile_entity->id();
			$uid = $this->get_updater_user_id();
			if (!in_array($field, $profile_entity->get_characteristics()))
			{
				$value = $this->get_update_profile_entity_extra_field($profile_entity, $field, $value, $eid, $uid);
				$field = 'extra_fields';
			}
			reason_update_entity( $eid, $uid, array($field => $value), false);
			if ($refresh) $this->get_profile_entity(true);
		}
	}
	/**
	 * Insert a new or updated value into the JSON extra_fields blob
	 *
	 * @param object $profile Profile entity
	 * @param string $field Extra field name
	 * @param mixed $value Extra field value
	 * @return string Updated JSON blob
	 */
	protected function get_update_profile_entity_extra_field($profile, $field, $value)
	{
		if ($extra = $profile->get_value('extra_fields'))
			$data = json_decode($extra, true);
		else
			$data = array();
			
		$data[$this->context][$field] = $value;
		
		return json_encode($data);
	}
	
	/**
	 * Get the Reason id that should be used as the entity updater.
	 * We return the reason user id OF THE LOGGED IN USER (if it exists), 
	 * otherwise we are returning causal_agent reason id.
	 *
	 * @return int
	 */
	protected function get_updater_user_id()
	{
		if (!isset($this->updater_user_id))
		{
			$this->updater_user_id = (get_user_id(reason_check_authentication())) ? get_user_id(reason_check_authentication()) : get_user_id('causal_agent');
		}
		return $this->updater_user_id;
	}
	
	/**
	 * Return html for a specific profile field or false if it doesn't exist for the profile
	 *
	 * @param string $fieldname
	 * @return string html
	 */
	public function get_profile_field($fieldname)
	{
		$profile = $this->get_profile();
		$custom_method = 'get_profile_field_'.$fieldname;
		if (method_exists($this, $custom_method)) 
			return $this->$custom_method($profile);
		else
			return (isset($profile[$fieldname])) ? $profile[$fieldname] : false;
	}
	
	/**
	 * Grab and return the categories associate with this person across a relationship
	 */
	public function get_categories($section)
	{
		$categories = array();
		if ( ($profile_id = $this->get_profile_id()) && ($rel_name = $this->get_tag_section_relationship_name($section)) && ($rel_id = relationship_id_of($rel_name)))
		{
			$es = new entity_selector();
			$es->add_type(id_of('category_type'));
			$es->add_right_relationship($profile_id, $rel_id);
			if ($results = $es->run_one())
			{
				foreach($results as $result)
					$categories[$result->get_value('slug')] = $result->get_value('name');
			}
			ksort($categories);
		}
		return $categories;
	}
	
	/**
	 * Associate a new category with this person, creating it as needed
	 *
	 * @param slug URL-safe slug for category
	 * @param name Human-friendly category name
	 */
	public function set_category($slug, $name, $section)
	{
		if ($slug && $name && ($rel_name = $this->get_tag_section_relationship_name($section)) && ($rel_id = relationship_id_of($rel_name)))
		{
			$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
			$es->add_type(id_of('category_type'));
			$es->add_relation('slug = "' . mysql_real_escape_string($slug) .'"');
			if ($result = $es->run_one())
			{
				$category = reset($result);
				$category_id = $category->id();
			}	
			else
			{
				$category_id = reason_create_entity( id_of($this->config->profiles_site_unique_name), id_of('category_type'), get_user_id('causal_agent'), $name, array('new' => 0, 'slug' => $slug) );	
			}
			
			$this->create_profile_entity_if_needed();
			create_relationship( $this->get_profile_id(), $category_id, $rel_id);
			$this->update_last_modified();
		}		
	}

	/**
	 * Remove category from a person, optionally deleting it if no longer in use
	 *
	 * @param string $slug URL-safe slug for category
	 * @param string $section Profile section name we're editing
	 * @param boolean $delete_unused Whether to delete the category if no one is using it
	 *
	 * @todo implement deleting orphaned entities
	 */
	public function remove_category($slug, $section, $delete_unused = false)
	{
		if ($slug && ($rel_name = $this->get_tag_section_relationship_name($section)) && ($rel_id = relationship_id_of($rel_name)))
		{
			$es = new entity_selector(id_of($this->config->profiles_site_unique_name));
			$es->add_type(id_of('category_type'));
			$es->add_relation('slug = "' . mysql_real_escape_string($slug) .'"');
			if ($result = $es->run_one())
			{
				$category = reset($result);
				delete_relationships(array('type'=>$rel_id,'entity_b'=>$category->id(),'entity_a'=>$this->profile_entity->id()));
				$this->update_last_modified();
				if ($delete_unused)
				{
					reason_expunge_entity($category->id(), get_user_id('causal_agent'));
				}
			}	
		}		
	}

	/**
	 * Update the last modified date of the profile - this should be called when relationship changes occur.
	 *
	 * - "touches" the entity to make sure the last_modified date is up to date.
	 * - only will run once during a particular page execution such that we don't have to worry about accidently calling multiple times.
	 *
	 * @return boolean did it do a database update (or NULL) if there is no profile entity to update.
	 */
	protected function update_last_modified()
	{
		if ($entity = $this->get_profile_entity())
		{
			if (!isset($this->_has_updated_last_modified))
			{
				$this->update_profile_entity_field('last_modified', get_mysql_datetime(), false);
				$this->_has_updated_last_modified = true;
				return true;
			}
			return false;
		}
		return NULL;
	}

	/**
	 * Takes an array of arrays containing site info and makes sure they exist and are linked to the profile.
	 *
	 * We loop first through any sites associated with this user:
	 *
	 * - if name exists as a reason entity and in the sites array (and url changed), update the existing entity.
	 * - if name exists as a reason entity for the user but does not exists in the sites array, delete the entity.
	 * - We unset any items from $sites that were changed or deleted.
	 *
	 * Next we loop through the items in the sites array passed into this function:
	 *
	 * - Make sure profile exists.
	 * - Create external url entities.
	 * - Create profile_to_external_url relationship.
	 *
	 * Note we do not do sophisticated error checks here as we expect the disco form to have done that.
	 *
	 * @param array sites array with name => url pairs.
	 * @return array synced_sites - output from get_sites()
	 */
	public function sync_sites($sites, $delete_unused=true)
	{
		$made_changes = false;
		if ($site_entities = $this->get_sites_entities())
		{
			foreach ($site_entities as $id=>$site)
			{
				$name = $site->get_value('name');
				$url = $site->get_value('url');
				if (isset($sites[$name]))
				{
					if ($url != $sites[$name])
					{
						$uid = $this->get_updater_user_id();
						reason_update_entity( $id, $uid, array('url' => $sites[$name]), false );
						$made_changes = true;
					}
				}
				else
				{
					$uid = $this->get_updater_user_id();
					reason_expunge_entity($id, $uid);
					$made_changes = true;
				}
				unset($sites[$name]);
			}
		}
		if (!empty($sites))
		{
			$this->create_profile_entity_if_needed();
			$profile_id = $this->get_profile_field('id');
			foreach ($sites as $name => $url) // create entities and rels.
			{
				$uid = $this->get_updater_user_id();
				$name_with_username = $name . ' ('.$this->get_username().')';
				$eid = reason_create_entity( id_of($this->config->profiles_site_unique_name), id_of('external_url'), $uid, $name_with_username, array('new' => 0, 'url' => $url) );
				create_relationship( $profile_id, $eid, relationship_id_of('profile_to_external_url') );
				$made_changes = true;
			}
		}
		if ($made_changes) $this->update_last_modified();
		return $this->get_sites( true );
	}
	
	/** 
	 * @return array of external url entity objects
	 */
	protected function get_sites_entities( $refresh = false )
	{
		if (!isset($this->sites_entities) || $refresh)
		{
			$this->sites_entities = NULL;
			if ($profile = $this->get_profile_entity())
			{
				$es = new entity_selector();
				$es->add_type(id_of('external_url'));
				$es->add_right_relationship($profile->id(), relationship_id_of('profile_to_external_url'));
				if ($result = $es->run_one())
				{
					$this->sites_entities = $result;
					foreach ($this->sites_entities as $entity)
					{
						$original_name = $entity->get_value('name');
						$username = ' ('.$this->get_username().')';
						$len = strlen($username);
						if (strcmp(substr($original_name, -$len, $len), $username) === 0)
						{
							$entity->set_value('name', substr($original_name, 0, -$len));
						}
					}
				}
			}
		}
		return $this->sites_entities;
	}
	
	/**
	 * Returns an array that contains site names and urls for the person. 
	 *
	 * We establish a defined order:
	 *
	 * - Personal Website
	 * - LinkedIn
	 * - Facebook
	 * - Twitter
	 * - Blog
	 *
	 * Others are then appended to the list in alphabetical order
	 * 
	 * @return mixed asset values or boolean FALSE
	 */
	public function get_sites( $refresh = false )
	{
		if (!isset($this->sites) || $refresh)
		{			
			if ($sites = $this->get_sites_entities())
			{
				$this->sites = array();
				// loop through and populate this->sites
				foreach ($sites as $s)
				{
					$sites2[$s->get_value('name')] = $s->get_value('url');
				}
				
				// lets do our custom order
				if (isset($sites2['Personal Website']))
				{
					$this->sites['Personal Website'] = $sites2['Personal Website'];
					unset ($sites2['Personal Website']);
				}
				if (isset($sites2['LinkedIn']))
				{
					$this->sites['LinkedIn'] = $sites2['LinkedIn'];
					unset ($sites2['LinkedIn']);
				}
				if (isset($sites2['Facebook']))
				{
					$this->sites['Facebook'] = $sites2['Facebook'];
					unset ($sites2['Facebook']);
				}
				if (isset($sites2['Twitter']))
				{
					$this->sites['Twitter'] = $sites2['Twitter'];
					unset ($sites2['Twitter']);
				}
				if (isset($sites2['Blog']))
				{
					$this->sites['Blog'] = $sites2['Blog'];
					unset ($sites2['Blog']);
				}
				if (!empty($sites2))
				{
					ksort($sites2);
					$this->sites = $this->sites + $sites2;
				}
			}
			else $this->sites = FALSE;
		}
		return $this->sites;
	}

	/**
	 * Set a new profile image based on the image object delivered from the form
	 * @param object Uploaded image value from Disco element
	 */
	public function set_image($image)
	{
		// Make sure we have an image to start with
		if( !empty($image->tmp_full_path) AND file_exists( $image->tmp_full_path ) )
		{
			// Create a new entity for the image
			$owner = get_user_id('causal_agent');
			$values['new'] = '0';
			$values['author'] = $this->get_first_ds_value('ds_fullname');
			$values['description'] = $this->construct_profile_name().' Profile Image';
			$values['no_share'] = '0';
			$values['keywords'] = $this->get_first_ds_value('ds_fullname').','.$this->username;
			if ($id = reason_create_entity( id_of($this->config->profiles_site_unique_name), id_of('image'),$owner, $this->construct_profile_name().' Profile Image', $values))
			{
				// The image content manager contains all the logic for processing
				// various image types and creating thumbnails, so we'll just 
				// instantiate one and make it do our bidding.
				$im = new ImageManager();
				$im->load_by_type( id_of('image'), $id, $owner );
				
				$im->handle_standard_image($id, $image);
				$im->handle_original_image($id, $image);		
					
				$im->create_default_thumbnail($id);
				
				// Pull the values generated in the content manager
				// and save them to the entity
				$values = array();
				foreach($im->get_element_names() as $element_name)
				{
					$values[ $element_name ] = $im->get_value($element_name);
				}
				reason_update_entity( $id, $owner, $values, false );

				$this->create_profile_entity_if_needed();
				
				// Remove any preexisting image associations
				if ($existing = $this->profile_entity->get_left_relationship('profile_to_image'))
				{
					foreach($existing as $old_image)
					{
						delete_relationships(array('entity_b'=>$old_image->id(),'entity_a'=>$this->profile_entity->id()));
					}
				}
				
				// Relate the new image to the profile
				create_relationship( $this->profile_entity->id(), $id, relationship_id_of('profile_to_image'));
				$this->update_last_modified();
				return true;
			}
			else
			{
				trigger_error('Failed to create image entity.');		
			}
		} 
		else 
		{
			trigger_error('No path to image: '.$image->tmp_full_path);
		}
		return false;
	}
	
	/**
	 * Get the link array for the person's image
	 */
	public function get_image( $refresh = false )
	{
		if (!isset($this->image_link) || $refresh)
		{
			if ($link = $this->get_image_url( $refresh ))
			{
				$this->image_link = array(
					'src' => $link,
					'link' => $link,
					'alt' => $this->get_first_ds_value('ds_fullname'),
					'width' => '200',
					'height' => '200',
				);
			}
		}
				
		return $this->image_link;
	}

	/**
	 * Construct the URL for the profile image
	 * If you want to get images from another source if they're not
	 * present as Reason images, you can add that in here.
	 */
	protected function get_image_url( $refresh = false )
	{
		$link = false;
		
		if ($profile = $this->get_profile_entity())
		{
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->add_right_relationship($profile->id(), relationship_id_of('profile_to_image'));
			if ($result = $es->run_one())
			{
				$image_entity = reset($result);
				$link = reason_get_image_url($image_entity->id());
			}
		}
		
		return $link;	
	}
	
	/** 
	 * @return object asset entity which contains the resume
	 */
	protected function get_resume_entity( $refresh = false )
	{
		if (!isset($this->resume_entity) || $refresh)
		{
			$this->resume_entity = NULL;
			if ($profile = $this->get_profile_entity())
			{
				$es = new entity_selector();
				$es->add_type(id_of('asset'));
				$es->add_right_relationship($profile->id(), relationship_id_of('profile_to_resume'));
				if ($result = $es->run_one())
				{
					$this->resume_entity = reset($result);
				}
			}
		}
		return $this->resume_entity;
	}
	
	public function set_resume( $file )
	{
		$this->create_profile_entity_if_needed();
		if ($resume = $this->get_resume())
		{
			$file['name'] = $this->get_standardized_resume_filename($file['name']);
			$asset_id = reason_update_asset($resume['id'], $this->get_updater_user_id(), $file);
		}
		else
		{
			$file['name'] = $this->get_standardized_resume_filename($file['name']);
			$asset_id = reason_create_asset(id_of($this->config->profiles_site_unique_name), $this->get_updater_user_id(), $file);
			create_relationship($this->get_profile_field('id'), $asset_id, relationship_id_of('profile_to_resume'));
		}
		$this->update_last_modified();
	}

	/**
	 * Lets standardize the filename that we use for an uploaded file.
	 *
	 * - username_resume for non-faculty
	 * - username_cv for faculty
	 */
	function get_standardized_resume_filename($uploaded_filename)
	{
		$extension = pathinfo($uploaded_filename, PATHINFO_EXTENSION);
		$extension = (!empty($extension)) ? '.'.$extension : '';
		if ($this->is_affil('faculty'))
		{
			return $this->get_username().'_cv'.$extension;
		}
		else return $this->get_username().'_resume'.$extension;
	}
	
	/**
	 * Should grab asset name, size, type, and possibly URL? Or asset entity id?.
	 * 
	 * @return mixed asset values or boolean FALSE
	 */
	public function get_resume( $refresh = false )
	{
		if (!isset($this->resume) || $refresh)
		{
			if ($resume = $this->get_resume_entity())
			{
				$this->resume = $resume->get_values();
			}
			else $this->resume = FALSE;
		}
		return $this->resume;
	}	
}
?>