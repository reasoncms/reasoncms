<?php
/**
 * Discovery Camps Module
 *
 * @author Steve Smith
 * @since 2011-01-26
 * @package Local Modules
 */

/**
 * needs default module
 */
reason_include_once( 'minisite_templates/modules/default.php' );
/**
 * needs Disco
 */
include_once(DISCO_INC . 'disco.php');

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DiscoveryCampsModule';

class DiscoveryCampsModule extends DefaultMinisiteModule
{
	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
		reason_include_once( 'minisite_templates/modules/discovery_camps/page1.php' );
		reason_include_once( 'minisite_templates/modules/discovery_camps/page2.php' );

		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('discovery_camps');
		$this->controller->show_back_button = false;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = true;
		//*
		$forms = array(
                    'DiscoveryCampsOne' => array(
                        'next_steps' => array('DiscoveryCampsTwo' => array('label' => 'Next'),
                        ),
                        'step_decision' => array('type'=>'user'),
                    ),
                    'DiscoveryCampsTwo' => array(
                        'back_button_text' => 'Back',
                        'final_step' => true,
                        'final_button_text' => 'Finish and Pay',
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
			$head_items->add_stylesheet('/reason/css/giftform.css');
			$head_items->add_javascript('/reason/js/dorian_sh_camp.js');
		}
	}//}}}

	/**
	 * Set up the request for the controller and run the sucker
	 * @return void
	 */
	function run()
	{
		if( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )
		{
			reason_include_once( 'minisite_templates/modules/discovery_camps/confirmation.php' );
			$dc = new DiscoveryCampsConfirmation;
			$dc->set_ref_number( $this->request[ 'r' ] );
			$dc->set_hash( $this->request[ 'h' ] );

			if( $dc->validates() )
			{
				echo $dc->get_confirmation_text();
			}
			else
			{
				echo $dc->get_error_message();
			}
			// MUST reconnect to Reason database.
                        // DiscoveryCampsConfirmation connects to discovery_camps for info.
			connectDB( REASON_DB );
		}
		else
		{
			$this->controller->set_request( $this->request );
			$this->controller->run();
		}
	}        
}
?>