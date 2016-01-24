<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__, '.php' ) ] = 'ProfileModule';

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/group_helper.php' );
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'minisite_templates/modules/profile/lib/profile_functions.php' );
reason_include_once( 'config/modules/profile/config.php' );

/**
 * Profile Module
 *
 * Presents editable profiles for individuals defined in directory services.
 *
 * We map out which profile sections are included for each audience. For those in multiple audiences, we take an additive approach.
 * 
 * @todo current inline editing does not use activation parameters or check if it is active and thus will not play well with layouts where multiple modules support inline editing.
 * @todo should we not use inline editing framework (editors are not typically site editors)
 * @todo add basic profile list capability to this module
 * @todo consider whether we can unify profile list and explore methods in this class
 * @todo can profile_list and profile_explore "views" be combined into a single profile "display?"
 * @todo can we make sections (ie basic info) more flexible so modules can be inserted?
 * @todo forms should move into lib - basic form editing handled in controller / models / view - not in this module.
 * @todo all HTML should be in views - move to MVC
 * 
 * @author Nathan White
 * @author Mark Heiman
 */ 
class ProfileModule extends DefaultMinisiteModule
{	
	public $cleanup_rules = array(
		'username' => array('function' => 'check_against_regexp', 'extra_args' => array('/^[a-z0-9_]*$/i')),
		'pose_as' => array('function' => 'check_against_regexp', 'extra_args' => array('/^[a-z0-9_]*$/i')),
		'connect' => array( 'function' => 'turn_into_int' ),
		'list' => array('function' => 'turn_into_int' ),
		'explore' => array('function' => 'turn_into_int' ),
		'contact' => array( 'function' => 'turn_into_string' ),
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		'term' => array( 'function' => 'turn_into_string' ),
		'tag' => array('function' => 'check_against_regexp', 'extra_args' => array('/^[a-z0-9_]*$/i')),
		'section' => array('function' => 'check_against_regexp', 'extra_args' => array('/^[a-z0-9_]*$/i')),
		'view' => array( 'function' => 'turn_into_string' ),
	);
	
	/** These are used to store various settings and lookup tables */
	protected $config;
	protected $person;
	protected $user_can_inline_edit;
	protected $affiliation_supports_section;
	protected $profile_sections_by_region;
	protected $affiliation_supports_section_editing;

	public function pre_request_cleanup_init()
	{
		$this->config = profile_get_config();
	}

	public function get_cleanup_rules()
	{
		$rules = parent::get_cleanup_rules();
		$rules['edit_section'] = array(
			'function' => 'check_against_array',
			'extra_args' => array_keys($this->config->section_defaults)
		);
		return $rules;
	}

