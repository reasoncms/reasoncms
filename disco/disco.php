<?php
/**
 * @package disco
 */
	/**
	 * Include the plasmature widgets and other dependencies
	 */
	include_once( 'paths.php');
	include_once( CARL_UTIL_INC . 'dev/pray.php' );
	include_once( CARL_UTIL_INC . 'basic/misc.php' );
	include_once( CARL_UTIL_INC . 'basic/cleanup_funcs.php' );
	include_once( CARL_UTIL_INC . 'dev/debug.php' );
	include_once( CARL_UTIL_INC . 'error_handler/error_handler.php' );
	include_once( DISCO_INC . 'plasmature/plasmature.php' );
	include_once( DISCO_INC . 'boxes/boxes.php' );
	
	include_once( 'element_group.php');
	
	/**
	 * Wrap a string in a div, with classes indicating that it is a comment on a form element
	 * @param string $comment
	 * @return string
	 */
	function form_comment($comment)  // {{{
	{
		return ("<div class=\"formComment smallText\">$comment</div>");
	} // }}}
	
	
	/*
	*
		Overview
		Disco is an object-oriented form handler. Each field of the form is represented by a {@link Plasmature} object (also referred to as an "element") 
		that knows its name, its current value, how it should be displayed, and other information about its capabilities.  Plasmature objects
		can also represent markup other than input fields that should be displayed in the form, such as text or <hr> elements.  Markup from the Plasmature
		elements is organized for display within the form using a {@link Box} class.  
	
		Element Groups
		Plasmature elements can be grouped together within a Disco form using an {@link ElementGroup} object.  This is used mainly for display purposes: for
		example, to display several plasmature elements on the same line or as part of a table.  This is different from grouping together several HTML
		<input> elements together, as some Plasmature objects do.  A Plasmature object that uses several HTML <input> elements stores the values from these 
		elements as a single value and behaves like a single field in the form as far as Disco is concerned.  In contrast, members of element groups store 
		their own value and retain all the capabilities of normal Plasmature elements.
	
		Error Handling in Disco
		
		
		Elements and Element Groups in Disco.
		
	
		@author Dave Hendler
		@author Meg Gibbs
		@author Matt Ryan
		@package disco
	*/
	
	 /**
	 * The Disco Form Management Class.
	 *
	 * Fairly sophisticated form manager.
	 * Handles:
	 * - maintaining state in a form
	 * - checking required fields
	 * - simple class structure and hooks to facilitate extension
	 * - error checking cycle (enter data, check for errors, repeat)
	 *
	 * ODisco is a more object oriented Disco system.  All elements are actually objects that know
	 * about themselves and their capabilities.  There is a standard interface to the object
	 * so Disco knows that each object will definitely, for example, have a grab() method.
	 * 
	 * 2005-02-03 - Major change to some internals in progress.  I'm removing all references to $_REQUEST and instead using
	 * an internal copy of whatever is passed to Disco, $this->_request.  Adding a $this->set_request( $r ) method.
	 * 
 	 * @author Dave Hendler
	 * @package disco
	 */	  
	class Disco //{{{
	{
		/**#@+
		 * @access public
		 */
		/**
		 * Array of elements the form will use.
		 *
		 * Form of the array: the elements array can take on a number of forms.  The most basic is simply a list of
		 * names of elements.
		 *
		 * <code>
		 * var $elements = array(
		 *    'name',
		 *    'number',
		 *    'shoe_size',
		 * );
		 * </code>
		 *
		 * In this form, each element defaults to being a simple text input.  If you want to alter the type of widget
		 * for a particular field, you can use another simple form like so:
		 * <code>
		 * var $elements = array(
		 *    'name',
		 *    'description' => 'textarea',
		 *    'event_start' => 'datetime',
		 * );
		 * </code>
		 *
		 * In that examples, name is a regular text input, description will be a textarea, and event_start will be a
		 * specialized field for capturing date times.  These input types are actually Plasmature classes.  So, textarea
		 * actually refers to the textareaType class, the event_start refers to the datetimeType, and so on.  Even more
		 * information can be passed in the elements array if the plasmature types are set up to take parameters, like
		 * so...
		 *
		 * <code>
		 * var $elements = array(
		 *    'name',
		 *    'description' => array(
		 *        'type' => 'textarea',
		 *        'cols' => 80,
		 *        'rows' => 10,
		 *    ),
		 * );
		 * </code>
		 *
		 * Here, we're saying that description is a textarea (notice that it uses the 'type' key - that's important and
		 * necessary) and then we define 'rows' and 'cols' for the textarea.  Those parameters are specially handled in
		 * the plasmature object.  To know what's available, the plasmture class needs to be consulted.  Generally,
		 * those parameters are either set up in the classes' init() method or the object's options.
		 *
		 * Often times, if the plasmature types are powerful enough, a form can almost be entirely powered by the fields
		 * it uses.  Those objects can define error checks, allow many options, and take care of a lot of necessary
		 * tasks.
		 *
		 * Traditionally, the elements array is modified in an extension of Disco.  This isn't necessary though.  There
		 * are a number of methods that can add and change elements at runtime.  You could even instantiate a new Disco
		 * object, set the public elements array, and then init and run the form.
		 *
		 * Also note that the elements array is not used internal to Disco.  It is only the definition of the elements.
		 * Disco has its own internal storage and representation for the elements.  In fact, it generally isn't useful
		 * to do much anything with $elements since the format is so variable.
		 *
		 * @var array
		 */
		var $elements;
		/**
		 * Which fields are required.
		 *
		 * This is a simple array containing which fields are required.  This is generally set up once.
		 * <code>
		 * var $required = array(
		 *    'name',
		 *    'description',
		 * );
		 * </code>
		 *
		 * @var array
		 */
		var $required = array();
		/**
		 * Array of error checks to run
		 *
		 * Define error checks to run.  The array maps fields to methods or functions.  The functions simply return true
		 * or false based on the value of the field.  True means that the value is valid.  False means that the value
		 * does not meet the criteria defined in the function.  When these are run, the current class is first checked
		 * to see if a method with the name exists.  Otherwise, Disco checks all defined functions to see if the
		 * function exists.
		 *
		 * <code>
		 * var $error_checks = array(
		 *    'name' => array(
		 *        'is_name' => 'Name must be a valid name',
		 *    ),
		 *    'username' => array(
		 *        'is_username' => 'Not a valid username.  They must be alphanumeric',
		 *        'username_already_exists' => 'the username you entered has already been taken.  Please choose another.',
		 *    ),
		 * );
		 * </code>
		 *
		 * @todo document more
		 *
		 * @var array
		 */
		var $error_checks;
		/**
		 * Method the form should use.
		 * This should be set to either 'get' or 'post'.
		 * get is almost never used for Disco forms, but it could be used that way.
		 * @var string
		 */
		var $form_method = 'post';
		/**
		 * The form action.
		 * This is the URL the form posts to. Populated by default with the current URL, but you can 
		 * set it manually if you have a special need.
		 * @var string
		 */
		var $form_action;
		/**
		 * The encoding type.
		 * Default handles most forms.  Use 'multipart/form-data' for forms that need to upload something.
		 * @var string
		 */
		var $form_enctype = 'application/x-www-form-urlencoded';
		/**
		* The form id.
		* Since there can only be one Disco form per page, the default should generally suffice.
		* @var string
		*/
		var $form_id = 'disco_form';
		/** 
		* The form name.
		* Can be used to address the form via JavaScript. Since there can only be one Disco form per page, the default 
 		* should generally suffice.
		* @var string
		*/
		var $form_name = 'disco_form'; 
		
		/** Any additional attributes needed on the form element.
		* An array of strings that is joined together when the form is built
        * @var array
        */
		var $additional_form_attributes= array();
		/**
		* The array of buttons/actions for this form.
		* Key = internal name of button, value = Name to display on the button
		* <code>
		* $actions = array(
		*    'save' => 'Save Your Order',
		*    'cancel' => 'Cancel Your Order',
		* );
		* </code>
		*
		* The internal name will later be available in {@link chosen_action}
		* @var array
		*/
		var $actions = array('Save');
		/**
		* Will contain the (button pressed|action chosen) if there was one.
		* This variable is populated in {@link init()}
		* @var string
		*/
		var $chosen_action = '';
		/**
		* Class name of the box object to use.
		* This is kind of ancient and there is only one box class currently.
		* @var string
		*/
		var $box_class = 'Box';
		/**
		* Controls display of the form on this run.
		* Any part of the form can decide that the form should not be shown.  this was added 11/18/04 by DH to address
		* the issue of a form that needs to display different possible messages after a succesful submission but does
		* *not* bounce to another page.  it's easier to keep the logic in the form rather than having to pass some
		* status code to another page to determine what to show.
		* @var bool
		*/
		var $show_form = true;
		/**
		* Header text above error messages.
		* @var string
		*/
		var $error_header_text = 'Your form has errors';
		/**
		* Toggle links to form elements after error messages
		* @var bool
		*/
		var $show_error_jumps = true;
		/**
		* Toggle to strip tags from the values of elements.
		* {@link allowable_HTML_tags} can be used to allow specific HTML tags.
		* @var boolean
		* @see get_value()
		*/
		var $strip_tags_from_user_input = false;
		/**
		* Tags that are allowed in user input.
		* If {@link $strip_tags_from_user_input} is true, this value will be used as the second argument of strip_tags().
		*
		* If a single set of tags works for this form, provide a string here -- e.g. '<em><strong>'
		*
		* If you need to allow different sets of tags for different fields, provide an array keyed on element name,
		* using the key "default_tags" for any fields not defined, -- e.g. 'array('default_tags'=>'<em><strong>','name'=>'')
		*
		* You can allow all tags by using the keyword 'all' instead of a list of tags.
		*
		* Note that it is easier to interact with the methods {@link set_allowable_html_tags()} and {@link get_allowable_html_tags()] than to work with this variable directly.
		*
		* @var mixed
		* @see get_value()
		* @see set_allowable_html_tags()
		* @see get_allowable_html_tags()
		*/
		var $allowable_HTML_tags = '';
		/**
		* Controls level of error triggering
		* If true, disco triggers errors when sorting, removing, or getting values from elements that don't exist. 
		* @var boolean
		*/
		var $full_error_triggers = false;

		/**#@-*/
		
		/**#@+
		 * @access private
		 */ 
		 /**
		  * Is this the first time the form has been generated?
		  *
		  * Set to false in the init phase if there is any data posted to form.
		  * 
		  * @var boolean
		  */
		 var $_first_time = true;
		 /**
		 * The internal array of plasmature elements.
		 * This is where the actual plasmature objects are stored. 
		 * Format: $element_name => $element_object
		 * @var array
		 */
		 var $_elements;
		 /**
		 * The internal array of element groups.
		 * This is where the actual element group objects are stored.
		 * Format: $element_group_name => $element_group_object.
		 * @var array
		 */
		 var $_element_groups = array();
		 /**
		 * Stores the names of elements that have been made a part of an element group.
		 * Maps element names to the names of the groups that they are part of.
		 * Format: $element_name => $element_group_name.
		 * @var array
		 */
		 var $_elements_in_groups = array();
		 
		 /**
		 *  Names of the elements and element groups in the order in which they will appear on the form.
		 *  This should NEVER be overloaded.  Use {@link set_order()} to set a different order.
		 *  Use {@link get_order()} to access this array.
		 *  Format: $name => $name
		 *  @var array
		 */
		 var $_order = array();
		 
		/**
		* True if the form has any errors at all.
		* @var boolean
		*/
		var $_error_flag;
		/**
		* Maps the names of elements and element groups to a boolean that is true if that element
		* or element group has an error from a custom error check.
		* Format $element_name => boolean
		* @var array
		*/
		var $_errors;
		/**
		* Stores the names of required elements and element groups that have an error.
		* This array does not store the names of elements that have errors from custom error checks, just required
		* elements that don't have a value. 
		*
		* Format $element_name => $element_name
		* @var array
		*/
		var $_error_required;
		/**
		* Stores error messages for custom error checks.
		* Format $element_name => $error_message
		* @var array
		*/
		var $_error_messages;

		var $_request;
		var $_form_started = false;
		var $_ignored_fields = array();
		/**
		* True if errors should be written into an error log.
		* @var boolean
		*/
		var $_log_errors = false;
		var $_disco_log_date_format = 'r';
		var $_hidden_element_ordering = 'bottom';
		/**#@-*/
		
		/**
		* Element names that may not be used because Disco needs them
		* @var array
		*/		
		var $_reserved = array('submitted');
		
		/**
		 * Stores the callbacks registered on a Disco object
		 * @var array Keys are process points, and values are arrays of php callbacks
		 * @access private
		 */
		var $_callbacks = array(
				'init'=>array(),
				'on_first_time'=>array(),
				'on_every_time'=>array(),
				'pre_show_form'=>array(),
				'post_show_form'=>array(),
				'no_show_form'=>array(),
				'pre_error_check_actions'=>array(),
				'run_error_checks'=>array(),
				'post_error_check_actions'=>array(),
				'post_error_checks'=>array(),
				'process'=>array(),
				'where_to'=>array(),
		);
		
		
	//////////////////////////////////////////////////
	// PUBLIC METHODS
	//////////////////////////////////////////////////
		/**
		 * Run the form
		 * This is really just a collection function to run all the main phases of the form.
		 * @access public
		 */
		function run() // {{{
		// the main function.  called to do this thang
		{
			$this->init();
			
			$this->run_load_phase();
			
			$this->run_process_phase();
			
			$this->run_display_phase();
		} // }}}
		
		
		/**
		* Initializes Disco.
		* This method handles most of the startup and prepares the object to be run.  It converts the {@link elements}
		* array into a usable internal format containing all the plasmature elements as well as checking the variables
		* that the programmer has set.
		* @access public
		*/
		function init($externally_set_up = false) // {{{
		{
			// make sure init() only gets run once.  This allows a script to init the form before running.
			if ( !isset( $this->_inited ) OR empty( $this->_inited ))
			{
				// are we first timing it?
				if( empty($this->_request) ) 
					$this->_request = conditional_stripslashes($_REQUEST);
				$HTTP_VARS = $this->_request;
				$this->_first_time = (isset( $HTTP_VARS[ 'submitted' ] ) AND !empty( $HTTP_VARS[ 'submitted' ] )) ? false : true;
				
				// determine action that was chosen
				$this->chosen_action = '';
				foreach( $HTTP_VARS AS $key => $val )
				{
					if( preg_match( '/__button_/' , $key ) )
						$this->chosen_action = preg_replace( '/__button_/' , '' , $key );
				}
				
				if (empty($this->form_action))
					$this->form_action = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES);
				
				// elements should not be empty
				/* if ( !$externally_set_up )
				{
					if(( !isset( $this->elements ) OR empty( $this->elements ) ) AND empty( $this->_elements ))
					trigger_error( 'Your form needs to have some elements.  Create an elements array. (Ex: var $elements = array( \'item_one\', \'item_two\'); )' );
				} */
				
				/*if ( !isset( $this->required ) OR !is_array( $this->required ) )
					$this->required = array();*/
				
				// initialize values
				$this->_error_required = array();
				$this->_error_messages = array();
				$this->_error_flag = false;

				// make the internal variables match the elements from the overloaded class
				if ( !empty( $this->elements ) )
				{
					foreach($this->elements as $key => $value)
					{
						// assume no extra arguments
						$args = array();
						if ( is_string( $key ) )
						{
							$element_name = $key;
							if ( is_array( $value ) )
							{
								$type = $value['type'];
								unset($value['type']);
								$args = $value;
							}
							else
								$type = $value;
						}
						else
						{
							$element_name = $value;
							$type = '';
						}
						$this->add_element( $element_name, $type, $args );
					}
				}

				// required should only contain elements in the elements array
				foreach($this->required as $element_name)
				{
					if(!$this->_is_element($element_name) && !$this->_is_element_group($element_name))
						trigger_error('The field '.$element_name.' is present in your required fields, but it is not a recognized element or element group.' );
				}
				$this->_run_callbacks('init');
				$this->_inited = true;
			}
		} // }}}
		
	//////////////////////////////////////////////////
	// LOAD PHASE METHODS
	//////////////////////////////////////////////////
		
		/**
		* Run the load phase
		* Hooks available: {@link on_first_time()}, {@link on_every_time()}, {@link pre_error_check_actions()}
		* @access public
		*/
		function run_load_phase() // {{{
		{
			if ( $this->_is_first_time() )
			{
				$this->on_first_time();
				$this->_run_callbacks('on_first_time');
			}
			
			$this->on_every_time();
			$this->_run_callbacks('on_every_time');
			
			if ( !$this->_is_first_time() )
			{
				$this->_grab_messages();
			}
			
			$this->pre_error_check_actions();
			$this->_run_callbacks('pre_error_check_actions');
			
			//make sure that the element groups have updated references to the disco elements
			/*this won't be necessary if we ever stop replacing elements in $this->_elements every time that 
			  we modify them, but for now this seems like the safest way to make sure that the element groups
			  reference the most recent versions of the elements */
			foreach($this->_element_groups as $group_name => $group)
			{
				$member_element_names = $group->get_element_names();
				foreach($member_element_names as $name)
				{
					$this->_element_groups[$group_name]->update_element($this->_elements[$name]);
				}
			}
			
		} // }}}
		
		/**
		 * Hook for actions that happen the first time the form is loaded.
		 * For example, set up some defaults that are hard or impossible to set up using element declaration.
		 * Called by {@link run_load_phase}.
		 */
		function on_first_time() // {{{
		{
		} // }}}
		/**
		 * Hook for actions that should happen every time a form page is loaded.  
		 * This includes the first time, when errors occur, and on the final process run.
		 * Called by {@link run_load_phase}.
		 */
		function on_every_time() // {{{
		{
		} // }}}
		
		/**
		* Grabs all data from the user.  
		* Defers to the plasmature type's grab method.
		* Called by {@link run_load_phase}.
		* @access private
		*/
		function _grab_messages() // {{{
		{
			$element_names = $this->get_element_names();
			
			foreach($element_names as $element_name)
			{
				//make sure you modify the element itself rather than replacing it with a copy
				//otherwise, the elements in element groups won't be modified
				$element =& $this->_elements[$element_name];
				$element->set_request( $this->_request );
				$element->grab();
			}
		} // }}}
		
		/**
		* Hook for actions that should happen before the error checks are run but AFTER data from the user has been
		* received.  
		* This allows the programmer to automatically munge user data before error checks are run on it.
		* This can be pretty useful.  This has traditionally been used for
		* features like a 'back' button that require that no error checks happen that interrupt the flow of the form or
		* saving the data that was entered into a session before errors are checked.
		* Called by {@link run_load_phase}.
		*/
		function pre_error_check_actions() // {{{
		{
		} // }}}


	//////////////////////////////////////////////////
	// PROCESS PHASE METHODS
	//////////////////////////////////////////////////		
		/**
		* Run process phase of the form (error checks, saving, clean up, transition)
		* Hooks: {@link run_error_checks()}, {@link process}, {@link where_to}, {@link post_error_check_actions()}
		* @access public
		*/
		function run_process_phase() // {{{
		{
			if ( !$this->_is_first_time() )
			{
				$this->_run_all_error_checks();
				
				if ( !$this->_has_errors() )
				{
					$this->process();
					$this->_run_callbacks('process');
					$kludge = $this->finish();
					$this->handle_transition( $kludge );
				}
			}
			$this->post_error_check_actions();
			$this->_run_callbacks('post_error_check_actions');
		} // }}}
		
		/**
		* Runs all required error checks, defined checks, and user checks.
		* Called by {@link run_process_phase()}.
		* @access private
		*/
		function _run_all_error_checks() // {{{
		{
			//make sure all elements in $required have a value
			$this->_check_required();
			
			//iterates through the custom error checks defined in $_error_checks
			$this->_run_defined_error_checks();
	
			//Check each plasmature object for errors.
			$element_names = $this->get_element_names();
			foreach($element_names as $name)
			{
				if(!$this->has_error($name))
				{
					$element_object = $this->get_element($name);
					if($element_object->has_error)
						$this->set_error( $name, $element_object->error_message );
				}
			}
			
			//call on hooks for custom error checks.
			$this->run_error_checks();
			$this->_run_callbacks('run_error_checks');
		} // }}}
		
		/**
		* Does the error checking and setting for required elements and element groups.
		* Called by {@link _run_all_error_checks()}.  Part of the process phase.
		* @access private
		*/
		function _check_required() // {{{
		{			
			foreach($this->required as $element_name)
			{
				$element_has_error = false;
				
				//if this is an element group, check to make sure that each member element has a value
				//if any of them does not, the entire group has an error			
				if($this->_is_element_group($element_name))
				{
					$member_names = $this->get_names_of_member_elements($element_name);
					foreach($member_names as $member_name)
					{
						$value = $this->get_value($member_name);
						if ( $this->_is_element($member_name) AND !$value AND (!strlen($value) > 0))
						{
							$element_has_error = true;
							break; 
						} 
					} 
				}
				//if this is a normal element, check to make sure that it has a value.
				elseif( $this->_is_element($element_name) )
				{
					$value = $this->get_value($element_name);
					if(!$value AND (!strlen($value) > 0))
						$element_has_error = true;
				}
				
				if($element_has_error)
				{
					$this->_error_required[ $element_name ] = $element_name;
					$this->_error_flag = true;
					$this->_log_error( $element_name, '', 'Required' );
				}
			}  
		} // }}}
		
		/**
  	    * Run the error checks that were defined in {@link error_checks} by the user.
		* Called by {@link _run_all_error_checks()}.  Part of the process phase.
	    * @access private
		*/
		function _run_defined_error_checks() // {{{
		{
			if( !empty( $this->error_checks ) )
			{
				foreach( $this->error_checks AS $el => $checks )
				{
					//check to make sure that this doesn't already have an error
					if( !$this->has_error( $el ) )
					{
						foreach( $checks AS $func => $msg )
						{
							$check_passed = false;
							$val = $this->get_value( $el );
							if( method_exists( $this, $func ) )
								$check_passed = $this->$func( $val );
							elseif( function_exists( $func ) )
								$check_passed = $func( $val );
							else
								trigger_error( 'Unable to find "'.$func.'" error check' );
							if( !$check_passed )
							{
								$this->set_error( $el, $msg );
								break;
							}
						}
					}
				}
			}
		} // }}}
		
		/**
		* Hook for user defined error checks.  
		* Called at the same time as the automated requirements checking.
		* Called by {@link _run_all_error_checks()}.
		*/
		function run_error_checks() // {{{
		{
		} // }}}
		
		/**
		* Hook for the process phase.  
		* This is where actions should happen based on the validated data from the form.
		* Generally used for database storage, file writing, or whatever.
		* Called by {@link run_process_phase()}.
		*/
		function process() // {{{
		{
		} // }}}
		/**
		 * Old hook for end of form actions.  Do not use anymore.
		 * @deprecated use process() instead
		 * Called by {@link run_process_phase()}.
		 */
		function finish() // {{{
		{
		} // }}}
		
		/**
		* Handles the transition from the end of the form to wherever or whatever the form should go/do.
		* Called by {@link run_process_phase()}.  Available hook: {@link where_to()}.
		* @param string $kludge kludge variables to handle the old finish method
		* @access public
		*/
		function handle_transition( $kludge ) // {{{
		{
			$where_to = $this->where_to();
			if($callback_where_tos = $this->_run_callbacks('where_to'))
			{
				$where_to = array_pop($callback_where_tos);
			}
			elseif ( !$where_to )
				$where_to = $kludge;
			
			if ( $where_to )
			{
				if ( !$where_to OR !is_string( $where_to ) )
				{
					$where_to = conditional_stripslashes($_SERVER['REQUEST_URI']);
				}
				if(function_exists('is_developer') && is_developer())
				{
					$errors = carl_util_get_error_list();
					if(!empty($errors))
					{
						echo '<h3>PHP errors were encountered during the processing of this form.</h3>'."\n";
						echo '<p>Because you are accessing this form from an IP address listed as a developer\'s, rather than redirecting your browser to the next step (which would hide the errors) the form is pausing to let you see the errors displayed above.</p>'."\n";
						echo '<p><a href="'.htmlspecialchars($where_to).'">Thanks for the info; continue on.</a></p>';
						exit;
					}
				}
				header( 'Location: '.$where_to );
				exit;
			}
		} // }}}
		
		/**
		 * Hook for determining what should happen once a form is finished.
		 * Called by {@link handle_transition}.  Part of the process phase.
		 * @return string URL to bounce to or false if nothing should happen
		 */
		function where_to() // {{{
		{
			return false;
		} // }}}
		
		
		/**
		 * Hook for actions that should occur after error checking and before processing.
		 * Called by {@link run_process_phase}.
		 */
		function post_error_check_actions() // {{{
		{
		} // }}}
		
		
	//////////////////////////////////////////////////
	// DISPLAY PHASE METHODS
	//////////////////////////////////////////////////		
		
		/**
		* Run display phase of the form
		* Available hooks: {@link pre_show_form()}, {@link post_show_form}
		* @access public
		*/
		function run_display_phase() // {{{
		{
			if( $this->show_form )
			{
				//show custom content before the form begins
				echo $this->pre_show_form();
				foreach($this->_run_callbacks('pre_show_form') as $str)
				{
					echo $str;
				}
				//show the form itself
				$this->show_form();
				//show custom content after the form buttons
				echo $this->post_show_form();
				foreach($this->_run_callbacks('post_show_form') as $str)
				{
					echo $str;
				}
			}
			else
			{
				echo $this->no_show_form();
				foreach($this->_run_callbacks('no_show_form') as $str)
				{
					echo $str;
				}
			}
			
		} // }}}
		
		/**
		* Hook for having custom content or HTML before the form
		* Called by {@link run_display_phase}.
		* @return string
		*/
		function pre_show_form() // {{{
		{
			return '';
		} // }}}
		
	 	/**
		* Hook for having custom content or HTML when the form is not displayed
		* Called by {@link run_display_phase}.
		* @return string
		*/
		function no_show_form()
		{
			return '';
		}
		
		/**
		* Display all elements in the form as well as buttons, form tags, and all else.
		* Iterates through the elements and displays them.  Each show_ method call then in
		* turns calls plasmatures's display() method.
		* Called by {@link run_display_phase}.
		*/
		function show_form() // {{{
		{
			$this->start_form();
			$box_object = new $this->box_class;

			$order = $this->get_order();
			$hidden_elements = array();
			
			if($this->_hidden_element_ordering != 'inline' && $this->_hidden_element_ordering != 'top' && $this->_hidden_element_ordering != 'bottom')
			{
				trigger_error('Hidden element ordering (set to "'.$this->_hidden_element_ordering.'") must be set to one of "inline", "top", or "bottom". Resetting to default (bottom)');
				$this->_hidden_element_ordering = 'bottom';
			}
			
			if($this->_hidden_element_ordering != 'inline')
			{
				foreach($order as $key=>$name)
				{
					if( $this->_is_element($name) && $this->element_is_hidden($name) )
					{
						$hidden_elements[] = $name;
						unset($order[$key]);
					}
				}
			}
			//pray($hidden_elements);
			
			if($this->_hidden_element_ordering == 'top')
			{
				$this->_show_elements($hidden_elements, $box_object);
			}
			
			$box_object->head();
			
			$this->_show_elements($order, $box_object);
			
			$actions = $this->make_buttons( $this->actions );
			
			$box_object->foot( $actions );
			
			if($this->_hidden_element_ordering == 'bottom')
			{
				$this->_show_elements($hidden_elements, $box_object);
			}
			$this->close_form();
		} // }}}
		
		function _show_elements($names, &$box_object)
		{
			foreach($names as $name)
			{
				$this->_show_element($name, $box_object);
			}
		}
		
		function _show_element($name, &$box_object)
		{
			if($this->_is_element($name))
			{
				
				if($this->element_is_hidden($name))
					$this->show_hidden_element( $name, $this->get_element($name), $box_object );
				elseif(!$this->element_is_labeled($name))
					$this->show_unlabeled_element( $name, $this->get_element($name), $box_object );
				else
					$this->show_normal_element($name, $this->get_element($name), $box_object );
			}
			elseif($this->_is_element_group($name))
			{
				$this->show_element_group($name, $this->get_element_group($name), $box_object );
			}
			else
			{
				trigger_error($name.' is not a defined plasmature element or element group', WARNING);
			}
		}
		
		/**
		* Handles the form tag and error display
		* Called by {@link show_form}.  Part of the display phase.
		*/
		function start_form() // {{{
		{
			if( !$this->_form_started )
			{
				$this->show_errors();
				$this->_form_started = true;
				$markup = '<form method="'.$this->form_method.'" ';
				$markup .= 'action="'.$this->form_action.'" ';
				$markup .= 'enctype="'.$this->form_enctype.'" ';
				$markup .= 'id="'.$this->form_id.'" ';
				$markup .= 'name="'.$this->form_name.'" ';
				if(!empty($this->additional_form_attributes))
				{
					$markup .= ' '.implode(' ',$this->additional_form_attributes);
				}
				$markup .= '>'."\n";
				echo $markup;
			}
		} // }}}
		
		/**
		* Display errors in a nice list.  
		* Usually used internally in {@link show_form()}.  Part of the display phase.
		*/
		function show_errors() // {{{
		{
			if( ( count( $this->_error_required ) > 0 ) || ( count( $this->_error_messages ) > 0 ) )
			{
				echo '<div id="discoErrorNotice">'."\n";
				echo '<h3 style="color:red;">'.$this->error_header_text.'</h3>'."\n";
				// count required errors
				echo '<ul>'."\n";
				//display the messages for required elements
				if ( count( $this->_error_required ) > 0 )
				{
					$num_errors = count( $this->_error_required );
					$err_fields = array();

					foreach( $this->_error_required AS $name )
					{						
						$err_fields[] = '<a href="#'.$name.'_error">'.$this->get_display_name($name,false).'</a>';
					}
					echo '<li>'.($num_errors == 1 ? 'This field is' : 'These fields are' ).' required: '.join(', ',$err_fields).'</li>'."\n";
				}
				// display the messages for custom error checks - iterate through $_order so that error messages occur in a sort of logical order.
				$order = $this->get_order();
				foreach($order as $name)
				{
					echo $this->get_error_message_and_link($name);
					if($this->_is_element_group($name))
					{
						$member_names = $this->get_names_of_member_elements($name);
						foreach($member_names as $member_name)
						{
							echo $this->get_error_message_and_link($member_name);
						}
					}
				} 
				echo '</ul>'."\n";
				echo '</div>'."\n";
			}
		} // }}}
		
		function get_error_message_and_link($name)
		{
			$str = '';		
			if(!empty($this->_error_messages[$name]))
			{
				$error_message = $this->_error_messages[$name];
				$str .= '<li>'.$error_message.'  ';
				if( $this->show_error_jumps )
					$str .= '<a href="#'.$name.'_error" class="errorJump">[ jump to error ]</a>';
				$str .= '</li>'."\n";
			}
			return $str;
		}
		
		
		/**
		* Print the HTML for a normal element
	    * Called by {@link show_form}.  Part of the display phase.
		* @param string $element_name Name of the element
		* @param defaultType $element The actual element object
		* @param mixed $b box class (passed by reference)
		*/
		function show_normal_element( $element_name , $element , &$b) // {{{
		{
			$b->row_open( $this->get_display_name($element_name), 
						  $this->is_required( $element_name ), 
						  $this->has_error( $element_name ), 
						  $element_name, 
						  $this->get_element_property($element_name, 'use_display_name') );
			
			// drop in a named anchor for error jumping
			echo '<a name="'.$element_name.'_error"></a>'."\n";
			
			// show the comments that were placed above the element
			$element->echo_comments('before');

			// always display element
			$element->display();
			
			// show the comments that were placed below the element
			$element->echo_comments();
			
			$b->row_close();
		} // }}}
		
		/**
		* Print the HTML for a hidden element.
		* Essentially does not display the label and table code.
	    * Called by {@link show_form}.  Part of the display phase.
		* @param string $key Name of the element
		* @param defaultType $element The actual element object
		* @param mixed $b box class (passed by reference)
		*/
		function show_hidden_element( $key , $element , &$b) // {{{
		{
			// drop in a named anchor for error jumping
			echo '<a name="'.$key.'_error"></a>'."\n";

			// always display element
			$element->display();
			echo "\n\n";
		} // }}}
		
		/**
		 * Print the HTML for a spanning element (deprecated)
		 * @param string $key Name of the element
		 * @param defaultType $element The actual element object
		 * @param mixed $b box class (passed by reference)
		 * @deprecated Replaced by {@link show_unlabeled_element}
		 */
		function show_text_span_element( $key , $element , &$b) // {{{
		{
			trigger_error('show_text_span_element() is deprecated. Please use show_unlabeled_element() instead');
			$this->show_unlabeled_element( $key , $element , $b);
		} // }}}
		
		/**
		 * Print the HTML for an element with no label
		 *
		 * Replaces {@link show_text_span_element}
		 *
		 * Called by {@link show_form}.  Part of the display phase
		 * @param string $key Name of the element
		 * @param defaultType $element The actual element object
		 * @param mixed $b box class (passed by reference)
		 * 
		 */
		function show_unlabeled_element( $key , $element , &$b) // {{{
		{
			$anchor = '<a name="'.$key.'_error"></a>';
			ob_start();
			$element->display();
			$display = ob_get_contents();
			ob_end_clean();
			$content = $anchor."\n".$element->get_comments('before').$display.$element->get_comments()."\n";
			
			$b->box_item_no_label( $content, $this->has_error($key), $key );
		} // }}}
		
		/**
		 * Print the HTML for an element group
		 * Called by {@link show_form}.  Part of the display phase.
		 * @param string $element_group_name Name of the element group
		 * @param defaultType $element The actual element group object
		 * @param mixed $b box class (passed by reference)
		 */
		function show_element_group ($element_group_name, $element_group, &$b)
		{
			//check to see if group is required
			if($this->is_required($element_group_name))
				$group_is_required = true;		
			else
				$group_is_required = $element_group->is_required();
			
			//check to see if the group or any member of the group has an error
			if($this->has_error($element_group_name))
				$group_has_error = true;
			else
			{
				$group_has_error = false;
				foreach($element_group->elements as $element_name => $element)
				{
					if($this->has_error($element_name))
					{
						$group_has_error = true;
						break;
					}
				}
			}
			
			if($element_group->span_columns)
			{
				// drop in a named anchor for error jumping
				$anchor = '<a name="'.$element_group_name.'_error"></a>';
			
				//if we're spanning columns, we'll print the display name as a comment above the element group
				if($element_group->has_display_name())
				{
					$name = $element_group->get_display_name();
					if($group_is_required)
						$name .= '*';
					$b->box_item_no_label( $anchor.prettify_string($name),$this->has_error($element_group_name), $element_group_name.'_label' );
					$anchor = '';
				}
				
				$content = $anchor."\n".$element_group->get_display()."\n".$element_group->get_comments()."\n";
				$b->box_item_no_label($content, $this->has_error($element_group_name), $element_group_name);
			}
			else
			{
				$name = $element_group->get_display_name();
				$b->row_open( prettify_string($name), $group_is_required, $group_has_error, $element_group_name );	
				// drop in a named anchor for error jumping
				echo '<a name="'.$element_group_name.'_error"></a>'."\n";
				$element_group->display();
				echo "\n";
				$element_group->echo_comments();
				echo "\n";
				$b->row_close();
			}
		}
		
		/**
		 * Creates the button arrays for the form.
		 * Translates the $actions array into the proper form for Disco to understand - basically just prepends
		 * '__button_' to the name of the action to avoid name conflict with other elements.
		 * Called by {@link show_form}.  Part of the display phase
		 * @param array $a array of actions
		 * @returns array suitable array of buttons for Disco
		 */
		function make_buttons( $a ) // {{{
		{
			if( !is_array( $a ) )
				$a = array();
			$b = array();
			foreach( $a AS $k => $v )
				$b[ '__button_' . $k ] = $v;
			return $b;
		} // }}}
		
		/**
		* Closes the form and includes the necessary "submitted" input.
		* Called by {@link show_form}.  Part of the display phase.
		*/
		function close_form() // {{{
		{
			echo '<input type="hidden" name="submitted" value="true" />'."\n".'</form>'."\n";
		} // }}}
		
		/**
		* Hook to add any HTML or text or anything you want after the form
		* Called by {@link run_display_phase}.
		* @return string
		*/
		function post_show_form() // {{{
		{
			return '';
		} // }}}
		
	//////////////////////////////////////////////////
	// METHODS FOR ELEMENTS & ELEMENT GROUPS
	//////////////////////////////////////////////////		
		/**
		* Adds an element or element group to the list of required elements
		* @param string $name Name of the element or element group
 		* @access public
		*/
		function add_required( $name ) // {{{
		{
			$this->required[] = $name;
		} // }}}
		
		/**
		* Removes an element or element group from the {@link required} array.
		* @access public
		*/
		function remove_required( $name ) // {{{
		{
			$key = array_search($name, $this->required);
			if ($key !== FALSE) unset( $this->required[ $key ] );
			else
			{
				if ($this->full_error_triggers) trigger_error('Cannot remove element from required array; element '.$name.' is not present in required array');
			}
		} // }}}
		
		/**
		* Set the display name of the element or element group.
		* The display name is the actual label that will be shown on the form.
		* @param $element string Name of element or element group
		* @param $value string Display name to be used on the form
		*/
		function set_display_name( $element, $value ) // {{{
		{
			if ( $this->_is_element( $element ) )
			{
				$el =& $this->_elements[$element];
				$el->set_display_name( $value );
			}
			elseif ($this->_is_element_group($element) )
			{
				$eg =& $this->_element_groups[$element];
				$eg->set_display_name( $value );
			}
			else 
				trigger_error( $element.' is not a defined element or element group', WARNING );
		} // }}}
		
		/**
		 * Get the display name for a given element or element group
		 * @param string $element_name
		 * @param boolean $empty_ok
		 * @return string
		 */
		public function get_display_name($element_name,$empty_ok = true)
		{
			$display_name = prettify_string($element_name);
						
			//find the real display name, if one exists.
			if($this->_is_element($element_name))
			{
				$element_object = $this->get_element($element_name);
				$element_display_name = trim($element_object->display_name);
				if($empty_ok || !empty($element_display_name))
					$display_name = prettify_string($element_display_name);
			}
			elseif($this->_is_element_group($element_name))
			{
				$element_group_object = $this->get_element_group($element_name);
				$group_display_name = trim($element_group_object->display_name);
				if($empty_ok || !empty($group_display_name))
					$display_name = prettify_string($group_display_name);
			}
			
			return $display_name;
		}
		
		/**
		* Set comments on an element or on an element group.  
		* Generally form_comment() is used to format the string.
		* @param string $element Name of the element or element group
		* @param string $value Comments for the field.
		* @param string $position 'before' or 'after'
		*/
		function set_comments( $element_name, $value, $position = 'after' ) // {{{
		{
			if ( $this->_is_element( $element_name ))
			{
				$el =& $this->_elements[$element_name];
				$el->set_comments( $value, $position );
			}
			elseif( $this->_is_element_group ($element_name) )
			{
				$element_group =& $this->_element_groups[$element_name];
				$element_group->set_comments( $value, $position );
			}
			else 
				trigger_error( 'Could not set comments on element '.$element_name.'; .'.$element_name.' is not a defined element or element group.' );
		} // }}}
		
		/**
		* Determines whether or not an element or an element group has an error.
		* Used for both determining whether or not to show an element with error messages as well as allowing cascading
		* error messages without overloading the user.  For example, if a field is required and you want to run your
		* own error check on it, the standard practice is to first check to see if the field already has an error
		* before checking and setting the error flag on the element.  This avoids having 2 error messages where one is
		* somewhat obvious from the first.
		* @param $name string Name of the element or element group
		* @return bool true if element has an error, false if not
		*/
		function has_error( $name ) // {{{
		{
			return (isset($this->_errors[ $name ]) AND $this->_errors[ $name ]) OR
				   (isset($this->_error_required[ $name ]) AND $this->_error_required[ $name ]);
		} // }}}
		

	
	//////////////////////////////////////////////////
	// METHODS FOR ELEMENTS ONLY
	//////////////////////////////////////////////////		
		/**
	    * Returns true if the given string is the name of a valid plasmature element in the form.
		* Note that this function does not recognize element groups as valid elements.  To check 
		* if something is an element group, use {@link _is_element_group}.
		* @param string $element_name Name of a potential field
		* @return bool true if this is the name of a valid element
		* @access private
		*/
		function _is_element( $element_name ) // {{{
		{
			if(isset( $this->_elements[ $element_name ] ))
				return true;
			else
				return false;
		} // }}}

		/**
	    * @param string $element_name Name of a potential field
		* @return bool true if this is the name of a valid element
		* @access public
		*/
		function is_element( $element_name )
		{
			return $this->_is_element($element_name);
		}
		
		/**
    	* Returns a copy of the plasmature element for a field.  
	    * Note that this function returns a COPY of the element -- do not use this to try to modify the element itself.
		* To modify an element, use {@link set_element_properties()} or {@link change_element_type()}.
		* To just get the value of a class variable of this element, use {@link get_element_property()}.
		* @param $name string name of the element
		* @return object The plasmature object itself
		*/
		function get_element( $name ) // {{{
		{
			if($this->_is_element($name))
				return $this->_elements[ $name ];
			else
				return false;
		} // }}}
		
		/**
		* Returns an array of all element names in the order that they appear, including the elements that are a part of element groups.
		* @return array Array of element names (as values in the array)
		*/
		function get_element_names() // {{{
		{
			$element_names = array();
			foreach($this->get_order() as $name)
			{
				if($this->_is_element($name))
				{
					$element_names[] = $name;
				}
				elseif($this->_is_element_group($name))
				{
					foreach($this->get_names_of_member_elements($name) as $member_name)
					{
						$element_names[] = $member_name;
					}
				}	
			}
			return $element_names;
		} // }}}
		
		function get_values()
		{
			$ret = array();
			foreach($this->get_element_names() as $name)
			{
				$ret[$name] = $this->get_value($name);
			}
			return($ret);
		}
	
		/**
	    * Adds a comment to the existing comments of an element or element group.
		* @param string $element Name of the element or element group
		* @param string $value Comments for the field.
		* @param string $position 'before' or 'after'
		*/
		function add_comments( $element, $value, $position = 'after' ) // {{{
		{
			if ( $this->_is_element( $element ) )
			{
				$el =& $this->_elements[$element];
				$el->add_comments( $value, $position );
			}
			else
				trigger_error('Cannot add comments to element '.$element.'; element is not defined.');
		} // }}}
	
		
		/**
		* Change an existing element to an element of a different type.
		* This should only be used when you want to actually alter the type of the element; if you want to change
		* other properties of the element, please use {@link set_element_properties}.  This function cannot be used to 
		* modify an element group, although it can (and should) be used to change the type of an element that's a member of 
		* an element group.
		*
		* @param string $element_name Name of the element to change
		* @param string $new_type Name of the plasmature type to change to
		* @param array $args Array of arguments for the plasmature type
		* @access public
		*
		*/
		function change_element_type( $element_name, $new_type = '', $args = array() ) // {{{
		// change an element's type at runtime
		{
			if($this->_is_element($element_name))
			{				
				if ( empty( $new_type ) )
					$new_type = 'textType';
				else
					$new_type = strtolower( $new_type ).'Type';
				
				if($this->is_plasmature_type($new_type))
				{	
					//get a copy of the current element of this name.
					$element_object = $this->get_element($element_name);
					if ( !is_object( $element_object ) )
						trigger_error( 'Cannot change_element_type: "'.$element_name.'" is not a defined element at this point' );
	
					//instantiate the new element object
					$new_element_object = new $new_type;
					
					//copy data from the old element object to the new element object
					$new_element_object->set_name( $element_name );
					$new_element_object->comments = $element_object->comments ;
					$new_element_object->display_name = $element_object->display_name ;
					$new_element_object->set_request($this->_request);
					
					$new_valid_args = $new_element_object->get_valid_args();
					
					$args_to_transfer = $element_object->get_args_to_transfer();
					
					foreach($element_object->get_valid_args() as $key => $arg_name)
					{
						//check to make sure we're not stomping on an arg passed by the user
						if(!isset($args[$arg_name]) )
						{
							//check sure that we won't trigger errors by trying to pass an arg that's not valid in the new element.
							if(in_array($arg_name, $new_valid_args) && (!is_string($key) || array_key_exists($key, $new_valid_args)))
							{
								$key_for_new_object = array_search($arg_name, $new_valid_args);
								//if the key is a string, then the $key is the name of the class var
								if(is_string($key) || is_string($key_for_new_object))
								{
									//make sure that the keys are the same - otherwise we're not dealing with the same var
									if($key_for_new_object === $key && isset($args_to_transfer[$key]) )
									{
										$args[$arg_name] = $args_to_transfer[$key];
									}
								}
								//usually, though, the arg_name is the name of the class var.
								elseif(isset($args_to_transfer[$arg_name]))
								{
									$args[$arg_name] = $args_to_transfer[$arg_name];
								}
							}
						}
					}
					
				/*	//this passed & clobbered variables without noticing whether or not they should be passed.
					foreach( $element_object AS $e_key => $e_data )
						if( $e_key != 'type' && $e_key != 'type_valid_args')  // ignore the type-specific data members
							$new_element_object->$e_key = $e_data;  */
					
					// re-init the object with the new arguments
					$new_element_object->init( $args );
					
					if( $new_element_object->register_fields() )
					{
						foreach( $new_element_object->register_fields() AS $field )
						{
							$this->_ignored_fields[] = $field;
						}
					}
					
					//set the value of the new element object
					$new_element_object->set($element_object->get());
					
					//replace the old element object with the new one.
					$this->_elements[ $element_name ] = $new_element_object;
					
					//since element groups rely on references to $_element array, update the reference to the element in the element group, if applicable.
					if($this->_element_is_in_group($element_name))
					{
						$group_name = $this->_elements_in_groups[$element_name];
						$this->_element_groups[$group_name]->update_element($this->_elements[$element_name]);
					}
				}
				else
					trigger_error('Could not change element '.$element_name.' to type '.$new_type.'; '.$new_type.' is not a valid plasmature type.');		
			}
			else
				trigger_error('Could not change element type for element '.$element_name.'; '.$element_name.' is not a defined element');
		} // }}}
		
		/**
		* Used internally to display a plasmature element
		* @param string $element Name of the element to display
		* @access public
		*/
		function display( $element_name ) // {{{
		{
			$element_object = $this->get_element($element_name);
			$element_object->display();
		} // }}}
		
		/**
		* Adds an element to the form.
		*
		* Note that not all strings can be valid element names, as element names are used as request
		* keys. See http://us.php.net/manual/en/language.variables.external.php#81080 for an
		* explanation of what constitutes valid and invalid request keys.
		*
		* @param string $element_name Name of the element
		* @param string $type Name of the Plasmature type to use
		* @param array $args Array of custom arguments/parameters for the plasmature type
		* @param string $db_type Database field type that this element corresponds to
		* @return boolean success -- may not add element if invalid name or plasmature type is specified
		* @access public
		*/
		function add_element( $element_name, $type = '', $args = '',$db_type = '' ) // {{{
		{
			if ($element_name != request_key_convert($element_name))
			{
				trigger_error('Use of an element name like "'.$element_name.'" is deprecated. Why? PHP mangles request keys with certain characters -- see http://us.php.net/manual/en/language.variables.external.php#81080 for more information.');
				// When we are confident that our identification is 100% accurate, we will reject. For now, let things through.
				// return false;
			}
			
			if ($this->_is_element($element_name))
			{
			//	trigger_error('An element named "'.$element_name.'" already exists on this form and will be overwritten.');
			}

			if (in_array($element_name, $this->_reserved))
			{
				trigger_error('The element name "'.$element_name.'" is reserved for internal Disco use.');
			}

			// convert the type to the corresponding plasmature type, defaulting to text
			if ( empty( $type ) )
				$type = 'textType';
			else
				$type = strtolower( $type ).'Type';

			//instantiate new plasmature object
			if($this->is_plasmature_type($type))
			{
				$element_object = new $type;
				$element_object->set_request( $this->_request );
				$element_object->set_name( $element_name );
				$element_object->set_db_type( $db_type );
				$element_object->init( $args );
				if( $element_object->register_fields() )
				{
					foreach( $element_object->register_fields() AS $field )
					{
						$this->_ignored_fields[] = $field;
					}
				}
				//store in the $_elements array
				$this->_elements[ $element_name ] = $element_object;
				//create an entry in the $_errors array
				$this->_errors[ $element_name ] = false;
				//add to the form order
				$this->_order[$element_name] = $element_name;
				return true;
			}
			else
			{
				trigger_error('Could not instantiate new element '.$element_name.'; '.$type.' is not a valid plasmature type.', WARNING);
				return false;
			}
		 } // }}}	 
		 
		/**
		* Completely removes an element from the form.
		* If you just don't want the element to be displayed in the form, use {@link change_element_type()} to
		* change the element type to hidden.
		* @param string $element_name Name of the element to be removed.
		* @access public
		*/
		function remove_element( $element_name ) // {{{
		{
			if($this->_is_element($element_name))
			{
				//remove the element from its group, if applicable.
				if($this->_element_is_in_group($element_name))
				{
					$this->remove_element_from_group($element_name);
				}
				//remove the element from the private element array
				if ( isset( $this->_elements[$element_name] ) )
					unset( $this->_elements[$element_name] );
				//remove the element from the public element array
				if( isset( $this->elements[$element_name] ) )
					unset( $this->elements[$element_name] );
				//remove the element from the errors array
				if( isset( $this->_errors[$element_name] ) )
					unset( $this->_errors[$element_name] );
				//remove the element from the order array
				if( isset( $this->_order[$element_name] ) )
					unset($this->_order[$element_name]);
				if( $this->is_required($element_name))
					$this->remove_required($element_name);
			}
			else
				if ($this->full_error_triggers) trigger_error('Cannot remove element; element '.$element_name.' is not defined');
		} // }}}
		
		/**
		* Returns the value of a property of a plasmature element.
		* @param string $element_name Name of the element
		* @param string $property_name Name of the plasmature element
		*/
		function get_element_property( $element_name, $property_name )
		{
			if($this->_is_element($element_name))
			{
				$element_object = $this->get_element($element_name);
				return $element_object->get_class_var($property_name);
			}
			else
			{
				trigger_error('Could not get element property; '.$element_name.' is not a defined plasmature element.');
			}
		}
		
		/**
		* Sets the values of one or more class variables of a plasmature element after that element has already been inited.
		* @param $element string Name of element
		* @param $args Array of properties to be set; $property_name => $property_val (same format as in init())
		*/
		function set_element_properties( $element_name, $args )
		{
			if($this->_is_element($element_name))
			{
				foreach($args as $property_name => $property_value)
				{
					$this->_elements[$element_name]->set_class_var($property_name, $property_value);
				}
			}
			else
				trigger_error('Cannot set properties for element '.$element_name.'; element is not defined', WARNING);
		}
		
		function set_element_option_order( $element_name, $order )
		{
			if($this->_is_element($element_name))
			{
				$this->_elements[$element_name]->set_option_order($order);
			}
			else
				trigger_error('Cannot set option order for element '.$element_name.'; element is not defined', WARNING);
		}
		
		function element_is_hidden($element_name)
		{
			if($this->_is_element($element_name))
			{
				$element_object = $this->get_element($element_name);
				return $element_object->is_hidden();
			}
			else
			{
				trigger_error('Could not determine whether element is hidden, as "'.$element_name.'" is not a defined form element.');
			}
		}
		
		function element_is_labeled($element_name)
		{
			if($this->_is_element($element_name))
			{
				$element_object = $this->get_element($element_name);
				return $element_object->is_labeled();
			}
			else
			{
				trigger_error('Could not determine whether element is labeled, as "'.$element_name.'" is not a defined form element.');
			}
		}
		
		/**
		* Retrieve an element's value.
		* This will also strip tags from the value of a field if {@link strip_tags_from_user_input} is true.
		* This will NOT retrieve the values of an element group. 
		* @see strip_tags_from_user_input
		* @see allowable_HTML_tags 
		* @param string $element_name 
		* @return string Value of element
		*/
		function get_value( $element_name ) // {{{
		{
			if($this->_is_element($element_name))
			{
				$element = $this->get_element($element_name);
				if( $element )
				{
					$value = $element->get();
					if(!empty($value) && $this->strip_tags_from_user_input)
						return $this->_strip_tags($value, $this->get_allowable_html_tags($element_name));
					else	
						return $value;
				}
				else 
					return false;
			}
			else
			{
				if ($this->full_error_triggers) trigger_error('Cannot get value of element '.$element_name.'; element is not defined');
				return false;
			}
		} // }}}
		
		/**
		* Retrieve a display version of an element's value.
		* Some elements store a value differently than they display it to the user; for example, a drop-down of countries
		* might store 'USA' but display 'United States of America' to the user. This function behaves like {@link get_value()},
		* but it returns the display value.
		* @param $element_name string Name of element
		* @return mixed Value of element, or array of the values of an element group.
		*/
		function get_value_for_display( $element_name ) // {{{
		{
			if($this->_is_element($element_name))
			{
				$element = $this->get_element($element_name);
				if( $element )
				{
					$value = $element->get_value_for_display();
					if(!empty($value) && $this->strip_tags_from_user_input)
						return $this->_strip_tags($value, $this->get_allowable_html_tags($element_name));
					else	
						return $value;
				}
			}
			else
			{
				if ($this->full_error_triggers) trigger_error('Cannot get value for display for element '.$element_name.'; element is not defined');
			}
			return false;
		} // }}}
		
		/**
		* A wrapper for strip_tags that understands special values for the second argument
		* and works recursively on arrays
		*
		* At the moment there is only one special value:
		* "all" will not strip any tags and will return the value as given
		*
		* @param mixed $value may be a string or mixed array of arrays and/or strings
		* @param string $allowable_tags
		* @return string stripped text
		*/
		function _strip_tags($value, $allowable_tags)
		{
			if($allowable_tags == 'all')
			{
				return $value;
			}
			elseif(is_array($value))
			{
				$return = array();
				foreach(array_keys($value) as $key)
				{
					$return[$key] = $this->_strip_tags($value[$key],$allowable_tags);
				}
				return $return;
			}
			else
			{
				return strip_tags($value, $allowable_tags);
			}
		}
		
		/**
		* Determine which html tags are allowed for a given element
		* @param string $element_name 
		* @return string allowed tags
		*/
		function get_allowable_html_tags($element_name)
		{
			if(is_string($this->allowable_HTML_tags))
			{
				return $this->allowable_HTML_tags;
			}
			elseif(isset($this->allowable_HTML_tags[$element_name]))
			{
				return $this->allowable_HTML_tags[$element_name];
			}
			elseif(isset($this->allowable_HTML_tags['default_tags']))
			{
				return $this->allowable_HTML_tags['default_tags'];
			}
			else
			{
				return '';
			}
		}
		
		/**
		* Set which html tags are allowed for a given element
		* @see allowable_HTML_tags
		* @see get_allowable_html_tags
		* @param string $element_name (use "default_tags" to set a default set for all fields that do not have allowable tags specifically defined)
		* @param string $tags (use this format: '<a><b><cite>'; use an empty string to allow no tags; use the string 'all' to turn tag-stripping off for a given field)
		* @return void
		*/
		function set_allowable_html_tags($element_name,$tags)
		{
			if(is_string($this->allowable_HTML_tags))
			{
				$default = $this->allowable_HTML_tags;
				$this->allowable_HTML_tags = array();
				$this->allowable_HTML_tags['default_tags'] = $default;
			}
			$this->allowable_HTML_tags[$element_name] = $tags;
		}
		
		/**
		* Set the value of an element in the form
		* @param $element string Name of element
		* @param $value mixed Value of element
		* @return void
		*/
		function set_value( $element, $value ) // {{{
		{
			if ( $this->_is_element( $element ) )
			{
				//make sure you modify the element object itself, don't replace it with a modified copy
				$this->_elements[$element]->set( $value );
			}
			else 
				trigger_error( "Element '$element' is not defined.", WARNING );
		} // }}}
		
		/**
		 * Return the name of the element that submitted the form
		 * @return string
		 */
		function get_chosen_action()
		{
			return $this->chosen_action;
		}
	//////////////////////////////////////////////////
	// METHODS FOR ELEMENT GROUPS ONLY
	//////////////////////////////////////////////////
		/**
		*  Adds a new element group to the form.
		*  @param string $type Type of element group to add (e.g. 'inline', 'table')
		*  @param string $group_name Name of the new element group
		*  @param array $elements Names of the elements to be added to the group (optional).  
		*  @param array $args Additional arguments for the element group (optional).
		*  @access public
		*/
		function add_element_group($type, $group_name, $elements = array(), $args = array() )
		{
			$type = 'Element'.$type;
			$element_group_object = new $type;
			$element_group_object->set_name( $group_name );
			$element_group_object->init($args);

			$this->_element_groups[$group_name] = $element_group_object;
			$this->_order[$group_name] = $group_name;
			
			foreach($elements as $element_name)
			{
				$this->add_element_to_group($element_name, $group_name);
			}
		}
		
		/**
		*  Adds an element to an element group.  
		*  This element must be a plasmature object that already exists in this form.
		*  @param string $element_name Name of the element to be added 
		*  @param string $group_name Name of the element group
		*  @access public
		*/
		function add_element_to_group($element_name, $group_name)
		{
			if($this->_is_element($element_name))
			{
				if($this->_is_element_group($group_name))
				{
					$args = array();
					$args['is_required'] = $this->is_required( $element_name );
					$args['has_error'] = $this->has_error( $element_name );
					$args['anchor'] = '<a name="'.$element_name.'_error"></a>';	
					
					//because we are passing by reference, it is VERY IMPORTANT that we pass the element from the _element array rather than a copy
					$this->_element_groups[$group_name]->add_element($this->_elements[$element_name], $args);
					$this->_elements_in_groups[$element_name] = $group_name;
					
					//remove this element from $_order, since it will be displayed with the element grup
					if(isset($this->_order[$element_name]))
						unset($this->_order[$element_name]);
				}
				else
					trigger_error('Cannot add element '.$element_name.' to group '.$group_name.', as '.$group_name.' is not a recognized element group', WARNING);
			}
			else
				trigger_error('Cannot add element '.$element_name.'; element is not a recognized plasmature element', WARNING);
		}  
		
		/**
		*  Removes an element from an element group.  
		*  @param string $element_name Name of the element to be removed 
		*  @param string $group_name Name of the element group
		*  @access public
		*/
		function remove_element_from_group($element_name, $group_name = '')
		{			
			if($this->_is_element($element_name))
			{
				if($this->_is_element_group($group_name))
				{
					if($this->_element_is_in_group($element_name, $group_name))
					{
						//remove element from group
						$this->_element_groups[$group_name]->remove_element($name);
						//add element back into order
						if(!isset($this->_order[$element_name]))
							$this->_order[$element_name] = $element_name;
					}
					else
						trigger_error('Cannot remove element '.$element_name.' from '.$group_name.', as '.$element_name.' is not a member of the element group '.$group_name, WARNING);
				}
				else
					trigger_error('Cannot remove element '.$element_name.' from group '.$group_name.', as '.$group_name.' is not a recognized element_group', WARNING);
			}
			else
				trigger_error('Cannot remove '.$element_name.' from group, as it is not a recognized plasmature element', WARNING);
		}
		
		
		/**
		* Returns the names of the elements that are a part of this element group.
		* @param string $group_name Name of the element group
		* @return array Names of the member elements
		*/
		function get_names_of_member_elements($group_name)
		{
			if($this->_is_element_group($group_name))
			{
				return $this->_element_groups[$group_name]->get_element_names();
			}
			else
			{
				trigger_error('Cannot get names of member elements from '.$group_name.' as it is not a recognized element group', WARNING);
				return false;
			}
		}
		
		/**
		* Determines if a string is the name of a valid element group in the form.
		* @param string $group_name Name of the element group
		* @return boolean true if string is the name of an element group
		* @access private
		*/
		function _is_element_group ($group_name)
		{
			if(!empty($group_name) && isset( $this->_element_groups[$group_name] ))
				return true;
			else
				return false;
		}

		/**
		* @param string $group_name Name of the element group
		* @return boolean true if string is the name of an element group
		* @access public
		*/
		function is_element_group ($group_name)
		{
			return $this->_is_element_group($group_name);
		}
		
		/**
		* Determines if an element is part of an element group.  
		* This will check to see if  an element is a part of a particular group if a group name is specified, but otherwise
		* will return true if the element is part of any element group.
		* @param string $element_name The name of the element
		* @param string $group_name The name of the element group (optional)
		* @return boolean true if element is a part of group
		* @access private
		*/
		function _element_is_in_group ($element_name, $group_name = '')
		{
			if(array_key_exists($element_name, $this->_elements_in_groups))
			{
				if(empty($group_name) || $this->_elements_in_groups[$element_name] == $group_name)
					return true;
			}
			return false;
		}
		
		/**
		*  Returns the name of the element group that the given element is a member of, or false
		*  if the element is not a member of an element group.
		*  @param string $element_name The name of the element
		*  @return string The name of the group that the element is a member of.
		*/
		function get_group_name_for_element($element_name)
		{
			if($this->_is_element($element_name))
			{
				if($this->_element_is_in_group($element_name))
				{
					return $this->_elements_in_groups[$element_name];
				}
			}
			else
				trigger_error('Cannot get group name for '.$element_name.', as '.$element_name.' is not a recognized element');
			return false;
		}
		
		/**
    	* Gets a copy of the element group object.  
	    * Note that this function returns a COPY of the element group -- do not use this to try to modify the element group itself.
		* @param $name string name of the element group
		* @return object The a copy of the element group
		*/
		function get_element_group( $name ) // {{{
		{
			if($this->_is_element_group($name))
				return $this->_element_groups[ $name ];
			else
				return false;
		} // }}}
		
		/**
		* Get an array of all element group names.
		* @return array Array of element names (as values in the array)
		*/
		function get_element_group_names() // {{{
		{
			return array_keys( $this->_element_groups );
		} // }}}
		
		
		//////////////////////////////////////////////////
		// ERROR-CHECKING METHODS
		//////////////////////////////////////////////////

		/**
		* Returns if the form has any errors at all
		* @return bool true if the form has errors.
		* @access private
		*/
		function _has_errors() // {{{
		{
			return $this->_error_flag;
		} // }}}
		
		/**
		* Returns if the form has any errors at all
		* @return bool true if the form has errors.
		* @access public
		*/
		function has_errors() // {{{
		{
			return $this->_has_errors();
		} // }}}
		
		/**
		* Do we have a successful submission?
		* @return bool false if not submitted or if the form has errors.
		* @access public
		*/
		function successfully_submitted() // {{{
		{
			if(!$this->_is_first_time() && !$this->_has_errors())
				return true;
			return false;
		} // }}}

		/**
		 * Logs an error to the error file if {@link _log_errors} is true.
		 * @param $el string name of the element
		 * @param $value mixed element's value
		 * @param $msg string The error message
		 * @access private
		 */
		function _log_error( $el, $value, $msg ) // {{{
		{
			if( $this->_log_errors )
			{
				$err = array();
				$parts = parse_url( get_current_url() );
				$err[] = $parts['host'];
				$err[] = $parts['path'];
				if (isset($parts['query'])) $err[] = $parts['query'];
				$err[] = get_class( $this );
				$err[] = $el;
				if( empty( $this->no_session ) OR !in_array( $el, $this->no_session ) )
				{
					if(is_array($value) || is_object($value))
						$err[] = serialize($value);
					else
						$err[] = $value;
				}
				else
					$err[] = '*** VALUE HIDDEN *** ('.strlen($value).' characters)';
				$err[] = $msg;
				$err[] = !empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
				$err[] = !empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
				$err[] = !empty( $_SERVER['REMOTE_USER'] ) ? $_SERVER['REMOTE_USER'] : '';
				$err[] = date($this->_disco_log_date_format);
				array_walk( $err, 'quote_walk' );
				$err_str = join( ',', $err );
				dlog( $err_str, '/tmp/disco_errors_'.$_SERVER['HTTP_HOST'] );
			}
		} // }}}
		
		/**
     	 * Determine if an element or an element group is required.
		 * @param $element_name string name of the element or element group
		 * @return bool whether the element is required or not
		 */
		function is_required( $element_name ) // {{{
		{
			if(in_array($element_name, $this->required))
				return true;
			else
				return false;
		} // }}}
		
		/**
		 * Add or set an error on an element or element group.
		 * @param $element_name string Name of the element
		 * @param $message string The message to display to the user
		 */
		function set_error( $element_name, $message = '') // {{{
		{
			if($this->_is_element($element_name) || $this->_is_element_group($element_name))
			{
				//set the error flag for the entire form to true
				if ( empty( $this->_error_flag ) )
					$this->_error_flag = true;
				
				//if this doesn't already have an error because it was required
				if ( empty( $this->_error_required[ $element_name ] ) )
				{
					//and if it doesn't have an error from a custom error check
					if ( empty( $this->_errors[ $element_name ] ) )
					{
						//let disco know that this element has an error from acustom error check
						$this->_errors[ $element_name ] = true;
						//store the error message, if we've got one
						if ( $message )
						{
							$this->_error_messages[ $element_name ] = $message;
						}
						//log the error ... won't actually happen if {@link _log_errors} isn't true.
						if($this->_is_element($element_name))
							$this->_log_error( $element_name, $this->get_value($element_name), $message);
						elseif($this->_is_element_group($element_name))
						{
							$member_element_names = $this->get_names_of_member_elements($element_name);
							$values = array();
							foreach($member_element_names as $member_name)
							{
								$values = $this->get_value($member_name);
							}
							//it might not be a great idea to give this an array .... maybe a implode into a string?
							$this->_log_error( $element_name, $values, $message);
						}
					}
				}
			}
			else
				trigger_error('Cannot set error, as '.$element_name.' is not a recognized element or element group', WARNING);
		} // }}}
		
	//////////////////////////////////////////////////
	// MISC. METHODS
	//////////////////////////////////////////////////				
		
		/**
		* Determines whether or not this is the first time the form has run.
		* @return bool true if this s the first time.
		* @access private
		*/
		function _is_first_time() // {{{
		{
			return $this->_first_time;
		} // }}}

		/**
		 * Stop execution in case of a major internal error
		 * @deprecated use trigger_error() instead
		 * @access private
		 */
		function _internal_error( $msg ) // {{{
		{
			trigger_error('DISCO ERROR: '.$msg, FATAL);
			//die( '<strong>DISCO ERROR:</strong> '.$msg );
		} // }}}			
				
		/**
		* Add attributes to the form element
		* @param $attr in form 'foo="bar"'. Must be already xhtml encoded.
		* @access public
		*/
		function add_additional_attribute($attr)
		{
			$this->additional_form_attributes[] = $attr;
		}
		
		/**
		* spit out all information about the object
		* @access public
		*/
		function debug() // {{{
		{
			echo '<pre>';
			reset( $this );
			pray( $this );
			echo '</pre>';
		} // }}}
					
		/**
		* Checks to see if the string given is the name of a plasmature type.
		* @param string $type_name 
		* @return boolean True if $type_name is a class and a child of the default plasmature class.
		*/			
		function is_plasmature_type($type_name)
		{
		 	if(class_exists($type_name))
			{
				$test = new $type_name;
				if(is_subclass_of($test, 'defaulttype'))
					return true;
			}
			else
				return false;
		 }			
					
		/**
		* Used to change box class.  Essentially useless.
		* @param $bc string Name of box class
		*/
		function set_form_class( $bc ) // {{{
		{
			$this->box_class = $bc;
		} // }}}
		
		/**
		* Set the actions
		* @param array $actions Array of action in format of internal @link{actions} array
		*/
		function set_actions( $actions ) // {{{
		{
			$this->actions = $actions;
		} // }}}
		
		
		/**
		*  Returns the names of elements and element groups in the order that they should be displayed.
		*  Elements that are members of element groups will not appear in this array, since they will be displayed as part of the element group.
		*  To get an array that just contains the names of elements (including elements that are members of element groups), use {@link get_element_names()}.
		*  @return array Names of the elements and element groups in the order that they should appear.
		*/
		function get_order()
		{
			return $this->_order;
		}
				
		/**
		* Sets display order of elements and element groups.
		* The display order is set by default to the order in which elements and element groups were added to the form, 
		* but that's often pretty arbitrary.  Note that any elements or element groups which are not included in the 
		* the parameter array will appear in arbirary order after the set order.e array.  Fields not in the array will appear at the end of the form
		* in arbitrary order.
		* @param array $order Array of element and element group names in the order you want them to appear.
		*/
		function set_order( $order ) // {{{
		{
			if( is_array( $order ) )
			{
				$old_order = $this->get_order();
				$new_order = array();
				
				foreach($order as $name)
				{
					if(($this->_is_element($name) && !$this->_element_is_in_group($name))|| $this->_is_element_group($name))
					{
						$new_order[$name] = $name;
						unset($old_order[$name]);
					}
					elseif($this->_element_is_in_group($name))
					{
						trigger_error($name.' is a member of an element group and cannot be a part of $_order', WARNING);
					}
					else
						if ($this->full_error_triggers) trigger_error('Cannot add '.$name.' to $_order; '.$name.' is not a recognized element or element group');
				}
				
				foreach($old_order as $name)
				{
					$new_order[$name] = $name;
				}
				
				$this->_order = $new_order;
			}
			else
				trigger_error('set_order() requires an array as a parameter', WARNING);
		} // }}}
		
		/**
		* Move a single element or element group to a new location.
		* Use set_order if you need to do wholesale rearranging.
		* @param string $element1 Name of element you're moving
		* @param string $where position relative to element2 ('before' or 'after')
		* @param string $element2 Name of element to move element1 next to
		*/
		function move_element( $element1, $where, $element2)
		{
			$old_order = $this->get_order();
			if (!$this->_is_element($element2) && !$this->_is_element_group($element2))
			{
				trigger_error($element2.' is a not an element that move_element() can locate', WARNING);				
				return;
			}
			if(($this->_is_element($element1) && !$this->_element_is_in_group($element1))|| $this->_is_element_group($element1))
			{
				foreach($old_order as $name)
				{
					if ($name == $element1) continue;
					if ($name == $element2)
					{ 
						if ($where == 'after')
						{
							$new_order[$name] = $element2;
							$new_order[$element1] = $element1;
						} else {
							$new_order[$element1] = $element1;
							$new_order[$name] = $element2;
						}
					} else {
						$new_order[$name] = $name;
					}
				}
				$this->_order = $new_order;
			}
			elseif($this->_element_is_in_group($element1))
			{
				trigger_error($element1.' is a member of an element group and cannot be moved', WARNING);
			}
			else
				if ($this->full_error_triggers) trigger_error('Cannot move '.$element1.'; '.$element1.' is not a recognized element or element group');
			
		}
		
		/**
		* Move a collection of elements or element groups to a new location.
		* Use set_order if you need to do wholesale rearranging.
		* @param array $elements Names of the elements you're moving, in the order you'd like them to appear
		* @param string $where position relative to element2 ('before' or 'after')
		* @param string $element2 Name of element to move elements with regard to
		*/
		function move_elements($elements, $where, $element2)
		{
			if (!is_array($elements) || empty($elements))
			{
				if ($this->full_error_triggers) trigger_error('move_elements called with empty or non-array value.');
				return false;
			}
			if ($where == 'before') $elements = array_reverse($elements);
			$last_element = $element2;
			foreach($elements as $element1)
			{
				$this->move_element($element1, $where, $last_element);
				$last_element = $element1;
			}
		}
		
		/**
		* Set the internal copy of the request.
		* This allows Disco to work with a filtered copy of any request vars.  Generally, this is simply set to
		* $_REQUEST or $_POST, but this can also be used to automatically test or provide a fake environment for Disco
		* to run under.
		* @param array $r Array of Request vars
		* @access public
		*/
		function set_request( $r ) // {{{
		{
			$this->_request = $r;
		} // }}}
	
		function show_simple_form() // {{{
		{
			echo '<pre>'.htmlentities( $show ).'</pre>';
		} // }}}
		
		/**
		 * Determine the appropriate plasmature type to use, given a database field definition
		 *
		 * Finds many common MySQL database field types; not tested significantly with other databases.
		 * Not guranteed to have 100% coverage of all field definitions; does not specify a plasmature
		 * type if it cannot divine one.
		 * 
		 * @param string $name the disco element name -- used mainly to ensure that ID fields are hidden
		 * @param string $db_type the SQL database type
		 * @param array $function_args An array of additional parameters.
		 * 
		 */
		function plasmature_type_from_db_type( $name, $db_type, $function_args = array('find_maxlength_of_text_field' => false, 'do_not_sort_enums' => false) ) // {{{
		{
			$args = array();
			// show correct form element based on field type from DB
			// ids are protected
			if ( $name == 'id' )
				$t = 'hidden';
			// date types
			else if( preg_match( '/^(timestamp|datetime)/i', $db_type ) )
				// at some point, make a make_datetime function
				$t = 'textDateTime';
			else if ( preg_match( '/^date/i', $db_type ) )
				$t = 'textDate'; 
			// timestamp or datetime
			// textarea types - big blobs
			else if( preg_match( '/^(text|blob|mediumblob|mediumtext|longblob|longtext)/i', $db_type ) )
				$t = 'textarea';
			
			// enumerated types - make a select
			else if ( preg_match( "/^enum\((.*)\).*$/", $db_type, $matches ) )
			{
				$esc_options = array();
				$options = array();
				$opts = array();
				if(!empty($function_args['do_not_sort_enums']))
					$t = 'select_no_sort';
				else
					$t = 'select';
				// explode on the commas
				$options = explode( ',', $matches[1] );
				// get rid of the single quotes at the beginning and end of the string
				// MySQL also escapes single quotes with single quotes, so if we see two single quotes, replace those two with one
				// also, make sure to not look at stupid whitespace.  hence the trim()
				foreach( $options as $key => $val )
					$esc_options[ $key ] = str_replace("''","'",substr( trim($val),1,-1 ));
				foreach( $esc_options as $val )
					$opts[ $val ] = $val;
				$args['options'] = $opts;
			}
			// default type
			else
			{
				$t = '';
				if(!empty($function_args['find_maxlength_of_text_field']))
				{
					//find size of field
					$maxlength = '';
					$exploded_db_type = explode('(', $db_type);
					if(!empty($exploded_db_type[1]))
						$maxlength = (int)rtrim($exploded_db_type[1], ')');					
					if(is_int($maxlength ) && !empty($maxlength ) && empty($args['size']))
						$args['size'] = $maxlength ;
					if(is_int($maxlength ) && !empty($maxlength ) && empty($args['maxlength']))
						$args['maxlength'] = $maxlength ;
				}
			}
			return array( $t, $args );
		} // }}}
		
		/**
		 * Select a method for Disco's handling of hidden fields
		 *
		 * Possible values for $order:
		 * - "top" -- places the hidden elements at the top of the form
		 * - "bottom" -- places the hidden elements at the bottom of the form
		 * - "inline" -- allows the hidden elements to appear intermixed with non-hidden elements.
		 * 
		 * The inline setting is not recommended for table-based disco forms, as it creates invalid
		 * (X)HTML.
		 *
		 * @param string $order
		 * @return boolean True if set; false if invalid $order is given
		 */
		function set_hidden_element_ordering($order)
		{
			switch($order)
			{
				case 'top':
				case 'bottom':
				case 'inline':
					$this->_hidden_element_ordering = $order;
					return true;
				default:
					trigger_error('Order value given not one of "top","bottom", or "inline". Unable to set the hidden element ordering method.');
					return false;
			}
		}
		
	//////////////////////////////////////////////////
	// CALLBACKS
	//////////////////////////////////////////////////	
		
		/**
		 * Register a callback with Disco
		 *
		 * As an alternative to (or in addition to) Disco's overloadable methods like 
		 * on_every_time() and process(), you can register callbacks that Disco will
		 * call when/if those points are reached.
		 *
		 * This allows you to run custom code on a disco form without having to extend Disco.
		 *
		 * Notes:
		 *
		 * Disco will submit a reference to itself as the first parameter of the callback.
		 *
		 * is_callable() is used to validate callbacks, so there may be valid callbacks 
		 * that don't work because they don't pass is_callable(). If you try to use a callback you
		 * know will work, please file an issue on Reason's issue tracker.
		 *
		 * You can register any number of callbacks for a given process point (though see the note 
		 * below regarding where_to.) Callbacks are run in the order they were added.
		 * 
		 * Most process points, similarly to the functions they augment/replace, do not require a 
		 * return value from the callback. There are four notable exceptions to this rule:
		 * 
		 * "pre_show_form", "post_show_form", and "no_show_form" should return a string for Disco 
		 * to echo in the appropriate locations
		 *
		 * "where_to" should return a URL that Disco will use to send a redirect header. Please note
		 * that if there are multiple "where_to" callbacks attached to the form, disco will use the
		 * last one.
		 *
		 * Example code (note that the ampersands are not required in php5, but are included here
		 * for backwards compatibility with php4):
		 *
		 * <code>
		 * function add_foo_element(&$disco)
		 * {
		 * 		$disco->add_element('foo','text');
		 * }
		 * 
		 * class where_to
		 * {
		 * 		var $_url = 'http://example.com/';
		 * 		function get_url(&$disco)
		 * 		{
		 * 			return $this->_url;
		 * 		}
		 * }
		 *
		 * $d = new disco();
		 * $d->add_callback('add_foo_element', 'on_every_time');
		 * $whereto = new where_to();
		 * $d->add_callback(array(&$whereto,'get_url'), 'where_to');
		 * $d->run();
		 * </code>
		 *
		 * @param callback $callback A callback to the function or method 
		 * @param string $process_point The point where Disco should run the callback. Valid
		 * process points include all the keys of @_callbacks.
		 * @access public
		 * @author Matt Ryan
		 * @todo Should callbacks be able to be removed?
		 * 
		 */
		function add_callback($callback, $process_point)
		{
			if(!isset($this->_callbacks[$process_point]))
			{
				trigger_error($process_point.' is not a valid disco callback attachment point');
				return false;
			}
			$this->_callbacks[$process_point][] = $callback;
			return true;
		}
		
		/**
		 * Run a callback for a given process point
		 * 
		 * @param string $process_point
		 * @return mixed false if empty, invalid or non-callable process point; otherwise the return value of the callback
		 * @access private
		 * @author Matt Ryan
		 */
		function _run_callbacks($process_point)
		{
			$return_values = array();
			if(!isset($this->_callbacks[$process_point]))
			{
				trigger_error('The process point "'.$process_point.'" is invalid. Valid process points include: '.implode(', ',array_keys($this->_callbacks)));
				return false;
			}
			if(!empty($this->_callbacks[$process_point]))
			{
				foreach(array_keys($this->_callbacks[$process_point]) as $key)
				{
					if(is_callable($this->_callbacks[$process_point][$key]))
					{
						$return_values[] = call_user_func_array($this->_callbacks[$process_point][$key],array(&$this));
					}
					else
					{
						trigger_error('Callback for process point "'.$process_point.'", #'.$key.' is not callable');
					}
				}
			}
			return $return_values;
		}
		
		/**
		 * Set the method used for the form
		 *
		 * @param string $method "get" or "post" (lower-case only).
		 * @return boolean false if an unrecognized method
		 * @access public
		 */
		function set_form_method($method)
		{
			if('get' == $method || 'post' == $method)
			{
				$this->form_method = $method;
				return true;
			}
			else
			{
				trigger_error('Form method must be "get" or "post". Note that this method is case-sensitive.');
				return false;
			}
		}
	} // }}}

?>
