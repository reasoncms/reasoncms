<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Demos
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * PHP sample code for the Google Calendar data API.  Utilizes the
 * Zend Framework Gdata components to communicate with the Google API.
 *
 * Requires the Zend Framework Gdata components and PHP >= 5.1.4
 *
 * You can run this sample both from the command line (CLI) and also
 * from a web browser.  When running through a web browser, only
 * AuthSub and outputting a list of calendars is demonstrated.  When
 * running via CLI, all functionality except AuthSub is available and dependent
 * upon the command line options passed.  Run this script without any
 * command line options to see usage, eg:
 *     /usr/local/bin/php -f Calendar.php
 *
/*
 * zendcal.php by bob puffer 2010-06-30
 * to query google for calendar event information
 * expects array $events in which to load the calendar event objects
 * expects array $users of all calendars to retrieve events for specific values
     * lis
     * fin
     * sports
     * adm
 * will substitute constants for abbreviated users and their respective magic cookie
 * expects $startdate and $enddate (up to but not including) for scope of calendar events retrieval 
*/

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_Calendar');

//$client = getClientLoginHttpClient($user, $pass);
$email = array(
    lis=>'dept_cal_lis@luther.edu'
    );
$cookie = array(
    lis=>'cf6173368b13181b6555ffa27250a864'
    );
foreach ($users as $user) {
    $magicCookie = $cookie[$user];
    $user = $email[$user];
    $events[] = outputCalendarMagicCookie($user, $magicCookie, $startDate, $endDate);
//    outputCalendarMagicCookie($user, $magicCookie, $startdate, $enddate);
}

/**
 * Outputs an HTML unordered list (ul), with each list item representing an event
 * in the user's calendar.  The calendar is retrieved using the magic cookie
 * which allows read-only access to private calendar data using a special token
 * available from within the Calendar UI.
 *
 * @param  string $user        The username or address of the calendar to be retrieved.
 * @param  string $magiccookie The magic cookie token
 * @return void
 */
function outputCalendarMagicCookie($user, $magicCookie, $startDate = NULL,
                                   $endDate = NULL) {
  $gdataCal = new Zend_Gdata_Calendar();
  $query = $gdataCal->newEventQuery();
  $query->setUser($user);
  $query->setVisibility('private-' . $magicCookie);
  $query->setProjection('full');
  $query->setOrderby('starttime');
  $query->setStartMin($startDate);
  $query->setStartMax($endDate);
  $eventFeed = $gdataCal->getCalendarEventFeed($query);
  echo "<ul>\n";
  foreach ($eventFeed as $event) {
    $sl = $event->getLink('self')->href;
    echo "\t<li>" . '<a href="' . $sl . '" target="_blank" >' . $event->title->text . "</a>";
    echo "\t\t<ul>\n";
    foreach ($event->when as $when) {
      echo "\t\t\t<li>Starts: " . $when->startTime . "</li>\n";
    }
    echo "\t\t</ul>\n";
    echo "\t</li>\n";
  }
  echo "</ul>\n";

  return $eventFeed;
}
?>
