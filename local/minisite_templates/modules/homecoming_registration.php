<?php
/**
 * Online Gift Module
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

	var $elements = array(
		'your_information' => 'comment',
		'current_name' => 'text',
		'class_year' => array(
			'type' => 'year'
			)
	
	);
	

	/**
	 * For kiosk mode; set a timeout on the second and third pages.
	 * @return void
	 */
/*
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
		
		if($head_items =& $this->get_head_items())
		{
			$head_items->add_stylesheet('/global_stock/css/gift_form.css');
			$head_items->add_javascript('/global_stock/js/gift_form.js');
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
*/

	function display_form() //{{{
	{
		echo '<div id="homecomingRegistrationForm">';
		$this->form->run();
		echo '</div>';	

	} //}}}
	
	function run()//{{{ 
	{
		$this->display_form();

	} //}}}
	
	function on_every_time();
	{
		$date = getdate();
		$this->set_element_properties('class_year', array('start' => ($date['year'] - 50), 'end' => ($date['year']-1,)));
		
	}

}
?>