<?php
/**
 * Disco Multi Page Controller
 *
 * An attempt at creating a multi page form controller.
 *
 * @author Dave Hendler
 * @author Mark Heiman
 * @since 2005-02-01
 * @package disco
 * @subpackage controller
 */

/**
 * The Form Step class
 */
include_once( 'controller_step.php' );

/**
 * The Step class that the controller depends upon
 */
define( 'FC_STEP_CLASS', 'FormStep' );

/**
 * Multi Page Form Controller
 *
 * @author Dave Hendler
 * @package disco
 * @subpackage controller
 */
class FormController
{
	/**
	 * Storage of individual forms
	 *
	 * 'DiscoFormName' => [DiscoForm object], ...
	 *
	 * @access private
	 * @see FormStep
	 * @var array of objects 
	 */
	var $forms;
	
	/**
	 * possible transitions between forms
	 *
	 * format is:
	 *
	 * <code>
	 * array(
	 *	FORM_NAME => array(
	 *		'next_steps' => array(
	 *			 FORM_NAME => array(
	 *				'label' => LABEL,
	 *			),
	 *			* more steps *
	 *		),
	 *		'step_decision' => array(
	 *			'type' => DECISION_TYPE,
	 *			'method' => METHOD_NAME,
	 *		),
	 *		'start_step' => true|false,
	 *		'final_step' => true|false,
	 *		'back_button_text' => 'Go Back',
	 *		'final_button_text' => 'Submit this form',
	 *		'display_name' => 'Page One',
	 *	),
	 *   * more forms *
	 * );
	 *
	 * </code>
	 *
	 * FORM_NAME is the name of an existing class that extends from Disco.
	 *
	 * LABEL is the label to be applied to the user choice for that form.
	 *
	 * The 'back_button_text' attribute lets the controller know what text to use when a back button refers to the
	 * defined step.  Kind of confusing.  The text does not appear on that step itself.  Instead, if any step in the
	 * form will be going back to the step that we are defining, it will use this text.
	 *
	 * DECISION_TYPE can either be 'user' or 'method'.
	 *     - 'user' is if you want the user to make the decision about which
	 *       step to go to next.
	 *     - 'method' is a method of the controller class to call to determine
	 *       where to go based on form info.  these methods are documented later.
	 *
	 * The 'final_label' attribute is used to name the final button on the form.  This is only looked at if the step the
	 * user is on is either a de facto finish state or if the transitions array specifically uses the 'final_step'
	 * attribute.
	 *
	 * The 'display_name' label is used when generating navigation.
	 *
	 * @access private
	 * @var mixed nested array of info
	 */
	var $transitions;
	/**
	 * Whether or not to show back buttons; if you're allowing linking to arbitrary
	 * steps in the form, the back buttons will break, so you should turn them off.
	 * If the back and next buttons are your only navigation, leave this on.
	 */
	var $show_back_button = true;
	/**
	 * Default text for the next/continue button
	 */
	var $default_next_text = 'Continue';
	/**
	 * Default text for the back button
	 */
	var $default_back_text = 'Go Back';
	/**
	 * Default text for the submit/final button
	 */
	var $default_final_text = 'Submit Form';
	/**
	 * Message to show user if a session times out.
	 */
	var $sess_timeout_msg = '<p class="callOut">You\'ve been returned to the start of the form, probably because your web browser was idle for a long time. Sorry for any inconvenience.</p>';
	/**
	 * Message if cookies are not enabled. (Not currently used!)
	 */
	var $no_cookie_msg = '<p class="callOut">It appears that you do not have cookies enabled.  Use of this form requires the use of cookies.  Please enable them and reload this page.</p>';
	/**
	 * Name of the class to be used for PHP session handling
	 */
	var $session_class = 'FormControllerSession';
	/**
	 * Session object for current session
	 */
	var $session;
	/**
	 * Session name for current session
	 */
	var $session_name = 'DISCO_SESSION';

	/**#@+
	 * @access private
	 */
	
	/**
	 * up to date path of where user has travelled
	 * @var array
	 */
	var $_path = array();
	
	/**
	 * has the controller init()ed?
	 * @var bool
	 */
	var $_inited = false;
	
