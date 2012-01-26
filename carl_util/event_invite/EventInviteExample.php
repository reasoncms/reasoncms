<?php
/**
 * @package carl_util
 * @subpackage event_invite
*/

    require_once("EventInvite.php");

    $recipient = "mbockol@carleton.edu"; 
    $sender = "Reason <reason@carleton.edu>"; 
    $subject = "Very Important Meeting" ; 
    $message = "Afghanistan Bananastand." ; 
    $ical_data = getSampleIcal() ; 
    $debug = TRUE ; 
    $invite = new EventInvite($recipient, $sender, $subject, $message, $ical_data, $debug); 
    $invite->send();

    $recipients = array("mbockol@carleton.edu","mattbockol@gmail.com","spambockol@gmail.com");
    foreach($recipients as $recipient){
        $invite->setRecipient($recipient);
        $invite->setSubject("this is a message for $recipient");
        $invite->setMessage("Oh, hello $recipient. Please come to this event.\n");
        $invite->send($debug);
    }

function getSampleIcal ( )
{

$ical = 
'BEGIN:VCALENDAR
BEGIN:VEVENT
UID:d6b3ead3-83bf-4424-8d73-34ff8b255493
ORGANIZER:MAILTO:mheiman@carleton.edu
SUMMARY:Dancing under the stars
DTSTART;TZID="(GMT-06.00) Central Time (US & Canada)":20071001T230000
DTEND;TZID="(GMT-06.00) Central Time (US & Canada)":20071001T233000
LOCATION:
STATUS:CONFIRMED
X-MICROSOFT-CDO-BUSYSTATUS:BUSY
X-MICROSOFT-CDO-INTENDEDSTATUS:BUSY
TRANSP:OPAQUE
ATTENDEE;CN=Matthew A. Bockol;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSV
 P=TRUE:MAILTO:mbockol@carleton.edu
DTSTAMP:20071001T193948Z
SEQUENCE:0
DESCRIPTION:The following is a new meeting request:\n\nSubject: Dancing unde
 r the stars \nOrganizer: \"Mark F. Heiman\" <mheiman@carleton.edu> \n\nTime:
  Monday\, October 1\, 2007\, 11:00:00 PM - 11:30:00 PM (GMT-0600) America/Ch
 icago\n \nInvitees: \"Matthew A. Bockol\" <mbockol@carleton.edu> \n\n*~*~*~*
 ~*~*~*~*~*~*\n\n
END:VEVENT
END:VCALENDAR
';

return $ical ; 

}

?>
