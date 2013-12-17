<?php
/**
 * the default minisite_template object
 * @author dave hendler
 * @author brendon stanton
 * @author matt ryan
 * @author ben cochran
 * @author nathan white
 * @package reason
 * @subpackage minisite_templates
 */

/**
 * Register the template with Reason
 *
 * This is important for any template you create.
 *
 * Because templates are referred to by filename alone, Reason needs a way to identify
 * the name of the class to instantiate. The way it does this is by looking to see if there
 * is an index in the $GLOBALS[ '_minisite_template_class_names' ] array that
 * matches the name of the file.
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'MinisiteTemplate';

/**
 * Include a bunch of Reason libraries
 */
reason_include_once( 'function_libraries/images.php' );
reason_include_once( 'function_libraries/file_finders.php' );
reason_include_once( 'content_listers/tree.php3' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
reason_include_once( 'classes/head_items.php' );
reason_include_once( 'classes/page_access.php' );
reason_include_once( 'classes/crumbs.php' );
reason_include_once( 'classes/api/factory.php' );
reason_include_once( 'classes/object_cache.php' );
include_once( CARL_UTIL_INC . 'dev/timer.php' );

/**
 * The default (and base) Reason minisite template class
 *
 * This class handles building Reason pages. It is responsible for two main things:
 * 
 * 1. Reading the page type of the current reason page, instantiating, and initializing
 *    the modules that are part of the page
 *
 * 2. Producing and assembling the HTML of the page. Note that modules typically handle their own
 *    markup generation, so the template is only responsible for the markup *outside* the modules'
 *    output. Altering the output of modules can be done in some cases by extending the module,
 *    or in more advanced cases by using a view/markup_generation/templating system implemented by
 *    the module itself. This can vary from module-to-module.
 *
 * To create a new Reason template, you can extend this class and overload methods. As the inner
 * workings of this class are not 100% guaranteed to remain exactly as-is, it is a good idea to
 * limit modifications to the run() method and the other methods that produce (X)HTML markup.
 *
 * Note that Templates *should not* obligate particular css. Coding css directly into the template
 * will make it a lot less flexible, as it means that you will be creating a new template for every
 * single little css change. It is much better practice to think of the template as the markup and 
 * the theme (set up in the Reason database as a template + css) as where the markup and the style 
 * meet. This will enable you to use a single template for any number of similar themes.
 *
 * @todo Complete documenting this class
 *
 * @todo Make a clearer distinction between the logic and presentation -- greater mvc design
 *       and/or use of Smarty or other templating system for the markup, perhaps
 *
 * @todo Tighten up distinction between public and private methods and attributes
 *
 * @todo Work to stop passing a reference to the template to the modules. This is a bad design, as
 *       it tightly couples the modules to the template (meaning that they cannot be intantiated
 *       or run outside the context of a minisite template).
 *
 * @todo Allow multiple modules to be placed in a single section
 *
 * @todo Eliminate the silly table-based/non-table-based logic... it currently means that an ideal
 *       extension modifies both branches of the logic, which would be kind of crazy.
 */
class MinisiteTemplate
{
	/**
	 * The id of the current site
	 * @var integer
	 * @todo clean up modules so that this can be private
	 */
	var $site_id;
	/**
	 * An entity object representing the current site
	 * @var object (entity)
	 * @todo clean up modules so that this can be private
	 */
	var $site_info;
	/**
	 * The id of the current page
	 * @var integer
	 * @todo clean up modules so that this can be private
	 */
	var $page_id;
	/**
	 * An entity object representing the current page
	 * @var object (entity)
	 * @todo clean up modules so that this can be private
	 */
	 
	/**
	 * Whether or not the page shown by the template is public - populated in _handle_access_auth_check.
	 *
	 * @var boolean
	 */
	protected $page_is_public;
	
	var $page_info;
	/**
	 * The title of the current page
	 *
	 * NOTE: this generally just contains the name of the page.
	 * The full title as used in the <title> tag is produced by the method get_title().
	 *
	 * @var string
	 * @access private
	 */
	var $title;
	
	/**
	 * A minisite navigation class, which contains a tree of all pages in the site
	 * and can be asked for links, etc.
	 * @var object (supports minisiteNavigation API)
	 * @todo clean up modules so that this can be a private variable
	 */
	var $pages;
	/**
	 * The theme entity that the template should use to get css, etc.
	 * @var object (entity)
	 * @access private
	 */
	var $theme;
	/**
	 * The name of the navigation class that the module should instantiate
	 *
	 * To use a different navigation class than the default, include the class in the file that
	 * defines your template, and then overload the value of this variable.
	 *
	 * @access private
	 * @var string
	 */
	var $nav_class = 'MinisiteNavigation';
	/**
	 * An array of breadcrumbs that can be displayed in the template.
	 * 
	 * The last breadcrumb will be used in the <title> attribute.
	 *
	 * Do not address this array directly to set crumbs; use the method add_crumb() instead.
	 *
	 * @deprecated Use the _get_crumbs_object() instead
	 * @access private
	 * @var array
	 */
	var $additional_crumbs = array();
	/**
	 * The breadcrumbs object
	 *
	 * It's best practice to access this via the _get_crumbs_object() method,
	 * as this object is created lazily and may not exist here yet.
	 *
	 * @var object
	 * @access private
	 */
	 var $_crumbs;
	/**
	 * @deprecated
	 * @access private
	 */
	var $last_modified;
	/**
	 * Is there a current user logged in?
	 * @todo deprecate me
	 * @var boolean
	 */
	var $logged_in = false;
	
	/**
	 * An array that maps section names to module names
	 *
	 * Array keys are section names, array values are module names
	 *
	 * @var array
	 * @access private
	 */
	var $section_to_module = array();
	
	/**
	 * An array of module objects
	 *
	 * Array keys are section names, array values are module objects
	 * @access private
	 */
	var $_modules = array();
	
	/**
	 * Head items object
	 *
	 * This object represents all the html elements that should be placed in the head of the page.
	 * You can use it to add head items (like css, js, meta, etc.) to the page.
	 *
	 * A reference to this object is passed to all modules, enabling them to add any required head
	 * items to the page.
	 *
	 * Note that head items added after the head items have already been output in the run phase
	 * will not be included -- they must be added during the initialization phase of the template
	 * or module.
	 *
	 * Don't worry about duplicate items; the head_items object scrubs duplicates.
	 *
	 * @var object
	 * @todo clean up modules so that this can be a private variable
	 */
	var $head_items;
	/**
	 * A simple boolean that controls whether the default org name (the constant
	 * FULL_ORGANIZATION_NAME, set in  settings/package_settings.php ) should be placed in the 
	 * title of the page.
	 * @var boolean
	 * @access private
	 */
	var $use_default_org_name_in_page_title = false;
	/**
	 * This is a boolean that sends the default module into two different modes -- 
	 * table-based or non-table-based
	 * 
	 * This is a really bad design necessitated by our coupling of logic and presentation --
	 * we wanted to have logic shared by both table-based and non-table-based layouts.
	 *
	 * You probably should not rely on this variable being present in extensions to the class.
	 * 
	 * @deprecated
	 * @var boolean
	 * @access private
	 */
	var $use_tables = false;
	/**
	 * An array of major page sections
	 *
	 * Keys are section names and values are class methods
	 *
	 * This is another not-so-well-thought-out design that probably shouldn't be in the default template.
	 * It was an attempt at generalization that does not allow enough customization -- what if I want a
	 * particular html element between these two sections, or this section needs an additional wrapper
	 * div?
	 *
	 * You should probably not rely on this variable in your extensions.
	 * 
	 * @var array
	 * @access private
	 */
	var $sections = array('content'=>'show_main_content','related'=>'show_sidebar','navigation'=>'show_navbar');
	/**
	 * What elements should be used for each section?
	 *
	 * Example: array('content'=>'section','related'=>'aside');
	 *
	 * The template use divs by default.
	 * @var array
	 */
	protected $section_elements = array();
	/**
	 * What ARIA roles should each of the sections have?
	 *
	 * Example: array('content'=>'main','related'=>'complementary');
	 *
	 * @var array
	 */
	protected $section_roles = array();
	/**
	 * The doctype that the template should use
	 * @var string
	 * @access private
	 */
	var $doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	/**
	 * Should the template cache the navigation object?
	 *
	 * The default template has the option to store the navigation object in a cache.
	 * This can help speed things up (reducing queries and php processing) for sites that are 
	 * large and have high traffic.
	 *
	 * This defaults to true as of Reason 4.2 to improve performance. The minisite_page content
	 * manager has code which clears the navigation_cache when pages are modified, so there isn't a great
	 * reason to not use this by default.
	 *
	 * @var boolean
	 * @access private
	 */
	var $use_navigation_cache = true;
	/**
	 * EXPERIMENTAL: page mode
	 *
	 * This class variable stores the mode of the page
	 *
	 * The template can be put into non-standard modes like "documentation," in which the template
	 * asks the modules for their documentation rather than asking them for their output, or
	 * "samples" in which the module can produce sample output.
	 *
	 * This is still an experimental feature of the Reaspon template system.
	 *
	 * @var string possible values: 'default','documentation','samples'
	 * @access private
	 */
	var $mode = 'default';
	/**
	 * Have the current site's parent site been requested from the db?
	 *
	 * Until they have, the value of this is false. Afterwards, it is true.
	 *
	 * @var boolean
	 * @acces private
	 */
	var $queried_for_parent_sites = false;
	/**
	 * The parent sites of the current site
	 *
	 * Do not attempt to access this directly, as it will not necessarily be populated.
	 * Use the method get_parent_sites() instead.
	 *
	 * @access private
	 * @var array
	 */
	var $parent_sites = array();
	/**
	 * Should the template add the basic Reason modules.css and modules_mod.css?
	 *
	 * These css files include basic styling for many common modules. If you want to style 
	 * modules 100% from scratch, set this variable to false.
	 *
	 * @access private
	 * @var boolean
	 */
	var $include_modules_css = true;
	
	/**
	 * The page type
	 * 
	 * Don't access this variable directly -- use $this->get_page_type(), which will set it up
	 * if it has not already.
	 *
	 * @var object
	 */
	protected $page_type;
	
	/**
	 * Set up the template
	 *
	 * @var integer $site_id
	 * @var integer $page_id
	 * @todo page_id should not have a default value -- this makes it seem like you could initialize
	 *       the template without providing a page_id, but that would result in a 404.
	 */
	function initialize( $site_id, $page_id = '' ) // {{{
	{
		$this->sess =& get_reason_session();
		if( $this->sess->exists() )
		{
			// if a session exists and the server supports https, pop over to the secure
			// site so we have access to the secure session information
			force_secure_if_available();
			if(!$this->sess->has_started())
				$this->sess->start();
		}
	
		$this->site_id = $site_id;
		$this->page_id = $page_id;
		$this->site_info = new entity( $site_id );
		$this->page_info = new entity( $page_id );
		$this->head_items = new HeadItems();

		// make sure that the page exists or that the page's state is Live
		// if not, redirect to the 404
		if( !$this->page_info->get_values() OR $this->page_info->get_value( 'state' ) != 'Live' )
		{
			//trigger_error( 'page does not exist', WARNING );
			$this->display_404_page();
			die();
		}
		
		if ($this->use_navigation_cache)
		{
			$cache = new ReasonObjectCache($this->site_id . '_navigation_cache', 3600); // lifetime of 1 hour
			$page_object_cache =& $cache->fetch();
			if ($page_object_cache && is_array($page_object_cache) && isset($page_object_cache[$this->nav_class]))
			{
				$this->pages = $page_object_cache[$this->nav_class];
			}
			elseif ($page_object_cache && is_object($page_object_cache)) // old format
			{
				// lets use our cache and also update it
				$this->pages = $page_object_cache;
				$new_page_object_cache[$this->nav_class] = $this->pages;
				$cache->set($new_page_object_cache); // replace with our array keyed cache
			}
		}
		// lets check the persistent cache
		
		if (empty($this->pages) || !isset($this->pages->values[$this->page_info->id()]))
		{
			// lets setup $this->pages and place in the persistent cache
			$this->pages = new $this->nav_class;
			// small kludge - just give the tree view access to the site info.  used in the show_item function to show the root node of the navigation
			$this->pages->site_info =& $this->site_info;
			$this->pages->order_by = 'sortable.sort_order';
			$this->pages->init( $this->site_id, id_of('minisite_page') );
			if ($this->use_navigation_cache) 
			{
				$page_object_cache[$this->nav_class] = $this->pages;
				$cache->set($page_object_cache);
			}
		}
		else // if pages came from cache refresh the request variables and set site_info and order_by
		{
			$this->pages->grab_request();
			$this->pages->site_info =& $this->site_info;
			$this->pages->order_by = 'sortable.sort_order'; // in case it was changed in the request
		}
		
		$this->_handle_access_auth_check();
		
		$this->textonly = '';
		
		if( $this->pages->values  )
		{
			if( !$this->page_id )
				$this->page_id = $this->pages->root_node();

			$this->pages->cur_page_id = $this->page_id;

			$this->pages->force_open( $this->page_id );

			$this->cur_page = new entity($this->page_id);
			
			$this->title = $this->cur_page->get_value('name');
			
			$this->get_css_files();

			$this->get_meta_information();
			
			if( $this->sess->exists() )
			{
				if (USE_JS_LOGOUT_TIMER)
				{
					$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/timer.css');
					$this->head_items->add_javascript(JQUERY_URL, true);
					$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'timer/timer.js');
				}
				
				// we know that someone is logged in if the session exists
				$this->logged_in = true;
			}

			// hook for any actions to take prior to loading modules
			$this->pre_load_modules();

			// load the modules
			$this->load_modules();
		}
		else
		{
			trigger_error('Page requested not able to be displayed... no pages on site');
			$this->_display_403_page();
			die();
		}
	} // }}}
	
	function _handle_access_auth_check()
	{
		$auth_username = reason_check_authentication();
		$rpa = new reasonPageAccess();
		$rpa->set_page_tree($this->pages);
		$has_access = $rpa->has_access($auth_username, $this->page_id);
		if(!$has_access)
		{
			if(!empty($auth_username))
			{
				$this->_display_403_page();
				die();
			}
			else
			{
				header('Location: '.REASON_LOGIN_URL.'?dest_page='.urlencode(get_current_url()));
				die();
			}
		}
		else
		{
			$this->page_is_public = (empty($auth_username)) ? true : $rpa->has_access(false, $this->page_id);
		}
	}
	
	function _display_403_page()
	{
		http_response_code(403);
		if(file_exists(WEB_PATH.ERROR_403_PATH) && is_readable(WEB_PATH.ERROR_403_PATH))
		{
			include(WEB_PATH.ERROR_403_PATH);
		}
		else
		{
			trigger_error('The file at ERROR_403_PATH ('.ERROR_403_PATH.') is not able to be included');
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>403: Forbidden</title></head><body><h1>403: Forbidden</h1><p>You do not have access to this page.</p></body></html>';
		}
	}
	
	function _display_404_page()
	{
		http_response_code(404);
		if(file_exists(WEB_PATH.ERROR_404_PATH) && is_readable(WEB_PATH.ERROR_404_PATH))
		{
			include(WEB_PATH.ERROR_404_PATH);
		}
		else
		{
			trigger_error('The file at ERROR_404_PATH ('.ERROR_404_PATH.') is not able to be included');
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404: Forbidden</title></head><body><h1>404: Not Found</h1><p>This page was not found.</p></body></html>';
		}
	}
	
	// hook
	function pre_load_modules()
	{
	}
	
	function set_theme( $t ) //{{{
	{
		$this->theme = $t;
	} // }}}
	function get_css_files()
	{
		$css_files = array();

		// get css assoc with template
		$es = new entity_selector();
		$es->description = 'Get CSS associated with template';
		$es->add_type( id_of('css') );
		$es->add_right_relationship( $this->template_id, relationship_id_of('minisite_template_to_external_css') );
		$es->set_order( 'sortable.sort_order' );
		$css_files += $es->run_one();
		
		// Get css assoc with theme
		$es = new entity_selector();
		$es->description = 'Get CSS associated with theme';
		$es->add_type( id_of('css') );
		$es->add_right_relationship( $this->theme->id(), relationship_id_of('theme_to_external_css_url') );
		$es->set_order( 'sortable.sort_order' );
		$css_files += $es->run_one();

		if($this->include_modules_css)
		{
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/modules.css');
		}
		if( $css_files )
		{
			foreach( $css_files AS $css )
			{
				if($css->get_value( 'css_relative_to_reason_http_base' ) == 'true')
				{
					$url = REASON_HTTP_BASE_PATH.$css->get_value( 'url' );
				}
				else
				{
					$url = $css->get_value( 'url' );
				}
				$media = $css->has_value('css_media') ? $css->get_value('css_media') : '';
				
				$this->head_items->add_stylesheet( $url, $media );
			}
		}
		if($customizer = $this->get_theme_customizer())
		{
			$customizer->modify_head_items($this->head_items);
		}
	}
	/**
	 * Add structured extra head items (stored on the page entity as json) to the page
	 */
	function add_extra_head_content_structured()
	{
		if($this->page_info->has_value('extra_head_content_structured') && $this->page_info->get_value('extra_head_content_structured') && ($data = json_decode($this->page_info->get_value('extra_head_content_structured'))))
		{
			foreach($data as $item)
			{
				if(empty($item->url))
					continue;
				
				switch($item->type)
				{
					case 'js':
						$this->head_items->add_javascript( $item->url );
						break;
					case 'css':
						$this->head_items->add_stylesheet( $item->url );
						break;
					default:
						trigger_error('Unrecognized head item type ('.$item->type.')');
				}
			}
		}
	}
	
	/**
	 * Get the theme customizer class
	 */
	function get_theme_customizer()
	{
		if(!isset($this->theme_customizer))
		{
			if($this->theme->get_value('theme_customizer') && $this->site_info->get_value('theme_customization'))
			{
				$this->theme_customizer = reason_get_theme_customizer($this->site_info, $this->theme);
				if(empty($this->theme_customizer))
					trigger_error('Theme customizer "'.$this->theme->get_value('theme_customizer').'" not found or not registered properly. No customizations applied.');
			}
			else
				$this->theme_customizer = false;
		}
		return $this->theme_customizer;
	}
	function get_meta_information()
	{
		// add the charset information
		$this->head_items->add_head_item('meta',array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8' ) );
		
		if($favicon_path = $this->_get_favicon_path() )
		{
			$this->head_items->add_head_item('link',array('rel'=>'shortcut icon','href'=>$favicon_path, ) );
		}
		
		// array of meta tags to search for in the page entity
		// key: entity field
		// value: meta tag to use
		$meta_tags = array(
			'description' => 'description',
			'author' => 'author',
			'keywords' => 'keywords'
		);

		// load meta elements from current page
		
		$tags_added = array();
		foreach( $meta_tags as $entity_field => $meta_name )
		{
			if( $this->cur_page->get_value( $entity_field ) )
			{
				$content = reason_htmlspecialchars( $this->cur_page->get_value( $entity_field ) );
				$this->head_items->add_head_item('meta',array('name'=>$meta_name,'content'=>$content) );
				$tags_added[] = $meta_name;
			}
		}
		
		if(!in_array('keywords',$tags_added) && $this->pages->root_node() == $this->page_id)
		{
			$content = reason_htmlspecialchars( $this->site_info->get_value( 'keywords' ) );
			$this->head_items->add_head_item('meta',array('name'=>'keywords','content'=>$content) );
		}
		
		if (!empty( $_REQUEST['no_search'] ) 
			|| $this->site_info->get_value('site_state') != 'Live' 
			|| ( defined('THIS_IS_A_DEVELOPMENT_REASON_INSTANCE') && THIS_IS_A_DEVELOPMENT_REASON_INSTANCE ) 
			|| !$this->cur_page->get_value('indexable'))
		{
			$this->head_items->add_head_item('meta',array('name'=>'robots','content'=>'none' ) );
		}
	}
	function _get_favicon_path()
	{
		if(defined('REASON_DEFAULT_FAVICON_PATH') && REASON_DEFAULT_FAVICON_PATH )
		{
			return REASON_DEFAULT_FAVICON_PATH;
		}
		return NULL;
	}
	function run() // {{{
	{
		$this->start_page();
		$this->show_body();
		$this->end_page();
	} // }}}
	
	/**
	 * Run by generate_page.php if module_api is defined in the request.
	 *
	 * What if nothing has content?
	 */
	function run_api()
	{
		if (!empty($this->section_to_module))
		{	
			foreach ($this->section_to_module as $section => $module)
			{
				$module =& $this->_get_module( $section );
				$module->run_api();
			}
		}
		else // LETS DO A 404 with text/html
		{
			$api = new CarlUtilAPI('html');
			$api->run();
			exit();
		}
	}
	
	/**
	 * @return mixed string requested_api name or false
	 */
	function get_requested_api()
	{
		return (!empty($this->requested_api)) ? $this->requested_api : false;
	}
	
	/**
	 * @return mixed string requested_module_identified name or false
	 */
	function get_requested_identifier()
	{
		return (!empty($this->requested_identifier)) ? $this->requested_identifier : false;
	}
	
	function change_module( $page_type, $section, $new_module ) // {{{
	// allows runtime modification of module to use for a given
	// type-section pair.
	{
		if( $page_type == $this->cur_page->get_value( 'custom_page' ) )
			$this->section_to_module[ $section ] = $new_module;
	} // }}}
	function change_module_global( $section, $new_module ) // {{{
	// allows runtime modification of module regardless of page type
	// useful for changing the navigation section globally.
	{
		$this->section_to_module[ $section ] = $new_module;
	} // }}}
	function alter_modules() // {{{
	{
		trigger_error('alter_modules() is deprecated. Please use alter_page_type() instead');
	} // }}}
	
	/**
	 * @deprecated
	 */
	function alter_page_type($page_type)
	{
		return $page_type;
	}
	
	/**
	 * If the instantiated module extends alter_page_type - do our legacy work and throw an error.
	 *
	 * @todo We use reflection here and should make sure performance is okay... it might be possible without reflection.
	 * @todo make sure equivalency check of page_type_oldformat_altered and page_type_oldformat is correct.
	 * @todo update the export function to properly handle _meta content
	 */
	final protected function _legacy_alter_page_type($page_type, $page_type_name)
	{
		// if my instance has parents
		if ($parents = class_parents($this))
		{
			$r = new ReflectionClass(get_class($this));
			$alter_page_type_method = $r->getMethod('alter_page_type');
			if ($alter_page_type_method->class != array_pop($parents))
			{
				// we need to call alter_page_type with the old style array and trigger a warning
				//trigger_error('The template object ' . $alter_page_type_method->class . ' extends alter_page_type, which is deprecated. Use alter_reason_page_type instead.');
				$page_type_oldformat = $page_type->export("reasonPTArray_var");
				$page_type_meta = $page_type->meta();
				$page_type_oldformat_altered = $this->alter_page_type($page_type_oldformat);
				if ($page_type_oldformat_altered != $page_type_oldformat)
				{
					$rpt =& get_reason_page_types();
					$page_type = $rpt->get_page_type($page_type_name, $page_type_oldformat_altered);
					// we are setting the meta information again because the export doesn't handle it properly.
					// this should be removed once that is fixed.
					foreach($page_type_meta as $k=>$v)
						$page_type->meta($k, $v);
				};
			}
		}
		return $page_type;
	}

	/**
	 * Hook in load modules which allows modification of the page_type object.
	 *
	 * This is often a bad idea because a page type ought to work consistently across templates.
	 *
	 * @param object reference to the page type object
	 */
	function alter_reason_page_type($page_type)
	{
	}
	
	function additional_args( &$args ) // {{{
	//if a module needs additional args
	{
	} // }}}
	
	function get_page_type()
	{
		if(!isset($this->page_type))
		{
			reason_include_once( 'classes/page_types.php');
			$requested_page_type_name = ($this->cur_page->get_value('custom_page') !== FALSE) ? $this->cur_page->get_value('custom_page') : null;
		
			// get the fully composed page type - make sure to support legacy alter_page_type operations
			$rpt =& get_reason_page_types();
			$page_type = ($requested_page_type = $rpt->get_page_type($requested_page_type_name)) ? $requested_page_type : 	$rpt->get_page_type();
			$page_type = $this->_legacy_alter_page_type($page_type, $requested_page_type_name);
			$this->alter_reason_page_type($page_type);
			$this->page_type = $page_type;
		}
		return $this->page_type;
	}
	
	function load_modules() // {{{
	{
		$page_type = $this->get_page_type();

		if (extension_loaded('newrelic')) { 
			newrelic_name_transaction($page_type->get_name()); 
		}
		
		// if an api was requested lets identify the region to run
		if ($requested_api = $this->get_requested_api())
		{
			$module_api = ReasonAPIFactory::get_requested_api($page_type, $requested_api, $this->get_requested_identifier());
			if ($module_api) $this->section_to_module[$module_api['module_region']] = $module_api['module_name'];
			else $this->section_to_module = null;
		}
		else
		{
			foreach ($page_type->get_region_names() as $region)
			{
				$region_info = $page_type->get_region($region);
				$module_name = $region_info['module_name'];
				$module_filename = $region_info['module_filename'];
				if ($module_filename && reason_file_exists($module_filename)) reason_include_once( $module_filename );
				$this->section_to_module[$region] = $module_name;
			}
		}
		
		if (!empty($this->section_to_module))
		{
			foreach( $this->section_to_module AS $region => $module_name )
			{
				if( !empty( $module_name ) )
				{
					$region_info = $page_type->get_region($region);				
					$params = ($region_info['module_params'] != null) ? $region_info['module_params'] : array();
					$module_class = (!empty($GLOBALS[ '_module_class_names' ][ $module_name ])) ? $GLOBALS[ '_module_class_names' ][ $module_name ] : '';
					if( !empty( $module_class ) )
					{
						$this->_modules[ $region ] = new $module_class;
						$args = array();
						// set up a reference instead of a copy
						// dh - I really want to get rid of this.  For now, it stays.  However, I'm adding a number
						// of other parameters that a module will take by default so that we can rely on some important
						// data coming in.  9/15/04
						$args[ 'parent' ] =& $this; // pass this object to the module
						$args[ 'page_id' ] = $this->page_id;
						$args[ 'site_id' ] = $this->site_id;
						$args[ 'cur_page' ] = $this->cur_page;
						// we set the module identifier as a hash of the section - should be unique
						$args[ 'identifier' ] = ReasonAPIFactory::get_identifier_for_module($page_type, $region);
						//$args[ 'nav_pages' ] =& $this->pages;
						$args[ 'textonly' ] = '';
						$args[ 'api' ] = (!empty($module_api)) ? $module_api['api'] : false;
						$args[ 'page_is_public' ] = $this->page_is_public;
						
						// this is used by a few templates to add some arguments.  leaving it in for backwards
						// compatibility.  i believe that any usage of this can be done with page type parameteres now.
						$this->additional_args( $args );
						
						// localizes the args array inside the module class.  this is basically another layer of backwards
						// compatibility with old modules.
						$this->_modules[ $region ]->prep_args( $args );
						
						// Pass a reference to the pages object into the module (so the module doesn't have to use the
						// deprecated reference to the template)
						$this->_modules[ $region ]->set_page_nav( $this->pages );
						
						// Pass a reference to the head items object into the module (so the module doesn't have to use the
						// deprecated reference to the template)
						$this->_modules[ $region ]->set_head_items( $this->head_items );
						
						// Pass a reference to the head items object into the module (so the module doesn't have to use the
						// deprecated reference to the template)
	
						$breadcrumbs_obj =& $this->_get_crumbs_object();
						$this->_modules[ $region ]->set_crumbs( $breadcrumbs_obj );
						
						// send and check parameters gathered above from the page_types
						$this->_modules[ $region ]->handle_params( $params );
						
						// hook to run code before grabbing and sanitizing the _REQUEST.  this is important for something
						// that might not know what variables will be coming through until a Disco class or some such thing
						// has been loaded.
						$this->_modules[ $region ]->pre_request_cleanup_init();
						
						// Set the module request array based on the cleanup rules. 
						$this->_modules[ $region ]->request = $this->clean_external_vars($this->_modules[$region]->get_cleanup_rules());
						
						// init takes $args as a backwards compatibility feature.  otherwise, everything should be handled
						// in prep_args
						if ($this->should_benchmark()) $this->benchmark_start('init module ' . $module_name);
						$this->_modules[ $region ]->init( $args );
						if ($this->should_benchmark()) $this->benchmark_stop('init module ' . $module_name);
					}
					else
					{
						trigger_error( 'Badly formatted module ('.$module_name.') - $module_class not set ' );
					}
				}
			}
		}
	} // }}}
	function & _get_module( $sec ) // {{{
	{
		if( !empty( $this->_modules[ $sec ] ) && is_object( $this->_modules[ $sec ] ) )
		{
			return $this->_modules[ $sec ];
		}
		$false = false;
		return $false;
		
	} // }}}
	function clean_vars( &$vars, $rules ) // {{{
	// Returns an array which takes the values of the keys in Vars of
	// the keys set in Settings, and runs the cleaning function
	// specified in the value of settings
	{
		return carl_clean_vars( $vars, $rules );
	} // }}}

	function clean_external_vars($rules)
	// Cleanup rules can include a 'method'
	// parameter which indicates where the value should come from -- options are get, post, and 
	// nothing/anything else, which means the $_REQUEST array.
	{
		$request = $cleanup_params = array();
		$prepped_request = conditional_stripslashes($_REQUEST);
		$prepped_post = conditional_stripslashes($_POST);
		$prepped_get = conditional_stripslashes($_GET);
		foreach ($rules as $param => $rule)
		{
			if (isset($rule['method']))
			{
				switch ($rule['method'])
				{
					case 'get':
					case 'GET':
						$cleanup_params['prepped_get'][$param] = $rule;
						break;
					case 'post':
					case 'POST':
						$cleanup_params['prepped_post'][$param] = $rule;
						break;
					default:
						$cleanup_params['prepped_request'][$param] = $rule;
						break;
				}
			} else {
				$cleanup_params['prepped_request'][$param] = $rule;	
			}	
		}
		foreach ($cleanup_params as $source => $rules)
		{
			$cleaned = $this->clean_vars( $$source, $rules );
			$request = array_merge($request, $cleaned);
		}
		return $request;
	} // }}}
		
	
	function run_section( $sec ) // {{{
	{
		$module =& $this->_get_module( $sec );
		$module_name = $this->section_to_module[$sec];
		if( $module )
		{
			echo "\n\n";
			if($this->in_documentation_mode())
			{
				$this->run_documentation($sec);
			}
			else
			{
				if ($this->should_benchmark()) $this->benchmark_start('run module ' . $module_name);
				$module->run();
				if ($this->should_benchmark()) $this->benchmark_stop('run module ' . $module_name);
			}
			echo "\n\n";
		}
	} // }}}
	function run_documentation($sec)
	{
		$module =& $this->_get_module( $sec );
		if( $module )
		{
			$doc =  $module->get_documentation();
			if($doc !== false)
			{
				$module_name = $this->section_to_module[$sec];
				echo '<div class="documentation">'."\n";
				echo '<h4>'.prettify_string($module_name).'</h4>'."\n";
				echo $doc;
				echo '</div>'."\n";
			}
		}
	}
	function has_content( $sec ) // {{{
	{
		$module =& $this->_get_module( $sec );
		if( $module )
		{
			if($this->in_documentation_mode())
				return true;
			return $module->has_content();
		}
		else
			return false;
	} // }}}
	
	function start_page() // {{{
	{
		$this->get_title();
		// start page
		echo $this->get_doctype()."\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
		echo '<head>'."\n";
		//echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
		
		$this->do_org_head_items();
		$this->add_extra_head_content_structured();
		echo $this->head_items->get_head_item_markup();
		
		if($this->cur_page->get_value('extra_head_content'))
		{
			echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
		}
			
		echo '</head>'."\n";

		echo $this->create_body_tag();
		echo '<div class="hide"><a href="#content" class="hide">Skip Navigation</a></div>'."\n";
		if ($this->has_content( 'pre_bluebar' ))
			$this->run_section( 'pre_bluebar' );
		$this->do_org_navigation();
		// You are here bar
		$this->you_are_here();
	} // }}}
	function create_body_tag()
	{
		$classes = $this->get_body_tag_classes();
		if(!empty($classes))
			return '<body class="'.implode(' ',$classes).'">'."\n";
		return '<body>'."\n";
	}
	function get_body_tag_classes()
	{
		$classes = array();
		$classes[] = 'fullGraphics';
		if($this->pages->root_node() == $this->page_id)
			$classes[] = 'siteHome';
		if($this->page_info->get_value('unique_name'))
			$classes[] = 'uname_'.$this->page_info->get_value('unique_name');
		return $classes;
	}
	function get_doctype()
	{
		 return $this->doctype;
	}
	
	function get_title()
	{
		$ret = '';
		if($this->use_default_org_name_in_page_title)
		{
			$ret .= FULL_ORGANIZATION_NAME.': ';
		}
		$ret .= $this->site_info->get_value('name');
		
		if(carl_strtolower($this->site_info->get_value('name')) != carl_strtolower($this->title))
		{
			$ret .= ": " . $this->title;
		}
		$crumbs = &$this->_get_crumbs_object();
		// Take the last-added crumb and add it to the page title
		if($last_crumb = $crumbs->get_last_crumb() )
		{
			if(empty($last_crumb['id']) || $last_crumb['id'] != $this->page_id)
			{
				$ret .= ': '.$last_crumb['page_name'];
			}
		}
		$ret = reason_htmlspecialchars(strip_tags($ret));
		$this->head_items->add_head_item('title',array(),$ret, true);
		//return $ret;
	}
	
	/**
	 * Produce the breadcrumbs ("you are here") block
	 *
	 * @param string $delimiter (X)HTML to place between auto-generated crumbs
	 * @return void
	 */
	function you_are_here($delimiter = ' &gt; ') // {{{
	{
		echo '<div id="breadcrumbs" class="locationBarText">';
		echo 'You are here: ';
		echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), $delimiter);
		echo '</div>'."\n";
	} // }}}
	
	/**
	 * Generate the markup for the breadcrumbs portion of the page
	 *
	 * @param array $breadcrumb_array in form array(0=>array('link'=>'/foo/bar/','page_name'=>'Some Name'),2=>array(etc...))
	 * @param string $base (X)HTML to place before auto-generated crumbs
	 * @param string $delimiter (X)HTML to place between auto-generated crumbs
	 * @return string (X)HTML breadcrumbs markup
	 */
	function _get_breadcrumb_markup($breadcrumb_array, $base = '', $delimiter = ' &gt; ')
	{
		$pieces = array();
		if(!empty($base))
			$pieces[] = $base;
		$last = array_pop($breadcrumb_array);
		foreach($breadcrumb_array as $crumb)
		{
			$pieces[] = '<a href="'.$crumb['link'].'" class="locationBarLinks">'.$crumb[ 'page_name' ].'</a>';
		}
		$pieces[] = $last['page_name'];
		return implode($delimiter,$pieces);
	}
	/**
	 * Create a breadcrumbs object to pass to modules
	 *
	 * For use specifically by _get_crumbs_object().
	 *
	 * @return object reasonCrumbs
	 */
	function _initialize_crumbs_object()
	{
		$crumbs = new reasonCrumbs();
		$page_ids = $this->pages->get_id_chain($this->page_info->id());
		$root_page_id = array_pop($page_ids);
		$crumbs->add_crumb( $this->site_info->get_value('name'), $this->pages->get_full_url( $root_page_id ), $root_page_id );
		$page_ids = array_reverse($page_ids);
		foreach( $page_ids as $page_id )
		{
			$page = $this->pages->values[ $page_id ];
			$page_name = $page->get_value('link_name') ? $page->get_value( 'link_name' ) : $page->get_value ( 'name' );
			$crumbs->add_crumb( $page_name, $this->pages->get_full_url( $page_id ), $page_id );
		}
		return $crumbs;
	}
	
	/**
	 * Get the crumbs object
	 *
	 * Use this method to access the crumbs rather than the member variable
	 *
	 * @return object reasonCrumbs
	 */
	function &_get_crumbs_object()
	{
		if(!is_object($this->_crumbs))
		{
			$this->_crumbs = $this->_initialize_crumbs_object();
		}
		return $this->_crumbs;
	}
	
	/**
	 * Get the array of breadcrumbs from the breadcrumbs object
	 *
	 * @return array array(0=>array('link'=>'/foo/bar/','page_name'=>'Some Name'),2=>array(etc...))
	 */
	function _get_breadcrumbs()
	{
		$crumbs = &$this->_get_crumbs_object();
		return $crumbs->get_crumbs();
	}

	/**
	 * Add a breadcrumb to the set of crumbs
	 *
	 * Note: Use of this function by modules is deprecated. Modules should use their _get_crumbs() 
	 * method to access the shared breadcrumbs object instead.
	 *
	 * @param string $name
	 * @param string $link
	 * @return void
	 */
	function add_crumb( $name , $link = '', $entity_id = NULL ) // {{{
	{
		if($crumbs = &$this->_get_crumbs_object())
			$crumbs->add_crumb( $name, $link, $entity_id );
	} // }}}
	
	function show_body()
	{
		if($this->use_tables)
		{
			$this->show_body_tabled();
		}
		else
		{
			$this->show_body_tableless();
		}
	}
	function show_body_tableless() // {{{
	{
		$class = 'fullGraphicsView';
		echo '<div id="wrapper" class="'.$class.'">'."\n";
		echo '<div id="bannerAndMeat">'."\n";
		$this->show_banner();
		$this->show_meat();
		echo '</div>'."\n";
		$this->show_footer();
		echo '</div>'."\n";
	} // }}}
	function show_body_tabled() // {{{
	{
		$class = 'fullGraphicsView';
		echo '<div id="wrapper" class="'.$class.'">'."\n";
		$this->show_banner();
		$this->show_meat();
		$this->show_footer();
		echo '</div>'."\n";
	} // }}}
	function end_page() // {{{
	{
		// finish body and html
		$this->do_org_foot();
		//$this->_do_testing_form();
		echo '</body>'."\n";
		echo '</html>'."\n";
	} // }}}
	/*
	function _do_testing_form()
	{
		echo '<form name="testing123" action="?">';
		$keys = array();
		foreach(array_keys($this->_modules) as $module_key)
		{
			$keys = array_merge($keys,array_keys($this->_modules[$module_key]->get_cleanup_rules()));
		}
		$keys = array_unique($keys);
		foreach($keys as $key)
			echo '<input type="hidden" name="'.htmlspecialchars($key,ENT_QUOTES).'" value="" />';
		echo '</form>';
	}
	*/
	
	function show_banner()
	{
		if($this->use_tables)
		{
			$this->show_banner_tabled();
		}
		else
		{
			$this->show_banner_tableless();
		}
	}
	function show_banner_tableless() // {{{
	{
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		echo '<div id="banner">'."\n";
		if($this->should_show_parent_sites())
		{
			echo $this->get_parent_sites_markup();
		}
		echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
		$this->show_banner_xtra();
		echo '</div>'."\n";
		if($this->has_content('post_banner'))
		{
			echo '<div id="postBanner">'."\n";
			$this->run_section('post_banner');
			echo '</div>'."\n";
		}
	} // }}}
	function show_banner_tabled() // {{{
	{
		$add_class = ' fullGraphics';
		echo '<div class="bannerAndMeat'.$add_class.'">'."\n";
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		echo '<div class="banner">'."\n";
		echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="bannerTable" summary="The Site Name">'."\n";
		echo '<tr>'."\n";
		echo '<td class="bannerCol1">'."\n";
		echo '<div class="bannerInfo">'."\n";
		if($this->should_show_parent_sites())
		{
			echo $this->get_parent_sites_markup();
		}
		echo '<h1 class="siteName"><a href="';
		echo $this->site_info->get_value('base_url');
		echo '" class="siteLink"><span>';
		echo $this->site_info->get_value('name');
		echo '</span></a></h1>'."\n";
		echo '</div>'."\n";
		echo '</td>'."\n";
		echo '<td class="bannerCol2">'."\n";
		if ($this->has_content( 'banner_xtra' ))
		{	
			echo '<div class="bannerXtra">';
			$this->run_section( 'banner_xtra' );
			echo '</div>'."\n";
		}
		echo '</td>'."\n".'</tr>'."\n".'</table>'."\n";
		if($this->has_content('post_banner'))
		{
			echo '<div id="postBanner">'."\n";
			$this->run_section('post_banner');
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
	} // }}}
	function show_meat()
	{
		if($this->use_tables)
		{
			$this->show_meat_tabled();
		}
		else
		{
			$this->show_meat_tableless();
		}
	}
	function show_meat_tableless() // {{{
	{
		$hasSections = array();
		$blobclass = 'contains';
		$classes = array();
		foreach($this->sections as $section=>$show_function)
		{
			$has_function = 'has_'.$section.'_section';
			if($this->$has_function())
			{
				$hasSections[$section] = $show_function;
				$capsed_section_name = ucfirst($section);
				$classes[] = 'contains'.$capsed_section_name;
				$blobclass .= substr($capsed_section_name,0,3);
			}
		}
		echo '<div id="meat" class="'.implode(' ',$classes).' '.$blobclass.'">'."\n";
		foreach($hasSections as $section=>$show_function)
		{
			if(isset($this->section_elements[$section]))
				$element = $this->section_elements[$section];
			else
				$element = 'div';
			echo '<'.$element.' id="'.$section.'"';
			if(isset($this->section_roles[$section]))
				echo ' role="'.$this->section_roles[$section].'"';
			echo '>'."\n";
			$this->$show_function();
			echo '</'.$element.'>'."\n";
		}
		echo '</div>'."\n";
	} // }}}
	function show_meat_tabled() // {{{
	{
		echo '<div class="layout">'."\n";
		echo '<table border="0" cellspacing="0" cellpadding="0" class="layoutTable" summary="The Main Content of Page">'."\n";
		echo '<tr>'."\n";
		$this->show_navbar();
		$this->show_main_content();
		$this->show_sidebar();
		echo '</tr>'."\n".'</table>'."\n";
		echo '</div>'."\n";
		echo '</div>'."\n";
	} // }}}
	function show_navbar()
	{
		if($this->use_tables)
		{
			$this->show_navbar_tabled();
		}
		else
		{
			$this->show_navbar_tableless();
		}
	}	
	function show_navbar_tableless() // {{{
	{
		if ($this->has_content( 'navigation' )) 
		{ 
			$this->run_section( 'navigation' );
		}
		if ($this->has_content( 'sub_nav' )) 
		{ 
			echo '<div id="subNav">'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'sub_nav_2' ))
		{
			$this->run_section( 'sub_nav_2' );
		}
		
		if ($this->has_content( 'sub_nav_3' ))
		{
			$this->run_section( 'sub_nav_3' );
		}
	} // }}}
	function show_navbar_tabled() // {{{
	{
		if ($this->has_content( 'navigation' ) || $this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) ) 
		{
			echo '<td valign="top" class="navigationTD">'."\n";
			if ($this->has_content( 'navigation' )) 
			{ 
				//$_nav_timing_start = getmicrotime();
				echo '<div class="navigation">'."\n";
				$this->run_section( 'navigation' );
				echo '</div>'."\n";
				//$_nav_timing_end = getmicrotime();
				//echo '<!-- nav start time: '.$_nav_timing_start.'   nav end time: '.$_nav_timing_end.'   total nav time: '.round(1000*($_nav_timing_end - $_nav_timing_start), 1).' ms -->'."\n";
						
			}
			if ($this->has_content( 'sub_nav' )) 
			{ 
				echo '<div class="subNav">'."\n";
				echo '<hr class="hideFromModern" />'."\n";
				$this->run_section( 'sub_nav' );
				echo '</div>'."\n";
			}
			if ($this->has_content( 'sub_nav_2' ))
				$this->run_section( 'sub_nav_2' );
			if ($this->has_content( 'sub_nav_3' ))
				$this->run_section( 'sub_nav_3' );
			echo '<div class="navigationSpacer"><img src="'.REASON_HTTP_BASE_PATH.'ui_images/stp.gif" width="150" height="2" alt="" /></div>'."\n";
			echo '</td>'."\n";
		}
	} // }}}
	function show_main_content()
	{
		if($this->use_tables)
		{
			$this->show_main_content_tabled();
		}
		else
		{
			$this->show_main_content_tableless();
		}
	}
	function show_main_content_tableless() // {{{
	{
		$this->show_main_content_sections();
	} // }}}
	
	function show_main_content_tabled() // {{{
	{
		if ($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' ) || $this->has_content( 'main_post_2' ) || $this->has_content( 'main_post_3' )) 
		{
			echo '<td valign="top" class="contentTD">'."\n";
			echo '<div class="content"><a name="content"></a>'."\n";
			$this->show_main_content_sections();
			echo '</div>'."\n";
			echo '</td>'."\n";
		}
	} // }}}
	function show_main_content_sections()
	{
		if ($this->has_content( 'main_head' )) 
		{
			echo '<div class="contentHead">'."\n";
			$this->run_section( 'main_head' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main' )) 
		{
			echo '<div class="contentMain">'."\n";
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_post' )) 
		{
			echo '<div class="contentPost">'."\n";
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_post_2' )) 
		{
			echo '<div class="contentPost2">'."\n";
			$this->run_section( 'main_post_2' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_post_3' )) 
		{
			echo '<div class="contentPost3">'."\n";
			$this->run_section( 'main_post_3' );
			echo '</div>'."\n";
		}
	}
	/**
	 * @deprecated This was for textonly views, which are deprecated
	 * @todo remove this function
	 */
	function show_nav_foot() // {{{
	{
		trigger_error('show_nav_foot() is deprecated. It will go away in a future release of Reason.');
		if ($this->has_content( 'sub_nav_2' ))
			$this->run_section( 'sub_nav_2' );
		if ($this->has_content( 'navigation' )) 
		{
			echo '<div class="navigation">'."\n";
			$this->run_section( 'navigation' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'sub_nav' )) 
		{ 
			echo '<div class="subNav">'."\n";
			echo '<hr class="hideFromModern" />'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		} 
	} // }}}
	function show_sidebar()
	{
		if($this->use_tables)
		{
			$this->show_sidebar_tabled();
		}
		else
		{
			$this->show_sidebar_tableless();
		}
	}
	function show_sidebar_tableless() // {{{
	{
		if($this->has_content( 'pre_sidebar' ))
		{
			echo '<div id="preSidebar">'."\n";
			$this->run_section( 'pre_sidebar' );
			echo '</div>'."\n";
		}
		if($this->has_content( 'sidebar' ))
		{
			echo '<div id="sidebar">'."\n";
			$this->run_section( 'sidebar' );
			echo '</div>'."\n";
		}
		if($this->has_content( 'post_sidebar' ))
		{
			echo '<div id="postSidebar">'."\n";
			$this->run_section( 'post_sidebar' );
			echo '</div>'."\n";
		}
	} // }}}
	function show_sidebar_tabled() // {{{
	{
		$show_sidebar = $this->has_content( 'sidebar' );
		$show_pre_sidebar = $this->has_content( 'pre_sidebar' );
		$show_post_sidebar = $this->has_content( 'post_sidebar' );
		if ($show_sidebar || $show_pre_sidebar || $show_post_sidebar)
		{ 
			echo '<td valign="top" class="sidebarTD">'."\n"; 
			if($show_pre_sidebar)
			{
				echo '<div class="preSidebar">'."\n";
				$this->run_section( 'pre_sidebar' );
				echo '</div>'."\n";
			}
			if($show_sidebar)
			{
				echo '<div class="sidebar">'."\n";
				$this->run_section( 'sidebar' );
				echo '</div>'."\n";
			}
			if($show_post_sidebar)
			{
				echo '<div class="postSidebar">'."\n";
				$this->run_section( 'post_sidebar' );
				echo '</div>'."\n";
			}
			echo '<div class="sidebarSpacer">'."\n";
			echo '<img src="'.REASON_HTTP_BASE_PATH.'ui_images/stp.gif" width="80" height="2" alt="" />'."\n";
			echo '</div>'."\n";
			echo '</td>'."\n";
		}
	} // }}}
	function show_footer()
	{
		if($this->use_tables)
		{
			$this->show_footer_tabled();
		}
		else
		{
			$this->show_footer_tableless();
		}
		// $this->_do_testing_form();
	}
	function show_footer_tableless() // {{{
	{
		echo '<div id="footer">'."\n";
		echo '<div class="module1">';
		$this->run_section( 'footer' );
		echo '</div>';
		echo '<div class="module2 lastModule">';
		$this->run_section( 'edit_link' );
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		echo '</div>';
		// I'm not ready to turn this on, but in the near future we should make this part of the
		// default template -- Matt Ryan. Apr. 3, 2009
		// $this->show_reason_badge();
		echo '</div>'."\n";
	} // }}}
	function show_footer_tabled() // {{{
	{
		echo '<div id="footer" class="maintained">'."\n";
		$this->run_section( 'footer' );
		$this->run_section( 'edit_link' );
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		// $this->show_reason_badge();
		echo '</div>'."\n";
	} // }}}
	
	function show_reason_badge()
	{
		echo '<div class="poweredBy">Powered by <a href="http://reason.carleton.edu" title="Reason Content Management System">Reason CMS</a></div>';
	}
	
	
	/**
	 * This function allows modules to add head items. They must add any head items during their init process.
	 * @deprecated method should be called on the head_items object
	 */
	function add_head_item( $element, $attributes, $content = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>'') )
	{
		$this->head_items->add_head_item( $element, $attributes, $content, $add_to_top, $wrapper);
	}
	
	/** 
	 * @deprecated method should be called on the head_items object
	 */
	function add_stylesheet( $url, $media = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>'') )
	{
		$this->head_items->add_stylesheet( $url, $media, $add_to_top, $wrapper );
	}
	
	/**
	 * This function used to set up the head item markup. It has been replaced by direct access to the head items object.
	 * @deprecated method should be called on the head_items object
	 */
	function get_head_item_markup()
	{
		trigger_error('$this->get_head_items_markup() no longer works on templates. Use $this->head_items->get_head_item_markup() instead.');
		return;
	}
	
	/*this stuff comes from the tableless template. from here... */
		function has_content_section()
	{
		if($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' ) || $this->has_content( 'main_post_2' ) || $this->has_content( 'main_post_3' ) )
		{
			return true;
		}
		return false;
	}
	function has_navigation_section()
	{
		if( $this->has_content( 'navigation' ) || $this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) )
		{
			return true;
		}
		return false;
	}
	function has_related_section()
	{
		if( $this->has_content( 'pre_sidebar' ) || $this->has_content( 'sidebar' ) )
		{
			return true;
		}
		return false;
	}
	function show_banner_xtra()
	{
		if ($this->has_content( 'banner_xtra' ))
		{	
			echo '<div id="bannerXtra">';
			$this->run_section( 'banner_xtra' );
			echo '</div>'."\n";
		}
	}
	/* ...down to here */
	
	function do_org_navigation()
	{
		// Just here as a shell for branding
	}
	/**
	 * @deprecated Textonly is no longer a thing.
	 */
	function do_org_navigation_textonly()
	{
		$this->do_org_navigation();
	}
	
	function do_org_head_items()
	{
		// Just here as a hook for branding head items (js/css/etc.)
	}
	function do_org_foot()
	{
		// Just here as a shell for branding
	}
	function in_documentation_mode()
	{
		if($this->mode == 'documentation')
			return true;
		return false;
	}
	function should_show_parent_sites()
	{
		return false;
	}
	function get_parent_sites_markup()
	{
		$ret = '';
		$parent_sites = $this->get_parent_sites();
		if(!empty($parent_sites))
		{
			$ret .= '<div class="parentSites">'."\n";
			if(count($parent_sites) == 1)
			{
				$ps = current($parent_sites);
				$ret .= '<h2><a href="'.$ps->get_value('base_url').'"><span>'.$ps->get_value('name').'</span></a></h2>'."\n";
			}
			else
			{
				$ret .= '<ul>'."\n";
				foreach($parent_sites as $id=>$ps)
				{
					$ret .= '<li><h2><a href="'.$ps->get_value('base_url').'"><span>'.$ps->get_value('name').'</span></a></h2></li>'."\n";
				}
				$ret .= '</ul>'."\n";
			}
			$ret .= '</div>'."\n";
		}
		return $ret;
	}
	function get_parent_sites()
	{
		if(!$this->queried_for_parent_sites)
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_right_relationship( $this->site_id, relationship_id_of( 'parent_site' ) );
			$es->set_order( 'entity.name' );
			if($this->site_info->get_value('site_state') == 'Live')
			{
				$es->add_relation('site_state = "Live"');
			}
			$this->parent_sites = $es->run_one();
			$this->queried_for_parent_sites = true;
		}
		return $this->parent_sites;
	}
	
	/**
	 * Return a reference to a singleton Timer class
	 *
	 * @return object Timer
	 */
	function &_get_timer()
	{
		if (!isset($this->_timer))
		{
			$this->_timer = new Timer();
		}
		return $this->_timer;
	}

	/**
	 * Start timing a named benchmark
	 *
	 * @param string name of benchmark to start
	 * @return void
	 */	
	function benchmark_start($name)
	{
		$timer =& $this->_get_timer();
		$timer->start($name);
	}
	
	/**
	 * Stop timing a named benchmark
	 *
	 * @param string name of benchmark to stop
	 * @return void
	 */
	function benchmark_stop($name)
	{
		$timer =& $this->_get_timer();
		$timer->stop($name);
	}

	/**
	 * Returns true if benchmarks_available() and benchmarks_enabled() return true;
	 * @return boolean
	 */		
	function should_benchmark()
	{
		if (!isset($this->_should_benchmark))
		{
			$benchmarks_requested = (isset($_REQUEST['reason_benchmark']) && ($_REQUEST['reason_benchmark'] == 1));
			$this->_should_benchmark = ($benchmarks_requested && is_developer() && !$this->get_requested_api());
		}
		return $this->_should_benchmark;
	}
	
	/**
	 * Show a link to enable benchmarks, or if enabled, the benchmarks themselves with a link to turn them off
	 * 
	 * This is called by generate_page.php after page generation if is_developer returns true.
	 */
	function display_benchmark_section()
	{
		if ($this->should_benchmark())
		{
			$timer =& $this->_get_timer();
			$timer->report_all();
			$link = carl_make_link(array('reason_benchmark' => ""));
			echo '<p><a href="'. $link . '">disable benchmarks</a></p>'."\n";
		}
		else
		{
			$link = carl_make_link(array('reason_benchmark' => 1));
			echo '<p><a href="'. $link . '">enable benchmarks</a></p>'."\n";
		}
		//$encoded_target = urlencode(carl_make_link(array('_force_mime_type'=>'xhtml'),'','',false));
		//echo '<p>Validate Markup: <a href="http://validator.w3.org/check?verbose=1&amp;uri='.$encoded_target.'">W3C</a> | <a href="http://html5.validator.nu/?doc='.$encoded_target.'">validator.nu</a></p>'."\n";
	}
	
	/**
	 * Generate page runs this method when it generates a page footer with developer information.
	 */
	function display_developer_section()
	{
		$this->display_benchmark_section();
	}
}
?>
