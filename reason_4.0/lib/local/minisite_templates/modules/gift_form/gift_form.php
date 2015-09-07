<?php
/**
 * Online Gift Module
 *
 * @author Dave Hendler
 * @since 2005-03-29
 * @package MinisiteModule
 */

/**
 * needs default module
 */
reason_include_once( 'minisite_templates/modules/default.php' );

$GLOBALS[ '_module_class_names' ][ 'gift_form/'.basename( __FILE__, '.php' ) ] = 'OnlineGiftModule';

/**
 * Run the online gift.
 * @author Dave Hendler
 * @package MinisiteModule
 */
class OnlineGiftModule extends DefaultMinisiteModule
{

	var $acceptable_params = array(
		'kiosk_mode' => false,
		);


	/**
	 * Before we clean the request vars, we need to init the controller so we know what we're initing
	 */
	function pre_request_cleanup_init()
	{
		include_once( DISCO_INC.'controller.php' );
		reason_include_once( 'minisite_templates/modules/gift_form/page1.php' );
		reason_include_once( 'minisite_templates/modules/gift_form/page2.php' );
		reason_include_once( 'minisite_templates/modules/gift_form/page3.php' );
		
		$this->controller = new FormController;
		$this->controller->set_session_class('Session_PHP');
		$this->controller->set_session_name('REASON_SESSION');
		$this->controller->set_data_context('giving_form');
		$this->controller->show_back_button = false;
		$this->controller->clear_form_data_on_finish = true;
		$this->controller->allow_arbitrary_start = true;

		$forms = array(
			'GiftPageOneForm' => array(
				'next_steps' => array(
					'GiftPageTwoForm' => array(
						'label' => 'Next Step: Personal Information',
					),
				),
				'step_decision' => array(
					'type' => 'user',
				),
				'back_button_text' => 'Change Gift Setup',
			),
			'GiftPageTwoForm' => array(
				'next_steps' => array(
					'GiftPageThreeForm' => array(
						'label' => 'Next Step: Gift Review / Payment Info',
					),
				),
				'step_decision' => array(
					'type' => 'user',
				),
				'back_button_text' => 'Change Personal Info',
			),
			'GiftPageThreeForm' => array(
				'final_step' => true,
				'final_button_text' => 'Submit Your Gift!',
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

	/**
	 * For kiosk mode; set a timeout on the second and third pages.
	 * @return void
	 */
	function init($args = array()) // {{{
	{
		parent::init( $args );
		$url = get_current_url();
		$parts = parse_url( $url );
		$url = $parts['scheme'].'://'.$parts['host'].$parts['path'];
		// Handle session reset
		if (!empty( $this->request[ 'ds' ]))
		{
			$this->controller->session->destroy();
			header( "Location: " . $url );
			return;
		}
		
		if ( !empty( $this->request[ 'gift_amount' ]))
		{
			$this->controller->set_form_data('gift_amount', $this->request[ 'gift_amount' ]);
		}
		if ( !empty( $this->request[ 'installment_type' ]))
		{
			$this->controller->set_form_data('installment_type', $this->request[ 'installment_type' ]);
		}

		if($head_items =& $this->get_head_items())
		{
			// $head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form.css');

			$head_items->add_stylesheet(JQUERY_UI_CSS_URL);
			$head_items->add_javascript(JQUERY_UI_URL);
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'luther_2014/stylesheets/sites/giving.css');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/giftform.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/disable_submit.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'luther_2014/javascripts/creditcard.js');
		}
		// Insert refresh headers when in kiosk mode
		if ($this->params['kiosk_mode'])
		{
			// There must be a better way to suppress the session expired notice when timing out
			$this->controller->sess_timeout_msg = '';

			if (isset($this->request[ '_step' ]) && $this->request[ '_step' ] == 'GiftPageThreeForm')
				$seconds = 300;
			elseif ( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )  
				$seconds = 60;
			else
				return;
				
			$this->parent->add_head_item('meta', array('http-equiv' => 'refresh', 'content' => $seconds . ';URL='.$url.'?ds=1' ));   
		}
	}

	/**
	 * Set up the request for the controller and run the sucker
	 * @return void
	 */
	function run()
	{
		echo '<div id="giving_form">'."\n";
		if( !empty( $this->request[ 'r' ] ) AND !empty( $this->request[ 'h' ] ) )
		{
			reason_include_once( 'minisite_templates/modules/gift_form/gift_confirmation.php' );
			$gc = new GiftConfirmation;
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
			// MUST reconnect to Reason database.  GiftConfirmation connects to reason_gifts for info.
			connectDB( REASON_DB );
		}
		else
		{		
			echo $this->generate_navigation();
			
			$this->controller->set_request( $this->request );
			$this->controller->run();
		}
		echo '</div>'."\n";
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
				
				$output .= '<li class="'.$class.'"><a href="?_step='.$name.'">'.htmlspecialchars($form->display_name).'</a></li>';
			}
		}
		$output .= '</ul></div>';
		return $output;
	}	
}
?>
