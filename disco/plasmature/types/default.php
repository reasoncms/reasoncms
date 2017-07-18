<?php

/**
 * Contains Plasmature's {@link defaultType default type}.
 * @package disco
 * @subpackage plasmature
 */

/**
 * The base class for all plasmature element types.
 *
 * Plasmature elements are objects that represent part of a form, usually an
 * input field. Each plasmature object stores its * own value and metadata,
 * knows how to display itself, and knows how to grab its value from a
 * submitted form.
 *
 * To instantiate a plasmature element:
 *   - Give the element a unique name using {@link set_name()}.
 *   - Pass the element a copy of the $_REQUEST array using
 *     {@link set_request()}.
 *   - Instantiate variables and perform other necessary initialization actions
 *     by calling {@link init()}.
 *
 * Valid Arguments & Setting Class Variables
 *   - Class variables should always be set using {@link set_class_var()} by
 *     including them in the $args parameter of the {@link init()} method, 
 *     or by using a setter method for that variable.
 *   - Not all class variables may be set using the set_class_var and init
 *     methods. Class variables need to be designated as a valid argument by
 *     being included in _valid_args array in order to be set using these
 *     methods. This is done to protect the values of variables that are
 *     inherent to the type; please do not directly set variables in order to 
 *     get around this unless you know what you're doing.
 *      
 * To set the value of a plasmature element:
 *   - Calling on {@link grab()} will cause the element to find & set its own
 *     value from the {@link request} array.
 *   - You can also use {@link set($value)} to set the value of the element to
 *     $value.
 * 
 * Displaying a plasmature element:
 *   - {@link get_display()} will return the markup for the element; {@link
 *     display()} will echo the element markup.
 *   - Display names/element labels are not usually included in the display
 *     methods. (It would be good to standardize this so that they NEVER are.).
 *     Make sure you don't forget to echo the {@link display_name}.
 *   - Comments are also not included in the display methods. Use {@link
 *     get_comments} to return comment markup or {@link echo_comments} to echo
 *     comment markup.
 *  
 * @package disco
 * @subpackage plasmature
 *
 * @todo This class is currently set up to be a fully functioning type that's
 *       basically the same as {@link textType}, * but it should probably be an
 *       abstract class. If it becomes an abstract class, it should be renamed
 *       so that * it doesn't include 'Type' in its name so that it can't
 *       possibly be used as an elementType. It would be nice * to give it a
 *       more descriptive name, too - defaultPlasmature or just plasmature or
 *       something.
 */
class defaultType
{
	/////////////////
	//  VARIABLES
	/////////////////
	/** 
	 * The type of plasmature object that this class represents. This string +
	 * 'Type' should be the class name.
	 * @var string
	 */
	var $type = 'default';
	/** 
	 * The unique name of this instance of this plasmature class - matches the element identifier in disco.
	 * @var string
	 */
	var $name;
	/** 
	 * The value of this plasmature element.
	 * @var string
	 */
	var $id;
	/** 
	 * The html id this plasmature element.
	 * @var string
	 */
	var $value;
	/** 
	 * The database field type that the value of this plasmature element will be stored in.
	 * This is used in error checking to make sure that values are not too long, wrong format, etc.
	 * @var string
	 */
	var $db_type;
	/** 
	 * Internal copy of the $_REQUEST array. 
	 * This is what the plasmature element uses to find its value in the grab() method. 
	 * It should be passed from the Disco element using the set_request() method.
	 * @var array
	 */
	var $_request;
	/** 
	 * Flag to indicate whether or not the $_REQUEST array has been passed from Disco.
	 * @var boolean
	 */
	var $request_has_been_set = false;
	/** 
	 * Flag for internal plasmature errors (independent of Disco errors).
	 * @var boolean
	 */
	var $has_error = false;
	
	/** 
	 * Message for internal plasmature errors (independent of Disco error messages).
	 * @var string
	 */
	var $error_message;
	
	/** 
	 * The name of this element as it will be displayed on the form itself. 
	 * If this is empty or not set, it will default to be the prettified version of $name. 
	 * If you don't want a display name to appear on the form, set display name to ' ' OR 
	 * (preferred) set {@link use_display_name} to false.
	 * @var string
	 */
	var $display_name;
	
	/**
	 * True if the display name should be displayed next to the element on the form.
	 * This allows the element to still have a display name that can be used to refer to the element
	 * in error messages, &tc, but Disco won't automatically show the display name next to the <input> element.
	 * @var boolean
	 */
	var $use_display_name = true;
	
