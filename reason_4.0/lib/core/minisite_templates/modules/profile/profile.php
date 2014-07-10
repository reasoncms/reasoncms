<?php
/**
 * @package reason_local
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
reason_include_once( 'minisite_templates/modules/profile/config.php' );

/**
 * Profile Module
 *
 * Presents editable profiles for individuals defined in directory services.
 *
 * We map out which profile sections are included for each audience. For those in multiple audiences, we take an additive approach.
 * 
 * @todo current inline editing does not use activation parameters or check if it is active and thus will not play well with layouts where multiple modules support inline editing.
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
		'contact' => array( 'function' => 'turn_into_string' ),
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		'term' => array( 'function' => 'turn_into_string' ),
		'tag' => array('function' => 'check_against_regexp', 'extra_args' => array('/^[a-z0-9_]*$/i')),
		'view' => array( 'function' => 'turn_into_string' ),
	);
	
	/** These are used to store various settings and lookup tables */
	protected $config;
	protected $person;
	protected $site_url;
	protected $user_can_inline_edit;
	protected $affiliation_supports_section;
	protected $profile_sections_by_region;
	protected $affiliation_supports_section_editing;

	public function pre_request_cleanup_init()
	{
		$this->config = new ProfileConfig();
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
		
		$this->site_url = reason_get_site_url(id_of($this->config->profiles_site_unique_name));
		
		// Handle requests for generic usernames (me and editme)
		if(isset($this->request['username']) && ('me' == $this->request['username'] || 'editme' == $this->request['username']))
		{
			$cur_username = reason_require_authentication();
			$link = $this->site_url . urlencode($cur_username) . '/';
			if('editme' == $this->request['username'])
				$link .= '?inline_editing_availability=enable';
						
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
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/profiles/general.js');

			if ($this->get_view_mode() == 'connect')
			{
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/profiles/connector.js');
			}
			
			if ($this->get_view_mode() == 'connect' || $this->get_view_mode() == 'tag')
			{
				$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/profiles/connector.css');
			}
		}
	}

	/**
	 * Defaults to showing a 404 if no valid profile entity is available. You can override
	 * to apply different logic.
	 */
	protected function should_show_404()
	{
		$p = $this->get_person();
		if ($p->is_valid())
		{
			if ($p->has_profile()) return false;
		}
		return true;
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
	 */
	function run()
	{
		$p = $this->get_person();
		if (!$p)
		{
			echo $this->get_welcome_html();
		}
		elseif ($this->should_show_404())
		{
			echo $this->get_not_found_html();
		}
		elseif ($this->temporarily_unavailable())
		{
			echo $this->get_temporarily_unavailable_html();
		}
		else
		{
			echo $this->get_profile_html();
		}
		echo $this->get_module_footer();
	}
	
	/**
	  * By default, show the content of the page the module is running on. Obviously, you can
	  * override this to do something else.
	  */
	protected function get_welcome_html()
	{
		$str = '';
		$str .= '<div id="profilesWelcome">'."\n";
		if ($content = $this->cur_page->get_value('content'))
		{
			$str .= $content;
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
		$str  = '<div id="profilesNotFound">'."\n";
		$str .= '<h2>Profile not found.</h2>'."\n";
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
		$str .= $this->get_basic_info();
		$str .= $this->get_contact_info();
		$str .= $this->get_connector_tabs();
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
		$str .= '<div id="secondaryProfileContent">'."\n";
		$str .= $this->get_module_navigation();
		$str .= $this->get_sign_in_block();
		$str .= '</div>'."\n";
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
		if ($this->user_can_inline_edit() && $this->get_view_mode() == 'profile')
		{
			if($this->user_is_currently_inline_editing())
			{
				$str .= '<div class="editOffer done"><a href="'.carl_make_link(array('inline_editing_availability'=>'disable','edit_section'=>'',)).'" title="Stop editing this profile"><span class="icon"></span>Done Editing</a></div>'."\n";
			}
			else
			{
				$person = $this->get_person();
				$profile = $person->get_profile();
				if(!empty($profile['id']))
					$str .= '<div class="editOffer start"><a href="'.carl_make_link(array('inline_editing_availability'=>'enable')).'" title="Edit this profile"><span class="icon"></span>Start Editing</a></div>'."\n";
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
				$str .= '<div class="editOffer cancel"><a href="'.carl_make_link(array('edit_section'=>'')).'" title="Cancel &amp; discard '.$this->get_section_label($section).' edits"><span class="icon"></span>Cancel</a></div>'."\n";
			}
			elseif(empty($this->request['edit_section']))
			{
				$str .= '<div class="editOffer start"><a href="'.carl_make_link(array('edit_section'=>$section)).'" title="Edit '.$this->get_section_label($section).'"><span class="icon"></span>'.$this->get_edit_language($section).'</a></div>'."\n";
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
			$str .= '<a href="/profiles/'.$this->request['username'].'/">Profile</a></li>'."\n";
		else
			$str .= 'Profile</li>'."\n";
		$str .= '<li id="connectTab" '.(($this->get_view_mode() == 'profile') ? 'class="disabled"' :'').'>';
		if ($this->get_view_mode() == 'profile')
			$str .= '<a href="/profiles/'.$this->request['username'].'/connect/">Connections</a></li>'."\n";
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
			if ( ($section_html = $this->get_section_html($section)) || ($this->user_is_currently_inline_editing() && $this->affiliation_supports_section($section)) )
			{
				$profile_info_str .= '<div class="'.$this->camelcase($section).' subsection'.$this->get_inline_editing_class_str($section).'">' . "\n";
				$profile_info_str .= $this->get_section_edit_offer($section);
				$profile_info_str .= '<h3>' . $this->get_section_label($section) . '</h3>' ."\n";
				$profile_info_str .= '<div class="textZone">' . "\n";
				if ($this->user_is_currently_inline_editing($section))
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
					$tags_str .= '<li><a class="interestTag" href="'.$this->site_url.$this->config->explore_slug.'/'.htmlspecialchars($slug).'" title="Find others with this interest">'.htmlspecialchars($tag).'</a></li>' ."\n";
			}
			$tags_str .= '</ul>' . "\n";
		}
		return $tags_str;
	}
		
	/**
	 * Populate the navigation region of the page
	 */
	protected function get_module_navigation()
	{
		$str = '';
		$str .= '<div id="moduleNavigation" class="section">';
		// You can put something here.
		$str .= '</div>';
		return $str;
	}
	
	/**
	 * Populate the sign in region of the page
	 */
	protected function get_sign_in_block()
	{
		$str = '<div id="signIn" class="section">';
		if(reason_check_authentication())
			$str .= '<a href="/login/?logout=1" class="out">Sign Out</a>';
		else
			$str .= '<a href="/login/" class="in">Sign In</a>';
		$str .= '</div>'."\n";
		return $str;
	}

	/**
	 * Display our footer text with links to about, sign in and edit, edit, etc as appropriate.
	 */
	protected function get_module_footer()
	{
		// we only show this if we are viewing a profile.
		if ($p = $this->get_person())
		{
			if ($netid = $this->get_user_netid())
			{
				if ($p->get_first_ds_value('ds_username') == $netid)
				{
					$link_txt = '';
				}
				else
				{
					$link_txt = 'View and edit your own profile';
				}
				$link_to = $netid;
				$link_preserve_array = array('pose_as');
			}
			else
			{
				$link_txt = 'Sign in to view and edit your own profile';
				$link_to = 'me';
				$link_preserve_array = array();
			}
			
			$str = '';
			$str .= '<div id="profileModuleFooter">' . "\n";
			if (!empty($link_txt)) $str .= '<a href="' . carl_construct_link(array(''), $link_preserve_array, '/profiles/'.$link_to.'/') .'">'.$link_txt.'</a>';
			$str .= '</p>';
			$str .= '</div>'."\n";
			return $str;
		}
		else return '';
	}
	
	/**
	 * If the current user's profile is being displayed, we allow it to be edited.
	 *
	 * @todo can all site admins edit all profiles? Or should we require you to pose_as a user?
	 * @return boolean;
	 */
	protected function user_can_inline_edit()
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
	protected function affiliation_supports_section( $section )
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
	protected function affiliation_supports_section_editing( $section )
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
						$this->affiliation_supports_section_editing[$section] = !$sections_by_affiliation[$affiliation][$section]['readonly'];
					else
						$this->affiliation_supports_section_editing[$section] = true;
						
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
	 * The profiles module supports three view modes:
	 * - profile: for viewing an individual profile
	 * - connect: for viewing the connections to an individual profile
	 * - tag: for viewing the connections for a particular tag.
	 */
	protected function get_view_mode()
	{
		if (!empty($this->request['tag']))
			return 'tag';
		else if (!empty($this->request['connect']))
			return 'connect';
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
	protected function get_inline_editing_form($section)
	{
		if (!isset($this->form[$section]))
		{
			$custom_form_path = 'minisite_templates/modules/profile/forms/'.$section.'.php';
			$entity_field_form_path = 'minisite_templates/modules/profile/forms/entity_field.php';
			if (reason_file_exists($custom_form_path))
			{
				reason_include_once($custom_form_path);
				$classname = $this->camelcase($section) . 'ProfileEditForm';
				$form = new $classname;
				$form->set_section($section);
				$form->set_section_display_name($this->get_section_label($section));
				$form->set_person($this->get_person());
				$form->set_head_items($this->get_head_items());
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
		return $this->form[$section];
	}
	
	/**
	 * Run the editing form for a profile section and capture its HTML output
	 *
	 * @param string $section Profile section name
	 * @return string HTML for our form
	 */
	protected function get_inline_editing_form_html($section)
	{
		if ($form = $this->get_inline_editing_form( $section ))
		{
			ob_start();
			$form->run();
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
	 */
	protected function get_user_netid()
	{
		if (!isset($this->_user_netid))
		{
			if ($this->config->allow_posing && !empty($this->request['pose_as']))
			{
				$username = strtolower($this->request['pose_as']);
				$p = new $this->person_class($username);
				if ($p->get_ds_record()) 
				{
					if (reason_check_access_to_site($this->site_id))
					{
						$this->_user_netid = $username;
					}
				}
			}
			if (!isset($this->_user_netid)) $this->_user_netid = reason_check_authentication();
		}
		return $this->_user_netid;
	}

	protected function format_phone_number($phone)
	{
		$phone_parts = explode(' ', $phone);
		// Remove leading +1
		unset($phone_parts[0]);
		return implode(' ', $phone_parts);
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
					if (method_exists($this, $this->config->section_defaults[$section]['instructions_function'])) return call_user_func_array(array($this, $this->config->section_defaults[$section]['html_function']), array($section));
					else trigger_error('Method ' . $this->config->section_defaults[$section]['instructions_function'] . ' does not exist in the profile module.');
				}
				break;
			}
		}
		return NULL;
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
				elseif (is_array($sections_by_affiliation[$affiliation][$section]) && isset($sections_by_affiliation[$affiliation][$section]['html_function']))
				{
					if (method_exists($this, $sections_by_affiliation[$affiliation][$section]['html_function'])) return call_user_func_array(array($this, $sections_by_affiliation[$affiliation][$section]['html_function']), array($section));
					else trigger_error('Method ' . $sections_by_affiliation[$affiliation][$section]['html_function'] . ' does not exist in the profile module.');
				}
				elseif (isset($this->config->section_defaults[$section]['html']))
				{
					return $this->config->section_defaults[$section]['html'];
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
			$person = $this->get_person();
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
			$this->_affiliations[$affiliation_to_return_first] = $affiliations;
		}
		return $this->_affiliations[$affiliation_to_return_first];
	}
	
	protected function get_profile_field_html($section)
	{
		$person = $this->get_person();
		$profile = $person->get_profile();
		return (isset($profile[$section]) && !carl_empty_html($profile[$section])) ? $profile[$section] : '';
	}
	
	protected function get_profile_photo_html($section = NULL)
	{
		$person = $this->get_person();
		$image = $person->get_image();
		if ($image)
		{
			return '<a href="'.htmlspecialchars($image['link']).'"><img src="'.htmlspecialchars($image['src']).'" width="200" height="200" alt="'.htmlspecialchars($image['alt']).'" /></a>'."\n";
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
			$str = '<span class="icon"></span>';
			foreach ($sites as $name => $url)
			{
				$sites_html[] = '<a href="'.htmlspecialchars($url).'">'.$this->get_section_label($section).'</a>';
			}
			$str .= implode(" | ", $sites_html);
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
				$str .= '<li><h4 class="tagName"><a href="'.$this->site_url.$this->config->explore_slug.'/'.htmlspecialchars($tag_data['slug']).'">'.$tag_data['name'].'</a></h4>';
				
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
					foreach ($profiles as $slug => $profile)
					{
						if ( $count < 12)
							$str .= '<li>';
						else
							$str .= '<li class="overflow">';
						$str .= '<a href="'.$this->site_url.$slug.'">'.$profile['display_name'].'</a></li>'."\n";	
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
	 * Get the base profile section list supplemented by any dynamic sections.
	 */
	protected function get_profile_sections_by_affiliation()
	{
		return $this->add_dynamic_profile_sections($this->config->profile_sections_by_affiliation);
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
