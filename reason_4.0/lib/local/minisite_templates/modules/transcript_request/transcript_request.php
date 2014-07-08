<?php
/**
 * Transcript Request Module
 *
 * @author Steve Smith 
 * @since 2010-11-24
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

$GLOBALS[ '_module_class_names' ][ 'transcript_request/'.basename( __FILE__, '.php' ) ] = 'TranscriptRequestModule';

/**
 * Run the transcript request form.
 * @author Steve Smith
 * @package MinisiteModule
 */
//class TranscriptRequestModule extends DefaultMinisiteWithAuthModule
class TranscriptRequestModule extends DefaultMinisiteModule
{
	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
//                reason_include_once( 'minisite_templates/modules/transcript_request/pre.php' );
		reason_include_once( 'minisite_templates/modules/transcript_request/page1.php' );
		reason_include_once( 'minisite_templates/modules/transcript_request/page2.php' );
		reason_include_once( 'minisite_templates/modules/transcript_request/page3.php' );
		
		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('transcript_request');
		$this->controller->show_back_button = false;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = false;
		//*
		$forms = array(
//                        'TranscriptPreForm' => array(
//				'next_steps' => array(
//					'TranscriptPageOneForm' => array(
//						'label' => 'Come on',
//					),
//				),
//				'step_decision' => array(
//					'type' => 'user',
//				),
//				'back_button_text' => 'Back',
//				'display_name' => 'Yo',
//			),
			'TranscriptPageOneForm' => array(
				'next_steps' => array(
					'TranscriptPageTwoForm' => array(
						'label' => 'Come on',
					),
					'TranscriptRequestConfirmation' => array(
						'label' => 'Transcript Confirmation',
					),
				),
				'step_decision' => array(
					'type' => 'method',
					'method' => 'needs_payment',
				),
				'back_button_text' => 'Back',
				'display_name' => 'Yo',

			),
			'TranscriptPageTwoForm' => array(
				'final_step' => true,
				'final_button_text' => 'Finish and Pay',
			),
			'TranscriptRequestConfirmation' => array(
				'display_name' => 'Transcript Confirmation',
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

                //$this->msg_uname = reason_unique_name_exists('transcript_login_blurb');
                //$this->user = reason_check_authentication();

		if($head_items =& $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_error.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'local/css/transcripts.css');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/transcripts.js');
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
			reason_include_once( 'minisite_templates/modules/transcript_request/transcript_confirmation.php' );
			$tc = new TranscriptConfirmation;
			$tc->set_ref_number( $this->request[ 'r' ] );
			$tc->set_hash( $this->request[ 'h' ] );
			
			if( $tc->validates() )
			{
				echo $tc->get_confirmation_text();
			}
			else
			{
				echo $tc->get_error_message();
			}
			// MUST reconnect to Reason database.  TranscriptConfirmation connects to transcript_request for info.
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