	/** 
	 * Comments to be displayed after this element.
	 * @var string
	 */
	var $comments;
	
	/** 
	 * Comments to be displayed before this element.
	 * @var string
	 */
	var $comments_pre;
	
	/** 
	 * Contains the names of properties that may be externally set using the parameter $args for the {@link init()} function
	 * or by the {@link set_class_var()} method.
	 * This array should be accessed using {@link get_valid_args()) so that type-specific valid args can be added to it.
	 * This array  should NOT be overloaded; to add additional valid args to a child class, overload {@link type_valid_args}. 
	 * Class variables cannot be set using {@link init()} or {@link set_class_var()} unless they are listed in this array.
	 * Usually the name of each valid argument should be the same as the class variable it corresponds to.. If it is different, set the key 
	 * of the array to the name of the class variable and the value to the name of the argument. Please do not create new valid arguments
	 * that have a different name than their corresponding class var - this functionality only exists for historical purposees.
	 * @access protected
	 * @var array
	 */
	var $_valid_args = array( 'display_name',
							  'comments',
							  'comments_pre',
							  'value' =>'default',
							  'db_type',
							  'display_style', // deprecated
							  'trim_input',
							  );
							  
	/**
	 * Contains the names of properties specific to  this type that may be externally set using the parameter $args for the {@link init()}
	 * function or by the {@link set_class_var()} method.
	 * This array should be overloaded by child classes to add type-specific valid args. It will be merged together with all parent 
	 * type_valid_args and added to {@link _valid_args}. The same formatting rules as {@link _valid_args} apply.
	 * @var array
	 */
	var $type_valid_args = array();
	
	/**
	 *  True if all the valid args specific  to this type have been added to the main {@link _valid_args}  array.
	 * @var boolean
	 */
	var $_valid_args_inited = false;
		
	/**
	 * Indicates whether the plasmature element is hidden.
	 *
	 * Access via _is_hidden() method
	 *
	 * @var boolean
	 * @access protected
	 */
	var $_hidden = false;
		
	/**
	 * Indicates whether the plasmature element should have a label
	 *
	 * Access via _is_labeled() method
	 *
	 * @var boolean
	 * @access protected
	 */
	var $_labeled = true;
	
	/**
	 * A record of which args have been set (as opposed to simply having their default state)
	 *
	 * @var array
	 */
	protected $_set_args = array();
	
	protected $trim_input = true;
	

	/////////////////////////
	//  INIT-RELATED METHODS
	/////////////////////////
	
	/** 
	 *  Initializes a new plasmature object.
	 *  Includes necessary files, sets variables specified in $args, and performs any additional actions specified in {@link additional_init_actions()}.
	 *  This method should NOT be overloaded in child classes; use {@link do_includes()} and {@link additional_init_actions()} for 
	 *  for any additional actions. Otherwise the big scary Disco Revenge monster will come after you.
	 *  Available hooks: {@link do_includes()}, {@link additional_init_actions()}	
	 *  @param array $args the array of arguments - e.g., for a text box, array( 'size' => 40, 'maxlength' => '100' )
	 */
	function init( $args = array() )
	{			
		$this->do_includes();
		
		if ( empty( $args ) )
			$args = array();
		
		foreach($args as $var_name => $var_value)
		{
			$this->set_class_var($var_name, $var_value);
		}
		
		$this->additional_init_actions($args);
	}
	
	/**
	 *  Hook to include any files necessary for a particular plasmature class.
	 *  Called at the start of {@link init()}.
	 */
	function do_includes()
	{
		// overload this function
	}
	
	/**
	 *  Hook for any custom actions that should take place in {@link init}.
	 *  Called at the end of {@link init()}.
	 */
	function additional_init_actions($args = array())
	{
		//overload this function
	}
	
	//////////////////////////////////////////
	//  METHODS TO SET THE VALUE OF THE OBJECT
	/////////////////////////////////////////
	
	/**
	 * Finds the value of this element from userland using {@link grab_value()} and sets the value using {@link set()}
	 * @return void
	*@todo Give this method a more descriptive/intuitive name?
	 */
	function grab()
	{
		$val = $this->grab_value();
		if($val !== NULL)
		{
			$this->set( $val );
		}
	}
	
	/**
	 * Finds the value of this element from userland (in {@link _request}) and returns it
	 * @return mixed array, integer, or string if available, otherwise NULL if no value from userland
	 */
	function grab_value()
	{
		$http_vars = $this->get_request();
		if ( isset( $http_vars[ $this->name ] ) )
		{
			if( !is_array( $http_vars[ $this->name ] ) AND !is_object( $http_vars[ $this->name ] ) )
			{
				if($this->trim_input)
					return trim($http_vars[ $this->name ]);
				else
					return $http_vars[ $this->name ];
			}
			else
				return $http_vars[ $this->name ];
		}
		return NULL;
	}
	
