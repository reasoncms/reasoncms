<?php
	/**
	* Plasmature
	*
	* \Plas"ma*ture\, n. Form; mold. [R.] 
	*
	* A strong typing method for a weakly typed language and, really, an easier way to deal with DB/PHP/HTML(forms) relations
	*
	* NOTE: Please don't make an extra member that uses underscores for the sake of Dave's sanity.  He may not work here anymore,
	* but it will still cause him severe psychological trauma.
	*
	* ANOTHER IMPORTANT NOTE: As of July 2006, the plasmature classes have begun to be modified for better consistency and 
	* ease of use (and to fix a few bugs), but the overall system is in desperate need of a facelift.  Until we have a chance
	* to clean up these classes, please make sure that you follow the instructions in the documentation to create new 
	* classes instead of modeling them off of existing classes.
	* 
	* How to create a new plasmature type:
	* - Extend one of the currently existing classes.  Usually you'll want to extend the {@link defaultType}. 
	* - Name the class whateverType.  You need the 'Type' to make it all work.
	* - Set the {@link type} variable to the name of the new type.
	* - Overload {@link type_valid_args} and add to it the names of any class variables that can be set outside of the class 
	*   (i.e., variables that can be set through {@link init()} or {@link set_class_var()}.
	* - Overload or extend functions as necessary.  Do NOT overload {@link init()} if you are mortal; extend or overload the 
	*	{@link additional_init_actions} hook instead.
	* - DOCUMENT THE NEW TYPE in a way that will be useful for future developers.  Otherwise Meg will have to hunt you down 
	*   and think of some terrible threat to carry out, and she's really far too busy to do that.
	*
	* @package disco
	* @subpackage plasmature
	*
	* @author Dave Hendler
	* @author Meg Gibbs
	*
	* @todo Remove any remaining references to $_REQUEST and replace them with {@link request}.  
	* @todo Separate the plasmature classes into different files.  
	* @todo Create a better error checking system.  Ideally, this should involve error checks that apply to all plasmature 
	*		types and error checks that are specific to particular types.  Error checks should be run both for values
	*		set using {@link set_value()} and for values set using {@link grab()}.
	* @todo Standardize the way that {@link display_style} is used; it's supposed to determine the way that Disco displays
	*		the element, but right now that's mostly determined by other element properties (like {@link colspan}) or by
	*		the type.
	* @todo Make the {@link defaultType} an abstract class instead of a functioning type.  Disco already uses the {@link textType}
	* 		as the default, anyway.  
	* @todo Create abstract classes for each "family" of element types.  For example, option types currently extend from 
	*		the {@link optionType()} abstract class; it would be nice to have an abstract class for date types, upload types, etc., as well.		
	* @todo Rename abstract plasmature classes so that they don't include 'Type' in their name ... that way, they CAN'T be
	*		used as types.  Also, give them more descriptive names; 'default' could become 'plasmature', etc.	
	* @todo Create a get_display_name() or get_label() method that returns the {@link display_name} if the element has one,
	*		and otherwise returns the prettified {@link name}, so that we can stop writing the code to do this all the time.
	* @todo Standardize whether or not display names are included in the markup in {@link get_display()}; right now they usually aren't,
	*		but they are sometimes.  They probably never should be.
	* @todo Create a variable that determines where labels should be displayed (to the left, right, or above the element).  Create corresponding
	*		functionality in the box class.
	*/
	
	/**
	* Include various files
	*/
	include_once('paths.php');
	include_once( CARL_UTIL_INC . 'basic/http_vars.php' );		// for get_http_vars()
	include_once( CARL_UTIL_INC . 'tidy/tidy.php' );				// for tidy functions
	include_once( CARL_UTIL_INC . 'basic/misc.php' );			// for trim_slashes(), prettify_string(), and unhtmlentities()
	include_once( CARL_UTIL_INC . 'basic/date_funcs.php' );

	/**
	* The default class for plasmature elements.
	*
	* Plasmature elements are objects that represent part of a form, usually an input field.  Each plasmature object stores its
	* own value and metadata, knows how to display itself, and knows how to grab its value from a submitted form.
	*
	*  To instantiate a plasmature element:
	*	- Give the element a unique name using {@link set_name()}.
	*	- Pass the element a copy of the $_REQUEST array using {@link set_request()}.
	*	- Instantiate variables and perform other necessary initialization actions by calling on {@link init()}.
	*
	*  Valid Arguments & Setting Class Variables
	*	- Class variables should always be set using {@link set_class_var()}  by including them in the $args
	*		parameter of the {@link init()} method, or by using a set_ method for that variable.
	*	- Not all class variables may be set using the set_class_var and init methods.  Class variables need 
	*		to be designated as a valid argument by being included in _valid_args array in order to be set
	*		using these methods.  This is done to protect the values of variables that are ineherent to the
	*		type; please do not directly set variables in order to get around this unless you know what you're doing.
	*		
	*  To set the value of a plasmature element:
	*	- Calling on {@link grab()} will cause the element to find & set its own value from the {@link request} array.
	*	- You can also use {@link set($value)} to set the value of the element to $value.
	*
	*  Displaying a plasmature element 
	*	- {@link get_display()} will return the markup for the element; {@link display()} will echo the element markup.
	*	- Display names/element labels are not usually included in the display methods.  (It would be good to standardize this so that
	*		they NEVER are.).  Make sure you don't forget to echo the {@link display_name}.
	*	- Comments are also not included in the display methods.  Use {@link get_comments} to return comment markup or
	*		{@link echo_comments} to echo comment markup.		
	*	
	* @package disco
	* @subpackage plasmature
	*
	* @todo This class is currently set up to be a fully functioning type that's basically the same as {@link textType}, 
	* 		but it should probably be an abstract class.  If it becomes an abstract class, it should be renamed so that 
	* 		it doesn't include 'Type' in its name so that it can't possibly be used as an elementType.  It would be nice
	*		to give it a more descriptive name, too - defaultPlasmature or just plasmature or something.
	*/
	class defaultType // {{{
	{
		/////////////////
		//  VARIABLES
		/////////////////
		/** 
		* The type of plasmature object that this class represents.  This string + 'Type' should be the class name.
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
		
#		What is the point of this?  aren't hidden elements a particular type? and colspan has a variable?
		/** 
		* Indicates special display instructions to Disco.
		* For example, indicates if this element is a hidden element or if it spans columns.
		* @var string
		*/
		var $display_style = 'normal';
		
		/** 
		* Number of columns which this element spans in the form.  
		* This is basically information for the box class.
		* @var int
		*/
		var $colspan;
		
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
		* Usually the name of each valid argument should be the same as the class variable it corresponds to..  If it is different, set the key 
		* of the array to the name of the class variable and the value to the name of the argument.  Please do not create new valid arguments
		* that have a different name than their corresponding class var - this functionality only exists for historical purposees.
		* @access private
		* @var array
		*/
		var $_valid_args = array( 'display_name',
									'display_style',
								  'colspan',
								  'comments',
								  'value' =>'default',
								  'db_type');
								  
		/**
		* Contains the names of properties specific to  this type that may be externally set using the parameter $args for the {@link init()}
		* function or by the {@link set_class_var()} method.
		* This array should be overloaded by child classes to add type-specific valid args.  It will be merged together with all parent 
		* type_valid_args and added to {@link _valid_args}.  The same formatting rules as {@link _valid_args} apply.
		* @var array
		*/
		var $type_valid_args = array();
		
		/**
		*  True if all the valid args specific  to this type have been added to the main {@link _valid_args}  array.
		* @var boolean
		*/
		var $_valid_args_inited = false;
		

		/////////////////////////
		//  INIT-RELATED METHODS
		/////////////////////////
		
		/** 
		*  Initializes a new plasmature object.
		*  Includes necessary files, sets variables specified in $args, and performs any additional actions specified in {@link additional_init_actions()}.
		*  This method should NOT be overloaded in child classes; use {@link do_includes()} and {@link additional_init_actions()} for 
		*  for any additional actions.  Otherwise the big scary Disco Revenge monster will come after you.
		*  Available hooks: {@link do_includes()}, {@link additional_init_actions()}	
		*  @param array $args the array of arguments - e.g., for a text box, array( 'size' => 40, 'maxlength' => '100' )
		*/
		function init( $args = array() ) // {{{
		{			
			$this->do_includes();
			
			if ( empty( $args ) )
				$args = array();
			
			foreach($args as $var_name => $var_value)
			{
				$this->set_class_var($var_name, $var_value);
			}
			
			$this->additional_init_actions($args);
		} // }}}
		
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
		function additional_init_actions($args = array()) // {{{
		{
			//overload this function
		} // }}}
		
		//////////////////////////////////////////
		//  METHODS TO SET THE VALUE OF THE OBJECT
		/////////////////////////////////////////
		
		/**
		* Finds the value of this element from userland using {@link grab_value()} and sets the value using {@link set()}
		* @return void
		*@todo Give this method a more descriptive/intuitive name?
		*/
		function grab() // {{{
		{
			$val = $this->grab_value();
			if($val !== NULL)
			{
				$this->set( $val );
			}
		} // }}}
		
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
					return trim($http_vars[ $this->name ]);
				else
					return $http_vars[ $this->name ];
			}
			return NULL;
		}
		
		/**
		* Sets the value of this element.
		* @param mixed $value Value of this element.
		*/
		function set( $value ) // {{{
		{
			$this->value = $value;
		} // }}}
		
		
		//////////////////////////////////////////
		//  DISPLAY METHODS
		/////////////////////////////////////////
		
		/**
		* Returns the markup for this element.
		* @return string HTML to display this element.
		*/
		function get_display() // {{{
		{
			$str  = '<input type="text" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" size="50" />';
			return $str;
		} // }}}
		
		/**
		*  Gets the markup for this element using {@link get_display()} and prints it.
		*/
		function display() // {{{
		{
			echo $this->get_display();
		} // }}}
		
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
		function echo_comments($position = 'after') // {{{
		{
			if($position == 'before')
			{
				echo $this->comments_pre;
			}
			else
			{
				echo $this->comments;
			}
		} // }}}		
		
		//////////////////////////////////////////
		//  MISC. METHODS
		/////////////////////////////////////////

		/**
		* Returns the value of this plasmature element.
		* @return mixed The value of this element.
		*/ 
		function get() // {{{
		{
			return $this->value;
		} // }}}
		
		/** 
		* Returns the value of this plasmature element as it should be displayed to the user.
		* This should be overloaded for types that store a different value than what they display to the user 
		* (e.g., select types often store something different from what is shown on the drop down).  To be used
		* when displaying stored info. 
		* @return mixed The pretty version of this element's value.
		*/
		function get_value_for_display()
		{
			return $this->get();
		}	
	
# Not sure exactly what this is used for, so I won't document it. -- MG
		function db_get() // {{{
		{
			return addslashes($this->value);
		} // }}}
		
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
		*  As a rule, plasmature class vars should not be set directly.  Unless there is a special method to set a class var (
		*  e.g., {@link set_comment()}, {@link set()}), this should ALWAYS be used to set plasmature class vars.
		*  Note: this will only set class variables that are included in {@link _valid_args}.  You can add additional class variables
		*  in child classes to {@link _valid_args} by overloading {@link type_valid_args}.
		*  @param string $var_name Name of the variable to be set.
		*  @param string $var_value Value that the variable should be set to.
		*/
		function set_class_var($var_name, $var_value)
		{
			$valid_args = $this->get_valid_args();
		
			//we don't need to check $_additional_valid_args because they should have already been added to the $_valid_args in init()
			if(in_array($var_name, $valid_args))
			{
				$key = array_search($var_name, $valid_args);
				
				//check to see if this arg has a string as a key; if it does, the key is the name of the actual class variable.
				//DO NOT make new valid args that have a different name than the class variable - we check this only for historical reasons.  We don't actually like this behavior.
				if(is_string($key))
					$var_name = $key;
				
				$this->$var_name = $var_value;
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
		* Returns an array of the valid args specific to this type and to its parents.
		* This function merges the {@link type_valid_args} of the current class with the {@link type_valid_args} 
		* of all parent classes.   Do not overload this function unless you have special magical powers and know what you're doing.
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
				//this is ewwy, and may be slowing things down.  But using parent:: causes a fatal error.
				$parent = new $parent_class();
				$parent_args = $parent->_get_valid_args_for_this_type();
				return array_merge ($parent_args, $this->type_valid_args); 
			}
		}
		
		/**
		* Allows a plasmature type to define its own cleanup rule.
		* Primarily for use within the Minisite module system
		* @returns array The value part of a cleanup rule (generally, 'function' => 'turn_into_something').
		*/
		function get_cleanup_rule()
		{
			return array( 'function' => 'turn_into_string' );
		}

		/**
		* Appends the given string to this element's {@link comments}.
		* @param string $content The text to be added to the comments.
		* @todo Add a check to make sure that $content is a string.
		*/
		function add_comments( $content, $position = 'after' ) // {{{
		{
			if($position == 'before')
			{
				$this->comments_pre .= $content;
			}
			else
			{
				$this->comments .= $content;
			}
		} // }}}

		/**
		* Sets this element's {@link comments} to the given string.
		* Note that this clobbers any existing comments.
		* @param string $content The comments for this element.
		* @todo Add a check to make sure that $content is a string.
		*/
		function set_comments( $content, $position = 'after' ) // {{{
		{
			if($position == 'before')
			{
				$this->comments_pre = $content;
			}
			else
			{
				$this->comments = $content;
			}
		} // }}}
		
		/**
		*  Sets an error on this element.
		*  Note that plasmature elements can currently only have one error message;
		*  trying to set an error on an element that already has an error will clobber
		*  the previous error message.
		*  @param string $error_msg Error message to be displayed.
		*/
		function set_error( $error_msg ) // {{{
		{
			$this->has_error = true;
			$this->error_message = $error_msg;
		} // }}}
		
		/**
		* Sets the {@link db_type} for this element.
		* This may also be set using {@link set_class_var()}.
		* @param string $db_type The database type of this element.
		*/
		function set_db_type( $db_type ) // {{{
		{
			$this->db_type = $db_type;
		} // }}}

		/**
		* Sets the {@link display_name}  for this element.
		* This may also be set using {@link set_class_var()}.
		* @param string $name The display name for this element.
		*/
		function set_display_name( $name ) // {{{
		{
			$this->display_name = $name;
		} // }}}	

		/**
		* Sets the unique identifier ( {@link name}) for an instance of this element type.
		* Note that {@link name} needs to be set using this method; it cannot be set using {@link set_class_var()} or {@link init()}.
		* @param string $name The name of this instance of this element type.
		*/
		function set_name( $name ) // {{{
		{
			$this->name = $name;
			if(!$this->display_name)
				$this->display_name = prettify_string($name);
		} // }}}

		/**
		*  Returns a copy of {@link _request}, if the request has been set.
		* @return array Internal copy of the $_REQUEST array.  
		*/
		function get_request() // {{{
		{
			if($this->request_has_been_set)
				return $this->_request;
			else
				trigger_error('request not passed to plasmature element');
		} // }}}

		/**
		* Sets the internal copy of the $_REQUEST array ({@link _request}).
		* @param array $r The $_REQUEST array.
		*/
		function set_request( $r ) // {{{
		{
			$this->_request = $r;
			$this->request_has_been_set = true;
		} // }}}
		