	/**
	 * internal copy of request variables available
	 */
	var $_request;
	
	/**
	 * internal copy of session form data
	 */
	var $_form_data;
	
	/**
	 * Map of variables to forms
	 *
	 * <code>
	 * example:  form BillingForm uses CCNumber.  entry would look like:
	 *   'CCNumber' => 'BillingForm'
	 * </code>
	 * @var array
	 */
	var $_vars = array();
	
	/**
	 * reverse lookup array of vars.  structured like so:
	 * <code>
	 * 'form1' => array( 'var1','var2' ),
	 * 'form2' => array( 'var3', 'var4' ),
	 * </code>
	 * @var array
	 */
	var $_form_vars = array();
	
	/**
	 * step of the process the user is on.
	 * @var string
	 */
	var $_current_step;
	
	/**
	 * is this the first time the FormController has been run?
	 * @var bool
	 */
	var $_first_run = true;
	
	/**
	 * simple array to store names of form steps in the order they were added.
	 *
	 * this is used as a last resort to determine step order
	 *
	 * @var string
	 */
	var $_form_order_added = array();
	
	/**
	 * Request variable name that holds the current step.  Generally passed in the URL as a GET var
	 * @var string
	 */
	var $_step_var_name = '_step';
	
	/**
	 * key for sessioned form data
	 * @var string
	 */
	var $_data_key = '_fc_data';
	
	/**
	 * key for sessioned path data in
	 * @var string
	 */
	var $_path_key = '_fc_path';

	/**
	 * determine the base URL for the form once and use at other times
	 * @var string
	 */
	var $_base_url;
	
	/**
	 * Without this, the start of a form will clobber the text of a query string on $form->run();
	 * @var boolean
	 * @access public
	 */
	var $preserve_query_string = true;
	
	/**
	 * Used to store the name of the first step
	 * @var string
	 */
	var $_start_step;
	/**
	 * Stores name of final step
	 * @var string
	 */
	var $_final_step;
	/**
	 * Bool that contains whether a session cookie exists
	 */
	var $_session_existed;
	/**
	 * Bool that contains whether data should be cleared from the session when the
	 * final step is submitted,
	 */
	var $clear_form_data_on_finish = true;
	/**
	 * Bool that contains whether the entire session should be destroyed when the
	 * final step is submitted,
	 */
	var $destroy_session_on_finish = false;
	/**
	 * If you want to allow the form to be entered at any step, rather than requiring
	 * that the user start from the beginning, set this to true
	 */
	var $allow_arbitrary_start = false;
	
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * Does nothing.
	 * @access public
	 */
	function FormController() // {{{
	{
	} // }}}
	
	//=========================================//
	//======== PUBLIC RUNNABLE METHODS ========//
	//=========================================//
	
