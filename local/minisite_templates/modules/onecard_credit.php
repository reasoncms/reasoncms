<?php
	/**
	 * OneCard Credit Module
	 *
	 * @author Mark Heiman
	 * @since 2006-11-01
	 * @package MinisiteModule
	 */
	
	/**
	 * needs default module
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'OneCardCreditModule';
	
	/**
	 * Run the credit module
	 * @author Mark Heiman
	 * @package MinisiteModule
	 */
	class OneCardCreditModule extends DefaultMinisiteModule
	{
		/**
		 * Before we clean the request vars, we need to init the controller so we know what we're initing
		 */
		function pre_request_cleanup_init()
		{
			include_once( DISCO_INC.'controller.php' );
			reason_include_once( 'minisite_templates/modules/onecard_credit/page1.php' );
			reason_include_once( 'minisite_templates/modules/onecard_credit/page2.php' );
			reason_include_once( 'minisite_templates/modules/onecard_credit/page3.php' );
			
			$this->controller = new FormController;
			//*
			$forms = array(
				'CreditPageOneForm' => array(
					'next_steps' => array(
						'CreditPageTwoForm' => array(
							'label' => 'Find Cardholder',
						),
					),
					'step_decision' => array(
						'type' => 'user',
					),
					'back_button_text' => 'Search again',
				),
				'CreditPageTwoForm' => array(
					'next_steps' => array(
						'CreditPageThreeForm' => array(
							'label' => 'Choose this cardholder',
						),
					),
					'step_decision' => array(
						'type' => 'user',
					),
				),
				'CreditPageThreeForm' => array(
					'final_step' => true,
					'final_button_text' => 'Credit this card',
				),
			);
			$this->controller->add_forms( $forms );
			$this->controller->sess_timeout_msg = '<em>You\'ve been at this page for a while without doing anything, so you may need to start the process over.</em>';
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
		/**
		 * Set up the request for the controller and run the sucker
		 * @return void
		 */
		function run() // {{{
		{
			if( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )
			{
				reason_include_once( 'minisite_templates/modules/onecard_credit/confirmation.php' );
				$gc = new CreditConfirmation;
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
				// MUST reconnect to Reason database.  CreditConfirmation connects to mysql for info.
				connectDB( REASON_DB );
			}
			else
			{
				$this->controller->set_request( $this->request );
				$this->controller->run();
			}
		} // }}}
	}
?>
