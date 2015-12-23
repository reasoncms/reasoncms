<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * The parent class
 */
require_once( DISCO_INC.'disco.php');
/**
 * The directory service class
 */
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
/**
 * User libraries
 */
reason_include_once( 'function_libraries/user_functions.php');
reason_include_once( 'classes/user.php');

$GLOBALS[ '_slot_registration_view_class_names' ][ basename( __FILE__, '.php') ] = 'EventSlotRegistrationForm';

/**
 * A form by which people can register for an event's registration slot
 */
class EventSlotRegistrationForm extends Disco{
	var $box_class = 'StackedBox';
	/**
	* Array of elements the form will use
	* @access public
	* @var array
	*/
	var $elements = array(
		'event_date',
		'name',
		'email',
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
		'email' => array(
			'email_is_correctly_formatted' => 'The email address you entered does not appear to be valid.  Please check to make sure you entered it correctly',
		 ),
	);
	
	var $delimiter1;
	var $delimiter2;
	var $event;
	var $request_array;
	var $cancel_link;
	
	var $show_date_change_link = false;
	var $include_time_in_email = true;
	
	var $actions = array('Register');
	
	function __construct($event_entity, $request_array, $delimiter1, $delimiter2, $cancel_link)
	{
		$this->event = $event_entity;
		$this->request_array = $request_array;
		$this->delimiter1 = $delimiter1;
		$this->delimiter2 = $delimiter2;
		$this->cancel_link = $cancel_link;
	}
	
	function email_is_correctly_formatted()
	{
		// Taken from http://us2.php.net/eregi, adapted for preg_match
		if( !preg_match('|^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$|i',$this->get_value('email')))
		{
			return false;
		}
		return true;
	}
	
	function on_every_time()
	{
		$this->change_element_type('event_date', 'solidtext');
		$this->set_value('event_date', prettify_mysql_datetime($this->request_array['date']));
		if ($this->show_date_change_link == true)
		{
			$link = carl_make_link(array('date' => '', 'slot_id' => ''));
			$this->add_comments('event_date', '<a href="'. $link . '">Register for a different date</a>');
		}
	}
	
	function show_date_change_link()
	{
		$this->show_date_change_link = true;
	}
	
	/**
	* Actions which happen based on the validated data from the form.
	* @access public
	*/
	function process()
	{
		// lets make sure the event_agent user exists
		$user = new User();
		if (!$user->get_user('event_agent')) $user->create_user('event_agent');
		
		$name = str_replace(array($this->delimiter1, $this->delimiter2), '_', $this->get_value('name'));
		
		$new_data = implode($this->delimiter2, array(strip_tags($this->request_array['date']),strip_tags($name),strip_tags($this->get_value('email')), time() ) );	
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
	
		if ($notifications = $slot_entity['notification_email'])
		{
			$tos = array();
			$dir = new directory_service();
			$addresses = preg_split('/[\s,;]+/', $notifications);
			foreach ($addresses as $address)
			{
				if (strpos($address, '@'))
					$tos[] = $address;
				else
				{
					if ($dir->search_by_attribute('ds_username', $address, array('ds_email','ds_fullname','ds_phone',)))
						$tos[] = $dir->get_first_value('ds_email');
				}
			}
		}
		$subject = 'Event Registration: '.$this->get_value('name').' for '.$this->event->get_value('name');
		$body = $this->get_value('name').' has registered for '.$this->event->get_value('name')."\n\n";
		$body .= 'Name: '.$this->get_value('name')."\n";
		$body .= "E-mail Address: ".$this->get_value('email')."\n";
		$body .= 'Date: '.prettify_mysql_datetime($this->request_array['date'], 'm/d/Y')."\n";
		
		if ($this->include_time_in_email)
		{
			$time = $this->event->get_value('datetime');
			$time_parts = explode(' ',$time);
			if($time_parts[1] != '00:00:00')
			{
				$body .= 'Time: '.prettify_mysql_datetime($time,'g:i a')."\n";
			}
		}
		$location = $this->event->get_value('location');
		if(!empty($location))
			$body.='Location: '.$location."\n";
		$slot = $slot_entity['name'];
		$body .= 'Slot: '.$slot."\n\n";
		
		// to person who should get registration notifications
		if (!empty($tos))
		{
			mail(join(',', $tos),$subject,$body,'From: '.strip_tags($this->get_value('email')));
			$sender = 'From: '.reset($tos);
		}
		else
		{
			$sender = null;
		}
		// to person who filled out email
		mail(strip_tags($this->get_value('email')),$subject,$body,$sender); 
	
	}
	
	function show_registration_thanks()
	{
		echo '<div class="formResponse">'."\n";
		echo '<h4>Thanks for registering, '.htmlspecialchars($this->get_value('name'),ENT_QUOTES).'!</h4>'."\n";
		echo '<p>An email copy of your registration request will be sent to '.htmlspecialchars($this->get_value('email'),ENT_QUOTES).'.</p>'."\n";
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
		echo '<p>We were unable to process your registration at this time. The webmaster ('.WEBMASTER_NAME.') has been notified. Please try again later.</p>';
		echo '</div>'."\n";
		
		$to = WEBMASTER_EMAIL_ADDRESS;
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
