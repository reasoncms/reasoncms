<?php
/**
 * @package reason_local
 * @subpackage classes
 */
 
/**
 * Profile Config Class
 *
 * There are a lot of different settings that are shared among the various pieces of the profile system.
 * This class brings them all together, so you can set up your profiles by creating a local version of this
 * class, with or without extending the other classes.
 *
 * @author Mark Heiman
 */
 
/**
 * Set these to match the class names defined below:
 */
reason_include_once( 'minisite_templates/modules/profile/profile_person.php' );
reason_include_once( 'minisite_templates/modules/profile/connector_class.php' );

class ProfileConfig
{
	/**
	 * If you extend the profilePerson class, you need to register the classname of your 
	 * new class here.
	 */
	public $person_class = 'profilePerson';
	
	/**
	 * If you extend the ProfileConnector class, you need to register the classname of your 
	 * new class here.
	 */
	public $connector_class = 'ProfileConnector';

	/**
	 * Populate this with the Reason unique name of your profiles site.
	 */
	public $profiles_site_unique_name = 'profiles';
	
	/**
	 * Populate this with the page slug of your explore page on the profiles site.
	 */
	public $explore_slug = 'explore';
	
	/**
	 * Populate this with the page slug of your profile page on the profiles site.
	 */
	public $profile_slug = 'profile';		
	
	/**
	 * If true, passing pose_as=xxxx in the URL will allow a site admin of the profile site to pose as another user.
	 */
	public $allow_posing = true;
	
	/**
	 * If true, we depend on an .htaccess file which provides friendly redirects.
	 *
	 * RewriteCond %{REQUEST_URI} ^/profiles/([0-9a-zA-Z_]+)$
	 * RewriteRule ^([^/?&]*)$ /profiles/$1/ [R=permanent]
	 *
	 * RewriteCond %{REQUEST_URI} ^/profiles/([0-9a-zA-Z_]+)/connect/?$
	 * RewriteRule ^([^/?&]*)/connect/?$  /reason/displayers/generate_page.php?site_id=*PROFILES_SITE_ID*&page_id=*PROFILES_PAGE_ID*&username=$1&connect=1
	 * 
	 * RewriteCond %{REQUEST_URI} ^/profiles/explore/([0-9a-zA-Z_]+)/?$
	 * RewriteRule ^explore/([^/?&]*)$  /reason/displayers/generate_page.php?site_id=*PROFILES_SITE_ID*&page_id=*EXPLORE_PAGE_ID*&tag=$1
	 *
	 * RewriteCond %{REQUEST_URI} ^/profiles/([0-9a-zA-Z_]+)/$
	 * RewriteRule ^([^/?&]*)/$  /reason/displayers/generate_page.php?site_id=*PROFILES_SITE_ID*&page_id=*PROFILES_PAGE_ID*&username=$1
	 */
	public $friendly_urls = false;
	
	/**
	 * To disable profiles for a particular audience, populate like this:
	 * $audiences_temporarily_unavailable = array('student' => 'Student profiles are temporarily unavailable. Please try again later.');
	 */
	public $audiences_temporarily_unavailable = array();
	
