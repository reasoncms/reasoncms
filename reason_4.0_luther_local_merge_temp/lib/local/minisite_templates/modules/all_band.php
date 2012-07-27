<?php
/**
 * All Band Alumni Reuninion Module
 *
 * @author Steve Smith
  * @since 2011-04-21
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

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AllBandModule';

class AllBandModule extends DefaultMinisiteModule
{
	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
		reason_include_once( 'minisite_templates/modules/all_band/page1.php' );
		reason_include_once( 'minisite_templates/modules/all_band/page2.php' );

		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('all_band');
		$this->controller->show_back_button = false;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = false;
		//*
		$forms = array(
			'AllBandOne' => array(
				'next_steps' => array(
					'AllBandTwo' => array(
						'label' => 'Next'),
				),
				'step_decision' => array('type'=>'user'),
				'back_button_text' => 'Back',
			),
			'AllBandTwo' => array(
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
                    $head_items->add_javascript('/reason/js/norge_form.js');
		}
	}
	/**
	 * Set up the request for the controller and run the sucker
	 * @return void
	 */
	function run()
	{
		if ( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )
		{
			reason_include_once( 'minisite_templates/modules/all_band/all_band_confirmation.php' );
			$abc = new AllBandConfirmation;
			$abc->set_ref_number( $this->request[ 'r' ] );
			$abc->set_hash( $this->request[ 'h' ] );

			if ( $abc->validates() )
			{
				echo $abc->get_confirmation_text();
			}
			else
			{
				echo $abc->get_error_message();
			}
			// MUST reconnect to Reason database.
                        // AllBandConfirmation connects to all_band for info.
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