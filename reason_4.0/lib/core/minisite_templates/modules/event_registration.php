<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include the base class, include dependencies, and register the module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/events.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventRegistrationModule';

/**
 * A minisite module that displays a site calendar and offer basic event registration capability to logged-in users
 *
 * Is this deprecated??
 *
 */
class EventRegistrationModule extends EventsModule
{
	
	function show_event() // {{{
	{
		if ($this->event->get_values() && ($this->event->get_value('type') == id_of('event_type')) && ($this->event->get_value('show_hide') == 'show'))
		{
			$this->show_event_details();
			$this->registration_logic();
		}
		else
			$this->show_event_error();
	} // }}}
	function registration_logic()
	{
		if(!($this->event->get_value('last_occurence') < date('Y-m-d')))
		{
			if(!empty($_SERVER[ 'REMOTE_USER' ]) && $this->event->get_value('registration') == 'available')
			{
				if(!empty($_POST['submit']))
					$this->process_registration();
				else
					$this->show_registration_form();
			}
			elseif($this->event->get_value('registration') == 'full')
				echo '<h3>Event registration not available</h3>'."\n".'<p>This event is fully registered.</p>'."\n";
		}
	} // }}}
	function show_registration_form()
	{
		$dir = new directory_service();
		$dir->search_by_attribute('ds_username', $_SERVER[ 'REMOTE_USER' ], array('ds_email','ds_fullname'));
		$email = $dir->get_first_value('ds_email');
		$fullname = $dir->get_first_value('ds_fullname');
		
		echo '<div id="eventRegistration">'."\n";
		echo '<h3>Register for '.$this->event->get_value('name').'</h3>'."\n";
		echo '<form method="POST">'."\n";
		echo '<p><strong>Name:</strong> '.htmlspecialchars($fullname, ENT_QUOTES).'<input type="hidden" name="name" id="name" value="'.htmlspecialchars($fullname, ENT_QUOTES).'"></p>'."\n";
		echo '<p><strong>E-mail address:</strong> '.htmlspecialchars($email, ENT_QUOTES).'<input type="hidden" name="email" id="email" value="'.htmlspecialchars($email, ENT_QUOTES).'"></p>'."\n";
		echo '<input type="submit" name="submit" id="submit" value="Register">'."\n";
		echo '</form>'."\n";
		echo '</div>'."\n";
	}
	function process_registration()
	{
		$dir = new directory_service();
		$dir->search_by_attribute('ds_username', $this->event->get_value('contact_username'), array('ds_email'));		
		$to = $dir->get_first_value('ds_email');
		$subject='Event Registration: '.$_POST["name"].' for '.$this->event->get_value('name');
		$body ='Name: '.$_POST["name"]."\n";
		$body.="E-mail Address: ".$_POST["email"]."\n\n";
		$body.='Class: '.$this->event->get_value('name')."\n\n";
		$body.='Date & Time: '.prettify_mysql_datetime($this->event->get_value('datetime'), 'm/d/Y \a\t g:i a')."\n\n";
		$body.='Location: '.$this->event->get_value('location')."\n\n";
		
		// to person who should get registration
		mail($to,$subject,$body,"From: ".strip_tags($_POST["email"]));
		// to person who filled out email
		mail(strip_tags($_POST["email"]),$subject,$body,"From: ".strip_tags($to));
		
		$this->show_registration_thanks();
	}
	function show_registration_thanks()
	{
		echo '<h3>Thanks for registering, '.htmlspecialchars($_POST["name"], ENT_QUOTES).'!</h3>'."\n";
		echo '<p>An email copy of your registration request will be sent to '.htmlspecialchars($_POST['email'], ENT_QUOTES).'.</p>'."\n";
	}
}
?>