	/**
	 * This value defines all the allowable profile sections and sets their default configuration. 
	 * These values will be used for the profile sections if no audience customizations are found.
	 * A profile section must be defined here if you want to use it in a profile.
	 * If you define a custom section to use tags, you must define the corresponding allowable relationship
	 * and register it in @see $tag_section_relationship_names below.
	 *
	 * These are sample fields as used at Carleton -- you should make your own. :]
	 */
	public $section_defaults = array(
		'image' => array(
			'html_function' => 'get_profile_photo_html',
			'region' => 'image',
		),
		'resume' => array(
			'label' => 'Résumé',
			'region' => 'links',
			'html_function' => 'get_resume_html',
			'instructions' => '<p>Your resume. Helps others know about your previous employment, skills, and goals.</p>',
		),
		'sites' => array(
			'label' => 'Web Sites',
			'region' => 'links',
			'html_function' => 'get_sites_html',
			'instructions' => '<p>Where would you like people to find you on the web? You can add up to 10 links here.</p>',
		),
		'single_site' => array(
			'label' => 'Web Site',
			'region' => 'links',
			'html_function' => 'get_single_site_html',
		),
		'overview' => array(
			'region' => 'main',
			'html_function' => 'get_profile_field_html',
			'instructions' => '<p>An introduction to your profile, tailored for the general public. Your “elevator speech.”</p>',
		),
		'professional_history' => array(
			'region' => 'main',
			'html_function' => 'get_profile_field_html',
			'instructions' => '<p>Institutions, degrees, and anything else you\'d like people to know about your background.</p>',
		),
		'tags' => array(
			'label' => 'Academic/Professional Interests',
			'region' => 'main',
			'html_function' => 'get_tags_html',
			'instructions' => '<p>Short interest tags, separated by commas. Used to find others with the same tags.</p>',
		),
		'highlights' => array(
			'region' => 'main',
			'html_function' => 'get_profile_field_html',
			'instructions' => '<p>Professional accomplishments. What have you done that you\'re proud of, and what are you currently working on?</p>',
		),
		'organizations'  => array(
			'label' => 'Organizations',
			'region' => 'main',
			'html_function' => 'get_profile_field_html',
			'instructions' => '<p>List professional organizations you belong to:</p>',
		),
		'personal_tags' => array(
			'label' => 'Personal Interests',
			'region' => 'main',
			'html_function' => 'get_tags_html',
			'instructions' => '<p>Short interest tags, separated by commas. Used to find others with the same tags.</p>',
		),
		'skills' => array(
			'label' => 'Skills',
			'region' => 'main',
			'html_function' => 'get_profile_field_html',
			'instructions' => '<p>Include skill sets such as languages, technical and IT skills, research and lab-related skills, certifications, etc.</p>',
		),
		'internships' => array(
			'label' => 'Internship Experiences',
			'region' => 'main',
			'html_function' => 'get_profile_field_html',
			'instructions' => '<p>List paid or volunteer internships that you\'ve held.</p>',
		),
		'studentorg_tags' => array(
			'label' => 'Student Organizations & Activities',
			'region' => 'main',
			'html_function' => 'get_tags_html',
			'instructions' => '<p>Include student organizations, clubs, co-curricular activities, music groups, sports teams.</p>',
		),
		/*
		'travel_tags' => array(
			'label' => 'Places I Have Traveled',
			'region' => 'main',
			'html_function' => 'get_tags_html',
			'instructions' => '<p>Enter the names of places where you have traveled or studied; connect with
				others who are interested in your experiences.</p>',
		),
		'classes_tags' => array(
			'label' => 'Favorite Classes',
			'region' => 'main',
			'html_function' => 'get_tags_html',
			'instructions' => '<p>List the classes that have particularly shaped you; connect with others who have taken
				or are interested in those classes.</p>',
		),
		*/
	);
		