	/**
	 * Sets the value of this element.
	 * @param mixed $value Value of this element.
	 */
	function set( $value )
	{
		$this->value = $value;
	}
	
	
	//////////////////////////////////////////
	//  DISPLAY METHODS
	/////////////////////////////////////////
	
	/**
	 * Returns the markup for this element.
	 * @return string HTML to display this element.
	 */
	function get_display()
	{
		$str  = '<input type="text" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" size="50" class="default" />';
		return $str;
	}
	
	/**
	 *  Gets the markup for this element using {@link get_display()} and prints it.
	 */
	function display()
	{
		echo $this->get_display();
	}
	
	/**
	 * Returns the markup for the comments for this element.
	 * @return string HTML to display the comments for this element.
	 */
	function get_comments($position = 'after')
	{
		if($position == 'before')
		{
			return $this->comments_pre;
		}
		else
		{
			return $this->comments;
		}
	}	
	
	/**
	 *  Gets the markup for this element's comments using {@link get_comments()} and prints them.
	 */
	function echo_comments($position = 'after')
	{
		if($position == 'before')
		{
			echo $this->comments_pre;
		}
		else
		{
			echo $this->comments;
		}
	}		
	
	//////////////////////////////////////////
	//  MISC. METHODS
	/////////////////////////////////////////

	/**
	 * Returns the value of this plasmature element.
	 * @return mixed The value of this element.
	 */ 
	function get()
	{
		return $this->value;
	}
	
	/** 
	 * Returns the value of this plasmature element as it should be displayed to the user.
	 * This should be overloaded for types that store a different value than what they display to the user 
	 * (e.g., select types often store something different from what is shown on the drop down). To be used
	 * when displaying stored info. 
	 * @return mixed The pretty version of this element's value.
	 */
	function get_value_for_display()
	{
		return $this->get();
	}	

# Not sure exactly what this is used for, so I won't document it. -- MG
	function db_get()
	{
		return addslashes($this->value);
	}
	
	/**
	 *  Returns the value of a class variable.
	 *  @param string $var_name Name of the class variable.
	 *  @return mixed Value of the class variable.
	 */
	function get_class_var($var_name)
	{
		if($this->is_class_var($var_name))
			return $this->$var_name;
		else
		{
			return false;
		}
	}
	
	/**
	 *  Returns true if the given string is the name of a class variable.
	 *  Note: This function should be deprecated once we upgrade to PHP 5 and can use property_exists()
	 *  @param string $var_name Name of the class variable
	 *  @return true if $var_name is the name of a variable for this class.
	 */
	function is_class_var($var_name)
	{
		$class_vars = get_class_vars(get_class($this));
		if(array_key_exists($var_name, $class_vars))
			return true;
		else
			return false;
	}
			
	/**
	 *  Sets a class variable of this element.
	 *  As a rule, plasmature class vars should not be set directly. Unless there is a special method to set a class var (
	 *  e.g., {@link set_comment()}, {@link set()}), this should ALWAYS be used to set plasmature class vars.
	 *  Note: this will only set class variables that are included in {@link _valid_args}. You can add additional class variables
	 *  in child classes to {@link _valid_args} by overloading {@link type_valid_args}.
	 *  @param string $var_name Name of the variable to be set.
	 *  @param string $var_value Value that the variable should be set to.
	 *	@todo make class vars protected so this is the only way to set them
	 */
	function set_class_var($var_name, $var_value)
	{
		$valid_args = $this->get_valid_args();
	
		//we don't need to check $_additional_valid_args because they should have already been added to the $_valid_args in init()
		if(in_array($var_name, $valid_args))
		{
			$key = array_search($var_name, $valid_args);
			
			//check to see if this arg has a string as a key; if it does, the key is the name of the actual class variable.
			//DO NOT make new valid args that have a different name than the class variable - we check this only for historical reasons. We don't actually like this behavior.
			if(is_string($key))
				$var_name = $key;
			
			$this->$var_name = $var_value;
			$this->_register_set_arg($var_name);
			return true;
		}
		else
		{
			trigger_error('Could not set class variable '.$var_name.'; '.$var_name.' is not a valid argument for the plasmature type '.$this->type, WARNING);
			return false;
		}
	}
	