	/**
	 * Set up the controller
	 * @access public
	 * @return void
	 */
	function init() // {{{
	{
		if( !$this->_inited )
		{
			$url_parts = parse_url( get_current_url() );
			// Use https if possible
			if (HTTPS_AVAILABLE && $url_parts[ 'scheme' ] != 'https')
			{
				header('Location: '.get_current_url( 'https' ) );
				exit;
			}
			
			$this->_base_url = $url_parts[ 'scheme' ].'://'.$url_parts['host'].$url_parts['path'];

			if (empty($this->_request)) $this->set_request();
			
			$this->session = new $this->session_class;
			$this->session->set_session_name($this->session_name);
			
			// determine if this is a first run or not, start session
			if (!$this->session->exists() && !$this->session->has_started())
			{
				$this->session->start();
			}

			if ($this->session->has_started() || $this->session->exists())
			{	
				if( !$this->session->get($this->_data_key . '_running') )
				{
					$this->_first_run = true;
					$this->session->set($this->_data_key . '_running', true);
				}
				else
				{
					$this->_first_run = false;
					if( $this->session->get($this->_path_key) )
						$this->_path = $this->session->get($this->_path_key);
					else
						$this->_path = array();
				}
				$this->_inited = true;
			} else {
				trigger_error( 'FormController Error: Failed to start session');	
			}

			// build the master list of form to variable
			foreach( array_keys( $this->forms ) AS $name )
			{
				$form =& $this->forms[ $name ];
				$form->set_controller( $this );
				$form->init();
				foreach( $form->get_element_names() AS $el )
				{
					if( !empty( $this->_vars[ $el ] ) )
					{
						trigger_error( sprintf('FormController Error: Duplicate variable on two steps (%s)', $el ));
					}
					else
					{
						$this->_vars[ $el ] = $name;
						if( empty( $this->_form_vars[ $name ] ) ) $this->_form_vars[ $name ] = array();
						$this->_form_vars[ $name ][] = $el;
					}
				}
			}
			$this->_populate_step_data();
		}
	} // }}}
	/**
	 * Figure out where the form is currently.
	 *
	 * Looks first to see if this is the first time the controller has been run.  If so, we need to find a start state
	 * in the transition graph or pick the first.  Otherwise, the controller looks to the request to see if a step class
	 * has been passed through.
	 *
	 * @access private
	 * @return void
	 */
	function determine_step() // {{{
	{
		// first time, no step name
		if( empty( $this->_request[ $this->_step_var_name ] ) )
		{
			$this->_current_step = $this->_get_start_step();
		}
		else
		{
			// If this looks like the first time we've hit the page, but there's a 
			// request for a form step that isn't the first one, we've probably had
			// the session expire out from under us.
			if( $this->_first_run && !$this->allow_arbitrary_start)
			{
				if( !empty( $this->_request[ $this->_step_var_name ] ) )
				{
					if ($this->_request[ $this->_step_var_name ] != $this->_get_start_step())
					{
						$this->session->set( 'timeout_msg' , $this->sess_timeout_msg);		
					}
				}
			}
			// if not the first time, figure out which step we are on
			else
			{
				// check request for step name
				if( !empty( $this->_request[ $this->_step_var_name ] ) )
				{
					// check forms to see if this step exists
					$cs = $this->_request[  $this->_step_var_name ];
					if( empty( $this->forms[ $cs ] ) )
					{
						trigger_error($cs.' is not a valid form step.');
					}
					else
					{
						$this->_current_step = $cs;
					}
				}
			}
		}
		
		if( empty( $this->_current_step ) )
		{
			$this->_current_step = $this->_get_start_step();
		}
		
		$this->validate_step();
	} // }}}
	/**
	 * Returns the name of the start step
	 *
	 * As a side effect, populates $this->_start_step.  Probably best to this method.
	 * @return string name of final step
	 * @access private
	 */
	function _get_start_step() // {{{
	{
		if( empty( $this->_start_step ) )
		{
			// find first form / start state
			foreach( $this->transitions AS $name => $trans )
			{
				if( !empty( $trans[ 'start_step' ] ) )
				{
					if( empty( $this->_start_step ) )
					{
						$this->_start_step = $name;
					}
					else
					{
						trigger_error('Two start states found.  Using the first one: '.$this->_start_step);
					}
				}
			}
			if( empty( $this->_start_step ) )
			{
				$this->_start_step = $this->_form_order_added[ 0 ];
			}
		}
		return $this->_start_step;
	} // }}}
	/**
	 * returns the name of the final step
	 *
	 * As a side effect, populates $this->_final_step
	 * @return string Name of the final step
	 * @access private
	 */
	function _get_final_step() // {{{
	{
		if( empty( $this->_final_step ) )
		{
			foreach( $this->transitions AS $name => $trans )
			{
				if( !empty( $trans[ 'final_step' ] ) )
				{
					if( empty( $this->_final_step ) )
					{
						$this->_final_step = $name;
					}
					else
					{
						trigger_error('More than one final state found.  Using the first one: '.$this->_final_step);
					}
				}
			}
			if( empty( $this->_final_step ) )
			{
				$this->_final_step = $this->_form_order_added[ count( $this->_form_order_added ) - 1 ];
			}
		}
		return $this->_final_step;
	} // }}}
	 
	/**
	 * Return the name of the current step
	 * @access public
	 * @return string
	 */
	function get_current_step() // {{{
	{
		if (!isset($this->_current_step))
		{
			$this->determine_step();
		}
		return $this->_current_step;
	}

