<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');


require_once 'Zend/Loader.php';

/**
 * @see Zend_Gdata
 */
Zend_Loader::loadClass('Zend_Gdata');
//include_once (ZendGdata-1.10.3/library/Zend/Loader.php)

/**
 * @see Zend_Gdata_AuthSub
 */
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

/**
 * @see Zend_Gdata_ClientLogin
 */
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

/**
 * @see Zend_Gdata_Calendar
 */
Zend_Loader::loadClass('Zend_Gdata_Calendar');
Zend_Loader::loadClass('Zend_Gdata_Extension_Visibility');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'AppDevOnCallForm';

/**
 * 
 * @author Steve Smith
 */
class AppDevOnCallForm extends DefaultThorForm
{

	var $info;
	/**
	* Returns a HTTP client object with the appropriate headers for communicating
	* with Google using the ClientLogin credentials supplied.
	* 
	* @param  string $user The username, in e-mail address format, to authenticate
	* @param  string $pass The password for the user specified
	* @return Zend_Http_Client
	*/    
	function getClientLoginHttpClient($user, $pass) 
	{
		$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
		$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
		return $client;
	}
	
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
	function getPerson($client, $startDate, $endDate)
	{
          $startDate = date("c");	
          $tomorrow_temp = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
          $endDate = date("Y-m-d", $tomorrow_temp);

          $gdataCal = new Zend_Gdata_Calendar($client);
          $query = $gdataCal->newEventQuery();
          $query->setUser('luther.edu_39333139333636353730@resource.calendar.google.com');
          //$query->setUser('luther.edu_9530n4c10faloia8q6ov32ddek@group.calendar.google.com'); // TEST CALENDAR
          $query->setVisibility('private');
          $query->setProjection('full');
          $query->setOrderby('starttime');
          $query->setStartMin($startDate);
          $query->setStartMax($endDate);
          $query->setFutureevents(false);
          $query->setSingleevents(false);
          $query->setSortorder('a');
          $eventFeed = $gdataCal->getCalendarEventFeed($query);
          foreach ($eventFeed as $event) {
            foreach ($event->when as $when) {
              $eventStatusUrl = $event->getEventStatus()->__toString();
              list($trash, $eventStatus) = explode('#', $eventStatusUrl); //$eventStatusUrl
              if ($eventStatus == 'event.confirmed') {
                return $event->title->text;
              }
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
		$now = date("c");
		
		$tomorrow_temp = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		$tomorrow = date("Y-m-d", $tomorrow_temp);
		$next_week_temp = mktime(0, 0, 0, date("m")  , date("d")+7, date("Y"));
		$next_week = date("Y-m-d", $next_week_temp);
		$client = $this->getClientLoginHttpClient('google_api_user@luther.edu', 'bTI1+9scGSkeORU');
		
		$onCall = $this->getPerson($client, $now, $tomorrow);
		if (($onCall != '') && (date("H") > 7) && (date("H") < 17)) {
		     // this is where we should send a text message and probably an email to the on-call person
		     $developer_info = $this->get_developer_info($onCall);
		     $this->notify_developer($developer_info, 'sms');
		}
		 else {
		     // this is where we would let the HD/requestor know that nobody is on-call at this time and
		     //   send an email to the next available on call person (next available)
		     $next_available = $this->getPerson($client, $now, $next_week);

                     if ($next_available == '') {
                       $next_available = 'Ben';
                     }

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
