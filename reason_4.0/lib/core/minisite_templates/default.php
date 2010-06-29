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
 *    output. Altering the oputput of modules can be done in some cases by extending the module,
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
	 * @deprecated
	 * @access private
	 * Now use $this->head_items object.
	 */
	var $css_files;
	
	/**
	 * @deprecated
	 * @access private
	 * Now use $this->head_items object.
	 */
	var $meta;
	
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
	 * The downside to navigation caching is that page changes -- title changes, new pages,
	 * deletions, moves -- may not appear on the site for up to 15 minutes.
	 *
	 * @var boolean
	 * @access private
	 */
	var $use_navigation_cache = false;
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
			// if a session exists and we're on a secure page, pop over to the secure
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
			header( 'Location: '.ERROR_404_PAGE );
			die();
		}
		
		if ($this->use_navigation_cache)
		{
			$cache = new ReasonObjectCache($this->site_id . $this->nav_class, 900); // lifetime of 15 minutes
			$this->pages =& $cache->fetch();
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
				$cache->set($this->pages);
			}
		}
		else // if pages came from cache refresh the request variables and set site_info and order_by
		{
			$this->pages->grab_request();
			$this->pages->site_info =& $this->site_info;
			$this->pages->order_by = 'sortable.sort_order'; // in case it was changed in the request
		}
		
		$this->_handle_access_auth_check();
		
		$this->get_css_files();
		
		$this->textonly = '';
		if (!empty($this->pages->request['textonly']))
			$this->textonly = 1;
			$this->pages->textonly = $this->textonly;
		if (!empty($this->textonly))
		{
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/textonly_styles.css');
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/print_styles.css','print');
		}
		
		if( $this->pages->values  )
		{
			if( !$this->page_id )
				$this->page_id = $this->pages->root_node();

			$this->pages->cur_page_id = $this->page_id;

			$this->pages->force_open( $this->page_id );

			$this->cur_page = new entity($this->page_id);
			
			$this->title = $this->cur_page->get_value('name');

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
		if(!$rpa->has_access($auth_username,$this->page_id))
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
	}
	
	function _display_403_page()
	{
		header('HTTP/1.0 403 Forbidden');
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
				$this->head_items->add_stylesheet( $url );
			}
		}
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

		foreach( $meta_tags as $entity_field => $meta_name )
		{
			if( $this->cur_page->get_value( $entity_field ) )
			{
				$content = reason_htmlspecialchars( $this->cur_page->get_value( $entity_field ) );
				$this->head_items->add_head_item('meta',array('name'=>$meta_name,'content'=>$content) );
			}
		}
		if (!empty ($this->textonly) || !empty( $_REQUEST['no_search'] ) || $this->site_info->get_value('site_state') != 'Live' || ( defined('THIS_IS_A_DEVELOPMENT_REASON_INSTANCE') && THIS_IS_A_DEVELOPMENT_REASON_INSTANCE ) )
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
	function alter_page_type($page_type)
	{
		return $page_type;
	}
	function additional_args( &$args ) // {{{
	//if a module needs additional args
	{
	} // }}}
	function load_modules() // {{{
	{
		//for page_types variables, defines the setup of the page
		reason_include_once( 'minisite_templates/page_types.php' );
		
		if( $this->cur_page->get_value( 'custom_page' ) )
			$type = $this->cur_page->get_value( 'custom_page' );
		else
			$type = 'default';
		
		// get the section to module relationships
		// note:: i merge the default set with the chosen set to simplify
		// changing defaults.  So, in the page_types file included above,
		// you only have to change what you want to change.  All other
		// settings are maintained.
		
		// this code is a little muddled because of the structure of the page_types array.  as of 11/04, the page_types
		// array can either be a simple list of section to module name or it can be more complex with extra arguments
		// for the module itself.  hence the checks to see if the $module variable is an array or not.  if it is an
		// array, the page_type MUST have a key within that second array with the name 'module' and a value which
		// corresponds to the name of the module.
		// $page_type = array_merge( $GLOBALS['_reason_page_types'][ 'default' ], $GLOBALS['_reason_page_types'][ $type ] );
		// We used the code below instead of array_merge to allow the page_type definition to control the initialization
		// order of the modules.
		$page_type = $GLOBALS['_reason_page_types'][ 'default' ];
		if (isset( $GLOBALS['_reason_page_types'][ $type ] ) && is_array( $GLOBALS['_reason_page_types'][ $type ] ) )
		{
			foreach ( $GLOBALS['_reason_page_types'][ $type ] as $key => $value )
			{
				if (isset( $page_type[$key] ) )
					unset($page_type[$key]);
				$page_type[$key] = $value;
			}
		}
		else
		{
			trigger_error('Page type specified ('.htmlspecialchars($type,ENT_QUOTES,'UTF-8').') is not listed in the page_types.php file. You should either reinstate or change the page type.');
		}
		
		$page_type = $this->alter_page_type($page_type);
		
		foreach( $page_type AS $sec => $module )
		{
			if( is_array( $module ) )
				$module_name = $module[ 'module' ];
			else
				$module_name = $module;
			$this->section_to_module[ $sec ] = $module_name;
		}
		
		$prepped_request = conditional_stripslashes($_REQUEST);
		
		foreach( $this->section_to_module AS $sec => $module )
		{
			if( !empty( $module ) )
			{
				$module_name = $module;
				
				// collect params from page types
				if( is_array( $page_type[ $sec ] ) )
				{
					$params = $page_type[ $sec ];
					unset( $params[ 'module' ] );
				}
				else
					$params = array();
					
				$module_class = '';
				
				// this is where the template automatically loads up the PHP files with the module classes.  The 'name'
				// of a module determines the location of the file in the modules/ directory.  To make sure we're not
				// doing something insane, we make sure the file exists first before include()ing it.  Additionally, if
				// a specific file is not found, it looks in a directory of the module name to see if there is a
				// module.php file in that directory.  This serves to collect a group of files that a module might use
				// into one directory.

				if (reason_file_exists( 'minisite_templates/modules/'.$module_name.'.php' ))
				{
					reason_include_once( 'minisite_templates/modules/'.$module_name.'.php' );
				}
				elseif (reason_file_exists( 'minisite_templates/modules/'.$module_name.'/module.php' ))
				{
					reason_include_once( 'minisite_templates/modules/'.$module_name.'/module.php' );
				}
				else
				{
					trigger_error('The minisite module class file for "'.$module_name.'" cannot be found',WARNING);
				}

				// grab the class name as defined by the include file
				$module_class = $GLOBALS[ '_module_class_names' ][ $module_name ];
				
				if( !empty( $module_class ) )
				{
					$this->_modules[ $sec ] = new $module_class;
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
					$args[ 'identifier' ] = md5($sec);
					//$args[ 'nav_pages' ] =& $this->pages;
					$args[ 'textonly' ] = $this->textonly;
					
					// this is used by a few templates to add some arguments.  leaving it in for backwards
					// compatibility.  i believe that any usage of this can be done with page type parameteres now.
					$this->additional_args( $args );
					
					// localizes the args array inside the module class.  this is basically another layer of backwards
					// compatibility with old modules.
					$this->_modules[ $sec ]->prep_args( $args );
					
					// Pass a reference to the pages object into the module (so the module doesn't have to use the
					// deprecated reference to the template)
					$this->_modules[ $sec ]->set_page_nav( $this->pages );
					
					// Pass a reference to the head items object into the module (so the module doesn't have to use the
					// deprecated reference to the template)
					$this->_modules[ $sec ]->set_head_items( $this->head_items );
					
					// Pass a reference to the head items object into the module (so the module doesn't have to use the
					// deprecated reference to the template)
					$breadcrumbs_obj =& $this->_get_crumbs_object();
					$this->_modules[ $sec ]->set_crumbs( $breadcrumbs_obj );
					
					// send and check parameters gathered above from the page_types
					$this->_modules[ $sec ]->handle_params( $params );
					
					// hook to run code before grabbing and sanitizing the _REQUEST.  this is important for something
					// that might not know what variables will be coming through until a Disco class or some such thing
					// has been loaded.
					$this->_modules[ $sec ]->pre_request_cleanup_init();
					
					// it's a little ugly, but i'm setting the request variable directly here.  other method used to
					// do this, but i wanted to have a few more hooks above that would allow a module to do some work
					// before get_cleanup_rules was called.  obviously, the request variables are unavailable to those
					// modules.
					$this->_modules[ $sec ]->request = $this->clean_vars( $prepped_request, $this->_modules[$sec]->get_cleanup_rules() );
					
					// init takes $args as a backwards compatibility feature.  otherwise, everything should be handled
					// in prep_args
					if ($this->should_benchmark()) $this->benchmark_start('init module ' . $module_name);
					$this->_modules[ $sec ]->init( $args );
					if ($this->should_benchmark()) $this->benchmark_stop('init module ' . $module_name);
				}
				else
					trigger_error( 'Badly formatted module ('.$module_name.') - $module_class not set ' );
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
		//$this->textonly_toggle( 'hide_link' );
		if (empty($this->textonly))
		{
			$this->do_org_navigation();
		// You are here bar
			$this->you_are_here();
		}
		else
		{
			$this->do_org_navigation_textonly();
		}	
	} // }}}
	function create_body_tag()
	{
		return '<body>'."\n";
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
		if (!empty ($this->textonly) )
		{
			$ret .= ' (Text Only)';
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
		$crumbs = &$this->_get_crumbs_object();
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
		if (!empty($this->textonly))
		{
			$class = 'textOnlyView';
		}
		else
		{
			$class = 'fullGraphicsView';
		}
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
		if (!empty($this->textonly))
		{
			$class = 'textOnlyView';
		}
		else
		{
			$class = 'fullGraphicsView';
		}
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
		if(!empty($this->textonly))
		{
			$add_class = ' textOnly';
		}
		else
		{
			$add_class = ' fullGraphics';
		}
		echo '<div class="bannerAndMeat'.$add_class.'">'."\n";
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		echo '<div class="banner">'."\n";
		if (empty($this->textonly))
		{
			echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="bannerTable" summary="The Site Name">'."\n";
			echo '<tr>'."\n";
			echo '<td class="bannerCol1">'."\n";
		}
		echo '<div class="bannerInfo">'."\n";
		if($this->should_show_parent_sites())
		{
			echo $this->get_parent_sites_markup();
		}
		echo '<h1 class="siteName"><a href="';
		echo $this->site_info->get_value('base_url');
		if (!empty ($this->textonly) )
			echo '?textonly=1';
		echo '" class="siteLink"><span>';
		echo $this->site_info->get_value('name');
		echo '</span></a></h1>'."\n";
		echo '</div>'."\n";
		if (empty($this->textonly)) 
		{
			echo '</td>'."\n";
			echo '<td class="bannerCol2">'."\n";
		}
		if ($this->has_content( 'banner_xtra' ))
		{	
			echo '<div class="bannerXtra">';
			$this->run_section( 'banner_xtra' );
			echo '</div>'."\n";
		}
		if (empty($this->textonly))
		{
			echo '</td>'."\n".'</tr>'."\n".'</table>'."\n";
		}
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
			echo '<div id="'.$section.'">'."\n";
			$this->$show_function();
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
	} // }}}
	function show_meat_tabled() // {{{
	{
		echo '<div class="layout">'."\n";
		if (empty($this->textonly))
		{
			echo '<table border="0" cellspacing="0" cellpadding="0" class="layoutTable" summary="The Main Content of Page">'."\n";
			echo '<tr>'."\n";
			$this->show_navbar();
		}
		$this->show_main_content();
		$this->show_sidebar();
		if (empty($this->textonly))  
			echo '</tr>'."\n".'</table>'."\n";
		else
		{
			$this->show_nav_foot();
		}
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
		if ($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' )) 
		{
			if (empty($this->textonly))
				echo '<td valign="top" class="contentTD">'."\n";
			echo '<div class="content"><a name="content"></a>'."\n";
			$this->show_main_content_sections();
			echo '</div>'."\n";
			if (empty($this->textonly))
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
	}
	function show_nav_foot() // {{{
	{
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
			if (empty($this->textonly))
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
			if (empty($this->textonly))
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
	function add_head_item( $element, $attributes, $content = '', $add_to_top = false )
	{
		$this->head_items->add_head_item( $element, $attributes, $content, $add_to_top);
	}
	
	/** 
	 * @deprecated method should be called on the head_items object
	 */
	function add_stylesheet( $url, $media = '', $add_to_top = false )
	{
		$this->head_items->add_stylesheet( $url, $media, $add_to_top );
	}
	
	/**
	 * This function assembles the head items from the data provided by the modules and handles some basic checking
	 * @deprecated method should be called on the head_items object
	 */
	function get_head_item_markup()
	{
		return $this->head_items->get_head_item_markup();
	}
	
	/*this stuff comes from the tableless template. from here... */
		function has_content_section()
	{
		if($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' ) )
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
			$url_xtra = '';
			if($this->textonly)
				$url_xtra = '?textonly=1';
			$ret .= '<div class="parentSites">'."\n";
			if(count($parent_sites) == 1)
			{
				$ps = current($parent_sites);
				$ret .= '<h2><a href="'.$ps->get_value('base_url').$url_xtra.'"><span>'.$ps->get_value('name').'</span></a></h2>'."\n";
			}
			else
			{
				$ret .= '<ul>'."\n";
				foreach($parent_sites as $id=>$ps)
				{
					$ret .= '<li><h2><a href="'.$ps->get_value('base_url').$url_xtra.'"><span>'.$ps->get_value('name').'</span></a></h2></li>'."\n";
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
			$this->_should_benchmark = ($benchmarks_requested && is_developer());
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
			echo '<p><a href="'. $link . '">disable benchmarks</a></p>';
		}
		else
		{
			$link = carl_make_link(array('reason_benchmark' => 1));
			echo '<p><a href="'. $link . '">enable benchmarks</a></p>';
		}
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
