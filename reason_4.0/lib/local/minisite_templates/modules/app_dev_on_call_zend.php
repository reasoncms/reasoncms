<?php 
/**
unset($CFG);
// This file has all the site settings, such
// as domain, admin user, password, and preferences
include 'google-config.php';
*/

/**
 * @see Zend_Loader
 */
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
function outputCalendar($client, $startDate='2010-04-22', $endDate='2010-04-23')
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
  $eventFeed = $gdataCal->getCalendarEventFeed($query);
  // option 2
  // $eventFeed = $gdataCal->getCalendarEventFeed($query->getQueryUrl());
  echo "<ul>\n";
  foreach ($eventFeed as $event) {
    echo "\t<li>" . $event->title->text;
    // Zend_Gdata_App_Extensions_Title->__toString() is defined, so the
    // following will also work on PHP >= 5.2.0
    //echo "\t<li>" . $event->title .  " (" . $event->id . ")\n";
    echo "\t\t<ul>\n";
    foreach ($event->when as $when) {
      echo "\t\t\t<li>Starts: " . $when->startTime . "</li>\n";
      echo "\t\t\t<li>Ends:  " . $when->endTime . "</li>\n";
    }
    echo "\t\t</ul>\n";
    echo "\t</li>\n";
  }
  echo "</ul>\n";
}

// log in 
$client = getClientLoginHttpClient('google_api_user@luther.edu', "bTI1'+'9scGSkeORU");

outputCalendar($client);
?>
