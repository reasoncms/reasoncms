<?php
reason_include_once( 'minisite_templates/modules/default.php' );
set_include_path('/usr/local/webapps/reason/reason_package/ZendGdata-1.10.3/library');
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AppDevOnCallModule';
//reason_include_once(DISCO.INC.'disco.php');
require_once 'Zend/Loader.php';

/**
 * @see Zend_Gdata
 */
Zend_Loader::loadClass('Zend_Gdata');

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


class AppDevOnCallModule extends DefaultMinisiteModule
{
  function init( $args = array() )
  {

  }
  
  function has_content()
  {
    return true;
  }

   

/**
 * Returns a HTTP client object with the appropriate headers for communicating
 * with Google using the ClientLogin credentials supplied.
 * 
 * @param  string $user The username, in e-mail address format, to authenticate
 * @param  string $pass The password for the user specified
 * @return Zend_Http_Client
 */    
function getClientLoginHttpClient($user, $pass) {
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
       $endTime =  split("T", $when->endTime);
       $endTime = split(":", $endTime[1]);
       $endHour = $endTime[0];
       $endMinute = $endTime[1];
       // if we haven't reached the end hour or we are currently living the end hour
       if ($endHour >= $currentHour) {
           //  if the end hour is the current hour we better check minutes
           if ($endHour == $currentHour) {
               //  if the ending minute has passed this is not the event we want
               if ($endMinute >= $currentMinute) {
                   //echo $event->title->text;
                   return $event->title->text;
               }
           }
           //  if the end hours do not match we know we haven't reached that time yet
           else {
               //echo $event->title->text;
               return $event->title->text;
           }
       }
    }
  }
}

 function run()
  { 
        $today = date("Y-m-d");
        $tomorrow_temp = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
        $tomorrow = date("Y-m-d", $tomorrow_temp);
        $day_after_tomorrow_temp = mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"));
        $day_after_tomorrow = date("Y-m-d", $day_after_tomorrow_temp);

        $client = $this->getClientLoginHttpClient('google_api_user@luther.edu', 'bTI1+9scGSkeORU');
        $currentHour = date("H");
        $currentMinute = date("i");

        $onCall = $this->getPerson($client, $today, $tomorrow, $currentHour, $currentMinute);
        if ($onCall != '') {
             echo "The on call person for today is: ".$onCall;
        }
         else {
             echo "nobody is on call at the current time";
        }    
  }
}
?>
