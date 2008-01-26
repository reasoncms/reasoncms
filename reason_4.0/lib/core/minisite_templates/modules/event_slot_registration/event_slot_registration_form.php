<?php 
        require_once( DISCO_INC.'disco.php');
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
        reason_include_once( 'function_libraries/user_functions.php');

class EventSlotRegistrationForm extends Disco{

	/**
	* Array of elements the form will use
	* @access public
	* @var array
	*/
	var $elements = array(
		'name',
		'email',
		'event_date' => 'hidden',
		'old_registrant_data' => 'hidden',
	);
	
	/**
	* Array of fields which are required
	* @access public
	* @var array
	*/	
	var $required = array(
		'name',
		'email',
	);
	
	var $error_checks = array(
		'name' => array(
			'contains_no_delimiters' => 'Fields cannot contain the \';\' or \'|\' characters.',
		),
		'email' => array(
			'email_is_correctly_formatted' => 'The email address you entered does not appear to be valid.  Please check to make sure you entered it correctly',
		 ),
	);
	
	var $delimiter1;
	var $delimiter2;
	var $event;
	var $request_array;
	var $cancel_link;
	
	function EventSlotRegistrationForm($event_entity, $request_array, $delimiter1, $delimiter2, $cancel_link)
	{
		$this->event = $event_entity;
		$this->request_array = $request_array;
		$this->delimiter1 = $delimiter1;
		$this->delimiter2 = $delimiter2;
		$this->cancel_link = $cancel_link;
	}
	
	
	function contains_no_delimiters()
	{
		$name = $this->get_value('name');
		if(strpos($name, $this->delimiter1)||strpos($name, $this->delimiter2))
		{
			return false;
		}
		return true;
	}
	
	function email_is_correctly_formatted()
	{
		// Taken from http://us2.php.net/eregi
		if( !eregi('^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$',$this->get_value('email')))
		{
			return false;
		}
		return true;
	}
	
	
	/**
	* Actions which happen based on the validated data from the form.
	* @access public
	*/
	function process(){

		$new_data = implode($this->delimiter2, array(strip_tags($this->request_array['date']),strip_tags($this->get_value('name')),strip_tags($this->get_value('email')), time() ) );	
		$slot_values = get_entity_by_id($this->request_array['slot_id']);

		$old_data = $slot_values['registrant_data'];
		if(!empty($old_data)) $registrant_data = $old_data.$this->delimiter1.$new_data;
		else $registrant_data = $new_data;
		
		$values = array (
			'registrant_data' => $registrant_data, 
		);

		$successful_update = reason_update_entity( $this->request_array['slot_id'], get_user_id('event_agent'), $values );
		
		if($successful_update)
		{
			$this->show_form = false;
			$this->send_confirmation_emails();
			$this->show_registration_thanks();
		}
		else
			$this->show_registration_error_message();	
	}
	
	

	//sends an email to the contact for the event and to the registrant	
	//still needs to include the slot
	function send_confirmation_emails()
	{
		$slot_entity = get_entity_by_id($this->request_array['slot_id']);
	
		$dir = new directory_service();
		$dir->search_by_attribute('ds_username', $this->event->get_value('contact_username'), array('ds_email','ds_fullname','ds_phone',));
		$to = $dir->get_first_value('ds_email');

		$subject='Event Registration: '.$this->get_value('name').' for '.$this->event->get_value('name');
		$body = $this->get_value('name').' has registered for '.$this->event->get_value('name')."\n\n";
		$body.='Name: '.$this->get_value('name')."\n";
		$body.="E-mail Address: ".$this->get_value('email')."\n";
//		the original code gives the datetime field of event in order to give both date & time, but that doesn't work for repeating events.  How to do both date & time?
		$body .= 'Date: '.prettify_mysql_datetime($this->request_array['date'], 'm/d/Y')."\n";
#		$body.='Class: '.$this->event->get_value('name')."\n\n";
#		$body.='Date & Time: '.prettify_mysql_datetime($this->event->get_value('datetime'), 'm/d/Y \a\t g:i a')."\n\n";
		$location = $this->event->get_value('location');
		if(!empty($location))
			$body.='Location: '.$location."\n";
		$slot = $slot_entity['name'];
		$body .= 'Slot: '.$slot."\n\n";
		
		// to person who should get registration
		mail($to,$subject,$body,"From: ".strip_tags($this->get_value('email')));
		// to person who filled out email
		mail(strip_tags($this->get_value('email')),$subject,$body,"From: ".strip_tags($to)); 
	
	}
	
	function show_registration_thanks()
	{
		echo '<div class="formResponse">'."\n";
		echo '<h4>Thanks for registering, '.$this->get_value('name').'!</h4>'."\n";
		echo '<p>An email copy of your registration request will be sent to '.$this->get_value('email').'.</p>'."\n";
		echo '</div>'."\n";
	}
	
	function post_show_form()
	{
		echo '<div id="cancel_link"><p><a href="';
		echo $this->cancel_link;
		echo '">';
		echo 'Cancel registration';
		echo '</a></p></div>';
	}
	
	//who should be contacted?  or should this exist?
	function show_registration_error_message()
	{
		echo '<div class="formResponse">'."\n";
		echo '<h4>Sorry.</h4>'."\n";
		echo '<p>We were unable to process your registration at this time. The Web Services Group has been notified of the error and will investigate the cause. Please try again later.</p>';
		echo '</div>'."\n";
		
		$to = 'nwhite@acs.carleton.edu';
		$subject = 'Slot registration error';
		$body = "There was an error with slot registration for ".$this->event->get_value('name').'.'."\n\n";
		$body = "The following person was not successfully registered\n\n";
		$body .='Name: '.$this->get_value('name')."\n";
		$body .="E-mail Address: ".$this->get_value('email')."\n";
		$body .='Date: '.prettify_mysql_datetime($this->request_array['date'], 'm/d/Y')."\n";
		mail($to,$subject,$body,"From: ".strip_tags($this->get_value('email')));		
	}
	
}
?>