	/**
	 * Return the name of the previous step in the path
	 * @access public
	 * @return string
	 */
	function get_previous_step($back=1) // {{{
	{
		if (!empty($this->_path))
		{
			if (count( $this->_path ) >= $back)
				return $this->_path[ count( $this->_path ) - $back ];
		}
	}

	/**
	 * Make sure that _current_step is a valid form and that it makes sense in the flow of the form.
	 * @return void
	 * @access private
	 */
	function validate_step() // {{{
	{
		if( empty( $this->_current_step ) )
		{
			trigger_error('Current step is empty.  Bad.');
			return;
		}
		if( empty( $this->forms[ $this->_current_step ] ) )
		{
			trigger_error('Current step has no defined form.');
			return;
		}
	} // }}}
	/**
	 * @todo write this method
	 */
	function intercept_post() // {{{
	{
	} // }}}

	
	/**
	 * Updates the session with all new/changed data from the current form step.
	 * You can pass your own set of form var names, for cases where you have dynamic
	 * elements on the form.
	 * @access public
	 * @return void
	 */
	function update_session_form_vars($vars = null) // {{{
	{
		$no_session = array();
		foreach( $this->forms AS $f )
		{
			$no_session = array_merge( (array) $no_session, (array) $f->no_session );
		}
		
		if (!is_array($vars))
		{
			$vars = $this->_form_vars[ $this->_current_step ];
		}
		
		foreach( $vars AS $var )
		{
			if( !in_array( $var, $no_session ) )
			{
				$this->set_form_data($var,  $this->forms[ $this->_current_step ]->get_value( $var ));
			}
		}
	} // }}}


