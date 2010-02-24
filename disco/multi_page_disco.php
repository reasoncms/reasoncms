<?php
	include_once( 'disco.php' );
	/**
	 * Multipage Disco is an extension of disco designed to be run over multiple pages.
	 * This is useful for large forms where putting the whole form on one page would
	 * be impractical, or for things where you might request different things from the user 
	 * based on previous input.
	 *
	 * This does not enforce the saving of Data in any way.  Instead, that is left to extensions
	 * of the class to decide how data will be stored from page to page.
	 * @author Brendon Stanton
	 * @package disco
	 * @abstract
	 */
	class multi_page_disco extends Disco
	{
		/**#@+
	 	 * @access public
	 	 */
		/**
		 * This will be overloaded in any class that will be implemented.
		 * It should contain an array where the keys are page names and the values are arrays of 
		 * elements which are designed the same way as in Disco::elements.  An example would
		 * look like this:
		 * <code>
		 * $page_elements = array( 'start' => array( 'input1', 'two' => 'text'),
		 * 			   'end' => array( 'finishme' )
		 *			 );
		 * </code>
		 */
		var $page_elements;
		var $page_actions = array();
		var $other_actions = array();
		var $start_page;
		/**#@-*/
		
		//////////////////////////////////////////////////
		// OVERLOADABLE METHODS
		//////////////////////////////////////////////////
		
		function pre_show_form() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'pre_show_form_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function post_show_form() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'post_show_form_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function on_first_time() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'on_first_time_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function on_every_time() // {{{
		{
			parent::on_every_time();
			$methods = get_class_methods( $this );
			$fun = 'on_every_time_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function pre_error_check_actions() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'pre_error_check_actions_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function run_error_checks() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'run_error_checks_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function process() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'process_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function finish() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'finish_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
		} // }}}
		function where_to() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'where_to_' . $this->page;
			if( in_array( $fun , $methods ) )
				return $this->$fun();
			return false;
		} // }}}
		function show_form() // {{{
		{
			$methods = get_class_methods( $this );
			$fun = 'show_form_' . $this->page;
			if( in_array( $fun , $methods ) )
				$this->$fun();
			else
				parent::show_form();
		} // }}}

		function init($externally_set_up = false) // {{{
		{
			// make sure init() only gets run once.  This allows a script to init the form before running.
			if ( !isset( $this->_inited ) OR empty( $this->_inited ))
			{
				// are we first timing it?
				if( empty( $this->_request ) ) $this->_request = $_REQUEST;
				$HTTP_VARS = $this->_request;
				$this->_first_time = (isset( $HTTP_VARS[ 'submitted' ] ) AND !empty( $HTTP_VARS[ 'submitted' ] )) ? false : true;
				$this->page = isset( $HTTP_VARS[ 'page' ] ) ? $HTTP_VARS[ 'page' ] : $this->start_page;	
				if( empty( $this->page ) )
					$this->_internal_error( 'The current page is not set properly, make sure var $start_page is defined' );
				$this->set_page_actions();
				// determine action that was chosen
				$this->chosen_action = '';
				foreach( $HTTP_VARS AS $key => $val )
				{
					if( preg_match( '/__button_/' , $key ) )
						$this->chosen_action = preg_replace( '/__button_/' , '' , $key );
				}
				// elements should not empty
				if ( empty( $this->elements ) AND empty( $this->_elements ) AND empty( $this->page_elements[ $this->page ] ) )
					$this->_internal_error( 'Your form needs to have some elements.  Create an elements array. (Ex: var $elements = array( \'item_one\', \'item_two\'); )' );
				
				/*if ( !isset( $this->required ) OR !is_array( $this->required ) )
					$this->required = array(); */
				
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
							$element = $key;
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
							$element = $value;
							$type = '';
						}
						$this->add_element( $element, $type, $args );
					}
				}
				if ( !empty( $this->page_elements[ $this->page ] ) )
				{
					foreach( $this->page_elements[ $this->page ] AS $key => $value )
					{
						// assume no extra arguments
						$args = array();
						if ( is_string( $key ) )
						{
							$element = $key;
							if ( is_array( $value ) )
							{
								if (isset($value['type']))
								{
									$type = $value['type'];
									unset($value['type']);
								} else {
									$type = '';
								}
								$args = $value;
							}
							else
								$type = $value;
						}
						else
						{
							$element = $value;
							$type = '';
						}
						$this->add_element( $element, $type, $args );
					}
				}
				$this->add_element( 'page' , 'hidden' );
				$this->set_value( 'page' , $this->page );
				
				$this->set_previous_states( $HTTP_VARS );
				$this->_inited = true;
			}
		} // }}}
		function set_page_actions() // {{{
		{
			if( !empty( $this->page_actions[ $this->page ] ) )
				$this->actions = array_merge( $this->page_actions[ $this->page ] , $this->other_actions );
			else
				$this->actions = array_merge( $this->actions , $this->other_actions );
		} // }}}
		function remove_element( $element ) // {{{
		{
			parent::remove_element( $element );
			$page = empty( $this->page ) ? $this->_request[ 'page' ] : $this->page;
			$page = empty( $page ) ? $this->start_page : $page;

			if( isset( $this->page_elements[ $page ][ $element ] ) )
				unset( $this->page_elements[ $page ][ $element ] );
		} // }}}
		function set_previous_states( $vars ) // {{{
		{
			foreach( $vars AS $k => $v )
			{
				if( $k != 'page' )
				{
#					if( !isset( $this->_elements[ $k ] ) )
					if(!$this->_is_element($k))
					{
						$this->add_element( $k , 'hidden' );
						$this->set_value( $k , $v );
					}
				}
			}
		} // }}}

		function make_link( $params = '' ) // {{{
		{
			$default_args = array();

			foreach($this->get_element_names() as $element_name)
			{
				$default_args[$element_name] = $this->get_value($element_name);
			}
			
			if( isset( $default_args[ 'submitted' ] ) )
				unset( $default_args[ 'submitted' ] );

			$params = array_merge( $default_args, $params );
			if( empty( $params ) )
				$params = array();
			$link = '';
			foreach( $params AS $key => $val )
			{
				if( isset( $default_args[ $key ] ) OR  !empty( $val ) ) //we need to get anything through that is in default args or has a value
				{
					$link .= '&amp;'.$key.'='.$val;
				}
			}
			$link = substr( $link, strlen( '&amp;' ) );
	
			return $_SERVER['PHP_SELF'].'?'.$link;
		} // }}}
	}
?>