	/**
	 * Init the profile module
	 *
	 * @todo Limit redirection to folks with profiles (and make redirection pay attention to pose_as)
	 * @todo Add a special message to login screen
	 */
	public function init( $args = array() )
	{
		$api = $this->get_api();
		// Handle autocompletion for tag entry
		if ($api && ($api->get_name() == 'standalone') && isset($this->request['term']))
		{
			if ($tags = $this->get_site_categories($this->request['edit_section'], $this->request['term']))
			{
				foreach ($tags as $tag) $names[] = utf8_encode($tag['name']);
				echo $_GET['callback'] . '(' .json_encode(array_values($names)). ');';
			}
			exit;
		}

		if ($this->config->make_current_page_a_link)
		{
			if ($pages =& $this->get_page_nav())
			{
				$pages->make_current_page_a_link();
			}
		}
		
		// Handle requests for generic usernames (me and editme)
		if(isset($this->request['username']) && ('me' == $this->request['username'] || 'editme' == $this->request['username']))
		{
			$username = reason_require_authentication();
			if ('me' == $this->request['username'])
			{
				$link = profile_construct_redirect(array('username' => $username));
			}
			else
			{
				$link = profile_construct_redirect(array('username' => $username, 'inline_editing_availability' => 'enable'));
			}		
			header('Location: '.$link);
			echo '<a href="'.htmlspecialchars($link, ENT_QUOTES).'">Your profile</a>';
			die();
		}
		
		if($p = $this->get_person())
		{
			if($this->should_show_404())
			{
				http_response_code(404);
			}
			elseif($this->temporarily_unavailable())
			{
				http_response_code(307);
			}
			else
			{
				if ($p->requires_authentication()) reason_require_authentication();

				$inline_edit = get_reason_inline_editing($this->page_id);
				$inline_edit->register_module($this, $this->user_can_inline_edit());
				
				if ($person_name = $p->get_first_ds_value('ds_fullname'))
				{
					$this->_add_crumb($person_name);
				}				
			}
		}
				
		// Verify that edit_section is acceptable; get the form used so we can add head items if needed
		if (!empty($this->request['edit_section']))
		{
			if ($this->user_is_currently_inline_editing( $this->request['edit_section'] ))
			{
				$form = $this->get_inline_editing_form( $this->request['edit_section'] );
				if (method_exists($form, 'custom_init')) call_user_func(array($form, 'custom_init'));
			}
		}
	
		if($head_items = $this->get_head_items())
		{
			$head_items->add_javascript(JQUERY_URL, true);
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/profiles/general.js');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/profiles/base.css');
			if ($this->get_view_mode() == 'connect')
			{
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/profiles/connector.js');
			}
			
			if ($this->get_view_mode() == 'connect' || $this->get_view_mode() == 'tag')
			{
				$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/profiles/connector.css');
			}
			
			if ($this->get_view_mode() == 'list')
			{
				$controller = $this->get_controller($this->config->list_controller);
				if (method_exists($controller, 'append_head_items'))
				{
					$controller->append_head_items($head_items);
				}
			}
			if ($this->get_view_mode() == 'explore')
			{
				$controller = $this->get_controller($this->config->explore_controller);
				if (method_exists($controller, 'append_head_items'))
				{
					$controller->append_head_items($head_items);
				}
			}
			
			// if we are viewing a profile AND have controllers on sections that define append_head_items($head_items) - add those head items
			if (!empty($p))
			{
				$profile_info_sections = $this->get_profile_info_sections($this->get_affiliations($this->config->primary_affiliation_for_section_ordering));
				foreach ($profile_info_sections as $section)
				{
					$this->append_controller_head_items($section, $head_items);
				}
			}
		}
	}

	/**
	 * Defaults to showing a 404 if no valid profile entity is available and the logged in user is not
	 * not the profile requested.
	 */
	protected function should_show_404()
	{
		$p = $this->get_person();
		if ( (reason_check_authentication() == $p->get_username()) || ($p->is_valid() && $p->has_profile()))
		{
			return false;
		}
		return true;
	}
	
	/**
	 * If user is logged in and user role isn't allowed to have a profile, return true.
	 */
	protected function not_authorized()
	{
		$p = $this->get_person();
		if ( (reason_check_authentication() == $p->get_username()) && !$p->is_valid()) return true;
		return false;
	}
	
	/**
	 * Check to see if we are disabled for a particular audience and returns true if so.
	 */
	protected function temporarily_unavailable()
	{
		$p = $this->get_person();
		$primary_affiliation = $p->get_first_ds_value('ds_affiliation');
		return (isset($this->config->audiences_temporarily_unavailable[$primary_affiliation]));
	}
		
	protected function get_person()
	{
		if(!isset($this->person))
		{
			$this->person = false;
			if(!empty($this->request['username']))
			{
				$this->person = new $this->config->person_class($this->request['username']);
			}
		}
		return $this->person;
	}

	/**
	  * Assign a person object to this profile
	  *
	  * @param object $person profilePerson
	  */
	public function set_person($person)
	{
		if (is_object($person)) $this->person = $person;
	}
	
	/**
	 * @return boolean true
	 */
	function has_content()
	{
		return true;
	}
	
	/**
	 * Run the profile module.
	 *
	 * @todo should have a wrapper so HTML logic in modes is not repeated.
	 */
	function run()
	{
		$p = $this->get_person();
		if (!$p)
		{
			if ($this->get_view_mode() == 'list')
			{
				// run basic profile list
				echo $this->get_profile_list_html();
			}
			elseif ($this->get_view_mode() == 'explore')
			{
				echo $this->get_profile_explore_html();
			}
			elseif ($this->config->redirect_to_profile_list_if_no_username)
			{
				if ($link = profile_construct_list_redirect())
				{
					header('Location: '.$link);
					exit();
				} 
			}
			else echo $this->get_welcome_html();
		}
		elseif ($this->should_show_404())
		{
			echo $this->get_not_found_html();
		}
		elseif ($this->not_authorized())
		{
			echo $this->get_not_authorized_html();
		}
		elseif ($this->temporarily_unavailable())
		{
			echo $this->get_temporarily_unavailable_html();
		}
		else
		{
			echo $this->get_profile_html();
		}
	}
	
	/**
	 * Show profile page entity content (if defined).
	 *
	 * By default, in config redirect_to_profile_list_if_no_username is true so you don't see this content.
	 *
	 */
	protected function get_welcome_html()
	{
		$str = '';
		$str .= '<div id="profilesModule" class="'.$this->get_api_class_string().'">'."\n";
		$str .= '<div id="mainProfileContent">'."\n";
		
		$str .= '<div id="profileInfo" class="section">' . "\n";
		if ($content = $this->cur_page->get_value('content'))
		{
			$str .= $content;
		}
		$str .= '</div>'."\n";
		
		$str .= '</div>'."\n";
		if ($this->should_show_secondary_profile_content())
		{
			$str .= '<div id="secondaryProfileContent" class="noActiveProfile">'."\n";
			$str .= $this->get_module_navigation();
			$str .= $this->get_sign_in_block();
			$str .= '</div>'."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}
	
	/**
	  * If the @see ProfileModule::$audiences_temporarily_unavailable class var is set, this
	  * method will be called when a profile from an unavailable audience is requested.
	  */
	protected function get_temporarily_unavailable_html()
	{
		$p = $this->get_person();
		$primary_affiliation = $p->get_first_ds_value('ds_affiliation');
		$str  = '<div id="profilesNotFound">'."\n";
		$str .= '<h2>Profile temporarily unavailable.</h2>'."\n";
		$str .= '<p>'.$this->config->audiences_temporarily_unavailable[$primary_affiliation].'</p>'."\n";
		$str .= '</div>'."\n";
		return $str;
	}
	
	protected function get_not_found_html()
	{
		$str = '';
		$str .= '<div id="profilesModule" class="'.$this->get_api_class_string().'">'."\n";
		$str .= '<div id="mainProfileContent">'."\n";
		$str .= '<div id="profileInfo" class="section">' . "\n";
		$str .= '<h2>Profile not found</h2>'."\n";
		$str .= '<p>The profile you have selected could not be found.</p>';
		$str .= '</div>'."\n";
		$str .= '</div>'."\n";
		$str .= '<div id="secondaryProfileContent" class="noActiveProfile">'."\n";
		$str .= $this->get_module_navigation();
		$str .= $this->get_sign_in_block();
		$str .= '</div>'."\n";
		if ($this->should_show_secondary_profile_content())
		{
			$str .= '<div id="secondaryProfileContent" class="noActiveProfile">'."\n";
			$str .= $this->get_module_navigation();
			$str .= $this->get_sign_in_block();
			$str .= '</div>'."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}
	
	/**
	 * For logged in users without permission to have a profile
	 */
	protected function get_not_authorized_html()
	{
		$str = '';
		$str .= '<div id="profilesModule" class="'.$this->get_api_class_string().'">'."\n";
		$str .= '<div id="mainProfileContent">'."\n";
		$str .= '<div id="profileInfo" class="section">' . "\n";
		$str .= '<h2>Profile not allowed</h2>'."\n";
		$str .= '<p>Your user type is not allowed to have a profile. Sorry!</p>';
		$str .= '</div>'."\n";
		$str .= '</div>'."\n";
		if ($this->should_show_secondary_profile_content())
		{
			$str .= '<div id="secondaryProfileContent" class="noActiveProfile">'."\n";
			$str .= $this->get_module_navigation();
			$str .= $this->get_sign_in_block();
			$str .= '</div>'."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}
	
	/**
	  * This is where the page content gets assembled.
	  */
	protected function get_profile_html()
	{
		$str = '';
		$str .= '<div id="profilesModule" class="'.$this->get_api_class_string().'">'."\n";
		$str .= '<div id="mainProfileContent" class="'.$this->person->get_first_ds_value('ds_affiliation').'Type">'."\n";
		$str .= '<div id="profileInfo" class="section">' . "\n";
		if ($this->get_view_mode() == 'connect')
		{
			$str .= $this->get_connections_html();
		}
		else 
		{
			$str .= $this->get_editing_notice();
			$str .= $this->get_profile_info();
		}
		$str .= '</div>'."\n";
		$str .= $this->get_last_updated_section();
		$str .= '</div>'."\n";
		if ($this->should_show_secondary_profile_content())
		{
			$str .= '<div id="secondaryProfileContent">'."\n";
			$str .= $this->get_module_navigation();
			$str .= $this->get_sign_in_block();
			$str .= '</div>'."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}
	
	/**
	 * Controllers always get the site_id and the request object
	 *
	 * @return controller object
	 */
	protected function get_controller($name)
	{
		static $controller;
		if ($name && !isset($controller[$name]))
		{
			if (!empty($name))
			{
				if (reason_file_exists('minisite_templates/modules/profile/lib/controllers/' . $name . '.php'))
				{
					reason_include_once('minisite_templates/modules/profile/lib/controllers/' . $name . '.php');
					$controller_object_name = $GLOBALS[ '_profiles_controller' ][ $name ];
					$controller[$name] = new $controller_object_name;
					$controller[$name]->config('site_id', $this->site_id);
					$controller[$name]->config('request', $this->request);
				}
				else
				{
					trigger_error('Controller `' . htmlspecialchars($name) . '` could not be found in the controllers directory.');
					return NULL;
				}
			}
		}
		return ($name) ? $controller[$name] : NULL;
	}
	
	/**
	 * Instantiate and run named controller.
	 *
	 * - apply controller specific configuration here if provided
	 *
	 * @return HTML from a named controller
	 */
	protected function get_controller_html($name, $config = NULL)
	{
		if ($controller = $this->get_controller($name))
		{
			if (!empty($config))
			{
				foreach ($config as $k=>$v)
				{
					$controller->config($k, $v);
				}
			}
			$output = $controller->run();
		}
		return (!empty($output)) ? $output : '';
	}
	
	/**
	 * This is where the page content gets assembled - if I'm loaded we have a view specified.
	 */
	protected function get_profile_list_html()
	{
		$str = '';
		$str .= '<div id="profilesModule" class="'.$this->get_api_class_string().'">'."\n";
		$str .= '<div id="mainProfileContent">'."\n";
		$str .= '<div id="profileList" class="section">' . "\n";
		
		// grab controller from config.
		$controller = $this->get_controller($this->config->list_controller);
		$str .= $controller->run();
		$str .= '</div>'."\n";
		$str .= '</div>'."\n";
		if ($this->should_show_secondary_profile_content())
		{
			$str .= '<div id="secondaryProfileContent" class="noActiveProfile">'."\n";
			$str .= $this->get_module_navigation();
			$str .= $this->get_sign_in_block();
			$str .= '</div>'."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}
	
	/**
	  * This is where the page content gets assembled - if I'm loaded we have a view specified.
	  */
	protected function get_profile_explore_html()
	{
		$str = '';
		$str .= '<div id="profilesModule" class="'.$this->get_api_class_string().'">'."\n";
		$str .= '<div id="mainProfileContent">'."\n";
		$str .= '<div id="profileExplore" class="section">' . "\n";
		
		// grab controller from config.
		$controller = $this->get_controller($this->config->explore_controller);
		$str .= $controller->run();
		$str .= '</div>'."\n";
		$str .= '</div>'."\n";
		if ($this->should_show_secondary_profile_content())
		{
			$str .= '<div id="secondaryProfileContent" class="noActiveProfile">'."\n";
			$str .= $this->get_module_navigation();
			$str .= $this->get_sign_in_block();
			$str .= '</div>'."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}

	protected function get_last_updated_section()
	{
		$person = $this->get_person();
		$pretty_last_mod = ($last_modified = $person->get_profile_field('last_modified')) ? prettify_mysql_datetime($last_modified, 'F j, Y') : false;
		if ($pretty_last_mod)
		{
			return '<div id="profileLastUpdated">Profile updated ' . $pretty_last_mod . '</div>';
		}
		return '<div id="profileLastUpdated">' . $person->get_first_ds_value('ds_fullname').' has not yet customized this profile.</div>';
	}
	
	protected function get_basic_info()
	{
		$str = '';
		$person = $this->get_person();
		$name = $person->get_display_name();
		$str .='<div id="basicInfo" class="section">'."\n";
		$str .= $this->get_edit_offer();
		$str .= $this->get_image_html();
		$str .= '<h2 class="name">'.htmlspecialchars($name).'</h2>';
		$str .= '</div>'."\n";
		return $str;
	}
	
	protected function get_image_html()
	{
		// if image is a profile section, get the profile photo
		if ( ($photo_html = $this->get_section_html('image')) || ( $this->user_is_currently_inline_editing() && $this->affiliation_supports_section('image') ) )
		{
			$str = '<div class="image' . $this->get_inline_editing_class_str('image') . '">'."\n";
			if ($this->user_is_currently_inline_editing('image'))
			{
				$str .= $this->get_inline_editing_form_html('image');
			}
			else
			{
				$str .= $photo_html;
			}
			$str .= $this->get_section_edit_offer('image');
			$str .= '</div>'."\n";
			return $str;
		}		
	}
	
	protected function get_edit_offer()
	{
		$str = '';
		if (($this->user_can_inline_edit() || $this->user_can_pose()) && $this->get_view_mode() == 'profile')
		{
			if($this->user_is_currently_inline_editing())
			{
				$str .= '<div class="editOffer done"><a href="'.carl_make_link(array('inline_editing_availability'=>'disable','edit_section'=>'','pose_as'=>'')).'" title="Stop editing this profile"><span class="icon"></span>Done Editing</a></div>'."\n";
			}
			else
			{
				$person = $this->get_person();
				$profile = $person->get_profile();
				$pose_as = ($person->get_username() != reason_check_authentication()) ? $person->get_username() : '';
				if(!empty($profile['id']))  
					$str .= '<div class="editOffer start"><a href="'.carl_make_link(array('inline_editing_availability'=>'enable', 'pose_as' => $pose_as)).'" title="Edit this profile"><span class="icon"></span>Start Editing</a></div>'."\n";
			}
		}
		return $str;
	}
	
	protected function get_section_edit_offer($section)
	{
		$str = '';
		if ($this->user_is_currently_inline_editing() && $this->affiliation_supports_section_editing($section))
		{
			if(!empty($this->request['edit_section']) && $this->request['edit_section'] == $section)
			{

				$str .= '<div class="editOffer cancel"><a href="'.carl_construct_link(array('edit_section'=>''), array('username', 'pose_as')).'" title="Cancel &amp; discard '.$this->get_section_label($section).' edits"><span class="icon"></span>Cancel</a></div>'."\n";
			}
			elseif(empty($this->request['edit_section']))
			{
				$str .= '<div class="editOffer start"><a href="'.carl_construct_link(array('edit_section'=>$section), array('username', 'pose_as')).'" title="Edit '.$this->get_section_label($section).'"><span class="icon"></span>'.$this->get_edit_language($section).'</a></div>'."\n";
			}
		}
		return $str;
	}
	
	function get_edit_language($section)
	{
		switch($section)
		{
			case 'image':
				return 'Replace Photo';
			case 'resume':
				return 'Upload';
			default:
				return 'Edit';
		}
	}
	
	/**
	 * Assemble the HTML for the contact info section of the profile page
	 */
	protected function get_contact_info()
	{
		$links_section_html = $this->get_links_section_html();
		$classes = array('section');
		$classes[] = ($links_section_html || $this->should_show_links_section()) ? 'withLinks' : 'noLinks';
		$contact_info_str  = '<div id="contactInfo" class="'.implode(' ', $classes).'">' . "\n";
		$contact_info_str .= $this->get_contacts_list_html();
		$contact_info_str .= $links_section_html;
		$contact_info_str .= '</div>';
		return $contact_info_str;
	}

	/**
	  * Generate the html list of links
	  *
	  * @param string $primary_affiliation
	  * @return string html
	  */
	protected function get_links_section_html()
	{		
		foreach ($this->get_profile_sections_by_region('links') as $section)
		{
			$content[$section] = $this->get_section_html($section);
		}
		
		$contact_info_str = '<div class="links">' . "\n";
		$contact_info_str .= '<ul>' . "\n";
		
		foreach ($this->get_profile_sections_by_region('links') as $section)
		{
			if ( isset($content[$section]) || ( $this->user_is_currently_inline_editing() && $this->affiliation_supports_section($section) ) )
			{
				$contact_info_str .= '<li class="'. $section . $this->get_inline_editing_class_str($section) .'">' . "\n";
				if ($this->user_is_currently_inline_editing($section))
				{
					$contact_info_str .= $this->get_section_edit_offer($section);
					$contact_info_str .= '<h3>'.$this->get_edit_language($section) . ' ' . $this->get_section_label($section) . '</h3>' ."\n";		
					$contact_info_str .= $this->get_inline_editing_form_html($section);
				}
				else
				{
					$contact_info_str .= $this->get_section_edit_offer($section);
					$contact_info_str .= $content[$section];
				}
				$contact_info_str .= '</li>' . "\n";
			}
		}
		
		$contact_info_str .= '</ul>';
		$contact_info_str .= '</div>';

		return $contact_info_str;
	}

	/**
	 * Should we show the secondary profile content section? Yes if any of the sections are enabled.
	 */
	protected function should_show_secondary_profile_content()
	{
		return ($this->should_show_sign_in_block() || $this->should_show_module_navigation());
	}
	
	/**
	 * Should we show the sign in section? Ask the config.
	*/
	protected function should_show_sign_in_block()
	{
		return ($this->config->show_sign_in_block);
	}

	/**
	 * Should we show the the module navigation section? Ask the config.
	 */
	protected function should_show_module_navigation()
	{
		return ($this->config->show_module_navigation);
	}
		
	/**
	 * Should we show the links section? Only if this affiliation supports it and we're editing.
	 */
	protected function should_show_links_section()
	{
		$supports_links = false;
		foreach ($this->get_profile_sections_by_region('links') as $section)
		{
			if ($this->affiliation_supports_section($section))
			{
				$supports_links = true;
			}
		}

		if ( $supports_links && $this->user_is_currently_inline_editing() )
			return true;
				
		return false;
	}
	
	/**
	  * Generate the html list of contacts
	  *
	  * @return string html
	  */
	protected function get_contacts_list_html()
	{
		$person = $this->get_person();
		$primary_affiliation = $person->get_first_ds_value('ds_affiliation');
		
		$contact_info_str = '<div class="contact">' . "\n";
		$contact_info_str .= '<ul>' . "\n";
		
		// You'll want to customize these with your own contact fields
		if ($loc_parts = $person->get_ds_value('carlofficelocation'))
		{
			foreach($loc_parts as $k => $loc)
			{
				$loc_parts[$k] = htmlspecialchars($loc);
			}
			$contact_info_str .= '<li class="office"><span class="icon"></span>Office: '.implode(', ',$loc_parts).'</li>' . "\n";
		}
		if ($phone_parts = $person->get_ds_value('telephonenumber'))
		{
			foreach($phone_parts as $k=>$phone)
			{
				$phone_parts[$k] = htmlspecialchars($this->format_phone_number($phone));
			}
			$contact_info_str .= '<li class="phone"><span class="icon"></span>Phone: '.implode(', ',$phone_parts).'</li>' . "\n";
		}
		if ($email_parts = $person->get_ds_value('ds_email'))
		{
			foreach($email_parts as $k=>$email)
			{
				$email_parts[$k] = '<a href="mailto:'.urlencode($email).'" title="Send email to this person">'.htmlspecialchars($email).'</a>';
			}
			$contact_info_str .= '<li class="email"><span class="icon"></span>Email: '.implode(', ',$email_parts).'</li>' . "\n";
		}
		$contact_info_str .= '</ul>' . "\n";
		$contact_info_str .= '</div>' . "\n";
		return $contact_info_str;
	}
	
	protected function get_connector_tabs()
	{
		if ($this->user_is_currently_inline_editing()) return null;
		
		$str = '<ul id="connectTabs">'."\n";
		$str .= '<li id="profileTab" '.(($this->get_view_mode() != 'profile') ? 'class="disabled"' :'').'>';
		if ($this->get_view_mode() != 'profile')
		{
			$str .= '<a href="'.profile_construct_link(array('username' => $this->request['username'])).'">Profile</a></li>'."\n";
		}
		else
			$str .= 'Profile</li>'."\n";
		$str .= '<li id="connectTab" '.(($this->get_view_mode() == 'profile') ? 'class="disabled"' :'').'>';
		if ($this->get_view_mode() == 'profile')
		{
			$str .= '<a href="'.profile_construct_link(array('username' => $this->request['username'], 'connect' => 1)).'">Connections</a></li>'."\n";
		}
		else
			$str .= 'Connections</li>'."\n";
		$str .= '</ul>'."\n";
		return $str;
	}
	
	/**
	 * Customizable text shown when the user is editing their profile
	 */
	protected function get_editing_notice()
	{
		$notice = '';
		if ($this->user_can_inline_edit() && $this->user_is_currently_inline_editing())
		{
			$notice = '<div id="editingNotice">Link to Usage Guidelines, etc.</div>'."\n";
		}
		return $notice;
	}
	
	/**
	 * Show the main profile sections.
	 *
	 * We show each supported section in the profile_info_sections array if it has content OR the user is inline editing.
	 */
	protected function get_profile_info()
	{
		$person = $this->get_person();
		$profile_info_sections = $this->get_profile_info_sections($this->get_affiliations($this->config->primary_affiliation_for_section_ordering));
		$profile = $person->get_profile();
		$profile_info_str = '';
		foreach ($profile_info_sections as $section)
		{
			$section_pre_html = $this->get_section_pre_html($section);
			$section_post_html = $this->get_section_post_html($section);
			$inline_editing_active = $this->user_is_currently_inline_editing();
			$affiliation_supports_section = $this->affiliation_supports_section($section);
			
			/** do not double get section_html when editing is active on the section **/
			// if (!($inline_editing_active && $affiliation_supports_section))
			if (!$this->user_is_currently_inline_editing($section))
			{
				$section_html = $this->get_section_html($section);
			}
			
			if ( !empty($section_pre_html) || !empty($section_post_html) || !empty($section_html) || ($inline_editing_active && $affiliation_supports_section) )
			{
				if (!empty($section_pre_html))
				{
					$profile_info_str .= $section_pre_html;
				}
				if (!empty($section_html) || $inline_editing_active)
				{
					$truncatable = $this->section_is_truncatable($section) ? " truncatable" : '';
					$profile_info_str .= '<div class="'.$this->camelcase($section).' subsection'.$this->get_inline_editing_class_str($section).$truncatable.'">' . "\n";
					$profile_info_str .= $this->get_section_edit_offer($section);
					if ($this->should_show_section_label($section, $inline_editing_active))
					{
						$profile_info_str .= '<h3>' . $this->get_section_label($section) . '</h3>' ."\n";
					}
					$profile_info_str .= '<div class="textZone">' . "\n";
					if ($inline_editing_active && $this->user_is_currently_inline_editing($section))
					{
						$form_html = $this->get_inline_editing_form_html($section);
						if ($form_html)
						{
							$profile_info_str .= $form_html;
						}
						else
						{
							$profile_info_str .= '<p>Sorry. This section is not currently editable</p>';
						}
					}
					else
					{
						$profile_info_str .= $section_html ."\n";
					}
					$profile_info_str .= '</div>' . "\n";
					$profile_info_str .= '</div>' . "\n";
				}
				if ($section_post_html)
				{
					$profile_info_str .= $section_post_html;
				}
			}
		}
		// Check for lack of profile entity.
		if (empty($profile['id']))
		{
			$setup_link = '<a href="'.carl_make_link(array('inline_editing_availability'=>'enable')).'" title="Customize your profile"><span class="icon"></span>Customize your profile</a>';
			$no_profile = '';
			if ($this->user_can_inline_edit() && !$this->user_is_currently_inline_editing())
			{
				$no_profile .= '<p class="profileSetupOffer">'.$setup_link.'</p>'."\n";
			}
			$profile_info_str = $no_profile . $profile_info_str;
		}

		return $profile_info_str;
	}

	/**
	 * Show a tags section. The current edit_section should correspond to a form.
	 *
	 * We show content here if it exists OR if we are editing and the person has an affiliation which supports this section.
	 */
	protected function get_tags_html($section)
	{
		$person = $this->get_person();
		$tags_str = '';
		if ($person && ($interest_tags = $person->get_categories($section)))
		{	
			$tags_str .= '<ul class="tagList">' . "\n";
			foreach ($interest_tags as $slug => $tag)
			{
				if ($section == 'classes_tags')
					$tags_str .= '<li class="disabled">'.htmlspecialchars($tag).'</li>';
				else
				{
					$tags_str .= '<li><a class="interestTag" href="'.profile_construct_explore_link(array('tag'=>htmlspecialchars($slug),'section'=>htmlspecialchars($section))).'" title="Find others with this interest">'.htmlspecialchars($tag).'</a></li>' ."\n";
				}
			}
			$tags_str .= '</ul>' . "\n";
		}
		return $tags_str;
	}
		
	/**
	 * Populate the navigation region of the page
	 * 
	 * The contents of this are defined in config.php, and can be comprised of two elements:
	 *
	 * - html suitable for inclusion within a list item
	 * - calls to class methods or profile functions that return html suitable for inclusion within a list item (or empty)
	 *
	 * The default settings shows "Profile list", "Pose as user" if on a user profile with privs
	 */
	protected function get_module_navigation()
	{
		if (!$this->should_show_module_navigation())
		{
			return '';
		}
		$items = array();
		foreach ($this->config->navigation_items as $k => $v)
		{
			if (!is_int($k) && method_exists($this, $k))
			{
				$item = ($v != NULL) ? call_user_func_array(array($this, $k), $v) : call_user_func(array($this, $k));
				if ($item) $items[] = $item;
			}
			elseif (!is_int($k) && function_exists($k))
			{
				$item = ($v != NULL) ? call_user_func_array($k, $v) : call_user_func($k);
				if ($item) $items[] = $item;
			}
			else $items[] = $v;
		}
		if (!empty($items))
		{
			$str = '<div id="moduleNavigation" class="section">';
			$str .= '<ul><li>' . implode('</li><li>', $items) . '</li></ul>';
			$str .= '</div>';
			return $str;
		}
	}	
	
	/**
	 * Return a link to "my" profile
	 */
	protected function get_my_profile_link($always_show = FALSE)
	{
		$str = '<a href="' . profile_construct_link(array('username' => 'me')) .'">My Profile</a>';	
		return $str;
	}
	
	/**
	 * Populate the sign in region of the page
	 */
	protected function get_sign_in_block()
	{
		if ($this->should_show_sign_in_block())
		{
			$str = '<div id="signIn" class="section">';
			if(reason_check_authentication())
				$str .= '<a href="/login/?logout=1" class="out">Sign Out</a>';
			else
				$str .= '<a href="/login/" class="in">Sign In</a>';
			$str .= '</div>'."\n";
			return $str;
		}
		else return '';
	}
	
	/**
	 * If the current user's profile is being displayed, we allow it to be edited.
	 *
	 * @return boolean is the active or posed user profile being displayed?
	 */
	function user_can_inline_edit()
	{
		if (!isset($this->user_can_inline_edit))
		{
			$this->user_can_inline_edit = false;
			if ($cur_user = $this->get_user_netid())
			{
				if( ($p = $this->get_person()) && $p->is_valid())
				{
					if($p->get_first_ds_value('ds_username') == $cur_user)
					{
						$this->user_can_inline_edit = true;
					}
				}
			}
		}
		return $this->user_can_inline_edit;
	}

	/**
	 * Does the person have an affiliation that supports showing a section?
	 *
	 * @param string $section Profile section name
	 */
	function affiliation_supports_section( $section )
	{
		if (!isset($this->affiliation_supports_section[$section]))
		{
			$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
			$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
			foreach ($affiliations as $affiliation)
			{
				if (isset($sections_by_affiliation[$affiliation][$section]))
				{
					$this->affiliation_supports_section[$section] = true;
				}
			}
			if (!isset($this->affiliation_supports_section[$section]))
			{
				$this->affiliation_supports_section[$section] = false;
			}
		}
		return $this->affiliation_supports_section[$section];
	}

	/**
	 * Does the person have an affiliation that supports editing a section?
	 *
	 * @param string $section Profile section name
	 */
	function affiliation_supports_section_editing( $section )
	{
		if (!isset($this->affiliation_supports_section_editing[$section]))
		{
			if (!$this->affiliation_supports_section( $section )) return false;

			$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
			$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
			foreach ($affiliations as $affiliation)
			{
				if (isset($sections_by_affiliation[$affiliation][$section]))
				{
					if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['readonly']))
					{
						$this->affiliation_supports_section_editing[$section] = !$sections_by_affiliation[$affiliation][$section]['readonly'];
					}
					elseif (isset($this->config->section_defaults[$section]['readonly']))
					{
						$this->affiliation_supports_section_editing[$section] = !$this->config->section_defaults[$section]['readonly'];
					}
					else
					{
						$this->affiliation_supports_section_editing[$section] = true; // default is true
					}
					break;
						
					// If we got permission, we can stop looking
					if ($this->affiliation_supports_section_editing[$section] == true) break;	
				}
			}
		}
		return $this->affiliation_supports_section_editing[$section];		
	}
	
	/**
	 * With no parameters, tells us whether inline editing is available and flipped on.
	 * When given a section, tells us whether or not that section is currently being edited.
	 *
	 * @param string $section Optional profile section name
	 */
	function user_is_currently_inline_editing( $section = NULL )
	{
		$inline_edit = get_reason_inline_editing($this->page_id);
		$editing_available = $inline_edit->available_for_module($this);
		return (!is_null($section)) ? 
			   ($editing_available && !empty($this->request['edit_section']) && ($this->request['edit_section'] == $section)) : 
			   $editing_available;
	}
	
	/**
	 * The profiles module supports four view modes:
	 * - list: for viewing list of profiles
	 * - profile: for viewing an individual profile
	 * - connect: for viewing the connections to an individual profile
	 * - tag: for viewing the connections for a particular tag.
	 *
	 * @todo is tag implemented right now?
	 */
	function get_view_mode()
	{
		if (!empty($this->request['connect']))
			return 'connect';
		else if (!empty($this->request['list']))
			return 'list';
		else if (!empty($this->request['explore']))
			return 'explore';
		elseif (!empty($this->request['tag']))
			return 'tag';
		else
			return 'profile';
	}
	
	/**
	 * Get an inline editing form.
	 *
	 * We provide it with the section, person, and head items.
	 * Note that this must be called in init in order to properly pass the head items.
	 *
	 * @param string $section Section name
	 * @return object Form object
	 */
	private function get_inline_editing_form($section)
	{
		if (!isset($this->form[$section]))
		{
			// if the section has a controller that supports editing, we invoke that.
			$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
			$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
			foreach ($affiliations as $affiliation)
			{
				if (isset($sections_by_affiliation[$affiliation][$section]))
				{
					if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['controller']))
					{
						$controller = call_user_func_array(array($this, 'get_controller'), $sections_by_affiliation[$affiliation][$section]['controller']);
					}
					elseif (isset($this->config->section_defaults[$section]['controller']))
					{
						$controller = call_user_func_array(array($this, 'get_controller'), $this->config->section_defaults[$section]['controller']);
					}
					break;
				}
			}
			if (!empty($controller) && method_exists($controller, 'supports_editing') && $controller->supports_editing())
			{
				$controller->config('section', $section);
				$this->form[$section] = $controller;
			}
			else // fallback to profile forms
			{
				foreach ($affiliations as $affiliation)
				{
					if (isset($sections_by_affiliation[$affiliation][$section]))
					{
						if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['form_name']))
						{
							$form_name = $sections_by_affiliation[$affiliation][$section]['form_name'];
						}
						elseif (isset($this->config->section_defaults[$section]['form_name']))
						{
							$form_name = $this->config->section_defaults[$section]['form_name'];
						}
						break;
					}
				}
				
				// use form name is specified otherwise section name.
				if (!isset($form_name)) $form_name = $section;
				$custom_form_path = 'minisite_templates/modules/profile/forms/'.$form_name.'.php';
				$entity_field_form_path = 'minisite_templates/modules/profile/forms/entity_field.php';
				if (reason_file_exists($custom_form_path))
				{
					reason_include_once($custom_form_path);
					$classname = $this->camelcase($form_name) . 'ProfileEditForm';
					$form = new $classname;
					$form->set_section($section); //?
					$form->set_section_display_name($this->get_section_label($section)); //?
					$form->set_person($this->get_person());
					$form->set_head_items($this->get_head_items());
				
					if ($config = $this->get_section_config($section))
					{
						foreach($config as $k=>$v)
						{
							$form->$k = $v;
						}
					}
				}
				elseif (reason_file_exists($entity_field_form_path))
				{
					reason_include_once($entity_field_form_path);
					$classname = 'entityFieldProfileEditForm';
					$form = new $classname;
					$form->set_section($section);
					$form->set_section_display_name($this->get_section_label($section));
					$form->set_person($this->get_person());
					$form->set_head_items($this->get_head_items());
				}
				else
				{
					trigger_error('Could not find a custom form called ' . $section . '.php or the default form ' . $default_form . '.php in the profile forms directory');
					$form = false;
				}
				$this->form[$section] = $form;
			}
		}
		return $this->form[$section];
	}
	
	/**
	 * Run the editing form for a profile section and capture its HTML output
	 *
	 * @param string $section Profile section name
	 * @return string HTML for our form
	 */
	private function get_inline_editing_form_html($section)
	{
		if ($form = $this->get_inline_editing_form( $section ))
		{
			ob_start();
			$return = $form->run();
			if (!empty($return)) echo $return;
			$result = ob_get_contents();
			ob_end_clean();
			if (!empty($result)) 
			{
				// Prepend instructions if they are defined.
				$result = $this->get_section_instructions($section) . $result;
			}
			return (!empty($result)) ? $result : '';
		}
	}
	
	/**
	 * Returns an array of class names for the section, according to whether or not inline editing is available and/or active for a section.
	 *
	 * @param string section name
	 * @return array
	 */
	protected function get_inline_editing_class($section)
	{
		if ($this->user_is_currently_inline_editing($section))
		{
			return array('editing', 'editable');
		}
		elseif ($this->user_is_currently_inline_editing())
		{
			return array('editable');
		}
		else return array();
	}
	
	/**
	 * Returns string with class names for the section, according to whether or not inline editing is available and/or active for a section.
	 *
	 * @param string section name
	 * @param boolean prefix_with_space add space character if the string to be returned is non-empty
	 * @return string
	 */
	protected function get_inline_editing_class_str($section, $prefix_with_space = true)
	{
		$str = implode(' ', $this->get_inline_editing_class($section));
		if (!empty($str))
		{
			return ($prefix_with_space) ? ' ' . $str : $str;
		}
		return '';	
	}

	/**
	 * Get the real or assumed netid for the logged in user. Someone can pose
	 * as the profile-holder if:
	 *  1. They're an admin on the profile site
	 *  2. The profile is an alum, and the person is in the alumni_profile_editors_group
	 *
	 * @todo is #2 functional?
	 */
	function get_user_netid()
	{
		if (!isset($this->_user_netid))
		{
			if ($this->config->allow_posing && !empty($this->request['pose_as']))
			{
				$username = strtolower($this->request['pose_as']);
				$p = new $this->config->person_class($username);
				if ($p->get_ds_record()) 
				{
					if ($this->user_can_pose())
					{
						$this->_user_netid = $username;
					}
				}
			}
			if (!isset($this->_user_netid)) $this->_user_netid = reason_check_authentication();
		}
		return $this->_user_netid;
	}

	/**
	 * Return true if the logged in user has admin access to this site.
	 */
	function user_can_pose()
	{
		return ($this->config->allow_posing && reason_check_access_to_site($this->site_id));
	}
	
	protected function format_phone_number($phone)
	{
		$phone_parts = explode(' ', $phone);
		// Remove leading +1
		unset($phone_parts[0]);
		return implode(' ', $phone_parts);
	}
	
	/** 
	 * We show the label in most cases except in this case:
	 *
	 * - inline editing is not active
	 * - section has label_only_when_editing set to true
	 */
	public function should_show_section_label($section, $inline_editing_active)
	{
		if ($inline_editing_active) return true;
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['label_only_when_editing']))
				{
					$label_only_when_editing = $sections_by_affiliation[$affiliation][$section]['label_only_when_editing'];
				}
				elseif (isset($this->config->section_defaults[$section]['label_only_when_editing']))
				{
					$label_only_when_editing = $this->config->section_defaults[$section]['label_only_when_editing'];
				}
				return (isset($label_only_when_editing)) ? false : true;
			}
		}
	}
	
	/**
	 * We get the section label based upon the affiliations the person is a part of.
	 *
	 * - The lookup uses profile_sections_by_affiliation, which specifies labels or has an array with a 'label' or 'label_function' to get labels.
	 * - We try known affiliations in order.
	 * - If nothing is found, we just prettify the string.
	 */
	public function get_section_label($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_string($sections_by_affiliation[$affiliation][$section]))
				{
					return htmlspecialchars($sections_by_affiliation[$affiliation][$section]);
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['label']))
				{
					return $sections_by_affiliation[$affiliation][$section]['label'];
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['label_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['label_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['label_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['label']))
				{
					return $this->config->section_defaults[$section]['label'];
				}
				elseif (isset($this->config->section_defaults[$section]['label_function']))
				{
					if (method_exists($this, $this->config->section_defaults[$section]['label_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['label_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['label_function'] . ' does not exist in the profile module.');
				}
				break; // no custom label - just use the section
			}
		}
		return htmlspecialchars(prettify_string($section));
	}
	
	/**
	 * We get the section instructions based upon the affiliations the person is a part of.
	 *
	 * - The lookup uses profile_sections_by_affiliation, and looks for array with key 'instructions' or 'instructions_function' to get instructions.
	 * - We try known affiliations in order.
	 * - If nothing is found, we just prettify the string.
	 */
	public function get_section_instructions($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['instructions']))
				{
					return $sections_by_affiliation[$affiliation][$section]['instructions'];
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['instructions_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['instructions_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['instructions_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['instructions_function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['instructions']))
				{
					return $this->config->section_defaults[$section]['instructions'];
				}
				elseif (isset($this->config->section_defaults[$section]['instructions_function']))
				{
					if (method_exists($this, $this->config->section_defaults[$section]['instructions_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['instructions_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['instructions_function'] . ' does not exist in the profile module.');
				}
				break;
			}
		}
		return NULL;
	}
	
	/**
	 * We get the section config based upon the affiliations the person is a part of.
	 *
	 * - The lookup uses profile_sections_by_affiliation, and looks for array with key 'config' or 'config_function' to get config.
	 * - We try known affiliations in order.
	 * - If nothing is found, we just prettify the string.
	 */
	public function get_section_config($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['config']))
				{
					return $sections_by_affiliation[$affiliation][$section]['config'];
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['config_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['config_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['config_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['config_function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['config']))
				{
					return $this->config->section_defaults[$section]['config'];
				}
				elseif (isset($this->config->section_defaults[$section]['config_function']))
				{
					if (method_exists($this, $this->config->section_defaults[$section]['config_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['config_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['config_function'] . ' does not exist in the profile module.');
				}
				break;
			}
		}
		return NULL;
	}

	/** 
	 * @return boolean true / false - default is true
	 */
	public function section_is_truncatable($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['truncatable']))
				{
					return $sections_by_affiliation[$affiliation][$section]['truncatable'];
				}
				elseif (isset($this->config->section_defaults[$section]['truncatable']))
				{
					return $this->config->section_defaults[$section]['truncatable'];
				}
				break;
			}
		}
		return true;
	}
	
	/**
	 * We get the section html based upon the affiliations the person is a part of.
	 *
	 * - The lookup uses profile_sections_by_affiliation, and looks for array with key 'html' or 'html_function' to get the html.
	 * - We try known affiliations in order.
	 * - If nothing is found, we default to grabbing a profile field named for the section.
	 */
	protected function get_section_html($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['html']))
				{
					return $sections_by_affiliation[$affiliation][$section]['html'];
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['controller']))
				{
					return call_user_func_array(array($this, 'get_controller_html'), $sections_by_affiliation[$affiliation][$section]['controller']);
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['html_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['html_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['html_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['html_function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['html']))
				{
					return $this->config->section_defaults[$section]['html'];
				}
				elseif (isset($this->config->section_defaults[$section]['controller']))
				{
					return call_user_func_array(array($this, 'get_controller_html'), $this->config->section_defaults[$section]['controller']);
				}
				elseif (isset($this->config->section_defaults[$section]['html_function']))
				{
					if (method_exists($this, $this->config->section_defaults[$section]['html_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['html_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['html_function'] . ' does not exist in the profile module.');
				}
				break;
			}
		}
		return NULL;
	}
	
	/**
	 * Retrieve any head items a controller specified in config wants to provide.
	 */
	protected function append_controller_head_items($section, $head_items)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['controller']))
				{
					// run get_head_items method if it exists
					$controller = call_user_func_array(array($this, 'get_controller'), $this->config->section_defaults[$section]['controller']);
					if (method_exists($controller, 'append_head_items'))
					{
						$controller->append_head_items($head_items);
					}
				}
				elseif (isset($this->config->section_defaults[$section]['controller']))
				{
					$controller = call_user_func_array(array($this, 'get_controller'), $this->config->section_defaults[$section]['controller']);
					if (method_exists($controller, 'append_head_items'))
					{
						$controller->append_head_items($head_items);
					}
				}
				break;
			}
		}
		return NULL;
	}
	
	/**
	 * We get the section pre html based upon the affiliations the person is a part of.
	 *
	 * - The lookup uses profile_sections_by_affiliation, and looks for array with key 'pre_html' or 'pre_html_function' to get the html.
	 * - We try known affiliations in order.
	 * - If nothing is found, we default to grabbing a profile field named for the section.
	 */
	protected function get_section_pre_html($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['pre_html']))
				{
					return $sections_by_affiliation[$affiliation][$section]['pre_html'];
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['pre_html_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['pre_html_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['pre_html_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['pre_html_function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['pre_html']))
				{
					return $this->config->section_defaults[$section]['pre_html'];
				}
				elseif (isset($this->config->section_defaults[$section]['pre_html_function']))
				{
					if (method_exists($this, $this->config->section_defaults[$section]['pre_html_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['pre_html_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['pre_html_function'] . ' does not exist in the profile module.');
				}
				break;
			}
		}
		return NULL;
	}
	
	/**
	 * We get the section post html based upon the affiliations the person is a part of.
	 *
	 * - The lookup uses profile_sections_by_affiliation, and looks for array with key 'post_html' or 'post_html_function' to get the html.
	 * - We try known affiliations in order.
	 * - If nothing is found, we default to grabbing a profile field named for the section.
	 */
	protected function get_section_post_html($section)
	{
		$affiliations = $this->get_affiliations($this->config->primary_affiliation_for_section_ordering);
		$sections_by_affiliation = $this->get_profile_sections_by_affiliation();
		foreach ($affiliations as $affiliation)
		{
			if (isset($sections_by_affiliation[$affiliation][$section]))
			{
				if (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['post_html']))
				{
					return $sections_by_affiliation[$affiliation][$section]['post_html'];
				}
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['post_html_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['post_html_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['post_html_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['post_html_function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['post_html']))
				{
					return $this->config->section_defaults[$section]['post_html'];
				}
				elseif (isset($this->config->section_defaults[$section]['post_html_function']))
				{
					if (method_exists($this, $this->config->section_defaults[$section]['post_html_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['post_html_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['post_html_function'] . ' does not exist in the profile module.');
				}
				break;
			}
		}
		return NULL;
	}
	
	/**
	 * There are instances where we want a certain affiliation to be effectively made primary - this supports that.
	 *
	 * @param string affiliation_to_return_first - optional affiliation that should be returned as the first item in the return array
	 *
	 * @return array affiliations
	 */
	protected function get_affiliations( $affiliation_to_return_first = NULL )
	{
		$affiliation_to_return_first = (empty($affiliation_to_return_first)) ? 'default' : $affiliation_to_return_first;
		if (!isset($this->_affiliations[$affiliation_to_return_first]))
		{
			if ($person = $this->get_person())
			{
				$affiliations = $person->get_ds_value('ds_affiliation');
				if (!empty($affiliations) && $affiliation_to_return_first != 'default')
				{
					$flipped = array_flip($affiliations);
					if (isset($flipped[$affiliation_to_return_first]))
					{
						unset($flipped[$affiliation_to_return_first]);
						$affiliations = array_keys(array($affiliation_to_return_first => 0) + (array) $flipped);
					}
				}
			}
			$this->_affiliations[$affiliation_to_return_first] = $affiliations;
		}
		return $this->_affiliations[$affiliation_to_return_first];
	}
	
	protected function get_profile_field_html($section)
	{
		$person = $this->get_person();
		$profile = $person->get_profile();
		if ($config = $this->get_section_config($section))
		{
			if (isset($config['apply_nl2br']) && $config['apply_nl2br'])
			{
				return (isset($profile[$section]) && !carl_empty_html($profile[$section])) ? nl2br($profile[$section]) : '';
			}
		}
		return (isset($profile[$section]) && !carl_empty_html($profile[$section])) ? $profile[$section] : '';
	}
	
	/**
	 * Return the profile photo or a placeholder.
	 */
	protected function get_profile_photo_html($section = NULL)
	{
		$person = $this->get_person();
		$image = $person->get_image();
		if ($image)
		{
			return '<a href="'.htmlspecialchars($image['link']).'"><img src="'.htmlspecialchars($image['src']).'" width="200" height="200" alt="'.htmlspecialchars($image['alt']).'" /></a>'."\n";
		}
		else // use default 200px by 200px profile icon
		{
			return '<img src="'.REASON_HTTP_BASE_PATH.'modules/profiles/profile_icon.png" width="200" height="200" alt="Profile Photo Placeholder" />'."\n";
		}
	}
	
	/**

	 * Get the resume link.
	 */
	protected function get_resume_html($section)
	{
		$person = $this->get_person();
		$resume = $person->get_resume();
		if ($resume)
		{
			$asset = new entity($resume['id']);
			$path = reason_get_asset_filesystem_location($asset);
			$size = (@filesize($path) / 1024);
			return '<a href="'.reason_get_asset_url($asset).'"><span class="icon"></span>'.$this->get_section_label('resume'). ' <span class="size">('.format_bytes_as_human_readable( ($size * 1024) ).' .'. $resume['file_type'] .')</span></a>' . "\n";
		}
		elseif ($this->user_is_currently_inline_editing())
		{
			return '<span class="icon"></span>'.$this->get_section_label($section). ' (Not yet uploaded)'; 
		}
	}
	
	function get_placeholder_html($section = NULL)
	{
		$config = $this->get_section_config($section);
		return (!empty($config['html'])) ? $config['html'] : '';
	}
	
	function get_sites_html($section = NULL)
	{
		$person = $this->get_person();
		if ($sites = $person->get_sites())
		{
			$str = '<span class="icon"></span>';
			foreach ($sites as $name => $url)
			{
				$sites_html[] = '<a href="'.htmlspecialchars($url).'">'.htmlspecialchars($name).'</a>';
			}
			$str .= implode(" | ", $sites_html);
			return $str;
		}
		elseif ($this->user_is_currently_inline_editing() && $this->affiliation_supports_section_editing('sites'))
		{
			return '<span class="icon"></span>'.$this->get_section_label($section). ' (None added)';
		}
	}

	function get_single_site_html($section = NULL)
	{
		$person = $this->get_person();
		if ($sites = $person->get_sites())
		{
			$site_name = $this->get_section_label($section);
			if ($config = $this->get_section_config($section))
			{	
				if (isset($config['site_name'])) $site_name = $config['site_name'];
				if (isset($config['site_url_as_label']) && $config['site_url_as_label']) $site_label = $sites[$site_name];
			}
			if (isset($sites[$site_name]))
			{
				if (!isset($site_label)) $site_label = $site_name;
				$str = '<a href="'.htmlspecialchars($sites[$site_name]).'">'.$site_label.'</a>';
			}
			return $str;
		}
		elseif ($this->user_is_currently_inline_editing())
		{
			return '<span class="icon"></span>'.$this->get_section_label($section). ' (None added)';
		}
	}
	
	protected function get_tags_label_with_full_name()
	{
		$person = $this->get_person();
		return 'Interest Tags for '.htmlspecialchars($person->get_first_ds_value('ds_fullname'));
	}
	
	function get_connections_html()
	{
		$pc = new $this->config->connector_class();
		$person = $this->get_person();
		$sections = $pc->get_connections_for_user($person);
		
		if (!$sections)
		{
			$str = '<p>This profile doesn\'t share any tags with other profiles yet.</p>';
			if ($this->user_can_inline_edit())
				$str .= '<p>Try adding some more tags to your profile to discover shared connections.</p>';
			return $str;
		} else {
			$str = '<p>The people listed below share tags with '.htmlspecialchars($person->get_first_ds_value('ds_fullname')).'.</p>';
		}
		
		$str .= '<ul id="navSections">';
		foreach ($sections as $section => $tags)
			$str .= '<li id="tab'.$section.'"><a href="#'.$section.'">'.$pc->get_section_name($section, $person).'</a></li>';
		$str .= '</ul>';
		$str .= '<div id="tagInfo">';		
		foreach ($sections as $section => $tags)
		{
			$str .= '<div class="connectSection" id="section'.$section.'">';
			$str .= '<h3 class="sectionName"><a name="'.$section.'"></a>'.$pc->get_section_name($section, $person).'</h3>';
			$str .= '<ul class="tag">';

			foreach ($tags as $id => $tag_data)
			{
				$str .= '<li><h4 class="tagName">';
				$str .= '<a href="'.profile_construct_explore_link(array('tag' => htmlspecialchars($tag_data['slug']))).'">'.$tag_data['name'].'</a></h4>';
				if (!isset($tag_data['profiles'][$this->config->tag_section_relationship_names[$section]])) continue;
				$profiles = $pc->get_profiles_by_affiliation($tag_data['profiles'][$this->config->tag_section_relationship_names[$section]]);
				$profiles_by_affil = $pc->sort_profiles_by_user_affiliations($this->person, $profiles);
				
				$str .= '<ul class="affiliations">';
				foreach ($profiles_by_affil as $affil => $profiles)
				{
					$str .= '<li><span class="affiliation">'.$pc->affiliations[$affil].':</span>';
					$str .= '<ul class="profiles">';
					$this->shuffle_assoc($profiles);
					$count = 0;
					foreach ($profiles as $username => $profile)
					{
						if ( $count < 12)
							$str .= '<li>';
						else
							$str .= '<li class="overflow">';
						$str .= '<a href="'. profile_construct_link(array('username' => $username)) .'">'.$profile['display_name'].'</a></li>'."\n";
						$count++;
					}
					$str .= '</ul>';
				}
				$str .= '</ul>';
				$str .= '</li>';
			}
			$str .= '</ul>';
			$str .= '</div>';
			
		}
		$str .= '</div>';
		return $str;
	}
	
	/**
	 * Get the base profile section list - we do a couple things to prepare:
	 *
	 * - If a section isn't explicitly listed, add it with an empty configuration so it is available and inherits the base.
	 * - Add any dynamic sections.
	 *
	 */
	protected function get_profile_sections_by_affiliation()
	{
		static $profile_sections_by_affiliation;
		if (!isset($profile_sections_by_affiliation))
		{
			$profile_sections_by_affiliation = $this->config->profile_sections_by_affiliation;
			$affiliations_with_profiles = array_keys($this->config->affiliations_that_have_profiles);
			$section_defaults = array_keys($this->config->section_defaults);
			foreach ($affiliations_with_profiles as $affiliation)
			{
				if (!isset($profile_sections_by_affiliation[$affiliation]))
				{
					// we loop through and set each section to true for this affiliation
					foreach ($section_defaults as $section_name)
					{
						$profile_sections_by_affiliation[$affiliation][$section_name] = true;
					}
				}
			}
			$this->add_dynamic_profile_sections($profile_sections_by_affiliation);
		}
		return $profile_sections_by_affiliation;
	}
	
	/**
	 * Get the list of profile sections that can appear in the named region
	 *
	 * @param string $region Currently, 'image','links', or 'main'
	 */
	protected function get_profile_sections_by_region($region)
	{
		// Rebuild our stored list if necessary
		if (empty($this->profile_sections_by_region)) 
		{
			foreach ($this->config->section_defaults as $name => $config)
			{
				if (isset($config['region']))
					$this->profile_sections_by_region[$config['region']][] = $name;
				else
					$this->profile_sections_by_region['main'][] = $name;
			}
		}
		
		if (isset($this->profile_sections_by_region[$region]))
			return $this->profile_sections_by_region[$region];
		else
			return array();
	}
	
	/**
	  * Most profile sections are statically defined according to affiliation, but it's possible
	  * to have other sections that are defined by other user attributes. This method adds those
	  * to the passed section list as appropriate. Dynamic sections still need to be defined in 
	  * section_defaults.
	  *
	  * @todo possibly this should be a model or something?
	  */
	protected function add_dynamic_profile_sections($profile_sections_by_affiliation)
	{
		if ($this->person)
		{
			// Add new profile sections here:
			// $profile_sections_by_affiliation['new_section'] = true;
		}
		
		return $profile_sections_by_affiliation;
	}
	
	/**
	 * Return the list of profile info sections; either the default set, or if affiliations are passed,
	 * a set that merges the sections for the passed affiliations in some sort of reasonable order based on
	 * the orders in profile_sections_by_affiliation. There are probably more elaborate ways one could
	 * merge multiple affiliation orders, but this one works reasonably well.
	 */
	public function get_profile_info_sections($affiliations = array())
	{
		$profile_info_sections = $this->get_profile_sections_by_region('main');
		if (empty($affiliations))
			return $profile_info_sections;
		
		$primary = true;
		$sections = array();
		$sections_by_affil = $this->get_profile_sections_by_affiliation();
		
		foreach ($affiliations as $affiliation)
		{
			if (!empty($sections_by_affil[$affiliation]))
			{
				// For the first (and often only) affiliation, just set the list of sections
				// to the keys of the matching list in profile_sections_by_affiliation.
				if ($primary)
				{
					$sections = array_intersect(array_keys($sections_by_affil[$affiliation]), $profile_info_sections);
					$primary = false;
				}
				// If there are additional affiliations, merge in any new sections based on
				// the positions of sections held in common.
				else
				{
					$current_pos = 0;
					foreach ($sections_by_affil[$affiliation] as $section)
					{
						// If this section is already present, just increment our current
						// position in the sections array.
						if ($pos = array_search($section, $sections))
							$current_pos = $pos;
						// If the section is new, insert it at the current position
						else
							array_splice($sections, $current_pos, 0, $section);	
					}
				}
			}
		}
		if (empty($sections))
			return $profile_info_sections;
		else
			return $sections;
		
	}
	
	protected function camelcase($str)
	{
		return preg_replace('/_(.?)/e',"strtoupper('$1')",$str);
	}
	
	/**
	  * Shuffles an associative array, maintaining keys. Used to shuffle tags.
	  *
	  * @param array $array
	  */
	protected function shuffle_assoc(&$array) 
	{
		$keys = array_keys($array);
		shuffle($keys);
		foreach($keys as $key) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
	}
	
	/** 
	  * Returns an array of tags assigned to a particular edit section,
	  * optionally filtered by a string. Used to supply data for the tag autocompletion
	  * ajax script.
	  *
	  * @param string $edit_section Profile section name
	  * @param string $filter Optional filter string
	  * @return array
	  */
	protected function get_site_categories($edit_section='tags', $filter=null)
	{
		$tags = array();
		$rel = $this->config->tag_section_relationship_names[$edit_section];
		
		$pc = new $this->config->connector_class();
				
		if ( $cache_tags = $pc->get_tag_index_by_relation($rel) )
		{
			$tags = array_filter($cache_tags, create_function('$i', 'return (stripos($i["name"], "'.addslashes($filter).'") === 0);'));
		}
		ksort($tags);
		return $tags;
	}
}
?>