	/**
	 * Run the Controller
	 * @access public
	 * @return void
	 */
	function run() // {{{
	{

		if (reason_maintenance_mode() && !reason_check_privs('db_maintenance'))
		{
			echo '<div id="form">';
			echo '<p><em>This web site is currently in maintenance mode, forms are temporarily disabled. Please try again later.</em></p>';
			echo '</div>';
			exit;
		}
		$this->determine_step();

		if( empty( $this->_request[ $this->_step_var_name ] ) )
		{
			if ($this->preserve_query_string)
			{
				$redirect = carl_make_redirect(array($this->_step_var_name => $this->_current_step));
				header('Location: ' . $redirect);
				exit;
			} else {
				header('Location: ' . $this->_base_url . '?' . $this->_step_var_name . '=' . $this->_current_step);
				exit;
			}
		}
		elseif( !empty( $this->_session_existed ) AND $this->_first_run )
		{
			// session timed out.  we know this because the cookie or SID exists but PHP could not find a
			// session file.
			trigger_error('Session has expired', E_USER_NOTICE);
			$_SESSION[ 'timeout_msg' ] = true;
			
			//! This should be a little more descriptive if we're going to be timing out more often, don't you think? Maybe preserve cur_module? Or site_id if they exist?
			header('Location: '.$this->_base_url.'?'.$this->_step_var_name.'='.$this->_get_start_step());
			exit;
		}
		elseif ($this->_request[ $this->_step_var_name ] != $this->_current_step )
		{
			// This error is no longer being triggered because it's not really an error.
			//trigger_error( 'Strange behavior: requested multipage form step not the same as the actual step being displayed. Probably due to session timeout. Client browser headered to start of form.',E_USER_NOTICE );
			header('Location: '.$this->_base_url.'?'.$this->_step_var_name.'='.$this->_get_start_step() );
			exit;
		}
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// intercept posts, store in session, redirect to a new page, send disco the sessioned _POST
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$this->intercept_post();
		
		$final_step = ( $this->_current_step == $this->_get_final_step() );
		
		// get the actual object that has already been instantiated.
		// we know current step is good since validate_step has run.
		$f =& $this->forms[ $this->_current_step ];
		$f->set_request( $this->_request );
		$actions = array();
		if( !empty( $this->transitions[ $this->_current_step ] ) )
		{
			$trans = $this->transitions[ $this->_current_step ];
			if( !empty( $trans[ 'step_decision' ] ) )
			{
				$trans_type = !empty( $trans['step_decision']['type'] ) ? $trans['step_decision']['type'] : '';
				switch( $trans_type )
				{
					case 'user':
						$next_steps = $trans[ 'next_steps' ];
						foreach( $next_steps AS $action => $action_info )
						{
							if( !empty( $action_info['label'] ) )
								$label = $action_info[ 'label' ];
							else
								$label = $action;
							$actions[ $action ] = $label;
						}
						break;
					
					case 'method':
						$actions[ 'next' ] = $this->default_next_text;
						break;
					
					default:
						trigger_error('Unknown transition step decision type.  How is that for programmer jargon?');
						break;
				}
			}
			else
			{
				$actions[ 'next' ] = $this->default_next_text;
			}
		}
		else
		{
			$actions[ 'next' ] = $this->default_next_text;
		}
		if($this->show_back_button && !empty( $this->_path ) )
		{
			$s = $this->get_previous_step();
			if( !empty( $this->transitions[ $s ][ 'back_button_text' ] ) )
				$actions[ 'back' ] = $this->transitions[ $s ][ 'back_button_text' ];
			else
				$actions[ 'back' ] = $this->default_back_text;
		}
		if( $final_step )
		{
			if( !empty( $this->transitions[ $this->_current_step ][ 'final_button_text' ] ) )
				$actions['next'] = $this->transitions[ $this->_current_step ][ 'final_button_text' ];
			else
				$actions[ 'next' ] = $this->default_final_text;
		}
		$f->actions = $actions;
		
		$f->run_load_phase();
		
		if( !empty( $f->chosen_action ) )
		{
			if( $f->chosen_action == 'back' )
			{
				$form_jump = $this->_remove_last_step_from_path();
			}
			// Save the last action; otherwise, it's not available to forms.
			$this->session->set('chosen_action', $f->chosen_action);
		}
		
		if( empty( $form_jump ) )
		{
			$f->run_process_phase();
			
			// $processed was added to FormStep to see if the form is done.  
			// This will be false on first time or in error checking. We
			// don't want to load the form values into the session until
			// the form has passed error checking.
			if( $f->processed )
			{
				$this->update_session_form_vars();
				// Save a value in the session to indicate that we've processed this step
				$this->set_form_data('controller_'.$this->_current_step.'_processed', true);
				$this->_add_step_to_path( $this->_current_step );
				$form_jump = $this->_determine_next_step();
			}
		}
		if( !empty( $form_jump ) )
		{
			$this->update_session_form_vars();
			if ($this->preserve_query_string)
			{
				$redirect = carl_make_redirect(array($this->_step_var_name => $form_jump));
				header('Location: ' . $redirect);
				exit;
			} else
			{
				header('Location: ' . $this->_base_url . '?' . $this->_step_var_name . '=' . $form_jump);
				exit;
			}
		}

		$timeout_msg = $this->session->get( 'timeout_msg' );
		if( !empty( $timeout_msg ) )
		{
			$this->session->set( 'timeout_msg' , '');
			echo $this->sess_timeout_msg;
		}
		
		$f->run_display_phase();
		
		if( $final_step AND $f->processed )
		{
			$final_where_to = $f->where_to();
			if ($this->clear_form_data_on_finish && !$this->destroy_session_on_finish)
			{
				$this->destroy_form_data();
				$this->reset_to_first_run();
			}
			if ($this->destroy_session_on_finish)
			{
				$this->session->destroy();
			}
			if( !empty( $final_where_to ) )
			{
				header( 'Location: '.$final_where_to );
			}
		}
	} // }}}
	
	//====================================================================//
	//========== PUBLIC DATA MANIPULATION AND RETRIEVAL METHODS ==========//
	//====================================================================//
	