	/**
	 *  Returns all valid arguments for this type, including the default valid args, the type-specific valid args, and 
	 *  the valid args for all parent classes.
	 */
	function get_valid_args()
	{
		if(!$this->_valid_args_inited)
		{
			//add the type-specific valid args to the valid args array
			$type_valid_args = $this->_get_valid_args_for_this_type();
			
			foreach($type_valid_args as $key => $arg_name)
			{
				if(!in_array($arg_name, $this->_valid_args))
				{
					if(is_string($key))
						$this->_valid_args[$key] = $arg_name;
					else
						$this->_valid_args[] = $arg_name;
				}
			}
			
			$this->_valid_args_inited = true;
		}
		return $this->_valid_args;
	}
	
	/**
	 * Get the names of the args that have been set on this element
	 * @return array format: array('argname1','argname2',...)
	 */
	function get_set_args()
	{
		return array_keys($this->_set_args);
	}
	
	/**
	 * Get the args that should be transferred if the element type is changed
	 *
	 * This method passes only those args that have been set on the element, not
	 * including the args that have simply retained their default value.
	 *
	 * Note that if even if an arg is set to its default value it will be included
	 * in this array -- what matters is the setting action.
	 *
	 * @return array format: array('argname1'=>'argval1','argname2'=>'argval2',...)
	 */
	function get_args_to_transfer()
	{
		$ret = array();
		foreach(array_keys($this->_set_args) as $arg_name)
		{
			$ret[$arg_name] = $this->get_class_var($arg_name);
		}
		return $ret;
	}
	
	/**
	 * Register an argument/class var as having been set.
	 * @param string $arg_name
	 * @return void
	 */
	protected function _register_set_arg($arg_name)
	{
		$this->_set_args[$arg_name] = 1;
	}
	
	/**
	 * Returns an array of the valid args specific to this type and to its parents.
	 * This function merges the {@link type_valid_args} of the current class with the {@link type_valid_args} 
	 * of all parent classes.  Do not overload this function unless you have special magical powers and know what you're doing.
	 * Helper function  to {@link get_valid_args()}.
	 * @return array The valid args specific to this type and to its parents.
	 * @access private
	 */
	function _get_valid_args_for_this_type()
	{
		$parent_class = get_parent_class($this);
		
		//if this is the default type, don't try to get type-specific valid args
		if(empty($parent_class))
			return array();
		//if this is an immediate child of the default type, we can just return $type_valid_args
		elseif($parent_class === 'defaulttype')
			return $this->type_valid_args;
		//otherwise, merge this type's $type_valid_args with its parents' $type_valid_args
		else
		{
			//this is ewwy, and may be slowing things down. But using parent:: causes a fatal error.
			$parent = new $parent_class();
			$parent_args = $parent->_get_valid_args_for_this_type();
			return array_merge ($parent_args, $this->type_valid_args); 
		}
	}
	
	/**
 	 * (Deprecated: see get_cleanup_rules())
	 * Allows a plasmature type to define its own cleanup rule.
	 * Primarily for use within the Minisite module system
	 * @returns array The value part of a cleanup rule (generally, 'function' => 'turn_into_something').
	 */
	function get_cleanup_rule()
	{
		return array( 'function' => 'turn_into_string' );
	}

	/**
	 * Allows a plasmature type to define its own cleanup rules.
	 * Primarily for use within the Minisite module system
	 * @returns array The full cleanup rule(s) for this element (generally, 'name' => array('function' => 'turn_into_something')).
	 */
	function get_cleanup_rules()
	{
		return array($this->name => array( 'function' => 'turn_into_string' ));
	}

	/**
	 * Appends the given string to this element's {@link comments}.
	 * @param string $content The text to be added to the comments.
	 * @param string $position 'before' or 'after'
	 * @todo Add a check to make sure that $content is a string.
	 */
	function add_comments( $content, $position = 'after' )
	{
		if($position == 'before')
		{
			$this->comments_pre .= $content;
		}
		else
		{
			$this->comments .= $content;
		}
	}

	/**
	 * Sets this element's {@link comments} to the given string.
	 * Note that this clobbers any existing comments.
	 * @param string $content The comments for this element.
	 * @todo Add a check to make sure that $content is a string.
	 */
	function set_comments( $content, $position = 'after' )
	{
		if($position == 'before')
		{
			$this->comments_pre = $content;
		}
		else
		{
			$this->comments = $content;
		}
	}
	