	/** 
	 * Array of the Reason allowable relationships that correspond to each tag-based profile section.
	 * Each tag-based section defined in @see $section_defaults should have a corresponding entry here.
	 */
	public $tag_section_relationship_names = array('tags' => 'profile_to_interest_category',
							'personal_tags' => 'profile_to_personal_interest_category',
							/*'travel_tags' => 'profile_to_travel_category',*/
							/*'studentorg_tags' => 'profile_to_student_org_category',*/
							/*'classes_tags' => 'profile_to_classes_category' */
							);
	/**
	 * Profile sections by affiliation explicitly defines the sections available for each affiliation,
	 * as well as the default order for displaying those fields.
	 *
	 * - Set the section value to true if it should be available using the default configuration.
	 * - Set the section value to a string to customize just the label.
	 * - Set the section value to an array to optionally customize label, label_function, instructions, instructions_function, html, or html_function.
	 */
	public $profile_sections_by_affiliation = array(
		'faculty' => array(
			'image' => true,
			'resume' => array(
				'label' => 'Curriculum Vitae',
				'instructions' => '<p>Your full C.V. Helps other faculty and academics gain an in-depth understanding of your work.</p>',
			),
			'single_site' => 'Faculty Web Site',
			'courses' => 'Courses Taught This Year',
			'overview' => 'Introduction',
			'professional_history' => 'Education & Professional History',
			'tags' => 'Teaching & Research Interests',
			'highlights' => array(
				'label' => 'Highlights & Recent Activity',
				'instructions' => "Specific scholarly works you've produced, grants you've received, and projects you are currently working on.",
			),
			'organizations' => array(
				'label' => 'Organizations & Scholarly Affiliations',
				'instructions' => '<p>List societies, organizations, and other groups to which you belong or are affiliated:</p>',
			),
			'personal_tags' => 'Personal Interests',
		),
		'staff' => array(
			'image' => true,
			'resume' => 'Résumé',
			'sites' => 'Personal Web Presence',
			'overview' => 'About Me',
			'professional_history' => 'Education & Professional History',
			'tags' => 'Professional Interests',
			'highlights' => 'Highlights & Selected Accomplishments',
			'organizations' => 'Professional Organizations & Affiliations',
			'personal_tags' => 'Personal Interests',
		),
		'student' => array(
			'image' => true,
			'resume' => array(
				'label' => 'Résumé/CV',
			),
			'sites' => 'Web Presence',
			'overview' => array(
				'label' => 'Introduction',
				'instructions' => '<p>Introduce yourself; say something about your background, your goals and aspirations.</p>',
			),
			'skills' => true,
			'tags' => 'Academic and Career-Related Interests',
			'studentorg_tags' => false,
			'professional_history' => array(
				'label' => 'Work & Volunteer Experiences',
				'instructions' => '<p>List paid or volunteer positions that you\'ve held.</p>',
			),
			'internships' => true,
			'classes_tags' => true,
			'travel_tags' => true,
			'personal_tags' => array(
				'label' => 'Hobbies & Interests',
			),
		),
		'alum' => array(
			'image' => true,
			'sites' => array(
				'readonly' => true,
				),
			'tags' => 'Career or Academic Interests',
			'studentorg_tags' => array(
				'label'=>'Activities While at College',
				'instructions' => '<p>Include student organizations, clubs, co-curricular activities, music groups, sports teams, etc. that were relevant to your career path.</p>',
			),
			'professional_history' => array(
				'label' => 'Career Path',
				'instructions' => '<p>Describe how you got to where you are now.</p>',
			),
			'travel_tags' => array(
				'label' => 'Places I Studied Abroad',
				'instructions' => '<p>Enter the names of places where you studied that affected your career path.</p>',
			),
		),
	);
	
	/**
	 * If someone has multiple affiliations, they may get lists of profile fields with
	 * competing default orders. This setting determines which order wins. Set to NULL
	 * if you don't care.
	 */
	public $primary_affiliation_for_section_ordering = 'faculty';	

	/**
	 * Set this to the list of directory service affiliations that should be permitted 
	 * to set up profiles on your site.
	 */
	public $affiliations_that_have_profiles = array('student', 'faculty', 'staff', 'alum');
	
	/**
	 * Set this to the list of directory service affiliations whose profiles should
	 * require authentication to view.
	 */
	public $affiliations_that_require_authentication = array('student', 'alum');
	
	/** 
	 * Fields that should be retrieved from directory service queries. Any directory service attributes
	 * that you need to display a profile should be listed here.
	 */
	public $ds_fields = array(
			'ds_guid',	
			'ds_username',
			'ds_email',
			'ds_fullname',
			'ds_firstname',
			'ds_lastname',
			'ds_phone',
			'ds_affiliation',
			'ds_classyear',
		);
}