	/**
	 * Gets all form class names that the Controller is using
	 * @access public
	 * @return array
	 */
	function get_form_names() // {{{
	{
		return array_keys( $this->forms );
	} // }}}
	/**
	 * Gets a particular form step
	 * @access public
	 * @return FormStep
	 * @param string $name Name of the FormStep to retrieve
	 */
	function get_form( $name ) // {{{
	{
		return $this->forms[ $name ];
	} // }}}
	/**
	 * Get all the forms in an array
	 * @access public
	 * @return array Returns the array of forms
	 */
	function get_forms() // {{{
	{
		return $this->forms;
	} // }}}
	/**
	 * Gets the Request variables this Controller needs
	 * @access public
	 * @return array
	 */
	function get_request_vars() // {{{
	{
		return array_keys( $this->_vars );
	} // }}}
	/**
	 * Gets the value of the element from whichever form it appears on
	 * @access public
	 * @return mixed
	 * @param string $var Name of the variable to get
	 */
	function get( $var ) // {{{
	{
		if (isset($this->_vars[ $var ]) && isset($this->forms[ $this->_vars[ $var ] ]))
			return $this->forms[ $this->_vars[ $var ] ]->get_value( $var );
		else
			trigger_error('Unable to get value for "'.$var.'"');
	} // }}}
	/**
	 * Gets the names of all the elements of which the controller is aware. An alias to get_request_vars().
	 * @access public
	 * @return array
	 */
	function get_element_names() // {{{
	{
		return $this->get_request_vars();
	} // }}}
	/**
	 * Adds a form to the Controller
	 * @access public
	 * @return void
	 * @param string $name Name of the class to add to the Controller
	 * @param array $transition The transition array to apply to this class.
	 * @see $transition for detailed explanation of the transition array
	 * @todo more error checking.
	 */
	function add_form( $name, $transition = array() ) // {{{
	{
		if( class_exists( $name ) )
		{
			$obj = new $name;
			if( true OR is_subclass_of( $obj, FC_STEP_CLASS ) )
			{
				$this->forms[ $name ] =& $obj;
				$this->_form_order_added[] = $name;
				$this->transitions[ $name ] = $transition;
				// TODO: init form?
			}
			else
			{
				trigger_error("$name must be a subclass of FormStep");
			}
		}
		else
		{
			trigger_error( "$name does not exist and cannot be added to this form" );
		}
	} // }}}
	/**
	 * Bulk setter.
	 * @access public
	 * @return void
	 * @param array $forms Array of transitions keyed by FormStep class names
	 */
	function add_forms( $forms ) // {{{
	{
		if( is_array( $forms ) )
		{
			foreach( $forms AS $form => $transitions )
			{
				$this->add_form( $form, $transitions );
			}
		}
		else
		{
			trigger_error('badly formatted forms array passed to add_forms()');
		}
	} // }}}
	/**
	 * Add a transition to a pre-existing FormStep
	 * @access public
	 * @return void
	 * @param string $form Step name
	 * @param array $transition_args A transition array
	 */
	function add_transition( $form, $transition_args ) // {{{
	{
		// TODO:
		// check to see if next forms actually exist
		
		// make sure decision methods exist
		
		$this->transitions[ $form ] = $transition_args;
	} // }}}
	/**
	 * Sets the internal request store
	 * Defaults to $_REQUEST if nothing is specified.
	 * @access public
	 * @return public
	 * @param array $r Array with the available Request keys-values that the Controller should have access to
	 */
	function set_request( $r = NULL ) // {{{
	{
		$this->_request = ( $r !== NULL ) ? $r : $_REQUEST;
	} // }}}
	/**
	 * Utility method for minisite system.  This can talk to the deep plasmature types to get their cleanup rules.
	 * @returns array A valid cleanup rules array with all elements as well as the form controller variables
	 */
	function get_cleanup_rules() // {{{
	{
		$rules = array();
		$rules[ $this->_step_var_name ] = array( 'function' => 'turn_into_string' );
		foreach( $this->_vars AS $key => $form )
		{
			$el = $this->forms[ $form ]->get_element( $key );
			$rules = array_merge($rules, $el->get_cleanup_rules());
		}
		return $rules;
	} // }}}
		
	function get_form_data($key)
	{
		if (!isset($this->_form_data))
		{
			$this->_form_data = $this->session->get($this->_data_key);
		}
		if (isset($this->_form_data[$key]))
			return $this->_form_data[$key];
		else
			return '';
	}
	
