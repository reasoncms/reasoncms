<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include the base class, include dependencies, and register the module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/event_registration.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventSignupModule';

/**
 * A minisite module that displays a site events calendar that allows registration
 *
 * Similar to event_registration, but recognizes repeating events(?)
 */
class EventSignupModule extends EventRegistrationModule
{
	function process_registration()
	{
		$dir = new directory_service();
		$dir->search_by_attribute('ds_username', $this->event->get_value('contact_username'), array('ds_email'));
		$to = $dir->get_first_value('ds_email');
		
		$dates = explode(',', $this->event->get_value('dates'));
		$date_strings = array();
		foreach($dates as $date)
		{
			$date_strings[] = prettify_mysql_datetime(trim($date), 'l, d F Y');
		}
		
		$subject='Event Registration: '.$_POST["name"].' for '.$this->event->get_value('name');
		$body ='Name: '.$_POST["name"]."\n";
		/*$body.="Department: ".$_POST["department"]."\n";
		$body.="Campus Address: ".$_POST["address"]."\n";
		$body.="Campus Postal Address: ".$_POST["postal_address"]."\n";
		$body.="Work Phone: ".$_POST["phone"]."\n";*/
		$body.="E-mail Address: ".$_POST["email"]."\n\n";
		$body.='Class: '.$this->event->get_value('name')."\n\n";
		$body.='Dates:'."\n".implode("\n",$date_strings)."\n\n";
		$body.='Time: '.prettify_mysql_datetime($this->event->get_value('datetime'), 'g:i a')."\n\n";
		$body.='Location: '.$this->event->get_value('location')."\n\n";
		
		// separated out so we don't repeat the content twice when we write back into the DB
		$other_info = 'Other Information: '."\n".strip_tags($this->event->get_value('content'))."\n\n";
		
		// to person who should get registration
		mail($to,$subject,$body.$other_info,"From: ".strip_tags($_POST["email"]));
		// to person who filled out email
		mail(strip_tags($_POST["email"]),$subject,$body.$other_info,"From: ".strip_tags($to));
		
		$values = array('registration'=>'full','show_hide'=>'hide', 'content'=>$this->event->get_value('content').'<h3>Registration Information</h3>'.nl2br(htmlspecialchars($body,ENT_QUOTES)));
		reason_update_entity( $this->event->id(), $this->event->get_value('last_edited_by'), $values, true );
		
		$this->show_registration_thanks();
	}
}
?>
