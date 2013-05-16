<?php
/**
 * Dorian Junior High Camps Module
 *
 * @author Steve Smith
 * @author Lucas Welper
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

$GLOBALS[ '_module_class_names' ][ 'dorian_jh_camps/'.basename( __FILE__, '.php' ) ] = 'DorianJHCampsModule';

class DorianJHCampsModule extends DefaultMinisiteModule
{
	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
		reason_include_once( 'minisite_templates/modules/dorian_jh_camps/page1.php' );
		reason_include_once( 'minisite_templates/modules/dorian_jh_camps/page2.php' );
		reason_include_once( 'minisite_templates/modules/dorian_jh_camps/page3.php' );

		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('dorian_jh_camps');
		$this->controller->show_back_button = true;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = false;
				
		$forms = array(
			'DorianJHCampsOneForm' => array(
				'next_steps' => array(
					'DorianJHCampsTwoForm' => array(
						'label' => 'Next'),
				),
				'step_decision' => array('type'=>'user'),
				'back_button_text' => 'Back',
			),
			'DorianJHCampsTwoForm' => array(
                            'next_steps' => array(
                                'DorianJHCampsThreeForm' => array(
                                    'label' => 'Payment Information'
                                ),
                            ),
			),
                        'DorianJHCampsThreeForm' => array(
                            'final_step' => true,
                            'final_button_text' => 'Finish and Pay',
                        ),
		);
		$this->controller->add_forms( $forms );
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


	function init( $args = array() ) 
	{
		parent::init( $args );
		$url = get_current_url();
		$parts = parse_url( $url );
		$url = $parts['scheme'].'://'.$parts['host'].$parts['path'];
		
		if($head_items =& $this->get_head_items())
		{
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/form.css');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/dorian_jh_camps.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/disable_submit.js');
		}
		
		/** reload pages to save session variables after 50 minutes (3000 seconds)
		* 	of no activity.
		*	This will delete the current info on the page.		 
		*/
		
		if (isset($this->request[ '_step' ]) && $this->request[ '_step' ] == 'DorianJHCampsOneForm' || 'DorianJHCampsTwoForm')
			$seconds = 3000;
		elseif ( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )  
			$seconds = 60;
		else
			return $url;
				
			$this->parent->add_head_item('meta', array('http-equiv' => 'refresh', 'content' => $seconds . ';URL='.$url.'?ds=1' ));
	}//}}}

	/**
	 * Set up the request for the controller and run the sucker
	 * @return void
	 */
	function run() 
	{
		if( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )
		{
			reason_include_once( 'minisite_templates/modules/dorian_jh_camps/dorian_jh_camp_confirmation.php' );
			$tc = new DorianJhCampConfirmation;
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
			// MUST reconnect to Reason database.
                        // DorianJHCampConfirmation connects to dorian_jh_camp for info.
			connectDB( REASON_DB );
		}
		else
		{
			echo $this->generate_navigation();
			$this->controller->set_request( $this->request );
			$this->controller->run();
		}
	}
        function generate_navigation()
	{
		$output = '<div id="formNavigation">';
		$output .= '<ul class="formSteps">';
		foreach ($this->controller->forms as $name => $form)
		{
			$class = 'formStep';
			if (isset($form->display_name))
			{
				if ($this->controller->get_current_step() == $name)
					$class .= ' current';

//				$output .= '<li class="'.$class.'"><a href="?_step='.$name.'">'.htmlspecialchars($form->display_name).'</a></li>';
				$output .= '<li class="'.$class.'">'.$form->display_name.'</a></li>';
				
			}
		}
		$output .= '</ul></div>';
		return $output;
	}	
}
?>