	function set_form_data($key, $val)
	{
		if (!isset($this->_form_data))
		{
			$this->_form_data = $this->session->get($this->_data_key);
		}
		$this->_form_data[$key] = $val;
		$this->session->set($this->_data_key, $this->_form_data);
	}

	function get_all_form_data()
	{
		if (!isset($this->_form_data))
		{
			$this->_form_data = $this->session->get($this->_data_key);
		}
		return $this->_form_data;
	}
	
	function set_all_form_data($data)
	{
		$this->_form_data = $data;
		$this->session->set($this->_data_key, $this->_form_data);
	}
	
	function destroy_form_data()
	{
		$this->session->set($this->_data_key, array());
	}

	function reset_to_first_run()
	{
		$this->session->set($this->_path_key, array());
		$this->session->set($this->_data_key . '_running', '');		
	}
	
	function set_session_class($class)
	{
		if (class_exists($class))
		{
			$this->session_class = $class;
		} else {
			trigger_error( 'FormController Error: Requested session class does not exist:' . $class);	
		}
	}

	function set_session_name($name)
	{
		if ($name)
		{
			$this->session_name = $name;
		}
	}

	/**
	 * Set a prefix on the data containers for this instance so that they
	 * won't conflict with other applications using the form controller.
	 * @access public
	 * @return void
	 */
	function set_data_context($text)
	{
		if ($text)
		{
			$this->_data_key = $text.$this->_data_key;
			$this->_path_key = $text.$this->_path_key;
		}
	}

	/**
	 * Look for the special value we saved to the session during the processing phase
	 * to determine if it has been successfully submitted.
	 * @access public
	 * @return boolean
	 */
	function get_step_is_complete($step)
	{
		if (isset ($this->forms[ $step ]))
		{
			return $this->get_form_data('controller_'.$step.'_processed');
		} else {
			trigger_error( 'FormController Error: get_step_is_complete called on nonexistent step:' . $step);	
			return false;
		}			
	}	
	
	//=========================================//
	//========== PRIVATE METHODS ==============//
	//=========================================//
	
	/**
	 * Populates each form step with the data saved in the session
	 * @access private
	 * @return void
	 */
	function _populate_step_data() // {{{
	{
		foreach( $this->_vars AS $var => $form_name )
		{
			if( $this->get_form_data( $var ) )
			{
				$fref =& $this->forms[ $form_name ];
				$fref->set_value( $var, $this->get_form_data( $var ) );
			}
		}
	} // }}}

	/**
	 * @access private
	 */
	function _determine_next_step() // {{{
	{
		// if it's the final step, there is no next step
		if( $this->_current_step == $this->_get_final_step() )
			return;
		
		// determine where to go from here.
		$trans = $this->transitions[ $this->_current_step ];
		// nothing was specified as a next step for this form.  try to figure out where to go.
		if( empty( $trans[ 'next_steps' ] ) )
		{
			// find the current form and then get the form listed after that one
			$pos = array_search( $this->_current_step, $this->_form_order_added );
			if( $pos !== false )
			{
				if( !empty( $this->_form_order_added[ $pos + 1 ] ) )
					$next_step = $this->_form_order_added[ $pos + 1 ];
				else
					trigger_error('There was no next step in the _form_order_added array');
			}
			else
			{
				trigger_error('The current step was not found in the list of steps.');
			}
		}
		// there's only one choice.  go do it.
		else if ( count( $trans[ 'next_steps' ] ) == 1 )
		{
			reset( $trans[ 'next_steps' ] );
			list( $next_step, $step_info ) = each( $trans[ 'next_steps' ] );
		}
		// more than one step possible.  determine where we are going
		else
		{
			// is there even a decision type specified?
			if( !empty( $trans[ 'step_decision' ] ) )
			{
				$dec = $trans[ 'step_decision' ];
				// check the decision type to see if this is a user decision or a method decision
				if( !empty( $dec[ 'type' ] ) )
				{
					// decision is based on what they user chose
					if( $dec[ 'type' ] == 'user' )
					{
						if( !empty( $this->forms[ $this->_current_step ]->chosen_action ) )
						{
							$next_step = $this->forms[ $this->_current_step ]->chosen_action;
						}
						else
						{
							trigger_error('no next step received from the user');
						}
					}
					// decision uses a method.
					elseif( $dec[ 'type' ] == 'method' )
					{
						if( !empty( $dec[ 'method' ] ))
						{
							$method = $dec[ 'method' ];
							// call the method specified, either on the step or on the controller
							if ( method_exists( $this->forms[ $this->_current_step ], $method ) )
							{
								$next_step = $this->forms[ $this->_current_step ]->$method();
							}
							else if ( method_exists( $this, $method ) )
							{
								$next_step = $this->$method();
							}
							else
							{
								trigger_error('the method '. $method . ' does not exist.');
							}
						}
						else
						{
							trigger_error('no method was specified for method transition.');
						}
					}
					else
					{
						trigger_error( 'unknown decision type: '.$dec['type'] );
					}
				}
				else
				{
					trigger_error('No decision type specified for the chosen transition');
				}
			}
			else
			{
				trigger_error('programmer error - no way to decide where to go from here - no decision information on this transition.');
			}
		}
		if( !empty( $next_step ) )
		{
			if( !empty( $this->forms[ $next_step ] ) )
			{
				return $next_step;
			}
			else
			{
				trigger_error( 'next_step ("'.$next_step.'") is not a valid form.  Perhaps fell off of the world' );
				return false;
			}
		}
		else
		{
			trigger_error('No next_step was found at all');
		}
	} // }}}
	/**
	 * @access private
	 */
	function _check_transitions() // {{{
	{
		// TODO:
	} // }}}
	/**
	 * @access private
	 */
	function _add_step_to_path( $step ) // {{{
	{
		$this->_path[] = $step;
		$this->session->set($this->_path_key, $this->_path);
	} // }}}