#  I'm not sure what this is, so I won't try to document it.  -- MG
		function register_fields() // {{{
		{
			return array();
		} // }}}
	 } // }}}
		 
	 ////////////////////////////////////////////
	 /// Text Types
	 ////////////////////////////////////////////

	/**
	* @package disco
	* @subpackage plasmature
	*/
	class textType extends defaultType // {{{
	{
		var $type = 'text';
		var $size = 50;
		var $maxlength = 256;
		var $type_valid_args = array( 'size', 'maxlength' );
		
		function get_display() // {{{
		{
			//return '<input type="text" name="'.$this->name.'" value="'.htmlentities($this->get(), ENT_COMPAT, 'UTF-8').'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" />';
			return '<input type="text" name="'.$this->name.'" value="'.str_replace('"', '&quot;', $this->get()).'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" />';
		} // }}}		
	} // }}}
	
	/**
	* Prints out value of item without allowing you to edit it.
	* Changing of this value in userland is now deprecated; in future releases it will not be possible.
	* @todo remove userland get behavior before 4.0rc1
	* @package disco
	* @subpackage plasmature
	*/
	class solidtextType extends defaultType // {{{
	{
		var $type = 'solidtext';
		function grab() // {{{
		{
			$value = $this->grab_value();
			if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
			{				
				trigger_error('solidText element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
			}
			parent::grab();
		} // }}}
		function get_display() // {{{
		{
			$str  = '<input type="hidden" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"/>';
			$str .= "\n".'<div class="solidText">' . $this->get(). '</div>';
			return $str;
		} // }}}		
	} // }}}
	 
	/**
	* @package disco
	* @subpackage plasmature
	*/ 
	class commentType extends defaultType // {{{
	{
		var $type = 'comment';
		var $text = 'Comment';
		var $colspan = 2;
		var $type_valid_args = array( 'text' );
		
		function grab() // {{{
		{
		}
		function get_display() // {{{
		{
			return $this->text;
		} // }}}
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class commentWithLabelType extends commentType {
		var $colspan = NULL;
		var $type = 'commentWithLabel';
	}
	
	/**
	* Plain Text -- similar to solidText -- shows text value but no form element
	* @todo remove userland get behavior before 4.0rc1
	* @package disco
	* @subpackage plasmature
	*/
	class plainTextType extends defaultType // {{{
	{
		var $type = 'plainText';
		function grab() // {{{
		{
			$value = $this->grab_value();
			if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
			{
				trigger_error('plainText element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
			}
			parent::grab();
		} // }}}
		function get_display() // {{{
		{
			$str  = '<input type="hidden" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" />'.$this->get();
			return $str;
		} // }}}
	} // }}}
	
	/**
	* Disabled Text -- shows a disabled form element
	* Changing of this value in userland is now deprecated; in future releases it will not be possible.
	* @todo remove userland get behavior before 4.0rc1
	* @package disco
	* @subpackage plasmature
	*/
	class disabledTextType extends textType // {{{
	{
	 	var $type = 'disabledText';
		function grab() // {{{
		{
			$value = $this->grab_value();
			if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
			{
				trigger_error('disabledText element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
			}
			parent::grab();
		} // }}}
		function get_display() // {{{
		{
			$str  = '<input type="text" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" disabled="disabled" />';
			return $str;
		} // }}}
	} // }}}

	/**
	* @package disco
	* @subpackage plasmature
	*/
	class passwordType extends defaultType // {{{
	{
		var $type = 'password';
		var $size = 20;
		var $maxlength = 256;
		var $type_valid_args = array( 'size', 'maxlength' );
		
		function get_display() // {{{
		{
			return '<input type="password" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" />';
		} // }}}		
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class moneyType extends textType // {{{
	{
		var $type = 'money';
		var $currency_symbol = '$';
		var $type_valid_args = array( 'currency_symbol' );

		function get_display() // {{{
		{
			$field = parent::get_display();
			return $this->currency_symbol.' '.$field;
		} // }}}
		function grab() // {{{
		{
			parent::grab();
			$this->value = str_replace( ',','',$this->value );
			if( !empty($this->value) && !is_numeric( $this->value ) )
				$this->set_error( 'Please express monetary amounts in numbers. Use a period (.) to indicate the decimal place.' );
		} // }}}
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	 class textareaType extends defaultType // {{{
	 {
	 	var $type = 'textarea';
		var $rows = 8;
		var $cols = 80;
		var $type_valid_args = array('rows', 'cols');
		
		function get_display() // {{{
		{
			$str  = '<textarea name="'.$this->name.'" rows="'.$this->rows.'" cols="'.$this->cols.'">'.htmlspecialchars($this->get(),ENT_QUOTES,'UTF-8').'</textarea>';
			return $str;
		} // }}}
		
		function grab() // {{{
		{
			parent::grab();	
			
			$length = strlen( $this->value );
			$length_limits = array( 'tinytext' =>  255,
									'text' => 65535, 
									'mediumtext' => 16777215
								   ); 
									
			
			if(!empty($this->db_type) && array_key_exists($this->db_type, $length_limits))
			{
				if($length  > $length_limits[$this->db_type])
				{
					$name_to_display = trim($this->display_name);
					if(empty($name_to_display))
						$name_to_display = prettify_string($this->name);
					$this->set_error( 'There is more text in '.$name_to_display.' than can be stored; this field can hold no more than '.$length_limits[$this->db_type].' characters.' );
				}
			}
		} // }}}
	 } // }}}

	 ////////////////////////////////////////////
	 /// Hidden Types
	 ////////////////////////////////////////////

	/**
	* @package disco
	* @subpackage plasmature
	*/
	class hiddenType extends defaultType // {{{
	{
		var $type = 'hidden';
		
		function grab() // {{{
		{
			$value = $this->grab_value();
			if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
			{
				trigger_error('hidden element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
			}
			parent::grab();
		} // }}}
		function get_display() // {{{
		{
			$str = '<input type="hidden" id="'.$this->name.'Element" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" />';
			return $str;
		} // }}}
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	* @todo Why does this extend the {@link hiddenType}?  This should extend the {@link defaultType if it's not drawing on 
	* 		any of {@link hiddenType}'s functionality.	
	*/
	class lokiType extends hiddenType // {{{
	{
		var $type = 'loki';
		var $widgets = 'default';
		var $user_is_admin = false;
		var $site_id = 0;
		var $paths = array();
		var $type_valid_args = array('widgets', 'user_is_admin', 'site_id', 'paths');
		
		function do_includes()
		{
			include_once( LOKI_INC.'lokiOptions.php3' );	// for loki Options
			include_once( LOKI_INC.'object.php' );
		}
						
		function grab() // {{{
		{
			$HTTP_VARS = $this->get_request();
			if ( isset( $HTTP_VARS[ $this->name ] ) )
			{
				$this->loki_process = new Loki_Process( $HTTP_VARS[ $this->name ] );
				$val = tidy( $this->loki_process->get_field_value() );
				if( empty( $val ) )
				{
					$tidy_err = tidy_err( $this->loki_process->get_field_value() );
					if( !empty($tidy_err) )
					{
						$tidy_err = nl2br( htmlentities( $tidy_err,ENT_QUOTES,'UTF-8' ) );
						$this->set_error( 'Your HTML appears to be ill-formatted.  Here is what Tidy has to say about it: <br />'.$tidy_err );
						$this->set( $this->loki_process->get_field_value() );
					}
					else
						$this->set( $val );
				}
				else
				{
					$val = eregi_replace("</table>\n\n<br />\n<br />\n","</table>\n", $val);
					$this->set( $val );
				}
			}
			$length = strlen( $this->value );
			if( ($this->db_type == 'tinytext' AND $length > 255) OR ($this->db_type == 'text' AND $length > 65535) OR ($this->db_type == 'mediumtext' AND $length > 16777215) )
				$this->set_error( 'There is more text in '.$this->display_name.' than can be stored ' );
		} // }}}
		
		function display() // {{{
		{
			$http_vars = $this->get_request();
			if( $this->has_error )
			{
				$this->loki = new Loki( $this->name, $this->loki_process->_field_value, $this->widgets, (!empty( $http_vars['site_id'] ) ? $http_vars['site_id'] : -1), $this->user_is_admin );
			}
			else
			{
				$this->loki = new Loki( $this->name, $this->value, $this->widgets, (!empty( $http_vars['site_id'] ) ? $http_vars['site_id'] : -1), $this->user_is_admin );
			}
			$this->loki->print_form_children();
			//loki( $this->name, $this->value , $this->widgets);
		} // }}}
		
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class loki2Type extends defaultType // {{{
	{
		var $type = 'loki2';
		var $widgets = 'default';
		var $site_id = 0;
		var $paths = array();
		var $allowable_tags = array();
		/**
		 * Exists for backwards compatibility with Loki 1
		 *
		 * Proper method to use now is to just pass the source option as a a widget, or not
		 * @deprecated
		 */
		var $user_is_admin;
		var $crash_report_uri;
		var $type_valid_args = array('widgets', 'site_id', 'paths', 'allowable_tags', 'user_is_admin', 'crash_report_uri');
		
		function do_includes()
		{
			if (file_exists( LOKI_2_INC.'loki.php' ))
			{
				include_once( LOKI_2_INC.'loki.php' );
			}
			else
			{
				trigger_error('Loki 2 file structure has changed slightly. Please update LOKI_2_INC in package_settings.php to reference the ' . LOKI_2_INC . '/helpers/php/ directory.');
				include_once( LOKI_2_INC.'/helpers/php/inc/options.php' );
			}
		}
						
		function grab() // {{{
		{
			$http_vars = $this->get_request();
			if ( isset( $http_vars[ $this->name ] ) )
			{
				$val = tidy( $http_vars[ $this->name ] );
				if( empty( $val ) )
				{
					$tidy_err = tidy_err( $http_vars[ $this->name ] );
					if( !empty($tidy_err) )
					{
						$tidy_err = nl2br( htmlentities( $tidy_err,ENT_QUOTES,'UTF-8' ) );
						$this->set_error( 'Your HTML appears to be ill-formatted.  Here is what Tidy has to say about it: <br />'.$tidy_err );
						$this->set( $http_vars[ $this->name ] );
					}
					else
						$this->set( $val );
				}
				else
				{
					// this looks like a hack. We could look into removing it.
					// $val = eregi_replace("</table>\n\n<br />\n<br />\n","</table>\n", $val);
					$this->set( $val );
				}
			}
			$length = strlen( $this->value );
			if( ($this->db_type == 'tinytext' AND $length > 255) OR ($this->db_type == 'text' AND $length > 65535) OR ($this->db_type == 'mediumtext' AND $length > 16777215) )
				$this->set_error( 'There is more text in '.$this->display_name.' than can be stored ' );
		} // }}}
		
		function display() // {{{
		{
			$loki = new Loki2( $this->name, $this->value, $this->_resolve_widgets($this->widgets) );
			if(!empty($this->paths['image_feed']))
			{
				$loki->set_feed('images',$this->paths['image_feed']);
			}
			if(!empty($this->paths['site_feed']))
			{
				$loki->set_feed('sites',$this->paths['site_feed']);
			}
			if(!empty($this->paths['finder_feed']))
			{
				$loki->set_feed('finder',$this->paths['finder_feed']);
			}
			if(!empty($this->paths['default_site_regexp']))
			{
				$loki->set_default_site_regexp($this->paths['default_site_regexp']);
			}
			if(!empty($this->paths['default_type_regexp']))
			{
				$loki->set_default_type_regexp($this->paths['default_type_regexp']);
			}
			if(!empty($this->paths['css']))
			{
				$loki->add_document_style_sheets($this->paths['css']);
			}
			if(!empty($this->allowable_tags))
			{
				$loki->set_allowable_tags($this->allowable_tags);
			}
			if(!empty($this->crash_report_uri))
			{
				$loki->set_crash_report_uri($this->crash_report_uri);
			}
			
			$loki->print_form_children();
		} // }}}
		
		function _resolve_widgets($widgets)
		{
			$widgets = $this->_flatten_widgets($widgets);
			if($this->user_is_admin)
			{
				$widgets .= ' +source +debug';
			}
			elseif($this->user_is_admin === false)
			{
				$widgets .= ' -source -debug';
			}
			return $widgets;
		}
		
		function _flatten_widgets($widgets)
		{
			if(is_array($widgets))
				return implode(' ',$widgets);
			else
				return $widgets;
		}
		
	} // }}}
	
	class tiny_mceType extends textareaType
	{
		var $type = 'tiny_mce';
		function display()
		{
			echo '<script language="javascript" type="text/javascript" src="'.TINYMCE_HTTP_PATH.'tiny_mce.js"></script>'."\n";
			echo '<script language="javascript" type="text/javascript">'."\n";
			echo 'tinyMCE.init({'."\n";
			echo 'mode : "exact",'."\n";
			echo 'theme : "advanced",'."\n";
			echo 'theme_advanced_toolbar_location : "top",'."\n";
			echo 'theme_advanced_path_location : "bottom",'."\n";
			echo 'theme_advanced_resizing : true,'."\n";
			echo 'elements : "'.$this->name.'"'."\n";
			echo '});'."\n";
			echo '</script>'."\n";
			$this->set_class_var('rows', $this->get_class_var('rows')+12 );
			parent::display();
		}
	}
	
	/**
	* @package disco
	* @subpackage plasmature
	* @todo Why does this extend the {@link hiddenType}?  This should extend the {@link defaultType if it's not drawing on 
	* 		any of {@link hiddenType}'s functionality.
	*/
	class thorType extends defaultType // {{{
	{
		var $tmp_id; // private, and don't set it using args, either
		var $original_db_conn_name; // private
		var $type = 'thor';
		var $thor_db_conn_name = NULL;
		var $asset_directory = THOR_HTTP_PATH;
		var $type_valid_args = array( 'thor_db_conn_name' );

		function display() // {{{
		{
			if(!$this->can_run())
			{
				return;
			}
			?>
			<script type="text/javascript">
			<!--
			var MM_contentVersion = 6;
			var plugin = (navigator.mimeTypes && navigator.mimeTypes["application/x-shockwave-flash"]) ? navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin : 0;
			if ( plugin ) {
				var words = navigator.plugins["Shockwave Flash"].description.split(" ");
	   			for (var i = 0; i < words.length; ++i) {
					if (isNaN(parseInt(words[i])))
					continue;
					var MM_PluginVersion = words[i]; 
	    		}
				var MM_FlashCanPlay = MM_PluginVersion >= MM_contentVersion;
			} else if (navigator.userAgent && navigator.userAgent.indexOf("MSIE")>=0 && (navigator.appVersion.indexOf("Win") != -1)) {
				document.write('<SCR' + 'IPT LANGUAGE=VBScript\> \n'); //FS hide this from IE4.5 Mac by splitting the tag
				document.write('on error resume next \n');
				document.write('MM_FlashCanPlay = ( IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash." & MM_contentVersion)))\n');
				document.write('</SCR' + 'IPT\> \n');
			}
			if ( MM_FlashCanPlay ) {
				document.write('<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" WIDTH="600" HEIGHT="400" id="thor" ALIGN="">');
				document.write('<PARAM NAME="movie" VALUE="<?php print ($this->asset_directory); ?>thor.swf?time=<?php print (time()); ?>">');
				document.write('<PARAM NAME="quality" VALUE="high">');
				document.write('<PARAM NAME="bgcolor" VALUE="#FFFFFF">');
				document.write('<PARAM NAME="FlashVars" VALUE="tmp_id=<?php print ($this->tmp_id); ?>&asset_directory=<?php print ($this->asset_directory); ?>">');
				document.write('<EMBED FlashVars="tmp_id=<?php print ($this->tmp_id); ?>&asset_directory=<?php print ($this->asset_directory); ?>" src="<?php print ($this->asset_directory); ?>thor.swf?time=<?php print (time()); ?>" quality="high" bgcolor="#FFFFFF"  WIDTH="600" HEIGHT="400" NAME="thor" ALIGN="" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"></EMBED>');
				document.write('</OBJECT>');
			} else{
				document.write('<span style="color:#ffffff;background-color:#ff0000;">You do not have the most current version of the Flash plug-in. Please install the latest <a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash">Flash Player</a> to manage this form.</span>');
			}
			//-->
			</script>
			<?php
			// Use a hidden field to pass along the tmp_id to $this->get()
			echo '<input type="hidden" id="' . $this->name . 'Element" name="' . $this->name . '" value="' . $this->tmp_id.'" />' . "\n";
		} // }}}

		function set( $value )
		{
			if(!$this->can_run())
			{
				return;
			}
			if ( empty($value) )
				$this->value = '<' . '?xml version="1.0" ?' . '><form submit="Submit" reset="Clear" />';
			else
				$this->value = $value;

			// If there's no xml declaration, assume that value contains the tmp_id
			if ( strpos($this->value, '<' . '?xml') === false )
			{
				$this->tmp_id = $this->value;

				connectDB($this->thor_db_conn_name);
				$dbs = new DBSelector();
				$dbs->add_table('thor');
				$dbs->add_field('thor', 'content');
				$dbs->add_relation('thor.id = ' . addslashes($this->tmp_id));
				$results = $dbs->run();

				if ( count($results) > 0 )
					$this->value = $results[0]['content'];
				else
					$this->value = '';

				connectDB($this->original_db_conn_name );
			}
			// Otherwise, assume that value contains the xml
			else
			{
				connectDB($this->thor_db_conn_name);
				include_once( CARL_UTIL_INC .'db/sqler.php');
				$sqler = new SQLER;
				$sqler->insert('thor', Array('content' => $this->value));
				$this->tmp_id = mysql_insert_id();
				connectDB($this->original_db_conn_name );
			}
		}
		// After init is run, one can safely assume that $this->tmp_id
		// is contains the tmp_id, and $this->value contains the XML
		function init( $args = array() ) // {{{
		{
			parent::init($args);
			if(!$this->can_run())
			{
				trigger_error('thor needs a db connection name (thor_db_conn_name)');
				return;
			}
			include_once(CARL_UTIL_INC.'db/db.php');
			$this->original_db_conn_name = get_current_db_connection_name();
			$this->set($this->value);			
			return true;

			// if $this->value doesn't contain an xml declaration,assume it's a tmp_id
			if ( strpos($this->value, '<' . '?xml') === false )
			{
				prp('in init, matches number');
				prp($this->value, 'this->value, in init (early)');

				$this->tmp_id = $this->value;
				// Use file('getXML.php'), instead of a direct call to
				// the database, because connecting to the test
				// database here seems to screw up the connection to
				// cms. Possibly only one mysql link can be open at
				// once.
				$this->value = implode("\n", file('http://'.HTTP_HOST_NAME.THOR_HTTP_PATH.'getXML.php?tmp_id=' . $this->tmp_id));
				
				prp($this->value, 'this->value, in init (late)');
			}
			// otherwise, assume $this->value contains the XML
			else
			{
				connectDB($this->thor_db_conn_name);

				include_once(CARL_UTIL_INC . 'db/sqler.php');
				$sqler = new SQLER;
				$sqler->insert('thor', Array('content' => $this->value));
				$this->tmp_id = mysql_insert_id();

				connectDB($this->original_db_conn_name);
			}
		} // }}}
		
		function can_run()
		{
			if(empty($this->thor_db_conn_name))
			{
				return false;
			}
			return true;
		}

		function get() // {{{
		{
			if(!$this->can_run())
			{
				return;
			}
			
			return $this->value;
		} // }}}
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class protectedType extends hiddenType // {{{
	{
	 	var $type = 'protected';

	 	function grab() // {{{
		{
		} // }}}
	} // }}}
	
	/**
	* Type for data that should never be written to a page (e.g. a password hash)
	* @package disco
	* @subpackage plasmature
	*/
	class cloakedType extends hiddenType // {{{
	{
		var $type = 'cloaked';
		var $display_style = 'hidden';
		
		function grab() // {{{
		{
		} // }}}
		
		function get_display() // {{{
		{
			// don't put any code here!
		} // }}
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class disabledDateType extends protectedType // {{{
	{
	 	var $type = 'disabledDate';
		var $format = 'F j, Y, g:i a';
		
		function grab() // {{{
		{
		} // }}}
		function get_display() // {{{
		{
			$year = substr( $this->get(), 0, 4 );
			$month = substr( $this->get(), 4, 2 );
			$day = substr( $this->get(), 6, 2 );
			$hour = substr( $this->get(), 8, 2 );
			$minute = substr( $this->get(), 10, 2 );
			$second = substr( $this->get(), 12, 4 );
			$str = carl_date( $this->format, carl_mktime( $hour, $minute, $second, $month, $day, $year ) );
			return $str;
		} // }}}
	} // }}}

	////////////////////////////////////////////
	/// Miscellaneous Types
	////////////////////////////////////////////
	/**
	* Displays a checkbox.
	* @see checkboxFirstType
	* @package disco
	* @subpackage plasmature
	*/
	class checkboxType extends defaultType // {{{
	{
		var $type = 'checkbox';
		var $checkbox_id;
		function grab() // {{{
		{
			$HTTP_VARS = $this->get_request();
			if ( isset( $HTTP_VARS[ $this->name ] ) )
			{
				if( !is_array( $HTTP_VARS[ $this->name ] ) AND !is_object( $HTTP_VARS[ $this->name ] ) )
					$this->set( trim($HTTP_VARS[ $this->name ]) );
				else
					$this->set( $HTTP_VARS[ $this->name ] );
			}
			else
			{
				$this->set( '' );
			}
		} // }}}
		function get_display() // {{{
		{
			$this->checkbox_id = 'checkbox_'.$this->name;
			$str = '<input type="checkbox" id="'.$this->checkbox_id.'" name="'.$this->name.'" value="true"';
			if ( $this->value )
			{
				$str .= ' checked="checked"';
			}
			$str .= '>';
			return $str;
		} // }}}
	} // }}}
	
	/**
	* Like {@link checkboxType}, but displays the label to the right of the checkbox.
	* @see checkboxType
	* @package disco
	* @subpackage plasmature
	*
	* @todo Add a variable to the default class (and corresponding box class functionality) 
	*	    that lets you choose lets you choose where the label  should be displayed for each 
	*	    element (left, right, above) and then deprecate this class.
	*/
	class checkboxfirstType extends checkboxType { // {{{
		var $type = 'checkboxfirst';
		var $display_style = 'text_span';
		var $colspan = 2;
		
		/**
		* Set to false because {@link get_display()} includes the markup for the display name.
		* Setting this to true will show the display name both to the left and to the right of the checkbox.
		*/
		var $use_display_name = false;
		
		function get_display()
		{
			return parent::get_display().' <label for="'.$this->checkbox_id.'">'.$this->display_name.'</label>';
		}
	} // }}} 
	
	/**
	* Displays an <hr /> tag.
	* @package disco
	* @subpackage plasmature
	*/
	 class hrType extends defaultType // {{{
	 {
	 	var $type = 'hr';
		function get_display() // {{{
		{
			return '<hr />';
		} // }}}
	 } // }}}


	 ////////////////////////////////////////////
	 /// Option Types
	 ////////////////////////////////////////////

	/**
	* The abstrct class that powers plasmature types that have multiple options (e.g. radio buttons, selects).
	* The optionType encapsulates a list of possible values with the one selected value.
	* This is abstract.  Don't use it!  
	* @package disco
	* @subpackage plasmature
	*/
	class optionType extends defaultType // {{{
	{
		var $type = 'option';
		var $type_valid_args = array('options');
		
		/**
		* The possible values of this element.
		* Format: value as it should be stored => value as it should be displayed.
		* @var array
		*/
		var $options = array();
		
	#should this be a valid arg?
		/**
		* True if the {@link options} array should be sorted into ascending order.
		* Otherwise, options will be displayed in the order that they were added to the {@link options} array.
		*/
		var $sort_options = true;
	
		/**
		*  Loads default options defined using {@link load_options()} and sorts the {@link options} array if {@link sort_options} is true.
		*/
		function additional_init_actions($args = array()) // {{{
		{
			parent::additional_init_actions($args);
			
			if( isset($this->options) )
			{
				$this->set_options( $this->options );
			}
			
			$this->load_options();
			
			if($this->sort_options)
				asort( $this->options );
		} // }}}
		
		/**
		*  Hook for child classes that have a default set of options (e.g. {@link stateType}, {@link languageType}).
		*/
		function load_options( $args = array() )
		{
		}

		function set_options( $options ) // {{{
		{
			if ( is_array( $options ) )
				$this->options = $options;
			else
			{
				trigger_error('Could not set options for element '.$this->name.'; '.$this->type.'::set_options() requires an array as an argument', WARNING);
				return false; 
			}
		} // }}}
		
		/**
		* Sets display order of options.  
		* @param $order array Array of options in the order you want them to appear.
		*
		* @todo Should this be disabled if {@link sort_options} is true?
		*/
		function set_option_order( $order ) // {{{
		{
			if(!empty($this->options))
			{
				if( is_array( $order ) )
				{
					$options = $this->options;
					$new_options = array();
					
					foreach($order as $option_key)
					{
						if( isset( $this->options[ $option_key ] ) )
						{
							$new_options[ $option_key ] = $this->options[ $option_key ];
							unset( $options[ $option_key ] );
						}
					}
					foreach($options as $key => $display_name)
						$new_options[ $key ] = $display_name; 
					$this->options = $new_options;
				}
			}
			else
				trigger_error('Could not set option order; '.$this->name.' does not have any options set.');
		} // }}}
	   
	 /**
	   * Returns the value as it was displayed in the element -- e.g., if you store 
	   * departments as abbreviations but display them in the option element as 
	   * department names, will return the department name that corresponds to the 
	   * internal value. -- MG 2006/07
	   */
		function get_value_for_display()
		{
			if(!empty($this->value) && !empty($this->options[$this->value]))
				return $this->options[$this->value];
			else
				return false;
		}
	} // }}}
	
	/**
	* Powers plasmature types that have multiple options (e.g. radio buttons, select drop-downs).
	* This is the same as the {@link optionType} but leaves options unsorted.
	* @package disco
	* @subpackage plasmature
	* @deprecated Use {@link optionType} instead and set {@link sort_options} to false.
	*/
	class option_no_sortType extends optionType // {{{
	{
		var $type = 'option_no_sort';
		var $sort_options = false;
	} // }}}
	
	/**
	* Displays {@link options} as radio buttons.
	* @package disco
	* @subpackage plasmature
	*/
	class radioType extends optionType // {{{
	{
		var $type = 'radio';

		function get_display() // {{{
		{
			$i = 0;
			$str = '<div id="'.$this->name.'_container" class="radioButtons">'."\n";
			$str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
			foreach( $this->options as $key => $val )
			{
				$id = 'radio_'.$this->name.'_'.$i++;
				$str .= '<tr>'."\n".'<td valign="top"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.$key.'"';
				if ( $key == $this->value )
					$str .= ' checked="checked"';
				$str .= '></td>'."\n".'<td valign="top"><label for="'.$id.'">'.$val.'</label></td>'."\n".'</tr>'."\n";
			}
			$str .= '</table>'."\n";
			$str .= '</div>'."\n";
			return $str;
		} // }}}
	} // }}}
	
	/**
	* Same as {@link radioType}, but doesn't sort {@link options}.
	* @package disco
	* @subpackage plasmature
	*/
	class radio_no_sortType extends radioType // {{{
	{
		var $type = 'radio_no_sort';
		var $sort_options = false;
		
	} // }}}
	
	/**
	* Displays {@link options} as a group of checkboxes.
	* Use {@link checkboxType} to create a single checkbox.
	* @package disco
	* @subpackage plasmature
	*/
	class checkboxgroupType extends optionType // {{{
	{
		var $type = 'checkboxgroup';
		
		function get_display() // {{{
		{
			$str = '<div class="checkBoxGroup">'."\n";
			$str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
			$i = 0;
			foreach( $this->options as $key => $val )
			{
				$id = 'checkbox_'.$this->name.'_'.$i;
				$str .= '<tr><td valign="top"><input type="checkbox" id="'.$id.'" name="'.$this->name.'['.$i.']" value="'.$key.'"';
				if ( is_array($this->value) ) {
					if ( array_search($key, $this->value) !== false )
						$str .= ' checked="checked"';
				}
				else {
					if ( $key == $this->value )
						$str .= ' checked="checked"';
				}
				$str .= ' /></td><td valign="top"><label for="'.$id.'">'.$val."</label></td></tr>\n";
				$i++;
			}
			$str .= '</table>'."\n";
			$str .= '</div>'."\n";
			return $str;
		} // }}}
		function grab()
		{
			// Without this condition, if the user unchecks all the
			// boxes, the default value will be used. The reason is
			// that if no checkboxes are checked, the browser sends no
			// post variable at all for the group. This is in contrast
			// to other form elements, e.g., input boxes, for which
			// the browser sends an empty string if the user doesn't
			// enter anything. And the default plasmature behavior
			// assumes that all the form elements will behave like the
			// input boxes. So what happens is that $this-value is
			// first set to the default value, and then over written
			// by the request variable if one is present (if an
			// appropriate request variable isn't present, it's
			// assumed that it's the first time). But in the case of
			// checkboxes, this is a false assumption, because (as I
			// said above) if no box is checked, no request variable
			// is sent. --NF 23/Feb/2004
			$request = $this->get_request();
			if ( isset($request[ $this->name ]) )
				$this->set( $request[ $this->name ] );
			else
				$this->set( array() );
		}
		function get()
		{
			// This conditional is needed because when disco checkes
			// whether values have been provided for all required
			// variables, disco simply (and naively, I might add)
			// evaluates the boolean value of $this->get(), rather
			// than the boolean value of, say
			// !empty($this->get()). (It might be worth fixing this
			// behavior of disco, but I'm not sure at this point
			// whether to do so would break anything.) But, in
			// contrast to an empty string, a variable which points to
			// an empty array evaluates to true. So I check for that
			// here. --NF 23/Feb/2004
			if ( empty($this->value) )
				return false;
			else
				return $this->value;
		}
		function get_cleanup_rule()
		{
			return array( 'function' => 'turn_into_array' );
		}
	} // }}}
	
	/**
	* Like {@link checkboxgroupType}, but doesn't sort options.
	* @package disco
	* @subpackage plasmature
	*/
	class checkboxgroup_no_sortType extends checkboxgroupType // {{{
	{
		var $type = 'checkboxgroup_no_sort';
		var $sort_options = false;
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	* @todo do better data checking to make sure that value is one of the available options (or empty/null)
	*/
	class selectType extends optionType // {{{
	{
		var $type = 'select';
		var $type_valid_args = array(	'n' => 'size',
										'multiple', 
								     	'add_null_value_to_top');
	
		var $n = 1;
		
		/**
		*  True if multiple options may be selected.
		* @var boolean
		*/
		var $multiple = false;
		
		/**
		* If true, adds a null value to the top of the select.
		*/
		var $add_null_value_to_top = true;	
		
		/* function grab() // {{{
		{
			parent::grab();
			
			$value = $this->get();
			if(is_array($value))
			{
				foreach($value as $key => $val)
				{
					if(!isset($this->options[$key]))
					{
						$this->set_error(htmlspecialchars($key,ENT_QUOTES).' is not an acceptable value');
					}
				}
			}
			elseif(is_string($value))
			{
				if(!isset($this->options[$value]))
				{
					$this->set_error(htmlspecialchars($value,ENT_QUOTES).' is not an acceptable value');
				}
			}
			else
			{
				$this->set_error('Strange problem');
			}
		} */
				
		function get_display() // {{{
		{
			$str = '<select id="'.$this->name.'Element" name="'.$this->name.'" size="'.$this->n.'" '.($this->multiple ? 'multiple="multiple"' : '').'>'."\n";
			if($this->add_null_value_to_top)
				$str .= '<option value="" '.(empty($this->value)?'selected="selected"':'').'>--</option>'."\n";
			foreach( $this->options as $key => $val )
			{
				if( $val === '--' )
				{
					$str .= '<option value="">--</option>' . "\n";
				}
				else
				{
					$str .= '<option value="'.$key.'"';
					if ( is_array($this->value) ) {
						if ( array_search($key, $this->value) !== false )
							$str .= ' selected="selected"';
					}
					else {
						if ( $key == $this->value )
							$str .= ' selected="selected"';
					}
					$str .= '>'.$val.'</option>'."\n";
				}
			}
			$str .= '</select>'."\n";
			return $str;
		} // }}}
	 } // }}}	
	
	/**
	* Same as {@link selectType}  but doesn't sort the {@link options}.
	* @package disco
	* @subpackage plasmature
	*/
	class select_no_sortType extends selectType // {{{
	{
		var $type = 'select_no_sort';
		var $add_null_value_to_top = false;	
		var $sort_options = false;		
	 } // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class file_listerType extends selectType // {{{
	{
		var $type = 'file_lister';
		var $type_valid_args = array( 'extension',
									  'strip_extension',
									  'prettify_file_name',
									  'directory');
	
		function load_options( $args = array())
		{
			$files = array();
			if ( isset( $this->directory ) )
			{
				$handle = opendir( $args['directory'] );
				while( $entry = readdir( $handle ) )
				{
					if( is_file( $this->directory.$entry ) )
					{
						$show_entry = true;
						$entry_display = $entry_value = $entry;

						if( !empty( $this->strip_extension ) )
							$entry_display = $entry_value = substr( $entry, 0, strrpos( $entry, '.' ));

						if( !empty( $this->prettify_file_name ) )
							$entry_display = prettify_string( substr( $entry, 0, strrpos( $entry, '.' ) ) );

						if( !empty( $this->extension ) )
							if ( !preg_match( '/'.$this->extension.'$/',$entry ) )
								$show_entry = false;

						if( $show_entry )
							$files[ $entry_value ] = $entry_display;
					}
				}
				ksort( $files );
			}
			$this->options += $files;
		}
	} // }}}
	
	/**
	* The plasmature class for a drop-down of languages.
	* @package disco
	* @subpackage plasmature
	*/
	class languageType extends select_no_sortType // {{{
	{
	 	var $type = 'language';
		var $type_valid_args = array( 'exclude_these_languages' );
		
		/**
		* Array of languages that should not be included in the {@link options}.
		* @var array
		*/
		var $exclude_these_languages = array();

		/**
		*  Adds the default languages to the {@link options} array.
		*/
		function load_options( $args = array() ) // {{{
		{
			$languages = array(
								'eng' => 'English',
								'alb' => 'Albanian',
								'ara' => 'Arabic',
								'arm' => 'Armenian',
								'asm' => 'Assamese',
								'aze' => 'Azerbaijani',
								'bel' => 'Belarusian',
								'ben' => 'Bengali',
								'bul' => 'Bulgarian',
								'cat' => 'Catalan/Valencian',
								'chi' => 'Chinese',
								'scr' => 'Croatian',
								'cze' => 'Czech',
								'dan' => 'Danish',
								'dut' => 'Dutch/Flemish',
								'est' => 'Estonian',
								'fin' => 'Finnish',
								'fre' => 'French',
								'geo' => 'Georgian',
								'ger' => 'German',
								'gre' => 'Greek',
								'guj' => 'Gujarati',
								'heb' => 'Hebrew',
								'hin' => 'Hindi',
								'hun' => 'Hungarian',
								'ice' => 'Icelandic',
								'ita' => 'Italian',
								'jpn' => 'Japanese',
								'jav' => 'Javanese',
								'kor' => 'Korean',
								'lav' => 'Latvian',
								'lit' => 'Lithuanian',
								'mac' => 'Macedonian',
								'may' => 'Malay',
								'mal' => 'Malayalam',
								'mar' => 'Marathi',
								'mol' => 'Moldavian',
								'nor' => 'Norwegian',
								'xxx' => 'Other',
								'per' => 'Persian',
								'pol' => 'Polish',
								'por' => 'Portuguese',
								'rum' => 'Romanian',
								'rus' => 'Russian',
								'scc' => 'Serbian',
								'slo' => 'Slovak',
								'slv' => 'Slovenian',
								'spa' => 'Spanish/Castilian',
								'swe' => 'Swedish',
								'tgl' => 'Tagalog',
								'tam' => 'Tamil',
								'tat' => 'Tatar',
								'tel' => 'Telugu',
								'tha' => 'Thai',
								'tur' => 'Turkish',
								'ukr' => 'Ukrainian',
								'urd' => 'Urdu',
								'vie' => 'Vietnamese',
			);
			foreach( $languages as $key => $val )
			{
				if(!in_array($val, $this->exclude_these_languages))
					$this->options[ $key ] = $val;
			}
		} // }}}
	} // }}}

	/**
	* The plasmature class for a drop-down of U.S. states.
	*
	* To include Canadian provinces, use {@link state_provinceType}.
	*
	* To include military APO/FPO codes, pass the include_military_codes argument as true
	*
	* @package disco
	* @subpackage plasmature
	*/
	class stateType extends selectType // {{{
	{
	 	var $type = 'state';
		var $type_valid_args = array('use_not_in_usa_option','include_military_codes'); 
		var $sort_options = false;

		/**
		* Adds a "Not in the US" option to the state type.
		* The default value is false. It can also be set to "top" if the "Not in US" option should appear at the top.
		* All non-empty values other than "top" will append the option to the end of the list.
		* @var mixed
		*/	
		var $use_not_in_usa_option = false;
		
		/**
		* Adds the US military state codes to the list of options
		* The default value is false. A true value will add the military state codes after Wyoming.
		* @var boolean
		*/	
		var $include_military_codes = false;

		/**
		*  Populates the {@link options} array.
		*/
		function load_options( $args = array())
		{
			$this->load_states();
			//if use_not_in_usa is set to 'top', put it at the top of the select
			if($this->use_not_in_usa_option === 'top')
			{
				$temp = array('XX' => 'Not in USA');
				$temp[] = '--';
				$this->options = array_merge($temp, $this->options);
			}
			//for any other true value, stick it at the bottom of the select
			elseif($this->use_not_in_usa_option)
			{
				$this->options[] = '--';
				$this->options['XX'] = 'Not in USA'; 
			}
		}		

		
		/**
		*  Adds the U.S. states to the {@link options} array.
		*  Helper function to {@link load_options()}.
		*/
		//load_states is outside of load_options so that state_province can use load_states, too
		function load_states() // {{{
		{
			$states = array(
				'AL' => 'Alabama',
				'AK' => 'Alaska',
				'AZ' => 'Arizona',
				'AR' => 'Arkansas',
				'CA' => 'California',
				'CO' => 'Colorado',
				'CT' => 'Connecticut',
				'DE' => 'Delaware',
				'DC' => 'District of Columbia',
				'FL' => 'Florida',
				'GA' => 'Georgia',
				'HI' => 'Hawaii',
				'ID' => 'Idaho',
				'IL' => 'Illinois',
				'IN' => 'Indiana',
				'IA' => 'Iowa',
				'KS' => 'Kansas',
				'KY' => 'Kentucky',
				'LA' => 'Louisiana',
				'ME' => 'Maine',
				'MD' => 'Maryland',
				'MA' => 'Massachusetts',
				'MI' => 'Michigan',
				'MN' => 'Minnesota',
				'MS' => 'Mississippi',
				'MO' => 'Missouri',
				'MT' => 'Montana',
				'NE' => 'Nebraska',
				'NV' => 'Nevada',
				'NH' => 'New Hampshire',
				'NJ' => 'New Jersey',
				'NM' => 'New Mexico',
				'NY' => 'New York',
				'NC' => 'North Carolina',
				'ND' => 'North Dakota',
				'OH' => 'Ohio',
				'OK' => 'Oklahoma',
				'OR' => 'Oregon',
				'PA' => 'Pennsylvania',
				'RI' => 'Rhode Island',
				'SC' => 'South Carolina',
				'SD' => 'South Dakota',
				'TN' => 'Tennessee',
				'TX' => 'Texas',
				'UT' => 'Utah',
				'VT' => 'Vermont',
				'VA' => 'Virginia',
				'WA' => 'Washington',
				'WV' => 'West Virginia',
				'WI' => 'Wisconsin',
				'WY' => 'Wyoming',
			);
			if($this->include_military_codes)
			{
				$states[] = '--';
				$states['AA'] = 'AA (Military APO/FPO)';
				$states['AE'] = 'AE (Military APO/FPO)';
				$states['AP'] = 'AP (Military APO/FPO)';
			}
			foreach( $states as $key => $val )
				$this->options[ $key ] = $val;
		} // }}}
	} // }}}
	
	/**
	* The plasmature element for a  drop-down of U.S. states and Canadian provinces.
	* To display only U.S. states, use {@link stateType}.
	* @package disco
	* @subpackage plasmature
	*/
	class state_provinceType extends stateType // {{{
	{
	 	var $type = 'state_province';
		var $use_not_in_usa_option = true;
		var $sort_options = false;		
		
		function load_options( $args = array())
		{
			$this->load_states();
			$this->options[] = '--';
			$this->load_provinces();
			
			if($this->use_not_in_usa_option === 'top')
			{
				$temp = array('XX' => 'Not in USA/Canada');
				$temp[] = '--';
				$this->options = array_merge($temp, $this->options);
			}
			elseif($this->use_not_in_usa_option)
			{
				$this->options[] = '--';
				$this->options['XX'] = 'Not in USA/Canada'; 
			}
		}
		
		/**
		*  Adds the Canadian states to the {@link options} array.
		*  Helper function to {@link load_options()}.
		*/
		function load_provinces() // {{{
		{
			$provinces = array(
				'AB' => 'Alberta',
				'BC' => 'British Columbia',
				'MB' => 'Manitoba',
				'NB' => 'New Brunswick',
				'NL' => 'Newfoundland and Labrador',
				'NT' => 'Northwest Territories',
				'NS' => 'Nova Scotia',
				'NU' => 'Nunavut',
				'ON' => 'Ontario',
				'PE' => 'Prince Edward Island',
				'QC' => 'Quebec',
				'SK' => 'Saskatchewan',
				'YT' => 'Yukon',
			);

			foreach( $provinces as $key => $val )
				$this->options[ $key ] = $val;
		} // }}}
	} // }}}
	
	/**
	* The plasmature element for  a drop-down of countries.
	* @package disco
	* @subpackage plasmature
	*
	*@todo Add "exclude these countries" functionality -- like in the {@link languageType}
	*/
	class countryType extends selectType // {{{
	{
	 	var $type = 'country';
		var $sort_options = false;	//false so that we can have USA at the top.
		
		/**
		* Populates the {@link options} array with a default list of countries.
		*/
		function load_options( $args = array() ) // {{{
		{
			$countries = array(
				'USA' => 'United States of America',
				'AFG' => 'Afghanistan',
				'ALB' => 'Albania',
				'DZA' => 'Algeria',
				'ASM' => 'American Samoa',
				'AND' => 'Andorra',
				'AGO' => 'Angola',
				'AIA' => 'Anguilla',
				'ATA' => 'Antarctica',
				'ATG' => 'Antigua and Barbuda',
				'ARG' => 'Argentina',
				'ARM' => 'Armenia',
				'ABW' => 'Aruba',
				'AUS' => 'Australia',
				'AUT' => 'Austria',
				'AZE' => 'Azerbaijan',
				'BHS' => 'Bahamas',
				'BHR' => 'Bahrain',
				'BGD' => 'Bangladesh',
				'BRB' => 'Barbados',
				'BLR' => 'Belarus',
				'BEL' => 'Belgium',
				'BLZ' => 'Belize',
				'BEN' => 'Benin',
				'BMU' => 'Bermuda',
				'BTN' => 'Bhutan',
				'BOL' => 'Bolivia',
				'BIH' => 'Bosnia and Herzegovina',
				'BWA' => 'Botswana',
				'BVT' => 'Bouvet Island',
				'BRA' => 'Brazil',
				'IOT' => 'British Indian Ocean',
				'VGB' => 'British Virgin Islands',
				'BRN' => 'Brunei Darussalam',
				'BGR' => 'Bulgaria',
				'BFA' => 'Burkina Faso',
				'BDI' => 'Burundi',
				'KHM' => 'Cambodia',
				'CMR' => 'Cameroon',
				'CAN' => 'Canada',
				'CPV' => 'Cape Verde',
				'CYM' => 'Cayman Islands',
				'CAF' => 'Central African Republic',
				'TCD' => 'Chad',
				'CHL' => 'Chile',
				'CHN' => 'China',
				'CXR' => 'Christmas Island',
				'CCK' => 'Cocos Islands',
				'COL' => 'Colombia',
				'COM' => 'Comoros',
				'COD' => 'Congo, Dem. Republic of',
				'COG' => 'Congo',
				'COK' => 'Cook Islands',
				'CRI' => 'Costa Rica',
				'CIV' => 'Cote d\'Ivoire',
				'CUB' => 'Cuba',
				'CYP' => 'Cyprus',
				'CZE' => 'Czech Republic',
				'DNK' => 'Denmark',
				'DJI' => 'Djibouti',
				'DMA' => 'Dominica',
				'DOM' => 'Dominican Republic',
				'ECU' => 'Ecuador',
				'EGY' => 'Egypt',
				'SLV' => 'El Salvador',
				'GNQ' => 'Equatorial Guinea',
				'ERI' => 'Eritrea',
				'EST' => 'Estonia',
				'ETH' => 'Ethiopia',
				'FRO' => 'Faroe Islands',
				'FLK' => 'Falkland Islands',
				'FJI' => 'Fiji the Fiji Islands',
				'FIN' => 'Finland',
				'FRA' => 'France',
				'GUF' => 'French Guiana',
				'PYF' => 'French Polynesia',
				'ATF' => 'French Southern Territories',
				'GAB' => 'Gabon',
				'GMB' => 'Gambia the',
				'GEO' => 'Georgia',
				'DEU' => 'Germany',
				'GHA' => 'Ghana',
				'GIB' => 'Gibraltar',
				'GRC' => 'Greece',
				'GRL' => 'Greenland',
				'GRD' => 'Grenada',
				'GLP' => 'Guadeloupe',
				'GUM' => 'Guam',
				'GTM' => 'Guatemala',
				'GIN' => 'Guinea',
				'GNB' => 'Guinea-Bissau',
				'GUY' => 'Guyana',
				'HTI' => 'Haiti',
				'HMD' => 'Heard & McDonald Islands',
				'VAT' => 'Holy See (Vatican)',
				'HND' => 'Honduras',
				'HKG' => 'Hong Kong',
				'HRV' => 'Hrvatska (Croatia)',
				'HUN' => 'Hungary',
				'ISL' => 'Iceland',
				'IND' => 'India',
				'IDN' => 'Indonesia',
				'IRN' => 'Iran',
				'IRQ' => 'Iraq',
				'IRL' => 'Ireland',
				'ISR' => 'Israel',
				'ITA' => 'Italy',
				'JAM' => 'Jamaica',
				'JPN' => 'Japan',
				'JOR' => 'Jordan',
				'KAZ' => 'Kazakhstan',
				'KEN' => 'Kenya',
				'KIR' => 'Kiribati',
				'PRK' => 'Korea, North',
				'KOR' => 'Korea, South',
				'KWT' => 'Kuwait',
				'KGZ' => 'Kyrgyz Republic',
				'LAO' => 'Laos',
				'LVA' => 'Latvia',
				'LBN' => 'Lebanon',
				'LSO' => 'Lesotho',
				'LBR' => 'Liberia',
				'LBY' => 'Libya',
				'LIE' => 'Liechtenstein',
				'LTU' => 'Lithuania',
				'LUX' => 'Luxembourg',
				'MAC' => 'Macau',
				'MKD' => 'Macedonia',
				'MDG' => 'Madagascar',
				'MWI' => 'Malawi',
				'MYS' => 'Malaysia',
				'MDV' => 'Maldives',
				'MLI' => 'Mali',
				'MLT' => 'Malta',
				'MHL' => 'Marshall Islands',
				'MTQ' => 'Martinique',
				'MRT' => 'Mauritania',
				'MUS' => 'Mauritius',
				'MYT' => 'Mayotte',
				'MEX' => 'Mexico',
				'FSM' => 'Micronesia',
				'MDA' => 'Moldova',
				'MCO' => 'Monaco',
				'MNG' => 'Mongolia',
				'MSR' => 'Montserrat',
				'MAR' => 'Morocco',
				'MOZ' => 'Mozambique',
				'MMR' => 'Myanmar',
				'NAM' => 'Namibia',
				'NRU' => 'Nauru',
				'NPL' => 'Nepal',
				'ANT' => 'Netherlands Antilles',
				'NLD' => 'Netherlands the',
				'NCL' => 'New Caledonia',
				'NZL' => 'New Zealand',
				'NIC' => 'Nicaragua',
				'NER' => 'Niger the',
				'NGA' => 'Nigeria',
				'NIU' => 'Niue',
				'NFK' => 'Norfolk Island',
				'MNP' => 'Northern Mariana Islands',
				'NOR' => 'Norway',
				'OMN' => 'Oman',
				'PAK' => 'Pakistan',
				'PLW' => 'Palau',
				'PSE' => 'Palestinian Territory',
				'PAN' => 'Panama',
				'PNG' => 'Papua New Guinea',
				'PRY' => 'Paraguay',
				'PER' => 'Peru',
				'PHL' => 'Philippines the',
				'PCN' => 'Pitcairn Island',
				'POL' => 'Poland',
				'PRT' => 'Portugal',
				'PRI' => 'Puerto Rico',
				'QAT' => 'Qatar',
				'REU' => 'Reunion',
				'ROU' => 'Romania',
				'RUS' => 'Russian Federation',
				'RWA' => 'Rwanda',
				'SHN' => 'St. Helena',
				'KNA' => 'St. Kitts and Nevis',
				'LCA' => 'St. Lucia',
				'SPM' => 'St. Pierre and Miquelon',
				'VCT' => 'St. Vincent & the Grenadines',
				'WSM' => 'Samoa',
				'SMR' => 'San Marino',
				'STP' => 'Sao Tome and Principe',
				'SAU' => 'Saudi Arabia',
				'SEN' => 'Senegal',
				'SCG' => 'Serbia and Montenegro',
				'SYC' => 'Seychelles',
				'SLE' => 'Sierra Leone',
				'SGP' => 'Singapore',
				'SVK' => 'Slovakia',
				'SVN' => 'Slovenia',
				'SLB' => 'Solomon Islands',
				'SOM' => 'Somalia',
				'ZAF' => 'South Africa',
				'SGS' => 'South Georgia',
				'ESP' => 'Spain',
				'LKA' => 'Sri Lanka',
				'SDN' => 'Sudan',
				'SUR' => 'Suriname',
				'SJM' => 'Svalbard',
				'SWZ' => 'Swaziland',
				'SWE' => 'Sweden',
				'CHE' => 'Switzerland',
				'SYR' => 'Syrian Arab Republic',
				'TWN' => 'Taiwan',
				'TJK' => 'Tajikistan',
				'TZA' => 'Tanzania',
				'THA' => 'Thailand',
				'TLS' => 'Timor-Leste',
				'TGO' => 'Togo',
				'TKL' => 'Tokelau',
				'TON' => 'Tonga',
				'TTO' => 'Trinidad and Tobago',
				'TUN' => 'Tunisia',
				'TUR' => 'Turkey',
				'TKM' => 'Turkmenistan',
				'TCA' => 'Turks and Caicos',
				'TUV' => 'Tuvalu',
				'UGA' => 'Uganda',
				'UKR' => 'Ukraine',
				'ARE' => 'United Arab Emirates',
				'GBR' => 'United Kingdom & N. Ireland',
				'UMI' => 'US Minor Outlying Islands',
				'VIR' => 'US Virgin Islands',
				'URY' => 'Uruguay',
				'UZB' => 'Uzbekistan',
				'VUT' => 'Vanuatu',
				'VEN' => 'Venezuela',
				'VNM' => 'Vietnam',
				'WLF' => 'Wallis and Futuna Islands',
				'ESH' => 'Western Sahara',
				'YEM' => 'Yemen',
				'ZMB' => 'Zambia',
				'ZWE' => 'Zimbabwe',
			);
			foreach( $countries as $key => $val )
				$this->options[ $key ] = $val;
		} // }}}
	} // }}}
	
	/**
	* The plasmature element for a drop-down of months.
	*  Months are displayed in the format specified by {@link date_format}.
	* @package disco
	* @subpackage plasmature
	*/
	class monthType extends selectType // {{{
	{
		var $type = 'month';
		var $date_format = 'm (F)';
		var $type_valid_args = array('date_format'); 
		var $sort_options = false;

		function load_options( $args = array() ) // {{{
		{
			for($month = 1; $month <= 12; $month++)
			{
				$this->options[ $month ] = carl_date($this->date_format,carl_mktime(0,0,0,$month,1,1970));
			}
		} // }}}
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	 class tablelinkerType extends selectType // {{{
	 {
		var $type = 'tablelinker';
		var $table;
		var $type_valid_args = array('table');
		
		function load_options( $args = array() )
		{
			// see if table is set
			if ( !isset( $this->table ) OR empty( $this->table ) )
				// if not, check the name of the element
				if ( preg_match( '/^(.*)_id$/', $this->name, $matches ) )
					// if it is tablename_id, we are good
					$this->table = $matches[ 1 ];
				// bad stuff.  we need a valid table name
				else
				{
					trigger_error( 'dblinkerType::init - no valid tablename found' );
					return;
				}

			// load options from DB
			$q = "SELECT id, name FROM ".$this->table;
			$r = mysql_query( $q ) OR die( 'Plasmature Error :: tablelinkerType :: "'.$q.'" :: '.mysql_error() );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$options[ $row['id'] ] = $row['name'];

			// sort options by value maintaining key association
			asort( $options );
			$this->set_options( $options );
		}
		
		/*function init( $args = '' ) // {{{
		{
			// handle args
			if ( !empty( $args ) )
				if ( isset( $args[ 'table' ] ) AND !empty( $args[ 'table' ] ) )
					$this->table = $args['table'];

			// see if table is set
			if ( !isset( $this->table ) OR empty( $this->table ) )
				// if not, check the name of the element
				if ( preg_match( '/^(.*)_id$/', $this->name, $matches ) )
					// if it is tablename_id, we are good
					$this->table = $matches[ 1 ];
				// bad stuff.  we need a valid table name
				else
					die( 'dblinkerType::init - no valid tablename found' );

			// load options from DB
			$q = "SELECT id, name FROM ".$this->table;
			$r = mysql_query( $q ) OR die( 'Plasmature Error :: tablelinkerType :: "'.$q.'" :: '.mysql_error() );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
				$options[ $row['id'] ] = $row['name'];

			// sort options by value maintaining key association
			asort( $options );
			$this->set_options( $options );
		} // }}}*/
	 } // }}}
	 
	/**
	* Displays a drop-down of numbers within the given range.
	* Begins at {@link start} (default 0) and stops after {@link end} (default 10), using {@link step} (default 1) to increment. 
	* @package disco
	* @subpackage plasmature
	*/
	class numrangeType extends selectType // {{{
	{
		var $type = 'numrange';
		var $start = 0;
		var $end = 10;
		var $step = 1;
		var $empty_option = false;
		var $type_valid_args = array ( 'start',
									   'end',
									   'step',
									   'empty_option',
									  ); 

		function load_options( $args = array() )
		{
			$this->add_numrange_to_options();
		}
		
		function add_numrange_to_options()
		{
			for( $i = $this->start; $i <= $this->end; $i += $this->step )
				$this->options[ $i ] = $i;
		}
	} // }}}
	
	/**
	* Displays a drop-down of ages.
	* Exactly like {@link numrangeType} except that it displays '0-1' instead of '0' and won't display negative numbers.
	* @package disco
	* @subpackage plasmature
	*/
	class ageType extends numrangeType // {{{
	{
		var $type = 'age';	
		
		function load_options( $args = array() )
		{
			if( $this->start < 0 ) 
				$this->start = 0;

			if( $this->start == 0 )
			{
				$this->options[ '0-1' ] = '0-1';
				$this->start = 1;
			}

			$this->add_numrange_to_options();
		} // }}}
	} // }}}
	
	/**
	* The plasmature element for a drop-down of years.  
	* Will display years from 2000 to 2050 by default; these can be altered by setting {@link start} and {@link end},
	* or by setting {@link num_years_before_today} and {@link num_years_after_today}.  By default, each year between
	* {@link start} and {@link end} is displayed, but the interval may be changed by setting {@link step}.
	* @package disco
	* @subpackage plasmature
	*/
	class yearType extends numrangeType // {{{
	{
	 	var $type = 'year';
		var $start = 2000;
		var $end = 2050;
		var $num_years_before_today;	//set this instead of start if you want to set a variable start date (e.g., always starts 5 years before current year.)
										//will override $this->start
		var $num_years_after_today;
		var $type_valid_args = array(  'num_years_before_today',
										'num_years_after_today',
									  ); 
				 
		function load_options( $args = array() )
		{		
			$this->determine_start_year();
			$this->determine_end_year();
			parent::load_options();
		}
		
		 function determine_start_year()
		 {
		 	if(!empty($this->num_years_before_today))
			{
				$current_date = getdate();
				$this->start = $current_date['year'] - $this->num_years_before_today;
			}
		 }
		 
		 function determine_end_year()
		 {
		 	if(!empty($this->num_years_after_today))
			{
				$current_date = getdate();
				$this->end = $current_date['year'] + $this->num_years_after_today;
			}
		 }	  
	} // }}}

	/**
	* @package disco
	* @subpackage plasmature
	*/
	class select_multipleType extends selectType // {{{
	{
		var $type = 'select_multiple';
		var $type_valid_args = array( 'select_size', 
									  'multiple_display_type'
									);
		
		/** Can be 'checkbox' or 'select' to use multiple checkboxes or a multiple select box, respectively*/
		var $multiple_display_type = 'select';
		/** if using multiple select box, how many rows to show at once? */
		var $select_size = 8;
		
		/*function init( $args = '' ) // {{{
		{
			if ( !empty( $args ) )
			{
				if ( isset( $args['multiple_display_type'] ) )
					$this->multiple = $args['multiple_display_type'];
			}
			parent::init( $args );
		} // }}} */
		
		function array_contains( $array, $value ) // {{{
		{
			if(!is_array( $array ) )
				return false;
			foreach( $array as $item )
				if($item == $value)
					return true;
			return false;
		} // }}}
		function nOptions() // {{{
		{
			$i = 0;
			foreach( $this->options as $key =>$val )
				$i++;
			return $i;
		} // }}}
		function get_display() // {{{
		{
			$str = '';
			if( $this->multiple_display_type == 'checkbox' )
			{
				foreach( $this->options as $key => $val )
				{
					$str .= "\n".'<input type="checkbox" id="'.$this->name.'-'.$key.'" name="'.$this->name.'[]" value="'.$key.'"';
					if( $this->array_contains( $this->value,$key ) )
						$str .= ' checked="checked"';
					$str .= ' />'."\n".'<label for="'.$this->name.'-'.$key.'">'.$val.'</label><br />';
				}
				$str .= "\n";
			}
			else
			{
				$str = '<select name="'.$this->name.'[]" multiple="multiple" size="'.$this->select_size.'">'."\n";
				//$str .= '<option value="">*none*</option>'."\n";
				foreach( $this->options as $key => $val )
				{
					$str .= '<option value="'.$key.'"';
					if ( $this->array_contains( $this->value, $key) )
						$str .= ' selected="selected"';
					$str .= '>'.$val.'</option>'."\n";
				}
				$str .= '</select>'."\n";
			}
			$str .= "\n";
			return $str;
		} // }}}
	} // }}}

/**
 * Not currently being used and thus commented out 
 * - if used the javascript reference to multiple_select.js will need to be dealt with and some web-available
 *   aspect of plasmature will need to be considered.

	class select_multiple_jsType extends selectType // {{{
	{
		function get_display() // {{{
		{
			$str  = "\n".'<script language="JavaScript" src ="//'.HTTP_HOST_NAME.'/global_stock/js/multiple_select.js" type="text/javascript"></script>'."\n";
			$str .= '<table><tr><th>Unselected</th><th>&nbsp</th><th>Selected</th></tr>'."\n";
			$str .= '<tr><td>';
			$str .= '<div id="'.$this->name.'_unselectedItems"><select multiple="multiple" size="'.min( 10,$this->nOptions() ).'" style="width: 60mm">'."\n";
			foreach( $this->options as $key => $val )
				if( !$this->array_contains( $this->value,$key ) )
					$str .= '<option value="'.$key.'">'.$val.'</option>'."\n";

			$str .= '</select></div></td><td><input type="button" value="--&gt;" id="'.$this->name.'_select_button" onclick="select_items(\''.$this->name.'\')" /><br /> <input type="button" value="&lt;--" id="'.$this->name.'_deselect_button" onclick="deselect_items(\''.$this->name.'\')" /></td>'."\n";
			$str .= '<td><select name="'.$this->name.'[]" id="'.$this->name.'_selectedItems" multiple="multiple" size="'.min( 10,$this->nOptions() ).'" style="width: 60mm">';
			foreach( $this->options as $key => $val )
				if( $this->array_contains( $this->value,$key ) )
					$str .= '<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
			$str .= '</select></td><td><input type="button" value="^" onclick="Moveup(\''.$this->name.'_selectedItems\');" /><br /><input type="button" value="v" onclick="Movedown(\''.$this->name.'_selectedItems\');"><br /></td></tr></table>';
			//$str .= '</select></td><td><a href="#"
			//onclick="javascript:Moveup(\''.$this->name.'_selectedItems\');">Move Item Up</a><br /><a href="#" onclick="javascript:Movedown(\''.$this->name.'_selectedItems\');">Move Item Down</a><br /></td></tr></table>';
			return $str;
		} // }}}
	} // }}}
*/

	 /**
	* @package disco
	* @subpackage plasmature
	*/
	 class select_jsType extends selectType // {{{
	 {
	 	var $type = 'select_js';
		var $script_url = '';
		var $type_valid_args = array('n' => 'size',
										'multiple', 
								     	'add_null_value_to_top',
										'script_url',
									); 

		function get_display() // {{{
		{
			$str  = $this->script_tag();
			$str .= parent::get_display();
			return $str;
		} // }}}
		
		function script_tag() // {{{
		{
			$s = '';
			if ( !empty( $this->script_url ) )
				$s = '<script language="JavaScript" src="'.$this->script_url.'"></script>'."\n";
			return $s;
		} // }}}
	 } // }}}
	 
	/**
	* Same as {@link select_jsType} but doesn't sort options.	
	* @package disco
	* @subpackage plasmature
	*/
	 class select_no_sort_jsType extends select_jsType // {{{
	 {
	 	var $type = 'select_no_sort_js';
		var $sort_options = false;
	 } // }}}
	 
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class sidebar_selectType extends selectType // {{{
	{
		var $type = 'sidebar_select';
		var $page = 'print_sidebar.php3?id=';
		var $frame = 'oIframe';
		
		function get_display() // {{{
		{
			$str = '<select name="'.$this->name.'" onChange="document.all.'.$this->frame.'.src=\''.$this->page.'\'
				+ this.form.'.$this->name.'.options[this.form.'.$this->name.'.selectedIndex].value">'."\n";
			foreach( $this->options as $key => $val )
			{
				$str .= '<option value="'.$key.'"';
				if ( $key == $this->value )
					$str .= ' selected="selected"';
				$str .= '>'.$val.'</option>'."\n";
			}
			$str .= '</select>'."\n";
			return $str;
		} // }}}
		
	 } // }}}	

	 ////////////////////////////////////////////
	 /// Upload Types
	 ////////////////////////////////////////////
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class upload_or_editType extends textareaType // {{{
	{
		var $type = 'upload_or_edit';

		function grab() // {{{
		// tries to grab info from textarea and/or file.
		// if a file upload, tidy's it, then puts that info into the textarea
		// for uploads, form MUST have enctype="multipart/form-data" in the <form> tag
		{
			$HTTP_VARS = $this->get_request();
			if ( isset( $HTTP_VARS[ $this->name ][ 'textarea' ] ) )
				$this->set( $HTTP_VARS[ $this->name ][ 'textarea' ] );

			// localize all global file info
			// yeah.  this code is fun.  how sweet are variable variables?  pretty sweet
			$local_vars = array(
				'file' => '',
				'file_name' => '_name',
				'file_type' => '_type',
				'file_size' => '_size'
			);

			foreach( $local_vars as $key => $suffix )
				$$key = $GLOBALS[ $this->name.'_file'.$suffix ];

			// now we handily have the file information in $file, $file_name, $file_type, and $file_size

			// see if file was uploaded...
			if ( !empty( $file ) AND !empty( $file_type ) AND !empty( $file_name ) )
			{
				// check content type - if some type of text, tidy it and set this element's value to the contents of the file
				if ( $file_type == 'text/plain' OR $file_type == 'text/html' )
				{
					$contents = implode( "\n", file( $file ) );
					//echo '<pre>'.htmlentities( $contents, ENT_QUOTES, 'UTF-8' ).'</pre>';
					$contents_tidy = tidy( $contents );
					$this->set( $contents_tidy );
				}
				else
				{
					echo 'bad file type';
				}
			}
		} // }}}
		function get_display() // {{{
		{
			$str  = '<textarea name="'.$this->name.'[textarea]" rows="'.$this->rows.'" cols="'.$this->cols.'">'.$this->value.'</textarea>';
			$str .= '<br />';
			$str .= '<strong>OR</strong> upload a file and overwrite current content';
			$str .= '<br />';
			$str .= '<input type="file" name="'.$this->name.'[file]">';
			$str .= '<input type="hidden" name="'.$this->name.'[MAX_FILE_SIZE]" value="100000">';
			return $str;
		} // }}}
	} // }}}
	
	/**
	* Very simple upload type for images.  
	* Doesn't handle the file at all - leaves the variables for the coder to decide what to do in disco's process().  Or somewhere else.
	* @package disco
	* @subpackage plasmature
	*/
	class image_uploadType extends defaultType // {{{
	{
		var $type = 'image_upload';
		var $state = 'ready';
		var $acceptable_types = array('image/jpeg','image/gif','image/pjpeg','image/png');
		var $existing_file;
		var $existing_file_web;
		var $allow_upload_on_edit;
		var $replacement_text = 'Replace image:';
		var $resize_image = true;
		var $max_width = 500;
		var $max_height = 500;
		
		var $type_valid_args = array( 'existing_file',
							 		  'existing_file_web',
									  'allow_upload_on_edit',
									  'max_height',
									  'max_width',
									  'resize_image',
									  'replacement_text',
									 ); 
		
		function additional_init_actions($args = array())
		{
			if(!empty($this->existing_file))
			{
				$this->state = 'existing';
				$this->value = $this->existing_file;
			}
		}
			
		function do_image_resize( $image ) // {{{
		{
			list($w,$h,$type) = getimagesize( $image );
			$num_to_type = array(
				1 => 'gif',
				2 => 'jpg',
				3 => 'png'
			);
			$cmd = IMAGEMAGICK_PATH . 'mogrify -geometry '.$this->max_width.'x'.$this->max_height.' -format '.$num_to_type[ $type ].' '.$image.' 2>&1';
			echo exec( $cmd, $results, $return_var );
		} // }}}
		function need_to_resize( $image ) // {{{
		{
			// This is slightly hacky.
			// We're checking to see if the Do Not Resize element was checked, and, if it was, returning false.
			// There's probably a way to do this without checking the post variables, but it would require a significant
			// update to Disco, which I'm not ready to attempt right now. -- MR
			
			if(empty($_POST['do_not_resize']))
			{
				list( $width, $height ) = getimagesize( $image );
				if( $this->resize_image AND ( $width > $this->max_width OR $height > $this->max_height ) )
					return true;
				else
					return false;
			}
			else
				return false;
		} // }}}
		function grab() // {{{
		{
			$this->file = $_FILES[ $this->name ];

			// image has just been uploaded
			if( !empty( $this->file[ 'name' ] ) )
			{
				// image not uploaded correctly
				if( !is_uploaded_file( $this->file['tmp_name'] ) )
				{
					// code grabbed from comments on http://us2.php.net/is_uploaded_file
					switch($this->file['error'])
					{
						case 0: //no error; possible file attack!
							$err = "There was a problem with your upload.";
							break;
						case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
							$err = "The file you are trying to upload is too big.";
							break;
						case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
							$err = "The file you are trying to upload is too big.";
							break;
						case 3: //uploaded file was only partially uploaded
							$err = "The file you are trying upload was only partially uploaded.";
							break;
						case 4: //no file was uploaded
							$err = "You must select an image for upload.";
							break;
						default:
							$err = "There was a problem with your upload.";
							break;
					}
					if( !$this->has_error )
						$this->set_error($err);
				}
				// make sure image is in the acceptable types
				if(!file_exists($this->file['tmp_name']))
				{
					if( !$this->has_error )
						$this->set_error( 'The upload for ('.$this->file[ 'name' ].') did not work. Please try again.');
				}
				elseif( in_array( $this->file[ 'type' ], $this->acceptable_types ) )
				{
					$this->state = 'uploaded';
					$this->base_path = '';
					list(,,$type) = getimagesize( $this->file['tmp_name'] );
					$num_to_type = array( 1 => 'gif', 2 => 'jpg', 3 => 'png' );
					$this->tmp_web_path = WEB_TEMP.uniqid( 'uptmp_' ).'.'.$num_to_type[ $type ];
					$this->value = $this->tmp_full_path = $_SERVER[ 'DOCUMENT_ROOT' ].$this->tmp_web_path;
					
					move_uploaded_file( $this->file[ 'tmp_name' ], $this->tmp_full_path );
					if( $this->need_to_resize( $this->tmp_full_path ) )
					{
						// copy the original to the temp directory so that the content manager can grab the original hires later
						copy( $this->tmp_full_path, $this->tmp_full_path.'.orig' );
						$this->do_image_resize( $this->tmp_full_path );
					}
				}
				// unacceptable image type
				else
				{
					if( !$this->has_error )
						$this->set_error( 'The file that you uploaded was not an acceptable type ('.$this->file[ 'name' ].'). Acceptable types include: '.implode(', ',$this->acceptable_types) );
				}
			}
			// image has been uploaded and moved to temp directory - waiting for form to finish
			else if ( isset( $this->_request[ $this->name ][ 'tmp_file' ] ) )
			{
				$this->state = 'uploaded';
				$this->value = $this->tmp_web_path = $this->_request[ $this->name ][ 'tmp_file' ];
				$this->tmp_full_path = $_SERVER[ 'DOCUMENT_ROOT' ].$this->tmp_web_path;
			}
			// existing image
			else if ( isset( $this->existing_file ) AND !empty( $this->existing_file ) )
			{
				$this->state = 'existing';
				$this->value = $this->existing_file;
			}
		} // }}}
		function get_display() // {{{
		{
			$str = '';
			$input = '<input type="file" name="'.$this->name.'" id="'.$this->name.'Element"><input type="hidden" name="'.$this->name.'[MAX_FILE_SIZE]" value="100000">';
			if( $this->state == 'ready' )
			{
				return $input;
			}
			else if ( $this->state == 'uploaded' )
			{
				list( $w, $h ) = getimagesize( $this->tmp_full_path );
				$disk_size = round(filesize( $this->tmp_full_path )/1024, 1);
				$image_size = $w.'x'.$h.' ('.$disk_size.' Kb)';
				$str = '<span class="smallText">Uploaded Image:</span><br />';
				$str .= '<img src="'.$this->tmp_web_path.'?cb='.filemtime( $this->tmp_full_path ).'" width="'.$w.'" height="'.$h.'" /><br />';
				$str .= $image_size.'<br />';
				$str .= '<br />';
				$str .= '<div class="imageReplace"><label for="'.$this->name.'Element"><span class="smallText">'.$this->replacement_text.'</span></label><br />';
				$str .= $input;
				$str .= '<input type="hidden" name="'.$this->name.'[tmp_file]" value="'.$this->tmp_web_path.'" /></div>';
				return $str;
			}
			else if ( $this->state == 'existing' )
			{
				list( $w, $h ) = getimagesize( $this->existing_file );
				$disk_size = round(filesize( $this->existing_file )/1024, 1);
				$image_size = $w.'x'.$h.' ('.$disk_size.' Kb)';
				if( $this->allow_upload_on_edit )
				{
					$str .= '<img src="'.$this->existing_file_web.'?cb='.filemtime( $this->existing_file ).'" width="'.$w.'" height="'.$h.'" /><br />';
					$str .= $image_size.'<br />';
					$str .= '<br />';
					$str .= '<div class="imageReplace"><label for="'.$this->name.'Element"><span class="smallText">'.$this->replacement_text.'</span></label><br />';
					$str .= $input;
					$str .= '</div>';
				}
				else
				{
					$str = '<img src="'.$this->existing_file_web.'?cb='.filemtime( $this->existing_file ).'" width="'.$w.'" height="'.$h.'" /><br />';
					$str .= $image_size.'<br />';
				}

				return $str;
			}
		} // }}}
	} // }}}
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class AssetUploadType extends defaultType // {{{
	{
		var $type = 'AssetUpload';
		// possible states: ready, uploaded, existing
		var $state = 'ready';
		var $existing_file;
		var $acceptable_types = array();
		var $file_display_name;
		var $allow_upload_on_edit;
		var $max_file_size = 20971520; // max size in bytes
										// 10485760 = 10 MB
		var $type_valid_args = array( 'existing_file',
							 		  'file_display_name',
									  'allow_upload_on_edit',
									  'max_file_size',
									 );

		function additional_init_actions($args = array())
		{
			if( !empty( $this->existing_file ) )
			{
				$this->state = 'existing';
				$this->value = $this->existing_file;
			}
		} // }}}
		
		function grab() // {{{
		{
			$this->file = $_FILES[ $this->name ];
			// asset has just been uploaded
			if( !empty( $this->file[ 'name' ] ) )
			{
				// asset not uploaded correctly
				if( !is_uploaded_file( $this->file['tmp_name'] ) )
				{	
				// asset not uploaded correctly
				// code grabbed from comments on http://us2.php.net/is_uploaded_file
					switch($this->file['error'])
					{
						case 0: //no error; possible file attack!
							$err = "There was a problem with your upload.";
							break;
						case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
							$err = "The file you are trying to upload is too big.";
							break;
						case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
							$err = "The file you are trying to upload is too big.";
							break;
						case 3: //uploaded file was only partially uploaded
							$err = "The file you are trying to upload was only partially uploaded.";
							break;
						case 4: //no file was uploaded
							$err = "You must select a file for upload.";
							break;
						default:
							$err = "There was a problem with your upload.";
							break;
					}
					if( !$this->has_error ) $this->set_error($err);
				}
				
				if( !$this->has_error )
				{
				// check if asset is empty
					if ($this->file['size'] == 0) 
					{
						$this->set_error('The file you want to upload does not appear to have any contents.');
					}
					elseif($this->file['size'] > $this->max_file_size)
					{
						$this->set_error('The file you want to upload ( '.strip_tags(htmlspecialchars($this->file['name'])).' ) is too big ( Maximum file size: '.format_bytes_as_human_readable($this->max_file_size).')');
					}
				}

				if (!$this->has_error)
				{
					$this->state = 'uploaded';
					$this->base_path = '';
					$this->tmp_web_path = WEB_TEMP.$this->file['name'];
					$this->value = $this->tmp_full_path = $_SERVER[ 'DOCUMENT_ROOT' ].$this->tmp_web_path;

					move_uploaded_file( $this->file[ 'tmp_name' ], $this->tmp_full_path );
				}
			}
			// asset has been uploaded and moved to temp directory - waiting for form to finish
			else if ( isset( $_REQUEST[ $this->name.'_tmp_file' ] ) )
			{
				$this->state = 'pending';
				$this->value = $this->tmp_web_path = $_REQUEST[ $this->name.'_tmp_file' ];
				$this->tmp_full_path = $_SERVER[ 'DOCUMENT_ROOT' ].$this->tmp_web_path;
			}
			// existing image
			else if ( isset( $this->existing_file ) AND !empty( $this->existing_file ) )
			{
				$this->state = 'existing';
				$this->value = $this->existing_file;
			}
		} // }}}
		function get_display() // {{{
		{
			$str = '';
			$input = '<input type="file" name="'.$this->name.'"><input type="hidden" name="'.$this->name.'[MAX_FILE_SIZE]" value="'.$this->max_file_size.'">';
			if( $this->state == 'ready' )
			{
				return $input;
			}
			else if ( $this->state == 'uploaded' OR $this->state == 'pending' )
			{
				
				$str = '<span class="smallText">Uploaded File:</span> ('.$this->state.', '.$this->tmp_web_path.')<br />';
				$str .= (round( (filesize( $_SERVER['DOCUMENT_ROOT'].$this->tmp_web_path ) / 1024), 0 ) ).'K<br />';
				// get rid of the WEB_TEMP string from the web path to show the nice name of the file
				$str .= str_replace( WEB_TEMP, '', $this->tmp_web_path).'<br />';
				$str .= '<br />';
				$str .= '<span class="smallText">Upload a different file:</span><br />';
				$str .= $input;
				$str .= '<input type="hidden" name="'.$this->name.'_tmp_file" value="'.$this->tmp_web_path.'" />';
				return $str;
			}
			else if ( $this->state == 'existing' )
			{
				if( $this->allow_upload_on_edit )
				{
					//echo WEB_PATH . '<br />';
					//echo $_SERVER['DOCUMENT_ROOT'];
					//echo $_SERVER['DOCUMENT_ROOT'].$this->existing_file;
					//die;

					$str .= ($this->file_display_name ? $this->file_display_name : $this->existing_file ).'<br />';
					$str .= (round( (filesize( $this->existing_file ) / 1024), 0 ) ).'K<br />';
					$str .= '<br />';
					$str .= '<span class="smallText">Upload a different file:</span><br />';
					$str .= $input;
				}
				else
				{
					$str = $this->existing_file.'<br />';
				}

				return $str;
			}
		} // }}}
	} // }}}
	/**
	* @package disco
	* @subpackage plasmature
	*/
	class group extends defaultType // {{{
	// groups encapsulate a list of items and their values
	// it is essentially a group of defaultTypes
	{
		var $type = 'group';
		var $elements = array();

		function grab() // {{{
		{
			$HTTP_VARS = $this->get_request();
			foreach( $this->elements as $key => $el )
				if ( isset( $HTTP_VARS[ $el->name ] ) )
				{
					$el->set( $HTTP_VARS[ $el->name ] );
					$this->elements[ $key ] = $el;
				}
		} // }}}
		function get_display() // {{{
		{
			$str = '';
			foreach( $this->elements as $key => $el )
				$str .= $el->get_display();
			return $str;
		} // }}}
	} // }}}

	////////////////////////////////////////////
	/// Date Types
	////////////////////////////////////////////
	/**
	* A plasmature element to represent the date/time as multiple text fields.
	* This element displays itself as multiple HTML inputs, but it is treated within plasmature and disco as a single 
	* element with a single value.  
	*
	* @package disco
	* @subpackage plasmature
	*
	* @todo Make a date interface to inherit from.  
	* @todo Add timezone functionality.  
	* @todo Add display order functionality for fields
	* @todo Add ways to change the delimiters between fields  
	*/
	class textDateTimeType extends textType // {{{
	{
		var $type = 'textDateTime';
		var $type_valid_args = array(   'prepopulate', 
										#'date_format',
										'year_max',
										'year_min', 
									);
		
		var $prepopulate = false;
		
		//note: this isn't a valid arg because it would mess up some of the checks in get() if it was.
		var $date_format = 'Y-m-d H:i:s';
		
		var $year_max;
		var $year_min;
		
		var $year;
		var $month;
		var $day;
		var $hour;
		var $minute;
		var $second;
		var $ampm;
				
		/**
		* All datetime portions.  This array in child classes define which fields we want to capture.
		* @var array
		*/
		var $use_fields = array(
			'month',
			'day',
			'year',
			'hour',
			'minute',
			'second',
			'ampm',
		);		
		
		/**
		*  Sets the value to the current datetime if the value is empty and {@link prepopulate} is set.
		*/
		function additional_init_actions($args = array())
		{
			if( !$this->get() AND !empty( $this->prepopulate) )
			{
				$this->set( time() );
			}
		}
		
		function set( $value ) // {{{
		{	
			//get_unix_timestamp now uses carl_ date functions, so this will be compatible with 64-bit timestamps
			$value = get_unix_timestamp( $value );		
			
			$date_pieces = array( 'year' => false,
								  'month' => false, 
								  'day' => false,
								  'hour' => false,
								  'minute' => false,
								  'second' => false,
								  'ampm' => false,
								 );
			
			if( !empty($value) && $value != -1 )
				list( $date_pieces['year'], $date_pieces['month'], $date_pieces['day'], $date_pieces['hour'], $date_pieces['minute'], $date_pieces['second'], $date_pieces['ampm'] ) = explode('-', carl_date('Y-n-j-g-i-s-a', $value));
			
			//only set the fields that we're actually using
			foreach($this->use_fields as $field_name)
			{
				#if(isset($date_pieces[$field_name]))
				if($date_pieces[$field_name])
					$this->$field_name = $date_pieces[$field_name];
			}
		} // }}}
				
		function grab() // {{{
		{
			$request = $this->get_request();
			$fields_that_have_values = array();
			// loop through fields to capture and capture them
			foreach( $this->use_fields AS $field )
			{
				$this->$field = isset( $request[ $this->name ][ $field ] ) ? $request[ $this->name ][ $field ] : '';
				if(!empty($this->$field) && $field != 'ampm')
					$fields_that_have_values[] = $field;
			}
			
			//run error checks if any values have been entered for this date.
			if(!empty($fields_that_have_values))
			{
				$this->run_error_checks();
			} 
		} // }}}
		
		function run_error_checks()
		{
			$name = trim($this->display_name);
			if(empty($name))
			{
				$name = $this->name;
			}
			$name = prettify_string($name);
			
			if(in_array('year', $this->use_fields))
			{
				//check to make sure we have a year if it's in the use_fields
				//otherwise, an empty year will become 2000 and that's confusingly Evil.
				if(empty($this->year))
				{
					$this->set_error( $name.':  Please enter a year for this date.');
				}
				else
				{
					//check to make sure that the year is within valid parameters
					if(!checkdate( 1, 1, $this->year))
						$this->set_error( $name.':  This does not appear to be a valid year.' );
					elseif(!empty($this->year_min) && $this->year < $this->year_min)
						$this->set_error( $name.':  Dates before the year '.$this->year_min.' cannot be processed.' );
					elseif(!empty($this->year_max) && $this->year > $this->year_max)
						$this->set_error( $name.':  Dates after the year '.$this->year_max.' cannot be processed.'  );
				}
			}
			if( $this->month AND $this->day AND $this->year )
			{
				if( !checkdate( $this->month, $this->day, $this->year ) )
					$this->set_error(  $name.':  This does not appear to be a valid date.' );
			}
			elseif( $this->month && $this->year )
			{
				if( !checkdate( $this->month, 1, $this->year ))
					$this->set_error ($name.':  This does not appear to be a valid month/year combination.');
			}
			elseif( $this->day && (in_array('month', $this->use_fields) && !$this->month) )
			{
				$this->set_error( $name.': Please specify a month for this date.' );
			}
		}
		
		function get() // {{{
		{	
			$all_fields_empty = true;
			foreach($this->use_fields as $field_name)
			{
				if(!empty($this->$field_name) && $field_name != 'ampm')	//ampm will alaways be set, since it's a select.
				{
					$all_fields_empty = false;
					break;
				}
			}		
			
			if($all_fields_empty)
			{
				$date = false;  //used to be 0; changed so that disco notices if this has no value.
			}
			else
			{
				if(in_array('hour', $this->use_fields))
				{
					if( $this->hour == 12 )
					{
						if( $this->ampm == 'am' )
							$this->hour = 0;
						else
							$this->hour = 12;
					}
					else
					{
						// if PM is chosen, make sure to add 12 hours
						if( $this->ampm == 'pm' AND $this->hour < 12 )
							$this->hour = $this->hour+12;
					}
				}
				
				$date_format = $this->date_format;
				
				//if the day isn't set, give it a value while making the timestamp so that carl_maketime() doesn't increment back.
				//just make sure that it's not part of the date format when we're formatting the date from the timestamp.
				if(empty($this->day))
				{
					$this->day = 1;
					$date_format = str_replace('-d', '', $date_format);
				}
				
				//if the month isn't set, give it a value while making the timestamp so that carl_maketime() doesn't increment back.
				//just make sure that it's not part of the date format when we're formatting the date from the timestamp.
				if(empty($this->month))
				{
					$this->month = 1;
					$date_format = str_replace('-m', '', $date_format);
				}			
			
				$timestamp = carl_mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
				$date = carl_date( $date_format, $timestamp );
			}			
			
			return $date;
		} // }}}
		
		function debug_display() // {{{
		{
			foreach( $this AS $var => $value )
			{
				if( in_array( $var, array('year','month','day','hour','minute','second','ampm') ) )
					echo $var.' = '.$value.'<br />';
			}
		} // }}}
		
		function get_display() // {{{
		{
			$str = '';
			
			foreach($this->use_fields as $field_name)
			{
				$get_val_method = 'get_'.$field_name.'_value_for_display';
				if(method_exists($this, $get_val_method))
					$display_val = $this->$get_val_method();
				else
					$display_val = $this->$field_name;
				
				$display_method = 'get_'.$field_name.'_display';
				if(method_exists($this, $display_method))
					$str .= $this->$display_method($display_val);
				else
					trigger_error($field_name.' is in $use_fields, but no display method exists for it');
			}
			
			return $str; 
		} // }}}
		
		function get_value_for_month_display()
		{
			if((int)$this->month)
				return (int)$this->month;
		}
		function get_value_for_day_display()
		{
			if((int)$this->day)
				return (int)$this->day;
		}
		function get_value_for_year_display()
		{
			if((int)$this->year)
				return (int)$this->year;
		}
		function get_value_for_hour_display()
		{
			//convert from 24-hr time to 12-hr time, if applicable
			if( $this->hour > 12 )
				$h = $this->hour - 12;
			//if the hour is 0 but ampm is set, then we really mean 12
			//if there really isn't a value, then ampm will also be empty.
			elseif(empty($this->hour) && !empty($this->ampm))
				$h = 12;
			else
				$h = $this->hour;
			$str .= $this->get_hour_display($h);
		}
		
		function get_month_display($month_val = '')
		{
			$str = '';
			$str .= '<input type="text" size="2" maxlength="2" id="'.$this->name.'monthElement" name="'.$this->name.'[month]" value="'.htmlspecialchars($month_val, ENT_QUOTES).'" />';
			return $str;
		}
		
		function get_day_display($day_val = '')
		{
			$str = '';
			$str .= ' / ';
			$str .= '<input type="text" size="2" maxlength="2" id="'.$this->name.'dayElement" name="'.$this->name.'[day]" value="'.htmlspecialchars($day_val, ENT_QUOTES).'" />';
			return $str;
		}
		
		function get_year_display($year_val = '')
		{
			$str = '';
			$str .= ' / ';
			$str .= '<input type="text" size="4" maxlength="4" id="'.$this->name.'yearElement" name="'.$this->name.'[year]" value="'.htmlspecialchars($year_val, ENT_QUOTES).'" />';
			return $str;
		}
		
		function get_hour_display($hour_val = '')
		{
			$str = '';
			$str .= '&nbsp;&nbsp; at ';
			$str .= '<input type="text" size="2" maxlength="2" id="'.$this->name.'hourElement" name="'.$this->name.'[hour]" value="'.htmlspecialchars($hour_val, ENT_QUOTES).'" />';
			return $str;
		}
		
		function get_minute_display($minute_val = '')
		{
			$str = '';
			$str .= ' : ';
			$str .= '<input type="text" size="2" maxlength="2" id="'.$this->name.'minuteElement" name="'.$this->name.'[minute]" value="'.htmlspecialchars($minute_val, ENT_QUOTES).'" />';	
			return $str;
		}
		
		function get_second_display($second_val = '')
		{
			$str = '';
			$str .= ' : ';
			$str .= '<input type="text" size="2" maxlength="2" id="'.$this->name.'secondElement" name="'.$this->name.'[second]" value="'.htmlspecialchars($second_val, ENT_QUOTES).'" />';
			return $str;
		}
		
		function get_ampm_display($ampm_val)
		{
			$str = ' ';
			$str .= '<select id="'.$this->name.'ampmElement" name="'.$this->name.'[ampm]">';
			$str .= '<option value="am"'.($ampm_val == 'am' ? ' selected="selected"': '').'>AM</option>';
			$str .= '<option value="pm"'.($ampm_val == 'pm' ? ' selected="selected"': '').'>PM</option>';
			$str .= '</select>';
			return $str;
		}
		
		function get_cleanup_rule()
		{
			return array( 'function' => 'turn_into_array' );
		}
	 } // }}}

	/**
	* Identical to {@link textDateTimeType} except that it only uses month, day, and year.
	*/
	class textDateType extends textDateTimeType { // {{{
		var $type = 'textDate';
		var $date_format = 'Y-m-d';
		var $use_fields = array( 'month', 'day', 'year');
	} // }}}
	
	/**
	* @package disco
	* @subpackage plasmature
	*/
	 class textDateTime_jsType extends textDateTimeType // {{{
	 {
	 	var $type = 'textDateTime_jsType';
		var $script_url = '';
		var $type_valid_args = array( 'script_url' );
			
		function get_display() // {{{
		{
			$str = parent::get_display();
			$str .= $this->script_tag();
			return $str;
		} // }}}
		function script_tag() // {{{
		{
			$s = '';
			if ( !empty( $this->script_url ) )
				$s = '<script language="JavaScript" src="'.$this->script_url.'"></script>'."\n";
			return $s;
		} // }}}
	 } // }}}
	
	/**
	* A date type that includes a month select and a year text field.
	* @package disco
	* @subpackage plasmature
	*/
	class selectMonthTextYearType extends textDateTimeType
	{
		var $type = 'selectMonthTextYear';
		var $date_format = 'Y-m';
		var $use_fields = array ('month', 'year');
		var $month_element;
		var $month_args = array('date_format' => 'F');
		var $type_valid_args = array( 'month_args' );		
		
		function additional_init_actions($args = array())
		{
			parent::additional_init_actions();
			$this->init_month_element();
		}
		
		function init_month_element()
		{
			//set up the month plasmature element
			$this->month_element = new monthType;
			$this->month_element->set_request( $this->_request );
			$this->month_element->set_name( $this->name.'[month]' );
			if(empty($this->month_args['date_format']))
				$this->month_args['date_format'] = 'F';
			$this->month_element->init($this->month_args);
		}
		
		function set( $value ) // {{{
		{
			$value = get_unix_timestamp( $value.'-01' );
			if( !empty($value) && $value != -1 )
			{
				list( $this->year, $this->month) = explode('-',carl_date($this->date_format, $value));
			}
			else
			{
				$this->year = '';
				$this->month = '';
			}
		} // }}} 
				
		function get_month_display($month_val = '')
		{
			$this->month_element->set($month_val);
			return $this->month_element->get_display();
		}		
	}
	
	/**
	* A date type that includes a month select and a year select.
	* This uses {@link monthType} and {@link yearType} objects.
	* @package disco
	* @subpackage plasmature
	*/
	class selectMonthYearType extends selectMonthTextYearType
	{
		var $type = 'selectMonthYear';
		var $type_valid_args = array( 'year_args');
		var $year_element;
		var $year_args = array();
		
		/**
		* Instantiate a new {@link monthType} element and {@link yearType} element.
		*/ 
		function additional_init_actions($args = array())
		{
			parent::additional_init_actions();
			$this->init_year_element();
		}
		
		function init_year_element()
		{
			//set up the year plasmature element
			$this->year_element = new yearType;
			$this->year_element->set_request( $this->_request );
			$this->year_element->set_name( $this->name.'[year]' );
			if(empty($this->year_args['end']) || (isset($this->year_args['end']) && $this->year_args['end'] > $this->year_max))
				$this->year_args['end'] = $this->year_max;
			if(empty($this->year_args['start']) || (isset($this->year_args['start']) && $this->year_args['start'] < $this->year_min))
				$this->year_args['start'] = $this->year_min;
			if(empty($this->year_args['end']))
				unset($this->year_args['end']);
			if(empty($this->year_args['start']))
				unset($this->year_args['start']);
			$this->year_element->init($this->year_args); 
		}
					
		function get_year_display($year_val = '')
		{
			$this->year_element->set($year_val);
			return ' / '.$this->year_element->get_display();
		}
	}
	 

	/*
	class dateType extends defaultType // {{{
	// prefers yyyy-mm-dd
	{
		var $type = 'date';
		var $year;
		var $month;
		var $day;

		function dateType() // {{{
		{
// 			$this->set( date('Y-m-d',time() ) );
		} // }}}
		function init( $args = array() ) // {{{
		{
			parent::init($args);
			//include_once( 'plasmature/date.php3' );
			if( !empty( $args ) AND !empty( $args[ 'value' ] ) )
				$this->set( $args[ 'value' ] );
			//if( empty( $args ) OR empty( $args[ 'default_is_empty'] ) )
				//$this->set( date('Y-m-d',time() ) );
		} // }}}
		function get() // {{{
		{
			return $this->year.'-'.$this->month.'-'.$this->day;
		} // }}}
		function set( $value ) // {{{
		// set has the fun job of identifying the type of date sent to it and then munging it to the correct format
		{
			// month-day-year format
			if ( preg_match( '/([0-9]{2})-([0-9]{2})-([0-9]{4})/', $value, $matches ) )
			{
				$this->year = $matches[3];
				$this->month = $matches[1];
				$this->day = $matches[2];
			}
			// mysql date format
			else if ( preg_match( '/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $value, $matches ) )
			{
				$this->year = $matches[1];
				$this->month = $matches[2];
				$this->day = $matches[3];
			}
			// MySQL timestamp
			else if ( preg_match( '/[0-9]{14}/', $value ) )
			{
				$this->year = substr( $value, 0, 4 );
				if ( $y < 1900 OR $y > 2100 )
					echo 'we probably have a date problem here';
				$this->month = substr( $value, 4, 2 );
				$this->day = substr( $value, 6, 2 );
			}
			else if ( preg_match( '//', $value ) )
			{
			}
			else die( 'date problem.  value sent to set() did not match a known format' );
		} // }}}
		function grab() // {{{
		{
			$this->month = !empty( $this->_request[ $this->name ][ 'month' ] ) ? $this->_request[ $this->name ][ 'month' ] : '';
			$this->day = !empty( $this->_request[ $this->name ][ 'day' ] ) ? $this->_request[ $this->name ][ 'day' ] : '';
			$this->year = !empty( $this->_request[ $this->name ][ 'year' ] ) ? $this->_request[ $this->name ][ 'year' ] : '';

			if( $this->month AND $this->day AND $this->year )
				if( !checkdate( $this->month,$this->day, $this->year ) )
					$this->set_error( 'Invalid date - please check ' );
		} // }}}
		function get_display() // {{{
		{
			$str  = '<input type="text" size="2" name="'.$this->name.'[month]" value="'.((int)$this->month ? (int)$this->month : '').'" />';
			$str .= ' / ';
			$str .= '<input type="text" name="'.$this->name.'[day]" size="2" value="'.((int)$this->day ? (int)$this->day : '').'" />';
			$str .= ' / ';
			$str .= '<input size="4" type="text" name="'.$this->name.'[year]" value="'.((int)$this->year ? (int)$this->year : '').'" />';
			return $str;
		} // }}}
	} // }}}
	class textDateType extends dateType // {{{
	{
		var $type = 'textDate';
	} // }}}
	class textDateOnlyType extends textDateType // {{{
	{
		var $type = 'textDateOnly';
	} // }}}
	*/
?>
