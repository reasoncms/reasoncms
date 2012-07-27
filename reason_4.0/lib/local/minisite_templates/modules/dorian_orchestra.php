<?php
/**
 * Dorian Orchestra Festival Director Nomination Registration Module
 *
 * @author Lucas Welper
 * @since 2010-11-03
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

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DorianOrchestraModule';

/**
 * Run the Dorian Vocal Registration
 * @author Steve Smith
 * @package MinisiteModule
 */
class DorianOrchestraModule extends DefaultMinisiteModule
{
	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
		reason_include_once( 'minisite_templates/modules/dorian_orchestra/director_info.php' );
		reason_include_once( 'minisite_templates/modules/dorian_orchestra/student_info.php' );
		reason_include_once( 'minisite_templates/modules/dorian_orchestra/confirmation.php' );
		
		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('dorian_orchestra');
		$this->controller->show_back_button = false;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = false;
		//*
		$forms = array(
			'DirectorInfoForm' => array(
				'next_steps' => array(
					'StudentInfoForm' => array(
						'label' => 'Nominate Students',
					),
				),
                                'start_step' => true,
                                'back_button_text' => 'Back',
				'step_decision' => array(
					'type' => 'user',
				),
				'display_name' => 'Yo',
			),
			'StudentInfoForm' => array(
                                'next_steps' => array(
                                        'StudentInfoForm' => array(
                                            'label' => 'Nominate Another Student',
                                        ),
                                        'ConfirmationForm' => array(
											'label' => 'I\'m Finished Nominating Students',
										),
                                ),
                                'step_decision' => array(
					'type' => 'user',
				),
                                'back_button_text' => 'Back',
				//'final_step' => true,
				//'final_button_text' => 'I\'m Finished Nominating Students',
			),
			'ConfirmationForm' => array(
				//'final_step' => true,
				'display_name' => 'Dorian Vocal Nomination Details',
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
			$head_items->add_stylesheet('/reason/css/form.css');
			$head_items->add_javascript('/reason/js/dorian_orchestra.js');
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
			reason_include_once( 'minisite_templates/modules/dorian_orchestra/dorian_orchestra_confirmation.php' );
			$dvc = new DorianOrchestraConfirmation;
			$dvc->set_ref_number( $this->request[ 'r' ] );
			$dvc->set_hash( $this->request[ 'h' ] );

			if( $dvc->validates() )
			{
				echo $dvc->get_confirmation_text();
			}
			else
			{
				echo $dvc->get_error_message();
			}
			// MUST reconnect to Reason database.  DorianOrchestraConfirmation connects to dorian_orchestra_nomination for info.
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