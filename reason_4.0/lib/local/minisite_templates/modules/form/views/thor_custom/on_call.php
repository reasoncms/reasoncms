<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');

/** 
 * api dependencies
 */

require_once('google-api-php-client/src/Google_Client.php');
require_once('google-api-php-client/src/contrib/Google_CalendarService.php');

//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'AppDevOnCallForm';

/**
 * 
 * @author Steve Smith
 */
class AppDevOnCallForm extends DefaultThorForm
{

	var $info;
	var $client;
	var $service;

	/**
	 * Outputs an HTML unordered list (ul), with each list item representing an
	 * event on the authenticated user's calendar.  Includes the start time and
	 * event ID in the output.  Events are ordered by starttime and include only
	 * events occurring in the future.
	 *
	 * @param  Zend_Http_Client $client The authenticated client object
         * return $event->title->text;
	 * @return void
	 */
	function getPerson($startDate, $endDate)
	{
		    // Initialise the Google Client object
			$this->client = new Google_Client();
			// Your 'Product name'
			$this->client->setApplicationName('reason-softdev-on-call');

			$this->client->setAssertionCredentials(
				new Google_AssertionCredentials(
					'561991832721-573m3493sj8k5n1rvcp9gpr7obuj0tgq@developer.gserviceaccount.com', // email you added to GA
				    array('https://www.googleapis.com/auth/calendar.readonly'),
				    file_get_contents(REASON_INC.'lib/local/keys/google-on-call-key.p12'),  // keyfile you downloaded
				    'notasecret'
				)
			);
			// other settings
			$this->client->setClientId('561991832721-573m3493sj8k5n1rvcp9gpr7obuj0tgq.apps.googleusercontent.com');
			// Return results as objects.
			$this->client->setUseObjects(true);
			$this->client->setAccessType('offline_access');  // this may be unnecessary?

			// create analytics service
			$this->service = new Google_CalendarService($this->client);
			$events = $this->service->events->listEvents('luther.edu_39333139333636353730@resource.calendar.google.com',
				                                         array(
				                                         	'orderBy'=>'startTime',
				                                         	'singleEvents'=>true,
				                                         	'timeMin'=>$startDate,
                              							 	'timeMax'=>$endDate,
                              							 	'timeZone'=>'UTC'));
			while(true) {
			  foreach ($events->getItems() as $event) {
			    $event_start_time = $event->getStart()->getDateTime();
			    $event_end_time = $event->getEnd()->getDateTime();
			    $event_status = $event->getStatus();
			    $current_datetime = date(DateTime::ATOM, time());
		    	if (($current_datetime >= $event_start_time) && ($current_datetime <= $event_end_time) && ($event_status == "confirmed")) {
		    		return $event->getSummary();
		    	}
			  }
			  $pageToken = $events->getNextPageToken();
			  if ($pageToken) {
			    $optParams = array('pageToken' => $pageToken);
			    $events = $this->service->events->listEvents('primary', $optParams);
			  } else {
			    break;
			  }
			}
	}
	
	function get_user_info($username)
	{	
		//query ldap ou=People to get the user info for the user having the problem
		$attributes = array('uid','cn','sn','officePhone');
		
		$dir = new directory_service('ldap_luther');
		$dir->search_by_attribute('uid', $username, $attributes);
		$record = $dir->get_first_record();
				
		return $record;
	}

	
	function get_developer_info($developer)
	{
		$dev ='';
		$developer = strtolower($developer);
		switch ($developer)
		{
			/*
			US Cellular: 	phonenumber@email.uscc.net
			SunCom: 		phonenumber@tms.suncom.com
			Powertel: 		phonenumber@ptel.net	
			AT&T: 			phonenumber@txt.att.net
			Alltel: 		phonenumber@message.alltel.com
			Metro PCS: 		phonenumber@MyMetroPcs.com
			Verizon:		phonenumber@vtext.com
			*/

			case "ben": $dev = array('email' => 'wilbbe01@luther.edu', 'sms' => '5074290136@vtext.com');
				break;
			case "bob": $dev = array('email' => 'puffro01@luther.edu', 'sms' => '');
				break;
			case "cindy": $dev = array('email' => 'goede@luther.edu', 'sms' => '5633808899@vtext.com');
				break;
			case "jean": $dev = array('email' => 'gehlje01@luther.edu', 'sms' => '5633805445@vtext.com');
				break;
			case "lane": $dev = array('email' => 'schwla01@luther.edu', 'sms' => '5634193233@vtext.com');
				break;
			case "nathan": $dev = array('email' => 'porana02@luther.edu', 'sms' => '4024174829@vtext.com');
				break;
			case "nate": $dev = array('email' => 'porana02@luther.edu', 'sms' => '4024174829@vtext.com');
				break;
			case "marcia": $dev = array('email' => 'gullick@luther.edu', 'sms' => '5633808127@email.uscc.net');
				break;
			case "steve": $dev = array('email' => 'steve.smith@luther.edu', 'sms' => '5634191556@vtext.com');
	  	}
	  	return $dev;
	}

	// check for username in ldap if nonexistent, throw an error
	function run_error_checks()
	{ 
		$username_field = $this->get_element_name_from_label('Username');
		$username = $this->get_value_from_label('Username');	

		$user_info = $this->get_user_info($username);

		if (!$user_info)
			$this -> set_error($username_field, 'Username does not exist.');
		global $info;
		$info = $user_info;
	}
	
	function process()
	{
		$today = date(DateTime::ATOM, mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $tomorrow = date(DateTime::ATOM, mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
        $next_week = date(DateTime::ATOM, mktime(0, 0, 0, date("m"), date("d")+7, date("Y")));
		
		$onCall = $this->getPerson($today, $tomorrow);
		if (($onCall != '') && (date("H") > 7) && (date("H") < 17)) {
		     // this is where we should send a text message and probably an email to the on-call person
		     $developer_info = $this->get_developer_info($onCall);
		     $this->notify_developer($developer_info, 'sms');
		}
		 else {
		     // this is where we would let the HD/requestor know that nobody is on-call at this time and
		     //   send an email to the next available on call person (next available)
		     $next_available = $this->getPerson($today, $next_week);
		     $developer_info = $this->get_developer_info($next_available);
		     $this->notify_developer($developer_info, 'email');
		}
		// send an email to helpdesk for auto ticket creation
		// kbox@helpdesk.luther.edu
		$txt = "Software Development Emergency Auto-Generate";
		$txt .= "\n";
		$txt .= $this->get_value_from_label('Emergency');
		$hd_mail = new Email('kbox@help.luther.edu', $this->get_value_from_label('Username'), $this->get_value_from_label('Username'), $this->get_value_from_label('Emergency'), $txt, $txt);
		$hd_mail->send();
	}
	
	
	function notify_developer($developer_info, $type)
	{
		global $info;
		
		$problem = $this->get_value_from_label('Emergency');
		
		$recipient = $developer_info[$type];
		$sender = 'AppDevOnCall@luther.edu';
		$txt_body = $problem;
		$html_body = '';
		

		$subject = $this->get_value_from_label('Username')."\n";
		$subject .= $info['officephone'][0]."\n";
		if (isset($info['officephone'][1])) $subject .= $info['officephone'][1];
		
		$mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
		$mailer->send();
	}
	
	function on_every_time()
	{	
		$username_field = $this->get_element_name_from_label('Username');
		$emergency_field = $this->get_element_name_from_label('Emergency'); 
		
		$this->set_comments($username_field, '<br />The username of the user having the problem<br />');
		$this->change_element_type($emergency_field, 'radio_with_other_no_sort');

	}
}
?>