	/**
	 *  Sets an error on this element.
	 *  Note that plasmature elements can currently only have one error message;
	 *  trying to set an error on an element that already has an error will clobber
	 *  the previous error message.
	 *  @param string $error_msg Error message to be displayed.
	 */
	function set_error( $error_msg )
	{
		$this->has_error = true;
		$this->error_message = $error_msg;
	}
	
	/**
	 * Sets the {@link db_type} for this element.
	 * This may also be set using {@link set_class_var()}.
	 * @param string $db_type The database type of this element.
	 */
	function set_db_type( $db_type )
	{
		$this->db_type = $db_type;
	}

	/**
	 * Sets the {@link display_name}  for this element.
	 * This may also be set using {@link set_class_var()}.
	 * @param string $name The display name for this element.
	 */
	function set_display_name( $name )
	{
		$this->display_name = $name;
	}	

	/**
	 * Sets the unique identifier ( {@link name}) for an instance of this element type.
	 * Note that {@link name} needs to be set using this method; it cannot be set using {@link set_class_var()} or {@link init()}.
	 * @param string $name The name of this instance of this element type.
	 */
	function set_name( $name )
	{
		$this->name = $name;
		if(!$this->display_name)
			$this->display_name = prettify_string($name);
	}

	/**
	 * Returns the html id for this element. If {@link id} is set, use that;
	 * otherwise, construct a default id from the element name.
	 */
	function get_id()
	{
		if (empty($this->id))
			return $this->name.'Element';
		else
			return $this->id;
	}

	/**
	 * Sets the html id ( {@link id}) for an instance of this element type.
	 */
	function set_id($id)
	{
		$this->id = $id;
	}

	/**
	 *  Returns a copy of {@link _request}, if the request has been set.
	 * @return array Internal copy of the $_REQUEST array. 
	 */
	function get_request()
	{
		if($this->request_has_been_set)
			return $this->_request;
		else
			trigger_error('request not passed to plasmature element');
	}

	/**
	 * Sets the internal copy of the $_REQUEST array ({@link _request}).
	 * @param array $r The $_REQUEST array.
	 */
	function set_request( $r )
	{
		$this->_request = $r;
		$this->request_has_been_set = true;
	}
	
	/**
	 * The names of elements that this plasmature object manages internally.
	 *
	 * When plasmature is being used with Reason, the default Reason content
	 * manager automatically creates hidden plasmature elements for values
	 * that are in a form's POST data that do not already have elements
	 * defined. Adding a field name to the array returned by this function
	 * suppresses this behavior for that field.
	 *
	 * @return array
	 */
	function register_fields()
	{
		return array();
	}
		
	/**
	 * Is this element hidden?
	 * @return boolean
	 * @todo Remove backwards-compatibility hack to support display_style
	 */
	function is_hidden()
	{
		// backwards compatibility hack. This will go away later on. */
		if(!empty($this->display_style) && 'hidden' == $this->display_style)
		{
			trigger_error('Using display_style to hide an element is deprecated in plasmature. Set var $_hidden = true instead.');
			return true;
		}
		return $this->_hidden;
	}
	
	/**
	 * Is this element labeled?
	 * @return boolean
	 * @todo Remove backwards-compatibility hack to support display_style
	 */
	function is_labeled()
	{
		// backwards compatibility hack. This will go away later on. */
		if(!empty($this->display_style) && 'text_span' == $this->display_style)
		{
			trigger_error('Using text_span to hide an element\'s label is deprecated in plasmature. Use a "_no_label" version of the plasmature type if available; otherwise create your own by extending it and setting var $_labeled = false instead.');
			return false;
		}
		return $this->_labeled;
	}
	/**
	 * Get an ID to use as the for="" attribute on the display name label produced by Disco
	 *
	 * Plasmature elements should assume that if this is returning a nonfalse value,
	 * Disco's box class will put a label element using this value as the for="" attribute
	 * around the display name.
	 *
	 * If the element is not labeled (That is, @is_labeled() returns false), the plasmature
	 * element should use other means of labeling the element for accessibility, like
	 * using the aria-label attribute.
	 *
	 * In some cases (e.g. Plasmature elements that use multiple inputs, like checkbox groups,
	 * radio buttons, and some date/time elements) there is no single input to label.
	 * In these cases, the plasmature element should return false for this method and should
	 * instead self-label. Best practices as of 2016 are to produce a wrapper element with
	 * role="group" and aria-label="[[display_name]]", and to produce label elements or use
	 * aria-label on each component input.
	 *
	 * @return mixed false if no label is appropriate, string if label is desired
	 */
	function get_label_target_id()
	{
		return false;
	}
}
