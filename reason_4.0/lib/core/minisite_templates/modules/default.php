<?php
	/**
	 * @package reason
	 * @subpackage minisite_modules
	 */
	
	/**
	 * Register the module with Reason
	 *
	 * Get the name of the file, without all the path information
	 * and without the .php suffix and set the name of the class
	 * to that index of this global array
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DefaultMinisiteModule';

	/**
	 * The base minisite module
	 *
	 * This class defines the API of minisite modules, and serves as a base
	 * class for other minisite modules
	 *
	 * This class is not completely abstract; it contains the prep_args and 
	 * handle_params utilities
	 */
	class DefaultMinisiteModule
	{
		var $cleanup_rules = array();
	
		/**
		 * the cleaned up array of request variables
		 * @var array
		 */
		var $request = array();
		/**
		 * page id as passed from the template
		 * @var integer
		 */
		var $page_id;
		/**
		 * site id as passed from the template
		 * @var integer
		 */
		var $site_id;
		/**
		 * identifier as assigned by the template
		 * @var string
		 */
		var $identifier;
		/**
		 * api that the module is running or false if no api is running - assigned by the template
		 * @var mixed string or false if no active api
		 */
		var $api;
		/**
		 * current page entity (this is the cur_page object from the pages collection from the template
		 * @var object (entity)
		 */
		var $cur_page;
		/**
		 * @deprecated since reason 4 beta 8
		 * @var object
		 */
		var $session = null;
		/**
		 * textonly variable from the template
		 * @var mixed (null until set as boolean value)
		 */
		var $textonly = null;
		/**
		 * A page if considered public if it can be viewed without login.
		 *
		 * Access through the function $this->page_is_public()
		 *
		 * @var object
		 */
		var $page_is_public;
		
		/**
		 * allowable parameters for this module
		 *
		 * IMPORTANT: this array must contain fully specified keys AND values.
		 *
		 * @var array
		 */
		var $acceptable_params = array();
		/**
		 * Parameters that are to be inherited by classes that extend the current class
		 *
		 * Structured the same way as acceptable_params
		 *
		 * @var array
		 */
		var $base_params = array();
		/**
		 * params as keys with values as values
		 *
		 * These are the parameters set up in the page types file
		 *
		 * Note that only those in acceptable_params or base_params will be set
		 *
		 * @var array
		 */
		var $params;


		/**
		 * noncanonical_request_keys with values as values
		 * 
		 * Use get_cleanup_rules to see possible url parameters for
		 * a given module
		 * 
		 * @var array
		 */
		var $noncanonical_request_keys = array(); 

		/**
		 * see if arguments have been parsed
		 * @var boolean
		 */
		var $_prepped = false;
		/**
		 * copy of original arguments
		 * @deprecated
		 * @var array
		 */
		var $_args = array();
		
		/**
		 * $parent is DEPRECATED.  Only for compatibility.
		 *
		 * this used to be the way to access page_id, site_id, etc.
		 * but that method sucks.  so we just pass down the right
		 * information now.  which is better
		 *
		 * @deprecated
		 * @var object minisite_template
		 * @todo Remove use of $this->parent from core minisite modules
		 */
		var $parent;
		
		/**
		 * A reference to the current minisite navigation object
		 *
		 * Access through the function $this->get_page_nav()
		 *
		 * @var object
		 */
		var $_pages;
		
		/**
		 * A reference to the head items object
		 *
		 * Access through the function $this->get_head_items()
		 *
		 * @var object
		 */
		var $_head_items;
		
		/**
		 * A reference to the breadcrumbs object
		 *
		 * Access through the function $this->_get_crumbs()
		 *
		 * @var object
		 */
		var $_crumbs;
			
		/**
		 * We need this because late static binding is not supported until PHP 5.3
		 *
		 * @todo eliminate me when we require PHP 5.3 or above.
		 */
		protected static $classname = NULL;

		/**
		 * convenience method that the template calls. sets up the variables from the template
		 * @param array $args
		 * @todo is there anything necessary about this? Shouldn't classes that 
		 *       instantiate modules just call something like set_site, set_page, etc?
		 *       This seems unnecessarily dangerous and complex.
		 */
		function prep_args( $args = array() ) // {{{
		{
			// only run this method once.  for compatibility, it is called by init().
			// older modules may have some weird inheritance or do some of the work
			// themselves.
			if( !$this->_prepped )
			{
				// loop through the args passed and make them object variables.
				// kind of bad as it could hide some variable declarations
				if( !empty( $args ) AND is_array( $args ) )
				{
					foreach( $args AS $key => $val )
						if( $key != 'parent' )
							$this->$key = $val;
				}
				// for compatibility with older modules only.  new modules should NOT use the parent var
				$this->parent =& $args[ 'parent' ];
				// save the args
				$this->_args = $args;
				// make sure this chunk isn't run again
				$this->_prepped = true;
			}
		} // }}}
		/**
		 * takes in the list of parameters set up by the page_types array. 
		 *
		 * checks to see if the module has defined each
		 * key as a valid parameter to accept.  if it is, adds the value to the 
		 * internal array for module usage.
		 *
		 * @param array $params
		 */
		function handle_params( $params ) // {{{
		{
			// run through acceptable_params and collect the real keys.  If there is an index that
			// is an actual integer, this is an entry where the key was specified as a value.  Otherwise,
			// if there is a programmer entered key AND value, then the key is the key, as it were.
			// we stuff these defaults directly into the params array
			$this->params = array();
			$this->acceptable_params += $this->base_params; // nwhite 12/20/2006
			if( is_array( $this->acceptable_params ) )
			{
				foreach( $this->acceptable_params AS $key => $val )
				{
					if( is_int( $key ) )
						$this->params[ $val ] = '';
					else
						$this->params[ $key ] = $val;
				}
			}
			
			// now, run through the params passed in and copy them in, unless they are bad stuffs
			foreach( $params AS $name => $val )
			{
				if( array_key_exists( $name, $this->params ) )
				{
					$this->params[ $name ] = $val;
				}
				else
				{
					trigger_error( 'Module Parameter Error: module does not accept parameter named "'.$name.'"' );
				}
			}
		} // }}}
		
		/**
		 * Return an array of APIs supported by the class tree.
		 *
		 * We require class_name and use reflection for now since only php 5.3 and above supports late static binding.
		 */
		static final function get_supported_apis($class_name)
		{
			static $supported_apis;
			if (empty($class_name) || !class_exists($class_name))
			{
				trigger_error('get_supported_apis must be provided the name of a defined class.');
			}
			elseif (!isset($supported_apis[$class_name]))
			{
				self::$classname = $class_name; // providing context for add_api
				while ($class = (isset($class)) ? $class->getParentClass() : new ReflectionClass($class_name))
				{
					if ($class->getMethod('setup_supported_apis')->getDeclaringClass()->getName() == $class->getName())
					{
						$class->getMethod('setup_supported_apis')->invoke(NULL);
					}
				}
				$supported_apis[$class_name] = self::_api_helper('get');
				self::$classname = NULL;
			}			
			return $supported_apis[$class_name];
		}
		
		/**
		 * _api_helper is used to get and set arrays of ReasonAPI objects for a module.
		 *
		 * Since we cannot rely on late static binding, we utilize the static classname if available.
		 *
		 * @param string action - should be "set" or "get"
		 * @param string name - name of api
		 * @param object api - ReasonAPI object
		 */
		static private final function _api_helper($action = 'set', $name = NULL, $api = NULL)
		{
			static $apis;
			$context = (isset(self::$classname)) ? self::$classname : __CLASS__;
			if ($action == 'get')
			{
				return $apis[$context];
			}
			elseif ($action == 'set' && !empty($name) && !empty($api))
			{
				$apis[$context][$name] = $api;
			}
		}
		
		/**
		 * Modules should use this method to add any APIs they support as follows:
		 *
		 * - Instantiate the API(s)
		 * - Call self::add_api with the API name and API object as parameters.
		 *
		 * Example:
		 * 
		 * <code>
		 * $standalone_api = new ReasonAPI('html');
		 * self::add_api('standalone', $standalone_api);
		 * </code>
		 */
		static function setup_supported_apis()
		{
			self::add_api('standalone', new ReasonAPI('html'));
		}
		
		/**
		 * Can I reliable find out who called me without late static binding - no ...
		 */
		static protected final function add_api($api_name, $api)
		{
			self::_api_helper('set', $api_name, $api);
		}	
		
		/**
		 * Return a string suitable for inclusion in a class declaration - the string includes:
		 *
		 * - module_api-moduleapi strings for each supported api
		 * - module_identifier-identifier string
		 */
		final function get_api_class_string()
		{
			$module_identifier = 'module_identifier-' . $this->identifier;
			$supported_apis = $this->get_supported_apis(get_class($this));
			$api_string = (!empty($supported_apis)) ? "module_api-" . implode(" module_api-", array_keys($supported_apis)) : '';
			$api_class_string = (!empty($api_string)) ? $module_identifier . " " . $api_string : $module_identifier;
			return htmlspecialchars($api_class_string,ENT_QUOTES,'UTF-8');
		}
		/**
		 *	Get the URL for a given api on this module
		 *
		 * @param string $api_string
		 * @return mixed html-escaped URL of the api, or NULL if $api_string is not supported
		 */
		function get_api_url($api_string = 'html')
		{
			$supported_apis = $this->get_supported_apis(get_class($this));
			if(!in_array($api_string, array_keys($supported_apis)))
			{
				trigger_error('$api_string "'.$api_string.'" passed to get_api_url() not supported. Use one of the following: "'.implode('", "',array_keys($supported_apis)).'".');
				return NULL;
			}
			return carl_make_link(array('module_api' => $api_string,'module_identifier' => $this->identifier));
		}
		
		/**
		 * Return the active api or false
		 * @return mixed string or false
		 */
		function get_api()
		{
			return $this->api;
		}
		
		/**
		 * Set a reference to the current minisite navigation object
		 *
		 * Many modules need a reference to this object, so any module instantiation should create
		 * a minisite navigation object and pass it into the module using this method.
		 *
		 * @param object $pages
		 */
		function set_page_nav(&$pages )
		{
			$this->_pages =& $pages;
		}
		/**
		 * Get a reference to the current minisite navigation object
		 *
		 * This method will trigger an error if there is no page navigation object set, and will
		 * return a NULL value. Therefore, it is a good idea for code in a module to wrap
		 * interaction with the page navigation in a conditional, as below.
		 *
		 * Example code using this method:
		 * <code>
		 * if($pages =& $this->get_page_nav())
		 * {
		 * 	$pages->make_current_page_a_link();
		 * }
		 * </code>
		 *
		 * @return object | NULL
		 */
		function &get_page_nav()
		{
			if(!empty($this->_pages))
			{
				return $this->_pages;
			}
			trigger_error('No page navigation object set');
			return NULL;
		}
		/**
		 * Set a reference to the current head items object
		 *
		 * Many modules need a reference to this object, so any module instantiation should create
		 * a head items object and pass it into the module using this method. 
		 *
		 * @param object $head_items
		 */
		function set_head_items( &$head_items )
		{
			$this->_head_items =& $head_items;
		}
		/**
		 * Get a reference to the current head items object
		 *
		 * Note that this method will trigger an error and return NULL if no head items have been
		 * set on the module.
		 *
		 * It is a good idea for modules to not *assume* that they have been given a head items
		 * object, and wrap interaction with the head items in a conditional, e.g.:
		 *
		 * <code>
		 * if($head_items =& $this->get_head_items())
		 * {
		 * 	$head_items->add_stylesheet('/path/to/stylesheet.css');
		 * }
		 * </code>
		 *
		 * @return object | NULL
		 */
		function &get_head_items()
		{
			if(!empty($this->_head_items))
			{
				return $this->_head_items;
			}
			trigger_error('No head items object set');
			return NULL;
		}
		
		/**
		 * Set a reference to the current breadcrumbs object
		 *
		 * @access public
		 * @param object $crumbs
		 */
		function set_crumbs( &$crumbs )
		{
			$this->_crumbs =& $crumbs;
		}
		/**
		 * Get a reference to the current breadcrumbs object
		 *
		 * Note that this method will trigger an error and return NULL if no breadcrumbs have been
		 * set on the module.
		 *
		 * It is a good idea for modules to not *assume* that they have been given a breadcrumbs
		 * object, and wrap interaction with the crumbs in a conditional, e.g.:
		 *
		 * <code>
		 * if($crumbs =& $this->_get_crumbs())
		 * {
		 * 	$crumbs->add_crumb('Link Name','/link/url/');
		 * }
		 * </code>
		 *
		 * Alternately (and more conveniently,) you can just use $this->_add_crumb().
		 *
		 * @access private
		 * @return object | NULL
		 */
		function &_get_crumbs()
		{
			if(!empty($this->_crumbs))
			{
				return $this->_crumbs;
			}
			trigger_error('No breadcrumbs object set');
			return NULL;
		}
		
		/**
		 * Add a breadcrumb to the crumbs object
		 *
		 * While you can use the _get_crumbs() method and interact directly with
		 * it to add crumbs, approx. 99% of the time you will be simply adding
		 * a breadcrumb to the crumbs object. Hence, this convenience method.
		 *
		 * @access private
		 * @param string $name The text of the breadcrumb link
		 * @param string $url the url of the breadcrumb link
		 * @return void
		 */
		function _add_crumb($name, $url = '')
		{
			if($crumbs =& $this->_get_crumbs())
		 	{
		 		$crumbs->add_crumb($name,$url);
		 	}
		}
		
		/**
		 * hook to run some code and load some objects before get_cleanup_rules() is called
		 */
		function pre_request_cleanup_init() // {{{
		{
		} // }}}
		/**
		 * called by the template to do any of the DB heavy lifting.
		 *
		 * if a module is going to get data from some source,
		 * it should always do it here.  we should NOT be retrieving
		 * or munging data from other methods.
		 *
		 * @param array $args This is param is kind of a legacy thing; it does not appear to
		 *                    be used much, if at all
		 */
		function init( $args = array() ) // {{{
		{
		} // }}}
		/**
		 * called when editing is available for any specific editable needs
		 * @param object $session
		 * @deprecated since reason 4 beta 8
		 */
		function init_editable( $session ) // {{{
		{
			trigger_error('init_editable is deprecated and no longer called by the default template.');
			$this->session = $session;
		} // }}}
		/**
		 * returns a boolean value determining whether someone can edit the page or not. 
		 *
		 * this method is for performing
		 * checks on authentication if they need to be made.  for instance, this 
		 * is where you would check to see if the
		 * logged in user is related to the page with an authentication relationship.
		 * it could even be used to perform
		 * special actions for a module that doesn't use the usual method of authentication.
		 *
		 * @return boolean
		 * @deprecated since reason 4 beta 8
		 */
		function can_edit() // {{{
		{
			trigger_error('can_edit is deprecated and no longer called by the default template.');
			return false;
		} // }}}
		/**
		 * returns a boolean that reports on whether the module will have any content to display
		 *
		 * @return boolean
		 */
		function has_content() // {{{
		{
			return true;
		} // }}}
		/**
		 * Gets the cleanup rules so that inputs can be validated before being set
		 *
		 * basic version simply returns the variable.  more complicated actions can be done here if needed
		 * @return array
		 */
		function get_cleanup_rules() // {{{
		{
			return $this->cleanup_rules;
		} // }}}
		/**
		 * @return array
		 */
		function get_noncanonical_request_keys()
		{
			return $this->noncanonical_request_keys;
		}
		/**
 		 *
		 * the basic run function to display this module.
		 *
		 * this is called when the template is in non-editing mode
		 */
		function run() // {{{
		{
			echo 'I am the default minisite frontend module<br />';
		} // }}}
		
		/**
		 * We implement an api "standalone" which just runs the run method and outputs the content.
		 */
		function run_api()
		{
			$api = $this->get_api();
			if ($api->get_name() == 'standalone')
			{
				ob_start();
				$this->run();
				$content = ob_get_contents();
				ob_end_clean();
				$api->set_content($content);
			}
			$api->run();
		}
		
		/**
		 * displays the editable version of the page.
		 *
		 * there is a good chance that this method will handle all states of
		 * an editable form
		 *
		 * @deprecated since reason 4 beta 8
		 */
		function run_editable()
		{
			trigger_error('run_editable is deprecated and no longer called by the default template.');
			$this->run();
		}
		
		/**
		 * Get the datetime of the most recently modified displayed item
		 *
		 * used by the template to determine the most recent modification time
		 *
		 * @return mixed false if not available; otherwise return a string containing a MySQL-formatted datetime
		 */
		function last_modified() // {{{
		{
			return false;
		} // }}}
		/**
		 * Get (x)HTML documentation of the module
		 * @return mixed null if no documentation available, string if available
		 */
		function get_documentation()
		{
			return '';
		}
		
		/**
		 * Get sample module output
		 * @return mixed null if no sample output available, string if available
		 */
		function get_sample_output()
		{
			return '';
		}
		
		function page_is_public()
		{
			return $this->page_is_public;
		}
	}

?>
