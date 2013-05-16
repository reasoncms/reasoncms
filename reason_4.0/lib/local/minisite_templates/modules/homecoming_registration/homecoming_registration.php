<?php
/**
 * Homecoming Registration Module
 *
 * @author Steve Smith 
 * @since 2010-05-07
 * @package MinisiteModule
 */

/**
 * needs default module
 */
reason_include_once( 'minisite_templates/modules/default.php' );
/**
 * needs Disco
 */
include_once(DISCO_INC . 'disco.php');

$GLOBALS[ '_module_class_names' ][ 'homecoming_registration/'.basename( __FILE__, '.php' ) ] = 'HomecomingRegistrationModule';

/**
 * Run the homecoming registration.
 * @author Steve Smith
 * @package MinisiteModule
 */
class HomecomingRegistrationModule extends DefaultMinisiteModule
{
	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
		reason_include_once( 'minisite_templates/modules/homecoming_registration/homecoming1.php' );
		reason_include_once( 'minisite_templates/modules/homecoming_registration/homecoming2.php' );
		reason_include_once( 'minisite_templates/modules/homecoming_registration/homecoming3.php' );
		
		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('homecoming_registration');
		$this->controller->show_back_button = false;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = true;
		//*
		$forms = array(
			'HomecomingRegistrationOneForm' => array(
				'next_steps' => array(
					'HomecomingRegistrationTwoForm' => array(
						'label' => 'Come on',
					),
					'HomecomingRegistrationConfirmation' => array(
						'label' => 'Homecoming Confirmation',
					),
				),
				'step_decision' => array(
					'type' => 'method',
					'method' => 'needs_payment',
				),
				'back_button_text' => 'Back',
				'display_name' => 'Yo',

			),
			'HomecomingRegistrationTwoForm' => array(
				'final_step' => true,
				'final_button_text' => 'Register',
			),
			'HomecomingRegistrationConfirmation' => array(
				'display_name' => 'Homecoming Confirmation',
			),
		);
		$this->controller->add_forms( $forms );
		// */
		$this->controller->init();
	}	
	
	/**
	 * Add possible forms variables that may come through to the list of vetted request vars
	 * @return void
	 */
	function get_cleanup_rules()
	{
		$rules = array();
		// debug var - resets form and destroys session
		$rules[ 'ds' ] = array( 'function' => 'turn_into_string' );
		// vars for confirmation page to let through
		$rules[ 'r' ] = array( 'function' => 'turn_into_string' );
		$rules[ 'h' ] = array( 'function' => 'turn_into_string' );
		// Allows form to be put into testing mode through a query string
		$rules[ 'tm' ] = array( 'function' => 'turn_into_int' );
		// add all cleanup rules from the form controller
		$rules = array_merge( $rules, $this->controller->get_cleanup_rules() );
		return $rules;
	}


	function init( $args = array() ) //{{{
	{	
		parent::init( $args );
		if($head_items =& $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/form.css');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/homecoming_reg.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/disable_submit.js');
		}
	}//}}}
	
	/**
	 * Set up the request for the controller and run the sucker
	 * @return void
	 */
	function run() // {{{
	{
		if( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )
		{
			reason_include_once( 'minisite_templates/modules/homecoming_registration/homecoming_confirmation.php' );
			$gc = new HomecomingConfirmation;
			$gc->set_ref_number( $this->request[ 'r' ] );
			$gc->set_hash( $this->request[ 'h' ] );
			
			if( $gc->validates() )
			{
				echo $gc->get_confirmation_text();
			}
			else
			{
				echo $gc->get_error_message();
			}
			// MUST reconnect to Reason database.  HomecomingConfirmation connects to homecoming_registration for info.
			connectDB( REASON_DB );
		}
		else
		{
//			echo $this->generate_navigation();
			$this->controller->set_request( $this->request );
			$this->controller->run();
		}
	} // }}}
}
?>