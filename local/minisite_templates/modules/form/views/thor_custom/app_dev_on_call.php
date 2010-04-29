<?
include_once('reason_header.php');
//include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once(DISCO_INC.'disco.php');
//include_once(DISCO_INC.'plasmature/plasmature.php');
reason_include_once('classes/user.php');
reason_include_once('classes/admin/admin_page.php');

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
	 * @return void
	 */
	function getPerson($client, $startDate, $endDate, $currentHour, $currentMinute)
	{
	  $gdataCal = new Zend_Gdata_Calendar($client);
	  $query = $gdataCal->newEventQuery();
	  $query->setUser('luther.edu_39333139333636353730@resource.calendar.google.com');
	  $query->setVisibility('private');
	  $query->setProjection('full');
	  $query->setOrderby('starttime');
	  $query->setStartMin($startDate);
	  $query->setStartMax($endDate);
	  $query->setFutureevents(false);
	  $query->setSingleevents(true);
	  $query->setSortorder('a');
	  $eventFeed = $gdataCal->getCalendarEventFeed($query);
	  foreach ($eventFeed as $event) {
	    foreach ($event->when as $when) {
	               return $event->title->text;
	    }
	  }
	}
	
	function get_developer_info($developer)
	{
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
			case "cindy": $dev = array('email' => 'goede@luther.edu', 'sms' => '5633808899@email.uscc.net');
				break;
			case "jean": $dev = array('email' => 'gehlje01@luther.edu', 'sms' => '');
				break;
			case "lucas": $dev = array('email' => 'welplu01@luther.edu', 'sms' => 'lucas\'s sms address');
				break;
			case "marcia": $dev = array('email' => 'gullick@luther.edu', 'sms' => '');
				break;
			case "steve": $dev = array('email' => 'steve.smith@luther.edu', 'sms' => '563-419-1556@vtext.com');
	  	}
	  	return $dev;
	}

	// check username
	// return google calendar information here if nothing is returned throw an error
	function run_error_checks()
	{ 
		$username = $this->get_value_from_label('username');
		if ($username = 'steve')
			$this -> set_error('username', 'invalid username');

		$now = date("c");
		$tomorrow_temp = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		$tomorrow = date("Y-m-d", $tomorrow_temp);
		$next_week_temp = mktime(0, 0, 0, date("m")  , date("d")+7, date("Y"));
		$next_week = date("Y-m-d", $next_week_temp);
		
		$client = $this->getClientLoginHttpClient('google_api_user@luther.edu', 'bTI1+9scGSkeORU');
		
		$onCall = $this->getPerson($client, $now, $tomorrow, $currentHour, $currentMinute);
		if ($onCall != '') {
		     // this is where we should send a text message and probably an email to the on-call person
		     echo "The on call person for today is ".$onCall.".";
		     echo $onCall.'<br />';
		     $this->get_developer_info($onCall);
		}
		 else {
		     // this is where we would let the HD/requestor know that nobody is on-call at this time and
		     //   send an email to the next available on call person (next available)
		     $next_available = $this->getPerson($client, $now, $next_week, $currentHour, $currentMinute);
		     //echo "Nobody is on call at the current time, but " . $next_available . " is next in line";
		     //echo $next_available.'<br />';
		      $t = $this->get_developer_info($next_available);
		     print_r($t);
		}    
	}
	
	function on_every_time()
	{	
		//$date = $this->get_element_name_from_label('Date needed');
		//$this->change_element_type($date, 'textdate', array('display_name'=>'Desired "go live" date',));
		//$url_field = $this->get_element_name_from_label('Your website URL');
		//$plain = 'www.luther.edu/page/to/work/on';
		//$this->add_comments($url_field, '<br />e.g. <em>'.$plain.'</em>');

	}
}
?>
