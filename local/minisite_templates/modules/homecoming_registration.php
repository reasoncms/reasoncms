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

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'HomecomingRegistrationModule';

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
			//$head_items->add_stylesheet('/css/form/homecoming_reg.css');
			$head_items->add_javascript('http://reasondev.luther.edu/javascripts/form/homecoming_reg.js');
		}
	}//}}}
	
	/*
function display_form() //{{{
	{
		$date = getdate();
		$this->form->change_element_type( 
			'class_year', 'year', array('start' => ($date['year'] - 75), 'end' => ($date['year']-1)));
		$this->form->change_element_type( 
			'guest_class', 'year', array('start' => ($date['year'] - 75), 'end' => ($date['year']-1)));

		// Set years and cost for luncheon
		$classes_string_75_to_50 = 'for Classes ';
		for ($i = 75; $i >= 55; $i -= 5){
			$classes_string_75_to_50 .= ($date['year'] - $i);
			$classes_string_75_to_50 .= ', ';
		}
		$classes_string_75_to_50 .= $date['year'] - 50;
		$this->form->change_element_type(
			'attend_luncheon', 'select_no_sort', array(
				'display_name' => 'Tickets for Luncheon',
				'comments' => '<br />'.$classes_string_75_to_50.'<br />No Cost',
				'options' => array(
					'--' => '--', 
					'1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', 
					'6' => '6', '7' => '7', '8' => '8', '9' => '9',	'10' => '10',),
				)
			);

		// Set years and ticket cost for 45 to 25 year reunions
		$classes_string_45_to_25 = 'for Classes ';
		for ($j = 45; $j >= 30; $j -= 5){
			$classes_string_45_to_25 .= ($date['year'] -$j);
			$classes_string_45_to_25 .= ', ';
		}
		$classes_string_45_to_25 .= $date['year'] - 25;
		$this->form->change_element_type(
			'attend_dinner_50_to_25', 'select_no_sort', array(
				'display_name' => 'Attend Dinner', 
				'comments' => '<br />'.$classes_string_45_to_25.'<br />$20/person',
				'options' => array(
					'--'=>'--', 
					'$20'=>'1 ticket, $20',
					'$40'=>'2 tickets, $40',
					'$60'=>'3 tickets, $60',
					'$80'=>'4 tickets, $80',
					'$100'=>'5 tickets, $100',
					'$120'=>'6 tickets, $120',
				),
			));
		// Set years and ticket cost for 20 to 10 year reunions		
		$classes_string_20_to_10 = 'for Classes ';
		for ($k = 20; $k >= 15; $k -= 5){
			$classes_string_20_to_10 .= ($date['year'] -$k);
			$classes_string_20_to_10 .= ', ';
		}
		$classes_string_20_to_10 .= $date['year'] - 10;
		$this->form->change_element_type(
			'attend_dinner_20_to_10', 'select_no_sort', array(
				'display_name' => 'Attend Reception', 
				'comments' => '<br />'.$classes_string_20_to_10.'<br />$15/person',
				'options' => array(
					'--'=>'--', 
					'$15'=>'1 ticket, $15',
					'$30'=>'2 tickets, $30',
					'$45'=>'3 tickets, $45',
					'$60'=>'4 tickets, $60',
					'$75'=>'5 tickets, $75',
					'$90'=>'6 tickets, $90',
				),
			));
			
		// Set cost for 5 year reunion
		$this->form->change_element_type(
			'attend_dinner_5', 'select_no_sort', array(
				'display_name' => 'Attend Reception',
				'comments' => '<br />for Class of '. ($date['year']-5) .'<br />$10/person',
				'options' => array(
					'--'=>'--', 
					'$10'=>'1 ticket, $10',
					'$20'=>'2 tickets, $20',
					'$30'=>'3 tickets, $30',
					'$40'=>'4 tickets, $40',
					'$50'=>'5 tickets, $50',
					'$60'=>'6 tickets, $60',

					),
				)
			);
		
		
		echo '<div id="homecomingRegistrationForm">';
		$this->form->run();
		echo '</div>';	

	} //}}}
	
*/
	
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
		
	/*
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
				
				$output .= '<li class="'.$class.'"><a href="?_step='.$name.'">'.htmlspecialchars($form->display_name).'</a></li>';
			}
		}
		$output .= '</ul></div>';
		return $output;
	}
*/
}
?>