	/**
	 * @access private
	 */
	function _remove_last_step_from_path() // {{{
	{
		$step = array_pop($this->_path);
		$this->session->set($this->_path_key, $this->_path);
		return $step;
	} // }}}

}

/**
 * Multi Page Form Controller Session
 *
 * A very minimal session class for standalone use.  For Reason integration, use one of the 
 * Reason session classes
 *
 * @author Mark Heiman
 * @package disco
 * @subpackage controller
 */
class FormControllerSession
{
	var $sess_name = 'DISCO_SESS';
	var $expires = 600;
	var $_started = false;

	function FormControllerSession() {}
	/**
	 * @access public
	 */
	function start() {
		//$this->_session_existed = !empty( $_REQUEST[ $this->sess_name ] );
		session_name( $this->sess_name );
		$this->_started = true;
		if (session_id())
			return true;
		else
			return session_start();
	}
	/**
	 * @access public
	 */
	function destroy() {
		setcookie($this->sess_name,'');
		$_SESSION = array();
		session_destroy();
		$this->started = false;
	}
	/**
	 * @access public
	 */
	function has_started() // {{{
	{
		return $this->_started;
	} // }}}
	/**
	 * @access public
	 */
	function exists() {
		session_name( $this->sess_name );
		if (session_id())
		{
			$this->_started = true;
			return true;
		} else {
			return false;
		}
	}
	/**
	 * @access public
	 */
	function set_session_name($name)
	{
		$this->sess_name = $name;
	}
	/**
	 * @access public
	 */
	function set( $var, $value )
	{
		$this->_store( $var, $value );
	}
	/**
	 * @access public
	 */
	function get( $var )
	{
		return $this->_retrieve( $var );
	}
	/**
	 * @access public
	 */
	function is_idle() {}

	/**
	 * @access public
	 */
	function define_vars( $var_array )
	{
		if( is_array( $var_array ) )
		{
			$this->sess_vars = $var_array;
			foreach( $var_array AS $key )
				$this->sess_values[ $key ] = '';
		}
	}
	/**
	 * This is the implementation-specific method
	 * @access private
	 */
	function _store( $var, $val )
	{
		$_SESSION[ $var ] = $val;
	}
	/**
	 * standard retrieve just pulls from the sess_values
	 * @access private
	 */
	function _retrieve( $var )
	{
		if (isset($_SESSION[ $var ]))
			return $_SESSION[ $var ];
		else
			return '';
	}
}

?>
